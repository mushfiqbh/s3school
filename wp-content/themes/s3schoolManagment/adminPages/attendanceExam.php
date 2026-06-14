<?php
/**
 * Template Name: Admin ExamAttendance
 */
global $wpdb; global $s3sRedux; 
?>

<?php if ( ! is_admin() ) { get_header(); ?>
<div class="b-layer-main">

	<div class="">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
<?php } ?>
<p id="theSiteURL" class="hidden"><?= get_template_directory_uri() ?></p>
<div class="container-fluid maxAdminpages" style="padding-left: 0">


	<div class="row">

		<div class="col-md-12">
			<div class="panel panel-info">
			  <div class="panel-heading"><h3>Exam Attendance<br><small>Create Students Exam Attendance sheet</small></h3></div>
			  <div class="panel-body">

					<form class="form-inline" action="" method="GET">
						<input type="hidden" name="page" value="examattendance">
						
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

						<div class="form-group">
							<label>Year</label>
							<select id='resultYear' class="form-control" name="syear" required disabled>
								<option disabled selected>Select Class First</option>
							</select>
						</div>

						<div class="form-group ">
							<label>Section</label>
							<select id="resultSection" class="form-control" name="section" required disabled>
								<option disabled selected>Select Class First</option>
							</select>
						</div>

						<div class="form-group ">
							<label>Exam</label>
							<select id="resultExam" class="form-control" name="exam" required disabled>
								<option disabled selected>Select Class First</option>
							</select>
						</div>

						<div class="form-group" id="idRows">
							<input style="width: 80px;" class="form-control" type="number" name="rows" placeholder="Rows">
						</div>
						<div class="form-group" id="idRoll">
							<input style="width: 80px;" class="form-control" type="text" name="roll" placeholder="Roll">
						</div>
						<div class="form-group">
							<input type="submit" name="creatId" value="Genarate" class="btn btn-primary">
						</div>
					</form>
			  </div>
			</div>
		</div>

		<?php if(isset($_GET['syear'])){ ?>

	  	<div class="col-md-12">
		  	<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>
		  </div>
		  <div id="printArea" class="col-md-12 printBG">
		  	<div class="printArea" style="margin: 0 30px;">
					<style type="text/css">
						@page { size: auto;  margin: 0px; }
						#itemMainBox{
							max-width: 8.27in; 
							display: inline-block;
						  border: 1px dashed #333;
						  overflow: hidden;
							margin: 15PX 0;
						  font-family: sans-serif;
						  width: 100%;
						}
						#itemMainBox .instLogo{
							width: 90px; position: absolute;left: 0;top: 10px;
						}
						#itemMainBox .instName{
							margin: 0 0 5px 0;
					    color: #337ab7;
					    font-weight: bold;
					    font-size: 25px;
						}
						#itemMainBox .instAddrs{
							margin: 0 0 7px 0;color: #888888;font-size: 16px;
						}
						#itemMainBox .examName{
							margin: 0 auto 7px;
					    text-align: center;
						}
						#itemMainBox .examName h3{
							margin: 0;
							font-size: 20px;
						}
						#itemMainBox .itemInfo{
							text-align: center; margin-bottom: 20px; clear: both;
						}
						#itemMainBox .admitCard{
							margin: 15px 0 15px 0px;
					    color: #f7740c;
					    font-weight: bold;
					    background: #f0f0f0;
					    -webkit-print-color-adjust: exact;
					    padding: 5px;
					    border-radius: 5px;
					    font-size: 21px;
					    border: 2px solid #f0f0f0;
						}
						#itemMainBox .admitNote{
							float: left;
						}
						#itemMainBox .admitNote p{
							margin: 0;
							padding-left: 15px;
						}
						#itemMainBox hr{
							clear: both;
						}
						#itemMainBox .princSign{
							float: right;
						}
					</style>
			  
			  	<?php
			  		$year 		= $_GET['syear'];
			  		$class 		= $_GET['class'];
			  		$exam 		= $_GET['exam'];
			  		$section 	= isset($_GET['section']) ? $_GET['section'] : "";
			  		$roll 		= isset($_GET['roll']) ? $_GET['roll'] : "";
			  					
						$groupsBy = "SELECT stdName,infoRoll,className,sectionName,examName,groupName,infoOptionals,info4thSub FROM ct_student
						LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid AND ct_student.stdCurrentClass = ct_studentinfo.infoClass
						LEFT JOIN ct_class ON ct_studentinfo.infoClass = ct_class.classid
						LEFT JOIN ct_exam ON ct_exam.examid = $exam
						LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId
						LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
						WHERE infoYear = '$year' AND infoClass = $class";

						$groupsBy .= ($roll != "") ? " AND infoRoll IN ($roll)" : '';

			  		$groupsBy .= ($section != "") ? " AND infoSection = $section" : ''; 

			  		$groupsBy .= " ORDER BY infoRoll" ;

			  		$groupsBy = $wpdb->get_results($groupsBy);

			  		$alloptSub = array();
			  		$alloptSubQuery = $wpdb->get_results("SELECT subjectid,subjectName FROM `ct_subject` WHERE subjectClass = $class AND (subOptinal = 1 OR sub4th = 1) ORDER BY subid");
			  		foreach ($alloptSubQuery as $key => $value) {
			  			$alloptSub[$value->subjectid] = $value->subjectName;
			  		}

			  		$allmainSubQuery = $wpdb->get_results("SELECT subjectid,subjectName FROM `ct_subject` WHERE `subjectClass` = $class AND subOptinal = 0 AND sub4th = 0 ORDER BY subid");


			  		if($groupsBy){
			  			function rows($name = ''){
				  			?>
				  			<tr>
			  					<td style="border: 1px solid; padding: 10px 5px; height: 40px"></td>
			  					<td style="border: 1px solid; padding: 10px 5px; height: 40px; background: #f6f6f6"><?= $name ?></td>
			  					<td style="border: 1px solid; padding: 10px 5px; height: 40px"></td>
			  					<td style="border: 1px solid; padding: 10px 5px; height: 40px; background: #f6f6f6"></td>
			  					<td style="border: 1px solid; padding: 10px 5px; height: 40px"></td>
			  				</tr>
				  			<?php
				  		}
							foreach ($groupsBy as $value) {
								$opt = json_decode($value->infoOptionals);
								if($value->info4thSub != '' && $value->info4thSub != 0)
									$opt[] = $value->info4thSub;
								?>
									<div id="itemMainBox">
										<div style="padding:0 15px 10px 15px; ">
											<div style="text-align: center; float: left; width: 100%;position: relative;padding: 20px 0 0;" >
												<img  class="instLogo" src="<?= $s3sRedux['instLogo']['url'] ?>">
												<h2  class="instName" ><?= $s3sRedux['institute_name'] ?></h2>
												<h4  class="instAddrs"><?= $s3sRedux['institute_address'] ?></h4>
												<div class="examName">
								  				<h3><?= $value->examName." ".$year ?></h3>
								  			</div>
												<h4 class="admitCard"><b>Exam Attendance Sheet</b></h4>
											</div>

											<div style="float: left; clear: both;width: 100%;margin-bottom: 10px;">

								  			<table style="font-size: 16px;width: 100%;">
								  				<tr>
								  					<td><p style="font-size: 16px;"><b>Name:</b> <?= $value->stdName; ?></p></td>
								  					<td><p style="font-size: 16px;"><b>Roll:</b> <?= $value->infoRoll; ?></p></td>
								  					<td><p style="font-size: 16px;"><?= $value->className; ?></p></td>
								  				</tr>
								  				<tr>
								  					<td><p style="font-size: 16px;"><b>Section:</b> <?= $value->sectionName; ?></p></td>
								  					<td><p style="font-size: 16px;"><b>Group:</b> <?= $value->groupName; ?></p></td>
								  					<td><p style="font-size: 16px;"><b>Year/Session:</b> <?= $_GET['syear']; ?></p></td>
								  				</tr>
								  				<tr>
								  					<td colspan="3">
								  						<b>Optional Subjects:</b>
								  						<i style="font-size: 12px">
								  							<?php
								  							if(is_array($alloptSub) && is_array($opt) && sizeof($opt) > 0){
								  								foreach ($alloptSub as $key => $subi){ echo in_array($key, $opt) ? $subi.', ' : ''; }
								  							}
								  							?>
								  						</i>
								  					</td>
								  				</tr>
								  			</table>

								  			<table style="width: 100%; border-collapse: collapse;margin-top: 15px;">
								  				<tr>
								  					<th style="border: 1px solid; padding: 10px 5px; width: 10%;">Date</th>
								  					<th style="border: 1px solid; padding: 10px 5px; width: 35%; background: #f6f6f6">Subject Name</th>
								  					<th style="border: 1px solid; padding: 10px 5px; width: 10%;">Sub Code</th>
								  					<th style="border: 1px solid; padding: 10px 5px; width: 25%; background: #f6f6f6">Signature of Examinee</th>
								  					<th style="border: 1px solid; padding: 10px 5px; width: 20%;">Signature of Invigilator</th>
								  				</tr>
								  				<?php
								  					
								  					foreach($allmainSubQuery as $sub) { rows($sub->subjectName); }
								  					
								  					if(is_array($opt) && sizeof($opt) > 0){
								  						foreach($alloptSub as $key => $subi){ if(in_array($key, $opt)){ rows($subi); } }
								  					}
									  				
									  				$rows = isset($_GET['rows']) ? $_GET['rows'] : 0;

									  				for($i = 0; $i < $rows; $i++){ rows(); }
									  			?>
								  			</table>

								  			<p style="margin-top: 20px">N.B - Student must be signed by ensuring date in eligible subject. Invigilator will ensure student's sign and date.</p>
								  			<small style="font-size: 11px;color: #888;">Generated  by Bornomala, Developed  by MS3 Technology  BD, Urmi-43, Shibgonj, Sylhet. Email: bornomala.ems@gmail.com</small>
									  	</div>
									  </div>
							  	</div>

								<?php
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
	    var $siteUrl = $('#theSiteURL').text();
	    $.ajax({
	      url: $siteUrl+"/inc/ajaxAction.php",
	      method: "POST",
	      data: { class : $(this).val(), type : 'getExams' },
	      dataType: "html"
	    }).done(function( msg ) {
	      $( "#resultExam" ).html( msg );
	      $( "#resultExam" ).prop('disabled', false);
	    });

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
