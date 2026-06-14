<?php
 
 global $wpdb; global $s3sRedux; 


if (isset($_POST['stuWithheld'])) {
    // echo '<pre>';
    // print_r($_POST);exit;

$allList = isset($_POST['stdidswithheld']) ? $_POST['stdidswithheld'] : [];


// echo '<pre>';
//     print_r($allList);exit;



// add condition for year exam class to avoid update wrong info


$resExam = isset($_POST['resExam']) ? $_POST['resExam'] : null;
$resultYear = isset($_POST['resultYear']) ? $_POST['resultYear'] : null;
$resclass = isset($_POST['resclass']) ? $_POST['resclass'] : null;
$ressec = isset($_POST['ressec']) ? $_POST['ressec'] : null;
$resgroup = isset($_POST['resgroup']) ? $_POST['resgroup'] : null;



// Ensure it's an array
if (is_array($allList) && !empty($allList)) {
foreach ($allList as $id) {
		$update1 = $wpdb->update(
		'ct_result',
			array(
				'withheld' 		=> 0
			),
			array( 'resStudentId' => $id,'resultYear' =>$resultYear,'resExam'=>$resExam)
		);
	}
}
// echo '<pre>';
// print_r($_POST['withheld']);exit;
if(isset($_POST['withheld'])){
		foreach ($_POST['withheld'] as $stdid) {
	    		$update = $wpdb->update(
		'ct_result',
			array(
				'withheld' 		=> 1
			),
			array( 'resStudentId' => $stdid,'resultYear' =>$resultYear,'resExam'=>$resExam)
		);
	    }
}
	    if (isset($update)) {
		$message = array('status' => 'success', 'message' => 'Successfully Updated.' );
	}else{
		$message = array('status' => 'faild', 'message' => 'Make sure you fill correct input.' );
	}


    
}

?>

<?php if ( ! is_admin() ) { get_header(); ?>
<div class="b-layer-main">

	<div class="">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
<?php } ?>
<?php

							if (isset($message)) {
								?>
									<div class="messageDiv">
										<div class="alert <?= ($message['status'] == 'success') ? 'alert-success' : 'alert-danger';  ?>">
											<?= $message['message'] ?>
										</div>
									</div>
								<?php
							}
						?>

							<div class="panel panel-info">
	<div class="panel-heading"><h3>Withheld Student</h3></div>
	<div class="panel-body">
		<form action="" method="GET" class="form-inline">

			<div class="form-group">
				<input type="hidden" name="page" value="result">
				<input type="hidden" name="view" value="withheld">
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
			
			<div class="form-group ">
					<label>Group</label>
					<select id="resultGroup" class="form-control" name="grou">
						<option value="">Select Group</option>
						<?php
            	            $groups = $wpdb->get_results("SELECT * FROM ct_group");
            	            foreach ($groups as $groups) {
            	              ?>
            	              <option value='<?= $groups->groupId ?>' >
            	                <?= $groups->groupName ?>
            	              </option>
            	              <?php
            	            }
            	          ?>
					</select>
				</div>

			<div class="form-group">
				<label>Year/Session</label>
				<select id='resultYear' class="form-control" name="syear" required disabled>
					<option disabled selected>Select Class First</option>
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
	
	$sec 		= isset($_GET['sec']) ? $_GET['sec'] : '';
	$grou 	= $_GET['grou'];

	

	$results = "SELECT examName,className";
	if($sec!= ''){$results .= " ,sectionName";}
	    $results .= " FROM ct_exam";
	    $results .= " LEFT JOIN ct_class ON ct_exam.examClass = ct_class.classid";
		if($sec!= ''){$results .= " LEFT JOIN ct_section ON ct_section.sectionid = $sec";}
		$results .= " where examid = $exam";
		$info = $wpdb->get_results($results);

	?>

	<div class="panel panel-info">
		<div class="panel-heading"><h3>Withheld Students</h3></div>
		<div class="panel-body">
				<div class="text-right">
					<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>
				</div>
				<form action="" method="POST">
			
					<div class="form-group">
						<input type="hidden" name="resExam" value='<?= $exam; ?>'>
						<input type="hidden" name="resultYear" value='<?= $year; ?>'>
						<input type="hidden" name="resclass" value='<?= $class; ?>'>
						<input type="hidden" name="ressec" value='<?= $sec; ?>'>
						<input type="hidden" name="resgroup" value='<?= $grou; ?>'>

					
						<div id="printArea">
							<style type="text/css"> @page{ size: auto;  margin: 0px; } </style>
							<link rel="stylesheet" media="print" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" />
						  <div class="printArea" style="margin: 20px;">
								<h3>
									<b>Class:</b> <?= $info[0]->className ?>,
									<b>Exam:</b> <?= $info[0]->examName ?>,
									<b>Year:</b> <?= $_GET['syear'] ?>
								</h3>

								<div class="table-responsive">
									<table id="resultInputTable" class="table table-bordered ">
										<tr>
											<th>Name</th>
											<th>Roll</th>
											<th>Group</th>
											<th>Sec</th>
											<th><label class="labelRadio">Select <input id="selectAll" type="checkbox"></label></th>
										</tr>
									

										<?php
												$stdQuery = "
                                                SELECT 
                                                    studentid, infoRoll,sectionName,groupName, stdName,withheld, infoGroup, infoSection 
                                                FROM 
                                                    ct_student 
                                                LEFT JOIN 
                                                    ct_studentinfo 
                                                    ON ct_student.studentid = ct_studentinfo.infoStdid 
                                                    AND ct_studentinfo.infoClass = %d 
                                                    AND ct_studentinfo.infoYear = %s 
                                                LEFT JOIN 
                                                    ct_section 
                                                    ON ct_studentinfo.infoSection = ct_section.sectionid 
                                                    AND stdCurntYear = %s 
                                                    AND stdCurrentClass = %d
                                                    LEFT JOIN 
                                                    ct_group 
                                                    ON ct_studentinfo.infoGroup = ct_group.groupid 
                                                    LEFT JOIN 
                                                    ct_result 
                                                    ON ct_student.studentid = ct_result.resStudentId 
                                                WHERE 1=1
                                            ";
                                            
                                            // Dynamically add conditions based on `$sec` and `$grou`
                                            if ($sec != "") {
                                                $stdQuery .= " AND infoSection = %d";
                                            }
                                            if ($grou != "") {
                                                $stdQuery .= " AND infoGroup = %d";
                                            }
                                            
                                            // Add the ORDER BY clause
                                            $stdQuery .= " GROUP BY ct_student.studentid  ORDER BY infoRoll ASC";
                                            
                                            // Prepare and execute the query
                                            $queryParams = [$class, $year, $year, $class];
                                            if ($sec != "") {
                                                $queryParams[] = $sec;
                                            }
                                            if ($grou != "") {
                                                $queryParams[] = $grou;
                                            }
                                            
                                            $stdQuery = $wpdb->prepare($stdQuery, ...$queryParams);
                                            $stdQuery = $wpdb->get_results($stdQuery);

							
											foreach ($stdQuery as $student) {
								// 			echo "<pre>";print_r($student);exit;
												?>
												<input type="hidden" name="stdidswithheld[]" value='<?= $student->studentid ?>'>
												

												<tr>
													<td><?= $student->stdName ?></td>
													<td><?= $student->infoRoll ?></td>
													<td><?= $student->groupName ?></td>
													<td><?= $student->sectionName ?></td>
													<td>
									
										<label class="labelRadio">
											<input class="stdSel" type="checkbox" name="withheld[]" value="<?= $student->studentid ?>" <?= $student->withheld == 1? 'checked':''; ?>> Select
										</label>
									</td>
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

					<?php if($stdQuery){ ?>
						<div class="form-group">
							<input name="stuWithheld" class="form-control btn-success" type="submit" value="Update">
						</div>
					<?php } ?>
				</form>
			
		</div>
	</div>

	<?php 
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