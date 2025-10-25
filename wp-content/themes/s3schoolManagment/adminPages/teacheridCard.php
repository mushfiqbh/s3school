<?php 
/*
** Template Name: Teacher ID card
*/
	global $wpdb; global $s3sRedux;
	$conStyle0 = "margin: 0; line-height: 1;margin-bottom:3px;";
	$conStyle = "$conStyle0 font-size: 12px;";
	$conStyle1 = "$conStyle0 font-size: 11px;";
	$conStyle2 = "$conStyle0 font-size: 13px;";
?>

<?php if ( ! is_admin() ) { get_header(); ?>
<div class="b-layer-main">

	<div class="">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
<?php } ?>

<div class="container-fluid maxAdminpages" style="padding-left: 0">
	<h2>ID Card Management
		<?php if (!isset($_GET['side'])) { ?>
			<a class="pull-right btn btn-primary" href="<?= admin_url() ?>admin.php?page=idcard&side=back">Back Side</a>
		<?php } ?>
		<a class="pull-right btn btn-primary" href="<?= admin_url() ?>admin.php?page=teacheridcard">Teacher ID</a>
		<a class="pull-right btn btn-primary" href="<?= admin_url() ?>admin.php?page=idcard">Student ID</a>
		
	</h2><br>

	<div class="row">

		<div class="col-md-12">
			<div class="panel panel-info">
			  <div class="panel-heading"><h3>Teacher ID Card<br><small>Print Id Card</small></h3></div>
			  <div class="panel-body">

					<form class="form-inline" action="" method="GET">
						<input type="hidden" name="page" value="teacheridcard">

						<div class="form-group">
	            <label>Teacher Name</label>
	            <input type="text" name="tcname" class="form-control">
	          </div>

						<div class="form-group" id="design">
							Design 
							<label class="hoverShowImg">
								<img class="hoverImg" width="200" src="<?php echo get_template_directory_uri()."/img/idDesign1.PNG" ?>">
								<label class="labelRadio">
									<input type="radio" name="design" value="1" checked>1
								</label>
								<img width="20" src="<?php echo get_template_directory_uri()."/img/idDesign1.PNG" ?>">
							</label>
							<label class="hoverShowImg">
								<img class="hoverImg" width="200" src="<?php echo get_template_directory_uri()."/img/idDesign2.PNG" ?>">
								<label class="labelRadio">
									<input type="radio" name="design" value="2">2
								</label>
								<img width="20" src="<?php echo get_template_directory_uri()."/img/idDesign2.PNG" ?>">
							</label>
						</div>

						<div class="form-group">
							<input type="submit" name="creatId" value="Create ID" class="btn btn-primary">
						</div>

					</form>

			  </div>
			</div>
		</div>

		<?php

		if(isset($_GET['tcname'])){ ?>
 <?php if(current_user_can('administrator')) {?>
			<div class="col-md-12">
	  		<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>
			</div>
 <?php } ?>
		  <div id="printArea" class="col-md-12 printBG">


			  <div class="printArea" style="text-align: center;">
					<style type="text/css"> 
						@page { size: auto !important;  margin: 0px !important; }
			  		*{
						  -webkit-print-color-adjust: exact !important;
						  color-adjust: exact !important;
						  line-height: 1;
			  		}
			  	</style>
			  	<?php

			  		$tcrname = isset($_GET['tcname']) ? $_GET['tcname'] : '';
			  		
		  			$query = "SELECT * FROM `ct_teacher`";
			  		$query .= ($tcrname != '') ? " WHERE teacherName LIKE '%$tcrname%'" :'';
			  		$query .= " ORDER BY `teacherName` ASC";
			  		$groupsBy = $wpdb->get_results( $query );
				  	

			  		if($groupsBy){

							foreach ($groupsBy as $value) {
								if(!isset($_GET['design']) || $_GET['design'] == 1){
									
									?>

										<div style="width: 5.3cm;height: 8.3cm;background: #eeeeee;display: inline-block;margin: 5px;border-radius: 10px;overflow: hidden;">

								  		<div style="text-align: center;background: #44c1e5;padding: 10px;"	>

								  			<h3 style="margin: 0;font-size: 15px;"><?= $s3sRedux['institute_name'] ?></h3>
								  			<p style="margin: 0;font-size: 12px;line-height: 1"><?= $s3sRedux['institute_address'] ?></p>

								  		</div>

								  		<div style="padding: 6px;text-align: center;">
								  			<div class="text-center">
								  				<img alt="Teacher_Image" width="70" height="80" src="<?php echo ($value->teacherImg != "") ? $value->teacherImg : get_template_directory_uri()."/img/No_Image.jpg" ?>">
								  			</div>

								  			<span style="text-align: center;margin: 5px 0;font-size:12px;overflow: hidden;letter-spacing: -1px;display: block;"><b><?= $value->teacherName ?></b></span>
								  			<p style="<?= $conStyle ?>"><?= $value->teacherDesignation ?></p>
								  			<p style="<?= $conStyle ?>"><b>ID No:</b> <?= $value->teacherMpo ?></p>
								  			<p style="<?= $conStyle ?>"><b>Father:</b> <?= $value->teacherFather ?></p>
								  			<p style="<?= $conStyle ?>"><b>Joining:</b> <?= $value->teacherJoining ?></p>
								  			<p style="<?= $conStyle ?>"><b>Birth Date:</b> <?= $value->teacherBirth ?></p>
								  			<p style="<?= $conStyle ?>"><b>Blood Group:</b> <?= $value->teacherBlood ?></p>
								  			<p style="<?= $conStyle ?>"><b>Phone:</b> <?= $value->teacherPhone ?></p>

								  			<div style="text-align: center;">
								  				<img align="Principal_Signature" style="display: block; margin: 5px auto 0;" width="60" src="<?= $s3sRedux['principalSign']['url'] ?>">
								  			</div>

								  			<span style="font-size: 10px;"><?= $s3sRedux['inst_head_title'] ?> signature</span>

								  		</div>
								  	</div>

									<?php
								}else{
									?>

										<div style="width: 5.3cm;height: 8.3cm;background: #eeeeee;display: inline-block;margin: 5px;border-radius: 10px;overflow: hidden;">

								  		<div style="text-align: center;background: #44c1e5;padding: 10px;"	>

								  			<h3 style="margin: 0;font-size: 17px;"><?= $s3sRedux['institute_name'] ?></h3>
								  			<p style="margin: 0;font-size: 12px;line-height: 1"><?= $s3sRedux['institute_address'] ?></p>

								  		</div>

								  		<div style="padding: 6px;text-align: center;">
								  			<div class="text-center">
								  				<img alt="Teacher_Image" width="70" height="80" src="<?php echo ($value->teacherImg != "") ? $value->teacherImg : get_template_directory_uri()."/img/No_Image.jpg" ?>">
								  			</div>

								  			<span style="text-align: center;margin: 5px 0;font-size:13px;overflow: hidden;letter-spacing: -1px;display: block;"><b><?= $value->teacherName ?></b></span>
								  			<p style="<?= $conStyle1 ?>"><?= $value->teacherSQuali ?></p>
								  			<p style="<?= $conStyle2 ?>"><b><?= $value->teacherDesignation ?></b></p>
								  			<p style="<?= $conStyle ?>"><b>Index No:</b> <?= $value->teacherMpo ?></p>
								  			<p style="<?= $conStyle ?>"><b>Date of Birth:</b> <?= $value->teacherBirth ?></p>
								  			<p style="<?= $conStyle ?>"><b>Joining:</b> <?= $value->teacherJoining ?></p>
								  			<p style="<?= $conStyle ?>"><b>Blood Group:</b> <?= $value->teacherBlood ?></p>
								  			<p style="<?= $conStyle ?>"><b>Phone:</b> <?= $value->teacherPhone ?></p>

								  		</div>
								  	</div>

									<?php
								}
							}

						}else{
							echo "<h3 class='text-center'>No Student Found</h3>";
						}
			  	?>		  	

			  </div>
		  </div>

		<?php } ?>

	</div>
</div>

<?php if ( ! is_admin() ) { ?>
				</div>
			</div>
		</div>
	</div>
</div>
<?php get_footer(); } ?>

<script type="text/javascript">
	(function($) {
		$('#resultClass').change(function() {
	    var $siteUrl = '<?= get_template_directory_uri() ?>';

	    $.ajax({
	      url: $siteUrl+"/inc/ajaxAction.php",
	      method: "POST",
	      data: { class : $(this).val(), type : 'getYears' },
	      dataType: "html"
	    }).done(function( msg ) {
	      $( "#resultYear" ).html( msg );
	      $( "#resultYear" ).prop('disabled', false);
	    });

	    $.ajax({
	      url: $siteUrl+"/inc/ajaxAction.php",
	      method: "POST",
	      data: { class : $(this).val(), type : 'getSection' },
	      dataType: "html"
	    }).done(function( msg ) {
	      $( "#resultSection" ).html( msg );
	      $( "#resultSection" ).prop('disabled', false);
	    });
	  });
  })( jQuery );

	function print(divId) {
    var printContents = document.getElementById(divId).innerHTML;
    w = window.open();
    w.document.write(printContents);
    w.document.write('<scr' + 'ipt type="text/javascript">' + 'window.onload = function() { window.print(); window.close(); };' + '</sc' + 'ript>');
    w.document.close(); // necessary for IE >= 10
    w.focus(); // necessary for IE >= 10
    return true;
  }

</script>