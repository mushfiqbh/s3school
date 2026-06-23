<?php
/** Template Name: Citizen Charter */

get_header();

// Fetch the latest Citizen Charter post
$args = array(
    'post_type'      => 'post',
    'post_title'     => 'Citizen Charter',
    'posts_per_page' => 1,
    'orderby'        => 'modified',
    'order'          => 'DESC',
    'post_status'    => 'publish',
);

$charter_query = new WP_Query($args);
$pdf_url = '';

if ($charter_query->have_posts()) {
    $charter_query->the_post();
    $content = get_the_content();
    // Extract src from iframe
    if (preg_match('/<iframe[^>]+src=["\']([^"\']+)["\']/i', $content, $matches)) {
        $pdf_url = $matches[1];
    }
    wp_reset_postdata();
}

// Fallback URL if none found
if (empty($pdf_url)) {
    $pdf_url = 'https://seojuri.gov.bd/wp-content/uploads/2026/06/Page-1-1.pdf?#navpanes=0&scrollbar=0&zoom=143';
}
?>

<div class="b-layer-main">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <h2 class="page-title">Citizen Charter</h2>
                
                <div class="pdf-container" style="width: 100%; height: 5000px; border: 1px solid #ddd; border-radius: 4px; overflow: hidden;">
                    <iframe 
                        src="<?php echo esc_url($pdf_url); ?>"
                        width="100%" 
                        height="100%" 
                        style="border: none;"
                        title="Citizen Charter PDF">
                        <p>Your browser does not support PDFs. 
                            <a href="<?php echo esc_url($pdf_url); ?>" target="_blank">Download the PDF</a>
                        </p>
                    </iframe>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .page-title {
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #ddd;
    }
    
    @media screen and (max-width: 768px) {
        .pdf-container {
            height: 70vh;
        }
    }
</style>

<?php get_footer(); ?>
