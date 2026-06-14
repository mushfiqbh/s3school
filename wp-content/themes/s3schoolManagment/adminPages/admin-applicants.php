    <?php
    /*
    Template Name: Admin Applicants
    */

    global $wpdb;
    $message = null;

    // Helper Functions
    function sm_clean_int($v)
    {
        return isset($v) && $v !== '' ? (int)$v : null;
    }
    function sm_clean_txt($v)
    {
        return isset($v) ? sanitize_text_field($v) : '';
    }

    /*=================
    AJAX Handler: Get Sections for Class (Direct SQL)
    ================*/
    if (isset($_POST['ajax_get_sections_for_class'])) {
        header('Content-Type: application/json');

        $classId = isset($_POST['classId']) ? intval($_POST['classId']) : 0;

        if ($classId <= 0) {
            echo json_encode(['status' => 'error', 'sections' => [], 'message' => 'Invalid class ID', 'debug' => 'invalid_class_id']);
            exit;
        }

        try {
            // Note: Column is 'forClass' not 'class'
            $sections = $wpdb->get_results($wpdb->prepare(
                "SELECT sectionid, sectionName FROM ct_section WHERE forClass = %d ORDER BY sectionName",
                $classId
            ));

            if ($wpdb->last_error) {
                echo json_encode(['status' => 'error', 'sections' => [], 'message' => 'Database error', 'debug' => $wpdb->last_error]);
                exit;
            }

            echo json_encode(['status' => 'success', 'sections' => $sections ? $sections : [], 'debug' => 'query_ok', 'classId' => $classId]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'sections' => [], 'message' => 'Exception occurred', 'debug' => $e->getMessage()]);
        }
        exit;
    }

    /*=================
    AJAX Handler: Get Admission Fee Config for All Classes
    ================*/
    if (isset($_POST['ajax_get_admission_config'])) {
        header('Content-Type: application/json');

        $configs = $wpdb->get_results("
            SELECT c.classid, c.className, 
                p.id as config_id, p.amount, p.admission_start_date, p.admission_end_date
            FROM ct_class c
            LEFT JOIN ct_admission_fee_promoted p ON c.classid = p.class
            ORDER BY c.className
        ");

        echo json_encode(['status' => 'success', 'configs' => $configs]);
        exit;
    }

    /*=================
    AJAX Handler: Save Admission Fee Config
    ================*/
    if (isset($_POST['ajax_save_admission_config'])) {
        header('Content-Type: application/json');

        $class_id = intval($_POST['class_id']);
        $amount = floatval($_POST['amount']);
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);

        $existing = $wpdb->get_row($wpdb->prepare("SELECT id FROM ct_admission_fee_promoted WHERE class = %d", $class_id));

        if ($existing) {
            $result = $wpdb->update(
                'ct_admission_fee_promoted',
                [
                    'amount' => $amount,
                    'admission_start_date' => $start_date,
                    'admission_end_date' => $end_date
                ],
                ['class' => $class_id]
            );
        } else {
            $result = $wpdb->insert(
                'ct_admission_fee_promoted',
                [
                    'class' => $class_id,
                    'amount' => $amount,
                    'admission_start_date' => $start_date,
                    'admission_end_date' => $end_date
                ]
            );
        }

        if ($result !== false) {
            echo json_encode(['status' => 'success', 'message' => 'Configuration saved successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to save configuration']);
        }
        exit;
    }

    /*=================
    AJAX Handler: Direct Register Applicant
    ================*/
    if (isset($_POST['ajax_register_applicant'])) {
        header('Content-Type: application/json');

        $applicationid = isset($_POST['applicationid']) ? intval($_POST['applicationid']) : 0;

        if ($applicationid <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid application ID']);
            exit;
        }

        // Update Section and Roll if provided (specifically for Approve & Register action)
        if (isset($_POST['stdSection']) && isset($_POST['stdRoll'])) {
            $wpdb->update(
                'ct_online_application',
                array(
                    'stdSection' => intval($_POST['stdSection']),
                    'stdRoll'    => intval($_POST['stdRoll'])
                ),
                array('applicationid' => $applicationid)
            );
        }

        $app = $wpdb->get_row($wpdb->prepare("SELECT * FROM ct_online_application WHERE applicationid = %d", $applicationid));

        if (!$app) {
            echo json_encode(['status' => 'error', 'message' => 'Application not found']);
            exit;
        }

        if ($app->studentid != 0) {
            echo json_encode(['status' => 'error', 'message' => 'Applicant already registered']);
            exit;
        }

        // Normalize DOB
        $dob = '';
        if (!empty($app->stdBrith)) {
            $timestamp = strtotime($app->stdBrith);
            if ($timestamp) {
                $dob = date('Y-m-d', $timestamp);
            } else {
                $dob = $app->stdBrith;
            }
        }

        // Prepare Student Data with default values for nullable fields to avoid "cannot be null" errors
        $student_data = array(
            'stdName'          => !empty($app->stdName) ? $app->stdName : '',
            'stdNameBangla'    => !empty($app->stdNameBangla) ? $app->stdNameBangla : '',
            'stdImg'           => !empty($app->stdImg) ? $app->stdImg : '',
            'stdFather'        => !empty($app->stdFather) ? $app->stdFather : '',
            'fatherLate'       => !empty($app->fatherLate) ? 1 : 0,
            'stdFatherProf'    => !empty($app->stdFatherProf) ? $app->stdFatherProf : '',
            'stdMother'        => !empty($app->stdMother) ? $app->stdMother : '',
            'motherLate'       => !empty($app->motherLate) ? 1 : 0,
            'stdMotherProf'    => !empty($app->stdMotherProf) ? $app->stdMotherProf : '',
            'stdParentIncome'  => !empty($app->stdParentIncome) ? $app->stdParentIncome : 0,
            'stdlocalGuardian' => !empty($app->stdlocalGuardian) ? $app->stdlocalGuardian : '',
            'stdGuardianNID'   => !empty($app->stdGuardianNID) ? $app->stdGuardianNID : '',
            'stdPhone'         => !empty($app->stdPhone) ? $app->stdPhone : '',
            'stdPermanent'     => !empty($app->stdPermanent) ? $app->stdPermanent : '',
            'stdAdmitYear'     => !empty($app->stdAdmitYear) ? $app->stdAdmitYear : date('Y'),
            'stdCurntYear'     => !empty($app->stdAdmitYear) ? $app->stdAdmitYear : date('Y'),
            'stdAdmitClass'    => !empty($app->stdAdmitClass) ? $app->stdAdmitClass : 0,
            'stdCurrentClass'  => !empty($app->stdAdmitClass) ? $app->stdAdmitClass : 0,
            'stdPresent'       => !empty($app->stdPresent) ? $app->stdPresent : '',
            'stdBrith'         => $dob,
            'facilities'       => 'None',
            'stdNationality'   => !empty($app->stdNationality) ? $app->stdNationality : 'Bangladeshi',
            'stdReligion'      => !empty($app->stdReligion) ? $app->stdReligion : '',
            'stdTcNumber'      => !empty($app->stdTcNumber) ? $app->stdTcNumber : '',
            'sscRoll'          => !empty($app->sscRoll) ? $app->sscRoll : '',
            'sscReg'           => !empty($app->sscReg) ? $app->sscReg : '',
            'stdPrevSchool'    => !empty($app->stdPrevSchool) ? $app->stdPrevSchool : '',
            'stdGPA'           => !empty($app->stdGPA) ? $app->stdGPA : '',
            'stdIntellectual'  => !empty($app->stdIntellectual) ? $app->stdIntellectual : '',
            'stdScholarsClass' => !empty($app->stdScholarsClass) ? $app->stdScholarsClass : '',
            'stdScholarsYear'  => !empty($app->stdScholarsYear) ? $app->stdScholarsYear : '',
            'stdScholarsMemo'  => !empty($app->stdScholarsMemo) ? $app->stdScholarsMemo : '',
            'stdGender'        => !empty($app->stdGender) ? $app->stdGender : '',
            'stdBldGrp'        => !empty($app->stdBldGrp) ? $app->stdBldGrp : '',
            'birth_reg_no'     => !empty($app->birth_reg_no) ? $app->birth_reg_no : '',
            'createdBy'        => get_current_user_id()
        );

        $insert_student = $wpdb->insert('ct_student', $student_data);

        if ($insert_student) {
            $lastid = $wpdb->insert_id;

            // Prepare Student Info Data
            $student_info_data = array(
                'infoStdid'   => $lastid,
                'infoClass'   => $app->stdAdmitClass,
                'infoYear'    => $app->stdAdmitYear,
                'infoSection' => $app->stdSection ? $app->stdSection : 0,
                'infoGroup'   => $app->stdGroup ? $app->stdGroup : 0,
                'infoRoll'    => $app->stdRoll ? $app->stdRoll : 0,
                'infoOptionals' => 0,
                'info4thSub'  => 0
            );

            $insert_info = $wpdb->insert('ct_studentinfo', $student_info_data);

            // Update Application Status
            $wpdb->update(
                'ct_online_application',
                array('approve_status' => 'Registered', 'studentid' => $lastid),
                array('applicationid' => $applicationid)
            );

            echo json_encode(['status' => 'success', 'message' => 'Applicant registered successfully as Student ID: ' . $lastid]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to register student: ' . $wpdb->last_error]);
        }
        exit;
    }

    get_header();

    /*=================
    Action Handlers
    ================*/

    // 1. Update Individual Applicant (Unified)
    if (isset($_POST['updateIndividualApplicant']) && isset($_POST['applicationid'])) {
        $applicationid = (int)$_POST['applicationid'];

        // Collect Data
        $status = sm_clean_txt($_POST['approve_status']);
        $payment_status = isset($_POST['payment_status']) ? sm_clean_txt($_POST['payment_status']) : 'Pending';
        $payment_amount = isset($_POST['payment_amount']) ? floatval($_POST['payment_amount']) : 0;
        $section_id = !empty($_POST['stdSection']) ? (int)$_POST['stdSection'] : null;
        $roll_no = !empty($_POST['stdRoll']) ? (int)$_POST['stdRoll'] : null;
        $reject_reason = isset($_POST['reject_reason']) ? sm_clean_txt($_POST['reject_reason']) : '';
        $admit_year = isset($_POST['stdAdmitYear']) ? (int)$_POST['stdAdmitYear'] : null;

        $update_data = array(
            'approve_status' => $status,
            'payment_status' => $payment_status,
            'payment_amount' => $payment_amount,
            'stdSection' => $section_id,
            'stdRoll' => $roll_no,
            'updated_at' => current_time('mysql')
        );
        if ($admit_year) {
            $update_data['stdAdmitYear'] = $admit_year;
        }
        // Add rejection reason if status is Rejected
        if ($status === 'Rejected' && !empty($reject_reason)) {
            $update_data['reject_reason'] = $reject_reason;
        }

        if ($payment_status === 'Paid') {
            $update_data['payment_date'] = current_time('mysql');
        }

        $update = $wpdb->update(
            'ct_online_application',
            $update_data,
            array('applicationid' => $applicationid)
        );

        // If already registered, update ct_studentinfo as well
        $app = $wpdb->get_row($wpdb->prepare("SELECT studentid FROM ct_online_application WHERE applicationid = %d", $applicationid));
        if ($app && $app->studentid) {
            $studentinfo_update = array();
            if ($admit_year) $studentinfo_update['infoYear'] = $admit_year;
            if ($section_id) $studentinfo_update['infoSection'] = $section_id;
            if ($roll_no) $studentinfo_update['infoRoll'] = $roll_no;
            if (!empty($studentinfo_update)) {
                $wpdb->update('ct_studentinfo', $studentinfo_update, array('infoStdid' => $app->studentid));
            }
        }

        if ($update !== false) {
            $message = array('status' => 'success', 'text' => 'Applicant updated successfully.');
        } else {
            $message = array('status' => 'error', 'text' => 'Failed to update applicant.');
        }
    }

    // 4. Bulk Update Section and Roll
    if (isset($_POST['bulkUpdateSectionRoll']) && !empty($_POST['selected_applicants'])) {
        $selected_ids = array_map('intval', $_POST['selected_applicants']);
        $section_id = !empty($_POST['bulk_section']) ? (int)$_POST['bulk_section'] : null;
        $starting_roll = !empty($_POST['starting_roll']) ? (int)$_POST['starting_roll'] : null;

        $success_count = 0;
        $current_roll = $starting_roll;

        foreach ($selected_ids as $app_id) {
            $update_data = array();

            if ($section_id !== null) {
                $update_data['stdSection'] = $section_id;
            }

            if ($current_roll !== null) {
                $update_data['stdRoll'] = $current_roll;
                $current_roll++; // Increment for next student
            }

            if (!empty($update_data)) {
                $result = $wpdb->update(
                    'ct_online_application',
                    $update_data,
                    array('applicationid' => $app_id)
                );

                if ($result !== false) {
                    $success_count++;
                }
            }
        }

        if ($success_count > 0) {
            $message = array('status' => 'success', 'text' => "Successfully updated {$success_count} applicant(s).");
        } else {
            $message = array('status' => 'error', 'text' => 'Failed to update applicants.');
        }
    }

        // 5. AJAX Reject Applicant
        if (isset($_POST['ajax_reject_applicant'])) {
            // Use WordPress JSON response helpers for AJAX
            $applicationid = isset($_POST['applicationid']) ? intval($_POST['applicationid']) : 0;
            $reject_reason = isset($_POST['reject_reason']) ? sm_clean_txt($_POST['reject_reason']) : '';

            if ($applicationid <= 0) {
                wp_send_json_error(['message' => 'Invalid application ID']);
            }

            $update_data = array(
                'approve_status' => 'Rejected',
                'reject_reason'  => $reject_reason,
                'updated_at'     => current_time('mysql')
            );

            $result = $wpdb->update(
                'ct_online_application',
                $update_data,
                array('applicationid' => $applicationid)
            );
            // $wpdb->update returns 0 if no rows changed, but that's not an error
            if ($result !== false) {
                wp_send_json_success(['message' => 'Applicant rejected successfully.']);
            } else {
                wp_send_json_error(['message' => 'Failed to reject applicant.']);
            }
            // exit handled by wp_send_json_*
        }

    /*=================
    Data Fetching
    ================*/

    // Check if any filter is applied
    $has_filter = !empty($_GET['filter_phone']) || !empty($_GET['filter_class']) ||
        !empty($_GET['filter_status']) || !empty($_GET['filter_payment']) ||
        !empty($_GET['filter_date_from']) || !empty($_GET['filter_date_to']);

    $apps = array();

    if ($has_filter) {
        // Build Query
        $query = "SELECT a.*, c.className, s.sectionName, g.groupName, 
                f.amount as expected_fee
                FROM ct_online_application a
                LEFT JOIN ct_class c ON a.stdAdmitClass = c.classid
                LEFT JOIN ct_section s ON a.stdSection = s.sectionid
                LEFT JOIN ct_group g ON a.stdGroup = g.groupId
                LEFT JOIN ct_admission_fee_promoted f ON a.stdAdmitClass = f.class AND f.is_active = 1
                WHERE 1=1";

        $params = array();

        // Filter: Phone Number
        if (!empty($_GET['filter_phone'])) {
            $phone = sanitize_text_field($_GET['filter_phone']);
            $query .= " AND a.stdPhone LIKE %s";
            $params[] = '%' . $wpdb->esc_like($phone) . '%';
        }

        // Filter: Class
        if (!empty($_GET['filter_class'])) {
            $class_id = (int)$_GET['filter_class'];
            $query .= " AND a.stdAdmitClass = %d";
            $params[] = $class_id;
        }

        // Filter: Approval Status
        if (!empty($_GET['filter_status'])) {
            $status = sanitize_text_field($_GET['filter_status']);
            $query .= " AND a.approve_status = %s";
            $params[] = $status;
        }

        // Filter: Payment Status
        if (!empty($_GET['filter_payment'])) {
            $payment_status = sanitize_text_field($_GET['filter_payment']);
            $query .= " AND a.payment_status = %s";
            $params[] = $payment_status;
        }

        // Filter: Date Range
        if (!empty($_GET['filter_date_from'])) {
            $date_from = sanitize_text_field($_GET['filter_date_from']);
            $query .= " AND DATE(a.stdCreatedAt) >= %s";
            $params[] = $date_from;
        }
        if (!empty($_GET['filter_date_to'])) {
            $date_to = sanitize_text_field($_GET['filter_date_to']);
            $query .= " AND DATE(a.stdCreatedAt) <= %s";
            $params[] = $date_to;
        }

        // Sort
        $sort_order = (!empty($_GET['filter_sort']) && $_GET['filter_sort'] === 'oldest') ? 'ASC' : 'DESC';
        if (!empty($_GET['filter_sort']) && $_GET['filter_sort'] === 'gpa') {
            $query .= " ORDER BY CAST(a.stdPreviousGPA AS DECIMAL(5,2)) DESC";
        } else {
            $query .= " ORDER BY a.applicationid " . $sort_order;
        }

        // Execute
        if (!empty($params)) {
            $query = $wpdb->prepare($query, $params);
        }
        $apps = $wpdb->get_results($query);
    }

    // Stats
    $total_apps = $wpdb->get_var("SELECT COUNT(*) FROM ct_online_application");
    $pending_apps = $wpdb->get_var("SELECT COUNT(*) FROM ct_online_application WHERE approve_status = 'Under Review'");
    $paid_apps = $wpdb->get_var("SELECT COUNT(*) FROM ct_online_application WHERE payment_status = 'Paid'");

    // Get all classes for filter
    $all_classes = $wpdb->get_results("SELECT classid, className FROM ct_class ORDER BY className");

    // Get classes with application counts for quick filter cards
    $classes_with_apps = $wpdb->get_results("
        SELECT c.classid, c.className, COUNT(a.applicationid) as app_count,
            SUM(CASE WHEN a.approve_status = 'Under Review' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN a.payment_status = 'Paid' THEN 1 ELSE 0 END) as paid_count
        FROM ct_class c
        INNER JOIN ct_online_application a ON c.classid = a.stdAdmitClass
        GROUP BY c.classid, c.className
        ORDER BY c.className
    ");

    // Get classes with new application counts for quick filter cards
    $classes_with_new_apps = $wpdb->get_results("
        SELECT c.classid, c.className, COUNT(a.applicationid) as app_count,
            SUM(CASE WHEN a.approve_status = 'Under Review' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN a.payment_status = 'Paid' THEN 1 ELSE 0 END) as paid_count
        FROM ct_class c
        INNER JOIN ct_online_application a ON c.classid = a.stdAdmitClass AND a.approve_status = 'Under Review'
        GROUP BY c.classid, c.className
        ORDER BY c.className
    ");


    ?>

    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #1e40af;
            --bg-color: #f1f5f9;
            --card-bg: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
        }

        body {
            background-color: var(--bg-color);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
        }

        .admin-container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-main);
            margin: 0;
        }

        .stats-badges {
            display: flex;
            gap: 15px;
        }

        .stat-badge {
            background: var(--card-bg);
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
        }

        .filter-card {
            background: var(--card-bg);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
        }

        .filter-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            align-items: end;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--text-muted);
        }

        .form-control {
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 14px;
            width: 100%;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary-color);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--text-main);
        }

        .btn-outline:hover {
            background: #f8fafc;
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-warning {
            background: var(--warning-color);
            color: white;
        }

        .btn-info {
            background: #7e22ce;
            color: white;
        }

        .btn-secondary {
            background: #64748b;
            color: white;
        }

        .data-card {
            background: var(--card-bg);
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .table-responsive {
            overflow-x: auto;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .table th {
            background: #f8fafc;
            padding: 12px 10px;
            text-align: left;
            font-weight: 600;
            color: var(--text-muted);
            border-bottom: 1px solid var(--border-color);
            white-space: nowrap;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table td {
            padding: 12px 10px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .table tr:last-child td {
            border-bottom: none;
        }

        .table tr:hover {
            background: #f8fafc;
        }

        .status-badge,
        .payment-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            display: inline-block;
            white-space: nowrap;
        }

        .status-under-review {
            background: #e0f2fe;
            color: #0369a1;
        }

        .status-under-review {
            background: #fef3c7;
            color: #92400e;
        }

        .status-approved {
            background: #dcfce7;
            color: #15803d;
        }

        .status-rejected {
            background: #fee2e2;
            color: #b91c1c;
        }

        .status-registered {
            background: #f3e8ff;
            color: #7e22ce;
        }

        .payment-pending {
            background: #fed7aa;
            color: #9a3412;
        }

        .payment-paid {
            background: #d1fae5;
            color: #065f46;
        }

        .payment-partial {
            background: #dbeafe;
            color: #1e40af;
        }

        .action-buttons {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .payment-form {
            display: flex;
            gap: 5px;
            align-items: center;
        }

        .payment-input {
            width: 80px;
            padding: 4px 6px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 12px;
        }

        .payment-select {
            padding: 4px 6px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 12px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        /* Class Filter Cards */
        .class-cards-section {
            margin-top: 20px;
        }

        .class-cards-title {
            font-size: 14px;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .class-cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 12px;
            max-width: 800px;
            margin: 0 auto;
        }

        .class-filter-card {
            background: var(--card-bg);
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .class-filter-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
            text-decoration: none;
            color: inherit;
        }

        .class-filter-card.active {
            border-color: var(--primary-color);
            background: rgba(37, 99, 235, 0.05);
        }

        .class-card-name {
            font-size: 16px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 8px;
        }

        .class-card-count {
            font-size: 24px;
            font-weight: 800;
            color: var(--primary-color);
            line-height: 1;
            margin-bottom: 4px;
        }

        .class-card-label {
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .class-card-stats {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid var(--border-color);
        }

        .class-card-stat {
            font-size: 11px;
        }

        .class-card-stat.pending {
            color: var(--warning-color);
        }

        .class-card-stat.paid {
            color: var(--success-color);
        }

        /* Checkbox styles */
        .checkbox-cell {
            width: 40px;
            text-align: center;
        }

        .applicant-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        /* Bulk actions bar */
        .bulk-actions-bar {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: var(--card-bg);
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            border: 1px solid var(--border-color);
            display: none;
            align-items: center;
            gap: 15px;
            z-index: 1000;
            animation: slideUp 0.3s ease;
        }

        @keyframes slideUp {
            from {
                transform: translateX(-50%) translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateX(-50%) translateY(0);
                opacity: 1;
            }
        }

        .bulk-actions-bar.show {
            display: flex;
        }

        .selected-count {
            font-weight: 600;
            color: var(--primary-color);
        }

        /* Modal styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 99999;
            align-items: center;
            justify-content: center;
        }

        .modal-overlay.show {
            display: flex !important;
        }

        .modal-content {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 0;
            max-width: 550px;
            width: 95%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: modalZoom 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            margin: auto;
            border: 1px solid var(--border-color);
            position: relative;
        }

        .modal-content.modal-lg {
            max-width: 1000px;
        }

        .modal-content.modal-xl {
            max-width: 1200px;
        }

        @keyframes modalZoom {
            from {
                transform: scale(0.9);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            border-bottom: 1px solid var(--border-color);
            background: #f8fafc;
            border-radius: 16px 16px 0 0;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .modal-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-main);
            margin: 0;
        }

        .modal-close {
            background: transparent;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-muted);
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: all 0.2s;
        }

        .modal-close:hover {
            background: #f1f5f9;
            color: var(--text-main);
        }

        .modal-body {
            padding: 30px;
        }

        .modal-form-group {
            margin-bottom: 20px;
        }

        .modal-label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 8px;
            color: var(--text-main);
        }

        .modal-input,
        .modal-select {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 14px;
        }

        .modal-input:focus,
        .modal-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .modal-footer {
            padding: 20px 30px;
            background: #f8fafc;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            border-radius: 0 0 16px 16px;
        }

        @media (max-width: 900px) {
            .modal-body-split {
                flex-direction: column !important;
            }
            .modal-col-left, .modal-col-right {
                min-width: 100% !important;
                border-right: none !important;
                border-bottom: 1px solid var(--border-color);
            }
        }

        .modal-help-text {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 5px;
        }
    </style>

    <style>
    /* Responsive Admin UI */
    @media (max-width: 900px) {
        .admin-container {
            padding: 8px !important;
        }
        .page-header {
            flex-direction: column !important;
            align-items: flex-start !important;
            gap: 10px !important;
        }
        .stats-badges {
            flex-wrap: wrap;
            gap: 8px !important;
        }
        .bulk-actions-bar {
            flex-direction: column;
            align-items: stretch;
            gap: 8px;
            padding: 8px 0 !important;
        }
        .filter-card, .filter-form {
            flex-direction: column !important;
            gap: 10px !important;
        }
        .form-group {
            width: 100% !important;
            min-width: 0 !important;
        }
        .filter-form .form-group {
            margin-bottom: 10px !important;
        }
        .alert {
            font-size: 15px !important;
            padding: 10px 8px !important;
        }
        .btn, .btn-primary, .btn-outline {
            width: 100%;
            margin-bottom: 8px;
            font-size: 16px !important;
            padding: 12px 0 !important;
        }
        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 16px;
        }
        table {
            min-width: 700px;
            font-size: 15px !important;
        }
        th, td {
            padding: 8px 6px !important;
        }
        .modal-body {
            padding: 12px !important;
        }
        .modal-footer {
            flex-direction: column;
            gap: 8px;
            padding: 10px 12px !important;
        }
    }
    @media (max-width: 600px) {
        .page-title {
            font-size: 20px !important;
        }
        .stats-badges {
            font-size: 13px !important;
        }
        .modal {
            width: 98vw !important;
            min-width: unset !important;
            left: 1vw !important;
            right: 1vw !important;
            padding: 0 !important;
        }
        .modal-body, .modal-footer {
            padding: 8px !important;
        }
        .modal-label {
            font-size: 13px !important;
        }
        .modal-input, .modal-select {
            font-size: 15px !important;
            padding: 8px 6px !important;
        }
        .form-label {
            font-size: 13px !important;
        }
    }
    </style>

    <div class="admin-container">

        <!-- Responsive Table Wrapper -->
        <div class="table-responsive">

        <div class="page-header">
            <h1 class="page-title">Applicants Management</h1>
            <div>
                <!-- Other Classes Quick Links -->
                <?php if (!empty($classes_with_new_apps)): ?>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
                        <?php
                        $current_class = !empty($_GET['filter_class']) ? (int)$_GET['filter_class'] : null;
                        foreach ($classes_with_new_apps as $cls):
                            $is_active = ($current_class === (int)$cls->classid);
                            $link = '?filter_class=' . $cls->classid;
                            if($is_active) continue;
                        ?>
                            <a href="<?= esc_url($link) ?>"
                            target="_blank"
                            class="btn btn-outline"
                            style="padding: 4px 10px; font-size: 13px; color: black;">
                                <?= esc_html($cls->className) ?>
                                <span style="font-weight:700; color: var(--primary-color); margin-left: 3px;">
                                    (<?= (int)$cls->app_count ?>)
                                </span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div style="display: flex; gap: 15px; align-items: center;">
                <button type="button" class="btn btn-info" onclick="openAdmissionConfigModal()" style="background: #10b981; color: white;">
                    📅 Admission Setup
                </button>
                <div class="stats-badges">
                    <div class="stat-badge">Total: <?= $total_apps ?></div>
                    <div class="stat-badge" style="color: var(--warning-color)">Pending: <?= $pending_apps ?></div>
                    <!-- <div class="stat-badge" style="color: var(--success-color)">Paid: <?= $paid_apps ?></div> -->
                </div>
            </div>
        </div>

        <!-- Bulk Actions Bar -->
        <div class="bulk-actions-bar" id="bulkActionsBar">
            <span class="selected-count"><span id="selectedCount">0</span> selected</span>
            <button type="button" class="btn btn-primary" onclick="openBulkModal()">
                Assign Section & Roll
            </button>
            <button type="button" class="btn btn-outline" onclick="clearSelection()">
                Clear Selection
            </button>
        </div>

        <!-- Class Filter Required Message -->
        <div class="alert alert-error" id="classFilterRequired" style="display: none;">
            <strong>⚠ Class Filter Required:</strong> Please filter by a specific class before assigning sections.
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message['status'] ?>">
                <?= esc_html($message['text']) ?>
            </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="filter-card">
            <form method="get" class="filter-form">
                <input type="hidden" name="page_id" value="<?= get_the_ID() ?>">

                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="filter_phone" class="form-control"
                        placeholder="Search phone..."
                        value="<?= esc_attr($_GET['filter_phone'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Class</label>
                    <select name="filter_class" class="form-control">
                        <option value="">All Classes</option>
                        <?php foreach ($all_classes as $cls): ?>
                            <option value="<?= $cls->classid ?>" <?= (!empty($_GET['filter_class']) && $_GET['filter_class'] == $cls->classid) ? 'selected' : '' ?>>
                                <?= esc_html($cls->className) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Approval Status</label>
                    <select name="filter_status" class="form-control">
                        <option value="">All Status</option>
                        <option value="Under Review" <?= (!empty($_GET['filter_status']) && $_GET['filter_status'] == 'Under Review') ? 'selected' : '' ?>>Under Review</option>
                        <option value="Approved" <?= (!empty($_GET['filter_status']) && $_GET['filter_status'] == 'Approved') ? 'selected' : '' ?>>Approved</option>
                        <option value="Registered" <?= (!empty($_GET['filter_status']) && $_GET['filter_status'] == 'Registered') ? 'selected' : '' ?>>Registered</option>
                        <option value="Rejected" <?= (!empty($_GET['filter_status']) && $_GET['filter_status'] == 'Rejected') ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>

                <!-- <div class="form-group">
                    <label class="form-label">Payment Status</label>
                    <select name="filter_payment" class="form-control">
                        <option value="">All Payments</option>
                        <option value="Pending" <?= (!empty($_GET['filter_payment']) && $_GET['filter_payment'] == 'Pending') ? 'selected' : '' ?>>Pending</option>
                        <option value="Paid" <?= (!empty($_GET['filter_payment']) && $_GET['filter_payment'] == 'Paid') ? 'selected' : '' ?>>Paid</option>
                        <option value="Partial" <?= (!empty($_GET['filter_payment']) && $_GET['filter_payment'] == 'Partial') ? 'selected' : '' ?>>Partial</option>
                    </select>
                </div> -->

                <div class="form-group">
                    <label class="form-label">From Date</label>
                    <input type="date" name="filter_date_from" class="form-control"
                        value="<?= esc_attr($_GET['filter_date_from'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">To Date</label>
                    <input type="date" name="filter_date_to" class="form-control"
                        value="<?= esc_attr($_GET['filter_date_to'] ?? '') ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Sort</label>
                    <select name="filter_sort" class="form-control">
                        <option value="newest" <?= (!empty($_GET['filter_sort']) && $_GET['filter_sort'] == 'newest') ? 'selected' : '' ?>>Newest First</option>
                        <option value="oldest" <?= (!empty($_GET['filter_sort']) && $_GET['filter_sort'] == 'oldest') ? 'selected' : '' ?>>Oldest First</option>
                        <option value="gpa" <?= (!empty($_GET['filter_sort']) && $_GET['filter_sort'] == 'gpa') ? 'selected' : '' ?>>GPA</option>
                    </select>
                </div>

                <div class="form-group" style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="?" class="btn btn-outline">Reset</a>
                </div>
            </form>
        </div>

        <!-- Applications Table -->
        <div class="data-card">
            <?php if (!$has_filter): ?>
                <div class="empty-state">
                    <?php if (!empty($classes_with_apps)): ?>
                        <div class="class-cards-section">
                            <p class="class-cards-title">Start by Class</p>
                            <div class="class-cards-grid">
                                <?php foreach ($classes_with_apps as $cls): ?>
                                    <a href="?page_id=<?= get_the_ID() ?>&filter_class=<?= $cls->classid ?>" class="class-filter-card">
                                        <div class="class-card-name"><?= esc_html($cls->className) ?></div>
                                        <div class="class-card-count"><?= $cls->app_count ?></div>
                                        <div class="class-card-label">Applications</div>
                                        <div class="class-card-stats">
                                            <span class="class-card-stat pending" title="Pending">⏳ <?= $cls->pending_count ?></span>
                                            <span class="class-card-stat paid" title="Paid">✓ <?= $cls->paid_count ?></span>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php elseif (empty($apps)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <p style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">No Applications Found</p>
                    <p>Try adjusting your filters or check back later.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <!-- <th class="checkbox-cell" style="visibility: hidden;">
                                    <input type="checkbox" id="selectAll" class="applicant-checkbox" title="Select All">
                                </th> -->
                                <th>App ID</th>
                                <th>Applicant</th>
                                <th>GPA</th>
                                <th>Class</th>
                                <th>Group</th>
                                <th>Section</th>
                                <th>Roll</th>
                                <th>Contact</th>
                                <!-- <th>Expected Fee</th> -->
                                <!-- <th>Paid</th> -->
                                <th>Applied</th>
                                <th>Status</th>
                                <!-- <th>Payment</th> -->
                                <th style="text-align: right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($apps as $row): ?>
                                <?php
                                // Status badge class
                                $statusClass = 'status-under-review';
                                $statusSlug = strtolower(str_replace(' ', '-', $row->approve_status));
                                if ($row->approve_status == 'Approved') $statusClass = 'status-approved';
                                if ($row->approve_status == 'Rejected') $statusClass = 'status-rejected';
                                if ($row->approve_status == 'Registered') $statusClass = 'status-registered';

                                // Payment badge class
                                $paymentClass = 'payment-pending';
                                if ($row->payment_status == 'Paid') $paymentClass = 'payment-paid';
                                if ($row->payment_status == 'Partial') $paymentClass = 'payment-partial';
                                ?>
                                <tr>
                                    <!-- <td class="checkbox-cell">
                                        <?php if ($row->approve_status != 'Rejected'): ?>
                                            <input type="checkbox" class="applicant-checkbox select-item"
                                                value="<?= $row->applicationid ?>"
                                                data-class="<?= $row->stdAdmitClass ?>">
                                        <?php endif; ?>
                                    </td> -->
                                    <td><strong>#<?= str_pad($row->applicationid, 4, '0', STR_PAD_LEFT) ?></strong></td>
                                    <td>
                                        <strong><?= esc_html($row->stdName) ?></strong><br>
                                        <small style="color: var(--text-muted)"><?= esc_html($row->stdFather) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($row->stdPreviousGPA !== null): ?>
                                            <strong><?= esc_html(number_format((float)$row->stdPreviousGPA, 2)) ?></strong>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= esc_html($row->className) ?><br>
                                        <small><?= esc_html($row->stdAdmitYear) ?></small>
                                    </td>
                                    <td><?= esc_html($row->groupName ?: '-') ?></td>
                                    <?php
                                    // Section color map (add more as needed)
                                    $sectionColors = [
                                        'A' => '#2563eb', // blue
                                        'B' => '#10b981', // green
                                        'C' => '#f59e42', // orange
                                        'D' => '#eab308', // yellow
                                        'E' => '#7e22ce', // purple
                                        'F' => '#ef4444', // red
                                    ];
                                    $sectionName = isset($row->sectionName) ? trim($row->sectionName) : '';
                                    $sectionColor = isset($sectionColors[$sectionName]) ? $sectionColors[$sectionName] : '#64748b';
                                    ?>
                                    <td>
                                        <?php if ($row->stdSection): ?>
                                            <span style="color: <?= $sectionColor ?>; font-weight: 700;">
                                                <?= esc_html($row->sectionName) ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted);">Not Set</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($row->stdRoll): ?>
                                            <strong style="color: <?= $sectionColor ?>; font-weight: 700;"><?= esc_html($row->stdRoll) ?></strong>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted);">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc_html($row->stdPhone) ?></td>
                                    <!-- <td><strong>৳<?= number_format($row->expected_fee ?: 0, 0) ?></strong></td> -->
                                    <!-- <td>
                                        <?php if ($row->payment_amount > 0): ?>
                                            <strong style="color: var(--success-color)">৳<?= number_format($row->payment_amount, 0) ?></strong>
                                        <?php else: ?>
                                            <span style="color: var(--text-muted)">-</span>
                                        <?php endif; ?>
                                    </td> -->
                                    <td><?= date('M j, Y', strtotime($row->stdCreatedAt)) ?></td>
                                    <td>
                                        <span class="status-badge <?= $statusClass ?>">
                                            <?= $row->studentid != 0 ? 'Registered' : esc_html($row->approve_status) ?>
                                        </span>
                                    </td>
                                    <!-- <td>
                                        <span class="payment-badge <?= $paymentClass ?>">
                                            <?= esc_html($row->payment_status) ?>
                                        </span>
                                    </td> -->
                                    <td style="text-align: right;">
                                        <div class="action-buttons" style="justify-content: flex-end; gap: 5px;">
                                            <?php
                                            // Prepare data for JS
                                            $rowData = htmlspecialchars(json_encode($row), ENT_QUOTES, 'UTF-8');

                                            // Build Register URL
                                            $registerUrl = home_url('/admin-student/?option=add&from_app=' . $row->applicationid);
                                            ?>
                                            <button type="button" class="btn btn-primary"
                                                style="padding: 6px 12px; font-size: 12px; background-color: #2563eb; color: white; display: inline-flex; align-items: center; gap: 4px;"
                                                onclick='openIndividualModal(<?= $rowData ?>)'>
                                                <span style="font-size: 14px; height: 14px;"></span>
                                                Review
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bulk Edit Modal -->
    <div class="modal-overlay" id="bulkModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Assign Section & Roll</h3>
                <button type="button" class="modal-close" onclick="closeBulkModal()">&times;</button>
            </div>
            <form method="POST" id="bulkEditForm">
                <div class="modal-body">
                    <div class="modal-form-group">
                        <label class="modal-label">Section</label>
                        <select name="bulk_section" id="bulkSection" class="modal-select">
                            <option value="">-- Loading sections... --</option>
                        </select>
                        <p class="modal-help-text">Select a section to assign to all selected applicants</p>
                    </div>

                    <div class="modal-form-group">
                        <label class="modal-label">Starting Roll Number</label>
                        <input type="number" name="starting_roll" id="startingRoll"
                            class="modal-input" placeholder="e.g., 1" min="1">
                        <p class="modal-help-text">Roll numbers will be assigned sequentially (1, 2, 3...)</p>
                    </div>

                    <div style="padding: 12px; background: #f8fafc; border-radius: 6px; margin-top: 15px;">
                        <p style="margin: 0; font-size: 13px; color: var(--text-muted);">
                            <strong>Selected:</strong> <span id="modalSelectedCount">0</span> applicant(s)
                        </p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeBulkModal()">Cancel</button>
                    <button type="submit" name="bulkUpdateSectionRoll" class="btn btn-primary">Apply Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Individual Edit Modal -->
    <div class="modal-overlay" id="individualModal">
        <div class="modal-content modal-lg">
            <div class="modal-header">
                <h3 class="modal-title">Review Applicant</h3>
                <button type="button" class="modal-close" style="font-size: 14px; width: 200px;" onclick="closeIndividualModal()">&times; (Click Outside to Close)</button>
            </div>
            <form method="POST" id="individualEditForm">
                <input type="hidden" name="updateIndividualApplicant" value="1">
                <input type="hidden" name="applicationid" id="ind_applicationid">

                <div class="modal-body" style="padding: 0;">
                    <div class="modal-body-split" style="display: flex; flex-direction: row; flex-wrap: wrap;">
                        <!-- Left Column: Summary & Registration -->
                        <div class="modal-col-left" style="flex: 1; min-width: 350px; border-right: 1px solid var(--border-color); padding: 30px; background: #fafafa;">
                            <h4 style="margin-top: 0; font-size: 14px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 20px;">Applicant Summary</h4>
                            
                            <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid var(--border-color); margin-bottom: 30px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                                <div style="display: flex; align-items: flex-start; gap: 20px;">
                                    <div id="ind_photo_container">
                                        <img id="ind_photo" src="" alt="Photo" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; background: #e2e8f0; border: 3px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                                    </div>
                                    <div style="flex: 1;">
                                        <h4 style="margin: 0 0 6px 0; font-size: 18px; color: var(--text-main); font-weight: 700;" id="ind_name">Student Name</h4>
                                        <div style="display: grid; grid-template-columns: 1fr; gap: 8px; font-size: 14px; color: var(--text-muted);">
                                            <div style="display: flex; align-items: center; gap: 6px;">
                                                <span style="font-weight: 600; color: var(--text-main);">ID:</span> #<span id="ind_id"></span>
                                                <span style="color: #cbd5e1;">|</span>
                                                <span style="font-weight: 600; color: var(--text-main);">Class:</span> <span id="ind_class"></span>
                                            </div>
                                            <div><span style="color: var(--text-muted);">Father:</span> <span id="ind_father" style="color: var(--text-main); font-weight: 500;"></span></div>
                                            <div><span style="color: var(--text-muted);">Phone:</span> <span id="ind_phone" style="color: var(--text-main); font-weight: 500;"></span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div style="border-top: 2px dashed var(--border-color); padding-top: 20px;">
                                
                                
                                <input type="hidden" name="approve_status" id="ind_status" value="<?php echo isset($row) ? $row->approve_status : 'Under Review'; ?>">
                                
                                <div style="margin: 15px 0;">
                                    <div style="margin-bottom: 10px;">
                                        <label for="rejectReason" style="display: block; margin-bottom: 5px; font-size: 13px; font-weight: 500; color: var(--text-muted);">
                                            Rejection Reason
                                        </label>
                                        <div style="display: flex; gap: 10px;">
                                            <input type="text" name="reject_reason" id="rejectReason" class="modal-input" 
                                                    placeholder="Enter reason for rejection" 
                                                    value="<?php echo isset($row) ? esc_attr($row->reject_reason) : ''; ?>"
                                                    style="flex-grow: 1; height: 36px; padding: 0 12px; border: 1px solid #e2e8f0; border-radius: 4px;">
                                            <button type="button" id="btnReject" class="btn btn-danger" 
                                                    style="white-space: nowrap; padding: 0 15px; height: 36px; display: flex; align-items: center; gap: 5px;">
                                                <span class="dashicons dashicons-dismiss" style="font-size: 14px;"></span>
                                                Reject
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                
                            </div>
                        </div>

                        <!-- Right Column: Settings Form -->
                        <div class="modal-col-right" style="flex: 1.2; min-width: 400px; padding: 30px;">
                            

                            <!-- <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 15px; margin-bottom: 25px;">
                                <div class="modal-form-group">
                                    <label class="modal-label">Payment Status</label>
                                    <select name="payment_status" id="ind_payment_status" class="modal-select" style="height: 45px; background: #fff;">
                                        <option value="Pending">Pending</option>
                                        <option value="Paid">Paid</option>
                                        <option value="Partial">Partial</option>
                                    </select>
                                </div>
                                <div class="modal-form-group">
                                    <label class="modal-label">Paid Amount (৳)</label>
                                    <input type="number" name="payment_amount" id="ind_payment_amount"
                                        class="modal-input" placeholder="0.00" style="height: 45px; background: #fff;" step="0.01">
                                </div>
                            </div> -->

                            <label class="modal-label" style="display: block; margin-bottom: 10px;">Application Status</label>
                            <div id="statusBreadcrumb" class="status-breadcrumb" style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 15px;"></div>

                            <div style="background: #f1f5f9; padding: 20px; border-radius: 12px; border: 1px solid var(--border-color);">
                                <p style="margin-top: 0; font-size: 12px; font-weight: 700; color: var(--primary-color); text-transform: uppercase; margin-bottom: 15px;">Admission Placement</p>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">                                
                                    <div class="modal-form-group" style="margin-bottom: 0;">
                                        <label class="modal-label">Year/Session</label>
                                        <select name="stdAdmitYear" id="ind_year" class="modal-select" style="height: 45px; background: #fff;"></select>
                                    </div>
                                    <div class="modal-form-group" style="margin-bottom: 0;">
                                        <label class="modal-label">Assign Section</label>
                                        <select name="stdSection" id="ind_section" class="modal-select" style="height: 45px; background: #fff;">
                                            <option value="">Select Section</option>
                                        </select>
                                    </div>
                                    <div class="modal-form-group" style="margin-bottom: 0;">
                                        <label class="modal-label">Roll Number</label>
                                        <input type="number" name="stdRoll" id="ind_roll" class="modal-input" placeholder="e.g. 101" style="height: 45px; background: #fff;">
                                    </div>
                                </div>

                                <style>
                                .action-section {
                                    width: 100%;
                                    margin-top: 50px;
                                }

                                .action-buttons {
                                    display: flex;
                                    align-items: center;
                                    justify-content: space-between;
                                    gap: 20px;
                                }

                                .action-buttons button {
                                    display: flex;
                                    align-items: center;
                                    gap: 8px;
                                    padding: 12px 18px;
                                    border-radius: 8px;
                                    font-weight: 600;
                                    font-size: 15px;
                                    cursor: pointer;
                                    transition: all 0.25s ease;
                                }

                                /* Approve Button */
                                .action-buttons button[type="submit"] {
                                    border: 1px solid #10b981;
                                    background: linear-gradient(135deg, #10b981, #059669);
                                    color: #fff;
                                }

                                .action-buttons button[type="submit"]:hover {
                                    background: linear-gradient(135deg, #059669, #047857);
                                    transform: translateY(-2px);
                                }

                                /* Approve & Register Button */
                                #btnDirectRegister {
                                    border: 1px solid #3b82f6;
                                    background: linear-gradient(135deg, #3b82f6, #2563eb);
                                    color: #fff;
                                }

                                #btnDirectRegister:hover {
                                    background: linear-gradient(135deg, #2563eb, #1d4ed8);
                                    transform: translateY(-2px);
                                }

                                .action-buttons span,
                                .action-buttons div span {
                                    font-size: 15px;
                                }

                                .action-buttons div p {
                                    font-size: 12px;
                                    margin: 0;
                                    opacity: 0.85;
                                }
                                </style>

                                <div class="action-section">
                                    <div class="action-buttons">
                                        <button type="submit" id="btnApprove">
                                            <span class="dashicons dashicons-yes"></span>
                                            <div style="text-align: left;">
                                                <span id="approveButtonText">Approve</span>
                                                <p>Update Application</p>
                                            </div>
                                        </button>

                                        <button type="button" id="btnDirectRegister">
                                            <span class="dashicons dashicons-database-add"></span>
                                            <div style="text-align: left;">
                                                <span>Approve & Register</span>
                                                <p>Save into students database</p>
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>


    <!-- Admission Config Modal -->
    <div class="modal-overlay" id="admissionConfigModal">
        <div class="modal-content modal-xl">
            <div class="modal-header">
                <h3 class="modal-title">Admission Dates & Fees Configuration</h3>
                <button type="button" class="modal-close" onclick="closeAdmissionConfigModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 20px;">
                    Configure admission fees and active date ranges for each class. These dates determine when applicants can apply or pay for specific classes.
                </p>
                <div class="table-responsive">
                    <table class="table" style="font-size: 12px;">
                        <thead>
                            <tr>
                                <th>Class Name</th>
                                <th>Amount</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th style="text-align: right;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="admissionConfigTableBody">
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 20px;">
                                    <div class="spinner">Loading...</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAdmissionConfigModal()">Close Configuration</button>
            </div>
        </div>
    </div>

    <script>
        (function($) {
            let selectedApplicants = [];

            // Select All checkbox
            $('#selectAll').on('change', function() {
                const isChecked = $(this).prop('checked');
                $('.select-item').prop('checked', isChecked);
                updateSelectedApplicants();
            });

            // Individual checkbox
            $(document).on('change', '.select-item', function() {
                updateSelectedApplicants();

                // Update "Select All" checkbox state
                const totalCheckboxes = $('.select-item').length;
                const checkedCheckboxes = $('.select-item:checked').length;
                $('#selectAll').prop('checked', totalCheckboxes === checkedCheckboxes);
            });

            // Update selected applicants array
            function updateSelectedApplicants() {
                selectedApplicants = [];
                $('.select-item:checked').each(function() {
                    selectedApplicants.push($(this).val());
                });

                const count = selectedApplicants.length;
                $('#selectedCount').text(count);
                $('#modalSelectedCount').text(count);

                if (count > 0) {
                    $('#bulkActionsBar').addClass('show');
                } else {
                    $('#bulkActionsBar').removeClass('show');
                }
            }

            // Open bulk modal
            window.openBulkModal = function() {
                if (selectedApplicants.length === 0) {
                    alert('Please select at least one applicant');
                    return;
                }

                // Check if class filter is applied
                const classFilter = $('select[name="filter_class"]').val();
                if (!classFilter) {
                    $('#classFilterRequired').slideDown();
                    setTimeout(function() {
                        $('#classFilterRequired').slideUp();
                    }, 5000);
                    return;
                }

                // Load sections for the filtered class
                loadSectionsForClass(classFilter, null, '#bulkSection');
                $('#bulkModal').addClass('show');
            };

            // Load sections for specific class - Direct SQL via same page
            function loadSectionsForClass(classId, selectedSectionId = null, targetSelector = '#ind_section') {
                $(targetSelector).html('<option value="">-- Loading sections... --</option>');

                $.ajax({
                    url: window.location.href.split('?')[0] + '?page_id=<?= get_the_ID() ?>',
                    method: 'POST',
                    data: {
                        ajax_get_sections_for_class: 1,
                        classId: classId
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('DEBUG loadSectionsForClass:', classId, response);
                        let options = '<option value="">Select Section</option>';
                        if (response.status === 'success' && response.sections.length > 0) {
                            response.sections.forEach(function(section) {
                                const isSelected = (selectedSectionId && section.sectionid == selectedSectionId) ? 'selected' : '';
                                options += `<option value="${section.sectionid}" ${isSelected}>${section.sectionName}</option>`;
                            });
                        } else {
                            options = '<option value="">No sections available for this class</option>';
                        }
                        $(targetSelector).html(options);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('Error loading sections:', textStatus, errorThrown);
                        $(targetSelector).html('<option value="">Error loading sections</option>');
                    }
                });
            }

            // Close bulk modal
            window.closeBulkModal = function() {
                $('#bulkModal').removeClass('show');
            };

            // Clear selection
            window.clearSelection = function() {
                $('.select-item').prop('checked', false);
                $('#selectAll').prop('checked', false);
                updateSelectedApplicants();
            };

            // Close modal on overlay click
            $('#bulkModal').on('click', function(e) {
                if (e.target.id === 'bulkModal') {
                    closeBulkModal();
                }
            });

            // Handle form submission
            $('#bulkEditForm').on('submit', function(e) {
                const section = $('#bulkSection').val();
                const roll = $('#startingRoll').val();

                if (!section && !roll) {
                    e.preventDefault();
                    alert('Please select a section or enter a starting roll number');
                    return;
                }

                if (!confirm(`Apply changes to ${selectedApplicants.length} applicant(s)?`)) {
                    e.preventDefault();
                    return;
                }

                // Add selected IDs as hidden inputs
                selectedApplicants.forEach(function(id) {
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'selected_applicants[]',
                        value: id
                    }).appendTo('#bulkEditForm');
                });
            });

            // Filter: Select all in current view
            window.selectAllInView = function() {
                $('.select-item').prop('checked', true);
                $('#selectAll').prop('checked', true);
                updateSelectedApplicants();
            };

            // Individual Modal Functions
            let currentApplicantData = null;
            // Helper to populate year/session dropdown
            function populateYearDropdown(selectedYear) {
                const $year = $('#ind_year');
                $year.empty();
                const currentYear = new Date().getFullYear();
                for (let y = currentYear + 1; y >= currentYear - 5; y--) {
                    $year.append(`<option value="${y}"${selectedYear == y ? ' selected' : ''}>${y}</option>`);
                }
            }

            window.openIndividualModal = function(data) {
                currentApplicantData = data;
                $('#ind_applicationid').val(data.applicationid);
                $('#ind_name').text(data.stdName);
                $('#ind_id').text(data.applicationid);
                $('#ind_class').text(data.className);
                $('#ind_father').text(data.stdFather);
                $('#ind_phone').text(data.stdPhone);

                if (data.stdImg) {
                    $('#ind_photo').attr('src', data.stdImg);
                } else {
                    $('#ind_photo').attr('src', 'https://via.placeholder.com/50?text=No+Img');
                }

                $('#ind_status').val(data.studentid != 0 ? 'Registered' : data.approve_status);
                $('#ind_payment_status').val(data.payment_status);
                $('#ind_payment_amount').val(data.payment_amount);
                $('#ind_roll').val(data.stdRoll);
                // Set reject reason field for this applicant only
                $('#rejectReason').val(data.reject_reason || '');


                // Load sections
                loadSectionsForClass(data.stdAdmitClass, data.stdSection, '#ind_section');

                // Populate year/session dropdown
                let admitYear = data.stdAdmitYear || new Date().getFullYear();
                populateYearDropdown(admitYear);

                // Update registration options
                const editUrl = `<?= home_url('/admin-student/') ?>?option=add&from_app=${data.applicationid}`;
                $('#btnEditRegister').attr('href', editUrl);

                // Hide/Show Direct Register button if already registered
                if (data.studentid != 0) {
                    $('#btnDirectRegister').hide();
                    $('#approveButtonText').text('Update');
                } else {
                    $('#btnDirectRegister').show();
                    $('#approveButtonText').text('Approve');
                }

                // Dynamically update status breadcrumb
                const statuses = ['Under Review', 'Approved', 'Registered'];
                let currentStatus = data.studentid != 0 ? 'Registered' : data.approve_status;
                let html = '';
                statuses.forEach(function(status, idx) {
                    let isCurrent = (currentStatus === status);
                    let statusClass = 'status-item';
                    if (isCurrent) statusClass += ' current';
                    html += `<div class="${statusClass}" style="padding: 8px 15px; border-radius: 20px; font-size: 13px; font-weight: 500; background: ${isCurrent ? 'var(--success-color)' : '#f1f5f9'}; color: ${isCurrent ? '#fff' : 'var(--text-muted)'}; display: flex; align-items: center; gap: 6px;">`;
                    if (isCurrent) html += '<span class="dashicons dashicons-yes" style="font-size: 16px; width: 16px; height: 16px;"></span>';
                    html += status + '</div>';
                    if (idx < statuses.length - 1) {
                        html += '<span style="color: #cbd5e1; font-size: 18px;">›</span>';
                    }
                });
                $('#statusBreadcrumb').html(html);

                $('#individualModal').addClass('show');
            };

            window.closeIndividualModal = function() {
                $('#individualModal').removeClass('show');
                currentApplicantData = null;
            };

            $('#individualModal').on('click', function(e) {
                if (e.target.id === 'individualModal') {
                    closeIndividualModal();
                }
            });

            $('#btnApprove').on('click', function(e) {
                if (!currentApplicantData) return;
                // Only allow status change to Approved if not registered
                if (currentApplicantData.studentid != 0) {
                    // If already registered, keep status as Registered
                    $('#ind_status').val('Registered');
                } else {
                    $('#ind_status').val('Approved');
                }

                // Set year/session value to hidden input (or add if missing)
                let year = $('#ind_year').val();
                let $yearInput = $('#individualEditForm input[name="stdAdmitYear"]');
                if ($yearInput.length === 0) {
                    $('<input>').attr({type: 'hidden', name: 'stdAdmitYear', value: year}).appendTo('#individualEditForm');
                } else {
                    $yearInput.val(year);
                }
            });

            $('#btnDirectRegister').on('click', function() {
                if (!currentApplicantData) return;
                
                const $btn = $(this);
                const originalHtml = $btn.html();

                $('#ind_section, #ind_roll, #ind_year').css('border-color', '');

                const section = $('#ind_section').val();
                const roll = $('#ind_roll').val();
                const year = $('#ind_year').val();
                let isValid = true;

                if (!section) {
                    $('#ind_section').css('border-color', 'var(--danger-color)');
                    isValid = false;
                }
                if (!roll) {
                    $('#ind_roll').css('border-color', 'var(--danger-color)');
                    isValid = false;
                }
                if (!year) {
                    $('#ind_year').css('border-color', 'var(--danger-color)');
                    isValid = false;
                }

                if (!isValid) {
                    return;
                }
                
                $btn.prop('disabled', true).text('Processing...');

                $.ajax({
                    url: window.location.href.split('?')[0] + '?page_id=<?= get_the_ID() ?>',
                    method: 'POST',
                    data: {
                        ajax_register_applicant: 1,
                        applicationid: currentApplicantData.applicationid,
                        stdSection: section,
                        stdRoll: roll,
                        stdAdmitYear: year
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                            $btn.prop('disabled', false).html(originalHtml);
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                });
            });

            $(document).on('click', '#btnReject', function() {
                if (!currentApplicantData) return;
                const reason = $('#rejectReason').val();
                const $btn = $(this);
                const originalHtml = $btn.html();
                if (!confirm('Are you sure you want to reject this applicant?')) {
                    return;
                }
                $btn.prop('disabled', true).text('Rejecting...');
                $.ajax({
                    url: window.location.href.split('?')[0] + '?page_id=<?= get_the_ID() ?>',
                    method: 'POST',
                    data: {
                        ajax_reject_applicant: 1,
                        applicationid: currentApplicantData.applicationid,
                        reject_reason: reason
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $('#ind_status').val('Rejected');
                        } else {
                            alert('Error: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'));
                            $btn.prop('disabled', false).html(originalHtml);
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false).html(originalHtml);
                    }
                });
            });

            // Admission Config Modal Functions
            window.openAdmissionConfigModal = function() {
                $('#admissionConfigModal').addClass('show');
                loadAdmissionConfigs();
            };

            window.closeAdmissionConfigModal = function() {
                $('#admissionConfigModal').removeClass('show');
            };

            function loadAdmissionConfigs() {
                const $tbody = $('#admissionConfigTableBody');
                $tbody.html('<tr><td colspan="5" style="text-align: center; padding: 20px;">Loading...</td></tr>');

                $.ajax({
                    url: window.location.href.split('?')[0] + '?page_id=<?= get_the_ID() ?>',
                    method: 'POST',
                    data: { ajax_get_admission_config: 1 },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            let html = '';
                            response.configs.forEach(function(config) {
                                html += `
                                    <tr data-class-id="${config.classid}">
                                        <td><strong>${config.className}</strong></td>
                                        <td>
                                            <input type="number" class="config-amount form-control" style="width: 100px; padding: 4px 8px;" value="${config.amount || 0}" step="0.01">
                                        </td>
                                        <td>
                                            <input type="date" class="config-start form-control" style="padding: 4px 8px;" value="${config.admission_start_date || ''}">
                                        </td>
                                        <td>
                                            <input type="date" class="config-end form-control" style="padding: 4px 8px;" value="${config.admission_end_date || ''}">
                                        </td>
                                        <td style="text-align: right;">
                                            <button type="button" class="btn btn-primary btn-sm save-config-btn" style="padding: 4px 8px; font-size: 11px;">
                                                Save
                                            </button>
                                        </td>
                                    </tr>
                                `;
                            });
                            $tbody.html(html);
                        } else {
                            $tbody.html('<tr><td colspan="5" style="text-align: center; color: var(--danger-color);">Error loading configurations.</td></tr>');
                        }
                    }
                });
            }

            $(document).on('click', '.save-config-btn', function() {
                const $btn = $(this);
                const $tr = $btn.closest('tr');
                const classId = $tr.data('class-id');
                const amount = $tr.find('.config-amount').val();
                const startDate = $tr.find('.config-start').val();
                const endDate = $tr.find('.config-end').val();

                $btn.prop('disabled', true).text('Saving...');

                $.ajax({
                    url: window.location.href.split('?')[0] + '?page_id=<?= get_the_ID() ?>',
                    method: 'POST',
                    data: {
                        ajax_save_admission_config: 1,
                        class_id: classId,
                        amount: amount,
                        start_date: startDate,
                        end_date: endDate
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status === 'success') {
                            $btn.text('Saved!').css('background', 'var(--success-color)');
                            setTimeout(() => {
                                $btn.prop('disabled', false).text('Save').css('background', '');
                            }, 2000);
                        } else {
                            alert('Error: ' + response.message);
                            $btn.prop('disabled', false).text('Save');
                        }
                    },
                    error: function() {
                        alert('Server error occurred');
                        $btn.prop('disabled', false).text('Save');
                    }
                });
            });

        })(jQuery);
    </script>

    <?php get_footer(); ?>