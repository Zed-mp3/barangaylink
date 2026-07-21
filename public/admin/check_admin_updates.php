<?php
// public/admin/check_admin_updates.php
require_once '../../includes/config.php';
require_once '../../includes/ajax_auth.php';

// Require admin authentication
$auth = requireAjaxAuth('admin');
$db = new Database();

header('Content-Type: application/json');

// Get last update timestamp from request
$last_update = isset($_GET['lastUpdate']) ? (int)$_GET['lastUpdate'] : 0;

try {
    // Check for new announcements
    $new_announcements_sql = "SELECT COUNT(*) as count FROM announcements WHERE UNIX_TIMESTAMP(created_at) > $last_update";
    $new_announcements = $db->query($new_announcements_sql)->fetch_assoc()['count'];
    
    // Check for new requests
    $new_requests_sql = "SELECT COUNT(*) as count FROM service_requests WHERE UNIX_TIMESTAMP(created_at) > $last_update";
    $new_requests = $db->query($new_requests_sql)->fetch_assoc()['count'];
    
    // Check for updated requests
    $updated_requests_sql = "SELECT COUNT(*) as count FROM service_requests WHERE UNIX_TIMESTAMP(updated_at) > $last_update";
    $updated_requests = $db->query($updated_requests_sql)->fetch_assoc()['count'];
    
    // Check for new residents
    $new_residents_sql = "SELECT COUNT(*) as count FROM users WHERE user_type='resident' AND UNIX_TIMESTAMP(created_at) > $last_update";
    $new_residents = $db->query($new_residents_sql)->fetch_assoc()['count'];
    
    $total_updates = $new_announcements + $new_requests + $updated_requests + $new_residents;
    
    // Get current last update timestamp
    $current_last_update_sql = "SELECT MAX(GREATEST(
        COALESCE(UNIX_TIMESTAMP((
            SELECT MAX(created_at) FROM announcements
        )), 0),
        COALESCE(UNIX_TIMESTAMP((
            SELECT MAX(created_at) FROM service_requests
        )), 0),
        COALESCE(UNIX_TIMESTAMP((
            SELECT MAX(updated_at) FROM service_requests
        )), 0),
        COALESCE(UNIX_TIMESTAMP((
            SELECT MAX(created_at) FROM users WHERE user_type='resident'
        )), 0)
    )) as last_update";
    
    $current_result = $db->query($current_last_update_sql);
    $current_last_update = $current_result->fetch_assoc()['last_update'];
    
    echo json_encode([
        'success' => true,
        'has_updates' => $total_updates > 0,
        'count' => $total_updates,
        'new_announcements' => $new_announcements,
        'new_requests' => $new_requests,
        'updated_requests' => $updated_requests,
        'new_residents' => $new_residents,
        'last_update' => $current_last_update ? (int)$current_last_update : time()
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'has_updates' => false,
        'count' => 0
    ]);
}
?>