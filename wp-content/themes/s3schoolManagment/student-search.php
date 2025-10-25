<?php

/**
 * Template Name: Student search
 */

get_header(); ?>

	<div class="b-page-wrap">
		<div class="b-page-content with-layer-bg">
			<div class="b-layer-big otherPageBg">
				<div class="layer-big-bg page-layer-big-bg">
					<div class="layer-content-big text-center">
						<h2>Student Search</h2>
					</div>
				</div>
			</div>
		</div>
	</div>



	<div class="b-layer-main">
		<div class="page-arrow">
			<i class="fa fa-angle-down" aria-hidden="true"></i>
		</div>
		<div class="b-blog-classic">
			<div class="container">
				<div class="row">
					<div class="col-xs-12 col-sm-7 col-md-8 col-lg-9">

						<?php 
						if(!isset($_POST['showStudent']) ){ 
							//==================
							//	Search
							//==================
							?>

							<div class="b-blog-items-holder wow slideInLeft">
								<div class="clearfix aboutUsPageContent">

									<div class="panel panel-default">
									  <div class="panel-heading">Student Search</div>
									  <div class="panel-body">

									  	<form action="" method="POST">
										  	<div class="row">
										  		<div class="col-md-6">
										  			<div class="form-group">
															<label>Class: *</label>
															<select id="classck" class="form-control" name="class" required>
																<option disabled selected>Select Class </option>
							                  <?php
							                  $classQuery = $wpdb->get_results( "SELECT classid,className FROM ct_class WHERE classid IN (SELECT infoClass FROM ct_studentinfo GROUP BY infoClass ORDER BY className ASC)" );
								                echo "<option value=''>Select Class</option>";

								                foreach ($classQuery as $class) {
								                  echo "<option value='".$class->classid."'>".$class->className."</option>";
								                }
																?>
							                </select>
														</div>
										  		</div>
										  		<div class="col-md-6 sectionDiv">
										  			<div class="form-group">
															<label>Section:</label>
															<select id="secSear" name="sections" class="form-control" required>
																<option selected value="">Select class first </option>
															</select>
														</div>
										  		</div>

										  		<div class="col-md-6">
										  			<div class="form-group">
															<label>Group: </label>
															<select name="group" class="form-control">
																<option selected value="">Select Group </option>
																<?php
											            $groups = $wpdb->get_results("SELECT * FROM ct_group");
											            foreach ($groups as $groups) {
											            	$selected = '';
											            	if (isset($edit)) {
											              	$selected = ($edit->infoGroup == $groups->groupId) ? 'selected' : '';
											            	}
											              ?>
											              <option value='<?= $groups->groupId ?>' <?= $selected ?>>
											                <?= $groups->groupName ?>
											              </option>
											              <?php
											            }
											          ?>
															</select>
														</div>
										  		</div>

										  		<div class="col-md-6">
										  			<div class="form-group">
															<label>Academic Year: *</label>
															<select id="syear" name="syear" class="form-control" required>
															<option selected value="">Select class first </option>
															</select>
														</div>
										  		</div>

										  		<div class="col-md-6">
										  			<div class="form-group">
															<label>Roll No:</label>
															<input type="number" name="roll" class="form-control" required>
														</div>
										  		</div>

										  		<div class="col-md-12">
										  			<div class="form-group">
															<button name="showStudent" type="submit" class="btn btn-secondary pull-right">Search</button>
														</div>
										  		</div>

										  	</div>
											</form>

									  </div>
									</div>
								</div>
							</div>

							<?php 
						}else{

							//====================
							//	Student View
							//====================
							if($_POST['class'] != '' && $_POST['syear'] != '' && is_numeric($_POST['roll'])){

								$stdClass 			= $_POST['class'];
								$stdSection 	 	= isset($_POST['sections']) ? $_POST['sections'] : '';
								$stdGroup	 			= isset($_POST['group']) ? $_POST['group'] : '';
								$stdYear 				= $_POST['syear'];
								$stdRoll 				= $_POST['roll'];
								$query = "SELECT stdImg,stdName,infoRoll,stdBrith,stdReligion,stdNationality,stdPermanent,stdPresent,stdFather,stdFatherProf,stdMother,stdMotherProf,stdPrevSchool,stdTcNumber,stdGPA,className,groupName,sectionName
									FROM ct_student
									LEFT JOIN ct_studentinfo 	 ON ct_studentinfo.infoStdid = ct_student.studentid
									LEFT JOIN ct_class 	 ON ct_studentinfo.infoClass  = ct_class.classid
									LEFT JOIN ct_group 	 ON ct_studentinfo.infoRoll 		 = ct_group.groupId
									LEFT JOIN ct_section ON ct_studentinfo.infoSection 	 = ct_section.sectionid
									WHERE infoClass = $stdClass";

			
								$query .= ($stdSection != '') ? " AND infoSection = $stdSection" : '';
								$query .= ($stdGroup != '') ? " AND infoGroup = $stdGroup" : '';
								
								$query .= " AND infoYear = $stdYear AND infoRoll = $stdRoll AND stdStatus = 1";

								$students = $wpdb->get_results($query);
							}


							?>
							<div class="b-blog-items-holder ">
								<div>
									<a class="btn btn-info" href="<?php home_url('student-search') ?>?search=search">Back To Search</a>
								</div><br>

								<?php 
								if(isset($students)){
									foreach ($students as $student) { ?>
									

										<div id="studentProfile" class="row">
										  <div class="col-md-4">
										    <?php if(!empty($student->stdImg)){ ?>
										    	<img src="<?= $student->stdImg ?>" class="img-responsive stdImg">
										    <?php }else{ ?>
										    	<img src="<?= get_template_directory_uri() ?>/img/No_Image.jpg" class="img-responsive stdImg">
										    <?php } ?>
										  </div>
										  <div class="col-md-8">

										    <h2 class="text-center stdName names"><?= $student->stdName ?></h2>

										    <div class="row">

										      <div class="col-md-4">
										        <label>Class</label>
										        <p><?= $student->className ?></p>

										        <label>Roll</label>
										        <p><?= $student->infoRoll ?></p>

										        <label>Birth Date</label>
										        <p><?= $student->stdBrith ?></p>

										        <label>Religion</label>
										        <p><?= $student->stdReligion ?></p>
										      </div>

										      <div class="col-md-4">
										        <label>Group</label>
										        <p><?= $student->groupName ?></p>

										        <label>Section Name</label>
										        <p><?= $student->sectionName ?></p>
										        
										      	<label>Nationality</label>
										        <p><?= $student->stdNationality ?></p>
										      </div>

										      <div class="col-md-4">
										        
										        <label>Permanent Address</label>
										        <p><?= $student->stdPermanent ?></p>

										        <label>Present Address</label>
										        <p><?= $student->stdPresent ?></p>
										      </div>

										    </div>
										  </div>

										  <div class="col-md-12">
										    <hr>
										    <div class="row">
										      <div class="col-md-4">
										        <h4>PARENTS</h4>
										      </div>

										      <div class="col-md-4">
										        <label>Father</label>
										        <p class="names"><?= $student->stdFather ?></p>
										        <label>Profession</label>
										        <p><?= $student->stdFatherProf ?></p>
										      </div>

										      <div class="col-md-4">
										        <label>Mother</label>
										        <p class="names"><?= $student->stdMother ?></p>

										        <label>Profession</label>
										        <p><?= $student->stdMotherProf ?></p>
										      </div>

										    </div>
										  </div>

										  <div class="col-md-12">
										    <hr>
										    <div class="row">
										      <div class="col-md-4">
										        <h4>Other Information</h4>
										      </div>

										      <div class="col-md-4">
										        <label>Previous School</label>
										        <p><?= $student->stdPrevSchool ?></p>

										        <label>TC Number</label>
										        <p><?= $student->stdTcNumber ?></p>
										      </div>

										      <div class="col-md-4">
										        <label>GPA</label>
										        <p><?= $student->stdGPA ?></p>
										      </div>

										    </div>
										  </div>
										</div>
											
										<?php
									} 
								}else{
									echo "<h3 class='alert alert-warning text-center'>Student not Found</h3>";
								}
								?>
							</div>
							<?php
						} ?>
						
					</div>

					<div class="col-xs-12 col-sm-5 col-md-4 col-lg-3">

						<?php get_sidebar(); ?>

					</div>
				</div>
			</div>
		</div>
	</div>

<?php get_footer(); ?>

<script type="text/javascript">
(function($) {
	$url = "<?= get_template_directory_uri() ?>/inc/ajaxAction.php";
	$('#classck').change(function() {
		$.ajax({
	    url: $url,
	    method: "POST",
	    data: { class : $(this).val(), type : 'getSection' },
	    dataType: "html"
	  }).done(function( msg ) {
	  	if(msg == 0){
	  		$( "#secSear" ).prop('required',false);
	  		$( ".sectionDiv" ).hide();
	  	}else{
	  		$( "#secSear" ).prop('required',true);
	  		$( ".sectionDiv" ).show();
		    $( "#secSear" ).html( msg );
	  	}
	  });

	  $.ajax({
      url: $url,
      method: "POST",
      data: { class : $(this).val(), type : 'getYears' },
      dataType: "html"
    }).done(function( msg ) {
      $( "#syear" ).html( msg );
    });
	});
})( jQuery );

</script>