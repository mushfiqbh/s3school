<?php
/*
** Template Name: Admin Testimonail
*/
global $wpdb;
global $s3sRedux;
// fetch instLogo, principalSignature and testimonial reference from db
$query = "SELECT option_name, option_value FROM sm_options WHERE option_name IN ('instLogo', 'principalSign', 'testimonial_ref')";
$results = $wpdb->get_results($query);
foreach ($results as $row) {
	$optionValue = isset($row->option_value) ? maybe_unserialize($row->option_value) : '';

	if (in_array($row->option_name, ['instLogo', 'principalSign'], true)) {
		$imageUrl = '';
		if (is_array($optionValue) && isset($optionValue['url']) && !empty($optionValue['url'])) {
			$imageUrl = $optionValue['url'];
		} elseif (is_string($optionValue) && !empty($optionValue)) {
			$imageUrl = $optionValue;
		}

		// Convert relative path to full URL if needed
		if (!empty($imageUrl) && strpos($imageUrl, 'http') !== 0) {
			$imageUrl = home_url($imageUrl);
		}

		$s3sRedux[$row->option_name] = $imageUrl;
	} elseif ($row->option_name === 'testimonial_ref') {
		$s3sRedux[$row->option_name] = max(1, (int) $optionValue);
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

function formatBirthDate($birthDate): string
{
	$timestamp = strtotime($birthDate);
	if ($timestamp !== false) {
		$day = (int) date('j', $timestamp);
		$month = date('F', $timestamp);
		$year = (int) date('Y', $timestamp);

		$ordinals = [
			1 => 'st',
			2 => 'nd',
			3 => 'rd'
		];
		$daySuffix = $ordinals[$day % 10] ?? 'th';
		if (in_array($day % 100, [11, 12, 13], true)) {
			$daySuffix = 'th';
		}

		$ones = [
			0 => 'Zero',
			1 => 'One',
			2 => 'Two',
			3 => 'Three',
			4 => 'Four',
			5 => 'Five',
			6 => 'Six',
			7 => 'Seven',
			8 => 'Eight',
			9 => 'Nine',
			10 => 'Ten',
			11 => 'Eleven',
			12 => 'Twelve',
			13 => 'Thirteen',
			14 => 'Fourteen',
			15 => 'Fifteen',
			16 => 'Sixteen',
			17 => 'Seventeen',
			18 => 'Eighteen',
			19 => 'Nineteen'
		];

		$tens = [
			20 => 'Twenty',
			30 => 'Thirty',
			40 => 'Forty',
			50 => 'Fifty',
			60 => 'Sixty',
			70 => 'Seventy',
			80 => 'Eighty',
			90 => 'Ninety'
		];

		$yearWords = [];

		$thousands = intdiv($year, 1000);
		$remainder = $year % 1000;

		if ($thousands > 0) {
			$yearWords[] = $ones[$thousands] . ' Thousand';
		}

		$hundreds = intdiv($remainder, 100);
		$remainder %= 100;

		if ($hundreds > 0) {
			$yearWords[] = $ones[$hundreds] . ' Hundred';
		}

		if ($remainder > 0) {
			if ($remainder < 20) {
				$yearWords[] = $ones[$remainder];
			} else {
				$tenPart = intdiv($remainder, 10) * 10;
				$onePart = $remainder % 10;
				$segment = $tens[$tenPart];
				if ($onePart > 0) {
					$segment .= ' ' . $ones[$onePart];
				}
				$yearWords[] = $segment;
			}
		}

		if (empty($yearWords)) {
			$yearWords[] = $ones[0];
		}

		$dateInWords = sprintf('%d%s %s %s', $day, $daySuffix, $month, implode(' ', $yearWords));


		return $dateInWords;
	}

	return '';
}
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
										<a class="nav-link <?= $currentTestimonialType === 'regular' ? 'active' : '' ?>" href="#testimonial-form" data-testimonial-type="regular" role="tab" aria-controls="testimonial-form" aria-selected="<?= $currentTestimonialType === 'regular' ? 'true' : 'false' ?>">Regular Testimonial</a>
									</li>
									<li class="nav-item <?= $currentTestimonialType === 'board' ? 'active' : '' ?>">
										<a class="nav-link <?= $currentTestimonialType === 'board' ? 'active' : '' ?>" href="#testimonial-form" data-testimonial-type="board" role="tab" aria-controls="testimonial-form" aria-selected="<?= $currentTestimonialType === 'board' ? 'true' : 'false' ?>">Board Testimonial</a>
									</li>
									<li class="nav-item <?= $currentTestimonialType === 'technical' ? 'active' : '' ?>">
										<a class="nav-link <?= $currentTestimonialType === 'technical' ? 'active' : '' ?>" href="#testimonial-form" data-testimonial-type="technical" role="tab" aria-controls="testimonial-form" aria-selected="<?= $currentTestimonialType === 'technical' ? 'true' : 'false' ?>">Technical Board Testimonial</a>
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
													$classQuery = $wpdb->get_results("SELECT classid,className FROM ct_class WHERE classid IN (SELECT examClass FROM ct_exam GROUP BY examClass ORDER BY className ASC)");
													echo "<option value=''>Select Class</option>";

													foreach ($classQuery as $class) {
														echo "<option value='" . $class->classid . "'>" . $class->className . "</option>";
													}
													?>
												</select>
											</div>

											<div class="form-group ">
												<label>Exam</label>
												<select class="resultExam form-control" name="exam" required disabled>
													<option disabled selected>Select Class</option>
												</select>
											</div>

											<div class="form-group ">
												<label>Section</label>
												<select class="resultSection" class="form-control" name="section" required disabled>
													<option disabled selected>Select Class</option>
												</select>
											</div>

											<div class="form-group">
												<label>Year</label>
												<select class="resultYear form-control" name="syear" required disabled>
													<option disabled selected>Select Class</option>
												</select>
											</div>

											<div class="form-group board-fields" style="<?= $showBoardFields ? '' : 'display:none;' ?>">
												<input class="form-control" type="text" name="rollstart" placeholder="Roll Start From" style="width: 115px">
											</div>

											<div class="form-group board-fields" style="<?= $showBoardFields ? '' : 'display:none;' ?>">
												<input class="form-control" type="text" name="regstart" placeholder="Reg Start From" style="width: 115px">
											</div>

											<div class="form-group board-fields" style="<?= $showBoardFields ? '' : 'display:none;' ?>">
												<input type="text" name="resSession" class="form-control" placeholder="Session" style="width: 100px">
											</div>

											<div class="form-group board-only-fields" style="<?= $showBoardOnlyFields ? '' : 'display:none;' ?>">
												<input type="text" name="resPassingYear" class="form-control" placeholder="Passing Year" style="width: 110px">
											</div>

											<div class="form-group" id="idRoll">
												<input class="form-control" type="text" name="roll" placeholder="Roll" style="width: 110px">
											</div>
											<div class="form-group">
												<label>Reference (Optional)</label>
												<input class="form-control" type="text" name="ref" placeholder="Reference" value="<?= isset($_GET['ref']) ? esc_attr(wp_unslash($_GET['ref'])) : '' ?>">
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

								<?php if (isset($_GET['syear'])) { ?>
									<div class="col-md-12">
										<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>
									</div>
									<div id="printArea" class="col-md-12 printBG">
										<div class="printArea">
											<link href="https://fonts.googleapis.com/css?family=Satisfy" rel="stylesheet">
											<link href="https://fonts.googleapis.com/css?family=Quicksand" rel="stylesheet">
											<style type="text/css">
												@page {
													size: A4;
												}

												@media print {
													body {
														-webkit-print-color-adjust: exact;
														print-color-adjust: exact;
													}

													img {
														max-width: 100%;
														height: auto;
														display: block;
													}
												}

												.itemMainBox {
													max-width: 21cm;
													min-height: calc(29.7cm - 40mm);
													display: inline-block;
													overflow: hidden;
													margin: 20px 0;
													font-family: sans-serif;
													width: 100%;
													position: relative;
													page-break-after: always;
													box-sizing: border-box;
												}

												.itemMainBox-border {
													border: 10px solid #005daa;
												}

												.itemMainBox p {
													font-size: 16px;
													font-family: 'Quicksand', sans-serif;
													line-height: 2;
												}

												.itemMainBox .itemWaterMark {
													position: absolute;
													width: 100%;
													bottom: 0;
													left: 0;
													z-index: -1;
													text-align: center;
												}

												.itemMainBox .itemWaterMark img {
													opacity: .12;
													width: 250px;
												}

												.itemMainBox .instLogo {
													width: 90px;
													position: absolute;
													left: 0;
													top: 0;
												}

												.itemMainBox .instName {
													margin: 0 0 5px 0;
													color: #337ab7;
													font-weight: bold;
													font-size: 30px;
												}

												.itemMainBox .instAddrs {
													margin: 0 0 10px 0;
													color: #000;
													font-weight: normal;
													font-size: 18px;
												}

												.itemMainBox .examName {
													margin: 0 auto 7px;
													text-align: center;
													font-size: 25px;
												}

												.itemMainBox .examName h3 {
													margin: 0;
													font-size: 20px;
												}

												.itemMainBox .itemInfo {
													text-align: center;
													margin: 20px 0;
													clear: both;
												}

												.itemMainBox .admitCard {
													margin: 0 0 10px 0;
													color: #f7740c;
													font-weight: bold;
													background: #f0f0f0;
													print-color-adjust: exact;
													-webkit-print-color-adjust: exact;
													padding: 10px;
													border-radius: 5px;
													font-size: 25px;
													border: 2px solid #f0f0f0;
												}

												.itemMainBox .admitNote {
													float: left;
												}

												.itemMainBox .admitNote p {
													margin: 0;
													padding-left: 15px;
												}

												.itemMainBox hr {
													clear: both;
												}

												.itemMainBox .princSign {
													float: right;
												}

												b u {
													font-family: 'Rechtman', sans-serif;
													text-decoration: none;
													padding: 0 5px;
												}

												.editable {
													position: relative;
												}

												.editable .closeEdit {
													position: absolute;
													z-index: 5;
													cursor: pointer;
													right: 0px;
													top: -6px;
													width: 15px;
													text-indent: 0;
													margin: 0;
													padding: 0;
													background: rgba(255, 0, 0, .8);
													border-radius: 3px;
													text-align: center;
													line-height: 15px;
													color: #fff;
													font-family: arial;
													-webkit-touch-callout: none;
													-webkit-user-select: none;
													-khtml-user-select: none;
													-moz-user-select: none;
													-ms-user-select: none;
													user-select: none;
												}

												#wrapper {
													position: absolute;
													overflow: auto;
													left: 0;
													right: 0;
													top: 0;
													bottom: 0;
												}

												#wrapper-border {
													border: 15px solid #d0bf9e;
												}
											</style>

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
											$testimonial_prepared_by = $wpdb->get_var("SELECT option_value FROM sm_options WHERE option_name = 'testimonial_prepared_by'");
											$testimonial_border_type = $wpdb->get_var("SELECT option_value FROM sm_options WHERE option_name = 'testimonial_type'");
											$testimonial_pad = $wpdb->get_var("SELECT option_value FROM sm_options WHERE option_name = 'testimonial_pad'");
											$instHeadName = $wpdb->get_var("SELECT option_value FROM sm_options WHERE option_name = 'inst_head_name'");

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
												if ($refSerialStart < 1) {
													$refSerialStart = 1;
												}
												$refSerialCounter = $refSerialStart;
												$generatedTestimonials = 0;

												foreach ($groupsBy as $value) {
													if ($useManualRef) {
														$currentRefValue = $manualRef;
													} else {
														$currentRefValue = $refSerialCounter;
														$refSerialCounter++;
													}
													$generatedTestimonials++;
											?>
													<div class="itemMainBox <?= $testimonial_border_type == 'Pad' ? '' : 'itemMainBox-border' ?>">
														<div id="<?= $testimonial_border_type == 'Pad' ? 'wrapper' : 'wrapper-border' ?>">
															<div style="padding: 32px;">
																<?php

																if ($testimonial_border_type == 'Pad') {
																?>
																	<script>
																		const itemMainBox = document.querySelector('.itemMainBox');
																		itemMainBox.style.backgroundImage = "url('<?= $testimonial_pad ?>')";
																		itemMainBox.style.backgroundSize = "cover";
																	</script>
																	<div style="margin-top:200px;"></div>
																<?php
																} else {
																?>
																	<table style="width: 100%;">
																		<tr>
																			<td style="width: 20%; text-align: left; vertical-align: top;">
																				<?php if ($s3sRedux['instLogo']): ?>
																					<img width="100" style="margin-top: 0;" src="<?= $s3sRedux['instLogo'] ?>" alt="Logo">
																				<?php endif; ?>
																			</td>
																			<td style="width: 80%; text-align: center;">
																				<h2 class="instName"><?= $s3sRedux['institute_name'] ?></h2>
																				<h5 class="instAddrs"><?= $s3sRedux['institute_address'] ?></h5>
																				<h5 class="instAddrs">ESTD: <?= $s3sRedux['estd_year'] ?>, EIIN: <?= $s3sRedux['institute_eiin'] ?></h5>
																			</td>
																		</tr>
																	</table>

																	<table style="width: 100%; margin-top: 1px;">
																		<tr>
																			<td style="width: 100%; text-align: center;">
																				Web: <?= home_url() ?> &nbsp; Email: <?= $s3sRedux['institute_email'] ?> &nbsp; Institute Code: <?= $institute_code ?>
																			</td>
																		</tr>
																	</table>

																	<b>
																		<center>----------------------------------------------------------------------------------------------------------------</center>
																	</b>

																	<table style="width: 100%; margin-top: 3px;">
																		<tr>
																			<td style="width: 50%; text-align: left;">
																				Ref: <?= esc_html($currentRefValue); ?>
																			</td>
																			<td style="width: 50%; text-align: right;">
																				Date: <?= date('d-m-Y') ?>
																			</td>
																		</tr>
																	</table>
																<?php } ?>

																<div class="section3" style="background: url(<?= $s3sRedux['instLogo'] ?>) no-repeat center; background-size: 400px;">
																	<div style="background: rgba(255,255,255,0.9);margin-top:80px;">
																		<div class="itemInfo">
																			<h3 style="text-transform: uppercase;">Testimonial</h3>
																		</div>

																		<div style="width: 100%;">
																			<?php if ($testimonial_type == 'board'): ?>
																				<!-- Board Testimonial -->
																				<p style="text-indent: 30px">
																					This is to Certify that
																					<b><u><?= $value->stdName ?></u></b> <?= ($value->stdGender == 0) ? 'daughter' : 'son' ?>
																					of <b><u><?= $value->stdFather ?></u></b> and <b><u><?= $value->stdMother ?></u></b>
																					passed the <b><u><?= $exam ?></u></b> Examination from this institution held in the year <b class='editable'><u><?= $passingyear ?></u></b>
																					under the <?= $s3sRedux['board_name_1'] ?> in <?= isset($value->groupName) ? "Group <b><u>$value->groupName</u></b>" : '' ?>
																					bearing Roll <?= $s3sRedux['center_code'] ?> No <b class='editable'><u><?= $value->sscRoll ?></u></b>
																					Registration No<b class='editable'><u><?= $value->sscReg ?></u></b> Session <b><u><?= $session ?></u></b> and
																					obtained GPA <b class='editable'><u><?= $value->stdGPA ?></u></b> Letter Grade<b class='editable'><u><?= $value->stdIntellectual ?></u></b>.
																					<?= ($value->stdGender == 0) ? 'Her' : 'His' ?> date of birth is <b class='editable'><u><?= date('d-m-Y', strtotime($value->stdBrith)) ?></u></b> in words
																					<?php
																					$birthDate = $value->stdBrith;
																					if ($birthDate) {
																						echo formatBirthDate($birthDate);
																					}
																					?>.
																				</p>
																			<?php elseif ($testimonial_type == 'technical'): ?>
																				<!-- Technical Board Testimonial -->
																				<p>This is to Certify that
																					<b><u><?= $value->stdName ?></u></b>
																					<?= ($value->stdGender == 0) ? 'daughter' : 'son' ?>
																					of Mr. <b><u><?= $value->stdFather ?></u></b> &
																					<b><u><?= $value->stdMother ?></u></b>
																					is a student of this institution in Class <b><u><?= $value->className ?>,</u></b>
																					<?= isset($value->sectionName) ? "Section <b><u>$value->sectionName</u></b>" : '' ?>
																					<?= isset($value->groupName) ? "Group <b><u>$value->groupName</u></b>" : '' ?>
																					Roll No. <b><u><?= $value->infoRoll ?></u></b>
																					and <?= ($value->stdGender == 0) ? 'she' : 'he' ?> appeared at <b><u><?= $value->examName ?></u></b> held in <b><u><?= $year ?></u></b>
																					under the <?= esc_html($s3sRedux['board_name_2']) ?> and placed /merit position <?= isset($value->spPosition) ? "<b><u>" . $value->spPosition . "</u></b>" : '............' ?>
																					GPA- <?= isset($value->spPoint) ? "<b><u>" . $value->spPoint . "</u></b>" : '............' ?>.
																					<?= ($value->stdGender == 0) ? 'Her' : 'His' ?> date of birth is <b class='editable'><u><?= date('d-m-Y', strtotime($value->stdBrith)) ?></u></b> in words
																					<?php
																					$birthDate = $value->stdBrith;
																					if ($birthDate) {
																						echo formatBirthDate($birthDate);
																					}
																					?>.
																				</p>
																			<?php else: ?>
																				<!-- Regular Testimonial -->
																				<p>This is to Certify that
																					<b><u><?= $value->stdName ?></u></b>
																					<?= ($value->stdGender == 0) ? 'daughter' : 'son' ?>
																					of Mr. <b><u><?= $value->stdFather ?></u></b> &
																					<b><u><?= $value->stdMother ?></u></b>
																					is a student of this institution in Class <b><u><?= $value->className ?>,</u></b>
																					<?= isset($value->sectionName) ? "Section <b><u>$value->sectionName</u></b>" : '' ?>
																					<?= isset($value->groupName) ? "Group <b><u>$value->groupName</u></b>" : '' ?>
																					Roll No. <b><u><?= $value->infoRoll ?></u></b>
																					and <?= ($value->stdGender == 0) ? 'she' : 'he' ?> appeared at <b><u><?= $value->examName ?></u></b> held in <b><u><?= $year ?></u></b> and
																					placed /merit position <?= isset($value->spPosition) ? "<b><u>" . $value->spPosition . "</u></b>" : '............' ?>
																					GPA- <?= isset($value->spPoint) ? "<b><u>" . $value->spPoint . "</u></b>" : '............' ?>.
																					<?= ($value->stdGender == 0) ? 'Her' : 'His' ?> date of birth is <b class='editable'><u><?= date('d-m-Y', strtotime($value->stdBrith)) ?></u></b> in words
																					<?php
																					$birthDate = $value->stdBrith;
																					if ($birthDate) {
																						echo formatBirthDate($birthDate);
																					}
																					?>.
																				</p>
																			<?php endif; ?>

																			<p style="text-indent: 30px">
																				<?= ($value->stdGender == 0) ? 'She' : 'He' ?> bears a good moral character.
																				So far I know <?= ($value->stdGender == 0) ? 'she' : 'he' ?> was not involved in any activity subversive of the State or
																				discipline of this institute.
																			</p>
																			<p></p>
																			<p></p>
																			<p style="<?= ($testimonial_type == 'board') ? 'text-indent: 30px' : 'padding-left: 20px' ?>">I wish <?= ($value->stdGender == 0) ? 'her' : 'him' ?> every success in life.</p>

																			<table style="width: 100%; margin-top: 120px;">
																				<tr>
																					<td>
																						<p style="line-height: 1.2;">Prepared by: <?= $testimonial_prepared_by ?></p>
																						<p style="line-height: 1.2;">Date: <?= date('d-m-Y') ?></p>
																					</td>
																					<td style="width: 350px; text-align: center;">
																						<div style="display: flex; flex-direction: column; align-items: center;">
																							<?php if ($s3sRedux['principalSign']): ?>
																								<img src="<?= $s3sRedux['principalSign'] ?>" alt="Signature" style="max-height: 50px; max-width: 150px;">
																							<?php endif; ?>
																						</div>
																						<p style="margin: 0;line-height: 1.2;"><?= $instHeadName ?><br>
																							<?= $s3sRedux['inst_head_title'] ?><br>
																							<?= $s3sRedux['institute_name'] ?><br>
																							<?= $s3sRedux['institute_address'] ?></p>
																					</td>
																				</tr>
																			</table>
																		</div>
																	</div>
																</div>
															</div>
															<div style="clear: both;"></div>
														</div>

												<?php
													// Increment roll/reg for board type
													if ($testimonial_type == 'board') {
														$rollStart++;
														$regStart++;
													}
												}

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

				function applyTestimonialType(type) {
					var $boardFields = $('.board-fields');
					var $boardOnlyFields = $('.board-only-fields');
					$('#testimonial_type_input').val(type);

					if (type === 'board' || type === 'technical') {
						$boardFields.show();
					} else {
						$boardFields.hide();
					}

					if (type === 'board') {
						$boardOnlyFields.show();
					} else {
						$boardOnlyFields.hide();
					}
				}

				$(function() {
					var $tabs = $('#testimonialTypeTabs .nav-link');
					var initialType = $('#testimonial_type_input').val() || 'regular';

					$tabs.each(function() {
						var $link = $(this);
						var isActive = $link.data('testimonial-type') === initialType;
						$link.attr('aria-selected', isActive ? 'true' : 'false');
						$link.toggleClass('active', isActive);
						$link.closest('.nav-item').toggleClass('active', isActive);
					});

					applyTestimonialType(initialType);

					$tabs.on('click', function(e) {
						e.preventDefault();
						var $link = $(this);
						var type = $link.data('testimonial-type');
						if (!type) {
							return;
						}

						$tabs.removeClass('active').attr('aria-selected', 'false');
						$('#testimonialTypeTabs .nav-item').removeClass('active');

						$link.addClass('active').attr('aria-selected', 'true');
						$link.closest('.nav-item').addClass('active');

						applyTestimonialType(type);
					});
				});

				$('.editable').on('click', 'u', function() {
					$this = $(this);
					$this.closest('.editable').html("").append("<input type='text' value='" + $this.text() + "'><p class='closeEdit'>x</p>");
				});



				$('.editable').on('focusout', 'input', function() {
					$this = $(this);
					$this.closest('.editable').html("<u>" + $this.val() + "</u>");
				});

				$('.resultClass').change(function() {
					$from = $(this).closest('form');
					var $siteUrl = $('#theSiteURL').text();
					var loadingOption = '<option disabled selected>Loading...</option>';

					$from.find('.resultExam').html(loadingOption).prop('disabled', true);
					$from.find('.resultSection').html(loadingOption).prop('disabled', true);
					$from.find('.resultYear').html(loadingOption).prop('disabled', true);
					$.ajax({
						url: $siteUrl + "/inc/ajaxAction.php",
						method: "POST",
						data: {
							class: $(this).val(),
							type: 'getExams'
						},
						dataType: "html"
					}).done(function(msg) {
						var content = msg && msg.trim() ? msg : '<option disabled selected>No exam found</option>';
						$from.find(".resultExam").html(content).prop('disabled', false);
					}).fail(function() {
						$from.find('.resultExam').html('<option disabled selected>Failed to load</option>').prop('disabled', true);
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
						var content = msg && msg.trim() ? msg : '<option disabled selected>No year found</option>';
						$from.find(".resultYear").html(content).prop('disabled', false);
					}).fail(function() {
						$from.find('.resultYear').html('<option disabled selected>Failed to load</option>').prop('disabled', true);
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
						var content = msg && msg.trim() ? msg : '<option disabled selected>No section found</option>';
						$from.find(".resultSection").html(content).prop('disabled', false);
					}).fail(function() {
						$from.find('.resultSection').html('<option disabled selected>Failed to load</option>').prop('disabled', true);
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