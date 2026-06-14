<?php 
/*
** Template Name: Admin ID card
*/
	global $wpdb; global $s3sRedux;
	$conStyle0 = '';
	$conStyle = "margin: 0; font-size: 12px;line-height: 1;margin-bottom:3px;color:blue;";
	$conStyle2 = "$conStyle0 font-size: 13px; color:blue;";
	$conStyle3 = "margin: 0; font-size: 15px;line-height: 1;margin-bottom:1px;color:blue;";
	$conStyle4 = "margin: 0; font-size: 15px;line-height: 1;margin-bottom:1px;color:black;";
	$selected = @$_GET['design'];
	 if(!@$_GET['design']){
	     $selected = 1;
	     
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
	<h2>ID Card Management
		<?php if (!isset($_GET['side'])) { ?>
			<a class="pull-right btn btn-primary" href="<?= home_url('id-card-back-side') ?>?side=back">Back Side</a>
		<?php } ?>
		<a class="pull-right btn btn-primary" href="<?= home_url('teacher-id-card')?>">Teacher ID</a>
		<a class="pull-right btn btn-primary" href="<?= home_url('admin-idcard')?>">Student ID</a>
		
	</h2><br>

	<div class="row">
		<?php 

			if (isset($_GET['side'])) {
				?>
					<div class="col-md-12">
						<div class="panel panel-info">
						  <div class="panel-heading"><h3>ID Card Backside<br><small>Print Student Id Card</small></h3></div>
						  <div class="panel-body">
						  	<form class="form-inline" action="" method="GET">
						  		<input type="hidden" name="page" value="idcard">
						  		<input type="hidden" name="side" value="back">
						  		<div class="form-group">
						  			<label>Total</label>
						  			<input class="form-control" type="number" name="total" >
						  		</div>
						  		<div class="form-group" id="design">
									Design 
									<label class="hoverShowImg">
										<img class="hoverImg" width="200" src="<?php echo get_template_directory_uri()."/img/idDesign1.PNG" ?>">
										<label class="labelRadio">
											<input type="radio" name="design" value="1" <?= $selected == 1 ? 'checked' : null?>>1
										</label>
										<img width="20" src="<?php echo get_template_directory_uri()."/img/idDesign1.PNG" ?>">
									</label>
									<label class="hoverShowImg">
										<img class="hoverImg" width="200" src="<?php echo get_template_directory_uri()."/img/idDesign2.PNG" ?>">
										<label class="labelRadio">
											<input type="radio" name="design" value="2" <?= $selected == 2 ? 'checked' : null?>>2
										</label>
										<img width="20" src="<?php echo get_template_directory_uri()."/img/idDesign2.PNG" ?>">
									</label>
								</div>
						  		<div class="form-group">
						  			<input class="form-control btn btn-success" type="submit" name="genarateBack" value="Genarate ID">
						  		</div>
						  	</form>
						  </div>
						</div>.
					</div>

					<div class="col-md-12">
			  		<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>
					</div>

				  <div id="printArea" class="col-md-12 printBG" >


					  <div class="printArea" style="text-align: center;">
							<style type="text/css"> 
								@page { size: auto  !important;  margin: 0px !important; } 
					  		*{
								  -webkit-print-color-adjust: exact !important;
								  print-color-adjust:exact !important;
								  line-height: 1;
					  		}
					  	</style>
					  	<?php 
					  		$total = 1;
					  		if (isset($_GET['total'])>= 1) { $total = $_GET['total']; }else{$total = 1;}
					  	// 	if ($_GET['total'] >= 1) { $total = $_GET['total']; }
					  	?>
					  	<?php 
                                    $background_image_url = get_template_directory_uri() . '/img/saim-id-design04.jpg';
                                  ?>
					  	<?php for ($i=1; $i <= $total; $i++) {  ?>
					  	<?php
					  	if(!isset($_GET['design']) || $_GET['design'] == 1){
									?>
					<div style="width: 5.3cm;height: 8.3cm;background: #eeeeee;display: inline-block;margin: 5px;border-radius: 10px;overflow: hidden; background-image: url('<?php echo $background_image_url; ?>');
					background-size: cover;background-position: right; position:relative;">

			<!--Mohammad Ziaul Haque ///	<div style="text-align: center;background: blue;padding: 10px;">-->
			<!--			  			<p style="margin: 0;color:#fff"><b>If found please return to</b></p>-->
			<!-- </div>-->

			<!--			  		<div style="padding: 0px;text-align: center;">-->
		                    	<!--<img align="Principal_Signature" style="display: block; margin:115px 5px 0 0;float:right;" height="30" src="<?= $s3sRedux['principalSign']['url'] ?>">--> 
		                    <!--<span style="font-size: 12px;position:absolute;margin:145px 0 10px 70px;"><?= $s3sRedux['inst_head_title'] ?> </span>--> 
		                    
						  			<!-- <h5 style="font-size: 11px; margin: 190px 0 5px 0;"><b><?= $s3sRedux['institute_name'] ?></b></h5>--> 
						  			<!-- <p style="font-size: 11px; margin: 0 0 0px 20px;"><?= $s3sRedux['institute_address'] ?></p>--> 
						  			<!-- <!--<!-- <p style="font-size: 11px; margin: 0 0 0px 20px;">Mob:<?= $s3sRedux['institute_phone'] ?></p>--> 
						  			<!-- <!-- <p style="font-size: 10px; margin: 0 0 0px 20px;">Email:sujanagaridealmadrasah@gmail.com</p>--> 
						  			<!-- <p style="font-size: 11px; margin: 0 0 0px 20px;">Web:www.sujanagar-iam.com</p>-->

			<!--			  			<div class="text-center">-->
			<!--			  				<img alt="barcode" height="85" src="<?= $s3sRedux['barcode']['url'] ?>">-->
			<!--yyyy			  			</div>-->


			<!--			  			<div style="text-align: center;">-->
						  				<!--<img align="Principal_Signature" style="display: block; margin: 15px auto 0;" height="32" src="<?= $s3sRedux['principalSign']['url'] ?>">-->
			<!--			  			</div>-->

			<!--<span style="font-size: 16px;color:blue;"><?= $s3sRedux['inst_head_title'] ?> </span>--> 
                           <!--</br>   </br>-->
   <!--					  		</div>-->
			<!--			  		<div style="font-size: 14px; text-align: center;background: blue;padding: 5px; border-radius: 0 0 10px 10px;overflow: hidden;">-->
			<!--			  			<p style="margin: 0;color:#fff"><b>Validity: Upto 31 Dec, 2024</b></p>-->
			<!--			  		</div>-->
						  		
	
						  	</div>
						  <?php } 	else if( $_GET['design'] == 2) {?>
						  <div style="width: 5.3cm;height: 8.3cm;background: #eeeeee;display: inline-block;margin: 5px;border-radius: 10px;overflow: hidden;">

						  		<div style="text-align: center;background: blue;padding: 10px;">
						  			<p style="margin: 0;color:#fff"><b>If found please return to</b></p>
						  		</div>

						  		<div style="padding: 6px;text-align: center;">
						  			<h4 style="margin: 5px 0 5px 0; color:blue"><b><?= $s3sRedux['institute_name'] ?></b></h4>
						  			<p style="color:blue; font-size: 12px; margin: 0 0 5px 0;"><?= $s3sRedux['institute_address'] ?></p>
						  			<p style="color:blue; font-size: 12px; margin: 0 0 15px 0;"><?= $s3sRedux['institute_phone'] ?></p>

						  			<div class="text-center">
						  				<img alt="barcode" height="90" src="<?= $s3sRedux['barcode']['url'] ?>">
						  			</div>


						  			<div style="text-align: center;">
						  				<img align="Principal_Signature" style="display: block; margin: 15px auto 0;" height="30" src="<?= $s3sRedux['principalSign']['url'] ?>">
						  			</div>

						  			<span style="font-size: 12px;color:blue;"><?= $s3sRedux['inst_head_title'] ?> signature</span>

						  		</div>
						  	</div>
						  <?php } ?>
						  <?php } ?>
						</div>
					</div>

				<?php
			}else{
				?>
				<div class="col-md-12">
					<div class="panel panel-info">
					  <div class="panel-heading"><h3>Student ID Card<br><small>Print Student Id Card</small></h3></div>
					  <div class="panel-body">

							<form class="form-inline" action="" method="GET">
								<input type="hidden" name="page" value="idcard">

								<div class="form-group">
			            <label>Class</label>
			            <select id='resultClass' class="form-control" name="class" required>
			              <?php

			                $classQuery = $wpdb->get_results( "SELECT classid,className FROM ct_class WHERE classid IN (SELECT infoClass FROM ct_studentinfo GROUP BY infoClass ORDER BY className ASC)" );
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
				<div class="form-group" id="idRoll">
									<input class="form-control" type="text" name="roll" placeholder="Roll">
								</div>

								<div class="form-group" id="design">
									Design 
									<label class="hoverShowImg">
										<img class="hoverImg" width="200" src="<?php echo get_template_directory_uri()."/img/idDesign1.PNG" ?>">
										<label class="labelRadio">
											<input type="radio" name="design" value="1" <?= $selected == 1 ? 'checked' : null?>>1
										</label>
										<img width="20" src="<?php echo get_template_directory_uri()."/img/idDesign1.PNG" ?>">
									</label>
									<label class="hoverShowImg">
										<img class="hoverImg" width="200" src="<?php echo get_template_directory_uri()."/img/idDesign2.PNG" ?>">
										<label class="labelRadio">
											<input type="radio" name="design" value="2" <?= $selected == 2 ? 'checked' : null?>>2
										</label>
										<img width="20" src="<?php echo get_template_directory_uri()."/img/idDesign2.PNG" ?>">
									</label>
        							<label class="hoverShowImg">
        								<img class="hoverImg" width="200" src="<?php echo get_template_directory_uri()."/img/idDesign2.PNG" ?>">
        								<label class="labelRadio">
        									<input type="radio" name="design" value="3" <?= $selected == 3 ? 'checked' : null?>>3
        								</label>
        								<img width="20" src="<?php echo get_template_directory_uri()."/img/idDesign2.PNG" ?>">
        							</label>
        							<label class="hoverShowImg">
        								<img class="hoverImg" width="200" src="<?php echo get_template_directory_uri()."/img/idDesign2.PNG" ?>">
        								<label class="labelRadio">
        									<input type="radio" name="design" value="4" <?= $selected == 4 ? 'checked' : null?>>4
        								</label>
        								<img width="20" src="<?php echo get_template_directory_uri()."/img/idDesign2.PNG" ?>">
        							</label>
        					
        							<label class="hoverShowImg">
        								<img class="hoverImg" width="200" src="<?php echo get_template_directory_uri()."/img/idDesign2.PNG" ?>">
        								<label class="labelRadio">
        									<input type="radio" name="design" value="5" <?= $selected == 5 ? 'checked' : null?>>5
        								</label>
        								<img width="20" src="<?php echo get_template_directory_uri()."/img/studentid-design2.jpg" ?>">
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
		} 
 
		if(isset($_GET['syear'])){ ?>  
 
			<div class="col-md-12">
	  		<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>
			</div>

		  <div id="printArea" class="col-md-12 printBG">


			  <div class="printArea" style="text-align: center;">
					<style type="text/css"> 
						@page { size: auto !important;  margin: 0px !important; }
			  		*{
						  -webkit-print-color-adjust: exact !important;
						  print-color-adjust: exact !important;
						  line-height: 1;
			  		}
			  	</style>
			  	<?php

			  		$year 		= $_GET['syear'];
			  		$class 		= $_GET['class'];
			  		$section 	= $_GET['section'];
			  		$roll 		= $_GET['roll'];

			  		if (isset($_GET['syear'])) {
			  			$query = "SELECT studentid,stdName,infoRoll,className,sectionName,stdImg,stdBrith,groupName,infoYear,stdPhone,stdFather,stdMother,stdBldGrp,stdAdmitYear FROM ct_student
								LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid AND ct_student.stdCurrentClass = ct_studentinfo.infoClass
								LEFT JOIN ct_class ON ct_studentinfo.infoClass = ct_class.classid
								LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId
								LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
								WHERE infoYear = '$year' AND infoClass = $class";

				  		if ($_GET['roll'] != ''){
								$query .= " AND infoRoll IN ($roll)";
				  		}
				  		if ($_GET['section'] != 0){
								$query .= " AND infoSection = $section";
				  		}
				  		$query .= " ORDER BY infoRoll ASC";
				  		$groupsBy = $wpdb->get_results( $query );
				  	}


			  		if($groupsBy){

							foreach ($groupsBy as $value) {
								if(!isset($_GET['design']) || $_GET['design'] == 1){
									
									?>

										<!--<div style="width: 5.3cm;height: 8.3cm;background: blue;display: inline-block;margin: 5px;border-radius: 10px;overflow: hidden;">-->

								  <!--		<div style="padding: 6px;text-align: center;background: blue;color:#fff">-->
								  <!--			<h3 style="margin: 0;font-size: 17px;"><?= $s3sRedux['institute_name'] ?></h3>-->
								  <!--		</div>-->

								  <!--		<div style="padding: 6px;text-align: center;margin: 0 7px;border-radius: 0 0 12px 12px;height: 79%;background: #eee">-->
								  <!--			<p style="background: #33dbd3;font-weight: bold;border-radius: 5px;display: inline-block;padding: 0 10px;margin-bottom: 5px;margin-top: 0;">STUDENT ID CARD</p>-->
								  <!--			<div class="text-center">-->
								  <!--				<img alt="Student_Image" width="70" height="80" src="<?php echo ($value->stdImg != "") ? $value->stdImg : get_template_directory_uri()."/img/No_Image.jpg" ?>">-->
								  <!--			</div>-->

								  <!--			<span style="text-align: center;margin: 5px 0;font-size:13px;overflow: hidden;letter-spacing: -1px;display: block;"><b><?= $value->stdName ?></b></span>-->
								  <!--			<p style="<?= $conStyle ?>"><b>ID:</b> <?= ($s3sRedux['stdidpref'] == 'year') ? $value->stdAdmitYear: $s3sRedux['stdidpref']; ?><?= sprintf("%05s", ($value->studentid + $s3sRedux['stdid'] )) ?></p>-->
								  <!--			<p style="<?= $conStyle ?>"><b>Father:</b> <?= $value->stdFather ?></p>-->
								  <!--			<p style="<?= $conStyle ?>"><b>Mother:</b> <?= $value->stdMother ?></p>-->
								  <!--			<p style="<?= $conStyle ?>"><b><?= $value->className ?></b> (<?= $value->sectionName ?>)</p>-->
								  <!--			<p style="<?= $conStyle ?>"><b>Roll:</b> <?= $value->infoRoll ?></p>-->
								  <!--			<p style="<?= $conStyle ?>"><b>Blood Group:</b> <?= $value->stdBldGrp ?></p>-->
								  <!--			<p style="<?= $conStyle ?>"><b>Phone:</b> <?= $value->stdPhone ?></p>-->

								  <!--		</div>-->
								  <!--	</div>-->
								  
								  <div style="width: 5.3cm;height: 8.3cm;background: blue;display: inline-block;margin: 5px;border-radius: 10px;overflow: hidden;">

								  		<!-- <div style="padding: 6px;text-align: center;background: blue;color:#fff">
								  			<h3 style="margin: 0;font-size: 17px;"><?= $s3sRedux['institute_name'] ?></h3>
								  		</div> -->

								  		<div style="padding: 6px;text-align: center;margin: 7px 7px;border-radius: 5px 56px 5px 56px;height: 93%;background: #eee;margin-top:10px">
										  <img width="40" src="<?= $s3sRedux['instLogo']['url'] ?>" style="border-radius: 25px">
										  <h3 style="margin: 0;font-size: 16px;color:blue;font-weight: 700;letter-spacing: -1px;"><?= $s3sRedux['institute_name'] ?></h3>
										  	<!--<p style="font-size: 12px; margin:3px 1px;color: blue;"><?= $s3sRedux['institute_address'] ?></p>-->
										   
								  			<div class="text-center">
								  				<img alt="Student_Image" width="70" height="70" style="border-radius:25px" src="<?php echo ($value->stdImg != "") ? $value->stdImg : get_template_directory_uri()."/img/No_Image.jpg" ?>">
								  			</div>
											  <!--<p style="background: blue;font-weight: bold;border-radius: 5px;display: inline-block;padding: 0 10px;margin-bottom: 0px;margin-top: 2px; color:#fff; padding:5px">STUDENT ID CARD</p>-->
											  <p style="background: blue;font-weight: bold;border-radius: 0px;padding: 0 10px;margin-bottom: 0px;margin-top: 2px; color:#fff; padding:inherit; margin-bottom:2px;min-height:28px;width:187px;margin-left:-6px;display: flex;align-items: center;justify-content: center;"><?= $value->stdName  ?></p>
 
						  			
								  			 <p style="<?= $conStyle ?> "><b>Father:</b> <?= $value->stdFather ?></p>
								  		 <p style="<?= $conStyle ?>"><b>Mother:</b> <?= $value->stdMother ?></p> 
											<p style="<?= $conStyle ?>"><b>Roll:</b> <?= $value->infoRoll ?></p>
											<p style="<?= $conStyle ?>"><b><?= $value->className ?></b> (<?= $value->sectionName ?>)</p>
											<?php if($class == 40 || $class == 41){?>
											<p style="<?= $conStyle ?>"><b>Group:</b> <?= $value->groupName ?></p>
											<?php }?>
											<p style="<?= $conStyle ?>"><b>Year:</b> <?= $value->infoYear ?></p>
											<!--<p style="<?= $conStyle ?>"><b>Valid Up:</b> 30 JUN <?= substr($value->infoYear,0,4) +2?></p>-->
								  			<!-- <p style="<?= $conStyle ?>"><b>Blood Group:</b> <?= $value->stdBldGrp ?></p>-->
								  			<p style="<?= $conStyle ?>"><b>Phone:<?= $value->stdPhone ?></b></p> 

								  		</div>
								  	</div>
								  	
								  	<?php
								}
								else if( $_GET['design'] == 3){
									
									?>
								  <?php
                                    $background_image_url = get_template_directory_uri() . '/img/student-id-design2.jpg';
                                  ?>
								   <div style="width: 5.3cm;height: 8.3cm;background: blue;display: inline-block;margin: 5px;overflow: hidden;background-image: url('<?php echo $background_image_url; ?>');background-size: cover;background-position: center; position:relative;">

						        	<!-- <h3 style="margin: 0;font-size: 18px;color:blue;font-weight: 800; margin:5px 5px 0px 5px; color: #FFFF00;letter-spacing: -.4px;"><?= $s3sRedux['institute_name'] ?></h3>-->  
										 <!-- <p style="font-size: 12px; margin:3px 1px;color: white;"><?= $s3sRedux['institute_address'] ?></p> --> 
										  <div class="text-center">
								  				<img alt="Student_Image" width="85" height="90" style="border-radius:45px;margin: 45px 5px 0px 5px;border: 2px solid #800000;" src="<?php echo ($value->stdImg != "") ? $value->stdImg : get_template_directory_uri()."/img/No_Image.jpg" ?>">
								  			</div>
										  
							<p style="background: #025092;font-weight: bold; font-size: 13px; border-radius: 20px; padding: 3px 5px 3px 5px; margin-bottom: 0px;margin-top: 2px; color:#fff; margin-bottom:2px;min-height:28px;width:207px;margin-left:-6px;display: flex;align-items: center;justify-content: center;"><?= $value->stdName  ?></p>
 
									 <p style="<?= $conStyle ?> "><b>Father:</b> <?= $value->stdFather ?></p>
								  		 <p style="<?= $conStyle ?>"><b>Mother:</b> <?= $value->stdMother ?></p> 
											<p style="<?= $conStyle ?>"><b><?= $value->className ?></b> <br> <b>Roll: <?= $value->infoRoll ?></b></p>
											<!--<p style="<?= $conStyle ?>"><b><?= $value->className ?></b> (<?= $value->sectionName ?>)</p>-->
											<?php if($class == 40 || $class == 41){?>
											<p style="<?= $conStyle ?>"><b>Group:</b> <?= $value->groupName ?></p>
											<?php }?>
											<p style="<?= $conStyle ?>"><b>Year:</b> <?= $value->infoYear ?></p>
											<!--<p style="<?= $conStyle ?>"><b>Valid Up:</b> 30 JUN <?= substr($value->infoYear,0,4) +2?></p>-->
								  			<!-- <p style="<?= $conStyle ?>"><b>Blood Group:</b> <?= $value->stdBldGrp ?></p>-->
								  			<p style="<?= $conStyle ?>"><b>DOB: <?= date('d-m-Y', strtotime($value->stdBrith)) ?></b></p> 
								  			<p style="<?= $conStyle ?>"><b>Phone: <?= $value->stdPhone ?></b></p> 
								  	</div>
			
								  		
		
	                            
	                            	<!-- This is extra design start  --> 						  	
								  	<?php
								}
								else if( $_GET['design'] == 5){
									
									?>
								  <?php
                                    $background_image_url = get_template_directory_uri() . '/img/Studentid-design3.jpg';
                                  ?>
								   <div style="width: 5.3cm;height: 8.3cm;background: blue;display: inline-block;margin: 5px;overflow: hidden;background-image: url('<?php echo $background_image_url; ?>');background-size: cover;background-position: center; position:relative;">

						        		 <!-- <h3 style="margin: 0;font-size: 18px;color:blue;font-weight: 800; margin:5px 5px 0px 5px; color: #FFFF00;letter-spacing: -.4px;"><?= $s3sRedux['institute_name'] ?></h3>--> 
										 <!-- <p style="font-size: 12px; margin:3px 1px;color: white;"><?= $s3sRedux['institute_address'] ?></p> --> 
										  <div class="text-center">
								  				<img alt="Student_Image" width="90" height="95" style="border-radius:45px;margin: 65px 0px 0px 5px; border: 2px solid white;" src="<?php echo ($value->stdImg != "") ? $value->stdImg : get_template_directory_uri()."/img/No_Image.jpg" ?>">
								  			</div>
										  
							<p style="background: #003766;font-weight: bold; font-size: 13px;border-radius: 0px; padding: 3px 10px 3px 10px; margin-bottom: 2px; margin-top: 1px; color:#fff; margin-bottom:2px;min-height:30px;width:207px;margin-left:-6px;display: flex;align-items: center;justify-content: center;"><?= $value->stdName  ?></p>
 
									<p style="<?= $conStyle ?> "><b>Father:</b> <?= $value->stdFather ?></p>
								  		 <p style="<?= $conStyle ?>"><b>Mother:</b> <?= $value->stdMother ?></p> 
											<p style="<?= $conStyle ?>"><b><?= $value->className ?></b><br> <b>Roll:</b> <?= $value->infoRoll ?></p>
											<!--<p style="<?= $conStyle ?>"><b><?= $value->className ?></b> (<?= $value->sectionName ?>)</p>-->
											<?php if($class == 40 || $class == 41){?>
											<p style="<?= $conStyle ?>"><b>Group:</b> <?= $value->groupName ?></p>
											<?php }?>
											<p style="<?= $conStyle ?>"><b>Year:</b> <?= $value->infoYear ?></p>
											<!--<p style="<?= $conStyle ?>"><b>Valid Up:</b> 30 JUN <?= substr($value->infoYear,0,4) +2?></p>-->
								  			<!-- <p style="<?= $conStyle ?>"><b>Blood Group:</b> <?= $value->stdBldGrp ?></p>-->
								  			<p style="<?= $conStyle ?>"><b>DOB: <?= date('d-m-Y', strtotime($value->stdBrith)) ?></b></p> 
								  			<p style="<?= $conStyle ?>"><b>Phone: <?= $value->stdPhone ?></b></p> 
								  	</div>
                            
	                            
	                            	<!-- This is extra design start  --> 						  	
								  	<?php
								}
								else if( $_GET['design'] == 6){
									
									?>
								  <?php
                                    $background_image_url = get_template_directory_uri() . '/img/student-id-design8.jpg';
                                  ?>
								   <div style="width: 5.3cm;height: 8.3cm;background: blue;display: inline-block;margin: 5px;overflow: hidden;background-image: url('<?php echo $background_image_url; ?>');background-size: cover;background-position: center; position:relative;">

						        		 <!-- <h3 style="margin: 0;font-size: 18px;color:blue;font-weight: 800; margin:5px 5px 0px 5px; color: #FFFF00;letter-spacing: -.4px;"><?= $s3sRedux['institute_name'] ?></h3>--> 
										 <!-- <p style="font-size: 12px; margin:3px 1px;color: white;"><?= $s3sRedux['institute_address'] ?></p> --> 
										  <div class="text-center">
								  				<img alt="Student_Image" width="90" height="95" style="border-radius:45px;margin: 65px 0px 0px 5px; border: 2px solid white;" src="<?php echo ($value->stdImg != "") ? $value->stdImg : get_template_directory_uri()."/img/No_Image.jpg" ?>">
								  			</div>
										  
							<p style="background: #ADD8E6;font-weight: bold; border-radius: 0px; padding: 3px 10px 3px 10px; margin-bottom: 2px; margin-top: 1px; color:#800000; margin-bottom:2px;min-height:28px;width:207px;margin-left:-6px;display: flex;align-items: center;justify-content: center;"><?= $value->stdName  ?></p>
 
									<p style="<?= $conStyle ?> "><b>Father:</b> <?= $value->stdFather ?></p>
								  		 <p style="<?= $conStyle ?>"><b>Mother:</b> <?= $value->stdMother ?></p> 
											<p style="<?= $conStyle ?>"><b><?= $value->className ?></b><br> <b>Roll: <?= $value->infoRoll ?></b></p>
											<!--<p style="<?= $conStyle ?>"><b><?= $value->className ?></b> (<?= $value->sectionName ?>)</p>-->
											<?php if($class == 40 || $class == 41){?>
											<p style="<?= $conStyle ?>"><b>Group:</b> <?= $value->groupName ?></p>
											<?php }?>
											<p style="<?= $conStyle ?>"><b>Year:</b> <?= $value->infoYear ?></p>
											<!--<p style="<?= $conStyle ?>"><b>Valid Up:</b> 30 JUN <?= substr($value->infoYear,0,4) +2?></p>-->
								  			<!-- <p style="<?= $conStyle ?>"><b>Blood Group:</b> <?= $value->stdBldGrp ?></p>-->
								  			<p style="<?= $conStyle ?>"><b>DOB: <?= date('d-m-Y', strtotime($value->stdBrith)) ?></b></p> 
								  			<p style="<?= $conStyle ?>"><b>Phone: <?= $value->stdPhone ?></b></p> 
								  	</div>
                            
                            <!-- This is extra design closed  5--> 	
                            <!-- This is extra design closed 6 --> 							  	
								  	
							<?php }	else if( $_GET['design'] == 4){
									?>
								  <?php
                                    $background_image_url = get_template_directory_uri() . '/img/sujanagor-id-design-main-4.jfif';
                                  ?>
								   <div style="width: 5.3cm;height: 8.7cm;background: blue;display: inline-block;margin: 5px;overflow: hidden;background-image: url('<?php echo $background_image_url; ?>');background-size: cover;background-position: center; position:relative;">
                            <!-- <img width="40" src="<?= $s3sRedux['instLogo']['url'] ?>" style="border-radius: 25px; margin: 14px 0 0 15px;">-->
							<!--<h3 style="margin: -3px 0 0 22px;font-size: 18px;font-weight: 750; color: #800000;letter-spacing: -.25px;"><?= $s3sRedux['institute_name'] ?></h3>-->
										  <!--<p style="font-size: 12px; margin:3px 1px;color: white;"><?= $s3sRedux['institute_address'] ?></p>-->
										  <div class="text-center">
								  				<img alt="Student_Image" width="80" height="85" style="border-radius:45px;margin: 45px 0px 0px 5px;" src="<?php echo ($value->stdImg != "") ? $value->stdImg : get_template_directory_uri()."/img/No_Image.jpg" ?>">
								  			</div>
				 	<p style="background: #ff7800;font-weight: bold; border-radius: 40px; margin-bottom: 0px; font-size:14px;margin: 5px -2px 0px 5px; color:#fff; 
					 padding:inherit; margin-bottom:2px;min-height:28px;margin-left:-6px;display: flex;align-items: center;justify-content: center;  padding: 4px 4px; font-family: sans-serif;"><?= $value->stdName  ?></p>
 				  		  	
							<!-- <p style="font-weight: bold;border-radius: 0px;padding: 0 10px; font-size:13px; margin-bottom: 0px;margin-top: 6px; color:#800000; padding:inherit; margin-left:-6px;display: flex;align-items: center;justify-content: center;"><?= $value->stdName  ?></p> --> 
									 <!-- This is student info details table start  --> 
									  <table style="position:absolute; margin: 5px 0 0 15px; width:95%;font-size:12px;color:black;text-align:left;">
										     	<tr>
										          <td style="width:21%;"><b>DoB</b></td>
										          <td>:  <?= date('d-m-Y', strtotime($value->stdBrith)) ?></td>
										      </tr> 
											      <tr>
										          <td style="width:21%;"><b>Father </b></td>
										          <td style="font-size:10px;"> : <?= $value->stdFather ?> </td>
										      </tr>
										      <tr>
										          <td style="width:21%;"><b> Mother </b></td>
										          <td style="font-size:10px;">:  <?= $value->stdMother ?></td>
										      </tr>								      
										      <tr>
										          <td style="width:21%;"><b>Class </b></td>
										          <td>: <?= $value->className ?> (<?= $value->sectionName ?>)</td>
										      </tr>
										      <tr>
										          <td style="width:21%;"><b>Roll </b></td>
										          <td>: <?= $value->infoRoll ?></td>
										      </tr>
										      <tr>
										          <td style="width:21%;"><b>Year </b></td>
										          <td>: <?= $value->infoYear ?></td>
										      </tr>
										      <!--<tr>-->
										      <!--    <td style="width:31%;"><b>Blood </b></td>-->
										      <!--    <td><b>: <?= $value->stdBldGrp ?></b></td>-->
										      <!--</tr>-->
										      <tr>
										          <td style="width:21%;"><b>Phone</b></td>
										          <td>: <?= $value->stdPhone ?></td>
										      </tr>
										  </table>
										  	 <!-- This is student info details table Closed  --> 
                   <!-- <img align="Principal_Signature" style="display: block; position:absolute; margin: 75px 0 0 111px;" width="60" src="<?= $s3sRedux['principalSign']['url'] ?>">--> 
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
								  				<img alt="Student_Image" width="70" height="80" src="<?php echo ($value->stdImg != "") ? $value->stdImg : get_template_directory_uri()."/img/No_Image.jpg" ?>">
								  			</div>

								  			<span style="text-align: center;margin: 3px 0;font-size:12px;overflow: hidden;letter-spacing: -1px;display: block;"><b><?= $value->stdName ?></b></span>
								  			<p style="<?= $conStyle ?>"><b>ID:</b> <?= ($s3sRedux['stdidpref'] == 'year') ? $value->stdAdmitYear: $s3sRedux['stdidpref']; ?><?= sprintf("%05s", ($value->studentid + $s3sRedux['stdid'] )) ?></p>
								  			<p style="<?= $conStyle ?>"><b>Father:</b> <?= $value->stdFather ?></p>
								  			<p style="<?= $conStyle ?>"><b><?= $value->className ?></b> (<?= $value->sectionName ?>)</p>
								  			<p style="<?= $conStyle ?>"><b>Year:</b> <?= $value->infoYear ?></p>
								  			<p style="<?= $conStyle ?>"><b>Roll:</b> <?= $value->infoRoll ?></p>
								  			<p style="<?= $conStyle ?>"><b>Blood Group:</b> <?= $value->stdBldGrp ?></p>
								  			<p style="<?= $conStyle ?>"><b>Phone:</b> <?= $value->stdPhone ?></p>

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
    w.document.write('<scr' + 'ipt type="text/javascript">' + 'window.onload = function() { window.print();  };' + '</sc' + 'ript>');
    w.document.close(); // necessary for IE >= 10
    w.focus(); // necessary for IE >= 10
    return true;
  }
</script>