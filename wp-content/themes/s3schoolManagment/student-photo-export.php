<?php
/*
Template Name: Student Photo Export
*/

defined('ABSPATH') || exit;

$passcode = 's3school_export_2026_jumanjii';
if ( ! isset( $_POST['passcode'] ) || $_POST['passcode'] !== $passcode ) {
    wp_die( 'Access Denied: Please close this page and try again.' );
}

global $wpdb;
$barnomala_school_id = '103'; // Kanaighat Precadet School



$primary_table  = $wpdb->prefix . 'ct_student';
$fallback_table = 'ct_student';
$students_table = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $primary_table));
if (!$students_table) {
	$students_table = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $fallback_table));
}
if (!$students_table) {
	wp_die('Student table not found in the database.');
}
$students_table_safe = '`' . str_replace('`', '``', $students_table) . '`';

/**
 * Helper function for image path resolution (copied from import-export-students.php)
 */
if (!function_exists('s3school_resolve_student_image_path')) {
    function s3school_resolve_student_image_path($raw_url) {
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
            untrailingslashit(site_url('/')),
        ]));
        foreach ($site_variants as $variant) {
            if ($variant !== '') {
                $map[$variant] = untrailingslashit(ABSPATH);
            }
        }

        foreach ($map as $base_url => $base_dir) {
            if (strpos($raw_url, $base_url) === 0) {
                $relative = ltrim(substr($raw_url, strlen($base_url)), '/');
                $path = $base_dir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
                if (file_exists($path)) {
                    return $path;
                }
            }
        }

        $relative_path = ABSPATH . str_replace('/', DIRECTORY_SEPARATOR, ltrim($raw_url, '/'));
        if (file_exists($relative_path)) {
            return $relative_path;
        }

        return '';
    }
}

// Photo Export Processing Logic
if (isset($_POST['s3school_export_photos'])) {
	if (!class_exists('ZipArchive')) {
		wp_die('Photo exports require the ZipArchive PHP extension.');
	}

	$photo_years = isset($_POST['s3school_export_photos_year']) ? (array) $_POST['s3school_export_photos_year'] : [];
	$photo_years = array_map('sanitize_text_field', array_map('wp_unslash', $photo_years));
	$photo_years = array_filter(array_map('trim', $photo_years));

	$fetch_all = empty($photo_years) || in_array('all', $photo_years, true);

	$where_clauses = ["IFNULL(s.stdImg, '') <> ''", "s.stdStatus = 1"];
	$params = [];

	if (!$fetch_all) {
		$placeholders = implode(', ', array_fill(0, count($photo_years), '%s'));
		$where_clauses[] = "s.stdCurntYear IN ($placeholders)";
		foreach ($photo_years as $py) {
			$params[] = $py;
		}
	}

	$where_sql = implode(' AND ', $where_clauses);
	$query = "SELECT s.studentid, s.uid, s.stdName, s.stdPhone, s.stdImg, s.stdCurntYear
		FROM {$students_table_safe} s
		WHERE {$where_sql}";

	if (!empty($params)) {
		$records = $wpdb->get_results($wpdb->prepare($query, $params), ARRAY_A);
	} else {
		$records = $wpdb->get_results($query, ARRAY_A);
	}

	if (empty($records)) {
		wp_die('No student photos found for the selected criteria.');
	}

    if (!function_exists('wp_tempnam')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
	$temp_file = wp_tempnam('s3school_student_photos');
	if ($temp_file === false) {
		wp_die('Unable to prepare the photo archive.');
	}

	$zip = new ZipArchive();
	if ($zip->open($temp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
		@unlink($temp_file);
		wp_die('Unable to create the photo archive.');
	}

	$added_files = 0;
	$used_names = [];
	foreach ($records as $record) {
		$image_path = s3school_resolve_student_image_path($record['stdImg']);
		if ($image_path === '' || !is_file($image_path)) {
			continue;
		}

		// Generate unique filename
		$fullName = trim((string) $record['stdName']);
		$personCode = $barnomala_school_id . trim((string) $record['uid']);
		$currentYear = trim((string) $record['stdCurntYear']);
		$base_name = $fullName . '+' . $currentYear . '_' . $personCode;
		$base_name = preg_replace('/[^A-Za-z0-9_\- +]+/', '', $base_name); // Clean up filename
		if ($base_name === '' || $base_name === '_') {
			$base_name = 'student_' . $personCode;
		}

		$extension = pathinfo($image_path, PATHINFO_EXTENSION);
		$extension = $extension !== '' ? '.' . strtolower($extension) : '';

		$entry_name = $base_name . $extension;
		$counter = 1;
		while (isset($used_names[$entry_name])) {
			$entry_name = $base_name . '-' . $counter . $extension;
			++$counter;
		}
		$used_names[$entry_name] = true;

		if ($zip->addFile($image_path, $entry_name)) {
			++$added_files;
		}
	}

	$zip->close();

	if ($added_files === 0) {
		@unlink($temp_file);
		wp_die('No valid student photos were available for export.');
	}

	$archive_filename = sanitize_file_name('student-photos_' . implode('-', $photo_years) . '_' . gmdate('md-His') . '.zip');

	nocache_headers();
	header('Content-Type: application/zip');
	header('Content-Disposition: attachment; filename="' . $archive_filename . '"');
	header('Content-Length: ' . filesize($temp_file));
	readfile($temp_file);
	@unlink($temp_file);
	exit;
}

// Prepare UI data
$year_options = $wpdb->get_results(
	"SELECT stdCurntYear, COUNT(*) as total FROM {$students_table_safe} WHERE stdCurntYear IS NOT NULL AND stdCurntYear <> '' GROUP BY stdCurntYear ORDER BY stdCurntYear DESC",
	ARRAY_A
);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Photo Export - Public Access</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            background-color: #f0f2f5;
            color: #1c1e21;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 90vh;
            margin: 0;
            padding: 20px;
        }
        .s3school-card {
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            padding: 30px;
        }
        .s3school-pill {
            display: inline-block;
            background-color: #e7f3ff;
            color: #1877f2;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 15px;
        }
        h2 {
            margin: 0 0 15px 0;
            font-size: 24px;
            font-weight: 700;
        }
        p {
            color: #65676b;
            font-size: 15px;
            line-height: 1.4;
            margin-bottom: 25px;
        }
        .year-selection-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            gap: 12px;
            margin-bottom: 30px;
        }
        .year-label {
            display: flex;
            align-items: center;
            padding: 10px;
            background: #f8f9fa;
            border: 1px solid #dddfe2;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .year-label:hover {
            background: #ebedf0;
        }
        .year-label input {
            margin-right: 10px;
        }
        .year-label strong {
            font-size: 14px;
        }
        .year-label span {
            font-size: 12px;
            color: #8a8d91;
            margin-left: 4px;
        }
        .button-primary {
            background-color: #1877f2;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            cursor: pointer;
            transition: background 0.2s;
        }
        .button-primary:hover {
            background-color: #166fe5;
        }
        .button-primary:disabled {
            background-color: #ebedf0;
            color: #bcc0c4;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="s3school-card">
        <span class="s3school-pill">Photo Export Tool</span>
        <h2>Export Student Photos</h2>
        <p>Select the academic years to bundle student photos into a ZIP archive for Hikvision integrations.</p>
        
        <form method="post" id="s3school-photo-export-form">
            <input type="hidden" name="passcode" value="<?php echo esc_attr($_POST['passcode']); ?>" />
            <input type="hidden" name="s3school_export_photos" value="1" />
            
            <div class="year-selection-grid">
                <label class="year-label">
                    <input type="checkbox" id="all_years" value="all" />
                    <strong>All Years</strong>
                </label>
                <?php if (!empty($year_options)) : ?>
                    <?php foreach ($year_options as $year_option) : ?>
                        <label class="year-label">
                            <input type="checkbox" name="s3school_export_photos_year[]" class="year-checkbox" value="<?php echo esc_attr($year_option['stdCurntYear']); ?>" />
                            <strong><?php echo esc_html($year_option['stdCurntYear']); ?></strong>
                            <span>(<?php echo intval($year_option['total']); ?>)</span>
                        </label>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <button class="button-primary" type="submit" id="export-submit" disabled>Export Photos (ZIP)</button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const allCheckbox = document.getElementById('all_years');
            const yearCheckboxes = document.querySelectorAll('.year-checkbox');
            const submitBtn = document.getElementById('export-submit');

            function updateBtnState() {
                const anyChecked = Array.from(yearCheckboxes).some(cb => cb.checked) || allCheckbox.checked;
                submitBtn.disabled = !anyChecked;
            }

            allCheckbox.addEventListener('change', function() {
                yearCheckboxes.forEach(cb => cb.checked = allCheckbox.checked);
                updateBtnState();
            });

            yearCheckboxes.forEach(cb => {
                cb.addEventListener('change', function() {
                    if (!this.checked) {
                        allCheckbox.checked = false;
                    } else if (Array.from(yearCheckboxes).every(c => c.checked)) {
                        allCheckbox.checked = true;
                    }
                    updateBtnState();
                });
            });
        });
    </script>
</body>
</html>
