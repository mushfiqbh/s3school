<?php

if (!defined('ABSPATH')) {
    $wpLoadPath = dirname(__FILE__, 6) . '/wp-load.php';
    if (!file_exists($wpLoadPath)) {
        $wpLoadPath = dirname(__FILE__, 5) . '/wp-load.php';
    }
    require_once $wpLoadPath;
}

if (ob_get_level() === 0) {
    ob_start();
}

// Initialize default POST keys to prevent notices
if (!isset($_POST['type'])) $_POST['type'] = '';
if (!isset($_POST['action'])) $_POST['action'] = '';

/*
    Handle class dependent dropdowns
*/
if (isset($_POST['type']) && $_POST['type'] === 'getYears') {
    header('Content-Type: text/html; charset=UTF-8');
    global $wpdb;

    $class = intval($_POST['class'] ?? 0);
    if ($class <= 0) {
        echo "<option value=''>Select Class First</option>";
        exit;
    }

    $years = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT infoYear FROM ct_studentinfo WHERE infoClass = %d GROUP BY infoYear ORDER BY infoYear ASC",
            $class
        )
    );

    if (empty($years)) {
        echo "<option value=''>No Student In this class</option>";
    } else {
        echo "<option value=''>Year</option>";
    }

    foreach ($years as $year) {
        echo "<option value='" . esc_attr($year->infoYear) . "'>" . esc_html($year->infoYear) . "</option>";
    }
    exit;
}

if (isset($_POST['type']) && $_POST['type'] === 'getSection') {
    header('Content-Type: text/html; charset=UTF-8');
    global $wpdb;

    $class = intval($_POST['class'] ?? 0);
    if ($class <= 0) {
        echo "<option value=''>Select Class First</option>";
        exit;
    }

    $sections = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT sectionid, sectionName FROM ct_section WHERE forClass = %d ORDER BY sectionName",
            $class
        )
    );

    if (!empty($sections)) {
        echo "<option value=''>Section</option>";
        foreach ($sections as $section) {
            echo "<option value='" . esc_attr($section->sectionid) . "'>" . esc_html($section->sectionName) . "</option>";
        }
    } else {
        echo "<option value=''>No sections available</option>";
    }
    exit;
}

/*
    Handle ID Card Image Upload (Consolidated)
*/
if (
    (isset($_POST['action']) && $_POST['action'] === 'upload_id_image') || 
    (isset($_POST['type']) && $_POST['type'] === 'upload_id_image')
) {
    header('Content-Type: application/json');
    
    if (empty($_FILES['id_image']) || !isset($_POST['design_no'])) {
        echo json_encode(['success' => false, 'data' => 'Invalid request']);
        wp_die();
    }

    global $wpdb;
    $design_no = intval($_POST['design_no']);
    $side = isset($_POST['side']) ? sanitize_text_field($_POST['side']) : 'front';
    $person_type = isset($_POST['person_type']) ? sanitize_text_field($_POST['person_type']) : 'student';
    
    if ($design_no <= 0) {
        echo json_encode(['success' => false, 'data' => 'Invalid design number']);
        wp_die();
    }

    if (!function_exists('wp_handle_upload')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    $uploadedfile = $_FILES['id_image'];
    $upload_overrides = ['test_form' => false];
    $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

    if ($movefile && !isset($movefile['error'])) {
        $uploaded_image_url = $movefile['url'];

        // Prepare option data
        $prefix = ($person_type === 'teacher') ? 'id-teacher-design-' : 'id-design-';
        $option_name  = ($side === 'back') ? $prefix . 'back-' . $design_no : $prefix . $design_no;
        $option_value = $uploaded_image_url;

        // Check if option already exists in sm_options
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM sm_options WHERE option_name = %s",
                $option_name
            )
        );

        if ($exists) {
            $result = $wpdb->update(
                'sm_options',
                ['option_value' => $option_value],
                ['option_name' => $option_name]
            );
        } else {
            $result = $wpdb->insert(
                'sm_options',
                [
                    'option_name'  => $option_name,
                    'option_value' => $option_value,
                    'autoload' => 'no'
                ]
            );
        }

        if ($result !== false) {
            $response_data = [
                'success' => true,
                'url'     => $uploaded_image_url,
                'data'    => 'Image uploaded successfully',
                'design_no' => $design_no
            ];
        } else {
            $response_data = [
                'success' => false, 
                'message' => 'Failed to update database with image URL',
                'db_error' => $wpdb->last_error
            ];
        }
    } else {
        $error_message = isset($movefile['error']) ? $movefile['error'] : 'Upload failed';
        $response_data = ['success' => false, 'data' => $error_message];
    }
    ob_end_clean();
    echo json_encode($response_data);
    exit;
}

/*
    Delete ID Card Background
*/
elseif (isset($_POST['action']) && $_POST['action'] === 'delete_id_card_bg') {
    header('Content-Type: application/json');
    global $wpdb;
    $design_no = intval($_POST['design_no']);
    $side = isset($_POST['side']) ? sanitize_text_field($_POST['side']) : 'front';
    $person_type = isset($_POST['person_type']) ? sanitize_text_field($_POST['person_type']) : 'student';
    
    $prefix = ($person_type === 'teacher') ? 'id-teacher-design-' : 'id-design-';
    $option_name = ($side === 'back') ? $prefix . 'back-' . $design_no : $prefix . $design_no;
    
    $result = $wpdb->delete('sm_options', ['option_name' => $option_name]);
    
    ob_end_clean();
    if ($result !== false) {
        echo json_encode(['success' => true, 'message' => 'Background removed successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to remove background']);
    }
    exit;
}

/*
    Save ID Card Configuration
*/
elseif (isset($_POST['action']) && $_POST['action'] === 'save_id_card_config') {
    header('Content-Type: application/json');
    global $wpdb;

    $design_no = intval($_POST['design_no']);
    $person_type = isset($_POST['person_type']) ? sanitize_text_field($_POST['person_type']) : 'student';
    $config_json = wp_unslash($_POST['config']); // Expecting JSON string

    if ($design_no <= 0 || empty($config_json)) {
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        wp_die();
    }

    // Validate JSON
    $config = json_decode($config_json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON config: ' . json_last_error_msg(), 'raw' => substr($config_json, 0, 100)]);
        wp_die();
    }

    $option_name = ($person_type === 'teacher') ? 'id_card_teacher_design_' . $design_no . '_config' : 'id_card_design_' . $design_no . '_config';
    
    // Check if exists
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM sm_options WHERE option_name = %s",
        $option_name
    ));

    $data = [
        'option_name' => $option_name,
        'option_value' => $config_json
    ];

    if ($exists) {
        $result = $wpdb->update('sm_options', ['option_value' => $config_json], ['option_name' => $option_name]);
    } else {
        $data['autoload'] = 'no';
        $result = $wpdb->insert('sm_options', $data);
    }

    if ($result !== false) {
        $response_data = ['success' => true, 'message' => 'Configuration saved', 'design_no' => $design_no];
    } else {
        $db_error = $wpdb->last_error;
        $response_data = ['success' => false, 'message' => 'Database error: ' . $db_error, 'query' => $wpdb->last_query];
    }
    ob_end_clean();
    echo json_encode($response_data);
    exit;
}

/*
    Get ID Card Configuration
*/
elseif (isset($_POST['action']) && $_POST['action'] === 'get_id_card_config') {
    header('Content-Type: application/json');
    global $wpdb;

    $design_no = intval($_POST['design_no']);
    $person_type = isset($_POST['person_type']) ? sanitize_text_field($_POST['person_type']) : 'student';
    
    if ($design_no <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid design number']);
        wp_die();
    }

    $option_name = ($person_type === 'teacher') ? 'id_card_teacher_design_' . $design_no . '_config' : 'id_card_design_' . $design_no . '_config';
    $config_json = $wpdb->get_var($wpdb->prepare(
        "SELECT option_value FROM sm_options WHERE option_name = %s",
        $option_name
    ));

    if ($config_json) {
        $response_data = ['success' => true, 'data' => json_decode($config_json)];
    } else {
        $response_data = ['success' => true, 'data' => null];
    }
    ob_end_clean();
    echo json_encode($response_data);
    exit;
}

/*
    List ID Card Designs
*/
elseif (isset($_POST['action']) && $_POST['action'] === 'get_id_card_designs') {
    header('Content-Type: application/json');
    global $wpdb;

    $person_type = isset($_POST['person_type']) ? sanitize_text_field($_POST['person_type']) : 'student';

    // Ensure default designs exist
    if ($person_type === 'teacher') {
        $defaults_to_check = [
            1 => [
                'design_name' => '', 'design_type' => 'front', 'orientation' => 'portrait', 'image_shape' => 'rounded',
                'image_border_size' => '', 'image_border_color' => '#000000', 'image_margin_top' => '75',
                'student_image_size' => 85, 'student_name_color' => '#065499',
                'show_logo' => false, 'show_inst_name' => true, 'show_image' => true, 'show_name' => true, 'show_id' => false,
                'show_class_section' => true,
                'show_class' => true, 'show_section' => false, 'show_roll' => true, 'show_year' => true, 'show_dob' => true,
                'show_blood' => true, 'show_phone' => false, 'show_father' => false, 'show_mother' => false,
                'show_address' => false, 'show_inst_address' => false, 'show_inst_phone' => false, 'show_signature' => true
            ]
        ];
    } else {
        $defaults_to_check = [
            1 => [
                'design_name' => 'Standard Potrait', 'design_type' => 'front', 'orientation' => 'portrait', 'image_shape' => 'rounded',
                'image_border_size' => '4', 'image_border_color' => '#0078b7', 'image_margin_top' => '75',
                'student_image_size' => 85, 'student_name_color' => '#065499',
                'show_logo' => false, 'show_inst_name' => false, 'show_image' => true, 'show_name' => true, 'show_id' => true,
                'show_class_section' => true,
                'show_class' => true, 'show_section' => true, 'show_roll' => false, 'show_year' => true, 'show_dob' => true,
                'show_blood' => false, 'show_phone' => true, 'show_father' => false, 'show_mother' => false,
                'show_address' => false, 'show_signature' => true
            ],
            2 => [
                'design_name' => 'Standard Potrait', 'design_type' => 'back', 'orientation' => 'portrait', 'image_shape' => 'square',
                'image_border_size' => '', 'image_border_color' => '#000000', 'image_margin_top' => '',
                'student_image_size' => 85, 'student_name_color' => '#065499',
                'show_logo' => true, 'show_inst_name' => true, 'show_image' => false, 'show_name' => false, 'show_id' => false,
                'show_class_section' => true,
                'show_class' => false, 'show_section' => false, 'show_roll' => false, 'show_year' => false, 'show_dob' => true,
                'show_blood' => true, 'show_phone' => true, 'show_father' => false, 'show_mother' => false,
                'show_address' => true, 'show_inst_address' => true, 'show_inst_phone' => true, 'show_signature' => true
            ]
        ];
    }

    foreach ($defaults_to_check as $no => $config) {
        $option_name = ($person_type === 'teacher') ? 'id_card_teacher_design_' . $no . '_config' : 'id_card_design_' . $no . '_config';
        $exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM sm_options WHERE option_name = %s", $option_name));
        if (!$exists) {
            $wpdb->insert('sm_options', [
                'option_name' => $option_name,
                'option_value' => json_encode($config),
                'autoload' => 'no'
            ]);
        }
    }

    // We look for patterns: id_card_design_{$no}_config or id-design-{$no} (or teacher variants)
    if ($person_type === 'teacher') {
        $query = "SELECT option_name, option_value FROM sm_options WHERE option_name LIKE 'id_card_teacher_design_%_config' OR option_name LIKE 'id-teacher-design-%'";
        $config_pattern = '/id_card_teacher_design_(\d+)_config/';
        $img_pattern = '/id-teacher-design-(\d+)/';
    } else {
        $query = "SELECT option_name, option_value FROM sm_options WHERE option_name LIKE 'id_card_design_%_config' OR option_name LIKE 'id-design-%'";
        $config_pattern = '/id_card_design_(\d+)_config/';
        $img_pattern = '/id-design-(\d+)/';
    }

    $results = $wpdb->get_results($query);
    
    $designs = [];
    $design_numbers = [1];
    
    // First collect all numbers
    foreach ($results as $row) {
        if (preg_match($config_pattern, $row->option_name, $matches)) {
            $design_numbers[] = intval($matches[1]);
        } elseif (preg_match($img_pattern, $row->option_name, $matches)) {
            $design_numbers[] = intval($matches[1]);
        }
    }
    
    $unique_numbers = array_unique($design_numbers);
    sort($unique_numbers);

    foreach ($unique_numbers as $no) {
        $name = 'Design ' . $no;
        
        // Try to find the name in config
        $option_name = ($person_type === 'teacher') ? 'id_card_teacher_design_' . $no . '_config' : 'id_card_design_' . $no . '_config';
        $config_json = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM sm_options WHERE option_name = %s", $option_name));
        
        if ($config_json) {
            $config = json_decode($config_json, true);
            if (!empty($config['design_name'])) {
                $name = $config['design_name'] . ' (' . ucfirst($config['design_type'] ?? 'front') . ')';
            }
        }
        
        $designs[] = [
            'no' => $no,
            'name' => $name
        ];
    }
    
    ob_end_clean();
    echo json_encode(['success' => true, 'data' => $designs]);
    exit;
}

/*
    Create New ID Card Design
*/
elseif (isset($_POST['action']) && $_POST['action'] === 'create_id_card_design') {
    header('Content-Type: application/json');
    global $wpdb;

    $person_type = isset($_POST['person_type']) ? sanitize_text_field($_POST['person_type']) : 'student';

    // Find max existing number
    if ($person_type === 'teacher') {
        $query = "SELECT option_name FROM sm_options WHERE option_name LIKE 'id_card_teacher_design_%_config' OR option_name LIKE 'id-teacher-design-%'";
        $config_pattern = '/id_card_teacher_design_(\d+)_config/';
        $img_pattern = '/id-teacher-design-(\d+)/';
    } else {
        $query = "SELECT option_name FROM sm_options WHERE option_name LIKE 'id_card_design_%_config' OR option_name LIKE 'id-design-%'";
        $config_pattern = '/id_card_design_(\d+)_config/';
        $img_pattern = '/id-design-(\d+)/';
    }

    $results = $wpdb->get_results($query);
    
    $max_no = 1;
    foreach ($results as $row) {
        if (preg_match($config_pattern, $row->option_name, $matches)) {
            $max_no = max($max_no, intval($matches[1]));
        } elseif (preg_match($img_pattern, $row->option_name, $matches)) {
            $max_no = max($max_no, intval($matches[1]));
        }
    }
    
    $new_no = $max_no + 1;
    
    // Initialize with default config
    if ($person_type === 'teacher' && $new_no == 1) {
        $default_config = [
            'design_name' => '', 'design_type' => 'front', 'orientation' => 'portrait', 'image_shape' => 'rounded',
            'image_border_size' => '', 'image_border_color' => '#000000', 'image_margin_top' => '75',
            'show_logo' => false, 'show_inst_name' => true, 'show_image' => true, 'show_name' => true, 'show_id' => false,
            'show_class_section' => true,
            'show_class' => true, 'show_section' => false, 'show_roll' => true, 'show_year' => true, 'show_dob' => true,
            'show_blood' => true, 'show_phone' => false, 'show_father' => false, 'show_mother' => false,
            'show_address' => false, 'show_inst_address' => false, 'show_inst_phone' => false, 'show_signature' => true
        ];
    } elseif ($person_type !== 'teacher' && $new_no == 1) {
        $default_config = [
            'design_name' => 'Standard Potrait', 'design_type' => 'front', 'orientation' => 'portrait', 'image_shape' => 'rounded',
            'image_border_size' => '4', 'image_border_color' => '#0078b7', 'image_margin_top' => '75',
            'show_logo' => false, 'show_inst_name' => false, 'show_image' => true, 'show_name' => true, 'show_id' => true,
            'show_class_section' => true,
            'show_class' => true, 'show_section' => true, 'show_roll' => false, 'show_year' => true, 'show_dob' => true,
            'show_blood' => false, 'show_phone' => true, 'show_father' => false, 'show_mother' => false,
            'show_address' => false, 'show_signature' => true
        ];
    } elseif ($person_type !== 'teacher' && $new_no == 2) {
        $default_config = [
            'design_name' => 'Standard Potrait', 'design_type' => 'back', 'orientation' => 'portrait', 'image_shape' => 'square',
            'image_border_size' => '', 'image_border_color' => '#000000', 'image_margin_top' => '',
            'show_logo' => true, 'show_inst_name' => true, 'show_image' => false, 'show_name' => false, 'show_id' => false,
            'show_class_section' => true,
            'show_class' => false, 'show_section' => false, 'show_roll' => false, 'show_year' => false, 'show_dob' => true,
            'show_blood' => true, 'show_phone' => true, 'show_father' => false, 'show_mother' => false,
            'show_address' => true, 'show_inst_address' => true, 'show_inst_phone' => true, 'show_signature' => true
        ];
    } else {
        $default_config = [
            'design_name' => 'Design ' . $new_no,
            'show_image' => true, 'show_name' => true, 'show_id' => true, 'show_class' => true,
            'show_class_section' => true,
            'show_section' => ($person_type !== 'teacher'), 'show_roll' => true, 'show_year' => true, 'show_dob' => true,
            'show_blood' => true, 'show_phone' => true, 'show_father' => true, 'show_mother' => true,
            'show_address' => true, 'show_logo' => true, 'show_inst_name' => true, 'show_signature' => true,
            'image_shape' => 'square', 'image_border_size' => 0, 'image_border_color' => '#000000', 'image_margin_top' => 75, 'orientation' => 'portrait'
        ];
    }
    
    $option_name = ($person_type === 'teacher') ? 'id_card_teacher_design_' . $new_no . '_config' : 'id_card_design_' . $new_no . '_config';

    $wpdb->insert('sm_options', [
        'option_name' => $option_name,
        'option_value' => json_encode($default_config),
        'autoload' => 'no'
    ]);
    
    ob_end_clean();
    echo json_encode(['success' => true, 'new_design_no' => $new_no]);
    exit;
}

/*
    Get ID Card Backgrounds
*/
elseif (isset($_POST['action']) && $_POST['action'] === 'get_id_card_backgrounds') {
    header('Content-Type: application/json');
    global $wpdb;
    $design_no = intval($_POST['design_no']);
    $person_type = isset($_POST['person_type']) ? sanitize_text_field($_POST['person_type']) : 'student';

    $prefix = ($person_type === 'teacher') ? 'id-teacher-design-' : 'id-design-';
    
    $front = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM sm_options WHERE option_name = %s", $prefix . $design_no));
    $back = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM sm_options WHERE option_name = %s", $prefix . 'back-' . $design_no));
    
    echo json_encode(['success' => true, 'data' => [
        'front' => [$design_no => $front],
        'back' => [$design_no => $back]
    ]]);
    exit;
}