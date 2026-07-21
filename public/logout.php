<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Check if this is an AJAX request for logout confirmation
if (isset($_GET['confirm']) && $_GET['confirm'] === 'true') {
    $auth = new Auth();
    $auth->logout();
    echo json_encode(['success' => true, 'redirect' => '../index.php']);
    exit();
}

// Handle regular logout
if (!isset($_GET['ajax'])) {
    $auth = new Auth();
    $auth->logout();
    
    // Redirect to the public index page (one level up from /user or /admin)
    $current_path = $_SERVER['PHP_SELF'];
    if (strpos($current_path, '/admin/') !== false) {
        redirect('../index.php'); // From admin folder to public index
    } else if (strpos($current_path, '/user/') !== false) {
        redirect('../index.php'); // From user folder to public index
    } else {
        redirect('index.php'); // Default fallback
    }
}
?>