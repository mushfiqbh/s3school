<?php 
/**
* Template Name: Admin Progress Report
*/
global $wpdb,$s3sRedux; 

if ( ! is_admin() ) { get_header(); ?>

<div class="b-layer-main">

	<div class="">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
<?php } ?>

	<p id="theSiteURL" class="hidden"><?= get_template_directory_uri() ?></p>
	<div class="panel panel-info">
		<div class="panel-heading">
			<h3>
				Progress Report<br>
				<small>Find Out students Progress Report</small>
			</h3>
		</div>
		<div class="panel-body">
			<form action="" method="GET" class="form-inline">

				<div class="form-group">
					<input type="hidden" name="page" value="progressReport">
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
					<label>Section</label>
					<select id="resultSection" class="form-control" name="sec" required disabled>
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
					<select id='resultYear' class="form-control" name="syear" required disabled>
						<option disabled selected>Select Class First</option>
					</select>
				</div>


				<div class="form-group">
					<input class="form-control btn btn btn-primary" type="submit" name="" value="Go">
				</div>
			</form>
		</div>

	</div>


	<!-- Report View -->
	<div class="text-right">
  	<button onclick="print('printArea')" class="btn btn-primary">Print</button>
  </div>
	<div id="printArea">
			<link rel="stylesheet" href="<?= get_template_directory_uri() ?>/css/tabulationSheet.css" />
			<style type="text/css">
				.detTbl tr td,.detTbl tr th{ border: 1px solid #000; padding: 5px; }
				.tbTable tr td,.tbTable tr th{ border: 1px solid #000; padding:1px; line-height: 1; text-align: center; font-size: 11px; }
				/*.rotate90 {	transform: rotate(-45deg); }*/
				@media print {
		  		@page  { size: auto; margin: 30px !important;} 
		  		table { page-break-inside: avoid !important; }
				}
	  		table td, table th {
					padding: 2px 4px !important;
				}
				.virticalText {	position: absolute;	transform: rotate(-90deg);	top: 13px;	right: -15px;	text-align: center;	height: 35px;	width: 60px;}
				.virticalText.singel{
					right: -22px;
				}
				.textCenter{
					text-align: center;
				}
				.thstyle{
					width: 35px;line-height: 1;height: 60px;text-align: center;padding:3px;position:relative;
				}
			</style>
		<?php 
		if(isset($_GET['syear']) && isset($_GET['class'])){

			$year 	= $_GET['syear']; 
			$class 	= $_GET['class'];
			$sec 		= isset($_GET['sec']) 	? $_GET['sec'] 	: '';
			$grou 	= isset($_GET['grou'])  ? $_GET['grou'] : '';

			$quey = "SELECT className,havecgpa,combineMark";

  			if($grou != '' ){ $quey .= ",groupName"; }
  			if($sec != '' ){  $quey .= ",sectionName"; }

				$quey .= " FROM ct_class";

				if($grou != ''){ $quey .= " LEFT JOIN ct_group ON ct_group.groupId = $grou"; }
  			if($sec != ''){ $quey .= " LEFT JOIN ct_section ON ct_section.sectionid = $sec"; }
					
			$quey .= " WHERE classid = $class";

			$info = $wpdb->get_results( $quey ); 
			$havecgpa = $info[0]->havecgpa;


			$stdQuery = "SELECT studentid,stdName,stdImg,stdFather,infoRoll,sectionName,cgpaFaild,cgpaPoint,cgpaTotalMark,cgpaPosition,cgpaSecPosi FROM `ct_student`
			 	LEFT JOIN ct_studentinfo ON infoStdid = studentid AND infoYear = '$year' AND infoClass = $class
			 	LEFT JOIN ct_section ON infoSection = sectionid
			 	LEFT JOIN ct_cgpa ON cgpaStudent = studentid AND cgpaYear = '$year' AND cgpaClass = $class
			  WHERE studentid IN (SELECT spStdID FROM ct_studentPoint WHERE spClass = $class AND spYear = '$year' GROUP BY spStdID)";

			  
			$stdQuery .= ($grou != '') ? " AND infoGroup = $grou" : '';
			$stdQuery .= ($sec != '')  ? " AND infoSection = $sec" : '';
			$stdQuery .= " ORDER BY infoRoll,sectionName";
			$students  = $wpdb->get_results( $stdQuery );

			echo "Total:". sizeof($students);
			function emptyRow($key,$subKey){
				echo "<tr>";
    		if($key == 0){ 
	    		echo "<td>$subKey</td><td></td>";
				}
				echo "<td><span style='color: transparent;'>0</span></td><td></td><td></td><td></td><td></td><td></td>
				<td></td></tr>";
			}
			function emptyRowNoborder(){
				echo '<tr><td class="noborder"><span style="color: transparent;">0</span></td><td class="noborder"></td><td class="noborder"></td><td class="noborder"></td><td class="noborder"></td><td class="noborder"></td><td class="noborder"></td></tr>';
			}
			
			$subNameNotPrint = true;

			if($students){

				/*=========== Student loop ==============*/
				foreach ($students as $student) {
					$std = $student->studentid;
					
			    ?>
			    	<div class="preportstd">
							<table>
								<tr>
									<td colspan="4" class="noborder">
							    	<table style="width: 100%;margin-top: 10px;text-align: center;">
											<tbody>
												<tr style="border: 0">
													<td style="border: 0">
														<img  height="80px" src="<?= $s3sRedux['instLogo']['url'] ?>">
													</td>
													<td style="border: 0;">
														<h2 style="color: #4472c4;margin: 0"><?= $s3sRedux['institute_name'] ?></h2>
														<h5><?= $s3sRedux['institute_address'] ?></h5>
														<h4 style="margin: 0">Progress Report</h4>
													</td>
													<td style="border: 0">
														<?php if(!empty($student->stdImg)){ ?>
															<img  height="80px" src="<?=  $student->stdImg ?>">
														<?php }else{ ?>
															<img src="<?= home_url(); ?>/wp-content/themes/s3schoolManagment/img/No_Image.jpg" height="80px">
														<?php } ?>
													</td>
												</tr>
											</tbody>
										</table>
									</td>
								</tr>
								<tr>
									<td colspan="4" class="noborder">
							    	<table style="width: 100%;margin-top: 5px;">
											<tbody>
												<tr style="background: #4472C4;print-color-adjust: exact; -webkit-print-color-adjust: exact;color: #fff">
													<td><b>Name :</b> <?=  $student->stdName ?></td>
													<td><b>Father :</b> <?=  $student->stdFather ?></td>
													<td><b>Class :</b> <?= $info[0]->className ?></td>
													<td><b>Roll :</b> <?=  $student->infoRoll ?></td>
													<td><b>Year :</b> <?=  $year ?></td>
													<td><b>Section :</b> <?=  $student->sectionName ?></td>
												</tr>
											</tbody>
										</table>
									</td>
								</tr>
								<tr>
									<?php
										$exams = $wpdb->get_results("SELECT spExam,examName,spFaild,spPoint,spPosition,spClassPosition,spPoint,spTotalMark,spSubjTotal FROM `ct_studentPoint`
							      	LEFT JOIN ct_exam ON ct_exam.examid = ct_studentPoint.spExam
							      	WHERE spClass = $class AND spYear = '$year' AND spStdID = $std GROUP BY spExam ORDER BY examSirial");
										$totalExam = sizeof($exams);

						      	$totalMrk = $obtainMrk = 0;
								    foreach ($exams as $key => $exam) { if($key > 2){ break; }
								    	$totalMrk += $exam->spSubjTotal;
								    	$obtainMrk += $exam->spTotalMark;
								    	$subExam = $exam->spExam;
							      	$subNames = $wpdb->get_results("SELECT *, (SELECT MAX(`resTotal`) as maxr FROM `ct_result` WHERE resClass = $class AND resExam = $subExam AND resultYear = '$year' AND resSubject = ct_subject.subjectid) AS maxres FROM `ct_result`
												LEFT JOIN ct_subject ON ct_result.resSubject = ct_subject.subjectid
												LEFT JOIN ct_class ON $class = ct_class.classid
												WHERE resClass = $class AND resExam = $subExam AND resultYear = '$year' AND resStudentId = $std ORDER BY subCombineMark DESC,sub4th,subOptinal,subCode,subjectName ASC");
							      	if(sizeof($subNames) > 0){
									    ?>
									    
									    	<td <?= $key == 0 ? 'colspan="2"' : ''; ?> class="noborder">
													<table>
														<tr>
															<th colspan="<?= $key == 0 ? 9 : 7; ?>" style="background: #a1dcff;text-align: center;color: #000">
																<?= $exam->examName ?>
															</th>
														</tr>

											    	<tr>
															<?php if($key == 0 || $subNameNotPrint){ $subNameNotPrint = false; ?>
												    		<td class="thstyle">SL. No.</td>
																<th>Subject Name</th>
															<?php } ?>
															<td class="thstyle"><div class="virticalText singel">CQ</div></td>
															<td class="thstyle"><div class="virticalText singel">MCQ</div></td>
															<td class="thstyle"><div class="virticalText singel">PA</div></td>
															<td class="thstyle"><div class="virticalText">Total<br>Mark</div></td>
															<td class="thstyle"><div class="virticalText">Grade<br>point</div></td>
															<td class="thstyle"><div class="virticalText">Latter<br>Grade</div></td>
															<td class="thstyle"><div class="virticalText">Highest<br>Mark</div></td>
														</tr>
														<?php foreach ($subNames as $subKey => $subNa) {
														
																													$subCQ		= $subNa->subCQ;
																													$subMCQ		= $subNa->subMCQ;
																													$subPect	= $subNa->subPect;
																													$stdCQ		= $subNa->resCQ;
																													$stdMCQ		= $subNa->resMCQ;
																													$stdPrec	= $subNa->resPrec;
																													$combine	= $subNa->combineMark;
																													$genFun = genPoint($subCQ,$subMCQ,$subPect,$stdCQ,$stdMCQ,$stdPrec,$combine,0,0);
																													$resTotalTemp = 0;
																													?>
												    	<tr>
												    		<?php if($key == 0){ ?> 
													    		<td><?= $subKey + 1 ?></td>
																	<td><?= $subNa->subjectName ?></td>
																<?php } ?>
																<td class="textCenter"><?= $subNa->resCQ ?></td>
																<td class="textCenter"><?= $subNa->resMCQ ?></td>
																<td class="textCenter"><?= $subNa->resPrec ?></td>
																<?php if($subNa->subPaper == 1 && $info[0]->combineMark){ ?>
																	<?php
																		$resCQTemp1   = $subNa->resCQ;
																		$resMCQTemp1  = $subNa->resMCQ;
																		$resPrecTemp1 = $subNa->resPrec;
																		if($subNames[$subKey + 1]->connecttedPaper == $subNa->subjectid){
																			$resCQTemp2 	= $subNames[$subKey + 1]->resCQ;
																			$resMCQTemp2 	= $subNames[$subKey + 1]->resMCQ;
																			$resPrecTemp2 = $subNames[$subKey + 1]->resPrec;
																		}else{
																			foreach ($subNames as $subNa2) {
																				if($subNa2->connecttedPaper == $subNa->subjectid){
																					$resCQTemp2 	= $subNa2->resCQ;
																					$resMCQTemp2 	= $subNa2->resMCQ;
																					$resPrecTemp2	= $subNa2->resPrec;
																					break;
																				}
																			}
																		}
																		$cqtmp  = (isnum($resCQTemp1)+isnum($resCQTemp2))/2;
																		$mcqtmp = (isnum($resMCQTemp1)+isnum($resMCQTemp2))/2;
																		$pretmp = (isnum($resPrecTemp1)+isnum($resPrecTemp2))/2;
																		$combine = 
																		$genTemp = genPoint($subNa->subCQ,$subNa->subMCQ,$subNa->subPect,$cqtmp,$mcqtmp,$pretmp,$info[0]->combineMark,0,0);
																		$resTotalTemp = isnum($resCQTemp1) + isnum($resMCQTemp1) + isnum($resPrecTemp1) + isnum($resCQTemp2) + isnum($resMCQTemp2) + isnum($resPrecTemp2);
																	?>
																	<td class="textCenter" rowspan="2"><?= $resTotalTemp ?></td>
																	<td class="textCenter" rowspan="2"><?= $genTemp['point'] ?></td>
																	<td class="textCenter" rowspan="2"><?= $genTemp['grade'] ?></td>
																<?php }elseif($subNa->subPaper == 0 || !$info[0]->combineMark){ ?>
																	<td class="textCenter"><?= $genFun['total'] ?></td>
																	<td class="textCenter"><?= $genFun['point'] ?></td>
																	<td class="textCenter"><?= $genFun['grade'] ?></td>
																<?php } ?>
																<td class="textCenter"><?= $subNa->maxres ?></td>
															</tr>
														<?php } 
															if($subKey < 8){emptyRow($key,$subKey+2); if($subKey < 6){emptyRow($key,$subKey+3);}}
														?>

														<tr style="background: #d9edf7">
															<th colspan="<?= $key == 0 ? 6 : 4; ?>" style='font-size: 12px'>
																Obtain <?= $exam->spTotalMark ?> out of <?= $exam->spSubjTotal ?>
															</th>
															<th><?= $exam->spFaild == 0 ? $exam->spPoint : '0.00' ?> </th>
															<th><?= $exam->spFaild == 0 ? pointToGrade($exam->spPoint) : 'F' ?> </th>
															<th></th>
														</tr>
														<tr style="background: #d9edf7;">
															<th style="text-align: center;" colspan="<?= $key == 0 ? 9 : 7; ?>">Position - Class=<?= $exam->spClassPosition ?>, Section=<?= $exam->spPosition ?></th>
														</tr>
														<?php if($subKey < 8){ emptyRowNoborder();} ?>
													</table>
												</td>
			    						<?php }
			    					}
			    				?>
								</tr>
								<tr>
									<td class="noborder">

										<!-- %%%%%%%%%%%%%%%%%% Final Result %%%%%%%%%%%%%%%%%% -->
										<table>
											<tr>
												<th colspan="4" style="background: #a1dcff;text-align: center;color: #000">Final Result</th>
											</tr>
											<tr>
												<th>Total Mark</th>
												<td><?= $totalMrk ?></td>
												<th colspan="2" style="text-align: center;">Position</h>
											</tr>
											<tr>
												<th>Obtained Mark</th>
												<td><?= $obtainMrk ?></td>
												<th>Class</th>
												<th>Section</th>
											</tr>
											<tr>
												<th>C.G.P.A</th>
												<td><?= $student->cgpaFaild == 0 ? $student->cgpaPoint : '0.00'; ?></td>
												<th rowspan="2"><h3 style="text-align: center;margin: 0"><?= $student->cgpaPosition ?></h3></th>
												<th rowspan="2"><h3 style="text-align: center;margin: 0"><?= $student->cgpaSecPosi; ?></h3></th>
											</tr>
											<tr>
												<th>L.G</th>
												<td><?= $student->cgpaFaild == 0 ? pointToGrade($student->cgpaPoint) : 'F'; ?></td>
											</tr>
										</table>
									</td>
									<td class="noborder">
										<table style="text-align: center;">
											<tr>
												<th colspan="4" style="background: #a1dcff;text-align: center;color: #000">Grading System</th>
											</tr>
											<tr>
												<th colspan="2">80 - 100</th>
												<td colspan="2">A+</td>
											</tr>
											<tr>
												<th>70 - 79</th>
												<td>A</td>
												<th>60 - 69</th>
												<td>A-</td>
											</tr>
											<tr>
												<th>50 - 59</th>
												<td>B</td>
												<th>40 - 49</th>
												<td>C</td>
											</tr>
											<tr>
												<th>33 - 39</th>
												<td>D</td>
												<th>0 - 32</th>
												<td>F</td>
											</tr>
										</table>
									</td>
									<?php 
										for ($i=sizeof($subNames); $i < 12; $i++) { 
											echo "<br>";
										}
									 ?>
									<td class="noborder" colspan="2">
										<table style="border-right: 1px solid #333;">
											<tr>
												<th colspan="2" class="textCenter">Attendance  _ _ _ _ _ _ out of _ _ _ _ _ _</th>
											</tr>
											<tr>
												<th width="20%" style="text-align: center;background: #d9edf7;">
													<p style="padding: 12px 0">Class Teacher comment & Signature</p>
												</th>
												<th></th>
											</tr>
										</table>
									</td>

								</tr>
								<tr>
									<td class="noborder" colspan="4">
										<div style="text-align: right;padding-right: 30px">
											<img style="margin:0 10px;" src="<?= $s3sRedux['principalSign']['url'] ?>" width="100" align="Principal_Signature">
											<p>Principal's Signature</p>
										</div>
									</td>
								</tr>
								<?php if (sizeof($subNames) < 11) { ?>
									<tr>
										<td class="noborder" colspan="4" style="color: #999 ">
											<small>Generated  by Bornomala, Developed  by MS3 Technology  BD, Urmi-43, Shibgonj, Sylhet. Email: bornomala.ems@gmail.com</small>
											<small style="float: right;">Copy right <?= home_url() ?></small>
										</td>
									</tr>
								<?php } ?>
							</table>
						</div>
					<?php
				}
			}
		}
		?>
	</div>

<?php if ( ! is_admin() ) { ?>
				</div>
			</div>
		</div>
	</div>
</div>
<?php get_footer(); } ?>


<script type="text/javascript">
	(function($) {
		$('#resultClass').change(function() {
	    var $siteUrl = $('#theSiteURL').text();

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


<?php
// ===============================================================
// FIX 409 CONFLICT - HANDLE AJAX ACTIONS LOCALLY (At End of File)
// ===============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'])) {

  // Clean output buffer to ensure JSON/HTML response is valid
  while (ob_get_level()) {
    ob_end_clean();
  }

  // ------------------------------------------
  // Get Exams
  // ------------------------------------------
  if ($_POST['type'] == 'getExams') {
    $class = $_POST['class'];
    $exams = $wpdb->get_results("SELECT examid,examName FROM ct_exam WHERE examClass = '$class'");
    if (empty($exams)) {
      echo "<option value=''>No Exam for this Class</option>";
    } else {
      echo "<option value=''>Select An Exam</option>";
    }
    foreach ($exams as $exam) {
      echo "<option value='{$exam->examid}'>{$exam->examName}</option>";
    }
    exit;
  }

  // ------------------------------------------
  // Get Years
  // ------------------------------------------
  elseif ($_POST['type'] == 'getYears') {
    $class = $_POST['class'];
    $years = $wpdb->get_results("SELECT infoYear FROM ct_studentinfo WHERE infoClass = $class GROUP BY infoYear ORDER BY infoYear ASC");
    if (empty($years)) {
      echo "<option value=''>No Student In this class</option>";
    } else {
      echo "<option value=''>Year</option>";
    }
    foreach ($years as $year) {
      echo "<option value='{$year->infoYear}'>{$year->infoYear}</option>";
    }
    exit;
  }

  // ------------------------------------------
  // Get Section
  // ------------------------------------------
  elseif ($_POST['type'] == 'getSection') {
    $class = $_POST['class'];
    $sections_query = "SELECT sectionid,sectionName FROM ct_section WHERE forClass = '$class'";
    
    $sections_query .= " ORDER BY sectionName";
    $sections = $wpdb->get_results($sections_query);

    if (!empty($sections)) {
      echo "<option value=''>Section</option>";
      foreach ($sections as $section) {
        echo "<option value='{$section->sectionid}'>{$section->sectionName}</option>";
      }
    } else {
      echo "<option value=''>No sections available</option>";
    }
    exit;
  }

  // ------------------------------------------
  // Get Groups
  // ------------------------------------------
  elseif ($_POST['type'] == 'getGroupsByClass') {
    $class = $_POST['class'];
    $groups_query = "SELECT DISTINCT ct_group.groupId, ct_group.groupName 
            FROM ct_group 
            INNER JOIN ct_studentinfo ON ct_studentinfo.infoGroup = ct_group.groupId 
            WHERE ct_studentinfo.infoClass = '$class'";
    
    $groups_query .= " ORDER BY ct_group.groupName ASC";
    $groups = $wpdb->get_results($groups_query);

    echo "<option value=''>All Groups</option>";
    foreach ($groups as $group) {
      echo "<option value='{$group->groupId}'>{$group->groupName}</option>";
    }
    exit;
  }

  // ------------------------------------------
  // Get Exam Subjects
  // ------------------------------------------
  elseif ($_POST['type'] == 'getExamSubject') {
    $exam = intval($_POST['exam']);
    $group = isset($_POST['group']) ? $_POST['group'] : '';
    $subjects = [];

    $subs = $wpdb->get_results("SELECT examSubjects FROM ct_exam WHERE examid = $exam");

    if (!empty($subs[0]->examSubjects)) {
      $subs = json_decode($subs[0]->examSubjects, true);
    } else {
      $subs = [];
    }

    if (!empty($subs)) {
      $subs_escaped = array_map('intval', $subs);
      $subjectQuery = "SELECT subjectid,subjectName FROM ct_subject 
                WHERE subjectid IN (" . implode(',', $subs_escaped) . ")";

      if (!empty($group)) {
        $subjectQuery .= " AND (forGroup = 'all' OR forGroup = '$group' OR forGroup LIKE '%\"$group\"%')";
      }

      $subjectQuery .= " ORDER BY subjectName ASC";
      $subjects = $wpdb->get_results($subjectQuery);
    }

    if (empty($subjects)) {
      echo "<option value=''>No subject!</option>";
    } else {
      echo "<option value=''>Select Subject</option>";
      foreach ($subjects as $subject) {
        echo '<option value="' . $subject->subjectid . '">' . $subject->subjectName . '</option>';
      }
    }
    exit;
  }
}

if (isset($_POST['updateAllResult'])) {
  $cq = $_POST['CQ'];
  $mcq = $_POST['MCQ'];
  $prc = $_POST['P'];
  $ca = $_POST['ca'];
  $response = false;
  foreach ($_POST['id'] as $id) {
    $update = $wpdb->update(
      'ct_result',
      array(
        'resCQ'     => $cq[$id],
        'resMCQ'     => $mcq[$id],
        'resPrec'   => $prc[$id],
        'resCa'   => $ca[$id],
        'resTotal'   => isnum($cq[$id]) + isnum($mcq[$id]) + isnum($prc[$id]) + isnum($ca[$id])
      ),
      array('resultId' => $id)
    );
    if ($update) {
      $response = $update;
    }
  }
  if ($response) {
    $message = array('status' => 'success', 'message' => 'Successfully updated');
  } else {
    $message = array('status' => 'faild', 'message' => 'Something wrong please try again');
  }
} ?>

<script type="text/javascript">
  // ==================================
  // HANDLE AJAX ACTIONS LOCALLY
  // ==================================
  (function($) {
    // Use current page as AJAX URL for standalone processing
    var ajaxUrl = '';

    $('#resultClass').change(function() {
      var selectedClass = $(this).val();

      // Fetch Exams
      $.ajax({
        url: ajaxUrl,
        method: "POST",
        data: {
          class: selectedClass,
          type: 'getExams'
        },
        dataType: "html"
      }).done(function(msg) {
        $("#resultExam").html(msg);
        $("#resultExam").prop('disabled', false);
        // Reset dependent dropdowns
        $("#resultSubject").prop('disabled', true).html('<option disabled selected>Select exam First</option>');
      });

      // Fetch Years
      $.ajax({
        url: ajaxUrl,
        method: "POST",
        data: {
          class: selectedClass,
          type: 'getYears'
        },
        dataType: "html"
      }).done(function(msg) {
        $("#resultYear").html(msg);
        $("#resultYear").prop('disabled', false);
      });

      // Fetch Sections
      $.ajax({
        url: ajaxUrl,
        method: "POST",
        data: {
          class: selectedClass,
          type: 'getSection'
        },
        dataType: "html"
      }).done(function(msg) {
        $("#resultSection").html(msg);
        $("#resultSection").prop('disabled', false);
      });

      // Fetch All Groups
      $.ajax({
        url: ajaxUrl,
        method: "POST",
        data: {
          class: selectedClass,
          type: 'getGroupsByClass'
        },
        dataType: "html"
      }).done(function(msg) {
        $("#resultGroup").html(msg);
        $("#resultGroup").prop('disabled', false);
      });
    });

    // Fetch Subjects when Exam Changes
    $('#resultExam').change(function() {
      var selectedExam = $(this).val();
      var selectedGroup = $('#resultGroup').val();

      $.ajax({
        url: ajaxUrl,
        method: "POST",
        data: {
          exam: selectedExam,
          group: selectedGroup,
          type: 'getExamSubject'
        },
        dataType: "html"
      }).done(function(msg) {
        $("#resultSubject").html(msg);
        $("#resultSubject").prop('disabled', false);
      });
    });

    // Fetch Subjects when Group Changes
    $('#resultGroup').change(function() {
      var selectedExam = $('#resultExam').val();
      var selectedGroup = $(this).val();

      if (selectedExam) {
        $.ajax({
          url: ajaxUrl,
          method: "POST",
          data: {
            exam: selectedExam,
            group: selectedGroup,
            type: 'getExamSubject'
          },
          dataType: "html"
        }).done(function(msg) {
          $("#resultSubject").html(msg);
          $("#resultSubject").prop('disabled', false);
        });
      }
    });

    // Interactive validation for result inputs (Client-side only)
    $('.resultInput').keyup(function(event) {
      $this = $(this);
      $val = $this.val();
      $max = $this.data('max');

      if ($val == '' || $val < ($max + 1) || $val == 'A' || $val == 'a') {
        $this.css('border-color', '#ddd');
        $this.removeClass('haserror');
      } else {
        $this.addClass('haserror');
        $this.css('border-color', 'red');
        $('.resultSubmit').prop('disabled', true);
      }

      if ($('.resultInput.haserror').length == 0) {
        $('.resultSubmit').prop('disabled', false);
      }
    });

  })(jQuery);
</script>