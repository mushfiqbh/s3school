<?php
/**
* Template Name: Admin Exam
*/
global $wpdb;


/*=================
	Add Exam
=================*/
if (isset($_POST['addExam'])) {

	if (isset($_POST['subjects'])) {
		$insert = $wpdb->insert(
			'ct_exam',
			array(
				'examName' 		=> $_POST['examName'],
				'examClass' 	=> $_POST['examClass'],
				'cgpaPercent' => isset($_POST['cgpaPercent']) ? $_POST['cgpaPercent'] : 0,
				'examClass2' 	=> isset($_POST['examClass2']) ? $_POST['examClass2'] : 0,
				'examSubjects'=> json_encode($_POST['subjects']),
				'examGroup'   => isset($_POST['forGroup']) ? $_POST['forGroup'] : 0,
				'examSirial' 		=> $_POST['examSirial'],
				'examNote' 		=> $_POST['examNote']
			)
		);
		$message = ms3message($insert, 'Added');
	}else{
		$message = ms3message($insert, 'Added');
	}
	
}


/*Update Exam*/
if (isset($_POST['updateExam'])) {
	$update = $wpdb->update(
		'ct_exam',
		array(
			'examName' 		=> $_POST['examName'],
			'examClass' 	=> $_POST['examClass'],
			'cgpaPercent' => isset($_POST['cgpaPercent']) ? $_POST['cgpaPercent'] : 0,
			'examClass2' 	=> isset($_POST['examClass2']) ? $_POST['examClass2'] : 0,
			'examSubjects' => json_encode($_POST['subjects']),
			'examGroup'   => isset($_POST['forGroup']) ? $_POST['forGroup'] : 0,
			'examSirial' 		=> $_POST['examSirial'],
			'examNote' 		=> $_POST['examNote']
		),
		array( 'examid' => $_POST['id'])
	);

	$message = ms3message($update, 'Updated');
}


/*Delete Exam*/
if (isset($_POST['deleteExam'])) {
	$delete = $wpdb->delete( 'ct_exam', array( 'examid' => $_POST['id'] ) );
	$delete2 = $wpdb->delete( 'ct_result', array( 'resExam' => $_POST['id'] ) );
	$delete3 = $wpdb->delete( 'ct_studentPoint', array( 'spExam' => $_POST['id'] ) );
	$message = ms3message($delete, 'Deleted');
}


/*edit Exam*/
$editid = 0;
if (isset($_POST['editExam'])) {
	$editid = $_POST['id'];
	$edit = $wpdb->get_results( "SELECT * FROM ct_exam WHERE examid = $editid" );
	$edit = $edit[0];
}

?>


<?php if ( ! is_admin() ) { get_header(); ?>
<div class="b-layer-main">

	<div class="">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
<?php } ?>

	<div class="b-blog-items-holder galleryHolder">
		<div class="container-fluid maxAdminpages" style="padding-left: 0">

			<!-- Show Status message -->
		  <?php if(isset($message)){ ms3showMessage($message); } ?>

			<h2>Exam Management</h2><br>

			<div class="row">
				<div class="col-md-6">
					<div class="panel panel-info">
					  <div class="panel-heading"><h3><?= isset($edit) ? 'Edit' : 'Add'; ?> Exam</h3></div>
					  <div class="panel-body">
					  	
					  	<form action="" method="POST">
					    	<div class="row">
					    		<input type="hidden" name="id" value="<?= $editid ?>">
					    		<div class="form-group col-md-6">
						    		<label>Exam Name</label>
						    		<input class="form-control" type="text" name="examName" value="<?= isset($edit) ? $edit->examName : ''; ?>" required>
						    	</div>
						    	<div class="form-group col-md-6">
						    		<label>Exam of Class</label>
						    		<select class="form-control examClass" name="examClass" required>
						    			<option disabled selected>Select a Class..</option>
						    			<?php
						    				$classes = $wpdb->get_results( "SELECT * FROM ct_class" );
												foreach ($classes as $class) {
													$selected = (isset($edit->examClass) && $edit->examClass == $class->classid) ? 'selected' : '';

													echo "<option value='".$class->classid."' $selected>".$class->className."</option>";
								    			
												}
						    			?>
						    		</select>
						    	</div>
								</div>

					    	<div class="row">
					    		<div class="form-group col-md-6">
						    		<label>Exam of Class 2</label>
						    		<select class="form-control examClass2" name="examClass2">
						    			<option value="0" selected>Only One Class </option>
						    			<?php
												foreach ($classes as $class) {
													$selected = (isset($edit->examClass2) && $edit->examClass2 == $class->classid) ? 'selected' : '';

													echo "<option value='".$class->classid."' $selected>".$class->className."</option>";
								    			
												}
						    			?>
						    		</select>
						    	</div>

						    	<div class="form-group col-md-6">
						    		<label>Group</label>
						    		<select class="form-control" name="forGroup">
							    		<option selected>Select a Class..</option>"
						    			<?php
							    			

						    				$groups = $wpdb->get_results( "SELECT * FROM ct_group" );
												foreach ($groups as $group) {
													if($edit->forClass == $group->groupId){
								    				echo "<option value='".$group->groupId."' selected>".$group->groupName."</option>";
								    			}else{
														echo "<option value='".$group->groupId."'>".$group->groupName."</option>";
								    			}
												}
						    			?>
						    		</select>
						    	</div>

						    	
						    	<div class="form-group col-md-6">
						    		<label>CGPA Percentage</label>
						    		<input class="form-control" type="number" name="cgpaPercent" value="<?= isset($edit->cgpaPercent) ? $edit->cgpaPercent : '' ?>">
						    	</div>
						    	<div class="form-group col-md-6">
						    		<label>Exam Sirial</label>
						    		<input class="form-control" type="number" name="examSirial" value="<?= isset($edit->examSirial) ? $edit->examSirial : '' ?>">
						    	</div>
						    	<div class="col-md-12 ">
						    		<table class="table table-bordered">
											<tr>
												<th>Subjects</th>
												<td class="text-right">
													<label class="labelRadio"><input type="checkbox" id="selectAll"> Select All</label>
												</td>
											</tr>
											<tr>
												<td colspan="2" class="allSubjects">
													<?php if(isset($edit)){ 
														$subjects = $wpdb->get_results( "SELECT subjectid, subjectName FROM ct_subject WHERE subjectClass IN (".$edit->examClass.",".$edit->examClass2.")" );
														$subjs = json_decode( $edit->examSubjects );
														
													?>
													
														<ul class="list-unstyled list-inline">
															<?php
															foreach ($subjects as $key => $subject) {
																$checkd = '';
																if(is_array( $subjs))
																	if (in_array($subject->subjectid, $subjs)){ $checkd = 'checked'; }
																?>
																<li style="width: 49%">
																	<label class="labelRadio">
																		<input type="checkbox" name="subjects[]" value="<?= $subject->subjectid ?>" <?= $checkd ?>>
																		<?= $subject->subjectName ?>
																	</label>
																</li>
																<?php
															}
															?>
														</ul>
													<?php }else{ ?>
														Select a Class first
													<?php } ?>
												</td>
											</tr>
										</table>
						    	</div>
					    	</div>

					    	<div class="form-group">
					    		<label>Note</label>
					    		<textarea class="form-control" name="examNote"><?= isset($edit) ? $edit->examNote : ''; ?></textarea>
					    	</div>

					    	<div class="form-group text-right">
					    		<button class="btn btn-primary" type="submit" name="<?= isset($edit) ? 'updateExam' : 'addExam'; ?>"><?= isset($edit) ? 'Update' : 'Add'; ?> Exam</button>
					    	</div>

					    </form>
						  
					  </div>
					</div>
				</div>

				<div class="col-md-6">
					<div class="panel panel-info">
					  <div class="panel-heading"><h3>All Exam</h3></div>
					  <div class="panel-body">
							<div class="panel-group" id="accordion">

								<?php
									$exams = $wpdb->get_results( "SELECT examid,examName,examNote,className,groupName,cgpaPercent,examSirial FROM ct_exam LEFT JOIN ct_class ON ct_exam.examClass = ct_class.classid  LEFT JOIN ct_group ON ct_exam.examGroup = ct_group.groupId" );

									foreach ($exams as $exam) {
										?>
										<div class="panel panel-default">
									    <div class="panel-heading">
									      <h4 class="panel-title">
									        <a data-toggle="collapse" data-parent="#accordion" href="#collapse<?= $exam->examid ?>">
									        <?= $exam->examName ?> (<?= $exam->className ?>)</a>
									        <form class="pull-right actionForm" method="POST" action="">
									        	<input type="hidden" name="id" value="<?= $exam->examid ?>">
									        	<button type="submit" name="editExam" class="btn-link">
									        		<span class="dashicons dashicons-welcome-write-blog"></span></span>
									        	</button>
									        	<button type="button" class="btn-link btnDelete" data-id='<?= $exam->examid ?>'>
									        		<span class="dashicons dashicons-trash"></span>
									        	</button>
									        </form>

									      </h4>
									    </div>

									    <div id="collapse<?= $exam->examid ?>" class="panel-collapse collapse">
									      <div class="panel-body">
													<table class="table table-bordered">
														<tr>
															<td>Class: <?= $exam->className ?></td>
															<td>Group: <?= $exam->groupName ?></td>
														</tr>
														<tr>
															<td>CGPA Percentage: <?= $exam->cgpaPercent ?>%</td>
															<td>Exam Sirial: <?= $exam->examSirial ?></td>
														</tr>
														<tr>
															<td>Exam Note</td>
															<td><?= $exam->examNote ?></td>
														</tr>
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
				</div>
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

<div id="deleteModal" class="modal fade" role="dialog">
  <div class="modal-dialog modal-sm">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Delete Data</h4>
      </div>
      <div class="modal-body text-center">
        <p class="text-danger">You can't recover the data after delete.</p>
        <h4 class="text-danger">All Results along with this exam will be deleted.</h4>
      </div>
      <div class="modal-footer">
	      <form action="" method="POST">
	      	<input type="hidden" name="id" class="id">
        	<button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
        	<button type="submit" class="btn btn-danger" name="deleteExam">Delete</button>
	      </form>
      </div>
    </div>

  </div>
</div>

<script type="text/javascript">
	(function($) {
		jQuery(document).ready(function($) {
			$(".examClass, .examClass2").change(function(event) {
				var $examClass = $('.examClass').val();
				var $examClass2 = $('.examClass2').val();
				var $siteUrl = $('#theSiteURL').text();

				$examClass = ($examClass == "" || $examClass == null) ? 0 : $examClass;
				$examClass2 = ($examClass2 == "" || $examClass2 == null) ? 0 : $examClass2;

				$.ajax({
	        url: '<?= get_template_directory_uri().'/inc/ajaxAction.php' ?>',
	        method: "POST",
	        data: { class1 : $examClass, class2 : $examClass2, type : 'getSubjects' },
	        dataType: "html"
	      }).done(function( data ) {
	        $( ".allSubjects" ).html( data );
	      });
				
			});

			$('#selectAll').change(function() {
				if(this.checked) {
	      	$('.allSubjects').find('input').attr('checked', 'checked');
	    	}else{
	      	$('.allSubjects').find('input').removeAttr('checked');
	    	}
			});

			/*Delete*/
			$('.btnDelete').click(function(event) {
				$('#deleteModal').find('.id').val($(this).data('id'));
				$('#deleteModal').modal('show');
			});
		});
	})( jQuery );
</script>