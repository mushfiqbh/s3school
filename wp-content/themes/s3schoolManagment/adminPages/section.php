<?php
/*
** Template Name: Admin Section
*/ 
global $wpdb;

/*=================
	Add Section
=================*/
if (isset($_POST['addSection'])) {

	$insert = $wpdb->insert(
		'ct_section',
		array(
			'sectionName' => $_POST['sectionName'],
			'secStart' 		=> $_POST['secStart'],
			'secEnd' 			=> $_POST['secEnd'],
			'forClass' 		=> $_POST['forClass'],
			'secNote' 		=> $_POST['secNote']
		)
	);
	$message = ms3message($insert, 'Added');
}

/*Update Section*/
if (isset($_POST['updateSection'])) {

	$update = $wpdb->update(
		'ct_section',
		array(
			'sectionName' => $_POST['sectionName'],
			'secStart' 		=> $_POST['secStart'],
			'secEnd' 			=> $_POST['secEnd'],
			'forClass' 		=> $_POST['forClass'],
			'secNote' 		=> $_POST['secNote']
		),
		array( 'sectionid' => $_POST['id'])
	);

	$message = ms3message($update, 'Updated');
}


/*Delete Section*/
if (isset($_POST['deleteSection'])) {
	$delete = $wpdb->delete( 'ct_section', array( 'sectionid' => $_POST['id'] ) );
	$message = ms3message($delete, 'Deleted');
}

/*edit Section*/
$editid = 0;
if (isset($_POST['editSection'])) {
	$editid = $_POST['id'];
	$edit = $wpdb->get_results( "SELECT * FROM ct_section WHERE sectionid = $editid" );
	$edit = $edit[0];
}
?>

<div class="container-fluid maxAdminpages" style="padding-left: 0">
	
	<!-- Show Status message -->
  <?php if(isset($message)){ ms3showMessage($message); } ?>


	<div class="row">
		<div class="col-md-6">
			<div class="panel panel-info">
			  <div class="panel-heading"><h3><?= isset($edit) ? 'Edit' : 'Add'; ?> Section<br><small>Section Information</small></h3></div>
			  <div class="panel-body">
			    <form action="" method="POST">
			    	<div class="row">
			    		<input type="hidden" name="id" value="<?= $editid ?>">
			    		<div class="form-group col-md-6">
				    		<label>Section Name</label>
				    		<input class="form-control" type="text" name="sectionName" value="<?= isset($edit) ? $edit->sectionName : ''; ?>" required>
				    	</div>
				    	<div class="form-group col-md-6">
				    		<label>Class Start Time</label>
				    		<input class="form-control" type="text" name="secStart" value="<?= isset($edit) ? $edit->secStart : ''; ?>">
				    	</div>
				    	<div class="form-group col-md-6">
				    		<label>Class End Time</label>
				    		<input class="form-control" type="text" name="secEnd" value="<?= isset($edit) ? $edit->secEnd : ''; ?>">
				    	</div>
				    	<div class="form-group col-md-6">
				    		<label>For Class</label>
				    		<select class="form-control" name="forClass" required>
				    			<?php
				    			$sel = '';
					    			if (!isset($edit) || empty($edit->forClass)) {
					    				echo "<option disabled selected>Select a Class..</option>";
					    			}

				    				$classes = $wpdb->get_results( "SELECT * FROM ct_class" );
										foreach ($classes as $class) {
										    $sel = (@$edit->forClass == $class->classid) ? "selected" : '';
											?>
					    				<option value='<?= $class->classid ?>' <?= $sel ?>>
					    					<?= $class->className ?>
					    					<?= !empty($class->groupName) ? " ($class->groupName)" : ''; ?>
					    				</option>
					    				<?php
										}
				    			?>
				    		</select>
				    	</div>
			    	</div>

			    	<div class="form-group">
			    		<label>Note</label>
			    		<textarea class="form-control" name="secNote"><?= isset($edit) ? $edit->secNote : ''; ?></textarea>
			    	</div>

			    	<div class="form-group text-right">
			    		<button class="btn btn-primary" type="submit" name="<?= isset($edit) ? 'updateSection' : 'addSection'; ?>"><?= isset($edit) ? 'Update' : 'Add'; ?> Section</button>
			    	</div>

			    </form>
			  </div>
			</div>
		</div>
		<div class="col-md-6">
			<div class="panel panel-info">
			  <div class="panel-heading"><h3>All Section<br><small>All Sections information</small></h3></div>
			  <div class="panel-body">
					<div class="panel-group" id="accordion">

						<?php
							$sections = $wpdb->get_results( "SELECT sectionid,sectionName,secStart,secEnd,secNote,className FROM ct_section LEFT JOIN ct_class ON ct_section.forClass = ct_class.classid" );
							foreach ($sections as $section) {
								?>
								<div class="panel panel-default">
							    <div class="panel-heading">
							      <h4 class="panel-title">
							        <a data-toggle="collapse" data-parent="#accordion" href="#collapse<?= $section->sectionid ?>">
								        <?= $section->sectionName ?>
								        (<?= $section->className ?><?= !empty($section->groupName) ? " - ".$section->groupName : ''; ?>)
								      </a>
							        <form class="pull-right actionForm" method="POST" action="">
							        	<input type="hidden" name="id" value="<?= $section->sectionid ?>">
							        	<button type="submit" name="editSection" class="btn-link">
							        		<span class="dashicons dashicons-welcome-write-blog"></span></span>
							        	</button>
							        	<button type="button" class="btn-link btnDelete" data-id='<?= $section->sectionid ?>'>
							        		<span class="dashicons dashicons-trash"></span>
							        	</button>
							        </form>
							      </h4>
							    </div>

							    <div id="collapse<?= $section->sectionid ?>" class="panel-collapse collapse">
							      <div class="panel-body">
											<table class="table table-bordered">
												<tr>
													<td>Class Start Time</td>
													<td><?= $section->secStart ?></td>
												</tr>
												<tr>
													<td>Class End Time</td>
													<td><?= $section->secEnd ?></td>
												</tr>
												<tr>
													<td>For Class</td>
													<td><?= $section->className ?></td>
												</tr>
												<tr>
													<td>Note</td>
													<td><?= $section->secNote ?></td>
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


<div id="deleteModal" class="modal fade" role="dialog">
  <div class="modal-dialog modal-sm">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Delete Data</h4>
      </div>
      <div class="modal-body">
        <p class="text-danger">You can't recover the data after delete.</p>
      </div>
      <div class="modal-footer">
	      <form action="" method="POST">
	      	<input type="hidden" name="id" class="id">
        	<button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
        	<button type="submit" class="btn btn-danger" name="deleteSection">Delete</button>
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