<?php

require_once '../../includes/config.php';
require_once '../../includes/database.php';

header("Content-Type: application/json");

$db = new Database();

$data = json_decode(file_get_contents("php://input"), true);

$user_id = (int)$data['user_id'];
$fcm_token = $db->escape($data['fcm_token']);

$db->query("
DELETE FROM user_devices
WHERE user_id = $user_id
AND fcm_token = '$fcm_token'
");

echo json_encode([
    "success" => true
]);