<?php
/*
** Template Name: Admin Demotion
*/ 
 global $wpdb; global $s3sRedux; 
	

if (isset($_POST['demotStu'])) {

	$prevClass = $_POST['prevClass'];
	$prevYear = $_POST['prevYear'];

	$toClass = $_POST['promotclass'];
	$infoYear = $_POST['infoYear'];
	$toSection = $_POST['promotsection'];


	foreach ($_POST['demotion'] as $stdid) {

		/*If exist in promot class*/
		$tocalssinfo = $wpdb->get_results("SELECT * FROM `ct_studentinfo` WHERE infoStdid = $stdid AND infoClass = $toClass AND infoYear = '$infoYear' limit 1");

		if(sizeof($tocalssinfo) < 1){
			$previnfo = $wpdb->get_results("SELECT * FROM ct_studentinfo WHERE infoStdid = $stdid AND infoClass = $prevClass AND infoYear = '$prevYear' limit 1");
			$prev4th = $previnfo[0]->info4thSub;
    	$prevOpt = $previnfo[0]->infoOptionals;
    	if($prev4th != 0){
    		$info4thSub = $wpdb->get_results("SELECT subjectid FROM `ct_subject` WHERE subjectClass = $toClass AND subid IN (SELECT subid FROM `ct_subject` WHERE subjectid = $prev4th) limit 1");
    		$info4thSub = isset($info4thSub[0]) ? $info4thSub[0]->subjectid : '';
    	}else{
    		$info4thSub = '';
    	}
  		$oldOpt = json_decode($prevOpt);
  		$infoOptSub = array();
  		if (sizeof($oldOpt) > 0) {
  			$query = $wpdb->get_results("SELECT subjectid FROM `ct_subject` WHERE subjectClass = $toClass AND subid IN (SELECT subid FROM `ct_subject` WHERE subjectid IN (".implode (", ", $oldOpt).")) limit 1");
  			foreach ($query as $key => $value) {
  				$infoOptSub[] = $value->subjectid;
  			}
  		}
  		$infoOptSub = json_encode($infoOptSub);

  		$update = $wpdb->insert('ct_studentinfo', array(
	      'infoStdid' => $stdid,
	      'infoClass' => $toClass,
	      'infoSection' =>  $toSection,
	      'infoGroup' =>  $previnfo[0]->infoGroup,
	      'infoRoll' => $previnfo[0]->infoRoll,
	      'infoYear' => $infoYear,
	      'infoOptionals' => $infoOptSub,
	      'info4thSub' => $info4thSub
	    ));
		} 

		$update = $wpdb->update('ct_student', array( 'stdCurrentClass' => $toClass, 'stdCurntYear' => $infoYear), array('studentid' => $stdid));
		$wpdb->query( "DELETE FROM ct_studentinfo WHERE infoStdid = $stdid AND infoClass = $prevClass AND infoYear = '$prevYear'");

	}
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
	
	<div class="row">
		<!-- Show Status message -->
  	<?php if(isset($message)){ ms3showMessage($message); } ?>

		<div class="col-md-12">
			<div class="panel panel-info">
			  <div class="panel-heading"><h3>Demotion<br><small>Demote students</small></h3></div>
			  <div class="panel-body">

					<form class="form-inline" action="" method="GET">
						<input type="hidden" name="page" value="demotion">

						<div class="form-group">
							<label>Class</label>
							<select id='resultClass' class="form-control" name="class" required>
								<?php

									$classQuery = $wpdb->get_results( "SELECT classid,className FROM ct_class WHERE classid IN (SELECT stdCurrentClass FROM ct_student GROUP BY stdCurrentClass ORDER BY className ASC)" );
									echo "<option value=''>Select Class</option>";

									foreach ($classQuery as $class) {
										echo "<option value='".$class->classid."'>".$class->className."</option>";
									}
								?>
							</select>
						</div>


						<div class="form-group ">
							<label>Section</label>
							<select id="resultSection" class="form-control" name="section" required disabled>
								<option disabled selected>Select Class First</option>
							</select>
						</div>

						<div class="form-group">
							<label>Year</label>
							<select id='resultYear' class="form-control" name="year" required disabled>
								<option disabled selected>Select Class First</option>
							</select>
						</div>

						<div class="form-group" id="idRoll">
							<input class="form-control" type="text" name="roll" placeholder="Roll">
						</div>
						<div class="form-group">
							<input type="submit" value="Genarate List" class="btn btn-primary">
						</div>
					</form>
			  </div>
			</div>
		</div>

		<?php if(isset($_GET['year'])){ ?>

		  <div id="printArea" class="col-md-12 printBG">

			  <div >

			  	<?php
			  		$year 		= $_GET['year'];
			  		$class 		= $_GET['class'];
			  		$section 	= isset($_GET['section']) ? $_GET['section'] : '';
			  		$roll 		= isset($_GET['roll']) ? $_GET['roll'] : '';

			  		if (isset($_GET['year'])) {

			  			$querry = "SELECT studentid, stdName, infoRoll, classid, className, sectionid, sectionName, groupId, groupName FROM ct_student
			  								LEFT JOIN ct_studentinfo ON ct_student.studentid = ct_studentinfo.infoStdid AND $class = ct_studentinfo.infoClass AND '$year' = ct_studentinfo.infoYear
												LEFT JOIN ct_class ON ct_studentinfo.infoClass = ct_class.classid
												LEFT JOIN ct_group ON ct_studentinfo.infoGroup = ct_group.groupId
												LEFT JOIN ct_section ON ct_studentinfo.infoSection = ct_section.sectionid
												WHERE infoYear = '$year' AND stdCurrentClass = $class ";

							$querry .= ($roll != "") ? " AND infoRoll = $roll" : '';
							$querry .= ($section != "") ? " AND infoSection = $section" : '';

							$querry .=  " ORDER BY infoRoll";
							$groupsBy = $wpdb->get_results($querry);
			  		}

			  		if($groupsBy){
			  			?>
			  			<form action="" method="post">
			  				<input type="hidden" name="prevClass" value="<?= $class ?>">
			  				<input type="hidden" name="prevYear" value="<?= $year ?>">
			  				<div class="text-right">
			  					<div class="form-group pull-left">
										<select id="promotclass" class="form-control" name="promotclass" required >
											<option value="">Select Class</option>
											<?php
												$classedQuery = $wpdb->get_results( "SELECT classid,className FROM ct_class" );
												foreach ($classedQuery as $classRes) {
													$id = $classRes->classid;
													$name = $classRes->className;
													$disable = ($class == $classRes->classid) ? 'disabled' : '';
													echo "<option value='$id' $disable>$name</option>";
												}
											?>
										</select>
									</div>
									<div class="form-group pull-left">
										<select id="promotsection" class="form-control" name="promotsection" required disabled>
											<option value="">Select Class First</option>
										</select>
									</div>
									<div class="form-group pull-left">
										<select class="form-control" name="infoYear" required >
											<option value="">Select Year</option>
											<?php for ($i=-2; $i < 3; $i++) { 
		                    $sec = (date("Y")-$i)."-".(date("Y")-($i-1));
		                    $selected = ($edit->stdCurntYear == $sec) ? 'selected' : '';
		                    ?>
		                      <option value="<?= $sec; ?>" <?= $selected; ?>><?= $sec; ?></option>
		                    <?php
		                  } ?>
		                  <?php for ($i=-2; $i < 3; $i++) { 
		                    $sec = (date("Y")-$i);
		                    $selected = ($edit->stdCurntYear == $sec) ? 'selected' : '';
		                    ?>
		                      <option value="<?= $sec; ?>" <?= $selected; ?>><?= $sec; ?></option>
		                    <?php
		                  } ?>
										</select>
									</div>

			  					<input class="btn btn-danger" type="submit" name="demotStu" value="Demote">
			  				</div>
			  				<br>
			  				<table class="table table-responsive table-striped table-bordered">
			  					<tr>
			  						<th>#</th>
			  						<th>Name</th>
			  						<th>Roll</th>
			  						<th>Class</th>
			  						<th>Section</th>
			  						<th>Group</th>
			  						<th><label class="labelRadio">Select <input id="selectAll" type="checkbox"></label></th>
			  					</tr>
			  					<?php
			  					
			  					foreach ($groupsBy as $key => $value) {
									?>
										<tr>
					  					<td><?= $key+1 ?></td>
					  					<td><?= $value->stdName ?></td>
					  					<td><?= $value->infoRoll ?></td>
					  					<td><?= $value->className ?></td>
					  					<td><?= $value->sectionName ?></td>
					  					<td><?= $value->groupName ?></td>
					  					<td>
					  						<label class="labelRadio">
					  							<input class="stdSel" type="checkbox" name="demotion[]" value="<?= $value->studentid ?>"> Select
					  						</label>
					  					</td>
					  				</tr>
									<?php
									}
	
									?>
								</table>
							</form>
							<?php

						}else{
							echo "<h3 class='text-center'>Result / Student not Found</h3>";
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

<p id="theSiteURL" class="hidden"><?= get_template_directory_uri() ?></p>
<script type="text/javascript">
	(function($) {
	  var $siteUrl = $('#theSiteURL').text();
		$('#resultClass').change(function() {
	    $.ajax({
	      url: $siteUrl+"/inc/ajaxAction.php",
	      method: "POST",
	      data: { class : $(this).val(), type : 'getExams' },
	      dataType: "html"
	    }).done(function( msg ) {
	      $( "#resultExam" ).html( msg );
	      $( "#resultExam" ).prop('disabled', false);
	    });

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

		$('#promotclass').change(function() {
			$.ajax({
	      url: $siteUrl+"/inc/ajaxAction.php",
	      method: "POST",
	      data: { class : $(this).val(), type : 'getSection' },
	      dataType: "html"
	    }).done(function( msg ) {
	      $( "#promotsection" ).html( msg );
	      $( "#promotsection" ).prop('disabled', false);
	    });
		});
  })( jQuery );

	function print(divId) {
    var printContents = document.getElementById(divId).innerHTML;
    w = window.open();
    w.document.write(printContents);
    w.document.write('<scr' + 'ipt type="text/javascript">' + 'window.onload = function() { window.print(); window.close(); };' + '</sc' + 'ript>');
    w.document.close(); // necessary for IE >= 10
    w.focus(); // necessary for IE >= 10
    return true;
  }
</script>