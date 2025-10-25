<?php
/**
 * Template Name: Academic Result
 */

get_header(); ?>

	<div class="b-page-wrap">
		<div class="b-page-content with-layer-bg">
			<div class="b-layer-big otherPageBg">
				<div class="layer-big-bg page-layer-big-bg">
					<div class="layer-content-big text-center">
						<h2>Academic Result</h2>
						Search Academic Result Download & print.
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
						<div class="b-blog-items-holder wow slideInLeft">
							<div class="clearfix aboutUsPageContent">

								<div class="panel panel-default">
								  <div class="panel-heading">Academic Result</div>
								  <div class="panel-body">
								  	<form action="" method="POST">
									  	<div class="row">
									  		<div class="col-md-6">
									  			<div class="form-group">
														<label>Exam: *</label>
														<select name="" class="form-control">
															<option disabled selected>Select Exam</option>
															<option value="Science">Half yearly</option>
														</select>
													</div>
									  		</div>
									  		<div class="col-md-6">
									  			<div class="form-group">
														<label>Class: *</label>
														<select name="" class="form-control">
														<option disabled selected>Select Class</option>
															<option disabled selected>Six</option>
															<option value="Science">Seven</option>
															<option disabled selected>Eight</option>
															<option value="Science">Nine</option>
															<option disabled selected>Ten</option>
														</select>
													</div>
									  		</div>
									  		<div class="col-md-6">
									  			<div class="form-group">
														<label>Section: *</label>
														<select name="" class="form-control">
														<option disabled selected>Select Section</option>
															<option value="Science">Section A</option>
															<option value="Science">Section B</option>
															<option value="Science">Section C</option>
														</select>
													</div>
									  		</div>
									  		<div class="col-md-6">
									  			<div class="form-group">
														<label>Group: *</label>
														<select name="" class="form-control">
															<option value="Science">Science</option>
															<option value="Science">Humanities</option>
															<option value="Science">Business</option>
														</select>
													</div>
									  		</div>
									  		<div class="col-md-6">
									  			<div class="form-group">
														<label>Academic Year: *</label>
														<select name="" class="form-control">
															<option disabled selected>Select Year </option>
															<option value="Science">2014</option>
															<option value="Science">2015</option>
															<option value="Science">2016</option>
															<option value="Science">2017</option>
														</select>
													</div>
									  		</div>
									  		<div class="col-md-12">
													<button type="submit" class="btn btn-secondary pull-right">Search</button>
									  		</div>

									  	</div>
										</form>
								  </div>
								</div>
							</div>
						</div>
					</div>
					<div class="col-xs-12 col-sm-5 col-md-4 col-lg-3">
						<?php include_once 'inc/sidebar.php'; ?>
					</div>
				</div>
			</div>
		</div>
	</div>

<?php get_footer(); ?>