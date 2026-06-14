<?php 

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
	<?php
		$thstyle = "style='width: 35px;line-height: 1;height: 60px;text-align: center;padding:3px;'";
		$spanstyle = "style='writing-mode: sideways-lr;word-wrap: break-word;'";
	?>
					
	<!-- Report View -->
	<div class="text-right">
  	<button onclick="print('printArea')" class="btn btn-primary">Print</button>
  </div>
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
			</style>
		<?php 
		if(isset($_GET['syear']) && isset($_GET['class'])){

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
					$exams = $wpdb->get_results("SELECT spExam,examName,spFaild,spPoint,spPosition,spClassPosition,spPoint,spTotalMark,spSubjTotal FROM `ct_studentPoint`
		      	LEFT JOIN ct_exam ON ct_exam.examid = ct_studentPoint.spExam
		      	WHERE spClass = $class AND spYear = '$year' AND spStdID = $std GROUP BY spExam ORDER BY examSirial");
					$totalExam = sizeof($exams);
			    ?>
			    	<div class="preportstd">
							<table>
								<tr>
									<td colspan="4" class="noborder">
							    	<table style="width: 100%;margin-top: 10px;">
											<tbody>
												<tr style="background: #4472C4;-webkit-print-color-adjust: exact;color: #fff">
													<td><b>Name :</b> <?=  $student->stdName ?></td>
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
									
							    <?php foreach ($exams as $key => $exam) { if($key > 2){ break; } ?>
							    <?php 
							    	$subExam = $exam->spExam;
						      	$subNames = $wpdb->get_results("SELECT *, (SELECT MAX(`resTotal`) as maxr FROM `ct_result` WHERE resClass = $class AND resExam = $subExam AND resultYear = '$year' AND resSubject = ct_subject.subjectid) AS maxres FROM `ct_result`
											LEFT JOIN ct_subject ON ct_result.resSubject = ct_subject.subjectid
											LEFT JOIN ct_class ON $class = ct_class.classid
											LEFT JOIN ct_studentPoint ON $class = ct_studentPoint.spClass AND ct_studentPoint.spExam = $subExam AND ct_studentPoint.spYear = '$year' AND ct_studentPoint.spStdID = $std
											WHERE resClass = $class AND resExam = $subExam AND resultYear = '$year' AND resStudentId = $std ORDER BY subCombineMark DESC,sub4th,subOptinal,subCode ASC");
							    ?>
							    	<td <?= $key == 0 ? 'colspan="2"' : ''; ?> class="noborder">
											<table>
												<tr>
													<th colspan="<?= $key == 0 ? 9 : 7; ?>" style="background: #a1dcff;text-align: center;color: #000">
														<?= $exam->examName ?>
													</th>
												</tr>
									    	
									    	<tr>
													<?php if($key == 0){ ?> 
										    		<th <?= $thstyle ?>>SL. No.</th>
														<th>Subject Name</th>
													<?php } ?>
													<th <?= $thstyle ?>>CQ</th>
													<th <?= $thstyle ?>>MCQ</th>
													<th <?= $thstyle ?>>PA</th>
													<th <?= $thstyle ?>><div <?= $spanstyle ?>>Total<br>Mark</div></th>
													<th <?= $thstyle ?>><div <?= $spanstyle ?>>Grade<br>point</div></th>
													<th <?= $thstyle ?>><div <?= $spanstyle ?>>Latter<br>Grade</div></th>
													<th <?= $thstyle ?>><div <?= $spanstyle ?>>Highest<br>Mark</div></th>
												</tr>
												<?php foreach ($subNames as $subKey => $subNa) { ?>
													<?php
														$subCQ		= $subNa->subCQ;
														$subMCQ		= $subNa->subMCQ;
														$subPect	= $subNa->subPect;
														$stdCQ		= $subNa->resCQ;
														$stdMCQ		= $subNa->resMCQ;
														$stdPrec	= $subNa->resPrec;
														$combine	= $subNa->combineMark;
														$genFun = genPoint($subCQ,$subMCQ,$subPect,$stdCQ,$stdMCQ,$stdPrec,$combine);
													?>
										    	<tr>
										    		<?php if($key == 0){ ?> 
											    		<td><?= $subKey + 1 ?></td>
															<td><?= $subNa->subjectName ?></td>
														<?php } ?>
														<td><?= $subNa->resCQ ?></td>
														<td><?= $subNa->resMCQ ?></td>
														<td><?= $subNa->resPrec ?></td>
														<td><?= $genFun['total'] ?></td>
														<td><?= $genFun['point'] ?></td>
														<td><?= $genFun['grade'] ?></td>
														<td><?= 100//$subNa->maxres ?></td>
													</tr>
											<?php } ?>

												<tr style="background: #d9edf7">
													<th colspan="<?= $key == 0 ? 6 : 4; ?>" style='font-size: 12px'>Obtain <?= '9999'//$subNames[0]->spTotalMark ?> out of <?= '9999'//$subNames[0]->spSubjTotal ?></th>
													<th><?= '1000'//$exam->spPoint ?> </th>
													<th><?= '1000'//$exam->spTotalMark ?> </th>
													<th></th>
												</tr>
											</table>
										</td>
	    						<?php } ?>

								</tr>
								<tr>
									<td class="noborder">

										<!-- %%%%%%%%%%%%%%%%%% Year Final Result %%%%%%%%%%%%%%%%%% -->
										<table>
											<tr>
												<th colspan="3" style="background: #a1dcff;text-align: center;color: #000">Year Final Result</th>
											</tr>
											<tr>
												<th>Total Mark</th>
												<td>800</td>
												<td><small>Merit Position</small></td>
											</tr>
											<tr>
												<th>Obtained Mark</th>
												<td>600</td>
												<th rowspan="3"><h3 style="text-align: center;">6</h3></th>
											</tr>
											<tr>
												<th>C.G.P.A</th>
												<td>3.55</td>
											</tr>
											<tr>
												<th>L.G</th>
												<td>A+</td>
											</tr>
										</table>
										<table style="margin-top: 3px">
											<tr>
												<th width="38%" style="text-align: center;background: #d9edf7;">Principal's comment & Signature</th>
												<td></td>
											</tr>
										</table>
									</td>
									<?php foreach ($exams as $key => $exam) { if($key > 2){ break; } ?>
										<td class="noborder">
											<table>
												<tr>
													<th colspan="3" style="background: #a1dcff;text-align: center;color: #000;width: 40px;"><?= $exam->examName ?></th>
												</tr>
												<tr>
													<th>Total Mark</th>
													<td><?= 800 ?></td>
													<td><small>Position</small></td>
												</tr>
												<tr>
													<th>Obtained Mark</th>
													<td><?= 600 ?></td>
													<th rowspan="3"><h3 style="text-align: center;">6</h3></th>
												</tr>
												<tr>
													<th>G.P.A</th>
													<td><?= 4.52 //$exam->spPoint ?></td>
												</tr>
												<tr>
													<th>L.G</th>
													<td><?= 'A+' ?></td>
												</tr>
											</table>
											<table style="margin-top: 3px">
												<tr>
													<th width="35%" style="text-align: center;background: #d9edf7;line-height: 1.08;">Class Teacher's comment & Signature</th>
													<td></td>
												</tr>
											</table>
										</td>
									<?php } ?>
								</tr>
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
$students  = $wpdb->get_results("SELECT `studentid`,`stdImg`,infoClass,infoSection,infoYear,infoRoll FROM `ct_student` LEFT JOIN ct_studentinfo ON ct_studentinfo.infoStdid = ct_student.studentid AND ct_studentinfo.infoClass = ct_student.stdCurrentClass WHERE `stdImg` != ''");

foreach ($students as $student) {
	$std = $student->studentid;
	$class = $student->infoClass;
	$sec = $student->infoSection;
	$year = $student->infoYear;
	$roll = sprintf("%02d", $student->infoRoll);
	$wpdb->query("UPDATE `ct_student` SET stdImg = 'http://chsac.edu.bd/wp-content/uploads/$class-$sec-$year/$roll.jpg' WHERE studentid = $std");
}


 ?>