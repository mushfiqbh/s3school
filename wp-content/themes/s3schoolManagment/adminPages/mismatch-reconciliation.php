<?php
/**
 * Template Name: Mismatch Reconciliation & Claim Management
 *
 * Tab-based admin page with:
 * - Overview: system-wide mismatch stats
 * - Student Wise: mismatches grouped by student
 * - Class Wise: mismatch stats by class
 * - Claims: claim management with approve/reject
 */

global $wpdb;
$current_user_id = get_current_user_id();

// Determine active tab
$active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'overview';
$valid_tabs = array('overview', 'students', 'classes', 'claims');
if (!in_array($active_tab, $valid_tabs)) $active_tab = 'overview';

// Table names
$mismatch_table = 'ct_payment_mismatch';
$claim_table    = 'ct_mismatch_claim';
$transfer_table = 'ct_payment_transfer';
$ps_table       = $wpdb->prefix . 'paystation_transactions';

// Check tables exist
$mm_exists  = $wpdb->get_var("SHOW TABLES LIKE '$mismatch_table'");
$cl_exists  = $wpdb->get_var("SHOW TABLES LIKE '$claim_table'");
$ps_exists  = $wpdb->get_var("SHOW TABLES LIKE '$ps_table'");

$pending_claims = 0;
if ($cl_exists) {
    $pending_claims = intval($wpdb->get_var("SELECT COUNT(*) FROM $claim_table WHERE status='PENDING'"));
}

// Handle claim actions (simple POST actions)
$action_message = '';
$action_error   = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['claim_action'])) {
    $action = sanitize_key($_POST['claim_action']);

    // Approve / Reject existing claims
    if (($action === 'approve' || $action === 'reject') && isset($_POST['claim_id'])) {
        $claim_id = intval($_POST['claim_id']);
        $notes    = isset($_POST['review_notes']) ? sanitize_textarea_field($_POST['review_notes']) : '';

        if ($cl_exists && $mm_exists) {
            $new_status = ($action === 'approve') ? 'APPROVED' : 'REJECTED';

            $updated = $wpdb->update(
                $claim_table,
                array(
                    'status'       => $new_status,
                    'reviewed_by'  => $current_user_id,
                    'reviewed_at'  => current_time('mysql'),
                    'review_notes' => $notes,
                ),
                array('claim_id' => $claim_id, 'status' => 'PENDING')
            );

            if ($updated !== false && $updated > 0) {
                $action_message = "Claim #{$claim_id} has been " . strtolower($new_status) . ".";

                // If approved, update the related mismatch
                if ($action === 'approve') {
                    $claim = $wpdb->get_row($wpdb->prepare(
                        "SELECT mismatch_id FROM $claim_table WHERE claim_id = %d", $claim_id
                    ));
                    if ($claim) {
                        $wpdb->update(
                            $mismatch_table,
                            array('status' => 'CLAIMED', 'resolved_at' => current_time('mysql')),
                            array('mismatch_id' => $claim->mismatch_id, 'status' => 'PENDING')
                        );
                    }
                }
            } else {
                $action_error = "Claim #{$claim_id} could not be updated. It may have already been processed.";
            }
        }
    }

    // Submit a new claim linking a mismatch to a different student
    if ($action === 'submit_claim' && isset($_POST['mismatch_id'], $_POST['claim_student_id'])) {
        $mismatch_id      = intval($_POST['mismatch_id']);
        $claim_student_id = intval($_POST['claim_student_id']);
        $claim_reason     = isset($_POST['claim_reason']) ? sanitize_textarea_field($_POST['claim_reason']) : '';

        if ($cl_exists && $mm_exists && $mismatch_id > 0 && $claim_student_id > 0) {
            // Check if claim already exists for this mismatch
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT claim_id FROM $claim_table WHERE mismatch_id = %d AND status = 'PENDING' LIMIT 1",
                $mismatch_id
            ));
            if ($existing) {
                $action_error = "A pending claim already exists for this mismatch (Claim #{$existing}).";
            } else {
                $inserted = $wpdb->insert($claim_table, array(
                    'mismatch_id'      => $mismatch_id,
                    'claim_student_id' => $claim_student_id,
                    'claim_reason'     => $claim_reason,
                    'status'           => 'PENDING',
                    'submitted_at'     => current_time('mysql'),
                ));
                if ($inserted !== false) {
                    $action_message = "Claim submitted successfully. Claim #{$wpdb->insert_id} is pending review.";
                } else {
                    $action_error = 'Failed to submit claim: ' . $wpdb->last_error;
                }
            }
        } else {
            $action_error = 'Invalid claim submission data.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Reconciliation</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --font: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            --bg: #f0f2f5;
            --surface: #ffffff;
            --surface-hover: #f8f9fa;
            --border: #e2e5ea;
            --text: #1a1d23;
            --text-secondary: #6b7280;
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #eef2ff;
            --success: #10b981;
            --success-bg: #ecfdf5;
            --warning: #f59e0b;
            --warning-bg: #fffbeb;
            --danger: #ef4444;
            --danger-bg: #fef2f2;
            --info: #3b82f6;
            --info-bg: #eff6ff;
            --purple: #8b5cf6;
            --purple-bg: #f5f3ff;
            --radius: 12px;
            --radius-sm: 8px;
            --shadow: 0 1px 3px rgba(0,0,0,0.06), 0 1px 2px rgba(0,0,0,0.04);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.07), 0 2px 4px -1px rgba(0,0,0,0.04);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.08), 0 4px 6px -2px rgba(0,0,0,0.04);
            --transition: 0.2s ease;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: var(--font);
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            padding: 24px;
            min-height: 100vh;
        }

        /* Page Header */
        .page-header {
            position: relative;
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #a855f7 100%);
            border-radius: var(--radius);
            padding: 28px 32px;
            margin-bottom: 28px;
            overflow: hidden;
            isolation: isolate;
        }
        .page-header::before {
            content: '';
            position: absolute;
            top: -50%; right: -20%;
            width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(255,255,255,0.12) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }
        .page-header::after {
            content: '';
            position: absolute;
            bottom: -40%; left: 10%;
            width: 300px; height: 300px;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
            z-index: 0;
        }
        .page-header h1 {
            color: #fff;
            font-size: 26px;
            font-weight: 700;
            position: relative;
            z-index: 1;
            letter-spacing: -0.3px;
        }
        .page-header p {
            color: rgba(255,255,255,0.85);
            font-size: 14px;
            margin-top: 4px;
            position: relative;
            z-index: 1;
        }

        /* Tabs */
        .nav-tabs {
            display: flex;
            gap: 4px;
            background: var(--surface);
            border-radius: var(--radius);
            padding: 6px;
            margin-bottom: 24px;
            box-shadow: var(--shadow);
            flex-wrap: wrap;
        }
        .tab-link {
            padding: 10px 20px;
            border-radius: var(--radius-sm);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            color: var(--text-secondary);
            transition: all var(--transition);
            white-space: nowrap;
        }
        .tab-link:hover {
            background: var(--surface-hover);
            color: var(--text);
        }
        .tab-link.active {
            background: var(--primary);
            color: #fff;
            box-shadow: 0 2px 8px rgba(99,102,241,0.3);
        }

        /* Cards */
        .card {
            background: var(--surface);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .card-header {
            padding: 18px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }
        .card-header h2 {
            font-size: 17px;
            font-weight: 600;
            color: var(--text);
        }
        .card-body {
            padding: 20px 24px;
        }
        .card-body:only-child { border: none; }

        /* Stat Cards */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 20px;
        }
        .stat-card {
            border-radius: var(--radius);
            padding: 20px;
            color: #fff;
            position: relative;
            overflow: hidden;
        }
        .stat-card .stat-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.85;
            font-weight: 500;
        }
        .stat-card .stat-value {
            font-size: 28px;
            font-weight: 700;
            margin-top: 6px;
            letter-spacing: -0.5px;
        }
        .stat-card .stat-sub {
            font-size: 12px;
            opacity: 0.75;
            margin-top: 2px;
        }
        .stat-card::after {
            content: '';
            position: absolute;
            top: -30%; right: -15%;
            width: 120px; height: 120px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            pointer-events: none;
        }
        .stat-bg-1 { background: linear-gradient(135deg, #6366f1, #8b5cf6); }
        .stat-bg-2 { background: linear-gradient(135deg, #10b981, #059669); }
        .stat-bg-3 { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .stat-bg-4 { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .stat-bg-5 { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .stat-bg-6 { background: linear-gradient(135deg, #8b5cf6, #7c3aed); }
        .stat-bg-7 { background: linear-gradient(135deg, #ec4899, #db2777); }
        .stat-bg-8 { background: linear-gradient(135deg, #14b8a6, #0d9488); }

        /* Tables */
        .table-wrap {
            overflow-x: auto;
            overflow-y: auto;
            max-height: 520px;
            border-radius: var(--radius-sm);
        }
        .table-wrap::-webkit-scrollbar {
            width: 6px; height: 6px;
        }
        .table-wrap::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        .table-wrap::-webkit-scrollbar-thumb {
            background: #c1c7cd;
            border-radius: 3px;
        }
        .table-wrap::-webkit-scrollbar-thumb:hover {
            background: #a0a7ae;
        }
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 13px;
        }
        table thead {
            position: sticky;
            top: 0;
            z-index: 2;
        }
        table th {
            background: #f8fafc;
            color: var(--text-secondary);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
            padding: 12px 14px;
            text-align: left;
            border-bottom: 2px solid var(--border);
            white-space: nowrap;
        }
        table td {
            padding: 10px 14px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }
        table tbody tr:hover {
            background: var(--surface-hover);
        }
        table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Badges */
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .badge-danger { background: var(--danger-bg); color: var(--danger); }
        .badge-warning { background: var(--warning-bg); color: var(--warning); }
        .badge-success { background: var(--success-bg); color: var(--success); }
        .badge-info { background: var(--info-bg); color: var(--info); }
        .badge-purple { background: var(--purple-bg); color: var(--purple); }
        .badge-gray {
            background: #f3f4f6;
            color: #6b7280;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-weight: 500;
            font-family: var(--font);
            border: none;
            cursor: pointer;
            transition: all var(--transition);
            text-decoration: none;
        }
        .btn-primary {
            background: var(--primary);
            color: #fff;
        }
        .btn-primary:hover {
            background: var(--primary-dark);
            box-shadow: 0 2px 8px rgba(99,102,241,0.3);
        }
        .btn-success {
            background: var(--success);
            color: #fff;
        }
        .btn-success:hover {
            background: #059669;
        }
        .btn-danger {
            background: var(--danger);
            color: #fff;
        }
        .btn-danger:hover {
            background: #dc2626;
        }
        .btn-outline {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border);
        }
        .btn-outline:hover {
            background: var(--surface-hover);
            border-color: var(--primary);
            color: var(--primary);
        }
        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        /* Filters */
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: end;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .filter-group label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .filter-group select,
        .filter-group input {
            padding: 8px 12px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 13px;
            font-family: var(--font);
            background: var(--surface);
            color: var(--text);
            min-width: 140px;
        }
        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
        }

        /* Alert / Message */
        .alert {
            padding: 14px 18px;
            border-radius: var(--radius-sm);
            font-size: 13px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .alert-success {
            background: var(--success-bg);
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .alert-error {
            background: var(--danger-bg);
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .alert-info {
            background: var(--info-bg);
            color: #1e40af;
            border: 1px solid #93c5fd;
        }

        /* Modal overlay */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        .modal-overlay.active {
            display: flex;
        }
        .modal-box {
            background: var(--surface);
            border-radius: var(--radius);
            max-width: 480px;
            width: 90%;
            padding: 28px;
            box-shadow: var(--shadow-lg);
        }
        .modal-box h3 {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .modal-box textarea {
            width: 100%;
            min-height: 100px;
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-family: var(--font);
            font-size: 13px;
            margin: 12px 0;
            resize: vertical;
        }
        .modal-box .btn-group {
            display: flex;
            gap: 10px;
            justify-content: end;
            margin-top: 16px;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 48px 20px;
            color: var(--text-secondary);
        }
        .empty-state .empty-icon {
            font-size: 48px;
            margin-bottom: 12px;
            opacity: 0.3;
        }
        .empty-state h3 {
            font-size: 18px;
            color: var(--text);
            margin-bottom: 6px;
        }
        .empty-state p {
            font-size: 14px;
        }

        /* Inline action buttons for claims */
        .action-group {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
        }
        .action-group form {
            display: inline;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body { padding: 12px; }
            .page-header { padding: 20px; }
            .page-header h1 { font-size: 20px; }
            .stat-grid { grid-template-columns: repeat(2, 1fr); }
            .nav-tabs { gap: 2px; padding: 4px; }
            .tab-link { padding: 8px 12px; font-size: 12px; }
            .card-body { padding: 16px; }
        }
    </style>
</head>
<body>

<div class="page-header">
    <h1>🔍 Payment Reconciliation</h1>
    <p>Detect, review, and resolve payment mismatches across all collections &amp; PayStation transactions</p>
</div>

<?php if ($action_message): ?>
<div class="alert alert-success"><?php echo esc_html($action_message); ?></div>
<?php elseif ($action_error): ?>
<div class="alert alert-error"><?php echo esc_html($action_error); ?></div>
<?php endif; ?>

<?php if (!$mm_exists): ?>
<div class="alert alert-info">
    ⚠️ The mismatch detection tables have not been created yet.
    <a href="mismatch-reconciliation?tab=<?php echo esc_attr($active_tab); ?>&run_migration=1" class="btn btn-primary btn-sm" style="margin-left:auto;">Run Migration</a>
</div>
<?php endif; ?>

<!-- Run Migration -->
<?php
if (isset($_GET['run_migration']) && $_GET['run_migration'] == 1) {
    $migration_file = __DIR__ . '/../migrations/reconcilation_tables.sql';
    if (file_exists($migration_file)) {
        $sql = file_get_contents($migration_file);
        if (!empty($sql)) {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $queries = explode(';', $sql);
            $migrate_errors = 0;
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query) && stripos($query, 'CREATE TABLE') !== false) {
                    $result = $wpdb->query($query);
                    if ($result === false) {
                        $migrate_errors++;
                    }
                }
            }
            if ($migrate_errors === 0) {
                echo '<div class="alert alert-success">✅ Migration completed successfully. All tables created.</div>';
                // Refresh
                $mm_exists = $wpdb->get_var("SHOW TABLES LIKE '$mismatch_table'");
                $cl_exists = $wpdb->get_var("SHOW TABLES LIKE '$claim_table'");
            } else {
                echo '<div class="alert alert-error">⚠️ Some migration queries failed. Check your database.</div>';
            }
        }
    } else {
        echo '<div class="alert alert-error">Migration file not found at: migrations/reconcilation_tables.sql</div>';
    }
}
?>

<!-- Tabs -->
<div class="nav-tabs">
    <a href="mismatch-reconciliation?tab=overview" class="tab-link <?php echo $active_tab === 'overview' ? 'active' : ''; ?>">📊 Overview</a>
    <a href="mismatch-reconciliation?tab=students" class="tab-link <?php echo $active_tab === 'students' ? 'active' : ''; ?>">👤 Student Wise</a>
    <a href="mismatch-reconciliation?tab=classes" class="tab-link <?php echo $active_tab === 'classes' ? 'active' : ''; ?>">📚 Class Wise</a>
    <a href="mismatch-reconciliation?tab=claims" class="tab-link <?php echo $active_tab === 'claims' ? 'active' : ''; ?>">📋 Claims</a>
</div>

<?php if ($active_tab === 'overview'): ?>
    <!-- ===================== OVERVIEW TAB ===================== -->
    <div class="card">
        <div class="card-header">
            <h2>📊 System-wide Mismatch Overview</h2>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <a href="mismatch-reconciliation?tab=overview&refresh=1" class="btn btn-outline btn-sm">🔄 Refresh</a>
            </div>
        </div>
        <div class="card-body">
            <?php
            // Fetch overview stats from the mismatch table
            $total_mismatches = 0;
            $mismatches_by_type = array();
            $mismatches_by_status = array();
            $total_mismatch_amount = 0;
            $total_claims = 0;
            $affected_students = 0;

            if ($mm_exists) {
                $total_mismatches = intval($wpdb->get_var("SELECT COUNT(*) FROM $mismatch_table"));
                $total_mismatch_amount = floatval($wpdb->get_var("SELECT COALESCE(SUM(amount),0) FROM $mismatch_table WHERE status NOT IN ('CLOSED','REJECTED')"));

                $by_type = $wpdb->get_results("SELECT mismatch_type, COUNT(*) AS cnt, COALESCE(SUM(amount),0) AS amt FROM $mismatch_table GROUP BY mismatch_type ORDER BY cnt DESC");
                foreach ($by_type as $row) {
                    $mismatches_by_type[$row->mismatch_type] = array('count' => intval($row->cnt), 'amount' => floatval($row->amt));
                }

                $by_status = $wpdb->get_results("SELECT status, COUNT(*) AS cnt FROM $mismatch_table GROUP BY status ORDER BY cnt DESC");
                foreach ($by_status as $row) {
                    $mismatches_by_status[$row->status] = intval($row->cnt);
                }

                $affected_students = intval($wpdb->get_var("SELECT COUNT(DISTINCT student_id) FROM $mismatch_table"));
            }

            if ($cl_exists) {
                $total_claims = intval($wpdb->get_var("SELECT COUNT(*) FROM $claim_table"));
            }

            // PayStation totals
            $ps_total_txns = 0;
            $ps_paid_txns = 0;
            if ($ps_exists) {
                $ps_total_txns = intval($wpdb->get_var("SELECT COUNT(*) FROM $ps_table"));
                $ps_paid_txns = intval($wpdb->get_var("SELECT COUNT(*) FROM $ps_table WHERE status='paid'"));
            }
            ?>

            <div class="stat-grid">
                <div class="stat-card stat-bg-1">
                    <div class="stat-label">Total Mismatches</div>
                    <div class="stat-value"><?php echo intval($total_mismatches); ?></div>
                    <div class="stat-sub">Across all students</div>
                </div>
                <div class="stat-card stat-bg-2">
                    <div class="stat-label">Amount at Risk</div>
                    <div class="stat-value">৳<?php echo number_format($total_mismatch_amount, 0); ?></div>
                    <div class="stat-sub">Unresolved mismatches</div>
                </div>
                <div class="stat-card stat-bg-5">
                    <div class="stat-label">Affected Students</div>
                    <div class="stat-value"><?php echo intval($affected_students); ?></div>
                    <div class="stat-sub">With at least 1 mismatch</div>
                </div>
                <div class="stat-card stat-bg-6">
                    <div class="stat-label">Total Claims</div>
                    <div class="stat-value"><?php echo intval($total_claims); ?></div>
                    <div class="stat-sub"><?php echo intval($pending_claims); ?> pending review</div>
                </div>
                <div class="stat-card stat-bg-7">
                    <div class="stat-label">PayStation Transactions</div>
                    <div class="stat-value"><?php echo intval($ps_total_txns); ?></div>
                    <div class="stat-sub"><?php echo intval($ps_paid_txns); ?> paid</div>
                </div>
                <div class="stat-card stat-bg-8">
                    <div class="stat-label">Mismatch Types</div>
                    <div class="stat-value"><?php echo count($mismatches_by_type); ?></div>
                    <div class="stat-sub">Unique categories detected</div>
                </div>
            </div>

            <!-- Mismatches by Type -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
                <div>
                    <h3 style="font-size:15px;font-weight:600;margin-bottom:12px;">By Type</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Count</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mismatches_by_type)): ?>
                            <tr><td colspan="3" style="text-align:center;color:var(--text-secondary);">No mismatches recorded yet.</td></tr>
                            <?php else: ?>
                            <?php foreach ($mismatches_by_type as $type => $data): ?>
                            <tr>
                                <td><span class="badge badge-<?php echo $type === 'DUPLICATE_PAYMENT' ? 'danger' : ($type === 'OVERPAYMENT' ? 'warning' : 'info'); ?>"><?php echo esc_html(str_replace('_', ' ', $type)); ?></span></td>
                                <td><strong><?php echo intval($data['count']); ?></strong></td>
                                <td>৳<?php echo number_format($data['amount'], 0); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div>
                    <h3 style="font-size:15px;font-weight:600;margin-bottom:12px;">By Status</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Count</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($mismatches_by_status)): ?>
                            <tr><td colspan="2" style="text-align:center;color:var(--text-secondary);">No data yet.</td></tr>
                            <?php else: ?>
                            <?php foreach ($mismatches_by_status as $status => $cnt): ?>
                            <tr>
                                <td>
                                    <span class="badge badge-<?php
                                        echo $status === 'PENDING' ? 'warning' : ($status === 'CLAIMED' ? 'info' : ($status === 'APPROVED' ? 'success' : ($status === 'REJECTED' || $status === 'CLOSED' ? 'gray' : 'danger')));
                                    ?>"><?php echo esc_html($status); ?></span>
                                </td>
                                <td><strong><?php echo intval($cnt); ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Scan Trigger Section -->
    <div class="card">
        <div class="card-header">
            <h2>🔎 Run Mismatch Scan</h2>
        </div>
        <div class="card-body">
            <p style="color:var(--text-secondary);font-size:14px;margin-bottom:16px;">
                Trigger a manual mismatch scan for a specific class. This checks all students in the selected class for duplicate invoices, overpayments, unmatched PayStation payments, and other discrepancies. Results are written to the <code>ct_payment_mismatch</code> table.
            </p>
            <form method="post" class="filter-row" onsubmit="return confirm('Run mismatch scan for this class? This may take a while for large classes.');">
                <input type="hidden" name="action" value="trigger_scan">
                <div class="filter-group">
                    <label>Class</label>
                    <select name="scan_class_id" required>
                        <option value="">Select Class</option>
                        <?php
                        $all_classes = $wpdb->get_results("SELECT classid, className FROM ct_class ORDER BY className");
                        foreach ($all_classes as $c) {
                            printf('<option value="%d">%s</option>', $c->classid, esc_html($c->className));
                        }
                        ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Year</label>
                    <select name="scan_year">
                        <?php
                        $cur_yr = current_time('Y');
                        for ($y = $cur_yr; $y >= $cur_yr - 5; $y--) {
                            printf('<option value="%s" %s>%s</option>', $y, selected($cur_yr, $y, false), $y);
                        }
                        ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">🚀 Run Scan</button>
            </form>

            <?php
            // Handle scan trigger
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'trigger_scan') {
                $scan_class_id = intval($_POST['scan_class_id']);
                $scan_year     = isset($_POST['scan_year']) ? sanitize_text_field($_POST['scan_year']) : current_time('Y');

                if ($scan_class_id > 0 && $mm_exists) {
                    // Process in batches of 20 to avoid timeout
                    $batch_size = 20;
                    $offset = 0;
                    $total_scanned = 0;
                    $total_mismatches = 0;
                    $total_new = 0;
                    $scan_errors = array();

                    do {
                        $students = $wpdb->get_results($wpdb->prepare(
                            "SELECT infoStdid AS student_id FROM ct_studentinfo
                             WHERE infoClass = %d AND infoYear = %s
                             ORDER BY infoRoll ASC LIMIT %d OFFSET %d",
                            $scan_class_id, $scan_year, $batch_size, $offset
                        ), ARRAY_A);

                        if (empty($students)) break;

                        foreach ($students as $std) {
                            $mock_req = new WP_REST_Request('POST');
                            $mock_req->set_param('student_id', $std['student_id']);
                            $mock_req->set_param('class_id', $scan_class_id);
                            $mock_req->set_param('year', $scan_year);

                            $result = s3s_mismatch_scan_student($mock_req);
                            if (is_wp_error($result)) {
                                $scan_errors[] = "Student #{$std['student_id']}: " . $result->get_error_message();
                            } else {
                                $total_mismatches += $result['mismatches_found'];
                                $total_new += $result['new_records'];
                            }
                            $total_scanned++;
                        }

                        $offset += $batch_size;
                    } while (count($students) === $batch_size);

                    $class_name = $wpdb->get_var($wpdb->prepare("SELECT className FROM ct_class WHERE classid = %d", $scan_class_id));
                    echo '<div class="alert alert-success" style="margin-top:12px;">';
                    echo '✅ Scan complete for <strong>' . esc_html($class_name ?: "Class #{$scan_class_id}") . '</strong> (' . esc_html($scan_year) . '): ';
                    echo intval($total_scanned) . ' student(s) scanned, ';
                    echo intval($total_mismatches) . ' mismatch(es) found, ';
                    echo intval($total_new) . ' new record(s) inserted.';
                    if (!empty($scan_errors)) {
                        echo '<br><small style="color:var(--danger);">Errors: ' . esc_html(implode('; ', $scan_errors)) . '</small>';
                    }
                    echo '</div>';
                } elseif (!$mm_exists) {
                    echo '<div class="alert alert-error" style="margin-top:12px;">Please run the migration first to create the mismatch tables.</div>';
                }
            }
            ?>
            <hr style="margin:20px 0;border:none;border-top:1px solid var(--border);">
            <div class="filter-row">
                <div style="flex:1;">
                    <p style="color:var(--text-secondary);font-size:13px;"><strong>📡 Full Scan — All Classes</strong><br>Scan every class for the current year in one go using the cron endpoint. Classes are processed sequentially with batch sizes of 30 students.</p>
                </div>
                <form method="post" onsubmit="return confirm('This will scan ALL classes for the current year (<?php echo esc_js(current_time('Y')); ?>). Continue?');">
                    <input type="hidden" name="action" value="trigger_full_scan">
                    <input type="hidden" name="full_scan_year" value="<?php echo esc_attr(current_time('Y')); ?>">
                    <button type="submit" class="btn btn-primary">📡 Full Scan All Classes</button>
                </form>
            </div>
            <?php
            // Handle full scan trigger
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'trigger_full_scan') {
                $scan_year = isset($_POST['full_scan_year']) ? sanitize_text_field($_POST['full_scan_year']) : current_time('Y');

                if ($mm_exists) {
                    $mock_req = new WP_REST_Request('POST');
                    $mock_req->set_param('year', $scan_year);
                    $mock_req->set_param('batch_size', 30);

                    $result = s3s_mismatch_scan_all_classes($mock_req);

                    echo '<div class="alert alert-success" style="margin-top:12px;">';
                    echo '✅ <strong>Full Scan Complete</strong> for ' . esc_html($scan_year) . ':<br>';
                    echo intval($result['classes_processed']) . ' class(es), ';
                    echo intval($result['total_students']) . ' student(s) scanned, ';
                    echo intval($result['total_mismatches']) . ' mismatch(es) found, ';
                    echo intval($result['new_records']) . ' new record(s) inserted.';
                    if (!empty($result['errors'])) {
                        echo '<br><small style="color:var(--danger);">Errors: ' . esc_html(implode('; ', array_slice($result['errors'], 0, 5))) . '</small>';
                    }
                    if (!empty($result['class_details'])) {
                        echo '<details style="margin-top:8px;font-size:12px;"><summary>📋 Per-class breakdown</summary>';
                        echo '<table style="font-size:12px;margin-top:6px;width:100%;"><thead><tr><th>Class</th><th>Students</th><th>Mismatches</th><th>New Records</th></tr></thead><tbody>';
                        foreach ($result['class_details'] as $cd) {
                            printf('<tr><td>%s</td><td>%d</td><td>%d</td><td>%d</td></tr>',
                                esc_html($cd['class_name']), $cd['students'], $cd['mismatches'], $cd['new_records']);
                        }
                        echo '</tbody></table></details>';
                    }
                    echo '</div>';
                } else {
                    echo '<div class="alert alert-error" style="margin-top:12px;">Please run the migration first.</div>';
                }
            }
            ?>
        </div>
    </div>

<?php elseif ($active_tab === 'students'): ?>
    <!-- ===================== STUDENT WISE TAB ===================== -->
    <div class="card">
        <div class="card-header">
            <h2>👤 Student-wise Mismatches</h2>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                    <input type="hidden" name="page" value="mismatch-reconciliation">
                    <input type="hidden" name="tab" value="students">
                    <div class="filter-group">
                        <label>Class</label>
                        <select name="class_id">
                            <option value="">All Classes</option>
                            <?php
                            $classes = $wpdb->get_results("SELECT classid, className FROM ct_class ORDER BY className");
                            $sel_class = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
                            foreach ($classes as $c) {
                                printf('<option value="%d" %s>%s</option>', $c->classid, selected($sel_class, $c->classid, false), esc_html($c->className));
                            }
                            ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Mismatch Type</label>
                        <select name="type">
                            <option value="">All Types</option>
                            <?php
                            $sel_type = isset($_GET['type']) ? sanitize_key($_GET['type']) : '';
                            $types = array('DUPLICATE_PAYMENT', 'OVERPAYMENT', 'UNIDENTIFIED_PAYMENT', 'PAID_WRONG_STUDENT');
                            foreach ($types as $t) {
                                printf('<option value="%s" %s>%s</option>', $t, selected($sel_type, $t, false), str_replace('_', ' ', $t));
                            }
                            ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="">All Status</option>
                            <?php
                            $sel_status = isset($_GET['status']) ? sanitize_key($_GET['status']) : '';
                            $statuses = array('PENDING', 'CLAIMED', 'APPROVED', 'REJECTED', 'CLOSED');
                            foreach ($statuses as $s) {
                                printf('<option value="%s" %s>%s</option>', $s, selected($sel_status, $s, false), $s);
                            }
                            ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                    <a href="mismatch-reconciliation?tab=students" class="btn btn-outline btn-sm">Clear</a>
                </form>
            </div>
        </div>
        <div class="card-body">
            <?php
            if (!$mm_exists) {
                echo '<div class="empty-state"><p>Mismatch table not found. Please run the migration first.</p></div>';
            } else {
                // Build query with filters
                $where_conditions = array('1=1');
                $query_params = array();

                if ($sel_class > 0) {
                    $where_conditions[] = 'm.student_id IN (SELECT infoStdid FROM ct_studentinfo WHERE infoClass = %d)';
                    $query_params[] = $sel_class;
                }
                if (!empty($sel_type)) {
                    $where_conditions[] = 'm.mismatch_type = %s';
                    $query_params[] = $sel_type;
                }
                if (!empty($sel_status)) {
                    $where_conditions[] = 'm.status = %s';
                    $query_params[] = $sel_status;
                }

                $where = implode(' AND ', $where_conditions);

                $student_mm_query = $wpdb->prepare(
                    "SELECT m.*, s.stdName, si.infoRoll, si.infoClass
                     FROM $mismatch_table m
                     LEFT JOIN ct_student s ON s.studentid = m.student_id
                     LEFT JOIN ct_studentinfo si ON si.infoStdid = m.student_id
                     WHERE {$where}
                     ORDER BY m.detected_at DESC
                     LIMIT 200",
                    $query_params
                );
                $student_mms = $wpdb->get_results($student_mm_query, ARRAY_A);

                if (empty($student_mms)): ?>
                <div class="empty-state">
                    <div class="empty-icon">✅</div>
                    <h3>No Mismatches Found</h3>
                    <p>There are no payment mismatches matching your filters.</p>
                </div>
                <?php else: ?>
                <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Roll</th>
                            <th>Class</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Detected</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($student_mms as $mm):
                            $class_name = '';
                            if ($mm['infoClass']) {
                                $cn = $wpdb->get_var($wpdb->prepare("SELECT className FROM ct_class WHERE classid = %d", $mm['infoClass']));
                                $class_name = $cn ?: 'Class #' . $mm['infoClass'];
                            }
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($mm['stdName'] ?: 'Student #' . $mm['student_id']); ?></strong></td>
                            <td><?php echo esc_html($mm['infoRoll'] ?: '—'); ?></td>
                            <td><?php echo esc_html($class_name ?: '—'); ?></td>
                            <td><span class="badge badge-<?php echo $mm['mismatch_type'] === 'DUPLICATE_PAYMENT' ? 'danger' : ($mm['mismatch_type'] === 'OVERPAYMENT' ? 'warning' : 'info'); ?>"><?php echo esc_html(str_replace('_', ' ', $mm['mismatch_type'])); ?></span></td>
                            <td>৳<?php echo number_format(floatval($mm['amount']), 0); ?></td>
                            <td><span class="badge badge-<?php
                                echo $mm['status'] === 'PENDING' ? 'warning' : ($mm['status'] === 'CLAIMED' ? 'info' : ($mm['status'] === 'APPROVED' ? 'success' : 'gray'));
                            ?>"><?php echo esc_html($mm['status']); ?></span></td>
                            <td><?php echo esc_html($mm['detected_at'] ? date('d M Y', strtotime($mm['detected_at'])) : '—'); ?></td>
                            <td>
                                <div class="action-group">
                                    <a href="student-fee-audit-report/?student_id=<?php echo intval($mm['student_id']); ?>&class_id=<?php echo intval($mm['infoClass']); ?>" class="btn btn-outline btn-sm" target="_blank">View</a>
                                    <?php if ($mm['status'] === 'PENDING'): ?>
                                    <button class="btn btn-success btn-sm" onclick="openClaimModal(<?php echo intval($mm['mismatch_id']); ?>, <?php echo intval($mm['student_id']); ?>)">Claim</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif;
            } ?>
        </div>
    </div>

<?php elseif ($active_tab === 'classes'): ?>
    <!-- ===================== CLASS WISE TAB ===================== -->
    <div class="card">
        <div class="card-header">
            <h2>📚 Class-wise Mismatch Summary</h2>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                    <input type="hidden" name="page" value="mismatch-reconciliation">
                    <input type="hidden" name="tab" value="classes">
                    <div class="filter-group">
                        <label>Year</label>
                        <select name="year">
                            <?php
                            $current_year = current_time('Y');
                            $sel_year = isset($_GET['year']) ? $_GET['year'] : $current_year;
                            for ($y = $current_year; $y >= $current_year - 5; $y--) {
                                printf('<option value="%s" %s>%s</option>', $y, selected($sel_year, $y, false), $y);
                            }
                            ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">Apply</button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <?php
            if (!$mm_exists) {
                echo '<div class="empty-state"><p>Mismatch table not found. Please run the migration first.</p></div>';
            } else {
                // Class-wise stats
                $class_stats = $wpdb->get_results($wpdb->prepare(
                    "SELECT si.infoClass AS class_id,
                            COUNT(DISTINCT m.mismatch_id) AS total_mismatches,
                            COUNT(DISTINCT m.student_id) AS affected_students,
                            COALESCE(SUM(CASE WHEN m.mismatch_type = 'DUPLICATE_PAYMENT' THEN 1 ELSE 0 END), 0) AS duplicate_count,
                            COALESCE(SUM(CASE WHEN m.mismatch_type = 'OVERPAYMENT' THEN 1 ELSE 0 END), 0) AS overpayment_count,
                            COALESCE(SUM(CASE WHEN m.mismatch_type = 'UNIDENTIFIED_PAYMENT' THEN 1 ELSE 0 END), 0) AS unidentified_count,
                            COALESCE(SUM(m.amount), 0) AS total_amount,
                            COALESCE(SUM(CASE WHEN m.status = 'PENDING' THEN 1 ELSE 0 END), 0) AS pending_count
                     FROM $mismatch_table m
                     INNER JOIN ct_studentinfo si ON si.infoStdid = m.student_id
                     WHERE si.infoYear = %s
                     GROUP BY si.infoClass
                     ORDER BY total_mismatches DESC",
                    $sel_year
                ), ARRAY_A);

                if (empty($class_stats)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📚</div>
                    <h3>No Class Data</h3>
                    <p>No mismatches recorded for the selected year. Run a mismatch scan first.</p>
                </div>
                <?php else: ?>
                <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Class</th>
                            <th>Affected Students</th>
                            <th>Total Mismatches</th>
                            <th>🔴 Duplicates</th>
                            <th>🟡 Overpayments</th>
                            <th>🔵 Unidentified</th>
                            <th>⏳ Pending</th>
                            <th>Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($class_stats as $cs):
                            $cname = $wpdb->get_var($wpdb->prepare("SELECT className FROM ct_class WHERE classid = %d", $cs['class_id']));
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($cname ?: 'Class #' . $cs['class_id']); ?></strong></td>
                            <td><?php echo intval($cs['affected_students']); ?></td>
                            <td><?php echo intval($cs['total_mismatches']); ?></td>
                            <td><span class="badge badge-danger"><?php echo intval($cs['duplicate_count']); ?></span></td>
                            <td><span class="badge badge-warning"><?php echo intval($cs['overpayment_count']); ?></span></td>
                            <td><span class="badge badge-info"><?php echo intval($cs['unidentified_count']); ?></span></td>
                            <td><span class="badge badge-gray"><?php echo intval($cs['pending_count']); ?></span></td>
                            <td><strong>৳<?php echo number_format(floatval($cs['total_amount']), 0); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif;
            } ?>
        </div>
    </div>

<?php elseif ($active_tab === 'claims'): ?>
    <!-- ===================== CLAIMS TAB ===================== -->
    <div class="card">
        <div class="card-header">
            <h2>📋 Claim Management</h2>
            <div style="display:flex;gap:8px;flex-wrap:wrap;">
                <span class="badge badge-warning" style="font-size:12px;padding:6px 14px;">Pending: <?php echo intval($pending_claims); ?></span>
            </div>
        </div>
        <div class="card-body">
            <?php
            if (!$cl_exists || !$mm_exists) {
                echo '<div class="empty-state"><p>Claims table not found. Please run the migration first.</p></div>';
            } else {
                // Fetch claims with mismatch details
                $claims_query = "SELECT c.*, m.mismatch_type, m.amount AS mismatch_amount, m.description AS mismatch_desc,
                                        m.student_id AS orig_student_id,
                                        s.stdName AS claimant_name, si.infoRoll AS claimant_roll,
                                        os.stdName AS orig_student_name
                                 FROM $claim_table c
                                 LEFT JOIN $mismatch_table m ON c.mismatch_id = m.mismatch_id
                                 LEFT JOIN ct_student s ON s.studentid = c.claim_student_id
                                 LEFT JOIN ct_studentinfo si ON si.infoStdid = c.claim_student_id
                                 LEFT JOIN ct_student os ON os.studentid = m.student_id
                                 ORDER BY c.submitted_at DESC
                                 LIMIT 100";
                $claims = $wpdb->get_results($claims_query, ARRAY_A);

                if (empty($claims)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📋</div>
                    <h3>No Claims Yet</h3>
                    <p>No one has submitted a claim for any mismatch. Claims appear when students identify their payments.</p>
                </div>
                <?php else: ?>
                <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Claim #</th>
                            <th>Claimant</th>
                            <th>Original Student</th>
                            <th>Mismatch Type</th>
                            <th>Amount</th>
                            <th>Reason</th>
                            <th>Submitted</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($claims as $claim): ?>
                        <tr>
                            <td><strong>#<?php echo intval($claim['claim_id']); ?></strong></td>
                            <td>
                                <strong><?php echo esc_html($claim['claimant_name'] ?: 'Student #' . $claim['claim_student_id']); ?></strong>
                                <?php if ($claim['claimant_roll']): ?><br><small style="color:var(--text-secondary);">Roll: <?php echo esc_html($claim['claimant_roll']); ?></small><?php endif; ?>
                            </td>
                            <td><?php echo esc_html($claim['orig_student_name'] ?: '#' . $claim['orig_student_id']); ?></td>
                            <td><span class="badge badge-<?php echo $claim['mismatch_type'] === 'DUPLICATE_PAYMENT' ? 'danger' : ($claim['mismatch_type'] === 'OVERPAYMENT' ? 'warning' : 'info'); ?>"><?php echo esc_html(str_replace('_', ' ', $claim['mismatch_type'])); ?></span></td>
                            <td>৳<?php echo number_format(floatval($claim['mismatch_amount']), 0); ?></td>
                            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo esc_attr($claim['claim_reason']); ?>">
                                <?php echo esc_html($claim['claim_reason'] ?: '—'); ?>
                            </td>
                            <td><?php echo esc_html($claim['submitted_at'] ? date('d M Y', strtotime($claim['submitted_at'])) : '—'); ?></td>
                            <td>
                                <span class="badge badge-<?php
                                    echo $claim['status'] === 'PENDING' ? 'warning' : ($claim['status'] === 'APPROVED' ? 'success' : 'gray');
                                ?>"><?php echo esc_html($claim['status']); ?></span>
                                <?php if ($claim['reviewed_at']): ?>
                                    <br><small style="color:var(--text-secondary);font-size:10px;"><?php echo esc_html(date('d M Y', strtotime($claim['reviewed_at']))); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($claim['status'] === 'PENDING'): ?>
                                <div class="action-group">
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Approve this claim? The mismatch will be marked as CLAIMED.');">
                                        <input type="hidden" name="claim_id" value="<?php echo intval($claim['claim_id']); ?>">
                                        <input type="hidden" name="claim_action" value="approve">
                                        <button type="submit" class="btn btn-success btn-sm">✅ Approve</button>
                                    </form>
                                    <button class="btn btn-danger btn-sm" onclick="openRejectModal(<?php echo intval($claim['claim_id']); ?>)">❌ Reject</button>
                                </div>
                                <?php else: ?>
                                    <?php if ($claim['review_notes']): ?>
                                    <small style="color:var(--text-secondary);"><?php echo esc_html($claim['review_notes']); ?></small>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif;
            } ?>
        </div>
    </div>

    <!-- Reject Modal -->
    <div class="modal-overlay" id="rejectModal">
        <div class="modal-box">
            <h3>Reject Claim</h3>
            <p style="color:var(--text-secondary);font-size:14px;">Provide a reason for rejecting this claim.</p>
            <form method="post" id="rejectForm">
                <input type="hidden" name="claim_id" id="reject_claim_id" value="">
                <input type="hidden" name="claim_action" value="reject">
                <textarea name="review_notes" placeholder="Enter rejection reason..." required></textarea>
                <div class="btn-group">
                    <button type="button" class="btn btn-outline" onclick="closeRejectModal()">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Claim</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Claim Modal (for student-wise tab) -->
    <div class="modal-overlay" id="claimModal">
        <div class="modal-box">
            <h3>Submit Claim</h3>
            <p style="color:var(--text-secondary);font-size:14px;">Link this mismatch to the correct student.</p>
            <form method="post" action="">
                <input type="hidden" name="claim_action" value="submit_claim">
                <input type="hidden" name="mismatch_id" id="claim_mismatch_id" value="">
                <div class="filter-group" style="margin-bottom:12px;">
                    <label>Claiming Student ID</label>
                    <input type="number" name="claim_student_id" id="claim_student_id" placeholder="Enter student ID" required>
                </div>
                <div class="filter-group" style="margin-bottom:12px;">
                    <label>Reason</label>
                    <textarea name="claim_reason" placeholder="Explain why this payment belongs to this student..." required style="min-height:80px;"></textarea>
                </div>
                <div class="btn-group">
                    <button type="button" class="btn btn-outline" onclick="closeClaimModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Claim</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openRejectModal(claimId) {
        document.getElementById('reject_claim_id').value = claimId;
        document.getElementById('rejectModal').classList.add('active');
    }
    function closeRejectModal() {
        document.getElementById('rejectModal').classList.remove('active');
    }
    function openClaimModal(mismatchId, studentId) {
        document.getElementById('claim_mismatch_id').value = mismatchId;
        document.getElementById('claim_student_id').value = studentId;
        document.getElementById('claimModal').classList.add('active');
    }
    function closeClaimModal() {
        document.getElementById('claimModal').classList.remove('active');
    }
    // Close modals on overlay click
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-overlay')) {
            e.target.classList.remove('active');
        }
    });
    </script>

<?php endif; ?>

</body>
</html>