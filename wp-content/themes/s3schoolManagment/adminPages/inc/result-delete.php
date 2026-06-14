<?php
	if (isset($_POST['deleteRes'])) {

		$class = $_POST['class'];
		$sec = $_POST['sec'];
		$year = $_POST['syear'];
		$exam = $_POST['exam'];
		$subject = $_POST['subject'];
		$students = $_POST['promotion'];

		if(sizeof($students) > 0){
			$qrry = "DELETE FROM `ct_result` WHERE resStudentId IN (" . implode(',', $students) .") AND resClass = $class AND resultYear = '$year' AND resExam = $exam";
			$qrry .= ($sec != '') ? " AND resSec = $sec" :'';
			$qrry .= ($subject != '') ? " AND resSubject = $subject" :'';
			$delete = $wpdb->query( $qrry );
			$message = ms3message($delete, 'Delete');
 		}
	}

?>
<style>
    .panel {
      overflow: visible;
    }

    .panel-body {
      width: 100%;
    }

    .compact-filter-form {
        background: #f9f9f9;
        padding: 15px;
        border-radius: 4px;
    }
    
    .compact-filter-form .filter-row {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: flex-end;
    }
    
    .compact-filter-form .filter-field {
        flex: 1 1 auto;
        min-width: 140px;
        max-width: 200px;
    }
    
    .compact-filter-form .filter-field.row-break {
        flex-basis: 100%;
        width: 100%;
        height: 0;
        min-width: 100%;
        max-width: 100%;
        margin: 0;
        padding: 0;
        border: none;
        overflow: hidden;
    }
    
    .compact-filter-form .filter-field label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 3px;
        color: #555;
    }
    
    .compact-filter-form .filter-field select,
    .compact-filter-form .filter-field input {
        width: 100%;
        padding: 6px 8px;
        font-size: 13px;
        border: 1px solid #ddd;
        border-radius: 3px;
        height: 32px;
    }
    
    .compact-filter-form .filter-field select:focus,
    .compact-filter-form .filter-field input:focus {
        border-color: #5bc0de;
        outline: none;
        box-shadow: 0 0 0 2px rgba(91, 192, 222, 0.1);
    }
    
    .compact-filter-form .filter-btn {
        flex: 0 0 auto;
        min-width: 100px;
    }
    
    .compact-filter-form .filter-btn button,
    .compact-filter-form .filter-btn input[type="submit"] {
        width: 100%;
        height: 32px;
        padding: 6px 12px;
        font-size: 13px;
        line-height: 1.2;
    }
    
    @media (max-width: 768px) {
        .compact-filter-form .filter-field {
            flex: 1 1 calc(50% - 5px);
            max-width: none;
        }
        
        .compact-filter-form .filter-field.row-break {
            display: none;
        }
        
        .compact-filter-form .filter-btn {
            flex: 1 1 100%;
            min-width: 100%;
        }
    }
    
    @media (max-width: 480px) {
        .compact-filter-form .filter-field {
            flex: 1 1 100%;
        }
    }
</style>

<div class="panel panel-info">
	<div class="panel-heading">
		<h3>Delete Result</h3>
	</div>
	<div class="panel-body">
		<form action="" method="GET" class="compact-filter-form">
			<input type="hidden" name="page" value="result">
			<input type="hidden" name="view" value="delete">

			<div class="filter-row">
				<div class="filter-field">
					<label>Class *</label>
					<select id='resultClass' class="form-control" name="class" required>
					<?php

					$classQuery = $wpdb->get_results("SELECT classid,className FROM ct_class WHERE classid IN (SELECT examClass FROM ct_exam GROUP BY examClass ORDER BY className ASC)");

					echo "<option value=''>Select Class</option>";

					foreach ($classQuery as $class) {
						echo "<option value='" . $class->classid . "'>" . $class->className . "</option>";
					}
					?>
					</select>
				</div>

				<div class="filter-field">
					<label>Section</label>
					<select id="resultSection" class="form-control" name="sec" disabled>
						<option disabled selected>Select Class First</option>
					</select>
				</div>

				<div class="filter-field">
					<label>Group</label>
					<select id="resultGroup" class="form-control" name="grou">
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

				<div class="filter-field">
					<label>Religion</label>
					<select class="form-control" name="religion">
						<option value="">All Religions</option>
						<option value="Muslim">Muslim</option>
						<option value="Hinduism">Hinduism</option>
						<option value="Buddist">Buddist</option>
						<option value="Christian">Christian</option>
					</select>
				</div>

				<div class="filter-field">
					<label>Gender</label>
					<select class="form-control" name="gender">
						<option value="">All Genders</option>
						<option value="1">Male</option>
						<option value="0">Female</option>
						<option value="2">Other</option>
					</select>
				</div>

				<!-- Row Break for Desktop -->
				<div class="filter-field row-break"></div>                

        <div class="filter-field">
            <label>Exam *</label>
            <select id="resultExam" class="form-control" name="exam" required disabled>
                <option disabled selected>Select Class First</option>
            </select>
        </div>

				<div class="filter-field">
            <label>Year/Session</label>
            <select id='resultYear' class="form-control" name="syear" required disabled>
                <option disabled selected>Select Class First</option>
            </select>
        </div>

        <div class="filter-field">
            <label>Subject *</label>
            <select id='resultSubject' class="form-control" name="subject" required disabled>
                <option disabled selected>Select exam First</option>
            </select>
        </div>

        <div class="filter-field filter-btn">
            <input class="form-control btn-success" type="submit" name="" value="Go">
        </div>
			</div>
		</form>
	</div>

</div>

<?php if (!empty($delete_message)) { echo $delete_message; } ?>

<?php

if(isset($_GET['exam'])):
	$exam 	= $_GET['exam']; 
	$year 	= $_GET['syear']; 
	$class 	= $_GET['class'];
	$sec 		= isset($_GET['sec']) ? $_GET['sec'] : '' ;
	$sub 		= isset($_GET['subject']) ? $_GET['subject'] : '' ;
	
	?>

		<div id="printArea" class="col-md-12">
		  <div >

		  	<?php

		  		$qrey = "SELECT studentid,stdName,infoRoll,className,sectionName,groupName,examName";

		  		if($sub != ''){ $qrey .= ",subjectName"; }

		  		$qrey .= " FROM ct_student
	  				LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid AND ct_student.stdCurrentClass = ct_studentinfo.infoClass
						LEFT JOIN ct_class ON ct_studentinfo.infoClass = ct_class.classid
						LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId
						LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
						LEFT JOIN ct_exam ON ct_exam.examid = $exam";

				if($sub != ''){ $qrey .= " LEFT JOIN ct_subject ON ct_subject.subjectid = $sub"; }
					
				$qrey .= " WHERE stdCurntYear = '$year' AND stdCurrentClass = $class " . $religionFilter;

				if($sec != ''){ $qrey .= " AND infoSection = $sec"; }

				if($group != ''){ $qrey .= " AND infoGroup = $group"; }

				$qrey .= " AND studentid IN (SELECT resStudentId FROM `ct_result` WHERE resClass = $class AND resultYear = '$year' AND resExam = $exam";

  			if($sub != ''){ $qrey .= " AND resSubject = $sub"; }

  			if($sec != ''){ $qrey .= " AND resSec = $sec"; }
  			
  			$qrey .= ") ORDER BY infoRoll ASC";	  			
	  			$groupsBy = $wpdb->get_results($qrey);


		  		if($groupsBy){
		  			?>
		  			<form action="" method="post">
		  				<input type="hidden" name="exam" value="<?= $exam ?>">
		  				<input type="hidden" name="syear" value="<?= $year ?>">
		  				<input type="hidden" name="class" value="<?= $class ?>">
		  				<input type="hidden" name="sec" value="<?= $sec ?>">
		  				<input type="hidden" name="subject" value="<?= $sub ?>">
		  				<div class="text-right">
		  					<div class="pull-left text-left">
		  						Delete Result of Class: <?= $groupsBy[0]->className ?>, Section: <?= $groupsBy[0]->sectionName ?>, Exam: <?= $groupsBy[0]->examName ?>, Year: <?= $year ?>, Subject: <?= ($sub != '') ? $groupsBy[0]->subjectName : 'All'; ?>
		  					</div>
		  					<input class="btn btn-success" name="deleteRes" type="submit" value="Delete">
		  				</div>
		  				<br>
		  				<table class="table table-responsive table-striped table-bordered">
		  					<tr>
		  						<th>#</th>
		  						<th>Name</th>
		  						<th>Roll</th>
		  						<th>Class</th>
		  						<th>Section</th>
		  						<th>Group</th>
		  						<th><label class="labelRadio">Select <input id="selectAll" type="checkbox"></label></th>
		  					</tr>
		  					<?php
		  					foreach ($groupsBy as $key => $value) {
								?>
									<tr>
				  					<td><?= $key+1 ?></td>
				  					<td><?= $value->stdName ?></td>
				  					<td><?= $value->infoRoll ?></td>
				  					<td><?= $value->className ?></td>
				  					<td><?= $value->sectionName ?></td>
				  					<td><?= $value->groupName ?></td>
				  					<td>
				  						<label class="labelRadio">
				  							<input class="stdSel" type="checkbox" name="promotion[]" value="<?= $value->studentid ?>"> Select
				  						</label>
				  					</td>
				  				</tr>
								<?php
								}
								?>
							</table>
						</form>
						<?php

					}else{
						echo "<h3 class='text-center'>No Student Found</h3>";
					}

		  	?>

		  </div>
	  </div>
	<?php 
endif; ?>


<script type="text/javascript">
	(function($) {
		$('#resultClass').change(function() {
      var $siteUrl = $('#theSiteURL').text();
      $.ajax({
        url: $siteUrl+"/inc/ajaxAction.php",
        method: "POST",
        data: { class : $(this).val(), type : 'getExams' },
        dataType: "html"
      }).done(function( msg ) {
        $( "#resultExam" ).html( msg );
        $( "#resultExam" ).prop('disabled', false);
      });

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


    $('#resultExam').change(function() {
      var $siteUrl = $('#theSiteURL').text();

      $.ajax({
        url: $siteUrl+"/inc/ajaxAction.php",
        method: "POST",
        data: { exam : $(this).val(), type : 'getExamSubject' },
        dataType: "html"
      }).done(function( msg ) {
        $( "#resultSubject" ).html( msg );
        $( "#resultSubject" ).prop('disabled', false);
      });

    });
  })( jQuery );
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