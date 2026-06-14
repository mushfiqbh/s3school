<?php
require( '../../../../wp-load.php' );
ob_start();

global $wpdb;

// Helpers
if (!function_exists('sm_clean_int')) {
	function sm_clean_int($v) { return isset($v) && $v !== '' ? (int)$v : null; }
}
if (!function_exists('sm_clean_txt')) {
	function sm_clean_txt($v) { return isset($v) ? sanitize_text_field($v) : ''; }
}

// Clean output buffer to ensure response is valid
while (ob_get_level()) {
	ob_end_clean();
}

// Route by action keys (pattern used in other admin pages)

// 4. Fetch Sections (returns HTML options)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['get_sections'])) {
	$class = sm_clean_int($_POST['class_id']);
	$sections = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT sectionid, sectionName FROM ct_section WHERE forClass = %d ORDER BY sectionName ASC",
			$class
		)
	);

	if (!empty($sections)) {
		echo "<option value='0'>All Sections</option>";
		foreach ($sections as $section) {
			echo "<option value='{$section->sectionid}'>{$section->sectionName}</option>";
		}
	} else {
		echo "<option value='0'>No Sections Found</option>";
	}
	exit;
}

header('Content-Type: application/json');

$table_att = 'ct_attendance';

// 1. Fetch Students & Attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['fetch_attendance'])) {
	$classId = sm_clean_int($_POST['class_id']);
	$sectionId = sm_clean_int($_POST['section_id']);
	$year = sm_clean_txt($_POST['year']);
	$date = sm_clean_txt($_POST['date']);

	if (!$classId || !$year || !$date) {
		echo json_encode(['success' => false, 'message' => 'Missing required fields']);
		exit;
	}

	// Get Students
	$studentsQuery = $wpdb->prepare(
		"SELECT s.studentid, s.stdName, s.stdPhone, si.infoRoll
		 FROM ct_studentinfo si
		 JOIN ct_student s ON si.infoStdid = s.studentid
		 WHERE si.infoClass = %d AND si.infoYear = %s",
		$classId,
		$year
	);

	if ($sectionId > 0) {
		$studentsQuery .= $wpdb->prepare(" AND si.infoSection = %d", $sectionId);
	}
	$studentsQuery .= " ORDER BY si.infoRoll ASC";
	$students = $wpdb->get_results($studentsQuery);

	// Attendance map for date
	$attQuery = $wpdb->prepare(
		"SELECT stdId, status, notes
		 FROM {$table_att}
		 WHERE attClass = %d AND attYear = %s AND attDate = %s",
		$classId,
		$year,
		$date
	);
	if ($sectionId > 0) {
		$attQuery .= $wpdb->prepare(" AND attSection = %d", $sectionId);
	}

	$attendanceRows = $wpdb->get_results($attQuery);
	$attMap = [];
	foreach ($attendanceRows as $row) {
		$attMap[(int)$row->stdId] = $row;
	}

	$result = [];
	foreach ($students as $student) {
		$stdId = (int)$student->studentid;
		$status = isset($attMap[$stdId]) ? $attMap[$stdId]->status : 'present';
		$notes = isset($attMap[$stdId]) ? (string)$attMap[$stdId]->notes : '';

		$result[] = [
			'id' => $stdId,
			'roll' => (int)$student->infoRoll,
			'name' => (string)$student->stdName,
			'status' => (string)$status,
			'notes' => $notes,
		];
	}

	echo json_encode(['success' => true, 'data' => $result]);
	exit;
}

// 2. Mark Single Attendance
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_single'])) {
	$data = [
		'attDate' => sm_clean_txt($_POST['date']),
		'attClass' => sm_clean_int($_POST['class_id']),
		'attSection' => sm_clean_int($_POST['section_id']),
		'attYear' => sm_clean_txt($_POST['year']),
		'stdId' => sm_clean_int($_POST['student_id']),
		'infoRoll' => sm_clean_int($_POST['roll']),
		'status' => sm_clean_txt($_POST['status']),
		'notes' => isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '',
	];

	if (!$data['attDate'] || !$data['attClass'] || !$data['attYear'] || !$data['stdId']) {
		echo json_encode(['success' => false, 'message' => 'Missing required fields']);
		exit;
	}

	$exists = $wpdb->get_var($wpdb->prepare(
		"SELECT attId FROM {$table_att} WHERE attDate=%s AND attClass=%d AND attSection=%d AND attYear=%s AND stdId=%d",
		$data['attDate'],
		$data['attClass'],
		(int)$data['attSection'],
		$data['attYear'],
		$data['stdId']
	));

	if ($exists) {
		$wpdb->update(
			$table_att,
			['status' => $data['status'], 'notes' => $data['notes']],
			['attId' => (int)$exists]
		);
	} else {
		$wpdb->insert($table_att, $data);
	}

	echo json_encode(['success' => true]);
	exit;
}

// 3. Bulk Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_bulk'])) {
	$status = sm_clean_txt($_POST['status']);
	$classId = sm_clean_int($_POST['class_id']);
	$sectionId = sm_clean_int($_POST['section_id']);
	$year = sm_clean_txt($_POST['year']);
	$date = sm_clean_txt($_POST['date']);

	if (!$status || !$classId || !$year || !$date) {
		echo json_encode(['success' => false, 'message' => 'Missing required fields']);
		exit;
	}

	$studentsQuery = $wpdb->prepare(
		"SELECT s.studentid, si.infoRoll
		 FROM ct_studentinfo si
		 JOIN ct_student s ON si.infoStdid = s.studentid
		 WHERE si.infoClass = %d AND si.infoYear = %s",
		$classId,
		$year
	);
	if ($sectionId > 0) {
		$studentsQuery .= $wpdb->prepare(" AND si.infoSection = %d", $sectionId);
	}
	$students = $wpdb->get_results($studentsQuery);

	$count = 0;
	foreach ($students as $student) {
		$sql = "INSERT INTO {$table_att}
			(attDate, attClass, attSection, attYear, stdId, infoRoll, status)
			VALUES (%s, %d, %d, %s, %d, %d, %s)
			ON DUPLICATE KEY UPDATE status = VALUES(status)";

		$wpdb->query($wpdb->prepare(
			$sql,
			$date,
			$classId,
			(int)$sectionId,
			$year,
			(int)$student->studentid,
			(int)$student->infoRoll,
			$status
		));
		$count++;
	}

	echo json_encode(['success' => true, 'updated' => $count]);
	exit;
}

echo json_encode(['success' => false, 'message' => 'No valid action']);
exit;
