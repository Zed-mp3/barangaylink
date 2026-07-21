<?php
// public/admin/get_admin_dashboard_data.php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/ajax_auth.php';

// Require admin authentication
$auth = requireAjaxAuth('admin');
$db = new Database();

header('Content-Type: application/json');

try {
    // Get updated statistics
    $total_residents = $db->query("SELECT COUNT(*) as count FROM users WHERE user_type='resident'")->fetch_assoc()['count'];
    $total_requests = $db->query("SELECT COUNT(*) as count FROM service_requests")->fetch_assoc()['count'];
    $pending_requests = $db->query("SELECT COUNT(*) as count FROM service_requests WHERE status='pending'")->fetch_assoc()['count'];
    $total_announcements = $db->query("SELECT COUNT(*) as count FROM announcements")->fetch_assoc()['count'];
    
    // Get recent requests HTML
    $recent_requests = $db->query("SELECT sr.*, u.full_name, u.email 
                                   FROM service_requests sr 
                                   JOIN users u ON sr.user_id = u.id 
                                   ORDER BY sr.created_at DESC LIMIT 5");
    
    $requests_html = '';
    if ($recent_requests->num_rows > 0) {
        $requests_html = '<div class="table-responsive"><table class="table"><thead><tr>
            <th>ID</th><th>Resident</th><th>Type</th><th>Status</th><th>Date</th><th>Action</th>
        </tr></thead><tbody>';
        
        while($row = $recent_requests->fetch_assoc()) {
            $statusClass = '';
            switch($row['status']) {
                case 'pending': $statusClass = 'badge-pending'; break;
                case 'processing': $statusClass = 'badge-processing'; break;
                case 'completed': $statusClass = 'badge-completed'; break;
                case 'rejected': $statusClass = 'badge-rejected'; break;
            }
            
            $requests_html .= '<tr>
                <td>#' . $row['id'] . '</td>
                <td>' . htmlspecialchars($row['full_name']) . '</td>
                <td>' . htmlspecialchars($row['request_type']) . '</td>
                <td><span class="badge ' . $statusClass . '">' . ucfirst($row['status']) . '</span></td>
                <td>' . date('M d, Y', strtotime($row['created_at'])) . '</td>
                <td><a href="service_requests.php?view=' . $row['id'] . '" class="btn btn-primary btn-sm">View</a></td>
            </tr>';
        }
        
        $requests_html .= '</tbody></table></div>';
    } else {
        $requests_html = '<p class="text-center">No service requests yet.</p>';
    }
    
    // Get last update timestamp
    $last_update_sql = "SELECT MAX(GREATEST(
        COALESCE(UNIX_TIMESTAMP((
            SELECT MAX(created_at) FROM announcements
        )), 0),
        COALESCE(UNIX_TIMESTAMP((
            SELECT MAX(created_at) FROM service_requests
        )), 0),
        COALESCE(UNIX_TIMESTAMP((
            SELECT MAX(created_at) FROM users
        )), 0)
    )) as last_update";
    
    $last_update_result = $db->query($last_update_sql);
    $last_update = $last_update_result->fetch_assoc()['last_update'];
    
    echo json_encode([
        'success' => true,
        'total_residents' => $total_residents,
        'total_requests' => $total_requests,
        'pending_requests' => $pending_requests,
        'total_announcements' => $total_announcements,
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