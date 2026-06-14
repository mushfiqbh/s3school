<?php
/*
** Template Name: Change Frontend Password
*/
 global $wpdb; global $s3sRedux; 
?>


<?php if ( ! is_admin() ) { get_header(); ?>
<div class="b-layer-main">

	<div class="">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
<?php } ?>
<p id="theSiteURL" class="hidden"><?= get_template_directory_uri() ?></p>
<div class="container-fluid maxAdminpages" style="padding-left: 0">
	<div class="row">

		<div class="col-md-12">
			<div class="panel panel-info">
			  <div class="panel-heading">
			  	<h3>
			  		Change Password<br>
			  	</h3>
			  </div>
			  <div class="panel-body">
				<?= do_shortcode('[change_password_form]') ?>

			  </div>
			</div>
		</div>


		
	</div>
</div>

<?php if ( ! is_admin() ) { ?>
				</div>
			</div>
		</div>
	</div>
</div>
<?php get_footer(); } ?>
