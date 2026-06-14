<?php
/**
 * Template Name: Academic Calendar Image
 */

get_header(); 

global $s3sRedux;
$calendar_img = 'img/academic-calendar.jpeg'; // Default image path
?>

<div class="b-layer-main">
    <div class="b-blog-classic">
        <div class="container">
            <div class="row">
                <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                    <div class="b-blog-items-holder wow slideInLeft">
                        <div class="clearfix aboutUsPageContent text-center">
                            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap;">
                                <h3 style="color:cadetblue;"><b>Academic Calendar</b></h3>
                                <a href="<?php echo home_url($calendar_img); ?>" download="Academic-Calendar.jpg" class="btn btn-primary btn-lg">
                                    <i class="fa fa-download"></i> Download Calendar
                                </a>
                            </div>

                            <div class="about-additional-text" style="margin-top: 30px;">
                                <?php if (!empty($calendar_img)) : ?>
                                    <img src="<?php echo home_url($calendar_img); ?>" alt="Academic Calendar" style="width: 100%; height: auto; box-shadow: 0 0 15px rgba(0,0,0,0.1); border-radius: 8px;">
                                <?php else : ?>
                                    <p class="text-danger">Calendar image not found. Please upload it in Theme Options.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer(); ?>
