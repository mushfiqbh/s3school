<?php global $s3sRedux; ?>
<div class="panel panel-info">
	<div class="panel-heading"><h3>Blank Mark Sheet</h3></div>
	<div class="panel-body">
		<form action="" method="GET" class="form-inline">

			<div class="form-group">
				<input type="hidden" name="page" value="result">
				<input type="hidden" name="view" value="marksheet">
				<label>Class</label>
				<select id='resultClass' class="form-control" name="class" required>
					<?php

						$classQuery = $wpdb->get_results( "SELECT classid,className FROM ct_class WHERE classid IN (SELECT examClass FROM ct_exam GROUP BY examClass ORDER BY className ASC)" );
						echo "<option value=''>Select Class</option>";

						foreach ($classQuery as $class) {
							echo "<option value='".$class->classid."'>".$class->className."</option>";
						}
					?>
				</select>
			</div>

			<div class="form-group ">
				<label>Exam</label>
				<select id="resultExam" class="form-control" name="exam" required disabled>
					<option disabled selected>Select Class First</option>
				</select>
			</div>

			<div class="form-group ">
				<label>Section</label>
				<select id="resultSection" class="form-control" name="sec" disabled>
					<option disabled selected>Select Class First</option>
				</select>
			</div>

			<div class="form-group">
				<label>Year/Session</label>
				<select id='resultYear' class="form-control" name="syear" required disabled>
					<option disabled selected>Select Class First</option>
				</select>
			</div>


			<div class="form-group">
				<label>Subject</label>
				<select id='resultSubject' class="form-control" name="subject" required disabled>
					<option disabled selected>Select exam First</option>
				</select>
			</div>

			<div class="form-group">
				<input class="form-control btn-success" type="submit" name="" value="Go">
			</div>
		</form>
	</div>

</div>

<?php

if(isset($_GET['exam'])):
	$exam 	= $_GET['exam']; 
	$year 	= $_GET['syear']; 
	$class 	= $_GET['class'];
	$sub 		= $_GET['subject'];
	$sec 		= isset($_GET['sec']) ? $_GET['sec'] : '';
   
	$results = "SELECT examName,className,subjectName,connecttedPaper,subPaper,subOptinal,sub4th,subMCQ,subCQ,subPect,subCa";
	if($sec!= ''){$results .= " ,sectionName";}
	    $results .= " FROM ct_subject";
	    $results .= " LEFT JOIN ct_exam ON examid = $exam
		LEFT JOIN ct_class ON ct_exam.examClass = ct_class.classid";
		if($sec!= ''){$results .= " LEFT JOIN ct_section ON ct_section.sectionid = $sec";}
		$results .= " WHERE subjectid = $sub" ;
		$info = $wpdb->get_results($results);
 if($info){
	$resCombineWith = $info[0]->connecttedPaper;
	$resSubPaper		= $info[0]->subPaper;
	$subOpt 				= $info[0]->subOptinal;
	$sub4th 				= $info[0]->sub4th;

	$subMCQ 			= $info[0]->subMCQ;
	$subCQ 				= $info[0]->subCQ;
	$subPect 			= $info[0]->subPect;
	$subCa 			= $info[0]->subCa;

	?>

	<div class="panel panel-info">
		<div class="panel-heading"><h3>Result <button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button> </h3></div>
		<div class="panel-body">
			<form action="" method="POST">
		
				<div class="form-group">

					<div id="printArea">
					  <div class="printArea" style="margin: 0;">
							<style type="text/css">table tr{ page-break-inside: avoid !important; } table tr a{ text-decoration: none;color: #000; } @page { size: 210mm 297mm !important; margin: 10px 0 !important; }</style>
							<link rel="stylesheet" href="<?= get_template_directory_uri() ?>/css/tabulationSheet.css" />
							
							<div style="text-align: center; position: relative;">
				  			<img height="80px" style="position: absolute;left: 10px;top: 10px" src="<?= $s3sRedux['instLogo']['url'] ?>">
		  					<h2 style="margin: 5px 0 5px 0;"><b><?= $s3sRedux['institute_name'] ?></b></h2>
					  		<p style="color:#2b5591; font-size: 14px; margin: 0;"><?= $s3sRedux['institute_address'] ?></p>
					  		<h3>Blank Mark Sheet</h3>
				  		</div>

							<table class="table table-bordered" style="width: 100%">
				  			<tr style="background: #4472C4;-webkit-print-color-adjust: exact;color: #fff">
				  				<td colspan="3"><b>Exam :</b> <?= $info[0]->examName ?></td>
				  				<td colspan="3"><b>Subject Name :</b> <?= $info[0]->subjectName ?></td>
				  			</tr>
				  			<tr style="background: #D9E2F3;-webkit-print-color-adjust: exact;">
				  				<td colspan="2"><b>Year/Session :</b> <?= $_GET['syear'] ?></td>
				  				<td colspan="2"><b>Class :</b> <?= $info[0]->className ?></td>
				  				<td colspan="2"><b>Section :</b> <?= @$info[0]->sectionName ?></td>
				  			</tr>
				  		</table>

							<div class="table-responsive">
								<table id="resultInputTable" class="table table-bordered" style="width: 100%">
									<tr>
										<th style="text-align: center;">Roll</th>
										<th>Name</th>
										<th style="text-align: center;<?= ($subCQ == 0) ? 'display:none' : ''; ?>"><?= $s3sRedux['cqtitle'] ?> (<?= $subCQ ?>)</th>
										<th style="text-align: center;<?= ($subMCQ == 0) ? 'display:none' : ''; ?>"><?= $s3sRedux['mcqtitle'] ?> (<?= $subMCQ ?>)</th>
										<th style="text-align: center;<?= ($subPect == 0) ? 'display:none' : ''; ?>"><?= $s3sRedux['prctitle'] ?> (<?= $subPect ?>)</th>
										<th style="text-align: center;<?= ($subCa == 0) ? 'display:none' : ''; ?>"><?= $s3sRedux['catitle'] ?> (<?= $subCa ?>)</th>
									</tr>
								

									<?php
										if($subOpt == 0 && $sub4th == 0){
											$stdQuery = "SELECT studentid,infoRoll,stdName,groupName,infoGroup,infoSection,infoOptionals,info4thSub,sectionName FROM ct_student
												LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid
																								AND ct_studentinfo.infoClass = $class AND ct_studentinfo.infoYear = '$year' 
												LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId 
												LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid 
												WHERE studentid NOT IN
													(SELECT resStudentId FROM `ct_result` WHERE resClass = $class AND resultYear = '$year' AND resSubject = $sub AND resExam = $exam)
													AND stdCurntYear = '$year' AND stdCurrentClass = $class";

											if ($sec != "") {
												$stdQuery .= " AND infoSection = $sec";
											}

											if($class == 41){
											 	$stdQuery .= " ORDER BY groupName DESC, infoRoll ASC";
											}else{
											$stdQuery .= " ORDER BY infoRoll ASC";
											}
										}else{
											$stdQuery = "SELECT studentid,infoRoll,stdName,groupName,infoGroup,infoSection,infoOptionals,info4thSub,sectionName FROM ct_student
												LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid
																								AND ct_studentinfo.infoClass = $class AND ct_studentinfo.infoYear = '$year' 
												LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId 
												LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid 
												WHERE studentid NOT IN
													(SELECT resStudentId FROM `ct_result` WHERE resClass = $class AND resultYear = '$year' AND resSubject = $sub AND resExam = $exam)
													AND stdCurntYear = '$year' AND stdCurrentClass = $class";
											if ($subOpt == 1 && $sub4th == 1) {
												$stdQuery .= " AND (infoOptionals LIKE '%\"$sub\"%' OR info4thSub = $sub)";
											}
											if ($subOpt == 1 && $sub4th == 0) {
												$stdQuery .= " AND infoOptionals LIKE '%\"$sub\"%' ";
											}
											if ($subOpt == 0 && $sub4th == 1) {
												$stdQuery .= " AND info4thSub = $sub ";
											}
											if ($sec != "") {
												$stdQuery .= " AND infoSection = $sec";
											}
											if($class == 41){
											 	$stdQuery .= " ORDER BY groupName DESC, infoRoll ASC";
											}else{
											$stdQuery .= " ORDER BY infoRoll ASC";
											}
										}

										$stdQuery = $wpdb->get_results( $stdQuery );
						
										foreach ($stdQuery as $student) { ?>
											
											<tr>
												<td style="text-align: center;"><?= $student->infoRoll ?></td>
												<td><?= $student->stdName ?></td>
												<td style="text-align: center;<?= ($subCQ == 0) ? 'display:none' : ''; ?>"><?= ($subCQ == 0) ? 'x' : ''; ?></td>
												<td style="text-align: center;<?= ($subMCQ == 0) ? 'display:none' : ''; ?>"><?= ($subMCQ == 0) ? 'x' : ''; ?></td>
												<td style="text-align: center;<?= ($subPect == 0) ? 'display:none' : ''; ?>"><?= ($subPect == 0) ? 'x' : ''; ?></td>
												<td style="text-align: center;<?= ($subCa == 0) ? 'display:none' : ''; ?>"><?= ($subCa == 0) ? 'x' : ''; ?></td>
											</tr>
											<?php
										}
									?>
								</table>
								
								
								<?php if(!$stdQuery){ ?>
									<h3 class="text-center text-info">No Student Found for add the result</h3>
								<?php } ?>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>
	</div>

	<?php 
	}else{?>
	    <h3 class="text-center text-info">No data found. Please check subject is added to selected exam</h3>
<?php 	}
endif; ?>


<script type="text/javascript">
	function print(divId) {
    var printContents = document.getElementById(divId).innerHTML;
    w = window.open();
    w.document.write(printContents);
    w.document.write('<scr' + 'ipt type="text/javascript">' + 'window.onload = function() { window.print();  };' + '</sc' + 'ript>');
    w.document.close();
    w.focus();
    return true;
  }
</script>


<?php
// ===============================================================
// FIX 409 CONFLICT - HANDLE AJAX ACTIONS LOCALLY (At End of File)
// ===============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'])) {

  // Clean output buffer to ensure JSON/HTML response is valid
  while (ob_get_level()) {
    ob_end_clean();
  }

  // ------------------------------------------
  // Get Exams
  // ------------------------------------------
  if ($_POST['type'] == 'getExams') {
    $class = $_POST['class'];
    $exams = $wpdb->get_results("SELECT examid,examName FROM ct_exam WHERE examClass = '$class'");
    if (empty($exams)) {
      echo "<option value=''>No Exam for this Class</option>";
    } else {
      echo "<option value=''>Select An Exam</option>";
    }
    foreach ($exams as $exam) {
      echo "<option value='{$exam->examid}'>{$exam->examName}</option>";
    }
    exit;
  }

  // ------------------------------------------
  // Get Years
  // ------------------------------------------
  elseif ($_POST['type'] == 'getYears') {
    $class = $_POST['class'];
    $years = $wpdb->get_results("SELECT infoYear FROM ct_studentinfo WHERE infoClass = $class GROUP BY infoYear ORDER BY infoYear ASC");
    if (empty($years)) {
      echo "<option value=''>No Student In this class</option>";
    } else {
      echo "<option value=''>Year</option>";
    }
    foreach ($years as $year) {
      echo "<option value='{$year->infoYear}'>{$year->infoYear}</option>";
    }
    exit;
  }

  // ------------------------------------------
  // Get Section
  // ------------------------------------------
  elseif ($_POST['type'] == 'getSection') {
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
  // Get Groups
  // ------------------------------------------
  elseif ($_POST['type'] == 'getGroupsByClass') {
    $class = $_POST['class'];
    $groups_query = "SELECT DISTINCT ct_group.groupId, ct_group.groupName 
            FROM ct_group 
            INNER JOIN ct_studentinfo ON ct_studentinfo.infoGroup = ct_group.groupId 
            WHERE ct_studentinfo.infoClass = '$class'";
    
    $groups_query .= " ORDER BY ct_group.groupName ASC";
    $groups = $wpdb->get_results($groups_query);

    echo "<option value=''>All Groups</option>";
    foreach ($groups as $group) {
      echo "<option value='{$group->groupId}'>{$group->groupName}</option>";
    }
    exit;
  }

  // ------------------------------------------
  // Get Exam Subjects
  // ------------------------------------------
  elseif ($_POST['type'] == 'getExamSubject') {
    $exam = intval($_POST['exam']);
    $group = isset($_POST['group']) ? $_POST['group'] : '';
    $subjects = [];

    $subs = $wpdb->get_results("SELECT examSubjects FROM ct_exam WHERE examid = $exam");

    if (!empty($subs[0]->examSubjects)) {
      $subs = json_decode($subs[0]->examSubjects, true);
    } else {
      $subs = [];
    }

    if (!empty($subs)) {
      $subs_escaped = array_map('intval', $subs);
      $subjectQuery = "SELECT subjectid,subjectName FROM ct_subject 
                WHERE subjectid IN (" . implode(',', $subs_escaped) . ")";

      if (!empty($group)) {
        $subjectQuery .= " AND (forGroup = 'all' OR forGroup = '$group' OR forGroup LIKE '%\"$group\"%')";
      }

      $subjectQuery .= " ORDER BY subjectName ASC";
      $subjects = $wpdb->get_results($subjectQuery);
    }

    if (empty($subjects)) {
      echo "<option value=''>No subject!</option>";
    } else {
      echo "<option value=''>Select Subject</option>";
      foreach ($subjects as $subject) {
        echo '<option value="' . $subject->subjectid . '">' . $subject->subjectName . '</option>';
      }
    }
    exit;
  }
}

if (isset($_POST['updateAllResult'])) {
  $cq = $_POST['CQ'];
  $mcq = $_POST['MCQ'];
  $prc = $_POST['P'];
  $ca = $_POST['ca'];
  $response = false;
  foreach ($_POST['id'] as $id) {
    $update = $wpdb->update(
      'ct_result',
      array(
        'resCQ'     => $cq[$id],
        'resMCQ'     => $mcq[$id],
        'resPrec'   => $prc[$id],
        'resCa'   => $ca[$id],
        'resTotal'   => isnum($cq[$id]) + isnum($mcq[$id]) + isnum($prc[$id]) + isnum($ca[$id])
      ),
      array('resultId' => $id)
    );
    if ($update) {
      $response = $update;
    }
  }
  if ($response) {
    $message = array('status' => 'success', 'message' => 'Successfully updated');
  } else {
    $message = array('status' => 'faild', 'message' => 'Something wrong please try again');
  }
} ?>

<script type="text/javascript">
  // ==================================
  // HANDLE AJAX ACTIONS LOCALLY
  // ==================================
  (function($) {
    // Use current page as AJAX URL for standalone processing
    var ajaxUrl = '';

    $('#resultClass').change(function() {
      var selectedClass = $(this).val();

      // Fetch Exams
      $.ajax({
        url: ajaxUrl,
        method: "POST",
        data: {
          class: selectedClass,
          type: 'getExams'
        },
        dataType: "html"
      }).done(function(msg) {
        $("#resultExam").html(msg);
        $("#resultExam").prop('disabled', false);
        // Reset dependent dropdowns
        $("#resultSubject").prop('disabled', true).html('<option disabled selected>Select exam First</option>');
      });

      // Fetch Years
      $.ajax({
        url: ajaxUrl,
        method: "POST",
        data: {
          class: selectedClass,
          type: 'getYears'
        },
        dataType: "html"
      }).done(function(msg) {
        $("#resultYear").html(msg);
        $("#resultYear").prop('disabled', false);
      });

      // Fetch Sections
      $.ajax({
        url: ajaxUrl,
        method: "POST",
        data: {
          class: selectedClass,
          type: 'getSection'
        },
        dataType: "html"
      }).done(function(msg) {
        $("#resultSection").html(msg);
        $("#resultSection").prop('disabled', false);
      });

      // Fetch All Groups
      $.ajax({
        url: ajaxUrl,
        method: "POST",
        data: {
          class: selectedClass,
          type: 'getGroupsByClass'
        },
        dataType: "html"
      }).done(function(msg) {
        $("#resultGroup").html(msg);
        $("#resultGroup").prop('disabled', false);
      });
    });

    // Fetch Subjects when Exam Changes
    $('#resultExam').change(function() {
      var selectedExam = $(this).val();
      var selectedGroup = $('#resultGroup').val();

      $.ajax({
        url: ajaxUrl,
        method: "POST",
        data: {
          exam: selectedExam,
          group: selectedGroup,
          type: 'getExamSubject'
        },
        dataType: "html"
      }).done(function(msg) {
        $("#resultSubject").html(msg);
        $("#resultSubject").prop('disabled', false);
      });
    });

    // Fetch Subjects when Group Changes
    $('#resultGroup').change(function() {
      var selectedExam = $('#resultExam').val();
      var selectedGroup = $(this).val();

      if (selectedExam) {
        $.ajax({
          url: ajaxUrl,
          method: "POST",
          data: {
            exam: selectedExam,
            group: selectedGroup,
            type: 'getExamSubject'
          },
          dataType: "html"
        }).done(function(msg) {
          $("#resultSubject").html(msg);
          $("#resultSubject").prop('disabled', false);
        });
      }
    });

    // Interactive validation for result inputs (Client-side only)
    $('.resultInput').keyup(function(event) {
      $this = $(this);
      $val = $this.val();
      $max = $this.data('max');

      if ($val == '' || $val < ($max + 1) || $val == 'A' || $val == 'a') {
        $this.css('border-color', '#ddd');
        $this.removeClass('haserror');
      } else {
        $this.addClass('haserror');
        $this.css('border-color', 'red');
        $('.resultSubmit').prop('disabled', true);
      }

      if ($('.resultInput.haserror').length == 0) {
        $('.resultSubmit').prop('disabled', false);
      }
    });

  })(jQuery);
</script>