<?php
/**
 * Auto-run SQL patch on new release.
 * Place this file in wp-content/mu-plugins/
 */

add_action('init', function() {
    global $wpdb;

    // 🏷️ Get release version (auto-detected from deployment or fallback)
    $release_file = WP_CONTENT_DIR . '/current_release.php';
    if ( file_exists( $release_file ) ) {
        require_once $release_file;
        $current_release = defined('CURRENT_RELEASE') ? constant('CURRENT_RELEASE') : 'dev-' . time();
    } else {
        $current_release = 'dev-' . time(); // Fallback for development
    }

    // 🧩 Create tracking table if it doesn't exist
    $table = $wpdb->prefix . 'release_patches';
    $wpdb->query("
        CREATE TABLE IF NOT EXISTS $table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            release_tag VARCHAR(50) NOT NULL,
            applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY release_tag (release_tag)
        )
    ");

    // Check if already applied
    $exists = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE release_tag = %s", $current_release) );

    if ( $exists > 0 ) {
        return; // already applied, skip
    }

    // 📦 Path to SQL patch file
    $sql_file = WP_CONTENT_DIR . '/database_patch.sql';
    if ( ! file_exists( $sql_file ) ) {
        error_log("Database patch file not found: $sql_file");
        return;
    }

    // 📖 Read & run SQL statements
    $sql = file_get_contents( $sql_file );

    // Split by semicolon (simple parser)
    $queries = array_filter(array_map('trim', explode(';', $sql)));
    foreach ( $queries as $query ) {
        if ( ! empty( $query ) ) {
            $result = $wpdb->query( $query );
            if ( $result === false ) {
                error_log("SQL Error in patch for release $current_release: " . $wpdb->last_error);
                error_log("Failed query: " . $query);
            }
        }
    }

    // 📝 Log that the patch was applied
    $wpdb->insert( $table, [
        'release_tag' => $current_release,
        'applied_at'  => current_time('mysql')
    ]);

    error_log("✅ Database patch applied for release $current_release");
});