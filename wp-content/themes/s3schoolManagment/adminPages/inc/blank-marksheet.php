<?php global $s3sRedux; ?>
<div class="panel panel-info">
	<div class="panel-heading"><h3>Blank Mark Sheet</h3></div>
	<div class="panel-body">
		<form action="" method="GET" class="form-inline">

			<div class="form-group">
				<input type="hidden" name="page" value="result">
				<input type="hidden" name="view" value="marksheet">
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

			<div class="form-group">
				<label>Year/Session</label>
				<select id='resultYear' class="form-control" name="syear" required disabled>
					<option disabled selected>Select Class First</option>
				</select>
			</div>


			<div class="form-group">
				<label>Subject</label>
				<select id='resultSubject' class="form-control" name="subject" required disabled>
					<option disabled selected>Select exam First</option>
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
	$sub 		= $_GET['subject'];
	$sec 		= isset($_GET['sec']) ? $_GET['sec'] : '';
   
	$results = "SELECT examName,className,subjectName,connecttedPaper,subPaper,subOptinal,sub4th,subMCQ,subCQ,subPect,subCa";
	if($sec!= ''){$results .= " ,sectionName";}
	    $results .= " FROM ct_subject";
	    $results .= " LEFT JOIN ct_exam ON examid = $exam
		LEFT JOIN ct_class ON ct_exam.examClass = ct_class.classid";
		if($sec!= ''){$results .= " LEFT JOIN ct_section ON ct_section.sectionid = $sec";}
		$results .= " WHERE subjectid = $sub" ;
		$info = $wpdb->get_results($results);
 if($info){
	$resCombineWith = $info[0]->connecttedPaper;
	$resSubPaper		= $info[0]->subPaper;
	$subOpt 				= $info[0]->subOptinal;
	$sub4th 				= $info[0]->sub4th;

	$subMCQ 			= $info[0]->subMCQ;
	$subCQ 				= $info[0]->subCQ;
	$subPect 			= $info[0]->subPect;
	$subCa 			= $info[0]->subCa;

	?>

	<div class="panel panel-info">
		<div class="panel-heading"><h3>Result <button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button> </h3></div>
		<div class="panel-body">
			<form action="" method="POST">
		
				<div class="form-group">

					<div id="printArea">
					  <div class="printArea" style="margin: 0;">
							<style type="text/css">table tr{ page-break-inside: avoid !important; } table tr a{ text-decoration: none;color: #000; } @page { size: 210mm 297mm !important; margin: 10px 0 !important; }</style>
							<link rel="stylesheet" href="<?= get_template_directory_uri() ?>/css/tabulationSheet.css" />
							
							<div style="text-align: center; position: relative;">
				  			<img height="80px" style="position: absolute;left: 10px;top: 10px" src="<?= $s3sRedux['instLogo']['url'] ?>">
		  					<h2 style="margin: 5px 0 5px 0;"><b><?= $s3sRedux['institute_name'] ?></b></h2>
					  		<p style="color:#2b5591; font-size: 14px; margin: 0;"><?= $s3sRedux['institute_address'] ?></p>
					  		<h3>Blank Mark Sheet</h3>
				  		</div>

							<table class="table table-bordered" style="width: 100%">
				  			<tr style="background: #4472C4;-webkit-print-color-adjust: exact;color: #fff">
				  				<td colspan="3"><b>Exam :</b> <?= $info[0]->examName ?></td>
				  				<td colspan="3"><b>Subject Name :</b> <?= $info[0]->subjectName ?></td>
				  			</tr>
				  			<tr style="background: #D9E2F3;-webkit-print-color-adjust: exact;">
				  				<td colspan="2"><b>Year/Session :</b> <?= $_GET['syear'] ?></td>
				  				<td colspan="2"><b>Class :</b> <?= $info[0]->className ?></td>
				  				<td colspan="2"><b>Section :</b> <?= @$info[0]->sectionName ?></td>
				  			</tr>
				  		</table>

							<div class="table-responsive">
								<table id="resultInputTable" class="table table-bordered" style="width: 100%">
									<tr>
										<th style="text-align: center;">Roll</th>
										<th>Name</th>
										<th style="text-align: center;<?= ($subCQ == 0) ? 'display:none' : ''; ?>"><?= $s3sRedux['cqtitle'] ?> (<?= $subCQ ?>)</th>
										<th style="text-align: center;<?= ($subMCQ == 0) ? 'display:none' : ''; ?>"><?= $s3sRedux['mcqtitle'] ?> (<?= $subMCQ ?>)</th>
										<th style="text-align: center;<?= ($subPect == 0) ? 'display:none' : ''; ?>"><?= $s3sRedux['prctitle'] ?> (<?= $subPect ?>)</th>
										<th style="text-align: center;<?= ($subCa == 0) ? 'display:none' : ''; ?>"><?= $s3sRedux['catitle'] ?> (<?= $subCa ?>)</th>
									</tr>
								

									<?php
										if($subOpt == 0 && $sub4th == 0){
											$stdQuery = "SELECT studentid,infoRoll,stdName,groupName,infoGroup,infoSection,infoOptionals,info4thSub,sectionName FROM ct_student
												LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid
																								AND ct_studentinfo.infoClass = $class AND ct_studentinfo.infoYear = '$year' 
												LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId 
												LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid 
												WHERE studentid NOT IN
													(SELECT resStudentId FROM `ct_result` WHERE resClass = $class AND resultYear = '$year' AND resSubject = $sub AND resExam = $exam)
													AND stdCurntYear = '$year' AND stdCurrentClass = $class";

											if ($sec != "") {
												$stdQuery .= " AND infoSection = $sec";
											}

											if($class == 41){
											 	$stdQuery .= " ORDER BY groupName DESC, infoRoll ASC";
											}else{
											$stdQuery .= " ORDER BY infoRoll ASC";
											}
										}else{
											$stdQuery = "SELECT studentid,infoRoll,stdName,groupName,infoGroup,infoSection,infoOptionals,info4thSub,sectionName FROM ct_student
												LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid
																								AND ct_studentinfo.infoClass = $class AND ct_studentinfo.infoYear = '$year' 
												LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId 
												LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid 
												WHERE studentid NOT IN
													(SELECT resStudentId FROM `ct_result` WHERE resClass = $class AND resultYear = '$year' AND resSubject = $sub AND resExam = $exam)
													AND stdCurntYear = '$year' AND stdCurrentClass = $class";
											if ($subOpt == 1 && $sub4th == 1) {
												$stdQuery .= " AND (infoOptionals LIKE '%\"$sub\"%' OR info4thSub = $sub)";
											}
											if ($subOpt == 1 && $sub4th == 0) {
												$stdQuery .= " AND infoOptionals LIKE '%\"$sub\"%' ";
											}
											if ($subOpt == 0 && $sub4th == 1) {
												$stdQuery .= " AND info4thSub = $sub ";
											}
											if ($sec != "") {
												$stdQuery .= " AND infoSection = $sec";
											}
											if($class == 41){
											 	$stdQuery .= " ORDER BY groupName DESC, infoRoll ASC";
											}else{
											$stdQuery .= " ORDER BY infoRoll ASC";
											}
										}

										$stdQuery = $wpdb->get_results( $stdQuery );
						
										foreach ($stdQuery as $student) { ?>
											
											<tr>
												<td style="text-align: center;"><?= $student->infoRoll ?></td>
												<td><?= $student->stdName ?></td>
												<td style="text-align: center;<?= ($subCQ == 0) ? 'display:none' : ''; ?>"><?= ($subCQ == 0) ? 'x' : ''; ?></td>
												<td style="text-align: center;<?= ($subMCQ == 0) ? 'display:none' : ''; ?>"><?= ($subMCQ == 0) ? 'x' : ''; ?></td>
												<td style="text-align: center;<?= ($subPect == 0) ? 'display:none' : ''; ?>"><?= ($subPect == 0) ? 'x' : ''; ?></td>
												<td style="text-align: center;<?= ($subCa == 0) ? 'display:none' : ''; ?>"><?= ($subCa == 0) ? 'x' : ''; ?></td>
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
			</form>
		</div>
	</div>

	<?php 
	}else{?>
	    <h3 class="text-center text-info">No data found. Please check subject is added to selected exam</h3>
<?php 	}
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