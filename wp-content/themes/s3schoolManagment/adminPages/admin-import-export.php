<?php
/*
Template Name: Admin Import Export Students
*/

defined('ABSPATH') || exit;

if (!is_user_logged_in()) {
	auth_redirect();
}

if (!current_user_can('manage_options')) {
	wp_die(esc_html__('You do not have permission to access this page.', 's3schoolManagment'));
}

global $wpdb;

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

$student_columns = $wpdb->get_col('SHOW COLUMNS FROM ' . $students_table, 0);

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

$all_export_columns = array_merge($student_columns, $additional_export_columns);

if (empty($student_columns)) {
	wp_die(esc_html__('Unable to determine student table columns.', 's3schoolManagment'));
}

$students_table_safe = '`' . str_replace('`', '``', $students_table) . '`';
$studentinfo_table_safe = '`' . str_replace('`', '``', $studentinfo_table) . '`';
$class_table_safe = '`' . str_replace('`', '``', $class_table) . '`';
$section_table_safe = '`' . str_replace('`', '``', $section_table) . '`';
$group_table_safe = '`' . str_replace('`', '``', $group_table) . '`';

$class_options = $wpdb->get_results("SELECT classid, className FROM {$class_table_safe} ORDER BY className ASC", ARRAY_A);
$section_options = $wpdb->get_results("SELECT sectionid, sectionName, forClass FROM {$section_table_safe} ORDER BY sectionName ASC", ARRAY_A);
$group_options = $wpdb->get_results("SELECT groupId, groupName FROM {$group_table_safe} ORDER BY groupName ASC", ARRAY_A);
$year_options = $wpdb->get_col("SELECT DISTINCT infoYear FROM {$studentinfo_table_safe} WHERE infoYear IS NOT NULL AND infoYear <> '' ORDER BY infoYear ASC");

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

$selected_class = isset($_GET['ziisc_export_class']) ? absint($_GET['ziisc_export_class']) : 0;
$selected_section = isset($_GET['ziisc_export_section']) ? absint($_GET['ziisc_export_section']) : 0;
$selected_group = isset($_GET['ziisc_export_group']) ? absint($_GET['ziisc_export_group']) : 0;
$selected_year = isset($_GET['ziisc_export_year']) ? sanitize_text_field(wp_unslash($_GET['ziisc_export_year'])) : '';
$selected_year = trim($selected_year);

if (!function_exists('ziisc_post_value')) {
	function ziisc_post_value($key, $default = null)
	{
		return isset($_POST[$key]) ? wp_unslash($_POST[$key]) : $default;
	}
}

if (!function_exists('ziisc_read_csv_header')) {
	function ziisc_read_csv_header($file_path)
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

if (!function_exists('ziisc_excel_column_to_index')) {
	function ziisc_excel_column_to_index($cell_ref)
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

if (!function_exists('ziisc_convert_excel_to_csv')) {
	function ziisc_convert_excel_to_csv($file_path)
	{
		if (!class_exists('ZipArchive')) {
			return new WP_Error('ziisc_excel_zip_missing', esc_html__('Excel imports require the ZipArchive PHP extension.', 's3schoolManagment'));
		}

		$zip = new ZipArchive();
		if ($zip->open($file_path) !== true) {
			return new WP_Error('ziisc_excel_open_failed', esc_html__('Unable to open the uploaded Excel file.', 's3schoolManagment'));
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
			return new WP_Error('ziisc_excel_sheet_missing', esc_html__('Unable to locate the first worksheet inside the Excel file.', 's3schoolManagment'));
		}

		$sheet_xml = simplexml_load_string($sheet_contents);
		if ($sheet_xml === false || !isset($sheet_xml->sheetData)) {
			$zip->close();
			return new WP_Error('ziisc_excel_sheet_invalid', esc_html__('The Excel worksheet appears to be corrupt or unreadable.', 's3schoolManagment'));
		}

		$rows = [];
		foreach ($sheet_xml->sheetData->row as $row) {
			$current_row   = [];
			$previous_index = -1;
			foreach ($row->c as $cell) {
				$ref       = isset($cell['r']) ? (string) $cell['r'] : '';
				$col_index = ziisc_excel_column_to_index($ref);
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
			return new WP_Error('ziisc_excel_empty', esc_html__('The Excel file does not contain any readable rows.', 's3schoolManagment'));
		}

		$csv_path = wp_tempnam('ziisc_student_import');
		if (!$csv_path) {
			return new WP_Error('ziisc_excel_temp_failed', esc_html__('Unable to create a temporary CSV file for the Excel conversion.', 's3schoolManagment'));
		}

		$handle = fopen($csv_path, 'w');
		if ($handle === false) {
			return new WP_Error('ziisc_excel_temp_unwritable', esc_html__('Unable to write the converted CSV file.', 's3schoolManagment'));
		}

		foreach ($rows as $row_values) {
			fputcsv($handle, $row_values);
		}

		fclose($handle);

		return $csv_path;
	}
}

if (!function_exists('ziisc_handle_unused_columns_ajax')) {
	function ziisc_handle_unused_columns_ajax()
	{
		if (!current_user_can('manage_options')) {
			wp_send_json_error(['message' => esc_html__('Unauthorized request.', 's3schoolManagment')], 403);
		}

		$nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
		if (!wp_verify_nonce($nonce, 'ziisc_import_students')) {
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

	add_action('wp_ajax_ziisc_unused_columns', 'ziisc_handle_unused_columns_ajax');
}

$import_stage   = 'upload';
$import_result  = null;
$mapping_token  = '';
$mapping_header = [];
$cleanup_token  = false;
$stored_data    = null;

if (isset($_POST['ziisc_import_stage']) && isset($_POST['ziisc_import_nonce'])) {
	if (!check_admin_referer('ziisc_import_students', 'ziisc_import_nonce')) {
		wp_die(esc_html__('Invalid import request.', 's3schoolManagment'));
	}

	$stage = sanitize_text_field(ziisc_post_value('ziisc_import_stage'));

	if ($stage === 'prepare_mapping') {
		require_once ABSPATH . 'wp-admin/includes/file.php';

		if (!isset($_FILES['ziisc_students_file']) || empty($_FILES['ziisc_students_file']['tmp_name'])) {
			$import_result = ['type' => 'error', 'message' => esc_html__('No file uploaded.', 's3schoolManagment')];
		} else {
			$uploaded = wp_handle_upload($_FILES['ziisc_students_file'], [
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
					$converted = ziisc_convert_excel_to_csv($uploaded_path);
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
					$header = ziisc_read_csv_header($csv_path);

					if (empty($header)) {
						foreach (array_unique($cleanup_files) as $file_path) {
							if ($file_path && file_exists($file_path)) {
								@unlink($file_path);
							}
						}
						$import_result = ['type' => 'error', 'message' => esc_html__('The uploaded file appears to be empty or invalid.', 's3schoolManagment')];
					} else {
						$mapping_token = wp_generate_password(16, false, false);
						set_transient('ziisc_student_import_' . $mapping_token, [
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
		$mapping_token = sanitize_text_field(ziisc_post_value('ziisc_import_token', ''));
		if ($mapping_token !== '') {
			$stored_data = get_transient('ziisc_student_import_' . $mapping_token);
		}

		if (!$stored_data || empty($stored_data['path']) || !file_exists($stored_data['path'])) {
			$import_result = ['type' => 'error', 'message' => esc_html__('Unable to locate the uploaded CSV. Please upload again.', 's3schoolManagment')];
			$cleanup_token = true;
		} else {
			$header = ziisc_read_csv_header($stored_data['path']);

			if (empty($header)) {
				$import_result = ['type' => 'error', 'message' => esc_html__('The uploaded CSV appears to be empty or invalid.', 's3schoolManagment')];
				$cleanup_token = true;
			} else {
				$column_map_raw = isset($_POST['ziisc_column_map']) ? (array) $_POST['ziisc_column_map'] : [];
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
					if (isset($_POST['ziisc_default_info']) && is_array($_POST['ziisc_default_info'])) {
						$default_info_input = wp_unslash($_POST['ziisc_default_info']);
					}

					$default_class = isset($default_info_input['className']) ? absint($default_info_input['className']) : 0;
					if ($default_class && !isset($class_lookup[$default_class])) {
						$default_class = 0;
					}

					$default_section = isset($default_info_input['sectionName']) ? absint($default_info_input['sectionName']) : 0;
					if ($default_section && !isset($section_lookup[$default_section])) {
						$default_section = 0;
					}

					$default_group = isset($default_info_input['groupName']) ? absint($default_info_input['groupName']) : 0;
					if ($default_group && !isset($group_lookup[$default_group])) {
						$default_group = 0;
					}

					$default_year = isset($default_info_input['year']) ? sanitize_text_field($default_info_input['year']) : '';
					$default_year = trim($default_year);

					$apply_default_class   = !isset($valid_info_map['className']) && $default_class;
					$apply_default_section = !isset($valid_info_map['sectionName']) && $default_section;
					$apply_default_group   = !isset($valid_info_map['groupName']) && $default_group;
					$apply_default_year    = !isset($valid_info_map['year']) && $default_year !== '';

					$header_index = array_flip($header);
					$inserted     = 0;
					$skipped      = 0;

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
								if (!isset($header_index[$header_label])) {
									continue;
								}

								$value    = $data_row[$header_index[$header_label]] ?? '';
								$trimmed  = trim((string) $value);
								$row_data[$column_name] = $trimmed === '' ? null : $trimmed;
							}

							// Process studentinfo columns
							$info_data = [];
							foreach ($valid_info_map as $column_name => $header_label) {
								if (!isset($header_index[$header_label])) {
									continue;
								}

								$value    = $data_row[$header_index[$header_label]] ?? '';
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
									}
								} elseif ($column_name === 'sectionName') {
									// Look up section ID by name
									$section_id = $wpdb->get_var($wpdb->prepare(
										"SELECT sectionid FROM `" . str_replace('`', '``', $section_table) . "` WHERE sectionName = %s LIMIT 1",
										$trimmed
									));
									if ($section_id) {
										$info_data['infoSection'] = $section_id;
									}
								} elseif ($column_name === 'year') {
									$info_data['infoYear'] = $trimmed;
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

							if ($apply_default_class) {
								$info_data['infoClass'] = $default_class;
							}

							if ($apply_default_section) {
								$info_data['infoSection'] = $default_section;
							}

							if ($apply_default_group) {
								$info_data['infoGroup'] = $default_group;
							}

							if ($apply_default_year) {
								$info_data['infoYear'] = $default_year;
							}

							if (empty($row_data) && empty($info_data)) {
								++$skipped;
								continue;
							}

							// Always insert a new student, never use or check studentid
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
								'stdNationality' => '',
								'stdReligion' => '',
								'stdAdmitClass' => 0,
								'stdAdmitYear' => '',
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
								continue;
							}

							// Insert student info if we have any
							if (!empty($info_data)) {
								$info_data['infoStdid'] = $new_student_id;
								$info_insert = $wpdb->insert($studentinfo_table, $info_data);
								if ($info_insert === false) {
									error_log('Studentinfo insert failed for student ID ' . $new_student_id . ': ' . $wpdb->last_error);
								}
							}

							++$inserted;
						}

						fclose($handle);

						$import_result = [
							'type' => 'success',
							'message' => sprintf(
								esc_html__('Import completed. Inserted: %1$d, Skipped: %2$d.', 's3schoolManagment'),
								$inserted,
								$skipped
							),
						];
						$cleanup_token = true;
					}
				}
			}
		}
	}

	if ($cleanup_token && $mapping_token) {
		$transient_key = 'ziisc_student_import_' . $mapping_token;
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

// Export handler triggers before any HTML output.
if (isset($_GET['ziisc_export_students']) && isset($_GET['ziisc_export_nonce'])) {
	if (!check_admin_referer('ziisc_export_students', 'ziisc_export_nonce')) {
		wp_die(esc_html__('Invalid export request.', 's3schoolManagment'));
	}

	$columns = $all_export_columns;

	// Build SELECT clause with JOINs for additional columns
	$select_parts = [];
	foreach ($columns as $column_name) {
		if (in_array($column_name, $student_columns, true)) {
			$select_parts[] = 's.`' . str_replace('`', '``', $column_name) . '`';
		} elseif ($column_name === 'className') {
			$select_parts[] = 'c.className';
		} elseif ($column_name === 'sectionName') {
			$select_parts[] = 'sec.sectionName';
		} elseif ($column_name === 'year') {
			$select_parts[] = 'si.infoYear AS year';
		} elseif ($column_name === 'groupName') {
			$select_parts[] = 'g.groupName';
		} elseif ($column_name === 'roll') {
			$select_parts[] = 'si.infoRoll AS roll';
		} elseif ($column_name === 'optionalSubjects') {
			$select_parts[] = 'si.infoOptionals AS optionalSubjects';
		} elseif ($column_name === 'fourthSubject') {
			$select_parts[] = 'si.info4thSub AS fourthSubject';
		}
	}

	$columns_sql = implode(', ', $select_parts);

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

	if (!empty($where_clauses)) {
		$query .= ' WHERE ' . implode(' AND ', $where_clauses);
		$query = $wpdb->prepare($query, $query_params);
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

	if (!empty($columns)) {
		fputcsv($output, $columns);
	}

	foreach ($records as $record) {
		$row = [];
		foreach ($columns as $column_name) {
			$row[] = isset($record[$column_name]) ? $record[$column_name] : '';
		}

		fputcsv($output, $row);
	}

	fclose($output);
	exit;
}

$default_info_submission = [];
if (isset($_POST['ziisc_default_info']) && is_array($_POST['ziisc_default_info'])) {
	$default_info_submission = wp_unslash($_POST['ziisc_default_info']);
}

$default_selected_class = isset($default_info_submission['className']) ? absint($default_info_submission['className']) : 0;
if ($default_selected_class && !isset($class_lookup[$default_selected_class])) {
	$default_selected_class = 0;
}

$default_selected_section = isset($default_info_submission['sectionName']) ? absint($default_info_submission['sectionName']) : 0;
if ($default_selected_section && !isset($section_lookup[$default_selected_section])) {
	$default_selected_section = 0;
}

$default_selected_group = isset($default_info_submission['groupName']) ? absint($default_info_submission['groupName']) : 0;
if ($default_selected_group && !isset($group_lookup[$default_selected_group])) {
	$default_selected_group = 0;
}

$default_selected_year = isset($default_info_submission['year']) ? sanitize_text_field($default_info_submission['year']) : '';
$default_selected_year = trim($default_selected_year);

get_header();
?>

<style>
	.ziisc-panel {
		max-width: 960px;
		margin: 0 auto 4rem;
		padding: 2rem 1.5rem;
		background: #f9fafc;
		border-radius: 18px;
		box-shadow: 0 18px 45px rgba(15, 23, 42, .12);
		font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
	}

	.ziisc-panel h1 {
		margin: 0 0 1.75rem;
		font-weight: 700;
		color: #0f172a;
		display: flex;
		align-items: center;
		gap: .75rem;
	}

	.ziisc-panel .notice {
		border-radius: 14px;
		padding: 1rem 1.25rem;
		margin-bottom: 1.5rem;
		font-weight: 600;
		box-shadow: 0 10px 25px rgba(15, 23, 42, .08);
	}

	.ziisc-flex {
		display: flex;
		flex-direction: column;
		gap: 1.5rem;
	}

	.ziisc-card {
		background: #fff;
		border-radius: 16px;
		padding: 1.75rem;
		box-shadow: 0 15px 35px rgba(15, 23, 42, .1);
		display: flex;
		flex-direction: column;
		gap: 1.1rem;
	}

	.ziisc-card h2 {
		margin: 0;
		font-weight: 600;
		color: #1e293b;
	}

	.ziisc-card p {
		margin: 0;
		color: #475569;
		line-height: 1.55;
	}

	.ziisc-card form {
		display: flex;
		flex-direction: column;
		gap: 1.25rem;
	}

	.ziisc-card button.button.button-primary {
		color: #fff;
		align-self: flex-start;
		border-radius: 999px;
		padding: .65rem 1.7rem;
		font-weight: 600;
		background: linear-gradient(135deg, #2563eb, #1d4ed8);
		border: none;
		box-shadow: 0 12px 24px rgba(37, 99, 235, .25);
	}

	.ziisc-pill {
		display: inline-flex;
		align-items: center;
		gap: .4rem;
		padding: .35rem .85rem;
		border-radius: 999px;
		font-weight: 600;
		background: #eef2ff;
		color: #4338ca;
	}

	.ziisc-map-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
		gap: 1rem 1.25rem;
	}

	.ziisc-map-field {
		background: #f8fafc;
		border: 1px solid #e2e8f0;
		border-radius: 12px;
		padding: 1rem 1.2rem;
		display: flex;
		flex-direction: column;
		gap: .45rem;
	}

	.ziisc-map-field strong {
		color: #1f2937;
		font-weight: 600;
		letter-spacing: .01em;
	}

	.ziisc-map-field select {
		width: 100%;
		border-radius: 10px;
		border: 1px solid #cbd5f5;
		padding: .55rem .75rem;
		transition: border-color .2s ease, box-shadow .2s ease;
	}

	.ziisc-map-field select:focus {
		border-color: #2563eb;
		box-shadow: 0 0 0 3px rgba(37, 99, 235, .15);
		outline: none;
	}

	.ziisc-map-heading {
		grid-column: 1/-1;
		font-weight: 700;
		color: #0f172a;
		text-transform: uppercase;
		letter-spacing: .08em;
		margin-top: .5rem;
	}

	.ziisc-upload {
		display: flex;
		flex-direction: column;
		gap: .6rem;
	}

	.ziisc-upload input[type=file] {
		border: 1px dashed #94a3b8;
		border-radius: 12px;
		padding: 1.3rem;
		background: #fafcff;
		transition: border-color .2s ease, background .2s ease;
	}

	.ziisc-upload input[type=file]:hover {
		border-color: #2563eb;
		background: #f1f5ff;
	}

	.ziisc-defaults {
		margin-top: 1rem;
		padding: 1.25rem 1.5rem;
		background: #ffffff;
		border: 1px solid #e2e8f0;
		border-radius: 14px;
		display: flex;
		flex-direction: column;
		gap: .85rem;
	}

	.ziisc-defaults-title {
		font-weight: 600;
		color: #1f2937;
	}

	.ziisc-defaults-description {
		margin: 0;
		color: #475569;
	}

	.ziisc-defaults-field {
		display: flex;
		gap: .45rem;
		color: #1f2937;
	}

	.ziisc-defaults-field select,
	.ziisc-defaults-field input[type=text] {
		border-radius: 10px;
		border: 1px solid #cbd5f5;
		padding: .55rem .75rem;
		transition: border-color .2s ease, box-shadow .2s ease;
	}

	.ziisc-defaults-field select:focus,
	.ziisc-defaults-field input[type=text]:focus {
		border-color: #2563eb;
		box-shadow: 0 0 0 3px rgba(37, 99, 235, .15);
		outline: none;
	}

	.ziisc-export-filters {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
		gap: 1rem;
	}

	.ziisc-export-filters label {
		display: flex;
		flex-direction: column;
		gap: .35rem;
		font-weight: 600;
		color: #1e293b;
	}

	.ziisc-export-filters select {
		border-radius: 10px;
		border: 1px solid #cbd5f5;
		padding: .55rem .75rem;
		background: #fff;
		transition: border-color .2s ease, box-shadow .2s ease;
	}

	.ziisc-export-filters select:focus {
		border-color: #2563eb;
		box-shadow: 0 0 0 3px rgba(37, 99, 235, .15);
		outline: none;
	}

	.ziisc-unused-wrapper {
		margin-top: 2rem;
		position: relative;
		border: 1px solid #cbd5f5;
		border-radius: 12px;
		padding: 1rem 1.2rem 1.2rem;
		background: #f8faff;
		box-shadow: inset 0 1px 0 rgba(148, 163, 184, .15);
	}

	.ziisc-unused-wrapper strong {
		display: block;
		margin-bottom: .35rem;
		color: #1e293b;
	}

	.ziisc-unused-description {
		margin: 0 0 .75rem;
		color: #475569;
		opacity: .7;
		display: flex;
	}

	.ziisc-unused-list {
		margin: 0;
		padding-left: 1.25rem;
		color: #ff0000;
	}

	.ziisc-unused-list li {
		margin-bottom: .3rem;
	}

	.ziisc-unused-empty {
		list-style: none;
		padding-left: 0;
		color: #64748b;
	}

	.ziisc-unused-wrapper.ziisc-unused-loading {
		opacity: .7;
	}

	.ziisc-unused-wrapper.ziisc-unused-loading::after {
		content: 'Updating...';
		position: absolute;
		top: .75rem;
		right: 1rem;
		font-weight: 600;
		color: #2563eb;
	}

	.ziisc-confirm-import {
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

	.ziisc-confirm-import label {
		display: flex;
		align-items: center;
		gap: .55rem;
		margin: 0;
		cursor: pointer;
	}

	.ziisc-confirm-import input[type=checkbox] {
		transform: scale(1.1);
	}

	.ziisc-confirm-import.ziisc-highlight {
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
</style>

<div class="ziisc-panel">
	<?php if ($import_result) : ?>
		<div class="notice notice-<?php echo esc_attr($import_result['type']); ?>">
			<p><?php echo esc_html($import_result['message']); ?></p>
		</div>
	<?php endif; ?>

	<div class="ziisc-flex">
		<div class="ziisc-card">
			<span class="ziisc-pill"><?php esc_html_e('Guided Import', 's3schoolManagment'); ?></span>
			<h2><?php esc_html_e('Import Students', 's3schoolManagment'); ?></h2>
			<p><?php esc_html_e('Upload your CSV or Excel (.xlsx) file, map header labels to database columns, then run the import with confidence.', 's3schoolManagment'); ?></p>
			<?php if ($import_stage === 'mapping' && !empty($mapping_header)) : ?>
				<form method="post">
					<?php wp_nonce_field('ziisc_import_students', 'ziisc_import_nonce'); ?>
					<input type="hidden" name="ziisc_import_stage" value="process_import" />
					<input type="hidden" name="ziisc_import_token" value="<?php echo esc_attr($mapping_token); ?>" />
					<div class="ziisc-map-grid">
						<span class="ziisc-map-heading"><?php esc_html_e('Student Table Columns', 's3schoolManagment'); ?></span>
						<?php foreach ($student_columns as $column_name) : ?>
							<div class="ziisc-map-field">
								<strong><?php echo esc_html($column_name); ?></strong>
								<select name="ziisc_column_map[<?php echo esc_attr($column_name); ?>]">
									<option value="__skip"><?php esc_html_e('Skip', 's3schoolManagment'); ?></option>
									<?php foreach ($mapping_header as $header_label) : ?>
										<option value="<?php echo esc_attr($header_label); ?>" <?php selected($column_name === $header_label); ?>><?php echo esc_html($header_label); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						<?php endforeach; ?>
						<span class="ziisc-map-heading"><?php esc_html_e('Student Info & Related Columns', 's3schoolManagment'); ?></span>
						<?php foreach ($additional_export_columns as $column_name) : ?>
							<div class="ziisc-map-field">
								<strong><?php echo esc_html($column_name); ?></strong>
								<select name="ziisc_column_map[<?php echo esc_attr($column_name); ?>]">
									<option value="__skip"><?php esc_html_e('Skip', 's3schoolManagment'); ?></option>
									<?php foreach ($mapping_header as $header_label) : ?>
										<option value="<?php echo esc_attr($header_label); ?>" <?php selected($column_name === $header_label); ?>><?php echo esc_html($header_label); ?></option>
									<?php endforeach; ?>
								</select>
							</div>
						<?php endforeach; ?>
					</div>

					<div class="ziisc-defaults">
						<p class="ziisc-defaults-description"><?php esc_html_e('Set default values if Class, Section, Group or Year are skipped (Optional)', 's3schoolManagment'); ?></p>
						<div class="ziisc-defaults-grid">
							<label class="ziisc-defaults-field">
								<span><?php esc_html_e('Default Class', 's3schoolManagment'); ?></span>
								<select name="ziisc_default_info[className]" id="ziisc_default_class">
									<option value=""><?php esc_html_e('No default', 's3schoolManagment'); ?></option>
									<?php if (!empty($class_options)) : ?>
										<?php foreach ($class_options as $class_option) : ?>
											<?php $class_id = (int) $class_option['classid']; ?>
											<option value="<?php echo esc_attr($class_id); ?>" <?php selected($default_selected_class, $class_id); ?>><?php echo esc_html($class_option['className']); ?></option>
										<?php endforeach; ?>
									<?php endif; ?>
								</select>
							</label>
							<label class="ziisc-defaults-field">
								<span><?php esc_html_e('Default Section', 's3schoolManagment'); ?></span>
								<select name="ziisc_default_info[sectionName]" id="ziisc_default_section" <?php disabled(!$default_selected_class); ?>>
									<option value=""><?php esc_html_e('No default', 's3schoolManagment'); ?></option>
									<?php if (!empty($section_options)) : ?>
										<?php foreach ($section_options as $section_option) : ?>
											<?php
											$section_id    = (int) $section_option['sectionid'];
											$section_class = isset($section_option['forClass']) ? (int) $section_option['forClass'] : 0;
											?>
											<option value="<?php echo esc_attr($section_id); ?>" data-class="<?php echo esc_attr($section_class); ?>" <?php selected($default_selected_section, $section_id); ?>><?php echo esc_html($section_option['sectionName']); ?></option>
										<?php endforeach; ?>
									<?php endif; ?>
								</select>
							</label>
							<label class="ziisc-defaults-field">
								<span><?php esc_html_e('Default Group', 's3schoolManagment'); ?></span>
								<select name="ziisc_default_info[groupName]">
									<option value=""><?php esc_html_e('No default', 's3schoolManagment'); ?></option>
									<?php if (!empty($group_options)) : ?>
										<?php foreach ($group_options as $group_option) : ?>
											<?php $group_id = (int) $group_option['groupId']; ?>
											<option value="<?php echo esc_attr($group_id); ?>" <?php selected($default_selected_group, $group_id); ?>><?php echo esc_html($group_option['groupName']); ?></option>
										<?php endforeach; ?>
									<?php endif; ?>
								</select>
							</label>
							<label class="ziisc-defaults-field">
								<span><?php esc_html_e('Default Year', 's3schoolManagment'); ?></span>
								<input type="text" name="ziisc_default_info[year]" value="<?php echo esc_attr($default_selected_year); ?>" placeholder="<?php esc_attr_e('e.g. 2025', 's3schoolManagment'); ?>" list="ziisc-default-year-options" />
								<?php if (!empty($year_options)) : ?>
									<datalist id="ziisc-default-year-options">
										<?php foreach ($year_options as $year_option) : ?>
											<option value="<?php echo esc_attr($year_option); ?>"></option>
										<?php endforeach; ?>
									</datalist>
								<?php endif; ?>
							</label>
						</div>
					</div>

					<div
						id="ziisc-unused-columns"
						class="ziisc-unused-wrapper"
						data-available="<?php echo esc_attr(wp_json_encode(array_values($mapping_header))); ?>"
						data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
						<strong><?php esc_html_e('Unmapped File Columns (Columns from your file that are not mapped yet)', 's3schoolManagment'); ?></strong>
						<ul class="ziisc-unused-list" aria-live="polite"></ul>
					</div>

					<div class="ziisc-confirm-import" id="ziisc_confirm_wrapper">
						<label for="ziisc_skip_existing">
							<input type="checkbox" id="ziisc_skip_existing" name="ziisc_skip_existing" value="1" />
							<span><?php esc_html_e('Import anyway skipping unmapped fields. (Skipped fields leave existing data untouched.)', 's3schoolManagment'); ?></span>
						</label>
					</div>
					<button class="button button-primary" id="ziisc_run_import" type="submit" disabled><?php esc_html_e('Run Import', 's3schoolManagment'); ?></button>
				</form>
			<?php else : ?>
				<form method="post" enctype="multipart/form-data">
					<?php wp_nonce_field('ziisc_import_students', 'ziisc_import_nonce'); ?>
					<input type="hidden" name="ziisc_import_stage" value="prepare_mapping" />
					<div class="ziisc-upload">
						<label for="ziisc_students_file"><?php esc_html_e('Upload CSV or Excel (.xlsx) file', 's3schoolManagment'); ?></label>
						<input type="file" id="ziisc_students_file" name="ziisc_students_file" accept=".csv,.xlsx" required />
						<small><?php esc_html_e('Ensure the first row contains column labels that match your data. Excel files are converted to CSV automatically.', 's3schoolManagment'); ?></small>
					</div>
					<button class="button button-primary" type="submit"><?php esc_html_e('Next: Map Columns', 's3schoolManagment'); ?></button>
				</form>
			<?php endif; ?>
		</div>


		<div class="ziisc-card">
			<span class="ziisc-pill"><?php esc_html_e('Quick Export', 's3schoolManagment'); ?></span>
			<h2><?php esc_html_e('Export Students', 's3schoolManagment'); ?></h2>
			<p><?php esc_html_e('Download every student and related detail in a single CSV tailored for spreadsheets and analytics.', 's3schoolManagment'); ?></p>
			<form method="get" action="<?php echo esc_url(get_permalink()); ?>">
				<input type="hidden" name="ziisc_export_students" value="1" />
				<?php wp_nonce_field('ziisc_export_students', 'ziisc_export_nonce'); ?>
				<div class="ziisc-export-filters">
					<label>
						<span><?php esc_html_e('Class', 's3schoolManagment'); ?></span>
						<select name="ziisc_export_class">
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
						<select name="ziisc_export_section">
							<option value="0" <?php selected($selected_section, 0); ?>><?php esc_html_e('All Sections', 's3schoolManagment'); ?></option>
							<?php if (!empty($section_options)) : ?>
								<?php foreach ($section_options as $section_option) : ?>
									<option value="<?php echo esc_attr($section_option['sectionid']); ?>" <?php selected((int) $section_option['sectionid'], $selected_section); ?>><?php echo esc_html($section_option['sectionName']); ?></option>
								<?php endforeach; ?>
							<?php endif; ?>
						</select>
					</label>
					<label>
						<span><?php esc_html_e('Group', 's3schoolManagment'); ?></span>
						<select name="ziisc_export_group">
							<option value="0" <?php selected($selected_group, 0); ?>><?php esc_html_e('All Groups', 's3schoolManagment'); ?></option>
							<?php if (!empty($group_options)) : ?>
								<?php foreach ($group_options as $group_option) : ?>
									<option value="<?php echo esc_attr($group_option['groupId']); ?>" <?php selected((int) $group_option['groupId'], $selected_group); ?>><?php echo esc_html($group_option['groupName']); ?></option>
								<?php endforeach; ?>
							<?php endif; ?>
						</select>
					</label>
					<label>
						<span><?php esc_html_e('Year', 's3schoolManagment'); ?></span>
						<select name="ziisc_export_year">
							<option value="" <?php selected($selected_year, ''); ?>><?php esc_html_e('All Years', 's3schoolManagment'); ?></option>
							<?php if (!empty($year_options)) : ?>
								<?php foreach ($year_options as $year_option) : ?>
									<option value="<?php echo esc_attr($year_option); ?>" <?php selected($year_option, $selected_year); ?>><?php echo esc_html($year_option); ?></option>
								<?php endforeach; ?>
							<?php endif; ?>
						</select>
					</label>
				</div>
				<button class="button button-primary" type="submit"><?php esc_html_e('Export CSV', 's3schoolManagment'); ?></button>
			</form>
		</div>
	</div>
</div>

<?php
if ($import_stage === 'mapping' && !empty($mapping_header)) :
	$ziisc_unused_empty_text = esc_js(__('Every column is mapped. Nice work!', 's3schoolManagment'));
?>
	<script>
		document.addEventListener('DOMContentLoaded', function() {
			var container = document.getElementById('ziisc-unused-columns');
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
			var listEl = container.querySelector('.ziisc-unused-list');
			var form = container.closest('form');

			if (!listEl || !form) {
				return;
			}

			var checkboxWrapper = document.getElementById('ziisc_confirm_wrapper');
			var checkbox = document.getElementById('ziisc_skip_existing');
			var submitBtn = document.getElementById('ziisc_run_import');
			var currentUnused = 0;

			var applyUnusedState = function(unusedCount) {
				currentUnused = unusedCount;
				if (checkboxWrapper) {
					if (unusedCount === 0) {
						checkboxWrapper.style.display = 'none';
						checkboxWrapper.classList.remove('ziisc-highlight');
					} else {
						checkboxWrapper.style.display = '';
						checkboxWrapper.classList.add('ziisc-highlight');
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

			var selectFields = Array.prototype.slice.call(form.querySelectorAll('.ziisc-map-field select'));
			var nonceField = form.querySelector('input[name="ziisc_import_nonce"]');

			var renderUnused = function(items) {
				var unusedItems = Array.isArray(items) ? items.filter(function(label) {
					return typeof label === 'string' && label !== '';
				}) : [];

				listEl.innerHTML = '';
				if (!unusedItems.length) {
					var emptyItem = document.createElement('li');
					emptyItem.className = 'ziisc-unused-empty';
					emptyItem.textContent = '<?php echo $ziisc_unused_empty_text; ?>';
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
				payload.append('action', 'ziisc_unused_columns');
				payload.append('nonce', nonceField.value);
				payload.append('available', JSON.stringify(available));
				payload.append('selected', JSON.stringify(getSelectedValues()));

				container.classList.add('ziisc-unused-loading');

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
						container.classList.remove('ziisc-unused-loading');
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

<?php
get_footer();
?>