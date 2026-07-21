<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$db = new Database();
$user = $auth->getCurrentUser();
$action = $_GET['action'] ?? '';

switch($action) {
    case 'check_announcements':
        $lastId = $_GET['lastId'] ?? 0;
        $sql = "SELECT * FROM announcements WHERE id > $lastId ORDER BY id DESC";
        $result = $db->query($sql);
        
        $newAnnouncements = [];
        while($row = $result->fetch_assoc()) {
            $newAnnouncements[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'new' => !empty($newAnnouncements),
            'announcements' => $newAnnouncements
        ]);
        break;
        
    case 'check_requests':
        $userId = $user['id'];
        $lastUpdate = $_GET['lastUpdate'] ?? 0;
        
        $sql = "SELECT * FROM service_requests 
                WHERE user_id = $userId 
                AND UNIX_TIMESTAMP(updated_at) > $lastUpdate
                ORDER BY updated_at DESC";
        $result = $db->query($sql);
        
        $updates = [];
        while($row = $result->fetch_assoc()) {
            $updates[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'updates' => $updates,
            'count' => count($updates)
        ]);
        break;
        
    default:
        echo json_encode(['error' => 'Invalid action']);
}