<?php 

// Check if current user is a teacher and get their restrictions
$current_user = wp_get_current_user();
$isTeacher = (isset($current_user->roles[0]) && $current_user->roles[0] == 'um_teachers');
$teacherRestrictions = null;

if ($isTeacher) {
    global $wpdb;
    // Determine table name (try prefixed first, fallback to ct_teacher)
    $prefixed = $wpdb->prefix . 'ct_teacher';
    $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $prefixed));
    $table = ($exists === $prefixed) ? $prefixed : 'ct_teacher';
    
    $user_id = $current_user->ID;
    $teacher = $wpdb->get_row($wpdb->prepare("SELECT teacherOfClass, teacherOfSection FROM $table WHERE tecUserId = %d", $user_id));
    
    if ($teacher && !empty($teacher->teacherOfClass) && !empty($teacher->teacherOfSection)) {
        $teacherRestrictions = $teacher;
    }
}

/*===============
** Edit Student
================*/
$editid = 0;
$transportFeeInfo = $wpdb->get_results("SELECT * FROM ct_transport_fee_list");

if (isset($_GET['edit']))
{
  $editid = $_GET['edit'];
  $stdclass = $_GET['class'];
  $edit = $wpdb->get_results("SELECT * FROM ct_student
  LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid AND ct_studentinfo.infoClass = $stdclass
  WHERE studentid = $editid");

  if($edit > 0){
    $edit = $edit[0];
    
    $showGroup = false;
      $result = $wpdb->get_row("SELECT havegroup FROM ct_class WHERE classid = '$stdclass'");
      if ($result && $result->havegroup == 1) {
        $showGroup = true;
      }
    ?>
    <form accept="" method="POST" class="applyForm fronendAdmin">
      
      <input type='hidden' name='stdid' value='<?= $edit->studentid ?>'> 
      <input type='hidden' name='infoid' value='<?= $edit->infoid ?>'>
      <?php if (isset($_GET['from_app'])): ?>
      <input type='hidden' name='applicationid' value='<?= (int)$_GET['from_app'] ?>'>
      <?php endif; ?> 

      <div class="panel panel-default">
        <div class="panel-heading"><b><center>Personal and educational information</center></b></div>
        <div class="panel-body">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Student Name <span>*</span></label>
                <input class="form-control" type="text" name="stdName" placeholder="Student Name" value='<?= $edit->stdName ?>' required>
              </div>
              <div class="form-group">
                <label>ছাত্র/ছাত্রীর নাম (বাংলা)</label>
                <input class="form-control" type="text" name="stdNameBangla" placeholder="ছাত্র/ছাত্রীর নাম" value='<?= $edit->stdNameBangla ?>'>
              </div>
              <div class="form-group">
                <label>Student Photo
                </label>
                <br>
                <div class="mediaUploadHolder">
                  <button type="button" class="mediaUploader btn btn-info">Upload
                  </button>
                  <span>
                    <?php echo (!empty($edit->stdImg)) ? "<img height='40' src='".$edit->stdImg."'>" : ''; ?>
                  </span>
                  <input class="hidden teacherImg" type="text" name="stdImg" value="<?= $edit->stdImg ?>">
                </div>
              </div>
              <div class="form-group">
                <label>Date Of Birth <span>*</span></label>
                <input class="form-control" type="date" name="stdBrith" placeholder="Date Of Birth" value="<?= $edit->stdBrith ?>" required>
              </div>
              <div class="form-group">
                <label>Birth Registration Number
                </label>
                <input class="form-control" type="text" name="birth_reg_no" placeholder="Birth Registration Number" value="<?= $edit->birth_reg_no ?>">
              </div>
              

              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Gender <span>*</span></label>

                    <?php
                      $SBoy = $SGirl = $SOther = '';
                      if($edit->stdGender == 0){ $SGirl = 'selected'; }
                      elseif ($edit->stdGender == 1) { $SBoy = 'selected'; }
                      else{ $SOther = 'selected'; }
                    ?>
                    <select class="form-control" name="stdGender">
                      <option value="1" <?= $SBoy; ?>>Boy</option>
                      <option value="0" <?= $SGirl; ?>>Girl</option>
                      <option value="2" <?= $SOther; ?>>Other</option>
                    </select>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Blood Group</label>
                    <?php
                      $A = $Ap = $B = $Bp = $AB = $ABp = $O = $Op = '';

                      if($edit->stdBldGrp == 'A-'){ $A = 'selected'; }
                      elseif($edit->stdBldGrp == 'A+'){ $Ap = 'selected'; }
                      elseif($edit->stdBldGrp == 'B-'){ $B = 'selected'; }
                      elseif($edit->stdBldGrp == 'B+'){ $Bp = 'selected'; }
                      elseif($edit->stdBldGrp == 'AB-'){ $AB = 'selected'; }
                      elseif($edit->stdBldGrp == 'AB+'){ $ABp = 'selected'; }
                      elseif($edit->stdBldGrp == 'O-'){ $O = 'selected'; }
                      elseif($edit->stdBldGrp == 'O+'){ $Op = 'selected'; }
                    ?>
                    <select class="form-control" name="stdBldGrp">
                      <option>N/A</option>
                      <option <?= $Ap; ?>>A+</option>
                      <option <?= $A; ?>>A-</option>
                      <option <?= $Bp; ?>>B+</option>
                      <option <?= $B; ?>>B-</option>
                      <option <?= $ABp; ?>>AB+</option>
                      <option <?= $AB; ?>>AB-</option>
                      <option <?= $Op; ?>>O+</option>
                      <option <?= $O; ?>>O-</option>
                    </select>
                    
                  </div>
                </div>
              </div>
              
              
              <div class="form-group">
                <label>Permanent Address</label>
                <input class="form-control" type="text" name="stdPermanent" placeholder="Permanent Address" value="<?= $edit->stdPermanent ?>">
              </div>
              <div class="form-group">
                <label>Present Address</label>
                <input class="form-control" type="text" name="stdPresent" placeholder="Present Address" value="<?= $edit->stdPresent ?>">
              </div>
              <div class="form-group">
                    <label>Admission Type <span>*</span></label>

                    <?php
                      $nadmission = $promoted = '';
                      if($edit->admission_type == 1){ $nadmission = 'selected'; }
                      else { $promoted = 'selected'; }
                    ?>
                    <select class="form-control" name="admission_type">
                      <option value="1" <?= $nadmission; ?>>New Admission</option>
                      <option value="2" <?= $promoted; ?>>Promoted</option>
                    </select>
                  </div>
                  <div class="row">
                    <div class="col-md-6">
                    <div class="form-group">
                          <label>Transport Required </label><br>
                          <label class="labelRadio">
                            <input type="radio" name="transport_required" value="1" <?= $edit->transport_required == 1 ? 'checked' : '' ?>> Yes
                          </label>
                          <label class="labelRadio">
                            <input type="radio" name="transport_required" value="0" <?= $edit->transport_required == '' || $edit->transport_required == 2 ? 'checked' : '' ?>> No
                          </label>
                  </div>
                    </div>
                    <div class="col-md-6">
                      <div class="form-group">
                            <label>Transport Type </label><br>
                            <label class="labelRadio">
                              <input type="radio" name="transport_type" value="1" <?= $edit->transport_type == 1 ? 'checked' : '' ?>> One Way
                            </label>
                            <label class="labelRadio">
                              <input type="radio" name="transport_type" value="2" <?= $edit->transport_type == '' || $edit->transport_type == 2 ? 'checked' : '' ?>> Two Way
                            </label>
                      </div>
                    </div>
                    <div class="col-md-6">
                <div class="form-group">
                      <label>Select Transport Fee </label><br>
                      <select class="form-control" name="transport_fee_id">
                        <?php foreach( $transportFeeInfo as $val){?>
                          <option value="<?= $val->id?>" <?= $edit->transport_fee_id == $val->id? "selected": '' ?>><?= $val->fee_name?> (<?= $val->distance?>) (<?= $val->amount?>Tk)</option>

                        <?php }?>
                      </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                    <label>Transport Activation Date </label><br>
                    <input class="form-control" type="date" name="transport_activation_date" value="<?= $edit->transport_activation_date ?>" placeholder="Transport Activation" >                
                </div>
              </div>
              </div>
                  
            </div>
            <!--middle-->
            <div class="col-md-6">
              <div class="form-group">
                <label>Facilities</label><br>
                <label class="labelRadio">
                  <input type="radio" name="facilities" value="None"  <?= $edit->facilities == '' || $edit->facilities == 'None' ? 'checked' : '' ?>> None &nbsp;
                </label>
                <label class="labelRadio">
                  <input type="radio" name="facilities" value="Scholarship" <?= $edit->facilities == 'Scholarship' ? 'checked' : '' ?>> Scholarship &nbsp;
                </label>
                <!-- <label class="labelRadio">
                  <input type="radio" name="facilities" value="Stipend" <?= @$edit->facilities == 'Stipend' ? 'checked' : '' ?>> Stipend &nbsp;
                </label> -->
                <label class="labelRadio">
                  <input type="radio" name="facilities" value="Full free" <?= $edit->facilities == 'Full free' ? 'checked' : '' ?>> Full free &nbsp;
                </label>
                <label class="labelRadio">
                  <input type="radio" name="facilities" value="Half free" <?= $edit->facilities == 'Half free' ? 'checked' : '' ?>> Half free &nbsp;
                </label>
                <!-- <label class="labelRadio">
                  <input type="radio" name="facilities" value="Disabled" <?= $edit->facilities == 'Disabled' ? 'checked' : '' ?>> Disabled
                </label> -->
              </div>
              <div class="form-group">
                <label>Facilities Activation Date</label>
                <input class="form-control" type="date" name="facilities_activation_date" placeholder="Facilities Activation" value="<?= $edit->facilities_activation_date ?>">
              </div>
              <div class="form-group">
                <label>Monthly Fee</label>
                <input class="form-control" type="number" name="monthly_fee" placeholder="Monthly Fee" value="<?= $edit->monthly_fee ?>">
              </div>
              <div class="form-group">
                <label>Nationality <span>*</span></label>
                <input class="form-control" type="text" name="stdNationality" placeholder="Nationality" value="<?= $edit->stdNationality ?>" required>
              </div>
              <div class="form-group">
                <label>Religion <span>*</span></label>
                <select class="form-control" name="stdReligion" required>
                  <option value="Muslim" <?= $edit->stdReligion == 'Muslim' ? 'selected' : ''  ?>>Muslim</option>
                  <option value="Hinduism" <?= $edit->stdReligion == 'Hinduism' ? 'selected' : ''  ?>>Hinduism</option>
                  <option value="Buddist" <?= $edit->stdReligion == 'Buddist' ? 'selected' : ''  ?>>Buddist</option>
                  <option value="Christian" <?= $edit->stdReligion == 'Christian' ? 'selected' : ''  ?>>Christian</option>
                  <option value="other" <?= $edit->stdReligion == 'other' ? 'selected' : ''  ?>>Other</option>
                </select>
              </div>
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Class <span>*</span></label> 
                    <input type="hidden" name="prevclass" value="<?= $edit->infoClass ?>">
                    <select id="admitClass" class="form-control" name="stdAdmitClass" required>
                      <?php
                      
                      echo "<option disabled selected value=''>Select a Class..</option>";
                      
                      $classes = $wpdb->get_results("SELECT classid,className FROM ct_class");
                      foreach ($classes as $class) {
                        $selected = ($edit->infoClass == $class->classid) ? 'selected' : '';
                        ?>
                        <option value='<?= $class->classid ?>' <?= $selected ?>>
                          <?= $class->className ?>
                        </option>
                        <?php
                      }
                      ?>
                    </select>
                </div>
              </div>
              <div class="col-md-6">
                <label>Year <span>*</span></label>
                <input type="hidden" name="prevYear" value="<?= $edit->stdCurntYear ?>">
                <select class="form-control" name="stdCurntYear" id="stdCurntYear" required>
                  <option value="">Select A Year..</option>
                   <?php 
                   if($edit->infoClass == 71 || $edit->infoClass == 72){
                  $current_year = date("Y");
                  
                  for ($i=-3; $i < 1; $i++) { 
                     $year = $current_year + $i;
                     $sec = $year . "-" . ($year + 1);
                    $selected = ($edit->stdCurntYear == $sec) ? 'selected' : '';
                    ?>
                      <option value="<?= $sec; ?>" <?= $selected; ?>><?= $sec; ?></option>
                    <?php
                  }}else{ ?>
                  <?php for ($i = -3; $i < 1; $i++) { 
                    $startYear = date("Y") - $i;
                    // $endYear = $startYear + 1;
                    // $value = $startYear . '-' . $endYear;
                    $selected = ($edit->stdCurntYear == $startYear) ? 'selected' : '';
                ?>
                  <option value="<?= $startYear; ?>" <?= $selected; ?>><?= $startYear; ?></option>
                <?php } ?>

                    <?php
                   }?>
                </select>
              </div>
            </div>

            <div class="row">

              <div class="form-group col-md-6">
                <label>Section <span>*</span></label>
                <?php 
                  $class = $edit->stdCurrentClass;
                  
                  // If teacher, only show their assigned section
                  if ($isTeacher && $teacherRestrictions) {
                    $sections = $wpdb->get_results( $wpdb->prepare(
                      "SELECT sectionid,sectionName FROM ct_section WHERE sectionid = %d AND forClass = %d",
                      $teacherRestrictions->teacherOfSection,
                      $teacherRestrictions->teacherOfClass
                    ));
                  } else {
                    $sections = $wpdb->get_results( "SELECT sectionid,sectionName FROM ct_section WHERE forClass = '$class'" );
                  }
                  
                  if(sizeof($sections) > 0){
                    ?>
                    <select class="form-control sectionSelect" name="stdSection" required>
                      <?php
                      
                      foreach ($sections as $section) {
                        $selected = ($edit->infoSection == $section->sectionid) ? 'selected' : '';
                        ?>
                        <option value="<?= $section->sectionid ?>" <?= $selected ?> ><?= $section->sectionName ?></option>
                        <?php
                      }
                      ?>
                    </select>
                  <?php }else{
                    echo "No section available for this class.";
                  } ?>
              </div>

              <div class="form-group col-md-6" id="stdGroupId" style="display: <?= $showGroup ? 'block' : 'none' ?>;">
                <label>Group</label>

                <select id="stdGroup" class="form-control" name="stdGroup">
                  <option value="0">Select A Group</option>
                  <?php
                    $groups = $wpdb->get_results("SELECT * FROM ct_group");
                    foreach ($groups as $groups) {
                      $selected = ($edit->infoGroup == $groups->groupId) ? 'selected' : '';
                      ?>
                      <option value='<?= $groups->groupId ?>' <?= $selected ?>>
                        <?= $groups->groupName ?>
                      </option>
                      <?php
                    }
                  ?>
                </select>
              </div>
            </div>

            
            <div class="form-group optionalSubDiv">
              <label>Optional Subject(s):</label><br>
              <?php 

                $class = $edit->stdCurrentClass;
                $group = $edit->infoGroup;
                $stdopt = json_decode($edit->infoOptionals);
            
                $subjects = $wpdb->get_results("SELECT subjectid,subjectName FROM ct_subject WHERE subjectClass = '$stdclass' AND (forGroup LIKE '%\"$group\"%' OR forGroup = 'all') AND subOptinal = 1 ORDER BY subjectName");

                if(!empty($subjects)){
                  foreach ($subjects as $subjct) {
                    $selected = '';
                    if (is_array($stdopt)) {
                      $selected = (in_array($subjct->subjectid, $stdopt)) ? 'checked' : '';
                    }
                    ?>
                    <label class="labelRadio">
                      <input type="checkbox" name="stdOptionals[]" value="<?= $subjct->subjectid; ?>" <?= $selected ?>> <?= $subjct->subjectName; ?>
                    </label>
                    <?php
                  }
                }
                echo "<br>";
                $std4th = @json_decode($edit->info4thSub);
                $subjects4th = $wpdb->get_results( "SELECT subjectid,subjectName FROM ct_subject WHERE subjectClass = '$stdclass' AND sub4th = 1 ORDER BY subjectName" );
                
                if(!empty($subjects4th)){
                  echo "<br><label>4th Subject</label><br>";

                  foreach ($subjects4th as $subjct) {
                      $selected = '';
                    if (is_array($std4th)) {
                      $selected = (in_array($subjct->subjectid, $std4th)) ? 'checked' : '';
                    }
                   
                    ?>
                    <label class="labelRadio">
                      <input type="checkbox" name="std4thsub[]" value="<?= $subjct->subjectid; ?>" <?= $selected ?>> <?= $subjct->subjectName; ?>
                    </label>
                    <?php
                  }
                }
              ?>
            </div>
            

            <div class="form-group">
              <label>Roll or ID NO: <span>*</span></label>
              <input id="stdRoll" data-std="<?= $edit->studentid ?>" class="form-control" type="text" name="stdRoll" placeholder="Roll or ID NO" value="<?= $edit->infoRoll ?>" required>
              <span class="warning text-danger"></span>
            </div>

            
            </div>
          </div>
        </div>
        
      </div>
      <!--</div>-->
      
      
      
      <div class="panel panel-default">
          <div class="panel-heading"><b><center> Guardian's Information</center></b>
          </div>
          <div class="panel-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <div class="row">
                              <div class="col-md-9">
                                <label>Father's Name <span>*</span></label>
                                <input class="form-control" type="text" name="stdFather" placeholder="Father's Name" value="<?= $edit->stdFather ?>" required>
                              </div>
                              <div class="col-md-3">
                                <label>Late ?</label><br>
                                <label class="labelRadio">
                                  <input type="checkbox" name="fatherLate" value="1" <?= $edit->fatherLate == 1 ? 'checked' : ''  ?>> Yes
                                </label>
                              </div>
                            </div>
                          </div>
                          <div class="form-group">
                            <label>Father Profession
                            </label>
                            <input class="form-control" type="text" name="stdFatherProf" placeholder="Father Profession" value="<?= $edit->stdFatherProf ?>">
                          </div>
                          <div class="form-group">
                            <label>Parental Monthly Income:
                            </label>
                            <input class="form-control" type="text" name="stdParentIncome" placeholder="Parental monthly income" value="<?= $edit->stdParentIncome ?>">
                          </div>
                          <div class="form-group">
                            <label>Guardian NID</label>
                            <input class="form-control" type="text" name="stdGuardianNID" placeholder="Guardian NID" value="<?= $edit->stdGuardianNID ?>">
                          </div>
                    </div>
                    <!--middle-->
                    <div class="col-md-6">
                      
                          <div class="form-group">
                            <div class="row">
                              <div class="col-md-9">
                                <label>Mother's Name <span>*</span></label>
                                <input class="form-control" type="text" name="stdMother" placeholder="Mother's Name" value="<?= $edit->stdMother ?>" required>
                              </div>
                              <div class="col-md-3">
                                <label>Late ?</label><br>
                                <label class="labelRadio">
                                  <input type="checkbox" name="motherLate" value="1" <?= $edit->fatherLate == 1 ? 'checked' : ''  ?>> Yes
                                </label>
                              </div>
                            </div>
                          </div>
                   
                          <div class="form-group">
                            <label>Mother's Profession
                            </label>
                            <input class="form-control" type="text" name="stdMotherProf" placeholder="Mother's Profession" value="<?= $edit->stdMotherProf ?>">
                          </div>
                          <div class="form-group">
                            <label>Local Guardian Name</label>
                            <input class="form-control" type="text" name="stdlocalGuardian" placeholder="Local Guardian Name" value="<?= $edit->stdlocalGuardian ?>">
                          </div>
                          <div class="form-group">
                            <label>Guardian Phone No:</label>
                            <input class="form-control" type="text" name="stdPhone" placeholder="Phone Number" value="<?= $edit->stdPhone ?>">
                          </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="panel panel-default">
          <div class="panel-heading"><b><center> Public Examination & Others Info Details</center></b>
          </div>
          <div class="panel-body">
              
                <div class="row">
                    <div class="col-md-6">
                        <div class="row">
                          <div class="form-group col-md-6">
                            <label>SSC Roll No</label>
                            <input class="form-control" type="text" name="sscRoll" placeholder="SSC Roll" value="<?= $edit->sscRoll ?>">
                          </div>
            
                          <div class="form-group col-md-6">
                            <label>SSC Registration No</label>
                            <input class="form-control" type="text" name="sscReg" placeholder="SSC Registration No" value="<?= $edit->sscReg ?>">
                          </div>
                        </div>
                        <div class="row">
                          <div class="col-md-6">
                            <div class="form-group">
                              <label>GPA:</label>
                              <input class="form-control" type="text" name="stdGPA" value="<?= $edit->stdGPA ?>">
                            </div>
                          </div>
                          <div class="col-md-6">
                            <div class="form-group">
                              <label>Letter Grade:</label>
                              <input class="form-control" type="text" name="stdIntellectual" value="<?= $edit->stdIntellectual ?>">
                            </div>
                          </div>
                        </div>
                      
                    </div>
                    <div class="col-md-6">
                     
                        <div class="row">
                             <div class="col-md-6">
                        <div class="form-group">
                          <label>Previous School Name</label>
                          <input class="form-control" type="text" name="stdPrevSchool" placeholder="Previous School Name" value="<?= $edit->stdPrevSchool ?>">
                        </div>
                        </div>
                        <div class="col-md-6">
                        <div class="form-group">
                          <label>TC Number</label>
                          <input class="form-control" type="text" name="stdTcNumber" placeholder="TC Number" value="<?= $edit->stdTcNumber ?>">
                        </div>
                        </div>
                        </div>
                        
                        <h4>
                          <strong>If got government scholarship</strong>
                        </h4>
                        <div class="row">
                          <div class="col-md-4">
                            <div class="form-group">
                              <label>In which class</label>
                              <input class="form-control" type="text" name="stdScholarsClass" value="<?= $edit->stdScholarsClass ?>">
                            </div>
                          </div>
                          <div class="col-md-4">
                            <div class="form-group">
                              <label>Year</label>
                              <input class="form-control" type="text" name="stdScholarsYear" value="<?= $edit->stdScholarsYear ?>">
                            </div>
                          </div>
                          <div class="col-md-4">
                            <div class="form-group">
                              <label>
                                <small>Memorandum No
                                </small>
                              </label>
                              <input class="form-control" type="text" name="stdScholarsMemo" value="<?= $edit->stdScholarsMemo ?>">
                            </div>
                          </div>
                      </div>
                    </div>
                </div>
                <div class="form-group">
                  <input class="btn btn-primary pull-right addStudentBtn" type="submit" name="updateStudent" value="Update">
                </div>
            </div>
        </div>
    </form>

    <?php
  }else{
    echo "<h3 class='text-center'>Somthing Wrong! Student not found.</h3>";
  }
}

/*===============
** Add Student
================*/
else{ ?>
  <form accept="" method="POST" class="applyForm fronendAdmin">

    <div class="panel panel-default">
      <div class="panel-heading"><b><center> Student Personal Information</center></b>
      </div>
      <div class="panel-body">
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>Student Name <span>*</span></label>
              <input class="form-control" type="text" name="stdName" placeholder="Student Name" required>
            </div>
            <div class="form-group">
              <label>ছাত্র/ছাত্রীর নাম (বাংলা)</label>
              <input class="form-control" type="text" name="stdNameBangla" placeholder="ছাত্র/ছাত্রীর নাম">
            </div>
            <div class="form-group">
              <label>Student Photo
              </label>
              <br>
              <div class="mediaUploadHolder">
                <button type="button" class="mediaUploader btn btn-info">Upload</button>
                <input class="hidden teacherImg" type="text" name="stdImg" >
              </div>
            </div>
            <div class="form-group">
              <label>Date Of Birth <span>*</span></label>
              <input class="form-control" type="date" name="stdBrith" placeholder="Date Of Birth" required>
            </div>
            <div class="form-group">
              <label>Birth Registration No:
              </label>
              <input class="form-control" type="text" name="birth_reg_no" placeholder="Birth Registration Number">
            </div>
            
              <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Gender <span>*</span></label>
                  <select class="form-control" name="stdGender">
                    <option value="1">Boy</option>
                    <option value="0">Girl</option>
                    <option value="2">Other</option>
                  </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Blood Group</label>
                  <select class="form-control" name="stdBldGrp">
                    <option>N/A</option>
                    <option>A+</option>
                    <option>A-</option>
                    <option>B+</option>
                    <option>B-</option>
                    <option>AB+</option>
                    <option>AB-</option>
                    <option>O+</option>
                    <option>O-</option>
                  </select>
                  
                </div>
              </div>
            </div>

            
            <div class="form-group">
              <label>Permanent Address:
              </label>
              <input class="form-control" type="text" name="stdPermanent" placeholder="Permanent Address">
            </div>
            <div class="form-group">
              <label>Present Address:
              </label>
              <input class="form-control" type="text" name="stdPresent" placeholder="Present Address">
            </div>
            <div class="form-group">
              <label>Admission Type <span>*</span></label>
              <select class="form-control" name="admission_type">
                <option value="1">New Admission</option>
                <option value="2">Promoted</option>
              </select>
            </div>
            <div class="row">
              <div class="col-md-6">
              <div class="form-group">
                    <label>Transport Required </label><br>
                    <label class="labelRadio">
                      <input type="radio" name="transport_required" value="1"> Yes
                    </label>
                    <label class="labelRadio">
                      <input type="radio" name="transport_required" value="0" checked> No
                    </label>
            </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                      <label>Transport Type </label><br>
                      <label class="labelRadio">
                        <input type="radio" name="transport_type" value="1"> One Way
                      </label>
                      <label class="labelRadio">
                        <input type="radio" name="transport_type" value="2" checked> Two Way
                      </label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                      <label>Select Transport Fee </label><br>
                      <select class="form-control" name="transport_fee_id">
                        <?php foreach( $transportFeeInfo as $val){?>
                          <option value="<?= $val->id?>"><?= $val->fee_name?> (<?= $val->distance?>) (<?= $val->amount?>Tk)</option>

                        <?php }?>
                      </select>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                <label>Transport Activation Date </label><br>
              <input class="form-control" type="date" name="transport_activation_date" placeholder="Transport Activation" >
                </div>
              </div>

            </div>
            
          </div>
          <!-- left side ends-->
          <div class="col-md-6">
            <div class="form-group">
              <label>Facilities</label><br>
              <label class="labelRadio">
                <input type="radio" name="facilities" value="None" checked> None &nbsp;
              </label>
              <label class="labelRadio">
                <input type="radio" name="facilities" value="Scholarship"> Scholarship &nbsp;
              </label>
              <!-- <label class="labelRadio">
                <input type="radio" name="facilities" value="Stipend"> Stipend &nbsp;
              </label> -->
              <label class="labelRadio">
                <input type="radio" name="facilities" value="Full free"> Full free &nbsp;
              </label>
              <label class="labelRadio">
                <input type="radio" name="facilities" value="Half free"> Half free &nbsp;
              </label>
              <!-- <label class="labelRadio">
                <input type="radio" name="facilities" value="Disabled"> Disabled
              </label> -->
            </div>
            <div class="form-group">
              <label>Facilities Activation Date<span></span></label>
              <input class="form-control" type="date" name="facilities_activation_date" placeholder="Facilities Activation" >
            </div>
            <div class="form-group">
                <label>Monthly Fee</label>
                <input class="form-control" type="number" name="monthly_fee" placeholder="Monthly Fee">
              </div>
            <div class="form-group">
              <label>Nationality <span>*</span></label>
              <input class="form-control" type="text" name="stdNationality" placeholder="Nationality" value="Bangladeshi" required>
            </div>
            <div class="form-group">
              <label>Religion <span>*</span></label>
              <select class="form-control" name="stdReligion" required>
                <option value="Muslim">Muslim
                </option>
                <option value="Hinduism">Hinduism
                </option>
                <option value="Buddist">Buddist
                </option>
                <option value="Christian">Christian
                </option>
                <option value="other">Other
                </option>
              </select>
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Class <span>*</span></label>
                  <select id="admitClass" class="form-control" name="stdAdmitClass" required>
                    <?php
                    
                    echo "<option disabled selected value=''>Select a Class..</option>";
                    
                    // If teacher, only show their assigned class
                    if ($isTeacher && $teacherRestrictions) {
                      $classes = $wpdb->get_results( $wpdb->prepare(
                        "SELECT classid,className FROM ct_class WHERE classid = %d",
                        $teacherRestrictions->teacherOfClass
                      ));
                    } else {
                      $classes = $wpdb->get_results("SELECT classid,className FROM ct_class");
                    }
                    
                    foreach ($classes as $class) {
                      ?>
                      <option value='<?= $class->classid ?>'>
                        <?= $class->className ?>
                      </option>
                      <?php
                    }
                    ?>
                  </select>
              </div>
            </div>
            <div class="col-md-6">
              <label>Year/Session <span>*</span></label>
              <select class="form-control" name="stdCurntYear" id="stdCurntYear" required>
                <option value="">Select Class First</option>
              </select>
            </div>
          </div>

          <div class="form-group" id="stdGroupId" style="display:none;">
            <label>Group</label>

            <select id="stdGroup" class="form-control" name="stdGroup">
              <option value="0" selected>Select A Group</option>
              <?php
                $groups = $wpdb->get_results("SELECT * FROM ct_group");
                foreach ($groups as $groups) {
                  ?>
                  <option value='<?= $groups->groupId ?>'>
                    <?= $groups->groupName ?>
                  </option>
                  <?php
                }
              ?>
            </select>
          </div>

          <!-- If Optional (Value will come by Ajax) -->
          <div class="form-group optionalSubDiv">
          </div>
          <div class="form-group sectionDiv">
            <label>Section <span>*</span></label>
            <select class="form-control sectionSelect" name="stdSection" required>
            </select>
          </div>

          <div class="form-group">
            <label>Roll or ID NO: <span>*</span></label>
            <input id="stdRoll" datd-std='x' class="form-control" type="text" name="stdRoll" placeholder="Roll or ID No" required>
            <span class="warning text-danger"></span>
          </div>
          
        </div>
        <!--right side ends-->
      </div>
      
        

    </div>
    </div>
    <div class="panel panel-default">
          <div class="panel-heading"><b><center> Guardian Information</center></b>
          </div>
          <div class="panel-body">
                <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                          <div class="row">
                            <div class="col-md-9">
                              <label>Father's Name <span>*</span></label>
                              <input class="form-control" type="text" name="stdFather" placeholder="Father Name" required>
                            </div>
                            <div class="col-md-3">
                              <label>Late ?</label><br>
                              <label class="labelRadio">
                                <input type="checkbox" name="fatherLate"> Yes
                              </label>
                            </div>
                          </div>
                        </div>
                        <div class="form-group">
                          <label>Father Profession
                          </label>
                          <input class="form-control" type="text" name="stdFatherProf" placeholder="Father Profession">
                        </div>
                        <div class="form-group">
                          <label>Parental Monthly Income
                          </label>
                          <input class="form-control" type="text" name="stdParentIncome" placeholder="Parental monthly income">
                        </div>
                        <div class="form-group">
                          <label>Guardian NID</label>
                          <input class="form-control" type="text" name="stdGuardianNID" placeholder="Guardian NID" >
                        </div>
                    </div>
                    <!--middle-->
                    <div class="col-md-6">
                       <div class="form-group">
                          <div class="row">
                            <div class="col-md-9">
                              <label>Mother's Name <span>*</span></label>
                              <input class="form-control" type="text" name="stdMother" placeholder="Mother Name" required>
                            </div>
                            <div class="col-md-3">
                              <label>Late ?</label><br>
                              <label class="labelRadio">
                                <input type="checkbox" name="motherLate"> Yes
                              </label>
                            </div>
                          </div>
                        </div>
                 
                        <div class="form-group">
                          <label>Mother's Profession
                          </label>
                          <input class="form-control" type="text" name="stdMotherProf" placeholder="Mother Profession">
                        </div>
                        
                        <div class="form-group">
                          <label>Local Guardian Name</label>
                          <input class="form-control" type="text" name="stdlocalGuardian" placeholder="Local Guardian Name">
                        </div>
                        <div class="form-group">
                          <label>Guardian Phone Number
                          </label>
                          <input class="form-control" type="text" name="stdPhone" placeholder="Guardian Phone Number">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="panel panel-default">
          <div class="panel-heading"><b><center> Public Examination & Others Info Details</center></b>
          </div>
          <div class="panel-body">
              
                <div class="row">
                    <div class="col-md-6">
                       
                     <div class="row">
                        <div class="form-group col-md-6">
                          <label>SSC Roll No</label>
                          <input class="form-control" type="text" name="sscRoll" placeholder="SSC Roll No">
                        </div>
            
                        <div class="form-group col-md-6">
                          <label>SSC Registration No</label>
                          <input class="form-control" type="text" name="sscReg" placeholder="SSC Registration No">
                        </div>
                      </div>

                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>GPA:
                            </label>
                            <input class="form-control" type="text" name="stdGPA">
                          </div>
                        </div>
                        <div class="col-md-6">
                          <div class="form-group">
                            <label>Letter Grade:
                            </label>
                            <input class="form-control" type="text" name="stdIntellectual">
                          </div>
                        </div>
                      </div>
            
                      
                    </div>
                    <div class="col-md-6">
                         <div class="row">
                             <div class="col-md-6">
                                 <div class="form-group">
                                    <label>Previous School Name</label>
                                    <input class="form-control" type="text" name="stdPrevSchool" placeholder="Previous School Name">
                                 </div>
                             </div>
                              <div class="col-md-6">
                                  <div class="form-group">
                                    <label>TC Number</label>
                                    <input class="form-control" type="text" name="stdTcNumber" placeholder="TC Number">
                                  </div>
                                </div>
                          </div>
                    
                      <h4>
                        <strong>If got government scholarship
                        </strong>
                      </h4>
                      <div class="row">
                        <div class="col-md-4">
                          <div class="form-group">
                            <label>In which class
                            </label>
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
                            <label>
                              <small>Memorandum No
                              </small>
                            </label>
                            <input class="form-control" type="text" name="stdScholarsMemo">
                          </div>
                        </div>
                      </div>
                    </div>
                </div>
                <div class="form-group">
                <input class="btn btn-primary pull-right addStudentBtn" type="submit" name="addStudent" value="Add">
              </div>
            </div>
        </div>
  </form> 
<?php } ?>


<script type="text/javascript">
  (function($) {
  	$('#stdGroup').change(function(event) {
    //   $('#stdRoll').val('');
      var $siteUrl = $('#theSiteURL').text();
      $.ajax({
        url: $siteUrl+"/inc/ajaxAction.php",
        method: "POST",
        data: { class : $('#admitClass').val(), group: $('#stdGroup').val(), type : 'getOpt4thSubjectByGroup' },
        dataType: "html"
      }).done(function( msg ) {
        $( ".optionalSubDiv" ).html( msg );
      });
    });
  	
  	$('#admitClass').change(function(event) {
      $('#stdRoll').val('');
var religionName = $('select[name="stdReligion"]').val();
var selectedGroup= $('select[name="stdGroup"]').val();

      $data = { class : $(this).val(), group: selectedGroup,  stdReligion : religionName, type : 'getOptionalSubject' }; 
      if (selectedGroup > 0) { 
      	$data = { class :  $('#admitClass').val(), group: $('#stdGroup').val(), type : 'getOpt4thSubjectByGroup' };
      }

      var $siteUrl = $('#theSiteURL').text();
      $classdata = { class : $(this).val(), type : 'hasGroup' };
      $.ajax({
          url: $siteUrl + "/inc/ajaxAction.php",
          method: "POST",
          data: $classdata,
          dataType: "html"
        }).done(function(msg) {
          if (msg === 'true') {
            $("#stdGroupId").show();
          } else {
            $("#stdGroupId").hide();
          }
        });
      
      $.ajax({
        url: $siteUrl+"/inc/ajaxAction.php",
        method: "POST",
        data: $data,
        dataType: "html"
      }).done(function( msg ) {
        $( ".optionalSubDiv" ).html( msg );
      });

      $.ajax({
        url: $siteUrl+"/inc/ajaxAction.php",
        method: "POST",
        data: { class : $(this).val(), type : 'getSection' },
        dataType: "html"
      }).done(function( msg ) {
        if (msg == 0) {
          $( ".sectionDiv" ).hide();
          $( ".sectionDiv .sectionSelect" ).removeAttr('required');
        }else{
          $( ".sectionDiv" ).show();
          $( ".sectionDiv .sectionSelect" ).attr('required', 'required');
        }
        $( ".sectionSelect" ).html( msg );
        $(this).data('')
      });

      $.ajax({
        url: $siteUrl+"/inc/ajaxAction.php",
        method: "POST",
        data: { class : $(this).val(), type : 'getYearSection' },
        dataType: "html"
      }).done(function( msg ) {
        $( "#stdCurntYear" ).html( msg );
      });

    });

    $('.stdView .panel-title a').click(function(event) {
      var $this = $(this);
      var $siteUrl = $('#theSiteURL').text();
      if($this.hasClass('done')){

      }else{
        $this.addClass('done');
        var $class    = $this.data('class');
        var $section  = $this.data('section');
        var $year     = $this.data('year');

        $.ajax({
          url: $('#theSiteURL').text()+"/inc/ajaxAction.php",
          method: "POST",
          data: { year : $year, class : $class, section : $section, type : 'getAllStudentByClass', siteUrl : $siteUrl },
          dataType: "html"
        }).done(function( msg ) {
          $this.closest('.panel').find('.panel-body').html(msg);
        });

      }

    });
  })( jQuery );
</script>