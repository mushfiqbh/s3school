<?php
/*
** Template Name: Admin Failed
*/ 
 global $wpdb; global $s3sRedux; 


if (isset($_POST['failed'])) {
	if ($_POST['promotion'] < 1) {
		$message = "Select minimum 1 student";
	}else{
		$prevClass = $_POST['prevClass'];
		$prevClass = $_POST['prevClass'];
		$prevSect  = $_POST['prevSection'];
		$infoYear  = $_POST['infoYear'];

		$roll = $wpdb->get_results("SELECT infoRoll FROM ct_studentinfo WHERE infoClass = $prevClass AND infoYear = $infoYear AND infoSection = $prevSect ORDER BY infoRoll DESC limit 1");

		$roll = $roll[0]->infoRoll;

		foreach ($_POST['promotion'] as $stdid) {
			$roll++;
			$previnfo = $wpdb->get_results("SELECT * FROM ct_studentinfo WHERE infoStdid = $stdid AND infoClass = $prevClass limit 1");
	   
    	$previnfo = $previnfo[0];
    	$insert = $wpdb->insert('ct_studentinfo', array(
	      'infoStdid' 		=> $stdid,
	      'infoClass' 		=> $previnfo->infoClass,
	      'infoSection' 	=> $previnfo->infoSection,
	      'infoGroup' 		=> $previnfo->infoGroup,
	      'infoRoll' 			=> $roll,
	      'infoYear' 			=> $infoYear,
	      'infoOptionals' => $previnfo->infoOptionals,
	      'info4thSub' 		=> $previnfo->info4thSub
	    ));
	    if($insert){
		    $message 	= ms3message($insert, 'Updated');
		    $update 	= $wpdb->update('ct_student', array('stdCurntYear' => $infoYear), array('studentid' => $stdid));
	    }
		}
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
			  <div class="panel-heading"><h3>Failed<br><small>Failed students</small></h3></div>
			  <div class="panel-body">

					<form class="form-inline" action="" method="GET">
						<input type="hidden" name="page" value="failed">

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

							$querry2 = "SELECT * FROM `ct_studentPoint`
			  								LEFT JOIN ct_student ON ct_student.studentid = ct_studentPoint.spStdID
			  								LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid AND $class = ct_studentinfo.infoClass AND '$year' = ct_studentinfo.infoYear
												LEFT JOIN ct_class ON ct_studentinfo.infoClass = ct_class.classid
												LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId
												LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
												WHERE spYear = '$year' AND spExam = $exam AND spClass = $class AND stdCurrentClass = $class AND stdCurntYear = '$year' AND spFaild != 0";

							$querry2 .= ($roll != "") ? " AND infoRoll = $roll" : '';
							$querry2 .= ($section != "") ? " AND infoSection = $section" : '';
							$querry2 .= " ORDER BY spFaild ASC, spTotalMark DESC, infoRoll ASC";
							$faildstd = $wpdb->get_results($querry2);

			  			
			  		}
			  		

			  		if($faildstd){
			  			?>
			  			<form action="" method="post" class="form-inline">
			  				<input type="hidden" name="prevClass" value="<?= $class ?>">
			  				<input type="hidden" name="prevYear" value="<?= $year ?>">
			  				<input type="hidden" name="prevSection" value="<?= $section ?>">
			  				<div class="form-top">
			  		
									
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
							
			  					<input class="btn btn-danger pull-right" type="submit" name="failed" value="Failed">
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
			  						<th><label class="labelRadio">Select <input id="selectAll" type="checkbox"></label></th>
			  					</tr>
			  					<?php
			  					
									foreach ($faildstd as $key => $value) {
										$stdid = $value->studentid;
										$olsyear = $year-1;
										$faildstd = $wpdb->get_results("SELECT infoRoll FROM `ct_studentinfo` WHERE `infoClass` = $class AND `infoSection` = $section AND `infoYear` = $olsyear AND `infoStdid` = $stdid");
										
									?>
										<tr>
					  					<td><?= $key+1 ?></td>
					  					<td><?= $value->stdName ?> <?php echo sizeof($faildstd) > 0 ? '('.$faildstd[0]->infoRoll.')' : ''; ?> </td>
					  					<td><?= $value->infoRoll ?></td>
					  					<td><?= $value->className ?></td>
					  					<td><?= $value->sectionName ?></td>
					  					<td><?= $value->groupName ?></td>
					  					<td><?= $value->spPosition ?> (<?= $value->spFaild ?> Sub Fail)</td>
					  					<td>
					  						<input type="hidden" name="position[<?= $value->studentid ?>]" value="<?= $value->spPosition ?>">
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

<p id="theSiteURL" class="hidden"><?= get_template_directory_uri() ?></p>
<script type="text/javascript">
	(function($) {
    var $siteUrl = $('#theSiteURL').text();

		$('#proClass').change(function() {
			$.ajax({
	      url: $siteUrl+"/inc/ajaxAction.php",
	      method: "POST",
	      data: { class : $(this).val(), type : 'getSection' },
	      dataType: "html"
	    }).done(function( msg ) {
	      $( "#proSec" ).html( msg );
	      $( "#proSec" ).prop('disabled', false);
	    });
		});


		$('#resultClass').change(function() {
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