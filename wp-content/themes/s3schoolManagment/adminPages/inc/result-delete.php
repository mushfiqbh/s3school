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
<div class="panel panel-info">
	<div class="panel-heading"><h3>Delete Result</h3></div>
	<div class="panel-body">
		<form action="" method="GET" class="form-inline">

			<div class="form-group">
				<input type="hidden" name="page" value="result">
				<input type="hidden" name="view" value="delete">
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
				<label>Year</label>
				<select id='resultYear' class="form-control" name="syear" required disabled>
					<option disabled selected>Select Class First</option>
				</select>
			</div>


			<div class="form-group">
				<label>Subject</label>
				<select id='resultSubject' class="form-control" name="subject" disabled>
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
						
					$qrey .= " WHERE stdCurntYear = '$year' AND stdCurrentClass = $class";

					if($sec != ''){ $qrey .= " AND infoSection = $sec"; }

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