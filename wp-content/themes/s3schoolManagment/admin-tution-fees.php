<?php
/** 01632552942
 * Template Name: Admin Tution Fees
 */

get_header(); ?>

	<div class="b-page-wrap">
		<div class="b-page-content with-layer-bg">
			<div class="b-layer-big otherPageBg">
				<div class="layer-big-bg page-layer-big-bg">
					<div class="layer-content-big text-center">
						<h2>Tution Fees</h2>
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
								  <div class="panel-heading">Tution Fees</div>
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
														<label>ID No:</label>
														<input type="text" name="" class="form-control">
													</div>
									  		</div>
									  		<div class="col-md-6">
									  			<div class="form-group">
														<label>Class:</label>
														<select id="tutionFeeClass" class="form-control" name="class" required="">
															<option value="">Select Class</option>
															<option value="40">Class-ix</option>
															<option value="41">Class-x</option>
															<option value="44">Class-vii</option>
															<option value="49">Class-viii</option>
															<option value="50">Class-vi</option>
															<option value="51">Class-xi</option>
														</select>
													</div>
									  		</div>
									  		<div class="col-md-6">
									  			<div class="form-group">
														<label>Method: *</label>
														<select name="payment_method" class="form-control pay_req" required="required">
															<option value="" selected="selected">Select One</option>
															<option value="2">bKash</option>
															<option value="3">Rocket</option>
															<option value="4">Sure Cash</option>
															<option value="5">Other</option>
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
					<div><h2>View Student Fees</h2></div>
					<div style="width:100%;height:459px;background:#4999c2">
					&nbsp;
					</div>
					</div>
				</div>
			</div>
		</div>
	</div>

<?php get_footer(); ?>