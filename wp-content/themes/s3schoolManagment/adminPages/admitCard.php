<?php

/*
 * * Template Name: Admin AdmitCard
 */
global $wpdb;
global $s3sRedux;

require_once get_template_directory() . '/adminPages/functions/teacher-access.php';
require_once __DIR__ . '/functions/html-snapshot.php';

$accessContext = s3s_get_teacher_access_context();
$isTeacher = $accessContext['is_teacher'];
$teacherRestrictions = $accessContext['restrictions'];
$hasAssignedClass = $accessContext['has_assignment'];

$attendanceGroups = $wpdb->get_results('SELECT groupId, groupName FROM ct_group ORDER BY groupName ASC');
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

$religionSubjectMap = array(
	'Muslim'    => 111,
	'Hinduism'  => 112,
	'Buddist'   => 113,
	'Christian' => 114,
);

$selectedGroupFilter = isset($_GET['group']) ? intval($_GET['group']) : 0;
$rawGenderFilter = isset($_GET['gender']) ? trim($_GET['gender']) : '';
$selectedGenderFilter = array_key_exists($rawGenderFilter, $genderSelectOptions) ? $rawGenderFilter : '';
$designVariation = isset($_GET['design']) ? sanitize_title((string) $_GET['design']) : '';
?>


<?php if (!is_admin()) {
	get_header(); ?>
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

													// If teacher has assigned class, only show that class
													if ($hasAssignedClass && $teacherRestrictions) {
														$classQuery = $wpdb->get_results($wpdb->prepare(
															'SELECT classid,className FROM ct_class WHERE classid = %d AND classid IN (SELECT examClass FROM ct_exam GROUP BY examClass)',
															$teacherRestrictions->teacherOfClass
														));
													} else {
														$classQuery = $wpdb->get_results('SELECT classid,className FROM ct_class WHERE classid IN (SELECT examClass FROM ct_exam GROUP BY examClass ORDER BY className ASC)');
													}

													echo "<option value=''>Select Class</option>";

													foreach ($classQuery as $class) {
														echo "<option value='" . $class->classid . "'>" . $class->className . '</option>';
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
												<label>
													Design 3
													<input type="checkbox" name="design" value="3">
												</label>
											</div>

											<div class="form-group">
												<input type="submit" name="creatId" value="Genarate" class="btn btn-primary">
											</div>
										</form>
									</div>
								</div>
							</div>



							<div class="container-fluid maxAdminpages" style="padding-left: 0">
								<div class="row">
									<!-- Tab Navigation -->
									<div class="col-md-12">
										<ul class="nav nav-tabs" id="admitCardTabs">
											<li class="active"><a href="#frontSide" data-toggle="tab">Front Side</a></li>
											<!-- <li><a href="#backSide" data-toggle="tab">Back Side</a></li> -->
										</ul>
									</div>

									<!-- Tab Content -->
									<div class="col-md-12">
										<div class="tab-content">
											<!-- Front Side Tab -->
											<div class="tab-pane active" id="frontSide">
												<?php
												$frontSubjectDates = array();
												if (isset($_GET['syear'])) {
													$frontStaticFilePath = '';
													$frontStaticUrl = '';
													$frontSnapshotError = '';
												?>
													<div class="col-md-12">
														<button onclick="printAdmitCardFront('printArea')" class="pull-right btn btn-primary">Print</button>
													</div>
													<div id="printArea" class="col-md-12 printBG">
														<?php
														ob_start();
														?>
														<div class="printArea" style="margin: 0 30px;">
															<style type="text/css">
																@page {
																	size: auto;
																	margin: 0px;
																}

																#itemMainBox {
																	max-width: 8.27in;
																	display: inline-block;
																	border: 2px solid #333333;
																	overflow: hidden;
																	font-family: sans-serif;
																	width: 100%;
																	position: relative;
																}

																#itemMainBox .itemWaterMark {
																	position: absolute;
																	width: 100%;
																	bottom: 0;
																	left: 0;
																	z-index: -1;
																	text-align: center;
																}

																#itemMainBox .itemWaterMark img {
																	opacity: .12;
																	width: 250px;
																}

																#itemMainBox .instLogo {
																	width: 90px;
																	position: absolute;
																	left: 0;
																	top: 0;
																}

																#itemMainBox .instName {
																	margin: 0 0 5px 0;
																	color: #337ab7;
																	font-weight: bold;
																	font-size: 18px;
																}

																#itemMainBox .instAddrs {
																	margin: 0 0 10px 0;
																	color: #888888;
																	font-size: 16px;
																}

																#itemMainBox .examName {
																	margin: 0 auto;
																	text-align: center;
																	font-size: 20px;
																}

																#itemMainBox .examName h3 {
																	margin: 10px 0;
																	font-size: 20px;
																}

																#itemMainBox .itemInfo {
																	clear: both;
																	color: #f7740c;
																	background: #f0f0f0;
																	padding: 5px;
																	print-color-adjust: exact;
																	-webkit-print-color-adjust: exact;
																}

																#itemMainBox .admitCard {
																	margin: 0;
																	font-size: 20px;
																	font-weight: bold;
																	text-align: center;
																}

																#itemMainBox .admitNote {
																	list-style-type: none;
																	float: left;
																	font-size: 12px;
																	padding: 0;
																}

																#itemMainBox .admitNote p {
																	margin: 0;
																}

																#itemMainBox hr {
																	clear: both;
																}

																#itemMainBox .princSign {
																	float: right;
																}
															</style>

															<?php
															$year = $_GET['syear'];
															$class = $_GET['class'];
															$section = isset($_GET['section']) ? $_GET['section'] : '';
															$roll = $_GET['roll'];
															$exam = $_GET['exam'];

															if (isset($_GET['syear'])) {
																$query = "SELECT studentid,stdName,stdFather,infoRoll,className,stdImg,infoYear,stdPhone,stdFather,groupName,ct_studentinfo.infoGroup,ct_studentinfo.info4thSub,sectionName,examName,stdAdmitYear,stdCreatedAt,ct_student.stdReligion  FROM ct_student
															LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid AND ct_student.stdCurrentClass = ct_studentinfo.infoClass
															LEFT JOIN ct_class ON ct_studentinfo.infoClass = ct_class.classid
															LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId
															LEFT JOIN ct_exam ON ct_exam.examid = $exam
															LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid  WHERE infoYear = '$year' ";

																if ($_GET['roll'] != '') {
																	$query .= " AND infoRoll IN ($roll)";
																}
																if ($section != '') {
																	$query .= " AND infoSection = $section";
																}
																$query .= ($selectedGroupFilter > 0) ? " AND infoGroup = $selectedGroupFilter" : '';
																if ($selectedGenderFilter !== '') {
																	$query .= ' AND ct_student.stdGender = ' . intval($selectedGenderFilter);
																}
																$query .= ' ORDER BY stdGender,infoRoll ASC';
																$groupsBy = $wpdb->get_results($query);

																$frontExamSubjectIds = array();
																$examSubjectsRaw = $wpdb->get_var($wpdb->prepare('SELECT examSubjects FROM ct_exam WHERE examid = %d', $exam));
																if (!empty($examSubjectsRaw)) {
																	$decodedExamSubjects = json_decode($examSubjectsRaw, true);
																	if (is_array($decodedExamSubjects)) {
																		foreach ($decodedExamSubjects as $subjectId) {
																			$subjectId = intval($subjectId);
																			if ($subjectId > 0) {
																				$frontExamSubjectIds[] = $subjectId;
																			}
																		}
																	}
																}

																$frontSubjectDates = array();
																$frontScheduleRow = $wpdb->get_row($wpdb->prepare(
																	'SELECT subject_dates FROM ct_exam_schedule WHERE classid = %d AND examid = %d AND year = %s LIMIT 1',
																	$class,
																	$exam,
																	$year
																));
																if ($frontScheduleRow && !empty($frontScheduleRow->subject_dates)) {
																	$frontSubjectDates = json_decode($frontScheduleRow->subject_dates, true);
																	if (!is_array($frontSubjectDates)) {
																		$frontSubjectDates = array();
																	}
																}
															}
															$frontSubjectSorter = static function ($left, $right) use ($frontSubjectDates) {
																$rawA = isset($frontSubjectDates[$left->subjectid]) ? $frontSubjectDates[$left->subjectid] : '';
																$rawB = isset($frontSubjectDates[$right->subjectid]) ? $frontSubjectDates[$right->subjectid] : '';

																$tsA = false;
																if ($rawA !== '') {
																	$dtA = DateTime::createFromFormat('Y-m-d', $rawA);
																	if ($dtA instanceof DateTime) {
																		$tsA = $dtA->getTimestamp();
																	} else {
																		$parsedA = strtotime($rawA);
																		if ($parsedA !== false) {
																			$tsA = $parsedA;
																		}
																	}
																}

																$tsB = false;
																if ($rawB !== '') {
																	$dtB = DateTime::createFromFormat('Y-m-d', $rawB);
																	if ($dtB instanceof DateTime) {
																		$tsB = $dtB->getTimestamp();
																	} else {
																		$parsedB = strtotime($rawB);
																		if ($parsedB !== false) {
																			$tsB = $parsedB;
																		}
																	}
																}

																if ($tsA !== false && $tsB !== false) {
																	if ($tsA === $tsB) {
																		return strcmp((string) ($left->subjectName ?? ''), (string) ($right->subjectName ?? ''));
																	}
																	return ($tsA < $tsB) ? -1 : 1;
																}

																if ($tsA !== false) {
																	return -1;
																}

																if ($tsB !== false) {
																	return 1;
																}

																return strcmp((string) ($left->subjectName ?? ''), (string) ($right->subjectName ?? ''));
															};

															$itemMainSyles = '';
															if(isset($_GET['design'])) {
																if($_GET['design'] == 1) {
																	$itemMainSyles = 'margin: 82px 0;';
																}else if($_GET['design'] == 2) {
																	$itemMainSyles = 'margin: 58px 0';
																}else if($_GET['design'] == 3) {
																	$itemMainSyles = 'margin: 70px 0;';
																}
															};

															if ($groupsBy) {
																foreach ($groupsBy as $key => $value) {
																	$datetime = new DateTime($value->stdCreatedAt);
															?>
																	<div id="itemMainBox" style="<?php echo $itemMainSyles; ?>" >
																		<div class="itemWaterMark">
																			<img src="<?= $s3sRedux['instLogo']['url'] ?>">
																		</div>
																		<div style="padding: 15px 30px 5px">
																			<div style="text-align: center; width: 100%;">
																				<div style="position: relative;padding-left: 90px;display:flex; align-items: center; justify-content: space-between;">
																					<img class="instLogo" src="<?= $s3sRedux['instLogo']['url'] ?>">

																					<div style="width: 100%; text-align: center;">
																						<h2 class="instName"><?= $s3sRedux['institute_name'] ?></h2>
																						<h4 class="instAddrs"><?= $s3sRedux['institute_address'] ?></h4>

																						<div class="examName">
																							<h3><?= $value->examName ?> <?= $year ?></h3>
																						</div>
																					</div>

																					<div style="text-align: right;">
																						<?php if (!empty($value->stdImg)) { ?>
																							<img style="height: 100px; " alt="<?= $value->stdName ?>_img" src="<?= $value->stdImg ?>">
																						<?php } else { ?>
																							<img style="height: 100px; " alt="<?= $value->stdName ?>_img" src="<?= get_template_directory_uri() ?>/img/No_Image.jpg">
																						<?php } ?>
																					</div>
																				</div>

																			</div>
																			<div class="itemInfo">
																				<h3 class="admitCard">Admit Card</h3>
																			</div>
																			<div style="float: left; clear: both;width: 100%;margin-bottom: 20px;">
																				<?php if (isset($_GET['design']) && $_GET['design'] == 3) { ?>
																					
																					<div style="width: 85%; float: left; margin-bottom: 10px;">
																						<?php
																						$studentGroupId = isset($value->infoGroup) ? intval($value->infoGroup) : 0;
																						$studentReligion = isset($value->stdReligion) ? trim($value->stdReligion) : '';
																						$frontSubjectClauses = array('subjectClass = ' . intval($class));
																						if (!empty($frontExamSubjectIds)) {
																							$frontSubjectClauses[] = 'subjectid IN (' . implode(',', array_map('intval', $frontExamSubjectIds)) . ')';
																						}
																						if ($studentGroupId > 0) {
																							$frontSubjectClauses[] = sprintf(
																								"(subOptinal = 0 OR (subOptinal = 1 AND (forGroup = 'all' OR JSON_CONTAINS(forGroup, '\"%d\"'))))",
																								$studentGroupId
																							);
																						}
																						$frontSubjectQuery = 'SELECT subjectid, subjectName, shortName, subCode, sub4th, assessment FROM ct_subject WHERE ' . implode(' AND ', $frontSubjectClauses) . ' ORDER BY subid';
																						$frontSubjectsForStudent = $wpdb->get_results($frontSubjectQuery);
																						if (!empty($frontSubjectsForStudent)) {
																							$student4thSub = array();
																							if (isset($value->info4thSub)) {
																								if (is_numeric($value->info4thSub)) {
																									$student4thSub[] = trim((string)$value->info4thSub);
																								} else {
																									$decoded = json_decode($value->info4thSub, true);
																									if (is_array($decoded)) {
																										$student4thSub = array_map('strval', $decoded);
																									} else {
																										$student4thSub = array_map('trim', explode(',', $value->info4thSub));
																									}
																								}
																							}

																							$frontSubjectsForStudent = array_values(array_filter($frontSubjectsForStudent, function ($subject) use ($studentReligion, $religionSubjectMap, $student4thSub) {
																								if (isset($subject->assessment) && $subject->assessment == 1) {
																									return false;
																								}
																								if (isset($subject->sub4th) && $subject->sub4th == 1) {
																									if (!in_array((string)$subject->subjectid, $student4thSub)) {
																										return false;
																									}
																								}
																								if (!isset($subject->subCode)) {
																									return true;
																								}
																								$code = intval($subject->subCode);
																								$matchedReligion = array_search($code, $religionSubjectMap, true);
																								if ($matchedReligion === false) {
																									return true;
																								}
																								return strcasecmp($studentReligion, $matchedReligion) === 0;
																							}));
																							if (!empty($frontSubjectsForStudent)) {
																								usort($frontSubjectsForStudent, $frontSubjectSorter);
																							}
																						}
																						?>
																						<table style="font-size: 13px;margin-top:5px;">
																							<tr>
																								<td width="20%"><b>Name</b></td>
																								<td width="10px" style="padding: 0 10px;"><b>:</b></td>
																								<td width="45%" calspan="2"><b><?= $value->stdName ?></b></td>
																							</tr>
																							<tr>
																								<td><b>Class</b></td>
																								<td style="padding: 0 10px;"><b>:</b></td>
																								<td><?= $value->className ?></td>

																								<td><b>ID</b></td>
																								<td style="padding: 0 10px;"><b>:</b></td>
																								<td><b><?= ($s3sRedux['stdidpref'] == 'year') ? $value->stdAdmitYear : $s3sRedux['stdidpref']; ?><?= sprintf('%05s', ($value->studentid + $s3sRedux['stdid'])) ?></b></td>

																							</tr>
																							<tr>
																								<td><b>Section</b></td>
																								<td style="padding: 0 10px;"><b>:</b></td>
																								<td calspan="2"><?= $value->sectionName ?></td>

																								<td><b>Roll</b></td>
																								<td style="padding: 0 10px;"><b>:</b></td>
																								<td colspan="2"><b><?= $value->infoRoll ?></b></td>
																							</tr>
																							<tr>
																								<?php if (!empty($value->groupName)) { ?>
																									<td><b>Group</b></td>
																									<td style="padding: 0 10px;"><b>:</b></td>
																									<td><?= $value->groupName ?></td>
																								<?php } ?>
																							</tr>
																						</table>

																				</div>

																				<div style="width:100%">
																					<?php if (!empty($frontSubjectsForStudent)) {
																							$maxColumnsPerTable = 16;
																							$totalSubjects = count($frontSubjectsForStudent);
																							$firstBatchSubjects = array_slice($frontSubjectsForStudent, 0, $maxColumnsPerTable);
																							$remainingSubjects = array_slice($frontSubjectsForStudent, $maxColumnsPerTable);
																					?>
																						<!-- First Table: Up to 16 subjects -->
																						<table style="width: 100%; border-collapse: collapse; font-size: 10px;">
																							<thead>
																								<tr>
																									<th style="border:none; padding: 2px 0px; font-weight: 900; width: fit-content;" colspan="<?= count($firstBatchSubjects) + 1 ?>">Exam Schedule</th>
																								</tr>
																							</thead>
																							<tr>
																								<td style="border: 1px solid #333; padding: 1px 4px; font-weight: 900; width: fit-content;"><b>Date</b></td>
																								<?php foreach ($firstBatchSubjects as $subject) {
																									$subjectDateRaw = isset($frontSubjectDates[$subject->subjectid]) ? $frontSubjectDates[$subject->subjectid] : '';
																									$dateDisplay = '';
																									if (!empty($subjectDateRaw)) {
																										$dateObj = DateTime::createFromFormat('Y-m-d', $subjectDateRaw);
																										if ($dateObj instanceof DateTime) {
																											$dateDisplay = $dateObj->format('d/m');
																										} else {
																											$timestamp = strtotime($subjectDateRaw);
																											if ($timestamp) {
																												$dateDisplay = date('d/m', $timestamp);
																											}
																										}
																									}
																								?>
																									<td style="border: 1px solid #333; padding: 1px 4px; text-align: center; font-weight: 600; width: fit-content;">
																										<?php
																										if ($dateDisplay !== '') {
																											echo esc_html($dateDisplay);
																										} else {
																											echo '&nbsp;';
																										}
																										?>
																									</td>
																								<?php } ?>
																							</tr>
																							<tr>
																								<td style="border: 1px solid #333; padding: 1px 4px; font-weight: 900; width: fit-content;"><b>Subject&nbsp;</b></td>
																								<?php foreach ($firstBatchSubjects as $subject) {
																									$labelBase = !empty($subject->shortName) ? $subject->shortName : $subject->subjectName;
																									$codeSuffix = '';
																									if (isset($subject->subCode) && $subject->subCode !== '') {
																										$codeSuffix = ' (' . $subject->subCode . ')';
																									}
																									$displayLabel = trim($labelBase . $codeSuffix);
																								?>
																									<td style="border: 1px solid #333; padding: 1px 4px; text-align: center; font-weight: 600; width: fit-content;">
																										<?= esc_html($displayLabel) ?>
																									</td>
																								<?php } ?>
																							</tr>
																						</table>

																						<?php if (!empty($remainingSubjects)) { ?>
																							<!-- Second Table: Remaining subjects (16+) -->
																							<table style="width: auto; border-collapse: collapse; font-size: 10px; margin-top: 10px;">
																								<tr>
																									<td style="border: 1px solid #333; padding: 1px 4px; font-weight: 900; width: fit-content;"><b>Date</b></td>
																									<?php foreach ($remainingSubjects as $subject) {
																										$subjectDateRaw = isset($frontSubjectDates[$subject->subjectid]) ? $frontSubjectDates[$subject->subjectid] : '';
																										$dateDisplay = '';
																										if (!empty($subjectDateRaw)) {
																											$dateObj = DateTime::createFromFormat('Y-m-d', $subjectDateRaw);
																											if ($dateObj instanceof DateTime) {
																												$dateDisplay = $dateObj->format('d/m');
																											} else {
																												$timestamp = strtotime($subjectDateRaw);
																												if ($timestamp) {
																													$dateDisplay = date('d/m', $timestamp);
																												}
																											}
																										}
																									?>
																										<td style="border: 1px solid #333; padding: 1px 4px; text-align: center; font-weight: 600; width: fit-content;">
																											<?php
																											if ($dateDisplay !== '') {
																												echo esc_html($dateDisplay);
																											} else {
																												echo '&nbsp;';
																											}
																											?>
																										</td>
																									<?php } ?>
																								</tr>
																								<tr>
																									<td style="border: 1px solid #333; padding: 1px 4px; font-weight: 900; width: fit-content;"><b>Subject&nbsp;</b></td>
																									<?php foreach ($remainingSubjects as $subject) {
																										$labelBase = !empty($subject->shortName) ? $subject->shortName : $subject->subjectName;
																										$codeSuffix = '';
																										if (isset($subject->subCode) && $subject->subCode !== '') {
																											$codeSuffix = ' (' . $subject->subCode . ')';
																										}
																										$displayLabel = trim($labelBase . $codeSuffix);
																									?>
																										<td style="border: 1px solid #333; padding: 1px 4px; text-align: center; font-weight: 600; width: fit-content;">
																											<?= esc_html($displayLabel) ?>
																										</td>
																									<?php } ?>
																								</tr>
																							</table>
																						<?php } ?>

																					<?php } else { ?>
																						<p style="margin: 10px 0 0 0; font-size: 12px; text-align: center;">No subjects available for the selected criteria.</p>
																					<?php } ?>

																				</div>
																			<?php } else { ?>

																				<div style="width: 85%; float: left;">

																					<table style="font-size: 16px;">
																						<tr>
																							<td width="20%"><b>Name</b></td>
																							<td width="10px" style="padding: 0 10px;"><b>:</b></td>
																							<td width="60%" calspan="2"><b><?= $value->stdName ?></b></td>
																						</tr>
																						<tr>
																							<td><b>ID</b></td>
																							<td style="padding: 0 10px;"><b>:</b></td>
																							<td calspan="2"><b><?= ($s3sRedux['stdidpref'] == 'year') ? $value->stdAdmitYear : $s3sRedux['stdidpref']; ?><?= sprintf('%05s', ($value->studentid + $s3sRedux['stdid'])) ?></b></td>
																						</tr>
																						<tr>
																							<td><b>Class</b></td>
																							<td style="padding: 0 10px;"><b>:</b></td>
																							<td><?= $value->className ?></td>
																							<td width="100%">
																								<?php if (isset($_GET['design']) && $_GET['design'] == 2) { ?>
																									Exam Roll - 205<?= sprintf('%03s', ($key + 1)) ?>
																								<?php } ?>
																							</td>
																						</tr>
																						<tr>
																							<td><b>Section</b></td>
																							<td style="padding: 0 10px;"><b>:</b></td>
																							<td calspan="2"><?= $value->sectionName ?></td>
																						</tr>
																						<tr>
																							<?php if (!empty($value->groupName)) { ?>
																								<td><b>Group</b></td>
																								<td style="padding: 0 10px;"><b>:</b></td>
																								<td><?= $value->groupName ?></td>
																							<?php } else { ?>
																								<td><b>ID No</b></td>
																								<td style="padding: 0 10px;"><b>:</b></td>
																								<td><b><?= $value->infoRoll ?></b></td>
																							<?php } ?>
																							<td>
																								<?php if (isset($_GET['design']) && $_GET['design'] == 2) { ?>
																									Regi No - <?= $datetime->format('Y') ?><?= sprintf('%06s', ($value->studentid)) ?>
																								<?php } ?>
																							</td>
																						</tr>
																						<tr>
																							<?php if (!empty($value->groupName)) { ?>
																								<td><b>ID No</b></td>
																								<td style="padding: 0 10px;"><b>:</b></td>
																								<td colspan="2"><b><?= $value->infoRoll ?></b></td>
																							<?php } else { ?>
																								<td colspan="2">&nbsp;</td>
																							<?php } ?>
																						</tr>
																					</table>

																				</div>

																			<?php } ?>
																			</div>
																			<div  style="width:100%;display:grid;grid-template-columns:4fr 1fr;">
																			<ul class="admitNote">
																				<?php if ($s3sRedux['admitCareNote'] != '') {
																					$notes = explode("\n", $s3sRedux['admitCareNote']); ?>
																					<?php foreach ($notes as $note) { ?>
																				<li><?= $note ?></li>
																					<?php } ?>
																				<?php } ?>
																			</ul>

																			<div class="princSign" style="text-align: center;">
																				<img width="110" style="max-width: 110px;" src="<?= $s3sRedux['principalSign']['url'] ?>"><br>
																				<?= $s3sRedux['inst_head_title'] ?>
																			</div>
																			</div>
																		</div>
																	</div>
															<?php
																}
															} else {
																echo "<h3 class='text-center'>No Student Found</h3>";
															}
															?>
														</div>
														<?php
														$frontAdmitCardHtml = ob_get_clean();
														if ($frontAdmitCardHtml === false) {
															$frontAdmitCardHtml = '';
														}
														$frontAdmitCardHtml = trim($frontAdmitCardHtml);

														if ($frontAdmitCardHtml !== '') {
															$year = $_GET['syear'];
															$class = $_GET['class'];
															$section = isset($_GET['section']) ? $_GET['section'] : '';
															$roll = $_GET['roll'];
															$exam = $_GET['exam'];

															$snapshotMeta = [
																'admit-card-front',
																($class !== '' ? 'class-' . $class : ''),
																($section !== '' ? 'section-' . $section : ''),
																($year !== '' ? 'year-' . $year : ''),
																($exam !== '' ? 'exam-' . $exam : ''),
																($selectedGroupFilter > 0 ? 'group-' . $selectedGroupFilter : ''),
																($selectedGenderFilter !== '' ? 'gender-' . $selectedGenderFilter : ''),
																($roll !== '' ? 'roll-' . $roll : '')
															];
															$snapshotArgs = [
																'subdir' => 'admit-cards',
																'prefix' => 'admit-card-front',
																'purge_previous' => true
															];
															$snapshot = s3s_store_html_snapshot($frontAdmitCardHtml, $snapshotMeta, $snapshotArgs);
															if ($snapshot['path'] !== '') {
																$frontStaticFilePath = $snapshot['path'];
															}
															if ($snapshot['url'] !== '') {
																$frontStaticUrl = $snapshot['url'];
															}
															if ($snapshot['error'] !== '' && $frontSnapshotError === '') {
																$frontSnapshotError = $snapshot['error'];
															}
														}

														if ($frontStaticFilePath && file_exists($frontStaticFilePath)) {
															include $frontStaticFilePath;
														} else {
															echo $frontAdmitCardHtml;
														}

														if ($frontSnapshotError !== '') {
															echo '<div class="alert alert-warning" role="alert">' . esc_html($frontSnapshotError) . '</div>';
														}

														if ($frontStaticUrl !== '') {
														?>
															<script type="text/javascript">
																(function() {
																	var area = document.getElementById('printArea');
																	if (area) {
																		area.setAttribute('data-static-url', '<?= esc_js($frontStaticUrl) ?>');
																	}
																})();
															</script>
														<?php
														}
														?>

													</div>
											</div>
										</div>

										<!-- Back Side Tab -->
										<div class="tab-pane" id="backSide">
											<!-- Back Side of Admit Card with Exam Schedule -->
											<div class="col-md-12" style="margin-top: 20px;">
												<button onclick="print('printAreaBack')" class="pull-right btn btn-success">Print Back Side</button>
												<h4>Back Side - Exam Schedule</h4>
											</div>
											<div id="printAreaBack" class="col-md-12 printBG">
												<div class="printArea" style="margin: 0 30px;">
													<style type="text/css">
														@page {
															size: auto;
															margin: 0px;
														}

														#printAreaBack {
															min-height: 100%;
															height: auto !important;
															height: 100%;
														}

														.backMainBox {
															max-width: 8.27in;
															min-height: 100vh;
															display: inline-block;
															border: 2px solid #333333;
															overflow: hidden;
															margin: 20px 0 80px 0;
															font-family: sans-serif;
															width: 100%;
															position: relative;
															min-height: 400px;
														}

														.backMainBox .backHeader {
															text-align: center;
															padding: 15px 30px 10px;
															border-bottom: 2px solid #333;
														}

														.backMainBox .backHeader h3 {
															margin: 5px 0;
															font-size: 20px;
															color: #337ab7;
															font-weight: bold;
														}

														.backMainBox .scheduleTable {
															width: 100%;
															border-collapse: collapse;
															margin: 0;
														}

														.backMainBox .scheduleTable th,
														.backMainBox .scheduleTable td {
															border: 1px solid #333;
															padding: 10px 8px;
															text-align: left;
														}

														.backMainBox .scheduleTable th {
															background: #f0f0f0;
															print-color-adjust: exact;
															-webkit-print-color-adjust: exact;
															font-weight: bold;
															font-size: 0px14px;
														}

														.backMainBox .scheduleTable td {
															font-size: 13px;
														}

														/* New list style for backside schedule (date, code, name) */
														.backMainBox .scheduleList {
															margin: 15px 20px;
															border-radius: 5px;
															overflow: hidden;
															display: flex;
															justify-content: space-around;
															min-height: 31vh;
														}

														.backMainBox .scheduleList ul {
															margin: 0;
															padding: 0;
															list-style: none;
														}

														.backMainBox .scheduleList li {
															padding: 2px 5px;
															font-size: 12px;
															display: flex;
															align-items: center;
															gap: 3px;
														}

														.backMainBox .scheduleList .date {
															width: 10%;
															min-width: 80px;
															font-weight: bold
														}

														.backMainBox .scheduleList .code {
															width: 20%;
															min-width: 20px
														}

														.backMainBox .scheduleList .name {
															flex: 1
														}

														.backMainBox .instructions {
															padding: 15px 30px;
															font-size: 12px;
															line-height: 1.6;
														}

														.backMainBox .instructions h4 {
															margin: 0 0 10px 0;
															font-size: 0px14px;
															font-weight: bold;
														}

														.backMainBox .instructions ul {
															margin: 5px 0;
															padding-left: 20px;
														}

														.backMainBox .instructions li {
															margin-bottom: 5px;
														}

														.backMainBox .footer {
															text-align: center;
															padding: 10px;
															font-size: 10px;
															color: #888;
															border-top: 1px solid #ddd;
															margin-top: 15px;
														}
													</style>

													<?php
													// Retrieve exam schedule
													$backScheduleRow = $wpdb->get_row($wpdb->prepare(
														'SELECT subject_dates FROM ct_exam_schedule WHERE classid = %d AND examid = %d AND year = %s LIMIT 1',
														$class,
														$exam,
														$year
													));
													$backSubjectDates = array();
													if ($backScheduleRow && !empty($backScheduleRow->subject_dates)) {
														$backSubjectDates = json_decode($backScheduleRow->subject_dates, true);
														if (!is_array($backSubjectDates)) {
															$backSubjectDates = array();
														}
													}

													$backSubjectSorter = static function ($left, $right) use ($backSubjectDates) {
														$rawA = isset($backSubjectDates[$left->subjectid]) ? $backSubjectDates[$left->subjectid] : '';
														$rawB = isset($backSubjectDates[$right->subjectid]) ? $backSubjectDates[$right->subjectid] : '';

														$tsA = false;
														if ($rawA !== '') {
															$dtA = DateTime::createFromFormat('Y-m-d', $rawA);
															if ($dtA instanceof DateTime) {
																$tsA = $dtA->getTimestamp();
															} else {
																$parsedA = strtotime($rawA);
																if ($parsedA !== false) {
																	$tsA = $parsedA;
																}
															}
														}

														$tsB = false;
														if ($rawB !== '') {
															$dtB = DateTime::createFromFormat('Y-m-d', $rawB);
															if ($dtB instanceof DateTime) {
																$tsB = $dtB->getTimestamp();
															} else {
																$parsedB = strtotime($rawB);
																if ($parsedB !== false) {
																	$tsB = $parsedB;
																}
															}
														}

														if ($tsA !== false && $tsB !== false) {
															if ($tsA === $tsB) {
																return strcmp((string) ($left->subjectName ?? ''), (string) ($right->subjectName ?? ''));
															}
															return ($tsA < $tsB) ? -1 : 1;
														}

														if ($tsA !== false) {
															return -1;
														}

														if ($tsB !== false) {
															return 1;
														}

														return strcmp((string) ($left->subjectName ?? ''), (string) ($right->subjectName ?? ''));
													};

													// Get exam subjects
													$examSubjectsRaw = $wpdb->get_var($wpdb->prepare('SELECT examSubjects FROM ct_exam WHERE examid = %d', $exam));
													$backExamSubjectIds = array();
													if (!empty($examSubjectsRaw)) {
														$decodedExamSubjects = json_decode($examSubjectsRaw, true);

														if (is_array($decodedExamSubjects)) {
															foreach ($decodedExamSubjects as $subjectId) {
																$subjectId = intval($subjectId);
																if ($subjectId > 0) {
																	$backExamSubjectIds[] = $subjectId;
																}
															}
														}
													}
													$backSubjectFilterClause = '';
													if (!empty($backExamSubjectIds)) {
														$backSubjectFilterClause = ' AND subjectid IN (' . implode(',', $backExamSubjectIds) . ')';
													}

													// Get all subjects for this class
													$backSubjects = $wpdb->get_results("SELECT subjectid, subjectName, subCode, sub4th FROM ct_subject WHERE subjectClass = $class" . $backSubjectFilterClause . ' ORDER BY subid');

													// Sort subjects by date
													if (!empty($backSubjects)) {
														usort($backSubjects, $backSubjectSorter);
													}

													// Get exam name
													$examInfo = $wpdb->get_row($wpdb->prepare('SELECT examName FROM ct_exam WHERE examid = %d', $exam));

													if ($groupsBy) {
														foreach ($groupsBy as $student) {
															// Determine subjects for this student. If student has a group, limit optional subjects to that group.
															$studentGroup = isset($student->infoGroup) ? intval($student->infoGroup) : 0;
															$studentReligion = isset($student->stdReligion) ? trim($student->stdReligion) : '';
															$student4thSub = array();
															if (isset($student->info4thSub)) {
																if (is_numeric($student->info4thSub)) {
																	$student4thSub[] = trim((string)$student->info4thSub);
																} else {
																	$decoded = json_decode($student->info4thSub, true);
																	if (is_array($decoded)) {
																		$student4thSub = array_map('strval', $decoded);
																	} else {
																		$student4thSub = array_map('trim', explode(',', $student->info4thSub));
																	}
																}
															}

															// Base filter: subjects for the class (and optional exam subject restriction)
															$studentSubjectFilter = "WHERE subjectClass = $class" . $backSubjectFilterClause;
															if ($studentGroup > 0) {
																// Include core subjects (subOptinal = 0) and optional subjects that match this group or 'all'.
																$studentSubjectFilter .= " AND (subOptinal = 0 OR (subOptinal = 1 AND (forGroup = 'all' OR JSON_CONTAINS(forGroup, '\"" . $studentGroup . "\"'))))";
															}
															$backSubjectsForStudent = $wpdb->get_results('SELECT subjectid, subjectName, subCode, sub4th, assessment FROM ct_subject ' . $studentSubjectFilter . ' ORDER BY subid');
															// Sort per-student subject list by date as earlier
															if (!empty($backSubjectsForStudent)) {
																$backSubjectsForStudent = array_values(array_filter($backSubjectsForStudent, function ($subject) use ($studentReligion, $religionSubjectMap, $student4thSub) {
																	if (isset($subject->assessment) && $subject->assessment == 1) {
																		return false;
																	}
																	if (isset($subject->sub4th) && $subject->sub4th == 1) {
																		if (!in_array((string)$subject->subjectid, $student4thSub)) {
																			return false;
																		}
																	}
																	if (!isset($subject->subCode)) {
																		return true;
																	}
																	$code = intval($subject->subCode);
																	$matchedReligion = array_search($code, $religionSubjectMap, true);
																	if ($matchedReligion === false) {
																		return true;
																	}
																	return strcasecmp($studentReligion, $matchedReligion) === 0;
																}));
																if (!empty($backSubjectsForStudent)) {
																	usort($backSubjectsForStudent, $backSubjectSorter);
																}
															}
													?>
															<div class="backMainBox">
																<h2 style="text-align: center;margin-top: 10px;">Exam Routine</h2>
																<div class="scheduleList">
																	<ul>
																		<li>
																			<span class="date">Date</span>
																			<span class="code" style="font-weight: bold;">Code</span>
																			<span class="name" style="font-weight: bold;">Subject Name</span>
																		</li>
																		<?php
																		$sl = 1;
																		foreach ($backSubjectsForStudent as $subject) {
																			if ($sl > ceil(count($backSubjectsForStudent) / 2)) {
																				break;
																			}
																			$subjectDate = isset($backSubjectDates[$subject->subjectid]) && !empty($backSubjectDates[$subject->subjectid]) ? $backSubjectDates[$subject->subjectid] : '';
																			// Format date if available
																			$formattedDate = '';
																			if ($subjectDate) {
																				$dateObj = DateTime::createFromFormat('Y-m-d', $subjectDate);
																				if ($dateObj) {
																					$formattedDate = $dateObj->format('d M, Y');
																				}
																			}
																		?>
																			<li>
																				<span class="date"><?php echo esc_html($formattedDate); ?></span>
																				<span class="code"><?php echo esc_html($subject->subCode); ?></span>
																				<span class="name"><?php echo esc_html($subject->subjectName); ?></span>
																			</li>
																		<?php
																			$sl++;
																		}
																		?>
																	</ul>
																	<ul>
																		<li>
																			<span class="date">Date</span>
																			<span class="code" style="font-weight: bold;">Code</span>
																			<span class="name" style="font-weight: bold;">Subject Name</span>
																		</li>
																		<?php
																		$sl = 1;
																		foreach ($backSubjectsForStudent as $subject) {
																			if ($sl <= ceil(count($backSubjectsForStudent) / 2)) {
																				$sl++;
																				continue;
																			}
																			$subjectDate = isset($backSubjectDates[$subject->subjectid]) && !empty($backSubjectDates[$subject->subjectid]) ? $backSubjectDates[$subject->subjectid] : '';
																			// Format date if available
																			$formattedDate = '';
																			if ($subjectDate) {
																				$dateObj = DateTime::createFromFormat('Y-m-d', $subjectDate);
																				if ($dateObj) {
																					$formattedDate = $dateObj->format('d M, Y');
																				}
																			}
																		?>
																			<li>
																				<span class="date"><?php echo esc_html($formattedDate); ?></span>
																				<span class="code"><?php echo esc_html($subject->subCode); ?></span>
																				<span class="name"><?php echo esc_html($subject->subjectName); ?></span>
																			</li>
																		<?php
																			$sl++;
																		}
																		?>
																	</ul>
																</div>
															</div>
													<?php
														}
													}
													?>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					</div>
				</div>
			</div>
		</div>

		<div id="editor"></div>
	<?php } ?>
	</div>

	<script>
		// Initialize tabs
		jQuery(document).ready(function($) {
			// Handle tab switching
			$('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
				// Store the active tab in the URL hash
				window.location.hash = e.target.hash;
			});

			// Activate tab from URL hash on page load
			var hash = window.location.hash;
			if (hash) {
				$('.nav-tabs a[href="' + hash + '"]').tab('show');
			}
		});
	</script>

	<?php if (!is_admin()) { ?>
		</div>
	<?php get_footer();
	} ?>

	<script type="text/javascript">
		(function($) {
			$('#resultClass').change(function() {
				var $siteUrl = $('#theSiteURL').text();
				$.ajax({
					url: $siteUrl + "/inc/ajaxAction.php",
					method: "POST",
					data: {
						class: $(this).val(),
						type: 'getExams'
					},
					dataType: "html"
				}).done(function(msg) {
					$("#resultExam").html(msg);
					$("#resultExam").prop('disabled', false);
				});

				$.ajax({
					url: $siteUrl + "/inc/ajaxAction.php",
					method: "POST",
					data: {
						class: $(this).val(),
						type: 'getYears'
					},
					dataType: "html"
				}).done(function(msg) {
					$("#resultYear").html(msg);
					$("#resultYear").prop('disabled', false);
				});

				$.ajax({
					url: $siteUrl + "/inc/ajaxAction.php",
					method: "POST",
					data: {
						class: $(this).val(),
						type: 'getSection'
					},
					dataType: "html"
				}).done(function(msg) {
					$("#resultSection").html(msg);
					$("#resultSection").prop('disabled', false);
				});
			});
		})(jQuery);

		function printAdmitCardFront(divId) {
			var container = document.getElementById(divId);
			var staticUrl = '';
			if (container && typeof container.getAttribute === 'function') {
				staticUrl = container.getAttribute('data-static-url') || '';
			}

			var buildHeadContent = function() {
				var safeBaseHref = document.location.href.replace(/"/g, '&quot;');
				var headContent = '<meta charset="utf-8"><title>Admit Card</title><base href="' + safeBaseHref + '">';
				document.querySelectorAll('head link[rel="stylesheet"], head style').forEach(function(node) {
					if (node.tagName && node.tagName.toLowerCase() === 'link' && node.href) {
						headContent += '<link rel="stylesheet" href="' + node.href + '">';
					} else if (node.outerHTML) {
						headContent += node.outerHTML;
					}
				});
				return headContent;
			};

			var openPrintWindow = function(html) {
				if (!html) {
					return false;
				}

				var printWindow = window.open('', '_blank', 'width=1024,height=768');
				if (!printWindow) {
					return false;
				}

				var doc = printWindow.document;
				var headContent = buildHeadContent();
				doc.open();
				doc.write('<!doctype html><html><head>' + headContent + '</head><body>' + html + '<script>window.addEventListener("load", function() { window.focus(); window.print(); setTimeout(function() { window.close(); }, 250); });<\/script></body></html>');
				doc.close();

				return true;
			};

			var fallbackToContainer = function() {
				if (container) {
					openPrintWindow(container.innerHTML);
				}
			};

			if (staticUrl && window.fetch) {
				fetch(staticUrl, {
						cache: 'reload'
					})
					.then(function(response) {
						if (!response.ok) {
							throw new Error('Failed to load static admit card');
						}
						return response.text();
					})
					.then(function(html) {
						if (!openPrintWindow(html)) {
							fallbackToContainer();
						}
					})
					.catch(function() {
						fallbackToContainer();
					});
				return true;
			}

			if (staticUrl && !window.fetch) {
				fallbackToContainer();
				return true;
			}

			if (container) {
				return openPrintWindow(container.innerHTML);
			}

			return false;
		}

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