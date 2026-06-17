<?php 
/*
** Template Name: Teacher ID card
*/
global $wpdb;
global $s3sRedux;

$conStyle0 = "margin: 0; line-height: 1;margin-bottom:3px;";
$conStyle = "$conStyle0 font-size: 12px;";
$conStyle1 = "$conStyle0 font-size: 11px;";
$conStyle2 = "$conStyle0 font-size: 13px;";

$selected = isset($_GET['design']) ? intval($_GET['design']) : 1;

function getIdDesignImageUrl($no)
{
	global $wpdb;
	$no = intval($no);
	if ($no <= 0) return false;

	$type = getIdDesignType($no);
	$option_name = ($type === 'back') ? 'id-teacher-design-back-' . $no : 'id-teacher-design-' . $no;

	$image_url = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT option_value FROM sm_options WHERE option_name = %s",
			$option_name
		)
	);

	return $image_url ? esc_url($image_url) : false;
}

function getIdDesignBackImageUrl($no)
{
	global $wpdb;

	$no = intval($no);

	if ($no <= 0) {
		return false;
	}

	$option_name = 'id-teacher-design-back-' . $no;

	$image_url = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT option_value FROM sm_options WHERE option_name = %s",
			$option_name
		)
	);

	return $image_url ? esc_url($image_url) : false;
}

function getIdDesignConfig($no)
{
	global $wpdb;
	$no = intval($no);
	if ($no <= 0) return [];
	
	$option_name = 'id_card_teacher_design_' . $no . '_config';
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
		'show_class' => true,
		'show_section' => false,
		'show_roll' => true,
		'show_year' => true,
		'show_dob' => true,
		'show_blood' => true,
		'show_phone' => true,
		'show_father' => true,
		'show_mother' => true,
		'show_address' => true,
		'show_logo' => true,
		'show_inst_name' => true,
		'show_signature' => true,
		'image_shape' => 'square',
		'image_border_size' => 0,
		'image_border_color' => '#000000',
		'image_margin_top' => 75,
		'content_margin_top' => 165,
		'line_height' => 1,
		'orientation' => 'portrait'
	];

	if ($config_json) {
		$stored = json_decode($config_json, true);
		if (is_array($stored)) {
			return array_merge($defaults, $stored);
		}
	}
	return $defaults;
}

function getAvailableDesignNumbers() {
	global $wpdb;
	$query = "SELECT option_name FROM sm_options WHERE option_name LIKE 'id_card_teacher_design_%_config' OR option_name LIKE 'id-teacher-design-%'";
	$results = $wpdb->get_results($query);
	$numbers = [1];
	foreach ($results as $row) {
		if (preg_match('/id_card_teacher_design_(\d+)_config/', $row->option_name, $matches)) {
			$numbers[] = intval($matches[1]);
		} elseif (preg_match('/id-teacher-design-(\d+)/', $row->option_name, $matches)) {
			$numbers[] = intval($matches[1]);
		}
	}
	$unique = array_unique($numbers);
	sort($unique);
	return $unique;
}

function getIdDesignName($no) {
	$config = getIdDesignConfig($no);
	return !empty($config['design_name']) ? $config['design_name'] : 'Design ' . $no;
}

function getIdDesignType($no) {
	$config = getIdDesignConfig($no);
	return !empty($config['design_type']) ? $config['design_type'] : 'front';
}

function generateBarcodeSVG($uid = null)
{
	if (empty($uid)) {
		$uid = '2610100';
	}

	$uid = htmlspecialchars($uid, ENT_QUOTES, 'UTF-8');
	$barcodeUrl = "https://barcode.orcascan.com/?type=code128&data=" . urlencode($uid);
	$svg = @file_get_contents($barcodeUrl);

	if ($svg === false) {
		return '';
	}

	return $svg;
}
?>

<style>
.card-parent {
	width: 54.2mm;
	height: 87.2mm;
	background: #f0f0f0;
	display: inline-block;
	margin: 5px;
	overflow: hidden;
	background-size: cover;
	background-position: center;
	position: relative;
	page-break-inside: avoid;
	break-inside: avoid;
}
.card-parent.portrait-mode {
	width: 54.2mm;
	height: 87.2mm;
}
.card-parent.landscape-mode {
	width: 87.2mm;
	height: 54.2mm;
}
.std-name {
	font-weight: bold;
	border-radius: 40px;
	margin-bottom: 0;
	font-size:13px;
	margin: 5px -2px 0px 5px;
	color:#065499;
	padding: 0px 20px;
	margin-bottom:2px;
	min-height:25px;
	margin-left:-6px;
	display: flex;
	align-items: center;
	justify-content: center;
	text-transform: uppercase;
	padding: 0px 18px;
}
.info-row {
	display: grid;
	grid-template-columns: 38% auto;
	line-height: 1.3;
	margin-bottom: 2px;
	font-size: 11px;
}
.info-label {
	font-weight: bold;
	white-space: nowrap;
	vertical-align: top;
}
.landscape-mode .student-image {
	border: 1px solid black;
	margin-right: 10px;
	margin-top: 70px;
	float: right;
	height: 70px;
	width: 65px;
	object-fit: fill;
}
.landscape-mode .info-row {
	display: flex;
	margin-bottom: 2px;
}
.landscape-mode .info-row b {
	min-width: 60px;
	display: inline-block;
}
.info-value {
	text-transform: uppercase;
	display: grid;
	grid-template-columns: 10px auto;
	word-break: break-word;
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
	position:absolute;
	margin: 1px 0 0 15px;
	width:95%;
	font-size:12px;
	color:black;
	text-align:left;
	border-collapse:collapse;
}
.card-table .info-row,
.card-table .info-row td,
.card-table .info-row .info-value,
.card-table .info-row .value-text {
	line-height: var(--table-line-height, 1) !important;
}
.id-card-content {
	height: 100%;
	position: relative;
	color: #0f172a;
	font-family: "Source Sans Pro", "Segoe UI", Tahoma, sans-serif;
}
.id-modern-panel {
	inset: 0px !important;
	position: absolute;
	background: rgba(255, 255, 255, 0.9);
	border: 1px solid rgba(15, 23, 42, 0.1);
	box-shadow: 0 4px 12px rgba(15, 23, 42, 0.12);
	overflow: hidden;
}
.id-modern-header {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 8px 10px;
	background: linear-gradient(135deg, rgba(6, 84, 153, 0.95), rgba(8, 145, 178, 0.95));
	color: #fff;
}
.id-modern-logo {
	width: 38px;
	height: 38px;
	border-radius: 50%;
	object-fit: cover;
	background: #fff;
	padding: 2px;
}
.id-modern-school {
	font-size: 10px;
	font-weight: 700;
	line-height: 1.25;
	text-transform: uppercase;
}
.id-modern-title {
	margin: 0;
	padding: 4px 10px;
	font-size: 16px;
	font-weight: 700;
	letter-spacing: 0.4px;
	text-transform: uppercase;
	color: #075985;
	background: rgba(14, 116, 144, 0.1);
	border-bottom: 1px solid rgba(14, 116, 144, 0.2);
}
.id-modern-body {
	padding: 8px 10px 54px;
}
.id-modern-photo {
	width: 76px;
	height: 76px;
	object-fit: fill;
	display: block;
	margin: 0 auto 6px;
	border-radius: 12px;
	border: 2px solid #fff;
	box-shadow: 0 2px 8px rgba(2, 6, 23, 0.2);
}
.id-modern-name {
	margin: 0 0 6px;
	text-align: center;
	font-size: 12px;
	font-weight: 700;
	color: #0c4a6e;
	text-transform: uppercase;
}
.id-modern-list {
	display: grid;
	gap: 3px;
	font-size: 9.2px;
	line-height: 1.3;
}
.id-modern-item {
	display: grid;
	grid-template-columns: 60px auto;
	gap: 4px;
}
.id-modern-item b {
	color: #1e3a8a;
}
.id-modern-footer {
	position: absolute;
	left: 0;
	right: 0;
	bottom: 0;
	display: flex;
	align-items: flex-end;
	justify-content: space-between;
	padding: 6px 10px 8px;
	background: linear-gradient(180deg, rgba(236, 253, 245, 0.85), rgba(224, 242, 254, 0.95));
	border-top: 1px solid rgba(2, 132, 199, 0.2);
}
.id-modern-join {
	font-size: 9px;
	font-weight: 700;
	color: #0f766e;
}
.id-modern-sign {
	text-align: center;
}
.id-modern-sign img {
	max-width: 56px;
	max-height: 20px;
	display: block;
	margin: 0 auto;
}
.id-modern-sign span {
	display: inline-block;
	margin-top: 2px;
	font-size: 8px;
	color: #0c4a6e;
	border-top: 1px solid #0c4a6e;
	padding-top: 1px;
	min-width: 62px;
}
.landscape-mode .id-modern-header {
	padding: 6px 8px;
}
.landscape-mode .id-modern-logo {
	width: 30px;
	height: 30px;
}
.landscape-mode .id-modern-school {
	font-size: 9px;
}
.landscape-mode .id-modern-title {
	font-size: 16px;
	padding: 6px 8px;
}
.landscape-mode .id-modern-body {
	display: grid;
	grid-template-columns: 74px 1fr;
	gap: 8px;
	align-items: start;
	padding: 6px 8px 38px;
}
.landscape-mode .id-modern-photo {
	width: 70px;
	height: 70px;
	margin: 0;
}
.landscape-mode .id-modern-name {
	text-align: left;
	font-size: 11px;
	margin-bottom: 5px;
}
.landscape-mode .id-modern-list {
	font-size: 8.3px;
}
.landscape-mode .id-modern-item {
	grid-template-columns: 50px auto;
}
.landscape-mode .id-modern-footer {
	padding: 4px 8px 6px;
}
.landscape-mode .id-modern-sign img {
	max-height: 16px;
}
.landscape-mode .id-modern-sign span {
	font-size: 7px;
	min-width: 52px;
}
</style>

<?php if ( ! is_admin() ) { get_header(); ?>
<div class="b-layer-main">

	<div class="">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
<?php } ?>

<div class="container-fluid maxAdminpages" style="padding-left: 0">
	<h2>ID Card Management		
		<a href="<?= home_url("/admin-idcard") ?>" class="btn btn-primary pull-right" style="margin-bottom:10px;">Student ID Card</a>
		<a href="<?= home_url("/admin-idcard?open_design=1&person_type=teacher") ?>" class="btn btn-warning pull-right" style="margin-bottom:10px; margin-right:10px;">Configure Teacher Designs</a>
	</h2><br>

	<div class="row">

		<div class="col-md-12">
			<div class="panel panel-info">
			  <div class="panel-heading"><h3>Teacher ID Card<br><small>Print Id Card</small></h3></div>
			  <div class="panel-body">

					<form class="form-inline" action="" method="GET">
						<input type="hidden" name="page" value="teacheridcard">

						<div class="form-group">
	            <label>Teacher Name</label>
	            <input type="text" name="tcname" class="form-control">
	          </div>

						<div class="form-group">
							<label>Design Template</label>
							<select name="design" class="form-control">
								<?php 
								$avail_designs = getAvailableDesignNumbers();
								foreach ($avail_designs as $no) {
									$bg_img = getIdDesignImageUrl($no);
									if (!$bg_img) {
										$bg_img = get_template_directory_uri() . '/img/No_Image.jpg';
									}
									$isSelected = ($selected == $no) ? 'selected' : '';
									$type = ucfirst(getIdDesignType($no));
									echo "<option value='$no' data-preview='$bg_img' $isSelected>" . getIdDesignName($no) . " ($type)</option>";
								}
								?>
							</select>
						</div>

						<div class="form-group">
							<input type="submit" name="creatId" value="Create ID" class="btn btn-primary">
						</div>

					</form>

					<p style="font-size:small; color:slate;">Keep the field empty to generate for all teachers. Use "Configure Teacher Designs" to open the same design modal in teacher mode.</p>
			  </div>
			</div>
		</div>

		<?php

		if(isset($_GET['tcname'])){ ?>
 <?php if(current_user_can('administrator')) {?>
			<div class="col-md-12">
	  		<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>
			</div>
 <?php } ?>
		  <div id="printArea" class="col-md-12 printBG">


			  <div class="printArea" style="text-align: center;">
					<style type="text/css"> 
						@page { size: auto !important;  margin: 0px !important; }
			  		*{
						  -webkit-print-color-adjust: exact !important;
						  print-color-adjust: exact !important;
			  		}
					.std-name,
					.teacher-designation {
						-webkit-text-size-adjust: 100% !important;
						text-size-adjust: 100% !important;
					}
					@media print {
						.std-name {
							font-size: 13px !important;
							line-height: 1.1 !important;
						}
						.teacher-designation {
							font-size: 11px !important;
							line-height: 1.1 !important;
						}
					}
			  	</style>
			  	<?php

			  		$tcrname = isset($_GET['tcname']) ? $_GET['tcname'] : '';
			  		
		  			$query = "SELECT * FROM `ct_teacher`";
			  		$query .= ($tcrname != '') ? " WHERE teacherName LIKE '%$tcrname%'" :'';
			  		$query .= " ORDER BY `teacherName` ASC";
			  		$groupsBy = $wpdb->get_results( $query );
				  	

			  		if($groupsBy){

							foreach ($groupsBy as $value) {
								$design = isset($_GET['design']) ? intval($_GET['design']) : 1;
								$config = getIdDesignConfig($design);
								$bg_url = getIdDesignImageUrl($design);
								
								if (!$bg_url) {
									$bg_url = '';
								}

								$orientation = $config['orientation'] ?? 'portrait';

								if ($orientation === 'landscape') {
									$card_width = "87.2mm";
									$card_height = "54.2mm";
									$inner_margin = "0px";
								} else {
									$card_width = "54.2mm";
									$card_height = "87.2mm";
									$inner_margin = "5px";
								}
									?>

    <div class="card-parent <?= $orientation ?>-mode" style="background-image: url('<?= $bg_url ?>'); width: <?= $card_width ?>; height: <?= $card_height ?>; background-size: cover; background-position: center; position: relative; display: inline-block; margin: <?= $inner_margin ?>; overflow: hidden; <?php if($orientation === 'landscape') echo 'border:1px solid #ddd; background-color:#fff;'; ?>">
        <?php if ($config['design_type'] === 'back'): ?>
            <div style="padding: 6px; text-align: center; margin: 7px 7px; height: 93%; margin-top:10px; position:relative; overflow: hidden;">
                <div style="margin-bottom: 5px;">
                    <p style="margin: 0; font-size: 12px; color: blue; font-weight: 700;">If found please return to</p>
                </div>
                <div style="padding: 10px; text-align: center;">
                    <h3 style="margin: 0; font-size: 14px; color: blue; font-weight: 700;"><?= $s3sRedux['institute_name'] ?></h3>
                    <?php if(!empty($s3sRedux['institute_address'])): ?>
                        <p style="font-size: 11px; margin-top: 5px !important;"><?= $s3sRedux['institute_address'] ?></p>
                    <?php endif; ?>
                    <?php if(!empty($s3sRedux['institute_phone'])): ?>
                        <p style="font-size: 11px; margin-top: 2px !important;">Phone: <?= $s3sRedux['institute_phone'] ?></p>
                    <?php endif; ?>
                </div>
                <div style="width: 200px; margin: 0 auto; position: absolute; bottom: 40px; width: 100%; left: 0; text-align: center;">
					<?php
						$uid = !empty($value->teacherMpo) ? $value->teacherMpo : '2610100';
						echo generateBarcodeSVG($uid);
					?>
				</div>
                <div style="position: absolute; bottom: 5px; right: 7px; text-align: center;">
                    <?php if(!empty($s3sRedux['principalSign']['url'])): ?>
                        <img src="<?= $s3sRedux['principalSign']['url'] ?>" style="height: auto; max-width: 50px; margin: 0 auto; display: block;">
                    <?php endif; ?>
                    <span style="font-size: 10px; color: blue; border-top: 1px solid blue; margin-top: 2px; display: inline-block; padding-top: 2px; min-width: 80px;"><?= $s3sRedux['inst_head_title'] ?></span>
                </div>
            </div>
        <?php elseif ($orientation === 'landscape'): ?>
            <div class="id-card-content">
                <div class="id-modern-panel">
                    <div class="id-modern-header">
                        <?php if (!empty($config['show_logo'])): ?>
                            <img class="id-modern-logo" src="<?= $s3sRedux['instLogo']['url'] ?>" alt="Institute Logo">
                        <?php endif; ?>
                        <?php if (!empty($config['show_inst_name'])): ?>
                            <div class="id-modern-school"><?= $s3sRedux['institute_name'] ?></div>
                        <?php endif; ?>
                    </div>
                    <p class="id-modern-title"><?= htmlspecialchars($value->teacherDesignation) ?></p>
                    <div class="id-modern-body">
                        <?php if (!empty($config['show_image'])): ?>
                            <img class="id-modern-photo" alt="Teacher Image" src="<?php echo ($value->teacherImg != "") ? $value->teacherImg : get_template_directory_uri()."/img/No_Image.jpg" ?>">
                        <?php endif; ?>
                        <div>
                            <?php if (!empty($config['show_name'])): ?>
                                <p class="id-modern-name"><?= $value->teacherName ?></p>
                            <?php endif; ?>
                            <div class="id-modern-list">
                                <?php if (!empty($config['show_id'])): ?><div class="id-modern-item"><b>ID</b><span><?= htmlspecialchars($value->teacherMpo) ?></span></div><?php endif; ?>
                                <?php if (!empty($config['show_mpo_index'])): ?><div class="id-modern-item"><b>MPO</b><span><?= htmlspecialchars($value->teacherMpo) ?></span></div><?php endif; ?>
                                <?php if (!empty($config['show_father'])): ?><div class="id-modern-item"><b>Father</b><span><?= htmlspecialchars($value->teacherFather) ?></span></div><?php endif; ?>
                                <?php if (!empty($config['show_dob'])): ?><div class="id-modern-item"><b>DOB</b><span><?= htmlspecialchars($value->teacherBirth) ?></span></div><?php endif; ?>
                                <?php if (!empty($config['show_phone'])): ?><div class="id-modern-item"><b>Mobile</b><span><?= htmlspecialchars($value->teacherPhone) ?></span></div><?php endif; ?>
                                <?php if (!empty($config['show_blood'])): ?><div class="id-modern-item"><b>Blood</b><span><?= htmlspecialchars($value->teacherBlood) ?></span></div><?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="id-modern-footer">
                        <span class="id-modern-join">
                            <?php if (!empty($config['show_year'])): ?>JOINING: <?= htmlspecialchars($value->teacherJoining) ?><?php endif; ?>
								<?php if (!empty($config['show_mpo_index'])): ?> | MPO: <?= htmlspecialchars($value->teacherMpo) ?><?php endif; ?>
								<?php if (!empty($config['show_retirement'])): ?> | RETIRE: <?= htmlspecialchars($value->retirement_date) ?><?php endif; ?>
								<?php if (!empty($config['show_job_type'])): ?> | JOB TYPE: <?= htmlspecialchars($value->job_type) ?><?php endif; ?>
								<?php if (!empty($config['show_recruitment_authority'])): ?> | RECRUITER: <?= htmlspecialchars($value->recruitment_authority) ?><?php endif; ?>
								<?php if (!empty($config['show_subject'])): ?> | SUBJECT: <?= htmlspecialchars($value->subject) ?><?php endif; ?>
							</span>
                        <?php if (!empty($config['show_signature'])): ?>
                        <div class="id-modern-sign">
                            <img src="<?= $s3sRedux['principalSign']['url'] ?>" alt="Signature">
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
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
                    <img alt="Teacher Image" width="85" height="85" style="<?= $img_style ?>" src="<?php echo ($value->teacherImg != "") ? $value->teacherImg : get_template_directory_uri() . "/img/No_Image.jpg" ?>" loading="eager" decoding="sync">
                </div>
                <?php endif; ?>

				<?php
					$content_margin_top = intval($config['content_margin_top'] ?? 165);
					$line_height = floatval($config['line_height'] ?? 1);
					if ($line_height <= 0) {
						$line_height = 1;
					}
					$has_designation = !empty($config['show_class']);
					$designation_top = $content_margin_top + 20;
					$table_top = $content_margin_top + ($has_designation ? 46 : 30);
				?>
                <?php if (!empty($config['show_name'])): ?>
                <p class="std-name" style="position: absolute; top: <?= $content_margin_top ?>px; color: <?= htmlspecialchars($config['student_name_color'] ?? '#000000') ?>; width: 100%; left: 0; margin: 0 !important; z-index: 2; font-size: 13px; text-transform: uppercase;font-weight: bold;"><?= $value->teacherName ?></p>
                <?php endif; ?>

				<?php if ($has_designation): ?>
				<p class="teacher-designation" style="position: absolute; top: <?= $designation_top ?>px; width: 100%; left: 0; margin: 0 !important; text-align: center; font-size: 11px; font-weight: bold; color: black; text-transform: uppercase; z-index: 2;"><?= htmlspecialchars($value->teacherDesignation) ?></p>
				<?php endif; ?>

				<table class="card-table" style="--table-line-height: <?= $line_height ?>; position: absolute; top: <?= $table_top ?>px; width: 90%; left: 5%; margin: 0 !important; z-index: 1; font-size: 10px; line-height: <?= $line_height ?>;">
                    <?php if (!empty($config['show_id'])): ?>
                    <tr class="info-row">
                        <td class="info-label">ID</td>
                        <td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text"><?= htmlspecialchars($value->teacherMpo) ?></span></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($config['show_mpo_index'])): ?>
                    <tr class="info-row">
                        <td class="info-label">MPO INDEX</td>
                        <td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text"><?= htmlspecialchars($value->teacherMpo) ?></span></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($config['show_roll'])): ?>
                    <tr class="info-row">
                        <td class="info-label">EDUCATION</td>
                        <td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text"><?= htmlspecialchars($value->teacherSQuali) ?></span></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($config['show_father'])): ?>
                    <tr class="info-row">
                        <td class="info-label">FATHER</td>
                        <td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text" style="text-transform: uppercase;"><?= htmlspecialchars($value->teacherFather) ?></span></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($config['show_dob'])): ?>
                    <tr class="info-row">
                        <td class="info-label">DOB</td>
                        <td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text"><?= htmlspecialchars($value->teacherBirth) ?></span></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($config['show_year'])): ?>
                    <tr class="info-row">
                        <td class="info-label">JOINING</td>
                        <td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text"><?= htmlspecialchars($value->teacherJoining) ?></span></td>
                    </tr>
                    <?php if (!empty($config['show_retirement'])): ?>
                    <tr class="info-row">
                        <td class="info-label">RETIRE</td>
                        <td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text"><?= htmlspecialchars($value->retirement_date) ?></span></td>
                    </tr>
                    <?php endif; ?>
					<?php if (!empty($config['show_job_type'])): ?>
                    <tr class="info-row">
                        <td class="info-label">JOB TYPE</td>
                        <td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text"><?= htmlspecialchars($value->job_type) ?></span></td>
                    </tr>
                    <?php endif; ?>
					<?php if (!empty($config['show_recruitment_authority'])): ?>
                    <tr class="info-row">
                        <td class="info-label">RECRUITER</td>
                        <td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text"><?= htmlspecialchars($value->recruitment_authority) ?></span></td>
                    </tr>
                    <?php endif; ?>
					<?php if (!empty($config['show_subject'])): ?>
                    <tr class="info-row">
                        <td class="info-label">SUBJECT</td>
                        <td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text"><?= htmlspecialchars($value->subject) ?></span></td>
                    </tr>
                    <?php endif; ?>
					
                    <?php endif; ?>
                    <?php if (!empty($config['show_blood'])): ?>
                    <tr class="info-row">
                        <td class="info-label">BLOOD</td>
                        <td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text"><?= htmlspecialchars($value->teacherBlood) ?></span></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($config['show_phone'])): ?>
                    <tr class="info-row">
                        <td class="info-label">PHONE</td>
                        <td class="info-value"><span class="colon">:&nbsp;</span><span class="value-text"><?= htmlspecialchars($value->teacherPhone) ?></span></td>
                    </tr>
                    <?php endif; ?>
                </table>

                <?php if (!empty($config['show_signature'])): ?>
                    <div style="text-align: center; position: absolute; bottom: 5px; right: 7px; z-index: 3;">
                        <img align="Principal Signature" style="display: block; margin: 0 auto; height: auto; max-width: 50px;" src="<?= $s3sRedux['principalSign']['url'] ?>">
                        <span style="font-size: 10px; color: blue; border-top: 1px solid blue; margin-top: 2px; display: inline-block; padding-top: 2px; min-width: 80px;"><?= $s3sRedux['inst_head_title'] ?></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

									<?php
							}

						}else{
							echo "<h3 class='text-center'>No Student Found</h3>";
						}
			  	?>		  	

			  </div>
		  </div>

		<?php } ?>

	</div>
</div>

<?php if ( ! is_admin() ) { ?>
				</div>
			</div>
		</div>
	</div>
</div>
<?php get_footer(); } ?>

<script type="text/javascript">
	(function($) {
		$('#resultClass').change(function() {
	    var idCardActionsUrl = '<?= get_template_directory_uri() . "/adminPages/functions/id-card-actions.php" ?>';

	    $.ajax({
	      url: idCardActionsUrl,
	      method: "POST",
	      data: { class : $(this).val(), type : 'getYears' },
	      dataType: "html"
	    }).done(function( msg ) {
	      $( "#resultYear" ).html( msg );
	      $( "#resultYear" ).prop('disabled', false);
	    });

	    $.ajax({
	      url: idCardActionsUrl,
	      method: "POST",
	      data: { class : $(this).val(), type : 'getSection' },
	      dataType: "html"
	    }).done(function( msg ) {
	      $( "#resultSection" ).html( msg );
	      $( "#resultSection" ).prop('disabled', false);
	    });
	  });
  })( jQuery );

	function print(divId) {
    var printContents = document.getElementById(divId).innerHTML;
    w = window.open();
    w.document.write(printContents);
    w.document.write('<scr' + 'ipt type="text/javascript">' + 'window.onload = function() { window.print(); window.close(); };' + '</sc' + 'ript>');
    w.document.close(); // necessary for IE >= 10
    w.focus(); // necessary for IE >= 10
    return true;
  }

</script>
