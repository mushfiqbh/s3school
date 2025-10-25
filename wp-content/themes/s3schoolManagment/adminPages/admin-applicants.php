<?php
/*
Template Name: Admin Applicants
*/

get_header();

global $wpdb;
$message = null;

// Helpers
function sm_clean_int($v) { return isset($v) && $v !== '' ? (int)$v : null; }
function sm_clean_txt($v) { return isset($v) ? sanitize_text_field($v) : ''; }

/*=================
  Actions
=================*/

// Update application status
if (isset($_POST['updateStatus']) && isset($_POST['applicationid'])) {
    $applicationid = (int)$_POST['applicationid'];
    $status = sm_clean_txt($_POST['updateStatus']);
    
    $update = $wpdb->update(
        'ct_online_application',
        array('approve_status' => $status),
        array('applicationid' => $applicationid)
    );
    
    if ($update !== false) {
        $message = array('status' => 'success', 'message' => 'Status updated successfully');
    } else {
        $message = array('status' => 'error', 'message' => 'Failed to update status: ' . $wpdb->last_error);
    }
}

// Update applicant in ct_online_application
else if (isset($_POST['updateApplicant']) && isset($_POST['applicationid'])) {
    $applicationid = (int)$_POST['applicationid'];

    $update = $wpdb->update(
        'ct_online_application',
        array(
            'stdName'         => sm_clean_txt($_POST['stdName']),
            'stdPhone'        => sm_clean_txt($_POST['stdPhone']),
            'stdAdmitClass'   => sm_clean_int($_POST['stdAdmitClass']),
            'stdAdmitYear'    => sm_clean_txt($_POST['stdAdmitYear']),
            'stdSection'      => sm_clean_int($_POST['stdSection']),
            'stdRoll'         => sm_clean_txt($_POST['stdRoll']),
            'stdPrevSchool'   => sm_clean_txt($_POST['stdPrevSchool']),
            'stdGPA'          => sm_clean_txt($_POST['stdGPA']),
            'paymentPaid'     => sm_clean_txt($_POST['paymentPaid']),
            'paymentDue'      => sm_clean_txt($_POST['paymentDue']),
            'stdNote'         => sm_clean_txt($_POST['stdNote']),
            'stdUpdatedAt'    => current_time('mysql'),
        ),
        array('applicationid' => $applicationid)
    );

    if ($update === false) {
        $message = array('status' => 'faild', 'message' => 'Update failed: ' . esc_html($wpdb->last_error));
    } else {
        $message = array('status' => 'success', 'message' => 'Applicant updated');
    }
}

// Approve: move from ct_online_application to ct_student + ct_studentinfo
if (isset($_POST['approveApplicant']) && isset($_POST['applicationid'])) {
    $applicationid = (int)$_POST['applicationid'];

    // Load application

    if (!$app) {
        $message = array('status' => 'faild', 'message' => 'Application not found');
    } else {
        // Insert into ct_student (basic info)
        $student_data = array(
            'stdName' => $app->stdName,
            'stdNameBangla' => $app->stdNameBangla,
            'stdGender' => $app->stdGender,
            'stdBldGrp' => $app->stdBldGrp,
            'facilities' => $app->facilities,
            'stdImg' => $app->stdImg,
            'stdFather' => $app->stdFather,
            'stdFatherProf' => $app->stdFatherProf,
            'stdMother' => $app->stdMother,
            'motherLate' => $app->motherLate,
            'stdMotherProf' => $app->stdMotherProf,
            'stdParentIncome' => $app->stdParentIncome,
            'stdlocalGuardian' => $app->stdlocalGuardian,
            'stdGuardianNID' => $app->stdGuardianNID,
            'stdPhone' => $app->stdPhone,
            'stdPermanent' => $app->stdPermanent,
            'stdPresent' => $app->stdPresent,
            'stdBrirth' => $app->stdBrirth,
            'stdNationality' => $app->stdNationality,
            'stdReligion' => $app->stdReligion,
            'stdGPA' => $app->stdGPA,
            'stdIntellectual' => $app->stdIntellectual,
            'stdScholarsClass' => $app->stdScholarsClass,
            'stdScholarsYear' => $app->stdScholarsYear,
            'stdScholarsMemo' => $app->stdScholarsMemo,
            'stdCreatedAt' => current_time('mysql'),
            'applicationid' => $app->applicationid  // Add the application ID
        );
        
        $insert_student = $wpdb->insert('ct_student', $student_data);

        if ($insert_student === false) {
            $message = array('status' => 'faild', 'message' => 'Approve failed (student insert): ' . esc_html($wpdb->last_error));
        } else {
            $student_id = $wpdb->insert_id;

            // Insert into ct_studentinfo (per-year/class info)
            // Try to include group if column exists in online app
            $stdGroup = null;
            $col = $wpdb->get_row("SHOW COLUMNS FROM ct_online_application LIKE 'stdGroup'");
            if ($col) { $stdGroup = $app->stdGroup; }

            $insert_info = $wpdb->insert(
                'ct_studentinfo',
                array(
                    'infoStdid'     => $student_id,
                    'infoClass'     => $app->stdAdmitClass,
                    'infoYear'      => $app->stdAdmitYear,
                    'infoSection'   => isset($app->stdSection) ? $app->stdSection : 0,
                    'infoGroup'     => isset($stdGroup) ? $stdGroup : 0,
                    'infoRoll'      => $app->stdRoll,
                    'infoOptionals' => null,
                    'info4thSub'    => 0,
                )
            );

            if ($insert_info === false) {
                $message = array('status' => 'faild', 'message' => 'Approve failed (studentinfo insert): ' . esc_html($wpdb->last_error));
            } else {
                // Mark application approved and link studentid
                $wpdb->update(
                    'ct_online_application',
                    array('stdStatus' => 1, 'studentid' => $student_id, 'stdUpdatedAt' => current_time('mysql')),
                    array('applicationid' => $applicationid)
                );
                $message = array('status' => 'success', 'message' => 'Applicant approved and transferred');
            }
        }
    }
}

// Delete/Reject applicant
if (isset($_POST['deleteApplicant']) && isset($_POST['applicationid'])) {
    $applicationid = (int)$_POST['applicationid'];
    $delete = $wpdb->delete('ct_online_application', array('applicationid' => $applicationid));

    if ($delete) {
        $message = array('status' => 'success', 'message' => 'Applicant deleted');
    } else {
        $message = array('status' => 'faild', 'message' => 'Delete failed: ' . esc_html($wpdb->last_error));
    }
}

/*=================
  View
=================*/
?>
<div class="container">
    <h2>Approve Applicants</h2>

    <?php if ($message): ?>
        <div class="alert <?= $message['status'] === 'success' ? 'alert-success' : 'alert-danger' ?>">
            <?= esc_html($message['message']); ?>
        </div>
    <?php endif; ?>

    <?php
    // Build base query
    $query = "SELECT a.*, c.className, s.sectionName 
              FROM ct_online_application a
              LEFT JOIN ct_class c ON a.stdAdmitClass = c.classid
              LEFT JOIN ct_section s ON a.stdSection = s.sectionid
              WHERE 1=1";
    
    $params = array();
    
    // Apply class filter
    if (!empty($_GET['filter_class'])) {
        $class_id = (int)$_GET['filter_class'];
        $query .= " AND a.stdAdmitClass = %d";
        $params[] = $class_id;
    }
    
    // Apply status filter
    if (!empty($_GET['filter_status'])) {
        $status = sanitize_text_field($_GET['filter_status']);
        $query .= " AND a.approve_status = %s";
        $params[] = $status;
    }
    
    // Apply sorting
    $sort_order = (!empty($_GET['filter_sort']) && $_GET['filter_sort'] === 'oldest') ? 'ASC' : 'DESC';
    $query .= " ORDER BY a.stdCreatedAt $sort_order";
    
    // Prepare and execute the query
    if (!empty($params)) {
        $query = $wpdb->prepare($query, $params);
    }
    
    $apps = $wpdb->get_results($query);
    ?>

    <!-- Filters -->
    <div class="panel panel-default">
        <div class="panel-heading">Filter Applications</div>
        <div class="panel-body">
            <form method="get" class="form-inline">
                <input type="hidden" name="page" value="<?= $_GET['page'] ?? '' ?>">
                
                <div class="form-group" style="margin-right: 15px;">
                    <label for="filter_class" class="control-label">Class: </label>
                    <select name="filter_class" id="filter_class" class="form-control input-sm">
                        <option value="">All Classes</option>
                        <?php
                        $classes = $wpdb->get_results("SELECT DISTINCT c.classid, c.className 
                            FROM ct_online_application a 
                            LEFT JOIN ct_class c ON a.stdAdmitClass = c.classid 
                            ORDER BY c.className");
                        foreach ($classes as $class) {
                            $selected = (isset($_GET['filter_class']) && $_GET['filter_class'] == $class->classid) ? 'selected' : '';
                            echo "<option value='{$class->classid}' {$selected}>{$class->className}</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group" style="margin-right: 15px;">
                    <label for="filter_status" class="control-label">Status: </label>
                    <select name="filter_status" id="filter_status" class="form-control input-sm">
                        <option value="">All Statuses</option>
                        <?php
                        $statuses = ['Submitted', 'Under Review', 'Approved', 'Registered', 'Rejected'];
                        foreach ($statuses as $status) {
                            $selected = (isset($_GET['filter_status']) && $_GET['filter_status'] === $status) ? 'selected' : '';
                            echo "<option value='{$status}' {$selected}>{$status}</option>";
                        }
                        ?>
                    </select>
                </div>
                
                <div class="form-group" style="margin-right: 15px;">
                    <label for="filter_sort" class="control-label">Sort: </label>
                    <select name="filter_sort" id="filter_sort" class="form-control input-sm">
                        <option value="newest" <?= (isset($_GET['filter_sort']) && $_GET['filter_sort'] === 'newest') ? 'selected' : '' ?>>Newest First</option>
                        <option value="oldest" <?= (isset($_GET['filter_sort']) && $_GET['filter_sort'] === 'oldest') ? 'selected' : '' ?>>Oldest First</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary btn-sm">Apply Filters</button>
                <?php if (isset($_GET['filter_class']) || isset($_GET['filter_status']) || isset($_GET['filter_sort'])): ?>
                    <a href="?page=<?= $_GET['page'] ?? '' ?>" class="btn btn-default btn-sm" style="margin-left: 10px;color:#000">Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="panel panel-default">
        <div class="panel-heading">
            <?php 
            $count = $wpdb->get_var("SELECT COUNT(*) FROM ct_online_application");
            echo "Applications ($count)"; 
            ?>
        </div>
        <div class="panel-body">
            <?php if (empty($apps)): ?>
                <p>No applications found.</p>
            <?php else: ?>
                <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Class</th>
                            <th>Section</th>
                            <th>Year</th>
                            <th>Roll</th>
                            <th>Applied At</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($apps as $row): ?>
                        <tr>
                            <td><?= (int)$row->applicationid ?></td>
                            <td><?= esc_html($row->stdName) ?></td>
                            <td><?= esc_html($row->stdPhone) ?></td>
                            <td><?= esc_html($row->className) ?></td>
                            <td><?= esc_html($row->sectionName) ?></td>
                            <td><?= esc_html($row->stdAdmitYear) ?></td>
                            <td><?= esc_html($row->stdRoll) ?: '-' ?></td>
                            <td><?= esc_html($row->stdCreatedAt) ?></td>
                            <td>
                                <form method="post" class="status-form" onchange="this.submit()">
                                    <input type="hidden" name="applicationid" value="<?= (int)$row->applicationid; ?>">
                                    <select name="updateStatus" class="form-control input-sm" style="font-size:16px">
                                        <option value="Submitted" <?= $row->approve_status === 'Submitted' ? 'selected' : '' ?>>Submitted</option>
                                        <option value="Under Review" <?= $row->approve_status === 'Under Review' ? 'selected' : '' ?>>Under Review</option>
                                        <option value="Approved" <?= $row->approve_status === 'Approved' ? 'selected' : '' ?>>Approved</option>
                                        <option value="Registered" <?= $row->approve_status === 'Registered' ? 'selected' : '' ?>>Registered</option>
                                        <option value="Rejected" <?= $row->approve_status === 'Rejected' ? 'selected' : '' ?>>Rejected</option>
                                    </select>
                                    <noscript><button type="submit" class="btn btn-xs btn-default">Update</button></noscript>
                                </form>
                            </td>
                            <td>
                                <!-- Edit & Approve: redirect to admin-student add form with prefill -->
                                <a class="btn btn-success btn-xs inline-block" style="padding: 8px" target="_blank" href="<?= home_url('admin-student'); ?>/?option=add&from_app=<?= (int)$row->applicationid ?>">Edit & Approve</a>

                                <!-- Delete -->
                                <form method="post" class="inline-block" onsubmit="return confirm('Delete this applicant?');">
                                    <input type="hidden" name="applicationid" value="<?= (int)$row->applicationid; ?>">
                                    <button type="submit" name="deleteApplicant" class="btn btn-danger btn-xs" style="padding: 8px">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<style>
.inline-block { display:inline-block; margin-right:4px; }
.status-form { min-width: 120px; }
.status-form select { 
    padding: 2px 5px;
    height: auto;
    line-height: 1.2;
}
.form-inline .form-group {
    margin-bottom: 10px;
}
.control-label {
    margin-right: 5px;
    font-weight: normal;
}
</style>

<?php get_footer(); ?>