<?php
/*
 * Template Name: Student Application
 */

get_header();

/*=================
    Handle Submission
=================*/
$message = null;
$submitted_app_id = null;
$show_payment_section = false;

if (isset($_POST['submitApplication'])) {
    // Backward compatibility
    if (!isset($_POST['stdAdmitYear']) && isset($_POST['stdCurntYear'])) {
        $_POST['stdAdmitYear'] = $_POST['stdCurntYear'];
    }

    // Check if this is an update
    $is_update = isset($_POST['applicationId']) && !empty($_POST['applicationId']);
    $application_id = $is_update ? intval($_POST['applicationId']) : null;

    // Sanitize and Prepare Data
    $data = array(
        'studentid' => 0, // Default, will be updated when approved
        'stdName' => sanitize_text_field($_POST['stdName']),
        'stdNameBangla' => sanitize_text_field($_POST['stdNameBangla'] ?? ''),
        'stdGender' => sanitize_text_field($_POST['stdGender']),
        'stdBldGrp' => sanitize_text_field($_POST['stdBldGrp'] ?? ''),
        'facilities' => sanitize_text_field($_POST['facilities'] ?? ''),
        'stdImg' => esc_url_raw($_POST['stdImg'] ?? ''),
        'stdFather' => sanitize_text_field($_POST['stdFather']),
        'fatherLate' => isset($_POST['fatherLate']) ? 1 : 0,
        'stdFatherProf' => sanitize_text_field($_POST['stdFatherProf'] ?? ''),
        'stdMother' => sanitize_text_field($_POST['stdMother']),
        'motherLate' => isset($_POST['motherLate']) ? 1 : 0,
        'stdMotherProf' => sanitize_text_field($_POST['stdMotherProf'] ?? ''),
        'stdParentIncome' => intval($_POST['stdParentIncome'] ?? 0),
        'stdlocalGuardian' => sanitize_text_field($_POST['stdlocalGuardian'] ?? ''),
        'stdGuardianNID' => sanitize_text_field($_POST['stdGuardianNID'] ?? ''),
        'stdPhone' => sanitize_text_field($_POST['stdPhone']),
        'stdPermanent' => sanitize_textarea_field($_POST['stdPermanent'] ?? ''),
        'stdPresent' => sanitize_textarea_field($_POST['stdPresent'] ?? ''),
        'stdBrith' => sanitize_text_field($_POST['stdBrith']),
        'stdNationality' => sanitize_text_field($_POST['stdNationality'] ?? 'Bangladeshi'),
        'stdReligion' => sanitize_text_field($_POST['stdReligion']),
        'stdAdmitClass' => intval($_POST['stdAdmitClass']),
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
        'approve_status' => 'Submitted',
        'payment_status' => 'Pending'
    );

    // For updates, don't change created time or approval status
    if (!$is_update) {
        $data['stdCreatedAt'] = current_time('mysql');
        $data['approve_status'] = 'Submitted';
    }

    if ($is_update) {
        // Update existing application
        $result = $wpdb->update('ct_online_application', $data, array('applicationid' => $application_id));
        if ($result === false) {
            error_log('Online application update error: ' . $wpdb->last_error);
            $message = array('status' => 'error', 'text' => 'Update failed. Please try again or contact the office.');
        } else {
            $submitted_app_id = $application_id;
            $message = array('status' => 'success', 'text' => 'Application updated successfully!');
            $show_payment_section = false; // Don't show payment for updates
        }
    } else {
        // Insert new application
        $result = $wpdb->insert('ct_online_application', $data);
        if ($result === false) {
            print_r($wpdb->last_error);
            error_log('Online application insert error: ' . $wpdb->last_error);
            $message = array('status' => 'error', 'text' => 'Submission failed. Please try again or contact the office.');
        } else {
            $submitted_app_id = $wpdb->insert_id;
            $message = array('status' => 'success', 'text' => 'Application submitted successfully!');
            $show_payment_section = true;
        }
    }
        
        // Get admission fee for the class
        $fee_data = $wpdb->get_row($wpdb->prepare(
            "SELECT amount FROM ct_admission_fee_promoted WHERE class = %d AND is_active = 1",
            $data['stdAdmitClass']
        ));
        
        $admission_fee = $fee_data ? $fee_data->amount : 0;
        
        // Pass data to JS for immediate slip printing
        $submitted_data = $data;
        $submitted_data['applicationid'] = $submitted_app_id;
        $submitted_data['admission_fee'] = $admission_fee;
    }
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
        margin: 40px auto;
        padding: 0 20px;
    }

    .app-card {
        background: var(--card-bg);
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        padding: 40px;
        margin-bottom: 30px;
    }

    .page-header {
        text-align: center;
        margin-bottom: 50px;
    }

    .page-header h1 {
        color: var(--primary-color);
        font-weight: 700;
        font-size: 32px;
        margin-bottom: 10px;
    }

    .page-header p {
        color: var(--text-muted);
        font-size: 16px;
    }

    .section-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid var(--border-color);
    }

    .section-number {
        background: var(--primary-color);
        color: white;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 18px;
    }

    .section-title {
        font-size: 22px;
        font-weight: 600;
        color: var(--text-main);
    }

    /* Search Section */
    .search-box {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
    }

    .search-input {
        flex: 1;
        padding: 12px 16px;
        border: 2px solid var(--border-color);
        border-radius: 8px;
        font-size: 16px;
        transition: border-color 0.2s;
    }

    .search-input:focus {
        border-color: var(--primary-color);
        outline: none;
    }

    .btn {
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 15px;
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
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
    }

    .class-card {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        padding: 30px 20px;
        border-radius: 12px;
        text-align: center;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .class-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
    }

    .class-card.selected {
        background: linear-gradient(135deg, var(--success-color), #059669);
    }

    .class-name {
        font-size: 20px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .class-fee {
        font-size: 14px;
        opacity: 0.9;
    }

    /* Form Styles */
    .form-section {
        margin-bottom: 35px;
        display: none;
    }

    .form-section.active {
        display: block;
    }

    .section-subtitle {
        font-size: 16px;
        font-weight: 600;
        color: var(--secondary-color);
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 1px solid var(--border-color);
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        display: block;
        font-weight: 500;
        margin-bottom: 8px;
        color: var(--text-main);
    }

    .form-control {
        width: 100%;
        padding: 10px 14px;
        border: 1px solid var(--border-color);
        border-radius: 8px;
        font-size: 15px;
        transition: border-color 0.2s, box-shadow 0.2s;
    }

    .form-control:focus {
        border-color: var(--primary-color);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        outline: none;
    }

    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-weight: 500;
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
        border-radius: 12px;
        padding: 30px;
        text-align: center;
    }

    .payment-amount {
        font-size: 48px;
        font-weight: 700;
        color: var(--success-color);
        margin: 20px 0;
    }

    .payment-details {
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
        text-align: left;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
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

    /* Print Styles */
    @media print {
        body * {
            visibility: hidden;
        }
        #printable-slip, #printable-slip * {
            visibility: visible;
        }
        #printable-slip {
            position: absolute;
            left: 0;
            top: 0;
        }
    }
</style>

<div class="app-container">
    
    <div class="page-header">
        <h1>Student Admission Portal</h1>
        <p>Complete your application in simple steps</p>
    </div>

    <?php if ($message && $message['status'] == 'error'): ?>
        <div class="alert alert-error"><?= $message['text'] ?></div>
    <?php endif; ?>

    <!-- Section 1: Search Existing Applications -->
    <div class="app-card" id="search-section">
        <div class="section-header">
            <div class="section-number">1</div>
            <div class="section-title">Search Your Application</div>
        </div>
        
        <p style="color: var(--text-muted); margin-bottom: 20px;">
            Already applied? Enter your phone number and date of birth to view, print, or update your application.
        </p>

        <div class="search-box">
            <input 
                type="tel" 
                id="phoneSearch" 
                class="search-input" 
                placeholder="Enter your phone number (e.g., 01712345678)"
                pattern="[0-9]{11}"
                style="flex: 1;"
            >
            <input 
                type="date" 
                id="dobSearch" 
                class="search-input" 
                placeholder="Date of Birth"
                style="flex: 1;"
            >
            <button class="btn btn-primary" onclick="searchApplication()">
                <span class="dashicons dashicons-search"></span> Search
            </button>
        </div>

        <div id="searchResults" class="hidden"></div>
    </div>

    <!-- Section 2: Select Class -->
    <div class="app-card" id="class-section" <?= $show_payment_section ? 'style="display:none;"' : '' ?>>
        <div class="section-header">
            <div class="section-number">2</div>
            <div class="section-title">Select Admission Class</div>
        </div>

        <p style="color: var(--text-muted); margin-bottom: 25px;">
            Choose the class you want to apply for admission.
        </p>

        <div class="class-grid">
            <?php
            $classes = $wpdb->get_results("
                SELECT c.classid, c.className, COALESCE(f.amount, 0) as fee
                FROM ct_class c
                INNER JOIN ct_admission_fee_promoted f ON c.classid = f.class AND f.is_active = 1
                ORDER BY c.classid ASC
            ");
            
            foreach ($classes as $class) {
                echo '<div class="class-card" onclick="selectClass(' . $class->classid . ', \'' . esc_js($class->className) . '\', ' . $class->fee . ')">';
                echo '<div class="class-name">' . esc_html($class->className) . '</div>';
                if ($class->fee > 0) {
                    echo '<div class="class-fee">Fee: ৳' . number_format($class->fee, 2) . '</div>';
                }
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <!-- Section 3: Application Form -->
    <div class="app-card form-section" id="form-section" <?= $show_payment_section ? 'style="display:none;"' : '' ?>>
        <div class="section-header">
            <div class="section-number">3</div>
            <div class="section-title">Complete Application Form</div>
        </div>

        <div class="alert alert-info" id="selectedClassInfo" style="display:none;">
            <strong>Selected Class:</strong> <span id="selectedClassName"></span> | 
            <strong>Admission Fee:</strong> ৳<span id="selectedClassFee"></span>
        </div>

        <form method="POST" id="applicationForm">
            <input type="hidden" name="stdAdmitClass" id="stdAdmitClass" required>
            <input type="hidden" name="applicationId" id="applicationId">
            
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
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="form-label">Date of Birth *</label>
                        <input class="form-control" type="date" name="stdBrith" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
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
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Nationality</label>
                        <input class="form-control" type="text" name="stdNationality" value="Bangladeshi">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Student Photo (URL)</label>
                <input class="form-control" type="text" name="stdImg" placeholder="Image URL (optional)">
            </div>

            <!-- Academic Information -->
            <div class="section-subtitle">Academic Information</div>
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">Admission Year *</label>
                        <select class="form-control" name="stdAdmitYear" required>
                            <?php
                            $currentYear = date('Y');
                            echo "<option value='{$currentYear}' selected>{$currentYear}</option>";
                            echo "<option value='" . ($currentYear + 1) . "'>" . ($currentYear + 1) . "</option>";
                            ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <!-- Group selection (shown for classes with groups) -->
                    <div class="form-group" id="groupSelectWrapper" style="display:none;">
                        <label class="form-label">Group *</label>
                        <select id="groupSelect" class="form-control" name="stdGroup">
                            <option value="">Select Group</option>
                            <?php
                            $groups = $wpdb->get_results("SELECT * FROM ct_group ORDER BY groupName");
                            foreach ($groups as $group) {
                                echo "<option value='{$group->groupId}'>{$group->groupName}</option>";
                            }
                            ?>
                        </select>
                    </div>
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
                        <label class="form-label">SSC Roll</label>
                        <input class="form-control" type="text" name="sscRoll">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="form-label">SSC Registration</label>
                        <input class="form-control" type="text" name="sscReg">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Additional Notes</label>
                <textarea class="form-control" name="stdNote" rows="3" placeholder="Any special requirements or notes..."></textarea>
            </div>

            <div class="form-group" style="text-align: right; margin-top: 30px;">
                <button type="submit" name="submitApplication" class="btn btn-primary" id="submitBtn">
                    <span id="submitBtnText">Submit Application</span> →
                </button>
            </div>
        </form>
    </div>

    <!-- Section 4: Payment Section -->
    <?php if ($show_payment_section): ?>
    <div class="app-card" id="payment-section">
        <div class="section-header">
            <div class="section-number">4</div>
            <div class="section-title">Payment Information</div>
        </div>

        <div class="alert alert-success">
            <strong>✓ Application Submitted Successfully!</strong><br>
            Your Application ID is: <strong>APP-<?= str_pad($submitted_app_id, 6, '0', STR_PAD_LEFT) ?></strong>
        </div>

        <div class="payment-card">
            <h3 style="color: var(--success-color); margin-top: 0;">Admission Fee</h3>
            <div class="payment-amount">৳<?= number_format($admission_fee, 2) ?></div>

            <div class="payment-details">
                <div class="detail-row">
                    <span><strong>Application ID:</strong></span>
                    <span>APP-<?= str_pad($submitted_app_id, 6, '0', STR_PAD_LEFT) ?></span>
                </div>
                <div class="detail-row">
                    <span><strong>Student Name:</strong></span>
                    <span><?= esc_html($data['stdName']) ?></span>
                </div>
                <div class="detail-row">
                    <span><strong>Class:</strong></span>
                    <span><?php 
                        $class = $wpdb->get_var($wpdb->prepare("SELECT className FROM ct_class WHERE classid = %d", $data['stdAdmitClass']));
                        echo esc_html($class);
                    ?></span>
                </div>
                <div class="detail-row">
                    <span><strong>Payment Status:</strong></span>
                    <span style="color: var(--warning-color); font-weight: 600;">Pending</span>
                </div>
            </div>

            <div style="margin-top: 30px;">
                <p style="color: var(--text-muted); margin-bottom: 15px;">
                    Please complete the payment and submit proof to the school office.
                </p>
                <button onclick="window.print()" class="btn btn-primary" style="margin-right: 10px;">
                    <span class="dashicons dashicons-printer"></span> Print Application
                </button>
                <a href="<?= get_permalink() ?>" class="btn btn-outline">
                    Submit Another Application
                </a>
            </div>
        </div>
    </div>

    <!-- Hidden Printable Slip -->
    <div id="printable-slip" style="display: none;"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fullAppData = <?= json_encode(array_merge($data, ['applicationid' => $submitted_app_id])) ?>;
            if(typeof renderSlip === 'function') {
                renderSlip(fullAppData);
            }
        });
    </script>
    <?php endif; ?>

</div>

<script type="text/javascript">
(function($) {
    let selectedClassId = null;
    let selectedClassName = '';
    let selectedClassFee = 0;

    // Select Class Function
    window.selectClass = function(classId, className, fee) {
        selectedClassId = classId;
        selectedClassName = className;
        selectedClassFee = fee;

        // Reset to submit mode
        $('#applicationId').val('');
        $('#submitBtnText').text('Submit Application');

        // Update UI
        $('.class-card').removeClass('selected');
        event.currentTarget.classList.add('selected');

        // Show form section
        $('#stdAdmitClass').val(classId);
        $('#selectedClassName').text(className);
        $('#selectedClassFee').text(fee.toFixed(2));
        $('#selectedClassInfo').show();
        $('#form-section').addClass('active').show();

        // Scroll to form
        $('html, body').animate({
            scrollTop: $('#form-section').offset().top - 20
        }, 500);

        // Check if class has groups
        checkClassHasGroups(classId);
    };

    // Reset Class Selection
    window.resetClassSelection = function() {
        $('.class-card').removeClass('selected');
        $('#form-section').removeClass('active').hide();
        $('#applicationForm')[0].reset();
        
        // Reset to submit mode
        $('#applicationId').val('');
        $('#submitBtnText').text('Submit Application');
        
        $('html, body').animate({
            scrollTop: $('#class-section').offset().top - 20
        }, 500);
    };

    // Check if Class has Groups
    function checkClassHasGroups(classId) {
        const $siteUrl = '<?= get_template_directory_uri() ?>';
        $.ajax({
            url: $siteUrl + "/inc/ajaxAction.php",
            method: "POST",
            data: { class: classId, type: 'hasGroup' },
            dataType: "text"
        }).done(function(hasGroup) {
            if (hasGroup === 'true') {
                // Show group selection, hide section selection
                $('#groupSelectWrapper').show();
                $('#sectionSelectWrapper').hide();
                $('#groupSelect').prop('required', true);
                $('#sectionSelect').prop('required', false);
            } else {
                // Show section selection, hide group selection
                $('#groupSelectWrapper').hide();
                $('#sectionSelectWrapper').show();
                $('#groupSelect').prop('required', false);
                $('#sectionSelect').prop('required', false);
                // Load sections for the class
                loadSections(classId);
            }
        });
    }

    // Load Sections for Class
    function loadSections(classId) {
        const $siteUrl = '<?= get_template_directory_uri() ?>';
        $.ajax({
            url: $siteUrl + "/inc/ajaxAction.php",
            method: "POST",
            data: { class: classId, type: 'getSection' },
            dataType: "html"
        }).done(function(msg) {
            $("#sectionSelect").html(msg);
        });
    }

    // Get Status Message and Color
    function getStatusInfo(status) {
        let message = '';
        let color = '';
        
        switch(status) {
            case 'Submitted':
                message = 'Authority will review your application shortly.';
                color = 'var(--warning-color)';
                break;
            case 'Under Review':
                message = 'Authority is currently reviewing your application.';
                color = 'var(--primary-color)';
                break;
            case 'Approved':
                message = 'Your application is approved. If you haven\'t paid the admission fee, please complete the payment to be registered.';
                color = 'var(--success-color)';
                break;
            case 'Registered':
                message = 'You have completed all requirements. You can print your application slip.';
                color = 'var(--success-color)';
                break;
            case 'Rejected':
                message = 'Your application was not approved.';
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
        
        if (!phone || phone.length !== 11) {
            alert('Please enter a valid 11-digit phone number');
            return;
        }
        
        if (!dob) {
            alert('Please enter your date of birth');
            return;
        }

        const $siteUrl = '<?= get_template_directory_uri() ?>';
        const $results = $('#searchResults');
        
        $results.html('<div style="padding: 20px; text-align: center;">Searching...</div>').removeClass('hidden');

        $.ajax({
            url: $siteUrl + "/inc/ajaxAction.php",
            method: "POST",
            data: { phone: phone, dob: dob, type: 'searchApplicationByPhone' },
            dataType: "json"
        }).done(function(response) {
            if (response.status === 'success' && response.data && response.data.length > 0) {
                let html = '<div style="margin-top: 20px;">';
                html += '<h4 style="color: var(--success-color); margin-bottom: 15px;">✓ Applications Found</h4>';
                
                response.data.forEach(function(app) {
                    const statusInfo = getStatusInfo(app.approve_status);
                    html += `
                        <div style="background: #f8fafc; padding: 20px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid var(--primary-color);">
                            <div style="display: flex; justify-content: space-between; align-items: start;">
                                <div>
                                    <h5 style="margin: 0 0 10px 0; color: var(--text-main);">${app.stdName}</h5>
                                    <p style="margin: 5px 0; color: var(--text-muted);">
                                        <strong>App ID:</strong> APP-${String(app.applicationid).padStart(6, '0')} | 
                                        <strong>Class:</strong> ${app.className} | 
                                        <strong>Status:</strong> ${app.approve_status}
                                    </p>
                                    <p style="margin: 5px 0; color: ${statusInfo.color}; font-weight: 500;">
                                        ${statusInfo.message}
                                    </p>
                                    <p style="margin: 5px 0; color: var(--text-muted);">
                                        <strong>Applied:</strong> ${new Date(app.stdCreatedAt).toLocaleDateString('en-GB')}
                                    </p>
                                </div>
                                <div style="display: flex; gap: 10px;">
                                    <button onclick="updateApplication(${app.applicationid})" class="btn btn-outline" style="padding: 8px 16px; font-size: 13px;">
                                        <span class="dashicons dashicons-edit"></span> Update
                                    </button>
                                    <button onclick="printApplication(${app.applicationid})" class="btn btn-primary" style="padding: 8px 16px; font-size: 13px;">
                                        <span class="dashicons dashicons-printer"></span> Print
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                html += '</div>';
                $results.html(html);
            } else {
                $results.html(`
                    <div style="padding: 20px; text-align: center; color: var(--text-muted);">
                        <p>No applications found for this phone number or Date of Birth is incorrect.</p>
                        <p style="margin-top: 10px;">Please proceed to apply for a new admission below.</p>
                    </div>
                `);
            }
        }).fail(function() {
            $results.html('<div style="padding: 20px; text-align: center; color: var(--danger-color);">Error searching. Please try again.</div>');
        });
    };

    // Print Application
    window.printApplication = function(appId) {
        const $siteUrl = '<?= get_template_directory_uri() ?>';
        
        $.ajax({
            url: $siteUrl + "/inc/ajaxAction.php",
            method: "POST",
            data: { applicationId: appId, type: 'getApplicationDetails' },
            dataType: "json"
        }).done(function(response) {
            if (response.status === 'success') {
                renderSlip(response.data);
                setTimeout(() => {
                    window.print();
                }, 500);
            } else {
                alert('Error fetching application data');
            }
        }).fail(function() {
            alert('Network error. Please try again.');
        });
    };

    // Update Application
    window.updateApplication = function(appId) {
        const $siteUrl = '<?= get_template_directory_uri() ?>';
        
        $.ajax({
            url: $siteUrl + "/inc/ajaxAction.php",
            method: "POST",
            data: { applicationId: appId, type: 'getApplicationDetails' },
            dataType: "json"
        }).done(function(response) {
            if (response.status === 'success') {
                populateFormForUpdate(response.data);
            } else {
                alert('Error fetching application data');
            }
        }).fail(function() {
            alert('Network error. Please try again.');
        });
    };

    // Populate Form for Update
    function populateFormForUpdate(data) {
        // Set application ID for update
        $('#applicationId').val(data.applicationid);
        
        // Change button text
        $('#submitBtnText').text('Update Application');
        
        // Populate form fields
        $('input[name="stdName"]').val(data.stdName);
        $('input[name="stdNameBangla"]').val(data.stdNameBangla || '');
        $('select[name="stdGender"]').val(data.stdGender);
        $('select[name="stdBldGrp"]').val(data.stdBldGrp || '');
        $('input[name="stdImg"]').val(data.stdImg || '');
        $('input[name="stdFather"]').val(data.stdFather);
        $('input[name="fatherLate"]').prop('checked', data.fatherLate == 1);
        $('input[name="stdFatherProf"]').val(data.stdFatherProf || '');
        $('input[name="stdMother"]').val(data.stdMother);
        $('input[name="motherLate"]').prop('checked', data.motherLate == 1);
        $('input[name="stdMotherProf"]').val(data.stdMotherProf || '');
        $('input[name="stdParentIncome"]').val(data.stdParentIncome || '');
        $('input[name="stdlocalGuardian"]').val(data.stdlocalGuardian || '');
        $('input[name="stdGuardianNID"]').val(data.stdGuardianNID || '');
        $('input[name="stdPhone"]').val(data.stdPhone);
        $('textarea[name="stdPermanent"]').val(data.stdPermanent || '');
        $('textarea[name="stdPresent"]').val(data.stdPresent || '');
        $('input[name="stdBrith"]').val(data.stdBrith);
        $('input[name="stdNationality"]').val(data.stdNationality || 'Bangladeshi');
        $('select[name="stdReligion"]').val(data.stdReligion);
        $('input[name="stdAdmitClass"]').val(data.stdAdmitClass);
        $('select[name="stdAdmitYear"]').val(data.stdAdmitYear);
        $('select[name="stdGroup"]').val(data.stdGroup || '');
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
        
        // Show selected class info
        const className = data.className || 'Unknown Class';
        $('#selectedClassName').text(className);
        $('#selectedClassInfo').show();
        
        // Show form section
        $('#form-section').addClass('active').show();
        
        // Scroll to form
        $('html, body').animate({
            scrollTop: $('#form-section').offset().top - 20
        }, 500);
        
        // Check if class has groups
        checkClassHasGroups(data.stdAdmitClass);
    };

    // Render Slip HTML
    window.renderSlip = function(data) {
        const padId = (num) => String(num).padStart(6, '0');
        const printDate = new Date().toLocaleDateString('en-GB');

        const html = `
            <div style="max-width: 800px; margin: 0 auto; border: 2px solid #000; padding: 30px;">
                <div style="text-align: center; border-bottom: 2px solid #000; padding-bottom: 20px; margin-bottom: 20px;">
                    <div style="font-size: 28px; font-weight: 700; text-transform: uppercase; margin-bottom: 5px;">School Name</div>
                    <div style="font-size: 14px; margin-bottom: 15px;">School Address</div>
                    <div style="font-size: 20px; font-weight: 600; background: #000; color: #fff; display: inline-block; padding: 5px 20px; border-radius: 20px;">Admission Application Slip</div>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 14px; font-weight: 600;">
                    <span>App ID: APP-${padId(data.applicationid)}</span>
                    <span>Date: ${printDate}</span>
                </div>

                <h4 style="background: #e5e7eb; padding: 5px 10px; margin: 15px 0 5px; font-size: 14px;">Student Information</h4>
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 14px;">
                    <tr><td style="border: 1px solid #ccc; padding: 8px 12px; background: #f3f4f6; width: 35%; font-weight: 600;">Name (English)</td><td style="border: 1px solid #ccc; padding: 8px 12px;">${data.stdName}</td></tr>
                    ${data.stdNameBangla ? `<tr><td style="border: 1px solid #ccc; padding: 8px 12px; background: #f3f4f6; font-weight: 600;">Name (Bangla)</td><td style="border: 1px solid #ccc; padding: 8px 12px;">${data.stdNameBangla}</td></tr>` : ''}
                    <tr><td style="border: 1px solid #ccc; padding: 8px 12px; background: #f3f4f6; font-weight: 600;">Gender</td><td style="border: 1px solid #ccc; padding: 8px 12px;">${data.stdGender == 1 ? 'Boy' : (data.stdGender == 0 ? 'Girl' : 'Other')}</td></tr>
                    <tr><td style="border: 1px solid #ccc; padding: 8px 12px; background: #f3f4f6; font-weight: 600;">Date of Birth</td><td style="border: 1px solid #ccc; padding: 8px 12px;">${data.stdBrith}</td></tr>
                    <tr><td style="border: 1px solid #ccc; padding: 8px 12px; background: #f3f4f6; font-weight: 600;">Religion</td><td style="border: 1px solid #ccc; padding: 8px 12px;">${data.stdReligion}</td></tr>
                    <tr><td style="border: 1px solid #ccc; padding: 8px 12px; background: #f3f4f6; font-weight: 600;">Blood Group</td><td style="border: 1px solid #ccc; padding: 8px 12px;">${data.stdBldGrp || '-'}</td></tr>
                </table>

                <h4 style="background: #e5e7eb; padding: 5px 10px; margin: 15px 0 5px; font-size: 14px;">Guardian Information</h4>
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px; font-size: 14px;">
                    <tr><td style="border: 1px solid #ccc; padding: 8px 12px; background: #f3f4f6; width: 35%; font-weight: 600;">Father's Name</td><td style="border: 1px solid #ccc; padding: 8px 12px;">${data.stdFather}</td></tr>
                    <tr><td style="border: 1px solid #ccc; padding: 8px 12px; background: #f3f4f6; font-weight: 600;">Mother's Name</td><td style="border: 1px solid #ccc; padding: 8px 12px;">${data.stdMother}</td></tr>
                    <tr><td style="border: 1px solid #ccc; padding: 8px 12px; background: #f3f4f6; font-weight: 600;">Contact Phone</td><td style="border: 1px solid #ccc; padding: 8px 12px;">${data.stdPhone}</td></tr>
                </table>

                <div style="margin-top: 60px; display: flex; justify-content: space-between; text-align: center;">
                    <div style="border-top: 1px solid #000; width: 200px; padding-top: 5px; font-size: 13px;">Guardian Signature</div>
                    <div style="border-top: 1px solid #000; width: 200px; padding-top: 5px; font-size: 13px;">Authorized Signature</div>
                </div>
            </div>
        `;
        
        $('#printable-slip').html(html).show();
    };

})(jQuery);
</script>

<?php get_footer(); ?>
