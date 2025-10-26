# Project: barnomala-wordpress-eims (ziisc)

This repository contains a WordPress site (theme: `s3schoolManagment`) used for school management. These instructions cover quick deployment steps for a fresh server or restoring to another environment. They include the required edits to `.htaccess`, the database constants in `wp-config.php`, and steps to upload/restore the old `uploads` folder in `wp-content`.

## Prerequisites

- PHP (version compatible with your WordPress release)
- MySQL / MariaDB
- Web server (Apache recommended for `.htaccess` rules)
- WP files from this repo deployed to your webroot (e.g., `c:\xampp\htdocs\ziisc`)
- Database dump exported from source (or credentials to an existing DB)
- The `wp-content/uploads` folder backup (old uploads folder)

## Quick deploy checklist

1. Copy repository files to your web server document root (for local XAMPP this is usually `c:\xampp\htdocs\ziisc`).
2. Create a database and user for WordPress, or prepare your existing DB credentials.
3. Import the database dump (if you have one) into the created database.
4. Edit `wp-config.php` and set database constants.
5. Ensure `.htaccess` is configured for pretty permalinks.
6. Upload the old `wp-content/uploads` folder preserving the folder structure and permissions.
7. Visit site and adjust Site URL / Home URL if the domain changed.

## Edit `wp-config.php` (required)

Open the `wp-config.php` file in the project root and update the database definitions. Replace the values below with your database name, user and password:

```php
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASSWORD', 'your_database_password');
define('DB_HOST', 'localhost'); // or your DB host
```

- If you are restoring from another domain, update the authentication unique keys and salts. You can generate new ones at: https://api.wordpress.org/secret-key/1.1/salt/
- (Optional) To enable debug temporarily while troubleshooting, set:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Remember to turn `WP_DEBUG` off on production by setting it to `false` after troubleshooting.

## `.htaccess` (permalinks / rewrite rules)

If your server uses Apache, place an `.htaccess` file in the WordPress root (next to `wp-config.php`) with the standard WordPress rewrite rules. Example:

```
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
```

- If your WordPress installation is in a subdirectory, adjust `RewriteBase` accordingly (for example `RewriteBase /ziisc/`).
- If using other server setups (Nginx, IIS), configure equivalent rewrite rules for pretty permalinks.

## Restoring / Uploading the old `uploads` folder

1. Locate your backup of the original `wp-content/uploads` folder (it should contain year/month subfolders like `uploads/2023/06`).
2. Upload/paste the whole `uploads` folder into `wp-content` of this project so the final path is `wp-content/uploads/...`.
3. Preserve the folder structure and file names; WordPress expects the same paths.
4. Set correct filesystem permissions so the webserver can read the files. On Windows/XAMPP this usually works by default. On Linux run:

```bash
# example for Linux environments (run as sudo)
chown -R www-data:www-data /path/to/site/wp-content/uploads
find /path/to/site/wp-content/uploads -type d -exec chmod 755 {} \;
find /path/to/site/wp-content/uploads -type f -exec chmod 644 {} \;
```

## Importing the database (common options)

- Using phpMyAdmin: Upload your SQL dump file and import to the DB you created.
- Using command line (Windows cmd example):

```bat
mysql -u your_db_user -p your_db_name < C:\path\to\dump.sql
```

- Using WP-CLI (recommended if available):

```bat
wp db import C:\path\to\dump.sql --path=C:\xampp\htdocs\ziisc
```

After import:
- If domain changed, update siteurl/home in the `wp_options` table using WP-CLI or an SQL query. With WP-CLI:

```bat
wp option update home 'https://your-new-domain.example' --path=C:\xampp\htdocs\ziisc
wp option update siteurl 'https://your-new-domain.example' --path=C:\xampp\htdocs\ziisc
```

Or with SQL (change values appropriately):

```sql
UPDATE wp_options SET option_value = 'https://your-new-domain.example' WHERE option_name = 'siteurl';
UPDATE wp_options SET option_value = 'https://your-new-domain.example' WHERE option_name = 'home';
```

If your DB uses a table prefix different than `wp_`, adjust the SQL accordingly.

## Post-deploy checks

1. Visit the site home page. If you see errors, check `wp-config.php` DB credentials and `WP_DEBUG` log at `wp-content/debug.log`.
2. Log into the WordPress admin area and go to Settings → Permalinks and simply click Save to rebuild rewrite rules.
3. Verify uploaded media displays correctly (images should reference URLs under `wp-content/uploads/...`).
4. If any links point to the old domain, run a search-and-replace with WP-CLI or a plugin (use the serialized-safe search/replace):

```bat
wp search-replace 'https://old-domain.example' 'https://new-domain.example' --skip-columns=guid --path=C:\xampp\htdocs\ziisc
```

## Troubleshooting

- White screen / 500 error: Check PHP error logs and enable `WP_DEBUG` temporarily.
- Missing images after uploads restore: confirm files were uploaded to `wp-content/uploads` and the database references match the files. If necessary run `wp media regenerate`.
- Permalink 404s: go to Settings → Permalinks and Save to refresh `.htaccess` rules.

## Backups & Rollback

- Always keep a copy of the original `wp-config.php` and `.htaccess` before editing.
- Keep a copy of your database dump and the `uploads` folder in a safe backup location.

## Security notes

- Never expose `wp-config.php` or your DB credentials publicly. Use secure channels when transferring DB dumps.
- Remove any debugging or test accounts after deployment.

## Contact / Next steps

If you want I can:
- Add a deployment script (PowerShell or batch) to automate DB import and uploads restore for Windows/XAMPP.
- Update `.htaccess` automatically based on install path.
- Add a small troubleshooting checklist in the admin page.

---

Place any project-specific notes below this line.

