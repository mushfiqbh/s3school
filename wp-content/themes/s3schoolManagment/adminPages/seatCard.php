<?php
/*
** Template Name: Admin SeatCard
*/
global $wpdb;
global $s3sRedux;


$attendanceGroups = $wpdb->get_results("SELECT groupId, groupName FROM ct_group ORDER BY groupName ASC");
$attendanceGroupLookup = array();
if (!empty($attendanceGroups)) {
    foreach ($attendanceGroups as $attendanceGroup) {
        $attendanceGroupLookup[$attendanceGroup->groupId] = $attendanceGroup->groupName;
    }
}

$genderSelectOptions = array(
    '' => 'All Genders',
    '1' => 'Boy',
    '0' => 'Girl',
    '2' => 'Other',
);

$selectedGroupFilter = isset($_GET['group']) ? intval($_GET['group']) : 0;
$rawGenderFilter = isset($_GET['gender']) ? trim($_GET['gender']) : '';
$selectedGenderFilter = array_key_exists($rawGenderFilter, $genderSelectOptions) ? $rawGenderFilter : '';
?>

<?php if ( ! is_admin() ) { get_header(); ?>
<div class="b-layer-main">

	<div class="">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
<?php } ?>

<div class="container-fluid maxAdminpages" style="padding-left: 0">

	<p id="theSiteURL" class="hidden"><?= get_template_directory_uri() ?></p>
	<div class="row">

		<div class="col-md-12">
			<div class="panel panel-info">
			  <div class="panel-heading"><h3>Seat Card<br><small>Create Students Seat Card</small></h3></div>
			  <div class="panel-body">
					<form class="form-inline" action="" method="GET">
						<input type="hidden" name="page" value="seatcard">

						<div class="form-group">
							<label>Class</label>
							<select id='resultClass' class="form-control" name="class" required>
								<?php

										$classQuery = $wpdb->get_results(
											"SELECT classid,className FROM ct_class WHERE classid IN (SELECT examClass FROM ct_exam GROUP BY examClass ORDER BY className ASC)"
										);
									
									
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
							<select id="resultSection" class="form-control" name="section" required disabled>
								<option disabled selected>Select Class First</option>
							</select>
						</div>

						<div class="form-group">
							<label>Year</label>
							<select id='resultYear' class="form-control" name="syear" required disabled>
								<option disabled selected>Select Class First</option>
							</select>
						</div>

						<div class="form-group">
							<label>Group</label>
							<select id="resultGroup" class="form-control" name="group">
								<option value="0">All Groups</option>
								<?php foreach ($attendanceGroups as $group) { ?>
									<option value="<?php echo $group->groupId; ?>" <?php echo ($selectedGroupFilter == $group->groupId) ? 'selected' : ''; ?>><?php echo $group->groupName; ?></option>
								<?php } ?>
							</select>
						</div>

						<div class="form-group">
							<label>Gender</label>
							<select id="resultGender" class="form-control" name="gender">
								<?php foreach ($genderSelectOptions as $key => $label) { ?>
									<option value="<?php echo $key; ?>" <?php echo ($selectedGenderFilter === $key) ? 'selected' : ''; ?>><?php echo $label; ?></option>
								<?php } ?>
							</select>
						</div>


						<div class="form-group" id="idRoll">
							<input class="form-control" type="text" name="roll" placeholder="Roll">
						</div>
						<div class="form-group">
							<input type="submit" value="Genarate" class="btn btn-primary">
						</div>
					</form>
			  </div>
			</div>
		</div>

		<?php if(isset($_GET['syear'])){ ?>
	  	<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>

		  <div id="printArea" class="col-md-12 printBG">
		  	<div style="max-width: 8.27in; margin: auto; background: #f9f9f9;">
			  	<style type="text/css"> @page { size: auto;  margin: 0px; } </style>

				  <div class="printArea">
				  	<?php

				  		$year 		= $_GET['syear'];
				  		$class 		= $_GET['class'];
				  		$section 	= $_GET['section'];
				  		$roll 		= $_GET['roll'];
				  		$exam 		= $_GET['exam']; 

				  		if (isset($_GET['syear'])) {
				  			$query = "SELECT stdName,infoRoll,className,stdImg,groupName,infoYear,stdPhone,stdFather,sectionName,examName FROM ct_student
									LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid AND ct_student.stdCurrentClass = ct_studentinfo.infoClass
									LEFT JOIN ct_class ON ct_studentinfo.infoClass = ct_class.classid
									LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId
									LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
									LEFT JOIN ct_exam ON ct_exam.examid = $exam
									WHERE infoYear = '$year' ";
 
					  		$query .= ($_GET['roll'] != '') ? " AND infoRoll IN ($roll)" : ''; 
					  		$query .= ($_GET['section'] != 0) ? " AND infoSection = $section" : '';
					  		$query .= ($selectedGroupFilter > 0) ? " AND infoGroup = $selectedGroupFilter" : '';
					  		if ($selectedGenderFilter !== '') {
					  		    $query .= ' AND ct_student.stdGender = ' . intval($selectedGenderFilter);
					  		}
					  		$query .= " ORDER BY infoRoll ASC";
					  		$groupsBy = $wpdb->get_results( $query );
					  	}

				  		if($groupsBy){

								foreach ($groupsBy as $value) {
									?>
										<div style="width: calc(50% - 50px);height: calc(2.338in - 44px);display: inline-block;margin: 5px 10px;border: 2px solid #333;overflow: hidden;padding: 10px;">

											<div style="text-align: center; margin-bottom: 7px; position: relative;">
												<img style="width: 50px; position: absolute;left: 0;top: 0;" src="<?= $s3sRedux['instLogo']['url'] ?>">
												<h2 style="margin: 0 0 3px;color: #337ab7; font-weight: bold;font-size: 18px;text-align: center;padding-left: 50px;line-height: 1;"><?= $s3sRedux['institute_name'] ?></h2>
							  				<h5 style="margin: 0 0 3px;color: #000;padding-left: 50px"><?= $s3sRedux['institute_address'] ?></h5>
							  				<h4 style="margin: 0;color: #000;font-weight: bold;"><?= $value->examName ?> <?= $year ?></h4>
											</div>
								  		<div style="width: 80%; float: left;line-height: 18px">
							  			<table style="line-height: 1.1">
								  				<tr>
								  					<td><b>Name </b></td>
								  					<td style="padding: 0 5px;"><b>:</b></td>
								  					<td><?= $value->stdName ?></td>
								  				</tr>
								  				<tr>
								  					<td><b>Class </b></td>
								  					<td style="padding: 0 5px;"><b>:</b></td>
								  					<td><?= $value->className ?></td>
								  				</tr>
								  				<tr>
								  					<td><b>Section </b></td>
								  					<td style="padding: 0 5px;"><b>:</b></td>
								  					<td><?= $value->sectionName ?></td>
								  				</tr>
												<?php if (!empty($value->groupName)) { ?>
								  				<tr>
								  					<td><b>Group </b></td>
								  					<td style="padding: 0 5px;"><b>:</b></td>
								  					<td><?= $value->groupName ?></td>
								  				</tr>
												<?php } ?>
								  			</table>

								  		</div>
								  		<div style="width: 20%; float: right;">
												<h1 style="margin: 0 0 2px 0; text-align: center;">Roll<br><b> <?= $value->infoRoll ?></b></h1>
								  		</div>
								  	</div>

									<?php
								}
							}else{
								echo "<h3 class='text-center'>No Student Found</h3>";
							}

				  	?>

				  </div>
		  	</div>

		  </div>

		<?php } ?>
	</div>
</div>


<?php if ( ! is_admin() ) { ?>
				</div>
			</div>
		</div>
	</div>
</div>
<?php get_footer(); } ?>

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
	})( jQuery );
	
	function print(divId) {
    var printContents = document.getElementById(divId).innerHTML;
    w = window.open();
    w.document.write(printContents);
    w.document.write('<scr' + 'ipt type="text/javascript">' + 'window.onload = function() { window.print(); window.close(); };' + '</sc' + 'ript>');
    w.document.close(); // necessary for IE >= 10
    w.focus(); // necessary for IE >= 10
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