<?php
global $wpdb;
if (isset($_POST['tables'])) {
	foreach ($_POST['tables'] as $value) {
		$tbl2 = '';
		$wpdb->query("TRUNCATE TABLE $value");

		if($value == 'ct_student'){ $tbl2 = 'ct_studentinfo'; }
		elseif($value == 'ct_result'){ $tbl2 = 'ct_studentPoint'; }

		if($tbl2 != ''){ $wpdb->query("TRUNCATE TABLE $tbl2"); }
	}
}

if (isset($_POST['deleteAllMedia'])) {
	$wpdb->query("DELETE FROM sm_postmeta WHERE post_id IN (SELECT id FROM sm_posts WHERE post_type = 'attachment')");
	$wpdb->query("DELETE FROM sm_posts WHERE post_type = 'attachment'");
}

if (isset($_POST['downloadBAckup'])) {

  EXPORT_DATABASE();

}


function database_cleanup_dashboard_widgets() {

	wp_add_dashboard_widget(
		'database_cleeanup_widget',
		'Database Cleanup',
		'database_cleeanup_widget_function'
  );	
}
add_action( 'wp_dashboard_setup', 'database_cleanup_dashboard_widgets' );

/**
 * Create the function to output the contents of our Dashboard Widget.
 */
function database_cleeanup_widget_function() {
	?>
	<form action="" method="post">
		<div class="form-group optionalSubDiv">		
			<label class="labelRadio">
				<input type="checkbox" name="tables[]" value="ct_attendance"> Attendance
			</label>
			<label class="labelRadio">
				<input type="checkbox" name="tables[]" value="ct_class"> Class
			</label>
			<label class="labelRadio">
				<input type="checkbox" name="tables[]" value="ct_exam"> Exam
			</label>
			<label class="labelRadio">
				<input type="checkbox" name="tables[]" value="ct_expense"> Expense
			</label>
			<label class="labelRadio">
				<input type="checkbox" name="tables[]" value="ct_group"> Group
			</label>
			<label class="labelRadio">
				<input type="checkbox" name="tables[]" value="ct_result"> Result
			</label>
			<label class="labelRadio">
				<input type="checkbox" name="tables[]" value="ct_revenue"> Revenue
			</label>
			<label class="labelRadio">
				<input type="checkbox" name="tables[]" value="ct_section"> Section
			</label>
			<label class="labelRadio">
				<input type="checkbox" name="tables[]" value="ct_student"> Student
			</label>
			<label class="labelRadio">
				<input type="checkbox" name="tables[]" value="ct_subject"> Subject
			</label>
			<label class="labelRadio">
				<input type="checkbox" name="tables[]" value="	ct_teacher"> Teacher
			</label>
		</div>
		<div class="form-group">
			<button type="submit" class="btn btn-danger" name="clearTable">Clear Tables</button>
		</div>
	</form>
	<hr>
	<h4>Delete All media with post</h4>
	<form action="" method="post">
		<button type="submit" class="btn btn-danger" name="deleteAllMedia">Yes Delete All</button>
	</form>
	<?php
}


function databaseBackupWidget() {

	wp_add_dashboard_widget(
		'database_backupup_widget',
		'Database Backup',
		'DatabaseBackpWidget_function'
  );	
}
add_action( 'wp_dashboard_setup', 'databaseBackupWidget' );

/**
 * Create the function to output the contents of our Dashboard Widget.
 */
function DatabaseBackpWidget_function() {
	?>
	<form action="" method="post">
		Download A full backup of the database <button type="submit" class="btn btn-success" name="downloadBAckup">Download Backup</button>
	</form>
	<?php
}
?>