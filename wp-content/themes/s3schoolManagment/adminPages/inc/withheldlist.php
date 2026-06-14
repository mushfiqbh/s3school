<?php
 
 global $wpdb; global $s3sRedux; 


if (isset($_POST['stuWithheld'])) {
    // echo '<pre>';
    // print_r($_POST);exit;

$allList = isset($_POST['stdidswithheld']) ? $_POST['stdidswithheld'] : [];


// echo '<pre>';
//     print_r($allList);exit;



// add condition for year exam class to avoid update wrong info


$resExam = isset($_POST['resExam']) ? $_POST['resExam'] : null;
$resultYear = isset($_POST['resultYear']) ? $_POST['resultYear'] : null;
$resclass = isset($_POST['resclass']) ? $_POST['resclass'] : null;
$ressec = isset($_POST['ressec']) ? $_POST['ressec'] : null;
$resgroup = isset($_POST['resgroup']) ? $_POST['resgroup'] : null;



// Ensure it's an array
if (is_array($allList) && !empty($allList)) {
foreach ($allList as $id) {
		$update1 = $wpdb->update(
		'ct_result',
			array(
				'withheld' 		=> 0
			),
			array( 'resStudentId' => $id,'resultYear' =>$resultYear,'resExam'=>$resExam)
		);
	}
}
// echo '<pre>';
// print_r($_POST['withheld']);exit;
if(isset($_POST['withheld'])){
		foreach ($_POST['withheld'] as $stdid) {
	    		$update = $wpdb->update(
		'ct_result',
			array(
				'withheld' 		=> 1
			),
			array( 'resStudentId' => $stdid,'resultYear' =>$resultYear,'resExam'=>$resExam)
		);
	    }
}
	    if (isset($update)) {
		$message = array('status' => 'success', 'message' => 'Successfully Updated.' );
	}else{
		$message = array('status' => 'faild', 'message' => 'Make sure you fill correct input.' );
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
<?php

							if (isset($message)) {
								?>
									<div class="messageDiv">
										<div class="alert <?= ($message['status'] == 'success') ? 'alert-success' : 'alert-danger';  ?>">
											<?= $message['message'] ?>
										</div>
									</div>
								<?php
							}
						?>

							<div class="panel panel-info">
	<div class="panel-heading"><h3>Withheld Student</h3></div>
	<div class="panel-body">
		<form action="" method="GET" class="form-inline">

			<div class="form-group">
				<input type="hidden" name="page" value="result">
				<input type="hidden" name="view" value="withheld">
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

			<div class="form-group ">
				<label>Exam</label>
				<select id="resultExam" class="form-control" name="exam" required disabled>
					<option disabled selected>Select Class First</option>
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
					<select id="resultGroup" class="form-control" name="grou">
						<option value="">Select Group</option>
						<?php
            	            $groups = $wpdb->get_results("SELECT * FROM ct_group");
            	            foreach ($groups as $groups) {
            	              ?>
            	              <option value='<?= $groups->groupId ?>' >
            	                <?= $groups->groupName ?>
            	              </option>
            	              <?php
            	            }
            	          ?>
					</select>
				</div>

			<div class="form-group">
				<label>Year/Session</label>
				<select id='resultYear' class="form-control" name="syear" required disabled>
					<option disabled selected>Select Class First</option>
				</select>
			</div>


			

			<div class="form-group">
				<input class="form-control btn-success" type="submit" name="" value="Go">
			</div>
		</form>
	</div>

</div>

<?php
if(isset($_GET['exam'])):
	$exam 	= $_GET['exam']; 
	$year 	= $_GET['syear']; 
	$class 	= $_GET['class'];
	
	$sec 		= isset($_GET['sec']) ? $_GET['sec'] : '';
	$grou 	= $_GET['grou'];

	

	$results = "SELECT examName,className";
	if($sec!= ''){$results .= " ,sectionName";}
	    $results .= " FROM ct_exam";
	    $results .= " LEFT JOIN ct_class ON ct_exam.examClass = ct_class.classid";
		if($sec!= ''){$results .= " LEFT JOIN ct_section ON ct_section.sectionid = $sec";}
		$results .= " where examid = $exam";
		$info = $wpdb->get_results($results);

	?>

	<div class="panel panel-info">
		<div class="panel-heading"><h3>Withheld Students</h3></div>
		<div class="panel-body">
				<div class="text-right">
					<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>
				</div>
				<form action="" method="POST">
			
					<div class="form-group">
						<input type="hidden" name="resExam" value='<?= $exam; ?>'>
						<input type="hidden" name="resultYear" value='<?= $year; ?>'>
						<input type="hidden" name="resclass" value='<?= $class; ?>'>
						<input type="hidden" name="ressec" value='<?= $sec; ?>'>
						<input type="hidden" name="resgroup" value='<?= $grou; ?>'>

					
						<div id="printArea">
							<style type="text/css"> @page{ size: auto;  margin: 0px; } </style>
							<link rel="stylesheet" media="print" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" />
						  <div class="printArea" style="margin: 20px;">
								<h3>
									<b>Class:</b> <?= $info[0]->className ?>,
									<b>Exam:</b> <?= $info[0]->examName ?>,
									<b>Year:</b> <?= $_GET['syear'] ?>
								</h3>

								<div class="table-responsive">
									<table id="resultInputTable" class="table table-bordered ">
										<tr>
											<th>Name</th>
											<th>Roll</th>
											<th>Group</th>
											<th>Sec</th>
											<th><label class="labelRadio">Select <input id="selectAll" type="checkbox"></label></th>
										</tr>
									

										<?php
												$stdQuery = "
                                                SELECT 
                                                    studentid, infoRoll,sectionName,groupName, stdName,withheld, infoGroup, infoSection 
                                                FROM 
                                                    ct_student 
                                                LEFT JOIN 
                                                    ct_studentinfo 
                                                    ON ct_student.studentid = ct_studentinfo.infoStdid 
                                                    AND ct_studentinfo.infoClass = %d 
                                                    AND ct_studentinfo.infoYear = %s 
                                                LEFT JOIN 
                                                    ct_section 
                                                    ON ct_studentinfo.infoSection = ct_section.sectionid 
                                                    AND stdCurntYear = %s 
                                                    AND stdCurrentClass = %d
                                                    LEFT JOIN 
                                                    ct_group 
                                                    ON ct_studentinfo.infoGroup = ct_group.groupid 
                                                    LEFT JOIN 
                                                    ct_result 
                                                    ON ct_student.studentid = ct_result.resStudentId 
                                                WHERE 1=1
                                            ";
                                            
                                            // Dynamically add conditions based on `$sec` and `$grou`
                                            if ($sec != "") {
                                                $stdQuery .= " AND infoSection = %d";
                                            }
                                            if ($grou != "") {
                                                $stdQuery .= " AND infoGroup = %d";
                                            }
                                            
                                            // Add the ORDER BY clause
                                            $stdQuery .= " GROUP BY ct_student.studentid  ORDER BY infoRoll ASC";
                                            
                                            // Prepare and execute the query
                                            $queryParams = [$class, $year, $year, $class];
                                            if ($sec != "") {
                                                $queryParams[] = $sec;
                                            }
                                            if ($grou != "") {
                                                $queryParams[] = $grou;
                                            }
                                            
                                            $stdQuery = $wpdb->prepare($stdQuery, ...$queryParams);
                                            $stdQuery = $wpdb->get_results($stdQuery);

							
											foreach ($stdQuery as $student) {
								// 			echo "<pre>";print_r($student);exit;
												?>
												<input type="hidden" name="stdidswithheld[]" value='<?= $student->studentid ?>'>
												

												<tr>
													<td><?= $student->stdName ?></td>
													<td><?= $student->infoRoll ?></td>
													<td><?= $student->groupName ?></td>
													<td><?= $student->sectionName ?></td>
													<td>
									
										<label class="labelRadio">
											<input class="stdSel" type="checkbox" name="withheld[]" value="<?= $student->studentid ?>" <?= $student->withheld == 1? 'checked':''; ?>> Select
										</label>
									</td>
												</tr>
												<?php
											}
										?>
									</table>
									
									
									<?php if(!$stdQuery){ ?>
										<h3 class="text-center text-info">No Student Found for add the result</h3>
									<?php } ?>
								</div>
							</div>
						</div>
					</div>

					<?php if($stdQuery){ ?>
						<div class="form-group">
							<input name="stuWithheld" class="form-control btn-success" type="submit" value="Update">
						</div>
					<?php } ?>
				</form>
			
		</div>
	</div>

	<?php 
endif; ?>


<script type="text/javascript">
	function print(divId) {
    var printContents = document.getElementById(divId).innerHTML;
    w = window.open();
    w.document.write(printContents);
    w.document.write('<scr' + 'ipt type="text/javascript">' + 'window.onload = function() { window.print();  };' + '</sc' + 'ript>');
    w.document.close();
    w.focus();
    return true;
  }

</script>