<?php global $s3sRedux;
$s3sRedux['school-logo']['url'] = $wpdb->get_var("SELECT option_value FROM sm_options WHERE option_name = 'instLogo'");
?>

<style>
    .footer-new {
        background: #fff;
        border-top: 1px solid #ddd;
    }

    .footer-new-top {
        background: #0056b3;
        display: flex;
        justify-content: center;
        padding: 15px 0;
        gap: 20px;
    }

    .footer-new-top a {
        color: white;
        font-size: 24px;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .footer-new-top a:hover {
        transform: scale(1.2);
        color: #f8b239;
    }

    .footer-new-main {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        margin-top: 20px;
        padding: 30px 50px;
        gap: 40px;
    }

    /* Left section */
    .footer-new-left {
        max-width: 300px;
    }

    .footer-new-left img {
        max-width: 80px;
        display: block;
        margin-bottom: 10px;
    }

    .footer-new-left h3 {
        margin: 10px 0 5px;
        color: #003366;
        font-size: 18px;
    }

    .footer-new-left p {
        margin: 5px 0;
        color: #555;
    }

    /* Quick Links */
    .footer-new-links {
        flex: 1;
        min-width: 200px;
    }

    .footer-new-links h4 {
        margin-bottom: 10px;
        color: #003366;
        border-bottom: 2px solid #0056b3;
        display: inline-block;
        padding-bottom: 3px;
        font-size: 16px;
    }

    .footer-new-links ul {
        list-style: none;
        padding: 0;
        margin: 0;
        columns: 2;
        column-gap: 20px;
    }

    .footer-new-links ul li {
        margin-bottom: 8px;
        break-inside: avoid;
    }

    .footer-new-links ul li a {
        text-decoration: none;
        color: #333;
        font-size: 14px;
        transition: color 0.3s ease;
    }

    .footer-new-links ul li a:hover {
        color: #0056b3;
    }

    /* Right Section */
    .footer-new-right {
        text-align: center;
        max-width: 200px;
    }

    .footer-new-right h4 {
        color: #003366;
        margin-bottom: 10px;
        font-size: 16px;
    }

    .footer-new-right img {
        max-width: 120px;
        margin-bottom: 15px;
    }

    .footer-new-right .visitor {
        font-size: 24px;
        font-weight: bold;
        color: #0056b3;
    }

    .footer-new-right p {
        margin: 0;
        font-size: 14px;
        color: #333;
    }

    /* Bottom */
    .footer-new-bottom {
        text-align: center;
        font-size: 14px;
        border-top: 1px solid #ddd;
        padding-top: 10px;
        margin-top: 20px;
        color: #666;
    }

    .footer-new-bottom a {
        color: #0056b3;
        font-weight: bold;
        text-decoration: none;
    }

    .footer-new-bottom a:hover {
        text-decoration: underline;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .footer-new {
            padding: 20px;
        }

        .footer-new-main {
            gap: 20px;
        }

        .footer-new-links ul {
            columns: 1;
        }
    }
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

<footer class="footer-new">
    <!-- Top Social -->
    <div class="footer-new-top">
        <a href="<?php !empty($s3sRedux['footerTwtUrl']) ?? esc_url($s3sRedux['footerTwtUrl']); ?>" target="_blank" title="Twitter">
            <i class="fab fa-twitter"></i>
        </a>

        <?php if (!empty($s3sRedux['footerFbUrl'])): ?>
            <a href="<?php echo esc_url($s3sRedux['footerFbUrl']); ?>" target="_blank" title="Facebook">
                <i class="fab fa-facebook-f"></i>
            </a>
        <?php endif; ?>

        <?php if (!empty($s3sRedux['footerIgUrl'])): ?>
            <a href="<?php echo esc_url($s3sRedux['footerIgUrl']); ?>" target="_blank" title="Instagram">
                <i class="fab fa-instagram"></i>
            </a>
        <?php endif; ?>

        <a href="https://wa.me/8801775383516" target="_blank" title="WhatsApp">
            <i class="fab fa-whatsapp"></i>
        </a>

        <a href="<?php !empty($s3sRedux['footerYtUrl']) ?? esc_url($s3sRedux['footerYtUrl']); ?>" target="_blank" title="YouTube">
            <i class="fab fa-youtube"></i>
        </a>
    </div>

    <div class="footer-new-main">
        <!-- Left -->
        <div class="footer-new-left">
            <?php if (isset($s3sRedux['school-logo']['url']) && !empty($s3sRedux['school-logo']['url'])): ?>
                <img src="<?php echo $s3sRedux['school-logo']['url']; ?>" alt="<?php echo get_bloginfo('name'); ?>">
            <?php else: ?>
                <img src="https://cdn-icons-png.flaticon.com/512/29/29302.png" alt="School Logo">
            <?php endif; ?>

            <h3><?php echo get_bloginfo('name'); ?></h3>

            <?php
            // Fetch contact info from sm_options table
            global $wpdb;
            $institute_phone = get_option('opt_name')['institute_phone'] ?? '';
            $institute_email = get_option('opt_name')['institute_email'] ?? '';
            $institute_address = get_option('opt_name')['institute_address'] ?? '';

            // Fallback to checking sm_options table directly if opt_name is empty
            if (empty($institute_phone) || empty($institute_email) || empty($institute_address)) {
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE 'sm_options'");
                if ($table_exists === 'sm_options') {
                    $option_rows = $wpdb->get_results(
                        "SELECT option_name, option_value FROM sm_options WHERE option_name IN ('institute_phone','institute_email','institute_address')",
                        ARRAY_A
                    );
                    if (!empty($option_rows)) {
                        foreach ($option_rows as $row) {
                            if ($row['option_name'] === 'institute_phone') {
                                $institute_phone = $row['option_value'];
                            }
                            if ($row['option_name'] === 'institute_email') {
                                $institute_email = $row['option_value'];
                            }
                            if ($row['option_name'] === 'institute_address') {
                                $institute_address = $row['option_value'];
                            }
                        }
                    }
                }
            }
            ?>

            <?php if (!empty($institute_phone)): ?>
                <p>📞 Contact: <?php echo esc_html($institute_phone); ?></p>
            <?php endif; ?>

            <?php if (!empty($institute_email)): ?>
                <p>📧 Email: <?php echo esc_html($institute_email); ?></p>
            <?php endif; ?>

            <?php if (!empty($institute_address)): ?>
                <p>📍 Address: <?php echo esc_html($institute_address); ?></p>
            <?php endif; ?>
        </div>

        <!-- Quick Links -->
        <div class="footer-new-links">
            <h4>Quick Links</h4>
            <?php
            if (has_nav_menu('footer-menu')) {
                wp_nav_menu(array(
                    'theme_location' => 'footer-menu',
                    'container' => false,
                    'menu_class' => '',
                    'items_wrap' => '<ul>%3$s</ul>'
                ));
            } else {
            ?>
                <ul>
                    <li><a href="<?php echo home_url('/apply-online'); ?>">Online Admission</a></li>
                    <li><a href="<?php echo home_url('/notice-board'); ?>">Notice Board</a></li>
                    <li><a href="<?php echo home_url('/about-us'); ?>">About Us</a></li>
                    <li><a href="<?php echo home_url('/admission-info'); ?>">Admission Info</a></li>
                    <li><a href="<?php echo home_url('/class-routine'); ?>">Class Routine</a></li>
                    <li><a href="<?php echo home_url('/result'); ?>">Result</a></li>
                    <li><a href="https://dshe.gov.bd/" target="_blank">DSHE</a></li>
                    <li><a href="http://www.banbeis.gov.bd/" target="_blank">BANBEIS</a></li>
                    <li><a href="https://bangladesh.gov.bd/" target="_blank">BD National Portal</a></li>
                    <li><a href="https://moedu.gov.bd/" target="_blank">Ministry of Education</a></li>
                    <li><a href="<?php echo home_url('/contact-us'); ?>">Contact Us</a></li>
                </ul>
            <?php } ?>
        </div>

        <!-- Right -->
        <div class="footer-new-right">
            <h4>Maintained By</h4>
            <a href="https://www.barnomala.com" target="_blank">
                <img src="<?php echo get_template_directory_uri(); ?>/img/logo-bornomala.png"
                    alt="Bornomala"
                    style="max-width: 180px; margin-bottom: 15px;"
                    onerror="this.style.display='none'">
            </a>

            <?php
            // Visitor counter from sm_options table
            $visitor_count = get_visitor_count();
            ?>
            <div class="visitor"><?php echo number_format($visitor_count); ?></div>
            <p>ONLINE VISITOR</p>
        </div>
    </div>

    <!-- Bottom -->
    <div class="footer-new-bottom">
        <?php if (!empty($s3sRedux['copyrightText'])): ?>
            <?php echo wp_kses_post($s3sRedux['copyrightText']); ?>
        <?php else: ?>
            Copyright © <?php echo date('Y'); ?> <b><?php echo get_bloginfo('name'); ?></b>. All Rights Reserved.
        <?php endif; ?>
        <br>
        Developed By <a href="http://www.ms3technology.com.bd" target="_blank">MS3 Technology BD</a> |
        Helpline: <a href="tel:+8801633516400">880 1633-516400</a>
    </div>
</footer>

<button id="backToTop">
    <img width="50" src="<?= get_template_directory_uri() ?>/img/backToTop.png">
</button>


<?php wp_footer(); ?>
</body>

</html>