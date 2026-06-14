<?php
/**
 * Template Name: Admin Committee
 */

global $wpdb;

/*=================
	Add Committee
=================*/
$committeeId = 0;

if (isset($_POST['addCommittee'])) {
	$insert = $wpdb->insert(
		'ct_committee',
		array(
			'committeeName' 				=> htmlentities($_POST['committeeName'], ENT_QUOTES),
			'committeeFather' 			=> htmlentities($_POST['committeeFather'], ENT_QUOTES),
			'committeeMother' 			=> htmlentities($_POST['committeeMother'], ENT_QUOTES),
			'committeeDesignation' 	=> htmlentities($_POST['committeeDesignation'], ENT_QUOTES),
			'committeeSession' 			=> htmlentities($_POST['committeeSession'], ENT_QUOTES),
			'committeeStatus' 			=> htmlentities($_POST['committeeStatus'], ENT_QUOTES),
			'committeeImg' 				=> htmlentities($_POST['committeeImg'], ENT_QUOTES),
			'committeeNote' 				=> htmlentities($_POST['committeeNote'], ENT_QUOTES),
			'committee_serial' 			=> htmlentities($_POST['committee_serial'], ENT_QUOTES)
		)
	);
	
	$message = ms3message($insert, 'Added');
}

/*edit Section*/
$editid = 0;
if (isset($_POST['editCommittee'])) {
	$editid = $_POST['id'];
	$edit = $wpdb->get_results( "SELECT * FROM ct_committee WHERE committeeid = $editid" );
	$edit = $edit[0];
}

/*Update Section*/
if (isset($_POST['updateCommittee'])) {
	$update = $wpdb->update(
		'ct_committee',
		array(
			'committeeName' 				=> htmlentities($_POST['committeeName'], ENT_QUOTES),
			'committeeFather' 			=> htmlentities($_POST['committeeFather'], ENT_QUOTES),
			'committeeMother' 			=> htmlentities($_POST['committeeMother'], ENT_QUOTES),
			'committeeDesignation' 	=> htmlentities($_POST['committeeDesignation'], ENT_QUOTES),
			'committeeSession' 			=> htmlentities($_POST['committeeSession'], ENT_QUOTES),
			'committeeStatus' 			=> htmlentities($_POST['committeeStatus'], ENT_QUOTES),
			'committeeImg' 				=> htmlentities($_POST['committeeImg'], ENT_QUOTES),
			'committeeNote' 				=> htmlentities($_POST['committeeNote'], ENT_QUOTES),
			'committee_serial' 			=> htmlentities($_POST['committee_serial'], ENT_QUOTES)
		),
		array( 'committeeid' => $_POST['id'])
	);

	$message = ms3message($update, 'Updated');
}

/*Delete Section*/
if (isset($_POST['deleteCommittee'])) {
	$delete = $wpdb->delete( 'ct_committee', array( 'committeeid' => $_POST['id'] ) );
	$message = ms3message($delete, 'Deleted');
}

function output($content){
	return str_replace("\n","<br />", html_entity_decode($content));
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

	<h2>Committee Management <a href="?page=committee" class="pull-right btn btn-success">Add Committee Member </a> </h2><br>
	<div class="row">
		<?php if(!isset($_GET['id']) || isset($_POST['editCommittee'])){ ?>
			<div class="col-md-7">
				<div class="panel panel-info">
				  <div class="panel-heading"><h3><?= isset($edit) ? 'Edit' : 'Add'; ?> Committee Member </h3></div>
				  <div class="panel-body">
				    <form action="" method="POST">

			    		<input type="hidden" name="id" value="<?= $editid ?>">
				    	<div class="row">

				    		<div class="form-group col-md-6">
					    		<label>Committee Member Name</label>
					    		<input class="form-control" type="text" name="committeeName" value="<?= isset($edit) ? $edit->committeeName : ''; ?>" required>
					    	</div>

					    	<div class="form-group col-md-3">
					    		<label>Committee Image</label>
					    		<div class="mediaUploadHolder">
						    		<button type="button" class="mediaUploader btn btn-success">Upload</button>
						    		<span>
						    			<?php echo (isset($edit)) ? "<img height='40' src='".$edit->committeeImg."'>" : ''; ?>
						    		</span>

										<input class="hidden teacherImg" type="text" name="committeeImg" value="<?= isset($edit) ? $edit->committeeImg : ''; ?>">
					    		</div>
					    	</div>

					    	<div class="form-group col-md-3">
					    		<label>Designation</label>
					    		<input class="form-control" type="text" name="committeeDesignation" value="<?= isset($edit) ? $edit->committeeDesignation : ''; ?>" required>
					    	</div>

					    </div>
					    <div class="row">

					    	<div class="form-group col-md-6">
					    		<label>Father's Name</label>
					    		<input class="form-control" type="text" name="committeeFather" value="<?= isset($edit) ? $edit->committeeFather : ''; ?>" required>
					    	</div>

					    	<div class="form-group col-md-6">
					    		<label>Mother's Name</label>
					    		<input class="form-control" type="text" name="committeeMother" value="<?= isset($edit) ? $edit->committeeMother : ''; ?>" required>
					    	</div>

				    	</div>

				    	<div class="row">

				    		<div class="form-group col-md-6">
					    		<label>Session</label>
					    		<input class="form-control" type="text" name="committeeSession" value="<?= isset($edit) ? $edit->committeeSession : ''; ?>" required>
					    	</div>

					    	<div class="form-group col-md-6">
					    		<label>Status</label>
					    		<select class="form-control" name="committeeStatus" required>
					    			<option value="">Select Status</option>
					    			<option value="active" <?= (isset($edit) && $edit->committeeStatus == 'active') ? 'selected' : ''; ?>>Active</option>
					    			<option value="inactive" <?= (isset($edit) && $edit->committeeStatus == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
					    		</select>
					    	</div>

				    	</div>

				    	<div class="form-group">
				    		<label>Note</label>
				    		<textarea class="form-control" name="committeeNote"><?= isset($edit) ? $edit->committeeNote : ''; ?></textarea>
				    	</div>
				    
						<div class="form-group">
				    		<label>Committee Serial Number</label>
				    		<input class="form-control" type="number" autocomplete="" value="<?= isset($edit) ? $edit->committee_serial : ''; ?>" name="committee_serial" >
				    	</div>

				    	<div class="form-group text-right">
				    		<button class="btn btn-primary" type="submit" name="<?= isset($edit) ? 'updateCommittee' : 'addCommittee'; ?>"><?= isset($edit) ? 'Update' : 'Add'; ?> Committee Member </button>
				    	</div>

				    </form>
				  </div>
				</div>
			</div>
		<?php }else{ ?>
			<div class="col-md-7">
				<div class="panel panel-info">

					<?php 
						$committeeId = $_GET['id'];
					  $committees = $wpdb->get_results("SELECT * FROM ct_committee WHERE committeeid = $committeeId" );
					  foreach ($committees as $committee) {
					    ?>
					    <div class="panel-heading">
					    	<h3><?= $committee->committeeName ?><br><small><?= $committee->committeeDesignation ?></small></h3>
					    </div>
				  		<div class="panel-body">
						    <div id="committeeProfile" class="row">
						      <div class="col-md-4">
						        <?php if(!empty($committee->committeeImg)){ ?>
						        <img src="<?= $committee->committeeImg ?>" class="img-responsive stdImg">
						        <?php }else{ ?>
						        <img src="<?= get_template_directory_uri() ?>/img/No_Image.jpg" class="img-responsive stdImg">
						        <?php } ?>
						      </div>
						      <div class="col-md-8">

						        <div class="row">
						        	<div class="col-md-6">
						        		<label>Designation</label>
						            <p><?= output($committee->committeeDesignation) ?></p>
						            <label>Session</label>
								        <p><?= output($committee->committeeSession) ?></p>
								        <label>Status</label>
								        <p>
								        	<span class="label label-<?= ($committee->committeeStatus == 'active') ? 'success' : 'danger'; ?>">
								        		<?= ucfirst($committee->committeeStatus) ?>
								        	</span>
								        </p>
						        	</div>

						        	<div class="col-md-6">
						        		<label>Father</label>
						            <p><?= output($committee->committeeFather) ?></p>
						            <label>Mother</label>
								        <p><?= output($committee->committeeMother) ?></p>
						        	</div>
						        </div>
						        
						        <?php if(!empty($committee->committeeNote)){ ?>
						        <div class="row">
						        	<div class="col-md-12">
						        		<hr>
						        		<label>Note</label>
							            <p><?= output($committee->committeeNote) ?></p>
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
	  <div class="panel-heading"><h3>All Committee Members </h3></div>
	  <div class="panel-body">
			<div class="table-responsive">
				<table class="table table-bordered table-striped" id="datatable">
					<thead>
						<tr>
							<th>Serial</th>
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
						$committees = $wpdb->get_results( "SELECT * FROM ct_committee order by committee_serial" );
						foreach ($committees as $committee) {
							?>
							<tr>
								<td><?= $committee->committee_serial ?></td>
								<td><?= $committee->committeeName ?></td>
								<td class="text-center" style="padding: 5px">
									<?= (!empty($committee->committeeImg)) ? "<img height='40' width='40' src='".$committee->committeeImg."' class='img-circle'>" : '<i class="fa fa-user-circle fa-2x text-muted"></i>'; ?>
								</td>
								<td><?= $committee->committeeDesignation ?></td>
								<td><?= $committee->committeeSession ?></td>
								<td>
									<span class="label label-<?= ($committee->committeeStatus == 'active') ? 'success' : 'danger'; ?>">
										<?= ucfirst($committee->committeeStatus) ?>
									</span>
								</td>
								<td class="text-center">
									<div class="btn-group" role="group">
										<a class="btn btn-sm btn-info" href="?page=committee&id=<?= $committee->committeeid ?>" title="View">
											<i class="fa fa-eye"></i>
										</a>
										<form class="actionForm" method="POST" action="" style="display: inline;">
								        	<input type="hidden" name="id" value="<?= $committee->committeeid ?>">
								        	<button type="submit" name="editCommittee" class="btn btn-sm btn-warning" title="Edit">
								        		<i class="fa fa-edit"></i>
								        	</button>
								        	<button type="button" class="btn btn-sm btn-danger btnDelete" data-id='<?= $committee->committeeid ?>' title="Delete">
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
        	<button type="submit" class="btn btn-danger" name="deleteCommittee">Delete</button>
	      </form>
      </div>
    </div>

  </div>
</div>

<script type="text/javascript">
	(function($) {
		$(document).ready(function() {
			var $siteUrl = $('#theSiteURL').text();

			$('.btnDelete').click(function(event) {
				$('#deleteModal').find('.id').val($(this).data('id'));
				$('#deleteModal').modal("show");
			});
		});
	})( jQuery );
</script>