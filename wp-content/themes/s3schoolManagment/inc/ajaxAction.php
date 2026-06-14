<?php

// 	define( 'SHORTINIT', true );
    	require( '../../../../wp-load.php' );
    	ob_start();


// Initialize default POST keys to prevent notices
if (!isset($_POST['type'])) $_POST['type'] = '';
if (!isset($_POST['action'])) $_POST['action'] = '';
	
	
	require_once get_template_directory() . '/adminPages/functions/teacher-access.php';
	$applyTeacherRestrictions = s3s_teacher_restrictions_enabled();

	/*
		Check The Roll
	*/
	if($_POST['type'] == 'checkRoll'){
		$class 		= $_POST['class'];
		$section  = $_POST['section'];
		$year			= $_POST['year'];
		$roll			= $_POST['roll'];
		$std			= isset($_POST['std']) ? $_POST['std'] : '';

		$stdQuery = "SELECT `infoStdid` FROM `ct_studentinfo`
				WHERE `infoYear` = '$year' AND `infoClass` = $class AND `infoRoll` = $roll";

		if(isset($section) && !empty($section)){
			$stdQuery .= " AND `infoSection` = $section";
		}

		if($std != ''){
			$stdQuery .= " AND infoStdid != $std";
		}
		
		$student = $wpdb->get_results( $stdQuery );

		if(!empty($student)){
			echo 1;
		}else{
			echo 0;
		}
	}



	/*
		Get Optional and 4th sub Subject
	*/
	if($_POST['type'] == 'getOptionalSubject' || $_POST['type'] == 'getOpt4thSubjectByGroup'){
$class = $_POST['class'];
$group = ($_POST['group'] == '' || $_POST['group'] == 0) ? 'all' : $_POST['group'];

$religionMap = [
    'Muslim'    => 1,
    'Hinduism'  => 2,
    'Buddist'   => 3,
    'Christian' => 4,
    'other'     => 5
];

$religionName = $_POST['stdReligion'] ?? '';
$religionId = $religionMap[$religionName] ?? 0;

$religionCondition = ($religionId > 0) 
    ? "AND (religionId IS NULL OR religionId = 0 OR religionId = $religionId)"
    : "AND (religionId IS NULL OR religionId = 0)";

$subjects = $wpdb->get_results("
    SELECT subjectid, subjectName 
    FROM ct_subject 
    WHERE subjectClass = '$class' 
      AND subOptinal = 1 
      AND (forGroup IN ('$group', 'all') OR forGroup LIKE '%\"$group\"%') 
      $religionCondition
    ORDER BY subjectName
");





// 		$subjects = $wpdb->get_results( "SELECT subjectid,subjectName FROM ct_subject WHERE subjectClass = '$class' AND subOptinal = 1 AND (forGroup IN ('$group', 'all') OR forGroup LIKE '%\"$group\"%') ORDER BY subjectName" );

		if(!empty($subjects)){
			echo "<label>Optional Subject(s)</label><br>";
		}

		foreach ($subjects as $subjct) {
			?>
			<label class="labelRadio">
				<input type="checkbox" name="stdOptionals[]" value="<?= $subjct->subjectid; ?>" checked> <?= $subjct->subjectName; ?>
			</label>
			<?php
		}

		$subjects4th = $wpdb->get_results( "SELECT subjectid,subjectName FROM ct_subject WHERE subjectClass = '$class' AND sub4th = 1 AND (forGroup IN ('$group', 'all') OR forGroup LIKE '%\"$group\"%') ORDER BY subjectName" );

		if(!empty($subjects4th)){
			echo "<br><br><label>4th Subject</label><br>";
		}

		$first = true;
        foreach ($subjects4th as $subjct) {
            ?>
            <label class="labelRadio">
                <input type="checkbox" name="std4thsub[]" value="<?= $subjct->subjectid; ?>" <?= $first ? 'checked' : ''; ?>>
                <?= $subjct->subjectName; ?>
            </label>
            <?php
            $first = false;
        }
	}



	/*
		Get Section
	*/
	elseif($_POST['type'] == 'getSection'){

		$class = $_POST['class'];

		$current_user = wp_get_current_user();
		$sections_query = "SELECT sectionid,sectionName FROM ct_section WHERE forClass = '$class'";

		if ($applyTeacherRestrictions && $current_user->roles[0] == 'um_teachers') {
			$current_user_id = get_current_user_id();
			
			// Determine table name (try prefixed first, fallback to ct_teacher)
			$prefixed = $wpdb->prefix . 'ct_teacher';
			$exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $prefixed));
			$table = ($exists === $prefixed) ? $prefixed : 'ct_teacher';
			
			$teacher_record = $wpdb->get_row($wpdb->prepare("SELECT teacherOfClass, teacherOfSection, assignSection FROM $table WHERE tecUserId = %d", $current_user_id));

			$allowed_sections = array();

			// Add subject-assigned sections
			if ($teacher_record && !empty($teacher_record->assignSection)) {
				$assigned_sections = json_decode($teacher_record->assignSection, true);
				$has_all = false;
				if (is_array($assigned_sections) && !empty($assigned_sections)) {
					if (in_array('all', $assigned_sections)) {
						$has_all = true;
						$assigned_sections = array_diff($assigned_sections, ['all']);
					}
					if (!empty($assigned_sections)) {
						$allowed_sections = array_merge($allowed_sections, $assigned_sections);
					}
				}
			}

			// Add class teacher section (only if it matches the requested class)
			if ($teacher_record && !empty($teacher_record->teacherOfClass) && !empty($teacher_record->teacherOfSection) && $teacher_record->teacherOfClass == $class) {
				$allowed_sections[] = $teacher_record->teacherOfSection;
			}

			// If we have any allowed sections, filter the query
			if ($has_all) {
				// Show all sections
			} elseif (!empty($allowed_sections)) {
				$allowed_sections = array_unique($allowed_sections);
				$sections_query .= " AND sectionid IN (" . implode(',', array_map('intval', $allowed_sections)) . ")";
			}
		}

		$sections_query .= " ORDER BY sectionName";

		$sections = $wpdb->get_results($sections_query);
		if(!empty($sections)){
			echo "<option value=''>Section</option>";
			foreach ($sections as $section) {
				?>
				<option value="<?= $section->sectionid ?>"><?= $section->sectionName ?></option>
				<?php
			}
		}else{
			echo "<option value=''>No sections available</option>";
		}
	}

	/*
		Get Groups by Class
	*/
	elseif($_POST['type'] == 'getGroupsByClass'){
		$class = $_POST['class'];
		
		$current_user = wp_get_current_user();
		$groups_query = "SELECT DISTINCT ct_group.groupId, ct_group.groupName 
			FROM ct_group 
			INNER JOIN ct_studentinfo ON ct_studentinfo.infoGroup = ct_group.groupId 
			WHERE ct_studentinfo.infoClass = '$class'";

		if ($applyTeacherRestrictions && $current_user->roles[0] == 'um_teachers') {
			$current_user_id = get_current_user_id();
			$teacher_record = $wpdb->get_row($wpdb->prepare("SELECT tecAssignSub FROM ct_teacher WHERE tecUserId = %d", $current_user_id));

			if ($teacher_record && !empty($teacher_record->tecAssignSub)) {
				$assigned_subjects = json_decode($teacher_record->tecAssignSub, true);
				if (is_array($assigned_subjects) && !empty($assigned_subjects)) {
					// Get groups that have subjects assigned to this teacher
					$groups_query .= " AND ct_studentinfo.infoGroup IN (
						SELECT DISTINCT forGroup 
						FROM ct_subject 
						WHERE subjectid IN (" . implode(',', array_map('intval', $assigned_subjects)) . ") 
						AND subjectClass = '$class'
						AND forGroup != 'all'
					)";
				}
			}
		}

		$groups_query .= " ORDER BY ct_group.groupName ASC";
		
		$groups = $wpdb->get_results($groups_query);
		
		echo "<option value=''>All Groups</option>";
		foreach ($groups as $group) {
			?>
			<option value="<?= $group->groupId ?>"><?= $group->groupName ?></option>
			<?php
		}
	}



	/*
		Get Exam
	*/
	elseif($_POST['type'] == 'getExams'){
		$class = $_POST['class'];
		$exams = $wpdb->get_results( "SELECT examid,examName FROM ct_exam WHERE examClass = '$class'" );
		if(empty($exams)){
			echo "<option value=''>No Exam for this Class</option>";
		}else{
			echo "<option value=''>Select An Exam</option>";
		}
		foreach ($exams as $exam) {
			?>
			<option value="<?= $exam->examid ?>"><?= $exam->examName ?></option>
			<?php
		}
	}

	/*
		Get active Exam for fee collecton
	*/
	elseif($_POST['type'] == 'getActiveExam'){
		$class = $_POST['class'];
		$exams = $wpdb->get_results( "SELECT examid FROM ct_exam WHERE examClass = '$class' AND active_for_collection=1" );
		if(empty($exams)){
			echo '';
		}else{
			echo $exams[0]->examid;
		}
	}


	/*
		Get Year
	*/
	elseif($_POST['type'] == 'getYears'){
		$class = $_POST['class'];
		$years = $wpdb->get_results( "SELECT infoYear FROM ct_studentinfo WHERE infoClass = $class GROUP BY infoYear ORDER BY infoYear ASC" );
		if(empty($years)){
			echo "<option value=''>No Student In this class</option>";
		}else{
			echo "<option value=''>Year</option>";
		}
		foreach ($years as $year) {
			?>
			<option value="<?= $year->infoYear ?>"><?= $year->infoYear ?></option>
			<?php
		}
	}



	/*
		Get Subject
	*/
	elseif($_POST['type'] == 'getSubject'){
		$class = $_POST['class'];

		$subjects = $wpdb->get_results( "SELECT subjectid,	subjectName FROM ct_subject WHERE subjectClass = '$class'" );
		if(empty($subjects)){
			echo "<option value=''>No subject!</option>";
		}else{
			echo "<option value=''>Select Subject</option>";
		}

		foreach ($subjects as $subject) {
			?>
			<option value="<?= $subject->subjectid ?>"><?= $subject->subjectName ?></option>
			<?php
		}
	}

	/*
		Get Subjects
	*/
	elseif($_POST['type'] == 'getSubjects'){
		$class1 = $_POST['class1'];
		$class2 = $_POST['class2'];

		$subjects = $wpdb->get_results( "SELECT subjectid, subjectName FROM ct_subject WHERE subjectClass IN ($class1,$class2)" );

		?>
		
			<ul class="list-unstyled list-inline">
				<?php
				foreach ($subjects as $key => $subject) {
					$num = $key+1;
					?>
						<li style="width: 49%">
							<label class="labelRadio">
								<input type="checkbox" name="subjects[]" value="<?= $subject->subjectid ?>"> <?= $subject->subjectName ?>
							</label>
						</li>
					<?php
				}
				?>
			</ul>
				
		<?php
	}


	/*
		Get Subject of exam
	*/
	elseif ($_POST['type'] == 'getExamSubject') {
    $exam = intval($_POST['exam']); // Always sanitize
    $group = isset($_POST['group']) ? $_POST['group'] : ''; // Get selected group
    $subjects = []; // ✅ Initialize so foreach doesn't break later

    $subs = $wpdb->get_results("SELECT examSubjects FROM ct_exam WHERE examid = $exam");

    if (!empty($subs[0]->examSubjects)) {
        $subs = json_decode($subs[0]->examSubjects, true);
    } else {
        $subs = [];
    }

    $current_user = wp_get_current_user();

	if ($applyTeacherRestrictions && $current_user->roles[0] == 'um_teachers') {
        $current_user_id = get_current_user_id();
        $teacher_record = $wpdb->get_row($wpdb->prepare("SELECT tecAssignSub FROM ct_teacher WHERE tecUserId = %d", $current_user_id));

        if ($teacher_record && !empty($teacher_record->tecAssignSub)) {
            $assigned_subjects = json_decode($teacher_record->tecAssignSub, true);

            if (is_array($assigned_subjects) && !empty($assigned_subjects) && !empty($subs)) {
                // Filter exam subjects to only include assigned subjects
                $subs = array_intersect($subs, $assigned_subjects);
            }
        }
    }

    if (!empty($subs)) {
        $subs_escaped = array_map('intval', $subs);
        $subjectQuery = "SELECT subjectid,subjectName FROM ct_subject 
             WHERE subjectid IN (" . implode(',', $subs_escaped) . ")";
        
        // Filter by group if selected
        if (!empty($group)) {
            $subjectQuery .= " AND (forGroup = 'all' OR forGroup = '$group' OR forGroup LIKE '%\"$group\"%')";
        }
        
        $subjectQuery .= " ORDER BY subjectName ASC";
        
        $subjects = $wpdb->get_results($subjectQuery);
    }

    if (empty($subjects)) {
        echo "<option value=''>No subject!</option>";
    } else {
        echo "<option value=''>Select Subject</option>";
        foreach ($subjects as $subject) {
            echo '<option value="' . $subject->subjectid . '">' . $subject->subjectName . '</option>';
        }
    }
}



	/*
		Get All Student By Class
	*/
	elseif($_POST['type'] == 'getAllStudentByClass'){
		$class 		= $_POST['class'];
		$year 		= $_POST['year'];
		$section 	= $_POST['section'];
		?>
			<table class="table table-bordered table-responsive">
				<thead>
					<tr>
						<th>Name</th>
						<th>Group</th>
						<th>Roll</th>
						<th>Action</th>
					</tr>
				</thead>
				<tbody>
					<?php
					$url = str_replace("wp-content/themes/s3schoolManagment","", $_POST['siteUrl']);

					$students = $wpdb->get_results( "SELECT studentid,studentid,stdName,stdRoll,groupName FROM ct_student
						LEFT JOIN ct_group ON ct_student.stdGroup = ct_group.groupId 
						WHERE ct_student.stdAdmitClass = $class AND ct_student.stdCurntYear = '$year' AND ct_student.stdSection = $section AND ct_student.stdStatus = 1 ORDER BY ct_student.stdRoll ASC" );

					foreach ($students as $student) {
						?>
						<tr>
							<td><?= $student->stdName; ?></td>
							<td><?= $student->groupName; ?></td>
							<td><?= $student->stdRoll; ?></td>
							<td>

								<form class="pull-right actionForm" method="POST" action="">
				        	<input type="hidden" name="id" value="<?= $student->studentid; ?>">
				        	<a href="?page=student&option=view&id=<?= $student->studentid; ?>" class="btn-link">
				        		<span class="dashicons dashicons-visibility"></span></span>
				        	</a>

				        	<button type="submit" class="btn-link btnDelete" name="deleteStudent">
				        		<span class="dashicons dashicons-trash"></span>
				        	</button>
				        </form>

				        <!-- <form class="pull-right  actionForm" method="GET" action="">
				        	<input type="hidden" name="page" value="student">
				        	<input type="hidden" name="option" value="add">
				        	<input type="hidden" name="edit" value="<?= $student->studentid; ?>">
									<button type="submit" class="btn-link">

				        		<span class="dashicons dashicons-welcome-write-blog"></span></span>

				        	</button>
								</form> -->

							</td>
						</tr>
						<?php
					}
					?>
				</tbody>
			</table>
		<?php
	}





/*
	Get Yeasr Or Section
*/
elseif($_POST['type'] == 'getYearSection'){
	$classid = $_POST['class'];

	$subs = $wpdb->get_results( "SELECT session FROM ct_class WHERE classid = $classid LIMIT 1" );
	$session = $subs[0]->session;

	$options = '';
    $currentYear = date("Y");
	if($session == 'year'){
		for ($i=-2; $i < 2; $i++) {
		     $sec = (date("Y")-$i);
            $selected = ($currentYear == $sec) ? 'selected' : '';
      
      $options .= "<option value='$sec' $selected>$sec</option>";
    } 
	}else{
	    $currentYear = date("Y")."-".(date("Y")+1);
		for ($i=-2; $i < 2; $i++) { 
      $sec = (date("Y")-($i+1))."-".(date("Y")-$i);
      $selected = ($currentYear == $sec) ? 'selected' : '';
      $options .= "<option value='$sec' $selected>$sec</option>";
    } 
	}

	echo $options;

}

/*
	Get Yeasr Or Section
*/
elseif($_POST['type'] == 'getStudentInfo'){
	
		$class 		= $_POST['class'];
		$section  = $_POST['section'];
		$year			= $_POST['year'];
		$roll			= $_POST['roll'];
		$group			= $_POST['group'];
		$admissionFeeSubHeadId	= $_POST['admissionFeeSubHeadId'];
		$admissionFormSubHeadId	= $_POST['admissionFormSubHeadId'];
		$monthlyFeeSubHeadId	= $_POST['monthlyFeeSubHeadId'];
		$transportFeeSubHeadId	= $_POST['transportFeeSubHeadId'];
		$coachingFeeSubHeadId	= $_POST['coachingFeeSubHeadId'];
		$registrationFeeSubHeadId	= $_POST['registrationFeeSubHeadId'];
		$fee_month			= $_POST['month'] == '' ? 0 : $_POST['month'];
		$std			= isset($_POST['std']) ? $_POST['std'] : '';
		// print_r($fee_month);exit;
	$studentId = 0;
	$stdQuery = "SELECT ct_studentinfo.infoStdid, ct_student.stdName, ct_student_wise_fee.transport_fee_id, ct_student_wise_fee.transport_type,ct_student_wise_fee.transport_required, ct_student.admission_type, ct_student.facilities FROM ct_studentinfo
	LEFT JOIN ct_student ON ct_student.studentid = ct_studentinfo.infoStdid
	LEFT JOIN ct_student_wise_fee ON ct_student.studentid = ct_student_wise_fee.student_id AND $class = ct_student_wise_fee.class_id AND '$year' = ct_student_wise_fee.year  AND 3 = ct_student_wise_fee.fee_type
	WHERE  ct_studentinfo.infoYear = '$year' AND ct_studentinfo.infoRoll = $roll AND ct_studentinfo.infoClass = $class";
	if(isset($section) && !empty($section)){
		$stdQuery .= " AND ct_studentinfo.infoSection = $section";
	}
	if(isset($group) && !empty($group)){
		$stdQuery .= " AND ct_studentinfo.infoGroup = $group";
	}
	$stdQuery .= " LIMIT 1";
	$result = [];

	
	 $studentInfo = $wpdb->get_results( $stdQuery );
	 if($studentInfo){
		$result['student_name']=$studentInfo[0]->stdName;
		$result['student_id']=$studentInfo[0]->infoStdid;
		$studentId  = $result['student_id'];
	 }else{
		$result['success'] = 'false';
	 }

	//  get active collection sub head id
	$subHeadId = $wpdb->get_results("SELECT * FROM ct_sub_head
	WHERE  active_for_collection = 1  AND relation_to = 1 and isHidden is null ORDER BY sub_head_name ASC");

	// Pre-fetch already-paid yearly and exam fees from collection_details as safety net
	$yearlyPaidInCollection = array();
	$examPaidInCollection = array();
	if($studentId > 0){
		$yQuery = "SELECT DISTINCT cd.sub_head_id FROM ct_student_fee_collection_details cd
			LEFT JOIN ct_student_fee_collection_info ci ON ci.id = cd.info_id
			LEFT JOIN ct_sub_head sh ON sh.id = cd.sub_head_id
			WHERE ci.class_id = $class AND ci.year = '$year' 
			AND ci.student_id = $studentId AND sh.type = 2";
		if(isset($section) && !empty($section)){ $yQuery .= " AND ci.section = $section"; }
		if(isset($group) && !empty($group)){ $yQuery .= " AND ci.group_id = $group"; }
		$yearlyPaidInCollection = $wpdb->get_col($yQuery);
		if(!$yearlyPaidInCollection) $yearlyPaidInCollection = array();
		
		$eQuery = "SELECT DISTINCT cd.sub_head_id FROM ct_student_fee_collection_details cd
			LEFT JOIN ct_student_fee_collection_info ci ON ci.id = cd.info_id
			LEFT JOIN ct_sub_head sh ON sh.id = cd.sub_head_id
			WHERE ci.class_id = $class AND ci.year = '$year' 
			AND ci.student_id = $studentId AND sh.type = 3";
		if(isset($section) && !empty($section)){ $eQuery .= " AND ci.section = $section"; }
		if(isset($group) && !empty($group)){ $eQuery .= " AND ci.group_id = $group"; }
		$examPaidInCollection = $wpdb->get_col($eQuery);
		if(!$examPaidInCollection) $examPaidInCollection = array();
	}

	foreach($subHeadId as $val){
	//  NOTES: NEED TO SAVE DUE MONTH AND YEAR WISE
	// $fees = $wpdb->get_results("SELECT fee FROM ct_student_fee_list WHERE sub_head_id = $val->id AND class_id = $GLOBALS[class] AND year = $GLOBALS[year]");
	$feesQuery = "SELECT fee FROM ct_student_fee_list WHERE sub_head_id = $val->id AND class_id = $class AND year = '$year' ";

	// No need section for fee
	// if(isset($section) && !empty($section)){
	// 	$feesQuery .= " AND section = $section";
	// }

	if(isset($group) && !empty($group)){
		$feesQuery .= " AND group_id = $group";
	}
	$feesQuery .= "  ORDER BY id DESC";
	$fees = $wpdb->get_results($feesQuery);
	

	if($fees){
		$fees = $fees[0]->fee;
	}else{
		$fees = 0;
	}
	if($val->type == 1){
		// monthly
		$sumOfFees = 0;
		$fee_month_list = [];
		for($i = $fee_month; $i>=1; $i--){
			$feeInfoQuery = "SELECT fee FROM ct_student_monthly_fee_summary WHERE sub_head_id = $val->id AND class_id = $class AND year = '$year' AND month = $i AND student_id = $studentId";
			if(isset($section) && !empty($section)){
				$feeInfoQuery .= " AND section = $section";
			}
			if(isset($group) && !empty($group)){
				$feeInfoQuery .= " AND group_id = $group";
			}
			
			$feeInfo = $wpdb->get_results($feeInfoQuery);
			if(!$feeInfo){
				if($val->id == $monthlyFeeSubHeadId){
					if($studentInfo[0]->facilities == 'Full free' || $studentInfo[0]->facilities == 'Scholarship'){
						// $sumOfFees += 0;
					} else if($studentInfo[0]->facilities == 'Half free' ){
						$sumOfFees += ($fees/2);
					}else{
						// check student wise monthly fee
						
						$checkfees = "SELECT monthly_fee FROM ct_student WHERE studentid = $studentId";
						
						$studentwisefees = $wpdb->get_results($checkfees);
						if($studentwisefees[0]->monthly_fee > 0){						
							$fees = $studentwisefees[0]->monthly_fee;
						}
						$sumOfFees += $fees;
						
					}
				}else if($val->id == $transportFeeSubHeadId){
					if($studentInfo[0]->transport_required == 1){
						
						
						$transport_fee_id = $studentInfo[0]->transport_fee_id;
						
						$feesquery = "SELECT amount FROM ct_transport_fee_list WHERE id = $transport_fee_id";
						$fees = $wpdb->get_results($feesquery);
						if($fees){
							
							$fees = $fees[0]->amount;
							
							if($studentInfo[0]->transport_type == 1){ 
								// one way
								$fees = $fees/2;
							}
						}else{
							$fees = 0;
						}
						$sumOfFees += $fees;
					}else{
						$sumOfFees += 0;
					}
				}else if($val->id == $coachingFeeSubHeadId){
					$checkfees = "SELECT amount FROM ct_student_wise_fee WHERE fee_type = 1 AND student_id = $studentId  AND class_id = $class AND year = '$year'";
					if(isset($section) && !empty($section)){
						$checkfees .= " AND section = $section";
					}
					if(isset($group) && !empty($group)){
						$checkfees .= " AND group_id = $group";
					}
					$studentwisefees = $wpdb->get_results($checkfees);
					if($studentwisefees && $studentwisefees[0]->amount > 0){						
						$fees = $studentwisefees[0]->amount;
					}else{
						$fees = 0;
					}
					$sumOfFees += $fees;
					
				}else{
					$sumOfFees += $fees;
				}
				$fee_month_list[] = $i;
			}
		}
		// print_r($fee_month_list);exit;
		$result['month_list'] = $fee_month_list;
		$result['subheadid'.$val->id] = $sumOfFees;
	}else if($val->type == 2){
		// yearly — skip if already paid (check summary + collection_details as safety net)
		
		$feeInfoQuery = "SELECT fee FROM ct_student_yearly_fee_summary WHERE sub_head_id = $val->id AND class_id = $class AND year = '$year' AND student_id = $studentId";
		if(isset($section) && !empty($section)){
			$feeInfoQuery .= " AND section = $section";
		}
		if(isset($group) && !empty($group)){
			$feeInfoQuery .= " AND group_id = $group";
		}
		$feeInfo = $wpdb->get_results($feeInfoQuery);
			if(!$feeInfo && !in_array($val->id, $yearlyPaidInCollection)){
				// check admission fee for new or promoted student
				if( $val->id == $admissionFeeSubHeadId){
					if($studentInfo[0]->admission_type == 1){
					    if($studentInfo[0]->facilities == 'Half free' ){
						    $fees = ($fees/2);
    					}else{
    						$fees = $fees;
    					}
						$result['subheadid'.$val->id] = $fees;
					}else{
						$feesquery = "SELECT amount FROM ct_admission_fee_promoted WHERE class = $class";
						$fees = $wpdb->get_results($feesquery);
						if($fees){	
						    $fees = $fees[0]->amount;
						    if($studentInfo[0]->facilities == 'Half free' ){
						        $fees = ($fees/2);
        					}
							
						}else{
							$fees = 0;
						}
						$result['subheadid'.$val->id] = $fees;
					}
			    }else if( $val->id == $admissionFormSubHeadId){
					    if($studentInfo[0]->facilities == 'Half free' ){
						    $fees = ($fees/2);
    					}else{
    						$fees = $fees;
    					}
						$result['subheadid'.$val->id] = $fees;
				}else if($val->id == $registrationFeeSubHeadId){
					$checkfees = "SELECT amount FROM ct_student_wise_fee WHERE fee_type = 2 AND student_id = $studentId  AND class_id = $class AND year = '$year'";
					if(isset($section) && !empty($section)){
						$checkfees .= " AND section = $section";
					}
					if(isset($group) && !empty($group)){
						$checkfees .= " AND group_id = $group";
					}
					$studentwisefees = $wpdb->get_results($checkfees);
					if($studentwisefees && $studentwisefees[0]->amount > 0){						
						$fees = $studentwisefees[0]->amount;
					}else{
					   // if($studentInfo[0]->facilities == 'Half free' ){
						  //  $fees = ($fees/2);
    				// 	}else{
    				// 		$fees = $fees;
    				// 	}
    				$fees = $fees;
					}
					$result['subheadid'.$val->id] = $fees;
				}else{
					$result['subheadid'.$val->id] = $fees;
				}
				
			}
	}else if($val->type == 3){
		// exam — skip if already paid (check summary + collection_details as safety net)
		// get active exam id
		$activeExamId = $wpdb->get_results("SELECT examid FROM ct_exam WHERE active_for_collection = 1 AND examClass = $class LIMIT 1");
		if($activeExamId){
			$activeExamId = $activeExamId[0]->examid;
			$feeInfoQuery = "SELECT fee FROM ct_student_exam_fee_summary WHERE sub_head_id = $val->id AND class_id = $class AND exam_id = $activeExamId AND year = '$year' AND student_id = $studentId";
			if(isset($section) && !empty($section)){
				$feeInfoQuery .= " AND section = $section";
			}
			if(isset($group) && !empty($group)){
				$feeInfoQuery .= " AND group_id = $group";
			}
			$feeInfo = $wpdb->get_results($feeInfoQuery);

			if(!$feeInfo && !in_array($val->id, $examPaidInCollection)){
				$result['subheadid'.$val->id] = $fees;
			}	
		}else{
			$result['subheadid'.$val->id] = 0;//exam fee o
		}
		
	}else if($val->type == 4){
		// OTHER
		$result['subheadid'.$val->id] = $fees;				
	}
	// echo '<pre>'; print_r($result);exit;
	// print_r( $fees[0]->fee);exit;
	// $fees =  getDefaultFee($val->id,$class, $year, $group );

 }
	
	


	 echo json_encode( $result);
	//  echo  $studentInfo[0]->stdName;
	// return json_encode( $studentInfo);

}
elseif($_POST['type'] == 'getStudentFeeAmount'){
		$class 		= $_POST['class'];
		$year			= $_POST['year'];
		$group			= $_POST['group'];
	
	$result = [];

	
	
	//  get active collection sub head id
	$subHeadId = $wpdb->get_results("SELECT * FROM ct_sub_head
	WHERE  relation_to = 1 and isHidden is null ORDER BY sub_head_name ASC");
	// echo '<pre>';
	// print_r($subHeadId);exit;
	foreach($subHeadId as $val){
		 
	//  NOTES: NEED TO SAVE DUE MONTH AND YEAR WISE
	// $fees = $wpdb->get_results("SELECT fee FROM ct_student_fee_list WHERE sub_head_id = $val->id AND class_id = $GLOBALS[class] AND year = $GLOBALS[year]");
	$feesQuery = "SELECT fee FROM ct_student_fee_list WHERE sub_head_id = $val->id AND class_id = $class AND year = '$year' ";

	// No need section for fee
	// if(isset($section) && !empty($section)){
	// 	$feesQuery .= " AND section = $section";
	// }

	if(isset($group) && !empty($group)){
		$feesQuery .= " AND group_id = $group";
	}
	$feesQuery .= "  ORDER BY id DESC";
	$fees = $wpdb->get_results($feesQuery);
	if($fees){
		$fees = $fees[0]->fee;
	}else{
		$fees = 0;
	}
		// OTHER
		$result['subheadid'.$val->id] = $fees;				
	
	// echo '<pre>'; print_r($result);exit;
	// print_r( $fees[0]->fee);exit;
	// $fees =  getDefaultFee($val->id,$class, $year, $group );

 }
	
	


	 echo json_encode( $result);
	//  echo  $studentInfo[0]->stdName;
	// return json_encode( $studentInfo);

}elseif($_POST['type'] == 'hasGroup'){
		$class = $_POST['class'];
		
		$classInfo = $wpdb->get_results( "SELECT havegroup FROM ct_class WHERE classid = '$class' " );

		if (!empty($classInfo) && $classInfo[0]->havegroup == 1) {
            echo "true";
        } else {
            echo "false";
        }

	/*
		Get Optional and 4th sub Subject
	*/
}


/*
	Get Exam Subjects as JSON (for schedule page)
*/
elseif ($_POST['type'] == 'getExamSubjectsJson') {
	$exam = intval($_POST['exam']);
	$subjects = array();

	$subs_raw = $wpdb->get_var($wpdb->prepare("SELECT examSubjects FROM ct_exam WHERE examid = %d", $exam));
	if (!empty($subs_raw)) {
		$subs = json_decode($subs_raw, true);
	} else {
		$subs = array();
	}

	if (!empty($subs) && is_array($subs)) {
		$subs_escaped = array_map('intval', $subs);
		$placeholders = implode(',', $subs_escaped);
		$subjects = $wpdb->get_results(
			"SELECT subjectid, subjectName FROM ct_subject WHERE subjectid IN (" . $placeholders . ") ORDER BY subid"
		);
	}

	header('Content-Type: application/json');
	echo json_encode($subjects);
}


/*
	Load existing exam schedule for class/exam/year
*/
elseif ($_POST['type'] == 'getExamSchedule') {
	$classid = intval($_POST['classid']);
	$examid  = intval($_POST['examid']);
	$year    = isset($_POST['year']) ? trim($_POST['year']) : '';

	$row = $wpdb->get_row($wpdb->prepare(
		"SELECT subject_dates FROM ct_exam_schedule WHERE classid = %d AND examid = %d AND year = %s LIMIT 1",
		$classid, $examid, $year
	));

	header('Content-Type: application/json');
	if ($row && !empty($row->subject_dates)) {
		// subject_dates already stored as JSON string
		echo $row->subject_dates;
	} else {
		echo json_encode(new stdClass());
	}
}


/*
	Save exam schedule (insert or update)
	Expects: classid, examid, year, subject_dates (JSON string or object)
*/
elseif ($_POST['type'] == 'saveExamSchedule') {
	$classid = intval($_POST['classid']);
	$examid  = intval($_POST['examid']);
	$year    = isset($_POST['year']) ? trim($_POST['year']) : '';
	$subject_dates_raw = isset($_POST['subject_dates']) ? wp_unslash($_POST['subject_dates']) : '';

	header('Content-Type: application/json');

	if ($classid <= 0 || $examid <= 0 || $year === '') {
		echo json_encode(array('success' => false, 'message' => 'Missing required fields.'));
		exit;
	}

	// Normalize subject_dates to JSON string
	if (is_string($subject_dates_raw)) {
		$subject_dates_decoded = json_decode($subject_dates_raw, true);
	} else {
		$subject_dates_decoded = $subject_dates_raw;
	}

	if (!is_array($subject_dates_decoded)) {
		$subject_dates_decoded = array();
	}

	// sanitize subject ids and dates
	$clean = array();
	foreach ($subject_dates_decoded as $sid => $dt) {
		$sid_i = intval($sid);
		$dt_s = trim($dt);
		// accept empty dates as empty string, otherwise try to normalize YYYY-MM-DD
		if ($dt_s !== '') {
			// basic validation YYYY-MM-DD
			if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dt_s)) {
				// try to convert common formats to YYYY-MM-DD
				$ts = strtotime($dt_s);
				if ($ts !== false) {
					$dt_s = date('Y-m-d', $ts);
				} else {
					// invalid date, skip
					$dt_s = '';
				}
			}
		}
		if ($sid_i > 0) {
			$clean[$sid_i] = $dt_s;
		}
	}

	$subject_dates_json = json_encode($clean);
	$now = current_time('mysql');

	// check for existing record
	$existing_id = $wpdb->get_var($wpdb->prepare(
		"SELECT scheduleid FROM ct_exam_schedule WHERE classid = %d AND examid = %d AND year = %s LIMIT 1",
		$classid, $examid, $year
	));

	if ($existing_id) {
		$updated = $wpdb->update(
			'ct_exam_schedule',
			array(
				'subject_dates' => $subject_dates_json,
				'updated_at' => $now
			),
			array('scheduleid' => $existing_id),
			array('%s', '%s'),
			array('%d')
		);

		if ($updated !== false) {
			echo json_encode(array('success' => true, 'message' => 'Schedule updated.'));
		} else {
			echo json_encode(array('success' => false, 'message' => 'Update failed.'));
		}
	} else {
		$inserted = $wpdb->insert(
			'ct_exam_schedule',
			array(
				'classid' => $classid,
				'examid' => $examid,
				'year' => $year,
				'subject_dates' => $subject_dates_json,
				'created_at' => $now,
				'updated_at' => $now
			),
			array('%d', '%d', '%s', '%s', '%s', '%s')
		);

		if ($inserted) {
			echo json_encode(array('success' => true, 'message' => 'Schedule saved.'));
		} else {
			echo json_encode(array('success' => false, 'message' => 'Insert failed.'));
		}
	}

}


// custom 
function getLateSubHeadId() {
    global $lateSubHeadId;
    $lateSubHeadId = 8;
}
add_action( 'init', 'getLateSubHeadId' );

function getAbsentSubHeadId() {
    global $absentSubHeadId;
    $absentSubHeadId = 9;
}
add_action( 'init', 'getAbsentSubHeadId' );

function getCashSubHeadId() {
    global $cashSubHeadId;
    $cashSubHeadId = 10;
}
add_action( 'init', 'getCashSubHeadId' );

function getAdmissionFeeSubHeadId() {
    global $admissionFeeSubHeadId;
    $admissionFeeSubHeadId = 1;
}
add_action( 'init', 'getAdmissionFeeSubHeadId' );

function getAdmissionFormSubHeadId() {
    global $admissionFormSubHeadId;
    $admissionFormSubHeadId = 2;
}
add_action( 'init', 'getAdmissionFormSubHeadId' );

function getExamFeeSubHeadId() {
    global $examFeeSubHeadId;
    $examFeeSubHeadId = 3;
}
add_action( 'init', 'getExamFeeSubHeadId' );

function getMonthlyFeeSubHeadId() {
    global $monthlyFeeSubHeadId;
    $monthlyFeeSubHeadId = 4;
}
add_action( 'init', 'getMonthlyFeeSubHeadId' );

function getTransportFeeSubHeadId() {
    global $transportFeeSubHeadId;
    $transportFeeSubHeadId =100;
}
add_action( 'init', 'getTransportFeeSubHeadId' );

function getIctFeeSubHeadId() {
    global $ictFeeSubHeadId;
    $ictFeeSubHeadId =200;
}
add_action( 'init', 'getIctFeeSubHeadId' );


function getPaystationSubHeadId() {
    global $paystationSubHeadId;
    $paystationSubHeadId = 250;
}
add_action( 'init', 'getPaystationSubHeadId' );

function getCoachingFeeSubHeadId() {
    global $coachingFeeSubHeadId;
    $coachingFeeSubHeadId =300;
}
add_action( 'init', 'getCoachingFeeSubHeadId' );

function getRegistrationFormFeeSubHeadId() {
    global $registrationFeeSubHeadId;
    $registrationFeeSubHeadId =400;
}
add_action( 'init', 'getRegistrationFormFeeSubHeadId' );

function getDairySubHeadId() {
    global $dairySubHeadId;
    $dairySubHeadId =410;
}
add_action( 'init', 'getDairySubHeadId' );

function getIdcardSubHeadId() {
    global $idcardSubHeadId;
    $idcardSubHeadId =420;
}
add_action( 'init', 'getIdcardSubHeadId' );

function getMonthArrayName() {
    global $monthArray;
	$monthArray = array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
}
add_action( 'init', 'getMonthArrayName' );
/*
 * PayStation Payment Integration AJAX Handlers
 */

// Initiate PayStation payment
if(isset($_POST['type']) && $_POST['type'] == 'initiatePaystationPayment'){
    // Use dirname to get the correct path since get_template_directory() may not work here
    require_once(dirname(__FILE__) . '/paystation_api.php');
    
    header('Content-Type: application/json');
    
    // Generate unique payment ID
    $payment_id = 'PAY-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
    
    // Get student and fee data from POST
    $student_data = array(
        'student_id' => intval($_POST['student_id']),
        'student_name' => sanitize_text_field($_POST['student_name']),
        'student_roll' => sanitize_text_field($_POST['student_roll']),
        'stdUniqueID' => sanitize_text_field($_POST['stdUniqueID'] ?? ''),
        'class_id' => intval($_POST['class_id']),
        'section' => isset($_POST['section']) && !empty($_POST['section']) ? intval($_POST['section']) : null,
        'group_id' => isset($_POST['group_id']) && !empty($_POST['group_id']) ? intval($_POST['group_id']) : null,
        'year' => sanitize_text_field($_POST['year']),
        'cust_phone' => sanitize_text_field($_POST['cust_phone']),
        'cust_email' => sanitize_email($_POST['cust_email'] ?? ''),
        'cust_address' => sanitize_text_field($_POST['cust_address'] ?? ''),
    );
    
    $fee_data = array(
        'fee_breakdown' => json_decode(stripslashes($_POST['fee_breakdown']), true),
        'sub_total' => floatval($_POST['sub_total']),
        'remission' => floatval($_POST['remission'] ?? 0),
        'total_amount' => floatval($_POST['total_amount']),
        'month' => intval($_POST['month']),
    );
    
    // Validate required data
    if (empty($student_data['student_id']) || empty($fee_data['total_amount']) || $fee_data['total_amount'] <= 0) {
        echo json_encode(array(
            'success' => false, 
            'message' => 'Invalid student or fee data'
        ));
        exit;
    }
    
    if (empty($student_data['cust_phone'])) {
        echo json_encode(array(
            'success' => false, 
            'message' => 'Phone number is required for PayStation payment'
        ));
        exit;
    }
    
    // Call PayStation API to initiate payment
    $result = paystation_initiate_payment($payment_id, $student_data, $fee_data);
    
    if (is_wp_error($result)) {
        echo json_encode(array(
            'success' => false, 
            'message' => $result->get_error_message()
        ));
    } else {
        echo json_encode(array(
            'success' => true,
            'payment_url' => $result['payment_url'],
            'payment_id' => $payment_id,
            'invoice_number' => $result['invoice_number']
        ));
    }
    exit;
}

// Get fee calculation for PayStation (uses existing getStudentInfo logic but returns structured data)
if(isset($_POST['type']) && $_POST['type'] == 'getPaystationFeeInfo'){
    $class = intval($_POST['class']);
    $section = isset($_POST['section']) && !empty($_POST['section']) ? intval($_POST['section']) : null;
    $year = sanitize_text_field($_POST['year']);
    $roll = intval($_POST['roll']);
    $group = isset($_POST['group']) && !empty($_POST['group']) ? intval($_POST['group']) : null;
    $fee_month = isset($_POST['month']) && !empty($_POST['month']) ? intval($_POST['month']) : intval(date('n'));
    
    // Get global sub head IDs
    global $admissionFeeSubHeadId, $admissionFormSubHeadId, $monthlyFeeSubHeadId;
    global $examFeeSubHeadId, $transportFeeSubHeadId, $ictFeeSubHeadId;
    global $registrationFeeSubHeadId, $coachingFeeSubHeadId;
    
    $result = array();
    
    // Find student
    $stdQuery = "SELECT ct_studentinfo.infoStdid, ct_student.stdName, ct_student.stdUniqueID,
                 ct_student_wise_fee.transport_fee_id, ct_student_wise_fee.transport_type, 
                 ct_student_wise_fee.transport_required, ct_student.admission_type, ct_student.facilities 
                 FROM ct_studentinfo
                 LEFT JOIN ct_student ON ct_student.studentid = ct_studentinfo.infoStdid
                 LEFT JOIN ct_student_wise_fee ON ct_student.studentid = ct_student_wise_fee.student_id 
                     AND $class = ct_student_wise_fee.class_id 
                     AND '$year' = ct_student_wise_fee.year 
                     AND 3 = ct_student_wise_fee.fee_type
                 WHERE ct_studentinfo.infoYear = '$year' 
                 AND ct_studentinfo.infoRoll = $roll 
                 AND ct_studentinfo.infoClass = $class
                 AND ct_student.stdStatus = 1";
    
    if($section){
        $stdQuery .= " AND ct_studentinfo.infoSection = $section";
    }
    if($group){
        $stdQuery .= " AND ct_studentinfo.infoGroup = $group";
    }
    $stdQuery .= " LIMIT 1";
    
    $studentInfo = $wpdb->get_results($stdQuery);
    
    if(!$studentInfo || empty($studentInfo)){
        header('Content-Type: application/json');
        echo json_encode(array('success' => false, 'message' => 'Student not found'));
        exit;
    }
    
    $studentId = $studentInfo[0]->infoStdid;
    $result['student_id'] = $studentId;
    $result['student_name'] = $studentInfo[0]->stdName;
    $result['stdUniqueID'] = $studentInfo[0]->stdUniqueID ?? '';
    $result['student_roll'] = $roll;
    $result['class_id'] = $class;
    $result['section'] = $section;
    $result['group_id'] = $group;
    $result['year'] = $year;
    $result['month'] = $fee_month;
    
    // Get class name
    $className = $wpdb->get_var("SELECT className FROM ct_class WHERE classid = $class");
    $result['class_name'] = $className;
    
    // Get section name
    if($section){
        $sectionName = $wpdb->get_var("SELECT sectionName FROM ct_section WHERE sectionid = $section");
        $result['section_name'] = $sectionName;
    }
    
    // Get group name
    if($group){
        $groupName = $wpdb->get_var("SELECT groupName FROM ct_group WHERE groupId = $group");
        $result['group_name'] = $groupName;
    }
    
    // Get active collection sub head IDs
    $subHeadId = $wpdb->get_results("SELECT * FROM ct_sub_head
        WHERE active_for_collection = 1 AND relation_to = 1 AND isHidden IS NULL 
        ORDER BY sort_order ASC, sub_head_name ASC");
    
    $fee_breakdown = array();
    $sub_total = 0;
    
    // Pre-fetch already-paid yearly and exam fees from collection_details as a safety net
    $yearlyPaidInCollection = array();
    $examPaidInCollection = array();
    
    $yearlyPaidQuery = "SELECT DISTINCT cd.sub_head_id FROM ct_student_fee_collection_details cd
        LEFT JOIN ct_student_fee_collection_info ci ON ci.id = cd.info_id
        LEFT JOIN ct_sub_head sh ON sh.id = cd.sub_head_id
        WHERE ci.class_id = $class AND ci.year = '$year' 
        AND ci.student_id = $studentId AND sh.type = 2";
    if($section){ $yearlyPaidQuery .= " AND ci.section = $section"; }
    if($group){ $yearlyPaidQuery .= " AND ci.group_id = $group"; }
    $yearlyPaidInCollection = $wpdb->get_col($yearlyPaidQuery);
    if(!$yearlyPaidInCollection) $yearlyPaidInCollection = array();
    
    $examPaidQuery = "SELECT DISTINCT cd.sub_head_id FROM ct_student_fee_collection_details cd
        LEFT JOIN ct_student_fee_collection_info ci ON ci.id = cd.info_id
        LEFT JOIN ct_sub_head sh ON sh.id = cd.sub_head_id
        WHERE ci.class_id = $class AND ci.year = '$year' 
        AND ci.student_id = $studentId AND sh.type = 3";
    if($section){ $examPaidQuery .= " AND ci.section = $section"; }
    if($group){ $examPaidQuery .= " AND ci.group_id = $group"; }
    $examPaidInCollection = $wpdb->get_col($examPaidQuery);
    if(!$examPaidInCollection) $examPaidInCollection = array();
    
    foreach($subHeadId as $val){
        $feesQuery = "SELECT fee FROM ct_student_fee_list WHERE sub_head_id = $val->id AND class_id = $class AND year = '$year'";
        if($group){
            $feesQuery .= " AND group_id = $group";
        }
        $feesQuery .= " ORDER BY id DESC LIMIT 1";
        $fees = $wpdb->get_results($feesQuery);
        $base_fee = $fees ? floatval($fees[0]->fee) : 0;
        
        $amount = 0;

		if($val->type == 3){
            // Exam fee — skip if already paid (check summary + collection_details as safety net)
            $activeExamId = $wpdb->get_results("SELECT examid FROM ct_exam WHERE active_for_collection = 1 AND examClass = $class LIMIT 1");
            if($activeExamId){
                $examId = $activeExamId[0]->examid;
                $feeInfoQuery = "SELECT fee FROM ct_student_exam_fee_summary 
                    WHERE sub_head_id = $val->id AND class_id = $class AND exam_id = $examId 
                    AND year = '$year' AND student_id = $studentId";
                if($section){ $feeInfoQuery .= " AND section = $section"; }
                if($group){ $feeInfoQuery .= " AND group_id = $group"; }
                $feeInfo = $wpdb->get_results($feeInfoQuery);
                
                if(!$feeInfo && !in_array($val->id, $examPaidInCollection)){
                    $amount = $base_fee;
                }
            }
        } else if($val->type == 1){
			// Monthly fee
			$sumOfFees = 0;
			for($i = $fee_month; $i >= 1; $i--){
				$feeInfoQuery = "SELECT fee FROM ct_student_monthly_fee_summary 
					WHERE sub_head_id = $val->id AND class_id = $class AND year = '$year' 
					AND month = $i AND student_id = $studentId";
				if($section){
					$feeInfoQuery .= " AND section = $section";
				}
				if($group){
					$feeInfoQuery .= " AND group_id = $group";
				}
				$feeInfo = $wpdb->get_results($feeInfoQuery);
				
				if(!$feeInfo){
					$fee_amount = $base_fee;
					
					if($val->id == $monthlyFeeSubHeadId){
						if($studentInfo[0]->facilities == 'Full free' || $studentInfo[0]->facilities == 'Scholarship'){
							$fee_amount = 0;
						} else if($studentInfo[0]->facilities == 'Half free'){
							$fee_amount = $fee_amount / 2;
						} else {
							$checkfees = $wpdb->get_results("SELECT monthly_fee FROM ct_student WHERE studentid = $studentId");
							if($checkfees && $checkfees[0]->monthly_fee > 0){
								$fee_amount = floatval($checkfees[0]->monthly_fee);
							}
						}
					} else if($val->id == $transportFeeSubHeadId){
						if($studentInfo[0]->transport_required == 1){
							$transport_fee_id = $studentInfo[0]->transport_fee_id;
							$tfees = $wpdb->get_results("SELECT amount FROM ct_transport_fee_list WHERE id = $transport_fee_id");
							if($tfees){
								$fee_amount = floatval($tfees[0]->amount);
								if($studentInfo[0]->transport_type == 1){
									$fee_amount = $fee_amount / 2;
								}
							} else {
								$fee_amount = 0;
							}
						} else {
							$fee_amount = 0;
						}
					} else if($val->id == $coachingFeeSubHeadId){
						$checkfees = "SELECT amount FROM ct_student_wise_fee WHERE fee_type = 1 AND student_id = $studentId AND class_id = $class AND year = '$year'";
						if($section){ $checkfees .= " AND section = $section"; }
						if($group){ $checkfees .= " AND group_id = $group"; }
						$studentwisefees = $wpdb->get_results($checkfees);
						if($studentwisefees && $studentwisefees[0]->amount > 0){
							$fee_amount = floatval($studentwisefees[0]->amount);
						} else {
							$fee_amount = 0;
						}
					}
					
					$sumOfFees += $fee_amount;
				}
			}
			$amount = $sumOfFees;
			
		} else if($val->type == 2){
			// Yearly fee — skip if already paid (check summary + collection_details as safety net)
			$feeInfoQuery = "SELECT fee FROM ct_student_yearly_fee_summary 
				WHERE sub_head_id = $val->id AND class_id = $class AND year = '$year' AND student_id = $studentId";
			if($section){ $feeInfoQuery .= " AND section = $section"; }
			if($group){ $feeInfoQuery .= " AND group_id = $group"; }
			$feeInfo = $wpdb->get_results($feeInfoQuery);
			
			if(!$feeInfo && !in_array($val->id, $yearlyPaidInCollection)){
				$amount = $base_fee;
				
				if($val->id == $admissionFeeSubHeadId){
					if($studentInfo[0]->admission_type == 1){
						if($studentInfo[0]->facilities == 'Half free'){
							$amount = $amount / 2;
						}
					} else {
						$promoted_fee = $wpdb->get_results("SELECT amount FROM ct_admission_fee_promoted WHERE class = $class");
						if($promoted_fee){
							$amount = floatval($promoted_fee[0]->amount);
							if($studentInfo[0]->facilities == 'Half free'){
								$amount = $amount / 2;
							}
						} else {
							$amount = 0;
						}
					}
				} else if($val->id == $admissionFormSubHeadId){
					if($studentInfo[0]->facilities == 'Half free'){
						$amount = $amount / 2;
					}
				} else if($val->id == $registrationFeeSubHeadId){
					$checkfees = "SELECT amount FROM ct_student_wise_fee WHERE fee_type = 2 AND student_id = $studentId AND class_id = $class AND year = '$year'";
					if($section){ $checkfees .= " AND section = $section"; }
					if($group){ $checkfees .= " AND group_id = $group"; }
					$studentwisefees = $wpdb->get_results($checkfees);
					if($studentwisefees && $studentwisefees[0]->amount > 0){
						$amount = floatval($studentwisefees[0]->amount);
					}
				}
			} else {
				$amount = 0;
			}
			
		} 
		else {
			$amount = $base_fee;
		}
        
        if($amount > 0){
            $fee_breakdown[] = array(
                'sub_head_id' => $val->id,
                'sub_head_name' => $val->sub_head_name,
                'fee_type' => $val->type == 1 ? 'monthly' : ($val->type == 2 ? 'yearly' : ($val->type == 3 ? 'exam' : 'other')),
                'amount' => round($amount, 2)
            );
            $sub_total += $amount;
        }
    }

	/**
	 * Calculate total paid MONTHLY amount for the month
	 */
	$onlinePaidTotalAmountQuery = "SELECT SUM(cd.fee) AS totalPaid 
		FROM ct_student_fee_collection_details cd
		LEFT JOIN ct_student_fee_collection_info ci ON ci.id = cd.info_id
		LEFT JOIN ct_sub_head sh ON sh.id = cd.sub_head_id
		WHERE ci.class_id = $class AND ci.year = '$year' 
		AND ci.month = $fee_month AND ci.student_id = $studentId 
		AND sh.type = 1";
	if(isset($section) && !empty($section)){
		$onlinePaidTotalAmountQuery .= " AND ci.section = $section";
	}
	if(isset($group) && !empty($group)){
		$onlinePaidTotalAmountQuery .= " AND ci.group_id = $group";
	}

	global $wpdb;
	$onlinePaidTotalAmount = $wpdb->get_results($onlinePaidTotalAmountQuery);
	$paidAmount = floatval($onlinePaidTotalAmount[0]->totalPaid);

	$sub_total = max(0, $sub_total - $paidAmount);

	$result['paid_amount'] = round($paidAmount, 2);    
    $result['fee_breakdown'] = $fee_breakdown;
    $result['sub_total'] = round($sub_total, 2);
    $result['remission'] = 0;
    $result['total_amount'] = round($sub_total, 2);
    $result['success'] = true;
    
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}
