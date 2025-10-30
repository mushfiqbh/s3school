<?php
/*
** Template Name: Admin AdmitCard
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
<p id="theSiteURL" class="hidden"><?= get_template_directory_uri() ?></p>
<div class="container-fluid maxAdminpages" style="padding-left: 0">
	<div class="row">

		<div class="col-md-12">
			<div class="panel panel-info">
			  <div class="panel-heading">
			  	<h3>
			  		Admit Card<br>
			  		<small>Create Students admit card here</small>
			  	</h3>
			  </div>
			  <div class="panel-body">
					<form class="form-inline" action="" method="GET">
						<input type="hidden" name="page" value="admitcard">

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
							<select id="resultSection" class="form-control" name="section" disabled>
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
							<input style="width: 100px;" class="form-control" type="text" name="roll" placeholder="Roll">
						</div>
						<div class="form-group">
							<label>
								Design 2
								<input type="checkbox" name="design" value="2">
							</label>
						</div>
						<div class="form-group">
							<input type="submit" name="creatId" value="Genarate" class="btn btn-primary">
						</div>
					</form>
			  </div>
			</div>
		</div>


		<?php if(isset($_GET['syear'])){ ?>
			<div class="col-md-12">
		  	<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>
		  </div>
		  <div id="printArea" class="col-md-12 printBG" >

			  <div class="printArea" style="margin: 10px 30px 0;">
					<style type="text/css">
						@page { size: auto;  margin: 0px; }
						#itemMainBox{max-width: 8.27in;display: inline-block;border: 2px solid #333333;overflow: hidden;margin: 20px 0 80px 0;font-family: sans-serif;width: 100%;position: relative;}
						#itemMainBox .itemWaterMark{position: absolute;width: 100%;bottom: 0;left: 0;z-index: -1;text-align: center;}
						#itemMainBox .itemWaterMark  img{opacity: .12;width: 250px;}
						#itemMainBox .instLogo{width: 90px; position: absolute;left: 0;top: 0;}
						#itemMainBox .instName{margin: 0 0 5px 0;color: #337ab7;font-weight: bold;font-size: 28px;}
						#itemMainBox .instAddrs{margin: 0 0 7px 0;color: #888888;font-size: 18px;}
						#itemMainBox .examName{margin: 0 auto 12px;text-align: center;font-size: 25px;}
						#itemMainBox .examName h3{margin: 0;font-size: 20px;}
						#itemMainBox .itemInfo{text-align: center; margin-bottom: 20px; clear: both;}
						#itemMainBox .admitCard{margin: 0 0 10px 0;color: #f7740c; font-weight: bold;background: #f0f0f0; print-color-adjust: exact; -webkit-print-color-adjust: exact; padding: 10px;border-radius: 5px; font-size: 25px;border: 2px solid #f0f0f0;}
						#itemMainBox .admitNote{float: left;}
						#itemMainBox .admitNote p{margin: 0;padding-left: 15px;}
						#itemMainBox hr{clear: both;}
						#itemMainBox .princSign{float: right;}
					</style>

			  	<?php
			  		$year 		= $_GET['syear'];
			  		$class 		= $_GET['class'];
			  		$section 	= isset($_GET['section']) ? $_GET['section'] : '';
			  		$roll 		= $_GET['roll'];
			  		$exam 		= $_GET['exam'];

			  		if (isset($_GET['syear'])) {
			  			$query = "SELECT studentid,stdName,stdFather,infoRoll,className,stdImg,infoYear,stdPhone,stdFather,groupName,sectionName,examName,stdAdmitYear,stdCreatedAt  FROM ct_student
								LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid AND ct_student.stdCurrentClass = ct_studentinfo.infoClass
								LEFT JOIN ct_class ON ct_studentinfo.infoClass = ct_class.classid
								LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId
								LEFT JOIN ct_exam ON ct_exam.examid = $exam
								LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid  WHERE infoYear = '$year' ";

				  		if ($_GET['roll'] != ''){
								$query .= " AND infoRoll IN ($roll)";
				  		}
				  		if ($section != ''){
								$query .= " AND infoSection = $section";
				  		}
				  		$query .= ($selectedGroupFilter > 0) ? " AND infoGroup = $selectedGroupFilter" : '';
				  		if ($selectedGenderFilter !== '') {
				  		    $query .= ' AND ct_student.stdGender = ' . intval($selectedGenderFilter);
				  		}
				  		$query .= " ORDER BY stdGender,infoRoll ASC";
				  		$groupsBy = $wpdb->get_results( $query );
				  	}
			  		if($groupsBy){

							foreach ($groupsBy as $key => $value) {
								$datetime = new DateTime($value->stdCreatedAt);
								?>
									<div id="itemMainBox">
										<div class="itemWaterMark">
											<img src="<?= $s3sRedux['instLogo']['url'] ?>">
										</div>
										<div style="padding: 15px 30px 5px">
											<div style="text-align: center; float: left; width: 100%" >
												<div style="position: relative;padding-left: 90px">
													<img class="instLogo" src="<?= $s3sRedux['instLogo']['url'] ?>">

													<h2 class="instName"><?= $s3sRedux['institute_name'] ?></h2>
									  			<h4 class="instAddrs"><?= $s3sRedux['institute_address'] ?></h4>
													
												</div>

											</div>
											<div class="itemInfo">
								  			<div class="examName">
								  				<h3><?= $value->examName ?> <?= $year ?></h3>
								  			</div>
								  			<h3 class="admitCard">Admit Card</h3>
											</div>
											<div style="float: left; clear: both;width: 100%;margin-bottom: 20px;">
									  		<div style="width: 75%; float: left;">
									  			<table style="font-size: 16px;">
									  				<tr>
									  					<td width="20%"><b>Name</b></td>
									  					<td width="10px" style="padding: 0 10px;"><b>:</b></td>
									  					<td width="40%" calspan="2"><b><?= $value->stdName ?></b></td>
									  				</tr>
									  				<tr>
									  					<td><b>ID</b></td>
									  					<td style="padding: 0 10px;"><b>:</b></td>
									  					<td calspan="2"><b><?= ($s3sRedux['stdidpref'] == 'year') ? $value->stdAdmitYear: $s3sRedux['stdidpref']; ?><?= sprintf("%05s", ($value->studentid + $s3sRedux['stdid'] )) ?></b></td>
									  				</tr>
									  				<tr>
									  					<td><b>Class</b></td>
									  					<td style="padding: 0 10px;"><b>:</b></td>
									  					<td><?= $value->className ?></td>
									  					<td width="100%">
									  						<?php if (isset($_GET['design']) && $_GET['design'] == 2){ ?>
									  							Exam Roll - 205<?= sprintf("%03s", ($key+1)) ?>
									  						<?php } ?>
								  						</td>
									  				</tr>
									  				<tr>
									  					<td><b>Section</b></td>
									  					<td style="padding: 0 10px;"><b>:</b></td>
									  					<td calspan="2"><?= $value->sectionName ?></td>
									  				</tr>
									  				<tr>
									  					<td><b>Group</b></td>
									  					<td style="padding: 0 10px;"><b>:</b></td>
									  					<td><?= $value->groupName ?></td>
									  					<td>
									  						<?php if (isset($_GET['design']) && $_GET['design'] == 2){ ?>
									  							Regi No - <?= $datetime->format('Y') ?><?= sprintf("%06s", ($value->studentid  )) ?>
									  						<?php } ?>
								  						</td>
									  				</tr>
									  				<tr>
									  					<td><b>ID No</b></td>
									  					<td style="padding: 0 10px;"><b>:</b></td>
									  					<td calspan="2"><b><?= $value->infoRoll ?></b></td>
									  				</tr>
									  			</table>

									  		</div>
									  		<div style="width: 25%; float: right; text-align: right;">
									  			<?php if (!empty($value->stdImg)) { ?>
										  			<img style="height: 100px; " alt="<?= $value->stdName ?>_img" src="<?= $value->stdImg ?>">
									  			<?php }else{ ?>
										  			<img style="height: 100px; " alt="<?= $value->stdName ?>_img" src="<?= get_template_directory_uri() ?>/img/No_Image.jpg">
									  			<?php } ?>
									  		</div>
									  	</div>
								  		<hr>
							  			<table class="admitNote">
							  				<tr>
							  					<td><b>N.B:</b></td>
							  					<td><?= $s3sRedux['admitCareNote'] ?></td>
							  				</tr>
							  			</table>
								  		
							  			<div class="princSign" style="text-align: center;">
							  				<img width="110" src="<?= $s3sRedux['principalSign']['url'] ?>"><br>
							  				<?= $s3sRedux['inst_head_title'] ?> signature
							  			</div>
							  			<div style="clear: both;text-align: center;padding: 10px 0 5px">
							  				<i style="font-size: 10px;color: #888;"> Generated by Bornomala, Developed by MS3 Technology BD, 
											 	Al-Marjan Shopping Center, Zindabazar, Sylhet. Email: teambornomala@gmail.com</i>
							  			</div>
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
		  <div id="editor"></div>
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