<?php
/**
* Template Name: Admin SMS
*/

	global $wpdb;
	$apikey = '26e7458dc56ab2830fadba7bd2c1aa10e981518d309';

	if (isset($_POST['sendSms'])) {

		$_POST['students'] = array_filter($_POST['students']);
		$message = $_POST['message'];
		$totalCost = 0;
		$sucessed = 0;
		$failed = 0;

		foreach ($_POST['students'] as $key => $student) {
			if (strpos($student, '88') == false) {
				$student = "88".$student;
			}

			$post_values = array( 
				'api_key' => $apikey,
				'type' => 'unicode',  // unicode or text
				'senderid' => '8801552146120',
				'contacts' => $student,
				'msg' => $message,
				'method' => 'api'
			);



			$post_string = "";
			foreach( $post_values as $key => $value ){
				$post_string .= "$key=" . urlencode( $value ) . "&"; 
			}
		  $post_string = rtrim( $post_string, "& " );

			$request = curl_init("http://portal.smsinbd.com/smsapi");  
			curl_setopt($request, CURLOPT_HEADER, 0);  
			curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);  
			curl_setopt($request, CURLOPT_POSTFIELDS, $post_string); 
			curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE);  
			$post_response = curl_exec($request);  

		  curl_close ($request);  
			$array =  json_decode( preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $post_response), true );   

			if($array){ 
			 if($array['status'] == "SUCCESS"){
			 	$totalCost += $array['cost'];
			 	$sucessed++;
			 }else{
			 	$failed++;
			 }
			}
		}

		if($array){
			?>
			<br>
				<div class="text-center">
					<div class="alert alert-info" style="max-width: 400px; margin: auto;">
						<b>Sucessfuly Send:</b>  <?= $sucessed ?><br>
						<b>Failed Send:</b>  <?= $failed ?><br>
						<b>SMS Account Debited:</b>  <?= $totalCost ?>
					</div>
				</div>
			<?php
		}else{?>
			<br>
				<div class="text-center">
					<div class="alert alert-danger" style="max-width: 400px; margin: auto;">
						0 Message Send
					</div>
				</div>
			<?php
		}
	}



	/*Balance*/
	$post_values = array( 
		'api_key' => $apikey,
		'act' => 'balance',    
		'method' => 'api'
	);
	$post_url = 'http://portal.smsinbd.com/api/' ;  


	$post_string = "";
	foreach( $post_values as $key => $value )
		{ $post_string .= "$key=" . urlencode( $value ) . "&"; }
	   $post_string = rtrim( $post_string, "& " );
	   
	$request = curl_init($post_url); 
		curl_setopt($request, CURLOPT_HEADER, 0); 
		curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($request, CURLOPT_POSTFIELDS, $post_string);  
		curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE); 
		$post_response = curl_exec($request);  
	 
	 
	curl_close ($request);  
	 
	 
	$responses=array();  		
 	$array =  json_decode( preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $post_response), true );   

?>



<?php if ( ! is_admin() ) { get_header(); ?>

<div class="b-layer-main">


	<div class="">
		<div class="container">
			<div class="row">
				<div class="col-md-12">

<?php } ?>



<div class="container-fluid maxAdminpages smsPage" style="padding-left: 0">

	<div class="panel panel-info">
	  <div class="panel-heading">
	  	<h3>
	  		SMS<br><small>Send SMS to Students/Teachers (Charge 0.34 BDT for english and 0.68 BDT for Bangla Per SMS)</small>
	  		<span class="pull-right">Balance: <?= $array['balance'] ?></span>
	  	</h3>
	  </div>
	  <div class="panel-body">

			<div id="smsTab">
				<ul class="nav nav-tabs">
				  <li class="active"><a data-toggle="tab" href="#students">Students</a></li>
				  <li><a data-toggle="tab" href="#teachers">Teachers</a></li>
				</ul>


				<div class="tab-content">
				  <div id="students" class="tab-pane fade in active">
				  	<div class="panel-body">
					    <form class="form-inline" action="" method="GET">
								<input type="hidden" name="page" value="sms">


								<div class="form-group">
									<label>Class</label>
									<select id='resultClass' class="form-control" name="class" required>
										<option value="">Select Class</option>
										<?php
											$groupsBy = $wpdb->get_results( "SELECT classid,className FROM ct_class" );
											foreach ($groupsBy as $value) {
												$id = $value->classid;
												$name = $value->className;
												echo "<option value='$id'>$name</option>";
											}
										?>
									</select>

								</div>


								<div class="form-group">
									<label>Year</label>
									<select id='resultYear' class="form-control" name="syear" required>
										<option disabled selected>Select Class First</option>
									</select>
								</div>

								<div class="form-group">
									<label>Section</label>
									<select id="resultSection" class="form-control" name="section" required>
										<option disabled selected>Select Class First</option>
									</select>
								</div>

								<div class="form-group" id="idRoll">
									<label>Roll</label>
									<input class="form-control" type="text" name="roll" placeholder="Roll">
								</div>
								<div class="form-group">
									<input type="submit" class="btn btn-primary">
								</div>
							</form>
				  	</div>
				  </div>
				  <div id="teachers" class="tab-pane fade">
				  	<div class="panel-body">
					    <form action="" method="GET" class="form-inline">
					    	<input type="hidden" name="page" value="sms">
					    	<div class="form-group">
					    		<input class="form-control btn btn-primary" type="submit" name="allTeachers" value="All Teachers">
					    	</div>
					    	<div class="form-group">
					    		<input class="form-control" type="text" name="teacherName" placeholder="Teacher Name">
					    	</div>
					    	<div class="form-group">
					    		<input class="form-control btn btn-success" type="submit" name="oneTeacher" value="GO">
					    	</div>
					    </form>
					  </div>
				  </div>
				</div>
			</div>


			<!-- For Student -->

			<?php if(isset($_GET['syear'])){ ?>

			  <div class="col-md-12 printBG">
			  	<div>
					  <div class="printArea">
					  	<?php
					  		$year 		= $_GET['syear'];
					  		$class 		= $_GET['class'];
					  		$section 	= $_GET['section'];
					  		$roll 		= $_GET['roll'];

					  		$qury1 = "SELECT stdName,infoRoll,stdPhone FROM ct_student
					  			LEFT JOIN ct_studentinfo ON ct_studentinfo.infoStdid = ct_student.studentid
					  			WHERE infoYear = '$year' AND infoClass = $class AND infoSection = $section ";

					  		if ($roll != "" ) {
					  			$qury1 .= " AND infoRoll = $roll";
					  		}

					  		$qury1 .= " ORDER BY infoRoll ASC";
								$groupsBy = $wpdb->get_results( $qury1 );


					  		if($groupsBy){
					  			?>
					  				<form action="" method="POST">
					  					<div class="row">
						  					<div class="form-group col-md-8 col-md-offset-2">
						  						<textarea class="form-control smsCount" name="message" placeholder="Message" required></textarea>
						  						<p class="smsCountShow"><span class="ramain">0</span> Characters | <span class="left">750</span> Characters Left | <span class="totalSms">1</span> SMS</p>
						  					</div>
						  				</div>
					  					<table class="table table-bordered">
					  						<tr>
					  							<td colspan="9" class="text-right"><label class="labelRadio"><input type="checkbox" id="selectAll"> Select All</label></td>
					  						</tr>
					  						<tr>

					  							<th>Select</th>
					  							<th>Name</th>
					  							<th style="border-right: 2px solid #000">Roll</th>
					  							<?php if(sizeof($groupsBy) > 1){ ?>
						  							<th>Select</th>
						  							<th>Name</th>
						  							<th>Roll</th>
						  						<?php }if(sizeof($groupsBy) > 2){ ?>
						  							<th style="border-left: 2px solid #000">Select</th>
						  							<th>Name</th>
						  							<th>Roll</th>
						  						<?php } ?>
					  						</tr>
					  						
									  		<?php
									  			$num = 1;
													foreach ($groupsBy as $key => $value) {

														if($num == 1){ echo "<tr>"; }
														$style = '';
														if ($num == 1 || $num == 2) {
															$style = 'style="border-right: 2px solid #000;"';
														}
														?>
															<td class="text-center">
																<input class="stdSel" type="checkbox" name="students[]" value="<?= $value->stdPhone ?>">
															</td>
															<td><?= $value->stdName ?></td>
															<td <?= $style ?>><?= $value->infoRoll ?></td>
														<?php

														if($num == 3 ){ echo "</tr>"; $num = 0; }
														$num++;
													}
												?>
											</table>
											<div class="form-group">
												<input class="btn btn-success" type="submit" name="sendSms" value="Send SMS">
											</div>
					  				</form>
					  			<?php
								}else{
									echo "<h3 class='text-center'>No Student Found</h3>";
								}
					  	?>

					  </div>
			  	</div>
			  </div>

			<?php } ?>


			<!-- For Teachers -->
			<?php if(isset($_GET['allTeachers']) || isset($_GET['oneTeacher'])){ ?>
			  <div class="col-md-12 printBG">
			  	<div>
					  <div class="printArea">
					  	<?php

					  		if (!empty($_GET['teacherName'])) {
					  			$groupsBy = $wpdb->get_results( "SELECT teacherName,teacherMpo,teacherPhone FROM `ct_teacher` WHERE teacherName LIKE '%".$_GET['teacherName']."%'" );
					  		}else{
									$groupsBy = $wpdb->get_results( "SELECT teacherName,teacherMpo,teacherPhone FROM `ct_teacher`" );
					  		}

					  		if($groupsBy){
					  			?>
					  				<form action="" method="POST">
					  					<div class="row">
						  					<div class="form-group col-md-8 col-md-offset-2">
						  						<textarea class="form-control smsCount" name="message" placeholder="Message" required></textarea>
						  						<p class="smsCountShow"><span class="ramain">0</span> Characters | <span class="left">750</span> Characters Left | <span class="totalSms">1</span> SMS</p>
						  					</div>
						  				</div>
					  					<table class="table table-bordered">
					  						<tr>
					  							<td colspan="9" class="text-right">
					  								<label class="labelRadio"><input type="checkbox" id="selectAll"> Select All</label>
					  							</td>
					  						</tr>
					  						<tr>
					  							<th>Select</th>
					  							<th>Name</th>
					  							<th>MPO No</th>
					  							<?php if(sizeof($groupsBy) > 1){ ?>
						  							<th style="border-left: 2px solid #000">Select</th>
						  							<th>Name</th>
						  							<th>MPO No</th>
						  						<?php }if(sizeof($groupsBy) > 2){ ?>
						  							<th style="border-left: 2px solid #000">Select</th>
						  							<th>Name</th>
						  							<th>MPO No</th>
						  						<?php } ?>
					  						</tr>

									  		<?php
									  			$num = 1;
													foreach ($groupsBy as $key => $value) {

														if($num == 1){
															echo "<tr>";
														}
														$style = '';
														if ($num == 1 || $num == 2) {
															$style = 'style="border-right: 2px solid #000;"';
														}
														?>
															<td class="text-center">
																<input class="stdSel" type="checkbox" name="students[]" value="<?= $value->teacherPhone ?>">
															</td>
															<td><?= $value->teacherName ?></td>
															<td <?= $style ?>><?= $value->teacherMpo ?></td>
														<?php
														if($num == 3 ){ echo "</tr>"; $num = 0; }
														$num++;
													}
												?>
											</table>
											<div class="form-group">
												<input class="btn btn-success" type="submit" name="sendSms" value="Send SMS">
											</div>
					  				</form>
					  			<?php
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
	    var $url = "";

	    $.ajax({
	      url: $url,
	      method: "POST",
	      data: { class : $(this).val(), type : 'getYears' },
	      dataType: "html"
	    }).done(function( msg ) {
	      $( "#resultYear" ).html( msg );
	    });

	    $.ajax({
	      url: $url,
	      method: "POST",
	      data: { class : $(this).val(), type : 'getSection' },
	      dataType: "html"
	    }).done(function( msg ) {
	      $( "#resultSection" ).html( msg );
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