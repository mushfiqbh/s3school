<?php 
/**
* Template Name: Admin TabulationSheet 2
*/
global $wpdb,$s3sRedux; 

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

	<!-- Tabulation View -->
	<div class="text-right">
  	<button onclick="print('printArea')" class="btn btn-primary">Print</button>
  </div>
	<div id="printArea" style="">
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

			$quey = "SELECT className,havecgpa";

  			if($grou != '' ){ $quey .= ",groupName"; }
  			if($sec != '' ){  $quey .= ",sectionName"; }

				$quey .= " FROM ct_class";

				if($grou != ''){ $quey .= " LEFT JOIN ct_group ON ct_group.groupId = $grou"; }
  			if($sec != ''){ $quey .= " LEFT JOIN ct_section ON ct_section.sectionid = $sec"; }
					
			$quey .= " WHERE classid = $class";

			$info = $wpdb->get_results( $quey );
			$havecgpa = $info[0]->havecgpa;
			?>

  		<table style="width: 100%">
  			<tr style="background: #4472C4;-webkit-print-color-adjust: exact;color: #fff">
  				<td><b>Class :</b></td>
  				<td><?= $info[0]->className ?></td>
  				<td><b>Year/Section :</b></td>
  				<td><?= $year ?></td>
  			</tr>
  			<tr style="background: #D9E2F3;-webkit-print-color-adjust: exact;">
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
			$stdQuery .= " GROUP BY studentid ORDER BY infoRoll,sectionName";
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
					      		LEFT JOIN ct_exam ON ct_exam.examid = ct_studentPoint.spExam
					      		WHERE spClass = $class AND spYear = '$year' AND spStdID = $std GROUP BY spExam");
								$totalExam = sizeof($exams);
					    ?>
					    <?php foreach ($exams as $key => $exam) { ?>
					    	<?php
					    		$subExam = $exam->spExam;
					      	$subNames1 = $wpdb->get_results("SELECT * FROM `ct_result`
										LEFT JOIN ct_subject ON ct_result.resSubject = ct_subject.subjectid
										LEFT JOIN ct_class ON $class = ct_class.classid
										WHERE resClass = $class AND resExam = $subExam AND resultYear = '$year' AND resStudentId = $std AND subCombineMark = 1 GROUP BY resSubject ORDER BY sub4th,subOptinal,subCode,subjectName ASC");
					      	$subNames2 = $wpdb->get_results( "SELECT * FROM `ct_result`
										LEFT JOIN ct_subject ON ct_result.resSubject = ct_subject.subjectid
										LEFT JOIN ct_class ON $class = ct_class.classid
										WHERE resClass = $class AND resExam = $subExam AND resultYear = '$year' AND resStudentId = $std AND  subCombineMark = 0 GROUP BY resSubject ORDER BY sub4th,subOptinal,subCode,subjectName ASC" );

					      	if($key == 0){
					    	?>
					    		<tr >
					    			<th colspan='29' width="10%" style="color: blue;padding: 3px 0;background: #dad0ff">Student's Name : <?= $student->stdName ?> <span style="padding: 0 8px;">-</span> Father's Name : <?= $student->stdFather ?></th>
					    		</tr>
						    	<tr style="background: #fff2cc;-webkit-print-color-adjust: exact;">
							      <th width="3%">Roll</th>

							      <th width="5%">Exam Name</th>
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
					    	<?php } ?>
						    <tr>
						      <th rowspan="5" width="3%">
						      	<?= $student->infoRoll ?><br>(<?= $student->sectionName ?>)
						      </th>
						      
						      <td rowspan="5" width="5%">
						      	<div class="rotate90"><?= $exam->examName ?></div>
						      </td>
						      <td width="6%"><?= $s3sRedux['cqtitle'] ?></td>

						      <?php 
						      	foreach ($subNames1 as $combin) {
											if($combin->connecttedPaper == 0){
												?>
												<td width="2%"><?= $combin->resCQ ?></td>
									      <td rowspan="4" width="2%"><?= $combin->resTotal ?></td>
									      <?php
												foreach ($subNames1 as $combin2) {
													if($combin2->connecttedPaper == $combin->resSubject){
														?>
														<td width="2%"><?= $combin2->resCQ ?></td>
											      <td rowspan="4" width="2%"><?= $combin2->resTotal ?></td>
											      <?php
													}
												}
											}
										}
										foreach ($subNames2 as $subj) {
											?>
											<td width="2%"><?= $subj->resCQ ?></td>
								      <td rowspan="4" width="2%"><?= $subj->resTotal ?></td>
								      <?php
										}
						      ?>
						      
						      <td rowspan="5" width="3%">(<?= $exam->spPosition ?>)<br><?= $exam->spFaild == 0 ? $exam->spPoint : 'F<br>'.$exam->spFaild  ?></td>
						      <?php if($key == 0 && $havecgpa){ ?>
						      	<td rowspan="<?= $totalExam*5 ?>" width="3%"><?= $student->cgpaPoint ?></td>
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
												$absentCk[] = $combin->resCa;
												$tCalSpan = 2;
												foreach ($subNames1 as $combin2) {
													if($combin2->connecttedPaper == $combin->resSubject){
														$resTotal += $combin2->resTotal;
														$tCalSpan = 4;
											      break;
													}
												}

												if(in_array('a', $absentCk) || in_array('A', $absentCk)){ 
													$info1['grade'] = 'Ab';
													$info1['point'] = '';
												}else{
													$subCQ		= (isnum($combin->subCQ) 	 + isnum($combin2->subCQ)) 	 / 2;
													$subMCQ		= (isnum($combin->subMCQ)	 + isnum($combin2->subMCQ))  / 2;
													$subPect	= (isnum($combin->subPect) + isnum($combin2->subPect)) / 2;
                          $subCa	= (isnum($combin->subCa) + isnum($combin2->subCa)) / 2;
													$resCQ 		= (isnum($combin->resCQ) 	 + isnum($combin2->resCQ)) 	 / 2;
													$resMCQ 	= (isnum($combin->resMCQ)	 + isnum($combin2->resMCQ))  / 2;
													$resPrec 	= (isnum($combin->resPrec) + isnum($combin2->resPrec)) / 2;
													$resCa 	= (isnum($combin->resCa) + isnum($combin2->resCa)) / 2;
													$info1 = genPoint($subCQ,$subMCQ,$subPect,$subCa,$resCQ,$resMCQ,$resPrec,$resCa,$combin->combineMark);
													$info1['point'] = ",".$info1['point'];
												}
												$resTotal += $combin->resTotal;

												echo "<td colspan='$tCalSpan'>$resTotal (".$info1['grade'].$info1['point'].")</td>";
												
											}
										}
										foreach ($subNames2 as $subj) {
											$absentCk = array();
						      		$absentCk[] = $subj->resCQ;
											$absentCk[] = $subj->resMCQ;
											$absentCk[] = $subj->resPrec;
											$absentCk[] = $subj->resCa;
											if(in_array('a', $absentCk) || in_array('A', $absentCk)){
												$info['grade'] = 'Ab';
												$info['point'] = '';
											}else{
												$info = genPoint($subj->subCQ,$subj->subMCQ,$subj->subPect,$subj->subCa,$subj->resCQ,$subj->resMCQ,$subj->resPrec,$subj->resCa,$subj->combineMark);
												$info['point'] = ",".$info['point'];
											}
											echo '<td colspan="2">'. $subj->resTotal .' ('.$info['grade'].$info['point'].')</td>';			
										}
						      ?>
						    </tr>
						  
						  <?php } ?>
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