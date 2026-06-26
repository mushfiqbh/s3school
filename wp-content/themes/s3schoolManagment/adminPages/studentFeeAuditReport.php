<?php
/**
* Template Name: Student Fee Audit Report
*/
// Restrict access to logged-in users only
if (!is_user_logged_in()) {
    auth_redirect();
}

global $wpdb;
global $monthlyFeeSubHeadId, $transportFeeSubHeadId, $coachingFeeSubHeadId;
global $admissionFeeSubHeadId, $admissionFormSubHeadId, $registrationFeeSubHeadId;
global $ictFeeSubHeadId, $idcardSubHeadId, $dairySubHeadId, $examFeeSubHeadId;

function getClassNameById($id){
	global $wpdb;
	$name_qry = "SELECT className FROM ct_class WHERE classid = $id";
	$name = $wpdb->get_results( $name_qry );
	return @$name[0]->className;
}

function getSectionNameById($id){
	global $wpdb;
	if($id){
		$name_qry = "SELECT sectionName FROM ct_section WHERE sectionid = $id";
		$name = $wpdb->get_results( $name_qry );
		return @$name[0]->sectionName;
	}else{
		return null;
	}
}

// Get filter values
$filter_class   = isset($_POST['stdclass']) ? intval($_POST['stdclass']) : (isset($_GET['class_id']) ? intval($_GET['class_id']) : 0);
$filter_sec     = isset($_POST['sec']) ? intval($_POST['sec']) : (isset($_GET['section']) ? intval($_GET['section']) : 0);
$filter_group   = isset($_POST['group']) ? intval($_POST['group']) : (isset($_GET['group_id']) ? intval($_GET['group_id']) : 0);
$filter_year    = isset($_POST['stdyear']) ? $_POST['stdyear'] : (isset($_GET['year']) ? $_GET['year'] : current_time('Y'));
$filter_status  = isset($_POST['payment_status']) ? $_POST['payment_status'] : (isset($_GET['payment_status']) ? $_GET['payment_status'] : '');
$selected_student = isset($_GET['student_id']) ? intval($_GET['student_id']) : (isset($_POST['student_id']) ? intval($_POST['student_id']) : 0);

// Get sub-heads grouped by type
$allSubHeads = $wpdb->get_results("SELECT * FROM ct_sub_head WHERE active_for_collection = 1 AND relation_to = 1 AND isHidden IS NULL ORDER BY sort_order ASC, sub_head_name ASC");
$yearlySubHeads = array(); // type=2
$monthlySubHeads = array(); // type=1
$examSubHeads = array(); // type=3
foreach ($allSubHeads as $sh) {
    if ($sh->type == 1) $monthlySubHeads[] = $sh;
    elseif ($sh->type == 2) $yearlySubHeads[] = $sh;
    elseif ($sh->type == 3) $examSubHeads[] = $sh;
}
$otherSubHeads = $wpdb->get_results("SELECT * FROM ct_sub_head WHERE active_for_collection = 1 AND relation_to = 1 AND type = 4 AND isHidden IS NULL ORDER BY sort_order ASC, sub_head_name ASC");
$monthNames = array(1=>"January","February","March","April","May","June","July","August","September","October","November","December");

// Build student query
$student_query = "SELECT si.infoStdid, si.infoRoll, s.stdName, s.facilities
                    FROM ct_studentinfo si
                    LEFT JOIN ct_student s ON s.studentid = si.infoStdid
                    WHERE si.infoClass = " . intval($filter_class) . " AND si.infoYear = '" . esc_sql($filter_year) . "'";
if ($filter_sec) $student_query .= " AND si.infoSection = " . intval($filter_sec);
if ($filter_group) $student_query .= " AND si.infoGroup = " . intval($filter_group);
$student_query .= " ORDER BY si.infoRoll ASC";
$students = $filter_class ? $wpdb->get_results($student_query) : array();

// Get selected student info
$studentInfo = null;
$studentYearlyFees = array();
$studentMonthlyFees = array();
$studentExamFees = array();
$studentOtherFees = array();
$paystationTxns = array();
$allPaystationTxns = array();
$collectionRecords = array();
$total_paid = 0;
$total_expected = 0;
$total_due = 0;
$payment_pct = 0;
$potentialMismatches = array();
$mismatch_high_count = 0;
$mismatch_medium_count = 0;
$mismatch_low_count = 0;
$mismatch_high_amount = 0;

if ($selected_student && $filter_class && $filter_year) {
    $studentInfo = $wpdb->get_row($wpdb->prepare(
        "SELECT s.*, si.infoRoll, si.infoClass, si.infoSection, si.infoGroup
            FROM ct_student s
            LEFT JOIN ct_studentinfo si ON si.infoStdid = s.studentid AND si.infoClass = %d AND si.infoYear = %s
            WHERE s.studentid = %d",
        $filter_class, $filter_year, $selected_student
    ));

    // Yearly fee summary
    foreach ($yearlySubHeads as $sh) {
        $fee_row = $wpdb->get_row($wpdb->prepare(
            "SELECT fee, date, notes FROM ct_student_yearly_fee_summary
                WHERE student_id = %d AND sub_head_id = %d AND class_id = %d AND year = %s LIMIT 1",
            $selected_student, $sh->id, $filter_class, $filter_year
        ));
        $studentYearlyFees[$sh->id] = array(
            'name'  => $sh->sub_head_name,
            'fee'   => $fee_row ? floatval($fee_row->fee) : 0,
            'paid'  => $fee_row ? true : false,
            'date'  => $fee_row ? $fee_row->date : null,
            'notes' => $fee_row ? $fee_row->notes : '',
        );
    }

    // Get expected monthly fee amount from fee list
    $monthlyExpectedFee = 0;
    foreach ($monthlySubHeads as $sh) {
        $base = $wpdb->get_var($wpdb->prepare(
            "SELECT fee FROM ct_student_fee_list WHERE sub_head_id = %d AND class_id = %d AND year = %s ORDER BY id DESC LIMIT 1",
            $sh->id, $filter_class, $filter_year
        ));
        if ($base) $monthlyExpectedFee = floatval($base);
        break; // use first (tuition)
    }

    // Monthly fee summary (per month)
    for ($m = 1; $m <= 12; $m++) {
        $monthData = array('month' => $m, 'name' => $monthNames[$m], 'items' => array(), 'total_paid' => 0);
        foreach ($monthlySubHeads as $sh) {
            $fee_row = $wpdb->get_row($wpdb->prepare(
                "SELECT fee, date, notes FROM ct_student_monthly_fee_summary
                    WHERE student_id = %d AND sub_head_id = %d AND class_id = %d AND year = %s AND month = %d LIMIT 1",
                $selected_student, $sh->id, $filter_class, $filter_year, $m
            ));
            $amt = $fee_row ? floatval($fee_row->fee) : 0;
            $monthData['items'][$sh->id] = array(
                'name'  => $sh->sub_head_name,
                'fee'   => $amt,
                'paid'  => $fee_row ? true : false,
                'date'  => $fee_row ? $fee_row->date : null,
            );
            $monthData['total_paid'] += $amt;
        }
        $studentMonthlyFees[$m] = $monthData;
    }

    // Exam fee summary — show ALL exam × sub_head entries (paid or unpaid)
    // First get expected fee per exam sub_head from fee list
    $exam_fee_expected = array();
    $exam_fee_list_rows = $wpdb->get_results($wpdb->prepare(
        "SELECT fl.sub_head_id, fl.fee FROM ct_student_fee_list fl
        INNER JOIN (
            SELECT sub_head_id, MAX(id) AS max_id FROM ct_student_fee_list
            WHERE class_id = %d AND year = %s AND sub_head_id IN (SELECT id FROM ct_sub_head WHERE type = 3 AND active_for_collection = 1)
            GROUP BY sub_head_id
        ) latest ON latest.max_id = fl.id",
        $filter_class, $filter_year
    ));
    foreach ($exam_fee_list_rows as $r) {
        $exam_fee_expected[$r->sub_head_id] = floatval($r->fee);
    }
    // Build paid lookup from exam_fee_summary: sub_head_id + exam_id => row
    $exam_paid_map = array();
    $paid_exam_rows = $wpdb->get_results($wpdb->prepare(
        "SELECT sub_head_id, exam_id, fee, date, notes FROM ct_student_exam_fee_summary
            WHERE student_id = %d AND class_id = %d AND year = %s AND fee > 0",
        $selected_student, $filter_class, $filter_year
    ));
    foreach ($paid_exam_rows as $pr) {
        $key = $pr->sub_head_id . '|' . $pr->exam_id;
        $exam_paid_map[$key] = $pr;
    }
    // Also check collection records for exam-type sub_head payments (fallback for
    // payments that didn't sync to exam_fee_summary, e.g. if active_for_collection wasn't set)
    $collection_exam_sub_head_ids = array();
    $coll_exam_rows = $wpdb->get_results($wpdb->prepare(
        "SELECT cd.sub_head_id, SUM(cd.fee) as total_fee
        FROM ct_student_fee_collection_info ci
        INNER JOIN ct_student_fee_collection_details cd ON cd.info_id = ci.id
        INNER JOIN ct_sub_head sh ON sh.id = cd.sub_head_id
        WHERE ci.student_id = %d AND ci.year = %s AND sh.type = 3 AND cd.fee > 0
        GROUP BY cd.sub_head_id",
        $selected_student, $filter_year
    ));
    foreach ($coll_exam_rows as $cr) {
        $collection_exam_sub_head_ids[$cr->sub_head_id] = floatval($cr->total_fee);
    }
    // Get the active_for_collection exam_id to map collection payments to an exam
    $active_exam_for_collection = $wpdb->get_var($wpdb->prepare(
        "SELECT examid FROM ct_exam WHERE active_for_collection = 1 AND examClass = %d LIMIT 1",
        $filter_class
    ));
    // Get exams: include those that are visible, have payments, or are active for collection
    $exams = $wpdb->get_results($wpdb->prepare(
        "SELECT e.examid, e.examName FROM ct_exam e
        WHERE e.examClass = %d AND (e.examsirial != 0 OR e.examid IN (
            SELECT DISTINCT exam_id FROM ct_student_exam_fee_summary
            WHERE class_id = %d AND year = %s AND fee > 0
        ) OR e.active_for_collection = 1)
        ORDER BY e.examsirial",
        $filter_class, $filter_class, $filter_year
    ));
    foreach ($exams as $exam) {
        foreach ($examSubHeads as $sh) {
            $key = $sh->id . '|' . $exam->examid;
            $paid_record = isset($exam_paid_map[$key]) ? $exam_paid_map[$key] : null;
            $expected = isset($exam_fee_expected[$sh->id]) ? $exam_fee_expected[$sh->id] : 0;
            // Fallback: if no exam_fee_summary record but collection has payment for
            // this sub_head, mark the active_for_collection exam row as paid
            $is_paid_from_collection = !$paid_record
                && isset($collection_exam_sub_head_ids[$sh->id])
                && $active_exam_for_collection
                && intval($exam->examid) === intval($active_exam_for_collection);
            $studentExamFees[] = array(
                'exam_name' => $exam->examName,
                'item_name' => $sh->sub_head_name,
                'fee'       => $paid_record ? floatval($paid_record->fee) : ($is_paid_from_collection ? $collection_exam_sub_head_ids[$sh->id] : $expected),
                'paid'      => $paid_record ? true : $is_paid_from_collection,
                'date'      => $paid_record ? $paid_record->date : null,
                'notes'     => $paid_record ? $paid_record->notes : ($is_paid_from_collection ? 'Paid via collection' : ''),
            );
        }
    }

    // Other fees (type=4)
    $studentOtherFees = array();
    foreach ($otherSubHeads as $sh) {
        $fee_row = $wpdb->get_row($wpdb->prepare(
            "SELECT fee, date, notes FROM ct_student_yearly_fee_summary
                WHERE student_id = %d AND sub_head_id = %d AND class_id = %d AND year = %s LIMIT 1",
            $selected_student, $sh->id, $filter_class, $filter_year
        ));
        $studentOtherFees[$sh->id] = array(
            'name'  => $sh->sub_head_name,
            'fee'   => $fee_row ? floatval($fee_row->fee) : 0,
            'paid'  => $fee_row ? true : false,
            'date'  => $fee_row ? $fee_row->date : null,
        );
    }

    // PayStation transactions for this student — linked via transaction_id in collection info
    $ps_table = $wpdb->prefix . 'paystation_transactions';
    $ps_exists = $wpdb->get_var("SHOW TABLES LIKE '$ps_table'");
    if ($ps_exists) {
        $paystationTxns = $wpdb->get_results($wpdb->prepare(
            "SELECT ps.*
            FROM {$ps_table} ps
            INNER JOIN ct_student_fee_collection_info ci ON ci.transaction_id = ps.transaction_id
            WHERE ci.student_id = %d AND ci.year = %s
            ORDER BY ps.created_at DESC
            LIMIT 50",
            $selected_student, $filter_year
        ), ARRAY_A);
    }

    // Other PayStation transactions — linked to this student via transaction_id but NOT in the current selected year
    // These are shown for reference only and are NOT included in calculations
    $allPaystationTxns = array();
    if ($ps_exists) {
        $allPaystationTxns = $wpdb->get_results($wpdb->prepare(
            "SELECT ps.*
            FROM {$ps_table} ps
            WHERE ps.student_id = %d
            ORDER BY ps.created_at DESC
            LIMIT 50",
            $selected_student, $selected_student, $filter_year
        ), ARRAY_A);
    }

    // Collection records (manual/online payments) — info only, no details breakdown
    $collectionRecords = $wpdb->get_results($wpdb->prepare(
        "SELECT ci.id, ci.year, ci.month, ci.sub_total, ci.remission, ci.total, ci.date, ci.payment_method, ci.transaction_id, ci.created_by
            FROM ct_student_fee_collection_info ci
            WHERE ci.student_id = %d AND ci.year = %s
            ORDER BY ci.date DESC, ci.id DESC
            LIMIT 100",
        $selected_student, $filter_year
    ), ARRAY_A);
}

// ===== Compute Fund Statistics for selected student =====
$fundStats = array(
    'yearly_expected' => 0,
    'monthly_expected' => 0, 'monthly_paid' => 0,
    'other_paid' => 0,
    'collection_total' => 0,
    'paystation_total' => 0,
    'paystation_paid_total' => 0,
    'over_payment' => 0,
);

if ($selected_student && $filter_class && $filter_year) {
    // Yearly fees from already-collected data
    $yearly_paid = 0;
    foreach ($studentYearlyFees as $sid => $yf) {
        $fundStats['yearly_expected'] += $yf['fee'];
        if ($yf['paid']) $yearly_paid += $yf['fee'];
    }

    // Monthly fees from already-collected data
    for ($m = 1; $m <= 12; $m++) {
        foreach ($studentMonthlyFees[$m]['items'] as $sh_id => $item) {
            $fundStats['monthly_expected'] += $item['fee'];
            if ($item['paid']) $fundStats['monthly_paid'] += $item['fee'];
        }
    }

    // Expected monthly from fee list
    $monthly_expected_from_list = 0;
    foreach ($monthlySubHeads as $sh) {
        $base = $wpdb->get_var($wpdb->prepare(
            "SELECT fee FROM ct_student_fee_list WHERE sub_head_id = %d AND class_id = %d AND year = %s ORDER BY id DESC LIMIT 1",
            $sh->id, $filter_class, $filter_year
        ));
        if ($base) $monthly_expected_from_list += floatval($base) * 12;
    }
    if ($monthly_expected_from_list > $fundStats['monthly_expected']) {
        $fundStats['monthly_expected'] = $monthly_expected_from_list;
    }

    // Expected yearly fees from fee list (fallback)
    $yearly_expected_from_list = 0;
    foreach ($yearlySubHeads as $sh) {
        $base = $wpdb->get_var($wpdb->prepare(
            "SELECT fee FROM ct_student_fee_list WHERE sub_head_id = %d AND class_id = %d AND year = %s ORDER BY id DESC LIMIT 1",
            $sh->id, $filter_class, $filter_year
        ));
        if ($base) $yearly_expected_from_list += floatval($base);
    }
    if ($yearly_expected_from_list > $fundStats['yearly_expected']) {
        $fundStats['yearly_expected'] = $yearly_expected_from_list;
    }

    // Expected exam fees from fee list (per-exam fee * number of exams)
    $exam_expected = 0;
    $exam_fee_rows = $wpdb->get_results($wpdb->prepare(
        "SELECT fl.fee FROM ct_student_fee_list fl
        INNER JOIN (
            SELECT sub_head_id, MAX(id) AS max_id FROM ct_student_fee_list
            WHERE class_id = %d AND year = %s AND sub_head_id IN (SELECT id FROM ct_sub_head WHERE type = 3 AND active_for_collection = 1)
            GROUP BY sub_head_id
        ) latest ON latest.max_id = fl.id",
        $filter_class, $filter_year
    ));
    foreach ($exam_fee_rows as $r) { $exam_expected += floatval($r->fee); }
    // Multiply by number of exams
    $exam_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM ct_exam WHERE examClass = %d AND examsirial != 0",
        $filter_class
    ));
    $exam_expected = $exam_expected * max(1, intval($exam_count));

    // Exam fees
    $exam_paid = 0;
    foreach ($studentExamFees as $ef) {
        if ($ef['paid']) $exam_paid += $ef['fee'];
    }

    // Other fees from already-collected data
    foreach ($studentOtherFees as $of) {
        if ($of['paid']) $fundStats['other_paid'] += $of['fee'];
    }

    // Total from collection records (using info.total instead of sum of details)
    foreach ($collectionRecords as $cr) {
        $fundStats['collection_total'] += floatval($cr['total']);
    }

    // PayStation totals
    foreach ($paystationTxns as $txn) {
        $amt = floatval($txn['total_amount']);
        $fundStats['paystation_total'] += $amt;
        if ($txn['status'] === 'paid') {
            $fundStats['paystation_paid_total'] += $amt;
        }
    }

    // Compute overall totals (needed before mismatch detection)
    $total_paid = $yearly_paid + $fundStats['monthly_paid'] + $exam_paid + $fundStats['other_paid'];
    $total_expected = $fundStats['yearly_expected'] + $fundStats['monthly_expected'] + $exam_expected;
    $total_due = max(0, $total_expected - $total_paid);
    $payment_pct = $total_expected > 0 ? round(($total_paid / $total_expected) * 100) : 0;

    // Over payment = PayStation paid exceeds what's expected
    $fundStats['over_payment'] = max(0, $fundStats['paystation_paid_total'] - $total_expected);

    // ===== Mismatch Detection =====
    $potentialMismatches = array();

    // 1. Duplicate payment detection by invoice_number
    $invoice_counts = array();
    foreach ($paystationTxns as $txn) {
        $inv = $txn['invoice_number'];
        if (!empty($inv)) {
            $invoice_counts[$inv][] = $txn;
        }
    }
    foreach ($invoice_counts as $inv => $txns) {
        if (count($txns) > 1) {
            $total_amt = 0;
            foreach ($txns as $t) { $total_amt += floatval($t['total_amount']); }
            $potentialMismatches[] = array(
                'type' => 'DUPLICATE_PAYMENT',
                'severity' => 'high',
                'title' => "Duplicate Invoice: {$inv}",
                'description' => sprintf('Invoice %s has %d payments totalling %.2f', $inv, count($txns), $total_amt),
                'count' => count($txns),
                'amount' => $total_amt,
            );
        }
    }

    // 2. Overpayment detection
    // Check if total collected (from summary tables) exceeds total expected
    $overpayment_amount = 0;
    $overpayment_source = '';
    if ($total_expected > 0) {
        // Check summary-based overpayment (collected more than expected)
        if ($total_paid > $total_expected) {
            $overpayment_amount = $total_paid - $total_expected;
            $overpayment_source = sprintf('Collection/Summary paid (%.2f) exceeds expected total (%.2f) by %.2f', $total_paid, $total_expected, $overpayment_amount);
        }
        // Also check PayStation paid vs total collected
        $paystation_paid = $fundStats['paystation_paid_total'];
        if ($paystation_paid > $total_paid && $paystation_paid > $total_expected) {
            $ps_over = $paystation_paid - max($total_paid, $total_expected);
            if ($ps_over > $overpayment_amount) {
                $overpayment_amount = $ps_over;
                $overpayment_source = sprintf('PayStation paid (%.2f) exceeds collected (%.2f) by %.2f — payments may not be fully reconciled', $paystation_paid, $total_paid, $ps_over);
            }
        }
    }
    if ($overpayment_amount > 0) {
        $potentialMismatches[] = array(
            'type' => 'OVERPAYMENT',
            'severity' => 'medium',
            'title' => 'Possible Overpayment',
            'description' => $overpayment_source,
            'count' => 1,
            'amount' => $overpayment_amount,
        );
    }

    // 3. Pending / failed PayStation transactions that have no collection record
    $pending_count = 0;
    foreach ($paystationTxns as $txn) {
        if ($txn['status'] !== 'paid' && $txn['status'] !== 'cancelled') {
            $pending_count++;
        }
    }
    if ($pending_count > 0) {
        $potentialMismatches[] = array(
            'type' => 'UNIDENTIFIED_PAYMENT',
            'severity' => 'low',
            'title' => 'Pending / Incomplete PayStation Transactions',
            'description' => "{$pending_count} transaction(s) are in pending, failed, or unknown status — may require manual review.",
            'count' => $pending_count,
            'amount' => 0,
        );
    }

    // 4. Unmatched PayStation payments (paid but no collection linked)
    //    Check by transaction_id instead of invoice_number
    $collection_txn_ids = array();
    foreach ($collectionRecords as $cr) {
        if (!empty($cr['transaction_id'])) {
            $collection_txn_ids[$cr['transaction_id']] = true;
        }
    }
    $unmatched_count = 0;
    foreach ($paystationTxns as $txn) {
        if ($txn['status'] === 'paid' && !empty($txn['transaction_id']) && !isset($collection_txn_ids[$txn['transaction_id']])) {
            $unmatched_count++;
        }
    }
    if ($unmatched_count > 0) {
        $potentialMismatches[] = array(
            'type' => 'UNIDENTIFIED_PAYMENT',
            'severity' => 'high',
            'title' => 'Paid Transactions Not Linked to Collection',
            'description' => "{$unmatched_count} paid PayStation invoice(s) have no matching fee collection record — possible wrong-account payment.",
            'count' => $unmatched_count,
            'amount' => 0,
        );
    }
}

// PayStation mismatch alert counts
$mismatch_high_count = 0;
$mismatch_medium_count = 0;
$mismatch_low_count = 0;
$mismatch_high_amount = 0;
if (!empty($potentialMismatches)) {
    foreach ($potentialMismatches as $mm) {
        if ($mm['severity'] === 'high') {
            $mismatch_high_count += $mm['count'];
            $mismatch_high_amount += $mm['amount'];
        } elseif ($mm['severity'] === 'medium') {
            $mismatch_medium_count += $mm['count'];
        } else {
            $mismatch_low_count += $mm['count'];
        }
    }
}

// ===== Active tab =====
$active_tab = isset($_GET['tab']) && in_array($_GET['tab'], array('due', 'audit', 'mismatches')) ? $_GET['tab'] : 'due';

// ===== Claim mismatch handler =====
$claim_message = '';
$claim_it_message = '';
$claim_it_error = '';
$refund_message = '';
$refund_error = '';
if (isset($_GET['claim_done']) && $_GET['claim_done'] === '1') {
    $claim_message = 'Claim submitted successfully.';
}
if (isset($_GET['claim_it_done']) && $_GET['claim_it_done'] === '1') {
    $claim_it_message = 'PayStation payment claimed, marked as paid, and added to collection successfully!';
}
if (isset($_GET['claim_it_error']) && $_GET['claim_it_error'] === '1') {
    $claim_it_error = 'Failed to claim the PayStation payment. Please check that the transaction exists and try again.';
}
if (isset($_GET['refund_done']) && $_GET['refund_done'] === '1') {
    $refund_message = 'Payment refunded successfully. Transaction status set to refunded and collection records removed.';
}
if (isset($_GET['refund_error']) && $_GET['refund_error'] === '1') {
    $refund_error = 'Failed to process refund. Please check that the transaction exists and try again.';
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'claim_mismatch') {
    if (!empty($_POST['mismatch_id']) && !empty($_POST['claim_reason'])) {
        $mismatch_id = intval($_POST['mismatch_id']);
        $claim_reason = sanitize_text_field($_POST['claim_reason']);
        $mismatch = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM ct_payment_mismatch WHERE mismatch_id = %d", $mismatch_id
        ));
        if ($mismatch) {
            $wpdb->insert('ct_mismatch_claim', array(
                'mismatch_id' => $mismatch_id,
                'claim_student_id' => $mismatch->student_id,
                'claim_reason' => $claim_reason,
                'status' => 'PENDING',
                'submitted_at' => current_time('mysql'),
            ));
            $wpdb->update('ct_payment_mismatch', array('status' => 'CLAIMED'), array('mismatch_id' => $mismatch_id));
            $claim_message = 'Claim submitted successfully.';
        }
        wp_redirect(add_query_arg(array('tab' => 'mismatches', 'claim_done' => '1')));
        exit;
    }
}

// ===== Claim It Payment handler (mark PayStation txn as paid + add to collection) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'claim_it_payment') {
    $ps_table = $wpdb->prefix . 'paystation_transactions';
    $payment_id = sanitize_text_field($_POST['payment_id']);
    $transaction_id = sanitize_text_field($_POST['transaction_id']);
    $claim_month = intval($_POST['claim_month']);
    $claim_year = sanitize_text_field($_POST['claim_year']);

    if ($payment_id && !empty($transaction_id) && $claim_year) {
        $txn = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$ps_table} WHERE payment_id = %s", $payment_id
        ));

        if ($txn && $txn->student_id) {
            $student_id = $txn->student_id;
            $fee_data = json_decode($txn->fee_data, true);
            $total_amount = floatval($txn->total_amount);

            // Update PayStation transaction status to paid
            $wpdb->update(
                $ps_table,
                array(
                    'transaction_id' => $transaction_id,
                    'status' => 'paid',
                    'payment_date' => current_time('mysql'),
                ),
                array('payment_id' => $payment_id)
            );

            // Get student info for class/section/group
            $student_info = $wpdb->get_row($wpdb->prepare(
                "SELECT si.infoStdid, si.infoClass, si.infoSection, si.infoGroup, si.infoRoll
                 FROM ct_studentinfo si
                 WHERE si.infoStdid = %d AND si.infoYear = %s",
                $student_id, $claim_year
            ));

            // Insert collection info record
            $info_data = array(
                'student_id' => $student_id,
                'student_roll' => $student_info ? $student_info->infoRoll : 0,
                'year' => $claim_year,
                'month' => ($claim_month > 0 && $claim_month <= 12) ? $claim_month : null,
                'class_id' => $student_info ? $student_info->infoClass : 0,
                'section' => $student_info ? $student_info->infoSection : null,
                'group_id' => $student_info ? $student_info->infoGroup : null,
                'sub_total' => $total_amount,
                'remission' => 0,
                'total' => $total_amount,
                'status' => 1,
                'notes' => 'Claimed from PayStation (payment_id: ' . $payment_id . ')',
                'date' => current_time('mysql'),
                'created_by' => get_current_user_id(),
                'created_at' => current_time('mysql'),
                'payment_method' => 'PayStation',
                'payment_id' => $payment_id,
                'transaction_id' => $transaction_id,
            );
            $wpdb->insert('ct_student_fee_collection_info', $info_data);
            $info_id = $wpdb->insert_id;

            // Insert collection details from fee_data (per-month rows for monthly fees)
            // Also insert into summary tables
            $has_details = false;
            if ($fee_data && is_array($fee_data)) {
                // Get the class_id for fee lookup
                $class_id_for_fee = $student_info ? $student_info->infoClass : 0;

                foreach ($fee_data as $fee_item) {
                    $sub_head_id = intval($fee_item['sub_head_id'] ?? $fee_item['id'] ?? 0);
                    $fee_amount = floatval($fee_item['amount'] ?? $fee_item['fee'] ?? $fee_item['total'] ?? 0);
                    $fee_type = $fee_item['fee_type'] ?? '';
                    $exam_id = intval($fee_item['exam_id'] ?? 0);

                    // Infer fee_type from sub_head type if not provided
                    if (!$fee_type && $sub_head_id) {
                        $sh_type = $wpdb->get_var($wpdb->prepare(
                            "SELECT type FROM ct_sub_head WHERE id = %d", $sub_head_id
                        ));
                        if ($sh_type == 1) $fee_type = 'monthly';
                        elseif ($sh_type == 2) $fee_type = 'yearly';
                        elseif ($sh_type == 3) $fee_type = 'exam';
                    }

                    if ($sub_head_id && $fee_amount > 0) {
                        if ($fee_type === 'monthly' && $claim_month > 0) {
                            // Monthly: insert per-month collection_details + monthly_summary rows
                            $base_fee = $wpdb->get_var($wpdb->prepare(
                                "SELECT fee FROM ct_student_fee_list 
                                 WHERE sub_head_id = %d AND class_id = %d AND year = %s 
                                 ORDER BY id DESC LIMIT 1",
                                $sub_head_id, $class_id_for_fee, $claim_year
                            ));
                            $base_fee = $base_fee ? floatval($base_fee) : 0;
                            $fee_per_month = $base_fee > 0 ? $base_fee : ($fee_amount / $claim_month);

                            for ($m = $claim_month; $m >= 1; $m--) {
                                $month_fee = round($fee_per_month, 2);
                                $wpdb->insert('ct_student_fee_collection_details', array(
                                    'info_id' => $info_id,
                                    'sub_head_id' => $sub_head_id,
                                    'month' => $m,
                                    'fee' => $month_fee,
                                    'status' => 1,
                                    'date' => current_time('mysql'),
                                    'created_by' => get_current_user_id(),
                                    'created_at' => current_time('mysql'),
                                ));
                                $wpdb->insert('ct_student_monthly_fee_summary', array(
                                    'student_id' => $student_id,
                                    'year' => $claim_year,
                                    'month' => $m,
                                    'class_id' => $class_id_for_fee,
                                    'section' => $student_info ? $student_info->infoSection : null,
                                    'group_id' => $student_info ? $student_info->infoGroup : null,
                                    'info_id' => $info_id,
                                    'sub_head_id' => $sub_head_id,
                                    'fee' => $month_fee,
                                    'status' => 1,
                                    'notes' => '',
                                    'date' => current_time('mysql'),
                                    'created_by' => get_current_user_id(),
                                    'created_at' => current_time('mysql'),
                                ));
                            }
                        } elseif ($fee_type === 'yearly') {
                            // Yearly: single collection_details row + yearly_summary
                            $wpdb->insert('ct_student_fee_collection_details', array(
                                'info_id' => $info_id,
                                'sub_head_id' => $sub_head_id,
                                'fee' => $fee_amount,
                                'status' => 1,
                                'date' => current_time('mysql'),
                                'created_by' => get_current_user_id(),
                                'created_at' => current_time('mysql'),
                            ));
                            $wpdb->insert('ct_student_yearly_fee_summary', array(
                                'student_id' => $student_id,
                                'year' => $claim_year,
                                'class_id' => $class_id_for_fee,
                                'section' => $student_info ? $student_info->infoSection : null,
                                'group_id' => $student_info ? $student_info->infoGroup : null,
                                'info_id' => $info_id,
                                'sub_head_id' => $sub_head_id,
                                'fee' => $fee_amount,
                                'status' => 1,
                                'notes' => 'Yearly Summary (Claimed)',
                                'date' => current_time('mysql'),
                                'created_by' => get_current_user_id(),
                                'created_at' => current_time('mysql'),
                            ));
                        } elseif ($fee_type === 'exam') {
                            // Exam: single collection_details row + exam_summary
                            $wpdb->insert('ct_student_fee_collection_details', array(
                                'info_id' => $info_id,
                                'sub_head_id' => $sub_head_id,
                                'fee' => $fee_amount,
                                'status' => 1,
                                'date' => current_time('mysql'),
                                'exam_id' => $exam_id ?: null,
                                'created_by' => get_current_user_id(),
                                'created_at' => current_time('mysql'),
                            ));
                            $active_exam_id = $exam_id ?: $wpdb->get_var($wpdb->prepare(
                                "SELECT examid FROM ct_exam WHERE active_for_collection = 1 AND examClass = %d LIMIT 1",
                                $class_id_for_fee
                            ));
                            if ($active_exam_id) {
                                $wpdb->insert('ct_student_exam_fee_summary', array(
                                    'student_id' => $student_id,
                                    'year' => $claim_year,
                                    'class_id' => $class_id_for_fee,
                                    'section' => $student_info ? $student_info->infoSection : null,
                                    'group_id' => $student_info ? $student_info->infoGroup : null,
                                    'info_id' => $info_id,
                                    'exam_id' => $active_exam_id,
                                    'sub_head_id' => $sub_head_id,
                                    'fee' => $fee_amount,
                                    'status' => 1,
                                    'notes' => 'Exam Fee Collection (Claimed)',
                                    'date' => current_time('mysql'),
                                    'created_by' => get_current_user_id(),
                                    'created_at' => current_time('mysql'),
                                ));
                            }
                        } else {
                            // Unknown type: single detail row
                            $wpdb->insert('ct_student_fee_collection_details', array(
                                'info_id' => $info_id,
                                'sub_head_id' => $sub_head_id,
                                'fee' => $fee_amount,
                                'status' => 1,
                                'date' => current_time('mysql'),
                                'exam_id' => $exam_id ?: null,
                                'created_by' => get_current_user_id(),
                                'created_at' => current_time('mysql'),
                            ));
                        }
                        $has_details = true;
                    }
                }
            }

            if (!$has_details) {
                // Fallback: insert total as a single detail row
                $wpdb->insert('ct_student_fee_collection_details', array(
                    'info_id' => $info_id,
                    'sub_head_id' => 0,
                    'fee' => $total_amount,
                    'status' => 1,
                    'date' => current_time('mysql'),
                    'created_by' => get_current_user_id(),
                    'created_at' => current_time('mysql'),
                ));
            }

            wp_redirect(add_query_arg(array('tab' => 'audit', 'claim_it_done' => '1')));
            exit;
        }
    }
    wp_redirect(add_query_arg(array('tab' => 'audit', 'claim_it_error' => '1')));
    exit;
}

// ===== Refund Payment handler =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'refund_payment') {
    $ps_table = $wpdb->prefix . 'paystation_transactions';
    $payment_id = sanitize_text_field($_POST['payment_id']);

    if ($payment_id) {
        $txn = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$ps_table} WHERE payment_id = %s", $payment_id
        ));

        if ($txn && $txn->transaction_id) {
            $transaction_id = $txn->transaction_id;

            // Update PayStation transaction status to refunded
            $wpdb->update(
                $ps_table,
                array('status' => 'refunded'),
                array('payment_id' => $payment_id)
            );

            // Find and remove collection info records matching this transaction_id
            $info_records = $wpdb->get_results($wpdb->prepare(
                "SELECT info_id FROM ct_student_fee_collection_info WHERE transaction_id = %s",
                $transaction_id
            ));

            if ($info_records) {
                $info_ids = array();
                foreach ($info_records as $rec) {
                    $info_ids[] = intval($rec->info_id);
                }
                $info_ids_str = implode(',', $info_ids);

                // Delete collection details for these info_ids
                $wpdb->query("DELETE FROM ct_student_fee_collection_details WHERE info_id IN ({$info_ids_str})");

                // Also delete summary table records linked to these info_ids
                $wpdb->query("DELETE FROM ct_student_monthly_fee_summary WHERE info_id IN ({$info_ids_str})");
                $wpdb->query("DELETE FROM ct_student_yearly_fee_summary WHERE info_id IN ({$info_ids_str})");
                $wpdb->query("DELETE FROM ct_student_exam_fee_summary WHERE info_id IN ({$info_ids_str})");

                // Delete collection info records
                $wpdb->delete('ct_student_fee_collection_info', array('transaction_id' => $transaction_id));
            }

            wp_redirect(add_query_arg(array('tab' => 'audit', 'refund_done' => '1')));
            exit;
        }
    }
    wp_redirect(add_query_arg(array('tab' => 'audit', 'refund_error' => '1')));
    exit;
}

// ===== Compute due students for a class =====
function computeDueStudents($class_id, $section, $group, $year) {
    global $wpdb;
    $query = "SELECT si.infoStdid, si.infoRoll, s.stdName, s.facilities FROM ct_studentinfo si LEFT JOIN ct_student s ON s.studentid = si.infoStdid WHERE si.infoClass = " . intval($class_id) . " AND si.infoYear = '" . esc_sql($year) . "'";
    if ($section) $query .= " AND si.infoSection = " . intval($section);
    if ($group) $query .= " AND si.infoGroup = " . intval($group);
    $query .= " ORDER BY si.infoRoll ASC";
    $students = $wpdb->get_results($query);
    if (empty($students)) return array();

    // Get latest fee per sub_head from fee list (avoid summing historical entries when fees changed)
    $yearly_expected = 0;
    $yearly_rows = $wpdb->get_results($wpdb->prepare(
        "SELECT fl.fee FROM ct_student_fee_list fl
        INNER JOIN (
            SELECT sub_head_id, MAX(id) AS max_id FROM ct_student_fee_list
            WHERE class_id = %d AND year = %s AND sub_head_id IN (SELECT id FROM ct_sub_head WHERE type = 2 AND active_for_collection = 1)
            GROUP BY sub_head_id
        ) latest ON latest.max_id = fl.id",
        $class_id, $year
    ));
    foreach ($yearly_rows as $yr) { $yearly_expected += floatval($yr->fee); }

    $monthly_expected = 0;
    $monthly_rows = $wpdb->get_results($wpdb->prepare(
        "SELECT fl.fee FROM ct_student_fee_list fl
        INNER JOIN (
            SELECT sub_head_id, MAX(id) AS max_id FROM ct_student_fee_list
            WHERE class_id = %d AND year = %s AND sub_head_id IN (SELECT id FROM ct_sub_head WHERE type = 1 AND active_for_collection = 1)
            GROUP BY sub_head_id
        ) latest ON latest.max_id = fl.id",
        $class_id, $year
    ));
    foreach ($monthly_rows as $mr) { $monthly_expected += floatval($mr->fee) * 12; }

    // Expected exam fees from fee list (per-exam fee * number of exams)
    $exam_expected = 0;
    $exam_rows = $wpdb->get_results($wpdb->prepare(
        "SELECT fl.fee FROM ct_student_fee_list fl
        INNER JOIN (
            SELECT sub_head_id, MAX(id) AS max_id FROM ct_student_fee_list
            WHERE class_id = %d AND year = %s AND sub_head_id IN (SELECT id FROM ct_sub_head WHERE type = 3 AND active_for_collection = 1)
            GROUP BY sub_head_id
        ) latest ON latest.max_id = fl.id",
        $class_id, $year
    ));
    foreach ($exam_rows as $er) { $exam_expected += floatval($er->fee); }
    // Only count exams active for collection (not all exams)
    $exam_count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM ct_exam WHERE examClass = %d AND active_for_collection = 1",
        $class_id
    ));
    $exam_expected = $exam_expected * max(1, intval($exam_count));

    $total_expected = $yearly_expected + $monthly_expected + $exam_expected;

    $yearly_paid_map = array();
    foreach ($wpdb->get_results($wpdb->prepare("SELECT student_id, SUM(fee) as paid FROM ct_student_yearly_fee_summary WHERE class_id = %d AND year = %s AND fee > 0 GROUP BY student_id", $class_id, $year)) as $r) {
        $yearly_paid_map[$r->student_id] = floatval($r->paid);
    }
    $monthly_paid_map = array();
    foreach ($wpdb->get_results($wpdb->prepare("SELECT student_id, SUM(fee) as paid FROM ct_student_monthly_fee_summary WHERE class_id = %d AND year = %s AND fee > 0 GROUP BY student_id", $class_id, $year)) as $r) {
        $monthly_paid_map[$r->student_id] = floatval($r->paid);
    }
    $exam_paid_map = array();
    foreach ($wpdb->get_results($wpdb->prepare("SELECT student_id, SUM(fee) as paid FROM ct_student_exam_fee_summary WHERE class_id = %d AND year = %s AND fee > 0 GROUP BY student_id", $class_id, $year)) as $r) {
        $exam_paid_map[$r->student_id] = floatval($r->paid);
    }

    $result = array();
    foreach ($students as $s) {
        $paid = ($yearly_paid_map[$s->infoStdid] ?? 0) + ($monthly_paid_map[$s->infoStdid] ?? 0) + ($exam_paid_map[$s->infoStdid] ?? 0);
        $due = max(0, $total_expected - $paid);
        $pct = $total_expected > 0 ? round(($paid / $total_expected) * 100) : 0;
        if ($paid > $total_expected) $status_s = 'Overpaid';
        elseif ($pct >= 100) $status_s = 'Paid';
        elseif ($pct > 0) $status_s = 'Partial';
        else $status_s = 'Due';
        $result[] = array(
            'student_id' => $s->infoStdid, 'roll' => $s->infoRoll, 'name' => $s->stdName,
            'facilities' => $s->facilities, 'total_expected' => $total_expected,
            'total_paid' => $paid, 'total_due' => $due, 'payment_pct' => $pct, 'status' => $status_s,
        );
    }
    return $result;
}

// ===== Fetch all mismatches from DB =====
$allMismatches = array();
$claim_map = array();
$mismatch_table_exists = $wpdb->get_var("SHOW TABLES LIKE 'ct_payment_mismatch'");
if ($mismatch_table_exists) {
    $mm_query = "SELECT m.*, s.stdName, s.studentid, si.infoRoll, si.infoClass FROM ct_payment_mismatch m LEFT JOIN ct_student s ON s.studentid = m.student_id LEFT JOIN ct_studentinfo si ON si.infoStdid = m.student_id AND si.infoYear = '" . esc_sql($filter_year) . "'";
    if ($filter_class) {
        $mm_query .= " WHERE si.infoClass = " . intval($filter_class);
    }
    $mm_query .= " ORDER BY m.detected_at DESC LIMIT 500";
    $allMismatches = $wpdb->get_results($mm_query, ARRAY_A);

    $claims = $wpdb->get_results("SELECT mismatch_id, status, review_notes FROM ct_mismatch_claim ORDER BY claim_id DESC");
    foreach ($claims as $c) {
        if (!isset($claim_map[$c->mismatch_id])) {
            $claim_map[$c->mismatch_id] = array('status' => $c->status, 'review_notes' => $c->review_notes);
        }
    }
}

// Pre-load all class-section mappings for standalone section loading (no AJAX)
$all_class_sections = array();
$section_data = $wpdb->get_results(
    "SELECT si.infoClass, si.infoSection, sec.sectionName 
     FROM ct_studentinfo si 
     LEFT JOIN ct_section sec ON sec.sectionid = si.infoSection 
     WHERE si.infoSection IS NOT NULL 
     GROUP BY si.infoClass, si.infoSection 
     ORDER BY si.infoClass, si.infoSection"
);
foreach ($section_data as $sd) {
    if (!isset($all_class_sections[$sd->infoClass])) {
        $all_class_sections[$sd->infoClass] = array();
    }
    $all_class_sections[$sd->infoClass][] = array(
        'id' => $sd->infoSection,
        'name' => $sd->sectionName,
    );
}
?>
<style>
:root {
    --font: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    --bg: #f0f2f5;
    --surface: #ffffff;
    --surface-hover: #f8f9fa;
    --border: #e2e5ea;
    --text: #1a1d23;
    --text-secondary: #6b7280;
    --primary: #6366f1;
    --primary-dark: #4f46e5;
    --primary-light: #eef2ff;
    --success: #10b981;
    --success-bg: #ecfdf5;
    --warning: #f59e0b;
    --warning-bg: #fffbeb;
    --danger: #ef4444;
    --danger-bg: #fef2f2;
    --info: #3b82f6;
    --info-bg: #eff6ff;
    --purple: #8b5cf6;
    --purple-bg: #f5f3ff;
    --radius: 12px;
    --radius-sm: 8px;
    --shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
    --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -1px rgba(0,0,0,0.04);
    --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.08), 0 4px 6px -4px rgba(0,0,0,0.04);
    --transition: 0.2s ease;
}

* { box-sizing: border-box; font-family: var(--font); }

body {
    background: var(--bg);
    margin: 0;
    padding: 16px;
    min-height: 100vh;
}

/* ===== Page Header ===== */
.page-header {
    position: relative;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%);
    border-radius: 0;
    padding: 28px 32px;
    margin: -16px -16px 24px -16px;
    overflow: hidden;
    isolation: isolate;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    box-shadow: var(--shadow-md);
}
.page-header .header-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(255,255,255);
    color: var(--text);
    backdrop-filter: blur(4px);
    padding: 8px 16px;
    border-radius: var(--radius-sm);
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    transition: var(--transition);
    white-space: nowrap;
}
.page-header .header-btn:hover {
    background: rgba(255,255,255,0.9);
    transform: translateY(-1px);
    color: var(--primary);
}
.page-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
    z-index: 0;
}
.page-header::after {
    content: '';
    position: absolute;
    bottom: -40%;
    left: 10%;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
    z-index: 0;
}
.page-header > * { position: relative; z-index: 1; }
.page-header h2 {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
    letter-spacing: -0.3px;
    color: #fff;
}
.page-header .header-stats {
    display: flex;
    gap: 16px;
    flex-wrap: wrap;
}
.page-header .header-stat {
    background: rgba(255,255,255,0.15);
    backdrop-filter: blur(4px);
    padding: 8px 16px;
    border-radius: var(--radius-sm);
    text-align: center;
    min-width: 80px;
}
.page-header .header-stat .stat-value {
    display: block;
    font-size: 18px;
    font-weight: 700;
    line-height: 1.2;
}
.page-header .header-stat .stat-label {
    display: block;
    font-size: 11px;
    opacity: 0.85;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-top: 2px;
}

/* ===== Filter Card ===== */
.filter-card {
    background: var(--surface);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    padding: 20px 24px;
    margin-bottom: 24px;
    border: 1px solid var(--border);
}
.filter-card .filter-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.filter-form { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; }
.filter-form .form-group { margin: 0; flex: 0 0 auto; }
.filter-form .form-group label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    color: var(--text-secondary);
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}
.filter-form .form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    font-size: 14px;
    color: var(--text);
    background: var(--surface);
    transition: var(--transition);
    appearance: none;
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    padding-right: 32px;
}
.filter-form .form-control:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
    outline: none;
}
/* Tabs inside filter card — side by side on desktop */
.filter-with-tabs {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    align-items: flex-start;
}
.filter-with-tabs .filter-body {
    flex: 1;
    min-width: 280px;
}
.filter-with-tabs .filter-tabs {
    display: flex;
    gap: 4px;
    padding: 6px;
    background: #f8fafc;
    border-radius: var(--radius-sm);
    border: 1px solid var(--border);
    flex-shrink: 0;
    align-self: flex-end;
    overflow-x: auto;
}
.filter-with-tabs .filter-tabs .tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    font-family: var(--font);
    color: var(--text-secondary);
    background: transparent;
    cursor: pointer;
    transition: var(--transition);
    white-space: nowrap;
}
.filter-with-tabs .filter-tabs .tab-btn:hover {
    background: var(--surface-hover);
    color: var(--text);
}
.filter-with-tabs .filter-tabs .tab-btn.active {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff;
    box-shadow: 0 2px 8px rgba(99,102,241,0.25);
}
.filter-with-tabs .filter-tabs .tab-btn svg { flex-shrink: 0; }
@media (max-width: 1024px) {
    .filter-with-tabs .filter-tabs { width: 100%; align-self: stretch; justify-content: center; }
}

.filter-form .btn-search {
    background: var(--primary);
    color: #fff;
    border: none;
    padding: 8px 24px;
    border-radius: var(--radius-sm);
    font-size: 14px;
    font-weight: 500;
    font-family: var(--font);
    cursor: pointer;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 6px;
    white-space: nowrap;
    min-width: 100px;
    justify-content: center;
}
.filter-form .btn-search:hover { background: var(--primary-dark); transform: translateY(-1px); box-shadow: var(--shadow-md); }
.filter-form .btn-search:active { transform: translateY(0); }

/* ===== Layout ===== */
.unified-layout { display: flex; gap: 20px; align-items: flex-start; }
.unified-sidebar {
    width: 300px;
    min-width: 260px;
    background: var(--surface);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
    overflow: hidden;
    max-height: 70vh;
    display: flex;
    flex-direction: column;
}
.unified-sidebar .sidebar-header {
    background: #f8fafc;
    padding: 12px 16px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}
.unified-sidebar .sidebar-header h4 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
    color: var(--text);
}
.unified-sidebar .sidebar-header .badge-count {
    background: var(--primary-light);
    color: var(--primary);
    font-size: 11px;
    font-weight: 600;
    padding: 2px 10px;
    border-radius: 20px;
}
.unified-sidebar .sidebar-search {
    padding: 10px 12px;
    border-bottom: 1px solid var(--border);
    flex-shrink: 0;
}
.unified-sidebar .sidebar-search input {
    width: 100%;
    padding: 7px 12px 7px 32px;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    font-size: 13px;
    transition: var(--transition);
    background: var(--bg) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'%3E%3C/circle%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'%3E%3C/line%3E%3C/svg%3E") no-repeat 10px center;
}
.unified-sidebar .sidebar-search input:focus {
    border-color: var(--primary);
    background-color: var(--surface);
    box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
    outline: none;
}
.unified-sidebar .student-list {
    flex: 1;
    overflow-y: auto;
    padding: 4px 0;
}
.unified-sidebar .student-item {
    display: flex;
    align-items: center;
    padding: 10px 14px;
    border-bottom: 1px solid var(--border);
    cursor: pointer;
    transition: var(--transition);
    color: var(--text);
    text-decoration: none;
    gap: 10px;
}
.unified-sidebar .student-item:hover { background: var(--surface-hover); }
.unified-sidebar .student-item.active {
    background: var(--primary-light);
    border-left: 3px solid var(--primary);
    font-weight: 600;
    color: var(--primary-dark);
}
.unified-sidebar .student-item .roll {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: var(--border);
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
    color: var(--text-secondary);
    flex-shrink: 0;
}
.unified-sidebar .student-item.active .roll {
    background: var(--primary);
    color: #fff;
}
.unified-sidebar .student-item.active .name {
    color: var(--primary-dark);
}
.unified-sidebar .student-item .name {
    font-size: 13px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.unified-main { flex: 1; min-width: 0; }

/* ===== Cards ===== */
.card {
    background: var(--surface);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    margin-bottom: 20px;
    overflow: hidden;
}
.card-header {
    padding: 18px 24px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}
.card-header h2,
.card-header h3 {
    margin: 0;
    font-size: 17px;
    font-weight: 600;
    color: var(--text);
}
.card-body {
    padding: 20px 24px;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.card-body:only-child { border: none; }
.card-body::-webkit-scrollbar { height: 6px; }
.card-body::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 3px; }
.card-body::-webkit-scrollbar-thumb { background: #c1c7cd; border-radius: 3px; }

/* ===== Stat Grid ===== */
.stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    gap: 16px;
    margin-bottom: 20px;
}
.stat-card {
    border-radius: var(--radius);
    padding: 20px;
    color: #fff;
    position: relative;
    overflow: hidden;
}
.stat-card::after {
    content: '';
    position: absolute;
    top: -30%;
    right: -15%;
    width: 120px;
    height: 120px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
    pointer-events: none;
}
.stat-card .stat-label {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    opacity: 0.85;
    font-weight: 500;
    position: relative;
    z-index: 1;
}
.stat-card .stat-value {
    font-size: 24px;
    font-weight: 700;
    margin-top: 6px;
    letter-spacing: -0.5px;
    position: relative;
    z-index: 1;
}
.stat-card .stat-sub {
    font-size: 12px;
    opacity: 0.75;
    margin-top: 2px;
    position: relative;
    z-index: 1;
}
.stat-card .stat-icon {
    position: absolute;
    top: 12px;
    right: 14px;
    font-size: 28px;
    opacity: 0.25;
    z-index: 0;
}
/* Stat card colors matching mismatch page */
.stat-bg-blue    { background: linear-gradient(135deg, #6366f1, #8b5cf6); }
.stat-bg-green   { background: linear-gradient(135deg, #10b981, #059669); }
.stat-bg-red     { background: linear-gradient(135deg, #ef4444, #dc2626); }
.stat-bg-amber   { background: linear-gradient(135deg, #f59e0b, #d97706); }
.stat-bg-teal    { background: linear-gradient(135deg, #14b8a6, #0d9488); }
.stat-bg-purple  { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
.stat-bg-orange  { background: linear-gradient(135deg, #f97316, #ea580c); }
.stat-bg-pink    { background: linear-gradient(135deg, #ec4899, #db2777); }
.stat-bg-info    { background: linear-gradient(135deg, #3b82f6, #2563eb); }

/* ===== Student Info Card ===== */
.student-info-card {
    background: var(--surface);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
    margin-bottom: 20px;
    overflow: hidden;
}
.student-info-card .info-header {
    background: linear-gradient(135deg, #f8fafc 0%, #fff 100%);
    padding: 20px 24px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}
.student-info-card .info-header .student-name {
    font-size: 20px;
    font-weight: 700;
    color: var(--text);
    margin: 0;
}
.student-info-card .info-header .student-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    margin-top: 6px;
}
.student-info-card .info-header .student-meta span {
    font-size: 13px;
    color: var(--text-secondary);
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.student-info-card .info-header .student-meta strong { color: var(--text); }

/* ===== Tables ===== */
.table-wrap {
    overflow-x: auto;
    border-radius: var(--radius-sm);
}
.table-wrap::-webkit-scrollbar {
    width: 6px; height: 6px;
}
.table-wrap::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}
.table-wrap::-webkit-scrollbar-thumb {
    background: #c1c7cd;
    border-radius: 3px;
}
.modern-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13px; }
.modern-table thead { position: sticky; top: 0; z-index: 2; }
.modern-table thead th {
    background: #f8fafc;
    padding: 12px 14px;
    text-align: center;
    font-weight: 600;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-secondary);
    border-bottom: 2px solid var(--border);
    white-space: nowrap;
}
.modern-table tbody td {
    padding: 10px 14px;
    border-bottom: 1px solid var(--border);
    color: var(--text);
    vertical-align: middle;
}
.modern-table tbody tr:hover { background: var(--surface-hover); }
.modern-table tbody tr:last-child td { border-bottom: none; }
.modern-table .text-left { text-align: left; }
.modern-table .text-right { text-align: right; }
.modern-table .text-center { text-align: center; }
.modern-table .font-bold { font-weight: 600; }
.modern-table .total-row { background: #f8fafc !important; font-weight: 600; }
.modern-table .total-row td { border-top: 2px solid var(--border); border-bottom: none; padding: 12px 14px; color: var(--text); }
.modern-table .table-danger { background: var(--danger-bg) !important; }
.modern-table .table-danger td { color: #991b1b; }

/* ===== Status Badges ===== */
.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}
.badge-paid, .badge-success { background: var(--success-bg); color: var(--success); }
.badge-pending, .badge-warning { background: var(--warning-bg); color: var(--warning); }
.badge-unpaid, .badge-danger { background: var(--danger-bg); color: var(--danger); }
.badge-failed { background: var(--danger-bg); color: var(--danger); }
.badge-cancelled, .badge-secondary { background: #f3f4f6; color: #6b7280; }
.badge-partial { background: var(--primary-light); color: var(--primary); }
.badge-overpaid { background: #fef3c7; color: #d97706; }
.badge-info { background: var(--info-bg); color: var(--info); }

/* ===== Row Highlights ===== */
.row-due { background: #fef2f2 !important; }
.row-due:hover { background: #fee2e2 !important; }
.row-overpaid { background: #fffbeb !important; }
.row-overpaid:hover { background: #fef3c7 !important; }
.clickable-row { cursor: pointer; }

/* ===== Cell Status ===== */
.cell-paid { color: var(--success); font-weight: 600; }
.cell-unpaid { color: var(--danger); font-weight: 600; }

/* ===== Empty State ===== */
.empty-state {
    text-align: center;
    padding: 48px 24px;
    color: var(--text-secondary);
}
.empty-state .empty-icon {
    font-size: 48px;
    margin-bottom: 12px;
    opacity: 0.3;
}
.empty-state h4 {
    margin: 0 0 6px;
    font-size: 18px;
    color: var(--text);
    font-weight: 600;
}
.empty-state p { margin: 0; font-size: 14px; color: var(--text-secondary); }

/* ===== Print ===== */
@media print {
    .filter-card, .unified-sidebar, .no-print { display: none !important; }
    .unified-layout { display: block; }
    .card { break-inside: avoid; box-shadow: none; border: 1px solid #ddd; }
    .page-header { background: #333 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .badge { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}

/* ===== Responsive ===== */
@media (max-width: 1024px) {
    .unified-layout { flex-direction: column; }
    .unified-sidebar { width: 100%; max-height: 300px; }
}
@media (max-width: 768px) {
    body { padding: 12px; }
    .page-header { padding: 20px; flex-direction: column; text-align: center; margin: -12px -12px 20px -12px; }
    .page-header h2 { font-size: 20px; }
    .page-header .header-stats { justify-content: center; }
    .filter-form .form-group { flex: 1 1 100%; }
    .card-header { flex-direction: column; align-items: flex-start; }
    .card-body { padding: 16px; }
    .modern-table { font-size: 12px; }
    .modern-table thead th, .modern-table tbody td { padding: 6px 8px; }
    .student-info-card .info-header { flex-direction: column; text-align: center; }
    .student-info-card .info-header .student-meta { justify-content: center; }
}

/* ===== Tab Navigation ===== */
.tab-nav {
    display: flex;
    gap: 4px;
    margin-bottom: 24px;
    background: var(--surface);
    border-radius: var(--radius);
    box-shadow: var(--shadow);
    border: 1px solid var(--border);
    padding: 6px;
    overflow-x: auto;
}
.tab-nav::-webkit-scrollbar { height: 4px; }
.tab-nav::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 2px; }
.tab-nav::-webkit-scrollbar-thumb { background: #c1c7cd; border-radius: 2px; }
.tab-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: none;
    border-radius: var(--radius-sm);
    font-size: 13px;
    font-weight: 500;
    font-family: var(--font);
    color: var(--text-secondary);
    background: transparent;
    cursor: pointer;
    transition: var(--transition);
    white-space: nowrap;
}
.tab-btn:hover {
    background: var(--surface-hover);
    color: var(--text);
}
.tab-btn.active {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: #fff;
    box-shadow: 0 2px 8px rgba(99,102,241,0.25);
}
.tab-btn svg { flex-shrink: 0; }

.tab-content { min-height: 300px; }
.tab-pane { display: none; }
.tab-pane.active { display: block; }

/* ===== Mismatch Claim Form ===== */
.claim-form-row { display: none; }
.claim-form-row.open { display: table-row; }
.claim-form-row td { padding: 0 !important; }
.claim-inner {
    padding: 16px 24px;
    background: #f8fafc;
    border-bottom: 2px solid var(--primary);
    display: flex;
    gap: 12px;
    align-items: flex-start;
    flex-wrap: wrap;
}
.claim-inner textarea {
    flex: 1;
    min-width: 200px;
    padding: 8px 12px;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    font-size: 13px;
    font-family: var(--font);
    resize: vertical;
    min-height: 36px;
}
.claim-inner textarea:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
    outline: none;
}
.claim-inner .btn-submit-claim {
    background: var(--primary);
    color: #fff;
    border: none;
    padding: 8px 20px;
    border-radius: var(--radius-sm);
    font-size: 13px;
    font-weight: 500;
    font-family: var(--font);
    cursor: pointer;
    transition: var(--transition);
    white-space: nowrap;
}
.claim-inner .btn-submit-claim:hover { background: var(--primary-dark); }
.claim-inner .btn-cancel-claim {
    background: transparent;
    color: var(--text-secondary);
    border: 1.5px solid var(--border);
    padding: 8px 16px;
    border-radius: var(--radius-sm);
    font-size: 13px;
    font-weight: 500;
    font-family: var(--font);
    cursor: pointer;
    transition: var(--transition);
}
.claim-inner .btn-cancel-claim:hover { background: var(--surface-hover); color: var(--text); }
.claim-success {
    background: var(--success-bg);
    color: var(--success);
    padding: 12px 20px;
    border-radius: var(--radius-sm);
    margin-bottom: 16px;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* ===== Due Tab Mini Stat Grid ===== */
.due-stat-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 12px;
    margin-bottom: 16px;
}

@media (max-width: 768px) {
    .tab-nav { padding: 4px; gap: 2px; }
    .tab-btn { padding: 8px 12px; font-size: 12px; }
    .tab-btn svg { width: 14px; height: 14px; }
    .claim-inner { flex-direction: column; }
    .claim-inner textarea { min-width: 100%; }
}

/* ===== Transfer Button ===== */
.btn-xs {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border: none;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
    font-family: var(--font);
    cursor: pointer;
    transition: var(--transition);
    white-space: nowrap;
}
.btn-transfer {
    background: var(--primary-light);
    color: var(--primary);
}
.btn-transfer:hover {
    background: var(--primary);
    color: #fff;
}
.btn-claim {
    background: #fff3e0;
    color: #e65100;
    border: 1.5px solid #ffb74d;
    padding: 6px 14px;
    font-size: 12px;
    flex-shrink: 0;
}
.btn-claim:hover {
    background: #e65100;
    color: #fff;
    border-color: #e65100;
}
.btn-refund {
    background: #fef2f2;
    color: #dc2626;
    border: 1.5px solid #fca5a5;
    padding: 6px 14px;
    font-size: 12px;
    flex-shrink: 0;
}
.btn-refund:hover {
    background: #dc2626;
    color: #fff;
    border-color: #dc2626;
}

/* ===== Claim Modal ===== */
.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    backdrop-filter: blur(4px);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.modal-overlay.open { display: flex; }
.modal-box {
    background: var(--surface);
    border-radius: var(--radius);
    box-shadow: var(--shadow-lg);
    width: 100%;
    max-width: 560px;
    max-height: 90vh;
    overflow-y: auto;
    animation: modalIn 0.2s ease;
}
@keyframes modalIn {
    from { opacity: 0; transform: scale(0.95) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}
.modal-box .modal-header {
    padding: 18px 24px;
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    background: var(--surface);
    z-index: 1;
}
.modal-box .modal-header h3 {
    margin: 0;
    font-size: 17px;
    font-weight: 600;
    color: var(--text);
}
.modal-box .modal-close {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border: none;
    background: var(--surface-hover);
    border-radius: 6px;
    cursor: pointer;
    color: var(--text-secondary);
    transition: var(--transition);
    font-size: 18px;
}
.modal-box .modal-close:hover { background: var(--border); color: var(--text); }
.modal-box .modal-body { padding: 20px 24px; }
.modal-box .modal-footer {
    padding: 16px 24px;
    border-top: 1px solid var(--border);
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    position: sticky;
    bottom: 0;
    background: var(--surface);
}

/* Transfer info summary */
.transfer-info {
    background: var(--primary-light);
    border-radius: var(--radius-sm);
    padding: 14px 16px;
    margin-bottom: 20px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
    font-size: 13px;
}
.transfer-info .ti-label { color: var(--text-secondary); font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; }
.transfer-info .ti-value { color: var(--text); font-weight: 600; }

/* Form inside modal */
.modal-form .form-row {
    display: flex;
    gap: 12px;
    margin-bottom: 14px;
    flex-wrap: wrap;
}
.modal-form .form-group {
    flex: 1;
    min-width: 140px;
}
.modal-form .form-group label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    color: var(--text-secondary);
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}
.modal-form .form-group select,
.modal-form .form-group input {
    width: 100%;
    padding: 8px 12px;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    font-size: 13px;
    color: var(--text);
    background: var(--surface);
    transition: var(--transition);
    font-family: var(--font);
}
.modal-form .form-group select:focus,
.modal-form .form-group input:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(99,102,241,0.12);
    outline: none;
}
.modal-form .form-group select:disabled,
.modal-form .form-group input:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Student results list */
.student-results {
    max-height: 200px;
    overflow-y: auto;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    margin-bottom: 14px;
}
.student-results .sr-item {
    display: flex;
    align-items: center;
    padding: 10px 14px;
    border-bottom: 1px solid var(--border);
    cursor: pointer;
    transition: var(--transition);
    gap: 12px;
}
.student-results .sr-item:last-child { border-bottom: none; }
.student-results .sr-item:hover { background: var(--surface-hover); }
.student-results .sr-item.selected {
    background: var(--primary-light);
    border-left: 3px solid var(--primary);
}
.student-results .sr-item .sr-roll {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    background: var(--border);
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    color: var(--text-secondary);
    flex-shrink: 0;
}
.student-results .sr-item.selected .sr-roll { background: var(--primary); color: #fff; }
.student-results .sr-item .sr-name { font-size: 13px; font-weight: 500; color: var(--text); flex: 1; }
.student-results .sr-item .sr-facilities { font-size: 11px; color: var(--text-secondary); }
.student-results .sr-empty {
    padding: 24px;
    text-align: center;
    color: var(--text-secondary);
    font-size: 13px;
}
.student-results::-webkit-scrollbar { width: 5px; }
.student-results::-webkit-scrollbar-track { background: #f1f1f1; }
.student-results::-webkit-scrollbar-thumb { background: #c1c7cd; border-radius: 3px; }

.btn-transfer-submit {
    background: var(--primary);
    color: #fff;
    border: none;
    padding: 9px 24px;
    border-radius: var(--radius-sm);
    font-size: 14px;
    font-weight: 500;
    font-family: var(--font);
    cursor: pointer;
    transition: var(--transition);
}
.btn-transfer-submit:hover { background: var(--primary-dark); }
.btn-transfer-submit:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-transfer-cancel {
    background: transparent;
    color: var(--text-secondary);
    border: 1.5px solid var(--border);
    padding: 9px 20px;
    border-radius: var(--radius-sm);
    font-size: 13px;
    font-weight: 500;
    font-family: var(--font);
    cursor: pointer;
    transition: var(--transition);
}
.btn-transfer-cancel:hover { background: var(--surface-hover); color: var(--text); }

.transfer-loading {
    display: none;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px;
    color: var(--text-secondary);
    font-size: 13px;
}
.transfer-loading.active { display: flex; }
.transfer-loading .spinner {
    width: 18px;
    height: 18px;
    border: 2px solid var(--border);
    border-top-color: var(--primary);
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

.transfer-success {
    display: none;
    background: var(--success-bg);
    color: var(--success);
    padding: 14px 18px;
    border-radius: var(--radius-sm);
    font-size: 14px;
    font-weight: 500;
    margin-bottom: 14px;
    align-items: center;
    gap: 10px;
}
.transfer-success.active { display: flex; }
.transfer-error {
    display: none;
    background: var(--danger-bg);
    color: var(--danger);
    padding: 14px 18px;
    border-radius: var(--radius-sm);
    font-size: 13px;
    margin-bottom: 14px;
}
.transfer-error.active { display: block; }

/* Transfer confirmation step */
.transfer-confirm {
    display: none;
    flex-direction: column;
    align-items: center;
    padding: 12px 0 4px;
    text-align: center;
}
.transfer-confirm.active { display: flex; }
.transfer-confirm .cf-header {
    font-size: 17px;
    font-weight: 600;
    color: var(--text);
    margin-bottom: 4px;
}
.transfer-confirm .cf-icon { margin: 8px 0 4px; }
.transfer-confirm .cf-desc {
    font-size: 13px;
    color: var(--text-secondary);
    margin: 0 0 18px;
    max-width: 360px;
}
.transfer-confirm .cf-details {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 14px 18px;
    width: 100%;
    max-width: 420px;
    text-align: left;
    margin-bottom: 20px;
}
.transfer-confirm .cf-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 6px 0;
    font-size: 13px;
}
.transfer-confirm .cf-label { color: var(--text-secondary); font-weight: 500; }
.transfer-confirm .cf-value { color: var(--text); font-weight: 600; text-align: right; max-width: 60%; word-break: break-word; }
.transfer-confirm .cf-mono { font-family: monospace; font-size: 12px; }
.transfer-confirm .cf-amount { color: var(--primary); font-size: 15px; }
.transfer-confirm .cf-divider {
    border-top: 1px solid var(--border);
    margin: 6px 0 4px;
}
.transfer-confirm .cf-actions {
    display: flex;
    gap: 10px;
    width: 100%;
    max-width: 420px;
    justify-content: center;
}
.transfer-confirm .cf-actions .btn-transfer-submit { flex: 1; max-width: 200px; }
</style>

<!-- ===== Page Header ===== -->
<div class="page-header">
    <div>
        <h2 id="pageHeaderTitle">Student Fee Audit Report</h2>
        <p style="margin:4px 0 0;font-size:13px;opacity:0.75;">Payment integrity &amp; reconciliation dashboard</p>
    </div>
    <div class="header-stats">
        <a href="/frontend-admin" class="header-btn">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
            Back to Frontend Admin
        </a>
        <div class="header-stat">
            <span class="stat-value"><?= count($students) ?></span>
            <span class="stat-label">Students</span>
        </div>
        <?php if ($filter_class): ?>
        <div class="header-stat">
            <span class="stat-value"><?= getClassNameById($filter_class) ?></span>
            <span class="stat-label">Class</span>
        </div>
        <?php endif; ?>
        <div class="header-stat">
            <span class="stat-value"><?= $filter_year ?></span>
            <span class="stat-label">Year</span>
        </div>
    </div>
</div>

<?php if (!empty($claim_it_message)): ?>
<div style="background:var(--success-bg);color:var(--success);padding:12px 20px;border-radius:var(--radius-sm);margin-bottom:12px;font-size:14px;font-weight:500;display:flex;align-items:center;gap:10px;">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    <?= esc_html($claim_it_message) ?>
</div>
<?php endif; ?>
<?php if (!empty($claim_it_error)): ?>
<div style="background:var(--danger-bg);color:var(--danger);padding:12px 20px;border-radius:var(--radius-sm);margin-bottom:12px;font-size:14px;display:flex;align-items:center;gap:10px;">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
    <?= esc_html($claim_it_error) ?>
</div>
<?php endif; ?>
<?php if (!empty($refund_message)): ?>
<div style="background:var(--success-bg);color:var(--success);padding:12px 20px;border-radius:var(--radius-sm);margin-bottom:12px;font-size:14px;font-weight:500;display:flex;align-items:center;gap:10px;">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
    <?= esc_html($refund_message) ?>
</div>
<?php endif; ?>
<?php if (!empty($refund_error)): ?>
<div style="background:var(--danger-bg);color:var(--danger);padding:12px 20px;border-radius:var(--radius-sm);margin-bottom:12px;font-size:14px;display:flex;align-items:center;gap:10px;">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
    <?= esc_html($refund_error) ?>
</div>
<?php endif; ?>

<!-- ===== Filter Card with Tabs ===== -->
<div class="filter-card filter-with-tabs">
    <div class="filter-body">
        <form action="" method="POST" class="filter-form">
        <div class="form-group">
            <label>Class</label>
            <select name="stdclass" id="filterClass" class="form-control" required onchange="loadFilterSections(this.value)">
                <option value="">Select Class</option>
                <?php
                $classQuery = $wpdb->get_results("SELECT classid,className FROM ct_class WHERE classid IN (SELECT infoClass FROM ct_studentinfo GROUP BY infoClass ORDER BY className ASC)");
                foreach ($classQuery as $c) {
                    echo '<option value="'.$c->classid.'"'.($filter_class==$c->classid?' selected':'').'>'.$c->className.'</option>';
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label>Section</label>
            <select name="sec" id="filterSec" class="form-control" onchange="this.form.submit()">
                <option value="">All</option>
                <?php
                if ($filter_class) {
                    $secs = $wpdb->get_results("SELECT DISTINCT si.infoSection, sec.sectionName FROM ct_studentinfo si LEFT JOIN ct_section sec ON sec.sectionid = si.infoSection WHERE si.infoClass = $filter_class AND si.infoSection IS NOT NULL ORDER BY si.infoSection");
                    foreach ($secs as $s) {
                        echo '<option value="'.$s->infoSection.'"'.($filter_sec==$s->infoSection?' selected':'').'>'.$s->sectionName.'</option>';
                    }
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label>Group</label>
            <select name="group" class="form-control">
                <option value="">All</option>
                <?php
                $groups = $wpdb->get_results("SELECT * FROM ct_group");
                foreach ($groups as $g) {
                    echo '<option value="'.$g->groupId.'"'.($filter_group==$g->groupId?' selected':'').'>'.$g->groupName.'</option>';
                }
                ?>
            </select>
        </div>
        <div class="form-group">
            <label>Year</label>
            <select name="stdyear" class="form-control">
                <?php for ($y = current_time('Y')-1; $y <= current_time('Y')+1; $y++) {
                    echo '<option value="'.$y.'"'.($filter_year==$y?' selected':'').'>'.$y.'</option>';
                } ?>
            </select>
        </div>

        <div class="form-group">
            <label>Status</label>
            <select name="payment_status" class="form-control">
                <option value="">All</option>
                <option value="paid" <?= $filter_status=='paid'?'selected':'' ?>>Paid</option>
                <option value="unpaid" <?= $filter_status=='unpaid'?'selected':'' ?>>Unpaid</option>
                <option value="partial" <?= $filter_status=='partial'?'selected':'' ?>>Partial</option>
            </select>
        </div>
        <div class="form-group">
            <label>&nbsp;</label>
            <button type="submit" class="btn-search">Search</button>
        </div>
    </form>
    </div>
    <div class="filter-tabs" id="tabNav">
        <button class="tab-btn <?= $active_tab==='due'?'active':'' ?>" data-tab="due" onclick="switchTab('due')">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
            Due Students
        </button>
        <button class="tab-btn <?= $active_tab==='audit'?'active':'' ?>" data-tab="audit" onclick="switchTab('audit')">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
            Student Audit
        </button>
        <button class="tab-btn <?= $active_tab==='mismatches'?'active':'' ?>" data-tab="mismatches" onclick="switchTab('mismatches')">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
            Mismatches
        </button>
    </div>
</div>

<script>
// Pre-loaded section data for standalone filter (no AJAX)
var sectionData = <?= json_encode($all_class_sections) ?>;

function loadFilterSections(classId) {
    var secSelect = document.getElementById('filterSec');
    secSelect.innerHTML = '<option value="">All</option>';
    if (!classId) return;

    if (sectionData[classId]) {
        for (var i = 0; i < sectionData[classId].length; i++) {
            var opt = document.createElement('option');
            opt.value = sectionData[classId][i].id;
            opt.textContent = sectionData[classId][i].name;
            secSelect.appendChild(opt);
        }
    }
}

function switchTab(tabName) {
    var panes = document.querySelectorAll('.tab-pane');
    for (var i = 0; i < panes.length; i++) {
        panes[i].classList.remove('active');
    }
    var btns = document.querySelectorAll('.filter-tabs .tab-btn');
    for (var i = 0; i < btns.length; i++) {
        btns[i].classList.remove('active');
    }
    document.getElementById('tab-' + tabName).classList.add('active');
    var activeBtn = document.querySelector('.filter-tabs .tab-btn[data-tab="' + tabName + '"]');
    if (activeBtn) activeBtn.classList.add('active');
    var url = new URL(window.location.href);
    url.searchParams.set('tab', tabName);
    window.history.replaceState({}, '', url.toString());

    // Update page header title dynamically
    var tabTitles = {
        audit: 'Student Audit &#8212; Fee Details',
        due: 'Due Students &#8212; Payment Status',
        mismatches: 'Payment Mismatch Management'
    };
    var titleEl = document.getElementById('pageHeaderTitle');
    if (titleEl && tabTitles[tabName]) {
        titleEl.innerHTML = tabTitles[tabName];
    }
}

document.addEventListener('DOMContentLoaded', function() {
    var params = new URLSearchParams(window.location.search);
    var tab = params.get('tab');
    if (tab && ['due','audit','mismatches'].indexOf(tab) > -1) {
        switchTab(tab);
    }
});
</script>

<?php if ($filter_class && !empty($students)): ?>

<!-- ===== Tab Content ===== -->
<div class="tab-content">

    <!-- ===== Tab: Due Students ===== -->
    <div class="tab-pane <?= $active_tab==='due'?'active':'' ?>" id="tab-due">
        <?php
        $dueStudents = computeDueStudents($filter_class, $filter_sec, $filter_group, $filter_year);
        // Apply status filter
        if ($filter_status === 'paid') {
            $dueStudents = array_filter($dueStudents, function($ds) {
                return $ds['status'] === 'Paid' || $ds['status'] === 'Overpaid';
            });
        } elseif ($filter_status === 'unpaid') {
            $dueStudents = array_filter($dueStudents, function($ds) {
                return $ds['status'] === 'Due';
            });
        } elseif ($filter_status === 'partial') {
            $dueStudents = array_filter($dueStudents, function($ds) {
                return $ds['status'] === 'Partial';
            });
        }
        $dueGrandExpected = 0; $dueGrandPaid = 0; $dueGrandDue = 0; $dueDueCount = 0;
        foreach ($dueStudents as $ds) {
            $dueGrandExpected += $ds['total_expected'];
            $dueGrandPaid += $ds['total_paid'];
            $dueGrandDue += $ds['total_due'];
            if ($ds['total_due'] > 0) $dueDueCount++;
        }
        ?>
        <div class="card">
            <div class="card-header">
                <h3>Due Students — <?= getClassNameById($filter_class) ?><?= $filter_sec ? ' ('.getSectionNameById($filter_sec).')' : '' ?></h3>
                <span class="badge badge-danger"><?= $dueDueCount ?> with dues</span>
            </div>
            <div class="card-body">
                <?php if (!empty($dueStudents)): ?>
                <div class="due-stat-grid">
                    <div class="stat-card stat-bg-blue"><div class="stat-label">Total Expected</div><div class="stat-value"><?= number_format($dueGrandExpected, 2) ?></div></div>
                    <div class="stat-card stat-bg-green"><div class="stat-label">Total Paid</div><div class="stat-value"><?= number_format($dueGrandPaid, 2) ?></div></div>
                    <div class="stat-card stat-bg-red"><div class="stat-label">Total Due</div><div class="stat-value"><?= number_format($dueGrandDue, 2) ?></div></div>
                    <div class="stat-card stat-bg-amber"><div class="stat-label">Students</div><div class="stat-value"><?= count($dueStudents) ?></div></div>
                </div>
                <div class="table-wrap">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Roll</th>
                            <th class="text-left">Name</th>
                            <th>Facilities</th>
                            <th>Expected</th>
                            <th>Paid</th>
                            <th>Due</th>
                            <th>%</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($dueStudents as $ds): 
                        $row_url = '?tab=audit&class_id=' . $filter_class . '&section=' . $filter_sec . '&group_id=' . $filter_group . '&year=' . $filter_year . '&payment_status=' . $filter_status . '&student_id=' . $ds['student_id'];
                    ?>
                    <tr class="clickable-row <?= $ds['status'] === 'Due' ? 'row-due' : ($ds['status'] === 'Overpaid' ? 'row-overpaid' : '') ?>" data-href="<?= esc_attr($row_url) ?>" onclick="window.location.href=this.getAttribute('data-href')">
                        <td><?= $ds['roll'] ?></td>
                        <td class="text-left" style="color:var(--primary);font-weight:600;"><?= esc_html($ds['name']) ?></td>
                        <td><?= esc_html($ds['facilities'] ?: '-') ?></td>
                        <td><?= number_format($ds['total_expected'], 2) ?></td>
                        <td><?= number_format($ds['total_paid'], 2) ?></td>
                        <td><strong class="<?= $ds['total_due'] > 0 ? 'cell-unpaid' : 'cell-paid' ?>"><?= number_format($ds['total_due'], 2) ?></strong></td>
                        <td>
                            <div style="display:flex;align-items:center;gap:6px;">
                                <div style="flex:1;height:6px;background:var(--border);border-radius:3px;overflow:hidden;min-width:40px;">
                                    <div style="height:100%;width:<?= $ds['payment_pct'] ?>%;background:<?= $ds['payment_pct'] >= 100 ? 'var(--success)' : ($ds['payment_pct'] > 0 ? 'var(--warning)' : 'var(--danger)') ?>;border-radius:3px;"></div>
                                </div>
                                <span style="font-size:11px;font-weight:600;color:var(--text-secondary);"><?= $ds['payment_pct'] ?>%</span>
                            </div>
                        </td>
                        <td>
                            <span class="badge <?= $ds['status'] === 'Overpaid' ? 'badge-overpaid' : ($ds['status'] === 'Paid' ? 'badge-paid' : ($ds['status'] === 'Partial' ? 'badge-partial' : 'badge-unpaid')) ?>"><?= $ds['status'] ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php else: ?>
                <div class="empty-state"><p>No students found for the selected filters.</p></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ===== Tab: Student Audit (existing per-student view) ===== -->
    <div class="tab-pane <?= $active_tab==='audit'?'active':'' ?>" id="tab-audit">

<div class="unified-layout">
    <!-- Left: Student List -->
    <div class="unified-sidebar">
        <div class="sidebar-header">
            <h4>Students</h4>
            <span class="badge-count"><?= count($students) ?></span>
        </div>
        <div class="sidebar-search">
            <input type="text" id="studentSearch" placeholder="Search by name or roll..." onkeyup="filterStudents()">
        </div>
        <div class="student-list" id="studentList">
            <?php foreach ($students as $std):
                $is_active = ($selected_student == $std->infoStdid);
            ?>
            <a href="?tab=audit&class_id=<?= $filter_class ?>&section=<?= $filter_sec ?>&group_id=<?= $filter_group ?>&year=<?= $filter_year ?>&payment_status=<?= $filter_status ?>&student_id=<?= $std->infoStdid ?>"
                class="student-item <?= $is_active ? 'active' : '' ?>">
                <span class="roll"><?= $std->infoRoll ?></span>
                <span class="name"><?= esc_html($std->stdName) ?></span>
            </a>
            <?php endforeach; ?>
            <?php if (empty($students)): ?>
            <div class="empty-state" style="padding:24px;">
                <div class="empty-icon">😕</div>
                <h4>No students found</h4>
                <p>Try adjusting your filter criteria</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
    function filterStudents() {
        var input = document.getElementById('studentSearch');
        var value = input.value.trim();
        var list = document.getElementById('studentList');
        var items = list.getElementsByClassName('student-item');

        // Check if input contains commas → treat as roll-number filter
        if (value.indexOf(',') > -1) {
            var targetRolls = value.split(',').map(function(r) { return r.trim().toUpperCase(); }).filter(function(r) { return r !== ''; });
            for (var i = 0; i < items.length; i++) {
                var rollEl = items[i].querySelector('.roll');
                var roll = rollEl ? rollEl.textContent.trim().toUpperCase() : '';
                items[i].style.display = targetRolls.indexOf(roll) > -1 ? '' : 'none';
            }
            return;
        }

        // Default: filter by name or roll
        var filter = value.toUpperCase();
        for (var i = 0; i < items.length; i++) {
            var name = items[i].querySelector('.name');
            var roll = items[i].querySelector('.roll');
            var txt = (name ? name.textContent : '') + ' ' + (roll ? roll.textContent : '');
            items[i].style.display = txt.toUpperCase().indexOf(filter) > -1 ? '' : 'none';
        }
    }
    </script>

    <!-- Right: Student Details -->
    <div class="unified-main">
        <?php if ($studentInfo): ?>
            <!-- ===== Student Info Card ===== -->
            <div class="student-info-card">
                <div class="info-header">
                    <div>
                        <h3 class="student-name"><?= esc_html($studentInfo->stdName) ?></h3>
                        <div class="student-meta">
                            <span>Roll: <strong><?= $studentInfo->infoRoll ?></strong></span>
                            <span>ID: <strong><?= $selected_student ?></strong></span>
                            <span>Class: <strong><?= getClassNameById($filter_class) ?></strong></span>
                            <span>Section: <strong><?= getSectionNameById($studentInfo->infoSection) ?: 'N/A' ?></strong></span>
                            <span>Facilities: <strong><?= $studentInfo->facilities ?: 'None' ?></strong></span>
                        </div>
                    </div>
                    <button class="btn-xs btn-claim" onclick="openClaimModal()" title="Claim a payment that was recorded under wrong student info">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                        Paid to Wrong Student?
                    </button>
                </div>
            </div>

            <!-- ===== Fund Stats ===== -->
            <?php
            // Determine payment percentage color
            $pct_class = 'low';
            if ($payment_pct >= 75) $pct_class = 'high';
            elseif ($payment_pct >= 40) $pct_class = 'medium';
            ?>
            <div class="stat-grid">
                <div class="stat-card stat-bg-blue">
                    <div class="stat-icon">💰</div>
                    <div class="stat-label">Total Expected Fees</div>
                    <div class="stat-value"><?= number_format($total_expected, 2) ?></div>
                </div>
                <div class="stat-card stat-bg-green">
                    <div class="stat-icon">✅</div>
                    <div class="stat-label">Total Collections</div>
                    <div class="stat-value"><?= number_format($fundStats['collection_total'], 2) ?></div>
                </div>
                <div class="stat-card stat-bg-red">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-label">Total Due</div>
                    <div class="stat-value"><?= number_format($total_due, 2) ?></div>
                </div>
                <div class="stat-card stat-bg-teal">
                    <div class="stat-icon">💳</div>
                    <div class="stat-label">PayStation Paid</div>
                    <div class="stat-value"><?= number_format($fundStats['paystation_paid_total'], 2) ?></div>
                </div>
                <!-- <div class="stat-card stat-bg-purple">
                    <div class="stat-icon">📋</div>
                    <div class="stat-label">Total Paid</div>
                    <div class="stat-value"><?= number_format($total_paid, 2) ?></div>
                </div> -->
                <div class="stat-card stat-bg-orange">
                    <div class="stat-icon">🔄</div>
                    <div class="stat-label">Over Payments</div>
                    <div class="stat-value"><?= number_format($fundStats['over_payment'], 2) ?></div>
                </div>
            </div>

            <?php
            // Reconciliation: check if summary-based total_paid differs from collection total
            $reconciliation_diff = abs($total_paid - $fundStats['collection_total']);
            $reconciliation_note = '';
            $reconciliation_type = 'match';
            if ($reconciliation_diff > 0.01 && $fundStats['collection_total'] > 0) {
                if ($fundStats['collection_total'] > $total_paid) {
                    $reconciliation_note = sprintf(
                        'Collection records (%.2f) exceed summary-based total paid (%.2f) by %.2f — payments may exist in collection logs but not yet synced to fee summary tables (ct_student_*_fee_summary).',
                        $fundStats['collection_total'], $total_paid, $reconciliation_diff
                    );
                    $reconciliation_type = 'collection_higher';
                } else {
                    $reconciliation_note = sprintf(
                        'Summary-based total paid (%.2f) exceeds collection records (%.2f) by %.2f — some summary entries may lack corresponding collection records.',
                        $total_paid, $fundStats['collection_total'], $reconciliation_diff
                    );
                    $reconciliation_type = 'summary_higher';
                }
            }
            ?>

            <!-- ===== PayStation Transactions ===== -->
            <?php if (!empty($allPaystationTxns)):
                // Build lookup of current-year transaction IDs (from $paystationTxns)
                $current_year_txn_ids = array();
                foreach ($paystationTxns as $pt) {
                    $current_year_txn_ids[$pt['payment_id']] = true;
                }
                // Build flagged invoice map for mismatch indicators (using $paystationTxns for calculations)
                $flagged_invoices = array();
                $inv_counts = array();
                $coll_txn_ids = array();
                foreach ($paystationTxns as $txn) {
                    $inv = $txn['invoice_number'];
                    if (!empty($inv)) $inv_counts[$inv][] = $txn;
                }
                foreach ($collectionRecords as $cr) {
                    if (!empty($cr['transaction_id'])) $coll_txn_ids[$cr['transaction_id']] = true;
                }
                foreach ($inv_counts as $inv => $txns) {
                    if (count($txns) > 1) {
                        foreach ($txns as $t) {
                            $flagged_invoices[$t['payment_id']] = 'Duplicate invoice: ' . $inv;
                        }
                    }
                }
                foreach ($paystationTxns as $txn) {
                    if ($txn['status'] === 'paid' && !empty($txn['transaction_id']) && !isset($coll_txn_ids[$txn['transaction_id']])) {
                        if (!isset($flagged_invoices[$txn['payment_id']])) {
                            $flagged_invoices[$txn['payment_id']] = 'Paid but no collection record';
                        }
                    }
                }
                $ps_total_amount = 0;
                $ps_paid_amount = 0;
                foreach ($paystationTxns as $txn) {
                    $amt = floatval($txn['total_amount']);
                    $ps_total_amount += $amt;
                    if ($txn['status'] === 'paid') $ps_paid_amount += $amt;
                }
                // Count transactions from other years
                $other_year_count = 0;
                foreach ($allPaystationTxns as $txn) {
                    if (!isset($current_year_txn_ids[$txn['payment_id']])) {
                        $other_year_count++;
                    }
                }
            ?>
            <div class="card">
                <div class="card-header">
                    <h3>PayStation Transactions</h3>
                </div>
                <div class="card-body">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Period</th>
                                <th>Transaction ID</th>
                                <th>Phone</th>
                                <th>Details</th>
                                <th style="width:80px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($allPaystationTxns as $txn):
                            $is_current_year = isset($current_year_txn_ids[$txn['payment_id']]);
                            $is_flagged = isset($flagged_invoices[$txn['payment_id']]);
                        ?>
                        <tr<?= $is_flagged ? ' class="table-danger"' : '' ?>>
                            <td><?= esc_html($txn['invoice_number']) ?></td>
                            <td><?= number_format(floatval($txn['total_amount']), 2) ?></td>
                            <td><span class="badge badge-<?= $txn['status'] ?>"><?= ucfirst($txn['status']) ?></span></td>
                            <td><?= $txn['payment_date'] ? date('d-m-Y H:i', strtotime($txn['payment_date'])) : date('d-m-Y H:i', strtotime($txn['created_at'])) ?></td>
                            <td><span class="badge badge-<?= $is_current_year ? 'success' : 'secondary' ?>" style="font-size:10px;"><?= $is_current_year ? $filter_year : ($txn['payment_date'] ? date('Y', strtotime($txn['payment_date'])) : date('Y', strtotime($txn['created_at']))) ?></span></td>
                            <td style="font-family:monospace;font-size:12px;"><?= !empty($txn['transaction_id']) ? esc_html($txn['transaction_id']) : '<span style="color:#999;">—</span>' ?></td>
                            <td>
                                <?php
                                $sd = json_decode($txn['student_data'], true);
                                $phone = ($sd && !empty($sd['cust_phone'])) ? esc_html($sd['cust_phone']) : '<span style="color:#999;">—</span>';
                                echo $phone;
                                ?>
                            </td>
                            <td>
                                <?php
                                $ps_data = json_decode($txn['paystation_response'], true);
                                if ($ps_data && isset($ps_data['message'])) {
                                    echo '<span">' . esc_html($ps_data['message']) . '</span>';
                                }
                                if ($is_flagged) {
                                    echo '<br><small style="color:#dc2626;">⚠ ' . esc_html($flagged_invoices[$txn['payment_id']]) . '</small>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($txn['status'] === 'paid'): 
                                    $refund_sd = json_decode($txn['student_data'], true);
                                    $refund_phone = ($refund_sd && !empty($refund_sd['cust_phone'])) ? esc_js($refund_sd['cust_phone']) : '';
                                ?>
                                <button class="btn-xs btn-refund" onclick="openRefundModal(
                                    '<?= esc_js($txn['payment_id']) ?>',
                                    '<?= esc_js($txn['invoice_number']) ?>',
                                    '<?= floatval($txn['total_amount']) ?>',
                                    '<?= esc_js($txn['transaction_id'] ?? '') ?>',
                                    '<?= intval($txn['student_id']) ?>',
                                    '<?= $refund_phone ?>'
                                )" title="Refund this payment">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9"/><polyline points="12 3 21 3 21 12"/><path d="M12 7v5l3 3"/></svg>
                                    Refund
                                </button>
                                <?php elseif ($txn['status'] !== 'paid' && $txn['status'] !== 'cancelled'): ?>
                                <button class="btn-xs btn-claim" onclick="openClaimItModal(
                                    '<?= esc_js($txn['payment_id']) ?>',
                                    '<?= esc_js($txn['invoice_number']) ?>',
                                    '<?= floatval($txn['total_amount']) ?>',
                                    '<?= esc_js($txn['transaction_id'] ?? '') ?>',
                                    '<?= intval($txn['student_id']) ?>'
                                )" title="Mark as paid and add to collection">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                                    Claim It
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td></td>
                            <td><strong>Total</strong></td>
                            <td><strong><?= number_format($ps_total_amount, 2) ?></strong></td>
                            <td colspan="7">
                                Paid (current year): <span class="cell-paid"><?= number_format($ps_paid_amount, 2) ?></span>
                                &nbsp;|&nbsp; Pending (current year): <span class="cell-unpaid"><?= number_format($ps_total_amount - $ps_paid_amount, 2) ?></span>
                                &nbsp;|&nbsp; Current year txns: <strong><?= count($paystationTxns) ?></strong>
                                &nbsp;|&nbsp; All txns: <strong><?= count($allPaystationTxns) ?></strong>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>



<!-- ===== Claim It Payment Modal (mark non-paid PayStation txn as paid) ===== -->
<div class="modal-overlay" id="claimItModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Claim PayStation Payment</h3>
            <button class="modal-close" onclick="closeClaimItModal()">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Transaction info summary -->
            <div class="transfer-info" id="claimItInfo">
                <div><span class="ti-label">Payment ID</span><div class="ti-value" id="ciPaymentId">-</div></div>
                <div><span class="ti-label">Invoice</span><div class="ti-value" id="ciInvoice">-</div></div>
                <div><span class="ti-label">Amount</span><div class="ti-value" id="ciAmount">-</div></div>
                <div><span class="ti-label">Student ID</span><div class="ti-value" id="ciStudentId">-</div></div>
            </div>

            <!-- Success / Error messages -->
            <div class="transfer-success" id="claimItSuccess">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <span>Payment claimed and added to collection successfully!</span>
            </div>
            <div class="transfer-error" id="claimItError"></div>

            <!-- Claim form -->
            <div class="modal-form" id="claimItForm">
                <p style="font-size:13px;color:var(--text-secondary);margin:0 0 16px;">
                    This PayStation transaction is not yet marked as paid. Confirm the details below to mark it as paid and add it to the student's collection records.
                </p>

                <div class="form-row">
                    <div class="form-group" style="flex:2;">
                        <label>Transaction ID <span style="color:#dc2626;">*</span></label>
                        <input type="text" id="claimItTransactionId" placeholder="Enter transaction reference number" style="width:100%;font-family:monospace;font-size:13px;" required autocomplete="off">
                        <div id="claimItTxnIdStatus" style="font-size:12px;margin-top:4px;min-height:18px;"></div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Month <span style="color:#dc2626;">*</span></label>
                        <select id="claimItMonth" required>
                            <option value="">Select Month</option>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= $m == date('n') ? 'selected' : '' ?>><?= $monthNames[$m] ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Year <span style="color:#dc2626;">*</span></label>
                        <select id="claimItYear" required>
                            <?php for ($y = current_time('Y') - 2; $y <= current_time('Y') + 1; $y++): ?>
                            <option value="<?= $y ?>" <?= $y == $filter_year ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>

                <button class="btn-transfer-submit" id="btnClaimItSubmit" onclick="reviewClaimItPayment()" style="width:100%;margin-top:8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    Review &amp; Confirm
                </button>

                <div class="transfer-loading" id="claimItLoading">
                    <div class="spinner"></div>
                    <span>Processing...</span>
                </div>
            </div>

            <!-- ===== Confirmation Step ===== -->
            <div class="modal-form" id="claimItConfirm" style="display:none;">
                <p style="font-size:14px;font-weight:600;margin:0 0 16px;color:var(--text-primary);">Please review the details before final submission:</p>

                <div class="transfer-info" style="margin-bottom:16px;">
                    <div><span class="ti-label">Payment ID</span><div class="ti-value" id="ciConfirmPaymentId">-</div></div>
                    <div><span class="ti-label">Transaction ID</span><div class="ti-value" id="ciConfirmTxnId">-</div></div>
                    <div><span class="ti-label">Invoice</span><div class="ti-value" id="ciConfirmInvoice">-</div></div>
                    <div><span class="ti-label">Amount</span><div class="ti-value" id="ciConfirmAmount">-</div></div>
                    <div><span class="ti-label">Period</span><div class="ti-value" id="ciConfirmPeriod">-</div></div>
                </div>

                <div id="claimItTxnIdWarning" style="display:none;font-size:13px;margin:0 0 12px;padding:12px;background:#fef2f2;border-radius:var(--radius-sm);border:1px solid #dc262633;color:#dc2626;">
                    <strong>⚠️ Duplicate Transaction ID:</strong> This transaction ID already exists in the system. Please verify it is correct before proceeding.
                </div>

                <p style="font-size:13px;color:var(--text-secondary);margin:0 0 16px;padding:12px;background:var(--warning-bg,#fef3c7);border-radius:var(--radius-sm);border:1px solid var(--warning-border,#f59e0b33);">
                    <strong>⚠️ This action will:</strong> Mark the PayStation transaction as <strong>paid</strong>, set the transaction ID, and add the payment to the student\'s monthly collection records. This cannot be automatically undone.
                </p>

                <div style="display:flex;gap:8px;">
                    <button class="btn-transfer-cancel" onclick="backToClaimItForm()" style="flex:1;justify-content:center;">← Edit Details</button>
                    <button class="btn-transfer-submit" id="btnClaimItFinalSubmit" onclick="submitClaimItPayment()" style="flex:2;justify-content:center;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        Confirm &amp; Submit
                    </button>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-transfer-cancel" onclick="closeClaimItModal()">Cancel</button>
        </div>
    </div>
</div>

<!-- ===== Refund Modal ===== -->
<div class="modal-overlay" id="refundModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Refund Payment</h3>
            <button class="modal-close" onclick="closeRefundModal()">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Info summary -->
            <div class="transfer-info" id="refundInfo">
                <div><span class="ti-label">Payment ID</span><div class="ti-value" id="rfPaymentId">-</div></div>
                <div><span class="ti-label">Invoice</span><div class="ti-value" id="rfInvoice">-</div></div>
                <div><span class="ti-label">Amount</span><div class="ti-value" id="rfAmount">-</div></div>
                <div><span class="ti-label">Transaction ID</span><div class="ti-value" id="rfTxnId">-</div></div>
                <div><span class="ti-label">Customer Phone</span><div class="ti-value" id="rfPhone">-</div></div>
                <div><span class="ti-label">Student ID</span><div class="ti-value" id="rfStudentId">-</div></div>
            </div>

            <!-- Error / Success -->
            <div class="transfer-error" id="refundError"></div>
            <div class="transfer-success" id="refundSuccess">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <span>Payment refunded successfully!</span>
            </div>

            <div class="modal-form" id="refundForm">
                <p style="font-size:13px;color:var(--text-secondary);margin:0 0 16px;">
                    This will mark the PayStation transaction as <strong>refunded</strong> and remove the associated collection records. This action cannot be automatically undone.
                </p>

                <div style="margin:16px 0;padding:16px;background:#f9fafb;border-radius:var(--radius-sm);border:1px solid #e5e7eb;">
                    <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;font-size:14px;">
                        <input type="checkbox" id="refundConfirmed" style="margin-top:2px;width:18px;height:18px;cursor:pointer;">
                        <span><strong>I confirm that I have manually refunded this amount to the customer.</strong><br><span style="font-size:12px;color:var(--text-secondary);">By checking this box, you confirm that the refund has been processed outside the system.</span></span>
                    </label>
                </div>

                <button class="btn-transfer-submit" id="btnRefundSubmit" onclick="submitRefundPayment()" style="width:100%;margin-top:8px;" disabled>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9"/><polyline points="12 3 21 3 21 12"/><path d="M12 7v5l3 3"/></svg>
                    Submit Refund
                </button>

                <div class="transfer-loading" id="refundLoading">
                    <div class="spinner"></div>
                    <span>Processing...</span>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn-transfer-cancel" onclick="closeRefundModal()">Cancel</button>
        </div>
    </div>
</div>

<!-- ===== Claim Payment Modal ===== -->
<div class="modal-overlay" id="claimModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Claim Wrong Student Payment</h3>
            <button class="modal-close" onclick="closeClaimModal()">&times;</button>
        </div>
        <div class="modal-body">
            <!-- Success / Error messages -->
            <div class="transfer-success" id="claimSuccess">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <span id="claimSuccessMsg">Payment claimed and transferred successfully!</span>
            </div>
            <div class="transfer-error" id="claimError"></div>

            <!-- Step 1: Claim Form -->
            <div class="modal-form" id="claimForm">
                <p style="font-size:13px;color:var(--text-secondary);margin:0 0 16px;">
                    If you made a payment but it was recorded under wrong student information, enter the details below to claim it.
                </p>

                <div class="form-row">
                    <div class="form-group" style="flex:2;">
                        <label>Transaction ID <span style="color:#dc2626;">*</span></label>
                        <input type="text" id="claimTransactionId" placeholder="Enter PayStation payment/transaction ID" style="width:100%;font-family:monospace;font-size:13px;" autocomplete="off">
                    </div>
                </div>

                <div style="margin:14px 0 8px;font-size:13px;font-weight:600;color:var(--text);">Wrong Student Info (as it was entered during payment):</div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Class</label>
                        <select id="claimWrongClass">
                            <option value="">Select Class</option>
                            <?php
                            $all_classes = $wpdb->get_results("SELECT classid, className FROM ct_class ORDER BY className ASC");
                            foreach ($all_classes as $c) {
                                echo '<option value="'.$c->classid.'">'.$c->className.'</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Section</label>
                        <select id="claimWrongSection">
                            <option value="">Select Section</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Roll</label>
                        <input type="text" id="claimWrongRoll" placeholder="Roll number" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Year</label>
                        <select id="claimWrongYear">
                            <?php for ($y = current_time('Y') - 2; $y <= current_time('Y') + 1; $y++) {
                                echo '<option value="'.$y.'"'.($y == $filter_year ? ' selected' : '').'>'.$y.'</option>';
                            } ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Note (optional)</label>
                        <input type="text" id="claimNote" placeholder="Any additional info..." style="width:100%;">
                    </div>
                </div>

                <button class="btn-transfer-submit" id="btnFindTransaction" onclick="findClaimTransaction()" style="width:100%;margin-top:8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    Find &amp; Verify Transaction
                </button>

                <div class="transfer-loading" id="claimLookupLoading">
                    <div class="spinner"></div>
                    <span>Looking up transaction...</span>
                </div>
            </div>

            <!-- Step 2: Transaction Found - Confirmation -->
            <div class="transfer-confirm" id="claimConfirm" style="display:none;">
                <div class="cf-header">Transaction Found</div>
                <div class="cf-icon">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </div>
                <p class="cf-desc">We found a matching transaction. Please review the details below before claiming.</p>
                <div class="cf-details">
                    <div class="cf-row">
                        <span class="cf-label">Transaction ID</span>
                        <span class="cf-value cf-mono" id="cfFoundTxnId">-</span>
                    </div>
                    <div class="cf-row">
                        <span class="cf-label">Invoice</span>
                        <span class="cf-value" id="cfFoundInvoice">-</span>
                    </div>
                    <div class="cf-row">
                        <span class="cf-label">Amount</span>
                        <span class="cf-value cf-amount" id="cfFoundAmount">-</span>
                    </div>
                    <div class="cf-row">
                        <span class="cf-label">Status</span>
                        <span class="cf-value" id="cfFoundStatus">-</span>
                    </div>
                    <div class="cf-row">
                        <span class="cf-label">Date</span>
                        <span class="cf-value" id="cfFoundDate">-</span>
                    </div>
                    <div class="cf-divider"></div>
                    <div class="cf-row">
                        <span class="cf-label">Currently Recorded To</span>
                        <span class="cf-value" id="cfFoundStudent">-</span>
                    </div>
                    <div class="cf-row" id="cfClaimNoteRow">
                        <span class="cf-label">Your Note</span>
                        <span class="cf-value" id="cfFoundNote">-</span>
                    </div>
                </div>
                <div class="cf-actions">
                    <button class="btn-transfer-cancel" onclick="backToClaimForm()">Back</button>
                    <button class="btn-transfer-submit" onclick="confirmClaimTransfer()">Confirm &amp; Transfer to My Account</button>
                </div>
            </div>

            <div class="transfer-loading" id="claimTransferLoading">
                <div class="spinner"></div>
                <span>Processing claim transfer...</span>
            </div>
        </div>
    </div>
</div>

<script>
// ===== Claim Modal Functions =====
var claimState = {
    transactionId: '',
    foundData: null,
};

function openClaimModal() {
    claimState.transactionId = '';
    claimState.foundData = null;

    // Reset form
    document.getElementById('claimForm').style.display = '';
    document.getElementById('claimConfirm').style.display = 'none';
    document.getElementById('claimSuccess').classList.remove('active');
    document.getElementById('claimError').style.display = 'none';
    document.getElementById('claimError').classList.remove('active');
    document.getElementById('claimLookupLoading').classList.remove('active');
    document.getElementById('claimTransferLoading').classList.remove('active');
    document.getElementById('btnFindTransaction').disabled = false;
    document.getElementById('btnFindTransaction').innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg> Find &amp; Verify Transaction';

    document.getElementById('claimTransactionId').value = '';
    document.getElementById('claimWrongClass').value = '';
    document.getElementById('claimWrongRoll').value = '';
    document.getElementById('claimNote').value = '';
    // Reset section
    document.getElementById('claimWrongSection').innerHTML = '<option value="">Select Section</option>';

    document.getElementById('claimModal').classList.add('open');
}

function closeClaimModal() {
    document.getElementById('claimModal').classList.remove('open');
}

// Load sections on class change for claim form
document.getElementById('claimWrongClass')?.addEventListener('change', function() {
    var classId = this.value;
    var secSelect = document.getElementById('claimWrongSection');
    secSelect.innerHTML = '<option value="">Select Section</option>';
    if (!classId) return;

    var url = '<?= esc_url_raw(rest_url('v1/sections/by-class')) ?>' +
        '?class_id=' + encodeURIComponent(classId);

    fetch(url)
        .then(function(res) { return res.json(); })
        .then(function(data) {
            if (data.success && data.sections) {
                for (var i = 0; i < data.sections.length; i++) {
                    var opt = document.createElement('option');
                    opt.value = data.sections[i].sectionid;
                    opt.textContent = data.sections[i].sectionName;
                    secSelect.appendChild(opt);
                }
            }
        })
        .catch(function(err) {
            console.error('Failed to load sections:', err);
        });
});

function findClaimTransaction() {
    var txnId = document.getElementById('claimTransactionId').value.trim();
    if (!txnId) {
        document.getElementById('claimError').textContent = 'Please enter a Transaction ID.';
        document.getElementById('claimError').style.display = 'block';
        document.getElementById('claimError').classList.add('active');
        return;
    }

    document.getElementById('claimError').style.display = 'none';
    document.getElementById('claimError').classList.remove('active');
    document.getElementById('claimLookupLoading').classList.add('active');
    document.getElementById('btnFindTransaction').disabled = true;

    var url = '<?= esc_url_raw(rest_url('v1/payment/lookup')) ?>' +
        '?transaction_id=' + encodeURIComponent(txnId);

    fetch(url)
        .then(function(res) { return res.json(); })
        .then(function(data) {
            document.getElementById('claimLookupLoading').classList.remove('active');
            document.getElementById('btnFindTransaction').disabled = false;

            if (data.success && data.transaction) {
                claimState.foundData = data.transaction;
                claimState.transactionId = txnId;

                // Fill confirmation details
                document.getElementById('cfFoundTxnId').textContent = data.transaction.payment_id;
                document.getElementById('cfFoundInvoice').textContent = data.transaction.invoice_number || 'N/A';
                document.getElementById('cfFoundAmount').textContent = parseFloat(data.transaction.total_amount).toFixed(2);
                document.getElementById('cfFoundStatus').innerHTML = '<span class="badge badge-' + data.transaction.status + '">' + data.transaction.status.charAt(0).toUpperCase() + data.transaction.status.slice(1) + '</span>';
                document.getElementById('cfFoundDate').textContent = data.transaction.payment_date ? data.transaction.payment_date : 'N/A';
                document.getElementById('cfFoundStudent').textContent = (data.transaction.current_student || 'Unknown') + (data.transaction.current_roll ? ' (Roll: ' + data.transaction.current_roll + ')' : '');

                var note = document.getElementById('claimNote').value.trim();
                document.getElementById('cfFoundNote').textContent = note || '(none)';
                document.getElementById('cfClaimNoteRow').style.display = note ? '' : 'none';

                // Hide form, show confirmation
                document.getElementById('claimForm').style.display = 'none';
                document.getElementById('claimConfirm').style.display = '';
            } else {
                var errMsg = data.message || 'Transaction not found.';
                if (data.data && data.data.message) errMsg = data.data.message;
                document.getElementById('claimError').textContent = 'Lookup failed: ' + errMsg;
                document.getElementById('claimError').style.display = 'block';
                document.getElementById('claimError').classList.add('active');
            }
        })
        .catch(function(err) {
            document.getElementById('claimLookupLoading').classList.remove('active');
            document.getElementById('btnFindTransaction').disabled = false;
            document.getElementById('claimError').textContent = 'Network error: ' + err.message;
            document.getElementById('claimError').style.display = 'block';
            document.getElementById('claimError').classList.add('active');
        });
}

function backToClaimForm() {
    document.getElementById('claimConfirm').style.display = 'none';
    document.getElementById('claimForm').style.display = '';
    document.getElementById('claimError').style.display = 'none';
    document.getElementById('claimError').classList.remove('active');
}

function confirmClaimTransfer() {
    if (!claimState.transactionId || !claimState.foundData) {
        document.getElementById('claimError').textContent = 'No transaction data found. Please start over.';
        document.getElementById('claimError').style.display = 'block';
        document.getElementById('claimError').classList.add('active');
        return;
    }

    var loading = document.getElementById('claimTransferLoading');
    var confirmBox = document.getElementById('claimConfirm');
    var errorBox = document.getElementById('claimError');
    var successBox = document.getElementById('claimSuccess');

    loading.classList.add('active');
    confirmBox.style.display = 'none';
    errorBox.style.display = 'none';
    errorBox.classList.remove('active');
    successBox.classList.remove('active');

    var wrongClass = document.getElementById('claimWrongClass').value;
    var wrongSection = document.getElementById('claimWrongSection').value;
    var wrongRoll = document.getElementById('claimWrongRoll').value.trim();
    var wrongYear = document.getElementById('claimWrongYear').value;
    var note = document.getElementById('claimNote').value.trim();

    fetch('<?= esc_url_raw(rest_url('v1/payment/claim-transfer')) ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            transaction_id: claimState.transactionId,
            current_student_id: <?= $selected_student ?>,
            wrong_class: wrongClass,
            wrong_section: wrongSection,
            wrong_roll: wrongRoll,
            wrong_year: wrongYear,
            note: note,
        })
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        loading.classList.remove('active');
        if (data.success) {
            document.getElementById('claimSuccessMsg').textContent = data.message;
            successBox.classList.add('active');
            // Show a close button
            var closeBtn = document.createElement('button');
            closeBtn.className = 'btn-transfer-submit';
            closeBtn.textContent = 'Close & Reload';
            closeBtn.style.marginTop = '14px';
            closeBtn.onclick = function() { location.reload(); };
            successBox.appendChild(closeBtn);
        } else {
            var errMsg = data.message || 'Claim transfer failed. Please try again.';
            if (data.data && data.data.message) errMsg = data.data.message;
            errorBox.textContent = 'Transfer failed: ' + errMsg;
            errorBox.style.display = 'block';
            errorBox.classList.add('active');
            // Show confirm again for retry
            confirmBox.style.display = '';
        }
    })
    .catch(function(err) {
        loading.classList.remove('active');
        errorBox.textContent = 'Network error: ' + err.message;
        errorBox.style.display = 'block';
        errorBox.classList.add('active');
        confirmBox.style.display = '';
    });
}

function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

// ===== Claim It Modal Functions =====
var claimItState = {
    paymentId: '',
    invoiceNumber: '',
    amount: 0,
    currentTxnId: '',
    studentId: 0,
    txnIdExists: false,
};

function openClaimItModal(paymentId, invoiceNumber, amount, currentTxnId, studentId) {
    claimItState.paymentId = paymentId;
    claimItState.invoiceNumber = invoiceNumber;
    claimItState.amount = parseFloat(amount) || 0;
    claimItState.currentTxnId = currentTxnId || '';
    claimItState.studentId = parseInt(studentId) || 0;

    // Fill info summary
    document.getElementById('ciPaymentId').textContent = paymentId;
    document.getElementById('ciInvoice').textContent = invoiceNumber || 'N/A';
    document.getElementById('ciAmount').textContent = claimItState.amount.toFixed(2);
    document.getElementById('ciStudentId').textContent = studentId || 'N/A';

    // Pre-fill transaction ID
    document.getElementById('claimItTransactionId').value = currentTxnId || '';
    document.getElementById('claimItTxnIdStatus').innerHTML = '';
    claimItState.txnIdExists = false;

    // Reset form
    document.getElementById('claimItForm').style.display = '';
    document.getElementById('claimItConfirm').style.display = 'none';
    document.getElementById('claimItSuccess').classList.remove('active');
    document.getElementById('claimItError').style.display = 'none';
    document.getElementById('claimItError').classList.remove('active');
    document.getElementById('claimItLoading').classList.remove('active');
    document.getElementById('btnClaimItSubmit').disabled = false;
    document.getElementById('btnClaimItSubmit').innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg> Review &amp; Confirm';
    document.getElementById('btnClaimItSubmit').title = '';
    document.getElementById('btnClaimItFinalSubmit').disabled = false;
    document.getElementById('btnClaimItFinalSubmit').title = '';

    document.getElementById('claimItModal').classList.add('open');
}

function closeClaimItModal() {
    document.getElementById('claimItModal').classList.remove('open');
}

// Close modal on overlay click
document.getElementById('claimItModal').addEventListener('click', function(e) {
    if (e.target === this) closeClaimItModal();
});

// ===== Transaction ID existence check =====
var claimItTxnCheckTimeout = null;
var claimItLastTxnCheck = '';

document.getElementById('claimItTransactionId').addEventListener('input', function() {
    var txnId = this.value.trim();
    var statusEl = document.getElementById('claimItTxnIdStatus');

    // Clear previous timeout
    if (claimItTxnCheckTimeout) {
        clearTimeout(claimItTxnCheckTimeout);
        claimItTxnCheckTimeout = null;
    }

    // Clear status if empty
    if (!txnId) {
        statusEl.innerHTML = '';
        claimItState.txnIdExists = false;
        claimItLastTxnCheck = '';
        document.getElementById('btnClaimItSubmit').disabled = false;
        document.getElementById('btnClaimItSubmit').title = '';
        return;
    }

    // Don't re-check the same value
    if (txnId === claimItLastTxnCheck) return;
    claimItLastTxnCheck = '';

    // Show checking indicator
    statusEl.innerHTML = '<span style="color:var(--text-secondary);">Checking...</span>';
    statusEl.style.color = '';

    claimItTxnCheckTimeout = setTimeout(function() {
        claimItTxnCheckTimeout = null;

        var url = '<?= esc_url_raw(rest_url('v1/payment/check-transaction-id')) ?>' +
            '?transaction_id=' + encodeURIComponent(txnId) +
            '&exclude_payment_id=' + encodeURIComponent(claimItState.paymentId);

        fetch(url)
            .then(function(resp) { return resp.json(); })
            .then(function(data) {
                if (data.success && data.exists) {
                    statusEl.innerHTML = '<span style="color:#dc2626;">⚠️ This transaction ID already exists in the system.</span>';
                    claimItState.txnIdExists = true;
                    document.getElementById('btnClaimItSubmit').disabled = true;
                    document.getElementById('btnClaimItSubmit').title = 'This transaction ID is already in use. Please enter a different ID.';
                } else {
                    statusEl.innerHTML = '<span style="color:#16a34a;">✓ Transaction ID is available.</span>';
                    claimItState.txnIdExists = false;
                    document.getElementById('btnClaimItSubmit').disabled = false;
                    document.getElementById('btnClaimItSubmit').title = '';
                }
                claimItLastTxnCheck = txnId;
            })
            .catch(function() {
                statusEl.innerHTML = '';
                claimItState.txnIdExists = false;
                claimItLastTxnCheck = '';
                document.getElementById('btnClaimItSubmit').disabled = false;
                document.getElementById('btnClaimItSubmit').title = '';
            });
    }, 500);
});

function reviewClaimItPayment() {
    var month = document.getElementById('claimItMonth').value;
    var year = document.getElementById('claimItYear').value;
    var txnId = document.getElementById('claimItTransactionId').value.trim();

    // Validate fields
    if (!month) {
        document.getElementById('claimItError').textContent = 'Please select a month.';
        document.getElementById('claimItError').style.display = 'block';
        document.getElementById('claimItError').classList.add('active');
        return;
    }
    if (!year) {
        document.getElementById('claimItError').textContent = 'Please select a year.';
        document.getElementById('claimItError').style.display = 'block';
        document.getElementById('claimItError').classList.add('active');
        return;
    }
    if (!txnId) {
        document.getElementById('claimItError').textContent = 'Transaction ID is required. Please enter the transaction reference number.';
        document.getElementById('claimItError').style.display = 'block';
        document.getElementById('claimItError').classList.add('active');
        return;
    }
    if (claimItState.txnIdExists) {
        document.getElementById('claimItError').textContent = 'This transaction ID already exists. Please enter a different transaction ID.';
        document.getElementById('claimItError').style.display = 'block';
        document.getElementById('claimItError').classList.add('active');
        return;
    }
    if (!claimItState.paymentId) {
        document.getElementById('claimItError').textContent = 'No payment selected. Please try again.';
        document.getElementById('claimItError').style.display = 'block';
        document.getElementById('claimItError').classList.add('active');
        return;
    }

    // Fill confirmation summary
    document.getElementById('ciConfirmPaymentId').textContent = claimItState.paymentId;
    document.getElementById('ciConfirmTxnId').textContent = txnId;
    document.getElementById('ciConfirmInvoice').textContent = claimItState.invoiceNumber || 'N/A';
    document.getElementById('ciConfirmAmount').textContent = claimItState.amount.toFixed(2);
    document.getElementById('ciConfirmPeriod').textContent = month + ' / ' + year;

    // Hide form, show confirmation
    document.getElementById('claimItForm').style.display = 'none';
    document.getElementById('claimItError').style.display = 'none';
    document.getElementById('claimItError').classList.remove('active');
    document.getElementById('claimItConfirm').style.display = '';

    // Disable final submit button if txn ID already exists
    document.getElementById('btnClaimItFinalSubmit').disabled = claimItState.txnIdExists;
    if (claimItState.txnIdExists) {
        document.getElementById('btnClaimItFinalSubmit').title = 'This transaction ID is already in use. Go back and enter a different ID.';
    } else {
        document.getElementById('btnClaimItFinalSubmit').title = '';
    }
}

function backToClaimItForm() {
    document.getElementById('claimItConfirm').style.display = 'none';
    document.getElementById('claimItForm').style.display = '';
    document.getElementById('btnClaimItFinalSubmit').disabled = false;
    document.getElementById('btnClaimItFinalSubmit').title = '';
}

function submitClaimItPayment() {
    var month = document.getElementById('claimItMonth').value;
    var year = document.getElementById('claimItYear').value;
    var txnId = document.getElementById('claimItTransactionId').value.trim();

    // Create a form and submit via POST to the same page (handled by PHP claim_it_payment)
    var form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';

    var fields = {
        action: 'claim_it_payment',
        payment_id: claimItState.paymentId,
        transaction_id: txnId,
        claim_month: month,
        claim_year: year,
    };

    for (var key in fields) {
        if (fields.hasOwnProperty(key)) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = fields[key];
            form.appendChild(input);
        }
    }

    document.body.appendChild(form);
    form.submit();
}

// ===== Refund Modal Functions =====
var refundState = {
    paymentId: '',
    invoiceNumber: '',
    amount: 0,
    transactionId: '',
    studentId: 0,
    phone: '',
};

function openRefundModal(paymentId, invoiceNumber, amount, transactionId, studentId, phone) {
    refundState.paymentId = paymentId;
    refundState.invoiceNumber = invoiceNumber;
    refundState.amount = parseFloat(amount) || 0;
    refundState.transactionId = transactionId || '';
    refundState.studentId = parseInt(studentId) || 0;
    refundState.phone = phone || '';

    // Fill info summary
    document.getElementById('rfPaymentId').textContent = paymentId;
    document.getElementById('rfInvoice').textContent = invoiceNumber || 'N/A';
    document.getElementById('rfAmount').textContent = refundState.amount.toFixed(2);
    document.getElementById('rfTxnId').textContent = transactionId || '—';
    document.getElementById('rfPhone').textContent = phone || '—';
    document.getElementById('rfStudentId').textContent = studentId || '—';

    // Reset form
    document.getElementById('refundForm').style.display = '';
    document.getElementById('refundError').style.display = 'none';
    document.getElementById('refundError').classList.remove('active');
    document.getElementById('refundSuccess').classList.remove('active');
    document.getElementById('refundLoading').classList.remove('active');
    document.getElementById('refundConfirmed').checked = false;
    document.getElementById('btnRefundSubmit').disabled = true;
    document.getElementById('btnRefundSubmit').innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 1 1-9-9"/><polyline points="12 3 21 3 21 12"/><path d="M12 7v5l3 3"/></svg> Submit Refund';

    document.getElementById('refundModal').classList.add('open');
}

function closeRefundModal() {
    document.getElementById('refundModal').classList.remove('open');
}

// Close modal on overlay click
document.getElementById('refundModal').addEventListener('click', function(e) {
    if (e.target === this) closeRefundModal();
});

// Enable submit only when checkbox is checked
document.getElementById('refundConfirmed').addEventListener('change', function() {
    document.getElementById('btnRefundSubmit').disabled = !this.checked;
});

function submitRefundPayment() {
    if (!refundState.paymentId) {
        document.getElementById('refundError').textContent = 'No payment selected. Please try again.';
        document.getElementById('refundError').style.display = 'block';
        document.getElementById('refundError').classList.add('active');
        return;
    }

    var form = document.createElement('form');
    form.method = 'POST';
    form.style.display = 'none';

    var fields = {
        action: 'refund_payment',
        payment_id: refundState.paymentId,
    };

    for (var key in fields) {
        if (fields.hasOwnProperty(key)) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = fields[key];
            form.appendChild(input);
        }
    }

    document.body.appendChild(form);
    form.submit();
}
</script>

            <!-- ===== Exam Fees ===== -->
            <?php if (!empty($examSubHeads)): ?>
            <div class="card">
                <div class="card-header"><h3>Exam Fees</h3></div>
                <div class="card-body">
                    <table class="modern-table">
                        <thead><tr><th class="text-left">Exam</th><th class="text-left">Fee Item</th><th>Amount</th><th>Status</th><th>Date</th><th>Notes</th></tr></thead>
                        <tbody>
                        <?php $exam_total = 0; $exam_paid_total = 0;
                        foreach ($studentExamFees as $ef):
                            $exam_total += $ef['fee'];
                            if ($ef['paid']) $exam_paid_total += $ef['fee'];
                        ?>
                        <tr>
                            <td class="text-left"><?= esc_html($ef['exam_name']) ?></td>
                            <td class="text-left"><?= esc_html($ef['item_name']) ?></td>
                            <td><?= number_format($ef['fee'], 2) ?></td>
                            <td><span class="badge <?= $ef['paid']?'badge-paid':'badge-pending' ?>"><?= $ef['paid']?'Paid':'Unpaid' ?></span></td>
                            <td><?= $ef['date'] ? date('d-m-Y', strtotime($ef['date'])) : '-' ?></td>
                            <td><?= esc_html($ef['notes']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row"><td colspan="2">Total</td><td><?= number_format($exam_total, 2) ?></td><td colspan="3">Paid: <span class="cell-paid"><?= number_format($exam_paid_total, 2) ?></span> &nbsp;|&nbsp; Due: <span class="cell-unpaid"><?= number_format($exam_total - $exam_paid_total, 2) ?></span></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- ===== Yearly Fees ===== -->
            <?php if (!empty($yearlySubHeads)): ?>
            <div class="card">
                <div class="card-header"><h3>Yearly Fees</h3></div>
                <div class="card-body">
                    <table class="modern-table">
                        <thead><tr><th class="text-left">Fee Item</th><th>Amount</th><th>Status</th><th>Date</th><th>Notes</th></tr></thead>
                        <tbody>
                        <?php $yearly_total = 0; $yearly_paid = 0;
                        foreach ($studentYearlyFees as $sid => $yf):
                            $yearly_total += $yf['fee'];
                            if ($yf['paid']) $yearly_paid += $yf['fee'];
                        ?>
                        <tr>
                            <td class="text-left"><?= esc_html($yf['name']) ?></td>
                            <td><?= number_format($yf['fee'], 2) ?></td>
                            <td><span class="badge <?= $yf['paid']?'badge-paid':'badge-pending' ?>"><?= $yf['paid']?'Paid':'Unpaid' ?></span></td>
                            <td><?= $yf['date'] ? date('d-m-Y', strtotime($yf['date'])) : '-' ?></td>
                            <td><?= esc_html($yf['notes']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td>Total</td>
                            <td><?= number_format($yearly_total, 2) ?></td>
                            <td colspan="3">Paid: <span class="cell-paid"><?= number_format($yearly_paid, 2) ?></span> &nbsp;|&nbsp; Due: <span class="cell-unpaid"><?= number_format($yearly_total - $yearly_paid, 2) ?></span></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- ===== Monthly Fees ===== -->
            <?php if (!empty($monthlySubHeads)): ?>
            <div class="card">
                <div class="card-header"><h3>Monthly Fees</h3></div>
                <div class="card-body">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <?php foreach ($monthlySubHeads as $sh): ?>
                                <th><?= esc_html($sh->sub_head_name) ?></th>
                                <?php endforeach; ?>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $grand_monthly = 0;
                        for ($m = 1; $m <= 12; $m++):
                            $md = $studentMonthlyFees[$m];
                            $month_total = $md['total_paid'];
                            $grand_monthly += $month_total;

                            $has_any = false; $all_paid = true;
                            foreach ($md['items'] as $item) {
                                if ($item['fee'] > 0) $has_any = true;
                                if (!$item['paid']) $all_paid = false;
                            }
                            if (!$has_any) continue;
                        ?>
                        <tr>
                            <td class="font-bold"><?= $md['name'] ?></td>
                            <?php foreach ($md['items'] as $item): ?>
                            <td class="<?= $item['paid'] ? 'cell-paid' : 'cell-unpaid' ?>"><?= number_format($item['fee'], 2) ?></td>
                            <?php endforeach; ?>
                            <td class="font-bold"><?= number_format($month_total, 2) ?></td>
                            <td><span class="badge <?= $all_paid?'badge-paid':'badge-pending' ?>"><?= $all_paid?'Paid':'Due' ?></span></td>
                        </tr>
                        <?php endfor; ?>
                        <tr class="total-row">
                            <td>Total</td>
                            <?php foreach ($monthlySubHeads as $sh):
                                $sh_total = 0;
                                for ($m = 1; $m <= 12; $m++) $sh_total += $studentMonthlyFees[$m]['items'][$sh->id]['fee'];
                            ?>
                            <td><?= number_format($sh_total, 2) ?></td>
                            <?php endforeach; ?>
                            <td><?= number_format($grand_monthly, 2) ?></td>
                            <td></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- ===== Other Fees ===== -->
            <?php if (!empty($studentOtherFees)): ?>
            <div class="card">
                <div class="card-header"><h3>Other Fees</h3></div>
                <div class="card-body">
                    <table class="modern-table">
                        <thead><tr><th class="text-left">Fee Item</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                        <?php foreach ($studentOtherFees as $of): ?>
                        <tr>
                            <td class="text-left"><?= esc_html($of['name']) ?></td>
                            <td><?= number_format($of['fee'], 2) ?></td>
                            <td><span class="badge <?= $of['paid']?'badge-paid':'badge-pending' ?>"><?= $of['paid']?'Paid':'Unpaid' ?></span></td>
                            <td><?= $of['date'] ? date('d-m-Y', strtotime($of['date'])) : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- ===== Fee Collection Records ===== -->
            <?php if (!empty($collectionRecords)): ?>
            <div class="card">
                <div class="card-header"><h3>Fee Collection Records</h3><span class="badge badge-secondary">Manual / Online</span></div>
                <div class="card-body">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Month</th>
                                <th>Sub Total</th>
                                <th>Remission</th>
                                <th>Total</th>
                                <th>Method</th>
                                <th>Transaction ID</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($collectionRecords as $cr): ?>
                        <tr>
                            <td><?= $cr['id'] ?></td>
                            <td><?= date('d-m-Y', strtotime($cr['date'])) ?></td>
                            <td><?= isset($monthNames[$cr['month']]) ? $monthNames[$cr['month']] : '-' ?></td>
                            <td><?= number_format(floatval($cr['sub_total']), 2) ?></td>
                            <td><?= number_format(floatval($cr['remission']), 2) ?></td>
                            <td><strong><?= number_format(floatval($cr['total']), 2) ?></strong></td>
                            <td><?= esc_html($cr['payment_method'] ?: 'Manual') ?></td>
                            <td style="font-size:11px;max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= esc_html($cr['transaction_id'] ?: '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <?php if (!$selected_student): ?>
            <div class="empty-state">
                <div class="empty-icon">👈</div>
                <h4>Select a Student</h4>
                <p>Choose a student from the left panel to view their complete fee details</p>
            </div>
            <?php endif; ?>

        <?php elseif ($selected_student): ?>
            <div class="empty-state">
                <div class="empty-icon">🔍</div>
                <h4>Student Not Found</h4>
                <p>No record found for this student in the selected class and year</p>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">👈</div>
                <h4>Select a Student</h4>
                <p>Choose a student from the left panel to view their fee details</p>
            </div>
        <?php endif; ?>
    </div>
</div>

    </div>

    <!-- ===== Tab: Mismatches ===== -->
    <div class="tab-pane <?= $active_tab==='mismatches'?'active':'' ?>" id="tab-mismatches">
        <div class="card">
            <div class="card-header">
                <h3>Payment Mismatch Management</h3>
                <span class="badge badge-danger"><?= count($allMismatches) ?> total</span>
            </div>
            <div class="card-body">
                <?php if (!empty($claim_message)): ?>
                <div class="claim-success">✅ <?= esc_html($claim_message) ?></div>
                <?php endif; ?>
                <?php if (!empty($allMismatches)): ?>
                <div class="table-wrap">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th class="text-left">Student</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th class="text-left">Description</th>
                            <th>Status</th>
                            <th>Detected</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($allMismatches as $mm): 
                        $claim_status = isset($claim_map[$mm['mismatch_id']]) ? $claim_map[$mm['mismatch_id']]['status'] : null;
                        $is_pending = $mm['status'] === 'PENDING' && !$claim_status;
                    ?>
                    <tr>
                        <td><?= $mm['mismatch_id'] ?></td>
                        <td class="text-left">
                            <?php if ($mm['student_id']): ?>
                            <a href="?tab=audit&class_id=<?= $mm['infoClass'] ?: $filter_class ?>&year=<?= $filter_year ?>&student_id=<?= $mm['student_id'] ?>" style="color:var(--primary);text-decoration:none;font-weight:600;">
                                <?= esc_html($mm['stdName'] ?: 'ID: '.$mm['student_id']) ?>
                            </a>
                            <?php if (!empty($mm['infoRoll'])): ?> <small style="color:var(--text-secondary);">(Roll: <?= $mm['infoRoll'] ?>)</small><?php endif; ?>
                            <?php else: ?>
                            <span style="color:var(--text-secondary);">Unknown</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge badge-<?= $mm['mismatch_type'] === 'DUPLICATE_PAYMENT' || $mm['mismatch_type'] === 'PAID_WRONG_STUDENT' ? 'danger' : ($mm['mismatch_type'] === 'OVERPAYMENT' ? 'warning' : 'info') ?>"><?= str_replace('_', ' ', $mm['mismatch_type']) ?></span></td>
                        <td><?= number_format(floatval($mm['amount']), 2) ?></td>
                        <td class="text-left" style="max-width:250px;"><small><?= esc_html($mm['description']) ?></small></td>
                        <td>
                            <?php if ($claim_status): ?>
                                <span class="badge badge-warning">Claimed</span>
                                <small style="display:block;color:var(--text-secondary);font-size:10px;">Review: <?= $claim_status ?></small>
                            <?php else: ?>
                                <span class="badge badge-<?= $mm['status'] === 'PENDING' ? 'pending' : ($mm['status'] === 'CLOSED' ? 'secondary' : 'success') ?>"><?= $mm['status'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:11px;color:var(--text-secondary);"><?= $mm['detected_at'] ? date('d-m-Y', strtotime($mm['detected_at'])) : '-' ?></td>
                        <td>
                            <?php if ($is_pending): ?>
                            <button class="btn-search" style="padding:4px 12px;font-size:12px;min-width:auto;" onclick="toggleClaimForm(<?= $mm['mismatch_id'] ?>)">Claim</button>
                            <?php elseif ($claim_status): ?>
                            <span class="badge badge-secondary">Claimed</span>
                            <?php else: ?>
                            <span class="badge badge-secondary">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if ($is_pending): ?>
                    <tr class="claim-form-row" id="claim-form-<?= $mm['mismatch_id'] ?>">
                        <td colspan="8">
                            <form method="POST" action="" class="claim-inner">
                                <input type="hidden" name="action" value="claim_mismatch">
                                <input type="hidden" name="mismatch_id" value="<?= $mm['mismatch_id'] ?>">
                                <textarea name="claim_reason" placeholder="Enter reason for claiming this mismatch..." required></textarea>
                                <button type="submit" class="btn-submit-claim">Submit Claim</button>
                                <button type="button" class="btn-cancel-claim" onclick="toggleClaimForm(<?= $mm['mismatch_id'] ?>)">Cancel</button>
                            </form>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">✅</div>
                    <h4>No Mismatches Found</h4>
                    <p>All payments appear to be properly reconciled.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div><!-- /tab-content -->

<script>
function toggleClaimForm(id) {
    var row = document.getElementById('claim-form-' + id);
    if (row) row.classList.toggle('open');
}
</script>

<?php else: ?>
<div class="empty-state" style="padding:60px;">
    <div class="empty-icon">📋</div>
    <h4>No Students Found</h4>
    <p>Please select a class and click Search to view the student list</p>
</div>
<?php endif; ?>



		
		
