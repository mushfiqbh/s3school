<?php
	require_once dirname(__DIR__) . '/functions/teacher-access.php';

	$current_user = wp_get_current_user();
	$teacherAccess = s3s_get_teacher_access_context();
	$is_teacher = $teacherAccess['is_teacher'];
	$teacher_record = $teacherAccess['teacher'];
	$restrictions_enabled = s3s_teacher_restrictions_enabled();
	$teacher_assignments = array(
		'subjects' => array(),
		'sections' => array(),
		'classes' => array(),
		'class_teacher_class' => null,
		'class_teacher_section' => null
	);
	$teacher_has_assigned_classes = false;
	$teacher_has_any_assignment = false;
	$delete_message = '';

	if ($restrictions_enabled && $is_teacher && $teacher_record) {
		$assigned_subjects = json_decode($teacher_record->tecAssignSub, true);
		$assigned_subjects = is_array($assigned_subjects) ? array_filter(array_map('intval', $assigned_subjects)) : array();

		$assigned_sections = json_decode($teacher_record->assignSection, true);
		$assigned_sections = is_array($assigned_sections) ? array_filter($assigned_sections) : array();

		$assigned_classes = array();
		if (!empty($assigned_subjects)) {
			$subjects_data = $wpdb->get_results(
				"SELECT DISTINCT subjectClass FROM ct_subject WHERE subjectid IN (" . implode(',', $assigned_subjects) . ")"
			);
			if ($subjects_data) {
				$assigned_classes = array_map('intval', array_column($subjects_data, 'subjectClass'));
			}
		}

		if (!empty($teacher_record->teacherOfClass)) {
			$assigned_classes[] = (int) $teacher_record->teacherOfClass;
		}

		$assigned_classes = array_values(array_unique($assigned_classes));

		$teacher_assignments = array(
			'subjects' => $assigned_subjects,
			'sections' => $assigned_sections,
			'classes' => $assigned_classes,
			'class_teacher_class' => !empty($teacher_record->teacherOfClass) ? (int) $teacher_record->teacherOfClass : null,
			'class_teacher_section' => !empty($teacher_record->teacherOfSection) ? (int) $teacher_record->teacherOfSection : null
		);

		$teacher_has_assigned_classes = !empty($assigned_classes);
		$teacher_has_any_assignment = $teacher_has_assigned_classes || !empty($assigned_sections) || !empty($assigned_subjects);
	}

	if (!$restrictions_enabled) {
		$teacher_assignments = array(
			'subjects' => array(),
			'sections' => array(),
			'classes' => array(),
			'class_teacher_class' => null,
			'class_teacher_section' => null
		);
		$teacher_has_assigned_classes = false;
		$teacher_has_any_assignment = false;
	}

	if (isset($_POST['deleteRes'])) {

		$class = $_POST['class'];
		$sec = $_POST['sec'];
		$year = $_POST['syear'];
		$exam = $_POST['exam'];
		$subject = $_POST['subject'];
		$students = $_POST['promotion'];

		$teacher_classes = !empty($teacher_assignments['classes']) ? array_map('intval', $teacher_assignments['classes']) : array();
		if ($restrictions_enabled && $is_teacher && $teacher_has_any_assignment && (empty($teacher_classes) || !in_array((int) $class, $teacher_classes, true))) {
			$delete_message = "<div class='alert alert-danger'>You do not have access to delete results for this class.</div>";
		} elseif (sizeof($students) > 0) {
			$qrry = "DELETE FROM `ct_result` WHERE resStudentId IN (" . implode(',', $students) .") AND resClass = $class AND resultYear = '$year' AND resExam = $exam";
			$qrry .= ($sec != '') ? " AND resSec = $sec" :'';
			$qrry .= ($subject != '') ? " AND resSubject = $subject" :'';
			$delete = $wpdb->query( $qrry );
			$message = ms3message($delete, 'Delete');
		}
	}

?>
<style>
.compact-filter-form {
	display: flex;
	flex-wrap: wrap;
	gap: 10px;
	align-items: flex-end;
	margin-bottom: 20px;
}

.filter-row {
	display: flex;
	flex-wrap: wrap;
	gap: 10px;
	align-items: flex-end;
	width: 100%;
}

.filter-field {
	flex: 1 1 auto;
	min-width: 140px;
	max-width: 200px;
	display: flex;
	flex-direction: column;
}

.filter-field label {
	margin-bottom: 3px;
	font-size: 13px;
	font-weight: 500;
	white-space: nowrap;
}

.filter-field .form-control {
	height: 32px;
	padding: 4px 8px;
	font-size: 13px;
	width: 100%;
}

.filter-field.row-break {
	flex-basis: 100%;
	height: 0;
	min-width: 100%;
	max-width: 100%;
	width: 100%;
	margin: 0;
	padding: 0;
}

.filter-btn {
	max-width: 100px;
	min-width: 80px;
}

@media (max-width: 768px) {
	.filter-field {
		flex: 1 1 calc(50% - 5px);
		min-width: calc(50% - 5px);
		max-width: calc(50% - 5px);
	}
	.filter-field.row-break {
		display: none;
	}
}

@media (max-width: 480px) {
	.filter-field {
		flex: 1 1 100%;
		min-width: 100%;
		max-width: 100%;
	}
}
</style>
<div class="panel panel-info">
	<div class="panel-heading">
		<h3>Delete Result</h3>
	</div>
	<div class="panel-body">
		<form action="" method="GET" class="compact-filter-form">
			<div class="filter-row">
				<input type="hidden" name="page" value="result">
				<input type="hidden" name="view" value="delete">
				<div class="filter-field">
					<label>Class *</label>
					<select id='resultClass' class="form-control" name="class" required>
					<?php

					$classQuery = $wpdb->get_results("SELECT classid,className FROM ct_class WHERE classid IN (SELECT examClass FROM ct_exam GROUP BY examClass ORDER BY className ASC)");

					if ($is_teacher && $teacher_has_any_assignment) {
						if (!empty($teacher_assignments['classes'])) {
							$allowed_classes = array_map('intval', $teacher_assignments['classes']);
							$classQuery = array_filter($classQuery, function ($class) use ($allowed_classes) {
								return in_array((int) $class->classid, $allowed_classes, true);
							});
						} else {
							$classQuery = array();
						}
					}

					echo "<option value=''>Select Class</option>";

					foreach ($classQuery as $class) {
						echo "<option value='" . $class->classid . "'>" . $class->className . "</option>";
					}

					if ($restrictions_enabled && $is_teacher && $teacher_has_any_assignment && !$teacher_has_assigned_classes) {
						echo "<option value='' disabled>No classes assigned to you</option>";
					}
							?>
					</select>
				</div>

				<div class="filter-field">
					<label>Section</label>
					<select id="resultSection" class="form-control" name="sec" disabled>
						<option disabled selected>Select Class First</option>
					</select>
				</div>

				<div class="filter-field">
					<label>Religion</label>
					<select class="form-control" name="religion">
						<option value="">All Religions</option>
						<option value="Muslim">Muslim</option>
						<option value="Hinduism">Hinduism</option>
						<option value="Buddist">Buddist</option>
						<option value="Christian">Christian</option>
					</select>
				</div>

				<div class="filter-field">
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

				<div class="filter-field">
					<label>Gender</label>
					<select class="form-control" name="gender">
						<option value="">All Genders</option>
						<option value="1">Male</option>
						<option value="0">Female</option>
						<option value="2">Other</option>
					</select>
				</div>

				<!-- Row Break for Desktop -->
				<div class="filter-field row-break"></div>

				<div class="filter-field">
					<label>Year/Session *</label>
					<select id='resultYear' class="form-control" name="syear" required disabled>
						<option disabled selected>Select Class First</option>
					</select>
				</div>

				<div class="filter-field">
					<label>Exam *</label>
					<select id="resultExam" class="form-control" name="exam" required disabled>
						<option disabled selected>Select Class First</option>
					</select>
				</div>

				<div class="filter-field">
					<label>Subject</label>
					<select id='resultSubject' class="form-control" name="subject" disabled>
						<option disabled selected>Select exam First</option>
					</select>
				</div>

				<div class="filter-field filter-btn">
					<input class="form-control btn-success" type="submit" name="" value="Go">
				</div>
			</div>
		</form>
	</div>

</div>

<?php if (!empty($delete_message)) { echo $delete_message; } ?>

<?php

if(isset($_GET['exam'])):
	$exam 	= $_GET['exam']; 
	$year 	= $_GET['syear']; 
	$class 	= $_GET['class'];
	$sec 		= isset($_GET['sec']) ? $_GET['sec'] : '' ;
	$sub 		= isset($_GET['subject']) ? $_GET['subject'] : '' ;
	$group 	= isset($_GET['group']) ? $_GET['group'] : ''; // Get selected group

	if ($is_teacher && $teacher_has_any_assignment) {
		$teacher_classes = !empty($teacher_assignments['classes']) ? array_map('intval', $teacher_assignments['classes']) : array();
		if (empty($teacher_classes) || !in_array((int) $class, $teacher_classes, true)) {
			echo "<div class='panel panel-danger'><div class='panel-body'><h4 class='text-danger'>You do not have access to this class.</h4></div></div>";
			return;
		}
	}

	// ReligionId mapping
    $religionMap = array(
        'Muslim'    => 1,
        'Hinduism'  => 2,
        'Buddist'   => 3,
        'Christian' => 4
    );
	
    $subject_info = $wpdb->get_row("SELECT religionId FROM ct_subject WHERE subjectid = $sub");
    $subCode = $subject_info->religionId ?? null;
    $religionFilter = '';
    if (isset($_GET['religion']) && !empty($_GET['religion'])) {
      $religion = $_GET['religion'];
      $religionFilter = " AND stdReligion = '$religion'";
    } else if ($subCode && in_array($subCode, array_values($religionMap))) {
        $religion = array_search($subCode, $religionMap);
        $religionFilter = " AND stdReligion = '$religion'";
    }

	?>

		<div id="printArea" class="col-md-12">
		  <div >

		  	<?php

		  		$qrey = "SELECT studentid,stdName,infoRoll,className,sectionName,groupName,examName";

		  		if($sub != ''){ $qrey .= ",subjectName"; }

		  		$qrey .= " FROM ct_student
	  				LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid AND ct_student.stdCurrentClass = ct_studentinfo.infoClass
						LEFT JOIN ct_class ON ct_studentinfo.infoClass = ct_class.classid
						LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId
						LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
						LEFT JOIN ct_exam ON ct_exam.examid = $exam";

				if($sub != ''){ $qrey .= " LEFT JOIN ct_subject ON ct_subject.subjectid = $sub"; }
					
				$qrey .= " WHERE stdCurntYear = '$year' AND stdCurrentClass = $class" . $religionFilter;

				if($sec != ''){ $qrey .= " AND infoSection = $sec"; }

				if($group != ''){ $qrey .= " AND infoGroup = $group"; }

				$qrey .= " AND studentid IN (SELECT resStudentId FROM `ct_result` WHERE resClass = $class AND resultYear = '$year' AND resExam = $exam";

  			if($sub != ''){ $qrey .= " AND resSubject = $sub"; }

  			if($sec != ''){ $qrey .= " AND resSec = $sec"; }
  			
  			$qrey .= ") ORDER BY infoRoll ASC";	  			
	  			$groupsBy = $wpdb->get_results($qrey);


		  		if($groupsBy){
		  			?>
		  			<form action="" method="post">
		  				<input type="hidden" name="exam" value="<?= $exam ?>">
		  				<input type="hidden" name="syear" value="<?= $year ?>">
		  				<input type="hidden" name="class" value="<?= $class ?>">
		  				<input type="hidden" name="sec" value="<?= $sec ?>">
		  				<input type="hidden" name="subject" value="<?= $sub ?>">
		  				<div class="text-right">
		  					<div class="pull-left text-left">
		  						Delete Result of Class: <?= $groupsBy[0]->className ?>, Section: <?= $groupsBy[0]->sectionName ?>, Exam: <?= $groupsBy[0]->examName ?>, Year: <?= $year ?>, Subject: <?= ($sub != '') ? $groupsBy[0]->subjectName : 'All'; ?>
		  					</div>
		  					<input class="btn btn-success" name="deleteRes" type="submit" value="Delete">
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
				  					<td>
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
						echo "<h3 class='text-center'>No Student Found</h3>";
					}

		  	?>

		  </div>
	  </div>
	<?php 
endif; ?>


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


    $('#resultExam').change(function() {
      var $siteUrl = $('#theSiteURL').text();

      $.ajax({
        url: $siteUrl+"/inc/ajaxAction.php",
        method: "POST",
        data: { exam : $(this).val(), type : 'getExamSubject' },
        dataType: "html"
      }).done(function( msg ) {
        $( "#resultSubject" ).html( msg );
        $( "#resultSubject" ).prop('disabled', false);
      });

    });
  })( jQuery );
</script>