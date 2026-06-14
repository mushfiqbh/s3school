<?php
/*
Template Name: Import Export Students
*/

defined('ABSPATH') || exit;

if (!is_user_logged_in()) {
	auth_redirect();
}

if (!current_user_can('manage_options')) {
	wp_die(esc_html__('You do not have permission to access this page.', 's3schoolManagment'));
}

if (!function_exists('s3school_post_value')) {
	function s3school_post_value($key, $default = null)
	{
		return isset($_POST[$key]) ? wp_unslash($_POST[$key]) : $default;
	}
}

if (!function_exists('s3school_format_export_column_label')) {
	function s3school_format_export_column_label($column)
	{
		$custom_labels = [
			'userid'           => __('User ID', 's3schoolManagment'),
			'username'         => __('Username', 's3schoolManagment'),
			'useremail'        => __('Email', 's3schoolManagment'),
			'parentPhone'      => __('Parent Phone', 's3schoolManagment'),
			'className'        => __('Class', 's3schoolManagment'),
			'year'             => __('Year', 's3schoolManagment'),
			'groupName'        => __('Group', 's3schoolManagment'),
			'sectionName'      => __('Section', 's3schoolManagment'),
			'optionalSubjects' => __('Optional Subjects', 's3schoolManagment'),
			'fourthSubject'    => __('4th Subject', 's3schoolManagment'),
			'roll'             => __('Roll', 's3schoolManagment'),
			'instituteName'    => __('Institute', 's3schoolManagment'),
			'eiinNumber'       => __('EIIN No', 's3schoolManagment'),
			'blankColumn'      => __('Blank Column', 's3schoolManagment'),
		];

		$column = (string) $column;
		if (isset($custom_labels[$column])) {
			return $custom_labels[$column];
		}

		$label = ucwords(str_replace(['_', '-'], ' ', $column));
		return $label !== '' ? $label : $column;
	}
}

if (!function_exists('s3school_resolve_report_label')) {
	function s3school_resolve_report_label($column, $custom_labels, $default_labels)
	{
		if (isset($custom_labels[$column]) && $custom_labels[$column] !== '') {
			return $custom_labels[$column];
		}
		return isset($default_labels[$column]) ? $default_labels[$column] : $column;
	}
}

if (!function_exists('s3school_normalize_header_label')) {
	function s3school_normalize_header_label($label)
	{
		if (!is_string($label)) {
			return '';
		}
		$label = trim($label);
		if ($label === '') {
			return '';
		}
		$label = preg_replace('/\s+/', '_', $label);
		if (function_exists('mb_strtolower')) {
			return mb_strtolower($label, 'UTF-8');
		}
		return strtolower($label);
	}
}

if (!function_exists('s3school_categorize_import_fields')) {
	function s3school_categorize_import_fields($student_columns, $additional_export_columns)
	{
		$categories = [
			'personal' => [
				'label' => __('Personal Information', 's3schoolManagment'),
				'fields' => []
			],
			'academic' => [
				'label' => __('Academic Information', 's3schoolManagment'),
				'fields' => []
			],
			'contact' => [
				'label' => __('Contact Information', 's3schoolManagment'),
				'fields' => []
			],
			'system' => [
				'label' => __('System Information', 's3schoolManagment'),
				'fields' => []
			],
			'other' => [
				'label' => __('Other Fields', 's3schoolManagment'),
				'fields' => []
			]
		];

		// Keywords for categorization
		$personal_keywords = ['name', 'email', 'phone', 'address', 'dob', 'birth', 'gender', 'age'];
		$academic_keywords = ['class', 'section', 'year', 'group', 'roll', 'subject', 'grade', 'marks'];
		$contact_keywords = ['parent', 'guardian', 'contact'];
		$system_keywords = ['id', 'user', 'institute', 'eiin', 'created', 'updated', 'status'];

		$all_fields = array_merge($student_columns, $additional_export_columns);

		foreach ($all_fields as $field) {
			$field_lower = strtolower($field);
			if (in_array($field, $additional_export_columns)) {
				// Additional fields are mostly academic
				$categories['academic']['fields'][] = $field;
			} elseif (preg_match('/(' . implode('|', $personal_keywords) . ')/', $field_lower)) {
				$categories['personal']['fields'][] = $field;
			} elseif (preg_match('/(' . implode('|', $academic_keywords) . ')/', $field_lower)) {
				$categories['academic']['fields'][] = $field;
			} elseif (preg_match('/(' . implode('|', $contact_keywords) . ')/', $field_lower)) {
				$categories['contact']['fields'][] = $field;
			} elseif (preg_match('/(' . implode('|', $system_keywords) . ')/', $field_lower)) {
				$categories['system']['fields'][] = $field;
			} else {
				$categories['other']['fields'][] = $field;
			}
		}

		// Remove empty categories
		foreach ($categories as $key => $category) {
			if (empty($category['fields'])) {
				unset($categories[$key]);
			}
		}

		return $categories;
	}
}

if (!function_exists('s3school_safe_array_combine')) {
	function s3school_safe_array_combine($keys, $values)
	{
		$keys_count = count($keys);
		$values_count = count($values);

		if ($keys_count === $values_count) {
			return array_combine($keys, $values);
		}

		// Pad the shorter array with empty strings
		if ($keys_count > $values_count) {
			$values = array_pad($values, $keys_count, '');
		} else {
			$keys = array_pad($keys, $values_count, 'Extra Column');
		}

		return array_combine($keys, $values);
	}
}

if (!function_exists('s3school_normalize_export_reports')) {
	function s3school_normalize_export_reports($reports, $allowed_columns)
	{
		if (!is_array($reports)) {
			return [];
		}

		$normalized = [];
		foreach ($reports as $key => $report) {
			$key = sanitize_title($key);
			if ($key === '' || $key === 'all') {
				continue;
			}

			$name = isset($report['name']) ? sanitize_text_field($report['name']) : '';
			$columns = isset($report['columns']) ? (array) $report['columns'] : [];
			$columns = array_values(array_intersect(array_map('sanitize_text_field', $columns), $allowed_columns));

			if ($name === '' || empty($columns)) {
				continue;
			}

			$labels = [];
			if (isset($report['labels']) && is_array($report['labels'])) {
				foreach ($columns as $column_name) {
					if (!isset($report['labels'][$column_name])) {
						continue;
					}
					$label_value = sanitize_text_field($report['labels'][$column_name]);
					if ($label_value !== '') {
						$labels[$column_name] = $label_value;
					}
				}
			}

			$normalized[$key] = [
				'name'    => $name,
				'columns' => $columns,
				'labels'  => $labels,
			];
		}

		return $normalized;
	}
}

if (!function_exists('s3school_locate_table')) {
	function s3school_locate_table($base_name)
	{
		global $wpdb;
		static $table_cache = [];

		if (isset($table_cache[$base_name])) {
			return $table_cache[$base_name];
		}

		$candidates = [];
		if (!empty($wpdb->prefix)) {
			$candidates[] = $wpdb->prefix . $base_name;
		}
		$candidates[] = $base_name;

		foreach ($candidates as $candidate) {
			$like = $wpdb->esc_like($candidate);
			$table_name = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $like));
			if (!empty($table_name)) {
				$table_cache[$base_name] = $table_name;
				return $table_name;
			}
		}

		$table_cache[$base_name] = null;
		return null;
	}
}

if (!function_exists('s3school_get_table_columns')) {
	function s3school_get_table_columns($table_name)
	{
		global $wpdb;
		static $column_cache = [];

		if (isset($column_cache[$table_name])) {
			return $column_cache[$table_name];
		}

		$table_escaped = '`' . str_replace('`', '``', $table_name) . '`';
		$columns = $wpdb->get_col("SHOW COLUMNS FROM {$table_escaped}", 0);
		if (!is_array($columns)) {
			$columns = [];
		}

		$column_cache[$table_name] = $columns;
		return $columns;
	}
}

if (!function_exists('s3school_get_custom_option_value')) {
	function s3school_get_custom_option_value($option_name, $default = [], $base_table = 'sm_options')
	{
		global $wpdb;
		$table_name = s3school_locate_table($base_table);
		if (!$table_name && $base_table !== 'sm_options') {
			$table_name = s3school_locate_table('sm_options');
		}

		if ($table_name) {
			$value = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$table_name} WHERE option_name = %s LIMIT 1", $option_name));
			if ($value === null) {
				return $default;
			}

			$maybe_serialized = maybe_unserialize($value);
			if (is_array($maybe_serialized)) {
				return $maybe_serialized;
			}
			if (is_object($maybe_serialized)) {
				return (array) $maybe_serialized;
			}

			$decoded = json_decode($value, true);
			if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
				return $decoded;
			}

			return $default;
		}

		$option_value = get_option($option_name, $default);
		return is_array($option_value) ? $option_value : $default;
	}
}

if (!function_exists('s3school_update_custom_option_value')) {
	function s3school_update_custom_option_value($option_name, $value, $base_table = 'sm_options')
	{
		global $wpdb;
		$table_name = s3school_locate_table($base_table);
		if (!$table_name && $base_table !== 'sm_options') {
			$table_name = s3school_locate_table('sm_options');
		}
		$serialized_value = maybe_serialize($value);

		if ($table_name) {
			$columns = s3school_get_table_columns($table_name);
			$has_autoload = in_array('autoload', $columns, true);

			$existing = $wpdb->get_var($wpdb->prepare("SELECT option_name FROM {$table_name} WHERE option_name = %s LIMIT 1", $option_name));

			if ($existing) {
				$update_data = ['option_value' => $serialized_value];
				$update_format = ['%s'];
				if ($has_autoload) {
					$update_data['autoload'] = 'no';
					$update_format[] = '%s';
				}
				$wpdb->update($table_name, $update_data, ['option_name' => $option_name], $update_format, ['%s']);
			} else {
				$insert_data = [
					'option_name'  => $option_name,
					'option_value' => $serialized_value,
				];
				$insert_format = ['%s', '%s'];
				if ($has_autoload) {
					$insert_data['autoload'] = 'no';
					$insert_format[] = '%s';
				}
				$wpdb->insert($table_name, $insert_data, $insert_format);
			}

			return;
		}

		update_option($option_name, $value, false);
	}
}

if (!function_exists('s3school_resolve_student_image_path')) {
	function s3school_resolve_student_image_path($raw_url)
	{
		$raw_url = trim((string) $raw_url);
		if ($raw_url === '') {
			return '';
		}

		$raw_url = str_replace('\\', '/', $raw_url);
		$raw_url = strtok($raw_url, '?');
		$raw_url = rtrim($raw_url, '/');

		$map = [];
		$upload_dir = wp_get_upload_dir();
		if (!empty($upload_dir['baseurl']) && !empty($upload_dir['basedir'])) {
			$base_url = untrailingslashit($upload_dir['baseurl']);
			$base_dir = untrailingslashit($upload_dir['basedir']);
			$map[$base_url] = $base_dir;
			$http_variant = untrailingslashit(set_url_scheme($upload_dir['baseurl'], 'http'));
			$https_variant = untrailingslashit(set_url_scheme($upload_dir['baseurl'], 'https'));
			if ($http_variant !== $base_url) {
				$map[$http_variant] = $base_dir;
			}
			if ($https_variant !== $base_url && $https_variant !== $http_variant) {
				$map[$https_variant] = $base_dir;
			}
		}

		$site_variants = array_filter(array_unique([
			untrailingslashit(home_url('/')),
			untrailingslashit(set_url_scheme(home_url('/'), 'http')),
			untrailingslashit(set_url_scheme(home_url('/'), 'https')),
			untrailingslashit(site_url('/')),
			untrailingslashit(set_url_scheme(site_url('/'), 'http')),
			untrailingslashit(set_url_scheme(site_url('/'), 'https')),
		]));
		foreach ($site_variants as $variant) {
			if ($variant !== '') {
				$map[$variant] = untrailingslashit(ABSPATH);
			}
		}

		$candidates = [];
		foreach ($map as $base_url => $base_dir) {
			if (strpos($raw_url, $base_url) === 0) {
				$relative = ltrim(substr($raw_url, strlen($base_url)), '/');
				$path = $base_dir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
				$candidates[] = $path;
				if (file_exists($path)) {
					return $path;
				}
			}
		}

		if (file_exists($raw_url)) {
			return $raw_url;
		}

		$relative_path = ABSPATH . str_replace('/', DIRECTORY_SEPARATOR, ltrim($raw_url, '/'));
		if (file_exists($relative_path)) {
			return $relative_path;
		}

		foreach ($candidates as $candidate) {
			if (file_exists($candidate)) {
				return $candidate;
			}
		}

		return '';
	}
}

global $wpdb, $s3sRedux;

$primary_table  = $wpdb->prefix . 'ct_student';
$fallback_table = 'ct_student';
$students_table = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $primary_table));

if (!$students_table) {
	$students_table = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $fallback_table));
}

if (!$students_table) {
	wp_die(esc_html__('Student table not found in the database.', 's3schoolManagment'));
}

// Determine additional tables
$studentinfo_table = str_replace('ct_student', 'ct_studentinfo', $students_table);
$class_table = str_replace('ct_student', 'ct_class', $students_table);
$section_table = str_replace('ct_student', 'ct_section', $students_table);
$group_table = str_replace('ct_student', 'ct_group', $students_table);
$subject_table = str_replace('ct_student', 'ct_subject', $students_table);

$student_columns = $wpdb->get_col('SHOW COLUMNS FROM ' . $students_table, 0);

// Columns that must never appear in exports or report configuration
$excluded_export_columns = ['stdImg', 'stdStatus', 'createdBy', 'stdCreatedAt', 'stdUpdatedAt', 'studentid'];
$student_columns = array_values(array_diff($student_columns, $excluded_export_columns));

// Pseudo/system columns that should be available for exports
$system_export_columns = ['instituteName', 'eiinNumber', 'blankColumn'];

// Add additional columns for export (from ct_studentinfo and related tables)
$additional_export_columns = [
	'className',
	'sectionName',
	'year',
	'groupName',
	'roll',
	'optionalSubjects',
	'fourthSubject'
];

$preserve_original_label_columns = apply_filters(
	's3school_export_preserve_original_labels',
	['className', 'sectionName', 'groupName', 'optionalSubjects', 'fourthSubject']
);

$all_export_columns = array_merge($system_export_columns, $student_columns, $additional_export_columns);
$pseudo_export_columns = $system_export_columns;

// Categorize fields for better UX in import mapping
$import_field_categories = s3school_categorize_import_fields($student_columns, $additional_export_columns);

if (empty($student_columns)) {
	wp_die(esc_html__('Unable to determine student table columns.', 's3schoolManagment'));
}

$export_column_labels = [];
foreach ($all_export_columns as $column_name) {
	$export_column_labels[$column_name] = s3school_format_export_column_label($column_name);
}

$stored_reports_raw = s3school_get_custom_option_value('s3school_export_reports', []);
$stored_reports = s3school_normalize_export_reports($stored_reports_raw, $all_export_columns);
$report_notice = null;
$selected_report_override = null;
$editing_report_override = null;

if (isset($_POST['s3school_report_action'])) {
	$action = sanitize_text_field(wp_unslash($_POST['s3school_report_action']));
	if (!isset($_POST['s3school_export_report_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['s3school_export_report_nonce'])), 's3school_export_report')) {
		$report_notice = ['type' => 'error', 'message' => esc_html__('Invalid report request. Please refresh and try again.', 's3schoolManagment')];
	} else {
		if ($action === 'save') {
			$report_name = isset($_POST['s3school_report_name']) ? sanitize_text_field(wp_unslash($_POST['s3school_report_name'])) : '';
			$report_columns_raw = isset($_POST['s3school_report_columns']) ? array_map('sanitize_text_field', (array) wp_unslash($_POST['s3school_report_columns'])) : [];
			$report_columns = array_values(array_intersect($report_columns_raw, $all_export_columns));
			$report_labels_raw = isset($_POST['s3school_report_labels']) ? (array) wp_unslash($_POST['s3school_report_labels']) : [];
			$report_key = isset($_POST['s3school_report_key']) ? sanitize_title(wp_unslash($_POST['s3school_report_key'])) : '';

			if ($report_name === '') {
				$report_notice = ['type' => 'error', 'message' => esc_html__('Report name is required.', 's3schoolManagment')];
			} elseif (empty($report_columns)) {
				$report_notice = ['type' => 'error', 'message' => esc_html__('Select at least one column for the report.', 's3schoolManagment')];
			} else {
				if ($report_key === '' || !isset($stored_reports[$report_key])) {
					$base_key = sanitize_title($report_name);
					if ($base_key === '') {
						$base_key = 'report';
					}
					$report_key = $base_key;
					$counter = 2;
					while ($report_key === 'all' || isset($stored_reports[$report_key])) {
						$report_key = $base_key . '-' . $counter;
						++$counter;
					}
				}

				$report_labels = [];
				foreach ($report_columns as $column_key) {
					if (!isset($report_labels_raw[$column_key])) {
						continue;
					}
					$label_value = sanitize_text_field($report_labels_raw[$column_key]);
					if ($label_value !== '') {
						$report_labels[$column_key] = $label_value;
					}
				}

				$stored_reports[$report_key] = [
					'name'    => $report_name,
					'columns' => $report_columns,
					'labels'  => $report_labels,
				];
				s3school_update_custom_option_value('s3school_export_reports', $stored_reports);
				$report_notice = ['type' => 'success', 'message' => esc_html__('Report saved successfully.', 's3schoolManagment')];
				$selected_report_override = $report_key;
				$editing_report_override = $report_key;
			}
		} elseif ($action === 'delete') {
			$report_key = isset($_POST['s3school_report_key']) ? sanitize_title(wp_unslash($_POST['s3school_report_key'])) : '';
			if ($report_key && isset($stored_reports[$report_key])) {
				unset($stored_reports[$report_key]);
				s3school_update_custom_option_value('s3school_export_reports', $stored_reports);
				$report_notice = ['type' => 'success', 'message' => esc_html__('Report deleted.', 's3schoolManagment')];
				$selected_report_override = 'all';
				$editing_report_override = 'new';
			} else {
				$report_notice = ['type' => 'error', 'message' => esc_html__('Unable to delete the selected report.', 's3schoolManagment')];
			}
		} else {
			$report_notice = ['type' => 'error', 'message' => esc_html__('Unsupported report action.', 's3schoolManagment')];
		}
	}

	$stored_reports = s3school_normalize_export_reports($stored_reports, $all_export_columns);
}

$students_table_safe = '`' . str_replace('`', '``', $students_table) . '`';
$studentinfo_table_safe = '`' . str_replace('`', '``', $studentinfo_table) . '`';
$class_table_safe = '`' . str_replace('`', '``', $class_table) . '`';
$section_table_safe = '`' . str_replace('`', '``', $section_table) . '`';
$group_table_safe = '`' . str_replace('`', '``', $group_table) . '`';

$class_options = $wpdb->get_results("SELECT classid, className FROM {$class_table_safe} ORDER BY className ASC", ARRAY_A);
$section_options = $wpdb->get_results("SELECT sectionid, sectionName, forClass FROM {$section_table_safe} ORDER BY sectionName ASC", ARRAY_A);
$group_options = $wpdb->get_results("SELECT groupId, groupName FROM {$group_table_safe} ORDER BY groupName ASC", ARRAY_A);
$year_options = $wpdb->get_col("SELECT DISTINCT infoYear FROM {$studentinfo_table_safe} WHERE infoYear IS NOT NULL AND infoYear <> '' ORDER BY infoYear DESC");

$class_lookup = [];
if (!empty($class_options)) {
	foreach ($class_options as $class_option) {
		$class_lookup[(int) $class_option['classid']] = $class_option['className'];
	}
}

$section_lookup = [];
if (!empty($section_options)) {
	foreach ($section_options as $section_option) {
		$section_lookup[(int) $section_option['sectionid']] = $section_option['sectionName'];
	}
}

$group_lookup = [];
if (!empty($group_options)) {
	foreach ($group_options as $group_option) {
		$group_lookup[(int) $group_option['groupId']] = $group_option['groupName'];
	}
}

$institute_name_value = '';
$institute_eiin_value = '';
$options_table = s3school_locate_table('sm_options');
if (!$options_table) {
	$options_table = s3school_locate_table('sm_options');
}

if ($options_table) {
	$options_table_safe = '`' . str_replace('`', '``', $options_table) . '`';

	$raw_institute_name = $wpdb->get_var($wpdb->prepare(
		"SELECT option_value FROM {$options_table_safe} WHERE option_name = %s LIMIT 1",
		'institute_name'
	));
	if ($raw_institute_name !== null) {
		$value = maybe_unserialize($raw_institute_name);
		if (!is_array($value) && !is_object($value)) {
			$institute_name_value = trim((string) $value);
		}
	}

	$raw_institute_eiin = $wpdb->get_var($wpdb->prepare(
		"SELECT option_value FROM {$options_table_safe} WHERE option_name = %s LIMIT 1",
		'institute_eiin'
	));
	if ($raw_institute_eiin !== null) {
		$value = maybe_unserialize($raw_institute_eiin);
		if (!is_array($value) && !is_object($value)) {
			$institute_eiin_value = trim((string) $value);
		}
	}
}

if ($institute_name_value === '' && isset($s3sRedux['institute_name']) && $s3sRedux['institute_name'] !== '') {
	$institute_name_value = trim((string) $s3sRedux['institute_name']);
}

if ($institute_name_value === '') {
	$institute_name_value = get_bloginfo('name');
}

if ($institute_eiin_value === '' && isset($s3sRedux['institute_eiin']) && $s3sRedux['institute_eiin'] !== '') {
	$institute_eiin_value = trim((string) $s3sRedux['institute_eiin']);
}

$special_column_values = [
	'instituteName' => $institute_name_value,
	'eiinNumber'    => $institute_eiin_value,
	'blankColumn'   => '',
];

$selected_class = isset($_GET['s3school_export_class']) ? absint($_GET['s3school_export_class']) : 0;
$selected_section = isset($_GET['s3school_export_section']) ? absint($_GET['s3school_export_section']) : 0;
$selected_group = isset($_GET['s3school_export_group']) ? absint($_GET['s3school_export_group']) : 0;
$selected_year = isset($_GET['s3school_export_year']) ? sanitize_text_field(wp_unslash($_GET['s3school_export_year'])) : '';
$selected_year = trim($selected_year);

if ($selected_class > 0 && $selected_year === '') {
	$latest_year = $wpdb->get_var($wpdb->prepare(
		"SELECT infoYear FROM {$studentinfo_table_safe} WHERE infoClass = %d AND infoYear IS NOT NULL AND infoYear <> '' ORDER BY infoYear DESC LIMIT 1",
		$selected_class
	));
	if (!empty($latest_year)) {
		$selected_year = $latest_year;
	}
}

$available_reports = array_merge(
	['all' => ['name' => esc_html__('All Columns', 's3schoolManagment'), 'columns' => array_merge($student_columns, $additional_export_columns), 'labels' => []]],
	$stored_reports
);

$selected_report_key = $selected_report_override !== null ? $selected_report_override : (isset($_GET['s3school_export_report']) ? sanitize_title(wp_unslash($_GET['s3school_export_report'])) : 'all');
if (!isset($available_reports[$selected_report_key])) {
	$selected_report_key = 'all';
}
$selected_report_columns = isset($available_reports[$selected_report_key]) ? $available_reports[$selected_report_key]['columns'] : $all_export_columns;
$selected_report_label_map = isset($available_reports[$selected_report_key]['labels']) && is_array($available_reports[$selected_report_key]['labels']) ? $available_reports[$selected_report_key]['labels'] : [];
$selected_report_labels = array_map(
	static function ($column) use ($export_column_labels, $selected_report_label_map) {
		return s3school_resolve_report_label($column, $selected_report_label_map, $export_column_labels);
	},
	$selected_report_columns
);

$editing_report_key = $editing_report_override !== null ? $editing_report_override : (isset($_GET['s3school_manage_report']) ? sanitize_title(wp_unslash($_GET['s3school_manage_report'])) : $selected_report_key);
$is_existing_report = $editing_report_key && $editing_report_key !== 'new' && isset($stored_reports[$editing_report_key]);

if ($is_existing_report) {
	$editing_report = $stored_reports[$editing_report_key];
	if (!isset($editing_report['labels']) || !is_array($editing_report['labels'])) {
		$editing_report['labels'] = [];
	}
} else {
	$editing_report_key = 'new';
	$editing_report = [
		'name'    => '',
		'columns' => $all_export_columns,
		'labels'  => [],
	];
}

if (!function_exists('s3school_read_csv_header')) {
	function s3school_read_csv_header($file_path)
	{
		$handle = fopen($file_path, 'r');
		if ($handle === false) {
			return null;
		}
		$header = fgetcsv($handle);
		fclose($handle);
		if (empty($header)) {
			return null;
		}
		if (isset($header[0])) {
			$header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
		}
		return array_map('trim', $header);
	}
}

if (!function_exists('s3school_excel_column_to_index')) {
	function s3school_excel_column_to_index($cell_ref)
	{
		if (!is_string($cell_ref) || $cell_ref === '') {
			return 0;
		}
		if (!preg_match('/([A-Z]+)/i', $cell_ref, $matches)) {
			return 0;
		}
		$letters = strtoupper($matches[1]);
		$length  = strlen($letters);
		$index   = 0;
		for ($i = 0; $i < $length; $i++) {
			$index = ($index * 26) + (ord($letters[$i]) - 64);
		}
		return max(0, $index - 1);
	}
}

if (!function_exists('s3school_convert_excel_to_csv')) {
	function s3school_convert_excel_to_csv($file_path)
	{
		if (!class_exists('ZipArchive')) {
			return new WP_Error('s3school_excel_zip_missing', esc_html__('Excel imports require the ZipArchive PHP extension.', 's3schoolManagment'));
		}

		$zip = new ZipArchive();
		if ($zip->open($file_path) !== true) {
			return new WP_Error('s3school_excel_open_failed', esc_html__('Unable to open the uploaded Excel file.', 's3schoolManagment'));
		}

		$shared_strings = [];
		$shared_index   = $zip->locateName('xl/sharedStrings.xml');
		if ($shared_index !== false) {
			$shared_xml = simplexml_load_string($zip->getFromIndex($shared_index));
			if ($shared_xml !== false) {
				foreach ($shared_xml->si as $si) {
					$text = '';
					if (isset($si->t)) {
						$text = (string) $si->t;
					} elseif (isset($si->r)) {
						foreach ($si->r as $run) {
							$text .= (string) $run->t;
						}
					}
					$shared_strings[] = $text;
				}
			}
		}

		$sheet_contents = $zip->getFromName('xl/worksheets/sheet1.xml');
		if ($sheet_contents === false) {
			$zip->close();
			return new WP_Error('s3school_excel_sheet_missing', esc_html__('Unable to locate the first worksheet inside the Excel file.', 's3schoolManagment'));
		}

		$sheet_xml = simplexml_load_string($sheet_contents);
		if ($sheet_xml === false || !isset($sheet_xml->sheetData)) {
			$zip->close();
			return new WP_Error('s3school_excel_sheet_invalid', esc_html__('The Excel worksheet appears to be corrupt or unreadable.', 's3schoolManagment'));
		}

		$rows = [];
		foreach ($sheet_xml->sheetData->row as $row) {
			$current_row   = [];
			$previous_index = -1;
			foreach ($row->c as $cell) {
				$ref       = isset($cell['r']) ? (string) $cell['r'] : '';
				$col_index = s3school_excel_column_to_index($ref);
				while ($previous_index + 1 < $col_index) {
					$current_row[] = '';
					++$previous_index;
				}

				$type  = isset($cell['t']) ? (string) $cell['t'] : '';
				$value = '';

				if ($type === 's') {
					$shared_key = isset($cell->v) ? (int) $cell->v : null;
					$value      = ($shared_key !== null && isset($shared_strings[$shared_key])) ? $shared_strings[$shared_key] : '';
				} elseif ($type === 'b') {
					$value = isset($cell->v) ? ((int) $cell->v === 1 ? 'TRUE' : 'FALSE') : '';
				} else {
					$value = isset($cell->v) ? (string) $cell->v : '';
				}

				$current_row[] = $value;
				$previous_index = $col_index;
			}
			$rows[] = $current_row;
		}

		$zip->close();

		if (empty($rows)) {
			return new WP_Error('s3school_excel_empty', esc_html__('The Excel file does not contain any readable rows.', 's3schoolManagment'));
		}

		$csv_path = wp_tempnam('s3school_student_import');
		if (!$csv_path) {
			return new WP_Error('s3school_excel_temp_failed', esc_html__('Unable to create a temporary CSV file for the Excel conversion.', 's3schoolManagment'));
		}

		$handle = fopen($csv_path, 'w');
		if ($handle === false) {
			return new WP_Error('s3school_excel_temp_unwritable', esc_html__('Unable to write the converted CSV file.', 's3schoolManagment'));
		}

		foreach ($rows as $row_values) {
			fputcsv($handle, $row_values);
		}

		fclose($handle);

		return $csv_path;
	}
}

if (!function_exists('s3school_handle_unused_columns_ajax')) {
	function s3school_handle_unused_columns_ajax()
	{
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => esc_html__('Unauthorized request.', 's3schoolManagment')], 403);
		}

		$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
		if (!wp_verify_nonce($nonce, 's3school_import_students')) {
			wp_send_json_error(['message' => esc_html__('Invalid request token.', 's3schoolManagment')], 400);
		}

		$available_raw = isset($_POST['available']) ? wp_unslash($_POST['available']) : '[]';
		$selected_raw  = isset($_POST['selected']) ? wp_unslash($_POST['selected']) : '[]';

		$available = is_string($available_raw) ? json_decode($available_raw, true) : [];
		$selected  = is_string($selected_raw) ? json_decode($selected_raw, true) : [];

		if (!is_array($available)) {
			$available = [];
		}
		if (!is_array($selected)) {
			$selected = [];
		}

		$available = array_map('sanitize_text_field', $available);
		$selected  = array_map('sanitize_text_field', $selected);

		$selected = array_filter(
			$selected,
			static function ($value) {
				return $value !== '' && $value !== '__skip';
			}
		);

		$unused = array_values(array_diff($available, $selected));

		wp_send_json_success(['unused' => $unused]);
	}

	add_action('wp_ajax_s3school_unused_columns', 's3school_handle_unused_columns_ajax');
}

if (!function_exists('s3school_handle_get_sections_ajax')) {
	function s3school_handle_get_sections_ajax()
	{
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => esc_html__('Unauthorized request.', 's3schoolManagment')], 403);
		}

		$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
		if (!wp_verify_nonce($nonce, 's3school_get_sections')) {
			wp_send_json_error(['message' => esc_html__('Invalid request token.', 's3schoolManagment')], 400);
		}

		$class_id = isset($_POST['class']) ? intval($_POST['class']) : 0;
		if (!$class_id) {
			wp_send_json_error(['message' => esc_html__('Invalid class ID.', 's3schoolManagment')], 400);
		}

		global $wpdb;
		$sections = $wpdb->get_results($wpdb->prepare(
			"SELECT sectionid, sectionName FROM ct_section WHERE forClass = %d ORDER BY sectionName",
			$class_id
		));

		if ($sections === false) {
			wp_send_json_error(['message' => esc_html__('Database error.', 's3schoolManagment')], 500);
		}

		wp_send_json_success($sections);
	}

	add_action('wp_ajax_s3school_get_sections', 's3school_handle_get_sections_ajax');
}

$import_stage   = 'upload';
$import_result  = null;
$mapping_token  = '';
$mapping_header = [];
$cleanup_token  = false;
$stored_data    = null;

if (isset($_POST['s3school_import_stage']) && isset($_POST['s3school_import_nonce'])) {
	if (!check_admin_referer('s3school_import_students', 's3school_import_nonce')) {
		wp_die(esc_html__('Invalid import request.', 's3schoolManagment'));
	}

	$stage = sanitize_text_field(s3school_post_value('s3school_import_stage'));

	if ($stage === 'prepare_mapping') {
		require_once ABSPATH . 'wp-admin/includes/file.php';

		if (!isset($_FILES['s3school_students_file']) || empty($_FILES['s3school_students_file']['tmp_name'])) {
			$import_result = ['type' => 'error', 'message' => esc_html__('No file uploaded.', 's3schoolManagment')];
		} else {
			$uploaded = wp_handle_upload($_FILES['s3school_students_file'], [
				'test_form' => false,
				'mimes'     => [
					'csv'  => 'text/csv',
					'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
				],
			]);

			if (isset($uploaded['error'])) {
				$import_result = ['type' => 'error', 'message' => esc_html($uploaded['error'])];
			} else {
				$uploaded_path = $uploaded['file'];
				$extension     = strtolower(pathinfo($uploaded_path, PATHINFO_EXTENSION));
				$csv_path      = null;
				$cleanup_files = [$uploaded_path];

				if ($extension === 'xlsx') {
					$converted = s3school_convert_excel_to_csv($uploaded_path);
					if (is_wp_error($converted)) {
						@unlink($uploaded_path);
						$import_result = ['type' => 'error', 'message' => esc_html($converted->get_error_message())];
					} else {
						$csv_path      = $converted;
						$cleanup_files[] = $converted;
					}
				} elseif ($extension === 'csv') {
					$csv_path = $uploaded_path;
				} else {
					@unlink($uploaded_path);
					$import_result = ['type' => 'error', 'message' => esc_html__('Unsupported file type. Please upload a CSV or Excel (.xlsx) file.', 's3schoolManagment')];
				}

				if (!$import_result && $csv_path) {
					$header = s3school_read_csv_header($csv_path);

					if (empty($header)) {
						foreach (array_unique($cleanup_files) as $file_path) {
							if ($file_path && file_exists($file_path)) {
								@unlink($file_path);
							}
						}
						$import_result = ['type' => 'error', 'message' => esc_html__('The uploaded file appears to be empty or invalid.', 's3schoolManagment')];
					} else {
						$mapping_token = wp_generate_password(16, false, false);
						set_transient('s3school_student_import_' . $mapping_token, [
							'path'          => $csv_path,
							'columns'       => $header,
							'cleanup_files' => array_unique($cleanup_files),
						], 15 * MINUTE_IN_SECONDS);
						$mapping_header = $header;
						$import_stage   = 'mapping';
					}
				}
			}
		}
	} elseif ($stage === 'process_import') {
		$mapping_token = sanitize_text_field(s3school_post_value('s3school_import_token', ''));
		if ($mapping_token !== '') {
			$stored_data = get_transient('s3school_student_import_' . $mapping_token);
		}

		if (!$stored_data || empty($stored_data['path']) || !file_exists($stored_data['path'])) {
			$import_result = ['type' => 'error', 'message' => esc_html__('Unable to locate the uploaded CSV. Please upload again.', 's3schoolManagment')];
			$cleanup_token = true;
		} else {
			$header = s3school_read_csv_header($stored_data['path']);

			if (empty($header)) {
				$import_result = ['type' => 'error', 'message' => esc_html__('The uploaded CSV appears to be empty or invalid.', 's3schoolManagment')];
				$cleanup_token = true;
			} else {
				$column_map_raw = isset($_POST['s3school_column_map']) ? (array) $_POST['s3school_column_map'] : [];
				$column_map     = [];
				$studentinfo_map = [];

				foreach ($column_map_raw as $column_name => $header_label) {
					$column_name  = sanitize_text_field($column_name);
					$header_label = sanitize_text_field($header_label);

					// Check if it's a student table column
					if (in_array($column_name, $student_columns, true)) {
						$column_map[$column_name] = $header_label;
					}
					// Check if it's a studentinfo/related column
					elseif (in_array($column_name, $additional_export_columns, true)) {
						$studentinfo_map[$column_name] = $header_label;
					}
				}

				$valid_map = array_filter(
					$column_map,
					static function ($header_key) {
						return $header_key !== '' && $header_key !== '__skip';
					}
				);

				$valid_info_map = array_filter(
					$studentinfo_map,
					static function ($header_key) {
						return $header_key !== '' && $header_key !== '__skip';
					}
				);

				if (empty($valid_map) && empty($valid_info_map)) {
					$import_result  = ['type' => 'error', 'message' => esc_html__('Select at least one column mapping before importing.', 's3schoolManagment')];
					$import_stage   = 'mapping';
					$mapping_header = $header;
					$cleanup_token  = false;
				} else {
					$default_info_input = [];
					if (isset($_POST['s3school_default_info']) && is_array($_POST['s3school_default_info'])) {
						$default_info_input = wp_unslash($_POST['s3school_default_info']);
					}

					$default_class = isset($default_info_input['className']) ? sanitize_text_field($default_info_input['className']) : '';
					$default_class = trim($default_class);

					$default_section = isset($default_info_input['sectionName']) ? sanitize_text_field($default_info_input['sectionName']) : '';
					$default_section = trim($default_section);

					$default_group = isset($default_info_input['groupName']) ? sanitize_text_field($default_info_input['groupName']) : '';
					$default_group = trim($default_group);

					$default_year = isset($default_info_input['year']) ? sanitize_text_field($default_info_input['year']) : '';
					$default_year = trim($default_year);

					// Resolve defaults (inputs are free-form text in UI; accept either numeric IDs or names)
					$default_class_id = 0;
					if ($default_class !== '') {
						if (is_numeric($default_class)) {
							$default_class_id = absint($default_class);
						} else {
							$resolved = $wpdb->get_var($wpdb->prepare(
								"SELECT classid FROM `" . str_replace('`', '``', $class_table) . "` WHERE className = %s LIMIT 1",
								$default_class
							));
							$default_class_id = $resolved ? (int) $resolved : 0;
						}
					}
					$apply_default_group   = !isset($valid_info_map['groupName']) && $default_group !== '';
					$apply_default_year    = !isset($valid_info_map['year']) && $default_year !== '';

					$header_index = [];
					$normalized_header_index = [];
					foreach ($header as $index => $header_label) {
						$header_index[$header_label] = $index;
						$normalized_label = s3school_normalize_header_label($header_label);
						if ($normalized_label !== '' && !isset($normalized_header_index[$normalized_label])) {
							$normalized_header_index[$normalized_label] = $index;
						}
					}
					$resolve_header_position = static function ($label) use ($header_index, $normalized_header_index) {
						if (isset($header_index[$label])) {
							return $header_index[$label];
						}
						$normalized_label = s3school_normalize_header_label($label);
						if ($normalized_label !== '' && isset($normalized_header_index[$normalized_label])) {
							return $normalized_header_index[$normalized_label];
						}
						return null;
					};
					$inserted     = 0;
					$skipped      = 0;
					$skipped_rows = [];

					$handle = fopen($stored_data['path'], 'r');
					if ($handle === false) {
						$import_result = ['type' => 'error', 'message' => esc_html__('Unable to reopen the uploaded CSV for reading.', 's3schoolManagment')];
						$cleanup_token = true;
					} else {
						fgetcsv($handle);

						while (($data_row = fgetcsv($handle)) !== false) {
							if (empty(array_filter($data_row, static function ($value) {
								return $value !== null && $value !== '';
							}))) {
								continue;
							}

							$row_data = [];
							foreach ($valid_map as $column_name => $header_label) {
								$column_position = $resolve_header_position($header_label);
								if ($column_position === null) {
									continue;
								}

								$value    = $data_row[$column_position] ?? '';
								$trimmed  = trim((string) $value);
								$row_data[$column_name] = $trimmed === '' ? null : $trimmed;
							}

							// Map gender values to numeric
							if (isset($row_data['stdGender']) && $row_data['stdGender'] !== null) {
								$gender_value = strtolower(trim($row_data['stdGender']));
								$gender_map = [
									'female' => 0,
									'girl' => 0,
									'male' => 1,
									'boy' => 1,
									'other' => 2,
								];
								if (isset($gender_map[$gender_value])) {
									$row_data['stdGender'] = $gender_map[$gender_value];
								} else {
									// If not matched, set to other
									$row_data['stdGender'] = 2;
								}
							}

							// Map class, group, section names to IDs
							if (isset($row_data['stdAdmitClass']) && $row_data['stdAdmitClass'] !== null && !is_numeric($row_data['stdAdmitClass'])) {
								$class_id = $wpdb->get_var($wpdb->prepare(
									"SELECT classid FROM `" . str_replace('`', '``', $class_table) . "` WHERE className = %s LIMIT 1",
									$row_data['stdAdmitClass']
								));
								if ($class_id) {
									$row_data['stdAdmitClass'] = $class_id;
								} else {
									$row_data['stdAdmitClass'] = 0;
								}
							}

							if (isset($row_data['stdGroup']) && $row_data['stdGroup'] !== null && !is_numeric($row_data['stdGroup'])) {
								$group_id = $wpdb->get_var($wpdb->prepare(
									"SELECT groupId FROM `" . str_replace('`', '``', $group_table) . "` WHERE groupName = %s LIMIT 1",
									$row_data['stdGroup']
								));
								if ($group_id) {
									$row_data['stdGroup'] = $group_id;
								} else {
									$row_data['stdGroup'] = 0;
								}
							}

							if (isset($row_data['stdSection']) && $row_data['stdSection'] !== null && !is_numeric($row_data['stdSection'])) {
								$section_id = $wpdb->get_var($wpdb->prepare(
									"SELECT sectionid FROM `" . str_replace('`', '``', $section_table) . "` WHERE sectionName = %s LIMIT 1",
									$row_data['stdSection']
								));
								if ($section_id) {
									$row_data['stdSection'] = $section_id;
								} else {
									$row_data['stdSection'] = 0;
								}
							}

							// Process studentinfo columns
							$info_data = [];

							// Sync stdCurrentClass with infoClass
							if (isset($row_data['stdCurrentClass']) && $row_data['stdCurrentClass'] !== null && $row_data['stdCurrentClass'] !== '') {
								$info_data['infoClass'] = $row_data['stdCurrentClass'];
							}

							// Sync stdCurntYear with infoYear
							if (isset($row_data['stdCurntYear']) && $row_data['stdCurntYear'] !== null && $row_data['stdCurntYear'] !== '') {
								$info_data['infoYear'] = $row_data['stdCurntYear'];
							}

							foreach ($valid_info_map as $column_name => $header_label) {
								$column_position = $resolve_header_position($header_label);
								if ($column_position === null) {
									continue;
								}

								$value    = $data_row[$column_position] ?? '';
								$trimmed  = trim((string) $value);

								// Map the additional columns to their database equivalents
								if ($column_name === 'className') {
									// Look up class ID by name
									$class_id = $wpdb->get_var($wpdb->prepare(
										"SELECT classid FROM `" . str_replace('`', '``', $class_table) . "` WHERE className = %s LIMIT 1",
										$trimmed
									));
									if ($class_id) {
										$info_data['infoClass'] = $class_id;
										$row_data['stdCurrentClass'] = $class_id;
									}
								} elseif ($column_name === 'sectionName') {
									// Store section name temporarily, will resolve after we know the class
									$info_data['_temp_sectionName'] = $trimmed;
								} elseif ($column_name === 'year') {
									$info_data['infoYear'] = $trimmed;
									$row_data['stdCurntYear'] = $trimmed;
								} elseif ($column_name === 'groupName') {
									// Look up group ID by name
									$group_id = $wpdb->get_var($wpdb->prepare(
										"SELECT groupId FROM `" . str_replace('`', '``', $group_table) . "` WHERE groupName = %s LIMIT 1",
										$trimmed
									));
									if ($group_id) {
										$info_data['infoGroup'] = $group_id;
									}
								} elseif ($column_name === 'roll') {
									$info_data['infoRoll'] = $trimmed === '' ? null : absint($trimmed);
								} elseif ($column_name === 'optionalSubjects') {
									$info_data['infoOptionals'] = $trimmed;
								} elseif ($column_name === 'fourthSubject') {
									$info_data['info4thSub'] = $trimmed;
								}
							}

						// Resolve/fallback class for this row.
						// Requirement: if class column is skipped OR row has empty class, fall back to default
						// for stdAdmitClass, stdCurrentClass and infoClass. Section resolution should use the resolved class.
						if (isset($info_data['infoClass']) && $info_data['infoClass'] !== null && $info_data['infoClass'] !== '' && !is_numeric($info_data['infoClass'])) {
							$resolved = $wpdb->get_var($wpdb->prepare(
								"SELECT classid FROM `" . str_replace('`', '``', $class_table) . "` WHERE className = %s LIMIT 1",
								(string) $info_data['infoClass']
							));
							$info_data['infoClass'] = $resolved ? (int) $resolved : 0;
						}
						if (isset($row_data['stdCurrentClass']) && $row_data['stdCurrentClass'] !== null && $row_data['stdCurrentClass'] !== '' && !is_numeric($row_data['stdCurrentClass'])) {
							$resolved = $wpdb->get_var($wpdb->prepare(
								"SELECT classid FROM `" . str_replace('`', '``', $class_table) . "` WHERE className = %s LIMIT 1",
								(string) $row_data['stdCurrentClass']
							));
							$row_data['stdCurrentClass'] = $resolved ? (int) $resolved : 0;
						}

						$resolved_class_id = 0;
						if (isset($info_data['infoClass']) && is_numeric($info_data['infoClass']) && (int) $info_data['infoClass'] > 0) {
							$resolved_class_id = (int) $info_data['infoClass'];
						} elseif (isset($row_data['stdCurrentClass']) && is_numeric($row_data['stdCurrentClass']) && (int) $row_data['stdCurrentClass'] > 0) {
							$resolved_class_id = (int) $row_data['stdCurrentClass'];
						} elseif (isset($row_data['stdAdmitClass']) && is_numeric($row_data['stdAdmitClass']) && (int) $row_data['stdAdmitClass'] > 0) {
							$resolved_class_id = (int) $row_data['stdAdmitClass'];
						} elseif ($default_class_id > 0) {
							$resolved_class_id = (int) $default_class_id;
						}

						if ($resolved_class_id > 0) {
							if (!isset($info_data['infoClass']) || !is_numeric($info_data['infoClass']) || (int) $info_data['infoClass'] <= 0) {
								$info_data['infoClass'] = $resolved_class_id;
							}
							if (!isset($row_data['stdCurrentClass']) || $row_data['stdCurrentClass'] === null || $row_data['stdCurrentClass'] === '' || (is_numeric($row_data['stdCurrentClass']) && (int) $row_data['stdCurrentClass'] <= 0)) {
								$row_data['stdCurrentClass'] = $resolved_class_id;
							}
							if (!isset($row_data['stdAdmitClass']) || $row_data['stdAdmitClass'] === null || $row_data['stdAdmitClass'] === '' || (is_numeric($row_data['stdAdmitClass']) && (int) $row_data['stdAdmitClass'] <= 0)) {
								$row_data['stdAdmitClass'] = $resolved_class_id;
							}
						}

						if ($apply_default_group) {
							$info_data['infoGroup'] = $default_group;
						}

						if ($apply_default_year) {
							$info_data['infoYear'] = $default_year;
							$row_data['stdCurntYear'] = $default_year;
						}

						// Resolve section to section ID based on the resolved class.
						$resolved_section_name = '';
						if (isset($info_data['_temp_sectionName']) && is_string($info_data['_temp_sectionName'])) {
							$resolved_section_name = trim($info_data['_temp_sectionName']);
						}
						if ($resolved_section_name === '' && is_string($default_section) && trim($default_section) !== '') {
							$resolved_section_name = trim($default_section);
						}
						if ($resolved_section_name !== '' && (!isset($info_data['infoSection']) || $info_data['infoSection'] === null || $info_data['infoSection'] === '' || (is_numeric($info_data['infoSection']) && (int) $info_data['infoSection'] <= 0))) {
							if (is_numeric($resolved_section_name)) {
								$info_data['infoSection'] = absint($resolved_section_name);
							} elseif ($resolved_class_id > 0) {
								$section_id = $wpdb->get_var($wpdb->prepare(
									"SELECT sectionid FROM `" . str_replace('`', '``', $section_table) . "` WHERE sectionName = %s AND forClass = %d LIMIT 1",
									$resolved_section_name,
									$resolved_class_id
								));
								if ($section_id) {
									$info_data['infoSection'] = (int) $section_id;
								}
							}
						}

						// Remove temporary field
						unset($info_data['_temp_sectionName']);

						if (empty($row_data) && empty($info_data)) {
							++$skipped;
							$skipped_rows[] = s3school_safe_array_combine($stored_data['columns'], $data_row);
							continue;
						}							// Always insert a new student, never use or check studentid
							if (isset($row_data['studentid'])) {
								unset($row_data['studentid']);
							}

							// Provide default values for all required NOT NULL columns if not present
							$required_defaults = [
								'stdName' => 'Student',
								'stdNameBangla' => '',
								'stdGender' => '',
								'stdBldGrp' => '',
								'facilities' => '',
								'stdImg' => '',
								'stdFather' => '',
								'fatherLate' => 0,
								'stdFatherProf' => '',
								'stdMother' => '',
								'motherLate' => 0,
								'stdMotherProf' => '',
								'stdParentIncome' => 0,
								'stdlocalGuardian' => '',
								'stdGuardianNID' => 0,
								'stdPhone' => '',
								'stdPermanent' => '',
								'stdPresent' => '',
								'stdBrith' => '0000-00-00',
								'stdNationality' => 'Bangladeshi',
								'stdReligion' => '',
								'stdAdmitClass' => 0,
								'stdAdmitYear' => '',
								'stdCurntYear' => '',
								'stdTcNumber' => '',
								'sscRoll' => '',
								'sscReg' => '',
								'stdPrevSchool' => '',
								'stdGPA' => '',
								'stdIntellectual' => '',
								'stdScholarsClass' => '',
								'stdScholarsYear' => '',
								'stdScholarsMemo' => '',
								'paymentPaid' => 0,
								'paymentDue' => 0,
								'stdNote' => '',
								'stdStatus' => 1
							];

							foreach ($required_defaults as $col => $def) {
								if (in_array($col, $student_columns) && (!isset($row_data[$col]) || $row_data[$col] === null)) {
									$row_data[$col] = $def;
								}
							}

							$insert_result = $wpdb->insert($students_table, $row_data);

							if ($insert_result !== false) {
								$new_student_id = (int) $wpdb->insert_id;
							} else {
								error_log('Student insert failed: ' . $wpdb->last_error);
								++$skipped;
								$skipped_rows[] = s3school_safe_array_combine($stored_data['columns'], $data_row);
								continue;
							}

							// Insert student info if we have any
							if (!empty($info_data)) {
								// Map subject codes to subject IDs for infoOptionals and info4thSub
								$info_class = isset($info_data['infoClass']) ? (int) $info_data['infoClass'] : 0;
								$subject_fields = ['infoOptionals', 'info4thSub'];
								$parse_subject_tokens = static function ($raw) {
									if ($raw === null) {
										return [];
									}
									$raw = trim((string) $raw);
									if ($raw === '') {
										return [];
									}

									// If already a JSON array, prefer that.
									$decoded = json_decode($raw, true);
									if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
										$decoded = array_values(array_filter(array_map('strval', $decoded), static function ($v) {
											return trim($v) !== '';
										}));
										return $decoded;
									}

									// Otherwise accept comma and/or whitespace separated tokens.
									$tokens = preg_split('/[\s,]+/', $raw, -1, PREG_SPLIT_NO_EMPTY);
									return is_array($tokens) ? $tokens : [];
								};

								foreach ($subject_fields as $field) {
									if (isset($info_data[$field]) && !empty($info_data[$field])) {
										$codes = $parse_subject_tokens($info_data[$field]);
										$subject_ids = [];
										foreach ($codes as $code) {
											$code = trim((string) $code);
											$code = trim($code, "\"'[](){} ");
											if ($code === '') {
												continue;
											}

											$flag_column = ($field === 'infoOptionals') ? 'subOptinal' : 'sub4th';
											if (is_numeric($code)) {
												$sub_id = $wpdb->get_var($wpdb->prepare(
													"SELECT subjectid FROM `" . str_replace('`', '``', $subject_table) . "` WHERE subjectid = %d AND subjectClass = %d AND {$flag_column} = 1 LIMIT 1",
													absint($code),
													$info_class
												));
											} else {
												$sub_id = $wpdb->get_var($wpdb->prepare(
													"SELECT subjectid FROM `" . str_replace('`', '``', $subject_table) . "` WHERE subCode = %s AND subjectClass = %d AND {$flag_column} = 1 LIMIT 1",
													$code,
													$info_class
												));
											}
											if ($sub_id) {
												$subject_ids[] = (string) $sub_id;
											}
										}
										$subject_ids = array_values(array_unique($subject_ids));
										$info_data[$field] = wp_json_encode($subject_ids);
									}
								}

								$info_data['infoStdid'] = $new_student_id;
								$info_insert = $wpdb->insert($studentinfo_table, $info_data);
								if ($info_insert === false) {
									error_log('Studentinfo insert failed for student ID ' . $new_student_id . ': ' . $wpdb->last_error);
								}
							}

							++$inserted;
						}

						fclose($handle);

						$message = sprintf(
							esc_html__('Import completed. Inserted: %1$d, Skipped: %2$d.', 's3schoolManagment'),
							$inserted,
							$skipped
						);

						$download_url = '';
						// Store skipped rows for export if any
						if (!empty($skipped_rows)) {
							$skipped_token = wp_generate_password(32, false);
							set_transient('s3school_skipped_rows_' . $skipped_token, $skipped_rows, 3600); // 1 hour
							$download_url = add_query_arg([
								's3school_export_skipped' => '1',
								's3school_skipped_token' => $skipped_token,
								's3school_export_nonce' => wp_create_nonce('s3school_export_skipped')
							], home_url('/import-export'));
						}

						$import_result = [
							'type' => 'success',
							'message' => $message,
							'download_url' => $download_url,
						];
						$cleanup_token = true;
					}
				}
			}
		}
	}

	if ($cleanup_token && $mapping_token) {
		$transient_key = 's3school_student_import_' . $mapping_token;
		delete_transient($transient_key);
		$files_to_remove = [];
		if ($stored_data) {
			if (!empty($stored_data['cleanup_files']) && is_array($stored_data['cleanup_files'])) {
				$files_to_remove = array_merge($files_to_remove, $stored_data['cleanup_files']);
			}
			if (!empty($stored_data['path'])) {
				$files_to_remove[] = $stored_data['path'];
			}
		}
		foreach (array_unique(array_filter($files_to_remove)) as $file_path) {
			if (file_exists($file_path)) {
				@unlink($file_path);
			}
		}
	}
}

// Export skipped rows handler
if (isset($_GET['s3school_export_skipped']) && isset($_GET['s3school_skipped_token']) && isset($_GET['s3school_export_nonce'])) {
	if (!check_admin_referer('s3school_export_skipped', 's3school_export_nonce')) {
		wp_die(esc_html__('Invalid export request.', 's3schoolManagment'));
	}

	$skipped_token = sanitize_text_field($_GET['s3school_skipped_token']);
	$skipped_rows = get_transient('s3school_skipped_rows_' . $skipped_token);

	if (empty($skipped_rows) || !is_array($skipped_rows)) {
		wp_die(esc_html__('Skipped rows data not found or expired. Please try importing again.', 's3schoolManagment'));
	}

	// Clean up transient after retrieval
	delete_transient('s3school_skipped_rows_' . $skipped_token);

	// Prepare CSV export
	$filename = 'skipped-students-' . gmdate('Y-m-d-His') . '.csv';
	
	header('Content-Type: text/csv; charset=utf-8');
	header('Content-Disposition: attachment; filename="' . $filename . '"');
	header('Pragma: no-cache');
	header('Expires: 0');

	$output = fopen('php://output', 'w');
	
	// Add BOM for UTF-8
	fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

	// Write headers (use keys from first row)
	if (!empty($skipped_rows[0])) {
		fputcsv($output, array_keys($skipped_rows[0]));
	}

	// Write data rows
	foreach ($skipped_rows as $row) {
		fputcsv($output, array_values($row));
	}

	fclose($output);
	exit;
}

// Export handler triggers before any HTML output.
if (isset($_GET['s3school_export_students']) && isset($_GET['s3school_export_nonce'])) {
	if (!check_admin_referer('s3school_export_students', 's3school_export_nonce')) {
		wp_die(esc_html__('Invalid export request.', 's3schoolManagment'));
	}

	$selected_columns = !empty($selected_report_columns) ? $selected_report_columns : $all_export_columns;
	$special_columns_included = [];
	foreach ($pseudo_export_columns as $special_column) {
		if (in_array($special_column, $selected_columns, true)) {
			$special_columns_included[] = $special_column;
		}
	}
	$regular_columns = array_values(array_diff($selected_columns, $special_columns_included));
	$ordered_columns = array_merge($special_columns_included, $regular_columns);

	// Build SELECT clause with JOINs for additional columns
	$select_parts = [];
	$final_columns = [];
	foreach ($ordered_columns as $column_name) {
		if (in_array($column_name, $pseudo_export_columns, true)) {
			$final_columns[] = $column_name;
			continue;
		}

		$column_expression = null;

		if ($column_name === 'className') {
			$column_expression = 'c.className';
		} elseif ($column_name === 'sectionName') {
			$column_expression = 'sec.sectionName';
		} elseif ($column_name === 'year') {
			$column_expression = 'si.infoYear AS year';
		} elseif ($column_name === 'groupName') {
			$column_expression = 'g.groupName';
		} elseif ($column_name === 'roll') {
			$column_expression = 'si.infoRoll AS roll';
		} elseif ($column_name === 'optionalSubjects') {
			$column_expression = 'si.infoOptionals AS optionalSubjects';
		} elseif ($column_name === 'fourthSubject') {
			$column_expression = 'si.info4thSub AS fourthSubject';
		} elseif (in_array($column_name, $student_columns, true)) {
			$column_expression = 's.`' . str_replace('`', '``', $column_name) . '`';
		}

		if ($column_expression !== null) {
			$select_parts[] = $column_expression;
			$final_columns[] = $column_name;
		}
	}

	$columns = $final_columns;
	$select_clause_parts = !empty($select_parts) ? $select_parts : ['s.studentid'];
	$columns_sql = implode(', ', $select_clause_parts);

	$query = "SELECT {$columns_sql} 
		FROM {$students_table_safe} s
		LEFT JOIN {$studentinfo_table_safe} si ON s.studentid = si.infoStdid
		LEFT JOIN {$class_table_safe} c ON si.infoClass = c.classid
		LEFT JOIN {$section_table_safe} sec ON si.infoSection = sec.sectionid
		LEFT JOIN {$group_table_safe} g ON si.infoGroup = g.groupId";

	$where_clauses = [];
	$query_params = [];

	if ($selected_class > 0) {
		$where_clauses[] = 'si.infoClass = %d';
		$query_params[] = $selected_class;
	}

	if ($selected_section > 0) {
		$where_clauses[] = 'si.infoSection = %d';
		$query_params[] = $selected_section;
	}

	if ($selected_group > 0) {
		$where_clauses[] = 'si.infoGroup = %d';
		$query_params[] = $selected_group;
	}

	if ($selected_year !== '') {
		$where_clauses[] = 'si.infoYear = %s';
		$query_params[] = $selected_year;
	}

	$order_clause = " ORDER BY (si.infoRoll IS NULL OR si.infoRoll = '') ASC, si.infoRoll+0 ASC, si.infoRoll ASC";

	if (!empty($where_clauses)) {
		$query .= ' WHERE ' . implode(' AND ', $where_clauses) . $order_clause;
		$query = $wpdb->prepare($query, $query_params);
	} else {
		$query .= $order_clause;
	}

	$records = $wpdb->get_results($query, ARRAY_A);

	$filename_fragments = ['students'];
	if ($selected_class > 0 && isset($class_lookup[$selected_class])) {
		$filename_fragments[] = 'class-' . sanitize_title($class_lookup[$selected_class]);
	}
	if ($selected_section > 0 && isset($section_lookup[$selected_section])) {
		$filename_fragments[] = 'section-' . sanitize_title($section_lookup[$selected_section]);
	}
	if ($selected_group > 0 && isset($group_lookup[$selected_group])) {
		$filename_fragments[] = 'group-' . sanitize_title($group_lookup[$selected_group]);
	}
	if ($selected_year !== '') {
		$filename_fragments[] = 'year-' . sanitize_title($selected_year);
	}

	$filename_base = implode('-', array_filter($filename_fragments, static function ($fragment) {
		return $fragment !== '';
	}));
	$filename_base = $filename_base !== '' ? $filename_base : 'students';
	$filename = $filename_base . '-' . gmdate('Ymd-His') . '.csv';

	nocache_headers();
	header('Content-Type: text/csv; charset=UTF-8');
	header('Content-Disposition: attachment; filename="' . $filename . '"');

	$output = fopen('php://output', 'w');
	if ($output === false) {
		wp_die(esc_html__('Unable to generate export file.', 's3schoolManagment'));
	}

	$display_columns = $columns;
	$column_headers = [];
	foreach ($display_columns as $column_name) {
		$resolved_label = s3school_resolve_report_label($column_name, $selected_report_label_map, $export_column_labels);
		$has_custom_label = isset($selected_report_label_map[$column_name]) && $selected_report_label_map[$column_name] !== '';
		if ($column_name === 'blankColumn' && !$has_custom_label) {
			$column_headers[] = '';
		} else {
			$preserve_original = in_array($column_name, $preserve_original_label_columns, true);
			if ($preserve_original && !$has_custom_label) {
				$column_headers[] = $column_name;
			} else {
				$column_headers[] = $resolved_label;
			}
		}
	}
	$header_columns = array_merge(['Sl No'], $column_headers);
	fputcsv($output, $header_columns);

	$force_text_columns = apply_filters('s3school_export_text_columns', ['stdPhone']);
	if (!is_array($force_text_columns)) {
		$force_text_columns = ['stdPhone'];
	}

	$gender_labels = apply_filters('s3school_export_gender_labels', [
		'0' => __('Girl', 's3schoolManagment'),
		'1' => __('Boy', 's3schoolManagment'),
		'2' => __('Other', 's3schoolManagment'),
	]);

	$charset = get_option('blog_charset', 'UTF-8');
	$serial_counter = 1;

	foreach ($records as $record) {
		$row = [$serial_counter];
		foreach ($display_columns as $column_name) {
			if (array_key_exists($column_name, $special_column_values)) {
				$row[] = $special_column_values[$column_name];
				continue;
			}

			$value = isset($record[$column_name]) ? $record[$column_name] : '';
			if ($column_name === 'stdPhone' && $value !== '') {
				$value = trim((string) $value);
			}

			if ($column_name === 'stdGender') {
				$gender_key = (string) $value;
				$value = isset($gender_labels[$gender_key]) ? $gender_labels[$gender_key] : $value;
			} elseif ($column_name === 'stdName' && $value !== '') {
				if (function_exists('mb_strtoupper')) {
					$value = mb_strtoupper($value, $charset);
				} else {
					$value = strtoupper($value);
				}
			} elseif ($column_name === 'className' && $value !== '') {
				if (function_exists('mb_convert_case')) {
					$value = mb_convert_case($value, MB_CASE_TITLE, $charset);
				} else {
					$value = ucwords(strtolower($value));
				}
			}

			if ($value !== '' && in_array($column_name, $force_text_columns, true)) {
				$row[] = '="' . str_replace('"', '""', $value) . '"';
			} else {
				$row[] = $value;
			}
		}

		fputcsv($output, $row);
		++$serial_counter;
	}

	fclose($output);
	exit;
}

$default_info_submission = [];
if (isset($_POST['s3school_default_info']) && is_array($_POST['s3school_default_info'])) {
	$default_info_submission = wp_unslash($_POST['s3school_default_info']);
}

$default_selected_class = isset($default_info_submission['className']) ? sanitize_text_field($default_info_submission['className']) : '';
$default_selected_class = trim($default_selected_class);

$default_selected_section = isset($default_info_submission['sectionName']) ? sanitize_text_field($default_info_submission['sectionName']) : '';
$default_selected_section = trim($default_selected_section);

$default_selected_group = isset($default_info_submission['groupName']) ? sanitize_text_field($default_info_submission['groupName']) : '';
$default_selected_group = trim($default_selected_group);

$default_selected_year = isset($default_info_submission['year']) ? sanitize_text_field($default_info_submission['year']) : '';
$default_selected_year = trim($default_selected_year);

get_header();
?>

<p id="s3school-template-url" class="screen-reader-text"><?php echo esc_url(get_template_directory_uri()); ?></p>

<style>
	.s3school-panel {
		max-width: 960px;
		margin: 0 auto 4rem;
		padding: 2rem 1.5rem;
		background: #f9fafc;
		border-radius: 18px;
		box-shadow: 0 18px 45px rgba(15, 23, 42, .12);
		font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
	}

	.s3school-panel h1 {
		margin: 0 0 1.75rem;
		font-weight: 700;
		color: #0f172a;
		display: flex;
		align-items: center;
		gap: .75rem;
	}

	.s3school-panel .notice {
		border-radius: 14px;
		padding: 1rem 1.25rem;
		margin-bottom: 1.5rem;
		font-weight: 600;
		box-shadow: 0 10px 25px rgba(15, 23, 42, .08);
	}

	.s3school-flex {
		display: flex;
		flex-direction: column;
		gap: 1.5rem;
	}

	.s3school-card {
		background: #fff;
		border-radius: 16px;
		padding: 1.75rem;
		box-shadow: 0 15px 35px rgba(15, 23, 42, .1);
		display: flex;
		flex-direction: column;
		gap: 1.1rem;
	}

	.s3school-card h2 {
		margin: 0;
		font-weight: 600;
		color: #475569;
	}

	.s3school-card p {
		margin: 0;
		color: #1e293b;
		line-height: 1.55;
	}

	.s3school-card form {
		display: flex;
		flex-direction: column;
		gap: 1.25rem;
	}

	.s3school-card button.button.button-primary {
		color: #fff;
		align-self: flex-start;
		border-radius: 999px;
		padding: .65rem 1.7rem;
		font-weight: 600;
		background: linear-gradient(135deg, #2563eb, #1d4ed8);
		border: none;
		box-shadow: 0 12px 24px rgba(37, 99, 235, .25);
	}

	.s3school-pill {
		display: inline-flex;
		align-items: center;
		gap: .4rem;
		padding: .35rem .85rem;
		border-radius: 999px;
		font-weight: 600;
		background: #eef2ff;
		color: #4338ca;
	}

	.s3school-map-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
		gap: 1rem 1.25rem;
	}

	.s3school-map-field {
		background: #f8fafc;
		border: 1px solid #e2e8f0;
		border-radius: 12px;
		padding: 1rem 1.2rem;
		display: flex;
		flex-direction: column;
		gap: .45rem;
	}

	.s3school-map-field strong {
		color: #1f2937;
		font-weight: 600;
		letter-spacing: .01em;
	}

	.s3school-map-field select {
		width: 100%;
		border-radius: 10px;
		border: 1px solid #cbd5f5;
		padding: .55rem .75rem;
		transition: border-color .2s ease, box-shadow .2s ease;
	}

	.s3school-map-field select:focus {
		border-color: #2563eb;
		box-shadow: 0 0 0 3px rgba(37, 99, 235, .15);
		outline: none;
	}

	.s3school-map-heading {
		grid-column: 1/-1;
		font-weight: 700;
		color: #0f172a;
		text-transform: uppercase;
		letter-spacing: .08em;
		margin-top: .5rem;
	}

	.s3school-upload {
		display: flex;
		flex-direction: column;
		gap: .6rem;
	}

	.s3school-upload input[type=file] {
		border: 1px dashed #94a3b8;
		border-radius: 12px;
		padding: 1.3rem;
		background: #fafcff;
		transition: border-color .2s ease, background .2s ease;
	}

	.s3school-upload input[type=file]:hover {
		border-color: #2563eb;
		background: #f1f5ff;
	}

	.s3school-defaults {
		margin-top: 1rem;
		padding: 1.25rem 1.5rem;
		background: #ffffff;
		border: 1px solid #e2e8f0;
		border-radius: 14px;
		display: flex;
		flex-direction: column;
		gap: .85rem;
	}

	.s3school-defaults-title {
		font-weight: 600;
		color: #1f2937;
	}

	.s3school-defaults-description {
		margin: 0;
		color: #475569;
	}

	.s3school-defaults-field {
		display: flex;
		gap: .45rem;
		color: #1f2937;
	}

	.s3school-defaults-field select,
	.s3school-defaults-field input[type=text] {
		border-radius: 10px;
		border: 1px solid #cbd5f5;
		padding: .55rem .75rem;
		transition: border-color .2s ease, box-shadow .2s ease;
	}

	.s3school-defaults-field select:focus,
	.s3school-defaults-field input[type=text]:focus {
		border-color: #2563eb;
		box-shadow: 0 0 0 3px rgba(37, 99, 235, .15);
		outline: none;
	}

	.s3school-export-filters {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
		gap: 1rem;
	}

	.s3school-export-filters label {
		display: flex;
		flex-direction: column;
		gap: .35rem;
		font-weight: 600;
		color: #1e293b;
	}

	.s3school-export-filters select {
		border-radius: 10px;
		border: 1px solid #cbd5f5;
		padding: .55rem .75rem;
		background: #fff;
		transition: border-color .2s ease, box-shadow .2s ease;
	}

	.s3school-export-filters select:focus {
		border-color: #2563eb;
		box-shadow: 0 0 0 3px rgba(37, 99, 235, .15);
		outline: none;
	}

	.s3school-report-hint {
		margin: -.25rem 0 0;
		font-size: .9rem;
		color: #475569;
	}

	.s3school-report-switcher {
		display: flex;
		flex-direction: column;
		gap: .65rem;
		margin-bottom: 1rem;
	}

	.s3school-report-field label {
		display: flex;
		flex-direction: column;
		gap: .35rem;
		font-weight: 600;
	}

	.s3school-report-field input[type=text] {
		border-radius: 10px;
		border: 1px solid #cbd5f5;
		padding: .55rem .75rem;
	}

	.s3school-report-columns {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
		gap: .5rem 1rem;
		margin: 1rem 0;
	}

	.s3school-report-checkbox {
		display: flex;
		flex-direction: column;
		gap: .45rem;
		background: #f8fafc;
		border: 1px solid #e2e8f0;
		border-radius: 10px;
		padding: .65rem .75rem;
	}

	.s3school-report-checkbox label {
		cursor: pointer;
	}

	.s3school-report-checkbox-row {
		display: flex;
		align-items: center;
		gap: .5rem;
		font-weight: 600;
		color: #1f2937;
	}

	.s3school-report-checkbox-row input[type=checkbox] {
		transform: scale(1.1);
	}

	.s3school-report-label-input {
		width: 100%;
		border-radius: 10px;
		border: 1px solid #94a3b8;
		padding: .55rem .65rem;
		font-size: 1.5rem;
		font-weight: 600;
		color: #0f172a;
		background: #fff;
	}

	.s3school-report-label-input:focus {
		border-color: #2563eb;
		box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
		outline: none;
	}

	.s3school-report-label-input:disabled {
		background: #e2e8f0;
		cursor: not-allowed;
		opacity: .9;
	}

	.s3school-report-label-meta {
		display: flex;
		align-items: center;
		justify-content: space-between;
		color: #475569;
		font-size: 1rem;
	}

	.s3school-report-bulk {
		display: flex;
		flex-wrap: wrap;
		gap: .6rem;
		margin: .35rem 0 1rem;
	}

	.s3school-report-bulk .button {
		flex: 0 0 auto;
	}

	.s3school-report-actions {
		display: flex;
		gap: .75rem;
		align-items: center;
	}

	.s3school-unused-wrapper {
		margin-top: 2rem;
		position: relative;
		border: 1px solid #cbd5f5;
		border-radius: 12px;
		padding: 1rem 1.2rem 1.2rem;
		background: #f8faff;
		box-shadow: inset 0 1px 0 rgba(148, 163, 184, .15);
	}

	.s3school-unused-wrapper strong {
		display: block;
		margin-bottom: .35rem;
		color: #1e293b;
	}

	.s3school-unused-description {
		margin: 0 0 .75rem;
		color: #475569;
		opacity: .7;
		display: flex;
	}

	.s3school-unused-list {
		margin: 0;
		padding-left: 1.25rem;
		color: #ff0000;
	}

	.s3school-unused-list li {
		margin-bottom: .3rem;
	}

	.s3school-unused-empty {
		list-style: none;
		padding-left: 0;
		color: #64748b;
	}

	.s3school-unused-wrapper.s3school-unused-loading {
		opacity: .7;
	}

	.s3school-unused-wrapper.s3school-unused-loading::after {
		content: 'Updating...';
		position: absolute;
		top: .75rem;
		right: 1rem;
		font-weight: 600;
		color: #2563eb;
	}

	.s3school-confirm-import {
		margin-top: 1rem;
		padding: .75rem 1rem;
		border-radius: 10px;
		background: #f8fafc;
		border: 1px solid transparent;
		display: flex;
		align-items: center;
		gap: .6rem;
		color: #1e293b;
	}

	.s3school-confirm-import label {
		display: flex;
		align-items: center;
		gap: .55rem;
		margin: 0;
		cursor: pointer;
	}

	.s3school-confirm-import input[type=checkbox] {
		transform: scale(1.1);
	}

	.s3school-confirm-import.s3school-highlight {
		border-color: #fb923c;
		background: #fff7ed;
		color: #9a3412;
	}

	.button.button-primary:disabled,
	.button.button-primary[disabled] {
		background: #94a3b8;
		cursor: not-allowed;
		box-shadow: none;
		opacity: .65;
	}

	.s3school-map-field-highlighted {
		background: #fef3c7;
		border-radius: 6px;
		padding: 0.5rem;
	}

	.s3school-map-field-highlighted strong {
		color: #92400e;
		font-weight: 700;
	}
</style>

<div class="s3school-panel">
	<?php if ($import_result) : ?>
		<div class="notice notice-<?php echo esc_attr($import_result['type']); ?>" style="color: green;">
			<p><?php echo wp_kses_post($import_result['message']); ?></p>	
		</div>
		<?php if (!empty($import_result['download_url'])) : ?>
			<script>
				setTimeout(function() {
					window.location.href = "<?php echo esc_url_raw($import_result['download_url']); ?>";
				}, 1500);
			</script>
		<?php endif; ?>
	<?php endif; ?>

	<div class="s3school-flex">
		<div class="s3school-card">
			<span class="s3school-pill"><?php esc_html_e('Guided Import', 's3schoolManagment'); ?></span>
			<h2><?php esc_html_e('Import Students', 's3schoolManagment'); ?></h2>
			<?php if ($import_stage === 'mapping' && !empty($mapping_header)) : ?>
				<form method="post">
					<?php wp_nonce_field('s3school_import_students', 's3school_import_nonce'); ?>
					<input type="hidden" name="s3school_import_stage" value="process_import" />
					<input type="hidden" name="s3school_import_token" value="<?php echo esc_attr($mapping_token); ?>" />
					<div class="s3school-map-grid">
						<?php foreach ($import_field_categories as $category_key => $category) : ?>
							<span class="s3school-map-heading"><?php echo esc_html($category['label']); ?></span>
							<?php foreach ($category['fields'] as $column_name) : ?>
								<?php
								$highlighted_fields = ['stdCurrentClass', 'className', 'sectionName', 'year', 'groupName', 'roll', 'stdName', 'stdGender', 'stdPhone', 'stdFather', 'stdReligion'];
								$is_highlighted = in_array($column_name, $highlighted_fields, true);
								?>
								<div class="s3school-map-field<?php echo $is_highlighted ? ' s3school-map-field-highlighted' : ''; ?>">
									<strong><?php echo esc_html($column_name); ?></strong>
									<?php $normalized_column_name = s3school_normalize_header_label($column_name); ?>
									<select name="s3school_column_map[<?php echo esc_attr($column_name); ?>]">
										<option value="__skip"><?php esc_html_e('Skip', 's3schoolManagment'); ?></option>
										<?php foreach ($mapping_header as $header_label) : ?>
											<?php $auto_match = $normalized_column_name !== '' && $normalized_column_name === s3school_normalize_header_label($header_label); ?>
											<option value="<?php echo esc_attr($header_label); ?>" <?php selected($auto_match); ?>><?php echo esc_html($header_label); ?></option>
										<?php endforeach; ?>
									</select>
								</div>
							<?php endforeach; ?>
						<?php endforeach; ?>
					</div>

					<div class="s3school-defaults">
						<p class="s3school-defaults-description"><?php esc_html_e('Set default values if Class, Section, Group or Year are skipped (Optional)', 's3schoolManagment'); ?></p>
						<div class="s3school-defaults-grid">
							<label class="s3school-defaults-field">
								<span><?php esc_html_e('Default Class', 's3schoolManagment'); ?></span>
								<input type="text" name="s3school_default_info[className]" value="<?php echo esc_attr($default_selected_class); ?>" placeholder="<?php esc_attr_e('Enter default class name', 's3schoolManagment'); ?>" />
							</label>
							<label class="s3school-defaults-field">
								<span><?php esc_html_e('Default Section', 's3schoolManagment'); ?></span>
								<input type="text" name="s3school_default_info[sectionName]" value="<?php echo esc_attr($default_selected_section); ?>" placeholder="<?php esc_attr_e('Enter default section name', 's3schoolManagment'); ?>" />
							</label>
							<label class="s3school-defaults-field">
								<span><?php esc_html_e('Default Group', 's3schoolManagment'); ?></span>
								<input type="text" name="s3school_default_info[groupName]" value="<?php echo esc_attr($default_selected_group); ?>" placeholder="<?php esc_attr_e('Enter default group name', 's3schoolManagment'); ?>" />
							</label>
							<label class="s3school-defaults-field">
								<span><?php esc_html_e('Default Year', 's3schoolManagment'); ?></span>
								<input type="text" name="s3school_default_info[year]" value="<?php echo esc_attr($default_selected_year); ?>" placeholder="<?php esc_attr_e('e.g. 2025', 's3schoolManagment'); ?>" list="s3school-default-year-options" />
								<?php if (!empty($year_options)) : ?>
									<datalist id="s3school-default-year-options">
										<?php foreach ($year_options as $year_option) : ?>
											<option value="<?php echo esc_attr($year_option); ?>"></option>
										<?php endforeach; ?>
									</datalist>
								<?php endif; ?>
							</label>
						</div>
					</div>

					<div
						id="s3school-unused-columns"
						class="s3school-unused-wrapper"
						data-available="<?php 
							$excluded_unmapped = ['Sl No', 'Institute Name', 'EIIN No', 'Comment'];
							$filtered_mapping_header = array_diff($mapping_header, $excluded_unmapped);
							echo esc_attr(wp_json_encode(array_values($filtered_mapping_header))); 
						?>"
						data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
						<strong><?php esc_html_e('Unmapped File Columns (Columns from your file that are not mapped yet)', 's3schoolManagment'); ?></strong>
						<ul class="s3school-unused-list" aria-live="polite"></ul>
					</div>

					<div class="s3school-confirm-import" id="s3school_confirm_wrapper">
						<label for="s3school_skip_existing">
							<input type="checkbox" id="s3school_skip_existing" name="s3school_skip_existing" value="1" />
							<span><?php esc_html_e('Import anyway skipping unmapped fields. (Skipped fields leave existing data untouched.)', 's3schoolManagment'); ?></span>
						</label>
					</div>
					<button class="button button-primary" id="s3school_run_import" type="submit" disabled><?php esc_html_e('Run Import', 's3schoolManagment'); ?></button>
				</form>
			<?php else : ?>
				<form method="post" enctype="multipart/form-data">
					<?php wp_nonce_field('s3school_import_students', 's3school_import_nonce'); ?>
					<input type="hidden" name="s3school_import_stage" value="prepare_mapping" />
					<div class="s3school-upload">
						<label for="s3school_students_file"><?php esc_html_e('Upload CSV or Excel (.xlsx) file', 's3schoolManagment'); ?></label>
						<input type="file" id="s3school_students_file" name="s3school_students_file" accept=".csv,.xlsx" required />
						<small><?php esc_html_e('Ensure the first row contains column labels that match your data. Excel files are converted to CSV automatically.', 's3schoolManagment'); ?></small>
					</div>
					<button class="button button-primary" type="submit"><?php esc_html_e('Next: Map Columns', 's3schoolManagment'); ?></button>
				</form>
			<?php endif; ?>
		</div>

		<div class="s3school-card">
			<span class="s3school-pill"><?php esc_html_e('Quick Export', 's3schoolManagment'); ?></span>
			<h2><?php esc_html_e('Export Students', 's3schoolManagment'); ?></h2>
			<form method="get" action="<?php echo esc_url(get_permalink()); ?>" id="s3school-export-form">
				<input type="hidden" name="s3school_export_students" value="1" />
				<?php wp_nonce_field('s3school_export_students', 's3school_export_nonce'); ?>
				<div class="s3school-export-filters">
					<label>
						<span><?php esc_html_e('Report Type', 's3schoolManagment'); ?></span>
						<select name="s3school_export_report">
							<?php foreach ($available_reports as $report_key => $report_data) : ?>
								<option value="<?php echo esc_attr($report_key); ?>" <?php selected($report_key, $selected_report_key); ?>><?php echo esc_html($report_data['name']); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label>
						<span><?php esc_html_e('Class', 's3schoolManagment'); ?></span>
						<select name="s3school_export_class" id="s3school_export_class">
							<option value="0" <?php selected($selected_class, 0); ?>><?php esc_html_e('All Classes', 's3schoolManagment'); ?></option>
							<?php if (!empty($class_options)) : ?>
								<?php foreach ($class_options as $class_option) : ?>
									<option value="<?php echo esc_attr($class_option['classid']); ?>" <?php selected((int) $class_option['classid'], $selected_class); ?>><?php echo esc_html($class_option['className']); ?></option>
								<?php endforeach; ?>
							<?php endif; ?>
						</select>
					</label>
					<label>
						<span><?php esc_html_e('Section', 's3schoolManagment'); ?></span>
						<select name="s3school_export_section" id="s3school_export_section" <?php disabled($selected_class == 0); ?>>
							<option value="0" <?php selected($selected_section, 0); ?>><?php esc_html_e('All Sections', 's3schoolManagment'); ?></option>
							<?php if (!empty($section_options) && $selected_class > 0) : ?>
								<?php foreach ($section_options as $section_option) : ?>
									<?php if ((int) $section_option['forClass'] === $selected_class) : ?>
										<option value="<?php echo esc_attr($section_option['sectionid']); ?>" <?php selected((int) $section_option['sectionid'], $selected_section); ?>><?php echo esc_html($section_option['sectionName']); ?></option>
									<?php endif; ?>
								<?php endforeach; ?>
							<?php endif; ?>
						</select>
					</label>
					<label>
						<span><?php esc_html_e('Group', 's3schoolManagment'); ?></span>
						<select name="s3school_export_group" id="s3school_export_group" <?php disabled($selected_class == 0); ?>>
							<option value="0" <?php selected($selected_group, 0); ?>><?php esc_html_e('All Groups', 's3schoolManagment'); ?></option>
							<?php if (!empty($group_options) && $selected_class > 0) : ?>
								<?php
								// Filter groups by selected class
								$filtered_groups = array_filter($group_options, function ($group) use ($selected_class, $wpdb, $studentinfo_table) {
									$group_id = (int) $group['groupId'];
									$count = $wpdb->get_var($wpdb->prepare(
										"SELECT COUNT(*) FROM {$studentinfo_table} WHERE infoClass = %d AND infoGroup = %d",
										$selected_class,
										$group_id
									));
									return $count > 0;
								});
								?>
								<?php foreach ($filtered_groups as $group_option) : ?>
									<option value="<?php echo esc_attr($group_option['groupId']); ?>" <?php selected((int) $group_option['groupId'], $selected_group); ?>><?php echo esc_html($group_option['groupName']); ?></option>
								<?php endforeach; ?>
							<?php endif; ?>
						</select>
					</label>
					<label>
						<span><?php esc_html_e('Year', 's3schoolManagment'); ?></span>
						<select name="s3school_export_year" id="s3school_export_year" <?php disabled($selected_class == 0); ?>>
							<option value="" <?php selected($selected_year, ''); ?>><?php esc_html_e('All Years', 's3schoolManagment'); ?></option>
							<?php if (!empty($year_options) && $selected_class > 0) : ?>
								<?php
								// Filter years by selected class
								$filtered_years = $wpdb->get_col($wpdb->prepare(
									"SELECT DISTINCT infoYear FROM {$studentinfo_table_safe} WHERE infoClass = %d AND infoYear IS NOT NULL AND infoYear <> '' ORDER BY infoYear ASC",
									$selected_class
								));
								?>
								<?php foreach ($filtered_years as $year_option) : ?>
									<option value="<?php echo esc_attr($year_option); ?>" <?php selected($year_option, $selected_year); ?>><?php echo esc_html($year_option); ?></option>
								<?php endforeach; ?>
							<?php endif; ?>
						</select>
					</label>
				</div>
				<button class="button button-primary" type="submit"><?php esc_html_e('Export CSV', 's3schoolManagment'); ?></button>
			</form>
		</div>

		<div class="s3school-card">
			<span class="s3school-pill"><?php esc_html_e('Report Templates', 's3schoolManagment'); ?></span>
			<h2><?php esc_html_e('Configurations', 's3schoolManagment'); ?></h2>
			<?php if ($report_notice) : ?>
				<div class="notice notice-<?php echo esc_attr($report_notice['type']); ?>">
					<p><?php echo esc_html($report_notice['message']); ?></p>
				</div>
			<?php endif; ?>
			<form method="get" class="s3school-report-switcher">
				<?php if (!empty($_GET)) : ?>
					<?php foreach ($_GET as $param_name => $param_value) : ?>
						<?php if ($param_name === 's3school_manage_report') {
							continue;
						}
						if (is_array($param_value)) {
							continue;
						}
						?>
						<input type="hidden" name="<?php echo esc_attr($param_name); ?>" value="<?php echo esc_attr($param_value); ?>" />
					<?php endforeach; ?>
				<?php endif; ?>
				<label style="color: teal;margin-top: 10px;font-size: larger;">
					<span><?php esc_html_e('Select report to edit', 's3schoolManagment'); ?></span>
					<select name="s3school_manage_report" onchange="this.form.submit()">
						<option value="new" <?php selected($editing_report_key, 'new'); ?>><?php esc_html_e('Create New Report', 's3schoolManagment'); ?></option>
						<?php foreach ($stored_reports as $report_key => $report_data) : ?>
							<option value="<?php echo esc_attr($report_key); ?>" <?php selected($editing_report_key, $report_key); ?>><?php echo esc_html($report_data['name']); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<noscript>
					<button type="submit" class="button button-secondary"><?php esc_html_e('Load Report', 's3schoolManagment'); ?></button>
				</noscript>
			</form>
			<form method="post" class="s3school-report-builder">
				<?php wp_nonce_field('s3school_export_report', 's3school_export_report_nonce'); ?>
				<input type="hidden" name="s3school_report_key" value="<?php echo esc_attr($is_existing_report ? $editing_report_key : ''); ?>" />
				<div class="s3school-report-field">
					<label>
						<span><?php esc_html_e('Report Name', 's3schoolManagment'); ?></span>
						<input type="text" name="s3school_report_name" value="<?php echo esc_attr($editing_report['name']); ?>" placeholder="<?php esc_attr_e('e.g. Exam Summary Columns', 's3schoolManagment'); ?>" required />
					</label>
				</div>

				<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: .75rem;">
					<div class="s3school-report-bulk">
						<button type="button" class="button button-secondary" data-report-select-all><?php esc_html_e('Select All', 's3schoolManagment'); ?></button>
						<button type="button" class="button" data-report-clear-all><?php esc_html_e('Unselect All', 's3schoolManagment'); ?></button>
					</div>

					<div class="s3school-report-actions">
						<button type="submit" class="button button-primary" name="s3school_report_action" value="save"><?php esc_html_e('Save Report', 's3schoolManagment'); ?></button>
						<?php if ($is_existing_report) : ?>
							<button type="submit" class="button button-secondary" name="s3school_report_action" value="delete" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete this report?', 's3schoolManagment')); ?>');"><?php esc_html_e('Delete This Report Type', 's3schoolManagment'); ?></button>
						<?php endif; ?>
					</div>
				</div>

				<div class="s3school-report-columns">
					<?php foreach ($export_column_labels as $column_key => $column_label) : ?>
						<?php
						$is_checked = in_array($column_key, $editing_report['columns'], true);
						$custom_label_value = isset($editing_report['labels'][$column_key]) ? $editing_report['labels'][$column_key] : '';
						?>
						<label class="s3school-report-checkbox">
							<input type="text" class="s3school-report-label-input" name="s3school_report_labels[<?php echo esc_attr($column_key); ?>]" value="<?php echo esc_attr($custom_label_value); ?>" placeholder="<?php echo esc_attr($column_label); ?>" <?php disabled(!$is_checked); ?> />
							<span class="s3school-report-label-meta">
								<span><?php printf(esc_html__('%s', 's3schoolManagment'), esc_html($column_label)); ?></span>
								<span class="s3school-report-checkbox-row">
									<input type="checkbox" name="s3school_report_columns[]" value="<?php echo esc_attr($column_key); ?>" data-report-column="<?php echo esc_attr($column_key); ?>" <?php checked($is_checked); ?> />
									<?php esc_html_e('Include', 's3schoolManagment'); ?>
								</span>
							</span>
						</label>
					<?php endforeach; ?>
				</div>
			</form>
		</div>
	</div>
</div>

<script>
	document.addEventListener('DOMContentLoaded', function() {
		var reportBuilder = document.querySelector('.s3school-report-builder');
		if (!reportBuilder) {
			return;
		}

		var selectAllBtn = reportBuilder.querySelector('[data-report-select-all]');
		var clearAllBtn = reportBuilder.querySelector('[data-report-clear-all]');
		var checkboxSelector = 'input[name="s3school_report_columns[]"]';

		var forEachCheckbox = function(callback) {
			var checkboxes = reportBuilder.querySelectorAll(checkboxSelector);
			Array.prototype.forEach.call(checkboxes, function(checkbox) {
				callback(checkbox);
			});
		};

		var getCheckboxWrapper = function(element) {
			if (element.closest) {
				return element.closest('.s3school-report-checkbox');
			}
			var node = element;
			while (node && node !== reportBuilder) {
				if (node.classList && node.classList.contains('s3school-report-checkbox')) {
					return node;
				}
				node = node.parentNode;
			}
			return null;
		};

		var syncLabelInput = function(checkbox) {
			var wrapper = getCheckboxWrapper(checkbox);
			if (!wrapper) {
				return;
			}
			var labelInput = wrapper.querySelector('.s3school-report-label-input');
			if (!labelInput) {
				return;
			}
			labelInput.disabled = !checkbox.checked;
		};

		var setCheckboxState = function(state) {
			forEachCheckbox(function(checkbox) {
				checkbox.checked = state;
				syncLabelInput(checkbox);
			});
		};

		if (selectAllBtn) {
			selectAllBtn.addEventListener('click', function(event) {
				event.preventDefault();
				setCheckboxState(true);
			});
		}

		if (clearAllBtn) {
			clearAllBtn.addEventListener('click', function(event) {
				event.preventDefault();
				setCheckboxState(false);
			});
		}

		forEachCheckbox(function(checkbox) {
			checkbox.addEventListener('change', function() {
				syncLabelInput(checkbox);
			});
			syncLabelInput(checkbox);
		});
	});
</script>

<?php
if ($import_stage === 'mapping' && !empty($mapping_header)) :
	$s3school_unused_empty_text = esc_js(__('Every column is mapped. Nice work!', 's3schoolManagment'));
?>
	<script>
		document.addEventListener('DOMContentLoaded', function() {
			var container = document.getElementById('s3school-unused-columns');
			if (!container) {
				return;
			}

			var availableRaw = container.dataset.available || '[]';
			var available;
			try {
				available = JSON.parse(availableRaw);
			} catch (error) {
				available = [];
			}

			if (!Array.isArray(available)) {
				available = [];
			}

			var ajaxUrl = container.dataset.ajaxUrl || '';
			var listEl = container.querySelector('.s3school-unused-list');
			var form = container.closest('form');

			if (!listEl || !form) {
				return;
			}

			var checkboxWrapper = document.getElementById('s3school_confirm_wrapper');
			var checkbox = document.getElementById('s3school_skip_existing');
			var submitBtn = document.getElementById('s3school_run_import');
			var currentUnused = 0;

			var applyUnusedState = function(unusedCount) {
				currentUnused = unusedCount;
				if (checkboxWrapper) {
					if (unusedCount === 0) {
						checkboxWrapper.style.display = 'none';
						checkboxWrapper.classList.remove('s3school-highlight');
					} else {
						checkboxWrapper.style.display = '';
						checkboxWrapper.classList.add('s3school-highlight');
					}
				}

				if (submitBtn) {
					if (unusedCount === 0) {
						submitBtn.disabled = false;
					} else {
						submitBtn.disabled = !(checkbox && checkbox.checked);
					}
				}

				if (unusedCount === 0 && checkbox) {
					checkbox.checked = false;
				}
			};

			var handleCheckboxChange = function() {
				if (!checkbox || !submitBtn) {
					return;
				}

				if (currentUnused === 0) {
					submitBtn.disabled = false;
					return;
				}

				submitBtn.disabled = !checkbox.checked;
			};

			if (checkbox) {
				checkbox.addEventListener('change', handleCheckboxChange);
			}

			var selectFields = Array.prototype.slice.call(form.querySelectorAll('.s3school-map-field select'));
			var nonceField = form.querySelector('input[name="s3school_import_nonce"]');

			var renderUnused = function(items) {
				var unusedItems = Array.isArray(items) ? items.filter(function(label) {
					return typeof label === 'string' && label !== '';
				}) : [];

				listEl.innerHTML = '';
				if (!unusedItems.length) {
					var emptyItem = document.createElement('li');
					emptyItem.className = 's3school-unused-empty';
					emptyItem.textContent = '<?php echo $s3school_unused_empty_text; ?>';
					listEl.appendChild(emptyItem);
					applyUnusedState(0);
					return;
				}

				unusedItems.forEach(function(label) {
					var li = document.createElement('li');
					li.textContent = label;
					listEl.appendChild(li);
				});

				applyUnusedState(unusedItems.length);
			};

			var getSelectedValues = function() {
				return selectFields.map(function(field) {
					return field.value || '';
				});
			};

			var fallbackUnused = function() {
				var selectedValues = getSelectedValues().filter(function(value) {
					return value && value !== '__skip';
				});
				return available.filter(function(label) {
					return selectedValues.indexOf(label) === -1;
				});
			};

			var requestUnused = function() {
				if (!ajaxUrl || !nonceField) {
					renderUnused(fallbackUnused());
					return;
				}

				var payload = new window.FormData();
				payload.append('action', 's3school_unused_columns');
				payload.append('nonce', nonceField.value);
				payload.append('available', JSON.stringify(available));
				payload.append('selected', JSON.stringify(getSelectedValues()));

				container.classList.add('s3school-unused-loading');

				window.fetch(ajaxUrl, {
						method: 'POST',
						credentials: 'same-origin',
						body: payload
					})
					.then(function(response) {
						if (!response.ok) {
							throw new Error('Request failed');
						}
						return response.json();
					})
					.then(function(result) {
						if (!result || !result.success || !result.data || !Array.isArray(result.data.unused)) {
							renderUnused(fallbackUnused());
							return;
						}
						renderUnused(result.data.unused);
					})
					.catch(function() {
						renderUnused(fallbackUnused());
					})
					.finally(function() {
						container.classList.remove('s3school-unused-loading');
					});
			};

			if (selectFields.length) {
				selectFields.forEach(function(field) {
					field.addEventListener('change', requestUnused);
				});
			}

			renderUnused(fallbackUnused());
			requestUnused();
		});
	</script>
<?php endif; ?>

<script>
	document.addEventListener('DOMContentLoaded', function() {
		// Auto-sync stdCurrentClass and className selections
		var stdCurrentClassSelect = document.querySelector('select[name="s3school_column_map[stdCurrentClass]"]');
		var classNameSelect = document.querySelector('select[name="s3school_column_map[className]"]');

		// Auto-sync year and stdCurntYear selections
		var yearSelect = document.querySelector('select[name="s3school_column_map[year]"]');
		var stdCurntYearSelect = document.querySelector('select[name="s3school_column_map[stdCurntYear]"]');

		var syncSelections = function(sourceSelect, targetSelect) {
			var selectedValue = sourceSelect.value;
			if (selectedValue && selectedValue !== '__skip') {
				targetSelect.value = selectedValue;
				// Trigger change event on target select to update unused columns
				var changeEvent = new Event('change', { bubbles: true });
				targetSelect.dispatchEvent(changeEvent);
			}
		};

		if (stdCurrentClassSelect && classNameSelect) {
			stdCurrentClassSelect.addEventListener('change', function() {
				syncSelections(stdCurrentClassSelect, classNameSelect);
			});

			classNameSelect.addEventListener('change', function() {
				syncSelections(classNameSelect, stdCurrentClassSelect);
			});
		}

		if (yearSelect && stdCurntYearSelect) {
			yearSelect.addEventListener('change', function() {
				syncSelections(yearSelect, stdCurntYearSelect);
			});

			stdCurntYearSelect.addEventListener('change', function() {
				syncSelections(stdCurntYearSelect, yearSelect);
			});
		}
	});
</script>

<script>
	(function($) {
		var exportForm = $('#s3school-export-form');
		if (!exportForm.length) {
			return;
		}

		var classSelect = $('#s3school_export_class');
		var sectionSelect = $('#s3school_export_section');
		var groupSelect = $('#s3school_export_group');
		var yearSelect = $('#s3school_export_year');
		var templateUrlEl = document.getElementById('s3school-template-url');
		var templateUrl = templateUrlEl ? templateUrlEl.textContent.trim() : '';
		var ajaxEndpoint = templateUrl ? templateUrl.replace(/\/$/, '') + '/inc/ajaxAction.php' : '';

		if (!ajaxEndpoint || !classSelect.length || !sectionSelect.length || !groupSelect.length || !yearSelect.length) {
			return;
		}

		var labels = {
			section: '<?php echo esc_js(__('All Sections', 's3schoolManagment')); ?>',
			group: '<?php echo esc_js(__('All Groups', 's3schoolManagment')); ?>',
			year: '<?php echo esc_js(__('All Years', 's3schoolManagment')); ?>'
		};

		var preserved = {
			section: '<?php echo esc_js((string) $selected_section); ?>',
			group: '<?php echo esc_js((string) $selected_group); ?>',
			year: '<?php echo esc_js((string) $selected_year); ?>'
		};

		var disableDependents = function(disabled) {
			sectionSelect.prop('disabled', disabled);
			groupSelect.prop('disabled', disabled);
			yearSelect.prop('disabled', disabled);
		};

		var resetSelect = function($select, placeholderValue, placeholderLabel) {
			$select.empty();
			$select.append($('<option>', {
				value: placeholderValue,
				text: placeholderLabel
			}));
			$select.val(placeholderValue);
		};

		var injectOptions = function($select, html, placeholderValue, placeholderLabel, selectedValue) {
			$select.empty();
			$select.append($('<option>', {
				value: placeholderValue,
				text: placeholderLabel
			}));
			if (html) {
				$select.append(html);
			}
			if (selectedValue !== undefined && selectedValue !== null && selectedValue !== '' && $select.find('option[value="' + selectedValue + '"]').length) {
				$select.val(String(selectedValue));
			} else {
				$select.val(placeholderValue);
			}
		};

		var loadOptions = function(type, classId, successCallback) {
			$.ajax({
				url: ajaxEndpoint,
				method: 'POST',
				data: {
					class: classId,
					type: type
				},
				dataType: 'html'
			}).done(function(html) {
				successCallback(html || '');
			}).fail(function(jqXHR) {
				console.error('Failed to load export filter data (' + type + '):', jqXHR.statusText);
			});
		};

		var updateDependentFields = function(classId, preserveSelection) {
			if (!classId || classId === '0') {
				disableDependents(true);
				resetSelect(sectionSelect, '0', labels.section);
				resetSelect(groupSelect, '0', labels.group);
				resetSelect(yearSelect, '', labels.year);
				return;
			}

			disableDependents(false);
			resetSelect(sectionSelect, '0', labels.section);
			resetSelect(groupSelect, '0', labels.group);
			resetSelect(yearSelect, '', labels.year);

			var targetSection = preserveSelection ? preserved.section : '0';
			var targetGroup = preserveSelection ? preserved.group : '0';
			var targetYear = preserveSelection ? preserved.year : '';

			loadOptions('getSection', classId, function(html) {
				injectOptions(sectionSelect, html, '0', labels.section, targetSection);
			});

			loadOptions('getGroupsByClass', classId, function(html) {
				injectOptions(groupSelect, html, '0', labels.group, targetGroup);
			});

			loadOptions('getYears', classId, function(html) {
				injectOptions(yearSelect, html, '', labels.year, targetYear);
				if ((!targetYear || targetYear === '') && yearSelect.length) {
					var latestYear = null;
					yearSelect.find('option').each(function() {
						var optionValue = this.value;
						if (optionValue !== '') {
							latestYear = optionValue;
						}
					});
					if (latestYear !== null) {
						yearSelect.val(latestYear);
					}
				}
			});
		};

		classSelect.on('change', function() {
			preserved.section = '0';
			preserved.group = '0';
			preserved.year = '';
			updateDependentFields(this.value, false);
		});

		var initialClass = classSelect.val();
		if (initialClass && initialClass !== '0') {
			updateDependentFields(initialClass, true);
		} else {
			disableDependents(true);
		}
	})(jQuery);
</script>



<?php
get_footer();
?>