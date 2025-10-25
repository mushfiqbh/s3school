<?php
/**
 * Template Name: Former Teachers
 */

get_header();
?>

<style>
	.teacher-directory {
		padding: 40px 30px;
		margin-bottom: 40px;
		background: #f8fafc;
		border-radius: 24px;
		box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.1);
	}

	.teacher-directory__heading {
		text-align: center;
		max-width: 560px;
		margin: 0 auto 35px;
	}

	.teacher-directory__heading h3 {
		font-size: 28px;
		font-weight: 700;
		color: #1e293b;
		margin-bottom: 12px;
	}

	.teacher-directory__heading p {
		margin: 0;
		color: #64748b;
		font-size: 15px;
		line-height: 1.6;
	}

	.teacher-grid {
		display: grid;
		grid-template-columns: repeat(4, minmax(100px, 1fr));
		gap: 24px;
	}

	@media (max-width: 900px) {
		.teacher-grid {
			grid-template-columns: repeat(3, minmax(100px, 1fr));
		}
	}

	@media (max-width: 600px) {
		.teacher-grid {
			grid-template-columns: repeat(2, minmax(100px, 1fr));
		}
	}

	.teacher-card-link {
		display: block;
		height: 100%;
		text-decoration: none;
		color: inherit;
	}

	.teacher-card {
		height: 100%;
		background: #ffffff;
		border-radius: 16px;
		box-shadow: 0 10px 25px rgba(15, 23, 42, 0.08);
		overflow: hidden;
		position: relative;
		display: flex;
		flex-direction: column;
		transition: transform 0.3s ease, box-shadow 0.3s ease;
	}

	.teacher-card::before {
		content: '';
		position: absolute;
		top: 0;
		left: 0;
		width: 100%;
		height: 4px;
		background: linear-gradient(90deg, #6366f1, #8b5cf6);
		opacity: 0;
		transition: opacity 0.3s ease;
	}

	.teacher-card-link:hover .teacher-card,
	.teacher-card-link:focus .teacher-card {
		transform: translateY(-6px);
		box-shadow: 0 15px 35px rgba(79, 70, 229, 0.2);
	}

	.teacher-card-link:hover .teacher-card::before,
	.teacher-card-link:focus .teacher-card::before {
		opacity: 1;
	}

	.teacher-photo {
		width: 100%;
		height: 220px;
		object-fit: cover;
		background: #e2e8f0;
	}

	.teacher-content {
		padding: 20px;
		display: flex;
		flex-direction: column;
		flex: 1;
	}

	.teacher-name {
		font-size: 18px;
		font-weight: 700;
		color: #1e293b;
		margin-bottom: 6px;
	}

	.teacher-designation {
		font-size: 14px;
		color: #636f7d;
		margin-bottom: auto;
	}

	.teacher-actions {
		margin-top: 18px;
		text-align: center;
	}

	.teacher-actions .btn-view-profile {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		background: linear-gradient(135deg, #6366f1, #8b5cf6);
		color: #ffffff;
		border-radius: 999px;
		padding: 10px 18px;
		font-size: 14px;
		font-weight: 500;
		text-decoration: none;
		transition: box-shadow 0.3s ease, transform 0.3s ease;
	}

	.teacher-actions .btn-view-profile i {
		font-size: 12px;
	}

	.teacher-actions .btn-view-profile:hover {
		box-shadow: 0 12px 25px rgba(79, 70, 229, 0.35);
		transform: translateY(-2px);
	}

	.teacher-detail-wrapper {
		background: #ffffff;
		border-radius: 20px;
		box-shadow: 0 12px 35px rgba(15, 23, 42, 0.12);
		overflow: hidden;
		margin-bottom: 30px;
	}

	.teacher-detail-header {
		display: flex;
		flex-wrap: wrap;
		align-items: center;
		gap: 30px;
		padding: 30px;
		background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(129, 140, 248, 0.15));
	}

	.teacher-detail-photo {
		flex: 0 0 220px;
		border-radius: 18px;
		overflow: hidden;
		box-shadow: 0 10px 25px rgba(79, 70, 229, 0.15);
	}

	.teacher-detail-photo img {
		width: 100%;
		height: 100%;
		object-fit: cover;
	}

	.teacher-detail-meta h3 {
		font-size: 28px;
		margin: 0 0 8px;
		color: #1e293b;
	}

	.teacher-detail-meta .designation {
		font-size: 16px;
		color: #4f46e5;
		font-weight: 600;
		margin-bottom: 14px;
	}

	.teacher-meta-list {
		list-style: none;
		padding: 0;
		margin: 0;
		display: grid;
		gap: 10px;
	}

	.teacher-meta-list li {
		display: flex;
		gap: 10px;
		font-size: 14px;
		color: #475569;
	}

	.teacher-meta-list span:first-child {
		font-weight: 600;
		color: #1e293b;
		width: 130px;
	}

	.teacher-detail-body {
		padding: 30px;
		display: grid;
		gap: 32px;
	}

	.teacher-section-title {
		font-size: 18px;
		font-weight: 700;
		color: #1f2937;
		margin-bottom: 16px;
	}

	.teacher-note {
		background: #f1f5f9;
		border-left: 4px solid #6366f1;
		padding: 18px;
		border-radius: 10px;
		color: #475569;
	}

	.teacher-note p {
		margin: 0 0 8px;
	}

	.teacher-note p:last-child {
		margin-bottom: 0;
	}

	.teacher-table {
		width: 100%;
		border-collapse: collapse;
		border-radius: 12px;
		overflow: hidden;
		box-shadow: 0 6px 18px rgba(15, 23, 42, 0.08);
	}

	.teacher-table thead {
		background: linear-gradient(135deg, #6366f1, #8b5cf6);
		color: #ffffff;
	}

	.teacher-table th,
	.teacher-table td {
		padding: 14px 16px;
		font-size: 14px;
		text-align: center;
		border-bottom: 1px solid rgba(226, 232, 240, 0.7);
	}

	.teacher-table tbody tr:nth-child(even) {
		background: #f8fafc;
	}

	.teacher-empty-state {
		padding: 40px;
		text-align: center;
		border: 2px dashed rgba(99, 102, 241, 0.25);
		border-radius: 18px;
		color: #475569;
	}

	.back-link {
		display: inline-flex;
		align-items: center;
		gap: 8px;
		padding: 10px 18px;
		border-radius: 999px;
		border: 1px solid #c7d2fe;
		color: #4f46e5;
		text-decoration: none;
		font-weight: 500;
		margin-bottom: 20px;
		transition: background 0.3s ease, color 0.3s ease;
	}

	.back-link i {
		font-size: 13px;
	}

	.back-link:hover,
	.back-link:focus {
		background: #eef2ff;
		color: #4338ca;
	}

	@media (max-width: 992px) {
		.teacher-directory {
			padding: 32px 20px;
		}

		.teacher-grid {
			gap: 20px;
		}
	}

	@media (max-width: 768px) {
		.teacher-detail-header {
			flex-direction: column;
			text-align: center;
			gap: 20px;
		}

		.teacher-detail-photo {
			flex: 0 0 auto;
		}

		.teacher-meta-list {
			text-align: left;
		}

		.teacher-meta-list span:first-child {
			width: 110px;
		}
	}
</style>

<?php
$wpdb->hide_errors();

global $wpdb;

$page_url    = get_permalink();
$teacher_id  = isset($_GET['t']) ? absint($_GET['t']) : 0;
$default_img = esc_url(get_template_directory_uri() . '/img/No_Image.jpg');
?>

<div class="b-title-page b-title-page_teacher b-title-page_6">
	<div class="container">
		<div class="row" style="min-height: 200px;background: #f5f5f5;">
			<div class="col-xs-12">
				<br><br>
				<div class="b-title-page__info text-center">
					<h2>Our Former Teachers</h2>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="b-layer-main">
	<div class="page-arrow">
		<i class="fa fa-angle-down" aria-hidden="true"></i>
	</div>
	<div class="b-blog-classic">
		<div class="container">
			<div class="row">
				<div class="col-xs-12 col-sm-7 col-md-8 col-lg-9">
					<div class="b-blog-items-holder">
						<div class="clearfix">
							<?php if (!$teacher_id) : ?>
								<?php
								$teachers = $wpdb->get_results('SELECT teacherid, teacherName, teacherImg, teacherDesignation FROM ct_teacher WHERE status="Former" ORDER BY teacherName ASC');
								?>

								<div class="teacher-directory">
									<div class="teacher-directory__heading">
										<h3><?php echo esc_html__('Meet Our Former Teachers', 's3schoolManagment'); ?></h3>
										<p><?php echo esc_html__('Discover the dedicated educators who nurtured and inspired our students in the past.', 's3schoolManagment'); ?></p>
									</div>

									<?php if (!empty($teachers)) : ?>
										<div class="teacher-grid">
											<?php
											foreach ($teachers as $teacher) {
												$photo_url = '';

												if (!empty($teacher->teacherImg)) {
													$photo_url = esc_url($teacher->teacherImg);
												}

												if (empty($photo_url)) {
													$photo_url = $default_img;
												}

												$profile_url = add_query_arg('t', absint($teacher->teacherid), $page_url);
												?>

												<a class="teacher-card-link" href="<?php echo esc_url($profile_url); ?>">
													<article class="teacher-card">
														<img class="teacher-photo" src="<?php echo $photo_url; ?>" alt="<?php echo esc_attr($teacher->teacherName); ?>">
														<div class="teacher-content">
															<h3 class="teacher-name"><?php echo esc_html($teacher->teacherName); ?></h3>
															<?php if (!empty($teacher->teacherDesignation)) : ?>
																<p class="teacher-designation">Former <?php echo esc_html($teacher->teacherDesignation); ?></p>
															<?php endif; ?>
														</div>
													</article>
												</a>
											<?php } ?>
										</div>
									<?php else : ?>
										<div class="teacher-empty-state">
											<p><?php echo esc_html__('Teacher profiles will appear here once they are added.', 's3schoolManagment'); ?></p>
										</div>
									<?php endif; ?>
								</div>

							<?php else : ?>
								<?php
								$teacher = $wpdb->get_row($wpdb->prepare('SELECT * FROM ct_teacher WHERE teacherid = %d', $teacher_id));
								?>

								<?php if ($teacher) : ?>
									<?php
									$photo_url = '';

									if (!empty($teacher->teacherImg)) {
										$photo_url = esc_url($teacher->teacherImg);
									}

									if (empty($photo_url)) {
										$photo_url = $default_img;
									}

									$formatted_birth   = (!empty($teacher->teacherBirth) && $teacher->teacherBirth !== '0000-00-00') ? mysql2date('d M Y', $teacher->teacherBirth) : '';
									$formatted_joining = (!empty($teacher->teacherJoining) && $teacher->teacherJoining !== '0000-00-00') ? mysql2date('d M Y', $teacher->teacherJoining) : '';

									$meta_rows = [
										__('Phone', 's3schoolManagment')        => $teacher->teacherPhone,
										__('Birth Date', 's3schoolManagment')    => $formatted_birth,
										__('Joining Date', 's3schoolManagment')  => $formatted_joining,
										__('Father', 's3schoolManagment')       => $teacher->teacherFather,
										__('Mother', 's3schoolManagment')       => $teacher->teacherMother,
										__('Index No', 's3schoolManagment')     => $teacher->teacherMpo,
									];

									$qualifications = [];
									if (!empty($teacher->teacherQualificarion)) {
										$decoded = json_decode($teacher->teacherQualificarion, true);
										if (is_array($decoded)) {
											$qualifications = $decoded;
										}
									}

									$trainings = [];
									if (!empty($teacher->teacherTraining)) {
										$decoded = json_decode($teacher->teacherTraining, true);
										if (is_array($decoded)) {
											$trainings = $decoded;
										}
									}
									?>

									<a class="back-link" href="<?php echo esc_url($page_url); ?>"><i class="fa fa-chevron-left" aria-hidden="true"></i><?php echo esc_html__('Back to all teachers', 's3schoolManagment'); ?></a>

									<div class="teacher-detail-wrapper">
										<div class="teacher-detail-header">
											<div class="teacher-detail-photo">
												<img src="<?php echo $photo_url; ?>" alt="<?php echo esc_attr($teacher->teacherName); ?>">
											</div>
											<div class="teacher-detail-meta">
												<h3><?php echo esc_html($teacher->teacherName); ?></h3>
												<?php if (!empty($teacher->teacherDesignation)) : ?>
													<div class="designation">Former <?php echo esc_html($teacher->teacherDesignation); ?></div>
												<?php endif; ?>

												<?php if (!empty(array_filter($meta_rows))) : ?>
													<ul class="teacher-meta-list">
														<?php foreach ($meta_rows as $label => $value) : ?>
															<?php if (!empty($value)) : ?>
																<li><span><?php echo esc_html($label); ?>:</span><span><?php echo esc_html($value); ?></span></li>
															<?php endif; ?>
														<?php endforeach; ?>
													</ul>
												<?php endif; ?>
											</div>
										</div>

										<div class="teacher-detail-body">
											<?php if (!empty($teacher->teacherPresent) || !empty($teacher->teacherPermanent)) : ?>
												<div>
													<h4 class="teacher-section-title"><?php echo esc_html__('Address', 's3schoolManagment'); ?></h4>
													<div class="teacher-note">
														<?php if (!empty($teacher->teacherPresent)) : ?>
															<p><strong><?php echo esc_html__('Present:', 's3schoolManagment'); ?></strong> <?php echo esc_html($teacher->teacherPresent); ?></p>
														<?php endif; ?>
														<?php if (!empty($teacher->teacherPermanent)) : ?>
															<p><strong><?php echo esc_html__('Permanent:', 's3schoolManagment'); ?></strong> <?php echo esc_html($teacher->teacherPermanent); ?></p>
														<?php endif; ?>
													</div>
												</div>
											<?php endif; ?>

											<?php if (!empty($qualifications)) : ?>
												<div>
													<h4 class="teacher-section-title"><?php echo esc_html__('Educational Qualification', 's3schoolManagment'); ?></h4>
													<div class="table-responsive">
														<table class="teacher-table">
															<thead>
																<tr>
																	<th><?php echo esc_html__('SL', 's3schoolManagment'); ?></th>
																	<th><?php echo esc_html__('Exam Name', 's3schoolManagment'); ?></th>
																	<th><?php echo esc_html__('Div/Class', 's3schoolManagment'); ?></th>
																	<th><?php echo esc_html__('Passing Year', 's3schoolManagment'); ?></th>
																	<th><?php echo esc_html__('Board/University', 's3schoolManagment'); ?></th>
																</tr>
															</thead>
															<tbody>
																<?php foreach ($qualifications as $index => $qualification) : ?>
																	<?php if (is_array($qualification)) : ?>
																		<tr>
																			<td><?php echo esc_html($index + 1); ?></td>
																			<?php foreach ($qualification as $cell_index => $cell_value) : ?>
																				<?php
																				$value = '';
																				if (is_scalar($cell_value)) {
																					$value = ($cell_index === 1) ? '--' : (string) $cell_value;
																				}
																			?>
																			<td><?php echo esc_html($value); ?></td>
																		<?php endforeach; ?>
																		</tr>
																	<?php endif; ?>
																<?php endforeach; ?>
															</tbody>
														</table>
													</div>
												</div>
											<?php endif; ?>

											<?php if (!empty($trainings)) : ?>
												<div>
													<h4 class="teacher-section-title"><?php echo esc_html__('Training Information', 's3schoolManagment'); ?></h4>
													<div class="table-responsive">
														<table class="teacher-table">
															<thead>
																<tr>
																	<th><?php echo esc_html__('SL', 's3schoolManagment'); ?></th>
																	<th><?php echo esc_html__('Name', 's3schoolManagment'); ?></th>
																	<th><?php echo esc_html__('Date', 's3schoolManagment'); ?></th>
																	<th><?php echo esc_html__('Duration', 's3schoolManagment'); ?></th>
																	<th><?php echo esc_html__('Organization', 's3schoolManagment'); ?></th>
																	<th><?php echo esc_html__('Venue', 's3schoolManagment'); ?></th>
																</tr>
															</thead>
															<tbody>
																<?php foreach ($trainings as $index => $training) : ?>
																	<?php if (is_array($training)) : ?>
																		<tr>
																			<td><?php echo esc_html($index + 1); ?></td>
																			<?php foreach ($training as $cell_index => $cell_value) : ?>
																				<?php
																				$value = '';
																				if (is_scalar($cell_value)) {
																					$value = ($cell_index === 2) ? sprintf('%s %s', $cell_value, __('Days', 's3schoolManagment')) : (string) $cell_value;
																				}
																			?>
																			<td><?php echo esc_html($value); ?></td>
																		<?php endforeach; ?>
																		</tr>
																	<?php endif; ?>
																<?php endforeach; ?>
															</tbody>
														</table>
													</div>
												</div>
											<?php endif; ?>

											<?php if (!empty($teacher->teacherNote)) : ?>
												<div>
													<h4 class="teacher-section-title"><?php echo esc_html__('Additional Notes', 's3schoolManagment'); ?></h4>
													<div class="teacher-note">
														<?php echo wp_kses_post(wpautop($teacher->teacherNote)); ?>
													</div>
												</div>
											<?php endif; ?>
										</div>
									</div>
								<?php else : ?>
									<div class="teacher-empty-state">
										<p><?php echo esc_html__('We couldn\'t find the profile you were looking for.', 's3schoolManagment'); ?></p>
										<a class="back-link" href="<?php echo esc_url($page_url); ?>"><i class="fa fa-chevron-left" aria-hidden="true"></i><?php echo esc_html__('Back to all teachers', 's3schoolManagment'); ?></a>
									</div>
								<?php endif; ?>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<div class="col-xs-12 col-sm-5 col-md-4 col-lg-3">
					<?php get_sidebar(); ?>
				</div>
			</div>
		</div>
	</div>
</div>

<?php get_footer(); ?>