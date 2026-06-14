<?php
/**
 * Template Name: Admin Governing Body
 */

global $wpdb;

if (!function_exists('s3school_gb_output')) {
	function s3school_gb_output($content)
	{
		return str_replace("\n", "<br />", html_entity_decode($content));
	}
}

$get_text = static function ($key) {
	return isset($_POST[$key]) ? htmlentities(wp_unslash($_POST[$key]), ENT_QUOTES) : '';
};

$get_int = static function ($key, $default = 0) {
	return isset($_POST[$key]) ? (int) $_POST[$key] : $default;
};

$edit    = null;
$edit_id = 0;
$message = null;

if (isset($_POST['addGoverningBody'])) {
	$insert = $wpdb->insert(
		'ct_governing_body',
		array(
			'governing_body_name'        => $get_text('governing_body_name'),
			'governing_body_father_name' => $get_text('governing_body_father_name'),
			'governing_body_mother_name' => $get_text('governing_body_mother_name'),
			'governing_body_designation' => $get_text('governing_body_designation'),
			'governing_body_session'     => $get_text('governing_body_session'),
			'governing_body_image'       => $get_text('governing_body_image'),
			'note'                       => $get_text('note'),
			'order_number'               => $get_int('order_number'),
			'is_active'                  => $get_int('is_active', 1),
		)
	);

	$message = ms3message($insert, 'Added');
}

if (isset($_POST['updateGoverningBody'])) {
	$record_id = absint($_POST['id']);
	$update    = $wpdb->update(
		'ct_governing_body',
		array(
			'governing_body_name'        => $get_text('governing_body_name'),
			'governing_body_father_name' => $get_text('governing_body_father_name'),
			'governing_body_mother_name' => $get_text('governing_body_mother_name'),
			'governing_body_designation' => $get_text('governing_body_designation'),
			'governing_body_session'     => $get_text('governing_body_session'),
			'governing_body_image'       => $get_text('governing_body_image'),
			'note'                       => $get_text('note'),
			'order_number'               => $get_int('order_number'),
			'is_active'                  => $get_int('is_active', 0),
		),
		array('governing_body_id' => $record_id)
	);

	$message = ms3message($update, 'Updated');
	$edit_id = $record_id;
	$edit    = $wpdb->get_row($wpdb->prepare('SELECT * FROM ct_governing_body WHERE governing_body_id = %d', $record_id));
}

if (isset($_POST['deleteGoverningBody'])) {
	$record_id = absint($_POST['id']);
	$delete    = $wpdb->delete('ct_governing_body', array('governing_body_id' => $record_id));
	$message   = ms3message($delete, 'Deleted');
}

if (isset($_POST['editGoverningBody'])) {
	$edit_id = absint($_POST['id']);
}

if ($edit_id > 0 && $edit === null) {
	$edit = $wpdb->get_row($wpdb->prepare('SELECT * FROM ct_governing_body WHERE governing_body_id = %d', $edit_id));
}

?>
<p id="theSiteURL" style="display: none;"><?= get_template_directory_uri() ?></p>
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

	<h2>Governing Body Management <a href="?page=governing-body" class="pull-right btn btn-success">Add Governing Body Member </a> </h2><br>
	<div class="row">
		<?php if(!isset($_GET['id']) || isset($_POST['editGoverningBody']) || isset($_POST['updateGoverningBody']) || isset($_POST['deleteGoverningBody'])){ ?>
			<div class="col-md-7">
				<div class="panel panel-info">
				  <div class="panel-heading"><h3><?= (!empty($edit)) ? 'Edit' : 'Add'; ?> Governing Body Member </h3></div>
				  <div class="panel-body">
				    <form action="" method="POST">

		    		<input type="hidden" name="id" value="<?= $edit_id ?>">
				    	<div class="row">

				    		<div class="form-group col-md-6">
						    	<label>Member Name</label>
						    	<input class="form-control" type="text" name="governing_body_name" value="<?= (!empty($edit)) ? $edit->governing_body_name : ''; ?>" required>
					    	</div>

				    		<div class="form-group col-md-3">
					    		<label>Member Image</label>
					    		<div class="mediaUploadHolder">
						    		<button type="button" class="mediaUploader btn btn-success">Upload</button>
						    		<span>
						    			<?php echo (!empty($edit) && !empty($edit->governing_body_image)) ? "<img height='40' src='".$edit->governing_body_image."'>" : ''; ?>
						    		</span>

								<input class="hidden teacherImg" type="text" name="governing_body_image" value="<?= (!empty($edit)) ? $edit->governing_body_image : ''; ?>">
					    		</div>
					    	</div>

					    	<div class="form-group col-md-3">
					    		<label>Designation</label>
					    		<input class="form-control" type="text" name="governing_body_designation" value="<?= (!empty($edit)) ? $edit->governing_body_designation : ''; ?>" required>
					    	</div>

					    </div>
					    <div class="row">

					    	<div class="form-group col-md-6">
					    		<label>Father's Name</label>
					    		<input class="form-control" type="text" name="governing_body_father_name" value="<?= (!empty($edit)) ? $edit->governing_body_father_name : ''; ?>" required>
					    	</div>

					    	<div class="form-group col-md-6">
					    		<label>Mother's Name</label>
					    		<input class="form-control" type="text" name="governing_body_mother_name" value="<?= (!empty($edit)) ? $edit->governing_body_mother_name : ''; ?>" required>
					    	</div>

				    	</div>

				    	<div class="row">

				    		<div class="form-group col-md-6">
				    			<label>Session</label>
				    			<input class="form-control" type="text" name="governing_body_session" value="<?= (!empty($edit)) ? $edit->governing_body_session : ''; ?>" required>
				    		</div>

				    		<div class="form-group col-md-6">
				    			<label>Status</label>
								<select class="form-control" name="is_active" required>
									<option value="1" <?= (!empty($edit) ? ((int) $edit->is_active === 1 ? 'selected' : '') : 'selected') ?>>Active</option>
									<option value="0" <?= (!empty($edit) && (int) $edit->is_active === 0) ? 'selected' : ''; ?>>Inactive</option>
				    			</select>
				    		</div>

				    	</div>

				    	<div class="form-group">
				    		<label>Note</label>
				    		<textarea class="form-control" name="note"><?= (!empty($edit)) ? $edit->note : ''; ?></textarea>
				    	</div>
		    
						<div class="form-group">
				    		<label>Display Order</label>
				    		<input class="form-control" type="number" autocomplete="" value="<?= (!empty($edit)) ? (int) $edit->order_number : ''; ?>" name="order_number" min="0">
				    	</div>

				    	<div class="form-group text-right">
				    		<button class="btn btn-primary" type="submit" name="<?= (!empty($edit)) ? 'updateGoverningBody' : 'addGoverningBody'; ?>"><?= (!empty($edit)) ? 'Update' : 'Add'; ?> Governing Body Member </button>
				    	</div>

				    </form>
				  </div>
				</div>
			</div>
		<?php }else{ ?>
			<div class="col-md-7">
				<div class="panel panel-info">

					<?php 
						$governing_body_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
					  $records = $wpdb->get_results($wpdb->prepare('SELECT * FROM ct_governing_body WHERE governing_body_id = %d', $governing_body_id));
					  foreach ($records as $body) {
					    ?>
					    <div class="panel-heading">
					    	<h3><?= $body->governing_body_name ?><br><small><?= $body->governing_body_designation ?></small></h3>
					    </div>
				  		<div class="panel-body">
						    <div id="governingBodyProfile" class="row">
						      <div class="col-md-4">
						        <?php if(!empty($body->governing_body_image)){ ?>
						        <img src="<?= $body->governing_body_image ?>" class="img-responsive stdImg">
						        <?php }else{ ?>
						        <img src="<?= get_template_directory_uri() ?>/img/No_Image.jpg" class="img-responsive stdImg">
						        <?php } ?>
						      </div>
						      <div class="col-md-8">

						        <div class="row">
						        	<div class="col-md-6">
						        		<label>Designation</label>
						            <p><?= s3school_gb_output($body->governing_body_designation) ?></p>
						            <label>Session</label>
							        <p><?= s3school_gb_output($body->governing_body_session) ?></p>
							        <label>Status</label>
							        <p>
							        	<span class="label label-<?= ((int) $body->is_active === 1) ? 'success' : 'danger'; ?>">
							        		<?= ((int) $body->is_active === 1) ? 'Active' : 'Inactive' ?>
							        	</span>
							        </p>
						        	</div>

						        	<div class="col-md-6">
						        		<label>Father</label>
						            <p><?= s3school_gb_output($body->governing_body_father_name) ?></p>
						            <label>Mother</label>
							        <p><?= s3school_gb_output($body->governing_body_mother_name) ?></p>
						        	</div>
						        </div>
					        
						        <?php if(!empty($body->note)){ ?>
						        <div class="row">
						        	<div class="col-md-12">
						        		<hr>
						        		<label>Note</label>
							            <p><?= s3school_gb_output($body->note) ?></p>
						        	</div>
						        </div>
						        <?php } ?>
						      </div>
						    </div>
						  </div>
						  <?php
							}
						?>
			
					</div>
				</div>
			<?php }?>
		<div class="col-md-5">
	<div class="panel panel-info">
	  <div class="panel-heading"><h3>All Governing Body Members </h3></div>
	  <div class="panel-body">
			<div class="table-responsive">
				<table class="table table-bordered table-striped" id="datatable">
					<thead>
						<tr>
							<th>Order</th>
							<th>Name</th>
							<th style="width: 60px">Image</th>
							<th>Designation</th>
							<th>Session</th>
							<th>Status</th>
							<th style="width: 100px">Action</th>
						</tr>
					</thead>
					<tbody>
					<?php
						$bodies = $wpdb->get_results('SELECT * FROM ct_governing_body ORDER BY order_number ASC, governing_body_name ASC');
						foreach ($bodies as $body) {
							?>
							<tr>
								<td><?= (int) $body->order_number ?></td>
								<td><?= $body->governing_body_name ?></td>
								<td class="text-center" style="padding: 5px">
									<?= (!empty($body->governing_body_image)) ? "<img height='40' width='40' src='".$body->governing_body_image."' class='img-circle'>" : '<i class="fa fa-user-circle fa-2x text-muted"></i>'; ?>
								</td>
								<td><?= $body->governing_body_designation ?></td>
								<td><?= $body->governing_body_session ?></td>
								<td>
									<span class="label label-<?= ((int) $body->is_active === 1) ? 'success' : 'danger'; ?>">
										<?= ((int) $body->is_active === 1) ? 'Active' : 'Inactive' ?>
									</span>
								</td>
								<td class="text-center">
									<div class="btn-group" role="group">
										<a class="btn btn-sm btn-info" href="?page=governing-body&id=<?= $body->governing_body_id ?>" title="View">
											<i class="fa fa-eye"></i>
										</a>
										<form class="actionForm" method="POST" action="" style="display: inline;">
						        	<input type="hidden" name="id" value="<?= $body->governing_body_id ?>">
						        	<button type="submit" name="editGoverningBody" class="btn btn-sm btn-warning" title="Edit">
						        		<i class="fa fa-edit"></i>
						        	</button>
						        	<button type="button" class="btn btn-sm btn-danger btnDelete" data-id='<?= $body->governing_body_id ?>' title="Delete">
						        		<i class="fa fa-trash"></i>
						        	</button>
						        </form>
									</div>
								</td>
							</tr>
							<?php
							}
						?>
						</tbody>
				</table>
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
      <div class="modal-body">
        <p class="text-danger">You can't recover the data after delete.</p>
      </div>
	      <div class="modal-footer">
	      	<form action="" method="POST">
	      		<input type="hidden" name="id" class="id">
	        	<button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
	        	<button type="submit" class="btn btn-danger" name="deleteGoverningBody">Delete</button>
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