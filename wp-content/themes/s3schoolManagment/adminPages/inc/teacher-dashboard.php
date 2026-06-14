<?php
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

<style>
  .btn-primary {
    z-index: 1;
  }
</style>

<div class="container">
  <div class="wow slideInLeft fronendAdmin">
    <?php
      @include 'teacher-profile.php';
    ?>

    <div class="panel panel-default">
      <div class="panel-heading">Academic</div>
      <div class="panel-body">
        <div class="row">
          <?php if ($isClassTeacher || $astudent) { ?>
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

          <?php if ($isClassTeacher || $aclass) { ?>
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

          <?php if ($isClassTeacher || $asection) { ?>
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

          <?php if ($isClassTeacher || $agroup) { ?>
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

          <?php if ($isClassTeacher || $aattendance) { ?>
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

          <?php if ($isClassTeacher || $asubject) { ?>
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
        </div>
      </div>
    </div>

    <div class="panel panel-default">
      <div class="panel-heading">Examination</div>
      <div class="panel-body">
        <div class="row">

          <?php if ($isClassTeacher || $aexam) { ?>
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

          <?php if ($isClassTeacher || $aexamatten) { ?>
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

          <?php if ($isClassTeacher) { ?>
            <div class="col-md-3 col-sm-4">
              <a class="managmentItem" href="<?= home_url('admin-exam-schedule'); ?>">
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



          <?php if ($isClassTeacher || $aadmit) { ?>
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

          <?php if ($isClassTeacher || $aseat) { ?>
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
      <div class="panel-heading">Result</div>
      <div class="panel-body">
        <div class="row">

          <?php if ($isClassTeacher) { ?>
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

          <?php if ($isClassTeacher || $aresultpublis) { ?>
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

          <?php if ($isClassTeacher || $aresultsummery) { ?>
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

          <?php if ($isClassTeacher || $acgpaGenarate) { ?>
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

          <?php if ($isClassTeacher || $aprogressReport) { ?>
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

          <?php if ($isClassTeacher || $aresult) { ?>
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

          <?php if ($isClassTeacher || $ameritlist) { ?>
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

          <?php if ($isClassTeacher || $afaillist) { ?>
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

          <?php if ($isClassTeacher || $atabulation1) { ?>
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

          <?php if ($isClassTeacher || $atabulation2) { ?>
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

          <?php if ($isClassTeacher || $allmarksheet) { ?>
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

    <div class="panel panel-default">
      <div class="panel-heading">Promotions</div>
      <div class="row">
        <div class="panel-body">
          <?php if ($isClassTeacher || $apromotion) { ?>
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

          <?php if ($isClassTeacher || $apromotion) { ?>
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

          <?php if ($isClassTeacher || $acgpapromotion) { ?>
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

          <?php if ($isClassTeacher || $apromotion) { ?>
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

      <div class="panel-heading">Accounts</div>
      <div class="panel-body">
        <div class="row">
          <?php if ($isClassTeacher || $arevenue) { ?>
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

          <?php if ($isClassTeacher || $astdfee) { ?>
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

          <?php if ($isClassTeacher || $astdfeereport) { ?>
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
          <?php if ($isClassTeacher || $astdcoaching) { ?>
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
          <?php if ($isClassTeacher || $asms) { ?>
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
  </div>
</div>