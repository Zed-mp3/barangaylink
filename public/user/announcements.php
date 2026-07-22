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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Announcements - BarangayLink</title>
    <link rel="stylesheet" href="../../src/css/main.css?v=<?php echo time(); ?>">
    <style>
        .notification-badge {
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.75rem;
            margin-left: 5px;
        }
        
        /* Pull to refresh indicator */
        .ptr-container {
            position: relative;
            overflow: hidden;
            height: 0;
            transition: height 0.3s;
            text-align: center;
            background: var(--light-bg);
            border-radius: 8px 8px 0 0;
            margin-top: -10px;
        }
        
        .ptr-container.show {
            height: 50px;
        }
        
        .ptr-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 10px;
            color: var(--primary-color);
            font-weight: 500;
        }
        
        .ptr-spinner {
            width: 24px;
            height: 24px;
            border: 3px solid var(--light-bg);
            border-top: 3px solid var(--secondary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        .last-checked {
            font-size: 0.8rem;
            color: #666;
            text-align: right;
            margin: 5px 15px 10px;
            padding: 5px;
            background: var(--white);
            border-radius: 4px;
            box-shadow: var(--shadow);
        }
        
        .announcements-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
            padding: 0 5px;
        }
        
        .announcements-header h1 {
            margin: 0;
            font-size: 1.5rem;
        }
        
        .new-badge {
            background: var(--success-color);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .new-badge:hover {
            transform: scale(1.05);
            background: var(--secondary-color);
        }
        
        .new-badge.pulse {
            animation: pulse 1.5s infinite;
        }
        
        .refresh-indicator {
            font-size: 0.8rem;
            color: #666;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 5px;
            padding: 5px 10px;
            background: var(--white);
            border-radius: 20px;
            box-shadow: var(--shadow);
            margin: 5px 0 10px;
        }
        
        .refresh-indicator .spinner-small {
            width: 16px;
            height: 16px;
            border: 2px solid var(--light-bg);
            border-top: 2px solid var(--secondary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            display: none;
        }
        
        .refresh-indicator.checking .spinner-small {
            display: inline-block;
        }
        
        .loading {
            text-align: center;
            padding: 30px;
            display: none;
        }
        
        .loading.active {
            display: block;
        }
        
        .loading .spinner {
            margin: 0 auto 15px;
        }
        
        @keyframes pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.1); }
            100% { opacity: 1; transform: scale(1); }
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Mobile optimizations */
        @media (max-width: 768px) {
            .announcements-header h1 {
                font-size: 1.3rem;
            }
            
            .last-checked {
                margin: 5px 10px 10px;
                font-size: 0.75rem;
            }
            
            .announcement-item {
                padding: 15px;
                margin-bottom: 12px;
            }
            
            .announcement-title {
                font-size: 1.1rem;
            }
            
            .announcement-meta {
                font-size: 0.8rem;
                flex-direction: column;
                gap: 3px;
            }
            
            .new-badge {
                padding: 4px 10px;
                font-size: 0.75rem;
            }
        }
        
        /* Swipe hint for mobile */
        .swipe-hint {
            display: none;
            text-align: center;
            color: #666;
            font-size: 0.75rem;
            padding: 5px;
            margin-bottom: 5px;
            opacity: 0.7;
        }
        
        @media (max-width: 768px) {
            .swipe-hint {
                display: block;
            }
        }
        
        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #666;
        }
        
        .empty-state p {
            margin: 10px 0;
        }
        
        /* Subtle hint that badge is clickable */
        .click-hint {
            font-size: 0.7rem;
            color: #666;
            text-align: center;
            margin-top: -10px;
            margin-bottom: 10px;
            opacity: 0.6;
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
                <h1>Barangay Announcements</h1>
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
    // Initialize notification system if not exists
    window.BarangayLink = window.BarangayLink || {
        showNotification: function(message, type) {
            console.log(message, type);
            // Use a more mobile-friendly notification
            if (navigator.vibrate) navigator.vibrate(50);
            
            // Create a toast notification
            const toast = document.createElement('div');
            toast.style.cssText = `
                position: fixed;
                bottom: 20px;
                left: 50%;
                transform: translateX(-50%);
                background: ${type === 'error' ? 'var(--danger-color)' : type === 'success' ? 'var(--success-color)' : 'var(--info-color)'};
                color: white;
                padding: 12px 20px;
                border-radius: 30px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                z-index: 9999;
                font-size: 0.9rem;
                max-width: 90%;
                text-align: center;
                animation: slideUp 0.3s ease;
            `;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'fadeOut 0.3s ease';
                setTimeout(() => toast.remove(), 300);
            }, 2000); // Shorter duration
        }
    };
    
    let lastUpdate = <?php echo $initial_last_update; ?>;
    let refreshInterval;
    let isRefreshing = false;
    let checkInterval = 15000; // 15 seconds
    let touchStartY = 0;
    let touchMoveY = 0;
    let pulling = false;
    let newCount = 0;
    
    // Load announcements on page load
    document.addEventListener('DOMContentLoaded', function() {
        loadAnnouncements();
        startAutoRefresh();
        initPullToRefresh();
    });
    
    function initPullToRefresh() {
        const container = document.querySelector('.main-content .container');
        const ptrContainer = document.getElementById('ptrContainer');
        const ptrText = document.getElementById('ptrText');
        
        container.addEventListener('touchstart', function(e) {
            if (window.scrollY === 0) {
                touchStartY = e.touches[0].clientY;
                pulling = true;
            }
        }, { passive: true });
        
        container.addEventListener('touchmove', function(e) {
            if (!pulling || window.scrollY > 0) return;
            
            touchMoveY = e.touches[0].clientY;
            const pullDistance = touchMoveY - touchStartY;
            
            if (pullDistance > 0 && pullDistance < 150) {
                ptrContainer.style.height = Math.min(pullDistance, 50) + 'px';
                
                if (pullDistance > 70) {
                    ptrText.textContent = 'Release to refresh';
                } else {
                    ptrText.textContent = 'Pull to refresh';
                }
            }
        }, { passive: true });
        
        container.addEventListener('touchend', function(e) {
            if (!pulling) return;
            
            const pullDistance = touchMoveY - touchStartY;
            
            if (pullDistance > 70 && window.scrollY === 0) {
                // Trigger refresh
                ptrContainer.style.height = '50px';
                ptrText.textContent = 'Refreshing...';
                refreshAnnouncements();
                
                setTimeout(() => {
                    ptrContainer.style.height = '0';
                }, 1000);
            } else {
                ptrContainer.style.height = '0';
            }
            
            pulling = false;
            touchStartY = 0;
            touchMoveY = 0;
        }, { passive: true });
    }
    
    function startAutoRefresh() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
        refreshInterval = setInterval(checkForUpdates, checkInterval);
    }
    
    function checkForUpdates() {
        const indicator = document.getElementById('refreshIndicator');
        indicator.classList.add('checking');
        
        fetch('check_announcements_updates.php?since=' + lastUpdate)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.hasUpdates) {
                    newCount = data.count;
                    
                    // Update the badge
                    const newCountBadge = document.getElementById('newCount');
                    newCountBadge.textContent = '✨ ' + newCount + ' new';
                    newCountBadge.style.display = 'inline-block';
                    newCountBadge.classList.add('pulse');
                    
                    // Show click hint
                    document.getElementById('clickHint').style.display = 'block';
                    
                    // Vibrate on mobile if supported (once)
                    if (navigator.vibrate) navigator.vibrate(50);
                    
                    // Log silently
                    console.log('New announcements available:', newCount);
                }
            })
            .catch(error => console.error('Error checking updates:', error))
            .finally(() => {
                indicator.classList.remove('checking');
            });
    }
    
    function refreshAnnouncements() {
        if (isRefreshing) return;
        
        isRefreshing = true;
        const loading = document.getElementById('loading');
        const container = document.getElementById('announcements-container');
        const indicator = document.getElementById('refreshIndicator');
        const newCountBadge = document.getElementById('newCount');
        
        loading.classList.add('active');
        indicator.classList.add('checking');
        
        // Hide badge and hint
        newCountBadge.style.display = 'none';
        newCountBadge.classList.remove('pulse');
        document.getElementById('clickHint').style.display = 'none';
        
        const timestamp = new Date().getTime();
        
        fetch('get_announcements.php?_=' + timestamp)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(data => {
                container.innerHTML = data;
                document.getElementById('last-updated').textContent = new Date().toLocaleTimeString();
                
                // Update lastUpdate timestamp from the response if available
                const timestampInput = document.getElementById('last-updated-timestamp');
                if (timestampInput) {
                    lastUpdate = parseInt(timestampInput.value) || Math.floor(Date.now() / 1000);
                } else {
                    lastUpdate = Math.floor(Date.now() / 1000);
                }
                
                newCount = 0;
                highlightNewItems();
            })
            .catch(error => {
                console.error('Error:', error);
                container.innerHTML = '<div class="alert alert-danger">Error loading announcements. Please try again.</div>';
            })
            .finally(() => {
                loading.classList.remove('active');
                indicator.classList.remove('checking');
                isRefreshing = false;
            });
    }
    
    function highlightNewItems() {
        const items = document.querySelectorAll('.announcement-item');
        items.forEach(item => {
            const itemTime = parseInt(item.dataset.updated || '0');
            if (itemTime > lastUpdate) {
                item.classList.add('highlight');
                setTimeout(() => {
                    item.classList.remove('highlight');
                }, 2000);
            }
        });
    }
    
    function loadAnnouncements() {
        refreshAnnouncements();
    }
    
    // Check for updates when tab becomes visible again
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            checkForUpdates();
        }
    });
    
    // Clean up interval on page unload
    window.addEventListener('beforeunload', function() {
        if (refreshInterval) {
            clearInterval(refreshInterval);
        }
    });
    
    // Add touch feedback
    document.addEventListener('touchstart', function() {}, { passive: true });
    </script>
    
    <script src="../../src/js/main.js"></script>
    <script src="../../src/js/mobile-menu.js"></script>
</body>
</html>
