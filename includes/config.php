<?php
// Optional local overrides for XAMPP development (gitignored).
// Copy includes/config.local.example.php to includes/config.local.php and fill it in.
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
}

// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'barangaylink_db');

// Application configuration
define('SITE_NAME', 'BarangayLink');
define('SITE_URL', rtrim(getenv('SITE_URL') ?: 'https://barangaylink-2.onrender.com', '/'));

// Gmail API (Google Cloud) — sends verification and password reset emails.
// Set these as environment variables (Render dashboard) — see EMAIL_SETUP.md.
define('GMAIL_CLIENT_ID', getenv('GMAIL_CLIENT_ID') ?: '');
define('GMAIL_CLIENT_SECRET', getenv('GMAIL_CLIENT_SECRET') ?: '');
define('GMAIL_REFRESH_TOKEN', getenv('GMAIL_REFRESH_TOKEN') ?: '');
define('GMAIL_SENDER', getenv('GMAIL_SENDER') ?: '');            // the Gmail address that sends the mail
define('GMAIL_SENDER_NAME', getenv('GMAIL_SENDER_NAME') ?: 'BarangayLink');

// Email token settings
define('VERIFY_TOKEN_TTL_MIN', 1440);   // verification link valid for 24 hours
define('RESET_TOKEN_TTL_MIN', 60);      // password reset link valid for 60 minutes
define('EMAIL_RESEND_COOLDOWN_SEC', 60); // wait between emails of the same type per user
define('UPLOAD_PATH', __DIR__ . '/../assets/');

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
