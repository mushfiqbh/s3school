<?php

// 	define( 'SHORTINIT', true );
    	require( '../../../../wp-load.php' );
    	ob_start();


// Initialize default POST keys to prevent notices
if (!isset($_POST['type'])) $_POST['type'] = '';
if (!isset($_POST['action'])) $_POST['action'] = '';

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
		for ($i=-2; $i < 7; $i++) {
		     $sec = (date("Y")-$i);
            $selected = ($currentYear == $sec) ? 'selected' : '';
      
      $options .= "<option value='$sec' $selected>$sec</option>";
    } 
	}else{
	    $currentYear = date("Y")."-".(date("Y")+1);
		for ($i=-2; $i < 7; $i++) { 
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
		// yearly 
		
		$feeInfoQuery = "SELECT fee FROM ct_student_yearly_fee_summary WHERE sub_head_id = $val->id AND class_id = $class AND year = '$year' AND student_id = $studentId";
		if(isset($section) && !empty($section)){
			$feeInfoQuery .= " AND section = $section";
		}
		if(isset($group) && !empty($group)){
			$feeInfoQuery .= " AND group_id = $group";
		}
		$feeInfo = $wpdb->get_results($feeInfoQuery);
			if(!$feeInfo){
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
		// exam
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

			if(!$feeInfo){
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



// ==========================================
// UNIFIED RESULT MANAGEMENT
// ==========================================

/*
    Load Students for Unified Result Entry
*/
elseif($_POST['type'] == 'load_students'){
    $class = intval($_POST['class']);
    $exam = intval($_POST['exam']);
    $section = isset($_POST['section']) ? intval($_POST['section']) : 0;
    $group = isset($_POST['group']) ? intval($_POST['group']) : 0;
    $year = sanitize_text_field($_POST['year']);
    $subject = intval($_POST['subject']);

    // Get subject configuration
    $subject_info = $wpdb->get_row($wpdb->prepare(
        "SELECT subCode, subCQ, subMCQ, subPect, subCa, subOptinal, sub4th, subPaper, connecttedPaper FROM ct_subject WHERE subjectid = %d",
        $subject
    ));

    if (!$subject_info) {
        echo json_encode(['success' => false, 'message' => 'Subject not found']);
        exit;
    }

    $subject_config = [
        'cq' => intval($subject_info->subCQ),
        'mcq' => intval($subject_info->subMCQ),
        'prac' => intval($subject_info->subPect),
        'ca' => intval($subject_info->subCa)
    ];

    $subOpt = intval($subject_info->subOptinal);
    $sub4th = intval($subject_info->sub4th);
    $subCode = $subject_info->subCode;

    // Religion filter
    $religionMap = [
        'Muslim' => 111,
        'Hinduism' => 112,
        'Buddist' => 113,
        'Christian' => 114
    ];

    $religionFilter = '';
    if ($subCode && in_array($subCode, array_values($religionMap))) {
        $religion = array_search($subCode, $religionMap);
        $religionFilter = $wpdb->prepare(" AND ct_student.stdReligion = %s", $religion);
    }

    // Build student query
    if ($subOpt == 0 && $sub4th == 0) {
        // Regular subject
        $stdQuery = "SELECT 
            studentid,
            infoRoll,
            stdName,
            groupName,
            infoGroup,
            infoSection,
            sectionName,
            stdReligion
        FROM ct_student
        LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid
            AND ct_studentinfo.infoClass = $class AND ct_studentinfo.infoYear = '$year'
        LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId
        LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
        WHERE stdCurntYear = '$year' AND stdCurrentClass = $class" . $religionFilter;
    } else {
        // Optional or 4th subject
        $stdQuery = "SELECT 
            studentid,
            infoRoll,
            stdName,
            groupName,
            infoGroup,
            infoSection,
            infoOptionals,
            info4thSub,
            sectionName,
            stdReligion
        FROM ct_student
        LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid
            AND ct_studentinfo.infoClass = $class AND ct_studentinfo.infoYear = '$year'
        LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId
        LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
        WHERE stdCurntYear = '$year' AND stdCurrentClass = $class" . $religionFilter;

        if ($subOpt == 1 && $sub4th == 1) {
            $stdQuery .= " AND (infoOptionals LIKE '%\"$subject\"%' OR info4thSub = $subject)";
        }
        if ($subOpt == 1 && $sub4th == 0) {
            $stdQuery .= " AND infoOptionals LIKE '%\"$subject\"%'";
        }
        if ($subOpt == 0 && $sub4th == 1) {
            $stdQuery .= " AND info4thSub = $subject";
        }
    }

    if ($section != 0 && $section != '') {
        $stdQuery .= " AND infoSection = $section";
    }
    if ($group != 0 && $group != '') {
        $stdQuery .= " AND infoGroup = $group";
    }

    $stdQuery .= " ORDER BY infoRoll ASC";

    $students = $wpdb->get_results($stdQuery);

    $studentsData = [];

    foreach ($students as $student) {
        // Check if result already exists
        $existingResult = $wpdb->get_row($wpdb->prepare(
            "SELECT resultId, resCQ, resMCQ, resPrec, resCa, resTotal 
            FROM ct_result 
            WHERE resStudentId = %d AND resClass = %d AND resExam = %d AND resSubject = %d AND resultYear = %s",
            $student->studentid,
            $class,
            $exam,
            $subject,
            $year
        ));

        $marks = [
            'cq' => '',
            'mcq' => '',
            'prac' => '',
            'ca' => ''
        ];

        $result_id = null;

        if ($existingResult) {
            // Edit mode - pre-fill marks
            $marks = [
                'cq' => $existingResult->resCQ,
                'mcq' => $existingResult->resMCQ,
                'prac' => $existingResult->resPrec,
                'ca' => $existingResult->resCa
            ];
            $result_id = $existingResult->resultId;
        }

        $studentsData[] = [
            'student_id' => $student->studentid,
            'roll' => $student->infoRoll,
            'name' => $student->stdName,
            'group' => $student->groupName,
            'section' => $student->sectionName,
            'info_group' => $student->infoGroup,
            'info_section' => $student->infoSection,
            'result_id' => $result_id,
            'marks' => $marks
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'students' => $studentsData,
            'subject_config' => $subject_config
        ]
    ]);
}

/*
    Save Individual Result (Add/Update)
*/
elseif($_POST['type'] == 'save_result'){
    $student_id = intval($_POST['student_id']);
    $result_id = isset($_POST['result_id']) && $_POST['result_id'] !== '' ? intval($_POST['result_id']) : null;
    $mode = sanitize_text_field($_POST['mode']);
    $class = intval($_POST['class']);
    $exam = intval($_POST['exam']);
    $section = isset($_POST['section']) ? intval($_POST['section']) : 0;
    $group = isset($_POST['group']) ? intval($_POST['group']) : 0;
    $year = sanitize_text_field($_POST['year']);
    $subject = intval($_POST['subject']);
    $marks = $_POST['marks'];

    // Get subject info
    $subject_info = $wpdb->get_row($wpdb->prepare(
        "SELECT subPaper, connecttedPaper FROM ct_subject WHERE subjectid = %d",
        $subject
    ));

    if (!$subject_info) {
        echo json_encode(['success' => false, 'message' => 'Subject not found']);
        exit;
    }

    // Get student info
    $student_info = $wpdb->get_row($wpdb->prepare(
        "SELECT infoRoll, infoGroup, infoSection, infoOptionals, info4thSub 
        FROM ct_studentinfo 
        WHERE infoStdid = %d AND infoClass = %d AND infoYear = %s",
        $student_id,
        $class,
        $year
    ));

    if (!$student_info) {
        echo json_encode(['success' => false, 'message' => 'Student information not found']);
        exit;
    }

    // Determine optional/4th subject status
    $resSubOpt = 0;
    if (!empty($student_info->infoOptionals)) {
        $optionals = json_decode($student_info->infoOptionals, true);
        if (is_array($optionals) && in_array($subject, $optionals)) {
            $resSubOpt = 1;
        }
    }

    $resSub4th = 0;
    if (!empty($student_info->info4thSub)) {
        // Handle both numeric and JSON format
        if (is_numeric($student_info->info4thSub)) {
            $fourthSubId = intval($student_info->info4thSub);
        } else {
            $fourthSub = json_decode($student_info->info4thSub, true);
            if (is_array($fourthSub)) {
                $fourthSubId = isset($fourthSub[0]) ? intval($fourthSub[0]) : null;
            } else {
                $fourthSubId = intval($student_info->info4thSub);
            }
        }
        if ($subject == $fourthSubId) {
            $resSub4th = 1;
        }
    }

    // Calculate total (same as result-add.php)
    $stdCQ = (is_numeric($marks['cq']) && $marks['cq'] != '') ? $marks['cq'] : 0;
    $stdMCQ = (is_numeric($marks['mcq']) && $marks['mcq'] != '') ? $marks['mcq'] : 0;
    $stdPrec = (is_numeric($marks['prac']) && $marks['prac'] != '') ? $marks['prac'] : 0;
    $stdCa = (is_numeric($marks['ca']) && $marks['ca'] != '') ? $marks['ca'] : 0;
    $total = $stdCQ + $stdMCQ + $stdPrec + $stdCa;

    $data = [
        'resStudentId' => $student_id,
        'resClass' => $class,
        'resSubPaper' => $subject_info->subPaper,
        'resgroup' => $student_info->infoGroup,
        'resSec' => $student_info->infoSection,
        'resExam' => $exam,
        'resSubject' => $subject,
        'resultYear' => $year,
        'resCombineWith' => $subject_info->connecttedPaper,
        'resSubOpt' => $resSubOpt,
        'resSub4th' => $resSub4th,
        'resStdRoll' => $student_info->infoRoll,
        'resCQ' => $marks['cq'],
        'resMCQ' => $marks['mcq'],
        'resPrec' => $marks['prac'],
        'resCa' => $marks['ca'],
        'resTotal' => $total
    ];

    if ($mode === 'add' || $result_id === null) {
        // INSERT
        $data['resAdd'] = get_current_user_id();
        
        $insert = $wpdb->insert('ct_result', $data);

        if ($insert) {
            echo json_encode([
                'success' => true,
                'data' => [
                    'message' => 'Result added successfully',
                    'result_id' => $wpdb->insert_id
                ]
            ]);
        } else {
            $error_msg = 'Failed to add result';
            if ($wpdb->last_error) {
                $error_msg .= ': ' . $wpdb->last_error;
            }
            echo json_encode(['success' => false, 'message' => $error_msg, 'debug_data' => $data]);
        }
    } else {
        // UPDATE
        $update = $wpdb->update(
            'ct_result',
            [
                'resCQ' => $marks['cq'],
                'resMCQ' => $marks['mcq'],
                'resPrec' => $marks['prac'],
                'resCa' => $marks['ca'],
                'resTotal' => $total
            ],
            ['resultId' => $result_id]
        );

        if ($update !== false) {
            echo json_encode([
                'success' => true,
                'data' => [
                    'message' => 'Result updated successfully',
                    'result_id' => $result_id
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update result']);
        }
    }
}

/*
    Delete Multiple Results
*/
elseif($_POST['type'] == 'delete_results'){
    $student_ids = $_POST['student_ids'];
    $class = intval($_POST['class']);
    $exam = intval($_POST['exam']);
    $year = sanitize_text_field($_POST['year']);
    $subject = intval($_POST['subject']);

    if (!is_array($student_ids) || empty($student_ids)) {
        echo json_encode(['success' => false, 'message' => 'No students selected']);
        exit;
    }

    $student_ids = array_map('intval', $student_ids);
    $placeholders = implode(',', array_fill(0, count($student_ids), '%d'));

    $query = $wpdb->prepare(
        "DELETE FROM ct_result 
        WHERE resStudentId IN ($placeholders) 
        AND resClass = %d 
        AND resExam = %d 
        AND resSubject = %d 
        AND resultYear = %s",
        array_merge($student_ids, [$class, $exam, $subject, $year])
    );

    $deleted = $wpdb->query($query);

    if ($deleted !== false) {
        echo json_encode([
            'success' => true,
            'data' => [
                'message' => 'Results deleted successfully',
                'deleted_count' => $deleted
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete results']);
    }
}
