<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/functions.php'; // for FIREBASE_SERVICE_ACCOUNT_FILENAME
require_once '../../vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

$db = new Database();

// Get input
$title = $_POST['title'] ?? 'Barangay Announcement';
$message = $_POST['message'] ?? '';

if (empty($message)) {
    echo json_encode(['error' => 'Message is required']);
    exit;
}

// Same resolution logic as functions.php — env var first (Render), falls
// back to local secure/ folder for local/XAMPP development.
$serviceAccount = getFirebaseServiceAccount();

if ($serviceAccount === null) {
    echo json_encode(['error' => 'Firebase service account not available (no env var or local file)']);
    exit;
}

// Initialize Firebase
try {
    $factory = (new Factory)->withServiceAccount($serviceAccount);
    $messaging = $factory->createMessaging();
} catch (Exception $e) {
    echo json_encode(['error' => 'Firebase initialization failed: ' . $e->getMessage()]);
    exit;
}

// Get all FCM tokens
$result = $db->query("SELECT fcm_token FROM user_devices WHERE fcm_token IS NOT NULL AND fcm_token != ''");

if (!$result || $result->num_rows === 0) {
    echo json_encode(['error' => 'No device tokens found']);
    exit;
}

$successCount = 0;
$failedTokens = [];

while ($row = $result->fetch_assoc()) {
    $token = $row['fcm_token'];
    
    try {
        // Create notification
        $notification = Notification::create($title, $message);
        
        // Create message
        $cloudMessage = CloudMessage::withTarget('token', $token)
            ->withNotification($notification)
            ->withData(['click_action' => 'FLUTTER_NOTIFICATION_CLICK'])
            ->withAndroidConfig([
                'priority' => 'high',
                'notification' => [
                    'sound' => 'default',
                    'channel_id' => 'barangay_channel'
                ]
            ])
            ->withApnsConfig([
                'headers' => [
                    'apns-priority' => '10',
                ],
                'payload' => [
                    'aps' => [
                        'sound' => 'default',
                        'badge' => 1,
                    ],
                ],
            ]);
        
        // Send message
        $messaging->send($cloudMessage);
        $successCount++;
        
    } catch (Kreait\Firebase\Exception\Messaging\InvalidMessage $e) {
        // Invalid token - remove it
        $db->query("DELETE FROM user_devices WHERE fcm_token = '" . $db->escape($token) . "'");
        $failedTokens[] = $token;
        error_log("Invalid FCM token removed: " . $e->getMessage());
        
    } catch (Kreait\Firebase\Exception\Messaging\NotFound $e) {
        // Token not found - remove it
        $db->query("DELETE FROM user_devices WHERE fcm_token = '" . $db->escape($token) . "'");
        $failedTokens[] = $token;
        error_log("FCM token not found: " . $e->getMessage());
        
    } catch (Exception $e) {
        $failedTokens[] = $token;
        error_log("FCM send error: " . $e->getMessage());
    }
}

// Response
echo json_encode([
    'success' => true,
    'sent' => $successCount,
    'failed' => count($failedTokens),
    'message' => "Notification sent to $successCount device(s)"
]);
