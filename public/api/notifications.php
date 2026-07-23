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
    case 'check':
        // Used by request_status.php's foreground polling — returns unread
        // notifications from the `notifications` table (id greater than the
        // last one the client has already seen).
        $userId = $user['id'];
        $lastId = (int)($_GET['lastId'] ?? 0);

        $sql = "SELECT id, type, title, message, data, created_at
                FROM notifications
                WHERE user_id = $userId
                AND id > $lastId
                ORDER BY id ASC";
        $result = $db->query($sql);

        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $row['data'] = $row['data'] ? json_decode($row['data'], true) : null;
            $notifications[] = $row;
        }

        echo json_encode([
            'success' => true,
            'notifications' => $notifications
        ]);
        break;

    case 'mark_read':
        // Called via POST with id= in the body
        $userId = $user['id'];
        $id = (int)($_POST['id'] ?? 0);

        if (!$id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Missing id']);
            break;
        }

        // Scope to this user's own notifications only
        $db->query("UPDATE notifications SET is_read = 1 WHERE id = $id AND user_id = $userId");

        echo json_encode(['success' => true]);
        break;

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
