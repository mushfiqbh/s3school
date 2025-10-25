<?php
global $wpdb; global $s3sRedux; 

if (isset($_GET['roll'])) {

	$stdnt  = $_GET['stdnt'];
	$roll 	= $_GET['roll'];
	$class 	= $_GET['class'];
	$exam 	= $_GET['exam'];
	$year 	= $_GET['syear'];

	

	$results = $wpdb->get_results( "SELECT status,className,resultId,subjectName,combineMark,resMCQ,resCQ,resPrec,resCa,subMCQ,subCQ,subPect,subCa,resTotal,resSub4th FROM `ct_result`
		LEFT JOIN ct_subject ON ct_result.resSubject = ct_subject.subjectid
		LEFT JOIN ct_class ON ct_result.resClass = ct_class.classid
		WHERE resClass = $class AND resExam = $exam AND resultYear = '$year' AND resStudentId = $stdnt ORDER BY subjectName ASC" );


	if (!empty($results)) { 
		?>

		<!-- if Not Publish -->
		<?php if ( intval($results[0]->status) == 0) { ?>
			<h3>Roll: <?= $roll ?>, Class: <?= $results[0]->className ?>, Year: <?= $year ?></h3>

			<table class="table table-bordered">
				<tr>
					<th>Subject</th>
					<th><?= $s3sRedux['cqtitle'] ?></th>
					<th><?= $s3sRedux['mcqtitle'] ?></th>
					<th><?= $s3sRedux['prctitle'] ?></th>
					<th><?= $s3sRedux['catitle'] ?></th>
					<th>Total Mark</th>
					<th>Result</th>
					<th>Action</th>
				</tr>
				<?php

				foreach ($results as $result) {
					$sub4th = $result->resSub4th;
					$cls = '';

					$genRes = genPoint($result->subCQ,$result->subMCQ,$result->subPect,$result->subCa,$result->resCQ,$result->resMCQ,$result->resPrec,$result->resCa, $result->combineMark);

					if($genRes['grade'] == "F")
						$cls = 'bg-danger';
					?>

						<tr class="<?= $cls ?>">
							<td><?= $result->subjectName ?> <?= ($sub4th == 1) ? '(4th Sub)' : '' ?></td>
							<td><?= $result->resCQ ?></td>
							<td><?= $result->resMCQ ?></td>
							<td><?= $result->resPrec ?></td>
							<td><?= $result->resCa ?></td>
							<td><?= $genRes['total']; ?></td>
							<td><?= $genRes['grade'];  ?></td>
							<td>
								
								<ul class="list-unstyled list-inline" style="margin: 0">
									<li>
										<a href="?page=result&view=edit&res=<?= $result->resultId ?>"><span class="dashicons dashicons-welcome-write-blog"></span></a>
									</li>
									<li>
										<form action="" method="POST" class="actionForm">
											<input type="hidden" name="id" value="<?= $result->resultId ?>">
											<button type="submit" name="deleteResult" class="btn-link btnDelete" ><span class="dashicons dashicons-trash"></span></button>						
										</form>
										
									</li>
								</ul>
								
							</td>
						</tr>
					<?php
				}
				?>
			</table>
		<?php }else{ ?>
			<h3 class="alert alert-info">Result Already published, Edit Not Possible</h3>
		<?php } ?>
 
		<div class="transcript" style="width: 794px">
			<style type="text/css">
				.section1 table,.section2 table,.section3 table{width:100%}.mainTable,.section1,.section3 h3,.section3 table th{text-align:center}.transcript{border:3px solid #84c4fc;padding:15px}.section1{margin-bottom:30px}.section1 .item,.section2 .item{display:table-cell;vertical-align:top;width:100%}.item.mid h3{margin-top:0}.section3 table,.section3 td,.section3 th{border:1px solid #000}.gradingTable td,.gradingTable th,.gradingTable tr{font-size:11px;min-width:55px;text-align:center;border:1px solid #333}.mainTable td,.mainTable tfoot th{padding:5px}
			</style>

			<div class="section1">
				<table>
					<tr>
						<td style="width: 140px;">
							<div class="item left">
								<?php
									$qry = "SELECT stdImg,stdName,infoRoll,stdFather,stdMother,infoYear,className,groupName,sectionName,infoOptionals,info4thSub FROM ct_student
									LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid AND $class = ct_studentinfo.infoClass AND '$year' = ct_studentinfo.infoYear
									LEFT JOIN ct_class ON ct_studentinfo.infoClass = ct_class.classid
									LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId
									LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
									WHERE studentid = $stdnt LIMIT 1";
									$student = $wpdb->get_results( $qry );

								?>
								<?php if ($student[0]->stdImg == ""){ ?>
									<img width="100" src="<?= get_template_directory_uri(); ?>/img/No_Image.jpg">
								<?php }else{ ?>
									<img width="100" src="<?= $student[0]->stdImg ?>">
								<?php } ?>
							</div>
						</td>
						<td>
							<div class="">
								<h3><?= $s3sRedux['institute_name'] ?></h3>
								<p><?= $s3sRedux['institute_address'] ?></p>
								<img width="50" src="<?= $s3sRedux['instLogo']['url'] ?>">
								<h4>Academic Transcript</h4>
							</div>
						</td>
						<td style="width: 140px;">
							<div class="item right">
								<table class="gradingTable">
									<tr style="background: #eee">
										<th>Range</th>
										<th>Grade</th>
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
										<th><?= $student->stdName ?></th>
									</tr>
									<tr>
										<td>Father Name</td>
										<td style="padding: 0 5px;"> : </td>
										<th><?= $student->stdFather ?></th>
									</tr>
									<tr>
										<td>Mother Name</td>
										<td style="padding: 0 5px;"> : </td>
										<th><?= $student->stdMother ?></th>
									</tr>
									<tr>
										<td>Group</td>
										<td style="padding: 0 5px;"> : </td>
										<th><?= $student->groupName ?></th>
									</tr>
									
								</table>
							</td>
							<td>
								<table>
									<tr>
										<td style="width: 110px">Class</td>
										<td style="padding: 0 5px;"> : </td>
										<th><?= $student->className ?></th>
									</tr>
									<tr>
										<td>Section</td>
										<td style="padding: 0 5px;"> : </td>
										<th><?= $student->sectionName ?></th>
									</tr>
									<tr>
										<td>Roll</td>
										<td style="padding: 0 5px;"> : </td>
										<th><?= $student->infoRoll ?></th>
									</tr>
									<tr>
										<td>Year/Session</td>
										<td style="padding: 0 5px;"> : </td>
										<th><?= $student->infoYear ?></th>
									</tr>
								</table>
							</td>
						</tr>
					</table>
				</div>

			<?php } ?>


			<div class="section3">
				<h3>
					<?php
						$exams = $wpdb->get_results( "SELECT examName FROM ct_exam WHERE examid = $exam LIMIT 1" );
						foreach ($exams as $exam) {
							echo $exam->examName;
						}
					?>
				</h3>
				<table class=" mainTable">
					<thead style="background: #eee">
						<tr>
							<th rowspan="2">Course name</th>
							<th rowspan="2">Marks</th>
							<th rowspan="2">Highest Marks</th>
							<th colspan="5">Obtain Marks</th>
							
							<th rowspan="2">Points</th>
							<th rowspan="2">Grade</th>
							<th style="width: 50px" rowspan="2">GPA</th>
						</tr>
						<tr>
							<th><?= $s3sRedux['cqtitle'] ?></th>
							<th><?= $s3sRedux['mcqtitle'] ?></th>
							<th><?= $s3sRedux['prctitle'] ?></th>
							<th><?= $s3sRedux['catitle'] ?></th>
							<th>Total</th>
						</tr>
					</thead>

					<tbody style="position: relative;">
						<?php
							$roll 	= $_GET['roll'];
							$class 	= $_GET['class'];
							$exam 	= $_GET['exam'];
							$year 	= $_GET['syear'];
							$stdnt  = $_GET['stdnt'];

							$totalobtain = $allsubjTotal = $classPosition = $secPosition = 0;
						
							
							$totalgrade = $totalgpa = $totalmerit = 'N/A';

							$studentPoint = $wpdb->get_results( "SELECT * FROM `ct_studentPoint`	WHERE spStdID = $stdnt AND spYear = '$year' AND 	spClass = $class AND spExam = $exam LIMIT 1" );
							if($studentPoint){
								if($studentPoint[0]->spFaild == 0){
									$totalgpa = $studentPoint[0]->spPoint;
									$meritPosition = $studentPoint[0]->spPosition;

									$totalgrade = "A+";

									if 		($totalgpa < 1){ $totalgrade = 'F'; }
									elseif($totalgpa < 2){ $totalgrade = 'D'; } 
									elseif($totalgpa < 3){ $totalgrade = 'C'; } 
									elseif($totalgpa < 3.5){ $totalgrade = 'B'; } 
									elseif($totalgpa < 4){ $totalgrade = 'A-'; } 
									elseif($totalgpa < 5){ $totalgrade = 'A'; }
								}else{
									$totalgrade = 'F';
									$totalgpa = '0.00';
									$meritPosition = 'Fail';
								}
								$classPosition = $studentPoint[0]->spPosition;
								$secPosition = $studentPoint[0]->spClassPosition;

							}else{
								$meritPosition = 'Result Not Publish';
							}
							/*==================
								For Combine mark
							====================*/
							$optionals = implode (", ",json_decode($student->infoOptionals));
							$fourth = $student->info4thSub;
							$optionals = ($optionals == 0) ? $optionals.",".$fourth : $optionals; 

							$combines = $wpdb->get_results("SELECT * FROM `ct_subject`
								LEFT JOIN ct_result ON resSubject = subjectid AND resExam = $exam AND resClass = $class AND resStudentId = $stdnt
								LEFT JOIN ct_class ON $class = ct_class.classid
								WHERE (subjectClass = $class AND subOptinal = 0 AND sub4th = 0 AND subCombineMark = 1) OR subjectid IN ($optionals) ORDER BY sub4th,subOptinal,subCode ASC");


							$results2 = $wpdb->get_results( "SELECT * FROM `ct_subject`
								LEFT JOIN ct_result ON resSubject = subjectid AND resExam = $exam AND resClass = $class AND resStudentId = $stdnt
								LEFT JOIN ct_class ON $class = ct_class.classid
								WHERE (subjectClass = $class AND subOptinal = 0 AND sub4th = 0 AND subCombineMark = 0) OR subjectid IN ($optionals) ORDER BY sub4th,subOptinal,subCode ASC" );

							$numberOf = sizeof($combines) + sizeof($results2);
							$gpaNotPrinted = true;

							foreach ($combines as $combin) {
								$combineMark = $combin->combineMark;
								$absentCk = array();
								if($combin->subPaper == 1){
									$havecon = false;

									$subTot1 = $combin->subMCQ+$combin->subCQ+$combin->subPect;
									$allsubjTotal += $subTot1;
									$highest = $wpdb->get_results( "SELECT MAX(`resTotal`) as max FROM `ct_result`	WHERE resClass = $class AND resExam = $exam AND resultYear = '$year' AND resSubject = ".$combin->subjectid );
								
									$obtain = $combin->resTotal;
									$subTot2 = 0;
									$resCQ = $resMCQ = $resPre = $resCa = $subCQ = $subMCQ = $subPrec = $subCa = 0;
									foreach ($combines as $combin2) {
										if($combin2->connecttedPaper == $combin->resSubject){

											$havecon = true;
											$obtain += $combin2->resTotal;

											$subTot2 = isnum($combin2->subMCQ)+isnum($combin2->subCQ)+isnum($combin2->subPect);
											
											$allsubjTotal += $subTot2;

											$subName = $combin2->subjectName;
											$subCQ = $combin2->subCQ; 
											$subMCQ = $combin2->subMCQ;
											$subPrec = $combin2->subPect;
											$reCQ2 = $combin2->resCQ;
											$reMCQ2 = $combin2->resMCQ;
											$rePre2 = $combin2->resPrec;
											$reCa2 = $combin2->resCa;
											$resCQ = isnum($combin2->resCQ); 
											$resMCQ = isnum($combin2->resMCQ);
											$resPre = isnum($combin2->resPrec);
											$resCa = isnum($combin2->resCa);

											$absentCk[] = $combin2->resCQ;
											$absentCk[] = $combin2->resMCQ;
											$absentCk[] = $combin2->resPrec;
											$absentCk[] = $combin2->resCa;

											$highest2 = $wpdb->get_results( "SELECT MAX(`resTotal`) as max FROM `ct_result`	WHERE resClass = $class AND resExam = $exam AND resultYear = '$year' AND resSubject = ".$combin2->subjectid );
											
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
									$absentCk[] = $combin->resCa;

									$subCQ += isnum($combin->subCQ);
									$subMCQ += isnum($combin->subMCQ);
									$subPrec += isnum($combin->subPect);
									$subCa += isnum($combin->subCa);
									$combTotal = 0;

									if(in_array('a', $absentCk) || in_array('A', $absentCk)){ 
										$combinegrade = 'Ab';
										$conbinePoint = '0.00';
									}else{
										$genRes = genPoint($subCQ,$subMCQ,$subPrec,$subCa,$resCQ,$resMCQ,$resPre,$resCa,$combineMark);
										$combinegrade = $genRes['grade'];
										$conbinePoint = $genRes['point'];
									}


									if($combin2->resSub4th == 1){
										$totalobtain += ($obtain > ((($subTot1+$subTot2)/100)*40) ) ? $resTotal-(($subjTotal/100)*40) : 0 ;
									}else{
										$totalobtain += $obtain;
									}
									?>
										<tr>
											<td><?= $combin->subjectName; ?></td>
											<td><?= $subTot1 ?></td>
											<td><?= $highest[0]->max ?></td>
											<td><?= ($combin->resCQ > 0) ? $combin->resCQ : '00' ?></td>
											<td><?= ($combin->resMCQ > 0) ? $combin->resMCQ : '00' ?></td>
											<td><?= ($combin->resPrec > 0) ? $combin->resPrec : '00' ?></td>
											<td><?= ($combin->resCa > 0) ? $combin->resCa : '00' ?></td>
											<td <?= ($havecon) ? 'rowspan="2"' : ''; ?>><?= ($obtain > 0) ? $obtain : '00' ?></td>
											<td <?= ($havecon) ? 'rowspan="2"' : ''; ?>><?= $conbinePoint ?></td>
											<td <?= ($havecon) ? 'rowspan="2"' : ''; ?>><?= $combinegrade ?></td>
											<?php if($gpaNotPrinted){ ?>
												<td rowspan="<?= $numberOf ?>">
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
												<td><?= isset($highest2) 	? $highest2[0]->max : ''; ?></td>
												<td><?= ($reCQ2 > 0) ? $reCQ2 : '00' ?></td>
												<td><?= ($reMCQ2 > 0) ? $reMCQ2 : '00' ?></td>
												<td><?= ($rePre2 > 0) ? $rePre2 : '00' ?></td>							
												<td><?= ($reCa2 > 0) ? $reCa2 : '00' ?></td>							
											</tr>
										<?php
									}
								}
							}

							/*Combine mark end*/
							foreach ($results2 as $key => $result2) {
								$absentCk = array();
								$absentCk[] = $result2->resCQ;
								$absentCk[] = $result2->resMCQ;
								$absentCk[] = $result2->resPrec;
								$absentCk[] = $result2->resCa;
								$sub4th = $result2->resSub4th;
								$resTotal = $result2->resTotal;

								$subjTotal =  $result2->subMCQ + $result2->subCQ + $result2->subPect + $result2->subCa;

								if(in_array('a', $absentCk) || in_array('A', $absentCk)){ 
									$grade = 'Ab';
									$point = '0.00';
								}else{
									$genRes = genPoint($result2->subCQ,$result2->subMCQ,$result2->subPect,$result2->subCa,$result2->resCQ,$result2->resMCQ,$result2->resPrec,$result2->resCa,$result2->combineMark);
									$grade = $genRes['grade'];
									$point = $genRes['point'];
								}

								if($sub4th == 1){
									$totalobtain += ($resTotal > (($subjTotal/100)*40) ) ? $resTotal-(($subjTotal/100)*40) : 0 ;
								}else{
									$totalobtain += $resTotal;
								}


								$allsubjTotal += $subjTotal;

								$highest = $wpdb->get_results( "SELECT MAX(`resTotal`) as max FROM `ct_result`	WHERE resClass = $class AND resExam = $exam AND resultYear = '$year' AND resSubject = ".$result2->subjectid );
								?>
									<tr>
										<td>
											<?= $result2->subjectName; ?>
											<?= ($sub4th == 1) ? '(4th Sub)' : '' ?>
										</td>
										<td><?= $subjTotal ?></td>
										<td><?= $highest[0]->max ?></td>
										<td><?= ($result2->resCQ > 0) ? $result2->resCQ : '00' ?></td>
										<td><?= ($result2->resMCQ > 0) ? $result2->resMCQ : '00' ?></td>
										<td><?= ($result2->resPrec > 0) ? $result2->resPrec : '00' ?></td>
										<td><?= ($result2->resCa > 0) ? $result2->resCa : '00' ?></td>
										<td><?= ($resTotal > 0) ? $resTotal : '00' ?></td>
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
						?>
					</tbody>

					<tfoot style="background: #eee">
						<tr>
							<th colspan="3">Total Marks: <?= $allsubjTotal ?> </th>
							<th colspan="3">Obtain Marks: <?= $totalobtain ?></th>
							<th colspan="4">Merit Position: <?= $meritPosition ?></th>
						</tr>
					</tfoot>
				</table>
				<div style="margin-top: 10px">
					<table class="table">
						<tr>
							<td>Section Position: <?= $classPosition ?></td>
							<td>Class Position: <?= $secPosition ?></td>
						</tr>
					</table>
				</div>
			</div>
		</div>

		<?php
	
	}else{
		echo "<h3 class='text-center text-danger'>Nothing Found</h3>";
	}
}