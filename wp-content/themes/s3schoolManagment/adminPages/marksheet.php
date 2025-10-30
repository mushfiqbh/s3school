<?php 
/**
* Template Name: Admin All MarkSheet
*/
global $wpdb,$s3sRedux; 
$convertPercent = 70;

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
				Mark Sheet<br>
				<small>Find Out students Mark Sheet</small>
			</h3>
		</div>
		<div class="panel-body">
			<form action="" method="GET" class="form-inline labelBlockFrom">

				<div class="form-group">
					<input type="hidden" name="page" value="marksheet">
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
					<label>Roll</label>
					<input type="number" name="roll" class="form-control">
				</div>


				<div class="form-group">
					<br>
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
		$sec 		= $_GET['sec'];
		$grou 	= $_GET['grou'];
		$roll 	= $_GET['roll'] ?? '';
		
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
					  		@page  { size: auto; margin: 0 !important; padding-top: 10px} 
					  		.transcript { page-break-inside: avoid !important; }
					  	</style>

			
				  		<?php //&class=49&exam=1&sec=11&grou&syear=2019
				  		
				  	    	// $examSubjects = $wpdb->get_results("
            //                     SELECT examSubjects FROM ct_exam WHERE examid = $exam AND examClass = $class
            //                 ");
                           
            //                 // Convert examSubjects into an array (assuming it is stored as a comma-separated string)
            //                 $examSubjectIDs = array_map('intval', explode(',', $examSubjects));
                             
                            // Ensure we have valid IDs before querying
                            // if (!empty($examSubjectIDs)) {
                                // Convert array to a comma-separated string for SQL query
                                // $ids = implode(",", $examSubjectIDs);
                            
                                // Query to get the sum of subCa
                                $subjects = json_decode($wpdb->get_var("SELECT examSubjects FROM ct_exam WHERE examid = $exam AND examClass = $class"), true);
            $ids = implode(',', array_map('intval', (array)$subjects));
                                
            $total_subCa = $ids ? $wpdb->get_var("SELECT IFNULL(SUM(subCa), 0) FROM ct_subject WHERE subjectid IN ($ids)") : 0;
                                // print_r($total_subCa);exit;
                            // }
				  			$quey = "SELECT infoStdid,infoRoll FROM `ct_studentPoint`
				  			LEFT JOIN ct_studentinfo ON ct_studentinfo.infoStdid = ct_studentPoint.spStdID AND ct_studentinfo.infoClass = $class AND ct_studentinfo.infoYear = '$year'
				  			WHERE spYear = '$year' AND spClass = $class AND infoSection = $sec AND spExam = $exam";
				  			$quey .= $roll != '' ? "  AND infoRoll = '$roll'" : '';
				  			$quey .= " ORDER BY infoRoll";
				  			$info = $wpdb->get_results( $quey );

				  			if(sizeof($info) > 0){
					  			foreach ($info as $value) {
					  				$stdnt  =	$value->infoStdid;
										$roll 	= $value->infoRoll;

										if ($stdnt != '' && $roll != '') {
						  				?>

						  					<div class="transcript" style="width: 794px;margin: 15px auto">
													<style type="text/css">
														.section1 table,.section2 table,.section3 table{width:100%}.mainTable,.section1,.section3 h3,.section3 table th{text-align:center}.transcript{border:3px solid #84c4fc;padding:15px 15px 5px}.section1{margin-bottom:30px}.section1 .item,.section2 .item{display:table-cell;vertical-align:top;width:100%}.item.mid h3{margin-top:0}.section3 table,.section3 td,.section3 th{border:1px solid #000}.gradingTable td,.gradingTable th,.gradingTable tr{font-size:11px;min-width:55px;text-align:center;border:1px solid #333}.mainTable td,.mainTable tfoot th{padding:5px} table{ border-collapse: collapse;}.section4 {margin: 25px -15px 0;height: 35px;padding: 0 15px;line-height: 1;}.secLeft {width: 50%;float: left;}
													</style>

													<div class="section1" style="margin-bottom:15px">
														<table>
															<tr>
																<td style="width: 140px;">
																	<div class="item left">
																		<?php
																			$qry = "SELECT stdImg,stdName,infoRoll,stdFather,stdMother,stdCurntYear,className,groupName,sectionid,sectionName FROM ct_student
																			LEFT JOIN ct_studentinfo ON studentid = infoStdid AND infoClass = $class AND infoRoll = '$roll' AND infoYear = '$year'
																			LEFT JOIN ct_class ON ct_studentinfo.infoClass = ct_class.classid
																			LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId
																			LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
																			WHERE studentid = $stdnt LIMIT 1";
																			$student = $wpdb->get_results( $qry );

																		?>
																		<?php if ($student[0]->stdImg == ""){ ?>
															
																		<?php }else{ ?>
																			<img width="100" src="<?= $student[0]->stdImg ?>">
																		<?php }?>
																	</div>
																</td>
																<td>
																	<div style="text-align: center;">
																		<h3 style="margin-top:5px;margin-bottom:3px;"><?= $s3sRedux['institute_name'] ?></h3>
																		<p style="font-size: 17px"><?= $s3sRedux['institute_address'] ?></p>
								                     	<img width="100" src="<?= $s3sRedux['instLogo']['url'] ?>">					
																		<h4 style="font-size: 20px;margin-top:3px;margin-bottom:0px;">Academic Transcript</h4>
																	</div>
																</td>
																<td style="width: 140px;">
																	<div class="item right">
																		<table class="gradingTable">
																			<tr style="background: #eee">
																				<th>Range</th>
																				<th>LG</th>
																				<th>GPA</th>
																			</tr>
																			<tr>
																				<td>80-100</td>
																				<td>A+</td>
																				<td>5.0</td>
																			</tr>
																			<tr>
																				<td>70-79</td>
																				<td>A</td>
																				<td>4.0</td>
																			</tr>
																			<tr>
																				<td>60-69</td>
																				<td>A-</td>
																				<td>3.5</td>
																			</tr>
																			<tr>
																				<td>50-59</td>
																				<td>B</td>
																				<td>3.0</td>
																			</tr>
																			<tr>
																				<td>40-49</td>
																				<td>C</td>
																				<td>2.0</td>
																			</tr>
																			<tr>
																				<td>33-39</td>
																				<td>D</td>
																				<td>1.0</td>
																			</tr>
																			<tr>
																				<td>00-32</td>
																				<td>F</td>
																				<td>0.0</td>
																			</tr>
																		</table>
																	</div>
																</td>
															</tr>
														</table>
														
													</div>

													<?php foreach ($student as $student) { ?>

														<div class="section2">
															<table>
																<tr>
																	<td>
																		<table>
																			<tr>
																				<td style="width: 110px">Student Name</td>
																				<td style="padding: 0 5px;"> : </td>
																				<th style="text-align: left;"><?= $student->stdName ?></th>
																			</tr>
																			<tr>
																				<td>Father Name</td>
																				<td style="padding: 0 5px;"> : </td>
																				<th style="text-align: left;"><?= $student->stdFather ?></th>
																			</tr>
																			<tr>
																				<td>Mother Name</td>
																				<td style="padding: 0 5px;"> : </td>
																				<th style="text-align: left;"><?= $student->stdMother ?></th>
																			</tr>
																			<tr>
																				<td>Group</td>
																				<td style="padding: 0 5px;"> : </td>
																				<th style="text-align: left;"><?= $student->groupName ?></th>
																			</tr>
																			
																		</table>
																	</td>
																	<td>
																		<table>
																			<tr>
																				<td style="width: 110px">Class</td>
																				<td style="padding: 0 5px;"> : </td>
																				<th style="text-align: left;"><?= $student->className ?></th>
																			</tr>
																			<tr>
																				<td>Section</td>
																				<td style="padding: 0 5px;"> : </td>
																				<th style="text-align: left;"><?= $student->sectionName ?></th>
																			</tr>
																			<tr>
																				<td>Roll</td>
																				<td style="padding: 0 5px;"> : </td>
																				<th style="text-align: left;"><?= $student->infoRoll ?></th>
																			</tr>
																			<tr>
																				<td>Year/Session</td>
																				<td style="padding: 0 5px;"> : </td>
																				<th style="text-align: left;"><?= $year ?></th>
																			</tr>
																		</table>
																	</td>
																</tr>
															</table>
														</div>

													<?php } ?>

													<div class="section3" style="background: url(<?= $s3sRedux['instLogo']['url'] ?>) no-repeat center; background-size: 400px; ">
														<div style="background: rgba(255,255,255,0.9);">
															<h3 style="margin-top:3px;margin-bottom:3px;">
																<?php
																	$exams = $wpdb->get_results( "SELECT examName FROM ct_exam WHERE examid = $exam LIMIT 1" );
																	foreach ($exams as $exam) {
																		echo $exam->examName." ".$year;
																	}
																		$class 	= $_GET['class'];
																		$exam 	= $_GET['exam'];
																		$year 	= $_GET['syear'];		
                //                                                     $results2 = $wpdb->get_results( "SELECT * FROM `ct_result`
																// 			LEFT JOIN ct_subject ON ct_result.resSubject = ct_subject.subjectid
																// 			LEFT JOIN ct_class ON $class = ct_class.classid
																// 			WHERE resClass = $class AND resExam = $exam AND resultYear = '$year' AND resStdRoll = '$roll' AND resStudentId = $stdnt AND subCombineMark = 0 GROUP BY resSubject ORDER BY sub4th,subOptinal,subCode ASC" );

																?>
															</h3>
															
															<!--converted marks-->
															<table class=" mainTable">
																<thead style="background:rgba(238, 238, 238, .5)">
																	<tr>
																		<th rowspan="2">Subject Name</th>
																		<th rowspan="2">Marks</th>
																		<?php if($total_subCa > 0){?>
																		    <th colspan="6">Obtain Marks</th>
																		<?php } else{?>
																		    <th colspan="3">Obtain Marks</th>
																		<?php }?>
																		<th rowspan="2">Total</th>
																		<th rowspan="2">Highest<br> Marks</th>
																		<th rowspan="2">Grade <br>Point</th>
																		<th rowspan="2">LG</th>
																		<th style="width: 50px" rowspan="2">GPA</th>
																	</tr>
																	<tr>
																		<th><?= $s3sRedux['cqtitle'] ?></th>
																		<th><?= $s3sRedux['mcqtitle'] ?></th>
																		<th><?= $s3sRedux['prctitle'] ?></th>
																		<?php if($total_subCa > 0){?>
    																		<th>Sub Total</th>
    																		<th><?=$convertPercent?>%</th>														
    																		<th><?= $s3sRedux['catitle'] ?> </th>
																		<?php }?>
																	</tr>
																</thead>

																<tbody style="position: relative; line-height: 1; font-size: 13px">
																	<?php

																	

																		$totalobtain = $allsubjTotal = 0;
																	
																		
																		$totalgrade = $totalgpa = $totalmerit = 'N/A';

																		$studentPoint = $wpdb->get_results( "SELECT * FROM `ct_studentPoint`	WHERE spStdID = $stdnt AND spYear = '$year' AND spClass = $class AND spExam = $exam LIMIT 1" );
																		if($studentPoint){
																			$meritPosition = $studentPoint[0]->spPosition;
																			$classPosition = $studentPoint[0]->spClassPosition;
																			if($studentPoint[0]->spFaild == 0){
																				$totalgpa = $studentPoint[0]->spPoint;

																				$totalgrade = "A+";

																				if 		($totalgpa < 1.0){ $totalgrade = 'F'; }
																				elseif($totalgpa < 2.0){ $totalgrade = 'D'; } 
																				elseif($totalgpa < 3.0){ $totalgrade = 'C'; } 
																				elseif($totalgpa < 3.5){ $totalgrade = 'B'; } 
																				elseif($totalgpa < 4.0){ $totalgrade = 'A-'; }
																				elseif($totalgpa < 5.0){ $totalgrade = 'A'; }
																			}else{
																				$totalgrade = 'F';
																				$totalgpa = '0.00';
																			}

																		}else{
																			$meritPosition = $classPosition = 'Result Not Publish';
																		}
																		/*==================
																			For Combine mark
																		====================*/

																		$combines = $wpdb->get_results("SELECT * FROM `ct_result`
																			LEFT JOIN ct_subject ON ct_result.resSubject = ct_subject.subjectid
																			LEFT JOIN ct_class ON $class = ct_class.classid
																			WHERE resClass = $class AND resExam = $exam AND resultYear = '$year' AND resStdRoll = '$roll' AND resStudentId = $stdnt AND subCombineMark = 1 AND resSub4th = 0 GROUP BY resSubject ORDER BY sub4th,subOptinal,subCode ASC");

																		$results2 = $wpdb->get_results( "SELECT * FROM `ct_result`
																			LEFT JOIN ct_subject ON ct_result.resSubject = ct_subject.subjectid
																			LEFT JOIN ct_class ON $class = ct_class.classid
																			WHERE resClass = $class AND resExam = $exam AND resultYear = '$year' AND resStdRoll = '$roll' AND resStudentId = $stdnt AND (subCombineMark = 0 || (subCombineMark = 1 AND resSub4th = 1)) GROUP BY resSubject ORDER BY resSub4th,subOptinal,subCode ASC" );

																		$optfind = $wpdb->get_results( "SELECT *, subjectName FROM `ct_result`
																			LEFT JOIN ct_subject ON ct_result.resSubject = ct_subject.subjectid
																			WHERE resClass = $class AND resExam = $exam AND resultYear = '$year' AND resStudentId = $stdnt AND resSub4th = 1 GROUP BY resSubject ORDER BY sub4th,subOptinal,subCode ASC" );

																		$assessment = $wpdb->get_results( "SELECT *, subjectName FROM `ct_result`
																			LEFT JOIN ct_subject ON ct_result.resSubject = ct_subject.subjectid
																			WHERE resClass = $class AND resExam = $exam AND resultYear = '$year' AND resStudentId = $stdnt AND assessment = 1 GROUP BY resSubject ORDER BY sub4th,subOptinal,subCode ASC" );

																		$numberOf = sizeof($combines) + sizeof($results2);
																		$gpaNotPrinted = true;
																		$showAssessment = false;

																		$title4th = false;
																		if (sizeof($optfind) > 0) {
																			$numberOf += 1; 
																			$title4th = true;
																		}
																		if (sizeof($assessment) > 0) {
																			$numberOf += 1; 
																			$showAssessment = true;
																		}
																// 		echo '<pre>';print_r($results2);exit;

																		foreach ($combines as $combin) {
																			$absentCk = array();
																			$combineMark = $combin->combineMark;
																			if($combin->subPaper == 1){
																				$havecon = false;
            																	$highest = $wpdb->get_results( "SELECT resCQ, resMCQ, resCa, resPrec, resTotal FROM `ct_result` 	WHERE resClass = $class AND resExam = $exam AND resultYear = '$year' AND resSubject = $combin->subjectid ORDER BY resTotal DESC LIMIT 1" );
                                                                            if($combin->subCa > 0){
																				$subTot1 = (isnum($combin->subMCQ)+isnum($combin->subCQ)+isnum($combin->subPect))*$convertPercent/100+$combin->subCa;
																				$obtain = round(((isnum($combin->resTotal)-isnum($combin->resCa))*$convertPercent/100)+isnum($combin->resCa));
																			}else{
																				$subTot1 = isnum($combin->subMCQ)+isnum($combin->subCQ)+isnum($combin->subPect)+isnum($combin->subCa);
																				$obtain = $combin->resTotal;
																			}
																			$allsubjTotal += $subTot1;
																				$subTot2 = 0;
																				$resCQ = $resMCQ = $resPre = $resCa = $subCQ = $subMCQ = $subPrec = $subCa = 0;
																				foreach ($combines as $combin2) {
																					if($combin2->connecttedPaper == $combin->resSubject){

																						$havecon = true;
																						if($combin2->subCa > 0){
																						$obtain += round(((isnum($combin2->resTotal)-isnum($combin2->resCa))*$convertPercent/100)+isnum($combin2->resCa));

																						$subTot2 = (isnum($combin2->subMCQ)+isnum($combin2->subCQ)+isnum($combin2->subPect))*$convertPercent/100+$combin2->subCa;
																						
                                                                                    	}else{
                                                                                    	    $obtain += isnum($combin2->resTotal)-isnum($combin2->resCa)+isnum($combin2->resCa);

																						$subTot2 = isnum($combin2->subMCQ)+isnum($combin2->subCQ)+isnum($combin2->subPect)+isnum($combin2->subCa);
																						
																						
                                                                                    	}
                                                                                    	$allsubjTotal += $subTot2;
																						$subName = $combin2->subjectName;
																						$subCQ = isnum($combin2->subCQ); 
																						$subMCQ = isnum($combin2->subMCQ);
																						$subPrec = isnum($combin2->subPect);
																						$subCa = isnum($combin2->subCa);
																						$reCQ2 =$resCQ = isnum($combin2->resCQ); 
																						$reMCQ2 =$resMCQ = isnum($combin2->resMCQ);
																						$rePre2 =$resPre = isnum($combin2->resPrec);
																						$reCa2 =$resCa = isnum($combin2->resCa);

																						$absentCk[] = $combin2->resCQ;
																						$absentCk[] = $combin2->resMCQ;
																						$absentCk[] = $combin2->resPrec;
																				// 		$absentCk[] = $combin2->resCa;

																						$highest2 = $wpdb->get_results( "SELECT resCQ, resMCQ, resCa, resPrec, resTotal FROM `ct_result` 	WHERE resClass = $class AND resExam = $exam AND resultYear = '$year' AND resSubject = $combin2->subjectid ORDER BY resTotal DESC LIMIT 1" );
																						
																						break;
																					}
																				}

																				$resCQ += isnum($combin->resCQ);
																				$resMCQ += isnum($combin->resMCQ);
																				$resPre += isnum($combin->resPrec);
																				$resCa += isnum($combin->resCa);

																				$absentCk[] = $combin->resCQ;
																				$absentCk[] = $combin->resMCQ;
																				$absentCk[] = $combin->resPrec;
																				// $absentCk[] = $combin->resCa;

																				$subCQ += isnum($combin->subCQ);
																				$subMCQ += isnum($combin->subMCQ);
																				$subPrec += isnum($combin->subPect);
																				$subCa += isnum($combin->subCa);
																				$combTotal = 0;

																				if(in_array('a', $absentCk) || in_array('A', $absentCk)){ 
																					$combinegrade = 'Ab';
																					$conbinePoint = '0.00';
																				}else{
																				    if($combin->subCa > 0){
																					$genRes = genPointWithPercent($subCQ,$subMCQ,$subPrec,$subCa,$resCQ,$resMCQ,$resPre,$resCa,$combineMark);
																				    }else{
																					$genRes = genPoint($subCQ,$subMCQ,$subPrec,$subCa,$resCQ,$resMCQ,$resPre,$resCa,$combineMark);
																				    }
																					$combinegrade = $genRes['grade'];
																					$conbinePoint = $genRes['point'];
																				}


																				if($combin2->resSub4th == 1){
																					$totalobtain += ($obtain > ((($subTot1+$subTot2)/100)*40) ) ? @$obtain-((@$obtain/100)*40) : 0 ;
																				}else{
																					$totalobtain += $obtain;
																				}
																				?>
																					<tr>
																						<td><?= $combin->subjectName; ?></td>
																						<td><?= $subTot1 ?></td>
																						
																						<td><?= $combin->resCQ ?></td>
																						<td><?= $combin->resMCQ ?></td>
																						<td><?= $combin->resPrec ?></td>
																						<?php if($combin->subCa > 0){?>
    																						<td><?= ((isnum($combin->resCQ) + isnum($combin->resMCQ) + isnum($combin->resPrec))) ?></td>
    																						<?php if($combin->subCa > 0){?>
    																						<td><?= round((isnum($combin->resCQ) + isnum($combin->resMCQ) + isnum($combin->resPrec))*$convertPercent/100) ?></td>
    																						<?php }else{?>
    																						<td></td>
    																						<?php }?>
    																						<td><?= $combin->resCa ?></td>
																						<?php }?>
																						<td <?= ($havecon) ? 'rowspan="2"' : ''; ?>><?= $obtain ?></td>
																						<?php if($combin->subCa > 0){?>
																						<td><?= round(((isnum($highest[0]->resCQ) + isnum($highest[0]->resMCQ) + isnum($highest[0]->resPrec))*$convertPercent/100) + isnum($highest[0]->resCa))  ?></td>
																						<?php }else{?>
																						<td><?= (isnum($highest[0]->resCQ) + isnum($highest[0]->resMCQ) + isnum($highest[0]->resPrec) + isnum($highest[0]->resCa))  ?></td>
																						<?php }?>
																						<td <?= ($havecon) ? 'rowspan="2"' : ''; ?>><?= $conbinePoint ?></td>
																						<td <?= ($havecon) ? 'rowspan="2"' : ''; ?>><?= $combinegrade ?></td>
																						<?php if($gpaNotPrinted){ ?>
																							<td rowspan="<?= $numberOf + 6?>">
																								<?= $totalgrade ?><br>
																								(<?= $totalgpa ?>)
																							</td>
																						<?php } $gpaNotPrinted = false; ?>
																					</tr>
																				<?php

																				if($havecon){ ?>
																						<!-- 2nd Paper -->
																						<tr>
																							<td><?= $subName; ?></td>
																							<td><?= $subTot2; ?></td>
																							
																							<td><?= $reCQ2; ?></td>
																							<td><?= $reMCQ2; ?></td>
																							<td><?= $rePre2; ?></td>
																							<?php if($total_subCa > 0){?>
        																							<td><?= ((isnum($reCQ2) + isnum($reMCQ2) + isnum($rePre2))) ?></td>	
        																							<?php if($combin->subCa > 0){?>
        																							<td><?= ((isnum($reCQ2) + isnum($reMCQ2) + isnum($rePre2))*$convertPercent/100) ?></td>							
        																						<?php }else{?>
        																							<td></td>							
        																						<?php }?>
        																							<td><?= $reCa2; ?></td>	
																							<?php }?>
																						<?php if($combin->subCa > 0){?>
													                                            <td><?= isset($highest2) 	?  round((isnum($highest2[0]->resCQ) + isnum($highest2[0]->resMCQ) + isnum($highest2[0]->resPrec))*$convertPercent/100 + isnum($highest2[0]->resCa))  : ''; ?></td>									</tr>
																						<?php }else{?>
													                                        <td><?= isset($highest2) 	?  (isnum($highest2[0]->resCQ) + isnum($highest2[0]->resMCQ) + isnum($highest2[0]->resPrec) + isnum($highest2[0]->resCa))  : ''; ?></td>									</tr>
																						<?php }?>
																						
																					<?php
																				}
																			}
																		}

																		/*Combine mark end*/
																		
// echo '<pre>';print_r($results2);exit;
																		foreach ($results2 as $key => $result2) {
																		    
																		    if($result2->subCombineMark == 1){
																		        $combin = $result2;
																		        $absentCk = array();
																			$combineMark = $combin->subCombineMark;
																			if($combin->subPaper == 1){
																				$havecon = false;
            																	$highest = $wpdb->get_results( "SELECT resCQ, resMCQ, resCa, resPrec, resTotal FROM `ct_result` 	WHERE resClass = $class AND resExam = $exam AND resultYear = '$year' AND resSubject = $combin->subjectid ORDER BY resTotal DESC LIMIT 1" );
                                                                            if($combin->subCa > 0){
																				$subTot1 = (isnum($combin->subMCQ)+isnum($combin->subCQ)+isnum($combin->subPect))*$convertPercent/100+$combin->subCa;
																				$obtain = round(((isnum($combin->resTotal)-isnum($combin->resCa))*$convertPercent/100)+isnum($combin->resCa));
																			}else{
																				$subTot1 = isnum($combin->subMCQ)+isnum($combin->subCQ)+isnum($combin->subPect)+isnum($combin->subCa);
																				$obtain = $combin->resTotal;
																			}
																			$allsubjTotal += $subTot1;
																				$subTot2 = 0;
																				$resCQ = $resMCQ = $resPre = $resCa = $subCQ = $subMCQ = $subPrec = $subCa = 0;
																				foreach ($results2 as $combin2) {
																					if($combin2->connecttedPaper == $combin->resSubject){

																						$havecon = true;
																						if($combin2->subCa > 0){
																						$obtain += round(((isnum($combin2->resTotal)-isnum($combin2->resCa))*$convertPercent/100)+isnum($combin2->resCa));

																						$subTot2 = (isnum($combin2->subMCQ)+isnum($combin2->subCQ)+isnum($combin2->subPect))*$convertPercent/100+$combin2->subCa;
																						
                                                                                    	}else{
                                                                                    	    $obtain += isnum($combin2->resTotal)-isnum($combin2->resCa)+isnum($combin2->resCa);

																						$subTot2 = isnum($combin2->subMCQ)+isnum($combin2->subCQ)+isnum($combin2->subPect)+isnum($combin2->subCa);
																						
																						
                                                                                    	}
                                                                                    	$allsubjTotal += $subTot2;
																						$subName = $combin2->subjectName;
																						$subCQ = isnum($combin2->subCQ); 
																						$subMCQ = isnum($combin2->subMCQ);
																						$subPrec = isnum($combin2->subPect);
																						$subCa = isnum($combin2->subCa);
																						$reCQ2 =$resCQ = isnum($combin2->resCQ); 
																						$reMCQ2 =$resMCQ = isnum($combin2->resMCQ);
																						$rePre2 =$resPre = isnum($combin2->resPrec);
																						$reCa2 =$resCa = isnum($combin2->resCa);

																						$absentCk[] = $combin2->resCQ;
																						$absentCk[] = $combin2->resMCQ;
																						$absentCk[] = $combin2->resPrec;
																				// 		$absentCk[] = $combin2->resCa;

																						$highest2 = $wpdb->get_results( "SELECT resCQ, resMCQ, resCa, resPrec, resTotal FROM `ct_result` 	WHERE resClass = $class AND resExam = $exam AND resultYear = '$year' AND resSubject = $combin2->subjectid ORDER BY resTotal DESC LIMIT 1" );
																						
																						break;
																					}
																				}

																				$resCQ += isnum($combin->resCQ);
																				$resMCQ += isnum($combin->resMCQ);
																				$resPre += isnum($combin->resPrec);
																				$resCa += isnum($combin->resCa);

																				$absentCk[] = $combin->resCQ;
																				$absentCk[] = $combin->resMCQ;
																				$absentCk[] = $combin->resPrec;
																				// $absentCk[] = $combin->resCa;
																				
																				$sub4th = $combin->resSub4th;

																				$subCQ += isnum($combin->subCQ);
																				$subMCQ += isnum($combin->subMCQ);
																				$subPrec += isnum($combin->subPect);
																				$subCa += isnum($combin->subCa);
																				$combTotal = 0;

																				if(in_array('a', $absentCk) || in_array('A', $absentCk)){ 
																					$combinegrade = 'Ab';
																					$conbinePoint = '0.00';
																				}else{
																				    if($combin->subCa > 0){
																					$genRes = genPointWithPercent($subCQ,$subMCQ,$subPrec,$subCa,$resCQ,$resMCQ,$resPre,$resCa,$combineMark);
																				    }else{
																					$genRes = genPoint($subCQ,$subMCQ,$subPrec,$subCa,$resCQ,$resMCQ,$resPre,$resCa,$combineMark);
																				    }
																					$combinegrade = $genRes['grade'];
																					$conbinePoint = $genRes['point'];
																				}


																				if($combin2->resSub4th == 1 || $combin->resSub4th == 1){
																					$subjTotalComb = $subTot1 + $subTot2;
                                                                    				$totalobtain += ($obtain > (($subjTotalComb/100)*40) ) ? $obtain-(($subjTotalComb/100)*40) : 0 ;
																				}else{
																					$totalobtain += $obtain;
																				}
																					if ($title4th && $sub4th == 1) {
																				$title4th = false;
																				?>
																				<tr class="text-left">
																					<th colspan="9">
																						<div class="text-left" style="background:rgba(238, 238, 238, .5);padding: 3px 10px;">
																							Optional Subject
																						</div>
																					</th>
																				</tr>
																				<?php
																			}
																				?>
																					<tr>
																						<td><?= $combin->subjectName; ?></td>
																						<td><?= $subTot1 ?></td>
																						
																						<td><?= $combin->resCQ ?></td>
																						<td><?= $combin->resMCQ ?></td>
																						<td><?= $combin->resPrec ?></td>
																						<?php if($total_subCa > 0){?>
    																						<td><?= ((isnum($combin->resCQ) + isnum($combin->resMCQ) + isnum($combin->resPrec))) ?></td>
    																						<?php if($combin->subCa > 0){?>
    																						<td><?= round((isnum($combin->resCQ) + isnum($combin->resMCQ) + isnum($combin->resPrec))*$convertPercent/100) ?></td>
    																						<?php }else{?>
    																						<td></td>
    																						<?php }?>
    																						<td><?= $combin->resCa ?></td>
																						<?php }?>
																						<td <?= ($havecon) ? 'rowspan="2"' : ''; ?>><?= $obtain ?></td>
																						<?php if($combin->subCa > 0){?>
																						<td><?= round(((isnum($highest[0]->resCQ) + isnum($highest[0]->resMCQ) + isnum($highest[0]->resPrec))*$convertPercent/100) + isnum($highest[0]->resCa))  ?></td>
																						<?php }else{?>
																						<td><?= (isnum($highest[0]->resCQ) + isnum($highest[0]->resMCQ) + isnum($highest[0]->resPrec) + isnum($highest[0]->resCa))  ?></td>
																						<?php }?>
																						<td <?= ($havecon) ? 'rowspan="2"' : ''; ?>><?= $conbinePoint ?></td>
																						<td <?= ($havecon) ? 'rowspan="2"' : ''; ?>><?= $combinegrade ?></td>
																						<?php if($gpaNotPrinted){ ?>
																							<td rowspan="<?= $numberOf + 6?>">
																								<?= $totalgrade ?><br>
																								(<?= $totalgpa ?>)
																							</td>
																						<?php } $gpaNotPrinted = false; ?>
																					</tr>
																				<?php

																				if($havecon){ ?>
																						<!-- 2nd Paper -->
																						<tr>
																							<td><?= $subName; ?></td>
																							<td><?= $subTot2; ?></td>
																							
																							<td><?= $reCQ2; ?></td>
																							<td><?= $reMCQ2; ?></td>
																							<td><?= $rePre2; ?></td>
																							<?php if($total_subCa > 0){?>
        																							<td><?= ((isnum($reCQ2) + isnum($reMCQ2) + isnum($rePre2))) ?></td>	
        																							<?php if($combin->subCa > 0){?>
        																							<td><?= ((isnum($reCQ2) + isnum($reMCQ2) + isnum($rePre2))*$convertPercent/100) ?></td>							
        																						<?php }else{?>
        																							<td></td>							
        																						<?php }?>
        																							<td><?= $reCa2; ?></td>	
																							<?php }?>
																						<?php if($combin->subCa > 0){?>
													                                            <td><?= isset($highest2) 	?  round((isnum($highest2[0]->resCQ) + isnum($highest2[0]->resMCQ) + isnum($highest2[0]->resPrec))*$convertPercent/100 + isnum($highest2[0]->resCa))  : ''; ?></td>									</tr>
																						<?php }else{?>
													                                        <td><?= isset($highest2) 	?  (isnum($highest2[0]->resCQ) + isnum($highest2[0]->resMCQ) + isnum($highest2[0]->resPrec) + isnum($highest2[0]->resCa))  : ''; ?></td>									</tr>
																						<?php }?>
																						
																					<?php
																				}
																			}
																		    }
																		    else
																		    {
																		        // wihout combine
																			$absentCk = array();
																			$absentCk[] = $result2->resCQ;
																			$absentCk[] = $result2->resMCQ;
																			$absentCk[] = $result2->resPrec;
																// 			$absentCk[] = $result2->resCa;
																			$sub4th = $result2->resSub4th;
																			if($result2->subCa > 0){
    																			$resTotal = round((isnum($result2->resCQ) + isnum($result2->resMCQ) + isnum($result2->resPrec))*$convertPercent/100+isnum($result2->resCa));
    																			$subjTotal =  ($result2->subMCQ + $result2->subCQ + $result2->subPect)*$convertPercent/100+ $result2->subCa;
                                                                            }else{
                                                                                $resTotal = isnum($result2->resCQ) + isnum($result2->resMCQ) + isnum($result2->resPrec)+isnum($result2->resCa);
    																			$subjTotal =  $result2->subMCQ + $result2->subCQ + $result2->subPect+ $result2->subCa;
                                                                            }
																			if(in_array('a', $absentCk) || in_array('A', $absentCk)){ 
																				$grade = 'Ab';
																				$point = '0.00';
																			}else{
																			    if($result2->subCa > 0){
																				    $genRes = genPointWithPercent($result2->subCQ,$result2->subMCQ,$result2->subPect,$result2->subCa,$result2->resCQ,$result2->resMCQ,$result2->resPrec,$result2->resCa,$result2->combineMark);
																				
																				}else{
       																				$genRes = genPoint($result2->subCQ,$result2->subMCQ,$result2->subPect,$result2->subCa,$result2->resCQ,$result2->resMCQ,$result2->resPrec,$result2->resCa,$result2->combineMark);
 
																				}
																				$grade = $genRes['grade'];
																				$point = $genRes['point'];
																			}
																			
																			if($sub4th == 1){
																				$totalobtain += ($resTotal > (($subjTotal/100)*40) ) ? $resTotal-(($subjTotal/100)*40) : 0 ;
																			}else{
																				$totalobtain += $resTotal;
																			}


																			$allsubjTotal += $subjTotal;

																			$highest = $wpdb->get_results( "SELECT resCQ, resMCQ, resCa, resPrec,  resTotal FROM `ct_result` 	WHERE resClass = $class AND resExam = $exam AND resultYear = '$year' AND resSubject = $result2->subjectid ORDER BY resTotal DESC LIMIT 1" );

																			if ($title4th && $sub4th == 1) {
																				$title4th = false;
																				?>
																				<tr class="text-left">
																					<th colspan="9">
																						<div class="text-left" style="background:rgba(238, 238, 238, .5);padding: 3px 10px;">
																							Optional Subject
																						</div>
																					</th>
																				</tr>
																				<?php
																			}
																			?>
																				<tr>
																					<td>
																						<?= $result2->subjectName; ?>
																					</td>
																					<td><?= $subjTotal ?></td>
																					
																					<td><?= $result2->resCQ ?></td>
																					<td><?= $result2->resMCQ ?></td>
																					<td><?= $result2->resPrec ?></td>
																					<?php if($total_subCa > 0){?>
    																					<td><?= ((isnum($result2->resCQ) + isnum($result2->resMCQ) + isnum($result2->resPrec))) ?></td>
    																					<?php if($result2->subCa > 0){?>
    																					<td><?= round((isnum($result2->resCQ) + isnum($result2->resMCQ) + isnum($result2->resPrec))*$convertPercent/100) ?></td>
    																					<?php }else{?>
    																					<td></td>
    																					<?php }?>
    																					<td><?= $result2->resCa ?></td>
																					<?php }?>
																					<td><?= $resTotal ?></td>
																					<?php if($result2->subCa > 0){?>
																					<td><?= round((isnum($highest[0]->resCQ) + isnum($highest[0]->resMCQ) + isnum($highest[0]->resPrec))*$convertPercent/100 + isnum($highest[0]->resCa) ) ?></td>
																					<?php }else{?>
																					<td><?= isnum($highest[0]->resCQ) + isnum($highest[0]->resMCQ) + isnum($highest[0]->resPrec) + isnum($highest[0]->resCa)  ?></td>
																					<?php }?>
																					<td><?= $point ?></td>
																					<td><?= $grade ?></td>
																					
																					<?php if($gpaNotPrinted){ ?>
																						<td rowspan="<?= $numberOf ?>">
																							<?= $totalgrade ?><br>
																							(<?= $totalgpa ?>)
																						</td>
																					<?php } $gpaNotPrinted = false; ?>	
																				</tr>
																			<?php
																		}
																		}
																		if ($showAssessment) {
																			$showAssessment = false;
																			$resTotal = $assessment[0]->resTotal;
																			$subjTotal =  $assessment[0]->subMCQ + $assessment[0]->subCQ + $assessment[0]->subPect + $assessment[0]->subCa;

																			if(in_array('a', $absentCk) || in_array('A', $absentCk)){ 
																				$grade = 'Ab';
																				$point = '0.00';
																			}else{
																			    if($assessment[0]->subCa){
																				    $genRes = genPointWithPercent($assessment[0]->subCQ,$assessment[0]->subMCQ,$assessment[0]->subPect,$assessment[0]->subCa,$assessment[0]->resCQ,$assessment[0]->resMCQ,$assessment[0]->resPrec,$assessment[0]->resCa,$assessment[0]->subCombineMark);
																			    }else{
       																				$genRes = genPoint($assessment[0]->subCQ,$assessment[0]->subMCQ,$assessment[0]->subPect,$assessment[0]->subCa,$assessment[0]->resCQ,$assessment[0]->resMCQ,$assessment[0]->resPrec,$assessment[0]->resCa,$assessment[0]->subCombineMark);
 
																			    }
																				$grade = $genRes['grade'];
																				$point = $genRes['point'];
																			}
																			
																			
																				$totalobtain += $resTotal;
																			


																			$allsubjTotal += $subjTotal;

																			$highest = $wpdb->get_results( "SELECT MAX(`resTotal`) as max FROM `ct_result`	WHERE resClass = $class AND resExam = $exam AND resultYear = '$year' AND resSubject = ".$assessment[0]->subjectid );

																			?>
																			<tr class="text-left">
																				<th colspan="10">
																					<div class="text-left" style="background:rgba(238, 238, 238, .5);padding: 3px 10px;">
																						<?= $assessment[0]->subjectName?>
																					</div>
																				</th>
																			</tr>
																			<tr style="background:rgba(238, 238, 238, .5)">
																							<th>Name </th>
																							<th>Marks</th>
																							<th>Highest Marks</th>
																							<th colspan="4">Obtain Marks</th>
																							<th>Total</th>
																							<th>Grade Point</th>
																							<th>LG</th>
																					
																			</tr>
																			<tr>
																			<?php
																				$highest = $wpdb->get_results( "SELECT MAX(`resMCQ`) as max FROM `ct_result`	WHERE resClass = $class AND resExam = $exam AND resultYear = '$year' AND resSubject = ".$assessment[0]->subjectid );
																			?>
																				<td>Attendence</td>
																				<td><?= $assessment[0]->subMCQ?></td>
																				<td><?= $highest[0]->max?></td>
																				<td colspan="4"><?= $assessment[0]->resMCQ?></td>
																				<td rowspan="4"><?= $resTotal?></td>
																				<td rowspan="4"><?= $point?></td>
																				<td rowspan="4"><?= $grade?></td>
																			</tr>
																			<tr>
																			<?php
																				$highest = $wpdb->get_results( "SELECT MAX(`resCQ`) as max FROM `ct_result`	WHERE resClass = $class AND resExam = $exam AND resultYear = '$year' AND resSubject = ".$assessment[0]->subjectid );
																			?>
																				<td>Hand Writing</td>
																				<td><?= $assessment[0]->subCQ?></td>
																				<td><?= $highest[0]->max?></td>
																				<td colspan="4"><?= $assessment[0]->resCQ?></td>
																			</tr>
																			<tr>
																			<?php
																				$highest = $wpdb->get_results( "SELECT MAX(`resPrec`) as max FROM `ct_result`	WHERE resClass = $class AND resExam = $exam AND resultYear = '$year' AND resSubject = ".$assessment[0]->subjectid );
																			?>
																				<td>Neat & Clean</td>
																				<td><?= $assessment[0]->subPect?></td>
																				<td><?= $highest[0]->max?></td>
																				<td colspan="4"><?= $assessment[0]->resPrec?></td>
																			</tr>
																			<tr>
																			<?php
																				$highest = $wpdb->get_results( "SELECT MAX(`resCa`) as max FROM `ct_result`	WHERE resClass = $class AND resExam = $exam AND resultYear = '$year' AND resSubject = ".$assessment[0]->subjectid );
																			?>
																				<td>Uniform</td>
																				<td><?= $assessment[0]->subCa?></td>
																				<td><?= $highest[0]->max?></td>
																				<td colspan="4"><?= $assessment[0]->resCa?></td>
																			</tr>
																			<?php
																		}
																	?>
																</tbody>

																<tfoot style="background:rgba(238, 238, 238, .5)">
																	<tr>
																		<th colspan="3">Total Marks: <?= $allsubjTotal ?> </th>
																			<?php if($total_subCa > 0){?>
																		<th colspan="6">Obtain Marks: <?= $totalobtain ?></th>
																			<?php }else {?>
																		<th colspan="3">Obtain Marks: <?= $totalobtain ?></th>
																			<?php }?>
																		<th colspan="4">Merit Position: <?= ($totalgrade == 'F') ? 'Fail' : $meritPosition ?></th>
																	</tr>
																</tfoot>
															</table>
														

															<br>
															<table style="border:0 !important;width: 100%">
																<tr>
																	<td style="width: 49%;border:0 !important;">
																		<table style="width: 100%">
																			<tr>
																				<td rowspan="2" style="padding: 0 10px;width: 50%;">Position</td>
																				<td style="padding: 0 10px;">Class</td>
																				<td style="padding: 0 10px;">Section</td>
																			</tr>
																			<tr>
																				<td style="padding: 0 10px;"><?= $classPosition ?></td>
																				<td style="padding: 0 10px;"><?= $meritPosition ?></td>
																			</tr>
																			<tr>
																				<td style="padding: 0 10px;">GPA</td>
																				<td colspan="2" style="padding: 0 10px;"><?= $totalgrade ?>(<?= number_format((float)$totalgpa, 2, '.', ''); ?>)</td>
																			</tr>
																			<tr>
																				<td style="padding: 0 10px;">Fail subject(s)</td>
																				<td colspan="2" style="padding: 0 10px;"></td>
																			</tr>
																			<tr>
																				<td style="padding: 0 10px;">Working Days</td>
																				<td colspan="2" style="padding: 0 10px;"><b class='editable'><span>...</span></b></td>
																			</tr>
																			<tr>
																				<td style="padding: 0 10px;">Total present</td>
																				<td colspan="2" style="padding: 0 10px;"><b class='editable'><span>...</span></b></td>
																			</tr>
																			<tr>
																				<td colspan="3" style="padding: 0 10px;height: 60px;">Remark:</td>
													
																			</tr>
																		</table>
																	</td>
																	<td style="width: 49%;border:0 !important;text-align: right;">
																		<?php
																			$sSect = $student->sectionid;
																			$sRoll = $student->infoRoll;
																			$resurl = home_url()."/result/?class=$class&sections=$sSect&syear=$year&exam=$exam&roll=$sRoll";
																		?>
																		<img width="150" src="https://qrickit.com/api/qr.php?d=<?= urlencode($resurl) ?>"> 
																	</td>
																</tr>
															</table>
															<table style="border:0 !important;width: 100%;margin-top: 10px;">
																<tr>
																	<td style="width: 49%;border:0 !important;vertical-align: bottom;">
																		<div style="width: 200px;text-align: center;">
																			<hr style="margin: 10px 0 0">
																			Class teacher signature
																		</div>
																	</td>
																	<td style="width: 49%;border:0 !important;">
																		<div style="width: 200px; float: right;text-align: center;">
																			<img align="Principal_Signature" style="display: block; margin: 20px auto 0;" width="100" src="<?= $s3sRedux['principalSign']['url'] ?>">
																			<hr style="margin: 10px 0 0">
																			<?= $s3sRedux['inst_head_title'] ?> signature
																		</div>
																		
																	</td>
																</tr>
															</table>

															<div style="clear: both;"></div>

											  			<div class="section4" style="margin:5px;">
																<div class="secLeft">
																	<i style="font-size: 10px;color: #888;">Generated by Bornomala, Developed by MS3 Technology BD, Urmi-43, Shibgonj, Sylhet. Email: bornomalaems@gmail.com</i>
																</div>
															</div>
											  		</div>
													</div>
												</div>

						  				<?php
						  			}
					  			}
					  		}else{
					  			echo "<h3 class='text-center'>Student Not Found</h3>";
					  		}
							?>

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
	    $('.editable').on('click', 'span', function(){
			$this = $(this);
			$this.closest('.editable').html("").append("<input type='text' value='"+$this.text()+"'><p class='closeEdit'>x</p>");
		});



 		$('.editable').on('focusout', 'input', function(){
 			$this = $(this);
			$this.closest('.editable').html("<span>"+$this.val()+"</span>");
 		});
 		
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
    w.document.write('<scr' + 'ipt type="text/javascript">' + 'window.onload = function() { window.print(); };' + '</sc' + 'ript>');
    w.document.close(); // necessary for IE >= 10
    w.focus(); // necessary for IE >= 10
    return true;
  }
</script>