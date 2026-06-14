<?php
	global $wpdb; global $s3sRedux;

	if (isset($_POST['delres'])) {
		$stdnt	= $_POST['stdnt'];
		$class	= $_POST['class'];
		$exam		= $_POST['exam'];
		$year		= $_POST['syear'];
		$roll		= $_POST['roll'];
		$wpdb->get_results("DELETE FROM `ct_result` WHERE resultYear = '$year' AND resClass = $class AND resExam = $exam AND resStudentId = $stdnt AND resStdRoll = $roll");
	}

	if (isset($_POST['deleteAllResult'])) {
		$stdnt	= $_POST['deleteAllResult'];
		$wpdb->get_results("DELETE FROM `ct_result` WHERE resStudentId = $stdnt");
		$wpdb->get_results("DELETE FROM `ct_studentPoint` WHERE spStdID = $stdnt");
	}
?>
	
<form action="" method="GET" class="form-inline pull-right">
	<input type="hidden" name="page" value="result">
	<input type="hidden" name="view" value="allresult">
	<div class="form-group">
		<select name="filter" class="form-control">
			<option disabled selected>Select a year...</option>
			<?php
				$groupsBy1 = $wpdb->get_results( "SELECT resultYear FROM ct_result GROUP BY resultYear" );
				foreach ($groupsBy1 as $group) {
					echo "<option>".$group->resultYear."</option>";
				}
			?>
		</select>
	</div>

	<div class="form-group">
		<input type="submit" value="Filter" class="form-control btn-info">
	</div>
</form>

<div class="panel panel-info">

	<?php 
		if(isset($_GET['filter'])){
			$curYear = $_GET['filter'];
		}else{
			$curYear = date('Y')-1 . "-" . date('Y');
		}
	?>

  <div class="panel-heading"><h3>All Result (<?= $curYear ?>)</h3></div>

  <div class="panel-body">
		<div class="panel-group">
			<?php

				$groupByClass = $wpdb->get_results( "SELECT resultId,resultYear,className,resClass FROM ct_result
				LEFT JOIN ct_class ON ct_result.resClass = ct_class.classid
				WHERE resultYear = '$curYear' GROUP BY className" );

				foreach ($groupByClass as $group) {
					?>
						<div class="panel panel-default">
					    <div class="panel-heading">
					      <h4 class="panel-title">
					        <a data-toggle="collapse" href="#collapse<?= $group->resultId; ?>"><?= $group->className; ?> </a>
					      </h4>
					    </div>
					    <div id="collapse<?= $group->resultId; ?>" class="panel-collapse collapse">

					      <div class="panel-body">

					      	<?php

					      		$resClass = $group->resClass;

										$groupByExam = $wpdb->get_results( "SELECT resultId,resultYear,resExam,examid,examName FROM ct_result
										LEFT JOIN ct_class ON ct_result.resClass = ct_class.classid
										LEFT JOIN ct_exam ON ct_result.resExam = ct_exam.examid
										WHERE resultYear = '$curYear' AND resClass = $resClass GROUP BY resExam" );

										foreach ($groupByExam as $group2) {
											$examid = $group2->examid;
											?>
											<div class="panel panel-default">
										    <div class="panel-heading">
										      <h4 class="panel-title">
										        <a data-toggle="collapse" href="#collapse<?= $group->resultId; ?><?= $group2->resExam; ?>"><?= $group2->examName; ?> </a>
										      </h4>

										    </div>

										    <div id="collapse<?= $group->resultId; ?><?= $group2->resExam; ?>" class="panel-collapse collapse">
										      <div class="panel-body">
										      	<table class="table table-bordered">
										      		<tr>
										      			<th>Name</th>
										      			<th>Roll</th>
										      			<th>Group</th>
										      			<th>Section</th>
										      			<th>Action</th>
										      		</tr>
										      		<?php

										      		//resStdRoll
										      		if($resClass == 41){
										      		    $students = $wpdb->get_results( "SELECT resStudentId,stdName,infoRoll,groupName,sectionName,sectionid FROM ct_result
										      				LEFT JOIN ct_studentinfo ON ct_result.resStudentId = ct_studentinfo.infoStdid AND ct_studentinfo.infoClass = $resClass AND ct_studentinfo.infoYear = '$curYear'
										      				LEFT JOIN ct_student ON ct_result.resStudentId = ct_student.studentid
										      				LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId  
										      				LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid 
																	WHERE resultYear = '$curYear' AND resExam = $examid GROUP BY resStudentId ORDER BY groupName DESC, sectionid,infoRoll ASC" );
										      		    
										      		}else{
										      		    $students = $wpdb->get_results( "SELECT resStudentId,stdName,infoRoll,groupName,sectionName,sectionid FROM ct_result
										      				LEFT JOIN ct_studentinfo ON ct_result.resStudentId = ct_studentinfo.infoStdid AND ct_studentinfo.infoClass = $resClass AND ct_studentinfo.infoYear = '$curYear'
										      				LEFT JOIN ct_student ON ct_result.resStudentId = ct_student.studentid
										      				LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId  
										      				LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid 
																	WHERE resultYear = '$curYear' AND resExam = $examid GROUP BY resStudentId ORDER BY sectionid,infoRoll" );
										      		}

										      			


										      			foreach ($students as $student) {
										      				$roll = $student->infoRoll;
										      				?>
										      					<tr>
													      			<td><?= $student->stdName ?></td>
													      			<td><?= $student->infoRoll ?></td>
													      			<td><?= $student->groupName ?></td>
													      			<td><?= $student->sectionName ?></td>
													      			<td class="actionTd">
													      				<a href="?page=result&view=result&stdnt=<?= $student->resStudentId ?>&class=<?= $resClass ?>&exam=<?= $examid ?>&syear=<?= $curYear ?>&roll=<?= $student->infoRoll ?>&sec=<?=  $student->sectionid ?>"><span class="dashicons dashicons-visibility text-info"></span></a>
													      				<form class="resultDelete" action="" method="POST" style="color: red">
													      					<span style="cursor: pointer;" class="dashicons dashicons-trash"></span>
													      					<button style="display: none;" name="deleteAllResult" value="<?= $student->resStudentId ?>">
													      						Yes
													      					</button>
													      				</form>
													      			</td>
													      		</tr>
										      				<?php
										      			}
										      		?>

										      	</table>
										      </div>
										    </div>
										  </div>
											<?php
										}
									?>
					      </div>
					    </div>
					  </div>
					<?php
				}
			?>
		</div>
  </div>
</div>
<script type="text/javascript">
	(function($) {
		$('.resultDelete .dashicons-trash').click(function(event) {
			$(this).hide(400);
			$(this).closest('.resultDelete').find('button').show(400);
		});
	})( jQuery );
</script>