<?php
// includes/ajax_auth.php
require_once 'config.php';
require_once 'auth.php';

function requireAjaxAuth($required_type = null) {
    $auth = new Auth();
    
    if (!$auth->isLoggedIn()) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated', 'redirect' => '../login.php']);
        exit();
    }
    
    if ($required_type) {
        if (($required_type == 'admin' && !$auth->isAdmin()) ||
            ($required_type == 'user' && $auth->isAdmin())) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized access']);
            exit();
        }
    }
    
    return $auth;
}
?>