<?php
/*
** Template Name: Admin Coaching
*/ 
 global $wpdb; global $s3sRedux;
 global $registrationFeeSubHeadId;
 global $coachingFeeSubHeadId;
 
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

if (isset($_POST['StuFee'])) {

	if ($_POST['studentwisefee'] < 1) {
		$message = "Select minimum 1 student";
	}else{

		


		foreach ($_POST['studentwisefee'] as $stdid) {

	    

	    $amount = $_POST['feeamount'][$stdid];
		$fee_type = $_POST['feeType'][$stdid];
		$student_id = $stdid;
		$student_roll =  $_POST['roll'][$stdid];
		$year =  $_POST['year'][$stdid];
		$class_id = $_POST['class'][$stdid];
		$section = $_POST['section'][$stdid];
		$group_id = $_POST['infoGroup'][$stdid];
		

		$check = $wpdb->get_results( "SELECT amount FROM ct_student_wise_fee WHERE class_id = $class_id and fee_type = $fee_type and student_id = $stdid and student_roll = $student_roll and year =  $year" );

		if($fee_type == 3){
			$transport_fee_id = $_POST['transport_fee_id'][$stdid];
			$transport_required = $_POST['transport_required'][$stdid];
			$transport_type = $_POST['transport_type'][$stdid];
			if($check){
				$insert = $wpdb->update(
					'ct_student_wise_fee',
					array( 'section' => $section,
					'group_id' => $group_id,
					'transport_fee_id' => $transport_fee_id,
					'transport_required' => $transport_required,
					'transport_type' => $transport_type),
					array( 'student_id' => $stdid, 'class_id' => $class_id,'year' =>  $year)
				);
	
			}else{
				$insert = $wpdb->insert('ct_student_wise_fee', array(
					'fee_type' => $_POST['feeType'][$stdid],
					'student_id' => $stdid,
					'student_roll' =>  $_POST['roll'][$stdid],
					'year' =>  $_POST['year'][$stdid],
					'class_id' => $_POST['class'][$stdid],
					'section' => $_POST['section'][$stdid],
					'group_id' => $_POST['infoGroup'][$stdid],
					'transport_fee_id' => $transport_fee_id,
					'transport_required' => $transport_required,
					'transport_type' => $transport_type,
					'status' => 1,
					'notes' => 'student wise fee'
				  ));
			}
		}else{
			if($check){
				$insert = $wpdb->update(
					'ct_student_wise_fee',
					array( 'section' => $section,
					'group_id' => $group_id,
					'amount' => $amount),
					array( 'student_id' => $stdid, 'class_id' => $class_id,'year' =>  $year)
				);
	
			}else{
				$insert = $wpdb->insert('ct_student_wise_fee', array(
					'fee_type' => $_POST['feeType'][$stdid],
					'student_id' => $stdid,
					'student_roll' =>  $_POST['roll'][$stdid],
					'year' =>  $_POST['year'][$stdid],
					'class_id' => $_POST['class'][$stdid],
					'section' => $_POST['section'][$stdid],
					'group_id' => $_POST['infoGroup'][$stdid],
					'amount' => $amount,
					'status' => 1,
					'notes' => 'student wise fee'
				  ));
			}
		}
		
    	
	    $message = ms3message($insert, 'Updated');
		    
	    
    	
		}
	}
}

?>

<?php if ( ! is_admin() ) { get_header(); ?>
<div class="b-layer-main">

	<div class="">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
<?php } ?>

<div class="container-fluid maxAdminpages" style="padding-left: 0">

	<div class="row">
		<!-- Show Status message -->
  	<?php if(isset($message)){ ms3showMessage($message); } ?>

		<div class="col-md-12">
			<div class="panel panel-info">
			  <div class="panel-heading"><h3>Student Wise Fee Management<br></h3></div>
			  <div class="panel-body">

					<form class="form-inline" action="" method="GET">
						<input type="hidden" name="page" value="studentCoachingFee">

						<div class="form-group">
							<label>Fee Type</label>
							<select id='feeType' class="form-control" name="feeType" required>
								<?php

									$feeType = $wpdb->get_results( "SELECT * FROM ct_student_fee_type" );
									echo "<option value=''>Select Fee Type</option>";

									foreach ($feeType as $val) {
										echo "<option value='".$val->id."'>".$val->fee_type."</option>";
									}
								?>
							</select>
						</div>

						<div class="form-group">
							<label>Class</label>
							<select id='resultClass' class="form-control" name="class" required>
								<?php

									$classQuery = $wpdb->get_results( "SELECT classid,className FROM ct_class WHERE classid IN (SELECT examClass FROM ct_exam GROUP BY examClass ORDER BY className ASC)" );
									echo "<option value=''>Select Class</option>";

									foreach ($classQuery as $class) {
										echo "<option value='".$class->classid."'>".$class->className."</option>";
									}
								?>
							</select>
						</div>

						<!-- <div class="form-group ">
							<label>Exam</label>
							<select id="resultExam" class="form-control" name="exam" required disabled>
								<option disabled selected>Select Class First</option>
							</select>
						</div> -->

						<div class="form-group ">
							<label>Section</label>
							<select id="resultSection" class="form-control" name="section" required disabled>
								<option disabled selected>Select Class First</option>
							</select>
						</div>

						<div class="form-group">
							<label>Year</label>
							<select id='resultYear' class="form-control" name="syear" required disabled>
								<option disabled selected>Select Class First</option>
							</select>
						</div>

						<div class="form-group" id="idRoll">
							<input class="form-control" type="text" name="roll" placeholder="Roll" style="width: 110px">
						</div>
						<div class="form-group">
							<input type="submit" name="creatId" value="Genarate List" class="btn btn-primary" style="margin:10px">
						</div>
					</form>
			  </div>
			</div>
		</div>

		<?php if(isset($_GET['syear'])){ ?>

		  <div id="printArea" class="col-md-12 printBG">

			  <div >

			  	<?php
			  		$year 		= $_GET['syear'];
			  		$class 		= $_GET['class'];
			  		$feeType 		= $_GET['feeType'];
			  		// $exam 		= $_GET['exam'];
			  		$section 	= isset($_GET['section']) ? $_GET['section'] : '';
			  		$roll 		= isset($_GET['roll']) ? $_GET['roll'] : '';

			  		if (isset($_GET['syear'])) {
						  if($feeType == 1){
							$feeAmount = getFeeAmount($coachingFeeSubHeadId, $class, $year);
						  }elseif($feeType == 2){
							$feeAmount = getFeeAmount($registrationFeeSubHeadId, $class, $year);
						  }else{
							$feeAmount = 0;
						  }
						$transportFeeInfo = $wpdb->get_results("SELECT * FROM ct_transport_fee_list");

			  			$querry = "SELECT stdName,ct_student_wise_fee.transport_required,ct_student_wise_fee.transport_type,ct_student_wise_fee.transport_fee_id, infoRoll,className,sectionName,groupName,studentid,infoGroup,amount,status FROM `ct_student`
  								LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid AND $class = ct_studentinfo.infoClass AND '$year' = ct_studentinfo.infoYear
  								LEFT JOIN ct_student_wise_fee ON ct_student.studentid = ct_student_wise_fee.student_id AND $class = ct_student_wise_fee.class_id AND '$year' = ct_student_wise_fee.year  AND $feeType = ct_student_wise_fee.fee_type
									LEFT JOIN ct_class ON ct_studentinfo.infoClass = ct_class.classid
									LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId
									LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
								WHERE infoYear = '$year' AND infoClass = $class AND stdCurrentClass = $class AND stdCurntYear = '$year'";

							$querry .= ($roll != "") ? " AND infoRoll = $roll" : '';
							$querry .= ($section != "") ? " AND infoSection = $section" : '';
							$querry .= "  Group By studentid ORDER BY infoRoll";
							$groupsBy = $wpdb->get_results($querry);
			  		}
			  		

			  		if($groupsBy){
			  			?>
			  			<form action="" method="post" class="form-inline">
			  				
								
									
			  					<input class="btn btn-success btn-lg pull-right" type="submit" name="StuFee"  style="margin:10px" value="Save">
			  				
			  				<br>
			  				<table class="table table-responsive table-striped table-bordered">
			  					<tr>
			  						<th>#</th>
			  						<th>Name</th>
			  						<th>Roll</th>
			  						<th>Class</th>
			  						<th>Section</th>
			  						<th>Group</th>
									  <?php
										if($feeType == 3){
									  ?>
									 
									  	<th>Transport Requried?</th>
									  	<th>Transport Type</th>
									  	<th>Transport Fee</th>
									  <?php
										}else{
									  ?>
			  							<th>Fee Amount</th>
									  <?php
										}
									  ?>
			  						<th><label class="labelRadio">Select <input id="selectAll" type="checkbox"></label></th>
			  					</tr>
			  					<?php
			  					
			  					foreach ($groupsBy as $key => $value) {
									?>
									

										<tr>
					  					<td><?= $key+1 ?></td>
					  					<td><?= $value->stdName ?></td>
					  					<td><?= $value->infoRoll ?></td>
					  					<td><?= $value->className ?></td>
					  					<td><?= $value->sectionName ?></td>
					  					<td><?= $value->groupName ?></td>
										<?php
											if($feeType == 3){
										?>
									  	<td>    
											<label class="labelRadio">
												<input type="radio" name="transport_required[<?= $value->studentid ?>]" value="1" <?= $value->transport_required == 1 ? 'checked' : '' ?>> Yes
											</label>
											<label class="labelRadio">
												<input type="radio" name="transport_required[<?= $value->studentid ?>]" value="0" <?= $value->transport_required == '' || $value->transport_required == 2 ? 'checked' : '' ?>> No
											</label>
										</td>
									  	<td>
										  <label class="labelRadio">
											<input type="radio" name="transport_type[<?= $value->studentid ?>]" value="1" <?= $value->transport_type == 1 ? 'checked' : '' ?>> One Way
											</label>
											<label class="labelRadio">
											<input type="radio" name="transport_type[<?= $value->studentid ?>]" value="2" <?= $value->transport_type == '' || $value->transport_type == 2 ? 'checked' : '' ?>> Two Way
											</label>  
										</td>
									  <?php
										}
									  ?>
					  					<td>
					  						<input type="hidden" name="infoGroup[<?= $value->studentid ?>]" value='<?= $value->infoGroup; ?>'>
					  						<input type="hidden" name="year[<?= $value->studentid ?>]" value='<?= $year; ?>'>
					  						<input type="hidden" name="class[<?= $value->studentid ?>]" value='<?= $class; ?>'>
					  						<input type="hidden" name="feeType[<?= $value->studentid ?>]" value='<?= $feeType; ?>'>
					  						<input type="hidden" name="roll[<?= $value->studentid ?>]" value='<?= $value->infoRoll; ?>'>
					  						<input type="hidden" name="section[<?= $value->studentid ?>]" value='<?= $section; ?>'>
											<input type="hidden" name="feeamount[<?= $value->studentid ?>]" value="<?= $value->amount ?>">
											<?php
												if($feeType == 3){
											?>
											<select class="form-control" name="transport_fee_id[<?= $value->studentid ?>]">
												<?php foreach( $transportFeeInfo as $val){?>
												<option value="<?= $val->id?>" <?= $value->transport_fee_id == $val->id? "selected": '' ?>><?= $val->fee_name?> (<?= $val->distance?>) (<?= $val->amount?>Tk)</option>

												<?php }?>
											</select>
											<?php
												}else{
											?>
												<input class="assignRoll" type="number" name="feeamount[<?= $value->studentid ?>]" data-amount="<?= $value->amount ?>" value="<?= $value->amount > 0 ? $value->amount: $feeAmount?>">

											<?php
												}
											?>
					  					</td>
					  					<td>
					  						<label class="labelRadio">
					  							<input class="stdSel" type="checkbox" name="studentwisefee[]" value="<?= $value->studentid ?>" <?= $value->status == 1 ? 'checked' : '' ?>> Select
					  						</label>
					  					</td>
					  				</tr>
									<?php
									}
									?>
								</table>
							</form>
							<?php

						}else{
							echo "<h3 class='text-center'>Result / Student not Found</h3>";
						}

			  	?>

			  </div>
		  </div>

		<?php } ?>
	</div>
</div>


<?php if ( ! is_admin() ) { ?>
				</div>
			</div>
		</div>
	</div>
</div>
<?php get_footer(); } ?>

<p id="theSiteURL" class="hidden"><?= get_template_directory_uri() ?></p>
<script type="text/javascript">
	(function($) {
		// $('#addAdditional').change(function(event) {
		// 	$value = +$(this).val();
		// 	$( ".assignRoll" ).each(function( index ) {
		// 	  $(this).val(+$(this).data('amount')+$value);
		// 	});
		// });

	  var $siteUrl = $('#theSiteURL').text();

		$('#proClass').change(function() {
			$.ajax({
	      url: $siteUrl+"/inc/ajaxAction.php",
	      method: "POST",
	      data: { class : $(this).val(), type : 'getSection' },
	      dataType: "html"
	    }).done(function( msg ) {
	      $( "#proSec" ).html( msg );
	      $( "#proSec" ).prop('disabled', false);
	    });
		});


		$('#resultClass').change(function() {
	    $.ajax({
	      url: $siteUrl+"/inc/ajaxAction.php",
	      method: "POST",
	      data: { class : $(this).val(), type : 'getExams' },
	      dataType: "html"
	    }).done(function( msg ) {
	      $( "#resultExam" ).html( msg );
	      $( "#resultExam" ).prop('disabled', false);
	    });

	    $.ajax({
	      url: $siteUrl+"/inc/ajaxAction.php",
	      method: "POST",
	      data: { class : $(this).val(), type : 'getYears' },
	      dataType: "html"
	    }).done(function( msg ) {
	      $( "#resultYear" ).html( msg );
	      $( "#resultYear" ).prop('disabled', false);
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
	})( jQuery );
	
	function print(divId) {
    var printContents = document.getElementById(divId).innerHTML;
    w = window.open();
    w.document.write(printContents);
    w.document.write('<scr' + 'ipt type="text/javascript">' + 'window.onload = function() { window.print(); window.close(); };' + '</sc' + 'ript>');
    w.document.close(); // necessary for IE >= 10
    w.focus(); // necessary for IE >= 10
    return true;
  }
</script>