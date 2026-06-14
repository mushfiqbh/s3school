<?php

/**
 * Template Name: Admin Committee
 */

global $wpdb;

// Helper function to sanitize input
function sanitize_input($input)
{
	return htmlentities(wp_unslash($input), ENT_QUOTES);
}

/*=================
	Add Committee with Members
=================*/
if (isset($_POST['addCommittee'])) {
	$committee_title = sanitize_input($_POST['committee_title']);
	$job_description = isset($_POST['job_description']) ? sanitize_input($_POST['job_description']) : '';
	$is_active = isset($_POST['is_active']) ? 1 : 0;

	// Insert committee header
	$committee_data = array(
		'committee_title' => $committee_title,
		'job_description' => $job_description,
		'sort_order' => 0,
		'is_active' => $is_active
	);

	$wpdb->insert('ct_committees', $committee_data);
	$committee_id = $wpdb->insert_id;

	if ($committee_id) {
		// Insert members
		$member_names = isset($_POST['member_name']) ? $_POST['member_name'] : array();
		$success_count = 0;

		foreach ($member_names as $index => $member_name) {
			if (empty(trim($member_name))) continue;

			$member_data = array(
				'committee_id' => $committee_id,
				'member_name' => sanitize_input($member_name),
				'member_designation' => isset($_POST['member_designation'][$index]) ? sanitize_input($_POST['member_designation'][$index]) : '',
				'member_label' => isset($_POST['member_label'][$index]) ? sanitize_input($_POST['member_label'][$index]) : NULL,
				'member_subject' => isset($_POST['member_subject'][$index]) ? sanitize_input($_POST['member_subject'][$index]) : NULL,
				'member_position' => isset($_POST['member_position'][$index]) ? sanitize_input($_POST['member_position'][$index]) : NULL,
				'sort_order' => intval($index),
				'is_active' => $is_active
			);

			if ($wpdb->insert('ct_committee_members', $member_data)) {
				$success_count++;
			}
		}

		// Update is_primary on committee record
		if (isset($_POST['is_primary'])) {
			$wpdb->update(
				'ct_committees',
				array('is_primary' => 1),
				array('committee_id' => $committee_id)
			);
		}

		$message = ms3message(($success_count > 0), "Added committee with $success_count member(s)");
	} else {
		$message = ms3message(false, "Failed to create committee");
	}
}

/*edit Section*/
$editCommittee = null;
$editMembers = array();
if (isset($_POST['editCommittee']) || isset($_GET['edit'])) {
	$committee_id = isset($_POST['committee_id']) ? intval($_POST['committee_id']) : intval($_GET['edit']);
	$editCommittee = $wpdb->get_row($wpdb->prepare("SELECT * FROM ct_committees WHERE committee_id = %d", $committee_id));
	if ($editCommittee) {
		$editMembers = $wpdb->get_results($wpdb->prepare(
			"SELECT * FROM ct_committee_members WHERE committee_id = %d ORDER BY sort_order",
			$committee_id
		));
	}
}

/*Update Section*/
if (isset($_POST['updateCommittee'])) {
	$committee_id = intval($_POST['committee_id']);
	$committee_title = sanitize_input($_POST['committee_title']);
	$job_description = isset($_POST['job_description']) ? sanitize_input($_POST['job_description']) : '';
	$is_active = isset($_POST['is_active']) ? 1 : 0;

	// Update committee header
	$committee_data = array(
		'committee_title' => $committee_title,
		'job_description' => $job_description,
		'is_active' => $is_active
	);

	$wpdb->update('ct_committees', $committee_data, array('committee_id' => $committee_id));

	// Delete existing members and re-insert
	$wpdb->delete('ct_committee_members', array('committee_id' => $committee_id));

	$member_names = isset($_POST['member_name']) ? $_POST['member_name'] : array();
	$success_count = 0;

	foreach ($member_names as $index => $member_name) {
		if (empty(trim($member_name))) continue;

		$member_data = array(
			'committee_id' => $committee_id,
			'member_name' => sanitize_input($member_name),
			'member_designation' => isset($_POST['member_designation'][$index]) ? sanitize_input($_POST['member_designation'][$index]) : '',
			'member_label' => isset($_POST['member_label'][$index]) ? sanitize_input($_POST['member_label'][$index]) : NULL,
			'member_subject' => isset($_POST['member_subject'][$index]) ? sanitize_input($_POST['member_subject'][$index]) : NULL,
			'member_position' => isset($_POST['member_position'][$index]) ? sanitize_input($_POST['member_position'][$index]) : NULL,
			'sort_order' => intval($index),
			'is_active' => $is_active
		);

		if ($wpdb->insert('ct_committee_members', $member_data)) {
			$success_count++;
		}
	}

	$message = ms3message(($success_count > 0), "Updated committee with $success_count member(s)");
}

/*Delete Section*/
if (isset($_POST['deleteCommittee'])) {
	$committee_id = intval($_POST['committee_id']);
	// Members will be deleted automatically due to foreign key CASCADE
	$delete = $wpdb->delete('ct_committees', array('committee_id' => $committee_id));
	$message = ms3message($delete, 'Deleted');
}

function output($content)
{
	return str_replace("\n", "<br />", html_entity_decode($content));
}

?>
<p id="theSiteURL" style="display: none;"><?= get_template_directory_uri() ?></p>
<?php if (! is_admin()) {
	get_header(); ?>
	<div class="b-layer-main">
		<div class="">
			<div class="container">
				<div class="row">
					<div class="col-md-12">
					<?php } ?>

					<div class="row">
						<div class="col-md-12">
							<div class="panel panel-info">
								<div class="panel-heading">
									<h3>All Committees</h3>
								</div>
								<div class="panel-body">
									<div class="table-responsive">
										<table class="table table-bordered table-striped" id="datatable">
											<thead>
												<tr>
													<th>Type</th>
													<th>Key</th>
													<th>Title</th>
													<th>Members</th>
													<th>Status</th>
													<th style="width: 120px">Action</th>
												</tr>
											</thead>
											<tbody>
												<?php
												$committees = $wpdb->get_results("SELECT 
					c.committee_id,
					c.committee_title,
					c.is_active,
					COUNT(m.member_id) as member_count
					FROM ct_committees c
					LEFT JOIN ct_committee_members m ON c.committee_id = m.committee_id
					GROUP BY c.committee_id 
					ORDER BY c.sort_order, c.committee_title");

												foreach ($committees as $committee) {
												?>
													<tr>
														<td><?= $committee->committee_title ?></td>
														<td><?= $committee->member_count ?> members</td>
														<td>
															<span class="label label-<?= ($committee->is_active == 1) ? 'success' : 'danger'; ?>">
																<?= ($committee->is_active == 1) ? 'Active' : 'Inactive'; ?>
															</span>
														</td>
														<td class="text-center">
															<div class="btn-group" role="group">
																<a class="btn btn-sm btn-info" href="?page=committee&view=<?= $committee->committee_id ?>" title="View">
																	<i class="fa fa-eye"></i>
																</a>
																<a class="btn btn-sm btn-warning" href="?page=committee&edit=<?= $committee->committee_id ?>" title="Edit">
																	<i class="fa fa-edit"></i>
																</a>
																<button type="button" class="btn btn-sm btn-danger btnDelete" data-id='<?= $committee->committee_id ?>' title="Delete">
																	<i class="fa fa-trash"></i>
																</button>
															</div>
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

					<div class="container-fluid maxAdminpages" style="padding-left: 0">

						<!-- Show Status message -->
						<?php if (isset($message)) {
							ms3showMessage($message);
						} ?>

						<h2>Committee Management <a href="?page=committee" class="pull-right btn btn-success">Add Committee</a> </h2><br>
						<div class="row">
							<?php if (!isset($_GET['view']) || $editCommittee) { ?>
								<div class="col-md-12">
									<div class="panel panel-info">
										<div class="panel-heading">
											<h3><?= $editCommittee ? 'Edit' : 'Add'; ?> Committee</h3>
										</div>
										<div class="panel-body">
											<form action="" method="POST" id="committeeForm">

								<?php
								$committee_id = '';
								$committee_title = '';
								$job_description = '';
								$is_active = true;

								if ($editCommittee) {
									$committee_id = $editCommittee->committee_id;
									$committee_title = $editCommittee->committee_title;
									$job_description = $editCommittee->job_description;
									$is_active = ($editCommittee->is_active == 1);
								}
								?>												<?php if ($editCommittee): ?>
													<input type="hidden" name="committee_id" value="<?= $committee_id ?>">
												<?php endif; ?>

								<!-- Committee Info -->
								<div class="row">
									<div class="form-group col-md-10">
										<label>Committee Title</label>
										<input class="form-control" type="text" name="committee_title" value="<?= $committee_title; ?>" placeholder="e.g., Staff Council 2024" required>
									</div>

									<div class="form-group col-md-2">
										<div class="checkbox" style="margin-top: 25px;">
											<label>
												<input type="checkbox" name="is_active" value="1" <?= $is_active ? 'checked' : ''; ?>> Active
											</label>
										</div>
									</div>
								</div>

								<div class="form-group">
									<label>Job Description</label>
									<textarea class="form-control" name="job_description" rows="3" placeholder="Describe the committee's responsibilities and objectives"><?= $job_description; ?></textarea>
								</div>

												<hr>
												<h4>Committee Members <button type="button" class="btn btn-sm btn-success" onclick="addMemberRow()"><i class="fa fa-plus"></i> Add Member</button></h4>

												<div id="membersContainer">
													<?php
													if (!empty($editMembers)) {
														foreach ($editMembers as $index => $member) {
													?>
															<div class="member-row panel panel-default" style="padding: 15px; margin-bottom: 15px;">
																<button type="button" class="btn btn-xs btn-danger pull-right" onclick="removeMemberRow(this)"><i class="fa fa-trash"></i></button>
																<h5 style="margin-top: 0;">Member #<span class="member-number"><?= $index + 1 ?></span></h5>

																<div class="row">
																	<div class="form-group col-md-4">
																		<label>Name *</label>
																		<input class="form-control" type="text" name="member_name[]" value="<?= $member->member_name ?>" required>
																	</div>
																	<div class="form-group col-md-4">
																		<label>Designation *</label>
																		<input class="form-control" type="text" name="member_designation[]" value="<?= $member->member_designation ?>" required>
																	</div>
																	<div class="form-group col-md-2">
																		<label>Label</label>
																		<input class="form-control" type="text" name="member_label[]" value="<?= $member->member_label ?>" placeholder="Mr/Ms">
																	</div>
																	<div class="form-group col-md-2">
																		<label>Position</label>
																		<input class="form-control" type="text" name="member_position[]" value="<?= $member->member_position ?>">
																	</div>
																</div>

															<div class="row">
																<div class="form-group col-md-12">
																	<label>Subject</label>
																	<input class="form-control" type="text" name="member_subject[]" value="<?= $member->member_subject ?>">
																</div>
															</div>
														</div>
														<?php
														}
													} else {
														// Default: one empty row
														?>
														<div class="member-row panel panel-default" style="padding: 15px; margin-bottom: 15px;">
															<button type="button" class="btn btn-xs btn-danger pull-right" onclick="removeMemberRow(this)"><i class="fa fa-trash"></i></button>
															<h5 style="margin-top: 0;">Member #<span class="member-number">1</span></h5>

															<div class="row">
																<div class="form-group col-md-4">
																	<label>Name *</label>
																	<input class="form-control" type="text" name="member_name[]" required>
																</div>
																<div class="form-group col-md-4">
																	<label>Designation *</label>
																	<input class="form-control" type="text" name="member_designation[]" required>
																</div>
																<div class="form-group col-md-2">
																	<label>Label</label>
																	<input class="form-control" type="text" name="member_label[]" placeholder="Mr/Ms">
																</div>
																<div class="form-group col-md-2">
																	<label>Position</label>
																	<input class="form-control" type="text" name="member_position[]">
																</div>
															</div>

															<div class="row">
																<div class="form-group col-md-12">
																	<label>Subject</label>
																	<input class="form-control" type="text" name="member_subject[]">
																</div>
															</div>
														</div>
												</div>
											<?php
													}
											?>
										</div>

										<div class="form-group text-right">
											<button class="btn btn-primary" type="submit" name="<?= $editCommittee ? 'updateCommittee' : 'addCommittee'; ?>">
												<i class="fa fa-save"></i> <?= $editCommittee ? 'Update' : 'Save'; ?> Committee
											</button>
										</div>
										</form>
									</div>
								</div>
						</div>
					<?php } else { ?>
						<div class="col-md-12">
							<div class="panel panel-info">
								<?php
								$committee_id = intval($_GET['view']);
								$committee = $wpdb->get_row($wpdb->prepare("SELECT * FROM ct_committees WHERE committee_id = %d", $committee_id));
								if ($committee) {
									$members = $wpdb->get_results($wpdb->prepare(
										"SELECT * FROM ct_committee_members WHERE committee_id = %d ORDER BY sort_order",
										$committee_id
									));
								?>
									<div class="panel-heading">
										<h3><?= $committee->committee_title ?></h3>
									</div>
									<div class="panel-body">
										<?php foreach ($members as $member) { ?>
											<div class="row" style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
												<div class="col-md-12">
													<h4><?= $member->member_name ?></h4>
													<p><strong><?= $member->member_designation ?></strong></p>

													<div class="row">
														<div class="col-md-6">
															<?php if (!empty($member->member_subject)) { ?>
																<p><strong>Subject:</strong> <?= output($member->member_subject) ?></p>
															<?php } ?>
														</div>

														<div class="col-md-6">
															<?php if (!empty($member->member_position)) { ?>
																<p><strong>Position:</strong> <?= output($member->member_position) ?></p>
															<?php } ?>
															<?php if (!empty($member->member_label)) { ?>
																<p><strong>Label:</strong> <?= output($member->member_label) ?></p>
															<?php } ?>
													</div>
												</div>
											</div>
										</div>
									<?php } ?>
									
									<?php if (!empty($committee->job_description)) { ?>
										<div style="margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 5px;">
											<strong>Committee Job Description:</strong><br>
											<?= output($committee->job_description) ?>
										</div>
									<?php } ?>
								</div>
							<?php
							}
							?>							</div>
						</div>
					<?php } ?>
					</div>


					</div>

					<?php if (! is_admin()) { ?>
				</div>
			</div>
		</div>
	</div>
	</div>
<?php get_footer();
					} ?>

<div id="deleteModal" class="modal fade" role="dialog">
	<div class="modal-dialog modal-sm">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal">&times;</button>
				<h4 class="modal-title">Delete Committee</h4>
			</div>
			<div class="modal-body">
				<p class="text-danger">This will delete the committee and all its members. You can't recover the data after delete.</p>
			</div>
			<div class="modal-footer">
				<form action="" method="POST">
					<input type="hidden" name="committee_id" class="delete-id">
					<button type="button" class="btn btn-default pull-left" data-dismiss="modal">Close</button>
					<button type="submit" class="btn btn-danger" name="deleteCommittee">Delete Committee</button>
				</form>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
	function addMemberRow() {
		var container = document.getElementById('membersContainer');
		var memberCount = container.querySelectorAll('.member-row').length;

		var newRow = document.createElement('div');
		newRow.className = 'member-row panel panel-default';
		newRow.style.padding = '15px';
		newRow.style.marginBottom = '15px';

		newRow.innerHTML = `
			<button type="button" class="btn btn-xs btn-danger pull-right" onclick="removeMemberRow(this)"><i class="fa fa-trash"></i></button>
			<h5 style="margin-top: 0;">Member #<span class="member-number">${memberCount + 1}</span></h5>
			
			<div class="row">
				<div class="form-group col-md-4">
					<label>Name *</label>
					<input class="form-control" type="text" name="member_name[]" required>
				</div>
				<div class="form-group col-md-4">
					<label>Designation *</label>
					<input class="form-control" type="text" name="member_designation[]" required>
				</div>
				<div class="form-group col-md-2">
					<label>Label</label>
					<input class="form-control" type="text" name="member_label[]" placeholder="Mr/Ms">
				</div>
				<div class="form-group col-md-2">
					<label>Position</label>
					<input class="form-control" type="text" name="member_position[]">
				</div>
			</div>

			<div class="row">
				<div class="form-group col-md-12">
					<label>Subject</label>
					<input class="form-control" type="text" name="member_subject[]">
				</div>
			</div>
		`;

		container.appendChild(newRow);
		updateMemberNumbers();
	}

	function removeMemberRow(button) {
		var container = document.getElementById('membersContainer');
		var rows = container.querySelectorAll('.member-row');

		if (rows.length > 1) {
			button.closest('.member-row').remove();
			updateMemberNumbers();
		} else {
			alert('At least one member is required!');
		}
	}

	function updateMemberNumbers() {
		var memberRows = document.querySelectorAll('.member-row');
		memberRows.forEach(function(row, index) {
			row.querySelector('.member-number').textContent = index + 1;
		});
	}

	(function($) {
		$(document).ready(function() {
			$('.btnDelete').click(function(event) {
				$('#deleteModal').find('.delete-id').val($(this).data('id'));
				$('#deleteModal').modal("show");
			});
		});
	})(jQuery);
</script>