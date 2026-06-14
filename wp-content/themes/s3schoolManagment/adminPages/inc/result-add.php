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

<style>
    .compact-filter-form {
        background: #f9f9f9;
        padding: 15px;
        border-radius: 4px;
    }
    
    .compact-filter-form .filter-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: flex-end;
    }
    
    .compact-filter-form .filter-field {
        flex: 1 1 auto;
        min-width: 140px;
        max-width: 200px;
    }
    
    .compact-filter-form .filter-field.row-break {
        flex-basis: 100%;
        width: 100%;
        height: 0;
        min-width: 100%;
        max-width: 100%;
        margin: 0;
        padding: 0;
        border: none;
        overflow: hidden;
    }
    
    .compact-filter-form .filter-field label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 3px;
        color: #555;
    }
    
    .compact-filter-form .filter-field select,
    .compact-filter-form .filter-field input {
        width: 100%;
        padding: 6px 8px;
        font-size: 13px;
        border: 1px solid #ddd;
        border-radius: 3px;
        height: 32px;
    }
    
    .compact-filter-form .filter-field select:focus,
    .compact-filter-form .filter-field input:focus {
        border-color: #5bc0de;
        outline: none;
        box-shadow: 0 0 0 2px rgba(91, 192, 222, 0.1);
    }
    
    .compact-filter-form .filter-btn {
        flex: 0 0 auto;
        min-width: 100px;
    }
    
    .compact-filter-form .filter-btn button {
        width: 100%;
        height: 32px;
        padding: 6px 12px;
        font-size: 13px;
        line-height: 1.2;
    }
    
    @media (max-width: 768px) {
        .compact-filter-form .filter-field {
            flex: 1 1 calc(50% - 5px);
            max-width: none;
        }
        
        .compact-filter-form .filter-field.row-break {
            display: none;
        }
        
        .compact-filter-form .filter-btn {
            flex: 1 1 100%;
            min-width: 100%;
        }
    }
    
    @media (max-width: 480px) {
        .compact-filter-form .filter-field {
            flex: 1 1 100%;
        }
    }
</style>

<div class="panel panel-info" style="clear:both;">
    <div class="panel-heading">
        <h3>Add Result</h3>
    </div>
    <div class="panel-body">
        <form action="" method="GET" class="compact-filter-form">
            <input type="hidden" name="page" value="result">

            <div class="filter-row">
                <div class="filter-field">
                    <label>Class *</label>
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

                <div class="filter-field">
                    <label>Section</label>
                    <select id="resultSection" class="form-control input-sm" name="sec" disabled>
                        <option disabled selected>Select Class First</option>
                    </select>
                </div>

                <div class="filter-field">
                    <label>Group</label>
                    <select id="resultGroup" class="form-control" name="grou">
						<option value="">Select Group</option>
						<?php
						$groups = $wpdb->get_results("SELECT * FROM ct_group");
						foreach ($groups as $groups) {
							$selected = ($edit->infoGroup == $groups->groupId) ? 'selected' : '';
						?>
							<option value='<?= $groups->groupId ?>' <?= $selected ?>>
								<?= $groups->groupName ?>
							</option>
						<?php
						}
						?>
					</select>
                </div>

                <div class="filter-field">
                    <label>Religion</label>
                    <select class="form-control input-sm" name="religion">
                        <option value="">All Religions</option>
                        <option value="Muslim">Muslim</option>
                        <option value="Hinduism">Hinduism</option>
                        <option value="Buddist">Buddist</option>
                        <option value="Christian">Christian</option>
                    </select>
                </div>

                <div class="filter-field">
                    <label>Gender</label>
                    <select class="form-control input-sm" name="gender">
                        <option value="">All Genders</option>
                        <option value="1">Male</option>
                        <option value="0">Female</option>
                        <option value="2">Other</option>
                    </select>
                </div>

                <!-- Row Break for Desktop -->
                <div class="filter-field row-break"></div>

                <div class="filter-field">
                    <label>Exam *</label>
                    <select id="resultExam" class="form-control input-sm" name="exam" required disabled>
                        <option disabled selected>Select Class First</option>
                    </select>
                </div>

                <div class="filter-field">
                    <label>Year/Session *</label>
                    <select id='resultYear' class="form-control input-sm" name="syear" required disabled>
                        <option disabled selected>Select Class First</option>
                    </select>
                </div>

                <div class="filter-field">
                    <label>Subject *</label>
                    <select id='resultSubject' class="form-control input-sm" name="subject" required disabled>
                        <option disabled selected>Select exam First</option>
                    </select>
                </div>

                <div class="filter-field filter-btn">
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-search"></i> Search
                    </button>
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

    // ReligionId mapping
    $religionMap = array(
        'Muslim'    => 1,
        'Hinduism'  => 2,
        'Buddist'   => 3,
        'Christian' => 4
    );

    $subject_info = $wpdb->get_row("SELECT religionId FROM ct_subject WHERE subjectid = $sub");
    $subCode = $subject_info->religionId ?? null;
    $religionFilter = '';
    if (isset($_GET['religion']) && !empty($_GET['religion'])) {
      $religion = $_GET['religion'];
      $religionFilter = " AND stdReligion = '$religion'";
    } else if ($subCode && in_array($subCode, array_values($religionMap))) {
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
                                                $stdQuery .= " AND (infoOptionals LIKE '%\"$sub\"%' OR info4thSub = $sub OR info4thSub LIKE '%\"$sub\"%') ";
                                            }
                                            if ($subOpt == 1 && $sub4th == 0) {
                                                $stdQuery .= " AND infoOptionals LIKE '%\"$sub\"%' ";
                                            }
                                            if ($subOpt == 0 && $sub4th == 1) {
                                                $stdQuery .= " AND (info4thSub = $sub OR info4thSub LIKE '%\"$sub\"%') ";
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

<?php
// ==========================================
    // HANDLE AJAX ACTIONS LOCALLY
    // ==========================================
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

            if ($restrictions_enabled && $is_teacher) {
                $allowed_sections = $teacher_assignments['sections'];
                
                // Add class teacher section if applicable
                if ($teacher_assignments['class_teacher_class'] == $class && !empty($teacher_assignments['class_teacher_section'])) {
                    $allowed_sections[] = $teacher_assignments['class_teacher_section'];
                }

                if (!empty($allowed_sections)) {
                    $has_all = in_array('all', $allowed_sections);
                    if (!$has_all) {
                         $sections_query .= " AND sectionid IN (" . implode(',', array_map('intval', $allowed_sections)) . ")";
                    }
                } elseif (!$teacher_has_assigned_classes) { 
                     // Logic gap: if teacher has no section assigned but has class assigned? 
                     // Assuming sections list follows restrictions
                }
            }
            
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
            
             // Apply teacher restrictions if enabled
            if ($restrictions_enabled && $is_teacher && !empty($teacher_assignments['subjects'])) {
                 $groups_query .= " AND ct_studentinfo.infoGroup IN (
                    SELECT DISTINCT forGroup 
                    FROM ct_subject 
                    WHERE subjectid IN (" . implode(',', $teacher_assignments['subjects']) . ") 
                    AND subjectClass = '$class'
                    AND forGroup != 'all'
                )";
            }

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

            // Teacher Restrictions
            if ($restrictions_enabled && $is_teacher) {
                 $exam_class = $wpdb->get_var($wpdb->prepare("SELECT examClass FROM ct_exam WHERE examid = %d", $exam));
                 $is_class_teacher = ($teacher_assignments['class_teacher_class'] == $exam_class);
                 
                 // If not class teacher, restrict subjects
                 if (!$is_class_teacher && !empty($teacher_assignments['subjects'])) {
                     $subs = array_intersect($subs, $teacher_assignments['subjects']);
                 }
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
?>


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

    (function($) {
        // Use current page as AJAX URL for standalone processing
        var ajaxUrl = ''; 
        // Note: Using window.location.href or just '' is fine. 
        // Logic check: $_SERVER['REQUEST_METHOD'] === 'POST' rule at top of this file triggers the response.

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

        // Interactive validation for result inputs (Client-side only, ported from result.php for completeness)
        // Works on .resultInput class which remains unchanged in the HTML loop
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