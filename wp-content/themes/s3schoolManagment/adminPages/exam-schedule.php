<?php
/**
 * Template Name: Admin Exam Schedule
*/
get_header();

global $wpdb;
global $s3sRedux;

get_header();
?>
<div class="b-layer-main">
	<div class="container">
		<div class="row">
			<div class="col-md-12">
				<p id="theSiteURL" class="hidden"><?= get_template_directory_uri() ?></p>
				<div class="panel panel-info">
					<div class="panel-heading">
						<h3>Exam Schedule<br><small>Create and manage exam schedules</small></h3>
					</div>
					<div class="panel-body" style="padding-top:0;">
						<form id="scheduleFilter" class="form-inline" onsubmit="return false;">
							<div class="form-group">
								<label>Class</label>
								<select id="schedClass" class="form-control" required>
									<option value="">Select Class</option>
									<?php
									$classes = $wpdb->get_results("SELECT classid, className FROM ct_class ORDER BY className ASC");
									foreach ($classes as $c) {
										echo "<option value='" . esc_attr($c->classid) . "'>" . esc_html($c->className) . "</option>";
									}
									?>
								</select>
							</div>

							<div class="form-group">
								<label>Exam</label>
								<select id="schedExam" class="form-control" required disabled>
									<option>Select Class First</option>
								</select>
							</div>

							<div class="form-group">
								<label>Year</label>
								<select id="schedYear" class="form-control" required disabled>
									<option>Select Class First</option>
								</select>
							</div>

							<div class="form-group">
								<button id="loadSubjects" class="btn btn-primary" type="button" disabled>Load Subjects</button>
							</div>
						</form>

						<hr>

						<div id="scheduleArea" style="display:none;">
							<form id="scheduleForm">
								<input type="hidden" id="schClassId" name="classid">
								<input type="hidden" id="schExamId" name="examid">
								<input type="hidden" id="schYear" name="year">

								<div id="subjectsList"></div>

								<div style="margin-top:15px;">
									<button id="saveSchedule" class="btn btn-success" type="button">Save Schedule</button>
									<span id="saveStatus" style="margin-left:15px;"></span>
								</div>
							</form>
						</div>

					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
(function($){
	var siteUrl = $('#theSiteURL').text() || '';

	$('#schedClass').change(function(){
		var cls = $(this).val();
		if(!cls) return;
		// get exams
		$.post(siteUrl + '/inc/ajaxAction.php', { type: 'getExams', class: cls }, function(html){
			$('#schedExam').html(html).prop('disabled', false);
		}, 'html');

		// get years
		$.post(siteUrl + '/inc/ajaxAction.php', { type: 'getYears', class: cls }, function(html){
			$('#schedYear').html(html).prop('disabled', false);
		}, 'html');

		$('#loadSubjects').prop('disabled', true);
	});

	$('#schedExam, #schedYear').change(function(){
		var exam = $('#schedExam').val();
		var year = $('#schedYear').val();
		$('#loadSubjects').prop('disabled', !(exam && year));
	});

	$('#loadSubjects').click(function(){
		var classid = $('#schedClass').val();
		var examid = $('#schedExam').val();
		var year = $('#schedYear').val();
		if(!classid || !examid || !year) return alert('Select class, exam and year');

		// load subjects for exam
		$.post(siteUrl + '/inc/ajaxAction.php', { type: 'getExamSubjectsJson', exam: examid }, function(json){
			var subjects = json || [];
			var html = '';
			if(subjects.length === 0){
				html = '<p>No subjects found for selected exam.</p>';
			} else {
				html += '<table class="table table-bordered"><thead><tr><th>Subject</th><th>Date (YYYY-MM-DD)</th></tr></thead><tbody>';
				for(var i=0;i<subjects.length;i++){
					var s = subjects[i];
					html += '<tr data-subjectid="'+s.subjectid+'">';
					html += '<td>'+s.subjectName+'</td>';
					html += '<td><input class="form-control subj-date" name="date_'+s.subjectid+'" type="date" /></td>';
					html += '</tr>';
				}
				html += '</tbody></table>';
			}

			$('#subjectsList').html(html);
			$('#scheduleArea').show();
			$('#schClassId').val(classid);
			$('#schExamId').val(examid);
			$('#schYear').val(year);

			// load existing schedule
			$.post(siteUrl + '/inc/ajaxAction.php', { type: 'getExamSchedule', classid: classid, examid: examid, year: year }, function(data){
				try{
					var sd = (typeof data === 'string') ? JSON.parse(data) : data;
					for(var sid in sd){
						var val = sd[sid];
						$('#subjectsList').find('tr[data-subjectid="'+sid+'"] .subj-date').val(val);
					}
				}catch(e){
					// ignore
				}
			}, 'json');

		}, 'json');
	});

	$('#saveSchedule').click(function(){
		var classid = $('#schClassId').val();
		var examid = $('#schExamId').val();
		var year = $('#schYear').val();
		if(!classid || !examid || !year) return alert('Missing required context');

		var subject_dates = {};
		$('#subjectsList').find('tr').each(function(){
			var sid = $(this).data('subjectid');
			var date = $(this).find('.subj-date').val() || '';
			subject_dates[sid] = date;
		});

		$('#saveStatus').text('Saving...');

		$.post(siteUrl + '/inc/ajaxAction.php', {
			type: 'saveExamSchedule',
			classid: classid,
			examid: examid,
			year: year,
			subject_dates: JSON.stringify(subject_dates)
		}, function(resp){
			try{
				if(resp.success){
					$('#saveStatus').text(resp.message).css('color','green');
				} else {
					$('#saveStatus').text(resp.message).css('color','red');
				}
			}catch(e){
				$('#saveStatus').text('Unexpected response').css('color','red');
			}
		}, 'json');
	});

})(jQuery);
</script>

<?php get_footer();

?>


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

    if ($restrictions_enabled && $is_teacher) {
      $allowed_sections = $teacher_assignments['sections'];

      // Add class teacher section if applicable
      if ($teacher_assignments['class_teacher_class'] == $class && !empty($teacher_assignments['class_teacher_section'])) {
        $allowed_sections[] = $teacher_assignments['class_teacher_section'];
      }

      if (!empty($allowed_sections)) {
        $has_all = in_array('all', $allowed_sections);
        if (!$has_all) {
          $sections_query .= " AND sectionid IN (" . implode(',', array_map('intval', $allowed_sections)) . ")";
        }
      } elseif (!$teacher_has_assigned_classes) {
        // Logic gap: if teacher has no section assigned but has class assigned? 
        // Assuming sections list follows restrictions
      }
    }

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

    // Apply teacher restrictions if enabled
    if ($restrictions_enabled && $is_teacher && !empty($teacher_assignments['subjects'])) {
      $groups_query .= " AND ct_studentinfo.infoGroup IN (
                SELECT DISTINCT forGroup 
                FROM ct_subject 
                WHERE subjectid IN (" . implode(',', $teacher_assignments['subjects']) . ") 
                AND subjectClass = '$class'
                AND forGroup != 'all'
            )";
    }

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

    // Teacher Restrictions
    if ($restrictions_enabled && $is_teacher) {
      $exam_class = $wpdb->get_var($wpdb->prepare("SELECT examClass FROM ct_exam WHERE examid = %d", $exam));
      $is_class_teacher = ($teacher_assignments['class_teacher_class'] == $exam_class);

      // If not class teacher, restrict subjects
      if (!$is_class_teacher && !empty($teacher_assignments['subjects'])) {
        $subs = array_intersect($subs, $teacher_assignments['subjects']);
      }
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