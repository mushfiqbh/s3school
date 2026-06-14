<script src="https://unpkg.com/jspdf@latest/dist/jspdf.umd.min.js">
</script><!-- <script src="https://unpkg.com/jspdf-autotable"></script> -->
<script src="	https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.6/jspdf.plugin.autotable.min.js"></script>


<style>
.std-img-upload-container {
    position: relative;
    width: 50px;
    height: 50px;
    cursor: pointer;
    border: 1px solid #ddd;
    border-radius: 4px;
    overflow: hidden;
    background: #f9f9f9;
    display: flex;
    align-items: center;
    justify-content: center;
}
.std-img-upload-container img {
    max-width: 100%;
    max-height: 100%;
    object-fit: cover;
}
.std-img-upload-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.4);
    display: none;
    align-items: center;
    justify-content: center;
    color: #fff;
}
.std-img-upload-container:hover .std-img-upload-overlay {
    display: flex;
}
.std-img-upload-overlay span {
    font-size: 20px;
}
.std-img-uploading {
    opacity: 0.5;
    pointer-events: none;
}
/* Spinner for uploading state */
.std-img-uploading::after {
    content: "";
    position: absolute;
    width: 20px;
    height: 20px;
    border: 3px solid #ccc;
    border-top-color: #333;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}
@keyframes spin {
    to { transform: rotate(360deg); }
}
</style>


<?php
global $wpdb,$s3sRedux; 
  $yearGroup = $wpdb->get_results( "SELECT stdCurntYear FROM ct_student GROUP BY stdCurntYear" );
  $classGroup = $wpdb->get_results( "SELECT classid,className FROM ct_student
    LEFT JOIN ct_class ON ct_class.classid = ct_student.stdAdmitClass
    GROUP BY stdAdmitClass" );

  $admitYear = isset($_POST['filter']) ? $_POST['filter'] : date("Y");
  ?>
  <div class="panel panel-info">
    <div class="panel-heading">
      <?php $class =  (isset($_POST['stdclass'])) ? $_POST['stdclass'] : '' ?> 
      <?php $year =  (isset($_POST['stdyear'])) ? $_POST['stdyear'] : '' ?> 
      <h3>
        Students <?= (isset($_POST['stdyear'])) ? '('.$clsName.', '.$year.' )' : '' ?> <br>
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

                $classQuery = $wpdb->get_results( "SELECT classid,className FROM ct_class WHERE classid IN (SELECT infoClass FROM ct_studentinfo GROUP BY infoClass ORDER BY className ASC)" );
                echo "<option value=''>Select Class</option>";

                foreach ($classQuery as $class) {
                  echo "<option value='".$class->classid."'>".$class->className."</option>";
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

        if(isset($_GET['stdyear'])){ ?>
          <?php 
            $class  = $_GET['stdclass'];
            $year   = $_GET['stdyear'];
            $sec    = isset($_GET['sec'])   ? $_GET['sec']   : '';
            $group  = isset($_GET['group']) ? $_GET['group'] : '';

            $stSql = "SELECT studentid,stdName,stdReligion,stdFather,stdMother,infoRoll,sectionName,infoOptionals,info4thSub,stdPhone,groupName,stdImg,className,stdPresent,stdGender,stdAdmitYear FROM ct_student
              LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid
              LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId
              LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
              LEFT JOIN ct_class ON ct_class.classid = $class
              WHERE infoClass = $class AND infoYear = '$year'";

            if ($sec != '') { $stSql .= " AND infoSection = $sec"; }
            if ($group != '') { $stSql .= " AND infoGroup = $group"; }

            $stSql .= " ORDER BY sectionid,infoRoll ASC";

            $students = $wpdb->get_results( $stSql );
            $totalstd = sizeof($students);

            $statSql = "SELECT 
              SUM(CASE WHEN stdGender = 1 THEN 1 ELSE 0 END) AS totalBoys,
              SUM(CASE WHEN stdGender = 0 THEN 1 ELSE 0 END) AS totalGirls,
              COUNT(*) AS total
              FROM ct_student
              LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid
              WHERE infoClass = $class AND infoYear = '$year'";
            $statistics = $wpdb->get_row( $statSql );
          ?>
          <div class="text-right">
            <button onclick="fnExcelReport()">Download Excel</button>
            <button id="pdfBtn" onclick="exportPDF()">Download PDF</button>
          </div>
          <br>

         <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
            <div style="text-align: center; position: relative; min-width: 800px;">
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
                  }elseif ($student->stdGender == 2) {
                    $stdGender = 'Other';
                  }
                  $otrSubj = array();
                  $opt = $student->infoOptionals;
                  $frth = $student->info4thSub;

                  if (!empty($opt)) {
                    $otrSubj = json_decode($opt);
                    $otrSubj[] = $frth;
                  }else{
                    $otrSubj[] = $frth;
                  }
                  if(sizeof($otrSubj) > 0){
                    $subSql = "SELECT subjectid,subjectName FROM ct_subject WHERE subjectid IN (".implode(", ", $otrSubj).")";
                    $optSubjs = $wpdb->get_results( $subSql );
                  }
                  ?>
                  <tr>
                    <td><?= ($s3sRedux['stdidpref'] == 'year') ? $student->stdAdmitYear: $s3sRedux['stdidpref']; ?><?= sprintf("%05s", ($student->studentid + $s3sRedux['stdid'] )) ?></td>
                    <td><?= $student->infoRoll; ?></td>
                    <td><?= $student->stdName; ?><br><small>Father: <?= $student->stdFather; ?></small></td>
                    <td><?= $student->groupName; ?></td>
                    <td><?= $student->className; ?><br>Sec - <?= $student->sectionName; ?></td>
                    <td>
                      <?php

                      if(sizeof($otrSubj) > 0){
                        foreach ($optSubjs as $subj) {
                          $ofclss = ($frth == $subj->subjectid) ? 'frtSub' : "optSub";
                          echo '<span data-id="'.$subj->subjectid.'" class="'.$ofclss.'">'.$subj->subjectName.'</span>';
                          
                        }
                      }
                      ?>
                    </td>
                    <td><?= $student->stdPhone; ?></td>
                    <td><?= $stdGender; ?> <?= $student->stdReligion ?></td>
                    <td><?= $student->stdPresent; ?></td>
                   
                    <td>
                      <div class="std-img-upload-container" data-id="<?= $student->studentid ?>">
                          <img src="<?= !empty($student->stdImg) ? $student->stdImg : get_template_directory_uri() . '/img/image.png' ?>" class="std-img-preview">
                          <div class="std-img-upload-overlay">
                              <span class="dashicons dashicons-upload"></span>
                          </div>
                          <input type="file" class="std-img-input" style="display:none;" accept="image/*">
                      </div>
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
          </div>

          <!-- For Export -->
         <div id="dtudentsTblDiv" class="hidden">
           <style>
            .pdf-container { width: 100%; font-family: sans-serif; }
            .header-table { width: 100%; margin-bottom: 20px; margin-top: 10px;}
            .header-text { text-align: center; vertical-align: middle; }
            .inst-name { font-size: 16pt; font-weight: bold; margin: 0; color: #000; }
            .inst-addr { font-size: 11pt; color: #555; margin: 5px 0; }
            .report-title { font-size: 12pt; font-weight: bold; margin: 10px 0; color: #2b5591; }
            
            .info-table { width: 100%; margin-bottom: 10px; border-collapse: collapse; }
            .info-table td { padding: 8px; font-size: 10pt; border: 1px solid #ddd; background: #f9f9f9; }
            
            .students-table { width: 100%; border-collapse: collapse; font-size: 9pt; table-layout: fixed; }
            .students-table th { background-color: #4472C4; color: white; padding: 10px 5px; border: 1px solid #335a96; text-align: left; }
            .students-table td { padding: 8px 5px; border: 1px solid #ddd; vertical-align: top; word-wrap: break-word; }
            .students-table tr:nth-child(even) { background-color: #f9f9f9; }
            
            .bangla-text { font-family: 'kalpurush', sans-serif; font-size: 12pt; color: #333; }
            .label-text { font-weight: bold; color: #555; font-size: 8pt; display: inline-block; width: 15px; }
          </style>

          <?php
          $sectionName = '';
          if ($sec != '' && $sec != 'all') {
            $section = $wpdb->get_row("SELECT sectionName FROM ct_section WHERE sectionid = $sec");
            if ($section) {
              $sectionName = $section->sectionName;
            }
          }
          ?>

          <div class="pdf-container">
            <table class="header-table">
              <tr>
                <td class="header-text">
                  <div class="inst-name"><?= $s3sRedux['institute_name'] ?></div>
                  <div class="inst-addr"><?= $s3sRedux['institute_address'] ?></div>
                  <div class="report-title" style="border:none">Class: <?= $students[0]->className ?> <?= $sec ? 'Section: ' . $sectionName : '' ?></div>
                </td>
              </tr>
            </table>

            <table class="info-table">
              <tr>
                <td><strong>Boy:</strong> <?= $statistics->totalBoys ?></td>
                <td><strong>Girl:</strong> <?= $statistics->totalGirls ?></td>
                <td><strong>Total Students:</strong> <?= $statistics->total ?></td>
              </tr>
            </table>

            <table id="studentsTbl" class="students-table">
              <thead>
                <tr>
                  <th style="width: 40px;">Roll No</th>
                  <th>Student Name</th>
                  <th>Father's Name</th>
                  <th>Mother's Name</th>
                  <th>Date of Birth</th>
                  <th>Birth Registration No</th>
                  <th>Gender & Religion</th>
                  <th>Contact &<br> Emergency Phone</th>
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
                    <td style="text-align: center;"><?= $student->infoRoll; ?></td>
                    <td>
                      <?= $student->stdName; ?><br>
                      <span class="bangla-text"><?= $student->stdNameBangla; ?></span>
                    </td>
                    <td>
                      <span class="label-text"></span> <?= $student->stdFather; ?><br>
                      <span class="bangla-text"><?= $student->stdFatherBangla ?></span><br>
                    </td>
                    <td>
                      <span class="label-text"></span> <?= $student->stdMother; ?><br>
                      <span class="bangla-text"><?= $student->stdMotherBangla ?></span>
                    </td>
                    <td>
                      <?= $student->stdBrith; ?>
                    </td>
                    <td>
                      <?= esc_html($student->birth_reg_no); ?>
                    </td>
                    <td><?= $stdGender; ?> <br> <?= $student->stdReligion ?></td>
                    <td>
                      <?= $student->stdPhone; ?> <br>
                      <?= !empty($student->stdEmergencyPhone) ? $student->stdEmergencyPhone : ''; ?>
                    </td>
                    <td><?= $student->stdPresent; ?></td>
                  </tr>
                <?php
                }
                ?>
              </tbody>
            </table>
          </div>
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
        url: $siteUrl+"/inc/ajaxAction.php",
        method: "POST",
        data: { class : $(this).val(), type : 'getYears' },
        dataType: "html"
      }).done(function( msg ) {
        $( "#resultYear" ).html( msg );
        $( "#resultYear" ).prop('disabled', false);
      });

      $.ajax({
        url: $siteUrl+"/inc/ajaxAction.php",
        method: "POST",
        data: { class : $(this).val(), type : 'getSection' },
        dataType: "html"
      }).done(function( msg ) {
        $( "#resultSection" ).html( msg );
        $( "#resultSection" ).prop('disabled', false);
      });
    });

    
    // Student Image Upload
    $(document).on('click', '.std-img-upload-container', function(e) {
        if (e.target.classList.contains('std-img-input')) {
            return;
        }
        $(this).find('.std-img-input').click();
    });

    $(document).on('change', '.std-img-input', function(e) {
        var file = e.target.files[0];
        if (!file) return;

        var container = $(this).closest('.std-img-upload-container');
        var studentId = container.data('id');
        var preview = container.find('.std-img-preview');
        var formData = new FormData();

        formData.append('student_image', file);
        formData.append('student_id', studentId);
        formData.append('type', 'uploadStudentImage');

        container.addClass('std-img-uploading');

        $.ajax({
            url: window.location.href, // Send to current page
            type: 'POST',
            data: formData,
            dataType: 'json',
            contentType: false,
            processData: false,
            success: function(response) {
                container.removeClass('std-img-uploading');
                if (response.success) {
                    preview.attr('src', response.data.url);
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                container.removeClass('std-img-uploading');
                alert('Upload failed. Please try again.');
            }
        });
    });
  })( jQuery );


  /*=====================Excel Export*/

  function fnExcelReport(){
    var tab_text="<table border='2px'><tr bgcolor='#87AFC6'>";
    var textRange; var j=0;
    tab = document.getElementById('studentsTbl'); // id of table

    for(j = 0 ; j < tab.rows.length ; j++){     
      tab_text=tab_text+tab.rows[j].innerHTML+"</tr>";
    }

    tab_text=tab_text+"</table>";
    tab_text= tab_text.replace(/<A[^>]*>|<\/A>/g, "");
    
    tab_text= tab_text.replace(/<input[^>]*>|<\/input>/gi, ""); 

    var ua = window.navigator.userAgent;
    var msie = ua.indexOf("MSIE "); 

    if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./)){
      txtArea1.document.open("txt/html","replace");
      txtArea1.document.write(tab_text);
      txtArea1.document.close();
      txtArea1.focus(); 
      sa=txtArea1.document.execCommand("SaveAs",true,"students.xls");
    }  
    else                 //other browser not tested on IE 11
      sa = window.open('data:application/vnd.ms-excel,' + encodeURIComponent(tab_text));  

    return (sa);
  }


  function exportPDF() {
    var btn = document.getElementById('pdfBtn');
    var originalText = btn.innerHTML;
    btn.disabled = true;
    var dotCount = 0;
    var loadingInterval = setInterval(function() {
      dotCount = (dotCount + 1) % 4;
      btn.innerHTML = 'Downloading' + '.'.repeat(dotCount);
    }, 400);

    // Get the HTML of the table
    var tableDiv = document.getElementById('dtudentsTblDiv');
    if (!tableDiv) {
      alert('Table not found for export.');
      return;
    }
    var html = tableDiv.innerHTML;

    // Prepare the payload
    var data = {
      html: html,
      filename: 'students.pdf',
      format: 'A4',
      orientation: 'L',
      font: 'sans-serif'
    };

    // Create a form data object
    var formData = new FormData();
    for (var key in data) {
      if (data.hasOwnProperty(key)) {
        formData.append(key, data[key]);
      }
    }

    // Send the request to the API
    fetch('https://cloud.barnomala.com/api/v1/download-pdf', {
      method: 'POST',
      body: formData
    })
      .then(function(response) {
        if (!response.ok) throw new Error('PDF generation failed');
        return response.blob();
      })
      .then(function(blob) {
        // Create a link to download the PDF
        var url = window.URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = data.filename;
        document.body.appendChild(a);
        a.click();
        setTimeout(function() {
          window.URL.revokeObjectURL(url);
          document.body.removeChild(a);
        }, 100);
      })
      .catch(function(error) {
        alert('PDF export failed: ' + error.message);
      })
      .finally(function() {
        clearInterval(loadingInterval);
        btn.innerHTML = originalText;
        btn.disabled = false;
      });
  }
</script>
