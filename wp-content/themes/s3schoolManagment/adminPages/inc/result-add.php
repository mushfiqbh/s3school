<?php global $s3sRedux; 

// Check if current user is a teacher and get their assignments
$current_user = wp_get_current_user();
$is_teacher = ($current_user->roles[0] == 'um_teachers');
$teacher_assignments = null;

if ($is_teacher) {
    $teacher_record = $wpdb->get_row($wpdb->prepare("SELECT tecAssignSub, assignSection, teacherOfClass, teacherOfSection FROM ct_teacher WHERE tecUserId = %d", $current_user->ID));
    if ($teacher_record) {
        $assigned_subjects = json_decode($teacher_record->tecAssignSub, true);
        $assigned_sections = json_decode($teacher_record->assignSection, true);
        
        // Get unique classes from assigned subjects
        $assigned_classes = array();
        if (!empty($assigned_subjects)) {
            $subjects_data = $wpdb->get_results("SELECT DISTINCT subjectClass FROM ct_subject WHERE subjectid IN (" . implode(',', array_map('intval', $assigned_subjects)) . ")");
            $assigned_classes = array_column($subjects_data, 'subjectClass');
        }
        
        // Add class teacher assignments
        if (!empty($teacher_record->teacherOfClass)) {
            $assigned_classes[] = $teacher_record->teacherOfClass;
        }
        
        $teacher_assignments = array(
            'subjects' => $assigned_subjects ?: array(),
            'sections' => $assigned_sections ?: array(),
            'classes' => array_unique($assigned_classes) ?: array(),
            'class_teacher_class' => $teacher_record->teacherOfClass,
            'class_teacher_section' => $teacher_record->teacherOfSection
        );
    }
}
?>
<div class="panel panel-info">
	<div class="panel-heading"><h3>Add Result</h3></div>
	<div class="panel-body">
		<form action="" method="GET" class="form-inline">

			<div class="form-group">
				<input type="hidden" name="page" value="result">
				<label>Class</label>
				<select id='resultClass' class="form-control" name="class" required>
					<?php
						$classQuery = $wpdb->get_results( "SELECT classid,className FROM ct_class WHERE classid IN (SELECT examClass FROM ct_exam GROUP BY examClass ORDER BY className ASC)" );
						
						// Filter classes if user is a teacher
						if ($is_teacher && !empty($teacher_assignments['classes'])) {
							$classQuery = array_filter($classQuery, function($class) use ($teacher_assignments) {
								return in_array($class->classid, $teacher_assignments['classes']);
							});
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
			<select id="resultSection" class="form-control" name="sec" disabled>
				<option disabled selected>Select Class First</option>
			</select>
		</div>

		<div class="form-group ">
			<label>Group</label>
			<select id="resultGroup" class="form-control" name="group" disabled>
				<option value="">Select Class First</option>
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

<?php
if(isset($_GET['exam'])):
	$exam 	= $_GET['exam']; 
	$year 	= $_GET['syear']; 
	$class 	= $_GET['class'];
	$sub 		= $_GET['subject'];
	$sec 		= isset($_GET['sec']) ? $_GET['sec'] : '';
	$group 	= isset($_GET['group']) ? $_GET['group'] : ''; // Get selected group

	$info = $wpdb->get_results( "SELECT examName,className,subjectName,combineMark,connecttedPaper,subPaper,subOptinal,sub4th,subMCQ,subCQ,subPect,subCa FROM ct_subject
		LEFT JOIN ct_exam ON examid = $exam
		LEFT JOIN ct_class ON ct_exam.examClass = ct_class.classid
		WHERE subjectid = $sub" );

	$resCombineWith = $info[0]->connecttedPaper;
	$combineMark = $info[0]->combineMark;
	$resSubPaper		= $info[0]->subPaper;
	$subOpt 				= $info[0]->subOptinal;
	$sub4th 				= $info[0]->sub4th;

	$subMCQ 			= $info[0]->subMCQ;
	$subCQ 				= $info[0]->subCQ;
	$subPect 			= $info[0]->subPect;
	$subCa 				= $info[0]->subCa;

	$user = wp_get_current_user();
	$canAdd = true;
	if ( !in_array( 'editor', (array) $user->roles ) && !in_array( 'administrator', (array) $user->roles ) ) {
		$teacher_record = $wpdb->get_row($wpdb->prepare("SELECT tecAssignSub, teacherOfClass FROM ct_teacher WHERE tecUserId = %d", get_current_user_id()));

		if ($teacher_record) {
			$assigned_subjects = json_decode($teacher_record->tecAssignSub, true) ?: array();
			
			// Check if subject is directly assigned
			$has_subject_access = in_array($sub, $assigned_subjects);
			
			// Check if teacher is class teacher for this subject's class
			$subject_class = $wpdb->get_var($wpdb->prepare("SELECT subjectClass FROM ct_subject WHERE subjectid = %d", $sub));
			$has_class_teacher_access = (!empty($teacher_record->teacherOfClass) && $teacher_record->teacherOfClass == $subject_class);
			
			// Allow access if either condition is true
			if (!$has_subject_access && !$has_class_teacher_access) {
				$canAdd = false;
			}
		}
	}
	?>

	<div class="panel panel-info">
		<div class="panel-heading"><h3>Result</h3></div>
		<div class="panel-body">
			<?php if($canAdd){ ?>
				<div class="text-right">
					<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>
				</div>
				<form action="" method="POST">
			
					<div class="form-group">
						<input type="hidden" name="resExam" value='<?= $exam; ?>'>
						<input type="hidden" name="resSubject" value='<?= $sub; ?>'>
						<input type="hidden" name="resultYear" value='<?= $year; ?>'>
						<input type="hidden" name="resSubPaper" value='<?= $resSubPaper; ?>'>
						<input type="hidden" name="resclass" value='<?= $class; ?>'>

						<input type="hidden" name="resCombineWith" value='<?= $resCombineWith; ?>'>
						<input type="hidden" name="combineMark" value='<?= $combineMark; ?>'>
						<input type="hidden" name="resSubPaper" value='<?= $resSubPaper; ?>'>
						<input type="hidden" name="subCQ" value='<?= $subCQ; ?>'>
						<input type="hidden" name="subMCQ" value='<?= $subMCQ; ?>'>
						<input type="hidden" name="subPect" value='<?= $subPect; ?>'>
						<div id="printArea">
							<style type="text/css"> @page{ size: auto;  margin: 0px; } </style>
							<link rel="stylesheet" media="print" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" />
						  <div class="printArea" style="margin: 20px;">
								<h3>
									<b>Class:</b> <?= $info[0]->className ?>,
									<b>Exam:</b> <?= $info[0]->examName ?>,
									<b>Subject:</b> <?= $info[0]->subjectName ?>,
									<?php if (!empty($group)) {
										$groupInfo = $wpdb->get_var("SELECT groupName FROM ct_group WHERE groupId = $group");
										if ($groupInfo) {
											echo "<b>Group:</b> " . $groupInfo . ",";
										}
									} ?>
									<b>Year:</b> <?= $_GET['syear'] ?>
								</h3>

								<div class="table-responsive">
									<table id="resultInputTable" class="table table-bordered ">
										<tr>
											<th>Name</th>
											<th>Roll</th>
											<th>Group</th>
											<th>Sec</th>
											<th>Sub Type</th>
											<th><?= $s3sRedux['cqtitle'] ?> (<?= $subCQ ?>)</th>
											<th><?= $s3sRedux['mcqtitle'] ?> (<?= $subMCQ ?>)</th>
											<th><?= $s3sRedux['prctitle'] ?> (<?= $subPect ?>)</th>
											<th><?= $s3sRedux['catitle'] ?> (<?= $subCa ?>)</th>
										</tr>
									

										<?php
											if($subOpt == 0 && $sub4th == 0){
												$stdQuery = "SELECT studentid,infoRoll,stdName,groupName,infoGroup,infoSection,infoOptionals,info4thSub,sectionName FROM ct_student
													LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid
																									AND ct_studentinfo.infoClass = $class AND ct_studentinfo.infoYear = '$year' 
													LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId 
													LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid 
													WHERE studentid NOT IN
														(SELECT resStudentId FROM `ct_result` WHERE resClass = $class AND resultYear = '$year' AND resSubject = $sub AND resExam = $exam)
														AND stdCurntYear = '$year' AND stdCurrentClass = $class";

												if ($sec != "") {
													$stdQuery .= " AND infoSection = $sec";
												}
												if ($group != "") {
													$stdQuery .= " AND infoGroup = $group";
												}
												if($class == 41){
											 $stdQuery .= " ORDER BY groupName DESC, infoRoll ASC";
											}else{
											$stdQuery .= " ORDER BY infoRoll ASC";
											}

												
											}else{
												$stdQuery = "SELECT studentid,infoRoll,stdName,groupName,infoGroup,infoSection,infoOptionals,info4thSub,sectionName FROM ct_student
													LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid
																									AND ct_studentinfo.infoClass = $class AND ct_studentinfo.infoYear = '$year' 
													LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId 
													LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid 
													WHERE studentid NOT IN
														(SELECT resStudentId FROM `ct_result` WHERE resClass = $class AND resultYear = '$year' AND resSubject = $sub AND resExam = $exam)
														AND stdCurntYear = '$year' AND stdCurrentClass = $class";
												if ($subOpt == 1 && $sub4th == 1) {
													$stdQuery .= " AND (infoOptionals LIKE '%\"$sub\"%' OR info4thSub = $sub)";
												}
												if ($subOpt == 1 && $sub4th == 0) {
													$stdQuery .= " AND infoOptionals LIKE '%\"$sub\"%' ";
												}
												if ($subOpt == 0 && $sub4th == 1) {
													$stdQuery .= " AND info4thSub = $sub ";
												}
												if ($sec != "") {
													$stdQuery .= " AND infoSection = $sec";
												}
												if ($group != "") {
													$stdQuery .= " AND infoGroup = $group";
												}
											if($class == 41){
											 $stdQuery .= " ORDER BY groupName DESC, infoRoll ASC";
											}else{
											$stdQuery .= " ORDER BY infoRoll ASC";
											}
											}

											$stdQuery = $wpdb->get_results( $stdQuery );
							
											foreach ($stdQuery as $student) {
												if(!empty($student->infoOptionals)){
													$subOpt    = (in_array($sub, json_decode( $student->infoOptionals ))) ? 1 : 0;
												}else{
													$subOpt = 0;
												}
												$std4thSub = ($sub == $student->info4thSub) ? 1 : 0 ;
												?>
												<input type="hidden" name="stdids[]" value='<?= $student->studentid ?>'>
												<input type="hidden" name="roll[<?= $student->studentid ?>]" value='<?= $student->infoRoll ?>'>
												<input type="hidden" name="group[<?= $student->studentid ?>]" value='<?= $student->infoGroup ?>'>
												<input type="hidden" name="section[<?= $student->studentid ?>]" value='<?= $student->infoSection ?>'>
												<input type="hidden" name="optional[<?= $student->studentid ?>]" value='<?= $subOpt ?>'>
												<input type="hidden" name="sub4th[<?= $student->studentid ?>]" value='<?= $std4thSub ?>'>

												<tr>
													<td><?= $student->stdName ?></td>
													<td><?= $student->infoRoll ?></td>
													<td><?= $student->groupName ?></td>
													<td><?= $student->sectionName ?></td>
													<td><?php if($std4thSub == 1){ echo '4th Sub'; }elseif($subOpt == 1){ echo "Optional"; }  ?></td>
													<!-- if($std4thSub == 1){ echo '4th Sub'; }elseif($subOpt == 1){ echo "Optional"; } -->
													<td>
														<input style="width: 100px" class="resultInput form-control" type="text" data-max="<?= $subCQ ?>" name="cq[<?= $student->studentid ?>]" <?= ($subCQ == 0) ? 'readonly' : ''; ?>>
													</td>
													<td>
														<input style="width: 100px" class="resultInput form-control" type="text" data-max="<?= $subMCQ ?>" name="mcq[<?= $student->studentid ?>]" <?= ($subMCQ == 0) ? 'readonly' : ''; ?>>
													</td>
													<td>
														<input style="width: 100px" class="resultInput form-control" type="text" data-max="<?= $subPect ?>" name="prac[<?= $student->studentid ?>]" <?= ($subPect == 0) ? 'readonly' : ''; ?>>
													</td>
													<td>
														<input style="width: 100px" class="resultInput form-control" type="text" data-max="<?= $subCa ?>" name="ca[<?= $student->studentid ?>]" <?= ($subCa == 0) ? 'readonly' : ''; ?>>
													</td>
												</tr>
												<?php
											}
										?>
									</table>
									
									
									<?php if(!$stdQuery){ ?>
										<h3 class="text-center text-info">No Student Found for add the result</h3>
									<?php } ?>
								</div>
							</div>
						</div>
					</div>

					<?php if($stdQuery){ ?>
						<div class="form-group">
							<input name="addResult" class="form-control btn-success resultSubmit" type="submit" value="Add Result">
						</div>
					<?php } ?>
				</form>
			<?php }else{
				echo "<h3 class='text-center text-danger'>You are not allowed to add result for this subject.</h3>";
			} ?>
		</div>
	</div>

	<?php 
endif; ?>


<script type="text/javascript">
	function print(divId) {
    var printContents = document.getElementById(divId).innerHTML;
    w = window.open();
    w.document.write(printContents);
    w.document.write('<scr' + 'ipt type="text/javascript">' + 'window.onload = function() { window.print(); window.close(); };' + '</sc' + 'ript>');
    w.document.close();
    w.focus();
    return true;
  }

</script>