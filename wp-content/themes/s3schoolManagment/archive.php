<?php get_header(); ?>

<div class="b-page-wrap">
	<div class="b-page-content with-layer-bg">
		<div class="b-layer-big otherPageBg">
			<div class="layer-big-bg page-layer-big-bg">
				<div class="layer-content-big text-center">
					<h1><b><?=  str_replace('Category:', '', get_the_archive_title());  ?></b></h1>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="b-layer-main">
	<div class="page-arrow">
		<i class="fa fa-angle-down" aria-hidden="true"></i>
	</div>
	<div class="b-blog-classic">
		<div class="container">
			<div class="row">
				<div class="col-xs-12 col-sm-7 col-md-8 col-lg-9">
					<div class="b-blog-items-holder wow slideInLeft">
						
						<?php 
							if ( have_posts() ) : 

								while ( have_posts() ) : the_post();
									?>
										<div class="b-blog__item wow zoomIn">
											<div class="blog-item-content clearfix">
												<div class="row">
													<div class="col-md-4">
														<a href="<?= the_permalink() ?>" class="blog-item-img">
															<?php 
																if ( has_post_thumbnail() ) {
																  the_post_thumbnail();
																}
																else {
																  ?>
																  	<img width="350" src="<?= get_template_directory_uri() ?>/img/noblogimage.png" alt="/">
																  <?php
																}
															?>
														</a>
													</div>

													<div class="col-md-8">
														<div class="blog-item-caption">
															<div class="item-data">
																<?= get_post_time('j M, Y', true) ?>
																
															</div>
															<h4 class="item-name">
																<a class="blodItemTitle" href="<?= the_permalink() ?>"><?= the_title( ) ?></a>
															</h4>
															<p class="item-description">
																<?php 
																	$limit = 350;
																	$content = get_the_content( );

																	if(strlen($content) > $limit) {
																	  $text = preg_replace("/^(.{1,$limit})(\s.*|$)/s", '\\1...', $content);
																	}else{
																	  $text = $content;
																	}
																	echo $text;
																?>
															</p>
															<a href="<?= the_permalink() ?>" title="Read more" class="item-read-more">...</a>
		
														</div>
													</div>
												</div>
											</div>
										</div>

									<?php
										

								endwhile;

									

							else :

							endif; 
						?>
		
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

