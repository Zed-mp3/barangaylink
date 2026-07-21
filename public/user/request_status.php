<?php
// public/user/request_status.php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect('../index.php');
}

$db = new Database();
$user = $auth->getCurrentUser();

// Get filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Get user's requests
$where_clause = "WHERE user_id = {$user['id']}";
if ($status_filter) {
    $status_filter_escaped = $db->escape($status_filter);
    $where_clause .= " AND status = '$status_filter_escaped'";
}

$requests = $db->query("SELECT * FROM service_requests $where_clause ORDER BY created_at DESC");

// Get latest update timestamp for auto-refresh
$latest_sql = "SELECT MAX(UNIX_TIMESTAMP(updated_at)) as latest FROM service_requests WHERE user_id = {$user['id']}";
$latest_result = $db->query($latest_sql);
$latest_row = $latest_result->fetch_assoc();
$initial_last_update = $latest_row['latest'] ? (int)$latest_row['latest'] : time();

// Pass user data to JavaScript
$user_id = $user['id'];
$user_name = $user['full_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>My Requests - BarangayLink</title>
    <link rel="stylesheet" href="../../src/css/main.css?v=<?php echo time(); ?>">
    <script>
    // Pass user data to JavaScript
    window.currentUserId = <?php echo $user_id; ?>;
    window.currentUserName = '<?php echo addslashes($user_name); ?>';
    </script>
    <style>
        .filter-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        .filter-buttons .btn.active {
            opacity: 1;
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        /* Admin notes style for residents */
        .admin-notes {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
            border-left: 4px solid;
        }
        
        .admin-notes.rejection {
            border-left-color: #e74c3c;
            background: #fef5f5;
        }
        
        .admin-notes.info {
            border-left-color: #3498db;
            background: #f0f7ff;
        }
        
        .admin-notes strong {
            display: block;
            margin-bottom: 8px;
            font-size: 0.95rem;
        }
        
        .admin-notes p {
            margin: 0;
            color: #666;
            font-style: italic;
        }
        
        /* Table styles */
        .table-responsive {
            overflow-x: auto;
        }
        
        .hidden {
            display: none;
        }
        
        .details-row {
            background: #f9f9f9;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 0.85rem;
        }
        
        /* New badge for request updates */
        .request-update-badge {
            display: inline-block;
            background: var(--success-color);
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            margin-left: 5px;
            animation: pulse 1.5s infinite;
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
            
            <h1>My Service Requests</h1>
            
            <!-- New updates badge -->
            <div style="display: flex; justify-content: flex-end; margin-bottom: 10px;">
                <span id="newCount" class="new-badge" style="display: none;" onclick="refreshRequests()">✨ 0 new</span>
            </div>
            
            <!-- Click hint -->
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
                Loading latest updates...
            </div>
            
            <div class="card">
                <div class="card-header">
                    Filter Requests
                </div>
                <div class="card-body">
                    <div class="filter-buttons">
                        <a href="?status=pending" class="btn btn-warning <?php echo $status_filter == 'pending' ? 'active' : ''; ?>" data-tooltip="Show pending requests">⏳ Pending</a>
                        <a href="?status=in_progress" class="btn btn-info <?php echo $status_filter == 'in_progress' ? 'active' : ''; ?>" data-tooltip="Show in-progress requests">🔄 In Progress</a>
                        <a href="?status=completed" class="btn btn-success <?php echo $status_filter == 'completed' ? 'active' : ''; ?>" data-tooltip="Show completed requests">✅ Completed</a>
                        <a href="?status=rejected" class="btn btn-danger <?php echo $status_filter == 'rejected' ? 'active' : ''; ?>" data-tooltip="Show rejected requests">❌ Rejected</a>
                        <a href="request_status.php" class="btn btn-primary <?php echo !$status_filter ? 'active' : ''; ?>" data-tooltip="Show all requests">📋 All Requests</a>
                    </div>
                </div>
            </div>
            
            <div class="card" id="requests-card">
                <div class="card-header">
                    Your Requests
                </div>
                <div class="card-body">
                    <div id="requests-container">
                        <?php if ($requests->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table sortable" id="requestsTable">
                                    <thead>
                                        <tr>
                                            <th>Request Type</th>
                                            <th>Status</th>
                                            <th>Submitted</th>
                                            <th>Last Updated</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($row = $requests->fetch_assoc()): ?>
                                        <tr data-request-id="<?php echo $row['id']; ?>" data-updated="<?php echo strtotime($row['updated_at']); ?>">
                                            <td><?php echo htmlspecialchars($row['request_type']); ?></td>
                                            <td><?php echo getStatusBadge($row['status']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                            <td><?php echo $row['updated_at'] != $row['created_at'] ? timeAgo($row['updated_at']) : 'Not updated'; ?></td>
                                            <td>
                                                <button onclick="toggleDetails(<?php echo $row['id']; ?>)" class="btn btn-primary btn-sm" data-tooltip="View details">👁️ View</button>
                                            </td>
                                        </tr>
                                        <tr id="details-<?php echo $row['id']; ?>" class="hidden details-row">
                                            <td colspan="5">
                                                <div class="card" style="margin: 10px 0;">
                                                    <div class="card-body">
                                                        <h4>Request Details</h4>
                                                        <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                                                        <p><strong>Submitted:</strong> <?php echo date('F d, Y h:i A', strtotime($row['created_at'])); ?></p>
                                                        <?php if ($row['updated_at'] != $row['created_at']): ?>
                                                            <p><strong>Last Updated:</strong> <?php echo date('F d, Y h:i A', strtotime($row['updated_at'])); ?></p>
                                                        <?php endif; ?>
                                                        
                                                        <!-- Show admin notes/rejection reason if available -->
                                                        <?php if (!empty($row['admin_notes'])): ?>
                                                            <div class="admin-notes <?php echo strpos($row['admin_notes'], 'Rejection reason:') === 0 ? 'rejection' : 'info'; ?>">
                                                                <strong>
                                                                    <?php if (strpos($row['admin_notes'], 'Rejection reason:') === 0): ?>
                                                                        ❌ Rejection Reason:
                                                                    <?php else: ?>
                                                                        📝 Admin Notes:
                                                                    <?php endif; ?>
                                                                </strong>
                                                                <p>
                                                                    <?php 
                                                                    // Remove the "Rejection reason: " prefix if present
                                                                    $notes = $row['admin_notes'];
                                                                    if (strpos($notes, 'Rejection reason:') === 0) {
                                                                        $notes = substr($notes, 16); // Remove "Rejection reason: "
                                                                    }
                                                                    echo nl2br(htmlspecialchars($notes));
                                                                    ?>
                                                                </p>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center">No service requests found.</p>
                            <div class="text-center">
                                <a href="service_request.php" class="btn btn-success">📝 Submit New Request</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
        <script>
    // Initialize push notifications
    import('../../src/js/push-notification-service.js').then(module => {
        const pushService = module.default;
        if (window.currentUserId) {
            pushService.initialize();
        }
    });

    // Initialize with initial last update timestamp
    let lastUpdate = <?php echo $initial_last_update; ?>;
    let isRefreshing = false;
    let autoRefreshManager;
    let pullToRefresh;
    let lastNotificationId = 0;
    let notificationService = null;
    let lastRequestUpdate = <?php echo $initial_last_update; ?>;

    document.addEventListener('DOMContentLoaded', function() {
        // Get current status filter
        const urlParams = new URLSearchParams(window.location.search);
        const statusFilter = urlParams.get('status');
        
        // Initialize auto-refresh manager with status filter
        let checkUrl = 'check_requests_updates.php';
        if (statusFilter) {
            checkUrl += '?status=' + encodeURIComponent(statusFilter);
        }
        
        autoRefreshManager = new BarangayLink.AutoRefreshManager({
            interval: 15000,
            lastUpdate: lastUpdate,
            checkUrl: checkUrl,
            onUpdate: function(data) {
                console.log('Request updates available:', data.count);
            }
        });
        
        // Initialize pull to refresh
        pullToRefresh = new BarangayLink.PullToRefresh({
            onRefresh: refreshRequests
        });
        
        autoRefreshManager.start();
        
        // Initialize native notifications if in Capacitor app
        initNativeNotifications();
        
        // Start checking for notifications
        setInterval(checkForNotifications, 30000); // Check every 30 seconds
        
        // Add sorting functionality to table
        const table = document.getElementById('requestsTable');
        if (table) {
            const headers = table.querySelectorAll('th');
            headers.forEach((header, index) => {
                header.addEventListener('click', () => {
                    sortTable(index);
                });
                header.style.cursor = 'pointer';
                header.title = 'Click to sort';
            });
        }
    });

    // Initialize native notifications
    async function initNativeNotifications() {
        if (window.Capacitor && window.Capacitor.isNative) {
            try {
                const module = await import('../../src/js/notification-service.js');
                notificationService = module.default;
                await notificationService.initialize();
                console.log('Native notifications ready');
            } catch (error) {
                console.log('Native notifications not available', error);
            }
        }
    }

    // Check for new notifications
    async function checkForNotifications() {
        if (!window.Capacitor || !window.Capacitor.isNative || !notificationService) return;
        
        try {
            const response = await fetch('/public/api/notifications.php?action=check&lastId=' + lastNotificationId);
            const data = await response.json();
            
            if (data.notifications && data.notifications.length > 0) {
                for (const notification of data.notifications) {
                    if (notification.type === 'request_update') {
                        await notificationService.sendRequestStatusNotification(notification.data, window.currentUserName);
                    }
                    
                    // Update last seen ID
                    lastNotificationId = Math.max(lastNotificationId, notification.id);
                    
                    // Mark as read
                    fetch('/public/api/notifications.php?action=mark_read', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'id=' + notification.id
                    });
                }
                
                // Show in-app badge
                if (data.notifications.length > 0) {
                    showNewCountBadge(data.notifications.length);
                }
                
                // Refresh the requests display
                refreshRequests();
            }
        } catch (error) {
            console.error('Error checking notifications:', error);
        }
    }

    // Show new count badge
    function showNewCountBadge(count) {
        const badge = document.getElementById('newCount');
        const hint = document.getElementById('clickHint');
        
        if (badge) {
            badge.textContent = `✨ ${count} new`;
            badge.style.display = 'inline-block';
            badge.classList.add('pulse');
        }
        
        if (hint) {
            hint.style.display = 'block';
            setTimeout(() => {
                hint.style.display = 'none';
            }, 5000);
        }
    }

    function refreshRequests() {
        if (isRefreshing) return;
        
        isRefreshing = true;
        const loading = document.getElementById('loading');
        const indicator = document.getElementById('refreshIndicator');
        const newCountBadge = document.getElementById('newCount');
        
        loading.classList.add('active');
        indicator.classList.add('checking');
        
        // Hide badge and hint
        newCountBadge.style.display = 'none';
        newCountBadge.classList.remove('pulse');
        document.getElementById('clickHint').style.display = 'none';
        
        const timestamp = new Date().getTime();
        
        // Build the AJAX URL with current filters
        const urlParams = new URLSearchParams(window.location.search);
        let ajaxUrl = 'ajax_request_status.php?_=' + timestamp;
        
        // Preserve status filter if any
        const statusFilter = urlParams.get('status');
        if (statusFilter) {
            ajaxUrl += '&status=' + encodeURIComponent(statusFilter);
        }
        
        // Fetch updated content
        fetch(ajaxUrl)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(html => {
                // Update the container with new HTML
                document.getElementById('requests-container').innerHTML = html;
                
                document.getElementById('last-updated').textContent = new Date().toLocaleTimeString();
                
                // Update lastUpdate
                lastUpdate = Math.floor(Date.now() / 1000);
                
                // Update auto-refresh manager
                if (autoRefreshManager) {
                    autoRefreshManager.updateLastUpdate(lastUpdate);
                    autoRefreshManager.reset(lastUpdate);
                }
                
                // Re-attach event listeners
                reattachEventListeners();
                
                // Show success notification
                BarangayLink.showNotification('Requests updated', 'success');
            })
            .catch(error => {
                console.error('Error:', error);
                BarangayLink.showNotification('Failed to load updates', 'error');
            })
            .finally(() => {
                loading.classList.remove('active');
                indicator.classList.remove('checking');
                isRefreshing = false;
                
                // Reset pull to refresh
                if (pullToRefresh) {
                    pullToRefresh.reset();
                }
            });
    }

    function toggleDetails(id) {
        const detailsRow = document.getElementById('details-' + id);
        detailsRow.classList.toggle('hidden');
    }

    function sortTable(colIndex) {
        const table = document.getElementById('requestsTable');
        if (!table) return;
        
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr:not([id^="details-"])'));
        
        const sortedRows = rows.sort((a, b) => {
            const aCol = a.children[colIndex].textContent.trim();
            const bCol = b.children[colIndex].textContent.trim();
            
            // Try to sort by date if it's the date column
            if (colIndex === 2) { // Submitted date column
                const aDate = new Date(aCol);
                const bDate = new Date(bCol);
                return aDate - bDate;
            }
            
            return aCol.localeCompare(bCol);
        });
        
        // Reorder rows
        tbody.innerHTML = '';
        sortedRows.forEach(row => {
            tbody.appendChild(row);
            // Also append the details row that follows
            const nextElement = row.nextElementSibling;
            if (nextElement && nextElement.id && nextElement.id.startsWith('details-')) {
                tbody.appendChild(nextElement);
            }
        });
    }

    function reattachEventListeners() {
        // Re-attach toggle details listeners
        document.querySelectorAll('button[onclick^="toggleDetails"]').forEach(button => {
            const match = button.getAttribute('onclick').match(/\d+/);
            if (match) {
                const id = match[0];
                button.onclick = function() { toggleDetails(id); };
            }
        });
        
        // Re-attach sort functionality
        const table = document.getElementById('requestsTable');
        if (table) {
            const headers = table.querySelectorAll('th');
            headers.forEach((header, index) => {
                header.addEventListener('click', () => {
                    sortTable(index);
                });
            });
        }
    }

    // Check for updates when tab becomes visible again
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden && autoRefreshManager) {
            autoRefreshManager.check();
        }
    });

    // Clean up interval on page unload
    window.addEventListener('beforeunload', function() {
        if (autoRefreshManager) {
            autoRefreshManager.stop();
        }
    });

    // Add touch feedback
    document.addEventListener('touchstart', function() {}, { passive: true });
    </script>
    
    <script src="../../src/js/main.js"></script>
    <script src="../../src/js/mobile-menu.js"></script>
    <script src="../../src/js/pull-to-refresh.js"></script>
</body>
</html>