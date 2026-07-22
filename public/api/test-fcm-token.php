<?php
// public/api/test-fcm-token.php
require_once '../../includes/config.php';
require_once '../../includes/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$db = new Database();

// Get all user devices
$result = $db->query("
    SELECT ud.*, u.full_name, u.email 
    FROM user_devices ud 
    LEFT JOIN users u ON ud.user_id = u.id 
    ORDER BY ud.last_active DESC
");

$tokens = [];
while ($row = $result->fetch_assoc()) {
    $tokens[] = $row;
}

echo json_encode([
    'success' => true,
    'count' => count($tokens),
    'tokens' => $tokens
]);
?>
