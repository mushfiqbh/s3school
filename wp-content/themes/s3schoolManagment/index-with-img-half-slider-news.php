<?php
/**
 * Template Name: Index Half Img News
 */
get_header(); ?>


<div id="index2" class="b-page-content with-layer-bg">
  <div class="b-layer-big container" id="halfSlider">
    <div class="row">
      <div class="col-md-8">
        <div id="myCarousel" class="carousel slide" data-ride="carousel">
          <div class="carousel-inner">
            <?php foreach ($s3sRedux['home_text_slides'] as $key => $value) { ?>
            <div class="item <?php echo ($key == 0) ? 'active' : ''; ?>">
              <img src="<?= $value['image'] ?>" alt="Los Angeles">
            </div>
            <?php } ?>
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
      <div class="col-md-4 sliderRight">
        <div class="">
          <h4 class="features-column-title">সর্বশেষ সংবাদ</h4>
          <div class="letestNewsDiv">
            <?php
              $args = [
                'post_status'   => 'publish',
                'category_name' => 'latest-news',
                'posts_per_page'  => '5'
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
              
              wp_reset_postdata();
              
            ?>
          </div>
        </div>
      </div>
    </div>
  </div>
  
  <div class="latestNewsMarque">
    <div class="container">
      <div class="title">সাম্প্রতিক :</div>
      <div class="marque">
        <marquee>
          <?php
            $args = [
              'post_status'   => 'publish',
              'category_name' => 'latest-news',
              'posts_per_page'  => '4'
            ];
            
            $the_query = new WP_Query( $args );
            
            if( $the_query->have_posts() ) {
            	while ( $the_query->have_posts() ) {
            		echo " ** ";
            		$the_query->the_post();
            		echo wp_strip_all_tags(the_title());
            	}
            }
            wp_reset_postdata();
          ?>
        </marquee>
      </div>
    </div>
  </div>

  
  <!-- About Section -->
  <div class="homeAboutSec index2 hidden">
    <!-- Title services block -->
    <div class="b-about-additional">
      <div class="container">
        <div class="row">
          <div class="col-xs-12 col-sm-4 col-md-3 text-center wow slideInLeft">
            <div class="aboutLeft">
              <div class="sliderRight">
                <img class="img-responsive" src="<?= get_template_directory_uri() ?>/img/s3soft.jpg"><br>
                <a href="<?= home_url("student-search"); ?>">
                  <div class="blog-item-content newsItem">
                    <i class="fa fa-user" aria-hidden="true"></i> Search Student
                  </div>
                </a>
                <a href="<?= home_url("routine"); ?>">
                  <div class="blog-item-content newsItem">
                    <i class="fa fa-users" aria-hidden="true"></i> Routine
                  </div>
                </a>
                <a href="<?= home_url("result"); ?>">
                  <div class="blog-item-content newsItem">
                    <i class="fa fa-trophy" aria-hidden="true"></i> Result
                  </div>
                </a>
                <a href="<?= home_url("apply-online"); ?>">
                  <div class="blog-item-content newsItem">
                    <i class="fa fa-users" aria-hidden="true"></i> Apply Online
                  </div>
                </a>
                <a href="<?= home_url("teachers-staff"); ?>">
                  <div class="blog-item-content newsItem">
                    <i class="fa fa-users" aria-hidden="true"></i> Our Teachers
                  </div>
                </a>
                <a href="<?= home_url("teachers-staff"); ?>">
                  <div class="blog-item-content newsItem">
                    <i class="fa fa-users" aria-hidden="true"></i> Our Staffs
                  </div>
                </a>
              </div>
            </div>
          </div>
          <div class="col-xs-12 col-sm-7 col-md-9  wow slideInRight">
            <div class="about-additional-content">
              <h3 class="inherit-title"><b><?= $s3sRedux['aboutTitelText']; ?></b></h3>
              <div class="about-additional-text text-justify">
                <?php $the_slug = 'about-us';
                  s3LimitText($s3sRedux['aboutUsText'], $s3sRedux['aboutUsTextLimit']);
                  
                  ?>
              </div>
              <?php if(!empty($s3sRedux['aboutUsMoreBtn'])){ ?>
              <a href="<?= home_url() ?>/speech?cont=about" class="btn btn-primary pull-right">
              <?= $s3sRedux['aboutUsMoreBtn'] ?>
              </a>
              <?php } ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="sceachSec homeYouCan b-about-tabs page-layer-bg2">
    <div class="container">
      <div class="row">
        <!--<div class="col-md-9 col-sm-9 speech">-->
          <!--<div class="row">-->
            <!-- Headmaster -->
            <div class="col-md-4 col-sm-4 wow slideInLeft">
              <div class="b-features-column">
                <div class="features-column-icon">
                  <img width="80" alt="/" src="<?= $s3sRedux['homeHeadmasterImg']['url']; ?>" class="img-responsive img-circle center-block">
                  <h6 class="features-column-title">
                    <?= $s3sRedux['homeHeadmasterTitle']; ?>
                  </h6>
                </div>
                <div class="features-column-text text-justify">
                  <?php 
                    s3LimitText($s3sRedux['homeHeadmaster'], $s3sRedux['headmasterTextLimit'])
                    
                    ?>
                </div>
                <?php if(!empty($s3sRedux['headmasterMoreBtn'])){ ?>
                <a href="<?= home_url() ?>/speech?cont=headmaster" class="btn btn-primary">
                <?= $s3sRedux['headmasterMoreBtn'] ?>
                </a>
                <?php } ?>
              </div>
            </div>
             <!-- about us -->
            <div class="col-md-4 col-sm-4 wow slideInUp">
              <div class="b-features-column">
                <div class="features-column-icon">
                    <img width="80" alt="" src="<?= $s3sRedux['instLogo']['url']; ?>" class="img-responsive img-circle center-block">
                  <h6 class="features-column-title">
                    <?= $s3sRedux['aboutTitelText']; ?>
                  </h6>
                </div>
                <div class="features-column-text text-justify">
                  <?php 
                    s3LimitText($s3sRedux['aboutUsText'], $s3sRedux['chairmanTextLimit']);
                    
                    ?>
                </div>
                 <?php if(!empty($s3sRedux['aboutUsMoreBtn'])){ ?>
              <a href="<?= home_url() ?>/speech?cont=about" class="btn btn-primary">
              <?= $s3sRedux['aboutUsMoreBtn'] ?>
              </a>
              <?php } ?>
              </div>
            </div>
            <!-- Chairman -->
            <div class="col-md-4 col-sm-4 wow slideInUp">
              <div class="b-features-column">
                <div class="features-column-icon">
                  <img width="80" alt="/" src="<?= $s3sRedux['homeChairmanImg']['url']; ?>" class="img-responsive img-circle center-block">
                  <h6 class="features-column-title">
                    <?= $s3sRedux['homeChairmanTitle']; ?>
                  </h6>
                </div>
                <div class="features-column-text text-justify">
                  <?php 
                    s3LimitText($s3sRedux['homeChairman'], $s3sRedux['chairmanTextLimit'])
                    
                    ?>
                </div>
                <?php if(!empty($s3sRedux['chairmanMoreBtn'])){ ?>
                <a href="<?= home_url() ?>/speech?cont=chairman" class="btn btn-primary">
                <?= $s3sRedux['chairmanMoreBtn'] ?>
                </a>
                <?php } ?>
              </div>
            </div>
          <!--</div>-->
        <!--</div>-->
        <!--<div class="col-md-3 col-sm-3  wow slideInRight">-->
        <!--  <div class="b-features-column">-->
        <!--    <div class="usefullinlk features-column-text">-->
        <!--      <h3>জরুরী লিংকসমুহ</h3>-->
        <!--      <ul>-->
        <!--        <li><a href="http://www.sylhetboard.gov.bd/" target="_blank">Sylhet Education Board </a></li>-->
        <!--        <li><a href="http://www.jalalabad24.com/" target="_blank">Jalalabad24</a></li>-->
        <!--        <li><a href="http://www.dshe.gov.bd/" target="_blank"> DSHE </a></li>-->
        <!--        <li><a href="http://banbeis.gov.bd/" target="_blank"> BANBEIS </a></li>-->
        <!--        <li><a href="http://www.bangabhaban.gov.bd/" target="_blank">President's Office </a></li>-->
        <!--        <li><a href="http://www.moedu.gov.bd/" target="_blank"> Ministry of Education </a></li>-->
        <!--        <li><a href="http://www.pmo.gov.bd/" target="_blank">Prime Minister's Office </a></li>-->
        <!--        <li><a href="http://www.mopme.gov.bd/" target="_blank"> Primary and Mass Education </a></li>-->
        <!--        <li><a href="http://www.ugc.gov.bd/" target="_blank"> UGC </a></li>-->
        <!--        <li><a href="http://www.sust.edu/" target="_blank"> SUST </a></li>-->
        <!--        <li><a href="http://www.du.ac.bd/" target="_blank">Dhaka University </a></li>-->
        <!--        <li><a href="http://www.educationboardresults.gov.bd" target="_blank">Education Board Result</a></li>-->
        <!--        <li><a href="http://www.ebook.gov.bd/" target="_blank">E-Book </a></li>-->
        <!--        <li><a href="https://www.teachers.gov.bd/" target="_blank">Teachers Portal </a></li>-->
        <!--        <li><a href="http://www.bangladesh.gov.bd" target="_blank"> BD National Portal </a></li>-->
        <!--        <li><a href="http://www.forms.gov.bd/" target="_blank"> Forms of BD Govt. </a></li>-->
        <!--        <li><a href="http://www.sylhet.gov.bd/" target="_blank">Sylhet District Portal </a></li>-->
        <!--      </ul>-->
        <!--    </div>-->
        <!--  </div>-->
        <!--</div>-->
      </div>
    </div>
  </div>
  
  <!-- counter -->
  <div class="" style="background-color: #04466c;padding: 40px; text-align: center;color: #fff;">
    <h3 class="tabs-title">
      <b>
        Student Statistics
      </b>
    </h3>
    <div class="container wow slideInUp">
      <div class="counter-container">
         <div class="counter">
            Nursery <br>  
        </div>
        <div class="counter">
            One <br>  
        </div>
        <div class="counter">
            Two <br>  
        </div>
        <div class="counter">
            Three <br>  
        </div>
        <div class="counter">
            Four <br> 
        </div>
           <div class="counter">
            Five <br> 
        </div>
        <div class="counter">
            Six <br>  
        </div>
        <div class="counter">
            Seven <br>  
        </div>
        <div class="counter">
            Eight <br>  
        </div>
        <div class="counter">
            Nine <br>  
        </div>
        <div class="counter">
            Ten <br> 
        </div>
    </div>
    </div>
  </div>
  <!-- counter end-->

  
  <!-- result student teacher links starts-->
  <div style="background: #473399; padding:50px 0;margin-top: 60px;" class="animated fadeInUpBig" data-wow-duration="1000ms" data-wow-delay="500ms">
		<div class="container">
			<div class="row" style="margin-top: 20px;">
				<div class="col-md-12" style="padding: 0;">
					<div class="col-md-3 col-xs-6">
						<div class="colorBox">
							<a href="<?= home_url("student-search"); ?>" class="colorBoxLink bgGreen">
								<div align="center">
									<span><i class="fa fa-users" aria-hidden="true"></i></span> <p> Students </p>
								</div>
							</a>
						</div>
					</div>
					
					<div class="col-md-3 col-xs-6">
						<div class="colorBox">
							<a href="<?= home_url("teachers-staff"); ?>" class="colorBoxLink bgOrange">
								<div align="center">
									<span><i class="fa fa-male" aria-hidden="true"></i></span> <p> Teachers </p>
								</div>
							</a>
						</div>
					</div>
					
					<div class="col-md-3 col-xs-6">
						<div class="colorBox">
							<a href="#" class="colorBoxLink bgBlue">
								<div align="center">
									<span><i class="fa fa-check" aria-hidden="true"></i></span> <p> Attendance </p>
								</div>
							</a>
						</div>
					</div>
					
					<div class="col-md-3 col-xs-6">
						<div class="colorBox">
							<a href="<?= home_url("result"); ?>" class="colorBoxLink bgRed exam_overall_showing" >
								<div align="center">
									<span><i class="fa fa-bolt" aria-hidden="true"></i></span> <p> Result </p>
								</div>
							</a>
						</div>
					</div>
					
					<div class="div_separator"> </div>
					
					<div class="col-md-3 col-xs-6">
						<div class="colorBox">
							<a href="<?= home_url("routine"); ?>" class="colorBoxLink bgGreen">
								<div align="center">
									<span><i class="fa fa-bell"></i></span> <p> Routine </p>
								</div>
							</a>
						</div>
					</div>
					
					<div class="col-md-3 col-xs-6">
						<div class="colorBox">
							<a href="#" class="colorBoxLink bgOrange">
								<div align="center">
									<span><i class="fa fa-book"></i></span> <p> Syllabus </p>
								</div>
							</a>
						</div>
					</div>
					
					<div class="col-md-3 col-xs-6">
						<div class="colorBox">
							<a href="<?= home_url("academic-calender"); ?>" class="colorBoxLink bgBlue">
								<div align="center">
									<span><i class="fa fa-calendar"></i></span> <p> Academic Calendar </p>
								</div>
							</a>
						</div>
					</div>
					
					<div class="col-md-3 col-xs-6">
						<div class="colorBox">
							<a href="<?= home_url("gallery"); ?>" class="colorBoxLink bgRed">
								<div align="center">
									<span><i class="fa fa-camera"></i></span> <p> Photo Gallery </p>
								</div>
							</a>
						</div>
					</div>
					
					<div class="div_separator"> </div>
					
					<div class="col-md-3 col-xs-6">
						<div class="colorBox">
							<a href="#" class="colorBoxLink bgGreen">
								<div align="center">
									<span><i class="fa fa-download"></i></span> <p> Download </p>
								</div>
							</a>
						</div>
					</div>
					
					<div class="col-md-3 col-xs-6">
						<div class="colorBox">
							<a href="<?= home_url("latest-news"); ?>" class="colorBoxLink bgOrange">
								<div align="center">
									<span><i class="fa fa-bell"></i></span> <p> News </p>
								</div>
							</a>
						</div>
					</div>
					
					<div class="col-md-3 col-xs-6">
						<div class="colorBox">
							<a href="<?= home_url("latest-notice"); ?>" class="colorBoxLink bgBlue">
								<div align="center">
									<span><i class="fa fa-quote-left"></i></span> <p> Notice </p>
								</div>
							</a>
						</div>
					</div>
					
					<div class="col-md-3 col-xs-6">
						<div class="colorBox">
							<a href="#" class="colorBoxLink bgRed">
								<div align="center">
									<span><i class="fa fa-bell"></i></span> <p> Career Opportunity </p>
								</div>
							</a>
						</div>
					</div>
				</div>
				
				<div class="col-md-4 animated fadeInUpBig hidden" data-wow-duration="1000ms" data-wow-delay="700ms" style="float: left;">
					<div class="panel panel-primary" style="padding: 10px; border: none; float:left; width: 100%;">
						<div class="panel-heading" style="background: #014984;">
							<h3 class="panel-title align_center"> 
								<a style="color: #FFF; background: none;" href="#"><i class="fa fa-quote-left fa-lg" style="color: #FFF;"></i>&nbsp;&nbsp; <span style="font-size:18px;">Notice Board </span></a>
							</h3>
						</div>
						
						<div class="panel-body" style="padding: 5px;">
							<ul class="demo1" style="overflow-y: hidden; height: 204px; min-height: 200px;">
																				
																							
																							
																							
																							
																							
																							
																		<li style="" class="news-item-list_bar2">
													<a style="border-left: 3px solid red; padding-left: 10px;" href="#">
														<span class="counterSpan" style="display: none;"> <i class="fa fa-check"></i> </span>
														6-10 - Candidate  2022 Assinemnt (5th Week)													</a>
													
													<span style="float:left; width: 100%; text-align: right; margin-top: 5px; color: #A0A0A0; font-size: 12px;">
														08 Mar, 2022 
													</span>
												</li><li style="" class="news-item-list_bar2">
													<a style="border-left: 3px solid red; padding-left: 10px;" href="#">
														<span class="counterSpan" style="display: none;"> <i class="fa fa-check"></i> </span>
														SSC-2022 Candidate Assignment (14th Week)													</a>
													
													<span style="float:left; width: 100%; text-align: right; margin-top: 5px; color: #A0A0A0; font-size: 12px;">
														08 Mar, 2022 
													</span>
												</li><li style="" class="news-item-list_bar2">
													<a style="border-left: 3px solid red; padding-left: 10px;" href="#">
														<span class="counterSpan" style="display: none;"> <i class="fa fa-check"></i> </span>
														Revised Short Syllabus for SSC Examination 2022(Bangla 2nd paper,English 1st and 2nd paper)													</a>
													
													<span style="float:left; width: 100%; text-align: right; margin-top: 5px; color: #A0A0A0; font-size: 12px;">
														03 Mar, 2022 
													</span>
												</li><li style="" class="news-item-list_bar2">
													<a style="border-left: 3px solid red; padding-left: 10px;" href="#">
														<span class="counterSpan" style="display: none;"> <i class="fa fa-check"></i> </span>
														51th International Letter Writing Competition-2022													</a>
													
													<span style="float:left; width: 100%; text-align: right; margin-top: 5px; color: #A0A0A0; font-size: 12px;">
														02 Mar, 2022 
													</span>
												</li><li style="display:none;" class="news-item-list_bar2">
													<a style="border-left: 3px solid red; padding-left: 10px;" href="#">
														<span class="counterSpan" style="display: none;"> <i class="fa fa-check"></i> </span>
														NOC													</a>
													
													<span style="float:left; width: 100%; text-align: right; margin-top: 5px; color: #A0A0A0; font-size: 12px;">
														15 Sep, 2022 
													</span>
												</li><li style="display:none;" class="news-item-list_bar2">
													<a style="border-left: 3px solid red; padding-left: 10px;" href="#">
														<span class="counterSpan" style="display: none;"> <i class="fa fa-check"></i> </span>
														SSC-2022 Candidate Assignment (15th Week)													</a>
													
													<span style="float:left; width: 100%; text-align: right; margin-top: 5px; color: #A0A0A0; font-size: 12px;">
														12 Mar, 2022 
													</span>
												</li><li style="display:none;" class="news-item-list_bar2">
													<a style="border-left: 3px solid red; padding-left: 10px;" href="#">
														<span class="counterSpan" style="display: none;"> <i class="fa fa-check"></i> </span>
														SSC-2022 Candidate Assignment (13th Week)													</a>
													
													<span style="float:left; width: 100%; text-align: right; margin-top: 5px; color: #A0A0A0; font-size: 12px;">
														08 Mar, 2022 
													</span>
												</li></ul>	
						</div>  <!------ END OF CLASS PANEL-BODY -------->
						
						<div class="panel-footer"> <a href="#" id="ReadMoreRight"> More... </a> <ul class="pagination" style="margin: 0px;"><li><a href="#" class="prev"><span class="fa fa-chevron-down"></span></a></li><li><a href="#" class="next"><span class="fa fa-chevron-up"></span></a></li></ul><div class="clearfix"></div></div>
					</div>
				</div>
			</div> <!-------  END OF DIV ROW ------>	
		</div> <!-------  END OF DIV CONTAINER ------>	
	</div>
  <!--result, student, teacher links ends-->

  

<section style='background: #dbb937; padding:50px 0;'>
		<div class="container">
		   <div class="row">
				<div class="col-md-12">
					<h1 class='large_heading' style='color: #624183;'> Important Links </h1>
					<!--<p class='headingPara'> Some important links </p>-->
				</div>
				
        
              <div class="col-md-3"> 
								<a class='imp_links' href='http://www.sylhetboard.gov.bd' target='_blank'> Sylhet Education Board </a>
							</div>
              <div class="col-md-3"> 
								<a class='imp_links' href='http://www.sylhetdiv.gov.bd' target='_blank'> Sylhet Divisional Portal </a>
							</div>
							<div class="col-md-3"> 
								<a class='imp_links' href='http://www.moedu.gov.bd' target='_blank'> Ministry of Education </a>
							</div>
												
								<div class="col-md-3"> 
								<a class='imp_links' href='http://www.sib.gov.bd' target='_blank'> Secondary Education Department </a>
							</div>
							<div class="col-md-3"> 
								<a class='imp_links' href='http://www.bangladesh.gov.bd' target='_blank'> BD National Portal </a>
							</div>
              <div class="col-md-3"> 
								<a class='imp_links' href='http://www.bdjobs.com' target='_blank'> BD Jobs </a>
							</div>
              <div class="col-md-3"> 
								<a class='imp_links' href='http://www.a2i.gov.bd' target='_blank'> Access to Information (a2i) </a>
							</div>
              <div class="col-md-3"> 
								<a class='imp_links' href='http://www.dshe.gov.bd' target='_blank'> DSHE </a>
							</div>
												
									</div>
		</div>  <!-------- END OF CLASS CONTAINER ---------->
	</section>
	
  <!-- Gallery Section -->
  <div class="gallerySection" style="background:#211c3c">
    <div class="container">
      <h3 class="tabs-title text-center">
        <b>Gallery</b>
      </h3>
      <?php
        $args = [
          'post_status'   => 'publish',
          'category_name' => 'gallery',
          'posts_per_page'  => '12'
        ];

        $gallery = new WP_Query( $args );

        if( $gallery->have_posts() ) {
        	echo '<div class="owl-carousel">';
        	while ( $gallery->have_posts() ) {
        		$gallery->the_post();
        		if ( has_post_thumbnail() ) {
          		?>
		          <div class="item" title="<?= the_title() ?>">
		            <?= the_post_thumbnail( ); ?>
		          </div>
		          <?php
          	}
          }
        	echo '</div>';
        }else{
        	echo "<h3 class='text-center text-danger'>Gallery Empty</h3>";
        }
        
        wp_reset_postdata();
        
      ?>
      <div class="text-center ">
        <a class="btn btn-primary" href="<?= home_url('gallery') ?>">See More</a>
      </div>
    </div>
  </div>
  
<style>
  .counter-container {
            display: flex; /* Display counters in a row */
            flex-wrap: wrap;
            align-items: center; /* Center align counters vertically */
        justify-content: center; 
        margin: 0 -10px;
        }

        .counter {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background-color: #3498db;
            color: white;
            text-align: center;
            font-size: 24px;
            margin:  10px; /* Add margin to separate counters */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
         @media (max-width: 768px) {
            .counter {
                font-size: 18px;  
                width: 100px;
                height: 100px;
            }
        }
</style>
<!-- counter -->
<div class="" style="background-color: #4b177ed9;padding: 40px; text-align: center;color: #fff;">
  <h3 class="tabs-title">
    <b>
      Organization Statistics
    </b>
  </h3>
  <div class="container wow slideInUp">
    <div class="counter-container">
      <div class="counter">
        Class<br> 11  
      </div>
        <div class="counter">
            Student  <br>  542
      </div>
      <div class="counter">
          Teacher <br>  19
      </div>
      <div class="counter">
          Staff <br> 5
      </div>
  </div>
  </div>
</div>
<!--counter ends-->
 
</div>


<style>
		.contact-w3ls {
			background: #226081;
			position: relative;
			padding-top: 0!important;
			margin-top22: 6.3em;
			margin-top: 150px;
		}
			.contact-w3ls:before {
				content: "";
				position: absolute;
				top: -106px;
				border-width: 0 882px 106px 0;
				border-style: solid;
				border-color: transparent transparent #f8b239 #f8b239;
				display: block;
				width: 0;
			}
			
			.contact-w3ls:after {
			content: "";
			position: absolute;
			top: -120px;
			right: 0;
			border-width: 0px 1904px 120px 0;
			border-style: solid;
			border-color: transparent transparent #226081 #226081;
			display: block;
			transform: rotateY(180deg);
			-webkit-transform: rotateY(180deg);
			-o-transform: rotateY(180deg);
			-ms-transform: rotateY(180deg);
			-moz-transform: rotateY(180deg);
			width: 0;
			}
	</style>

	<div class='contact-w3ls hidden-sm hidden-xs' style='display22: none;'>
		<div class="contact-top-w3-agile"></div>
	</div>

	<style>
		.newsBar{
		background: #FFF;
		position: fixed;
		width: 38px;
		height: 35px;
		top: 70%;
		right: 0;
		z-index: 1000; border-left: 3px solid #0055a5; box-shadow22: 5px 2px 5px 1px #ccc;
		}
	</style>
	
	
<section style='background: #226081; padding:50px 0;'>
				<div class="container">
					<div class="row">
						<div class="col-md-6 footer_social"> 
							<p> Stay with Us </p> 
							<a href="#" target="_blank"><i class="fa fa-facebook"></i></a>&nbsp;							<a href="" target="_blank"><i class="fa fa-twitter"></i></a>&nbsp;							<a href="" target="_blank"><i class="fa fa-google-plus"></i></a>&nbsp;							<a href="" target="_blank"><i class="fa fa-youtube"></i></a>
						</div>
						
						<div class="col-md-6 footer_social">
							<span style='float: right;'>
								<p style='float: right; margin-left: 10px;'> It Partner - বর্ণমালা, ০১৬৩৩-৫১৬৪০০ </p>
								<i class="fa fa-phone"></i> 
							</span>
						</div>
					</div> <!-------  END OF DIV ROW ------>	
				</div> <!-------  END OF DIV CONTAINER ------>	
			</section>

<?php get_footer(); ?>