<?php
/**
 * Template Name: Speech
 */

get_header();

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
