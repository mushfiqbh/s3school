<?php
	global $s3sRedux, $wp, $post;
	if (is_object($post)) {
  	$current = $post->post_name;
  	$allpage = array("frontend-admin", "admin-applicants","admin-student","admin-attendance","admin-class","admin-section","admin-group","admin-examattendance","admin-admitcard","admin-seatcard","admin-result","admin-meritlist","admin-faillist","admin-tabulation","admin-promotion","admin-idcard","admin-testimonial","admin-tc","admin-revenue","admin-studentfee","admin-sms");
  	if(in_array($current, $allpage) && !is_user_logged_in()){
  		wp_redirect( home_url() ."/login/");exit;
  	}
	}
?>
<!DOCTYPE html>

<html <?php language_attributes(); ?>>
<head>

	<meta charset="<?php bloginfo( 'charset' ); ?>"/>
	<meta http-equiv="x-ua-compatible" content="ie=edge"/>
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	<meta content="telephone=no" name="format-detection"/>
	<meta name="HandheldFriendly" content="true"/>
	<link rel="icon" type="image/x-icon" href="favicon.ico"/>

	<?php wp_head(); ?>

</head>

<body <?php body_class(); ?> data-url='<?= get_template_directory_uri() ?>'>

	<?php

		if ($s3sRedux['topbarOption'] == 1){
			include_once 'inc/topbar.php';
		}
		if ($s3sRedux['headerStyle'] == 1 || $s3sRedux['headerStyle'] == "") {
			include_once 'inc/header.php';
		}elseif ($s3sRedux['headerStyle'] == 2){
			include_once 'inc/header2.php';
		}elseif ($s3sRedux['headerStyle'] == 3){
			include_once 'inc/header3.php';
		}elseif ($s3sRedux['headerStyle'] == 4){
			include_once 'inc/header4.php';
		}elseif ($s3sRedux['headerStyle'] == 5){
			include_once 'inc/header5.php';
		}
	?>


	<div class="socialShare">
	  <ul class="list-unstyled">
	    <li title="Share With Facebook">
	      <a target="_blank" href="<?= home_url($s3sRedux['footerFbUrl']); ?>" class="fa fa-facebook"></a>
	    </li>
	    <li title="Share With Youtube" style="background:red;">
	      <a target="_blank" href="<?= home_url($s3sRedux['footerTwtUrl']); ?>" class="fa fa-youtube"></a>
	    </li>
	    <li title="Share With Google+">
	      <a target="_blank" href="https://plus.google.com/share?url=<?= home_url( $wp->request ) ?>" class="fa fa-google"></a>
	    </li>
	    <li title="Share With Linkedin">
	      <a target="_blank" href="http%3A//www.linkedin.com/shareArticle?mini=true&amp;url=<?= home_url( $wp->request ) ?>&amp;title=S3schools&amp;summary=&amp;source=" class="fa fa-linkedin"></a>
	    </li>
	    <li title="Share With Pinterest">
	      <a target="_blank" href="https://pinterest.com/pin/create/button/?url=&amp;media=<?= home_url( $wp->request ) ?>&amp;description=" class="fa fa-pinterest"></a>
	    </li>
	  </ul>
	</div>