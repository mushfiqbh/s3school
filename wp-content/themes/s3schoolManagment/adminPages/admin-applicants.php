<?php
/*
Template Name: Admin Applicants
*/

get_header();

global $wpdb;
$message = null;

// Helper Functions
function sm_clean_int($v) { return isset($v) && $v !== '' ? (int)$v : null; }
function sm_clean_txt($v) { return isset($v) ? sanitize_text_field($v) : ''; }

/*=================
  Action Handlers
=================*/

// 1. Update Approval Status
if (isset($_POST['updateStatus']) && isset($_POST['applicationid'])) {
    $applicationid = (int)$_POST['applicationid'];
    $status = sm_clean_txt($_POST['updateStatus']);
    
    $update = $wpdb->update(
        'ct_online_application',
        array('approve_status' => $status),
        array('applicationid' => $applicationid)
    );
    
    if ($update !== false) {
        $message = array('status' => 'success', 'text' => 'Status updated successfully.');
    } else {
        $message = array('status' => 'error', 'text' => 'Failed to update status.');
    }
}

// 2. Update Payment Status
if (isset($_POST['updatePayment']) && isset($_POST['applicationid'])) {
    $applicationid = (int)$_POST['applicationid'];
    $payment_status = sm_clean_txt($_POST['payment_status']);
    $payment_amount = isset($_POST['payment_amount']) ? floatval($_POST['payment_amount']) : 0;
    
    $update_data = array(
        'payment_status' => $payment_status,
        'payment_amount' => $payment_amount
    );
    
    // If marking as paid, record the date and auto-update to Registered if currently Approved
    if ($payment_status === 'Paid') {
        $update_data['payment_date'] = current_time('mysql');
        
        // Get current approval status
        $current_status = $wpdb->get_var($wpdb->prepare(
            "SELECT approve_status FROM ct_online_application WHERE applicationid = %d",
            $applicationid
        ));
        
        // Auto-update to Registered if Approved
        if ($current_status === 'Approved') {
            $update_data['approve_status'] = 'Registered';
        }
    }
    
    $update = $wpdb->update(
        'ct_online_application',
        $update_data,
        array('applicationid' => $applicationid)
    );
    
    if ($update !== false) {
        $message = array('status' => 'success', 'text' => 'Payment status updated successfully.');
    } else {
        $message = array('status' => 'error', 'text' => 'Failed to update payment status.');
    }
}

// 3. Delete Applicant
if (isset($_POST['deleteApplicant']) && isset($_POST['applicationid'])) {
    $applicationid = (int)$_POST['applicationid'];
    $delete = $wpdb->delete('ct_online_application', array('applicationid' => $applicationid));

    if ($delete) {
        $message = array('status' => 'success', 'text' => 'Applicant deleted successfully.');
    } else {
        $message = array('status' => 'error', 'text' => 'Failed to delete applicant.');
    }
}

/*=================
  Data Fetching
=================*/

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
$query .= " ORDER BY a.stdCreatedAt $sort_order";

// Execute
if (!empty($params)) {
    $query = $wpdb->prepare($query, $params);
}
$apps = $wpdb->get_results($query);

// Stats
$total_apps = $wpdb->get_var("SELECT COUNT(*) FROM ct_online_application");
$pending_apps = $wpdb->get_var("SELECT COUNT(*) FROM ct_online_application WHERE approve_status = 'Submitted'");
$paid_apps = $wpdb->get_var("SELECT COUNT(*) FROM ct_online_application WHERE payment_status = 'Paid'");

// Get all classes for filter
$all_classes = $wpdb->get_results("SELECT classid, className FROM ct_class ORDER BY className");

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
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        border: 1px solid var(--border-color);
    }

    .filter-card {
        background: var(--card-bg);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 25px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
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

    .btn-primary { background: var(--primary-color); color: white; }
    .btn-primary:hover { background: var(--secondary-color); }
    
    .btn-outline { background: transparent; border: 1px solid var(--border-color); color: var(--text-main); }
    .btn-outline:hover { background: #f8fafc; }

    .btn-danger { background: var(--danger-color); color: white; }
    .btn-success { background: var(--success-color); color: white; }

    .data-card {
        background: var(--card-bg);
        border-radius: 10px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
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

    .table tr:last-child td { border-bottom: none; }
    .table tr:hover { background: #f8fafc; }

    .status-badge, .payment-badge {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
        display: inline-block;
        white-space: nowrap;
    }

    .status-submitted { background: #e0f2fe; color: #0369a1; }
    .status-under-review { background: #fef3c7; color: #92400e; }
    .status-approved { background: #dcfce7; color: #15803d; }
    .status-rejected { background: #fee2e2; color: #b91c1c; }
    .status-registered { background: #f3e8ff; color: #7e22ce; }

    .payment-pending { background: #fed7aa; color: #9a3412; }
    .payment-paid { background: #d1fae5; color: #065f46; }
    .payment-partial { background: #dbeafe; color: #1e40af; }

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
    .alert-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .alert-error { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

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

</style>

<div class="admin-container">
    
    <div class="page-header">
        <h1 class="page-title">Applicant Management</h1>
        <div class="stats-badges">
            <div class="stat-badge">Total: <?= $total_apps ?></div>
            <div class="stat-badge" style="color: var(--warning-color)">Pending: <?= $pending_apps ?></div>
            <div class="stat-badge" style="color: var(--success-color)">Paid: <?= $paid_apps ?></div>
        </div>
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
                    <option value="Submitted" <?= (!empty($_GET['filter_status']) && $_GET['filter_status'] == 'Submitted') ? 'selected' : '' ?>>Submitted</option>
                    <option value="Under Review" <?= (!empty($_GET['filter_status']) && $_GET['filter_status'] == 'Under Review') ? 'selected' : '' ?>>Under Review</option>
                    <option value="Approved" <?= (!empty($_GET['filter_status']) && $_GET['filter_status'] == 'Approved') ? 'selected' : '' ?>>Approved</option>
                    <option value="Registered" <?= (!empty($_GET['filter_status']) && $_GET['filter_status'] == 'Registered') ? 'selected' : '' ?>>Registered</option>
                    <option value="Rejected" <?= (!empty($_GET['filter_status']) && $_GET['filter_status'] == 'Rejected') ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Payment Status</label>
                <select name="filter_payment" class="form-control">
                    <option value="">All Payments</option>
                    <option value="Pending" <?= (!empty($_GET['filter_payment']) && $_GET['filter_payment'] == 'Pending') ? 'selected' : '' ?>>Pending</option>
                    <option value="Paid" <?= (!empty($_GET['filter_payment']) && $_GET['filter_payment'] == 'Paid') ? 'selected' : '' ?>>Paid</option>
                    <option value="Partial" <?= (!empty($_GET['filter_payment']) && $_GET['filter_payment'] == 'Partial') ? 'selected' : '' ?>>Partial</option>
                </select>
            </div>

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
        <?php if (empty($apps)): ?>
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
                            <th>ID</th>
                            <th>Applicant</th>
                            <th>Class</th>
                            <th>Group</th>
                            <th>Contact</th>
                            <th>Expected Fee</th>
                            <th>Paid</th>
                            <th>Applied</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($apps as $row): ?>
                            <?php 
                                // Status badge class
                                $statusClass = 'status-submitted';
                                $statusSlug = strtolower(str_replace(' ', '-', $row->approve_status));
                                if ($row->approve_status == 'Under Review') $statusClass = 'status-under-review';
                                if ($row->approve_status == 'Approved') $statusClass = 'status-approved';
                                if ($row->approve_status == 'Rejected') $statusClass = 'status-rejected';
                                if ($row->approve_status == 'Registered') $statusClass = 'status-registered';
                                
                                // Payment badge class
                                $paymentClass = 'payment-pending';
                                if ($row->payment_status == 'Paid') $paymentClass = 'payment-paid';
                                if ($row->payment_status == 'Partial') $paymentClass = 'payment-partial';
                            ?>
                            <tr>
                                <td><strong>#<?= str_pad($row->applicationid, 4, '0', STR_PAD_LEFT) ?></strong></td>
                                <td>
                                    <strong><?= esc_html($row->stdName) ?></strong><br>
                                    <small style="color: var(--text-muted)"><?= esc_html($row->stdFather) ?></small>
                                </td>
                                <td>
                                    <?= esc_html($row->className) ?><br>
                                    <small><?= esc_html($row->stdAdmitYear) ?></small>
                                </td>
                                <td><?= esc_html($row->groupName ?: '-') ?></td>
                                <td><?= esc_html($row->stdPhone) ?></td>
                                <td><strong>৳<?= number_format($row->expected_fee ?: 0, 0) ?></strong></td>
                                <td>
                                    <?php if ($row->payment_amount > 0): ?>
                                        <strong style="color: var(--success-color)">৳<?= number_format($row->payment_amount, 0) ?></strong>
                                    <?php else: ?>
                                        <span style="color: var(--text-muted)">-</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= date('M j, Y', strtotime($row->stdCreatedAt)) ?></td>
                                <td>
                                    <span class="status-badge <?= $statusClass ?>">
                                        <?= esc_html($row->approve_status) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="payment-badge <?= $paymentClass ?>">
                                        <?= esc_html($row->payment_status) ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <div class="action-buttons" style="justify-content: flex-end;">
                                        
                                        <!-- Status Update -->
                                        <form method="post" style="margin:0">
                                            <input type="hidden" name="applicationid" value="<?= $row->applicationid ?>">
                                            <select name="updateStatus" onchange="this.form.submit()" class="payment-select">
                                                <option value="" disabled selected>Status</option>
                                                <option value="Submitted">Submitted</option>
                                                <option value="Under Review">Under Review</option>
                                                <option value="Approved">Approved</option>
                                                <option value="Registered">Registered</option>
                                                <option value="Rejected">Rejected</option>
                                            </select>
                                        </form>

                                        <!-- Payment Update -->
                                        <form method="post" class="payment-form" style="margin:0">
                                            <input type="hidden" name="applicationid" value="<?= $row->applicationid ?>">
                                            <input type="number" name="payment_amount" class="payment-input" 
                                                   placeholder="Amount" value="<?= $row->expected_fee ?>" step="0.01">
                                            <select name="payment_status" class="payment-select">
                                                <option value="Pending">Pending</option>
                                                <option value="Paid">Paid</option>
                                                <option value="Partial">Partial</option>
                                            </select>
                                            <button type="submit" name="updatePayment" class="btn btn-success" 
                                                    style="padding: 4px 8px; font-size: 11px;" title="Update Payment">
                                                💰
                                            </button>
                                        </form>

                                        <!-- Edit -->
                                        <a href="<?= home_url('admin-student'); ?>/?option=add&from_app=<?= $row->applicationid ?>" 
                                           target="_blank" 
                                           class="btn btn-primary" 
                                           style="padding: 4px 8px; font-size: 12px;"
                                           title="Edit & Finalize">
                                            <span class="dashicons dashicons-edit"></span>
                                        </a>

                                        <!-- Delete -->
                                        <form method="post" onsubmit="return confirm('Delete this application?');" style="margin:0">
                                            <input type="hidden" name="applicationid" value="<?= $row->applicationid ?>">
                                            <button type="submit" name="deleteApplicant" class="btn btn-danger" 
                                                    style="padding: 4px 8px; font-size: 12px;" title="Delete">
                                                <span class="dashicons dashicons-trash"></span>
                                            </button>
                                        </form>

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

<?php get_footer(); ?>