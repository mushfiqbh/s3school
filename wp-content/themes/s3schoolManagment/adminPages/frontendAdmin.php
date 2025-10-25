<?php

/**
 * Template Name: Frontend Admin
 */
get_header();

$haveAccess = false;
$acgpaGenarate = false;
$acgpapromotion = false;
$aprogressReport = false;
if (isset(wp_get_current_user()->roles[0]) || current_user_can('administrator')) {
	if (wp_get_current_user()->roles[0] == 'um_headmaster'  || wp_get_current_user()->roles[0] == 'um_accounts' || wp_get_current_user()->roles[0] == 'um_accounts-user'  || current_user_can('administrator')) {
		$haveAccess = true;
	}
}

$access = $wpdb->get_results("SELECT * FROM ct_access WHERE acid = 1");
if (sizeof($access) > 0) {
	$access = $access[0];
	foreach ($access as $key => $value) {
		$$key = $value;
	}
}

if (isset($_POST['downloadDatabase'])) {
	EXPORT_DATABASE();
}
?>

<style type="text/css">
	#user-submitted-title,
	#user-submitted-category,
	.usp-clone {
		width: 100%;
		margin-bottom: 15px;
		border-radius: 3px;
		border: 2px solid #ccc;
		padding: 5px;
	}

	#usp-submit {
		text-align: right;
	}

	#user-submitted-post {
		padding: 8px 25px;
		font-weight: bold;
		border-radius: 5px;
		border: 0;
		background: #337ab7;
		color: #fff;
	}
</style>


<div class="b-layer-main">
	<div class="">
		<div class="container">
			<div class="wow slideInLeft fronendAdmin">
				<style>
					.btn-primary {
						z-index: 1;
					}
				</style>
				<?php if (wp_get_current_user()->roles[0] == 'um_teachers') { ?>
					<div class="panel panel-default">
						<div class="panel-heading">Dashboard</div>
						<div class="panel-body">
							<div class="row">
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-student'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Student</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-attendance'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Attendance</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-result'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Result</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>


							</div>
						</div>
					</div>
			</div>

		<?php } else { ?>
			<div style="margin-bottom:20px;">
				<form action="" method="POST">
					<button type="submit" class="btn btn-primary" name="downloadDatabase">Download A Database Backup</button>
					<a class="btn btn-primary text-center" href="<?= home_url('update-institute-information'); ?>">Update Institution Information</a>
					<a class="btn btn-danger text-center" href="<?= home_url('change-password'); ?>">Change Password </a>
					<a class="btn btn-primary pull-right" href="<?= home_url('add-post'); ?>">Add Post</a>
				</form>
			</div>



			<?php if (wp_get_current_user()->roles[0] == 'um_accounts'  || wp_get_current_user()->roles[0] == 'um_accounts-user') { ?>

				<div class="panel panel-default">
					<div class="panel-heading">Student fee & Accounts </div>
					<div class="panel-body">
						<div class="row">

							<?php if ($haveAccess || $arevenue) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-revenue'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Accounts</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $astdfee) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('student-fee-management?page=studentFeeManagement&view=addFee'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Student Fee</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $astdcoaching) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('coaching'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Coaching Fee etc</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>



						</div>
					</div>
				</div>





			<?php } else if (wp_get_current_user()->roles[0] == 'um_teachers') { ?>
				<div class="panel panel-default">
					<div class="panel-heading"><?= $haveAccess ? "Student & Teacher" : "Student"; ?></div>
					<div class="panel-body">
						<div class="row">
							<?php if ($haveAccess) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-teacher'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Teachers</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $astudent) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-student'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Student</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $aattendance) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-attendance'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Attendance</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $aclass) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-class'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Class</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $asection) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-section'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Section</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $agroup) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-group'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Group</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>
							<?php if ($haveAccess || $asubject) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-subject'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Subject</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $aexam) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-exam'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Exam</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $aexamatten) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-examattendance'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Exam attendance</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-examschedule'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Exam Schedule</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $aadmit) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-admitcard'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Admit Card</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $aseat) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-seatcard'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Seat Card</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>
							<?php if ($haveAccess || $aresultpublis) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-resultpublish'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Result Publish</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $aresultsummery) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('result-summery'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Result Summery</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $acgpaGenarate) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('cgpa-genarate'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">CGPA Genarate</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $aprogressReport) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('progress-report'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Progress Report</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $aresult) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-result'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Result</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $ameritlist) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-meritlist'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Merit List</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $afaillist) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-faillist'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Fail List</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $atabulation1) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-tabulation'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Tabulation Sheet</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $atabulation2) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-tabulation2'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Tabulation Sheet2</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $allmarksheet) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('all-marksheet'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">All MarkSheet</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $apromotion) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-promotion'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Promotion</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $apromotion) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('auto-promotion'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Auto Promotion</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $acgpapromotion) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('cgpa-promotion'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">CGPA Promotion</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $apromotion) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('demotion'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Demotion</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>
							<?php if ($haveAccess || $arevenue) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-revenue'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Accounts</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $astdfee) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('student-fee-management?page=studentFeeManagement&view=addFee'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Student Fee</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $astdfeereport) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('datewise-fees-information'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Fee Reports</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>
							<?php if ($haveAccess || $astdcoaching) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('coaching'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Coaching Fee etc.</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>
							<?php if ($haveAccess || $asms) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-sms'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">SMS</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

						</div>
					</div>
				</div>
			<?php } else { ?>
				<div class="panel panel-default">
					<div class="panel-heading"><?= $haveAccess ? "Student & Teacher" : "Student"; ?></div>
					<div class="panel-body">
						<div class="row">
							<?php if ($haveAccess) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-teacher'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Teachers</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>



							<?php if ($haveAccess) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-applicants'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Applicants</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $astudent) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-student'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Student</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $aattendance) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-attendance'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Attendance</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $aclass) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-class'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Class</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $asection) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-section'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Section</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $agroup) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-group'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Group</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-staff'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Staff</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-committee'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Committee</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>
						</div>
					</div>
				</div>

				<div class="panel panel-default">
					<div class="panel-heading">Exam management</div>
					<div class="panel-body">
						<div class="row">
							<?php if ($haveAccess || $asubject) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-subject'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Add Subject</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $aexam) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-exam'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Create Exam</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $aexamatten) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-examattendance'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Exam attendance</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-examschedule'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Exam Schedule</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $aadmit) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-admitcard'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Admit Card</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $aseat) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-seatcard'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Seat Card</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

						</div>
					</div>
				</div>

				<div class="panel panel-default">
					<div class="panel-heading">Result Management</div>

					<div class="panel-body">
						<div class="row">
							<?php if ($haveAccess || $aresult) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-result'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading"> Marks Entry</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $aresultpublis) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-resultpublish'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Result Publish</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $aresultsummery) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('result-summery'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Result Summery</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $ameritlist) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-meritlist'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Merit List</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $afaillist) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-faillist'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Fail List</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<!--	<?php if ($haveAccess || $atabulation1) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-tabulation'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Tabulation Sheet</h3><hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>-->

							<?php if ($haveAccess || $atabulation2) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-tabulation2'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Tabulation Sheet</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $allmarksheet) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('all-marksheet'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">All MarkSheet</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $acgpaGenarate) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('cgpa-genarate'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">CGPA Genarate</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $aprogressReport) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('progress-report'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Progress Report</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $apromotion) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-promotion'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Promotion</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $apromotion) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('auto-promotion'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Auto Promotion</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $acgpapromotion) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('cgpa-promotion'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">CGPA Promotion</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $apromotion) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('demotion'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Demotion</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

						</div>
					</div>
				</div>

				<div class="panel panel-default">
					<div class="panel-heading">Ready Template</div>
					<div class="panel-body">
						<div class="row">

							<?php if ($haveAccess || $aidcard) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-idcard'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">ID Card</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $atestimonial) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-testimonial'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Testimonial</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-concern-letter'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Concern Letter</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $atc) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-tc'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">TC</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

						</div>
					</div>
				</div>

				<div class="panel panel-default">
					<div class="panel-heading">Student fee & Accounts </div>
					<div class="panel-body">
						<div class="row">

							<?php if ($haveAccess || $arevenue) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-revenue'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Accounts</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $astdfee) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('student-fee-management?page=studentFeeManagement&view=addFee'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Student Fee</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

							<?php if ($haveAccess || $astdcoaching) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('coaching'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">Coaching Fee etc</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>



						</div>
					</div>
				</div>

				<div class="panel panel-default">
					<div class="panel-heading">SMS System</div>
					<div class="panel-body">
						<div class="row">

							<?php if ($haveAccess || $asms) { ?>
								<div class="col-md-3 col-sm-4">
									<a class="managmentItem" href="<?= home_url('admin-sms'); ?>">
										<div class="media">
											<div class="media-left">
												<span class="dashicons dashicons-networking"></span>
											</div>
											<div class="media-body">
												<h3 class="media-heading">SMS</h3>
												<hr>
											</div>
										</div>
									</a>
								</div>
							<?php } ?>

						</div>
					</div>
				</div>
		<?php }
				} ?>
		</div>

	</div>
</div>
</div>


<?php get_footer(); ?>
<script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
<script type="text/javascript">
	(function($) {
		$(document).ready(function() {
			$('#allposttbl').DataTable();
			$('#allposttbl').on('click', '.deletepost', function() {
				$(this).hide('fast').closest('div').find('.btn').show('fast');
			});
		});
	})(jQuery);
</script>