<?php
	global $s3sRedux;

$current_user = wp_get_current_user();
$user = $current_user;


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

<style>
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
		<h3>Edit Result</h3>
	</div>
	<div class="panel-body">
		<form action="" method="GET" class="compact-filter-form">
			<input type="hidden" name="page" value="result">
			<input type="hidden" name="view" value="resultedit">

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
				<label>Year/Session</label>
				<select id='resultYear' class="form-control" name="syear" required disabled>
					<option disabled selected>Select Class First</option>
				</select>
			</div>

			<div class="filter-field">
				<label>Exam *</label>
				<select id="resultExam" class="form-control" name="exam" required disabled>
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


	<?php if(isset($_GET['class'])){
	?>

	<div class="panel panel-info">

	  <div class="panel-body">
			<div class="panel-group">
				<form action="" method="POST">

					<?php 
						
						$class		= $_GET['class'];
						$exam			= $_GET['exam'];
						$year			= $_GET['syear'];
						$sec			= isset($_GET['sec']) ? $_GET['sec'] : '';
						$subject  = $sub = $_GET['subject'];
						$grou 	= $_GET['grou'];

                        // Religion religionId mapping
                        $religionMap = array(
                            'Muslim'    => 1,
                            'Hinduism'  => 2,
                            'Buddist'   => 3,
                            'Christian' => 4
                        );
                    
                        $subject_info = $wpdb->get_row("SELECT religionId FROM ct_subject WHERE subjectid = $sub");
                        $subCode = $subject_info->religionId ?? null;
                        $religionFilter = '';
                        if (isset($_GET['religion']) && !empty($_GET['religion'])) {
                          $religion = $_GET['religion'];
                          $religionFilter = " AND stdReligion = '$religion'";
                        } else if ($subCode && in_array($subCode, array_values($religionMap))) {
                            $religion = array_search($subCode, $religionMap);
                            $religionFilter = " AND stdReligion = '$religion'";
                        }

						$query = "SELECT stdName,infoRoll,resultId,subjectName,assessment,resMCQ,resCQ,resPrec,resCa,subCQ,subMCQ,subPect,subCa FROM `ct_result`
							LEFT JOIN ct_student ON ct_student.studentid = ct_result.resStudentId
							LEFT JOIN ct_studentinfo ON ct_studentinfo.infoStdid = ct_result.resStudentId
							LEFT JOIN ct_subject ON ct_result.resSubject = ct_subject.subjectid
							WHERE resClass = $class AND resExam = $exam AND resultYear = '$year' AND resSubject = $subject AND status = 0" . $religionFilter;
						if ($sec != '' && $sec != 'all') { $query .= " AND infoSection = $sec"; }
						if ($grou != "") {
													$query .= " AND infoGroup = $grou";
												}
						$query .= " Group By resStudentId ORDER BY infoRoll ASC";

						$results = $wpdb->get_results($query);

						$canAdd = true;
						if($canAdd){
							if($results){
								?>

								<!--<input type="hidden" name="subCQ" value="<?= $result->subCQ ?>">-->
								<!--<input type="hidden" name="subMCQ" value="<?= $result->subMCQ ?>">-->
								<!--<input type="hidden" name="subPect" value="<?= $result->subPect ?>">-->
								<!--<input type="hidden" name="subCa" value="<?= $result->subCa ?>">-->
								<table class="table table-bordered table-striped">
									<tbody>
										<tr>
											<th>Student Name</th>
											<th>Roll</th>
											<th>Subject</th>
											<th style="<?= ($results[0]->subCQ == 0) ? 'display: none;' : ''; ?>"><?= $results[0]->assessment == 1? 'Hand Writing' : $s3sRedux['cqtitle'] ?> 	<?= "(".$results[0]->subCQ.")" ?></th>
											<th style="<?= ($results[0]->subMCQ == 0) ? 'display: none;' : ''; ?>"><?= $results[0]->assessment == 1? 'Attendence' : $s3sRedux['mcqtitle'] ?>  	<?= "(".$results[0]->subMCQ.")" ?></th>
											<th style="<?= ($results[0]->subPect == 0) ? 'display: none;' : ''; ?>"><?= $results[0]->assessment == 1? 'Neat & Clean' : $s3sRedux['prctitle'] ?> 	<?= "(".$results[0]->subPect.")" ?></th>
											<th style="<?= ($results[0]->subCa == 0) ? 'display: none;' : ''; ?>"><?= $results[0]->assessment == 1? 'Uniform' : $s3sRedux['catitle'] ?> 		<?= "(".$results[0]->subCa.")" ?></th>
										</tr>
										<?php

											foreach ($results as $result) {
												?>
												<tr>
													<input type="hidden" name="id[<?= $result->resultId ?>]" value="<?= $result->resultId ?>">
													<td><?= $result->stdName ?></td>
													<td><?= $result->infoRoll ?></td>
													<td><?= $result->subjectName ?></td>
													<td style="<?= ($result->subCQ == 0) ? 'display: none;' : ''; ?>">
														<input class="resultInput form-control" type="text" data-max="<?= $result->subCQ ?>" name="CQ[<?= $result->resultId ?>]" value="<?= $result->resCQ ?>" style="<?= ($result->subCQ == 0) ? 'display: none;' : ''; ?>">
													</td>
													<td style="<?= ($result->subMCQ == 0) ? 'display: none;' : ''; ?>">
														<input class="resultInput form-control" type="text" data-max="<?= $result->subMCQ ?>" name="MCQ[<?= $result->resultId ?>]" value="<?= $result->resMCQ ?>" style="<?= ($result->subMCQ == 0) ? 'display: none;' : ''; ?> <?= ($result->subMCQ == 0) ? 'readonly' : ''; ?>">
													</td>
													<td style="<?= ($result->subPect == 0) ? 'display: none;' : ''; ?>">
														<input class="resultInput form-control" type="text" data-max="<?= $result->subPect ?>" name="P[<?= $result->resultId ?>]" value="<?= $result->resPrec ?>" style="<?= ($result->subPect == 0) ? 'display: none;' : ''; ?> <?= ($result->subPect == 0) ? 'readonly' : ''; ?>">
													</td>
													<td style="<?= ($result->subCa == 0) ? 'display: none;' : ''; ?>">
														<input class="resultInput form-control" type="text" data-max="<?= $result->subCa ?>" name="ca[<?= $result->resultId ?>]" value="<?= $result->resCa ?>" style="<?= ($result->subCa == 0) ? 'display: none;' : ''; ?> <?= ($result->subCa == 0) ? 'readonly' : ''; ?>">
													</td>
												</tr>
												<?php  
											} 
										?>
									</tbody>
								</table>
								<div class="text-right">
									<input class="btn btn-success resultSubmit" type="submit" name="updateAllResult" value="Update">
								</div>
								<?php
							}else{ ?>
								<h2 class="text-center">Student not Found, Maybe result published.</h2>
								<?php 
							}
						}else{
							echo "<h3 class='text-center text-danger'>You are not allowed to edit result for this subject.</h3>";
						}
					?>
				</form>
			</div>
	  </div>
	</div>

<?php
}

?>

<?php
// ==========================================
// HANDLE AJAX ACTIONS LOCALLY
// ==========================================
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
				'resCQ' 		=> $cq[$id],
				'resMCQ' 		=> $mcq[$id],
				'resPrec' 	=> $prc[$id],
				'resCa' 	=> $ca[$id],
				'resTotal' 	=> isnum($cq[$id])+isnum($mcq[$id])+isnum($prc[$id])+isnum($ca[$id])
			),
			array( 'resultId' => $id)
		);
		if ($update) { $response = $update;	}
	}
	if ($response) {
		$message = array('status' => 'success', 'message' => 'Successfully updated' );
	}else{
		$message = array('status' => 'faild', 'message' => 'Something wrong please try again' );
	}

} ?> 

<script type="text/javascript">
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