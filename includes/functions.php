<?php

function redirect($url) {
    header("Location: $url");
    exit();
}

function displayMessage($message, $type = 'success') {
    $class = $type == 'success' ? 'alert-success' : 'alert-danger';
    return "<div class='alert $class'>$message</div>";
}

function getStatusBadge($status) {
    $colors = [
        'pending' => 'warning',
        'in_progress' => 'info',
        'completed' => 'success',
        'rejected' => 'danger',
        'active' => 'success',
        'inactive' => 'secondary'
    ];
    $color = $colors[$status] ?? 'secondary';
    return "<span class='badge bg-$color'>$status</span>";
}

function timeAgo($timestamp) {
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;

    $minutes = round($time_difference / 60);
    $hours = round($time_difference / 3600);
    $days = round($time_difference / 86400);

    if ($time_difference <= 60) return "Just now";
    if ($minutes <= 60) return "$minutes minutes ago";
    if ($hours <= 24) return "$hours hours ago";
    return "$days days ago";
}


/*
|--------------------------------------------------------------------------
| FIREBASE PUSH NOTIFICATION FUNCTION
|--------------------------------------------------------------------------
*/

// TODO: replace with your actual filename from Firebase Console — used only
// as a fallback for local/XAMPP development where no env var is set.
define('FIREBASE_SERVICE_ACCOUNT_FILENAME', 'barangaylink-c5e86-firebase-adminsdk-fbsvc-faac69ae05.json');

function sendFCMNotification(array $tokens, array $notificationData, array $data = [])
{
    if (empty($tokens)) return false;

    require __DIR__ . '/../vendor/autoload.php';

    $serviceAccount = getFirebaseServiceAccount();
    if ($serviceAccount === null) {
        error_log("FCM ERROR: no Firebase service account available (env var or local file)");
        return false;
    }

    $factory = (new \Kreait\Firebase\Factory)
        ->withServiceAccount($serviceAccount);

    $messaging = $factory->createMessaging();

    foreach ($tokens as $token) {

        try {

            $message = \Kreait\Firebase\Messaging\CloudMessage::withTarget('token', $token)
                ->withNotification(
                    \Kreait\Firebase\Messaging\Notification::create(
                        $notificationData['title'],
                        $notificationData['body']
                    )
                );

            if (!empty($data)) {
                $message = $message->withData(array_map('strval', $data));
            }

            $messaging->send($message);

        } catch (\Throwable $e) {
            error_log("FCM ERROR: ".$e->getMessage());
        }
    }

    return true;
}

/**
 * Returns the Firebase service account as either a decoded array
 * (from FIREBASE_SERVICE_ACCOUNT_BASE64 env var — used on Render, avoids
 * file-permission issues with Secret Files) or a file path (local/XAMPP
 * fallback). Returns null if neither is available.
 *
 * @return array|string|null
 */
function getFirebaseServiceAccount()
{
    $base64 = getenv('FIREBASE_SERVICE_ACCOUNT_BASE64');

    if (!empty($base64)) {
        $json = base64_decode($base64, true);
        $decoded = $json !== false ? json_decode($json, true) : null;

        if (is_array($decoded)) {
            return $decoded;
        }

        error_log("FCM ERROR: FIREBASE_SERVICE_ACCOUNT_BASE64 is set but failed to decode as valid JSON");
    }

    // Local/XAMPP fallback
    $localPath = __DIR__ . '/../secure/' . FIREBASE_SERVICE_ACCOUNT_FILENAME;
    if (file_exists($localPath)) {
        return $localPath;
    }

    return null;
}
