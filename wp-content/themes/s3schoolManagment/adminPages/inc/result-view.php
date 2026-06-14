<?php 
global $wpdb,$s3sRedux; 
$convertPercent = 70;
?>

<?php if ( ! is_admin() ) { get_header(); ?>
<div class="b-layer-main">

	<div class="">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
<?php } ?>

	<p id="theSiteURL" class="hidden"><?= get_template_directory_uri() ?></p>
	<div class="panel panel-info">
		<div class="panel-heading">
			<h3>
				Result View<br>
				<small>Check students Result</small>
			</h3>
		</div>
		<div class="panel-body">
			<form action="" method="GET" class="form-inline">

				<div class="form-group">
					<input type="hidden" name="page" value="tabulation_sheet2">
					<input type="hidden" name="view" value="resultview">
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

				<div class="form-group">
					<label>Year</label>
					<select id='resultYear' class="form-control" name="syear" required disabled>
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
					<input class="form-control btn btn btn-primary" type="submit" name="" value="Go">
				</div>
			</form>
		</div>

	</div>

	<!-- Result Entries View -->
	<?php if(isset($_GET['syear']) && isset($_GET['class']) && isset($_GET['exam'])): 
		$year = $_GET['syear']; 
		$class = $_GET['class'];
		$sec = isset($_GET['sec']) ? $_GET['sec'] : '';
		$grou = isset($_GET['grou']) ? $_GET['grou'] : '';
		$exam = isset($_GET['exam']) ? $_GET['exam'] : '';
		
		// Get class info
		$classInfo = $wpdb->get_row($wpdb->prepare("SELECT className FROM ct_class WHERE classid = %d", $class));
		$examInfo = $wpdb->get_row($wpdb->prepare("SELECT examName FROM ct_exam WHERE examid = %d", $exam));
		$sectionInfo = $sec ? $wpdb->get_row($wpdb->prepare("SELECT sectionName FROM ct_section WHERE sectionid = %d", $sec)) : null;
		$groupInfo = $grou ? $wpdb->get_row($wpdb->prepare("SELECT groupName FROM ct_group WHERE groupId = %d", $grou)) : null;
		
		// Get subjects for this exam
		$subjects = json_decode($wpdb->get_var($wpdb->prepare("SELECT examSubjects FROM ct_exam WHERE examid = %d AND examClass = %d", $exam, $class)), true);
		
		if($subjects && is_array($subjects)):
			$subjectIds = implode(',', array_map('intval', $subjects));
			
			// Get all sections for this class
			$sections = $wpdb->get_results("SELECT sectionid, sectionName FROM ct_section WHERE sectionid IN (SELECT DISTINCT infoSection FROM ct_studentinfo WHERE infoClass = $class AND infoYear = '$year')");
			
			// Get subject details and count entries grouped by section
			$subjectStats = [];
			foreach($sections as $section) {
				$secId = $section->sectionid;
				$secName = $section->sectionName;
				
				$stats = $wpdb->get_results("
				SELECT 
					s.subjectid,
					s.subjectName,
					s.subCQ,
					s.subMCQ,
					s.subPect,
					s.subCa,
					s.subOptinal,
					s.sub4th,
					s.religionId,
					'$secId' as resSec,
					'$secName' as sectionName,
				COUNT(DISTINCT r.resStudentId) as total_students,
				COUNT(DISTINCT CASE WHEN r.resCQ IS NOT NULL AND r.resCQ != '' AND r.resCQ != 'a' AND r.resCQ != 'A' THEN r.resStudentId END) as cq_count,
				COUNT(DISTINCT CASE WHEN r.resMCQ IS NOT NULL AND r.resMCQ != '' AND r.resMCQ != 'a' AND r.resMCQ != 'A' THEN r.resStudentId END) as mcq_count,
				COUNT(DISTINCT CASE WHEN r.resPrec IS NOT NULL AND r.resPrec != '' AND r.resPrec != 'a' AND r.resPrec != 'A' THEN r.resStudentId END) as pr_count,
				COUNT(DISTINCT CASE WHEN r.resCa IS NOT NULL AND r.resCa != '' AND r.resCa != 'a' AND r.resCa != 'A' THEN r.resStudentId END) as ca_count,
				COUNT(DISTINCT CASE WHEN r.resCQ = 'a' OR r.resCQ = 'A' THEN r.resStudentId END) as cq_absent,
				COUNT(DISTINCT CASE WHEN r.resMCQ = 'a' OR r.resMCQ = 'A' THEN r.resStudentId END) as mcq_absent,
				COUNT(DISTINCT CASE WHEN r.resPrec = 'a' OR r.resPrec = 'A' THEN r.resStudentId END) as pr_absent,
				COUNT(DISTINCT CASE WHEN r.resCa = 'a' OR r.resCa = 'A' THEN r.resStudentId END) as ca_absent,
				0 as not_added_count
					FROM ct_subject s
					LEFT JOIN ct_result r ON s.subjectid = r.resSubject 
						AND r.resClass = $class 
						AND r.resExam = $exam 
						AND r.resultYear = '$year'
						AND r.resSec = $secId
						" . ($grou ? "AND r.resgroup = $grou" : "") . "
			WHERE s.subjectid IN ($subjectIds)
				GROUP BY s.subjectid, s.subjectName, s.subCQ, s.subMCQ, s.subPect, s.subCa, s.subOptinal, s.sub4th, s.religionId
				ORDER BY s.subjectName ASC
					");
					
					// Calculate not_added_count separately for each subject
					foreach($stats as $stat) {
						// Build the condition based on subject type
						$subjectCondition = "";
						if($stat->subOptinal == 0 && $stat->sub4th == 0) {
							// Regular subject - all students
							$subjectCondition = "1=1";
						} elseif($stat->subOptinal == 1 && $stat->sub4th == 1) {
							// Both optional and 4th subject
							$subjectCondition = "(si.infoOptionals LIKE '%\"" . $stat->subjectid . "\"%' OR si.info4thSub = " . $stat->subjectid . ")";
						} elseif($stat->subOptinal == 1) {
							// Only optional
							$subjectCondition = "si.infoOptionals LIKE '%\"" . $stat->subjectid . "\"%'";
						} elseif($stat->sub4th == 1) {
							// Only 4th subject
							$subjectCondition = "si.info4thSub = " . $stat->subjectid;
						}
						
					// Build religion condition based on subject's religionId
					$religionCondition = "";
					if(!empty($stat->religionId)) {
						// Map religionId to stdReligion values
						$religionMap = array(
							1 => 'Muslim',
							2 => 'Hinduism',
							3 => 'Buddist',
							4 => 'Christian',
							5 => 'Others'
						);
						if(isset($religionMap[$stat->religionId])) {
							$religionCondition = " AND st.stdReligion = '" . $religionMap[$stat->religionId] . "'";
						}
					}
					
					$notAddedQuery = "
						SELECT COUNT(DISTINCT st.studentid) 
						FROM ct_student st 
						LEFT JOIN ct_studentinfo si ON st.studentid = si.infoStdid 
							AND si.infoClass = $class 
							AND si.infoYear = '$year' 
							AND si.infoSection = $secId
							" . ($grou ? " AND si.infoGroup = $grou" : "") . "
						WHERE st.studentid NOT IN (
							SELECT resStudentId 
							FROM ct_result 
							WHERE resClass = $class 
								AND resultYear = '$year' 
								AND resSubject = " . $stat->subjectid . " 
								AND resExam = $exam 
								AND resSec = $secId
					)
					AND st.stdCurntYear = '$year' 
					AND st.stdCurrentClass = $class
					AND si.infoStdid IS NOT NULL
					" . $religionCondition . "
					AND " . $subjectCondition;						$stat->not_added_count = (int)$wpdb->get_var($notAddedQuery);
					}
				
				$subjectStats = array_merge($subjectStats, $stats);
			}
	?>
	
	<div class="panel panel-success">
		<div class="panel-body">
			<table style="width: 100%; margin-bottom: 15px;">
				<tr style="background: #4472C4; print-color-adjust: exact; -webkit-print-color-adjust: exact; color: #fff">
					<td style="padding: 5px;"><b>Class:</b></td>
					<td style="padding: 5px;"><?= $classInfo->className ?></td>
					<td style="padding: 5px;"><b>Exam:</b></td>
					<td style="padding: 5px;"><?= $examInfo->examName ?></td>
				</tr>
				<tr style="background: #D9E2F3; print-color-adjust: exact; -webkit-print-color-adjust: exact;">
					<td style="padding: 5px;"><b>Year/Session:</b></td>
					<td style="padding: 5px;"><?= $year ?></td>
					<td style="padding: 5px;"><b>Group:</b></td>
					<td style="padding: 5px;"><?= $groupInfo ? $groupInfo->groupName : 'All' ?></td>
				</tr>
			</table>
			
			<?php 
			// Group stats by section
			$statsBySection = [];
			foreach($subjectStats as $stat) {
				$sectionKey = $stat->sectionName ?: 'No Section';
				if(!isset($statsBySection[$sectionKey])) {
					$statsBySection[$sectionKey] = [];
				}
				$statsBySection[$sectionKey][] = $stat;
			}
			
			// Display separate table for each section
			foreach($statsBySection as $sectionName => $sectionStats):
			?>
			
			<h4 style="margin-top: 20px; padding: 8px; background: #4472C4; color: #fff; border-radius: 4px;">
				Section: <?= $sectionName ?>
			</h4>
			
			<div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
			<table class="table table-bordered table-striped">
				<thead style="background: #5cb85c; color: #fff;">
					<tr>
						<th style="text-align: center;">#</th>
						<th>Subject Name</th>
						<th style="text-align: center;">Total Entries</th>
					<th style="text-align: center;">CQ Entries</th>
					<th style="text-align: center;">MCQ Entries</th>
					<th style="text-align: center;">PR Entries</th>
					<th style="text-align: center;">CA Entries</th>
					<th style="text-align: center;">Not Added</th>
						<th style="text-align: center;">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php 
					$sl = 1;
					foreach($sectionStats as $stat): 
					// Check only configured fields (where subject field != 0)
					$is_complete = true;
					
					if($stat->subCQ != 0) {
						$cq_complete = ($stat->total_students == ($stat->cq_absent + $stat->cq_count));
						$is_complete = $is_complete && $cq_complete;
					}
					
					if($stat->subMCQ != 0) {
						$mcq_complete = ($stat->total_students == ($stat->mcq_absent + $stat->mcq_count));
						$is_complete = $is_complete && $mcq_complete;
					}
					
					if($stat->subPect != 0) {
						$pr_complete = ($stat->total_students == ($stat->pr_absent + $stat->pr_count));
						$is_complete = $is_complete && $pr_complete;
					}
					
					if($stat->subCa != 0) {
						$ca_complete = ($stat->total_students == ($stat->ca_absent + $stat->ca_count));
						$is_complete = $is_complete && $ca_complete;
					}						$row_style = !$is_complete ? 'background-color: #ffe6e6;' : '';
					?>
					<tr style="<?= $row_style ?>">
					<td style="text-align: center;"><?= $sl++ ?></td>
					<td><strong><?= $stat->subjectName ?></strong></td>
					<td style="text-align: center;">
						<?= $stat->total_students ?>
					</td>
				<td style="text-align: center;">
					<?php if($stat->subCQ != 0): ?>
						<?= $stat->cq_count ?>
						<?php if($stat->cq_absent > 0): ?>
							<span style="color: #d9534f;"> + <?= $stat->cq_absent ?>A</span>
						<?php endif; ?>
					<?php endif; ?>
				</td>
				<td style="text-align: center;">
					<?php if($stat->subMCQ != 0): ?>
						<?= $stat->mcq_count ?>
						<?php if($stat->mcq_absent > 0): ?>
							<span style="color: #d9534f;"> + <?= $stat->mcq_absent ?>A</span>
						<?php endif; ?>
					<?php endif; ?>
				</td>
				<td style="text-align: center;">
					<?php if($stat->subPect != 0): ?>
						<?= $stat->pr_count ?>
						<?php if($stat->pr_absent > 0): ?>
							<span style="color: #d9534f;"> + <?= $stat->pr_absent ?>A</span>
						<?php endif; ?>
					<?php endif; ?>
				</td>
				<td style="text-align: center;">
					<?php if($stat->subCa != 0): ?>
						<?= $stat->ca_count ?>
						<?php if($stat->ca_absent > 0): ?>
							<span style="color: #d9534f;"> + <?= $stat->ca_absent ?>A</span>
						<?php endif; ?>
					<?php endif; ?>
				</td>
				<td style="text-align: center; <?= $stat->not_added_count > 0 ? 'background-color: #ffe6cc; font-weight: bold;' : '' ?>">
							<?= $stat->not_added_count ?>
						</td>
						<td style="text-align: center;">
						<?php 
						// Build URL parameters
						$urlParams = "page=result&class={$class}&sec={$stat->resSec}&grou={$grou}&syear={$year}&exam={$exam}&subject={$stat->subjectid}";
						$urlParams .= "&religion=&gender=";
						
						// Add result entry link if there are students without results
						if($stat->not_added_count > 0):
							?>
								<a href="?<?= $urlParams ?>" target="_blank" title="Add Results" style="margin-right: 8px;">
									<span style="font-size: 18px; color: #5cb85c;">➕</span>
								</a>
							<?php endif; ?>
							
							<?php 
							// Add result edit link if entries are incomplete (red rows)
							if(!$is_complete): 
							?>
								<a href="?page=result&view=resultedit&<?= $urlParams ?>" target="_blank" title="Edit Results">
									<span style="font-size: 18px; color: #f0ad4e;">✏️</span>
								</a>
							<?php endif; ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			</div>
			
			<?php endforeach; ?>
		</div>
	</div>
	
	<?php 
		endif;
	endif; 
	?>

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