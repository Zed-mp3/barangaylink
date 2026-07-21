<?php

require_once '../../includes/config.php';
require_once '../../includes/database.php';

require_once '../../vendor/autoload.php';

use Kreait\Firebase\Factory;

$db = new Database();

$title = $_POST['title'];
$message = $_POST['message'];

$serviceAccount = '../../secure/barangaylink-c5e86-firebase-adminsdk-fbsvc-faac69ae05';

$factory = (new Factory)->withServiceAccount($serviceAccount);
$messaging = $factory->createMessaging();

$result = $db->query("SELECT fcm_token FROM user_devices");

while ($row = $result->fetch_assoc()) {

    $token = $row['fcm_token'];

    $messaging->send([
        'token' => $token,
        'notification' => [
            'title' => $title,
            'body' => $message
        ],
        'android' => [
            'priority' => 'high'
        ]
    ]);
}

echo "Notification sent";