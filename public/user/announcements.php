<?php
// public/user/announcements.php
header("Cache-Control: no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: Wed, 11 Jan 1984 05:00:00 GMT");

require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

$auth = new Auth();
$auth->requireUser();

$db = new Database();
$user = $auth->getCurrentUser();

// Get the latest announcement timestamp for initial lastUpdate
$latest_sql = "SELECT MAX(UNIX_TIMESTAMP(created_at)) as latest FROM announcements";
$latest_result = $db->query($latest_sql);
$latest_row = $latest_result->fetch_assoc();
$initial_last_update = $latest_row['latest'] ? (int)$latest_row['latest'] : time();

// Pass user data to JavaScript for notifications
$user_id = $user['id'];
$user_name = $user['full_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Announcements - BarangayLink</title>
    <link rel="stylesheet" href="../../src/css/main.css?v=<?php echo time(); ?>">
    <style>
        /* New styles for polling system */
        .new-badge {
            display: inline-block;
            background: #ff6b6b;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            animation: pulse 1.5s ease-in-out infinite;
        }
        .new-badge:hover {
            transform: scale(1.05);
            background: #ee5a24;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        .click-hint {
            font-size: 12px;
            color: #666;
            margin: 5px 0 10px 0;
            font-style: italic;
        }
        .announcement-item.highlight-new {
            animation: highlightFlash 2s ease;
            border-left: 4px solid #1a73e8;
            background: #f0f7ff;
        }
        @keyframes highlightFlash {
            0% { background: #e3f2fd; }
            100% { background: transparent; }
        }
        .live-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            background: #4caf50;
            border-radius: 50%;
            margin-left: 8px;
            animation: livePulse 2s infinite;
        }
        @keyframes livePulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        .refresh-indicator.checking .spinner-small {
            display: inline-block;
            animation: spin 1s linear infinite;
        }
        .spinner-small {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #3498db;
            border-radius: 50%;
            margin-left: 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .loading.active .spinner {
            display: inline-block;
            width: 30px;
            height: 30px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }
        .loading {
            display: none;
            padding: 20px;
            text-align: center;
            color: #666;
        }
        .loading.active {
            display: block;
        }
        .swipe-hint {
            text-align: center;
            color: #999;
            font-size: 12px;
            padding: 5px;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            border: 1px solid #f5c6cb;
        }
        .announcements-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .announcements-header h1 {
            display: flex;
            align-items: center;
        }
        .toast-notification {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 12px 24px;
            border-radius: 8px;
            z-index: 9999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            font-size: 14px;
            max-width: 90%;
            animation: fadeInUp 0.3s ease;
            color: white;
        }
        .toast-notification.success {
            background: #28a745;
        }
        .toast-notification.error {
            background: #dc3545;
        }
        .toast-notification.info {
            background: #17a2b8;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateX(-50%) translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateX(-50%) translateY(0);
            }
        }
    </style>
    <script>
    // Pass user data to JavaScript
    window.currentUserId = <?php echo $user_id; ?>;
    window.currentUserName = '<?php echo addslashes($user_name); ?>';
    window.initialLastUpdate = <?php echo $initial_last_update; ?>;
    </script>
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
            <a href="dashboard.php"><img src="../../assets/1772429077726-removebg-preview.png" alt="BarangayLink" style="height: 30px; vertical-align: middle;">BarangayLink</a>
        </div>
        <div class="user-info">
            Welcome, <?php echo htmlspecialchars($user['full_name']); ?>
        </div>
        <button class="mobile-menu-btn" id="mobile-menu-btn">☰</button>
    </nav>
    
    <div class="main-content">
        <div class="container" style="padding-top: 10px;">
            <!-- Pull to refresh container -->
            <div class="ptr-container" id="ptrContainer">
                <div class="ptr-content">
                    <div class="ptr-spinner"></div>
                    <span id="ptrText">Pull to refresh</span>
                </div>
            </div>
            
            <div class="announcements-header">
                <h1>Barangay Announcements <span class="live-indicator"></span></h1>
                <span id="newCount" class="new-badge" style="display: none;" onclick="refreshAnnouncements()">✨ 0 new</span>
            </div>
            
            <!-- Subtle hint that badge is clickable (only shows when badge appears) -->
            <div id="clickHint" class="click-hint" style="display: none;">
                Click the badge to refresh
            </div>
            
            <!-- Swipe hint for mobile -->
            <div class="swipe-hint">
                ↓ Pull down to refresh
            </div>
            
            <!-- Last updated indicator -->
            <div class="refresh-indicator" id="refreshIndicator">
                <span>Last updated: <span id="last-updated"><?php echo date('h:i:s A'); ?></span></span>
                <span class="spinner-small"></span>
            </div>
            
            <div class="loading" id="loading">
                <div class="spinner"></div>
                Loading latest announcements...
            </div>
            
            <div class="card">
                <div class="card-header">
                    Latest Announcements
                </div>
                <div class="card-body announcements-container" id="announcements-container">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
    
    <script>
    (function() {
        'use strict';

        // ============================================
        // Configuration
        // ============================================
        let lastUpdate = window.initialLastUpdate || Math.floor(Date.now() / 1000);
        let isRefreshing = false;
        let pollInterval = null;
        let isPolling = false;
        let notificationSound = null;
        let lastNotificationId = 0;
        let notificationService = null;

        // ============================================
        // Push Notification Service (Web)
        // ============================================
        
        // Load push notification service
        function loadPushNotificationService() {
            const script = document.createElement('script');
            script.src = '../../src/js/push-notification-service.js';
            script.type = 'module';
            script.onload = function() {
                console.log('✅ Push notification service loaded');
            };
            script.onerror = function() {
                console.log('⚠️ Push notification service not available');
            };
            document.head.appendChild(script);
        }

        // Initialize native notifications (Capacitor)
        async function initNativeNotifications() {
            if (window.Capacitor && window.Capacitor.isNative) {
                try {
                    const module = await import('../../src/js/notification-service.js');
                    notificationService = module.default;
                    await notificationService.initialize();
                    console.log('📱 Native notifications ready');
                } catch (error) {
                    console.log('⚠️ Native notifications not available', error);
                }
            }
        }

        // Check for native notifications
        async function checkForNotifications() {
            if (!window.Capacitor || !window.Capacitor.isNative || !notificationService) return;
            
            try {
                const response = await fetch('/public/api/notifications.php?action=check&lastId=' + lastNotificationId);
                const data = await response.json();
                
                if (data.notifications && data.notifications.length > 0) {
                    for (const notification of data.notifications) {
                        if (notification.type === 'announcement') {
                            await notificationService.sendAnnouncementNotification(notification.data);
                        }
                        
                        lastNotificationId = Math.max(lastNotificationId, notification.id);
                        
                        fetch('/public/api/notifications.php?action=mark_read', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                            body: 'id=' + notification.id
                        });
                    }
                }
            } catch (error) {
                console.error('Error checking notifications:', error);
            }
        }

        // ============================================
        // Core Functions
        // ============================================
        
        // Refresh announcements from server
        window.refreshAnnouncements = function() {
            if (isRefreshing) return;
            isRefreshing = true;
            
            const loading = document.getElementById('loading');
            const container = document.getElementById('announcements-container');
            const indicator = document.getElementById('refreshIndicator');
            const badge = document.getElementById('newCount');
            
            if (loading) loading.classList.add('active');
            if (indicator) indicator.classList.add('checking');
            
            if (badge) {
                badge.style.display = 'none';
            }
            const hint = document.getElementById('clickHint');
            if (hint) hint.style.display = 'none';
            
            fetch('get_announcements.php?_=' + Date.now())
                .then(response => {
                    if (!response.ok) throw new Error('Network error');
                    return response.text();
                })
                .then(html => {
                    if (container) container.innerHTML = html;
                    const updated = document.getElementById('last-updated');
                    if (updated) updated.textContent = new Date().toLocaleTimeString();
                    
                    // Update timestamp from hidden input if exists
                    const tsInput = document.getElementById('last-updated-timestamp');
                    if (tsInput) {
                        lastUpdate = parseInt(tsInput.value) || Math.floor(Date.now() / 1000);
                    } else {
                        lastUpdate = Math.floor(Date.now() / 1000);
                    }
                    
                    highlightNewItems();
                    showToast('Announcements updated', 'success');
                })
                .catch(error => {
                    console.error('Error:', error);
                    if (container) {
                        container.innerHTML = '<div class="alert alert-danger">Error loading announcements. Please try again.</div>';
                    }
                    showToast('Failed to load announcements', 'error');
                })
                .finally(() => {
                    if (loading) loading.classList.remove('active');
                    if (indicator) indicator.classList.remove('checking');
                    isRefreshing = false;
                    
                    const ptr = document.querySelector('.ptr-container');
                    if (ptr) ptr.classList.remove('refreshing');
                });
        };

        // Check for updates via AJAX polling
        function checkForUpdates() {
            if (isRefreshing) return;
            
            fetch('check_announcements_updates.php?last=' + lastUpdate + '&_=' + Date.now())
                .then(response => {
                    if (!response.ok) throw new Error('Network error');
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.hasUpdates && data.count > 0) {
                        console.log('📢 Found', data.count, 'new announcement(s)');
                        
                        if (data.lastUpdate > lastUpdate) {
                            lastUpdate = data.lastUpdate;
                        }
                        
                        // Show badge
                        showNewCountBadge(data.count);
                        
                        // Play notification sound
                        playNotificationSound();
                        
                        // Show browser notification
                        if (data.announcements && data.announcements.length > 0) {
                            data.announcements.forEach(function(ann) {
                                // Send web push notification
                                sendPushNotification(ann);
                                
                                // Send native notification if available
                                if (notificationService) {
                                    notificationService.sendAnnouncementNotification({
                                        title: ann.title,
                                        body: ann.content || ann.message || '',
                                        id: ann.id
                                    });
                                }
                                
                                // Show browser notification
                                showBrowserNotification('📢 New Announcement', ann.title);
                            });
                        }
                        
                        // Auto-refresh after 2 seconds
                        setTimeout(function() {
                            window.refreshAnnouncements();
                        }, 2000);
                    }
                })
                .catch(err => {
                    // Silent fail for polling
                    console.debug('Polling check failed:', err);
                });
        }

        // ============================================
        // Push Notification Functions
        // ============================================
        
        // Send push notification (web)
        function sendPushNotification(announcement) {
            // Check if service worker is registered
            if ('serviceWorker' in navigator && 'PushManager' in window) {
                // The push-notification-service.js handles this automatically
                // We just need to trigger the notification
                if (window.BarangayLink && window.BarangayLink.showNotification) {
                    window.BarangayLink.showNotification('📢 ' + announcement.title, 'info');
                }
            }
        }

        // ============================================
        // UI Helper Functions
        // ============================================

        function highlightNewItems() {
            const items = document.querySelectorAll('.announcement-item');
            items.forEach(function(item) {
                const itemTime = parseInt(item.dataset.updated || '0');
                if (itemTime > lastUpdate) {
                    item.classList.add('highlight-new');
                    setTimeout(function() {
                        item.classList.remove('highlight-new');
                    }, 3000);
                }
            });
        }

        function showToast(message, type) {
            // Use BarangayLink notification if available
            if (window.BarangayLink && window.BarangayLink.showNotification) {
                window.BarangayLink.showNotification(message, type);
                return;
            }
            
            // Fallback toast
            const toast = document.createElement('div');
            toast.className = 'toast-notification ' + (type || 'info');
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(function() {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.3s ease';
                setTimeout(function() {
                    toast.remove();
                }, 300);
            }, 3000);
        }

        function showNewCountBadge(count) {
            const badge = document.getElementById('newCount');
            const hint = document.getElementById('clickHint');
            
            if (badge) {
                badge.textContent = '✨ ' + count + ' new';
                badge.style.display = 'inline-block';
            }
            
            if (hint && hint.style.display !== 'block') {
                hint.style.display = 'block';
                clearTimeout(window.hintTimeout);
                window.hintTimeout = setTimeout(function() {
                    if (hint) hint.style.display = 'none';
                }, 5000);
            }
        }

        function showBrowserNotification(title, body) {
            if ('Notification' in window && Notification.permission === 'granted') {
                try {
                    new Notification(title, {
                        body: body || 'New announcement available',
                        icon: '/assets/1772429077726-removebg-preview.png'
                    });
                } catch (e) {
                    // Ignore
                }
            }
        }

        function playNotificationSound() {
            try {
                if (!notificationSound) {
                    notificationSound = new Audio('/assets/notification.mp3');
                }
                notificationSound.play().catch(function() {
                    // Audio play failed, ignore
                });
            } catch (e) {
                // Audio not available
            }
        }

        // ============================================
        // Polling Control
        // ============================================

        function startPolling() {
            if (isPolling) return;
            isPolling = true;
            
            // Check every 10 seconds
            pollInterval = setInterval(checkForUpdates, 10000);
            console.log('🔄 Polling started (every 10 seconds)');
            
            // Also check immediately
            checkForUpdates();
        }

        function stopPolling() {
            isPolling = false;
            if (pollInterval) {
                clearInterval(pollInterval);
                pollInterval = null;
                console.log('⏹️ Polling stopped');
            }
        }

        // ============================================
        // Initialize
        // ============================================

        document.addEventListener('DOMContentLoaded', function() {
            console.log('📢 Announcements page loaded');
            
            // Load push notification service
            loadPushNotificationService();
            
            // Initialize native notifications (Capacitor)
            initNativeNotifications();
            
            // Load initial announcements
            window.refreshAnnouncements();
            
            // Start polling for updates
            setTimeout(startPolling, 1000);
            
            // Request notification permission
            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission().then(function(permission) {
                    console.log('🔔 Notification permission:', permission);
                });
            }
            
            // Handle visibility change - check when tab becomes active
            document.addEventListener('visibilitychange', function() {
                if (!document.hidden) {
                    console.log('👁️ Tab became active, checking for updates');
                    checkForUpdates();
                }
            });
            
            // Clean up on page unload
            window.addEventListener('beforeunload', function() {
                stopPolling();
            });
        });

        // Expose functions globally
        window.startPolling = startPolling;
        window.stopPolling = stopPolling;
        window.checkForUpdates = checkForUpdates;

    })();
    </script>
    
    <script src="../../src/js/main.js"></script>
    <script src="../../src/js/mobile-menu.js"></script>
    <script src="../../src/js/pull-to-refresh.js"></script>
</body>
</html>