<?php 
/**
* Template Name: Admin TabulationSheet
*/
global $wpdb,$s3sRedux; 

// Check if current user is a teacher and get their restrictions
$current_user = wp_get_current_user();
$isTeacher = (isset($current_user->roles[0]) && $current_user->roles[0] == 'um_teachers');
$teacherRestrictions = null;

if ($isTeacher) {
    global $wpdb;
    // Determine table name (try prefixed first, fallback to ct_teacher)
    $prefixed = $wpdb->prefix . 'ct_teacher';
    $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $prefixed));
    $table = ($exists === $prefixed) ? $prefixed : 'ct_teacher';
    
    $user_id = $current_user->ID;
    $teacher = $wpdb->get_row($wpdb->prepare("SELECT teacherOfClass, teacherOfSection FROM $table WHERE tecUserId = %d", $user_id));
    
    if ($teacher && !empty($teacher->teacherOfClass) && !empty($teacher->teacherOfSection)) {
        $teacherRestrictions = $teacher;
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

	<p id="theSiteURL" class="hidden"><?= get_template_directory_uri() ?></p>
	<div class="panel panel-info">
		<div class="panel-heading">
			<h3>
				Tabulation Sheet<br>
				<small>Find Out students Tabulation Sheet</small>
			</h3>
		</div>
		<div class="panel-body">
			<form action="" method="GET" class="form-inline">
				<input type="hidden" name="page" value="tabulation_sheet">

				<div class="form-group">
					<label>Class</label>
					<select id='resultClass' class="form-control" name="class" required>
						<?php

							// If teacher, only show their assigned class
							if ($isTeacher && $teacherRestrictions) {
								$classQuery = $wpdb->get_results( $wpdb->prepare(
									"SELECT classid,className FROM ct_class WHERE classid = %d AND classid IN (SELECT examClass FROM ct_exam GROUP BY examClass ORDER BY className ASC)",
									$teacherRestrictions->teacherOfClass
								));
							} else {
								$classQuery = $wpdb->get_results( "SELECT classid,className FROM ct_class WHERE classid IN (SELECT examClass FROM ct_exam GROUP BY examClass ORDER BY className ASC)" );
							}
							
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

	<?php

	if(isset($_GET['exam'])){
		$exam 	= $_GET['exam']; 
		$year 	= $_GET['syear']; 
		$class 	= $_GET['class'];
		$sec 		= isset($_GET['sec']) ? $_GET['sec'] : '';
		$grou 	= $_GET['grou'];
		
		?>
		<div class="container-fluid maxAdminpages">
			
			<div class="row">
				<div class="col-md-12">
			  	<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>
			  </div>
			  <div class="col-md-12">
			  	
			  	<div id="printArea" class="col-md-12 printBG" style="width: 8.27in">
					  <div class="printArea">
					  	<style type="text/css">
					  		@page  { size: auto; margin: 3cm !important;} 
					  		table { page-break-inside: avoid !important; }

					  	</style>

					  	<link rel="stylesheet" href="<?= get_template_directory_uri() ?>/css/tabulationSheet.css" />

				  		<div style="text-align: center; position: relative;">
				  			<img height="80px" style="position: absolute;left: 10px;top: 10px" src="<?= $s3sRedux['instLogo']['url'] ?>">
		  					<h2 style="margin: 5px 0 5px 0;"><b><?= $s3sRedux['institute_name'] ?></b></h2>
					  		<p style="color:#2b5591; font-size: 14px; margin: 0;"><?= $s3sRedux['institute_address'] ?></p>
					  		<h3>Tabulation sheet</h3>
				  		</div>

				  		<?php
				  			$quey = "SELECT className,examName";

				  			if($grou != '' ){ $quey .= ",groupName"; }
				  			if($sec != '' ){  $quey .= ",sectionName"; }

								$quey .= " FROM ct_class LEFT JOIN ct_exam ON ct_exam.examid = $exam";

								if($grou != ''){ $quey .= " LEFT JOIN ct_group ON ct_group.groupId = $grou"; }
				  			if($sec != ''){  $quey .= " LEFT JOIN ct_section ON ct_section.sectionid = $sec"; }
									
								$quey .= " WHERE classid = $class";

				  			$info = $wpdb->get_results( $quey ); 
							?>

				  		<table style="width: 100%">
				  			<tr style="background: #4472C4;print-color-adjust: exact; -webkit-print-color-adjust: exact;color: #fff">
				  				<td><b>Exam Name :</b></td>
				  				<td colspan="3"><?= $info[0]->examName ?></td>
				  				<td><b>Year/Section :</b></td>
				  				<td><?= $year ?></td>
				  			</tr>
				  			<tr style="background: #D9E2F3;print-color-adjust: exact; -webkit-print-color-adjust: exact;">
				  				<td><b>Class :</b></td>
				  				<td><?= $info[0]->className ?></td>
				  				<td><b>Section :</b></td>
				  				<td><?= ($sec != '') ? $info[0]->sectionName : '' ?></td>
				  				<td><b>Group :</b></td>
				  				<td><?= ($grou != '') ? $info[0]->groupName : '' ?></td>
				  			</tr>
				  		</table>
				  		<br>

				  		
				  		<table style="width: 100%">
				  			<thead>
					  			<tr style="background: #FFE599;print-color-adjust: exact; -webkit-print-color-adjust: exact;">
					  				<td>Subject name</td>
					  				<td width="50">Marks</td>
					  				<td width="60">Highest Marks</td>
					  				<td width="50"><?= $s3sRedux['cqtitle'] ?></td>
					  				<td width="50"><?= $s3sRedux['mcqtitle'] ?></td>
					  				<td width="50"><?= $s3sRedux['prctitle'] ?></td>
					  				<td width="50"><?= $s3sRedux['catitle'] ?></td>
					  				<td width="50">Total</td>
					  				<td width="50">Points</td>
					  				<td width="50">Grade</td>
					  				<td width="60">GPA</td>
					  			</tr>
				  			</thead>
				  		</table>

					  		<?php

					  			$grpqury = "SELECT spFaild FROM `ct_studentPoint`
					  				LEFT JOIN ct_studentinfo ON ct_studentinfo.infoStdid = ct_studentPoint.spStdID WHERE spExam = $exam AND spYear = $year AND spClass = $class";
					  			if($sec != ''){
					  				$grpqury .= " AND infoSection = $sec";
					  			}

					  			if ($grou != '') { $grpqury .= " AND infoGroup = $grou"; }

					  			$grpqury .= " GROUP BY spFaild ORDER BY spFaild ASC";

					  			$grpqury = $wpdb->get_results($grpqury);

					  			if($grpqury){
					  				foreach($grpqury as $group){
					  					$spFailds = $group->spFaild;

											$qry = "SELECT spStdID,stdName,infoRoll,sectionName,spPoint,spFaild,spPosition,spTotalMark FROM ct_studentPoint
												LEFT JOIN ct_student ON ct_student.studentid = ct_studentPoint.spStdID
												LEFT JOIN ct_studentinfo ON ct_studentinfo.infoStdid = ct_studentPoint.spStdID AND ct_studentinfo.infoYear = '$year' AND ct_studentinfo.infoClass = $class
												LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
												WHERE spYear = '$year' AND spClass = $class AND spExam = $exam AND spFaild = $spFailds";

											if ($sec != '') { $qry .= " AND infoSection = $sec"; }

											if ($grou != '') { $qry .= " AND infoGroup = $grou"; }

											$qry .= " ORDER BY spPoint DESC, spTotalMark DESC, infoRoll ASC";
							  			$students = $wpdb->get_results( $qry );

											foreach($students as $student){
							  				$std = $student->spStdID;
							  				$spFaild = $student->spFaild;
							  				$spPoint = $student->spPoint;
							  	
												if($spFaild == 0){
													$resGrade = 'A+';
													$meritPosition = "Merit Position : ".$student->spPosition;
													if 		($spPoint < 1){ $resGrade = 'F'; }
													elseif($spPoint < 2){ $resGrade = 'D'; } 
													elseif($spPoint < 3){ $resGrade = 'C'; } 
													elseif($spPoint < 3.5){ $resGrade = 'B'; } 
													elseif($spPoint < 4){ $resGrade = 'A-'; } 
													elseif($spPoint < 5){ $resGrade = 'A'; }
												}else{
													$resGrade = 'F'; $spPoint = '0.00';
													$meritPosition = $spPoint = 'Failed in '.$spFaild.' Subject/s';
												}
									  		?>
									  		<table class="tabulationitem" style="width: 100%">
									  		<tbody>
									  			
									  			<tr style="background: #FFF2CC;print-color-adjust: exact; -webkit-print-color-adjust: exact;">
									  				<td colspan="">Student Name: <?= $student->stdName ?></td>
									  				<td colspan="2">Total: <?= $student->spTotalMark ?></td>
									  				<td colspan="<?= ($sec == '') ? 2 : 4 ?>">Roll: <?= $student->infoRoll ?></td>
									  				<?php if ($sec == ''){ ?>
									  					<td colspan="2">Section: <?= $student->sectionName ?></td>
									  				<?php } ?>
									  				<td colspan="4"><?= $meritPosition ?></td>
									  			</tr>

									  			<?php

									  			/*==================
														For Combine mark
													====================*/

													$combines = $wpdb->get_results("SELECT *, (SELECT MAX(`resTotal`) as maxr FROM `ct_result` WHERE resClass = $class AND resExam = $exam AND resultYear = '$year' AND resSubject = ct_subject.subjectid) AS maxres FROM `ct_result`
														LEFT JOIN ct_subject ON ct_result.resSubject = ct_subject.subjectid
														LEFT JOIN ct_class ON $class = ct_class.classid
														WHERE resClass = $class AND resExam = $exam AND resultYear = '$year' AND resStudentId = $std AND subCombineMark = 1 ORDER BY sub4th,subOptinal,subCode ASC");

													$results2 = $wpdb->get_results( "SELECT *, (SELECT MAX(`resTotal`) as maxr FROM `ct_result` WHERE resClass = $class AND resExam = $exam AND resultYear = '$year' AND resSubject = ct_subject.subjectid) AS maxres FROM `ct_result`
														LEFT JOIN ct_subject ON ct_result.resSubject = ct_subject.subjectid
														LEFT JOIN ct_class ON $class = ct_class.classid
														WHERE resClass = $class AND resExam = $exam AND resultYear = '$year' AND resStudentId = $std AND  subCombineMark = 0 ORDER BY sub4th,subOptinal,subCode ASC" );

													$numberOf = sizeof($combines) + sizeof($results2);
													$pointNotShown = true;
													foreach ($combines as $combin) {
														if($combin->connecttedPaper == 0){
															$havecon = false;

															$subTot1 = $combin->subMCQ+$combin->subCQ+$combin->subPect;
															
															$obtain = $combin->resTotal;
															$subTot2 = 0;

															foreach ($combines as $combin2) {
																if($combin2->connecttedPaper == $combin->resSubject){

																	$havecon = true;
																	$obtain += $combin2->resTotal;

																	$subTot2 = isnum($combin2->subMCQ)+isnum($combin2->subCQ)+isnum($combin2->subPect);

																	$subName = $combin2->subjectName;
																	$secSuCQ = isnum($combin2->resCQ); 
																	$secSuMCQ = isnum($combin2->resMCQ);
																	$secSuPre = isnum($combin2->resPrec);
																	$secSuCa = isnum($combin2->resCa);

																	$highest2 = $combin2->maxres;
																	
																	break;
																}
															}

															$conbineCQ = isnum($combin->resCQ) + isnum($combin2->resCQ);
															$conbineMCQ = isnum($combin->resMCQ) + isnum($combin2->resMCQ);
															$conbinePrec = isnum($combin->resPrec) + isnum($combin2->resPrec);
															$conbineCa = isnum($combin->resCa) + isnum($combin2->resCa);

															$conSubCQ = isnum($combin->subCQ) + ($combin2->subCQ);
															$conSubMCQ = isnum($combin->subMCQ) + ($combin2->subMCQ);
															$conSubPrec = isnum($combin->subPect) + ($combin2->subPect);
															$conSubCa = isnum($combin->subCa) + ($combin2->subCa);
 
															$genRes = genPoint($conSubCQ,$conSubMCQ,$conSubPrec,$conSubCa,$conbineCQ,$conbineMCQ,$conbinePrec,$conbineCa,$combin->combineMark);
															$combinegrade = $genRes['grade'];
															$conbinePoint = $genRes['point'];

															?>
																<tr>
																	<td><?= $combin->subjectName; ?></td>
																	<td><?= $subTot1 ?></td>
																	<td><?= $combin->maxres ?></td>
																	<td><?= $combin->resCQ ?></td>
																	<td><?= $combin->resMCQ ?></td>
																	<td><?= $combin->resPrec ?></td>
																	<td><?= $combin->resCa ?></td>
																	<td <?= ($havecon) ? 'rowspan="2"' : ''; ?>><?= $obtain ?></td>
																	<td <?= ($havecon) ? 'rowspan="2"' : ''; ?>><?= $conbinePoint ?></td>
																	<td <?= ($havecon) ? 'rowspan="2"' : ''; ?>><?= $combinegrade ?></td>
																	<?php if($pointNotShown){ ?>
																		<td width="60" rowspan="<?= $numberOf ?>" style="text-align: center; ">
																			<strong><?= $resGrade ?></strong><br>
																			(<?= number_format((float)$spPoint, 2, '.', ''); ?>)
																		</td>
																	<?php } $pointNotShown = false; ?>
																</tr>
															<?php

															if($havecon){ ?>
																	<!-- 2nd Paper -->
																	<tr>
																		<td><?= $subName; ?></td>
																		<td><?= $subTot2; ?></td>
																		<td><?= $highest2 ?? ''; ?></td>
																		<td><?= $secSuCQ; ?></td>
																		<td><?= $secSuMCQ; ?></td>
																		<td><?= $secSuPre; ?></td>							
																	</tr>
																<?php
															}
														}
													}
													/*Combine mark end*/
													foreach ($results2 as $key => $result2) {
														$sub4th = $result2->resSub4th;
														$tempTotal = $result2->resTotal;
														$total =  $result2->subMCQ + $result2->subCQ + $result2->subPect + $result2->subCa;

														$genRes = genPoint($result2->subCQ,$result2->subMCQ,$result2->subPect,$result2->subCa,$result2->resCQ,$result2->resMCQ,$result2->resPrec,$result2->resCa,$result2->combineMark);
														$grade = $genRes['grade'];
														$point = $genRes['point'];

														if($grade == "F"){
															$fail = 1;
														}
														?>
															<tr>
																<td>
																	<?= $result2->subjectName; ?>
																	<?= ($sub4th == 1) ? '(4th Sub)' : '' ?>
																</td>
																<td width="50"><?= $total ?></td>
																<td width="60"><?= $result2->maxres ?></td>
																<td width="50"><?= $result2->resCQ ?></td>
																<td width="50"><?= $result2->resMCQ ?></td>
																<td width="50"><?= $result2->resPrec ?></td>
																<td width="50"><?= $result2->resCa ?></td>
																<td width="50"><?= $tempTotal ?></td>
																<td width="50"><?= $point ?></td>
																<td width="50"><?= $grade ?></td>
																<?php
										
																	if($pointNotShown && $key == 0){

																		?> 
																			<td width="60" rowspan="<?= $numberOf ?>" style="text-align: center; ">
																				<strong><?= $resGrade ?></strong><br>
																				(<?= number_format((float)$spPoint, 2, '.', ''); ?>)
																			</td>
																		<?php
																	}
																?>
															</tr>
													
														<?php
													
													}

												echo "</tbody>";
												echo "</table>";
								  		} 

					  				}
					  			}else{

					  				echo "<h3 class='alert alert-info text-center'>No result found.<br>Maybe result not published.</h3>";
					  			}

					  			
						  	?>

				  		</table>
					  </div>
					</div>
			  </div>
			</div>
			
		</div>

		<?php 
	} ?>

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