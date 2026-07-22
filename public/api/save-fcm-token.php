<?php
// TEMP: surface real errors while debugging — remove display_errors in production
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../includes/config.php';
require_once '../../includes/database.php';

// Allow the Capacitor WebView (loaded from your Render URL) to call this API
header('Access-Control-Allow-Origin: https://barangaylink-2.onrender.com');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header('Content-Type: application/json');

$db = new Database();

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || empty($data['user_id']) || empty($data['fcm_token'])) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Missing required fields: user_id and fcm_token are required",
        "received" => $data
    ]);
    exit;
}

$user_id = (int)$data['user_id'];
$fcm_token = $db->escape($data['fcm_token']);
$device_type = $db->escape($data['device_type'] ?? 'android');

$result = $db->query("
    INSERT INTO user_devices (user_id, fcm_token, device_type)
    VALUES ($user_id, '$fcm_token', '$device_type')
    ON DUPLICATE KEY UPDATE last_active = NOW()
");

if ($result === false) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Database insert failed"
    ]);
    exit;
}

echo json_encode(["success" => true]);
