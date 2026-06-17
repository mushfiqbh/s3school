<?php
/*
** Template Name: Admin ID card
*/
global $wpdb;
global $s3sRedux;

// ==========================================
// AJAX ACTIONS - LOCAL HANDLER
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'])) {
  while (ob_get_level()) { ob_end_clean(); }

  // ------------------------------------------
  // Get Years
  // ------------------------------------------
  if ($_POST['type'] == 'getYears') {
    $class = $_POST['class'];
    $years = $wpdb->get_results("SELECT infoYear FROM ct_studentinfo WHERE infoClass = $class GROUP BY infoYear ORDER BY infoYear ASC");
    if (empty($years)) { echo "<option value=''>No Student In this class</option>"; }
    else { echo "<option value=''>Year</option>"; }
    foreach ($years as $year) { echo "<option value='{$year->infoYear}'>{$year->infoYear}</option>"; }
    exit;
  }

  // ------------------------------------------
  // Get Sections
  // ------------------------------------------
  elseif ($_POST['type'] == 'getSection') {
    $class = $_POST['class'];
    $sections = $wpdb->get_results("SELECT sectionid,sectionName FROM ct_section WHERE forClass = '$class' ORDER BY sectionName");
    if (!empty($sections)) {
      echo "<option value=''>Section</option>";
      foreach ($sections as $section) { echo "<option value='{$section->sectionid}'>{$section->sectionName}</option>"; }
    } else {
      echo "<option value=''>No sections available</option>";
    }
    exit;
  }
}

$conStyle0 = '';
$conStyle = "margin: 0; font-size: 12px;line-height: 1;margin-bottom:3px;color:blue;";
$conStyle2 = $conStyle0 . " font-size: 13px; color:blue;";
$conStyle3 = "margin: 0; font-size: 15px;line-height: 1;margin-bottom:1px;color:blue;";
$conStyle4 = "margin: 0; font-size: 15px;line-height: 1;margin-bottom:1px;color:black;";
$selected = @$_GET['design'];
if (!@$_GET['design']) {
	$selected = 1;
}

// modify table 'ct_student' to add new column 'uid' if not exists
$column_exists = $wpdb->get_results("SHOW COLUMNS FROM ct_student LIKE 'uid'");
if (empty($column_exists)){
	$wpdb->query("ALTER TABLE ct_student ADD COLUMN uid VARCHAR(16)");
}

function getIdDesignImageUrl($no, $person_type = 'student')
{
	global $wpdb;
	$no = intval($no);
	if ($no <= 0) return false;

	$type = getIdDesignType($no, $person_type);
	$prefix = ($person_type === 'teacher') ? 'id-teacher-design-' : 'id-design-';
	$option_name = ($type === 'back') ? $prefix . 'back-' . $no : $prefix . $no;

	$image_url = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT option_value FROM sm_options WHERE option_name = %s",
			$option_name
		)
	);

	return $image_url ? esc_url($image_url) : false;
}

function getIdDesignBackImageUrl($no, $person_type = 'student')
{
	global $wpdb;

	$no = intval($no);

	if ($no <= 0) {
		return false;
	}

	$prefix = ($person_type === 'teacher') ? 'id-teacher-design-' : 'id-design-';
	$option_name = $prefix . 'back-' . $no;

	$image_url = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT option_value FROM sm_options WHERE option_name = %s",
			$option_name
		)
	);

	return $image_url ? esc_url($image_url) : false;
}

function getIdDesignConfig($no, $person_type = 'student')
{
    global $wpdb;
    $no = intval($no);
    if ($no <= 0) return [];
    
    $option_name = ($person_type === 'teacher') ? 'id_card_teacher_design_' . $no . '_config' : 'id_card_design_' . $no . '_config';
    $config_json = $wpdb->get_var($wpdb->prepare(
        "SELECT option_value FROM sm_options WHERE option_name = %s",
        $option_name
    ));
    
    $defaults = [
        'design_name' => '',
        'design_type' => 'front',
        'show_image' => true,
        'show_name' => true,
        'show_id' => true,
        'show_mpo_index' => false,
		'show_class_section' => true,
        'show_class' => true,
        'show_section' => ($person_type !== 'teacher'),
        'show_group' => ($person_type !== 'teacher'),
        'show_roll' => true,
        'show_year' => true,
        'show_dob' => true,
        'show_blood' => true,
        'show_phone' => true,
        'show_father' => true,
        'show_mother' => true,
        'show_address' => true,
        'show_inst_address' => true,
        'show_student_address' => true,
        'show_inst_phone' => true,
        'show_logo' => true,
        'show_inst_name' => true,
        'show_signature' => true,
        'image_shape' => 'square',
        'image_border_size' => 0,
        'image_border_color' => '#000000',
        'image_margin_top' => 75,
		'content_margin_top' => 165,
		'student_image_size' => 85,
		'student_name_color' => '#065499',
		'line_height' => 1,
        'orientation' => 'portrait'
    ];

    if ($person_type === 'teacher' && $no == 1) {
        $defaults = array_merge($defaults, [
            'design_name' => '', 'design_type' => 'front', 'orientation' => 'portrait', 'image_shape' => 'rounded',
            'image_border_size' => '', 'image_border_color' => '#000000', 'image_margin_top' => '75',
            'show_logo' => false, 'show_inst_name' => true, 'show_image' => true, 'show_name' => true, 'show_id' => false,
			'show_class_section' => true,
            'show_class' => true, 'show_section' => false, 'show_group' => false, 'show_roll' => true, 'show_year' => true, 'show_dob' => true,
            'show_blood' => true, 'show_phone' => false, 'show_father' => false, 'show_mother' => false,
            'show_address' => false, 'show_inst_address' => false, 'show_inst_phone' => false, 'show_signature' => true
        ]);
    } elseif ($person_type !== 'teacher' && $no == 1) {
        $defaults = array_merge($defaults, [
            'design_name' => 'Standard Potrait', 'design_type' => 'front', 'orientation' => 'portrait', 'image_shape' => 'rounded',
            'image_border_size' => '4', 'image_border_color' => '#0078b7', 'image_margin_top' => '75',
            'show_logo' => false, 'show_inst_name' => false, 'show_image' => true, 'show_name' => true, 'show_id' => true,
			'show_class_section' => true,
            'show_class' => true, 'show_section' => true, 'show_group' => true, 'show_roll' => false, 'show_year' => true, 'show_dob' => true,
            'show_blood' => false, 'show_phone' => true, 'show_father' => false, 'show_mother' => false,
            'show_address' => false, 'show_signature' => true
        ]);
    } elseif ($person_type !== 'teacher' && $no == 2) {
        $defaults = array_merge($defaults, [
            'design_name' => 'Standard Potrait', 'design_type' => 'back', 'orientation' => 'portrait', 'image_shape' => 'square',
            'image_border_size' => '', 'image_border_color' => '#000000', 'image_margin_top' => '',
            'show_logo' => true, 'show_inst_name' => true, 'show_image' => false, 'show_name' => false, 'show_id' => false,
			'show_class_section' => true,
            'show_class' => false, 'show_section' => false, 'show_group' => false, 'show_roll' => false, 'show_year' => false, 'show_dob' => true,
            'show_blood' => true, 'show_phone' => true, 'show_father' => false, 'show_mother' => false,
            'show_address' => true, 'show_inst_address' => true, 'show_inst_phone' => true, 'show_signature' => true
        ]);
    }

    if ($config_json) {
        $stored = json_decode($config_json, true);
        if (is_array($stored)) {
            return array_merge($defaults, $stored);
        }
    }
    return $defaults;
}

function getAvailableDesignNumbers($person_type = 'student') {
    global $wpdb;
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
    $numbers = [1];
    foreach ($results as $row) {
        if (preg_match($config_pattern, $row->option_name, $matches)) {
            $numbers[] = intval($matches[1]);
        } elseif (preg_match($img_pattern, $row->option_name, $matches)) {
            $numbers[] = intval($matches[1]);
        }
    }
    $unique = array_unique($numbers);
    sort($unique);
    return $unique;
}

function getIdDesignName($no, $person_type = 'student') {
    $config = getIdDesignConfig($no, $person_type);
    return !empty($config['design_name']) ? $config['design_name'] : 'Design ' . $no;
}

function getIdDesignType($no, $person_type = 'student') {
    $config = getIdDesignConfig($no, $person_type);
    return !empty($config['design_type']) ? $config['design_type'] : 'front';
}

function generateBarcodeSVG($uid = null)
{
	// Default UID if empty
	if (empty($uid)) {
		$uid = '2610100';
	}

	// Sanitize UID
	$uid = htmlspecialchars($uid, ENT_QUOTES, 'UTF-8');

	// Barcode API URL
	$barcodeUrl = "https://barcode.orcascan.com/?type=code128&data=" . urlencode($uid);

	// Fetch SVG content
	$svg = @file_get_contents($barcodeUrl);

	// Fail-safe if API is unreachable
	if ($svg === false) {
		return '';
	}

	return $svg;
}

require_once __DIR__ . '/functions/html-snapshot.php';
?>


<?php
$design_no = 1; // change dynamically if needed

$design_url  = getIdDesignImageUrl($design_no);
?>


<!-- Modal for uploading background images for 5 designs -->

<style>
	/* Integrated Modal Styles */
	.modal-integrated {
		display: flex;
		position: fixed;
		z-index: 9999;
		left: 0;
		top: 0;
		width: 100vw;
		height: 100vh;
		background: rgba(0, 0, 0, 0);
		align-items: center;
		justify-content: center;
		visibility: hidden;
		opacity: 0;
		transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
		backdrop-filter: blur(0px);
	}

	.modal-integrated.active {
		visibility: visible;
		opacity: 1;
		background: rgba(0, 0, 0, 0.5);
		backdrop-filter: blur(8px);
	}

	.modal-integrated-content {
		background: #fff;
		border-radius: 20px;
		width: 90vw;
		height: 85vh;
		max-width: 1200px;
		display: flex;
		flex-direction: column;
		position: relative;
		transform: translateY(30px) scale(0.95);
		transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
		box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
		overflow: hidden;
	}

	.modal-integrated.active .modal-integrated-content {
		transform: translateY(0) scale(1);
	}

	.modal-integrated-header {
		padding: 20px 30px;
		border-bottom: 1px solid #f0f0f0;
		display: flex;
		justify-content: space-between;
		align-items: center;
		background: #fafafa;
	}

	.modal-integrated-header h4 {
		margin: 0;
		font-weight: 700;
		color: #1a1a1a;
		font-size: 1.25rem;
	}

	.modal-integrated-body {
		flex: 1;
		display: flex;
		overflow: hidden;
	}

	.modal-integrated-left {
		flex: 1.2;
		padding: 30px;
		border-right: 1px solid #f0f0f0;
		overflow-y: auto;
	}

	.modal-integrated-right {
		flex: 1;
		padding: 20px;
		background: #f1f5f9;
		overflow-y: auto;
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: flex-start;
	}

	.preview-container {
		position: sticky;
		top: 0;
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: 20px;
	}

	/* Preview Card Specific CSS */
	#liveCardPreview.card-parent {
		box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
		margin: 0;
		flex-shrink: 0;
		background-color: #fff;
	}

	.preview-actual-size.portrait-mode {
		width: 5.3cm;
		height: 8.7cm;
	}
	.preview-actual-size.landscape-mode {
		width: 8.7cm;
		height: 5.3cm;
	}

	.modal-integrated-footer {
		padding: 15px 30px;
		border-top: 1px solid #f0f0f0;
		text-align: right;
		background: #fafafa;
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 10px;
	}

	.integrated-config-grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
		gap: 8px;
	}

	.integrated-config-item {
		display: flex;
		align-items: center;
		gap: 8px;
		padding: 8px 12px;
		background: #fff;
		border: 1px solid #e5e7eb;
		border-radius: 8px;
		transition: all 0.2s;
		cursor: pointer;
		font-size: 13px;
	}

	.integrated-config-item:hover {
		border-color: #3b82f6;
		background: #eff6ff;
	}

	.integrated-config-item input[type="checkbox"] {
		width: 16px;
		height: 16px;
		cursor: pointer;
	}

	/* Upload Preview Area */
	.bg-preview-container {
		width: 100%;
		max-width: 350px;
		aspect-ratio: 5.3 / 8.7;
		background: #f0f0f0;
		border-radius: 12px;
		margin-bottom: 20px;
		position: relative;
		overflow: hidden;
		box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
		border: 4px solid #fff;
	}

	.bg-preview-image {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	.upload-overlay {
		position: absolute;
		bottom: 0;
		left: 0;
		right: 0;
		background: rgba(0, 0, 0, 0.6);
		padding: 15px;
		text-align: center;
		color: #fff;
		opacity: 0;
		transition: opacity 0.3s;
	}

	.bg-preview-container:hover .upload-overlay {
		opacity: 1;
	}

	.upload-status-badge {
		margin-top: 10px;
		padding: 5px 12px;
		border-radius: 20px;
		font-size: 12px;
		font-weight: 600;
	}

	.status-idle { background: #f3f4f6; color: #374151; }
	.status-uploading { background: #dbeafe; color: #1e40af; animation: pulse 1s infinite; }
	.status-success { background: #dcfce7; color: #166534; }
	.status-error { background: #fee2e2; color: #991b1b; }

	.close-integrated-modal {
		font-size: 28px;
		cursor: pointer;
		color: #6b7280;
		transition: color 0.2s;
		line-height: 1;
	}

	.close-integrated-modal:hover {
		color: #ef4444;
	}

	/* Global Page Loader */
	.global-fetch-loader {
		display: none;
		position: fixed;
		top: 50%;
		left: 50%;
		transform: translate(-50%, -50%);
		background: #fff;
		padding: 15px 30px;
		border-radius: 50px;
		box-shadow: 0 15px 30px rgba(0,0,0,0.15);
		z-index: 10001;
		font-weight: 700;
		color: #3b82f6;
		align-items: center;
		gap: 12px;
		border: 1px solid #dbeafe;
	}

	.global-fetch-loader.active {
		display: flex;
	}

	.loader-spinner {
		width: 20px;
		height: 20px;
		border: 3px solid #dbeafe;
		border-top: 3px solid #3b82f6;
		border-radius: 50%;
		animation: spin 0.8s linear infinite;
	}

	@keyframes spin {
		0% { transform: rotate(0deg); }
		100% { transform: rotate(360deg); }
	}
    /* Dynamic Design Styles from idCard-Design.php */
	.conStyle {
		font-size: 12px;
		color: black;
		margin: 0 0 2px 15px;
		text-align: left;
	}

	.card-parent {
		width: 5.3cm;
		height: 8.7cm;
		background: #f0f0f0;
		display: inline-block;
		margin: 5px;
		overflow: hidden;
		background-size: cover;
		background-position: center; 
		position:relative;
		page-break-inside: avoid;
		break-inside: avoid;
	}
	.card-parent.portrait-mode {
		width: 5.3cm;
		height: 8.7cm;
	}
	.card-parent.landscape-mode {
		width: 8.7cm;
		height: 5.3cm;
	}

	.card-image {
		border-radius:49px;
		border: 4px solid #1F396D;
		margin: 78px 0px 0px 5px;
	}

	.std-name {
		font-weight: bold;
		border-radius: 40px;
		margin-bottom: 0px;
		font-size:13.5px;
		margin: 5px -2px 0px 5px;
		color: var(--student-name-color, #065499);
		padding: 0px 20px;
		margin-bottom:2px;
		min-height:25px;
		margin-left:-6px;
		display: flex;
		align-items: center;
		justify-content: center; 
		text-transform: uppercase; 
		padding: 0px 18px; 
		font-family: sans-serif;
	}

	.info-row {
		display: grid;
		grid-template-columns: 20% auto;
		line-height: var(--line-height, 1);
		margin-bottom: 2px;
	}

	.preview-container.teacher .info-row {
		display: grid;
		grid-template-columns: 33% auto;
		line-height: var(--line-height, 1);
		margin-bottom: 2px;
	}

	.info-label {
		font-weight: bold;
		white-space: nowrap;
		vertical-align: top;
	}

	.landscape-mode .student-image {
		border: 1px solid black !important;
		margin-left: 10px !important;
		float: left !important;
		height: var(--student-image-height, 70px) !important;
		width: var(--student-image-width, 65px) !important;
		object-fit: cover !important;
	}

	.landscape-mode .info-row {
		display: flex !important;
		margin-bottom: 2px !important;
		line-height: var(--line-height, 1) !important;
	}

	.landscape-mode .info-row b {
		min-width: 60px !important;
		display: inline-block !important;
	}

	.info-value {
		text-transform: uppercase;
		font-size: 10.5px;
		display: grid;
		grid-template-columns: 10px auto;
		white-space: normal;
	}

	.colon {
		text-align: right;
		padding-right: 5px;
	}

	.value-text {
		text-indent: 0;
	}

	.card-table {
		position:absolute; margin: 1px 0 0 15px; width:95%; font-size:12px; color:black; text-align:left; border-collapse:collapse;
	}
	#previewTeacherPortraitBox .info-row {
		display: grid;
		grid-template-columns: 33% auto;
		line-height: var(--line-height, 1);
		margin-bottom: 2px;
		font-size: 10.5px;
	}
	#previewTeacherPortraitBox .info-row b {
		white-space: nowrap;
		vertical-align: top;
	}
	#previewTeacherPortraitBox .teacher-designation {
		position: absolute;
		width: 100%;
		left: 0;
		margin: 0 !important;
		text-align: center;
		font-size: 12px;
		font-weight: bold;
		color: black;
		text-transform: uppercase;
		z-index: 2;
	}
    
    /* Config Modal Styles */
    .config-row {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 20px;
    }
    .config-item {
        flex: 1 1 45%;
        display: flex;
        align-items: center;
        gap: 10px;
        background: #fdfdfd;
        padding: 8px;
        border: 1px solid #eee;
        border-radius: 6px;
    }

    /* Fix clipping for design hover previews */
    .panel, .panel-body {
        overflow: visible !important;
    }
    
    .hoverShowImg .hoverImg {
        z-index: 1000 !important;
        box-shadow: 0 10px 25px rgba(0,0,0,0.3);
        border: 2px solid #3b82f6;
    }

	/* Samples Modal Grid */
	.samples-grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, 5.3cm);
		gap: 30px;
		padding: 30px;
		justify-content: center;
	}

	.sample-item {
		width: 5.3cm;
		height: 8.7cm;
		background: #fff;
		border: 1px solid #e2e8f0;
		border-radius: 8px;
		transition: all 0.3s;
		text-align: center;
		position: relative;
		overflow: hidden;
		box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
	}

	.sample-item:hover {
		transform: translateY(-5px) scale(1.02);
		box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
		border-color: #3b82f6;
	}

	.sample-item img {
		width: 100%;
		height: 100%;
		object-fit: cover;
		display: block;
	}

	.sample-item span {
		position: absolute;
		bottom: 0;
		left: 0;
		right: 0;
		background: rgba(255, 255, 255, 0.9);
		padding: 8px;
		font-weight: 700;
		color: #1e40af;
		font-size: 11px;
		border-top: 1px solid #e2e8f0;
		transition: transform 0.3s;
	}

	.sample-item:hover span {
		background: #3b82f6;
		color: #fff;
	}
</style>


<!-- Global Loader Element -->
<div id="globalFetchLoader" class="global-fetch-loader">
	<div class="loader-spinner"></div>
	<span>Fetching Settings...</span>
</div>

<!-- Modal for ID Card Samples -->
<div id="modalIdSamples" class="modal-integrated">
	<div class="modal-integrated-content" style="height: 70vh; max-width: 1000px;">
		<div class="modal-integrated-header">
			<h4>ID Card Design Samples</h4>
			<span class="close-integrated-modal" onclick="closeSamplesModal()">&times;</span>
		</div>
		<div class="modal-integrated-body" style="overflow-y: auto; display: block;">
			<div class="samples-grid">
				<div class="sample-item">
					<img src="<?php echo home_url("/img/id-sample-1.png") ?>" alt="Design 1">
					<span>Design Sample 1</span>
				</div>
				<div class="sample-item">
					<img src="<?php echo home_url("/img/id-sample-2.jpg") ?>" alt="Design 2">
					<span>Design Sample 2</span>
				</div>
				<div class="sample-item">
					<img src="<?php echo home_url("/img/id-sample-3.jpg") ?>" alt="Design 3">
					<span>Design Sample 3</span>
				</div>
				<div class="sample-item">
					<img src="<?php echo home_url("/img/id-sample-4.jpg") ?>" alt="Design 4">
					<span>Design Sample 4</span>
				</div>
				<div class="sample-item">
					<img src="<?php echo home_url("/img/id-sample-5.jpg") ?>" alt="Design 5">
					<span>Design Sample 5</span>
				</div>
				<div class="sample-item">
					<img src="<?php echo home_url("/img/id-sample-6.jpg") ?>" alt="Design 6">
					<span>Design Sample 6</span>
				</div>
			</div>
		</div>
		<div class="modal-integrated-footer">
			<button type="button" class="btn btn-default" onclick="closeSamplesModal()">Close</button>
		</div>
	</div>
</div>

<!-- Combined Integrated Modal -->
<div id="modalDesignIntegrated" class="modal-integrated">
	<div class="modal-integrated-content">
		<div class="modal-integrated-header">
			<h4>Design Settings & Background</h4>
			<div style="display: flex; align-items: center; gap: 20px;">
				<div style="display: flex; align-items: center; gap: 15px;">
					<div style="display: flex; align-items: center; gap: 8px;">
						<label style="margin: 0; font-weight: 600;">Person Type:</label>
						<select id="configPersonType" class="form-control" style="width: auto; height: 38px;" onchange="changePersonType(this.value)">
							<option value="student">Student</option>
							<option value="teacher">Teacher</option>
						</select>
					</div>

					<div style="display: flex; align-items: center; gap: 8px;">
						<label style="margin: 0; font-weight: 600;">Active Design:</label>
						<div style="display: flex; gap: 5px; align-items: center;">
							<select id="configDesignSelect" class="form-control" style="width: auto; height: 38px;" onchange="loadDesignIntegrated(this.value)">
								<option value="1">Design 1</option>
							</select>
							<button id="createNewDesignBtn" class="btn btn-success" title="Create New Design" onclick="createNewDesign()" style="height: 38px; padding: 0 12px;">
								<i class="fa fa-plus"></i> NEW
							</button>
						</div>
					</div>
				</div>
				<span class="close-integrated-modal" onclick="closeDesignConfigModal()">&times;</span>
			</div>
		</div>

		<div class="modal-integrated-body">
			<!-- Left Pane: Fields -->
			<div class="modal-integrated-left">
				<form id="designConfigForm">
					<!-- General Settings -->
					<h5 style="margin-top: 5px; margin-bottom: 12px; font-weight: 700; color: #374151; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">
						<i class="fa fa-cog" style="margin-right: 8px; color: #3b82f6;"></i> General Settings
					</h5>
					<div class="integrated-config-grid" style="margin-bottom: 25px;">
						<div class="integrated-config-item" style="flex-direction: column; align-items: flex-start; gap: 4px;">
							<label style="font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 2px;">Design Name</label>
							<input type="text" name="design_name" class="form-control" style="width: 100%; height: 32px; padding: 4px 8px; font-size: 13px;" placeholder="e.g. Standard Portrait">
						</div>
						<div class="integrated-config-item" style="flex-direction: column; align-items: flex-start; gap: 4px;">
							<label style="font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 2px;">Design Type</label>
							<select name="design_type" class="form-control" style="width: 100%; height: 32px; padding: 4px 8px; font-size: 13px;" onchange="updateLivePreview()">
								<option value="front">Front Side</option>
								<option value="back">Back Side</option>
							</select>
						</div>
						<div class="integrated-config-item" style="flex-direction: column; align-items: flex-start; gap: 4px;">
							<label style="font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 2px;">Orientation</label>
							<select name="orientation" class="form-control" style="width: 100%; height: 32px; padding: 4px 8px; font-size: 13px;" onchange="updateLivePreview()">
								<option value="portrait">Portrait</option>
								<option value="landscape">Landscape</option>
							</select>
						</div>
					</div>

					<!-- Image Styling Section -->
					<div id="imageStylingSection">
						<h5 style="margin-top: 5px; margin-bottom: 12px; font-weight: 700; color: #374151; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">
							<i class="fa fa-paint-brush" style="margin-right: 8px; color: #3b82f6;"></i> Image Styling
						</h5>
						<div class="integrated-config-grid" style="margin-bottom: 25px;">
							<div class="integrated-config-item" style="flex-direction: column; align-items: flex-start; gap: 4px;">
								<label style="font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 2px;">Shape</label>
								<select name="image_shape" class="form-control" style="width: 100%; height: 32px; padding: 4px 8px; font-size: 13px;">
									<option value="square">Square</option>
									<option value="rounded">Rounded (Circle)</option>
								</select>
							</div>
							<div class="integrated-config-item" style="flex-direction: column; align-items: flex-start; gap: 4px;">
								<label style="font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 2px;">Image Size (px)</label>
								<input type="number" name="student_image_size" class="form-control" style="width: 100%; height: 32px; padding: 4px 8px; font-size: 13px;" min="30" max="150" placeholder="85">
							</div>
							<div class="integrated-config-item" style="flex-direction: column; align-items: flex-start; gap: 4px;">
								<label style="font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 2px;">Name Color</label>
								<input type="color" name="student_name_color" class="form-control" style="width: 100%; height: 32px; padding: 2px; border: 1px solid #d1d5db;" value="#065499">
							</div>
							<div class="integrated-config-item" style="flex-direction: column; align-items: flex-start; gap: 4px;">
								<label style="font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 2px;">Border (px)</label>
								<input type="number" name="image_border_size" class="form-control" style="width: 100%; height: 32px; padding: 4px 8px; font-size: 13px;" min="0" max="20" placeholder="0">
							</div>
							<div class="integrated-config-item" style="flex-direction: column; align-items: flex-start; gap: 4px;">
								<label style="font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 2px;">Border Color</label>
								<input type="color" name="image_border_color" class="form-control" style="width: 100%; height: 32px; padding: 2px; border: 1px solid #d1d5db;">
							</div>
							<div class="integrated-config-item" style="flex-direction: column; align-items: flex-start; gap: 4px;">
								<label style="font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 2px;">Image Top (px)</label>
								<input type="number" name="image_margin_top" class="form-control" style="width: 100%; height: 32px; padding: 4px 8px; font-size: 13px;" min="0" max="300" placeholder="75">
							</div>
							<div class="integrated-config-item" style="flex-direction: column; align-items: flex-start; gap: 4px;">
								<label style="font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 2px;">Line Height</label>
								<input type="number" name="line_height" class="form-control" style="width: 100%; height: 32px; padding: 4px 8px; font-size: 13px;" min="0.5" max="3.0" step="0.1" placeholder="1">
							</div>
							<div class="integrated-config-item" style="flex-direction: column; align-items: flex-start; gap: 4px;">
								<label style="font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 2px;">Content Top (px)</label>
								<input type="number" name="content_margin_top" class="form-control" style="width: 100%; height: 32px; padding: 4px 8px; font-size: 13px;" min="0" max="500" placeholder="165">
							</div>
						</div>
					</div>

					<!-- Field Visibility Section -->
					<div id="fieldVisibilitySection">
						<h5 style="margin-bottom: 12px; font-weight: 700; color: #374151; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px;">
							<i class="fa fa-eye" style="margin-right: 8px; color: #3b82f6;"></i> Field Visibility
						</h5>
						<div id="fvGridWrapper" class="integrated-config-grid">
							<label class="integrated-config-item"><input type="checkbox" name="show_logo"> Logo</label>
							<label class="integrated-config-item"><input type="checkbox" name="show_inst_name"> Inst. Name</label>
							<label class="integrated-config-item"><input type="checkbox" name="show_image"> Image</label>
							<label class="integrated-config-item"><input type="checkbox" name="show_name"> Name</label>
							<label class="integrated-config-item"><input type="checkbox" name="show_id"> <span class="label-id">ID No</span></label>
							<label class="integrated-config-item"><input type="checkbox" name="show_mpo_index"> MPO Index</label>
							<label class="integrated-config-item"><input type="checkbox" name="show_class_section"> Class/Section</label>
							<label class="integrated-config-item"><input type="checkbox" name="show_class"> <span class="label-class">Class</span></label>
							<label class="integrated-config-item label-section-wrapper"><input type="checkbox" name="show_section"> Section</label>
							<label class="integrated-config-item label-group-wrapper"><input type="checkbox" name="show_group"> Group</label>
							<label class="integrated-config-item"><input type="checkbox" name="show_roll"> <span class="label-roll">Roll</span></label>
							<label class="integrated-config-item"><input type="checkbox" name="show_year"> <span class="label-year">Year</span></label>
							<label class="integrated-config-item"><input type="checkbox" name="show_dob"> <span class="label-dob">DOB</span></label>
							<label class="integrated-config-item"><input type="checkbox" name="show_blood"> Blood</label>
							<label class="integrated-config-item"><input type="checkbox" name="show_phone"> <span class="label-phone">Phone</span></label>
							<label class="integrated-config-item"><input type="checkbox" name="show_father"> Father</label>
							<label class="integrated-config-item"><input type="checkbox" name="show_mother"> Mother</label>
							<label class="integrated-config-item"><input type="checkbox" name="show_address"> Address</label>
							<label class="integrated-config-item"><input type="checkbox" name="show_inst_address"> Inst. Address</label>
							<label class="integrated-config-item"><input type="checkbox" name="show_inst_phone"> Inst. Phone</label>
							<label class="integrated-config-item"><input type="checkbox" name="show_signature"> Signature</label>
						</div>
					</div>
				</form>
			</div>

			<!-- Right Pane: Live Preview & Background -->
			<div class="modal-integrated-right">
				<div class="preview-container" id="previewContainer">					
					<!-- Live Preview Card -->
					<div id="liveCardPreview" class="card-parent preview-actual-size">
						<div id="previewInnerBox" style="padding: 6px; text-align: center; margin: 7px 7px; height: 93%; margin-top:10px; position:relative; overflow: hidden;">
							
							<div style="margin-bottom: 5px;">
								<img id="prev_show_logo" width="35" src="<?= $s3sRedux['instLogo']['url'] ?>" style="border-radius: 25px">
								<h3 id="prev_show_inst_name" style="margin: 0; font-size: 14px; color: blue; font-weight: 700; letter-spacing: -0.5px;"><?= $s3sRedux['institute_name'] ?></h3>
							</div>

							<div style="padding: 4px; text-align: center;">
								<div id="prev_show_image" class="text-center" style="position: absolute; top: 75px; width: 100%; left: 0; z-index: 2;">
									<img width="85" height="85" style="border-radius: 20px; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1);" src="<?= get_template_directory_uri() ?>/img/No_Image.jpg">
								</div>
								<p id="prev_show_name" class="std-name" style="font-size: 12px; position: absolute; top: <?= $config['content_margin_top'] ?? 165 ?>px; width: 100%; left: 0; margin: 0 !important; z-index: 2;">SAMPLE STUDENT NAME</p>
							</div>

							<table class="card-table" style="position: absolute; top: <?= ($config['content_margin_top'] ?? 165) + 30 ?>px; width: 90%; font-size: 10px; left: 5%; margin: 0 !important; z-index: 1; line-height: <?= $config['line_height'] ?? 1 ?>;">
								<tr id="prev_show_id" class="info-row">
									<td class="info-label label-id">ID No</td>
									<td class="info-value"><span class="colon">: </span><span class="value-text">20240001</span></td>
								</tr>
								<tr id="prev_show_father" class="info-row">
									<td class="info-label">Father</td>
									<td class="info-value"><span class="colon">:</span><span class="value-text">MR. SAMPLE FATHER</span></td>
								</tr>
								<tr id="prev_show_mother" class="info-row">
									<td class="info-label">Mother</td>
									<td class="info-value"><span class="colon">:</span><span class="value-text">MRS. SAMPLE MOTHER</span></td>
								</tr>
								<tr id="prev_show_class_section" class="info-row">
									<td class="info-label label-class">Class</td>
									<td class="info-value"><span class="colon">:</span><span id="prev_show_class_section_val" class="value-text">NINE (A)</span></td>
								</tr>
								<tr id="prev_show_class_only" class="info-row">
									<td class="info-label label-class">Class</td>
									<td class="info-value"><span class="colon">:</span><span id="prev_show_class_only_val" class="value-text">NINE</span></td>
								</tr>
								<tr id="prev_show_section_only" class="info-row">
									<td class="info-label label-class">Section</td>
									<td class="info-value"><span class="colon">:</span><span id="prev_show_section_only_val" class="value-text">A</span></td>
								</tr>
								<tr id="prev_show_group" class="info-row">
									<td class="info-label label-group">Group</td>
									<td class="info-value"><span class="colon">:</span><span class="value-text">SCIENCE</span></td>
								</tr>
								<tr id="prev_show_roll" class="info-row">
									<td class="info-label label-roll">Roll</td>
									<td class="info-value"><span class="colon">:</span><span class="value-text">10</span></td>
								</tr>
								<tr id="prev_show_dob" class="info-row">
									<td class="info-label label-dob">DOB</td>
									<td class="info-value"><span class="colon">:</span><span class="value-text">01-01-2010</span></td>
								</tr>
								<tr id="prev_show_year" class="info-row">
									<td class="info-label label-year">Year</td>
									<td class="info-value"><span class="colon">:</span><span class="value-text">2024-2025</span></td>
								</tr>
								<tr id="prev_show_blood" class="info-row">
									<td class="info-label">Blood</td>
									<td class="info-value"><span class="colon">:</span><span class="value-text">A+</span></td>
								</tr>
								<tr id="prev_show_phone" class="info-row">
									<td class="info-label">Phone</td>
									<td class="info-value"><span class="colon">:</span><span class="value-text">01700000000</span></td>
								</tr>
								<tr id="prev_show_address" class="info-row">
									<td class="info-label">Address</td>
									<td class="info-value"><span class="colon">:</span><span class="value-text">HOUSE #123, ROAD #5, DHAKA, BANGLADESH</span></td>
								</tr>
							</table>
							<div id="prev_show_signature" style="position: absolute; bottom: 5px; right: 7px; text-align: center; z-index: 3;">
								<img src="<?= $s3sRedux['principalSign']['url'] ?>" style="height: auto; max-width: 50px; margin: 0 auto; display: block;">
								<span style="font-size: 10px; color: blue; border-top: 1px solid blue; margin-top: 2px; display: inline-block; padding-top: 2px; min-width: 80px;"><?= $s3sRedux['inst_head_title'] ?></span>
							</div>
						</div>

						<div id="previewTeacherPortraitBox" style="display:none; text-align: center; margin: 7px 7px; height: 93%; margin-top: 10px; position: relative;">
							<div style="margin-bottom: 5px;">
								<img id="prev_teacher_show_logo" width="35" src="<?= $s3sRedux['instLogo']['url'] ?>" style="border-radius: 25px">
								<h3 id="prev_teacher_show_inst_name" style="margin: 0; font-size: 14px; color: blue; font-weight: 700; letter-spacing: -0.5px;"><?= $s3sRedux['institute_name'] ?></h3>
							</div>

							<div style="padding: 4px; text-align: center;">
								<div id="prev_teacher_show_image" class="text-center" style="position: absolute; top: 75px; width: 100%; left: 0; z-index: 2;">
									<img width="85" height="85" style="border-radius: 20px; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.1);" src="<?= get_template_directory_uri() ?>/img/No_Image.jpg">
								</div>
								<p id="prev_teacher_show_name" class="std-name" style="font-size: 13px; position: absolute; top: <?= $config['content_margin_top'] ?? 165 ?>px; width: 100%; left: 0; margin: 0 !important; z-index: 2;">SAMPLE TEACHER</p>
								<p id="prev_teacher_show_designation" class="teacher-designation" style="top: <?= ($config['content_margin_top'] ?? 165) + 20 ?>px;">SENIOR TEACHER</p>
							</div>

							<?php $teacher_table_top = ($config['content_margin_top'] ?? 165) + (!empty($config['show_class']) ? 46 : 30); ?>
							<table class="card-table" style="position: absolute; top: <?= $teacher_table_top ?>px; width: 90%; left: 5%; margin: 0 !important; z-index: 1; font-size: 10px; line-height: <?= $config['line_height'] ?? 1 ?>;">
								<tr id="prev_teacher_show_id" class="info-row">
									<td class="info-label">ID</td>
									<td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text">2610100</span></td>
								</tr>
								<tr id="prev_teacher_show_mpo_index" class="info-row">
									<td class="info-label">MPO INDEX</td>
									<td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text">2610100</span></td>
								</tr>
								<tr id="prev_teacher_show_roll" class="info-row">
									<td class="info-label">EDUCATION</td>
									<td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text">M.A. IN ENGLISH</span></td>
								</tr>
								<tr id="prev_teacher_show_father" class="info-row">
									<td class="info-label">FATHER</td>
									<td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text">MR. SAMPLE FATHER</span></td>
								</tr>
								<tr id="prev_teacher_show_dob" class="info-row">
									<td class="info-label">DOB</td>
									<td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text">01-01-1980</span></td>
								</tr>
								<tr id="prev_teacher_show_year" class="info-row">
									<td class="info-label">JOINING</td>
									<td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text">01-01-2020</span></td>
								</tr>
								<tr id="prev_teacher_show_blood" class="info-row">
									<td class="info-label">BLOOD</td>
									<td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text">A+</span></td>
								</tr>
								<tr id="prev_teacher_show_phone" class="info-row">
									<td class="info-label">PHONE</td>
									<td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text">01700000000</span></td>
								</tr>
							</table>
							<div id="prev_teacher_show_signature" style="position: absolute; bottom: 5px; right: 7px; text-align: center; z-index: 3;">
								<img src="<?= $s3sRedux['principalSign']['url'] ?>" style="height: auto; max-width: 50px; margin: 0 auto; display: block;">
								<span style="font-size: 10px; color: blue; border-top: 1px solid blue; margin-top: 2px; display: inline-block; padding-top: 2px; min-width: 80px;"> <?= $s3sRedux['inst_head_title'] ?></span>
							</div>
						</div>

						<div id="previewLandscapeBox" style="display:none; text-align: left; margin: 0; height: 100%; position: relative;">
							<div class="id-card-content">
								<img id="prev_landscape_logo" width="54" src="<?= $s3sRedux['instLogo']['url'] ?>" style="float:left; margin-top:2px; margin-left:7px; height:54px; border-radius: 5px;">
								
								<span id="prev_landscape_inst_name" style="font-size: 17px; color:#1a7000f2; margin-top:2px; display: block; text-align: center;"><b><?= $s3sRedux['institute_name'] ?></b></span>
								
								<div class="landscape-flex-container" style="display: flex; align-items: flex-start; margin-top: <?= $config['content_margin_top'] ?? 10 ?>px;">
									<?php 
										$img_shape = ($config['image_shape'] ?? 'square');
										$border_size = intval($config['image_border_size'] ?? 0);
										$border_color = htmlspecialchars($config['image_border_color'] ?? '#000000');
										$img_style = "margin-top: " . intval($config['image_margin_top'] ?? 5) . "px !important; object-fit: cover;";
										if ($img_shape === 'rounded') {
											$img_style .= " border-radius: 50%;";
										} else {
											$img_style .= " border-radius: 5px;";
										}
										if ($border_size > 0) {
											$img_style .= " border: {$border_size}px solid {$border_color} !important;";
										}
									?>
									<img id="prev_landscape_image" class="student-image" alt="Student_Image" src="<?= get_template_directory_uri() ?>/img/No_Image.jpg" style="<?= $img_style ?>">
									
									<div style="text-align: left; padding-left: 15px; flex: 1;">
										<p id="prev_landscape_teacher_designation" style="display:none; font-size: 11px; font-weight: bold; color: black; text-transform: uppercase; margin-bottom: 5px;">SENIOR TEACHER</p>
										<div style="font-size: 12px; line-height: 1.4; color: #333;">
											<div id="prev_landscape_name" class="info-row"><b>Name:</b> <span>SAMPLE STUDENT NAME</span></div>
											<div id="prev_landscape_father" class="info-row"><b>Father:</b> <span>MR. SAMPLE FATHER</span></div>
											<div id="prev_landscape_mother" class="info-row"><b>Mother:</b> <span>MRS. SAMPLE MOTHER</span></div>
											<div id="prev_landscape_id" class="info-row"><b><span class="label-id">ID No</span>:</b> <span>20240001</span></div>
											<div id="prev_landscape_mpo_index" class="info-row" style="display:none;"><b>MPO:</b> <span>2610100</span></div>
											<div id="prev_landscape_roll" class="info-row"><b><span class="label-roll">Roll</span>:</b> <span>10</span></div>
											<div id="prev_landscape_class_section" class="info-row"><b><span class="label-class">Class</span>:</b> <span id="prev_landscape_class_section_val">NINE (A)</span></div>
											<div id="prev_landscape_class_only" class="info-row"><b><span class="label-class">Class</span>:</b> <span id="prev_landscape_class_only_val">NINE</span></div>
											<div id="prev_landscape_section_only" class="info-row"><b>Section:</b> <span id="prev_landscape_section_only_val">A</span></div>
											<div id="prev_landscape_group" class="info-row"><b><span class="label-group">Group</span>:</b> <span>SCIENCE</span></div>
											<div id="prev_landscape_phone" class="info-row"><b>Phone:</b> <span>01700000000</span></div>
										</div>
									</div>
								</div>
							</div>
						</div>

						<style>
							svg {
								/* width: 100px; */
								height: 50px;
							}
						</style>

						<div id="previewBacksideBox" style="display:none; padding: 6px; text-align: center; margin: 7px 7px; height: 93%; margin-top:10px; position:relative; overflow: hidden;">
							<div id="prev_back_header" style="margin-bottom: 5px;">
								<img id="prev_back_logo" width="35" src="<?= $s3sRedux['instLogo']['url'] ?>" style="border-radius: 25px">
								<p style="margin: 0; font-size: 12px; color: blue; font-weight: 700;">If found please return to</p>
							</div>
							<div id="prev_back_info" style="padding: 5px; text-align: center;">
								<h3 id="prev_back_inst_name" style="margin: 0; font-size: 14px; color: blue; font-weight: 700;"><?= $s3sRedux['institute_name'] ?></h3>
								<p id="prev_back_inst_address" style="font-size: 10px; margin-top: 2px !important;"><?= $s3sRedux['institute_address'] ?></p>
								<p id="prev_back_inst_phone" style="font-size: 10px; margin-top: 2px !important;">Phone: <?= $s3sRedux['institute_phone'] ?></p>
							</div>
							<table id="prev_back_student_info" class="card-table" style="position: absolute; top: 150px; width: 90%; font-size: 9px; left: 15%; margin: 0 !important;">
								<tr id="prev_back_name">
									<td colspan="2" style="padding-bottom:3px; font-weight:bold; color:blue; text-align: center;">SAMPLE STUDENT NAME</td>
								</tr>
								<tr id="prev_back_id" class="info-row">
									<td class="info-label label-id">ID No</td>
									<td class="info-value"><span class="colon">: </span><span class="value-text">20240001</span></td>
								</tr>
								<tr id="prev_back_father" class="info-row">
									<td class="info-label">Father</td>
									<td class="info-value"><span class="colon">:</span><span class="value-text">MR. SAMPLE FATHER</span></td>
								</tr>
								<tr id="prev_back_mother" class="info-row">
									<td class="info-label">Mother</td>
									<td class="info-value"><span class="colon">:</span><span class="value-text">MRS. SAMPLE MOTHER</span></td>
								</tr>
								<tr id="prev_back_class_section" class="info-row">
									<td class="info-label label-class">Class</td>
									<td class="info-value"><span class="colon">:</span><span id="prev_back_class_section_val" class="value-text">NINE (A)</span></td>
								</tr>
								<tr id="prev_back_class_only" class="info-row">
									<td class="info-label label-class">Class</td>
									<td class="info-value"><span class="colon">:</span><span id="prev_back_class_only_val" class="value-text">NINE</span></td>
								</tr>
								<tr id="prev_back_section_only" class="info-row">
									<td class="info-label">Section</td>
									<td class="info-value"><span class="colon">:</span><span id="prev_back_section_only_val" class="value-text">A</span></td>
								</tr>
								<tr id="prev_back_group" class="info-row">
									<td class="info-label label-group">Group</td>
									<td class="info-value"><span class="colon">:</span><span class="value-text">SCIENCE</span></td>
								</tr>
								<tr id="prev_back_roll" class="info-row">
									<td class="info-label label-roll">Roll</td>
									<td class="info-value"><span class="colon">:</span><span class="value-text">10</span></td>
								</tr>
								<tr id="prev_back_year" class="info-row">
									<td class="info-label label-year">Year</td>
									<td class="info-value"><span class="colon">:</span><span class="value-text">2024-2025</span></td>
								</tr>
								<tr id="prev_back_blood" class="info-row">
									<td class="info-label">Blood</td>
									<td class="info-value"><span class="colon">:</span><span class="value-text">A+</span></td>
								</tr>
								<tr id="prev_back_dob" class="info-row">
									<td class="info-label label-dob">DOB</td>
									<td class="info-value"><span class="colon">:</span><span class="value-text">01-01-2010</span></td>
								</tr>
								<tr id="prev_back_phone" class="info-row">
									<td class="info-label">Phone</td>
									<td class="info-value"><span class="colon">:</span><span class="value-text">01700000000</span></td>
								</tr>
								<tr id="prev_back_address" class="info-row">
									<td class="info-label">Address</td>
									<td class="info-value"><span class="colon">:</span><span class="value-text">HOUSE #123, DHAKA</span></td>
								</tr>
							</table>
							<div id="prev_back_signature" style="position: absolute; bottom: 5px; right: 7px; text-align: center;">
								<img src="<?= $s3sRedux['principalSign']['url'] ?>" style="height: auto; max-width: 50px; margin: 0 auto; display: block;">
								<span style="font-size: 10px; color: blue; border-top: 1px solid blue; margin-top: 2px; display: inline-block; padding-top: 2px; min-width: 80px;"><?= $s3sRedux['inst_head_title'] ?></span>
							</div>
						</div>
					</div>

					<!-- Upload Controls -->
					<div style="margin-top: 25px; display: flex; flex-direction: column; align-items: center; gap: 12px; width: 100%;">
						<div style="display: flex; gap: 8px; width: 100%;">
							<button class="btn btn-info" style="flex: 1; border-radius: 8px; font-weight: 600;" onclick="document.getElementById('bgIntegratedInput').click()">
								<i class="fa fa-image" style="margin-right: 8px;"></i> Upload
							</button>
							<button class="btn btn-danger" style="flex: 1; border-radius: 8px; font-weight: 600;" onclick="removeBgIntegrated()">
								<i class="fa fa-trash" style="margin-right: 8px;"></i> Remove
							</button>
						</div>
						<input type="file" id="bgIntegratedInput" style="display: none;" accept="image/*" onchange="previewAndUploadBgIntegrated(this)">
						<div id="bgIntegratedStatus" class="upload-status-badge status-idle">Ready to upload</div>
					</div>
				</div>
			</div>
		</div>

		<div class="modal-integrated-footer">
			<span id="configStatus" style="margin-right: 20px; font-weight: 600;"></span>
			<button type="button" class="btn btn-primary" onclick="saveDesignConfig()">Save Settings</button>
		</div>
	</div>
</div>

<?php if (! is_admin()) {
	get_header(); ?>
	<div class="b-layer-main">

		<div class="">
			<div class="container">
				<div class="row">
					<div class="col-md-12">
					<?php } ?>

					<div class="container-fluid maxAdminpages" style="padding-left: 0">
						<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
							<h2>ID Card Management</h2>
							<div>
								<button class="btn btn-warning" style="margin-bottom:10px;" onclick="openDesignConfigModal()">Configure Designs & Backgrounds</button>
								<!-- <button class="btn btn-info" style="margin-bottom:10px;" onclick="openSamplesModal()">View Design Samples</button> -->
								<a href="<?= home_url("/teacher-id-card") ?>" class="btn btn-primary" style="margin-bottom:10px;">Teacher ID Card</a>
							</div>
						</div>
								
						<div class="row">
							
								<div class="col-md-12">
									<div class="panel panel-info">
										<div class="panel-heading">
											<h3>Student ID Card<br><small>Print Student Id Card</small></h3>
										</div>
										<div class="panel-body" style="padding: 20px;padding-bottom:10px;">
											<form class="form-inline" action="" method="GET" style="display: flex; flex-wrap: wrap; align-items: flex-end; gap: 20px;">
												<input type="hidden" name="page" value="idcard">
												<div class="form-group" style="margin: 0;">
													<label style="display: block; font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.5px;">Class</label>
													<select id='resultClass' class="form-control" name="class" style="height: 38px; border-radius: 6px; border: 1px solid #d1d5db; min-width: 140px;">
														<?php
														$classQuery = $wpdb->get_results("SELECT classid,className FROM ct_class WHERE classid IN (SELECT infoClass FROM ct_studentinfo GROUP BY infoClass ORDER BY className ASC)");
														echo "<option value=''>Select Class</option>";
														foreach ($classQuery as $class) {
															echo "<option value='" . $class->classid . "'>" . $class->className . "</option>";
														}
														?>
													</select>
												</div>

												<div class="form-group" style="margin: 0;">
													<label style="display: block; font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.5px;">Section</label>
													<select id="resultSection" class="form-control" name="section" disabled style="height: 38px; border-radius: 6px; border: 1px solid #d1d5db; min-width: 140px;">
														<option disabled selected>Select Class First</option>
													</select>
												</div>

												<div class="form-group" style="margin: 0;">
													<label style="display: block; font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.5px;">Year</label>
													<select id='resultYear' class="form-control" name="syear" disabled style="height: 38px; border-radius: 6px; border: 1px solid #d1d5db; min-width: 120px;">
														<option disabled selected>Select Class First</option>
													</select>
												</div>

												<div class="form-group" style="margin: 0;">
													<label style="display: block; font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; margin-bottom: 4px; letter-spacing: 0.5px;">Design Template</label>
													<div style="display: flex; align-items: center; gap: 8px;">
														<select name="design" class="form-control" style="min-width: 160px; height: 38px; border-radius: 6px; border: 1px solid #d1d5db;">
															<?php 
															$avail_designs = getAvailableDesignNumbers();
															$designs_data = [];
															foreach ($avail_designs as $no) {
																$bg_img = getIdDesignImageUrl($no);
																if (!$bg_img) {
																	$bg_img = get_template_directory_uri() . '/img/No_Image.jpg';
																}
																$designs_data[$no] = $bg_img;
																$isSelected = ($selected == $no) ? 'selected' : '';
																$type = ucfirst(getIdDesignType($no));
																echo "<option value='$no' data-preview='$bg_img' $isSelected>" . getIdDesignName($no) . " ($type)</option>";
															}
															?>
														</select>
													</div>
												</div>

												<div class="form-group" style="margin: 0;">
													<button type="submit" name="creatId" class="btn btn-primary" style="height: 38px; padding: 0 25px; border-radius: 6px; font-weight: 600; background-color: #2563eb; border-color: #2563eb;">
														Generate
													</button>
												</div>
												<br>
												<div class="form-group" style="margin: 0;width:100%" id="idRoll">
													<label style="display: block; font-size: 11px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px;">Or Roll / ID No</label>
													<input class="form-control" type="text" name="roll" placeholder="e.g. 1,2,5" style="height: 38px; border-radius: 6px; border: 1px solid #d1d5db; min-width: 600px;">
												</div>
											</form>
										</div>
									</div>
								</div>
								
								<?php if (wp_get_current_user()->user_login == 'admin') { ?>
									<div class="col-md-12">
										<button onclick="printIdCard('printArea')" class="pull-right btn btn-primary">Print</button>
									</div>
								<?php } ?>
							<?php

							$hasClassFilter = isset($_GET['class']) && $_GET['class'] !== '';
							$hasYearFilter = isset($_GET['syear']) && $_GET['syear'] !== '';
							$hasSectionFilter = isset($_GET['section']) && $_GET['section'] !== '' && $_GET['section'] !== '0';
							$hasRollFilter = isset($_GET['roll']) && trim((string) $_GET['roll']) !== '';
							$hasAnyFilter = $hasClassFilter || $hasYearFilter || $hasSectionFilter || $hasRollFilter;

							if (isset($_GET['creatId']) && $hasAnyFilter) { 
								ob_start();
							?>
								<div id="printArea" class="col-md-12 printBG">
									<div class="printArea" style="text-align: center;">
										<style type="text/css">
											@page {
												size: auto !important;
												margin: 0px !important;
											}

											* {
												box-sizing: border-box !important;
												-webkit-print-color-adjust: exact !important;
												print-color-adjust: exact !important;
											}

											.printArea {
												width: 100% !important;
												padding: 0 !important;
												margin: 0 !important;
												text-align: center;
											}

											.card-parent {
												box-shadow: none !important;
												border: none !important;
												display: inline-block !important;
												vertical-align: top !important;
											}

											/* Normalize text rendering for print */
											h3, p {
												line-height: 1.3 !important;
												margin: 0 !important;
												padding: 0 !important;
											}

											table {
												border-collapse: collapse !important;
											}

											/* Landscape Specific Styles */
											.landscape-mode {
												width: 8.7cm !important;
												height: 5.3cm !important;
												background-color: #ffffff !important;
												font-family: Arial, sans-serif !important;
												font-size: 12px !important;
												border: 1px solid black !important;
											}

											.landscape-mode .student-image {
												border: 1px solid black !important;
												margin-left: 10px !important;
												float: left !important;
													height: var(--student-image-height, 70px) !important;
													width: var(--student-image-width, 65px) !important;
												object-fit: cover !important;
											}

											.landscape-mode .info-row {
												display: flex !important;
												margin-bottom: 2px !important;
											}

											.landscape-mode .info-row b {
												min-width: 45px !important;
												display: flex;
											}

											svg {
												width: 200px;
												height: 60px;
											}
										</style>
										<?php

										$year 		= isset($_GET['syear']) ? sanitize_text_field(wp_unslash($_GET['syear'])) : '';
										$class 		= isset($_GET['class']) ? intval($_GET['class']) : 0;
										$section 	= isset($_GET['section']) ? intval($_GET['section']) : 0;
										$rollInput 	= isset($_GET['roll']) ? sanitize_text_field(wp_unslash($_GET['roll'])) : '';
										
										$rollValues = [];
										if (!empty($rollInput)) {
											$parts = explode(',', $rollInput);
											foreach ($parts as $part) {
												$part = trim($part);
												if ($part === '') continue;
												if (strpos($part, '-') !== false) {
													list($start, $end) = array_map('trim', explode('-', $part));
													if (ctype_digit($start) && ctype_digit($end)) {
														$start = intval($start);
														$end = intval($end);
														if ($start <= $end) {
															for ($i = $start; $i <= $end; $i++) {
																$rollValues[] = $i;
															}
														}
													}
												} elseif (ctype_digit($part)) {
													$rollValues[] = intval($part);
												}
											}
										}
										$rollValues = array_unique($rollValues);
										$person_type = 'student';

										if ($hasAnyFilter) {
											$query = "SELECT studentid,uid,stdName,infoRoll,stdPresent,className,sectionName,groupName,stdImg,stdBrith,infoYear,stdPhone,stdFather,stdMother,stdBldGrp,stdAdmitYear FROM ct_student
								LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid AND ct_student.stdCurrentClass = ct_studentinfo.infoClass
								LEFT JOIN ct_class ON ct_studentinfo.infoClass = ct_class.classid
								LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId
								LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
													WHERE 1=1";

											if ($year !== '') {
												$query .= $wpdb->prepare(" AND infoYear = %s", $year);
											}
											if ($class > 0) {
												$query .= " AND infoClass = {$class}";
											}
											if ($section > 0) {
												$query .= " AND infoSection = $section";
											}
											if (!empty($rollValues)) {
												$query .= " AND infoRoll IN (" . implode(',', $rollValues) . ")";
											}
											$query .= " ORDER BY infoRoll ASC";
											$groupsBy = $wpdb->get_results($query);
										}


										if ($groupsBy) {

											foreach ($groupsBy as $value) {
    $design = isset($_GET['design']) ? intval($_GET['design']) : 1;
    $config = getIdDesignConfig($design);
    $bg_url = getIdDesignImageUrl($design);
    
    // No fallback defaults
    if (!$bg_url) {
        $bg_url = ''; 
    }

    $orientation = $config['orientation'] ?? 'portrait';
	$student_image_size = intval($config['student_image_size'] ?? 85);
	if ($student_image_size <= 0) $student_image_size = 85;
	$student_name_color = htmlspecialchars($config['student_name_color'] ?? '#065499');
	$img_h = $student_image_size;
	$img_w = ($orientation === 'landscape') ? max(1, (int) round($student_image_size * 65 / 70)) : $student_image_size;

    if ($orientation === 'landscape') {
        $card_width = "8.7cm";
        $card_height = "5.3cm";
        $inner_margin = "0px";
    } else {
        $card_width = "5.3cm";
        $card_height = "8.7cm";
        $inner_margin = "5px";
    }
?>
	<div class="card-parent <?= $orientation ?>-mode" style="--student-name-color: <?= $student_name_color ?>; --student-image-height: <?= $img_h ?>px; --student-image-width: <?= $img_w ?>px; --line-height: <?= floatval($config['line_height'] ?? 1) ?>; background-image: url('<?= $bg_url ?>'); width: <?= $card_width ?>; height: <?= $card_height ?>; background-size: cover; background-position: center; position: relative; display: inline-block; margin: <?= $inner_margin ?>; overflow: hidden; <?php if($orientation === 'landscape') echo 'border:1px solid #ddd; background-color:#fff;'; ?>">
        <?php if ($config['design_type'] === 'back'): ?>
            <!-- Back Side Layout -->
            <div style="padding: 6px; text-align: center; margin: 7px 7px; height: 93%; margin-top:10px; position:relative; overflow: hidden;">
                <div style="margin-bottom: 5px;">
                    <?php if (!empty($config['show_logo'])): ?>
                        <img width="35" src="<?= $s3sRedux['instLogo']['url'] ?>" style="border-radius: 25px">
                    <?php endif; ?>
                    <p style="margin: 0; font-size: 12px; color: blue; font-weight: 700;">If found please return to</p>
                </div>
                <div style="padding: 5px; text-align: center;">
                    <?php if (!empty($config['show_inst_name'])): ?>
                        <h3 style="margin: 0; font-size: 14px; color: blue; font-weight: 700;"><?= $s3sRedux['institute_name'] ?></h3>
                    <?php endif; ?>
                    <?php if(!empty($config['show_inst_address']) && !empty($s3sRedux['institute_address'])): ?>
                        <p style="font-size: 10px; margin-top: 2px !important;"><?= $s3sRedux['institute_address'] ?></p>
                    <?php endif; ?>
                    <?php if(!empty($config['show_inst_phone']) && !empty($s3sRedux['institute_phone'])): ?>
                        <p style="font-size: 10px; margin-top: 2px !important;">Phone: <?= $s3sRedux['institute_phone'] ?></p>
                    <?php endif; ?>
                </div>
                <table class="card-table" style="position: absolute; top: 150px; width: 90%; font-size: 9px; left: 15%; margin: 0 !important;">
                    <?php if (!empty($config['show_name'])): ?>
                        <tr><td colspan="2" style="padding-bottom:3px; font-weight:bold; color:blue; text-align: center;"><?= $value->stdName ?></td></tr>
                    <?php endif; ?>
                    
                    <?php if (!empty($config['show_id'])): ?>
                    <tr class="info-row">
                        <td class="info-label">ID</td>
                        <td class="info-value"><span class="colon">: </span><span class="value-text"><?= ($s3sRedux['stdidpref'] == 'year') ? $value->stdAdmitYear : $s3sRedux['stdidpref']; ?><?= sprintf("%05s", ($value->studentid + $s3sRedux['stdid'])) ?></span></td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if (!empty($config['show_father'])): ?>
                    <tr class="info-row">
                        <td class="info-label">Father</td>
                        <td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text"><?= htmlspecialchars($value->stdFather) ?></span></td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if (!empty($config['show_mother'])): ?>
                    <tr class="info-row">
                        <td class="info-label">Mother</td>
                        <td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text"><?= htmlspecialchars($value->stdMother) ?></span></td>
                    </tr>
                    <?php endif; ?>
                    
					<?php if (!empty($config['show_class_section'])): ?>
                    <tr class="info-row">
                        <td class="info-label">Class</td>
                        <td class="info-value">
                            <span class="colon">:&nbsp;</span>
                            <span class="value-text"><?= htmlspecialchars($value->className) ?> <?php echo '('.htmlspecialchars($value->sectionName).')'; ?></span>
                        </td>
                    </tr>
                    <?php endif; ?>

					<?php if (!empty($config['show_class'])): ?>
                    <tr class="info-row">
                        <td class="info-label">Class</td>
                        <td class="info-value">
                            <span class="colon">:&nbsp;</span>
                            <span class="value-text"><?= htmlspecialchars($value->className) ?></span>
                        </td>
                    </tr>
                    <?php endif; ?>

					<?php if (!empty($config['show_section'])): ?>
                    <tr class="info-row">
                        <td class="info-label">Section</td>
                        <td class="info-value">
                            <span class="colon">:&nbsp;</span>
                            <span class="value-text"><?= htmlspecialchars($value->sectionName) ?></span>
                        </td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if (!empty($config['show_group'])): ?>
                    <tr class="info-row">
                        <td class="info-label">Group</td>
                        <td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text"><?= htmlspecialchars($value->groupName) ?></span></td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if (!empty($config['show_roll'])): ?>
                    <tr class="info-row">
                        <td class="info-label">Roll</td>
                        <td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text"><?= htmlspecialchars($value->infoRoll) ?></span></td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if (!empty($config['show_year'])): ?>
                    <tr class="info-row">
                        <td class="info-label">Year</td>
                        <td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text"><?= htmlspecialchars($value->infoYear) ?></span></td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if (!empty($config['show_blood'])): ?>
                    <tr class="info-row">
                        <td class="info-label">Blood</td>
                        <td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text"><?= htmlspecialchars($value->stdBldGrp) ?></span></td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if (!empty($config['show_dob'])): ?>
                    <tr class="info-row">
                        <td class="info-label">DOB</td>
                        <td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text"><?= date('d-m-Y', strtotime($value->stdBrith)) ?></span></td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if (!empty($config['show_phone'])): ?>
                    <tr class="info-row">
                        <td class="info-label">Phone</td>
                        <td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text"><?= htmlspecialchars($value->stdPhone) ?></span></td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if (!empty($config['show_address'])): ?>
                    <tr class="info-row">
                        <td class="info-label">Address</td>
                        <td class="info-value">
                            <span class="colon">:&nbsp;</span>
                            <span class="value-text">
                            <?php
                            $address = $value->stdPresent;
                            $addressParts = explode(',', $address);
                            echo isset($addressParts[0]) ? trim($addressParts[0]) : '';
                            if (isset($addressParts[1])) echo ', ' . trim($addressParts[1]);
                            ?>
                            </span>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
                <?php if (!empty($config['show_signature'])): ?>
                <div style="position: absolute; bottom: 5px; right: 7px; text-align: center;">
                    <?php if(!empty($s3sRedux['principalSign']['url'])): ?>
                        <img src="<?= $s3sRedux['principalSign']['url'] ?>" style="height: auto; max-width: 50px; margin: 0 auto; display: block;">
                    <?php endif; ?>
                    <span style="font-size: 10px; color: blue; border-top: 1px solid blue; margin-top: 2px; display: inline-block; padding-top: 2px; min-width: 80px;"><?= $s3sRedux['inst_head_title'] ?></span>
                </div>
                <?php endif; ?>
            </div>
        <?php elseif ($orientation === 'landscape'): ?>
            <!-- Landscape Layout -->
            <div class="id-card-content">
                <?php if (!empty($config['show_logo'])): ?>
                    <img width="54" src="<?= $s3sRedux['instLogo']['url'] ?>" style="float:left; margin-top:2px; margin-left:7px; height:54px; border-radius: 5px;">
                <?php endif; ?>
                
                <?php if (!empty($config['show_inst_name'])): ?>
                    <span style="font-size: 17px; color:#1a7000f2; margin-top:2px; display: block; text-align: center;"><b><?= $s3sRedux['institute_name'] ?></b></span>
                <?php endif; ?>

				<div class="landscape-flex-container" style="display: flex; align-items: flex-start; margin-top: <?= intval($config['content_margin_top'] ?? 10) ?>px;">
				<?php if (!empty($config['show_image'])): ?>
					<?php 
							$img_shape = ($config['image_shape'] ?? 'square');
							$border_size = intval($config['image_border_size'] ?? 0);
							$border_color = htmlspecialchars($config['image_border_color'] ?? '#000000');
							$img_style = "margin-top: " . intval($config['image_margin_top'] ?? 5) . "px !important; object-fit: cover; margin-left: 20px !important; float: left !important; height: var(--student-image-height) !important; width: var(--student-image-height) !important;";
							if ($img_shape === 'rounded') {
								$img_style .= " border-radius: 50%;";
							} else {
								$img_style .= " border-radius: 5px;";
							}
							if ($border_size > 0) {
								$img_style .= " border: {$border_size}px solid {$border_color} !important;";
								}
								?>
						<img class="student-image" alt="Student_Image" src="<?php echo ($value->stdImg != "") ? $value->stdImg : get_template_directory_uri()."/img/No_Image.jpg" ?>" style="<?= $img_style ?>">
					<?php endif; ?>

					<style>
						.info-row {
							color: var(--student-name-color, #065499);
							font-weight: bold;
							line-height: var(--line-height, 1.2);
						}
					</style>

					<div style="text-align: left; padding-left: 10px; flex: 1;">
						<div style="font-size: 11px; line-height: 1.4; color: #333;">
							<?php if (!empty($config['show_name'])): ?>
								<div class="info-row"><b>Name</b> <span style="color: var(--student-name-color, #065499);">: <?= $value->stdName ?></span></div>
							<?php endif; ?>
							<?php if (!empty($config['show_father'])): ?>
								<div class="info-row"><b>Father</b> <span>: <?= htmlspecialchars($value->stdFather) ?></span></div>
							<?php endif; ?>
							<?php if (!empty($config['show_mother'])): ?>
								<div class="info-row"><b>Mother</b> <span>: <?= htmlspecialchars($value->stdMother) ?></span></div>
							<?php endif; ?>
							<?php if (!empty($config['show_id'])): ?>
								<div class="info-row"><b>ID</b> <span>: <?= ($s3sRedux['stdidpref'] == 'year') ? $value->stdAdmitYear : $s3sRedux['stdidpref']; ?><?= sprintf("%05s", ($value->studentid + $s3sRedux['stdid'])) ?></span></div>
							<?php endif; ?>
							<?php if (!empty($config['show_class_section'])): ?>
								<div class="info-row">
									<b><?= $person_type === 'teacher' ? 'Designation' : 'Class' ?></b>
									<span>: 
										<?php if ($person_type === 'teacher'): ?>
											<?= htmlspecialchars($value->designation) ?>
										<?php else: ?>
											<?= htmlspecialchars($value->className) ?><?php echo ' ('.htmlspecialchars($value->sectionName).')'; ?>
										<?php endif; ?>
									</span>
								</div>
							<?php endif; ?>
							<?php if (!empty($config['show_class'])): ?>
								<div class="info-row"><b><?= $person_type === 'teacher' ? 'Designation' : 'Class' ?></b> <span>: <?= $person_type === 'teacher' ? htmlspecialchars($value->designation) : htmlspecialchars($value->className) ?></span></div>
							<?php endif; ?>
							<?php if (!empty($config['show_section']) && $person_type !== 'teacher'): ?>
								<div class="info-row"><b>Section</b> <span>: <?= htmlspecialchars($value->sectionName) ?></span></div>
							<?php endif; ?>
							<?php if (!empty($config['show_roll'])): ?>
								<div class="info-row"><b>Roll</b> <span>: <?= $value->infoRoll ?></span></div>
							<?php endif; ?>
							<?php if (!empty($config['show_group'])): ?>
								<div class="info-row"><b>Group</b> <span>: <?= $value->groupName ?></span></div>
							<?php endif; ?>
							<?php if (!empty($config['show_blood'])): ?>
								<div class="info-row"><b>Blood</b> <span>: <?= $value->stdBldGrp != 'N/A' ? $value->stdBldGrp : '' ?></span></div>
							<?php endif; ?>
							<?php if (!empty($config['show_phone'])): ?>
								<div class="info-row"><b>Phone</b> <span>: <?= $value->stdPhone ?></span></div>
							<?php endif; ?>
						</div>
					</div>
                </div>
            </div>
        <?php else: ?>
            <!-- Portrait Layout (Default) -->
            <div style="padding: 6px; text-align: center; margin: 7px 7px; height: 93%; margin-top: 10px; position: relative;">
                
                <div style="margin-bottom: 5px;">
                    <?php if (!empty($config['show_logo'])): ?>
                        <img width="35" src="<?= $s3sRedux['instLogo']['url'] ?>" style="border-radius: 25px" loading="eager" decoding="sync">
                    <?php endif; ?>
                    
                    <?php if (!empty($config['show_inst_name'])): ?>
                        <h3 style="margin: 0; font-size: 14px; color: blue; font-weight: 700; letter-spacing: -0.5px;"><?= $s3sRedux['institute_name'] ?></h3>
                    <?php endif; ?>
                </div>

                <?php if (!empty($config['show_image'])): ?>
                <div class="text-center" style="position: absolute; top: <?= intval($config['image_margin_top'] ?? 75) ?>px; width: 100%; left: 0; z-index: 2;">
                    <?php 
                        $img_style = "";
                        if (($config['image_shape'] ?? 'square') == 'rounded') {
                            $img_style .= "border-radius: 50%;";
                        } else {
                            $img_style .= "border-radius: 20px;"; 
                        }
                        $border_size = intval($config['image_border_size'] ?? 0);
                        $border_color = htmlspecialchars($config['image_border_color'] ?? '#000000');
                        if ($border_size > 0) {
                            $img_style .= "border: {$border_size}px solid {$border_color};";
                        } else {
                            $img_style .= "border: 2px solid #fff;"; 
                        }
                        $img_style .= "box-shadow: 0 2px 4px rgba(0,0,0,0.1);"; 
                    ?>
					<img alt="Student Image" width="<?= $student_image_size ?>" height="<?= $student_image_size ?>" style="<?= $img_style ?> width: <?= $student_image_size ?>px; height: <?= $student_image_size ?>px;" src="<?php echo ($value->stdImg != "") ? $value->stdImg : get_template_directory_uri() . "/img/No_Image.jpg" ?>" loading="eager" decoding="sync">
                </div>
                <?php endif; ?>
                
				<?php 
					$content_margin_top = intval($config['content_margin_top'] ?? 165);
				?>
				<?php if (!empty($config['show_name'])): ?>
				<p class="std-name" style="position: absolute; top: <?= $content_margin_top ?>px; width: 100%; left: 0; margin: 0 !important; z-index: 2; font-size: 12px;"><?= $value->stdName  ?></p>
				<?php endif; ?>

                <table class="card-table" style="position: absolute; top: <?= ($content_margin_top + 30) ?>px; width: 90%; height: auto; left: 5%; margin: 0 !important; margin-bottom: auto; z-index: 1; font-size: 10px; line-height: <?= $config['line_height'] ?? 1 ?>;">
                    <?php if (!empty($config['show_id'])): ?>
                    <tr><td colspan="2" style="padding-bottom:2px; font-weight:bold; color:blue;">ID: <?= ($s3sRedux['stdidpref'] == 'year') ? $value->stdAdmitYear : $s3sRedux['stdidpref']; ?><?= sprintf("%05s", ($value->studentid + $s3sRedux['stdid'])) ?></td></tr>
                    <?php endif; ?>

                    <?php if (!empty($config['show_father'])): ?>
                    <tr class="info-row">
                        <td class="info-label">Father</td>
                        <td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text"><?= htmlspecialchars($value->stdFather) ?></span></td>
                    </tr>
                    <?php endif; ?>

                    <?php if (!empty($config['show_mother'])): ?>
                    <tr class="info-row">
                        <td class="info-label">Mother</td>
                        <td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text"><?= htmlspecialchars($value->stdMother) ?></span></td>
                    </tr>
                    <?php endif; ?>
					
					<?php if (!empty($config['show_class_section'])): ?>
                    <tr class="info-row">
                        <td class="info-label">Class</td>
                        <td class="info-value">
                            <span class="colon">:&nbsp;</span>
                            <span class="value-text"><?= htmlspecialchars($value->className) ?> <?php echo '('.htmlspecialchars($value->sectionName).')'; ?></span>
                        </td>
                    </tr>
                    <?php endif; ?>

					<?php if (!empty($config['show_class'])): ?>
                    <tr class="info-row">
                        <td class="info-label">Class</td>
                        <td class="info-value">
                            <span class="colon">:&nbsp;</span>
                            <span class="value-text"><?= htmlspecialchars($value->className) ?></span>
                        </td>
                    </tr>
                    <?php endif; ?>

					<?php if (!empty($config['show_section'])): ?>
                    <tr class="info-row">
                        <td class="info-label">Section</td>
                        <td class="info-value">
                            <span class="colon">:&nbsp;</span>
                            <span class="value-text"><?= htmlspecialchars($value->sectionName) ?></span>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php if (!empty($config['show_group'])): ?>
                    <tr class="info-row">
                        <td class="info-label">Group</td>
                        <td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text"><?= htmlspecialchars($value->groupName) ?></span></td>
                    </tr>
                    <?php endif; ?>

                    <?php if (!empty($config['show_roll'])): ?>
                    <tr class="info-row">
                        <td class="info-label">Roll</td>
                        <td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text"><?= htmlspecialchars($value->infoRoll) ?></span></td>
                    </tr>
                    <?php endif; ?>

                    <?php if (!empty($config['show_year'])): ?>
                    <tr class="info-row">
                        <td class="info-label">Year</td>
                        <td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text"><?= htmlspecialchars($value->infoYear) ?></span></td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if (!empty($config['show_blood'])): ?>
                    <tr class="info-row">
                        <td class="info-label">Blood</td>
                        <td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text"><?= htmlspecialchars($value->stdBldGrp) ?></span></td>
                    </tr>
                    <?php endif; ?>

                    <?php if (!empty($config['show_dob'])): ?>
                    <tr class="info-row">
                        <td class="info-label">DOB</td>
                        <td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text"><?= date('d-m-Y', strtotime($value->stdBrith)) ?></span></td>
                    </tr>
                    <?php endif; ?>

                    <?php if (!empty($config['show_phone'])): ?>
                    <tr class="info-row">
                        <td class="info-label">Phone</td>
                        <td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text"><?= htmlspecialchars($value->stdPhone) ?></span></td>
                    </tr>
                    <?php endif; ?>
                    
                    <?php if (!empty($config['show_address'])): ?>
                    <tr class="info-row">
                        <td class="info-label">Address</td>
                        <td class="info-value">
                            <span class="colon">:&nbsp;</span>
                            <span class="value-text">
                            <?php
                            $address = $value->stdPresent;
                            $addressParts = explode(',', $address);
                            echo isset($addressParts[0]) ? trim($addressParts[0]) : '';
                            if (isset($addressParts[1])) echo ', ' . trim($addressParts[1]);
                            ?>
                            </span>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
                
                <?php if (!empty($config['show_signature'])): ?>
                    <div style="text-align: center; position: absolute; bottom: 5px; right: 7px; z-index: 3;">
                        <img align="Principal_Signature" style="display: block; margin: 0 auto; height: auto; max-width: 50px;" src="<?= $s3sRedux['principalSign']['url'] ?>">
                        <span style="font-size: 10px; color: blue; border-top: 1px solid blue; margin-top: 2px; display: inline-block; padding-top: 2px; min-width: 80px;"><?= $s3sRedux['inst_head_title'] ?></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
<?php
}
										} else {
											echo "<h3 class='text-center'>No Student Found</h3>";
										}
										?>
									</div>
								</div>
								<?php 
									$frontHtml = ob_get_clean();
									$classTag = isset($_GET['class']) && $_GET['class'] !== '' ? $_GET['class'] : 'all';
									$yearTag = isset($_GET['syear']) && $_GET['syear'] !== '' ? $_GET['syear'] : 'all';
									$snapshot = s3s_store_html_snapshot($frontHtml, ['idcard', 'front', 'class-'.$classTag, 'year-'.$yearTag], ['subdir' => 'idcards', 'prefix' => 'id-front']);
									$staticUrl = $snapshot['url'];
									echo $frontHtml;
								?>
								<script>
									document.getElementById('printArea').setAttribute('data-static-url', '<?= esc_js($staticUrl) ?>');
								</script>

							<?php } elseif (isset($_GET['creatId'])) { ?>
								<div class="col-md-12"><h3 class='text-center'>Please apply at least one filter (Class, Section, Year, or Roll).</h3></div>
							<?php } ?>

						</div>
					</div>

					<?php if (! is_admin()) { ?>
					</div>
				</div>
			</div>
		</div>
	</div>

<?php get_footer();
					} ?>

<script type="text/javascript">
	const idCardActionsUrl = '<?php echo get_template_directory_uri() . "/adminPages/functions/id-card-actions.php"; ?>';

	(function($) {
		$('#resultClass').change(function() {
			var ajaxUrl = idCardActionsUrl;

			$.ajax({
				url: ajaxUrl,
				method: "POST",
				data: {
					class: $(this).val(),
					type: 'getYears'
				},
				dataType: "html"
			}).done(function(msg) {
				$("#resultYear").html(msg);
				$("#resultYear").prop('disabled', false);
			});

			$.ajax({
				url: ajaxUrl,
				method: "POST",
				data: {
					class: $(this).val(),
					type: 'getSection'
				},
				dataType: "html"
			}).done(function(msg) {
				$("#resultSection").html(msg);
				$("#resultSection").prop('disabled', false);
			});
		});

	})(jQuery);

	/* Samples Modal JS */
	function openSamplesModal() {
		document.getElementById('modalIdSamples').classList.add('active');
	}

	function closeSamplesModal() {
		document.getElementById('modalIdSamples').classList.remove('active');
	}

	/* Integrated Config Modal JS */
	async function refreshDesignsList(selectedNo = null) {
		const select = document.getElementById('configDesignSelect');
		const personType = document.getElementById('configPersonType').value || 'student';
		const currentVal = selectedNo || select.value || 1;
		const previewContainerDiv = document.getElementById('previewContainer');
		previewContainerDiv.classList.add(personType);
		
		try {
			const res = await fetch(idCardActionsUrl, {
				method: 'POST',
				headers: {'Content-Type': 'application/x-www-form-urlencoded'},
				body: new URLSearchParams({
					'action': 'get_id_card_designs',
					'person_type': personType
				})
			});
			const response = await res.json();
			if (response.success && response.data) {
				select.innerHTML = '';
				response.data.forEach(design => {
					const opt = document.createElement('option');
					opt.value = design.no;
					opt.textContent = design.name;
					select.appendChild(opt);
				});
				// Ensure currentVal exists in options
				const hasOption = Array.from(select.options).some(opt => opt.value == currentVal);
				select.value = hasOption ? currentVal : (select.options[0] ? select.options[0].value : 1);
			}
		} catch (e) {
			console.error('Failed to fetch designs:', e);
		}
	}

	async function changePersonType(type) {
		const loader = document.getElementById('globalFetchLoader');
		loader.classList.add('active');
		try {
			// Update Labels
			const isTeacher = (type === 'teacher');
			document.querySelectorAll('.label-id').forEach(el => el.textContent = isTeacher ? 'ID' : 'ID No');
			document.querySelectorAll('.label-class').forEach(el => el.textContent = isTeacher ? 'Designation' : 'Class');
			document.querySelectorAll('.label-roll').forEach(el => el.textContent = isTeacher ? 'Qualification' : 'Roll');
			document.querySelectorAll('.label-year').forEach(el => el.textContent = isTeacher ? 'Joining' : 'Year');
			document.querySelectorAll('.label-dob').forEach(el => el.textContent = isTeacher ? 'Birth Date' : 'DOB');
			document.querySelectorAll('.label-phone').forEach(el => el.textContent = isTeacher ? 'Phone' : 'Phone');
			
			const sectionWrapper = document.querySelector('.label-section-wrapper');
			if (sectionWrapper) sectionWrapper.style.display = isTeacher ? 'none' : 'flex';

			await refreshDesignsList();
			const designNo = document.getElementById('configDesignSelect').value || 1;
			await loadDesignIntegrated(designNo);
		} finally {
			loader.classList.remove('active');
		}
	}

	async function createNewDesign() {
		const btn = document.getElementById('createNewDesignBtn');
		const personType = document.getElementById('configPersonType').value || 'student';
		const oldHtml = btn.innerHTML;
		btn.disabled = true;
		btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i>';
		
		try {
			const res = await fetch(idCardActionsUrl, {
				method: 'POST',
				headers: {'Content-Type': 'application/x-www-form-urlencoded'},
				body: new URLSearchParams({
					'action': 'create_id_card_design',
					'person_type': personType
				})
			});
			const response = await res.json();
			if (response.success) {
				await refreshDesignsList(response.new_design_no);
				await loadDesignIntegrated(response.new_design_no);
			}
		} catch (e) {
			console.error('Failed to create design:', e);
			alert('Failed to create new design.');
		} finally {
			btn.disabled = false;
			btn.innerHTML = oldHtml;
		}
	}

	async function openDesignConfigModal() {
		const loader = document.getElementById('globalFetchLoader');
		loader.classList.add('active');
		
		try {
			await refreshDesignsList();
			const designNo = document.getElementById('configDesignSelect').value || 1;
			await loadDesignIntegrated(designNo);
			document.getElementById('modalDesignIntegrated').classList.add('active');
		} catch (e) {
			console.error('Failed to open modal:', e);
			alert('Failed to load design settings. Please check console.');
		} finally {
			loader.classList.remove('active');
		}
	}

	function closeDesignConfigModal() {
		document.getElementById('modalDesignIntegrated').classList.remove('active');
	}

	let currentSide = 'front';
	function updateLivePreview() {
		const form = document.getElementById('designConfigForm');
		const liveCard = document.getElementById('liveCardPreview');
		const portraitBox = document.getElementById('previewInnerBox');
		const teacherPortraitBox = document.getElementById('previewTeacherPortraitBox');
		const landscapeBox = document.getElementById('previewLandscapeBox');
		const backsideBox = document.getElementById('previewBacksideBox');
		
		const designNo = document.getElementById('configDesignSelect').value;
		const personType = document.getElementById('configPersonType').value || 'student';
		const orientation = form.elements['orientation'].value;
		const designType = form.elements['design_type'] ? form.elements['design_type'].value : 'front';
		const uploadStatus = document.getElementById('bgIntegratedStatus');
		const landscapeTeacherDesig = document.getElementById('prev_landscape_teacher_designation');
		
		const isTeacher = (personType === 'teacher');
		currentSide = designType;

		// Update Header in Landscape
		if (landscapeTeacherDesig) {
			landscapeTeacherDesig.style.display = isTeacher ? 'block' : 'none';
		}
		
		// Update Names in Preview
		const portraitName = portraitBox.querySelector('#prev_show_name');
		if (portraitName) portraitName.textContent = isTeacher ? 'SAMPLE TEACHER' : 'SAMPLE STUDENT NAME';
		const teacherPortraitName = teacherPortraitBox ? teacherPortraitBox.querySelector('#prev_teacher_show_name') : null;
		if (teacherPortraitName) teacherPortraitName.textContent = 'SAMPLE TEACHER';
		const teacherPortraitDesignation = teacherPortraitBox ? teacherPortraitBox.querySelector('#prev_teacher_show_designation') : null;
		if (teacherPortraitDesignation) teacherPortraitDesignation.textContent = 'SENIOR TEACHER';
		const landscapeNameEl = landscapeBox.querySelector('#prev_landscape_name span');
		if (landscapeNameEl) landscapeNameEl.textContent = isTeacher ? 'SAMPLE TEACHER' : 'SAMPLE STUDENT NAME';

		// Toggle Sections Visibility
		const imSection = document.getElementById('imageStylingSection');
		
		if (designType === 'back') {
			if (imSection) imSection.style.display = 'none';
			
			portraitBox.style.display = 'none';
			if (teacherPortraitBox) teacherPortraitBox.style.display = 'none';
			landscapeBox.style.display = 'none';
			backsideBox.style.display = 'block';
			if (orientation === 'landscape') {
				liveCard.classList.remove('portrait-mode');
				liveCard.classList.add('landscape-mode');
			} else {
				liveCard.classList.remove('landscape-mode');
				liveCard.classList.add('portrait-mode');
			}
		} else {
			if (imSection) imSection.style.display = 'block';
			
			backsideBox.style.display = 'none';
			if (isTeacher && orientation === 'portrait') {
				liveCard.classList.remove('landscape-mode');
				liveCard.classList.add('portrait-mode');
				portraitBox.style.display = 'none';
				landscapeBox.style.display = 'none';
				if (teacherPortraitBox) teacherPortraitBox.style.display = 'block';
			} else if (orientation === 'landscape') {
				liveCard.classList.remove('portrait-mode');
				liveCard.classList.add('landscape-mode');
				portraitBox.style.display = 'none';
				if (teacherPortraitBox) teacherPortraitBox.style.display = 'none';
				landscapeBox.style.display = 'block';
			} else {
				liveCard.classList.remove('landscape-mode');
				liveCard.classList.add('portrait-mode');
				portraitBox.style.display = 'block';
				if (teacherPortraitBox) teacherPortraitBox.style.display = 'none';
				landscapeBox.style.display = 'none';
			}
		}

		// Update Background
		let bgUrl = '';
		if (window.idDesignBackgrounds && window.idDesignBackgrounds[designType] && window.idDesignBackgrounds[designType][designNo]) {
			bgUrl = window.idDesignBackgrounds[designType][designNo];
			uploadStatus.textContent = 'Custom ' + designType + ' Background';
		} else {
			bgUrl = ''; // No Default Background
			uploadStatus.textContent = 'No background uploaded';
		}
		
		if (bgUrl) {
			liveCard.style.backgroundImage = `url('${bgUrl}')`;
			liveCard.style.backgroundColor = 'transparent';
		} else {
			liveCard.style.backgroundImage = 'none';
			liveCard.style.backgroundColor = '#f3f4f6'; // Light gray placeholder
		}
		
		const fields = [
			'show_logo', 'show_inst_name', 'show_image', 'show_name', 'show_id', 
			'show_mpo_index', 'show_class_section', 'show_class', 'show_section', 'show_group', 'show_roll', 'show_year', 'show_dob', 
			'show_blood', 'show_phone', 'show_father', 'show_mother', 
			'show_address', 'show_inst_address', 'show_student_address', 
			'show_inst_phone', 'show_signature'
		];
		
		fields.forEach(field => {
			const checkbox = form.elements[field];
			const isVisible = checkbox ? checkbox.checked : true;
			
			if (field !== 'show_class_section' && field !== 'show_class' && field !== 'show_section' && field !== 'show_group') {
				// For Portrait
				const portraitEl = document.getElementById('prev_' + field);
				const portraitLabel = portraitEl ? portraitEl.querySelector('.info-label') : null;
				if (portraitLabel) {
					if (field === 'show_id') portraitLabel.textContent = isTeacher ? 'ID' : 'ID No';
					if (field === 'show_roll') portraitLabel.textContent = isTeacher ? 'Qualification' : 'Roll';
					if (field === 'show_year') portraitLabel.textContent = isTeacher ? 'Joining' : 'Year';
					if (field === 'show_dob') portraitLabel.textContent = isTeacher ? 'Birth Date' : 'DOB';
				}

				if (portraitEl) portraitEl.style.display = isVisible ? '' : 'none';
				
				// For Landscape
				let landscapeId = 'prev_landscape_' + field.replace('show_', '');
				if (field === 'show_year') landscapeId = 'prev_landscape_session';
				if (field === 'show_image') landscapeId = 'prev_landscape_image';
				if (field === 'show_logo') landscapeId = 'prev_landscape_logo';
				if (field === 'show_inst_name') landscapeId = 'prev_landscape_inst_name';
				if (field === 'show_signature') landscapeId = 'prev_landscape_signature';
				
				const landscapeEl = document.getElementById(landscapeId);
				const landscapeLabel = landscapeEl ? landscapeEl.querySelector('b') : null;
				if (landscapeLabel) {
					if (field === 'show_id') landscapeLabel.textContent = isTeacher ? 'ID:' : 'ID:';
					if (field === 'show_mpo_index') landscapeLabel.textContent = isTeacher ? 'MPO:' : 'MPO:';
					if (field === 'show_father') landscapeLabel.textContent = isTeacher ? 'Father:' : 'Father:';
					if (field === 'show_mother') landscapeLabel.textContent = isTeacher ? 'Mother:' : 'Mother:';
					if (field === 'show_dob') landscapeLabel.textContent = isTeacher ? 'Birth Date:' : 'DOB:';
					if (field === 'show_phone') landscapeLabel.textContent = 'Phone:';
				}

				if (landscapeEl) {
					landscapeEl.style.display = isVisible ? '' : 'none';
					if (field === 'show_year' && isTeacher) landscapeEl.innerHTML = '<span style="margin-left: 10px;"><span class="label-year">JOINING</span>: 01-01-2020</span>';
					else if (field === 'show_year') landscapeEl.innerHTML = '<span style="margin-left: 10px;"><span class="label-year">SESSION</span>: 2024</span>';
				}
				
				// For Backside
				if (field === 'show_logo') {
					const backLogo = document.getElementById('prev_back_logo');
					if (backLogo) backLogo.style.display = isVisible ? '' : 'none';
				}
				if (field === 'show_inst_name') {
					const backInstName = document.getElementById('prev_back_inst_name');
					if (backInstName) backInstName.style.display = isVisible ? '' : 'none';
				}
				if (field === 'show_address') {
					const backStudentAddress = document.getElementById('prev_back_address');
					if (backStudentAddress) backStudentAddress.style.display = isVisible ? '' : 'none';
				}
				if (field === 'show_inst_address') {
					const backInstAddress = document.getElementById('prev_back_inst_address');
					if (backInstAddress) backInstAddress.style.display = isVisible ? '' : 'none';
				}
				if (field === 'show_student_address') {
					const backStudentAddress = document.getElementById('prev_back_student_address');
					if (backStudentAddress) backStudentAddress.style.display = isVisible ? '' : 'none';
				}
				if (field === 'show_phone') {
					const backStudentPhone = document.getElementById('prev_back_phone');
					if (backStudentPhone) backStudentPhone.style.display = isVisible ? '' : 'none';
				}
				if (field === 'show_inst_phone') {
					const backInstPhone = document.getElementById('prev_back_inst_phone');
					if (backInstPhone) backInstPhone.style.display = isVisible ? '' : 'none';
				}
				if (field === 'show_signature') {
					const backSig = document.getElementById('prev_back_signature');
					if (backSig) backSig.style.display = isVisible ? '' : 'none';
				}
				if (field === 'show_name') {
					const backName = document.getElementById('prev_back_name');
					if (backName) backName.style.display = isVisible ? '' : 'none';
				}
				if (field === 'show_id') {
					const backId = document.getElementById('prev_back_id');
					if (backId) backId.style.display = isVisible ? '' : 'none';
				}
				if (field === 'show_father') {
					const backFather = document.getElementById('prev_back_father');
					if (backFather) backFather.style.display = isVisible ? '' : 'none';
				}
				if (field === 'show_mother') {
					const backMother = document.getElementById('prev_back_mother');
					if (backMother) backMother.style.display = isVisible ? '' : 'none';
				}
				if (field === 'show_roll') {
					const backRoll = document.getElementById('prev_back_roll');
					if (backRoll) backRoll.style.display = isVisible ? '' : 'none';
				}
				if (field === 'show_year') {
					const backYear = document.getElementById('prev_back_year');
					if (backYear) backYear.style.display = isVisible ? '' : 'none';
				}
				if (field === 'show_blood') {
					const backBlood = document.getElementById('prev_back_blood');
					if (backBlood) backBlood.style.display = isVisible ? '' : 'none';
				}
				if (field === 'show_dob') {
					const backDob = document.getElementById('prev_back_dob');
					if (backDob) backDob.style.display = isVisible ? '' : 'none';
				}
			}

			// Special case for class/section group
			if (field === 'show_class_section' || field === 'show_class' || field === 'show_section' || field === 'show_group') {
				const classSectionEnabled = form.elements['show_class_section'].checked;
				const classVisible = form.elements['show_class'].checked;
				const sectionVisible = !isTeacher && form.elements['show_section'].checked;
				const groupVisible = !isTeacher && form.elements['show_group'].checked;
				const classSectionVisible = classSectionEnabled;
				
				const csRow = document.getElementById('prev_show_class_section');
				const classOnlyRow = document.getElementById('prev_show_class_only');
				const sectionOnlyRow = document.getElementById('prev_show_section_only');
				if (csRow) {
					const label = csRow.querySelector('.info-label');
					if (label) label.textContent = isTeacher ? 'Designation' : 'Class';
					csRow.style.display = classSectionVisible ? '' : 'none';
					const val = document.getElementById('prev_show_class_section_val');
					if (val) {
						if (isTeacher) val.textContent = 'SENIOR TEACHER';
						else val.textContent = 'NINE (A)';
					}
				}

				if (classOnlyRow) {
					const label = classOnlyRow.querySelector('.info-label');
					if (label) label.textContent = isTeacher ? 'Designation' : 'Class';
					classOnlyRow.style.display = classVisible ? '' : 'none';
					const val = document.getElementById('prev_show_class_only_val');
					if (val) val.textContent = isTeacher ? 'SENIOR TEACHER' : 'NINE';
				}

				if (sectionOnlyRow) {
					sectionOnlyRow.style.display = sectionVisible ? '' : 'none';
					const val = document.getElementById('prev_show_section_only_val');
					if (val) val.textContent = 'A';
				}

				const prGroup = document.getElementById('prev_show_group');
				if (prGroup) {
					prGroup.style.display = groupVisible ? '' : 'none';
				}
				
				// For Backside class/section
				const bcsRow = document.getElementById('prev_back_class_section');
				const bClassOnlyRow = document.getElementById('prev_back_class_only');
				const bSectionOnlyRow = document.getElementById('prev_back_section_only');
				if (bcsRow) {
					const blabel = bcsRow.querySelector('.info-label');
					if (blabel) blabel.textContent = isTeacher ? 'Designation' : 'Class';
					bcsRow.style.display = classSectionVisible ? '' : 'none';
					const bval = document.getElementById('prev_back_class_section_val');
					if (bval) {
						if (isTeacher) bval.textContent = 'SENIOR TEACHER';
						else bval.textContent = 'NINE (A)';
					}
				}

				if (bClassOnlyRow) {
					const blabel = bClassOnlyRow.querySelector('.info-label');
					if (blabel) blabel.textContent = isTeacher ? 'Designation' : 'Class';
					bClassOnlyRow.style.display = classVisible ? '' : 'none';
					const bval = document.getElementById('prev_back_class_only_val');
					if (bval) bval.textContent = isTeacher ? 'SENIOR TEACHER' : 'NINE';
				}

				if (bSectionOnlyRow) {
					bSectionOnlyRow.style.display = sectionVisible ? '' : 'none';
					const bval = document.getElementById('prev_back_section_only_val');
					if (bval) bval.textContent = 'A';
				}

				const bGroup = document.getElementById('prev_back_group');
				if (bGroup) {
					bGroup.style.display = groupVisible ? '' : 'none';
				}
				
				const lcsRow = document.getElementById('prev_landscape_class_section');
				const lClassOnlyRow = document.getElementById('prev_landscape_class_only');
				const lSectionOnlyRow = document.getElementById('prev_landscape_section_only');
				if (lcsRow) {
					const llabel = lcsRow.querySelector('b');
					if (llabel) llabel.textContent = isTeacher ? 'Designation:' : 'Class:';
					lcsRow.style.display = classSectionVisible ? '' : 'none';
					const lval = document.getElementById('prev_landscape_class_section_val');
					if (lval) {
						if (isTeacher) lval.textContent = 'SENIOR TEACHER';
						else lval.textContent = 'NINE (A)';
					}
				}

				if (lClassOnlyRow) {
					const llabel = lClassOnlyRow.querySelector('b');
					if (llabel) llabel.textContent = isTeacher ? 'Designation:' : 'Class:';
					lClassOnlyRow.style.display = classVisible ? '' : 'none';
					const lval = document.getElementById('prev_landscape_class_only_val');
					if (lval) lval.textContent = isTeacher ? 'SENIOR TEACHER' : 'NINE';
				}

				if (lSectionOnlyRow) {
					lSectionOnlyRow.style.display = sectionVisible ? '' : 'none';
					const lval = document.getElementById('prev_landscape_section_only_val');
					if (lval) lval.textContent = 'A';
				}

				const lGroup = document.getElementById('prev_landscape_group');
				if (lGroup) {
					lGroup.style.display = isTeacher ? 'none' : (groupVisible ? '' : 'none');
				}
			}
		});

		// Image styling
		const shape = form.elements['image_shape'].value;
		const borderSize = form.elements['image_border_size'].value || 0;
		const borderColor = form.elements['image_border_color'].value || '#000000';
		const rawImageMarginTop = form.elements['image_margin_top'] ? form.elements['image_margin_top'].value : '';
		const marginTop = rawImageMarginTop !== '' ? parseInt(rawImageMarginTop, 10) : (orientation === 'landscape' ? 5 : 75);
		const contentMarginTop = parseInt(form.elements['content_margin_top']?.value || '165', 10) || 165;
		const lineHeight = parseFloat(form.elements['line_height']?.value || '1') || 1;
		const imageSize = parseInt(form.elements['student_image_size']?.value || '85', 10) || 85;
		const nameColor = form.elements['student_name_color']?.value || '#065499';

		// Landscape Image and Content Top override based on config
		const prevLandscapeImg = document.getElementById('prev_landscape_image');
		const landscapeFlexContainer = landscapeBox.querySelector('.landscape-flex-container');
		if (prevLandscapeImg) {
			prevLandscapeImg.style.marginTop = marginTop + 'px';
		}
		if (landscapeFlexContainer) {
			landscapeFlexContainer.style.marginTop = contentMarginTop + 'px';
		}

		// Apply config as CSS variables for consistent styling
		// Apply config as CSS variables for consistent styling
		const landscapeHeight = imageSize;
		const landscapeWidth = Math.round(imageSize * 65 / 70);

		if (liveCard) {
			liveCard.style.setProperty('--student-name-color', nameColor);
			liveCard.style.setProperty('--line-height', lineHeight);
			
			// Apply name color to landscape name elements specifically if needed, 
			// though the CSS variable should handle it if applied correctly in HTML
			const lName = landscapeBox.querySelector('#prev_landscape_name b');
			const lNameVal = landscapeBox.querySelector('#prev_landscape_name span');
			if (lName) lName.style.color = nameColor;
			if (lNameVal) lNameVal.style.color = nameColor;

			// Preserve existing landscape ratio (65w x 70h) while scaling
			liveCard.style.setProperty('--student-image-height', landscapeHeight + 'px');
			liveCard.style.setProperty('--student-image-width', landscapeWidth + 'px');
		}

		const prevImgContainer = document.getElementById('prev_show_image');
		const prevImg = prevImgContainer ? prevImgContainer.querySelector('img') : null;
		const teacherPrevImgContainer = document.getElementById('prev_teacher_show_image');
		const teacherPrevImg = teacherPrevImgContainer ? teacherPrevImgContainer.querySelector('img') : null;
		
		if (prevImgContainer) {
			prevImgContainer.style.top = marginTop + 'px';
		}
		if (teacherPrevImgContainer) {
			teacherPrevImgContainer.style.top = marginTop + 'px';
		}

		// Update Content Top (Name and Table)
		const prevName = document.getElementById('prev_show_name');
		const prevTable = portraitBox.querySelector('.card-table');
		if (prevName) prevName.style.top = contentMarginTop + 'px';
		if (prevTable) prevTable.style.top = (contentMarginTop + 30) + 'px';

		if (teacherPortraitBox) {
			const teacherPrevName = teacherPortraitBox.querySelector('#prev_teacher_show_name');
			const teacherDesignation = teacherPortraitBox.querySelector('#prev_teacher_show_designation');
			const teacherPrevTable = teacherPortraitBox.querySelector('.card-table');
			const teacherDesignationTop = contentMarginTop + 20;
			const teacherTableTop = contentMarginTop + (form.elements['show_class'] && form.elements['show_class'].checked ? 46 : 30);
			if (teacherPrevName) teacherPrevName.style.top = contentMarginTop + 'px';
			if (teacherDesignation) teacherDesignation.style.top = teacherDesignationTop + 'px';
			if (teacherPrevTable) teacherPrevTable.style.top = teacherTableTop + 'px';
		}

		if (prevImg) {
			prevImg.style.borderRadius = (shape === 'rounded' ? '50%' : '20px');
			prevImg.style.border = borderSize + 'px solid ' + borderColor;
			prevImg.style.width = imageSize + 'px';
			prevImg.style.height = imageSize + 'px';
		}

		if (prevLandscapeImg) {
			prevLandscapeImg.style.borderRadius = (shape === 'rounded' ? '50%' : '5px');
			prevLandscapeImg.style.border = borderSize + 'px solid ' + borderColor;
			// Aspect ratio is handled by CSS variables --student-image-width/height or inline if needed
			// Let's ensure it follows the same sizing logic
			prevLandscapeImg.style.width = landscapeWidth + 'px';
			prevLandscapeImg.style.height = landscapeHeight + 'px';
		}

		if (teacherPrevImg) {
			teacherPrevImg.style.borderRadius = (shape === 'rounded' ? '50%' : '20px');
			teacherPrevImg.style.border = borderSize + 'px solid ' + borderColor;
			teacherPrevImg.style.width = imageSize + 'px';
			teacherPrevImg.style.height = imageSize + 'px';
		}

		const teacherFieldMap = {
			show_logo: 'prev_teacher_show_logo',
			show_inst_name: 'prev_teacher_show_inst_name',
			show_image: 'prev_teacher_show_image',
			show_name: 'prev_teacher_show_name',
			show_id: 'prev_teacher_show_id',
			show_mpo_index: 'prev_teacher_show_mpo_index',
			show_class: 'prev_teacher_show_designation',
			show_roll: 'prev_teacher_show_roll',
			show_year: 'prev_teacher_show_year',
			show_dob: 'prev_teacher_show_dob',
			show_blood: 'prev_teacher_show_blood',
			show_phone: 'prev_teacher_show_phone',
			show_father: 'prev_teacher_show_father',
			show_signature: 'prev_teacher_show_signature'
		};

		if (isTeacher && orientation === 'portrait' && designType !== 'back' && teacherPortraitBox) {
			Object.keys(teacherFieldMap).forEach(field => {
				const checkbox = form.elements[field];
				const el = document.getElementById(teacherFieldMap[field]);
				const visible = checkbox ? checkbox.checked : true;
				if (el) el.style.display = visible ? '' : 'none';
			});
			const teacherLabelId = teacherPortraitBox.querySelector('#prev_teacher_show_id .info-label');
			if (teacherLabelId) teacherLabelId.textContent = 'ID';
			const teacherLabelRoll = teacherPortraitBox.querySelector('#prev_teacher_show_roll .info-label');
			if (teacherLabelRoll) teacherLabelRoll.textContent = 'EDUCATION';
			const teacherLabelYear = teacherPortraitBox.querySelector('#prev_teacher_show_year .info-label');
			if (teacherLabelYear) teacherLabelYear.textContent = 'JOINING';
			const teacherLabelDob = teacherPortraitBox.querySelector('#prev_teacher_show_dob .info-label');
			if (teacherLabelDob) teacherLabelDob.textContent = 'DOB';
			const teacherLabelFather = teacherPortraitBox.querySelector('#prev_teacher_show_father .info-label');
			if (teacherLabelFather) teacherLabelFather.textContent = 'FATHER';
			const teacherLabelBlood = teacherPortraitBox.querySelector('#prev_teacher_show_blood .info-label');
			if (teacherLabelBlood) teacherLabelBlood.textContent = 'BLOOD';
			const teacherLabelPhone = teacherPortraitBox.querySelector('#prev_teacher_show_phone .info-label');
			if (teacherLabelPhone) teacherLabelPhone.textContent = 'PHONE';
			const teacherDesignation = teacherPortraitBox.querySelector('#prev_teacher_show_designation');
			if (teacherDesignation) {
				teacherDesignation.textContent = 'SENIOR TEACHER';
				teacherDesignation.style.display = form.elements['show_class'] && form.elements['show_class'].checked ? '' : 'none';
			}
		}

		// Landscape Image
		const lImg = document.getElementById('prev_landscape_image');
		if (lImg) {
			lImg.style.borderRadius = (shape === 'rounded' ? '50%' : '5px');
			lImg.style.border = borderSize + 'px solid ' + borderColor;
		}
	}

	function loadDesignIntegrated(designNo) {
		return new Promise((resolve, reject) => {
			const loader = document.getElementById('globalFetchLoader');
			if (loader) loader.classList.add('active');

			const form = document.getElementById('designConfigForm');
			const personType = document.getElementById('configPersonType').value || 'student';
			form.reset();
			
			const statusSpan = document.getElementById('configStatus');
			const uploadStatus = document.getElementById('bgIntegratedStatus');
			
			statusSpan.textContent = '';
			
			// Fetch Field Configurations
			fetch(idCardActionsUrl, {
				method: 'POST',
				headers: {'Content-Type': 'application/x-www-form-urlencoded'},
				body: new URLSearchParams({
					'action': 'get_id_card_config',
					'design_no': designNo,
					'person_type': personType
				})
			})
			.then(res => res.json())
			.then(response => {
				if (response.success && response.data) {
					for (const key in response.data) {
						if (form.elements[key]) {
							if (form.elements[key].type === 'checkbox') {
								form.elements[key].checked = response.data[key];
							} else {
								form.elements[key].value = response.data[key];
							}
						}
					}

					// Backfill defaults for newer fields if missing in stored config
					if (form.elements['student_image_size'] && !form.elements['student_image_size'].value) {
						form.elements['student_image_size'].value = 85;
					}
					if (form.elements['student_name_color'] && !form.elements['student_name_color'].value) {
						form.elements['student_name_color'].value = '#065499';
					}
					if (form.elements['image_margin_top'] && !form.elements['image_margin_top'].value) {
						const fetchedOrientation = response.data.orientation || 'portrait';
						form.elements['image_margin_top'].value = (fetchedOrientation === 'landscape') ? 5 : 75;
					}
					if (form.elements['content_margin_top'] && !form.elements['content_margin_top'].value) {
						form.elements['content_margin_top'].value = 165;
					}
					if (form.elements['line_height'] && !form.elements['line_height'].value) {
						form.elements['line_height'].value = 1;
					}
				} else {
					form.querySelectorAll('input[type="checkbox"]').forEach(cb => {
						if (cb.name === 'show_section' && personType === 'teacher') cb.checked = false;
						else cb.checked = true;
					});

					if (form.elements['student_image_size']) form.elements['student_image_size'].value = 85;
					if (form.elements['student_name_color']) form.elements['student_name_color'].value = '#065499';
					if (form.elements['content_margin_top']) form.elements['content_margin_top'].value = 165;
					if (form.elements['line_height']) form.elements['line_height'].value = 1;
				}

				// Fetch backgrounds
				fetch(idCardActionsUrl, {
					method: 'POST',
					headers: {'Content-Type': 'application/x-www-form-urlencoded'},
					body: new URLSearchParams({
						'action': 'get_id_card_backgrounds',
						'design_no': designNo,
						'person_type': personType
					})
				})
				.then(res => res.json())
				.then(bgResponse => {
					if (bgResponse.success) {
						window.idDesignBackgrounds = bgResponse.data;
						updateLivePreview();
					}
					if (loader) loader.classList.remove('active');
					resolve();
				});
			})
			.catch(err => {
				if (loader) loader.classList.remove('active');
				console.error(err);
				reject(err);
			});
		});
	}
	function applyFieldVisibilityPreview() {
		const form = document.getElementById('designConfigForm');
		const fields = [
			'show_logo', 'show_inst_name', 'show_image', 'show_name', 'show_id', 
			'show_mpo_index', 'show_roll', 'show_year', 'show_dob', 'show_blood', 'show_phone', 
			'show_father', 'show_mother', 'show_address', 
			'show_signature'
		];
		
		fields.forEach(f => {
			const portraitEl = document.getElementById('prev_' + f);
			const landscapeFieldId = f.replace('show_', 'landscape_');
			const landscapeEl = document.getElementById('prev_' + landscapeFieldId);
			const isChecked = form.elements[f].checked;
			
			if (portraitEl) portraitEl.style.display = isChecked ? '' : 'none';
			if (landscapeEl) landscapeEl.style.display = isChecked ? '' : 'none';
		});
		
		// Independent Class/Section rows
		const portraitClassSecEl = document.getElementById('prev_show_class_section');
		const portraitClassEl = document.getElementById('prev_show_class_only');
		const portraitSectionEl = document.getElementById('prev_show_section_only');
		const landscapeClassSecEl = document.getElementById('prev_landscape_class_section');
		const landscapeClassEl = document.getElementById('prev_landscape_class_only');
		const landscapeSectionEl = document.getElementById('prev_landscape_section_only');
		const personType = document.getElementById('configPersonType')?.value || 'student';
		const isTeacher = personType === 'teacher';
		const showClassSection = form.elements['show_class_section'].checked;
		const showClass = form.elements['show_class'].checked;
		const showSection = !isTeacher && form.elements['show_section'].checked;
		const showCombined = showClassSection;
		
		if (portraitClassSecEl) portraitClassSecEl.style.display = showCombined ? '' : 'none';
		if (portraitClassEl) portraitClassEl.style.display = showClass ? '' : 'none';
		if (portraitSectionEl) portraitSectionEl.style.display = showSection ? '' : 'none';
		if (landscapeClassSecEl) landscapeClassSecEl.style.display = showCombined ? '' : 'none';
		if (landscapeClassEl) landscapeClassEl.style.display = showClass ? '' : 'none';
		if (landscapeSectionEl) landscapeSectionEl.style.display = showSection ? '' : 'none';
		
		const portraitCombinedValEl = document.getElementById('prev_show_class_section_val');
		const landscapeCombinedValEl = document.getElementById('prev_landscape_class_section_val');
		if (portraitCombinedValEl) portraitCombinedValEl.textContent = isTeacher ? 'SENIOR TEACHER' : 'NINE (A)';
		if (landscapeCombinedValEl) landscapeCombinedValEl.textContent = isTeacher ? 'SENIOR TEACHER' : 'NINE (A)';

		const portraitClassValEl = document.getElementById('prev_show_class_only_val');
		const portraitSectionValEl = document.getElementById('prev_show_section_only_val');
		const landscapeClassValEl = document.getElementById('prev_landscape_class_only_val');
		const landscapeSectionValEl = document.getElementById('prev_landscape_section_only_val');
		if (portraitClassValEl) portraitClassValEl.textContent = isTeacher ? 'SENIOR TEACHER' : 'NINE';
		if (portraitSectionValEl) portraitSectionValEl.textContent = 'A';
		if (landscapeClassValEl) landscapeClassValEl.textContent = isTeacher ? 'SENIOR TEACHER' : 'NINE';
		if (landscapeSectionValEl) landscapeSectionValEl.textContent = 'A';

		const backCombinedValEl = document.getElementById('prev_back_class_section_val');
		const backClassValEl = document.getElementById('prev_back_class_only_val');
		const backSectionValEl = document.getElementById('prev_back_section_only_val');
		if (backCombinedValEl) backCombinedValEl.textContent = isTeacher ? 'SENIOR TEACHER' : 'NINE (A)';
		if (backClassValEl) backClassValEl.textContent = isTeacher ? 'SENIOR TEACHER' : 'NINE';
		if (backSectionValEl) backSectionValEl.textContent = 'A';

		const backCombinedEl = document.getElementById('prev_back_class_section');
		const backClassEl = document.getElementById('prev_back_class_only');
		const backSectionEl = document.getElementById('prev_back_section_only');
		if (backCombinedEl) backCombinedEl.style.display = showCombined ? '' : 'none';
		if (backClassEl) backClassEl.style.display = showClass ? '' : 'none';
		if (backSectionEl) backSectionEl.style.display = showSection ? '' : 'none';

		// Update Image Style Preview (Portrait)
		const portraitImageContainer = document.getElementById('prev_show_image');
		const portraitPreviewImg = portraitImageContainer ? portraitImageContainer.querySelector('img') : null;
		
		// Update Landscape Image
		const landscapePreviewImg = document.getElementById('prev_landscape_image');
		
		const shape = form.elements['image_shape'].value;
		const borderSize = form.elements['image_border_size'].value || 0;
		const borderColor = form.elements['image_border_color'].value || '#000000';
		const marginTop = form.elements['image_margin_top'].value || 75;
		const contentMarginTop = parseInt(form.elements['content_margin_top']?.value || '165', 10) || 165;

		if (portraitImageContainer) portraitImageContainer.style.top = marginTop + 'px';
		
		const prevName = document.getElementById('prev_show_name');
		const prevTable = document.querySelector('#previewInnerBox .card-table');
		if (prevName) prevName.style.top = contentMarginTop + 'px';
		if (prevTable) prevTable.style.top = (contentMarginTop + 30) + 'px';

		// For landscape, top is float controlled but we can apply some margin if needed. 
		// Actually, user's landscape uses margin-top: -20px. We can let them adjust but landscape layout is tighter.

		[portraitPreviewImg, landscapePreviewImg].forEach(img => {
			if (!img) return;
			if (shape === 'rounded') {
				img.style.borderRadius = '50%';
			} else {
				img.style.borderRadius = '5px'; // Landscape uses 5px radius usually
			}
			
			if (borderSize > 0) {
				img.style.border = `${borderSize}px solid ${borderColor}`;
			} else {
				img.style.border = (img === portraitPreviewImg) ? '2px solid #fff' : '1px solid #ddd';
			}
		});

		// Resize portrait preview image element (landscape uses CSS vars)
		if (portraitPreviewImg) {
			portraitPreviewImg.style.width = imageSize + 'px';
			portraitPreviewImg.style.height = imageSize + 'px';
		}
	}

	// Add listener to form
	document.addEventListener('DOMContentLoaded', function() {
		const configForm = document.getElementById('designConfigForm');
		if (configForm) {
			configForm.addEventListener('change', updateLivePreview);
		}
	});

	function saveDesignConfig() {
		const designNo = document.getElementById('configDesignSelect').value;
		const personType = document.getElementById('configPersonType').value || 'student';
		const form = document.getElementById('designConfigForm');
		const config = {};
		
		form.querySelectorAll('input, select').forEach(el => {
			if (el.type === 'checkbox') {
				config[el.name] = el.checked;
			} else if (el.name) {
				config[el.name] = el.value;
			}
		});

		const statusSpan = document.getElementById('configStatus');
		statusSpan.textContent = 'Saving...';
		statusSpan.style.color = '#3b82f6';

		fetch(idCardActionsUrl, {
			method: 'POST',
			headers: {'Content-Type': 'application/x-www-form-urlencoded'},
			body: new URLSearchParams({
				'action': 'save_id_card_config',
				'design_no': designNo,
				'person_type': personType,
				'config': JSON.stringify(config)
			})
		})
		.then(async res => {
			const text = await res.text();
			const cleanText = text.trim();
			console.log('Save Config Raw Response:', cleanText);
			try {
				return JSON.parse(cleanText);
			} catch (e) {
				console.error('JSON Parse Error:', e, 'Original text:', text);
				throw new Error('Invalid JSON response. See console for details.');
			}
		})
		.then(response => {
			if (response.success) {
				statusSpan.textContent = 'Settings Saved!';
				statusSpan.style.color = '#10b981';
				refreshDesignsList(designNo);
				setTimeout(() => statusSpan.textContent = '', 3000);
			} else {
				statusSpan.textContent = 'Error: ' + (response.message || response.data || 'Unknown error');
				statusSpan.style.color = '#ef4444';
				console.error('Save failed details:', response);
			}
		})
		.catch(err => {
			console.error('Save error:', err);
			statusSpan.textContent = 'Error: ' + err.message;
			statusSpan.style.color = '#ef4444';
		});
	}

	function previewAndUploadBgIntegrated(input) {
		const designNo = document.getElementById('configDesignSelect').value;
		const personType = document.getElementById('configPersonType').value || 'student';
		const liveCard = document.getElementById('liveCardPreview');
		const statusBadge = document.getElementById('bgIntegratedStatus');
		
		if (!input.files.length) return;
		
		const file = input.files[0];
		const reader = new FileReader();
		reader.onload = function(e) {
			liveCard.style.backgroundImage = `url('${e.target.result}')`;
			uploadBgIntegrated(file, designNo, currentSide, personType);
		};
		reader.readAsDataURL(file);
	}

	function uploadBgIntegrated(file, designNo, side = 'front', personType = 'student') {
		const formData = new FormData();
		formData.append('action', 'upload_id_image');
		formData.append('design_no', designNo);
		formData.append('person_type', personType);
		formData.append('id_image', file);
		formData.append('side', side);

		const statusBadge = document.getElementById('bgIntegratedStatus');
		statusBadge.textContent = 'Uploading...';
		statusBadge.className = 'upload-status-badge status-uploading';

		fetch(idCardActionsUrl, {
			method: 'POST',
			body: formData
		})
		.then(async res => {
			const text = await res.text();
			const cleanText = text.trim();
			console.log('Upload Image Raw Response:', cleanText);
			try {
				return JSON.parse(cleanText);
			} catch (e) {
				console.error('JSON Parse Error:', e, 'Original text:', text);
				throw new Error('Invalid JSON response. See console for details.');
			}
		})
		.then(response => {
			if (response.success) {
				statusBadge.textContent = 'Upload Successful!';
				statusBadge.className = 'upload-status-badge status-success';
				
				if (!window.idDesignBackgrounds) window.idDesignBackgrounds = {front: {}, back: {}};
				if (!window.idDesignBackgrounds[side]) window.idDesignBackgrounds[side] = {};
				window.idDesignBackgrounds[side][designNo] = response.url;
				
				// Ensure preview matches server URL
				document.getElementById('liveCardPreview').style.backgroundImage = `url('${response.url}')`;
			} else {
				statusBadge.textContent = 'Error: ' + (response.data || response.message || 'Upload Failed');
				statusBadge.className = 'upload-status-badge status-error';
				console.error('Upload failed details:', response);
			}
		})
		.catch(err => {
			console.error('Upload error:', err);
			statusBadge.textContent = 'Error: ' + err.message;
			statusBadge.className = 'upload-status-badge status-error';
		});
	}

	function removeBgIntegrated() {
		const designNo = document.getElementById('configDesignSelect').value;
		const personType = document.getElementById('configPersonType').value || 'student';
		if (!confirm('Are you sure you want to remove the ' + currentSide + ' background image for Design #' + designNo + '?')) return;

		const statusBadge = document.getElementById('bgIntegratedStatus');
		statusBadge.textContent = 'Removing...';
		statusBadge.className = 'upload-status-badge status-uploading';

		fetch(idCardActionsUrl, {
			method: 'POST',
			headers: {'Content-Type': 'application/x-www-form-urlencoded'},
			body: new URLSearchParams({
				'action': 'delete_id_card_bg',
				'design_no': designNo,
				'person_type': personType,
				'side': currentSide
			})
		})
		.then(async res => {
			const text = await res.text();
			try {
				return JSON.parse(text.trim());
			} catch (e) {
				console.error('JSON Parse Error:', e, 'Raw Response:', text);
				// If text contains 'success":true', treat as success despite parsing error
				if (text.includes('"success":true')) return {success: true};
				throw new Error('Invalid server response');
			}
		})
		.then(response => {
			if (response.success) {
				statusBadge.textContent = 'Removed Successfully';
				statusBadge.className = 'upload-status-badge status-success';
				
				if (window.idDesignBackgrounds && window.idDesignBackgrounds[currentSide]) {
					delete window.idDesignBackgrounds[currentSide][designNo];
				}
				updateLivePreview();
			} else {
				statusBadge.textContent = 'Error Removing';
				statusBadge.className = 'upload-status-badge status-error';
			}
		})
		.catch(err => {
			console.error('BG Removal Error:', err);
			statusBadge.textContent = 'Removal Failed';
			statusBadge.className = 'upload-status-badge status-error';
		});
	}

	function printIdCard(divId) {
		var container = document.getElementById(divId);
		var staticUrl = '';
		if (container && typeof container.getAttribute === 'function') {
			staticUrl = container.getAttribute('data-static-url') || '';
		}

		var buildHeadContent = function() {
			var safeBaseHref = document.location.href.replace(/"/g, '&quot;');
			var headContent = '<meta charset="utf-8"><title>ID Card</title><base href="' + safeBaseHref + '">';
			document.querySelectorAll('head link[rel="stylesheet"], head style').forEach(function(node) {
				if (node.tagName && node.tagName.toLowerCase() === 'link' && node.href) {
					headContent += '<link rel="stylesheet" href="' + node.href + '">';
				} else if (node.outerHTML) {
					headContent += node.outerHTML;
				}
			});
			return headContent;
		};

		var openPrintWindow = function(html) {
			if (!html) return false;
			var printWindow = window.open('', '_blank', 'width=1024,height=768');
			if (!printWindow) return false;

			var doc = printWindow.document;
			var headContent = buildHeadContent();
			doc.open();
			doc.write('<!doctype html><html><head>' + headContent + '</head><body>' + html + '<script>window.addEventListener("load", function() { window.focus(); window.print(); setTimeout(function() { window.close(); }, 250); });<\/script></body></html>');
			doc.close();
			return true;
		};

		if (staticUrl && window.fetch) {
			fetch(staticUrl, { cache: 'reload' })
				.then(function(res) { return res.text(); })
				.then(function(html) {
					if (!openPrintWindow(html)) openPrintWindow(container.innerHTML);
				})
				.catch(function() { openPrintWindow(container.innerHTML); });
			return true;
		}
		
		return openPrintWindow(container.innerHTML);
	}
</script>
