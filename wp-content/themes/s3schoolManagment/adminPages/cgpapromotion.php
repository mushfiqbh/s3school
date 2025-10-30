<?php
/*
** Template Name: Admin CGPA Promotion
*/ 
 global $wpdb; global $s3sRedux; 

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


if (isset($_POST['promotStu'])) {
	if ($_POST['promotion'] < 1) {
		$message = ms3message(false, 'Updated');

	}else{
		$prevClass 	= $_POST['prevClass'];
		$toClass 		= $_POST['promotclass'];
		$infoYear 	= $_POST['infoYear'];
		$toSection 	= $_POST['promotsection'];
		$prvSection = $_GET['section'];
		$addroll 		= $_POST['addroll'];

		$assignRoll = isset($_POST['assignRoll']) ? $_POST['assignRoll'] : '';

		$samesec = $wpdb->get_results("SELECT `sectionName`,count(*) FROM ct_section WHERE `sectionid` = $toSection OR `sectionid` = $prvSection GROUP BY sectionName HAVING count(*) >1");

		if($assignRoll == ''){
			$lastRoll = $wpdb->get_results("SELECT infoRoll FROM `ct_studentinfo` WHERE `infoClass` = $toClass AND `infoYear` = '$infoYear' AND `infoSection` = $toSection ORDER BY `infoRoll` DESC LIMIT 1");
			$lastRoll = sizeof($lastRoll) > 0 ? $lastRoll[0]->infoRoll + 1 : 1;
		}

		foreach ($_POST['promotion'] as $stdid) {

	    $students = $wpdb->get_results("SELECT infoid FROM ct_studentinfo WHERE infoStdid = $stdid AND infoClass = $toClass AND infoYear = $infoYear" );

	    if(sizeof($students) < 1){
	    	$previnfo = $wpdb->get_results("SELECT * FROM ct_studentinfo WHERE infoStdid = $stdid AND infoClass = $prevClass limit 1");

	    	$prev4th = $previnfo[0]->info4thSub;
	    	$prevOpt = $previnfo[0]->infoOptionals;
	    	$info4thSub = $wpdb->get_results("SELECT subjectid FROM `ct_subject` WHERE subjectClass = $toClass AND subid = (SELECT subid FROM `ct_subject` WHERE subjectid = $prev4th) limit 1");

	    	$info4thSub = isset($info4thSub[0]) ? $info4thSub[0]->subjectid : '';


	    	if($prevOpt != '' && sizeof(json_decode($prevOpt)) > 0){
	    		$oldOpt = json_decode($prevOpt);

	    		$infoOptSub = $wpdb->get_results("SELECT subjectid FROM `ct_subject` WHERE subjectClass = $toClass AND subid IN (SELECT subid FROM `ct_subject` WHERE subjectid IN (".implode (", ", $oldOpt)."))");
	    		$subarr = array();
	    		foreach ($infoOptSub as $opt) {
	    			$subarr[] = $opt->subjectid;
	    		}
	    		$infoOptSub = json_encode($subarr);
	    	}else{
	    		$infoOptSub = '';
	    	}


	    	if($assignRoll == ''){
		    	$roll = $_POST['position'];

		    	if($_POST['format'] == 'even'){
		    		$newroll = $roll[$stdid]+$roll[$stdid];
		    	}elseif($_POST['format'] == 'odd'){
		    		if($roll[$stdid] != 1){ $newroll = $roll[$stdid] + ($roll[$stdid] - 1); }
		    		else{ $newroll = $roll[$stdid]; }
		    	}else{
		    		$newroll = $roll[$stdid];
		    	}


		    	if (sizeof($samesec) < 1) {
		    		if($_POST['format'] == 'even'){
			    		$newroll = ($lastRoll % 2 != 0) ? $lastRoll+1 : $lastRoll;
			    	}elseif($_POST['format'] == 'odd'){
			    		$newroll = ($lastRoll % 2 == 0) ? $lastRoll+1 : $lastRoll;
			    	}else{
			    		$newroll = $lastRoll;
			    	}
		    		$lastRoll = $newroll+1;
		    	}
				// $newroll = (isset($addroll) && $addroll != '') ? intval($addroll) + intval($newroll) : $newroll;
				$newroll = $_POST['setRoll'][$stdid];
		    }else{
		    	$newroll = $assignRoll;
		    }


	    	$previnfo = $previnfo[0];
	    	$insert = $wpdb->insert('ct_studentinfo', array(
		      'infoStdid' => $stdid,
		      'infoClass' => $toClass,
		      'infoSection' =>  $toSection,
		      'infoGroup' =>  $previnfo->infoGroup,
		      'infoRoll' => $newroll,
		      'infoYear' => $infoYear,
		      'infoOptionals' => $infoOptSub,
		      'info4thSub' => $info4thSub
		    ));
		    $message = ms3message($insert, 'Updated');
		    
	    }else{
	    	$infoid = $students[0]->infoid;
	    	$insert = $wpdb->update('ct_studentinfo', array( 'infoClass' => $toClass, 'infoYear' => $infoYear), array('infoid' => $infoid));
	    	$message = ms3message($insert, 'Updated');
	    }
    	if($insert){
	    	$update = $wpdb->update('ct_student', array( 'stdCurrentClass' => $toClass, 'stdCurntYear' => $infoYear), array('studentid' => $stdid));
	    }
		}
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

<div class="container-fluid maxAdminpages" style="padding-left: 0">

	<div class="row">
		<!-- Show Status message -->
  	<?php if(isset($message)){ ms3showMessage($message); } ?>

		<div class="col-md-12">
			<div class="panel panel-info">
			  <div class="panel-heading"><h3>CGPA Promotion<br><small>Promot students</small></h3></div>
			  <div class="panel-body">

					<form class="form-inline" action="" method="GET">
						<input type="hidden" name="page" value="cgpapromotion">

						<div class="form-group">
							<label>Class</label>
							<select id='resultClass' class="form-control" name="class" required>
								<?php
									if ($isTeacher) {
										$classQuery = $wpdb->get_results( $wpdb->prepare( "SELECT classid,className FROM ct_class WHERE classid IN (SELECT cgpaClass FROM ct_cgpa GROUP BY cgpaClass ORDER BY className ASC) AND classid IN ($teacherOfClass)", null ) );
									} else {
										$classQuery = $wpdb->get_results( "SELECT classid,className FROM ct_class WHERE classid IN (SELECT cgpaClass FROM ct_cgpa GROUP BY cgpaClass ORDER BY className ASC)" );
									}
									echo "<option value=''>Select Class</option>";

									foreach ($classQuery as $class) {
										echo "<option value='".$class->classid."'>".$class->className."</option>";
									}
								?>
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

						<div class="form-group" id="idRoll">
							<input class="form-control" type="text" name="roll" placeholder="Roll" style="width: 110px">
						</div>
						<div class="form-group">
							<input type="submit" name="creatId" value="Genarate List" class="btn btn-primary">
						</div>
					</form>
			  </div>
			</div>
		</div>

		<?php if(isset($_GET['syear'])){ ?>

		  <div id="printArea" class="col-md-12 printBG">

			  <div >

			  	<?php
			  		$year 		= $_GET['syear'];
			  		$class 		= $_GET['class'];
			  		$section 	= isset($_GET['section']) ? $_GET['section'] : '';
			  		$roll 		= isset($_GET['roll']) ? $_GET['roll'] : '';

			  		if (isset($_GET['syear'])) {

			  			$querry = "SELECT * FROM `ct_cgpa`
			  								LEFT JOIN ct_student ON ct_student.studentid = cgpaStudent
			  								LEFT JOIN ct_studentinfo ON studentid = infoStdid AND $class = infoClass AND '$year' = infoYear
												LEFT JOIN ct_class ON ct_studentinfo.infoClass = ct_class.classid
												LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId
												LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
												WHERE infoYear = '$year' AND infoClass = $class AND stdCurrentClass = $class AND stdCurntYear = '$year'";

							$querry .= ($roll != "") ? " AND infoRoll = $roll" : '';
							$querry .= ($section != "") ? " AND infoSection = $section" : '';
							$querry .= " ORDER BY cgpaSecPosi";
							$groupsBy = $wpdb->get_results($querry);

			  			
			  		}
			  		

			  		if($groupsBy){
			  			?>
			  			<form action="" method="post" class="form-inline">
			  				<input type="hidden" name="prevClass" value="<?= $class ?>">
			  				<div class="form-top">
			  					<div class="form-group">
										<select id="proClass" class="form-control" name="promotclass" required >
											<option value="">Select Class</option>
											<?php
												$classedQuery = $wpdb->get_results( "SELECT classid,className FROM ct_class" );
												foreach ($classedQuery as $classRes) {
													$id = $classRes->classid;
													$name = $classRes->className;
													$disable = ($class == $classRes->classid) ? 'disabled' : '';
													echo "<option value='$id'>$name</option>";
												}
											?>
										</select>
									</div>
									<div class="form-group">
										<select id="proSec" class="form-control" name="promotsection" required disabled>
											<option value="">Select Class First</option>
										</select>
									</div>
									<div class="form-group">
										<select class="form-control" name="infoYear" required>
											<option value="">Select Year</option>
											<?php for ($i=-2; $i < 3; $i++) { 
		                    $sec = (date("Y")-$i)."-".(date("Y")-($i-1));
		                    $selected = ($edit->stdCurntYear == $sec) ? 'selected' : '';
		                    ?>
		                      <option value="<?= $sec; ?>" <?= $selected; ?>><?= $sec; ?></option>
		                    <?php
		                  } ?>
		                  <?php for ($i=-2; $i < 3; $i++) { 
		                    $sec = (date("Y")-$i);
		                    $selected = ($edit->stdCurntYear == $sec) ? 'selected' : '';
		                    ?>
		                      <option value="<?= $sec; ?>" <?= $selected; ?>><?= $sec; ?></option>
		                    <?php
		                  } ?>
										</select>
									</div>
									<div class="form-group">
										<label> Add additional</label>
										<input style="width: 80px" class="form-control" type="number" name="addroll">
									</div>
									<div class="form-group">
										<select class="form-control" name="format">
											<option value="none">None</option>
											<option value="even">Even</option>
											<option value="odd">Odd</option>
										</select>
									</div>
									<div class="form-group">
										<label> Assign Roll</label>
										<input style="width: 80px" class="form-control" type="number" name="assignRoll">
									</div>
			  					<input class="btn btn-success pull-right" type="submit" name="promotStu" value="Promote">
			  				</div>
			  				<br>
			  				<table class="table table-responsive table-striped table-bordered">
			  					<tr>
			  						<th>#</th>
			  						<th>Name</th>
			  						<th>Roll</th>
			  						<th>Class</th>
			  						<th>Section</th>
			  						<th>Group</th>
			  						<th>Position</th>
									<th>Assign Roll</th>
			  						<th><label class="labelRadio">Select <input id="selectAll" type="checkbox"></label></th>
			  					</tr>
			  					<?php
			  					
			  					foreach ($groupsBy as $key => $value) {
									?>
										<tr>
					  					<td><?= $key+1 ?></td>
					  					<td><?= $value->stdName ?></td>
					  					<td><?= $value->infoRoll ?></td>
					  					<td><?= $value->className ?></td>
					  					<td><?= $value->sectionName ?></td>
					  					<td><?= $value->groupName ?></td>
					  					<td><?= $value->cgpaSecPosi ?> <?= $value->cgpaFaild != 0 ? "(".$value->cgpaFaild.")" : ''; ?></td>
					  					<td>
					  						<!-- <input type="hidden" name="info4thSub[<?= $value->studentid ?>]" value='<?= str_replace("\"","",$value->info4thSub); ?>'>
					  						<input type="hidden" name="infoOptionals[<?= $value->studentid ?>]" value='<?= str_replace("\"","",$value->infoOptionals); ?>'>
					  						<input type="hidden" name="infoGroup[<?= $value->studentid ?>]" value='<?= $value->infoGroup; ?>'> -->
					  						<input class="assignRoll" type="number" name="setRoll[<?= $value->studentid ?>]" data-position="<?= $value->cgpaSecPosi ?>" value="<?= $value->cgpaSecPosi ?>">
					  					</td>
										<td>
										<input type="hidden" name="position[<?= $value->studentid ?>]" value="<?= $value->cgpaSecPosi ?>">
										<label class="labelRadio">
											<input class="stdSel" type="checkbox" name="promotion[]" value="<?= $value->studentid ?>"> Select
										</label>
									</td>
					  				</tr>
									<?php
									}
									?>
								</table>
							</form>
							<?php

						}else{
							echo "<h3 class='text-center'>Result / Student not Found</h3>";
						}

			  	?>

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

<p id="theSiteURL" class="hidden"><?= get_template_directory_uri() ?></p>
<script type="text/javascript">
	(function($) {
	  var $siteUrl = $('#theSiteURL').text();

		$('#proClass').change(function() {
			$.ajax({
	      url: $siteUrl+"/inc/ajaxAction.php",
	      method: "POST",
	      data: { class : $(this).val(), type : 'getSection' },
	      dataType: "html"
	    }).done(function( msg ) {
	      $( "#proSec" ).html( msg );
	      $( "#proSec" ).prop('disabled', false);
	    });
		});


		$('#resultClass').change(function() {

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