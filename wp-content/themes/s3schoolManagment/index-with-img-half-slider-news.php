<?php

/**
 * Template Name: Index Half Img News
 */

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

<style>
  @import url('https://cdn.msar.me/fonts/kalpurush/font.css');

  body {
    font-family: 'Kalpurush', Arial, sans-serif !important;
  }
</style>

<!-- Latest News -->
<div class="latestNewsMarque">
  <div class="container latest-news-container">
    <div class="title latest-news-label">সাম্প্রতিক:</div>
    <div class="marque latest-news-ticker" style="color: black;">
      <marquee onmouseover="this.stop();" onmouseout="this.start();">
        <?php
        $args = [
          'post_status' => 'publish',
          'category_name' => 'latest-news',
          'posts_per_page' => 10
        ];
        $news_query = new WP_Query($args);
        if ($news_query->have_posts()) {
          while ($news_query->have_posts()) {
            $news_query->the_post();
        ?>
            <span style="margin-right: 20px;">
              <i class="fa fa-bell" aria-hidden="true" style="color: #d32f2f;"></i>
              <a href="<?= get_permalink(); ?>" style="color: #333;"><?php the_title(); ?></a>
            </span>
        <?php
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
        <div id="myCarousel" class="col-md-12 carousel slide" data-ride="carousel">
          <div class="carousel-inner">
            <?php foreach ($slider_images as $key => $value) { ?>
              <div class="item <?php echo ($key == 0) ? 'active' : ''; ?>">
                <img class="img-responsive" src="<?= $value->image_url ?>" alt="">
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

  <?php if (!empty($display_info)): ?>
    <div class="instituteInfo" style="margin-bottom:20px;">
      <div class="container">
        <div class="row" style="display:flex;justify-content:center;gap:10px;">
          <?php foreach ($display_info as [$label, $key, $colors]): ?>
            <div class="text-center institute-card" style="width:calc(<?= $cardWidth ?>% - 10px);background:linear-gradient(135deg,<?= $colors ?>);color:#fff;border-radius:8px;margin:5px 2px;box-shadow:0 2px 8px rgba(0,0,0,0.06);padding:15px 5px;">
              <div style="font-size:16px;font-weight:600;"><?= esc_html($label) ?></div>
              <div style="font-size:20px;font-weight:500;"><?= esc_html((string) ($sm_opts[$key] ?? '')) ?></div>
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
                <a href="<?= home_url('our-staffs'); ?>">
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


  <section class="modern-about-section">
    <div class="container">
      <!-- Main About Card -->
      <div class="row">
        <div class="col-md-12 wow fadeInUp">
          <div class="about-main-card">
            <div class="about-content-wrapper">
              <div class="about-header-flex">
                <div class="about-icon-container">
                  <img src="<?= $s3sRedux['home_about_img']['url']; ?>" alt="About Us">
                </div>
                <div class="about-title-container">
                  <div class="section-badge">About Us</div>
                  <h2 class="about-title"><?= $s3sRedux['aboutTitelText']; ?></h2>
                </div>
              </div>
              <div class="about-text">
                <?php
                $content = preg_replace("/(\r\n|\n|\r){2,}/", "\n", $s3sRedux['aboutUsText']);
                s3LimitText(wp_kses_post(trim($content)), $s3sRedux['aboutUsTextLimit']);
                ?>
              </div>
              <?php if (!empty($s3sRedux['aboutUsMoreBtn'])) { ?>
                <a href="<?= home_url() ?>/speech?cont=about" class="modern-btn-primary">
                  <?= $s3sRedux['aboutUsMoreBtn'] ?>
                  <i class="fa fa-arrow-right"></i>
                </a>
              <?php } ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Leadership Cards -->
      <div class="row speech-cards-row">
        <!-- Headmaster -->
        <div class="col-md-6 col-sm-6 wow fadeInLeft" data-wow-delay="0.2s">
          <div class="speech-card">
            <div class="speech-card-header">
              <div class="speech-avatar">
                <img src="<?= $s3sRedux['homeHeadmasterImg']['url']; ?>" alt="<?= $s3sRedux['homeHeadmasterTitle']; ?>">
              </div>
              <div class="speech-meta">
                <h4 class="speech-name"><?= $s3sRedux['homeHeadmasterTitle']; ?></h4>
                <span class="speech-role">Headmaster</span>
              </div>
            </div>
            <div class="speech-body">
              <h3 class="speech-title"><?= $s3sRedux['headmasterSpeechTitle'] ?></h3>
              <div class="speech-text">
                <?php
                $content = preg_replace("/(\r\n|\n|\r){2,}/", "\n", $s3sRedux['homeHeadmaster']);
                s3LimitText(wp_kses_post(nl2br(trim($content))), $s3sRedux['headmasterTextLimit']);
                ?>
              </div>
            </div>
            <div class="speech-footer">
              <?php if (!empty($s3sRedux['headmasterMoreBtn'])) { ?>
                <a href="<?= home_url() ?>/speech?cont=headmaster" class="read-more-link">
                  <?= $s3sRedux['headmasterMoreBtn'] ?> <i class="fa fa-long-arrow-right"></i>
                </a>
              <?php } ?>
            </div>
          </div>
        </div>

        <!-- Chairman -->
        <div class="col-md-6 col-sm-6 wow fadeInRight" data-wow-delay="0.2s">
          <div class="speech-card">
            <div class="speech-card-header">
              <div class="speech-avatar">
                <img src="<?= $s3sRedux['homeChairmanImg']['url']; ?>" alt="<?= $s3sRedux['homeChairmanTitle']; ?>">
              </div>
              <div class="speech-meta">
                <h4 class="speech-name"><?= $s3sRedux['homeChairmanTitle']; ?></h4>
                <span class="speech-role">Chairman</span>
              </div>
            </div>
            <div class="speech-body">
              <h3 class="speech-title"><?= $s3sRedux['chairmanSpeechTitle'] ?></h3>
              <div class="speech-text">
                <?php
                s3LimitText(wp_kses_post(nl2br($s3sRedux['homeChairman'])), $s3sRedux['chairmanTextLimit']);
                ?>
              </div>
            </div>
            <div class="speech-footer">
              <?php if (!empty($s3sRedux['chairmanMoreBtn'])) { ?>
                <a href="<?= home_url() ?>/speech?cont=chairman" class="read-more-link">
                  <?= $s3sRedux['chairmanMoreBtn'] ?>
                </a>
              <?php } ?>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <style>
    .modern-about-section {
      padding: 80px 0;
      background: #f8fafc;
    }

    .about-main-card {
      background: #fff;
      border-radius: 24px;
      overflow: hidden;
      box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.08);
      margin-bottom: 40px;
    }

    .about-header-flex {
      display: flex;
      align-items: center;
      gap: 24px;
      margin-bottom: 24px;
    }

    .about-icon-container {
      width: 80px;
      height: 80px;
      flex-shrink: 0;
      background: #f1f5f9;
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 12px;
    }

    .about-icon-container img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }

    .about-title-container {
      flex: 1;
    }

    .about-content-wrapper {
      padding: 40px;
    }

    .section-badge {
      display: inline-block;
      padding: 6px 16px;
      background: rgba(99, 102, 241, 0.1);
      color: #6366f1;
      border-radius: 50px;
      font-size: 12px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1px;
      margin-bottom: 16px;
      width: fit-content;
    }

    .about-title {
      font-size: 32px;
      font-weight: 800;
      color: #0f172a;
      margin-bottom: 0;
      line-height: 1.3;
    }

    .about-text {
      color: #64748b;
      font-size: 16px;
      line-height: 1.7;
      margin-bottom: 30px;
    }

    .modern-btn-primary {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      background: linear-gradient(135deg, #6366f1, #8b5cf6);
      color: #fff;
      padding: 12px 28px;
      border-radius: 50px;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.3s ease;
      width: fit-content;
      box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
    }

    .modern-btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(99, 102, 241, 0.35);
      color: #fff;
    }

    /* Speech Cards */
    .speech-cards-row {
      display: flex;
      flex-wrap: wrap;
    }

    .speech-card {
      background: #fff;
      border-radius: 20px;
      padding: 30px;
      height: 100%;
      border: 1px solid #e2e8f0;
      transition: all 0.3s ease;
      display: flex;
      flex-direction: column;
    }

    .speech-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 15px 30px -5px rgba(0, 0, 0, 0.08);
      border-color: transparent;
    }

    .speech-card-header {
      display: flex;
      align-items: center;
      gap: 16px;
      margin-bottom: 24px;
      padding-bottom: 20px;
      border-bottom: 1px solid #f1f5f9;
    }

    .speech-avatar {
      width: 64px;
      height: 64px;
      border-radius: 50%;
      overflow: hidden;
      border: 3px solid #eef2ff;
    }

    .speech-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .speech-meta {
      flex: 1;
    }

    .speech-name {
      font-size: 18px;
      font-weight: 700;
      color: #0f172a;
      margin: 0 0 4px 0;
    }

    .speech-role {
      font-size: 13px;
      color: #6366f1;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .speech-body {
      flex: 1;
    }

    .speech-title {
      font-size: 20px;
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 12px;
    }

    .speech-text {
      color: #64748b;
      font-size: 15px;
      line-height: 1.6;
      margin-bottom: 20px;
    }

    .speech-footer {
      margin-top: auto;
    }

    .read-more-link {
      color: #6366f1;
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      transition: gap 0.3s ease;
    }

    .read-more-link:hover {
      gap: 10px;
      color: #4f46e5;
    }

    @media (max-width: 767px) {
      .display-flex {
        flex-direction: column;
      }

      .about-img-wrapper {
        min-height: 250px;
      }

      .about-content-wrapper {
        padding: 24px;
      }

      .speech-card {
        margin-bottom: 20px;
      }
    }
  </style>


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
        <div class="col-md-3 col-xs-6 col-sm-6 text-center wow fadeInUp stat-col" data-wow-delay=".1s">
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
        <div class="col-md-3 col-xs-6 col-sm-6 text-center wow fadeInUp stat-col" data-wow-delay=".2s">
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
        <div class="col-md-3 col-xs-6 col-sm-6 text-center wow fadeInUp stat-col" data-wow-delay=".3s">
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
        <div class="col-md-3 col-xs-6 col-sm-6 text-center wow fadeInUp stat-col" data-wow-delay=".4s">
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



  <?php
  $quick_links = [
    [
      'title' => 'Students',
      'url' => home_url('student-search'),
      'icon' => 'users',
      'tagline' => 'Find student profiles instantly',
      'gradient' => 'linear-gradient(135deg,#5efce8,#736efe)'
    ],
    [
      'title' => 'Teachers',
      'url' => home_url('teachers'),
      'icon' => 'male',
      'tagline' => 'Meet our dedicated faculty',
      'gradient' => 'linear-gradient(135deg,#ffb347,#ffcc33)'
    ],
    [
      'title' => 'Attendance',
      'url' => '#',
      'icon' => 'check',
      'tagline' => 'Monitor daily presence data',
      'gradient' => 'linear-gradient(135deg,#43cea2,#185a9d)'
    ],
    [
      'title' => 'Result',
      'url' => home_url('result'),
      'icon' => 'bolt',
      'tagline' => 'Review published exam results',
      'gradient' => 'linear-gradient(135deg,#ff0844,#ffb199)'
    ],
    [
      'title' => 'Routine',
      'url' => home_url('routine'),
      'icon' => 'bell',
      'tagline' => 'Stay aligned with routines',
      'gradient' => 'linear-gradient(135deg,#11998e,#38ef7d)'
    ],
    [
      'title' => 'Photo Gallery',
      'url' => home_url('gallery'),
      'icon' => 'camera',
      'tagline' => 'Relive memorable moments',
      'gradient' => 'linear-gradient(135deg,#f7971e,#ffd200)'
    ],
    [
      'title' => 'News',
      'url' => home_url('latest-news'),
      'icon' => 'bell',
      'tagline' => 'Catch up on announcements',
      'gradient' => 'linear-gradient(135deg,#ff9a9e,#fecfef)'
    ],
    [
      'title' => 'Notice',
      'url' => home_url('latest-notice'),
      'icon' => 'quote-left',
      'tagline' => 'Important school notices',
      'gradient' => 'linear-gradient(135deg,#f8ffae,#43c6ac)'
    ],
  ];
  ?>

  <section class="quick-links-modern animated fadeInUpBig" data-wow-duration="1000ms" data-wow-delay="500ms">
    <div class="container">
      <div class="row quick-links-row" style="margin-top: 20px;">
        <div class="col-md-8 quick-links-wrapper">
          <div class="quick-links-grid">
            <?php foreach ($quick_links as $link) : ?>
              <a class="quick-link-card" href="<?= esc_url($link['url']); ?>" style="--card-accent: <?= esc_attr($link['gradient']); ?>" aria-label="<?= esc_attr($link['title']); ?> shortcut">
                <span class="quick-link-glow"></span>
                <div class="quick-link-top">
                  <div class="quick-link-icon">
                    <i class="fa fa-<?= esc_attr($link['icon']); ?>" aria-hidden="true"></i>
                  </div>
                  <span class="quick-link-arrow">
                    <i class="fa fa-arrow-right" aria-hidden="true"></i>
                  </span>
                </div>

                <span class="quick-link-meta">
                  <strong><?= esc_html($link['title']); ?></strong>
                </span>
              </a>
            <?php endforeach; ?>
          </div>
        </div>

        <style>
          @media (max-width: 991px) {
            .responsive-fix {
              margin-top: 40px !important;
            }
          }
        </style>

        <div class="col-md-4 sliderRight latest-news-panel responsive-fix">
          <div class="latest-news-box modern-news-box">
            <div class="latest-news-header">
              <div>
                <p class="quick-links-eyebrow">সর্বশেষ সংবাদ</p>
              </div>
              <a href="<?= home_url('latest-news'); ?>" class="news-view-all">
                View all
                <i class="fa fa-arrow-right" aria-hidden="true"></i>
              </a>
            </div>
            <div class="latest-news-stream">
              <?php
              $args = [
                'post_status' => 'publish',
                'category_name' => 'latest-news',
                'posts_per_page' => 10
              ];

              $the_query = new WP_Query($args);
              if ($the_query->have_posts()) {
                while ($the_query->have_posts()) {
                  $the_query->the_post();
                  $day = get_the_date('d');
                  $month = get_the_date('M');
              ?>
                  <a href="<?= get_permalink(); ?>" class="news-scroll-item">
                    <div class="news-date-box">
                      <span class="news-date-day"><?= esc_html($day); ?></span>
                      <span class="news-date-month"><?= esc_html($month); ?></span>
                    </div>
                    <div class="news-item-content">
                      <h4><?php the_title(); ?></h4>
                      <div class="news-item-meta">
                        <i class="fa fa-clock-o" aria-hidden="true"></i>
                        <?= esc_html(get_post_time('h:i a', true)); ?>
                      </div>
                    </div>
                  </a>
                <?php
                }
              } else {
                ?>
                <div class="news-empty-state">
                  <p><?= esc_html__('No news published yet.', 's3school'); ?></p>
                </div>
              <?php
              }

              wp_reset_postdata();
              ?>
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
  </section>

  <script>
    (function() {
      var grid = document.querySelector('.quick-links-grid');
      if (grid) {
        var cols = grid.querySelectorAll('.quick-link-col');
        if (cols.length) {
          cols.forEach(function(col) {
            col.style.padding = '0';
          });
        }
      }
      var items = document.querySelectorAll('.quick-link-card');
      items.forEach(function(el) {
        el.addEventListener('mouseenter', function() {
          el.classList.add('hovered');
        });
        el.addEventListener('mouseleave', function() {
          el.classList.remove('hovered');
        });
      });
    })();
  </script>
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

  <section class="important-links-modern">
    <div class="container">
      <div class="section-header text-center">
        <h2 class="section-title">Important Links</h2>
        <div class="section-underline" style="margin: 0 auto 15px;"></div>
      </div>

      <div class="important-links-grid">
        <?php
        if (!empty($important_links)) {
          foreach ($important_links as $link) {
            if (!empty($link['title']) && !empty($link['url'])) {
        ?>
              <a class="resource-card" href="<?= esc_url($link['url']); ?>" target="_blank">
                <div class="resource-icon">
                  <i class="fa fa-link"></i>
                </div>
                <span class="resource-title"><?= esc_html($link['title']); ?></span>
                <div class="resource-arrow">
                  <i class="fa fa-arrow-right"></i>
                </div>
              </a>
        <?php
            }
          }
        } else {
          echo '<div class="col-md-12 text-center"><p class="text-muted">No important links available.</p></div>';
        }
        ?>
      </div>
    </div>
  </section>

  <style>
    .important-links-modern {
      padding: 80px 0;
      background: #DBB937;
      position: relative;
    }

    .important-links-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 20px;
      margin-top: 40px;
    }

    .resource-card {
      display: flex;
      align-items: center;
      gap: 10px;
      background: #fff;
      padding: 5px;
      border-radius: 16px;
      text-decoration: none;
      color: #334155;
      border: 1px solid #e2e8f0;
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
    }

    .resource-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 24px -8px rgba(0, 0, 0, 0.08);
      border-color: #cbd5e1;
      color: #0f172a;
    }

    .resource-icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      background: #f1f5f9;
      color: #64748b;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      transition: all 0.3s ease;
      flex-shrink: 0;
    }

    .resource-card:hover .resource-icon {
      background: #6366f1;
      color: #fff;
      transform: scale(1.1) rotate(-5deg);
    }

    .resource-title {
      font-size: 16px;
      font-weight: 600;
      flex: 1;
      line-height: 1.4;
    }

    .resource-arrow {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #cbd5e1;
      transition: all 0.3s ease;
      opacity: 0;
      transform: translateX(-10px);
    }

    .resource-card:hover .resource-arrow {
      opacity: 1;
      transform: translateX(0);
      color: #6366f1;
      background: #eef2ff;
    }

    /* --- Teachers Grid Layout --- */
    .teachers-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 1rem;
    }

    /* --- Teacher Card --- */
    .teacher-card {
      width: 180px;
      /* fixed width */
      height: 230px;
      /* fixed height */
      background: #fff;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
      transition: transform 0.25s ease, box-shadow 0.25s ease;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      text-align: center;
      position: relative;
    }

    .teacher-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 8px 18px rgba(0, 0, 0, 0.12);
    }

    .teacher-card-inner {
      width: 100%;
      height: 100%;
      display: flex;
      flex-direction: column;
    }

    /* --- Image Wrapper (fixed height zone) --- */
    .teacher-image-wrapper {
      width: 100%;
      height: 170px;
      /* fixed height for image section */
      background-color: #f2f2f2;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
    }

    .teacher-image {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.4s ease;
    }

    .teacher-card:hover .teacher-image {
      transform: scale(1.05);
    }


    /* --- Info Section --- */
    .teacher-info {
      padding: 1rem;
      background-color: #fff;
      flex: 1;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .teacher-name {
      font-size: 1.1rem;
      font-weight: 600;
      color: #222;
      margin: 0.4rem 0 0.2rem;
      word-wrap: break-word;
    }

    .teacher-designation {
      font-size: 0.95rem;
      color: #666;
      margin: 0;
    }

    /* --- Link Styling --- */
    .teacher-card-link {
      display: block;
      width: 100%;
      height: 100%;
      text-decoration: none;
      color: inherit;
      transition: all 0.3s ease;
    }

    .teacher-card-link:hover {
      text-decoration: none;
      color: inherit;
    }

    /* --- Fallback for Missing Image --- */
    .teacher-image-wrapper img[src*="No_Image.jpg"] {
      object-fit: contain;
      opacity: 0.8;
    }



    @media (width <=768px) {
      .teachers-grid {
        display: grid;
        grid-template-columns: repeact(2, 1fr);
      }

      .teacher-card {
        width: 95%;
      }
    }
  </style>

  <?php
  if ($layout_visibility['teachers'] === 1) {
    global $wpdb;

    $teacherRows = [];
    $teacherTable = $wpdb->get_var("SHOW TABLES LIKE 'ct_teacher'");

    if ($teacherTable === 'ct_teacher') {
      $teacherRows = $wpdb->get_results(
        "SELECT teacherid, teacherName, teacherImg, teacherDesignation, teacherPhone, teacher_serial FROM ct_teacher WHERE status='Present' AND teacherDesignation NOT LIKE '%Lecturer%' ORDER BY teacher_serial, teacherName ASC LIMIT 12"
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
                <div class="teacher-card" data-aos="fade-up">
                  <a href="<?= home_url('/our-teachers/?t=' . $teacher->teacherid) ?>" title="View Profile" class="teacher-card-link">
                    <div class="teacher-card-inner">
                      <div class="teacher-image-wrapper">
                        <img src="<?= $imageSrc ?>" alt="<?= esc_attr($teacherName) ?>" class="teacher-image">
                      </div>
                      <div class="teacher-info">
                        <h4 class="teacher-name"><?= esc_html($teacherName) ?></h4>
                        <?php if (!empty($teacherDesignation)): ?>
                          <p class="teacher-designation"><?= esc_html($teacherDesignation) ?></p>
                        <?php endif; ?>
                      </div>
                    </div>
                  </a>
                </div>
            <?php
              }
            }
            ?>
          </div>

          <div class="text-center" style="margin-top: 40px;">
            <a href="<?= home_url('our-teachers'); ?>" class="btn-view-all">
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
  if ($layout_visibility['committees'] === 1 && !empty($committees)) {
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
                continue;  // Skip empty entries
              }
          ?>
              <a href="<?= home_url('committee/') ?>">
                <div class="committee-card" data-aos="fade-up">
                  <div class="committee-card-inner">
                    <div class="committee-image-wrapper">
                      <img src="<?= $imageSrc ?>" alt="<?= esc_attr($name ?: 'Committee member photo') ?>" class="committee-image">
                    </div>
                    <div class="committee-info">
                      <?php if (!empty($name)): ?>
                        <h4 class="committee-name" style="margin: 0;"><?= esc_html($name) ?></h4>
                      <?php endif; ?>
                      <?php if (!empty($role)): ?>
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


      <style>
        .classes-grid-five {
          display: grid;
          grid-template-columns: repeat(5, 1fr);
        }

        .classes-grid-four {
          grid-template-columns: repeat(4, 1fr);
        }

        .classes-grid-three {
          grid-template-columns: repeat(3, 1fr);
        }
      </style>


      <div class="demographics-row">
        <?php
        if ($layout_visibility['classwise_students'] === 1) {
          // Fetch class-wise student counts from sm_options table json stringified
          $classes = get_class_wise_students();

          foreach ($classes as $name => $count) {
            if ($count == 0) {
              unset($classes[$name]);
            }
          }

          $class_count = is_array($classes) ? count($classes) : 0;
          $classes_grid_class = 'classes-grid-five';
          if ($class_count > 6 && $class_count < 9) {
            $classes_grid_class = 'classes-grid-four';
          } elseif ($class_count > 0 && $class_count < 7) {
            $classes_grid_class = 'classes-grid-three';
          }
        ?>
          <!-- No of students in classes -->
          <div class="classes-container">
            <div class="<?= esc_attr($classes_grid_class); ?>" style="display: grid;">
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
                      <span class="class-count-number class-counter" data-count="<?= (int) $count ?>">0</span>
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
                    <span class="demo-item-count demo-counter" data-count="<?= (int) ($demographics['total_students'] ?? 0) ?>">0</span>
                  </div>
                </li>
                <li class="demo-item" data-delay="100">
                  <span class="demo-item-label">Boys</span>
                  <div class="demo-item-value">
                    <span class="demo-item-percentage">(<?= calc_percentage($demographics['boys'] ?? 0, $total) ?>%)</span>
                    <span class="demo-item-count demo-counter" data-count="<?= (int) ($demographics['boys'] ?? 0) ?>">0</span>
                  </div>
                </li>
                <li class="demo-item" data-delay="200">
                  <span class="demo-item-label">Girls</span>
                  <div class="demo-item-value">
                    <span class="demo-item-percentage">(<?= calc_percentage($demographics['girls'] ?? 0, $total) ?>%)</span>
                    <span class="demo-item-count demo-counter" data-count="<?= (int) ($demographics['girls'] ?? 0) ?>">0</span>
                  </div>
                </li>

                <?php if (calc_percentage($demographics['gender_other'] ?? 0, $total) > 0) { ?>
                  <li class="demo-item" data-delay="300">
                    <span class="demo-item-label">Other</span>
                    <div class="demo-item-value">
                      <span class="demo-item-percentage">(<?= calc_percentage($demographics['gender_other'] ?? 0, $total) ?>%)</span>
                      <span class="demo-item-count demo-counter" data-count="<?= (int) ($demographics['gender_other'] ?? 0) ?>">0</span>
                    </div>
                  </li>
                <?php } ?>

                <li class="demo-item" data-delay="400">
                  <span class="demo-item-label">Muslim</span>
                  <div class="demo-item-value">
                    <span class="demo-item-percentage">(<?= calc_percentage($demographics['muslim'] ?? 0, $total) ?>%)</span>
                    <span class="demo-item-count demo-counter" data-count="<?= (int) ($demographics['muslim'] ?? 0) ?>">0</span>
                  </div>
                </li>
                <li class="demo-item" data-delay="500">
                  <span class="demo-item-label">Hinduism</span>
                  <div class="demo-item-value">
                    <span class="demo-item-percentage">(<?= calc_percentage($demographics['hinduism'] ?? 0, $total) ?>%)</span>
                    <span class="demo-item-count demo-counter" data-count="<?= (int) ($demographics['hinduism'] ?? 0) ?>">0</span>
                  </div>
                </li>

                <?php if (calc_percentage($demographics['buddhist'] ?? 0, $total) > 0) { ?>
                  <li class="demo-item" data-delay="600">
                    <span class="demo-item-label">Buddhist</span>
                    <div class="demo-item-value">
                      <span class="demo-item-percentage">(<?= calc_percentage($demographics['buddhist'] ?? 0, $total) ?>%)</span>
                      <span class="demo-item-count demo-counter" data-count="<?= (int) ($demographics['buddhist'] ?? 0) ?>">0</span>
                    </div>
                  </li>
                <?php } ?>

                <?php if (calc_percentage($demographics['christian'] ?? 0, $total) > 0) { ?>
                  <li class="demo-item" data-delay="700">
                    <span class="demo-item-label">Christian</span>
                    <div class="demo-item-value">
                      <span class="demo-item-percentage">(<?= calc_percentage($demographics['christian'] ?? 0, $total) ?>%)</span>
                      <span class="demo-item-count demo-counter" data-count="<?= (int) ($demographics['christian'] ?? 0) ?>">0</span>
                    </div>
                  </li>
                <?php } ?>

                <?php if (calc_percentage($demographics['other'] ?? 0, $total) > 0) { ?>
                  <li class="demo-item" data-delay="800">
                    <span class="demo-item-label">Other</span>
                    <div class="demo-item-value">
                      <span class="demo-item-percentage">(<?= calc_percentage($demographics['other'] ?? 0, $total) ?>%)</span>
                      <span class="demo-item-count demo-counter" data-count="<?= (int) ($demographics['other'] ?? 0) ?>">0</span>
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
</div>


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

<style>
  .quick-links-modern {
    position: relative;
    background: #473399;
    overflow: hidden;
    padding: 60px 0;
  }

  .quick-links-modern::before {
    content: '';
    position: absolute;
    width: 600px;
    height: 600px;
    background: radial-gradient(circle at center, rgba(99, 102, 241, 0.08), transparent 70%);
    top: -100px;
    right: -100px;
    pointer-events: none;
  }

  .quick-links-wrapper {
    margin-bottom: 30px;
  }

  .quick-links-intro {
    margin-bottom: 30px;
  }

  .quick-links-intro h2 {
    font-weight: 800;
    margin-bottom: 10px;
    color: #0f172a;
    font-size: 32px;
    letter-spacing: -0.5px;
  }

  .quick-links-eyebrow {
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 2px;
    font-weight: 700;
    color: #6366f1;
    margin-bottom: 8px;
    display: inline-block;
    background: rgba(99, 102, 241, 0.1);
    padding: 4px 12px;
    border-radius: 99px;
  }

  .quick-links-subtitle {
    color: #64748b;
    max-width: 540px;
    font-size: 16px;
    line-height: 1.6;
  }

  .quick-links-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 20px;
  }

  .quick-link-card {
    position: relative;
    background: #ffffff;
    border-radius: 24px;
    padding: 24px;
    min-height: 130px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    gap: 24px;
    color: #0f172a;
    text-decoration: none;
    transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1), box-shadow 0.3s ease;
    overflow: hidden;
    border: 1px solid #f1f5f9;
  }

  .quick-link-card::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--card-accent);
    opacity: 0.04;
    pointer-events: none;
  }

  .quick-link-card:hover {
    transform: translateY(-8px) scale(1.02);
    box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1);
    z-index: 1;
    border-color: rgba(99, 102, 241, 0.2);
  }

  .quick-link-glow {
    position: absolute;
    width: 150px;
    height: 150px;
    background: var(--card-accent);
    filter: blur(40px);
    top: -50px;
    right: -50px;
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
  }

  .quick-link-card:hover .quick-link-glow {
    opacity: 0.15;
  }

  .quick-link-top {
    width: 100%;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 12px;
  }

  .quick-link-icon {
    width: 54px;
    height: 54px;
    border-radius: 16px;
    background: var(--card-accent);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #fff;
    margin-bottom: 0;
    box-shadow: 0 8px 16px -4px rgba(0, 0, 0, 0.15);
    position: relative;
    z-index: 1;
  }

  .quick-link-meta {
    width: 100%;
    z-index: 1;
    display: flex;
    flex-direction: column;
    gap: 6px;
  }

  .quick-link-meta strong {
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
    letter-spacing: -0.3px;
  }

  .quick-link-meta small {
    color: #64748b;
    font-size: 13px;
    line-height: 1.4;
    font-weight: 500;
  }

  .quick-link-arrow {
    position: absolute;
    top: 24px;
    right: 24px;
    color: #cbd5e1;
    font-size: 16px;
    transition: transform 0.3s ease, color 0.3s ease;
    z-index: 1;
  }

  .quick-link-card:hover .quick-link-arrow {
    transform: translateX(4px);
    color: #6366f1;
  }

  .modern-news-box {
    border-radius: 24px;
    background: #fff;
    color: #0f172a;
    padding: 30px;
    box-shadow: 0 20px 40px -5px rgba(0, 0, 0, 0.05);
    position: relative;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    height: 100%;
  }

  .modern-news-box::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 6px;
    background: linear-gradient(90deg, #6366f1, #8b5cf6);
  }

  .latest-news-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid #f1f5f9;
  }

  .latest-news-header h4 {
    color: #0f172a;
    margin: 0;
    font-weight: 700;
    font-size: 20px;
  }

  .news-view-all {
    color: #fff;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    padding: 8px 20px;
    border-radius: 99px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
  }

  .news-view-all:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 16px rgba(99, 102, 241, 0.3);
    color: #fff;
  }

  .latest-news-stream {
    display: flex;
    flex-direction: column;
    gap: 0;
    position: relative;
    z-index: 1;
    height: 400px;
    overflow-y: auto;
    padding-right: 5px;
  }

  .latest-news-stream::-webkit-scrollbar {
    width: 6px;
  }

  .latest-news-stream::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
  }

  .latest-news-stream::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
  }

  .latest-news-stream::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
  }

  .news-scroll-item {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    padding: 16px 0;
    border-bottom: 1px solid #f1f5f9;
    text-decoration: none;
    color: #334155;
    transition: all 0.2s ease;
  }

  .news-scroll-item:last-child {
    border-bottom: none;
  }

  .news-scroll-item:hover {
    background: #f8fafc;
    padding-left: 10px;
    padding-right: 10px;
    border-radius: 12px;
    border-bottom-color: transparent;
  }

  .news-date-box {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    background: #eef2ff;
    color: #6366f1;
    border-radius: 12px;
    flex-shrink: 0;
    line-height: 1;
    border: 1px solid rgba(99, 102, 241, 0.1);
  }

  .news-date-day {
    font-size: 18px;
    font-weight: 700;
    letter-spacing: -0.5px;
  }

  .news-date-month {
    font-size: 10px;
    text-transform: uppercase;
    font-weight: 700;
    margin-top: 2px;
  }

  .news-item-content {
    flex: 1;
  }

  .news-item-content h4 {
    font-size: 15px;
    font-weight: 600;
    margin: 0 0 6px 0;
    color: #0f172a;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    transition: color 0.2s ease;
  }

  .news-scroll-item:hover .news-item-content h4 {
    color: #6366f1;
  }

  .news-item-meta {
    font-size: 12px;
    color: #94a3b8;
    display: flex;
    align-items: center;
    gap: 6px;
    font-weight: 500;
  }

  .news-empty-state {
    text-align: center;
    padding: 40px 0;
    color: #94a3b8;
  }

  @media (max-width: 991px) {
    .quick-links-grid {
      grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    }

    .modern-news-box {
      margin-top: 40px;
    }
  }

  @media (max-width: 767px) {
    .quick-link-card {
      min-height: 140px;
    }

    .modern-news-box {
      margin-top: 30px;
      padding: 20px;
    }

    .latest-news-stream {
      height: 300px;
    }
  }
</style>

<?php get_footer(); ?>