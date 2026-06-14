<?php
/**
 * Template Name: Apply Online
 */

get_header(); 
/*=================
	Add Student
=================*/
if (isset($_POST['applySubmit'])) {

	$insert = $wpdb->insert(
		'ct_online_application',
		array(
			// 'paymentPaid' 			=> $_POST['paymentPaid'],
			// 'paymentDue' 				=> $_POST['paymentDue'],
			// 'stdNote' 					=> $_POST['stdNote'],
			'stdName' 					=> $_POST['stdName'],
			// 'stdRoll' 					=> $_POST['stdRoll'],
			// 'stdImg' 						=> $_POST['stdImg'],
			'stdFather' 				=> $_POST['stdFather'],
			// 'stdFatherProf' 		=> $_POST['stdFatherProf'],
			'stdMother' 				=> $_POST['stdMother']
			// 'stdMotherProf' 		=> $_POST['stdMotherProf'],
			// 'stdParentIncome' 	=> $_POST['stdParentIncome'],
			// 'stdlocalGuardian' 	=> $_POST['stdlocalGuardian'],
			// 'stdPhone' 					=> $_POST['stdPhone'],
			// 'stdPermanent' 			=> $_POST['stdPermanent'],
			// 'stdPresent' 				=> $_POST['stdPresent'],
			// 'stdBrith' 					=> $_POST['stdBrith'],
			// 'stdNationality' 		=> $_POST['stdNationality'],
			// 'stdReligion' 			=> isset($_POST['stdReligion']) ? $_POST['stdReligion'] : '',
			// 'stdAdmitClass' 		=> $_POST['stdAdmitClass'],
			// 'stdCurntYear' 			=> $_POST['stdCurntYear'],
			// 'stdSection' 				=> isset($_POST['stdSection']) ? $_POST['stdSection'] : 0,
			// 'stdOptionals' 			=> isset($_POST['stdOptionals']) ? json_encode($_POST['stdOptionals']) : 0,
			// 'stdTcNumber' 			=> $_POST['stdTcNumber'],
			// 'stdPrevSchool' 		=> $_POST['stdPrevSchool'],
			// 'stdGPA' 						=> $_POST['stdGPA'],
			// 'stdIntellectual' 	=> $_POST['stdIntellectual'],
			// 'stdScholarsClass' 	=> $_POST['stdScholarsClass'],
			// 'stdScholarsYear' 	=> $_POST['stdScholarsYear'],
			// 'stdScholarsMemo' 	=> $_POST['stdScholarsMemo']
		)
	);

	echo $wpdb->last_query;

	if ($insert) {
		$message = array('status' => 'success', 'message' => 'Successfully Added' );
	}else{
		$message = array('status' => 'faild', 'message' => 'Something wrong please try again' );
	}
}
?>

	<div class="b-page-wrap">
		<div class="b-page-content with-layer-bg">
			<div class="b-layer-big otherPageBg">
				<div class="layer-big-bg page-layer-big-bg">
					<div class="layer-content-big text-center">
						<h2><?php echo the_title() ?></h2>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="section">
		<div class="container">
		<?php
		if (isset($message)) {
			?>
				<div class="messageDiv">
					<div class="alert <?= ($message['status'] == 'success') ? 'alert-success' : 'alert-danger';  ?>">
						<?= $message['message'] ?>
					</div>
				</div>
			<?php
		}
	?>
			<form accept="" method="POST" class="applyForm">
				<div class="panel panel-default">
				  <div class="panel-heading">Personal and educational information</div>
				  <div class="panel-body">
						<div class="row">
							<div class="col-md-6">
								<div class="form-group">
									<label>Section</label>
									<select class="form-control">
										<option disabled selected>Select Section</option>
										<option>School</option>
										<option>College</option>
									</select>
								</div>

								<div class="form-group">
									<label>Student Name</label>
									<input class="form-control" type="text" name="stdName" placeholder="Student Name">
								</div>

								<div class="form-group">
									<label>Student Photo</label><br>
									<label class="fileUpload">
										<input class="fileInput" type="file" name="">
									</label>
								</div>

								<div class="form-group">
									<label>Father Name</label>
									<input class="form-control" type="text" name="stdFather" placeholder="Father Name">
								</div>

								<div class="form-group">
									<label>Father Profession</label>
									<input class="form-control" type="text" name="" placeholder="Father Profession">
								</div>

								<div class="form-group">
									<label>Mother Name</label>
									<input class="form-control" type="text" name="stdMother" placeholder="Mother Name">
								</div>

								<div class="form-group">
									<label>Mother Profession</label>
									<input class="form-control" type="text" name="" placeholder="Mother Profession">
								</div>

								<div class="form-group">
									<label>Parental Annual Income</label>
									<input class="form-control" type="text" name="" placeholder="Parental annual income">
								</div>

								<div class="form-group">
									<label>Local Guardian Name</label>
									<input class="form-control" type="text" name="" placeholder="Local Guardian Name">
								</div>
							</div>

							<div class="col-md-6">
								<div class="form-group">
									<label>Phone Number</label>
									<input class="form-control" type="text" name="" placeholder="Phone Number">
								</div>

								<div class="form-group">
									<label>Permanent Address</label>
									<input class="form-control" type="text" name="" placeholder="Permanent Address">
								</div>

								<div class="form-group">
									<label>Present Address</label>
									<input class="form-control" type="text" name="" placeholder="Present Address">
								</div>

								<div class="form-group">
									<label>Date Of Birth</label>
									<input class="form-control" type="text" name="" placeholder="Date Of Birth">
								</div>

								<div class="form-group">
									<label>Nationality</label>
									<input class="form-control" type="text" name="" placeholder="Nationality">
								</div>

								<div class="form-group">
									<label>Religion</label>
									<select class="form-control">
										<option disabled selected>Select Religion</option>
										<option value="Muslim">Muslim</option>
										<option value="Hinduism">Hinduism</option>
										<option value="Buddist">Buddist</option>
										<option value="Christian">Christian</option>
										<option value="other">Other</option>
									</select>
								</div>

								<div class="form-group">
									<label>The Class want to admit </label>
									<select class="form-control">
										<option disabled selected>Select Class</option>
										<option value="6">Six</option>
										<option value="7">Seven</option>
										<option value="8">Eight</option>
										<option value="9">Nine</option>
									</select>
								</div>

								<div class="form-group">
									<label>TC Number</label>
									<input class="form-control" type="text" name="" placeholder="TC Number">
								</div>

								<div class="form-group">
									<label>Previous School Name</label>
									<input class="form-control" type="text" name="" placeholder="Previous School Name">
								</div>

							</div>
						</div>

				  </div>
				</div>


				<div class="row">
					<div class="col-md-6">
						<div class="panel panel-default">
						  <div class="panel-heading">College Branch (if any)</div>
						  <div class="panel-body">
						  	<div class="form-group">
									<label>Group</label>
									<select name="" class="form-control">
										<option disabled selected>Select Group</option>
										<option value="Science">Science</option>
										<option value="Business Studies">Business Studies</option>
										<option value="Humanities">Humanities</option>
										<option value="other">Other</option>
									</select>
								</div>
								<hr>
								<h3>SSC / Dakhil Examination results</h3>
								<div class="row">
									<div class="col-md-6">
										<div class="form-group">
											<label>GPA (With 4th Sub)</label>
											<input class="form-control" type="text" name="">
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label>GPA (Without 4th Sub)</label>
											<input class="form-control" type="text" name="">
										</div>
									</div>
								</div>
								<hr>
								<h3>Subjects (class XI)</h3>
								<div class="row">
									<div class="col-md-6">
										<div class="form-group">
											<label>A) Bangla</label>
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label>B) English</label>
										</div>
									</div>
									<div class="col-md-12">
										<div class="form-group">
											<label>C) Information & communication</label>
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label>D)</label>
											<input class="form-control" type="text" name="">
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label>E)</label>
											<input class="form-control" type="text" name="">
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label>F)</label>
											<input class="form-control" type="text" name="">
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label>G) Fourth Subject</label>
											<input class="form-control" type="text" name="">
										</div>
									</div>
								</div>

						  </div>
						</div>
					</div>


					<div class="col-md-6">
						<div class="panel panel-default">
						  <div class="panel-heading">School Branch</div>
						  <div class="panel-body">
								<h3>Past Annual / Public Examination Details</h3>
								<div class="row">
									<div class="col-md-6">
										<div class="form-group">
											<label>Number / GPA</label>
											<input class="form-control" type="text" name="">
										</div>
									</div>
									<div class="col-md-6">
										<div class="form-group">
											<label>Intellectual Position</label>
											<input class="form-control" type="text" name="">
										</div>
									</div>

								</div>
								<hr>
								<h3>If you get government scholarship</h3>
								<div class="row">
									<div class="col-md-4">
										<div class="form-group">
											<label>In which class</label>
											<input class="form-control" type="text" name="">
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-group">
											<label>Year</label>
											<input class="form-control" type="text" name="">
										</div>
									</div>
									<div class="col-md-4">
										<div class="form-group">
											<label><small>Memorandum No</small></label>
											<input class="form-control" type="text" name="">
										</div>
									</div>

								</div>
						  </div>
						</div>

						<br><br>

						<div class="form-group">
							<input class="btn btn-secondary pull-right" type="submit" name="applySubmit" value="Apply">
						</div>
					</div>

				</div>

			</form>
		</div>
	</div>

<?php get_footer(); ?>