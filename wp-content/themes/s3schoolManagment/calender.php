<?php
/**
 * Template Name: Academic Calender
 */

get_header(); ?>

	<link rel="stylesheet" href="css/calendar.css"/>

	<div class="b-page-wrap">
		<div class="b-page-content with-layer-bg">
			<div class="b-layer-big otherPageBg">
				<div class="layer-big-bg page-layer-big-bg">
					<div class="layer-content-big text-center">
						<h2>Academic Calender</h2>
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

								<h3 class="inherit-title"><b>Academic Calender</b></h3>
								<div class="about-additional-text">

									<div class="monthly" id="mycalendar"></div>
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

	<?php
		function pageScript(){
			?>
			<script src="js/calendar.js"></script>
			<script type="text/javascript">
				var sampleEvents = {
					"monthly": [
						{
						"id": 4,
						"name": "Test single day with time",
						"startdate": "2018-02-07",
						"enddate": "2018-02-07",
						"starttime": "12:00",
						"endtime": "02:00",
						"color": "#46a1c4",
						"url": ""
						},
						{
						"id": 5,
						"name": "Test splits month",
						"startdate": "2018-02-25",
						"enddate": "2018-02-26",
						"starttime": "12:00",
						"endtime": "02:00",
						"color": "#46a1c4",
						"url": ""
						},
					]
				};
				$(window).load( function() {
					$('#mycalendar').monthly({
						mode: 'event',
						dataType: 'json',
						events: sampleEvents
					});
				});
			</script>
			<?php
		}
	?>

<?php get_footer(); ?>