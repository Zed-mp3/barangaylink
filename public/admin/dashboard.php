<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

$auth = new Auth();
$auth->requireAdmin();

$db = new Database();
$user = $auth->getCurrentUser();

// Get statistics
$total_residents = $db->query("SELECT COUNT(*) as count FROM users WHERE user_type='resident'")->fetch_assoc()['count'];
$total_requests = $db->query("SELECT COUNT(*) as count FROM service_requests")->fetch_assoc()['count'];
$pending_requests = $db->query("SELECT COUNT(*) as count FROM service_requests WHERE status='pending'")->fetch_assoc()['count'];
$total_announcements = $db->query("SELECT COUNT(*) as count FROM announcements")->fetch_assoc()['count'];

// Get recent requests for dashboard
$recent_requests = $db->query("SELECT sr.*, u.full_name, u.email 
                               FROM service_requests sr 
                               JOIN users u ON sr.user_id = u.id 
                               ORDER BY sr.created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Admin Dashboard - BarangayLink</title>
    <link rel="stylesheet" href="../../src/css/main.css?v=<?php echo time(); ?>">
    <style>
        /* Admin Dashboard Specific Styles */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, #2c3e50 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transform: translate(30px, -30px);
        }
        
        .stat-card::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transform: translate(-30px, 30px);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        .stat-card h3 {
            margin: 0 0 10px;
            font-size: 1rem;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .stat-card .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            position: relative;
            z-index: 1;
        }
        
        .stat-card .stat-icon {
            position: absolute;
            bottom: 15px;
            right: 15px;
            font-size: 3rem;
            opacity: 0.3;
            z-index: 1;
        }
        
        .stat-card:nth-child(1) {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stat-card:nth-child(2) {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .stat-card:nth-child(3) {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .stat-card:nth-child(4) {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        
        .system-info {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-left: 4px solid var(--primary-color);
        }
        
        .system-info p {
            margin: 10px 0;
            padding: 5px 0;
            border-bottom: 1px dashed rgba(0,0,0,0.1);
        }
        
        .system-info p:last-child {
            border-bottom: none;
        }
        
        .system-info strong {
            color: var(--primary-color);
            width: 120px;
            display: inline-block;
        }
        
        
        /* Table styles */
        .table th {
            background: var(--primary-color);
            color: white;
            font-weight: 500;
        }
        
        .badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85rem;
        }
        
        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-processing {
            background: #cce5ff;
            color: #004085;
        }
        
        .badge-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-rejected {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* Card header with action */
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header .btn {
            padding: 5px 15px;
            font-size: 0.9rem;
        }
        
        /* Welcome section */
        .welcome-section {
            margin-bottom: 30px;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .welcome-section h1 {
            margin: 0;
            font-size: 2rem;
            position: relative;
            z-index: 1;
        }
        
        .welcome-section p {
            margin: 5px 0 0;
            opacity: 0.9;
            position: relative;
            z-index: 1;
        }
        
        .welcome-section::before {
            content: '🏛️';
            position: absolute;
            bottom: -20px;
            right: -20px;
            font-size: 10rem;
            opacity: 0.2;
            transform: rotate(-10deg);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .welcome-section h1 {
                font-size: 1.5rem;
            }
            
            .system-info strong {
                width: 100px;
            }
        }
        
        /* Quick action buttons */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .quick-action-btn {
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            text-decoration: none;
            color: var(--text-color);
            transition: all 0.3s ease;
        }
        
        .quick-action-btn:hover {
            border-color: var(--primary-color);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .quick-action-btn .icon {
            font-size: 2rem;
            margin-bottom: 5px;
            display: block;
        }
        
        .quick-action-btn span {
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Pull to Refresh Styles (Matching announcements) */
        .ptr-container {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--primary-color);
            color: white;
            font-size: 14px;
            transform: translateY(-100%);
            transition: transform 0.2s ease;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .ptr-container.visible {
            transform: translateY(0);
        }

        .ptr-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .ptr-spinner {
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: ptr-spin 0.8s linear infinite;
        }

        @keyframes ptr-spin {
            to { transform: rotate(360deg); }
        }

        #ptrText {
            font-weight: 500;
        }

        /* Swipe hint */
        .swipe-hint {
            text-align: center;
            color: #999;
            font-size: 12px;
            margin: 5px 0 15px;
            display: none;
        }

        @media (max-width: 768px) {
            .swipe-hint {
                display: block;
            }
        }

        /* Refresh indicator */
        .refresh-indicator {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            font-size: 12px;
            color: #999;
            margin-bottom: 15px;
            padding: 5px 10px;
            background: #f5f5f5;
            border-radius: 20px;
            float: top;
        }

        .refresh-indicator .spinner-small {
            width: 14px;
            height: 14px;
            border: 2px solid rgba(0,0,0,0.1);
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            display: none;
        }

        .refresh-indicator.checking .spinner-small {
            display: inline-block;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Loading overlay */
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
            background: rgba(255,255,255,0.9);
            border-radius: 10px;
            margin: 10px 0;
        }

        .loading.active {
            display: block;
        }

        .loading .spinner {
            width: 30px;
            height: 30px;
            border: 3px solid #f3f3f3;
            border-top-color: var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        /* New badge */
        .new-badge {
            background: linear-gradient(135deg, #ff6b6b 0%, #ff5252 100%);
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 2px 5px rgba(255,82,82,0.3);
            transition: all 0.3s ease;
        }

        .new-badge:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(255,82,82,0.4);
        }

        .new-badge.pulse {
            animation: badge-pulse 1.5s infinite;
        }

        @keyframes badge-pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(255,82,82,0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(255,82,82,0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(255,82,82,0);
            }
        }

        /* Click hint */
        .click-hint {
            text-align: center;
            color: #666;
            font-size: 11px;
            margin: 5px 0 10px;
            animation: fadeInOut 4s ease-in-out;
        }

        @keyframes fadeInOut {
            0%, 100% { opacity: 0; }
            10%, 90% { opacity: 1; }
        }

        /* Dashboard header */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 10px;
        }

        .dashboard-header h1 {
            margin: 0;
            font-size: 1.8rem;
        }

        @media (max-width: 768px) {
            .dashboard-header h1 {
                font-size: 1.5rem;
            }
        }

        /* Disable native pull-to-refresh */
        body {
            overscroll-behavior-y: contain;
        }
    </style>
</head>
<body data-user-role="<?php echo $_SESSION['user_type']; ?>">
    <!-- Pull to Refresh Container (Matching announcements) -->
    <div class="ptr-container" id="ptrContainer">
        <div class="ptr-content">
            <div class="ptr-spinner"></div>
            <span id="ptrText">Pull to refresh</span>
        </div>
    </div>

    <!-- Side Navigation (Admin Version) -->
    <div class="sidenav admin-sidenav">
        <div class="sidenav-header">
            <div class="sidenav-logo">
                <img src="../../assets/1772429077726-removebg-preview.png" alt="BarangayLink Logo" onerror="this.style.display='none'">
            </div>
            <h2>BarangayLink</h2>
            <p>Admin Portal</p>
        </div>
        
        <div class="sidenav-user">
            <div class="sidenav-avatar admin-avatar">
                <?php 
                $initial = strtoupper(substr($user['full_name'], 0, 1));
                echo $initial;
                ?>
            </div>
            <div class="sidenav-user-info">
                <div class="sidenav-user-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                <div class="sidenav-user-type admin-badge">Administrator</div>
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
                <a href="service_requests.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'service_requests.php' ? 'active' : ''; ?>">
                    <span class="sidenav-icon">📋</span>
                    <span class="sidenav-text">Service Requests</span>
                </a>
            </li>
            <li class="sidenav-item">
                <a href="residents.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'residents.php' ? 'active' : ''; ?>">
                    <span class="sidenav-icon">👥</span>
                    <span class="sidenav-text">Residents</span>
                </a>
            </li>
            <li class="sidenav-item">
                <a href="reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                    <span class="sidenav-icon">📈</span>
                    <span class="sidenav-text">Reports</span>
                </a>
            </li>
            <?php if ($_SESSION['user_type'] === 'admin'): ?>
<li class="sidenav-item">
   <a href="registration_codes.php">
      <span class="sidenav-icon">🔑</span>
      <span class="sidenav-text">Registration Codes</span>
   </a>
</li>
<?php endif; ?>
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
            <p class="sidenav-version">Admin v1.0.0</p>
        </div>
    </div>

    <!-- Top Navbar (Mobile Only) -->
    <nav class="navbar mobile-only">
        <div class="navbar-brand">
            <a href="dashboard.php">
                <img src="../../assets/1772429077726-removebg-preview.png" alt="BarangayLink" style="height: 30px; vertical-align: middle;">
                BarangayLink Admin
            </a>
        </div>
        <div class="user-info">
            <span class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></span>
        </div>
        <button class="mobile-menu-btn" id="mobile-menu-btn">☰</button>
    </nav>
    
    <div class="main-content">
        <div class="container">
            <!-- Dashboard header with new badge -->
            <div class="dashboard-header">
                <h1>Admin Dashboard</h1>
                <span id="newCount" class="new-badge" style="display: none;" onclick="refreshDashboard()">✨ 0 new</span>
            </div>
            
            <!-- Subtle hint that badge is clickable -->
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
                Loading dashboard data...
            </div>
            
            <!-- Welcome Section -->
            <div class="welcome-section">
                <h1>Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>! 👋</h1>
                <p>Here's what's happening in your barangay today.</p>
            </div>
            
            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="announcements.php?action=add" class="quick-action-btn" data-tooltip="Create new announcement">
                    <span class="icon">📢</span>
                    <span>New Announcement</span>
                </a>
                <a href="service_requests.php?filter=pending" class="quick-action-btn" data-tooltip="View pending requests">
                    <span class="icon">⏳</span>
                    <span>Pending Requests</span>
                </a>
                <a href="residents.php?action=add" class="quick-action-btn" data-tooltip="Register new resident">
                    <span class="icon">👤</span>
                    <span>Add Resident</span>
                </a>
                <a href="reports.php" class="quick-action-btn" data-tooltip="Generate reports">
                    <span class="icon">📊</span>
                    <span>Generate Report</span>
                </a>
            </div>
            
            <!-- Statistics -->
            <div class="stats-grid">
                <div class="stat-card" data-tooltip="Total registered residents">
                    <h3>Total Residents</h3>
                    <div class="stat-number" id="totalResidents"><?php echo $total_residents; ?></div>
                    <div class="stat-icon">👥</div>
                </div>
                <div class="stat-card" data-tooltip="Total service requests received">
                    <h3>Total Requests</h3>
                    <div class="stat-number" id="totalRequests"><?php echo $total_requests; ?></div>
                    <div class="stat-icon">📋</div>
                </div>
                <div class="stat-card" data-tooltip="Requests awaiting action">
                    <h3>Pending Requests</h3>
                    <div class="stat-number" id="pendingRequests"><?php echo $pending_requests; ?></div>
                    <div class="stat-icon">⏳</div>
                </div>
                <div class="stat-card" data-tooltip="Total announcements posted">
                    <h3>Announcements</h3>
                    <div class="stat-number" id="totalAnnouncements"><?php echo $total_announcements; ?></div>
                    <div class="stat-icon">📢</div>
                </div>
            </div>
            
            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <h3>Recent Service Requests</h3>
                            <a href="service_requests.php" class="btn btn-primary btn-sm">View All</a>
                        </div>
                        <div class="card-body" id="requestsContainer">
                            <?php if ($recent_requests->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Resident</th>
                                                <th>Type</th>
                                                <th>Status</th>
                                                <th>Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($row = $recent_requests->fetch_assoc()): ?>
                                            <tr>
                                                <td>#<?php echo $row['id']; ?></td>
                                                <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($row['request_type']); ?></td>
                                                <td>
                                                    <?php
                                                    $statusClass = '';
                                                    switch($row['status']) {
                                                        case 'pending':
                                                            $statusClass = 'badge-pending';
                                                            break;
                                                        case 'processing':
                                                            $statusClass = 'badge-processing';
                                                            break;
                                                        case 'completed':
                                                            $statusClass = 'badge-completed';
                                                            break;
                                                        case 'rejected':
                                                            $statusClass = 'badge-rejected';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($row['status']); ?></span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                                <td>
                                                    <a href="service_requests.php?view=<?php echo $row['id']; ?>" class="btn btn-primary btn-sm" data-tooltip="View request details">View</a>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-center">No service requests yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <h3>System Overview</h3>
                        </div>
                        <div class="card-body system-info" id="systemInfo">
                            <p><strong>Administrator:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                            <p><strong>Last Login:</strong> <?php echo isset($_SESSION['last_login']) ? date('F d, Y h:i A', $_SESSION['last_login']) : 'First login'; ?></p>
                            <p><strong>System Version:</strong> 1.0.0</p>
                            <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                            <p><strong>Database:</strong> MySQL</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="../../src/js/admin-dashboard.js"></script>
    <script src="../../src/js/main.js"></script>
    <script src="../../src/js/mobile-menu.js"></script>
    
    
</body>
</html>