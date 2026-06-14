<?php
/*
** Template Name: Admin Testimonail
*/
global $wpdb;
global $s3sRedux;
// fetch instLogo, principalSignature, testimonial reference and pads from db
$query = "SELECT option_name, option_value FROM sm_options WHERE option_name IN ('instLogo', 'principalSign', 'testimonial_ref', 'institute_name', 'institute_address', 'estd_year', 'institute_eiin', 'inst_head_name', 'inst_head_title', 'testimonial_prepared_by', 'testimonial_pad', 'board_name_1', 'board_name_2')";
$results = $wpdb->get_results($query);
foreach ($results as $row) {
	$optionValue = isset($row->option_value) ? maybe_unserialize($row->option_value) : '';

	if (in_array($row->option_name, ['instLogo', 'principalSign', 'testimonial_pad'], true)) {
		$imageUrl = '';
		if (is_array($optionValue) && isset($optionValue['url']) && !empty($optionValue['url'])) {
			$imageUrl = $optionValue['url'];
		} elseif (is_string($optionValue) && !empty($optionValue)) {
			$imageUrl = $optionValue;
		}

		// Convert relative path to full URL if needed
		if (!empty($imageUrl) && strpos($imageUrl, 'http') !== 0) {
			$imageUrl = home_url('/' . ltrim($imageUrl, '/'));
		}

		$s3sRedux[$row->option_name] = $imageUrl;
	} elseif ($row->option_name === 'testimonial_ref') {
		$s3sRedux[$row->option_name] = max(1, (int) $optionValue);
	} else {
		$s3sRedux[$row->option_name] = $optionValue;
	}
}

if (!isset($s3sRedux['testimonial_ref'])) {
	$s3sRedux['testimonial_ref'] = 1;
}

$allowedTestimonialTypes = ['regular', 'board', 'technical'];
$requestedTestimonialType = isset($_GET['testimonial_type']) ? sanitize_text_field(wp_unslash($_GET['testimonial_type'])) : '';
if (!in_array($requestedTestimonialType, $allowedTestimonialTypes, true)) {
	$requestedTestimonialType = 'regular';
}

$currentTestimonialType = $requestedTestimonialType;
$showBoardFields = in_array($currentTestimonialType, ['board', 'technical'], true);
$showBoardOnlyFields = ($currentTestimonialType === 'board');

$testimonial_border_type = $wpdb->get_var("SELECT option_value FROM sm_options WHERE option_name = 'testimonial_type'");

require_once __DIR__ . '/functions/html-snapshot.php';
require_once __DIR__ . '/functions/html-utils.php';
?>


<?php if (! is_admin()) {
	get_header(); ?>
	<div class="b-layer-main">

		<div class="">
			<div class="container">
				<div class="row">
					<div class="col-md-12">
					<?php } ?>

					<div class="container-fluid maxAdminpages" style="padding-left: 0">
						<p id="theSiteURL" class="hidden"><?= get_template_directory_uri() ?></p>

						<div class="panel panel-info">
							<div class="panel-heading">
								<ul class="nav nav-tabs" id="testimonialTypeTabs" role="tablist" aria-label="Testimonial types">
									<li class="nav-item <?= $currentTestimonialType === 'regular' ? 'active' : '' ?>">
										<a class="nav-link <?= $currentTestimonialType === 'regular' ? 'active' : '' ?>" href="?page=testimonail&testimonial_type=regular" role="tab" aria-selected="<?= $currentTestimonialType === 'regular' ? 'true' : 'false' ?>">Regular Testimonial</a>
									</li>
									<li class="nav-item <?= $currentTestimonialType === 'board' ? 'active' : '' ?>">
										<a class="nav-link <?= $currentTestimonialType === 'board' ? 'active' : '' ?>" href="?page=testimonail&testimonial_type=board" role="tab" aria-selected="<?= $currentTestimonialType === 'board' ? 'true' : 'false' ?>">Board Testimonial</a>
									</li>
									<li class="nav-item <?= $currentTestimonialType === 'technical' ? 'active' : '' ?>">
										<a class="nav-link <?= $currentTestimonialType === 'technical' ? 'active' : '' ?>" href="?page=testimonail&testimonial_type=technical" role="tab" aria-selected="<?= $currentTestimonialType === 'technical' ? 'true' : 'false' ?>">Technical Board Testimonial</a>
									</li>
								</ul>
							</div>
							<div class="panel-body">
								<style>
									#testimonial-form.form-inline {
										display: flex;
										flex-wrap: wrap;
										gap: 12px;
										align-items: flex-end;
									}

									#testimonial-form .form-group {
										display: flex;
										flex-direction: column;
										margin: 0;
										gap: 4px;
										min-width: 160px;
									}

									#testimonial-form .form-group label {
										font-weight: 600;
										font-size: 13px;
										letter-spacing: 0.3px;
										text-transform: uppercase;
										color: #0c3c60;
										margin-bottom: 0;
									}

									#testimonial-form .form-control {
										height: 36px;
										padding: 4px 10px;
										font-size: 14px;
										border-radius: 6px;
										border: 1px solid #c5d5e4;
										box-shadow: inset 0 1px 2px rgba(12, 60, 96, 0.05);
									}

									#testimonial-form .form-group.board-fields,
									#testimonial-form .form-group.board-only-fields {
										min-width: 140px;
									}

									#testimonial-form .btn.btn-primary {
										height: 36px;
										padding: 0 18px;
										border-radius: 6px;
										margin-top: 0;
										letter-spacing: 0.4px;
									}

									@media (max-width: 768px) {
										#testimonial-form .form-group {
											min-width: calc(50% - 12px);
										}

										#testimonial-form .btn.btn-primary {
											width: 100%;
										}
									}
								</style>
								<div class="tab-content" id="myTabContent">
									<div class="tab-pane fade active in" id="home" role="tabpanel" aria-labelledby="testimonialTypeTabs">
										<form id="testimonial-form" class="form-inline" action="" method="GET">
											<input type="hidden" name="page" value="testimonail">
											<input type="hidden" name="testimonial_type" id="testimonial_type_input" value="<?= esc_attr($currentTestimonialType) ?>">

											<div class="form-group">
												<label>Class</label>
												<select class="resultClass form-control" name="class" required>
													<?php
													$selectedClass = isset($_GET['class']) ? (int) $_GET['class'] : 0;
													$classQuery = $wpdb->get_results("SELECT classid,className FROM ct_class WHERE classid IN (SELECT examClass FROM ct_exam GROUP BY examClass ORDER BY className ASC)");
													echo "<option value=''>Select Class</option>";

													foreach ($classQuery as $class) {
														$sel = ((int) $class->classid === $selectedClass) ? ' selected' : '';
														echo "<option value='" . $class->classid . "'" . $sel . ">" . $class->className . "</option>";
													}
													?>
												</select>
											</div>

											<div class="form-group ">
												<label>Section</label>
												<select class="resultSection form-control" name="section" required <?= $selectedClass ? '' : 'disabled' ?>>
													<?php
													if ($selectedClass) {
														$sections = $wpdb->get_results($wpdb->prepare("SELECT sectionid,sectionName FROM ct_section WHERE forClass = %d", $selectedClass));
														$selectedSection = isset($_GET['section']) ? (int) $_GET['section'] : 0;
														if (empty($sections)) {
															echo "<option value=''>No sections available</option>";
														} else {
															echo "<option value=''>Section</option>";
														}
														foreach ($sections as $section) {
															$sel = ((int) $section->sectionid === $selectedSection) ? ' selected' : '';
															echo "<option value='" . (int) $section->sectionid . "'" . $sel . ">" . esc_html($section->sectionName) . "</option>";
														}
													} else {
														echo '<option disabled selected>Select Class</option>';
													}
													?>
												</select>
											</div>

											<div class="form-group">
												<label>Year</label>
												<select class="resultYear form-control" name="syear" required <?= $selectedClass ? '' : 'disabled' ?>>
													<?php
													if ($selectedClass) {
														$years = $wpdb->get_results($wpdb->prepare("SELECT infoYear FROM ct_studentinfo WHERE infoClass = %d GROUP BY infoYear ORDER BY infoYear ASC", $selectedClass));
														$selectedYear = isset($_GET['syear']) ? $_GET['syear'] : '';
														if (empty($years)) {
															echo "<option value=''>No Student In this class</option>";
														} else {
															echo "<option value=''>Year</option>";
														}
														foreach ($years as $year) {
															$sel = ($selectedYear === $year->infoYear) ? ' selected' : '';
															echo "<option value='" . esc_attr($year->infoYear) . "'" . $sel . ">" . esc_html($year->infoYear) . "</option>";
														}
													} else {
														echo '<option disabled selected>Select Class</option>';
													}
													?>
												</select>
											</div>

											<div class="form-group ">
												<label>Exam</label>
												<?php if ($currentTestimonialType === 'regular'): ?>
													<select class="resultExam form-control" name="exam" required <?= $selectedClass ? '' : 'disabled' ?>>
														<?php
														if ($selectedClass) {
															$exams = $wpdb->get_results($wpdb->prepare("SELECT examid,examName FROM ct_exam WHERE examClass = %d", $selectedClass));
															$selectedExam = isset($_GET['exam']) ? (int) $_GET['exam'] : 0;
															if (empty($exams)) {
																echo "<option value=''>No Exam for this Class</option>";
															} else {
																echo "<option value=''>Select An Exam</option>";
															}
															foreach ($exams as $exam) {
																$sel = ((int) $exam->examid === $selectedExam) ? ' selected' : '';
																echo "<option value='" . (int) $exam->examid . "'" . $sel . ">" . esc_html($exam->examName) . "</option>";
															}
														} else {
															echo '<option disabled selected>Select Class</option>';
														}
														?>
													</select>
												<?php else: ?>
													<input type="text" class="form-control" name="exam" placeholder="Enter Exam Name" required value="<?= isset($_GET['exam']) ? esc_attr(wp_unslash($_GET['exam'])) : '' ?>">
												<?php endif; ?>
											</div>

											<div class="form-group board-fields" style="<?= $showBoardFields ? '' : 'display:none;' ?>">
												<input class="form-control" type="text" name="rollstart" placeholder="Roll Start From" style="width: 115px" value="<?= isset($_GET['rollstart']) ? esc_attr($_GET['rollstart']) : '' ?>">
											</div>

											<div class="form-group board-fields" style="<?= $showBoardFields ? '' : 'display:none;' ?>">
												<input class="form-control" type="text" name="regstart" placeholder="Reg Start From" style="width: 115px" value="<?= isset($_GET['regstart']) ? esc_attr($_GET['regstart']) : '' ?>">
											</div>

											<div class="form-group board-fields" style="<?= $showBoardFields ? '' : 'display:none;' ?>">
												<input type="text" name="resSession" class="form-control" placeholder="Session" style="width: 100px" value="<?= isset($_GET['resSession']) ? esc_attr($_GET['resSession']) : '' ?>">
											</div>

											<div class="form-group board-only-fields" style="<?= $showBoardOnlyFields ? '' : 'display:none;' ?>">
												<input type="text" name="resPassingYear" class="form-control" placeholder="Passing Year" style="width: 110px" value="<?= isset($_GET['resPassingYear']) ? esc_attr($_GET['resPassingYear']) : '' ?>">
											</div>

											<div class="form-group" id="idRoll">
												<input class="form-control" type="text" name="roll" placeholder="Roll" style="width: 110px" value="<?= isset($_GET['roll']) ? esc_attr($_GET['roll']) : '' ?>">
											</div>
											<div class="form-group">
												<label>Reference (Optional)</label>
												<input class="form-control" type="text" name="ref" placeholder="Reference" value="<?= isset($_GET['ref']) ? esc_attr(wp_unslash($_GET['ref'])) : '' ?>">
											</div>
                                            <div class="form-group">
                                                <label>Serial No Start At</label>
                                                <input class="form-control" type="number" name="serial_start" placeholder="Auto" value="<?= isset($_GET['serial_start']) ? esc_attr($_GET['serial_start']) : '' ?>">
                                            </div>
                                            <div class="form-group">
                                                <label>Format</label>
                                                <select class="form-control" name="pad_type">
                                                    <?php
                                                    $hasPadImage = !empty($s3sRedux['testimonial_pad']);
                                                    $padDefault = !isset($_GET['pad_type']) && $hasPadImage;
                                                    ?>
                                                    <option value="default" <?= (isset($_GET['pad_type']) && $_GET['pad_type'] == 'default') || (!isset($_GET['pad_type']) && !$hasPadImage) ? 'selected' : '' ?>>Default</option>
                                                    <option value="pad" <?= (isset($_GET['pad_type']) && $_GET['pad_type'] == 'pad') || $padDefault ? 'selected' : '' ?>>Pad</option>
                                                </select>
                                            </div>
											<div class="form-group">
												<input type="submit" name="creatId" value="Create" class="btn btn-primary">
											</div>
										</form>
									</div>
								</div>
							</div>
						</div>


						<div class="row">
							<div class="col-md-12">

								<?php if (isset($_GET['syear'])) {
									$staticTestimonialUrl = '';
									$staticWriteError = '';
								?>
									<div class="col-md-12">
										<button onclick="printTestimonial('printArea')" class="pull-right btn btn-primary">Print</button>
									</div>
									<div id="printArea" class="col-md-12 printBG">
										<div class="printArea">

											<?php
											$year 		= @$_GET['syear'];
											$class 		= @$_GET['class'];
											$section 	= @$_GET['section'];
											$roll 		= @$_GET['roll'];
											$exam 		= @$_GET['exam'];
											$manualRef  = isset($_GET['ref']) ? trim(wp_unslash($_GET['ref'])) : '';
											$useManualRef = $manualRef !== '';
											$testimonial_type = $currentTestimonialType;
											$session = @$_GET['resSession'];
											$passingyear = @$_GET['resPassingYear'];
											$institute_code = $wpdb->get_var("SELECT option_value FROM sm_options WHERE option_name = 'institute_code'");
											$testimonial_prepared_by = isset($s3sRedux['testimonial_prepared_by']) ? $s3sRedux['testimonial_prepared_by'] : $wpdb->get_var("SELECT option_value FROM sm_options WHERE option_name = 'testimonial_prepared_by'");
											
											// Handle Pad Selection
                                            $pad_type = $_GET['pad_type'] ?? 'default';
											$testimonial_bg_url = '';
											if ($pad_type === 'pad' && !empty($s3sRedux['testimonial_pad'])) {
												$testimonial_bg_url = $s3sRedux['testimonial_pad'];
											}
											$instHeadName = isset($s3sRedux['inst_head_name']) ? $s3sRedux['inst_head_name'] : $wpdb->get_var("SELECT option_value FROM sm_options WHERE option_name = 'inst_head_name'");

											// Determine query based on testimonial type
											if ($testimonial_type == 'board') {
												// Board testimonial query - no exam join needed
												$query = "SELECT stdName,stdGender,infoRoll,className,sectionName,stdImg,groupName,infoYear,stdPhone,stdFather,stdMother,stdBrith,sscRoll,sscReg,stdGPA,stdIntellectual 
														FROM ct_student
															LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid
															LEFT JOIN ct_class ON ct_studentinfo.infoClass = ct_class.classid
															LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId
															LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
															WHERE infoYear = '$year'";
											} else {
												// Regular and technical testimonial query - includes exam join
												$query = "SELECT stdName,stdGender,infoRoll,className,sectionName,stdImg,groupName,infoYear,stdPhone,stdFather,stdMother,examName,spPosition,spPoint,stdBrith 
														FROM ct_student
															LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid
															LEFT JOIN ct_class ON ct_studentinfo.infoClass = ct_class.classid
															LEFT JOIN ct_exam ON $exam = ct_exam.examid
															LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId
															LEFT JOIN ct_studentPoint ON ct_studentinfo.infoStdid = ct_studentPoint.spStdID AND ct_studentPoint.spYear = '$year' AND ct_studentPoint.spClass = $class AND ct_studentPoint.spExam = $exam
															LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
															WHERE infoYear = '$year'";
											}

											// Add additional filters
											if ($_GET['roll'] != '') {
												$query .= " AND infoRoll = $roll";
											}
											if ($_GET['section'] != 0) {
												$query .= " AND infoSection = $section";
											}
											$query .= " ORDER BY infoRoll ASC";

											// Execute query
											$groupsBy = $wpdb->get_results($query);

											// For board type testimonials, set roll/reg starting values
											$rollStart = ($testimonial_type == 'board') ? (@$_GET['rollstart'] != '' ? $_GET['rollstart'] : "...") : null;
											$regStart = ($testimonial_type == 'board') ? (@$_GET['regstart'] != '' ? $_GET['regstart'] : "...") : null;

											// Display results
											if ($groupsBy) {
												$refSerialStart = isset($s3sRedux['testimonial_ref']) ? (int) $s3sRedux['testimonial_ref'] : 1;
												if (isset($_GET['serial_start']) && $_GET['serial_start'] !== '') {
													$refSerialStart = max(1, (int) $_GET['serial_start']);
												}
												if ($refSerialStart < 1) {
													$refSerialStart = 1;
												}
												$refSerialCounter = $refSerialStart;
												$generatedTestimonials = 0;
												$staticFilePath = '';
												$staticTestimonialUrl = '';
												$staticWriteError = '';

												ob_start();
										?>
												<style type="text/css">
													@page {
														size: A4 portrait;
														margin: 0;
													}

													@media print {
														body {
															-webkit-print-color-adjust: exact !important;
															print-color-adjust: exact !important;
														}
														.certificate {
															-webkit-print-color-adjust: exact !important;
															print-color-adjust: exact !important;
														}
													}

													body {
														background: #e6e6e6;
														font-family: "Times New Roman", serif;
														margin: 0;
														padding: 0;
													}

													.certificate {
														width: 21cm;
														height: 29.7cm;
														margin: 0 auto;
														background-color: #ffffff;
														box-sizing: border-box;
														position: relative;
														page-break-after: always;
														-webkit-print-color-adjust: exact;
														print-color-adjust: exact;
													}

													.cert-content {
														padding: 50px 60px;
													}

													.certificate .header {
														text-align: center;
														margin-bottom: 20px;
													}

													.certificate .header h1 {
														font-size: 20pt;
														color: #000;
														margin: 0 0 5px 0;
														font-weight: bold;
														text-transform: uppercase;
														letter-spacing: 1px;
													}

													.certificate .header .sub {
														font-size: 13pt;
														color: #000;
														font-weight: normal;
														margin: 5px 0;
														line-height: 1.4;
													}

													.certificate .content {
														font-size: 17px;
														line-height: 1.8;
														color: #000;
														margin-top: 20px;
														margin-bottom: 20px;
													}

													.certificate .content p {
														margin-bottom: 12px;
														text-align: justify;
													}

													.certificate .ref-date {
														display: flex;
														justify-content: space-between;
														margin-bottom: 15px;
													}

													.certificate .doc-footer {
														margin-top: 140px;
														color: #000;
														display: flex;
														justify-content: space-between;
														align-items: flex-end;
													}

													.certificate .doc-footer .left {
														width: 40%;
														font-size: 15px;
													}

													.certificate .doc-footer .right-sign {
														text-align: center;
														width: 40%;
														font-size: 15px;
													}

													.certificate .doc-footer .right-sign img {
														max-height: 60px;
														max-width: 150px;
														margin: 0 auto 5px;
														display: block;
													}

													.certificate .doc-footer .right-sign p {
														margin: 2px 0;
														line-height: 1.3;
													}

													.certificate .bottom {
														margin-top: 20px;
														text-align: left;
														font-size: 15px;
													}

													.line {
														border-bottom: 1px dotted #000;
														padding-left: 4px;
														padding-right: 4px;
														text-align: center;
													}

                                                    .title {
                                                        font-size: 22pt;
                                                        font-weight: bold;
                                                        text-decoration: underline;
                                                        margin: 20px 0;
                                                        text-align: center;
                                                    }

													b u {
														text-decoration: none;
														padding: 0 3px;
													}
												</style>
												<?php

												foreach ($groupsBy as $value) {
													if ($useManualRef) {
														$currentRefValue = $manualRef;
													} else {
														$currentRefValue = $refSerialCounter;
														$refSerialCounter++;
													}
													$generatedTestimonials++;
												?>
													<div class="certificate">
														<?php if ($pad_type == 'pad' && !empty($testimonial_bg_url)) : ?>
														<div style="width:100%;height:100%;">
															<img style="width:100%;height:100%;" src="<?= esc_url($testimonial_bg_url) ?>" alt="Background" />
														</div>
														<?php endif; ?>
														<div class="cert-content" style="<?= ($pad_type == 'pad' && !empty($testimonial_bg_url)) ? 'position:absolute;inset:0;z-index:20;margin-top:200px;' : '' ?>">
															<?php if ($pad_type != 'pad') : ?>
															<div class="header" style="display: flex; align-items: center; justify-content: center; gap: 20px; margin-bottom: 30px;">															
																<div style="width: 20%; text-align: left; vertical-align: top;">
																	<?php if ($s3sRedux['instLogo']): ?>
																		<img width="100" style="margin-top: 0;" src="<?= $s3sRedux['instLogo'] ?>" alt="Logo">
																	<?php endif; ?>
																</div>
																<div>
																	<h1><?= esc_html($s3sRedux['institute_name']) ?></h1>
																	<p class="sub">
																		<?= esc_html($s3sRedux['institute_address']) ?><br>
																		Estd. <?= esc_html($s3sRedux['estd_year']) ?> | EIIN: <?= esc_html($s3sRedux['institute_eiin']) ?>
																	</p>
																</div>															
															</div>
															<?php endif; ?>

															<div class="content">
																<div class="title">TESTIMONIAL</div>

																<div class="ref-date">
																	<p><strong>Serial No:</strong> <span class="line"><?= esc_html($currentRefValue); ?></span></p>
																	<p><strong>Date:</strong> <span class="line"><?= date('d-m-Y') ?></span></p>
																</div>

																<?php if ($testimonial_type == 'board'): ?>
																	<!-- Board Testimonial -->
																	<p>
																	This is to certify that <span class="line"><?= esc_html($value->stdName) ?></span>, 
																	<?= ($value->stdGender == 0) ? 'daughter' : 'son' ?> of 
																	<span class="line"><?= esc_html($value->stdFather) ?></span> and 
																	<span class="line"><?= esc_html($value->stdMother) ?></span>, 
																	has successfully passed the <span class="line"><?= esc_html($exam) ?></span> Examination 
																	held in <span class="line"><?= esc_html($passingyear) ?></span> from this institution 
																	under the <?= esc_html($s3sRedux['board_name_1']) ?>
																	<?= isset($value->groupName) ? ", Group <span class='line'>" . esc_html($value->groupName) . "</span>" : '' ?>, 
																	bearing Roll No: <span class="line"><?= esc_html($value->sscRoll) ?></span>, 
																	Registration No: <span class="line"><?= esc_html($value->sscReg) ?></span>, 
																	Session: <span class="line"><?= esc_html($session) ?></span>, 
																	securing a G.P.A of <span class="line"><?= esc_html($value->stdGPA) ?></span> 
																	on a scale of <span class="line">5.00</span>.
																	</p>
																	<p>
																	According to the admission register, <?= ($value->stdGender == 0) ? 'her' : 'his' ?> 
																	date of birth is recorded as <span class="line"><?= formatBirthDate($value->stdBrith) ?></span>.
																	</p>
																<?php elseif ($testimonial_type == 'technical'): ?>
																	<!-- Technical Board Testimonial -->
																	<p>
																	This is to certify that <span class="line"><?= esc_html($value->stdName) ?></span>, 
																	<?= ($value->stdGender == 0) ? 'daughter' : 'son' ?> of 
																	<span class="line"><?= esc_html($value->stdFather) ?></span> and 
																	<span class="line"><?= esc_html($value->stdMother) ?></span>, 
																	is a student of this institution in Class <span class="line"><?= esc_html($value->className) ?></span>
																	<?= isset($value->sectionName) ? ", Section <span class='line'>" . esc_html($value->sectionName) . "</span>" : '' ?>
																	<?= isset($value->groupName) ? ", Group <span class='line'>" . esc_html($value->groupName) . "</span>" : '' ?>, 
																	Roll No. <span class="line"><?= esc_html($value->infoRoll) ?></span>. 
																	<?= ($value->stdGender == 0) ? 'She' : 'He' ?> appeared at 
																	<span class="line"><?= esc_html($value->examName) ?></span> held in <span class="line"><?= esc_html($year) ?></span> 
																	under the <?= esc_html($s3sRedux['board_name_2']) ?> and achieved merit position 
																	<?= isset($value->spPosition) ? "<span class='line'>" . esc_html($value->spPosition) . "</span>" : '<span class="line">............</span>' ?> 
																	with GPA <span class="line"><?= isset($value->spPoint) ? esc_html($value->spPoint) : '............' ?></span>.
																	</p>
																	<p>
																	According to the admission register, <?= ($value->stdGender == 0) ? 'her' : 'his' ?> 
																	date of birth is recorded as <span class="line"><?= formatBirthDate($value->stdBrith) ?></span>.
																	</p>
																<?php else: ?>
																	<!-- Regular Testimonial -->
																	<p>
																	This is to certify that <span class="line"><?= esc_html($value->stdName) ?></span>, 
																	<?= ($value->stdGender == 0) ? 'daughter' : 'son' ?> of 
																	<span class="line"><?= esc_html($value->stdFather) ?></span> and 
																	<span class="line"><?= esc_html($value->stdMother) ?></span>, 
																	is a student of this institution in Class <span class="line"><?= esc_html($value->className) ?></span>
																	<?= isset($value->sectionName) ? ", Section <span class='line'>" . esc_html($value->sectionName) . "</span>" : '' ?>
																	<?= isset($value->groupName) ? ", Group <span class='line'>" . esc_html($value->groupName) . "</span>" : '' ?>, 
																	Roll No. <span class="line"><?= esc_html($value->infoRoll) ?></span>. 
																	<?= ($value->stdGender == 0) ? 'She' : 'He' ?> appeared at 
																	<span class="line"><?= esc_html($value->examName) ?></span> held in <span class="line"><?= esc_html($year) ?></span> 
																	and secured merit position 
																	<?= isset($value->spPosition) ? "<span class='line'>" . esc_html($value->spPosition) . "</span>" : '<span class="line">............</span>' ?> 
																	with GPA <span class="line"><?= isset($value->spPoint) ? esc_html($value->spPoint) : '............' ?></span>.
																	</p>
																	<p>
																	According to the admission register, <?= ($value->stdGender == 0) ? 'her' : 'his' ?> 
																	date of birth is recorded as <span class="line"><?= formatBirthDate($value->stdBrith) ?></span>.
																	</p>
																<?php endif; ?>

																<p>
																	<?= ($value->stdGender == 0) ? 'She' : 'He' ?> has never taken part in any activity 
																	detrimental to the state or contrary to the discipline of this institution. 
																	<?= ($value->stdGender == 0) ? 'She' : 'He' ?> bears good moral character.
																</p>

																<p>
																	We wish <?= ($value->stdGender == 0) ? 'her' : 'him' ?> every success in all future endeavors.
																</p>
															</div>

															<div class="doc-footer">
																<div class="left">
																	<!-- <p><strong>Prepared by:</strong> <span class="line"><?= esc_html($testimonial_prepared_by) ?></span></p> -->
																	<p>Date of Issue: <span class="line"><?= date('d-m-Y') ?></span></p>
																</div>

																<div class="right-sign">
																	<?php if (!empty($s3sRedux['principalSign'])): ?>
																		<img src="<?= esc_url($s3sRedux['principalSign']) ?>" alt="Signature">
																	<?php endif; ?>
																	<p><strong><?= esc_html($instHeadName) ?></strong></p>
																	<p><?= esc_html($s3sRedux['inst_head_title']) ?></p>
																	<p><?= esc_html($s3sRedux['institute_name']) ?></p>
																</div>
															</div>
														</div>
													</div>

													<?php
													// Increment roll/reg for board type
													if ($testimonial_type == 'board') {
														$rollStart++;
														$regStart++;
													}
												}

												$testimonialHtml = ob_get_clean();
												if ($testimonialHtml === false) {
													$testimonialHtml = '';
												}
												$testimonialHtml = trim($testimonialHtml);

												if ($generatedTestimonials > 0 && isset($_GET['creatId']) && ! $useManualRef) {
													$nextRefValue = $refSerialCounter;
													$storedValue = (string) $nextRefValue;
													$updated = $wpdb->update(
														'sm_options',
														['option_value' => $storedValue],
														['option_name' => 'testimonial_ref'],
														['%s'],
														['%s']
													);

													if ($updated === false || $updated === 0) {
														$wpdb->insert(
															'sm_options',
															['option_name' => 'testimonial_ref', 'option_value' => $storedValue],
															['%s', '%s']
														);
													}

													$s3sRedux['testimonial_ref'] = $nextRefValue;
												}

												if ($testimonialHtml !== '') {
													// Persist a static snapshot so printing relies on a cached HTML file.
													$snapshotMeta = [
														$testimonial_type,
														($class !== '' ? 'class-' . $class : ''),
														($section !== '' ? 'section-' . $section : ''),
														($year !== '' ? 'year-' . $year : ''),
														($exam !== '' ? 'exam-' . $exam : '')
													];
													$snapshotArgs = [
														'subdir' => 'testimonials',
														'prefix' => 'testimonial'
													];
													$snapshot = s3s_store_html_snapshot($testimonialHtml, $snapshotMeta, $snapshotArgs);
													if ($snapshot['path'] !== '') {
														$staticFilePath = $snapshot['path'];
													}
													if ($snapshot['url'] !== '') {
														$staticTestimonialUrl = $snapshot['url'];
													}
													if ($snapshot['error'] !== '' && $staticWriteError === '') {
														$staticWriteError = $snapshot['error'];
													}
												}

												if ($staticFilePath && file_exists($staticFilePath)) {
													include $staticFilePath;
												} else {
													echo $testimonialHtml;
												}

												if ($staticWriteError !== '') {
													echo '<div class="alert alert-warning" role="alert">' . esc_html($staticWriteError) . '</div>';
												}

												if ($staticTestimonialUrl !== '') {
													?>
													<script type="text/javascript">
														(function() {
															var area = document.getElementById('printArea');
															if (area) {
																area.setAttribute('data-static-url', '<?= esc_js($staticTestimonialUrl) ?>');
															}
														})();
													</script>
											<?php
												}
											} else {
												echo "<h3 class='text-center'>No Student Found</h3>";
											}
											?>

										</div>
									</div>
									<div id="editor"></div>
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

			$('.resultClass').on('change', function() {
				var $form = $(this).closest('form');
				var siteUrl = $('#theSiteURL').text();
				var loadingOption = '<option disabled selected>Loading...</option>';

				$form.find('.resultExam').html(loadingOption).prop('disabled', true);
				$form.find('.resultSection').html(loadingOption).prop('disabled', true);
				$form.find('.resultYear').html(loadingOption).prop('disabled', true);

				$.ajax({
					url: siteUrl + "/inc/ajaxAction.php",
					method: "POST",
					data: {
						class: $(this).val(),
						type: 'getExams'
					},
					dataType: "html"
				}).done(function(msg) {
					var content = msg && msg.trim() ? msg : '<option disabled selected>No exam found</option>';
					$form.find('.resultExam').html(content).prop('disabled', false);
				}).fail(function() {
					$form.find('.resultExam').html('<option disabled selected>Failed to load</option>').prop('disabled', true);
				});

				$.ajax({
					url: siteUrl + "/inc/ajaxAction.php",
					method: "POST",
					data: {
						class: $(this).val(),
						type: 'getYears'
					},
					dataType: "html"
				}).done(function(msg) {
					var content = msg && msg.trim() ? msg : '<option disabled selected>No year found</option>';
					$form.find('.resultYear').html(content).prop('disabled', false);
				}).fail(function() {
					$form.find('.resultYear').html('<option disabled selected>Failed to load</option>').prop('disabled', true);
				});

				$.ajax({
					url: siteUrl + "/inc/ajaxAction.php",
					method: "POST",
					data: {
						class: $(this).val(),
						type: 'getSection'
					},
					dataType: "html"
				}).done(function(msg) {
					var content = msg && msg.trim() ? msg : '<option disabled selected>No section found</option>';
					$form.find('.resultSection').html(content).prop('disabled', false);
				}).fail(function() {
					$form.find('.resultSection').html('<option disabled selected>Failed to load</option>').prop('disabled', true);
				});
			});
		})(jQuery);

		function printTestimonial(divId) {
			var container = document.getElementById(divId);
			var staticUrl = '';
			if (container && typeof container.getAttribute === 'function') {
				staticUrl = container.getAttribute('data-static-url') || '';
			}

			var buildHeadContent = function() {
				var safeBaseHref = document.location.href.replace(/"/g, '&quot;');
				var headContent = '<meta charset="utf-8"><title>Testimonial</title><base href="' + safeBaseHref + '">';
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
							throw new Error('Failed to load static testimonial');
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
	</script>