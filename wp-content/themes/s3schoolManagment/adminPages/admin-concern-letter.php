<?php
/*
** Template Name: Admin Concern Letter
*/
global $wpdb;
global $s3sRedux;
require_once __DIR__ . '/functions/html-utils.php';
// fetch instLogo, principalSignature and Concern reference from db
$query = "SELECT option_name, option_value FROM sm_options WHERE option_name IN ('instLogo', 'principalSign', 'concern_ref', 'institute_name', 'institute_address', 'estd_year', 'institute_eiin', 'inst_head_name', 'inst_head_title', 'testimonial_prepared_by', 'testimonial_pad', 'concern_pad')";
$results = $wpdb->get_results($query);
foreach ($results as $row) {
    $optionValue = isset($row->option_value) ? maybe_unserialize($row->option_value) : '';

    if (in_array($row->option_name, ['instLogo', 'principalSign', 'testimonial_pad', 'concern_pad'], true)) {
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
    } elseif ($row->option_name === 'concern_ref') {
        $s3sRedux['concern_ref'] = max(1, (int) $optionValue);
    } else {
        $s3sRedux[$row->option_name] = $optionValue;
    }
}

if (!isset($s3sRedux['concern_ref'])) {
    $s3sRedux['concern_ref'] = 1;
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
                                <h3 class="panel-title" style="margin: 0; font-weight: 600; letter-spacing: 0.4px;">Concern Letter</h3>
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
                                            <input type="hidden" name="page" value="concern-letter">

                                            <div class="form-group">
                                                <label>Class</label>
                                                <select class="resultClass form-control" name="class" required>
                                                    <?php
                                                    $selectedClass = isset($_GET['class']) ? (int) $_GET['class'] : '';
                                                    $classQuery = $wpdb->get_results("SELECT classid,className FROM ct_class WHERE classid IN (SELECT examClass FROM ct_exam GROUP BY examClass ORDER BY className ASC)");
                                                    echo "<option value=''>Select Class</option>";

                                                    foreach ($classQuery as $class) {
                                                        $sel = ($selectedClass === (int) $class->classid) ? ' selected' : '';
                                                        echo "<option value='" . $class->classid . "'{$sel}>" . $class->className . "</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>

                                            <div class="form-group ">
                                                <label>Exam</label>
                                                <select class="resultExam form-control" name="exam" required <?= $selectedClass === '' ? 'disabled' : '' ?>>
                                                    <?php if ($selectedClass !== ''): ?>
                                                    <?php
                                                    $examsDD = $wpdb->get_results("SELECT examid,examName FROM ct_exam WHERE examClass = '$selectedClass'");
                                                    $selectedExam = isset($_GET['exam']) ? (int) $_GET['exam'] : '';
                                                    echo "<option value=''>Select An Exam</option>";
                                                    foreach ($examsDD as $examDD) {
                                                        $sel = ($selectedExam === (int) $examDD->examid) ? ' selected' : '';
                                                        echo "<option value='{$examDD->examid}'{$sel}>{$examDD->examName}</option>";
                                                    }
                                                    ?>
                                                    <?php else: ?>
                                                    <option disabled selected>Select Class</option>
                                                    <?php endif; ?>
                                                </select>
                                            </div>

                                            <div class="form-group ">
                                                <label>Section</label>
                                                <select class="resultSection form-control" name="section" required <?= $selectedClass === '' ? 'disabled' : '' ?>>
                                                    <?php if ($selectedClass !== ''): ?>
                                                    <?php
                                                    $selectedSection = isset($_GET['section']) ? (int) $_GET['section'] : '';
                                                    $sectionsDD = $wpdb->get_results("SELECT sectionid,sectionName FROM ct_section WHERE forClass = '$selectedClass' ORDER BY sectionName");
                                                    echo "<option value=''>Section</option>";
                                                    foreach ($sectionsDD as $secDD) {
                                                        $sel = ($selectedSection === (int) $secDD->sectionid) ? ' selected' : '';
                                                        echo "<option value='{$secDD->sectionid}'{$sel}>{$secDD->sectionName}</option>";
                                                    }
                                                    ?>
                                                    <?php else: ?>
                                                    <option disabled selected>Select Class</option>
                                                    <?php endif; ?>
                                                </select>
                                            </div>

                                            <div class="form-group">
                                                <label>Year</label>
                                                <select class="resultYear form-control" name="syear" required <?= $selectedClass === '' ? 'disabled' : '' ?>>
                                                    <?php if ($selectedClass !== ''): ?>
                                                    <?php
                                                    $selectedYear = isset($_GET['syear']) ? esc_attr($_GET['syear']) : '';
                                                    $yearsDD = $wpdb->get_results("SELECT DISTINCT infoYear FROM ct_studentinfo WHERE infoClass = '$selectedClass' ORDER BY infoYear ASC");
                                                    echo "<option value=''>Year</option>";
                                                    foreach ($yearsDD as $yrDD) {
                                                        $sel = ($selectedYear === $yrDD->infoYear) ? ' selected' : '';
                                                        echo "<option value='{$yrDD->infoYear}'{$sel}>{$yrDD->infoYear}</option>";
                                                    }
                                                    ?>
                                                    <?php else: ?>
                                                    <option disabled selected>Select Class</option>
                                                    <?php endif; ?>
                                                </select>
                                            </div>

                                            <?php
                                            $hasPadImage = !empty($s3sRedux['concern_pad']);
                                            $padDefault = !isset($_GET['pad_type']) && $hasPadImage;
                                            $currentPadType = isset($_GET['pad_type']) ? $_GET['pad_type'] : ($padDefault ? 'pad' : 'default');
                                            ?>
                                            <div class="form-group">
                                                <label>Format</label>
                                                <select class="form-control" name="pad_type">
                                                    <option value="default" <?= $currentPadType === 'default' ? 'selected' : '' ?>>Default</option>
                                                    <?php if ($hasPadImage): ?>
                                                    <option value="pad" <?= $currentPadType === 'pad' ? 'selected' : '' ?>>Pad</option>
                                                    <?php endif; ?>
                                                </select>
                                            </div>

                                            <div class="form-group" id="idRoll">
                                                <input class="form-control" type="text" name="roll" placeholder="Roll" value="<?= isset($_GET['roll']) ? esc_attr($_GET['roll']) : '' ?>" style="width: 110px">
                                            </div>
                                            <div class="form-group">
                                                <label>Serial No Start At</label>
                                                <input class="form-control" type="number" name="serial_start" placeholder="Start from" value="<?= isset($_GET['serial_start']) ? esc_attr($_GET['serial_start']) : '' ?>" style="width: 130px">
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
                                        <button onclick="printConcernLetter('printArea')" class="pull-right btn btn-primary">Print</button>
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
                                            $institute_code = isset($s3sRedux['institute_code']) ? $s3sRedux['institute_code'] : $wpdb->get_var("SELECT option_value FROM sm_options WHERE option_name = 'institute_code'");
                                            $testimonial_prepared_by = isset($s3sRedux['testimonial_prepared_by']) ? $s3sRedux['testimonial_prepared_by'] : $wpdb->get_var("SELECT option_value FROM sm_options WHERE option_name = 'testimonial_prepared_by'");
                                            
                                            // Handle Pad Selection
                                            $pad_type = $_GET['pad_type'] ?? 'default';
                                            $bg_image_url = '';
                                            if ($pad_type === 'pad' && !empty($s3sRedux['concern_pad'])) {
                                                $bg_image_url = $s3sRedux['concern_pad'];
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
                                                $refSerialStart = isset($s3sRedux['concern_ref']) ? (int) $s3sRedux['concern_ref'] : 1;
                                                if (isset($_GET['serial_start']) && $_GET['serial_start'] !== '') {
                                                    $refSerialStart = max(1, (int) $_GET['serial_start']);
                                                }
                                                if ($refSerialStart < 1) {
                                                    $refSerialStart = 1;
                                                }
                                                $refSerialCounter = $refSerialStart;
                                                $generatedLetters = 0;
                                                $staticFilePath = '';
                                                $staticLetterUrl = '';
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

                                                        .letter-card {
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

                                                    .letter-card {
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

                                                    .letter-card-content {
                                                        padding: 50px 60px;
                                                    }

                                                    .letter-card .header {
                                                        text-align: center;
                                                        margin-bottom: 20px;
                                                    }

                                                    .letter-card .header h1 {
                                                        font-size: 20pt;
                                                        color: #000;
                                                        margin: 0 0 5px 0;
                                                        font-weight: bold;
                                                        text-transform: uppercase;
                                                        letter-spacing: 1px;
                                                    }

                                                    .letter-card .header .sub {
                                                        font-size: 13pt;
                                                        color: #000;
                                                        font-weight: normal;
                                                        margin: 5px 0;
                                                        line-height: 1.4;
                                                    }

                                                    .letter-card .content {
                                                        font-size: 17px;
                                                        line-height: 1.8;
                                                        color: #000;
                                                        margin-top: 40px;
                                                        margin-bottom: 20px;
                                                    }

                                                    .letter-card .content p {
                                                        margin-bottom: 15px;
                                                        text-align: justify;
                                                        text-indent: 30px;
                                                    }

                                                    .letter-card .ref-date {
                                                        display: flex;
                                                        justify-content: space-between;
                                                        margin-bottom: 15px;
                                                        margin-top: 20px;
                                                    }

                                                    .letter-card .doc-footer {
                                                        margin-top: 140px;
                                                        color: #000;
                                                        display: flex;
                                                        justify-content: space-between;
                                                        align-items: flex-end;
                                                    }

                                                    .letter-card .doc-footer .left {
                                                        width: 40%;
                                                        font-size: 15px;
                                                    }

                                                    .letter-card .doc-footer .right-sign {
                                                        text-align: center;
                                                        width: 40%;
                                                        font-size: 15px;
                                                    }

                                                    .letter-card .doc-footer .right-sign img {
                                                        max-height: 60px;
                                                        max-width: 150px;
                                                        margin: 0 auto 5px;
                                                        display: block;
                                                    }

                                                    .letter-card .doc-footer .right-sign p {
                                                        margin: 2px 0;
                                                        line-height: 1.3;
                                                    }

                                                    .line {
                                                        border-bottom: 1px dotted #000;
                                                        padding-left: 4px;
                                                        padding-right: 4px;
                                                        text-align: center;
                                                    }

                                                    .title {
                                                        font-size: 18pt;
                                                        font-weight: bold;
                                                        text-decoration: underline;
                                                        margin: 40px 0 20px;
                                                        text-align: center;
                                                        text-transform: uppercase;
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
                                                    $generatedLetters++;
                                                    $genderTitle = ($value->stdGender == 0) ? 'daughter' : 'son';
                                                    $subjectPronoun = ($value->stdGender == 0) ? 'She' : 'He';
                                                    $possessivePronoun = ($value->stdGender == 0) ? 'Her' : 'His';
                                                    $studentId = $value->studentId ?? '';
                                            ?>
                                                    <div class="letter-card">
                                                        <?php if ($pad_type == 'pad' && !empty($bg_image_url)) : ?>
                                                        <div style="width:100%;height:100%;">
                                                            <img style="width:100%;height:100%;" src="<?= esc_url($bg_image_url) ?>" alt="Background" />
                                                        </div>
                                                        <?php endif; ?>
                                                        <div class="letter-card-content" style="<?= ($pad_type == 'pad' && !empty($bg_image_url)) ? 'position:absolute;inset:0;z-index:20;margin-top:200px;' : '' ?>">
                                                            <?php if ($pad_type != 'pad') : ?>
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
                                                            <?php endif; ?>

                                                            <div class="title">To Whom It May Concern</div>
                                                            <div class="ref-date">
                                                                <div>Ref No: <span class="line"><?= esc_html($currentRefValue) ?></span></div>
                                                                <div>Date: <span class="line"><?= esc_html(date('d-m-Y')) ?></span></div>
                                                            </div>
                                                           <div class="content">
                                                                <p>
                                                                    This is to certify that <span class="line"><?= esc_html($value->stdName) ?></span>, 
                                                                    <?= $genderTitle ?> of <span class="line"><?= esc_html($value->stdFather) ?></span> 
                                                                    and <span class="line"><?= esc_html($value->stdMother) ?></span>, is a genuine student of this institution, 
                                                                    currently enrolled in Class <span class="line"><?= esc_html($value->className) ?></span>
                                                                    <?= !empty($value->sectionName) ? " Section <span class='line'>" . esc_html($value->sectionName) . "</span>" : '' ?>
                                                                    <?= !empty($value->groupName) ? " Group <span class='line'>" . esc_html($value->groupName) . "</span>" : '' ?>, 
                                                                    Roll No <span class="line"><?= esc_html($value->infoRoll) ?></span>, 
                                                                    Admission ID <span class="line"><?= esc_html($studentId) ?></span>, 
                                                                    for the Academic Session <span class="line"><?= esc_html($value->infoYear) ?></span>.
                                                                </p>
                                                                <p style="text-indent: 0;">
                                                                    According to the admission register, <?= strtolower($possessivePronoun) ?> 
                                                                    date of birth is recorded as <span class="line"><?= formatBirthDate($value->stdBrith) ?></span>.
                                                                </p>
                                                                <p style="text-indent: 0;">
                                                                    To the best of my knowledge, <?= strtolower($subjectPronoun) ?> bears good moral character and 
                                                                    <?= strtolower($subjectPronoun) ?> has not been involved in any activity detrimental to the state or 
                                                                    contrary to the discipline of this institution.
                                                                </p>
                                                                <p style="text-indent: 0;">
                                                                    We sincerely wish <?= strtolower($possessivePronoun) ?> continued success in all future endeavors.
                                                                </p>
                                                                </div>
                                                            <div class="doc-footer">
                                                                <div class="left">
                                                                    <!-- <p><strong>Prepared by:</strong> <span class="line"><?= esc_html($s3sRedux['testimonial_prepared_by']) ?></span></p> -->
                                                                </div>
                                                                <div class="right-sign">
                                                                    <?php if (!empty($s3sRedux['principalSign'])): ?>
                                                                        <img src="<?= esc_url($s3sRedux['principalSign']) ?>" alt="Signature" style="max-height: 60px; max-width: 100px; display: block; margin: 0 auto 5px;">
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

                                                $letterHtml = ob_get_clean();
                                                if ($letterHtml === false) {
                                                    $letterHtml = '';
                                                }
                                                $letterHtml = trim($letterHtml);

                                                if ($generatedLetters > 0 && isset($_GET['creatId']) && ! $useManualRef) {
                                                    $nextRefValue = $refSerialCounter;
                                                    $storedValue = (string) $nextRefValue;
                                                    $updated = $wpdb->update(
                                                        'sm_options',
                                                        ['option_value' => $storedValue],
                                                        ['option_name' => 'concern_ref'],
                                                        ['%s'],
                                                        ['%s']
                                                    );

                                                    if ($updated === false || $updated === 0) {
                                                        $wpdb->insert(
                                                            'sm_options',
                                                            ['option_name' => 'concern_ref', 'option_value' => $storedValue],
                                                            ['%s', '%s']
                                                        );
                                                    }

                                                    $s3sRedux['concern_ref'] = $nextRefValue;
                                                }

                                                if ($letterHtml !== '') {
                                                    // Persist a static snapshot so printing relies on a cached HTML file.
                                                    $snapshotMeta = [
                                                        'concern-letter',
                                                        ($class !== '' ? 'class-' . $class : ''),
                                                        ($section !== '' ? 'section-' . $section : ''),
                                                        ($year !== '' ? 'year-' . $year : ''),
                                                        ($roll !== '' ? 'roll-' . $roll : '')
                                                    ];
                                                    $snapshotArgs = [
                                                        'subdir' => 'concern-letter',
                                                        'prefix' => 'concern'
                                                    ];
                                                    $snapshot = s3s_store_html_snapshot($letterHtml, $snapshotMeta, $snapshotArgs);
                                                    if ($snapshot['path'] !== '') {
                                                        $staticFilePath = $snapshot['path'];
                                                    }
                                                    if ($snapshot['url'] !== '') {
                                                        $staticLetterUrl = $snapshot['url'];
                                                    }
                                                    if ($snapshot['error'] !== '' && $staticWriteError === '') {
                                                        $staticWriteError = $snapshot['error'];
                                                    }
                                                }

                                                if ($staticFilePath && file_exists($staticFilePath)) {
                                                    include $staticFilePath;
                                                } else {
                                                    echo $letterHtml;
                                                }

                                                if ($staticWriteError !== '') {
                                                    echo '<div class="alert alert-warning" role="alert">' . esc_html($staticWriteError) . '</div>';
                                                }

                                                if ($staticLetterUrl !== '') {
                                                    ?>
                                                    <script type="text/javascript">
                                                        (function() {
                                                            var area = document.getElementById('printArea');
                                                            if (area) {
                                                                area.setAttribute('data-static-url', '<?= esc_js($staticLetterUrl) ?>');
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

        function printConcernLetter(divId) {
            var container = document.getElementById(divId);
            var staticUrl = '';
            if (container && typeof container.getAttribute === 'function') {
                staticUrl = container.getAttribute('data-static-url') || '';
            }

            var buildHeadContent = function(innerHtml) {
                var safeBaseHref = document.location.href.replace(/"/g, '&quot;');
                var headContent = '<meta charset="utf-8"><title>Concern Letter</title><base href="' + safeBaseHref + '">';
                
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
                            throw new Error('Failed to load static Letter');
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