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

    $table_name = $wpdb->prefix . 'paystation_transactions';

    // Verify the table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    if (!$table_exists) {
        return array(
            'success' => true,
            'message' => 'No PayStation transactions table found',
            'processed' => 0,
            'succeeded' => 0,
            'failed'    => 0,
            'errors'    => [],
            'details'   => []
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
            'details'   => []
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
        'message'   => "Processed {$processed} pending transaction(s): {$succeeded} succeeded, {$failed} failed",
        'processed' => $processed,
        'succeeded' => $succeeded,
        'failed'    => $failed,
        'errors'    => $errors,
        'details'   => $details,
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

        // Check if this record's summary already exists
        $already_synced = s3s_sync_check_exists($sub_head_type, $record);

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
            $result = s3s_sync_insert_exam($record);
        } else {
            $result = array('success' => false, 'message' => 'Unsupported sub-head type: ' . $sub_head_type);
        }

        if ($result['success']) {
            $inserted++;
        } else if (count($errors) < 10) {
            $errors[] = "Info ID {$info_id}, Sub-head {$sub_head_id} (" . $record['sub_head_name'] . '): ' . $result['message'];
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
