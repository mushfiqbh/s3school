<?php

/**
 * Template Name: Cgpa search (Frontend)
 */
global $s3sRedux;


get_header(); ?>

	<div class="b-page-wrap">
		<div class="b-page-content with-layer-bg">
			<div class="b-layer-big otherPageBg">
				<div class="layer-big-bg page-layer-big-bg">
					<div class="layer-content-big text-center">
						<h2>CGPA Search</h2>
					</div>
				</div>
			</div>
		</div>
	</div>



	<div class="b-layer-main">
		<div class="page-arrow">
			<i class="fa fa-angle-down" aria-hidden="true"></i>
		</div>
		<style>
		    .b-blog-classic img {
		     height:80px;   
		    }
		</style>
		<div class="b-blog-classic">
			<div class="container">
				<div class="row">
					<div class="col-md-12">

						<?php 
						if(!isset($_GET['roll'])){
							//==================
							//	Search
							//==================
							?>

							<div class="b-blog-items-holder wow slideInLeft">
								<div class="clearfix aboutUsPageContent">

									<div class="panel panel-default">
									  <div class="panel-heading">CGPA Search</div>
									  <div class="panel-body">

									  	<form action="" method="GET">
										  	<div class="row">
										  		<div class="col-md-6">
										  			<div class="form-group">
															<label>Class: *</label>
															<select id="classck" class="form-control" name="class" required>
																<option disabled selected>Select Class </option>
							                  <?php															
																$classes = $wpdb->get_results("SELECT classid,className FROM ct_class WHERE classid IN (SELECT DISTINCT stdAdmitClass FROM ct_student)");
																foreach ($classes as $class) {
																	?>
									                <option value='<?= $class->classid ?>'>
									                  <?= $class->className ?>
									                </option>
									                <?php
																}
																?>
							                </select>
														</div>
										  		</div>
										  		<div class="col-md-6">
										  			<div class="form-group">
															<label>Section:</label>
															<select id="secSear" name="sections" class="form-control" required>
																<option disabled selected>Select class first </option>
															</select>
														</div>
										  		</div>

										  		<div class="col-md-6">
										  			<div class="form-group">
															<label>Group: </label>
															<select name="group" class="form-control">
																<option disabled selected>Select Group </option>
																<?php
											            $groups = $wpdb->get_results("SELECT * FROM ct_group");
											            foreach ($groups as $groups) {
											            	$selected = '';
											            	if (isset($edit)) {
											              	$selected = ($edit->infoGroup == $groups->groupId) ? 'selected' : '';
											            	}
											              ?>
											              <option value='<?= $groups->groupId ?>' <?= $selected ?>>
											                <?= $groups->groupName ?>
											              </option>
											              <?php
											            }
											          ?>
															</select>
														</div>
										  		</div>

										  		<div class="col-md-6">
										  			<div class="form-group">
															<label>Academic Year: *</label>
															<select id="syear" name="syear" class="form-control" required>
															<option disabled selected>Select class first </option>
															</select>
														</div>
										  		</div>

										  		<!--<div class="col-md-6">-->
										  		<!--	<div class="form-group">-->
														<!--	<label>Exam: *</label>-->
														<!--	<select id="exam" name="exam" class="form-control" required>-->
														<!--	<option disabled selected>Select class first </option>-->
														<!--	</select>-->
														<!--</div>-->
										  		<!--</div>-->

										  		<div class="col-md-6">
										  			<div class="form-group">
															<label>Roll No:</label>
															<input type="text" name="roll" class="form-control">
														</div>
										  		</div>

										  		<div class="col-md-12">
										  			<div class="form-group">
															<button type="submit" class="btn btn-secondary pull-right">Search</button>
														</div>
										  		</div>

										  	</div>
											</form>

									  </div>
									</div>
								</div>
							</div>

							<?php 
						}else{

							//====================
							//	Result View
							//====================

							$class 			= $_GET['class'];
							$year  			= $_GET['syear'];
				// 			$exam  			= $_GET['exam'];
							$stdRoll    = $_GET['roll'];
							
                			$sec 		= isset($_GET['sections']) 	? $_GET['sections'] 	: '';
                			$grou 	= isset($_GET['grou'])  ? $_GET['grou'] : '';

							$haveRes = $wpdb->get_results("SELECT * FROM `ct_result` WHERE resClass = $class AND resultYear = '$year' AND resStdRoll = $stdRoll AND status = 1");
							if(sizeof($haveRes) > 0){

								?>
									<div>
										<a class="btn btn-info" href="<?php home_url('cgpa-search') ?>?search=search">Back To Search</a>
										<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>
									</div><br>
									<div id="printArea" style="">
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
			  WHERE studentid IN (SELECT spStdID FROM ct_studentPoint WHERE spClass = $class AND spYear = '$year' AND infoRoll = $stdRoll GROUP BY spStdID)";

			  
			$stdQuery .= ($grou != '') ? " AND infoGroup = $grou" : '';
			$stdQuery .= ($sec != '')  ? " AND infoSection = $sec" : '';
			$stdQuery .= " GROUP BY studentid, infoRoll ORDER BY infoRoll,sectionName";
			$students  = $wpdb->get_results( $stdQuery );
			echo "Total:". sizeof($students);
			function emptyRow($key,$subKey){
				echo "<tr>";
    		if($key == 0){ 
	    		echo "<td>$subKey</td><td></td>";
				}
				echo "<td><span style='color: transparent;'>0</span></td><td></td><td></td><td></td><td></td><td></td>
				<td></td><td></td></tr>";
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
							    	<table style="width: 100%;margin-top: 2px;text-align: center;">
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
							    	<table style="width: 100%;margin-top: 2px;">
											<tbody>
												<tr style="background: #4472C4;-webkit-print-color-adjust: exact;color: #fff">
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
															<th colspan="<?= $key == 0 ? 10 : 8; ?>" style="background: #a1dcff;text-align: center;color: #000">
																<?= $exam->examName ?>
															</th>
														</tr>

											    	<tr>
															<?php if($key == 0 || $subNameNotPrint){ $subNameNotPrint = false; ?>
												    		<td class="thstyle">SL. No.</td>
																<th>Subject Name</th>
															<?php } ?>
															<td class="thstyle"><div class="virticalText singel"><?= $s3sRedux['cqtitle'] ?></div></td>
															<td class="thstyle"><div class="virticalText singel"><?= $s3sRedux['mcqtitle'] ?></div></td>
															<td class="thstyle"><div class="virticalText singel"><?= $s3sRedux['prctitle'] ?></div></td>
															<td class="thstyle"><div class="virticalText singel"><?= $s3sRedux['catitle'] ?></div></td>
															<td class="thstyle"><div class="virticalText">Total<br>Mark</div></td>
															<td class="thstyle"><div class="virticalText">Grade<br>point</div></td>
															<td class="thstyle"><div class="virticalText">Latter<br>Grade</div></td>
															<td class="thstyle"><div class="virticalText">Highest<br>Mark</div></td>
														</tr>
														<?php foreach ($subNames as $subKey => $subNa) {

															$subCQ		= $subNa->subCQ;
															$subMCQ		= $subNa->subMCQ;
															$subPect	= $subNa->subPect;
															$subCa		= $subNa->subCa;
															$stdCQ		= $subNa->resCQ;
															$stdMCQ		= $subNa->resMCQ;
															$stdPrec	= $subNa->resPrec;
															$stdCa		= $subNa->resCa;
															$combine	= $subNa->combineMark;
															$genFun = genPoint($subCQ,$subMCQ,$subPect,$subCa,$stdCQ,$stdMCQ,$stdPrec,$stdCa,$combine);
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
																<td class="textCenter"><?= $subNa->resCa ?></td>
																<?php if($subNa->subPaper == 1 && $info[0]->combineMark){ ?>
																	<?php
																		$resCQTemp1   = $subNa->resCQ;
																		$resMCQTemp1  = $subNa->resMCQ;
																		$resPrecTemp1 = $subNa->resPrec;
																		$resCaTemp1 = $subNa->resCa;
																		if($subNames[$subKey + 1]->connecttedPaper == $subNa->subjectid){
																			$resCQTemp2 	= $subNames[$subKey + 1]->resCQ;
																			$resMCQTemp2 	= $subNames[$subKey + 1]->resMCQ;
																			$resPrecTemp2 = $subNames[$subKey + 1]->resPrec;
																			$resCaTemp2 = $subNames[$subKey + 1]->resCa;
																		}else{
																			foreach ($subNames as $subNa2) {
																				if($subNa2->connecttedPaper == $subNa->subjectid){
																					$resCQTemp2 	= $subNa2->resCQ;
																					$resMCQTemp2 	= $subNa2->resMCQ;
																					$resPrecTemp2	= $subNa2->resPrec;
																					$resCaTemp2	= $subNa2->resCa;
																					break;
																				}
																			}
																		}
																		$cqtmp  = (isnum($resCQTemp1)+isnum($resCQTemp2))/2;
																		$mcqtmp = (isnum($resMCQTemp1)+isnum($resMCQTemp2))/2;
																		$pretmp = (isnum($resPrecTemp1)+isnum($resPrecTemp2))/2;
																		$catmp = (isnum($resCaTemp1)+isnum($resCaTemp2))/2;
																		$combine = 
																		$genTemp = genPoint($subNa->subCQ,$subNa->subMCQ,$subNa->subPect,$subNa->subCa,$cqtmp,$mcqtmp,$pretmp,$catmp,$info[0]->combineMark);
																		$resTotalTemp = isnum($resCQTemp1) + isnum($resMCQTemp1) + isnum($resPrecTemp1) + isnum($resCaTemp1) + isnum($resCQTemp2) + isnum($resMCQTemp2) + isnum($resPrecTemp2) + isnum($resCaTemp2);
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
															<th colspan="<?= $key == 0 ? 7 : 5; ?>" style='font-size: 12px'>
																Obtain <?= $exam->spTotalMark ?> out of <?= $exam->spSubjTotal ?>
															</th>
															<th><?= $exam->spFaild == 0 ? $exam->spPoint : '0.00' ?> </th>
															<th><?= $exam->spFaild == 0 ? pointToGrade($exam->spPoint) : 'F' ?> </th>
															<th></th>
														</tr>
														<tr style="background: #d9edf7;">
															<th style="text-align: center;" colspan="<?= $key == 0 ? 10 : 8; ?>">Position - Class=<?= $exam->spClassPosition ?></th>
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
												<!--<th>Section</th>-->
											</tr>
											<tr>
												<th>C.G.P.A</th>
												<td><?= $student->cgpaFaild == 0 ? $student->cgpaPoint : '0.00'; ?></td>
												<th rowspan="2"><h3 style="text-align: center;margin: 0"><?= $student->cgpaPosition ?></h3></th>
												<!--<th rowspan="2"><h3 style="text-align: center;margin: 0"><?= $student->cgpaPosition; ?></h3></th>-->
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
											<img style="margin:0 1px;" src="<?= $s3sRedux['principalSign']['url'] ?>" width="100" align="Principal_Signature">
											<p>Principal Signature</p>
										</div>
									</td>
								</tr>
								<?php if (sizeof($subNames) < 11) { ?>
									<tr>
										<td class="noborder" colspan="4" style="color: #999 ">
											<small>Generated  by Bornomala, Developed  by MS3 Technology  BD Pvt. Ltd, Haque Tower, Tilagor, Sylhet. Email: bornomala.ems@gmail.com</small>
											<small style="float: right;">Copy right <?= home_url() ?></small>
										</td>
									</tr>
								<?php } ?>
							</table>
						</div>
					<?php
				}
			}
		
		?>
	</div>
	<!-- progress report ends-->

								<?php
							}else{
								echo "<h3 class='alert alert-info text-center'>Result not published yet</h3>";
							}
						} ?>
					</div>
				</div>
			</div>
		</div>
	</div>

<?php get_footer(); ?>

<script type="text/javascript">
	(function($) {
		$url = "<?= get_template_directory_uri() ?>/inc/ajaxAction.php";
		$('#classck').change(function() {
			$.ajax({
		    url: $url,
		    method: "POST",
		    data: { class : $(this).val(), type : 'getSection' },
		    dataType: "html"
		  }).done(function( msg ) {
		    $( "#secSear" ).html( msg );
		  });

		  $.ajax({
	      url: $url,
	      method: "POST",
	      data: { class : $(this).val(), type : 'getYears' },
	      dataType: "html"
	    }).done(function( msg ) {
	      $( "#syear" ).html( msg );
	    });

	    $.ajax({
	      url: $url,
	      method: "POST",
	      data: { class : $(this).val(), type : 'getExams' },
	      dataType: "html"
	    }).done(function( msg ) {
	      $( "#exam" ).html( msg );
	    });
	    
		});
	})( jQuery );
	function print(divId) {
    var printContents = document.getElementById(divId).innerHTML;
    w = window.open();
    w.document.write(printContents);
    w.document.write('<scr' + 'ipt type="text/javascript">' + 'window.onload = function() { window.print(); window.close(); };' + '</sc' + 'ript>');
    w.document.close();
    w.focus();
    return true;
  }

</script>