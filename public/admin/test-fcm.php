<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

// Include Composer autoloader
require_once __DIR__ . '/../../../vendor/autoload.php';

use Kreait\Firebase\Factory;
use Kreait\Firebase\Exception\MessagingException;

// Require admin login for security
$auth = new Auth();
if (!$auth->isLoggedIn()) {
    die('Please login first');
}

$user = $auth->getCurrentUser();
$db = new Database();

// Handle test notification submission
$result = null;
$error = null;
$token = '';
$title = '';
$body = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $title = $_POST['title'] ?? 'Test Notification';
    $body = $_POST['body'] ?? 'This is a test notification from BarangayLink';
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    
    try {
        // Service account path
        $serviceAccountPath = __DIR__ . '/../../../secure/barangaylink-c5e86-firebase-adminsdk-fbsvc-006264d723.json';
        
        if (!file_exists($serviceAccountPath)) {
            throw new Exception("Service account file not found at: $serviceAccountPath");
        }
        
        // Initialize Firebase
        $factory = (new Factory)->withServiceAccount($serviceAccountPath);
        $messaging = $factory->createMessaging();
        
        // Prepare tokens array
        $tokens = [];
        
        if (!empty($token)) {
            // Use manually entered token
            $tokens = [$token];
        } elseif ($user_id > 0) {
            // Get tokens for specific user
            $sql = "SELECT fcm_token FROM user_devices WHERE user_id = $user_id AND last_active > DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $result_tokens = $db->query($sql);
            
            while ($row = $result_tokens->fetch_assoc()) {
                $tokens[] = $row['fcm_token'];
            }
            
            if (empty($tokens)) {
                throw new Exception("No active tokens found for user ID: $user_id");
            }
        } else {
            // Get all recent tokens
            $sql = "SELECT fcm_token FROM user_devices WHERE last_active > DATE_SUB(NOW(), INTERVAL 30 DAY) ORDER BY last_active DESC LIMIT 10";
            $result_tokens = $db->query($sql);
            
            while ($row = $result_tokens->fetch_assoc()) {
                $tokens[] = $row['fcm_token'];
            }
            
            if (empty($tokens)) {
                throw new Exception("No active tokens found in database");
            }
        }
        
        // Build Android config
        $androidConfig = [
            'priority' => 'high',
            'notification' => [
                'icon' => 'ic_stat_icon',
                'color' => '#2c3e50',
                'sound' => 'default',
            ],
        ];
        
        // Build APNS config for iOS
        $apnsConfig = [
            'headers' => [
                'apns-priority' => '10',
            ],
            'payload' => [
                'aps' => [
                    'sound' => 'default',
                    'badge' => 1,
                ],
            ],
        ];
        
        // Prepare data payload
        $data = [
            'type' => 'test',
            'timestamp' => (string)time(),
            'click_action' => 'OPEN_APP'
        ];
        
        $successCount = 0;
        $failCount = 0;
        $results = [];
        
        // Send to each token
        foreach ($tokens as $t) {
            try {
                $message = [
                    'token' => $t,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'android' => $androidConfig,
                    'apns' => $apnsConfig,
                    'data' => $data,
                ];
                
                $messaging->send($message);
                $successCount++;
                $results[] = ['token' => substr($t, 0, 30) . '...', 'success' => true];
                
            } catch (MessagingException $e) {
                $failCount++;
                $results[] = ['token' => substr($t, 0, 30) . '...', 'success' => false, 'error' => $e->getMessage()];
                
                // Remove invalid token if not registered
                if (strpos($e->getMessage(), 'NotRegistered') !== false || 
                    strpos($e->getMessage(), 'registration-token-not-registered') !== false) {
                    $token_escaped = $db->escape($t);
                    $db->query("DELETE FROM user_devices WHERE fcm_token = '$token_escaped'");
                }
            }
        }
        
        $result = [
            'success' => true,
            'total' => count($tokens),
            'success_count' => $successCount,
            'fail_count' => $failCount,
            'details' => $results
        ];
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get list of recent users with tokens for dropdown
$users_sql = "SELECT DISTINCT u.id, u.full_name 
              FROM users u 
              JOIN user_devices d ON u.id = d.user_id 
              WHERE d.last_active > DATE_SUB(NOW(), INTERVAL 30 DAY)
              ORDER BY u.full_name";
$users_result = $db->query($users_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test FCM Push Notifications</title>
    <link rel="stylesheet" href="../../src/css/main.css">
    <style>
        .test-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
        .result-box {
            background: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-family: monospace;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .result-box.success {
            border-left-color: #27ae60;
        }
        .result-box.error {
            border-left-color: #e74c3c;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
        }
        .token-preview {
            font-size: 0.85rem;
            color: #666;
            background: #f5f5f5;
            padding: 2px 5px;
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="main-content">
        <div class="container test-container">
            <h1>📱 Test FCM Push Notifications</h1>
            
            <div class="card">
                <div class="card-header">
                    Send Test Notification
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="form-group">
                            <label>Select User (Optional)</label>
                            <select name="user_id" class="form-control">
                                <option value="0">-- Send to all recent devices --</option>
                                <?php while($u = $users_result->fetch_assoc()): ?>
                                    <option value="<?php echo $u['id']; ?>">
                                        <?php echo htmlspecialchars($u['full_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                            <small>Choose a specific user or send to all</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Or Enter FCM Token Manually</label>
                            <input type="text" name="token" class="form-control" 
                                   value="<?php echo htmlspecialchars($token); ?>" 
                                   placeholder="Paste FCM token here">
                            <small>Leave empty if using user selection above</small>
                        </div>
                        
                        <div class="row">
                            <div class="col">
                                <div class="form-group">
                                    <label>Notification Title</label>
                                    <input type="text" name="title" class="form-control" 
                                           value="<?php echo htmlspecialchars($title ?: 'Test Notification'); ?>" required>
                                </div>
                            </div>
                            <div class="col">
                                <div class="form-group">
                                    <label>Notification Body</label>
                                    <input type="text" name="body" class="form-control" 
                                           value="<?php echo htmlspecialchars($body ?: 'This is a test notification from BarangayLink'); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-success btn-block">
                            📨 Send Test Notification
                        </button>
                    </form>
                </div>
            </div>
            
            <?php if ($error): ?>
                <div class="result-box error">
                    <strong>❌ Error:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($result): ?>
                <div class="stats">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $result['total']; ?></div>
                        <div>Total Tokens</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" style="color: #27ae60;"><?php echo $result['success_count']; ?></div>
                        <div>Successful</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number" style="color: #e74c3c;"><?php echo $result['fail_count']; ?></div>
                        <div>Failed</div>
                    </div>
                </div>
                
                <div class="result-box success">
                    <strong>✅ Results:</strong>
                    <pre><?php print_r($result['details']); ?></pre>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header">
                    Quick Token Lookup
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="form-group">
                            <label>Enter User ID to see tokens</label>
                            <div class="row">
                                <div class="col">
                                    <input type="number" name="lookup_user" class="form-control" 
                                           placeholder="User ID" value="<?php echo isset($_GET['lookup_user']) ? (int)$_GET['lookup_user'] : ''; ?>">
                                </div>
                                <div class="col">
                                    <button type="submit" class="btn btn-primary">Look Up</button>
                                </div>
                            </div>
                        </div>
                    </form>
                    
                    <?php if (isset($_GET['lookup_user']) && $_GET['lookup_user'] > 0): 
                        $lookup_id = (int)$_GET['lookup_user'];
                        $tokens_sql = "SELECT fcm_token, device_type, last_active FROM user_devices WHERE user_id = $lookup_id ORDER BY last_active DESC";
                        $tokens_result = $db->query($tokens_sql);
                        
                        if ($tokens_result && $tokens_result->num_rows > 0):
                    ?>
                        <h4>Tokens for User ID: <?php echo $lookup_id; ?></h4>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Token (truncated)</th>
                                    <th>Device</th>
                                    <th>Last Active</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($t = $tokens_result->fetch_assoc()): ?>
                                <tr>
                                    <td><span class="token-preview"><?php echo substr($t['fcm_token'], 0, 50); ?>...</span></td>
                                    <td><?php echo $t['device_type']; ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($t['last_active'])); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                            <p>No tokens found for user ID: <?php echo $lookup_id; ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    System Status
                </div>
                <div class="card-body">
                    <p><strong>Service Account Path:</strong> 
                        <?php 
                        $path = __DIR__ . '/../../../secure/barangaylink-c5e86-firebase-adminsdk-fbsvc-006264d723.json';
                        if (file_exists($path)) {
                            echo '<span style="color: #27ae60;">✅ Found at: ' . $path . '</span>';
                        } else {
                            echo '<span style="color: #e74c3c;">❌ Not found at: ' . $path . '</span>';
                        }
                        ?>
                    </p>
                    <p><strong>Firebase SDK Version:</strong> 
                        <?php
                        $composer_path = __DIR__ . '/../../../vendor/kreait/firebase-php/composer.json';
                        if (file_exists($composer_path)) {
                            $composer = json_decode(file_get_contents($composer_path), true);
                            echo $composer['version'] ?? 'Unknown';
                        } else {
                            echo 'Not detected';
                        }
                        ?>
                    </p>
                </div>
            </div>
            
            <p style="text-align: center; margin-top: 20px;">
                <a href="service_requests.php" class="btn btn-primary">← Back to Service Requests</a>
            </p>
        </div>
    </div>
    
    <script src="../../src/js/main.js"></script>
</body>
</html>