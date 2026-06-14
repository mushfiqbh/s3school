<?php
/**
* Template Name: Datewise Fees Information
*/
global $wpdb; global $s3sRedux;

function getClassNameById($id){
	global $wpdb;
	$name_qry = "SELECT className FROM ct_class WHERE classid = $id";
	$name = $wpdb->get_results( $name_qry );
	return @$name[0]->className;
}

function getSectionNameById($id, $class){
	global $wpdb;
	$name_qry = "SELECT sectionName FROM ct_section WHERE sectionid = $id AND forClass = $class";
	$name = $wpdb->get_results( $name_qry );
	return @$name[0]->sectionName;
}
$selectedfromDate = isset($_POST['from-date']) ? $_POST['from-date'] : date('Y-m-d');
$selectedtoDate = isset($_POST['to-date']) ? $_POST['to-date'] : date('Y-m-d');
?>
                                  
<?php if ( ! is_admin() ) { get_header(); ?>
<div class="b-layer-main">

	<div class="">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
<?php } ?>
<style>
	.form-group{
		margin-top: 10px !important;
	}
	.pl-10{
		padding-left: 10px !important
	}
</style>
	<div class="container-fluid maxAdminpages" style="padding-left: 0">

		<h2 class="resmangh2">
		Datewise Fees Information
		</h2>

		<!-- Show Status message -->
  	<?php if(isset($message)){ ms3showMessage($message); } ?>
		<div class="panel panel-info">
			<div class="panel-heading"><h3>Datewise Fees Information</h3></div>
				
			<div class="panel-body">
			  <form action="" method="POST" class="form-inline">
				  <div class="row pl-10">
				  <p style="color:red">*** For Section Wise Report Please select Class, Section and Year ***</p>
				  <div class="form-group">
						  <label>Class</label>
						  <select id='resultClass' class="form-control" name="stdClass">
						  <?php
  
							  $classQuery = $wpdb->get_results( "SELECT classid,className FROM ct_class WHERE classid IN (SELECT infoClass FROM ct_studentinfo GROUP BY infoClass ORDER BY className ASC)" );
							  echo "<option value=''>Select Class</option>";
  
							  foreach ($classQuery as $class) {
							  echo "<option value='".$class->classid."'>".$class->className."</option>";
							  }
						  ?>
						  </select>
					  </div>
  
					  <div class="form-group ">
						  <label>Section</label>
						  <select id="resultSection" class="form-control" name="sec" disabled>
						  <option disabled selected>Select Class First</option>
						  </select>
					  </div>
					  <div class="form-group">
						<label>Year</label>
						<select id='resultYear' class="form-control" name="stdyear" disabled>
						<option disabled selected>Select Class First</option>
						</select>
					</div>
				  	<div class="form-group">
						<label>From Date</label>
						<input id="from-date" type="date" name="from-date" value="<?php echo $selectedfromDate; ?>">

					</div>
				  	<div class="form-group">
						<label>To Date</label>
						<input id="to-date" type="date" name="to-date" value="<?php echo $selectedtoDate; ?>">
						<input class="form-control btn-success" name="datewiseFeesInformation" type="submit" value="Search">

					</div>

					
					
				  </div>
				  			  		
					</form>
		  </div>







		  <?php
		if(isset($_POST['datewiseFeesInformation'])):
			$from_date = $_POST['from-date'];
			$to_date = $_POST['to-date'];
			$class = @$_POST['stdClass'];
			$section = @$_POST['sec'];
			$year = @$_POST['stdyear'];
			$allLists = [];
			
			//  get active collection sub head id
			$subHeadId = $wpdb->get_results("SELECT * FROM ct_sub_head
			WHERE  relation_to = 1 and isHidden is null ORDER BY sub_head_name ASC");
			foreach($subHeadId as $key => $val){
				$allLists[$key]['name'] = $val->sub_head_name;
				
				// if($class!= '' && $section != '' && $year != ''){
				// 	$sum = $wpdb->get_results("SELECT SUM(ct_student_fee_collection_details.fee) AS total FROM ct_student_fee_collection_details
				// 	LEFT JOIN ct_student_fee_collection_info ON ct_student_fee_collection_info.id = ct_student_fee_collection_details.info_id
				// 	WHERE  DATE(ct_student_fee_collection_details.date) >= '$from_date' AND DATE(ct_student_fee_collection_details.date) <= '$to_date' AND ct_student_fee_collection_details.sub_head_id = $val->id AND ct_student_fee_collection_info.year = $year AND ct_student_fee_collection_info.class_id = $class AND ct_student_fee_collection_info.section = $section");
				// }else{
				// 	$sum = $wpdb->get_results("SELECT SUM(fee) AS total FROM ct_student_fee_collection_details
				// 	WHERE  DATE(date) >= '$from_date' AND DATE(date) <= '$to_date' AND sub_head_id = $val->id");
				// }
				$qry = "SELECT SUM(ct_student_fee_collection_details.fee) AS total FROM ct_student_fee_collection_details
				 	LEFT JOIN ct_student_fee_collection_info ON ct_student_fee_collection_info.id = ct_student_fee_collection_details.info_id
				 	WHERE  DATE(ct_student_fee_collection_details.date) >= '$from_date' AND DATE(ct_student_fee_collection_details.date) <= '$to_date' AND ct_student_fee_collection_details.sub_head_id = $val->id";
				if ($section != '') { $qry .= " AND ct_student_fee_collection_info.section = $section"; }
				if ($class != '') { $qry .= " AND ct_student_fee_collection_info.class_id = $class"; }

				if ($year != '') { $qry .= " AND ct_student_fee_collection_info.year = $year"; }

				$sum = $wpdb->get_results( $qry ); 
				
				$allLists[$key]['fee'] = $sum[0]->total;
			}
// echo '<pre>';
// print_r($allLists);exit;

				?>
		<div class="container-fluid maxAdminpages" style="padding-left: 0">
			
			<div class="row">
				<div class="col-md-12">
			  	<button onclick="print('printArea')" class="pull-right btn btn-primary">Print</button>
			  </div>
			  <div class="col-md-12" style="padding-left: 5px;">
			  	<div id="printArea" class="col-md-12 printBG" style="width: 8.27in">
					  <div class="printArea" style="margin: 10px 10px 0 0">
					  	<style type="text/css">
					  		table tr{ page-break-inside: avoid !important; }
					  		table tr a{ text-decoration: none;color: #000; }
					  		@page { size: 297mm 210mm !important; margin: 0 !important; }
					  	</style>

				  		<div style="text-align: center; position: relative;">
				  			<img height="80px" style="position: absolute;left: 10px;top: 10px" src="<?= $s3sRedux['instLogo']['url'] ?>">
		  					<h2 style="margin: 5px 0 5px 0;"><b><?= $s3sRedux['institute_name'] ?></b></h2>
					  		<p style="color:#2b5591; font-size: 14px; margin: 0;"><?= $s3sRedux['institute_address'] ?></p>
					  		<?php 
								if($class!= '' && $section != '' && $year != ''){
							?>
							  	<p style="margin: 0;">Section Wise Fees Information</p>
								<p style="margin: 0;"> <?= getClassNameById($class)?>, Section: <?= getSectionNameById($section,$class)?></p>
							<?php }else{?>
								<p style="margin: 0;">Datewise Fees Information</p>
								<?php if($class!= ''){ echo "<p>Class: ". getClassNameById($class)."</p>";}	?>
								<?php if($section!= ''){ echo "<p>Section: ". getSectionNameById($section,$class)."</p>";}	?>
							<?php }?>
					  		<p style="margin: 0;">From: <?= date('d-m-Y', strtotime($from_date))?> To:  <?= date('d-m-Y', strtotime($to_date))?></p>

				  		</div>
				  		<br>

					  		<table class="table table-bordered" style="width: 100%; text-align: center; border: 1px solid black;">
					  			<tr style="text-align: center; border: 1px solid black; font-size:12px;background: #bda9d1c9;">
					  				<th style=" text-align: center;">NO</th>
					  				<!-- <th style=" text-align: center;">Date</th> -->
									  <?php foreach ($allLists as $key => $val){ ?>
										<th style=" text-align: center;"><?= $allLists[$key]['name']?></th>
									  <?php } ?>
					  				<th>Total</th>
					  			
					  			</tr>
								  <tr style="border: 1px solid black; font-size:12px;">
								  <td>1</td>
									
								  <?php
								 $total = 0;
								  foreach($allLists as $key=>$val){
									$total = $total + $allLists[$key]['fee']; 
									  ?>
								  
									
									<td><?= $allLists[$key]['fee']?></td>
								  
						  		<?php } ?>
								  <td><?= $total?></td>
								  </tr>
								  
					  		</table>
					  		
					  </div>
					</div>
			  </div>
			</div>
			
		</div>

		<?php 
	endif; ?>










  
		
		

	</div>


<?php if ( ! is_admin() ) {  ?>
				</div>
			</div>
		</div>
	</div>
</div>
<?php get_footer(); } ?>

<script type="text/javascript">
	(function($) {
		$catmodal = $('#addCatModal');
		
		$('#addCategoryBtn').click(function(event) {
			$catmodal.find('.modal-title').text('Add Category');
			$catmodal.find('.name').val('');
			$catmodal.find('.addCatSubmit').val('save');
			$catmodal.modal('show');
		});

		$('.editCategory').click(function(event) {
			$catmodal.find('.modal-title').text('Edit Category');
			$tr 	= $(this).closest('tr');

			$catmodal.find('.addCatSubmit').val('update');
			$catmodal.find('.name').val($tr.find('.name').text());
			$catmodal.find('.catId').val($tr.find('.catID').val());
			$catmodal.find("input[value='"+$tr.find('.type').text()+"']").click();
			$catmodal.modal('show');
		});


	})( jQuery );
	function print(divId) {
    var printContents = document.getElementById(divId).innerHTML;
    w = window.open();
    w.document.write(printContents);
    w.document.write('<scr' + 'ipt type="text/javascript">' + 'window.onload = function() { window.print();  };' + '</sc' + 'ript>');
    w.document.close(); // necessary for IE >= 10
    w.focus(); // necessary for IE >= 10
    return true;
  }
</script>

<script type="text/javascript">
  (function($) {
    $('#resultClass').change(function() {
      var $siteUrl = '<?= get_template_directory_uri() ?>';

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

    $('#resultRoll').focusout(function() {
      var $siteUrl = '<?= get_template_directory_uri() ?>';

	  $.ajax({
			url: $siteUrl+"/inc/ajaxAction.php",
			method: "POST",
			data: { class : $('#resultClass').val(), section : $('#resultSection').val(), group : $('#resultGroup').val(), year : $('#resultYear').val(), roll : $('#resultRoll').val(), month: $('#fee-month').val(), type : 'getStudentInfo' },
			dataType: "json"
		}).success(function( data ) {
			// console.log( data)
			// console.log( typeof data)
			if(data.success == 'false'){
				$( "#resultName" ).val( '' );
			}else{	
				Object.entries(data).forEach(([key, val]) => {
					if(key == 'month_list'){
						$("#month_list").val( val );
					}else{
						$("#"+key ).val( val );
					}
					
				});
				// data = JSON.parse(data);			
				getTotal();
				
			}
			
		});

    });
  })( jQuery );


  /*=====================Excel Export*/

  function fnExcelReport(){
    var tab_text="<table border='2px'><tr bgcolor='#87AFC6'>";
    var textRange; var j=0;
    tab = document.getElementById('studentsTbl'); // id of table

    for(j = 0 ; j < tab.rows.length ; j++){     
      tab_text=tab_text+tab.rows[j].innerHTML+"</tr>";
    }

    tab_text=tab_text+"</table>";
    tab_text= tab_text.replace(/<A[^>]*>|<\/A>/g, "");
    
    tab_text= tab_text.replace(/<input[^>]*>|<\/input>/gi, ""); 

    var ua = window.navigator.userAgent;
    var msie = ua.indexOf("MSIE "); 

    if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./)){
      txtArea1.document.open("txt/html","replace");
      txtArea1.document.write(tab_text);
      txtArea1.document.close();
      txtArea1.focus(); 
      sa=txtArea1.document.execCommand("SaveAs",true,"students.xls");
    }  
    else                 //other browser not tested on IE 11
      sa = window.open('data:application/vnd.ms-excel,' + encodeURIComponent(tab_text));  

    return (sa);
  }


  /*=====================PDF Export*/

  function exportPDF() {
    var doc = new jsPDF('l', 'pt', 'a4');
    doc.autoTable({
      html: '#studentsTbl',
      theme: 'grid',
      styles: {fontSize: 8}
    });
    doc.save('students.pdf');
  }


</script>
<script type="text/javascript">
	function getTotal(){
		var arr = document.getElementsByClassName('calculate');

		var total = 0, lateFee = 0, absentFee = 0;

		latefee = document.getElementById('late-fee').value || 0;
		absentfee = document.getElementById('absent-fee').value || 0;
		remissionfee = document.getElementById('remission').value || 0;

		for(var i=0;i<arr.length;i++){
			if(parseInt(arr[i].value))
			total += parseInt(arr[i].value);
		}
		subTotal = parseInt(latefee) + parseInt(absentfee) + total;
		grandtotal = subTotal - parseInt(remissionfee);

		document.getElementById('sub-total').value = subTotal;
		document.getElementById('grand-total').value = grandtotal;
	}

</script>

<?php
// ===============================================================
// FIX 409 CONFLICT - HANDLE AJAX ACTIONS LOCALLY (At End of File)
// ===============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type'])) {

  // Clean output buffer to ensure JSON/HTML response is valid
  while (ob_get_level()) {
    ob_end_clean();
  }

  // ------------------------------------------
  // Get Exams
  // ------------------------------------------
  if ($_POST['type'] == 'getExams') {
    $class = $_POST['class'];
    $exams = $wpdb->get_results("SELECT examid,examName FROM ct_exam WHERE examClass = '$class'");
    if (empty($exams)) {
      echo "<option value=''>No Exam for this Class</option>";
    } else {
      echo "<option value=''>Select An Exam</option>";
    }
    foreach ($exams as $exam) {
      echo "<option value='{$exam->examid}'>{$exam->examName}</option>";
    }
    exit;
  }

  // ------------------------------------------
  // Get Years
  // ------------------------------------------
  elseif ($_POST['type'] == 'getYears') {
    $class = $_POST['class'];
    $years = $wpdb->get_results("SELECT infoYear FROM ct_studentinfo WHERE infoClass = $class GROUP BY infoYear ORDER BY infoYear ASC");
    if (empty($years)) {
      echo "<option value=''>No Student In this class</option>";
    } else {
      echo "<option value=''>Year</option>";
    }
    foreach ($years as $year) {
      echo "<option value='{$year->infoYear}'>{$year->infoYear}</option>";
    }
    exit;
  }

  // ------------------------------------------
  // Get Section
  // ------------------------------------------
  elseif ($_POST['type'] == 'getSection') {
    $class = $_POST['class'];
    $sections_query = "SELECT sectionid,sectionName FROM ct_section WHERE forClass = '$class'";
    
    $sections_query .= " ORDER BY sectionName";
    $sections = $wpdb->get_results($sections_query);

    if (!empty($sections)) {
      echo "<option value=''>Section</option>";
      foreach ($sections as $section) {
        echo "<option value='{$section->sectionid}'>{$section->sectionName}</option>";
      }
    } else {
      echo "<option value=''>No sections available</option>";
    }
    exit;
  }

  // ------------------------------------------
  // Get Groups
  // ------------------------------------------
  elseif ($_POST['type'] == 'getGroupsByClass') {
    $class = $_POST['class'];
    $groups_query = "SELECT DISTINCT ct_group.groupId, ct_group.groupName 
            FROM ct_group 
            INNER JOIN ct_studentinfo ON ct_studentinfo.infoGroup = ct_group.groupId 
            WHERE ct_studentinfo.infoClass = '$class'";
    
    $groups_query .= " ORDER BY ct_group.groupName ASC";
    $groups = $wpdb->get_results($groups_query);

    echo "<option value=''>All Groups</option>";
    foreach ($groups as $group) {
      echo "<option value='{$group->groupId}'>{$group->groupName}</option>";
    }
    exit;
  }

  // ------------------------------------------
  // Get Exam Subjects
  // ------------------------------------------
  elseif ($_POST['type'] == 'getExamSubject') {
    $exam = intval($_POST['exam']);
    $group = isset($_POST['group']) ? $_POST['group'] : '';
    $subjects = [];

    $subs = $wpdb->get_results("SELECT examSubjects FROM ct_exam WHERE examid = $exam");

    if (!empty($subs[0]->examSubjects)) {
      $subs = json_decode($subs[0]->examSubjects, true);
    } else {
      $subs = [];
    }

    if (!empty($subs)) {
      $subs_escaped = array_map('intval', $subs);
      $subjectQuery = "SELECT subjectid,subjectName FROM ct_subject 
                WHERE subjectid IN (" . implode(',', $subs_escaped) . ")";

      if (!empty($group)) {
        $subjectQuery .= " AND (forGroup = 'all' OR forGroup = '$group' OR forGroup LIKE '%\"$group\"%')";
      }

      $subjectQuery .= " ORDER BY subjectName ASC";
      $subjects = $wpdb->get_results($subjectQuery);
    }

    if (empty($subjects)) {
      echo "<option value=''>No subject!</option>";
    } else {
      echo "<option value=''>Select Subject</option>";
      foreach ($subjects as $subject) {
        echo '<option value="' . $subject->subjectid . '">' . $subject->subjectName . '</option>';
      }
    }
    exit;
  }
}

if (isset($_POST['updateAllResult'])) {
  $cq = $_POST['CQ'];
  $mcq = $_POST['MCQ'];
  $prc = $_POST['P'];
  $ca = $_POST['ca'];
  $response = false;
  foreach ($_POST['id'] as $id) {
    $update = $wpdb->update(
      'ct_result',
      array(
        'resCQ'     => $cq[$id],
        'resMCQ'     => $mcq[$id],
        'resPrec'   => $prc[$id],
        'resCa'   => $ca[$id],
        'resTotal'   => isnum($cq[$id]) + isnum($mcq[$id]) + isnum($prc[$id]) + isnum($ca[$id])
      ),
      array('resultId' => $id)
    );
    if ($update) {
      $response = $update;
    }
  }
  if ($response) {
    $message = array('status' => 'success', 'message' => 'Successfully updated');
  } else {
    $message = array('status' => 'faild', 'message' => 'Something wrong please try again');
  }
} ?>

<script type="text/javascript">
  // ==================================
  // HANDLE AJAX ACTIONS LOCALLY
  // ==================================
  (function($) {
    // Use current page as AJAX URL for standalone processing
    var ajaxUrl = '';

    $('#resultClass').change(function() {
      var selectedClass = $(this).val();

      // Fetch Exams
      $.ajax({
        url: ajaxUrl,
        method: "POST",
        data: {
          class: selectedClass,
          type: 'getExams'
        },
        dataType: "html"
      }).done(function(msg) {
        $("#resultExam").html(msg);
        $("#resultExam").prop('disabled', false);
        // Reset dependent dropdowns
        $("#resultSubject").prop('disabled', true).html('<option disabled selected>Select exam First</option>');
      });

      // Fetch Years
      $.ajax({
        url: ajaxUrl,
        method: "POST",
        data: {
          class: selectedClass,
          type: 'getYears'
        },
        dataType: "html"
      }).done(function(msg) {
        $("#resultYear").html(msg);
        $("#resultYear").prop('disabled', false);
      });

      // Fetch Sections
      $.ajax({
        url: ajaxUrl,
        method: "POST",
        data: {
          class: selectedClass,
          type: 'getSection'
        },
        dataType: "html"
      }).done(function(msg) {
        $("#resultSection").html(msg);
        $("#resultSection").prop('disabled', false);
      });

      // Fetch All Groups
      $.ajax({
        url: ajaxUrl,
        method: "POST",
        data: {
          class: selectedClass,
          type: 'getGroupsByClass'
        },
        dataType: "html"
      }).done(function(msg) {
        $("#resultGroup").html(msg);
        $("#resultGroup").prop('disabled', false);
      });
    });

    // Fetch Subjects when Exam Changes
    $('#resultExam').change(function() {
      var selectedExam = $(this).val();
      var selectedGroup = $('#resultGroup').val();

      $.ajax({
        url: ajaxUrl,
        method: "POST",
        data: {
          exam: selectedExam,
          group: selectedGroup,
          type: 'getExamSubject'
        },
        dataType: "html"
      }).done(function(msg) {
        $("#resultSubject").html(msg);
        $("#resultSubject").prop('disabled', false);
      });
    });

    // Fetch Subjects when Group Changes
    $('#resultGroup').change(function() {
      var selectedExam = $('#resultExam').val();
      var selectedGroup = $(this).val();

      if (selectedExam) {
        $.ajax({
          url: ajaxUrl,
          method: "POST",
          data: {
            exam: selectedExam,
            group: selectedGroup,
            type: 'getExamSubject'
          },
          dataType: "html"
        }).done(function(msg) {
          $("#resultSubject").html(msg);
          $("#resultSubject").prop('disabled', false);
        });
      }
    });

    // Interactive validation for result inputs (Client-side only)
    $('.resultInput').keyup(function(event) {
      $this = $(this);
      $val = $this.val();
      $max = $this.data('max');

      if ($val == '' || $val < ($max + 1) || $val == 'A' || $val == 'a') {
        $this.css('border-color', '#ddd');
        $this.removeClass('haserror');
      } else {
        $this.addClass('haserror');
        $this.css('border-color', 'red');
        $('.resultSubmit').prop('disabled', true);
      }

      if ($('.resultInput.haserror').length == 0) {
        $('.resultSubmit').prop('disabled', false);
      }
    });

  })(jQuery);
</script>