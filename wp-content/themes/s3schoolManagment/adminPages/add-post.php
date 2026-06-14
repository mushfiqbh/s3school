<?php

/**
 * Template Name: Add Post
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type']) && $_POST['type'] === 'uploadPostAttachment') {
	while (ob_get_level()) {
		ob_end_clean();
	}

	if (!isset($_FILES['post_attachment'])) {
		wp_send_json_error('Invalid request');
	}

	$uploadedfile = $_FILES['post_attachment'];

	if (!function_exists('wp_handle_upload')) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	$upload_overrides = ['test_form' => false];
	$movefile = wp_handle_upload($uploadedfile, $upload_overrides);

	if ($movefile && !isset($movefile['error'])) {
		$filename = basename($movefile['file']);
		$filetype = wp_check_filetype($filename, null);
		$attachment = [
			'guid'           => $movefile['url'],
			'post_mime_type' => $filetype['type'],
			'post_title'     => sanitize_file_name(pathinfo($filename, PATHINFO_FILENAME)),
			'post_content'   => '',
			'post_status'    => 'inherit'
		];

		$attach_id = wp_insert_attachment($attachment, $movefile['file']);
		if (!is_wp_error($attach_id)) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
			wp_update_attachment_metadata($attach_id, $attach_data);
			wp_send_json_success(['id' => $attach_id, 'url' => $movefile['url'], 'mime' => $filetype['type'], 'filename' => $filename]);
		}
		wp_send_json_error(is_wp_error($attach_id) ? $attach_id->get_error_message() : 'Upload failed');
	}

	$error_message = (is_array($movefile) && isset($movefile['error'])) ? $movefile['error'] : 'Upload failed';
	wp_send_json_error($error_message);
}

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
$postimgid = isset($editpost) ? get_post_thumbnail_id($postid) : '';

$postattachmenturl = !empty($postimgid) ? wp_get_attachment_url($postimgid) : '';
$postattachmentmime = !empty($postimgid) ? get_post_mime_type($postimgid) : '';
$postattachmentname = '';
if (!empty($postimgid)) {
	$postattachmentfile = get_attached_file($postimgid);
	$postattachmentname = $postattachmentfile ? basename($postattachmentfile) : '';
}
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

	.std-img-upload-container {
		position: relative;
		width: 50px;
		height: 50px;
		cursor: pointer;
		border: 1px solid #ddd;
		border-radius: 4px;
		overflow: hidden;
		background: #f9f9f9;
		display: inline-flex;
		align-items: center;
		justify-content: center;
	}
	.std-img-upload-container img {
		max-width: 100%;
		max-height: 100%;
		object-fit: cover;
	}
	.std-img-upload-overlay {
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 100%;
		background: rgba(0,0,0,0.4);
		display: none;
		align-items: center;
		justify-content: center;
		color: #fff;
	}
	.std-img-upload-container:hover .std-img-upload-overlay {
		display: flex;
	}
	.post-attachment-preview-pdf {
		display: none;
		flex-direction: column;
		gap: 6px;
		align-items: center;
		justify-content: center;
		text-align: center;
		padding: 6px;
		width: 100%;
		height: 100%;
		color: #333;
	}
	.post-attachment-preview-pdf .post-attachment-filename {
		max-width: 100%;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
		font-size: 11px;
		line-height: 1.2;
	}
	.post-attachment-preview-pdf .dashicons {
		font-size: 22px;
		width: 22px;
		height: 22px;
	}
	.post-attachment-meta {
		margin-top: 8px;
		font-size: 12px;
		color: #555;
	}
	.post-attachment-meta a {
		text-decoration: none;
	}
	.std-img-uploading {
		opacity: 0.5;
		pointer-events: none;
	}
	.std-img-uploading::after {
		content: "";
		position: absolute;
		width: 20px;
		height: 20px;
		border: 3px solid #ccc;
		border-top-color: #333;
		border-radius: 50%;
		animation: spin 1s linear infinite;
	}
	@keyframes spin {
		to { transform: rotate(360deg); }
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
											<div class="std-img-upload-container post-attachment-upload" data-initial-url="<?= esc_attr($postattachmenturl); ?>" data-initial-mime="<?= esc_attr($postattachmentmime); ?>" data-initial-name="<?= esc_attr($postattachmentname); ?>">
												<img src="<?= ($postimg != '') ? $postimg : (get_template_directory_uri() . '/img/image.png') ?>" class="std-img-preview">
												<div class="post-attachment-preview-pdf">
													<span class="dashicons dashicons-media-document"></span>
													<a class="post-attachment-open" href="#" target="_blank" rel="noopener">
														<span class="post-attachment-filename"><?= $postattachmentname ? $postattachmentname : 'PDF' ?></span>
													</a>
												</div>
												<div class="std-img-upload-overlay">
													<span class="dashicons dashicons-upload"></span>
												</div>
												<input type="file" class="std-img-input" style="display:none;" accept="image/*,application/pdf">
											</div>
											<input class="hidden teacherImg post-attachment-id" type="text" name="postimg" value="<?= esc_attr($postimgid); ?>">
											<div class="post-attachment-meta"></div>
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

			function s3sRenderAttachmentPreview($container, payload) {
				var $img = $container.find('.std-img-preview');
				var $pdf = $container.find('.post-attachment-preview-pdf');
				var $open = $container.find('.post-attachment-open');
				var $filenameEl = $container.find('.post-attachment-filename');
				var $meta = $container.closest('.form-group').find('.post-attachment-meta');

				var url = payload && payload.url ? payload.url : '';
				var mime = payload && payload.mime ? payload.mime : '';
				var filename = payload && payload.filename ? payload.filename : '';

				if (mime && mime.indexOf('application/pdf') === 0) {
					$img.hide();
					$pdf.css('display', 'flex');
					if (url) {
						$open.attr('href', url);
					}
					if (filenameEl.length) {
						$filenameEl.text(filename ? filename : 'PDF');
					}
					if (filename) {
						$meta.html('<div><strong>PDF:</strong> ' + filename + '</div>');
					} else {
						$meta.empty();
					}
				} else {
					$pdf.hide();
					$img.show();
					if (url) {
						$img.attr('src', url);
					}
					if (filenameEl.length) {
						$filenameEl.text('PDF');
					}
					if (filename) {
						$meta.html('<div><strong>File:</strong> ' + filename + '</div>');
					} else {
						$meta.empty();
					}
				}
			}

			$('.post-attachment-upload').each(function() {
				var $container = $(this);
				var initialUrl = $container.data('initial-url') || '';
				var initialMime = $container.data('initial-mime') || '';
				var initialName = $container.data('initial-name') || '';
				if (initialMime && initialMime.indexOf('application/pdf') === 0) {
					s3sRenderAttachmentPreview($container, { url: initialUrl, mime: initialMime, filename: initialName });
				}
			});

			$(document).on('click', '.post-attachment-upload', function(e) {
				if (e.target.classList && e.target.classList.contains('std-img-input')) {
					return;
				}
				$(this).find('.std-img-input').click();
			});

			$(document).on('change', '.post-attachment-upload .std-img-input', function(e) {
				var file = e.target.files[0];
				if (!file) return;

				var container = $(this).closest('.post-attachment-upload');
				var idField = container.closest('form').find('.post-attachment-id');

				var formData = new FormData();
				formData.append('post_attachment', file);
				formData.append('type', 'uploadPostAttachment');

				container.addClass('std-img-uploading');
				$.ajax({
					url: window.location.href,

					type: 'POST',
					data: formData,
					dataType: 'json',
					contentType: false,
					processData: false,
					success: function(response) {
						container.removeClass('std-img-uploading');
						if (response && response.success && response.data) {
							if (response.data.id && idField.length) {
								idField.val(response.data.id);
							}
							s3sRenderAttachmentPreview(container, response.data);
						} else {
							alert('Error: ' + (response && response.data ? response.data : 'Upload failed'));
						}
					},

					error: function() {
						container.removeClass('std-img-uploading');
						alert('Upload failed. Please try again.');
					}
				});
			});
		});
	})(jQuery);
</script>