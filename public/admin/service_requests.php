<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';


$auth = new Auth();
$auth->requireAdmin();

$db = new Database();
$user = $auth->getCurrentUser();

// Pass user data to JavaScript
$user_id = $user['id'];
$user_name = $user['full_name'];
?>
<script>
window.currentUserId = <?php echo $user_id; ?>;
window.currentUserName = '<?php echo addslashes($user_name); ?>';
</script>
<?php

/**
 * Send push notification using Kreait Firebase PHP SDK v5
 * @param array $tokens Array of device tokens
 * @param array $notificationData ['title' => '', 'body' => '', 'icon' => '']
 * @param array $data Optional additional payload
 * @return array Response per token
 */


/**
 * Remove invalid FCM token from database
 */
function markTokenForRemoval(string $token): void {
    global $db;
    if (!$db) return;

    $tokenEscaped = $db->escape($token);
    $db->query("DELETE FROM user_devices WHERE fcm_token='$tokenEscaped'");
    error_log("🗑️ Removed invalid token from database: $token");
}

// Handle status update with reason
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $request_id = (int)$_POST['request_id'];
    $status = $db->escape($_POST['status']);
    $reason = isset($_POST['reason']) ? $db->escape($_POST['reason']) : '';
    $notes = isset($_POST['notes']) ? $db->escape($_POST['notes']) : '';
    
    // Build update query
    $updates = ["status='$status'", "updated_at=NOW()"];
    
    // Add reason if status is rejected or completed with notes
    if ($status == 'rejected' && !empty($reason)) {
        $updates[] = "admin_notes='Rejection reason: $reason'";
    } elseif (!empty($notes)) {
        $updates[] = "admin_notes='$notes'";
    }
    
    $sql = "UPDATE service_requests SET " . implode(', ', $updates) . " WHERE id=$request_id";
    
    if ($db->query($sql)) {
        // ===== STORE NOTIFICATION FOR THE RESIDENT =====
        // Get the resident's ID
        $resident_sql = "SELECT user_id FROM service_requests WHERE id = $request_id";
        $resident_result = $db->query($resident_sql);
        
        if ($resident_result && $resident_result->num_rows > 0) {
            $resident = $resident_result->fetch_assoc();
            $resident_id = (int)$resident['user_id'];
            
            // Get request details for the notification
            $request_details = $db->query("SELECT * FROM service_requests WHERE id = $request_id")->fetch_assoc();
            
            // Create message based on status
           $request_type = $request_details['request_type'];
$status_display = ucfirst(str_replace('_', ' ', $status));

$title = "📋 $request_type Update";
$message = "Your request status for '$request_type' has been updated to: $status_display";

if ($status == 'rejected' && !empty($reason)) {
    $title = "❌ $request_type Rejected";
    $message = "Your '$request_type' request was rejected. Reason: $reason";
}
            
            // Prepare data JSON
            $notification_data = json_encode([
                'request_id' => $request_id,
                'status' => $status,
                'request_type' => $request_details['request_type']
            ]);
            
            // Insert notification into database
            $notification_sql = "INSERT INTO notifications (user_id, type, title, message, data, created_at) 
                                VALUES (
                                    $resident_id, 
                                    'request_update', 
                                    '" . $db->escape($title) . "', 
                                    '" . $db->escape($message) . "', 
                                    '" . $db->escape($notification_data) . "', 
                                    NOW()
                                )";
            $db->query($notification_sql);
            
            // ===== SEND PUSH NOTIFICATION =====
            $push_sql = "SELECT fcm_token FROM user_devices WHERE user_id = $resident_id AND last_active > DATE_SUB(NOW(), INTERVAL 30 DAY)";
            $push_result = $db->query($push_sql);
            
            if ($push_result && $push_result->num_rows > 0) {
                // Prepare FCM notification
                $fcm_notification = [
'title' => $title,
                    'body' => $message,
                    'icon' => 'ic_stat_icon',
                    'click_action' => 'OPEN_REQUEST',
                    'sound' => 'default'
                ];
                
                $fcm_data = [
                    'type' => 'request_update',
                    'requestId' => (string)$request_id,
                    'status' => $status,
                    'click_action' => 'OPEN_REQUEST'
                ];
                
                $tokens = [];
                while($device = $push_result->fetch_assoc()) {
                    $tokens[] = $device['fcm_token'];
                }
                
                // Send FCM notification using the SDK
                sendFCMNotification($tokens, $fcm_notification, $fcm_data);
            }
            // ===== END PUSH NOTIFICATION =====
        }
        
        // Set success flag for JavaScript
        echo "<script>sessionStorage.setItem('notification', JSON.stringify({message: 'Request status updated successfully!', type: 'success'}));</script>";
    } else {
        echo "<script>sessionStorage.setItem('notification', JSON.stringify({message: 'Failed to update status', type: 'error'}));</script>";
    }
    
    // Refresh the page to show updated data
    echo "<script>window.location.href = 'service_requests.php" . (isset($_GET['status']) ? '?status='.$_GET['status'] : '') . "';</script>";
    exit();
}

// Get filter
$status_filter = isset($_GET['status']) ? $db->escape($_GET['status']) : '';
$search = isset($_GET['search']) ? $db->escape($_GET['search']) : '';

$where_clauses = [];
if ($status_filter) {
    $where_clauses[] = "sr.status='$status_filter'";
}
if ($search) {
    $where_clauses[] = "(u.full_name LIKE '%$search%' OR u.contact_number LIKE '%$search%' OR sr.request_type LIKE '%$search%' OR sr.description LIKE '%$search%')";
}

$where_sql = !empty($where_clauses) ? "WHERE " . implode(' AND ', $where_clauses) : "";

// Get all service requests
$sql = "SELECT sr.*, u.full_name, u.address, u.contact_number, u.email 
        FROM service_requests sr 
        JOIN users u ON sr.user_id = u.id 
        $where_sql 
        ORDER BY sr.created_at DESC";
$requests = $db->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Requests - BarangayLink Admin</title>
    <link rel="stylesheet" href="../../src/css/main.css?v=<?php echo time(); ?>">
    <style>
        .filter-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }
        .filter-buttons .btn.active {
            opacity: 1;
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }
        
        /* Reason input field */
        .reason-field {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 3px solid #e74c3c;
            display: none;
        }
        
        .reason-field.show {
            display: block;
            animation: slideDown 0.3s ease;
        }
        
        .notes-field {
            margin-top: 15px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            border-left: 3px solid #3498db;
            display: none;
        }
        
        .notes-field.show {
            display: block;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .request-type-badge {
            background: #e9ecef;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
        }
        
        .admin-notes {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            font-style: italic;
            color: #666;
            margin-top: 10px;
        }
        
        .search-box {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
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
            <li class="sidenav-item">
               <a href="registration_codes.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'registration_codes.php' ? 'active' : ''; ?>">
                    <span class="sidenav-icon">🔑</span>
                    <span class="sidenav-text">Registration Codes</span>
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
            <span class="user-avatar"><?php echo $initial; ?></span>
            <span class="user-name"><?php echo htmlspecialchars($user['full_name']); ?></span>
        </div>
        <button class="mobile-menu-btn" id="mobile-menu-btn">☰</button>
    </nav>
    
    <div class="main-content">
        <div class="container">
            <h1>Manage Service Requests</h1>
            
            <!-- Search and Filter -->
            <div class="card">
                <div class="card-header">
                    Filter & Search
                </div>
                <div class="card-body">
                    <div class="search-box">
                        <form method="GET" action="">
                            <div class="row">
                                <div class="col">
                                    <input type="text" name="search" class="form-control" placeholder="Search by name, contact, request type..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div class="col">
                                    <button type="submit" class="btn btn-primary">🔍 Search</button>
                                    <?php if ($search || $status_filter): ?>
                                        <a href="service_requests.php" class="btn btn-secondary">🗑️ Clear</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <div class="filter-buttons">
                        <a href="?status=pending<?php echo $search ? '&search='.urlencode($search) : ''; ?>" 
                           class="btn btn-warning <?php echo $status_filter == 'pending' ? 'active' : ''; ?>" 
                           data-tooltip="Show pending requests">
                           ⏳ Pending
                        </a>
                        <a href="?status=in_progress<?php echo $search ? '&search='.urlencode($search) : ''; ?>" 
                           class="btn btn-info <?php echo $status_filter == 'in_progress' ? 'active' : ''; ?>" 
                           data-tooltip="Show in-progress requests">
                           🔄 In Progress
                        </a>
                        <a href="?status=completed<?php echo $search ? '&search='.urlencode($search) : ''; ?>" 
                           class="btn btn-success <?php echo $status_filter == 'completed' ? 'active' : ''; ?>" 
                           data-tooltip="Show completed requests">
                           ✅ Completed
                        </a>
                        <a href="?status=rejected<?php echo $search ? '&search='.urlencode($search) : ''; ?>" 
                           class="btn btn-danger <?php echo $status_filter == 'rejected' ? 'active' : ''; ?>" 
                           data-tooltip="Show rejected requests">
                           ❌ Rejected
                        </a>
                        <a href="service_requests.php" class="btn btn-primary <?php echo !$status_filter ? 'active' : ''; ?>" data-tooltip="Show all requests">
                           📋 All Requests
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Requests List -->
            <div class="card">
                <div class="card-header">
                    Service Requests List
                    <span class="badge bg-info"><?php echo $requests->num_rows; ?> total</span>
                </div>
                <div class="card-body">
                    <?php if ($requests->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table sortable" id="requestsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Resident</th>
                                        <th>Request Type</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $requests->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $row['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($row['full_name']); ?></strong><br>
                                            <small>📞 <?php echo htmlspecialchars($row['contact_number'] ?: 'N/A'); ?></small><br>
                                            <small>✉️ <?php echo htmlspecialchars($row['email']); ?></small>
                                        </td>
                                        <td>
                                            <span class="request-type-badge"><?php echo htmlspecialchars($row['request_type']); ?></span>
                                        </td>
                                        <td><?php echo getStatusBadge($row['status']); ?></td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($row['created_at'])); ?><br>
                                            <small><?php echo timeAgo($row['created_at']); ?></small>
                                        </td>
                                        <td>
                                            <button onclick="toggleRequestDetails(<?php echo $row['id']; ?>)" class="btn btn-primary btn-sm" data-tooltip="View details">👁️</button>
                                            <button onclick="toggleUpdateForm(<?php echo $row['id']; ?>)" class="btn btn-success btn-sm" data-tooltip="Update status">🔄</button>
                                        </td>
                                    </tr>
                                    
                                    <!-- Hidden detail row -->
                                    <tr id="details-<?php echo $row['id']; ?>" class="hidden">
                                        <td colspan="6">
                                            <div class="card" style="margin: 10px 0;">
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col">
                                                            <h4>Request Details</h4>
                                                            <p><strong>Description:</strong><br> <?php echo nl2br(htmlspecialchars($row['description'])); ?></p>
                                                            <p><strong>Address:</strong><br> <?php echo nl2br(htmlspecialchars($row['address'] ?: 'Not provided')); ?></p>
                                                            
                                                            <?php if (!empty($row['admin_notes'])): ?>
                                                                <div class="admin-notes">
                                                                    <strong>📝 Admin Notes:</strong><br>
                                                                    <?php echo nl2br(htmlspecialchars($row['admin_notes'])); ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="col">
                                                            <h4>Timeline</h4>
                                                            <p><strong>Submitted:</strong> <?php echo date('F d, Y h:i A', strtotime($row['created_at'])); ?></p>
                                                            <?php if ($row['updated_at'] && $row['updated_at'] != $row['created_at']): ?>
                                                                <p><strong>Last Updated:</strong> <?php echo date('F d, Y h:i A', strtotime($row['updated_at'])); ?></p>
                                                                <p><small><?php echo timeAgo($row['updated_at']); ?></small></p>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    
                                    <!-- Hidden update form row -->
                                    <tr id="update-<?php echo $row['id']; ?>" class="hidden">
                                        <td colspan="6">
                                            <div class="card" style="margin: 10px 0;">
                                                <div class="card-body">
                                                    <form method="POST" action="" onsubmit="return validateUpdateForm(<?php echo $row['id']; ?>)">
                                                        <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                                        
                                                        <div class="form-group">
                                                            <label>Update Status</label>
                                                            <select name="status" id="status-<?php echo $row['id']; ?>" class="form-control" onchange="toggleReasonField(<?php echo $row['id']; ?>)" required>
                                                                <option value="pending" <?php echo $row['status'] == 'pending' ? 'selected' : ''; ?>>⏳ Pending</option>
                                                                <option value="in_progress" <?php echo $row['status'] == 'in_progress' ? 'selected' : ''; ?>>🔄 In Progress</option>
                                                                <option value="completed" <?php echo $row['status'] == 'completed' ? 'selected' : ''; ?>>✅ Completed</option>
                                                                <option value="rejected" <?php echo $row['status'] == 'rejected' ? 'selected' : ''; ?>>❌ Rejected</option>
                                                            </select>
                                                        </div>
                                                        
                                                        <!-- Reason for Rejection Field -->
                                                        <div class="reason-field" id="reason-<?php echo $row['id']; ?>">
                                                            <label>Reason for Rejection <span style="color: red;">*</span></label>
                                                            <textarea name="reason" class="form-control" rows="3" placeholder="Please provide the reason for rejection..." data-tooltip="Required when rejecting a request"></textarea>
                                                            <small style="color: #666;">This reason will be visible to the resident</small>
                                                        </div>
                                                        
                                                        <!-- Admin Notes Field (for other statuses) -->
                                                        <div class="notes-field" id="notes-<?php echo $row['id']; ?>">
                                                            <label>Admin Notes (Optional)</label>
                                                            <textarea name="notes" class="form-control" rows="3" placeholder="Add any internal notes about this request..."></textarea>
                                                            <small style="color: #666;">These notes are for admin reference only</small>
                                                        </div>
                                                        
                                                        <div class="form-group" style="margin-top: 15px;">
                                                            <button type="submit" name="update_status" class="btn btn-success">Update Status</button>
                                                            <button type="button" onclick="toggleUpdateForm(<?php echo $row['id']; ?>)" class="btn btn-secondary">Cancel</button>
                                                        </div>
                                                    </form>
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
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    
    <script>
    // Check for stored notification on page load
    document.addEventListener('DOMContentLoaded', function() {
        const notification = sessionStorage.getItem('notification');
        if (notification) {
            const { message, type } = JSON.parse(notification);
            // Wait a tiny bit for everything to load
            setTimeout(() => {
                if (window.BarangayLink && window.BarangayLink.showNotification) {
                    window.BarangayLink.showNotification(message, type);
                } else {
                    // Fallback if BarangayLink is not ready
                    alert(message);
                }
            }, 100);
            // Clear the notification
            sessionStorage.removeItem('notification');
        }
    });
    
    function toggleRequestDetails(id) {
        const detailsRow = document.getElementById('details-' + id);
        const updateRow = document.getElementById('update-' + id);
        
        detailsRow.classList.toggle('hidden');
        if (!updateRow.classList.contains('hidden')) {
            updateRow.classList.add('hidden');
        }
    }
    
    function toggleUpdateForm(id) {
        const updateRow = document.getElementById('update-' + id);
        const detailsRow = document.getElementById('details-' + id);
        
        updateRow.classList.toggle('hidden');
        if (!detailsRow.classList.contains('hidden')) {
            detailsRow.classList.add('hidden');
        }
        
        // Reset form fields when opening
        if (updateRow.classList.contains('hidden')) {
            // Form is being closed, reset fields
            document.getElementById('reason-' + id).classList.remove('show');
            document.getElementById('notes-' + id).classList.remove('show');
        } else {
            // Form is being opened, check current status
            toggleReasonField(id);
        }
    }
    
    function toggleReasonField(id) {
        const statusSelect = document.getElementById('status-' + id);
        const reasonField = document.getElementById('reason-' + id);
        const notesField = document.getElementById('notes-' + id);
        const reasonTextarea = reasonField.querySelector('textarea');
        
        if (statusSelect.value === 'rejected') {
            reasonField.classList.add('show');
            notesField.classList.remove('show');
            reasonTextarea.setAttribute('required', 'required');
        } else {
            reasonField.classList.remove('show');
            notesField.classList.add('show');
            reasonTextarea.removeAttribute('required');
        }
    }
    
    function validateUpdateForm(id) {
        const statusSelect = document.getElementById('status-' + id);
        const reasonField = document.getElementById('reason-' + id);
        const reasonTextarea = reasonField.querySelector('textarea');
        
        if (statusSelect.value === 'rejected') {
            if (!reasonTextarea.value.trim()) {
                // Use the same notification system for validation errors
                if (window.BarangayLink && window.BarangayLink.showNotification) {
                    window.BarangayLink.showNotification('Please provide a reason for rejection', 'error');
                } else {
                    alert('Please provide a reason for rejection');
                }
                return false;
            }
        }
        return true;
    }
    </script>
    
    <script src="../../src/js/main.js"></script>
    <script src="../../src/js/mobile-menu.js"></script>
</body>
</html>
