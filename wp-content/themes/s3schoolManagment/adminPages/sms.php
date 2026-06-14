<?php
/**
* Template Name: Admin SMS
*/

	global $wpdb;
	$apikey = '26e7458dc56ab2830fadba7bd2c1aa10e981518d309';

	if (isset($_POST['sendSms'])) {

		$_POST['students'] = array_filter($_POST['students']);
		$message = $_POST['message'];
		$totalCost = 0;
		$sucessed = 0;
		$failed = 0;

		foreach ($_POST['students'] as $key => $student) {
			if (strpos($student, '88') == false) {
				$student = "88".$student;
			}

			$post_values = array( 
				'api_key' => $apikey,
				'type' => 'unicode',  // unicode or text
				'senderid' => '8801552146120',
				'contacts' => $student,
				'msg' => $message,
				'method' => 'api'
			);



			$post_string = "";
			foreach( $post_values as $key => $value ){
				$post_string .= "$key=" . urlencode( $value ) . "&"; 
			}
		  $post_string = rtrim( $post_string, "& " );

			$request = curl_init("http://portal.smsinbd.com/smsapi");  
			curl_setopt($request, CURLOPT_HEADER, 0);  
			curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);  
			curl_setopt($request, CURLOPT_POSTFIELDS, $post_string); 
			curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE);  
			$post_response = curl_exec($request);  

		  curl_close ($request);  
			$array =  json_decode( preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $post_response), true );   

			if($array){ 
			 if($array['status'] == "SUCCESS"){
			 	$totalCost += $array['cost'];
			 	$sucessed++;
			 }else{
			 	$failed++;
			 }
			}
		}

		if($array){
			?>
			<br>
				<div class="text-center">
					<div class="alert alert-info" style="max-width: 400px; margin: auto;">
						<b>Sucessfuly Send:</b>  <?= $sucessed ?><br>
						<b>Failed Send:</b>  <?= $failed ?><br>
						<b>SMS Account Debited:</b>  <?= $totalCost ?>
					</div>
				</div>
			<?php
		}else{?>
			<br>
				<div class="text-center">
					<div class="alert alert-danger" style="max-width: 400px; margin: auto;">
						0 Message Send
					</div>
				</div>
			<?php
		}
	}



	/*Balance*/
	$post_values = array( 
		'api_key' => $apikey,
		'act' => 'balance',    
		'method' => 'api'
	);
	$post_url = 'http://portal.smsinbd.com/api/' ;  


	$post_string = "";
	foreach( $post_values as $key => $value )
		{ $post_string .= "$key=" . urlencode( $value ) . "&"; }
	   $post_string = rtrim( $post_string, "& " );
	   
	$request = curl_init($post_url); 
		curl_setopt($request, CURLOPT_HEADER, 0); 
		curl_setopt($request, CURLOPT_RETURNTRANSFER, 1); 
		curl_setopt($request, CURLOPT_POSTFIELDS, $post_string);  
		curl_setopt($request, CURLOPT_SSL_VERIFYPEER, FALSE); 
		$post_response = curl_exec($request);  
	 
	 
	curl_close ($request);  
	 
	 
	$responses=array();  		
 	$array =  json_decode( preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $post_response), true );   

?>



<?php if ( ! is_admin() ) { get_header(); ?>

<div class="b-layer-main">


	<div class="">
		<div class="container">
			<div class="row">
				<div class="col-md-12">

<?php } ?>



<div class="container-fluid maxAdminpages smsPage" style="padding-left: 0">

	<div class="panel panel-info">
	  <div class="panel-heading">
	  	<h3>
	  		SMS<br><small>Send SMS to Students/Teachers (Charge 0.34 BDT for english and 0.68 BDT for Bangla Per SMS)</small>
	  		<span class="pull-right">Balance: <?= $array['balance'] ?></span>
	  	</h3>
	  </div>
	  <div class="panel-body">

			<div id="smsTab">
				<ul class="nav nav-tabs">
				  <li class="active"><a data-toggle="tab" href="#students">Students</a></li>
				  <li><a data-toggle="tab" href="#teachers">Teachers</a></li>
				</ul>


				<div class="tab-content">
				  <div id="students" class="tab-pane fade in active">
				  	<div class="panel-body">
					    <form class="form-inline" action="" method="GET">
								<input type="hidden" name="page" value="sms">


								<div class="form-group">
									<label>Class</label>
									<select id='resultClass' class="form-control" name="class" required>
										<option value="">Select Class</option>
										<?php
											$groupsBy = $wpdb->get_results( "SELECT classid,className FROM ct_class" );
											foreach ($groupsBy as $value) {
												$id = $value->classid;
												$name = $value->className;
												echo "<option value='$id'>$name</option>";
											}
										?>
									</select>

								</div>


								<div class="form-group">
									<label>Year</label>
									<select id='resultYear' class="form-control" name="syear" required>
										<option disabled selected>Select Class First</option>
									</select>
								</div>

								<div class="form-group">
									<label>Section</label>
									<select id="resultSection" class="form-control" name="section" required>
										<option disabled selected>Select Class First</option>
									</select>
								</div>

								<div class="form-group" id="idRoll">
									<label>Roll</label>
									<input class="form-control" type="text" name="roll" placeholder="Roll">
								</div>
								<div class="form-group">
									<input type="submit" class="btn btn-primary">
								</div>
							</form>
				  	</div>
				  </div>
				  <div id="teachers" class="tab-pane fade">
				  	<div class="panel-body">
					    <form action="" method="GET" class="form-inline">
					    	<input type="hidden" name="page" value="sms">
					    	<div class="form-group">
					    		<input class="form-control btn btn-primary" type="submit" name="allTeachers" value="All Teachers">
					    	</div>
					    	<div class="form-group">
					    		<input class="form-control" type="text" name="teacherName" placeholder="Teacher Name">
					    	</div>
					    	<div class="form-group">
					    		<input class="form-control btn btn-success" type="submit" name="oneTeacher" value="GO">
					    	</div>
					    </form>
					  </div>
				  </div>
				</div>
			</div>


			<!-- For Student -->

			<?php if(isset($_GET['syear'])){ ?>

			  <div class="col-md-12 printBG">
			  	<div>
					  <div class="printArea">
					  	<?php
					  		$year 		= $_GET['syear'];
					  		$class 		= $_GET['class'];
					  		$section 	= $_GET['section'];
					  		$roll 		= $_GET['roll'];

					  		$qury1 = "SELECT stdName,infoRoll,stdPhone FROM ct_student
					  			LEFT JOIN ct_studentinfo ON ct_studentinfo.infoStdid = ct_student.studentid
					  			WHERE infoYear = '$year' AND infoClass = $class AND infoSection = $section ";

					  		if ($roll != "" ) {
					  			$qury1 .= " AND infoRoll = $roll";
					  		}

					  		$qury1 .= " ORDER BY infoRoll ASC";
								$groupsBy = $wpdb->get_results( $qury1 );


					  		if($groupsBy){
					  			?>
					  				<form action="" method="POST">
					  					<div class="row">
						  					<div class="form-group col-md-8 col-md-offset-2">
						  						<textarea class="form-control smsCount" name="message" placeholder="Message" required></textarea>
						  						<p class="smsCountShow"><span class="ramain">0</span> Characters | <span class="left">750</span> Characters Left | <span class="totalSms">1</span> SMS</p>
						  					</div>
						  				</div>
					  					<table class="table table-bordered">
					  						<tr>
					  							<td colspan="9" class="text-right"><label class="labelRadio"><input type="checkbox" id="selectAll"> Select All</label></td>
					  						</tr>
					  						<tr>

					  							<th>Select</th>
					  							<th>Name</th>
					  							<th style="border-right: 2px solid #000">Roll</th>
					  							<?php if(sizeof($groupsBy) > 1){ ?>
						  							<th>Select</th>
						  							<th>Name</th>
						  							<th>Roll</th>
						  						<?php }if(sizeof($groupsBy) > 2){ ?>
						  							<th style="border-left: 2px solid #000">Select</th>
						  							<th>Name</th>
						  							<th>Roll</th>
						  						<?php } ?>
					  						</tr>
					  						
									  		<?php
									  			$num = 1;
													foreach ($groupsBy as $key => $value) {

														if($num == 1){ echo "<tr>"; }
														$style = '';
														if ($num == 1 || $num == 2) {
															$style = 'style="border-right: 2px solid #000;"';
														}
														?>
															<td class="text-center">
																<input class="stdSel" type="checkbox" name="students[]" value="<?= $value->stdPhone ?>">
															</td>
															<td><?= $value->stdName ?></td>
															<td <?= $style ?>><?= $value->infoRoll ?></td>
														<?php

														if($num == 3 ){ echo "</tr>"; $num = 0; }
														$num++;
													}
												?>
											</table>
											<div class="form-group">
												<input class="btn btn-success" type="submit" name="sendSms" value="Send SMS">
											</div>
					  				</form>
					  			<?php
								}else{
									echo "<h3 class='text-center'>No Student Found</h3>";
								}
					  	?>

					  </div>
			  	</div>
			  </div>

			<?php } ?>


			<!-- For Teachers -->
			<?php if(isset($_GET['allTeachers']) || isset($_GET['oneTeacher'])){ ?>
			  <div class="col-md-12 printBG">
			  	<div>
					  <div class="printArea">
					  	<?php

					  		if (!empty($_GET['teacherName'])) {
					  			$groupsBy = $wpdb->get_results( "SELECT teacherName,teacherMpo,teacherPhone FROM `ct_teacher` WHERE teacherName LIKE '%".$_GET['teacherName']."%'" );
					  		}else{
									$groupsBy = $wpdb->get_results( "SELECT teacherName,teacherMpo,teacherPhone FROM `ct_teacher`" );
					  		}

					  		if($groupsBy){
					  			?>
					  				<form action="" method="POST">
					  					<div class="row">
						  					<div class="form-group col-md-8 col-md-offset-2">
						  						<textarea class="form-control smsCount" name="message" placeholder="Message" required></textarea>
						  						<p class="smsCountShow"><span class="ramain">0</span> Characters | <span class="left">750</span> Characters Left | <span class="totalSms">1</span> SMS</p>
						  					</div>
						  				</div>
					  					<table class="table table-bordered">
					  						<tr>
					  							<td colspan="9" class="text-right">
					  								<label class="labelRadio"><input type="checkbox" id="selectAll"> Select All</label>
					  							</td>
					  						</tr>
					  						<tr>
					  							<th>Select</th>
					  							<th>Name</th>
					  							<th>MPO No</th>
					  							<?php if(sizeof($groupsBy) > 1){ ?>
						  							<th style="border-left: 2px solid #000">Select</th>
						  							<th>Name</th>
						  							<th>MPO No</th>
						  						<?php }if(sizeof($groupsBy) > 2){ ?>
						  							<th style="border-left: 2px solid #000">Select</th>
						  							<th>Name</th>
						  							<th>MPO No</th>
						  						<?php } ?>
					  						</tr>

									  		<?php
									  			$num = 1;
													foreach ($groupsBy as $key => $value) {

														if($num == 1){
															echo "<tr>";
														}
														$style = '';
														if ($num == 1 || $num == 2) {
															$style = 'style="border-right: 2px solid #000;"';
														}
														?>
															<td class="text-center">
																<input class="stdSel" type="checkbox" name="students[]" value="<?= $value->teacherPhone ?>">
															</td>
															<td><?= $value->teacherName ?></td>
															<td <?= $style ?>><?= $value->teacherMpo ?></td>
														<?php
														if($num == 3 ){ echo "</tr>"; $num = 0; }
														$num++;
													}
												?>
											</table>
											<div class="form-group">
												<input class="btn btn-success" type="submit" name="sendSms" value="Send SMS">
											</div>
					  				</form>
					  			<?php
								}else{
									echo "<h3 class='text-center'>No Student Found</h3>";
								}
					  	?>

					  </div>
			  	</div>
			  </div>

			<?php } ?>
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
	(function($) {
		$('#resultClass').change(function() {
	    var $url = "<?= get_template_directory_uri() ?>/inc/ajaxAction.php";

	    $.ajax({
	      url: $url,
	      method: "POST",
	      data: { class : $(this).val(), type : 'getYears' },
	      dataType: "html"
	    }).done(function( msg ) {
	      $( "#resultYear" ).html( msg );
	    });

	    $.ajax({
	      url: $url,
	      method: "POST",
	      data: { class : $(this).val(), type : 'getSection' },
	      dataType: "html"
	    }).done(function( msg ) {
	      $( "#resultSection" ).html( msg );
	    });
	  });
	})( jQuery );
</script>