<?php

/**
 * Template Name: Add Post
 */
get_header();

$haveAccess = false;
if (isset(wp_get_current_user()->roles[0]) || current_user_can('administrator')) {
	if (wp_get_current_user()->roles[0] == 'um_headmaster' || current_user_can('administrator')) {
		$haveAccess = true;
	}
}

$access = $wpdb->get_results("SELECT * FROM ct_access WHERE acid = 1");
$access = $access[0];
foreach ($access as $key => $value) {
	$$key = $value;
}

if (isset($_POST['deletepost'])) {
	wp_delete_post($_POST['postid']);
}

if (isset($_POST['editpost'])) {
	$editpost = get_post($_POST['postid']);
}

if (isset($_POST['s3addfontendpost'])) {
	$post = array(
		'post_title'    => $_POST['ptitle'],
		'post_content'  => $_POST['postcontent'],
		'post_category' => array($_POST['pcat']),
		'post_status'   => 'publish',
		'post_type'   => 'post'
	);
	$postId = wp_insert_post($post);
	set_post_thumbnail($postId, $_POST['postimg']);
}

if (isset($_POST['s3fontendeditpost'])) {
	$post = array(
		'ID'    => $_POST['postid'],
		'post_title'    => $_POST['ptitle'],
		'post_content'  => $_POST['postcontent'],
		'post_category' => array($_POST['pcat'])
	);

	wp_update_post($post);
}

$postid    = isset($editpost) ? $editpost->ID 					: '';
$posttitle = isset($editpost) ? $editpost->post_title 	: '';
$postconte = isset($editpost) ? $editpost->post_content : '';
$postcateg = isset($editpost) ? get_the_category($postid) : '';
$postimg 	 = isset($editpost) ? get_the_post_thumbnail_url($postid) : '';
$action    = isset($editpost) ? 's3fontendeditpost' : 's3addfontendpost';
?>

<style type="text/css">
	#user-submitted-title,
	#user-submitted-category,
	.usp-clone {
		width: 100%;
		margin-bottom: 15px;
		border-radius: 3px;
		border: 2px solid #ccc;
		padding: 5px;
	}

	#usp-submit {
		text-align: right;
	}

	#user-submitted-post {
		padding: 8px 25px;
		font-weight: bold;
		border-radius: 5px;
		border: 0;
		background: #337ab7;
		color: #fff;
	}
</style>


<div class="b-layer-main">

	<div class="">
		<div class="container">

			<div class="wow slideInLeft fronendAdmin">
				<?php if ($haveAccess) { ?>
					<div class="panel panel-default">
						<div class="panel-heading">POST</div>
						<div class="panel-body">
							<div class="row">
								<div class="col-md-6 text-center">
									<form action="" method="POST">
										<input type="hidden" name="postid" value="<?= $postid ?>">
										<div class="form-group">
											<label>Post Title</label>
											<input class="form-control" type="text" name="ptitle" value="<?= $posttitle ?>">
										</div>
										<div class="form-group">
											<label>Post Category</label>
											<select class="form-control" name="pcat">
												<option>Select a Category</option>
												<?php
												$categories = get_categories(array("hide_empty" => 0));
												foreach ($categories as $category) {
													$selected =  '';
													if ($postcateg[0]->term_id == $category->term_id) {
														$selected =  'selected';
													}
													echo "<option value='$category->term_id' $selected>$category->name</option>";
												} ?>
											</select>
										</div>
										<div class="form-group">
											<label>Post Content</label>
											<?php wp_editor($postconte, 'postcontent'); ?>
										</div>
										<div class="form-group">
											<label>Attach Image or PDF</label>
											<div class="mediaUploadHolder">
												<button type="button" class="mediaUploader btn btn-success returnid">Upload</button>
												<span>
													<?php echo ($postimg != '') ? "<img height='40' src='" . $postimg . "'>" : ''; ?>
												</span>

												<input class="hidden teacherImg" type="text" name="postimg" value="<?= isset($edit) ? $postimg : ''; ?>">
											</div>
										</div>
										<div>
											<button type="submit" class="btn btn-primary">POST</button>
										</div>
										<input type="hidden" name="<?= $action ?>" value="fontendpost">
									</form>
								</div>
								<div class="col-md-6">
									<table class="table table-bordered" id="allposttbl">
										<thead>
											<tr>
												<th>#</th>
												<th>Post</th>
												<th>Action</th>
											</tr>
										</thead>
										<tbody>
											<?php $args = array(
												'post_type' => 'post',
												'orderby' => 'ID',
												'post_status' => 'publish',
												'order' => 'DESC',
												'posts_per_page' => -1
											);
											$result = new WP_Query($args);
											if ($result->have_posts()) {
												$num = 1; ?>

												<?php while ($result->have_posts()) {
													$result->the_post(); ?>
													<tr>
														<td><?= $num++ ?></td>
														<td><?php the_title(); ?></td>
														<td>
															<form action="" method="POST" style="display: inline-block;">
																<input type="hidden" name="postid" value="<?php the_ID() ?>">
																<div class="text-center">
																	<button name="editpost" class="btn-link" type="submit"><span class="dashicons dashicons-welcome-write-blog text-primary"></span></button>

																	<span style="cursor: pointer;" class="deletepost dashicons dashicons-trash text-danger"></span>

																	<button name="deletepost" type="Submit" class="btn btn-danger" style="display: none;">YES</button>
																</div>
															</form>
														</td>
													</tr>
											<?php }
											} ?>
										</tbody>
									</table>

								</div>
							</div>
						</div>
					</div>
				<?php } ?>

			</div>

		</div>
	</div>
</div>


<?php get_footer(); ?>
<script src="https://cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
<script type="text/javascript">
	(function($) {
		$(document).ready(function() {
			$('#allposttbl').DataTable();
			$('#allposttbl').on('click', '.deletepost', function() {
				$(this).hide('fast').closest('div').find('.btn').show('fast');
			});
		});
	})(jQuery);
</script>