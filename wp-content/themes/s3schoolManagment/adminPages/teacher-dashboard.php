<?php

/**
 * Template Name: Teacher Dashboard
 */

if (wp_get_current_user()->roles[0] != 'um_teachers') {
  wp_redirect(home_url());
  exit;
}

global $wpdb;

// Determine table name (try prefixed first, fallback to ct_teacher)
$prefixed = $wpdb->prefix . 'ct_teacher';
$exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $prefixed));
$table = ($exists === $prefixed) ? $prefixed : 'ct_teacher';

$current_user = wp_get_current_user();
$user_id = $current_user->ID;


// Fetch teacher record for current user
$teacher = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE tecUserId = %d", $user_id));

// Check if the teacher is assigned as a class teacher
$isClassTeacher = (!empty($teacher->teacherOfClass) && !empty($teacher->teacherOfSection));
$haveAccess = $isClassTeacher;


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

              <?php if ($aresult) { ?>
                <div class="col-md-3 col-sm-4">
                  <a class="managmentItem" href="<?= home_url('admin-result'); ?>">
                    <div class="media">
                      <div class="media-left">
                        <span class="dashicons dashicons-networking"></span>
                      </div>
                      <div class="media-body">
                        <h3 class="media-heading">Result Entry</h3>
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
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>