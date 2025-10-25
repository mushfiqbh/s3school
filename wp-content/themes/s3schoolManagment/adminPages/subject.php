<?php
/**
* Template Name: Admin Subject
*/
global $wpdb; global $s3sRedux;

/*=================
Add Subject
=================*/
if (isset($_POST['addSubject'])) {

  if (isset($_POST['allGroup'])) {
    $forGroup = 'all';
  }else{
    $forGroup = isset($_POST['forGroup']) ? json_encode($_POST['forGroup']) : 0;
  }

	$insert = $wpdb->insert(
		'ct_subject',
		array(
			'subjectName' 		=> $_POST['subjectName'],
			'shortName' 		=> $_POST['shortName'],
            'subCode'         => $_POST['subCode'],
			'subid' 				  => $_POST['subid'],
			'subjectClass' 		=> $_POST['subjectClass'],
			'subOptinal' 			=> isset($_POST['subOptinal']) ? 1 : 0,
			'sub4th' 					=> isset($_POST['sub4th']) ? 1 : 0,
			'assessment' 			=> isset($_POST['assessment']) ? 1 : 0,
			'forGroup' 				=> $forGroup,
			'subjectTeacher' 	=> isset($_POST['subjectTeacher']) ? $_POST['subjectTeacher'] : 0,
			'subPaper' 				=> $_POST['subPaper'],
			'subMCQ' 					=> $_POST['subMCQ'],
			'subCQ' 					=> $_POST['subCQ'],
			'subPect' 				=> $_POST['subPect'],
            'subCa'           => $_POST['subCa'],
			'subCombineMark' 	=> isset($_POST['subCombineMark']) ? 1 : 0,
			'connecttedPaper' => isset($_POST['connecttedPaper']) ? $_POST['connecttedPaper'] : 0,
			'subjectNote' 		=> $_POST['subjectNote'],
			'religionId' 		=> @$_POST['religionId']
		)
	);

	$message = ms3message($insert, 'Added');
}

/*=================
Update Subject
=================*/
if (isset($_POST['updateSubject'])) {
  if (isset($_POST['allGroup'])) {
    $forGroup = 'all';
  }else{
    $forGroup = isset($_POST['forGroup']) ? json_encode($_POST['forGroup']) : 0;
  }
	$update = $wpdb->update(
		'ct_subject',
		array(
			'subjectName' 		=> $_POST['subjectName'],
			'shortName' 		=> $_POST['shortName'],
            'subCode'         => $_POST['subCode'],
			'subid' 				  => $_POST['subid'],
			'subjectClass' 		=> $_POST['subjectClass'],
			'subOptinal' 			=> isset($_POST['subOptinal']) ? 1 : 0,
			'sub4th' 					=> isset($_POST['sub4th']) ? 1 : 0,
			'assessment' 					=> isset($_POST['assessment']) ? 1 : 0,
			'forGroup' 				=> $forGroup,
			'subjectTeacher' 	=> isset($_POST['subjectTeacher']) ? $_POST['subjectTeacher'] : 0,
			'subPaper' 				=> $_POST['subPaper'],
			'subMCQ' 					=> $_POST['subMCQ'],
			'subCQ' 					=> $_POST['subCQ'],
			'subPect' 				=> $_POST['subPect'],
            'subCa'           => $_POST['subCa'],
			'subCombineMark' 	=> isset($_POST['subCombineMark']) ? 1 : 0,
			'connecttedPaper' => isset($_POST['connecttedPaper']) ? $_POST['connecttedPaper'] : 0,
			'subjectNote' 		=> $_POST['subjectNote'],
			'religionId' 		=> @$_POST['religionId']
		),
		array( 'subjectid' => $_POST['id'])
	);

	$message = ms3message($update, 'Updated');
}

/*=================
Delete Subject
==================*/
if (isset($_POST['deleteSubject'])) {
	$delete = $wpdb->delete('ct_subject', array( 'subjectid' => $_POST['id'] ));
	$message = ms3message($delete, 'Deleted');
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
  
  <!-- Show Status message -->
  <?php if(isset($message)){ ms3showMessage($message); } ?>
  
  <h2>Subject Management
    <button class="pull-right btn btn-primary addModal">Add Subject
    </button>
  </h2>
  <br>
  <div class="panel panel-info">
    <div class="panel-heading">
      <h3>All Subject</h3>
    </div>
    <div class="panel-body">
      <table class="table table-bordered table-responsive" id="datatable">
        <thead>
          <tr>
            <th>Name</th>
            <th>Short Name</th>
            <th>For Class</th>
            <th><?= $s3sRedux['mcqtitle'] ?></th>
            <th><?= $s3sRedux['cqtitle'] ?></th>
            <th><?= $s3sRedux['prctitle'] ?></th>
            <th><?= $s3sRedux['catitle'] ?></th>
            <th>Teacher</th>
            <th style="max-width: 150px">For Group</th>
            <th>Sub ID</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
					$subjects = $wpdb->get_results( "SELECT * FROM ct_subject LEFT JOIN ct_class ON ct_subject.subjectClass = ct_class.classid LEFT JOIN ct_teacher ON ct_subject.subjectTeacher = ct_teacher.teacherid" );
					foreach ($subjects as $subject) {
					?>
          <tr>
            <td class='subjectName'><?= $subject->subjectName ?></td>
            <td class='shortName'><?= $subject->shortName ?></td>
            <td class='className'><?= $subject->className ?></td>
            <td class='subMCQ'>
              <?= $subject->subMCQ ?>
            </td>
            <td class='subCQ'><?= $subject->subCQ ?></td>
            <td class='subPect'><?= $subject->subPect ?></td>
            <td class='subCa'><?= $subject->subCa ?></td>
            <td class='teacherName'><?= $subject->teacherName ?></td>
            <td class='f'>
              <?php 
                $groupCk = $subject->forGroup;
                if($groupCk == '0' || $groupCk  == 'all'){
                  echo "<span class='optSub'>All Group</span>";
                }else{
                  if (is_numeric($groupCk)) {
                    $groupQer = "SELECT groupName FROM ct_group WHERE groupId = $groupCk";
                  }else{
                    $jesonGrp = json_decode($groupCk);
                    $groupQer = "SELECT groupName FROM ct_group WHERE groupId IN (".implode (", ", $jesonGrp).")";
                  }
                  $groupQer = $wpdb->get_results( $groupQer );
                  foreach ($groupQer as $value) {
                    $tempGrp = $value->groupName;
                    echo "<span class='optSub'>$tempGrp</span>";
                  }
                }
              ?>            
            </td>
            <td class='Note'><?= $subject->subid ?></td>
            <td>
              <form class="pull-right actionForm" method="POST" action="">
                <input type="hidden" name="id" value="<?= $subject->subjectid ?>">
                <button type="button" class="btn-link editSubject"
                        data-id='<?= $subject->subjectid ?>'
                        data-subpaper='<?= $subject->subPaper ?>'
                        data-subcode='<?= $subject->subCode ?>'
                        data-subid='<?= $subject->subid ?>'
                        data-connecttedpaper='<?= $subject->connecttedPaper ?>'
                        data-subjectNote='<?= $subject->subjectNote ?>'
                        data-combinemark='<?= $subject->subCombineMark ?>'
                        data-sub4th='<?= $subject->sub4th ?>'
                        data-assessment='<?= $subject->assessment ?>'
                        data-forGroup='<?= $subject->forGroup ?>'
                        data-class='<?= $subject->subjectClass ?>'
                        data-teacherid='<?= $subject->teacherid ?>'
                        data-optional='<?= $subject->subOptinal ?>'
                        data-religionId='<?= $subject->religionId ?>'>
                  <span class="dashicons dashicons-welcome-write-blog">
                  </span>
                </button>
                <button type="button" class="btn-link btnDelete" data-id='<?= $subject->subjectid ?>'>
                  <span class="dashicons dashicons-trash">
                  </span>
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

<?php if ( ! is_admin() ) { ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php get_footer(); } ?>


<!--========================
	Subject Delete Modal
=========================-->
<div id="deleteModal" class="modal fade" role="dialog">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;
        </button>
        <h4 class="modal-title">Delete Data
        </h4>
      </div>
      <div class="modal-body">
        <p class="text-danger">You can't recover the data after delete.
        </p>
      </div>
      <div class="modal-footer">
        <form action="" method="POST">
          <input type="hidden" name="id" class="id">
          <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close
          </button>
          <button type="submit" class="btn btn-danger" name="deleteSubject">Delete
          </button>
        </form>
      </div>
    </div>
  </div>
</div>


<!--========================
	Subject Add/Edit Modal
=========================-->
<div id="addModal" class="modal fade" role="dialog">
    <p id="theSiteURL" class="hidden"><?= get_template_directory_uri() ?></p>
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;
        </button>
        <h4 class="modal-title">Add Subject</h4>
      </div>
      <div class="modal-body">
        <form action="" method="POST">
          <div class="row">
            <input class="id" type="hidden" name="id" value="">
            <div class="form-group col-md-5">
              <label>Subject Name</label>
              <input class="form-control subjectName" type="text" name="subjectName" value="" required>
            </div>
            <div class="form-group col-md-3">
              <label>Short Name</label>
              <input class="form-control shortName" type="text" name="shortName" value="" required>
            </div>
            <div class="form-group col-md-4">
              <label>For Class</label>
              <select class="form-control className" id="stdClass" name="subjectClass" required>
                <?php
								
								echo "<option disabled selected>Select a Class..</option>";
								$classes = $wpdb->get_results( "SELECT * FROM ct_class" );
								foreach ($classes as $class) {
									?>
	                <option value='<?= $class->classid ?>'>
	                	<?= $class->className ?>
	                </option>
	              <?php
								}
							?>
              </select>
          	</div>
          </div>

          <div class="row">

	          <div class="col-md-2">
	            <div class="form-group">
	              <label>Optional?</label><br>
                <label class="labelRadio">
	                <input class="optionalCk" type="checkbox" name="subOptinal" value="1"> Yes
	              </label>
	            </div>
	          </div>

	          <div class="col-md-2">
	            <div class="form-group">
	              <label>4th Sub?</label><br>
	              <label class="labelRadio">
                  <input class="sub4thCk" type="checkbox" name="sub4th" value="1"> Yes
	              </label>
	            </div>
	          </div>
	          <div class="col-md-2">
	            <div class="form-group">
	              <label>Assessment?</label><br>
	              <label class="labelRadio">
                  <input class="assessmentCk" type="checkbox" name="assessment" value="1"> Yes
	              </label>
	            </div>
	          </div>

	          <div class="form-group col-md-6 forGroup">
	            <label>For The Group</label>
              <br>
              <label class="labelRadio">
                <input class="allGrpCk" type="checkbox" name="allGroup" value="all"> All
              </label>
              
              <?php
              	$groups = $wpdb->get_results( "SELECT * FROM ct_group" );
								foreach ($groups as $group) {
									?>
                  <label class="labelRadio">
                    <input class="groupCkBox" type="checkbox" name="forGroup[]" value="<?= $group->groupId ?>"> <?= $group->groupName ?>
                  </label>
	                
	              <?php
								}
              ?>
             
	          </div>
	        </div>
	        <div class="row">
              
                  <div class="col-md-2">
    	            <div class="form-group">
    	              <label>Religion?</label><br>
    	              <label class="labelRadio">
                      <input class="religionck" type="checkbox" name="isReligion" value="1"> Yes
    	              </label>
    	            </div>
    	          </div>
    	          <!-- Radio Buttons -->
    	          <div class="col-md-10">
                    <div id="religionContainer" style="display:none">
                      <label><input type="radio" name="religionId" value="1"> Islam</label>
                      <label><input type="radio" name="religionId" value="2"> Hinduism</label>
                      <label><input type="radio" name="religionId" value="3"> Buddhism</label>
                      <label><input type="radio" name="religionId" value="4"> Christianity</label>
                      <label><input type="radio" name="religionId" value="5"> Others</label>
                      <label><input type="radio" name="religionId" value=""> None</label>
                    </div>
                </div>
	          </div>

	        <div class="row">
	          <div class="form-group col-md-4">
	            <label>Subject Type
	            </label>
	            <select class="form-control" id="subType" name="subPaper">
	              <option value="0">Main paper
	              </option>
	              <option value="1">1st paper
	              </option>
	              <option value="2">2nd paper
	              </option>
	            </select>
	          </div>
	          <div class="form-group col-md-4" id="combineMark">
	            <label>Combine the mark?
	            </label>
	            <label class="combineMark labelRadio">
	              <input type="checkbox" name="subCombineMark"> Combine Mark
	            </label>
	          </div>

	          <div class="form-group col-md-4 connecttedSub">
	            <label>Connected Paper
	            </label>
	            <select class="form-control" name="connecttedPaper">
	              <?php
									echo "<option selected>Select Connected paper..</option>";
									$subjects = $wpdb->get_results( "SELECT subjectid,subjectName,className FROM `ct_subject`
                    LEFT JOIN ct_class ON ct_class.classid = ct_subject.subjectClass
                    WHERE `subPaper` = 1" );
									foreach ($subjects as $subject) {
										?>
				              <option value='<?= $subject->subjectid ?>'><?= $subject->subjectName ?>(<?= $subject->className ?>)</option>
			              <?php
									}
								?>
	            </select>
	          </div>
	          <div class="form-group col-md-4">
	            <label>Teacher</label>
	            <select class="form-control teacherName" name="subjectTeacher">
	              <?php
								if (!isset($edit))
									echo "<option value='' selected>Select a Teacher..</option>";
								
								$teachers = $wpdb->get_results( "SELECT * FROM ct_teacher" );
								foreach ($teachers as $teacher) {
									echo "<option value='".$teacher->teacherid."'>".$teacher->teacherName."</option>";
								}
								?>
	            </select>
	          </div>
	          <div class="form-group col-md-4">
	            <label>Subject Code</label>
	            <input class="form-control subCode" type="text" name="subCode" value="" >
	          </div>
	        </div>
	        <div class="row">
	          <div class="form-group col-md-3">
	            <label class="mcqtitle"><?= $s3sRedux['mcqtitle'] ?></label>
	            <input class="form-control subMCQ" type="text" name="subMCQ" value="" >
	          </div>
	          <div class="form-group col-md-3">
	            <label class="cqtitle"><?= $s3sRedux['cqtitle'] ?></label>
	            <input class="form-control subCQ" type="text" name="subCQ" value="" >
	          </div>
	          <div class="form-group col-md-3">
	            <label class="prctitle"><?= $s3sRedux['prctitle'] ?></label>
	            <input class="form-control subPect" type="text" name="subPect" value="">
	          </div>
            <div class="form-group col-md-3">
              <label class="catitle"><?= $s3sRedux['catitle'] ?></label>
              <input class="form-control subCa" type="text" name="subCa" value="">
            </div>
            <div class="form-group col-md-3">
              <label>Sub ID
              </label>
              <input class="form-control subid" type="text" name="subid" value="">
            </div>
	        </div>
	        <div class="form-group" style="display:none">
	          <label>Note
	          </label>
	          <textarea class="form-control Note" name="subjectNote"></textarea>
	        </div>
	        <div class="form-group">
	          <button type="button" class="btn btn-default" data-dismiss="modal">Close
	          </button>
	          <button class="btn btn-primary pull-right" type="submit" name="addSubject">Add Subject
	          </button>
	        </div>
      	</form>
    	</div>
  	</div>
	</div>
</div>


<script type="text/javascript">
  (function($) {
  	var $combin = $('#combineMark');

    function subChange(){
      if($("#subType").val() == 0){
        $combin.hide('slow');
        $combin.find('input').prop('checked', false)

      }else{
        $combin.show('slow');
      }
      combineChange();
    }

    function combineChange() {
      if($combin.find('input').is(':checked') && $("#subType").val() == 2){
        $('.connecttedSub').show('slow');
      }else{
        $('.connecttedSub').hide('slow');
      }
    }
    subChange();
    combineChange();

    $("#subType").change(function () {
      subChange();
    });

    $($combin.find('input')).change(function(event) {
      combineChange();
    });


    $(".optionalCk, .sub4thCk").change(function() {
      if($('.optionalCk').is(':checked') || $('.sub4thCk').is(':checked')) {
           var $siteUrl = $('#theSiteURL').text();
      $classdata = { class : $('#stdClass').val(), type : 'hasGroup' };
      $.ajax({
          url: $siteUrl + "/inc/ajaxAction.php",
          method: "POST",
          data: $classdata,
          dataType: "html"
        }).done(function(msg) {
          if (msg === 'true') {
            $('.forGroup').show('slow');
            $( "#forGroup" ).attr('required', 'required');
          } else {
            $('.forGroup').hide('slow');
            $( "#forGroup" ).removeAttr('required');          }
        });
       
      }else{
        $('.forGroup').hide('slow');
        $( "#forGroup" ).removeAttr('required');
      }
    });
    
   $(".religionck").change(function() {
  if ($('.religionck:checked').length > 0) {
    $('#religionContainer').show('slow');
    $("input[name='religionId']").attr('required', 'required');
  } else {
    $('#religionContainer').hide('slow');
    $("input[name='religionId']").removeAttr('required');
  }
});


    /*Delete Button*/
    $('.btnDelete').click(function(event) {
      $('#deleteModal').find('.id').val($(this).data('id'));
      $('#deleteModal').modal('show');
    });

    /*Add Button*/
    $('.addModal').click(function(event) {
      var $modal = $('#addModal');

      $modal.find('.modal-title').text("Add Subject");
      $modal.find('.btn-primary').text("Add Subject");
      $modal.find('.btn-primary').attr('name', 'addSubject');
      $modal.modal('show');
    });



    /*Subject Edit Button*/
    $('.editSubject').click(function(event) {
      var $this = $(this);
      var $tr = $(this).closest('tr');

      /*Get All value*/
      var $subjectName = $tr.find('.subjectName').text();
      var $shortName = $tr.find('.shortName').text();
      var $subMCQ = $tr.find('.subMCQ').text();
      var $subCQ = $tr.find('.subCQ').text();
      var $subPect = $tr.find('.subPect').text();
      var $subCa = $tr.find('.subCa').text();
      var $teacherName = $tr.find('.teacherName').text();
      var $Note = $tr.find('.Note').text();
      var $subpaper = $this.data('subpaper');
      var $combinemark = $this.data('combinemark');
      var $connecttedpaper = $this.data('connecttedpaper');
      var $subjectNote = $this.data('subjectNote');
      var $optional = $this.data('optional');
      var $sub4th = $this.data('sub4th');
      var $assessment = $this.data('assessment');
      var $forGroup = $this.data('forgroup');
      var $subcode = $this.data('subcode');
      var $subid = $this.data('subid');
      var $class = $this.data('class');
      var $teacherid = $this.data('teacherid');
      var $religionId = $this.data('religionid');

      var $modal = $('#addModal');

      $('#combineMark').hide();
      if($subpaper != 0){ $('#combineMark').show(); }

      $('.connecttedSub').hide();
      if($subpaper == 2){
        $('.connecttedSub').show();
        if($connecttedpaper == 0){ $('.connecttedSub select').val(); }
        else{ $('.connecttedSub select').val($connecttedpaper); }
      }

      if($combinemark == 1){ $('#combineMark input').prop('checked', true); }
      else{ $('#combineMark input, .groupCkBox, .allGrpCk').prop('checked', false); }

      if($subpaper != 0 && $combinemark == 1){  }

      $modal.find('.modal-title').text("Edit Subject");
      $modal.find('.btn-primary').text("Update Subject");
      $modal.find('.btn-primary').attr('name', 'updateSubject');

      /*Reset Value*/
      $modal.find('.id, .subjectName, .shortName, .subMCQ, .subCQ, .subPect, .Note, .subjectNote, .subCode, .subid').val('');

      /*Set value*/
      $modal.find('.id').val($this.data('id'));
      $modal.find('.subjectName').val($subjectName);
      $modal.find('.shortName').val($shortName);
      $modal.find('.subMCQ').val($.trim($subMCQ));
      $modal.find('.subCQ').val($subCQ);
      $modal.find('.subPect').val($subPect);
      $modal.find('.subCa').val($subCa);
      $modal.find('.subCode').val($subcode);
      $modal.find('.subid').val($subid);
      $modal.find('.Note').text($Note);
      $modal.find(".className option[value="+$class+"]").prop("selected", true);
      
      $modal.find(".teacherName option[value='"+$teacherid+"']").prop("selected", true);
      


      if($.isNumeric($forGroup)){
        $modal.find('.groupCkBox:checkbox[value='+$forGroup+']').attr('checked', true);
      }else if ($forGroup == 'all') {
        // $modal.find('.allGrpCk').attr('checked', true);
        $('.forGroup .allGrpCk').prop('checked', true);
      }
      else{
        console.log($forGroup);
        $.each( $forGroup, function( index, value ) {
          console.log(value);
          $modal.find('.groupCkBox:checkbox[value='+value+']').attr('checked', true);
        });
      }
      

      $modal.find('.forGroup').hide();

      $modal.find('.optionalCk').attr('checked', false);
      if ($optional == 1) {
        $modal.find('.optionalCk').attr('checked', true);
        $modal.find('.forGroup').show();
      }

      if ($religionId) {
          
        // Check the checkbox
        $modal.find('.religionck').prop('checked', true);
    
        // Show the radio container
        $modal.find('#religionContainer').show();
    
        // Check the correct radio button
        $modal.find('input[name="religionId"][value="' + $religionId + '"]').prop('checked', true);
    } else {
        // If no religionId, make sure checkbox is unchecked and container is hidden
        $modal.find('.religionck').prop('checked', false);
        $modal.find('#religionContainer').hide();
        $modal.find('input[name="religionId"][value=""]').prop('checked', true);

    }


      $modal.find('.sub4thCk').attr('checked', false);
      if ($sub4th == 1) {
        $modal.find('.sub4thCk').attr('checked', true);
        $modal.find('.forGroup').show();
      }
      $modal.find('.assessmentCk').attr('checked', false);
      if ($assessment == 1) {
        $modal.find('.assessmentCk').attr('checked', true);
      }

      $("#subType").val($subpaper);
      if($assessment == 1){
        $(".mcqtitle").html('Attendence');
        $(".cqtitle").html('Hand Writing');
        $(".prctitle").html('Neat & Clean');
        $(".catitle").html('Uniform');
      }else{
        $(".mcqtitle").html('MCQ');
        $(".cqtitle").html('CQ');
        $(".prctitle").html('PR');
        $(".catitle").html('CA');
      }
      $modal.modal('show');
    });

    /*Data Table*/
    $('#datatable').DataTable();


    /*All Group Ck*/
    $('.groupCkBox:checked').length == $('.groupCkBox').length;
    $(".groupCkBox").change(function(){
      if ($('.groupCkBox:checked').length == $('.groupCkBox').length) {
        $('.forGroup .allGrpCk').prop('checked', true);
      }else { 
        $('.forGroup .allGrpCk').prop('checked', false);
      }
    });
    $(".forGroup .allGrpCk").change(function(){
      if($(this).is(':checked')){
        $(".groupCkBox").prop('checked', true);
      }else{
        $(".groupCkBox").prop('checked', false);
      }
    });
  })( jQuery );
</script>