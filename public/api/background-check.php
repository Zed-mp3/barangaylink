<?php
require_once '../../includes/config.php';

header('Content-Type: application/json');

$db = new Database();

// Get announcements from last 24 hours
$announcements_sql = "SELECT id, title, created_at 
                     FROM announcements 
                     WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                     ORDER BY created_at DESC";
$announcements = $db->query($announcements_sql);

$newAnnouncements = [];
while($row = $announcements->fetch_assoc()) {
    $newAnnouncements[] = $row;
}

// Get unread notifications from last 24 hours
$notifications_sql = "SELECT n.*, u.full_name 
                      FROM notifications n
                      JOIN users u ON n.user_id = u.id
                      WHERE n.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                      AND n.is_read = 0
                      ORDER BY n.created_at DESC";
$notifications = $db->query($notifications_sql);

$newNotifications = [];
while($row = $notifications->fetch_assoc()) {
    $row['data'] = json_decode($row['data'], true);
    $newNotifications[] = $row;
}

echo json_encode([
    'newAnnouncements' => $newAnnouncements,
    'newNotifications' => $newNotifications,
    'timestamp' => time()
]);