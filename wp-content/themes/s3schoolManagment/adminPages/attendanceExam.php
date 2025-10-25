<?php

/**
 * Template Name: Admin ExamAttendance
 */
global $wpdb;
global $s3sRedux;

$attendanceGroups      = $wpdb->get_results("SELECT groupId, groupName FROM ct_group ORDER BY groupName ASC");
$attendanceGroupLookup = array();
if (! empty($attendanceGroups)) {
	foreach ($attendanceGroups as $attendanceGroup) {
		$attendanceGroupLookup[$attendanceGroup->groupId] = $attendanceGroup->groupName;
	}
}


$genderSelectOptions = array(
	''  => 'All Genders',
	'1' => 'Boy',
	'0' => 'Girl',
	'2' => 'Other',
);

$selectedGroupFilter = isset($_GET['group']) ? intval($_GET['group']) : 0;
$rawGenderFilter     = isset($_GET['gender']) ? trim($_GET['gender']) : '';
$selectedGenderFilter = array_key_exists($rawGenderFilter, $genderSelectOptions) ? $rawGenderFilter : '';
$groupFilterLabel     = ($selectedGroupFilter > 0 && isset($attendanceGroupLookup[$selectedGroupFilter])) ? $attendanceGroupLookup[$selectedGroupFilter] : 'All Groups';
$genderFilterLabel    = ($selectedGenderFilter !== '' && isset($genderSelectOptions[$selectedGenderFilter])) ? $genderSelectOptions[$selectedGenderFilter] : 'All Genders';
?>

<?php if (! is_admin()) {
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
										<h3>Exam Attendance<br><small>Create Students Exam Attendance sheet</small></h3>
									</div>

									<div class="panel-body" style="padding-top:0;">
										<!-- Navigation Tabs -->
										<ul class="nav nav-tabs" role="tablist" style="margin-bottom:20px;">
											<li role="presentation" class="active"><a href="#individual" aria-controls="individual" role="tab" data-toggle="tab">Individual Attendance</a></li>
											<li role="presentation"><a href="#classwise" aria-controls="classwise" role="tab" data-toggle="tab">Class-wise Attendance</a></li>
										</ul>

										<!-- Tab Content -->
										<div class="tab-content">
											<!-- Individual Attendance Tab -->
											<div role="tabpanel" class="tab-pane active" id="individual">
												<div class="panel-body" style="padding:0;">

													<form class="form-inline" action="" method="GET">
														<input type="hidden" name="page" value="examattendance">

														<div class="form-group">
															<label>Class</label>
															<select id='resultClass' class="form-control" name="class" required>
																<?php

																$classQuery = $wpdb->get_results("SELECT classid,className FROM ct_class WHERE classid IN (SELECT examClass FROM ct_exam GROUP BY examClass ORDER BY className ASC)");
																echo "<option value=''>Select Class</option>";

																foreach ($classQuery as $class) {
																	echo "<option value='" . $class->classid . "'>" . $class->className . "</option>";
																}
																?>
															</select>
														</div>

														<div class="form-group">
															<label>Year</label>
															<select id='resultYear' class="form-control" name="syear" required disabled>
																<option disabled selected>Select Class First</option>
															</select>
														</div>

														<div class="form-group ">
															<label>Section</label>
															<select id="resultSection" class="form-control" name="section" required disabled>
																<option disabled selected>Select Class First</option>
															</select>
														</div>

														<div class="form-group">
															<label>Group</label>
															<select id="resultGroup" class="form-control" name="group">
																<option value="" <?= $selectedGroupFilter === 0 ? ' selected' : '' ?>>All Groups</option>
																<?php if (! empty($attendanceGroups)) {
																	foreach ($attendanceGroups as $groupOption) { ?>
																		<option value="<?= esc_attr($groupOption->groupId); ?>" <?= ($selectedGroupFilter === intval($groupOption->groupId)) ? ' selected' : '' ?>><?= esc_html($groupOption->groupName); ?></option>
																<?php }
																} ?>
															</select>
														</div>

														<div class="form-group">
															<label>Gender</label>
															<select id="resultGender" class="form-control" name="gender">
																<?php foreach ($genderSelectOptions as $genderValue => $genderLabel) { ?>
																	<option value="<?= esc_attr($genderValue); ?>" <?= ($selectedGenderFilter === $genderValue) ? ' selected' : '' ?>><?= esc_html($genderLabel); ?></option>
																<?php } ?>
															</select>
														</div>

														<div class="form-group ">
															<label>Exam</label>
															<select id="resultExam" class="form-control" name="exam" required disabled>
																<option disabled selected>Select Class First</option>
															</select>
														</div>

														<div class="form-group" id="idRows">
															<input style="width: 80px;" class="form-control" type="number" name="rows" placeholder="Rows">
														</div>
														<div class="form-group" id="idRoll">
															<input style="width: 80px;" class="form-control" type="text" name="roll" placeholder="Roll">
														</div>
														<div class="form-group">
															<input type="submit" name="creatId" value="Genarate" class="btn btn-primary">
														</div>
													</form>
												</div>
											</div>

											<!-- Class-wise Attendance Tab -->
											<div role="tabpanel" class="tab-pane" id="classwise">
												<div class="panel-body" style="padding:0;">
													<form class="form-inline" action="" method="GET">
														<input type="hidden" name="page" value="examattendance">
														<input type="hidden" name="mode" value="classwise">

														<div class="form-group">
															<label>Class</label>
															<select id='classwiseClass' class="form-control" name="class" required>
																<?php
																$classQuery = $wpdb->get_results("SELECT classid,className FROM ct_class WHERE classid IN (SELECT examClass FROM ct_exam GROUP BY examClass ORDER BY className ASC)");
																echo "<option value=''>Select Class</option>";
																foreach ($classQuery as $class) {
																	echo "<option value='" . $class->classid . "'>" . $class->className . "</option>";
																}
																?>
															</select>
														</div>

														<div class="form-group">
															<label>Year</label>
															<select id='classwiseYear' class="form-control" name="syear" required disabled>
																<option disabled selected>Select Class First</option>
															</select>
														</div>

														<div class="form-group ">
															<label>Section</label>
															<select id="classwiseSection" class="form-control" name="section" required disabled>
																<option disabled selected>Select Class First</option>
															</select>
														</div>

														<div class="form-group">
															<label>Group</label>
															<select id="classwiseGroup" class="form-control" name="group">
																<option value="" <?= $selectedGroupFilter === 0 ? ' selected' : '' ?>>All Groups</option>
																<?php if (! empty($attendanceGroups)) {
																	foreach ($attendanceGroups as $groupOption) { ?>
																		<option value="<?= esc_attr($groupOption->groupId); ?>" <?= ($selectedGroupFilter === intval($groupOption->groupId)) ? ' selected' : '' ?>><?= esc_html($groupOption->groupName); ?></option>
																<?php }
																} ?>
															</select>
														</div>

														<div class="form-group">
															<label>Gender</label>
															<select id="classwiseGender" class="form-control" name="gender">
																<?php foreach ($genderSelectOptions as $genderValue => $genderLabel) { ?>
																	<option value="<?= esc_attr($genderValue); ?>" <?= ($selectedGenderFilter === $genderValue) ? ' selected' : '' ?>><?= esc_html($genderLabel); ?></option>
																<?php } ?>
															</select>
														</div>

														<div class="form-group ">
															<label>Exam</label>
															<select id="classwiseExam" class="form-control" name="exam" required disabled>
																<option disabled selected>Select Class First</option>
															</select>
														</div>

														<div class="form-group" id="idExtraRows">
															<input style="width: 80px;" class="form-control" type="number" name="extrarows" placeholder="Extra Rows" value="5">
														</div>
														<div class="form-group">
															<input type="submit" name="createClasswise" value="Generate" class="btn btn-primary">
														</div>
													</form>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>

							<?php if (isset($_GET['syear']) && (!isset($_GET['mode']) || $_GET['mode'] !== 'classwise')) { ?>

								<div class="col-md-12">
									<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>
								</div>
								<div id="printArea" class="col-md-12 printBG">
									<div class="printArea" style="margin: 0 30px;">
										<style type="text/css">
											@page {
												size: auto;
												margin: 0;
											}

											#itemMainBox {
												max-width: 8.27in;
												display: inline-block;
												border: 1px dashed #333;
												overflow: hidden;
												margin: 15PX 0;
												font-family: sans-serif;
												width: 100%;
											}

											#itemMainBox .instLogo {
												width: 80px;
												position: absolute;
												left: 0;
												top: 10px;
											}

											#itemMainBox .instName {
												margin: 0 0 5px 0;
												color: #337ab7;
												font-weight: bold;
												font-size: 25px;
											}

											#itemMainBox .instAddrs {
												margin: 0 0 7px 0;
												color: #888888;
												font-size: 16px;
											}

											#itemMainBox .examName {
												margin: 0 auto 7px;
												text-align: center;
											}

											#itemMainBox .examName h3 {
												margin: 0;
												font-size: 20px;
											}

											#itemMainBox .itemInfo {
												text-align: center;
												margin-bottom: 20px;
												clear: both;
											}

											#itemMainBox .admitCard {
												margin: 15px 0 15px 0px;
												color: #f7740c;
												font-weight: bold;
												background: #f0f0f0;
												print-color-adjust: exact;
												-webkit-print-color-adjust: exact;
												padding: 5px;
												border-radius: 5px;
												font-size: 21px;
												border: 2px solid #f0f0f0;
											}

											#itemMainBox .admitNote {
												float: left;
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
										$year 		= $_GET['syear'];
										$class 		= $_GET['class'];
										$exam 		= $_GET['exam'];
										$section 	= isset($_GET['section']) ? $_GET['section'] : "";
										$roll 		= isset($_GET['roll']) ? $_GET['roll'] : "";

										$examSubjectsRaw = $wpdb->get_var($wpdb->prepare("SELECT examSubjects FROM ct_exam WHERE examid = %d", $exam));
										$selectedExamSubjectIds = array();
										if (! empty($examSubjectsRaw)) {
											$decodedExamSubjects = json_decode($examSubjectsRaw, true);
											if (is_array($decodedExamSubjects)) {
												foreach ($decodedExamSubjects as $subjectId) {
													$subjectId = intval($subjectId);
													if ($subjectId > 0) {
														$selectedExamSubjectIds[] = $subjectId;
													}
												}
												$selectedExamSubjectIds = array_values(array_unique($selectedExamSubjectIds));
											}
										}
										$subjectIdFilterClause = '';
										if (! empty($selectedExamSubjectIds)) {
											$subjectIdFilterClause = ' AND subjectid IN (' . implode(',', $selectedExamSubjectIds) . ')';
										}

										$groupsBy = "SELECT stdName,infoRoll,className,sectionName,examName,groupName,infoOptionals,info4thSub FROM ct_student
						LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid AND ct_student.stdCurrentClass = ct_studentinfo.infoClass
						LEFT JOIN ct_class ON ct_studentinfo.infoClass = ct_class.classid
						LEFT JOIN ct_exam ON ct_exam.examid = $exam
						LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId
						LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
						WHERE infoYear = '$year' AND infoClass = $class";

										$groupsBy .= ($roll != "") ? " AND infoRoll IN ($roll)" : '';

										$groupsBy .= ($section != "") ? " AND infoSection = $section" : '';

										$groupsBy .= ($selectedGroupFilter > 0) ? " AND infoGroup = $selectedGroupFilter" : '';

										if ($selectedGenderFilter !== '') {
											$groupsBy .= ' AND ct_student.stdGender = ' . intval($selectedGenderFilter);
										}

										$groupsBy .= " ORDER BY infoRoll";

										$groupsBy = $wpdb->get_results($groupsBy);

										$alloptSub = array();
										$alloptSubQuery = $wpdb->get_results("SELECT subjectid,subjectName FROM `ct_subject` WHERE subjectClass = $class AND (subOptinal = 1 OR sub4th = 1)" . $subjectIdFilterClause . " ORDER BY subid");
										foreach ($alloptSubQuery as $key => $value) {
											$alloptSub[$value->subjectid] = $value->subjectName;
										}

										$allmainSubQuery = $wpdb->get_results("SELECT subjectid,subjectName FROM `ct_subject` WHERE `subjectClass` = $class AND subOptinal = 0 AND sub4th = 0" . $subjectIdFilterClause . " ORDER BY subid");


										if ($groupsBy) {
											function rows($name = '')
											{
										?>
												<tr>
													<td style="border: 1px solid; padding: 10px 5px; height: 40px"></td>
													<td style="border: 1px solid; padding: 10px 5px; height: 40px; background: #f6f6f6"><?= $name ?></td>
													<td style="border: 1px solid; padding: 10px 5px; height: 40px"></td>
													<td style="border: 1px solid; padding: 10px 5px; height: 40px; background: #f6f6f6"></td>
													<td style="border: 1px solid; padding: 10px 5px; height: 40px"></td>
												</tr>
											<?php
											}
											foreach ($groupsBy as $value) {
												$opt = json_decode($value->infoOptionals);
												if ($value->info4thSub != '' && $value->info4thSub != 0)
													$opt[] = $value->info4thSub;
											?>
												<div id="itemMainBox">
													<div style="padding:0 15px 10px 15px; ">
														<div style="text-align: center; float: left; width: 100%;position: relative;padding: 20px 0 0;">
															<img class="instLogo" src="<?= $s3sRedux['instLogo']['url'] ?>">
															<h2 class="instName"><?= $s3sRedux['institute_name'] ?></h2>
															<h4 class="instAddrs"><?= $s3sRedux['institute_address'] ?></h4>
															<div class="examName">
																<h3><?= $value->examName . " " . $year ?></h3>
															</div>
															<h4 class="admitCard"><b>Exam Attendance Sheet</b></h4>
														</div>

														<div style="float: left; clear: both;width: 100%;margin-bottom: 10px;">

															<table style="font-size: 16px;width: 100%;">
																<tr>
																	<td>
																		<p style="font-size: 16px;"><b>Name:</b> <?= $value->stdName; ?></p>
																	</td>
																	<td>
																		<p style="font-size: 16px;"><b>Roll:</b> <?= $value->infoRoll; ?></p>
																	</td>
																	<td>
																		<p style="font-size: 16px;"><?= $value->className; ?></p>
																	</td>
																</tr>
																<tr>
																	<td>
																		<p style="font-size: 16px;"><b>Section:</b> <?= $value->sectionName; ?></p>
																	</td>
																	<td>
																		<p style="font-size: 16px;"><b>Group:</b> <?= $value->groupName; ?></p>
																	</td>
																	<td>
																		<p style="font-size: 16px;"><b>Year/Session:</b> <?= $_GET['syear']; ?></p>
																	</td>
																</tr>
																<tr>
																	<td colspan="3">
																		<b>Optional Subjects:</b>
																		<i style="font-size: 12px">
																			<?php
																			if (is_array($alloptSub) && is_array($opt) && sizeof($opt) > 0) {
																				foreach ($alloptSub as $key => $subi) {
																					echo in_array($key, $opt) ? $subi . ', ' : '';
																				}
																			}
																			?>
																		</i>
																	</td>
																</tr>
															</table>

															<table style="width: 100%; border-collapse: collapse;margin-top: 15px;">
																<tr>
																	<th style="border: 1px solid; padding: 10px 5px; width: 10%;">Date</th>
																	<th style="border: 1px solid; padding: 10px 5px; width: 35%; background: #f6f6f6">Subject Name</th>
																	<th style="border: 1px solid; padding: 10px 5px; width: 10%;">Sub Code</th>
																	<th style="border: 1px solid; padding: 10px 5px; width: 25%; background: #f6f6f6">Signature of Examinee</th>
																	<th style="border: 1px solid; padding: 10px 5px; width: 20%;">Signature of Invigilator</th>
																</tr>
																<?php

																foreach ($allmainSubQuery as $sub) {
																	rows($sub->subjectName);
																}

																if (is_array($opt) && sizeof($opt) > 0) {
																	foreach ($alloptSub as $key => $subi) {
																		if (in_array($key, $opt)) {
																			rows($subi);
																		}
																	}
																}

																$rows = isset($_GET['rows']) ? $_GET['rows'] : 0;

																for ($i = 0; $i < $rows; $i++) {
																	rows();
																}
																?>
															</table>

															<p style="margin-top: 20px">N.B - Student must be signed by ensuring date in eligible subject. Invigilator will ensure student's sign and date.</p>
															<small style="font-size: 11px;color: #888;">Generated by Bornomala, Developed by MS3 Technology BD, Urmi-43, Shibgonj, Sylhet. Email: bornomala.ems@gmail.com</small>
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

							<?php } ?>

							<?php if (isset($_GET['mode']) && $_GET['mode'] == 'classwise' && isset($_GET['syear'])) { ?>

								<div class="col-md-12">
									<button onclick="print('printAreaClasswise')" class="pull-right btn btn-primary">Print</button>
								</div>
								<div id="printAreaClasswise" class="col-md-12 printBG">
									<div class="printArea" style="margin: 0 30px;">
										<style type="text/css">
											@page {
												size: A4 landscape;
												margin: 10mm;
											}

											.classwise-container {
												max-width: 11in;
												border: 1px dashed #333;
												overflow: hidden;
												margin: 15px 0;
												font-family: sans-serif;
												width: 100%;
												page-break-after: always;
											}

											.classwise-header {
												text-align: center;
												position: relative;
												padding: 20px 0 0;
											}

											.classwise-header .instLogo {
												width: 80px;
												position: absolute;
												left: 0;
												top: 10px;
											}

											.classwise-header .instName {
												margin: 0 0 5px;
												color: #337ab7;
												font-weight: bold;
												font-size: 25px;
											}

											.classwise-header .instAddrs {
												margin: 0 0 7px;
												color: #888888;
												font-size: 16px;
											}

											.classwise-header .examName h3 {
												margin: 0;
												font-size: 20px;
											}

											.classwise-header .sheet-title {
												margin: 15px 0;
												color: #f7740c;
												font-weight: bold;
												background: #f0f0f0;
												print-color-adjust: exact;
												-webkit-print-color-adjust: exact;
												padding: 5px 12px;
												border-radius: 5px;
												font-size: 20px;
												border: 2px solid #f0f0f0;
												display: inline-block;
											}

											.classwise-meta {
												font-size: 14px;
												width: 100%;
												margin: 0 0 12px;
												border-collapse: collapse;
											}

											.classwise-meta td {
												padding: 4px 6px;
											}

											.classwise-table {
												width: 100%;
												border-collapse: collapse;
												font-size: 12px;
											}

											.classwise-table th,
											.classwise-table td {
												border: 1px solid #333;
												padding: 6px 4px;
											}

											.classwise-table th {
												background: #f6f6f6;
											}

											.classwise-table th.subject-col {
												background: #e8f4ff;
												white-space: nowrap;
											}

											.signature-strip {
												margin-top: 30px;
												display: flex;
												justify-content: space-between;
												gap: 20px;
											}

											.signature-strip p {
												margin: 0;
												padding-top: 50px;
												border-top: 2px solid #333;
												font-weight: bold;
												text-align: center;
											}

											.footer-note {
												font-size: 10px;
												color: #888;
												margin-top: 10px;
												display: block;
												text-align: center;
											}

											@media print {
												.classwise-container:last-of-type {
													page-break-after: auto;
												}
											}
										</style>

										<?php
										$year      = $_GET['syear'];
										$class     = $_GET['class'];
										$exam      = $_GET['exam'];
										$section   = isset($_GET['section']) ? $_GET['section'] : "";
										$extrarows = isset($_GET['extrarows']) ? max(0, intval($_GET['extrarows'])) : 5;

										$classwiseExamSubjectsRaw = $wpdb->get_var($wpdb->prepare("SELECT examSubjects FROM ct_exam WHERE examid = %d", $exam));
										$classwiseExamSubjectIds  = array();
										if (! empty($classwiseExamSubjectsRaw)) {
											$decodedClasswiseSubjects = json_decode($classwiseExamSubjectsRaw, true);
											if (is_array($decodedClasswiseSubjects)) {
												foreach ($decodedClasswiseSubjects as $subjectId) {
													$subjectId = intval($subjectId);
													if ($subjectId > 0) {
														$classwiseExamSubjectIds[] = $subjectId;
													}
												}
												$classwiseExamSubjectIds = array_values(array_unique($classwiseExamSubjectIds));
											}
										}
										$classwiseSubjectFilterClause = '';
										if (! empty($classwiseExamSubjectIds)) {
											$classwiseSubjectFilterClause = ' AND subjectid IN (' . implode(',', $classwiseExamSubjectIds) . ')';
										}

										$classwiseQuery = "SELECT stdName, infoRoll, className, sectionName, examName FROM ct_student
						        LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid AND ct_student.stdCurrentClass = ct_studentinfo.infoClass
						        LEFT JOIN ct_class ON ct_studentinfo.infoClass = ct_class.classid
						        LEFT JOIN ct_exam ON ct_exam.examid = $exam
							        LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
							        LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId
						        WHERE infoYear = '$year' AND infoClass = $class";

										if ($section !== "") {
											$classwiseQuery .= " AND infoSection = $section";
										}

										$classwiseQuery .= ($selectedGroupFilter > 0) ? " AND infoGroup = $selectedGroupFilter" : '';

										if ($selectedGenderFilter !== '') {
											$classwiseQuery .= ' AND ct_student.stdGender = ' . intval($selectedGenderFilter);
										}
										$classwiseQuery .= " GROUP BY ct_student.studentid ORDER BY CAST(infoRoll AS UNSIGNED), infoRoll";

										$studentsList   = $wpdb->get_results($classwiseQuery);
										$classSubjects  = $wpdb->get_results("SELECT subjectid, subjectName, shortName FROM ct_subject WHERE subjectClass = $class" . $classwiseSubjectFilterClause . " ORDER BY subid");


										if ($studentsList && !empty($studentsList)) {
											$firstStudent    = $studentsList[0];
											$subjectHeaders  = array_map(function ($sub) {
												return [
													'title' => $sub->subjectName,
													'label' => !empty($sub->shortName) ? $sub->shortName : $sub->subjectName,
												];
											}, $classSubjects);

											$totalStudents = count($studentsList);
											$firstPageCount = 15;
											$otherPageCount = 20;
											$currentIndex = 0;

											// Helper to render header and meta
											function render_classwise_header($firstStudent, $year, $groupFilterLabel, $genderFilterLabel, $subjectHeaders, $s3sRedux)
											{
										?>
												<div class="classwise-header">
													<img class="instLogo" src="<?= esc_url(home_url($s3sRedux['instLogo']['url'])) ?>" alt="<?= esc_attr($s3sRedux['institute_name']) ?>">
													<h2 class="instName"><?= esc_html($s3sRedux['institute_name']); ?></h2>
													<h4 class="instAddrs"><?= esc_html($s3sRedux['institute_address']); ?></h4>
													<div class="examName">
														<h3><?= esc_html($firstStudent->examName . ' ' . $year); ?></h3>
													</div>
													<span class="sheet-title">Class-wise Exam Attendance Sheet</span>
												</div>
												<table class="classwise-meta">
													<tr>
														<td style="min-height: 40px;"><strong>Class:</strong> <?= esc_html($firstStudent->className); ?></td>
														<td style="min-height: 40px;"><strong>Section:</strong> <?= esc_html($firstStudent->sectionName); ?></td>
														<td style="min-height: 40px;"><strong>Year/Session:</strong> <?= esc_html($year); ?></td>
													</tr>
													<tr>
														<td style="min-height: 40px;"><strong>Group:</strong> <?= esc_html($groupFilterLabel); ?></td>
														<td style="min-height: 40px;"><strong>Gender:</strong> <?= esc_html($genderFilterLabel); ?></td>
														<td style="min-height: 40px;"></td>
													</tr>
												</table>
												<table class="classwise-table">
													<thead>
														<tr>
															<th style="min-height: 40px;">Roll No</th>
															<th style="min-height: 40px;">Student Name</th>
															<?php foreach ($subjectHeaders as $subject) { ?>
																<th class="subject-col" style="min-height: 40px;" title="<?= esc_attr($subject['title']) ?>"><?= esc_html($subject['label']) ?></th>
															<?php } ?>
														</tr>
													</thead>
													<tbody>
													<?php
												}

												// First page: header + 15 records
												echo '<div class="classwise-container">';
												render_classwise_header($firstStudent, $year, $groupFilterLabel, $genderFilterLabel, $subjectHeaders, $s3sRedux);
												for ($i = 0; $i < $firstPageCount && $currentIndex < $totalStudents; $i++, $currentIndex++) {
													$student = $studentsList[$currentIndex];
													?>
														<tr>
															<td style="text-align:center; min-height: 40px;"><?= esc_html($student->infoRoll); ?></td>
															<td style="min-height: 40px;"><?= esc_html($student->stdName); ?></td>
															<?php foreach ($subjectHeaders as $subject) { ?>
																<td style="min-height: 40px;"></td>
															<?php } ?>
														</tr>
													<?php
												}
												// Extra rows for first page
												for ($i = 0; $i < $extrarows; $i++) {
													?>
														<tr>
															<td style="min-height: 40px;">&nbsp;</td>
															<td style="min-height: 40px;">&nbsp;</td>
															<?php foreach ($subjectHeaders as $subject) { ?>
																<td style="min-height: 40px;">&nbsp;</td>
															<?php } ?>
														</tr>
														<?php
													}
													echo '</tbody></table>';
													echo '<div class="signature-strip"><p>Prepared By</p><p>Chief Invigilator Signature</p><p>Principal Signature</p></div>';
													echo '</div>';

													// Subsequent pages: 20 records per page
													while ($currentIndex < $totalStudents) {
														echo '<div class="classwise-container">';
														render_classwise_header($firstStudent, $year, $groupFilterLabel, $genderFilterLabel, $subjectHeaders, $s3sRedux);
														for ($i = 0; $i < $otherPageCount && $currentIndex < $totalStudents; $i++, $currentIndex++) {
															$student = $studentsList[$currentIndex];
														?>
															<tr>
																<td style="text-align:center; min-height: 40px;"><?= esc_html($student->infoRoll); ?></td>
																<td style="min-height: 40px;"><?= esc_html($student->stdName); ?></td>
																<?php foreach ($subjectHeaders as $subject) { ?>
																	<td style="min-height: 40px;"></td>
																<?php } ?>
															</tr>
												<?php
														}
														echo '</tbody></table>';
														echo '<div class="signature-strip"><p>Prepared By</p><p>Chief Invigilator Signature</p><p>Principal Signature</p></div>';
														echo '</div>';
													}
												} else {
													echo "<h3 class='text-center'>No Students Found</h3>";
												}

												?>
									</div>
								</div>
							<?php } ?>

						</div>
					</div>

					<?php if (! is_admin()) { ?>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php get_footer();
					} ?>














<script type="text/javascript">
	(function($) {
		// Individual attendance form handler
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

		// Class-wise attendance form handler
		$('#classwiseClass').change(function() {
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
				$("#classwiseExam").html(msg);
				$("#classwiseExam").prop('disabled', false);
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
				$("#classwiseYear").html(msg);
				$("#classwiseYear").prop('disabled', false);
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
				$("#classwiseSection").html(msg);
				$("#classwiseSection").prop('disabled', false);
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