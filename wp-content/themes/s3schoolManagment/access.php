<?php
/**
* Template Name: Access Controll
*/
global $wpdb; $updateval = array();

$access = $wpdb->get_results( "SELECT * FROM ct_access WHERE acid = 1" );
foreach ($access[0] as $key => $value) {
	if($key != 'acid'){
		if (isset($_POST['accessctrl'])) {
			$updateval[$key] = isset($_POST[$key]) ? 1 : 0;
			$$key = isset($_POST[$key]) ? 'checked' : '';
		}else{
			$$key = $value == 1 ? 'checked' : '';
		}
	}
}

/*=================
	Change Access
=================*/
if (isset($_POST['accessctrl'])){
	$update = $wpdb->update( 'ct_access',$updateval,array( 'acid' => 1)	);
	$message = ms3message($update, 'Updated');
}
?>


<?php if ( ! is_admin() ) { get_header(); ?>
<div class="b-layer-main">

	<div class="">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
<?php } ?>


<div class="container-fluid maxAdminpages" style="padding-left: 0">

	<!-- Show Status message -->
  <?php if(isset($message)){ ms3showMessage($message); } ?>
  <style type="text/css">.labelRadio{min-width: 25%;}</style>
	<div class="row">
		<div class="col-md-12">
			<div class="panel panel-info">
			  <div class="panel-heading"><h3>Access Controll<br><small>Controll Assess of theacher</small></h3></div>
			  <div class="panel-body">
			    <form action="" method="POST">
			    	<div class="form-group">
			    		<label class="labelRadio">
			    			<input class="form-controll" type="checkbox" name="astudent" <?= $astudent ?>> Student Functionality
			    		</label>
			    		<label class="labelRadio">
			    			<input class="form-controll" type="checkbox" name="aattendance" <?= $aattendance ?>> Attendance Functionality
			    		</label>
			    		<label class="labelRadio">
			    			<input class="form-controll" type="checkbox" name="aclass" <?= $aclass ?>> Class Functionality
			    		</label>
			    		<label class="labelRadio">
			    			<input class="form-controll" type="checkbox" name="asection" <?= $asection ?>> Section Functionality
			    		</label>
			    		<label class="labelRadio">
			    			<input class="form-controll" type="checkbox" name="agroup" <?= $agroup ?>> Group Functionality
			    		</label>
			    		<label class="labelRadio">
			    			<input class="form-controll" type="checkbox" name="asubject" <?= $asubject ?>> Subject Functionality
			    		</label>
			    		<label class="labelRadio">
			    			<input class="form-controll" type="checkbox" name="aexam" <?= $aexam ?>> Exam Functionality
			    		</label>
			    		<label class="labelRadio">
			    			<input class="form-controll" type="checkbox" name="aexamatten" <?= $aexamatten ?>> Exam attendance Functionality
			    		</label>
			    		<label class="labelRadio">
			    			<input class="form-controll" type="checkbox" name="aadmit" <?= $aadmit ?>> Admit Card Functionality
			    		</label>
			    		<label class="labelRadio">
			    			<input class="form-controll" type="checkbox" name="aseat" <?= $aseat ?>> Seat Card Functionality
			    		</label>
			    		<label class="labelRadio">
			    			<input class="form-controll" type="checkbox" name="aresultpublis" <?= $aresultpublis ?>> Result Publish Functionality
			    		</label>
			    		<label class="labelRadio">
			    			<input class="form-controll" type="checkbox" name="aresult" <?= $aresult ?>> Result Functionality
			    		</label>
			    		<label class="labelRadio">
			    			<input class="form-controll" type="checkbox" name="ameritlist" <?= $ameritlist ?>> Merit List Functionality
			    		</label>
			    		<label class="labelRadio">
			    			<input class="form-controll" type="checkbox" name="afaillist" <?= $afaillist ?>> Fail List Functionality
			    		</label>
			    		<label class="labelRadio">
			    			<input class="form-controll" type="checkbox" name="atabulation1" <?= $atabulation1 ?>> Tabulation Sheet Functionality
			    		</label>
			    		<label class="labelRadio">
			    			<input class="form-controll" type="checkbox" name="atabulation2" <?= $atabulation2 ?>> Tabulation Sheet 2 Functionality
			    		</label>
			    		<label class="labelRadio">
			    			<input class="form-controll" type="checkbox" name="allmarksheet" <?= $allmarksheet ?>> All MarkSheet Functionality
			    		</label>
			    		<label class="labelRadio">
			    			<input class="form-controll" type="checkbox" name="apromotion" <?= $apromotion ?>> Promotion Functionality
			    		</label>
			    		<label class="labelRadio">
			    			<input class="form-controll" type="checkbox" name="aidcard" <?= $aidcard ?>> ID Card Functionality
			    		</label>
			    		<label class="labelRadio">
			    			<input class="form-controll" type="checkbox" name="atestimonial" <?= $atestimonial ?>> Testimonial Functionality
			    		</label>
			    		<label class="labelRadio">
			    			<input class="form-controll" type="checkbox" name="atc" <?= $atc ?>> TC Functionality
			    		</label>
			    		<label class="labelRadio">
			    			<input class="form-controll" type="checkbox" name="arevenue" <?= $arevenue ?>> Revenue Functionality
			    		</label>
			    		<label class="labelRadio">
			    			<input class="form-controll" type="checkbox" name="astdfee" <?= $astdfee ?>> Student Fee Functionality
			    		</label>
			    		<label class="labelRadio">
			    			<input class="form-controll" type="checkbox" name="astdfeereport" <?= $astdfeereport ?>> Student Fee Report
			    		</label>
			    		<label class="labelRadio">
			    			<input class="form-controll" type="checkbox" name="astdcoaching" <?= $astdcoaching ?>> Coaching Fee
			    		</label>
			    		<label class="labelRadio">
			    			<input class="form-controll" type="checkbox" name="asms" <?= $asms ?>> SMS Functionality
			    		</label>
			    	</div>

			    	<div class="form-group text-right">
			    		<button class="btn btn-primary" type="submit" name="accessctrl">Change Access</button>
			    	</div>

			    </form>
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
