<?php

/**
 * Combined Export REST APIs
 * Consolidates Student, Subject, Teacher, and User export endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', 's3s_register_export_routes');

function s3s_register_export_routes()
{
    // Students
    register_rest_route('v1', 'students', array(
        'methods'  => 'GET',
        'callback' => 's3s_get_all_students_legacy',
        'permission_callback' => '__return_true',
    ));

    register_rest_route('v1', 'students', array(
        'methods'  => 'POST',
        'callback' => 's3s_insert_student_export',
        'permission_callback' => '__return_true',
    ));

    // Upload Image
    register_rest_route('v1', 'upload-image', array(
        'methods'  => 'POST',
        'callback' => 's3s_upload_image_export',
        'permission_callback' => '__return_true',
    ));

    register_rest_route('v1', 'student/enrollments', array(
        'methods'  => 'GET',
        'callback' => 's3s_get_student_enrollments',
        'permission_callback' => '__return_true',
    ));

    // Subjects
    register_rest_route('v1', 'subjects', array(
        'methods'  => 'GET',
        'callback' => 's3s_get_subjects_export',
        'permission_callback' => '__return_true',
    ));

    // Teachers
    register_rest_route('v1', 'teachers', array(
        'methods'  => 'GET',
        'callback' => 's3s_get_teachers_export',
        'permission_callback' => '__return_true',
    ));

    // Exams
    register_rest_route('v1', 'exams', array(
        'methods'  => 'GET',
        'callback' => 's3s_get_exams_export',
        'permission_callback' => '__return_true',
    ));

    register_rest_route('v1', 'exams/schedules', array(
        'methods'  => 'GET',
        'callback' => 's3s_get_exam_schedules_export',
        'permission_callback' => '__return_true',
    ));

    // Results
    register_rest_route('v1', 'exams/results', array(
        'methods'  => 'GET',
        'callback' => 's3s_get_exam_results_export',
        'permission_callback' => '__return_true',
        'args' => array(
            'page' => array(
                'default' => 1,
                'sanitize_callback' => 'absint',
            ),
            'per_page' => array(
                'default' => 1000,
                'sanitize_callback' => 'absint',
            ),
        ),
    ));

    // Slider Images
    register_rest_route('v1', 'slider-images', array(
        'methods'  => 'GET',
        'callback' => 's3s_get_slider_images_export',
        'permission_callback' => '__return_true',
    ));
    // Committees (Nested)
    register_rest_route('v1', 'committees', array(
        'methods'  => 'GET',
        'callback' => 's3s_get_committees_export',
        'permission_callback' => '__return_true',
    ));

    // Governing Body
    register_rest_route('v1', 'governing-body', array(
        'methods'  => 'GET',
        'callback' => 's3s_get_governing_body_export',
        'permission_callback' => '__return_true',
    ));

    // Theme Options
    register_rest_route('v1', 'options', array(
        'methods'  => 'GET',
        'callback' => 's3s_get_theme_options_export',
        'permission_callback' => '__return_true',
    ));
}

/**
 * CALLBACKS: STUDENTS
 */

function s3s_get_all_students_legacy(WP_REST_Request $request)
{
    global $wpdb;

    $results = $wpdb->get_results(
        "SELECT studentid, stdName, stdNameBangla, stdGender, stdBldGrp, stdImg, stdFather, fatherLate, stdFatherProf, stdMother, motherLate, stdMotherProf, stdParentIncome, stdlocalGuardian, stdGuardianNID, stdPhone, stdPermanent, stdPresent, stdBrith, stdNationality, stdReligion, stdAdmitClass, stdAdmitYear, stdTcNumber, sscRoll, sscReg, stdPrevSchool, stdGPA, stdIntellectual, stdScholarsClass, stdScholarsYear, stdScholarsMemo, birth_reg_no, c.className as stdAdmitClass
         FROM ct_student s
         LEFT JOIN ct_class c ON s.stdAdmitClass = c.classid",
        ARRAY_A
    );

    if (empty($results)) {
        return array('success' => true, 'data' => []);
    }

    $gender_map = [0 => 'female', 1 => 'male', 2 => 'other'];
    foreach ($results as &$student) {
        $gender_val = (int)$student['stdGender'];
        $student['stdGender'] = isset($gender_map[$gender_val]) ? $gender_map[$gender_val] : 'other';
    }

    return array('success' => true, 'data' => $results);
}

/**
 * Insert new student(s) via POST API (Supports Single or Bulk)
 */
function s3s_insert_student_export(WP_REST_Request $request)
{
    global $wpdb;

    $params = $request->get_params();

    // Check if it's a bulk request (array of students) or single
    $is_bulk = isset($params['students']) && is_array($params['students']);
    $students_to_process = $is_bulk ? $params['students'] : [$params];

    $results = [
        'success_count' => 0,
        'failed_count'  => 0,
        'errors'        => [],
        'data'          => []
    ];

    foreach ($students_to_process as $index => $student_params) {
        // Required fields check
        if (empty($student_params['stdName'])) {
            $results['failed_count']++;
            $results['errors'][] = "Index $index: Student Name is required";
            continue;
        }

        // Prepare data for ct_student
        $student_data = array(
            'stdName'           => sanitize_text_field($student_params['stdName']),
            'stdNameBangla'     => isset($student_params['stdNameBangla']) ? sanitize_text_field($student_params['stdNameBangla']) : '',
            'stdImg'            => isset($student_params['stdImg']) ? esc_url_raw($student_params['stdImg']) : '',
            'stdFather'         => isset($student_params['stdFather']) ? sanitize_text_field($student_params['stdFather']) : '',
            'fatherLate'        => !empty($student_params['fatherLate']) ? 1 : 0,
            'stdFatherProf'     => isset($student_params['stdFatherProf']) ? sanitize_text_field($student_params['stdFatherProf']) : '',
            'stdFatherNID'      => isset($student_params['stdFatherNID']) ? sanitize_text_field($student_params['stdFatherNID']) : '',
            'stdMother'         => isset($student_params['stdMother']) ? sanitize_text_field($student_params['stdMother']) : '',
            'motherLate'        => !empty($student_params['motherLate']) ? 1 : 0,
            'stdMotherProf'     => isset($student_params['stdMotherProf']) ? sanitize_text_field($student_params['stdMotherProf']) : '',
            'stdMotherNID'      => isset($student_params['stdMotherNID']) ? sanitize_text_field($student_params['stdMotherNID']) : '',
            'stdParentIncome'   => isset($student_params['stdParentIncome']) ? sanitize_text_field($student_params['stdParentIncome']) : '',
            'stdGuardianNID'    => isset($student_params['stdGuardianNID']) ? sanitize_text_field($student_params['stdGuardianNID']) : (isset($student_params['stdFatherNID']) ? sanitize_text_field($student_params['stdFatherNID']) : '0'),
            'stdPhone'          => isset($student_params['stdPhone']) ? sanitize_text_field($student_params['stdPhone']) : '',
            'stdEmergencyPhone' => isset($student_params['stdEmergencyPhone']) ? sanitize_text_field($student_params['stdEmergencyPhone']) : '',
            'stdEmail'          => isset($student_params['stdEmail']) ? sanitize_email($student_params['stdEmail']) : '',
            'stdPermanent'      => isset($student_params['stdPermanent']) ? sanitize_textarea_field($student_params['stdPermanent']) : '',
            'stdAdmitYear'      => isset($student_params['stdCurntYear']) ? sanitize_text_field($student_params['stdCurntYear']) : date('Y'),
            'stdCurntYear'      => isset($student_params['stdCurntYear']) ? sanitize_text_field($student_params['stdCurntYear']) : date('Y'),
            'stdAdmitClass'     => isset($student_params['stdAdmitClass']) ? sanitize_text_field($student_params['stdAdmitClass']) : '',
            'stdCurrentClass'   => isset($student_params['stdAdmitClass']) ? sanitize_text_field($student_params['stdAdmitClass']) : '',
            'stdShift'          => isset($student_params['stdShift']) ? sanitize_text_field($student_params['stdShift']) : '',
            'stdPresent'        => isset($student_params['stdPresent']) ? sanitize_textarea_field($student_params['stdPresent']) : '',
            'stdBrith'          => isset($student_params['stdBrith']) ? sanitize_text_field($student_params['stdBrith']) : '',
            'birth_reg_no'      => isset($student_params['birth_reg_no']) ? sanitize_text_field($student_params['birth_reg_no']) : '',
            'facilities'        => isset($student_params['facilities']) ? sanitize_text_field($student_params['facilities']) : '',
            'stdNationality'    => isset($student_params['stdNationality']) ? sanitize_text_field($student_params['stdNationality']) : 'Bangladeshi',
            'stdReligion'       => isset($student_params['stdReligion']) ? sanitize_text_field($student_params['stdReligion']) : '',
            'stdTcNumber'       => isset($student_params['stdTcNumber']) ? sanitize_text_field($student_params['stdTcNumber']) : '',
            'sscRoll'           => isset($student_params['sscRoll']) ? sanitize_text_field($student_params['sscRoll']) : '',
            'sscReg'            => isset($student_params['sscReg']) ? sanitize_text_field($student_params['sscReg']) : '',
            'stdPrevSchool'     => isset($student_params['stdPrevSchool']) ? sanitize_text_field($student_params['stdPrevSchool']) : '',
            'stdGPA'            => isset($student_params['stdGPA']) ? sanitize_text_field($student_params['stdGPA']) : '',
            'stdIntellectual'   => isset($student_params['stdIntellectual']) ? sanitize_text_field($student_params['stdIntellectual']) : '',
            'stdScholarsClass'  => isset($student_params['stdScholarsClass']) ? sanitize_text_field($student_params['stdScholarsClass']) : '',
            'stdScholarsYear'   => isset($student_params['stdScholarsYear']) ? sanitize_text_field($student_params['stdScholarsYear']) : '',
            'stdScholarsMemo'   => isset($student_params['stdScholarsMemo']) ? sanitize_text_field($student_params['stdScholarsMemo']) : '',
            'stdGender'         => isset($student_params['stdGender']) ? sanitize_text_field($student_params['stdGender']) : '1',
            'stdBldGrp'         => isset($student_params['stdBldGrp']) ? sanitize_text_field($student_params['stdBldGrp']) : '',
            'createdBy'         => get_current_user_id()
        );

        $insert_student = $wpdb->insert('ct_student', $student_data);

        if (false === $insert_student) {
            $results['failed_count']++;
            $results['errors'][] = "Index $index: DB Error inserting student - " . $wpdb->last_error;
            continue;
        }

        $lastid = $wpdb->insert_id;

        // Prepare data for ct_studentinfo (Enrollment)
        $enrollment_data = array(
            'infoStdid'     => $lastid,
            'infoClass'     => isset($student_params['stdAdmitClass']) ? sanitize_text_field($student_params['stdAdmitClass']) : '',
            'infoYear'      => isset($student_params['stdCurntYear']) ? sanitize_text_field($student_params['stdCurntYear']) : date('Y'),
            'infoSection'   => isset($student_params['stdSection']) ? sanitize_text_field($student_params['stdSection']) : 0,
            'infoGroup'     => isset($student_params['stdGroup']) ? sanitize_text_field($student_params['stdGroup']) : 0,
            'infoRoll'      => isset($student_params['stdRoll']) ? sanitize_text_field($student_params['stdRoll']) : '',
            'infoOptionals' => isset($student_params['stdOptionals']) ? (is_array($student_params['stdOptionals']) ? json_encode($student_params['stdOptionals']) : $student_params['stdOptionals']) : 0,
            'info4thSub'    => isset($student_params['std4thsub']) ? (is_array($student_params['std4thsub']) ? json_encode($student_params['std4thsub']) : $student_params['std4thsub']) : 0
        );

        $insert_info = $wpdb->insert('ct_studentinfo', $enrollment_data);

        if (false === $insert_info) {
            $results['failed_count']++;
            $results['errors'][] = "Index $index: DB Error inserting enrollment - " . $wpdb->last_error;
            continue;
        }

        $results['success_count']++;
        $results['data'][] = [
            'student_id' => $lastid,
            'infoid'     => $wpdb->insert_id,
            'stdName'    => $student_params['stdName']
        ];
    }

    return array(
        'success' => ($results['success_count'] > 0),
        'results' => $results
    );
}

function s3s_get_student_enrollments(WP_REST_Request $request)
{
    global $wpdb;

    $results = $wpdb->get_results(
        "SELECT si.infoid, si.infoStdid, c.className as infoClass, s.sectionName as infoSection, si.infoYear, g.groupName as infoGroup, si.infoRoll, si.infoOptionals, si.info4thSub
         FROM ct_studentinfo si
         LEFT JOIN ct_class c ON si.infoClass = c.classid
         LEFT JOIN ct_section s ON si.infoSection = s.sectionid
         LEFT JOIN ct_group g ON si.infoGroup = g.groupId
         ORDER BY si.infoYear DESC, CAST(si.infoClass AS UNSIGNED) ASC, CAST(si.infoRoll AS UNSIGNED) ASC",
        ARRAY_A
    );

    return array(
        'success' => true,
        'count'   => is_array($results) ? count($results) : 0,
        'data'    => is_array($results) ? $results : []
    );
}

/**
 * CALLBACKS: SUBJECTS
 */

function s3s_get_subjects_export(WP_REST_Request $request)
{
    global $wpdb;

    $results = $wpdb->get_results(
        "SELECT s.*, c.className as subjectClassName, t.teacherName as subjectTeacherName
         FROM ct_subject s
         LEFT JOIN ct_class c ON s.subjectClass = c.classid
         LEFT JOIN ct_teacher t ON s.subjectTeacher = t.teacherid",
        ARRAY_A
    );

    if (empty($results)) {
        return array('success' => true, 'data' => []);
    }

    foreach ($results as &$subject) {
        $groupCk = $subject['forGroup'];
        $groupNames = [];

        if ($groupCk == '0' || $groupCk == 'all' || empty($groupCk)) {
            $groupNames[] = 'All Group';
        } else {
            $groupIds = [];
            if (is_numeric($groupCk)) {
                $groupIds[] = (int)$groupCk;
            } else {
                $decoded = json_decode($groupCk, true);
                if (is_array($decoded)) {
                    $groupIds = array_map('intval', $decoded);
                }
            }

            if (!empty($groupIds)) {
                $ids_placeholder = implode(',', array_fill(0, count($groupIds), '%d'));
                $groups = $wpdb->get_results($wpdb->prepare("SELECT groupName FROM ct_group WHERE groupId IN ($ids_placeholder)", $groupIds));
                foreach ($groups as $g) {
                    $groupNames[] = $g->groupName;
                }
            }
        }
        $subject['forGroupNames'] = $groupNames;
    }

    return array('success' => true, 'count' => count($results), 'data' => $results);
}

/**
 * CALLBACKS: TEACHERS
 */

function s3s_get_teachers_export(WP_REST_Request $request)
{
    global $wpdb;

    $results = $wpdb->get_results(
        "SELECT t.*, u.user_login as username
         FROM ct_teacher t
         LEFT JOIN sm_users u ON t.tecUserId = u.ID AND t.tecUserId > 0
         ORDER BY t.teacher_serial ASC, t.teacherName ASC",
        ARRAY_A
    );

    if (empty($results)) {
        return array('success' => true, 'data' => []);
    }

    foreach ($results as &$teacher) {
        $json_fields = ['tecAssignSub', 'teacherQualificarion', 'assignSection', 'teacherTraining'];
        foreach ($json_fields as $field) {
            if (isset($teacher[$field]) && !empty($teacher[$field])) {
                $decoded = json_decode($teacher[$field], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $teacher[$field] = $decoded;
                }
            }
        }
    }

    return array('success' => true, 'count' => count($results), 'data' => $results);
}

/**
 * CALLBACKS: EXAMS & SCHEDULES
 */

function s3s_get_exams_export(WP_REST_Request $request)
{
    global $wpdb;

    $results = $wpdb->get_results(
        "SELECT e.*, c.className, c2.className as className2, g.groupName
         FROM ct_exam e
         LEFT JOIN ct_class c ON e.examClass = c.classid
         LEFT JOIN ct_class c2 ON e.examClass2 = c2.classid
         LEFT JOIN ct_group g ON e.examGroup = g.groupId
         ORDER BY e.examSirial ASC, e.examid DESC",
        ARRAY_A
    );

    if (empty($results)) {
        return array('success' => true, 'data' => []);
    }

    foreach ($results as &$exam) {
        if (isset($exam['examSubjects']) && !empty($exam['examSubjects'])) {
            $decoded = json_decode($exam['examSubjects'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $exam['examSubjects'] = $decoded;
            }
        }
    }

    return array('success' => true, 'count' => count($results), 'data' => $results);
}

function s3s_get_exam_schedules_export(WP_REST_Request $request)
{
    global $wpdb;

    $results = $wpdb->get_results(
        "SELECT es.*, c.className, e.examName
         FROM ct_exam_schedule es
         LEFT JOIN ct_class c ON es.classid = c.classid
         LEFT JOIN ct_exam e ON es.examid = e.examid
         ORDER BY es.year DESC, es.examid DESC, es.classid ASC",
        ARRAY_A
    );

    if (empty($results)) {
        return array('success' => true, 'data' => []);
    }

    foreach ($results as &$schedule) {
        if (isset($schedule['subject_dates']) && !empty($schedule['subject_dates'])) {
            $decoded = json_decode($schedule['subject_dates'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $schedule['subject_dates'] = $decoded;
            }
        }
    }

    return array('success' => true, 'count' => count($results), 'data' => $results);
}

function s3s_get_exam_results_export(WP_REST_Request $request)
{
    global $wpdb;

    $page = $request->get_param('page');
    $per_page = $request->get_param('per_page');
    $offset = ($page - 1) * $per_page;

    // Get total count
    $total_count = $wpdb->get_var("SELECT COUNT(*) FROM ct_result");

    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT r.*, c.className, g.groupName, s.sectionName, e.examName
             FROM ct_result r
             LEFT JOIN ct_class c ON r.resClass = c.classid
             LEFT JOIN ct_group g ON r.resgroup = g.groupId
             LEFT JOIN ct_section s ON r.resSec = s.sectionid
             LEFT JOIN ct_exam e ON r.resExam = e.examid
             ORDER BY r.resultYear DESC, r.resExam DESC, r.resClass ASC, CAST(r.resStdRoll AS UNSIGNED) ASC
             LIMIT %d OFFSET %d",
            $per_page,
            $offset
        ),
        ARRAY_A
    );

    $total_pages = ceil($total_count / $per_page);

    return array(
        'success'      => true,
        'count'        => is_array($results) ? count($results) : 0,
        'total_count'  => (int)$total_count,
        'total_pages'  => (int)$total_pages,
        'current_page' => (int)$page,
        'per_page'     => (int)$per_page,
        'data'         => is_array($results) ? $results : []
    );
}

/**
 * CALLBACK: SLIDER IMAGES
 */

function s3s_get_slider_images_export(WP_REST_Request $request)
{
    global $wpdb;

    $results = $wpdb->get_results(
        "SELECT id, image_url, created_at FROM sm_slider_images ORDER BY created_at DESC",
        ARRAY_A
    );

    if (empty($results)) {
        return array('success' => true, 'data' => []);
    }

    return array(
        'success' => true,
        'count'   => count($results),
        'data'    => $results
    );
}

/**
 * Upload an image via REST API
 */
function s3s_upload_image_export(WP_REST_Request $request)
{
    if (!function_exists('wp_handle_upload')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    $files = $request->get_file_params();
    if (empty($files) || !isset($files['image'])) {
        return new WP_Error('no_file', 'No image file provided in the "image" field', array('status' => 400));
    }

    $uploadedfile = $files['image'];
    $upload_overrides = array('test_form' => false);

    $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

    if ($movefile && !isset($movefile['error'])) {
        $url = $movefile['url'];
        
        // Optionally register as attachment (recommended for WP)
        $filename = basename($movefile['file']);
        $filetype = wp_check_filetype($filename, null);
        $attachment = array(
            'guid'           => $url,
            'post_mime_type' => $filetype['type'],
            'post_title'     => preg_replace('/\.[^.]+$/', '', $filename),
            'post_content'   => '',
            'post_status'    => 'inherit'
        );

        $attach_id = wp_insert_attachment($attachment, $movefile['file']);

        if (!is_wp_error($attach_id)) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            $attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
            wp_update_attachment_metadata($attach_id, $attach_data);
        }

        return array(
            'success' => true,
            'url'     => $url,
            'id'      => is_wp_error($attach_id) ? null : $attach_id
        );
    } else {
        return new WP_Error('upload_error', $movefile['error'], array('status' => 500));
    }
}

/**
 * CALLBACK: COMMITTEES (NESTED)
 */

function s3s_get_committees_export(WP_REST_Request $request)
{
    global $wpdb;

    // Get all committees
    $committees = $wpdb->get_results(
        "SELECT committee_id, committee_title, job_description, sort_order, is_primary, is_active, created_at, updated_at 
         FROM ct_committees 
         ORDER BY sort_order ASC",  
        ARRAY_A
    );

    if (empty($committees)) {
        return array('success' => true, 'data' => []);
    }

    // Get all members and associate them with committees
    foreach ($committees as &$committee) {
        $committee_id = $committee['committee_id'];
        $members = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT member_id, member_label, member_name, member_designation, member_subject, member_position, sort_order, is_active, created_at, updated_at 
                 FROM ct_committee_members 
                 WHERE committee_id = %d 
                 ORDER BY sort_order ASC",
                $committee_id
            ),
            ARRAY_A
        );
        $committee['members'] = $members ? $members : [];
    }

    return array(
        'success' => true,
        'count'   => count($committees),
        'data'    => $committees
    );
}

/**
 * CALLBACK: GOVERNING BODY
 */

function s3s_get_governing_body_export(WP_REST_Request $request)
{
    global $wpdb;

    $results = $wpdb->get_results(
        "SELECT governing_body_id, governing_body_session, governing_body_name, governing_body_image, governing_body_father_name, governing_body_mother_name, governing_body_designation, note, order_number, is_active, created_at, updated_at 
         FROM ct_governing_body 
         ORDER BY (order_number + 0) ASC", // Simple way to handle string/numeric sorting if order_number is varchar
        ARRAY_A
    );

    if (empty($results)) {
        return array('success' => true, 'data' => []);
    }

    return array(
        'success' => true,
        'count'   => count($results),
        'data'    => $results
    );
}

/**
 * CALLBACK: THEME OPTIONS
 */

function s3s_get_theme_options_export(WP_REST_Request $request)
{
    global $wpdb;
    $sm_table = 'sm_options';
    $options_data = [];

    // Whitelist based on theme-options-list.md
    $allowed_options = [
        'institute_name', 'institute_address', 'institute_email', 'institute_phone',
        'institute_eiin', 'institute_code', 'center_code', 'estd_year',
        'inst_head_title', 'inst_head_name', 'homeHeadmasterTitle', 'headmasterSpeechTitle',
        'homeHeadmaster', 'homeHeadmasterImg', 'homeChairmanTitle', 'chairmanSpeechTitle',
        'homeChairman', 'homeChairmanImg', 'principalSign', 'aboutTitelText',
        'aboutUsText', 'aboutUsTextLimit', 'aboutUsMoreBtn', 'footerAddress',
        'footerContact', 'footerFbUrl', 'copyrightText', 'board_name_1',
        'board_name_2', 'admitCareNote', 'stdidpref'
    ];

    // Check if sm_options table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$sm_table'");

    if ($table_exists === $sm_table) {
        $placeholders = implode(',', array_fill(0, count($allowed_options), '%s'));
        $results = $wpdb->get_results(
            $wpdb->prepare("SELECT option_name, option_value FROM $sm_table WHERE option_name IN ($placeholders)", $allowed_options),
            ARRAY_A
        );
        if ($results) {
            foreach ($results as $row) {
                $val = $row['option_value'];
                // Try to decode if it's JSON
                $decoded = json_decode($val, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $val = $decoded;
                }
                $options_data[$row['option_name']] = $val;
            }
        }
    }

    // Also include Redux options (opt_name) from WordPress options table for completeness, but filter them
    $redux_options = get_option('opt_name', []);
    if (is_array($redux_options)) {
        foreach ($allowed_options as $key) {
            if (!isset($options_data[$key]) && isset($redux_options[$key])) {
                $options_data[$key] = $redux_options[$key];
            }
        }
    }

    return array(
        'success' => true,
        'data'    => $options_data
    );
}
