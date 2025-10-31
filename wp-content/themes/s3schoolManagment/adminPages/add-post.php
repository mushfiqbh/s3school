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
	// Server-side validation for gallery images (150KB limit)
	$image_id = intval($_POST['postimg']);
	$category_id = intval($_POST['pcat']);
	$category = get_category($category_id);
	$is_gallery = ($category && stripos($category->name, 'gallery') !== false);
	
	if ($image_id && $is_gallery) {
		$image_path = get_attached_file($image_id);
		if ($image_path && file_exists($image_path)) {
			$file_size = filesize($image_path);
			$max_gallery_size = 150 * 1024; // 150KB
			
			if ($file_size > $max_gallery_size) {
				echo '<div class="alert alert-danger">Error: Gallery image size (' . round($file_size / 1024, 2) . ' KB) exceeds the 150 KB limit. Please upload a smaller image.</div>';
				$_POST['s3addfontendpost'] = null; // Prevent post creation
			} else {
				// File size is valid, proceed with post creation
				$post = array(
					'post_title'    => $_POST['ptitle'],
					'post_content'  => $_POST['postcontent'],
					'post_category' => array($_POST['pcat']),
					'post_status'   => 'publish',
					'post_type'   => 'post'
				);
				$postId = wp_insert_post($post);
				set_post_thumbnail($postId, $_POST['postimg']);
				echo '<div class="alert alert-success">Gallery post created successfully!</div>';
			}
		} else {
			// Image file not found, proceed without validation
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
	} else {
		// Not a gallery post, use default validation (60KB handled in JS)
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
											<select class="form-control" name="pcat" id="pcat">
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
											<p class="text-muted" id="imageSizeHint" style="font-size: 12px; margin: 5px 0;">
												<strong>Image Size Limit:</strong> <span id="currentSizeLimit">60 KB for regular posts, 150 KB for gallery posts</span>
											</p>
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
			
			// Update size limit indicator when category changes
			$('#pcat').on('change', function() {
				var selectedText = $(this).find('option:selected').text().toLowerCase();
				var isGallery = selectedText.indexOf('gallery') !== -1;
				
				if (isGallery) {
					$('#currentSizeLimit').html('<span style="color: #28a745; font-weight: 600;">150 KB for gallery posts</span>');
				} else {
					$('#currentSizeLimit').html('60 KB for regular posts, 150 KB for gallery posts');
				}
			});
		});
	})(jQuery);
</script>