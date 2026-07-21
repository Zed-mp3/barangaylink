<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'barangaylink_db');

// Application configuration
define('SITE_NAME', 'BarangayLink');
define('SITE_URL', 'https://barangaylink.sbs/public/');
define('UPLOAD_PATH', __DIR__ . '/../assets/');

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>