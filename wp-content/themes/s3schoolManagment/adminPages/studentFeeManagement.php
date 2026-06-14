<?php
/**
* Template Name: Student Fee Management
*/
global $wpdb; global $s3sRedux;
global $admissionFeeSubHeadId;
global $admissionFormSubHeadId;
global $monthlyFeeSubHeadId;
global $examFeeSubHeadId;
global $transportFeeSubHeadId;
global $ictFeeSubHeadId;
global $registrationFeeSubHeadId;
global $coachingFeeSubHeadId;
global $dairySubHeadId;
global $idcardSubHeadId;
function saveLeadger($sub_head_id,$credit,$debit,$reference,$monthly_fee_id,$yearly_fee_id,$exam_fee_id, $date=null,$info_id = null){
	// save ledger table
	global $wpdb;
	if($date == null){
		$date = date('Y-m-d H-i-s');
	}
	$insert = $wpdb->insert(
		'ct_ledger',
		array(
			'sub_head_id' 		=> $sub_head_id,
			'credit' 	=> $credit,
			'debit' => $debit,
			'reference' 	=> $reference,
			'monthly_fee_id' 	=> $monthly_fee_id,
			'yearly_fee_id' 	=> $yearly_fee_id,
			'exam_fee_id' 	=> $exam_fee_id,
			'info_id' 	=> $info_id,
			'date' 	=> $date,
			// 'exam_id' 	=> $_POST['revDate'],
			'created_by' 	=> get_current_user_id(),
			'created_at' 	=> date('Y-m-d H-i-s')
		)
	);
}

function getNameById($id){
	global $wpdb;
	$name_qry = "SELECT stdName FROM ct_student WHERE studentid = $id";
	$name = $wpdb->get_results( $name_qry );
	return @$name[0]->stdName;
}
function getSumOfCollectionDetailsByDate($student_id, $sub_head_id,  $from_date, $to_date){
	global $wpdb;
	$feeInfo = $wpdb->get_results("SELECT ct_student_fee_collection_info.student_id, SUM(ct_student_fee_collection_details.fee) AS fee FROM ct_student_fee_collection_details
			LEFT JOIN ct_student_fee_collection_info ON ct_student_fee_collection_info.id = ct_student_fee_collection_details.info_id
			WHERE  sub_head_id = $sub_head_id  AND student_id = $student_id AND date(ct_student_fee_collection_details.date) >= '$from_date' AND date(ct_student_fee_collection_details.date) <= '$to_date'");
	return @$feeInfo[0]->fee;
}

function getSumOfRemissionFeeByDate($student_id,  $from_date, $to_date){
	global $wpdb;
	$feeInfo = $wpdb->get_results("SELECT SUM(remission) AS fee FROM ct_student_fee_collection_info
			WHERE  student_id = $student_id AND date(date) >= '$from_date' AND date(date) <= '$to_date'");
	return @$feeInfo[0]->fee;
}
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
function getSubHeadNameById($id){
	global $wpdb;
	$name_qry = "SELECT sub_head_name FROM ct_sub_head WHERE id = $id";
	$name = $wpdb->get_results( $name_qry );
	return @$name[0]->sub_head_name;
}

function getFeeAmount($sub_head_id, $class, $year){
	global $wpdb;

	$feesQuery = "SELECT fee FROM ct_student_fee_list WHERE sub_head_id = $sub_head_id AND class_id = $class AND year = '$year' ";

	if(isset($grou) && !empty($grou)){
		$feesQuery .= " AND group_id = $grou";
	}
	$feesQuery .= " ORDER BY id DESC";
	$fees = $wpdb->get_results($feesQuery);
	if($fees){
		$fees = $fees[0]->fee;
	}else{
		$fees = 0;
	}
	return $fees;
}

function getFeeAmountByStudent($student_id, $month = null, $year, $sub_head_id, $class ){
	global $wpdb;
	$list = [];
	$feesQuery = "SELECT fee FROM ct_student_monthly_fee_summary WHERE student_id = $student_id and sub_head_id = $sub_head_id AND class_id = $class AND year = '$year'";

	if(isset($month) && !empty($month)){
		$feesQuery .= " AND month = $month";
	}
	$feesQuery .= " limit 1";
	$fees = $wpdb->get_results($feesQuery);
	if($fees){
		$fees = $fees[0]->fee;
		$paid = true;
	}else{
		$fees = 0;
		$paid = false;
	}
	$list['fees'] = $fees;
	$list['paid'] = $paid;
	return $list;
}

function getYearlyFeeAmountByStudent($student_id, $year, $sub_head_id, $class ){
	global $wpdb;
	global $admissionFeeSubHeadId;
	global $admissionFormSubHeadId;
	$list = [];
	$feesQuery = "SELECT fee FROM ct_student_yearly_fee_summary WHERE student_id = $student_id and sub_head_id = $sub_head_id AND class_id = $class AND year = '$year'";

	$feesQuery .= " limit 1";
	$fees = $wpdb->get_results($feesQuery);
	if($fees){
		$fees = $fees[0]->fee;
		// if($sub_head_id == $admissionFeeSubHeadId){
		// 	$formFeesQuery2 = "SELECT fee FROM ct_student_yearly_fee_summary WHERE student_id = $student_id and sub_head_id = $admissionFormSubHeadId AND class_id = $class AND year = '$year' limit 1";
		// 	$fromFees = $wpdb->get_results($formFeesQuery2);
		// 	if($fromFees){
		// 		$fees += $fromFees[0]->fee;
		// 	}
		// }
		$paid = true;
	}else{
		$fees = 0;
		$paid = false;
	}
	$list['fees'] = $fees;
	$list['paid'] = $paid;
	return $list;
}

function getExamFeeAmountByStudent($student_id, $exam_id, $year, $sub_head_id, $class ){
	global $wpdb;
	$list = [];
	$feesQuery = "SELECT fee FROM ct_student_exam_fee_summary WHERE student_id = $student_id and exam_id = $exam_id and sub_head_id = $sub_head_id AND class_id = $class AND year = '$year'";

	
	$feesQuery .= " limit 1";
	$fees = $wpdb->get_results($feesQuery);
	if($fees){
		$fees = $fees[0]->fee;		
	}else{
		$fees = 0;
	}
	return $fees;
}

function convertNumberToWord($num = false)
    {
        $num = str_replace(array(',', ' '), '' , trim($num));
        if(! $num) {
            return false;
        }
        $num = (int) $num;
        $words = array();
        $list1 = array('', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven',
            'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'
        );
        $list2 = array('', 'ten', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety', 'hundred');
        $list3 = array('', 'thousand', 'million', 'billion', 'trillion', 'quadrillion', 'quintillion', 'sextillion', 'septillion',
            'octillion', 'nonillion', 'decillion', 'undecillion', 'duodecillion', 'tredecillion', 'quattuordecillion',
            'quindecillion', 'sexdecillion', 'septendecillion', 'octodecillion', 'novemdecillion', 'vigintillion'
        );
        $num_length = strlen($num);
        $levels = (int) (($num_length + 2) / 3);
        $max_length = $levels * 3;
        $num = substr('00' . $num, -$max_length);
        $num_levels = str_split($num, 3);
        for ($i = 0; $i < count($num_levels); $i++) {
            $levels--;
            $hundreds = (int) ($num_levels[$i] / 100);
            $hundreds = ($hundreds ? ' ' . $list1[$hundreds] . ' hundred' . ' ' : '');
            $tens = (int) ($num_levels[$i] % 100);
            $singles = '';
            if ( $tens < 20 ) {
                $tens = ($tens ? ' ' . $list1[$tens] . ' ' : '' );
            } else {
                $tens = (int)($tens / 10);
                $tens = ' ' . $list2[$tens] . ' ';
                $singles = (int) ($num_levels[$i] % 10);
                $singles = ' ' . $list1[$singles] . ' ';
            }
            $words[] = $hundreds . $tens . $singles . ( ( $levels && ( int ) ( $num_levels[$i] ) ) ? ' ' . $list3[$levels] . ' ' : '' );
        } //end for loop
        $commas = count($words);
        if ($commas > 1) {
            $commas = $commas - 1;
        }
        return ucwords(implode(' ', $words));
    }

$activeExp = $activeRev = "";
global $absentSubHeadId;
global $lateSubHeadId;
global $cashSubHeadId;
global $monthArray;

echo "<script>
	const absentSubHeadId = $absentSubHeadId;
	const lateSubHeadId = $lateSubHeadId;
</script>";

/* addCategory */
if (isset($_POST['addFeeManagement'])) {
	if ($_POST['addFeeManagement'] == 'Save') {
		foreach ($_POST['subheadid'] AS $key => $val) {
		$insert = $wpdb->insert(
			'ct_student_fee_list',
			array(
				'class_id' 	=> $_POST['stdclass'],
				'group_id' 	=> $_POST['group'],
				'year' 	=> $_POST['stdyear'],
				'sub_head_id' 	=> $val,
				'fee' 	=> $_POST['fee'][$key]?$_POST['fee'][$key]:0
			)
		);
	}
		$message = ms3message($insert, 'Added');
	}elseif ($_POST['addFeeManagement'] == 'update') {
		$update = $wpdb->update(
			'ct_sub_head',
			array(
				'relation_to' 	=> $_POST['relation_to'],
				'head_id' 	=> $_POST['head_id'],
				'status' 	=> $_POST['status'],
				'type' 	=> $_POST['type'],
				'sub_head_name' 	=> $_POST['sub_head_name'],
				'sort_order' 	=> $_POST['sort_order']
			),
			array( 'id' => $_POST['id'])
		);
		$message = ms3message($update, 'Updated');
	}
}

/*Delete Cat*/
if (isset($_POST['delCat'])) {
	$delete = $wpdb->delete( 'ct_sub_head', array( 'id' => $_POST['id'] ) );
	$wpdb->delete( 'ct_ledger', array( 'sub_head_id' => $_POST['id'] ) );
	$message = ms3message($delete, 'Deleted');
}


/*Add Revinew*/


/*Delete Revinew*/
if (isset($_POST['delRevinew'])) {
	$delete = $wpdb->delete( 'ct_revenue', array( 'revId' => $_POST['delRevinew'] ) );
	$message = ms3message($delete, 'Deleted');
}
?>

<?php if ( ! is_admin() ) { get_header(); ?>
<div class="b-layer-main">

	<div class="">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
<?php } ?>
<style>
	.form-group{
		margin-top: 10px !important;
	}
	.pl-10{
		padding-left: 10px !important
	}
</style>
		<div class="row text-center">
			<h2> Fee Management</h2>
			<div class="col-md-9" style="margin: 10px">		
				<a href="?page=studentFeeManagement&view=printReceipt" class="btn btn-secondary pull-right" style="background:#4dc28f;">Print Receipt</a>
				<a href="?page=studentFeeManagement&view=addFee" class="btn btn-secondary pull-right" style="background:#4dc28f;">Fee Collection</a>
			</div>
		</div>
		
	<div class="container-fluid maxAdminpages" style="padding-left: 0">
		
		<div class="col-md-12" style="margin-top: 5px">
	
		<h2 class="resmangh2" style="padding-bottom: 10px;">
			<?php if(wp_get_current_user()->roles[0] == 'um_headmaster'  || wp_get_current_user()->roles[0] == 'administrator'){?>
				<a href="?page=studentFeeManagement" class="btn btn-primary pull-right">Fee Management</a>
			<?php } ?>
			<a href="?page=studentFeeManagement&view=dueList" class="btn btn-primary pull-right">Due List </a>
			<a href="?page=studentFeeManagement&view=monthlyFeeReport" class="btn btn-primary pull-right">Monthly Fee Report </a>
			<a href="?page=studentFeeManagement&view=dailyFeeReport" class="btn btn-primary pull-right">Daily Fee Report </a>
			<a href="?page=studentFeeManagement&view=absentRemission" class="btn btn-primary pull-right">Absent Poor Fund List </a>
			<a href="?page=studentFeeManagement&view=fullFree" class="btn btn-primary pull-right">Full Free Half Free List </a>
			<a href="?page=studentFeeManagement&view=studentFeeYearlySummary" class="btn btn-primary pull-right">Student Fee Yearly Summary Report </a>
			<?php if(wp_get_current_user()->roles[0] != 'um_teachers' || wp_get_current_user()->roles[0] == 'um_accounts'){?>
				<a href="?page=studentFeeManagement&view=transport" class="btn btn-primary pull-right">Transport Fee </a>
				<a href="?page=studentFeeManagement&view=promoted" class="btn btn-primary pull-right">Promoted Admission Fee </a>
				<a href="?page=studentFeeManagement&view=activeCollectionFee" class="btn btn-primary pull-right">Active Collection Fee</a>
				<a href="?page=studentFeeManagement&view=activeExam" class="btn btn-primary pull-right">Active Exam</a>
				<a href="<?= home_url('datewise-fees-information'); ?>" target="_blank" class="btn btn-primary pull-right">Section wise Fee Report</a>
			<?php } else if(wp_get_current_user()->roles[0] == 'um_accounts-user'){?>
				<a href="?page=studentFeeManagement&view=activeCollectionFee" class="btn btn-primary pull-right">Active Collection Fee</a>
				<a href="?page=studentFeeManagement&view=activeExam" class="btn btn-primary pull-right">Active Exam</a>
				<a href="<?= home_url('datewise-fees-information'); ?>" target="_blank" class="btn btn-primary pull-right">Section wise Fee Report</a>
			<?php } ?>

		</h2>
		</div>
		<!-- Show Status message -->
  	<?php if(isset($message)){ ms3showMessage($message); } ?>
  	<?php if(!isset($_GET['view'])) { ?>
  	<?php if( wp_get_current_user()->roles[0] != 'um_teachers'){ ?>
			<div class="panel panel-info">
			  <div class="panel-heading"><h3>Fee Management</h3></div>
			  <div class="panel-body">
			  	<div class="">
				  <form action="" method="POST" class="form-inline">
				  <div class="row pl-10">
				  <div class="form-group">
						<label>Class</label>
						<select id='resultClass' class="form-control" name="stdclass" required>
						<?php

							$classQuery = $wpdb->get_results( "SELECT classid,className FROM ct_class WHERE classid IN (SELECT infoClass FROM ct_studentinfo GROUP BY infoClass ORDER BY className ASC)" );
							echo "<option value=''>Select Class</option>";

							foreach ($classQuery as $class) {
							echo "<option value='".$class->classid."'>".$class->className."</option>";
							}
						?>
						</select>
					</div>

					<!-- <div class="form-group ">
						<label>Section</label>
						<select id="resultSection" class="form-control" name="sec" disabled>
						<option disabled selected>Select Class First</option>
						</select>
					</div> -->

					<div class="form-group ">
						<label>Group</label>
						<select id="resultGroup" class="form-control" name="group">
						<option value="">Select Group</option>
						<?php
							$groups = $wpdb->get_results("SELECT * FROM ct_group");
							foreach ($groups as $groups) {
							$selected = ($edit->infoGroup == $groups->groupId) ? 'selected' : '';
							?>
							<option value='<?= $groups->groupId ?>' <?= $selected ?>>
								<?= $groups->groupName ?>
							</option>
							<?php
							}
						?>
						</select>
					</div>

					<div class="form-group">
						<label>Year</label>
						<select id='addFeeYear' class="form-control" name="stdyear" required disabled>
						<option disabled selected>Select Class First</option>
						</select>
					</div>
				  </div>
				  <br>
				  <div class="row pl-10">
				  <?php 
					  $feeHead = $wpdb->get_results( "SELECT ct_sub_head.*, ct_head.head_name FROM `ct_sub_head` LEFT JOIN  ct_head ON ct_sub_head.head_id = ct_head.id WHERE ct_sub_head.relation_to = '1' AND ct_sub_head.isHidden is null ORDER BY ct_sub_head.sort_order ASC" );
					  foreach ($feeHead AS $key => $income) {

						// print_r( $income);
						// echo '<br>';exit;
						?>
						<div class="form-group">
							<label><?= $income->sub_head_name?></label>
							<input class="form-control" type="hidden" name="subheadid[]" value="<?= $income->id?>">
							<input class="form-control" id="subheadid<?= $income->id ?>" type="number" name="fee[]" placeholder="<?= $income->sub_head_name?>">
						</div>
						
						<?php
					  }
					?>
					<div class="form-group">
						<input class="form-control btn-success" name="addFeeManagement" type="submit" value="Save">
					</div>
				  </div>			  		
					</form>
			  	</div>
				</div>
			</div>
  	<?php } ?>
  	<?php }else if($_GET['view'] == 'absentRemission'){ ?>

		<div class="panel panel-info">
			<div class="panel-heading"><h3>Absent Poor Fund Late Fee List</h3></div>
				
			<div class="panel-body">
			  <form action="" method="POST" class="form-inline">
				  <div class="row pl-10">
				  <div class="form-group">
						<label>Class</label>
						<select id='resultClass' class="form-control" name="stdclass" required>
						<?php

							$classQuery = $wpdb->get_results( "SELECT classid,className FROM ct_class WHERE classid IN (SELECT infoClass FROM ct_studentinfo GROUP BY infoClass ORDER BY className ASC)" );
							echo "<option value=''>Select Class</option>";

							foreach ($classQuery as $class) {
							echo "<option value='".$class->classid."'>".$class->className."</option>";
							}
						?>
						</select>
					</div>

					<div class="form-group ">
						<label>Section</label>
						<select id="resultSection" class="form-control" name="sec" disabled>
						<option disabled selected>Select Class First</option>
						</select>
					</div>

					<div class="form-group ">
						<label>Group</label>
						<select id="resultGroup" class="form-control" name="group">
						<option value="">Select Group</option>
						<?php
							$groups = $wpdb->get_results("SELECT * FROM ct_group");
							foreach ($groups as $groups) {
							$selected = ($edit->infoGroup == $groups->groupId) ? 'selected' : '';
							?>
							<option value='<?= $groups->groupId ?>' <?= $selected ?>>
								<?= $groups->groupName ?>
							</option>
							<?php
							}
						?>
						</select>
					</div>

					<div class="form-group">
						<label>Year</label>
						<select id='resultYear' class="form-control" name="stdyear" required disabled>
						<option disabled selected>Select Class First</option>
						</select>
					</div>
				<br>
					<div class="form-group">
								<label>From Date </label>
								<input id="fee-date" type="date" name="from-date" value="<?php echo date('Y-m-d'); ?>">							
						</div>
						<div class="form-group">
								<label>To Date </label>
								<input id="fee-date" type="date" name="to-date" value="<?php echo date('Y-m-d'); ?>">							
						</div>

						<div class="form-group">
							<input class="form-control btn-success" name="absentRemission" type="submit" value="Search">
							<input class="btn btn-info" type="reset" value="Reset" >
						</div>
					
				  </div>
					</form>
		  </div>

		  <?php
		if(isset($_POST['absentRemission'])):
			$year 	= $_POST['stdyear']; 
			$class 	= $_POST['stdclass'];
			$sec 		= $_POST['sec'];
			$grou 	= $_POST['group'];
			$from_date			= $_POST['from-date'];
			$to_date			= $_POST['to-date'];
			$qry1 = "SELECT infoStdid, infoRoll FROM ct_studentinfo WHERE infoClass = $class AND infoyear = '$year'";
			

			if ($sec != '') { $qry1 .= " AND infoSection = $sec"; }

			if ($grou != '') { $qry1 .= " AND infoGroup = $grou"; }

			$qry1 .= " ORDER BY infoRoll ASC";
		

		$students = $wpdb->get_results( $qry1 ); 
		

		$allLists = [];
		foreach($students as $key => $val){
			$allLists[$key]['name'] = getNameById($val->infoStdid);
			$allLists[$key]['roll'] = $val->infoRoll;
			$allLists[$key]['studentId'] = $val->infoStdid;
			$allLists[$key]['absentFee'] = getSumOfCollectionDetailsByDate($val->infoStdid,$absentSubHeadId, $from_date,$to_date);
			$allLists[$key]['remissionFee'] = getSumOfRemissionFeeByDate($val->infoStdid,$from_date,$to_date);;
			$allLists[$key]['lateFee'] = getSumOfCollectionDetailsByDate($val->infoStdid,$lateSubHeadId, $from_date,$to_date);;

		}
		
		?>
		<div class="container-fluid maxAdminpages">
			
			<div class="row">
				<div class="col-md-12">
			  	<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>
			  </div>
			  <div class="col-md-12">
			  	<div id="printArea" class="col-md-12 printBG" style="width: 8.27in">
					  <div class="printArea" style="margin: 10px 30px 0;">
					  	<style type="text/css">
					  		table tr{ page-break-inside: avoid !important; }
					  		table tr a{ text-decoration: none;color: #000; }
					  		@page { size: 210mm 297mm !important; margin: 0 !important; }
					  	</style>
						  <style>
	
							table th, table td {
								border:1px solid #000;
								padding:0.5em;
							}
							.table-bordered{
								border-collapse: collapse;
							}
						</style>

				  		<div style="text-align: center; position: relative;">
				  			<img height="80px" style="position: absolute;left: 10px;top: 10px" src="<?= $s3sRedux['instLogo']['url'] ?>">
		  					<h2 style="margin:  0;"><b><?= $s3sRedux['institute_name'] ?></b></h2>
					  		<p style="color:#2b5591; font-size: 14px; margin: 0;"><?= $s3sRedux['institute_address'] ?></p>
					  		<p style="margin:  0;">Abesent, Late & Poor Fund Information</p>
					  		<p style="margin:  0;">From: <?=date('d-m-Y', strtotime($from_date))?> To: <?=date('d-m-Y', strtotime($to_date))?></p>
					  		<p style="margin:  0;">Class: <?= getClassNameById($class)?> (Section: <?= getSectionNameById($sec)?>)</p>
				  		</div>
				  		<br>

					  		<table class="table table-bordered" style="width: 100%; text-align: center;">
					  			<tr style="text-align: center;">
					  				<th style=" text-align: center;">No</th>
					  				<th style=" text-align: center;">Student ID</th>
					  				<th style=" text-align: center;">Student Name</th>
					  				<!-- <th style=" text-align: center;">Roll</th> -->
					  				<th style=" text-align: center;">Abesent Fees</th>
					  				<th style=" text-align: center;">Late Fees</th>
					  				<th style=" text-align: center;">Poor Fund Fees</th>
					  			</tr>
								  <?php 
								 	$totalAbsentFee = 0; 
								 	$totalRemissionFee = 0; 
								 	$totalLateFee = 0; 
								  foreach($allLists as $key=>$val){?>
								  <tr>
									<td><?= $key + 1?></td>
									<td><?= $val['roll']?></td>
									<td><?= $val['name']?></td>
									<!-- <td><?= $val['roll']?></td> -->
									<td><?= number_format( $val['absentFee'],2)?></td>
									<td><?= number_format( $val['lateFee'],2)?></td>
									<td><?= number_format( $val['remissionFee'],2)?></td>
								  </tr>
						  		<?php 
									$totalAbsentFee += $val['absentFee']; 
									$totalRemissionFee += $val['remissionFee']; 
									$totalLateFee += $val['lateFee']; 
							} ?>
								  <tr style="font-size: 17px;font-weight: 700;">
								  <td colspan="3">Total</td>
								  <td><?= number_format( $totalAbsentFee,2)?></td>
								  <td><?= number_format( $totalLateFee,2);?></td>
								  <td><?= number_format( $totalRemissionFee,2);?></td>
								  </tr>
					  		</table>
					  		
					  </div>
					</div>
			  </div>
			</div>
			
		</div>

		<?php endif; ?>

  	<?php }else if($_GET['view'] == 'fullFree'){ ?>

		<div class="panel panel-info">
			<div class="panel-heading"><h3>Full Free Half Free List</h3></div>
				
			<div class="panel-body">
			  <form action="" method="POST" class="form-inline">
				  <div class="row pl-10">
				  <div class="form-group">
						<label>Class</label>
						<select id='resultClass' class="form-control" name="stdclass" required>
						<?php

							$classQuery = $wpdb->get_results( "SELECT classid,className FROM ct_class WHERE classid IN (SELECT infoClass FROM ct_studentinfo GROUP BY infoClass ORDER BY className ASC)" );
							echo "<option value=''>Select Class</option>";

							foreach ($classQuery as $class) {
							echo "<option value='".$class->classid."'>".$class->className."</option>";
							}
						?>
						</select>
					</div>

					<div class="form-group ">
						<label>Section</label>
						<select id="resultSection" class="form-control" name="sec" disabled>
						<option disabled selected>Select Class First</option>
						</select>
					</div>

					<div class="form-group ">
						<label>Group</label>
						<select id="resultGroup" class="form-control" name="group">
						<option value="">Select Group</option>
						<?php
							$groups = $wpdb->get_results("SELECT * FROM ct_group");
							foreach ($groups as $groups) {
							$selected = ($edit->infoGroup == $groups->groupId) ? 'selected' : '';
							?>
							<option value='<?= $groups->groupId ?>' <?= $selected ?>>
								<?= $groups->groupName ?>
							</option>
							<?php
							}
						?>
						</select>
					</div>

					<div class="form-group">
						<label>Year</label>
						<select id='resultYear' class="form-control" name="stdyear" required disabled>
						<option disabled selected>Select Class First</option>
						</select>
					</div>
				<br>
					<div class="form-group">
								<label>From Date </label>
								<input id="fee-date" type="date" name="from-date" value="<?php echo date('Y-m-d'); ?>">							
						</div>
						<div class="form-group">
								<label>To Date </label>
								<input id="fee-date" type="date" name="to-date" value="<?php echo date('Y-m-d'); ?>">							
						</div>

						<div class="form-group">
							<input class="form-control btn-success" name="fullFree" type="submit" value="Search">
							<input class="btn btn-info" type="reset" value="Reset" >
						</div>
					
				  </div>
					</form>
		  </div>

		  <?php
		if(isset($_POST['fullFree'])):
			$year 	= $_POST['stdyear']; 
			$class 	= $_POST['stdclass'];
			$sec 		= $_POST['sec'];
			$grou 	= $_POST['group'];
			$from_date			= $_POST['from-date'];
			$to_date			= $_POST['to-date'];

			$qry1 = "SELECT infoStdid, infoRoll, stdName FROM ct_studentinfo
			LEFT JOIN ct_student ON ct_student.studentid = ct_studentinfo.infoStdid 
			WHERE infoClass = $class AND infoyear = '$year' AND facilities IN ('Half free', 'Full free')";
			

			if ($sec != '') { $qry1 .= " AND infoSection = $sec"; }

			if ($grou != '') { $qry1 .= " AND infoGroup = $grou"; }

			$qry1 .= " ORDER BY infoRoll ASC";
		

		$students = $wpdb->get_results( $qry1 ); 
		

		$allLists = [];
		foreach($students as $key => $val){
			$allLists[$key]['name'] = $val->stdName;
			$allLists[$key]['roll'] = $val->infoRoll;
			$allLists[$key]['studentId'] = $val->infoStdid;
			$allLists[$key]['halfFee'] = getSumOfCollectionDetailsByDate($val->infoStdid,$monthlyFeeSubHeadId, $from_date,$to_date);
			$allLists[$key]['fullFee'] = 0;

		}
		
		?>
		<div class="container-fluid maxAdminpages">
			
			<div class="row">
				<div class="col-md-12">
			  	<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>
			  </div>
			  <div class="col-md-12">
			  	<div id="printArea" class="col-md-12 printBG" style="width: 8.27in">
					  <div class="printArea" style="margin: 10px 30px 0;">
					  	<style type="text/css">
					  		table tr{ page-break-inside: avoid !important; }
					  		table tr a{ text-decoration: none;color: #000; }
					  		@page { size: 210mm 297mm !important; margin: 0 !important; }
					  	</style>
						<style>
							
							table th, table td {
								border:1px solid #000;
								padding:0.5em;
							}
							.table-bordered{
								border-collapse: collapse;
							}
						</style>
						<div style="text-align: center; position: relative;">
				  			<img height="80px" style="position: absolute;left: 10px;top: 10px" src="<?= $s3sRedux['instLogo']['url'] ?>">
		  					<h2 style="margin:  0;"><b><?= $s3sRedux['institute_name'] ?></b></h2>
					  		<p style="color:#2b5591; font-size: 14px; margin: 0;"><?= $s3sRedux['institute_address'] ?></p>
					  		<p style="margin:  0;">Full and Half Fee Report</p>
					  		<p style="margin:  0;">From: <?=date('d-m-Y', strtotime($from_date))?> To: <?=date('d-m-Y', strtotime($to_date))?></p>
					  		<p style="margin:  0;">Class: <?= getClassNameById($class)?> (Section: <?= getSectionNameById($sec)?>)</p>
				  		</div>
				  		<br>

					  		<table class="table table-bordered" style="width: 100%; text-align: center;">
					  			<tr style="text-align: center;">
					  				<th style=" text-align: center;">No</th>
					  				<th style=" text-align: center;">Student ID</th>
					  				<th style=" text-align: center;">Student Name</th>
					  				<!-- <th style=" text-align: center;">Roll</th> -->
					  				<th style=" text-align: center;">Half Fees</th>
					  				<th style=" text-align: center;">Full Fees</th>
					  			</tr>
								  <?php 
								 	$totalhalfFee = 0; 
								 	$totalfullFee = 0; 
								  foreach($allLists as $key=>$val){?>
								  <tr>
									<td><?= $key + 1?></td>
									<td><?= $val['roll']?></td>
									<td><?= $val['name']?></td>
									<!-- <td><?= $val['roll']?></td> -->
									<td><?= number_format( $val['halfFee'],2)?></td>
									<td><?= number_format( $val['fullFee'],2)?></td>
								  </tr>
						  		<?php 
									$totalhalfFee += $val['halfFee']; 
									$totalfullFee += $val['fullFee']; 
							} ?>
								  <tr style="font-size: 17px;font-weight: 700;">
								  <td colspan="4">Total</td>
								  <td><?= number_format( $totalhalfFee,2)?></td>
								  <td><?= number_format( $totalfullFee,2);?></td>
								  </tr>
					  		</table>
					  		
					  </div>
					</div>
			  </div>
			</div>
			
		</div>

		<?php endif; ?>
  	<?php }else if($_GET['view'] == 'studentFeeYearlySummary'){ ?>

		<div class="panel panel-info" style="overflow:scroll">
			<div class="panel-heading"><h3>Student Fee Yearly Summary Report</h3></div>
				
			<div class="panel-body">
			  <form action="" method="POST" class="form-inline">
				  <div class="row pl-10">
				  <div class="form-group">
						<label>Class</label>
						<select id='resultClass' class="form-control" name="stdclass" required>
						<?php

							$classQuery = $wpdb->get_results( "SELECT classid,className FROM ct_class WHERE classid IN (SELECT infoClass FROM ct_studentinfo GROUP BY infoClass ORDER BY className ASC)" );
							echo "<option value=''>Select Class</option>";

							foreach ($classQuery as $class) {
							echo "<option value='".$class->classid."'>".$class->className."</option>";
							}
						?>
						</select>
					</div>

					<div class="form-group ">
						<label>Section</label>
						<select id="resultSection" class="form-control" name="sec" disabled>
						<option disabled selected>Select Class First</option>
						</select>
					</div>

					<div class="form-group ">
						<label>Group</label>
						<select id="resultGroup" class="form-control" name="group">
						<option value="">Select Group</option>
						<?php
							$groups = $wpdb->get_results("SELECT * FROM ct_group");
							foreach ($groups as $groups) {
							$selected = ($edit->infoGroup == $groups->groupId) ? 'selected' : '';
							?>
							<option value='<?= $groups->groupId ?>' <?= $selected ?>>
								<?= $groups->groupName ?>
							</option>
							<?php
							}
						?>
						</select>
					</div>

					<div class="form-group">
						<label>Year</label>
						<select id='resultYear' class="form-control" name="stdyear" required disabled>
						<option disabled selected>Select Class First</option>
						</select>
					</div>
					<div class="form-group">
							<input class="form-control btn-success" name="studentFeeYearlySummary" type="submit" value="Search">
							<input class="btn btn-info" type="reset" value="Reset" >
						</div>
				
					
				  </div>
					</form>
		  </div>

		  <?php
		if(isset($_POST['studentFeeYearlySummary'])):
			$year 	= $_POST['stdyear']; 
			$class 	= $_POST['stdclass'];
			$sec 		= $_POST['sec'];
			$grou 	= $_POST['group'];

			$qry1 = "SELECT infoStdid, infoRoll, stdName FROM ct_studentinfo
			LEFT JOIN ct_student ON ct_student.studentid = ct_studentinfo.infoStdid 
			WHERE infoClass = $class AND infoyear = '$year'";
			

			if ($sec != '') { $qry1 .= " AND infoSection = $sec"; }

			if ($grou != '') { $qry1 .= " AND infoGroup = $grou"; }

			$qry1 .= " ORDER BY infoRoll ASC";
		

		$students = $wpdb->get_results( $qry1 ); 


		$examqry = "SELECT examid, examName FROM ct_exam WHERE examClass = $class AND examsirial != 0 ORDER BY examsirial";
		$examInfo = $wpdb->get_results( $examqry ); 
	
		
		
// echo '<pre>';
// print_r($students);exit;
		$allLists = [];
		foreach($students as $key => $val){
			$allLists[$key]['name'] = $val->stdName;
			$allLists[$key]['roll'] = $val->infoRoll;
			$allLists[$key]['studentId'] = $val->infoStdid;
			$allLists[$key]['monthlyFee'] = ["January" => getFeeAmountByStudent($val->infoStdid, 1, $year, $monthlyFeeSubHeadId, $class), 
			"February" => getFeeAmountByStudent($val->infoStdid, 2, $year, $monthlyFeeSubHeadId, $class), 
			"March" => getFeeAmountByStudent($val->infoStdid, 3, $year, $monthlyFeeSubHeadId, $class), 
			"April" => getFeeAmountByStudent($val->infoStdid, 4, $year, $monthlyFeeSubHeadId, $class), 
			"May" => getFeeAmountByStudent($val->infoStdid, 5, $year, $monthlyFeeSubHeadId, $class), 
			"June" => getFeeAmountByStudent($val->infoStdid, 6, $year, $monthlyFeeSubHeadId, $class), 
			"July" => getFeeAmountByStudent($val->infoStdid, 7, $year, $monthlyFeeSubHeadId, $class), 
			"August" => getFeeAmountByStudent($val->infoStdid, 8, $year, $monthlyFeeSubHeadId, $class), 
			"September" => getFeeAmountByStudent($val->infoStdid, 9, $year, $monthlyFeeSubHeadId, $class), 
			"October" => getFeeAmountByStudent($val->infoStdid, 10, $year, $monthlyFeeSubHeadId, $class), 
			"November" => getFeeAmountByStudent($val->infoStdid, 11, $year, $monthlyFeeSubHeadId, $class), 
			"December" => getFeeAmountByStudent($val->infoStdid, 12, $year, $monthlyFeeSubHeadId, $class)];

			$allLists[$key]['transportFee'] = ["January" => getFeeAmountByStudent($val->infoStdid, 1, $year, $transportFeeSubHeadId, $class), 
			"February" => getFeeAmountByStudent($val->infoStdid, 2, $year, $transportFeeSubHeadId, $class), 
			"March" => getFeeAmountByStudent($val->infoStdid, 3, $year, $transportFeeSubHeadId, $class), 
			"April" => getFeeAmountByStudent($val->infoStdid, 4, $year, $transportFeeSubHeadId, $class), 
			"May" => getFeeAmountByStudent($val->infoStdid, 5, $year, $transportFeeSubHeadId, $class), 
			"June" => getFeeAmountByStudent($val->infoStdid, 6, $year, $transportFeeSubHeadId, $class), 
			"July" => getFeeAmountByStudent($val->infoStdid, 7, $year, $transportFeeSubHeadId, $class), 
			"August" => getFeeAmountByStudent($val->infoStdid, 8, $year, $transportFeeSubHeadId, $class), 
			"September" => getFeeAmountByStudent($val->infoStdid, 9, $year, $transportFeeSubHeadId, $class), 
			"October" => getFeeAmountByStudent($val->infoStdid, 10, $year, $transportFeeSubHeadId, $class), 
			"November" => getFeeAmountByStudent($val->infoStdid, 11, $year, $transportFeeSubHeadId, $class), 
			"December" => getFeeAmountByStudent($val->infoStdid, 12, $year, $transportFeeSubHeadId, $class)];
			$allLists[$key]['admissionFee'] =  getYearlyFeeAmountByStudent($val->infoStdid, $year, $admissionFeeSubHeadId, $class);
			$allLists[$key]['admissionFormFee'] =  getYearlyFeeAmountByStudent($val->infoStdid, $year, $admissionFormSubHeadId, $class);
			$allLists[$key]['registrationFee'] =  getYearlyFeeAmountByStudent($val->infoStdid, $year, $registrationFeeSubHeadId, $class);
			$allLists[$key]['ictFee'] =  getYearlyFeeAmountByStudent($val->infoStdid, $year, $ictFeeSubHeadId, $class);
			$allLists[$key]['idcardFee'] =  getYearlyFeeAmountByStudent($val->infoStdid, $year, $idcardSubHeadId, $class);
			$allLists[$key]['dairyFee'] =  getYearlyFeeAmountByStudent($val->infoStdid, $year, $dairySubHeadId, $class);
			$allLists[$key]['remissionFee'] = getSumOfRemissionFeeByDate($val->infoStdid,date('Y').'-01-01',date('Y').'-12-31');

			foreach($examInfo as $key2=>$examval){
				$allLists[$key]['examFee'][$key2] = getExamFeeAmountByStudent($val->infoStdid, $examval->examid, $year, $examFeeSubHeadId, $class);
			}
		}
		
		?>
		<div class="container-fluid maxAdminpages">
			
			<div class="row">
				<div class="col-md-12">
			  	<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>
			  </div>
			  <div class="col-md-12">
			  	<div id="printArea" class="col-md-12 printBG" style="width: 8.27in">
					  <div class="printArea" style="margin: 10px 30px 0;">
					  	<style type="text/css">
					  		table tr{ page-break-inside: avoid !important; }
					  		table tr a{ text-decoration: none;color: #000; }
					  		@page { size: 297mm 210mm !important; margin: 0 !important; }
					  	</style>
						  <style>
	
								table th, table td {
									border:1px solid #000;
									padding:0.5em;
								}
								.table-bordered{
									border-collapse: collapse;
								}
						</style>

				  		<div style="text-align: center; position: relative;">
				  			<img height="80px" style="position: absolute;left: 10px;top: 10px" src="<?= $s3sRedux['instLogo']['url'] ?>">
		  					<h2 style="margin: 0;"><b><?= $s3sRedux['institute_name'] ?></b></h2>
					  		<p style="color:#2b5591; font-size: 14px; margin: 0;"><?= $s3sRedux['institute_address'] ?></p>
					  		<p style="margin: 0;">Yearly Student Fee Summary - <?= $year?></p>
					  		
					  		<p style="margin: 0;">Class: <?= getClassNameById($class)?> (Section: <?= getSectionNameById($sec)?>)</p>
				  		</div>
				  		<br>

					  		<table class="table table-bordered" style="width: 100%; text-align: center;">
					  			<tr style="text-align: center;">
					  				<th style=" text-align: center;">No</th>
					  				<th style=" text-align: center;">Student Name</th>
					  				<th style=" text-align: center;">ID NO</th>
					  				<th style=" text-align: center;">Admission Fees</th>
					  				<th style=" text-align: center;">Session Fee</th>
					  				
									  <?php
										foreach($monthArray as $monthname){
									  ?>
					  					<th style=" text-align: center;"><?= $monthname?></th>
									  <?php }?>
									    <th style=" text-align: center;">Registration Fee</th>
    					  				<th style=" text-align: center;">ICT</th>
    					  				<th style=" text-align: center;">ID Card</th>
    					  				<th style=" text-align: center;">Diary</th>
									  <?php
										foreach($examInfo as $val){
									  ?>
					  					<th style=" text-align: center;"><?= $val->examName?></th>
									  <?php }?>
									  
									  <th style=" text-align: center;">Poor Fund</th>
									  <th style=" text-align: center;">Total</th>
									  <th style=" text-align: center;">Paid</th>
					  			</tr>
								  <?php 
								 	 foreach($allLists as $key=>$val){
										$totalFee = 0; 
										$paid = true;
									?>

								  <tr>
									<td><?= $key + 1?></td>
									<td><?= $val['name']?></td>
									<td><?= $val['roll']?></td>
									<td><?=  $val['admissionFee']['fees']?></td>
									<td><?=  $val['admissionFormFee']['fees']?></td>
									
									<?php
										$totalFee += $val['admissionFee']['fees'];
										$totalFee += $val['admissionFormFee']['fees'];
										$totalFee += $val['registrationFee']['fees'];
										$totalFee += $val['ictFee']['fees'];
										$totalFee += $val['idcardFee']['fees'];
										$totalFee += $val['dairyFee']['fees'];
										$totalFee -= $val['remissionFee'];
										foreach($monthArray as $monthname){
											$totalFee += $val['monthlyFee'][$monthname]['fees'];
											$totalFee += $val['transportFee'][$monthname]['fees'];
											if($val['monthlyFee'][$monthname]['paid'] == false){
												$paid = false;
											}
									  ?>
										<td>
											<?=  $val['monthlyFee'][$monthname]['fees']?><br>
											<?=  $val['transportFee'][$monthname]['fees'] == 0 ? null : $val['transportFee'][$monthname]['fees']?>
										</td>
										
									  <?php }?>
									  
									  <td><?=  $val['registrationFee']['fees']?></td>
    									<td><?=  $val['ictFee']['fees']?></td>
    									<td><?=  $val['idcardFee']['fees']?></td>
    									<td><?=  $val['dairyFee']['fees']?></td>
										<?php
										if($examInfo){
										foreach($val['examFee'] as $key2=>$examval){
											$totalFee += $examval;
										?>
											<td><?=  $examval?></td>
										<?php }}?>
										<td><?=  $val['remissionFee']?></td>
										<td><?=  $totalFee?></td>
										<td>
											<?php if($paid){?>
												<i class="fa fa-check" style="font-size:30px;color:green"></i>
											<?php }else{?>
									  			<i class="fa fa-close" style="font-size:30px;color:red"></i>
										  <?php }?>
										</td>
								  </tr>
						  		<?php 
									
							} ?>
								 
					  		</table>
					  		
					  </div>
					</div>
			  </div>
			</div>
			
		</div>

		<?php endif; ?>
  	<?php }else if($_GET['view'] == 'dueList'){ ?>
		<div class="panel panel-info">
			<div class="panel-heading"><h3>Student Due List</h3></div>
				
			<div class="panel-body">
			  <form action="" method="POST" class="form-inline">
				  <div class="row pl-10">
				  <div class="form-group">
						<label>Class</label>
						<select id='resultClass' class="form-control" name="stdclass" required>
						<?php

							$classQuery = $wpdb->get_results( "SELECT classid,className FROM ct_class WHERE classid IN (SELECT infoClass FROM ct_studentinfo GROUP BY infoClass ORDER BY className ASC)" );
							echo "<option value=''>Select Class</option>";

							foreach ($classQuery as $class) {
							echo "<option value='".$class->classid."'>".$class->className."</option>";
							}
						?>
						</select>
					</div>

					<div class="form-group ">
						<label>Section</label>
						<select id="resultSection" class="form-control" name="sec" disabled>
						<option disabled selected>Select Class First</option>
						</select>
					</div>

					<div class="form-group ">
						<label>Group</label>
						<select id="resultGroup" class="form-control" name="group">
						<option value="">Select Group</option>
						<?php
							$groups = $wpdb->get_results("SELECT * FROM ct_group");
							foreach ($groups as $groups) {
							$selected = ($edit->infoGroup == $groups->groupId) ? 'selected' : '';
							?>
							<option value='<?= $groups->groupId ?>' <?= $selected ?>>
								<?= $groups->groupName ?>
							</option>
							<?php
							}
						?>
						</select>
					</div>

					<div class="form-group">
						<label>Year</label>
						<select id='resultYear' class="form-control" name="stdyear" required disabled>
						<option disabled selected>Select Class First</option>
						</select>
					</div>
					<div class="form-group">
							  <label>Fee Month </label>
							  <select name="fee-month" id="fee-month" required>
									<option value="">Select Month</option>
									<option value="1">January</option>
									<option value="2">February</option>
									<option value="3">March</option>
									<option value="4">April</option>
									<option value="5">May</option>
									<option value="6">June</option>
									<option value="7">July</option>
									<option value="8">August</option>
									<option value="9">September</option>
									<option value="10">October</option>
									<option value="11">November</option>
									<option value="12">December</option>
							  </select>
					  </div>
					  <input type="hidden" id="month_list" name="month_list[]" >
					<!-- <div class="form-group">
						<label>Roll</label>
						<input id="resultRoll" type="text" name="roll" required>
					</div> -->

					<div class="form-group ">
						<label>Fee Category</label>
						<select id="fee-category" class="form-control" name="sub_head_id">
						<option value="">Select Category</option>
						<?php
							$feeHead = $wpdb->get_results( "SELECT ct_sub_head.*, ct_head.head_name FROM `ct_sub_head` LEFT JOIN  ct_head ON ct_sub_head.head_id = ct_head.id WHERE ct_sub_head.relation_to = '1' AND ct_sub_head.isHidden is null ORDER BY ct_sub_head.sort_order ASC" );
							foreach ($feeHead AS $key => $val) {
							
							?>
							<option value='<?= $val->id ?>'>
								<?= $val->sub_head_name ?>
							</option>
							<?php
							}
						?>
						</select>
					</div>
					
				  </div>
				  <div class="row pl-10">
					  
					<div class="form-group">
						<input class="form-control btn-success" name="dueList" type="submit" value="Search">
						<input class="btn btn-info" type="reset" value="Reset" >
					</div>
				  </div>			  		
					</form>
		  </div>







		  <?php
		if(isset($_POST['dueList'])):
			$month 	= $_POST['fee-month'] ?? ''; 
			$year 	= $_POST['stdyear']; 
			$class 	= $_POST['stdclass'];
			$sec 		= $_POST['sec'];
			$grou 	= $_POST['group'];
			$sub_head_id 	= $_POST['sub_head_id'];
			$monthName = $monthArray[$month-1];

			if($sub_head_id != ''){

				// SUB HEAD INFO
				$subHeadQry = "SELECT * FROM ct_sub_head WHERE id = $sub_head_id";
				$subHeadDetails = $wpdb->get_results( $subHeadQry ); 
				$subHeadType = $subHeadDetails[0]->type;


				$qry1 = "SELECT infoStdid, infoRoll FROM ct_studentinfo WHERE infoClass = $class AND infoyear = '$year'";
				// $qry1 = "SELECT infoStdid, infoRoll FROM ct_studentinfo WHERE infoClass = $class AND infoyear = '$year'";
				

				if ($sec != '') { $qry1 .= " AND infoSection = $sec"; }

				if ($grou != '') { $qry1 .= " AND infoGroup = $grou"; }

				// check monthly or others in query
				if($subHeadType == 1){
					$qry1 .= " AND infoStdid NOT IN (SELECT student_id FROM view_fee_collection_with_details  
					WHERE class_id = $class AND year = '$year' AND month= $month AND sub_head_id = $sub_head_id)";
				}else{
					$qry1 .= " AND infoStdid NOT IN (SELECT student_id FROM view_fee_collection_with_details  
					WHERE class_id = $class AND year = '$year' AND sub_head_id = $sub_head_id)";
				}
				if($sub_head_id == $transportFeeSubHeadId){
					$qry1 .= " AND infoStdid IN (SELECT studentid FROM ct_student  
					WHERE transport_required = 1)";
				}
				
				$qry1 .= " ORDER BY infoRoll ASC";
			

			$students = $wpdb->get_results( $qry1 ); 

			

			$allLists = [];
			foreach($students as $key => $val){
				$studentInfo = $wpdb->get_results("SELECT stdName, transport_fee_id, transport_type,transport_required, admission_type, facilities FROM ct_student WHERE studentid = $val->infoStdid");
				$allLists[$key]['name'] = $studentInfo[0]->stdName;
				$allLists[$key]['roll'] = $val->infoRoll;



				// get due by student id
				$feesQuery = "SELECT fee FROM ct_student_fee_list WHERE sub_head_id = $sub_head_id AND class_id = $class AND year = '$year' ";

				// No need section for fee
				// if(isset($section) && !empty($section)){
				// 	$feesQuery .= " AND section = $section";
				// }

				if(isset($grou) && !empty($grou)){
					$feesQuery .= " AND group_id = $grou";
				}
				$feesQuery .= " ORDER BY id DESC";
				$fees = $wpdb->get_results($feesQuery);
				if($fees){
					$fees = $fees[0]->fee;
				}else{
					$fees = 0;
				}
				if($subHeadType == 1){
					// monthly
					$sumOfFees = 0;
					$fee_month_list = [];
					for($i = $month; $i>=1; $i--){
						$feeInfoQuery = "SELECT fee FROM ct_student_monthly_fee_summary WHERE sub_head_id = $sub_head_id AND class_id = $class AND year = '$year' AND month = $i AND student_id = $val->infoStdid";
						if(isset($sec) && !empty($sec)){
							$feeInfoQuery .= " AND section = $sec";
						}
						if(isset($grou) && !empty($grou)){
							$feeInfoQuery .= " AND group_id = $grou";
						}
						
						$feeInfo = $wpdb->get_results($feeInfoQuery);
						
						if(!$feeInfo){
							if($sub_head_id == $monthlyFeeSubHeadId){
								if($studentInfo[0]->facilities == 'Full free' || $studentInfo[0]->facilities == 'Scholarship'){
									// $sumOfFees += 0;
								} else if($studentInfo[0]->facilities == 'Half free' ){
									$sumOfFees += ($fees/2);
								}else{
									$sumOfFees += $fees;
								}
							}else if($sub_head_id == $transportFeeSubHeadId){
								if($studentInfo[0]->transport_required == 1){
									
									
									$transport_fee_id = $studentInfo[0]->transport_fee_id;
									
									$feesquery = "SELECT amount FROM ct_transport_fee_list WHERE id = $transport_fee_id";
									$fees = $wpdb->get_results($feesquery);
									if($fees){
										
										$fees = $fees[0]->amount;
										
										if($studentInfo[0]->transport_type == 1){ 
											// one way
											$fees = $fees/2;
										}
									}else{
										$fees = 0;
									}
									$sumOfFees += $fees;
								}else{
									$sumOfFees += 0;
								}
							}else{
								$sumOfFees += $fees;
							}
							
							// $sumOfFees += $fees;
							
						}			
					}
					
					$allLists[$key]['due'] = $sumOfFees;
					

				}else if($subHeadType == 2){
					// yearly
					$feeInfoQuery = "SELECT fee FROM ct_student_yearly_fee_summary WHERE sub_head_id = $sub_head_id AND class_id = $class AND year = '$year' AND student_id = $val->infoStdid";
					if(isset($sec) && !empty($sec)){
						$feeInfoQuery .= " AND section = $sec";
					}
					if(isset($grou) && !empty($grou)){
						$feeInfoQuery .= " AND group_id = $grou";
					}
					$feeInfo = $wpdb->get_results($feeInfoQuery);
						if(!$feeInfo){
							$allLists[$key]['due'] = $fees;
						}else{
							$allLists[$key]['due'] = 0;
						}
				}else if($subHeadType == 3){
					// exam
					// get active exam id
					// $activeExamId = $wpdb->get_results("SELECT examid FROM ct_exam WHERE active_for_collection = 1 LIMIT 1");
					// if($activeExamId){
						// $activeExamId = $activeExamId[0]->examid;
						$feeInfoQuery = "SELECT fee FROM ct_student_exam_fee_summary WHERE sub_head_id = $sub_head_id AND class_id = $class AND year = '$year' AND student_id = $val->infoStdid";
						if(isset($sec) && !empty($sec)){
							$feeInfoQuery .= " AND section = $sec";
						}
						if(isset($grou) && !empty($grou)){
							$feeInfoQuery .= " AND group_id = $grou";
						}
						$feeInfo = $wpdb->get_results($feeInfoQuery);

						if(!$feeInfo){
							$allLists[$key]['due'] = $fees;
						}else{
							$allLists[$key]['due'] = 0;
						}	
					// }
					
				}else if($subHeadType == 4){
					$allLists[$key]['due'] = $fees;
				}



				// $allLists[$key]['due'] = 400;
			}
		}else{
			// All sub head combine

			
			
			// SUB HEAD INFO
			// $subHeadQry = "SELECT * FROM ct_sub_head WHERE id = $sub_head_id";
			// $subHeadDetails = $wpdb->get_results( $subHeadQry ); 
			// $subHeadType = $subHeadDetails[0]->type;


			$qry1 = "SELECT infoStdid, infoRoll FROM ct_studentinfo WHERE infoClass = $class AND infoyear = '$year'";
			// $qry1 = "SELECT infoStdid, infoRoll FROM ct_studentinfo WHERE infoClass = $class AND infoyear = '$year'";
			

			if ($sec != '') { $qry1 .= " AND infoSection = $sec"; }

			if ($grou != '') { $qry1 .= " AND infoGroup = $grou"; }

			// check monthly or others in query
			// if($subHeadType == 1){
			// 	$qry1 .= " AND infoStdid NOT IN (SELECT student_id FROM view_fee_collection_with_details  
			// 	WHERE class_id = $class AND year = '$year' AND month= $month AND sub_head_id = $sub_head_id)";
			// }else{
			// 	$qry1 .= " AND infoStdid NOT IN (SELECT student_id FROM view_fee_collection_with_details  
			// 	WHERE class_id = $class AND year = '$year' AND sub_head_id = $sub_head_id)";
			// }
			
			$qry1 .= " ORDER BY infoRoll ASC";
		

		$students = $wpdb->get_results( $qry1 ); 

		

		$allLists = [];
		foreach($students as $key => $val){
			$studentInfo = $wpdb->get_results("SELECT stdName, transport_fee_id, transport_type,transport_required, admission_type, facilities FROM ct_student WHERE studentid = $val->infoStdid");
			$allLists[$key]['name'] = $studentInfo[0]->stdName;
			$allLists[$key]['roll'] = $val->infoRoll;
			$allLists[$key]['due'] = 0;
			//  get active collection sub head id
			$subHeadId = $wpdb->get_results("SELECT * FROM ct_sub_head
			WHERE  active_for_collection = 1  AND relation_to = 1 and isHidden is null ORDER BY sub_head_name ASC");

			foreach($subHeadId as $subval){

			// get due by student id
			$feesQuery = "SELECT fee FROM ct_student_fee_list WHERE sub_head_id = $subval->id AND class_id = $class AND year = '$year' ";

			// No need section for fee
			// if(isset($section) && !empty($section)){
			// 	$feesQuery .= " AND section = $section";
			// }

			if(isset($grou) && !empty($grou)){
				$feesQuery .= " AND group_id = $grou";
			}
			$feesQuery .= " ORDER BY id DESC";
			$fees = $wpdb->get_results($feesQuery);
			if($fees){
				$fees = $fees[0]->fee;
			}else{
				$fees = 0;
			}
			if($subval->type == 1){
				// monthly
				$sumOfFees = 0;
				$fee_month_list = [];
				for($i = $month; $i>=1; $i--){
					$feeInfoQuery = "SELECT fee FROM ct_student_monthly_fee_summary WHERE sub_head_id = $subval->id AND class_id = $class AND year = '$year' AND month = $i AND student_id = $val->infoStdid";
					if(isset($sec) && !empty($sec)){
						$feeInfoQuery .= " AND section = $sec";
					}
					if(isset($grou) && !empty($grou)){
						$feeInfoQuery .= " AND group_id = $grou";
					}
					
					$feeInfo = $wpdb->get_results($feeInfoQuery);
				
					if(!$feeInfo){
						if($subval->id == $monthlyFeeSubHeadId){
							if($studentInfo[0]->facilities == 'Full free' || $studentInfo[0]->facilities == 'Scholarship'){
								// $sumOfFees += 0;
							} else if($studentInfo[0]->facilities == 'Half free' ){
								$sumOfFees += ($fees/2);
							}else{
								$sumOfFees += $fees;
							}
						}else if($subval->id == $transportFeeSubHeadId){
							if($studentInfo[0]->transport_required == 1){
								
								
								$transport_fee_id = $studentInfo[0]->transport_fee_id;
								
								$feesquery = "SELECT amount FROM ct_transport_fee_list WHERE id = $transport_fee_id";
								$fees = $wpdb->get_results($feesquery);
								if($fees){
									
									$fees = $fees[0]->amount;
									
									if($studentInfo[0]->transport_type == 1){ 
										// one way
										$fees = $fees/2;
									}
								}else{
									$fees = 0;
								}
								$sumOfFees += $fees;
								
							}else{
								$sumOfFees += 0;
							}
						}else if($subval->id == $coachingFeeSubHeadId){
							$checkfees = "SELECT amount FROM ct_student_wise_fee WHERE fee_type = 1 AND student_id = $val->infoStdid  AND class_id = $class AND year = '$year'";
							if(isset($section) && !empty($section)){
								$checkfees .= " AND section = $section";
							}
							if(isset($group) && !empty($group)){
								$checkfees .= " AND group_id = $group";
							}
							$studentwisefees = $wpdb->get_results($checkfees);
							if($studentwisefees && $studentwisefees[0]->amount > 0){						
								$fees = $studentwisefees[0]->amount;
							}else{
								$fees = 0;
							}
							$sumOfFees += $fees;
						}else{
							$sumOfFees += $fees;
						}
						
						// $sumOfFees += $fees;
						
					}			
				}
				
				$allLists[$key]['due'] += $sumOfFees;
					

			}else if($subval->type == 2){
				// yearly
				$feeInfoQuery = "SELECT fee FROM ct_student_yearly_fee_summary WHERE sub_head_id = $subval->id AND class_id = $class AND year = '$year' AND student_id = $val->infoStdid";
				if(isset($sec) && !empty($sec)){
					$feeInfoQuery .= " AND section = $sec";
				}
				if(isset($grou) && !empty($grou)){
					$feeInfoQuery .= " AND group_id = $grou";
				}
				$feeInfo = $wpdb->get_results($feeInfoQuery);
					if(!$feeInfo){
						// check admission fee for new or promoted student
				if( $subval->id == $admissionFeeSubHeadId){
					if($studentInfo[0]->admission_type == 1){
						$allLists[$key]['due'] += $fees;
					}else{
						$feesquery = "SELECT amount FROM ct_admission_fee_promoted WHERE class = $class";
						$fees = $wpdb->get_results($feesquery);
						if($fees){						
							$fees = $fees[0]->amount;
						}else{
							$fees = 0;
						}
						$allLists[$key]['due'] += $fees;
					}
				}else if($subval->id == $registrationFeeSubHeadId){
					$checkfees = "SELECT amount FROM ct_student_wise_fee WHERE fee_type = 2 AND student_id = $val->infoStdid  AND class_id = $class AND year = '$year'";
					if(isset($section) && !empty($section)){
						$checkfees .= " AND section = $section";
					}
					if(isset($group) && !empty($group)){
						$checkfees .= " AND group_id = $group";
					}
					$studentwisefees = $wpdb->get_results($checkfees);
					if($studentwisefees && $studentwisefees[0]->amount > 0){						
						$fees = $studentwisefees[0]->amount;
					}else{
						$fees = 0;
					}
					$allLists[$key]['due'] += $fees;
				}else{
					$allLists[$key]['due'] += $fees;
				}
					}else{
						$allLists[$key]['due'] += 0;
					}
					
			}else if($subval->type == 3){
				// exam
				// get active exam id
				$activeExamId = $wpdb->get_results("SELECT examid FROM ct_exam WHERE examClass = $class and active_for_collection = 1 LIMIT 1");
				if($activeExamId){
					$activeExamId = $activeExamId[0]->examid;
					$feeInfoQuery = "SELECT fee FROM ct_student_exam_fee_summary WHERE sub_head_id = $subval->id AND class_id = $class AND exam_id = $activeExamId AND year = '$year' AND student_id = $val->infoStdid";
					if(isset($sec) && !empty($sec)){
						$feeInfoQuery .= " AND section = $sec";
					}
					if(isset($grou) && !empty($grou)){
						$feeInfoQuery .= " AND group_id = $grou";
					}
					$feeInfo = $wpdb->get_results($feeInfoQuery);

					if(!$feeInfo){
						$allLists[$key]['due'] += $fees;
					}else{
						$allLists[$key]['due'] += 0;
					}	
				}
				
			}else if($subval->type == 4){
				$allLists[$key]['due'] += $fees;
			}


		}//end of sub head
			// $allLists[$key]['due'] = 400;
		}


		}
			// echo '<pre>';
			// print_r($allLists);exit;

			// $qry = "SELECT stdName,infoRoll,sectionName FROM ct_student_fee_collection_info
			// 		LEFT JOIN ct_student ON ct_student.studentid = ct_student_fee_collection_info.student_id
			// 		LEFT JOIN ct_studentinfo ON ct_studentinfo.infoStdid = ct_student_fee_collection_info.student_id AND ct_studentinfo.infoClass = $class AND ct_studentinfo.infoyear = '$year'
			// 		LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
			// 		WHERE ct_student_fee_collection_info.year = '$year' AND ct_student_fee_collection_info.class = $class";
		?>
		<div class="container-fluid maxAdminpages">
			
			<div class="row">
				<div class="col-md-12">
			  	<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>
			  </div>
			  <div class="col-md-12">
			  	<div id="printArea" class="col-md-12 printBG" style="width: 8.27in">
					  <div class="printArea" style="margin: 10px 30px 0;">
					  	<style type="text/css">
					  		table tr{ page-break-inside: avoid !important; }
					  		table tr a{ text-decoration: none;color: #000; }
					  		@page { size: 210mm 297mm !important; margin: 0 !important; }
					  	</style>
						  <style>
	
							table th, table td {
								border:1px solid #000;
								padding:0.5em;
							}
							.table-bordered{
								border-collapse: collapse;
							}
						</style>

				  		<div style="text-align: center; position: relative;">
				  			<img height="80px" style="position: absolute;left: 10px;top: 10px" src="<?= $s3sRedux['instLogo']['url'] ?>">
		  					<h2 style="margin-bottom: 0;"><b><?= $s3sRedux['institute_name'] ?></b></h2>
					  		<p style="color:#2b5591; font-size: 14px; margin: 0;"><?= $s3sRedux['institute_address'] ?></p>
					  		<span style="font-size: 17px;">Due List (<?= $sub_head_id != '' ? getSubHeadNameById($sub_head_id) : "All"?>)</span>
					  		<br><span style="font-size: 17px;">Month: <?= $monthName?> </span>
					  		<br><span style="font-size: 17px;">Year: <?= $year?> </span>
					  		<br><span style="font-size: 17px;">Class: <?= getClassNameById($class)?> </span>
				  		</div>
				  		<br>

					  		<table class="table table-bordered" style="width: 100%; text-align: center;">
					  			<tr style="text-align: center;">
					  				<th style=" text-align: center;">Student Name</th>
					  				<th style=" text-align: center;">ID NO:</th>
					  				<th style=" text-align: center;">Total Due</th>
					  			</tr>
								  <?php foreach($allLists as $key=>$val){?>
								  <tr>
									<td><?= $val['name']?></td>
									<td><?= $val['roll']?></td>
									<td><?= $val['due']?></td>
								  </tr>
						  		<?php } ?>
								  <tr style="font-size: 17px;font-weight: 700;">
								  <td >Total</td>
								  <td><?= count(array_column($allLists,'due'));?></td>
								  <td><?= array_sum(array_column($allLists,'due'));?></td>
								  </tr>
					  		</table>
					  		
					  </div>
					</div>
			  </div>
			</div>
			
		</div>

		<?php 
	endif; ?>
  	<?php }else if($_GET['view'] == 'monthlyFeeReport'){ ?>
		<div class="panel panel-info">
			<div class="panel-heading"><h3>Monthly Fee Report</h3></div>
				
			<div class="panel-body">
			  <form action="" method="POST" class="form-inline">
				  <div class="row pl-10">
				  <div class="form-group">
						<label>Class</label>
						<select id='resultClass' class="form-control" name="stdclass" required>
						<?php

							$classQuery = $wpdb->get_results( "SELECT classid,className FROM ct_class WHERE classid IN (SELECT infoClass FROM ct_studentinfo GROUP BY infoClass ORDER BY className ASC)" );
							echo "<option value=''>Select Class</option>";

							foreach ($classQuery as $class) {
							echo "<option value='".$class->classid."'>".$class->className."</option>";
							}
						?>
						</select>
					</div>

					<div class="form-group ">
						<label>Section</label>
						<select id="resultSection" class="form-control" name="sec" disabled>
						<option disabled selected>Select Class First</option>
						</select>
					</div>

					<div class="form-group ">
						<label>Group</label>
						<select id="resultGroup" class="form-control" name="group">
						<option value="">Select Group</option>
						<?php
							$groups = $wpdb->get_results("SELECT * FROM ct_group");
							foreach ($groups as $groups) {
							$selected = ($edit->infoGroup == $groups->groupId) ? 'selected' : '';
							?>
							<option value='<?= $groups->groupId ?>' <?= $selected ?>>
								<?= $groups->groupName ?>
							</option>
							<?php
							}
						?>
						</select>
					</div>

					<div class="form-group">
						<label>Year</label>
						<select id='resultYear' class="form-control" name="stdyear" required disabled>
						<option disabled selected>Select Class First</option>
						</select>
					</div>
					<div class="form-group">
							  <label>Fee Month </label>
							  <select name="fee-month" id="fee-month" required>
									<option value="">Select Month</option>
									<option value="1">January</option>
									<option value="2">February</option>
									<option value="3">March</option>
									<option value="4">April</option>
									<option value="5">May</option>
									<option value="6">June</option>
									<option value="7">July</option>
									<option value="8">August</option>
									<option value="9">September</option>
									<option value="10">October</option>
									<option value="11">November</option>
									<option value="12">December</option>
							  </select>
					  </div>
					  <input type="hidden" id="month_list" name="month_list[]" >
					<!-- <div class="form-group">
						<label>Roll</label>
						<input id="resultRoll" type="text" name="roll" required>
					</div> -->

					<!-- <div class="form-group ">
						<label>Fee Category</label>
						<select id="fee-category" class="form-control" name="sub_head_id">
						<option value="">Select Category</option>
						<?php
							$feeHead = $wpdb->get_results( "SELECT ct_sub_head.*, ct_head.head_name FROM `ct_sub_head` LEFT JOIN  ct_head ON ct_sub_head.head_id = ct_head.id WHERE ct_sub_head.relation_to = '1' AND ct_sub_head.isHidden is null ORDER BY ct_sub_head.sort_order ASC" );
							foreach ($feeHead AS $key => $val) {
							
							?>
							<option value='<?= $val->id ?>'>
								<?= $val->sub_head_name ?>
							</option>
							<?php
							}
						?>
						</select>
					</div> -->
					
				  </div>
				  <div class="row pl-10">
					  
					<div class="form-group">
						<input class="form-control btn-success" name="monthlyFeeReport" type="submit" value="Search">
						<input class="btn btn-info" type="reset" value="Reset" >
					</div>
				  </div>			  		
					</form>
		  </div>







		  <?php
		  if (isset($_GET['delete_post'])) {
			$post_id = $_GET['delete_post'];
			$deleteCollectionInfo = $wpdb->get_results( "DELETE FROM ct_student_fee_collection_info WHERE id = $post_id" ); 
			$deleteCollectionDetails = $wpdb->get_results( "DELETE FROM ct_student_fee_collection_details WHERE info_id = $post_id" ); 
			$deleteExamSummary = $wpdb->get_results( "DELETE FROM ct_student_exam_fee_summary WHERE info_id = $post_id" ); 
			$deleteYearlySummary = $wpdb->get_results( "DELETE FROM ct_student_yearly_fee_summary WHERE info_id = $post_id" ); 
			$deleteMonthlySummary = $wpdb->get_results( "DELETE FROM ct_student_monthly_fee_summary WHERE info_id = $post_id" ); 
			$deleteLedger = $wpdb->get_results( "DELETE FROM ct_ledger WHERE info_id = $post_id" );   
		}

		if(isset($_POST['monthlyFeeReport'])):
			$month 	= $_POST['fee-month'] ?? ''; 
			$year 	= $_POST['stdyear']; 
			$class 	= $_POST['stdclass'];
			$sec 		= $_POST['sec'];
			$grou 	= $_POST['group'];
			// $sub_head_id 	= $_POST['sub_head_id'];
			$monthName = $monthArray[$month-1];

		
			// All sub head combine

			
			
			// SUB HEAD INFO
			// $subHeadQry = "SELECT * FROM ct_sub_head WHERE id = $sub_head_id";
			// $subHeadDetails = $wpdb->get_results( $subHeadQry ); 
			// $subHeadType = $subHeadDetails[0]->type;


			$qry1 = "SELECT infoStdid, infoRoll FROM ct_studentinfo WHERE infoClass = $class AND infoyear = '$year'";
			// $qry1 = "SELECT infoStdid, infoRoll FROM ct_studentinfo WHERE infoClass = $class AND infoyear = '$year'";
			

			if ($sec != '') { $qry1 .= " AND infoSection = $sec"; }

			if ($grou != '') { $qry1 .= " AND infoGroup = $grou"; }

			// check monthly or others in query
			// if($subHeadType == 1){
			// 	$qry1 .= " AND infoStdid NOT IN (SELECT student_id FROM view_fee_collection_with_details  
			// 	WHERE class_id = $class AND year = '$year' AND month= $month AND sub_head_id = $sub_head_id)";
			// }else{
			// 	$qry1 .= " AND infoStdid NOT IN (SELECT student_id FROM view_fee_collection_with_details  
			// 	WHERE class_id = $class AND year = '$year' AND sub_head_id = $sub_head_id)";
			// }
			
			$qry1 .= " ORDER BY infoRoll ASC";
		

		$students = $wpdb->get_results( $qry1 ); 

		

		$allLists = [];
		foreach($students as $key => $val){
			$studentInfo = $wpdb->get_results("SELECT stdName, transport_fee_id, transport_type,transport_required, admission_type, facilities FROM ct_student WHERE studentid = $val->infoStdid");
			
			

			// get total by student id
			$feesQuery = "SELECT SUM(total) as total, id FROM ct_student_fee_collection_info WHERE month = $month AND student_id = $val->infoStdid AND class_id = $class AND year = '$year' ";

		
			$feesQuery .= " ORDER BY id DESC";
			$fees = $wpdb->get_results($feesQuery);
			if($fees){				
				$total = $fees[0]->total;
				if($total){
					$allLists[$key]['name'] = $studentInfo[0]->stdName;
					$allLists[$key]['roll'] = $val->infoRoll;
					$allLists[$key]['total'] = $total;				
					$allLists[$key]['order_id'] = $fees[0]->id;
				}
			}else{
				$total = 0;
			}


		}


		
			// echo '<pre>';
			// print_r($allLists);exit;

			// $qry = "SELECT stdName,infoRoll,sectionName FROM ct_student_fee_collection_info
			// 		LEFT JOIN ct_student ON ct_student.studentid = ct_student_fee_collection_info.student_id
			// 		LEFT JOIN ct_studentinfo ON ct_studentinfo.infoStdid = ct_student_fee_collection_info.student_id AND ct_studentinfo.infoClass = $class AND ct_studentinfo.infoyear = '$year'
			// 		LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
			// 		WHERE ct_student_fee_collection_info.year = '$year' AND ct_student_fee_collection_info.class = $class";
		?>
		<div class="container-fluid maxAdminpages">
			
			<div class="row">
				<div class="col-md-12">
			  	<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>
			  </div>
			  <div class="col-md-12">
			  	<div id="printArea" class="col-md-12 printBG" style="width: 8.27in">
					  <div class="printArea" style="margin: 10px 30px 0;">
					  	<style type="text/css">
					  		table tr{ page-break-inside: avoid !important; }
					  		table tr a{ text-decoration: none;color: #000; }
					  		@page { size: 210mm 297mm !important; margin: 0 !important; }
					  	</style>
						  <style>
	
							table th, table td {
								border:1px solid #000;
								padding:0.5em;
							}
							.table-bordered{
								border-collapse: collapse;
							}
						</style>

				  		<div style="text-align: center; position: relative;">
				  			<img height="80px" style="position: absolute;left: 10px;top: 10px" src="<?= $s3sRedux['instLogo']['url'] ?>">
		  					<h2 style="margin-bottom: 0;"><b><?= $s3sRedux['institute_name'] ?></b></h2>
					  		<p style="color:#2b5591; font-size: 14px; margin: 0;"><?= $s3sRedux['institute_address'] ?></p>
					  		<span style="font-size: 17px;">Monthly Fee Report </span>
					  		<br><span style="font-size: 17px;">Month: <?= $monthName?> </span>
					  		<br><span style="font-size: 17px;">Year: <?= $year?> </span>
					  		<br><span style="font-size: 17px;">Class: <?= getClassNameById($class)?> </span>
				  		</div>
				  		<br>

					  		<table class="table table-bordered" style="width: 100%; text-align: center;">
					  			<tr style="text-align: center;">
					  				<th style=" text-align: center;">Student Name</th>
					  				<th style=" text-align: center;">ID NO</th>
					  				<th style=" text-align: center;">Total</th>
					  				<th style=" text-align: center;">Delete</th>
					  			</tr>
								  <?php foreach($allLists as $key=>$val){?>
								  <tr>
									<td><?= $val['name']?></td>
									<td><?= $val['roll']?></td>
									<td><?= $val['total']?></td>
									<td><a href="?page=studentFeeManagement&view=monthlyFeeReport&delete_post=<?= $val['order_id']?>" onclick='return confirm("Are you sure you want to delete this post?")' style="cursor: pointer;"> Delete</a></td>
								  </tr>
						  		<?php } ?>
								  <tr style="font-size: 17px;font-weight: 700;">
								  <td >Total</td>
								  <td><?= count(array_column($allLists,'total'));?></td>
								  <td><?= array_sum(array_column($allLists,'total'));?></td>
								  </tr>
					  		</table>
					  		
					  </div>
					</div>
			  </div>
			</div>
			
		</div>

		<?php 
	endif; ?>
  	<?php }else if($_GET['view'] == 'dailyFeeReport'){ ?>
		<div class="panel panel-info">
			<div class="panel-heading"><h3>Daily Fee Report</h3></div>
				
			<div class="panel-body">
			  <form action="" method="POST" class="form-inline">
				  <div class="row pl-10">
                    <div class="form-group">
						<label>Date</label>
						<input id="from-date" type="date" name="from-date" >

					</div>
					
			        <div class="form-group">
						<input class="form-control btn-success" name="daillyFeeReport" type="submit" value="Search">
						<input class="btn btn-info" type="reset" value="Reset" >
					</div>
					  
					
				  </div>			  		
					</form>
		  </div>







		  <?php

		if(isset($_POST['daillyFeeReport'])):
			$from_date = $_POST['from-date'];
							
                    			$feesQuery = "SELECT SUM(total) as total, sectionName,className  FROM ct_student_fee_collection_info
                    			LEFT JOIN ct_section ON ct_student_fee_collection_info.section = ct_section.sectionid
                    			LEFT JOIN ct_class ON ct_student_fee_collection_info.class_id = ct_class.classid
                    			WHERE date(date) = date('$from_date') ";
                    
                    		
                    			$feesQuery .= " GROUP BY className, sectionName  ORDER BY classid, sectionName ASC";
                    			$fees = $wpdb->get_results($feesQuery);
			
			

                    		$classes = [];
                            foreach ($fees as $item) {
                                $className = $item->className;
                                if (!isset($classes[$className])) {
                                    $classes[$className] = [];
                                }
                                $classes[$className][] = ['sectionName' => $item->sectionName, 'total' => $item->total];
                            }
			
			$totalSum = array_sum(array_column($fees, 'total'));
		?>
		<div class="container-fluid maxAdminpages">
			
			<div class="row">
				<div class="col-md-12">
			  	<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>
			  </div>
			  <div class="col-md-12">
			  	<div id="printArea" class="col-md-12 printBG" style="width: 8.27in">
					  <div class="printArea" style="margin: 10px 30px 0;">
					  	<style type="text/css">
					  		table tr{ page-break-inside: avoid !important; }
					  		table tr a{ text-decoration: none;color: #000; }
					  		@page { size: 210mm 297mm !important; margin: 0 !important; }
					  	</style>
						  <style>
	
							table th, table td {
								border:1px solid #000;
								padding:0.5em;
							}
							.table-bordered{
								border-collapse: collapse;
							}
						</style>

				  		<div style="text-align: center; position: relative;">
				  			<img height="80px" style="position: absolute;left: 10px;top: 10px" src="<?= $s3sRedux['instLogo']['url'] ?>">
		  					<h2 style="margin-bottom: 0;"><b><?= $s3sRedux['institute_name'] ?></b></h2>
					  		<p style="color:#2b5591; font-size: 14px; margin: 0;"><?= $s3sRedux['institute_address'] ?></p>
					  		<span style="font-size: 17px;">Daily Fee Report </span>
					  		<br><span style="font-size: 17px;">Date: <?= date('d-m-Y',strtotime($from_date)) ?> </span>
				  		</div>
				  		<br>

					  		<table class="table table-bordered" style="width: 100%; text-align: center;">
					  		    <thead>
					  		        <tr style="text-align: center;">
					  				<th style=" text-align: center;">Class Name</th>
					  				<th style=" text-align: center;">Section Name</th>
					  				<th style=" text-align: center;">Total</th>
					  			</tr>
					  		    </thead>
					  			
								 <tbody>
                                    <?php foreach ($classes as $className => $sections): ?>
                                        <tr>
                                            <td rowspan="<?= count($sections) ?>"><?php echo $className; ?></td>
                                            <td><?php echo $sections[0]['sectionName']; ?></td>
                                            <td><?php echo $sections[0]['total']; ?></td>
                                        </tr>
                                        <?php for ($i = 1; $i < count($sections); $i++): ?>
                                            <tr>
                                                <td><?php echo $sections[$i]['sectionName']; ?></td>
                                                <td><?php echo $sections[$i]['total']; ?></td>
                                            </tr>
                                        <?php endfor; ?>
                                    <?php endforeach; ?>
                                    <tr>
                                        <td colspan="2" style="text-align: right; font-weight: bold;">Total Sum:</td>
                                        <td style="font-weight: bold;"><?php echo $totalSum; ?></td>
                                    </tr>
                                </tbody>
					  		</table>
					  		
					  </div>
					</div>
			  </div>
			</div>
			
		</div>

		<?php 
	endif; ?>










  	<?php }else if($_GET['view'] == 'activeCollectionFee'){ ?>
		<div class="panel panel-info">
			<div class="panel-heading"><h3>Active Collection Fee</h3></div>
				
			<div class="panel-body">
			  <form action="" method="POST" class="form-inline">
				  <div class="row pl-10">

					<div class="form-group ">
						
						
						<?php
							$feeHead = $wpdb->get_results( "SELECT ct_sub_head.*, ct_head.head_name FROM `ct_sub_head` LEFT JOIN  ct_head ON ct_sub_head.head_id = ct_head.id WHERE ct_sub_head.relation_to = '1' AND ct_sub_head.isHidden is null ORDER BY ct_sub_head.sort_order ASC" );
							foreach ($feeHead AS $key => $val) {
							
							?>
							<label><?= $val->sub_head_name ?></label>
							<select id="feeCategory-<?= $val->id ?>" class="form-control" name="<?= $val->id ?>" selected>
								<option value="1" <?= $val->active_for_collection == 1 ? 'selected': '' ?>>Active</option>
								<option value="0" <?= $val->active_for_collection == 0 ? 'selected': '' ?>>Inactive</option>
							</select>
							<?php
							}
						?>
						
					</div>
					
				  </div>
				  <br>
				  <div class="row pl-10">
					  
					
					<div class="form-group">
						<input class="form-control btn-success" name="activeCollectionFee" type="submit" value="Update">
					</div>
				  </div>			  		
					</form>
		  </div>







		  <?php
		if(isset($_POST['activeCollectionFee'])):
			// echo '<pre>';
			// print_r($_POST);exit;
			foreach($_POST as $key => $val){
				if($key == 'activeCollectionFee')
				{
					continue;
				}else{
					$subHeadQry = "UPDATE ct_sub_head SET active_for_collection = '$val' WHERE  id = $key";
					$updateSubHeadQry = $wpdb->get_results( $subHeadQry ); 
				}
				
			}
			header("Refresh:0");
	
	endif; ?>










  	<?php }elseif($_GET['view'] == 'addFee'){ ?>
			<div class="panel panel-info">
			  <div class="panel-heading"><h3>Student Fee Collection</h3></div>
			  	<?php
  
					$subHead = $wpdb->get_results( "SELECT * FROM ct_sub_head WHERE relation_to = 1 and active_for_collection = 1 and isHidden is null ORDER BY sub_head_name ASC" );
					$remissionCategory= $wpdb->get_results( "SELECT * FROM ct_sub_head WHERE relation_to = 1 and active_for_collection = 1 ORDER BY sub_head_name ASC" );
				?>
			  <div class="panel-body" >
				<form action="" method="POST" class="form-inline">
					<div class="row pl-10">
					<div class="form-group result-class">
						  <label>Class</label>
						  <select id='resultClass' class="form-control" name="stdclass" required>
						  <?php
						//   print_r($selectedClass);exit;
	// echo '<pre>'; print_r( $_SESSION);exit; //".$class->classid == $_SESSION['selectedClass']? 'selected':'' ."
							  $classQuery = $wpdb->get_results( "SELECT classid,className FROM ct_class WHERE classid IN (SELECT infoClass FROM ct_studentinfo GROUP BY infoClass ORDER BY className ASC)" );
							  echo "<option value=''>Select Class</option>";
  
							  foreach ($classQuery as $class) {?>
							  <option value="<?=$class->classid?>" ><?= $class->className ?></option>;
							<?php  }
						  ?>
						  </select>
					  </div>
					  </script>

					  <div class="form-group ">
						  <label>Section</label>
						  <select id="resultSection" class="form-control" name="sec" disabled>
						  <option disabled selected>Select Class First</option>
						  </select>
					  </div>
  
					  <div class="form-group ">
						  <label>Group</label>
						  <select id="resultGroup" class="form-control" name="group">
						  <option value="">Select Group</option>
						  <?php
							  $groups = $wpdb->get_results("SELECT * FROM ct_group");
							  foreach ($groups as $groups) {
							  $selected = ($edit->infoGroup == $groups->groupId) ? 'selected' : '';
							  ?>
							  <option value='<?= $groups->groupId ?>' <?= $selected ?>>
								  <?= $groups->groupName ?>
							  </option>
							  <?php
							  }
						  ?>
						  </select>
					  </div>
  
					  <div class="form-group">
						  <label>Year</label>
						  <select id='resultYear' class="form-control" name="stdyear" required>
						  <!-- <option disabled selected>Select Class First</option> -->
						  </select>
					  </div>
					  <div class="form-group">
								<label>Fee Month </label>
								<select name="fee-month" id="fee-month" required>
							  		<option value="">Select Month</option>
							  		<option value="1">January</option>
							  		<option value="2">February</option>
							  		<option value="3">March</option>
							  		<option value="4">April</option>
							  		<option value="5">May</option>
							  		<option value="6">June</option>
							  		<option value="7">July</option>
							  		<option value="8">August</option>
							  		<option value="9">September</option>
							  		<option value="10">October</option>
							  		<option value="11">November</option>
							  		<option value="12">December</option>
								</select>
						</div>
						<input type="hidden" id="month_list" name="month_list[]" >
					  <div class="form-group">
						  <label>ID NO:</label>
						  <input id="resultRoll" type="text" name="roll" required>
					  </div>
					  <input type="hidden" id="admissionFeeSubHeadId" name="admissionFeeSubHeadId" value="<?= $admissionFeeSubHeadId?>">
					  <input type="hidden" id="admissionFormSubHeadId" name="admissionFormSubHeadId" value="<?= $admissionFormSubHeadId?>">
					  <input type="hidden" id="monthlyFeeSubHeadId" name="monthlyFeeSubHeadId" value="<?= $monthlyFeeSubHeadId?>">
					  <input type="hidden" id="transportFeeSubHeadId" name="transportFeeSubHeadId" value="<?= $transportFeeSubHeadId?>">
					  <input type="hidden" id="coachingFeeSubHeadId" name="coachingFeeSubHeadId" value="<?= $coachingFeeSubHeadId?>">
					  <input type="hidden" id="registrationFeeSubHeadId" name="registrationFeeSubHeadId" value="<?= $registrationFeeSubHeadId?>">
					</div>
					<br>
					<br>
					<div class="row pl-10">
						
						<div class="form-group">
								<label>Date </label><br>
								<input id="fee-date" type="date" name="fee-date" value="<?php echo date('Y-m-d'); ?>">							
						</div>
						
						<div class="form-group">
						  <label>Notes</label><br>
						  <input id="notes" type="text" name="notes">
					  </div>
					  <div class="form-group">
						  <label>Student Name</label><br>
						  <input id="student_name" type="text" name="stdname" readonly>
						  <input id="student_id" type="hidden" name="student_id">
					  </div><br>
						<?php foreach ($subHead as $key=>$val){?>
							<div class="form-group">
									<label><?= $val->sub_head_name ?> <input type="checkbox"  class="active_category" name="active_category[]" id="active_category<?= $val->id?>" value="<?= $val->id?>" checked> </label><br>
									<input type="hidden" name="subheadidhidden[]" value="<?= $val->id ?>">
									<input id="subheadid<?= $val->id ?>" class="calculate" type="number" name="subheadid[]" value="0" <?= $val->is_editable == 1 ? 'onChange="getTotal()"': 'readonly' ?> >
								
							</div>
						<?php } ?>
						<div class="form-group">
								<label>Late Fee <input type="checkbox"  class="active_category" name="active_category[]" id="active_category<?= $lateSubHeadId?>" value="<?= $lateSubHeadId?>" checked onclick="return false;" onkeydown="return false;"> </label><br>
								<input type="hidden" name="subheadidhidden[]" value="<?= $lateSubHeadId ?>">
								<input id="subheadid<?= $lateSubHeadId ?>" onChange="getTotal()" type="number" name="subheadid[]" value="0">
							
						</div>
						<div class="form-group">
								<label>Absent Fee <input type="checkbox"  class="active_category" name="active_category[]" id="active_category<?= $absentSubHeadId?>" value="<?= $absentSubHeadId?>" checked onclick="return false;" onkeydown="return false;"></label><br>
								<input type="hidden" name="subheadidhidden[]" value="<?= $absentSubHeadId ?>">
								<input id="subheadid<?= $absentSubHeadId ?>" class="calculate" onChange="getTotal()" type="number" name="subheadid[]" value="0">
							
						</div><br>
						<div class="form-group ">
								<label>Sub Total </label><br>
								<input id="sub-total" type="number" name="sub-total" value="0" readonly>
						</div>
						<div class="form-group ">
								<label>Poor Fund </label><br>
								<input id="remission" onChange="getTotal()" type="number" name="remission" value="0">
						</div>
						<div class="form-group ">
								<label>Grand Total </label><br>
								<input id="grand-total" type="number" name="grand-total" value="0" readonly>
						</div>
						<br>
					  <div class="form-group">
					  <strong>Poor Fund Category: </strong> 
						  <?php foreach($remissionCategory as $key=>$val) {?>
						  	<input type="checkbox"  class="remission_category" name="remission_category[]" id="remission_category<?= $val->id?>" value="<?= $val->id?>"> <label for="remission_category<?= $val->id?>"><?= $val->sub_head_name?></label>
						  <?php }?>
					  </div><br>
					  <div class="form-group">
						  <input class="form-control btn-success" name="addFee" type="submit" value="Save">
						  <input class="btn btn-info" type="reset" value="Reset" >
					  </div>
					</div>			  		
					  </form>
				</div>
			</div>
			<?php
if (isset($_POST['addFee'])) {

if( $_POST['grand-total'] == 0){
	
	$message = ms3message('', 'Please enter information correctly.');
}else{
	// echo '<pre>';
	
	// print_r($_POST);exit;

$class = $_POST['stdclass'];
$section   = $_POST['sec'];
$year	=  $_POST['stdyear'];
$roll			= $_POST['roll'];
$group		= $_POST['group'];
$fee_month		=  $_POST['fee-month'] == '' ? 0 : $_POST['fee-month'];
$std_name			= $_POST['stdname'];
$std_id			= $_POST['student_id'];
$_SESSION['selectedClass'] = $class;
$_SESSION['selectedSection']= $section;
$_SESSION['selectedYear']		= $year;
$_SESSION['selectedGroup']	= $group;
$_SESSION['selectedMonth']	= $fee_month;

// get student info
$facilities = '';
$admission_type = '';
$stdPhone = '';
$studentDetails = $wpdb->get_results("SELECT * FROM ct_student WHERE studentid = $std_id");
if($studentDetails){
	$facilities = $studentDetails[0]->facilities;
	$admission_type = $studentDetails[0]->admission_type;
	$stdPhone = $studentDetails[0]->stdPhone;
}
$remission_category = null;
if(isset($_POST['remission_category'])){
	$remission_category = implode(",",$_POST['remission_category']);
}

// $active_category_array = [];
// if(isset($_POST['active_category'])){
// 	$active_category_array = implode(",",$_POST['active_category']);
// }
// print_r($active_category_array);exit;
// insert collection info table

$insert = $wpdb->insert(
	'ct_student_fee_collection_info',
	array(
		'student_roll' 		=> $roll,
		'student_id' 		=> $std_id,
		'year' 	=> $year,
		'month' => $fee_month,
		'class_id' 	=> $class,
		'section' 	=> $section,
		'group_id' 	=> $group,
		'sub_total' 	=> $_POST['sub-total'],
		'total' 	=> $_POST['grand-total'],
		'remission' 	=> $_POST['remission'],
		'remission_category' 	=> $remission_category,
		'status' 	=> 1,
		'notes' 	=> $_POST['notes'],
		'date' 	=> $_POST['fee-date'],
		// 'exam_id' 	=> $_POST['revDate'],
		'created_by' 	=> get_current_user_id(),
		'created_at' 	=> date('Y-m-d H-i-s')
	)
);
$info_id = $wpdb->insert_id;

// save cash in ledger table $reference,$monthly_fee_id,$yearly_fee_id,$exam_fee_id
saveLeadger($cashSubHeadId ,$_POST['grand-total'],0, 'Collection Reference ID-'.$info_id,null,null,null,$_POST['fee-date'],$info_id);

// remission
if(isset($_POST['remission']) != 0){
	saveLeadger(0,0 ,$_POST['remission'], 'Collection Reference ID-'.$info_id.'. Remission Category '.$remission_category,null,null,null,$_POST['fee-date'],$info_id);
}



foreach($_POST['active_category'] as $key=>$shid){

	//  get active collection sub head id
$subHeadInfo = $wpdb->get_results("SELECT * FROM ct_sub_head
WHERE  active_for_collection = 1  AND relation_to = 1 and  id = $shid");
if($subHeadInfo){
$val = new stdClass();
$val->id = $shid;
$val->type = $subHeadInfo[0]->type;
//  NOTES: NEED TO SAVE DUE MONTH AND YEAR WISE
// $fees = $wpdb->get_results("SELECT fee FROM ct_student_fee_list WHERE sub_head_id = $val->id AND class_id = $GLOBALS[class] AND year = $GLOBALS[year]");
$feesQuery = "SELECT fee FROM ct_student_fee_list WHERE sub_head_id = $val->id AND class_id = $class AND year = '$year' ";

// No need section for fee
// if(isset($section) && !empty($section)){
// 	$feesQuery .= " AND section = $section";
// }

if(isset($group) && !empty($group)){
	$feesQuery .= " AND group_id = $group";
}
$feesQuery .= " ORDER BY id DESC";
$fees = $wpdb->get_results($feesQuery);
if($fees){
	$fees = $fees[0]->fee;
}else{
	if($val->id == $absentSubHeadId || $val->id == $lateSubHeadId){
		$key2 = array_search ($val->id, $_POST['active_category']);
		$fees = $_POST['subheadid'][$key2];	
	}else{
		$fees = 0;
	}
	
}
if($val->type == 1){
	// monthly
	$sumOfFees = 0;
	$fee_month_list = [];
	
	// remove this after adjusting absent and late fee dynamically
	if($val->id == $absentSubHeadId || $val->id == $lateSubHeadId || $val->id == $ictFeeSubHeadId){
		$fees = $_POST['subheadid'][$key];
		$insert = $wpdb->insert(
			'ct_student_monthly_fee_summary',
			array(
				'student_id' 		=> $std_id,
				'year' 	=> $year,
				'month' => $i,
				'class_id' 	=> $class,
				'section' 	=> $section,
				'group_id' 	=> $group,
				'info_id' 	=> $info_id,						
				'sub_head_id' 	=> $val->id,
				'fee' 	=> $fees,
				'status' 	=> 1,
				'notes' 	=> $val->id == $absentSubHeadId? 'Absent Fee':'Late Fee',
				'date' 	=> $_POST['fee-date'],
				'created_by' 	=> get_current_user_id(),
				'created_at' 	=> date('Y-m-d H-i-s')
			)
		);
		$last_id_month = $wpdb->insert_id;
		// save ledger $reference,$monthly_fee_id,$yearly_fee_id,$exam_fee_id
		saveLeadger($val->id ,$fees,0, 'Collection Reference ID-'.$info_id,$last_id_month,null,null,$_POST['fee-date'],$info_id);

		$sumOfFees += $fees;
	}else{
	for($i = $fee_month; $i>=1; $i--){
		$feeInfoQuery = "SELECT fee FROM ct_student_monthly_fee_summary WHERE sub_head_id = $val->id AND class_id = $class AND year = '$year' AND month = $i AND student_id = $std_id";
		if(isset($section) && !empty($section)){
			$feeInfoQuery .= " AND section = $section";
		}
		if(isset($group) && !empty($group)){
			$feeInfoQuery .= " AND group_id = $group";
		}
		
		$feeInfo = $wpdb->get_results($feeInfoQuery);
		if(!$feeInfo){
			// save monthly summary here

			if($val->id == $monthlyFeeSubHeadId){
				if($facilities == 'Full free' || $facilities == 'Scholarship'){
					$insert = $wpdb->insert(
						'ct_student_monthly_fee_summary',
						array(
							'student_id' 		=> $std_id,
							'year' 	=> $year,
							'month' => $i,
							'class_id' 	=> $class,
							'section' 	=> $section,
							'group_id' 	=> $group,		
							'info_id' 	=> $info_id,				
							'sub_head_id' 	=> $val->id,
							'fee' 	=> 0,
							'status' 	=> 1,
							'notes' 	=> $monthArray[$i-1].' '.$facilities,
							'date' 	=> $_POST['fee-date'],
							'created_by' 	=> get_current_user_id(),
							'created_at' 	=> date('Y-m-d H-i-s')
						)
					);
					$last_id_month = $wpdb->insert_id;
					// save ledger $reference,$monthly_fee_id,$yearly_fee_id,$exam_fee_id
					saveLeadger($val->id ,0,0, 'Collection Reference ID ('.$facilities.')-'.$info_id,$last_id_month,null,null,$_POST['fee-date'],$info_id);
		
					$sumOfFees += 0;
					$fee_month_list[] = $monthArray[$i-1];
				} else if($facilities == 'Half free' ){
					$insert = $wpdb->insert(
						'ct_student_monthly_fee_summary',
						array(
							'student_id' 		=> $std_id,
							'year' 	=> $year,
							'month' => $i,
							'class_id' 	=> $class,
							'section' 	=> $section,
							'group_id' 	=> $group,	
							'info_id' 	=> $info_id,					
							'sub_head_id' 	=> $val->id,
							'fee' 	=> $fees/2,
							'status' 	=> 1,
							'notes' 	=> $monthArray[$i-1].' '.$facilities,
							'date' 	=> $_POST['fee-date'],
							'created_by' 	=> get_current_user_id(),
							'created_at' 	=> date('Y-m-d H-i-s')
						)
					);
					$last_id_month = $wpdb->insert_id;
					// save ledger $reference,$monthly_fee_id,$yearly_fee_id,$exam_fee_id
					saveLeadger($val->id ,$fees/2,0, 'Collection Reference ID ('.$facilities.')-'.$info_id,$last_id_month,null,null,$_POST['fee-date'],$info_id);
		
					$sumOfFees += $fees/2;
					$fee_month_list[] = $monthArray[$i-1];
				}else{
					// check student wise monthly fee
					$feesQuery = "SELECT monthly_fee FROM ct_student WHERE studentid = $std_id";
					$studentwisefees = $wpdb->get_results($feesQuery);
					if($studentwisefees[0]->monthly_fee > 0){						
						$fees = $studentwisefees[0]->monthly_fee;
					}
					$insert = $wpdb->insert(
						'ct_student_monthly_fee_summary',
						array(
							'student_id' 		=> $std_id,
							'year' 	=> $year,
							'month' => $i,
							'class_id' 	=> $class,
							'section' 	=> $section,
							'group_id' 	=> $group,	
							'info_id' 	=> $info_id,					
							'sub_head_id' 	=> $val->id,
							'fee' 	=> $fees,
							'status' 	=> 1,
							'notes' 	=> $monthArray[$i-1],
							'date' 	=> $_POST['fee-date'],
							'created_by' 	=> get_current_user_id(),
							'created_at' 	=> date('Y-m-d H-i-s')
						)
					);
					$last_id_month = $wpdb->insert_id;
					// save ledger $reference,$monthly_fee_id,$yearly_fee_id,$exam_fee_id
					saveLeadger($val->id ,$fees,0, 'Collection Reference ID-'.$info_id,$last_id_month,null,null,$_POST['fee-date'],$info_id);
		
					$sumOfFees += $fees;
					$fee_month_list[] = $monthArray[$i-1];
				}
				
			} else if($val->id == $transportFeeSubHeadId){
				$transportDetails = $wpdb->get_results("SELECT ct_student_wise_fee.transport_fee_id, ct_student_wise_fee.transport_type,ct_student_wise_fee.transport_required FROM ct_student_wise_fee WHERE student_id = $std_id AND year = '$year' AND class_id = $class AND fee_type = 3 AND status = 1");

				if($transportDetails && $transportDetails[0]->transport_required == 1){
						
						
					$transport_fee_id = $transportDetails[0]->transport_fee_id;
					
					$feesquery = "SELECT amount FROM ct_transport_fee_list WHERE id = $transport_fee_id";
					$fees = $wpdb->get_results($feesquery);
					if($fees){
						
						$fees = $fees[0]->amount;
						
						if($transportDetails[0]->transport_type == 1){ 
							// one way
							$fees = $fees/2;
							$insert = $wpdb->insert(
								'ct_student_monthly_fee_summary',
								array(
									'student_id' 		=> $std_id,
									'year' 	=> $year,
									'month' => $i,
									'class_id' 	=> $class,
									'section' 	=> $section,
									'group_id' 	=> $group,
									'info_id' 	=> $info_id,						
									'sub_head_id' 	=> $val->id,
									'fee' 	=> $fees,
									'status' 	=> 1,
									'notes' 	=> $monthArray[$i-1].' One way transport',
									'date' 	=> $_POST['fee-date'],
									'created_by' 	=> get_current_user_id(),
									'created_at' 	=> date('Y-m-d H-i-s')
								)
							);
							$last_id_month = $wpdb->insert_id;
							// save ledger $reference,$monthly_fee_id,$yearly_fee_id,$exam_fee_id
							saveLeadger($val->id ,$fees,0, 'Collection Reference ID (One way transport)-'.$info_id,$last_id_month,null,null,$_POST['fee-date'],$info_id);
				
							$sumOfFees += $fees;
							$fee_month_list[] = $monthArray[$i-1];
						}else{
							// two way
							$insert = $wpdb->insert(
								'ct_student_monthly_fee_summary',
								array(
									'student_id' 		=> $std_id,
									'year' 	=> $year,
									'month' => $i,
									'class_id' 	=> $class,
									'section' 	=> $section,
									'group_id' 	=> $group,	
									'info_id' 	=> $info_id,					
									'sub_head_id' 	=> $val->id,
									'fee' 	=> $fees,
									'status' 	=> 1,
									'notes' 	=> $monthArray[$i-1].' Two way transport',
									'date' 	=> $_POST['fee-date'],
									'created_by' 	=> get_current_user_id(),
									'created_at' 	=> date('Y-m-d H-i-s')
								)
							);
							$last_id_month = $wpdb->insert_id;
							// save ledger $reference,$monthly_fee_id,$yearly_fee_id,$exam_fee_id
							saveLeadger($val->id ,$fees,0, 'Collection Reference ID (Two way transport)-'.$info_id,$last_id_month,null,null,$_POST['fee-date'],$info_id);
				
							$sumOfFees += $fees;
							$fee_month_list[] = $monthArray[$i-1];
						}
					}
				}

				
				
			}else if($val->id == $coachingFeeSubHeadId){
				$checkfees = "SELECT amount FROM ct_student_wise_fee WHERE fee_type = 1 AND student_id = $std_id  AND class_id = $class AND year = '$year'";
				if(isset($section) && !empty($section)){
					$checkfees .= " AND section = $section";
				}
				if(isset($group) && !empty($group)){
					$checkfees .= " AND group_id = $group";
				}
				$studentwisefees = $wpdb->get_results($checkfees);
				if($studentwisefees && $studentwisefees[0]->amount > 0){						
					$fees = $studentwisefees[0]->amount;
				}else{
					$fees = 0;
				}
				$insert = $wpdb->insert(
					'ct_student_monthly_fee_summary',
					array(
						'student_id' 		=> $std_id,
						'year' 	=> $year,
						'month' => $i,
						'class_id' 	=> $class,
						'section' 	=> $section,
						'group_id' 	=> $group,	
						'info_id' 	=> $info_id,					
						'sub_head_id' 	=> $val->id,
						'fee' 	=> $fees,
						'status' 	=> 1,
						'notes' 	=> $monthArray[$i-1].' Coaching fee',
						'date' 	=> $_POST['fee-date'],
						'created_by' 	=> get_current_user_id(),
						'created_at' 	=> date('Y-m-d H-i-s')
					)
				);
				$last_id_month = $wpdb->insert_id;
				// save ledger $reference,$monthly_fee_id,$yearly_fee_id,$exam_fee_id
				saveLeadger($val->id ,$fees,0, 'Collection Reference ID (Coaching fee)-'.$info_id,$last_id_month,null,null,$_POST['fee-date'],$info_id);
	
				$sumOfFees += $fees;
				$fee_month_list[] = $monthArray[$i-1];
			}else{
				$insert = $wpdb->insert(
					'ct_student_monthly_fee_summary',
					array(
						'student_id' 		=> $std_id,
						'year' 	=> $year,
						'month' => $i,
						'class_id' 	=> $class,
						'section' 	=> $section,
						'group_id' 	=> $group,	
						'info_id' 	=> $info_id,					
						'sub_head_id' 	=> $val->id,
						'fee' 	=> $fees,
						'status' 	=> 1,
						'notes' 	=> $monthArray[$i-1],
						'date' 	=> $_POST['fee-date'],
						'created_by' 	=> get_current_user_id(),
						'created_at' 	=> date('Y-m-d H-i-s')
					)
				);
				$last_id_month = $wpdb->insert_id;
				// save ledger $reference,$monthly_fee_id,$yearly_fee_id,$exam_fee_id
				saveLeadger($val->id ,$fees,0, 'Collection Reference ID-'.$info_id,$last_id_month,null,null,$_POST['fee-date'],$info_id);
	
				$sumOfFees += $fees;
				$fee_month_list[] = $monthArray[$i-1];
			}
			
			
		}			
	}
}
	// remove this after adjustion absent and late fee dynamically
	if($val->id == $absentSubHeadId || $val->id == $lateSubHeadId){
		$sumOfFees = $_POST['subheadid'][$key];;
	}

	// save monthly collection details here
	if($sumOfFees > 0){
			$insert = $wpdb->insert(
			'ct_student_fee_collection_details',
			array(
				'info_id' 		=> $info_id,								
				'sub_head_id' 	=> $val->id,
				'fee' 	=> $sumOfFees,
				'status' 	=> 1,
				'reference' 	=> 'Monthly Summary',
				'date' 	=> $_POST['fee-date'],
				'created_by' 	=> get_current_user_id(),
				'created_at' 	=> date('Y-m-d H-i-s')
			)
		);
	}
	

	

}else if($val->type == 2){
	// yearly
	$feeInfoQuery = "SELECT fee FROM ct_student_yearly_fee_summary WHERE sub_head_id = $val->id AND class_id = $class AND year = '$year' AND student_id = $std_id";
	if(isset($section) && !empty($section)){
		$feeInfoQuery .= " AND section = $section";
	}
	if(isset($group) && !empty($group)){
		$feeInfoQuery .= " AND group_id = $group";
	}
	$feeInfo = $wpdb->get_results($feeInfoQuery);
		if(!$feeInfo){
			if( $val->id == $admissionFeeSubHeadId || $val->id == $ictFeeSubHeadId){
				$fees = $_POST['subheadid'][$key]; //for editable admission fee etc
				// echo '<pre>';print_r($_POST);exit;
				if($admission_type == 1){
					// NEW ADMITTED STUDENT
					// save yearly summary 
				// 	if($facilities == 'Half free' ){
				// 	    $fees = ($fees/2);
				// 	}
					$insert = $wpdb->insert(
						'ct_student_yearly_fee_summary',
						array(
							'student_id' 		=> $std_id,
							'year' 	=> $year,
							'class_id' 	=> $class,
							'section' 	=> $section,
							'group_id' 	=> $group,
							'info_id' 	=> $info_id,						
							'sub_head_id' 	=> $val->id,
							'fee' 	=> $fees,
							'status' 	=> 1,
							'notes' 	=> 'Yearly Summary (NEW ADMITTED)',
							'date' 	=> $_POST['fee-date'],
							'created_by' 	=> get_current_user_id(),
							'created_at' 	=> date('Y-m-d H-i-s')
						)
					);

					$last_id_year = $wpdb->insert_id;
					// save ledger $reference,$monthly_fee_id,$yearly_fee_id,$exam_fee_id
					saveLeadger($val->id ,$fees,0, 'Collection Reference ID (NEW ADMITTED)-'.$info_id,null,$last_id_year,null,$_POST['fee-date'],$info_id);

					// save yearly collection details here
					$insert = $wpdb->insert(
						'ct_student_fee_collection_details',
						array(
							'info_id' 		=> $info_id,								
							'sub_head_id' 	=> $val->id,
							'fee' 	=> $fees,
							'status' 	=> 1,
							'reference' 	=> 'Yearly Collection (NEW ADMITTED)',
							'date' 	=> $_POST['fee-date'],
							'created_by' 	=> get_current_user_id(),
							'created_at' 	=> date('Y-m-d H-i-s')
						)
					);
				}else{
				//     if($facilities == 'Half free' ){
				// 	    $fees = ($fees/2);
				// 	}
					// PROMOTED STUDENT
					// save yearly summary 
					$feesquery = "SELECT amount FROM ct_admission_fee_promoted WHERE class = $class";
					$promotedfees = $wpdb->get_results($feesquery);
					if(@$promotedfees && @$promotedfees[0]->amount > 0){						
						$fees = $promotedfees[0]->amount;
						if($facilities == 'Half free' ){
    					    $fees = ($fees/2);
    					}
					}
					 
					$insert = $wpdb->insert(
						'ct_student_yearly_fee_summary',
						array(
							'student_id' 		=> $std_id,
							'year' 	=> $year,
							'class_id' 	=> $class,
							'section' 	=> $section,
							'group_id' 	=> $group,	
							'info_id' 	=> $info_id,					
							'sub_head_id' 	=> $val->id,
							'fee' 	=> $fees,
							'status' 	=> 1,
							'notes' 	=> 'Yearly Summary (PROMOTED)',
							'date' 	=> $_POST['fee-date'],
							'created_by' 	=> get_current_user_id(),
							'created_at' 	=> date('Y-m-d H-i-s')
						)
					);

					$last_id_year = $wpdb->insert_id;
					// save ledger $reference,$monthly_fee_id,$yearly_fee_id,$exam_fee_id
					saveLeadger($val->id ,$fees,0, 'Collection Reference ID (PROMOTED)-'.$info_id,null,$last_id_year,null,$_POST['fee-date'],$info_id);

					// save yearly collection details here
					$insert = $wpdb->insert(
						'ct_student_fee_collection_details',
						array(
							'info_id' 		=> $info_id,								
							'sub_head_id' 	=> $val->id,
							'fee' 	=> $fees,
							'status' 	=> 1,
							'reference' 	=> 'Yearly Collection (PROMOTED)',
							'date' 	=> $_POST['fee-date'],
							'created_by' 	=> get_current_user_id(),
							'created_at' 	=> date('Y-m-d H-i-s')
						)
					);
				}
			}else if($val->id == $registrationFeeSubHeadId){
				$checkfees = "SELECT amount FROM ct_student_wise_fee WHERE fee_type = 2 AND student_id = $std_id  AND class_id = $class AND year = '$year'";
				if(isset($section) && !empty($section)){
					$checkfees .= " AND section = $section";
				}
				if(isset($group) && !empty($group)){
					$checkfees .= " AND group_id = $group";
				}
				$studentwisefees = $wpdb->get_results($checkfees);
				if($studentwisefees && $studentwisefees[0]->amount > 0){						
					$fees = $studentwisefees[0]->amount;
				}else{
					$fees = $fees;
				}
				$insert = $wpdb->insert(
					'ct_student_yearly_fee_summary',
					array(
						'student_id' 		=> $std_id,
						'year' 	=> $year,
						'class_id' 	=> $class,
						'section' 	=> $section,
						'group_id' 	=> $group,	
						'info_id' 	=> $info_id,					
						'sub_head_id' 	=> $val->id,
						'fee' 	=> $fees,
						'status' 	=> 1,
						'notes' 	=> 'Yearly Summary (Registration Fee)',
						'date' 	=> $_POST['fee-date'],
						'created_by' 	=> get_current_user_id(),
						'created_at' 	=> date('Y-m-d H-i-s')
					)
				);

				$last_id_year = $wpdb->insert_id;
				// save ledger $reference,$monthly_fee_id,$yearly_fee_id,$exam_fee_id
				saveLeadger($val->id ,$fees,0, 'Collection Reference ID (Registration Fee)-'.$info_id,null,$last_id_year,null,$_POST['fee-date'],$info_id);

				// save yearly collection details here
				$insert = $wpdb->insert(
					'ct_student_fee_collection_details',
					array(
						'info_id' 		=> $info_id,								
						'sub_head_id' 	=> $val->id,
						'fee' 	=> $fees,
						'status' 	=> 1,
						'reference' 	=> 'Yearly Collection (Registration Fee)',
						'date' 	=> $_POST['fee-date'],
						'created_by' 	=> get_current_user_id(),
						'created_at' 	=> date('Y-m-d H-i-s')
					)
				);
			}else if($val->id == $admissionFormSubHeadId){
					if($facilities == 'Half free' ){
    					    $fees = ($fees/2);
    					}
				$insert = $wpdb->insert(
					'ct_student_yearly_fee_summary',
					array(
						'student_id' 		=> $std_id,
						'year' 	=> $year,
						'class_id' 	=> $class,
						'section' 	=> $section,
						'group_id' 	=> $group,	
						'info_id' 	=> $info_id,					
						'sub_head_id' 	=> $val->id,
						'fee' 	=> $fees,
						'status' 	=> 1,
						'notes' 	=> 'Yearly Summary (Session Fee)',
						'date' 	=> $_POST['fee-date'],
						'created_by' 	=> get_current_user_id(),
						'created_at' 	=> date('Y-m-d H-i-s')
					)
				);

				$last_id_year = $wpdb->insert_id;
				// save ledger $reference,$monthly_fee_id,$yearly_fee_id,$exam_fee_id
				saveLeadger($val->id ,$fees,0, 'Collection Reference ID (Session Fee)-'.$info_id,null,$last_id_year,null,$_POST['fee-date'],$info_id);

				// save yearly collection details here
				$insert = $wpdb->insert(
					'ct_student_fee_collection_details',
					array(
						'info_id' 		=> $info_id,								
						'sub_head_id' 	=> $val->id,
						'fee' 	=> $fees,
						'status' 	=> 1,
						'reference' 	=> 'Yearly Collection (Session Fee)',
						'date' 	=> $_POST['fee-date'],
						'created_by' 	=> get_current_user_id(),
						'created_at' 	=> date('Y-m-d H-i-s')
					)
				);
			}else{
				// save yearly summary 
				$insert = $wpdb->insert(
					'ct_student_yearly_fee_summary',
					array(
						'student_id' 		=> $std_id,
						'year' 	=> $year,
						'class_id' 	=> $class,
						'section' 	=> $section,
						'group_id' 	=> $group,	
						'info_id' 	=> $info_id,					
						'sub_head_id' 	=> $val->id,
						'fee' 	=> $fees,
						'status' 	=> 1,
						'notes' 	=> 'Yearly Summary',
						'date' 	=> $_POST['fee-date'],
						'created_by' 	=> get_current_user_id(),
						'created_at' 	=> date('Y-m-d H-i-s')
					)
				);

				$last_id_year = $wpdb->insert_id;
				// save ledger $reference,$monthly_fee_id,$yearly_fee_id,$exam_fee_id
				saveLeadger($val->id ,$fees,0, 'Collection Reference ID-'.$info_id,null,$last_id_year,null,$_POST['fee-date'],$info_id);

				// save yearly collection details here
				$insert = $wpdb->insert(
					'ct_student_fee_collection_details',
					array(
						'info_id' 		=> $info_id,								
						'sub_head_id' 	=> $val->id,
						'fee' 	=> $fees,
						'status' 	=> 1,
						'reference' 	=> 'Yearly Collection',
						'date' 	=> $_POST['fee-date'],
						'created_by' 	=> get_current_user_id(),
						'created_at' 	=> date('Y-m-d H-i-s')
					)
				);
			}

			

			
		}
}else if($val->type == 3){
	// exam
	// get active exam id
	$activeExamId = $wpdb->get_results("SELECT examid FROM ct_exam WHERE active_for_collection = 1 AND examClass = $class LIMIT 1");
	if($activeExamId){
		$activeExamId = $activeExamId[0]->examid;
		$feeInfoQuery = "SELECT fee FROM ct_student_exam_fee_summary WHERE sub_head_id = $val->id AND class_id = $class AND exam_id = $activeExamId AND year = '$year' AND student_id = $std_id";
		if(isset($section) && !empty($section)){
			$feeInfoQuery .= " AND section = $section";
		}
		if(isset($group) && !empty($group)){
			$feeInfoQuery .= " AND group_id = $group";
		}
		$feeInfo = $wpdb->get_results($feeInfoQuery);

		if(!$feeInfo){
			// save exam summary 
			$insert = $wpdb->insert(
				'ct_student_exam_fee_summary',
				array(
					'student_id' 		=> $std_id,
					'year' 	=> $year,
					'class_id' 	=> $class,
					'section' 	=> $section,
					'group_id' 	=> $group,	
					'info_id' 	=> $info_id,					
					'exam_id' 	=> $activeExamId,						
					'sub_head_id' 	=> $val->id,
					'fee' 	=> $fees,
					'status' 	=> 1,
					'notes' 	=> 'Exam Summary',
					'date' 	=> $_POST['fee-date'],
					'created_by' 	=> get_current_user_id(),
					'created_at' 	=> date('Y-m-d H-i-s')
				)
			);

			$last_id_exam = $wpdb->insert_id;
			// save ledger $reference,$monthly_fee_id,$yearly_fee_id,$exam_fee_id
			saveLeadger($val->id ,$fees,0, 'Collection Reference ID-'.$info_id,null,null,$last_id_exam,$_POST['fee-date'],$info_id);

			// save yearly collection details here
			$insert = $wpdb->insert(
				'ct_student_fee_collection_details',
				array(
					'info_id' 		=> $info_id,								
					'sub_head_id' 	=> $val->id,
					'fee' 	=> $fees,
					'status' 	=> 1,
					'reference' 	=> 'Exam Collection',
					'date' 	=> $_POST['fee-date'],
					'created_by' 	=> get_current_user_id(),
					'created_at' 	=> date('Y-m-d H-i-s')
				)
			);
		}	
	}
	
}else if($val->type == 4){
	// OTHER
	// save Other collection details here
	$insert = $wpdb->insert(
		'ct_student_fee_collection_details',
		array(
			'info_id' 		=> $info_id,								
			'sub_head_id' 	=> $val->id,
			'fee' 	=> $fees,
			'status' 	=> 1,
			'reference' 	=> 'Exam Collection',
			'date' 	=> $_POST['fee-date'],
			'created_by' 	=> get_current_user_id(),
			'created_at' 	=> date('Y-m-d H-i-s')
		)
	);
	
	// save ledger $reference,$monthly_fee_id,$yearly_fee_id,$exam_fee_id
	saveLeadger($val->id ,$fees,0, 'Collection Reference ID-'.$info_id,null,null,null,$_POST['fee-date'],$info_id);
}
}
// echo '<pre>'; print_r($result);exit;
// print_r( $fees[0]->fee);exit;
// $fees =  getDefaultFee($val->id,$class, $year, $group );

}
//  $_SESSION['collection_info_id'] = $info_id;
$message = ms3message($insert, 'Save Successfully');

$collection_info_id = $info_id;;
//   echo  $_SESSION['collection_info_id'];exit;



    
        $qry = "SELECT sfci.*,ct_student.stdName, ct_student.stdPhone, sm_users.display_name
            FROM ct_student_fee_collection_info as sfci  
            LEFT JOIN ct_studentinfo ON sfci.student_id = ct_studentinfo.infoStdid AND ct_studentinfo.infoClass = sfci.class_id AND ct_studentinfo.infoYear = sfci.year
            LEFT JOIN ct_student ON sfci.student_id = ct_student.studentid
            LEFT JOIN sm_users ON sfci.created_by = sm_users.ID
            LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId  
            LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid         
            WHERE sfci.id = '$collection_info_id' GROUP BY student_id ORDER BY sectionid,infoRoll";
            
        // $qry = "SELECT ct_student_fee_collection_info.*
        //     FROM ct_student_fee_collection_info            
        //     WHERE ct_student_fee_collection_info.id = '$collection_info_id'";

      
    $feeInfo = $wpdb->get_results( $qry );
    
    

        $qry2 = "SELECT fee, sub_head_id
            FROM ct_student_fee_collection_details
            WHERE info_id = '$collection_info_id'";

      
    $feeDetails = $wpdb->get_results( $qry2 );
    $feeDetailsArray = [];
    foreach($feeDetails as $val){
        $feeDetailsArray[$val->sub_head_id] = $val->fee;
    }
    
    // send sms
    
    try{
    $post_url = "http://api.smsinbd.com/sms-api/sendsms" ;  
      
			$post_values = array( 
			'api_token' => 'Pjjwy7TqETCErp02CPvzl1HeBBKIHiXZnMjEBbbr',
			'senderid' => '8801969908462',
			'message' => 'Dear '.$feeInfo[0]->stdName.', Your fee amount '.$feeInfo[0]->total.'TK has been received on '.date("d-m-Y").' for '. $monthArray[$feeInfo[0]->month - 1] .'-'. $feeInfo[0]->year.'.',
			'contact_number' => $stdPhone,
			);
			
			$post_string = "";
			foreach( $post_values as $key => $value )
				{ $post_string .= "$key=" . urlencode( $value ) . "&"; }
			   $post_string = rtrim( $post_string, "& " );
			  
			$request = curl_init($post_url);
				curl_setopt($request, CURLOPT_HEADER, 0);
				curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);  
				curl_setopt($request, CURLOPT_POSTFIELDS, $post_string); 
				curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE);  
				$post_response = curl_exec($request);  
			   curl_close ($request);  
		
			$array =  json_decode( preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $post_response), true ); 
}catch(Exception $ex){
    
}
?>

<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>

    <div id="printArea" class="col-md-12">
    <style type="text/css"> @page { size: auto;  margin: 0px; }
					  				.feeTbl th, .feeTbl td {
											text-align: center;
											vertical-align: middle !important;
											font-size: 11px;
											padding: 3px 2px !important;
											-webkit-print-color-adjust: exact;
										}
					  			</style>
                                 <style>
		@media print {
			.col-md-6 {
					/* float: left !important; */
					width: 100% !important;

			}
			.col-md-4 {
					/* float: left !important; */
					width: 100% !important;

			}
			.table-bordered{
				border-collapse: collapse;
			}
			.print-area-multiple{
				/* margin-left: 5px;  */
				/* width: 210mm;
				height: 297mm;  */
				margin: 0;
				/* border: initial; */
				/* border-radius: initial; */
				width: initial;
				min-height: initial;
				/* box-shadow: initial;
				background: initial; */
				page-break-after: always;
			}
		}
</style>
<style>
	.width-43{
		width: 45%  !important;
	}
</style>
<style>
@media print {
  .bottom-row {
    position: fixed;
    bottom: 0;
    width: 45%;
    page-break-after: always;
  }

  .bottom-row.left {
    left: 0;
  }

  .bottom-row.right {
    right: 0;
  }
}
</style>
        <div style="margin-left: 5px;">
        <div class="container">
            <div class="row">
				<table>
					<tr>
						<td class="width-43">
							<div class="form-heading">
								<div>
								<!-- <div class="container"> -->
									<div class="form-heading-content" style="text-align: center; margin: 10px 0px;">
									<img height="50px" style="position: absolute;left: 10px;top: 40px" src="<?= $s3sRedux['instLogo']['url'] ?>">

										<h4 style="margin-bottom: 0px"><?= $s3sRedux['institute_name'] ?></h4>
										<span style="color:#2b5591; font-size: 14px; margin: 0;"><?= $s3sRedux['institute_address'] ?></span><br>
										<span>Student Fees Memo</span><br>
										<span>Institute Copy</span><br>
									</div>
								</div>
							</div>
							<table class="table table-bordered" style="border: 1px solid black; font-size:12px;">            
								<tbody style="border-top: 0px;">
									<tr>
										<td style="width: 200px;">Transaction No: <?= $feeInfo[0]->id ?></td>
										<td style="width: 200px;">Student ID: <?= $feeInfo[0]->student_roll ?></td>

										<!-- <td style="width: 200px;">Memo No: M-<?= $feeInfo[0]->id ?></td> -->
										<td style="width: 200px;">Date: <?= date("d/m/Y", strtotime($feeInfo[0]->date) )?></td>     
									</tr>
								</tbody>
							</table>
							<table class="table table-bordered" style="border: 1px solid black;font-size:13px;">            
								<tbody style="border-top: 0px;">
									<tr>
										<td style="width: 300px;">Name: <?= $std_name?></td>
										<!-- <td style="width: 300px;">Name: <?= $feeInfo[0]->stdName?></td> -->
										<td style="width: 150px;">Phone: <?= $stdPhone ?></td>
										<!-- <td style="width: 150px;">ID NO: <?= $feeInfo[0]->student_roll ?></td>      -->
									</tr>
								</tbody>
							</table>
							<table class="table table-bordered" style="border: 1px solid black;font-size:12px;">            
								<tbody style="border-top: 0px;">
									<tr>
										<td style="width: 200px;">Class: <?= getClassNameById($feeInfo[0]->class_id) ?></td>
										<td style="width: 200px;">Section: Section <?= getSectionNameById($feeInfo[0]->section) ?></td>
										<td style="width: 200px;">Month: <?= $monthArray[$feeInfo[0]->month - 1]?> - <?= $feeInfo[0]->year ?></td>     
									</tr>
								</tbody>
							</table>
							<table class="table table-bordered feeTbl" style="border: 1px solid black;">            
								<tbody style="border-top: 0px;">
							   
									<tr>
										<td style="text-align: center;width: 200px;border: 1px solid black ">SL No</td>
										<td style="width: 400px; border: 1px solid black ">Particular</td>
										<td style="text-align: end;width: 200px; border: 1px solid black ">Tk</td>     
									</tr>
									<?php
										$subHeadInfo = $wpdb->get_results("SELECT * FROM ct_sub_head
										WHERE  relation_to = 1  ORDER BY sort_order ASC");
		
										$i = 1;
										foreach($subHeadInfo as $val){
									?>
									<tr style="border: 1px solid black;">
										<td style="text-align: center;border: 1px solid black "><?= $i ?></td>
										<td style="width: 400px;border: 1px solid black "><?= $subHeadInfo[$i-1]->sub_head_name  ?></td>
										<td style="text-align: end;border: 1px solid black "><?= @$feeDetailsArray[$val->id] == null ? '0.00' : @$feeDetailsArray[$val->id]?></td>     
									</tr>
									
									<?php
									   $i++; }
									?>
									
								   
									<tr>
										<td style="text-align: center;"></td>
										<td style="width: 400px;">
										<table>
														<tr>
															<td style="width:200px">
																<table class="table table-bordered" style="border: 1px solid #000; width:100%;margin-left: 20px">
																	<tr><td style="border: 1px solid #000;">Total Due</td></tr>
																	<tr><td style="border: 1px solid #000;">0</td></tr>
																</table>
															</td>
															<td style="width:200px">
																
																	<span style="display: grid;text-align: end;">
																		<span>Total Fee</span>
																		<span style="margin-top:5px;;">Gross Total</span>
																		<span>Poor Fund</span>
																		<span>Total Received</span>
																	</span>  
															</td>
														</tr>
													</table>
										</td>
										<td style="text-align: end; padding-left: 0;
										padding-right: 0px;">
											<span style="padding-right: 0.5rem;"><?= $feeInfo[0]->sub_total ?></span>
											
											<div style="margin-top:5px;;border-top: 1px solid #000;">
												<span style="padding-right: 0.5rem;"><?= $feeInfo[0]->sub_total ?></span> <br>
												<span style="padding-right: 0.5rem;"><?= $feeInfo[0]->remission ?></span>
											</div>
											<div style="border-top: 1px solid #000;">
												<span style="padding-right: 0.5rem;"><?= $feeInfo[0]->total ?></span> <br>
											</div>
											
										</td>     
									</tr>
								</tbody>
							</table>
							<table class="table table-bordered" style="border: 1px solid #000;font-size:13px;">            
								<tbody style="border-top: 0px;">
									<tr>
										<td style="width: 150px; text-align: center;">In Words</td>
										<td style="width: 350px;"><?= convertNumberToWord($feeInfo[0]->total)?></td>
										 
									</tr>
								</tbody>
							</table>
							<div style="margin-top: 30px;" class="bottom-row">
								<div class="row">
									<table>
									    <tr>
											<td style="width:200px">
											</td>
											<td style="width:200px"></td>
											<td style="width:200px">
											    <p><?= $feeInfo[0]->display_name?></p>
											</td>
										</tr>
										<tr>
											<td style="width:200px">
												<h6>Student's Signature</h6>
											</td>
											<td style="width:200px"></td>
											<td style="width:200px">
												<h6>Official's Signature</h6>
											</td>
										</tr>
									</table>
									<!-- <div class="col-md-6">
										<div class="stdnt-signature">
											<h6>Student's Signature</h6>
										</div>
									</div>
									<div class="col-md-6">
										<div class="officals-signature" style="text-align: end;">
											<h6>Official's Signature</h6>
										</div>
									</div> -->
								</div>
							</div>
						</td>
						<td>
							<style>
								.vl {
										border-left: 1px dotted black;
										height: 100%;
										position: absolute;
										left: 50%;
										/* margin-left: -3px; */
										top: 0;
									}
							</style>
							<div class="vl"></div>
						</td>
						<td class="width-43">
						<div class="form-heading">
								<div>
								<!-- <div class="container"> -->
									<div class="form-heading-content" style="text-align: center; margin: 10px 0px;">
									<img height="50px" style="position: absolute;left: 54%;top: 40px" src="<?= $s3sRedux['instLogo']['url'] ?>">
										<h4 style="margin-bottom: 0px"><?= $s3sRedux['institute_name'] ?></h4>
										<span style="color:#2b5591; font-size: 14px; margin: 0;"><?= $s3sRedux['institute_address'] ?></span><br>
										<span>Student Fees Memo</span><br>
										<span>Student Copy</span><br>
									</div>
								</div>
							</div>
							<table class="table table-bordered" style="border: 1px solid black; font-size:12px;">            
								<tbody style="border-top: 0px;">
									<tr>
										<td style="width: 200px;">Transaction No: <?= $feeInfo[0]->id ?></td>
										<td style="width: 200px;">Student ID: <?= $feeInfo[0]->student_roll ?></td>
										<td style="width: 200px;">Date: <?= date("d/m/Y", strtotime($feeInfo[0]->date) )?></td>     
									</tr>
								</tbody>
							</table>
							<table class="table table-bordered" style="border: 1px solid black;font-size:13px;">            
								<tbody style="border-top: 0px;">
									<tr>
										<td style="width: 300px;">Name: <?= $std_name?></td>
										<!-- <td style="width: 300px;">Name: <?= $feeInfo[0]->stdName?></td> -->
										<td style="width: 150px;">Phone: <?= $stdPhone ?></td>
										<!-- <td style="width: 150px;">ID NO: <?= $feeInfo[0]->student_roll ?></td>      -->
									</tr>
								</tbody>
							</table>
							<table class="table table-bordered" style="border: 1px solid black;font-size:12px;">            
								<tbody style="border-top: 0px;">
									<tr>
										<td style="width: 200px;">Class: <?= getClassNameById($feeInfo[0]->class_id) ?></td>
										<td style="width: 200px;">Section: Section <?= getSectionNameById($feeInfo[0]->section) ?></td>
										<td style="width: 200px;">Month: <?= $monthArray[$feeInfo[0]->month - 1]?> - <?= $feeInfo[0]->year ?></td>     
									</tr>
								</tbody>
							</table>
							<table class="table table-bordered feeTbl" style="border: 1px solid black;">            
								<tbody style="border-top: 0px;">
							   
									<tr>
										<td style="text-align: center;width: 200px;border: 1px solid black ">SL No</td>
										<td style="width: 400px; border: 1px solid black ">Particular</td>
										<td style="text-align: end;width: 200px; border: 1px solid black ">Tk</td>     
									</tr>
									<?php
										// $subHeadInfo = $wpdb->get_results("SELECT * FROM ct_sub_head
										// WHERE  relation_to = 1  ORDER BY sort_order ASC");
		
										$i = 1;
										foreach($subHeadInfo as $val){
									?>
									<tr style="border: 1px solid black;">
										<td style="text-align: center;border: 1px solid black "><?= $i ?></td>
										<td style="width: 400px;border: 1px solid black "><?= $subHeadInfo[$i-1]->sub_head_name  ?></td>
										<td style="text-align: end;border: 1px solid black "><?= @$feeDetailsArray[$val->id] == null ? '0.00' : @$feeDetailsArray[$val->id]?></td>     
									</tr>
									
									<?php
									   $i++; }
									?>
									
								   
									<tr>
										<td style="text-align: center;"></td>
										<td style="width: 400px;">
										<table>
														<tr>
															<td style="width:200px">
																<table class="table table-bordered" style="border: 1px solid #000; width:100%;margin-left: 20px">
																	<tr><td style="border: 1px solid #000;">Total Due</td></tr>
																	<tr><td style="border: 1px solid #000;">0</td></tr>
																</table>
															</td>
															<td style="width:200px">
																
																	<span style="display: grid;text-align: end;">
																		<span>Total Fee</span>
																		<span style="margin-top:5px;;">Gross Total</span>
																		<span>Poor Fund</span>
																		<span>Total Received</span>
																	</span>  
															</td>
														</tr>
													</table>
										</td>
										<td style="text-align: end; padding-left: 0;
										padding-right: 0px;">
											<span style="padding-right: 0.5rem;"><?= $feeInfo[0]->sub_total ?></span>
											
											<div style="margin-top:5px;;border-top: 1px solid #000;">
												<span style="padding-right: 0.5rem;"><?= $feeInfo[0]->sub_total ?></span> <br>
												<span style="padding-right: 0.5rem;"><?= $feeInfo[0]->remission ?></span>
											</div>
											<div style="border-top: 1px solid #000;">
												<span style="padding-right: 0.5rem;"><?= $feeInfo[0]->total ?></span> <br>
											</div>
											
										</td>     
									</tr>
								</tbody>
							</table>
							<table class="table table-bordered" style="border: 1px solid #000;font-size:13px;">            
								<tbody style="border-top: 0px;">
									<tr>
										<td style="width: 150px; text-align: center;">In Words</td>
										<td style="width: 350px;"><?= convertNumberToWord($feeInfo[0]->total)?></td>
										 
									</tr>
								</tbody>
							</table>
							<div style="margin-top: 30px;" class="bottom-row">
								<div class="row">
									<table>
									    <tr>
											<td style="width:200px">
											</td>
											<td style="width:200px"></td>
											<td style="width:200px">
											    <p><?= $feeInfo[0]->display_name?></p>
											</td>
										</tr>
										<tr>
											<td style="width:200px">
												<h6>Student's Signature</h6>
											</td>
											<td style="width:200px"></td>
											<td style="width:200px">
												<h6>Official's Signature</h6>
											</td>
										</tr>
									</table>
									<!-- <div class="col-md-6">
										<div class="stdnt-signature">
											<h6>Student's Signature</h6>
										</div>
									</div>
									<div class="col-md-6">
										<div class="officals-signature" style="text-align: end;">
											<h6>Official's Signature</h6>
										</div>
									</div> -->
								</div>
							</div>
		
						</td>
						<!--<td>-->
						<!--	<style>-->
						<!--		.vl2 {-->
						<!--				border-left: 1px dotted black;-->
						<!--				height: 100%;-->
						<!--				position: absolute;-->
						<!--				left: 69%;-->
										<!--/* margin-left: -3px; */-->
						<!--				top: 0;-->
						<!--			}-->
						<!--	</style>-->
						<!--	<div class="vl2"></div>-->
						<!--</td>-->
						
					</tr>
				</table>
                
                                </div>
            
        </div>
    </div>
    </div>
  
				<?php } }
			


			
			?>
  	<?php }elseif($_GET['view'] == 'transport'){ 
		  $id = null;
		if(isset($_GET['id']) != ''){ 
			$id = $_GET['id'];
			$updateInfo = $wpdb->get_results( "SELECT * FROM ct_transport_fee_list WHERE id = $id" );
			$updateInfo = $updateInfo[0];
		}
	?>
			<div class="panel panel-info">
			  <div class="panel-heading"><h3>Transport Fee</h3></div>
			  	
			  <div class="panel-body" >
				<form action="" method="POST" class="form-inline">
					<div class="row pl-10">
						<div class="form-group result-class">
						  <label>Transport Fee Title</label>
						  <input id="fee_name" type="text" name="fee_name" value="<?= $id == null? '' : $updateInfo->fee_name ?>" required>
					  </div>					 
					  <div class="form-group">
						<label>Distance</label>
						<input id="distance" type="text" name="distance"  value="<?= $id == null? '' : $updateInfo->distance ?>" required>
					</div>
					  <div class="form-group">
						  <label>Amount</label>
						  <input id="amount" type="number" name="amount"  value="<?= $id == null? '' : $updateInfo->amount ?>" required>
					  </div>
					  <div class="form-group">
						  <?php if(isset($_GET['id']) != ''){ ?>
							<input id="transport_id" type="hidden" name="id" value="<?= $id?>" required>

							<button name="transport-update" type="submit">Update</button>
						<?php }else{ ?>
							<button name="transport" type="submit">Save</button>
						<?php } ?>
					  </div>
					</div>
							  		
					  </form>
					  <br><br>
					  <h2>Transport Fee Table</h2>
						<table class="table table-bordered" style="width: 100%; text-align: center;">
							<tr style="text-align: center;">
								<th style=" text-align: center;">No</th>
								<th style=" text-align: center;">Fee Title</th>
								<th style=" text-align: center;">Distance</th>
								<th style=" text-align: center;">Amount</th>
								<th style=" text-align: center;">Action</th>
							</tr>
							<?php  
							$allLists = $wpdb->get_results( "SELECT * FROM ct_transport_fee_list" );

							foreach($allLists as $key=>$val){?>
							<tr>
							  <td><?= $key + 1?></td>
							  <td><?= $val->fee_name?></td>
							  <td><?= $val->distance?></td>
							  <td><?= $val->amount?></td>
							  <td>			<a href="?page=studentFeeManagement&view=transport&id=<?= $val->id?>" class="btn btn-primary pull-right">Edit </a>
							  </td>
							</tr>
							<?php 
					  } ?>
						</table>
					</div>
			</div>
			<?php
if (isset($_POST['transport'])) {

	$insert = $wpdb->insert(
		'ct_transport_fee_list',
		array(
			'fee_name' 	=> $_POST['fee_name'],
			'amount' 	=> $_POST['amount'],
			'distance' 	=> $_POST['distance']
		)
	);
	$message = ms3showMessage($insert, 'Added');
	//header("Refresh:0");
	if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')   
			$url = "https://";   
		else  
			$url = "http://";   
		// Append the host(domain name, ip) to the URL.   
		$url.= $_SERVER['HTTP_HOST'];   
		$url.= $_SERVER['REQUEST_URI'];   
		header("Location: $url");
		die();
	 }elseif (isset($_POST['transport-update']) ) {
		$update = $wpdb->update(
			'ct_transport_fee_list',
			array(
				'fee_name' 	=> $_POST['fee_name'],
			'amount' 	=> $_POST['amount'],
			'distance' 	=> $_POST['distance']
			),
			array( 'id' => $_POST['id'])
		);
		$message = ms3message($update, 'Updated');
		//header("Refresh:0");
		if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')   
         $url = "https://";   
    else  
         $url = "http://";   
    // Append the host(domain name, ip) to the URL.   
    $url.= $_SERVER['HTTP_HOST'];   
	$url.= $_SERVER['REQUEST_URI'];   
		header("Location: $url");
die();
	}
	?>
  	<?php }elseif($_GET['view'] == 'promoted'){ 
		  $id = null;
		if(isset($_GET['id']) != ''){ 
			$id = $_GET['id'];
			$updateInfo = $wpdb->get_results( "SELECT * FROM ct_admission_fee_promoted WHERE id = $id" );
			$updateInfo = $updateInfo[0];
		}
	?>
			<div class="panel panel-info">
			  <div class="panel-heading"><h3>Promoted Fee</h3></div>
			  	
			  <div class="panel-body" >
				<form action="" method="POST" class="form-inline">
					<div class="row pl-10">					 
					  <div class="form-group">
					  <label>For Class</label>
						<select class="form-control className" name="promotedClass" selected="<?= $id == null? '' : @$updateInfo->class ?>"  required>
							<?php
								
								$classes = $wpdb->get_results( "SELECT * FROM ct_class" );
								foreach ($classes as $class) {
									?>
									<option value='<?= $class->classid ?>' <?= $class->classid == @$updateInfo->class? 'selected':'' ?>>
										<?= $class->className ?>
									</option>
								<?php
											}
										?>
						</select>					
						</div>
					  <div class="form-group">
						  <label>Amount</label>
						  <input id="amount" type="number" name="amount"  value="<?= $id == null? '' : @$updateInfo->amount ?>" required>
					  </div>
					  <div class="form-group">
						  <?php if(isset($_GET['id']) != ''){ ?>
							<input id="promoted_admission_id" type="hidden" name="id" value="<?= $id?>" required>

							<button name="promoted-admission-update" type="submit">Update</button>
						<?php }else{ ?>
							<button name="promoted-admission" type="submit">Save</button>
						<?php } ?>
					  </div>
					</div>
							  		
					  </form>
					  <br><br>
					  <h2>Promoted Fee Table</h2>
						<table class="table table-bordered" style="width: 100%; text-align: center;">
							<tr style="text-align: center;">
								<th style=" text-align: center;">No</th>
								<th style=" text-align: center;">Class</th>
								<th style=" text-align: center;">Amount</th>
								<th style=" text-align: center;">Action</th>
							</tr>
							<?php  
							$allLists = $wpdb->get_results( "SELECT * FROM ct_admission_fee_promoted LEFT JOIN ct_class ON ct_admission_fee_promoted.class = ct_class.classid" );

							foreach($allLists as $key=>$val){?>
							<tr>
							  <td><?= $key + 1?></td>
							  <td><?= $val->className?></td>
							  <td><?= $val->amount?></td>
							  <td>			<a href="?page=studentFeeManagement&view=promoted&id=<?= $val->id?>" class="btn btn-primary pull-right">Edit </a>
							  </td>
							</tr>
							<?php 
					  } ?>
						</table>
					</div>
			</div>
			<?php
if (isset($_POST['promoted-admission'])) {
	$Info = $wpdb->get_results( "SELECT * FROM ct_admission_fee_promoted WHERE class = ".$_POST['promotedClass'] );
	$Info = $Info[0];
	if(!$Info){
		$insert = $wpdb->insert(
			'ct_admission_fee_promoted',
			array(
				'class' 	=> $_POST['promotedClass'],
				'amount' 	=> $_POST['amount']
			)
		);
		$message = ms3showMessage($insert, 'Added');
	}else{
		$message = ms3showMessage($insert, 'Already Added');
	}
	//header("Refresh:0");
	if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')   
			$url = "https://";   
		else  
			$url = "http://";   
		// Append the host(domain name, ip) to the URL.   
		$url.= $_SERVER['HTTP_HOST'];   
		$url.= $_SERVER['REQUEST_URI'];   
		header("Location: $url");
		die();
	 }elseif (isset($_POST['promoted-admission-update']) ) {
		$update = $wpdb->update(
			'ct_admission_fee_promoted',
			array(
				'class' 	=> $_POST['promotedClass'],
			'amount' 	=> $_POST['amount']
			),
			array( 'id' => $_POST['id'])
		);
		$message = ms3message($update, 'Updated');
		//header("Refresh:0");
		if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')   
         $url = "https://";   
    else  
         $url = "http://";   
    // Append the host(domain name, ip) to the URL.   
    $url.= $_SERVER['HTTP_HOST'];   
	$url.= $_SERVER['REQUEST_URI'];   
		header("Location: $url");
die();
	}
	?>
  	<?php }elseif($_GET['view'] == 'printReceipt'){ ?>
			<div class="panel panel-info">
			  <div class="panel-heading"><h3>Print Receipt</h3></div>
			  
			  <div class="panel-body" >
				<form action="" method="POST" class="form-inline">
					<div class="row pl-10">
					<div class="form-group result-class">
						  <label>Class</label>
						  <select id='resultClass' class="form-control" name="stdclass" required>
						  <?php
							  $classQuery = $wpdb->get_results( "SELECT classid,className FROM ct_class WHERE classid IN (SELECT infoClass FROM ct_studentinfo GROUP BY infoClass ORDER BY className ASC)" );
							  echo "<option value=''>Select Class</option>";
  
							  foreach ($classQuery as $class) {?>
							  <option value="<?=$class->classid?>" ><?= $class->className ?></option>;
							<?php  }
						  ?>
						  </select>
					  </div>
					  </script>

					  <div class="form-group ">
						  <label>Section</label>
						  <select id="resultSection" class="form-control" name="sec" disabled>
						  <option disabled selected>Select Class First</option>
						  </select>
					  </div>
  
					  <div class="form-group ">
						  <label>Group</label>
						  <select id="resultGroup" class="form-control" name="group">
						  <option value="">Select Group</option>
						  <?php
							  $groups = $wpdb->get_results("SELECT * FROM ct_group");
							  foreach ($groups as $groups) {
							  $selected = ($edit->infoGroup == $groups->groupId) ? 'selected' : '';
							  ?>
							  <option value='<?= $groups->groupId ?>' <?= $selected ?>>
								  <?= $groups->groupName ?>
							  </option>
							  <?php
							  }
						  ?>
						  </select>
					  </div>
  
					  <div class="form-group">
						  <label>Year</label>
						  <select id='resultYear' class="form-control" name="stdyear" required>
						  <option disabled selected>Select Class First</option>
						  </select>
					  </div>
					  <div class="form-group">
						  <label>ID NO:</label>
						  <input id="student_Roll" type="text" name="roll">
					  </div>
					</div>
					<div class="row pl-10">
						
						<div class="form-group">
								<label>From Date </label><br>
								<input id="fee-date" type="date" name="from-date" value="<?php echo date('Y-m-d'); ?>">							
						</div>
						<div class="form-group">
								<label>To Date </label><br>
								<input id="fee-date" type="date" name="to-date" value="<?php echo date('Y-m-d'); ?>">							
						</div>
						
						
					  <div class="form-group">
						  <input class="form-control btn-success" name="printReceipt" type="submit" style ="margin-top:30px" value="Print">
					  </div>
					</div>			  		
					  </form>
				</div>
			</div>
			<?php
if (isset($_POST['printReceipt'])) {
	

$class = $_POST['stdclass'];
$section   = $_POST['sec'];
$year	=  $_POST['stdyear'];
$roll			= @$_POST['roll'];
$group		= $_POST['group'];
$from_date			= $_POST['from-date'];
$to_date			= $_POST['to-date'];
$_SESSION['selectedClass'] = $class;
$_SESSION['selectedSection']= $section;
$_SESSION['selectedYear']		= $year;
$_SESSION['selectedGroup']	= $group;


$qry1 = "SELECT sfci.*
            FROM ct_student_fee_collection_info as sfci 
                  
            WHERE class_id = '$class' AND year = '$year' AND date(date) >= '$from_date' AND date(date) <= '$to_date'";

if(isset($section) && !empty($section)){
	$qry1 .= " AND section = $section";
}
if(isset($group) && !empty($group)){
	$qry1 .= " AND group_id = $group";
}
if(isset($roll) && !empty($roll)){
	$qry1 .= " AND student_roll = '$roll'";
}
$qry1 .= " GROUP BY sfci.student_id ORDER BY student_roll";
$collectionInfo = $wpdb->get_results( $qry1 );

if($collectionInfo){
// echo '<pre>';
// print_r($collectionInfo);exit;
// $collection_info_id = $info_id;;
//   echo  $_SESSION['collection_info_id'];exit;


    
       
?>

<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>

    <div id="printArea" class="col-md-12" >
    <style type="text/css"> 
			@page { size: auto;  margin: 0px;}
		.feeTbl th, .feeTbl td {
				text-align: center;
				vertical-align: middle !important;
				font-size: 11px;
				padding: 3px 2px !important;
				-webkit-print-color-adjust: exact;
			}
			.print-area-multiple{
				margin-left: 5px; 
				width: 297mm;
				/* height: 297mm;  */
			}
	</style>
     <style>
		@media print {
			.col-md-6 {
					/* float: left !important; */
					width: 100% !important;

			}
			.col-md-4 {
					/* float: left !important; */
					width: 100% !important;

			}
			.table-bordered{
				border-collapse: collapse;
			}
			.print-area-multiple{
				/* margin-left: 5px;  */
				/* width: 210mm;
				height: 297mm;  */
				margin: 0;
				/* border: initial; */
				/* border-radius: initial; */
				width: initial;
				min-height: initial;
				/* box-shadow: initial;
				background: initial; */
				page-break-after: always;
			}
		}
		
</style>
<style>
	.width-43{
		width: 45%  !important;
	}
</style>
<style>
@media print {
  .bottom-row {
    position: fixed;
    bottom: 0;
    width: 45%;
    page-break-after: always;
  }

  .bottom-row.left {
    left: 0;
  }

  .bottom-row.right {
    right: 0;
  }
}
</style>
<?php

	foreach($collectionInfo as $val){
		$qry = "SELECT sfci.*,ct_student.stdName, ct_student.stdPhone, sm_users.display_name
		FROM ct_student_fee_collection_info as sfci  
		LEFT JOIN ct_studentinfo ON sfci.student_id = ct_studentinfo.infoStdid AND ct_studentinfo.infoClass = sfci.class_id AND ct_studentinfo.infoYear = sfci.year
		LEFT JOIN ct_student ON sfci.student_id = ct_student.studentid
		LEFT JOIN sm_users ON sfci.created_by = sm_users.ID
		LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId  
		LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid         
		WHERE sfci.id = '$val->id' GROUP BY student_id ORDER BY sectionid,infoRoll";
		
	// $qry = "SELECT ct_student_fee_collection_info.*
	//     FROM ct_student_fee_collection_info            
	//     WHERE ct_student_fee_collection_info.id = '$collection_info_id'";

  
$feeInfo = $wpdb->get_results( $qry );
	$qry2 = "SELECT fee, sub_head_id
		FROM ct_student_fee_collection_details
		WHERE info_id = '$val->id'";

  
$feeDetails = $wpdb->get_results( $qry2 );
$feeDetailsArray = [];
foreach($feeDetails as $val){
	$feeDetailsArray[$val->sub_head_id] = $val->fee;
}


?>
        <div class="print-area-multiple">
        <div class="container">
            <div class="row">
				<table>
					<tr>
						<td class="width-43">
							<div class="form-heading">
								<div>
								<!-- <div class="container"> -->
									<div class="form-heading-content" style="text-align: center; margin: 10px 0px;">
									<img height="50px" style="position: absolute;left: 10px;top: 40px" src="<?= $s3sRedux['instLogo']['url'] ?>">
										<h4 style="margin-bottom: 0px"><?= $s3sRedux['institute_name'] ?></h4>
										<span style="color:#2b5591; font-size: 14px; margin: 0;"><?= $s3sRedux['institute_address'] ?></span><br>
										<span>Student Fees Memo</span><br>
										<span>Institute Copy</span><br>
									</div>
								</div>
							</div>
							<table class="table table-bordered" style="border: 1px solid black; font-size:12px;">            
								<tbody style="border-top: 0px;">
									<tr>
										<td style="width: 200px;">Transaction No: <?= $feeInfo[0]->id ?></td>
										<td style="width: 200px;">Student ID: <?= $feeInfo[0]->student_roll ?></td>
										<td style="width: 200px;">Date: <?= date("d/m/Y", strtotime($feeInfo[0]->date) )?></td>     
									</tr>
								</tbody>
							</table>
							<table class="table table-bordered" style="border: 1px solid black;font-size:13px;">            
								<tbody style="border-top: 0px;">
									<tr>
										<td style="width: 300px;">Name: <?= $feeInfo[0]->stdName?></td>
										<td style="width: 150px;">Phone: <?= $feeInfo[0]->stdPhone ?></td>
										<!-- <td style="width: 150px;">Roll: <?= $feeInfo[0]->student_id ?></td>      -->
									</tr>
								</tbody>
							</table>
							<table class="table table-bordered" style="border: 1px solid black;font-size:12px;">            
								<tbody style="border-top: 0px;">
									<tr>
										<td style="width: 200px;">Class: <?= getClassNameById($feeInfo[0]->class_id) ?></td>
										<td style="width: 200px;">Section: Section <?= getSectionNameById($feeInfo[0]->section) ?></td>
										<td style="width: 200px;">Month: <?= $monthArray[$feeInfo[0]->month - 1]?> - <?= $feeInfo[0]->year ?></td>     
									</tr>
								</tbody>
							</table>
							<table class="table table-bordered feeTbl" style="border: 1px solid black;">            
								<tbody style="border-top: 0px;">
							   
									<tr>
										<td style="text-align: center;width: 200px;border: 1px solid black ">SL No</td>
										<td style="width: 400px; border: 1px solid black ">Particular</td>
										<td style="text-align: end;width: 200px; border: 1px solid black ">Tk</td>     
									</tr>
									<?php
										$subHeadInfo = $wpdb->get_results("SELECT * FROM ct_sub_head
										WHERE  relation_to = 1 ORDER BY sort_order ASC");
		
										$i = 1;
										foreach($subHeadInfo as $val){
									?>
									<tr style="border: 1px solid black;">
										<td style="text-align: center;border: 1px solid black "><?= $i ?></td>
										<td style="width: 400px;border: 1px solid black "><?= $subHeadInfo[$i-1]->sub_head_name  ?></td>
										<td style="text-align: end;border: 1px solid black "><?= @$feeDetailsArray[$val->id] == null ? '0.00' : @$feeDetailsArray[$val->id]?></td>     
									</tr>
									
									<?php
									   $i++; }
									?>
									
								   
									<tr>
										<td style="text-align: center;"></td>
										<td style="width: 400px;">
													<!-- <span style="display:grid; border: 1px solid #000; width: 100px; text-align: center; margin-left: 20px;
													margin-top: 22px;">
														<span>Total Due</span>
														<span style="border-top: 1px solid #000;">0</span>
													</span> -->
													<table>
														<tr>
															<td style="width:200px">
																<table class="table table-bordered" style="border: 1px solid #000; width:100%;margin-left: 20px">
																	<tr><td style="border: 1px solid #000;">Total Due</td></tr>
																	<tr><td style="border: 1px solid #000;">0</td></tr>
																</table>
															</td>
															<td style="width:200px">
																
																	<span style="display: grid;text-align: end;">
																		<span>Total Fee</span>
																		<span style="margin-top:5px;;">Gross Total</span>
																		<span>Poor Fund</span>
																		<span>Total Received</span>
																	</span>  
															</td>
														</tr>
													</table>
													
										</td>
										<td style="text-align: end; padding-left: 0;
										padding-right: 0px;">
											<span style="padding-right: 0.5rem;"><?= $feeInfo[0]->sub_total ?></span>
											
											<div style="margin-top:5px;;border-top: 1px solid #000;">
												<span style="padding-right: 0.5rem;"><?= $feeInfo[0]->sub_total ?></span> <br>
												<span style="padding-right: 0.5rem;"><?= $feeInfo[0]->remission ?></span>
											</div>
											<div style="border-top: 1px solid #000;">
												<span style="padding-right: 0.5rem;"><?= $feeInfo[0]->total ?></span> <br>
											</div>
											
										</td>     
									</tr>
								</tbody>
							</table>
							<table class="table table-bordered" style="border: 1px solid #000;font-size:13px;">            
								<tbody style="border-top: 0px;">
									<tr>
										<td style="width: 150px; text-align: center;">In Words</td>
										<td style="width: 350px;"><?= convertNumberToWord($feeInfo[0]->total)?></td>
										 
									</tr>
								</tbody>
							</table>
							
							<div style="margin-top: 30px;" class="bottom-row">
								<div class="row">
									<table>
									    <tr>
											<td style="width:200px">
											</td>
											<td style="width:200px"></td>
											<td style="width:200px">
											    <p><?= $feeInfo[0]->display_name?></p>
											</td>
										</tr>
										<tr>
											<td style="width:200px">
												<h6>Student's Signature</h6>
											</td>
											<td style="width:200px"></td>
											<td style="width:200px">
												<h6>Official's Signature</h6>
											</td>
										</tr>
									</table>
									<!-- <div class="col-md-6">-->
									<!--	<div class="stdnt-signature">-->
									<!--		<h6>Student's Signature</h6>-->
									<!--	</div>-->
									<!--</div>-->
									<!--<div class="col-md-6">-->
									<!--	<div class="officals-signature" style="text-align: end;">-->
									<!--		<h6>Official's Signature</h6>-->
									<!--	</div>-->
									<!--</div> -->
								</div>
							</div>
						</td>
						<td>
							<style>
								.vl {
										border-left: 1px dotted black;
										height: 100%;
										position: absolute;
										left:50%;
										/* margin-left: -3px; */
										top: 0;
									}
							</style>
							<div class="vl"></div>
						</td>
						<td class="width-43">
						<div class="form-heading">
								<div>
								<!-- <div class="container"> -->
									<div class="form-heading-content" style="text-align: center; margin: 10px 0px;">
									<img height="50px" style="position: absolute;left: 54%;top: 40px" src="<?= $s3sRedux['instLogo']['url'] ?>">
										<h4 style="margin-bottom: 0px"><?= $s3sRedux['institute_name'] ?></h4>
										<span style="color:#2b5591; font-size: 14px; margin: 0;"><?= $s3sRedux['institute_address'] ?></span><br>
										<span>Student Fees Memo</span><br>
										<span>Student Copy</span><br>
									</div>
								</div>
							</div>
							<table class="table table-bordered" style="border: 1px solid black; font-size:12px;">            
								<tbody style="border-top: 0px;">
									<tr>
										<td style="width: 200px;">Transaction No: <?= $feeInfo[0]->id ?></td>
										<td style="width: 200px;">Student ID: <?= $feeInfo[0]->student_roll ?></td>
										<td style="width: 200px;">Date: <?= date("d/m/Y", strtotime($feeInfo[0]->date) )?></td>     
									</tr>
								</tbody>
							</table>
							<table class="table table-bordered" style="border: 1px solid black;font-size:13px;">            
								<tbody style="border-top: 0px;">
									<tr>
										<!-- <td style="width: 300px;">Name: <?= $std_name?></td> -->
										<td style="width: 300px;">Name: <?= $feeInfo[0]->stdName?></td>
										<td style="width: 150px;">Phone: <?= $feeInfo[0]->stdPhone ?></td>
										<!-- <td style="width: 150px;">Roll: <?= $feeInfo[0]->student_roll ?></td>      -->
									</tr>
								</tbody>
							</table>
							<table class="table table-bordered" style="border: 1px solid black;font-size:12px;">            
								<tbody style="border-top: 0px;">
									<tr>
										<td style="width: 200px;">Class: <?= getClassNameById($feeInfo[0]->class_id) ?></td>
										<td style="width: 200px;">Section: Section <?= getSectionNameById($feeInfo[0]->section) ?></td>
										<td style="width: 200px;">Month: <?= $monthArray[$feeInfo[0]->month - 1]?> - <?= $feeInfo[0]->year ?></td>     
									</tr>
								</tbody>
							</table>
							<table class="table table-bordered feeTbl" style="border: 1px solid black;">            
								<tbody style="border-top: 0px;">
							   
									<tr>
										<td style="text-align: center;width: 200px;border: 1px solid black ">SL No</td>
										<td style="width: 400px; border: 1px solid black ">Particular</td>
										<td style="text-align: end;width: 200px; border: 1px solid black ">Tk</td>     
									</tr>
									<?php
										// $subHeadInfo = $wpdb->get_results("SELECT * FROM ct_sub_head
										// WHERE  relation_to = 1 and isHidden is null ORDER BY sort_order ASC");
		
										$i = 1;
										foreach($subHeadInfo as $val){
									?>
									<tr style="border: 1px solid black;">
										<td style="text-align: center;border: 1px solid black "><?= $i ?></td>
										<td style="width: 400px;border: 1px solid black "><?= $subHeadInfo[$i-1]->sub_head_name  ?></td>
										<td style="text-align: end;border: 1px solid black "><?= @$feeDetailsArray[$val->id] == null ? '0.00' : @$feeDetailsArray[$val->id]?></td>     
									</tr>
									
									<?php
									   $i++; }
									?>
									
								   
									<tr>
										<td style="text-align: center;"></td>
										<td style="width: 400px;">
											<!-- <div class="row">
												<div class="col-md-6">
													<div style="display:grid; border: 1px solid #000; width: 200px; text-align: center; margin-left: 48px;
													margin-top: 22px;">
														<span>Total Due</span>
														<span style="border-top: 1px solid #000;">0</span>
													</div>
												</div>
												<div class="col-md-6">
													<div style="display: grid;text-align: end;">
														<span>Total Fee</span>
														<span style="margin-top:5px;;">Gross Total</span>
														<span>Poor Fund</span>
														<span>Total Received</span>
													</div>
												</div>
											</div>    -->
											<table>
														<tr>
															<td style="width:200px">
																<table class="table table-bordered" style="border: 1px solid #000; width:100%;margin-left: 20px">
																	<tr><td style="border: 1px solid #000;">Total Due</td></tr>
																	<tr><td style="border: 1px solid #000;">0</td></tr>
																</table>
															</td>
															<td style="width:200px">
																
																	<span style="display: grid;text-align: end;">
																		<span>Total Fee</span>
																		<span style="margin-top:5px;;">Gross Total</span>
																		<span>Poor Fund</span>
																		<span>Total Received</span>
																	</span>  
															</td>
														</tr>
													</table>
										</td>
										<td style="text-align: end; padding-left: 0;
										padding-right: 0px;">
											<span style="padding-right: 0.5rem;"><?= $feeInfo[0]->sub_total ?></span>
											
											<div style="margin-top:5px;;border-top: 1px solid #000;">
												<span style="padding-right: 0.5rem;"><?= $feeInfo[0]->sub_total ?></span> <br>
												<span style="padding-right: 0.5rem;"><?= $feeInfo[0]->remission ?></span>
											</div>
											<div style="border-top: 1px solid #000;">
												<span style="padding-right: 0.5rem;"><?= $feeInfo[0]->total ?></span> <br>
											</div>
											
										</td>     
									</tr>
								</tbody>
							</table>
							<table class="table table-bordered" style="border: 1px solid #000;font-size:13px;">            
								<tbody style="border-top: 0px;">
									<tr>
										<td style="width: 150px; text-align: center;">In Words</td>
										<td style="width: 350px;"><?= convertNumberToWord($feeInfo[0]->total)?></td>
										 
									</tr>
								</tbody>
							</table>
							<div style="margin-top: 30px;"  class="bottom-row">
								<div class="row">
									<table>
									    <tr>
											<td style="width:200px">
											</td>
											<td style="width:200px"></td>
											<td style="width:200px">
											    <p><?= $feeInfo[0]->display_name?></p>
											</td>
										</tr>
										<tr>
											<td style="width:200px">
												<h6>Student's Signature</h6>
											</td>
											<td style="width:200px"></td>
											<td style="width:200px">
												<h6>Official's Signature</h6>
											</td>
										</tr>
									</table>
									<!-- <div class="col-md-6">
										<div class="stdnt-signature">
											<h6>Student's Signature</h6>
										</div>
									</div>
									<div class="col-md-6">
										<div class="officals-signature" style="text-align: end;">
											<h6>Official's Signature</h6>
										</div>
									</div> -->
								</div>
							</div>
		
						</td>
						<!--<td>-->
						<!--	<style>-->
						<!--		.vl2 {-->
						<!--				border-left: 1px dotted black;-->
						<!--				height: 100%;-->
						<!--				position: absolute;-->
						<!--				left: 69%;-->
										<!--/* margin-left: -3px; */-->
						<!--				top: 0;-->
						<!--			}-->
						<!--	</style>-->
						<!--	<div class="vl2"></div>-->
						<!--</td>-->
						
					</tr>
				</table>
                
                                </div>
            
        </div>
    </div>
	<?php }}?>
    </div>
  
				<?php  }?>
			


		<?php }elseif($_GET['view'] == 'activeExam'){ ?>
			<div class="panel panel-info">
			<div class="panel-heading"><h3>Active Exam</h3></div>
				
			<div class="panel-body">
			  <form action="" method="POST" class="form-inline">
				  <div class="row pl-10">

				  <div class="form-group ">
						<select class="form-control" id="examClass" name="acitveExamClass">
							<option value=''>Select Class</option>
							<?php
								$classQuery = $wpdb->get_results( "SELECT classid,className FROM ct_class WHERE classid IN (SELECT subjectClass FROM `ct_subject` GROUP BY subjectClass) ORDER BY className");
								$subCls = $tecAssignSub[$i]->subjectClass;
								foreach ($classQuery as $value) {
									$sel = ($subCls == $value->classid) ? 'selected' : '';
									echo "<option value='".$value->classid."' $sel>".$value->className."</option>";
								}
								
							?>
						</select>
					</div>

					<div class="form-group ">
						<label>Exam</label>
							<select id="active-exam" class="form-control" name="activeExamId" disabled>
							<option disabled selected value="Select Class First">Select Class First</option>
						</select>
					</div>
					
				  </div>
				  <br>
				  <div class="row pl-10">
					  
					
					<div class="form-group">
						<input class="form-control btn-success" name="activeExam" type="submit" value="Update">
					</div>
				  </div>			  		
					</form>
		  </div>







		  <?php
		if(isset($_POST['activeExam'])):
			// echo '<pre>';
			// print_r($_POST);exit;
			foreach($_POST as $key => $val){
				if(isset($_POST['activeExamId']))
				{
					$resetAllExam = $wpdb->get_results("UPDATE ct_exam SET active_for_collection = '0' WHERE  examClass = ".$_POST['acitveExamClass']);
					$qry = "UPDATE ct_exam SET active_for_collection = '1' WHERE  examid = ".$_POST['activeExamId'];
					$updateQry = $wpdb->get_results( $qry ); 
				}
				
			}
			// header("Refresh:0");
	
	endif; ?>

		<?php	}?>
		
		

	</div>


<?php if ( ! is_admin() ) {  ?>
				</div>
			</div>
		</div>
	</div>
</div>
<?php get_footer(); } ?>

<script type="text/javascript">
	(function($) {
		$catmodal = $('#addCatModal');
		
		$('#addCategoryBtn').click(function(event) {
			$catmodal.find('.modal-title').text('Add Category');
			$catmodal.find('.name').val('');
			$catmodal.find('.addCatSubmit').val('save');
			$catmodal.modal('show');
		});

		$('.editCategory').click(function(event) {
			$catmodal.find('.modal-title').text('Edit Category');
			$tr 	= $(this).closest('tr');

			$catmodal.find('.addCatSubmit').val('update');
			$catmodal.find('.name').val($tr.find('.name').text());
			$catmodal.find('.catId').val($tr.find('.catID').val());
			$catmodal.find("input[value='"+$tr.find('.type').text()+"']").click();
			$catmodal.modal('show');
		});


	})( jQuery );
	function print(divId) {
    var printContents = document.getElementById(divId).innerHTML;
    w = window.open();
    w.document.write(printContents);
    w.document.write('<scr' + 'ipt type="text/javascript">' + 'window.onload = function() { window.print();  };' + '</sc' + 'ript>');
    w.document.close(); // necessary for IE >= 10
    w.focus(); // necessary for IE >= 10
    return true;
  }
</script>
<?php 
if(isset($_SESSION['selectedClass'])){
echo "
<script type='text/javascript'>
jQuery.noConflict()(function ($) {
$(document).ready(function() {
     
		$('#resultClass').val(".@$_SESSION['selectedClass'].").change();
		console.log(2);
		setTimeout( function(){
		$('#resultSection').val(".@$_SESSION['selectedSection'].");
		console.log(3);
		$('#resultGroup').val(".@$_SESSION['selectedGroup'].");
		$('#resultYear').val(".@$_SESSION['selectedYear'].");
		$('#fee-month').val(".@$_SESSION['selectedMonth'].");
	},3000
);
});
});
</script>";
}
?>
<script type="text/javascript">
  (function($) {
    $('#resultClass').change(function() {
      var $siteUrl = '<?= get_template_directory_uri() ?>';

      $.ajax({
        url: $siteUrl+"/inc/ajaxAction.php",
        method: "POST",
        data: { class : $(this).val(), type : 'getYears' },
        dataType: "html"
      }).done(function( msg ) {
        $( "#resultYear" ).html( msg );
        $( "#addFeeYear" ).html( msg );
        $( "#resultYear" ).prop('disabled', false);
        $( "#addFeeYear" ).prop('disabled', false);
      });

      $.ajax({
        url: $siteUrl+"/inc/ajaxAction.php",
        method: "POST",
        data: { class : $(this).val(), type : 'getSection' },
        dataType: "html"
      }).done(function( msg ) {
        $( "#resultSection" ).html( msg );
        $( "#resultSection" ).prop('disabled', false);
      });
    });

var selectedActiveExam;
    $('#examClass').change(function() {
      var $siteUrl = '<?= get_template_directory_uri() ?>';

      $.ajax({
        url: $siteUrl+"/inc/ajaxAction.php",
        method: "POST",
        data: { class : $(this).val(), type : 'getExams' },
        dataType: "html"
      }).done(function( msg ) {
        $( "#active-exam" ).html( msg );
        $( "#active-exam" ).prop('disabled', false);
		$("#active-exam").val(selectedActiveExam);
      });
	  
	  $.ajax({
				url: $siteUrl+"/inc/ajaxAction.php",
				method: "POST",
				data: { class : $(this).val(), type : 'getActiveExam' },
				dataType: "html"
			}).done(function( id ) {
				// console.log(id)
				// if(id == 'false'){
					selectedActiveExam = id;
				// }else{
					$("#active-exam").val(id);
				// }
				
			});

    });

    $('#resultRoll').focusout(function() {
      var $siteUrl = '<?= get_template_directory_uri() ?>';

	  $.ajax({
			url: $siteUrl+"/inc/ajaxAction.php",
			method: "POST",
			data: { class : $('#resultClass').val(), section : $('#resultSection').val(), group : $('#resultGroup').val(), year : $('#resultYear').val(), roll : $('#resultRoll').val(), month: $('#fee-month').val(), admissionFeeSubHeadId:$('#admissionFeeSubHeadId').val(), admissionFormSubHeadId:$('#admissionFormSubHeadId').val(), monthlyFeeSubHeadId:$('#monthlyFeeSubHeadId').val(), registrationFeeSubHeadId:$('#registrationFeeSubHeadId').val(), coachingFeeSubHeadId:$('#coachingFeeSubHeadId').val(), transportFeeSubHeadId:$('#transportFeeSubHeadId').val(), type : 'getStudentInfo' },
			dataType: "json"
		}).success(function( data ) {
			// console.log( data)
			// console.log( typeof data)
			if(data.success == 'false'){
				$( "#resultName" ).val( '' );
			}else{	
				Object.entries(data).forEach(([key, val]) => {
					if(key == 'month_list'){
						$("#month_list").val( val );
					}else{
						$("#"+key ).val( val );
					}
					
				});
				// data = JSON.parse(data);			
				getTotal();
				
			}
			
		});

    });

    $('#addFeeYear').change(function() {
      var $siteUrl = '<?= get_template_directory_uri() ?>';

	  $.ajax({
			url: $siteUrl+"/inc/ajaxAction.php",
			method: "POST",
			data: { class : $('#resultClass').val(), group : $('#resultGroup').val(), year : $('#addFeeYear').val(), type : 'getStudentFeeAmount' },
			dataType: "json"
		}).success(function( data ) {
			console.log( data)
			// console.log( typeof data)
			if(data.success == 'false'){
				$( "#resultName" ).val( '' );
			}else{	
				Object.entries(data).forEach(([key, val]) => {
					// if(key == 'month_list'){
					// 	$("#month_list").val( val );
					// }else{
						$("#"+key ).val( val );
					// }
					
				});
				// data = JSON.parse(data);			
				// getTotal();
				
			}
			
		});

    });

	$('.remission_category').change(function() {
		calculateRemission();
		getTotal();
	});
	$('.active_category').change(function() {
		getTotal();
	});

  })( jQuery );


  /*=====================Excel Export*/

  function fnExcelReport(){
    var tab_text="<table border='2px'><tr bgcolor='#87AFC6'>";
    var textRange; var j=0;
    tab = document.getElementById('studentsTbl'); // id of table

    for(j = 0 ; j < tab.rows.length ; j++){     
      tab_text=tab_text+tab.rows[j].innerHTML+"</tr>";
    }

    tab_text=tab_text+"</table>";
    tab_text= tab_text.replace(/<A[^>]*>|<\/A>/g, "");
    
    tab_text= tab_text.replace(/<input[^>]*>|<\/input>/gi, ""); 

    var ua = window.navigator.userAgent;
    var msie = ua.indexOf("MSIE "); 

    if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./)){
      txtArea1.document.open("txt/html","replace");
      txtArea1.document.write(tab_text);
      txtArea1.document.close();
      txtArea1.focus(); 
      sa=txtArea1.document.execCommand("SaveAs",true,"students.xls");
    }  
    else                 //other browser not tested on IE 11
      sa = window.open('data:application/vnd.ms-excel,' + encodeURIComponent(tab_text));  

    return (sa);
  }


  /*=====================PDF Export*/

  function exportPDF() {
    var doc = new jsPDF('l', 'pt', 'a4');
    doc.autoTable({
      html: '#studentsTbl',
      theme: 'grid',
      styles: {fontSize: 8}
    });
    doc.save('students.pdf');
  }


</script>
<script type="text/javascript">
	function calculateRemission(){
		var activeArr = [];
		var all_active_id = document.querySelectorAll('input[name="active_category[]"]:checked');

		for(var x = 0, l = all_active_id.length; x < l;  x++)
		{
			activeArr.push( all_active_id[x].value);
		}

		var all_remission_id = document.querySelectorAll('input[name="remission_category[]"]:checked');
		var remisstionTotal = 0;
		for(var x = 0, l = all_remission_id.length; x < l;  x++)
		{	if(activeArr.includes(all_remission_id[x].value)){
				var subheadid = 'subheadid'+all_remission_id[x].value;
				remisstionTotal += Number(document.getElementById(subheadid).value) || 0;
			}						
		}
		document.getElementById('remission').value = remisstionTotal;
		// getTotal();
	}

	function getTotal(){
		// calculateRemission();
		var activeArr = [];
		var total = 0, lateFee = 0, absentFee = 0;
		var all_active_id = document.querySelectorAll('input[name="active_category[]"]:checked');

		for(var x = 0, l = all_active_id.length; x < l;  x++)
		{
			total += parseInt(document.getElementById('subheadid'+all_active_id[x].value).value) || parseInt(0);
			// activeArr.push( all_active_id[x].value);
		}
		// for(var i=0;i<activeArr.length;i++){
		// 	if(parseInt(allArr[i].value))
		// 	total += parseInt(allArr[i].value);
		// }


		// var allArr = document.getElementsByClassName('calculate');
		// var activeArr = document.getElementsByClassName('active_category');
// console.log(allArr[0].value)
// console.log(activeArr)
		
		// latefee = document.getElementById('subheadid'+lateSubHeadId).value || 0;
		// absentfee = document.getElementById('subheadid'+absentSubHeadId).value || 0;
		remissionfee = Number(document.getElementById('remission').value) || 0;

		// for(var i=0;i<allArr.length;i++){
		// 	if(parseInt(allArr[i].value))
		// 	total += parseInt(allArr[i].value);
		// }
		// subTotal = parseInt(latefee) + parseInt(absentfee) + total;
		subTotal =  total;
		grandtotal = subTotal - parseInt(remissionfee);

		document.getElementById('sub-total').value = subTotal;
		document.getElementById('grand-total').value = grandtotal;
	}

</script>