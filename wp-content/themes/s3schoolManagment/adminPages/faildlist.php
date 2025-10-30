<?php
/*
** Template Name: Admin FaildList
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

// Extract teacher restrictions for use in queries
$teacherOfClass = '';
$teacherOfSection = '';
if ($teacherRestrictions) {
    $teacherOfClass = $teacherRestrictions->teacherOfClass;
    $teacherOfSection = $teacherRestrictions->teacherOfSection;
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
				Fail List<br>
				<small>Find list of faild students</small>
			</h3>
		</div>
		<div class="panel-body">
			<form action="" method="GET" class="form-inline">

				<div class="form-group">
					<input type="hidden" name="page" value="faild_list">
					<label>Class</label>
					<select id='resultClass' class="form-control" name="class" required>
						<?php
							if ($isTeacher) {
								$classQuery = $wpdb->get_results( $wpdb->prepare( "SELECT classid,className FROM ct_class WHERE classid IN (SELECT examClass FROM ct_exam GROUP BY examClass ORDER BY className ASC) AND classid IN ($teacherOfClass)", null ) );
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
					<select id="resultExam" class="form-control" name="exam" disabled>
						<option selected>Select Class First</option>
					</select>
				</div>

				<div class="form-group ">
					<label>Section</label>
					<select id="resultSection" class="form-control" name="sec" disabled>
						<option selected>Select Class First</option>
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
		if(isset($_GET['exam'])):
			$exam 	= $_GET['exam'] ?? ''; 
			$year 	= $_GET['syear']; 
			$class 	= $_GET['class'];
			$sec 		= $_GET['sec'];
			$grou 	= $_GET['grou'];
$totalpassed = 0;
			if($exam != ''){
			    
			    $pass = "SELECT spStdID,stdName,withheld,infoRoll,spPoint,spTotalMark,spSubjTotal,sectionName,spPosition FROM ct_studentPoint
				LEFT JOIN ct_student ON ct_student.studentid = ct_studentPoint.spStdID
				LEFT JOIN ct_studentinfo ON ct_studentinfo.infoStdid = ct_studentPoint.spStdID AND ct_studentinfo.infoClass = $class AND ct_studentinfo.infoyear = '$year'
				LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
				LEFT JOIN ct_result ON ct_student.studentid = ct_result.resStudentId
				WHERE spYear = '$year' AND spClass = $class AND spExam = $exam AND spFaild = 0 AND spPoint != '0.00'";

			if ($sec != '') { $pass .= " AND infoSection = $sec"; }
			if ($grou != '') { $pass .= " AND infoGroup = $grou"; }
			$pass .= " GROUP BY spStdID";
			$passedstudents = $wpdb->get_results( $pass );
			$totalpassed = sizeof($passedstudents);

				$qry = "SELECT spStdID,stdName,withheld,infoRoll,spPoint,spTotalMark,spSubjTotal,sectionName,spFaild,spPosition,spClassPosition,spAbsent,infoOptionals,info4thSub FROM ct_studentPoint
					LEFT JOIN ct_student ON ct_student.studentid = ct_studentPoint.spStdID
					LEFT JOIN ct_studentinfo ON ct_studentinfo.infoStdid = ct_studentPoint.spStdID AND ct_studentinfo.infoClass = $class AND ct_studentinfo.infoyear = '$year'
					LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
					LEFT JOIN ct_result ON ct_student.studentid = ct_result.resStudentId
					WHERE spYear = '$year' AND spClass = $class AND spExam = $exam AND spFaild != 0";

				if ($sec != '') { $qry .= " AND infoSection = $sec"; }

				if ($grou != '') { $qry .= " AND infoGroup = $grou"; }

				$qry .= " GROUP BY spStdID ORDER BY spAbsent ASC, spFaild ASC, spPoint DESC, spTotalMark DESC, infoRoll ASC";
				// $qry .= " ORDER BY infoRoll ASC";
			}else{
				$exams = $wpdb->get_results("SELECT examid,examName,cgpaPercent FROM ct_studentPoint LEFT JOIN ct_exam ON spExam = examid WHERE spClass = $class AND spYear ='$year' GROUP BY spExam ORDER By examSirial");
				$qry = "SELECT cgpaStudent,stdName,infoRoll,sectionName,cgpaFaild,cgpaPoint,cgpaPosition,cgpaSecPosi,infoOptionals,info4thSub FROM ct_cgpa
					LEFT JOIN ct_student ON studentid = cgpaStudent
					LEFT JOIN ct_studentinfo ON infoStdid = cgpaStudent AND infoClass = $class AND infoyear = '$year'
					LEFT JOIN ct_section ON infoSection = sectionid
					WHERE cgpaYear = '$year' AND cgpaClass = $class AND cgpaFaild != 0";
					if ($sec != '') { $qry .= " AND infoSection = $sec"; }
					if ($grou != '') { $qry .= " AND infoGroup = $grou"; }
					 $qry .= " GROUP BY cgpaStudent ORDER BY cgpaPosition";
				$allmarksQry = $wpdb->get_results("SELECT spStdID,spExam,spPoint,spTotalMark,spFaild FROM ct_studentPoint LEFT JOIN ct_exam ON spExam = examid  WHERE spClass = $class AND spYear ='$year' ORDER By examSirial");
				$allmarks = array();
				foreach ($allmarksQry as $marksQry) {
					$allmarks[$marksQry->spStdID][$marksQry->spExam] = array($marksQry->spPoint,$marksQry->spTotalMark,$marksQry->spFaild);
				}
			}

			$students = $wpdb->get_results( $qry );
			$totalf = sizeof($students);
		?>
		<div class="container-fluid maxAdminpages">
			
			<div class="row">
				<div class="col-md-12">
			  	<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>
			  </div>
			  <div class="col-md-12">
			  	<div id="printArea" class="col-md-12 printBG" style="width: 8.27in">
					  <div class="printArea" style="margin: 10px 30px 0;">
					  	<style type="text/css">
					  		table tr{ page-break-inside: avoid !important; }
					  		table tr a{ text-decoration: none;color: #000; }
					  		@page { size: 210mm 297mm !important; margin: 0 !important; }
					  	</style>
					  	<link rel="stylesheet" href="<?= get_template_directory_uri() ?>/css/tabulationSheet.css" />

				  		<div style="text-align: center; position: relative;">
				  			<img height="80px" style="position: absolute;left: 10px;top: 10px" src="<?= $s3sRedux['instLogo']['url'] ?>">
		  					<h2 style="margin: 5px 0 5px 0;"><b><?= $s3sRedux['institute_name'] ?></b></h2>
					  		<p style="color:#2b5591; font-size: 14px; margin: 0;"><?= $s3sRedux['institute_address'] ?></p>
					  		<h3>Faild List (<?= $totalf ?>)</h3>
				  		</div>

				  		<?php
				  			$quey = "SELECT className";

				  			if($exam != '' ){ $quey .= ",examName"; }
				  			if($grou != '' ){ $quey .= ",groupName"; }
				  			if($sec != '' ){  $quey .= ",sectionName"; }
								$quey .= " FROM ct_class";
								if($exam != ''){ $quey .= "  LEFT JOIN ct_exam ON ct_exam.examid = $exam"; }
								if($grou != ''){ $quey .= " LEFT JOIN ct_group ON ct_group.groupId = $grou"; }
				  			if($sec != ''){  $quey .= " LEFT JOIN ct_section ON ct_section.sectionid = $sec"; }
									
								$quey .= " WHERE classid = $class";
				  			$info = $wpdb->get_results( $quey ); 
							?>

				  		<table style="width: 100%">
				  			<tr style="background: #4472C4;print-color-adjust: exact; -webkit-print-color-adjust: exact;color: #fff">
				  				<td><b>Exam Name :</b></td>
				  				<td colspan="3"><?= @$info[0]->examName ?></td>
				  				<td><b>Year/Section :</b></td>
				  				<td><?= $year ?></td>
				  			</tr>
				  			<tr style="background: #D9E2F3;print-color-adjust: exact; -webkit-print-color-adjust: exact;">
				  				<td><b>Class :</b></td>
				  				<td><?= @$info[0]->className ?></td>
				  				<td><b>Section :</b></td>
				  				<td><?= $info[0]->sectionName ?? 'ALL' ?></td>
				  				<td><b>Group :</b></td>
				  				<td><?= @$info[0]->groupName ?></td>
				  			</tr>
				  		</table>
				  		<br>

				  		<?php if($exam != ''){ ?>
					  		<table style="width: 100%; text-align: center;">
					  			<tr style="background: #FFE599;print-color-adjust: exact; -webkit-print-color-adjust: exact;line-height: 1">
					  				<th width="200">Student Name</th>
					  				<th width="70">Roll</th>
<!-- 					  				<th width="70">Total Mark</th> -->
					  				<th width="70">Obtain Mark</th>
					  				<!--<th width="70">Point</th>-->
					  				<th width="70">Faild Subject</th>
					  				<th width="70">Section Position</th>
					  				<th width="70">Class Position</th>
					  				<!--<th width="70"><?= ($sec != '') ? 'Class Position' : 'Section Position' ?></th>-->
					  				<!--<th width="70"><?= ($sec != '') ? 'Section Position' : 'Class Position' ?></th>-->
					  			</tr>

						  		<?php
										foreach($students as $key => $student){
											$stdId = $student->spStdID;
											$stdRol = $student->infoRoll;
// 											$subid = json_decode($student->infoOptionals);
// 											$subid[] = $student->info4thSub;
// 											$subid = implode (", ", $subid);
											$subjects = $wpdb->get_results("SELECT SUM(subMCQ)+SUM(subCQ)+SUM(subPect)+SUM(subCa) AS totalSmark FROM ct_subject WHERE (subjectClass = $class AND subOptinal = 0 AND sub4th = 0) ")[0];
										
											?>
												<tr style="line-height: 1">
								  				<td style="text-align: left;">
								  					<?php if(is_admin()) { ?>
								  						<a href='<?= admin_url("admin.php?page=result&view=result&stdnt=$stdId&class=$class&exam=$exam&syear=$year&roll=$stdRol&sec=$sec") ?>'>
								  					<?php }else{ ?>
															<a href='<?= "admin-result?page=result&view=result&stdnt=$stdId&class=$class&exam=$exam&syear=$year&roll=$stdRol&sec=$sec" ?>'>
								  					<?php } ?>
									  					<?= $student->stdName ?>
									  				</a>
								  				</td>
								  				<td style="white-space: nowrap;"><?= $student->infoRoll ?><?= ($sec == '') ? " (".$student->sectionName.")" :''; ?></td>
								  				
								  				<td><?= $student->spTotalMark ?></td>
								  				<!--<td><?= $student->spPoint ?></td>-->
								 					<td><?= $student->spFaild ?> <?= $student->spAbsent != 0 ? '('.$student->spAbsent.' Abs)' : ''; ?> </td>
								 				
					  							<td><?=  $student->withheld == 1?'Withheld': (( $grou != '' || ($sec != '' && $grou == '') ) ? $key+1+$totalpassed  : $student->spPosition);  ?></td>
					  							<td><?=  $student->withheld == 1?'Withheld': $student->spClassPosition ?></td>
								  			</tr>
											<?php									
							  		} 
							  	?>
					  		</table>
					  	<?php }else{ ?>
					  		<p style="text-align: center;">
					  			<?php foreach ($exams as $value) { echo $value->examName ." - ". $value->cgpaPercent ."%, "; } ?>
			  				</p>
					  		<table style="width: 100%; text-align: center;">
					  			<tr style="background: #FFE599;print-color-adjust: exact; -webkit-print-color-adjust: exact;line-height: 1
					  			">
					  				<th width="200">Student Name</th>
					  				<th>Roll</th>
					  				<?php foreach ($exams as $value) { ?>
					  					<th colspan="2"><small><?= $value->examName ?></small></th>
					  				<?php } ?>
					  				<th width="70">CGPA</th>
					  				<th width="70">Latter Grade</th>
					  				<!--<th width="70"><?= ($sec != '') ? 'Class Position' : 'Section Position' ?></th>-->
					  				<!--<th width="70"><?= ($sec != '') ? 'Section Position' : 'Class Position' ?></th>-->
					  				<th width="70">Section Position</th>
					  				<th width="70">Class Position</th>
					  			</tr>
					  			<?php foreach ($students as $student) { ?>
					  				<tr style="line-height: 1">
					  					<td><small><?= $student->stdName ?></small></td>
					  					<td style="white-space: nowrap;"><?= $student->infoRoll ?> (<?= $student->sectionName ?>)</td>
					  					<?php foreach ($exams as $value) { $res = $allmarks[$student->cgpaStudent][$value->examid] ?? ''; ?>
						  					<td><?= @$res[1] ?></td>
						  					<td><?= @$res[0] ?><?= isset($res[2]) && $res[2] != 0 ? " (F/".$res[2].")" : '' ?></td>
						  				<?php } ?>
					  					<td><?= $student->cgpaPoint ?></td>
					  					<td><?= $student->cgpaFaild == 0 ?  pointToGrade($student->cgpaPoint) : 'F' ?></td>
					  					<!--<td><?= ($sec != '') ? $student->cgpaPosition : $student->cgpaSecPosi ?></td>-->
					  					<!--<td><?= ($sec != '') ? $student->cgpaSecPosi : $student->cgpaPosition ?></td>-->
					  						<td><?=  $student->withheld == 1?'Withheld': (( $grou != '' || ($sec != '' && $grou == '') ) ? $key+1+$totalpassed  : $student->spPosition);  ?></td>
					  							<td><?=  $student->withheld == 1?'Withheld': $student->spClassPosition ?></td>
					  				</tr>
				  				<?php } ?>

					  		</table>
					  	<?php } ?>
					  </div>
					</div>
			  </div>
			</div>
			
		</div>

		<?php 
	endif; ?>


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
    w.document.write('<scr' + 'ipt type="text/javascript">' + 'window.onload = function() { window.print();  };' + '</sc' + 'ript>');
    w.document.close();
    w.focus();
    return true;
  }
</script>