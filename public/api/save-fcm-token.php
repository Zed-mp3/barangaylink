<?php

require_once '../../includes/config.php';
require_once '../../includes/database.php';

$db = new Database();

$data = json_decode(file_get_contents("php://input"), true);

$user_id = (int)$data['user_id'];
$fcm_token = $db->escape($data['fcm_token']);
$device_type = $db->escape($data['device_type']);

$db->query("
INSERT INTO user_devices (user_id, fcm_token, device_type)
VALUES ($user_id, '$fcm_token', '$device_type')
ON DUPLICATE KEY UPDATE last_active = NOW()
");

echo json_encode(["success"=>true]);