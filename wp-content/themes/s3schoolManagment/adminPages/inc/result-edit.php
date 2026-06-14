<?php
	global $s3sRedux;

require_once dirname(__DIR__) . '/functions/teacher-access.php';

$teacherAccess = s3s_get_teacher_access_context();
$current_user = wp_get_current_user();
$user = $current_user;
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
if (isset($_POST['updateAllResult'])) {
	$cq = $_POST['CQ'];
	$mcq = $_POST['MCQ'];
	$prc = $_POST['P'];
	$ca = $_POST['ca'];
	$response = false;
	foreach ($_POST['id'] as $id) {
		$update = $wpdb->update(
		'ct_result',
			array(
				'resCQ' 		=> $cq[$id],
				'resMCQ' 		=> $mcq[$id],
				'resPrec' 	=> $prc[$id],
				'resCa' 	=> $ca[$id],
				'resTotal' 	=> isnum($cq[$id])+isnum($mcq[$id])+isnum($prc[$id])+isnum($ca[$id])
			),
			array( 'resultId' => $id)
		);
		if ($update) { $response = $update;	}
	}
	if ($response) {
		$message = array('status' => 'success', 'message' => 'Successfully updated' );
	}else{
		$message = array('status' => 'faild', 'message' => 'Something wrong please try again' );
	}

} ?> 

<?php

	if (isset($message)) {
		?>
			<div class="messageDiv">
				<div class="alert <?= ($message['status'] == 'success') ? 'alert-success' : 'alert-danger';  ?>">
					<?= $message['message'] ?>
				</div>
			</div>
		<?php
	}
?>

<div class="panel panel-info">
	<div class="panel-heading"><h3>Edit Result</h3></div>
	<div class="panel-body">
		<form action="" method="GET" class="form-inline">

			<div class="form-group">
				<input type="hidden" name="page" value="result">
				<input type="hidden" name="view" value="resultedit">
				<label>Class</label>
				<select id='resultClass' class="form-control" name="class" required>
					<?php

						$classQuery = $wpdb->get_results( "SELECT classid,className FROM ct_class WHERE classid IN (SELECT examClass FROM ct_exam GROUP BY examClass ORDER BY className ASC)" );
				
						// Filter classes if user is a teacher with explicit assignments
						if ($is_teacher && $teacher_has_any_assignment) {
							if (!empty($teacher_assignments['classes'])) {
								$allowed_classes = array_map('intval', $teacher_assignments['classes']);
								$classQuery = array_filter($classQuery, function($class) use ($allowed_classes) {
									return in_array((int) $class->classid, $allowed_classes, true);
								});
							} else {
								$classQuery = array();
							}
						}
						
						echo "<option value=''>Select Class</option>";

						foreach ($classQuery as $class) {
							echo "<option value='".$class->classid."'>".$class->className."</option>";
						}

						if ($is_teacher && $teacher_has_any_assignment && !$teacher_has_assigned_classes) {
							echo "<option value='' disabled>No classes assigned to you</option>";
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
				<select id="resultSection" class="form-control" name="sec" disabled>
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
				<label>Year/Session</label>
				<select id='resultYear' class="form-control" name="syear" required disabled>
					<option disabled selected>Select Class First</option>
				</select>
			</div>


			<div class="form-group">
				<label>Subject</label>
				<select id='resultSubject' class="form-control" name="subject" required disabled>
					<option disabled selected>Select exam First</option>
				</select>
			</div>

			<div class="form-group">
				<input class="form-control btn-success" type="submit" name="" value="Go">
			</div>
		</form>
	</div>

</div>

	<?php if(isset($_GET['class'])){

		if ($is_teacher && $teacher_has_any_assignment) {
			$teacher_classes = !empty($teacher_assignments['classes']) ? array_map('intval', $teacher_assignments['classes']) : array();
			if (empty($teacher_classes) || !in_array((int) $_GET['class'], $teacher_classes, true)) {
				echo "<div class='panel panel-danger'><div class='panel-body'><h4 class='text-danger'>You do not have access to this class.</h4></div></div>";
				return;
			}
		}
	?>

	<div class="panel panel-info">

	  <div class="panel-body">
			<div class="panel-group">
				<form action="" method="POST">

					<?php 
						
						$class		= $_GET['class'];
						$exam			= $_GET['exam'];
						$year			= $_GET['syear'];
						$sec			= isset($_GET['sec']) ? $_GET['sec'] : '';
						$subject  = $sub = $_GET['subject'];
						$grou 	= $_GET['grou'];

						// Religion subCode mapping
						$religionMap = array(
							'Muslim'    => 111,
							'Hinduism'  => 112,
							'Buddist'   => 113,
							'Christian' => 114
						);

						$subject_info = $wpdb->get_row("SELECT subCode FROM ct_subject WHERE subjectid = $sub");
						$subCode = $subject_info->subCode ?? null;
						$religionFilter = '';
						if ($subCode && in_array($subCode, array_values($religionMap))) {
							$religion = array_search($subCode, $religionMap);
							$religionFilter = " AND ct_student.stdReligion = '$religion'";
						}

						$query = "SELECT stdName,infoRoll,resultId,subjectName,assessment,resMCQ,resCQ,resPrec,resCa,subCQ,subMCQ,subPect,subCa FROM `ct_result`
							LEFT JOIN ct_student ON ct_student.studentid = ct_result.resStudentId
							LEFT JOIN ct_studentinfo ON ct_studentinfo.infoStdid = ct_result.resStudentId
							LEFT JOIN ct_subject ON ct_result.resSubject = ct_subject.subjectid
							WHERE resClass = $class AND resExam = $exam AND resultYear = '$year' AND resSubject = $subject AND status = 0" . $religionFilter;
						if ($sec != '' && $sec != 'all') { $query .= " AND infoSection = $sec"; }
						if ($grou != "") {
													$query .= " AND infoGroup = $grou";
												}
						$query .= " Group By resStudentId ORDER BY infoRoll ASC";

						$results = $wpdb->get_results($query);

						$canAdd = true;
						if (!in_array('editor', (array) $user->roles) && !in_array('administrator', (array) $user->roles) && $is_teacher) {
							$assigned_subjects = $teacher_assignments['subjects'];
							$has_subject_access = in_array((int) $sub, $assigned_subjects, true);

							$subject_class = (int) $wpdb->get_var($wpdb->prepare("SELECT subjectClass FROM ct_subject WHERE subjectid = %d", $sub));
							$has_class_teacher_access = ($teacher_assignments['class_teacher_class'] !== null && $teacher_assignments['class_teacher_class'] === $subject_class);

							if ($teacher_has_any_assignment && !$has_subject_access && !$has_class_teacher_access) {
								$canAdd = false;
							}
						}
						if($canAdd){
							if($results){
								?>

								<input type="hidden" name="subCQ" value="<?= $result->subCQ ?>">
								<input type="hidden" name="subMCQ" value="<?= $result->subMCQ ?>">
								<input type="hidden" name="subPect" value="<?= $result->subPect ?>">
								<input type="hidden" name="subCa" value="<?= $result->subCa ?>">
								<table class="table table-bordered table-striped">
									<tbody>
										<tr>
											<th>Student Name</th>
											<th>Roll</th>
											<th>Subject</th>
											<th style="<?= ($results[0]->subCQ == 0) ? 'display: none;' : ''; ?>"><?= $results[0]->assessment == 1? 'Hand Writing' : $s3sRedux['cqtitle'] ?> 	<?= "(".$results[0]->subCQ.")" ?></th>
											<th style="<?= ($results[0]->subMCQ == 0) ? 'display: none;' : ''; ?>"><?= $results[0]->assessment == 1? 'Attendence' : $s3sRedux['mcqtitle'] ?>  	<?= "(".$results[0]->subMCQ.")" ?></th>
											<th style="<?= ($results[0]->subPect == 0) ? 'display: none;' : ''; ?>"><?= $results[0]->assessment == 1? 'Neat & Clean' : $s3sRedux['prctitle'] ?> 	<?= "(".$results[0]->subPect.")" ?></th>
											<th style="<?= ($results[0]->subCa == 0) ? 'display: none;' : ''; ?>"><?= $results[0]->assessment == 1? 'Uniform' : $s3sRedux['catitle'] ?> 		<?= "(".$results[0]->subCa.")" ?></th>
										</tr>
										<?php

											foreach ($results as $result) {
												?>
												<tr>
													<input type="hidden" name="id[<?= $result->resultId ?>]" value="<?= $result->resultId ?>">
													<td><?= $result->stdName ?></td>
													<td><?= $result->infoRoll ?></td>
													<td><?= $result->subjectName ?></td>
													<td style="<?= ($result->subCQ == 0) ? 'display: none;' : ''; ?>">
														<input class="resultInput form-control" type="text" data-max="<?= $result->subCQ ?>" name="CQ[<?= $result->resultId ?>]" value="<?= $result->resCQ ?>" style="<?= ($result->subCQ == 0) ? 'display: none;' : ''; ?>">
													</td>
													<td style="<?= ($result->subMCQ == 0) ? 'display: none;' : ''; ?>">
														<input class="resultInput form-control" type="text" data-max="<?= $result->subMCQ ?>" name="MCQ[<?= $result->resultId ?>]" value="<?= $result->resMCQ ?>" style="<?= ($result->subMCQ == 0) ? 'display: none;' : ''; ?> <?= ($result->subMCQ == 0) ? 'readonly' : ''; ?>">
													</td>
													<td style="<?= ($result->subPect == 0) ? 'display: none;' : ''; ?>">
														<input class="resultInput form-control" type="text" data-max="<?= $result->subPect ?>" name="P[<?= $result->resultId ?>]" value="<?= $result->resPrec ?>" style="<?= ($result->subPect == 0) ? 'display: none;' : ''; ?> <?= ($result->subPect == 0) ? 'readonly' : ''; ?>">
													</td>
													<td style="<?= ($result->subCa == 0) ? 'display: none;' : ''; ?>">
														<input class="resultInput form-control" type="text" data-max="<?= $result->subCa ?>" name="ca[<?= $result->resultId ?>]" value="<?= $result->resCa ?>" style="<?= ($result->subCa == 0) ? 'display: none;' : ''; ?> <?= ($result->subCa == 0) ? 'readonly' : ''; ?>">
													</td>
												</tr>
												<?php  
											} 
										?>
									</tbody>
								</table>
								<div class="text-right">
									<input class="btn btn-success resultSubmit" type="submit" name="updateAllResult" value="Update">
								</div>
								<?php
							}else{ ?>
								<h2 class="text-center">Student not Found, Maybe result published.</h2>
								<?php 
							}
						}else{
							echo "<h3 class='text-center text-danger'>You are not allowed to edit result for this subject.</h3>";
						}
					?>
				</form>
			</div>
	  </div>
	</div>

<?php
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