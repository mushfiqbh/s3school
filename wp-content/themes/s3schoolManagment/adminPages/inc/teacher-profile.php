<?php
if (wp_get_current_user()->roles[0] != 'um_teachers') {
    wp_redirect(home_url());
    exit;
}

global $wpdb;

// Determine table name (try prefixed first, fallback to ct_teacher)
$prefixed = $wpdb->prefix . 'ct_teacher';
$exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $prefixed));
$table = ($exists === $prefixed) ? $prefixed : 'ct_teacher';

$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Fetch teacher record for current user
$teacher = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE tecUserId = %d LIMIT 1", $user_id));

// Handle profile save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_teacher_profile'])) {
    if (!isset($_POST['teacher_profile_nonce']) || !wp_verify_nonce($_POST['teacher_profile_nonce'], 'save_teacher_profile')) {
        wp_die('Security check failed');
    }

    if (!$teacher) {
        wp_die('Teacher profile not found.');
    }

    // Handle image upload
    $image_url = $teacher->teacherImg; // Default to existing image
    if (!empty($_FILES['teacherImg']['name'])) {
        // Process file upload
        $upload_dir = wp_upload_dir();
        $file_name = sanitize_file_name($_FILES['teacherImg']['name']);
        $file_tmp = $_FILES['teacherImg']['tmp_name'];
        $file_size = $_FILES['teacherImg']['size'];
        $file_error = $_FILES['teacherImg']['error'];

        // Validate file
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
        $file_type = wp_check_filetype($file_name);

        if ($file_error === UPLOAD_ERR_OK && in_array($file_type['type'], $allowed_types) && $file_size <= 2 * 1024 * 1024) { // 2MB limit
            // Generate unique filename
            $unique_name = 'teacher_' . $user_id . '_' . time() . '.' . $file_type['ext'];
            $upload_path = $upload_dir['path'] . '/' . $unique_name;
            $upload_url = $upload_dir['url'] . '/' . $unique_name;

            if (move_uploaded_file($file_tmp, $upload_path)) {
                // Delete old image if it exists and is not a default avatar
                if (!empty($teacher->teacherImg) && strpos($teacher->teacherImg, 'wp-content/uploads') !== false) {
                    $old_image_path = str_replace($upload_dir['url'], $upload_dir['path'], $teacher->teacherImg);
                    if (file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }
                }
                $image_url = $upload_url;
            } else {
                $save_message = 'Error uploading image.';
            }
        } else {
            $save_message = 'Invalid image file. Please upload a JPEG, PNG, GIF, or WebP file under 2MB.';
        }
    }

    // Collect and sanitize fields we allow to edit
    $data = array(
        'teacherName' => sanitize_text_field($_POST['teacherName'] ?? ''),
        'teacherPhone' => sanitize_text_field($_POST['teacherPhone'] ?? ''),
        'teacherDesignation' => sanitize_text_field($_POST['teacherDesignation'] ?? ''),
        'teacherPresent' => sanitize_text_field($_POST['teacherPresent'] ?? ''),
        'teacherPermanent' => sanitize_text_field($_POST['teacherPermanent'] ?? ''),
        'teacherJoining' => sanitize_text_field($_POST['teacherJoining'] ?? ''),
        'teacherImg' => esc_url_raw($image_url),
    );

    $formats = array('%s', '%s', '%s', '%s', '%s', '%s', '%s');

    $where = array('teacherid' => $teacher->teacherid);
    $where_format = array('%d');

    $updated = $wpdb->update($table, $data, $where, $formats, $where_format);

    // reload teacher after update
    $teacher = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE teacherid = %d LIMIT 1", $teacher->teacherid));

    if ($updated !== false) {
        // Add a transient or query var to show success message — we'll set a simple flag
        $save_message = isset($save_message) ? $save_message : 'Profile updated successfully.';
    } else {
        $save_message = isset($save_message) ? $save_message : 'No changes saved.';
    }
}

?>

<style>
    /* Compact profile card styles */
    .teacher-card {
        margin: 20px auto;
        border: 1px solid #e1e1e1;
        padding: 16px;
        border-radius: 6px;
        display: flex;
        gap: 16px;
        align-items: flex-start;
        background: #fff
    }

    .teacher-avatar {
        width: 96px;
        height: 96px;
        border-radius: 6px;
        overflow: hidden;
        flex: 0 0 96px;
    }

    .teacher-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .teacher-main {
        flex: 1
    }

    .teacher-main h2 {
        margin: 0 0 6px;
        font-size: 20px
    }

    .teacher-meta {
        color: #555;
        font-size: 14px;
        margin-bottom: 8px
    }

    .teacher-actions {
        margin-top: 10px
    }

    .btn {
        display: inline-block;
        padding: 8px 12px;
        border-radius: 4px;
        border: 1px solid #0073aa;
        background: #0073aa;
        color: #fff;
        text-decoration: none
    }

    .btn.secondary {
        background: #f1f1f1;
        color: #333;
        border-color: #ccc
    }

    .teacher-details {
        margin-top: 10px;
        font-size: 14px
    }

    .profile-form {
        display: none;
        margin-top: 12px
    }

    .profile-form input[type=text],
    .profile-form textarea,
    .profile-form input[type=file] {
        width: 100%;
        padding: 8px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box
    }

    .image-preview {
        margin-top: 10px;
        max-width: 200px;
        max-height: 200px;
        border: 1px solid #ddd;
        border-radius: 4px;
        display: none;
    }

    .field-row {
        margin-bottom: 8px
    }

    .save-msg {
        margin: 10px 0;
        color: green
    }

    @media (max-width:600px) {
        .teacher-card {
            flex-direction: column;
            align-items: center;
            text-align: center
        }

        .teacher-main {
            text-align: center
        }
    }
</style>

<div class="teacher-card">
    <div class="teacher-avatar">
        <?php
        // Prefer stored teacherImg, fall back to WP avatar
        if (!empty($teacher) && !empty($teacher->teacherImg)) {
            echo '<img src="' . esc_url($teacher->teacherImg) . '" alt="' . esc_attr($teacher->teacherName) . '">';
        } else {
            echo get_avatar($user_id, 96);
        }
        ?>
    </div>

    <div class="teacher-main">
        <?php if ($teacher): ?>
            <div style="display: flex;align-items: center;justify-content: space-between;">
                <div>
                    <h2><?php echo esc_html($teacher->teacherName); ?></h2>
                    <div class="teacher-meta"><?php echo esc_html($teacher->teacherDesignation); ?> &middot; Joined: <?php echo esc_html($teacher->teacherJoining); ?></div>

                    <div><strong>Phone:</strong> <?php echo esc_html($teacher->teacherPhone); ?></div>
                    <div><strong>Present Address:</strong> <?php echo esc_html($teacher->teacherPresent); ?></div>

                    <?php if (!empty($save_message)): ?>
                        <div class="save-msg"><?php echo esc_html($save_message); ?></div>
                    <?php endif; ?>
                </div>

                <div class="teacher-details">
                    <?php
                    $assignedSubjects = json_decode($teacher->tecAssignSub, true);
                    $assignedSections = json_decode($teacher->assignSection, true);
                    if (!empty($assignedSubjects)) {
                        echo '<div><strong>Assigned Classes/Sections/Subjects:</strong></div>';
                        foreach ($assignedSubjects as $index => $subjectId) {
                            $subject = $wpdb->get_row($wpdb->prepare("SELECT subjectName, subjectClass FROM ct_subject WHERE subjectid = %d", $subjectId));
                            if ($subject) {
                                $class = $wpdb->get_row($wpdb->prepare("SELECT className FROM ct_class WHERE classid = %d", $subject->subjectClass));
                                $sectionId = isset($assignedSections[$index]) ? $assignedSections[$index] : null;
                                $section = $sectionId ? $wpdb->get_row($wpdb->prepare("SELECT sectionName FROM ct_section WHERE sectionid = %d", $sectionId)) : null;
                                echo '<div>- Class: ' . esc_html($class->className ?? 'N/A') . ', Section: ' . esc_html($section->sectionName ?? 'N/A') . ', Subject: ' . esc_html($subject->subjectName) . '</div>';
                            }
                        }
                    } else {
                        echo '<div><strong>Subjects:</strong> None assigned</div>';
                    }

                    // Display class teacher information
                    if (!empty($teacher->teacherOfClass) && !empty($teacher->teacherOfSection)) {
                        $classTeacherClass = $wpdb->get_row($wpdb->prepare("SELECT className FROM ct_class WHERE classid = %d", $teacher->teacherOfClass));
                        $classTeacherSection = $wpdb->get_row($wpdb->prepare("SELECT sectionName FROM ct_section WHERE sectionid = %d", $teacher->teacherOfSection));
                        if ($classTeacherClass && $classTeacherSection) {
                            echo '<div><strong>Class Teacher For:</strong> ' . esc_html($classTeacherClass->className) . ' - ' . esc_html($classTeacherSection->sectionName) . '</div>';
                        }
                    }
                    ?>
                </div>
            </div>

            <div class="teacher-actions">
                <a href="#" id="editProfileBtn" class="btn">Edit profile</a>
            </div>

            <form method="post" enctype="multipart/form-data" class="profile-form" id="teacherProfileForm">
                <?php wp_nonce_field('save_teacher_profile', 'teacher_profile_nonce'); ?>
                <input type="hidden" name="save_teacher_profile" value="1">

                <div class="field-row">
                    <label>Name</label>
                    <input type="text" name="teacherName" value="<?php echo esc_attr($teacher->teacherName); ?>">
                </div>

                <div class="field-row">
                    <label>Designation</label>
                    <input type="text" name="teacherDesignation" value="<?php echo esc_attr($teacher->teacherDesignation); ?>">
                </div>

                <div class="field-row">
                    <label>Phone</label>
                    <input type="text" name="teacherPhone" value="<?php echo esc_attr($teacher->teacherPhone); ?>">
                </div>

                <div class="field-row">
                    <label>Joining Date</label>
                    <input type="text" name="teacherJoining" value="<?php echo esc_attr($teacher->teacherJoining); ?>">
                </div>

                <div class="field-row">
                    <label>Present Address</label>
                    <textarea name="teacherPresent"><?php echo esc_textarea($teacher->teacherPresent); ?></textarea>
                </div>

                <div class="field-row">
                    <label>Permanent Address</label>
                    <textarea name="teacherPermanent"><?php echo esc_textarea($teacher->teacherPermanent); ?></textarea>
                </div>

                <div class="field-row">
                    <label>Profile Image</label>
                    <input type="file" name="teacherImg" accept="image/*" id="teacherImgInput">
                    <small>Accepted formats: JPEG, PNG, GIF, WebP. Max size: 2MB</small>
                    <div id="imagePreview" class="image-preview"></div>
                    <?php if (!empty($teacher->teacherImg)): ?>
                        <div style="margin-top: 5px;">
                            <small>Current image: <a href="<?php echo esc_url($teacher->teacherImg); ?>" target="_blank">View</a></small>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="margin-top:8px">
                    <button type="submit" class="btn">Save</button>
                    <a href="#" id="cancelEditBtn" class="btn secondary">Cancel</a>
                </div>
            </form>

        <?php else: ?>
            <h2>No profile found</h2>
            <p>We couldn't find a teacher profile for your account. Please contact an administrator to create your profile.</p>
        <?php endif; ?>
    </div>
</div>

<script>
    (function() {
        var editBtn = document.getElementById('editProfileBtn');
        var form = document.getElementById('teacherProfileForm');
        var cancelBtn = document.getElementById('cancelEditBtn');
        if (editBtn && form) {
            editBtn.addEventListener('click', function(e) {
                e.preventDefault();
                form.style.display = form.style.display === 'block' ? 'none' : 'block';
                window.scrollTo({
                    top: form.offsetTop - 20,
                    behavior: 'smooth'
                });
            });
        }
        if (cancelBtn && form) {
            cancelBtn.addEventListener('click', function(e) {
                e.preventDefault();
                form.style.display = 'none';
            });
        }

        // Image preview functionality
        var imgInput = document.getElementById('teacherImgInput');
        var imgPreview = document.getElementById('imagePreview');
        if (imgInput && imgPreview) {
            imgInput.addEventListener('change', function(e) {
                var file = e.target.files[0];
                if (file) {
                    var reader = new FileReader();
                    reader.onload = function(e) {
                        imgPreview.innerHTML = '<img src="' + e.target.result + '" style="max-width: 100%; max-height: 200px;">';
                        imgPreview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                } else {
                    imgPreview.style.display = 'none';
                    imgPreview.innerHTML = '';
                }
            });
        }
    })();
</script>