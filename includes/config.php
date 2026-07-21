<?php
// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'barangaylink_db');

// Application configuration
define('SITE_NAME', 'BarangayLink');
define('SITE_URL', 'https://barangaylink-2.onrender.com');
define('UPLOAD_PATH', __DIR__ . '/../assets/');

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
