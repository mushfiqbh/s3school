
	<div class="">
		<div class="headerTop clearfix">
			<div class="container">
				<a href="<?= home_url() ?>" class="pull-left">
					<img width="220" class="img-responsive" src="<?= $s3sRedux['logo_upload']['url']; ?>">
				</a>
				<div class="pull-right">
					<ul class="list-unstyled">
						<li><a href="callto:<?= $s3sRedux['header_phone']; ?>"><?= $s3sRedux['header_phone']; ?></a></li>
						<li><a href="mailto:<?= $s3sRedux['header_email']; ?>"><?= $s3sRedux['header_email']; ?></a></li>
					</ul>

				</div>
			</div>
		</div>
		<!-- ====== MENU =========-->
		<header class="header-transparent wow slideInDown">
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
										    'menu_class'=> 'nav navbar-nav navbar-right'
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