<?php
/**
 * Template Name: Index Main
 */
get_header(); ?>

<div class="b-page-content with-layer-bg">
	<div class="b-layer-big">
		<div class="layer-big-bg page-layer-big-bg">
			<div class="layer-content-big">
				<!-- Home slider -->
				<div class="b-home-slider-holder wow slideInUp">
					<div class="b-home-slider" data-slick='{"slidesToShow": 1, "slidesToScroll": 1, "fade": true, "speed": 1000, "autoplay": true}'>
						<?php foreach ($s3sRedux['home_text_slides'] as $slide) { ?>
							<!-- Home slide 1 -->
							<div class="home-slide">
								<div class="container">
									<div class="row">
										<div class="col-xs-10 col-xs-offset-1 col-sm-10 col-sm-offset-1 text-center">
											<div class="b-home-slider-content">
												<h2 class="main-heading">
													<?= $slide['title'] ?>
												</h2>
												<div class="home-slider-text">
													<?= $slide['description'] ?>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						<?php } ?>
					</div>
					<div class="b-slick-arrows">
						<div class="custom-slideshow-controls">
							<span id="home-slider-prev" class="slick-arrows-prev arrow-transparent"><i class="fa fa-angle-left" aria-hidden="true"></i></span>
							<span id="home-slider-next" class="slick-arrows-next arrow-transparent"><i class="fa fa-angle-right" aria-hidden="true"></i></span>
						</div>
					</div>
				</div>
				<!-- END Home slider -->
			</div>
		</div>
	</div>
	<div class="b-homepage-content-mod">
		<div class="">
			<!-- Home Features -->
			<div class="b-home-features">
				<div class="b-features-columns-holder">
					<div class="container">
						<div class="row equal">
							<div class="col-xs-12 col-sm-4 col-md-4 wow slideInRight">
								<div class="b-features-column">
									<div class="features-column-icon">
										<a href="routine.php"><i class="fa fa-book" aria-hidden="true"></i></a>
									</div>
									<h6 class="features-column-title">
										<a href="routine.php">Routine</a>
									</h6>
									<div class="features-column-text">
										Lorem Ipsum is simply dummy text
									</div>
								</div>
							</div>
							<div class="col-xs-12 col-sm-4 col-md-4 wow fadeInUp">
								<div class="b-features-column even-features-column">
									<div class="features-column-icon">
										<a href="results.php"><i class="fa fa-trophy" aria-hidden="true"></i></a>
									</div>
									<h6 class="features-column-title">
										<a href="results.php">Result</a>
									</h6>
									<div class="features-column-text">
										Lorem Ipsum is simply dummy text
									</div>
								</div>
								<div class="page-arrow hidden-xs">
									<i class="fa fa-angle-right" aria-hidden="true"></i>
								</div>
							</div>
							<div class="col-xs-12 col-sm-4 col-md-4 wow slideInLeft">
								<div class="b-features-column">
									<div class="features-column-icon">
										<a href="apply.html"><i class="fa fa-users" aria-hidden="true"></i></a>
									</div>
									<h6 class="features-column-title">
										<a href="apply.php">Apply Online</a>
									</h6>
									<div class="features-column-text">
										Lorem Ipsum is simply dummy text
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- About Section -->
			<div class="homeAboutSec">
				<!-- Title services block -->
				<div class="b-about-additional">
					<div class="container">
						<div class="row">
							<div class="col-xs-12 col-sm-6 col-md-5 text-center wow slideInLeft">
								<div class="about-additional-img">
									<img width="458" src="<?= $s3sRedux['home_about_img']['url']; ?>" class="img-responsive" alt="/">
								</div>
							</div>
							<div class="col-xs-12 col-sm-6 col-md-6 col-md-offset-1  wow slideInRight">
								<div class="about-additional-content">
									<h3 class="inherit-title"><b>About Us</b></h3>
									<div class="about-additional-text">

										<?php $the_slug = 'about-us';
											$args=array(
											  'name' => $the_slug,
											  'post_type' => 'page'
											);
											$my_posts = get_posts($args);
											if( $my_posts ) {
												echo $my_posts[0]->post_content;
											}
										?>
									</div>
									<a href="about.php" class="btn btn-primary">
										More information
									</a>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<div class="homeYouCan b-about-tabs page-layer-bg2">
				<div class="container">
					<div class="row">
						<div class="col-md-4 col-sm-4 wow slideInLeft">

							<div class="b-features-column">
								<div class="features-column-icon">
									<img alt="/" src="<?= $s3sRedux['homeHeadmasterImg']['url']; ?>" class="img-responsive img-circle">
								</div>
								<h6 class="features-column-title">
									Head Master Speech
								</h6>
								<div class="features-column-text">
									<?= $s3sRedux['homeHeadmaster']; ?>
								</div>
							</div>

						</div>

						<div class="col-md-4 col-sm-4  wow slideInUp">
							<div class="b-features-column">

								<h6 class="features-column-title">
									Notice Board
								</h6>

								<?php

									$args = [
									  'post_status'   	=> 'publish',
									  'category_name' 	=> 'latest-notice',
									  'posts_per_page'  => '4'
									];
									$the_query = new WP_Query( $args );
									if( $the_query->have_posts() ) {
										while ( $the_query->have_posts() ) {
											$the_query->the_post();
											?>
											<a href="<?= the_permalink(); ?>">
												<div class="blog-item-content newsItem">
											    <h4><?php the_title(); ?></h4>
											    <p><?= get_post_time('j M Y. h:i a', true) ?></p>
												</div>
											</a>
											<?php
										}
									}
								?>

								<a href="all-notice.php" class="btn btn-primary">MORE Notice</a>

							</div>
						</div>

						<div class="col-md-4 col-sm-4 wow slideInRight">
							<div class="b-features-column">

								<h6 class="features-column-title">
									News Update
								</h6>
								<?php

									$args = [
									  'post_status'   => 'publish',
									  'category_name' => 'latest-news',
									  'posts_per_page'  => '4'
									];
									$the_query = new WP_Query( $args );
									if( $the_query->have_posts() ) {
										while ( $the_query->have_posts() ) {
											$the_query->the_post();
											?>
											<a href="<?= the_permalink(); ?>">
												<div class="blog-item-content newsItem">
											    <h4><?php the_title(); ?></h4>
											    <p><?= get_post_time('j M Y. h:i a', true) ?></p>
												</div>
											</a>
											<?php
										}
									}
								?>

								<a href="all-news.php" class="btn btn-primary">MORE News</a>

							</div>
						</div>

					</div>
				</div>
			</div>

			<!-- Count Section -->
			<div class="b-about">
				<div class="map-bg">
					<div class="container">
						<div class="row">
							<!-- Info columns -->
							<div class="b-progress-list col-xs-12 col-sm-12">
								<div class="row equal">
									<div class="b-info-column col-xs-12 col-sm-4 wow slideInLeft">
										<div class="b-progress-list__item clearfix">
											<span data-percent="<?= $s3sRedux['countLeftNumber']; ?>" class="b-progress-list__percent js-chart">
												<span class="js-percent"><?= $s3sRedux['countLeftNumber']; ?></span>
												<canvas height="0" width="0"></canvas>
											</span>
										</div>
										<h6 class="info-column-title">
											<?= $s3sRedux['countLeftTitle']; ?>
										</h6>
										<div class="info-column-text">
											<p>
												<?= $s3sRedux['countLeftDesc']; ?>
											</p>
										</div>
									</div>
									<div class="b-info-column col-xs-12 col-sm-4 wow slideInUp">
										<div class="b-progress-list__item clearfix">
											<span data-percent="<?= $s3sRedux['countMidNumber']; ?>" class="b-progress-list__percent js-chart">
												<span class="js-percent"><?= $s3sRedux['countMidNumber']; ?></span>
												<canvas height="0" width="0"></canvas>
											</span>
										</div>
										<h6 class="info-column-title">
											<?= $s3sRedux['countMidTitle']; ?>
										</h6>
										<div class="info-column-text">
											<p>
												<?= $s3sRedux['countMidDesc']; ?>
											</p>
										</div>
									</div>
									<div class="b-info-column col-xs-12 col-sm-4 wow slideInRight">
										<div class="b-progress-list__item clearfix">
											<span data-percent="<?= $s3sRedux['countRightNumber']; ?>" class="b-progress-list__percent js-chart">
												<span class="js-percent"><?= $s3sRedux['countRightNumber']; ?></span>
												<canvas height="0" width="0"></canvas>
											</span>
										</div>
										<h6 class="info-column-title">
											<?= $s3sRedux['countRightTitle']; ?>
										</h6>
										<div class="info-column-text">
											<p>
												<?= $s3sRedux['countRightDesc']; ?>
											</p>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

			<!-- You can Find Section -->
			<div class="homeYouCan b-about-tabs page-layer-bg2">
				<div class="container wow slideInUp">
					<div class="row">
						<div class="col-xs-12 col-sm-12 text-center">
							<h3 class="tabs-title">
								<b>You Can Find</b>
							</h3>
						</div>
						<div class="col-xs-12 col-sm-4 col-md-3 wow slideInLeft">
							<div class="b-features-column">
								<div class="features-column-icon">
									<i class="fa fa-calendar" aria-hidden="true"></i>
								</div>
								<h6 class="features-column-title">
									Events
								</h6>
								<div class="features-column-text">
									Lorem Ipsum is simply dummy text
								</div>
							</div>
						</div>
						<div class="col-xs-12 col-sm-4 col-md-3 wow slideInUp">
							<div class="b-features-column">
								<div class="features-column-icon">
									<i class="fa fa-user" aria-hidden="true"></i>
								</div>
								<h6 class="features-column-title">
									Teachers
								</h6>
								<div class="features-column-text">
									Lorem Ipsum is simply dummy text
								</div>
							</div>
						</div>
						<div class="col-xs-12 col-sm-4 col-md-3 wow slideInUp">
							<div class="b-features-column">
								<div class="features-column-icon">
									<i class="fa fa-users" aria-hidden="true"></i>
								</div>
								<h6 class="features-column-title">
									Staffs
								</h6>
								<div class="features-column-text">
									Lorem Ipsum is simply dummy text
								</div>
							</div>
						</div>
						<div class="col-xs-12 col-sm-4 col-md-3 wow slideInRight">
							<div class="b-features-column">
								<div class="features-column-icon">
									<i class="fa fa-image" aria-hidden="true"></i>
								</div>
								<h6 class="features-column-title">
									Gallery
								</h6>
								<div class="features-column-text">
									Lorem Ipsum is simply dummy text
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>

            
		</div>
	</div>
</div>

<?php get_footer(); ?>