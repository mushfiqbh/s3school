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
$paystationTxns = array();
$collectionRecords = array();

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
?>
<style>
    .unified-layout { display: flex; gap: 15px; }
    .unified-sidebar { width: 320px; min-width: 280px; max-height: 600px; overflow-y: auto; border: 1px solid #ddd; border-radius: 4px; padding: 0; background: #fff; }
    .unified-sidebar .student-item { padding: 8px 12px; border-bottom: 1px solid #f0f0f0; cursor: pointer; transition: background 0.2s; display: block; color: #333; text-decoration: none; }
    .unified-sidebar .student-item:hover { background: #e8f0fe; }
    .unified-sidebar .student-item.active { background: #cce5ff; font-weight: bold; border-left: 3px solid #007bff; }
    .unified-sidebar .student-item .roll { display: inline-block; width: 40px; font-weight: bold; color: #666; }
    .unified-sidebar .student-item .name { margin-left: 5px; }
    .unified-main { flex: 1; min-width: 0; }
    .fee-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    .fee-table th, .fee-table td { border: 1px solid #ddd; padding: 6px 10px; text-align: center; font-size: 13px; }
    .fee-table th { background: #f5f5f5; font-weight: 600; }
    .fee-table .paid-yes { color: #155724; background: #d4edda; }
    .fee-table .paid-no { color: #721c24; background: #f8d7da; }
    .fee-section-title { background: #007bff; color: #fff; padding: 8px 12px; border-radius: 4px; margin: 15px 0 8px 0; font-size: 14px; }
    .status-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: bold; }
    .status-paid { background: #d4edda; color: #155724; }
    .status-pending { background: #fff3cd; color: #856404; }
    .status-failed { background: #f8d7da; color: #721c24; }
    .status-cancelled { background: #e2e3e5; color: #383d41; }
    .filter-form .form-group { margin-right: 8px; margin-bottom: 8px; }
    .unified-header { border-bottom: 2px solid #007bff; padding-bottom: 8px; margin-bottom: 15px; }
    .unified-header h3 { margin: 0; color: #007bff; }
    @media (max-width: 768px) { .unified-layout { flex-direction: column; } .unified-sidebar { width: 100%; max-height: 300px; } }
</style>

<div class="panel panel-info">
    <div class="panel-heading"><h3>Unified Student Report</h3></div>
    <div class="panel-body">
        <form action="" method="POST" class="form-inline filter-form">
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
                <button type="submit" class="btn btn-success form-control">Search</button>
            </div>
        </form>
    </div>
</div>

<?php if ($filter_class && !empty($students)): ?>
<div class="unified-layout">
    <!-- Left: Student List -->
    <div class="unified-sidebar">
        <div style="background:#f5f5f5;padding:8px 12px;font-weight:bold;border-bottom:2px solid #007bff;position:sticky;top:0;">
            Students (<?= count($students) ?>)
        </div>
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
        <div style="padding:20px;text-align:center;color:#999;">No students found</div>
        <?php endif; ?>
    </div>

    <!-- Right: Student Details -->
    <div class="unified-main">
        <?php if ($studentInfo): ?>
            <div class="unified-header">
                <h3><?= esc_html($studentInfo->stdName) ?> (Roll: <?= $studentInfo->infoRoll ?>, ID: <?= $selected_student ?>)</h3>
                <p style="margin:4px 0 0;color:#666;">
                    Class: <?= getClassNameById($filter_class) ?> |
                    Section: <?= getSectionNameById($studentInfo->infoSection) ?> |
                    Facilities: <?= $studentInfo->facilities ?: 'None' ?>
                </p>
            </div>

            <!-- === YEARLY FEES === -->
            <?php if (!empty($yearlySubHeads)): ?>
            <div class="fee-section-title">Yearly Fees</div>
            <table class="fee-table">
                <thead><tr><th>Fee Item</th><th>Amount</th><th>Status</th><th>Date</th><th>Notes</th></tr></thead>
                <tbody>
                <?php $yearly_total = 0; $yearly_paid = 0;
                foreach ($studentYearlyFees as $sid => $yf):
                    $yearly_total += $yf['fee'];
                    if ($yf['paid']) $yearly_paid += $yf['fee'];
                ?>
                <tr>
                    <td style="text-align:left"><?= esc_html($yf['name']) ?></td>
                    <td><?= number_format($yf['fee'], 2) ?></td>
                    <td><span class="status-badge <?= $yf['paid']?'status-paid':'status-pending' ?>"><?= $yf['paid']?'Paid':'Unpaid' ?></span></td>
                    <td><?= $yf['date'] ? date('d-m-Y', strtotime($yf['date'])) : '-' ?></td>
                    <td><?= esc_html($yf['notes']) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr style="font-weight:bold;background:#f9f9f9;">
                    <td>Total</td>
                    <td><?= number_format($yearly_total, 2) ?></td>
                    <td colspan="3">Paid: <?= number_format($yearly_paid, 2) ?> | Due: <?= number_format($yearly_total - $yearly_paid, 2) ?></td>
                </tr>
                </tbody>
            </table>
            <?php endif; ?>

            <!-- === MONTHLY FEES === -->
            <?php if (!empty($monthlySubHeads)): ?>
            <div class="fee-section-title">Monthly Fees</div>
            <table class="fee-table">
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
                <?php $grand_monthly = 0; $grand_paid_monthly = 0;
                for ($m = 1; $m <= 12; $m++):
                    $md = $studentMonthlyFees[$m];
                    $month_total = $md['total_paid'];
                    $grand_monthly += $month_total;

                    // Determine month status
                    $has_any = false; $all_paid = true;
                    foreach ($md['items'] as $item) {
                        if ($item['fee'] > 0) $has_any = true;
                        if (!$item['paid']) $all_paid = false;
                    }
                    if (!$has_any) continue; // skip empty months
                    if ($all_paid) $grand_paid_monthly += $month_total;
                ?>
                <tr>
                    <td style="font-weight:bold"><?= $md['name'] ?></td>
                    <?php foreach ($md['items'] as $item): ?>
                    <td class="<?= $item['paid']?'paid-yes':'paid-no' ?>"><?= number_format($item['fee'], 2) ?></td>
                    <?php endforeach; ?>
                    <td style="font-weight:bold"><?= number_format($month_total, 2) ?></td>
                    <td><span class="status-badge <?= $all_paid?'status-paid':'status-pending' ?>"><?= $all_paid?'Paid':'Due' ?></span></td>
                </tr>
                <?php endfor; ?>
                <tr style="font-weight:bold;background:#f9f9f9;">
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
            <?php endif; ?>

            <!-- === EXAM FEES === -->
            <?php if (!empty($studentExamFees)): ?>
            <div class="fee-section-title">Exam Fees</div>
            <table class="fee-table">
                <thead><tr><th>Exam</th><th>Fee Item</th><th>Amount</th><th>Date</th><th>Notes</th></tr></thead>
                <tbody>
                <?php $exam_total = 0;
                foreach ($studentExamFees as $ef):
                    $exam_total += $ef['fee'];
                ?>
                <tr>
                    <td><?= esc_html($ef['exam_name']) ?></td>
                    <td><?= esc_html($ef['item_name']) ?></td>
                    <td><?= number_format($ef['fee'], 2) ?></td>
                    <td><?= $ef['date'] ? date('d-m-Y', strtotime($ef['date'])) : '-' ?></td>
                    <td><?= esc_html($ef['notes']) ?></td>
                </tr>
                <?php endforeach; ?>
                <tr style="font-weight:bold;background:#f9f9f9;"><td colspan="2">Total</td><td><?= number_format($exam_total, 2) ?></td><td colspan="2"></td></tr>
                </tbody>
            </table>
            <?php endif; ?>

            <!-- === OTHER FEES === -->
            <?php if (!empty($studentOtherFees)): ?>
            <div class="fee-section-title">Other Fees</div>
            <table class="fee-table">
                <thead><tr><th>Fee Item</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php foreach ($studentOtherFees as $of): ?>
                <tr>
                    <td style="text-align:left"><?= esc_html($of['name']) ?></td>
                    <td><?= number_format($of['fee'], 2) ?></td>
                    <td><span class="status-badge <?= $of['paid']?'status-paid':'status-pending' ?>"><?= $of['paid']?'Paid':'Unpaid' ?></span></td>
                    <td><?= $of['date'] ? date('d-m-Y', strtotime($of['date'])) : '-' ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <!-- === PAYSTATION TRANSACTIONS === -->
            <?php if (!empty($paystationTxns)): ?>
            <div class="fee-section-title">PayStation Transactions</div>
            <table class="fee-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Payment ID</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($paystationTxns as $txn): ?>
                <tr>
                    <td><?= esc_html($txn['invoice_number']) ?></td>
                    <td><?= number_format(floatval($txn['total_amount']), 2) ?></td>
                    <td><span class="status-badge status-<?= $txn['status'] ?>"><?= ucfirst($txn['status']) ?></span></td>
                    <td><?= $txn['payment_date'] ? date('d-m-Y H:i', strtotime($txn['payment_date'])) : date('d-m-Y H:i', strtotime($txn['created_at'])) ?></td>
                    <td style="font-size:11px"><?= esc_html(substr($txn['payment_id'], 0, 20)) ?>...</td>
                    <td>
                        <?php
                        $ps_data = json_decode($txn['paystation_response'], true);
                        if ($ps_data && isset($ps_data['transaction_status'])) {
                            echo 'Status: ' . esc_html($ps_data['transaction_status']);
                        }
                        if ($txn['status'] == 'paid' && !empty($txn['fee_data'])) {
                            $fee_data = json_decode($txn['fee_data'], true);
                            if ($fee_data && isset($fee_data['breakdown'])) {
                                echo '<br><small>' . count($fee_data['breakdown']) . ' item(s)</small>';
                            }
                        }
                        ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <!-- === COLLECTION RECORDS (MANUAL/ONLINE) === -->
            <?php if (!empty($collectionRecords)): ?>
            <div class="fee-section-title">Fee Collection Records (Manual / Online)</div>
            <table class="fee-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Month</th>
                        <th>Fee Item</th>
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
                    <td style="text-align:left"><?= esc_html($cr['sub_head_name']) ?></td>
                    <td><?= number_format(floatval($cr['fee']), 2) ?></td>
                    <td><?= $is_first ? number_format(floatval($cr['sub_total']), 2) : '' ?></td>
                    <td><?= $is_first ? number_format(floatval($cr['remission']), 2) : '' ?></td>
                    <td><?= $is_first ? number_format(floatval($cr['total']), 2) : '' ?></td>
                    <td><?= esc_html($cr['payment_method'] ?: 'Manual') ?></td>
                    <td style="font-size:11px"><?= esc_html($cr['transaction_id'] ?: '-') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>

            <?php if (!$selected_student): ?>
            <div style="text-align:center;padding:40px;color:#999;"><h4>Select a student from the left panel</h4></div>
            <?php endif; ?>

        <?php elseif ($selected_student): ?>
            <div style="text-align:center;padding:40px;color:#999;"><h4>Student not found for the selected class/year</h4></div>
        <?php else: ?>
            <div style="text-align:center;padding:40px;color:#999;"><h4>Select a student from the left panel to view details</h4></div>
        <?php endif; ?>
    </div>
</div>
<?php else: ?>
<div style="text-align:center;padding:40px;color:#999;"><h4>Please select a class and click Search</h4></div>
<?php endif; ?>



		
		
