<?php
/**
* Template Name: Student Image Upload
*/
get_header();

/* Handle form submission */
$uploaded_image_url = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['custom_image'])) {

    if (!function_exists('wp_handle_upload')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    $uploadedfile = $_FILES['custom_image'];

    $upload_overrides = [
        'test_form' => false,
        'mimes' => [
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp'
        ]
    ];

    $movefile = wp_handle_upload($uploadedfile, $upload_overrides);

    if ($movefile && !isset($movefile['error'])) {
        if ($movefile && !isset($movefile['error'])) {
          $uploaded_image_url = $movefile['url'];

          // --- Register image in database ---
          $filename = basename($movefile['file']);
          $filetype = wp_check_filetype($filename, null);

          $attachment = [
              'guid'           => $uploaded_image_url,
              'post_mime_type' => $filetype['type'],
              'post_title'     => sanitize_file_name($filename),
              'post_content'   => '',
              'post_status'    => 'inherit'
          ];

          // Insert attachment into database
          $attach_id = wp_insert_attachment($attachment, $movefile['file']);

          // Include image.php to generate metadata
          require_once ABSPATH . 'wp-admin/includes/image.php';
          $attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
          wp_update_attachment_metadata($attach_id, $attach_data);
      }

    } else {
        echo '<p style="color:red;">Upload error: ' . esc_html($movefile['error']) . '</p>';
    }
}
?>

<button id="openUploadModal">Upload Image</button>

<?php if ($uploaded_image_url): ?>
    <p>Uploaded Image:</p>
    <img src="<?php echo esc_url($uploaded_image_url); ?>" style="max-width:300px;">
<?php endif; ?>

<!-- MODAL -->
<div id="uploadModal">
    <div id="uploadModalContent">
        <span id="closeUploadModal">&times;</span>

        <h3>Upload Image</h3>

        <form method="post" enctype="multipart/form-data">
            <input type="file" name="custom_image" accept="image/*" required>
            <br><br>
            <button type="submit">Upload</button>
        </form>
    </div>
</div>

<style>
#uploadModal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.6);
    z-index: 9999;
}
#uploadModalContent {
    background: #fff;
    width: 400px;
    padding: 20px;
    margin: 10% auto;
    border-radius: 6px;
    position: relative;
}
#closeUploadModal {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 20px;
    cursor: pointer;
}
</style>

<script>
document.getElementById('openUploadModal').onclick = function() {
    document.getElementById('uploadModal').style.display = 'block';
};
document.getElementById('closeUploadModal').onclick = function() {
    document.getElementById('uploadModal').style.display = 'none';
};
</script>

<?php get_footer(); ?>
