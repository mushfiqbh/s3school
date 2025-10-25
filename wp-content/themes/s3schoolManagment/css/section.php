<?php
/**
 * Template Name: Admin Section
 */
get_header();

?>

<div class="b-layer-main">

	<div class="b-blog-classic">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
					<div class="b-blog-items-holder galleryHolder classFAdmin">

						<?php require __DIR__.'/../adminPages/section.php'; ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<?php get_footer(); ?>