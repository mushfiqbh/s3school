<?php

/*
 * * Template Name: Admin AdmitCard
 */
global $wpdb;
global $s3sRedux;

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

$selectedGroupFilter = isset($_GET['group']) ? intval($_GET['group']) : 0;
$rawGenderFilter = isset($_GET['gender']) ? trim($_GET['gender']) : '';
$selectedGenderFilter = array_key_exists($rawGenderFilter, $genderSelectOptions) ? $rawGenderFilter : '';
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
													$classQuery = $wpdb->get_results('SELECT classid,className FROM ct_class WHERE classid IN (SELECT examClass FROM ct_exam GROUP BY examClass ORDER BY className ASC)');
													

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
											<li><a href="#backSide" data-toggle="tab">Back Side</a></li>
										</ul>
									</div>

									<!-- Tab Content -->
									<div class="col-md-12">
										<div class="tab-content">
											<!-- Front Side Tab -->
											<div class="tab-pane active" id="frontSide">
												<?php if (isset($_GET['syear'])) { ?>
													<div class="col-md-12">
														<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>
													</div>
													<div id="printArea" class="col-md-12 printBG">

														<div class="printArea" style="margin: 10px 30px 0;">
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
																	margin: 20px 0 80px 0;
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
																	font-size: 24px;
																}

																#itemMainBox .instAddrs {
																	margin: 0 0 20px 0;
																	color: #888888;
																	font-size: 18px;
																}

																#itemMainBox .examName {
																	margin: 0 auto;
																	text-align: center;
																	font-size: 25px;
																}

																#itemMainBox .examName h3 {
																	margin: 10px 0;
																	font-size: 20px;
																}

																#itemMainBox .itemInfo {
																	clear: both;
																	color: #f7740c;
																	background: #f0f0f0;
																	padding: 10px;
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
																	float: left;
																	font-size: 12px;
																}

																#itemMainBox .admitNote p {
																	margin: 0;
																	padding-left: 15px;
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
																$query = "SELECT studentid,stdName,stdFather,infoRoll,className,stdImg,infoYear,stdPhone,stdFather,groupName,ct_studentinfo.infoGroup,sectionName,examName,stdAdmitYear,stdCreatedAt  FROM ct_student
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
															}
															if ($groupsBy) {
																foreach ($groupsBy as $key => $value) {
																	$datetime = new DateTime($value->stdCreatedAt);
															?>
																	<div id="itemMainBox">
																		<div class="itemWaterMark">
																			<img src="<?= $s3sRedux['instLogo']['url'] ?>">
																		</div>
																		<div style="padding: 15px 30px 5px">
																			<div style="text-align: center; float: left; width: 100%">
																				<div style="position: relative;padding-left: 90px">
																					<img class="instLogo" src="<?= $s3sRedux['instLogo']['url'] ?>">

																					<h2 class="instName"><?= $s3sRedux['institute_name'] ?></h2>
																					<h4 class="instAddrs"><?= $s3sRedux['institute_address'] ?></h4>

																				</div>

																			</div>
																			<div class="itemInfo">
																				<h3 class="admitCard">Admit Card</h3>
																			</div>
																			<div class="examName">
																				<h3><?= $value->examName ?> <?= $year ?></h3>
																			</div>
																			<div style="float: left; clear: both;width: 100%;margin-bottom: 20px;">
																				<div style="width: 75%; float: left;">
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
																				<div style="width: 25%; float: right; text-align: right;">
																					<?php if (!empty($value->stdImg)) { ?>
																						<img style="height: 100px; " alt="<?= $value->stdName ?>_img" src="<?= $value->stdImg ?>">
																					<?php } else { ?>
																						<img style="height: 100px; " alt="<?= $value->stdName ?>_img" src="<?= get_template_directory_uri() ?>/img/No_Image.jpg">
																					<?php } ?>
																				</div>
																			</div>
																			<hr>
																			<table class="admitNote">
																				<?php if ($s3sRedux['admitCareNote'] != '') {
																					$notes = explode("\n", $s3sRedux['admitCareNote']); ?>
																					<?php foreach ($notes as $note) { ?>
																						<tr>
																							<td><?= $note ?></td>
																						</tr>
																					<?php } ?>
																				<?php } ?>
																			</table>

																			<div class="princSign" style="text-align: center;">
																				<img width="110" style="max-width: 110px;" src="<?= $s3sRedux['principalSign']['url'] ?>"><br>
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
															} else {
																echo "<h3 class='text-center'>No Student Found</h3>";
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
														<div class="printArea" style="margin: 10px 30px 0;">
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
																	font-size: 14px;
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
																	font-size: 14px;
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
															$backSubjects = $wpdb->get_results("SELECT subjectid, subjectName, subCode FROM ct_subject WHERE subjectClass = $class" . $backSubjectFilterClause . ' ORDER BY subid');

															// Sort subjects by date
															if (!empty($backSubjects)) {
																usort($backSubjects, function ($a, $b) use ($backSubjectDates) {
																	$dateA = isset($backSubjectDates[$a->subjectid]) && !empty($backSubjectDates[$a->subjectid]) ? $backSubjectDates[$a->subjectid] : '';
																	$dateB = isset($backSubjectDates[$b->subjectid]) && !empty($backSubjectDates[$b->subjectid]) ? $backSubjectDates[$b->subjectid] : '';
																	if ($dateA && $dateB) {
																		return strcmp($dateA, $dateB);
																	}
																	if ($dateA)
																		return -1;
																	if ($dateB)
																		return 1;
																	return 0;
																});
															}

															// Get exam name
															$examInfo = $wpdb->get_row($wpdb->prepare('SELECT examName FROM ct_exam WHERE examid = %d', $exam));

															if ($groupsBy) {
																foreach ($groupsBy as $student) {
																	// Determine subjects for this student. If student has a group, limit optional subjects to that group.
																	$studentGroup = isset($student->infoGroup) ? intval($student->infoGroup) : 0;
																	// Base filter: subjects for the class (and optional exam subject restriction)
																	$studentSubjectFilter = "WHERE subjectClass = $class" . $backSubjectFilterClause;
																	if ($studentGroup > 0) {
																		// Include core subjects (subOptinal = 0) and optional subjects that match this group or 'all'.
																		$studentSubjectFilter .= " AND (subOptinal = 0 OR (subOptinal = 1 AND (forGroup IN ('" . $studentGroup . "', 'all') OR forGroup LIKE '%\"" . $studentGroup . '"%\')))';
																	}
																	$backSubjectsForStudent = $wpdb->get_results('SELECT subjectid, subjectName, subCode FROM ct_subject ' . $studentSubjectFilter . ' ORDER BY subid');
																	// Sort per-student subject list by date as earlier
																	if (!empty($backSubjectsForStudent)) {
																		usort($backSubjectsForStudent, function ($a, $b) use ($backSubjectDates) {
																			$dateA = isset($backSubjectDates[$a->subjectid]) && !empty($backSubjectDates[$a->subjectid]) ? $backSubjectDates[$a->subjectid] : '';
																			$dateB = isset($backSubjectDates[$b->subjectid]) && !empty($backSubjectDates[$b->subjectid]) ? $backSubjectDates[$b->subjectid] : '';
																			if ($dateA && $dateB) {
																				return strcmp($dateA, $dateB);
																			}
																			if ($dateA)
																				return -1;
																			if ($dateB)
																				return 1;
																			return 0;
																		});
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

<?php
// ===============================================================
// FIX 409 CONFLICT - HANDLE AJAX ACTIONS LOCALLY (At End of File)
// ===============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'])) {

  // Clean output buffer to ensure JSON/HTML response is valid
  while (ob_get_level()) {
    ob_end_clean();
  }

  // ------------------------------------------
  // Get Exams
  // ------------------------------------------
  if ($_POST['type'] == 'getExams') {
    $class = $_POST['class'];
    $exams = $wpdb->get_results("SELECT examid,examName FROM ct_exam WHERE examClass = '$class'");
    if (empty($exams)) {
      echo "<option value=''>No Exam for this Class</option>";
    } else {
      echo "<option value=''>Select An Exam</option>";
    }
    foreach ($exams as $exam) {
      echo "<option value='{$exam->examid}'>{$exam->examName}</option>";
    }
    exit;
  }

  // ------------------------------------------
  // Get Years
  // ------------------------------------------
  elseif ($_POST['type'] == 'getYears') {
    $class = $_POST['class'];
    $years = $wpdb->get_results("SELECT infoYear FROM ct_studentinfo WHERE infoClass = $class GROUP BY infoYear ORDER BY infoYear ASC");
    if (empty($years)) {
      echo "<option value=''>No Student In this class</option>";
    } else {
      echo "<option value=''>Year</option>";
    }
    foreach ($years as $year) {
      echo "<option value='{$year->infoYear}'>{$year->infoYear}</option>";
    }
    exit;
  }

  // ------------------------------------------
  // Get Section
  // ------------------------------------------
  elseif ($_POST['type'] == 'getSection') {
    $class = $_POST['class'];
    $sections_query = "SELECT sectionid,sectionName FROM ct_section WHERE forClass = '$class'";
    
    $sections_query .= " ORDER BY sectionName";
    $sections = $wpdb->get_results($sections_query);

    if (!empty($sections)) {
      echo "<option value=''>Section</option>";
      foreach ($sections as $section) {
        echo "<option value='{$section->sectionid}'>{$section->sectionName}</option>";
      }
    } else {
      echo "<option value=''>No sections available</option>";
    }
    exit;
  }

  // ------------------------------------------
  // Get Groups
  // ------------------------------------------
  elseif ($_POST['type'] == 'getGroupsByClass') {
    $class = $_POST['class'];
    $groups_query = "SELECT DISTINCT ct_group.groupId, ct_group.groupName 
            FROM ct_group 
            INNER JOIN ct_studentinfo ON ct_studentinfo.infoGroup = ct_group.groupId 
            WHERE ct_studentinfo.infoClass = '$class'";
    
    $groups_query .= " ORDER BY ct_group.groupName ASC";
    $groups = $wpdb->get_results($groups_query);

    echo "<option value=''>All Groups</option>";
    foreach ($groups as $group) {
      echo "<option value='{$group->groupId}'>{$group->groupName}</option>";
    }
    exit;
  }

  // ------------------------------------------
  // Get Exam Subjects
  // ------------------------------------------
  elseif ($_POST['type'] == 'getExamSubject') {
    $exam = intval($_POST['exam']);
    $group = isset($_POST['group']) ? $_POST['group'] : '';
    $subjects = [];

    $subs = $wpdb->get_results("SELECT examSubjects FROM ct_exam WHERE examid = $exam");

    if (!empty($subs[0]->examSubjects)) {
      $subs = json_decode($subs[0]->examSubjects, true);
    } else {
      $subs = [];
    }

    if (!empty($subs)) {
      $subs_escaped = array_map('intval', $subs);
      $subjectQuery = "SELECT subjectid,subjectName FROM ct_subject 
                WHERE subjectid IN (" . implode(',', $subs_escaped) . ")";

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
    exit;
  }
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
        'resCQ'     => $cq[$id],
        'resMCQ'     => $mcq[$id],
        'resPrec'   => $prc[$id],
        'resCa'   => $ca[$id],
        'resTotal'   => isnum($cq[$id]) + isnum($mcq[$id]) + isnum($prc[$id]) + isnum($ca[$id])
      ),
      array('resultId' => $id)
    );
    if ($update) {
      $response = $update;
    }
  }
  if ($response) {
    $message = array('status' => 'success', 'message' => 'Successfully updated');
  } else {
    $message = array('status' => 'faild', 'message' => 'Something wrong please try again');
  }
} ?>

<script type="text/javascript">
  // ==================================
  // HANDLE AJAX ACTIONS LOCALLY
  // ==================================
  (function($) {
    // Use current page as AJAX URL for standalone processing
    var ajaxUrl = '';

    $('#resultClass').change(function() {
      var selectedClass = $(this).val();

      // Fetch Exams
      $.ajax({
        url: ajaxUrl,
        method: "POST",
        data: {
          class: selectedClass,
          type: 'getExams'
        },
        dataType: "html"
      }).done(function(msg) {
        $("#resultExam").html(msg);
        $("#resultExam").prop('disabled', false);
        // Reset dependent dropdowns
        $("#resultSubject").prop('disabled', true).html('<option disabled selected>Select exam First</option>');
      });

      // Fetch Years
      $.ajax({
        url: ajaxUrl,
        method: "POST",
        data: {
          class: selectedClass,
          type: 'getYears'
        },
        dataType: "html"
      }).done(function(msg) {
        $("#resultYear").html(msg);
        $("#resultYear").prop('disabled', false);
      });

      // Fetch Sections
      $.ajax({
        url: ajaxUrl,
        method: "POST",
        data: {
          class: selectedClass,
          type: 'getSection'
        },
        dataType: "html"
      }).done(function(msg) {
        $("#resultSection").html(msg);
        $("#resultSection").prop('disabled', false);
      });

      // Fetch All Groups
      $.ajax({
        url: ajaxUrl,
        method: "POST",
        data: {
          class: selectedClass,
          type: 'getGroupsByClass'
        },
        dataType: "html"
      }).done(function(msg) {
        $("#resultGroup").html(msg);
        $("#resultGroup").prop('disabled', false);
      });
    });

    // Fetch Subjects when Exam Changes
    $('#resultExam').change(function() {
      var selectedExam = $(this).val();
      var selectedGroup = $('#resultGroup').val();

      $.ajax({
        url: ajaxUrl,
        method: "POST",
        data: {
          exam: selectedExam,
          group: selectedGroup,
          type: 'getExamSubject'
        },
        dataType: "html"
      }).done(function(msg) {
        $("#resultSubject").html(msg);
        $("#resultSubject").prop('disabled', false);
      });
    });

    // Fetch Subjects when Group Changes
    $('#resultGroup').change(function() {
      var selectedExam = $('#resultExam').val();
      var selectedGroup = $(this).val();

      if (selectedExam) {
        $.ajax({
          url: ajaxUrl,
          method: "POST",
          data: {
            exam: selectedExam,
            group: selectedGroup,
            type: 'getExamSubject'
          },
          dataType: "html"
        }).done(function(msg) {
          $("#resultSubject").html(msg);
          $("#resultSubject").prop('disabled', false);
        });
      }
    });

    // Interactive validation for result inputs (Client-side only)
    $('.resultInput').keyup(function(event) {
      $this = $(this);
      $val = $this.val();
      $max = $this.data('max');

      if ($val == '' || $val < ($max + 1) || $val == 'A' || $val == 'a') {
        $this.css('border-color', '#ddd');
        $this.removeClass('haserror');
      } else {
        $this.addClass('haserror');
        $this.css('border-color', 'red');
        $('.resultSubmit').prop('disabled', true);
      }

      if ($('.resultInput.haserror').length == 0) {
        $('.resultSubmit').prop('disabled', false);
      }
    });

  })(jQuery);
</script>