<?php

/**
 * Template Name: Contact Us
 * Description: Modern contact page with form and information
 */

get_header();
global $s3sRedux;
?>

<style>
    /* Contact Page Styles */
    .contact-hero {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        padding: 80px 0 60px;
        color: white;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .contact-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="100" height="100" patternUnits="userSpaceOnUse"><path d="M 100 0 L 0 0 0 100" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grid)"/></svg>');
        opacity: 0.3;
    }

    .contact-hero-content {
        position: relative;
        z-index: 1;
    }

    .contact-hero h1 {
        font-size: 48px;
        font-weight: 700;
        margin-bottom: 15px;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
    }

    .contact-hero p {
        font-size: 18px;
        opacity: 0.95;
        max-width: 600px;
        margin: 0 auto;
    }

    .contact-breadcrumb {
        margin-top: 20px;
        font-size: 14px;
    }

    .contact-breadcrumb a {
        color: rgba(255, 255, 255, 0.9);
        text-decoration: none;
        transition: all 0.3s;
    }

    .contact-breadcrumb a:hover {
        color: white;
        text-decoration: underline;
    }

    .contact-section {
        padding: 80px 0;
        background: #f8f9fa;
    }

    .contact-info-cards {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 30px;
        margin-bottom: 60px;
    }

    .contact-info-card {
        background: white;
        padding: 40px 30px;
        border-radius: 15px;
        text-align: center;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        border: 2px solid transparent;
        position: relative;
        overflow: hidden;
    }

    .contact-info-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, #667eea, #764ba2);
        transform: scaleX(0);
        transition: transform 0.4s;
    }

    .contact-info-card:hover::before {
        transform: scaleX(1);
    }

    .contact-info-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 40px rgba(102, 126, 234, 0.2);
        border-color: #667eea;
    }

    .contact-icon {
        width: 80px;
        height: 80px;
        margin: 0 auto 25px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.4s;
    }

    .contact-info-card:hover .contact-icon {
        transform: rotateY(360deg);
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
    }

    .contact-icon i {
        font-size: 36px;
        color: white;
    }

    .contact-info-card h3 {
        font-size: 22px;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 15px;
    }

    .contact-info-card p {
        color: #7f8c8d;
        font-size: 15px;
        line-height: 1.8;
        margin: 0;
    }

    .contact-info-card a {
        color: #667eea;
        text-decoration: none;
        transition: all 0.3s;
        font-weight: 500;
    }

    .contact-info-card a:hover {
        color: #764ba2;
        text-decoration: underline;
    }

    .contact-form-wrapper {
        background: white;
        padding: 50px;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        position: relative;
        overflow: hidden;
    }

    .contact-form-wrapper::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(90deg, #667eea, #764ba2, #667eea);
        background-size: 200% 100%;
        animation: gradientShift 3s ease infinite;
    }

    @keyframes gradientShift {

        0%,
        100% {
            background-position: 0% 50%;
        }

        50% {
            background-position: 100% 50%;
        }
    }

    .contact-form-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .contact-form-header h2 {
        font-size: 32px;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 10px;
    }

    .contact-form-header p {
        color: #7f8c8d;
        font-size: 16px;
    }

    .form-group {
        margin-bottom: 25px;
    }

    .form-group label {
        display: block;
        font-size: 15px;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 8px;
    }

    .form-group label .required {
        color: #e74c3c;
        margin-left: 3px;
    }

    .form-control {
        width: 100%;
        padding: 15px 20px;
        border: 2px solid #e0e6ed;
        border-radius: 10px;
        font-size: 15px;
        transition: all 0.3s;
        background: #f8f9fa;
    }

    .form-control:focus {
        outline: none;
        border-color: #667eea;
        background: white;
        box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
    }

    .form-control::placeholder {
        color: #b0b7c3;
    }

    textarea.form-control {
        min-height: 150px;
        resize: vertical;
        font-family: inherit;
    }

    .submit-btn {
        width: 100%;
        padding: 18px 40px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        text-transform: uppercase;
        letter-spacing: 1px;
        position: relative;
        overflow: hidden;
    }

    .submit-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.2);
        transition: left 0.5s;
    }

    .submit-btn:hover::before {
        left: 100%;
    }

    .submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
    }

    .submit-btn:active {
        transform: translateY(0);
    }

    .map-section {
        margin-top: 60px;
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
    }

    .map-section iframe {
        width: 100%;
        height: 450px;
        border: none;
        display: block;
    }

    .form-message {
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: none;
    }

    .form-message.success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .form-message.error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .contact-hero {
            padding: 60px 0 40px;
        }

        .contact-hero h1 {
            font-size: 32px;
        }

        .contact-section {
            padding: 50px 0;
        }

        .contact-info-cards {
            gap: 20px;
        }

        .contact-form-wrapper {
            padding: 30px 20px;
        }

        .contact-form-header h2 {
            font-size: 26px;
        }
    }
</style>

<!-- Hero Section -->
<section class="contact-hero">
    <div class="contact-hero-content">
        <div class="container">
            <h1><i class="fa fa-envelope-o"></i> Contact Us</h1>
            <p>We'd love to hear from you! Get in touch with us for any queries or assistance.</p>
            <div class="contact-breadcrumb">
                <a href="<?php echo home_url(); ?>"><i class="fa fa-home"></i> Home</a> / <span>Contact</span>
            </div>
        </div>
    </div>
</section>

<!-- Contact Information & Form Section -->
<section class="contact-section">
    <div class="container">
        <!-- Contact Info Cards -->
        <div class="contact-info-cards">
            <!-- Phone Card -->
            <div class="contact-info-card">
                <div class="contact-icon">
                    <i class="fa fa-phone"></i>
                </div>
                <h3>Phone Number</h3>
                <p><?php
                    if (!empty($s3sRedux['institute_phone'])) {
                        echo esc_html($s3sRedux['institute_phone']);
                    } else {
                        echo '+880 1775-383516';
                    } ?>
                </p>
            </div>

            <!-- Email Card -->
            <div class="contact-info-card">
                <div class="contact-icon">
                    <i class="fa fa-envelope"></i>
                </div>
                <h3>Email Address</h3>
                <p>
                    <?php
                    if (!empty($s3sRedux['institute_email'])) {
                        echo '<a href="mailto:' . esc_html($s3sRedux['institute_email']) . '">' . esc_html($s3sRedux['institute_email']) . '</a>';
                    } else {
                        echo '<a href="mailto:ziisc@gmail.com">ziisc@gmail.com</a>';
                    } ?>
                </p>
            </div>

            <!-- Working Hours Card -->
            <div class="contact-info-card">
                <div class="contact-icon">
                    <i class="fa fa-clock-o"></i>
                </div>
                <h3>Working Hours</h3>
                <p>
                    Saturday - Thursday<br>
                    <strong>10:00 AM - 4:00 PM</strong><br>
                    <small>Friday: Closed</small>
                </p>
            </div>
        </div>

        <!-- Contact Form -->
        <div class="contact-form-wrapper">
            <div class="contact-form-header">
                <h2><i class="fa fa-paper-plane-o"></i> Send us a Message</h2>
                <p>Fill out the form below and we'll get back to you as soon as possible</p>
            </div>

            <div class="form-message" id="formMessage"></div>

            <form id="contactForm" method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
                <input type="hidden" name="action" value="submit_contact_form">
                <?php wp_nonce_field('contact_form_nonce', 'contact_nonce'); ?>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="contact_name">Your Name <span class="required">*</span></label>
                            <input type="text" id="contact_name" name="contact_name" class="form-control"
                                placeholder="Enter your full name" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="contact_email">Email Address <span class="required">*</span></label>
                            <input type="email" id="contact_email" name="contact_email" class="form-control"
                                placeholder="your.email@example.com" required>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="contact_phone">Phone Number</label>
                            <input type="tel" id="contact_phone" name="contact_phone" class="form-control"
                                placeholder="+880 1XXX-XXXXXX">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="contact_subject">Subject <span class="required">*</span></label>
                            <input type="text" id="contact_subject" name="contact_subject" class="form-control"
                                placeholder="What is this regarding?" required>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="contact_message">Your Message <span class="required">*</span></label>
                    <textarea id="contact_message" name="contact_message" class="form-control"
                        placeholder="Write your message here..." required></textarea>
                </div>

                <button type="submit" class="submit-btn">
                    <i class="fa fa-paper-plane"></i> Send Message
                </button>
            </form>
        </div>

        <!-- Google Map Section -->
        <div class="map-section">
            <?php
            $institute_title = !empty($s3sRedux['institute_name']) ? $s3sRedux['institute_name'] : get_bloginfo('name');
            $q = urlencode($institute_title);
            ?>
            <iframe
                src="https://maps.google.com/maps?q=<?php echo $q; ?>&t=&z=13&ie=UTF8&iwloc=&output=embed"
                allowfullscreen=""
                loading="lazy">
            </iframe>
        </div>

    </div>
</section>

<script>
    jQuery(document).ready(function($) {
        $('#contactForm').on('submit', function(e) {
            e.preventDefault();

            var formData = $(this).serialize();
            var submitBtn = $(this).find('.submit-btn');
            var formMessage = $('#formMessage');

            // Disable submit button
            submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Sending...');

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        formMessage.removeClass('error').addClass('success')
                            .html('<i class="fa fa-check-circle"></i> ' + response.data.message)
                            .slideDown();
                        $('#contactForm')[0].reset();
                    } else {
                        formMessage.removeClass('success').addClass('error')
                            .html('<i class="fa fa-exclamation-circle"></i> ' + response.data.message)
                            .slideDown();
                    }

                    // Re-enable submit button
                    submitBtn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Send Message');

                    // Hide message after 5 seconds
                    setTimeout(function() {
                        formMessage.slideUp();
                    }, 5000);
                },
                error: function() {
                    formMessage.removeClass('success').addClass('error')
                        .html('<i class="fa fa-exclamation-circle"></i> An error occurred. Please try again.')
                        .slideDown();
                    submitBtn.prop('disabled', false).html('<i class="fa fa-paper-plane"></i> Send Message');
                }
            });
        });
    });
</script>

<?php get_footer(); ?>