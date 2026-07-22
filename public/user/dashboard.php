<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

$auth = new Auth();
$auth->requireUser();

$db = new Database();
$user = $auth->getCurrentUser();

// FIX 3: Server-side detection for installed app
$isInstalledApp = false;
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

// Check for Capacitor/Cordova/WebView in user agent
if (strpos($userAgent, 'Capacitor') !== false || 
    strpos($userAgent, 'cordova') !== false ||
    strpos($userAgent, 'WebView') !== false ||
    strpos($userAgent, 'wv') !== false ||
    preg_match('/android.*applewebkit.*(?!chrome).*version\//i', $userAgent)) {
    $isInstalledApp = true;
}

// Check for custom cookie (can be set in Capacitor app)
if (isset($_COOKIE['barangaylink_app']) && $_COOKIE['barangaylink_app'] === 'true') {
    $isInstalledApp = true;
}

// Check for custom header (can be added in Capacitor app)
$headers = getallheaders();
if (isset($headers['X-BarangayLink-App']) && $headers['X-BarangayLink-App'] === 'true') {
    $isInstalledApp = true;
}

// Get user's requests
$requests = $db->query("SELECT * FROM service_requests WHERE user_id = {$user['id']} ORDER BY created_at DESC LIMIT 5");

// Get recent announcements
$announcements = $db->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 3");

// Pass user data to JavaScript
$user_id = $user['id'];
$user_name = $user['full_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - BarangayLink</title>
    <link rel="stylesheet" href="../../src/css/main.css">
    <script>
    // Pass user data to JavaScript
    window.currentUserId = <?php echo $user_id; ?>;
    window.currentUserName = '<?php echo addslashes($user_name); ?>';
    </script>
    <style>
        /* Android Download Button Styles */
        .android-download-container {
            margin: 25px 0;
            text-align: center;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .android-download-btn {
            display: inline-flex;
            align-items: center;
            background: #3b3f46;
            background: linear-gradient(145deg, #3b3f46 0%, #2b2f36 100%);
            border-radius: 12px;
            padding: 12px 24px;
            text-decoration: none;
            color: white;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1), 0 1px 3px rgba(0, 0, 0, 0.08);
            transition: transform 0.2s, box-shadow 0.2s;
            border: 1px solid rgba(255, 255, 255, 0.1);
            max-width: 320px;
            margin: 0 auto;
        }

        .android-download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 7px 14px rgba(0, 0, 0, 0.15), 0 3px 6px rgba(0, 0, 0, 0.1);
            background: linear-gradient(145deg, #2f333a 0%, #1f232a 100%);
            text-decoration: none;
            color: white;
        }

        .android-download-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .android-icon {
            margin-right: 15px;
            filter: drop-shadow(0 2px 2px rgba(0, 0, 0, 0.2));
        }

        .download-text {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            line-height: 1.2;
        }

        .small-text {
            font-size: 10px;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .large-text {
            font-size: 18px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .apk-text {
            font-size: 8px;
            opacity: 0.6;
            text-transform: uppercase;
        }

        .download-badge {
            background: #ff6b6b;
            background: linear-gradient(135deg, #ff6b6b 0%, #ff5252 100%);
            border-radius: 20px;
            padding: 4px 8px;
            font-size: 12px;
            font-weight: 700;
            margin-left: 15px;
            text-transform: uppercase;
            box-shadow: 0 2px 4px rgba(255, 82, 82, 0.3);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(255, 82, 82, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(255, 82, 82, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(255, 82, 82, 0);
            }
        }

        @media (max-width: 768px) {
            .android-download-btn {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Side Navigation -->
<div class="sidenav">
    <div class="sidenav-header">
        <div class="sidenav-logo">
            <img src="../../assets/1772429077726-removebg-preview.png" alt="BarangayLink Logo" onerror="this.style.display='none'">
        </div>
        <h2>BarangayLink</h2>
        <p>Resident Portal</p>
    </div>
    
    <div class="sidenav-user">
        <div class="sidenav-avatar">
            <?php 
            $initial = strtoupper(substr($user['full_name'], 0, 1));
            echo $initial;
            ?>
        </div>
        <div class="sidenav-user-info">
            <div class="sidenav-user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
            <div class="sidenav-user-type">Resident</div>
        </div>
    </div>
    
    <ul class="sidenav-menu">
        <li class="sidenav-item">
            <a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                <span class="sidenav-icon">📊</span>
                <span class="sidenav-text">Dashboard</span>
            </a>
        </li>
        <li class="sidenav-item">
            <a href="announcements.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'announcements.php' ? 'active' : ''; ?>">
                <span class="sidenav-icon">📢</span>
                <span class="sidenav-text">Announcements</span>
            </a>
        </li>
        <li class="sidenav-item">
            <a href="service_request.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'service_request.php' ? 'active' : ''; ?>">
                <span class="sidenav-icon">📝</span>
                <span class="sidenav-text">Request Service</span>
            </a>
        </li>
        <li class="sidenav-item">
            <a href="request_status.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'request_status.php' ? 'active' : ''; ?>">
                <span class="sidenav-icon">📋</span>
                <span class="sidenav-text">My Requests</span>
            </a>
        </li>
        <li class="sidenav-item">
            <a href="profile.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                <span class="sidenav-icon">👤</span>
                <span class="sidenav-text">Profile</span>
            </a>
        </li>
        <li class="sidenav-divider"></li>
        <li class="sidenav-item">
            <a href="../logout.php" class="logout-link">
                <span class="sidenav-icon">🚪</span>
                <span class="sidenav-text">Logout</span>
            </a>
        </li>
    </ul>
    
    <div class="sidenav-footer">
        <p>&copy; 2026 BarangayLink</p>
        <p class="sidenav-version">v1.0.0</p>
    </div>
</div>

    <!-- Top Navbar (Mobile Only) -->
    <nav class="navbar mobile-only">
        <div class="navbar-brand">
            <a href="dashboard.php"> <img src="../../assets/1772429077726-removebg-preview.png" alt="BarangayLink" style="height: 30px; vertical-align: middle;">BarangayLink</a>
        </div>
        <div class="user-info">
            Welcome, <?php echo $user['full_name']; ?>
        </div>
        <button class="mobile-menu-btn" id="mobile-menu-btn">☰</button>
    </nav>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <h1>Resident Dashboard</h1>
            
            <div class="stats-grid">
                <div class="stat-card" data-tooltip="Total requests you've submitted">
                    <h3>My Requests</h3>
                    <div class="stat-number">
                        <?php 
                        $count = $db->query("SELECT COUNT(*) as count FROM service_requests WHERE user_id = {$user['id']}")->fetch_assoc()['count'];
                        echo $count;
                        ?>
                    </div>
                </div>
                <div class="stat-card" data-tooltip="Requests currently pending">
                    <h3>Pending Requests</h3>
                    <div class="stat-number">
                        <?php 
                        $pending = $db->query("SELECT COUNT(*) as count FROM service_requests WHERE user_id = {$user['id']} AND status='pending'")->fetch_assoc()['count'];
                        echo $pending;
                        ?>
                    </div>
                </div>
            </div>

            <!-- Android App Download Button - Only shown in browser, NOT in installed app -->
            <?php if (!$isInstalledApp): ?>
            <div id="android-download" class="android-download-container" style="display: none;">
                <a href="thank-you-download.php" class="android-download-btn" id="downloadAppBtn">
                    <svg class="android-icon" viewBox="0 0 24 24" width="24" height="24">
                        <path fill="currentColor" d="M17.6,9.2l2.5-4.3c0.2-0.4,0.1-0.9-0.3-1.1c-0.4-0.2-0.9-0.1-1.1,0.3l-2.5,4.3c-1.2-0.6-2.6-1-4.2-1s-3,0.4-4.2,1L5.3,4.1C5.1,3.7,4.6,3.6,4.2,3.8C3.8,4,3.7,4.5,3.9,4.9l2.5,4.3C4.5,10.5,3,12.7,3,15.2h18C21,12.7,19.5,10.5,17.6,9.2z M8.5,13.2c-0.7,0-1.2-0.6-1.2-1.2s0.6-1.2,1.2-1.2s1.2,0.6,1.2,1.2S9.2,13.2,8.5,13.2z M15.5,13.2c-0.7,0-1.2-0.6-1.2-1.2s0.6-1.2,1.2-1.2s1.2,0.6,1.2,1.2S16.2,13.2,15.5,13.2z M6,16.2c0,0.6,0.5,1,1,1h1v3c0,0.8,0.7,1.5,1.5,1.5s1.5-0.7,1.5-1.5v-3h2v3c0,0.8,0.7,1.5,1.5,1.5s1.5-0.7,1.5-1.5v-3h1c0.6,0,1-0.5,1-1v-7H6V16.2z"/>
                    </svg>
                    <span class="download-text">
                        <span class="small-text">Get it on</span>
                        <span class="large-text">Google Play</span>
                        <span class="apk-text">(APK Download)</span>
                    </span>
                    <span class="download-badge">FREE</span>
                </a>
            </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            Recent Announcements
                            <a href="announcements.php" class="btn btn-primary btn-sm">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if ($announcements->num_rows > 0): ?>
                                <?php while($row = $announcements->fetch_assoc()): ?>
                                    <div class="announcement-item">
                                        <h4><?php echo $row['title']; ?></h4>
                                        <p><?php echo substr($row['content'], 0, 100); ?>...</p>
                                        <small><?php echo timeAgo($row['created_at']); ?></small>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p>No announcements yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            My Recent Requests
                            <a href="request_status.php" class="btn btn-primary btn-sm">View All</a>
                        </div>
                        <div class="card-body">
                            <?php if ($requests->num_rows > 0): ?>
                                <?php while($row = $requests->fetch_assoc()): ?>
                                    <div class="request-card">
                                        <div class="request-header">
                                            <span class="request-type"><?php echo $row['request_type']; ?></span>
                                            <?php echo getStatusBadge($row['status']); ?>
                                        </div>
                                        <p><?php echo substr($row['description'], 0, 50); ?>...</p>
                                        <small><?php echo timeAgo($row['created_at']); ?></small>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p>No service requests yet.</p>
                                <a href="service_request.php" class="btn btn-success">Request Service</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="/public/push.js"></script>
    <script src="../../src/js/user-dashboard.js"></script>
    <script src="../../src/js/main.js"></script>
    <script src="../../src/js/mobile-menu.js"></script>
    
    
</body>
</html>
