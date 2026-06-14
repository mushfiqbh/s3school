<?php
/**
 * The template for displaying all pages
 */

get_header();

?>


<div class="">

	<div class="b-blog-classic no" style="margin: 40px 0;">
		<div class="container">
			<div class="row">
				<div class="col-xs-12 col-sm-7 col-md-8 col-lg-9">
					<div class="b-blog-items-holder wow slideInLeft">
						<div class="clearfix singelPageContent">

							<div class="text-center">
								<?php if ( has_post_thumbnail() ) { ?>
									<img class="img-responsive" src="<?= get_the_post_thumbnail_url() ?>">
								<?php } ?>
							</div>

							<h4 class="inherit-title"><b><?php the_title(); ?></b></h4>
							<div class="about-additional-text">
								<?php
								while ( have_posts() ) : the_post();

									the_content();

								endwhile;
								?>
							</div>
						</div>
					</div>
				</div>
				<div class="col-xs-12 col-sm-5 col-md-4 col-lg-3">
					<?php get_sidebar(); ?>
				</div>
			</div>
		</div>
	</div>
</div>
<?php get_footer();
