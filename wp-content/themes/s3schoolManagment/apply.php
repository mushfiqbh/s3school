<?php

/*
 * Template Name: Student Inseart
 */

get_header();

/*=================
	Add Student
=================*/
if (isset($_POST['addStudent'])) {
	// Backward compatibility: accept legacy stdCurntYear as stdAdmitYear
	if (!isset($_POST['stdAdmitYear']) && isset($_POST['stdCurntYear'])) {
		$_POST['stdAdmitYear'] = $_POST['stdCurntYear'];
	}
	// Map submitted fields to ct_online_application schema
	$insert = $wpdb->insert(
		'ct_online_application',
		array(
			// Required not-null fields (ensure defaults if missing)
			'stdName' => isset($_POST['stdName']) ? $_POST['stdName'] : '',
			'stdNameBangla' => isset($_POST['stdNameBangla']) ? $_POST['stdNameBangla'] : '',
			'stdGender' => !empty($_POST['stdGender']) ? $_POST['stdGender'] : (function() {
				$message = 'Gender is required';
				return '';
			})(),
			'stdBldGrp' => isset($_POST['stdBldGrp']) ? $_POST['stdBldGrp'] : '',
			'facilities' => isset($_POST['facilities']) ? $_POST['facilities'] : '',
			'stdImg' => isset($_POST['stdImg']) ? $_POST['stdImg'] : '',
			'stdFather' => isset($_POST['stdFather']) ? $_POST['stdFather'] : '',
			'stdFatherProf' => isset($_POST['stdFatherProf']) ? $_POST['stdFatherProf'] : '',
			'stdMother' => isset($_POST['stdMother']) ? $_POST['stdMother'] : '',
			'motherLate' => 0,
			'stdMotherProf' => isset($_POST['stdMotherProf']) ? $_POST['stdMotherProf'] : '',
			'stdParentIncome' => isset($_POST['stdParentIncome']) ? $_POST['stdParentIncome'] : 0,
			'stdlocalGuardian' => isset($_POST['stdlocalGuardian']) ? $_POST['stdlocalGuardian'] : '',
			'stdGuardianNID' => isset($_POST['stdGuardianNID']) ? $_POST['stdGuardianNID'] : 0,
			'stdPhone' => isset($_POST['stdPhone']) ? $_POST['stdPhone'] : '',
			'stdPermanent' => isset($_POST['stdPermanent']) ? $_POST['stdPermanent'] : '',
			'stdPresent' => isset($_POST['stdPresent']) ? $_POST['stdPresent'] : '',
			'stdBrith' => isset($_POST['stdBrith']) ? $_POST['stdBrith'] : '',
			'stdNationality' => isset($_POST['stdNationality']) ? $_POST['stdNationality'] : '',
			'stdReligion' => isset($_POST['stdReligion']) ? $_POST['stdReligion'] : '',
			'stdAdmitClass' => isset($_POST['stdAdmitClass']) ? $_POST['stdAdmitClass'] : 0,
			'stdAdmitYear' => isset($_POST['stdAdmitYear']) ? $_POST['stdAdmitYear'] : '',
			// Optional/nullable fields
			'stdSection' => isset($_POST['stdSection']) ? $_POST['stdSection'] : null,
			'stdRoll' => isset($_POST['stdRoll']) ? $_POST['stdRoll'] : null,
			'stdTcNumber' => isset($_POST['stdTcNumber']) ? $_POST['stdTcNumber'] : null,
			'sscRoll' => isset($_POST['sscRoll']) ? $_POST['sscRoll'] : null,
			'sscReg' => isset($_POST['sscReg']) ? $_POST['sscReg'] : null,
			'stdPrevSchool' => isset($_POST['stdPrevSchool']) ? $_POST['stdPrevSchool'] : null,
			'stdGPA' => isset($_POST['stdGPA']) ? $_POST['stdGPA'] : null,
			'stdIntellectual' => isset($_POST['stdIntellectual']) ? $_POST['stdIntellectual'] : null,
			'stdScholarsClass' => isset($_POST['stdScholarsClass']) ? $_POST['stdScholarsClass'] : null,
			'stdScholarsYear' => isset($_POST['stdScholarsYear']) ? $_POST['stdScholarsYear'] : null,
			'stdScholarsMemo' => isset($_POST['stdScholarsMemo']) ? $_POST['stdScholarsMemo'] : null,
			// Financial and note fields (nullable)
			'paymentPaid' => isset($_POST['paymentPaid']) ? $_POST['paymentPaid'] : null,
			'paymentDue' => isset($_POST['paymentDue']) ? $_POST['paymentDue'] : null,
			'stdNote' => isset($_POST['stdNote']) ? $_POST['stdNote'] : null,
		)
	);

	// Robust error reporting
	if ($insert === false) {
		error_log('Online application insert error: ' . $wpdb->last_error);
		$message = array('status' => 'faild', 'message' => 'Insert failed: ' . esc_html($wpdb->last_error));
	} else {
		$application_id = $wpdb->insert_id;
		$message = array('status' => 'success', 'message' => 'Application submitted successfully');
		// Redirect to slip view
		wp_redirect(add_query_arg(array('view_slip' => 'true', 'app_id' => $application_id), get_permalink()));
		exit;
	}
}

/*=================
	Update Student
=================*/
if (isset($_POST['updateSubject'])) {
	$update = $wpdb->update(
		'ct_subject',
		array(
			'stdName' => $_POST['stdName'],
			'stdRoll' => $_POST['stdRoll'],
			'stdImg' => $_POST['stdImg'],
			'stdFather' => $_POST['stdFather'],
			'stdFatherProf' => $_POST['stdFatherProf'],
			'stdMother' => $_POST['stdMother'],
			'stdMotherProf' => $_POST['stdMotherProf'],
			'stdParentIncome' => $_POST['stdParentIncome'],
			'stdlocalGuardian' => $_POST['stdlocalGuardian'],
			'stdPhone' => $_POST['stdPhone'],
			'stdPermanent' => $_POST['stdPermanent'],
			'stdPresent' => $_POST['stdPresent'],
			'stdBrith' => $_POST['stdBrith'],
			'stdNationality' => $_POST['stdNationality'],
			'stdReligion' => $_POST['stdReligion'],
			'stdAdmitClass' => $_POST['stdAdmitClass'],
			'stdCurntYear' => $_POST['stdCurntYear'],
			'stdSection' => isset($_POST['stdSection']) ? $_POST['stdSection'] : 0,
			'stdOptionals' => isset($_POST['stdOptionals']) ? json_encode($_POST['stdOptionals']) : 0,
			'stdTcNumber' => $_POST['stdTcNumber'],
			'stdPrevSchool' => $_POST['stdPrevSchool'],
			'stdGPA' => $_POST['stdGPA'],
			'stdIntellectual' => $_POST['stdIntellectual'],
			'stdScholarsClass' => $_POST['stdScholarsClass'],
			'stdScholarsYear' => $_POST['stdScholarsYear'],
			'stdScholarsMemo' => $_POST['stdScholarsMemo'],
			'paymentPaid' => $_POST['paymentPaid'],
			'paymentDue' => $_POST['paymentDue'],
			'stdNote' => $_POST['stdNote'],
			'stdUpdatedAt' => current_time('mysql')
		),
		array('studentid' => $_POST['id'])
	);

	if ($update) {
		$message = array('status' => 'success', 'message' => 'Successfully updated');
	} else {
		$message = array('status' => 'faild', 'message' => 'Something wrong please try again');
	}
}

/*=================
	Delete Subject
==================*/
if (isset($_POST['deleteStudent'])) {
	$delete = $wpdb->update(
		'ct_student',
		array(
			'stdStatus' => 0
		),
		array('studentid' => $_POST['id'])
	);

	if ($delete) {
		$message = array('status' => 'success', 'message' => 'Successfully Deleted');
	} else {
		$message = array('status' => 'faild', 'message' => 'Something wrong please try again');
	}
}

/*===============
	Edit Subject
================*/
$editid = 0;
if (isset($_POST['editStudent'])) {
	$editid = $_POST['id'];
	$edit = $wpdb->get_results("SELECT * FROM ct_student WHERE studentid = $editid");
	$edit = $edit[0];
}

/*===============
	View Application Slip
================*/

?>

<p id="theSiteURL" class="hidden"><?= get_template_directory_uri() ?></p>

<div class="container maxAdminpages">

	<!-- Slips List -->
	<div id="slipList"></div>

	<?php
	if (isset($message)) {
		?>
				<div class="messageDiv">
					<div class="alert <?= ($message['status'] == 'success') ? 'alert-success' : 'alert-danger'; ?>">
						<?= $message['message'] ?>
					</div>
				</div>
			<?php
	}
	?>

	<h2>
		Student
		<?php if (!isset($_GET['option'])) { ?>
			<a class="pull-right btn btn-success" href="<?= home_url() ?>/add-student?page=student&option=add">
				<span class="dashicons dashicons-plus"></span> Add Student
			</a>
		<?php } else { ?>
			<a class="pull-right btn btn-success" href="<?= home_url() ?>/add-student?page=student">
				<span class="dashicons dashicons-groups"></span> Students
			</a>
		<?php } ?>
	</h2><br>



	<!-- 
		Add Window
	 -->
		<form accept="" method="POST" class="applyForm">
			<div class="panel panel-info">
			  <div class="panel-heading">Administration </div>
			  <div class="panel-body">
					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								<label>Payment Paid</label>
								<input min="0" step="5" class="form-control" type="text" name="paymentPaid" placeholder="Payment Paid">
							</div>

							<div class="form-group">
								<label>Payment Due</label>
								<input min="0" step="5" class="form-control" type="text" name="paymentDue" placeholder="Payment Due">
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label>Note</label>
								<textarea class="form-control" rows="4" name="stdNote" placeholder="Student Note"></textarea>
							</div>
						</div>
					</div>
			  </div>
			</div>

			<div class="panel panel-info">
			  <div class="panel-heading">Personal and educational information</div>
			  <div class="panel-body">
					<div class="row">
						<div class="col-md-6">

							<div class="form-group">
								<label>Student Name</label>
								<input class="form-control" type="text" name="stdName" placeholder="Student Name" required>
							</div>

							<div class="form-group">
								<label>Student Name (Bangla)</label>
								<input class="form-control" type="text" name="stdNameBangla" placeholder="ছাত্রের নাম (বাংলা)">
							</div>

							<div class="form-group">
								<label>Gender</label>
								
								<?php
								// Get gender from POST or application data
								$gender = $_POST['stdGender'] ?? ($application->stdGender ?? '1'); // Default to Boy (1) if not set

								// Convert text values to numbers if needed
								if (!is_numeric($gender)) {
									$genderMap = ['Girl' => '0', 'Boy' => '1', 'Other' => '2'];
									$gender = $genderMap[$gender] ?? '1'; // Default to Boy (1) if not found
								}
								?>
								<select class="form-control" name="stdGender" required>
									<option value="1" <?= $gender == '1' ? 'selected' : '' ?>>Boy</option>
									<option value="0" <?= $gender == '0' ? 'selected' : '' ?>>Girl</option>
									<option value="2" <?= $gender == '2' ? 'selected' : '' ?>>Other</option>
								</select>
							</div>
							
							<div class="form-group">
								<label>Blood Group</label>
								<select class="form-control" name="stdBldGrp" required>
									<option disabled selected>Select Blood Group</option>
									<option value="A+">A+</option>
									<option value="A-">A-</option>
									<option value="B+">B+</option>
									<option value="B-">B-</option>
									<option value="AB+">AB+</option>
									<option value="AB-">AB-</option>
									<option value="O+">O+</option>
									<option value="O-">O-</option>
								</select>
							</div>

							<div class="form-group">
								<label>Student Photo</label><br>
								<div class="mediaUploadHolder">
					    		<button type="button" class="mediaUploader">Upload</button>
					    		<span>
					    			<?php echo (isset($edit)) ? "<img height='40' src='" . $edit->teacherImg . "'>" : ''; ?>
					    		</span>

									<input class="hidden teacherImg" type="text" name="stdImg" value="<?= isset($edit) ? $edit->stdImg : ''; ?>">
				    		</div>
							</div>

							<div class="form-group">
								<label>Date Of Birth</label>
								<input class="form-control" type="date" name="stdBrith" placeholder="Date Of Birth" required>
							</div>

							<div class="form-group">
								<label>Father Name</label>
								<input class="form-control" type="text" name="stdFather" placeholder="Father Name" required>
							</div>

							<div class="form-group">
								<label>Father Profession</label>
								<input class="form-control" type="text" name="stdFatherProf" placeholder="Father Profession">
							</div>

							<div class="form-group">
								<label>Mother Name</label>
								<input class="form-control" type="text" name="stdMother" placeholder="Mother Name" required>
							</div>

							<div class="form-group">
								<label>Mother Profession</label>
								<input class="form-control" type="text" name="stdMotherProf" placeholder="Mother Profession">
							</div>

							<div class="form-group">
								<label>Parental Annual Income</label>
								<input class="form-control" type="text" name="stdParentIncome" placeholder="Parental annual income">
							</div>

							<div class="form-group">
								<label>Local Guardian Name</label>
								<input class="form-control" type="text" name="stdlocalGuardian" placeholder="Local Guardian Name">
							</div>

							<div class="form-group">
								<label>Guardian NID</label>
								<input class="form-control" type="text" name="stdGuardianNID" placeholder="Guardian NID Number">
							</div>

							<div class="form-group">
								<label>Phone Number</label>
								<input class="form-control" type="text" name="stdPhone" placeholder="Phone Number">
							</div>

							<div class="form-group">
								<label>Permanent Address</label>
								<input class="form-control" type="text" name="stdPermanent" placeholder="Permanent Address">
							</div>


						</div>

						<div class="col-md-6">

							<div class="form-group">
								<label>Present Address</label>
								<input class="form-control" type="text" name="stdPresent" placeholder="Present Address">
							</div>

							<div class="form-group">
								<label>Nationality</label>
								<input class="form-control" type="text" name="stdNationality" placeholder="Nationality">
							</div>

							<div class="form-group">
								<label>Facilities</label>
								<select class="form-control" name="facilities">
									<option value="">None</option>
									<option value="Disabled">Disabled</option>
									<option value="FreedomFighterQuota">Freedom Fighter Quota</option>
									<option value="Other">Other</option>
								</select>
							</div>

							<div class="form-group">
								<label>Religion</label>
								<select class="form-control" name="stdReligion">
									<option disabled selected>Select Religion</option>
									<option value="Muslim">Muslim</option>
									<option value="Hinduism">Hinduism</option>
									<option value="Buddist">Buddist</option>
									<option value="Christian">Christian</option>
									<option value="other">Other</option>
								</select>
							</div>

							<div class="row">
								<div class="col-md-6">
									<div class="form-group">
										<label>Class*</label>
										<select id="admitClass" class="form-control" name="stdAdmitClass" required>
											<?php
											$classes = $wpdb->get_results("SELECT classid,className FROM ct_class ORDER BY className ASC");
											echo "<option disabled selected>Select a Class..</option>";
											
											foreach ($classes as $class) {
												?>
												<option value="<?= $class->classid ?>">
													<?= $class->className ?>
												</option>
												<?php
											}
											?>
                                        </select>
                                    </div>
								</div>

								<div class="col-md-6">
									<div class="form-group">
										<label>Admission Year*</label>
										<select id="admitYear" class="form-control" name="stdAdmitYear" required>
											<option disabled selected>Select Year</option>
												<?php
												for ($i = date('Y'); $i >= 2000; $i--) {
													?>
													<option value="<?= $i; ?>"><?= $i; ?></option>
													<?php
												}
												?>
										</select>
									</div>
								</div>
							</div>

							<div class="row">
								<div class="col-md-4">
									<div class="form-group">
										<label>Section (Optional)</label>
										<select id="sectionSelect" class="form-control sectionSelect" name="stdSection" disabled>
											<option disabled selected>Select a Class First</option>
										</select>
									</div>
								</div>

								<div class="col-md-4">
									<div class="form-group">
										<label>Group (Optional)</label>
										<select class="form-control groupSelect" name="stdGroup">
											<option value="">Select Group</option>
											<?php
												$groups = $wpdb->get_results("SELECT * FROM ct_group");
												foreach ($groups as $group) {
													?>
													<option value='<?= $group->groupId ?>'>
														<?= $group->groupName ?>
													</option>
													<?php
												}
											?>
										</select>
									</div>
								</div>

								<div class="col-md-4">
									<div class="form-group">
										<label>Roll (Optional)</label>
										<input class="form-control" type="text" name="stdRoll" placeholder="Roll">
									</div>
								</div>
							</div>

							<!-- If Optional (Value will come by Ajax) -->
							<div class="form-group optionalSubDiv">
								
							</div>
							

							<div class="form-group">
								<label>Previous School Name</label>
								<input class="form-control" type="text" name="stdPrevSchool" placeholder="Previous School Name">
							</div>
							<div class="form-group">
								<label>TC Number</label>
								<input class="form-control" type="text" name="stdTcNumber" placeholder="TC Number">
							</div>
							<hr>
							<h4><strong>Past Annual / Public Examination Details</strong></h4>
							<div class="row">
								<div class="col-md-6">
									<div class="form-group">
										<label>Number / GPA</label>
										<input class="form-control" type="text" name="stdGPA">
									</div>
								</div>
								<div class="col-md-6">
									<div class="form-group">
										<label>Intellectual Position</label>
										<input class="form-control" type="text" name="stdIntellectual">
									</div>
								</div>
							</div>
							<hr>
							<h4><strong>If you get government scholarship</strong></h4>
							<div class="row">
								<div class="col-md-4">
									<div class="form-group">
										<label>In which class</label>
										<input class="form-control" type="text" name="stdScholarsClass">
									</div>
								</div>
								<div class="col-md-4">
									<div class="form-group">
										<label>Year</label>
										<input class="form-control" type="text" name="stdScholarsYear">
									</div>
								</div>
								<div class="col-md-4">
									<div class="form-group">
										<label><small>Memorandum No</small></label>
										<input class="form-control" type="text" name="stdScholarsMemo">
									</div>
								</div>

							</div>

						</div>
					</div>

					<div class="form-group">
						<input class="btn btn-secondary pull-right" type="submit" name="addStudent" value="Add">
					</div>

			  </div>
			</div>

		</form>
	

</div>


<!-- Delete Modal-->
<div id="deleteModal" class="modal fade" role="dialog">
  <div class="modal-dialog modal-sm">

    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Delete Data</h4>
      </div>
      <div class="modal-body">
        <p class="text-danger">You can't recover the data after delete.</p>
      </div>
      <div class="modal-footer">
	      <form action="" method="POST">
	      	<input type="hidden" name="id" class="id">
        	<button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
        	<button type="submit" class="btn btn-danger" name="deleteTeacher">Delete</button>
	      </form>
      </div>
    </div>

  </div>
</div>

<script type="text/javascript">
  (function($) {
    $('#admitClass').change(function() {
      var $siteUrl = '<?= get_template_directory_uri() ?>';

      $.ajax({
        url: $siteUrl+"/inc/ajaxAction.php",
        method: "POST",
        data: { class : $(this).val(), type : 'getSection' },
        dataType: "html"
      }).done(function( msg ) {
        $( "#sectionSelect" ).html( msg );
        $( "#sectionSelect" ).prop('disabled', false);
      });
    });

		// Slip LocalStorage Management
		function getSlips() {
			let slips = localStorage.getItem('studentSlips');
			return slips ? JSON.parse(slips) : [];
		}

		function saveSlip(slip) {
			let slips = getSlips();
			slips.push(slip);
			localStorage.setItem('studentSlips', JSON.stringify(slips));
		}

		function deleteSlip(idx) {
			let slips = getSlips();
			slips.splice(idx, 1);
			localStorage.setItem('studentSlips', JSON.stringify(slips));
			renderSlips();
		}

			function printSlip(idx) {
				let slips = getSlips();
				let slip = slips[idx];
				let win = window.open('', '', 'width=900,height=1000');
				win.document.write('<html><head><title>Print Application Slip</title>');
				win.document.write('<link href="https://fonts.googleapis.com/css?family=Quicksand:400,600,700" rel="stylesheet">');
				win.document.write('<style>body{font-family:Quicksand,Arial,sans-serif;background:#f5f5f5;padding:20px;} .slip-main{max-width:900px;margin:0 auto;background:#fff;border:2px solid #2563eb;box-shadow:0 0 12px rgba(37,99,235,0.08);padding:0;} .slip-header{text-align:center;border-bottom:2px solid #2563eb;padding:24px 0 16px 0;} .slip-logo{max-width:80px;max-height:80px;margin-bottom:10px;} .slip-title{font-size:22px;font-weight:700;color:#2563eb;margin-top:10px;letter-spacing:1px;} .slip-inst-name{font-size:26px;font-weight:700;color:#1e293b;margin-bottom:6px;text-transform:uppercase;} .slip-inst-address{font-size:14px;color:#64748b;margin-bottom:5px;} .slip-ref{font-size:15px;color:#78350f;background:#fef3c7;border-left:4px solid #f59e0b;padding:8px 18px;margin:18px 0;border-radius:4px;display:inline-block;} .slip-part-label{background:#2563eb;color:#fff;padding:6px 18px;font-weight:600;font-size:14px;border-radius:4px;margin-bottom:18px;display:inline-block;letter-spacing:1px;} .slip-table{width:100%;border-collapse:collapse;margin-bottom:18px;} .slip-table th,.slip-table td{border:1px solid #c5d5e4;padding:8px 12px;font-size:15px;} .slip-table th{background:#f1f5f9;font-weight:700;color:#2563eb;} .slip-table td{color:#1e293b;} .slip-photo{float:right;width:110px;height:130px;border:2px solid #2563eb;border-radius:8px;background:#f1f5f9;margin-left:18px;margin-bottom:10px;overflow:hidden;} .slip-photo img{width:100%;height:100%;object-fit:cover;} .signature-area{display:flex;justify-content:space-between;margin-top:80px;} .signature-box{text-align:center;flex:0 0 200px;} .signature-line{border-top:2px solid #1e293b;margin-bottom:8px;padding-top:60px;} .signature-label{font-size:13px;font-weight:600;color:#475569;} .page-break{page-break-after:always;} @media print{body{background:#fff;padding:0;} .slip-main{box-shadow:none !important;border:2px solid #2563eb;} .print-btn{display:none !important;}} </style>');
				win.document.write('</head><body>');
				win.document.write('<div class="slip-main">');
				// Applicant's Copy
				win.document.write('<div style="padding:32px;">');
				win.document.write('<div class="slip-header">');
				win.document.write('<div class="slip-logo"></div>');
				win.document.write('<div class="slip-inst-name">School Name</div>');
				win.document.write('<div class="slip-inst-address"></div>');
				win.document.write('<div class="slip-title">Admission Application Slip</div>');
				win.document.write('</div>');
				win.document.write('<div class="slip-part-label">Applicant\'s Copy</div>');
				win.document.write('<div class="slip-ref">Application Reference No: <strong>APP-' + String(idx+1).padStart(6,'0') + '</strong></div>');
				if (slip.stdImg) win.document.write('<div class="slip-photo"><img src="'+slip.stdImg+'" alt="Student Photo"></div>');
				win.document.write('<table class="slip-table"><tr><th colspan="2">Personal Information</th></tr>');
				win.document.write('<tr><td>Student Name (English)</td><td>'+ (slip.stdName||'') +'</td></tr>');
				if (slip.stdNameBangla) win.document.write('<tr><td>Student Name (Bangla)</td><td>'+slip.stdNameBangla+'</td></tr>');
				win.document.write('<tr><td>Gender</td><td>'+ (slip.stdGender||'') +'</td></tr>');
				win.document.write('<tr><td>Date of Birth</td><td>'+ (slip.stdBrith||'') +'</td></tr>');
				win.document.write('<tr><td>Religion</td><td>'+ (slip.stdReligion||'') +'</td></tr>');
				if (slip.facilities) win.document.write('<tr><td>Facilities</td><td>'+slip.facilities+'</td></tr>');
				win.document.write('</table>');
				win.document.write('<table class="slip-table"><tr><th colspan="2">Academic Information</th></tr>');
				win.document.write('<tr><td>Applying for Class</td><td>'+ (slip.stdAdmitClass||'') +'</td></tr>');
				win.document.write('<tr><td>Admission Year</td><td>'+ (slip.stdAdmitYear||'') +'</td></tr>');
				if (slip.stdPrevSchool) win.document.write('<tr><td>Previous School</td><td>'+slip.stdPrevSchool+'</td></tr>');
				if (slip.stdGPA) win.document.write('<tr><td>Previous GPA</td><td>'+slip.stdGPA+'</td></tr>');
				if (slip.stdTcNumber) win.document.write('<tr><td>TC Number</td><td>'+slip.stdTcNumber+'</td></tr>');
				win.document.write('</table>');
				win.document.write('<table class="slip-table"><tr><th colspan="2">Guardian Information</th></tr>');
				win.document.write('<tr><td>Father\'s Name</td><td>'+ (slip.stdFather||'') +'</td></tr>');
				win.document.write('<tr><td>Mother\'s Name</td><td>'+ (slip.stdMother||'') +'</td></tr>');
				if (slip.stdlocalGuardian) win.document.write('<tr><td>Local Guardian</td><td>'+slip.stdlocalGuardian+'</td></tr>');
				if (slip.stdGuardianNID) win.document.write('<tr><td>Guardian NID</td><td>'+slip.stdGuardianNID+'</td></tr>');
				win.document.write('<tr><td>Contact Phone</td><td>'+ (slip.stdPhone||'') +'</td></tr>');
				win.document.write('</table>');
				win.document.write('<table class="slip-table"><tr><th colspan="2">Address</th></tr>');
				win.document.write('<tr><td>Present Address</td><td>'+ (slip.stdPresent||'') +'</td></tr>');
				win.document.write('<tr><td>Permanent Address</td><td>'+ (slip.stdPermanent||'') +'</td></tr>');
				win.document.write('</table>');
				if (slip.stdNote) win.document.write('<table class="slip-table"><tr><th>Additional Notes</th></tr><tr><td>'+slip.stdNote+'</td></tr></table>');
				if (slip.paymentPaid || slip.paymentDue) {
					win.document.write('<table class="slip-table"><tr><th colspan="2">Payment Information</th></tr>');
					if (slip.paymentPaid) win.document.write('<tr><td>Payment Paid</td><td>'+slip.paymentPaid+'</td></tr>');
					if (slip.paymentDue) win.document.write('<tr><td>Payment Due</td><td>'+slip.paymentDue+'</td></tr>');
					win.document.write('</table>');
				}
				win.document.write('<div class="signature-area"><div class="signature-box"><div class="signature-label">Applicant/Guardian Signature</div></div><div class="signature-box"><div class="signature-label">Date: '+(new Date()).toLocaleDateString()+'</div></div></div>');
				win.document.write('</div>');
				win.document.write('<div class="page-break"></div>');
				// Institute's Copy
				win.document.write('<div style="padding:32px;">');
				win.document.write('<div class="slip-header">');
				win.document.write('<div class="slip-logo"></div>');
				win.document.write('<div class="slip-inst-name">School Name</div>');
				win.document.write('<div class="slip-inst-address"></div>');
				win.document.write('<div class="slip-title">Admission Application Slip</div>');
				win.document.write('</div>');
				win.document.write('<div class="slip-part-label">Institute\'s Copy</div>');
				win.document.write('<div class="slip-ref">Application Reference No: <strong>APP-' + String(idx+1).padStart(6,'0') + '</strong></div>');
				if (slip.stdImg) win.document.write('<div class="slip-photo"><img src="'+slip.stdImg+'" alt="Student Photo"></div>');
				win.document.write('<table class="slip-table"><tr><th colspan="2">Personal Information</th></tr>');
				win.document.write('<tr><td>Student Name (English)</td><td>'+ (slip.stdName||'') +'</td></tr>');
				if (slip.stdNameBangla) win.document.write('<tr><td>Student Name (Bangla)</td><td>'+slip.stdNameBangla+'</td></tr>');
				win.document.write('<tr><td>Gender</td><td>'+ (slip.stdGender||'') +'</td></tr>');
				win.document.write('<tr><td>Date of Birth</td><td>'+ (slip.stdBrith||'') +'</td></tr>');
				win.document.write('<tr><td>Religion</td><td>'+ (slip.stdReligion||'') +'</td></tr>');
				if (slip.facilities) win.document.write('<tr><td>Facilities</td><td>'+slip.facilities+'</td></tr>');
				win.document.write('</table>');
				win.document.write('<table class="slip-table"><tr><th colspan="2">Academic Information</th></tr>');
				win.document.write('<tr><td>Applying for Class</td><td>'+ (slip.stdAdmitClass||'') +'</td></tr>');
				win.document.write('<tr><td>Admission Year</td><td>'+ (slip.stdAdmitYear||'') +'</td></tr>');
				if (slip.stdPrevSchool) win.document.write('<tr><td>Previous School</td><td>'+slip.stdPrevSchool+'</td></tr>');
				if (slip.stdGPA) win.document.write('<tr><td>Previous GPA</td><td>'+slip.stdGPA+'</td></tr>');
				if (slip.stdTcNumber) win.document.write('<tr><td>TC Number</td><td>'+slip.stdTcNumber+'</td></tr>');
				win.document.write('</table>');
				win.document.write('<table class="slip-table"><tr><th colspan="2">Guardian Information</th></tr>');
				win.document.write('<tr><td>Father\'s Name</td><td>'+ (slip.stdFather||'') +'</td></tr>');
				win.document.write('<tr><td>Mother\'s Name</td><td>'+ (slip.stdMother||'') +'</td></tr>');
				if (slip.stdlocalGuardian) win.document.write('<tr><td>Local Guardian</td><td>'+slip.stdlocalGuardian+'</td></tr>');
				if (slip.stdGuardianNID) win.document.write('<tr><td>Guardian NID</td><td>'+slip.stdGuardianNID+'</td></tr>');
				win.document.write('<tr><td>Contact Phone</td><td>'+ (slip.stdPhone||'') +'</td></tr>');
				win.document.write('</table>');
				win.document.write('<table class="slip-table"><tr><th colspan="2">Address</th></tr>');
				win.document.write('<tr><td>Present Address</td><td>'+ (slip.stdPresent||'') +'</td></tr>');
				win.document.write('<tr><td>Permanent Address</td><td>'+ (slip.stdPermanent||'') +'</td></tr>');
				win.document.write('</table>');
				if (slip.stdNote) win.document.write('<table class="slip-table"><tr><th>Additional Notes</th></tr><tr><td>'+slip.stdNote+'</td></tr></table>');
				if (slip.paymentPaid || slip.paymentDue) {
					win.document.write('<table class="slip-table"><tr><th colspan="2">Payment Information</th></tr>');
					if (slip.paymentPaid) win.document.write('<tr><td>Payment Paid</td><td>'+slip.paymentPaid+'</td></tr>');
					if (slip.paymentDue) win.document.write('<tr><td>Payment Due</td><td>'+slip.paymentDue+'</td></tr>');
					win.document.write('</table>');
				}
				win.document.write('<div class="signature-area"><div class="signature-box"><div class="signature-label">Received By</div></div><div class="signature-box"><div class="signature-label">Authorized Signature</div></div></div>');
				win.document.write('</div>');
				win.document.write('</div>');
				win.document.write('</body></html>');
				win.document.close();
				win.print();
			}

		function renderSlips() {
			let slips = getSlips();
			let html = '';
			if (slips.length === 0) {
				html = '<div class="alert alert-info">No saved slips found.</div>';
			} else {
				html = '<h3>Saved Slips</h3>';
				slips.forEach(function(slip, idx) {
					html += '<div class="panel panel-default" style="margin-bottom:10px;">';
					html += '<div class="panel-heading">Slip #' + (idx+1) + '</div>';
					html += '<div class="panel-body">';
					html += '<strong>Name:</strong> ' + (slip.stdName || '') + '<br>';
					html += '<strong>Class:</strong> ' + (slip.stdAdmitClass || '') + '<br>';
					html += '<strong>Year:</strong> ' + (slip.stdAdmitYear || '') + '<br>';
					html += '<strong>Phone:</strong> ' + (slip.stdPhone || '') + '<br>';
					html += '<strong>Note:</strong> ' + (slip.stdNote || '') + '<br>';
					html += '<button class="btn btn-primary btn-sm" onclick="printSlip(' + idx + ')">Print</button> ';
					html += '<button class="btn btn-danger btn-sm" onclick="deleteSlip(' + idx + ')">Delete</button>';
					html += '</div></div>';
				});
			}
			$('#slipList').html(html);
		}

		// Expose print/delete globally for inline onclick
		window.printSlip = printSlip;
		window.deleteSlip = deleteSlip;

		// On c:\xampp\htdocs\ziisc\wp-content\themes\s3schoolManagment\templatesform submit, save slip data
		$('.applyForm').on('submit', function(e) {
			// Only save to localStorage if not submitting for backend
			if (!e.originalEvent.submitter || e.originalEvent.submitter.name !== 'addStudent') return;
			var formData = $(this).serializeArray();
			var slip = {};
			formData.forEach(function(item) {
				slip[item.name] = item.value;
			});
			saveSlip(slip);
			renderSlips();
			// Optionally, prevent actual submit for demo:
			// e.preventDefault();
		});

		// Initial render
		$(document).ready(function() {
			renderSlips();
		});
  })( jQuery );
</script>

<?php get_footer(); ?>