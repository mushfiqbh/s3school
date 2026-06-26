<?php

/**
 * PayStation Sync REST API
 *
 * Provides an endpoint to check pending PayStation transaction statuses
 * and update them locally. Designed to be called by a cron job as a service (cronfast.com).
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', 's3s_register_cron_job_routes');

function s3s_register_cron_job_routes()
{
    s3s_register_paystation_sync_route();
    s3s_register_summary_sync_route();
    s3s_register_mismatch_routes();
    s3s_register_payment_transfer_routes();
}

function s3s_register_payment_transfer_routes()
{
    // Payment Transfer - Transfer a PayStation transaction to another student
    register_rest_route('v1', 'payment/transfer', array(
        'methods'  => 'POST',
        'callback' => 's3s_payment_transfer',
        'permission_callback' => '__return_true',
    ));

    // Student Lookup - Get students by class for cascading dropdown
    register_rest_route('v1', 'students/by-class', array(
        'methods'  => 'GET',
        'callback' => 's3s_students_by_class',
        'permission_callback' => '__return_true',
    ));

    // Sections Lookup - Get sections for a class
    register_rest_route('v1', 'sections/by-class', array(
        'methods'  => 'GET',
        'callback' => 's3s_sections_by_class',
        'permission_callback' => '__return_true',
    ));

    // Claim Transfer - Student claims a payment that was recorded under wrong info
    register_rest_route('v1', 'payment/claim-transfer', array(
        'methods'  => 'POST',
        'callback' => 's3s_payment_claim_transfer',
        'permission_callback' => '__return_true',
    ));

    // Transaction Lookup - Lookup a PayStation transaction by payment_id
    register_rest_route('v1', 'payment/lookup', array(
        'methods'  => 'GET',
        'callback' => 's3s_payment_lookup',
        'permission_callback' => '__return_true',
    ));

    // Check Transaction ID - Verify if a transaction_id already exists in the system
    register_rest_route('v1', 'payment/check-transaction-id', array(
        'methods'  => 'GET',
        'callback' => 's3s_payment_check_transaction_id',
        'permission_callback' => '__return_true',
    ));
}

function s3s_register_mismatch_routes()
{
    // Mismatch - Scan a single student (called from admin UI on-demand)
    register_rest_route('v1', 'mismatch/scan-student', array(
        'methods'  => 'POST',
        'callback' => 's3s_mismatch_scan_student',
        'permission_callback' => '__return_true',
    ));

    // Mismatch - Update claim status
    register_rest_route('v1', 'mismatch/update-claim', array(
        'methods'  => 'POST',
        'callback' => 's3s_mismatch_update_claim',
        'permission_callback' => '__return_true',
    ));

    // Mismatch - Scan ALL classes for the current year (called by cron)
    register_rest_route('v1', 'mismatch/scan-all-classes', array(
        'methods'  => 'POST',
        'callback' => 's3s_mismatch_scan_all_classes',
        'permission_callback' => '__return_true',
    ));
}

function s3s_register_paystation_sync_route()
{
    // PayStation - Sync pending transaction statuses (called by cron)
    register_rest_route('v1', 'paystation/sync-status', array(
        'methods'  => 'POST',
        'callback' => 's3s_paystation_sync_pending_status',
        'permission_callback' => '__return_true',
    ));
}

function s3s_register_summary_sync_route()
{
    // Summary Tables - Sync missing fee payments into summary tables (called by cron)
    register_rest_route('v1', 'cron/sync-summary-tables', array(
        'methods'  => 'POST',
        'callback' => 's3s_sync_summary_tables',
        'permission_callback' => '__return_true',
    ));
}

/**
 * PayStation: Sync pending transaction statuses
 *
 * Queries all pending PayStation transactions and checks their status
 * with the PayStation API. Updates local transaction status accordingly.
 * Designed to be called by a cron job via POST /v1/paystation/sync-status
 *
 * @param WP_REST_Request $request
 * @return array Summary of processed transactions
 */
function s3s_paystation_sync_pending_status(WP_REST_Request $request)
{
    global $wpdb;

    // First, fill any missing transaction_ids from PayStation
    $fill_result = s3s_paystation_fill_missing_transaction_ids();

    $table_name = $wpdb->prefix . 'paystation_transactions';

    // Verify the table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    if (!$table_exists) {
        return array(
            'success'   => true,
            'message'   => 'No PayStation transactions table found',
            'processed' => 0,
            'succeeded' => 0,
            'failed'    => 0,
            'errors'    => [],
            'details'   => [],
            'fill_processed' => $fill_result['processed'],
            'fill_updated'   => $fill_result['updated'],
            'fill_errors'    => $fill_result['errors'],
        );
    }

    // Get all pending transactions
    $pending_transactions = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM $table_name WHERE status = %s ORDER BY created_at ASC",
            'pending'
        ),
        ARRAY_A
    );

    if (empty($pending_transactions)) {
        return array(
            'success'   => true,
            'message'   => 'No pending transactions found',
            'processed' => 0,
            'succeeded' => 0,
            'failed'    => 0,
            'errors'    => [],
            'details'   => [],
            'fill_processed' => $fill_result['processed'],
            'fill_updated'   => $fill_result['updated'],
            'fill_errors'    => $fill_result['errors'],
        );
    }

    $processed = 0;
    $succeeded = 0;
    $failed    = 0;
    $errors    = [];
    $details   = [];

    foreach ($pending_transactions as $transaction) {
        $payment_id     = $transaction['payment_id'];
        $invoice_number = $transaction['invoice_number'];

        // Skip transactions without an invoice number
        if (empty($invoice_number)) {
            $errors[] = "Payment ID {$payment_id}: No invoice number assigned";
            $failed++;
            $processed++;
            continue;
        }

        // Call PayStation to check the transaction status
        $status_result = paystation_check_status($invoice_number);

        if (is_wp_error($status_result)) {
            // PayStation API returned an error — the payment might still be pending
            $error_message = $status_result->get_error_message();
            $errors[] = "Payment ID {$payment_id} (Invoice: {$invoice_number}): {$error_message}";

            $details[] = array(
                'payment_id'     => $payment_id,
                'invoice_number' => $invoice_number,
                'action'         => 'still_pending',
                'reason'         => $error_message,
            );

            $processed++;
            continue;
        }

        // Successful response from PayStation
        $response_data = $status_result['data'];

        // Determine the actual payment status from PayStation response
        $paystation_status = isset($response_data['transaction_status'])
            ? strtolower($response_data['transaction_status'])
            : '';

        $paystation_status_code = isset($response_data['status_code'])
            ? $response_data['status_code']
            : '';

        $api_status = isset($response_data['status'])
            ? strtolower($response_data['status'])
            : '';

        // Check if the transaction was paid successfully
        $is_paid = false;

        // PayStation success indicators
        if ($paystation_status === 'success' || $paystation_status === 'completed' || $paystation_status === 'paid') {
            $is_paid = true;
        } elseif (($paystation_status_code === '200' || $paystation_status_code === 200) && $api_status === 'success') {
            $is_paid = true;
        } elseif (isset($response_data['payment_status']) && strtolower($response_data['payment_status']) === 'success') {
            $is_paid = true;
        }

        if ($is_paid) {
            // Payment successful — confirm and process locally
            $transaction_data = array(
                'paystation_txn_id' => $response_data['transaction_id'] ?? $invoice_number,
                'payment_date'      => $response_data['payment_date'] ?? get_bdt_time(),
            );

            $confirm_result = paystation_confirm_payment(
                $payment_id,
                $invoice_number,
                $transaction_data
            );

            if (is_wp_error($confirm_result)) {
                $errors[] = "Payment ID {$payment_id}: Confirmation failed - " . $confirm_result->get_error_message();
                $failed++;
            } else {
                $succeeded++;
                $details[] = array(
                    'payment_id'     => $payment_id,
                    'invoice_number' => $invoice_number,
                    'action'         => 'confirmed',
                    'collection_info_id' => $confirm_result['collection_info_id'] ?? null,
                );
            }
        } elseif ($paystation_status === 'failed' || $api_status === 'failed') {
            // Payment failed at PayStation
            paystation_update_transaction($payment_id, array(
                'status'            => 'failed',
                'paystation_response' => $response_data,
                'payment_date'      => get_bdt_time(),
            ));

            $details[] = array(
                'payment_id'     => $payment_id,
                'invoice_number' => $invoice_number,
                'action'         => 'marked_failed',
            );
            $failed++;
        } else {
            // Transaction still pending at PayStation
            paystation_update_transaction($payment_id, array(
                'paystation_response' => $response_data,
            ));

            $details[] = array(
                'payment_id'     => $payment_id,
                'invoice_number' => $invoice_number,
                'action'         => 'still_pending',
            );
        }

        $processed++;
    }

    return array(
        'success'   => true,
        'message'   => "Fill: {$fill_result['updated']} transaction_id(s) filled. Sync: Processed {$processed} pending transaction(s): {$succeeded} succeeded, {$failed} failed",
        'processed' => $processed,
        'succeeded' => $succeeded,
        'failed'    => $failed,
        'errors'    => $errors,
        'details'   => $details,
        'fill_processed' => $fill_result['processed'],
        'fill_updated'   => $fill_result['updated'],
        'fill_errors'    => $fill_result['errors'],
    );
}

/**
 * Summary Tables Sync: Check if a summary entry already exists
 *
 * @param int $type        Sub-head type (1=monthly, 2=yearly, 3=exam)
 * @param int $info_id     Collection info ID
 * @param int $sub_head_id Sub-head ID
 * @return bool True if summary entry exists
 */
function s3s_sync_check_exists($type, $record)
{
    global $wpdb;

    $student_id  = $record['student_id'];
    $sub_head_id = $record['sub_head_id'];
    $class_id    = $record['class_id'];
    $year        = $record['year'];

    if ($type == 1) {
        $month = (int) $record['fee_month'];
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM ct_student_monthly_fee_summary
             WHERE student_id = %d AND sub_head_id = %d AND class_id = %d AND year = %s AND month = %d",
            $student_id, $sub_head_id, $class_id, $year, $month
        ));
    } elseif ($type == 2) {
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM ct_student_yearly_fee_summary
             WHERE student_id = %d AND sub_head_id = %d AND class_id = %d AND year = %s",
            $student_id, $sub_head_id, $class_id, $year
        ));
    } elseif ($type == 3) {
        $active_exam = $wpdb->get_row($wpdb->prepare(
            "SELECT examid FROM ct_exam WHERE active_for_collection = 1 AND examClass = %d LIMIT 1",
            $class_id
        ));
        if (!$active_exam) {
            return false;
        }
        $exam_id = $active_exam->examid;
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM ct_student_exam_fee_summary
             WHERE student_id = %d AND sub_head_id = %d AND exam_id = %d AND class_id = %d AND year = %s",
            $student_id, $sub_head_id, $exam_id, $class_id, $year
        ));
    } else {
        return true;
    }

    return $count > 0;
}

/**
 * Summary Tables Sync: Get base fee from the fee list
 *
 * @param int    $sub_head_id Sub-head ID
 * @param int    $class_id    Class ID
 * @param string $year        Academic year
 * @param int    $group_id    Group ID (optional)
 * @return float Base fee amount
 */
function s3s_sync_get_base_fee($sub_head_id, $class_id, $year, $group_id = null)
{
    global $wpdb;

    $query = "SELECT fee FROM ct_student_fee_list
              WHERE sub_head_id = %d AND class_id = %d AND year = %s";
    $params = array($sub_head_id, $class_id, $year);

    if (!empty($group_id)) {
        $query .= " AND group_id = %d";
        $params[] = $group_id;
    }

    $query .= " ORDER BY id DESC LIMIT 1";

    $fee = $wpdb->get_var($wpdb->prepare($query, $params));

    return $fee ? floatval($fee) : 0;
}

/**
 * Summary Tables Sync: Insert missing monthly fee summary entries
 *
 * Loops from fee_month down to 1 and creates individual monthly summary rows
 * for each unpaid month, applying facility discounts and transport/coaching logic.
 *
 * @param array $record Collection detail record with joined info data
 * @return array Result with success flag and message/rows count
 */
function s3s_sync_insert_monthly($record)
{
    global $wpdb;
    global $monthlyFeeSubHeadId, $transportFeeSubHeadId, $coachingFeeSubHeadId;

    $student_id   = $record['student_id'];
    $class_id     = $record['class_id'];
    $section      = $record['section'];
    $group_id     = $record['group_id'];
    $year         = $record['year'];
    $fee_month    = $record['fee_month'];
    $sub_head_id  = $record['sub_head_id'];
    $info_id      = $record['info_id'];
    $detail_date  = $record['detail_date'];
    $created_by   = !empty($record['created_by']) ? $record['created_by'] : 0;

    // Get student details (facilities, monthly_fee)
    $student = $wpdb->get_row($wpdb->prepare(
        "SELECT facilities, monthly_fee FROM ct_student WHERE studentid = %d",
        $student_id
    ));

    if (!$student) {
        return array('success' => false, 'message' => 'Student not found (ID: ' . $student_id . ')');
    }

    $facilities = $student->facilities;

    // Get base fee from fee list
    $base_fee = s3s_sync_get_base_fee($sub_head_id, $class_id, $year, $group_id);

    $month_names   = array("January", "February", "March", "April", "May", "June",
                           "July", "August", "September", "October", "November", "December");
    $rows_inserted = 0;
    $sum_of_fees   = 0;
    $last_monthly_id = null;
    $ledger_note   = 'Collection Reference ID-' . $info_id;

    for ($i = $fee_month; $i >= 1; $i--) {
        // Check if this specific month already has a summary entry
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM ct_student_monthly_fee_summary
             WHERE student_id = %d AND sub_head_id = %d AND class_id = %d
             AND year = %s AND month = %d",
            $student_id, $sub_head_id, $class_id, $year, $i
        ));

        if ($existing) {
            continue;
        }

        $fee_amount = $base_fee;
        $notes      = $month_names[$i - 1];
        $l_note     = $ledger_note;

        // Apply specific sub-head business logic
        if ($sub_head_id == $monthlyFeeSubHeadId) {
            if ($facilities == 'Full free' || $facilities == 'Scholarship') {
                $fee_amount = 0;
                $notes      = $month_names[$i - 1] . ' ' . $facilities;
                $l_note     = 'Collection Reference ID (' . $facilities . ')-' . $info_id;
            } elseif ($facilities == 'Half free') {
                $fee_amount = $base_fee / 2;
                $notes      = $month_names[$i - 1] . ' ' . $facilities;
                $l_note     = 'Collection Reference ID (' . $facilities . ')-' . $info_id;
            } else {
                // Check student-wise monthly fee override
                if (!empty($student->monthly_fee) && floatval($student->monthly_fee) > 0) {
                    $fee_amount = floatval($student->monthly_fee);
                }
            }
        } elseif ($sub_head_id == $transportFeeSubHeadId) {
            $transport = $wpdb->get_row($wpdb->prepare(
                "SELECT swf.transport_fee_id, swf.transport_type, swf.transport_required
                 FROM ct_student_wise_fee swf
                 WHERE swf.student_id = %d AND swf.year = %s AND swf.class_id = %d
                 AND swf.fee_type = 3 AND swf.status = 1",
                $student_id, $year, $class_id
            ));

            if ($transport && $transport->transport_required == 1) {
                $transport_fee = $wpdb->get_var($wpdb->prepare(
                    "SELECT amount FROM ct_transport_fee_list WHERE id = %d",
                    $transport->transport_fee_id
                ));
                if ($transport_fee) {
                    $fee_amount = floatval($transport_fee);
                    if ($transport->transport_type == 1) {
                        $fee_amount = $fee_amount / 2;
                        $notes  = $month_names[$i - 1] . ' One way transport';
                        $l_note = 'Collection Reference ID (One way transport)-' . $info_id;
                    } else {
                        $notes  = $month_names[$i - 1] . ' Two way transport';
                        $l_note = 'Collection Reference ID (Two way transport)-' . $info_id;
                    }
                } else {
                    $fee_amount = 0;
                }
            } else {
                $fee_amount = 0;
            }
        } elseif ($sub_head_id == $coachingFeeSubHeadId) {
            $coaching_fee = $wpdb->get_var($wpdb->prepare(
                "SELECT amount FROM ct_student_wise_fee
                 WHERE fee_type = 1 AND student_id = %d AND class_id = %d AND year = %s",
                $student_id, $class_id, $year
            ));
            if ($coaching_fee && floatval($coaching_fee) > 0) {
                $fee_amount = floatval($coaching_fee);
            } else {
                $fee_amount = 0;
            }
            $notes  = $month_names[$i - 1] . ' Coaching fee';
            $l_note = 'Collection Reference ID (Coaching fee)-' . $info_id;
        }

        // Insert monthly summary row
        $insert_result = $wpdb->insert(
            'ct_student_monthly_fee_summary',
            array(
                'student_id'  => $student_id,
                'year'        => $year,
                'month'       => $i,
                'class_id'    => $class_id,
                'section'     => $section ?: null,
                'group_id'    => $group_id ?: null,
                'sub_head_id' => $sub_head_id,
                'fee'         => $fee_amount,
                'status'      => 1,
                'notes'       => $notes,
                'date'        => $detail_date,
                'created_by'  => $created_by,
                'created_at'  => current_time('mysql'),
            )
        );

        if ($insert_result === false) {
            return array(
                'success' => false,
                'message' => 'Monthly summary insert failed for month ' . $i . ': ' . $wpdb->last_error
            );
        }

        $last_monthly_id = $wpdb->insert_id;
        $sum_of_fees    += $fee_amount;
        $rows_inserted++;
    }

    // Insert ledger entry if summary rows were created
    if ($rows_inserted > 0 && $last_monthly_id && function_exists('saveLeadger')) {
        saveLeadger(
            $sub_head_id,
            $sum_of_fees,
            0,
            $l_note ?? $ledger_note,
            $last_monthly_id,
            null,
            null,
            $detail_date,
            $info_id
        );
    }

    return array('success' => true, 'rows' => $rows_inserted);
}

/**
 * Summary Tables Sync: Insert missing yearly fee summary entry
 *
 * @param array $record Collection detail record with joined info data
 * @return array Result with success flag and message/rows count
 */
function s3s_sync_insert_yearly($record)
{
    global $wpdb;
    global $admissionFeeSubHeadId, $admissionFormSubHeadId, $registrationFeeSubHeadId;

    $student_id   = $record['student_id'];
    $class_id     = $record['class_id'];
    $section      = $record['section'];
    $group_id     = $record['group_id'];
    $year         = $record['year'];
    $sub_head_id  = $record['sub_head_id'];
    $info_id      = $record['info_id'];
    $detail_fee   = $record['detail_fee'];
    $detail_date  = $record['detail_date'];
    $created_by   = !empty($record['created_by']) ? $record['created_by'] : 0;

    // Check if yearly summary already exists (using student+sub_head+class+year)
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM ct_student_yearly_fee_summary
         WHERE student_id = %d AND sub_head_id = %d AND class_id = %d AND year = %s",
        $student_id, $sub_head_id, $class_id, $year
    ));

    if ($existing) {
        return array('success' => true, 'rows' => 0);
    }

    // Get student for facilities/admission_type
    $student = $wpdb->get_row($wpdb->prepare(
        "SELECT facilities, admission_type FROM ct_student WHERE studentid = %d",
        $student_id
    ));

    $facilities     = $student ? $student->facilities : '';
    $admission_type = $student ? $student->admission_type : '';

    $fee_amount = $detail_fee;
    $notes      = 'Yearly Summary';

    if ($sub_head_id == $admissionFeeSubHeadId) {
        if ($admission_type == 1) {
            // New admitted student
            if ($facilities == 'Half free') {
                $fee_amount = $detail_fee / 2;
            }
            $notes = 'Yearly Summary (NEW ADMITTED)';
        } else {
            // Promoted student — check promoted admission fee
            $today = current_time('Y-m-d');
            $promoted_fee = $wpdb->get_var($wpdb->prepare(
                "SELECT amount FROM ct_admission_fee_promoted
                 WHERE class = %d AND admission_start_date <= %s AND admission_end_date >= %s",
                $class_id, $today, $today
            ));
            if ($promoted_fee && floatval($promoted_fee) > 0) {
                $fee_amount = floatval($promoted_fee);
                if ($facilities == 'Half free') {
                    $fee_amount = $fee_amount / 2;
                }
            }
            $notes = 'Yearly Summary (PROMOTED)';
        }
    } elseif ($sub_head_id == $admissionFormSubHeadId) {
        if ($facilities == 'Half free') {
            $fee_amount = $detail_fee / 2;
        }
        $notes = 'Yearly Summary (Session Fee)';
    } elseif ($sub_head_id == $registrationFeeSubHeadId) {
        $reg_fee = $wpdb->get_var($wpdb->prepare(
            "SELECT amount FROM ct_student_wise_fee
             WHERE fee_type = 2 AND student_id = %d AND class_id = %d AND year = %s",
            $student_id, $class_id, $year
        ));
        if ($reg_fee && floatval($reg_fee) > 0) {
            $fee_amount = floatval($reg_fee);
        }
        $notes = 'Yearly Summary (Registration Fee)';
    }

    $insert_result = $wpdb->insert(
        'ct_student_yearly_fee_summary',
        array(
            'student_id'  => $student_id,
            'year'        => $year,
            'class_id'    => $class_id,
            'section'     => $section ?: null,
            'group_id'    => $group_id ?: null,
            'sub_head_id' => $sub_head_id,
            'fee'         => $fee_amount,
            'status'      => 1,
            'notes'       => $notes,
            'date'        => $detail_date,
            'created_by'  => $created_by,
            'created_at'  => current_time('mysql'),
        )
    );

    if ($insert_result === false) {
        return array(
            'success' => false,
            'message' => 'Yearly summary insert failed: ' . $wpdb->last_error
        );
    }

    $yearly_id = $wpdb->insert_id;

    // Insert ledger entry
    if (function_exists('saveLeadger')) {
        saveLeadger(
            $sub_head_id,
            $fee_amount,
            0,
            'Collection Reference ID-' . $info_id,
            null,
            $yearly_id,
            null,
            $detail_date,
            $info_id
        );
    }

    return array('success' => true, 'rows' => 1);
}

/**
 * Summary Tables Sync: Insert missing exam fee summary entry
 *
 * @param array $record Collection detail record with joined info data
 * @return array Result with success flag and message/rows count
 */
function s3s_sync_insert_exam($record)
{
    global $wpdb;

    $student_id   = $record['student_id'];
    $class_id     = $record['class_id'];
    $section      = $record['section'];
    $group_id     = $record['group_id'];
    $year         = $record['year'];
    $sub_head_id  = $record['sub_head_id'];
    $info_id      = $record['info_id'];
    $detail_fee   = $record['detail_fee'];
    $detail_date  = $record['detail_date'];
    $created_by   = !empty($record['created_by']) ? $record['created_by'] : 0;

    // Get active exam for the class
    $active_exam = $wpdb->get_row($wpdb->prepare(
        "SELECT examid FROM ct_exam WHERE active_for_collection = 1 AND examClass = %d LIMIT 1",
        $class_id
    ));

    if (!$active_exam) {
        return array(
            'success' => false,
            'message' => 'No active exam found for class ' . $class_id
        );
    }

    $exam_id = $active_exam->examid;

    // Check if exam summary already exists
    $existing = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM ct_student_exam_fee_summary
         WHERE student_id = %d AND sub_head_id = %d AND exam_id = %d
         AND class_id = %d AND year = %s",
        $student_id, $sub_head_id, $exam_id, $class_id, $year
    ));

    if ($existing) {
        return array('success' => true, 'rows' => 0);
    }

    $insert_result = $wpdb->insert(
        'ct_student_exam_fee_summary',
        array(
            'student_id'  => $student_id,
            'year'        => $year,
            'class_id'    => $class_id,
            'section'     => $section ?: null,
            'group_id'    => $group_id ?: null,
            'exam_id'     => $exam_id,
            'sub_head_id' => $sub_head_id,
            'fee'         => $detail_fee,
            'status'      => 1,
            'notes'       => 'Exam Summary',
            'date'        => $detail_date,
            'created_by'  => $created_by,
            'created_at'  => current_time('mysql'),
        )
    );

    if ($insert_result === false) {
        return array(
            'success' => false,
            'message' => 'Exam summary insert failed: ' . $wpdb->last_error
        );
    }

    $exam_fee_id = $wpdb->insert_id;

    // Insert ledger entry
    if (function_exists('saveLeadger')) {
        saveLeadger(
            $sub_head_id,
            $detail_fee,
            0,
            'Collection Reference ID-' . $info_id,
            null,
            null,
            $exam_fee_id,
            $detail_date,
            $info_id
        );
    }

    return array('success' => true, 'rows' => 1);
}

/**
 * Summary Tables Sync: Main callback
 *
 * Queries all collection info+details records and finds those
 * that are missing from the corresponding summary tables.
 * Only inserts missing entries — never overwrites existing data.
 *
 * Designed to be called via POST /v1/cron/sync-summary-tables
 *
 * @param WP_REST_Request $request
 * @return array Summary of processed records
 */
function s3s_sync_summary_tables(WP_REST_Request $request)
{
    global $wpdb;

    // Get all collection details linked to sub_heads that have summary tables (type 1, 2, 3)
    $current_year = current_time('Y');
    $details_query = $wpdb->prepare(
        "SELECT d.id AS detail_id, d.info_id, d.sub_head_id, d.fee AS detail_fee, d.date AS detail_date,
                                i.student_id, i.year, i.month AS fee_month, i.class_id, i.section, i.group_id,
                                i.date AS info_date, i.created_by,
                                sh.type AS sub_head_type, sh.sub_head_name
                         FROM ct_student_fee_collection_details d
                         INNER JOIN ct_student_fee_collection_info i ON d.info_id = i.id
                         INNER JOIN ct_sub_head sh ON d.sub_head_id = sh.id
                         WHERE sh.type IN (1, 2, 3) AND i.year = %d
                         ORDER BY d.info_id ASC, d.id ASC",
        $current_year
    );

    $records = $wpdb->get_results($details_query, ARRAY_A);

    if (empty($records)) {
        return array(
            'success'   => true,
            'message'   => 'No collection records found to process',
            'processed' => 0,
            'inserted'  => 0,
            'skipped'   => 0,
            'errors'    => array(),
        );
    }

    $processed = 0;
    $inserted  = 0;
    $skipped   = 0;
    $errors    = array();

    foreach ($records as $record) {
        $processed++;
        $info_id      = $record['info_id'];
        $sub_head_id  = $record['sub_head_id'];
        $sub_head_type = (int) $record['sub_head_type'];
        $student_id   = (int) $record['student_id'];

        // DEBUG: Log student 1022 details
        if ($student_id === 1022) {
            error_log(sprintf(
                '[DEBUG s3s_sync_summary_tables] Processing student_id=1022: info_id=%d, sub_head_id=%d, sub_head_type=%d, sub_head_name=%s, detail_fee=%s, class_id=%d, year=%s, fee_month=%s',
                $info_id,
                $sub_head_id,
                $sub_head_type,
                $record['sub_head_name'],
                $record['detail_fee'],
                $record['class_id'],
                $record['year'],
                $record['fee_month'] ?? 'N/A'
            ));
        }

        // Check if this record's summary already exists
        $already_synced = s3s_sync_check_exists($sub_head_type, $record);

        // DEBUG: Log check_exists result for student 1022
        if ($student_id === 1022) {
            error_log('[DEBUG s3s_sync_summary_tables] student_id=1022: s3s_sync_check_exists(type=' . $sub_head_type . ') returned ' . ($already_synced ? 'true (SKIPPING)' : 'false (will insert)'));
        }

        if ($already_synced) {
            $skipped++;
            continue;
        }

        // Process based on sub-head type
        if ($sub_head_type == 1) {
            $result = s3s_sync_insert_monthly($record);
        } elseif ($sub_head_type == 2) {
            $result = s3s_sync_insert_yearly($record);
        } elseif ($sub_head_type == 3) {
            // DEBUG: Log before exam insert for student 1022
            if ($student_id === 1022) {
                error_log('[DEBUG s3s_sync_summary_tables] student_id=1022: Calling s3s_sync_insert_exam()');
            }
            $result = s3s_sync_insert_exam($record);
            // DEBUG: Log exam insert result for student 1022
            if ($student_id === 1022) {
                error_log('[DEBUG s3s_sync_summary_tables] student_id=1022: s3s_sync_insert_exam() returned success=' . ($result['success'] ? 'true' : 'false') . ' rows=' . ($result['rows'] ?? 'N/A') . ' message=' . ($result['message'] ?? 'OK'));
            }
        } else {
            $result = array('success' => false, 'message' => 'Unsupported sub-head type: ' . $sub_head_type);
        }

        if ($result['success']) {
            $inserted++;
            // DEBUG: Log successful insert for student 1022
            if ($student_id === 1022) {
                error_log('[DEBUG s3s_sync_summary_tables] student_id=1022: Insert succeeded for info_id=' . $info_id . ', sub_head_type=' . $sub_head_type);
            }
        } else if (count($errors) < 10) {
            $errors[] = "Info ID {$info_id}, Sub-head {$sub_head_id} (" . $record['sub_head_name'] . '): ' . $result['message'];
            // DEBUG: Log error for student 1022
            if ($student_id === 1022) {
                error_log('[DEBUG s3s_sync_summary_tables] student_id=1022: ERROR - ' . end($errors));
            }
            // Also log all exam-related errors immediately
            if ($sub_head_type == 3) {
                error_log('[DEBUG s3s_sync_summary_tables] EXAM SYNC FAILED: info_id=' . $info_id . ', student_id=' . $student_id . ', sub_head_id=' . $sub_head_id . ', class_id=' . $record['class_id'] . ', year=' . $record['year'] . ' - ' . $result['message']);
            }
        }
    }

    return array(
        'success'   => true,
        'message'   => "Processed {$processed} record(s): {$inserted} inserted, {$skipped} skipped",
        'processed' => $processed,
        'inserted'  => $inserted,
        'skipped'   => $skipped,
        'errors'    => $errors,
    );
}

// ======================================================================
// Payment Mismatch Detection API
// ======================================================================

/**
 * Scan a single student for payment mismatches and write to ct_payment_mismatch.
 *
 * POST /v1/mismatch/scan-student
 * Body: { student_id: int, class_id: int, year: string }
 */
function s3s_mismatch_scan_student(WP_REST_Request $request)
{
    global $wpdb;

    $student_id = intval($request->get_param('student_id'));
    $class_id   = intval($request->get_param('class_id'));
    $year       = $request->get_param('year') ?: current_time('Y');

    if (!$student_id || !$class_id) {
        return new WP_Error('missing_params', 'student_id and class_id are required', array('status' => 400));
    }

    $user_id = get_current_user_id();

    $ps_table = $wpdb->prefix . 'paystation_transactions';
    $ps_exists = $wpdb->get_var("SHOW TABLES LIKE '$ps_table'");

    // Get PayStation transactions for this student — linked via transaction_id in collection info
    $paystationTxns = array();
    if ($ps_exists) {
        $paystationTxns = $wpdb->get_results($wpdb->prepare(
            "SELECT ps.*
            FROM {$ps_table} ps
            INNER JOIN ct_student_fee_collection_info ci ON ci.transaction_id = ps.transaction_id
            WHERE ci.student_id = %d AND ci.year = %s
            ORDER BY ps.created_at DESC",
            $student_id, $year
        ), ARRAY_A);
    }

    // Get collection records for this student
    $collectionRecords = $wpdb->get_results($wpdb->prepare(
        "SELECT ci.id, ci.year, ci.month, ci.sub_total, ci.total, ci.date,
                ci.payment_method, ci.transaction_id, ci.payment_id,
                cd.sub_head_id, cd.fee
            FROM ct_student_fee_collection_info ci
            LEFT JOIN ct_student_fee_collection_details cd ON cd.info_id = ci.id
            WHERE ci.student_id = %d AND ci.year = %s
            ORDER BY ci.date DESC",
        $student_id, $year
    ), ARRAY_A);

    $mismatches_found = 0;
    $inserted = 0;

    // 1. Duplicate payment detection by invoice_number
    $invoice_counts = array();
    foreach ($paystationTxns as $txn) {
        $inv = $txn['invoice_number'];
        if (!empty($inv)) $invoice_counts[$inv][] = $txn;
    }
    foreach ($invoice_counts as $inv => $txns) {
        if (count($txns) > 1) {
            $total_amt = 0;
            $payment_ids = array();
            foreach ($txns as $t) {
                $total_amt += floatval($t['total_amount']);
                $payment_ids[] = $t['payment_id'];
            }
            // Check if already recorded
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT mismatch_id FROM ct_payment_mismatch WHERE payment_id = %s AND mismatch_type = 'DUPLICATE_PAYMENT' AND status != 'CLOSED' LIMIT 1",
                $txns[0]['payment_id']
            ));
            if (!$exists) {
                $wpdb->insert('ct_payment_mismatch', array(
                    'payment_id'   => $txns[0]['payment_id'],
                    'student_id'   => $student_id,
                    'mismatch_type' => 'DUPLICATE_PAYMENT',
                    'amount'       => $total_amt,
                    'description'  => sprintf('Invoice %s has %d payments totalling %.2f. IDs: %s', $inv, count($txns), $total_amt, implode(', ', $payment_ids)),
                    'status'       => 'PENDING',
                    'detected_at'  => current_time('mysql'),
                    'detected_by'  => $user_id,
                ));
                $mismatches_found++;
                if ($wpdb->insert_id) $inserted++;
            } else {
                $mismatches_found++;
            }
        }
    }

    // 2. Unmatched paid transactions (paid but no collection record)
    $coll_invoices = array();
    $coll_payment_ids = array();
    foreach ($collectionRecords as $cr) {
        if (!empty($cr['transaction_id'])) $coll_invoices[$cr['transaction_id']] = true;
        if (!empty($cr['payment_id'])) $coll_payment_ids[$cr['payment_id']] = true;
    }
    foreach ($paystationTxns as $txn) {
        $matched_by_transaction = !empty($txn['transaction_id']) && isset($coll_invoices[$txn['transaction_id']]);
        $matched_by_payment = !empty($txn['payment_id']) && isset($coll_payment_ids[$txn['payment_id']]);
        if ($txn['status'] === 'paid' && !$matched_by_transaction && !$matched_by_payment) {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT mismatch_id FROM ct_payment_mismatch WHERE payment_id = %s AND mismatch_type = 'UNIDENTIFIED_PAYMENT' AND status != 'CLOSED' LIMIT 1",
                $txn['payment_id']
            ));
            if (!$exists) {
                $wpdb->insert('ct_payment_mismatch', array(
                    'payment_id'   => $txn['payment_id'],
                    'student_id'   => $student_id,
                    'mismatch_type' => 'UNIDENTIFIED_PAYMENT',
                    'amount'       => floatval($txn['total_amount']),
                    'description'  => sprintf('PayStation transaction %s is paid (%.2f) but no matching collection record found', $txn['transaction_id'], floatval($txn['total_amount'])),
                    'status'       => 'PENDING',
                    'detected_at'  => current_time('mysql'),
                    'detected_by'  => $user_id,
                ));
                $inserted++;
            }
            $mismatches_found++;
        }
    }

    return array(
        'success'           => true,
        'message'           => "Scan complete for student #{$student_id}: {$mismatches_found} mismatch(es) found, {$inserted} new record(s) inserted",
        'student_id'        => $student_id,
        'mismatches_found'  => $mismatches_found,
        'new_records'       => $inserted,
    );
}

/**
 * Scan all students in a class for payment mismatches.
 *
 * POST /v1/mismatch/scan-class
 * Body: { class_id: int, year: string }
 */
function s3s_mismatch_scan_class(WP_REST_Request $request)
{
    global $wpdb;

    $class_id = intval($request->get_param('class_id'));
    $year     = $request->get_param('year') ?: current_time('Y');
    $limit    = intval($request->get_param('limit')) ?: 50;
    $offset   = intval($request->get_param('offset')) ?: 0;

    if (!$class_id) {
        return new WP_Error('missing_params', 'class_id is required', array('status' => 400));
    }

    // Get students for this class
    $students = $wpdb->get_results($wpdb->prepare(
        "SELECT si.infoStdid AS student_id, si.infoRoll, s.stdName
         FROM ct_studentinfo si
         LEFT JOIN ct_student s ON s.studentid = si.infoStdid
         WHERE si.infoClass = %d AND si.infoYear = %s
         ORDER BY si.infoRoll ASC
         LIMIT %d OFFSET %d",
        $class_id, $year, $limit, $offset
    ), ARRAY_A);

    if (empty($students)) {
        return array(
            'success'   => true,
            'message'   => 'No students found for this class/year',
            'processed' => 0,
            'total_mismatches' => 0,
            'more'      => false,
        );
    }

    $total_mismatches = 0;
    $total_new = 0;
    $errors   = array();

    foreach ($students as $std) {
        // Simulate a request to scan this student
        $mock_request = new WP_REST_Request('POST');
        $mock_request->set_param('student_id', $std['student_id']);
        $mock_request->set_param('class_id', $class_id);
        $mock_request->set_param('year', $year);

        $result = s3s_mismatch_scan_student($mock_request);
        if (is_wp_error($result)) {
            $errors[] = "Student #{$std['student_id']}: " . $result->get_error_message();
        } else {
            $total_mismatches += $result['mismatches_found'];
            $total_new += $result['new_records'];
        }
    }

    // Check if there are more students
    $total_students = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM ct_studentinfo WHERE infoClass = %d AND infoYear = %s",
        $class_id, $year
    ));
    $more = ($offset + $limit) < intval($total_students);

    return array(
        'success'           => true,
        'message'           => "Processed " . count($students) . " student(s): {$total_mismatches} mismatch(es), {$total_new} new record(s)",
        'processed'         => count($students),
        'total_mismatches'  => $total_mismatches,
        'new_records'       => $total_new,
        'more'              => $more,
        'next_offset'       => $offset + $limit,
        'errors'            => $errors,
    );
}

/**
 * Update a mismatch claim status (approve/reject).
 *
 * POST /v1/mismatch/update-claim
 * Body: { claim_id: int, status: string, review_notes: string }
 */
function s3s_mismatch_update_claim(WP_REST_Request $request)
{
    global $wpdb;

    $claim_id    = intval($request->get_param('claim_id'));
    $new_status  = $request->get_param('status');
    $review_notes = $request->get_param('review_notes', '');

    if (!$claim_id || !in_array($new_status, array('APPROVED', 'REJECTED'))) {
        return new WP_Error('invalid_params', 'Valid claim_id and status (APPROVED|REJECTED) required', array('status' => 400));
    }

    $user_id = get_current_user_id();

    $updated = $wpdb->update(
        'ct_mismatch_claim',
        array(
            'status'       => $new_status,
            'reviewed_by'  => $user_id,
            'reviewed_at'  => current_time('mysql'),
            'review_notes' => $review_notes,
        ),
        array('claim_id' => $claim_id, 'status' => 'PENDING')
    );

    if ($updated === false) {
        return new WP_Error('db_error', 'Failed to update claim: ' . $wpdb->last_error, array('status' => 500));
    }

    if ($updated === 0) {
        return array(
            'success' => false,
            'message' => 'Claim not found or already processed',
        );
    }

    // If approved, also update the related mismatch
    $claim = $wpdb->get_row($wpdb->prepare(
        "SELECT c.mismatch_id, c.claim_student_id, m.student_id, m.mismatch_type
         FROM ct_mismatch_claim c
         LEFT JOIN ct_payment_mismatch m ON c.mismatch_id = m.mismatch_id
         WHERE c.claim_id = %d",
        $claim_id
    ));

    if ($claim && $new_status === 'APPROVED') {
        $wpdb->update(
            'ct_payment_mismatch',
            array('status' => 'CLAIMED', 'resolved_at' => current_time('mysql')),
            array('mismatch_id' => $claim->mismatch_id, 'status' => 'PENDING')
        );
    }

    return array(
        'success'  => true,
        'message'  => "Claim #{$claim_id} has been {$new_status}",
        'claim_id' => $claim_id,
        'status'   => $new_status,
    );
}

/**
 * Scan ALL classes for mismatch detection — designed for cron jobs.
 *
 * Iterates through every class in ct_class for the given year (or current year),
 * scanning all students in each class in batches. Returns a cumulative summary.
 *
 * POST /v1/mismatch/scan-all-classes
 * Body: { year: string (optional, defaults to current year) }
 */
function s3s_mismatch_scan_all_classes(WP_REST_Request $request)
{
    global $wpdb;

    $year       = $request->get_param('year') ?: current_time('Y');
    $batch_size = intval($request->get_param('batch_size')) ?: 50;
    $start_time = microtime(true);

    // Verify mismatch table exists
    $mm_exists = $wpdb->get_var("SHOW TABLES LIKE 'ct_payment_mismatch'");
    if (!$mm_exists) {
        return array(
            'success' => false,
            'message' => 'ct_payment_mismatch table does not exist. Run the migration first.',
            'year'    => $year,
            'classes_processed' => 0,
            'total_students'    => 0,
            'total_mismatches'  => 0,
            'new_records'       => 0,
            'errors'            => array('Missing ct_payment_mismatch table'),
        );
    }

    // Get all classes that have students enrolled for this year
    $classes = $wpdb->get_results($wpdb->prepare(
        "SELECT DISTINCT c.classid, c.className
         FROM ct_class c
         INNER JOIN ct_studentinfo si ON si.infoClass = c.classid
         WHERE si.infoYear = %s
         ORDER BY c.className ASC",
        $year
    ), ARRAY_A);

    if (empty($classes)) {
        return array(
            'success' => true,
            'message' => "No classes found with students enrolled for {$year}",
            'year'    => $year,
            'classes_processed' => 0,
            'total_students'    => 0,
            'total_mismatches'  => 0,
            'new_records'       => 0,
            'errors'            => array(),
        );
    }

    $total_classes      = count($classes);
    $total_students     = 0;
    $total_mismatches   = 0;
    $total_new_records  = 0;
    $class_results      = array();
    $errors             = array();

    foreach ($classes as $cls) {
        $class_id   = intval($cls['classid']);
        $class_name = $cls['className'];
        $class_students   = 0;
        $class_mismatches = 0;
        $class_new        = 0;
        $class_errors     = array();
        $offset           = 0;

        // Process this class in batches until all students are scanned
        do {
            $mock_request = new WP_REST_Request('POST');
            $mock_request->set_param('class_id', $class_id);
            $mock_request->set_param('year', $year);
            $mock_request->set_param('limit', $batch_size);
            $mock_request->set_param('offset', $offset);

            $result = s3s_mismatch_scan_class($mock_request);

            if (is_wp_error($result)) {
                $class_errors[] = $result->get_error_message();
                break;
            }

            $class_students   += $result['processed'];
            $class_mismatches += $result['total_mismatches'];
            $class_new        += $result['new_records'];

            if (!empty($result['errors'])) {
                foreach ($result['errors'] as $e) {
                    $class_errors[] = $e;
                }
            }

            $has_more = isset($result['more']) ? $result['more'] : false;
            $offset   = isset($result['next_offset']) ? $result['next_offset'] : ($offset + $batch_size);

        } while ($has_more);

        $total_students    += $class_students;
        $total_mismatches  += $class_mismatches;
        $total_new_records += $class_new;

        $class_results[] = array(
            'class_id'      => $class_id,
            'class_name'    => $class_name,
            'students'      => $class_students,
            'mismatches'    => $class_mismatches,
            'new_records'   => $class_new,
            'errors'        => $class_errors,
        );

        if (!empty($class_errors)) {
            foreach ($class_errors as $e) {
                $errors[] = "{$class_name}: {$e}";
            }
        }
    }

    $elapsed = round(microtime(true) - $start_time, 2);

    return array(
        'success'           => true,
        'message'           => "Scanned {$total_classes} class(es), {$total_students} student(s) for {$year}: {$total_mismatches} mismatch(es) found, {$total_new_records} new record(s) inserted in {$elapsed}s",
        'year'              => $year,
        'classes_processed' => $total_classes,
        'total_students'    => $total_students,
        'total_mismatches'  => $total_mismatches,
        'new_records'       => $total_new_records,
        'elapsed_seconds'   => $elapsed,
        'class_details'     => $class_results,
        'errors'            => $errors,
    );
}

/**
 * PayStation: Fill missing transaction_ids (internal helper)
 *
 * Selects all rows from paystation_transactions where transaction_id IS NULL,
 * fetches their status from PayStation API by invoice_number,
 * and updates the transaction_id with the trx_id from the response.
 * Called automatically at the start of s3s_paystation_sync_pending_status().
 */
function s3s_paystation_fill_missing_transaction_ids()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'paystation_transactions';

    // Verify the table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    if (!$table_exists) {
        return array(
            'success'   => true,
            'message'   => 'No PayStation transactions table found',
            'processed' => 0,
            'updated'   => 0,
            'errors'    => [],
        );
    }

    // 1. Select all rows where transaction_id IS NULL and invoice_number is not empty
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$table_name} WHERE (transaction_id IS NULL OR transaction_id LIKE %s) AND invoice_number IS NOT NULL AND invoice_number != %s ORDER BY id ASC",
            'PAY%',
            ''
        ),
        ARRAY_A
    );

    if (empty($rows)) {
        return array(
            'success'   => true,
            'message'   => 'No rows found with missing transaction_id',
            'processed' => 0,
            'updated'   => 0,
            'errors'    => [],
        );
    }

    $config   = get_paystation_config();
    $base_url = get_paystation_base_url();
    $url      = $base_url . '/transaction-status';

    $processed = 0;
    $updated   = 0;
    $errors    = [];

    foreach ($rows as $row) {
        $invoice_number = $row['invoice_number'];
        $processed++;

        // 2. Call PayStation transaction-status API
       $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_POSTFIELDS     => [
                'invoice_number' => $invoice_number,
            ],
            CURLOPT_HTTPHEADER     => [
                'merchantId: ' . $config['merchant_id'],
            ],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response_body = curl_exec($curl);
        $curl_error    = curl_error($curl);
        $http_code     = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($curl_error) {
            $errors[] = "Invoice {$invoice_number}: cURL error - {$curl_error}";
            continue;
        }

        $response_data = json_decode($response_body, true);

        if (!$response_data) {
            $errors[] = "Invoice {$invoice_number}: Invalid JSON response";
            continue;
        }

        // 3. Check if API returned success with trx_id
        $status_code = $response_data['status_code'] ?? '';
        $api_status  = $response_data['status'] ?? '';
        $trx_id      = $response_data['data']['trx_id'] ?? '';

        if ($status_code === '200' && $api_status === 'success' && !empty($trx_id)) {
            // 4. Update the transaction_id in the database
            $result = $wpdb->update(
                $table_name,
                array(
                    'transaction_id' => $trx_id,
                    'updated_at'     => get_bdt_time(),
                ),
                array('id' => $row['id']),
                array('%s', '%s'),
                array('%d')
            );

            if ($result !== false) {
                $updated++;
                error_log("PayStation Fill: Updated transaction_id for invoice {$invoice_number} → {$trx_id}");
            } else {
                $errors[] = "Invoice {$invoice_number}: DB update failed - {$wpdb->last_error}";
            }
        } else {
            $errors[] = "Invoice {$invoice_number}: API returned status={$api_status}, code={$status_code}" . ($trx_id ? ', trx_id empty' : ', no trx_id');
        }
    }

    return array(
        'success'   => true,
        'message'   => "Processed {$processed} row(s), updated {$updated} transaction_id(s)",
        'processed' => $processed,
        'updated'   => $updated,
        'errors'    => $errors,
    );
}

// ======================================================================
// Payment Transfer API
// ======================================================================

/**
 * Get students by class for cascading dropdown
 *
 * GET /v1/students/by-class?class_id=1&year=2026&search=
 *
 * @param WP_REST_Request $request
 * @return array List of matching students
 */
function s3s_students_by_class(WP_REST_Request $request)
{
    global $wpdb;

    $class_id = intval($request->get_param('class_id'));
    $year     = $request->get_param('year') ?: current_time('Y');
    $search   = $request->get_param('search');
    $section  = intval($request->get_param('section'));

    if (!$class_id) {
        return new WP_Error('missing_params', 'class_id is required', array('status' => 400));
    }

    $query = "SELECT si.infoStdid AS student_id, si.infoRoll, si.infoSection,
                     s.stdName, s.facilities
              FROM ct_studentinfo si
              LEFT JOIN ct_student s ON s.studentid = si.infoStdid
              WHERE si.infoClass = %d AND si.infoYear = %s";
    $params = array($class_id, $year);

    if ($section) {
        $query .= " AND si.infoSection = %d";
        $params[] = $section;
    }

    if (!empty($search)) {
        $query .= " AND (s.stdName LIKE %s OR si.infoRoll LIKE %s)";
        $like = '%' . $wpdb->esc_like($search) . '%';
        $params[] = $like;
        $params[] = $like;
    }

    $query .= " ORDER BY si.infoRoll ASC LIMIT 100";

    $students = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);

    if (empty($students)) {
        return array(
            'success'  => true,
            'students' => array(),
            'message'  => 'No students found',
        );
    }

    return array(
        'success'  => true,
        'students' => $students,
        'count'    => count($students),
    );
}

/**
 * Get sections for a class
 *
 * GET /v1/sections/by-class?class_id=1
 *
 * @param WP_REST_Request $request
 * @return array List of sections
 */
function s3s_sections_by_class(WP_REST_Request $request)
{
    global $wpdb;

    $class_id = intval($request->get_param('class_id'));

    if (!$class_id) {
        return new WP_Error('missing_params', 'class_id is required', array('status' => 400));
    }

    $sections = $wpdb->get_results($wpdb->prepare(
        "SELECT sectionid, sectionName
         FROM ct_section
         WHERE forClass = %d
         ORDER BY sectionName ASC",
        $class_id
    ), ARRAY_A);

    return array(
        'success'  => true,
        'sections' => $sections ?: array(),
        'count'    => count($sections ?: array()),
    );
}

/**
 * Transfer a PayStation payment to another student
 *
 * POST /v1/payment/transfer
 * Body: { payment_id: string, target_student_id: int, note: string }
 *
 * @param WP_REST_Request $request
 * @return array Result of the transfer
 */
function s3s_payment_transfer(WP_REST_Request $request)
{
    global $wpdb;

    $payment_id       = $request->get_param('payment_id');
    $target_student_id = intval($request->get_param('target_student_id'));
    $note             = sanitize_text_field($request->get_param('note', ''));

    if (empty($payment_id) || !$target_student_id) {
        return new WP_Error('missing_params', 'payment_id and target_student_id are required', array('status' => 400));
    }

    $ps_table = $wpdb->prefix . 'paystation_transactions';

    // Verify table exists
    if (!$wpdb->get_var("SHOW TABLES LIKE '$ps_table'")) {
        return new WP_Error('no_table', 'PayStation transactions table not found', array('status' => 500));
    }

    // Get the source transaction
    $transaction = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $ps_table WHERE payment_id = %s",
        $payment_id
    ), ARRAY_A);

    if (!$transaction) {
        return new WP_Error('not_found', 'Transaction not found with payment_id: ' . $payment_id, array('status' => 404));
    }

    // Verify target student exists
    $target_student = $wpdb->get_row($wpdb->prepare(
        "SELECT s.studentid, s.stdName, si.infoRoll, si.infoClass
         FROM ct_student s
         LEFT JOIN ct_studentinfo si ON si.infoStdid = s.studentid
         WHERE s.studentid = %d",
        $target_student_id
    ));

    if (!$target_student) {
        return new WP_Error('invalid_student', 'Target student not found', array('status' => 404));
    }

    $old_student_id = $transaction['student_id'];

    // Build transfer log data — preserve original student_data for audit, update to new student
    $original_student_data = json_decode($transaction['student_data'], true);
    $old_student_name = '';
    if ($original_student_data && isset($original_student_data['student_name'])) {
        $old_student_name = $original_student_data['student_name'];
    } else {
        $old = $wpdb->get_var($wpdb->prepare("SELECT stdName FROM ct_student WHERE studentid = %d", $old_student_id));
        $old_student_name = $old ?: 'Unknown';
    }

    // Build new student_data preserving original reference
    $new_student_data = array(
        'student_id'   => $target_student_id,
        'student_name' => $target_student->stdName,
        'original_student_id'   => $old_student_id,
        'original_student_name' => $old_student_name,
        'transferred_at'        => current_time('mysql'),
        'transfer_note'         => $note,
    );

    // Also update fee_data if present — tag it as transferred
    $fee_data = json_decode($transaction['fee_data'], true);
    if ($fee_data) {
        $fee_data['transferred_from'] = $old_student_id;
        $fee_data['transferred_to']   = $target_student_id;
        $fee_data['transferred_at']   = current_time('mysql');
    }

    // Update the transaction record
    $update_data = array(
        'student_id'   => $target_student_id,
        'student_data' => json_encode($new_student_data),
        'fee_data'     => $fee_data ? json_encode($fee_data) : $transaction['fee_data'],
        'updated_at'   => get_bdt_time(),
    );

    $updated = $wpdb->update(
        $ps_table,
        $update_data,
        array('payment_id' => $payment_id),
        array('%d', '%s', '%s', '%s'),
        array('%s')
    );

    if ($updated === false) {
        return new WP_Error('db_error', 'Failed to update transaction: ' . $wpdb->last_error, array('status' => 500));
    }

    // Log the transfer event
    error_log(sprintf(
        'Payment Transfer: Payment %s moved from student #%d (%s) to student #%d (%s). Note: %s',
        $payment_id,
        $old_student_id,
        $old_student_name,
        $target_student_id,
        $target_student->stdName,
        $note
    ));

    return array(
        'success'            => true,
        'message'            => sprintf('Payment %s transferred from %s (ID: %d) to %s (ID: %d)', $payment_id, $old_student_name, $old_student_id, $target_student->stdName, $target_student_id),
        'payment_id'         => $payment_id,
        'old_student_id'     => $old_student_id,
        'old_student_name'   => $old_student_name,
        'target_student_id'  => $target_student_id,
        'target_student_name' => $target_student->stdName,
    );
}

/**
 * Claim-based Payment Transfer — Student claims a transaction recorded under wrong info
 *
 * POST /v1/payment/claim-transfer
 * Body: {
 *   transaction_id: string,        (PayStation payment_id)
 *   current_student_id: int,       (the correct student id to transfer to)
 *   wrong_class: int,              (class that was incorrectly used)
 *   wrong_section: int,            (section that was incorrectly used)
 *   wrong_roll: string,            (roll that was incorrectly used)
 *   wrong_year: string,            (year that was incorrectly used)
 *   note: string                   (optional claim note)
 * }
 *
 * @param WP_REST_Request $request
 * @return array Result of the claim transfer
 */
function s3s_payment_claim_transfer(WP_REST_Request $request)
{
    global $wpdb;

    $transaction_id    = $request->get_param('transaction_id');
    $current_student_id = intval($request->get_param('current_student_id'));
    $wrong_class       = intval($request->get_param('wrong_class'));
    $wrong_section     = intval($request->get_param('wrong_section'));
    $wrong_roll        = sanitize_text_field($request->get_param('wrong_roll', ''));
    $wrong_year        = sanitize_text_field($request->get_param('wrong_year', ''));
    $note              = sanitize_text_field($request->get_param('note', ''));

    // Validate required
    if (empty($transaction_id) || !$current_student_id) {
        return new WP_Error('missing_params', 'transaction_id and current_student_id are required', array('status' => 400));
    }

    $ps_table = $wpdb->prefix . 'paystation_transactions';

    // Verify table exists
    if (!$wpdb->get_var("SHOW TABLES LIKE '$ps_table'")) {
        return new WP_Error('no_table', 'PayStation transactions table not found', array('status' => 500));
    }

    // Get the transaction by payment_id
    $transaction = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $ps_table WHERE payment_id = %s",
        $transaction_id
    ), ARRAY_A);

    if (!$transaction) {
        return new WP_Error('not_found', 'No PayStation transaction found with ID: ' . $transaction_id, array('status' => 404));
    }

    // Verify the target (current) student exists
    $target_student = $wpdb->get_row($wpdb->prepare(
        "SELECT s.studentid, s.stdName, s.facilities,
                si.infoRoll, si.infoClass, si.infoSection, si.infoYear
         FROM ct_student s
         LEFT JOIN ct_studentinfo si ON si.infoStdid = s.studentid
         WHERE s.studentid = %d",
        $current_student_id
    ));

    if (!$target_student) {
        return new WP_Error('invalid_student', 'Current student not found with ID: ' . $current_student_id, array('status' => 404));
    }

    $old_student_id = $transaction['student_id'];

    // Get source (old/wrong) student details for audit log
    $source_student = null;
    $source_student_name = '';
    $source_student_roll = '';
    $source_student_class = '';
    $source_student_section = '';
    $source_student_year = '';
    $source_student_facilities = '';

    if ($old_student_id) {
        $source_student = $wpdb->get_row($wpdb->prepare(
            "SELECT s.studentid, s.stdName, s.facilities,
                    si.infoRoll, si.infoClass, si.infoSection, si.infoYear
             FROM ct_student s
             LEFT JOIN ct_studentinfo si ON si.infoStdid = s.studentid
             WHERE s.studentid = %d",
            $old_student_id
        ));

        if ($source_student) {
            $source_student_name = $source_student->stdName;
            $source_student_roll = $source_student->infoRoll;
            $source_student_facilities = $source_student->facilities;
            // Resolve names
            $src_class_name = $wpdb->get_var($wpdb->prepare("SELECT className FROM ct_class WHERE classid = %d", $source_student->infoClass));
            $source_student_class = $src_class_name ?: 'Class #' . $source_student->infoClass;
            $src_sec_name = $wpdb->get_var($wpdb->prepare("SELECT sectionName FROM ct_section WHERE sectionid = %d", $source_student->infoSection));
            $source_student_section = $src_sec_name ?: '';
            $source_student_year = $source_student->infoYear ?: '';
        }
    }

    // If we couldn't get the source from DB, try the transaction's student_data JSON
    if (!$source_student || empty($source_student_name)) {
        $sd = json_decode($transaction['student_data'], true);
        if ($sd && isset($sd['student_name'])) {
            $source_student_name = $sd['student_name'];
        } else {
            $source_student_name = 'Unknown (ID: ' . ($old_student_id ?: 'N/A') . ')';
        }
    }

    // Resolve target student names
    $target_class_name = $wpdb->get_var($wpdb->prepare("SELECT className FROM ct_class WHERE classid = %d", $target_student->infoClass));
    $target_section_name = $wpdb->get_var($wpdb->prepare("SELECT sectionName FROM ct_section WHERE sectionid = %d", $target_student->infoSection));

    // Build new student_data preserving audit trail
    $original_student_data = json_decode($transaction['student_data'], true) ?: array();
    $new_student_data = array_merge($original_student_data, array(
        'student_id'            => $current_student_id,
        'student_name'          => $target_student->stdName,
        'original_student_id'   => $old_student_id,
        'original_student_name' => $source_student_name,
        'transferred_at'        => current_time('mysql'),
        'transfer_type'         => 'claim',
        'transfer_note'         => $note,
        'claimed_wrong_class'   => $wrong_class,
        'claimed_wrong_section' => $wrong_section,
        'claimed_wrong_roll'    => $wrong_roll,
        'claimed_wrong_year'    => $wrong_year,
    ));

    // Tag fee_data
    $fee_data = json_decode($transaction['fee_data'], true) ?: array();
    if (!empty($fee_data)) {
        $fee_data['transferred_from'] = $old_student_id;
        $fee_data['transferred_to']   = $current_student_id;
        $fee_data['transferred_at']   = current_time('mysql');
        $fee_data['transfer_type']    = 'claim';
    }

    // Update the transaction
    $update_data = array(
        'student_id'   => $current_student_id,
        'student_data' => json_encode($new_student_data),
        'fee_data'     => !empty($fee_data) ? json_encode($fee_data) : $transaction['fee_data'],
        'updated_at'   => get_bdt_time(),
    );

    $updated = $wpdb->update(
        $ps_table,
        $update_data,
        array('payment_id' => $transaction_id),
        array('%d', '%s', '%s', '%s'),
        array('%s')
    );

    if ($updated === false) {
        return new WP_Error('db_error', 'Failed to update transaction: ' . $wpdb->last_error, array('status' => 500));
    }

    // ---- Log to payment_transfer_log table ----
    $log_table = $wpdb->prefix . 'payment_transfer_log';
    $current_user = wp_get_current_user();

    // Ensure the log table exists (create if not)
    $log_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$log_table'");
    if (!$log_table_exists) {
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS `$log_table` (
            `id` bigint(20) NOT NULL AUTO_INCREMENT,
            `transfer_type` varchar(50) NOT NULL DEFAULT 'claim',
            `payment_id` varchar(255) NOT NULL,
            `invoice_number` varchar(255) DEFAULT NULL,
            `total_amount` decimal(10,2) DEFAULT NULL,
            `source_student_id` bigint(20) DEFAULT NULL,
            `source_student_name` varchar(255) DEFAULT NULL,
            `source_student_roll` varchar(50) DEFAULT NULL,
            `source_student_class` varchar(255) DEFAULT NULL,
            `source_student_section` varchar(255) DEFAULT NULL,
            `source_student_year` varchar(20) DEFAULT NULL,
            `source_student_facilities` varchar(255) DEFAULT NULL,
            `source_student_data_raw` longtext,
            `target_student_id` bigint(20) DEFAULT NULL,
            `target_student_name` varchar(255) DEFAULT NULL,
            `target_student_roll` varchar(50) DEFAULT NULL,
            `target_student_class` varchar(255) DEFAULT NULL,
            `target_student_section` varchar(255) DEFAULT NULL,
            `target_student_year` varchar(20) DEFAULT NULL,
            `target_student_facilities` varchar(255) DEFAULT NULL,
            `target_student_data_raw` longtext,
            `fee_data_snapshot` longtext,
            `note` text,
            `claimed_transaction_id` varchar(255) DEFAULT NULL,
            `claimed_wrong_class` varchar(255) DEFAULT NULL,
            `claimed_wrong_section` varchar(255) DEFAULT NULL,
            `claimed_wrong_roll` varchar(50) DEFAULT NULL,
            `claimed_wrong_year` varchar(20) DEFAULT NULL,
            `performed_by` bigint(20) DEFAULT NULL,
            `performed_by_name` varchar(255) DEFAULT NULL,
            `created_at` datetime DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `payment_id` (`payment_id`),
            KEY `source_student_id` (`source_student_id`),
            KEY `target_student_id` (`target_student_id`),
            KEY `transfer_type` (`transfer_type`),
            KEY `created_at` (`created_at`)
        ) $charset_collate;";
        $wpdb->query($sql);
    }

    $wpdb->insert($log_table, array(
        'transfer_type'           => 'claim',
        'payment_id'              => $transaction_id,
        'invoice_number'          => $transaction['invoice_number'],
        'total_amount'            => $transaction['total_amount'],
        'source_student_id'       => $old_student_id,
        'source_student_name'     => $source_student_name,
        'source_student_roll'     => $source_student_roll,
        'source_student_class'    => $source_student_class,
        'source_student_section'  => $source_student_section,
        'source_student_year'     => $source_student_year,
        'source_student_facilities' => $source_student_facilities,
        'source_student_data_raw' => $transaction['student_data'],
        'target_student_id'       => $current_student_id,
        'target_student_name'     => $target_student->stdName,
        'target_student_roll'     => $target_student->infoRoll,
        'target_student_class'    => $target_class_name ?: '',
        'target_student_section'  => $target_section_name ?: '',
        'target_student_year'     => $target_student->infoYear ?: $wrong_year,
        'target_student_facilities' => $target_student->facilities,
        'target_student_data_raw' => json_encode($new_student_data),
        'fee_data_snapshot'       => $transaction['fee_data'],
        'note'                    => $note,
        'claimed_transaction_id'  => $transaction_id,
        'claimed_wrong_class'     => $wrong_class ?: '',
        'claimed_wrong_section'   => $wrong_section ?: '',
        'claimed_wrong_roll'      => $wrong_roll,
        'claimed_wrong_year'      => $wrong_year,
        'performed_by'            => get_current_user_id(),
        'performed_by_name'       => $current_user ? $current_user->display_name : '',
        'created_at'              => current_time('mysql'),
    ));

    return array(
        'success'            => true,
        'message'            => sprintf(
            'Payment %s claimed and transferred from %s to %s successfully!',
            $transaction_id,
            $source_student_name,
            $target_student->stdName
        ),
        'payment_id'         => $transaction_id,
        'source_student_id'   => $old_student_id,
        'source_student_name' => $source_student_name,
        'target_student_id'   => $current_student_id,
        'target_student_name' => $target_student->stdName,
    );
}

/**
 * Look up a PayStation transaction by payment_id
 *
 * GET /v1/payment/lookup?transaction_id=xxx
 *
 * @param WP_REST_Request $request
 * @return array|WP_Error Transaction details
 */
function s3s_payment_lookup(WP_REST_Request $request)
{
    global $wpdb;

    $transaction_id = $request->get_param('transaction_id');

    if (empty($transaction_id)) {
        return new WP_Error('missing_param', 'transaction_id is required', array('status' => 400));
    }

    $ps_table = $wpdb->prefix . 'paystation_transactions';

    if (!$wpdb->get_var("SHOW TABLES LIKE '$ps_table'")) {
        return new WP_Error('no_table', 'PayStation transactions table not found', array('status' => 500));
    }

    $transaction = $wpdb->get_row($wpdb->prepare(
        "SELECT payment_id, invoice_number, student_id, total_amount, status, fee_data, student_data, payment_date, created_at
         FROM $ps_table WHERE payment_id = %s",
        $transaction_id
    ), ARRAY_A);

    if (!$transaction) {
        return new WP_Error('not_found', 'No PayStation transaction found with ID: ' . $transaction_id, array('status' => 404));
    }

    // Get current student name for this transaction
    $current_student_name = '';
    $current_student_roll = '';
    if ($transaction['student_id']) {
        $student = $wpdb->get_row($wpdb->prepare(
            "SELECT s.stdName, si.infoRoll FROM ct_student s LEFT JOIN ct_studentinfo si ON si.infoStdid = s.studentid WHERE s.studentid = %d",
            $transaction['student_id']
        ));
        if ($student) {
            $current_student_name = $student->stdName;
            $current_student_roll = $student->infoRoll;
        }
    }

    // Try to get name from student_data if not found
    if (empty($current_student_name)) {
        $sd = json_decode($transaction['student_data'], true);
        if ($sd && isset($sd['student_name'])) {
            $current_student_name = $sd['student_name'];
        }
    }

    return array(
        'success'              => true,
        'transaction'          => array(
            'payment_id'         => $transaction['payment_id'],
            'invoice_number'     => $transaction['invoice_number'],
            'total_amount'       => floatval($transaction['total_amount']),
            'status'             => $transaction['status'],
            'payment_date'       => $transaction['payment_date'] ?: $transaction['created_at'],
            'current_student_id' => $transaction['student_id'],
            'current_student'    => $current_student_name,
            'current_roll'       => $current_student_roll,
        ),
    );
}

/**
 * Check Transaction ID - Verify if a transaction_id already exists in the system
 *
 * GET /v1/payment/check-transaction-id?transaction_id=XXX&exclude_payment_id=YYY
 *
 * @param WP_REST_Request $request
 * @return array|WP_Error
 */
function s3s_payment_check_transaction_id(WP_REST_Request $request)
{
    global $wpdb;

    $transaction_id = $request->get_param('transaction_id');
    $exclude_payment_id = $request->get_param('exclude_payment_id');

    if (empty($transaction_id)) {
        return new WP_Error('missing_param', 'transaction_id is required', array('status' => 400));
    }

    $ps_table = $wpdb->prefix . 'paystation_transactions';

    if (!$wpdb->get_var("SHOW TABLES LIKE '$ps_table'")) {
        return new WP_Error('no_table', 'PayStation transactions table not found', array('status' => 500));
    }

    // Check if any existing row has this transaction_id (excluding the current payment if specified)
    $sql = "SELECT COUNT(*) FROM $ps_table WHERE transaction_id = %s AND transaction_id != ''";
    $params = array($transaction_id);

    if (!empty($exclude_payment_id)) {
        $sql .= " AND payment_id != %s";
        $params[] = $exclude_payment_id;
    }

    $count = $wpdb->get_var($wpdb->prepare($sql, $params));

    return array(
        'success'        => true,
        'exists'         => intval($count) > 0,
        'transaction_id' => $transaction_id,
    );
}
