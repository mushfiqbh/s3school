<?php

/*
 * Template Name: Update Redux Options
 */

// Ensure only logged-in users can access this page
if (!is_user_logged_in()) {
    wp_redirect(home_url());
    exit;
}

// Check if the user has the necessary capability to modify theme options
if (wp_get_current_user()->roles[0] == 'um_headmaster' || current_user_can('administrator')) {

    $layout_editor_sections = array(
        'teachers' => array(
            'label' => 'Teachers Section',
            'description' => 'Toggle the teacher highlight that showcases faculty profiles on the homepage.'
        ),
        'committees' => array(
            'label' => 'Committees',
            'description' => 'Controls the governing committee timeline and cards.'
        ),
        'gallery' => array(
            'label' => 'Gallery',
            'description' => 'Show or hide the photo gallery section.'
        ),
        'classwise_students' => array(
            'label' => 'Classwise Students',
            'description' => 'Displays the class-by-class student counts widget.'
        ),
        'student_demographics' => array(
            'label' => 'Student Demographics',
            'description' => 'Enables the charts for gender and religion distribution.'
        )
    );

    // AJAX handler for getting gender distribution from database
    if (isset($_POST['action']) && $_POST['action'] === 'get_gender_distribution_from_db') {
        global $wpdb;

        try {
            // Query to get gender distribution from ct_student table
            // stdGender: 0=Girl, 1=Boy, 2=Other
            $query = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN stdGender = '1' THEN 1 ELSE 0 END) as boys,
                SUM(CASE WHEN stdGender = '0' THEN 1 ELSE 0 END) as girls,
                SUM(CASE WHEN stdGender = '2' THEN 1 ELSE 0 END) as other
            FROM ct_student
            WHERE stdStatus = 1";

            $result = $wpdb->get_row($query);

            if ($wpdb->last_error) {
                error_log('Gender query error: ' . $wpdb->last_error);
                wp_send_json_error('Database query error: ' . $wpdb->last_error);
                exit;
            }

            $gender_data = array(
                'total' => intval($result->total ?? 0),
                'boys' => intval($result->boys ?? 0),
                'girls' => intval($result->girls ?? 0),
                'other' => intval($result->other ?? 0)
            );

            error_log('Gender data fetched: ' . json_encode($gender_data));
            wp_send_json_success($gender_data);
            exit;
        } catch (Exception $e) {
            error_log('Gender fetch exception: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
            exit;
        }
    }

    // AJAX handler for getting religion distribution from database
    if (isset($_POST['action']) && $_POST['action'] === 'get_religion_distribution_from_db') {
        global $wpdb;

        try {
            // Query to get religion distribution from ct_student table
            // Religion values: Muslim, Hinduism, Buddist, Christian, other
            $query = "SELECT 
                stdReligion,
                COUNT(*) as count
            FROM ct_student
            WHERE stdStatus = 1
            GROUP BY stdReligion";

            $results = $wpdb->get_results($query);

            if ($wpdb->last_error) {
                error_log('Religion query error: ' . $wpdb->last_error);
                wp_send_json_error('Database query error: ' . $wpdb->last_error);
                exit;
            }

            $religion_data = array(
                'Muslim' => 0,
                'Hinduism' => 0,
                'Buddist' => 0,
                'Christian' => 0,
                'other' => 0
            );

            if ($results) {
                foreach ($results as $row) {
                    $religion = trim($row->stdReligion);
                    $count = intval($row->count);

                    // Map database values to form field names
                    if (isset($religion_data[$religion])) {
                        $religion_data[$religion] = $count;
                    } else {
                        // Any other value goes to "Other"
                        $religion_data['other'] += $count;
                    }
                }
            }

            error_log('Religion data fetched: ' . json_encode($religion_data));
            wp_send_json_success($religion_data);
            exit;
        } catch (Exception $e) {
            error_log('Religion fetch exception: ' . $e->getMessage());
            wp_send_json_error($e->getMessage());
            exit;
        }
    }

    // Helper function to convert relative path to full URL for display
    function get_image_url($url)
    {
        if (empty($url)) {
            return '';
        }

        // If URL is already a full URL (starts with http), return as is
        if (strpos($url, 'http') === 0) {
            return $url;
        }

        // If it's a relative path, prepend home_url()
        return home_url($url);
    }

    //  $redux_options = get_option('opt_name', array()); // Ensure it's an array
    // Handle form submission
    if (isset($_POST['save_options']) && isset($_POST['opt_name'])) {
        // Debug logging (you can remove this in production)
        error_log('Form submission detected with opt_name data');


        //   print_r($_POST);exit;
        //  print_r(kses_post($_POST['opt_name']['aboutUsText']));exit;
        // Get the Redux options or set it as an empty array if it doesn't exist
        $redux_options = get_option('opt_name', array());  // Ensure it's an array

        // Remove slashes added by WordPress for safe HTML/text handling
        $_POST['opt_name'] = wp_unslash($_POST['opt_name']);

        try {

            // Update text-based Redux options with sanitized input
            $redux_options['institute_name'] = sanitize_text_field($_POST['opt_name']['institute_name']);
            $redux_options['inst_head_title'] = sanitize_text_field($_POST['opt_name']['inst_head_title']);
            $redux_options['inst_head_name'] = sanitize_text_field($_POST['opt_name']['inst_head_name']);
            $redux_options['institute_address'] = sanitize_text_field($_POST['opt_name']['institute_address']);
            $redux_options['institute_email'] = sanitize_email($_POST['opt_name']['institute_email']);
            $redux_options['institute_phone'] = sanitize_text_field($_POST['opt_name']['institute_phone']);
            $redux_options['institute_eiin'] = sanitize_text_field($_POST['opt_name']['institute_eiin']);
            $redux_options['institute_code'] = sanitize_text_field($_POST['opt_name']['institute_code']);
            $redux_options['center_code'] = sanitize_text_field($_POST['opt_name']['center_code']);
            $redux_options['estd_year'] = sanitize_text_field($_POST['opt_name']['estd_year']);
            $redux_options['mcqtitle'] = sanitize_text_field($_POST['opt_name']['mcqtitle']);
            $redux_options['cqtitle'] = sanitize_text_field($_POST['opt_name']['cqtitle']);
            $redux_options['prctitle'] = sanitize_text_field($_POST['opt_name']['prctitle']);
            $redux_options['catitle'] = sanitize_text_field($_POST['opt_name']['catitle']);
            $redux_options['footerAddress'] = $_POST['opt_name']['footerAddress'];
            $redux_options['footerContact'] = $_POST['opt_name']['footerContact'];
            $redux_options['admitCareNote'] = wp_kses_post($_POST['opt_name']['admitCareNote']);
            $redux_options['board_name_1'] = sanitize_text_field($_POST['opt_name']['board_name_1']);
            $redux_options['board_name_2'] = sanitize_text_field($_POST['opt_name']['board_name_2']);
            $redux_options['testimonial_prepared_by'] = sanitize_text_field($_POST['opt_name']['testimonial_prepared_by']);

            $redux_options['stdid'] = sanitize_text_field($_POST['opt_name']['stdid']);
            $redux_options['stdidpref'] = sanitize_text_field($_POST['opt_name']['stdidpref']);
            $redux_options['header_email'] = sanitize_text_field($_POST['opt_name']['header_email']);
            $redux_options['header_phone'] = sanitize_text_field($_POST['opt_name']['header_phone']);
            $redux_options['logo_width'] = sanitize_text_field($_POST['opt_name']['logo_width']);


            $redux_options['footerFbUrl'] = sanitize_text_field($_POST['opt_name']['footerFbUrl']);
            $redux_options['footerTwtUrl'] = sanitize_text_field($_POST['opt_name']['footerTwtUrl']);
            $redux_options['footerGglUrl'] = sanitize_text_field($_POST['opt_name']['footerGglUrl']);
            $redux_options['copyrightText'] = sanitize_text_field($_POST['opt_name']['copyrightText']);

            $redux_options['aboutTitelText'] = sanitize_text_field($_POST['opt_name']['aboutTitelText']);
            $redux_options['aboutUsText'] = wp_kses_post($_POST['opt_name']['aboutUsText']);
            $redux_options['aboutUsTextLimit'] = sanitize_text_field($_POST['opt_name']['aboutUsTextLimit']);
            $redux_options['aboutUsMoreBtn'] = sanitize_text_field($_POST['opt_name']['aboutUsMoreBtn']);

            $redux_options['headmasterSpeechTitle'] = sanitize_text_field($_POST['opt_name']['headmasterSpeechTitle'] ?? '');
            $redux_options['homeHeadmasterTitle'] = sanitize_text_field($_POST['opt_name']['homeHeadmasterTitle'] ?? '');
            $redux_options['homeHeadmaster'] = wp_kses_post($_POST['opt_name']['homeHeadmaster'] ?? '');
            $redux_options['headmasterTextLimit'] = sanitize_text_field($_POST['opt_name']['headmasterTextLimit'] ?? '');
            $redux_options['headmasterMoreBtn'] = sanitize_text_field($_POST['opt_name']['headmasterMoreBtn'] ?? '');

            $redux_options['chairmanSpeechTitle'] = sanitize_text_field($_POST['opt_name']['chairmanSpeechTitle'] ?? '');
            $redux_options['homeChairmanTitle'] = sanitize_text_field($_POST['opt_name']['homeChairmanTitle'] ?? '');
            $redux_options['homeChairman'] = wp_kses_post($_POST['opt_name']['homeChairman'] ?? '');
            $redux_options['chairmanTextLimit'] = sanitize_text_field($_POST['opt_name']['chairmanTextLimit'] ?? '');
            $redux_options['chairmanMoreBtn'] = sanitize_text_field($_POST['opt_name']['chairmanMoreBtn'] ?? '');

            $allowed_testimonial_types = ['Default', 'Pad'];
            $submitted_testimonial_type = sanitize_text_field($_POST['opt_name']['testimonial_type'] ?? 'Default');
            if (!in_array($submitted_testimonial_type, $allowed_testimonial_types, true)) {
                $submitted_testimonial_type = 'Default';
            }
            $redux_options['testimonial_type'] = $submitted_testimonial_type;

            // Save statistics to Redux options
            $redux_options['totalClasses'] = intval($_POST['opt_name']['totalClasses'] ?? 0);
            $redux_options['totalStudents'] = intval($_POST['opt_name']['totalStudents'] ?? 0);
            $redux_options['totalTeachers'] = intval($_POST['opt_name']['totalTeachers'] ?? 0);
            $redux_options['totalStaffs'] = intval($_POST['opt_name']['totalStaffs'] ?? 0);

            //         // Handle image uploads (URLs converted to relative paths)
            if (!empty($_POST['opt_name']['home_about_img'])) {
                $redux_options['home_about_img'] = [
                    'url' => esc_url_raw($_POST['opt_name']['home_about_img']['url']),
                    'id' => sanitize_text_field($_POST['opt_name']['home_about_img']['id'] ?? ''),
                    'height' => sanitize_text_field($_POST['opt_name']['home_about_img']['height'] ?? ''),
                    'width' => sanitize_text_field($_POST['opt_name']['home_about_img']['width'] ?? ''),
                    'thumbnail' => esc_url_raw($_POST['opt_name']['home_about_img']['thumbnail'] ?? ''),
                ];
            }
            if (!empty($_POST['opt_name']['homeHeadmasterImg'])) {
                $redux_options['homeHeadmasterImg'] = [
                    'url' => esc_url_raw($_POST['opt_name']['homeHeadmasterImg']['url']),
                    'id' => sanitize_text_field($_POST['opt_name']['homeHeadmasterImg']['id'] ?? ''),
                    'height' => sanitize_text_field($_POST['opt_name']['homeHeadmasterImg']['height'] ?? ''),
                    'width' => sanitize_text_field($_POST['opt_name']['homeHeadmasterImg']['width'] ?? ''),
                    'thumbnail' => esc_url_raw($_POST['opt_name']['homeHeadmasterImg']['thumbnail'] ?? ''),
                ];
            }
            if (!empty($_POST['opt_name']['homeChairmanImg'])) {
                $redux_options['homeChairmanImg'] = [
                    'url' => esc_url_raw($_POST['opt_name']['homeChairmanImg']['url']),
                    'id' => sanitize_text_field($_POST['opt_name']['homeChairmanImg']['id'] ?? ''),
                    'height' => sanitize_text_field($_POST['opt_name']['homeChairmanImg']['height'] ?? ''),
                    'width' => sanitize_text_field($_POST['opt_name']['homeChairmanImg']['width'] ?? ''),
                    'thumbnail' => esc_url_raw($_POST['opt_name']['homeChairmanImg']['thumbnail'] ?? ''),
                ];
            }
            if (!empty($_POST['opt_name']['principalSign'])) {
                $redux_options['principalSign'] = [
                    'url' => esc_url_raw($_POST['opt_name']['principalSign']['url']),
                    'id' => sanitize_text_field($_POST['opt_name']['principalSign']['id'] ?? ''),
                    'height' => sanitize_text_field($_POST['opt_name']['principalSign']['height'] ?? ''),
                    'width' => sanitize_text_field($_POST['opt_name']['principalSign']['width'] ?? ''),
                    'thumbnail' => esc_url_raw($_POST['opt_name']['principalSign']['thumbnail'] ?? ''),
                ];
            }

            if (!empty($_POST['opt_name']['instLogo'])) {
                $redux_options['instLogo'] = [
                    'url' => esc_url_raw($_POST['opt_name']['instLogo']['url']),
                    'id' => sanitize_text_field($_POST['opt_name']['instLogo']['id'] ?? ''),
                    'height' => sanitize_text_field($_POST['opt_name']['instLogo']['height'] ?? ''),
                    'width' => sanitize_text_field($_POST['opt_name']['instLogo']['width'] ?? ''),
                    'thumbnail' => esc_url_raw($_POST['opt_name']['instLogo']['thumbnail'] ?? ''),
                ];
            }

            if (!empty($_POST['opt_name']['barcode'])) {
                $redux_options['barcode'] = [
                    'url' => esc_url_raw($_POST['opt_name']['barcode']['url']),
                    'id' => sanitize_text_field($_POST['opt_name']['barcode']['id'] ?? ''),
                    'height' => sanitize_text_field($_POST['opt_name']['barcode']['height'] ?? ''),
                    'width' => sanitize_text_field($_POST['opt_name']['barcode']['width'] ?? ''),
                    'thumbnail' => esc_url_raw($_POST['opt_name']['barcode']['thumbnail'] ?? ''),
                ];
            }
            if (!empty($_POST['opt_name']['logo_upload'])) {
                $redux_options['logo_upload'] = [
                    'url' => esc_url_raw($_POST['opt_name']['logo_upload']['url']),
                    'id' => sanitize_text_field($_POST['opt_name']['logo_upload']['id'] ?? ''),
                    'height' => sanitize_text_field($_POST['opt_name']['logo_upload']['height'] ?? ''),
                    'width' => sanitize_text_field($_POST['opt_name']['logo_upload']['width'] ?? ''),
                    'thumbnail' => esc_url_raw($_POST['opt_name']['logo_upload']['thumbnail'] ?? ''),
                ];
            }

            $existing_pad = esc_url_raw($_POST['opt_name']['current_testimonial_pad'] ?? '');
            $testimonial_pad_url = $existing_pad;

            if (!empty($_FILES['testimonial_pad']['name'])) {
                if (!function_exists('wp_handle_upload')) {
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                }

                $upload_overrides = ['test_form' => false];
                $uploaded_file = wp_handle_upload($_FILES['testimonial_pad'], $upload_overrides);

                if (!isset($uploaded_file['error']) && !empty($uploaded_file['url'])) {
                    $testimonial_pad_url = esc_url_raw($uploaded_file['url']);
                } else {
                    if (isset($uploaded_file['error'])) {
                        error_log('Testimonial pad upload error: ' . $uploaded_file['error']);
                    }
                }
            }

            $redux_options['testimonial_pad'] = $testimonial_pad_url;

            // Save all options to sm_options table
            global $wpdb;
            $sm_table = 'sm_options';
            // Check if table exists
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$sm_table'");
            if ($table_exists === $sm_table) {
                // Helper function to save individual options
                $save_option = function ($option_name, $option_value) use ($wpdb, $sm_table) {
                    $wpdb->replace(
                        $sm_table,
                        [
                            'option_name'  => $option_name,
                            'option_value' => $option_value,
                            'autoload'     => 'yes',
                        ],
                        ['%s', '%s', '%s']
                    );
                };

                // Save all text-based options
                $text_options = [
                    'institute_name' => sanitize_text_field($_POST['opt_name']['institute_name'] ?? ''),
                    'inst_head_title' => sanitize_text_field($_POST['opt_name']['inst_head_title'] ?? ''),
                    'inst_head_name' => sanitize_text_field($_POST['opt_name']['inst_head_name'] ?? ''),
                    'institute_address' => sanitize_text_field($_POST['opt_name']['institute_address'] ?? ''),
                    'institute_email' => sanitize_email($_POST['opt_name']['institute_email'] ?? ''),
                    'institute_phone'   => sanitize_text_field($_POST['opt_name']['institute_phone'] ?? ''),
                    'institute_eiin'    => sanitize_text_field($_POST['opt_name']['institute_eiin'] ?? ''),
                    'institute_code'    => sanitize_text_field($_POST['opt_name']['institute_code'] ?? ''),
                    'center_code'       => sanitize_text_field($_POST['opt_name']['center_code'] ?? ''),
                    'estd_year'         => sanitize_text_field($_POST['opt_name']['estd_year'] ?? ''),
                    'board_name_1' => sanitize_text_field($_POST['opt_name']['board_name_1'] ?? ''),
                    'board_name_2' => sanitize_text_field($_POST['opt_name']['board_name_2'] ?? ''),
                    'testimonial_prepared_by' => sanitize_text_field($_POST['opt_name']['testimonial_prepared_by'] ?? ''),
                    'mcqtitle' => sanitize_text_field($_POST['opt_name']['mcqtitle'] ?? ''),
                    'cqtitle' => sanitize_text_field($_POST['opt_name']['cqtitle'] ?? ''),
                    'prctitle' => sanitize_text_field($_POST['opt_name']['prctitle'] ?? ''),
                    'catitle' => sanitize_text_field($_POST['opt_name']['catitle'] ?? ''),
                    'stdid' => sanitize_text_field($_POST['opt_name']['stdid'] ?? ''),
                    'stdidpref' => sanitize_text_field($_POST['opt_name']['stdidpref'] ?? ''),
                    'header_email' => sanitize_text_field($_POST['opt_name']['header_email'] ?? ''),
                    'header_phone' => sanitize_text_field($_POST['opt_name']['header_phone'] ?? ''),
                    'logo_width' => sanitize_text_field($_POST['opt_name']['logo_width'] ?? ''),
                    'footerFbUrl' => sanitize_text_field($_POST['opt_name']['footerFbUrl'] ?? ''),
                    'footerTwtUrl' => sanitize_text_field($_POST['opt_name']['footerTwtUrl'] ?? ''),
                    'footerGglUrl' => sanitize_text_field($_POST['opt_name']['footerGglUrl'] ?? ''),
                    'copyrightText' => sanitize_text_field($_POST['opt_name']['copyrightText'] ?? ''),
                    'aboutTitelText' => sanitize_text_field($_POST['opt_name']['aboutTitelText'] ?? ''),
                    'aboutUsTextLimit' => sanitize_text_field($_POST['opt_name']['aboutUsTextLimit'] ?? ''),
                    'aboutUsMoreBtn' => sanitize_text_field($_POST['opt_name']['aboutUsMoreBtn'] ?? ''),
                    '
                    ' => sanitize_text_field($_POST['opt_name']['headmasterSpeechTitle'] ?? ''),
                    'homeHeadmasterTitle' => sanitize_text_field($_POST['opt_name']['homeHeadmasterTitle'] ?? ''),
                    'headmasterTextLimit' => sanitize_text_field($_POST['opt_name']['headmasterTextLimit'] ?? ''),
                    'headmasterMoreBtn' => sanitize_text_field($_POST['opt_name']['headmasterMoreBtn'] ?? ''),
                    'chairmanSpeechTitle' => sanitize_text_field($_POST['opt_name']['chairmanSpeechTitle'] ?? ''),
                    'homeChairmanTitle' => sanitize_text_field($_POST['opt_name']['homeChairmanTitle'] ?? ''),
                    'chairmanTextLimit' => sanitize_text_field($_POST['opt_name']['chairmanTextLimit'] ?? ''),
                    'chairmanMoreBtn' => sanitize_text_field($_POST['opt_name']['chairmanMoreBtn'] ?? ''),
                    'testimonial_type' => $submitted_testimonial_type,
                    'totalClasses'      => intval($_POST['opt_name']['totalClasses'] ?? 0),
                    'totalStudents'     => intval($_POST['opt_name']['totalStudents'] ?? 0),
                    'totalTeachers'     => intval($_POST['opt_name']['totalTeachers'] ?? 0),
                    'totalStaffs'       => intval($_POST['opt_name']['totalStaffs'] ?? 0),
                ];

                foreach ($text_options as $opt_name => $opt_value) {
                    $save_option($opt_name, $opt_value);
                    // Keep $redux_options updated for immediate display
                    $redux_options[$opt_name] = $opt_value;
                }

                // Save HTML content options (with wp_kses_post)
                $html_options = [
                    'footerAddress' => $_POST['opt_name']['footerAddress'] ?? '',
                    'footerContact' => $_POST['opt_name']['footerContact'] ?? '',
                    'admitCareNote' => wp_kses_post($_POST['opt_name']['admitCareNote'] ?? ''),
                    'aboutUsText' => wp_kses_post($_POST['opt_name']['aboutUsText'] ?? ''),
                    'homeHeadmaster' => wp_kses_post($_POST['opt_name']['homeHeadmaster'] ?? ''),
                    'homeChairman' => wp_kses_post($_POST['opt_name']['homeChairman'] ?? ''),
                ];

                foreach ($html_options as $opt_name => $opt_value) {
                    $save_option($opt_name, $opt_value);
                    // Keep $redux_options updated for immediate display
                    $redux_options[$opt_name] = $opt_value;
                }

                // Save image/media options as URL text (not JSON)
                $media_fields = ['home_about_img', 'homeHeadmasterImg', 'homeChairmanImg', 'principalSign', 'instLogo', 'barcode', 'logo_upload'];

                foreach ($media_fields as $field) {
                    if (!empty($_POST['opt_name'][$field]['url'])) {
                        // Save only the URL as plain text to database
                        $image_url = esc_url_raw($_POST['opt_name'][$field]['url']);
                        $save_option($field, $image_url);

                        // Keep $redux_options updated with full array for backward compatibility
                        $redux_options[$field] = [
                            'url' => $image_url,
                            'id' => sanitize_text_field($_POST['opt_name'][$field]['id'] ?? ''),
                            'height' => sanitize_text_field($_POST['opt_name'][$field]['height'] ?? ''),
                            'width' => sanitize_text_field($_POST['opt_name'][$field]['width'] ?? ''),
                            'thumbnail' => esc_url_raw($_POST['opt_name'][$field]['thumbnail'] ?? ''),
                        ];
                    }
                }

                $save_option('testimonial_pad', $redux_options['testimonial_pad']);

                // Handle Class-wise Students Count save
                if (isset($_POST['class_wise_students']) && is_array($_POST['class_wise_students'])) {
                    $class_data = array();
                    foreach ($_POST['class_wise_students'] as $class_key => $count) {
                        $class_data[sanitize_key($class_key)] = intval($count);
                    }
                    $class_json = json_encode($class_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                    // Save as JSON string to database
                    $save_option('class_wise_students', $class_json);
                    // Keep $redux_options updated with array (not JSON) for immediate display
                    $redux_options['class_wise_students'] = $class_data;
                    error_log('Saved class_wise_students: ' . $class_json);
                }

                // Handle Student Demographics save (Gender & Religion)
                if (isset($_POST['student_demographics']) && is_array($_POST['student_demographics'])) {
                    $demographics = array();
                    foreach ($_POST['student_demographics'] as $demo_key => $value) {
                        $demographics[sanitize_key($demo_key)] = intval($value);
                    }

                    $demographics_json = json_encode($demographics, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                    // Save as JSON string to database
                    $save_option('student_demographics', $demographics_json);
                    // Keep $redux_options updated with array (not JSON) for immediate display
                    $redux_options['student_demographics'] = $demographics;
                    // Also expand demographics into individual fields for form display
                    $redux_options['demographics_total_students'] = $demographics['total_students'] ?? 0;
                    $redux_options['demographics_boys'] = $demographics['boys'] ?? 0;
                    $redux_options['demographics_girls'] = $demographics['girls'] ?? 0;
                    $redux_options['demographics_gender_other'] = $demographics['gender_other'] ?? 0;
                    $redux_options['demographics_muslim'] = $demographics['muslim'] ?? 0;
                    $redux_options['demographics_hinduism'] = $demographics['hinduism'] ?? 0;
                    $redux_options['demographics_buddhist'] = $demographics['buddhist'] ?? 0;
                    $redux_options['demographics_christian'] = $demographics['christian'] ?? 0;
                    $redux_options['demographics_other'] = $demographics['other'] ?? 0;
                    error_log('Saved student_demographics: ' . $demographics_json);
                }

                // Handle layout visibility save
                if (isset($layout_editor_sections) && is_array($layout_editor_sections)) {
                    $layout_visibility = array();
                    foreach ($layout_editor_sections as $section_key => $section_config) {
                        $layout_visibility[$section_key] = isset($_POST['layout_visibility'][$section_key]) ? 1 : 0;
                    }

                    $layout_visibility_json = json_encode($layout_visibility, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $save_option('layout_visibility', $layout_visibility_json);
                    $redux_options['layout_visibility'] = $layout_visibility;
                    error_log('Saved layout_visibility: ' . $layout_visibility_json);
                }

                // Handle Important Links save
                if (isset($_POST['important_link_title']) && isset($_POST['important_link_url'])) {
                    $links = [];
                    foreach ($_POST['important_link_title'] as $index => $title) {
                        if (!empty($title) && !empty($_POST['important_link_url'][$index])) {
                            $links[] = [
                                'title' => sanitize_text_field($title),
                                'url' => esc_url_raw($_POST['important_link_url'][$index])
                            ];
                        }
                    }
                    $json_data = json_encode($links, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                    // Save as JSON string to database
                    $save_option('important_links', $json_data);
                    // Keep $redux_options updated with array (not JSON) for immediate display
                    $redux_options['important_links'] = $links;
                    error_log('Saved important_links: ' . $json_data);
                }
            }

            // Save all options to Redux for backward compatibility
            update_option('opt_name', $redux_options);

            // Note: All options are now saved to both sm_options table AND Redux options
            // Redux compatibility maintained through $redux_options variable for form display

            echo '<div class="notice notice-success is-dismissible"><p><strong>Success!</strong> Theme options have been updated successfully.</p></div>';
        } catch (Exception $e) {
            error_log('Error updating theme options: ' . $e->getMessage());
            echo '<div class="notice notice-error is-dismissible"><p><strong>Error:</strong> Failed to update theme options: ' . esc_html($e->getMessage()) . '</p></div>';
        }
    } else {
        // Not a form submission, so fetch ALL options from sm_options table
        // Get the Redux options or set it as an empty array if it doesn't exist
        $redux_options = array();

        global $wpdb;
        $sm_table = 'sm_options';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$sm_table'");

        if ($table_exists === $sm_table) {
            // Load all options from sm_options table
            $all_options = $wpdb->get_results("SELECT option_name, option_value FROM $sm_table", ARRAY_A);

            // Define media fields that are stored as URL strings
            $media_fields = ['home_about_img', 'homeHeadmasterImg', 'homeChairmanImg', 'principalSign', 'instLogo', 'barcode', 'logo_upload'];

            foreach ($all_options as $option) {
                $option_name = $option['option_name'];
                $option_value = $option['option_value'];

                // Check if this is a media field (stored as URL text)
                if (in_array($option_name, $media_fields)) {
                    // Only convert to array if value is not empty
                    if (!empty($option_value)) {
                        // Convert URL string to array format for backward compatibility
                        $redux_options[$option_name] = [
                            'url' => $option_value,
                            'id' => '',
                            'height' => '',
                            'width' => '',
                            'thumbnail' => '',
                        ];
                    } else {
                        // Keep as empty string or use default value
                        $redux_options[$option_name] = $option_value;
                    }
                } else {
                    // Check if value is JSON (for demographics, class-wise, links, etc.)
                    $decoded = json_decode($option_value, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        $redux_options[$option_name] = $decoded;
                    } else {
                        $redux_options[$option_name] = $option_value;
                    }
                }
            }

            // Load and expand student demographics data into individual fields
            if (isset($redux_options['student_demographics']) && is_array($redux_options['student_demographics'])) {
                $demographics_data = $redux_options['student_demographics'];
                $redux_options['demographics_total_students'] = $demographics_data['total_students'] ?? 0;
                $redux_options['demographics_boys'] = $demographics_data['boys'] ?? 0;
                $redux_options['demographics_girls'] = $demographics_data['girls'] ?? 0;
                $redux_options['demographics_gender_other'] = $demographics_data['gender_other'] ?? 0;
                $redux_options['demographics_muslim'] = $demographics_data['muslim'] ?? 0;
                $redux_options['demographics_hinduism'] = $demographics_data['hinduism'] ?? 0;
                $redux_options['demographics_buddhist'] = $demographics_data['buddhist'] ?? 0;
                $redux_options['demographics_christian'] = $demographics_data['christian'] ?? 0;
                $redux_options['demographics_other'] = $demographics_data['other'] ?? 0;
            } else {
                // Initialize demographics with default values if no data exists
                $redux_options['demographics_total_students'] = 0;
                $redux_options['demographics_boys'] = 0;
                $redux_options['demographics_girls'] = 0;
                $redux_options['demographics_gender_other'] = 0;
                $redux_options['demographics_muslim'] = 0;
                $redux_options['demographics_hinduism'] = 0;
                $redux_options['demographics_buddhist'] = 0;
                $redux_options['demographics_christian'] = 0;
                $redux_options['demographics_other'] = 0;
            }
        }

        // Fallback to old Redux options if sm_options table doesn't exist or is empty
        if (empty($redux_options)) {
            $redux_options = get_option('opt_name', array());
        }

        // Ensure $redux_options is an array before trying to access its values
        if (!is_array($redux_options)) {
            $redux_options = array();
        }

        if (isset($layout_editor_sections) && is_array($layout_editor_sections)) {
            $layout_visibility_defaults = array();
            foreach ($layout_editor_sections as $section_key => $section_config) {
                $layout_visibility_defaults[$section_key] = 1;
            }

            if (!isset($redux_options['layout_visibility']) || !is_array($redux_options['layout_visibility'])) {
                $redux_options['layout_visibility'] = $layout_visibility_defaults;
            } else {
                foreach ($layout_visibility_defaults as $section_key => $default_value) {
                    $current_value = isset($redux_options['layout_visibility'][$section_key]) ? intval($redux_options['layout_visibility'][$section_key]) : $default_value;
                    $redux_options['layout_visibility'][$section_key] = $current_value ? 1 : 0;
                }
            }
        }
    }

    // AJAX Handler for fetching class-wise students from database
    if (isset($_POST['action']) && $_POST['action'] === 'get_class_students_from_db') {
        global $wpdb;

        // Query to get student counts per class from ct_class and ct_studentinfo tables
        $query = "SELECT 
            c.classid,
            c.className,
            c.classOrder,
            COUNT(s.infoStdid) AS totalStudents
        FROM ct_class c
        LEFT JOIN ct_studentinfo s 
            ON s.infoClass = c.classid
        GROUP BY c.classid, c.className, c.classOrder
        ORDER BY c.classOrder ASC, c.className ASC";

        $results = $wpdb->get_results($query);

        $class_data = array();
        if ($results) {
            foreach ($results as $row) {
                $class_name = trim((string)$row->className);
                if ($class_name !== '') {
                    $class_key = 'class_' . preg_replace('/\s+/', '_', strtolower($class_name));
                    $class_data[$class_key] = intval($row->totalStudents);
                }
            }
        }

        wp_send_json_success($class_data);
        exit;
    }

    //     $homeChairmanContent = $redux_options['homeChairman'] ?? '';
    // echo wpautop($homeChairmanContent);
    // echo '<pre>';print_r($redux_options);exit;
    // Ensure $redux_options is an array before trying to access its values
    if (!is_array($redux_options)) {
        $redux_options = array();
    }
    // require_once('wp-load.php');
    get_header();
    maxSchoolMngs_scripts();

?>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            background-color: #f4f7f6;
        }

        .tabs {
            display: flex;
            border-bottom: 2px solid #ddd;
        }

        .tab {
            padding: 15px 30px;
            cursor: pointer;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px 5px 0 0;
            margin-right: 5px;
            transition: background-color 0.3s ease;
        }

        .tab:hover {
            background-color: #f1f1f1;
            color: black;
        }

        .active {
            background-color: #007BFF;
            color: white;
            border-bottom: 2px solid #007BFF;
        }

        .tab-content {
            display: none;
            padding: 20px;
            border-top: 1px solid #ddd;
            background-color: #f7f7f7;
            /* Updated background color */
            border-radius: 0 0 5px 5px;
            /* Rounded corners at the bottom */
            box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.1);
            /* Adding a subtle shadow */
        }

        .tab-content.active {
            display: block;
            color: black;
        }

        /*.container {*/
        /*    width: 80%;*/
        /*    margin: 50px auto;*/
        /*}*/
        fieldset {
            width: 90%;
        }

        #image_barcode {
            width: 100px;
        }

        a img {
            width: 100px;
        }

        #image_logo_upload {
            width: 400px;
        }
    </style>



    <div class="row">
        <div class="col-md-12" style="width:100%;padding: 0;">
            <!-- Slick CSS -->
            <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.css" />
            <!-- Slick Theme (Optional) -->
            <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick-theme.min.css" />

            <!-- Slick JS -->
            <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
            <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/slick-carousel/1.8.1/slick.min.js"></script>


            <style>
                .frontend-slider {
                    width: 100%;
                    max-width: 1200px;
                    margin: 0 auto;
                    border-radius: 10px;
                    overflow: hidden;
                }

                .frontend-slider .slick-slide {
                    display: flex;
                    justify-content: center;
                    align-items: center;
                }

                .frontend-slider .slider-img {
                    width: 100%;
                    height: auto;
                    border-radius: 10px;
                    transition: transform 0.3s ease;
                }

                .frontend-slider .slick-prev,
                .frontend-slider .slick-next {
                    background-color: rgba(0, 0, 0, 0.5);
                    border-radius: 50%;
                    color: #fff;
                    z-index: 10;
                }

                .frontend-slider .slick-prev:hover,
                .frontend-slider .slick-next:hover {
                    background-color: rgba(0, 0, 0, 0.7);
                }

                .frontend-slider .slick-dots {
                    bottom: 10px;
                    z-index: 10;
                }

                .frontend-slider .slick-dots li button {
                    background-color: rgba(255, 255, 255, 0.5);
                }

                .frontend-slider .slick-dots li.slick-active button {
                    background-color: #fff;
                }
            </style>
            <!--[frontend_slider] -->
            <script>
                jQuery(document).ready(function($) {
                    $('.frontend-slider').slick({
                        autoplay: true, // Enable auto-sliding
                        autoplaySpeed: 3000, // Slide every 3 seconds
                        dots: true, // Show dots at the bottom of the slider
                        arrows: true, // Show navigation arrows
                        infinite: true, // Loop through slides
                        speed: 500, // Speed of the transition
                        slidesToShow: 1, // Show one slide at a time
                        slidesToScroll: 1, // Scroll one slide at a time
                        adaptiveHeight: true // Adjust height for images
                    });
                });
            </script>
            <div class="" style="margin:30px;">
                <form method="POST" enctype="multipart/form-data">

                    <div class="tabs">
                        <div class="tab active" onclick="openTab(event, 'homeslider')">Home Slider</div>
                        <div class="tab" onclick="openTab(event, 'tab1')">Institute Information</div>
                        <div class="tab" onclick="openTab(event, 'header')">Header</div>
                        <div class="tab" onclick="openTab(event, 'footer')">Footer</div>
                        <div class="tab" onclick="openTab(event, 'aboutus')">About Us</div>
                        <div class="tab" onclick="openTab(event, 'headmaster')">Headmaster Speech</div>
                        <div class="tab" onclick="openTab(event, 'chairman')">Chairman Speech</div>
                        <div class="tab" onclick="openTab(event, 'statistics')">Statistics & Links</div>
                        <div class="tab" onclick="openTab(event, 'classwise')">Classwise Student Count</div>
                        <div class="tab" onclick="openTab(event, 'demographics')">Student Demographics</div>
                    </div>

                    <div id="tab1" class="tab-content">
                        <h2>Institute Information</h2>
                        <table class="form-table" role="presentation" style="color:#000">
                            <tbody>
                                <tr>
                                    <th scope="row">
                                        <div class="redux_field_th">Institute Name</div>
                                    </th>
                                    <td>
                                        <fieldset id="opt_name-institute_name" class="redux-field-container redux-field redux-field-init redux-container-text" data-id="institute_name" data-type="text">
                                            <input type="text" id="institute_name" name="opt_name[institute_name]" value="<?php echo esc_attr($redux_options['institute_name'] ?? ''); ?>" class="regular-text">
                                        </fieldset>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <div class="redux_field_th">Head of Institute Title</div>
                                    </th>
                                    <td>
                                        <fieldset id="opt_name-inst_head_title" class="redux-field-container redux-field redux-field-init redux-container-text" data-id="inst_head_title" data-type="text">
                                            <input type="text" id="inst_head_title" name="opt_name[inst_head_title]" value="<?php echo esc_attr($redux_options['inst_head_title'] ?? 'Headmaster'); ?>" class="regular-text">
                                        </fieldset>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <div class="redux_field_th">Institute Head Name</div>
                                    </th>
                                    <td>
                                        <fieldset id="opt_name-inst_head_name" class="redux-field-container redux-field redux-field-init redux-container-text" data-id="inst_head_name" data-type="text">
                                            <input type="text" id="inst_head_name" name="opt_name[inst_head_name]" value="<?php echo esc_attr($redux_options['inst_head_name'] ?? ''); ?>" class="regular-text">
                                        </fieldset>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <div class="redux_field_th">Institute Address</div>
                                    </th>
                                    <td>
                                        <fieldset id="opt_name-institute_address" class="redux-field-container redux-field redux-field-init redux-container-text" data-id="institute_address" data-type="text">
                                            <input type="text" id="institute_address" name="opt_name[institute_address]" value="<?php echo esc_attr($redux_options['institute_address'] ?? ''); ?>" class="regular-text">
                                        </fieldset>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <div class="redux_field_th">Institute Email</div>
                                    </th>
                                    <td>
                                        <fieldset id="opt_name-institute_email" class="redux-field-container redux-field redux-field-init redux-container-text" data-id="institute_email" data-type="text">
                                            <input type="email" id="institute_email" name="opt_name[institute_email]" value="<?php echo esc_attr($redux_options['institute_email'] ?? ''); ?>" class="regular-text">
                                        </fieldset>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <div class="redux_field_th">Institute Phone</div>
                                    </th>
                                    <td>
                                        <fieldset id="opt_name-institute_phone" class="redux-field-container redux-field redux-field-init redux-container-text" data-id="institute_phone" data-type="text">
                                            <input type="text" id="institute_phone" name="opt_name[institute_phone]" value="<?php echo esc_attr($redux_options['institute_phone'] ?? ''); ?>" class="regular-text">
                                        </fieldset>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <div class="redux_field_th">Institute EIIN</div>
                                    </th>
                                    <td>
                                        <fieldset id="opt_name-institute_eiin" class="redux-field-container redux-field redux-field-init redux-container-text" data-id="institute_eiin" data-type="text">
                                            <input type="text" id="institute_eiin" name="opt_name[institute_eiin]" value="<?php echo esc_attr($redux_options['institute_eiin'] ?? ''); ?>" class="regular-text">
                                        </fieldset>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <div class="redux_field_th">Institute Code</div>
                                    </th>
                                    <td>
                                        <fieldset id="opt_name-institute_code" class="redux-field-container redux-field redux-field-init redux-container-text" data-id="institute_code" data-type="text">
                                            <input type="text" id="institute_code" name="opt_name[institute_code]" value="<?php echo esc_attr($redux_options['institute_code'] ?? ''); ?>" class="regular-text">
                                        </fieldset>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <div class="redux_field_th">Center Code</div>
                                    </th>
                                    <td>
                                        <fieldset id="opt_name-center_code" class="redux-field-container redux-field redux-field-init redux-container-text" data-id="center_code" data-type="text">
                                            <input type="text" id="center_code" name="opt_name[center_code]" value="<?php echo esc_attr($redux_options['center_code'] ?? ''); ?>" class="regular-text">
                                        </fieldset>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <div class="redux_field_th">ESTD Year</div>
                                    </th>
                                    <td>
                                        <fieldset id="opt_name-estd_year" class="redux-field-container redux-field redux-field-init redux-container-text" data-id="estd_year" data-type="text">
                                            <input type="text" id="estd_year" name="opt_name[estd_year]" value="<?php echo esc_attr($redux_options['estd_year'] ?? ''); ?>" class="regular-text">
                                        </fieldset>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <div class="redux_field_th">Board Name - 1</div>
                                    </th>
                                    <td>
                                        <fieldset id="opt_name-board_name_1" class="redux-field-container redux-field redux-field-init redux-container-text" data-id="board_name_1" data-type="text">
                                            <input type="text" id="board_name_1" name="opt_name[board_name_1]" value="<?php echo esc_attr($redux_options['board_name_1'] ?? ''); ?>" class="regular-text">
                                        </fieldset>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <div class="redux_field_th">Board Name - 2</div>
                                    </th>
                                    <td>
                                        <fieldset id="opt_name-board_name_2" class="redux-field-container redux-field redux-field-init redux-container-text" data-id="board_name_2" data-type="text">
                                            <input type="text" id="board_name_2" name="opt_name[board_name_2]" value="<?php echo esc_attr($redux_options['board_name_2'] ?? ''); ?>" class="regular-text">
                                        </fieldset>
                                    </td>
                                </tr>


                                <tr>
                                    <th scope="row">
                                        <div class="redux_field_th">Testimonial Prepared By</div>
                                    </th>
                                    <td>
                                        <fieldset id="opt_name-testimonial_prepared_by" class="redux-field-container redux-field redux-field-init redux-container-text" data-id="testimonial_prepared_by" data-type="text">
                                            <input type="text" id="testimonial_prepared_by" name="opt_name[testimonial_prepared_by]" value="<?php echo esc_attr($redux_options['testimonial_prepared_by'] ?? ''); ?>" class="regular-text">
                                        </fieldset>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <div class="redux_field_th">MCQ</div>
                                    </th>
                                    <td>
                                        <fieldset id="opt_name-mcqtitle" class="redux-field-container redux-field redux-field-init redux-container-text" data-id="mcqtitle" data-type="text">
                                            <input type="text" id="mcqtitle" name="opt_name[mcqtitle]" value="<?php echo esc_attr($redux_options['mcqtitle'] ?? 'MCQ'); ?>" class="regular-text">
                                        </fieldset>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <div class="redux_field_th">CQ</div>
                                    </th>
                                    <td>
                                        <fieldset id="opt_name-cqtitle" class="redux-field-container redux-field redux-field-init redux-container-text" data-id="cqtitle" data-type="text">
                                            <input type="text" id="cqtitle" name="opt_name[cqtitle]" value="<?php echo esc_attr($redux_options['cqtitle'] ?? 'CQ'); ?>" class="regular-text">
                                        </fieldset>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <div class="redux_field_th">Practical</div>
                                    </th>
                                    <td>
                                        <fieldset id="opt_name-prctitle" class="redux-field-container redux-field redux-field-init redux-container-text" data-id="prctitle" data-type="text">
                                            <input type="text" id="prctitle" name="opt_name[prctitle]" value="<?php echo esc_attr($redux_options['prctitle'] ?? 'PR'); ?>" class="regular-text">
                                        </fieldset>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <div class="redux_field_th">CA</div>
                                    </th>
                                    <td>
                                        <fieldset id="opt_name-catitle" class="redux-field-container redux-field redux-field-init redux-container-text" data-id="catitle" data-type="text">
                                            <input type="text" id="catitle" name="opt_name[catitle]" value="<?php echo esc_attr($redux_options['catitle'] ?? 'CA'); ?>" class="regular-text">
                                        </fieldset>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <div class="redux_field_th">Principal Sign<span class="description"></span></div>
                                    </th>
                                    <td>
                                        <fieldset id="opt_name-principalSign" class="redux-field-container redux-field redux-container-media" data-id="principalSign" data-type="media">
                                            <input placeholder="No media selected" type="text" class="upload large-text" name="opt_name[principalSign][url]" id="opt_name[principalSign][url]" value="<?php echo esc_attr($redux_options['principalSign']['url'] ?? get_site_url() . '/wp-content/uploads/2024/01/anwar-sir.png'); ?>" readonly="readonly">
                                            <input type="hidden" class="data" data-mode="image">
                                            <input type="hidden" class="library-filter" data-lib-filter="">
                                            <input type="hidden" class="upload-id" name="opt_name[principalSign][id]" id="opt_name[principalSign][id]" value="<?php echo esc_attr($redux_options['principalSign']['id'] ?? '4082'); ?>">
                                            <input type="hidden" class="upload-height" name="opt_name[principalSign][height]" id="opt_name[principalSign][height]" value="<?php echo esc_attr($redux_options['principalSign']['height'] ?? '80'); ?>">
                                            <input type="hidden" class="upload-width" name="opt_name[principalSign][width]" id="opt_name[principalSign][width]" value="<?php echo esc_attr($redux_options['principalSign']['width'] ?? '170'); ?>">
                                            <input type="hidden" class="upload-thumbnail" name="opt_name[principalSign][thumbnail]" id="opt_name[principalSign][thumbnail]" value="<?php echo esc_attr($redux_options['principalSign']['thumbnail'] ?? $redux_options['principalSign']['url'] ?? ''); ?>">
                                            <div class="screenshot">
                                                <a class="of-uploaded-image" href="<?php echo esc_url(get_image_url($redux_options['principalSign']['url'] ?? '') ?: get_site_url() . '/wp-content/uploads/2024/01/anwar-sir.png'); ?>" target="_blank">
                                                    <img class="redux-option-image" id="image_principalSign" src="<?php echo esc_url(get_image_url($redux_options['principalSign']['url'] ?? '') ?: get_site_url() . '/wp-content/uploads/2024/01/anwar-sir.png'); ?>" alt="" target="_blank" rel="external">
                                                </a>
                                            </div>
                                            <div class="upload_button_div">
                                                <span class="button media_upload_button" id="principalSign-media">Upload</span>
                                                <span class="button remove-image" id="reset_principalSign" rel="principalSign">Remove</span>
                                            </div>
                                        </fieldset>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <div class="redux_field_th">Barcode Or Logo for ID card<span class="description"></span></div>
                                    </th>
                                    <td>
                                        <fieldset id="opt_name-barcode" class="redux-field-container redux-field redux-container-media" data-id="barcode" data-type="media">
                                            <input placeholder="No media selected" type="text" class="upload large-text" name="opt_name[barcode][url]" id="opt_name[barcode][url]" value="<?php echo esc_attr($redux_options['barcode']['url'] ?? get_site_url() . '/wp-content/uploads/2024/01/logo.png'); ?>" readonly="readonly">
                                            <input type="hidden" class="data" data-mode="image">
                                            <input type="hidden" class="library-filter" data-lib-filter="">
                                            <input type="hidden" class="upload-id" name="opt_name[barcode][id]" id="opt_name[barcode][id]" value="<?php echo esc_attr($redux_options['barcode']['id'] ?? '4083'); ?>">
                                            <input type="hidden" class="upload-height" name="opt_name[barcode][height]" id="opt_name[barcode][height]" value="<?php echo esc_attr($redux_options['barcode']['height'] ?? '180'); ?>">
                                            <input type="hidden" class="upload-width" name="opt_name[barcode][width]" id="opt_name[barcode][width]" value="<?php echo esc_attr($redux_options['barcode']['width'] ?? '100'); ?>">
                                            <input type="hidden" class="upload-thumbnail" name="opt_name[barcode][thumbnail]" id="opt_name[barcode][thumbnail]" value="<?php echo esc_attr($redux_options['barcode']['thumbnail'] ?? $redux_options['barcode']['url'] ?? ''); ?>">
                                            <div class="screenshot">
                                                <a class="of-uploaded-image" href="<?php echo esc_url(get_image_url($redux_options['barcode']['url'] ?? '') ?: get_site_url() . '/wp-content/uploads/2024/01/logo.png'); ?>" target="_blank">
                                                    <img class="redux-option-image" id="image_barcode" src="<?php echo esc_url(get_image_url($redux_options['barcode']['url'] ?? '') ?: get_site_url() . '/wp-content/uploads/2024/01/logo.png'); ?>" alt="" target="_blank" rel="external">
                                                </a>
                                            </div>
                                            <div class="upload_button_div">
                                                <span class="button media_upload_button" id="barcode-media">Upload</span>
                                                <span class="button remove-image" id="reset_barcode" rel="barcode">Remove</span>
                                            </div>
                                        </fieldset>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <div class="redux_field_th">Institute logo for Admit/Seat card<span class="description"></span></div>
                                    </th>
                                    <td>
                                        <fieldset id="opt_name-instLogo" class="redux-field-container redux-field redux-container-media" data-id="instLogo" data-type="media">
                                            <input placeholder="No media selected" type="text" class="upload large-text" name="opt_name[instLogo][url]" id="opt_name[instLogo][url]" value="<?php echo esc_attr($redux_options['instLogo']['url'] ?? get_site_url() . '/wp-content/uploads/2024/01/logo.png'); ?>" readonly="readonly">
                                            <input type="hidden" class="data" data-mode="image">
                                            <input type="hidden" class="library-filter" data-lib-filter="">
                                            <input type="hidden" class="upload-id" name="opt_name[instLogo][id]" id="opt_name[instLogo][id]" value="<?php echo esc_attr($redux_options['instLogo']['id'] ?? ''); ?>">
                                            <input type="hidden" class="upload-height" name="opt_name[instLogo][height]" id="opt_name[instLogo][height]" value="<?php echo esc_attr($redux_options['instLogo']['height'] ?? '180'); ?>">
                                            <input type="hidden" class="upload-width" name="opt_name[instLogo][width]" id="opt_name[instLogo][width]" value="<?php echo esc_attr($redux_options['instLogo']['width'] ?? '180'); ?>">
                                            <input type="hidden" class="upload-thumbnail" name="opt_name[instLogo][thumbnail]" id="opt_name[instLogo][thumbnail]" value="<?php echo esc_attr($redux_options['instLogo']['thumbnail'] ?? $redux_options['instLogo']['url'] ?? ''); ?>">
                                            <div class="screenshot">
                                                <a class="of-uploaded-image" href="<?php echo esc_url(get_image_url($redux_options['instLogo']['url'] ?? '') ?: get_site_url() . '/wp-content/uploads/2024/01/logo.png'); ?>" target="_blank">
                                                    <img class="redux-option-image" id="image_instLogo" src="<?php echo esc_url(get_image_url($redux_options['instLogo']['url'] ?? '') ?: get_site_url() . '/wp-content/uploads/2024/01/logo.png'); ?>" alt="" target="_blank" rel="external">
                                                </a>
                                            </div>
                                            <div class="upload_button_div">
                                                <span class="button media_upload_button" id="instLogo-media">Upload</span>
                                                <span class="button remove-image" id="reset_instLogo" rel="instLogo">Remove</span>
                                            </div>
                                        </fieldset>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <div class="redux_field_th">Admit Card Note</div>
                                    </th>
                                    <td>

                                        <!--<fieldset id="opt_name-admitCareNote" class="redux-field-container redux-field redux-field-init redux-container-editor" data-id="admitCareNote" data-type="editor">-->
                                        <!--    <div class="wp-core-ui wp-editor-wrap">-->
                                        <!--        <link rel="stylesheet" id="editor-buttons-css" href="https://pakshailihs.edu.bd/wp-includes/css/editor.min.css?ver=5.3.18" media="all">-->


                                        <?php
                                        //         $editor_id = 'admitCareNote';
                                        //         $content = $redux_options['admitCareNote'] ?? '';
                                        // $settings = array(
                                        //     'textarea_name' => 'opt_name[admitCareNote]',
                                        //     'media_buttons' => true,
                                        //     'teeny'         => false,
                                        //     'quicktags'     => true,
                                        //     'editor_height' => 200,
                                        //     'wpautop'       => true
                                        // );

                                        // wp_editor($content, $editor_id, $settings);

                                        wp_editor(
                                            $redux_options['admitCareNote'] ?? '',
                                            'admitCareNote',
                                            array(
                                                'textarea_name' => 'opt_name[admitCareNote]',
                                                'media_buttons' => true,
                                                'textarea_rows' => 10,
                                                'tinymce' => true,  // Enables rich text editor
                                                'quicktags' => false,
                                                'wpautop' => true  // Automatically add <p> tags
                                            )
                                        );

                                        ?>
                                        <!--</div>-->
                                        <!--<div class="description field-desc">This note will show in footer of Admit Cards</div>-->
                                        </fieldset>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <div class="redux_field_th">Student ID Start After</div>
                                    </th>
                                    <td>
                                        <fieldset id="opt_name-stdid" class="redux-field-container redux-field redux-field-init redux-container-text" data-id="stdid" data-type="text">
                                            <input type="text" id="stdid" name="opt_name[stdid]" value="<?php echo esc_attr($redux_options['stdid'] ?? '1000'); ?>" class="regular-text">
                                        </fieldset>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <div class="redux_field_th">Student ID Prefix</div>
                                    </th>
                                    <td>
                                        <fieldset id="opt_name-stdidpref" class="redux-field-container redux-field redux-field-init redux-container-text" data-id="stdidpref" data-type="text">
                                            <input type="text" id="stdidpref" name="opt_name[stdidpref]" value="<?php echo esc_attr($redux_options['stdidpref'] ?? 'SPPM-'); ?>" class="regular-text">
                                            <div class="description field-desc">For adding the year just type year</div>
                                        </fieldset>
                                    </td>
                                </tr>


                            </tbody>
                        </table>
                    </div>


                    <div id="homeslider" class="tab-content active">
                        <h2>Home Slider</h2>
                        <?php
                        /** Template Name: Slider Image Upload */
                        global $wpdb;
                        $table_name = $wpdb->prefix . 'slider_images';

                        // Handle AJAX save manually
                        if (
                            isset($_POST['action']) &&
                            $_POST['action'] === 'save_slider_image' &&
                            !empty($_POST['image_url'])
                        ) {
                            header('Content-Type: application/json');
                            $image_url = esc_url_raw($_POST['image_url']);
                            
                            // Server-side file size validation (300KB limit)
                            $max_size = 300 * 1024; // 300KB in bytes
                            $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;
                            
                            if ($image_id) {
                                $file_path = get_attached_file($image_id);
                                if ($file_path && file_exists($file_path)) {
                                    $file_size = filesize($file_path);
                                    if ($file_size > $max_size) {
                                        echo json_encode([
                                            'success' => false, 
                                            'message' => 'Image size exceeds 300KB limit. Current size: ' . round($file_size / 1024, 2) . 'KB'
                                        ]);
                                        exit;
                                    }
                                }
                            }
                            
                            $saved = $wpdb->insert($table_name, [
                                'image_url' => $image_url,
                                'created_at' => current_time('mysql'),
                            ], ['%s', '%s']);

                            if ($saved) {
                                echo json_encode(['success' => true, 'message' => 'Image saved successfully!']);
                            } else {
                                echo json_encode(['success' => false, 'message' => 'Database insert failed.']);
                            }
                            exit;
                        }

                        if (
                            $_SERVER['REQUEST_METHOD'] === 'POST' &&
                            isset($_POST['action']) &&
                            $_POST['action'] === 'delete_slider_image' &&
                            isset($_POST['id'])
                        ) {
                            header('Content-Type: application/json');
                            $id = intval($_POST['id']);
                            $wpdb->delete($table_name, ['id' => $id]);
                            return json_encode(['success' => true, 'message' => 'Image deleted.']);
                        }

                        ?>

                        <div class="wrap">
                            <h2>Upload Slider Image</h2>
                            <p class="description" style="color: #d63638; font-weight: 600;">⚠️ Maximum file size: 300KB</p>
                            <button type="button" id="upload_slider_image_button">Upload Image</button>
                            <input type="hidden" id="slider_image_url" name="slider_image_url" value="">
                            <div id="slider_image_preview" style="margin-top: 10px;"></div>
                            <div id="slider_upload_error" style="margin-top: 10px; color: #d63638; font-weight: 600; display: none;"></div>

                            <hr style="margin: 30px 0;">

                            <h3>Slider Images</h3>
                            <div id="slider_images_list" style="display: flex; flex-wrap: wrap; gap: 20px;">
                                <?php
                                $images = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC");
                                foreach ($images as $img):
                                ?>
                                    <div class="slider-item" data-id="<?php echo esc_attr($img->id); ?>" style="position: relative;">
                                        <img src="<?php echo esc_url($img->image_url); ?>" style="width: 250px; height: 118px; border: 1px solid #ccc;">
                                        <span class="delete-icon" style="position: absolute; top: 5px; right: 5px; background: #f44336; color: #fff; border-radius: 50%; padding: 4px 8px; cursor: pointer;">&times;</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <script>
                            document.addEventListener("DOMContentLoaded", function() {
                                const $ = jQuery;

                                // Upload Image
                                $('#upload_slider_image_button').on('click', function(e) {
                                    e.preventDefault();

                                    let frame = wp.media({
                                        title: 'Select or Upload Image',
                                        button: {
                                            text: 'Use this image'
                                        },
                                        multiple: false
                                    });

                                    frame.on('select', function() {
                                        let attachment = frame.state().get('selection').first().toJSON();
                                        let imageUrl = attachment.url;
                                        let imageId = attachment.id;
                                        let fileSize = attachment.filesizeInBytes || 0;
                                        
                                        // Client-side validation: 300KB limit
                                        const maxSize = 300 * 1024; // 300KB in bytes
                                        
                                        $('#slider_upload_error').hide().text('');
                                        
                                        if (fileSize > maxSize) {
                                            let sizeInKB = Math.round(fileSize / 1024);
                                            let errorMsg = '❌ Image size (' + sizeInKB + 'KB) exceeds the 300KB limit. Please choose a smaller image.';
                                            $('#slider_upload_error').text(errorMsg).show();
                                            $('#slider_image_preview').html('');
                                            return;
                                        }

                                        $('#slider_image_url').val(imageUrl);
                                        let sizeInKB = Math.round(fileSize / 1024);
                                        $('#slider_image_preview').html('<img src="' + imageUrl + '" style="max-width:200px;"><p style="color: #2271b1; margin-top: 5px;">✓ Size: ' + sizeInKB + 'KB (Valid)</p>');

                                        $.ajax({
                                            url: window.location.href,
                                            method: 'POST',
                                            dataType: 'json',
                                            data: {
                                                action: 'save_slider_image',
                                                image_url: imageUrl,
                                                image_id: imageId
                                            },
                                            success: function(res) {
                                                if (res.success) {
                                                    location.reload(); // Refresh to show new image
                                                } else {
                                                    $('#slider_upload_error').text('❌ ' + res.message).show();
                                                    $('#slider_image_preview').html('');
                                                }
                                            },
                                            error: function(xhr) {
                                                let errorMsg = 'Upload failed. Please try again.';
                                                if (xhr.responseJSON && xhr.responseJSON.message) {
                                                    errorMsg = xhr.responseJSON.message;
                                                }
                                                $('#slider_upload_error').text('❌ ' + errorMsg).show();
                                            }
                                        });
                                    });

                                    frame.open();
                                });

                                // Delete Image - Ensure event delegation is applied to dynamically added elements
                                $('#slider_images_list').on('click', '.delete-icon', function() {
                                    if (!confirm('Delete this image?')) return;

                                    let item = $(this).closest('.slider-item');
                                    let id = item.data('id');

                                    $.ajax({
                                        url: window.location.href,
                                        method: 'POST',
                                        dataType: 'json',
                                        data: {
                                            action: 'delete_slider_image',
                                            id: id
                                        },
                                        success: function(res) {
                                            // alert(res.message);
                                            // item.remove();
                                            location.reload();
                                        },
                                        error: function() {
                                            // alert('Updated.');
                                            location.reload();
                                        }
                                    });
                                });
                            });
                        </script>

                        <div class="testimonial-settings">
                            <h1 style="margin-top: 20px;">Testimonial Settings</h1>

                            <label for="testimonial_type">Testimonial Type</label>
                            <select id="testimonial_type" name="opt_name[testimonial_type]" class="regular-text" style="width: 200px;">
                                <?php
                                $testimonial_type_value = $redux_options['testimonial_type'] ?? 'Default';
                                $options = [
                                    'Default' => 'Default',
                                    'Pad' => 'Pad',
                                ];
                                foreach ($options as $value => $label) {
                                    echo '<option value="' . esc_attr($value) . '"' . selected($testimonial_type_value, $value, false) . '>' . esc_html($label) . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        <div class="testimonial-settings">
                            <label for="testimonial_pad">Testimonial Pad</label>
                            <input type="file" id="testimonial_pad" name="testimonial_pad" accept="image/*,application/pdf">
                            <?php if (!empty($redux_options['testimonial_pad'])) : ?>
                                <p class="description">Current file: <a href="<?php echo esc_url(get_image_url($redux_options['testimonial_pad'])); ?>" style="text-decoration:underline;color:blue" target="_blank" rel="noopener noreferrer">View pad</a></p>
                            <?php endif; ?>
                            <input type="hidden" name="opt_name[current_testimonial_pad]" value="<?php echo esc_attr($redux_options['testimonial_pad'] ?? ''); ?>">
                        </div>
                        
                        <style>
                            #slider_images_list {
                                display: flex;
                                flex-wrap: wrap;
                                gap: 20px;
                            }

                            .slider-item {
                                position: relative;
                                width: 200px;
                                height: 120px;
                                overflow: hidden;
                                border: 1px solid #ccc;
                                border-radius: 6px;
                                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                            }

                            .slider-item img {
                                width: 100%;
                                height: 100%;
                                object-fit: cover;
                                display: block;
                            }

                            .delete-icon {
                                position: absolute;
                                top: 5px;
                                right: 5px;
                                background: #f44336;
                                color: #fff;
                                border-radius: 50%;
                                padding: 4px 8px;
                                cursor: pointer;
                                font-weight: bold;
                                font-size: 16px;
                                line-height: 1;
                            }

                            /* Layout editor styles */
                            #layout-editor.layout-editor {
                                border: 1px solid #e2e8f0;
                                background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%);
                                padding: 30px;
                                border-radius: 16px;
                                margin-top: 35px;
                                box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
                                transition: box-shadow 0.3s ease;
                            }

                            #layout-editor.layout-editor:hover {
                                box-shadow: 0 18px 32px rgba(30, 64, 175, 0.14);
                            }

                            #layout-editor.layout-editor h3 {
                                margin: 0;
                                font-size: 22px;
                                color: #1e293b;
                                font-weight: 700;
                            }

                            #layout-editor.layout-editor p {
                                margin: 6px 0 0;
                                color: #64748b;
                            }

                            .layout-editor-header {
                                display: flex;
                                align-items: flex-start;
                                justify-content: space-between;
                                gap: 24px;
                                flex-wrap: wrap;
                            }

                            .layout-editor-actions {
                                display: flex;
                                align-items: center;
                                gap: 12px;
                                flex-wrap: wrap;
                            }

                            .layout-editor-actions .button {
                                border-radius: 999px;
                                padding: 8px 18px;
                                font-weight: 600;
                            }

                            .layout-editor-summary {
                                font-size: 14px;
                                color: #475569;
                                font-weight: 500;
                                background: #f1f5f9;
                                padding: 6px 14px;
                                border-radius: 999px;
                                display: inline-flex;
                                align-items: center;
                                gap: 6px;
                            }

                            .layout-editor-summary strong {
                                color: #1e293b;
                            }

                            .layout-grid {
                                margin-top: 28px;
                                display: grid;
                                gap: 20px;
                                grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
                            }

                            .layout-card {
                                display: block;
                                cursor: pointer;
                                position: relative;
                                border-radius: 16px;
                            }

                            .layout-card input {
                                display: none;
                            }

                            .layout-card-body {
                                border: 1px solid #e2e8f0;
                                background: linear-gradient(135deg, #ffffff 0%, #f9fbfc 100%);
                                border-radius: 16px;
                                padding: 22px;
                                min-height: 140px;
                                display: flex;
                                flex-direction: column;
                                gap: 14px;
                                transition: all 0.3s ease;
                            }

                            .layout-card-header {
                                display: flex;
                                align-items: center;
                                justify-content: space-between;
                                gap: 16px;
                            }

                            .layout-card-title {
                                font-size: 16px;
                                font-weight: 600;
                                color: #1e293b;
                            }

                            .layout-card-description {
                                margin: 0;
                                color: #64748b;
                                font-size: 14px;
                                line-height: 1.5;
                                flex-grow: 1;
                            }

                            .layout-status {
                                font-size: 12px;
                                text-transform: uppercase;
                                letter-spacing: 0.08em;
                                padding: 6px 14px;
                                border-radius: 999px;
                                background: #e2e8f0;
                                color: #475569;
                                font-weight: 700;
                                transition: all 0.3s ease;
                            }

                            .layout-status::before {
                                content: attr(data-off);
                            }

                            .layout-card input:checked+.layout-card-body {
                                border-color: #2563eb;
                                box-shadow: 0 16px 30px rgba(37, 99, 235, 0.18);
                                transform: translateY(-2px);
                            }

                            .layout-card input:checked+.layout-card-body .layout-status {
                                background: #2563eb;
                                color: #ffffff;
                            }

                            .layout-card input:checked+.layout-card-body .layout-status::before {
                                content: attr(data-on);
                            }

                            .layout-empty-state {
                                text-align: center;
                                color: #94a3b8;
                                font-style: italic;
                                font-size: 15px;
                                margin: 20px 0 0;
                            }

                            @media (max-width: 768px) {
                                .layout-editor-header {
                                    flex-direction: column;
                                    align-items: flex-start;
                                }

                                .layout-editor-actions {
                                    width: 100%;
                                    justify-content: flex-start;
                                }
                            }
                        </style>



                        <?php
                        $total_layout_sections = is_array($layout_editor_sections) ? count($layout_editor_sections) : 0;
                        $initial_visible_sections = 0;
                        if ($total_layout_sections > 0) {
                            if (isset($redux_options['layout_visibility']) && is_array($redux_options['layout_visibility'])) {
                                foreach ($layout_editor_sections as $layout_key => $layout_config) {
                                    if (!empty($redux_options['layout_visibility'][$layout_key])) {
                                        $initial_visible_sections++;
                                    }
                                }
                            } else {
                                $initial_visible_sections = $total_layout_sections;
                            }
                        }
                        ?>
                        <div id="layout-editor" class="layout-editor" data-visible-count="<?php echo esc_attr($initial_visible_sections); ?>" data-total-count="<?php echo esc_attr($total_layout_sections); ?>">
                            <div class="layout-editor-header">
                                <div>
                                    <h3>Layout Visibility Manager</h3>
                                    <p>Choose which homepage sections should stay visible. Save to publish your changes.</p>
                                </div>
                                <div class="layout-editor-actions">
                                    <button type="button" class="button button-primary layout-select-all">Show All</button>
                                    <button type="button" class="button layout-clear-all">Hide All</button>
                                    <span class="layout-editor-summary"><strong><span class="layout-visible-count"><?php echo esc_html($initial_visible_sections); ?></span></strong> / <?php echo esc_html($total_layout_sections); ?> visible</span>
                                </div>
                            </div>
                            <div class="layout-grid">
                                <?php if (!empty($layout_editor_sections)) : ?>
                                    <?php foreach ($layout_editor_sections as $section_key => $section_config) :
                                        $is_enabled = !empty($redux_options['layout_visibility'][$section_key]);
                                        $description = $section_config['description'] ?? '';
                                        $label = $section_config['label'] ?? ucfirst($section_key);
                                    ?>
                                        <label class="layout-card">
                                            <input type="checkbox" class="layout-toggle-input" name="layout_visibility[<?php echo esc_attr($section_key); ?>]" value="1" <?php checked($is_enabled); ?>>
                                            <div class="layout-card-body">
                                                <div class="layout-card-header">
                                                    <span class="layout-card-title"><?php echo esc_html($label); ?></span>
                                                    <span class="layout-status" data-on="Visible" data-off="Hidden"></span>
                                                </div>
                                                <?php if (!empty($description)) : ?>
                                                    <p class="layout-card-description"><?php echo esc_html($description); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </label>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <p class="layout-empty-state">No sections available for layout control yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const layoutEditor = document.querySelector('#layout-editor.layout-editor');
                                if (!layoutEditor) {
                                    return;
                                }

                                const toggles = layoutEditor.querySelectorAll('.layout-toggle-input');
                                const selectAllBtn = layoutEditor.querySelector('.layout-select-all');
                                const clearAllBtn = layoutEditor.querySelector('.layout-clear-all');
                                const visibleCountEl = layoutEditor.querySelector('.layout-visible-count');

                                const updateSummary = () => {
                                    const visibleCount = Array.from(toggles).filter(function(cb) {
                                        return cb.checked;
                                    }).length;

                                    if (visibleCountEl) {
                                        visibleCountEl.textContent = visibleCount;
                                    }

                                    layoutEditor.setAttribute('data-visible-count', visibleCount);
                                };

                                toggles.forEach(function(toggle) {
                                    toggle.addEventListener('change', updateSummary);
                                });

                                if (selectAllBtn) {
                                    selectAllBtn.addEventListener('click', function() {
                                        toggles.forEach(function(toggle) {
                                            if (!toggle.checked) {
                                                toggle.checked = true;
                                                toggle.dispatchEvent(new Event('change'));
                                            }
                                        });
                                    });
                                }

                                if (clearAllBtn) {
                                    clearAllBtn.addEventListener('click', function() {
                                        toggles.forEach(function(toggle) {
                                            if (toggle.checked) {
                                                toggle.checked = false;
                                                toggle.dispatchEvent(new Event('change'));
                                            }
                                        });
                                    });
                                }

                                updateSummary();
                            });
                        </script>
                    </div>

                    <div id="header" class="tab-content">
                        <h2>Header</h2>
                        <label>Upload Banner</label>
                        <fieldset id="opt_name-logo_upload" class="redux-field-container redux-field redux-container-media" data-id="logo_upload" data-type="media">
                            <input placeholder="No media selected" type="text" class="upload large-text" name="opt_name[logo_upload][url]" id="opt_name[logo_upload][url]" value="<?php echo esc_attr($redux_options['logo_upload']['url'] ?? get_site_url() . '/wp-content/uploads/2024/01/logo.png'); ?>" readonly="readonly">
                            <input type="hidden" class="data" data-mode="image">
                            <input type="hidden" class="library-filter" data-lib-filter="">
                            <input type="hidden" class="upload-id" name="opt_name[logo_upload][id]" id="opt_name[logo_upload][id]" value="<?php echo esc_attr($redux_options['logo_upload']['id'] ?? ''); ?>">
                            <input type="hidden" class="upload-height" name="opt_name[logo_upload][height]" id="opt_name[logo_upload][height]" value="<?php echo esc_attr($redux_options['logo_upload']['height'] ?? '180'); ?>">
                            <input type="hidden" class="upload-width" name="opt_name[logo_upload][width]" id="opt_name[logo_upload][width]" value="<?php echo esc_attr($redux_options['logo_upload']['width'] ?? '180'); ?>">
                            <input type="hidden" class="upload-thumbnail" name="opt_name[logo_upload][thumbnail]" id="opt_name[logo_upload][thumbnail]" value="<?php echo esc_attr($redux_options['logo_upload']['thumbnail'] ?? $redux_options['logo_upload']['url'] ?? ''); ?>">
                            <div class="screenshot">
                                <a class="of-uploaded-image" href="<?php echo esc_url(get_image_url($redux_options['logo_upload']['url'] ?? '') ?: get_site_url() . '/wp-content/uploads/2024/01/logo.png'); ?>" target="_blank">
                                    <img class="redux-option-image" id="image_logo_upload" src="<?php echo esc_url(get_image_url($redux_options['logo_upload']['url'] ?? '') ?: get_site_url() . '/wp-content/uploads/2024/01/logo.png'); ?>" alt="" target="_blank" rel="external">
                                </a>
                            </div>
                            <div class="upload_button_div">
                                <span class="button media_upload_button" id="logo_upload-media">Upload</span>
                                <span class="button remove-image" id="reset_logo_upload" rel="logo_upload">Remove</span>
                            </div>
                        </fieldset>
                        <label>Logo Width</label>
                        <input type="text" id="logo_width" name="opt_name[logo_width]" value="<?php echo esc_attr($redux_options['logo_width'] ?? '800'); ?>" class="regular-text">

                        <label>MPO Code</label>
                        <input type="text" id="header_phone" name="opt_name[header_phone]" value="<?php echo esc_attr($redux_options['header_phone'] ?? ''); ?>" class="regular-text">
                        <label>EIIN ID</label>
                        <input type="text" id="header_email" name="opt_name[header_email]" value="<?php echo esc_attr($redux_options['header_email'] ?? ''); ?>" class="regular-text">


                    </div>

                    <div id="footer" class="tab-content">
                        <h2>Footer</h2>
                        <style>
                            .form-group {
                                display: flex;
                                align-items: center;
                                margin-bottom: 15px;
                            }


                            .form-group label {
                                width: 150px;
                                font-weight: bold;
                            }

                            .form-group input {
                                flex: 1;
                                padding: 8px;
                                border: 1px solid #ccc;
                                border-radius: 4px;
                                width: 100%;
                            }

                            .form-container {
                                /*max-width: 500px;*/
                                background: #f9f9f9;
                                padding: 20px;
                                border-radius: 5px;
                                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                            }
                        </style>

                        <div class="form-container">
                            <div class="form-group">
                                <label for="footerAddress">Footer Address:</label>
                                <!--<fieldset id="opt_name-footerAddress" class="redux-field-container redux-field redux-field-init redux-container-editor" data-id="footerAddress" data-type="editor">-->
                                <!--    <div class="wp-core-ui wp-editor-wrap">-->
                                <?php
                                // $editor_id = 'footerAddress';
                                // $content = $redux_options['footerAddress'] ?? '';

                                // $settings = array(
                                //     'textarea_name' => 'opt_name[footerAddress]',
                                //     'media_buttons' => true, // Show 'Add Media' button
                                //     'teeny'         => false, // Full editor mode
                                //     'quicktags'     => true, // Show HTML buttons
                                //     'editor_height' => 200,
                                // );

                                // wp_editor($content, $editor_id, $settings);
                                wp_editor(
                                    $redux_options['footerAddress'] ?? '',
                                    'footerAddress',
                                    array(
                                        'textarea_name' => 'opt_name[footerAddress]',
                                        'media_buttons' => true,
                                        'textarea_rows' => 10,
                                        'tinymce' => true,  // Enables rich text editor
                                        'quicktags' => false,
                                        'wpautop' => true  // Automatically add <p> tags
                                    )
                                );

                                ?>
                                <!--    </div>-->
                                <!--</fieldset>-->
                            </div>
                            <div class="form-group">
                                <label for="footerContact">Footer Address:</label>

                                <?php

                                wp_editor(
                                    $redux_options['footerContact'] ?? '',
                                    'footerContact',
                                    array(
                                        'textarea_name' => 'opt_name[footerContact]',
                                        'media_buttons' => true,
                                        'textarea_rows' => 10,
                                        'tinymce' => true,  // Enables rich text editor
                                        'quicktags' => false,
                                        'wpautop' => true  // Automatically add <p> tags
                                    )
                                );

                                ?>
                            </div>
                            <div class="form-group">
                                <label for="footerFbUrl">Facebook URL:</label>
                                <input type="text" id="footerFbUrl" name="opt_name[footerFbUrl]"
                                    value="<?php echo esc_attr($redux_options['footerFbUrl'] ?? ''); ?>" class="regular-text">
                            </div>
                            <div class="form-group">
                                <label for="footerTwtUrl">Youtube URL:</label>
                                <input type="text" id="footerTwtUrl" name="opt_name[footerTwtUrl]"
                                    value="<?php echo esc_attr($redux_options['footerTwtUrl'] ?? ''); ?>" class="regular-text">
                            </div>

                            <div class="form-group">
                                <label for="footerGglUrl">Google+ URL:</label>
                                <input type="text" id="footerGglUrl" name="opt_name[footerGglUrl]"
                                    value="<?php echo esc_attr($redux_options['footerGglUrl'] ?? ''); ?>" class="regular-text">
                            </div>

                            <div class="form-group">
                                <label for="copyrightText">Copyright Text:</label>
                                <input type="text" id="copyrightText" name="opt_name[copyrightText]"
                                    value="<?php echo esc_attr($redux_options['copyrightText'] ?? ''); ?>" class="regular-text">
                            </div>
                        </div>






                    </div>
                    <div id="aboutus" class="tab-content">
                        <h2>about us</h2>
                        <div class="form-container">
                            <div class="form-group">
                                <label>Upload About us Photo</label>
                                <fieldset id="opt_name-home_about_img" class="redux-field-container redux-field redux-container-media" data-id="home_about_img" data-type="media">
                                    <input placeholder="No media selected" type="text" class="upload large-text" name="opt_name[home_about_img][url]" id="opt_name[home_about_img][url]" value="<?php echo esc_attr($redux_options['home_about_img']['url'] ?? get_site_url() . '/wp-content/uploads/2024/01/logo.png'); ?>" readonly="readonly">
                                    <input type="hidden" class="data" data-mode="image">
                                    <input type="hidden" class="library-filter" data-lib-filter="">
                                    <input type="hidden" class="upload-id" name="opt_name[home_about_img][id]" id="opt_name[home_about_img][id]" value="<?php echo esc_attr($redux_options['home_about_img']['id'] ?? ''); ?>">
                                    <input type="hidden" class="upload-height" name="opt_name[home_about_img][height]" id="opt_name[home_about_img][height]" value="<?php echo esc_attr($redux_options['home_about_img']['height'] ?? '180'); ?>">
                                    <input type="hidden" class="upload-width" name="opt_name[home_about_img][width]" id="opt_name[home_about_img][width]" value="<?php echo esc_attr($redux_options['home_about_img']['width'] ?? '180'); ?>">
                                    <input type="hidden" class="upload-thumbnail" name="opt_name[home_about_img][thumbnail]" id="opt_name[home_about_img][thumbnail]" value="<?php echo esc_attr($redux_options['home_about_img']['thumbnail'] ?? $redux_options['home_about_img']['url'] ?? ''); ?>">
                                    <div class="screenshot">
                                        <a class="of-uploaded-image" href="<?php echo esc_url(get_image_url($redux_options['home_about_img']['url'] ?? '') ?: get_site_url() . '/wp-content/uploads/2024/01/logo.png'); ?>" target="_blank">
                                            <img class="redux-option-image" id="image_home_about_img" src="<?php echo esc_url(get_image_url($redux_options['home_about_img']['url'] ?? '') ?: get_site_url() . '/wp-content/uploads/2024/01/logo.png'); ?>" alt="" target="_blank" rel="external">
                                        </a>
                                    </div>
                                    <div class="upload_button_div">
                                        <span class="button media_upload_button" id="home_about_img-media">Upload</span>
                                        <span class="button remove-image" id="reset_home_about_img" rel="home_about_img">Remove</span>
                                    </div>
                                </fieldset>
                            </div>
                            <div class="form-group">
                                <label for="aboutTitelText">About Us Title:</label>
                                <input type="text" id="aboutTitelText" name="opt_name[aboutTitelText]"
                                    value="<?php echo esc_attr($redux_options['aboutTitelText'] ?? ''); ?>" class="regular-text">
                            </div>
                            <div class="form-group" style="width:80%">
                                <label for="aboutUsText">About Us Text:</label>
                                <!--<fieldset id="opt_name-aboutUsText" class="redux-field-container redux-field redux-field-init redux-container-editor" data-id="aboutUsText" data-type="editor">-->
                                <!--    <div class="wp-core-ui wp-editor-wrap">-->
                                <?php

                                wp_editor(
                                    $redux_options['aboutUsText'] ?? '',
                                    'aboutUsText_manual',
                                    array(
                                        'textarea_name' => 'opt_name[aboutUsText]',
                                        'media_buttons' => true,
                                        'textarea_rows' => 10,
                                        'tinymce' => true,  // Enables rich text editor
                                        'quicktags' => false,
                                        'wpautop' => true  // Automatically add <p> tags
                                    )
                                );

                                ?>
                                <!--    </div>-->
                                <!--</fieldset>-->


                            </div>

                            <div class="form-group">
                                <label for="aboutUsTextLimit">Text Limit (character):</label>
                                <input type="text" id="aboutUsTextLimit" name="opt_name[aboutUsTextLimit]"
                                    value="<?php echo esc_attr($redux_options['aboutUsTextLimit'] ?? ''); ?>" class="regular-text">
                            </div>

                            <div class="form-group">
                                <label for="aboutUsMoreBtn">Read More Button:</label>
                                <input type="text" id="aboutUsMoreBtn" name="opt_name[aboutUsMoreBtn]"
                                    value="<?php echo esc_attr($redux_options['aboutUsMoreBtn'] ?? ''); ?>" class="regular-text">
                            </div>
                        </div>
                    </div>
                    <div id="headmaster" class="tab-content">
                        <h2>Headmaster</h2>
                        <div class="form-container">

                            <div class="form-group">
                                <label>Upload Headmaster Photo</label>
                                <fieldset id="opt_name-homeHeadmasterImg" class="redux-field-container redux-field redux-container-media" data-id="homeHeadmasterImg" data-type="media">
                                    <input placeholder="No media selected" type="text" class="upload large-text" name="opt_name[homeHeadmasterImg][url]" id="opt_name[homeHeadmasterImg][url]" value="<?php echo esc_attr($redux_options['homeHeadmasterImg']['url'] ?? get_site_url() . '/wp-content/uploads/2024/01/logo.png'); ?>" readonly="readonly">
                                    <input type="hidden" class="data" data-mode="image">
                                    <input type="hidden" class="library-filter" data-lib-filter="">
                                    <input type="hidden" class="upload-id" name="opt_name[homeHeadmasterImg][id]" id="opt_name[homeHeadmasterImg][id]" value="<?php echo esc_attr($redux_options['homeHeadmasterImg']['id'] ?? ''); ?>">
                                    <input type="hidden" class="upload-height" name="opt_name[homeHeadmasterImg][height]" id="opt_name[homeHeadmasterImg][height]" value="<?php echo esc_attr($redux_options['homeHeadmasterImg']['height'] ?? '180'); ?>">
                                    <input type="hidden" class="upload-width" name="opt_name[homeHeadmasterImg][width]" id="opt_name[homeHeadmasterImg][width]" value="<?php echo esc_attr($redux_options['homeHeadmasterImg']['width'] ?? '180'); ?>">
                                    <input type="hidden" class="upload-thumbnail" name="opt_name[homeHeadmasterImg][thumbnail]" id="opt_name[homeHeadmasterImg][thumbnail]" value="<?php echo esc_attr($redux_options['homeHeadmasterImg']['thumbnail'] ?? $redux_options['homeHeadmasterImg']['url'] ?? ''); ?>">
                                    <div class="screenshot">
                                        <a class="of-uploaded-image" href="<?php echo esc_url(get_image_url($redux_options['homeHeadmasterImg']['url'] ?? '') ?: get_site_url() . '/wp-content/uploads/2024/01/logo.png'); ?>" target="_blank">
                                            <img class="redux-option-image" id="image_homeHeadmasterImg" src="<?php echo esc_url(get_image_url($redux_options['homeHeadmasterImg']['url'] ?? '') ?: get_site_url() . '/wp-content/uploads/2024/01/logo.png'); ?>" alt="" target="_blank" rel="external">
                                        </a>
                                    </div>
                                    <div class="upload_button_div">
                                        <span class="button media_upload_button" id="homeHeadmasterImg-media">Upload</span>
                                        <span class="button remove-image" id="reset_homeHeadmasterImg" rel="homeHeadmasterImg">Remove</span>
                                    </div>
                                </fieldset>
                            </div>
                            <div class="form-group">
                                <label for="homeHeadmasterTitle">Headmaster Name:</label>
                                <input type="text" id="homeHeadmasterTitle" name="opt_name[homeHeadmasterTitle]"
                                    value="<?php echo esc_attr($redux_options['homeHeadmasterTitle'] ?? ''); ?>" class="regular-text">
                            </div>
                            <div class="form-group">
                                <label for="headmasterSpeechTitle">Headmaster Speech Title:</label>
                                <input type="text" id="headmasterSpeechTitle" name="opt_name[headmasterSpeechTitle]"
                                    value="<?php echo esc_attr($redux_options['headmasterSpeechTitle'] ?? ''); ?>" class="regular-text">
                            </div>
                            <div class="form-group">
                                <label for="homeHeadmaster">Headmaster Speech:</label>
                                <!--<fieldset id="opt_name-homeHeadmaster" class="redux-field-container redux-field redux-field-init redux-container-editor" data-id="homeHeadmaster" data-type="editor">-->
                                <!--    <div class="wp-core-ui wp-editor-wrap">-->
                                <?php
                                // $editor_id = 'homeHeadmaster';
                                // $content = $redux_options['homeHeadmaster'] ?? '';

                                // $settings = array(
                                //     'textarea_name' => 'opt_name[homeHeadmaster]',
                                //     'media_buttons' => true, // Show 'Add Media' button
                                //     'teeny'         => false, // Full editor mode
                                //     'quicktags'     => false, // Show HTML buttons
                                //     'editor_height' => 200,
                                // );

                                // wp_editor($content, $editor_id, $settings);
                                wp_editor(
                                    $redux_options['homeHeadmaster'] ?? '',
                                    'homeHeadmaster',
                                    array(
                                        'textarea_name' => 'opt_name[homeHeadmaster]',
                                        'media_buttons' => true,
                                        'textarea_rows' => 10,
                                        'tinymce' => true,  // Enables rich text editor
                                        'quicktags' => false,
                                        'wpautop' => true  // Automatically add <p> tags
                                    )
                                );
                                ?>
                                <!--    </div>-->
                                <!--</fieldset>-->
                            </div>

                            <div class="form-group">
                                <label for="headmasterTextLimit">Text Limit (character):</label>
                                <input type="text" id="headmasterTextLimit" name="opt_name[headmasterTextLimit]"
                                    value="<?php echo esc_attr($redux_options['headmasterTextLimit'] ?? ''); ?>" class="regular-text">
                            </div>

                            <div class="form-group">
                                <label for="headmasterMoreBtn">Read More Button:</label>
                                <input type="text" id="headmasterMoreBtn" name="opt_name[headmasterMoreBtn]"
                                    value="<?php echo esc_attr($redux_options['headmasterMoreBtn'] ?? ''); ?>" class="regular-text">
                            </div>
                        </div>
                    </div>

                    <div id="chairman" class="tab-content">
                        <h2>Chairman</h2>
                        <div class="form-container">
                            <div class="form-group">
                                <label>Upload Headmaster Photo</label>
                                <fieldset id="opt_name-homeChairmanImg" class="redux-field-container redux-field redux-container-media" data-id="homeChairmanImg" data-type="media">
                                    <input placeholder="No media selected" type="text" class="upload large-text" name="opt_name[homeChairmanImg][url]" id="opt_name[homeChairmanImg][url]" value="<?php echo esc_attr($redux_options['homeChairmanImg']['url'] ?? get_site_url() . '/wp-content/uploads/2024/01/logo.png'); ?>" readonly="readonly">
                                    <input type="hidden" class="data" data-mode="image">
                                    <input type="hidden" class="library-filter" data-lib-filter="">
                                    <input type="hidden" class="upload-id" name="opt_name[homeChairmanImg][id]" id="opt_name[homeChairmanImg][id]" value="<?php echo esc_attr($redux_options['homeChairmanImg']['id'] ?? ''); ?>">
                                    <input type="hidden" class="upload-height" name="opt_name[homeChairmanImg][height]" id="opt_name[homeChairmanImg][height]" value="<?php echo esc_attr($redux_options['homeChairmanImg']['height'] ?? '180'); ?>">
                                    <input type="hidden" class="upload-width" name="opt_name[homeChairmanImg][width]" id="opt_name[homeChairmanImg][width]" value="<?php echo esc_attr($redux_options['homeChairmanImg']['width'] ?? '180'); ?>">
                                    <input type="hidden" class="upload-thumbnail" name="opt_name[homeChairmanImg][thumbnail]" id="opt_name[homeChairmanImg][thumbnail]" value="<?php echo esc_attr($redux_options['homeChairmanImg']['thumbnail'] ?? $redux_options['homeChairmanImg']['url'] ?? ''); ?>">
                                    <div class="screenshot">
                                        <a class="of-uploaded-image" href="<?php echo esc_url(get_image_url($redux_options['homeChairmanImg']['url'] ?? '') ?: get_site_url() . '/wp-content/uploads/2024/01/logo.png'); ?>" target="_blank">
                                            <img class="redux-option-image" id="image_homeChairmanImg" src="<?php echo esc_url(get_image_url($redux_options['homeChairmanImg']['url'] ?? '') ?: get_site_url() . '/wp-content/uploads/2024/01/logo.png'); ?>" alt="" target="_blank" rel="external">
                                        </a>
                                    </div>
                                    <div class="upload_button_div">
                                        <span class="button media_upload_button" id="homeChairmanImg-media">Upload</span>
                                        <span class="button remove-image" id="reset_homeChairmanImg" rel="homeChairmanImg">Remove</span>
                                    </div>
                                </fieldset>
                            </div>


                            <div class="form-group">
                                <label for="homeChairmanTitle">Chairman Name:</label>
                                <input type="text" id="homeChairmanTitle" name="opt_name[homeChairmanTitle]"
                                    value="<?php echo esc_attr($redux_options['homeChairmanTitle'] ?? ''); ?>" class="regular-text">
                            </div>
                            <div class="form-group">
                                <label for="chairmanSpeechTitle">Chairman Speech Title:</label>
                                <input type="text" id="chairmanSpeechTitle" name="opt_name[chairmanSpeechTitle]"
                                    value="<?php echo esc_attr($redux_options['chairmanSpeechTitle'] ?? ''); ?>" class="regular-text">
                            </div>
                            <div class="form-group">
                                <label for="homeChairman">Chairman Speech:</label>
                                <!--<div id="opt_name-homeChairman" class="redux-field-container redux-field redux-field-init redux-container-editor" data-id="homeChairman" data-type="editor">-->
                                <div class="wp-core-ui wp-editor-wrap">
                                    <?php
                                    wp_editor(
                                        $redux_options['homeChairman'] ?? '',
                                        'homeChairman',
                                        array(
                                            'textarea_name' => 'opt_name[homeChairman]',
                                            'media_buttons' => true,
                                            'textarea_rows' => 10,
                                            'tinymce' => true,  // Enables rich text editor
                                            'quicktags' => false,
                                            'wpautop' => true  // Automatically add <p> tags
                                        )
                                    );

                                    // $editor_id = 'homeChairman';
                                    // $content = $redux_options['homeChairman'] ?? '';

                                    // $settings = array(
                                    //     'textarea_name' => 'opt_name[homeChairman]',
                                    //     'media_buttons' => true, // Show 'Add Media' button
                                    //     'teeny'         => false, // Full editor mode
                                    //     'quicktags'     => true, // Show HTML buttons
                                    //     'editor_height' => 200,
                                    //     'sanitize_callback' => 'wp_kses_post',
                                    // );

                                    // wp_editor($content, $editor_id, $settings);
                                    ?>
                                </div>
                                <!--</div>-->
                            </div>

                            <div class="form-group">
                                <label for="chairmanTextLimit">Text Limit (character):</label>
                                <input type="text" id="chairmanTextLimit" name="opt_name[chairmanTextLimit]"
                                    value="<?php echo esc_attr($redux_options['chairmanTextLimit'] ?? ''); ?>" class="regular-text">
                            </div>

                            <div class="form-group">
                                <label for="chairmanMoreBtn">Read More Button:</label>
                                <input type="text" id="chairmanMoreBtn" name="opt_name[chairmanMoreBtn]"
                                    value="<?php echo esc_attr($redux_options['chairmanMoreBtn'] ?? ''); ?>" class="regular-text">
                            </div>
                        </div>
                    </div>

                    <div id="statistics" class="tab-content">
                        <h2>Statistics & Important Links</h2>
                        <div class="form-container">
                            <div class="form-group">
                                <label for="totalClasses">Number of Classes:</label>
                                <input type="number" id="totalClasses" name="opt_name[totalClasses]"
                                    value="<?php echo esc_attr($redux_options['totalClasses'] ?? ''); ?>" class="regular-text" min="0">
                            </div>
                            <div class="form-group">
                                <label for="totalStudents">Number of Students:</label>
                                <input type="number" id="totalStudents" name="opt_name[totalStudents]"
                                    value="<?php echo esc_attr($redux_options['totalStudents'] ?? ''); ?>" class="regular-text" min="0">
                            </div>
                            <div class="form-group">
                                <label for="totalTeachers">Number of Teachers:</label>
                                <input type="number" id="totalTeachers" name="opt_name[totalTeachers]"
                                    value="<?php echo esc_attr($redux_options['totalTeachers'] ?? ''); ?>" class="regular-text" min="0">
                            </div>
                            <div class="form-group">
                                <label for="totalStaffs">Number of Staffs:</label>
                                <input type="number" id="totalStaffs" name="opt_name[totalStaffs]"
                                    value="<?php echo esc_attr($redux_options['totalStaffs'] ?? ''); ?>" class="regular-text" min="0">
                            </div>
                        </div>

                        <hr style="margin: 40px 0; border: none; border-top: 2px solid #ddd;">

                        <!-- Important Links Management -->
                        <div class="important-links">
                            <h3 style="color: #2d3748; font-size: 24px; margin-bottom: 20px;">
                                <i class="dashicons dashicons-admin-links" style="font-size: 28px; vertical-align: middle;"></i>
                                Important Links Management
                            </h3>
                            <p style="color: #718096; margin-bottom: 25px;">
                                Manage the important links that appear on the homepage. Links are displayed in a 4-column grid layout.
                            </p>

                            <style>
                                .important-links .link-item {
                                    background: #fff;
                                    padding: 20px;
                                    margin-bottom: 15px;
                                    border-radius: 12px;
                                    border-left: 5px solid #667eea;
                                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
                                    transition: all 0.3s ease;
                                }

                                .important-links .link-item:hover {
                                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.12);
                                    transform: translateX(5px);
                                }

                                .important-links .link-item .form-group {
                                    margin-bottom: 15px;
                                }

                                .important-links .link-item label {
                                    display: block;
                                    font-weight: 600;
                                    margin-bottom: 8px;
                                    color: #2d3748;
                                    font-size: 14px;
                                }

                                .important-links .link-item input {
                                    width: 100%;
                                    padding: 10px 15px;
                                    border: 2px solid #e2e8f0;
                                    border-radius: 8px;
                                    font-size: 14px;
                                    transition: all 0.3s ease;
                                }

                                .important-links .link-item input:focus {
                                    border-color: #667eea;
                                    outline: none;
                                    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
                                }

                                .important-links .remove-link-btn {
                                    background: linear-gradient(135deg, #ee0979 0%, #ff6a00 100%);
                                    color: #fff;
                                    border: none;
                                    padding: 10px 20px;
                                    border-radius: 6px;
                                    cursor: pointer;
                                    font-weight: 600;
                                    font-size: 14px;
                                    transition: all 0.3s ease;
                                    box-shadow: 0 4px 12px rgba(238, 9, 121, 0.3);
                                }

                                .important-links .remove-link-btn:hover {
                                    transform: translateY(-2px);
                                    box-shadow: 0 6px 18px rgba(238, 9, 121, 0.4);
                                }

                                .important-links .add-link-btn {
                                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                    color: #fff;
                                    border: none;
                                    padding: 12px 30px;
                                    border-radius: 8px;
                                    cursor: pointer;
                                    font-weight: 700;
                                    font-size: 15px;
                                    margin-top: 15px;
                                    transition: all 0.3s ease;
                                    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
                                    display: inline-flex;
                                    align-items: center;
                                    gap: 8px;
                                }

                                .important-links .add-link-btn:hover {
                                    transform: translateY(-3px);
                                    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
                                }
                            </style>

                            <div id="important-links-container">
                                <?php
                                // Fetch existing important links
                                global $wpdb;
                                $links_data = $wpdb->get_var("SELECT option_value FROM sm_options WHERE option_name = 'important_links'");
                                $important_links = $links_data ? json_decode($links_data, true) : [];

                                // Default links if empty
                                if (empty($important_links)) {
                                    $important_links = [
                                        ['title' => 'Sylhet Education Board', 'url' => 'http://www.sylhetboard.gov.bd'],
                                        ['title' => 'Ministry of Education', 'url' => 'http://www.moedu.gov.bd'],
                                        ['title' => 'BD National Portal', 'url' => 'http://www.bangladesh.gov.bd'],
                                        ['title' => 'DSHE', 'url' => 'http://www.dshe.gov.bd']
                                    ];
                                }

                                foreach ($important_links as $index => $link):
                                ?>
                                    <div class="link-item" style="width:100%;max-width: 600px;">
                                        <button type="button"
                                            class="remove-link-btn"
                                            onclick="removeImportantLink(this)"
                                            style="float: right;">
                                            <i class="dashicons dashicons-trash" style="vertical-align: middle;"></i> Remove
                                        </button>

                                        <div class="form-group">
                                            <label>Link Title:</label>
                                            <input type="text"
                                                name="important_link_title[]"
                                                value="<?= esc_attr($link['title'] ?? ''); ?>"
                                                placeholder="e.g., Ministry of Education"
                                                required>
                                        </div>

                                        <div class="form-group">
                                            <label>Link URL:</label>
                                            <input type="url"
                                                name="important_link_url[]"
                                                value="<?= esc_url($link['url'] ?? ''); ?>"
                                                placeholder="https://example.com"
                                                required>
                                        </div>
                                        <div style="clear: both;"></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <button type="button"
                                class="add-link-btn"
                                onclick="addImportantLink()">
                                <i class="dashicons dashicons-plus-alt" style="font-size: 20px;"></i>
                                Add New Link
                            </button>

                            <script>
                                function addImportantLink() {
                                    const container = document.getElementById('important-links-container');
                                    const newRow = document.createElement('div');
                                    newRow.className = 'link-item';
                                    newRow.innerHTML = `
                                        <button type="button" 
                                                class="remove-link-btn" 
                                                onclick="removeImportantLink(this)"
                                                style="float: right;">
                                            <i class="dashicons dashicons-trash" style="vertical-align: middle;"></i> Remove
                                        </button>
                                        
                                        <div class="form-group">
                                            <label>Link Title:</label>
                                            <input type="text" 
                                                name="important_link_title[]" 
                                                placeholder="e.g., Ministry of Education"
                                                required>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label>Link URL:</label>
                                            <input type="url" 
                                                name="important_link_url[]" 
                                                placeholder="https://example.com"
                                                required>
                                        </div>
                                        <div style="clear: both;"></div>
                                    `;
                                    container.appendChild(newRow);

                                    // Smooth scroll to new item
                                    newRow.scrollIntoView({
                                        behavior: 'smooth',
                                        block: 'center'
                                    });
                                }

                                function removeImportantLink(button) {
                                    if (confirm('Are you sure you want to remove this link?')) {
                                        button.closest('.link-item').remove();
                                    }
                                }
                            </script>
                        </div>
                    </div>

                    <div id="classwise" class="tab-content">
                        <!-- Class-wise Students Count -->
                        <div class="class-wise-students">
                            <h3 style="color: #2d3748; font-size: 24px; margin-bottom: 20px;">
                                <i class="dashicons dashicons-welcome-learn-more" style="font-size: 28px; vertical-align: middle;"></i>
                                Class-wise Students Count
                            </h3>
                            <p style="color: #718096; margin-bottom: 15px;">
                                Manage the number of students in each class. This data will be displayed on the homepage and other pages.
                            </p>

                            <div style="margin-bottom: 25px;">
                                <button
                                    type="button"
                                    id="setAllFromDb"
                                    style="background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);">
                                    <i class="dashicons dashicons-database-import" style="vertical-align: middle; margin-top: -2px;"></i> Set All Classes from Database
                                </button>
                            </div>

                            <style>
                                .class-wise-container {
                                    background: linear-gradient(135deg, #f6f8fb 0%, #ffffff 100%);
                                    padding: 30px;
                                    border-radius: 15px;
                                    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
                                    border: 1px solid #e2e8f0;
                                }

                                .class-row {
                                    display: flex;
                                    gap: 20px;
                                    align-items: center;
                                    background: white;
                                    padding: 20px;
                                    margin-bottom: 15px;
                                    border-radius: 10px;
                                    border: 2px solid transparent;
                                    transition: all 0.3s ease;
                                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
                                }

                                .class-row:hover {
                                    border-color: #667eea;
                                    transform: translateY(-2px);
                                    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.15);
                                }

                                .class-label {
                                    font-weight: 600;
                                    color: #2d3748;
                                    font-size: 15px;
                                    display: flex;
                                    align-items: center;
                                    gap: 10px;
                                }

                                .class-label .class-icon {
                                    width: 35px;
                                    height: 35px;
                                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                    border-radius: 8px;
                                    display: flex;
                                    align-items: center;
                                    justify-content: center;
                                    color: white;
                                    font-weight: bold;
                                    font-size: 14px;
                                }

                                .class-input {
                                    padding: 12px 18px;
                                    border: 2px solid #e2e8f0;
                                    border-radius: 8px;
                                    font-size: 15px;
                                    transition: all 0.3s;
                                    background: #f8f9fa;
                                }

                                .class-input:focus {
                                    outline: none;
                                    border-color: #667eea;
                                    background: white;
                                    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
                                }

                                .class-total-badge {
                                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                    color: white;
                                    padding: 8px 16px;
                                    border-radius: 20px;
                                    font-weight: 600;
                                    text-align: center;
                                    font-size: 14px;
                                    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
                                }

                                .total-summary {
                                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                    color: white;
                                    padding: 25px;
                                    border-radius: 12px;
                                    margin-top: 25px;
                                    text-align: center;
                                    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
                                }

                                .total-summary h4 {
                                    margin: 0 0 10px 0;
                                    font-size: 16px;
                                    opacity: 0.95;
                                }

                                .total-summary .total-number {
                                    font-size: 36px;
                                    font-weight: bold;
                                    margin: 0;
                                }

                                @media (max-width: 768px) {
                                    .class-row {
                                        grid-template-columns: 1fr;
                                        gap: 10px;
                                    }
                                }
                            </style>

                            <div class="class-wise-container">
                                <?php
                                // Get class-wise student data from sm_options
                                $class_wise_data = $wpdb->get_var("SELECT option_value FROM sm_options WHERE option_name = 'class_wise_students'");
                                $class_students = $class_wise_data ? json_decode($class_wise_data, true) : array();

                                // Default classes (customize based on your school structure)
                                $classes = array();

                                // Fetch class names and order from ct_class table ordered by classOrder
                                $results = $wpdb->get_results("SELECT className, classOrder FROM ct_class ORDER BY classOrder ASC, className ASC");

                                if (!empty($results)) {
                                    foreach ($results as $row) {
                                        $name = trim((string)$row->className);
                                        if ($name === '') {
                                            continue;
                                        }

                                        // Build a stable, unique key for each class
                                        $key = 'class_' . preg_replace('/\s+/', '_', strtolower($name));
                                        $classes[$key] = array(
                                            'name' => $name,
                                            'order' => isset($row->classOrder) ? intval($row->classOrder) : 0
                                        );
                                    }
                                } else {
                                    // Fallback to default class names if ct_class table is empty
                                    for ($i = 1; $i <= 12; $i++) {
                                        $classes['class_' . $i] = array('name' => 'Class ' . $i, 'order' => $i);
                                    }
                                }

                                $total = 0;
                                foreach ($classes as $class_key => $class_info):
                                    $class_name = is_array($class_info) ? $class_info['name'] : $class_info;
                                    $class_order = is_array($class_info) ? $class_info['order'] : 0;
                                    $count = isset($class_students[$class_key]) ? intval($class_students[$class_key]) : 0;
                                    $total += $count;
                                ?>
                                    <div class="class-row">
                                        <div class="class-label">
                                            <span class="badge" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-size: 11px; padding: 4px 8px; border-radius: 4px; margin-right: 8px; font-weight: 600;">
                                                #<?php echo esc_html($class_order); ?>
                                            </span>
                                            <span style="min-width: 100px;"><?php echo esc_html($class_name); ?></span>
                                        </div>
                                        <input
                                            type="number"
                                            name="class_wise_students[<?php echo esc_attr($class_key); ?>]"
                                            value="<?php echo esc_attr($count); ?>"
                                            class="class-input class-student-count"
                                            data-class="<?php echo esc_attr($class_key); ?>"
                                            min="0"
                                            style="max-width: 200px;"
                                            placeholder="Enter number of students">
                                        <button
                                            type="button"
                                            class="set-from-db-btn"
                                            data-class="<?php echo esc_attr($class_key); ?>"
                                            style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 13px; transition: all 0.3s ease; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3); min-width: 140px;">
                                            <i class="dashicons dashicons-database" style="vertical-align: middle; margin-top: -2px;"></i>
                                            <span class="db-count-label">Loading...</span>
                                        </button>
                                    </div>
                                <?php endforeach; ?>

                                <div class="total-summary">
                                    <h4><i class="dashicons dashicons-groups" style="font-size: 24px; vertical-align: middle;"></i> Total Students (All Classes)</h4>
                                    <p class="total-number" id="totalClassStudents"><?php echo number_format($total); ?></p>
                                </div>
                            </div>

                            <script>
                                jQuery(document).ready(function($) {
                                    // Store database counts globally
                                    let dbCounts = {};

                                    // Update total when any class count changes
                                    function updateTotals() {
                                        let total = 0;
                                        $('.class-student-count').each(function() {
                                            let value = parseInt($(this).val()) || 0;
                                            total += value;
                                        });

                                        // Update total display
                                        $('#totalClassStudents').text(total.toLocaleString());

                                        // Also update the main total students field
                                        $('#totalStudents').val(total);
                                    }

                                    // Function to load database counts and update button labels
                                    function loadDatabaseCounts() {
                                        $.ajax({
                                            url: window.location.href,
                                            type: 'POST',
                                            data: {
                                                action: 'get_class_students_from_db'
                                            },
                                            success: function(response) {
                                                if (response.success && response.data) {
                                                    dbCounts = response.data;

                                                    // Update all button labels with counts
                                                    $('.set-from-db-btn').each(function() {
                                                        const $btn = $(this);
                                                        const classKey = $btn.data('class');
                                                        const count = dbCounts[classKey] || 0;

                                                        $btn.find('.db-count-label').html('Set From DB: <strong>' + count + '</strong>');
                                                    });
                                                }
                                            },
                                            error: function() {
                                                $('.set-from-db-btn .db-count-label').text('Set From DB: N/A');
                                            }
                                        });
                                    }

                                    // Load counts on page load
                                    loadDatabaseCounts();

                                    $('.class-student-count').on('input', updateTotals);

                                    // Handle "Set All from DB" button click
                                    $('#setAllFromDb').on('click', function() {
                                        const $btn = $(this);

                                        // Show loading state
                                        $btn.prop('disabled', true).html('<i class="dashicons dashicons-update" style="vertical-align: middle; animation: spin 1s linear infinite;"></i> Loading from Database...');

                                        // Make AJAX request
                                        $.ajax({
                                            url: window.location.href,
                                            type: 'POST',
                                            data: {
                                                action: 'get_class_students_from_db'
                                            },
                                            success: function(response) {
                                                if (response.success && response.data) {
                                                    // Update all input fields with database values
                                                    let updatedCount = 0;
                                                    $.each(response.data, function(classKey, count) {
                                                        const $input = $('.class-student-count[data-class="' + classKey + '"]');
                                                        if ($input.length) {
                                                            $input.val(count);
                                                            updatedCount++;
                                                        }
                                                    });

                                                    updateTotals();

                                                    // Show success state
                                                    $btn.css('background', 'linear-gradient(135deg, #22c55e 0%, #16a34a 100%)')
                                                        .html('<i class="dashicons dashicons-yes" style="vertical-align: middle;"></i> Updated ' + updatedCount + ' classes!');

                                                    setTimeout(function() {
                                                        $btn.prop('disabled', false)
                                                            .css('background', 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)')
                                                            .html('<i class="dashicons dashicons-database-import" style="vertical-align: middle; margin-top: -2px;"></i> Set All Classes from Database');
                                                    }, 3000);
                                                } else {
                                                    throw new Error('Invalid response');
                                                }
                                            },
                                            error: function() {
                                                // Show error state
                                                $btn.css('background', 'linear-gradient(135deg, #ef4444 0%, #dc2626 100%)')
                                                    .html('<i class="dashicons dashicons-no" style="vertical-align: middle;"></i> Error loading data!');

                                                setTimeout(function() {
                                                    $btn.prop('disabled', false)
                                                        .css('background', 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)')
                                                        .html('<i class="dashicons dashicons-database-import" style="vertical-align: middle; margin-top: -2px;"></i> Set All Classes from Database');
                                                }, 3000);
                                            }
                                        });
                                    });

                                    // Handle hover effect for "Set All from DB" button
                                    $(document).on('mouseenter', '#setAllFromDb', function() {
                                        if (!$(this).prop('disabled')) {
                                            $(this).css({
                                                'transform': 'translateY(-2px)',
                                                'box-shadow': '0 6px 20px rgba(59, 130, 246, 0.4)'
                                            });
                                        }
                                    }).on('mouseleave', '#setAllFromDb', function() {
                                        $(this).css({
                                            'transform': 'translateY(0)',
                                            'box-shadow': '0 4px 12px rgba(59, 130, 246, 0.3)'
                                        });
                                    });

                                    // Handle "Set from DB" button click
                                    $('.set-from-db-btn').on('click', function() {
                                        const $btn = $(this);
                                        const classKey = $btn.data('class');
                                        const $input = $('.class-student-count[data-class="' + classKey + '"]');
                                        const dbCount = dbCounts[classKey] || 0;

                                        // Show loading state
                                        $btn.prop('disabled', true).html('<i class="dashicons dashicons-update" style="vertical-align: middle; animation: spin 1s linear infinite;"></i> <span class="db-count-label">Setting...</span>');

                                        // Set the value directly from cached dbCounts
                                        $input.val(dbCount);
                                        updateTotals();

                                        // Show success state
                                        $btn.css('background', 'linear-gradient(135deg, #22c55e 0%, #16a34a 100%)')
                                            .html('<i class="dashicons dashicons-yes" style="vertical-align: middle;"></i> <span class="db-count-label">Set: <strong>' + dbCount + '</strong></span>');

                                        setTimeout(function() {
                                            $btn.prop('disabled', false)
                                                .css('background', 'linear-gradient(135deg, #10b981 0%, #059669 100%)')
                                                .html('<i class="dashicons dashicons-database" style="vertical-align: middle; margin-top: -2px;"></i> <span class="db-count-label">Set From DB: <strong>' + dbCount + '</strong></span>');
                                        }, 1500);
                                    });

                                    // Add hover effect for buttons
                                    $(document).on('mouseenter', '.set-from-db-btn', function() {
                                        if (!$(this).prop('disabled')) {
                                            $(this).css({
                                                'transform': 'translateY(-2px)',
                                                'box-shadow': '0 6px 20px rgba(16, 185, 129, 0.4)'
                                            });
                                        }
                                    }).on('mouseleave', '.set-from-db-btn', function() {
                                        $(this).css({
                                            'transform': 'translateY(0)',
                                            'box-shadow': '0 2px 8px rgba(16, 185, 129, 0.3)'
                                        });
                                    });
                                });

                                // Add CSS for spin animation
                                const style = document.createElement('style');
                                style.textContent = `
                                    @keyframes spin {
                                        from { transform: rotate(0deg); }
                                        to { transform: rotate(360deg); }
                                    }
                                `;
                                document.head.appendChild(style);
                            </script>
                        </div>
                    </div>

                    <!-- Student Demographics Section -->
                    <div id="demographics" class="tab-content">
                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);">
                            <div style="display: flex; align-items: center; margin-bottom: 15px;">
                                <span class="dashicons dashicons-groups" style="font-size: 42px; color: white; margin-right: 20px;"></span>
                                <div>
                                    <h2 style="color: white; margin: 0; font-size: 28px; font-weight: 700;">Student Demographics</h2>
                                    <p style="color: rgba(255,255,255,0.9); margin: 5px 0 0 0; font-size: 14px;">Manage gender and religious demographics data</p>
                                </div>
                            </div>
                        </div>

                        <!-- Gender Demographics Card -->
                        <div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); padding: 25px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 6px 24px rgba(240, 147, 251, 0.25);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                <h3 style="color: white; margin: 0; font-size: 20px; font-weight: 600; display: flex; align-items: center;">
                                    <span class="dashicons dashicons-admin-users" style="margin-right: 10px; font-size: 24px;"></span>
                                    Gender Distribution
                                </h3>
                                <button type="button" id="set-gender-from-db" class="button button-secondary" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3); transition: all 0.3s ease; min-width: 160px;">
                                    <span class="dashicons dashicons-database" style="vertical-align: middle; margin-right: 5px;"></span>
                                    <span class="db-gender-label">Loading...</span>
                                </button>
                            </div>

                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                                <!-- Total Students -->
                                <div style="background: rgba(255,255,255,0.95); padding: 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                                    <label style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 600; font-size: 14px;">
                                        <span class="dashicons dashicons-groups" style="color: #667eea; vertical-align: middle; margin-right: 5px;"></span>
                                        Total Students
                                    </label>
                                    <input type="number"
                                        name="student_demographics[total_students]"
                                        id="demographics_total_students"
                                        class="demographics-input"
                                        value="<?php echo esc_attr($redux_options['demographics_total_students'] ?? 0); ?>"
                                        min="0"
                                        style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 16px; font-weight: 600; color: #1e293b; transition: all 0.3s ease;">
                                </div>

                                <!-- Boys -->
                                <div style="background: rgba(255,255,255,0.95); padding: 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                                    <label style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 600; font-size: 14px;">
                                        <span class="dashicons dashicons-businessman" style="color: #3b82f6; vertical-align: middle; margin-right: 5px;"></span>
                                        Boys
                                    </label>
                                    <input type="number"
                                        name="student_demographics[boys]"
                                        id="demographics_boys"
                                        class="demographics-input demographics-gender"
                                        value="<?php echo esc_attr($redux_options['demographics_boys'] ?? 0); ?>"
                                        min="0"
                                        style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 16px; font-weight: 600; color: #1e293b; transition: all 0.3s ease;">
                                </div>

                                <!-- Girls -->
                                <div style="background: rgba(255,255,255,0.95); padding: 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                                    <label style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 600; font-size: 14px;">
                                        <span class="dashicons dashicons-businesswoman" style="color: #ec4899; vertical-align: middle; margin-right: 5px;"></span>
                                        Girls
                                    </label>
                                    <input type="number"
                                        name="student_demographics[girls]"
                                        id="demographics_girls"
                                        class="demographics-input demographics-gender"
                                        value="<?php echo esc_attr($redux_options['demographics_girls'] ?? 0); ?>"
                                        min="0"
                                        style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 16px; font-weight: 600; color: #1e293b; transition: all 0.3s ease;">
                                </div>

                                <!-- Other -->
                                <div style="background: rgba(255,255,255,0.95); padding: 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                                    <label style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 600; font-size: 14px;">
                                        <span class="dashicons dashicons-admin-users" style="color: #8b5cf6; vertical-align: middle; margin-right: 5px;"></span>
                                        Other
                                    </label>
                                    <input type="number"
                                        name="student_demographics[gender_other]"
                                        id="demographics_gender_other"
                                        class="demographics-input demographics-gender"
                                        value="<?php echo esc_attr($redux_options['demographics_gender_other'] ?? 0); ?>"
                                        min="0"
                                        style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 16px; font-weight: 600; color: #1e293b; transition: all 0.3s ease;">
                                </div>
                            </div>

                            <!-- Gender Total Display -->
                            <div id="gender-total-display" style="margin-top: 20px; padding: 15px; background: rgba(255,255,255,0.9); border-radius: 8px; text-align: center; font-size: 16px; font-weight: 600; color: #1e293b;">
                                Gender Total: <span id="gender-total-count" style="color: #667eea;">0</span>
                                <span id="gender-match-indicator" style="margin-left: 15px;"></span>
                            </div>
                        </div>

                        <!-- Religious Demographics Card -->
                        <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); padding: 25px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 6px 24px rgba(79, 172, 254, 0.25);">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                <h3 style="color: white; margin: 0; font-size: 20px; font-weight: 600; display: flex; align-items: center;">
                                    <span class="dashicons dashicons-heart" style="margin-right: 10px; font-size: 24px;"></span>
                                    Religious Distribution
                                </h3>
                                <button type="button" id="set-religion-from-db" class="button button-secondary" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3); transition: all 0.3s ease; min-width: 160px;">
                                    <span class="dashicons dashicons-database" style="vertical-align: middle; margin-right: 5px;"></span>
                                    <span class="db-religion-label">Loading...</span>
                                </button>
                            </div>

                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                                <!-- Muslim -->
                                <div style="background: rgba(255,255,255,0.95); padding: 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                                    <label style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 600; font-size: 14px;">
                                        <span class="dashicons dashicons-star-filled" style="color: #22c55e; vertical-align: middle; margin-right: 5px;"></span>
                                        Muslim
                                    </label>
                                    <input type="number"
                                        name="student_demographics[muslim]"
                                        id="demographics_muslim"
                                        class="demographics-input demographics-religion"
                                        value="<?php echo esc_attr($redux_options['demographics_muslim'] ?? 0); ?>"
                                        min="0"
                                        style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 16px; font-weight: 600; color: #1e293b; transition: all 0.3s ease;">
                                </div>

                                <!-- Hinduism -->
                                <div style="background: rgba(255,255,255,0.95); padding: 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                                    <label style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 600; font-size: 14px;">
                                        <span class="dashicons dashicons-star-filled" style="color: #f97316; vertical-align: middle; margin-right: 5px;"></span>
                                        Hinduism
                                    </label>
                                    <input type="number"
                                        name="student_demographics[hinduism]"
                                        id="demographics_hinduism"
                                        class="demographics-input demographics-religion"
                                        value="<?php echo esc_attr($redux_options['demographics_hinduism'] ?? 0); ?>"
                                        min="0"
                                        style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 16px; font-weight: 600; color: #1e293b; transition: all 0.3s ease;">
                                </div>

                                <!-- Buddhist -->
                                <div style="background: rgba(255,255,255,0.95); padding: 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                                    <label style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 600; font-size: 14px;">
                                        <span class="dashicons dashicons-star-filled" style="color: #eab308; vertical-align: middle; margin-right: 5px;"></span>
                                        Buddhist
                                    </label>
                                    <input type="number"
                                        name="student_demographics[buddhist]"
                                        id="demographics_buddhist"
                                        class="demographics-input demographics-religion"
                                        value="<?php echo esc_attr($redux_options['demographics_buddhist'] ?? 0); ?>"
                                        min="0"
                                        style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 16px; font-weight: 600; color: #1e293b; transition: all 0.3s ease;">
                                </div>

                                <!-- Christian -->
                                <div style="background: rgba(255,255,255,0.95); padding: 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                                    <label style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 600; font-size: 14px;">
                                        <span class="dashicons dashicons-star-filled" style="color: #8b5cf6; vertical-align: middle; margin-right: 5px;"></span>
                                        Christian
                                    </label>
                                    <input type="number"
                                        name="student_demographics[christian]"
                                        id="demographics_christian"
                                        class="demographics-input demographics-religion"
                                        value="<?php echo esc_attr($redux_options['demographics_christian'] ?? 0); ?>"
                                        min="0"
                                        style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 16px; font-weight: 600; color: #1e293b; transition: all 0.3s ease;">
                                </div>

                                <!-- Other -->
                                <div style="background: rgba(255,255,255,0.95); padding: 20px; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
                                    <label style="display: block; margin-bottom: 10px; color: #1e293b; font-weight: 600; font-size: 14px;">
                                        <span class="dashicons dashicons-star-filled" style="color: #64748b; vertical-align: middle; margin-right: 5px;"></span>
                                        Other
                                    </label>
                                    <input type="number"
                                        name="student_demographics[other]"
                                        id="demographics_other"
                                        class="demographics-input demographics-religion"
                                        value="<?php echo esc_attr($redux_options['demographics_other'] ?? 0); ?>"
                                        min="0"
                                        style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 16px; font-weight: 600; color: #1e293b; transition: all 0.3s ease;">
                                </div>
                            </div>

                            <!-- Religion Total Display -->
                            <div id="religion-total-display" style="margin-top: 20px; padding: 15px; background: rgba(255,255,255,0.9); border-radius: 8px; text-align: center; font-size: 16px; font-weight: 600; color: #1e293b;">
                                Religion Total: <span id="religion-total-count" style="color: #4facfe;">0</span>
                                <span id="religion-match-indicator" style="margin-left: 15px;"></span>
                            </div>
                        </div>

                        <!-- Summary Card -->
                        <div style="background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); padding: 25px; border-radius: 12px; box-shadow: 0 6px 24px rgba(252, 182, 159, 0.25);">
                            <h3 style="color: #7c2d12; margin: 0 0 15px 0; font-size: 18px; font-weight: 600;">
                                <span class="dashicons dashicons-info" style="vertical-align: middle; margin-right: 8px;"></span>
                                Validation Summary
                            </h3>
                            <div id="validation-messages" style="color: #7c2d12; font-size: 14px; line-height: 1.8;">
                                <p style="margin: 5px 0;">✓ All fields will be validated on save</p>
                                <p style="margin: 5px 0;">✓ Gender total should equal religion total</p>
                                <p style="margin: 5px 0;">✓ Both totals should match the Total Students count</p>
                            </div>
                        </div>

                        <!-- JavaScript for Auto-calculation -->
                        <script>
                            jQuery(document).ready(function($) {
                                // Store database counts globally
                                let dbGenderData = {};
                                let dbReligionData = {};

                                function calculateDemographics() {
                                    // Get values
                                    const totalStudents = parseInt($('#demographics_total_students').val()) || 0;
                                    const boys = parseInt($('#demographics_boys').val()) || 0;
                                    const girls = parseInt($('#demographics_girls').val()) || 0;
                                    const genderOther = parseInt($('#demographics_gender_other').val()) || 0;
                                    const muslim = parseInt($('#demographics_muslim').val()) || 0;
                                    const hinduism = parseInt($('#demographics_hinduism').val()) || 0;
                                    const buddhist = parseInt($('#demographics_buddhist').val()) || 0;
                                    const christian = parseInt($('#demographics_christian').val()) || 0;
                                    const other = parseInt($('#demographics_other').val()) || 0;

                                    // Calculate totals
                                    const genderTotal = boys + girls + genderOther;
                                    const religionTotal = muslim + hinduism + buddhist + christian + other;

                                    // Update displays
                                    $('#gender-total-count').text(genderTotal);
                                    $('#religion-total-count').text(religionTotal);

                                    // Gender validation
                                    const genderIndicator = $('#gender-match-indicator');
                                    if (genderTotal === totalStudents && totalStudents > 0) {
                                        genderIndicator.html('<span style="color: #22c55e;">✓ Matches Total</span>');
                                    } else if (genderTotal > 0) {
                                        genderIndicator.html('<span style="color: #ef4444;">✗ Does not match (' + totalStudents + ')</span>');
                                    } else {
                                        genderIndicator.html('');
                                    }

                                    // Religion validation
                                    const religionIndicator = $('#religion-match-indicator');
                                    if (religionTotal === totalStudents && totalStudents > 0) {
                                        religionIndicator.html('<span style="color: #22c55e;">✓ Matches Total</span>');
                                    } else if (religionTotal > 0) {
                                        religionIndicator.html('<span style="color: #ef4444;">✗ Does not match (' + totalStudents + ')</span>');
                                    } else {
                                        religionIndicator.html('');
                                    }

                                    // Update validation messages
                                    const messages = $('#validation-messages');
                                    let html = '';

                                    if (totalStudents === 0) {
                                        html += '<p style="margin: 5px 0; color: #64748b;">ℹ Enter Total Students to begin</p>';
                                    } else {
                                        if (genderTotal === totalStudents) {
                                            html += '<p style="margin: 5px 0; color: #22c55e;">✓ Gender distribution is correct</p>';
                                        } else {
                                            html += '<p style="margin: 5px 0; color: #ef4444;">✗ Gender total (' + genderTotal + ') should equal Total Students (' + totalStudents + ')</p>';
                                        }

                                        if (religionTotal === totalStudents) {
                                            html += '<p style="margin: 5px 0; color: #22c55e;">✓ Religious distribution is correct</p>';
                                        } else {
                                            html += '<p style="margin: 5px 0; color: #ef4444;">✗ Religion total (' + religionTotal + ') should equal Total Students (' + totalStudents + ')</p>';
                                        }

                                        if (genderTotal === religionTotal && genderTotal === totalStudents) {
                                            html += '<p style="margin: 5px 0; color: #22c55e; font-weight: 700;">✓ All demographics are valid!</p>';
                                        }
                                    }

                                    messages.html(html);
                                }

                                // Function to load gender data from database and update button label
                                function loadGenderData() {
                                    console.log('Loading gender data...');
                                    $.ajax({
                                        url: window.location.href,
                                        type: 'POST',
                                        data: {
                                            action: 'get_gender_distribution_from_db'
                                        },
                                        success: function(response) {
                                            console.log('Gender response:', response);
                                            if (response.success && response.data) {
                                                dbGenderData = response.data;
                                                const total = dbGenderData.total || 0;
                                                $('#set-gender-from-db .db-gender-label').html('Set From DB');
                                                console.log('Gender data loaded:', dbGenderData);
                                            } else {
                                                console.error('Gender response not successful or no data');
                                                $('#set-gender-from-db .db-gender-label').text('Set From DB: Error');
                                            }
                                        },
                                        error: function(xhr, status, error) {
                                            console.error('Gender data load error:', error, 'Status:', status, 'Response:', xhr.responseText);
                                            $('#set-gender-from-db .db-gender-label').text('Set From DB: N/A');
                                        }
                                    });
                                }

                                // Function to load religion data from database and update button label
                                function loadReligionData() {
                                    console.log('Loading religion data...');
                                    $.ajax({
                                        url: window.location.href,
                                        type: 'POST',
                                        data: {
                                            action: 'get_religion_distribution_from_db'
                                        },
                                        success: function(response) {
                                            console.log('Religion response:', response);
                                            if (response.success && response.data) {
                                                dbReligionData = response.data;
                                                // Calculate total from religion data
                                                const total = (dbReligionData.Muslim || 0) + (dbReligionData.Hinduism || 0) +
                                                    (dbReligionData.Buddist || 0) + (dbReligionData.Christian || 0) +
                                                    (dbReligionData.other || 0);
                                                $('#set-religion-from-db .db-religion-label').html('Set From DB');
                                                console.log('Religion data loaded:', dbReligionData);
                                            } else {
                                                console.error('Religion response not successful or no data');
                                                $('#set-religion-from-db .db-religion-label').text('Set From DB: Error');
                                            }
                                        },
                                        error: function(xhr, status, error) {
                                            console.error('Religion data load error:', error, 'Status:', status, 'Response:', xhr.responseText);
                                            $('#set-religion-from-db .db-religion-label').text('Set From DB: N/A');
                                        }
                                    });
                                }

                                // Load counts on page load
                                loadGenderData();
                                loadReligionData();

                                // Bind events
                                $('.demographics-input').on('input change', calculateDemographics);

                                // Initial calculation
                                calculateDemographics();

                                // Add focus/blur effects
                                $('.demographics-input').on('focus', function() {
                                    $(this).css({
                                        'border-color': '#667eea',
                                        'box-shadow': '0 0 0 3px rgba(102, 126, 234, 0.1)',
                                        'transform': 'translateY(-2px)'
                                    });
                                }).on('blur', function() {
                                    $(this).css({
                                        'border-color': '#e2e8f0',
                                        'box-shadow': 'none',
                                        'transform': 'translateY(0)'
                                    });
                                });

                                // Set Gender From DB button - instant set with cached data
                                $('#set-gender-from-db').on('click', function() {
                                    const $btn = $(this);

                                    // Show loading state
                                    $btn.prop('disabled', true).html('<i class="dashicons dashicons-update" style="vertical-align: middle; animation: spin 1s linear infinite;"></i> <span class="db-gender-label">Setting...</span>');

                                    // Set the values directly from cached dbGenderData
                                    $('#demographics_boys').val(dbGenderData.boys || 0);
                                    $('#demographics_girls').val(dbGenderData.girls || 0);
                                    $('#demographics_gender_other').val(dbGenderData.other || 0);
                                    $('#demographics_total_students').val(dbGenderData.total || 0);
                                    calculateDemographics();

                                    // Show success state
                                    $btn.css('background', 'linear-gradient(135deg, #22c55e 0%, #16a34a 100%)')
                                        .html('<i class="dashicons dashicons-yes" style="vertical-align: middle;"></i> <span class="db-gender-label">Set: <strong>' + (dbGenderData.total || 0) + '</strong></span>');

                                    setTimeout(function() {
                                        $btn.prop('disabled', false)
                                            .css('background', 'linear-gradient(135deg, #10b981 0%, #059669 100%)')
                                            .html('<i class="dashicons dashicons-database" style="vertical-align: middle; margin-right: 5px;"></i> <span class="db-gender-label">Set From DB</span>');
                                    }, 1500);
                                });

                                // Set Religion From DB button - instant set with cached data
                                $('#set-religion-from-db').on('click', function() {
                                    const $btn = $(this);
                                    const total = (dbReligionData.Muslim || 0) + (dbReligionData.Hinduism || 0) +
                                        (dbReligionData.Buddist || 0) + (dbReligionData.Christian || 0) +
                                        (dbReligionData.other || 0);

                                    // Show loading state
                                    $btn.prop('disabled', true).html('<i class="dashicons dashicons-update" style="vertical-align: middle; animation: spin 1s linear infinite;"></i> <span class="db-religion-label">Setting...</span>');

                                    // Set the values directly from cached dbReligionData
                                    $('#demographics_muslim').val(dbReligionData.Muslim || 0);
                                    $('#demographics_hinduism').val(dbReligionData.Hinduism || 0);
                                    $('#demographics_buddhist').val(dbReligionData.Buddist || 0);
                                    $('#demographics_christian').val(dbReligionData.Christian || 0);
                                    $('#demographics_other').val(dbReligionData.other || 0);
                                    calculateDemographics();

                                    // Show success state
                                    $btn.css('background', 'linear-gradient(135deg, #22c55e 0%, #16a34a 100%)')
                                        .html('<i class="dashicons dashicons-yes" style="vertical-align: middle;"></i> <span class="db-religion-label">Set: <strong>' + total + '</strong></span>');

                                    setTimeout(function() {
                                        $btn.prop('disabled', false)
                                            .css('background', 'linear-gradient(135deg, #10b981 0%, #059669 100%)')
                                            .html('<i class="dashicons dashicons-database" style="vertical-align: middle; margin-right: 5px;"></i> <span class="db-religion-label">Set From DB</span>');
                                    }, 1500);
                                });

                                // Add hover effect for "Set Gender From DB" button
                                $(document).on('mouseenter', '#set-gender-from-db', function() {
                                    if (!$(this).prop('disabled')) {
                                        $(this).css({
                                            'transform': 'translateY(-2px)',
                                            'box-shadow': '0 6px 20px rgba(16, 185, 129, 0.4)'
                                        });
                                    }
                                }).on('mouseleave', '#set-gender-from-db', function() {
                                    $(this).css({
                                        'transform': 'translateY(0)',
                                        'box-shadow': '0 2px 8px rgba(16, 185, 129, 0.3)'
                                    });
                                });

                                // Add hover effect for "Set Religion From DB" button
                                $(document).on('mouseenter', '#set-religion-from-db', function() {
                                    if (!$(this).prop('disabled')) {
                                        $(this).css({
                                            'transform': 'translateY(-2px)',
                                            'box-shadow': '0 6px 20px rgba(16, 185, 129, 0.4)'
                                        });
                                    }
                                }).on('mouseleave', '#set-religion-from-db', function() {
                                    $(this).css({
                                        'transform': 'translateY(0)',
                                        'box-shadow': '0 2px 8px rgba(16, 185, 129, 0.3)'
                                    });
                                });
                            });
                        </script>
                    </div>

                    <br>

                    <div class="text-center" style="position: fixed; bottom: 0px; left: 50%; width: 100%; transform: translateX(-50%); background: rgba(0, 0, 0, 0.9); padding: 20px 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); z-index: 1000;">
                        <input type="submit" name="save_options" value="Save Options" class="btn btn-primary text-center">
                    </div>

                </form>
            </div>


        </div>
    </div>

<?php
} else {
    echo '<p>You do not have permission to edit these options.</p>';
}
?>
<style>
    .button {
        display: inline-block;
        padding: 10px 20px;
        margin: 5px;
        background-color: #0073aa;
        /* or your preferred color */
        color: white;
        text-align: center;
        border: none;
        border-radius: 3px;
        cursor: pointer;
    }

    .button:hover {
        background-color: #005177;
        /* darker shade for hover */
    }

    .remove-image {
        background-color: red;
        /* color for remove button */
    }

    .regular-text {
        width: 100%;
        margin-top: 5px;
    }

    .large-text {
        width: 100%;
    }

    .upload {
        background: none;
    }
</style>
<script>
    jQuery(document).ready(function($) {
        $('.media_upload_button').on('click', function(e) {
            e.preventDefault();
            var button = $(this);
            var fieldset = button.closest('fieldset'); // Ensure we target the correct fieldset

            var mediaFrame = wp.media({
                title: 'Select or Upload an Image',
                button: {
                    text: 'Use this image'
                },
                multiple: false
            });

            mediaFrame.on('select', function() {
                var attachment = mediaFrame.state().get('selection').first().toJSON();

                // Store full attachment URLs (do not convert to relative paths)
                var fullUrl = attachment.url;

                // Ensure we're updating the correct input fields with full URLs
                fieldset.find('.upload').val(fullUrl);
                fieldset.find('.upload-id').val(attachment.id);
                fieldset.find('.upload-height').val(attachment.height);
                fieldset.find('.upload-width').val(attachment.width);
                if (attachment.sizes && attachment.sizes.thumbnail) {
                    // Save thumbnail full URL
                    fieldset.find('.upload-thumbnail').val(attachment.sizes.thumbnail.url);
                    // Display uses full thumbnail URL
                    fieldset.find('.redux-option-image').attr('src', attachment.sizes.thumbnail.url);
                } else {
                    // No thumbnail available - save/display main URL
                    fieldset.find('.upload-thumbnail').val(fullUrl);
                    fieldset.find('.redux-option-image').attr('src', fullUrl);
                }
            });

            mediaFrame.open();
        });

        $('.remove-image').on('click', function(e) {
            e.preventDefault();
            var button = $(this);
            var fieldset = button.closest('fieldset');

            fieldset.find('.upload, .upload-id, .upload-height, .upload-width, .upload-thumbnail').val('');
            fieldset.find('.redux-option-image').attr('src', '');
        });




    });

    function openTab(evt, tabName) {
        var i, tabcontent, tablinks;

        // Hide all tab content
        tabcontent = document.getElementsByClassName("tab-content");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].classList.remove("active");
        }

        // Remove 'active' class from all tab links
        tablinks = document.getElementsByClassName("tab");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].classList.remove("active");
        }

        // Show the current tab content and add 'active' class to the clicked tab
        document.getElementById(tabName).classList.add("active");
        evt.currentTarget.classList.add("active");
    }
</script>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<?php
get_footer();
