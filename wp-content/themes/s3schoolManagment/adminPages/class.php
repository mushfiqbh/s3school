<?php
/**
* Template Name: Admin Class
*/
global $wpdb;

/*=================
	Add Class
=================*/
if (isset($_POST['addClass'])) {

	$insert = $wpdb->insert(
		'ct_class',
		array(
			'className' 			=> $_POST['className'],
			'haveOptionalSub' => $_POST['haveOptionalSub'],
			'have4thSub' 			=> $_POST['have4thSub'],
			'havecgpa' 			=> $_POST['havecgpa'],
			'havegroup' 			=> $_POST['havegroup'],
			'combineMark' 		=> $_POST['combineMark'],
			'session' 				=> $_POST['session'],
			'classNote' 			=> $_POST['classNote'],
			'classOrder'			=> isset($_POST['classOrder']) ? intval($_POST['classOrder']) : 0
		)
	);

	$message = ms3message($insert, 'Added');
}

/*=================
	Update Class
=================*/
if (isset($_POST['updateClass'])) {

	$update = $wpdb->update(
		'ct_class',
		array(
			'className' 			=> $_POST['className'],
			'haveOptionalSub' => $_POST['haveOptionalSub'],
			'have4thSub' 			=> $_POST['have4thSub'],
			'havecgpa' 			=> $_POST['havecgpa'],
			'havegroup' 			=> $_POST['havegroup'],
			'combineMark' 		=> $_POST['combineMark'],
			'session' 				=> $_POST['session'],
			'classNote' 			=> $_POST['classNote'],
			'classOrder'			=> isset($_POST['classOrder']) ? intval($_POST['classOrder']) : 0
		),
		array( 'classid' => $_POST['id'])
	);

	$message = ms3message($update, 'Updated');
}


/*=================
	Delete Class
=================*/
if (isset($_POST['deleteClass'])) {
	$delete = $wpdb->delete( 'ct_class', array( 'classid' => $_POST['id'] ) );
	$message = ms3message($delete, 'Deleted');
}

/*=================
	Edit Class
=================*/
$editid = 0;
$dont4th = $optionNo = $combineMarkN = $sessionY = $havecgpay = $nogroup = 'checked';
$optionYes =  $have4th = $combineMarkY = $sessionS = $havecgpan = $havegroup = '';
if (isset($_POST['editClass'])) {
	$editid = $_POST['id'];
	$edit = $wpdb->get_results( "SELECT * FROM ct_class WHERE classid = $editid" );
	$edit = $edit[0];

	if($edit->haveOptionalSub == 1){ $optionYes = 'checked'; $optionNo = ''; }
	if($edit->have4thSub == 1){ $have4th = 'checked'; $dont4th = ''; }
	if($edit->havegroup == 1){ $havegroup = 'checked'; $nogroup = '';}
	if($edit->combineMark == 1){ $combineMarkY = 'checked'; $combineMarkN = ''; }
	if($edit->session != 'year'){ $sessionS = 'checked'; $sessionY = ''; }
	if($edit->havecgpa != '1'){ $havecgpan = 'checked'; $havecgpay = ''; }
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

	<!-- Show Status message -->
  <?php if(isset($message)){ ms3showMessage($message); } ?>

	<div class="row">
		<div class="col-md-6">
			<div class="panel panel-info">
			  <div class="panel-heading"><h3><?= isset($edit) ? 'Edit' : 'Add'; ?> Class<br><small>Class Informations</small></h3></div>
			  <div class="panel-body">
			    <form action="" method="POST">
			    	<div class="row">
			    		<input type="hidden" name="id" value="<?= $editid ?>">
			    		<div class="form-group col-md-8">
				    		<label>Class Name</label>
				    		<input class="form-control" type="text" name="className" value="<?= isset($edit) ? $edit->className : ''; ?>" required>
				    	</div>

				    	<div class="form-group col-md-4">
				    		<label>Display Order</label>
				    		<input class="form-control" type="number" name="classOrder" value="<?= isset($edit) ? $edit->classOrder : '0'; ?>" min="0" placeholder="0">
				    		<small class="text-muted">Lower numbers appear first</small>
				    	</div>

				    	<div class="col-md-6">
				    		<label>Have Optional Subject</label><br>
				    		<label class="labelRadio">
				    			<input type="radio" name="haveOptionalSub" value="0" <?= $optionNo ?>> No
				    		</label>
				    		<label class="labelRadio">
				    			<input type="radio" name="haveOptionalSub" value="1" <?= $optionYes ?>> Yes
				    		</label>
				    	</div>

				    	<div class="col-md-6">
				    		<label>Have 4th Subject</label><br>
				    		<label class="labelRadio">
				    			<input type="radio" name="have4thSub" value="0" <?= $dont4th ?>> No
				    		</label>
				    		<label class="labelRadio">
				    			<input type="radio" name="have4thSub" value="1" <?= $have4th ?>> Yes
				    		</label>
				    	</div>

				    	<div class="col-md-6">
				    		<label>Combine Mark</label><br>
				    		<label class="labelRadio">
				    			<input type="radio" name="combineMark" value="0" <?= $combineMarkN ?>> No
				    		</label>
				    		<label class="labelRadio">
				    			<input type="radio" name="combineMark" value="1" <?= $combineMarkY ?>> Yes
				    		</label>
				    	</div>

				    	<div class="col-md-6">
				    		<label>Session/Year</label><br>
				    		<label class="labelRadio">
				    			<input type="radio" name="session" value="year" <?= $sessionY ?>> Year
				    		</label>
				    		<label class="labelRadio">
				    			<input type="radio" name="session" value="session" <?= $sessionS ?>> Session
				    		</label>
				    	</div>
				    	<div class="form-group col-md-6">
				    		<label>CGPA System?</label><br>
				    		<label class="labelRadio">
				    			<input type="radio" name="havecgpa" value="1" <?= $havecgpay ?>> Yes
				    		</label>
				    		<label class="labelRadio">
				    			<input type="radio" name="havecgpa" value="0" <?= $havecgpan ?>> No
				    		</label>
				    	</div>
				    	<div class="form-group col-md-6">
				    		<label>Has Group?</label><br>
				    		<label class="labelRadio">
				    			<input type="radio" name="havegroup" value="1" <?= $havegroup ?>> Yes
				    		</label>
				    		<label class="labelRadio">
				    			<input type="radio" name="havegroup" value="0" <?= $nogroup ?>> No
				    		</label>
				    	</div>
			    	</div>

			    	<div class="form-group">
			    		<label>Note</label>
			    		<textarea class="form-control" name="classNote"><?= isset($edit) ? $edit->classNote : ''; ?></textarea>
			    	</div>

			    	<div class="form-group text-right">
			    		<button class="btn btn-primary" type="submit" name="<?= isset($edit) ? 'updateClass' : 'addClass'; ?>"><?= isset($edit) ? 'Update' : 'Add'; ?> Class</button>
			    	</div>

			    </form>
			  </div>
			</div>
		</div>
		<div class="col-md-6">
			<div class="panel panel-info">
			  <div class="panel-heading"><h3>All Class<br><small>All Class Informations</small> </h3></div>
			  <div class="panel-body">
					<div class="panel-group" id="accordion">

						<?php
							$classes = $wpdb->get_results( "SELECT * FROM ct_class ORDER BY classOrder ASC, className ASC" );
							foreach ($classes as $class) {

								?>
								<div class="panel panel-default">
							    <div class="panel-heading">
							      <h4 class="panel-title">
							        <a data-toggle="collapse" data-parent="#accordion" href="#collapse<?= $class->classid ?>">
							        <span class="badge" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; font-size: 11px; padding: 4px 8px; border-radius: 4px; margin-right: 8px; font-weight: 600;">
							        	#<?= isset($class->classOrder) ? $class->classOrder : '0'; ?>
							        </span>
							        <?= $class->className ?>
							        <?= !empty($class->groupName) ? " (".$class->groupName.")" : ''; ?> </a>
							        <form class="pull-right actionForm" method="POST" action="">
							        	<input type="hidden" name="id" value="<?= $class->classid ?>">
							        	<button type="submit" name="editClass" class="btn-link">
							        		<span class="dashicons dashicons-welcome-write-blog"></span></span>
							        	</button>
							        	<button type="button" class="btn-link btnDelete" data-id='<?= $class->classid ?>'>
							        		<span class="dashicons dashicons-trash"></span>
							        	</button>
							        </form>
							      </h4>
							    </div>
							    <div id="collapse<?= $class->classid ?>" class="panel-collapse collapse">
							      <div class="panel-body">
											<table class="table table-bordered">
												<tr>
													<td>
														<b>Optional Subject:</b>
														<?php echo ($class->haveOptionalSub) ? "Yes" : "No"; ?>
													</td>
													<td>
														<b>4th Subject:</b>
														<?php echo ($class->have4thSub) ? "Yes" : "No"; ?>
													</td>
												</tr>
												<tr>
													<td>
														<b>Display Order:</b>
														<?= isset($class->classOrder) ? $class->classOrder : '0'; ?>
													</td>
													<td>
														<b>Has Group:</b>
														<?php echo ($class->havegroup) ? "Yes" : "No"; ?>
													</td>
												</tr>
												<tr>
													<td colspan="2"><b>Note:</b> <?= $class->classNote ?></td>
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
      <div class="modal-body text-danger text-center">
        <p>You can't recover the data after delete.</p>
        <p>You will lost all student information of this class.</p>
      </div>
      <div class="modal-footer">
	      <form action="" method="POST">
	      	<input type="hidden" name="id" class="id">
        	<button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
        	<button type="submit" class="btn btn-danger" name="deleteClass">Delete</button>
	      </form>
      </div>
    </div>

  </div>
</div>

<script type="text/javascript">
	(function($) {
		$(document).ready(function() {
			$('.btnDelete').click(function(event) {
				$('#deleteModal').find('.id').val($(this).data('id'));
				$('#deleteModal').modal("show");
			});
		});
	})( jQuery );
</script>