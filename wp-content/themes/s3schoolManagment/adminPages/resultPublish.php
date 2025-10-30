<?php
/**
* Template Name: Admin ResultPublish
*/
global $wpdb;
$convertPercent = 70;

// Check if current user is a teacher and get their restrictions
$current_user = wp_get_current_user();
$isTeacher = (isset($current_user->roles[0]) && $current_user->roles[0] == 'um_teachers');
$teacherRestrictions = null;

if ($isTeacher) {
    global $wpdb;
    // Determine table name (try prefixed first, fallback to ct_teacher)
    $prefixed = $wpdb->prefix . 'ct_teacher';
    $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $prefixed));
    $table = ($exists === $prefixed) ? $prefixed : 'ct_teacher';
    
    $user_id = $current_user->ID;
    $teacher = $wpdb->get_row($wpdb->prepare("SELECT teacherOfClass, teacherOfSection FROM $table WHERE tecUserId = %d", $user_id));
    
    if ($teacher && !empty($teacher->teacherOfClass) && !empty($teacher->teacherOfSection)) {
        $teacherRestrictions = $teacher;
    }
}

// Extract teacher restrictions for use in queries
$teacherOfClass = '';
$teacherOfSection = '';
if ($teacherRestrictions) {
    $teacherOfClass = $teacherRestrictions->teacherOfClass;
    $teacherOfSection = $teacherRestrictions->teacherOfSection;
}

/*=================
	Publish 
=================*/
if (isset($_POST['showInFrontend'])) {
	$class = $_POST['resClass'];
	$exam = $_POST['resExam'];
	$year = $_POST['resYear'];

	$update = $wpdb->update(
		'ct_result',
		array( 'status' => 1, 'can_student_search_result' => 1),
		array( 'resultYear' => "$year",'resClass' => $class,'resExam' => $exam)
	);
	$message = ms3message($update, 'Updated');
}

if (isset($_POST['publisRes'])) {
	$class = $_POST['resClass'];
	$exam = $_POST['resExam'];
	$year = $_POST['resYear'];

	$update = $wpdb->update(
		'ct_result',
		array( 'status' => 1, 'can_student_search_result' => 0),
		array( 'resultYear' => "$year",'resClass' => $class,'resExam' => $exam)
	);

	$users = $wpdb->get_results("SELECT resStudentId,resStdRoll FROM `ct_result`
								WHERE resClass = $class AND resExam = $exam AND resultYear = '$year' GROUP BY resStudentId ORDER BY resStudentId ASC");

	$insert = '';
	foreach ($users as $userkey => $user) {

		$roll 	= $user->resStdRoll;
		$stdnt  = $user->resStudentId;
		$totalPoint = $spAbsent = $spFaild = $totalobtain = $allsubjTotal = $mainSubj = $totalPoin = 0;

		/*==================
			For Combine mark 
		====================*/
		// Below code removed for grading and total mark issue for roll
		// AND resStdRoll = $roll 
		$combines = $wpdb->get_results("SELECT * FROM `ct_result`
			LEFT JOIN ct_subject ON ct_result.resSubject = ct_subject.subjectid
			LEFT JOIN ct_class ON $class = ct_class.classid
			WHERE resClass = $class AND resExam = $exam AND resultYear = '$year'  AND resStudentId = $stdnt AND subCombineMark = 1 GROUP BY resSubject ORDER BY sub4th,subOptinal,subCode ASC");

		$results2 = $wpdb->get_results( "SELECT *, others FROM `ct_result`
			LEFT JOIN ct_subject ON ct_result.resSubject = ct_subject.subjectid
			LEFT JOIN ct_class ON $class = ct_class.classid
			WHERE resClass = $class AND resExam = $exam AND resultYear = '$year'  AND resStudentId = $stdnt AND subCombineMark = 0  GROUP BY resSubject ORDER BY sub4th,subOptinal,subCode ASC" );
		foreach ($combines as $combin) {
			$absentCk = array();
			if($combin->subPaper == 1){
			    $sub4th = $combin->resSub4th;
				$havecon = false;
                if($combin->subCa > 0){
                    $subTot1 = (isnum($combin->subMCQ)+isnum($combin->subCQ)+isnum($combin->subPect))*$convertPercent/100 + $combin->subCa;
    				$allsubjTotal += $subTot1;
    				$obtain = round(((isnum($combin->resCQ)+isnum($combin->resMCQ)+isnum($combin->resPrec))*$convertPercent/100)+isnum($combin->resCa));
                }else{
                    $subTot1 = $combin->subMCQ+$combin->subCQ+$combin->subPect+$combin->subCa;
    				$allsubjTotal += $subTot1;
    				$obtain = $combin->resTotal;
                }
				
				$subTot2 = 0;
				$resCQ = $resMCQ = $resPre = $resCa = $subCQ = $subMCQ = $subPrec = $subCa = 0;
				foreach ($combines as $combin2) {
					if($combin2->connecttedPaper == $combin->resSubject){

						$havecon = true;
						if($combin2->subCa > 0){
						    $obtain += round(((isnum($combin2->resCQ)+isnum($combin2->resMCQ)+isnum($combin2->resPrec))*$convertPercent/100)+isnum($combin2->resCa));
						    $subTot2 = (isnum($combin2->subMCQ)+isnum($combin2->subCQ)+isnum($combin2->subPect))*$convertPercent/100 + $combin2->subCa;
						    $allsubjTotal += $subTot2;
						}else{
						    $obtain += $combin2->resTotal;
    						$subTot2 = isnum($combin2->subMCQ)+isnum($combin2->subCQ)+isnum($combin2->subPect)+isnum($combin2->subCa);
    						$allsubjTotal += $subTot2;
						}
						
						$subName = $combin2->subjectName;
						$subCQ = isnum($combin2->subCQ); 
						$subMCQ = isnum($combin2->subMCQ);
						$subPrec = isnum($combin2->subPect);
						$subCa = isnum($combin2->subCa);
						$reCQ2 = $resCQ = isnum($combin2->resCQ); 
						$reMCQ2 = $resMCQ = isnum($combin2->resMCQ);
						$rePre2 = $resPre = isnum($combin2->resPrec);
						$reCa2 = $resCa = isnum($combin2->resCa);

						$absentCk[] = $combin2->resCQ;
						$absentCk[] = $combin2->resMCQ;
						$absentCk[] = $combin2->resPrec;
				// 		$absentCk[] = $combin2->resCa;
					
						break;
					}
				}

				$resCQ += isnum($combin->resCQ);
				$resMCQ += isnum($combin->resMCQ);
				$resPre += isnum($combin->resPrec);
				$resCa += isnum($combin->resCa);

				$subCQ += isnum($combin->subCQ);
				$subMCQ += isnum($combin->subMCQ);
				$subPrec += isnum($combin->subPect);
				$subCa += isnum($combin->subCa);
				$combTotal = 0;

				$absentCk[] = $combin->resCQ;
				$absentCk[] = $combin->resMCQ;
				$absentCk[] = $combin->resPrec;
				// $absentCk[] = $combin->resCa;
				
				// $totalobtain += $obtain;
				if($sub4th == 1){
				    $subjTotalComb = $subTot1 + $subTot2;
    				$totalobtain += ($obtain > (($subjTotalComb/100)*40) ) ? $obtain-(($subjTotalComb/100)*40) : 0 ;
    			}else{
    				$totalobtain += $obtain;
    			}
			
				if( $havecon ){
				    if($combin->subCa > 0){
					$genRes = genPointWithPercent($subCQ,$subMCQ,$subPrec,$subCa,$resCQ,$resMCQ,$resPre,$resCa,$combin->combineMark);
				    }else{
				        $genRes = genPoint($subCQ,$subMCQ,$subPrec,$subCa,$resCQ,$resMCQ,$resPre,$resCa,$combin->combineMark);
				    }
					$combinegrade = $genRes['grade'];
					$conbinePoint = $genRes['point'];
					if(in_array('a', $absentCk) || in_array('A', $absentCk)){ $combinegrade = 'F'; }

					if($combinegrade == 'F'){ if($combin->resSub4th != 1){ $spFaild++; } }

				}else{
					if($combin->resSub4th != 1){ $spFaild++; }
					$conbinePoint = '0.0';
					$combinegrade = 'F';
				}
				if(in_array('a', $absentCk) || in_array('A', $absentCk)){ $spAbsent++; }
				if($combin->resSub4th == 1){ if($conbinePoint > 2){ $totalPoin += ($conbinePoint-2); }}
				else{ $mainSubj++; $totalPoin += $conbinePoint; }
	
				
			}
			
		}

		/*Combine mark end*/

		foreach ($results2 as $key => $result2) {
			$absentCk = array();
			$absentCk[] = $result2->resCQ;
			$absentCk[] = $result2->resMCQ;
			$absentCk[] = $result2->resPrec;
// 			$absentCk[] = $result2->resCa;

			if(in_array('a', $absentCk) || in_array('A', $absentCk)){ $spAbsent++; }
			$sub4th = $result2->resSub4th;
			$others = $result2->others;
			 if($result2->subCa > 0){
    			$resTotal = round(((isnum($result2->resCQ)+isnum($result2->resMCQ)+isnum($result2->resPrec))*$convertPercent/100)+isnum($result2->resCa));
    			if($others != 1){
    			    $subjTotal =  (isnum($result2->subMCQ) + isnum($result2->subCQ) + isnum($result2->subPect))*$convertPercent/100 + $result2->subCa;
    			}else{
    				$subjTotal = 0;
    			}
    			$genRes = genPointWithPercent($result2->subCQ,$result2->subMCQ,$result2->subPect,$result2->subCa,$result2->resCQ,$result2->resMCQ,$result2->resPrec,$result2->resCa,$result2->combineMark);
			 }else{
			    $resTotal = $result2->resTotal;
    			if($others != 1){
    				$subjTotal =  isnum($result2->subMCQ) + isnum($result2->subCQ) + isnum($result2->subPect)+ isnum($result2->subCa);
    			}else{
    				$subjTotal = 0;
    			}
    			$genRes = genPoint($result2->subCQ,$result2->subMCQ,$result2->subPect,$result2->subCa,$result2->resCQ,$result2->resMCQ,$result2->resPrec,$result2->resCa,$result2->combineMark);
    			
			 }
			$grade = $genRes['grade'];
			$point = $genRes['point'];
			if(in_array('a', $absentCk) || in_array('A', $absentCk)){ $grade = 'F'; }

			if($sub4th == 1){
				$totalobtain += ($resTotal > (($subjTotal/100)*40) ) ? $resTotal-(($subjTotal/100)*40) : 0 ;
			}else{
				$totalobtain += $resTotal;
			}

			$allsubjTotal += $subjTotal;

			if($grade == 'F'){ if($result2->resSub4th != 1){ $spFaild++; } }
			if($result2->resSub4th == 1){ if($point > 2){ $totalPoin += $point-2; }}
			else{ $mainSubj++; $totalPoin += $point; }
		
		}
		
		if ($totalPoin != 0 && $mainSubj != 0) {
			$gettotalPoint = $totalPoin/$mainSubj;
		}else{
			$gettotalPoint = $totalPoin;
		}
		$gettotalPoint = ($gettotalPoint > 5) ? 5 : $gettotalPoint;

		if ($userkey != 0) {
			$insert .= ','; 
		}		
		$insert .= "($stdnt,".number_format((float)$gettotalPoint, 2, '.', '').",'$year',$class,$exam,$totalobtain,$allsubjTotal,$spAbsent,$spFaild)"; 
	}//End User loop



	$wpdb->query("INSERT INTO ct_studentPoint (spStdID, spPoint, spYear, spClass, spExam,spTotalMark,spSubjTotal,spAbsent,spFaild) VALUES $insert");

	/*Genarate Class Possition*/
/*
UPDATE 
ct_cgpa TBL1 
INNER JOIN 
(
    SELECT cgpaid,
        @rowNumber := @rowNumber + 1 AS rowNum
    FROM ct_cgpa , (SELECT @rowNumber := 0) var
    WHERE `cgpaClass` = 44 AND `cgpaYear` = 2019
    ORDER BY `cgpaPoint` DESC
) AS TBL2
ON TBL1.cgpaid = TBL2.cgpaid
SET TBL1.cgpaPosition = TBL2.rowNum;
*/

	$allClsPoss = array();
	$allPosi = $wpdb->get_results("SELECT `spid` FROM `ct_studentPoint`
		LEFT JOIN ct_studentinfo ON infoStdid = spStdID AND infoClass = $class AND infoYear = '$year'
		WHERE `spClass` = $class AND `spExam` = $exam AND `spYear` = '$year' ORDER BY spAbsent,spFaild, spPoint DESC,spTotalMark DESC,infoRoll ASC");
	foreach ($allPosi as $key => $value) {
		$allClsPoss[$key+1] = $value->spid;
	}

	/*Genarate Position*/
	$allRes = $wpdb->get_results("SELECT sectionid FROM `ct_section` WHERE forClass = $class ORDER BY `sectionName`");

	foreach ($allRes as $value) {
		$sec = $value->sectionid;
		$qury11 = "SELECT spid FROM `ct_studentPoint`
			LEFT JOIN ct_studentinfo ON ct_studentinfo.infoStdid = ct_studentPoint.spStdID AND ct_studentinfo.infoClass = $class AND ct_studentinfo.infoYear = '$year'
			WHERE spExam = $exam AND spYear = '$year'";

		if($sec != '')
			$qury11 .= " AND infoSection = $sec";
		$qury11 .= " ORDER BY spAbsent,spFaild,spPoint DESC,spTotalMark DESC,infoRoll ASC";

		$positions = $wpdb->get_results($qury11);
		$posi = 0;
		foreach ($positions as $key => $value) {
			$posi = $key+1;
			$ClassPos = array_search($value->spid, $allClsPoss);
			$update = $wpdb->update(
				'ct_studentPoint',
				array( 'spPosition' => $posi, 'spClassPosition' =>  $ClassPos),
				array( 'spid' => $value->spid)
			);
		}
	}
	
	$message = ms3message($insert, 'Updated');
}

if (isset($_POST['unpublisRes'])) {
	$class = $_POST['resClass'];
	$exam = $_POST['resExam'];
	$year = $_POST['resYear'];

	$update = $wpdb->query( $wpdb->prepare( "UPDATE ct_result SET status = %d,  can_student_search_result = %d WHERE resultYear = %d AND resClass = %d AND resExam = %d", 0,0,$year, $class , $exam	) );

	$users = $wpdb->get_results("SELECT resStudentId,resStdRoll FROM `ct_result`
			WHERE resClass = $class AND resExam = $exam AND resultYear = '$year' GROUP BY resStudentId ORDER BY resStudentId ASC");

	$users = $wpdb->query("DELETE FROM `ct_studentPoint` WHERE spYear = '$year' AND spClass = $class AND spExam = $exam");
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

	<h2>Result Publish</h2><br>
	<div class="row">
		<div class="col-md-6">
			<div class="panel panel-info">
			  <div class="panel-heading"><h3>Unpublished Results</h3></div>
			  <div class="panel-body">
				  <?php 
				  	$years = $wpdb->get_results( "SELECT resultYear FROM `ct_result` WHERE status = 0 GROUP BY resultYear ORDER BY resultYear DESC" );
				  	foreach ($years as $year) {
				  		$resYear = $year->resultYear;
				  		?>
				  			<div class="panel panel-success">
								  <div class="panel-heading"><h4 style="margin:0"><?= $resYear ?></h4></div>
								  <div class="panel-body">
									 	<?php 
									  	if ($isTeacher) {
									  		$classes = $wpdb->get_results( $wpdb->prepare( "SELECT resClass,className FROM `ct_result` LEFT JOIN ct_class ON ct_result.resClass = ct_class.classid WHERE resultYear = %s AND status = 1 AND resClass IN ($teacherOfClass) GROUP BY resClass ORDER BY resClass ASC", $resYear ) );
									  	} else {
									  		$classes = $wpdb->get_results( $wpdb->prepare( "SELECT resClass,className FROM `ct_result` LEFT JOIN ct_class ON ct_result.resClass = ct_class.classid WHERE resultYear = %s AND status = 1 GROUP BY resClass ORDER BY resClass ASC", $resYear ) );
									  	}
									  	foreach ($classes as $class) {
									  		$resClass = $class->resClass;
									  		?>

													<div class="bs-example">
													  <table class="table table-striped table-bordered">
													    <caption><b><?= $class->className ?></b></caption>
													    <thead>
													      <tr>
													        <th>Exam</th>
													        <th>Action</th>
													      </tr>
													    </thead>
													    <tbody>
													      <?php 
															  	$exams = $wpdb->get_results( "SELECT resExam,examName FROM `ct_result` LEFT JOIN ct_exam ON ct_result.resExam = ct_exam.examid WHERE resultYear = '$resYear' AND resClass = $resClass AND status = 0 GROUP BY resExam ORDER BY resExam ASC" );
															  	foreach ($exams as $exam) {
															  		$resExam = $exam->resExam;
															  		?>
															  			
														  				<tr>
														  					<td><?= $exam->examName ?></td>
														  					<td>
														  						<form action="" method="POST">
														  							<input type="hidden" name="resYear" value="<?= $resYear ?>">
														  							<input type="hidden" name="resClass" value="<?= $resClass ?>">
														  							<input type="hidden" name="resExam" value="<?= $resExam ?>">
														  							<button class="btn btn-success btn-sm" type="submit" name="publisRes">Generate</button>
														  						</form>
														  					</td>
														  				</tr>
															  		<?php
															  	}
															  ?>
													    </tbody>
													  </table>
													</div>
									  		<?php
									  	}
									  ?>
								  </div>
								</div>
				  		<?php
				  	}
				  ?>
			  </div>
			</div>
		</div>
		<div class="col-md-6">
			<div class="panel panel-info">
			  <div class="panel-heading"><h3>Published Results</h3></div>
			  <div class="panel-body">
				  <?php 
				  	$years = $wpdb->get_results( "SELECT resultYear FROM `ct_result` WHERE status = 1 GROUP BY resultYear ORDER BY resultYear DESC" );
				  	foreach ($years as $year) {
				  		$resYear = $year->resultYear;
				  		?>
				  			<div class="panel panel-success">
								  <div class="panel-heading"><h4 style="margin:0"><?= $resYear ?></h4></div>
								  <div class="panel-body">
									 	<?php 
									  	if ($isTeacher) {
									  		$classes = $wpdb->get_results( $wpdb->prepare( "SELECT resClass,className FROM `ct_result` LEFT JOIN ct_class ON ct_result.resClass = ct_class.classid WHERE resultYear = %s AND status = 1 AND resClass IN ($teacherOfClass) GROUP BY resClass ORDER BY resClass ASC", $resYear ) );
									  	} else {
									  		$classes = $wpdb->get_results( $wpdb->prepare( "SELECT resClass,className FROM `ct_result` LEFT JOIN ct_class ON ct_result.resClass = ct_class.classid WHERE resultYear = %s AND status = 1 GROUP BY resClass ORDER BY resClass ASC", $resYear ) );
									  	}
									  	foreach ($classes as $class) {
									  		$resClass = $class->resClass;
									  		?>

													<div class="bs-example">
													  <table class="table table-striped table-bordered">
													    <caption><b><?= $class->className ?></b></caption>
													    <thead>
													      <tr>
													        <th>Exam</th>
													        <th>Stutas</th>
													         <th>Action</th>
													      </tr>
													    </thead>
													    <tbody>
													      <?php 
															  	$exams = $wpdb->get_results( "SELECT resExam,examName,can_student_search_result   FROM `ct_result` LEFT JOIN ct_exam ON ct_result.resExam = ct_exam.examid WHERE resultYear = '$resYear' AND resClass = $resClass AND status = 1 GROUP BY resExam ORDER BY resExam ASC" );
															  	foreach ($exams as $exam) {
															  		$resExam = $exam->resExam;
															  		?>
															  			
														  				<tr>
														  					<td><?= $exam->examName ?></td>
														  					<td><?= $exam->can_student_search_result == 1? '<span style="color: green">Published</span>': '<span style="color: red">Generated</span>' ?></td>

														  					<td>
														  						<form action="" method="POST">
														  							<input type="hidden" name="resYear" value="<?= $resYear ?>">
														  							<input type="hidden" name="resClass" value="<?= $resClass ?>">
														  							<input type="hidden" name="resExam" value="<?= $resExam ?>">
														  						    <button class="btn btn-danger btn-sm" type="submit" name="unpublisRes">Unpublish</button>
														  							<button class="btn btn-success btn-sm" type="submit" name="showInFrontend">Publish</button>
														  						</form>
														  					</td>
														  				</tr>
															  		<?php
															  	}
															  ?>
													    </tbody>
													  </table>
													</div>
									  		<?php
									  	}
									  ?>
								  </div>
								</div>
				  		<?php
				  	}
				  ?>
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