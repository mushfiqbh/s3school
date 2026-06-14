<?php
/*
 * Template Name: Student Application
 */

global $wpdb;

/*=================
    Handle AJAX Requests (Direct SQL - no ajaxAction.php)
=================*/

// AJAX: Search Application by Phone
if (isset($_POST['ajax_search_application'])) {
    header('Content-Type: application/json');
    
    $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    $dob = isset($_POST['dob']) ? sanitize_text_field($_POST['dob']) : '';
    
    if (empty($phone) || strlen($phone) !== 11) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid phone number', 'debug' => 'invalid_phone']);
        exit;
    }
    
    if (empty($dob)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid date of birth', 'debug' => 'invalid_dob']);
        exit;
    }
    
    try {
        $applications = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, c.className, s.sectionName  
             FROM ct_online_application a 
             LEFT JOIN ct_class c ON a.stdAdmitClass = c.classid 
             LEFT JOIN ct_section s ON a.stdSection = s.sectionid
             WHERE a.stdPhone = %s AND a.stdBrith = %s 
             ORDER BY a.applicationid DESC",
            $phone, $dob
        ));
        
        if ($wpdb->last_error) {
            echo json_encode(['status' => 'error', 'message' => 'Database error', 'debug' => 'db_error', 'error' => $wpdb->last_error]);
            exit;
        }
        
        if ($applications && count($applications) > 0) {
            echo json_encode(['status' => 'success', 'data' => $applications, 'debug' => 'found', 'count' => count($applications)]);
        } else {
            echo json_encode(['status' => 'not_found', 'data' => [], 'debug' => 'not_found']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Exception occurred', 'debug' => 'exception', 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Get Application Details by ID
if (isset($_POST['ajax_get_application'])) {
    header('Content-Type: application/json');
    
    $appId = isset($_POST['applicationId']) ? intval($_POST['applicationId']) : 0;
    
    if ($appId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid application ID']);
        exit;
    }
    
    try {
        $application = $wpdb->get_row($wpdb->prepare(
            "SELECT a.*, c.className, s.sectionName
             FROM ct_online_application a 
             LEFT JOIN ct_class c ON a.stdAdmitClass = c.classid 
             LEFT JOIN ct_section s ON a.stdSection = s.sectionid
             WHERE a.applicationid = %d",
            $appId
        ));
        
        if ($wpdb->last_error) {
            echo json_encode(['status' => 'error', 'message' => 'Database error', 'error' => $wpdb->last_error]);
            exit;
        }
        
        if ($application) {
            // Get admission fee and check if admission is open
            $feeData = $wpdb->get_row($wpdb->prepare(
                "SELECT amount, admission_end_date,
                 CASE WHEN CURDATE() BETWEEN admission_start_date AND admission_end_date THEN 1 ELSE 0 END as admissionOpen
                 FROM ct_admission_fee_promoted 
                 WHERE class = %d 
                 ORDER BY admission_end_date DESC LIMIT 1",
                $application->stdAdmitClass
            ));
            
            $application->admissionFee = $feeData ? $feeData->amount : 0;
            $application->admissionOpen = $feeData ? (bool)$feeData->admissionOpen : false;
            $application->admissionEndDate = $feeData ? $feeData->admission_end_date : null;
            
            echo json_encode(['status' => 'success', 'data' => $application]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Application not found']);
        }
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Exception occurred', 'error' => $e->getMessage()]);
    }
    exit;
}


// AJAX: Check if Class has Groups
if (isset($_POST['ajax_has_group'])) {
    header('Content-Type: text/plain');
    
    $classId = isset($_POST['classId']) ? intval($_POST['classId']) : 0;
    
    if ($classId <= 0) {
        echo 'false';
        exit;
    }
    
    try {
        // Classes 9, 10, 11, 12 typically have groups (Science, Arts, Commerce)
        // Or check from database if there's a mapping
        $hasGroup = in_array($classId, [68, 69, 71, 72]); // Adjust based on your class IDs
        echo $hasGroup ? 'true' : 'false';
    } catch (Exception $e) {
        echo 'false';
    }
    exit;
}

// AJAX: Get Sections for Class
if (isset($_POST['ajax_get_sections'])) {
    header('Content-Type: text/html');
    
    $classId = isset($_POST['classId']) ? intval($_POST['classId']) : 0;
    
    if ($classId <= 0) {
        echo '<option value="">No sections available</option>';
        exit;
    }
    
    try {
        // Note: Column is 'forClass' not 'class'
        $sections = $wpdb->get_results($wpdb->prepare(
            "SELECT sectionid, sectionName FROM ct_section WHERE forClass = %d ORDER BY sectionName",
            $classId
        ));
        
        $html = '<option value="">Select Section</option>';
        if ($sections) {
            foreach ($sections as $section) {
                $html .= '<option value="' . esc_attr($section->sectionid) . '">' . esc_html($section->sectionName) . '</option>';
            }
        }
        echo $html;
    } catch (Exception $e) {
        echo '<option value="">Error loading sections</option>';
    }
    exit;
}

/*=================
    Handle Submission (Before any output for PRG redirect)
=================*/
$message = null;
$current_app_id = isset($_GET['app_id']) ? intval($_GET['app_id']) : null;
$is_update = false;

// Handle POST submission first (before get_header for redirect capability)
if (isset($_POST['submitApplication'])) {
    // Backward compatibility
    if (!isset($_POST['stdAdmitYear']) && isset($_POST['stdCurntYear'])) {
        $_POST['stdAdmitYear'] = $_POST['stdCurntYear'];
    }

    // Check if this is an explicit update (has applicationId)
    $explicit_update = isset($_POST['applicationId']) && !empty($_POST['applicationId']);
    $application_id = $explicit_update ? intval($_POST['applicationId']) : null;
    
    // Auto-detect existing application by phone, DOB, and class (for upsert)
    $phone = sanitize_text_field($_POST['stdPhone']);
    $dob = sanitize_text_field($_POST['stdBrith']);
    $class_id = intval($_POST['stdAdmitClass']);
    
    // Check if trying to update a registered application (not allowed)
    if ($explicit_update) {
        $existing_status = $wpdb->get_var($wpdb->prepare(
            "SELECT approve_status FROM ct_online_application WHERE applicationid = %d",
            $application_id
        ));
        if ($existing_status === 'Registered') {
            $message = array('status' => 'error', 'text' => 'Cannot update a registered application. Please contact the office for any changes.');
            goto skip_submission;
        }
    }
    
    if (!$explicit_update) {
        // Check if application already exists with same phone, DOB, and class
        $existing_app = $wpdb->get_row($wpdb->prepare(
            "SELECT applicationid, approve_status FROM ct_online_application 
             WHERE stdPhone = %s AND stdBrith = %s AND stdAdmitClass = %d",
            $phone, $dob, $class_id
        ));
        
        if ($existing_app) {
            // Check if existing application is registered (cannot update)
            if ($existing_app->approve_status === 'Registered') {
                $message = array('status' => 'error', 'text' => 'An application with this information is already registered. Please contact the office for any changes.');
                goto skip_submission;
            }
            // Found existing application - switch to update mode
            $application_id = $existing_app->applicationid;
            $is_update = true;
        }
    } else {
        $is_update = true;
    }

    // Handle image upload
    $uploaded_image_url = '';
    if (isset($_FILES['stdImg']) && $_FILES['stdImg']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = wp_upload_dir();
        $student_upload_dir = $upload_dir['basedir'] . '/student-photos/';
        
        // Create directory if it doesn't exist
        if (!file_exists($student_upload_dir)) {
            wp_mkdir_p($student_upload_dir);
        }
        
        $file_name = $_FILES['stdImg']['name'];
        $file_tmp = $_FILES['stdImg']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif');
        
        if (in_array($file_ext, $allowed_extensions)) {
            $new_file_name = uniqid('student_') . '_' . time() . '.' . $file_ext;
            $file_path = $student_upload_dir . $new_file_name;
            
            if (move_uploaded_file($file_tmp, $file_path)) {
                $uploaded_image_url = $upload_dir['baseurl'] . '/student-photos/' . $new_file_name;
            }
        }
    }

    // Sanitize and Prepare Data
    $data = array(
        'studentid' => 0,
        'stdName' => sanitize_text_field($_POST['stdName']),
        'stdNameBangla' => sanitize_text_field($_POST['stdNameBangla'] ?? ''),
        'stdGender' => sanitize_text_field($_POST['stdGender']),
        'stdBldGrp' => sanitize_text_field($_POST['stdBldGrp'] ?? ''),
        'facilities' => sanitize_text_field($_POST['facilities'] ?? ''),
        'stdImg' => !empty($uploaded_image_url) ? esc_url_raw($uploaded_image_url) : esc_url_raw($_POST['stdImg_existing'] ?? ''),
        'stdFather' => sanitize_text_field($_POST['stdFather']),
        'fatherLate' => isset($_POST['fatherLate']) ? 1 : 0,
        'stdFatherProf' => sanitize_text_field($_POST['stdFatherProf'] ?? ''),
        'stdMother' => sanitize_text_field($_POST['stdMother']),
        'motherLate' => isset($_POST['motherLate']) ? 1 : 0,
        'stdMotherProf' => sanitize_text_field($_POST['stdMotherProf'] ?? ''),
        'stdParentIncome' => intval($_POST['stdParentIncome'] ?? 0),
        'stdlocalGuardian' => sanitize_text_field($_POST['stdlocalGuardian'] ?? ''),
        'stdGuardianNID' => sanitize_text_field($_POST['stdGuardianNID'] ?? ''),
        'stdPhone' => $phone,
        'stdPermanent' => sanitize_textarea_field($_POST['stdPermanent'] ?? ''),
        'stdPresent' => sanitize_textarea_field($_POST['stdPresent'] ?? ''),
        'stdBrith' => $dob,
        'stdNationality' => sanitize_text_field($_POST['stdNationality'] ?? 'Bangladeshi'),
        'stdReligion' => sanitize_text_field($_POST['stdReligion']),
        'stdAdmitClass' => $class_id,
        'stdAdmitYear' => sanitize_text_field($_POST['stdAdmitYear']),
        'stdGroup' => isset($_POST['stdGroup']) && !empty($_POST['stdGroup']) ? intval($_POST['stdGroup']) : null,
        'stdSection' => isset($_POST['stdSection']) && !empty($_POST['stdSection']) ? intval($_POST['stdSection']) : null,
        'stdRoll' => isset($_POST['stdRoll']) ? sanitize_text_field($_POST['stdRoll']) : null,
        'stdTcNumber' => isset($_POST['stdTcNumber']) ? sanitize_text_field($_POST['stdTcNumber']) : null,
        'sscRoll' => isset($_POST['sscRoll']) ? sanitize_text_field($_POST['sscRoll']) : null,
        'sscReg' => isset($_POST['sscReg']) ? sanitize_text_field($_POST['sscReg']) : null,
        'stdPrevSchool' => isset($_POST['stdPrevSchool']) ? sanitize_text_field($_POST['stdPrevSchool']) : null,
        'stdGPA' => isset($_POST['stdGPA']) ? sanitize_text_field($_POST['stdGPA']) : null,
        'stdIntellectual' => isset($_POST['stdIntellectual']) ? sanitize_text_field($_POST['stdIntellectual']) : null,
        'stdScholarsClass' => isset($_POST['stdScholarsClass']) ? sanitize_text_field($_POST['stdScholarsClass']) : null,
        'stdScholarsYear' => isset($_POST['stdScholarsYear']) ? sanitize_text_field($_POST['stdScholarsYear']) : null,
        'stdScholarsMemo' => isset($_POST['stdScholarsMemo']) ? sanitize_text_field($_POST['stdScholarsMemo']) : null,
        'stdStatus' => 1,
        'paymentPaid' => isset($_POST['paymentPaid']) ? sanitize_text_field($_POST['paymentPaid']) : null,
        'paymentDue' => isset($_POST['paymentDue']) ? sanitize_text_field($_POST['paymentDue']) : null,
        'stdNote' => isset($_POST['stdNote']) ? sanitize_textarea_field($_POST['stdNote']) : null,
        'birth_reg_no' => isset($_POST['birth_reg_no']) ? sanitize_text_field($_POST['birth_reg_no']) : '',
        'approve_status' => 'Under Review'
    );

    if (!$is_update) {
        $data['stdCreatedAt'] = current_time('mysql');
    }

    if ($is_update) {
        // Update existing application - don't overwrite status fields
        unset($data['approve_status']);
        unset($data['payment_status']);

        // If stdAdmitClass is not set or is 0, preserve the original value from DB
        if (!isset($_POST['stdAdmitClass']) || empty($_POST['stdAdmitClass']) || intval($_POST['stdAdmitClass']) === 0) {
            $current_class = $wpdb->get_var($wpdb->prepare(
                "SELECT stdAdmitClass FROM ct_online_application WHERE applicationid = %d",
                $application_id
            ));
            if ($current_class) {
                $data['stdAdmitClass'] = $current_class;
            }
        }

        $result = $wpdb->update('ct_online_application', $data, array('applicationid' => $application_id));
        if ($result === false) {
            error_log('Online application update error: ' . $wpdb->last_error);
            $message = array('status' => 'error', 'text' => 'Update failed. Please try again or contact the office.');
        } else {
            // PRG Pattern: Redirect to prevent duplicate submission on refresh
            $redirect_url = add_query_arg(array(
                'app_id' => $application_id,
                'status' => 'updated'
            ), get_permalink());
            wp_redirect($redirect_url);
            exit;
        }
    } else {
        // Insert new application
        $result = $wpdb->insert('ct_online_application', $data);
        if ($result === false) {
            error_log('Online application insert error: ' . $wpdb->last_error);
            $message = array('status' => 'error', 'text' => 'Submission failed. Please try again or contact the office.');
        } else {
            $submitted_app_id = $wpdb->insert_id;
            
            // PRG Pattern: Redirect to prevent duplicate submission on refresh
            $redirect_url = add_query_arg(array(
                'app_id' => $submitted_app_id,
                'status' => 'success'
            ), get_permalink());
            
            wp_redirect($redirect_url);
            exit;
        }
    }
    
    skip_submission: // Label for goto when registered application tries to update
}

// Handle GET request messages after redirect (PRG pattern)
if (isset($_GET['status'])) {
    if ($_GET['status'] === 'success') {
        $message = array('status' => 'success', 'text' => 'Application submitted successfully!');
    } elseif ($_GET['status'] === 'updated') {
        $message = array('status' => 'success', 'text' => 'Application updated successfully!');
    }
}

// Pre-load application data for PHP (to avoid selection issues on initial render)
$application_data = null;
if ($current_app_id) {
    $application_data = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM ct_online_application WHERE applicationid = %d",
        $current_app_id
    ));
}

get_header();
?>

<style>
    :root {
        --primary-color: #2563eb;
        --secondary-color: #1e40af;
        --success-color: #10b981;
        --warning-color: #f59e0b;
        --bg-color: #f8fafc;
        --card-bg: #ffffff;
        --text-main: #1e293b;
        --text-muted: #64748b;
        --border-color: #e2e8f0;
    }

    body {
        background-color: var(--bg-color);
        color: var(--text-main);
        font-family: 'Inter', sans-serif;
    }

    .app-container {
        max-width: 1000px;
        margin: 20px auto;
        padding: 0 15px;
    }

    .app-card {
        background: var(--card-bg);
        border-radius: 10px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        padding: 20px;
        margin-bottom: 20px;
    }

    @media screen and (max-width: 600px) {
        .app-card {
            padding: 15px 10px;
        }
    }

    .page-header {
        text-align: center;
        margin-bottom: 30px;
    }

    .page-header h1 {
        color: var(--primary-color);
        font-weight: 700;
        font-size: 26px;
        margin-bottom: 5px;
    }

    .page-header p {
        color: var(--text-muted);
        font-size: 14px;
    }

    .section-header {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--border-color);
    }

    .section-number {
        background: var(--primary-color);
        color: white;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 15px;
    }

    .section-title {
        font-size: 18px;
        font-weight: 600;
        color: var(--text-main);
    }

    /* Search Section */
    .search-box {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    @media screen and (max-width: 600px) {
        .search-box {
            flex-direction: column;
        }        
    }

    .search-input {
        flex: 1;
        padding: 10px 14px;
        border: 2px solid var(--border-color);
        border-radius: 6px;
        font-size: 15px;
        transition: border-color 0.2s;
    }

    .search-input:focus {
        border-color: var(--primary-color);
        outline: none;
    }

    .btn {
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s;
        border: none;
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

    .btn-success {
        background: var(--success-color);
        color: white;
    }

    .btn-outline {
        background: transparent;
        border: 2px solid var(--primary-color);
        color: var(--primary-color);
    }

    /* Class Cards */
    .class-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 15px;
    }

    .class-card {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        padding: 20px 15px;
        border-radius: 10px;
        text-align: center;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
        position: relative;
    }

    .class-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(37, 99, 235, 0.2);
    }

    .class-card.selected {
        background: linear-gradient(135deg, var(--success-color), #059669);
    }

    .class-card.selected::after {
        content: '✓';
        position: absolute;
        top: 8px;
        right: 8px;
        width: 22px;
        height: 22px;
        background: white;
        color: var(--success-color);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 700;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
    }

    .class-card.disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .class-card.disabled.selected {
        opacity: 1;
    }

    .class-name {
        font-size: 18px;
        font-weight: 700;
        margin-bottom: 5px;
    }

    .class-fee {
        font-size: 13px;
        opacity: 0.9;
    }

    /* Form Styles */
    .form-section {
        margin-bottom: 25px;
    }

    .form-section:not(.active) .section-subtitle,
    .form-section:not(.active) .row,
    .form-section:not(.active) .form-group,
    .form-section:not(.active) form > *:not(.section-subtitle):not(.alert) {
        display: none;
    }

    .form-section:not(.active)::after {
        content: 'Select a class above to fill the application form';
        display: block;
        text-align: center;
        padding: 30px 15px;
        color: var(--text-muted);
        font-size: 15px;
    }

    .form-section.active {
        display: block;
    }

    /* Completed step styling */
    .section-number {
        transition: all 0.3s ease;
    }

    .section-subtitle {
        margin-top: 25px;
        font-size: 15px;
        font-weight: 600;
        color: var(--secondary-color);
        margin-bottom: 12px;
        padding-bottom: 8px;
        border-bottom: 1px solid var(--border-color);
    }

    .form-group {
        margin-bottom: 12px;
    }

    .form-label {
        display: block;
        font-weight: 500;
        margin-bottom: 5px;
        color: var(--text-main);
        font-size: 14px;
    }

    .form-control {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid var(--border-color);
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.2s, box-shadow 0.2s;
        box-sizing: border-box;
    }

    input.form-control:not([type="checkbox"]):not([type="radio"]), 
    select.form-control {
        height: 40px;
    }

    textarea.form-control {
        height: auto;
    }

    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        outline: none;
    }

    .alert {
        padding: 10px 15px;
        border-radius: 6px;
        margin-bottom: 15px;
        font-weight: 500;
        font-size: 14px;
    }

    .alert-success {
        background-color: #dcfce7;
        color: #166534;
        border: 1px solid #bbf7d0;
    }

    .alert-error {
        background-color: #fee2e2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }

    .alert-info {
        background-color: #dbeafe;
        color: #1e40af;
        border: 1px solid #bfdbfe;
    }

    /* Payment Section */
    .payment-card {
        background: linear-gradient(135deg, #f0fdf4, #dcfce7);
        border: 2px solid var(--success-color);
        border-radius: 10px;
        padding: 20px;
        text-align: center;
    }

    .payment-amount {
        font-size: 36px;
        font-weight: 700;
        color: var(--success-color);
        margin: 15px 0;
    }

    .payment-details {
        background: white;
        border-radius: 6px;
        padding: 15px;
        margin: 15px 0;
        text-align: left;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid var(--border-color);
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .hidden {
        display: none;
    }

    .loading {
        opacity: 0.6;
        pointer-events: none;
    }

    /* Print Styles - A4 Size Optimization */
    @media print {
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
        
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            box-sizing: border-box !important;
        }
        
        html, body {
            width: 100% !important;
            height: auto !important;
            margin: 0 !important;
            padding: 0 !important;
            font-size: 11pt !important;
            background: white !important;
        }
        
        /* Hide everything by default */
        body > *:not(#print-container) {
            display: none !important;
            visibility: hidden !important;
        }
        
        /* Show the print container */
        #print-container {
            display: block !important;
            visibility: visible !important;
            position: static !important;
            width: 100% !important;
            margin: 0 !important;
            padding: 5mm !important;
            background: white !important;
        }
        
        #print-container * {
            visibility: visible !important;
        }
        
        /* Make sure slip container displays properly with margins */
        .slip-container {
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 auto !important;
            padding: 5mm !important;
            background: white !important;
            box-sizing: border-box !important;
        }
        
        /* Ensure tables print correctly */
        table {
            display: table !important;
            width: 100% !important;
            border-collapse: collapse !important;
        }
        tr {
            display: table-row !important;
        }
        td {
            display: table-cell !important;
        }
        
        /* Allow content to flow to multiple pages */
        .slip-container, table, tr {
            page-break-inside: auto !important;
        }
    }
        
    .search-box {
        display: flex;
        gap: 10px;
        align-items: flex-center;
    }

    .form-group {
        position: relative;
        flex: 1;
    }

    .form-group input {
        width: 100%;
        padding: 12px 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
        background: none;
        font-size: 14px;
    }

    .form-group label {
        position: absolute;
        top: -8px;
        left: 10px;
        background: #fff;
        padding: 0 4px;
        font-size: 12px;
        color: #555;
        pointer-events: none;
    }

    .btn {
        padding: 10px 16px;
        font-size: 14px;
    }

    .row {
        margin-bottom: 20px;
    }
</style>

<div class="app-container">
    
    <div class="page-header">
        <h1>Student Admission Portal</h1>
        <p>Complete your application in simple steps</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-<?= $message['status'] == 'error' ? 'error' : 'success' ?>" id="statusMessage">
            <?= $message['text'] ?>
        </div>
    <?php endif; ?> 

    <!-- Section 1: Search Existing Applications -->
    <div class="app-card" id="search-section">
        <div class="section-header">
            <div class="section-number">1</div>
            <div class="section-title">Already Applied? Search Your Application</div>
        </div>

        <div class="search-box">
            <div class="form-group" style="width: 100%;">
                <label for="phoneSearch">Phone Number</label>
                <input 
                    type="tel" 
                    id="phoneSearch" 
                    class="search-input" 
                    placeholder="017xxxxxxxx"
                    pattern="[0-9]{11}"
                >
            </div>
            <div class="form-group" style="width: 100%;">
                <label for="dobSearch">Date of Birth</label>
                <input 
                    type="date" 
                    id="dobSearch" 
                    class="search-input"
                >
            </div>
            <button class="btn btn-primary" onclick="searchApplication()">
                <span class="dashicons dashicons-search"></span> Search
            </button>
        </div>

        <div id="searchResults" class="hidden"></div>
    </div>

    <!-- Section 2: Application Form -->
    <div class="app-card form-section active" id="form-section">
        <div class="section-header">
            <div class="section-number" id="form-section-number">2</div>
            <div class="section-title">New/Update Application Form</div>
        </div>

        <div class="alert alert-info" id="selectedClassInfo" style="display:none;">
            <strong>Selected Class:</strong> <span id="selectedClassName"></span> | 
            <strong>Admission Fee:</strong> ৳<span id="selectedClassFee"></span>
        </div>

        <form method="POST" id="applicationForm" enctype="multipart/form-data">
            <input type="hidden" name="applicationId" id="applicationId">
            <input type="hidden" name="stdImg_existing" id="stdImg_existing">

            <!-- Academic Information -->
            <div class="section-subtitle">Academic Information</div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Admission Class *</label>
                        <select name="stdAdmitClass" id="stdAdmitClass" class="form-control" required>
                            <option value="">Select Class...</option>
                            <?php
                            $classes = $wpdb->get_results("
                                SELECT c.classid, c.className, COALESCE(f.amount, 0) as fee
                                FROM ct_class c
                                INNER JOIN ct_admission_fee_promoted f ON c.classid = f.class AND CURDATE() BETWEEN f.admission_start_date AND f.admission_end_date
                                ORDER BY c.classid ASC
                            ");
                            
                            $current_selection = $application_data ? $application_data->stdAdmitClass : '';
                            
                            foreach ($classes as $class) {
                                $display = $class->className;
                                $selected = ($current_selection == $class->classid) ? 'selected' : '';
                                echo '<option value="' . $class->classid . '" data-fee="' . $class->fee . '" data-name="' . esc_attr($class->className) . '" ' . $selected . '>' . esc_html($display) . '</option>';
                            }
                            
                            // If admission is closed but application exists, add it as a selected option
                            if ($application_data) {
                                $found = false;
                                foreach ($classes as $c) {
                                    if ($c->classid == $application_data->stdAdmitClass) { $found = true; break; }
                                }
                                if (!$found) {
                                    $className = $wpdb->get_var($wpdb->prepare("SELECT className FROM ct_class WHERE classid = %d", $application_data->stdAdmitClass));
                                    echo '<option value="' . $application_data->stdAdmitClass . '" selected>' . esc_html($className) . ' (Closed)</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Admission Year *</label>
                        <select class="form-control" name="stdAdmitYear" required>
                            <?php
                            $currentYear = date('Y');
                            $savedYear = $application_data ? $application_data->stdAdmitYear : $currentYear;
                            $years = [$currentYear, $currentYear + 1, $currentYear - 1]; // Added previous year just in case
                            sort($years);
                            foreach (array_unique($years) as $y) {
                                $selected = ($y == $savedYear) ? 'selected' : '';
                                echo "<option value='{$y}' {$selected}>{$y}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <!-- Group selection (shown for classes with groups) -->
                    <div class="form-group">
                        <label class="form-label">Group *</label>
                        <select id="groupSelect" class="form-control" name="stdGroup">
                            <option value="">Select Group...</option>
                            <?php
                            $groups = $wpdb->get_results("SELECT * FROM ct_group ORDER BY groupName");
                            $savedGroup = $application_data ? $application_data->stdGroup : '';
                            echo "<option value='0' " . (($savedGroup === '0' || $savedGroup === 0) ? 'selected' : '') . ">No Group (Class 6, 7, 8)</option>";
                            foreach ($groups as $group) {
                                $selected = ($savedGroup == $group->groupId) ? 'selected' : '';
                                echo "<option value='{$group->groupId}' {$selected}>{$group->groupName}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Student Information -->
            <div class="section-subtitle">Student Information</div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Student Name (English) *</label>
                        <input class="form-control" type="text" name="stdName" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Student Name (Bangla)</label>
                        <input class="form-control" type="text" name="stdNameBangla" placeholder="বাংলায় নাম">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Gender *</label>
                        <select class="form-control" name="stdGender" required>
                            <option value="1">Boy</option>
                            <option value="0">Girl</option>
                            <option value="2">Other</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Religion *</label>
                        <select class="form-control" name="stdReligion" required>
                            <option value="Muslim">Muslim</option>
                            <option value="Hinduism">Hinduism</option>
                            <option value="Christian">Christian</option>
                            <option value="Buddhist">Buddhist</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Blood Group</label>
                        <select class="form-control" name="stdBldGrp">
                            <option value="">Select...</option>
                            <option value="A+">A+</option>
                            <option value="A-">A-</option>
                            <option value="B+">B+</option>
                            <option value="B-">B-</option>
                            <option value="AB+">AB+</option>
                            <option value="AB-">AB-</option>
                            <option value="O+">O+</option>
                            <option value="O-">O-</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Date of Birth *</label>
                        <input class="form-control" type="date" name="stdBrith" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label">Birth Reg No *</label>
                        <input class="form-control" type="text" name="birth_reg_no" placeholder="Birth Registration Number" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Nationality</label>
                        <input class="form-control" type="text" name="stdNationality" value="Bangladeshi">
                    </div>
                </div>
            </div>

            <div class="form-group" style="width: fit-content;">
                <label class="form-label">Student Photo</label>
                <input class="form-control" type="file" name="stdImg" id="stdImgUpload" accept="image/jpeg,image/png,image/gif,image/jpg">
                <small class="form-text text-muted">Allowed formats: JPG, JPEG, PNG, GIF (Max 5MB)</small>
                <div id="imagePreview" style="margin-top: 10px; display: none;">
                    <img id="previewImg" src="" alt="Preview" style="max-width: 200px; max-height: 200px; border: 1px solid #ddd; border-radius: 4px; padding: 5px;">
                    <button type="button" class="btn btn-sm btn-danger" id="removeImage" style="display: block; margin-top: 5px;">Remove Image</button>
                </div>
            </div>

            <!-- Guardian Information -->
            <div class="section-subtitle">Guardian Information</div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Father's Name *</label>
                        <input class="form-control" type="text" name="stdFather" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Father's Profession</label>
                        <input class="form-control" type="text" name="stdFatherProf">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Mother's Name *</label>
                        <input class="form-control" type="text" name="stdMother" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Mother's Profession</label>
                        <input class="form-control" type="text" name="stdMotherProf">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Contact Phone *</label>
                        <input class="form-control" type="tel" name="stdPhone" required placeholder="017XXXXXXXX" pattern="[0-9]{11}">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Guardian NID</label>
                        <input class="form-control" type="text" name="stdGuardianNID">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Local Guardian Name</label>
                        <input class="form-control" type="text" name="stdlocalGuardian">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Parent Income (Monthly)</label>
                        <input class="form-control" type="number" name="stdParentIncome" placeholder="Amount in Taka">
                    </div>
                </div>
            </div>

            <!-- Address -->
            <div class="section-subtitle">Address</div>
            <div class="row">
                <div  class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Present Address</label>
                        <textarea class="form-control" name="stdPresent" rows="3"></textarea>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Permanent Address</label>
                        <textarea class="form-control" name="stdPermanent" rows="3"></textarea>
                    </div>
                </div>
            </div>

            <!-- Previous Academic Info (Optional) -->
            <div class="section-subtitle">Previous Academic Info (Optional)</div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Previous School</label>
                        <input class="form-control" type="text" name="stdPrevSchool">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label">Previous GPA</label>
                        <input class="form-control" type="text" name="stdGPA">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="form-label">TC Number</label>
                        <input class="form-control" type="text" name="stdTcNumber">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">JSC/SSC Roll</label>
                        <input class="form-control" type="text" name="sscRoll">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">JSC/SSC Registration</label>
                        <input class="form-control" type="text" name="sscReg">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Additional Notes</label>
                <textarea class="form-control" name="stdNote" rows="3" placeholder="Any special requirements or notes..."></textarea>
            </div>

            <div class="form-group" style="text-align: right; margin-top: 20px;">
                <button type="submit" name="submitApplication" class="btn btn-primary" id="submitBtn">
                    <span id="submitBtnText">Submit Application</span> →
                </button>
            </div>
        </form>
    </div>

    <!-- Section 3: Application Confirmation -->
    <div class="app-card" id="confirmation-section">
        <div class="section-header">
            <div class="section-number">3</div>
            <div class="section-title">Application Confirmation</div>
        </div>
        <div class="section-content">
            <div id="admission-fee-details" class="text-center">
                <p>Admission Fee: <span id="display-fee">-</span></p>
                <p>Please contact the school office for further payment instructions.</p>
                <button onclick="printApplication()" class="btn btn-primary" style="margin-top: 15px;">
                    <span class="dashicons dashicons-printer"></span> Print Application Slip
                </button>
            </div>
            <div id="printable-slip" style="display: none; margin-top: 20px; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
                <!-- Slip content will be dynamically inserted here -->
            </div>
        </div>
    </div>

</div>

<script type="text/javascript">
(function() {
    // Hide web URL and page number in print
    const style = document.createElement('style');
    style.innerHTML = `@media print {
        @page { margin: 0; }
        body { -webkit-print-color-adjust: exact; }
        a[href]:after { content: none !important; }
        #print-container { box-shadow: none !important; }
        /* Hide header/footer (URL, page number) in most browsers */
        html, body { width: 100%; height: 100%; margin: 0; padding: 0; }
    }`;
    document.head.appendChild(style);
})();
(function($) {
    const $siteUrl = '<?= get_template_directory_uri() ?>';
    const pageUrl = '<?= get_permalink() ?>';
    let currentAppId = <?= $current_app_id ? $current_app_id : 'null' ?>;
    let selectedClassId = null;
    let selectedClassName = '';
    let selectedClassFee = 0;

    // ============================================
    // URL Parameter Management
    // ============================================
    
    // Get app_id from URL
    function getAppIdFromUrl() {
        const params = new URLSearchParams(window.location.search);
        return params.get('app_id') ? parseInt(params.get('app_id')) : null;
    }
    
    // Set app_id in URL without page reload
    function setAppIdInUrl(appId) {
        // Only trigger update if the ID actually changes
        if (appId === currentAppId) return;

        const url = new URL(window.location.href);
        if (appId) {
            url.searchParams.set('app_id', appId);
            url.searchParams.delete('status'); // Clear status after initial load
        } else {
            url.searchParams.delete('app_id');
            url.searchParams.delete('status');
        }
        window.history.pushState({appId: appId}, '', url.toString());
        currentAppId = appId;
        
        // Trigger update of all sections
        if (appId) {
            loadApplicationData(appId);
        } else {
            resetAllSections();
        }
    }
    
    // Listen for browser back/forward
    window.addEventListener('popstate', function(event) {
        const appId = getAppIdFromUrl();
        currentAppId = appId;
        if (appId) {
            loadApplicationData(appId);
        } else {
            resetAllSections();
        }
    });

    // ============================================
    // Load Application Data
    // ============================================
    
    function loadApplicationData(appId) {
        if (!appId) return;
        
        // Use direct SQL via same page
        $.ajax({
            url: pageUrl,
            method: "POST",
            data: { ajax_get_application: 1, applicationId: appId },
            dataType: "json"
        }).done(function(response) {
            console.log('DEBUG loadApplicationData:', response);
            if (response.status === 'success') {
                const data = response.data;
                
                // Update all sections with the application data
                updateSearchSection(data);
                updateClassSection(data);
                updateFormSection(data);
                updatePaymentSection(data);
            } else {
                console.error('Application not found:', response.message);
                resetAllSections();
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error('Failed to load application data:', textStatus, errorThrown);
        });
    }
    
    // ============================================
    // Update Individual Sections
    // ============================================
    
    // Update Search Section with found application
    function updateSearchSection(data) {
        const statusInfo = getStatusInfo(data.approve_status);
        let sectionRollHtml = '';
        if (data.stdSection || data.stdRoll) {
            sectionRollHtml = `<div style="margin: 8px 0 0 0; font-size: 15px; color: var(--secondary-color);">
                <span style="display:inline-block; margin-right:12px;"><strong>Section:</strong> ${data.stdSection ? data.sectionName : '<span style=\'color:#aaa\'>N/A</span>'}</span>
                <span style="display:inline-block;"><strong>Roll:</strong> ${data.stdRoll ? data.stdRoll : '<span style=\'color:#aaa\'>N/A</span>'}</span>
            </div>`;
        }
        const html = `
            <div style="margin-top: 20px;">
                <h4 style="color: var(--success-color); margin-bottom: 15px; font-size: 20px; letter-spacing: 0.5px;">✓ Current Application</h4>
                <div style="background: #f8fafc; padding: 18px 12px; border-radius: 10px; margin-bottom: 15px; border-left: 4px solid var(--primary-color); box-shadow: 0 2px 8px rgba(37,99,235,0.04);">
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <div style="display: flex; flex-direction: column; gap: 2px;">
                            <div style="font-size: 18px; font-weight: 700; color: var(--text-main); margin-bottom: 2px;">${data.stdName}</div>
                            <div style="font-size: 14px; color: var(--text-muted); margin-bottom: 2px;">
                                <strong>App ID:</strong> APP-${String(data.applicationid).padStart(6, '0')}<br>
                                <strong>Class:</strong> ${data.className}
                                ${sectionRollHtml}
                            </div>
                            <div style="font-size: 14px; color: var(--text-muted); margin-bottom: 2px;">
                                <strong>Status:</strong> <span style="color:${statusInfo.color}; font-weight:600;">${data.approve_status}</span>
                            </div>
                            <div style="margin: 2px 0 0 0; color: ${statusInfo.color}; font-weight: 500; font-size: 14px;">
                                ${statusInfo.message}
                            </div>
                            ${data.approve_status === 'Rejected' && data.reject_reason ? `<div style='color:#dc2626; font-weight:600; margin:8px 0 0 0;'>Reason: ${data.reject_reason}</div>` : ''}
                        </div>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px;">
                            <button onclick="printApplication()" class="btn btn-primary" style="padding: 8px 16px; font-size: 14px;">
                                <span class="dashicons dashicons-printer"></span> Print Slip
                            </button>
                            <a href="${pageUrl}" class="btn btn-primary" style="padding: 8px 16px; font-size: 14px;">
                                + New Application
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        `;
        $('#searchResults').html(html).removeClass('hidden');
    }
    
    // Update Class Section - set selected class in dropdown
    function updateClassSection(data) {
        let $select = $('#stdAdmitClass');
        let optionExists = $select.find(`option[value="${data.stdAdmitClass}"]`).length > 0;
        
        if (!optionExists) {
            // Add a temporary option for the closed/missing class
            let display = data.className + ' (Closed)';
            $select.append(`<option value="${data.stdAdmitClass}" data-fee="${data.admissionFee}" data-name="${data.className}">${display}</option>`);
        }
        
        $select.val(data.stdAdmitClass);
        selectedClassId = data.stdAdmitClass;
        selectedClassName = data.className;
        selectedClassFee = parseFloat(data.admissionFee) || 0;
    }
    
    // Update Form Section with application data
    function updateFormSection(data) {
        // Set application ID for update
        $('#applicationId').val(data.applicationid);
        
        // Disable class selection for existing applications
        $('#stdAdmitClass').prop('disabled', true);
        
        // Remove any previous admission closed alert in form
        $('#admissionClosedAlert').remove();
        
        // Check if registered - hide form, just show message
        if (data.approve_status === 'Registered') {
            $('#submitBtn').hide();
            $('#form-section').addClass('active');
            $('#form-section-number').text('✓');
        } else {
            $('#submitBtn').show();
            $('#submitBtnText').text('Update Application');
            $('#form-section').addClass('active');
            $('#form-section-number').text('✓');
        }
        
        // Populate form fields
        $('input[name="stdName"]').val(data.stdName);
        $('input[name="stdNameBangla"]').val(data.stdNameBangla || '');
        $('select[name="stdGender"]').val(data.stdGender);
        $('select[name="stdBldGrp"]').val(data.stdBldGrp || '');
        
        // Handle existing image
        if (data.stdImg) {
            $('#stdImg_existing').val(data.stdImg);
            $('#previewImg').attr('src', data.stdImg);
            $('#imagePreview').show();
        } else {
            $('#stdImg_existing').val('');
            $('#imagePreview').hide();
        }
        
        $('input[name="stdFather"]').val(data.stdFather);
        $('input[name="fatherLate"]').prop('checked', data.fatherLate == 1);
        $('input[name="stdFatherProf"]').val(data.stdFatherProf || '');
        $('input[name="stdMother"]').val(data.stdMother);
        $('input[name="motherLate"]').prop('checked', data.motherLate == 1);
        $('input[name="stdMotherProf"]').val(data.stdMotherProf || '');
        $('input[name="stdParentIncome"]').val(data.stdParentIncome || '');
        $('input[name="birth_reg_no"]').val(data.birth_reg_no || '');
        $('input[name="stdlocalGuardian"]').val(data.stdlocalGuardian || '');
        $('input[name="stdGuardianNID"]').val(data.stdGuardianNID || '');
        $('input[name="stdPhone"]').val(data.stdPhone);
        $('textarea[name="stdPermanent"]').val(data.stdPermanent || '');
        $('textarea[name="stdPresent"]').val(data.stdPresent || '');
        $('input[name="stdBrith"]').val(data.stdBrith);
        $('input[name="stdNationality"]').val(data.stdNationality || 'Bangladeshi');
        $('select[name="stdReligion"]').val(data.stdReligion);
        $('#stdAdmitClass').val(data.stdAdmitClass);
        $('select[name="stdAdmitYear"]').val(data.stdAdmitYear);
        // Ensure 'No Group' (value 0) is selected if stdGroup is 0, null, or empty
        if (data.stdGroup === 0 || data.stdGroup === '0' || data.stdGroup === null || data.stdGroup === '' || typeof data.stdGroup === 'undefined') {
            $('select[name="stdGroup"]').val('0');
        } else {
            $('select[name="stdGroup"]').val(data.stdGroup);
        }
        $('select[name="stdSection"]').val(data.stdSection || '');
        $('input[name="stdRoll"]').val(data.stdRoll || '');
        $('input[name="stdTcNumber"]').val(data.stdTcNumber || '');
        $('input[name="sscRoll"]').val(data.sscRoll || '');
        $('input[name="sscReg"]').val(data.sscReg || '');
        $('input[name="stdPrevSchool"]').val(data.stdPrevSchool || '');
        $('input[name="stdGPA"]').val(data.stdGPA || '');
        $('input[name="stdIntellectual"]').val(data.stdIntellectual || '');
        $('input[name="stdScholarsClass"]').val(data.stdScholarsClass || '');
        $('input[name="stdScholarsYear"]').val(data.stdScholarsYear || '');
        $('input[name="stdScholarsMemo"]').val(data.stdScholarsMemo || '');
        $('textarea[name="stdNote"]').val(data.stdNote || '');
        
        // Show selected class info with fee
        $('#selectedClassName').text(data.className || 'Unknown Class');
        const fee = parseFloat(data.admissionFee) || 0;
        $('#selectedClassFee').text(fee.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        selectedClassFee = fee; // Update global variable
        $('#selectedClassInfo').show();
        
        // Check if class has groups
        checkClassHasGroups(data.stdAdmitClass);
    }
    
    // Update Payment Section
    function updatePaymentSection(data) {
        // Show admission fee information
        const admissionFee = data.admissionFee || 0;
        $('#display-fee').text('৳' + admissionFee);
        $('#confirmation-section').css('opacity', '1');
        const approveStatusColor = data.approve_status === 'Approved' || data.approve_status === 'Registered' ? 'var(--success-color)' : 
                                   data.approve_status === 'Rejected' ? '#dc2626' : 'var(--warning-color)';
        
        const paymentHtml = `
            <div class="alert alert-success">
                <strong>✓ Application Loaded</strong><br>
                Your Application ID is: <strong>APP-${String(data.applicationid).padStart(6, '0')}</strong>
            </div>

            <div class="payment-card">
                <div style="margin-top: 30px;">
                    <p style="color: var(--text-muted); margin-bottom: 15px;">
                        ${data.payment_status === 'Paid' ? 
                            'Payment completed. You can print your application slip below.' : 
                            'Please complete the payment and submit proof to the school office.'}
                    </p>
                    <button onclick="printApplication()" class="btn btn-primary" style="margin-right: 10px;">
                        <span class="dashicons dashicons-printer"></span> Print Application
                    </button>
                    <a href="${pageUrl}" class="btn btn-outline">
                        Submit Another Application
                    </a>
                </div>
            </div>
            <br><br>
        `;
        
        $('#payment-section-content').html(paymentHtml + '<div id="printable-slip" style="display: none;"></div>');
        $('#payment-section').css('opacity', '1');
        $('#payment-section-number').text('✓');
        
        // Render slip for printing
        renderSlip(data);
    }
    
    // Reset all sections to initial state
    function resetAllSections() {
        // Reset search results
        $('#searchResults').html('').addClass('hidden');
        
        // Reset class selection
        $('#stdAdmitClass').val('').prop('disabled', false);
        selectedClassId = null;
        selectedClassName = '';
        selectedClassFee = 0;
        
        // Reset form
        $('#applicationForm')[0].reset();
        $('#applicationId').val('');
        $('#admissionClosedAlert').remove(); // Remove admission closed alert
        $('#submitBtn').show();
        $('#submitBtnText').text('Submit Application');
        $('#form-section').addClass('active');
        $('#form-section-number').text('2');
        $('#selectedClassInfo').hide();
        $('#imagePreview').hide();
        
        // Reset payment section
        $('#payment-section').css('opacity', '0.5');
        $('#payment-section-number').text('4');
        $('#payment-section-content').html(`
            <div class="empty-payment-state" style="text-align: center; padding: 40px 20px; color: var(--text-muted);">
                <div style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;">📄</div>
                <p style="font-size: 16px; margin-bottom: 8px;">Complete the application form above</p>
                <p style="font-size: 14px;">After submitting, you can print your application slip and view payment details here.</p>
            </div>
            <div id="printable-slip" style="display: none;"></div>
        `);
    }
    
    // ============================================
    // Helper Functions
    // ============================================
    
    window.scrollToForm = function() {
        $('html, body').animate({
            scrollTop: $('#form-section').offset().top - 20
        }, 500);
    };
    
    window.scrollToPayment = function() {
        $('html, body').animate({
            scrollTop: $('#payment-section').offset().top - 20
        }, 500);
    };

    // Handle Class Selection Change
    $('#stdAdmitClass').on('change', function() {
        const classId = $(this).val();
        if (!classId) {
            resetClassSelection();
            return;
        }

        const $selectedOption = $(this).find('option:selected');
        const className = $selectedOption.data('name');
        const fee = parseFloat($selectedOption.data('fee')) || 0;

        // Prevent class change if editing existing application
        if ($('#applicationId').val()) {
            alert('Cannot change admission class for an existing application. Please submit a new application for a different class.');
            $(this).val(selectedClassId); // Revert
            return;
        }
        
        // Clear app_id when selecting new class for new application
        setAppIdInUrl(null);
        
        selectedClassId = classId;
        selectedClassName = className;
        selectedClassFee = fee;

        // Reset to submit mode
        $('#applicationId').val('');
        $('#submitBtnText').text('Submit Application');
        $('#submitBtn').show();

        // Update UI
        $('#selectedClassName').text(className);
        $('#selectedClassFee').text(fee.toFixed(2));
        $('#selectedClassInfo').show();
        $('#form-section-number').text('2');

        // Check if class has groups
        checkClassHasGroups(classId);
    });

    // Reset Class Selection
    function resetClassSelection() {
        setAppIdInUrl(null);
        selectedClassId = null;
        selectedClassName = '';
        selectedClassFee = 0;
        $('#selectedClassInfo').hide();
        $('#groupSelectWrapper').hide();
        $('#sectionSelectWrapper').hide();
    }

    // Check if Class has Groups - Direct SQL via same page
    function checkClassHasGroups(classId) {
        $.ajax({
            url: pageUrl,
            method: "POST",
            data: { ajax_has_group: 1, classId: classId },
            dataType: "text"
        }).done(function(hasGroup) {
            console.log('DEBUG checkClassHasGroups:', classId, hasGroup);
            if (hasGroup === 'true') {
                $('#groupSelectWrapper').show();
                $('#sectionSelectWrapper').hide();
                $('#groupSelect').prop('required', true);
                $('#sectionSelect').prop('required', false);
            } else {
                $('#groupSelectWrapper').hide();
                $('#sectionSelectWrapper').show();
                $('#groupSelect').prop('required', false);
                $('#sectionSelect').prop('required', false);
                loadSections(classId);
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error('Failed to check class groups:', textStatus, errorThrown);
        });
    }

    // Load Sections for Class - Direct SQL via same page
    function loadSections(classId) {
        $.ajax({
            url: pageUrl,
            method: "POST",
            data: { ajax_get_sections: 1, classId: classId },
            dataType: "html"
        }).done(function(msg) {
            console.log('DEBUG loadSections:', classId);
            $("#sectionSelect").html(msg);
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.error('Failed to load sections:', textStatus, errorThrown);
        });
    }

    // Get Status Message and Color
    function getStatusInfo(status) {
        let message = '';
        let color = '';
        
        switch(status) {
            case 'Under Review':
                message = 'Authority is currently reviewing your application.';
                color = 'var(--primary-color)';
                break;
            case 'Approved':
                message = 'Your application is approved. Please wait for final registration. Print your application slip by selecting the application.';
                color = 'var(--success-color)';
                break;
            case 'Registered':
                message = 'You have completed all requirements. You can print your application slip by selecting the application.';
                color = 'var(--success-color)';
                break;
            case 'Rejected':
                message = 'Your application was not approved. Please update your application by selecting the application or contact the school authority.';
                color = '#dc2626'; // error color
                break;
            default:
                message = status;
                color = 'var(--text-muted)';
        }
        
        return { message: message, color: color };
    }

    // Search Application
    window.searchApplication = function() {
        const phone = $('#phoneSearch').val().trim();
        const dob = $('#dobSearch').val().trim();
        
        console.log('DEBUG: Starting search with phone:', phone, 'dob:', dob);
        
        if (!phone || phone.length !== 11) {
            alert('Please enter a valid 11-digit phone number');
            return;
        }
        
        if (!dob) {
            alert('Please enter your date of birth');
            return;
        }

        const $results = $('#searchResults');
        
        $results.html('<div style="padding: 20px; text-align: center;">Searching...</div>').removeClass('hidden');

        // Use direct SQL via same page (not ajaxAction.php)
        $.ajax({
            url: pageUrl,
            method: "POST",
            data: { 
                ajax_search_application: 1,
                phone: phone, 
                dob: dob 
            },
            dataType: "json"
        }).done(function(response) {
            console.log('DEBUG: Response received:', response);
            
            if (response.status === 'success' && response.data && response.data.length > 0) {
                let html = '<div style="margin-top: 20px;">';
                html += '<h4 style="color: var(--success-color); margin-bottom: 15px;">✓ Applications Found</h4>';
                
                response.data.forEach(function(app) {
                    const statusInfo = getStatusInfo(app.approve_status);
                    let sectionRollHtml = '';
                    if (app.stdSection || app.stdRoll) {
                        sectionRollHtml = `<div style="margin: 8px 0 0 0; font-size: 15px; color: var(--secondary-color);">
                            <span style="display:inline-block; margin-right:12px;"><strong>Section:</strong> ${app.stdSection ? app.sectionName : '<span style=\'color:#aaa\'>N/A</span>'}</span>
                            <span style="display:inline-block;"><strong>Roll:</strong> ${app.stdRoll ? app.stdRoll : '<span style=\'color:#aaa\'>N/A</span>'}</span>
                        </div>`;
                    }
                    html += `
                        <div style="background: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid var(--primary-color);">
                            <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 15px;">
                                <div style="display: flex; flex-direction: column; gap: 2px;">
                                    <div style="font-size: 18px; font-weight: 700; color: var(--text-main); margin-bottom: 2px;">${app.stdName}</div>
                                    <div style="font-size: 14px; color: var(--text-muted); margin-bottom: 2px;">
                                        <strong>App ID:</strong> APP-${String(app.applicationid).padStart(6, '0')}<br>
                                        <strong>Class:</strong> ${app.className}
                                        ${sectionRollHtml}
                                    </div>
                                    <div style="font-size: 14px; color: var(--text-muted); margin-bottom: 2px;">
                                        <strong>Status:</strong> <span style="color:${statusInfo.color}; font-weight:600;">${app.approve_status}</span>
                                    </div>
                                    <div style="margin: 2px 0 0 0; color: ${statusInfo.color}; font-weight: 500; font-size: 14px;">
                                        ${statusInfo.message}
                                    </div>
                                    ${app.approve_status === 'Rejected' && app.reject_reason ? `<div style='color:#dc2626; font-weight:600; margin:8px 0 0 0;'>Reason: ${app.reject_reason}</div>` : ''}
                                </div>
                                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                    <button onclick="selectApplication(${app.applicationid})" class="btn btn-primary" style="padding: 8px 16px; font-size: 13px;">
                                        Select Application
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                html += '</div>';
                $results.html(html);
            } else {
                console.log('DEBUG: No results or not_found status:', response);
                $results.html(`
                    <div style="padding: 20px; text-align: center; color: var(--text-muted);">
                        <p>No applications found for this phone number or Date of Birth is incorrect.</p>
                        <p style="margin-top: 10px;">Please proceed to apply for a new admission below.</p>
                        <p style="margin-top: 10px; font-size: 11px; color: #999;">Debug: ${response.debug || 'no_debug'}</p>
                    </div>
                `);
            }
        }).fail(function(jqXHR, textStatus, errorThrown) {
            console.log('DEBUG: AJAX fail - Status:', jqXHR.status, 'Text:', textStatus, 'Error:', errorThrown);
            console.log('DEBUG: Response text:', jqXHR.responseText);
            $results.html(`
                <div style="padding: 20px; text-align: center; color: var(--danger-color);">
                    Error searching. Please try again.<br>
                    <small style="color: #999;">Debug: ${textStatus} - ${jqXHR.status} ${errorThrown}</small>
                </div>
            `);
        });
    };
    
    // Select an application (sets app_id in URL and loads data)
    window.selectApplication = function(appId) {
        setAppIdInUrl(appId);
    };

    // Print Application
    window.printApplication = function() {
        // Clone the printable slip content and append to body for printing
        const slipContent = document.getElementById('printable-slip');
        if (!slipContent || !slipContent.innerHTML.trim()) {
            alert('Please wait for the application slip to load before printing.');
            return;
        }
        
        // Create a temporary print container
        const printContainer = document.createElement('div');
        printContainer.id = 'print-container';
        printContainer.innerHTML = slipContent.innerHTML;
        printContainer.style.cssText = 'display: block; position: fixed; top: 0; left: 0; width: 100%; background: white; z-index: 99999;';
        
        // Append to body
        document.body.appendChild(printContainer);
        
        // Print
        window.print();
        
        // Remove the print container after printing
        setTimeout(function() {
            if (document.getElementById('print-container')) {
                document.body.removeChild(printContainer);
            }
        }, 1000);
    };

    // Render Slip HTML (for printing) - A4 optimized
    window.renderSlip = function(data) {
        const padId = (num) => String(num).padStart(6, '0');
        const printDate = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
        const appliedDate = data.stdCreatedAt ? new Date(data.stdCreatedAt).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : printDate;
        const gender = data.stdGender == 1 ? 'Male' : (data.stdGender == 0 ? 'Female' : 'Other');
        const paymentStatus = data.payment_status || 'Unpaid';
        const approveStatus = data.approve_status || 'Pending';
        const admissionFee = parseFloat(data.admissionFee || 0).toLocaleString('en-BD');

        const html = `
            <div class="slip-container" style="width: 100%; max-width: 190mm; margin: 0 auto; padding: 15mm 10mm; font-family: Arial, sans-serif; font-size: 11pt; line-height: 1.4; box-sizing: border-box;">
                <!-- Header Section -->
                <div style="text-align: center; border-bottom: 2px solid #000; padding-bottom: 12px; margin-bottom: 12px;">
                    <div style="font-size: 18pt; font-weight: 700; text-transform: uppercase; margin-bottom: 3px; letter-spacing: 1px;">${data.schoolName || 'Mangalchandi Nishikanta Govt. Model High School'}</div>
                    <div style="font-size: 10pt; color: #333; margin-bottom: 8px;">${data.schoolAddress || 'Tajpur, Osmaninagor, Sylhet.'}</div>
                    <div style="font-size: 13pt; font-weight: 600; background: #1a1a1a; color: #fff; display: inline-block; padding: 4px 20px; border-radius: 3px; letter-spacing: 0.5px;">ADMISSION APPLICATION SLIP</div>
                </div>
                
                <!-- Application Info Bar -->
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px; padding: 8px 12px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 4px;">
                    <div><strong>Application ID:</strong> ${padId(data.applicationid)}</div>
                    <div><strong>Applied Date:</strong> ${appliedDate}</div>
                </div>

                <!-- Two Column Layout for Student Info and Photo -->
                <div style="display: flex; gap: 15px; margin-bottom: 10px;">
                    <!-- Student Information -->
                    <div style="flex: 1;">
                        <div style="background: #e8e8e8; padding: 4px 10px; font-weight: 600; font-size: 10pt; border-left: 3px solid #333; margin-bottom: 6px;">STUDENT INFORMATION</div>
                        <table style="width: 100%; border-collapse: collapse; font-size: 10pt;">
                            <tr>
                                <td style="border: 1px solid #ccc; padding: 5px 8px; background: #f8f8f8; width: 35%; font-weight: 600;">Name (English)</td>
                                <td style="border: 1px solid #ccc; padding: 5px 8px;">${data.stdName || '-'}</td>
                            </tr>
                            ${data.stdNameBangla ? `<tr>
                                <td style="border: 1px solid #ccc; padding: 5px 8px; background: #f8f8f8; font-weight: 600;">Name (Bangla)</td>
                                <td style="border: 1px solid #ccc; padding: 5px 8px;">${data.stdNameBangla}</td>
                            </tr>` : ''}
                            <tr>
                                <td style="border: 1px solid #ccc; padding: 5px 8px; background: #f8f8f8; font-weight: 600;">Gender / Blood</td>
                                <td style="border: 1px solid #ccc; padding: 5px 8px;">${gender} ${data.stdBldGrp ? '/ ' + data.stdBldGrp : ''}</td>
                            </tr>
                            <tr>
                                <td style="border: 1px solid #ccc; padding: 5px 8px; background: #f8f8f8; font-weight: 600;">Date of Birth</td>
                                <td style="border: 1px solid #ccc; padding: 5px 8px;">${data.stdBrith || '-'}</td>
                            </tr>
                            <tr>
                                <td style="border: 1px solid #ccc; padding: 5px 8px; background: #f8f8f8; font-weight: 600;">Religion</td>
                                <td style="border: 1px solid #ccc; padding: 5px 8px;">${data.stdReligion || '-'}</td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Photo Box -->
                    <div style="width: 90px; text-align: center;">
                        <div style="width: 85px; height: 100px; border: 2px solid #333; display: flex; align-items: center; justify-content: center; background: #f9f9f9; margin-bottom: 4px;">
                            ${data.stdImg ? `<img src="${data.stdImg}" style="max-width: 100%; max-height: 100%; object-fit: cover;">` : '<span style="font-size: 9pt; color: #999;">Photo</span>'}
                        </div>
                    </div>
                </div>

                <!-- Admission Information -->
                <div style="background: #e8e8e8; padding: 4px 10px; font-weight: 600; font-size: 10pt; border-left: 3px solid #333; margin-bottom: 6px;">ADMISSION DETAILS</div>
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 10pt;">
                    <tr>
                        <td style="border: 1px solid #ccc; padding: 5px 8px; background: #f8f8f8; width: 25%; font-weight: 600;">Class</td>
                        <td style="border: 1px solid #ccc; padding: 5px 8px; width: 25%;">${data.className || '-'}</td>
                        <td style="border: 1px solid #ccc; padding: 5px 8px; background: #f8f8f8; width: 25%; font-weight: 600;">Session</td>
                        <td style="border: 1px solid #ccc; padding: 5px 8px; width: 25%;">${data.stdAdmitYear || '-'}</td>
                    </tr>
                    ${data.groupName ? `<tr>
                        <td style="border: 1px solid #ccc; padding: 5px 8px; background: #f8f8f8; font-weight: 600;">Group</td>
                        <td style="border: 1px solid #ccc; padding: 5px 8px;" colspan="3">${data.groupName}</td>
                    </tr>` : ''}
                    <tr>
                        <td style="border: 1px solid #ccc; padding: 5px 8px; background: #f8f8f8; font-weight: 600;">Admission Fee</td>
                        <td style="border: 1px solid #ccc; padding: 5px 8px;">৳ ${admissionFee}</td>
                        <td style="border: 1px solid #ccc; padding: 5px 8px; background: #f8f8f8; font-weight: 600;">Payment Status</td>
                        <td style="border: 1px solid #ccc; padding: 5px 8px; font-weight: 600; color: ${paymentStatus === 'Paid' ? '#059669' : '#d97706'};">${paymentStatus}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ccc; padding: 5px 8px; background: #f8f8f8; font-weight: 600;">App Status</td>
                        <td style="border: 1px solid #ccc; padding: 5px 8px; font-weight: 600; color: ${approveStatus === 'Approved' || approveStatus === 'Registered' ? '#059669' : approveStatus === 'Rejected' ? '#dc2626' : '#d97706'};" colspan="3">${approveStatus}</td>
                    </tr>
                </table>

                <!-- Guardian Information -->
                <div style="background: #e8e8e8; padding: 4px 10px; font-weight: 600; font-size: 10pt; border-left: 3px solid #333; margin-bottom: 6px;">GUARDIAN INFORMATION</div>
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 10pt;">
                    <tr>
                        <td style="border: 1px solid #ccc; padding: 5px 8px; background: #f8f8f8; width: 25%; font-weight: 600;">Father's Name</td>
                        <td style="border: 1px solid #ccc; padding: 5px 8px; width: 25%;">${data.stdFather || '-'} ${data.fatherLate == 1 ? '(Late)' : ''}</td>
                        <td style="border: 1px solid #ccc; padding: 5px 8px; background: #f8f8f8; width: 25%; font-weight: 600;">Profession</td>
                        <td style="border: 1px solid #ccc; padding: 5px 8px; width: 25%;">${data.stdFatherProf || '-'}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ccc; padding: 5px 8px; background: #f8f8f8; font-weight: 600;">Mother's Name</td>
                        <td style="border: 1px solid #ccc; padding: 5px 8px;">${data.stdMother || '-'} ${data.motherLate == 1 ? '(Late)' : ''}</td>
                        <td style="border: 1px solid #ccc; padding: 5px 8px; background: #f8f8f8; font-weight: 600;">Profession</td>
                        <td style="border: 1px solid #ccc; padding: 5px 8px;">${data.stdMotherProf || '-'}</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ccc; padding: 5px 8px; background: #f8f8f8; font-weight: 600;">Contact Phone</td>
                        <td style="border: 1px solid #ccc; padding: 5px 8px;">${data.stdPhone || '-'}</td>
                        <td style="border: 1px solid #ccc; padding: 5px 8px; background: #f8f8f8; font-weight: 600;">Guardian NID</td>
                        <td style="border: 1px solid #ccc; padding: 5px 8px;">${data.stdGuardianNID || '-'}</td>
                    </tr>
                </table>

                <!-- Address Information -->
                <div style="background: #e8e8e8; padding: 4px 10px; font-weight: 600; font-size: 10pt; border-left: 3px solid #333; margin-bottom: 6px;">ADDRESS</div>
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 10px; font-size: 10pt;">
                    <tr>
                        <td style="border: 1px solid #ccc; padding: 5px 8px; background: #f8f8f8; width: 25%; font-weight: 600;">Present Address</td>
                        <td style="border: 1px solid #ccc; padding: 5px 8px;">${data.stdPresent || '-'}</td>
                    </tr>
                </table>

                <!-- Signature Section -->
                <div style="margin-top: 100px; display: flex; justify-content: space-between; padding: 0 20px;">
                    <div style="text-align: center; width: 150px;">
                        <div style="border-top: 1px solid #000; padding-top: 4px; font-size: 9pt;">Guardian's Signature</div>
                    </div>
                    <div style="text-align: center; width: 150px;">
                        <div style="border-top: 1px solid #000; padding-top: 4px; font-size: 9pt;">Applicant's Signature</div>
                    </div>
                    <div style="text-align: center; width: 150px;">
                        <div style="border-top: 1px solid #000; padding-top: 4px; font-size: 9pt;">Office Seal & Signature</div>
                    </div>
                </div>

                <!-- Footer Note -->
                <div style="margin-top: 15px; padding: 8px; background: #f8f8f8; border: 1px solid #ddd; font-size: 8pt; color: #666; text-align: center;">
                    <strong>Note:</strong> This is a computer-generated application slip. Please bring this slip along with original documents during admission verification. 
                    For any queries, contact the school office.
                </div>
            </div>
        `;
        
        $('#printable-slip').html(html).show();
    };

    // Image Upload Preview
    $('#stdImgUpload').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Check file size (5MB limit)
            if (file.size > 5 * 1024 * 1024) {
                alert('File size must be less than 5MB');
                $(this).val('');
                return;
            }
            
            // Check file type
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
            if (!allowedTypes.includes(file.type)) {
                alert('Only JPG, JPEG, PNG, and GIF files are allowed');
                $(this).val('');
                return;
            }
            
            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#previewImg').attr('src', e.target.result);
                $('#imagePreview').show();
            };
            reader.readAsDataURL(file);
        }
    });

    // Remove Image
    $('#removeImage').on('click', function() {
        $('#stdImgUpload').val('');
        $('#stdImg_existing').val('');
        $('#imagePreview').hide();
        $('#previewImg').attr('src', '');
    });

    // Helper: Close modal by ID
    window.closeModal = function(modalId) {
        const $modal = $('#' + modalId);
        if ($modal.length) {
            $modal.hide();
            $modal.removeClass('show');
        }
    };

    // ============================================
    // Initialize on Page Load
    // ============================================
    $(document).ready(function() {
        // Check if app_id is in URL on page load
        const appId = getAppIdFromUrl();
        if (appId) {
            currentAppId = appId;
            loadApplicationData(appId);
            
            // Show success message if status param exists
            const params = new URLSearchParams(window.location.search);
            const status = params.get('status');
            if (status === 'success' || status === 'updated') {
                // Scroll to payment section after data loads
                setTimeout(function() {
                    scrollToPayment();
                }, 500);
            }
        }
    });

})(jQuery);
</script>

<?php get_footer(); ?>