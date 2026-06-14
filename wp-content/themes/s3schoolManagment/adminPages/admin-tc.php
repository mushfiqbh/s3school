<?php
/*
** Template Name: Admin Transfer Certificate
*/
global $wpdb;
global $s3sRedux;
$bg_image_url = '';

require_once __DIR__ . '/functions/html-utils.php';

// fetch instLogo, principalSignature and TC (and pads) from db
$query = "SELECT option_name, option_value FROM sm_options WHERE option_name IN ('instLogo', 'principalSign', 'tc_ref', 'institute_name', 'institute_address', 'estd_year', 'institute_eiin', 'inst_head_name', 'inst_head_title', 'testimonial_prepared_by', 'testimonial_pad', 'tc_pad')";
$results = $wpdb->get_results($query);
foreach ($results as $row) {
    $optionValue = isset($row->option_value) ? maybe_unserialize($row->option_value) : '';

    if (in_array($row->option_name, ['instLogo', 'principalSign', 'testimonial_pad', 'tc_pad'], true)) {
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
    } elseif ($row->option_name === 'tc_ref') {
        $s3sRedux['tc_ref'] = max(1, (int) $optionValue);
    } else {
        $s3sRedux[$row->option_name] = $optionValue;
    }
}

if (!isset($s3sRedux['tc_ref'])) {
    $s3sRedux['tc_ref'] = 1;
}

$testimonial_border_type = $wpdb->get_var("SELECT option_value FROM sm_options WHERE option_name = 'testimonial_type'");
$allowedBorderTypes = ['Default', 'Pad'];
if (!in_array($testimonial_border_type, $allowedBorderTypes, true)) {
    $testimonial_border_type = 'Default';
}

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
                                <h3 class="panel-title" style="margin: 0; font-weight: 600; letter-spacing: 0.4px;">Transfer Certificate</h3>
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
                                                <label>Exam</label>
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

                                            <div class="form-group">
                                                <label>Format</label>
                                                <select class="form-control" name="pad_type">
                                                    <?php
                                                    $hasPadImage = !empty($s3sRedux['tc_pad']);
                                                    $padDefault = !isset($_GET['pad_type']) && $hasPadImage;
                                                    ?>
                                                    <option value="default" <?= (isset($_GET['pad_type']) && $_GET['pad_type'] == 'default') || (!isset($_GET['pad_type']) && !$hasPadImage) ? 'selected' : '' ?>>Default</option>
                                                    <option value="pad" <?= (isset($_GET['pad_type']) && $_GET['pad_type'] == 'pad') || $padDefault ? 'selected' : '' ?>>Pad</option>
                                                </select>
                                            </div>

                                            <div class="form-group" id="idRoll">
                                                <input class="form-control" type="text" name="roll" placeholder="Roll" style="width: 110px">
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
                                        <button onclick="printTC('printArea')" class="pull-right btn btn-primary">Print</button>
                                    </div>
                                    <div id="printArea" class="col-md-12 printBG">
                                        <div class="printArea">
                                            <?php
                                            $year         = @$_GET['syear'];
                                            $class         = @$_GET['class'];
                                            $section     = @$_GET['section'];
                                            $roll         = @$_GET['roll'];
                                            $exam         = @$_GET['exam'];
                                            $manualRef    = isset($_GET['ref']) ? trim(wp_unslash($_GET['ref'])) : '';
                                            $useManualRef = $manualRef !== '';
                                            $institute_code = $wpdb->get_var("SELECT option_value FROM sm_options WHERE option_name = 'institute_code'");
                                            $testimonial_prepared_by = $wpdb->get_var("SELECT option_value FROM sm_options WHERE option_name = 'testimonial_prepared_by'");
                                            $testimonial_border_type = $wpdb->get_var("SELECT option_value FROM sm_options WHERE option_name = 'testimonial_type'");
                                            $testimonial_pad = $wpdb->get_var("SELECT option_value FROM sm_options WHERE option_name = 'testimonial_pad'");
                                            $instHeadName = $wpdb->get_var("SELECT option_value FROM sm_options WHERE option_name = 'inst_head_name'");
                                            
                                            // Handle Pad Selection
                                            $pad_type = $_GET['pad_type'] ?? 'default';
                                            $bg_image_url = '';
                                            // Using s3sRedux which we populated at the top of the file
                                            if ($pad_type === 'pad' && !empty($s3sRedux['tc_pad'])) {
                                                $bg_image_url = $s3sRedux['tc_pad'];
                                            }

                                            $query = "SELECT stdName,stdGender,infoRoll,className,sectionName,stdImg,groupName,infoYear,stdPhone,stdFather,stdMother,stdPresent,examName,spPosition,spPoint,stdBrith,ct_student.studentid AS studentId
                    FROM ct_student
                        LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid
                        LEFT JOIN ct_class ON ct_studentinfo.infoClass = ct_class.classid
                        LEFT JOIN ct_exam ON $exam = ct_exam.examid
                        LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId
                        LEFT JOIN ct_studentPoint ON ct_studentinfo.infoStdid = ct_studentPoint.spStdID AND ct_studentPoint.spYear = '$year' AND ct_studentPoint.spClass = $class AND ct_studentPoint.spExam = $exam
                        LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
                        WHERE infoYear = '$year'";

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

                                            // Display results
                                            if ($groupsBy) {
                                                $refSerialStart = isset($s3sRedux['tc_ref']) ? (int) $s3sRedux['tc_ref'] : 1;
                                                if (isset($_GET['serial_start']) && $_GET['serial_start'] !== '') {
                                                    $refSerialStart = max(1, (int) $_GET['serial_start']);
                                                }
                                                if ($refSerialStart < 1) {
                                                    $refSerialStart = 1;
                                                }
                                                $refSerialCounter = $refSerialStart;
                                                $generatedTestimonials = 0;
                                                $staticFilePath = '';
                                                $staticTCUrl = '';
                                                $staticWriteError = '';

                                                ob_start();
                                            ?>
                                                <link href="https://fonts.googleapis.com/css?family=Satisfy" rel="stylesheet">
                                                <link href="https://fonts.googleapis.com/css?family=Quicksand" rel="stylesheet">
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

                                                        .tc-card {
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
                                                    
                                                    .tc-card {
                                                        width: 21cm;
                                                        height: 29.7cm;
                                                        margin: 0 auto;
                                                        /* background-color: #fffaf0; Removed default bg color if using image, handled above */
                                                        box-sizing: border-box;
                                                        position: relative;
                                                        page-break-after: always;
                                                        -webkit-print-color-adjust: exact;
                                                        print-color-adjust: exact;
                                                    }

                                                    .tc-content {
                                                        padding: 50px 60px;
                                                    }

                                                    .tc-card .header {
                                                        text-align: center;
                                                        margin-bottom: 20px;
                                                    }

                                                    .tc-card .header h1 {
                                                        font-size: 20pt;
                                                        color: #000;
                                                        margin: 0 0 5px 0;
                                                        font-weight: bold;
                                                        text-transform: uppercase;
                                                        letter-spacing: 1px;
                                                    }

                                                    .tc-card .header .sub {
                                                        font-size: 13pt;
                                                        color: #000;
                                                        font-weight: normal;
                                                        margin: 5px 0;
                                                        line-height: 1.4;
                                                    }

                                                    .tc-card .content {
                                                        font-size: 17px;
                                                        line-height: 1.8;
                                                        color: #000;
                                                        margin-top: 20px;
                                                        margin-bottom: 20px;
                                                    }

                                                    .tc-card .content p {
                                                        margin-bottom: 12px;
                                                        text-align: justify;
                                                    }

                                                    .tc-card .ref-date {
                                                        display: flex;
                                                        justify-content: space-between;
                                                        margin-bottom: 15px;
                                                    }

                                                    .tc-card .doc-footer {
                                                        margin-top: 20px;
                                                        color: #000;
                                                        display: flex;
                                                        justify-content: end;
                                                        align-items: flex-end;
                                                    }

                                                    .tc-card .doc-footer .left {
                                                        width: 40%;
                                                        font-size: 15px;
                                                    }

                                                    .tc-card .doc-footer .right-sign {
                                                        text-align: center;
                                                        width: 40%;
                                                        font-size: 15px;
                                                    }

                                                    .tc-card .doc-footer .right-sign img {
                                                        max-height: 60px;
                                                        max-width: 150px;
                                                        margin: 0 auto 5px;
                                                        display: block;
                                                    }

                                                    .tc-card .doc-footer .right-sign p {
                                                        margin: 2px 0;
                                                        line-height: 1.3;
                                                    }

                                                    .tc-card .bottom {
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

                                                    .para {
                                                        text-align: justify;
                                                        margin-bottom: 14px;
                                                        line-height: 1.8;
                                                    }

                                                    .options {
                                                        margin-left: 20px;
                                                        line-height: 1.6;
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
                                                    $genderTitle = ($value->stdGender == 0) ? 'daughter' : 'son';
                                                    $subjectPronoun = ($value->stdGender == 0) ? 'She' : 'He';
                                                    $possessivePronoun = ($value->stdGender == 0) ? 'Her' : 'His';
                                                    $classLabel = trim($value->className . ($value->sectionName ? ' - ' . $value->sectionName : ''));
                                                    $birthDate = $value->stdBrith ? formatBirthDate($value->stdBrith) : '';
                                                    $presentAddress = $value->stdPresent ?: '';
                                                    $examResult = 'good academic standing';
                                                    if (!empty($value->spPoint)) {
                                                        $examResult = 'secured a GPA of ' . number_format((float) $value->spPoint, 2);
                                                        if (!empty($value->spPosition)) {
                                                            $examResult .= ' and positioned at ' . $value->spPosition;
                                                        }
                                                    }
                                                    $studentId = $value->studentId ?? '';
                                            ?>
                                                    <div class="tc-card">

                                                        <?php if($_GET['pad_type'] == 'default') { ?>
                                                        <div class="header" style="display: flex; align-items: center; justify-content: center; gap: 20px; margin-bottom: 20px;">
                                                            <div style="width: 15%; text-align: left;">
                                                                <?php if (!empty($s3sRedux['instLogo'])): ?>
                                                                    <img width="80" src="<?= esc_url($s3sRedux['instLogo']) ?>" alt="Logo">
                                                                <?php endif; ?>
                                                            </div>
                                                            <div style="width: 70%; text-align: center;">
                                                                <h1><?= esc_html($s3sRedux['institute_name']) ?></h1>
                                                                <p class="sub">
                                                                    <?= esc_html($s3sRedux['institute_address']) ?><br>
                                                                    Estd. <?= esc_html($s3sRedux['estd_year']) ?> | EIIN: <?= esc_html($s3sRedux['institute_eiin']) ?>
                                                                </p>
                                                            </div>
                                                            <div style="width: 15%;"></div>
                                                        </div>

                                                        <?php } else { ?>                         
                                                            <div style="width: 100%; height:100%;">
                                                                <img style="width: 100%; height:100%;" src='<?= $bg_image_url ?>' />
                                                            </div>
                                                        <?php } ?>

                                                        <div class="tc-content" style="position: absolute; inset: 0; z-index: 20; margin-top: 200px;">
                                                            <div class="title">TRANSFER CERTIFICATE</div>
                                                            <div class="ref-date">
                                                                <div>Serial No: <span class="line"><?= esc_html($currentRefValue) ?></span></div>
                                                                <div>Date: <span class="line"><?= esc_html(date('d-m-Y')) ?></span></div>
                                                            </div>
                                                            <div class="content">
                                                                <p class="para">
                                                                    This is to certify that <span class="line wide-line"><?= esc_html($value->stdName) ?></span>,
                                                                    <?= $genderTitle ?> of <span class="line wide-line"><?= esc_html($value->stdFather) ?></span>
                                                                    and <span class="line wide-line"><?= esc_html($value->stdMother) ?></span>, residing at 
                                                                    <span class="line wide-line"><?= esc_html($presentAddress) ?></span>, was a student of 
                                                                    Class <span class="line"><?= esc_html($value->className) ?></span>, Roll No 
                                                                    <span class="line"><?= esc_html($value->infoRoll) ?></span>, Registration No 
                                                                    <span class="line"><?= esc_html($studentId) ?></span>, during the Academic Year 
                                                                    <span class="line"><?= esc_html($value->infoYear) ?></span> at this institution.
                                                                </p>
                                                                <p class="para">
                                                                    <?= $subjectPronoun ?> studied regularly at this institution until 
                                                                    <span class="line wide-line"><?= esc_html($classLabel) ?></span>. According to the admission register, 
                                                                    <?= $possessivePronoun ?> date of birth is recorded as 
                                                                    <span class="line wide-line"><?= esc_html($birthDate) ?></span>.
                                                                </p>
                                                                <p class="para">
                                                                    <?= $subjectPronoun ?> appeared in the annual examination of Class 
                                                                    <span class="line"><?= esc_html($value->className) ?></span> and has 
                                                                    <?= esc_html($examResult) ?>.
                                                                </p>
                                                                <p class="para">
                                                                    <?= $subjectPronoun ?> has paid all school dues and fees up to 
                                                                    <span class="line wide-line"><?= esc_html($value->infoYear) ?></span>. <?= $possessivePronoun ?> conduct and character are good.
                                                                </p>
                                                                <p class="para"><strong>Reason for leaving the school:</strong></p>
                                                                <div class="options">
                                                                    a) Guardian's wish<br>
                                                                    b) Change of residence<br>
                                                                    c) Physical illness<br>
                                                                    d) Completion of studies
                                                                </div>
                                                                </div>
                                                            <div class="doc-footer">
                                                                <div class="right-sign">
                                                                    <?php if (!empty($s3sRedux['principalSign'])): ?>
                                                                        <img src="<?= esc_url($s3sRedux['principalSign']) ?>" alt="Signature" style="max-height: 60px; max-width: 150px; display: block; margin: 0 auto 5px;">
                                                                    <?php endif; ?>
                                                                    <p><strong><?= esc_html($s3sRedux['inst_head_name']) ?></strong></p>
                                                                    <p><?= esc_html($s3sRedux['inst_head_title']) ?></p>
                                                                    <p><?= esc_html($s3sRedux['institute_name']) ?></p>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                            <?php
                                                }

                                                $tcHtml = ob_get_clean();
                                                if ($tcHtml === false) {
                                                    $tcHtml = '';
                                                }
                                                $tcHtml = trim($tcHtml);

                                                if ($generatedTestimonials > 0 && isset($_GET['creatId']) && ! $useManualRef) {
                                                    $nextRefValue = $refSerialCounter;
                                                    $storedValue = (string) $nextRefValue;
                                                    $updated = $wpdb->update(
                                                        'sm_options',
                                                        ['option_value' => $storedValue],
                                                        ['option_name' => 'tc_ref'],
                                                        ['%s'],
                                                        ['%s']
                                                    );

                                                    if ($updated === false || $updated === 0) {
                                                        $wpdb->insert(
                                                            'sm_options',
                                                            ['option_name' => 'tc_ref', 'option_value' => $storedValue],
                                                            ['%s', '%s']
                                                        );
                                                    }

                                                    $s3sRedux['tc_ref'] = $nextRefValue;
                                                }

                                                if ($tcHtml !== '') {
                                                    // Persist a static snapshot so printing relies on a cached HTML file.
                                                    $snapshotMeta = [
                                                        'tc',
                                                        ($class !== '' ? 'class-' . $class : ''),
                                                        ($section !== '' ? 'section-' . $section : ''),
                                                        ($year !== '' ? 'year-' . $year : ''),
                                                        ($roll !== '' ? 'roll-' . $roll : '')
                                                    ];
                                                    $snapshotArgs = [
                                                        'subdir' => 'tc',
                                                        'prefix' => 'tc'
                                                    ];
                                                    $snapshot = s3s_store_html_snapshot($tcHtml, $snapshotMeta, $snapshotArgs);
                                                    if ($snapshot['path'] !== '') {
                                                        $staticFilePath = $snapshot['path'];
                                                    }
                                                    if ($snapshot['url'] !== '') {
                                                        $staticTCUrl = $snapshot['url'];
                                                    }
                                                    if ($snapshot['error'] !== '' && $staticWriteError === '') {
                                                        $staticWriteError = $snapshot['error'];
                                                    }
                                                }

                                                if ($staticFilePath && file_exists($staticFilePath)) {
                                                    include $staticFilePath;
                                                } else {
                                                    echo $tcHtml;
                                                }

                                                if ($staticWriteError !== '') {
                                                    echo '<div class="alert alert-warning" role="alert">' . esc_html($staticWriteError) . '</div>';
                                                }

                                                if ($staticTCUrl !== '') {
                                                    ?>
                                                    <script type="text/javascript">
                                                        (function() {
                                                            var area = document.getElementById('printArea');
                                                            if (area) {
                                                                area.setAttribute('data-static-url', '<?= esc_js($staticTCUrl) ?>');
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

            function printTC(divId) {
                var container = document.getElementById(divId);
                var staticUrl = '';
                if (container && typeof container.getAttribute === 'function') {
                    staticUrl = container.getAttribute('data-static-url') || '';
                }

                var buildHeadContent = function(innerHtml) {
                    var safeBaseHref = document.location.href.replace(/"/g, '&quot;');
                    var headContent = '<meta charset="utf-8"><title>Transfer Certificate</title><base href="' + safeBaseHref + '">';
                    
                    // Add existing styles from main document
                    document.querySelectorAll('head link[rel="stylesheet"], head style').forEach(function(node) {
                        if (node.tagName && node.tagName.toLowerCase() === 'link' && node.href) {
                            headContent += '<link rel="stylesheet" href="' + node.href + '">';
                        } else if (node.outerHTML) {
                            headContent += node.outerHTML;
                        }
                    });

                    // Also extract styles from the innerHtml if any
                    if (innerHtml) {
                        var tempDiv = document.createElement('div');
                        tempDiv.innerHTML = innerHtml;
                        tempDiv.querySelectorAll('link[rel="stylesheet"], style').forEach(function(node) {
                             headContent += node.outerHTML;
                        });
                    }

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
                    var headContent = buildHeadContent(html);
                    
                    // Remove style/link tags from html body to avoid duplication
                    var cleanHtml = html.replace(/<(link|style)[^>]*>([\s\S]*?)<\/\1>/gi, '')
                                        .replace(/<link[^>]*>/gi, '');

                    doc.open();
                    doc.write('<!doctype html><html><head>' + headContent + '</head><body>' + cleanHtml + '<script>window.addEventListener("load", function() { window.focus(); window.print(); setTimeout(function() { window.close(); }, 250); });<\/script></body></html>');
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
                                throw new Error('Failed to load static TC');
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
    