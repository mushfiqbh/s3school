

	<div class="">
		<!-- ====== MENU =========-->
		<header class="header-transparent">
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
										<a href="<?= home_url() ?>">
											<img width="220" class="img-responsive" src="<?= $s3sRedux['logo_upload']['url']; ?>">
										</a>
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