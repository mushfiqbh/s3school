<?php
$studentID = $_GET['id'];
$stdCls = $_GET['class'];
$syear = $_GET['syear'];
$students = $wpdb->get_results(
"SELECT ct_student.*,ct_class.className,ct_group.groupName,ct_section.sectionName,ct_studentinfo.infoRoll,ct_studentinfo.infoYear,ct_studentinfo.infoid FROM ct_student
LEFT JOIN ct_studentinfo ON studentid = infoStdid AND infoClass = $stdCls AND infoYear = $syear 
LEFT JOIN ct_class ON ct_studentinfo.infoClass = ct_class.classid
LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId
LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
WHERE ct_student.studentid = $studentID AND stdStatus = 1" );
foreach ($students as $student) {
?>
<div id="studentProfile" class="row">
  <div class="col-md-4">
     <?php if(!empty($student->stdImg)){ ?>
    <img src="<?= $student->stdImg ?>" class="img-responsive stdImg">
    <?php }else{ ?>
    <img src="<?= get_template_directory_uri() ?>/img/No_Image.jpg" class="img-responsive stdImg">
    <?php } ?>
  </div>
  <div class="col-md-8">
    <h2 class="text-center stdName">
      <?= $student->stdName ?>
    </h2>
    <hr>
    <div class="row">
      <div class="col-md-4">
        <label>Class</label>
        <p><?= $student->className ?></p>
        <label>ID NO:</label>
        <p><?= $student->infoRoll ?></p>
        <label>Birth Date</label>
        <p><?= $student->stdBrith ?></p>
        <label>Religion</label>
        <p><?= $student->stdReligion ?></p>
        <label>Year/Session</label>
        <p><?= $student->infoYear ?></p>
      </div>
      <div class="col-md-4">
        <label>Group</label>
        <p><?= $student->groupName ?></p>
        <label>Section Name</label>
        <p><?= $student->sectionName ?></p>

        <label>SSC Roll</label>
        <p><?= $student->sscRoll ?></p>
        <label>SSC Registration</label>
        <p><?= $student->sscReg ?></p>
      </div>
      <div class="col-md-4">
        <label>Permanent Address</label>
        <p><?= $student->stdPermanent ?></p>
        <label>Present Address</label>
        <p><?= $student->stdPresent ?></p>
        <label>Nationality</label>
        <p><?= $student->stdNationality ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-12">
    <hr>
    <div class="row">
      <div class="col-md-4">
        <h4>PARENTS</h4>
      </div>
      <div class="col-md-4">
        <label>Father</label>
        <p><?= $student->stdFather ?></p>
        <label>Profession</label>
        <p><?= $student->stdFatherProf ?></p>
      </div>
      <div class="col-md-4">
        <label>Mother</label>
        <p><?= $student->stdMother ?></p>
        <label>Profession</label>
        <p><?= $student->stdMotherProf ?></p>
      </div>
    </div>
  </div>
  <div class="col-md-12">
    <hr>
    <div class="row">
      <div class="col-md-4">
        <h4>Other Information</h4>
      </div>
      <div class="col-md-4">
        <label>Previous School</label>
        <p><?= $student->stdPrevSchool ?></p>
        <label>TC Number</label>
        <p><?= $student->stdTcNumber ?></p>
      </div>
      <div class="col-md-4">
        <label>GPA</label>
        <p><?= $student->stdGPA ?></p>
      </div>
    </div>
  </div>
</div>
<?php
}