<?php
/*
Template Name: Student Inseart
*/

get_header();

/*=================
	Add Student
=================*/
if (isset($_POST['addStudent'])) {

	$insert = $wpdb->insert(
		'ct_student',
		array(
			'paymentPaid' 			=> $_POST['paymentPaid'],
			'paymentDue' 				=> $_POST['paymentDue'],
			'stdNote' 					=> $_POST['stdNote'],
			'stdName' 					=> $_POST['stdName'],
			'stdRoll' 					=> $_POST['stdRoll'],
			'stdImg' 						=> $_POST['stdImg'],
			'stdFather' 				=> $_POST['stdFather'],
			'stdFatherProf' 		=> $_POST['stdFatherProf'],
			'stdMother' 				=> $_POST['stdMother'],
			'stdMotherProf' 		=> $_POST['stdMotherProf'],
			'stdParentIncome' 	=> $_POST['stdParentIncome'],
			'stdlocalGuardian' 	=> $_POST['stdlocalGuardian'],
			'stdPhone' 					=> $_POST['stdPhone'],
			'stdPermanent' 			=> $_POST['stdPermanent'],
			'stdPresent' 				=> $_POST['stdPresent'],
			'stdBrith' 					=> $_POST['stdBrith'],
			'stdNationality' 		=> $_POST['stdNationality'],
			'stdReligion' 			=> isset($_POST['stdReligion']) ? $_POST['stdReligion'] : '',
			'stdAdmitClass' 		=> $_POST['stdAdmitClass'],
			'stdCurntYear' 			=> $_POST['stdCurntYear'],
			'stdSection' 				=> isset($_POST['stdSection']) ? $_POST['stdSection'] : 0,
			'stdOptionals' 			=> isset($_POST['stdOptionals']) ? json_encode($_POST['stdOptionals']) : 0,
			'stdTcNumber' 			=> $_POST['stdTcNumber'],
			'stdPrevSchool' 		=> $_POST['stdPrevSchool'],
			'stdGPA' 						=> $_POST['stdGPA'],
			'stdIntellectual' 	=> $_POST['stdIntellectual'],
			'stdScholarsClass' 	=> $_POST['stdScholarsClass'],
			'stdScholarsYear' 	=> $_POST['stdScholarsYear'],
			'stdScholarsMemo' 	=> $_POST['stdScholarsMemo']
		)
	);

	//echo $wpdb->last_query;

	if ($insert) {
		$message = array('status' => 'success', 'message' => 'Successfully Added' );
	}else{
		$message = array('status' => 'faild', 'message' => 'Something wrong please try again' );
	}
}


/*=================
	Update Student
=================*/
if (isset($_POST['updateSubject'])) {

	$update = $wpdb->update(
		'ct_subject',
		array(
			'stdName' 					=> $_POST['stdName'],
			'stdRoll' 					=> $_POST['stdRoll'],
			'stdImg' 						=> $_POST['stdImg'],
			'stdFather' 				=> $_POST['stdFather'],
			'stdFatherProf' 		=> $_POST['stdFatherProf'],
			'stdMother' 				=> $_POST['stdMother'],
			'stdMotherProf' 		=> $_POST['stdMotherProf'],
			'stdParentIncome' 	=> $_POST['stdParentIncome'],
			'stdlocalGuardian' 	=> $_POST['stdlocalGuardian'],
			'stdPhone' 					=> $_POST['stdPhone'],
			'stdPermanent' 			=> $_POST['stdPermanent'],
			'stdPresent' 				=> $_POST['stdPresent'],
			'stdBrith' 					=> $_POST['stdBrith'],
			'stdNationality' 		=> $_POST['stdNationality'],
			'stdReligion' 			=> $_POST['stdReligion'],
			'stdAdmitClass' 		=> $_POST['stdAdmitClass'],
			'stdCurntYear' 			=> $_POST['stdCurntYear'],
			'stdSection' 				=> isset($_POST['stdSection']) ? $_POST['stdSection'] : 0,
			'stdOptionals' 			=> isset($_POST['stdOptionals']) ? json_encode($_POST['stdOptionals']) : 0 ,
			'stdTcNumber' 			=> $_POST['stdTcNumber'],
			'stdPrevSchool' 		=> $_POST['stdPrevSchool'],
			'stdGPA' 						=> $_POST['stdGPA'],
			'stdIntellectual' 	=> $_POST['stdIntellectual'],
			'stdScholarsClass' 	=> $_POST['stdScholarsClass'],
			'stdScholarsYear' 	=> $_POST['stdScholarsYear'],
			'stdScholarsMemo' 	=> $_POST['stdScholarsMemo'],
			'paymentPaid' 			=> $_POST['paymentPaid'],
			'paymentDue' 				=> $_POST['paymentDue'],
			'stdNote' 					=> $_POST['stdNote'],
			'stdUpdatedAt' 			=> now()
		),
		array( 'studentid' => $_POST['id'])
	);

	if ($update) {
		$message = array('status' => 'success', 'message' => 'Successfully updated' );
	}else{
		$message = array('status' => 'faild', 'message' => 'Something wrong please try again' );
	}
}


/*=================
	Delete Subject
==================*/
if (isset($_POST['deleteStudent'])) {

	$delete = $wpdb->update(
		'ct_student',
		array(
			'stdStatus' => 0
		),
		array( 'studentid' => $_POST['id'])
	);

	if ($delete) {
		$message = array('status' => 'success', 'message' => 'Successfully Deleted' );
	}else{
		$message = array('status' => 'faild', 'message' => 'Something wrong please try again' );
	}
}

/*===============
	Edit Subject
================*/
$editid = 0;
if (isset($_POST['editStudent'])) {
	$editid = $_POST['id'];
	$edit = $wpdb->get_results( "SELECT * FROM ct_student WHERE studentid = $editid" );
	$edit = $edit[0];
}
?>

<p id="theSiteURL" class="hidden"><?= get_template_directory_uri() ?></p>

<div class="container maxAdminpages">
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

	<h2>
		Student
		<?php if(!isset($_GET['option'])){ ?>
			<a class="pull-right btn btn-success" href="<?= home_url() ?>/add-student?page=student&option=add">
				<span class="dashicons dashicons-plus"></span> Add Student
			</a>
		<?php }else{ ?>
			<a class="pull-right btn btn-success" href="<?= home_url() ?>/add-student?page=student">
				<span class="dashicons dashicons-groups"></span> Students
			</a>
		<?php } ?>
	</h2><br>



	<!-- 
		Add Window
	 -->
		<form accept="" method="POST" class="applyForm">
			<div class="panel panel-info">
			  <div class="panel-heading">Administration </div>
			  <div class="panel-body">
					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								<label>Payment Paid</label>
								<input min="0" step="5" class="form-control" type="text" name="paymentPaid" placeholder="Payment Paid">
							</div>

							<div class="form-group">
								<label>Payment Due</label>
								<input min="0" step="5" class="form-control" type="text" name="paymentDue" placeholder="Payment Due">
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label>Note</label>
								<textarea class="form-control" rows="4" name="stdNote" placeholder="Student Note"></textarea>
							</div>
						</div>
					</div>
			  </div>
			</div>

			<div class="panel panel-info">
			  <div class="panel-heading">Personal and educational information</div>
			  <div class="panel-body">
					<div class="row">
						<div class="col-md-6">

							<div class="form-group">
								<label>Student Name</label>
								<input class="form-control" type="text" name="stdName" placeholder="Student Name" required>
							</div>

							<div class="form-group">
								<label>Student Photo</label><br>
								<div class="mediaUploadHolder">
					    		<button type="button" class="mediaUploader">Upload</button>
					    		<span>
					    			<?php echo (isset($edit)) ? "<img height='40' src='".$edit->teacherImg."'>" : ''; ?>
					    		</span>

									<input class="hidden teacherImg" type="text" name="stdImg" value="<?= isset($edit) ? $edit->stdImg : ''; ?>">
				    		</div>
							</div>

							<div class="form-group">
								<label>Date Of Birth</label>
								<input class="form-control" type="date" name="stdBrith" placeholder="Date Of Birth">
							</div>

							<div class="form-group">
								<label>Father Name</label>
								<input class="form-control" type="text" name="stdFather" placeholder="Father Name">
							</div>

							<div class="form-group">
								<label>Father Profession</label>
								<input class="form-control" type="text" name="stdFatherProf" placeholder="Father Profession">
							</div>

							<div class="form-group">
								<label>Mother Name</label>
								<input class="form-control" type="text" name="stdMother" placeholder="Mother Name">
							</div>

							<div class="form-group">
								<label>Mother Profession</label>
								<input class="form-control" type="text" name="stdMotherProf" placeholder="Mother Profession">
							</div>

							<div class="form-group">
								<label>Parental Annual Income</label>
								<input class="form-control" type="text" name="stdParentIncome" placeholder="Parental annual income">
							</div>

							<div class="form-group">
								<label>Local Guardian Name</label>
								<input class="form-control" type="text" name="stdlocalGuardian" placeholder="Local Guardian Name">
							</div>

							<div class="form-group">
								<label>Phone Number</label>
								<input class="form-control" type="text" name="stdPhone" placeholder="Phone Number">
							</div>

							<div class="form-group">
								<label>Permanent Address</label>
								<input class="form-control" type="text" name="stdPermanent" placeholder="Permanent Address">
							</div>


						</div>

						<div class="col-md-6">

							<div class="form-group">
								<label>Present Address</label>
								<input class="form-control" type="text" name="stdPresent" placeholder="Present Address">
							</div>

							<div class="form-group">
								<label>Nationality</label>
								<input class="form-control" type="text" name="stdNationality" placeholder="Nationality">
							</div>

							<div class="form-group">
								<label>Religion</label>
								<select class="form-control" name="stdReligion">
									<option disabled selected>Select Religion</option>
									<option value="Muslim">Muslim</option>
									<option value="Hinduism">Hinduism</option>
									<option value="Buddist">Buddist</option>
									<option value="Christian">Christian</option>
									<option value="other">Other</option>
								</select>
							</div>
							<div class="row">
								<div class="col-md-6">
									<div class="form-group">
										<label>Class</label>
										<select id="admitClass" class="form-control" name="stdAdmitClass" required>
						    			<?php
							    			if (!isset($edit) || empty($edit->forClass)) {
							    				echo "<option disabled selected>Select a Class..</option>";
							    			}

						    				$classes = $wpdb->get_results( "SELECT classid,className,groupId,groupName FROM ct_class LEFT JOIN ct_group ON ct_class.classGroup = ct_group.groupId" );
												foreach ($classes as $class) {

							    				?>
							    				<option value='<?= $class->classid ?>' <?= ($edit->forClass == $class->classid) ? "selected" : ''; ?>>
							    					<?= $class->className ?>
							    					<?= !empty($class->groupName) ? " ($class->groupName)" : ''; ?>
							    				</option>
							    				<?php
												}
						    			?>
						    		</select>
									</div>
								</div>

								<div class="col-md-6">
									<label>Year</label>
									<select class="form-control" name="stdCurntYear" required>
										<option>Select A Year..</option>
										<option value="<?= date("Y"); ?>"><?= date("Y"); ?></option>
										<option value="<?= date("Y")+1; ?>"><?= date("Y")+1; ?></option>
									</select>
								</div>
							</div>

							<div class="form-group">
								<label>Roll</label>
								<input class="form-control" type="text" name="stdRoll" placeholder="Roll" required>
							</div>

							<!-- If Optional (Value will come by Ajax) -->
							<div class="form-group optionalSubDiv">
								
							</div>
							<div class="form-group sectionDiv">
								<label>Section </label>
								<select class="form-control sectionSelect" name="stdAdmitClass" required>
				    			
				    		</select>
							</div>

							<div class="form-group">
								<label>Previous School Name</label>
								<input class="form-control" type="text" name="stdPrevSchool" placeholder="Previous School Name">
							</div>

							<div class="form-group">
								<label>TC Number</label>
								<input class="form-control" type="text" name="stdTcNumber" placeholder="TC Number">
							</div>
							<hr>
							<h4><strong>Past Annual / Public Examination Details</strong></h4>
							<div class="row">
								<div class="col-md-6">
									<div class="form-group">
										<label>Number / GPA</label>
										<input class="form-control" type="text" name="stdGPA">
									</div>
								</div>
								<div class="col-md-6">
									<div class="form-group">
										<label>Intellectual Position</label>
										<input class="form-control" type="text" name="stdIntellectual">
									</div>
								</div>
							</div>
							<hr>
							<h4><strong>If you get government scholarship</strong></h4>
							<div class="row">
								<div class="col-md-4">
									<div class="form-group">
										<label>In which class</label>
										<input class="form-control" type="text" name="stdScholarsClass">
									</div>
								</div>
								<div class="col-md-4">
									<div class="form-group">
										<label>Year</label>
										<input class="form-control" type="text" name="stdScholarsYear">
									</div>
								</div>
								<div class="col-md-4">
									<div class="form-group">
										<label><small>Memorandum No</small></label>
										<input class="form-control" type="text" name="stdScholarsMemo">
									</div>
								</div>

							</div>

						</div>
					</div>

					<div class="form-group">
						<input class="btn btn-secondary pull-right" type="submit" name="addStudent" value="Add">
					</div>

			  </div>
			</div>

		</form>
	

</div>


<!-- Delete Modal-->
<div id="deleteModal" class="modal fade" role="dialog">
  <div class="modal-dialog modal-sm">

    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">Delete Data</h4>
      </div>
      <div class="modal-body">
        <p class="text-danger">You can't recover the data after delete.</p>
      </div>
      <div class="modal-footer">
	      <form action="" method="POST">
	      	<input type="hidden" name="id" class="id">
        	<button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
        	<button type="submit" class="btn btn-danger" name="deleteTeacher">Delete</button>
	      </form>
      </div>
    </div>

  </div>
</div>


<?php get_footer(); ?>