<?php
/**
 * Template Name: Speech
 */

get_header();

// Ensure options are loaded from sm_options table
global $wpdb, $s3sRedux;

// Load options from sm_options table if available
$sm_table = 'sm_options';
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$sm_table'");

if ($table_exists === $sm_table) {
    $option_rows = $wpdb->get_results("SELECT option_name, option_value FROM sm_options", ARRAY_A);
    
    if (!empty($option_rows)) {
        foreach ($option_rows as $row) {
            $option_name = $row['option_name'];
            $option_value = $row['option_value'];
            
            // Try to unserialize if it's a serialized value
            $unserialized = @unserialize($option_value);
            if ($unserialized !== false || $option_value === 'b:0;') {
                $s3sRedux[$option_name] = $unserialized;
            } else {
                $s3sRedux[$option_name] = $option_value;
            }
        }
    }
}

// Fallback to Redux options if sm_options doesn't have the values
if (empty($s3sRedux['aboutTitelText'])) {
    $redux_options = get_option('opt_name', array());
    if (!empty($redux_options)) {
        $s3sRedux = array_merge($redux_options, $s3sRedux ?: array());
    }
}

?>

<div class="b-page-wrap">
	<div class="b-page-content with-layer-bg">
		<div class="b-layer-big otherPageBg">
			<div class="layer-big-bg page-layer-big-bg">
				<div class="layer-content-big text-center">
					<h1><b>
						<?php
							if(isset($_GET['cont'])){
								if ($_GET['cont'] == 'headmaster') {
									echo $s3sRedux['homeHeadmasterTitle'];
								}elseif ($_GET['cont'] == 'chairman') {
									echo $s3sRedux['homeChairmanTitle'];
								}else{
									echo $s3sRedux['aboutTitelText'];
								}
							}else{
								echo $s3sRedux['aboutTitelText'];
							}
						?>
					</b></h1>
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
					<div class="b-blog-items-holder wow slideInLeft">
						<div class="clearfix aboutUsPageContent">
							
							<div class="about-additional-text">
								<?php
									if(isset($_GET['cont'])){
										if ($_GET['cont'] == 'headmaster') {
											echo $s3sRedux['homeHeadmaster'];
										}elseif ($_GET['cont'] == 'chairman') {
											echo $s3sRedux['homeChairman'];
										}else{
											echo $s3sRedux['aboutUsText'];
										}
									}else{
										echo $s3sRedux['aboutUsText'];
									}
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
<?php get_footer();
