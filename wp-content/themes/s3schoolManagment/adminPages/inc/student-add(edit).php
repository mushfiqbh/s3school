<?php 
if (!function_exists('s3s_format_dob_display')) {
  function s3s_format_dob_display($value)
  {
    if (empty($value)) {
      return '';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
      return $value;
    }

    return date('d/m/Y', $timestamp);
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
      $result = $wpdb->get_row("SELECT havegroup, haveShift FROM ct_class WHERE classid = '$stdclass'");
      if ($result && $result->havegroup == 1) {
        $showGroup = true;
      }
      $showShift = ($result && isset($result->haveShift) && $result->haveShift == 1);
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
                <?php
                  $stdDobIso = $edit->stdBrith;
                  $stdDobDisplay = s3s_format_dob_display($stdDobIso);
                ?>
                <div class="dob-input-wrapper">
                  <input class="form-control dob-text" type="text" name="stdBrith_text" placeholder="dd/mm/yyyy or dd-mm-yyyy" value="<?= esc_attr($stdDobDisplay); ?>">
                  <input class="form-control dob-picker" type="date" name="stdBrith_picker" value="<?= esc_attr($stdDobIso); ?>" aria-label="Pick date from calendar">
                  <input class="dob-hidden" type="hidden" name="stdBrith" value="<?= esc_attr($stdDobIso); ?>">
                </div>
                <small class="text-muted dob-note">Enter DD/MM/YYYY or DD-MM-YYYY, or use the picker.</small>
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
                      <!-- Transport fields removed -->
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
              
              
              <?php 
              
              $sessionTypeQuery = "SELECT session FROM ct_class WHERE classid=$edit->infoClass";
              $sessionType = $wpdb->get_var($sessionTypeQuery);
              
              ?>

              <div class="col-md-6">
                <label>Year <span>*</span></label>
                <input type="hidden" name="prevYear" value="<?= $edit->stdCurntYear ?>">
                <select class="form-control" name="stdCurntYear" id="stdCurntYear" required>
                  <option value="">Select A Year..</option>
                   <?php 
                   if($sessionType == 'session'){
                  $current_year = date("Y");
                  
                  for ($i=-3; $i < 5; $i++) { 
                     $year = $current_year + $i;
                     $sec = $year . "-" . ($year + 1);
                    $selected = ($edit->stdCurntYear == $sec) ? 'selected' : '';
                    ?>
                      <option value="<?= $sec; ?>" <?= $selected; ?>><?= $sec; ?></option>
                    <?php
                  }}else{ ?>
                  <?php for ($i = -3; $i < 5; $i++) { 
                    $startYear = date("Y") + $i;
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
                  
                  $sections_query = "SELECT sectionid,sectionName FROM ct_section WHERE forClass = '$class'";
                  $allowed_sections = array();
                  $has_all = false;
                  
                  $sections = $wpdb->get_results($sections_query);
                  
                  if(sizeof($sections) > 0 || $has_all){
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

              <div class="form-group col-md-6" id="stdShiftId" style="display: <?= $showShift ? 'block' : 'none' ?>;">
                <label>Shift</label>
                <select name="stdShift" class="form-control">
                  <option value="">Select Shift</option>
                  <option value="Morning" <?= (isset($edit->stdShift) && $edit->stdShift == 'Morning') ? 'selected' : '' ?>>Morning</option>
                  <option value="Day" <?= (isset($edit->stdShift) && $edit->stdShift == 'Day') ? 'selected' : '' ?>>Day</option>
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
                if (!is_array($std4th)) {
                    $std4th = empty($edit->info4thSub) ? [] : [$edit->info4thSub];
                }
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
                            <label>Father's Profession
                            </label>
                            <input class="form-control" type="text" name="stdFatherProf" placeholder="Father Profession" value="<?= $edit->stdFatherProf ?>">
                          </div>
                          <div class="form-group">
                            <label>Father's NID</label>
                            <input class="form-control" type="text" name="stdFatherNID" placeholder="Father NID" value="<?= isset($edit->stdFatherNID) ? $edit->stdFatherNID : '' ?>">
                          </div>
                          <div class="form-group">
                            <label>Parental Monthly Income:
                            </label>
                            <input class="form-control" type="text" name="stdParentIncome" placeholder="Parental monthly income" value="<?= $edit->stdParentIncome ?>">
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
                            <label>Mother's NID</label>
                            <input class="form-control" type="text" name="stdMotherNID" placeholder="Mother's NID" value="<?= isset($edit->stdMotherNID) ? $edit->stdMotherNID : '' ?>">
                          </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="panel panel-default">
          <div class="panel-heading"><b><center>Contact Info & Address</center></b></div>
          <div class="panel-body">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Present Address</label>
                  <input class="form-control" type="text" name="stdPresent" value="<?= isset($edit->stdPresent) ? $edit->stdPresent : '' ?>" placeholder="Present Address">
                </div>
                <div class="form-group">
                  <label>Permanent Address</label>
                  <input class="form-control" type="text" name="stdPermanent" value="<?= isset($edit->stdPermanent) ? $edit->stdPermanent : '' ?>" placeholder="Permanent Address">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Phone</label>
                  <input class="form-control" type="text" name="stdPhone" value="<?= isset($edit->stdPhone) ? $edit->stdPhone : '' ?>" placeholder="Phone">
                </div>
                <div class="form-group">
                  <label>Emergency Phone 2</label>
                  <input class="form-control" type="text" name="stdEmergencyPhone" value="<?= isset($edit->stdEmergencyPhone) ? $edit->stdEmergencyPhone : '' ?>" placeholder="Emergency Phone 2">
                </div>
                <div class="form-group">
                  <label>Email</label>
                  <input class="form-control" type="email" name="stdEmail" value="<?= isset($edit->stdEmail) ? $edit->stdEmail : '' ?>" placeholder="Email">
                </div>
              </div>
            </div>
          </div>
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
              <label>Date Of Birth</label>
              <div class="dob-input-wrapper">
                <input class="form-control dob-text" type="text" name="stdBrith_text" placeholder="dd/mm/yyyy or dd-mm-yyyy">
                <input class="form-control dob-picker" type="date" name="stdBrith_picker" aria-label="Pick date from calendar">
                <input class="dob-hidden" type="hidden" name="stdBrith" value="">
              </div>
              <small class="text-muted dob-note">Enter DD/MM/YYYY or DD-MM-YYYY, or use the picker.</small>
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
              <label>Admission Type <span>*</span></label>
              <select class="form-control" name="admission_type">
                <option value="1">New Admission</option>
                <option value="2">Promoted</option>
              </select>
            </div>
            <div class="row">
                <!-- Transport fields removed -->

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
                    
                      $classes = $wpdb->get_results("SELECT classid,className FROM ct_class");
                    
                    
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

          <div class="form-group" id="stdShiftId" style="display:none;">
            <label>Shift</label>
            <select name="stdShift" class="form-control">
              <option value="">Select Shift</option>
              <option value="Morning">Morning</option>
              <option value="Day">Day</option>
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
                          <label>Father's Profession
                          </label>
                          <input class="form-control" type="text" name="stdFatherProf" placeholder="Father Profession">
                        </div>
                        <div class="form-group">
                           <label>Father's NID</label>
                          <input class="form-control" type="text" name="stdFatherNID" placeholder="Father NID" >
                        </div>
                        <div class="form-group">
                          <label>Parental Monthly Income
                          </label>
                          <input class="form-control" type="text" name="stdParentIncome" placeholder="Parental monthly income">
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
                          <label>Mother's NID</label>
                          <input class="form-control" type="text" name="stdMotherNID" placeholder="Mother's NID" >
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="panel panel-default">
          <div class="panel-heading"><b><center>Contact Info & Address</center></b></div>
          <div class="panel-body">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Present Address</label>
                  <input class="form-control" type="text" name="stdPresent" placeholder="Present Address">
                </div>
                <div class="form-group">
                  <label>Permanent Address</label>
                  <input class="form-control" type="text" name="stdPermanent" placeholder="Permanent Address">
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Phone</label>
                  <input class="form-control" type="text" name="stdPhone" placeholder="Phone">
                </div>
                <div class="form-group">
                  <label>Emergency Phone</label>
                  <input class="form-control" type="text" name="stdEmergencyPhone" placeholder="Phone 2">
                </div>
                <div class="form-group">
                  <label>Email</label>
                  <input class="form-control" type="email" name="stdEmail" placeholder="Email">
                </div>
              </div>
            </div>
          </div>
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


<style>
  .dob-input-wrapper {
    display: flex;
    gap: 10px;
    align-items: center;
    flex-wrap: wrap;
  }

  .dob-input-wrapper .dob-text {
    flex: 1 1 220px;
    min-width: 200px;
  }

  .dob-input-wrapper .dob-picker {
    flex: 0 0 160px;
    min-width: 150px;
  }

  .dob-note {
    display: block;
    margin-top: 5px;
  }

  .dob-text.dob-error {
    border-color: #a94442;
  }
</style>


<script type="text/javascript">
  (function($) {
    function s3sPad(num) {
      var n = parseInt(num, 10);
      if (isNaN(n)) {
        return '';
      }
      return (n < 10 ? '0' : '') + n;
    }

    function s3sIsoFromParts(day, month, year) {
      var d = parseInt(day, 10);
      var m = parseInt(month, 10);
      var y = parseInt(year, 10);
      if (isNaN(d) || isNaN(m) || isNaN(y)) {
        return '';
      }
      var jsDate = new Date(y, m - 1, d);
      if (jsDate.getFullYear() !== y || jsDate.getMonth() !== (m - 1) || jsDate.getDate() !== d) {
        return '';
      }
      return [y, s3sPad(m), s3sPad(d)].join('-');
    }

    function s3sDisplayFromIso(iso) {
      if (!iso) {
        return '';
      }
      var parts = iso.split('-');
      if (parts.length === 3) {
        return [s3sPad(parts[2]), s3sPad(parts[1]), parts[0]].join('/');
      }
      var parsed = new Date(iso);
      if (!isNaN(parsed.getTime())) {
        return [s3sPad(parsed.getDate()), s3sPad(parsed.getMonth() + 1), parsed.getFullYear()].join('/');
      }
      return iso;
    }

    function s3sParseDobInput(raw) {
      if (!raw) {
        return '';
      }
      var normalized = raw.trim();
      if (!normalized) {
        return '';
      }
      normalized = normalized.replace(/\./g, '/').replace(/-/g, '/');
      var parts = normalized.split('/');
      if (parts.length === 3) {
        return s3sIsoFromParts(parts[0], parts[1], parts[2]);
      }
      return '';
    }

    function s3sInitDobFields() {
      var $wrappers = $('.dob-input-wrapper');
      if (!$wrappers.length) {
        return;
      }


      $wrappers.each(function() {
        var $wrap = $(this);
        var $text = $wrap.find('.dob-text');
        var $picker = $wrap.find('.dob-picker');
        var $hidden = $wrap.find('.dob-hidden');
        var initialIso = $hidden.val() || $picker.val() || '';

        if (initialIso) {
          $text.val(s3sDisplayFromIso(initialIso));
          $picker.val(initialIso);
        }

        function setValidity(message) {
          if ($text.length && typeof $text[0].setCustomValidity === 'function') {
            $text[0].setCustomValidity(message || '');
          }
          $text.toggleClass('dob-error', Boolean(message));
        }

        function syncFromText(isSubmit) {
          var value = $text.val().trim();
          if (value === '' || value == '00/00/0000' || value == '00-00-0000') {
            $hidden.val('');
            if ($picker.length) {
              $picker.val('');
            }
            setValidity('');
            return true;
          }
          var iso = s3sParseDobInput(value);
          if (iso) {
            $hidden.val(iso);
            if ($picker.length) {
              $picker.val(iso);
            }
            setValidity('');
            return true;
          }
          if (isSubmit) {
            setValidity('Use DD/MM/YYYY or DD-MM-YYYY.');
          } else {
            setValidity('Use DD/MM/YYYY or DD-MM-YYYY.');
          }
          return false;
        }

        $text.on('blur change', function() {
          if (!$text.val().trim()) {
            // No longer required, so just clear error
            setValidity('');
            return;
          }
          syncFromText(false);
        });

        $picker.on('change input', function() {
          var iso = $(this).val();
          if (iso) {
            $hidden.val(iso);
            $text.val(s3sDisplayFromIso(iso));
            setValidity('');
          }
        });

        $wrap.data('s3sValidateDob', function(isSubmit) {
          return syncFromText(Boolean(isSubmit));
        });
      });

      $('.applyForm').off('submit.s3sDob').on('submit.s3sDob', function(event) {
        // No longer required, so skip DOB validation for required
        // But still validate format if filled
        var formValid = true;
        $(this).find('.dob-input-wrapper').each(function() {
          var validator = $(this).data('s3sValidateDob');
          if (typeof validator === 'function') {
            var ok = validator(true);
            if (!ok) {
              formValid = false;
              $(this).find('.dob-text').focus();
              return false;
            }
          }
        });
        if (!formValid) {
          event.preventDefault();
          event.stopImmediatePropagation();
        }
      });
    }

    s3sInitDobFields();

	$('#stdGroup').change(function(event) {
      var $siteUrl = $('#theSiteURL').text();
      $.ajax({
        url: "",
        method: "POST",
        data: { class : $('#admitClass').val(), group: $('#stdGroup').val(), type : 'getOpt4thSubjectByGroup' },
        dataType: "html"
      }).done(function( msg ) {
        $( ".optionalSubDiv" ).html( msg );
      });
    });
  	
  	$('#admitClass').change(function(event) {
      $('#stdRoll').val('');
      var selectedGroup= $('select[name="stdGroup"]').val();

      $data = { class : $(this).val(), group: selectedGroup, type : 'getOptionalSubject' }; 
      if (selectedGroup > 0) { 
      	$data = { class :  $('#admitClass').val(), group: $('#stdGroup').val(), type : 'getOpt4thSubjectByGroup' };
      }

      var $siteUrl = $('#theSiteURL').text();
      $classdata = { class : $(this).val(), type : 'hasGroup' };
      $.ajax({
          url: "",
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
      url: "",
        method: "POST",
        data: { class : $(this).val(), type : 'hasShift' },
        dataType: "html"
      }).done(function(msg) {
        if (msg === 'true') {
          $("#stdShiftId").show();
        } else {
          $("#stdShiftId").hide();
        }
      });
      
      $.ajax({
        url: "",
        method: "POST",
        data: $data,
        dataType: "html"
      }).done(function( msg ) {
        $( ".optionalSubDiv" ).html( msg );
      });

      $.ajax({
        url: "",
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
        url: "",
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
          url: "",
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

<?php
// ==============================================
// FIX 409 CONFLICT - HANDLE AJAX ACTIONS LOCALLY
// ==============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'])) {

  // Clean output buffer to ensure JSON/HTML response is valid
  while (ob_get_level()) {
    ob_end_clean();
  }

  // ------------------------------------------
  // Get Section
  // ------------------------------------------
  if ($_POST['type'] == 'getSection') {
    $class = $_POST['class'];
    $sections_query = "SELECT sectionid,sectionName FROM ct_section WHERE forClass = '$class'";
    
    $sections_query .= " ORDER BY sectionName";
    $sections = $wpdb->get_results($sections_query);

    if (!empty($sections)) {
      echo "<option value=''>Section</option>";
      foreach ($sections as $section) {
        echo "<option value='{$section->sectionid}'>{$section->sectionName}</option>";
      }
    } else {
      echo "<option value=''>No sections available</option>";
    }
    exit;
  }


  // ------------------------------------------
  // Get Optional and 4th sub Subject
  // ------------------------------------------
  elseif ($_POST['type'] == 'getOptionalSubject' || $_POST['type'] == 'getOpt4thSubjectByGroup') {
    $class = $_POST['class'];
    $group = (empty($_POST['group']) || $_POST['group'] == 0) ? 'all' : $_POST['group'];

    $subjects = $wpdb->get_results("
          SELECT subjectid, subjectName 
          FROM ct_subject 
          WHERE subjectClass = '$class' 
            AND subOptinal = 1 
            AND (forGroup IN ('$group', 'all') OR forGroup LIKE '%\"$group\"%') 
          ORDER BY subjectName
      ");

    if (!empty($subjects)) {
      echo "<label>Optional Subject(s)</label><br>";
    }

    foreach ($subjects as $subjct) {
      echo '<label class="labelRadio"><input type="checkbox" name="stdOptionals[]" value="' . $subjct->subjectid . '" checked> ' . $subjct->subjectName . '</label>';
    }

    $subjects4th = $wpdb->get_results("SELECT subjectid,subjectName FROM ct_subject WHERE subjectClass = '$class' AND sub4th = 1 AND (forGroup IN ('$group', 'all') OR forGroup LIKE '%\"$group\"%') ORDER BY subjectName");

    if (!empty($subjects4th)) {
      echo "<br><br><label>4th Subject</label><br>";
    }

    $first = true;
    foreach ($subjects4th as $subjct) {
      $checked = $first ? 'checked' : '';
      echo '<label class="labelRadio"><input type="checkbox" name="std4thsub[]" value="' . $subjct->subjectid . '" ' . $checked . '> ' . $subjct->subjectName . '</label>';
      $first = false;
    }
    exit;
  }

  // ------------------------------------------
  // Get Year Section
  // ------------------------------------------
  elseif ($_POST['type'] == 'getYearSection') {
    $classid = $_POST['class'];
    $subs = $wpdb->get_results("SELECT session FROM ct_class WHERE classid = $classid LIMIT 1");
    $session = isset($subs[0]->session) ? $subs[0]->session : '';

    $options = '';
    $currentYear = date("Y");
    if ($session == 'year') {
      for ($i = -3; $i < 7; $i++) {
        $sec = (date("Y") - $i);
        $selected = ($currentYear == $sec) ? 'selected' : '';
        $options .= "<option value='$sec' $selected>$sec</option>";
      }
    } else {
      $currentYear = date("Y") . "-" . (date("Y") + 1);
      for ($i = -3; $i < 7; $i++) {
        $sec = (date("Y") - ($i + 1)) . "-" . (date("Y") - $i);
        $selected = ($currentYear == $sec) ? 'selected' : '';
        $options .= "<option value='$sec' $selected>$sec</option>";
      }
    }
    echo $options;
    exit;
  }

  // ------------------------------------------
  // Has Group
  // ------------------------------------------
  elseif ($_POST['type'] == 'hasGroup') {
    $class = $_POST['class'];
    $classInfo = $wpdb->get_results("SELECT havegroup FROM ct_class WHERE classid = '$class' ");
    if (!empty($classInfo) && $classInfo[0]->havegroup == 1) {
      echo "true";
    } else {
      echo "false";
    }
    exit;
  }

  // ------------------------------------------
  // Has Shift
  // ------------------------------------------
  elseif ($_POST['type'] == 'hasShift') {
    $class = $_POST['class'];
    $classInfo = $wpdb->get_results("SELECT haveShift FROM ct_class WHERE classid = '$class' ");
    if (!empty($classInfo) && isset($classInfo[0]->haveShift) && $classInfo[0]->haveShift == 1) {
      echo "true";
    } else {
      echo "false";
    }
    exit;
  }

  // ------------------------------------------
  // Get All Student By Class
  // ------------------------------------------
  elseif ($_POST['type'] == 'getAllStudentByClass') {
    $class     = $_POST['class'];
    $year     = $_POST['year'];
    $section   = $_POST['section'];
?>
    <table class="table table-bordered table-responsive">
      <thead>
        <tr>
          <th>Name</th>
          <th>Group</th>
          <th>Roll</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $students = $wpdb->get_results("SELECT studentid,stdName,stdRoll,groupName FROM ct_student
                    LEFT JOIN ct_group ON ct_student.stdGroup = ct_group.groupId 
                    WHERE ct_student.stdAdmitClass = $class AND ct_student.stdCurntYear = '$year' AND ct_student.stdSection = $section AND ct_student.stdStatus = 1 ORDER BY ct_student.stdRoll ASC");

        foreach ($students as $student) {
        ?>
          <tr>
            <td><?= $student->stdName; ?></td>
            <td><?= $student->groupName; ?></td>
            <td><?= $student->stdRoll; ?></td>
            <td>
              <form class="pull-right actionForm" method="POST" action="">
                <input type="hidden" name="id" value="<?= $student->studentid; ?>">
                <a href="?page=student&option=view&id=<?= $student->studentid; ?>" class="btn-link">
                  <span class="dashicons dashicons-visibility"></span></span>
                </a>
                <button type="submit" class="btn-link btnDelete" name="deleteStudent">
                  <span class="dashicons dashicons-trash"></span>
                </button>
              </form>
            </td>
          </tr>
        <?php
        }
        ?>
      </tbody>
    </table>
<?php
    exit;
  }
}
?>
