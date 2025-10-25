
	<div class="headerBiglogo">
<style>
    .applyLink a {
        float: left;
        color: #EAEAEA;
        width: 140px;
        margin: 38px 0 0 0;
        font-size:17px;
        padding: 5px;
        line-height: 25px;
        border-radius: 15px;
        text-align: center;
        text-decoration: none;
        background: #00213C;
}
.quizLink a {
        float: left;
        color: #EAEAEA;
        width: 150px;
        margin: 38px 0 0 0;
        font-size:17px;
        padding: 5px;
        line-height: 25px;
        border-radius: 15px;
        text-align: center;
        text-decoration: none;
        background: #00213C;
}
.apply-margin {
   margin-top: 60px !important; 
}
.daycare-margin {
   margin-top: 20px !important; 
   margin-left: -140px !important; 
}
.quiz-margin {
   margin-top: 3px !important; 
   margin-left: 63px !important; 
}
    
</style>
		<div class="headerTop clearfix" style="background: <?= $s3sRedux['headerBgColor']; ?>;color: <?= $s3sRedux['headerTextColor']; ?>">

			<div class="container">
				<div class="row">
					<div class="col-md-8 col-sm-8">
						<a href="<?= home_url() ?>" class="pull-left">
							<img width="<?= $s3sRedux['logo_width']; ?>" class="img-responsive" src="<?= $s3sRedux['logo_upload']['url']; ?>">
						</a>
						
					</div>

					<div class="col-md-4 col-sm-4">
					    
						<ul class="list-unstyled header-list">
						    <li class="applyLink">
						        <a target="_blank" class="text-left apply-margin" href="#">Apply Online</a>
						    </li>
							<li class="text-right"><?= $s3sRedux['header_phone']; ?> <?= $s3sRedux['header_email']; ?></li>


							<li class="text-right" style="clear: both;"><a>Total Visitors - <?= vcp_get_visit_count('T')+500 ?></a></li>
						</ul>
					</div>

			</div>

		</div>

		<!-- ====== MENU =========-->

		<header class="header-transparent" style="background: <?= $s3sRedux['menuBgColor']; ?>;">

			<style type="text/css">
				.sub-menu{
					background: <?= $s3sRedux['menuBgColor']; ?>;
				}
				.navbar-default .navbar-nav > li > a,.sub-menu a,.menu-item-has-children::after{
					color: <?= $s3sRedux['menuTextColor']; ?> !important;
				}

			</style>

			<div class="container">

				<div class="row">

					<div class="col-md-12">

						<div class="header-navibox-2">

							<nav class="navbar navbar-default">

								<div class="container-fluid">

									<div class="navbar-header">

										<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">

											<span class="sr-only">Menu</span>

											<span class="icon-bar"></span>

											<span class="icon-bar"></span>

											<span class="icon-bar"></span>

										</button>



									</div>

									<div id="navbar" class="navbar-collapse collapse">

										<?php

										  wp_nav_menu(array(

										    'theme_location' => 'mainMenu',

										    'container' => '',

										    'menu_class'=> 'nav navbar-nav'

										  ));

										?>



									</div>

								</div>

							</nav>

						</div>

					</div>

				</div>

			</div>

		</header>

	</div>