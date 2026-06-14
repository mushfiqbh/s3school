<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.4/jspdf.min.js"></script>
<script src="https://unpkg.com/jspdf-autotable@3.5.22/dist/jspdf.plugin.autotable.js"></script>
<?php
global $wpdb, $s3sRedux;

require_once dirname(__DIR__) . '/functions/teacher-access.php';

$teacherAccess = s3s_get_teacher_access_context();
$isTeacher = $teacherAccess['is_teacher'];
$teacherRestrictions = $teacherAccess['restrictions'];
$hasAssignedClass = $teacherAccess['has_assignment'];

$yearGroup = $wpdb->get_results("SELECT stdCurntYear FROM ct_student GROUP BY stdCurntYear");
$classGroup = $wpdb->get_results("SELECT classid,className FROM ct_student
    LEFT JOIN ct_class ON ct_class.classid = ct_student.stdAdmitClass
    GROUP BY stdAdmitClass");

$admitYear = isset($_POST['filter']) ? $_POST['filter'] : date("Y");
?>
<div class="panel panel-info">
  <div class="panel-heading">
    <?php $class =  (isset($_POST['stdclass'])) ? $_POST['stdclass'] : '' ?>
    <?php $year =  (isset($_POST['stdyear'])) ? $_POST['stdyear'] : '' ?>
    <h3>
      Students <?= (isset($_POST['stdyear'])) ? '(' . $clsName . ', ' . $year . ' )' : '' ?> <br>
      <small>Search For Students</small>
    </h3>
  </div>
  <div class="panel-body">
    <div class="panel-group stdView">
      <form action="" method="GET" class="form-inline">
        <input type="hidden" name="page" value="student">
        <div class="form-group">
          <label>Class</label>
          <select id='resultClass' class="form-control" name="stdclass" required>
            <?php

            // If teacher, only show their assigned class
            if ($isTeacher && $hasAssignedClass && $teacherRestrictions) {
              $classQuery = $wpdb->get_results($wpdb->prepare(
                "SELECT classid,className FROM ct_class WHERE classid = %d ORDER BY className ASC",
                $teacherRestrictions->teacherOfClass
              ));
            } else {
              $classQuery = $wpdb->get_results("SELECT classid,className FROM ct_class  ORDER BY className ASC");
            }

            echo "<option value=''>Select Class</option>";

            foreach ($classQuery as $class) {
              echo "<option value='" . $class->classid . "'>" . $class->className . "</option>";
            }
            ?>
          </select>
        </div>

        <div class="form-group ">
          <label>Section</label>
          <select id="resultSection" class="form-control" name="sec" disabled>
            <option disabled selected>Select Class First</option>
          </select>
        </div>

        <div class="form-group ">
          <label>Group</label>
          <select id="resultGroup" class="form-control" name="group">
            <option value="">Select Group</option>
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

        <div class="form-group">
          <label>Year</label>
          <select id='resultYear' class="form-control" name="stdyear" required disabled>
            <option disabled selected>Select Class First</option>
          </select>
        </div>


        <div class="form-group">
          <input class="form-control btn-success" type="submit" value="Go">
        </div>
      </form>
      <?php

      if (isset($_GET['stdyear'])) { ?>
        <?php
        $class  = $_GET['stdclass'];
        $year   = $_GET['stdyear'];
        $sec    = isset($_GET['sec'])   ? $_GET['sec']   : '';
        $group  = isset($_GET['group']) ? $_GET['group'] : '';

        $stSql = "SELECT studentid,stdName,stdNameBangla,stdReligion,stdFather,stdFatherBangla,stdMother,stdMotherBangla, stdBrith, birth_reg_no, infoRoll,sectionName,infoOptionals,info4thSub,stdPhone,stdEmergencyPhone,groupName,stdImg,className,stdPresent,stdGender,stdAdmitYear FROM ct_student
              LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid
              LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId
              LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
              LEFT JOIN ct_class ON ct_class.classid = $class
              WHERE infoClass = $class AND infoYear = '$year'";

        // Add teacher restrictions if user is a teacher
        if ($isTeacher && $hasAssignedClass && $teacherRestrictions) {
          $stSql .= $wpdb->prepare(
            " AND infoClass = %d AND infoSection = %d",
            $teacherRestrictions->teacherOfClass,
            $teacherRestrictions->teacherOfSection
          );
        }

        if ($sec != '' && $sec != 'all') {
          $stSql .= " AND infoSection = $sec";
        }
        if ($group != '') {
          $stSql .= " AND infoGroup = $group";
        }

        $stSql .= " ORDER BY sectionid,infoRoll ASC";

        $students = $wpdb->get_results($stSql);
        $totalstd = sizeof($students);

        // Get class, section, and group names for filename
        $classNameText = '';
        $sectionNameText = '';
        $groupNameText = '';
        if (!empty($students)) {
          $classNameText = !empty($students[0]->className) ? $students[0]->className : '';
          $sectionNameText = isset($_GET['sec']) && $_GET['sec'] != '' ? $wpdb->get_var($wpdb->prepare("SELECT sectionName FROM ct_section WHERE sectionid = %d", $_GET['sec'])) : '';
          $groupNameText = isset($_GET['group']) && $_GET['group'] != '' ? $wpdb->get_var($wpdb->prepare("SELECT groupName FROM ct_group WHERE groupId = %d", $_GET['group'])) : '';
        }
        ?>

        <div class="text-right">
          <button onclick="fnExcelReport()">Download Excel</button>
          <button
            onclick="exportPDF()"
            data-class="<?= esc_attr($classNameText) ?>"
            data-section="<?= esc_attr($sectionNameText) ?>"
            data-group="<?= esc_attr($groupNameText) ?>"
            data-year="<?= esc_attr($year) ?>">Download PDF</button>
        </div>
        <br>

        <div style="overflow-x: auto;">
          <div style="text-align: center; position: relative;">
            <img height="80px" style="position: absolute;left: 10px;top: 10px" src="<?= $s3sRedux['instLogo']['url'] ?>">
            <h2 style="margin: 5px 0 5px 0;"><b><?= $s3sRedux['institute_name'] ?></b></h2>
            <p style="color:#2b5591; font-size: 14px; margin: 0;"><?= $s3sRedux['institute_address'] ?></p>
            <h3>Student List (<?= $totalstd ?>)</h3>
          </div>

          <table class="table table-bordered table-responsive">
            <thead>
              <tr>
                <th>ID</th>
                <th>ID NO:</th>
                <th>Name</th>
                <th>Group</th>
                <th style="line-height: 1"><small>Class - Section</small></th>
                <th><span class="frtSub">4th</span> & <span class="optSub">Optional</span> Subject</th>
                <th>Phone</th>
                <th>Gender & Religion</th>
                <th>Address</th>
                <th>Image</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>

              <?php
              foreach ($students as $key => $student) {
                $stdGender = 'Boy';
                if ($student->stdGender == 0) {
                  $stdGender = 'Girl';
                } elseif ($student->stdGender == 2) {
                  $stdGender = 'Other';
                }
                //   $otrSubj = array();
                //   $opt = $student->infoOptionals;
                //   $frth = $student->info4thSub;

                //   if (!empty($opt)) {
                //     $otrSubj = json_decode($opt);

                //   }
                //   if (!empty($frth)) {
                //     $otrSubj = json_decode($frth);
                //   }
                //   if(sizeof($otrSubj) > 0){
                //     $subSql = "SELECT subjectid,subjectName FROM ct_subject WHERE subjectid IN (".implode(", ", $otrSubj).")";
                //     $optSubjs = $wpdb->get_results( $subSql );
                //   }

                $otrSubj = []; // Final array to hold subject IDs

                $opt = $student->infoOptionals;
                $frth = $student->info4thSub;

                // Decode $opt
                if (!empty($opt)) {
                  $decodedOpt = json_decode($opt, true);
                  if (is_array($decodedOpt)) {
                    $otrSubj = array_merge($otrSubj, $decodedOpt);
                  }
                }

                $decodedFrth = [];
                if (!empty($frth)) {
                  $tmp = json_decode($frth, true);

                  if (is_array($tmp)) {
                    $decodedFrth = $tmp;
                  } elseif (!empty($tmp)) {
                    $decodedFrth = [(string)$tmp];
                  }

                  if (!empty($decodedFrth)) {
                    $otrSubj = array_merge($otrSubj, $decodedFrth);
                  }
                }


                // Proceed only if we have subject IDs
                if (!empty($otrSubj)) {
                  // Sanitize to ensure they're all integers (prevent SQL injection)
                  $otrSubj = array_map('intval', $otrSubj);

                  $subSql = "SELECT subjectid, subjectName 
                               FROM ct_subject 
                               WHERE subjectid IN (" . implode(", ", $otrSubj) . ")";

                  $optSubjs = $wpdb->get_results($subSql);
                }

                // $otrSubj = [];
                // $opt = $student->infoOptionals;
                // $frth = $student->info4thSub;
                // if (!empty($opt)) {
                //     $otrSubj = json_decode($opt, true); // decode as associative array
                // }

                // if (!empty($frth)) {
                //     $otrSubj = json_decode($frth, true); // decode as associative array
                // }

                // if (is_array($otrSubj) && count($otrSubj) > 0) {
                //     $subSql = "SELECT subjectid,subjectName FROM ct_subject WHERE subjectid IN (" . implode(", ", $otrSubj) . ")";
                //     $optSubjs = $wpdb->get_results($subSql);
                // }



              ?>
                <tr>
                  <td><?= ($s3sRedux['stdidpref'] == 'year') ? $student->stdAdmitYear : $s3sRedux['stdidpref']; ?><?= sprintf("%05s", ($student->studentid + $s3sRedux['stdid'])) ?></td>
                  <td><?= $student->infoRoll; ?></td>
                  <td><?= $student->stdName; ?><br><small>Father: <?= $student->stdFather; ?></small></td>
                  <td><?= $student->groupName; ?></td>
                  <td><?= $student->className; ?><br>Sec - <?= $student->sectionName; ?></td>
                  <td>
                    <?php

                    if (sizeof($otrSubj) > 0) {
                      foreach ($optSubjs as $subj) {
                        //$ofclss = ($frth == $subj->subjectid) ? 'frtSub' : "optSub";
                        $ofclss = in_array((string)$subj->subjectid, $decodedFrth) ? 'frtSub' : 'optSub';


                        echo '<span data-id="' . $subj->subjectid . '" class="' . $ofclss . '">' . $subj->subjectName . '</span>';
                      }
                    }
                    ?>
                  </td>
                  <td><?= $student->stdPhone; ?></td>
                  <td><?= $stdGender; ?> <?= $student->stdReligion ?></td>
                  <td><?= $student->stdPresent; ?></td>
                  <td>
                    <?php if (!empty($student->stdImg)): ?>
                      <img width="40" src="<?= $student->stdImg; ?>">
                    <?php endif; ?>
                  </td>
                  <td>

                    <form class="pull-right actionForm" method="POST" action="">

                      <a href="?page=student&option=view&id=<?= $student->studentid; ?>&class=<?= $class ?>&syear=<?= $year ?>" class="btn-link">
                        <span class="dashicons dashicons-visibility"></span></span>
                      </a>

                      <a href="?page=student&option=add&edit=<?= $student->studentid; ?>&class=<?= $class ?>" class="btn-link">
                        <span class="dashicons dashicons-welcome-write-blog"></span></span>
                      </a>

                      <button type="button" class="btn-link btnDelete" name="deleteStudent" data-id='<?= $student->studentid ?>'>
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
        </div>

        <!-- For Export -->
        <div id="dtudentsTblDiv" class="hidden">
          <table id="studentsTbl" class="table table-bordered table-responsive">
            <thead style="color: #87AFC6">
              <tr>
                <td colspan="9" style="text-align: center;font-size: 14px;">
                  <b><?= $s3sRedux['institute_name'] ?> </b>
                  <br>
                  <p style="font-size: 12px;"><?= $s3sRedux['institute_address'] ?> </p>
                  <br>
                  <p style="font-size: 12px;">Class: <?= $classNameText ?><?= !empty($sectionNameText) ? ', Section: ' . $sectionNameText : '' ?><?= !empty($groupNameText) ? ', Group: ' . $groupNameText : '' ?>, Year: <?= $year ?></p>
                </td>
              </tr>
              <tr>
                <th>Roll No</th>
                <th>Name</th>
                <th>Father's Name</th>
                <th>Mother's Name</th>
                <th>Date of Birth</th>
                <th>Birth Registration No</th>
                <th>Gender &<br>Religion</th>
                <th>Gurdian Phone &<br>Emergency Phone</th>
                <th>Address</th>
              </tr>
            </thead>
            <tbody>
              <?php



              foreach ($students as $key => $student) {
                $stdGender = 'Boy';
                if ($student->stdGender == 0) {
                  $stdGender = 'Girl';
                } elseif ($student->stdGender == 2) {
                  $stdGender = 'Other';
                }
              ?>
                <tr>
                  <td><?= $student->infoRoll; ?></td>
                  <td><?= $student->stdName; ?><br>
                    <p style="font-size: 14px;"><?= $student->stdNameBangla ?></p>
                  </td>
                  <td><?= $student->stdFather; ?><br>
                    <p style="font-size: 14px;"><?= $student->stdFatherBangla; ?></p>
                  </td>
                  <td><?= $student->stdMother; ?><br>
                    <p style="font-size: 14px;"><?= $student->stdMotherBangla; ?></p>
                  </td>
                  <td><?= $student->stdBrith; ?></td>
                  <td><?= $student->birth_reg_no; ?></td>

                  <td><?= $stdGender; ?> <br> <?= $student->stdReligion ?></td>
                  <td><?= $student->stdPhone; ?><br><?= $student->stdEmergencyPhone; ?></td>
                  <td><?= $student->stdPresent; ?> </td>
                </tr>
              <?php
              }
              ?>
            </tbody>
          </table>
        </div>
      <?php } ?>
    </div>
  </div>
</div>


<!-- <script src="https://unpkg.com/jspdf"></script> -->


<script type="text/javascript">
  (function($) {
    $('#resultClass').change(function() {
      var $siteUrl = '<?= get_template_directory_uri() ?>';

      $.ajax({
        url: $siteUrl + "/inc/ajaxAction.php",
        method: "POST",
        data: {
          class: $(this).val(),
          type: 'getYears'
        },
        dataType: "html"
      }).done(function(msg) {
        $("#resultYear").html(msg);
        $("#resultYear").prop('disabled', false);
      });

      $.ajax({
        url: $siteUrl + "/inc/ajaxAction.php",
        method: "POST",
        data: {
          class: $(this).val(),
          type: 'getSection'
        },
        dataType: "html"
      }).done(function(msg) {
        $("#resultSection").html(msg);
        $("#resultSection").prop('disabled', false);
      });
    });
  })(jQuery);


  /*=====================Excel Export*/

  function fnExcelReport() {
    var tab_text = "<table border='2px'><tr bgcolor='#87AFC6'>";
    var textRange;
    var j = 0;
    tab = document.getElementById('studentsTbl'); // id of table

    for (j = 0; j < tab.rows.length; j++) {
      tab_text = tab_text + tab.rows[j].innerHTML + "</tr>";
    }

    tab_text = tab_text + "</table>";
    tab_text = tab_text.replace(/<A[^>]*>|<\/A>/g, "");

    tab_text = tab_text.replace(/<input[^>]*>|<\/input>/gi, "");

    var ua = window.navigator.userAgent;
    var msie = ua.indexOf("MSIE ");

    if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./)) {
      txtArea1.document.open("txt/html", "replace");
      txtArea1.document.write(tab_text);
      txtArea1.document.close();
      txtArea1.focus();
      sa = txtArea1.document.execCommand("SaveAs", true, "students.xls");
    } else //other browser not tested on IE 11
      sa = window.open('data:application/vnd.ms-excel,' + encodeURIComponent(tab_text));

    return (sa);
  }


  /*=====================PDF Export*/

  function exportPDF() {
    // Get the table HTML
    var table = document.getElementById('studentsTbl');
    if (!table) {
      alert('No data to export');
      return;
    }

    // Get the complete HTML including styles
    var htmlContent = `
      <html>
        <head>
          <style>
            table {
              width: 100%;
              border-collapse: collapse;
              font-family: Arial, sans-serif;
            }
            table, th, td {
              border: 1px solid #000;
            }
            th, td {
              padding: 8px;
              text-align: left;
              font-size: 10px;
            }
            th {
              background-color: #87AFC6;
              color: white;
              font-weight: bold;
            }
            tr:nth-child(even) {
              background-color: #f2f2f2;
            }
            img {
              max-width: 40px;
              max-height: 60px;
            }
          </style>
        </head>
        <body>
          ${table.outerHTML}
        </body>
      </html>
    `;

    // Show loading indicator
    var button = event.target;
    var originalText = button.innerHTML;
    button.innerHTML = 'Generating PDF...';
    button.disabled = true;

    // Get class, section, and group info from button data attributes
    var className = button.getAttribute('data-class') || '';
    var sectionName = button.getAttribute('data-section') || '';
    var groupName = button.getAttribute('data-group') || '';
    var year = button.getAttribute('data-year') || '';

    // Build filename with class/section/group info
    var filenameParts = ['students'];
    if (className) filenameParts.push(className);
    if (sectionName) filenameParts.push(sectionName);
    if (groupName) filenameParts.push(groupName);
    if (year) filenameParts.push(year);

    var filename = filenameParts.join('-').replace(/\s+/g, '-');

    // Prepare the request payload
    var payload = {
      html: htmlContent,
      filename: filename,
      format: 'A4',
      orientation: 'L', // Landscape for better table view
      margin_left: 10,
      margin_right: 10,
      margin_top: 10,
      margin_bottom: 10
    };

    // Send request to the API
    fetch('https://cloud.barnomala.com/api/v1/download-pdf', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('PDF generation failed');
        }
        return response.blob();
      })
      .then(blob => {
        // Create download link
        var url = window.URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = payload.filename + '.pdf';
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);

        // Reset button
        button.innerHTML = originalText;
        button.disabled = false;
      })
      .catch(error => {
        console.error('Error generating PDF:', error);
        alert('Failed to generate PDF. Please make sure the PDF API is running on https://cloud.barnomala.com');

        // Reset button
        button.innerHTML = originalText;
        button.disabled = false;
      });
  }
</script>