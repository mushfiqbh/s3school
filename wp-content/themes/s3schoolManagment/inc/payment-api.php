<?php
/**
 * Payment API for Third-Party Integration
 * Handles fee calculation and payment confirmation via REST API
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add CORS headers to API responses
 */
function barnomala_add_cors_headers($served, $result, $request, $server) {
    // Only process if it's our API endpoint and result is a response object
    if (strpos($request->get_route(), '/barnomala/v1/') !== false && is_a($result, 'WP_REST_Response')) {
        // Allow requests from any origin (for production, you may want to restrict this)
        $result->header('Access-Control-Allow-Origin', '*');
        $result->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $result->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, api_key, api_secret');
        $result->header('Access-Control-Max-Age', '86400');
    }
    return $served;
}
add_filter('rest_pre_serve_request', 'barnomala_add_cors_headers', 0, 4);

/**
 * Handle OPTIONS preflight requests
 */
function barnomala_handle_options_request() {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS' && strpos($_SERVER['REQUEST_URI'], '/barnomala/v1/') !== false) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, api_key, api_secret');
        header('Access-Control-Max-Age: 86400');
        status_header(200);
        exit();
    }
}
add_action('init', 'barnomala_handle_options_request', 0);

/**
 * Initialize Payment API
 */
function barnomala_payment_api_init() {
    // Register REST API routes
    register_rest_route('barnomala/v1', '/fee/calculate', array(
        'methods' => 'GET',
        'callback' => 'barnomala_calculate_fee',
        'permission_callback' => '__return_true',
    ));

    register_rest_route('barnomala/v1', '/fee/confirm', array(
        'methods' => 'POST',
        'callback' => 'barnomala_confirm_payment',
        'permission_callback' => '__return_true',
    ));

    register_rest_route('barnomala/v1', '/fee/status', array(
        'methods' => 'GET',
        'callback' => 'barnomala_check_payment_status',
        'permission_callback' => '__return_true',
    ));

    register_rest_route('barnomala/v1', '/classes', array(
        'methods' => 'GET',
        'callback' => 'barnomala_get_classes',
        'permission_callback' => '__return_true',
    ));

    register_rest_route('barnomala/v1', '/sections', array(
        'methods' => 'GET',
        'callback' => 'barnomala_get_sections',
        'permission_callback' => '__return_true',
    ));

    register_rest_route('barnomala/v1', '/groups', array(
        'methods' => 'GET',
        'callback' => 'barnomala_get_groups',
        'permission_callback' => '__return_true',
    ));
}
add_action('rest_api_init', 'barnomala_payment_api_init');

/**
 * Authenticate API request
 */
function barnomala_authenticate_api($api_key, $api_secret) {
    // Get stored API credentials from WordPress options
    $stored_key = get_option('barnomala_api_key', '');
    $stored_secret = get_option('barnomala_api_secret', '');
    
    // Default credentials for testing (should be changed in production)
    if (empty($stored_key)) {
        $stored_key = 'barnomala_test_api_key_12345';
    }
    if (empty($stored_secret)) {
        $stored_secret = 'barnomala_test_api_secret_67890';
    }
    
    return ($api_key === $stored_key && $api_secret === $stored_secret);
}

/**
 * Log API request
 */
function barnomala_log_api_request($endpoint, $request_data, $response_data, $status = 'success') {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'barnomala_api_logs';
    
    // Create table if it doesn't exist
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        endpoint varchar(255) NOT NULL,
        request_data text,
        response_data text,
        status varchar(50),
        ip_address varchar(45),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Insert log
    $wpdb->insert(
        $table_name,
        array(
            'endpoint' => $endpoint,
            'request_data' => json_encode($request_data),
            'response_data' => json_encode($response_data),
            'status' => $status,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        )
    );
}

/**
 * Store transaction temporarily
 */
function barnomala_store_transaction($transaction_id, $student_data, $fee_data, $expires_at) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'barnomala_transactions';
    
    // Create table if it doesn't exist
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        transaction_id varchar(255) NOT NULL UNIQUE,
        student_id bigint(20),
        student_data text,
        fee_data text,
        total_amount decimal(10,2),
        expires_at datetime,
        status varchar(50) DEFAULT 'pending',
        payment_id varchar(255),
        payment_date datetime,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY transaction_id (transaction_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Verify table was created
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    if (!$table_exists) {
        // If dbDelta failed, try direct creation
        $wpdb->query($sql);
    }
    
    // Check if transaction already exists
    $existing = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE transaction_id = %s",
        $transaction_id
    ));
    
    if ($existing) {
        // Update existing transaction
        $result = $wpdb->update(
            $table_name,
            array(
                'student_data' => json_encode($student_data),
                'fee_data' => json_encode($fee_data),
                'total_amount' => $fee_data['total_amount'],
                'expires_at' => $expires_at,
            ),
            array('transaction_id' => $transaction_id),
            array('%s', '%s', '%f', '%s'),
            array('%s')
        );
    } else {
        // Insert new transaction
        $result = $wpdb->insert(
            $table_name,
            array(
                'transaction_id' => $transaction_id,
                'student_id' => $student_data['student_id'],
                'student_data' => json_encode($student_data),
                'fee_data' => json_encode($fee_data),
                'total_amount' => $fee_data['total_amount'],
                'expires_at' => $expires_at,
                'status' => 'pending',
            ),
            array('%s', '%d', '%s', '%s', '%f', '%s', '%s')
        );
    }
    
    // Log if insert/update failed
    if ($result === false) {
        error_log('Barnomala API: Failed to store transaction. Error: ' . $wpdb->last_error);
    }
}

/**
 * Get stored transaction
 */
function barnomala_get_transaction($transaction_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'barnomala_transactions';
    
    // Ensure table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    if (!$table_exists) {
        // Table doesn't exist, create it
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            transaction_id varchar(255) NOT NULL UNIQUE,
            student_id bigint(20),
            student_data text,
            fee_data text,
            total_amount decimal(10,2),
            expires_at datetime,
            status varchar(50) DEFAULT 'pending',
            payment_id varchar(255),
            payment_date datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY transaction_id (transaction_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    // Try exact match first
    $transaction = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE transaction_id = %s",
        $transaction_id
    ));
    
    // If not found, try case-insensitive search (in case of case mismatch)
    if (!$transaction) {
        $transaction = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE LOWER(transaction_id) = LOWER(%s)",
            $transaction_id
        ));
    }
    
    if ($transaction) {
        $transaction->student_data = json_decode($transaction->student_data, true);
        $transaction->fee_data = json_decode($transaction->fee_data, true);
    }
    
    return $transaction;
}

/**
 * Calculate Fee Endpoint
 */
function barnomala_calculate_fee($request) {
    global $wpdb;
    
    // Get global variables
    global $admissionFeeSubHeadId, $admissionFormSubHeadId, $monthlyFeeSubHeadId;
    global $examFeeSubHeadId, $transportFeeSubHeadId, $ictFeeSubHeadId;
    global $registrationFeeSubHeadId, $coachingFeeSubHeadId;
    
    // Get request parameters
    $params = $request->get_query_params();
    $api_key = sanitize_text_field($params['api_key'] ?? '');
    $api_secret = sanitize_text_field($params['api_secret'] ?? '');
    $class_id = intval($params['class_id'] ?? 0);
    $year = sanitize_text_field($params['year'] ?? '');
    $roll = sanitize_text_field($params['roll'] ?? '');
    $section = isset($params['section']) ? intval($params['section']) : null;
    $group_id = isset($params['group_id']) ? intval($params['group_id']) : null;
    $month = isset($params['month']) ? intval($params['month']) : null;
    
    // Authenticate
    if (!barnomala_authenticate_api($api_key, $api_secret)) {
        $error_response = array(
            'success' => false,
            'error' => array(
                'code' => 'UNAUTHORIZED',
                'message' => 'Invalid API credentials'
            )
        );
        barnomala_log_api_request('/fee/calculate', $params, $error_response, 'error');
        return new WP_Error('unauthorized', $error_response, array('status' => 401));
    }
    
    // Validate required parameters
    if (empty($class_id) || empty($year) || empty($roll)) {
        $error_response = array(
            'success' => false,
            'error' => array(
                'code' => 'MISSING_PARAMETER',
                'message' => 'Missing required parameters: class_id, year, and roll are required'
            )
        );
        barnomala_log_api_request('/fee/calculate', $params, $error_response, 'error');
        return new WP_Error('missing_parameter', $error_response, array('status' => 400));
    }
    
    // Find student
    $student_query = "SELECT ct_student.*, ct_studentinfo.*, ct_student_wise_fee.transport_fee_id, 
                      ct_student_wise_fee.transport_type, ct_student_wise_fee.transport_required 
                      FROM ct_studentinfo
                      LEFT JOIN ct_student ON ct_student.studentid = ct_studentinfo.infoStdid
                      LEFT JOIN ct_student_wise_fee ON ct_student.studentid = ct_student_wise_fee.student_id 
                          AND ct_student_wise_fee.class_id = %d 
                          AND ct_student_wise_fee.year = %s 
                          AND ct_student_wise_fee.fee_type = 3
                      WHERE ct_studentinfo.infoYear = %s 
                      AND ct_studentinfo.infoRoll = %s 
                      AND ct_studentinfo.infoClass = %d";
    
    $query_params = array($class_id, $year, $year, $roll, $class_id);
    
    if ($section) {
        $student_query .= " AND ct_studentinfo.infoSection = %d";
        $query_params[] = $section;
    }
    
    if ($group_id) {
        $student_query .= " AND ct_studentinfo.infoGroup = %d";
        $query_params[] = $group_id;
    }
    
    $student_query .= " AND ct_student.stdStatus = 1 LIMIT 1";
    
    $student = $wpdb->get_row($wpdb->prepare($student_query, $query_params));
    
    if (!$student) {
        $error_response = array(
            'success' => false,
            'error' => array(
                'code' => 'STUDENT_NOT_FOUND',
                'message' => 'Student not found with the provided class, year, section, and roll number'
            )
        );
        barnomala_log_api_request('/fee/calculate', $params, $error_response, 'error');
        return new WP_Error('student_not_found', $error_response, array('status' => 404));
    }
    
    $student_id = $student->infoStdid;
    
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
    $fee_month = $month ?: date('n'); // Current month if not specified
    
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
                // Check if already paid
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
                
                if (!$paid_check) {
                    // Not paid, calculate fee
                    $fee_amount = $base_fee;
                    
                    // Handle special cases
                    if ($sub_head_id == $monthlyFeeSubHeadId) {
                        if ($student->facilities == 'Full free' || $student->facilities == 'Scholarship') {
                            $fee_amount = 0;
                        } elseif ($student->facilities == 'Half free') {
                            $fee_amount = $fee_amount / 2;
                        } else {
                            // Check student-wise monthly fee
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
                                    $fee_amount = $fee_amount / 2; // One way
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
            
            if (!$paid_check) {
                $amount = $base_fee;
                
                // Handle special cases
                if ($sub_head_id == $admissionFeeSubHeadId) {
                    if ($student->admission_type == 1) {
                        // New admitted
                        if ($student->facilities == 'Half free') {
                            $amount = $amount / 2;
                        }
                    } else {
                        // Promoted
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
            $active_exam = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT examid FROM ct_exam 
                    WHERE active_for_collection = 1 AND examClass = %d LIMIT 1",
                    $class_id
                )
            );
            
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
                
                if (!$paid_check) {
                    $amount = $base_fee;
                }
            }
        } else {
            // Other types
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
    
    $remission = 0; // Can be calculated if needed
    $total_amount = $sub_total - $remission;
    
    // Generate transaction ID
    $transaction_id = 'TXN-' . date('Ymd') . '-' . wp_generate_password(6, false);
    
    // Set expiration (24 hours from now)
    $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Store transaction
    $student_data = array(
        'student_id' => $student_id,
        'student_name' => $student->stdName,
        'student_roll' => $student->infoRoll,
        'class_id' => $class_id,
        'section' => $section,
        'group_id' => $group_id,
        'year' => $year,
    );
    
    $fee_data = array(
        'fee_breakdown' => $fee_breakdown,
        'sub_total' => round($sub_total, 2),
        'remission' => round($remission, 2),
        'total_amount' => round($total_amount, 2),
        'month' => $fee_month,
    );
    
    // Store transaction and verify it was saved
    barnomala_store_transaction($transaction_id, $student_data, $fee_data, $expires_at);
    
    // Verify transaction was saved
    $saved_transaction = barnomala_get_transaction($transaction_id);
    if (!$saved_transaction) {
        error_log('Barnomala API: Failed to save transaction ' . $transaction_id);
        // Continue anyway - transaction might be saved but query failed
    }
    
    // Prepare response
    $response = array(
        'success' => true,
        'data' => array(
            'student_id' => $student_id,
            'student_name' => $student->stdName,
            'student_roll' => $student->infoRoll,
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
            'currency' => 'BDT',
            'transaction_id' => $transaction_id,
            'expires_at' => $expires_at,
        )
    );
    
    barnomala_log_api_request('/fee/calculate', $params, $response, 'success');
    
    $rest_response = rest_ensure_response($response);
    $rest_response->header('Access-Control-Allow-Origin', '*');
    $rest_response->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    $rest_response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
    
    return $rest_response;
}

/**
 * Confirm Payment Endpoint
 */
function barnomala_confirm_payment($request) {
    global $wpdb;
    
    // Get global variables
    global $admissionFeeSubHeadId, $admissionFormSubHeadId, $monthlyFeeSubHeadId;
    global $examFeeSubHeadId, $transportFeeSubHeadId, $ictFeeSubHeadId;
    global $registrationFeeSubHeadId, $coachingFeeSubHeadId;
    global $cashSubHeadId;
    
    // Get request body - try multiple methods
    $body = $request->get_json_params();
    
    // If JSON params are empty, try get_body_params (for form data)
    if (empty($body)) {
        $body = $request->get_body_params();
    }
    
    // If still empty, try parsing raw body
    if (empty($body)) {
        $raw_body = $request->get_body();
        if (!empty($raw_body)) {
            $body = json_decode($raw_body, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $body = array();
            }
        }
    }
    
    $api_key = sanitize_text_field($body['api_key'] ?? '');
    $api_secret = sanitize_text_field($body['api_secret'] ?? '');
    $transaction_id = sanitize_text_field($body['transaction_id'] ?? '');
    $payment_id = sanitize_text_field($body['payment_id'] ?? '');
    $payment_method = sanitize_text_field($body['payment_method'] ?? 'api');
    $payment_date = sanitize_text_field($body['payment_date'] ?? date('Y-m-d H:i:s'));
    $amount_paid = floatval($body['amount_paid'] ?? 0);
    
    // Debug: Log received body for troubleshooting
    if (empty($transaction_id) || empty($payment_id) || $amount_paid <= 0) {
        error_log('Barnomala API Confirm Payment - Received body: ' . print_r($body, true));
        error_log('Barnomala API Confirm Payment - Raw body: ' . $request->get_body());
    }
    
    // Authenticate
    if (!barnomala_authenticate_api($api_key, $api_secret)) {
        $error_response = array(
            'success' => false,
            'error' => array(
                'code' => 'UNAUTHORIZED',
                'message' => 'Invalid API credentials'
            )
        );
        barnomala_log_api_request('/fee/confirm', $body, $error_response, 'error');
        $rest_response = rest_ensure_response($error_response);
        $rest_response->set_status(401);
        $rest_response->header('Access-Control-Allow-Origin', '*');
        $rest_response->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $rest_response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        return $rest_response;
    }
    
    // Validate required parameters
    $missing_params = array();
    if (empty($transaction_id)) {
        $missing_params[] = 'transaction_id';
    }
    if (empty($payment_id)) {
        $missing_params[] = 'payment_id';
    }
    if ($amount_paid <= 0) {
        $missing_params[] = 'amount_paid';
    }
    
    if (!empty($missing_params)) {
        $error_response = array(
            'success' => false,
            'error' => array(
                'code' => 'MISSING_PARAMETER',
                'message' => 'Missing required parameters: ' . implode(', ', $missing_params),
                'missing_parameters' => $missing_params,
                'debug' => array(
                    'received_body' => $body,
                    'content_type' => $request->get_header('content-type'),
                    'request_method' => $request->get_method()
                )
            )
        );
        barnomala_log_api_request('/fee/confirm', $body, $error_response, 'error');
        $rest_response = rest_ensure_response($error_response);
        $rest_response->set_status(400);
        $rest_response->header('Access-Control-Allow-Origin', '*');
        $rest_response->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $rest_response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        return $rest_response;
    }
    
    // Get transaction
    $transaction = barnomala_get_transaction($transaction_id);
    
    if (!$transaction) {
        // Debug: Check if table exists and log recent transactions
        $table_name = $wpdb->prefix . 'barnomala_transactions';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
        
        // Try to find similar transaction IDs (for debugging)
        $similar_transactions = array();
        if ($table_exists) {
            $recent_transactions = $wpdb->get_results(
                "SELECT transaction_id, created_at, status FROM $table_name ORDER BY created_at DESC LIMIT 5"
            );
            foreach ($recent_transactions as $txn) {
                $similar_transactions[] = $txn->transaction_id;
            }
        }
        
        $error_response = array(
            'success' => false,
            'error' => array(
                'code' => 'INVALID_TRANSACTION',
                'message' => 'Transaction ID not found. Please ensure you use the transaction_id from the calculate fee response.',
                'debug' => array(
                    'transaction_id_provided' => $transaction_id,
                    'table_exists' => $table_exists ? true : false,
                    'recent_transactions' => $similar_transactions
                )
            )
        );
        barnomala_log_api_request('/fee/confirm', $body, $error_response, 'error');
        
        $rest_response = rest_ensure_response($error_response);
        $rest_response->set_status(404);
        $rest_response->header('Access-Control-Allow-Origin', '*');
        $rest_response->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $rest_response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        return $rest_response;
    }
    
    // Check if expired
    if (strtotime($transaction->expires_at) < time()) {
        $error_response = array(
            'success' => false,
            'error' => array(
                'code' => 'TRANSACTION_EXPIRED',
                'message' => 'Transaction has expired'
            )
        );
        barnomala_log_api_request('/fee/confirm', $body, $error_response, 'error');
        $rest_response = rest_ensure_response($error_response);
        $rest_response->set_status(400);
        $rest_response->header('Access-Control-Allow-Origin', '*');
        $rest_response->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $rest_response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        return $rest_response;
    }
    
    // Check if already processed
    if ($transaction->status === 'paid') {
        $error_response = array(
            'success' => false,
            'error' => array(
                'code' => 'PAYMENT_ALREADY_PROCESSED',
                'message' => 'Payment already confirmed for this transaction'
            )
        );
        barnomala_log_api_request('/fee/confirm', $body, $error_response, 'error');
        $rest_response = rest_ensure_response($error_response);
        $rest_response->set_status(400);
        $rest_response->header('Access-Control-Allow-Origin', '*');
        $rest_response->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $rest_response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        return $rest_response;
    }
    
    // Verify amount
    $expected_amount = floatval($transaction->total_amount);
    if (abs($amount_paid - $expected_amount) > 0.01) {
        $error_response = array(
            'success' => false,
            'error' => array(
                'code' => 'AMOUNT_MISMATCH',
                'message' => sprintf('Paid amount (%.2f) does not match expected amount (%.2f)', $amount_paid, $expected_amount)
            )
        );
        barnomala_log_api_request('/fee/confirm', $body, $error_response, 'error');
        $rest_response = rest_ensure_response($error_response);
        $rest_response->set_status(400);
        $rest_response->header('Access-Control-Allow-Origin', '*');
        $rest_response->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $rest_response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        return $rest_response;
    }
    
    $student_data = $transaction->student_data;
    $fee_data = $transaction->fee_data;
    $student_id = $student_data['student_id'];
    $class_id = $student_data['class_id'];
    $section = $student_data['section'];
    $group_id = $student_data['group_id'];
    $year = $student_data['year'];
    $fee_month = $fee_data['month'];
    
    // Get cash sub head ID (assuming it exists, you may need to set this)
    if (!isset($cashSubHeadId)) {
        $cashSubHeadId = $wpdb->get_var("SELECT id FROM ct_sub_head WHERE sub_head_name LIKE '%Cash%' LIMIT 1");
        if (!$cashSubHeadId) {
            $cashSubHeadId = 0; // Default if not found
        }
    }
    
    // Insert into fee collection info
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
            'notes' => 'Payment via API - Payment ID: ' . $payment_id,
            'date' => $payment_date,
            'created_by' => 0, // System/API
            'created_at' => date('Y-m-d H:i:s'),
        )
    );
    
    if (!$info_id) {
        $error_response = array(
            'success' => false,
            'error' => array(
                'code' => 'DATABASE_ERROR',
                'message' => 'Failed to save payment record'
            )
        );
        barnomala_log_api_request('/fee/confirm', $body, $error_response, 'error');
        return new WP_Error('database_error', $error_response, array('status' => 500));
    }
    
    $info_id = $wpdb->insert_id;
    
    // Save fee breakdown details
    foreach ($fee_data['fee_breakdown'] as $fee_item) {
        $wpdb->insert(
            'ct_student_fee_collection_details',
            array(
                'info_id' => $info_id,
                'sub_head_id' => $fee_item['sub_head_id'],
                'fee' => $fee_item['amount'],
                'date' => $payment_date,
            )
        );
        
        // Save to monthly/yearly/exam summaries based on fee type
        $sub_head_info = $wpdb->get_row($wpdb->prepare(
            "SELECT type FROM ct_sub_head WHERE id = %d",
            $fee_item['sub_head_id']
        ));
        
        if ($sub_head_info) {
            if ($sub_head_info->type == 1) {
                // Monthly fee summary
                for ($i = $fee_month; $i >= 1; $i--) {
                    $wpdb->insert(
                        'ct_student_monthly_fee_summary',
                        array(
                            'student_id' => $student_id,
                            'year' => $year,
                            'month' => $i,
                            'class_id' => $class_id,
                            'section' => $section,
                            'group_id' => $group_id,
                            'info_id' => $info_id,
                            'sub_head_id' => $fee_item['sub_head_id'],
                            'fee' => $fee_item['amount'] / $fee_month, // Distribute evenly
                            'status' => 1,
                            'notes' => 'API Payment',
                            'date' => $payment_date,
                            'created_by' => 0,
                            'created_at' => date('Y-m-d H:i:s'),
                        )
                    );
                }
            } elseif ($sub_head_info->type == 2) {
                // Yearly fee summary
                $wpdb->insert(
                    'ct_student_yearly_fee_summary',
                    array(
                        'student_id' => $student_id,
                        'year' => $year,
                        'class_id' => $class_id,
                        'section' => $section,
                        'group_id' => $group_id,
                        'info_id' => $info_id,
                        'sub_head_id' => $fee_item['sub_head_id'],
                        'fee' => $fee_item['amount'],
                        'status' => 1,
                        'notes' => 'API Payment',
                        'date' => $payment_date,
                        'created_by' => 0,
                        'created_at' => date('Y-m-d H:i:s'),
                    )
                );
            } elseif ($sub_head_info->type == 3) {
                // Exam fee summary
                $active_exam = $wpdb->get_row($wpdb->prepare(
                    "SELECT examid FROM ct_exam WHERE active_for_collection = 1 AND examClass = %d LIMIT 1",
                    $class_id
                ));
                if ($active_exam) {
                    $wpdb->insert(
                        'ct_student_exam_fee_summary',
                        array(
                            'student_id' => $student_id,
                            'exam_id' => $active_exam->examid,
                            'year' => $year,
                            'class_id' => $class_id,
                            'section' => $section,
                            'group_id' => $group_id,
                            'info_id' => $info_id,
                            'sub_head_id' => $fee_item['sub_head_id'],
                            'fee' => $fee_item['amount'],
                            'status' => 1,
                            'notes' => 'API Payment',
                            'date' => $payment_date,
                            'created_by' => 0,
                            'created_at' => date('Y-m-d H:i:s'),
                        )
                    );
                }
            }
        }
    }
    
    // Save ledger entries (using existing function if available)
    if (function_exists('saveLeadger')) {
        saveLeadger($cashSubHeadId, $fee_data['total_amount'], 0, 'API Payment Reference ID-' . $info_id, null, null, null, $payment_date, $info_id);
        
        if ($fee_data['remission'] > 0) {
            saveLeadger(0, 0, $fee_data['remission'], 'API Payment Reference ID-' . $info_id . '. Remission', null, null, null, $payment_date, $info_id);
        }
    } else {
        // Fallback: direct insert
        $wpdb->insert(
            'ct_ledger',
            array(
                'sub_head_id' => $cashSubHeadId,
                'credit' => $fee_data['total_amount'],
                'debit' => 0,
                'reference' => 'API Payment Reference ID-' . $info_id,
                'monthly_fee_id' => null,
                'yearly_fee_id' => null,
                'exam_fee_id' => null,
                'info_id' => $info_id,
                'date' => $payment_date,
                'created_by' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            )
        );
    }
    
    // Update transaction status
    $table_name = $wpdb->prefix . 'barnomala_transactions';
    $wpdb->update(
        $table_name,
        array(
            'status' => 'paid',
            'payment_id' => $payment_id,
            'payment_date' => $payment_date,
        ),
        array('transaction_id' => $transaction_id)
    );
    
    $response = array(
        'success' => true,
        'data' => array(
            'collection_info_id' => $info_id,
            'transaction_id' => $transaction_id,
            'payment_id' => $payment_id,
            'status' => 'paid',
            'paid_at' => $payment_date,
            'message' => 'Payment confirmed and recorded successfully'
        )
    );
    
    barnomala_log_api_request('/fee/confirm', $body, $response, 'success');
    
    $rest_response = rest_ensure_response($response);
    $rest_response->header('Access-Control-Allow-Origin', '*');
    $rest_response->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    $rest_response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
    
    return $rest_response;
}

/**
 * Check Payment Status Endpoint
 */
function barnomala_check_payment_status($request) {
    $params = $request->get_query_params();
    $api_key = sanitize_text_field($params['api_key'] ?? '');
    $api_secret = sanitize_text_field($params['api_secret'] ?? '');
    $transaction_id = sanitize_text_field($params['transaction_id'] ?? '');
    
    // Authenticate
    if (!barnomala_authenticate_api($api_key, $api_secret)) {
        $error_response = array(
            'success' => false,
            'error' => array(
                'code' => 'UNAUTHORIZED',
                'message' => 'Invalid API credentials'
            )
        );
        return new WP_Error('unauthorized', $error_response, array('status' => 401));
    }
    
    if (empty($transaction_id)) {
        $error_response = array(
            'success' => false,
            'error' => array(
                'code' => 'MISSING_PARAMETER',
                'message' => 'transaction_id is required'
            )
        );
        return new WP_Error('missing_parameter', $error_response, array('status' => 400));
    }
    
    $transaction = barnomala_get_transaction($transaction_id);
    
    if (!$transaction) {
        $error_response = array(
            'success' => false,
            'error' => array(
                'code' => 'INVALID_TRANSACTION',
                'message' => 'Transaction ID not found'
            )
        );
        return new WP_Error('invalid_transaction', $error_response, array('status' => 404));
    }
    
    $response = array(
        'success' => true,
        'data' => array(
            'transaction_id' => $transaction->transaction_id,
            'status' => $transaction->status,
            'total_amount' => floatval($transaction->total_amount),
            'payment_id' => $transaction->payment_id,
            'payment_date' => $transaction->payment_date,
            'expires_at' => $transaction->expires_at,
            'student_data' => $transaction->student_data,
        )
    );
    
    $rest_response = rest_ensure_response($response);
    $rest_response->header('Access-Control-Allow-Origin', '*');
    $rest_response->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    $rest_response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
    
    return $rest_response;
}

/**
 * Get Classes Endpoint
 */
function barnomala_get_classes($request) {
    global $wpdb;
    
    $params = $request->get_query_params();
    $api_key = sanitize_text_field($params['api_key'] ?? '');
    $api_secret = sanitize_text_field($params['api_secret'] ?? '');
    
    // Authenticate
    if (!barnomala_authenticate_api($api_key, $api_secret)) {
        $error_response = array(
            'success' => false,
            'error' => array(
                'code' => 'UNAUTHORIZED',
                'message' => 'Invalid API credentials'
            )
        );
        return new WP_Error('unauthorized', $error_response, array('status' => 401));
    }
    
    // Get all classes, ordered by classOrder then className
    // First check if table exists
    $table_name = 'ct_class';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    
    if (!$table_exists) {
        $error_response = array(
            'success' => false,
            'error' => array(
                'code' => 'TABLE_NOT_FOUND',
                'message' => 'Classes table (ct_class) does not exist in database'
            )
        );
        $rest_response = rest_ensure_response($error_response);
        $rest_response->set_status(500);
        $rest_response->header('Access-Control-Allow-Origin', '*');
        $rest_response->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $rest_response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        return $rest_response;
    }
    
    // Try to get classes with classOrder
    $classes = $wpdb->get_results(
        "SELECT classid, className, 
         COALESCE(classOrder, 0) as classOrder,
         COALESCE(havegroup, 0) as havegroup,
         session 
         FROM ct_class 
         ORDER BY COALESCE(classOrder, 999) ASC, className ASC"
    );
    
    // If query failed or no results, try simpler query
    if ($classes === false || empty($classes)) {
        // Check for SQL error
        if ($wpdb->last_error) {
            error_log('Barnomala API Classes Query Error: ' . $wpdb->last_error);
        }
        
        // Try simpler query without classOrder
        $classes = $wpdb->get_results(
            "SELECT classid, className, havegroup, session 
             FROM ct_class 
             ORDER BY className ASC"
        );
    }
    
    // If still no results, try minimal query
    if (empty($classes)) {
        $classes = $wpdb->get_results(
            "SELECT classid, className FROM ct_class ORDER BY className ASC"
        );
    }
    
    $classes_data = array();
    if ($classes && is_array($classes)) {
        foreach ($classes as $class) {
            $classes_data[] = array(
                'class_id' => intval($class->classid ?? 0),
                'class_name' => $class->className ?? '',
                'class_order' => isset($class->classOrder) ? intval($class->classOrder) : 0,
                'has_group' => isset($class->havegroup) ? (intval($class->havegroup) == 1) : false,
                'session' => $class->session ?? null
            );
        }
    }
    
    $response = array(
        'success' => true,
        'data' => $classes_data
    );
    
    // Add debug info if empty (for troubleshooting)
    if (empty($classes_data)) {
        $response['debug'] = array(
            'table_exists' => $table_exists ? true : false,
            'query_result_count' => $classes ? count($classes) : 0,
            'last_error' => $wpdb->last_error ? $wpdb->last_error : null
        );
    }
    
    barnomala_log_api_request('/classes', $params, $response, 'success');
    
    $rest_response = rest_ensure_response($response);
    $rest_response->header('Access-Control-Allow-Origin', '*');
    $rest_response->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    $rest_response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
    
    return $rest_response;
}

/**
 * Get Sections Endpoint
 */
function barnomala_get_sections($request) {
    global $wpdb;
    
    $params = $request->get_query_params();
    $api_key = sanitize_text_field($params['api_key'] ?? '');
    $api_secret = sanitize_text_field($params['api_secret'] ?? '');
    $class_id = isset($params['class_id']) ? intval($params['class_id']) : null;
    
    // Authenticate
    if (!barnomala_authenticate_api($api_key, $api_secret)) {
        $error_response = array(
            'success' => false,
            'error' => array(
                'code' => 'UNAUTHORIZED',
                'message' => 'Invalid API credentials'
            )
        );
        return new WP_Error('unauthorized', $error_response, array('status' => 401));
    }
    
    // Build query
    if ($class_id) {
        // Get sections for specific class
        $sections = $wpdb->get_results($wpdb->prepare(
            "SELECT sectionid, sectionName, forClass 
             FROM ct_section 
             WHERE forClass = %d 
             ORDER BY sectionName ASC",
            $class_id
        ));
    } else {
        // Get all sections
        $sections = $wpdb->get_results(
            "SELECT sectionid, sectionName, forClass 
             FROM ct_section 
             ORDER BY forClass ASC, sectionName ASC"
        );
    }
    
    $sections_data = array();
    foreach ($sections as $section) {
        $sections_data[] = array(
            'section_id' => intval($section->sectionid),
            'section_name' => $section->sectionName,
            'class_id' => intval($section->forClass ?? 0)
        );
    }
    
    $response = array(
        'success' => true,
        'data' => $sections_data
    );
    
    barnomala_log_api_request('/sections', $params, $response, 'success');
    
    $rest_response = rest_ensure_response($response);
    $rest_response->header('Access-Control-Allow-Origin', '*');
    $rest_response->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    $rest_response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
    
    return $rest_response;
}

/**
 * Get Groups Endpoint
 */
function barnomala_get_groups($request) {
    global $wpdb;
    
    $params = $request->get_query_params();
    $api_key = sanitize_text_field($params['api_key'] ?? '');
    $api_secret = sanitize_text_field($params['api_secret'] ?? '');
    $class_id = isset($params['class_id']) ? intval($params['class_id']) : null;
    
    // Authenticate
    if (!barnomala_authenticate_api($api_key, $api_secret)) {
        $error_response = array(
            'success' => false,
            'error' => array(
                'code' => 'UNAUTHORIZED',
                'message' => 'Invalid API credentials'
            )
        );
        return new WP_Error('unauthorized', $error_response, array('status' => 401));
    }
    
    // Build query
    if ($class_id) {
        // Get groups that are actually used by students in this class
        $groups = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT ct_group.groupId, ct_group.groupName 
             FROM ct_group 
             INNER JOIN ct_studentinfo ON ct_studentinfo.infoGroup = ct_group.groupId 
             WHERE ct_studentinfo.infoClass = %d 
             ORDER BY ct_group.groupName ASC",
            $class_id
        ));
    } else {
        // Get all groups
        $groups = $wpdb->get_results(
            "SELECT groupId, groupName 
             FROM ct_group 
             ORDER BY groupName ASC"
        );
    }
    
    $groups_data = array();
    foreach ($groups as $group) {
        $groups_data[] = array(
            'group_id' => intval($group->groupId),
            'group_name' => $group->groupName
        );
    }
    
    $response = array(
        'success' => true,
        'data' => $groups_data
    );
    
    barnomala_log_api_request('/groups', $params, $response, 'success');
    
    $rest_response = rest_ensure_response($response);
    $rest_response->header('Access-Control-Allow-Origin', '*');
    $rest_response->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    $rest_response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
    
    return $rest_response;
}

