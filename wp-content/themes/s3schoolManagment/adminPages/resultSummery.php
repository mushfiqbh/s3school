<?php 
/**
* Template Name: Admin Result Summery
*/
global $wpdb,$s3sRedux; 

$considerable_failed_subjects = 4;
?>

<?php if ( ! is_admin() ) { get_header(); ?>
<div class="b-layer-main">

	<div class="">
		<div class="container">
			<div class="row">
				<div class="col-md-12">
<?php } ?>

	<p id="theSiteURL" class="hidden"><?= get_template_directory_uri() ?></p>
	<div class="panel panel-info">
		<div class="panel-heading">
			<h3>
				Result Summary<br>
				<small>Find Out Result Summary</small>
			</h3>
		</div>
		<div class="panel-body">
			<form action="" method="GET" class="form-inline">
				<input type="hidden" name="page" value="result_summery">

				<div class="form-group">
					<label>Class</label>
					<select id='resultClass' class="form-control" name="class" required>
						<?php
								$classQuery = $wpdb->get_results( "SELECT classid,className FROM ct_class WHERE classid IN (SELECT examClass FROM ct_exam GROUP BY examClass ORDER BY className ASC)" );
							echo "<option value=''>Select Class</option>";

							foreach ($classQuery as $class) {
								echo "<option value='".$class->classid."'>".$class->className."</option>";
							}
						?>
					</select>
				</div>

				<div class="form-group ">
					<label>Exam</label>
					<select id="resultExam" class="form-control" name="exam" required disabled>
						<option disabled selected>Select Class First</option>
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
					<select id='resultYear' class="form-control" name="syear" required disabled>
						<option disabled selected>Select Class First</option>
					</select>
				</div>


				<div class="form-group">
					<input class="form-control btn btn btn-primary" type="submit" name="" value="Go">
				</div>
			</form>
		</div>

	</div>
	<script type="text/javascript">
	(function($) {
	    function download($divid) {
	    var HTML_Width = $("#printArea").width();
	    var HTML_Height = $("#printArea").height();
	    var top_left_margin = 15;
	    var PDF_Width = HTML_Width + (top_left_margin * 2);
	    var PDF_Height = (PDF_Width * 1.5) + (top_left_margin * 2);
	    var canvas_image_width = HTML_Width;
	    var canvas_image_height = HTML_Height;

	    var totalPDFPages = Math.ceil(HTML_Height / PDF_Height) - 1;

	    html2canvas($("#printArea")[0]).then(function (canvas) {
	        var imgData = canvas.toDataURL("image/jpeg", 1.0);
	        var pdf = new jsPDF('p', 'pt', [PDF_Width, PDF_Height]);
	        pdf.addImage(imgData, 'JPG', top_left_margin, top_left_margin, canvas_image_width, canvas_image_height);
	        
	        pdf.save("result_summery.pdf");
	    });
		}
		
		$(document).on('click', '#downloadBtn', function() {
        download('printArea');
    });
	})( jQuery );
	</script>
	

	<?php

	if(isset($_GET['exam'])){
		$exam 	= $_GET['exam']; 
		$year 	= $_GET['syear']; 
		$class 	= $_GET['class'];
		$sec 		= isset($_GET['sec']) ? $_GET['sec'] : '';

		$querySecnem = $querySec = $queryGrpnem = $queryGrp = '';
		if ($sec != '') {
			$querySecnem = ',sectionName';
			$querySec = "LEFT JOIN ct_section ON sectionid = $sec";
		}

		$info = $wpdb->get_results("SELECT className,combineMark,examName $querySecnem FROM ct_class
			LEFT JOIN ct_exam ON examid = $exam
			$querySec
			WHERE classid = $class" );
			$info = $info[0];
		?>
		<div class="container-fluid maxAdminpages">
			
			<div class="row">
				<div class="col-md-12">
			  	<button id="downloadBtn" class="pull-right btn btn-primary">Download</button>
			  </div>
			  <div class="col-md-12">
			  	
			  	<div id="printArea" class="col-md-12 printBG" style="width: 8.27in">
					  <div class="printArea">
					  	<style type="text/css">
					  		body * {print-color-adjust: exact; -webkit-print-color-adjust: exact !important;}
					  		@page  { size: auto; margin: 0 20px !important;} 
					  		table { page-break-inside: avoid !important;width: 100%; }
					  		table tr,table th{ text-align: center; }
					  	</style>

					  	<link rel="stylesheet" href="<?= get_template_directory_uri() ?>/css/tabulationSheet.css" />

				  		<div style="text-align: center; position: relative;">
				  			<img height="80px" style="position: absolute;left: 10px;top: 10px" src="<?= $s3sRedux['instLogo']['url'] ?>">
		  					<h2 style="margin: 5px 0 5px 0;font-size: 23px;"><b><?= $s3sRedux['institute_name'] ?></b></h2>
					  		<p style="color:#2b5591; font-size: 14px; margin: 0;"><?= $s3sRedux['institute_address'] ?></p>
					  		<h4 style="margin: 5px;">Result Summary</h4>
					  		<h3 style="margin: 5px;"><?= $info->examName ?></h3>
				  		</div>

				  		<table>
				  			<tbody>
				  				<tr style="background:#4472C4;print-color-adjust: exact; -webkit-print-color-adjust: exact;color: #fff">
					  				<td><b>Class:</b> <?= $info->className ?></td>
					  				<td><b>Section:</b> <?= @$info->sectionName ?></td>
					  				<td><b>Group:</b> <?= @$info->groupName ?></td>
					  			</tr>
					  		</tbody>
					  	</table>
					  	
					  	<h4>Result Summary</h4>
					  	<table>
					  		<thead>
					  			<tr>
					  				<th>SL No</th>
					  				<th>Total Student</th>
					  				<th>Pass</th>
					  				<th>Considered</th>
					  				<th>Fail</th>
					  				<th>A+</th>
					  				<th>A</th>
					  				<th>A-</th>
					  				<th>B</th>
					  				<th>C</th>
					  				<th>D</th>
					  				<th>F</th>
					  			</tr>
					  		</thead>
					  		<tbody>
					  			<?php
					  				$qry = "SELECT SUM(IF(`spFaild` = 0, 1, 0)) AS pas,
                                        SUM(IF(`spFaild` > 0 AND `spFaild` <= $considerable_failed_subjects, 1, 0)) AS considered,
                                        SUM(IF(`spFaild` > $considerable_failed_subjects, 1, 0)) AS fail,
                                        SUM(IF(`spPoint` >= 5 AND `spFaild` = 0, 1, 0)) AS aplus,
                                        SUM(IF(`spPoint` >= 4 AND `spPoint` < 5 AND `spFaild` = 0, 1, 0)) AS a,
                                        SUM(IF(`spPoint` >= 3.5 AND `spPoint` < 4 AND `spFaild` = 0, 1, 0)) AS aminus,
                                        SUM(IF(`spPoint` >= 3 AND `spPoint` < 3.5 AND `spFaild` = 0, 1, 0)) AS b,
                                        SUM(IF(`spPoint` >= 2 AND `spPoint` < 3 AND `spFaild` = 0, 1, 0)) AS c,
                                        SUM(IF(`spPoint` >= 1 AND `spPoint` < 2 AND `spFaild` = 0, 1, 0)) AS d
                                  FROM ct_studentPoint
                                  LEFT JOIN ct_studentinfo ON ct_studentinfo.infoStdid = ct_studentPoint.spStdID AND ct_studentinfo.infoClass = $class AND ct_studentinfo.infoyear = '$year'
                                  WHERE spYear = '$year' AND spClass = $class AND spExam = $exam";
                        
                        if ($sec != '') { $qry .= " AND infoSection = $sec"; }
                        
                        $summerydata = $wpdb->get_results($qry)[0];
                        
                        $totalstd = $summerydata->pas + $summerydata->considered + $summerydata->fail;

					  			?>
					  			<tr>
					  				<td>1</td>
					  				<td><?= $totalstd ?></td>
					  				<td>
					  					<div><?= $summerydata->pas ?></div>
					  					<?= $totalstd != 0 ? number_format((float)($summerydata->pas/$totalstd)*100, 2, '.', '') : 0; ?>%
					  				</td>
					  				<td>
					  					<div><?= $summerydata->considered ?></div>
					  					<?= $totalstd != 0 ? number_format((float)($summerydata->considered/$totalstd)*100, 2, '.', '') : 0; ?>%
					  				</td>
					  				<td>
					  					<div><?= $summerydata->fail ?></div>
				  						<?= $totalstd != 0 ? number_format((float)($summerydata->fail/$totalstd)*100, 2, '.', '') : 0; ?>%
				  					</td>
					  				<td><?= $summerydata->aplus ?></td>
					  				<td><?= $summerydata->a ?></td>
					  				<td><?= $summerydata->aminus ?></td>
					  				<td><?= $summerydata->b ?></td>
					  				<td><?= $summerydata->c ?></td>
					  				<td><?= $summerydata->d ?></td>
					  				<td><?= $summerydata->fail ?></td>
					  			</tr>
					  		</tbody>
					  	</table>
					  	<br>
							<style type="text/css">#passfailchart a,#resSummery a,#failfailchart a,#failSummery a {color: transparent !important;display: none !important;}</style>
					  	<table style="width: 100%">
					  		<tr>
					  			<td>
					  				<div id="passfailchart" style="height: 300px; width: 360px;"></div>
					  			</td>
					  			<td>
					  				<div id="resSummery" style="height: 300px; width: 360px;"></div>
					  			</td>
					  		</tr>
					  	</table>
							
							<br>
					  	<h4 >Failed Summary</h4>
					  	<?php



			  				$qry = "SELECT `spFaild`,COUNT(*) AS sub,  GROUP_CONCAT(ct_studentinfo.infoRoll ORDER BY ct_studentinfo.infoRoll ASC) AS infoRolls FROM ct_studentPoint
									LEFT JOIN ct_studentinfo ON ct_studentinfo.infoStdid = ct_studentPoint.spStdID AND ct_studentinfo.infoClass = $class AND ct_studentinfo.infoyear = '$year'
									WHERE spYear = '$year' AND spClass = $class AND spExam = $exam AND spFaild > $considerable_failed_subjects";
			  				if ($sec != '') { $qry .= " AND infoSection = $sec"; }
			  				$qry .= " GROUP BY `spFaild`";
			  				$failSum = $wpdb->get_results($qry);
			  			// 	echo '<pre>';print_r($failSum);exit;
			  			?>
					  	<table>
					  		<thead>
					  			<tr>
					  				<th>Total</th>
					  				<?php foreach ($failSum as $value) {
					  					echo "<th>".$value->spFaild." Sub</th>";
					  				} ?>
					  			</tr>
					  		</thead>
					  		<tbody>
					  			<tr>
					  				<td><?= $summerydata->fail ?></td>
					  				<?php foreach ($failSum as $value) {
					  					echo "<td>".$value->sub."</td>";
					  				} ?>
					  			</tr>
					  		</tbody>
					  	</table>
					  	<br>
					  	<table style="width: 100%">
					  		<tr>
					  			<td>
					  				<div id="failfailchart" style="height: 300px; width: 360px;"></div>
					  			</td>
					  			<td>
					  				<div id="failSummery" style="height: 300px; width: 360px;"></div>
					  			</td>
					  		</tr>
					  	</table>
					  	<br><br><br>
					  	<span style="font-size: 10px;color: #888;">Generated &nbsp;by Bornomala, Developed &nbsp;by MS3 Technology &nbsp;BD, Urmi-43, Shibgonj, Sylhet. Email: bornomala.ems@gmail.com</span>
					  </div>
					</div>

			  </div>

			  <div class="col-md-12">
			  	<br>
			  	<div class="col-md-12">
			  		<button onclick="print('printArea2')" class="pull-right btn btn-primary">Print</button>
			  	</div>
			  	<div id="printArea2" class="col-md-12 printBG" style="width: 8.27in">
					  <div class="printArea">
					  	<style type="text/css">
					  		body * {print-color-adjust: exact; -webkit-print-color-adjust: exact !important;}
					  		@page  { size: auto; margin: 0 20px !important;} 
					  		table { page-break-inside: avoid !important;width: 100%; }
					  		table tr,table th{ text-align: center; }
					  	</style>

					  	<link rel="stylesheet" href="<?= get_template_directory_uri() ?>/css/tabulationSheet.css" />

				  		<div style="text-align: center; position: relative;">
				  			<img height="80px" style="position: absolute;left: 10px;top: 10px" src="<?= $s3sRedux['instLogo']['url'] ?>">
		  					<h2 style="margin: 5px 0 5px 0;font-size: 23px;"><b><?= $s3sRedux['institute_name'] ?></b></h2>
					  		<p style="color:#2b5591; font-size: 14px; margin: 0;"><?= $s3sRedux['institute_address'] ?></p>
					  		<h4 style="margin: 5px;">Result Summary</h4>
					  		<h3 style="margin: 5px;"><?= $info->examName ?></h3>
				  		</div>

				  		<table>
				  			<tbody>
				  				<tr style="background:#4472C4;print-color-adjust: exact; -webkit-print-color-adjust: exact;color: #fff">
					  				<td><b>Class:</b> <?= $info->className ?></td>
					  				<td><b>Section:</b> <?= @$info->sectionName ?></td>
					  				<td><b>Group:</b> <?= @$info->groupName ?></td>
					  			</tr>
					  		</tbody>
					  	</table>
		
					  	<h4>Subjects based Result Summary</h4>
					  	<table>
					  		<thead>
						  		<tr>
						  			<th>SL No</th>
						  			<th>Subject Name</th>
						  			<th>A+</th>
						  			<th>A</th>
						  			<th>A-</th>
						  			<th>B</th>
						  			<th>C</th>
						  			<th>D</th>
						  			<th>F</th>
						  			<th>Total</th>
						  		</tr>
						  	</thead>
						  	<tbody>
						  		<?php
						 				$secQuer = ''; 
										if($sec != '')
											$secQuer = "AND `resSec` = $sec";

					  				$subjects = $wpdb->get_results("SELECT subjectid,subjectName FROM `ct_subject` WHERE subjectid IN (SELECT `resSubject` FROM `ct_result` WHERE `resClass` = $class AND `resExam`= $exam AND `resultYear` = '$year' $secQuer GROUP BY `resSubject`) ORDER BY subjectName");
										foreach ($subjects as $key => $subject) {
											$subid =$subject->subjectid;
											if($info->combineMark == 1){
												$subSummerys = $wpdb->get_results("SELECT
													@sub := (subMCQ+subCQ+subPect)/100,
													@p80 := @sub*80,
													@p70 := @sub*70,
													@p60 := @sub*60,
													@p50 := @sub*50,
													@p40 := @sub*40,
													@p33 := @sub*33,
													COUNT(*) AS total,
												  SUM(IF(`resTotal` >= @p80 AND `resTotal` != 0, 1, 0)) AS aplus,
												  SUM(IF(`resTotal` >= @p70 AND `resTotal` < @p80, 1, 0)) AS a,
												  SUM(IF(`resTotal` >= @p60 AND `resTotal` < @p70, 1, 0)) AS aminus,
												  SUM(IF(`resTotal` >= @p50	AND `resTotal` < @p60, 1, 0)) AS b,
												  SUM(IF(`resTotal` >= @p40 AND `resTotal` < @p50, 1, 0)) AS c,
												  SUM(IF(`resTotal` >= @p33	AND `resTotal` < @p40, 1, 0)) AS d,
												  SUM(IF(`resTotal` < @p33, 1, 0)) AS fail
												FROM ct_result
												LEFT JOIN ct_subject ON subjectid = $subid
												WHERE `resultYear` = '$year' AND `resClass` = $class AND `resExam` = $exam AND resSubject = $subid $secQuer")[0];
											}else{
												$and = "AND resCQ >= ((subCQ/100)*33) AND resMCQ >= ((subMCQ/100)*33) AND resPrec >= ((subPect/100)*33)";
												$subSummerys = $wpdb->get_results("SELECT
													@sub := (subMCQ+subCQ+subPect)/100,
													@p80 := @sub*80,
													@p70 := @sub*70,
													@p60 := @sub*60,
													@p50 := @sub*50,
													@p40 := @sub*40,
													@p33 := @sub*33,
													COUNT(*) AS total,
												  SUM(IF(`resTotal` >= @p80 AND `resTotal` != 0 $and, 1, 0)) AS aplus,
												  SUM(IF(`resTotal` >= @p70 AND `resTotal` < @p80 $and, 1, 0)) AS a,
												  SUM(IF(`resTotal` >= @p60 AND `resTotal` < @p70 $and, 1, 0)) AS aminus,
												  SUM(IF(`resTotal` >= @p50	AND `resTotal` < @p60 $and, 1, 0)) AS b,
												  SUM(IF(`resTotal` >= @p40 AND `resTotal` < @p50 $and, 1, 0)) AS c,
												  SUM(IF(`resTotal` >= @p33	AND `resTotal` < @p40 $and, 1, 0)) AS d,
												  SUM(IF(resCQ < ((subCQ/100)*33) OR resMCQ < ((subMCQ/100)*33) OR resPrec < ((subPect/100)*33), 1, 0)) AS fail
												FROM ct_result
												LEFT JOIN ct_subject ON subjectid = $subid
												WHERE `resultYear` = '$year' AND `resClass` = $class AND `resExam` = $exam AND resSubject = $subid $secQuer")[0];
		
											}
										
							  			?>
									  		<tr>
									  			<td><?= $key+1 ?></td>
									  			<td><?= $subject->subjectName ?> </td>
									  			<td><?= $subSummerys->aplus ?></td>
									  			<td><?= $subSummerys->a ?></td>
									  			<td><?= $subSummerys->aminus ?></td>
									  			<td><?= $subSummerys->b ?></td>
									  			<td><?= $subSummerys->c ?></td>
									  			<td><?= $subSummerys->d ?></td>
									  			<td><?= $subSummerys->fail ?></td>
									  			<td><?= $subSummerys->total ?></td>
									  		</tr>
									  	<?php 
									  } ?>
						  	</tbody>

					  	</table>
					  </div>
					</div>
				</div>
			  <div class="col-md-12">
			  	<br
			  	<?php
			  			    $qry = "
                                SELECT 
                                    GROUP_CONCAT(ct_studentinfo.infoRoll ORDER BY ct_studentinfo.infoRoll ASC) AS infoRolls
                                FROM 
                                    ct_studentPoint
                                LEFT JOIN 
                                    ct_studentinfo 
                                    ON ct_studentinfo.infoStdid = ct_studentPoint.spStdID 
                                    AND ct_studentinfo.infoClass = $class 
                                    AND ct_studentinfo.infoyear = '$year'
                                LEFT JOIN 
                                    ct_section 
                                    ON ct_section.sectionid = ct_studentinfo.infoSection
                                WHERE 
                                    spYear = '$year' 
                                    AND spClass = $class 
                                    AND spExam = $exam 
                                    AND spFaild = 0
                            ";
                            
                            if ($sec != '') {
                                $qry .= " AND ct_studentinfo.infoSection = $sec";
                            }
                            
                            $qry .= "
                                GROUP BY ct_studentinfo.infoSection
                            ";
                            
                            $passlist = $wpdb->get_results($qry);
                            
                            $qry = "
                                SELECT 
                                    GROUP_CONCAT(ct_studentinfo.infoRoll ORDER BY ct_studentinfo.infoRoll ASC) AS infoRolls
                                FROM 
                                    ct_studentPoint
                                LEFT JOIN 
                                    ct_studentinfo 
                                    ON ct_studentinfo.infoStdid = ct_studentPoint.spStdID 
                                    AND ct_studentinfo.infoClass = $class 
                                    AND ct_studentinfo.infoyear = '$year'
                                LEFT JOIN 
                                    ct_section 
                                    ON ct_section.sectionid = ct_studentinfo.infoSection
                                WHERE 
                                    spYear = '$year' 
                                    AND spClass = $class 
                                    AND spExam = $exam 
                                    AND spFaild >= 3
                            ";
                            
                            if ($sec != '') {
                                $qry .= " AND ct_studentinfo.infoSection = $sec";
                            }
                            
                            $qry .= "
                                GROUP BY ct_studentinfo.infoSection
                            ";
                            
                            $faillist = $wpdb->get_results($qry);
                            
                            $qry = "
                                SELECT 
                                    GROUP_CONCAT(ct_studentinfo.infoRoll ORDER BY ct_studentinfo.infoRoll ASC) AS infoRolls
                                FROM 
                                    ct_studentPoint
                                LEFT JOIN 
                                    ct_studentinfo 
                                    ON ct_studentinfo.infoStdid = ct_studentPoint.spStdID 
                                    AND ct_studentinfo.infoClass = $class 
                                    AND ct_studentinfo.infoyear = '$year'
                                LEFT JOIN 
                                    ct_section 
                                    ON ct_section.sectionid = ct_studentinfo.infoSection
                                WHERE 
                                    spYear = '$year' 
                                    AND spClass = $class 
                                    AND spExam = $exam 
                                    AND spFaild > 0 AND spFaild <= $considerable_failed_subjects AND spPoint != '0.00'
                            ";
                            
                            if ($sec != '') {
                                $qry .= " AND ct_studentinfo.infoSection = $sec";
                            }
                            
                            $qry .= "
                                GROUP BY ct_studentinfo.infoSection
                            ";
                            
                            $consideredlist = $wpdb->get_results($qry);
                            
                            // subject wise faillist
                            	$qry = "SELECT `spFaild`,COUNT(*) AS sub,  GROUP_CONCAT(ct_studentinfo.infoRoll ORDER BY ct_studentinfo.infoRoll ASC) AS infoRolls FROM ct_studentPoint
									LEFT JOIN ct_studentinfo ON ct_studentinfo.infoStdid = ct_studentPoint.spStdID AND ct_studentinfo.infoClass = $class AND ct_studentinfo.infoyear = '$year'
									WHERE spYear = '$year' AND spClass = $class AND spExam = $exam AND spFaild !=0";
			  				if ($sec != '') { $qry .= " AND infoSection = $sec"; }
			  				$qry .= " GROUP BY `spFaild`";
			  				$subjectWiseFailed = $wpdb->get_results($qry);
			  			?>
			  	<div class="col-md-12">
			  		<button onclick="print('printArea3')" class="pull-right btn btn-primary">Print</button>
			  	</div>
			  	<div id="printArea3" class="col-md-12 printBG" style="width: 8.27in">
					  <div class="printArea">
					  	<style type="text/css">
					  		body * {print-color-adjust: exact; -webkit-print-color-adjust: exact !important;}
					  		@page  { size: auto; margin: 0 20px !important;} 
					  		table { page-break-inside: avoid !important;width: 100%; }
					  		table tr,table th{ text-align: center; }
					  	</style>

					  	<link rel="stylesheet" href="<?= get_template_directory_uri() ?>/css/tabulationSheet.css" />

				  		<div style="text-align: center; position: relative;">
				  			<img height="80px" style="position: absolute;left: 10px;top: 10px" src="<?= $s3sRedux['instLogo']['url'] ?>">
		  					<h2 style="margin: 5px 0 5px 0;font-size: 23px;"><b><?= $s3sRedux['institute_name'] ?></b></h2>
					  		<p style="color:#2b5591; font-size: 14px; margin: 0;"><?= $s3sRedux['institute_address'] ?></p>
					  		<h4 style="margin: 5px;">Passed & FailedList</h4>
					  		<h3 style="margin: 5px;"><?= $info->examName ?></h3>
				  		</div>

				  		<table>
				  			<tbody>
				  				<tr style="background:#4472C4;print-color-adjust: exact; -webkit-print-color-adjust: exact;color: #fff">
					  				<td><b>Class:</b> <?= $info->className ?></td>
					  				<td><b>Section:</b> <?= @$info->sectionName ?></td>
					  				<td><b>Group:</b> <?= @$info->groupName ?></td>
					  			</tr>
					  		</tbody>
					  	</table>
		
					  	<h4>Passed List:</h4>
					  	<span><?= str_replace(',', ', <wbr>',$passlist[0]->infoRolls)?></span><br>
					  	<h4>Considered List:</h4>
					  	<span><?= str_replace(',', ', <wbr>',$consideredlist[0]->infoRolls)?></span><br>
					  	<h4>Failed List:</h4>
					  	<span><?= str_replace(',', ', <wbr>',$faillist[0]->infoRolls)?></span><br>
					  	
					  	<?php if (!empty($subjectWiseFailed)) : ?>
					  	<h4>Subject Wise Failed List:</h4>
                        <table border="1" cellpadding="10" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>No of Subjects</th>
                                    <th>Total</th>
                                    <th>Rolls</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subjectWiseFailed as $row) : ?>
									<tr>
                                        <td><?php echo htmlspecialchars($row->spFaild). ' Sub'; ?></td>
                                        <td><?php echo htmlspecialchars($row->sub); ?></td>
                                        <td><?=  str_replace(',', ', <wbr>',$row->infoRolls); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <p>No data found.</p>
                    <?php endif; ?>


					  	
					  </div>
					</div>
				</div>
			</div>
			
		</div>

		<?php 
	} ?>

<?php if ( ! is_admin() ) { ?>
				</div>
			</div>
		</div>
	</div>
</div>
<?php get_footer(); } ?>
<script src="https://canvasjs.com/assets/script/jquery.canvasjs.min.js"></script>

<script type="text/javascript">
// 	CanvasJS.addColorSet("redgreen", ["#f00","#0f0","#00f"]);
	var chart1 = new CanvasJS.Chart("passfailchart",{
		colorSet: "redgreen",
		title: {
			text: "Pass / Fail"
		},
		animationEnabled: true,
		data: [{
			type: "pie",
			startAngle: -90,
			toolTipContent: "<b>{label}</b>: {y}",
			showInLegend: "true",
			legendText: "{label}",
			indexLabelFontSize: 14,
			indexLabel: "{label} - {y}",
			dataPoints: [
			{ y: <?= $summerydata->pas ?>, label: "Pass <?= $totalstd != 0 ? number_format((float)($summerydata->pas/$totalstd)*100, 2, '.', '') : 0; ?>%", indexLabelLineColor: "#0f0" },
			{ y: <?= $summerydata->fail ?>, label: "Fail <?= $totalstd != 0 ? number_format((float)($summerydata->fail/$totalstd)*100, 2, '.', '') : 0; ?>%", indexLabelLineColor: "#f00" },
			{ y: <?= $summerydata->considered ?>, label: "Considered <?= $totalstd != 0 ? number_format((float)($summerydata->considered/$totalstd)*100, 2, '.', '') : 0; ?>%", indexLabelLineColor: "#00f" }
			]
		}]
	});
	chart1.render();

	var chart2 = new CanvasJS.Chart("resSummery",{
		animationEnabled: true,
		title: {
			text: "Summary"
		},
		data: [{
			type: "column",
			yValueFormatString: "###",
			dataPoints: [
			{ label: "A+", 	y: <?= $summerydata->aplus ?> },	
			{ label: "A", 	y: <?= $summerydata->a ?> },
			{ label: "A-", 	y: <?= $summerydata->aminus ?> },	
			{ label: "B", 	y: <?= $summerydata->b ?> },
			{ label: "C", 	y: <?= $summerydata->c ?> },
			{ label: "D", 	y: <?= $summerydata->d ?> },
			{ label: "F", 	y: <?= $summerydata->fail ?> }
			]
		}]
	});
	chart2.render();


	var chart3 = new CanvasJS.Chart("failfailchart",{
		title: {
			text: "Summary"
		},
		animationEnabled: true,
		data: [{
			type: "pie",
			startAngle: -90,
			toolTipContent: "<b>{label}</b>: {y}",
			showInLegend: "true",
			legendText: "{label}",
			indexLabelFontSize: 13,
			indexLabel: "{label}:{y}",
			dataPoints: [
				<?php foreach ($failSum as $value) {
					$fail = $value->sub;
					$sub = $value->spFaild;
					echo "{ y: $fail, label: '$sub-Sub' },";
				} ?>
			]
		}]
	});
	chart3.render();
//".$value->sub."
	var chart4 = new CanvasJS.Chart("failSummery",{
		animationEnabled: true,
		title: {
			text: "Summary"
		},
		data: [{
			type: "column",
			yValueFormatString: "###",
			dataPoints: [
				<?php 
					foreach ($failSum as $value) {
						$fail = $value->sub;
						$sub = $value->spFaild;
						echo "{ label: '$sub Sub', 	y: $fail },";
					}
				?>
			]
		}]
	});
	chart4.render();
</script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.5.3/jspdf.min.js"></script>
<script type="text/javascript" src="https://html2canvas.hertzen.com/dist/html2canvas.js"></script>
<script type="text/javascript">
	(function($) {
		

		$('#resultClass').change(function() {
      var $siteUrl = $('#theSiteURL').text();
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