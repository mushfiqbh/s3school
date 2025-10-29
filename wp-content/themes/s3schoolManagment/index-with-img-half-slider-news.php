<?php

/** Template Name: Index Half Img News */

// Enqueue css styles
function my_theme_enqueue_styles()
{
  wp_enqueue_style('index', get_template_directory_uri() . '/css/index.css', array(), '1.0.0', 'all');
}
add_action('wp_enqueue_scripts', 'my_theme_enqueue_styles');



global $wpdb;
global $s3sRedux;

// Load options from sm_options table if available
$sm_opts_table = $wpdb->get_var("SHOW TABLES LIKE 'sm_options'");
if ($sm_opts_table === 'sm_options') {
  $option_rows = $wpdb->get_results(
    "SELECT option_name, option_value FROM sm_options WHERE option_name IN ('aboutTitelText', 'aboutUsText', 'aboutUsTextLimit', 'aboutUsMoreBtn', 'layout_visibility', 'headmasterSpeechTitle', 'chairmanSpeechTitle')",
    ARRAY_A
  );
  if (!empty($option_rows)) {
    foreach ($option_rows as $row) {
      $optionValue = isset($row['option_value']) ? maybe_unserialize($row['option_value']) : '';
      if (is_string($optionValue)) {
        $s3sRedux[$row['option_name']] = trim($optionValue);
      } else {
        $s3sRedux[$row['option_name']] = $optionValue;
      }
    }
  }
}

$slider_images = $wpdb->get_results('SELECT image_url FROM sm_slider_images');

$layout_visibility = isset($s3sRedux['layout_visibility']) ? json_decode($s3sRedux['layout_visibility'], true) : [];

get_header();
?>

<link rel="stylesheet" href="css/index.css" />

<!-- Latest News -->
<div class="latestNewsMarque">
  <div class="container">
    <div class="title">সাম্প্রতিক:</div>
    <div class="marque">
      <marquee onmouseover="this.stop();" onmouseout="this.start();">
        <?php
        $args = [
          'post_status' => 'publish',
          'category_name' => 'latest-news',
          'posts_per_page' => '4'
        ];

        $the_query = new WP_Query($args);

        if ($the_query->have_posts()) {
          while ($the_query->have_posts()) {
            $the_query->the_post();
            echo '<a href="' . esc_url(get_permalink()) . '">';
            echo wp_strip_all_tags(get_the_title());
            echo '</a>';
            if ($the_query->current_post + 1 < $the_query->post_count) {
              echo ' &nbsp; | &nbsp; ';
            }
          }
        }
        wp_reset_postdata();
        ?>
      </marquee>
    </div>
  </div>
</div>

<div id="index2" class="b-page-content with-layer-bg">
  <div class="b-layer-big container" id="halfSlider">
    <div class="row">
      <div class="col-md-12">
        <div id="myCarousel" class="carousel slide" data-ride="carousel">
          <div class="carousel-inner">

            <?php // foreach ($s3sRedux['home_text_slides'] as $key => $value) { 
            ?>
            <?php foreach ($slider_images as $key => $value) { ?>
              <div class="item <?php echo ($key == 0) ? 'active' : ''; ?>">
                <img src="<?= $value->image_url ?>" alt="">
                <!--<img src="<?php // $value['image_url'] 
                              ?>" alt="">-->
              </div>
            <?php } ?>
          </div>
          <!-- Left and right controls -->
          <a class="left carousel-control" href="#myCarousel" data-slide="prev">
            <span class="glyphicon glyphicon-chevron-left"></span>
            <span class="sr-only">Previous</span>
            <a class="right carousel-control" href="#myCarousel" data-slide="next">
              <span class="glyphicon glyphicon-chevron-right"></span>
            </a>
        </div>
      </div>
    </div>
  </div>

  <!-- Institute Info -->
  <?php
  global $wpdb;
  // Fetch institute info from key-value table sm_options
  $sm_opts = [];
  $table_exists = $wpdb->get_var("SHOW TABLES LIKE 'sm_options'");

  if ($table_exists === 'sm_options') {
    $option_rows = $wpdb->get_results(
      "SELECT option_name, option_value FROM sm_options WHERE option_name IN ('institute_eiin','institute_code','center_code','estd_year')",
      ARRAY_A
    );
    if (!empty($option_rows)) {
      foreach ($option_rows as $row) {
        $sm_opts[$row['option_name']] = $row['option_value'];
      }
    }
  }

  // Fallback to Redux options if sm_options is empty
  if (empty($sm_opts)) {
    $redux_options = get_option('opt_name', array());
    $sm_opts = [
      'institute_eiin' => $redux_options['institute_eiin'] ?? '',
      'institute_code' => $redux_options['institute_code'] ?? '',
      'center_code' => $redux_options['center_code'] ?? '',
      'estd_year' => $redux_options['estd_year'] ?? '',
    ];
  }

  // Normalize values (trim strings)
  foreach ($sm_opts as $k => $v) {
    if (is_string($v)) {
      $sm_opts[$k] = trim($v);
    }
  }

  $info = [
    ['EIIN', 'institute_eiin', '#007bff,#0056b3'],
    ['Institution Code', 'institute_code', '#28a745,#218838'],
    ['Center Code', 'center_code', '#20c997,#17a589'],
    ['ESTD Year', 'estd_year', '#dc3545,#b21f2d'],
  ];


  // Only keep cards that have a non-empty value
  $display_info = array_values(array_filter($info, function ($item) use ($sm_opts) {
    $key = $item[1];
    return isset($sm_opts[$key]) && $sm_opts[$key] !== '';
  }));

  $cardWidth = intval(100 / count($display_info));

  ?>

  <?php if (!empty($display_info)) : ?>
    <div class="instituteInfo" style="margin-bottom:20px;">
      <div class="container">
        <div class="row" style="display:flex;justify-content:center;gap:10px;">
          <?php foreach ($display_info as [$label, $key, $colors]) : ?>
            <div class="text-center institute-card" style="width:calc(<?= $cardWidth ?>% - 10px);background:linear-gradient(135deg,<?= $colors ?>);color:#fff;border-radius:8px;margin:5px 2px;box-shadow:0 2px 8px rgba(0,0,0,0.06);padding:15px 5px;">
              <div style="font-size:16px;font-weight:600;"><?= esc_html($label) ?></div>
              <div style="font-size:20px;font-weight:500;"><?= esc_html((string)($sm_opts[$key] ?? '')) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <style>
        @media (max-width: 767px) {
          .institute-card {
            min-width: 90vw !important;
            margin: 5px auto !important;
          }

          .instituteInfo .row {
            flex-direction: column !important;
            gap: 0 !important;
          }
        }
      </style>
    </div>
  <?php endif; ?>



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
                <a href="<?= home_url('student-search'); ?>">
                  <div class="blog-item-content newsItem">
                    <i class="fa fa-user" aria-hidden="true"></i> Search Student
                  </div>
                </a>
                <a href="<?= home_url('routine'); ?>">
                  <div class="blog-item-content newsItem">
                    <i class="fa fa-users" aria-hidden="true"></i> Routine
                  </div>
                </a>
                <a href="<?= home_url('result'); ?>">
                  <div class="blog-item-content newsItem">
                    <i class="fa fa-trophy" aria-hidden="true"></i> Result
                  </div>
                </a>
                <a href="<?= home_url('apply-online'); ?>">
                  <div class="blog-item-content newsItem">
                    <i class="fa fa-users" aria-hidden="true"></i> Apply Online
                  </div>
                </a>
                <a href="<?= home_url('teachers'); ?>">
                  <div class="blog-item-content newsItem">
                    <i class="fa fa-users" aria-hidden="true"></i> Our Teachers
                  </div>
                </a>
                <a href="<?= home_url('staffs'); ?>">
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
              <?php if (!empty($s3sRedux['aboutUsMoreBtn'])) { ?>
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


  <div class="modern-speech-section">
    <div class="container">
      <!-- About Us Card -->
      <div class="row">
        <div class="col-md-12 wow slideInUp">
          <div class="modern-feature-card">
            <div class="feature-avatar">
              <img src="<?= $s3sRedux['home_about_img']['url']; ?>" alt="About Us">
            </div>

            <!-- Section Title -->
            <div class="section-title-modern wow fadeInDown">
              <div class="title-line"></div>
              <h3 class="inherit-title"><b><?= $s3sRedux['aboutTitelText']; ?></b></h3>
            </div>

            <div class="feature-content-modern">
              <?php
              $content = preg_replace("/(\r\n|\n|\r){2,}/", "\n", $s3sRedux['aboutUsText']);
              s3LimitText(wp_kses_post(trim($content)), $s3sRedux['aboutUsTextLimit']);
              ?>
            </div>

            <br>

            <div class="text-center">
              <?php if (!empty($s3sRedux['aboutUsMoreBtn'])) { ?>
                <a href="<?= home_url() ?>/speech?cont=about" class="feature-btn-modern">
                  <?= $s3sRedux['aboutUsMoreBtn'] ?>
                  <i class="fa fa-arrow-right"></i>
                </a>
              <?php } ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Leadership Cards -->
      <div class="row" style="margin-top: 30px;">
        <!-- Headmaster -->
        <div class="col-md-6 col-sm-6 wow slideInLeft">
          <div class="modern-feature-card">
            <div class="feature-avatar">
              <img src="<?= $s3sRedux['homeHeadmasterImg']['url']; ?>" alt="<?= $s3sRedux['homeHeadmasterTitle']; ?>">
            </div>
            <h3 class="feature-title-modern">
              <?= $s3sRedux['headmasterSpeechTitle'] ?>
            </h3>
            <div class="feature-content-modern">
              <?php
              $content = preg_replace("/(\r\n|\n|\r){2,}/", "\n", $s3sRedux['homeHeadmaster']);
              s3LimitText(wp_kses_post(nl2br(trim($content))), $s3sRedux['headmasterTextLimit']);
              ?>
            </div>
            <p style="padding:10px 0;float:right;">- <?= $s3sRedux['homeHeadmasterTitle']; ?></p>

            <br>
            <div class="text-center" style="clear:both;">
              <?php if (!empty($s3sRedux['headmasterMoreBtn'])) { ?>
                <a href="<?= home_url() ?>/speech?cont=headmaster" class="feature-btn-modern">
                  <?= $s3sRedux['headmasterMoreBtn'] ?>
                  <i class="fa fa-arrow-right"></i>
                </a>
              <?php } ?>
            </div>
          </div>
        </div>

        <!-- Chairman -->
        <div class="col-md-6 col-sm-6 wow slideInRight">
          <div class="modern-feature-card">
            <div class="feature-avatar">
              <img src="<?= $s3sRedux['homeChairmanImg']['url']; ?>" alt="<?= $s3sRedux['homeChairmanTitle']; ?>">
            </div>
            <h3 class="feature-title-modern">
              <?= $s3sRedux['chairmanSpeechTitle'] ?>
            </h3>
            <div class="feature-content-modern">
              <?php
              s3LimitText(wp_kses_post(nl2br($s3sRedux['homeChairman'])), $s3sRedux['chairmanTextLimit']);
              ?>
            </div>
            <p style="padding:10px 0;float:right;">- <?= $s3sRedux['homeChairmanTitle']; ?></p>

            <br>
            <div class="text-center" style="clear:both;">
              <?php if (!empty($s3sRedux['chairmanMoreBtn'])) { ?>
                <a href="<?= home_url() ?>/speech?cont=chairman" class="feature-btn-modern">
                  <?= $s3sRedux['chairmanMoreBtn'] ?>
                  <i class="fa fa-arrow-right"></i>
                </a>
              <?php } ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>


  <!-- Statistics Section -->
  <div class="statisticsSection" style="background:#f5f5f5;padding: 60px 0;">
    <style>
      .statisticsSection {
        background: linear-gradient(135deg, #e8f1ff 0%, #f0fff9 100%) !important;
      }

      .statistics-item {
        transition: transform .3s ease, box-shadow .3s ease;
        background: #fff;
        border-radius: 8px;
        padding: 20px 10px;
        margin-bottom: 20px;
        box-shadow: 0 6px 18px rgba(0, 0, 0, .06)
      }

      .statistics-item:hover {
        transform: translateY(-4px);
        box-shadow: 0 10px 24px rgba(0, 0, 0, .10)
      }

      .statistics-item img {
        width: 100px;
        height: auto;
        filter: drop-shadow(0 4px 8px rgba(0, 0, 0, .15));
      }

      .statistics-item h3 {
        margin-top: 12px
      }
    </style>
    <div class="container">
      <div class="row">
        <div class="col-md-3 col-xs-6 col-sm-6 text-center wow fadeInUp" data-wow-delay=".1s">
          <div class="statistics-item">
            <img src="img/class.svg" alt="">
            <h3>
              <strong>
                <span class="stat-count" data-count="<?= (int) get_option('totalClasses', '0'); ?>">0</span>+
              </strong>
              <br>
              Classes
            </h3>
          </div>
        </div>
        <div class="col-md-3 col-xs-6 col-sm-6 text-center wow fadeInUp" data-wow-delay=".2s">
          <div class="statistics-item">
            <img src="img/student.png" alt="">
            <h3>
              <strong>
                <span class="stat-count" data-count="<?= (int) get_option('totalStudents', '0'); ?>">0</span>+
              </strong>
              <br>
              Students
            </h3>
          </div>
        </div>
        <div class="col-md-3 col-xs-6 col-sm-6 text-center wow fadeInUp" data-wow-delay=".3s">
          <div class="statistics-item">
            <img src="img/teacher.png" alt="">
            <h3>
              <strong>
                <span class="stat-count" data-count="<?= (int) get_option('totalTeachers', '0'); ?>">0</span>+
              </strong>
              <br>
              Teachers
            </h3>
          </div>
        </div>
        <div class="col-md-3 col-xs-6 col-sm-6 text-center wow fadeInUp" data-wow-delay=".4s">
          <div class="statistics-item">
            <img src="img/staff.png" alt="">
            <h3>
              <strong>
                <span class="stat-count" data-count="<?= (int) get_option('totalStaffs', '0'); ?>">0</span>+
              </strong>
              <br>
              Staffs
            </h3>
          </div>
        </div>
      </div>
    </div>
    <script>
      (function($) {
        var started = false;

        function startCounters() {
          if (started) return;
          var $sec = $('.statisticsSection');
          if (!$sec.length) return;
          var trigger = $(window).scrollTop() + $(window).height() > $sec.offset().top + 60;
          if (!trigger) return;
          started = true;
          $('.stat-count').each(function() {
            var $el = $(this);
            var target = parseInt($el.data('count'), 10) || 0;
            $({
              n: 0
            }).animate({
              n: target
            }, {
              duration: 1800,
              easing: 'swing',
              step: function(now) {
                $el.text(Math.ceil(now));
              },
              complete: function() {
                $el.text(target);
              }
            });
          });
        }
        $(window).on('load scroll', startCounters);
      })(jQuery);
    </script>
  </div>


  <!-- result student teacher links starts-->
  <div style="background: #473399; padding:50px 0;" class="animated fadeInUpBig" data-wow-duration="1000ms" data-wow-delay="500ms">
    <div class="container">
      <div class="row" style="margin-top: 20px;">
        <div class="col-md-8" style="padding: 0;">
          <div class="col-md-3 col-xs-6">
            <div class="colorBox">
              <a href="<?= home_url('student-search'); ?>" class="colorBoxLink bgGreen">
                <div align="center">
                  <span><i class="fa fa-users" aria-hidden="true"></i></span>
                  <p> Students </p>
                </div>
              </a>
            </div>
          </div>

          <div class="col-md-3 col-xs-6">
            <div class="colorBox">
              <a href="<?= home_url('teachers'); ?>" class="colorBoxLink bgOrange">
                <div align="center">
                  <span><i class="fa fa-male" aria-hidden="true"></i></span>
                  <p> Teachers </p>
                </div>
              </a>
            </div>
          </div>

          <div class="col-md-3 col-xs-6">
            <div class="colorBox">
              <a href="#" class="colorBoxLink bgBlue">
                <div align="center">
                  <span><i class="fa fa-check" aria-hidden="true"></i></span>
                  <p> Attendance </p>
                </div>
              </a>
            </div>
          </div>

          <div class="col-md-3 col-xs-6">
            <div class="colorBox">
              <a href="<?= home_url('result'); ?>" class="colorBoxLink bgRed exam_overall_showing">
                <div align="center">
                  <span><i class="fa fa-bolt" aria-hidden="true"></i></span>
                  <p> Result </p>
                </div>
              </a>
            </div>
          </div>

          <div class="div_separator"> </div>

          <div class="col-md-3 col-xs-6">
            <div class="colorBox">
              <a href="<?= home_url('routine'); ?>" class="colorBoxLink bgGreen">
                <div align="center">
                  <span><i class="fa fa-bell"></i></span>
                  <p> Routine </p>
                </div>
              </a>
            </div>
          </div>

          <div class="col-md-3 col-xs-6">
            <div class="colorBox">
              <a href="#" class="colorBoxLink bgOrange">
                <div align="center">
                  <span><i class="fa fa-book"></i></span>
                  <p> Syllabus </p>
                </div>
              </a>
            </div>
          </div>

          <div class="col-md-3 col-xs-6">
            <div class="colorBox">
              <a href="<?= home_url('academic-calender'); ?>" class="colorBoxLink bgBlue">
                <div align="center">
                  <span><i class="fa fa-calendar"></i></span>
                  <p> Academic Calendar </p>
                </div>
              </a>
            </div>
          </div>

          <div class="col-md-3 col-xs-6">
            <div class="colorBox">
              <a href="<?= home_url('gallery'); ?>" class="colorBoxLink bgRed">
                <div align="center">
                  <span><i class="fa fa-camera"></i></span>
                  <p> Photo Gallery </p>
                </div>
              </a>
            </div>
          </div>

          <div class="div_separator"> </div>

          <div class="col-md-3 col-xs-6">
            <div class="colorBox">
              <a href="#" class="colorBoxLink bgGreen">
                <div align="center">
                  <span><i class="fa fa-download"></i></span>
                  <p> Download </p>
                </div>
              </a>
            </div>
          </div>

          <div class="col-md-3 col-xs-6">
            <div class="colorBox">
              <a href="<?= home_url('latest-news'); ?>" class="colorBoxLink bgOrange">
                <div align="center">
                  <span><i class="fa fa-bell"></i></span>
                  <p> News </p>
                </div>
              </a>
            </div>
          </div>

          <div class="col-md-3 col-xs-6">
            <div class="colorBox">
              <a href="<?= home_url('latest-notice'); ?>" class="colorBoxLink bgBlue">
                <div align="center">
                  <span><i class="fa fa-quote-left"></i></span>
                  <p> Notice </p>
                </div>
              </a>
            </div>
          </div>

          <div class="col-md-3 col-xs-6">
            <div class="colorBox">
              <a href="#" class="colorBoxLink bgRed">
                <div align="center">
                  <span><i class="fa fa-bell"></i></span>
                  <p> Career Opportunity </p>
                </div>
              </a>
            </div>
          </div>
        </div>
        <div class="col-md-4 sliderRight">
          <div class="latest-news-box">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
              <h4 class="features-column-title news-header">সর্বশেষ সংবাদ</h4>
            </div>
            <div class="letestNewsDiv">
              <div class="news-scroll-content">
                <?php
                $args = [
                  'post_status' => 'publish',
                  'category_name' => 'latest-news',
                  'posts_per_page' => '5'
                ];

                $the_query = new WP_Query($args);
                if ($the_query->have_posts()) {
                  while ($the_query->have_posts()) {
                    $the_query->the_post();
                ?>
                    <a href="<?= get_permalink(); ?>">
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


        <div class="col-md-4 animated fadeInUpBig hidden" data-wow-duration="1000ms" data-wow-delay="700ms" style="float: left;">
          <div class="panel panel-primary" style="padding: 10px; border: none; float:left; width: 100%;">
            <div class="panel-heading" style="background: #014984;">
              <h3 class="panel-title align_center">
                <a style="color: #FFF; background: none;" href="#"><i class="fa fa-quote-left fa-lg" style="color: #FFF;"></i>&nbsp;&nbsp; <span style="font-size:18px;">Notice Board </span></a>
              </h3>
            </div>

            <div class="panel-body" style="padding: 5px;">
              <ul class="demo1" style="overflow-y: hidden; height: 204px; min-height: 200px;">







                <li class="news-item-list_bar2">
                  <a style="border-left: 3px solid red; padding-left: 10px;" href="#">
                    <span class="counterSpan" style="display: none;"> <i class="fa fa-check"></i> </span>
                    6-10 - Candidate 2022 Assinemnt (5th Week) </a>

                  <span style="float:left; width: 100%; text-align: right; margin-top: 5px; color: #A0A0A0; font-size: 12px;">
                    08 Mar, 2022
                  </span>
                </li>
                <li class="news-item-list_bar2">
                  <a style="border-left: 3px solid red; padding-left: 10px;" href="#">
                    <span class="counterSpan" style="display: none;"> <i class="fa fa-check"></i> </span>
                    SSC-2022 Candidate Assignment (14th Week) </a>

                  <span style="float:left; width: 100%; text-align: right; margin-top: 5px; color: #A0A0A0; font-size: 12px;">
                    08 Mar, 2022
                  </span>
                </li>
                <li class="news-item-list_bar2">
                  <a style="border-left: 3px solid red; padding-left: 10px;" href="#">
                    <span class="counterSpan" style="display: none;"> <i class="fa fa-check"></i> </span>
                    Revised Short Syllabus for SSC Examination 2022(Bangla 2nd paper,English 1st and 2nd paper) </a>

                  <span style="float:left; width: 100%; text-align: right; margin-top: 5px; color: #A0A0A0; font-size: 12px;">
                    03 Mar, 2022
                  </span>
                </li>
                <li class="news-item-list_bar2">
                  <a style="border-left: 3px solid red; padding-left: 10px;" href="#">
                    <span class="counterSpan" style="display: none;"> <i class="fa fa-check"></i> </span>
                    51th International Letter Writing Competition-2022 </a>

                  <span style="float:left; width: 100%; text-align: right; margin-top: 5px; color: #A0A0A0; font-size: 12px;">
                    02 Mar, 2022
                  </span>
                </li>
                <li style="display:none;" class="news-item-list_bar2">
                  <a style="border-left: 3px solid red; padding-left: 10px;" href="#">
                    <span class="counterSpan" style="display: none;"> <i class="fa fa-check"></i> </span>
                    NOC </a>

                  <span style="float:left; width: 100%; text-align: right; margin-top: 5px; color: #A0A0A0; font-size: 12px;">
                    15 Sep, 2022
                  </span>
                </li>
                <li style="display:none;" class="news-item-list_bar2">
                  <a style="border-left: 3px solid red; padding-left: 10px;" href="#">
                    <span class="counterSpan" style="display: none;"> <i class="fa fa-check"></i> </span>
                    SSC-2022 Candidate Assignment (15th Week) </a>

                  <span style="float:left; width: 100%; text-align: right; margin-top: 5px; color: #A0A0A0; font-size: 12px;">
                    12 Mar, 2022
                  </span>
                </li>
                <li style="display:none;" class="news-item-list_bar2">
                  <a style="border-left: 3px solid red; padding-left: 10px;" href="#">
                    <span class="counterSpan" style="display: none;"> <i class="fa fa-check"></i> </span>
                    SSC-2022 Candidate Assignment (13th Week) </a>

                  <span style="float:left; width: 100%; text-align: right; margin-top: 5px; color: #A0A0A0; font-size: 12px;">
                    08 Mar, 2022
                  </span>
                </li>
              </ul>
            </div> <!------ END OF CLASS PANEL-BODY -------->

            <div class="panel-footer"> <a href="#" id="ReadMoreRight"> More... </a>
              <ul class="pagination" style="margin: 0px;">
                <li><a href="#" class="prev"><span class="fa fa-chevron-down"></span></a></li>
                <li><a href="#" class="next"><span class="fa fa-chevron-up"></span></a></li>
              </ul>
              <div class="clearfix"></div>
            </div>
          </div>
        </div>
      </div> <!-------  END OF DIV ROW ------>
    </div> <!-------  END OF DIV CONTAINER ------>
  </div>
  <!--result, student, teacher links ends-->



  <?php
  // Fetch Important Links from sm_options table
  $important_links = [];
  $links_data = $wpdb->get_var("SELECT option_value FROM sm_options WHERE option_name = 'important_links'");

  if ($links_data) {
    $important_links = json_decode($links_data, true);
  }

  // Fallback to default links if no data in database
  if (empty($important_links)) {
    $important_links = [
      ['title' => 'Sylhet Education Board', 'url' => 'http://www.sylhetboard.gov.bd'],
      ['title' => 'Sylhet Divisional Portal', 'url' => 'http://www.sylhetdiv.gov.bd'],
      ['title' => 'Ministry of Education', 'url' => 'http://www.moedu.gov.bd'],
      ['title' => 'Secondary Education Department', 'url' => 'http://www.sib.gov.bd'],
      ['title' => 'BD National Portal', 'url' => 'http://www.bangladesh.gov.bd'],
      ['title' => 'BD Jobs', 'url' => 'http://www.bdjobs.com'],
      ['title' => 'Access to Information (a2i)', 'url' => 'http://www.a2i.gov.bd'],
      ['title' => 'DSHE', 'url' => 'http://www.dshe.gov.bd']
    ];
  }
  ?>

  <section style='background: #dbb937; padding:50px 0;'>
    <div class="container">
      <div class="row">
        <div class="col-md-12">
          <h1 class='large_heading' style='color: #624183;'> Important Links </h1>
          <!--<p class='headingPara'> Some important links </p>-->
        </div>

        <?php
        if (!empty($important_links)) {
          foreach ($important_links as $link) {
            if (!empty($link['title']) && !empty($link['url'])) {
        ?>
              <div class="col-md-3">
                <a class='imp_links' href='<?= esc_url($link['url']); ?>' target='_blank'>
                  <?= esc_html($link['title']); ?>
                </a>
              </div>
        <?php
            }
          }
        } else {
          echo '<div class="col-md-12 text-center"><p style="color: #624183;">No important links available.</p></div>';
        }
        ?>

      </div>
    </div> <!-------- END OF CLASS CONTAINER ---------->
  </section>

  <?php
  if ($layout_visibility['teachers'] === 1) {

    global $wpdb;

    $teacherRows = [];
    $teacherTable = $wpdb->get_var("SHOW TABLES LIKE 'ct_teacher'");

    if ($teacherTable === 'ct_teacher') {
      $teacherRows = $wpdb->get_results(
        "SELECT teacherid, teacherName, teacherImg, teacherDesignation, teacherPhone FROM ct_teacher WHERE status='Present' AND teacherDesignation NOT LIKE '%Lecturer%' ORDER BY teacherName ASC LIMIT 12"
      );
    }

    if ($teacherRows) {
  ?>
      <!-- Teachers Grid Section -->
      <section class="teachers-section">
        <div class="container">
          <div class="section-header">
            <h2 class="section-title">Our Teachers</h2>
            <div class="section-underline"></div>
            <p class="section-subtitle">Meet our dedicated faculty members</p>
          </div>

          <div class="teachers-grid">
            <?php
            if (!empty($teacherRows)) {
              foreach ($teacherRows as $teacher) {
                $teacherName = isset($teacher->teacherName) ? trim($teacher->teacherName) : '';
                $teacherDesignation = isset($teacher->teacherDesignation) ? trim($teacher->teacherDesignation) : '';
                $rawImage = isset($teacher->teacherImg) ? trim($teacher->teacherImg) : '';

                if (!empty($rawImage) && strpos($rawImage, 'http') !== 0) {
                  $rawImage = home_url('/') . ltrim($rawImage, '/');
                }

                $imageSrc = !empty($rawImage)
                  ? esc_url($rawImage)
                  : esc_url(get_template_directory_uri() . '/img/No_Image.jpg');
            ?>
                <a href="<?= home_url('/teachers/?t=' . $teacher->teacherid) ?>" title="View Profile">
                  <div class="teacher-card" data-aos="fade-up">
                    <div class="teacher-card-inner">
                      <div class="teacher-image-wrapper">
                        <img src="<?= $imageSrc ?>" alt="<?= esc_attr($teacherName ?: 'Teacher photo') ?>" class="teacher-image">
                      </div>
                      <div class="teacher-info">
                        <h4 class="teacher-name"><?= esc_html($teacherName) ?></h4>
                        <?php if (!empty($teacherDesignation)) : ?>
                          <p class="teacher-designation"><?= esc_html($teacherDesignation) ?></p>
                        <?php endif; ?>
                        <div class="teacher-badge">
                          <i class="fa fa-graduation-cap"></i>
                        </div>
                      </div>
                    </div>
                  </div>
                </a>
            <?php
              }
            }
            ?>
          </div>

          <div class="text-center" style="margin-top: 40px;">
            <a href="<?= home_url('teachers'); ?>" class="btn-view-all">
              View All Teachers <i class="fa fa-arrow-right"></i>
            </a>
          </div>
        </div>
      </section>

  <?php
    }
  }
  ?>


  <?php
  if ($layout_visibility['committees'] === 1) {
  ?>
    <section class="committee-section">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">Governing Committee</h2>
          <div class="section-underline"></div>
          <p class="section-subtitle">Meet our dedicated committee members</p>
        </div>

        <div class="committee-grid">
          <?php
          // Fetch committee members from ct_committee table
          $committees = [];
          $committees_data = $wpdb->get_results("SELECT * FROM ct_committee WHERE committeeStatus IN ('active') ORDER BY committeeSession DESC, committeeName ASC", ARRAY_A);

          if ($committees_data) {
            $committees = $committees_data;
          }

          if (!empty($committees)) {
            foreach ($committees as $member) {
              $name = isset($member['committeeName']) ? trim($member['committeeName']) : '';
              $role = isset($member['committeeDesignation']) ? trim($member['committeeDesignation']) : '';
              $rawImage = isset($member['committeeImg']) ? trim($member['committeeImg']) : '';

              if (!empty($rawImage) && strpos($rawImage, 'http') !== 0) {
                $rawImage = home_url('/') . ltrim($rawImage, '/');
              }

              $imageSrc = !empty($rawImage)
                ? esc_url($rawImage)
                : esc_url(get_template_directory_uri() . '/img/No_Image.jpg');

              if (empty($name) && empty($role)) {
                continue; // Skip empty entries
              }
          ?>
              <a href="<?= home_url('committee/') ?>">
                <div class="committee-card" data-aos="fade-up">
                  <div class="committee-card-inner">
                    <div class="committee-image-wrapper">
                      <img src="<?= $imageSrc ?>" alt="<?= esc_attr($name ?: 'Committee member photo') ?>" class="committee-image">
                    </div>
                    <div class="committee-info">
                      <?php if (!empty($name)) : ?>
                        <h4 class="committee-name" style="margin: 0;"><?= esc_html($name) ?></h4>
                      <?php endif; ?>
                      <?php if (!empty($role)) : ?>
                        <p class="committee-role"><?= esc_html($role) ?></p>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </a>
          <?php
            }
          } else {
            echo '<p class="text-center text-danger">No committee members available.</p>';
          }
          ?>
        </div>

        <div class="text-center" style="margin-top: 40px;">
          <a href="<?= home_url('committee'); ?>" class="btn-view-all">
            View All Committees <i class="fa fa-arrow-right"></i>
          </a>
        </div>
    </section>

  <?php
  }

  if ($layout_visibility['classwise_students'] === 1 || $layout_visibility['student_demographics'] === 1) {

  ?>

    <section class="demographics-section">
      <div class="section-header">
        <h2 class="section-title">Students Demographics</h2>
        <div class="section-underline"></div>
        <p class="section-subtitle">Classwise students and their distribution</p>
      </div>


      <div class="demographics-row">
        <?php
        if ($layout_visibility['classwise_students'] === 1) {
          // Fetch class-wise student counts from sm_options table json stringified
          $classes = get_class_wise_students();
          $class_count = is_array($classes) ? count($classes) : 0;
          $classes_grid_class = 'classes-grid';
          if ($class_count > 0 && $class_count < 7) {
            $classes_grid_class .= ' classes-grid--three';
          }
        ?>
          <!-- No of students in classes -->
          <div class="classes-container">
            <div class="<?= esc_attr($classes_grid_class); ?>" style="display:grid;grid-template-columns:repeat(5, 1fr);">
              <?php
              if (!empty($classes)) {
                foreach ($classes as $name => $count) {
                  $palette = [
                    ['#6a11cb', '#2575fc'],
                    ['#11998e', '#38ef7d'],
                    ['#f7971e', '#ffd200'],
                    ['#f953c6', '#b91d73'],
                    ['#00c6ff', '#0072ff'],
                    ['#ff512f', '#dd2476'],
                  ];
                  $idx = abs(crc32($name)) % count($palette);
                  [$c1, $c2] = $palette[$idx];
              ?>
                  <div class="modern-class-card" style="--gradient-start: <?= esc_attr($c1) ?>; --gradient-end: <?= esc_attr($c2) ?>;">
                    <div class="class-count-circle">
                      <span class="class-count-number class-counter" data-count="<?= (int)$count ?>">0</span>
                    </div>
                    <p class="class-name-modern">
                      <?= esc_html(ucwords(str_replace('class_', ' ', $name))); ?>
                    </p>
                  </div>
              <?php
                }
              } else {
                echo '<p class="text-center text-white">No class data available.</p>';
              }
              ?>
            </div>

            <script>
              (function($) {
                var initialAnimationDone = false;

                // Initial animation on scroll into view
                function startInitialAnimation() {
                  if (initialAnimationDone) return;

                  var $section = $('.classes-container');
                  if (!$section.length) return;

                  var trigger = $(window).scrollTop() + $(window).height() > $section.offset().top + 100;
                  if (!trigger) return;

                  initialAnimationDone = true;

                  $('.modern-class-card').each(function(index) {
                    var $card = $(this);
                    var $counter = $card.find('.class-counter');
                    var target = parseInt($counter.data('count'), 10) || 0;

                    setTimeout(function() {
                      $({
                        n: 0
                      }).animate({
                        n: target
                      }, {
                        duration: 2000,
                        easing: 'swing',
                        step: function(now) {
                          $counter.text(Math.ceil(now));
                        },
                        complete: function() {
                          $counter.text(target);
                        }
                      });
                    }, index * 100); // Stagger animation by 100ms per card
                  });
                }

                $(window).on('load scroll', startInitialAnimation);

                // Hover animation - re-count on hover
                $('.modern-class-card').on('mouseenter', function() {
                  var $card = $(this);
                  var $counter = $card.find('.class-counter');
                  var target = parseInt($counter.data('count'), 10) || 0;

                  // Animate count again on hover
                  $({
                    n: 0
                  }).animate({
                    n: target
                  }, {
                    duration: 1000,
                    easing: 'swing',
                    step: function(now) {
                      $counter.text(Math.ceil(now));
                    },
                    complete: function() {
                      $counter.text(target);
                    }
                  });
                });
              })(jQuery);
            </script>
          </div>
        <?php
        }

        if ($layout_visibility['student_demographics'] === 1) {
          // Fetch student demographics from sm_options table
          $demographics = get_student_demographics();
          $total = $demographics['total_students'] ?? 0;

          // Calculate percentages
          function calc_percentage($value, $total)
          {
            return $total > 0 ? round(($value / $total) * 100, 1) : 0;
          }
        ?>
          <div class="demographics-container">
            <div class="demographics-content">
              <!-- Gender Distribution -->
              <ul class="demo-list">
                <li class="demo-item" data-delay="100">
                  <span class="demo-item-label">Total Students</span>
                  <div class="demo-item-value">
                    <span class="demo-item-percentage">(<?= calc_percentage($demographics['total_students'] ?? 0, $total) ?>%)</span>
                    <span class="demo-item-count demo-counter" data-count="<?= (int)($demographics['total_students'] ?? 0) ?>">0</span>
                  </div>
                </li>
                <li class="demo-item" data-delay="100">
                  <span class="demo-item-label">Boys</span>
                  <div class="demo-item-value">
                    <span class="demo-item-percentage">(<?= calc_percentage($demographics['boys'] ?? 0, $total) ?>%)</span>
                    <span class="demo-item-count demo-counter" data-count="<?= (int)($demographics['boys'] ?? 0) ?>">0</span>
                  </div>
                </li>
                <li class="demo-item" data-delay="200">
                  <span class="demo-item-label">Girls</span>
                  <div class="demo-item-value">
                    <span class="demo-item-percentage">(<?= calc_percentage($demographics['girls'] ?? 0, $total) ?>%)</span>
                    <span class="demo-item-count demo-counter" data-count="<?= (int)($demographics['girls'] ?? 0) ?>">0</span>
                  </div>
                </li>

                <?php if (calc_percentage($demographics['gender_other'] ?? 0, $total) > 0) { ?>
                  <li class="demo-item" data-delay="300">
                    <span class="demo-item-label">Other</span>
                    <div class="demo-item-value">
                      <span class="demo-item-percentage">(<?= calc_percentage($demographics['gender_other'] ?? 0, $total) ?>%)</span>
                      <span class="demo-item-count demo-counter" data-count="<?= (int)($demographics['gender_other'] ?? 0) ?>">0</span>
                    </div>
                  </li>
                <?php } ?>

                <li class="demo-item" data-delay="400">
                  <span class="demo-item-label">Muslim</span>
                  <div class="demo-item-value">
                    <span class="demo-item-percentage">(<?= calc_percentage($demographics['muslim'] ?? 0, $total) ?>%)</span>
                    <span class="demo-item-count demo-counter" data-count="<?= (int)($demographics['muslim'] ?? 0) ?>">0</span>
                  </div>
                </li>
                <li class="demo-item" data-delay="500">
                  <span class="demo-item-label">Hinduism</span>
                  <div class="demo-item-value">
                    <span class="demo-item-percentage">(<?= calc_percentage($demographics['hinduism'] ?? 0, $total) ?>%)</span>
                    <span class="demo-item-count demo-counter" data-count="<?= (int)($demographics['hinduism'] ?? 0) ?>">0</span>
                  </div>
                </li>

                <?php if (calc_percentage($demographics['buddhist'] ?? 0, $total) > 0) { ?>
                  <li class="demo-item" data-delay="600">
                    <span class="demo-item-label">Buddhist</span>
                    <div class="demo-item-value">
                      <span class="demo-item-percentage">(<?= calc_percentage($demographics['buddhist'] ?? 0, $total) ?>%)</span>
                      <span class="demo-item-count demo-counter" data-count="<?= (int)($demographics['buddhist'] ?? 0) ?>">0</span>
                    </div>
                  </li>
                <?php } ?>

                <?php if (calc_percentage($demographics['christian'] ?? 0, $total) > 0) { ?>
                  <li class="demo-item" data-delay="700">
                    <span class="demo-item-label">Christian</span>
                    <div class="demo-item-value">
                      <span class="demo-item-percentage">(<?= calc_percentage($demographics['christian'] ?? 0, $total) ?>%)</span>
                      <span class="demo-item-count demo-counter" data-count="<?= (int)($demographics['christian'] ?? 0) ?>">0</span>
                    </div>
                  </li>
                <?php } ?>

                <?php if (calc_percentage($demographics['other'] ?? 0, $total) > 0) { ?>
                  <li class="demo-item" data-delay="800">
                    <span class="demo-item-label">Other</span>
                    <div class="demo-item-value">
                      <span class="demo-item-percentage">(<?= calc_percentage($demographics['other'] ?? 0, $total) ?>%)</span>
                      <span class="demo-item-count demo-counter" data-count="<?= (int)($demographics['other'] ?? 0) ?>">0</span>
                    </div>
                  </li>
                <?php } ?>
              </ul>
            </div>
          </div>
        <?php
        }
        ?>
      </div>
    </section>

  <?php
  }

  // Gallery Section
  if ($layout_visibility['gallery'] === 1) {
  ?>
    <!-- Gallery Section -->
    <div class="gallerySection" style="background:#211c3c">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title">Gallery</h2>
          <div class="section-underline"></div>
          <p class="section-subtitle">Explore our beautiful moments captured in time</p>
        </div>

        <?php
        $args = [
          'post_status' => 'publish',
          'category_name' => 'gallery',
          'posts_per_page' => '12'
        ];

        $gallery = new WP_Query($args);

        if ($gallery->have_posts()) {
          echo '<div class="owl-carousel">';
          while ($gallery->have_posts()) {
            $gallery->the_post();
            if (has_post_thumbnail()) {
        ?>
              <div class="item" title="<?= the_title() ?>">
                <?= the_post_thumbnail(); ?>
              </div>
        <?php
            }
          }
          echo '</div>';
        } else {
          echo "<h3 class='text-center text-danger'>Gallery Empty</h3>";
        }

        wp_reset_postdata();

        ?>
        <div class="text-center ">
          <a class="btn btn-primary" href="<?= home_url('gallery') ?>">See More</a>
        </div>
      </div>
    </div>

  <?php
  }
  ?>


  <script>
    (function($) {
      var demographicsAnimated = false;

      // Animate demographics section on scroll
      function animateDemographics() {
        if (demographicsAnimated) return;

        var $section = $('.demographics-section');
        if (!$section.length) return;

        var trigger = $(window).scrollTop() + $(window).height() > $section.offset().top + 100;
        if (!trigger) return;

        demographicsAnimated = true;

        // Animate all counters
        $('.demo-counter').each(function() {
          var $counter = $(this);
          var target = parseInt($counter.data('count'), 10) || 0;

          $({
            n: 0
          }).animate({
            n: target
          }, {
            duration: 1500,
            easing: 'swing',
            step: function(now) {
              $counter.text(Math.ceil(now));
            },
            complete: function() {
              $counter.text(target);
            }
          });
        });

        // Animate list items with staggered delay
        $('.demo-item').each(function() {
          var $item = $(this);
          var delay = $item.data('delay') || 0;

          setTimeout(function() {
            $item.addClass('animate-in');
          }, delay);
        });
      }

      $(window).on('load scroll', animateDemographics);

      // Hover effect on list items - re-animate count
      $('.demo-item').on('mouseenter', function() {
        var $item = $(this);
        var $counter = $item.find('.demo-counter');

        if ($counter.length) {
          var target = parseInt($counter.data('count'), 10) || 0;

          $({
            n: 0
          }).animate({
            n: target
          }, {
            duration: 600,
            easing: 'swing',
            step: function(now) {
              $counter.text(Math.ceil(now));
            },
            complete: function() {
              $counter.text(target);
            }
          });
        }
      });
    })(jQuery);
  </script>

  <?php get_footer(); ?>