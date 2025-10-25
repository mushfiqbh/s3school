<?php
/**
* Template Name: Admin Attendance
*/
global $wpdb,$s3sRedux;


/*Add Atta*/
if (isset($_POST['addAtta'])) {
	$apikey = "26e7458dc56ab2830fadba7bd2c1aa10e981518d309";
	$atdroll = explode(',', str_replace(' ', '', $_POST['rolls']));
	$gclass = $_GET['class'];
	$syear = $_GET['syear'];
	$gsec = isset($_GET['section']) ? $_GET['section'] : 0;

	$insert = $wpdb->insert(
		'ct_attendance',
		array(
			'attRoll' 		=> json_encode($atdroll),
			'attDate' 		=> $_POST['attDate'],
			'attClass' 		=> $gclass,
			'attSection' 	=> $gsec,
			'attYear' 		=> $syear
		)
	);
	$message = ms3message($insert, 'Added');

	$subSql = "SELECT stdName,stdPhone FROM ct_studentinfo
		LEFT JOIN ct_student ON ct_student.studentid = ct_studentinfo.infoStdid
	 	WHERE infoClass = $gclass AND infoYear = '$syear' AND infoSection = $gsec AND infoRoll IN (".$_POST['rolls'].")";
	$optSubjs = $wpdb->get_results( $subSql );



	$sendSMS =false;
	if ($sendSMS) {

		$post_url = 'http://portal.smsinbd.com/smsapi/' ;  
	                  
	  $post_values = array( 
	  	'api_key' => $apikey,
			'type' => 'unicode',  // unicode or text
			'senderid' => '8804445641111',
			'contacts' => $student,
			'msg' => $message,
			'method' => 'api'
	  );
	  
	  $post_string = "";
	  foreach( $post_values as $key => $value ){ $post_string .= "$key=" . urlencode( $value ) . "&"; }
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
	   
	  if($array){ 
			if($array['status'] == "SUCCESS"){
				$totalCost += $array['cost'];
				$sucessed++;
			}else{
				$failed++;
			}
		}
	}
}

?>


<?php if ( ! is_admin() ) { get_header(); ?>
<div class="b-layer-main">

	<div class="">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
<?php } ?>

<?php if(isset($_GET['view'])){ ?>
	<?php if($_GET['view'] == 'day'){ ?>
		<div class="container-fluid maxAdminpages" style="padding-left: 0">

			<div class="row">
				<div class="col-md-12">
					<h2> Attendance Management
			      <a class="pull-right btn btn-primary" href="?page=attendance&view=month">Monthly Attendance</a>
		        <a class="pull-right btn btn-primary" href="?page=attendance">Add Attendance</a>
		      </h2>
					
				</div>
				<div class="col-md-12">
					<div class="panel panel-info">
					  <div class="panel-heading">
					  	<h3>
					  		Daily Attendance
					  	</h3>
					  </div>
					  <div class="panel-body">
					  	<form action="" method="GET" class="form-inline">

								<input type="hidden" name="page" value="attendance">
								<input type="hidden" name="view" value="day">
								
								<div class="form-group">
									<label>Class</label>
									<select id='resultClass' class="form-control" name="class" required>
										<?php

											$classQuery = $wpdb->get_results( "SELECT classid,className FROM ct_class WHERE classid IN (SELECT attClass FROM ct_attendance GROUP BY attClass) ORDER BY className ASC" );
											echo "<option value=''>Select Class</option>";

											foreach ($classQuery as $class) {
												echo "<option value='".$class->classid."'>".$class->className."</option>";
											}
										?>
									</select>
								</div>

								<div class="form-group ">
									<label>Section</label>
									<select id="resultSection" class="form-control" name="section" disabled>
										<option disabled selected>Select Class First</option>
									</select>
								</div>

								<div class="form-group">
									<label>Year</label>
									<select id='resultYear' class="form-control" name="syear" required disabled>
										<option disabled selected>Select Class First</option>
									</select>
								</div>
								
								<div class="form-group">
									<label>Date</label>
									<input type="date" name="date">
								</div>

								<div class="form-group">
									<label>Roll</label>
									<input type="number" name="roll">
								</div>

								<div class="form-group">
									<input class="form-control btn-success" type="submit" name="searchAtt" value="Go">
								</div>
							</form>


							<?php if(isset($_GET['searchAtt'])){ ?>
								<?php
									$class 		= $_GET['class'];
						  		$year 		= $_GET['syear'];
						  		$date 		= $_GET['date'];
						  		$section 	= isset($_GET['section']) ? $_GET['section'] : '';
						  		$roll 		= isset($_GET['roll']) ? $_GET['roll'] : '';

						  		$query =  "SELECT attRoll FROM `ct_attendance` WHERE attClass = $class AND attYear = '$year'";
						  		$query .= ($section != '') ? " AND attSection = $section" : '';
						  		$query .= ($roll != '') ? " AND attRoll like '%\"$roll\"%" : '';
						  		$query .= " ORDER BY attId DESC LIMIT 1";

									$attendance = $wpdb->get_results($query);
									if($attendance){
										$absentStd = json_decode( $attendance[0]->attRoll );
									}

									$query2 ="SELECT stdName,infoRoll FROM `ct_studentinfo` LEFT JOIN ct_student ON ct_studentinfo.infoStdid = ct_student.studentid  WHERE";
									$query2 .= ($attendance) ? " infoRoll NOT IN (".implode (", ", $absentStd).") AND " : '';
									$query2 .= " infoClass = $class AND infoYear = '$year'";

									$query2 .= ($section != '') ? " AND infoSection = $section" : '';
									$query2 .= ($roll != '') ? " AND infoRoll = $roll" : '';
									$precents =  $wpdb->get_results($query2);
								?>
								<br>
								<hr>

							  <div id="printArea" class="printBG">
									<style type="text/css"> @page { size: auto;  margin: 0px; } </style>

								  <div class="printArea">
								  	<h3>Precent</h3>
								  	<table class="table table-responsive table-bordered">
								  		<tr>
								  			<th>#</th>
								  			<th>Name</th>
								  			<th>Roll</th>
								  		</tr>
								  		<?php 
								  			foreach ($precents as $key => $value) {
								  				?><tr><td><?= $key+1 ?></td><td><?= $value->stdName ?></td><td><?= $value->infoRoll ?></td></tr><?php
								  			}
								  		?>
								  	</table>
								  	<?php if($attendance){ ?>
								  		<?php 
								  			$query2 = "SELECT stdName,infoRoll FROM `ct_studentinfo` LEFT JOIN ct_student ON ct_studentinfo.infoStdid = ct_student.studentid  WHERE infoRoll IN (".implode (", ", $absentStd).") AND infoClass = $class AND infoYear = '$year'";
												$query2 .= ($section != '') ? " AND infoSection = $section" : '';
												$query2 .= ($roll != '') ? " AND infoRoll = $roll" : '';
												$query2 .= " ORDER BY infoRoll ASC";
												$precents =  $wpdb->get_results($query2);
								  		?>
								  		<h3>Absent</h3>
								  		<table class="table table-responsive table-bordered">
									  		<tr>
									  			<th>#</th>
									  			<th>Name</th>
									  			<th>Roll</th>
									  		</tr>
									  		<?php 
								  			foreach ($precents as $key => $value) {
								  				?><tr><td><?= $key+1 ?></td><td><?= $value->stdName ?></td><td><?= $value->infoRoll ?></td></tr><?php
								  			}
								  		?>
									  	</table>
								  	<?php } ?>
								  </div>
							  </div>

							<?php } ?>

					  </div>
					</div>
				</div>

				
			</div>
		</div>
	<?php }elseif ($_GET['view'] == 'month') { ?>
		
		<div class="container-fluid maxAdminpages" style="padding-left: 0">

			<div class="row">
				<div class="col-md-12">
					<h2> Attendance Management
						<a class="pull-right btn btn-primary" href="?page=attendance&view=day">
		       		Daily Attendance
			      </a>
		        <a class="pull-right btn btn-primary" href="?page=attendance">
		       		Add Attendance
			      </a>
		      </h2>
					
				</div>
				<div class="col-md-12">
					<div class="panel panel-info">
					  <div class="panel-heading">
					  	<h3>
					  		Monthly Attendance
					  	</h3>
					  </div>
					  <div class="panel-body">
					  	<form action="" method="GET" class="form-inline">

								<input type="hidden" name="page" value="attendance">
								<input type="hidden" name="view" value="month">
								
								<div class="form-group">
									<label>Class</label>
									<select id='resultClass' class="form-control" name="class" required>
										<?php

											$classQuery = $wpdb->get_results( "SELECT classid,className FROM ct_class WHERE classid IN (SELECT attClass FROM ct_attendance GROUP BY attClass) ORDER BY className ASC" );
											echo "<option value=''>Select Class</option>";

											foreach ($classQuery as $class) {
												echo "<option value='".$class->classid."'>".$class->className."</option>";
											}
										?>
									</select>
								</div>

								<div class="form-group ">
									<label>Section</label>
									<select id="resultSection" class="form-control" name="section" required>
										<option disabled selected>Select Class First</option>
									</select>
								</div>

								<div class="form-group">
									<label>Year</label>
									<select id='resultYear' class="form-control" name="syear" required>
										<option disabled selected>Select Class First</option>
									</select>
								</div>
								
								<div class="form-group">
									<label>Month</label>
									<select name="month">
										<?php
											for ($i=1; $i < 13; $i++) { 
												$month = date("F", mktime(0, 0, 0, $i, 10));
												echo "<option value='$i'>$month</option>";
											} 
										?>
									</select>
								</div>

								<div class="form-group">
									<input class="form-control btn-success" type="submit" name="searchAtt" value="Go">
								</div>
							</form>

							<br><hr><br>
							<?php 
								if(isset($_GET['searchAtt'])){
									$class 		= $_GET['class'];
						  		$year 		= $_GET['syear'];
						  		$month 		= $_GET['month'];
						  		$section 	= isset($_GET['section']) ? $_GET['section'] : '';

									$dates = cal_days_in_month(CAL_GREGORIAN, $month, $year);

						  		$query =  "SELECT infoRoll,stdName,className,sectionName FROM `ct_studentinfo` LEFT JOIN ct_student ON ct_student.studentid = ct_studentinfo.infoStdid LEFT JOIN ct_class ON ct_class.classid = $class LEFT JOIN ct_section ON ct_section.sectionid = $section WHERE `infoClass` = $class AND `infoYear` = '$year'";
						  		$query .= ($section != '') ? " AND infoSection = $section" : '';
						  		$query .= " ORDER BY infoRoll ASC";

									$students = $wpdb->get_results($query);
									if($students){

										?>
											<div class="text-right">
												
												<button onclick="print('monthlyRes')" class="btn btn-primary">Print</button>
											</div>
											<div id="monthlyRes">
									  		<div style="text-align: center; position: relative;">
									  			<img height="80px" style="position: absolute;left: 10px;top: 10px" src="<?= $s3sRedux['instLogo']['url'] ?>">
							  					<h2 style="margin: 5px 0 5px 0;"><b><?= $s3sRedux['institute_name'] ?></b></h2>
										  		<p style="color:#2b5591; font-size: 14px; margin: 0;"><?= $s3sRedux['institute_address'] ?></p>
										  		<h3>Attendance</h3>
									  		</div>
												<style type="text/css">
													.table td, .table th{
														padding: 5px !important;
														text-align: center;
														border: 1px solid #333;
													}
													.atnDay{
														width: 23px;
													}
												</style>
												<table style="width: 100%">
									  			<tr style="background: #4472C4;print-color-adjust: exact;-webkit-print-color-adjust: exact;color: #fff">
									  				<td style="padding: 5px;"><b>Class Name :</b> <?= $students[0]->className ?></td>
									  				<td style="padding: 5px;"><b>Year/Session :</b> <?= $year ?></td>
									  				<td style="padding: 5px;"><b>Section :</b> <?= $students[0]->sectionName ?></td>
									  			</tr>
									  		</table>
												<table class="table" style="font-size: 11px" cellspacing="0" cellpadding="0">
													<tr>
														<th style="width: 35px">#</th>
														<th>Name</th>
														<th style="width: 55px">Roll</th>
														<?php for ($i=1; $i < ($dates+1) ; $i++) {
															$date = "$i-$month-$year";
															if(date('D', strtotime($date)) != 'Fri')
																echo "<th class='atnDay'>$i</th>";
														} ?>
													</tr>
													<?php foreach ($students as $key => $student) { ?>
														<tr>
															<td><?= $key+1 ?></td>
															<td style="text-align: left;"><?= $student->stdName ?></td>
															
															<td><?= $stdRol = $student->infoRoll ?>
																
															<?php
																$query2 =  "SELECT attDate FROM `ct_attendance` WHERE `attClass` = $class AND `attYear` = '$year' AND MONTH(attDate) = $month AND attRoll LIKE '%\"$stdRol\"%'";
																$attnsd = $wpdb->get_results($query2);
																$allatnd = array();
																if (sizeof($attnsd) > 0) {
																	foreach ($attnsd as $value) {
																		$allatnd[] = $value->attDate;
																	}
																}
															?>
															</td>
															<?php for ($i=1; $i < ($dates+1) ; $i++) {
																$date = "$i-$month-$year";
																$mnt = sprintf('%02d', $month);
																$day = sprintf('%02d', $i);
																$dbdate = "$year-$mnt-$day";
																$attnd = 'P';

																if(date('D', strtotime($date)) != 'Fri'){
																	if (in_array($dbdate, $allatnd))
																		$attnd = '<span style="color:red">A</span>';
																	echo "<td>$attnd</td>";
																}
															} ?>
														</tr>
													<?php } ?>
												</table>
											</div>
										<?php
									}else{
										echo "<h3 class='text-center'>No Student Found</h3>";
									}
								}
							?>
					  </div>
					</div>
				</div>

				
			</div>
		</div>
	<?php } ?>
<?php }else{ ?>

	<div class="container-fluid maxAdminpages" style="padding-left: 0">

		<!-- Show Status message -->
		<?php if(isset($message)){ ms3showMessage($message); } ?>

		

		<div class="row">
			<div class="col-md-12">
				<h2> Attendance Management

	        <a class="pull-right btn btn-primary" href="?page=attendance&view=day">
	       		View Daily
		      </a>
		      <a class="pull-right btn btn-primary" href="?page=attendance&view=month">
	       		View Monthly
		      </a>
	      </h2>
				
			</div>
			<div class="col-md-12">
				<div class="panel panel-info">
				  <div class="panel-heading">
				  	<h3>
				  		Attendance<br>
							<small>Only Input students daily absent. Student daily attendance will be auto generate.</small>
				  	</h3>
				  </div>
				  <div class="panel-body">
				  	<form action="" method="GET" class="form-inline">

							<input type="hidden" name="page" value="attendance">
							
							<div class="form-group">
								<label>Class</label>
								<select id='resultClass' class="form-control" name="class" required>
									<?php

										$classQuery = $wpdb->get_results( "SELECT classid,className FROM ct_class WHERE classid IN (SELECT examClass FROM ct_exam GROUP BY examClass ORDER BY className ASC)" );
										echo "<option value=''>Select Class</option>";

										foreach ($classQuery as $class) {
											echo "<option value='".$class->classid."'>".$class->className."</option>";
										}
									?>
								</select>
							</div>

							<div class="form-group ">
								<label>Section</label>
								<select id="resultSection" class="form-control" name="section">
									<option disabled selected>Select Class First</option>
								</select>
							</div>

							<div class="form-group">
								<label>Year</label>
								<select id='resultYear' class="form-control" name="syear" required>
									<option disabled selected>Select Class First</option>
								</select>
							</div>


							<div class="form-group">
								<input class="form-control btn-success" type="submit" name="" value="Go">
							</div>
						</form>


						<?php if(isset($_GET['class'])){ ?>
							<br>
							<hr>

						  <div  class="monthlyRes">
								<style type="text/css"> @page { size: auto;  margin: 0px; } </style>

							  <div id="monthlyRes">

							  	<?php

							  		$class 		= $_GET['class'];
							  		$year 		= $_GET['syear'];
							  		$section 	= isset($_GET['section']) ? $_GET['section'] : '';

							  		
							  		$studentsQry = "SELECT * FROM ct_class";

							  		if($section != ''){
							  			$studentsQry .= " LEFT JOIN ct_section ON ct_section.sectionid = $section";
							  		}

							  		$studentsQry .= " WHERE classid = $class";

							  		if($section != ''){
							  			$studentsQry .= " AND sectionid = $section";
							  		}

							  		$names = $wpdb->get_results( $studentsQry );

							  		if($names){
							  			?>
							  			<form action="" method="POST">
							  				<div class="row">

							  					<div class="form-group col-md-3 col-sm-4 col-xs-6">
							  						<label> Class : 
							  							<input class="form-control" type="text" name="class" value="<?= $names[0]->className ?>" readonly>
							  						</label>

							  						<label> Year : 
							  							<input class="form-control" type="text" name="year" value="<?= $year ?>" readonly>
							  						</label>
							  					</div>

							  					

								  				<div class="form-group col-md-3 col-sm-4 col-xs-6">
								  					<?php if($section != ''){ ?>
								  						<label> Section : 
								  							<input class="form-control" type="text" name="section" value="<?= $names[0]->sectionName ?>" readonly>
								  							<input type="hidden" name="section" value="<?= $section ?>">
								  						</label>
								  					<?php } ?>
								  					<label>Date : 
							  							<input class="form-control" type="date" name="attDate" value="<?= date('Y-m-d') ?>">
								  					</label>
							  					</div>

							  					
								  				<div class="form-group col-md-6 col-sm-8">
							  						<label>Absent Roll Numbers : 
							  							<textarea id="rullTextArea" class="form-control" cols="80" type="number" name="rolls" required></textarea>
							  							<small>Input roll separated by comma. ex: 3,21,35,85</small>
							  						</label>
							  					</div>
								  				
							  				</div>

							  				<div class="text-right">

						  						<input class="btn btn-primary" type="submit" name="addAtta" value="ADD">
							  				</div>

							  			</form>
							  			<?php

										}else{
											echo "<h3 class='text-center'>No Student Found</h3>";
										}

							  	?>

							  </div>
						  </div>

						<?php } ?>

				  </div>
				</div>
			</div>

			
		</div>
	</div>

<?php } ?>

<?php if ( ! is_admin() ) { ?>
				</div>
			</div>
		</div>
	</div>
</div>
<?php get_footer(); } ?>

<script type="text/javascript">
	(function($) {
		$('#rullTextArea').keyup(function(event) {
			$this = $(this);
			var $val = $this.val();
			var isValid = /^[0-9,.]*$/.test($val);
			if(isValid && $val.indexOf('.') == -1){
				$this.closest('.form-group').removeClass('has-error'); 
			}else{
				$this.closest('.form-group').addClass('has-error'); 
			}
		});

		$('#resultClass').change(function() {
	    var $siteUrl = '<?= get_template_directory_uri() ?>/inc/ajaxAction.php';

	    $.ajax({
	      url: $siteUrl,
	      method: "POST",
	      data: { class : $(this).val(), type : 'getYears' },
	      dataType: "html"
	    }).done(function( msg ) {
	      $( "#resultYear" ).html( msg );
	      $( "#resultYear" ).prop('disabled', false);
	    });

	    $.ajax({
	      url: $siteUrl,
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
    w.document.close();
    w.focus();
    return true;
  }

</script>