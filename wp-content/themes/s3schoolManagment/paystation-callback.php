<?php
/**
 * Template Name: PayStation Callback
 * Handles PayStation payment callback after successful/failed payment
 */

get_header();

// Include PayStation API
require_once(get_template_directory() . '/inc/paystation_api.php');

// Ensure BDT time functions are available
if (!function_exists('get_bdt_time')) {
    function get_bdt_time() {
        $bdt_timezone = new DateTimeZone('Asia/Dhaka');
        $bdt_time = new DateTime('now', $bdt_timezone);
        return $bdt_time->format('Y-m-d H:i:s');
    }
}

global $wpdb;

$payment_status = 'unknown';
$message = '';
$transaction_data = null;
$collection_info_id = null;

// Debug: Log all callback parameters
error_log('=== PayStation Callback Debug ===');
error_log('REQUEST: ' . print_r($_REQUEST, true));
error_log('GET: ' . print_r($_GET, true));
error_log('POST: ' . print_r($_POST, true));

// Get callback parameters from PayStation
// PayStation may send these via GET or POST
$invoice_number = isset($_REQUEST['invoice_number']) ? sanitize_text_field($_REQUEST['invoice_number']) : '';
$status = isset($_REQUEST['status']) ? sanitize_text_field($_REQUEST['status']) : '';
$paystation_txn_id = isset($_REQUEST['transaction_id']) ? sanitize_text_field($_REQUEST['transaction_id']) : '';
$payment_reference = isset($_REQUEST['payment_reference']) ? sanitize_text_field($_REQUEST['payment_reference']) : '';

// Also check for alternate parameter names PayStation might use
if (empty($paystation_txn_id)) {
    $paystation_txn_id = isset($_REQUEST['txn_id']) ? sanitize_text_field($_REQUEST['txn_id']) : '';
}
if (empty($paystation_txn_id)) {
    $paystation_txn_id = isset($_REQUEST['trx_id']) ? sanitize_text_field($_REQUEST['trx_id']) : '';
}
if (empty($paystation_txn_id)) {
    $paystation_txn_id = isset($_REQUEST['ref_id']) ? sanitize_text_field($_REQUEST['ref_id']) : '';
}

error_log('Invoice Number: ' . $invoice_number);
error_log('Status: ' . $status);
error_log('PayStation TXN ID: ' . $paystation_txn_id);

// If invoice_number is provided, find the transaction
if (!empty($invoice_number)) {
    // Debug: Check if table exists and what's in it
    $table_name = $wpdb->prefix . 'paystation_transactions';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'");
    error_log('Table name: ' . $table_name);
    error_log('Table exists check: ' . ($table_exists ? 'YES' : 'NO'));
    
    if ($table_exists) {
        $all_transactions = $wpdb->get_results("SELECT payment_id, invoice_number, status FROM $table_name ORDER BY id DESC LIMIT 10");
        error_log('Recent transactions: ' . print_r($all_transactions, true));
    }
    
    $transaction = paystation_get_transaction_by_invoice($invoice_number);
    error_log('Transaction found by invoice: ' . ($transaction ? 'YES - ID: ' . $transaction->payment_id : 'NO'));
    
    // Fallback: Try to find by payment_id since invoice_number = payment_id in our system
    if (!$transaction) {
        $transaction = paystation_get_transaction($invoice_number);
        error_log('Transaction found by payment_id: ' . ($transaction ? 'YES' : 'NO'));
    }
    
    if ($transaction) {
        $transaction_data = $transaction;
        
        // Use invoice_number as fallback for transaction ID if not provided
        if (empty($paystation_txn_id)) {
            $paystation_txn_id = $invoice_number;
        }
        
        // First check if payment is already processed
        if ($transaction->status === 'paid') {
            $payment_status = 'successful';
            $message = 'Payment already processed successfully.';
        } else {
            // Check payment status from URL parameters and API response
            // PayStation API returns: "Successful", "Failed", "Canceled" (exact spelling)
            $is_canceled = false;
            $is_failed = false;
            $is_successful = false;
            
            // PayStation API status values (case-insensitive matching)
            $canceled_statuses = ['canceled', 'cancelled', 'cancel']; // Support both spellings
            $failed_statuses = ['failed'];
            $successful_statuses = ['successful', 'success']; // API returns "Successful"
            
            // FIRST: Check URL status parameter (most reliable indicator from PayStation redirect)
            if (!empty($status)) {
                $status_lower = strtolower($status);
                if (in_array($status_lower, $failed_statuses)) {
                    $is_failed = true;
                    error_log('PayStation: FAILED status detected from URL parameter: ' . $status);
                } elseif (in_array($status_lower, $canceled_statuses)) {
                    $is_canceled = true;
                    error_log('PayStation: CANCELED status detected from URL parameter: ' . $status);
                } elseif (in_array($status_lower, $successful_statuses)) {
                    $is_successful = true;
                    error_log('PayStation: SUCCESSFUL status detected from URL parameter: ' . $status);
                }
            }
            
            // Check for explicit cancellation parameters in request
            if (!$is_canceled && !$is_failed) {
                $cancel_params = ['cancel', 'cancelled', 'canceled', 'cancel_payment'];
                foreach ($cancel_params as $param) {
                    if (isset($_REQUEST[$param]) && !empty($_REQUEST[$param]) && strtolower($_REQUEST[$param]) !== '0' && strtolower($_REQUEST[$param]) !== 'false') {
                        $is_canceled = true;
                        error_log('PayStation: Explicit cancellation detected from parameter: ' . $param);
                        break;
                    }
                }
            }
            
            // Check message parameter for failure indicators
            $message_param = isset($_REQUEST['message']) ? strtolower($_REQUEST['message']) : '';
            if (!empty($message_param) && !$is_successful && !$is_canceled) {
                $failure_keywords = ['invalid', 'failed', 'error', 'unsuccessful', 'declined', 'rejected'];
                foreach ($failure_keywords as $keyword) {
                    if (strpos($message_param, $keyword) !== false) {
                        $is_failed = true;
                        error_log('PayStation: Failure detected from message parameter: ' . $_REQUEST['message']);
                        break;
                    }
                }
            }
            
            $paystation_data = null;
            
            // Check status from PayStation API (if not already determined from URL)
            // PayStation API returns: "Successful", "Failed", "Canceled"
            if (!$is_successful && !$is_failed && !$is_canceled) {
                $status_check = paystation_check_status($invoice_number);
                
                if (!is_wp_error($status_check) && isset($status_check['data'])) {
                    $paystation_data = $status_check['data'];
                    
                    // Extract transaction ID from PayStation response if available
                    if (isset($paystation_data['transaction_id']) && !empty($paystation_data['transaction_id'])) {
                        $paystation_txn_id = $paystation_data['transaction_id'];
                    } elseif (isset($paystation_data['trx_id']) && !empty($paystation_data['trx_id'])) {
                        $paystation_txn_id = $paystation_data['trx_id'];
                    }
                    
                    // Check PayStation API format: status_code and status fields
                    // API returns status as: "Successful", "Failed", "Canceled"
                    $status_code = isset($paystation_data['status_code']) ? $paystation_data['status_code'] : null;
                    $api_status = isset($paystation_data['status']) ? $paystation_data['status'] : '';
                    $api_status_lower = strtolower($api_status);
                    
                    // Check for Successful (status_code 200 and status "Successful")
                    if (($status_code === '200' || $status_code === 200) && $api_status_lower === 'successful') {
                        $is_successful = true;
                        error_log('PayStation: Successful detected from API response - status_code: ' . $status_code . ', status: ' . $api_status);
                    } elseif (in_array($api_status_lower, $successful_statuses)) {
                        $is_successful = true;
                        error_log('PayStation: Successful detected from API response status: ' . $api_status);
                    }
                    // Check for Failed (status "Failed")
                    elseif ($api_status_lower === 'failed') {
                        $is_failed = true;
                        error_log('PayStation: Failed detected from API response - status_code: ' . $status_code . ', status: ' . $api_status);
                    }
                    // Check for Canceled (status "Canceled")
                    elseif (in_array($api_status_lower, $canceled_statuses)) {
                        $is_canceled = true;
                        error_log('PayStation: Canceled detected from API response status: ' . $api_status);
                    }
                    // Fallback: Check status_code for failure
                    elseif ($status_code && $status_code !== '200' && $status_code !== 200) {
                        $is_failed = true;
                        error_log('PayStation: Failed detected from API response status_code: ' . $status_code);
                    }
                } elseif (is_wp_error($status_check)) {
                    // If status check fails and we have URL parameters indicating failure, trust the URL
                    if ($is_failed) {
                        error_log('PayStation: Status check API failed, but URL indicates failure - treating as failed');
                    } else {
                        error_log('PayStation: Status check API failed - ' . $status_check->get_error_message());
                        // Don't assume success if API check fails - only if we have explicit success indicators
                    }
                }
            }
        
            // Handle payment status based on detection
            // PayStation API returns: "Successful", "Failed", "Canceled"
            if ($is_canceled) {
                // Update transaction status to canceled (matching API spelling)
                if ($transaction->status !== 'cancelled' && $transaction->status !== 'canceled' && $transaction->status !== 'paid') {
                    paystation_update_transaction($transaction->payment_id, array(
                        'status' => 'cancelled', // Store as 'cancelled' in DB for consistency
                        'paystation_response' => array(
                            'status' => 'Canceled', // API returns "Canceled"
                            'message' => 'Payment was canceled by user',
                            'canceled_at' => get_bdt_time()
                        )
                    ));
                }
                
                $payment_status = 'canceled';
                $message = 'Payment was canceled. No charges were made to your account.';
            } elseif ($is_failed) {
                // Update transaction status to failed
                if ($transaction->status !== 'failed' && $transaction->status !== 'paid') {
                    $failure_message = isset($_REQUEST['message']) ? $_REQUEST['message'] : (isset($paystation_data['message']) ? $paystation_data['message'] : 'Payment failed');
                    paystation_update_transaction($transaction->payment_id, array(
                        'status' => 'failed',
                        'paystation_response' => array(
                            'status' => 'Failed', // API returns "Failed"
                            'message' => $failure_message,
                            'failed_at' => get_bdt_time()
                        )
                    ));
                }
                
                $payment_status = 'failed';
                $failure_message = isset($_REQUEST['message']) ? urldecode($_REQUEST['message']) : (isset($paystation_data['message']) ? $paystation_data['message'] : 'Payment was not successful. Please try again.');
                $message = $failure_message;
            } elseif ($is_successful) {
                // Confirm payment only if explicitly successful
                if ($transaction->status !== 'paid') {
                    // Confirm payment with PayStation transaction ID
                    $confirm_result = paystation_confirm_payment(
                        $transaction->payment_id,
                        $invoice_number,
                        array(
                            'payment_date' => get_bdt_time(),
                            'paystation_txn_id' => $paystation_txn_id
                        )
                    );
                    
                    
                    if (!is_wp_error($confirm_result)) {
                        $payment_status = 'successful';
                        $collection_info_id = $confirm_result['collection_info_id'];
                        $message = 'Payment completed successfully! Your fee payment has been recorded.';
                        error_log('PayStation: Payment confirmed successfully');
                    } else {
                        $payment_status = 'error';
                        $message = 'Payment received but there was an error processing it. Please contact support. Error: ' . $confirm_result->get_error_message();
                        error_log('PayStation: Payment confirmation failed: ' . $confirm_result->get_error_message());
                    }
                } else {
                    $payment_status = 'successful';
                    $message = 'Payment already processed successfully.';
                }
            } else {
                // Unknown status - default to failed for safety
                $payment_status = 'failed';
                $message = 'Payment status could not be determined. Please contact support with your invoice number: ' . esc_html($invoice_number);
                error_log('PayStation: Unknown payment status for invoice: ' . $invoice_number . ', URL status: ' . ($status ?? 'N/A'));
            }
        }
    } else {
        $payment_status = 'error';
        
        // More helpful error message with debug info
        $debug_info = '';
        if ($table_exists) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            $debug_info = " (Table has $count records)";
        } else {
            $debug_info = " (Transaction table not found)";
        }
        
        $message = 'Transaction not found. Please contact support with your invoice number: ' . esc_html($invoice_number) . $debug_info;
        
        // If payment was successful on PayStation side, we should still record it
        if (in_array(strtolower($status), ['success', 'successful', 'completed', 'paid'])) {
            $message .= ' Note: PayStation reported payment as successful. Please contact support immediately.';
        }
    }
} else {
    $payment_status = 'error';
    $message = 'Invalid callback. No invoice number provided.';
}
?>

<style>
.callback-page-wrapper {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding: 60px 0;
}

.callback-container {
    max-width: 700px;
    margin: 0 auto;
    padding: 0 15px;
}

.callback-card {
    background: #fff;
    border-radius: 20px;
    box-shadow: 0 25px 80px rgba(0,0,0,0.3);
    overflow: hidden;
    text-align: center;
}

.callback-icon {
    padding: 50px 30px 30px;
}

.callback-icon i {
    font-size: 80px;
}

.callback-icon.success i {
    color: #28a745;
}

.callback-icon.failed i {
    color: #dc3545;
}

.callback-icon.error i {
    color: #ffc107;
}

.callback-body {
    padding: 0 40px 40px;
}

.callback-title {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 15px;
}

.callback-title.success {
    color: #28a745;
}

.callback-title.failed {
    color: #dc3545;
}

.callback-title.error {
    color: #ffc107;
}

.callback-message {
    font-size: 16px;
    color: #666;
    margin-bottom: 30px;
    line-height: 1.6;
}

.transaction-details {
    background: #f8f9fa;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 30px;
    text-align: left;
}

.transaction-details h4 {
    margin: 0 0 20px;
    font-size: 18px;
    color: #333;
    text-align: center;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #e9ecef;
}

.detail-row:last-child {
    border-bottom: none;
}

.detail-label {
    color: #666;
    font-size: 14px;
}

.detail-value {
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.detail-value.amount {
    color: #28a745;
    font-size: 18px;
}

.callback-actions {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    justify-content: center;
}

.btn-action {
    padding: 15px 30px;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.btn-primary-custom {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
}

.btn-primary-custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
    color: #fff;
    text-decoration: none;
}

.btn-secondary-custom {
    background: #6c757d;
    color: #fff;
}

.btn-secondary-custom:hover {
    background: #5a6268;
    color: #fff;
    text-decoration: none;
}

.btn-success-custom {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: #fff;
}

.btn-success-custom:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 30px rgba(40, 167, 69, 0.4);
    color: #fff;
    text-decoration: none;
}

/* Print header - hidden on screen */
.print-header {
    display: none;
}

@media print {
    /* Page settings */
    @page {
        margin: 0.5cm;
        size: A4;
    }
    
    /* Hide everything by default */
    * {
        visibility: hidden;
    }
    
    /* Show only receipt content */
    .callback-page-wrapper,
    .callback-page-wrapper * {
        visibility: visible;
    }
    
    /* Hide all site elements */
    body * {
        visibility: hidden;
    }
    
    /* Show only receipt wrapper and its children */
    .callback-page-wrapper,
    .callback-page-wrapper * {
        visibility: visible;
    }
    
    /* Hide WordPress header, footer, navigation, admin bar */
    header, footer, nav, aside,
    .header, .footer, .navigation, .sidebar,
    .site-header, .site-footer, .main-navigation,
    #masthead, #colophon, #secondary, #site-navigation,
    .menu, .nav-menu, .top-bar, .bottom-bar,
    .navbar, .topbar, #wpadminbar,
    .site-header, .site-footer, .site-main,
    .entry-header, .entry-footer, .comments-area,
    .widget-area, .sidebar, .widget {
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
        overflow: hidden !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    /* Reset body and html */
    html, body {
        background: #fff !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        height: auto !important;
        font-size: 12pt !important;
    }
    
    /* Show print header */
    .print-header {
        display: block !important;
        visibility: visible !important;
        text-align: center;
        padding: 20px 0 15px 0;
        border-bottom: 2px solid #000;
        margin-bottom: 20px;
        page-break-inside: avoid;
    }
    
    .print-header h2 {
        margin: 0 0 8px 0;
        font-size: 22px;
        font-weight: bold;
        color: #000 !important;
    }
    
    .print-header p {
        margin: 5px 0;
        color: #000 !important;
        font-size: 14px;
    }
    
    .print-header .print-date {
        font-size: 12px;
        margin-top: 8px;
        color: #333 !important;
    }
    
    /* Receipt wrapper */
    .callback-page-wrapper {
        background: #fff !important;
        padding: 0 !important;
        margin: 0 !important;
        min-height: auto !important;
        position: relative !important;
        width: 100% !important;
    }
    
    .callback-container {
        max-width: 100% !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    
    .callback-card {
        box-shadow: none !important;
        border: none !important;
        border-radius: 0 !important;
        background: #fff !important;
    }
    
    /* Hide icon for cleaner print */
    .callback-icon {
        display: none !important;
        visibility: hidden !important;
    }
    
    .callback-body {
        padding: 0 !important;
    }
    
    .callback-title {
        font-size: 20px !important;
        font-weight: bold !important;
        color: #000 !important;
        margin-bottom: 15px !important;
        text-align: center !important;
    }
    
    .callback-message {
        font-size: 13px !important;
        color: #000 !important;
        margin-bottom: 20px !important;
        text-align: center !important;
    }
    
    .transaction-details {
        background: #fff !important;
        border: 2px solid #000 !important;
        padding: 20px !important;
        margin: 20px 0 !important;
        page-break-inside: avoid;
    }
    
    .transaction-details h4 {
        font-size: 18px !important;
        font-weight: bold !important;
        color: #000 !important;
        margin: 0 0 15px 0 !important;
        text-align: center !important;
        border-bottom: 1px solid #000 !important;
        padding-bottom: 10px !important;
    }
    
    .detail-row {
        border-bottom: 1px solid #ddd !important;
        padding: 10px 0 !important;
        display: flex !important;
        justify-content: space-between !important;
    }
    
    .detail-row:last-child {
        border-bottom: 2px solid #000 !important;
        font-weight: bold !important;
        margin-top: 10px !important;
        padding-top: 15px !important;
    }
    
    .detail-label {
        color: #000 !important;
        font-size: 13px !important;
        font-weight: normal !important;
    }
    
    .detail-value {
        color: #000 !important;
        font-size: 13px !important;
        font-weight: bold !important;
        text-align: right !important;
    }
    
    .detail-value.amount {
        font-size: 16px !important;
        font-weight: bold !important;
        color: #000 !important;
    }
    
    /* Hide buttons and actions */
    .callback-actions {
        display: none !important;
        visibility: hidden !important;
    }
    
    /* Hide PayStation footer text and any other elements */
    .callback-page-wrapper > .callback-container ~ div,
    .callback-page-wrapper > div:not(.callback-container) {
        display: none !important;
        visibility: hidden !important;
    }
    
    /* Remove all link URLs and decorations */
    a, a:visited, a:hover, a:active {
        color: #000 !important;
        text-decoration: none !important;
    }
    
    a[href]:after {
        content: "" !important;
    }
    
    /* Hide any script tags or other elements */
    script, style, noscript, iframe, embed, object {
        display: none !important;
        visibility: hidden !important;
    }
    
    /* Ensure no page breaks inside important sections */
    .transaction-details,
    .print-header {
        page-break-inside: avoid;
        page-break-after: auto;
    }
    
    /* Print only on first page */
    @page :first {
        margin-top: 0.5cm;
    }
}

@media (max-width: 576px) {
    .callback-body {
        padding: 0 20px 30px;
    }
    
    .callback-title {
        font-size: 22px;
    }
    
    .callback-icon i {
        font-size: 60px;
    }
    
    .btn-action {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="callback-page-wrapper">
    <div class="callback-container">
        <div class="callback-card">
            
            <!-- Print-only header -->
            <div class="print-header">
                <h2><?php echo get_bloginfo('name'); ?></h2>
                <p>Fee Payment Receipt</p>
                <p class="print-date">Date: <?php echo date('d M Y, h:i A'); ?></p>
            </div>
            
            <div class="callback-icon <?php echo $payment_status === 'successful' ? 'success' : ($payment_status === 'canceled' ? 'failed' : $payment_status); ?>">
                <?php if ($payment_status === 'successful'): ?>
                    <i class="fa fa-check-circle"></i>
                <?php elseif ($payment_status === 'failed'): ?>
                    <i class="fa fa-times-circle"></i>
                <?php elseif ($payment_status === 'canceled'): ?>
                    <i class="fa fa-ban"></i>
                <?php else: ?>
                    <i class="fa fa-exclamation-triangle"></i>
                <?php endif; ?>
</div>

            <div class="callback-body">
                <h2 class="callback-title <?php echo $payment_status === 'successful' ? 'success' : ($payment_status === 'canceled' ? 'failed' : $payment_status); ?>">
                                        <?php if ($payment_status === 'successful'): ?>
                        Payment Status: Successful
                                        <?php elseif ($payment_status === 'failed'): ?>
                        Payment Status: Failed
                                        <?php elseif ($payment_status === 'canceled'): ?>
                        Payment Status: Canceled
                                        <?php else: ?>
                        Payment Status: Error
                                        <?php endif; ?>
                </h2>
                
                <p class="callback-message"><?php echo esc_html($message); ?></p>
                
                <?php if ($payment_status === 'successful' && $transaction_data): ?>
                    <div class="transaction-details">
                        <h4><i class="fa fa-receipt"></i> Payment Receipt</h4>
                        
                        <div class="detail-row">
                            <span class="detail-label">Transaction ID</span>
                            <span class="detail-value"><?php echo esc_html($paystation_txn_id); ?></span>
                                </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Invoice Number</span>
                            <span class="detail-value"><?php echo esc_html($transaction_data->invoice_number); ?></span>
                                        </div>
                                        
                                                    <?php if ($transaction_data->student_data): ?>
                            <div class="detail-row">
                                <span class="detail-label">Student Name</span>
                                <span class="detail-value"><?php echo esc_html($transaction_data->student_data['student_name']); ?></span>
                                        </div>
                                        
                            <?php if (!empty($transaction_data->student_data['stdUniqueID'])): ?>
                            <div class="detail-row">
                                <span class="detail-label">Student ID</span>
                                <span class="detail-value"><?php echo esc_html($transaction_data->student_data['stdUniqueID']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        
                            <div class="detail-row">
                                <span class="detail-label">Roll Number</span>
                                <span class="detail-value"><?php echo esc_html($transaction_data->student_data['student_roll']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                    
                        <div class="detail-row">
                            <span class="detail-label">Payment Method</span>
                            <span class="detail-value">Online</span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="detail-label">Payment Date</span>
                            <span class="detail-value"><?php echo date('d M Y, h:i A'); ?></span>
                                    </div>
                                    
                        <div class="detail-row">
                            <span class="detail-label">Amount Paid</span>
                            <span class="detail-value amount"><?php echo number_format($transaction_data->total_amount, 2); ?> BDT</span>
                                </div>
                    </div>
                <?php elseif ($payment_status === 'failed' && $transaction_data): ?>
                    <div class="transaction-details">
                        <h4><i class="fa fa-info-circle"></i> Transaction Details</h4>
                        
                        <div class="detail-row">
                            <span class="detail-label">Invoice Number</span>
                            <span class="detail-value"><?php echo esc_html($transaction_data->invoice_number); ?></span>
                            </div>
                            
                        <div class="detail-row">
                            <span class="detail-label">Amount</span>
                            <span class="detail-value"><?php echo number_format($transaction_data->total_amount, 2); ?> BDT</span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="callback-actions">
                    <?php if ($payment_status === 'successful'): ?>
                        <a href="javascript:window.print()" class="btn-action btn-success-custom">
                            <i class="fa fa-print"></i> Print Receipt
                        </a>
                    <?php endif; ?>
                    
                    <a href="<?php echo esc_url(home_url('/student-fee')); ?>" class="btn-action btn-primary-custom">
                        <i class="fa fa-credit-card"></i> Make Another Payment
                    </a>
                    
                    <a href="<?php echo esc_url(home_url()); ?>" class="btn-action btn-secondary-custom">
                        <i class="fa fa-home"></i> Go to Home
                    </a>
                </div>
            </div>
        </div>
        
        <!-- <div style="text-align: center; margin-top: 20px; color: rgba(255,255,255,0.7); font-size: 14px;">
            <i class="fa fa-shield"></i> Secure Payment powered by PayStation
        </div> -->
    </div>
</div>

<?php get_footer(); ?>
