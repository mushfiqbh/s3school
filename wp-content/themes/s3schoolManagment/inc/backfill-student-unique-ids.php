<?php
/**
 * Backfill Student Unique IDs
 * Assigns unique IDs to existing students based on current year (2025)
 * Order: Class 6 → 7 → 8 → 9 → 10, sorted by roll number within each class
 * 
 * Usage:
 * 1. Access via WordPress admin page
 * 2. Or run directly: php backfill-student-unique-ids.php
 */

if (!defined('ABSPATH')) {
    // If running standalone, try to find and load WordPress
    // Try multiple possible paths
    $wp_load_paths = array(
        dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php', // 4 levels up
        dirname(dirname(dirname(__FILE__))) . '/wp-load.php', // 3 levels up (if in different structure)
        '../../../wp-load.php',
        '../../../../wp-load.php',
    );
    
    $wp_loaded = false;
    foreach ($wp_load_paths as $path) {
        if (file_exists($path)) {
            require_once($path);
            $wp_loaded = true;
            break;
        }
    }
    
    if (!$wp_loaded) {
        die('Error: Could not find wp-load.php. Please run this script from WordPress admin or ensure WordPress is properly installed.');
    }
}

global $wpdb;

/**
 * Backfill unique IDs for existing students
 * 
 * @param string $year Academic year (default: 2025)
 * @return array Results with success count and errors
 */
function backfill_student_unique_ids($year = '2025') {
    global $wpdb;
    
    $results = array(
        'success' => 0,
        'skipped' => 0,
        'errors' => array()
    );
    
    // Get students from current year, ordered by class then roll
    $students = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT s.studentid, si.infoClass, si.infoRoll, si.infoSection, si.infoGroup
             FROM ct_student s
             INNER JOIN ct_studentinfo si ON s.studentid = si.infoStdid
             WHERE si.infoYear = %s
             AND si.infoClass IN (65,66, 67, 68, 69, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100)
             AND s.stdStatus = 1
             ORDER BY si.infoClass ASC, CAST(si.infoRoll AS UNSIGNED) ASC",
            $year
        )
    );
    
    if (empty($students)) {
        $results['errors'][] = 'No students found for year ' . $year;
        return $results;
    }
    
    // Get the highest existing unique ID number
    $max_id = $wpdb->get_var(
        "SELECT MAX(CAST(SUBSTRING(stdUniqueID, 6) AS UNSIGNED)) 
         FROM ct_student 
         WHERE stdUniqueID LIKE 'MCNK-%' 
         AND stdUniqueID IS NOT NULL"
    );
    
    $next_number = ($max_id ? intval($max_id) + 1 : 1);
    
    // Assign unique IDs to each student
    foreach ($students as $student) {
        // Check if student already has a unique ID
        $existing_id = $wpdb->get_var($wpdb->prepare(
            "SELECT stdUniqueID FROM ct_student WHERE studentid = %d",
            $student->studentid
        ));
        
        if (!empty($existing_id)) {
            $results['skipped']++;
            continue; // Skip if already has unique ID
        }
        
        // Generate unique ID
        $unique_id = 'MCNK-' . str_pad($next_number, 5, '0', STR_PAD_LEFT);
        
        // Update student record
        $updated = $wpdb->update(
            'ct_student',
            array('stdUniqueID' => $unique_id),
            array('studentid' => $student->studentid),
            array('%s'),
            array('%d')
        );
        
        if ($updated !== false) {
            $results['success']++;
            $next_number++;
        } else {
            $results['errors'][] = 'Failed to assign unique ID to student ID: ' . $student->studentid;
        }
    }
    
    return $results;
}

// If accessed directly (not via WordPress), run the backfill
if (php_sapi_name() === 'cli' || (isset($_GET['run_backfill']) && current_user_can('manage_options'))) {
    $year = isset($_GET['year']) ? sanitize_text_field($_GET['year']) : '2025';
    $results = backfill_student_unique_ids($year);
    
    if (php_sapi_name() === 'cli') {
        echo "Backfill Results:\n";
        echo "Success: " . $results['success'] . "\n";
        echo "Skipped: " . $results['skipped'] . "\n";
        echo "Errors: " . count($results['errors']) . "\n";
        if (!empty($results['errors'])) {
            foreach ($results['errors'] as $error) {
                echo "  - " . $error . "\n";
            }
        }
    } else {
        // Return JSON for AJAX or display
        header('Content-Type: application/json');
        echo json_encode($results);
        exit;
    }
}

