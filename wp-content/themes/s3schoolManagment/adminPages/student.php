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
    'stdFatherBangla' => $_POST['stdFatherBangla'],
    'fatherLate'    => isset($_POST['fatherLate']) ? 1 : 0,
    'stdFatherProf' => $_POST['stdFatherProf'],
    'stdMother'     => $_POST['stdMother'],
    'stdMotherBangla' => $_POST['stdMotherBangla'],
    'motherLate'    => isset($_POST['motherLate']) ? 1 : 0,
    'stdMotherProf' => $_POST['stdMotherProf'],
    'stdParentIncome'  => $_POST['stdParentIncome'],
    'stdlocalGuardian' => $_POST['stdlocalGuardian'],
    'stdGuardianNID' => $_POST['stdGuardianNID'],
    'stdPhone'      => $_POST['stdPhone'],
    'stdPermanent'  => $_POST['stdPermanent'],
    'stdAdmitYear'  => $_POST['stdCurntYear'],
    'stdCurntYear'  => $_POST['stdCurntYear'],
    'stdAdmitClass' => $_POST['stdAdmitClass'],
    'stdCurrentClass'  => $_POST['stdAdmitClass'],
    'stdPresent'    => $_POST['stdPresent'],
    'stdBrith'      => $_POST['stdBrith'],
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
    'admission_type' => $_POST['admission_type'],
    'facilities_activation_date'  => $_POST['facilities_activation_date'],
    'monthly_fee'     => $_POST['monthly_fee'],
    'transport_required'  => $_POST['transport_required'],
    'transport_type'  => $_POST['transport_type'],
    'transport_fee_id'  => $_POST['transport_fee_id'],
    'transport_activation_date'  => $_POST['transport_activation_date'],
    'birth_reg_no'  => $_POST['birth_reg_no'],
    'stdBldGrp'     => $_POST['stdBldGrp'],
    'createdBy'     => get_current_user_id()
  ));

  $lastid = $wpdb->insert_id;

  if ($insert) {

    $insert2 = $wpdb->insert('ct_studentinfo', array(
      'infoStdid'   => $lastid,
      'infoClass'   => $_POST['stdAdmitClass'],
      'infoYear'    => $_POST['stdCurntYear'],
      'infoSection' => isset($_POST['stdSection']) ? $_POST['stdSection'] : 0,
      'infoGroup'   => isset($_POST['stdGroup']) ? $_POST['stdGroup'] : 0,
      'infoRoll'    => $_POST['stdRoll'],
      'infoOptionals' => isset($_POST['stdOptionals']) ? json_encode($_POST['stdOptionals']) : 0,
      'info4thSub'  => isset($_POST['std4thsub']) ? $_POST['std4thsub'] : 0
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
    'stdFatherBangla' => $_POST['stdFatherBangla'],
    'fatherLate'      => isset($_POST['fatherLate']) ? 1 : 0,
    'stdFatherProf'   => $_POST['stdFatherProf'],
    'stdMother'       => $_POST['stdMother'],
    'stdMotherBangla' => $_POST['stdMotherBangla'],
    'motherLate'      => isset($_POST['motherLate']) ? 1 : 0,
    'stdMotherProf'   => $_POST['stdMotherProf'],
    'stdParentIncome' => $_POST['stdParentIncome'],
    'stdlocalGuardian' => $_POST['stdlocalGuardian'],
    'stdGuardianNID' => $_POST['stdGuardianNID'],
    'stdCurntYear'    => $_POST['stdCurntYear'],
    'stdCurrentClass' => $_POST['stdAdmitClass'],
    'stdPhone'        => $_POST['stdPhone'],
    'stdPermanent'    => $_POST['stdPermanent'],
    'stdPresent'      => $_POST['stdPresent'],
    'stdBrith'        => $_POST['stdBrith'],
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
    'admission_type'  => $_POST['admission_type'],
    'facilities_activation_date'  => $_POST['facilities_activation_date'],
    'monthly_fee'     => $_POST['monthly_fee'],
    'transport_required'  => $_POST['transport_required'],
    'transport_type'  => $_POST['transport_type'],
    'transport_fee_id'  => $_POST['transport_fee_id'],
    'transport_activation_date'  => $_POST['transport_activation_date'],
    'birth_reg_no'  => $_POST['birth_reg_no'],
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
    'info4thSub'    => isset($_POST['std4thsub']) ? $_POST['std4thsub'] : 0
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
  $delete = $wpdb->delete('ct_student', array('studentid' => $_POST['id']));
  $delete = $wpdb->delete('ct_studentinfo', array('infoStdid' => $_POST['id']));
  $delete = $wpdb->delete('ct_result', array('resStudentId' => $_POST['id']));
  $delete = $wpdb->delete('ct_studentPoint', array('spStdID' => $_POST['id']));
  $message = ms3message($delete, 'Deleted');
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
              <a class="pull-right btn btn-info" href="?page=student&option=statistics">
                <span class="dashicons dashicons-chart-pie"></span> Student Information
              </a>
            </h2><br>
            <style>
              .panel {
                overflow: visible;
              }
            </style>


            <?php
            if (!isset($_GET['option'])) {
              require 'inc/student-defalt.php';
            } elseif ($_GET['option'] == 'add') {
              require 'inc/student-add(edit).php';
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
  <div class="modal-dialog modal-sm">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Do you want to Delete?</h4>
      </div>
      <div class="modal-body">
        <p class="text-danger">You can't recover the data after delete.</p>
      </div>
      <div class="modal-footer">
        <form action="" method="POST">
          <input type="hidden" name="id" class="id">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">NO</button>
          <button type="submit" class="btn btn-danger" name="deleteStudent">YES</button>
        </form>
      </div>
    </div>

  </div>
</div>

<script type="text/javascript">
  (function($) {
    $(document).ready(function() {
      $('.btnDelete').click(function(event) {
        $('#deleteModal').find('.id').val($(this).data('id'));
        $('#deleteModal').modal("show");
      });
    });
  })(jQuery);
</script>