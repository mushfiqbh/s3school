<?php global $s3sRedux; ?>
<div class="panel panel-info">

  <div class="panel-heading"><h3>Result Edit</h3></div>

  <div class="panel-body">
		<div class="panel-group">
			<form action="" method="POST">

				<?php 
					$res = $_GET['res'];
					$results = $wpdb->get_results( "SELECT stdName,infoRoll,resultId,subjectName,resMCQ,resCQ,resPrec,resCa,subCQ,subMCQ,subPect,subCa FROM `ct_result`
						LEFT JOIN ct_student ON ct_student.studentid = ct_result.resStudentId
						LEFT JOIN ct_studentinfo ON ct_studentinfo.infoStdid = ct_result.resStudentId
						LEFT JOIN ct_subject ON ct_result.resSubject = ct_subject.subjectid
						WHERE resultId = $res LIMIT 1" );
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