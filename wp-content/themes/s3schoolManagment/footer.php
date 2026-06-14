<?php global $s3sRedux; ?>

	<div class="footerTop text-center" style="background: <?= $s3sRedux['footerTopBg'] ?>;color: <?= $s3sRedux['footerTopText'] ?>">
		<div class="container">
			<div class="col-sm-4">
				<h3>ঠিকানা</h3>
				<?= $s3sRedux['footerAddress'] ?>
			</div>

			<div class="col-sm-4">
				<h3>যোগাযোগ</h3>
				<?= $s3sRedux['footerContact'] ?>
			</div>

			<div class="col-sm-4">
				<img class="img-responsive" src="<?= get_template_directory_uri() ?>/img/scholars.png">
			</div>
		</div>
	</div>

	<footer style="background: <?= $s3sRedux['footerBtmBg'] ?>;color: <?= $s3sRedux['footerBtmText'] ?>">
		<div class="container">
			<div class="row">
				<div class="col-md-6">
					<?= $s3sRedux['copyrightText'] ?>
				</div>
				<div class="col-md-6 text-right">
					Developed by <a href="http://www.ms3technology.com.bd" style="text-decoration: underline;"><b>MS3 Technology BD Pvt. Ltd</b></a>
				</div>

			</div>
		</div>
	</footer>

	<button  id="backToTop">
		<img width="50" src="<?= get_template_directory_uri() ?>/img/backToTop.png">
	</button>


	<?php wp_footer(); ?>
</body>
</html>