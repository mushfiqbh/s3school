<?php
/**
 * Migration Script: Add month column to ct_student_fee_collection_details
 * 
 * Run this script ONCE after deploying Phase 1.
 * 
 * Usage: Run from browser or CLI after ensuring wp-load.php is accessible.
 *        Or execute the ALTER TABLE directly via phpMyAdmin.
 */

// Security: prevent direct access if not called from migration runner
if (!defined('ABSPATH') && !defined('MIGRATION_RUNNING')) {
    header('Content-Type: text/plain');
    echo "Migration Script: Add month column to collection_details\n";
    echo "=========================================================\n\n";
    
    // Bootstrap WordPress
    $wp_load_path = dirname(__FILE__, 5) . '/wp-load.php';
    if (file_exists($wp_load_path)) {
        require_once($wp_load_path);
    } else {
        die("Cannot find wp-load.php\n");
    }
}

global $wpdb;

echo "Starting migration: Add month column to ct_student_fee_collection_details...\n\n";

// Step 1: Check if column already exists
$column_exists = $wpdb->get_results("SHOW COLUMNS FROM ct_student_fee_collection_details LIKE 'month'");
if ($column_exists) {
    echo "✓ Column 'month' already exists. Skipping ALTER TABLE.\n";
} else {
    echo "Adding column 'month' (INT(11) DEFAULT NULL) after sub_head_id...\n";
    $result = $wpdb->query("ALTER TABLE ct_student_fee_collection_details ADD COLUMN month INT(11) DEFAULT NULL AFTER sub_head_id");
    if ($result === false) {
        echo "✗ Failed to add column: " . $wpdb->last_error . "\n";
        exit(1);
    }
    echo "✓ Column added successfully.\n";
}

echo "\nStep 2: Backfilling existing data...\n";

// Get all collection_details rows that belong to monthly sub_heads (type=1)
// and currently have no month set
$rows_to_backfill = $wpdb->get_results("
    SELECT cd.id, cd.info_id, cd.sub_head_id, cd.fee, ci.month as info_month, ci.student_id, ci.class_id, ci.year
    FROM ct_student_fee_collection_details cd
    INNER JOIN ct_student_fee_collection_info ci ON ci.id = cd.info_id
    INNER JOIN ct_sub_head sh ON sh.id = cd.sub_head_id
    WHERE sh.type = 1 
      AND cd.month IS NULL
      AND cd.fee > 0
    ORDER BY cd.id
");

if (empty($rows_to_backfill)) {
    echo "✓ No rows need backfilling.\n";
} else {
    $updated = 0;
    $split_into = 0;
    
    foreach ($rows_to_backfill as $row) {
        $info_month = intval($row->info_month);
        $fee = floatval($row->fee);
        $sub_head_id = intval($row->sub_head_id);
        $class_id = intval($row->class_id);
        $student_id = intval($row->student_id);
        $year = $row->year;
        
        // Get the base monthly fee from fee_list
        $base_fee = $wpdb->get_var($wpdb->prepare(
            "SELECT fee FROM ct_student_fee_list 
             WHERE sub_head_id = %d AND class_id = %d AND year = %s 
             ORDER BY id DESC LIMIT 1",
            $sub_head_id, $class_id, $year
        ));
        $base_fee = $base_fee ? floatval($base_fee) : 0;
        
        if ($info_month > 0 && $info_month <= 12) {
            if ($base_fee > 0 && $info_month > 1 && abs($fee - ($base_fee * $info_month)) < 0.01) {
                // This row covers multiple months (e.g. fee=6000, base=1000, month=6)
                // Update this row to month=1, then insert rows for months 2..info_month
                $wpdb->update(
                    'ct_student_fee_collection_details',
                    array('month' => 1),
                    array('id' => $row->id)
                );
                $updated++;
                
                for ($m = 2; $m <= $info_month; $m++) {
                    $wpdb->insert('ct_student_fee_collection_details', array(
                        'info_id' => $row->info_id,
                        'sub_head_id' => $sub_head_id,
                        'month' => $m,
                        'fee' => $base_fee,
                        'status' => 1,
                        'reference' => 'Monthly Summary',
                        'date' => $row->info_month ? $row->date : current_time('mysql'),
                        'created_by' => 0,
                        'created_at' => current_time('mysql'),
                    ));
                    $split_into++;
                }
            } else {
                // Single month payment or can't determine split
                // Set month to the info_month value
                $wpdb->update(
                    'ct_student_fee_collection_details',
                    array('month' => $info_month),
                    array('id' => $row->id)
                );
                $updated++;
            }
        }
    }
    
    echo "✓ Backfill complete: $updated rows updated, $split_into new rows created (from splitting multi-month payments).\n";
}

// Step 3: Verify
echo "\nStep 3: Verification...\n";
$still_null = $wpdb->get_var("
    SELECT COUNT(*) FROM ct_student_fee_collection_details cd
    INNER JOIN ct_sub_head sh ON sh.id = cd.sub_head_id
    WHERE sh.type = 1 AND cd.month IS NULL
");
$total_monthly = $wpdb->get_var("
    SELECT COUNT(*) FROM ct_student_fee_collection_details cd
    INNER JOIN ct_sub_head sh ON sh.id = cd.sub_head_id
    WHERE sh.type = 1
");
$with_month = $total_monthly - $still_null;

echo "  Total monthly-type detail rows: $total_monthly\n";
echo "  Rows with month set: $with_month\n";
echo "  Rows still NULL: $still_null\n";

if ($still_null > 0) {
    echo "  ⚠ Some monthly rows still have NULL month. These may be edge cases needing manual review.\n";
} else {
    echo "  ✓ All monthly collection_details rows now have month populated.\n";
}

echo "\nMigration complete!\n";
