<?php
// Database configuration

define('DB_HOST', getenv('DB_HOST') ?: 'sakura.proxy.rlwy.net');
define('DB_PORT', getenv('DB_PORT') ?: '29508');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: 'nvwQMmIyFqDwuJjwYXHbadqcGrpFUbeu');
define('DB_NAME', getenv('DB_NAME') ?: 'railway');

// Application configuration
define('SITE_NAME', 'BarangayLink');
define('SITE_URL', 'https://barangaylink-2.onrender.com');
define('UPLOAD_PATH', __DIR__ . '/../assets/');

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
