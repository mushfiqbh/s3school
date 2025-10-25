<?php
/**
 * Template Name: Admin Staff
 */

global $wpdb;


/*=================
	Add staff
=================*/
$userId = 0;
$userName = null;

function checkUniqueUsername($username, $userId=null){
	global $wpdb;
	if($userId){
		$check = $wpdb->get_results( "SELECT * FROM sm_users WHERE ID != $userId AND user_login = '$username'" );

	}else{
		$check = $wpdb->get_results( "SELECT * FROM sm_users WHERE user_login = '$username'" );
	}
	return $check;
}
if (isset($_POST['addStaff'])) {

	$qualificArray = array();

 	for ($i=1; $i < 6; $i++) { 
		if(!empty($_POST["quExam$i"])){
			$tmpArray = array(
				$_POST["quExam$i"],
				$_POST["quDiv$i"],
				$_POST["quYear$i"],
				$_POST["quBord$i"]
			);
			$qualificArray[] = $tmpArray;
		}
	}
	$staffQualificarion = json_encode($qualificArray);

	$trainingArray = array();
	foreach ($_POST["train"] as $key => $value) {
		if($_POST["train"][$key] != ''){
			$trainingArray[] = array(
				$_POST["train"][$key],
				$_POST["trDate"][$key],
				$_POST["duration"][$key],
				$_POST["orga"][$key],
				$_POST["venue"][$key]
			);
		}
	}	
	$staffTraining = json_encode($trainingArray);
	

	$insert = $wpdb->insert(
		'ct_staff',
		array(
			'staffUserId' 						=> $userId,
			'staffName' 					=> htmlentities($_POST['staffName'], ENT_QUOTES),
			'staffSQuali' 				=> htmlentities($_POST['staffSQuali'], ENT_QUOTES),
			'staffImg' 						=> htmlentities($_POST['staffImg'], ENT_QUOTES),
			'staffNote' 					=> htmlentities($_POST['staffNote'], ENT_QUOTES),
			'staffFather' 				=> htmlentities($_POST['staffFather'], ENT_QUOTES),
			'staffMother' 				=> htmlentities($_POST['staffMother'], ENT_QUOTES),
			'staffDesignation' 		=> htmlentities($_POST['staffDesignation'], ENT_QUOTES),
			'staffBirth' 					=> htmlentities($_POST['staffBirth'], ENT_QUOTES),
			'staffBlood' 					=> htmlentities($_POST['staffBlood'], ENT_QUOTES),
			'staffJoining' 				=> htmlentities($_POST['staffJoining'], ENT_QUOTES),
			'staffPhone' 					=> htmlentities($_POST['staffPhone'], ENT_QUOTES),
			'staffNid' 						=> htmlentities($_POST['staffNid'], ENT_QUOTES),
			'staffPresent' 				=> htmlentities($_POST['staffPresent'], ENT_QUOTES),
			'staffPermanent' 			=> htmlentities($_POST['staffPermanent'], ENT_QUOTES),
			'staffMpo' 						=> htmlentities($_POST['staffMpo'], ENT_QUOTES),
			'staffQualificarion' 	=> $staffQualificarion,
			'staff_serial' 	=>  htmlentities($_POST['staff_serial'], ENT_QUOTES),
			'status' 		=> htmlentities(isset($_POST['status']) ? $_POST['status'] : 'Present', ENT_QUOTES),
			'staffTraining' 			=> $staffTraining
		)
	);
	
	$message = ms3message($insert, 'Added');
}

/*edit Section*/
$editid = 0;
if (isset($_POST['editStaff'])) {
	$editid = $_POST['id'];
	$edit = $wpdb->get_results( "SELECT * FROM ct_staff WHERE staffid = $editid" );
	$edit = $edit[0];

	$userNameInfo = $wpdb->get_results( "SELECT user_login FROM sm_users WHERE ID = $edit->staffUserId" );

	if($userNameInfo){
		$userName = $userNameInfo[0]->user_login;
		$_SESSION['staffUserId'] = $edit->staffUserId;
	}else{
		$userName = '';
		$_SESSION['staffUserId'] = null;
	}

}

/*Update Section*/
if (isset($_POST['updateStaff'])) {
	$userId = $_SESSION['staffUserId'];
	$qualificArray = array();

 	for ($i=1; $i < 6; $i++) { 
		if(!empty($_POST["quExam$i"])){
			$tmpArray = array(
				$_POST["quExam$i"],
				$_POST["quDiv$i"],
				$_POST["quYear$i"],
				$_POST["quBord$i"]
			);
			$qualificArray[] = $tmpArray;
		}
	}
	$staffQualificarion = json_encode($qualificArray);

	$trainingArray = array();
	if (isset($_POST["train"])) {
		foreach ($_POST["train"] as $key => $value) {
			if($_POST["train"][$key] != ''){
				$trainingArray[] = array(
					$_POST["train"][$key],
					$_POST["trDate"][$key],
					$_POST["duration"][$key],
					$_POST["orga"][$key],
					$_POST["venue"][$key]
				);
			}
		}	
	}
	$staffTraining = json_encode($trainingArray);



	$update = $wpdb->update(
		'ct_staff',
		array(
			'staffUserId' 						=> $userId,
			'staffName' 					=> htmlentities($_POST['staffName'], ENT_QUOTES),
			'staffSQuali' 				=> htmlentities($_POST['staffSQuali'], ENT_QUOTES),
			'staffImg' 						=> htmlentities($_POST['staffImg'], ENT_QUOTES),
			'staffNote' 					=> htmlentities($_POST['staffNote'], ENT_QUOTES),
			'staffFather' 				=> htmlentities($_POST['staffFather'], ENT_QUOTES),
			'staffMother' 				=> htmlentities($_POST['staffMother'], ENT_QUOTES),
			'staffDesignation' 		=> htmlentities($_POST['staffDesignation'], ENT_QUOTES),
			'staffBirth' 					=> htmlentities($_POST['staffBirth'], ENT_QUOTES),
			'staffBlood' 					=> htmlentities($_POST['staffBlood'], ENT_QUOTES),
			'staffJoining' 				=> htmlentities($_POST['staffJoining'], ENT_QUOTES),
			'staffPhone' 					=> htmlentities($_POST['staffPhone'], ENT_QUOTES),
			'staffNid' 						=> htmlentities($_POST['staffNid'], ENT_QUOTES),
			'staffPresent' 				=> htmlentities($_POST['staffPresent'], ENT_QUOTES),
			'staffPermanent' 			=> htmlentities($_POST['staffPermanent'], ENT_QUOTES),
			'staffMpo' 						=> htmlentities($_POST['staffMpo'], ENT_QUOTES),
			'staffQualificarion' 	=> $staffQualificarion,
			'staff_serial' 	=>  htmlentities($_POST['staff_serial'], ENT_QUOTES),
			'status' 		=> htmlentities(isset($_POST['status']) ? $_POST['status'] : 'Present', ENT_QUOTES),
			'staffTraining' 		=> $staffTraining
		),
		array( 'staffid' => $_POST['id'])
	);

	

	$message = ms3message($update, 'Updated');
	$_SESSION['staffUserId'] = null;
}


/*Delete Section*/
if (isset($_POST['deleteStaff'])) {
	$delete = $wpdb->delete( 'ct_staff', array( 'staffid' => $_POST['id'] ) );
	$message = ms3message($delete, 'Deleted');
}





function output($content){
	return str_replace("\n","<br />", html_entity_decode($content));
}

$allTecAcc = get_users(array('role' => 'um_staffs' ));
$haveTechquer = $wpdb->get_results( "SELECT staffUserId FROM ct_staff GROUP BY staffUserId" );
$haveTech = array();
foreach ($haveTechquer as $vtech) {
	$haveTech[] = $vtech->staffUserId;
}

?>
<p id="theSiteURL" style="display: none;"><?= get_template_directory_uri() ?></p>
<?php if ( ! is_admin() ) { get_header(); ?>
<div class="b-layer-main">

	<div class="">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
<?php } ?>


<div class="container-fluid maxAdminpages" style="padding-left: 0">
	
	<!-- Show Status message -->
	<?php if(isset($message)){ ms3showMessage($message); } ?>

	<h2>Staff Management <a href="?page=staff" class="pull-right btn btn-success">Add Staff </a> </h2><br>
	<div class="row">
		<?php if(!isset($_GET['id']) || isset($_POST['editStaff'])){ ?>
			<div class="col-md-7">
				<div class="panel panel-info">
				  <div class="panel-heading"><h3><?= isset($edit) ? 'Edit' : 'Add'; ?> Staff </h3></div>
				  <div class="panel-body">
				    <form action="" method="POST">

			    		<input type="hidden" name="id" value="<?= $editid ?>">
				    	<div class="row">

				    		<div class="form-group col-md-6">
					    		<label>Staff Name</label>
					    		<input class="form-control" type="text" name="staffName" value="<?= isset($edit) ? $edit->staffName : ''; ?>" required>
					    	</div>

					    	<div class="form-group col-md-3">
					    		<label>Staff Image</label>
					    		<div class="mediaUploadHolder">
						    		<button type="button" class="mediaUploader btn btn-success">Upload</button>
						    		<span>
						    			<?php echo (isset($edit)) ? "<img height='40' src='".$edit->staffImg."'>" : ''; ?>
						    		</span>

										<input class="hidden teacherImg" type="text" name="staffImg" value="<?= isset($edit) ? $edit->staffImg : ''; ?>">
					    		</div>
					    	</div>

					    	<!-- <div class="form-group col-md-3">
					    		<label>Account</label>
					    		<select class="form-control" name="staffUserId">
					    			<option>Select</option>
					    			<?php
					    				foreach ($allTecAcc as $key => $value) {
					    					if(in_array($value->data->ID, $haveTech))
					    						continue;
					    					$sel = "";
					    					if(isset($edit)){
					    						$sel = ($value->data->ID == $edit->staffUserId) ? 'selected' : '';
					    					} 
						    				?>
							    			<option value='<?= $value->data->ID ?>' <?= $sel ?>><?= $value->data->display_name ?></option>
						    			<?php } ?>
					    		</select>
					    	</div> -->

					    </div>
					    <div class="row">

					    	<div class="form-group col-md-12">
					    		<label>Qualification</label>
					    		<input class="form-control" type="text" name="staffSQuali" value="<?= isset($edit) ? $edit->staffSQuali : ''; ?>" required>
					    	</div>

					    	<div class="form-group col-md-6">
					    		<label>Father's Name</label>
					    		<input class="form-control" type="text" name="staffFather" value="<?= isset($edit) ? $edit->staffFather : ''; ?>" required>
					    	</div>

					    	<div class="form-group col-md-6">
					    		<label>Mother's Name</label>
					    		<input class="form-control" type="text" name="staffMother" value="<?= isset($edit) ? $edit->staffMother : ''; ?>" required>
					    	</div>

					    	<div class="form-group col-md-6">
					    		<label>Designation</label>
					    		<input class="form-control" type="text" name="staffDesignation" value="<?= isset($edit) ? $edit->staffDesignation : ''; ?>" required>
					    	</div>

					    	<div class="form-group col-md-6">
					    		<label>Phone</label>
					    		<input class="form-control" type="text" name="staffPhone" value="<?= isset($edit) ? $edit->staffPhone : ''; ?>" >
					    	</div>

					    	<div class="form-group col-md-6">
					    		<label>Status</label>
					    		<?php $current_status = isset($edit) && !empty($edit->status) ? $edit->status : 'Present'; ?>
					    		<select class="form-control" name="status" required>
					    			<option value="Present" <?= ($current_status === 'Present') ? 'selected' : ''; ?>>Present</option>
					    			<option value="Former" <?= ($current_status === 'Former') ? 'selected' : ''; ?>>Former</option>
					    		</select>
					    	</div>

				    		<div class="form-group col-md-4">
					    		<label>Birth Date</label>
					    		<input class="form-control" type="date" name="staffBirth" value="<?= isset($edit) ? $edit->staffBirth : ''; ?>" required>
					    	</div>

					    	<div class="form-group col-md-4">
					    		<label>Joining Date</label>
					    		<input class="form-control" type="date" name="staffJoining" value="<?= isset($edit) ? $edit->staffJoining : ''; ?>" required>

					    	</div>

					    	<div class="form-group col-md-4">
					    		<label>Blood Group</label>
					    		<input class="form-control" type="text" name="staffBlood" value="<?= isset($edit) ? $edit->staffBlood : ''; ?>">
					    	</div>


					    	<div class="form-group col-md-6">
					    		<label>NID no</label>
					    		<input class="form-control" type="text" name="staffNid" value="<?= isset($edit) ? $edit->staffNid : ''; ?>" >
					    	</div>

					    	<div class="form-group col-md-6">
					    		<label>MPO Index no</label>
					    		<input class="form-control" type="text" name="staffMpo" value="<?= isset($edit) ? $edit->staffMpo : ''; ?>" >

					    	</div>
				    	</div>

				    	<div class="form-group">
				    		<label>Present Address</label>
				    		<input class="form-control" type="text" name="staffPresent" value="<?= isset($edit) ? $edit->staffPresent : ''; ?>" >
				    	</div>

				    	<div class="form-group">
				    		<label>Permanent Address</label>
				    		<input class="form-control" type="text" name="staffPermanent" value="<?= isset($edit) ? $edit->staffPermanent : ''; ?>" >
				    	</div>


				    	<div class="form-group">
				    		<label>Qualification </label>
				    		<table class="table table-striped table-bordered">
		        			<tr class="table-primary">
		        				<th>SL</th>
		        				<th>Exam:</th>
		        				<th>Div/Class:</th>
		        				<th>Year</th>
		        				<th>Board/University</th>
		        			</tr>

		        			<?php
		        			if (isset($edit)) {
		        				$qualifications = json_decode($edit->staffQualificarion);
		        			}
		        			for ($qu=1; $qu < 6; $qu++) {
		        				$qualif = isset($qualifications[$qu-1]) ? $qualifications[$qu-1] : "";
		        				?>
		        					<tr>
				        				<td><?= $qu ?></td>
				        				<td><input class="form-control" name="quExam<?= $qu ?>" type="text" placeholder="Exam" value=<?= isset($qualif[0]) ? $qualif[0] : ''; ?>></td>
				        				<td><input class="form-control" name="quDiv<?= $qu ?>" type="text" placeholder="Div/Class" value="<?= isset($qualif[1]) ? $qualif[1] : ''; ?>"></td>
				        				<td>
				        					<select class="form-control" name="quYear<?= $qu ?>" style='padding: 0;width: 55px;'>
				        						<?php for ($i=date('Y'); $i > (date('Y')-50); $i--) {
				        							if (isset($qualif[2]) && $qualif[2] == $i) {
					        							echo "<option selected>$i</option>";
				        							}else{
					        							echo "<option>$i</option>";
				        							}				        							
				        						} ?>
				        					</select>
				        				</td>
				        				<td><input class="form-control" name="quBord<?= $qu ?>" type="text" placeholder="Board/Versity" value="<?= isset($qualif[3]) ? $qualif[3] : ''; ?>"></td>
				        			</tr>
		        				<?php
		        			} ?>

		        		</table>
				    		
				    	</div>

				    	<div class="form-group">
				    		<?php
					    		$dataNow = 1;
					    		if (isset($edit)) {
		        				$staffTraining = json_decode($edit->staffTraining);
		        				$dataNow = sizeof($staffTraining) > 0 ? sizeof($staffTraining) : 1;
			        		}
					    	?>
				    		<label>Training</label>
				    		<table class="table table-striped table-bordered"> 
				    			<thead>
			        			<tr class="table-primary">
			        				<th>Name:</th>
			        				<th>Date:</th>
			        				<th>Duration</th>
			        				<th>Organizetion</th>
			        				<th>Venue</th>
			        			</tr>
				    			</thead>
				    			<tbody class="tecTraining" data-now='<?= $dataNow ?>'>
				    				<?php if (isset($edit) && sizeof($staffTraining) > 0) { foreach ($staffTraining as $key => $train) { ?>
				        			<tr class="inputGrp">
				        				<td><input class="form-control" name="train[]" type="text" placeholder="Training Name"  value="<?= isset($train[0]) ? $train[0] : ''; ?>"></td>
				        				
				        				<td>
				        					<input class="form-control" name="trDate[]" type="Date" placeholder="Date"  value="<?= isset($train[1]) ? $train[1] : ''; ?>">
				        				</td>
				        				<td><input style="width: 70px;" class="form-control" name="duration[]" type="number"  value="<?= isset($train[2]) ? $train[2] : ''; ?>"> Days</td>

				        				<td><input class="form-control" name="orga[]" type="text" placeholder="Organizetion"  value="<?= isset($train[3]) ? $train[3] : ''; ?>"></td>

				        				<td><input class="form-control" name="venue[]" type="text" placeholder="Venue"  value="<?= isset($train[4]) ? $train[4] : ''; ?>"></td>
				        			</tr>
				    				<?php } }else{ ?>
				    					<tr class="inputGrp">
				        				<td><input class="form-control" name="train[]" type="text" placeholder="Training Name"></td>
				        				<td><input class="form-control" name="trDate[]" type="Date" placeholder="Date"></td>
				        				<td><input style="width: 70px;" class="form-control" name="duration[]" type="number"> Days</td>
				        				<td><input class="form-control" name="orga[]" type="text" placeholder="Organizetion"></td>
				        				<td><input class="form-control" name="venue[]" type="text" placeholder="Venue"></td>
				        			</tr>
				    				<?php } ?>
				    			</tbody>
				    			<tfoot>
				    				<tr>
				    					<td colspan="5" class="text-right">
				    						<button type="button" class="btn btn-danger removeTra">Remove</button>
							    			<button type="button" class="btn btn-info addMoreTra">Add More</button>
				    					</td>
				    				</tr>
				    			</tfoot>
		        		</table>
				    	</div>

				    	
				    	
				    	

				    	<div class="form-group">
				    		<label>Note</label>
				    		<textarea class="form-control" name="staffNote"><?= isset($edit) ? $edit->staffNote : ''; ?></textarea>
				    	</div>
				    
						<div class="form-group">
				    		<label>Staff Serial Number</label>
				    		<input class="form-control" type="number" autocomplete="" value="<?= isset($edit) ? $edit->staff_serial : ''; ?>" name="staff_serial" >
				    	</div>

				    	<div class="form-group text-right">
				    		<button class="btn btn-primary" type="submit" name="<?= isset($edit) ? 'updateStaff' : 'addStaff'; ?>"><?= isset($edit) ? 'Update' : 'Add'; ?> Staff </button>
				    	</div>

				    </form>
				  </div>
				</div>
			</div>
		<?php }else{ ?>
			<div class="col-md-7">
				<div class="panel panel-info">

					<?php 
						$staffId = $_GET['id'];
					  $staffs = $wpdb->get_results("SELECT * FROM ct_staff WHERE staffid = $staffId" );
					  foreach ($staffs as $staff) {
					    ?>
					    <div class="panel-heading">
					    	<h3><?= $staff->staffName ?><br><small><?= $staff->staffSQuali ?></small></h3>
					    	
					    </div>
				  		<div class="panel-body">
						    <div id="studentProfile" class="row">
						      <div class="col-md-4">
						        <?php if(!empty($staff->staffImg)){ ?>
						        <img src="<?= $staff->staffImg ?>" class="img-responsive stdImg">
						        <?php }else{ ?>
						        <img src="<?= get_template_directory_uri() ?>/img/No_Image.jpg" class="img-responsive stdImg">
						        <?php } ?>
						      </div>
						      <div class="col-md-8">

						        <div class="row">
						        	<div class="col-md-6">
						        		<label>Designation</label>
						            <p><?= output($staff->staffDesignation) ?></p>
					            <label>Status</label>
					            <p><?= output(!empty($staff->status) ? $staff->status : 'Present') ?></p>
						            <label>Phone</label>
								        <p><?= output($staff->staffPhone) ?></p>
								        <label>Birth Date</label>
								        <p><?= output($staff->staffBirth) ?></p>
								        <label>Blood Group</label>
								        <p><?= output($staff->staffBlood) ?></p>
								        <label>Joining Date</label>
								        <p><?= output($staff->staffJoining) ?></p>
						        	</div>

						        	<div class="col-md-6">
						        		<label>Father</label>
						            <p><?= output($staff->staffFather) ?></p>
						            <label>Mother</label>
								        <p><?= output($staff->staffMother) ?></p>
								        <label>NID</label>
								        <p><?= output($staff->staffNid) ?></p>
								        <label>MPO No</label>
								        <p><?= output($staff->staffMpo) ?></p>
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
							            <p><?= output($staff->staffPresent) ?></p>
							            <label>Permanent Address</label>
							            <p><?= output($staff->staffPermanent) ?></p>
				        				</td>
				        			</tr>
				        		</table>

						        <div class="row">
						        	<div class="col-md-12">

		        						<?php if(!empty($staff->staffQualificarion)){ ?>

							        		<label>Qualification</label>
							            <table class="table-striped table table-bordered text-center">
							            	<tr class="table-primary">
							        				<th>SL</th>
							        				<th>Exam:</th>
							        				<th>Div/Class:</th>
							        				<th>Year</th>
							        				<th>Board/University</th>
							        			</tr>
							        			<?php
							        				$qualifications = json_decode($staff->staffQualificarion);
							        				foreach ($qualifications as $key => $qualification) {
							        					?>
							        					<tr>
							        						<td><?= $key+1 ?></td>
							        						<?php 
							        							foreach ($qualification as $value) {
							        								echo "<td>$value</td>";
							        							}
							        						?>
							        					</tr>
							        					<?php
							        				}
							        			?>
							            </table>
							          <?php } ?>

						            <?php if(!empty($staff->staffTraining)){ ?>
							            <label>Training</label>
							            <table class="table-striped table table-bordered text-center">
							            	<tr class="table-primary">
							        				<th>SL</th>
							        				<th>Name:</th>
							        				<th>Date:</th>
							        				<th>Duration</th>
							        				<th>Organizetion</th>
							        				<th>Venue</th>
							        			</tr>
							        			<?php
							        				$trainings = json_decode($staff->staffTraining);
							        				foreach ($trainings as $key => $training) {
							        					?>
							        					<tr>
							        						<td><?= $key+1 ?></td>
							        						<?php 
							        							foreach ($training as $key => $value) {
							        								if($key == 2){ echo "<td>$value Days</td>"; }
							        								else{ echo "<td>$value</td>"; }
							        							}
							        						?>
							        					</tr>
							        					<?php
							        				}
							        			?>
							            </table>
							          <?php } ?>

							          <?php if(!empty($staff->staffNote)){ ?>
							            <label>Note</label>
							            <p><?= output($staff->staffNote) ?></p>
							          <?php } ?>

						        	</div>
						        </div>
						      </div>
						    </div>
						  </div>
						  <?php
						}
					?>
				
				</div>
			</div>
		<?php }?>
		<div class="col-md-5">
			<div class="panel panel-info">
			  <div class="panel-heading"><h3>All Staff </h3></div>
			  <div class="panel-body">
					<table class="table table-bordered table-responsive" id="datatable">
						<thead>
							<tr>
								<th>Serial</th>
								<th>Name</th>
								<th style="width: 50px">Image</th>
								<th style="width: 90px">Status</th>
								<th style="width: 60px">Action</th>
							</tr>
						</thead>
						<tbody>
						<?php
							$staffs = $wpdb->get_results( "SELECT * FROM ct_staff order by staff_serial" );
							foreach ($staffs as $staff) {
								?>
								<tr>
									<td><?= $staff->staff_serial ?></td>
									<td><?= $staff->staffName ?></td>
									<td class="text-center" style="padding: 0"><?= (!empty($staff->staffImg)) ? "<img height='50' src='".$staff->staffImg."'>" : ''; ?></td>
									<td><?= !empty($staff->status) ? $staff->status : 'Present'; ?></td>
									<td class="actionTd">

										<a class="btn-link" href="?page=staff&id=<?= $staff->staffid ?>">
											<span class="dashicons dashicons-visibility"></span>
										</a>
										<form class="actionForm" method="POST" action="">
						        	<input type="hidden" name="id" value="<?= $staff->staffid ?>">
						        	<button type="submit" name="editStaff" class="btn-link">
						        		<span class="dashicons dashicons-welcome-write-blog"></span>
						        	</button>
						        	<button type="button" class="btn-link btnDelete" data-id='<?= $staff->staffid ?>'>
						        		<span class="dashicons dashicons-trash"></span>
						        	</button>
						        </form>
									</td>
								</tr>

								<?php
							}
						?>
						</tbody>
					</table>
			  </div>
			</div>
		</div>
	</div>
</div>

<?php if ( ! is_admin() ) { ?>
				</div>
			</div>
		</div>
	</div>
</div>
<?php get_footer(); } ?>

<div id="deleteModal" class="modal fade" role="dialog">
  <div class="modal-dialog modal-sm">

    <!-- Modal content-->
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
        	<button type="submit" class="btn btn-danger" name="deleteStaff">Delete</button>
	      </form>
      </div>
    </div>

  </div>
</div>


<script type="text/javascript">
	(function($) {
		$(document).ready(function() {
			var $siteUrl = $('#theSiteURL').text();

			$('.addMoreSub').click(function(event) {
				$now = $('.tecAssignSub').data('now') + 1;
				if ($now < 21) {
					$('.tecAssignSub').data('now',$now);
					$(".tecAssignSub").find('.inputGrp:first').clone(true).appendTo(".tecAssignSub");
					$(".tecAssignSub").find('.inputGrp:last').find('input').val('');
				}
			});

			$('.removeSub').click(function(event) {
				$now = $('.tecAssignSub').data('now') - 1;
				if ($now > 0) {
					$('.tecAssignSub').data('now',$now);
					$(".tecAssignSub").find('.inputGrp:last').remove();
				}
			});

			$('.addMoreTra').click(function(event) {
				$now = $('.tecTraining').data('now') + 1;
				if ($now < 21) {
					$('.tecTraining').data('now',$now);
					$(".tecTraining").find('.inputGrp:first').clone(true).appendTo(".tecTraining");
					$(".tecTraining").find('.inputGrp:last').find('input').val('');
				}
			});
			$('.removeTra').click(function(event) {
				$now = $('.tecTraining').data('now') - 1;
				if ($now > 0) {
					$('.tecTraining').data('now',$now);
					$(".tecTraining").find('.inputGrp:last').remove();
				}
			});

			$('.assignClass').change(function(event) {
				$this = $(this);
	      $.ajax({
	        url: $siteUrl+"/inc/ajaxAction.php",
	        method: "POST",
	        data: { class : $this.val(), type : 'getSubject' },
	        dataType: "html"
	      }).done(function( msg ) {
	        $this.closest('.inputGrp').find('.assignSub').html(msg);
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

			$('.btnDelete').click(function(event) {
				$('#deleteModal').find('.id').val($(this).data('id'));
				$('#deleteModal').modal("show");
			});
		});
	})( jQuery );
</script>