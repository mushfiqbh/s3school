<?php
/*
** Template Name: Admin Testimonail
*/
 global $wpdb; global $s3sRedux; 
 function dateToWords($date) {
    // Define arrays for months and days
    $months = array("", "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
    $days = array("", "1st", "2nd", "3rd", "4th", "5th", "6th", "7th", "8th", "9th", "10th", "11th", "12th", "13th", "14th", "15th", "16th", "17th", "18th", "19th", "20th", "21st", "22nd", "23rd", "24th", "25th", "26th", "27th", "28th", "29th", "30th", "31st");

    // Split the date into day, month, and year
    $dateParts = explode("-", $date);
    $day = (int)$dateParts[0];
    $month = (int)$dateParts[1];
    $year = (int)$dateParts[2];

    // Validate the date
    if (!checkdate($month, $day, $year)) {
        return "Invalid date!";
    }

    // Convert the year into words
    $yearInWords = numberToWords($year);

    // Create the text representation
    $textDate = $days[$day] . " " . $months[$month] . " " . $yearInWords;

    return $textDate;
}

// Function to convert a number into words (for the year)
function numberToWords($number) {
    // Add support for converting four-digit numbers (years) to words
    $ones = array("", "One", "Two", "Three", "Four", "Five", "Six", "Seven", "Eight", "Nine");
    $teens = array("Eleven", "Twelve", "Thirteen", "Fourteen", "Fifteen", "Sixteen", "Seventeen", "Eighteen", "Nineteen");
    $tens = array("", "Ten", "Twenty", "Thirty", "Forty", "Fifty", "Sixty", "Seventy", "Eighty", "Ninety");

    if ($number < 10) {
        return $ones[$number];
    } elseif ($number < 20) {
        return $teens[$number - 11];
    } elseif ($number < 100) {
        return $tens[(int)($number / 10)] . (($number % 10 !== 0) ? ' ' . $ones[$number % 10] : '');
    } elseif ($number < 1000) {
        return $ones[(int)($number / 100)] . ' Hundred' . (($number % 100 !== 0) ? ' and ' . numberToWords($number % 100) : '');
    } elseif ($number < 10000) {
        return $ones[(int)($number / 1000)] . ' Thousand' . (($number % 1000 !== 0) ? ' ' . numberToWords($number % 1000) : '');
    } else {
        // Handle other cases if needed (up to billions or more)
        return $number;
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

<div class="container-fluid maxAdminpages" style="padding-left: 0">
	<p id="theSiteURL" class="hidden"><?= get_template_directory_uri() ?></p>

	<div class="panel panel-info">
	  <div class="panel-heading">
			<ul class="nav nav-tabs" id="myTab" role="tablist">
			  <li class="nav-item active">
			    <a class="nav-link active" id="home-tab" data-toggle="tab" href="#home" role="tab" aria-controls="home" aria-selected="true">Testimonail</a>
			  </li>
			  <li class="nav-item">
			    <a class="nav-link" id="profile-tab" data-toggle="tab" href="#profile" role="tab" aria-controls="profile" aria-selected="false">Board Testimonail</a>
			  </li>
			</ul>
		</div>
		<div class="panel-body">
			<div class="tab-content" id="myTabContent">
			  <div class="tab-pane fade active in" id="home" role="tabpanel" aria-labelledby="home-tab">
					<form class="form-inline" action="" method="GET">
						<input type="hidden" name="page" value="testimonail">

						<div class="form-group">
							<label>Class</label>
							<select class="resultClass form-control" name="class" required>
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
							<label>Exam</label>
							<select class="resultExam form-control" name="exam" required disabled>
								<option disabled selected>Select Class</option>
							</select>
						</div>

						<div class="form-group ">
							<label>Section</label>
							<select class="resultSection" class="form-control" name="section" required disabled>
								<option disabled selected>Select Class</option>
							</select>
						</div>

						<div class="form-group">
							<label>Year</label>
							<select class="resultYear form-control" name="syear" required disabled>
								<option disabled selected>Select Class</option>
							</select>
						</div>

	
						<div class="form-group" id="idRoll">
							<input class="form-control" type="text" name="roll" placeholder="Roll" style="width: 110px">
						</div>
						<div class="form-group">
							<input type="submit" name="creatId" value="Create" class="btn btn-primary">
						</div>
					</form>
			 	</div>
			  <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">
			  	<form class="form-inline" action="" method="GET">
						<input type="hidden" name="page" value="testimonail">
						<input type="hidden" name="bord" value="1">

						<div class="form-group">
							<select class="resultClass form-control" name="class" required>
								<?php
									$classQuery = $wpdb->get_results( "SELECT classid,className FROM ct_class WHERE classid IN (SELECT examClass FROM ct_exam GROUP BY examClass ORDER BY className ASC)" );
									echo "<option value=''>Class</option>";

									foreach ($classQuery as $class) {
										echo "<option value='".$class->classid."'>".$class->className."</option>";
									}
								?>
							</select>
						</div>

						<div class="form-group ">
							<select class="resultSection" class="form-control" name="section" required disabled>
								<option disabled selected>Select Class</option>
							</select>
						</div>

						<div class="form-group">
							<select class="resultYear form-control" name="syear" required disabled>
								<option disabled selected>Select Class</option>
							</select>
						</div>

						<div class="form-group ">
							<input class="resultExam form-control" type="text" name="exam" placeholder="Exan Name" style="width: 100px">
						</div>

						<div class="form-group" style="display:none;">
							<input class="form-control" type="text" name="rollstart" placeholder="Roll Start From" style="width: 115px">
						</div>

						<div class="form-group" style="display:none;">
							<input class="form-control" type="text" name="regstart" placeholder="Reg Start From" style="width: 115px">
						</div>

						<div class="form-group ">
							<input type="text" name="resSession" class="form-control" placeholder="Session" style="width: 100px">
						</div>
						<div class="form-group " style="">
							<input type="text" name="resPassingYear" class="form-control" placeholder="Passing Year" style="width: 100px">
						</div>

	
						<div class="form-group" id="idRoll">
							<input class="form-control" type="text" name="roll" placeholder="Class Roll" style="width: 110px">
						</div>
						<div class="form-group">
							<input type="submit" name="creatId" value="Create" class="btn btn-primary">
						</div>
					</form>
			  </div>
			</div>
		</div>
	</div>


	<div class="row">
		<div class="col-md-12">
			
		<?php if(isset($_GET['syear'])){ ?>
			<div class="col-md-12">
		  	<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>
		  </div>
		  <div id="printArea" class="col-md-12 printBG" >

			  <div class="printArea" style="margin: 10px 30px 0;">
			  	<link href="https://fonts.googleapis.com/css?family=Satisfy" rel="stylesheet">
					<link href="https://fonts.googleapis.com/css?family=Quicksand" rel="stylesheet"> 
					<style type="text/css">
						@page { margin: 0; size: auto; }
						.itemMainBox{
							max-width: 8.27in; 
							display: inline-block;
							border: 10px solid #005daa;
						  overflow: hidden;
							margin: 20PX 0;
						  font-family: sans-serif;
						  width: 100%;
						  position: relative;
						}
						.itemMainBox p {
							font-size: 16px;
							font-family: 'Quicksand', sans-serif;
							line-height: 2;
						}
						.itemMainBox .itemWaterMark{
							position: absolute;
							width: 100%;
							bottom: 0;
							left: 0;
							z-index: -1;
							text-align: center;
						}
						.itemMainBox .itemWaterMark  img{
							opacity: .12;
    					width: 250px;
						}
						.itemMainBox .instLogo{
							width: 90px; position: absolute;left: 0;top: 0;
						}
						.itemMainBox .instName{
							margin: 0 0 5px 0;
					    color: #337ab7;
					    font-weight: bold;
					    font-size: 30px;
						}
						.itemMainBox .instAddrs{
							margin: 0 0 15px 0;color: #888888;font-size: 18px;
						}
						.itemMainBox .examName{
							margin: 0 auto 7px;
					    text-align: center;
					    font-size: 25px;
						}
						.itemMainBox .examName h3{
							margin: 0;
							font-size: 20px;
						}
						.itemMainBox .itemInfo{
							text-align: center; margin: 20px 0; clear: both;
						}
						.itemMainBox .admitCard{
							margin: 0 0 10px 0;color: #f7740c; font-weight: bold; background: #f0f0f0;-webkit-print-color-adjust: exact; padding: 10px; border-radius: 5px; font-size: 25px;
							border: 2px solid #f0f0f0;
						}
						.itemMainBox .admitNote{
							float: left;
						}
						.itemMainBox .admitNote p{
							margin: 0;
							padding-left: 15px;
						}
						.itemMainBox hr{
							clear: both;
						}
						.itemMainBox .princSign{
							float: right;
						}
						b u{
							font-family: 'Rechtman', sans-serif;
							text-decoration: none;
							padding: 0 5px;
						}
						.editable{
							position: relative;
						}
						.editable .closeEdit {
							position: absolute;
							z-index: 5;
							cursor: pointer;
							right: 0px;
							top: -6px;
							width: 15px;
							text-indent: 0;
							margin: 0;
							padding: 0;
							background: rgba(255,0,0,.8);
							border-radius: 3px;
							text-align: center;
							line-height: ;
							color: #fff;
							font-family: arial;
							-webkit-touch-callout: none;
							-webkit-user-select: none;
							-khtml-user-select: none;
							-moz-user-select: none;
							-ms-user-select: none;
							user-select: none;
						}
						#wrapper {
							position: absolute;
							overflow: auto;
							left: 0;
							right: 0;
							top: 0;
							bottom: 0;
							border: 15px solid #d0bf9e;
						}
					
					</style>

			  	<?php
			  		$year 		= @$_GET['syear'];
			  		$class 		= @$_GET['class'];
			  		$section 	= @$_GET['section'];
			  		$roll 		= @$_GET['roll'];
			  		$exam 		= @$_GET['exam'];

			  		if (!isset($_GET['bord'])) {
			  			$query = "SELECT stdName,stdGender,infoRoll,sscRoll,sscReg,stdGPA,stdIntellectual,className,sectionName,stdImg,groupName,infoYear,stdPhone,stdFather,stdMother,examName,spPosition,spPoint,stdPermanent, stdBrith FROM ct_student
								LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid
								LEFT JOIN ct_class ON ct_studentinfo.infoClass = ct_class.classid
								LEFT JOIN ct_exam ON $exam = ct_exam.examid
								LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId
								LEFT JOIN ct_studentPoint ON ct_studentinfo.infoStdid = ct_studentPoint.spStdID AND ct_studentPoint.spYear = '$year' AND ct_studentPoint.spClass = $class AND ct_studentPoint.spExam = $exam
								LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
								WHERE infoYear = '$year'";

				  		if ($_GET['roll'] != ''){
								$query .= " AND infoRoll = $roll";
				  		}
				  		if ($_GET['section'] != 0){
								$query .= " AND infoSection = $section";
				  		}
				  		$query .= " ORDER BY infoRoll ASC";
				  		$groupsBy = $wpdb->get_results( $query );

				  		if($groupsBy){

								foreach ($groupsBy as $value) {
									?>
										<div class="itemMainBox">
										<div id="wrapper"></div>
											<div style="padding: 50px 30px; ">
												<div style="text-align: center;" >
													<div style="position: relative;">

														<h2 class="instName"><?= $s3sRedux['institute_name'] ?></h2>
										  			<h4 class="instAddrs"><?= $s3sRedux['institute_address'] ?></h4>
														<img width="80" src="<?= $s3sRedux['instLogo']['url'] ?>">
													</div>
												</div>
												
												<div class="section3" style="background: url(<?= $s3sRedux['instLogo']['url'] ?>) no-repeat center; background-size: 400px; ">
	                                         	<div style="background: rgba(255,255,255,0.9);">

														    
												<div class="itemInfo">
									  			<h3 style="text-transform: uppercase;">Testimonial</h3>
												</div>
															
												<div style="width: 100%;">
													<p> This is to Certify that, 
														<b><u><?= $value->stdName ?></u></b> 
														<?= ($value->stdGender == 0) ? 'daughter' : 'son' ?> 
														of Mr. <b><u><?= $value->stdFather ?></u></b> &  
														 <b><u><?= $value->stdMother ?> </u></b>
														is a student of this institution in Class <b><u><?= $value->className ?>,</u></b> 
														<?= isset($value->sectionName) ? "Section <b><u>$value->sectionName</u></b>" : '' ?> 
														<?= isset($value->groupName) ? "Group <b><u>$value->groupName</u></b>" : '' ?> 
														Roll No. <b><u><?= $value->infoRoll ?></u></b> 
														and <?= ($value->stdGender == 0) ? 'she' : 'he' ?> appeared at <b><u><?= $value->examName ?></u></b> held in <b><u><?= $year ?></u></b> and 
														placed /merit position <?= isset($value->spPosition) ? "<b><u>".$value->spPosition."</u></b>" : '............' ?> 
														GPA- <?= isset($value->spPoint) ? "<b><u>".$value->spPoint."</u></b>" : '............' ?>. 
														According to admission register, <?= ($value->stdGender == 0) ? 'her' : 'his' ?> 
														date of birth is <b><?= date('d-m-Y', strtotime($value->stdBrith))?></b> in words <?= dateToWords(date('d-m-Y', strtotime($value->stdBrith)))?>.	</p>
													<p style="text-indent: 30px">
														<?= ($value->stdGender == 0) ? 'She' : 'He' ?> bears a good moral  character. 
														So far I know <?= ($value->stdGender == 0) ? 'she' : 'he' ?> was not involved in any activity subversive of the State or 
														discipline of this institute.
													</p> 
													<P></P>	<P></P>
													<p style="padding-left: 20px">I wish <?= ($value->stdGender == 0) ? 'her' : 'him' ?> every success in life.</p>
										  		<table style="width: 100%; margin-top: 220px;">
										  			<tr>
										  				<td>
										  					<p>Prepared by: .................</p>
										  					<p>Date: <?= date('d-m-Y') ?> </p>
										  				</td>
										  				<td style="width: 300px; text-align: center;">
														  .............................
														  	<h4 style="margin-bottom: 0">Mohammad Anwar Hussain</h4>
										  					<p style="margin: 0"><?= $s3sRedux['inst_head_title'] ?></p>
										  					<p style="margin: 0"><?= $s3sRedux['institute_name'] ?></p>
										  					<p style="margin: 0"><?= $s3sRedux['institute_address'] ?></p>
										  				</td>
										  			</tr>
										  		</table>
									  		</div>
										  	
									  	</div>
									  		
							  			<div style="clear: both;"></div>
							  		</div>
							  		</div>
							  		</div>

									<?php
								}
							}else{
								echo "<h3 class='text-center'>No Student Found</h3>";
							}
				  	}else {
				  		$session = @$_GET['resSession'];
			  		    $passingyear 		= @$_GET['resPassingYear'];
			  			$query = "SELECT stdName,stdGender,infoRoll,className,sscRoll,sscReg,stdGPA,stdIntellectual,sectionName,stdImg,groupName,infoYear,stdPhone,stdFather,stdMother, stdBrith FROM ct_student
								LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid
								LEFT JOIN ct_class ON ct_studentinfo.infoClass = ct_class.classid
								LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId
								LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
								WHERE infoYear = '$year'";

				  		if ($_GET['roll'] != ''){
								$query .= " AND infoRoll = $roll";
				  		}
				  		if ($_GET['section'] != 0){
								$query .= " AND infoSection = $section";
				  		}
				  		$query .= " ORDER BY infoRoll ASC";

				  		$groupsBy = $wpdb->get_results( $query );
				  		$roll = $_GET['rollstart'] != '' ? $_GET['rollstart'] : "...";
				  		$reg = $_GET['regstart'] != '' ? $_GET['regstart'] : "...";
				  		if($groupsBy){

								foreach ($groupsBy AS $key => $value) {
									?>
										<div class="itemMainBox">
										    <div id="wrapper"></div>
											<div style="padding: 50px 30px; ">
												<div style="text-align: center;" >
													<div style="position: relative;">

														<h2 class="instName"><?= $s3sRedux['institute_name'] ?></h2>
										  			<h4 class="instAddrs"><?= $s3sRedux['institute_address'] ?></h4>
														<img width="80" src="<?= $s3sRedux['instLogo']['url'] ?>">
													</div>
												</div>
												
												<div class="section3" style="background: url(<?= $s3sRedux['instLogo']['url'] ?>) no-repeat center; background-size: 400px;">
                                                <div style="background: rgba(255,255,255,0.9);">
														    
												
		                                       
		                                       <div class="itemInfo">
									  			<h3 style="text-transform: uppercase;">Testimonial</h3>
												</div>


												<div style="width: 100%;" style="background: url(<?= $s3sRedux['instLogo']['url'] ?>) no-repeat center; background-size: 400px; ">
													<p style="text-indent: 30px">
														This is to Certify that, 
														<b><u><?= $value->stdName ?></u></b><?= ($value->stdGender == 0) ? 'daughter' : 'son' ?> of <b><u><?= $value->stdFather ?></u></b> and <b><u><?= $value->stdMother ?></u></b> 
														passed the <b><u><?= $exam ?></u></b> Examination from this institution held in the year <b class='editable'><u><?= $passingyear ?></u></b>
														under the Board of Intermediate and Secondary Education, Sylhet in <?= isset($value->groupName) ? "Group <b><u>$value->groupName</u></b>" : '' ?>
														bearing Roll Sylhet-1 No <b class='editable'><u><?= $value->sscRoll ?></u></b> 
														Registration No<b class='editable'><u><?= $value->sscReg ?></u></b> Session <b><u><?= $session ?></u></b> and 
														obtained GPA <b class='editable'><u><?= $value->stdGPA ?></u></b> Letter Grade <b class='editable'><u><?= $value->stdIntellectual ?></u></b>. 
													<?= ($value->stdGender == 0) ? 'Her' : 'His' ?> 
														date of birth is <b><?= date('d-m-Y', strtotime($value->stdBrith))?></b> in words <?= dateToWords(date('d-m-Y', strtotime($value->stdBrith)))?>. 
													</p>
													<p style="text-indent: 30px">
														<?= ($value->stdGender == 0) ? 'She' : 'He' ?> bears a good moral  character. 
														So far I know <?= ($value->stdGender == 0) ? 'she' : 'he' ?> was not involved in any activity subversive of the State or 
														discipline of this institute. 
													</p>
														
													<p style="text-indent: 30px">I wish <?= ($value->stdGender == 0) ? 'her' : 'him' ?> every success in life.</p>
												
										  		<table style="width: 100%; margin-top: 220px;">
										  			<tr>
										  				<td>
										  					<p>Prepared by: .................</p>
										  					<p>Date: <?= date('d-m-Y') ?> </p>
										  				</td>
										  	<td style="width: 300px; text-align: center;">
														  .............................
														  	<h4 style="margin-bottom: 0">Mohammad Anwar Hussain</h4>
										  					<p style="margin: 0"><?= $s3sRedux['inst_head_title'] ?></p>
										  					<p style="margin: 0"><?= $s3sRedux['institute_name'] ?></p>
										  					<p style="margin: 0"><?= $s3sRedux['institute_address'] ?></p>
										  				</td>
										  			</tr>
										  		</table>
									  		</div>
										  	
									  	</div>
									  		
							  			<div style="clear: both;"></div>
							  		</div>
							  		</div>
							  		</div>

									<?php
									$roll++;
									$reg++;
								}
							}else{
								echo "<h3 class='text-center'>No Student Found</h3>";
							}
				  	}
			  		

			  	?>

			  </div>
		  </div>
		  <div id="editor"></div>
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

		$('.editable').on('click', 'u', function(){
			$this = $(this);
			$this.closest('.editable').html("").append("<input type='text' value='"+$this.text()+"'><p class='closeEdit'>x</p>");
		});



 		$('.editable').on('focusout', 'input', function(){
 			$this = $(this);
			$this.closest('.editable').html("<u>"+$this.val()+"</u>");
 		});

		$('.resultClass').change(function() {
			$from = $(this).closest('form');
	    var $siteUrl = $('#theSiteURL').text();
	    $.ajax({
	      url: $siteUrl+"/inc/ajaxAction.php",
	      method: "POST",
	      data: { class : $(this).val(), type : 'getExams' },
	      dataType: "html"
	    }).done(function( msg ) {
	      $from.find(".resultExam").html( msg ).prop('disabled', false);
	    });

	    $.ajax({
	      url: $siteUrl+"/inc/ajaxAction.php",
	      method: "POST",
	      data: { class : $(this).val(), type : 'getYears' },
	      dataType: "html"
	    }).done(function( msg ) {
	      $from.find(".resultYear" ).html( msg ).prop('disabled', false);
	    });

	    $.ajax({
	      url: $siteUrl+"/inc/ajaxAction.php",
	      method: "POST",
	      data: { class : $(this).val(), type : 'getSection' },
	      dataType: "html"
	    }).done(function( msg ) {
	      $from.find(".resultSection" ).html( msg ).prop('disabled', false);
	    });
	  });
  })( jQuery );
  
	function print(divId) {
    var printContents = document.getElementById(divId).innerHTML;
    w = window.open();
    w.document.write(printContents);
    w.document.write('<scr' + 'ipt type="text/javascript">' + 'window.onload = function() { window.print(); };' + '</sc' + 'ript>');
    w.document.close(); // necessary for IE >= 10
    w.focus(); // necessary for IE >= 10
    return true;
  }
</script>