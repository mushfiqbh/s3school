<?php
/** 01632552942
 * Template Name: Mobile Banking
 */

get_header(); ?>

	<div class="b-page-wrap">
		<div class="b-page-content with-layer-bg">
			<div class="b-layer-big otherPageBg">
				<div class="layer-big-bg page-layer-big-bg">
					<div class="layer-content-big text-center">
						<h2>Mobile Banking</h2>
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
								  <div class="panel-heading">Mobile Banking</div>
								  <div class="panel-body">

								  	<form action="" method="POST">
									  	<div class="row">
									  		<div class="col-md-6">
									  			<div class="form-group">
														<label>Name: *</label>
														<input type="text" name="" class="form-control">
													</div>
									  		</div>
									  		<div class="col-md-6">
									  			<div class="form-group">
														<label>Roll No:</label>
														<input type="text" name="" class="form-control">
													</div>
									  		</div>
									  		<div class="col-md-6">
									  			<div class="form-group">
														<label>Class:</label>
														<select name="" class="form-control">
															<option disabled selected>Select Class</option>
															<option value="Science">Science</option>
														</select>
													</div>
									  		</div>
									  		<div class="col-md-6">
									  			<div class="form-group">
														<label>Method: *</label>
														<select name="" class="form-control">
															<option disabled selected>Select Method </option>
															<option value="Science">Science</option>
														</select>
													</div>
									  		</div>
									  		<div class="col-md-6">
									  			<div class="form-group">
														<label>Mobile No: *</label>
														<input type="text" name="" class="form-control">
													</div>
									  		</div>
									  		<div class="col-md-6">
									  			<div class="form-group">
														<label>Transaction ID:</label>
														<input type="text" name="" class="form-control">
													</div>
									  		</div>
									  		<div class="col-md-6">
									  			<div class="form-group">
														<label>Date of Submission:</label>
														<input type="date" name="" class="form-control">
													</div>
									  		</div>
									  		<div class="col-md-6">
									  			<div class="form-group">
														<label>Amount:</label>
														<input type="text" name="" class="form-control">
													</div>
									  		</div>
									  		<div class="col-md-6">
									  			<div class="form-group">
														<label>Remarks:</label>
														<textarea name="" class="form-control"></textarea>
													</div>
									  		</div>
									  		<div class="col-md-12">
									  			<div class="form-group">
														<button type="submit" class="btn btn-secondary pull-right">Submit</button>
													</div>
									  		</div>

									  	</div>
										</form>

								  </div>
								</div>
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