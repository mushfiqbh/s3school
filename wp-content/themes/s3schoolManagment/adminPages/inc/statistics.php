<?php
/*
** Template Name: Admin Statistics
*/
global $wpdb; global $s3sRedux;
$yer = $_GET['syear'] ??  date('Y');
?>

<?php if ( ! is_admin() ) { get_header(); ?>
<div class="b-layer-main">

	<div class="">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
<?php } ?>

<div class="container-fluid maxAdminpages" style="padding-left: 0">

	<p id="theSiteURL" class="hidden"><?= get_template_directory_uri() ?></p>
	<div class="row">
	
	
		<div class="col-md-6">
			<form class="form-inline">
				<input type="hidden" name="page" value="student">
				<input type="hidden" name="option" value="statistics">
				<div class="form-group">
					<select class="form-control" name="syear">
						<?php 
							$syears = $wpdb->get_results( "SELECT stdCurntYear FROM ct_student GROUP BY stdCurntYear ORDER BY stdCurntYear DESC");
							foreach ($syears as $key => $syear) {
								echo "<option>".$syear->stdCurntYear."</option>";
							}
						?>
					</select>
				</div>
				<div class="form-group">
					<button type="submit" class="btn btn-success">View</button>
				</div>
			</form>
		</div>
		<div class="col-md-6 text-right">
  		<button onclick="print('printArea')" class="btn btn-primary">Print</button>
		</div>
	


	  <div id="printArea" class="col-md-12 printBG">
	  	<div style="max-width: 8.27in; margin: auto;">
	  		<link rel="stylesheet" href="<?= get_template_directory_uri() ?>/css/tabulationSheet.css" />
		  	<style type="text/css"> @page { size: auto;  margin: 0px; } th, td { text-align: center; vertical-align: middle !important;} </style>
				<div style="text-align: center; position: relative;">
	  			<img height="80px" style="position: absolute;left: 10px;top: 10px" src="<?= $s3sRedux['instLogo']['url'] ?>">
					<h2 style="margin: 5px 0 5px 0;"><b><?= $s3sRedux['institute_name'] ?></b></h2>
		  		<p style="color:#2b5591; font-size: 14px; margin: 0;"><?= $s3sRedux['institute_address'] ?></p>
		  		<h3>Students Information <?= $yer ?></h3>
	  		</div>
		  	<?php

		  		$classQuery = $wpdb->get_results( "SELECT count(*) AS total,sum(case when stdGender = 1 then 1 else 0 end) AS boys, sum(case when stdGender = 0 then 1 else 0 end) AS girls FROM ct_student WHERE stdCurntYear = '$yer'")[0];
		  		
		  	?>

			  <div class="printArea">
			  	<table class="table table-bordered">
			  		<tr>
			  			<th>Total Students</th>
			  			<th colspan="2">Boys</th>
			  			<th colspan="2">Girls</th>
			  			<th colspan="2">Other</th>
			  		</tr>
			  		<tr>
			  			<th><?= $classQuery->total ?></th>
			  			<th colspan="2"><?= $classQuery->boys ?></th>
			  			<th colspan="2"><?= $classQuery->girls ?></th>
			  			<th colspan="2"><?= $classQuery->total - ($classQuery->boys + $classQuery->girls) ?></th>
			  		</tr>
			  	</table>

			  	<table class="table table-bordered">
			  		<?php
			  			$classes = $wpdb->get_results("SELECT classid,className,COUNT(*) AS totalstd FROM `ct_studentinfo` LEFT JOIN ct_class ON classid = infoClass WHERE `infoYear` = '$yer' GROUP BY infoClass");
			  		
			  			foreach ($classes as $key => $class) {
			  				$classid = $class->classid;
			  				$sections = $wpdb->get_results("SELECT sectionid,sectionName FROM `ct_section` WHERE sectionid IN (SELECT infoSection FROM `ct_studentinfo` WHERE infoYear = '$yer' AND infoClass = $classid GROUP BY infoSection) ORDER BY sectionName");
			  				?>
					  		<tr>
					  			<th rowspan="2"><?= $class->className ?><br>(<?= $class->totalstd ?>)</th>
					  			<?php foreach ($sections as $key => $section) {  ?>
						  			<th colspan="2"><?= $section->sectionName ?></th>
						  		<?php } ?>
					  		</tr>
					  		<tr>
						  		<?php foreach ($sections as $key => $section) {  ?>
					  				<?php
					  				
					  					$secID = $section->sectionid;
					  					$secTot = $wpdb->get_results( "SELECT count(*) AS total,sum(case when stdGender = 1 then 1 else 0 end) AS boys, sum(case when stdGender = 0 then 1 else 0 end) AS girls FROM ct_student LEFT JOIN ct_studentinfo ON infoStdid = studentid AND stdCurntYear = infoYear AND stdCurrentClass = infoClass WHERE stdCurntYear = '$yer' AND stdCurrentClass = $classid AND infoSection = $secID")[0];
					  				?>
						  			<td>Boy <?= $secTot->boys ?></td>
						  			<td>Girl <?= $secTot->girls ?></td>
						  		<?php } ?>
					  		</tr>
				  			<?php
				  		}
				  	?>
			  	</table>

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

<script type="text/javascript">

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