<?php
/**
* Template Name: Student Fee Audit Report
*/
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
$filter_month   = isset($_POST['month']) ? intval($_POST['month']) : (isset($_GET['month']) ? intval($_GET['month']) : 0);
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
        $monthNames = array("","January","February","March","April","May","June","July","August","September","October","November","December");
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

    // Exam fee summary
    $exams = $wpdb->get_results($wpdb->prepare(
        "SELECT examid, examName FROM ct_exam WHERE examClass = %d AND examsirial != 0 ORDER BY examsirial",
        $filter_class
    ));
    foreach ($exams as $exam) {
        foreach ($examSubHeads as $sh) {
            $fee_row = $wpdb->get_row($wpdb->prepare(
                "SELECT fee, date, notes FROM ct_student_exam_fee_summary
                    WHERE student_id = %d AND sub_head_id = %d AND exam_id = %d AND class_id = %d AND year = %s LIMIT 1",
                $selected_student, $sh->id, $exam->examid, $filter_class, $filter_year
            ));
            if ($fee_row) {
                $studentExamFees[] = array(
                    'exam_name' => $exam->examName,
                    'item_name' => $sh->sub_head_name,
                    'fee'       => floatval($fee_row->fee),
                    'date'      => $fee_row->date,
                    'notes'     => $fee_row->notes,
                );
            }
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

    // PayStation transactions for this student
    $ps_table = $wpdb->prefix . 'paystation_transactions';
    $ps_exists = $wpdb->get_var("SHOW TABLES LIKE '$ps_table'");
    if ($ps_exists) {
        $paystationTxns = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $ps_table WHERE student_id = %d ORDER BY created_at DESC LIMIT 50",
            $selected_student
        ), ARRAY_A);
    }

    // Collection records (manual/online payments)
    $collectionRecords = $wpdb->get_results($wpdb->prepare(
        "SELECT ci.id, ci.year, ci.month, ci.sub_total, ci.remission, ci.total, ci.date, ci.payment_method, ci.transaction_id, ci.created_by,
                cd.sub_head_id, cd.fee, cd.reference,
                sh.sub_head_name
            FROM ct_student_fee_collection_info ci
            LEFT JOIN ct_student_fee_collection_details cd ON cd.info_id = ci.id
            LEFT JOIN ct_sub_head sh ON sh.id = cd.sub_head_id
            WHERE ci.student_id = %d AND ci.year = %s
            ORDER BY ci.date DESC, ci.id DESC
            LIMIT 100",
        $selected_student, $filter_year
    ), ARRAY_A);
}

// ===== Compute Fund Statistics for selected student =====
$fundStats = array(
    'yearly_expected' => 0, 'yearly_paid' => 0,
    'monthly_expected' => 0, 'monthly_paid' => 0,
    'exam_paid' => 0, 'other_paid' => 0,
    'collection_total' => 0,
    'paystation_total' => 0,
    'paystation_paid_total' => 0,
);

if ($selected_student && $filter_class && $filter_year) {
    // Yearly fees from already-collected data
    foreach ($studentYearlyFees as $sid => $yf) {
        $fundStats['yearly_expected'] += $yf['fee'];
        if ($yf['paid']) $fundStats['yearly_paid'] += $yf['fee'];
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

    // Exam fees
    foreach ($studentExamFees as $ef) {
        $fundStats['exam_paid'] += $ef['fee'];
    }

    // Other fees from already-collected data
    foreach ($studentOtherFees as $of) {
        if ($of['paid']) $fundStats['other_paid'] += $of['fee'];
    }

    // Total from collection records
    foreach ($collectionRecords as $cr) {
        $fundStats['collection_total'] += floatval($cr['fee']);
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
    $total_paid = $fundStats['yearly_paid'] + $fundStats['monthly_paid'] + $fundStats['exam_paid'] + $fundStats['other_paid'];
    $total_expected = $fundStats['yearly_expected'] + $fundStats['monthly_expected'];
    $total_due = max(0, $total_expected - $total_paid);
    $payment_pct = $total_expected > 0 ? round(($total_paid / $total_expected) * 100) : 0;

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

    // 2. Overpayment detection (paystation paid > 120% of expected)
    if ($total_expected > 0) {
        $paystation_paid = $fundStats['paystation_paid_total'];
        if ($paystation_paid > $total_expected * 1.2) {
            $potentialMismatches[] = array(
                'type' => 'OVERPAYMENT',
                'severity' => 'medium',
                'title' => 'Possible Overpayment',
                'description' => sprintf('PayStation paid (%.2f) exceeds 120%% of expected (%.2f)', $paystation_paid, $total_expected),
                'count' => 1,
                'amount' => $paystation_paid - $total_expected,
            );
        }
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
    //    Rough heuristic: no matching invoice in collection records
    $collection_invoices = array();
    foreach ($collectionRecords as $cr) {
        if (!empty($cr['invoice_number'])) {
            $collection_invoices[$cr['invoice_number']] = true;
        }
    }
    $unmatched_count = 0;
    foreach ($paystationTxns as $txn) {
        if ($txn['status'] === 'paid' && !empty($txn['invoice_number']) && !isset($collection_invoices[$txn['invoice_number']])) {
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
?>
<style>
:root {
    --primary: #4f6ef7;
    --primary-light: #eef1ff;
    --primary-dark: #3a56d4;
    --success: #22c55e;
    --success-bg: #dcfce7;
    --danger: #ef4444;
    --danger-bg: #fee2e2;
    --warning: #f59e0b;
    --warning-bg: #fef3c7;
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-300: #d1d5db;
    --gray-400: #9ca3af;
    --gray-500: #6b7280;
    --gray-600: #4b5563;
    --gray-700: #374151;
    --gray-800: #1f2937;
    --gray-900: #111827;
    --font: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --radius: 8px;
    --radius-lg: 12px;
    --shadow: 0 1px 3px 0 rgba(0,0,0,0.06), 0 1px 2px -1px rgba(0,0,0,0.06);
    --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -2px rgba(0,0,0,0.05);
    --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.08), 0 4px 6px -4px rgba(0,0,0,0.04);
    --transition: all 0.2s ease;
}

* { box-sizing: border-box; font-family: var(--font); }

body {
    background: #f0f2f5;
    margin: 0;
    padding: 16px;
    min-height: 100vh;
}

/* ===== Page Header ===== */
.page-header {
    position: relative;
    overflow: hidden;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: #fff;
    border-radius: var(--radius-lg);
    padding: 24px 32px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    box-shadow: var(--shadow-md);
}
.page-header::before {
    content: '';
    position: absolute;
    top: -80px;
    right: -80px;
    width: 300px;
    height: 300px;
    background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}
.page-header::after {
    content: '';
    position: absolute;
    bottom: -100px;
    left: -60px;
    width: 250px;
    height: 250px;
    background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}
.page-header > * { position: relative; z-index: 1; }
.page-header h2 {
    margin: 0;
    font-size: 22px;
    font-weight: 700;
    letter-spacing: -0.3px;
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
    border-radius: var(--radius);
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
    background: #fff;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow);
    padding: 20px 24px;
    margin-bottom: 24px;
    border: 1px solid var(--gray-100);
}
.filter-card .filter-title {
    font-size: 14px;
    font-weight: 600;
    color: var(--gray-700);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.filter-form { display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; }
.filter-form .form-group { margin: 0; min-width: 140px; flex: 1 0 auto; }
.filter-form .form-group label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    color: var(--gray-500);
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}
.filter-form .form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1.5px solid var(--gray-200);
    border-radius: var(--radius);
    font-size: 14px;
    color: var(--gray-800);
    background: #fff;
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
    box-shadow: 0 0 0 3px rgba(79,110,247,0.12);
    outline: none;
}
.filter-form .btn-search {
    background: var(--primary);
    color: #fff;
    border: none;
    padding: 8px 24px;
    border-radius: var(--radius);
    font-size: 14px;
    font-weight: 500;
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
    background: #fff;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow);
    border: 1px solid var(--gray-100);
    overflow: hidden;
    max-height: 70vh;
    display: flex;
    flex-direction: column;
}
.unified-sidebar .sidebar-header {
    background: var(--gray-50);
    padding: 12px 16px;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-shrink: 0;
}
.unified-sidebar .sidebar-header h4 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
    color: var(--gray-700);
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
    border-bottom: 1px solid var(--gray-100);
    flex-shrink: 0;
}
.unified-sidebar .sidebar-search input {
    width: 100%;
    padding: 7px 12px 7px 32px;
    border: 1.5px solid var(--gray-200);
    border-radius: var(--radius);
    font-size: 13px;
    transition: var(--transition);
    background: var(--gray-50) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Ccircle cx='11' cy='11' r='8'%3E%3C/circle%3E%3Cline x1='21' y1='21' x2='16.65' y2='16.65'%3E%3C/line%3E%3C/svg%3E") no-repeat 10px center;
}
.unified-sidebar .sidebar-search input:focus {
    border-color: var(--primary);
    background-color: #fff;
    box-shadow: 0 0 0 3px rgba(79,110,247,0.1);
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
    border-bottom: 1px solid var(--gray-50);
    cursor: pointer;
    transition: var(--transition);
    color: var(--gray-700);
    text-decoration: none;
    gap: 10px;
}
.unified-sidebar .student-item:hover { background: var(--gray-50); }
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
    background: var(--gray-100);
    border-radius: 6px;
    font-size: 12px;
    font-weight: 700;
    color: var(--gray-500);
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
    background: #fff;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow);
    border: 1px solid var(--gray-100);
    margin-bottom: 20px;
    overflow: hidden;
}
.card-header {
    padding: 14px 20px;
    border-bottom: 1px solid var(--gray-100);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 8px;
}
.card-header h3 {
    margin: 0;
    font-size: 15px;
    font-weight: 600;
    color: var(--gray-800);
    display: flex;
    align-items: center;
    gap: 8px;
}
.card-body { padding: 0; overflow-x: auto; -webkit-overflow-scrolling: touch; }
.card-body::-webkit-scrollbar { height: 6px; }
.card-body::-webkit-scrollbar-track { background: var(--gray-100); border-radius: 3px; }
.card-body::-webkit-scrollbar-thumb { background: var(--gray-300); border-radius: 3px; }

/* ===== Fund Stats Row ===== */
.fund-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
    gap: 14px;
    margin-bottom: 20px;
}
.fund-stat-card {
    border-radius: var(--radius-lg);
    padding: 18px 20px;
    box-shadow: var(--shadow);
    display: flex;
    align-items: flex-start;
    gap: 14px;
    transition: var(--transition);
    color: #fff;
    border: none;
}
.fund-stat-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-3px); }

/* Colored card variants */
.fund-stat-card.card-blue    { background: linear-gradient(135deg, #4f6ef7 0%, #6366f1 100%); }
.fund-stat-card.card-green   { background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); }
.fund-stat-card.card-red     { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
.fund-stat-card.card-amber   { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
.fund-stat-card.card-teal    { background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%); }
.fund-stat-card.card-purple  { background: linear-gradient(135deg, #a855f7 0%, #9333ea 100%); }
.fund-stat-card.card-orange  { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }

.fund-stat-card .stat-icon {
    width: 42px;
    height: 42px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    flex-shrink: 0;
    background: rgba(255,255,255,0.25);
    backdrop-filter: blur(4px);
}
.fund-stat-card .stat-body { flex: 1; min-width: 0; }
.fund-stat-card .stat-body .stat-amount {
    font-size: 22px;
    font-weight: 800;
    color: #fff;
    line-height: 1.1;
    letter-spacing: -0.5px;
}
.fund-stat-card .stat-body .stat-amount.small { font-size: 18px; }
.fund-stat-card .stat-body .stat-label {
    font-size: 12px;
    color: rgba(255,255,255,0.8);
    margin-top: 3px;
    font-weight: 500;
}
.fund-stat-card .stat-body .stat-pct {
    display: inline-block;
    margin-top: 6px;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 20px;
    background: rgba(255,255,255,0.2);
    color: #fff;
}
.fund-stat-card .stat-body .stat-pct.high,
.fund-stat-card .stat-body .stat-pct.medium,
.fund-stat-card .stat-body .stat-pct.low { background: rgba(255,255,255,0.2); color: #fff; }

/* ===== Student Info Card ===== */
.student-info-card {
    background: #fff;
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow);
    border: 1px solid var(--gray-100);
    margin-bottom: 20px;
    overflow: hidden;
}
.student-info-card .info-header {
    background: linear-gradient(135deg, var(--gray-50) 0%, #fff 100%);
    padding: 20px 24px;
    border-bottom: 1px solid var(--gray-100);
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
}
.student-info-card .info-header .student-name {
    font-size: 20px;
    font-weight: 700;
    color: var(--gray-900);
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
    color: var(--gray-500);
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.student-info-card .info-header .student-meta strong { color: var(--gray-700); }

/* ===== Modern Tables ===== */
.modern-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13px; }
.modern-table thead { background: var(--gray-50); }
.modern-table thead th {
    padding: 10px 14px;
    text-align: center;
    font-weight: 600;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    color: var(--gray-500);
    border-bottom: 2px solid var(--gray-200);
    white-space: nowrap;
}
.modern-table tbody td {
    padding: 10px 14px;
    border-bottom: 1px solid var(--gray-100);
    color: var(--gray-700);
    vertical-align: middle;
}
.modern-table tbody tr:hover { background: var(--gray-50); }
.modern-table tbody tr:last-child td { border-bottom: none; }
.modern-table .text-left { text-align: left; }
.modern-table .text-right { text-align: right; }
.modern-table .text-center { text-align: center; }
.modern-table .font-bold { font-weight: 600; }
.modern-table .total-row { background: var(--gray-50) !important; font-weight: 600; }
.modern-table .total-row td { border-top: 2px solid var(--gray-200); border-bottom: none; padding: 12px 14px; color: var(--gray-800); }
.modern-table .table-danger { background: #fef2f2 !important; }
.modern-table .table-danger td { color: #991b1b; }

/* ===== Status Badges ===== */
.badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.2px;
}
.badge-paid, .badge-success { color: var(--success-bg); }
.badge-pending, .badge-warning { color: var(--warning-bg); }
.badge-unpaid, .badge-danger { color: var(--danger-bg); }
.badge-failed { color: var(--danger-bg); }
.badge-cancelled, .badge-secondary { color: var(--gray-100); }
.badge-partial { color: var(--primary-light); }

/* ===== Cell Status ===== */
.cell-paid { color: #15803d; font-weight: 600; }
.cell-unpaid { color: #b91c1c; font-weight: 600; }

/* ===== Empty State ===== */
.empty-state {
    text-align: center;
    padding: 48px 24px;
    color: var(--gray-400);
}
.empty-state .empty-icon {
    width: 48px;
    height: 48px;
    margin: 0 auto 16px;
    background: var(--gray-100);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}
.empty-state h4 { margin: 0 0 6px; font-size: 16px; color: var(--gray-500); font-weight: 500; }
.empty-state p { margin: 0; font-size: 13px; color: var(--gray-400); }

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
    .page-header { padding: 16px 20px; flex-direction: column; text-align: center; }
    .page-header .header-stats { justify-content: center; }
    .filter-form .form-group { min-width: 100%; }
    .card-header { flex-direction: column; align-items: flex-start; }
    .modern-table { font-size: 12px; }
    .modern-table thead th, .modern-table tbody td { padding: 6px 8px; }
    .student-info-card .info-header { flex-direction: column; text-align: center; }
    .student-info-card .info-header .student-meta { justify-content: center; }
}


</style>

<!-- ===== Page Header ===== -->
<div class="page-header">
    <div>
        <h2>Student Fee Audit Report</h2>
        <p style="margin:4px 0 0;font-size:13px;opacity:0.75;">Payment integrity &amp; reconciliation dashboard</p>
    </div>
    <div class="header-stats">
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

<!-- ===== Filter Card ===== -->
<div class="filter-card">
    <div class="filter-title">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
        Filter Students
    </div>
    <form action="" method="POST" class="filter-form">
        <div class="form-group">
            <label>Class</label>
            <select name="stdclass" class="form-control" required onchange="this.form.submit()">
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
            <select name="sec" class="form-control">
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
                <option value="<?= current_time('Y') ?>"><?= current_time('Y') ?></option>
                <?php for ($y = current_time('Y')-1; $y <= current_time('Y')+1; $y++) {
                    echo '<option value="'.$y.'"'.($filter_year==$y?' selected':'').'>'.$y.'</option>';
                } ?>
            </select>
        </div>
        <div class="form-group">
            <label>Month</label>
            <select name="month" class="form-control">
                <option value="">All Months</option>
                <?php $mn = array(1=>"January","February","March","April","May","June","July","August","September","October","November","December");
                foreach ($mn as $k=>$v) {
                    echo '<option value="'.$k.'"'.($filter_month==$k?' selected':'').'>'.$v.'</option>';
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

<?php if ($filter_class && !empty($students)): ?>
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
            <a href="?class_id=<?= $filter_class ?>&section=<?= $filter_sec ?>&group_id=<?= $filter_group ?>&year=<?= $filter_year ?>&month=<?= $filter_month ?>&payment_status=<?= $filter_status ?>&student_id=<?= $std->infoStdid ?>"
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
        var filter = input.value.toUpperCase();
        var list = document.getElementById('studentList');
        var items = list.getElementsByClassName('student-item');
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
                </div>
            </div>

            <!-- ===== Fund Stats ===== -->
            <?php
            // Determine payment percentage color
            $pct_class = 'low';
            if ($payment_pct >= 75) $pct_class = 'high';
            elseif ($payment_pct >= 40) $pct_class = 'medium';
            ?>
            <div class="fund-stats">
                <div class="fund-stat-card card-blue">
                    <div class="stat-icon">💰</div>
                    <div class="stat-body">
                        <div class="stat-amount"><?= number_format($total_expected, 2) ?></div>
                        <div class="stat-label">Total Expected Fees</div>
                    </div>
                </div>
                <div class="fund-stat-card card-green">
                    <div class="stat-icon">✅</div>
                    <div class="stat-body">
                        <div class="stat-amount"><?= number_format($total_paid, 2) ?></div>
                        <div class="stat-label">Total Paid</div>
                    </div>
                </div>
                <div class="fund-stat-card card-red">
                    <div class="stat-icon">⚠️</div>
                    <div class="stat-body">
                        <div class="stat-amount"><?= number_format($total_due, 2) ?></div>
                        <div class="stat-label">Total Due</div>
                    </div>
                </div>
                <div class="fund-stat-card card-teal">
                    <div class="stat-icon">📅</div>
                    <div class="stat-body">
                        <div class="stat-amount small"><?= number_format($fundStats['yearly_paid'] + $fundStats['monthly_paid'], 2) ?></div>
                        <div class="stat-label">Yearly + Monthly Paid</div>
                    </div>
                </div>
                <div class="fund-stat-card card-purple">
                    <div class="stat-icon">🧾</div>
                    <div class="stat-body">
                        <div class="stat-amount small"><?= number_format($fundStats['exam_paid'] + $fundStats['other_paid'], 2) ?></div>
                        <div class="stat-label">Exam + Other Paid</div>
                    </div>
                </div>
                <div class="fund-stat-card card-orange">
                    <div class="stat-icon">💳</div>
                    <div class="stat-body">
                        <div class="stat-amount small"><?= number_format($fundStats['paystation_paid_total'], 2) ?></div>
                        <div class="stat-label">PayStation Paid</div>
                        <span class="stat-pct" style="font-size:11px;">Total: <?= number_format($fundStats['paystation_total'], 2) ?></span>
                    </div>
                </div>
            </div>

            <!-- ===== Mismatch Alerts ===== -->
            <?php if (!empty($potentialMismatches)): ?>
            <div class="card" style="border-left: 4px solid <?= $mismatch_high_count > 0 ? '#dc2626' : ($mismatch_medium_count > 0 ? '#d97706' : '#6b7280') ?>;">
                <div class="card-header">
                    <h3>🔍 Payment Mismatch Alerts</h3>
                    <?php if ($mismatch_high_count > 0): ?><span class="badge badge-pending" style="margin-left:10px;"><?= $mismatch_high_count ?> high</span><?php endif; ?>
                    <?php if ($mismatch_medium_count > 0): ?><span class="badge badge-secondary" style="margin-left:6px;"><?= $mismatch_medium_count ?> medium</span><?php endif; ?>
                    <?php if ($mismatch_low_count > 0): ?><span class="badge badge-paid" style="margin-left:6px;"><?= $mismatch_low_count ?> low</span><?php endif; ?>
                </div>
                <div class="card-body">
                    <table class="modern-table">
                        <thead><tr><th>Severity</th><th>Type</th><th>Issue</th><th>Count</th><th class="text-right">Amount</th></tr></thead>
                        <tbody>
                        <?php foreach ($potentialMismatches as $mm): ?>
                        <tr>
                            <td>
                                <?php if ($mm['severity'] === 'high'): ?>
                                <span class="badge badge-pending">🔴 High</span>
                                <?php elseif ($mm['severity'] === 'medium'): ?>
                                <span class="badge badge-secondary">🟠 Medium</span>
                                <?php else: ?>
                                <span class="badge badge-paid">⚪ Low</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight:600;"><?= str_replace('_', ' ', $mm['type']) ?></td>
                            <td><?= esc_html($mm['title']) ?><br><small style="color:var(--gray-500);"><?= esc_html($mm['description']) ?></small></td>
                            <td><?= $mm['count'] ?></td>
                            <td class="text-right"><?= $mm['amount'] > 0 ? number_format($mm['amount'], 2) : '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- ===== PayStation Transactions ===== -->
            <?php if (!empty($paystationTxns)):
                // Build flagged invoice map for mismatch indicators
                $flagged_invoices = array();
                $inv_counts = array();
                $coll_invs = array();
                foreach ($paystationTxns as $txn) {
                    $inv = $txn['invoice_number'];
                    if (!empty($inv)) $inv_counts[$inv][] = $txn;
                }
                foreach ($collectionRecords as $cr) {
                    if (!empty($cr['invoice_number'])) $coll_invs[$cr['invoice_number']] = true;
                }
                foreach ($inv_counts as $inv => $txns) {
                    if (count($txns) > 1) {
                        foreach ($txns as $t) {
                            $flagged_invoices[$t['payment_id']] = 'Duplicate invoice: ' . $inv;
                        }
                    }
                }
                foreach ($paystationTxns as $txn) {
                    if ($txn['status'] === 'paid' && !empty($txn['invoice_number']) && !isset($coll_invs[$txn['invoice_number']])) {
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
            ?>
            <div class="card">
                <div class="card-header"><h3>PayStation Transactions</h3></div>
                <div class="card-body">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th style="width:32px;">⚠️</th>
                                <th>Invoice</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Payment ID</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($paystationTxns as $txn):
                            $is_flagged = isset($flagged_invoices[$txn['payment_id']]);
                        ?>
                        <tr<?= $is_flagged ? ' class="table-danger"' : '' ?>>
                            <td style="text-align:center;"><?= $is_flagged ? '⚠️' : '' ?></td>
                            <td><?= esc_html($txn['invoice_number']) ?></td>
                            <td><?= number_format(floatval($txn['total_amount']), 2) ?></td>
                            <td><span class="badge badge-<?= $txn['status'] ?>"><?= ucfirst($txn['status']) ?></span></td>
                            <td><?= $txn['payment_date'] ? date('d-m-Y H:i', strtotime($txn['payment_date'])) : date('d-m-Y H:i', strtotime($txn['created_at'])) ?></td>
                            <td style="font-size:11px;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= esc_attr($txn['payment_id']) ?>"><?= esc_html(substr($txn['payment_id'], 0, 20)) ?>...</td>
                            <td>
                                <?php
                                $ps_data = json_decode($txn['paystation_response'], true);
                                if ($ps_data && isset($ps_data['transaction_status'])) {
                                    echo '<span class="badge badge-secondary">' . esc_html($ps_data['transaction_status']) . '</span>';
                                }
                                if ($txn['status'] == 'paid' && !empty($txn['fee_data'])) {
                                    $fee_data = json_decode($txn['fee_data'], true);
                                    if ($fee_data && isset($fee_data['breakdown'])) {
                                        echo ' <small>(' . count($fee_data['breakdown']) . ' items)</small>';
                                    }
                                }
                                if ($is_flagged) {
                                    echo '<br><small style="color:#dc2626;">⚠ ' . esc_html($flagged_invoices[$txn['payment_id']]) . '</small>';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td></td>
                            <td><strong>Total</strong></td>
                            <td><strong><?= number_format($ps_total_amount, 2) ?></strong></td>
                            <td colspan="4">
                                Paid: <span class="cell-paid"><?= number_format($ps_paid_amount, 2) ?></span>
                                &nbsp;|&nbsp; Pending: <span class="cell-unpaid"><?= number_format($ps_total_amount - $ps_paid_amount, 2) ?></span>
                                &nbsp;|&nbsp; Txns: <strong><?= count($paystationTxns) ?></strong>
                            </td>
                        </tr>
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

            <!-- ===== Exam Fees ===== -->
            <?php if (!empty($studentExamFees)): ?>
            <div class="card">
                <div class="card-header"><h3>Exam Fees</h3></div>
                <div class="card-body">
                    <table class="modern-table">
                        <thead><tr><th class="text-left">Exam</th><th class="text-left">Fee Item</th><th>Amount</th><th>Date</th><th>Notes</th></tr></thead>
                        <tbody>
                        <?php $exam_total = 0;
                        foreach ($studentExamFees as $ef):
                            $exam_total += $ef['fee'];
                        ?>
                        <tr>
                            <td class="text-left"><?= esc_html($ef['exam_name']) ?></td>
                            <td class="text-left"><?= esc_html($ef['item_name']) ?></td>
                            <td><?= number_format($ef['fee'], 2) ?></td>
                            <td><?= $ef['date'] ? date('d-m-Y', strtotime($ef['date'])) : '-' ?></td>
                            <td><?= esc_html($ef['notes']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row"><td colspan="2">Total</td><td><?= number_format($exam_total, 2) ?></td><td colspan="2"></td></tr>
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
            <!-- <?php if (!empty($collectionRecords)): ?>
            <div class="card">
                <div class="card-header"><h3>Fee Collection Records</h3><span class="badge badge-secondary">Manual / Online</span></div>
                <div class="card-body">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Date</th>
                                <th>Month</th>
                                <th class="text-left">Fee Item</th>
                                <th>Amount</th>
                                <th>Sub Total</th>
                                <th>Remission</th>
                                <th>Total</th>
                                <th>Method</th>
                                <th>Transaction ID</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php 
                        $seen_infos = array();
                        foreach ($collectionRecords as $cr):
                            $info_key = $cr['id'];
                            $is_first = !isset($seen_infos[$info_key]);
                            $seen_infos[$info_key] = true;
                        ?>
                        <tr>
                            <td><?= $cr['id'] ?></td>
                            <td><?= date('d-m-Y', strtotime($cr['date'])) ?></td>
                            <td><?= isset($mn[$cr['month']]) ? $mn[$cr['month']] : '-' ?></td>
                            <td class="text-left"><?= esc_html($cr['sub_head_name']) ?></td>
                            <td><?= number_format(floatval($cr['fee']), 2) ?></td>
                            <td><?= $is_first ? number_format(floatval($cr['sub_total']), 2) : '' ?></td>
                            <td><?= $is_first ? number_format(floatval($cr['remission']), 2) : '' ?></td>
                            <td><?= $is_first ? number_format(floatval($cr['total']), 2) : '' ?></td>
                            <td><?= esc_html($cr['payment_method'] ?: 'Manual') ?></td>
                            <td style="font-size:11px;max-width:100px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= esc_html($cr['transaction_id'] ?: '-') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?> -->

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
<?php else: ?>
<div class="empty-state" style="padding:60px;">
    <div class="empty-icon">📋</div>
    <h4>No Students Found</h4>
    <p>Please select a class and click Search to view the student list</p>
</div>
<?php endif; ?>



		
		
