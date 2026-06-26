<?php
/**
 * PayStation Payment Gateway Integration
 * Handles PayStation payment initiation, status checking, and callbacks
 */


/** ===========================================================================
 * DOCS for Your Reference
 * 
 * PayStation: Sync pending transaction statuses by cron job
 * Please check the file inc/cron-job-apis.php
 */
// ============================================================================

if (!defined('ABSPATH')) {
    exit;
}

// Include student unique ID functions
require_once(get_template_directory() . '/inc/student-unique-id.php');

/**
 * Get current BDT (Bangladesh Time) - UTC+6
 * @return string Date in Y-m-d H:i:s format
 */
function get_bdt_time() {
    $bdt_timezone = new DateTimeZone('Asia/Dhaka');
    $bdt_time = new DateTime('now', $bdt_timezone);
    return $bdt_time->format('Y-m-d H:i:s');
}

/**
 * Get current BDT date - UTC+6
 * @return string Date in Y-m-d format
 */
function get_bdt_date() {
    $bdt_timezone = new DateTimeZone('Asia/Dhaka');
    $bdt_time = new DateTime('now', $bdt_timezone);
    return $bdt_time->format('Y-m-d');
}

/**
 * Get BDT time with custom format
 * @param string $format Date format (default: Y-m-d H-i-s for backend compatibility)
 * @return string Formatted date
 */
function get_bdt_time_formatted($format = 'Y-m-d H-i-s') {
    $bdt_timezone = new DateTimeZone('Asia/Dhaka');
    $bdt_time = new DateTime('now', $bdt_timezone);
    return $bdt_time->format($format);
}

/**
 * Get PayStation configuration
 */
function get_paystation_config() {
    return array(
        'merchant_id' => get_option('paystation_merchant_id', '1950-1765784432'),
        'password' => get_option('paystation_password', 'A3$#n432'),
        'sandbox_url' => 'https://sandbox.paystation.com.bd',
        'production_url' => 'https://api.paystation.com.bd',
        'mode' => get_option('paystation_mode', 'production'), // sandbox or production
    );
}

/**
 * Get PayStation base URL based on mode
 */
function get_paystation_base_url() {
    $config = get_paystation_config();
    return $config['mode'] === 'production' ? $config['production_url'] : $config['sandbox_url'];
}

/**
 * Store PayStation transaction
 */
function paystation_store_transaction($payment_id, $student_data, $fee_data, $paystation_invoice_number = null) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'paystation_transactions';
    
    // Ensure table exists - use direct SQL for reliability
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    if (!$table_exists) {
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            payment_id varchar(255) NOT NULL,
            invoice_number varchar(255) DEFAULT NULL,
            student_id bigint(20),
            student_data longtext,
            fee_data longtext,
            total_amount decimal(10,2),
            status varchar(50) DEFAULT 'pending',
            transaction_id varchar(255) DEFAULT NULL,
            paystation_response longtext,
            payment_date datetime,
            created_at datetime,
            updated_at datetime,
            PRIMARY KEY (id),
            UNIQUE KEY payment_id (payment_id),
            KEY invoice_number (invoice_number),
            KEY status (status)
        ) $charset_collate";
        
        $result = $wpdb->query($sql);
        if ($result === false) {
            error_log('PayStation: Failed to create table - ' . $wpdb->last_error);
        } else {
            error_log('PayStation: Table created successfully - ' . $table_name);
        }
    }
    
    // Check if transaction already exists
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_name WHERE payment_id = %s",
        $payment_id
    ));
    
    $bdt_now = get_bdt_time();
    
    if ($existing) {
        // Update existing transaction - preserve created_at, only update updated_at
        $result = $wpdb->update(
            $table_name,
            array(
                'invoice_number' => $paystation_invoice_number,
                'student_id' => $student_data['student_id'],
                'student_data' => json_encode($student_data),
                'fee_data' => json_encode($fee_data),
                'total_amount' => $fee_data['total_amount'],
                'status' => 'pending',
                'updated_at' => $bdt_now,
            ),
            array('payment_id' => $payment_id),
            array('%s', '%d', '%s', '%s', '%f', '%s', '%s'),
            array('%s')
        );
    } else {
        // Insert new transaction with BDT time
        error_log('PayStation: Inserting new transaction with BDT time: ' . $bdt_now);
        $result = $wpdb->insert(
            $table_name,
            array(
                'payment_id' => $payment_id,
                'invoice_number' => $paystation_invoice_number,
                'student_id' => $student_data['student_id'],
                'student_data' => json_encode($student_data),
                'fee_data' => json_encode($fee_data),
                'total_amount' => $fee_data['total_amount'],
                'status' => 'pending',
                'created_at' => $bdt_now,
                'updated_at' => $bdt_now,
            ),
            array('%s', '%s', '%d', '%s', '%s', '%f', '%s', '%s', '%s')
        );
    }
    
    if ($result === false) {
        error_log('PayStation: Failed to store transaction - ' . $wpdb->last_error);
        error_log('PayStation: Payment ID: ' . $payment_id . ', Invoice: ' . $paystation_invoice_number);
        error_log('PayStation: Last query: ' . $wpdb->last_query);
        error_log('PayStation: BDT time used: ' . $bdt_now);
    } else {
        $action = $existing ? 'updated' : 'inserted';
        $insert_id = $existing ? $existing : $wpdb->insert_id;
        error_log('PayStation: Transaction ' . $action . ' successfully - Payment ID: ' . $payment_id . ', ID: ' . $insert_id . ', created_at: ' . ($existing ? 'preserved' : $bdt_now));
        
        // Verify the inserted/updated record
        if (!$existing) {
            $verify = $wpdb->get_var($wpdb->prepare(
                "SELECT created_at FROM $table_name WHERE payment_id = %s",
                $payment_id
            ));
            error_log('PayStation: Verified created_at in DB: ' . ($verify ?: 'NULL'));
        }
    }
}

/**
 * Get PayStation transaction by payment_id
 */
function paystation_get_transaction($payment_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'paystation_transactions';
    $transaction = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE payment_id = %s",
        $payment_id
    ));
    
    if ($transaction) {
        if ($transaction->student_data) {
            $transaction->student_data = json_decode($transaction->student_data, true);
        }
        if ($transaction->fee_data) {
            $transaction->fee_data = json_decode($transaction->fee_data, true);
        }
        if ($transaction->paystation_response) {
            $transaction->paystation_response = json_decode($transaction->paystation_response, true);
        }
    }
    
    return $transaction;
}

/**
 * Get PayStation transaction by invoice_number
 */
function paystation_get_transaction_by_invoice($invoice_number) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'paystation_transactions';
    $transaction = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE invoice_number = %s",
        $invoice_number
    ));
    
    if ($transaction) {
        if ($transaction->student_data) {
            $transaction->student_data = json_decode($transaction->student_data, true);
        }
        if ($transaction->fee_data) {
            $transaction->fee_data = json_decode($transaction->fee_data, true);
        }
        if ($transaction->paystation_response) {
            $transaction->paystation_response = json_decode($transaction->paystation_response, true);
        }
    }
    
    return $transaction;
}

/**
 * Update PayStation transaction status
 */
function paystation_update_transaction($payment_id, $data) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'paystation_transactions';
    
    $update_data = array();
    $format = array();
    
    if (isset($data['status'])) {
        $update_data['status'] = $data['status'];
        $format[] = '%s';
    }
    
    if (isset($data['paystation_response'])) {
        $update_data['paystation_response'] = is_array($data['paystation_response']) 
            ? json_encode($data['paystation_response']) 
            : $data['paystation_response'];
        $format[] = '%s';
    }
    
    if (isset($data['payment_date'])) {
        $update_data['payment_date'] = $data['payment_date'];
        $format[] = '%s';
    }
    
    if (isset($data['invoice_number'])) {
        $update_data['invoice_number'] = $data['invoice_number'];
        $format[] = '%s';
    }

    if (isset($data['transaction_id'])) {
        $update_data['transaction_id'] = $data['transaction_id'];
        $format[] = '%s';
    }

    if (!empty($update_data)) {
        $wpdb->update(
            $table_name,
            $update_data,
            array('payment_id' => $payment_id),
            $format,
            array('%s')
        );
    }
}

/**
 * Initiate PayStation payment using cURL
 * 
 * @param string $payment_id Internal payment ID
 * @param array $student_data Student information
 * @param array $fee_data Fee breakdown and totals
 * @return array|WP_Error Response from PayStation or error
 */
function paystation_initiate_payment($payment_id, $student_data, $fee_data) {
    $config = get_paystation_config();
    $base_url = get_paystation_base_url();
    
    // Generate invoice number (use payment_id or generate unique number)
    $invoice_number = $payment_id;
    
    // Prepare form data exactly as PayStation expects
    $form_data = array(
        'invoice_number' => $invoice_number,
        'currency' => 'BDT',
        'payment_amount' => number_format($fee_data['total_amount'], 2, '.', ''),
        'pay_with_charge' => 1,
        'reference' => 'Student Fee Payment - ' . $student_data['student_name'],
        'cust_name' => $student_data['student_name'],
        'cust_phone' => $student_data['cust_phone'] ?? '',
        'cust_email' => $student_data['cust_email'] ?? '',
        'cust_address' => $student_data['cust_address'] ?? 'N/A',
        'callback_url' => home_url('/paystation-callback?invoice_number=' . urlencode($invoice_number)),
        'checkout_items' => json_encode($fee_data['fee_breakdown']),
        'merchantId' => $config['merchant_id'],
        'password' => $config['password'],
    );
    
    // Store transaction before API call
    paystation_store_transaction($payment_id, $student_data, $fee_data, $invoice_number);
    
    // PayStation API URL
    $url = $base_url . '/initiate-payment';
    
    // Log the request for debugging
    error_log('PayStation Request URL: ' . $url);
    error_log('PayStation Request Data: ' . print_r($form_data, true));
    
    // Use PHP cURL as per PayStation documentation
    $curl = curl_init();
    
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => $form_data,
        CURLOPT_SSL_VERIFYPEER => true,
    ));
    
    $response_body = curl_exec($curl);
    $curl_error = curl_error($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    // Log response for debugging
    error_log('PayStation Response Code: ' . $http_code);
    error_log('PayStation Response: ' . $response_body);
    
    // Check for cURL errors
    if ($curl_error) {
        paystation_update_transaction($payment_id, array(
            'status' => 'failed',
            'paystation_response' => array('error' => $curl_error)
        ));
        return new WP_Error('paystation_curl_error', 'cURL Error: ' . $curl_error);
    }
    
    // Parse JSON response
    $response_data = json_decode($response_body, true);
    
    // Handle different response formats
    if (json_last_error() !== JSON_ERROR_NONE) {
        // Not JSON - check if response contains a redirect URL
        if (preg_match('/https?:\/\/[^\s<>"\']+/', $response_body, $matches)) {
            $payment_url = $matches[0];
            paystation_update_transaction($payment_id, array(
                'paystation_response' => array('redirect_url' => $payment_url, 'raw_response' => substr($response_body, 0, 500))
            ));
            return array(
                'success' => true,
                'payment_url' => $payment_url,
                'invoice_number' => $invoice_number,
                'data' => array('redirect_url' => $payment_url)
            );
        }
        
        paystation_update_transaction($payment_id, array(
            'status' => 'failed',
            'paystation_response' => array('html_response' => substr($response_body, 0, 1000))
        ));
        
        // Return detailed error with actual response for debugging
        $error_detail = 'HTTP ' . $http_code . ' - Response: ' . substr($response_body, 0, 300);
        return new WP_Error(
            'paystation_error',
            'PayStation Error: ' . $error_detail,
            array('status' => $http_code, 'response' => substr($response_body, 0, 1000))
        );
    }
    
    // Update transaction with response
    paystation_update_transaction($payment_id, array(
        'paystation_response' => $response_data
    ));
    
    // Handle JSON response - check for success and payment URL
    // PayStation API format: status_code "200" and status "success" for success
    // PayStation API format: status_code "1008" (or other) and status "failed" for failure
    $status_code = isset($response_data['status_code']) ? $response_data['status_code'] : null;
    $api_status = isset($response_data['status']) ? strtolower($response_data['status']) : '';
    
    // Check for success based on PayStation API format
    $is_success = false;
    if ($status_code === '200' || $status_code === 200) {
        if ($api_status === 'success') {
            $is_success = true;
        }
    }
    
    // Fallback: Check if payment_url exists (might be success even without status_code)
    if (!$is_success && (isset($response_data['payment_url']) || isset($response_data['redirect_url']))) {
        $is_success = true;
    }
    
    // Get payment URL
    $payment_url = $response_data['payment_url'] ?? 
                  $response_data['redirect_url'] ?? 
                  $response_data['url'] ?? 
                  $response_data['data']['payment_url'] ?? 
                  $response_data['data']['redirect_url'] ?? '';
    
    if ($is_success && !empty($payment_url)) {
        return array(
            'success' => true,
            'payment_url' => $payment_url,
            'invoice_number' => $invoice_number,
            'data' => $response_data
        );
    } else {
        // Failed response - get error message from PayStation API format
        $error_msg = $response_data['message'] ?? $response_data['error'] ?? $response_data['msg'] ?? 'Failed to initiate payment';
        return new WP_Error('paystation_error', $error_msg, array('status' => $http_code, 'status_code' => $status_code, 'response' => $response_data));
    }
}

/**
 * Check PayStation transaction status using cURL
 * 
 * @param string $invoice_number PayStation invoice number
 * @return array|WP_Error Transaction status or error
 */
function paystation_check_status($invoice_number) {
    $config = get_paystation_config();
    $base_url = get_paystation_base_url();
    
    $url = $base_url . '/transaction-status';
    
    // Use cURL for consistency with PayStation API
    $curl = curl_init();
    
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array(
            'invoice_number' => $invoice_number,
            'merchantId' => $config['merchant_id'],
            'password' => $config['password'],
        ), 
        CURLOPT_HTTPHEADER     => ['merchantId: ' . $config['merchant_id'],],
        CURLOPT_SSL_VERIFYPEER => true,
    ));
    
    $response_body = curl_exec($curl);
    $curl_error = curl_error($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    // Log for debugging
    error_log('PayStation Status Check URL: ' . $url);
    error_log('PayStation Status Response: ' . $response_body);
    
    if ($curl_error) {
        return new WP_Error('paystation_curl_error', 'cURL Error: ' . $curl_error);
    }
    
    $response_data = json_decode($response_body, true);
    
    // PayStation API format: status_code "200" and status "success" for success
    $status_code = isset($response_data['status_code']) ? $response_data['status_code'] : null;
    $api_status = isset($response_data['status']) ? strtolower($response_data['status']) : '';
    
    // Check if response indicates success
    if ($http_code === 200) {
        // Check PayStation API format
        if (($status_code === '200' || $status_code === 200) && $api_status === 'success') {
            return array(
                'success' => true,
                'data' => $response_data
            );
        } elseif ($api_status === 'failed') {
            // PayStation returned failed status
            $error_msg = $response_data['message'] ?? 'Transaction failed';
            return new WP_Error(
                'paystation_error',
                $error_msg,
                array('status' => $http_code, 'status_code' => $status_code, 'response' => $response_data)
            );
        } else {
            // HTTP 200 but unclear status - return data anyway
            return array(
                'success' => true,
                'data' => $response_data
            );
        }
    } else {
        // HTTP error
        $error_msg = $response_data['message'] ?? $response_data['error'] ?? 'Failed to check transaction status';
        return new WP_Error(
            'paystation_error',
            $error_msg,
            array('status' => $http_code, 'status_code' => $status_code, 'response' => $response_data)
        );
    }
}

/**
 * Process PayStation payment confirmation
 * Similar to rocket_api confirm payment but for PayStation
 */
function paystation_confirm_payment($payment_id, $invoice_number, $transaction_data = array()) {
    global $wpdb;
    
    error_log('PayStation: confirm_payment called - payment_id: ' . $payment_id . ', invoice_number: ' . $invoice_number);
    
    // Get transaction
    $transaction = paystation_get_transaction($payment_id);
    
    if (!$transaction) {
        error_log('PayStation: Transaction not found for payment_id: ' . $payment_id);
        return new WP_Error('transaction_not_found', 'Transaction not found');
    }
    
    if ($transaction->status === 'paid') {
        error_log('PayStation: Payment already processed for payment_id: ' . $payment_id);
        return new WP_Error('already_paid', 'Payment already processed');
    }
    
    // Get student and fee data
    $student_data = $transaction->student_data;
    $fee_data = $transaction->fee_data;
    
    error_log('PayStation: Fee breakdown count: ' . count($fee_data['fee_breakdown']));
    error_log('PayStation: Fee breakdown: ' . json_encode($fee_data['fee_breakdown']));
    $student_id = $student_data['student_id'];
    $class_id = $student_data['class_id'];
    $section = $student_data['section'];
    $group_id = $student_data['group_id'];
    $year = $student_data['year'];
    $fee_month = $fee_data['month'];
    // Use BDT time for payment date
    $payment_date = isset($transaction_data['payment_date']) 
        ? $transaction_data['payment_date'] 
        : get_bdt_time();
    
    // Get PayStation transaction ID from callback data
    $paystation_txn_id = isset($transaction_data['paystation_txn_id']) 
        ? $transaction_data['paystation_txn_id'] 
        : $invoice_number;
    
    // Get global variables
    global $admissionFeeSubHeadId, $admissionFormSubHeadId, $monthlyFeeSubHeadId;
    global $examFeeSubHeadId, $transportFeeSubHeadId, $ictFeeSubHeadId;
    global $registrationFeeSubHeadId, $coachingFeeSubHeadId;
    global $cashSubHeadId, $paystationSubHeadId;
    
    // Get PayStation sub head ID for online payments (instead of cash)
    // PayStation sub_head_id is set to 250 (defined in ajaxAction.php getPaystationSubHeadId function)
    if (!isset($paystationSubHeadId)) {
        $paystationSubHeadId = 250; // Hardcoded PayStation sub_head_id
    }
    
    // Use PayStation sub_head_id for ledger entry (not cash)
    $ledger_sub_head_id = $paystationSubHeadId;
    
    // Format payment date properly (ensure it's in Y-m-d format)
    $formatted_payment_date = date('Y-m-d', strtotime($payment_date));
    
    // Get month name for notes
    $month_names = array(1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December');
    $month_name = isset($month_names[$fee_month]) ? $month_names[$fee_month] : $fee_month;
    
    // Create descriptive notes
    $notes = 'Online Payment via PayStation - Transaction ID: ' . substr($paystation_txn_id, -12) . 
             ' | Invoice: ' . substr($invoice_number, -12) . 
             ' | Payment Date: ' . date('d M Y', strtotime($payment_date));
    
    // Ensure the ct_student_fee_collection_info table has the required columns
    $collection_table = 'ct_student_fee_collection_info';
    $required_columns = array(
        'payment_method'  => "VARCHAR(50) DEFAULT NULL",
        'transaction_id'  => "VARCHAR(255) DEFAULT NULL",
        'payment_id'      => "VARCHAR(255) DEFAULT NULL",
    );
    foreach ($required_columns as $col_name => $col_def) {
        $col_exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM information_schema.COLUMNS 
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s AND COLUMN_NAME = %s",
            $collection_table,
            $col_name
        ));
        if (!$col_exists) {
            $wpdb->query("ALTER TABLE $collection_table ADD COLUMN $col_name $col_def");
            error_log("PayStation: Added column $col_name to $collection_table");
        }
    }
    
    // Insert into fee collection info with payment_method, transaction_id and payment_id
    // Match backend format: date format 'Y-m-d H-i-s' (with hyphens)
    $info_id = $wpdb->insert(
        'ct_student_fee_collection_info',
        array(
            'student_roll' => $student_data['student_roll'],
            'student_id' => $student_id,
            'year' => $year,
            'month' => $fee_month,
            'class_id' => $class_id,
            'section' => $section,
            'group_id' => $group_id,
            'sub_total' => $fee_data['sub_total'],
            'total' => $fee_data['total_amount'],
            'remission' => $fee_data['remission'],
            'remission_category' => null,
            'status' => 1, // Paid
            'notes' => $notes,
            'payment_method' => 'PayStation API',
            'transaction_id' => $paystation_txn_id,
            'payment_id' => $payment_id,
            'date' => $formatted_payment_date,
            'created_by' => 0, // System/API
            'created_at' => get_bdt_time_formatted(), // BDT time - Match backend format with hyphens
        )
    );
    
    if (!$info_id) {
        return new WP_Error('database_error', 'Failed to save payment record: ' . $wpdb->last_error);
    }
    
    $collection_info_id = $wpdb->insert_id;
    
    // Insert fee breakdown details - per-month rows for monthly fees
    foreach ($fee_data['fee_breakdown'] as $fee_item) {
        if ($fee_item['fee_type'] === 'monthly') {
            // Monthly fees: insert one row per month with month column
            $base_fee_query = "SELECT fee FROM ct_student_fee_list 
                             WHERE sub_head_id = %d AND class_id = %d AND year = %s";
            $base_fee_params = array($fee_item['sub_head_id'], $class_id, $year);
            if ($group_id) {
                $base_fee_query .= " AND group_id = %d";
                $base_fee_params[] = $group_id;
            }
            $base_fee_query .= " ORDER BY id DESC LIMIT 1";
            $base_fee_result = $wpdb->get_row($wpdb->prepare($base_fee_query, $base_fee_params));
            $base_monthly_fee = $base_fee_result ? floatval($base_fee_result->fee) : 0;
            
            $fee_per_month = $base_monthly_fee > 0 ? $base_monthly_fee : $fee_item['amount'] / $fee_month;
            
            for ($i = $fee_month; $i >= 1; $i--) {
                $wpdb->insert(
                    'ct_student_fee_collection_details',
                    array(
                        'info_id' => $collection_info_id,
                        'sub_head_id' => $fee_item['sub_head_id'],
                        'month' => $i,
                        'fee' => round($fee_per_month, 2),
                        'status' => 1,
                        'reference' => 'Monthly Summary',
                        'date' => $formatted_payment_date,
                        'created_by' => 0, // System/API
                        'created_at' => get_bdt_time_formatted(), // BDT time - Match backend format with hyphens
                    )
                );
            }
        } else {
            // Yearly/exam/other: single row
            $reference = '';
            if ($fee_item['fee_type'] === 'yearly') {
                $reference = 'Yearly Collection';
            } elseif ($fee_item['fee_type'] === 'exam') {
                $reference = 'Exam Collection';
            } else {
                $reference = 'Fee Collection';
            }
            
            $wpdb->insert(
                'ct_student_fee_collection_details',
                array(
                    'info_id' => $collection_info_id,
                    'sub_head_id' => $fee_item['sub_head_id'],
                    'fee' => $fee_item['amount'],
                    'status' => 1,
                    'reference' => $reference,
                    'date' => $formatted_payment_date,
                    'created_by' => 0, // System/API
                    'created_at' => get_bdt_time_formatted(), // BDT time - Match backend format with hyphens
                )
            );
        }
    }
    
    // Process monthly/yearly/exam fee summaries based on fee breakdown
    error_log('PayStation: Starting to process fee summaries. Total items: ' . count($fee_data['fee_breakdown']));
    
    foreach ($fee_data['fee_breakdown'] as $index => $fee_item) {
        $sub_head_id = $fee_item['sub_head_id'];
        $amount = $fee_item['amount'];
        $fee_type = $fee_item['fee_type'];
        
        error_log('PayStation: Processing fee item #' . ($index + 1) . ' - type: ' . $fee_type . ', sub_head_id: ' . $sub_head_id . ', amount: ' . $amount . ', sub_head_name: ' . ($fee_item['sub_head_name'] ?? 'N/A'));
        
        if ($fee_type === 'monthly') {
            // For monthly fees, the amount represents the sum of fees for months from fee_month down to 1
            // We need to get the base monthly fee to insert entries for each month
            // Get base fee for this sub_head
            $base_fee_query = "SELECT fee FROM ct_student_fee_list 
                             WHERE sub_head_id = %d AND class_id = %d AND year = %s";
            $base_fee_params = array($sub_head_id, $class_id, $year);
            if ($group_id) {
                $base_fee_query .= " AND group_id = %d";
                $base_fee_params[] = $group_id;
            }
            $base_fee_query .= " ORDER BY id DESC LIMIT 1";
            $base_fee_result = $wpdb->get_row($wpdb->prepare($base_fee_query, $base_fee_params));
            $base_monthly_fee = $base_fee_result ? floatval($base_fee_result->fee) : 0;
            
            // If we have a base fee, use it; otherwise distribute the total amount
            if ($base_monthly_fee > 0) {
                $fee_per_month = $base_monthly_fee;
            } else {
                // Distribute total amount evenly across months
                $fee_per_month = $amount / $fee_month;
            }
            
            // Insert one entry per month from fee_month down to 1 (with duplicate check)
            for ($i = $fee_month; $i >= 1; $i--) {
                // Check if monthly summary already exists for this student/sub_head/year/month
                $existing_monthly = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM ct_student_monthly_fee_summary 
                     WHERE student_id = %d AND sub_head_id = %d AND year = %s AND month = %d AND class_id = %d",
                    $student_id, $sub_head_id, $year, $i, $class_id
                ));
                
                if ($existing_monthly) {
                    error_log('PayStation: Monthly fee summary already exists for month ' . $i . ' - ID: ' . $existing_monthly . ', skipping insert.');
                    continue;
                }
                
                $month_name_for_note = isset($month_names[$i]) ? $month_names[$i] : $i;
                $insert_result = $wpdb->insert(
                    'ct_student_monthly_fee_summary',
                    array(
                        'student_id' => $student_id,
                        'sub_head_id' => $sub_head_id,
                        'class_id' => $class_id,
                        'section' => $section,
                        'group_id' => $group_id,
                        'year' => $year,
                        'month' => $i,
                        'fee' => round($fee_per_month, 2),
                        'info_id' => $collection_info_id,
                        'status' => 1,
                        'notes' => $month_name_for_note, // Match backend format
                        'date' => $formatted_payment_date,
                        'created_by' => 0, // System/API
                        'created_at' => get_bdt_time_formatted(), // BDT time - Match backend format with hyphens
                    )
                );
                
                if ($insert_result === false) {
                    error_log('PayStation: Failed to insert monthly fee summary for month ' . $i . ' - Error: ' . $wpdb->last_error);
                    error_log('PayStation: Last query: ' . $wpdb->last_query);
                }
            }
        } elseif ($fee_type === 'yearly') {
            error_log('PayStation: ===== PROCESSING YEARLY FEE =====');
            error_log('PayStation: sub_head_id: ' . $sub_head_id . ', amount: ' . $amount . ', student_id: ' . $student_id);
            error_log('PayStation: class_id: ' . $class_id . ', section: ' . ($section ?? 'NULL') . ', group_id: ' . ($group_id ?? 'NULL') . ', year: ' . $year);
            
            // Check if yearly fee already exists (like backend does)
            // Build query similar to backend - handle NULL values properly
            $yearly_fee_query = "SELECT fee FROM ct_student_yearly_fee_summary 
                               WHERE sub_head_id = %d AND class_id = %d AND year = %s 
                               AND student_id = %d";
            $yearly_fee_params = array($sub_head_id, $class_id, $year, $student_id);
            
            if (isset($section) && !empty($section)) {
                $yearly_fee_query .= " AND section = %d";
                $yearly_fee_params[] = $section;
            }
            
            if (isset($group_id) && !empty($group_id)) {
                $yearly_fee_query .= " AND group_id = %d";
                $yearly_fee_params[] = $group_id;
            }
            
            $prepared_query = $wpdb->prepare($yearly_fee_query, $yearly_fee_params);
            error_log('PayStation: Checking existing yearly fee with query: ' . $prepared_query);
            $existing_yearly_fee = $wpdb->get_var($prepared_query);
            error_log('PayStation: Existing yearly fee result: ' . ($existing_yearly_fee !== null ? $existing_yearly_fee : 'NONE'));
            
            // Only insert if it doesn't already exist
            if (!$existing_yearly_fee) {
                error_log('PayStation: Attempting to insert yearly fee summary...');
                
                $insert_data = array(
                    'student_id' => $student_id,
                    'sub_head_id' => $sub_head_id,
                    'class_id' => $class_id,
                    'section' => $section ? $section : null,
                    'group_id' => $group_id ? $group_id : null,
                    'year' => $year,
                    'fee' => $amount,
                    'info_id' => $collection_info_id,
                    'status' => 1,
                    'notes' => 'Yearly Summary', // Match backend format
                    'date' => $formatted_payment_date,
                    'created_by' => 0, // System/API
                    'created_at' => get_bdt_time_formatted(), // BDT time - Match backend format with hyphens
                );
                
                error_log('PayStation: Insert data: ' . json_encode($insert_data));
                
                $yearly_insert_result = $wpdb->insert(
                    'ct_student_yearly_fee_summary',
                    $insert_data
                );
                
                if ($yearly_insert_result === false) {
                    error_log('PayStation: ❌ FAILED to insert yearly fee summary');
                    error_log('PayStation: Database error: ' . $wpdb->last_error);
                    error_log('PayStation: Last query: ' . $wpdb->last_query);
                    error_log('PayStation: Yearly fee data - student_id: ' . $student_id . ', sub_head_id: ' . $sub_head_id . ', amount: ' . $amount);
                } else {
                    $yearly_fee_id = $wpdb->insert_id;
                    error_log('PayStation: ✅ Yearly fee summary inserted successfully - ID: ' . $yearly_fee_id);
                }
            } else {
                error_log('PayStation: ⚠️ Yearly fee already exists for student_id: ' . $student_id . ', sub_head_id: ' . $sub_head_id . ', year: ' . $year);
            }
            error_log('PayStation: ===== END YEARLY FEE PROCESSING =====');
        } elseif ($fee_type === 'exam') {
            // Get active exam for the class
            $active_exam = $wpdb->get_row($wpdb->prepare(
                "SELECT examid FROM ct_exam WHERE active_for_collection = 1 AND examClass = %d LIMIT 1",
                $class_id
            ));
            
            if ($active_exam) {
                // Check if exam fee summary already exists (duplicate check)
                $existing_exam = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM ct_student_exam_fee_summary 
                     WHERE student_id = %d AND sub_head_id = %d AND exam_id = %d AND year = %s AND class_id = %d",
                    $student_id, $sub_head_id, $active_exam->examid, $year, $class_id
                ));
                
                if ($existing_exam) {
                    error_log('PayStation: ⚠️ Exam fee summary already exists for student_id=' . $student_id . ', sub_head_id=' . $sub_head_id . ', exam_id=' . $active_exam->examid . ' - ID: ' . $existing_exam . ', skipping insert.');
                } else {
                    $exam_insert_result = $wpdb->insert(
                        'ct_student_exam_fee_summary',
                        array(
                            'student_id' => $student_id,
                            'sub_head_id' => $sub_head_id,
                            'exam_id' => $active_exam->examid,
                            'class_id' => $class_id,
                            'section' => $section,
                            'group_id' => $group_id,
                            'year' => $year,
                            'fee' => $amount,
                            'status' => 1,
                            'notes' => 'Exam Fee Collection', // Match backend format
                            'date' => $formatted_payment_date,
                            'created_by' => 0, // System/API
                            'created_at' => get_bdt_time_formatted(), // BDT time - Match backend format with hyphens
                        )
                    );
                    
                    if ($exam_insert_result === false) {
                        error_log('PayStation: ❌ FAILED to insert exam fee summary for student_id=' . $student_id . ', sub_head_id=' . $sub_head_id . ', class_id=' . $class_id . ' - Error: ' . $wpdb->last_error);
                    } else {
                        error_log('PayStation: ✅ Exam fee summary inserted successfully - ID: ' . $wpdb->insert_id . ' for student_id=' . $student_id . ', exam_id=' . $active_exam->examid);
                    }
                }
            } else {
                error_log('PayStation: ❌ Cannot process exam fee - No active exam found for class_id=' . $class_id . ', student_id=' . $student_id . ', sub_head_id=' . $sub_head_id);
            }
        }
    }
    
    // Create ledger entry (if saveLeadger function exists)
    // Match backend format: saveLeadger($sub_head_id, $credit, $debit, $reference, $monthly_fee_id, $yearly_fee_id, $exam_fee_id, $date, $info_id)
    // Use PayStation sub_head_id instead of cash for online payments
    if (function_exists('saveLeadger')) {
        saveLeadger(
            $ledger_sub_head_id, // sub_head_id - PayStation/Online Payment (not cash)
            $fee_data['total_amount'], // credit
            0, // debit
            'Collection Reference ID-' . $collection_info_id . ' (PayStation Online Payment)', // reference - match backend format with payment method
            null, // monthly_fee_id
            null, // yearly_fee_id
            null, // exam_fee_id
            $formatted_payment_date, // date
            $collection_info_id // info_id
        );
    }
    
    // Update PayStation transaction status with BDT time
    paystation_update_transaction($payment_id, array(
        'status' => 'paid',
        'payment_date' => get_bdt_time(), // Use BDT time for payment date
        'invoice_number' => $invoice_number,
        'transaction_id' => $paystation_txn_id,
    ));
    
    return array(
        'success' => true,
        'payment_id' => $payment_id,
        'invoice_number' => $invoice_number,
        'collection_info_id' => $collection_info_id,
        'transaction_id' => $paystation_txn_id,
    );
}

/**
 * Calculate fee for PayStation payment using class, section, year, month, and roll
 */
function paystation_calculate_fee($class_id, $section, $group_id, $year, $month, $roll) {
    global $wpdb;
    
    // Use current month/year if not provided
    if (!$month) {
        $month = intval(date('n'));
    }
    if (!$year) {
        $year = date('Y');
    }
    
    // Find student by class, section, year, and roll
    $query = "SELECT s.*, si.infoClass, si.infoRoll, si.infoSection, si.infoGroup, si.infoYear,
         swf.transport_fee_id, swf.transport_type, swf.transport_required
         FROM ct_student s
         INNER JOIN ct_studentinfo si ON s.studentid = si.infoStdid
         LEFT JOIN ct_student_wise_fee swf ON s.studentid = swf.student_id 
             AND swf.class_id = si.infoClass 
             AND swf.year = si.infoYear 
             AND swf.fee_type = 3
         WHERE si.infoClass = %d
         AND si.infoYear = %s
         AND si.infoRoll = %d
         AND s.stdStatus = 1";
    
    $params = array($class_id, $year, $roll);
    
    if ($section) {
        $query .= " AND si.infoSection = %d";
        $params[] = $section;
    }
    
    if ($group_id) {
        $query .= " AND si.infoGroup = %d";
        $params[] = $group_id;
    }
    
    $query .= " LIMIT 1";
    
    $student = $wpdb->get_row($wpdb->prepare($query, $params));
    
    if (!$student) {
        return new WP_Error('student_not_found', 'Student not found with the provided information');
    }
    
    // Extract student info
    $student_id = $student->studentid;
    $class_id = $student->infoClass;
    $section = $student->infoSection ?: null;
    $group_id = $student->infoGroup ?: null;
    $roll = $student->infoRoll;
    
    // Get global variables
    global $admissionFeeSubHeadId, $admissionFormSubHeadId, $monthlyFeeSubHeadId;
    global $examFeeSubHeadId, $transportFeeSubHeadId, $ictFeeSubHeadId;
    global $registrationFeeSubHeadId, $coachingFeeSubHeadId;
    
    // Get active fee sub-heads
    $sub_heads = $wpdb->get_results(
        "SELECT * FROM ct_sub_head 
        WHERE active_for_collection = 1 
        AND relation_to = 1 
        AND isHidden IS NULL 
        ORDER BY sort_order ASC, sub_head_name ASC"
    );
    
    $fee_breakdown = array();
    $sub_total = 0;
    $fee_month = $month;
    
    // Pre-fetch already-paid amounts from collection_details as safety net
    // (for cases when summary table wasn't updated but collection_details has the record)
    $monthlyPaidInCollection = array();
    $yearlyPaidInCollection = array();
    $examPaidInCollection = array();
    
    // Yearly: sub_head_ids paid in collection
    $yearlyPaidQuery = "SELECT DISTINCT cd.sub_head_id FROM ct_student_fee_collection_details cd
        INNER JOIN ct_student_fee_collection_info ci ON ci.id = cd.info_id
        INNER JOIN ct_sub_head sh ON sh.id = cd.sub_head_id
        WHERE ci.class_id = %d AND ci.year = %s
        AND ci.student_id = %d AND sh.type = 2";
    $yearlyPaidParams = array($class_id, $year, $student_id);
    if ($section) {
        $yearlyPaidQuery .= " AND ci.section = %d";
        $yearlyPaidParams[] = $section;
    }
    if ($group_id) {
        $yearlyPaidQuery .= " AND ci.group_id = %d";
        $yearlyPaidParams[] = $group_id;
    }
    $yearlyPaidInCollection = $wpdb->get_col($wpdb->prepare($yearlyPaidQuery, $yearlyPaidParams));
    if (!$yearlyPaidInCollection) $yearlyPaidInCollection = array();
    
    // Exam: sub_head_ids paid in collection
    $examPaidQuery = "SELECT DISTINCT cd.sub_head_id FROM ct_student_fee_collection_details cd
        INNER JOIN ct_student_fee_collection_info ci ON ci.id = cd.info_id
        INNER JOIN ct_sub_head sh ON sh.id = cd.sub_head_id
        WHERE ci.class_id = %d AND ci.year = %s
        AND ci.student_id = %d AND sh.type = 3";
    $examPaidParams = array($class_id, $year, $student_id);
    if ($section) {
        $examPaidQuery .= " AND ci.section = %d";
        $examPaidParams[] = $section;
    }
    if ($group_id) {
        $examPaidQuery .= " AND ci.group_id = %d";
        $examPaidParams[] = $group_id;
    }
    $examPaidInCollection = $wpdb->get_col($wpdb->prepare($examPaidQuery, $examPaidParams));
    if (!$examPaidInCollection) $examPaidInCollection = array();
    
    // Monthly: sub_head_id+month combinations paid in collection (using cd.month column)
    $monthlyPaidQuery = "SELECT cd.sub_head_id, cd.month FROM ct_student_fee_collection_details cd
        INNER JOIN ct_student_fee_collection_info ci ON ci.id = cd.info_id
        INNER JOIN ct_sub_head sh ON sh.id = cd.sub_head_id
        WHERE ci.class_id = %d AND ci.year = %s
        AND ci.student_id = %d AND sh.type = 1 AND cd.fee > 0 AND cd.month IS NOT NULL";
    $monthlyPaidParams = array($class_id, $year, $student_id);
    if ($section) {
        $monthlyPaidQuery .= " AND ci.section = %d";
        $monthlyPaidParams[] = $section;
    }
    if ($group_id) {
        $monthlyPaidQuery .= " AND ci.group_id = %d";
        $monthlyPaidParams[] = $group_id;
    }
    $monthlyPaidRows = $wpdb->get_results($wpdb->prepare($monthlyPaidQuery, $monthlyPaidParams));
    if ($monthlyPaidRows) {
        foreach ($monthlyPaidRows as $row) {
            if ($row->month) {
                $monthlyPaidInCollection[$row->sub_head_id . '|' . $row->month] = true;
            }
        }
    }
    
    foreach ($sub_heads as $sub_head) {
        $sub_head_id = $sub_head->id;
        $sub_head_type = $sub_head->type;
        
        // Get base fee from fee list
        $fee_query = "SELECT fee FROM ct_student_fee_list 
                     WHERE sub_head_id = %d AND class_id = %d AND year = %s";
        $fee_params = array($sub_head_id, $class_id, $year);
        
        if ($group_id) {
            $fee_query .= " AND group_id = %d";
            $fee_params[] = $group_id;
        }
        
        $fee_query .= " ORDER BY id DESC LIMIT 1";
        $fee_result = $wpdb->get_row($wpdb->prepare($fee_query, $fee_params));
        $base_fee = $fee_result ? floatval($fee_result->fee) : 0;
        
        $amount = 0;
        
        if ($sub_head_type == 1) {
            // Monthly fee
            $sum_of_fees = 0;
            
            for ($i = $fee_month; $i >= 1; $i--) {
                $paid_query = "SELECT fee FROM ct_student_monthly_fee_summary 
                              WHERE sub_head_id = %d AND class_id = %d AND year = %s 
                              AND month = %d AND student_id = %d";
                $paid_params = array($sub_head_id, $class_id, $year, $i, $student_id);
                
                if ($section) {
                    $paid_query .= " AND section = %d";
                    $paid_params[] = $section;
                }
                if ($group_id) {
                    $paid_query .= " AND group_id = %d";
                    $paid_params[] = $group_id;
                }
                
                $paid_check = $wpdb->get_var($wpdb->prepare($paid_query, $paid_params));
                $monthlyCollectionKey = $sub_head_id . '|' . $i;
                
                if (!$paid_check && !isset($monthlyPaidInCollection[$monthlyCollectionKey])) {
                    $fee_amount = $base_fee;
                    
                    if ($sub_head_id == $monthlyFeeSubHeadId) {
                        if ($student->facilities == 'Full free' || $student->facilities == 'Scholarship') {
                            $fee_amount = 0;
                        } elseif ($student->facilities == 'Half free') {
                            $fee_amount = $fee_amount / 2;
                        } else {
                            $student_fee = $wpdb->get_var($wpdb->prepare(
                                "SELECT monthly_fee FROM ct_student WHERE studentid = %d",
                                $student_id
                            ));
                            if ($student_fee > 0) {
                                $fee_amount = floatval($student_fee);
                            }
                        }
                    } elseif ($sub_head_id == $transportFeeSubHeadId) {
                        if ($student->transport_required == 1) {
                            $transport_fee_id = $student->transport_fee_id;
                            $transport_fee = $wpdb->get_var($wpdb->prepare(
                                "SELECT amount FROM ct_transport_fee_list WHERE id = %d",
                                $transport_fee_id
                            ));
                            if ($transport_fee) {
                                $fee_amount = floatval($transport_fee);
                                if ($student->transport_type == 1) {
                                    $fee_amount = $fee_amount / 2;
                                }
                            } else {
                                $fee_amount = 0;
                            }
                        } else {
                            $fee_amount = 0;
                        }
                    } elseif ($sub_head_id == $coachingFeeSubHeadId) {
                        $coaching_query = "SELECT amount FROM ct_student_wise_fee 
                                          WHERE fee_type = 1 AND student_id = %d 
                                          AND class_id = %d AND year = %s";
                        $coaching_params = array($student_id, $class_id, $year);
                        if ($section) {
                            $coaching_query .= " AND section = %d";
                            $coaching_params[] = $section;
                        }
                        if ($group_id) {
                            $coaching_query .= " AND group_id = %d";
                            $coaching_params[] = $group_id;
                        }
                        $coaching_fee = $wpdb->get_var($wpdb->prepare($coaching_query, $coaching_params));
                        if ($coaching_fee && $coaching_fee > 0) {
                            $fee_amount = floatval($coaching_fee);
                        } else {
                            $fee_amount = 0;
                        }
                    }
                    
                    $sum_of_fees += $fee_amount;
                }
            }
            
            $amount = $sum_of_fees;
            
        } elseif ($sub_head_type == 2) {
            // Yearly fee
            $paid_query = "SELECT fee FROM ct_student_yearly_fee_summary 
                          WHERE sub_head_id = %d AND class_id = %d AND year = %s 
                          AND student_id = %d";
            $paid_params = array($sub_head_id, $class_id, $year, $student_id);
            
            if ($section) {
                $paid_query .= " AND section = %d";
                $paid_params[] = $section;
            }
            if ($group_id) {
                $paid_query .= " AND group_id = %d";
                $paid_params[] = $group_id;
            }
            
            $paid_check = $wpdb->get_var($wpdb->prepare($paid_query, $paid_params));
            
            if (!$paid_check && !in_array($sub_head_id, $yearlyPaidInCollection)) {
                $amount = $base_fee;
                
                if ($sub_head_id == $admissionFeeSubHeadId) {
                    if ($student->admission_type == 1) {
                        if ($student->facilities == 'Half free') {
                            $amount = $amount / 2;
                        }
                    } else {
                        $promoted_fee = $wpdb->get_var($wpdb->prepare(
                            "SELECT amount FROM ct_admission_fee_promoted WHERE class = %d",
                            $class_id
                        ));
                        if ($promoted_fee) {
                            $amount = floatval($promoted_fee);
                            if ($student->facilities == 'Half free') {
                                $amount = $amount / 2;
                            }
                        } else {
                            $amount = 0;
                        }
                    }
                } elseif ($sub_head_id == $admissionFormSubHeadId) {
                    if ($student->facilities == 'Half free') {
                        $amount = $amount / 2;
                    }
                } elseif ($sub_head_id == $registrationFeeSubHeadId) {
                    $reg_query = "SELECT amount FROM ct_student_wise_fee 
                                 WHERE fee_type = 2 AND student_id = %d 
                                 AND class_id = %d AND year = %s";
                    $reg_params = array($student_id, $class_id, $year);
                    if ($section) {
                        $reg_query .= " AND section = %d";
                        $reg_params[] = $section;
                    }
                    if ($group_id) {
                        $reg_query .= " AND group_id = %d";
                        $reg_params[] = $group_id;
                    }
                    $reg_fee = $wpdb->get_var($wpdb->prepare($reg_query, $reg_params));
                    if ($reg_fee && $reg_fee > 0) {
                        $amount = floatval($reg_fee);
                    }
                }
            }
            
        } elseif ($sub_head_type == 3) {
            // Exam fee
            $active_exam = $wpdb->get_row($wpdb->prepare(
                "SELECT examid FROM ct_exam 
                WHERE active_for_collection = 1 AND examClass = %d LIMIT 1",
                $class_id
            ));
            
            if ($active_exam) {
                $exam_id = $active_exam->examid;
                $paid_query = "SELECT fee FROM ct_student_exam_fee_summary 
                              WHERE sub_head_id = %d AND class_id = %d AND exam_id = %d 
                              AND year = %s AND student_id = %d";
                $paid_params = array($sub_head_id, $class_id, $exam_id, $year, $student_id);
                
                if ($section) {
                    $paid_query .= " AND section = %d";
                    $paid_params[] = $section;
                }
                if ($group_id) {
                    $paid_query .= " AND group_id = %d";
                    $paid_params[] = $group_id;
                }
                
                $paid_check = $wpdb->get_var($wpdb->prepare($paid_query, $paid_params));
                
                if (!$paid_check && !in_array($sub_head_id, $examPaidInCollection)) {
                    $amount = $base_fee;
                }
            }
        } else {
            $amount = $base_fee;
        }
        
        if ($amount > 0) {
            $fee_breakdown[] = array(
                'sub_head_id' => $sub_head_id,
                'sub_head_name' => $sub_head->sub_head_name,
                'fee_type' => $sub_head_type == 1 ? 'monthly' : ($sub_head_type == 2 ? 'yearly' : ($sub_head_type == 3 ? 'exam' : 'other')),
                'amount' => round($amount, 2)
            );
            $sub_total += $amount;
        }
    }
    
    $remission = 0;
    $total_amount = $sub_total - $remission;
    
    // Get class and section names
    $class_name = $wpdb->get_var($wpdb->prepare(
        "SELECT className FROM ct_class WHERE classid = %d",
        $class_id
    ));
    
    $section_name = null;
    if ($section) {
        $section_name = $wpdb->get_var($wpdb->prepare(
            "SELECT sectionName FROM ct_section WHERE sectionid = %d",
            $section
        ));
    }
    
    $group_name = null;
    if ($group_id) {
        $group_name = $wpdb->get_var($wpdb->prepare(
            "SELECT groupName FROM ct_group WHERE groupId = %d",
            $group_id
        ));
    }
    
    return array(
        'student_id' => $student_id,
        'student_name' => $student->stdName,
        'stdUniqueID' => $student->stdUniqueID ?? '',
        'student_roll' => $roll,
        'class_id' => $class_id,
        'class_name' => $class_name,
        'section' => $section,
        'section_name' => $section_name,
        'group_id' => $group_id,
        'group_name' => $group_name,
        'year' => $year,
        'month' => $fee_month,
        'fee_breakdown' => $fee_breakdown,
        'sub_total' => round($sub_total, 2),
        'remission' => round($remission, 2),
        'total_amount' => round($total_amount, 2),
    );
}

