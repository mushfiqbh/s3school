<?php

/**
 * Template Name: Frontend Admin
 */
get_header();

global $wpdb;

if (wp_get_current_user()->user_login == 'teacher'){
  wp_redirect(home_url('admin-result'));
  exit;
}

require_once get_template_directory() . '/adminPages/functions/teacher-access.php';

$accessContext = s3s_get_teacher_access_context();
$teacher = $accessContext['teacher'];
$hasSubjectAssignment = $accessContext['has_subject_assignment'];
$hasAssignment = $accessContext['has_assignment'];
$isUnrestricted = $accessContext['unrestricted'];

// Allow full dashboard when either a class/section is assigned or the teacher is unrestricted
$isClassTeacher = $hasAssignment || $isUnrestricted;
$isSubjectTeacher = $hasSubjectAssignment || $isUnrestricted;

// 1️⃣ Define current user role
$user = wp_get_current_user();
$wpRole = $user->roles[0] ?? '';

// Mapping WP roles to abstract roles: admin | headmaster | teacher | accountant
$currentRole = 'student';
if (current_user_can('administrator') || $wpRole == 'admin') {
	$currentRole = 'admin';
} else if ($wpRole == 'um_headmaster') {
	$currentRole = 'headmaster';
} else if ($wpRole == 'um_teachers' && $isClassTeacher) {
	$currentRole = 'teacher';
} else if ($wpRole == 'um_teachers' && $isSubjectTeacher) {
	$currentRole = 'subject_teacher';
} else if ($wpRole == 'um_accounts' || $wpRole == 'um_accounts-user') {
	$currentRole = 'accountant';
}

// 2️⃣ Role permission helper
function canAccess(array $allowedRoles, string $currentRole): bool
{
	// admin and headmaster see everything
	if ($currentRole === 'admin' || $currentRole === 'headmaster') {
		return true;
	}

	return in_array($currentRole, $allowedRoles, true);
}


if (isset($_POST['downloadDatabase'])) {
	EXPORT_DATABASE();
}


$dashboardPanels = [
	[
		'title' => $wpRole === 'um_teachers' ? 'Students' : 'Student & Teacher',
		'items' => [
			['title' => 'Teachers', 'url' => 'admin-teacher', 'roles' => ['admin']],
			['title' => 'Applicants', 'url' => 'admin-applicants', 'roles' => ['admin']],
			['title' => 'Student', 'url' => 'admin-student', 'roles' => ['admin', 'teacher']],
			['title' => 'Attendance', 'url' => 'admin-attendance', 'roles' => ['admin', 'teacher']],
			['title' => 'Class', 'url' => 'admin-class', 'roles' => ['admin']],
			['title' => 'Section', 'url' => 'admin-section', 'roles' => ['admin']],
			['title' => 'Group', 'url' => 'admin-group', 'roles' => ['admin']],
			['title' => 'Staff', 'url' => 'admin-staff', 'roles' => ['admin']],
			['title' => 'Committee', 'url' => 'admin-committee', 'roles' => ['admin']],
		],
	],

	[
		'title' => 'Exam management',
		'items' => [
			['title' => 'Add Subject', 'url' => 'admin-subject', 'roles' => ['admin']],
			['title' => 'Create Exam', 'url' => 'admin-exam', 'roles' => ['admin']],
			['title' => 'Exam attendance', 'url' => 'admin-examattendance', 'roles' => ['admin']],
			['title' => 'Exam Schedule', 'url' => 'admin-exam-schedule', 'roles' => ['admin']],
			['title' => 'Admit Card', 'url' => 'admin-admitcard', 'roles' => ['admin']],
			['title' => 'Seat Card', 'url' => 'admin-seatcard', 'roles' => ['admin']],
		],
	],

	[
		'title' => 'Result Management',
		'items' => [
			['title' => 'Marks Entry', 'url' => 'admin-result', 'roles' => ['admin', 'teacher', 'subject_teacher']],
			['title' => 'Result Publish', 'url' => 'admin-resultpublish', 'roles' => ['admin']],
			['title' => 'Result Summery', 'url' => 'result-summery', 'roles' => ['admin', 'teacher']],
			['title' => 'Merit List', 'url' => 'admin-meritlist', 'roles' => ['admin', 'teacher',]],
			['title' => 'Fail List', 'url' => 'admin-faillist', 'roles' => ['admin', 'teacher',]],
			['title' => 'Tabulation Sheet', 'url' => 'admin-tabulation', 'roles' => ['admin', 'teacher',]],
			['title' => 'Tabulation Sheet2', 'url' => 'admin-tabulation2', 'roles' => ['admin', 'teacher',]],
			['title' => 'All MarkSheet', 'url' => 'all-marksheet', 'roles' => ['admin', 'teacher',]],
			['title' => 'CGPA Genarate', 'url' => 'cgpa-genarate', 'roles' => ['admin']],
			['title' => 'Progress Report', 'url' => 'progress-report', 'roles' => ['admin', 'teacher']],
			['title' => 'Promotion', 'url' => 'admin-promotion', 'roles' => ['admin', 'teacher']],
			['title' => 'Auto Promotion', 'url' => 'auto-promotion', 'roles' => ['admin', 'teacher']],
			['title' => 'CGPA Promotion', 'url' => 'cgpa-promotion', 'roles' => ['admin', 'teacher']],
			['title' => 'Demotion', 'url' => 'demotion', 'roles' => ['admin', 'teacher']],
		],
	],

	[
		'title' => 'Ready Template',
		'items' => [
			['title' => 'ID Card', 'url' => 'admin-idcard', 'roles' => ['admin']],
			['title' => 'Testimonial', 'url' => 'admin-testimonial', 'roles' => ['admin']],
			['title' => 'Concern Letter', 'url' => 'admin-concern-letter', 'roles' => ['admin']],
			['title' => 'TC', 'url' => 'admin-tc', 'roles' => ['admin']],
		],
	],

	[
		'title' => 'Student fee & Accounts',
		'items' => [
			['title' => 'Accounts', 'url' => 'admin-revenue', 'roles' => ['admin', 'accountant']],
			['title' => 'Student Fee', 'url' => 'student-fee-management?page=studentFeeManagement&view=addFee', 'roles' => ['admin', 'accountant']],
			['title' => 'Fee Reports', 'url' => 'datewise-fees-information', 'roles' => ['admin', 'accountant']],
			['title' => 'Coaching Fee etc', 'url' => 'coaching', 'roles' => ['admin', 'accountant']],
		],
	],
	[
		'title' => 'SMS System',
		'items' => [
			['title' => 'SMS', 'url' => 'admin-sms', 'roles' => ['admin', 'staff']],
		],
	],
];

?>
<style type="text/css">
	#user-submitted-title,#user-submitted-category, .usp-clone {
	width: 100%;
	margin-bottom: 15px;
	border-radius: 3px;
	border: 2px solid #ccc;
	padding: 5px;
}
#usp-submit{
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
			<?php if($wpRole === 'um_teachers') @include 'inc/teacher-profile.php'; ?>

			<div class="wow slideInLeft fronendAdmin">
				<style>
				.btn-primary {
					z-index: 1;
				}
				</style>
				<!-- Action Buttons -->
				<div style="margin-bottom:10px;">
					<form action="" method="POST">
						<?php if ($currentRole === 'admin' || $currentRole === 'headmaster'): ?>
							<button type="submit" class="btn btn-primary" name="downloadDatabase">Download A Database Backup</button>
							<a class="btn btn-primary text-center" href="<?= home_url('update-institute-information'); ?>">Update Institution Information</a>
						<?php endif; ?>
						
						<a class="btn btn-primary" href="<?= home_url('change-password'); ?>">Change Password</a>								
						
						<?php if ($currentRole === 'admin' || $currentRole === 'headmaster'): ?>
							<a class="btn btn-primary pull-right" href="<?= home_url('add-post'); ?>">Add Post</a>
						<?php endif; ?>
					</form>
				</div>

				<!-- Dynamic Content Panels Loop -->
				<?php foreach ($dashboardPanels as $panel): ?>
					<?php 
						$allowedItems = array_filter($panel['items'], function($item) use ($currentRole) {
							return canAccess($item['roles'], $currentRole);
						});
						if (empty($allowedItems)) continue;
					?>
					<div class="panel panel-default">
						<div class="panel-heading"><?= esc_html($panel['title']); ?></div>
						<div class="panel-body">
							<div class="row">
								<?php foreach ($allowedItems as $item): ?>
									<div class="col-md-3 col-sm-4">
										<a class="managmentItem" href="<?= (strpos($item['url'], 'http') === 0) ? $item['url'] : home_url($item['url']); ?>">
											<div class="media">
												<div class="media-left">
													<span class="dashicons dashicons-networking"></span>
												</div>
												<div class="media-body">
													<h3 class="media-heading"><?= esc_html($item['title']); ?></h3><hr>
												</div>
											</div>
										</a>
									</div>
								<?php endforeach; ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
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