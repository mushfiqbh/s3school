<?php
/**
 * Template Name: Teachers & Staffs
 */

get_header();

?>

	<div class="b-page-wrap">
		<div class="b-page-content with-layer-bg">
			<div class="b-layer-big otherPageBg">
				<div class="layer-big-bg page-layer-big-bg">
					<div class="layer-content-big text-center">
						<h2><?php the_title(); ?></h2>
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
						<div class="b-blog-items-holder">
							<div class="clearfix">

								<?php

									if (!isset($_GET['t'])) {
										$teachers = $wpdb->get_results( "SELECT `teacherid`, `teacherName`, `teacherImg`,`teacherDesignation` FROM ct_teacher" );				
						
										foreach ($teachers as $teacher) {
											?>
											<a href="?t=<?= $teacher->teacherid ?>">
												<div class="col-xs-12 col-sm-12 col-md-4 wow slideInUp">
													<div class="b-features-column teacherCustomHeight" style="padding: 15px">
														<div class="features-column-icon">
															<?php if(!empty($teacher->teacherImg)){ ?>
																<img style="width: auto !important;" height="120" class="img-responsive center-block" src="<?= $teacher->teacherImg ?>">
															<?php }else{ ?>
																<img style="width: auto !important;" height="120" class="img-responsive center-block" src="<?= home_url() ?>/wp-content/themes/s3schoolManagment/img/No_Image.jpg">
															<?php } ?>
														</div>
														<h6 class="features-column-title" style="margin-bottom: 0 !important;">
															<?= $teacher->teacherName ?>
														</h6>
														<div class="features-column-text" style="line-height: 1;"><?= $teacher->teacherDesignation ?></div>
													</div>
												</div>
											</a>
											<?php
										}
									}else{
										$teacherId = $_GET['t'];
									  $teachers = $wpdb->get_results("SELECT * FROM ct_teacher WHERE teacherid = $teacherId" );
									  foreach ($teachers as $teacher) {
									    ?>
									    <div class="panel-heading"><h3><?= $teacher->teacherName ?> <a class="pull-right btn btn-success" href="<?= home_url( $wp->request ) ?>">All Teachers</a></h3></div>
								  		<div class="panel-body">
										    <div id="studentProfile" class="row">
										      <div class="col-md-4">
										        <?php if(!empty($teacher->teacherImg)){ ?>
										        <img src="<?= $teacher->teacherImg ?>" class="img-responsive stdImg">
										        <?php }else{ ?>
										        <img src="<?= get_template_directory_uri() ?>/img/No_Image.jpg" class="img-responsive stdImg">
										        <?php } ?>
										      </div>
										      <div class="col-md-8">

										        <div class="row">
										        	<div class="col-md-6">
										        		<label>Designation</label>
										            <p><?= $teacher->teacherDesignation ?></p>

										            <label>Phone</label>
												        <p><?= $teacher->teacherPhone ?></p>

												        <label>Birth Date</label>
												        <p><?= date("d-m-Y",strtotime($teacher->teacherBirth )) ?></p>

												        <label>Joining Date</label>
												        <p><?= date("d-m-Y",strtotime($teacher->teacherJoining)) ?></p>
										        	</div>

										        	<div class="col-md-6">
										        		<label>Father</label>
										            <p><?= $teacher->teacherFather ?></p>

										            <label>Mother</label>
												        <p><?= $teacher->teacherMother ?></p>

												        <label>NID</label>
												        <p><?= $teacher->teacherNid ?></p>

												        <label>Index No</label>
												        <p><?= $teacher->teacherMpo ?></p>
										        	</div>
										        </div>
										        
										      </div>
										      <div class="col-md-12">
										        <hr>

								        		<table style="width: 100%">
								        			<tr>
								        				<td style="width: 50%; text-align: center;">
								        					<b>Address</b>
								        				</td>
								        				<td>
											            <label>Present Address</label>
											            <p><?= $teacher->teacherPresent ?></p>

											            <label>Permanent Address</label>
											            <p><?= $teacher->teacherPermanent ?></p>
								        				</td>
								        			</tr>
								        		</table>
								        		<br>
									        
										        <div class="row">


										        	<div class="col-md-12">
										        		<?php if(!empty($teacher->teacherQualificarion)){ ?>
											        		<label>Educational Qualification</label>
											            <table class="table-striped table table-bordered text-center">
											            	<tr class="table-primary">
											        				<th>SL</th>
											        				<th>Exam Name</th>
											        				<th>Div/Class</th>
											        				<th>Passing Year</th>
											        				<th>Board/University</th>
											        			</tr>
											        			<?php
											        				$qualifications = json_decode($teacher->teacherQualificarion);
											        				foreach ($qualifications as $key => $qualification) {
											        					?>
											        					<tr>
											        						<td><?= $key+1 ?></td>
											        						<?php 
											        							foreach ($qualification AS $key2 => $value) {
											        								if ($key2 == 1) {
											        									echo "<td>--</td>";
											        								}else{
											        									echo "<td>$value</td>";
											        								}
											        							}
											        						?>
											        					</tr>
											        					<?php
											        				}
											        			?>
											            </table>
											          <?php } ?>

										            <?php if(!empty($teacher->teacherTraining)){ ?>
											            <label>Training Information</label>
											            <table class="table-striped table table-bordered text-center">
											            	<tr class="table-primary">
											        				<th>SL</th>
											        				<th>Name:</th>
											        				<th>Date:</th>
											        				<th>Duration</th>
											        				<th>Organization</th>
											        				<th>Venue</th>
											        			</tr>
											        			<?php
											        				$trainings = json_decode($teacher->teacherTraining);
											        				foreach ($trainings as $key => $training) {
											        					?>
											        					<tr>
											        						<td><?= $key+1 ?></td>
											        						<?php 
											        							foreach ($training as $key => $value) {
											        								if($key == 2){
											        									echo "<td>$value Days</td>";
											        								}else{
											        									echo "<td>$value</td>";
											        								}
											        							}
											        						?>
											        					</tr>
											        					<?php
											        				}
											        			?>
											            </table>
											          <?php } ?>

										            <?php if(!empty($teacher->teacherNote)){ ?>
											            <label>Note</label>
											            <p><?= $teacher->teacherNote ?></p>
											          <?php } ?>

										        	</div>

										          

										        </div>
										      </div>
										  
										    </div>
										  </div>
										  <?php
										}
					
									}
								?>						

							</div>
						</div>
					</div>
					<div class="col-xs-12 col-sm-5 col-md-4 col-lg-3">
						<?php get_sidebar(); ?>
					</div>
				</div>
			</div>
		</div>
	</div>

<?php get_footer(); ?>