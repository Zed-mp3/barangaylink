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

function sendFCMNotification(array $tokens, array $notificationData, array $data = [])
{
    if (empty($tokens)) return false;

    require __DIR__ . '/../vendor/autoload.php';

    $factory = (new \Kreait\Firebase\Factory)
        ->withServiceAccount(__DIR__ . '/../secure/barangaylink-c5e86-firebase-adminsdk-fbsvc-006264d723.json');

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