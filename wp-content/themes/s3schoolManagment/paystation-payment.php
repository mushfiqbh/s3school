<?php
/**
 * Template Name: PayStation Payment
 * Frontend page for students to search fees and pay via PayStation
 */

get_header();

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

global $wpdb;

// Get global sub head IDs for JavaScript
global $admissionFeeSubHeadId, $admissionFormSubHeadId, $monthlyFeeSubHeadId;
global $transportFeeSubHeadId, $coachingFeeSubHeadId, $registrationFeeSubHeadId;
?>

<style>
.payment-page-wrapper {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 40px 0;
}

.payment-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 0 15px;
}

.payment-card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    overflow: hidden;
}

.payment-header {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
    color: #fff;
    padding: 30px;
    text-align: center;
}

.payment-header h2 {
    margin: 0;
    font-size: 28px;
    font-weight: 600;
}

.payment-header p {
    margin: 10px 0 0;
    opacity: 0.8;
    font-size: 16px;
}

.payment-body {
    padding: 30px;
}

.step-indicator {
    display: flex;
    justify-content: center;
    margin-bottom: 30px;
}

.step {
    display: flex;
    align-items: center;
    color: #999;
}

.step.active {
    color: #667eea;
}

.step.completed {
    color: #28a745;
}

.step-number {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: #e9ecef;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    margin-right: 10px;
}

.step.active .step-number {
    background: #667eea;
    color: #fff;
}

.step.completed .step-number {
    background: #28a745;
    color: #fff;
}

.step-connector {
    width: 60px;
    height: 3px;
    background: #e9ecef;
    margin: 0 15px;
}

.step.completed + .step-connector {
    background: #28a745;
}

.form-section {
    margin-bottom: 20px;
}

.form-section-title {
    font-size: 15px;
    font-weight: 600;
    color: #333;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 2px solid #667eea;
}

.form-row {
    display: flex;
    flex-wrap: wrap;
    margin: 0 -8px;
}

.form-col {
    flex: 1;
    min-width: 200px;
    padding: 0 8px;
    margin-bottom: 12px;
}

.compact-row {
    margin: 0 -6px;
}

.form-col-3 {
    flex: 1;
    min-width: 100px;
    padding: 0 6px;
    margin-bottom: 10px;
}

.form-group label {
    font-weight: 600;
    color: #555;
    margin-bottom: 5px;
    display: block;
    font-size: 13px;
}

.form-control {
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 8px 12px;
    font-size: 14px;
    transition: all 0.2s;
    background-color: #fff;
    color: #333;
    width: 100%;
    height: 42px;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
}

select.form-control {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 12 12'%3E%3Cpath fill='%23666' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    padding-right: 30px;
    cursor: pointer;
}

select.form-control option {
    color: #333;
    background: #fff;
    padding: 8px;
}

.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.15);
    outline: none;
}

.form-control::placeholder {
    color: #999;
    font-size: 13px;
}

.btn-calculate {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: #fff;
    padding: 15px 40px;
    font-size: 16px;
    font-weight: 600;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    width: 100%;
}

.btn-calculate:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
}

.btn-calculate-compact {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: #fff;
    padding: 10px 20px;
    font-size: 14px;
    font-weight: 600;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
    width: 100%;
    height: 42px;
}

.btn-calculate-compact:hover {
    transform: translateY(-1px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-pay {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border: none;
    color: #fff;
    padding: 18px 40px;
    font-size: 18px;
    font-weight: 600;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
    width: 100%;
}

.btn-pay:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(40, 167, 69, 0.4);
}

.btn-pay:disabled,
.btn-pay[disabled] {
    background: #94d3a2;
    cursor: not-allowed;
    box-shadow: none;
    transform: none;
}

.btn-back {
    background: #6c757d;
    border: none;
    color: #fff;
    padding: 12px 30px;
    font-size: 14px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-back:hover {
    background: #5a6268;
}

/* Fee Display */
.fee-display {
    display: none;
}

.student-info-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
}

.student-info-card h4 {
    color: #333;
    margin: 0 0 15px;
    font-size: 18px;
}

.student-info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.info-item {
    display: flex;
    flex-direction: column;
}

.info-label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.info-value {
    font-size: 16px;
    font-weight: 600;
    color: #333;
}

.fee-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.fee-table th,
.fee-table td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #e9ecef;
}

.fee-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #333;
}

.fee-table .text-right {
    text-align: right;
}

.fee-table .total-row {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
}

.fee-table .total-row td {
    font-size: 18px;
    font-weight: 700;
    border: none;
}

.payment-methods {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
}

.payment-methods h4 {
    margin: 0 0 15px;
    color: #333;
}

.payment-method-icons {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    align-items: center;
}

.payment-method-icons img {
    height: 35px;
    opacity: 0.8;
}

.payment-method-icons span {
    background: #667eea;
    color: #fff;
    padding: 8px 15px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 500;
}

/* Loading */
.loading-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}

.loading-spinner {
    background: #fff;
    padding: 40px;
    border-radius: 16px;
    text-align: center;
}

.spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #e9ecef;
    border-top-color: #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 15px;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Alert messages */
.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-info {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

/* Responsive */
@media (max-width: 768px) {
    .payment-body {
        padding: 15px;
    }
    
    .step-text {
        display: none;
    }
    
    .step-connector {
        width: 20px;
    }
    
    .form-col {
        min-width: 100%;
    }
    
    .form-col-3 {
        min-width: 48%;
        flex: 0 0 48%;
    }
    
    .compact-row {
        justify-content: space-between;
    }
}

@media (max-width: 480px) {
    .form-col-3 {
        min-width: 100%;
        flex: 0 0 100%;
    }
    
    .payment-header h2 {
        font-size: 20px;
    }
    
    .payment-header {
        padding: 20px;
    }
}
</style>

<div class="payment-page-wrapper">
    <div class="payment-container">
        <div class="payment-card">
            <div class="payment-header">
                <h2><i class="fa fa-credit-card"></i> Online Fee Payment</h2>
                <p>Pay your school fees securely online</p>
            </div>
            
            <div class="payment-body">
                <!-- Step Indicator -->
                <div class="step-indicator">
                    <div class="step active" id="step1-indicator">
                        <span class="step-number">1</span>
                        <span class="step-text">Search</span>
                    </div>
                    <div class="step-connector"></div>
                    <div class="step" id="step2-indicator">
                        <span class="step-number">2</span>
                        <span class="step-text">Review</span>
        </div>
                    <div class="step-connector"></div>
                    <div class="step" id="step3-indicator">
                        <span class="step-number">3</span>
                        <span class="step-text">Pay</span>
    </div>
</div>

                <!-- Error/Success Messages -->
                <div id="message-container"></div>
                
                <!-- Step 1: Search Form -->
                <div id="search-form-section">
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fa fa-search"></i> Find Your Fee Information
                                </div>
                        
                        <form id="fee-search-form">
                            <!-- Row 1: Class, Section, Month -->
                            <div class="form-row compact-row">
                                <div class="form-col-3">
                                                    <div class="form-group">
                                        <label>Class *</label>
                                                        <select id="paystationClass" name="class" class="form-control" required>
                                            <option value="">Select</option>
                                                            <?php
                                            $classQuery = $wpdb->get_results("SELECT classid, className FROM ct_class WHERE classid IN (SELECT infoClass FROM ct_studentinfo GROUP BY infoClass) ORDER BY className ASC");
                                            if (empty($classQuery)) {
                                                $classQuery = $wpdb->get_results("SELECT classid, className FROM ct_class ORDER BY className ASC");
                                            }
                                                            foreach ($classQuery as $class) {
                                                echo '<option value="' . esc_attr($class->classid) . '">' . esc_html($class->className) . '</option>';
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                
                                <div class="form-col-3 sectionDiv">
                                                    <div class="form-group">
                                        <label>Section</label>
                                                        <select id="paystationSection" name="section" class="form-control">
                                            <option value="">Select</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                
                                <div class="form-col-3">
                                                    <div class="form-group">
                                        <label>Month *</label>
                                        <select id="paystationMonth" name="month" class="form-control" required>
                                                            <?php
                                            // Only show January, default selected
//                                             echo '<option value="1" selected>January</option>';
                                            
                                            // PREVIOUS CODE - All months with current month selected (commented for rollback)
                                            
                                            $months = array(
                                                1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
                                                5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
                                                9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
                                            );
                                            $current_month = intval(date('n'));
                                            foreach ($months as $num => $name) {
                                                $selected = ($current_month == $num) ? 'selected' : '';
                                                echo '<option value="' . $num . '" ' . $selected . '>' . $name . '</option>';
                                            }
                                           
                                                            ?>
                                                        </select>
                                    </div>
                                                    </div>
                                                </div>
                                                
                            <!-- Row 2: Year, Roll, Button -->
                            <div class="form-row compact-row">
                                <div class="form-col-3">
                                                    <div class="form-group">
                                        <label>Year *</label>
                                                        <select id="paystationYear" name="year" class="form-control" required>
<!--                                             <option value="2026" selected>2026</option> -->
                                            <!-- PREVIOUS CODE - Select option (commented for rollback) -->
                                            <option value="">Select</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                
                                <div class="form-col-3">
                                    <div class="form-group">
                                        <label>Roll *</label>
                                        <input type="number" id="paystationRoll" name="roll" class="form-control" placeholder="Roll" required>
                                    </div>
                                </div>
                                
                                <div class="form-col-3">
                                                    <div class="form-group">
                                        <label>&nbsp;</label>
                                        <button type="submit" class="btn-calculate-compact">
                                            <i class="fa fa-search"></i> Search
                                        </button>
                                    </div>
                                                    </div>
                                                </div>
                                                
                            <!-- Hidden Group field -->
                            <div class="form-row groupDiv" style="display: none;">
                                <div class="form-col-3">
                                                    <div class="form-group">
                                        <label>Group</label>
                                        <select id="paystationGroup" name="group" class="form-control">
                                            <option value="">Select</option>
                                                            <?php
                                            $groups = $wpdb->get_results("SELECT * FROM ct_group");
                                            foreach ($groups as $group) {
                                                echo '<option value="' . $group->groupId . '">' . $group->groupName . '</option>';
                                                            }
                                                            ?>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                
                <!-- Step 2 & 3: Fee Display and Payment -->
                <div id="fee-display-section" class="fee-display">
                    <!-- Student Info -->
                    <div class="student-info-card">
                        <h4><i class="fa fa-user"></i> Student Information</h4>
                        <div class="student-info-grid">
                            <div class="info-item">
                                <span class="info-label">Name</span>
                                <span class="info-value" id="display-name">-</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Roll</span>
                                <span class="info-value" id="display-roll">-</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Class</span>
                                <span class="info-value" id="display-class">-</span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Section</span>
                                <span class="info-value" id="display-section">-</span>
                                    </div>
                            <div class="info-item">
                                <span class="info-label">Year</span>
                                <span class="info-value" id="display-year">-</span>
                                            </div>
                            <div class="info-item">
                                <span class="info-label">Month</span>
                                <span class="info-value" id="display-month">-</span>
                                                    </div>
                                                    </div>
                                                </div>
                                                
                    <!-- Fee Breakdown -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fa fa-list-alt"></i> Fee Breakdown
                        </div>
                        <table class="fee-table">
                            <thead>
                                <tr>
                                    <th>Fee Type</th>
                                    <th class="text-right">Amount (BDT)</th>
                                </tr>
                            </thead>
                            <tbody id="fee-breakdown-body">
                                <!-- Populated by JavaScript -->
                            </tbody>
                            <tfoot>
                                <tr style="background: #f8f9fa; font-weight: 600; color: #333;">
                                    <td><strong>
                                        Already Paid
                                    </strong></td>
                                    <td class="text-right"><strong id="paid-amount">- 0.00 BDT</strong></td>
                                </tr>

                                <tr class="total-row">
                                                            <td><strong>Total Amount</strong></td>
                                    <td class="text-right"><strong id="total-amount">0.00 BDT</strong></td>
                                                        </tr>
                            </tfoot>
                                                </table>
                                        </div>
                                        
                    <!-- Payment Methods Info -->
                    <!-- <div class="payment-methods">
                        <h4><i class="fa fa-shield"></i> Secure Online Payment</h4>
                        <p style="margin: 0 0 15px; color: #666;">You can pay using any of these methods:</p>
                        <div class="payment-method-icons">
                            <span>bKash</span>
                            <span>Nagad</span>
                            <span>Rocket</span>
                            <span>Cards</span>
                            <span>Bank Transfer</span>
                                                    </div>
                                                </div> -->
                                                
                    <!-- Customer Info Form -->
                    <div class="form-section">
                        <div class="form-section-title">
                            <i class="fa fa-phone"></i> Contact Information
                                                </div>
                                                
                        <form id="payment-form">
                            <div class="form-row">
                                <div class="form-col">
                                                    <div class="form-group">
                                        <label>Phone Number *</label>
                                        <input type="tel" id="cust_phone" name="cust_phone" class="form-control" placeholder="01XXXXXXXXX" required>
                                                    </div>
                                                </div>
                                                
                                <div class="form-col">
                                                    <div class="form-group">
                                        <label>Email (Optional)</label>
                                        <input type="email" id="cust_email" name="cust_email" class="form-control" placeholder="your@email.com">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Address (Optional)</label>
                                <textarea id="cust_address" name="cust_address" class="form-control" rows="2" placeholder="Your address"></textarea>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i> 
                                You will be redirected to secure payment gateway to complete your payment.
                            </div>
                            
                            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                                <button type="button" class="btn-back" onclick="goBackToSearch()">
                                    <i class="fa fa-arrow-left"></i> Back
                                </button>
                                <button type="submit" class="btn-pay" id="pay-now-btn" style="flex: 1;">
                                    <i class="fa fa-lock"></i> Pay Now - <span id="pay-button-amount">0.00</span> BDT
                                </button>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 20px; color: rgba(255,255,255,0.7); font-size: 14px;">
            <i class="fa fa-lock"></i> Secure Online Payment
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loading-overlay">
    <div class="loading-spinner">
        <div class="spinner"></div>
        <p style="margin: 0; color: #333;">Processing...</p>
    </div>
</div>

<?php get_footer(); ?>

<script type="text/javascript">
(function($) {
    var ajaxUrl = "<?php echo get_template_directory_uri(); ?>/inc/ajaxAction.php";
    var feeData = null;
    var studentData = null;
    
    // Debug: Log the AJAX URL on page load
    console.log('PayStation Payment Page Loaded');
    console.log('AJAX URL:', ajaxUrl);
    
    // Initialize year dropdown to 2026 on page load
    $('#paystationYear').html('<option value="2026" selected>2026</option>');
    
    // PREVIOUS CODE - No year initialization (commented for rollback)
    // Year was loaded dynamically when class was selected
    
    var monthNames = {
        1: 'January', 2: 'February', 3: 'March', 4: 'April',
        5: 'May', 6: 'June', 7: 'July', 8: 'August',
        9: 'September', 10: 'October', 11: 'November', 12: 'December'
    };
    
    // Class change - load sections and years
    $('#paystationClass').change(function() {
        var classId = $(this).val();
        
        console.log('Class changed to:', classId);
        
        if (!classId) {
            $('#paystationSection').html('<option value="">Select Section</option>');
            $('#paystationYear').html('<option value="2026" selected>2026</option>');
            // PREVIOUS CODE - Reset to Select option (commented for rollback)
            // $('#paystationYear').html('<option value="">Select Year</option>');
            return;
        }
        
        // Get sections
        $.ajax({
            url: ajaxUrl,
            method: "POST",
            data: { class: classId, type: 'getSection' },
            dataType: "html"
        }).done(function(msg) {
            console.log('Section response:', msg);
            // Check if response is empty, "0", or contains "No sections"
            if (!msg || msg == '0' || msg.indexOf('No sections') > -1) {
                $(".sectionDiv").hide();
                $("#paystationSection").html('<option value="">No Section</option>');
            } else {
                $(".sectionDiv").show();
                // Prepend a "Select Section" option if not present
                if (msg.indexOf('value=""') === -1 && msg.indexOf("value=''") === -1) {
                    msg = '<option value="">Select Section</option>' + msg;
                }
                $("#paystationSection").html(msg);
            }
        }).fail(function(xhr, status, error) {
            console.error('Section AJAX error:', error);
            $(".sectionDiv").hide();
        });
        
        // Get years - only show 2026
        $.ajax({
            url: ajaxUrl,
            method: "POST",
            data: { class: classId, type: 'getYears' },
            dataType: "html"
        }).done(function(msg) {
            console.log('Year response:', msg);
            // Only show 2026, default selected
            $("#paystationYear").html('<option value="2026" selected>2026</option>');
            
            // PREVIOUS CODE - Load all years dynamically (commented for rollback)
            /*
            if (msg && msg.length > 0) {
                // Prepend a "Select Year" option if not present
                if (msg.indexOf('value=""') === -1 && msg.indexOf("value=''") === -1) {
                    msg = '<option value="">Select Year</option>' + msg;
                }
                $("#paystationYear").html(msg);
            } else {
                $("#paystationYear").html('<option value="">No Years Available</option>');
            }
            */
        }).fail(function(xhr, status, error) {
            console.error('Year AJAX error:', error);
            // Default to 2026 even on error
            $("#paystationYear").html('<option value="2026" selected>2026</option>');
            
            // PREVIOUS CODE - Show error message (commented for rollback)
            // $("#paystationYear").html('<option value="">Error loading years</option>');
        });
        
        // Check if class has groups
        $.ajax({
            url: ajaxUrl,
            method: "POST",
            data: { class: classId, type: 'hasGroup' },
            dataType: "text"
        }).done(function(msg) {
            console.log('HasGroup response:', msg);
            if (msg && msg.trim() === 'true') {
                $(".groupDiv").show();
            } else {
                $(".groupDiv").hide();
                $("#paystationGroup").val('');
            }
        }).fail(function(xhr, status, error) {
            console.error('HasGroup AJAX error:', error);
            $(".groupDiv").hide();
        });
    });
    
    // Fee Search Form Submit
    $('#fee-search-form').submit(function(e) {
        e.preventDefault();
        
        var classId = $('#paystationClass').val();
        var section = $('#paystationSection').val();
        var group = $('#paystationGroup').val();
        var year = $('#paystationYear').val();
        var roll = $('#paystationRoll').val();
        var month = $('#paystationMonth').val();
        
        if (!classId || !year || !roll) {
            showMessage('Please fill in all required fields', 'danger');
            return;
        }
        
        showLoading();
        
        $.ajax({
            url: ajaxUrl,
            method: "POST",
            data: {
                type: 'getPaystationFeeInfo',
                class: classId,
                section: section,
                group: group,
                year: year,
                roll: roll,
                month: month
            },
            dataType: "json"
        }).done(function(response) {
            hideLoading();
            
            if (response.success) {
                feeData = response;
                studentData = {
                    student_id: response.student_id,
                    student_name: response.student_name,
                    student_roll: response.student_roll,
                    stdUniqueID: response.stdUniqueID || '',
                    class_id: response.class_id,
                    section: response.section,
                    group_id: response.group_id,
                    year: response.year
                };
                
                displayFeeInfo(response);
            } else {
                showMessage(response.message || 'Student not found', 'danger');
            }
        }).fail(function() {
            hideLoading();
            showMessage('Failed to calculate fee. Please try again.', 'danger');
        });
    });
    
    // Display fee information
    function displayFeeInfo(data) {
        // Update student info
        $('#display-name').text(data.student_name);
        $('#display-roll').text(data.student_roll);
        $('#display-class').text(data.class_name || '-');
        $('#display-section').text(data.section_name || '-');
        $('#display-year').text(data.year);
        $('#display-month').text(monthNames[data.month] || data.month);
        
        // Build fee breakdown table
        var tbody = '';
        if (data.fee_breakdown && data.fee_breakdown.length > 0) {
            data.fee_breakdown.forEach(function(item) {
                tbody += '<tr>' +
                    '<td>' + item.sub_head_name + '</td>' +
                    '<td class="text-right">' + parseFloat(item.amount).toFixed(2) + '</td>' +
                    '</tr>';
            });
        } else {
            tbody = '<tr><td colspan="2" style="text-align:center;">No pending fees</td></tr>';
        }
        
        $('#fee-breakdown-body').html(tbody);
        $('#paid-amount').text( '- ' + parseFloat(data.paid_amount).toFixed(2) + ' BDT');
        $('#total-amount').text(parseFloat(data.total_amount).toFixed(2) + ' BDT');
        $('#pay-button-amount').text(parseFloat(data.total_amount).toFixed(2));
        $('#pay-now-btn').prop('disabled', data.total_amount <= 0);
        
        // Update step indicators
        $('#step1-indicator').removeClass('active').addClass('completed');
        $('#step2-indicator').addClass('active');
        
        // Show fee display, hide search form
        $('#search-form-section').hide();
        $('#fee-display-section').show();
        
        // Check if no fees to pay
        if (data.total_amount <= 0) {
            showMessage('No pending fees for this student.', 'info');
            $('#payment-form button[type="submit"]').prop('disabled', true);
        }
    }
    
    // Go back to search
    window.goBackToSearch = function() {
        $('#fee-display-section').hide();
        $('#search-form-section').show();
        
        $('#step1-indicator').addClass('active').removeClass('completed');
        $('#step2-indicator').removeClass('active completed');
        $('#step3-indicator').removeClass('active completed');
        
        $('#message-container').html('');
        feeData = null;
        studentData = null;
    };
    
    // Payment Form Submit
    $('#payment-form').on('submit', function(e) {
        e.preventDefault();
        
        console.log('Payment form submitted');
        console.log('feeData:', feeData);
        console.log('studentData:', studentData);
        
        var phone = $('#cust_phone').val();
        var email = $('#cust_email').val();
        var address = $('#cust_address').val();
        
        console.log('Phone:', phone);
        
        if (!phone) {
            showMessage('Phone number is required for payment', 'danger');
            return false;
        }
        
        if (!feeData) {
            showMessage('Fee data not found. Please calculate fee first.', 'danger');
            return false;
        }
        
        if (feeData.total_amount <= 0) {
            showMessage('No valid fee to pay', 'danger');
            return false;
        }
        
        if (!studentData) {
            showMessage('Student data not found. Please search again.', 'danger');
            return false;
        }
        
        // Update step indicator
        $('#step2-indicator').removeClass('active').addClass('completed');
        $('#step3-indicator').addClass('active');
        
        showLoading();
        
        var postData = {
            type: 'initiatePaystationPayment',
            student_id: studentData.student_id,
            student_name: studentData.student_name,
            student_roll: studentData.student_roll,
            stdUniqueID: studentData.stdUniqueID || '',
            class_id: studentData.class_id,
            section: studentData.section || '',
            group_id: studentData.group_id || '',
            year: studentData.year,
            month: feeData.month,
            cust_phone: phone,
            cust_email: email || '',
            cust_address: address || '',
            fee_breakdown: JSON.stringify(feeData.fee_breakdown),
            sub_total: feeData.sub_total,
            remission: feeData.remission || 0,
            total_amount: feeData.total_amount
        };
        
        console.log('Sending payment data:', postData);
        
        $.ajax({
            url: ajaxUrl,
            method: "POST",
            data: postData,
            dataType: "json"
        }).done(function(response) {
            console.log('Payment response:', response);
            hideLoading();
            
            if (response && response.success && response.payment_url) {
                showMessage('Redirecting to payment gateway...', 'success');
                // Redirect to payment gateway
                setTimeout(function() {
                    window.location.href = response.payment_url;
                }, 1000);
            } else {
                var errorMsg = (response && response.message) ? response.message : 'Failed to initiate payment';
                console.error('Payment error:', errorMsg);
                showMessage(errorMsg, 'danger');
                $('#step3-indicator').removeClass('active');
                $('#step2-indicator').addClass('active').removeClass('completed');
            }
        }).fail(function(xhr, status, error) {
            console.error('Payment AJAX failed:', status, error);
            console.error('Response:', xhr.responseText);
            hideLoading();
            showMessage('Failed to initiate payment. Please try again. Error: ' + error, 'danger');
            $('#step3-indicator').removeClass('active');
            $('#step2-indicator').addClass('active').removeClass('completed');
        });
        
        return false;
    });
    
    // Helper functions
    function showLoading() {
        $('#loading-overlay').css('display', 'flex');
    }
    
    function hideLoading() {
        $('#loading-overlay').hide();
    }
    
    function showMessage(message, type) {
        var alertClass = 'alert-' + type;
        var icon = type === 'success' ? 'check-circle' : (type === 'danger' ? 'times-circle' : 'info-circle');
        var html = '<div class="alert ' + alertClass + '">' +
            '<i class="fa fa-' + icon + '"></i> ' + message +
            '</div>';
        $('#message-container').html(html);
        
        // Scroll to message
        $('html, body').animate({
            scrollTop: $('#message-container').offset().top - 100
        }, 300);
    }
    
})(jQuery);
</script>
