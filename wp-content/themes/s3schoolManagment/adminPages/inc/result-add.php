<?php global $s3sRedux;

require_once dirname(__DIR__) . '/functions/teacher-access.php';

$teacherAccess = s3s_get_teacher_access_context();
$current_user = wp_get_current_user();
$is_teacher = $teacherAccess['is_teacher'];
$teacher_record = $teacherAccess['teacher'];
$restrictions_enabled = s3s_teacher_restrictions_enabled();

$teacher_assignments = array(
    'subjects' => array(),
    'sections' => array(),
    'classes' => array(),
    'class_teacher_class' => null,
    'class_teacher_section' => null
);
$teacher_has_assigned_classes = false;
$teacher_has_any_assignment = false;

if ($restrictions_enabled && $is_teacher && $teacher_record) {
    $assigned_subjects = json_decode($teacher_record->tecAssignSub, true);
    $assigned_subjects = is_array($assigned_subjects) ? array_filter(array_map('intval', $assigned_subjects)) : array();

    $assigned_sections = json_decode($teacher_record->assignSection, true);
    $assigned_sections = is_array($assigned_sections) ? array_filter($assigned_sections) : array();

    // Determine unique classes linked to assigned subjects
    $assigned_classes = array();
    if (!empty($assigned_subjects)) {
        $subjects_data = $wpdb->get_results(
            "SELECT DISTINCT subjectClass FROM ct_subject WHERE subjectid IN (" . implode(',', $assigned_subjects) . ")"
        );
        if ($subjects_data) {
            $assigned_classes = array_map('intval', array_column($subjects_data, 'subjectClass'));
        }
    }

    // Include class teacher assignment
    if (!empty($teacher_record->teacherOfClass)) {
        $assigned_classes[] = (int) $teacher_record->teacherOfClass;
    }

    $assigned_classes = array_values(array_unique($assigned_classes));

    $teacher_assignments = array(
        'subjects' => $assigned_subjects,
        'sections' => $assigned_sections,
        'classes' => $assigned_classes,
        'class_teacher_class' => !empty($teacher_record->teacherOfClass) ? (int) $teacher_record->teacherOfClass : null,
        'class_teacher_section' => !empty($teacher_record->teacherOfSection) ? (int) $teacher_record->teacherOfSection : null
    );

    $teacher_has_assigned_classes = !empty($assigned_classes);
    $teacher_has_any_assignment = $teacher_has_assigned_classes || !empty($assigned_sections) || !empty($assigned_subjects);
}

if (!$restrictions_enabled) {
    $teacher_assignments = array(
        'subjects' => array(),
        'sections' => array(),
        'classes' => array(),
        'class_teacher_class' => null,
        'class_teacher_section' => null
    );
    $teacher_has_assigned_classes = false;
    $teacher_has_any_assignment = false;
}
?>
<div class="panel panel-info" style="clear:both;">
    <div class="panel-heading">
        <h3>Add Result</h3>
    </div>
    <div class="panel-body">
        <form action="" method="GET" class="form-horizontal">
            <input type="hidden" name="page" value="result">

            <div class="container-fluid">
                <div class="row form-group">
                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                        <label class="control-label">Class</label>
                        <select id='resultClass' class="form-control input-sm" name="class" required>
                            <?php
                            $classQuery = $wpdb->get_results("SELECT classid,className FROM ct_class WHERE classid IN (SELECT examClass FROM ct_exam GROUP BY examClass ORDER BY className ASC)");

                            // Filter classes only when the teacher has explicit assignments
                            if ($is_teacher && $teacher_has_any_assignment) {
                                if (!empty($teacher_assignments['classes'])) {
                                    $allowed_classes = array_map('intval', $teacher_assignments['classes']);
                                    $classQuery = array_filter($classQuery, function ($class) use ($allowed_classes) {
                                        return in_array((int) $class->classid, $allowed_classes, true);
                                    });
                                } else {
                                    $classQuery = array();
                                }
                            }

                            echo "<option value=''>Select Class</option>";

                            foreach ($classQuery as $class) {
                                echo "<option value='" . $class->classid . "'>" . $class->className . "</option>";
                            }

                            if ($is_teacher && $teacher_has_any_assignment && !$teacher_has_assigned_classes) {
                                echo "<option value='' disabled>No classes assigned to you</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                        <label class="control-label">Exam</label>
                        <select id="resultExam" class="form-control input-sm" name="exam" required disabled>
                            <option disabled selected>Select Class First</option>
                        </select>
                    </div>

                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                        <label class="control-label">Section</label>
                        <select id="resultSection" class="form-control input-sm" name="sec" disabled>
                            <option disabled selected>Select Class First</option>
                        </select>
                    </div>

                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                        <label class="control-label">Group</label>
                        <select id="resultGroup" class="form-control input-sm" name="group" disabled>
                            <option value="">Select Class First</option>
                        </select>
                    </div>

                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                        <label class="control-label">Year/Session</label>
                        <select id='resultYear' class="form-control input-sm" name="syear" required disabled>
                            <option disabled selected>Select Class First</option>
                        </select>
                    </div>

                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3">
                        <label class="control-label">Subject</label>
                        <select id='resultSubject' class="form-control input-sm" name="subject" required disabled>
                            <option disabled selected>Select exam First</option>
                        </select>
                    </div>

                    <div class="col-xs-12 col-sm-6 col-md-4 col-lg-3" style="margin-top: 25px;">
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="fa fa-search"></i> Search
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

</div>

<?php
if (isset($_GET['exam'])):
    $exam  = $_GET['exam'];
    $year  = $_GET['syear'];
    $class  = $_GET['class'];
    $sub     = $_GET['subject'];
    $sec     = isset($_GET['sec']) ? $_GET['sec'] : '';
    $group   = isset($_GET['group']) ? $_GET['group'] : ''; // Get selected group

    // Religion subCode mapping
    $religionMap = array(
        'Muslim'    => 111,
        'Hinduism'  => 112,
        'Buddist'   => 113,
        'Christian' => 114
    );

    $subject_info = $wpdb->get_row("SELECT subCode FROM ct_subject WHERE subjectid = $sub");
    $subCode = $subject_info->subCode ?? null;
    $religionFilter = '';
    if ($subCode && in_array($subCode, array_values($religionMap))) {
        $religion = array_search($subCode, $religionMap);
        $religionFilter = " AND stdReligion = '$religion'";
    }

    // Prevent teachers with specific assignments from accessing unauthorized classes
    if ($is_teacher && $teacher_has_any_assignment) {
        $teacher_classes = !empty($teacher_assignments['classes']) ? array_map('intval', $teacher_assignments['classes']) : array();
        if (empty($teacher_classes) || !in_array((int) $class, $teacher_classes, true)) {
            echo "<div class='panel panel-danger'><div class='panel-body'><h4 class='text-danger'>You do not have access to this class.</h4></div></div>";
            return;
        }
    }

    $info = $wpdb->get_results("SELECT examName,className,subjectName,combineMark,connecttedPaper,subPaper,subOptinal,sub4th,subMCQ,subCQ,subPect,subCa FROM ct_subject
        LEFT JOIN ct_exam ON examid = $exam
        LEFT JOIN ct_class ON ct_exam.examClass = ct_class.classid
        WHERE subjectid = $sub");

    $resCombineWith = $info[0]->connecttedPaper;
    $combineMark = $info[0]->combineMark;
    $resSubPaper    = $info[0]->subPaper;
    $subOpt         = $info[0]->subOptinal;
    $sub4th         = $info[0]->sub4th;

    $subMCQ         = $info[0]->subMCQ;
    $subCQ          = $info[0]->subCQ;
    $subPect        = $info[0]->subPect;
    $subCa          = $info[0]->subCa;

    $user = wp_get_current_user();
    $canAdd = true;
    if (!in_array('editor', (array) $user->roles) && !in_array('administrator', (array) $user->roles) && $is_teacher) {
        $assigned_subjects = $teacher_assignments['subjects'];
        $has_subject_access = in_array((int) $sub, $assigned_subjects, true);

        $subject_class = (int) $wpdb->get_var($wpdb->prepare("SELECT subjectClass FROM ct_subject WHERE subjectid = %d", $sub));
        $has_class_teacher_access = ($teacher_assignments['class_teacher_class'] !== null && $teacher_assignments['class_teacher_class'] === $subject_class);

        if ($teacher_has_any_assignment && !$has_subject_access && !$has_class_teacher_access) {
            $canAdd = false;
        }
    }
?>

    <div class="panel panel-info">
        <div class="panel-heading">
            <h3>Result</h3>
        </div>
        <div class="panel-body">
            <?php if ($canAdd) { ?>
                <div class="text-right">
                    <button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>
                </div>
                <form action="" method="POST">

                    <div class="form-group">
                        <input type="hidden" name="resExam" value='<?= $exam; ?>'>
                        <input type="hidden" name="resSubject" value='<?= $sub; ?>'>
                        <input type="hidden" name="resultYear" value='<?= $year; ?>'>
                        <input type="hidden" name="resSubPaper" value='<?= $resSubPaper; ?>'>
                        <input type="hidden" name="resclass" value='<?= $class; ?>'>

                        <input type="hidden" name="resCombineWith" value='<?= $resCombineWith; ?>'>
                        <input type="hidden" name="combineMark" value='<?= $combineMark; ?>'>
                        <input type="hidden" name="resSubPaper" value='<?= $resSubPaper; ?>'>
                        <input type="hidden" name="subCQ" value='<?= $subCQ; ?>'>
                        <input type="hidden" name="subMCQ" value='<?= $subMCQ; ?>'>
                        <input type="hidden" name="subPect" value='<?= $subPect; ?>'>
                        <div id="printArea">
                            <style type="text/css">
                                @page {
                                    size: auto;
                                    margin: 0px;
                                }
                            </style>
                            <link rel="stylesheet" media="print" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" />
                            <div class="printArea" style="margin: 20px;">
                                <h3>
                                    <b>Class:</b> <?= $info[0]->className ?>,
                                    <b>Exam:</b> <?= $info[0]->examName ?>,
                                    <b>Subject:</b> <?= $info[0]->subjectName ?>,
                                    <?php if (!empty($group)) {
                                        $groupInfo = $wpdb->get_var("SELECT groupName FROM ct_group WHERE groupId = $group");
                                        if ($groupInfo) {
                                            echo "<b>Group:</b> " . $groupInfo . ",";
                                        }
                                    } ?>
                                    <b>Year:</b> <?= $_GET['syear'] ?>
                                </h3>
                                <?php if (!empty($group)) {
                                    $groupInfo = $wpdb->get_var("SELECT groupName FROM ct_group WHERE groupId = $group");
                                    if ($groupInfo) {
                                        echo "<b>Group:</b> " . $groupInfo . ",";
                                    }
                                } ?>
                                <b>Year:</b> <?= $_GET['syear'] ?>
                                </h3>

                                <div class="table-responsive">
                                    <table id="resultInputTable" class="table table-bordered ">
                                        <tr>
                                            <th>Name</th>
                                            <th>Roll</th>
                                            <th>Group</th>
                                            <th>Sec</th>
                                            <th>Sub Type</th>
                                            <th <?= ($subCQ == 0) ? 'style="display:none;"' : ''; ?>><?= $s3sRedux['cqtitle'] ?> (<?= $subCQ ?>)</th>
                                            <th <?= ($subMCQ == 0) ? 'style="display:none;"' : ''; ?>><?= $s3sRedux['mcqtitle'] ?> (<?= $subMCQ ?>)</th>
                                            <th <?= ($subPect == 0) ? 'style="display:none;"' : ''; ?>><?= $s3sRedux['prctitle'] ?> (<?= $subPect ?>)</th>
                                            <th <?= ($subCa == 0) ? 'style="display:none;"' : ''; ?>><?= $s3sRedux['catitle'] ?> (<?= $subCa ?>)</th>
                                        </tr>


                                        <?php
                                        if ($subOpt == 0 && $sub4th == 0) {
                                            $stdQuery = "SELECT studentid,infoRoll,stdName,groupName,infoGroup,infoSection,infoOptionals,info4thSub,sectionName,stdReligion FROM ct_student
													LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid
																									AND ct_studentinfo.infoClass = $class AND ct_studentinfo.infoYear = '$year' 
													LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId 
													LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid 
													WHERE studentid NOT IN
														(SELECT resStudentId FROM `ct_result` WHERE resClass = $class AND resultYear = '$year' AND resSubject = $sub AND resExam = $exam)
														AND stdCurntYear = '$year' AND stdCurrentClass = $class" . $religionFilter;

                                            if ($sec != "" && $sec != 'all') {
                                                $stdQuery .= " AND infoSection = $sec";
                                            }
                                            if ($group != "") {
                                                $stdQuery .= " AND infoGroup = $group";
                                            }
                                            if ($class == 41) {
                                                $stdQuery .= " ORDER BY groupName DESC, infoRoll ASC";
                                            } else {
                                                $stdQuery .= " ORDER BY infoRoll ASC";
                                            }
                                        } else {
                                            $stdQuery = "SELECT studentid,infoRoll,stdName,groupName,infoGroup,infoSection,infoOptionals,info4thSub,sectionName,stdReligion FROM ct_student
													LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid
																									AND ct_studentinfo.infoClass = $class AND ct_studentinfo.infoYear = '$year' 
													LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId 
													LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid 
													WHERE studentid NOT IN
														(SELECT resStudentId FROM `ct_result` WHERE resClass = $class AND resultYear = '$year' AND resSubject = $sub AND resExam = $exam)
														AND stdCurntYear = '$year' AND stdCurrentClass = $class" . $religionFilter;
                                            if ($subOpt == 1 && $sub4th == 1) {
                                                $stdQuery .= " AND (infoOptionals LIKE '%\"$sub\"%' OR info4thSub = $sub)";
                                            }
                                            if ($subOpt == 1 && $sub4th == 0) {
                                                $stdQuery .= " AND infoOptionals LIKE '%\"$sub\"%' ";
                                            }
                                            if ($subOpt == 0 && $sub4th == 1) {
                                                $stdQuery .= " AND info4thSub = $sub ";
                                            }
                                            if ($sec != "" && $sec != 'all') {
                                                $stdQuery .= " AND infoSection = $sec";
                                            }
                                            if ($group != "") {
                                                $stdQuery .= " AND infoGroup = $group";
                                            }
                                            if ($class == 41) {
                                                $stdQuery .= " ORDER BY groupName DESC, infoRoll ASC";
                                            } else {
                                                $stdQuery .= " ORDER BY infoRoll ASC";
                                            }
                                        }

                                        $stdQuery = $wpdb->get_results($stdQuery);

                                        foreach ($stdQuery as $student) {
                                            if (!empty($student->infoOptionals)) {
                                                $subOpt    = (in_array($sub, json_decode($student->infoOptionals))) ? 1 : 0;
                                            } else {
                                                $subOpt = 0;
                                            }

                                            $fourth = '';
                                            if (!empty($student->info4thSub)) {
                                                $tmp = json_decode($student->info4thSub, true);
                                                if (is_array($tmp)) {
                                                    $fourth = $tmp[0];
                                                } elseif (!empty($tmp)) {
                                                    $fourth = (string)$tmp;
                                                }
                                            }

                                            $std4thSub = ($sub == $fourth) ? 1 : 0;
                                        ?>
                                            <input type="hidden" name="stdids[]" value='<?= $student->studentid ?>'>
                                            <input type="hidden" name="roll[<?= $student->studentid ?>]" value='<?= $student->infoRoll ?>'>
                                            <input type="hidden" name="group[<?= $student->studentid ?>]" value='<?= $student->infoGroup ?>'>
                                            <input type="hidden" name="section[<?= $student->studentid ?>]" value='<?= $student->infoSection ?>'>
                                            <input type="hidden" name="optional[<?= $student->studentid ?>]" value='<?= $subOpt ?>'>
                                            <input type="hidden" name="sub4th[<?= $student->studentid ?>]" value='<?= $std4thSub ?>'>

                                            <tr>
                                                <td><?= $student->stdName ?></td>
                                                <td><?= $student->infoRoll ?></td>
                                                <td><?= $student->groupName ?></td>
                                                <td><?= $student->sectionName ?></td>
                                                <td><?php if ($std4thSub == 1) {
                                                        echo '4th Sub';
                                                    } elseif ($subOpt == 1) {
                                                        echo "Optional";
                                                    }  ?></td>
                                                <!-- if($std4thSub == 1){ echo '4th Sub'; }elseif($subOpt == 1){ echo "Optional"; } -->
                                                <td style="<?= ($subCQ == 0) ? 'display:none;' : ''; ?>">
                                                    <input style="width: 100px" class="resultInput form-control" type="text" data-max="<?= $subCQ ?>" name="cq[<?= $student->studentid ?>]">
                                                </td>
                                                <td style="<?= ($subMCQ == 0) ? 'display:none;' : ''; ?>">
                                                    <input style="width: 100px" class="resultInput form-control" type="text" data-max="<?= $subMCQ ?>" name="mcq[<?= $student->studentid ?>]">
                                                </td>
                                                <td style="<?= ($subPect == 0) ? 'display:none;' : ''; ?>">
                                                    <input style="width: 100px" class="resultInput form-control" type="text" data-max="<?= $subPect ?>" name="prac[<?= $student->studentid ?>]">
                                                </td>
                                                <td style="<?= ($subCa == 0) ? 'display:none;' : ''; ?>">
                                                    <input style="width: 100px" class="resultInput form-control" type="text" data-max="<?= $subCa ?>" name="ca[<?= $student->studentid ?>]">
                                                </td>
                                            </tr>
                                        <?php
                                        }
                                        ?>
                                    </table>


                                    <?php if (!$stdQuery) { ?>
                                        <h3 class="text-center text-info">No Student Found for add the result</h3>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($stdQuery) { ?>
                        <div class="form-group">
                            <input name="addResult" class="form-control btn-success resultSubmit" type="submit" value="Add Result">
                        </div>
                    <?php } ?>
                </form>
            <?php } else {
                echo "<h3 class='text-center text-danger'>You are not allowed to add result for this subject.</h3>";
            } ?>
        </div>
    </div>

<?php
endif; ?>


<script type="text/javascript">
    function print(divId) {
        var printContents = document.getElementById(divId).innerHTML;
        w = window.open();
        w.document.write(printContents);
        w.document.write('<scr' + 'ipt type="text/javascript">' + 'window.onload = function() { window.print(); window.close(); };' + '</sc' + 'ript>');
        w.document.close();
        w.focus();
        return true;
    }
</script>