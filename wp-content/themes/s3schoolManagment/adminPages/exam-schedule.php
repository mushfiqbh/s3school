<?php
/**
 * Template Name: Admin ExamSchedule
*/
get_header();

global $wpdb;
global $s3sRedux;

get_header();
?>
<div class="b-layer-main">
	<div class="container">
		<div class="row">
			<div class="col-md-12">
				<p id="theSiteURL" class="hidden"><?= get_template_directory_uri() ?></p>
				<div class="panel panel-info">
					<div class="panel-heading">
						<h3>Exam Schedule<br><small>Create and manage exam schedules</small></h3>
					</div>
					<div class="panel-body" style="padding-top:0;">
						<form id="scheduleFilter" class="form-inline" onsubmit="return false;">
							<div class="form-group">
								<label>Class</label>
								<select id="schedClass" class="form-control" required>
									<option value="">Select Class</option>
									<?php
									$classes = $wpdb->get_results("SELECT classid, className FROM ct_class ORDER BY className ASC");
									foreach ($classes as $c) {
										echo "<option value='" . esc_attr($c->classid) . "'>" . esc_html($c->className) . "</option>";
									}
									?>
								</select>
							</div>

							<div class="form-group">
								<label>Exam</label>
								<select id="schedExam" class="form-control" required disabled>
									<option>Select Class First</option>
								</select>
							</div>

							<div class="form-group">
								<label>Year</label>
								<select id="schedYear" class="form-control" required disabled>
									<option>Select Class First</option>
								</select>
							</div>

							<div class="form-group">
								<button id="loadSubjects" class="btn btn-primary" type="button" disabled>Load Subjects</button>
							</div>
						</form>

						<hr>

						<div id="scheduleArea" style="display:none;">
							<form id="scheduleForm">
								<input type="hidden" id="schClassId" name="classid">
								<input type="hidden" id="schExamId" name="examid">
								<input type="hidden" id="schYear" name="year">

								<div id="subjectsList"></div>

								<div style="margin-top:15px;">
									<button id="saveSchedule" class="btn btn-success" type="button">Save Schedule</button>
									<span id="saveStatus" style="margin-left:15px;"></span>
								</div>
							</form>
						</div>

					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
(function($){
	var siteUrl = $('#theSiteURL').text() || '';

	$('#schedClass').change(function(){
		var cls = $(this).val();
		if(!cls) return;
		// get exams
		$.post(siteUrl + '/inc/ajaxAction.php', { type: 'getExams', class: cls }, function(html){
			$('#schedExam').html(html).prop('disabled', false);
		}, 'html');

		// get years
		$.post(siteUrl + '/inc/ajaxAction.php', { type: 'getYears', class: cls }, function(html){
			$('#schedYear').html(html).prop('disabled', false);
		}, 'html');

		$('#loadSubjects').prop('disabled', true);
	});

	$('#schedExam, #schedYear').change(function(){
		var exam = $('#schedExam').val();
		var year = $('#schedYear').val();
		$('#loadSubjects').prop('disabled', !(exam && year));
	});

	$('#loadSubjects').click(function(){
		var classid = $('#schedClass').val();
		var examid = $('#schedExam').val();
		var year = $('#schedYear').val();
		if(!classid || !examid || !year) return alert('Select class, exam and year');

		// load subjects for exam
		$.post(siteUrl + '/inc/ajaxAction.php', { type: 'getExamSubjectsJson', exam: examid }, function(json){
			var subjects = json || [];
			var html = '';
			if(subjects.length === 0){
				html = '<p>No subjects found for selected exam.</p>';
			} else {
				html += '<table class="table table-bordered"><thead><tr><th>Subject</th><th>Date (YYYY-MM-DD)</th></tr></thead><tbody>';
				for(var i=0;i<subjects.length;i++){
					var s = subjects[i];
					html += '<tr data-subjectid="'+s.subjectid+'">';
					html += '<td>'+s.subjectName+'</td>';
					html += '<td><input class="form-control subj-date" name="date_'+s.subjectid+'" type="date" /></td>';
					html += '</tr>';
				}
				html += '</tbody></table>';
			}

			$('#subjectsList').html(html);
			$('#scheduleArea').show();
			$('#schClassId').val(classid);
			$('#schExamId').val(examid);
			$('#schYear').val(year);

			// load existing schedule
			$.post(siteUrl + '/inc/ajaxAction.php', { type: 'getExamSchedule', classid: classid, examid: examid, year: year }, function(data){
				try{
					var sd = (typeof data === 'string') ? JSON.parse(data) : data;
					for(var sid in sd){
						var val = sd[sid];
						$('#subjectsList').find('tr[data-subjectid="'+sid+'"] .subj-date').val(val);
					}
				}catch(e){
					// ignore
				}
			}, 'json');

		}, 'json');
	});

	$('#saveSchedule').click(function(){
		var classid = $('#schClassId').val();
		var examid = $('#schExamId').val();
		var year = $('#schYear').val();
		if(!classid || !examid || !year) return alert('Missing required context');

		var subject_dates = {};
		$('#subjectsList').find('tr').each(function(){
			var sid = $(this).data('subjectid');
			var date = $(this).find('.subj-date').val() || '';
			subject_dates[sid] = date;
		});

		$('#saveStatus').text('Saving...');

		$.post(siteUrl + '/inc/ajaxAction.php', {
			type: 'saveExamSchedule',
			classid: classid,
			examid: examid,
			year: year,
			subject_dates: JSON.stringify(subject_dates)
		}, function(resp){
			try{
				if(resp.success){
					$('#saveStatus').text(resp.message).css('color','green');
				} else {
					$('#saveStatus').text(resp.message).css('color','red');
				}
			}catch(e){
				$('#saveStatus').text('Unexpected response').css('color','red');
			}
		}, 'json');
	});

})(jQuery);
</script>

<?php get_footer();

?>