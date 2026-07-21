<?php
// public/user/get_dashboard_data.php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/ajax_auth.php';

// Require user authentication
$auth = requireAjaxAuth('user');
$db = new Database();
$user = $auth->getCurrentUser();

header('Content-Type: application/json');

try {
    // Get updated statistics
    $total_requests = $db->query("SELECT COUNT(*) as count FROM service_requests WHERE user_id = {$user['id']}")->fetch_assoc()['count'];
    $pending_requests = $db->query("SELECT COUNT(*) as count FROM service_requests WHERE user_id = {$user['id']} AND status='pending'")->fetch_assoc()['count'];
    
    // Get recent announcements
    $announcements = $db->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 3");
    $announcements_html = '';
    if ($announcements->num_rows > 0) {
        while($row = $announcements->fetch_assoc()) {
            $announcements_html .= '
                <div class="announcement-item" data-id="' . $row['id'] . '">
                    <h4>' . htmlspecialchars($row['title']) . '</h4>
                    <p>' . htmlspecialchars(substr($row['content'], 0, 100)) . '...</p>
                    <small>' . timeAgo($row['created_at']) . '</small>
                </div>
            ';
        }
    } else {
        $announcements_html = '<p>No announcements yet.</p>';
    }
    
    // Get recent requests
    $requests = $db->query("SELECT * FROM service_requests WHERE user_id = {$user['id']} ORDER BY created_at DESC LIMIT 5");
    $requests_html = '';
    if ($requests->num_rows > 0) {
        while($row = $requests->fetch_assoc()) {
            $status_badge = getStatusBadge($row['status']);
            $requests_html .= '
                <div class="request-card" data-id="' . $row['id'] . '">
                    <div class="request-header">
                        <span class="request-type">' . htmlspecialchars($row['request_type']) . '</span>
                        ' . $status_badge . '
                    </div>
                    <p>' . htmlspecialchars(substr($row['description'], 0, 50)) . '...</p>
                    <small>' . timeAgo($row['created_at']) . '</small>
                </div>
            ';
        }
    } else {
        $requests_html = '<p>No service requests yet.</p><a href="service_request.php" class="btn btn-success">Request Service</a>';
    }
    
    // Get last update timestamp
    $last_update_sql = "SELECT MAX(GREATEST(
        COALESCE(UNIX_TIMESTAMP((
            SELECT MAX(created_at) FROM announcements
        )), 0),
        COALESCE(UNIX_TIMESTAMP((
            SELECT MAX(created_at) FROM service_requests WHERE user_id = {$user['id']}
        )), 0)
    )) as last_update";
    
    $last_update_result = $db->query($last_update_sql);
    $last_update = $last_update_result->fetch_assoc()['last_update'];
    
    echo json_encode([
        'success' => true,
        'total_requests' => $total_requests,
        'pending_requests' => $pending_requests,
        'announcements_html' => $announcements_html,
        'requests_html' => $requests_html,
        'last_update' => $last_update ? (int)$last_update : time()
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load dashboard data'
    ]);
}
?>