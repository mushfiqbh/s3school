<?php
/**
* Template Name: Admin CGPA Genarate
*/
global $wpdb;

if (isset($_POST['genarateCgpa'])) {
	$cgpaYear = $_POST['cgpaYear'];
	$cgpaCls = $_POST['cgpaClass'];

    // 1. Get all exams for the selected class and year, including their CGPA percentage weight
	$exams = $wpdb->get_results("SELECT examid,examName,cgpaPercent FROM ct_studentPoint LEFT JOIN ct_exam ON spExam = examid WHERE spClass = $cgpaCls AND spYear ='$cgpaYear' GROUP BY spExam ORDER By examSirial");
    $examCounter = sizeof($exams);

    // 2. Mark all student points for this class/year as 'calculated' (spstutas = 1)
	$users = $wpdb->query("UPDATE `ct_studentPoint` SET spstutas = 1 WHERE spYear = '$cgpaYear' AND spClass = $cgpaCls");

    /*
     * 3. Calculate CGPA for each student and sort by merit:
     *    - For each student, sum up their points for all exams, weighted by each exam's cgpaPercent
     *    - Calculate total marks and total failed subjects
     *    - The formula for each student:
     *        CGPA = SUM((spPoint / 100) * cgpaPercent) over all exams
     *    - Sort order (in sequence):
     *        1. Passed students (0 failed subjects) appear first
     *        2. Then by number of exams attended (descending)
     *        3. Then by number of failed subjects (ascending)
     *        4. Then by CGPA (descending)
     *        5. Finally by total marks (descending)
     *    - Students with identical scores receive the same position
     *    - Results are grouped by student ID
     */
	$allPoint = $wpdb->get_results("SELECT `spStdID`, COUNT(spStdID) AS exam_counter, cast(sum((`spPoint`/100)*cgpaPercent) as decimal(12,2)) as spoint, sum(spTotalMark) as totalmark, sum(spFaild) as spFaild FROM `ct_studentPoint` LEFT JOIN ct_exam ON ct_exam.examid = ct_studentPoint.spExam WHERE `spClass` = $cgpaCls AND `spYear` = '$cgpaYear' GROUP BY `spStdID` ORDER BY CASE WHEN SUM(spFaild) = 0 THEN 0 ELSE 1 END, exam_counter DESC, spFaild ASC, spoint DESC, totalmark DESC");

    // 4. Prepare bulk insert query for all students' CGPA results
	$cgpainseart = "INSERT INTO `ct_cgpa` (`cgpaYear`,`cgpaClass`,`cgpaStudent`,`cgpaPoint`,`cgpaTotalMark`,`cgpaFaild`,`cgpaPosition`) VALUES";
	foreach ($allPoint as $key => $value) {	   
		$stdId = $value->spStdID;
		$spoint = $value->spoint;
		$totalm = $value->totalmark;
		$faild = $value->spFaild;
		
		if($spoint == 0){
			$faild++;
		}

		$posi = $key+1;
		if ($key != 0)
			$cgpainseart .= ",";
		$cgpainseart .= " ('$cgpaYear',$cgpaCls,$stdId,$spoint,$totalm,$faild,$posi)";
	}
    // 5. Insert all students' CGPA results into ct_cgpa table
	$wpdb->query($cgpainseart);

    /*
     * 6. For each section in the class, update section-wise position (cgpaSecPosi):
     *    - Updates cgpaSecPosi in ct_cgpa for each student in the section
     */
	$sections = $wpdb->get_results("SELECT infoSection FROM `ct_studentinfo` WHERE `infoClass` = $cgpaCls AND `infoYear` = '$cgpaYear' GROUP BY `infoSection`");
	foreach ($sections as $section) {
		$sec = $section->infoSection;
		// Get all students in this section, ordered by ranking rules
		$students = $wpdb->get_results(
			"SELECT cg.cgpaid, cg.cgpaFaild, cg.cgpaPoint, cg.cgpaTotalMark
			 FROM ct_cgpa cg
			 LEFT JOIN ct_studentinfo si ON si.infoStdid = cg.cgpaStudent AND si.infoClass = $cgpaCls AND si.infoYear = '$cgpaYear'
			 WHERE cg.cgpaClass = $cgpaCls AND cg.cgpaYear = '$cgpaYear' AND si.infoSection = $sec
			 ORDER BY 
				CASE WHEN cg.cgpaFaild = 0 THEN 0 ELSE 1 END,
				cg.cgpaFaild ASC,
				cg.cgpaPoint DESC,
				cg.cgpaTotalMark DESC"
		);
		   $last = null;
		   $rank = 1;
		   $countAtRank = 0;
		   foreach ($students as $idx => $student) {
			   $current = $student->cgpaFaild . '-' . $student->cgpaPoint . '-' . $student->cgpaTotalMark;
			   if ($last !== $current) {
				   // If not the first, increment rank by number of students at previous rank
				   if ($idx !== 0) {
					   $rank += $countAtRank;
				   }
				   $countAtRank = 1;
				   $last = $current;
			   } else {
				   $countAtRank++;
			   }
			   $wpdb->query($wpdb->prepare(
				   "UPDATE ct_cgpa SET cgpaSecPosi = %d WHERE cgpaid = %d",
				   $rank,
				   $student->cgpaid
			   ));
		   }
	}
}

if (isset($_POST['cancelCgpa'])) {
	$cgpaYear = $_POST['cgpaYear'];
	$cgpaClass = $_POST['cgpaClass']; 
	$users = $wpdb->query("UPDATE `ct_studentPoint` SET spstutas = 0 WHERE spYear = '$cgpaYear' AND spClass = $cgpaClass");
	$users = $wpdb->query("DELETE FROM `ct_cgpa` WHERE cgpaYear = '$cgpaYear' AND cgpaClass  = $cgpaClass");
}

?>

<?php if ( ! is_admin() ) { get_header(); ?>
<div class="b-layer-main">
	<div class="">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
<?php } ?>

<div class="container-fluid maxAdminpages" style="padding-left: 0">
	
	<!-- Show Status message -->
  <?php if(isset($message)){ ms3showMessage($message); } ?>

	<h2>CGPA Genarate</h2>
	<p>Please Publish the result first for genarate the CGPA</p><br>
	<div class="row">
		<div class="col-md-6">
			<div class="panel panel-info">
			  <div class="panel-heading"><h3>For Genarate</h3></div>
			  <div class="panel-body">
			  	<?php 
				  	$years = $wpdb->get_results( "SELECT spYear FROM `ct_studentPoint` WHERE spstutas = 0 GROUP BY spYear ORDER BY spYear DESC" );
				  	foreach ($years as $year) {
				  		$spYear = $year->spYear;
				  		?>
				  			<div class="panel panel-success">
								  <div class="panel-heading"><h4 style="margin:0"><?= $spYear ?></h4></div>
								  <div class="panel-body">

										<div class="bs-example">
										  <table class="table table-striped table-bordered">
										    <thead>
										      <tr>
										        <th>Exam</th>
										        <th>Action</th>
										      </tr>
										    </thead>
										    <tbody>
										      <?php 
												  	$classes = $wpdb->get_results( "SELECT spClass,className FROM `ct_studentPoint` LEFT JOIN ct_class ON ct_studentPoint.spClass = ct_class.classid WHERE spYear = $spYear AND spstutas = 0 AND havecgpa = 1 GROUP BY spClass ORDER BY spClass ASC" );
												  	foreach ($classes as $class) {
												  		$spClass = $class->spClass;
												  		?>
												  			
											  				<tr>
											  					<td><?= $class->className ?></td>
											  					<td>
											  						<form action="" method="POST">
											  							<input type="hidden" name="cgpaYear" value="<?= $spYear ?>">
											  							<input type="hidden" name="cgpaClass" value="<?= $spClass ?>">
											  							<button class="btn btn-success btn-sm" type="submit" name="genarateCgpa">Genarate</button>
											  						</form>
											  					</td>
											  				</tr>
												  		<?php
												  	}
												  ?>
										    </tbody>
										  </table>
										</div>
						  		
								  </div>
								</div>
				  		<?php
				  	}
				  ?>
			  </div>
			</div>
		</div>
		<div class="col-md-6">
			<div class="panel panel-info">
			  <div class="panel-heading"><h3>Genareted</h3></div>
			  <div class="panel-body">
			  	<?php 
				  	$years = $wpdb->get_results( "SELECT spYear FROM `ct_studentPoint` WHERE spstutas = 1 GROUP BY spYear ORDER BY spYear DESC" );
				  	foreach ($years as $year) {
				  		$spYear  = $year->spYear;
				  		?>
				  			<div class="panel panel-success">
								  <div class="panel-heading"><h4 style="margin:0"><?= $spYear ?></h4></div>
								  <div class="panel-body">
								  	<div class="bs-example">
										  <table class="table table-striped table-bordered">
										    <thead>
										      <tr>
										        <th>Exam</th>
										        <th>Action</th>
										      </tr>
										    </thead>
										    <tbody>
										      <?php 
												  	$classes = $wpdb->get_results( "SELECT spClass,className FROM `ct_studentPoint` LEFT JOIN ct_class ON ct_studentPoint.spClass = ct_class.classid WHERE spYear = $spYear AND spstutas = 1 GROUP BY spClass ORDER BY spClass ASC" );
												  	foreach ($classes as $class) {
												  		$spClass = $class->spClass;
												  		?>
												  			
											  				<tr>
											  					<td><?= $class->className ?></td>
											  					<td>
											  						<form action="" method="POST">
											  							<input type="hidden" name="cgpaYear" value="<?= $spYear ?>">
											  							<input type="hidden" name="cgpaClass" value="<?= $spClass ?>">
											  							<button class="btn btn-danger btn-sm" type="submit" name="cancelCgpa">Cancel</button>
											  						</form>
											  					</td>
											  				</tr>
												  		<?php
												  	}
												  ?>
										    </tbody>
										  </table>
										</div>
								  </div>
								</div>
				  		<?php
				  	}
				  ?>
			  </div>
			</div>
		</div>
	</div>
</div>

<?php if ( ! is_admin() ) { ?>
				</div>
			</div>
		</div>
	</div>
</div>
<?php get_footer(); } ?>