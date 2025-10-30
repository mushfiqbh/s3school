<?php
/**
 * Auto-run SQL patch on new release.
 * Place this file in wp-content/mu-plugins/
 */

add_action('init', function() {
    global $wpdb;

    // 🏷️ Detect current release tag directly from Git (no external file)
    $repo_dir = ABSPATH; // WordPress root (where .git usually lives)
    $current_release = null;

    if (is_dir($repo_dir . '/.git')) {
        // Try to get latest tag from Git
        $tag = trim(@shell_exec("cd " . escapeshellarg($repo_dir) . " && git describe --tags --abbrev=0 2>/dev/null"));
        if ($tag) {
            $current_release = $tag;
        } else {
            // Fallback to short commit hash
            $commit = trim(@shell_exec("cd " . escapeshellarg($repo_dir) . " && git rev-parse --short HEAD 2>/dev/null"));
            if ($commit) {
                $current_release = 'commit-' . $commit;
            }
        }
    }

    // Final fallback if Git not available
    if (!$current_release) {
        $current_release = 'dev-' . date('Ymd-His');
    }

    // 🧩 Create tracking table if it doesn't exist
    $table = $wpdb->prefix . 'release_patches';
    $wpdb->query("
        CREATE TABLE IF NOT EXISTS $table (
            id INT AUTO_INCREMENT PRIMARY KEY,
            release_tag VARCHAR(100) NOT NULL,
            applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY release_tag (release_tag)
        )
    ");

    // 🔍 Check if this release was already patched
    $exists = $wpdb->get_var(
        $wpdb->prepare("SELECT COUNT(*) FROM $table WHERE release_tag = %s", $current_release)
    );

    if ($exists > 0) {
        return; // already applied, skip
    }

    // 📦 Path to SQL patch file
    $sql_file = WP_CONTENT_DIR . '/database_patch.sql';
    if (!file_exists($sql_file)) {
        error_log("Database patch file not found: $sql_file");
        return;
    }

    // 📖 Read and execute SQL
    $sql = file_get_contents($sql_file);
    $queries = array_filter(array_map('trim', explode(';', $sql)));

    foreach ($queries as $query) {
        if (!empty($query)) {
            $result = $wpdb->query($query);
            if ($result === false) {
                error_log("❌ SQL Error in patch for $current_release: " . $wpdb->last_error);
                error_log("Failed query: " . $query);
            }
        }
    }

    // 📝 Log patch as applied
    $wpdb->insert($table, [
        'release_tag' => $current_release,
        'applied_at'  => current_time('mysql')
    ]);

    error_log("✅ Database patch applied for release: $current_release");
});
