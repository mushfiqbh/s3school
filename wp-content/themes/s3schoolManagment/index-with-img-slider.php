<?php
/**
 * Template Name: Index With Img Slider
 */
get_header(); ?>

<div id="index2" class="b-page-content with-layer-bg">
	<div class="b-layer-big">
		<div id="myCarousel" class="carousel slide" data-ride="carousel">
			<!-- Indicators
			<ol class="carousel-indicators">
				<li data-target="#myCarousel" data-slide-to="0" class="active"></li>
				<li data-target="#myCarousel" data-slide-to="1"></li>
				<li data-target="#myCarousel" data-slide-to="2"></li>
			</ol>-->

			<!-- Wrapper for slides -->
			<div class="carousel-inner">

				<?php
					$flag = 1;
					foreach ($s3sRedux['home_text_slides'] as $value) { ?>
						<div class="item <?php echo ($flag == 1) ? 'active' : ''; ?>">
							<img src="<?= $value['image'] ?>" alt="Los Angeles">
						</div>

						<?php
						$flag++;
					}
				?>
			</div>

			<!-- Left and right controls -->
			<a class="left carousel-control" href="#myCarousel" data-slide="prev">
				<span class="glyphicon glyphicon-chevron-left"></span>
				<span class="sr-only">Previous</span>
			</a>
			<a class="right carousel-control" href="#myCarousel" data-slide="next">
				<span class="glyphicon glyphicon-chevron-right"></span>
				<span class="sr-only">Next</span>
			</a>
		</div>
	</div>

	<div class="b-homepage-content-mod">
		<div class="">

			<!-- About Section -->
			<div class="homeAboutSec index2">
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

								<a href="">
									<div class="blog-item-content newsItem">
										<h4>Monthly Notice</h4>
										<p>1 Jan 2018</p>
									</div>
								</a>

								<a href="">
									<div class="blog-item-content newsItem">
										<h4>Monthly Notice</h4>
										<p>1 Jan 2018</p>
									</div>
								</a>

								<a href="">
									<div class="blog-item-content newsItem">
										<h4>Monthly Notice</h4>
										<p>1 Jan 2018</p>
									</div>
								</a>

								<a href="">
									<div class="blog-item-content newsItem">
										<h4>Monthly Notice</h4>
										<p>1 Jan 2018</p>
									</div>
								</a>

								<a href="all-notice.php" class="btn btn-primary">MORE Notice</a>

							</div>
						</div>

						<div class="col-md-4 col-sm-4 wow slideInRight">
							<div class="b-features-column">

								<h6 class="features-column-title">
									News Update
								</h6>
								<a href="">
									<div class="blog-item-content newsItem">
										<h4>Monthly News</h4>
										<p>1 Jan 2018</p>
									</div>
								</a>

								<a href="">
									<div class="blog-item-content newsItem">
										<h4>Monthly News</h4>
										<p>1 Jan 2018</p>
									</div>
								</a>

								<a href="">
									<div class="blog-item-content newsItem">
										<h4>Monthly News</h4>
										<p>1 Jan 2018</p>
									</div>
								</a>

								<a href="">
									<div class="blog-item-content newsItem">
										<h4>Monthly News</h4>
										<p>1 Jan 2018</p>
									</div>
								</a>

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

			<div class="b-reviews testimonialBg">
				<div class="b-reviews-content">
					<div class="container">
						<div class="row">
							<div class="col-xs-12 col-sm-12 wow slideInUp">
								<!-- bxslider with custom pager -->
								<div class="reviewBxSlider">
									<?php foreach ($s3sRedux['testimonial_slides'] as $slide) {

										$pieces = explode(" ", $slide['url']);
										$url = $pieces[0];
										$rating = (int)$pieces[1];
										?>

										<div class="pager-item">
											<div class="review-item text-center">
												<div class="b-stars">
													<ul class="list-inline">
														<?php for($i = 0; $i<$rating; $i++){ if($i > 4){ break; } ?>
															<li>
																<i class="fa fa-star" aria-hidden="true"></i>
															</li>
														<?php } ?>

														<?php for($i = $rating; $i<5; $i++){ ?>
															<li>
																<i class="fa fa-star-o" aria-hidden="true"></i>
															</li>
														<?php } ?>
													</ul>
												</div>
												<h4 class="review-title">
													<?= $slide['title'] ?>
												</h4>
												<div class="review-text">
													<?= $slide['description'] ?>
												</div>
												<div class="review-author">
													<span class="pre-line"></span> <?= str_replace( ',', '', $url ); ?>
												</div>
											</div>
										</div>
									<?php } ?>

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