<?php 
/**
* Template Name: Admin TabulationSheet 2
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
				Tabulation Sheet 2<br>
				<small>Find Out students Tabulation Sheet</small>
			</h3>
		</div>
		<div class="panel-body">
			<form action="" method="GET" class="form-inline">

				<div class="form-group">
					<input type="hidden" name="page" value="tabulation_sheet2">
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

	<!-- Tabulation View -->
	<div class="text-right">
  	<button onclick="print('printArea')" class="btn btn-primary">Print</button>
  </div>
	<div id="printArea">
		<?php 
		if(isset($_GET['syear']) && isset($_GET['class'])){ ?>
			<style type="text/css">
				.detTbl tr td,.detTbl tr th{ border: 1px solid #000; padding: 5px; }
				.tbTable tr td,.tbTable tr th{ border: 1px solid #000; padding:1px; line-height: 1; text-align: center; font-size: 11px; }
				/*.rotate90 {	transform: rotate(-45deg); }*/
				@media print {
		  		@page  { size: auto; margin: 30px !important;} 
		  		table { page-break-inside: avoid !important; }
				}
			</style>
			<link rel="stylesheet" href="<?= get_template_directory_uri() ?>/css/tabulationSheet.css" />
			<div style="text-align: center; position: relative;">
				<img height="80px" style="position: absolute;left: 10px;top: 10px" src="<?= $s3sRedux['instLogo']['url'] ?>">
				<h2 style="margin: 5px 0 5px 0;"><b><?= $s3sRedux['institute_name'] ?></b></h2>
	  		<p style="color:#2b5591; font-size: 14px; margin: 0;"><?= $s3sRedux['institute_address'] ?></p>
	  		<h3>Tabulation sheet</h3>
			</div>

			<br>

			<?php

			$year 	= $_GET['syear']; 
			$class 	= $_GET['class'];
			$sec 		= isset($_GET['sec']) 	? $_GET['sec'] 	: '';
			$grou 	= isset($_GET['grou'])  ? $_GET['grou'] : '';
			$exam 	= isset($_GET['exam'])  ? $_GET['exam'] : '';
			
			$subjects = json_decode($wpdb->get_var("SELECT examSubjects FROM ct_exam WHERE examid = $exam AND examClass = $class"), true);
            $ids = implode(',', array_map('intval', (array)$subjects));
                                
            $total_subCa = $ids ? $wpdb->get_var("SELECT IFNULL(SUM(subCa), 0) FROM ct_subject WHERE subjectid IN ($ids)") : 0;


			$quey = "SELECT className,havecgpa";

  			if($grou != '' ){ $quey .= ",groupName"; }
  			if($sec != '' ){  $quey .= ",sectionName"; }

				$quey .= " FROM ct_class LEFT JOIN ct_exam ON ct_exam.examid = $exam";

				if($grou != ''){ $quey .= " LEFT JOIN ct_group ON ct_group.groupId = $grou"; }
  			if($sec != ''){ $quey .= " LEFT JOIN ct_section ON ct_section.sectionid = $sec"; }
					
			$quey .= " WHERE classid = $class";

			$info = $wpdb->get_results( $quey );
			$havecgpa = $info[0]->havecgpa;
			?>

  		<table style="width: 100%">
  			<tr style="background: #4472C4;print-color-adjust: exact; -webkit-print-color-adjust: exact;color: #fff">
  				<td><b>Class :</b></td>
  				<td><?= $info[0]->className ?></td>
  				<td><b>Year/Section :</b></td>
  				<td><?= $year ?></td>
  			</tr>
  			<tr style="background: #D9E2F3;print-color-adjust: exact; -webkit-print-color-adjust: exact;">
  				<td><b>Section :</b></td>
  				<td><?= ($sec != '') ? $info[0]->sectionName : '' ?></td>
  				<td><b>Group :</b></td>
  				<td><?= ($grou != '') ? $info[0]->groupName : '' ?></td>
  			</tr>
  		</table>
  		<br>
		<?php

			$stdQuery = "SELECT studentid,stdName,stdFather,infoRoll,sectionName,cgpaPoint FROM `ct_student`
			 	LEFT JOIN ct_studentinfo ON ct_studentinfo.infoStdid = ct_student.studentid AND ct_studentinfo.infoYear = '$year' AND ct_studentinfo.infoClass = $class
			 	LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
			 	LEFT JOIN ct_cgpa ON ct_cgpa.cgpaClass = ct_studentinfo.infoClass AND ct_cgpa.cgpaStudent = ct_student.studentid AND ct_cgpa.cgpaYear = ct_studentinfo.infoYear
			  WHERE studentid IN (SELECT spStdID FROM ct_studentPoint WHERE spClass = $class AND spYear = '$year' GROUP BY spStdID)";

			  
			$stdQuery .= ($grou != '') ? " AND infoGroup = $grou" : '';
			$stdQuery .= ($sec != '')  ? " AND infoSection = $sec" : '';
			$stdQuery .= " ORDER BY infoRoll,sectionName";
			$students  = $wpdb->get_results( $stdQuery );

			if($students){

				/*=========== Student loop ==============*/
				foreach ($students as $student) {
					$std = $student->studentid;
					?>
					<table class="tbTable" width="100%" style="margin-bottom: 5px;">
					  <tbody>
					    <?php 
					    $exams = $wpdb->get_results("SELECT spExam,examName,spFaild,spPoint,spPosition FROM `ct_studentPoint`
					      		LEFT JOIN ct_exam ON ct_exam.examid = $exam
					      		WHERE spClass = $class AND spYear = '$year' AND spExam = $exam AND spStdID = $std GROUP BY spExam");
					      		
								// $exams = $wpdb->get_results("SELECT spExam,examName,spFaild,spPoint,spPosition FROM `ct_studentPoint`
					   //   		LEFT JOIN ct_exam ON ct_exam.examid = ct_studentPoint.spExam
					   //   		WHERE spClass = $class AND spYear = '$year' AND spStdID = $std GROUP BY spExam");
								// $totalExam = sizeof($exams);
					    ?>
					    <?php //foreach ($exams as $key => $exam) { ?>
					    	<?php
					    		$subExam = $exam;
					      	$subNames1 = $wpdb->get_results("SELECT * FROM `ct_result`
										LEFT JOIN ct_subject ON ct_result.resSubject = ct_subject.subjectid
										LEFT JOIN ct_class ON $class = ct_class.classid
										WHERE resClass = $class AND resExam = $subExam AND resultYear = '$year' AND resStudentId = $std AND subCombineMark = 1 GROUP BY resSubject ORDER BY sub4th,subOptinal,subCode,subjectName ASC");
					      	$subNames2 = $wpdb->get_results( "SELECT * FROM `ct_result`
										LEFT JOIN ct_subject ON ct_result.resSubject = ct_subject.subjectid
										LEFT JOIN ct_class ON $class = ct_class.classid
										WHERE resClass = $class AND resExam = $subExam AND resultYear = '$year' AND resStudentId = $std AND  subCombineMark = 0 GROUP BY resSubject ORDER BY sub4th,subOptinal,subCode,subjectName ASC" );

					      	//if($key == 0){
					    	?>
					    		<tr >
					    			<th colspan='30' width="10%" style="color: blue;padding: 3px 0;background: #dad0ff">Student's Name : <?= $student->stdName ?> </th>
					    		</tr>
						    	<tr style="background: #fff2cc;print-color-adjust: exact; -webkit-print-color-adjust: exact;">
							      <th width="3%">Roll</th>

							      <!--<th width="5%">Exam Name</th>-->
							      <th width="6%">Obtain Marks </th>

							      <?php
											foreach ($subNames1 as $combin) {
												if($combin->connecttedPaper == 0){
													$havecon = false;
													echo "<th colspan='2'>".$combin->subjectName."</th>";
													foreach ($subNames1 as $combin2) {
														if($combin2->connecttedPaper == $combin->resSubject){
															echo "<th colspan='2'>".$combin2->subjectName."</th>";
														}
													}
												}
											}
											foreach ($subNames2 as $subj) {
												echo "<th colspan='2'>".$subj->subjectName."</th>";
											}
							      ?>
							      <th width="3%">Position<br>GPA</th>
							      <?php if($havecgpa){ ?>
							      	<th width="3%">CGPA</th>
							      <?php } ?>
							    </tr>
					    	<?php //} ?>
						    <tr>
						      <th rowspan="6" width="3%">
						      	<?= $student->infoRoll ?><br>(<?= $student->sectionName ?>)
						      </th>
						      
						      <!--<td rowspan="6" width="5%">-->
						      <!--	<div class="rotate90"><?php //$exam->examName ?></div>-->
						      <!--</td>-->
						      <td width="6%"><?= $s3sRedux['cqtitle'] ?></td>

						      <?php 
						      	foreach ($subNames1 as $combin) {
											if($combin->connecttedPaper == 0){
												?>
												<td width="2%"><?= $combin->resCQ ?></td>
												<?php if($combin->resCa > 0){ ?>
        									        <td rowspan="5" width="2%"><?= round(((isnum($combin->resTotal)-isnum($combin->resCa))*$convertPercent/100)+isnum($combin->resCa)) ?></td>
        									      <?php }else{ ?>
        									        <td rowspan="3" width="2%"><?= $combin->resTotal ?></td>
        									      <?php } ?>
									      <?php
												foreach ($subNames1 as $combin2) {
													if($combin2->connecttedPaper == $combin->resSubject){
														?>
														<td width="2%"><?= $combin2->resCQ ?></td>
														<?php if($combin2->resCa > 0){ ?>
											                    <td rowspan="5" width="2%"><?= round(((isnum($combin2->resTotal)-isnum($combin2->resCa))*$convertPercent/100) +isnum($combin2->resCa))?></td>
											             <?php }else{ ?>
            									                 <td rowspan="3" width="2%"><?= $combin2->resTotal ?></td>
            									      <?php } ?>
											      <?php
													}
												}
											}
										}
										foreach ($subNames2 as $subj) {
											?>
											<td width="2%"><?= $subj->resCQ ?></td>
											<?php if($subj->resCa > 0){ ?>
								      <td rowspan="5" width="2%"><?= round(((isnum($subj->resTotal)-isnum($subj->resCa))*$convertPercent/100) +isnum($subj->resCa))?></td>
								      <?php }else{ ?>
            									                 <td rowspan="3" width="2%"><?= $subj->resTotal ?></td>
            									      <?php } ?>
								      <?php
										}
						      ?>

						      <td rowspan="6" width="3%">(<?= $exams[0]->spPosition ?>)<br><?= $exams[0]->spFaild == 0 ? $exams[0]->spPoint : '<span style="color:red"><b>F('. $exams[0]->spFaild.')</b></span><br>'.$exams[0]->spPoint  ?></td>
						      <?php if($havecgpa){ ?>
						      	<td rowspan="6" width="3%"><?= $student->cgpaPoint ?></td>
						      <?php } ?>
						    </tr>
						    <tr>
						      <td width="6%"><?= $s3sRedux['mcqtitle'] ?></td>

						      <?php
						      	foreach ($subNames1 as $combin) {
											if($combin->connecttedPaper == 0){
												$havecon = false;
												?> <td width="2%"><?= $combin->resMCQ ?></td> <?php
												foreach ($subNames1 as $combin2) {
													if($combin2->connecttedPaper == $combin->resSubject){
														echo '<td width="2%">'. $combin2->resMCQ .'</td>';
													}
												}
											}
										}
										foreach ($subNames2 as $subj) {
											echo '<td width="2%">'. $subj->resMCQ .'</td>';
										}
						      ?>
						    </tr>
						    <tr>
						      <td width="6%"><?= $s3sRedux['prctitle'] ?></td>

						      <?php 
						      	foreach ($subNames1 as $combin) {
											if($combin->connecttedPaper == 0){
												echo '<td width="2%">'. $combin->resPrec .'</td>';
												foreach ($subNames1 as $combin2) {
													if($combin2->connecttedPaper == $combin->resSubject){
														echo '<td width="2%">'. $combin2->resPrec .'</td>';
													}
												}
											}
										}
										foreach ($subNames2 as $subj) {
											echo '<td width="2%">'. $subj->resPrec .'</td>';
										}
						      ?>
						    </tr>
						    <?php if($total_subCa >0){?>
                <tr>
                  <td width="6%"><?= $convertPercent?>% </td>

                  <?php
                  foreach ($subNames1 as $combin) {
                    if($combin->connecttedPaper == 0){
                        if($combin->resCa > 0){
                      echo '<td width="2%">'. round(((isnum($combin->resCQ) + isnum($combin->resMCQ) + isnum($combin->resPrec))*$convertPercent/100)) .'</td>';
                        }else{
                            echo '<td width="2%"></td>';
                        }
                      foreach ($subNames1 as $combin2) {
                        if($combin2->connecttedPaper == $combin->resSubject){
                            if($combin2->resCa > 0){
                          echo '<td width="2%">'. round(((isnum($combin2->resCQ) + isnum($combin2->resMCQ) + isnum($combin2->resPrec))*$convertPercent/100)) .'</td>';
                            }else{
                            echo '<td width="2%"></td>';
                        }
                        }
                      }
                    }
                  }
                  foreach ($subNames2 as $subj) {
                      if($subj->resCa > 0){
                    echo '<td width="2%">'. round(((isnum($subj->resCQ) + isnum($subj->resMCQ) + isnum($subj->resPrec))*$convertPercent/100)).'</td>';
                      }else{
                            echo '<td width="2%"></td>';
                        }
                  }
                  ?>
                </tr>
                <tr>
                  <td width="6%"><?= $s3sRedux['catitle'] ?></td>

                  <?php
                  foreach ($subNames1 as $combin) {
                    if($combin->connecttedPaper == 0){
                      echo '<td width="2%">'. $combin->resCa .'</td>';
                      foreach ($subNames1 as $combin2) {
                        if($combin2->connecttedPaper == $combin->resSubject){
                          echo '<td width="2%">'. $combin2->resCa .'</td>';
                        }
                      }
                    }
                  }
                  foreach ($subNames2 as $subj) {
                    echo '<td width="2%">'. $subj->resCa .'</td>';
                  }
                  ?>
                </tr>
                 <?php }?>
						    <tr>
						      <td width="6%">Total</td>
						      <?php 
						      	foreach ($subNames1 as $combin) {
						      		$resTotal = 0;
											if($combin->connecttedPaper == 0){
							      		$absentCk = array();
							      		$absentCk[] = $combin->resCQ;
												$absentCk[] = $combin->resMCQ;
												$absentCk[] = $combin->resPrec;
												// $absentCk[] = $combin->resCa;
												$tCalSpan = 2;
												foreach ($subNames1 as $combin2) {
													if($combin2->connecttedPaper == $combin->resSubject){
													    if($combin2->resCa > 0){
														$resTotal += round(((isnum($combin2->resTotal)-isnum($combin2->resCa))*$convertPercent/100)+isnum($combin2->resCa));
													    }else{
													        $resTotal += isnum($combin2->resTotal);
													    }
														$tCalSpan = 4;
											      break;
													}
												}

												if(in_array('a', $absentCk) || in_array('A', $absentCk)){ 
													$info1['grade'] = 'Ab';
													$info1['point'] = '';
												}else{
													$subCQ		= (isnum($combin->subCQ) 	 + isnum($combin2->subCQ)) 	 ;
													$subMCQ		= (isnum($combin->subMCQ)	 + isnum($combin2->subMCQ))  ;
													$subPect	= (isnum($combin->subPect) + isnum($combin2->subPect)) ;
                                                    $subCa	    = (isnum($combin->subCa) + isnum($combin2->subCa)) ;
													$resCQ 		= (isnum($combin->resCQ) 	 + isnum($combin2->resCQ)) 	 ;
													$resMCQ 	= (isnum($combin->resMCQ)	 + isnum($combin2->resMCQ))  ;
													$resPrec 	= (isnum($combin->resPrec) + isnum($combin2->resPrec)) ;
													$resCa 	= (isnum($combin->resCa) + isnum($combin2->resCa)) ;
													if($combin2->subCa > 0){
													$info1 = genPointWithPercent($subCQ,$subMCQ,$subPect,$subCa,$resCQ,$resMCQ,$resPrec,$resCa,$combin->combineMark);
													}else{
													$info1 = genPoint($subCQ,$subMCQ,$subPect,$subCa,$resCQ,$resMCQ,$resPrec,$resCa,$combin->combineMark);
													}
													$info1['point'] = ",".$info1['point'];
												}
												if($combin2->subCa>0){
												$resTotal += round(((isnum($combin->resTotal)-isnum($combin->resCa))*$convertPercent/100)+isnum($combin->resCa));
												}else{
												    $resTotal += isnum($combin->resTotal);
												}

												echo "<td colspan='$tCalSpan'>$resTotal (".$info1['grade'].$info1['point'].")</td>";
												
											}
										}
										foreach ($subNames2 as $subj) {
											$absentCk = array();
						      		$absentCk[] = $subj->resCQ;
											$absentCk[] = $subj->resMCQ;
											$absentCk[] = $subj->resPrec;
								// 			$absentCk[] = $subj->resCa;
											if(in_array('a', $absentCk) || in_array('A', $absentCk)){
												$info['grade'] = 'Ab';
												$info['point'] = '';
											}else{
											    if($subj->subCa>0){
												$info = genPointWithPercent($subj->subCQ,$subj->subMCQ,$subj->subPect,$subj->subCa,$subj->resCQ,$subj->resMCQ,$subj->resPrec,$subj->resCa,$subj->combineMark);
											    }else{
												$info = genPoint($subj->subCQ,$subj->subMCQ,$subj->subPect,$subj->subCa,$subj->resCQ,$subj->resMCQ,$subj->resPrec,$subj->resCa,$subj->combineMark);
											    }
												$info['point'] = ",".$info['point'];
											}
											if($subj->subCa>0){
											    echo '<td colspan="2">'. round((isnum($subj->resTotal)-isnum($subj->resCa))*$convertPercent/100+isnum($subj->resCa)) .' ('.$info['grade'].$info['point'].')</td>';			
											}else{
                                                echo '<td colspan="2">'. $subj->resTotal .' ('.$info['grade'].$info['point'].')</td>';			

											}
											    
											}
						      ?>
						    </tr>
						  
						  <?php //} ?>
					  </tbody>
					</table>
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