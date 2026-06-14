<?php
/**
 * Template Name: Gallery
 */
get_header(); ?>
<style>
    .b-blog-classic img{
        width:185px;
        height:200px;
    }
    .gallery-item-content .gallery-item-caption .item-category{
        font-weight:200;
    }
</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/jquery.magnific-popup.min.js"></script>


<div class="b-page-wrap">
	<div class="b-page-content with-layer-bg">
		<div class="b-layer-big otherPageBg">
			<div class="layer-big-bg page-layer-big-bg">
				<div class="layer-content-big text-center">
					<h1><b>Gallery</b></h1>
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
					<div class="b-blog-items-holder wow slideInLeft galleryHolder">
						
						<div class="row">
          <div class="js-zoom-gallery grid clearfix">
            <div class="grid-sizer"></div>
            <?php
							$args = [
							  'post_status'   => 'publish',
							  'category_name' => 'gallery'
							];
							$gallery = new WP_Query( $args );
							if( $gallery->have_posts() ) {
								
								while ( $gallery->have_posts() ) {
									$gallery->the_post();
									if ( has_post_thumbnail() ) {
										$img = get_the_post_thumbnail_url();
										?>
											<div class="b-gallery-2__item col-md-3 col-sm-6">
					              <div class="gallery-item-content">
					                <div class="gallery-item-img">
					                  <img src="<?= $img  ?>" alt="<?php the_title() ?>" class="img-responsive">
					                  <div class="gallery-item-hover">
					                    <a href="<?= $img  ?>" class="js-zoom-gallery__item">
					                      <span class="item-hover-icon"><i class="fa fa-search" aria-hidden="true"></i></span>
					                      <img src="<?= $img  ?>" alt="<?php the_title() ?>" class="img-responsive">
					                    </a>
					                  </div>
					                </div>
					                <div class="gallery-item-caption text-center">
			
					                  <p class="item-category">
					                    <?php the_title() ?>
					                  </p>
					                </div>
					              </div>
					            </div>
										<?php
									}
								}
								
							}else{
								echo "<h3 class='text-center text-danger'>Gallery Empty</h3>";
							}
							wp_reset_postdata();
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






<?php get_footer(); ?>

<script type="text/javascript">
 	// gallery images popup
  if ($('.js-zoom-gallery').length > 0) {
    $('.js-zoom-gallery').each(function() { // the containers for all your galleries
        $(this).magnificPopup({
            delegate: '.js-zoom-gallery__item', // the selector for gallery item
            type: 'image',
            gallery: {
              enabled:true
            }
        });
    });
  }
</script>