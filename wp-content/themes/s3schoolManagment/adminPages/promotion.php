<?php
/*
** Template Name: Admin Promotion
*/ 
 global $wpdb; global $s3sRedux;

 
if (isset($_POST['promotStu'])) {

	if ($_POST['promotion'] < 1) {
		$message = "Select minimum 1 student";
	}else{

		$toClass = $_POST['promotclass'];
		$infoYear = $_POST['infoYear'];
		$toSection = $_POST['promotsection'];
		$prvSection = $_GET['section'];


		foreach ($_POST['promotion'] as $stdid) {

	    $wpdb->query("DELETE FROM ct_studentinfo WHERE infoStdid = $stdid AND infoClass = $toClass AND infoYear = $infoYear");
	  
    	$prev4th = $_POST['info4thSub'][$stdid];
    	$prevOpt = $_POST['infoOptionals'][$stdid];
    	$info4thSub = $wpdb->get_results("SELECT subjectid FROM `ct_subject` WHERE subjectClass = $toClass AND subid = (SELECT subid FROM `ct_subject` WHERE subjectid = $prev4th) limit 1");

    	$info4thSub = isset($info4thSub[0]) ? $info4thSub[0]->subjectid : '';


    if ($prevOpt !== '' && is_array(json_decode($prevOpt, true)) && sizeof(json_decode($prevOpt, true)) > 0) {
    		$oldOpt = json_decode($prevOpt);

    		$infoOptSub = $wpdb->get_results("SELECT subjectid FROM `ct_subject` WHERE subjectClass = $toClass AND subid IN (SELECT subid FROM `ct_subject` WHERE subjectid IN (".implode (", ", $oldOpt)."))");
    		$subarr = array();
    		foreach ($infoOptSub as $opt) {
    			$subarr[] = $opt->subjectid;
    		}
    		$infoOptSub = json_encode($subarr);
    	}else{
    		$infoOptSub = '';
    	}

	    $newroll = $_POST['setRoll'][$stdid];

    	$insert = $wpdb->insert('ct_studentinfo', array(
	      'infoStdid' => $stdid,
	      'infoClass' => $toClass,
	      'infoSection' =>  $toSection,
	      'infoGroup' =>  $_POST['infoGroup'][$stdid],
	      'infoRoll' => $newroll,
	      'infoYear' => $infoYear,
	      'infoOptionals' => $infoOptSub,
	      'info4thSub' => $info4thSub
	    ));
	    $message = ms3message($insert, 'Updated');
		    
	    
    	if($insert){
	    	$update = $wpdb->update('ct_student', array( 'stdCurrentClass' => $toClass, 'stdCurntYear' => $infoYear), array('studentid' => $stdid));
	    }
		}
	}
}

// ==========================================
// AJAX ACTIONS - LOCAL HANDLER
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'])) {
  while (ob_get_level()) { ob_end_clean(); }

  // ------------------------------------------
  // Get Exams
  // ------------------------------------------
  if ($_POST['type'] == 'getExams') {
    $class = $_POST['class'];
    $exams = $wpdb->get_results("SELECT examid,examName FROM ct_exam WHERE examClass = '$class'");
    if (empty($exams)) { echo "<option value=''>No Exam for this Class</option>"; }
    else { echo "<option value=''>Select An Exam</option>"; }
    foreach ($exams as $exam) { echo "<option value='{$exam->examid}'>{$exam->examName}</option>"; }
    exit;
  }

  // ------------------------------------------
  // Get Years
  // ------------------------------------------
  elseif ($_POST['type'] == 'getYears') {
    $class = $_POST['class'];
    $years = $wpdb->get_results("SELECT infoYear FROM ct_studentinfo WHERE infoClass = $class GROUP BY infoYear ORDER BY infoYear ASC");
    if (empty($years)) { echo "<option value=''>No Student In this class</option>"; }
    else { echo "<option value=''>Year</option>"; }
    foreach ($years as $year) { echo "<option value='{$year->infoYear}'>{$year->infoYear}</option>"; }
    exit;
  }

  // ------------------------------------------
  // Get Sections
  // ------------------------------------------
  elseif ($_POST['type'] == 'getSection') {
    $class = $_POST['class'];
    $sections = $wpdb->get_results("SELECT sectionid,sectionName FROM ct_section WHERE forClass = '$class' ORDER BY sectionName");
    if (!empty($sections)) {
      echo "<option value=''>Section</option>";
      foreach ($sections as $section) { echo "<option value='{$section->sectionid}'>{$section->sectionName}</option>"; }
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
    $groups = $wpdb->get_results("SELECT DISTINCT ct_group.groupId, ct_group.groupName FROM ct_group INNER JOIN ct_studentinfo ON ct_studentinfo.infoGroup = ct_group.groupId WHERE ct_studentinfo.infoClass = '$class' ORDER BY ct_group.groupName ASC");
    echo "<option value=''>All Groups</option>";
    foreach ($groups as $group) { echo "<option value='{$group->groupId}'>{$group->groupName}</option>"; }
    exit;
  }

  // ------------------------------------------
  // Get Exam Subjects
  // ------------------------------------------
  elseif ($_POST['type'] == 'getExamSubject') {
    $exam = intval($_POST['exam']);
    $group = isset($_POST['group']) ? $_POST['group'] : '';
    $subs = $wpdb->get_var("SELECT examSubjects FROM ct_exam WHERE examid = $exam");
    $subs_arr = !empty($subs) ? json_decode($subs, true) : [];
    if (!empty($subs_arr)) {
      $subs_escaped = array_map('intval', $subs_arr);
      $subjectQuery = "SELECT subjectid,subjectName FROM ct_subject WHERE subjectid IN (" . implode(',', $subs_escaped) . ")";
      if (!empty($group)) { $subjectQuery .= " AND (forGroup = 'all' OR forGroup = '$group' OR forGroup LIKE '%\"$group\"%')"; }
      $subjectQuery .= " ORDER BY subjectName ASC";
      $subjects = $wpdb->get_results($subjectQuery);
      if (empty($subjects)) { echo "<option value=''>No subject!</option>"; }
      else {
        echo "<option value=''>Select Subject</option>";
        foreach ($subjects as $subject) { echo '<option value="' . $subject->subjectid . '">' . $subject->subjectName . '</option>'; }
      }
    } else { echo "<option value=''>No subject!</option>"; }
    exit;
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

<div class="container-fluid maxAdminpages" style="padding-left: 0">

	<div class="row">
		<!-- Show Status message -->
  	<?php if(isset($message)){ ms3showMessage($message); } ?>

		<div class="col-md-12">
			<div class="panel panel-info">
			  <div class="panel-heading"><h3>Promotion<br><small>Promot students</small></h3></div>
			  <div class="panel-body">

					<form class="form-inline" action="" method="GET">
						<input type="hidden" name="page" value="promotion">

						<div class="form-group">
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
							<select id="resultSection" class="form-control" name="section" disabled>
								<option disabled selected>Select Class First</option>
							</select>
						</div>

						<div class="form-group">
							<label>Year</label>
							<select id='resultYear' class="form-control" name="syear" required disabled>
								<option disabled selected>Select Class First</option>
							</select>
						</div>

						<div class="form-group" id="idRoll">
							<input class="form-control" type="text" name="roll" placeholder="Roll" style="width: 110px">
						</div>
						<div class="form-group">
							<input type="submit" name="creatId" value="Genarate List" class="btn btn-primary">
						</div>
					</form>
			  </div>
			</div>
		</div>

		<?php if(isset($_GET['syear'])){ ?>

		  <div id="printArea" class="col-md-12 printBG">

			  <div >

			  	<?php
			  		$year 		= $_GET['syear'];
			  		$class 		= $_GET['class'];
			  		$exam 		= $_GET['exam'];
			  		$section 	= isset($_GET['section']) ? $_GET['section'] : '';
			  		$roll 		= isset($_GET['roll']) ? $_GET['roll'] : '';

			  		if (isset($_GET['syear'])) {

			  			$querry = "SELECT stdName,infoRoll,className,sectionName,groupName,spPosition,spClassPosition,spFaild,studentid,infoGroup,info4thSub,infoOptionals FROM `ct_studentPoint`
  								LEFT JOIN ct_student ON ct_student.studentid = ct_studentPoint.spStdID
  								LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid AND $class = ct_studentinfo.infoClass AND '$year' = ct_studentinfo.infoYear
									LEFT JOIN ct_class ON ct_studentinfo.infoClass = ct_class.classid
									LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId
									LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
								WHERE infoYear = '$year' AND spExam = $exam AND infoClass = $class AND stdCurrentClass = $class AND stdCurntYear = '$year'";

							$querry .= ($roll != "") ? " AND infoRoll = $roll" : '';
							$querry .= ($section != "") ? " AND infoSection = $section ORDER BY spPosition" : ' ORDER BY spClassPosition';
							$groupsBy = $wpdb->get_results($querry);
			  		}
			  		

			  		if($groupsBy){
			  			?>
			  			<form action="" method="post" class="form-inline">
			  				<div class="form-top">
			  					<div class="form-group">
										<select id="proClass" class="form-control" name="promotclass" required >
											<option value="">Select Class</option>
											<?php
												$classedQuery = $wpdb->get_results( "SELECT classid,className FROM ct_class" );
												foreach ($classedQuery as $classRes) {
													$id = $classRes->classid;
													$name = $classRes->className;
													$disable = ($class == $classRes->classid) ? 'disabled' : '';
													echo "<option value='$id'>$name</option>";
												}
											?>
										</select>
									</div>
									<div class="form-group">
										<select id="proSec" class="form-control" name="promotsection" required disabled>
											<option value="">Select Class First</option>
										</select>
									</div>
									<div class="form-group">
										<select class="form-control" name="infoYear" required>
											<option value="">Select Year</option>
											<?php for ($i=-2; $i < 3; $i++) { 
		                    $sec = (date("Y")-$i)."-".(date("Y")-($i-1));
		                    $selected = ($edit->stdCurntYear == $sec) ? 'selected' : '';
		                    ?>
		                      <option value="<?= $sec; ?>" <?= $selected; ?>><?= $sec; ?></option>
		                    <?php
		                  } ?>
		                  <?php for ($i=-2; $i < 3; $i++) { 
		                    $sec = (date("Y")-$i);
		                    $selected = ($edit->stdCurntYear == $sec) ? 'selected' : '';
		                    ?>
		                      <option value="<?= $sec; ?>" <?= $selected; ?>><?= $sec; ?></option>
		                    <?php
		                  } ?>
										</select>
									</div>
                  <div class="form-group">
										<label> Add additional</label>
										<input id="addAdditional" style="width: 80px" class="form-control" type="number" >
									</div>
                  
                  <div class="form-group" style="margin-left: 8px;">
										<label> Start from</label>
										<input id="reorderStart" style="width: 40px" class="form-control" type="text" value="1">
									</div>
									<button type="button" id="reorderRoll" class="btn btn-info" style="margin-right: 8px;">Reorder Roll</button>

									
			  					<input class="btn btn-success pull-right" type="submit" name="promotStu" value="Promote">
			  				</div>

                <div class="form-top" style="width: 100%; padding-top: 25px; display: flex; justify-content: flex-end; align-items: end;">									
									<div class="form-group" style="margin-left: auto;">
										<label> Auto Select Filter</label>
										<select id="selectFilter" class="form-control" style="width: 150px;">
											<option value="">Choose...</option>
											<option value="firstHalf">First Half</option>
                      <option value="lastHalf">Last Half</option>
											<option value="evenStudents">Even Students</option>
											<option value="oddStudents">Odd Students</option>
											<option value="First Third 1/3">First Third 1/3</option>
											<option value="Second Third 2/3">Second Third 2/3</option>
											<option value="Last Third 3/3">Last Third 3/3</option>
										</select>
									</div>
									<button type="button" id="applySelectFilter" class="btn btn-warning" style="margin-right: 8px;">Apply Selection</button>

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
			  						<th>Position</th>
			  						<th>Assign Roll</th>
			  						<th><label class="labelRadio">Select <input id="selectAll" type="checkbox"></label></th>
			  					</tr>
			  					<?php
			  					
			  					foreach ($groupsBy as $key => $value) {
			  					    
			  					    $rank = 0;
			  					    if($section != ""){ // when section is selected
			  					        $rank = $value->spPosition;
			  					    }else {
			  					        $rank = $value->spClassPosition;
			  					    }
			  					    
									?>
									

										<tr>
					  					<td><?= $key+1 ?></td>
					  					<td><?= $value->stdName ?></td>
					  					<td><?= $value->infoRoll ?></td>
					  					<td><?= $value->className ?></td>
					  					<td><?= $value->sectionName ?></td>
					  					<td><?= $value->groupName ?></td>
					  					<td><?= $rank ?><?= ($value->spFaild > 0) ? ' ('.$value->spFaild.' Sub Fail)' : ''; ?></td>
					  					<td>
					  						<input type="hidden" name="info4thSub[<?= $value->studentid ?>]" value='<?= str_replace("\"","",$value->info4thSub); ?>'>
					  						<input type="hidden" name="infoOptionals[<?= $value->studentid ?>]" value='<?= str_replace("\"","",$value->infoOptionals); ?>'>
					  						<input type="hidden" name="infoGroup[<?= $value->studentid ?>]" value='<?= $value->infoGroup; ?>'>
					  						<input class="assignRoll" type="number" name="setRoll[<?= $value->studentid ?>]" data-position="<?= $rank ?>" value="<?= $rank ?>">
					  					</td>
					  					<td>
					  						<input type="hidden" name="position[<?= $value->studentid ?>]" value="<?= $rank ?>">
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
							echo "<h3 class='text-center'>Result / Student not Found</h3>";
						}

			  	?>

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
  // ==================================
  // HANDLE AJAX ACTIONS LOCALLY
  // ==================================
  (function($) {
    // Use current page as AJAX URL for standalone processing
    var ajaxUrl = '';

    // Change Roll based on additional value
    $('#addAdditional').change(function(event) {
      var value = +$(this).val();
      $( ".assignRoll" ).each(function( index ) {
        $(this).val(+$(this).data('position') + value);
      });
    });

    // Reorder Assign Roll for selected rows, starting from the value in #reorderStart
    $('#reorderRoll').click(function() {
      var start = parseInt($('#reorderStart').val(), 10);
      if (isNaN(start) || start < 1) { start = 1; }
      var n = start;
      $('input.assignRoll').each(function() {
        var $roll = $(this);
        var $row = $roll.closest('tr');
        var $sel = $row.find('input.stdSel');
        if ($sel.length && $sel.is(':checked')) {
          $roll.val(n);
          n++;
        } else {
          $roll.val('');
        }
      });
    });

    // Fetch Sections for Promotion Class
    $('#proClass').change(function() {
      $.ajax({
        url: ajaxUrl,
        method: "POST",
        data: { class : $(this).val(), type : 'getSection' },
        dataType: "html"
      }).done(function( msg ) {
        $( "#proSec" ).html( msg );
        $( "#proSec" ).prop('disabled', false);
      });
    });

    // Fetch dependent dropdowns for Result Class
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

    // Select All Checkbox
    $('#selectAll').change(function() {
      if ($(this).is(':checked')) {
        $('.stdSel').prop('checked', true);
      } else {
        $('.stdSel').prop('checked', false);
      }
    });

    // Apply Selection Filter (First Half / Even / Odd)
    $('#applySelectFilter').click(function() {
      var filter = $('#selectFilter').val();
      if (!filter) { return; }

      var $rows = $('table.table tbody tr');
      var total = $rows.length;

      // Uncheck all first
      $('.stdSel').prop('checked', false);

      if (filter === 'firstHalf') {
        var half = Math.ceil(total / 2);
        $rows.each(function(index) {
          if (index < half) {
            $(this).find('.stdSel').prop('checked', true);
          }
        });
      } else if (filter === 'lastHalf') {
        var half = Math.ceil(total / 2);
        $rows.each(function(index) {
          if (index >= half) {
            $(this).find('.stdSel').prop('checked', true);
          }
        });
      }
       else if (filter === 'evenStudents') {
        $rows.each(function(index) {
          if ((index + 1) % 2 === 0) {
            $(this).find('.stdSel').prop('checked', true);
          }
        });
      } else if (filter === 'oddStudents') {
        $rows.each(function(index) {
          if ((index + 1) % 2 !== 0) {
            $(this).find('.stdSel').prop('checked', true);
          }
        });
      } else if (filter === 'First Third 1/3') {
        var third = Math.ceil(total / 3);
        $rows.each(function(index) {
          if (index < third) {
            $(this).find('.stdSel').prop('checked', true);
          }
        });
      } else if (filter === 'Second Third 2/3') {
        var third = Math.ceil(total / 3);
        $rows.each(function(index) {
          if (index >= third && index < 2 * third) {
            $(this).find('.stdSel').prop('checked', true);
          }
        });
      } else if (filter === 'Last Third 3/3') {
        var third = Math.ceil(total / 3);
        $rows.each(function(index) {
          if (index >= 2 * third) {
            $(this).find('.stdSel').prop('checked', true);
          }
        });
      }
    });

  })(jQuery);

  function print(divId) {
    var printContents = document.getElementById(divId).innerHTML;
    w = window.open();
    w.document.write(printContents);
    w.document.write('<scr' + 'ipt type="text/javascript">' + 'window.onload = function() { window.print(); window.close(); };' + '</sc' + 'ript>');
    w.document.close();
    w.focus();
    return true;
  }
</script>
