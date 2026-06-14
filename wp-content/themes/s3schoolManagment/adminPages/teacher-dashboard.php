<?php

/**
 * Template Name: Teacher Dashboard
 */

$current_user = wp_get_current_user();
if (!$current_user || empty($current_user->roles) || $current_user->roles[0] !== 'um_teachers') {
  wp_redirect(home_url());
  exit;
}

require_once get_template_directory() . '/adminPages/functions/teacher-access.php';

$accessContext = s3s_get_teacher_access_context();
$teacher = $accessContext['teacher'];
$hasAssignment = $accessContext['has_assignment'];
$isUnrestricted = $accessContext['unrestricted'];

// Allow full dashboard when either a class/section is assigned or the teacher is unrestricted
$isClassTeacher = $hasAssignment || $isUnrestricted;


?>
<div class="">
  <?php @include 'inc/teacher-profile.php'; ?>
  <div class="">
    <div class="container">
      <div class="wow slideInLeft fronendAdmin">
        <style>
          .btn-primary {
            z-index: 1;
          }
        </style>
        <div class="panel panel-default">
          <div class="panel-heading">Teacher Dashboard</div>
          <div class="panel-body">
            <div class="row">

              <?php if ($isClassTeacher) { ?>
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

              <?php if ($isClassTeacher) { ?>
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

              <?php if ($isClassTeacher) { ?>
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

              <?php if ($isClassTeacher) { ?>
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

              <?php if (true) { ?>
                <div class="col-md-3 col-sm-4">
                  <a class="managmentItem" href="<?= home_url('admin-result'); ?>">
                    <div class="media">
                      <div class="media-left">
                        <span class="dashicons dashicons-networking"></span>
                      </div>
                      <div class="media-body">
                        <h3 class="media-heading">Marks Entry</h3>
                        <hr>
                      </div>
                    </div>
                  </a>
                </div>
              <?php } ?>

              <?php if ($isClassTeacher) { ?>
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

              <?php if ($isClassTeacher) { ?>
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

              <?php if ($isClassTeacher) { ?>
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

              <?php if ($isClassTeacher) { ?>
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

              <?php if ($isClassTeacher) { ?>
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

              <?php if ($isClassTeacher) { ?>
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

              <?php if ($isClassTeacher) { ?>
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

              <?php if ($isClassTeacher) { ?>
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

              <?php if ($isClassTeacher) { ?>
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

							<?php if ($isClassTeacher) { ?>
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

							<?php if ($isClassTeacher) { ?>
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

							<?php if ($isClassTeacher) { ?>
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
      </div>
    </div>
  </div>
</div>