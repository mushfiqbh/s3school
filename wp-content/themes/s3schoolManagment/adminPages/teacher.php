<?php
/**
 * Template Name: Admin Teachers
 */

global $wpdb;


/*=================
	Add Teacher
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
if (isset($_POST['addTeacher'])) {

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
	$teacherQualificarion = json_encode($qualificArray);

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
	$teacherTraining = json_encode($trainingArray);
	if($_POST['userid'] != '' && $_POST['userpass'] != '' ){
		
		$user_info = wp_insert_user( array(
			'user_login' => str_replace(' ', '', htmlentities($_POST['userid'], ENT_QUOTES)),
			'user_pass' => $_POST['userpass'],
			'user_email' => '',
			'first_name' => htmlentities($_POST['teacherName'], ENT_QUOTES),
			'last_name' => '',
			'display_name' => htmlentities($_POST['teacherName'], ENT_QUOTES),
			'role' => 'um_teachers'
		  ));
		$userId = $user_info;
	}

	$insert = $wpdb->insert(
		'ct_teacher',
		array(
			'tecUserId' 						=> $userId,
			'teacherName' 					=> htmlentities($_POST['teacherName'], ENT_QUOTES),
			'tecAssignSub' 					=> json_encode(array_filter($_POST["subjects"])),
			'teacherSQuali' 				=> htmlentities($_POST['teacherSQuali'], ENT_QUOTES),
			'teacherImg' 						=> htmlentities($_POST['teacherImg'], ENT_QUOTES),
			'teacherNote' 					=> htmlentities($_POST['teacherNote'], ENT_QUOTES),
			'teacherFather' 				=> htmlentities($_POST['teacherFather'], ENT_QUOTES),
			'teacherMother' 				=> htmlentities($_POST['teacherMother'], ENT_QUOTES),
			'teacherDesignation' 		=> htmlentities($_POST['teacherDesignation'], ENT_QUOTES),
			'teacherBirth' 					=> htmlentities($_POST['teacherBirth'], ENT_QUOTES),
			'teacherBlood' 					=> htmlentities($_POST['teacherBlood'], ENT_QUOTES),
			'teacherJoining' 				=> htmlentities($_POST['teacherJoining'], ENT_QUOTES),
			'teacherPhone' 					=> htmlentities($_POST['teacherPhone'], ENT_QUOTES),
			'teacherNid' 						=> htmlentities($_POST['teacherNid'], ENT_QUOTES),
			'teacherPresent' 				=> htmlentities($_POST['teacherPresent'], ENT_QUOTES),
			'teacherPermanent' 			=> htmlentities($_POST['teacherPermanent'], ENT_QUOTES),
			'teacherMpo' 						=> htmlentities($_POST['teacherMpo'], ENT_QUOTES),
			'teacherQualificarion' 	=> $teacherQualificarion,
			'teacher_serial' 	=>  htmlentities($_POST['teacher_serial'], ENT_QUOTES),
			'assignSection' 	=>  htmlentities($_POST['sec'], ENT_QUOTES),
			'status' 		=> htmlentities(isset($_POST['status']) ? $_POST['status'] : 'Present', ENT_QUOTES),
			'teacherTraining' 			=> $teacherTraining
		)
	);
	
	$message = ms3message($insert, 'Added');
}

/*edit Section*/
$editid = 0;
if (isset($_POST['editTeacher'])) {
	$editid = $_POST['id'];
	$edit = $wpdb->get_results( "SELECT * FROM ct_teacher WHERE teacherid = $editid" );
	$edit = $edit[0];

	$userNameInfo = $wpdb->get_results( "SELECT user_login FROM sm_users WHERE ID = $edit->tecUserId" );

	if($userNameInfo){
		$userName = $userNameInfo[0]->user_login;
		$_SESSION['tecUserId'] = $edit->tecUserId;
	}else{
		$userName = '';
		$_SESSION['tecUserId'] = null;
	}

}

/*Update Section*/
if (isset($_POST['updateTeacher'])) {
	$userId = $_SESSION['tecUserId'];
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
	$teacherQualificarion = json_encode($qualificArray);

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
	$teacherTraining = json_encode($trainingArray);

	if($_POST['userid'] != '' && $_POST['userpass'] != '' ){
		if($_SESSION['tecUserId'] > 0){
			$userId = $_SESSION['tecUserId'];
			wp_set_password($_POST['userpass'], $userId);
			$user_info = $wpdb->update(
				'sm_users', array(
				'user_login' => str_replace(' ', '', htmlentities($_POST['userid'], ENT_QUOTES)),
				// 'user_pass' => MD5($_POST['userpass'])				
			  ),
			  array( 'ID' => $userId)
			);
		}else{
			$user_info = wp_insert_user( array(
				'user_login' => str_replace(' ', '', htmlentities($_POST['userid'], ENT_QUOTES)),
				'user_pass' => $_POST['userpass'],
				'user_email' => '',
				'first_name' => htmlentities($_POST['teacherName'], ENT_QUOTES),
				'last_name' => '',
				'display_name' => htmlentities($_POST['teacherName'], ENT_QUOTES),
				'role' => 'um_teachers'
			  ));

			  $userId = $user_info;
		}
		
	}

	$update = $wpdb->update(
		'ct_teacher',
		array(
			'tecUserId' 						=> $userId,
			'teacherName' 					=> htmlentities($_POST['teacherName'], ENT_QUOTES),
			'tecAssignSub' 					=> isset($_POST["subjects"]) ? json_encode(array_filter($_POST["subjects"])) : '',
			'teacherSQuali' 				=> htmlentities($_POST['teacherSQuali'], ENT_QUOTES),
			'teacherImg' 						=> htmlentities($_POST['teacherImg'], ENT_QUOTES),
			'teacherNote' 					=> htmlentities($_POST['teacherNote'], ENT_QUOTES),
			'teacherFather' 				=> htmlentities($_POST['teacherFather'], ENT_QUOTES),
			'teacherMother' 				=> htmlentities($_POST['teacherMother'], ENT_QUOTES),
			'teacherDesignation' 		=> htmlentities($_POST['teacherDesignation'], ENT_QUOTES),
			'teacherBirth' 					=> htmlentities($_POST['teacherBirth'], ENT_QUOTES),
			'teacherBlood' 					=> htmlentities($_POST['teacherBlood'], ENT_QUOTES),
			'teacherJoining' 				=> htmlentities($_POST['teacherJoining'], ENT_QUOTES),
			'teacherPhone' 					=> htmlentities($_POST['teacherPhone'], ENT_QUOTES),
			'teacherNid' 						=> htmlentities($_POST['teacherNid'], ENT_QUOTES),
			'teacherPresent' 				=> htmlentities($_POST['teacherPresent'], ENT_QUOTES),
			'teacherPermanent' 			=> htmlentities($_POST['teacherPermanent'], ENT_QUOTES),
			'teacherMpo' 						=> htmlentities($_POST['teacherMpo'], ENT_QUOTES),
			'teacherQualificarion' 	=> $teacherQualificarion,
			'teacher_serial' 	=>  htmlentities($_POST['teacher_serial'], ENT_QUOTES),
			'assignSection' 	=>  htmlentities($_POST['sec'], ENT_QUOTES),
			'status' 		=> htmlentities(isset($_POST['status']) ? $_POST['status'] : 'Present', ENT_QUOTES),
			'teacherTraining' 			=> $teacherTraining
		),
		array( 'teacherid' => $_POST['id'])
	);

	

	$message = ms3message($update, 'Updated');
	$_SESSION['tecUserId'] = null;
}


/*Delete Section*/
if (isset($_POST['deleteTeacher'])) {
	$delete = $wpdb->delete( 'ct_teacher', array( 'teacherid' => $_POST['id'] ) );
	$message = ms3message($delete, 'Deleted');
}





function output($content){
	return str_replace("\n","<br />", html_entity_decode($content));
}

$allTecAcc = get_users(array('role' => 'um_teachers' ));
$haveTechquer = $wpdb->get_results( "SELECT tecUserId FROM ct_teacher GROUP BY tecUserId" );
$haveTech = array();
foreach ($haveTechquer as $vtech) {
	$haveTech[] = $vtech->tecUserId;
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

	<h2>Teacher Management <a href="?page=teacher" class="pull-right btn btn-success">Add Teacher</a> </h2><br>
	<div class="row">
		<?php if(!isset($_GET['id']) || isset($_POST['editTeacher'])){ ?>
			<div class="col-md-7">
				<div class="panel panel-info">
				  <div class="panel-heading"><h3><?= isset($edit) ? 'Edit' : 'Add'; ?> Teacher</h3></div>
				  <div class="panel-body">
				    <form action="" method="POST">

			    		<input type="hidden" name="id" value="<?= $editid ?>">
				    	<div class="row">

				    		<div class="form-group col-md-6">
					    		<label>Teacher Name</label>
					    		<input class="form-control" type="text" name="teacherName" value="<?= isset($edit) ? $edit->teacherName : ''; ?>" required>
					    	</div>

					    	<div class="form-group col-md-3">
					    		<label>Teacher Image</label>
					    		<div class="mediaUploadHolder">
						    		<button type="button" class="mediaUploader btn btn-success">Upload</button>
						    		<span>
						    			<?php echo (isset($edit)) ? "<img height='40' src='".$edit->teacherImg."'>" : ''; ?>
						    		</span>

										<input class="hidden teacherImg" type="text" name="teacherImg" value="<?= isset($edit) ? $edit->teacherImg : ''; ?>">
					    		</div>
					    	</div>

					    	<!-- <div class="form-group col-md-3">
					    		<label>Account</label>
					    		<select class="form-control" name="tecUserId">
					    			<option>Select</option>
					    			<?php
					    				foreach ($allTecAcc as $key => $value) {
					    					if(in_array($value->data->ID, $haveTech))
					    						continue;
					    					$sel = "";
					    					if(isset($edit)){
					    						$sel = ($value->data->ID == $edit->tecUserId) ? 'selected' : '';
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
					    		<input class="form-control" type="text" name="teacherSQuali" value="<?= isset($edit) ? $edit->teacherSQuali : ''; ?>" required>
					    	</div>

					    	<div class="form-group col-md-6">
					    		<label>Father's Name</label>
					    		<input class="form-control" type="text" name="teacherFather" value="<?= isset($edit) ? $edit->teacherFather : ''; ?>" required>
					    	</div>

					    	<div class="form-group col-md-6">
					    		<label>Mother's Name</label>
					    		<input class="form-control" type="text" name="teacherMother" value="<?= isset($edit) ? $edit->teacherMother : ''; ?>" required>
					    	</div>

					    	<div class="form-group col-md-6">
					    		<label>Designation</label>
					    		<input class="form-control" type="text" name="teacherDesignation" value="<?= isset($edit) ? $edit->teacherDesignation : ''; ?>" required>
					    	</div>

					    	<div class="form-group col-md-6">
					    		<label>Phone</label>
					    		<input class="form-control" type="text" name="teacherPhone" value="<?= isset($edit) ? $edit->teacherPhone : ''; ?>" >
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
					    		<input class="form-control" type="date" name="teacherBirth" value="<?= isset($edit) ? $edit->teacherBirth : ''; ?>" required>
					    	</div>

					    	<div class="form-group col-md-4">
					    		<label>Joining Date</label>
					    		<input class="form-control" type="date" name="teacherJoining" value="<?= isset($edit) ? $edit->teacherJoining : ''; ?>" required>

					    	</div>

					    	<div class="form-group col-md-4">
					    		<label>Blood Group</label>
					    		<input class="form-control" type="text" name="teacherBlood" value="<?= isset($edit) ? $edit->teacherBlood : ''; ?>">
					    	</div>


					    	<div class="form-group col-md-6">
					    		<label>NID no</label>
					    		<input class="form-control" type="text" name="teacherNid" value="<?= isset($edit) ? $edit->teacherNid : ''; ?>" >
					    	</div>

					    	<div class="form-group col-md-6">
					    		<label>MPO Index no</label>
					    		<input class="form-control" type="text" name="teacherMpo" value="<?= isset($edit) ? $edit->teacherMpo : ''; ?>" >

					    	</div>
				    	</div>

				    	<div class="form-group">
				    		<label>Present Address</label>
				    		<input class="form-control" type="text" name="teacherPresent" value="<?= isset($edit) ? $edit->teacherPresent : ''; ?>" >
				    	</div>

				    	<div class="form-group">
				    		<label>Permanent Address</label>
				    		<input class="form-control" type="text" name="teacherPermanent" value="<?= isset($edit) ? $edit->teacherPermanent : ''; ?>" >
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
		        				$qualifications = json_decode($edit->teacherQualificarion);
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
		        				$teacherTraining = json_decode($edit->teacherTraining);
		        				$dataNow = sizeof($teacherTraining) > 0 ? sizeof($teacherTraining) : 1;
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
				    				<?php if (isset($edit) && sizeof($teacherTraining) > 0) { foreach ($teacherTraining as $key => $train) { ?>
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

				    	<?php
				    		$dataNow = 1;
				    		if (isset($edit) && $edit->tecAssignSub != '[]' && $edit->tecAssignSub != '[""]') {
	        				$tecAss = json_decode($edit->tecAssignSub);
	        				if ($tecAss != null && sizeof($tecAss) > 0) {
	        					$dataNow = sizeof($tecAss);
	        					$tecAssignSub = $wpdb->get_results( "SELECT subjectid,subjectClass FROM ct_subject WHERE subjectid IN (".implode(",", $tecAss).") ORDER BY subjectClass DESC");
	        				}
		        		}
				    	?>

				    	<div class="tecAssignSub" data-now='<?= $dataNow ?>'>
				    		<label>Assign Subjet To Teacher</label>
				    		<?php for ($i=0; $i < $dataNow; $i++) { ?>
					    		<div class="inputGrp row">
							    	<div class="form-group col-md-6">
							    		<select class="form-control assignClass">
							    			<option value=''>Select Class</option>
							    			<?php
							    				$classQuery = $wpdb->get_results( "SELECT classid,className FROM ct_class WHERE classid IN (SELECT subjectClass FROM `ct_subject` GROUP BY subjectClass) ORDER BY className");
							    				$subCls = $tecAssignSub[$i]->subjectClass;
							    				foreach ($classQuery as $value) {
							    					$sel = ($subCls == $value->classid) ? 'selected' : '';
							    					echo "<option value='".$value->classid."' $sel>".$value->className."</option>";
							    				}
							    				
							    			?>
							    		</select>
							    	</div>
							    	
							    	<div class="form-group col-md-6"">
					<select id="resultSection" class="form-control" name="sec"  >
					<?php
								    			if (isset($edit)) { 
								    				$subQry = $wpdb->get_results( "SELECT sectionid,sectionName FROM `ct_section` WHERE forClass = $subCls ORDER BY sectionName");
								    				echo "<option value=''>  Section</option>";
								    				foreach ($subQry as $subv) {
								    					$sel = ($edit->assignSection == $subv->sectionid) ? 'selected' : '';
								    					echo "<option value='".$subv->sectionid."' $sel>".$subv->sectionName."</option>";
								    				}
							    				}else{
							    					echo "<option value=''>Select Class First</option>";
							    				}
							    			?>
					</select>
				</div>

							    	<div class="form-group col-md-6">
							    		<select class="form-control assignSub" name="subjects[]">
							    			<?php
								    			if (isset($edit)) { 
								    				$subQry = $wpdb->get_results( "SELECT subjectid,subjectName FROM `ct_subject` WHERE subjectClass = $subCls ORDER BY subjectName");
								    				foreach ($subQry as $subv) {
								    					$sel = ($tecAssignSub[$i]->subjectid == $subv->subjectid) ? 'selected' : '';
								    					echo "<option value='".$subv->subjectid."' $sel>".$subv->subjectName."</option>";
								    				}
							    				}else{
							    					echo "<option value=''>Select Class First</option>";
							    				}
							    			?>
							    			
							    		</select>
							    	</div>
					    		</div>
					    	<?php } ?>
				    	</div>
				    	<div class="text-right">
				    		<button type="button" class="btn btn-danger removeSub">Remove</button>
				    		<button type="button" class="btn btn-info addMoreSub">Add More</button>
				    	</div>

				    	<div class="form-group">
				    		<label>Note</label>
				    		<textarea class="form-control" name="teacherNote"><?= isset($edit) ? $edit->teacherNote : ''; ?></textarea>
				    	</div>
				    	<div class="form-group">
				    		<label>User ID</label>
				    		<input class="form-control" type="text" name="userid" autocomplete="userid" value="<?= $userName ?>" >
				    	</div>
						<div class="form-group">
				    		<label>Password</label>
				    		<input class="form-control" type="password" autocomplete="new-password" name="userpass" >
				    	</div>
						<div class="form-group">
				    		<label>Teacher Serial Number</label>
				    		<input class="form-control" type="number" autocomplete="" value="<?= isset($edit) ? $edit->teacher_serial : ''; ?>" name="teacher_serial" >
				    	</div>

				    	<div class="form-group text-right">
				    		<button class="btn btn-primary" type="submit" name="<?= isset($edit) ? 'updateTeacher' : 'addTeacher'; ?>"><?= isset($edit) ? 'Update' : 'Add'; ?> Teacher</button>
				    	</div>

				    </form>
				  </div>
				</div>
			</div>
		<?php }else{ ?>
			<div class="col-md-7">
				<div class="panel panel-info">

					<?php 
						$teacherId = $_GET['id'];
					  $teachers = $wpdb->get_results("SELECT * FROM ct_teacher WHERE teacherid = $teacherId" );
					  foreach ($teachers as $teacher) {
					    ?>
					    <div class="panel-heading">
					    	<h3><?= $teacher->teacherName ?><br><small><?= $teacher->teacherSQuali ?></small></h3>
					    	
					    </div>
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
						            <p><?= output($teacher->teacherDesignation) ?></p>
						            <label>Phone</label>
								        <p><?= output($teacher->teacherPhone) ?></p>
								        <label>Birth Date</label>
								        <p><?= output($teacher->teacherBirth) ?></p>
								        <label>Blood Group</label>
								        <p><?= output($teacher->teacherBlood) ?></p>
								        <label>Joining Date</label>
								        <p><?= output($teacher->teacherJoining) ?></p>
						        	</div>

						        	<div class="col-md-6">
						        		<label>Father</label>
						            <p><?= output($teacher->teacherFather) ?></p>
						            <label>Mother</label>
								        <p><?= output($teacher->teacherMother) ?></p>
								        <label>NID</label>
								        <p><?= output($teacher->teacherNid) ?></p>
								        <label>MPO No</label>
								        <p><?= output($teacher->teacherMpo) ?></p>
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
							            <p><?= output($teacher->teacherPresent) ?></p>
							            <label>Permanent Address</label>
							            <p><?= output($teacher->teacherPermanent) ?></p>
				        				</td>
				        			</tr>
				        		</table>

						        <div class="row">
						        	<div class="col-md-12">

		        						<?php if(!empty($teacher->teacherQualificarion)){ ?>

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
							        				$qualifications = json_decode($teacher->teacherQualificarion);
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

						            <?php if(!empty($teacher->teacherTraining)){ ?>
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
							        				$trainings = json_decode($teacher->teacherTraining);
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

							          <?php if(!empty($teacher->teacherNote)){ ?>
							            <label>Note</label>
							            <p><?= output($teacher->teacherNote) ?></p>
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
			  <div class="panel-heading"><h3>All Teacher</h3></div>
			  <div class="panel-body">
					<table class="table table-bordered table-responsive" id="datatable">
						<thead>
							<tr>
								<th>Serial</th>
								<th>Name</th>
								<th style="width: 50px">Image</th>
								<th style="width: 60px">Action</th>
							</tr>
						</thead>
						<tbody>
						<?php
							$teachers = $wpdb->get_results( "SELECT * FROM ct_teacher order by teacher_serial" );
							foreach ($teachers as $teacher) {
								?>
								<tr>
									<td><?= $teacher->teacher_serial ?></td>
									<td><?= $teacher->teacherName ?></td>
									<td class="text-center" style="padding: 0"><?= (!empty($teacher->teacherImg)) ? "<img height='50' src='".$teacher->teacherImg."'>" : ''; ?></td>
									<td class="actionTd">

										<a class="btn-link" href="?page=teacher&id=<?= $teacher->teacherid ?>">
											<span class="dashicons dashicons-visibility"></span>
										</a>
										<form class="actionForm" method="POST" action="">
						        	<input type="hidden" name="id" value="<?= $teacher->teacherid ?>">
						        	<button type="submit" name="editTeacher" class="btn-link">
						        		<span class="dashicons dashicons-welcome-write-blog"></span>
						        	</button>
						        	<button type="button" class="btn-link btnDelete" data-id='<?= $teacher->teacherid ?>'>
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
        	<button type="submit" class="btn btn-danger" name="deleteTeacher">Delete</button>
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