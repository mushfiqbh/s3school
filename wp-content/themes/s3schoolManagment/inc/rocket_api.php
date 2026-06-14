<?php
/**
 * Barnomala Rocket API
 * Simplified payment API using student unique IDs (MCNK-00001, etc.)
 * This is a separate module from the existing payment API for backward compatibility
 */

if (!defined('ABSPATH')) {
    exit;
}

// Include student unique ID functions
require_once(get_template_directory() . '/inc/student-unique-id.php');

/**
 * Add CORS headers to Rocket API responses
 */
function barnomala_rocket_add_cors_headers($served, $result, $request, $server) {
    if (strpos($request->get_route(), '/barnomala-rocket/v1/') !== false && is_a($result, 'WP_REST_Response')) {
        $result->header('Access-Control-Allow-Origin', '*');
        $result->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $result->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, api_key, api_secret');
        $result->header('Access-Control-Max-Age', '86400');
    }
    return $served;
}
add_filter('rest_pre_serve_request', 'barnomala_rocket_add_cors_headers', 0, 4);

/**
 * Handle OPTIONS preflight requests for Rocket API
 */
function barnomala_rocket_handle_options_request() {
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS' && strpos($_SERVER['REQUEST_URI'], '/barnomala-rocket/v1/') !== false) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, api_key, api_secret');
        header('Access-Control-Max-Age: 86400');
        status_header(200);
        exit();
    }
}
add_action('init', 'barnomala_rocket_handle_options_request', 0);

/**
 * Initialize Rocket API
 */
function barnomala_rocket_api_init() {
    // Register REST API routes
    $namespace = 'barnomala-rocket/v1';
    
    // Register calculate fee endpoint
    $result1 = register_rest_route($namespace, '/fee/calculate', array(
        'methods' => 'GET',
        'callback' => 'barnomala_rocket_calculate_fee',
        'permission_callback' => '__return_true',
    ));

    // Register confirm payment endpoint
    $result2 = register_rest_route($namespace, '/fee/confirm', array(
        'methods' => 'POST',
        'callback' => 'barnomala_rocket_confirm_payment',
        'permission_callback' => '__return_true',
    ));

    // Register status check endpoint
    $result3 = register_rest_route($namespace, '/fee/status', array(
        'methods' => 'GET',
        'callback' => 'barnomala_rocket_check_payment_status',
        'permission_callback' => '__return_true',
    ));
    
    // Debug logging
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Barnomala Rocket API: Routes registration - Calculate: ' . ($result1 ? 'OK' : 'FAILED') . ', Confirm: ' . ($result2 ? 'OK' : 'FAILED') . ', Status: ' . ($result3 ? 'OK' : 'FAILED'));
    }
}
add_action('rest_api_init', 'barnomala_rocket_api_init', 10);

/**
 * Authenticate Rocket API request (reuse existing authentication)
 */
function barnomala_rocket_authenticate_api($api_key, $api_secret) {
    // Reuse the same authentication as the main API
    $stored_key = get_option('barnomala_api_key', '');
    $stored_secret = get_option('barnomala_api_secret', '');
    
    if (empty($stored_key)) {
        $stored_key = 'barnomala_test_api_key_12345';
    }
    if (empty($stored_secret)) {
        $stored_secret = 'barnomala_test_api_secret_67890';
    }
    
    return ($api_key === $stored_key && $api_secret === $stored_secret);
}

/**
 * Log Rocket API request
 */
function barnomala_rocket_log_api_request($endpoint, $request_data, $response_data, $status) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'barnomala_api_logs';
    
    // Create table if it doesn't exist (reuse from main API)
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        endpoint varchar(255) NOT NULL,
        request_data text,
        response_data text,
        status varchar(50),
        ip_address varchar(45),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Insert log
    $wpdb->insert(
        $table_name,
        array(
            'endpoint' => $endpoint,
            'request_data' => json_encode($request_data),
            'response_data' => json_encode($response_data),
            'status' => $status,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        )
    );
}

/**
 * Store Rocket API transaction
 * payment_id is our internal reference, transaction_id will be added by third-party on confirm
 */
function barnomala_rocket_store_transaction($payment_id, $student_data, $fee_data, $expires_at) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'barnomala_transactions';
    
    // Ensure table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    if (!$table_exists) {
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            transaction_id varchar(255) DEFAULT NULL,
            student_id bigint(20),
            student_data text,
            fee_data text,
            total_amount decimal(10,2),
            expires_at datetime,
            status varchar(50) DEFAULT 'pending',
            payment_id varchar(255) NOT NULL UNIQUE,
            payment_date datetime,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY payment_id (payment_id),
            KEY transaction_id (transaction_id),
            KEY status (status),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    // Insert or update transaction using payment_id as the key
    $wpdb->replace(
        $table_name,
        array(
            'payment_id' => $payment_id,
            'student_id' => $student_data['student_id'],
            'student_data' => json_encode($student_data),
            'fee_data' => json_encode($fee_data),
            'total_amount' => $fee_data['total_amount'],
            'expires_at' => $expires_at,
            'status' => 'pending',
        ),
        array('%s', '%d', '%s', '%s', '%f', '%s', '%s')
    );
}

/**
 * Get Rocket API transaction by payment_id (our internal reference)
 */
function barnomala_rocket_get_transaction_by_payment_id($payment_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'barnomala_transactions';
    $transaction = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE payment_id = %s",
        $payment_id
    ));
    
    if ($transaction) {
        if ($transaction->student_data) {
            $transaction->student_data = json_decode($transaction->student_data, true);
        }
        if ($transaction->fee_data) {
            $transaction->fee_data = json_decode($transaction->fee_data, true);
        }
    }
    
    return $transaction;
}

/**
 * Get Rocket API transaction by transaction_id (rocket transaction ID from payment gateway)
 */
function barnomala_rocket_get_transaction_by_transaction_id($transaction_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'barnomala_transactions';
    $transaction = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE transaction_id = %s",
        $transaction_id
    ));
    
    if ($transaction) {
        if ($transaction->student_data) {
            $transaction->student_data = json_decode($transaction->student_data, true);
        }
        if ($transaction->fee_data) {
            $transaction->fee_data = json_decode($transaction->fee_data, true);
        }
    }
    
    return $transaction;
}

/**
 * Calculate Fee Endpoint (Rocket API)
 * Uses unique ID instead of class/year/roll/section/group
 */
function barnomala_rocket_calculate_fee($request) {
    global $wpdb;
    
    // Get global variables
    global $admissionFeeSubHeadId, $admissionFormSubHeadId, $monthlyFeeSubHeadId;
    global $examFeeSubHeadId, $transportFeeSubHeadId, $ictFeeSubHeadId;
    global $registrationFeeSubHeadId, $coachingFeeSubHeadId;
    global $cashSubHeadId;
    
    // Get request parameters
    $params = $request->get_query_params();
    $api_key = sanitize_text_field($params['api_key'] ?? '');
    $api_secret = sanitize_text_field($params['api_secret'] ?? '');
    $stdUniqueID = sanitize_text_field($params['stdUniqueID'] ?? '');
    
    // Use current month (1-12) if not provided
    $month = isset($params['month']) && !empty($params['month']) ? intval($params['month']) : intval(date('n'));
    
    // Use current year (4 digits) if not provided
    $year = isset($params['year']) && !empty($params['year']) ? sanitize_text_field($params['year']) : date('Y');
    
    // Validate month is between 1-12
    if ($month < 1 || $month > 12) {
        $month = intval(date('n')); // Reset to current month if invalid
    }
    
    // Validate year is 4 digits
    if (strlen($year) !== 4 || !is_numeric($year)) {
        $year = date('Y'); // Reset to current year if invalid
    }
    
    // Authenticate
    if (!barnomala_rocket_authenticate_api($api_key, $api_secret)) {
        $error_response = array(
            'success' => false,
            'error' => array(
                'code' => 'UNAUTHORIZED',
                'message' => 'Invalid API credentials'
            )
        );
        barnomala_rocket_log_api_request('/fee/calculate', $params, $error_response, 'error');
        $rest_response = rest_ensure_response($error_response);
        $rest_response->set_status(401);
        $rest_response->header('Access-Control-Allow-Origin', '*');
        $rest_response->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $rest_response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        return $rest_response;
    }
    
    // Validate required parameters
    if (empty($stdUniqueID)) {
        $error_response = array(
            'success' => false,
            'error' => array(
                'code' => 'MISSING_PARAMETER',
                'message' => 'Missing required parameter: stdUniqueID is required'
            )
        );
        barnomala_rocket_log_api_request('/fee/calculate', $params, $error_response, 'error');
        $rest_response = rest_ensure_response($error_response);
        $rest_response->set_status(400);
        $rest_response->header('Access-Control-Allow-Origin', '*');
        $rest_response->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $rest_response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        return $rest_response;
    }
    
    // Find student by unique ID and get current year info
    $student = $wpdb->get_row($wpdb->prepare(
        "SELECT s.*, si.infoClass, si.infoRoll, si.infoSection, si.infoGroup, si.infoYear,
         swf.transport_fee_id, swf.transport_type, swf.transport_required
         FROM ct_student s
         INNER JOIN ct_studentinfo si ON s.studentid = si.infoStdid
         LEFT JOIN ct_student_wise_fee swf ON s.studentid = swf.student_id 
             AND swf.class_id = si.infoClass 
             AND swf.year = si.infoYear 
             AND swf.fee_type = 3
         WHERE s.stdUniqueID = %s
         AND si.infoYear = %s
         AND s.stdStatus = 1
         LIMIT 1",
        $stdUniqueID,
        $year
    ));
    
    if (!$student) {
        $error_response = array(
            'success' => false,
            'error' => array(
                'code' => 'STUDENT_NOT_FOUND',
                'message' => 'Student not found with the provided unique ID and year'
            )
        );
        barnomala_rocket_log_api_request('/fee/calculate', $params, $error_response, 'error');
        $rest_response = rest_ensure_response($error_response);
        $rest_response->set_status(404);
        $rest_response->header('Access-Control-Allow-Origin', '*');
        $rest_response->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $rest_response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        return $rest_response;
    }
    
    // Extract student info
    $student_id = $student->studentid;
    $class_id = $student->infoClass;
    $section = $student->infoSection ?: null;
    $group_id = $student->infoGroup ?: null;
    $roll = $student->infoRoll;
    
    // Get class and section names
    $class_name = $wpdb->get_var($wpdb->prepare(
        "SELECT className FROM ct_class WHERE classid = %d",
        $class_id
    ));
    
    $section_name = null;
    if ($section) {
        $section_name = $wpdb->get_var($wpdb->prepare(
            "SELECT sectionName FROM ct_section WHERE sectionid = %d",
            $section
        ));
    }
    
    $group_name = null;
    if ($group_id) {
        $group_name = $wpdb->get_var($wpdb->prepare(
            "SELECT groupName FROM ct_group WHERE groupId = %d",
            $group_id
        ));
    }
    
    // Reuse fee calculation logic from main API
    // Get active fee sub-heads
    $sub_heads = $wpdb->get_results(
        "SELECT * FROM ct_sub_head 
        WHERE active_for_collection = 1 
        AND relation_to = 1 
        AND isHidden IS NULL 
        ORDER BY sort_order ASC, sub_head_name ASC"
    );
    
    $fee_breakdown = array();
    $sub_total = 0;
    $fee_month = $month; // Already set to current month if not provided
    
    foreach ($sub_heads as $sub_head) {
        $sub_head_id = $sub_head->id;
        $sub_head_type = $sub_head->type;
        
        // Get base fee from fee list
        $fee_query = "SELECT fee FROM ct_student_fee_list 
                     WHERE sub_head_id = %d AND class_id = %d AND year = %s";
        $fee_params = array($sub_head_id, $class_id, $year);
        
        if ($group_id) {
            $fee_query .= " AND group_id = %d";
            $fee_params[] = $group_id;
        }
        
        $fee_query .= " ORDER BY id DESC LIMIT 1";
        $fee_result = $wpdb->get_row($wpdb->prepare($fee_query, $fee_params));
        $base_fee = $fee_result ? floatval($fee_result->fee) : 0;
        
        $amount = 0;
        
        if ($sub_head_type == 1) {
            // Monthly fee - reuse logic from main API
            $sum_of_fees = 0;
            
            for ($i = $fee_month; $i >= 1; $i--) {
                $paid_query = "SELECT fee FROM ct_student_monthly_fee_summary 
                              WHERE sub_head_id = %d AND class_id = %d AND year = %s 
                              AND month = %d AND student_id = %d";
                $paid_params = array($sub_head_id, $class_id, $year, $i, $student_id);
                
                if ($section) {
                    $paid_query .= " AND section = %d";
                    $paid_params[] = $section;
                }
                if ($group_id) {
                    $paid_query .= " AND group_id = %d";
                    $paid_params[] = $group_id;
                }
                
                $paid_check = $wpdb->get_var($wpdb->prepare($paid_query, $paid_params));
                
                if (!$paid_check) {
                    $fee_amount = $base_fee;
                    
                    if ($sub_head_id == $monthlyFeeSubHeadId) {
                        if ($student->facilities == 'Full free' || $student->facilities == 'Scholarship') {
                            $fee_amount = 0;
                        } elseif ($student->facilities == 'Half free') {
                            $fee_amount = $fee_amount / 2;
                        } else {
                            $student_fee = $wpdb->get_var($wpdb->prepare(
                                "SELECT monthly_fee FROM ct_student WHERE studentid = %d",
                                $student_id
                            ));
                            if ($student_fee > 0) {
                                $fee_amount = floatval($student_fee);
                            }
                        }
                    } elseif ($sub_head_id == $transportFeeSubHeadId) {
                        if ($student->transport_required == 1) {
                            $transport_fee_id = $student->transport_fee_id;
                            $transport_fee = $wpdb->get_var($wpdb->prepare(
                                "SELECT amount FROM ct_transport_fee_list WHERE id = %d",
                                $transport_fee_id
                            ));
                            if ($transport_fee) {
                                $fee_amount = floatval($transport_fee);
                                if ($student->transport_type == 1) {
                                    $fee_amount = $fee_amount / 2;
                                }
                            } else {
                                $fee_amount = 0;
                            }
                        } else {
                            $fee_amount = 0;
                        }
                    } elseif ($sub_head_id == $coachingFeeSubHeadId) {
                        $coaching_query = "SELECT amount FROM ct_student_wise_fee 
                                          WHERE fee_type = 1 AND student_id = %d 
                                          AND class_id = %d AND year = %s";
                        $coaching_params = array($student_id, $class_id, $year);
                        if ($section) {
                            $coaching_query .= " AND section = %d";
                            $coaching_params[] = $section;
                        }
                        if ($group_id) {
                            $coaching_query .= " AND group_id = %d";
                            $coaching_params[] = $group_id;
                        }
                        $coaching_fee = $wpdb->get_var($wpdb->prepare($coaching_query, $coaching_params));
                        if ($coaching_fee && $coaching_fee > 0) {
                            $fee_amount = floatval($coaching_fee);
                        } else {
                            $fee_amount = 0;
                        }
                    }
                    
                    $sum_of_fees += $fee_amount;
                }
            }
            
            $amount = $sum_of_fees;
            
        } elseif ($sub_head_type == 2) {
            // Yearly fee
            $paid_query = "SELECT fee FROM ct_student_yearly_fee_summary 
                          WHERE sub_head_id = %d AND class_id = %d AND year = %s 
                          AND student_id = %d";
            $paid_params = array($sub_head_id, $class_id, $year, $student_id);
            
            if ($section) {
                $paid_query .= " AND section = %d";
                $paid_params[] = $section;
            }
            if ($group_id) {
                $paid_query .= " AND group_id = %d";
                $paid_params[] = $group_id;
            }
            
            $paid_check = $wpdb->get_var($wpdb->prepare($paid_query, $paid_params));
            
            if (!$paid_check) {
                $amount = $base_fee;
                
                if ($sub_head_id == $admissionFeeSubHeadId) {
                    if ($student->admission_type == 1) {
                        if ($student->facilities == 'Half free') {
                            $amount = $amount / 2;
                        }
                    } else {
                        $promoted_fee = $wpdb->get_var($wpdb->prepare(
                            "SELECT amount FROM ct_admission_fee_promoted WHERE class = %d",
                            $class_id
                        ));
                        if ($promoted_fee) {
                            $amount = floatval($promoted_fee);
                            if ($student->facilities == 'Half free') {
                                $amount = $amount / 2;
                            }
                        } else {
                            $amount = 0;
                        }
                    }
                } elseif ($sub_head_id == $admissionFormSubHeadId) {
                    if ($student->facilities == 'Half free') {
                        $amount = $amount / 2;
                    }
                } elseif ($sub_head_id == $registrationFeeSubHeadId) {
                    $reg_query = "SELECT amount FROM ct_student_wise_fee 
                                 WHERE fee_type = 2 AND student_id = %d 
                                 AND class_id = %d AND year = %s";
                    $reg_params = array($student_id, $class_id, $year);
                    if ($section) {
                        $reg_query .= " AND section = %d";
                        $reg_params[] = $section;
                    }
                    if ($group_id) {
                        $reg_query .= " AND group_id = %d";
                        $reg_params[] = $group_id;
                    }
                    $reg_fee = $wpdb->get_var($wpdb->prepare($reg_query, $reg_params));
                    if ($reg_fee && $reg_fee > 0) {
                        $amount = floatval($reg_fee);
                    }
                }
            }
            
        } elseif ($sub_head_type == 3) {
            // Exam fee
            $active_exam = $wpdb->get_row($wpdb->prepare(
                "SELECT examid FROM ct_exam 
                WHERE active_for_collection = 1 AND examClass = %d LIMIT 1",
                $class_id
            ));
            
            if ($active_exam) {
                $exam_id = $active_exam->examid;
                $paid_query = "SELECT fee FROM ct_student_exam_fee_summary 
                              WHERE sub_head_id = %d AND class_id = %d AND exam_id = %d 
                              AND year = %s AND student_id = %d";
                $paid_params = array($sub_head_id, $class_id, $exam_id, $year, $student_id);
                
                if ($section) {
                    $paid_query .= " AND section = %d";
                    $paid_params[] = $section;
                }
                if ($group_id) {
                    $paid_query .= " AND group_id = %d";
                    $paid_params[] = $group_id;
                }
                
                $paid_check = $wpdb->get_var($wpdb->prepare($paid_query, $paid_params));
                
                if (!$paid_check) {
                    $amount = $base_fee;
                }
            }
        } else {
            $amount = $base_fee;
        }
        
        if ($amount > 0) {
            $fee_breakdown[] = array(
                'sub_head_id' => $sub_head_id,
                'sub_head_name' => $sub_head->sub_head_name,
                'fee_type' => $sub_head_type == 1 ? 'monthly' : ($sub_head_type == 2 ? 'yearly' : ($sub_head_type == 3 ? 'exam' : 'other')),
                'amount' => round($amount, 2)
            );
            $sub_total += $amount;
        }
    }
    
    $remission = 0;
    $total_amount = $sub_total - $remission;
    
    // Generate payment_id (our internal reference)
    $payment_id = 'PAY-' . date('Ymd') . '-' . wp_generate_password(8, false);
    
    // Set expiration (24 hours from now)
    $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    // Store transaction with payment_id
    $student_data = array(
        'student_id' => $student_id,
        'student_name' => $student->stdName,
        'student_roll' => $roll,
        'stdUniqueID' => $stdUniqueID,
        'class_id' => $class_id,
        'section' => $section,
        'group_id' => $group_id,
        'year' => $year,
    );
    
    $fee_data = array(
        'fee_breakdown' => $fee_breakdown,
        'sub_total' => round($sub_total, 2),
        'remission' => round($remission, 2),
        'total_amount' => round($total_amount, 2),
        'month' => $fee_month,
    );
    
    barnomala_rocket_store_transaction($payment_id, $student_data, $fee_data, $expires_at);
    
    // Prepare response
    $response = array(
        'success' => true,
        'data' => array(
            'student_id' => $student_id,
            'student_name' => $student->stdName,
            'stdUniqueID' => $stdUniqueID,
            'student_roll' => $roll,
            'class_id' => $class_id,
            'class_name' => $class_name,
            'section' => $section,
            'section_name' => $section_name,
            'group_id' => $group_id,
            'group_name' => $group_name,
            'year' => $year,
            'month' => $fee_month,
            'fee_breakdown' => $fee_breakdown,
            'sub_total' => round($sub_total, 2),
            'remission' => round($remission, 2),
            'total_amount' => round($total_amount, 2),
            'currency' => 'BDT',
            'payment_id' => $payment_id,
            'expires_at' => $expires_at,
        )
    );
    
    barnomala_rocket_log_api_request('/fee/calculate', $params, $response, 'success');
    
    $rest_response = rest_ensure_response($response);
    $rest_response->header('Access-Control-Allow-Origin', '*');
    $rest_response->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    $rest_response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
    
    return $rest_response;
}

/**
 * Confirm Payment Endpoint (Rocket API)
 * Accepts payment_id (from calculate response) and transaction_id (rocket transaction ID from third-party payment gateway)
 */
function barnomala_rocket_confirm_payment($request) {
    global $wpdb;
    
    // Get request body
    $body = $request->get_json_params();
    if (empty($body)) {
        $body = $request->get_body_params();
    }
    if (empty($body)) {
        $raw_body = $request->get_body();
        if (!empty($raw_body)) {
            $body = json_decode($raw_body, true);
        }
    }
    
    $api_key = sanitize_text_field($body['api_key'] ?? '');
    $api_secret = sanitize_text_field($body['api_secret'] ?? '');
    $payment_id = sanitize_text_field($body['payment_id'] ?? '');
    $transaction_id = sanitize_text_field($body['transaction_id'] ?? ''); // Rocket transaction ID from payment gateway
    $payment_method = sanitize_text_field($body['payment_method'] ?? 'rocket');
    $payment_date = sanitize_text_field($body['payment_date'] ?? date('Y-m-d H:i:s'));
    $amount_paid = isset($body['amount_paid']) ? floatval($body['amount_paid']) : 0;
    
    // Authenticate
    if (!barnomala_rocket_authenticate_api($api_key, $api_secret)) {
        $error_response = array(
            'success' => false,
            'error' => array(
                'code' => 'UNAUTHORIZED',
                'message' => 'Invalid API credentials'
            )
        );
        barnomala_rocket_log_api_request('/fee/confirm', $body, $error_response, 'error');
        $rest_response = rest_ensure_response($error_response);
        $rest_response->set_status(401);
        $rest_response->header('Access-Control-Allow-Origin', '*');
        return $rest_response;
    }
    
    // Validate required parameters
    if (empty($payment_id)) {
        $error_response = array(
            'success' => false,
            'error' => array(
                'code' => 'MISSING_PARAMETER',
                'message' => 'Missing required parameter: payment_id is required'
            )
        );
        barnomala_rocket_log_api_request('/fee/confirm', $body, $error_response, 'error');
        $rest_response = rest_ensure_response($error_response);
        $rest_response->set_status(400);
        $rest_response->header('Access-Control-Allow-Origin', '*');
        return $rest_response;
    }
    
    if (empty($transaction_id)) {
        $error_response = array(
            'success' => false,
            'error' => array(
                'code' => 'MISSING_PARAMETER',
                'message' => 'Missing required parameter: transaction_id is required (rocket transaction ID from payment gateway)'
            )
        );
        barnomala_rocket_log_api_request('/fee/confirm', $body, $error_response, 'error');
        $rest_response = rest_ensure_response($error_response);
        $rest_response->set_status(400);
        $rest_response->header('Access-Control-Allow-Origin', '*');
        return $rest_response;
    }
    
    // Get transaction by payment_id
    $transaction = barnomala_rocket_get_transaction_by_payment_id($payment_id);
    
    if (!$transaction) {
        $error_response = array(
            'success' => false,
            'error' => array(
                'code' => 'INVALID_PAYMENT_ID',
                'message' => 'Payment ID not found or invalid'
            )
        );
        barnomala_rocket_log_api_request('/fee/confirm', $body, $error_response, 'error');
        $rest_response = rest_ensure_response($error_response);
        $rest_response->set_status(404);
        $rest_response->header('Access-Control-Allow-Origin', '*');
        return $rest_response;
    }
    
    // Check if already processed
    if ($transaction->status === 'paid') {
        $error_response = array(
            'success' => false,
            'error' => array(
                'code' => 'PAYMENT_ALREADY_PROCESSED',
                'message' => 'This payment has already been processed'
            )
        );
        barnomala_rocket_log_api_request('/fee/confirm', $body, $error_response, 'error');
        $rest_response = rest_ensure_response($error_response);
        $rest_response->set_status(400);
        $rest_response->header('Access-Control-Allow-Origin', '*');
        return $rest_response;
    }
    
    // Check expiration
    if (strtotime($transaction->expires_at) < time()) {
        $error_response = array(
            'success' => false,
            'error' => array(
                'code' => 'PAYMENT_EXPIRED',
                'message' => 'Payment ID has expired'
            )
        );
        barnomala_rocket_log_api_request('/fee/confirm', $body, $error_response, 'error');
        $rest_response = rest_ensure_response($error_response);
        $rest_response->set_status(400);
        $rest_response->header('Access-Control-Allow-Origin', '*');
        return $rest_response;
    }
    
    // Verify amount (allow small difference for rounding)
    $expected_amount = floatval($transaction->total_amount);
    if (abs($amount_paid - $expected_amount) > 0.01) {
        $error_response = array(
            'success' => false,
            'error' => array(
                'code' => 'AMOUNT_MISMATCH',
                'message' => sprintf('Amount mismatch. Expected: %.2f, Received: %.2f', $expected_amount, $amount_paid)
            )
        );
        barnomala_rocket_log_api_request('/fee/confirm', $body, $error_response, 'error');
        $rest_response = rest_ensure_response($error_response);
        $rest_response->set_status(400);
        $rest_response->header('Access-Control-Allow-Origin', '*');
        return $rest_response;
    }
    
    // Get student and fee data
    $student_data = $transaction->student_data;
    $fee_data = $transaction->fee_data;
    $student_id = $student_data['student_id'];
    $class_id = $student_data['class_id'];
    $section = $student_data['section'];
    $group_id = $student_data['group_id'];
    $year = $student_data['year'];
    $fee_month = $fee_data['month'];
    
    // Get global variables
    global $admissionFeeSubHeadId, $admissionFormSubHeadId, $monthlyFeeSubHeadId;
    global $examFeeSubHeadId, $transportFeeSubHeadId, $ictFeeSubHeadId;
    global $registrationFeeSubHeadId, $coachingFeeSubHeadId;
    global $cashSubHeadId;
    
    // Get cash sub head ID
    if (!isset($cashSubHeadId)) {
        $cashSubHeadId = $wpdb->get_var("SELECT id FROM ct_sub_head WHERE sub_head_name LIKE '%Cash%' LIMIT 1");
        if (!$cashSubHeadId) {
            $cashSubHeadId = 0;
        }
    }
    
    // Insert into fee collection info
    $info_id = $wpdb->insert(
        'ct_student_fee_collection_info',
        array(
            'student_roll' => $student_data['student_roll'],
            'student_id' => $student_id,
            'year' => $year,
            'month' => $fee_month,
            'class_id' => $class_id,
            'section' => $section,
            'group_id' => $group_id,
            'sub_total' => $fee_data['sub_total'],
            'total' => $fee_data['total_amount'],
            'remission' => $fee_data['remission'],
            'remission_category' => null,
            'status' => 1, // Paid
            'notes' => 'Rocket API: ' . substr($payment_id, -10) . '/' . substr($transaction_id, -12),
            'date' => $payment_date,
            'created_by' => 0, // System/API
            'created_at' => date('Y-m-d H:i:s'),
        )
    );
    
    if (!$info_id) {
        $error_response = array(
            'success' => false,
            'error' => array(
                'code' => 'DATABASE_ERROR',
                'message' => 'Failed to save payment record: ' . $wpdb->last_error
            )
        );
        barnomala_rocket_log_api_request('/fee/confirm', $body, $error_response, 'error');
        $rest_response = rest_ensure_response($error_response);
        $rest_response->set_status(500);
        $rest_response->header('Access-Control-Allow-Origin', '*');
        return $rest_response;
    }
    
    $collection_info_id = $wpdb->insert_id;
    
    // Insert fee breakdown details
    foreach ($fee_data['fee_breakdown'] as $fee_item) {
        $wpdb->insert(
            'ct_student_fee_collection_details',
            array(
                'info_id' => $collection_info_id,
                'sub_head_id' => $fee_item['sub_head_id'],
                'fee' => $fee_item['amount'],
            )
        );
    }
    
    // Update transaction record with rocket transaction_id (from payment gateway) and mark as paid
    $table_name = $wpdb->prefix . 'barnomala_transactions';
    $wpdb->update(
        $table_name,
        array(
            'transaction_id' => $transaction_id,
            'status' => 'paid',
            'payment_date' => $payment_date,
        ),
        array('payment_id' => $payment_id),
        array('%s', '%s', '%s'),
        array('%s')
    );
    
    // Process monthly/yearly/exam fee summaries based on fee breakdown
    foreach ($fee_data['fee_breakdown'] as $fee_item) {
        $sub_head_id = $fee_item['sub_head_id'];
        $amount = $fee_item['amount'];
        $fee_type = $fee_item['fee_type'];
        
        if ($fee_type === 'monthly') {
            $wpdb->insert(
                'ct_student_monthly_fee_summary',
                array(
                    'student_id' => $student_id,
                    'sub_head_id' => $sub_head_id,
                    'class_id' => $class_id,
                    'section' => $section,
                    'group_id' => $group_id,
                    'year' => $year,
                    'month' => $fee_month,
                    'fee' => $amount,
                )
            );
        } elseif ($fee_type === 'yearly') {
            $wpdb->insert(
                'ct_student_yearly_fee_summary',
                array(
                    'student_id' => $student_id,
                    'sub_head_id' => $sub_head_id,
                    'class_id' => $class_id,
                    'section' => $section,
                    'group_id' => $group_id,
                    'year' => $year,
                    'fee' => $amount,
                )
            );
        } elseif ($fee_type === 'exam') {
            $wpdb->insert(
                'ct_student_exam_fee_summary',
                array(
                    'student_id' => $student_id,
                    'sub_head_id' => $sub_head_id,
                    'class_id' => $class_id,
                    'section' => $section,
                    'group_id' => $group_id,
                    'year' => $year,
                    'fee' => $amount,
                )
            );
        }
    }
    
    // Create ledger entry (if saveLeadger function exists)
    if (function_exists('saveLeadger')) {
        saveLeadger(
            $collection_info_id,
            $cashSubHeadId,
            $fee_data['total_amount'],
            'Rocket API: ' . substr($payment_id, -10) . '/' . substr($transaction_id, -12),
            $payment_date
        );
    }
    
    $response = array(
        'success' => true,
        'data' => array(
            'payment_id' => $payment_id,
            'transaction_id' => $transaction_id,
            'status' => 'paid',
            'paid_at' => $payment_date,
            'message' => 'Payment confirmed and recorded successfully'
        )
    );
    
    barnomala_rocket_log_api_request('/fee/confirm', $body, $response, 'success');
    $rest_response = rest_ensure_response($response);
    $rest_response->header('Access-Control-Allow-Origin', '*');
    $rest_response->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    $rest_response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
    return $rest_response;
}

/**
 * Check Payment Status Endpoint (Rocket API)
 * Checks status by transaction_id (rocket transaction ID from payment gateway)
 */
function barnomala_rocket_check_payment_status($request) {
    $params = $request->get_query_params();
    $api_key = sanitize_text_field($params['api_key'] ?? '');
    $api_secret = sanitize_text_field($params['api_secret'] ?? '');
    $transaction_id = sanitize_text_field($params['transaction_id'] ?? '');
    
    // Authenticate
    if (!barnomala_rocket_authenticate_api($api_key, $api_secret)) {
        $error_response = array(
            'success' => false,
            'error' => array(
                'code' => 'UNAUTHORIZED',
                'message' => 'Invalid API credentials'
            )
        );
        barnomala_rocket_log_api_request('/fee/status', $params, $error_response, 'error');
        $rest_response = rest_ensure_response($error_response);
        $rest_response->set_status(401);
        $rest_response->header('Access-Control-Allow-Origin', '*');
        return $rest_response;
    }
    
    // Validate transaction_id
    if (empty($transaction_id)) {
        $error_response = array(
            'success' => false,
            'error' => array(
                'code' => 'MISSING_PARAMETER',
                'message' => 'Missing required parameter: transaction_id is required (rocket transaction ID from payment gateway)'
            )
        );
        barnomala_rocket_log_api_request('/fee/status', $params, $error_response, 'error');
        $rest_response = rest_ensure_response($error_response);
        $rest_response->set_status(400);
        $rest_response->header('Access-Control-Allow-Origin', '*');
        return $rest_response;
    }
    
    // Get transaction by transaction_id (rocket transaction ID)
    $transaction = barnomala_rocket_get_transaction_by_transaction_id($transaction_id);
    
    if (!$transaction) {
        $error_response = array(
            'success' => false,
            'error' => array(
                'code' => 'INVALID_TRANSACTION_ID',
                'message' => 'Transaction ID not found (rocket transaction ID not found in system)'
            )
        );
        barnomala_rocket_log_api_request('/fee/status', $params, $error_response, 'error');
        $rest_response = rest_ensure_response($error_response);
        $rest_response->set_status(404);
        $rest_response->header('Access-Control-Allow-Origin', '*');
        return $rest_response;
    }
    
    $response = array(
        'success' => true,
        'data' => array(
            'payment_id' => $transaction->payment_id,
            'transaction_id' => $transaction->transaction_id, // Rocket transaction ID from payment gateway
            'status' => $transaction->status,
            'total_amount' => floatval($transaction->total_amount),
            'created_at' => $transaction->created_at,
            'payment_date' => $transaction->payment_date,
            'expires_at' => $transaction->expires_at,
        )
    );
    
    barnomala_rocket_log_api_request('/fee/status', $params, $response, 'success');
    $rest_response = rest_ensure_response($response);
    $rest_response->header('Access-Control-Allow-Origin', '*');
    $rest_response->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    $rest_response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
    return $rest_response;
}

