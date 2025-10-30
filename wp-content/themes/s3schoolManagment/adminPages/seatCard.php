<?php
/*
** Template Name: Admin SeatCard
*/
global $wpdb; global $s3sRedux; 

// Check if current user is a teacher and get their restrictions
$current_user = wp_get_current_user();
$isTeacher = (isset($current_user->roles[0]) && $current_user->roles[0] == 'um_teachers');
$teacherRestrictions = null;

if ($isTeacher) {
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

$attendanceGroups = $wpdb->get_results("SELECT groupId, groupName FROM ct_group ORDER BY groupName ASC");
$attendanceGroupLookup = array();
if (!empty($attendanceGroups)) {
    foreach ($attendanceGroups as $attendanceGroup) {
        $attendanceGroupLookup[$attendanceGroup->groupId] = $attendanceGroup->groupName;
    }
}

$genderSelectOptions = array(
    '' => 'All Genders',
    '1' => 'Boy',
    '0' => 'Girl',
    '2' => 'Other',
);

$selectedGroupFilter = isset($_GET['group']) ? intval($_GET['group']) : 0;
$rawGenderFilter = isset($_GET['gender']) ? trim($_GET['gender']) : '';
$selectedGenderFilter = array_key_exists($rawGenderFilter, $genderSelectOptions) ? $rawGenderFilter : '';
?>

<?php if ( ! is_admin() ) { get_header(); ?>
<div class="b-layer-main">

	<div class="">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
<?php } ?>

<div class="container-fluid maxAdminpages" style="padding-left: 0">

	<p id="theSiteURL" class="hidden"><?= get_template_directory_uri() ?></p>
	<div class="row">

		<div class="col-md-12">
			<div class="panel panel-info">
			  <div class="panel-heading"><h3>Seat Card<br><small>Create Students Seat Card</small></h3></div>
			  <div class="panel-body">
					<form class="form-inline" action="" method="GET">
						<input type="hidden" name="page" value="seatcard">

						<div class="form-group">
							<label>Class</label>
							<select id='resultClass' class="form-control" name="class" required>
								<?php

									// If teacher, only show their assigned class
									if ($isTeacher && $teacherRestrictions) {
										$classQuery = $wpdb->get_results( $wpdb->prepare(
											"SELECT classid,className FROM ct_class WHERE classid = %d AND classid IN (SELECT examClass FROM ct_exam GROUP BY examClass)",
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
							<select id="resultSection" class="form-control" name="section" required disabled>
								<option disabled selected>Select Class First</option>
							</select>
						</div>

						<div class="form-group">
							<label>Year</label>
							<select id='resultYear' class="form-control" name="syear" required disabled>
								<option disabled selected>Select Class First</option>
							</select>
						</div>

						<div class="form-group">
							<label>Group</label>
							<select id="resultGroup" class="form-control" name="group">
								<option value="0">All Groups</option>
								<?php foreach ($attendanceGroups as $group) { ?>
									<option value="<?php echo $group->groupId; ?>" <?php echo ($selectedGroupFilter == $group->groupId) ? 'selected' : ''; ?>><?php echo $group->groupName; ?></option>
								<?php } ?>
							</select>
						</div>

						<div class="form-group">
							<label>Gender</label>
							<select id="resultGender" class="form-control" name="gender">
								<?php foreach ($genderSelectOptions as $key => $label) { ?>
									<option value="<?php echo $key; ?>" <?php echo ($selectedGenderFilter === $key) ? 'selected' : ''; ?>><?php echo $label; ?></option>
								<?php } ?>
							</select>
						</div>


						<div class="form-group" id="idRoll">
							<input class="form-control" type="text" name="roll" placeholder="Roll">
						</div>
						<div class="form-group">
							<input type="submit" value="Genarate" class="btn btn-primary">
						</div>
					</form>
			  </div>
			</div>
		</div>

		<?php if(isset($_GET['syear'])){ ?>
	  	<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>

		  <div id="printArea" class="col-md-12 printBG">
		  	<div style="max-width: 8.27in; margin: auto; background: #f9f9f9;">
			  	<style type="text/css"> @page { size: auto;  margin: 0px; } </style>

				  <div class="printArea">
				  	<?php

				  		$year 		= $_GET['syear'];
				  		$class 		= $_GET['class'];
				  		$section 	= $_GET['section'];
				  		$roll 		= $_GET['roll'];
				  		$exam 		= $_GET['exam']; 

				  		if (isset($_GET['syear'])) {
				  			$query = "SELECT stdName,infoRoll,className,stdImg,groupName,infoYear,stdPhone,stdFather,sectionName,examName FROM ct_student
									LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid AND ct_student.stdCurrentClass = ct_studentinfo.infoClass
									LEFT JOIN ct_class ON ct_studentinfo.infoClass = ct_class.classid
									LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId
									LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
									LEFT JOIN ct_exam ON ct_exam.examid = $exam
									WHERE infoYear = '$year' ";
 
					  		$query .= ($_GET['roll'] != '') ? " AND infoRoll IN ($roll)" : ''; 
					  		$query .= ($_GET['section'] != 0) ? " AND infoSection = $section" : '';
					  		$query .= ($selectedGroupFilter > 0) ? " AND infoGroup = $selectedGroupFilter" : '';
					  		if ($selectedGenderFilter !== '') {
					  		    $query .= ' AND ct_student.stdGender = ' . intval($selectedGenderFilter);
					  		}
					  		$query .= " ORDER BY infoRoll ASC";
					  		$groupsBy = $wpdb->get_results( $query );
					  	}

				  		if($groupsBy){

								foreach ($groupsBy as $value) {
									?>
										<div style="width: calc(50% - 50px);height: calc(2.338in - 44px);display: inline-block;margin: 5px 10px;border: 2px solid #333;overflow: hidden;padding: 10px;">

											<div style="text-align: center; margin-bottom: 7px; position: relative;">
												<img style="width: 50px; position: absolute;left: 0;top: 0;" src="<?= $s3sRedux['instLogo']['url'] ?>">
												<h2 style="margin: 0 0 3px;color: #337ab7; font-weight: bold;font-size: 18px;text-align: center;padding-left: 50px;line-height: 1;"><?= $s3sRedux['institute_name'] ?></h2>
							  				<h5 style="margin: 0 0 3px;color: #000;padding-left: 50px"><?= $s3sRedux['institute_address'] ?></h5>
							  				<h4 style="margin: 0;color: #000;font-weight: bold;"><?= $value->examName ?> <?= $year ?></h4>
											</div>
								  		<div style="width: 80%; float: left;line-height: 18px">
							  			<table style="line-height: 1.1">
								  				<tr>
								  					<td><b>Name </b></td>
								  					<td style="padding: 0 5px;"><b>:</b></td>
								  					<td><?= $value->stdName ?></td>
								  				</tr>
								  				<tr>
								  					<td><b>Class </b></td>
								  					<td style="padding: 0 5px;"><b>:</b></td>
								  					<td><?= $value->className ?></td>
								  				</tr>
								  				<tr>
								  					<td><b>Section </b></td>
								  					<td style="padding: 0 5px;"><b>:</b></td>
								  					<td><?= $value->sectionName ?></td>
								  				</tr>
								  				<tr>
								  					<td><b>Group </b></td>
								  					<td style="padding: 0 5px;"><b>:</b></td>
								  					<td><?= $value->groupName ?></td>
								  				</tr>
								  			</table>

								  		</div>
								  		<div style="width: 20%; float: right;">
												<h1 style="margin: 0 0 2px 0; text-align: center;">Roll<br><b> <?= $value->infoRoll ?></b></h1>
								  		</div>
								  	</div>

									<?php
								}
							}else{
								echo "<h3 class='text-center'>No Student Found</h3>";
							}

				  	?>

				  </div>
		  	</div>

		  </div>

		<?php } ?>
	</div>
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