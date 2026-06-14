<?php
/**
 * Student Unique ID Generation Functions
 * Handles generation and management of student unique identifiers (MCNK-00001, etc.)
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generate a new unique student ID
 * Format: MCNK-00001, MCNK-00002, etc.
 * 
 * @return string Unique student ID
 */
function generate_student_unique_id() {
    global $wpdb;
    
    // Get the highest existing unique ID number
    $max_id = $wpdb->get_var(
        "SELECT MAX(CAST(SUBSTRING(stdUniqueID, 6) AS UNSIGNED)) 
         FROM ct_student 
         WHERE stdUniqueID LIKE 'MCNK-%' 
         AND stdUniqueID IS NOT NULL"
    );
    
    // Calculate next number (start from 1 if no existing IDs)
    $next_number = ($max_id ? intval($max_id) + 1 : 1);
    
    // Format as MCNK-00001 (5-digit zero-padded number)
    $unique_id = 'MCNK-' . str_pad($next_number, 5, '0', STR_PAD_LEFT);
    
    return $unique_id;
}

/**
 * Assign unique ID to a student
 * 
 * @param int $student_id Student ID
 * @return string|false Unique ID on success, false on failure
 */
function assign_student_unique_id($student_id) {
    global $wpdb;
    
    // Check if student already has a unique ID
    $existing_id = $wpdb->get_var($wpdb->prepare(
        "SELECT stdUniqueID FROM ct_student WHERE studentid = %d",
        $student_id
    ));
    
    if (!empty($existing_id)) {
        return $existing_id; // Return existing ID
    }
    
    // Generate new unique ID
    $unique_id = generate_student_unique_id();
    
    // Update student record
    $updated = $wpdb->update(
        'ct_student',
        array('stdUniqueID' => $unique_id),
        array('studentid' => $student_id),
        array('%s'),
        array('%d')
    );
    
    if ($updated !== false) {
        return $unique_id;
    }
    
    return false;
}

/**
 * Get student by unique ID
 * 
 * @param string $unique_id Student unique ID (e.g., MCNK-00001)
 * @return object|false Student object on success, false on failure
 */
function get_student_by_unique_id($unique_id) {
    global $wpdb;
    
    $student = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM ct_student WHERE stdUniqueID = %s AND stdStatus = 1",
        $unique_id
    ));
    
    return $student ? $student : false;
}

