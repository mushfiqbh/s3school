<?php

/**
 * Template Name: Admin Student
 */
global $wpdb;


/*=================
Add Student
=================*/
if (isset($_POST['addStudent'])) {
  $insert = $wpdb->insert('ct_student', array(
    'stdName'       => $_POST['stdName'],
    'stdNameBangla' => $_POST['stdNameBangla'],
    'stdImg'        => $_POST['stdImg'],
    'stdFather'     => $_POST['stdFather'],
    'fatherLate'    => isset($_POST['fatherLate']) ? 1 : 0,
    'stdFatherProf' => $_POST['stdFatherProf'],
    'stdFatherNID'  => isset($_POST['stdFatherNID']) ? $_POST['stdFatherNID'] : '',
    'stdMother'     => $_POST['stdMother'],
    'motherLate'    => isset($_POST['motherLate']) ? 1 : 0,
    'stdMotherProf' => $_POST['stdMotherProf'],
    'stdMotherNID'  => isset($_POST['stdMotherNID']) ? $_POST['stdMotherNID'] : '',
    'stdParentIncome'  => $_POST['stdParentIncome'],
    'stdGuardianNID' => $_POST['stdFatherNID'] || '0',
    'stdPhone'      => $_POST['stdPhone'],
    'stdEmergencyPhone' => isset($_POST['stdEmergencyPhone']) ? $_POST['stdEmergencyPhone'] : '',
    'stdEmail'      => isset($_POST['stdEmail']) ? $_POST['stdEmail'] : '',
    'stdPermanent'  => $_POST['stdPermanent'],
    'stdAdmitYear'  => $_POST['stdCurntYear'],
    'stdCurntYear'  => $_POST['stdCurntYear'],
    'stdAdmitClass' => $_POST['stdAdmitClass'],
    'stdCurrentClass'  => $_POST['stdAdmitClass'],
    'stdShift'      => isset($_POST['stdShift']) ? $_POST['stdShift'] : '',
    'stdPresent'    => $_POST['stdPresent'],
    'stdBrith'      => $_POST['stdBrith'],
    'birth_reg_no'  => $_POST['birth_reg_no'],
    'facilities'    => $_POST['facilities'],
    'stdNationality'   => $_POST['stdNationality'],
    'stdReligion'   => isset($_POST['stdReligion']) ? $_POST['stdReligion'] : '',
    'stdTcNumber'   => $_POST['stdTcNumber'],
    'sscRoll'       => $_POST['sscRoll'],
    'sscReg'        => $_POST['sscReg'],
    'stdPrevSchool' => $_POST['stdPrevSchool'],
    'stdGPA'        => $_POST['stdGPA'],
    'stdIntellectual'  => $_POST['stdIntellectual'],
    'stdScholarsClass' => $_POST['stdScholarsClass'],
    'stdScholarsYear'  => $_POST['stdScholarsYear'],
    'stdScholarsMemo'  => $_POST['stdScholarsMemo'],
    'stdGender'     => $_POST['stdGender'],
    'stdBldGrp'     => $_POST['stdBldGrp'],
    'createdBy'     => get_current_user_id()
  ));

  $lastid = $wpdb->insert_id;

  // If this student was created from an application, update the application status to 'Registered'
  if (!empty($_POST['applicationid'])) {
    $wpdb->update(
      'ct_online_application',
      array('approve_status' => 'Registered'),
      array('applicationid' => (int)$_POST['applicationid'])
    );
  }

  if ($insert) {

    $insert2 = $wpdb->insert('ct_studentinfo', array(
      'infoStdid'   => $lastid,
      'infoClass'   => $_POST['stdAdmitClass'],
      'infoYear'    => $_POST['stdCurntYear'],
      'infoSection' => isset($_POST['stdSection']) ? $_POST['stdSection'] : 0,
      'infoGroup'   => isset($_POST['stdGroup']) ? $_POST['stdGroup'] : 0,
      'infoRoll'    => $_POST['stdRoll'],
      'infoOptionals' => isset($_POST['stdOptionals']) ? json_encode($_POST['stdOptionals']) : 0,
      'info4thSub'  => isset($_POST['std4thsub']) ? json_encode($_POST['std4thsub']) : 0
    ));
    $message = ms3message($insert2, 'Added');
  }
}



/*=================
Update Student
=================*/
if (isset($_POST['updateStudent'])) {

  $update = $wpdb->update('ct_student', array(
    'stdName'         => $_POST['stdName'],
    'stdNameBangla'   => $_POST['stdNameBangla'],
    'stdImg'          => $_POST['stdImg'],
    'stdFather'       => $_POST['stdFather'],
    'fatherLate'      => isset($_POST['fatherLate']) ? 1 : 0,
    'stdFatherProf'   => $_POST['stdFatherProf'],
    'stdFatherNID'    => isset($_POST['stdFatherNID']) ? $_POST['stdFatherNID'] : '',
    'stdMother'       => $_POST['stdMother'],
    'motherLate'      => isset($_POST['motherLate']) ? 1 : 0,
    'stdMotherProf'   => $_POST['stdMotherProf'],
    'stdMotherNID'    => isset($_POST['stdMotherNID']) ? $_POST['stdMotherNID'] : '',
    'stdParentIncome' => $_POST['stdParentIncome'],
    'stdGuardianNID'  => $_POST['stdFatherNID'] || '0',
    'stdPhone'        => $_POST['stdPhone'],
    'stdEmergencyPhone' => isset($_POST['stdEmergencyPhone']) ? $_POST['stdEmergencyPhone'] : '',
    'stdEmail'        => isset($_POST['stdEmail']) ? $_POST['stdEmail'] : '',
    'stdCurntYear'    => $_POST['stdCurntYear'],
    'stdCurrentClass' => $_POST['stdAdmitClass'],
    'stdShift'        => isset($_POST['stdShift']) ? $_POST['stdShift'] : '',
    'stdPermanent'    => $_POST['stdPermanent'],
    'stdPresent'      => $_POST['stdPresent'],
    'stdBrith'        => $_POST['stdBrith'],
    'birth_reg_no'  => $_POST['birth_reg_no'],
    'facilities'      => $_POST['facilities'],
    'stdNationality'  => $_POST['stdNationality'],
    'stdReligion'     => isset($_POST['stdReligion']) ? $_POST['stdReligion'] : '',
    'stdTcNumber'     => $_POST['stdTcNumber'],
    'sscRoll'         => $_POST['sscRoll'],
    'sscReg'          => $_POST['sscReg'],
    'stdPrevSchool'   => $_POST['stdPrevSchool'],
    'stdGPA'          => $_POST['stdGPA'],
    'stdIntellectual' => $_POST['stdIntellectual'],
    'stdScholarsClass' => $_POST['stdScholarsClass'],
    'stdScholarsYear' => $_POST['stdScholarsYear'],
    'stdScholarsMemo' => $_POST['stdScholarsMemo'],
    'stdGender'       => $_POST['stdGender'],
    'stdBldGrp'       => $_POST['stdBldGrp'],
    'stdUpdatedAt'    => date("Y-m-d h:i:sa")
  ), array(
    'studentid' => $_POST['stdid']
  ));


  $update2 = $wpdb->update('ct_studentinfo', array(
    'infoClass'     => $_POST['stdAdmitClass'],
    'infoSection'   => isset($_POST['stdSection']) ? $_POST['stdSection'] : 0,
    'infoGroup'     => isset($_POST['stdGroup']) ? $_POST['stdGroup'] : 0,
    'infoRoll'      => $_POST['stdRoll'],
    'infoYear'      => $_POST['stdCurntYear'],
    'infoOptionals' => isset($_POST['stdOptionals']) ? json_encode($_POST['stdOptionals']) : 0,
    'info4thSub'    => isset($_POST['std4thsub']) ? json_encode($_POST['std4thsub']) : 0
  ), array(
    'infoid' => $_POST['infoid']
  ));
  if ($update || $update2) {
    $message = ms3message($update, 'Updated');
  }
}



/*=================
Delete Student
==================*/
if (isset($_POST['deleteStudent'])) {

  $id = intval($_POST['id']);

  $d1 = $wpdb->delete('ct_student', ['studentid' => $id]);
  $d2 = $wpdb->delete('ct_studentinfo', ['infoStdid' => $id]);
  $d3 = $wpdb->delete('ct_result', ['resStudentId' => $id]);
  $d4 = $wpdb->delete('ct_studentPoint', ['spStdID' => $id]);

  $delete = ($d1 !== false || $d2 !== false || $d3 !== false || $d4 !== false);

  $message = ms3message($delete, 'Deleted Student Profile & Everything');
}

/*=================
Delete Enrollment
==================*/
if (isset($_POST['deleteEnrollment'])) {

  $id = intval($_POST['id']);
  $enrollment_id = isset($_POST['enrollment_id']) ? intval($_POST['enrollment_id']) : 0;
  
  $enrollment_year = $wpdb->get_var($wpdb->prepare("SELECT infoYear FROM ct_studentinfo WHERE infoid = %d", $enrollment_id));
  
  if ($enrollment_id > 0) {
    $delete = $wpdb->delete('ct_studentinfo', ['infoid' => $enrollment_id]);
  } else {
    $delete = $wpdb->delete('ct_studentinfo', ['infoStdid' => $id]);
  }
  

  $wpdb->delete('ct_result', ['resStudentId' => $id, 'resultYear' => $enrollment_year]);
  $wpdb->delete('ct_studentPoint', ['spStdID' => $id, 'spYear' => $enrollment_year]);

  $message = ms3message($delete, 'Deleted Enrollment & Its Results');
}


/*=================
Upload Student Image (AJAX)
=================*/
if (isset($_POST['type']) && $_POST['type'] == 'uploadStudentImage') {
  // Increase limits for image processing to prevent 503 errors
  @ini_set('memory_limit', '512M');
  @set_time_limit(300);

  if (!isset($_FILES['student_image']) || !isset($_POST['student_id'])) {
    wp_send_json_error('Invalid request');
  }

  $student_id = intval($_POST['student_id']);
  $uploadedfile = $_FILES['student_image'];

  if (!function_exists('wp_handle_upload')) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
  }

  $upload_overrides = ['test_form' => false];
  $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

  if ($movefile && !isset($movefile['error'])) {
    $uploaded_image_url = $movefile['url'];

    // Register image in database as attachment
    $filename = basename($movefile['file']);
    $filetype = wp_check_filetype($filename, null);
    $attachment = [
      'guid'           => $uploaded_image_url,
      'post_mime_type' => $filetype['type'],
      'post_title'     => sanitize_file_name($filename),
      'post_content'   => '',
      'post_status'    => 'inherit'
    ];

    $attach_id = wp_insert_attachment($attachment, $movefile['file']);

    if (!is_wp_error($attach_id)) {
      require_once ABSPATH . 'wp-admin/includes/image.php';
      require_once ABSPATH . 'wp-admin/includes/media.php';

      // Disable thumbnail generation for this request to save memory and prevent 503 errors
      add_filter('intermediate_image_sizes_advanced', '__return_empty_array');

      $attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
      wp_update_attachment_metadata($attach_id, $attach_data);

      remove_all_filters('intermediate_image_sizes_advanced');

      // Update student record
      $wpdb->update(
        'ct_student',
        ['stdImg' => $uploaded_image_url],
        ['studentid' => $student_id]
      );

      wp_send_json_success(['url' => $uploaded_image_url]);
    } else {
      wp_send_json_error($attach_id->get_error_message());
    }
  } else {
    $error_message = (is_array($movefile) && isset($movefile['error'])) ? $movefile['error'] : 'Upload failed';
    wp_send_json_error($error_message);
  }
  exit;
}

?>

<?php if (! is_admin()) {
  get_header(); ?>
  <div class="b-layer-main">

    <div class="">
      <div class="container">
        <div class="row">
          <div class="col-md-12">
          <?php } ?>

          <p id="theSiteURL" class="hidden"><?= get_template_directory_uri() ?></p>

          <div class="container-fluid maxAdminpages" style="padding-left: 0">

            <!-- Show Status message -->
            <?php if (isset($message)) {
              ms3showMessage($message);
            } ?>

            <h2>
              Student Management

              <?php if (!isset($_GET['option'])) { ?>
                <a class="pull-right btn btn-primary" href="?page=student&option=add">
                  <span class="dashicons dashicons-plus"></span> Add Student
                </a>
              <?php } else { ?>
                <a class="pull-right btn btn-primary" href="?page=student">
                  <span class="dashicons dashicons-groups"></span> Students
                </a>
              <?php } ?>

              <div style="display:flex; gap:10px; margin-right: 10px;" class="pull-right">
                <a class="pull-right btn" style="color:black;" href="<?= home_url('/import-export-students') ?>">
                  <span class="dashicons dashicons-upload"></span> Import/Export Students
                </a>
                <a class="btn btn-info" href="?page=student&option=statistics">
                  <span class="dashicons dashicons-chart-pie"></span> Student Information
                </a>
              </div>
            </h2><br>



            <?php
            if (!isset($_GET['option'])) {
              require 'inc/student-defalt.php';
            } elseif ($_GET['option'] == 'add') {
              // If redirected from admin-applicants with an online application ID, fetch for prefill
              $prefill = null;
              if (isset($_GET['from_app'])) {
                $appid = (int) $_GET['from_app'];
                $prefill = $wpdb->get_row($wpdb->prepare("SELECT * FROM ct_online_application WHERE applicationid = %d", $appid));
              }
              require 'inc/student-add(edit).php';
              // Inject prefill script after form is rendered
              if ($prefill) {
            ?>
                <script type="text/javascript">
                  (function($) {
                    $(function() {
                      var d = <?php echo json_encode($prefill); ?>;
                      // Personal
                      $('[name="stdName"]').val(d.stdName || '');
                      $('[name="stdNameBangla"]').val(d.stdNameBangla || '');
                      if (d.stdImg) {
                        $('[name="stdImg"]').val(d.stdImg);
                        $('.std-img-url').val(d.stdImg);
                        $('.std-img-preview').attr('src', d.stdImg);
                      }
                      if (d.stdBrith) $('[name="stdBrith"]').val(d.stdBrith);
                      if (d.stdGender !== null) $('[name="stdGender"]').val(String(d.stdGender));
                      if (d.stdBldGrp) $('[name="stdBldGrp"]').val(d.stdBldGrp);
                      $('[name="stdPermanent"]').val(d.stdPermanent || '');
                      $('[name="stdPresent"]').val(d.stdPresent || '');
                      $('[name="stdNationality"]').val(d.stdNationality || '');
                      if (d.stdReligion) $('[name="stdReligion"]').val(d.stdReligion).trigger('change');

                      // Guardian
                      $('[name="stdFather"]').val(d.stdFather || '');
                      if (parseInt(d.fatherLate || 0) === 1) $('[name="fatherLate"]').prop('checked', true);
                      $('[name="stdFatherProf"]').val(d.stdFatherProf || '');
                      $('[name="stdParentIncome"]').val(d.stdParentIncome || '');
                      $('[name="stdGuardianNID"]').val(d.stdGuardianNID || '');
                      $('[name="stdMother"]').val(d.stdMother || '');
                      if (parseInt(d.motherLate || 0) === 1) $('[name="motherLate"]').prop('checked', true);
                      $('[name="stdMotherProf"]').val(d.stdMotherProf || '');
                      $('[name="stdPhone"]').val(d.stdPhone || '');

                      // Exam/others
                      $('[name="sscRoll"]').val(d.sscRoll || '');
                      $('[name="sscReg"]').val(d.sscReg || '');
                      $('[name="stdGPA"]').val(d.stdGPA || '');
                      $('[name="stdIntellectual"]').val(d.stdIntellectual || '');
                      $('[name="stdPrevSchool"]').val(d.stdPrevSchool || '');
                      $('[name="stdTcNumber"]').val(d.stdTcNumber || '');

                      // Class and Year
                      if (d.stdAdmitClass) {
                        $('#admitClass').val(String(d.stdAdmitClass)).trigger('change');
                      }
                      if (d.stdAdmitYear) {
                        $('#stdCurntYear').val(String(d.stdAdmitYear));
                      }

                      // Section (wait for AJAX population, then set)
                      (function setSection() {
                        var $sec = $('select[name="stdSection"]');
                        if (d.stdSection && $sec.length && $sec.find('option').length) {
                          $sec.val(String(d.stdSection)).trigger('change');
                        } else {
                          setTimeout(setSection, 400);
                        }
                      })();

                      // Group if present in DOM
                      (function setGroup() {
                        var $grp = $('#stdGroup');
                        if ($grp.length && (typeof d.stdGroup !== 'undefined')) {
                          $grp.val(String(d.stdGroup)).trigger('change');
                        } else {
                          setTimeout(setGroup, 400);
                        }
                      })();

                      // Roll and defaults
                      if (d.stdRoll) $('#stdRoll').val(d.stdRoll);
                      if (d.stdShift) $('[name="stdShift"]').val(d.stdShift);
                      $('select[name="admission_type"]').val('1');
                    });
                  })(jQuery);
                </script>
            <?php
              }
            } elseif ($_GET['option'] == 'view') {
              require 'inc/student-view.php';
            } elseif ($_GET['option'] == 'statistics') {
              require 'inc/statistics.php';
            }
            ?>
          </div>


          <?php if (! is_admin()) { ?>
          </div>
        </div>
      </div>
    </div>
  </div>
<?php get_footer();
          } ?>

<div id="deleteModal" class="modal fade" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">

      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Do you want to Delete?</h4>
      </div>

      <div class="modal-body">
        <p class="text-danger">You can't recover the data after delete.</p>
      </div>

      <div class="modal-footer">

        <form method="POST">

          <input type="hidden" name="id" id="delete_id">
          <input type="hidden" name="enrollment_id" id="delete_enrollment_id">

          <button type="submit" class="btn btn-danger" name="deleteStudent">
            <strong>Delete Everything</strong>
            <span style="display:block;font-size:12px;">Remove profile, enrollment & results</span>
          </button>
          
          <button type="submit" class="btn btn-warning" name="deleteEnrollment">
            <strong>Delete Enrollment Only</strong>
            <span style="display:block;font-size:12px;">Remove from class & Its Results</span>
          </button>


        </form>

      </div>
    </div>
  </div>
</div>

<script type="text/javascript">
  jQuery(document).ready(function($) {
    $('.btnDelete').click(function() {
      var id = $(this).data('id');
      var enrollmentId = $(this).data('enrollment-id') || '';

      $('#delete_id').val(id);
      $('#delete_enrollment_id').val(enrollmentId);
      // OPEN MODAL
      $('#deleteModal').modal('show');
    });
  });
</script>