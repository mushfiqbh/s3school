<?php global $s3sRedux; ?>
<div class="panel panel-info">

  <div class="panel-heading"><h3>Result Edit</h3></div>

  <div class="panel-body">
		<div class="panel-group">
			<?php 
				// Check if this is a single result edit (needs 'res' parameter)
				if (isset($_GET['view']) && $_GET['view'] == 'edit') {
					// Single result edit mode
					if (!isset($_GET['res']) || empty($_GET['res'])) {
						echo '<div class="alert alert-danger">No result ID provided. Please select a result to edit.</div>';
						echo '</div></div></div>';
						return;
					}
					
					$res = intval($_GET['res']); // Sanitize the input
				} elseif (isset($_GET['view']) && $_GET['view'] == 'resultedit') {
					// All results edit mode - show search form or list
					echo '<div class="alert alert-info">Please search for a student result to edit.</div>';
					echo '<p>Use the "All Result" page to find and edit specific results.</p>';
					echo '</div></div></div>';
					return;
				} else {
					// Unknown mode
					echo '<div class="alert alert-warning">Invalid access mode.</div>';
					echo '</div></div></div>';
					return;
				}
			?>
			<form action="" method="POST">

				<?php 
					$results = $wpdb->get_results( "SELECT stdName,infoRoll,resultId,subjectName,resMCQ,resCQ,resPrec,resCa,subCQ,subMCQ,subPect,subCa FROM `ct_result`
						LEFT JOIN ct_student ON ct_student.studentid = ct_result.resStudentId
						LEFT JOIN ct_studentinfo ON ct_studentinfo.infoStdid = ct_result.resStudentId
						LEFT JOIN ct_subject ON ct_result.resSubject = ct_subject.subjectid
						WHERE resultId = $res LIMIT 1" );
					
					// Check if results found
					if (empty($results)) {
						echo '<div class="alert alert-warning">No result found with this ID.</div>';
						echo '</form></div></div></div>';
						return;
					}
				?>

				<table class="table table-bordered table-striped">
					<tbody>
						<tr>
							<th>Student Name</th>
							<th>Roll</th>
							<th>Subject</th>
							<th><?= $s3sRedux['cqtitle'] ?> <?= "(".$results[0]->subCQ.")" ?></th>
							<th><?= $s3sRedux['mcqtitle'] ?> <?= "(".$results[0]->subMCQ.")" ?></th>
							<th><?= $s3sRedux['prctitle'] ?> <?= "(".$results[0]->subPect.")" ?></th>
							<th><?= $s3sRedux['catitle'] ?> <?= "(".$results[0]->subCa.")" ?></th>
						</tr>
						<tr>
							<?php

								foreach ($results as $result) {
									?>
										<input type="hidden" name="id" value="<?= $result->resultId ?>">
										<input type="hidden" name="subCQ" value="<?= $result->subCQ ?>">
										<input type="hidden" name="subMCQ" value="<?= $result->subMCQ ?>">
										<input type="hidden" name="subPect" value="<?= $result->subPect ?>">
										<td><?= $result->stdName ?></td>
										<td><?= $result->infoRoll ?></td>
										<td><?= $result->subjectName ?></td>
										<td><input class="form-control" type="text" name="CQ" value="<?= $result->resCQ ?>"></td>
										<td><input class="form-control" type="text" name="MCQ" value="<?= $result->resMCQ ?>" <?= ($result->subMCQ == 0) ? 'readonly' : ''; ?>></td>
										<td><input class="form-control" type="text" name="P" value="<?= $result->resPrec ?>" <?= ($result->subPect == 0) ? 'readonly' : ''; ?>></td>
										<td><input class="form-control" type="text" name="ca" value="<?= $result->resCa ?>" <?= ($result->resCa == 0) ? 'readonly' : ''; ?>></td>
									<?php  
								} ?>
						</tr>
					</tbody>
				</table>
				<div class="text-right">
					<input class="btn btn-success" type="submit" name="updateRes" value="Update">
				</div>
			</form>
		</div>
  </div>
</div>