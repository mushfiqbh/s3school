<?php
/**
* Template Name: Admin CGPA Genarate
*/
global $wpdb;

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

if (isset($_POST['genarateCgpa'])) {
	$cgpaYear = $_POST['cgpaYear'];
	$cgpaCls = $_POST['cgpaClass']; 
	$users = $wpdb->query("UPDATE `ct_studentPoint` SET spstutas = 1 WHERE spYear = '$cgpaYear' AND spClass = $cgpaCls");
	$allPoint = $wpdb->get_results("SELECT `spStdID`,cast(sum((`spPoint`/100)*cgpaPercent) as decimal(12,2)) as spoint, sum(spTotalMark) as totalmark, sum(spFaild) as spFaild FROM `ct_studentPoint` LEFT JOIN ct_exam ON ct_exam.examid = ct_studentPoint.spExam WHERE `spClass` = $cgpaCls AND `spYear` = '$cgpaYear' GROUP BY `spStdID` ORDER BY spFaild,spoint DESC");
	$cgpainseart = "INSERT INTO `ct_cgpa` (`cgpaYear`,`cgpaClass`,`cgpaStudent`,`cgpaPoint`,`cgpaTotalMark`,`cgpaFaild`,`cgpaPosition`) VALUES";
	foreach ($allPoint as $key => $value) {
		$stdId = $value->spStdID;
		$spoint = $value->spoint;
		$totalm = $value->totalmark;
		$faild = $value->spFaild;
		$posi = $key+1;
		if ($key != 0)
			$cgpainseart .= ",";
		$cgpainseart .= " ('$cgpaYear',$cgpaCls,$stdId,$spoint,$totalm,$faild,$posi)";
	}
	$wpdb->query($cgpainseart);

	$sections = $wpdb->get_results("SELECT infoSection FROM `ct_studentinfo` WHERE `infoClass` = $cgpaCls AND `infoYear` = '$cgpaYear' GROUP BY `infoSection`");
	foreach ($sections as $section) {
		$sec = $section->infoSection;
		$up = "UPDATE 
			ct_cgpa TBL1 
			INNER JOIN 
			(
		    SELECT cgpaid,
		      @rowNumber := @rowNumber + 1 AS rowNum
		    FROM ct_cgpa LEFT JOIN ct_studentinfo ON infoStdid = cgpaStudent, (SELECT @rowNumber := 0) var
		    WHERE `cgpaClass` = $cgpaCls AND `cgpaYear` = '$cgpaYear' AND infoSection = $sec
		    ORDER BY cgpaFaild,cgpaPoint DESC
			) AS TBL2
			ON TBL1.cgpaid = TBL2.cgpaid
			SET TBL1.cgpaSecPosi = TBL2.rowNum";
			$wpdb->query($up);
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
												  	if ($isTeacher) {
												  		$classes = $wpdb->get_results( $wpdb->prepare( "SELECT spClass,className FROM `ct_studentPoint` LEFT JOIN ct_class ON ct_studentPoint.spClass = ct_class.classid WHERE spYear = %d AND spstutas = 0 AND havecgpa = 1 AND spClass IN ($teacherOfClass) GROUP BY spClass ORDER BY spClass ASC", $spYear ) );
												  	} else {
												  		$classes = $wpdb->get_results( $wpdb->prepare( "SELECT spClass,className FROM `ct_studentPoint` LEFT JOIN ct_class ON ct_studentPoint.spClass = ct_class.classid WHERE spYear = %d AND spstutas = 0 AND havecgpa = 1 GROUP BY spClass ORDER BY spClass ASC", $spYear ) );
												  	}
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
												  	if ($isTeacher) {
												  		$classes = $wpdb->get_results( $wpdb->prepare( "SELECT spClass,className FROM `ct_studentPoint` LEFT JOIN ct_class ON ct_studentPoint.spClass = ct_class.classid WHERE spYear = %d AND spstutas = 1 AND spClass IN ($teacherOfClass) GROUP BY spClass ORDER BY spClass ASC", $spYear ) );
												  	} else {
												  		$classes = $wpdb->get_results( $wpdb->prepare( "SELECT spClass,className FROM `ct_studentPoint` LEFT JOIN ct_class ON ct_studentPoint.spClass = ct_class.classid WHERE spYear = %d AND spstutas = 1 GROUP BY spClass ORDER BY spClass ASC", $spYear ) );
												  	}
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
