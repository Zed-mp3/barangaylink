<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

$auth = new Auth();
$auth->requireAdmin();

$db = new Database();
$user = $auth->getCurrentUser();
$message = '';
$error = '';

// Handle Add/Edit Resident
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $full_name = $db->escape($_POST['full_name']);
        $username = $db->escape($_POST['username']);
        $email = $db->escape($_POST['email']);
        $contact_number = $db->escape($_POST['contact_number']);
        $address = $db->escape($_POST['address']);
        
        if ($_POST['action'] == 'add') {
            // Check if username or email already exists
            $check_sql = "SELECT id FROM users WHERE username = '$username' OR email = '$email'";
            $check_result = $db->query($check_sql);
            
            if ($check_result->num_rows > 0) {
                $error = "Username or email already exists!";
                echo "<script>sessionStorage.setItem('notification', JSON.stringify({message: 'Username or email already exists!', type: 'error'}));</script>";
            } else {
                $password = password_hash('default123', PASSWORD_DEFAULT); // Default password
                $sql = "INSERT INTO users (username, email, password, full_name, address, contact_number, user_type, status) 
                        VALUES ('$username', '$email', '$password', '$full_name', '$address', '$contact_number', 'resident', 'active')";
                
                if ($db->query($sql)) {
                    echo "<script>sessionStorage.setItem('notification', JSON.stringify({message: 'Resident added successfully! Default password: default123', type: 'success'}));</script>";
                } else {
                    echo "<script>sessionStorage.setItem('notification', JSON.stringify({message: 'Failed to add resident', type: 'error'}));</script>";
                }
            }
            echo "<script>window.location.href = 'residents.php';</script>";
            exit();
            
        } elseif ($_POST['action'] == 'edit') {
            $resident_id = (int)$_POST['resident_id'];
            
            // Check if username or email already exists for other users
            $check_sql = "SELECT id FROM users WHERE (username = '$username' OR email = '$email') AND id != $resident_id";
            $check_result = $db->query($check_sql);
            
            if ($check_result->num_rows > 0) {
                $error = "Username or email already exists!";
                echo "<script>sessionStorage.setItem('notification', JSON.stringify({message: 'Username or email already exists!', type: 'error'}));</script>";
            } else {
                $sql = "UPDATE users SET 
                        full_name='$full_name', 
                        username='$username', 
                        email='$email', 
                        contact_number='$contact_number', 
                        address='$address' 
                        WHERE id=$resident_id";
                
                if ($db->query($sql)) {
                    echo "<script>sessionStorage.setItem('notification', JSON.stringify({message: 'Resident updated successfully!', type: 'success'}));</script>";
                } else {
                    echo "<script>sessionStorage.setItem('notification', JSON.stringify({message: 'Failed to update resident', type: 'error'}));</script>";
                }
            }
            echo "<script>window.location.href = 'residents.php';</script>";
            exit();
        }
    }
    
    // Handle resident status update
    if (isset($_POST['update_status'])) {
        $resident_id = (int)$_POST['resident_id'];
        $status = $db->escape($_POST['status']);
        
        $sql = "UPDATE users SET status='$status' WHERE id=$resident_id";
        if ($db->query($sql)) {
            echo "<script>sessionStorage.setItem('notification', JSON.stringify({message: 'Resident status updated successfully!', type: 'success'}));</script>";
        }
        echo "<script>window.location.href = 'residents.php';</script>";
        exit();
    }
    
    // Handle password reset
    if (isset($_POST['reset_password'])) {
        $resident_id = (int)$_POST['resident_id'];
        $new_password = password_hash('default123', PASSWORD_DEFAULT);
        
        $sql = "UPDATE users SET password='$new_password' WHERE id=$resident_id";
        if ($db->query($sql)) {
            echo "<script>sessionStorage.setItem('notification', JSON.stringify({message: 'Password reset to default123', type: 'success'}));</script>";
        }
        echo "<script>window.location.href = 'residents.php';</script>";
        exit();
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $resident_id = (int)$_GET['delete'];
    
    // Check if resident has any service requests
    $check_sql = "SELECT id FROM service_requests WHERE user_id = $resident_id LIMIT 1";
    $check_result = $db->query($check_sql);
    
    if ($check_result->num_rows > 0) {
        echo "<script>sessionStorage.setItem('notification', JSON.stringify({message: 'Cannot delete resident with existing requests', type: 'error'}));</script>";
    } else {
        $sql = "DELETE FROM users WHERE id = $resident_id AND user_type = 'resident'";
        if ($db->query($sql)) {
            echo "<script>sessionStorage.setItem('notification', JSON.stringify({message: 'Resident deleted successfully!', type: 'success'}));</script>";
        }
    }
    echo "<script>window.location.href = 'residents.php';</script>";
    exit();
}

// Get resident for editing
$edit_resident = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $edit_result = $db->query("SELECT * FROM users WHERE id = $edit_id AND user_type = 'resident'");
    if ($edit_result->num_rows > 0) {
        $edit_resident = $edit_result->fetch_assoc();
    }
}

// Search functionality
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = '';

if (!empty($search)) {
    $escaped_search = $db->escape($search);
    $where_clause = "AND (full_name LIKE '%$escaped_search%' OR email LIKE '%$escaped_search%' OR username LIKE '%$escaped_search%' OR contact_number LIKE '%$escaped_search%')";
}

// Get all residents
$sql = "SELECT * FROM users WHERE user_type='resident' $where_clause ORDER BY created_at DESC";
$sql = preg_replace('/\s+/', ' ', trim($sql));
$residents = $db->query($sql);

// Get statistics
$total_active = $db->query("SELECT COUNT(*) as count FROM users WHERE user_type='resident' AND status='active'")->fetch_assoc()['count'];
$total_inactive = $db->query("SELECT COUNT(*) as count FROM users WHERE user_type='resident' AND status='inactive'")->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Residents Management - BarangayLink Admin</title>
    <link rel="stylesheet" href="../../src/css/main.css?v=<?php echo time(); ?>">
    <style>
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            animation: slideUp 0.3s ease;
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            background: white;
            border-radius: 12px 12px 0 0;
        }
        
        .modal-header h3 {
            margin: 0;
            color: var(--primary-color);
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #999;
            transition: color 0.3s;
        }
        
        .modal-close:hover {
            color: var(--danger-color);
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 20px;
            border-top: 1px solid var(--border-color);
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        /* Stats cards */
        .stats-mini {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-mini-card {
            background: var(--white);
            padding: 15px;
            border-radius: 8px;
            box-shadow: var(--shadow);
            text-align: center;
        }
        
        .stat-mini-card h4 {
            color: var(--primary-color);
            margin-bottom: 5px;
            font-size: 0.9rem;
        }
        
        .stat-mini-card .number {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--secondary-color);
        }
        
        /* Action buttons */
        .action-group {
            display: flex;
            gap: 5px;
            flex-wrap: wrap;
        }
        
        .btn-icon {
            padding: 5px 10px;
            font-size: 0.85rem;
        }
        
        /* Table actions */
        .table td .btn-sm {
            margin: 2px;
        }
        
        .hidden {
            display: none;
        }
        
        @media (max-width: 768px) {
            .stats-mini {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                width: 95%;
                margin: 10px;
            }
            
            .action-group {
                flex-direction: column;
            }
            
            .action-group .btn {
                width: 100%;
            }
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        /* Password info */
        .password-info {
            background: var(--light-bg);
            padding: 10px;
            border-radius: 4px;
            margin: 10px 0;
            font-size: 0.9rem;
            border-left: 3px solid var(--info-color);
        }
        
        .password-info code {
            background: var(--white);
            padding: 2px 6px;
            border-radius: 3px;
            font-weight: bold;
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
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h1>Residents Management</h1>
                <button onclick="openAddModal()" class="btn btn-success">
                    ➕ Add Resident
                </button>
            </div>
            
            <!-- Mini Stats -->
            <div class="stats-mini">
                <div class="stat-mini-card">
                    <h4>Total Residents</h4>
                    <div class="number"><?php echo $residents->num_rows; ?></div>
                </div>
                <div class="stat-mini-card">
                    <h4>Active</h4>
                    <div class="number"><?php echo $total_active; ?></div>
                </div>
                <div class="stat-mini-card">
                    <h4>Inactive</h4>
                    <div class="number"><?php echo $total_inactive; ?></div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    Search Residents
                </div>
                <div class="card-body">
                    <form method="GET" action="" id="searchForm" onsubmit="return validateSearch()">
                        <div class="row">
                            <div class="col">
                                <input type="text" name="search" id="searchInput" class="form-control" 
                                       placeholder="Search by name, email, username, or contact..." 
                                       value="<?php echo htmlspecialchars($search); ?>" 
                                       data-tooltip="Enter search terms"
                                       autocomplete="off">
                            </div>
                            <div class="col">
                                <button type="submit" class="btn btn-primary" data-tooltip="Search residents">
                                    🔍 Search
                                </button>
                                <?php if (!empty($search)): ?>
                                    <a href="residents.php" class="btn btn-secondary clear-search" data-tooltip="Clear search">
                                        🗑️ Clear
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                    
                    <?php if (!empty($search)): ?>
                        <div class="search-stats">
                            <strong>Search results for:</strong> "<?php echo htmlspecialchars($search); ?>" 
                            | Found <strong><?php echo $residents->num_rows; ?></strong> resident(s)
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    Residents List 
                    <span class="badge bg-info"><?php echo $residents->num_rows; ?> total</span>
                </div>
                <div class="card-body">
                    <?php if ($residents->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table sortable" id="residentsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Full Name</th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Contact</th>
                                        <th>Status</th>
                                        <th>Registered</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($row = $residents->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><?php echo $row['contact_number'] ?: 'N/A'; ?></td>
                                        <td><?php echo getStatusBadge($row['status']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                        <td>
                                            <div class="action-group">
                                                <button onclick="viewResident(<?php echo $row['id']; ?>)" 
                                                        class="btn btn-primary btn-sm btn-icon" data-tooltip="View details">
                                                    👁️
                                                </button>
                                                <button onclick='editResident(<?php echo json_encode($row); ?>)' 
                                                        class="btn btn-warning btn-sm btn-icon" data-tooltip="Edit resident">
                                                    ✏️
                                                </button>
                                                <button onclick="showStatusForm(<?php echo $row['id']; ?>)" 
                                                        class="btn btn-success btn-sm btn-icon" data-tooltip="Change status">
                                                    🔄
                                                </button>
                                                <button onclick="resetPassword(<?php echo $row['id']; ?>)" 
                                                        class="btn btn-info btn-sm btn-icon" data-tooltip="Reset password">
                                                    🔑
                                                </button>
                                                <button onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['full_name'], ENT_QUOTES); ?>')" 
                                                        class="btn btn-danger btn-sm btn-icon" data-tooltip="Delete resident">
                                                    🗑️
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    
                                    <!-- Hidden detail row -->
                                    <tr id="details-<?php echo $row['id']; ?>" class="hidden">
                                        <td colspan="8">
                                            <div class="card" style="margin: 10px 0;">
                                                <div class="card-body">
                                                    <h4>Resident Details</h4>
                                                    <p><strong>Full Name:</strong> <?php echo htmlspecialchars($row['full_name']); ?></p>
                                                    <p><strong>Username:</strong> <?php echo htmlspecialchars($row['username']); ?></p>
                                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($row['email']); ?></p>
                                                    <p><strong>Contact Number:</strong> <?php echo $row['contact_number'] ?: 'Not provided'; ?></p>
                                                    <p><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($row['address'] ?: 'Not provided')); ?></p>
                                                    <p><strong>Status:</strong> <?php echo getStatusBadge($row['status']); ?></p>
                                                    <p><strong>Registered:</strong> <?php echo date('F d, Y h:i A', strtotime($row['created_at'])); ?></p>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    
                                    <!-- Hidden status update form row -->
                                    <tr id="status-<?php echo $row['id']; ?>" class="hidden">
                                        <td colspan="8">
                                            <div class="card" style="margin: 10px 0;">
                                                <div class="card-body">
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="resident_id" value="<?php echo $row['id']; ?>">
                                                        <div class="form-group">
                                                            <label>Update Status</label>
                                                            <select name="status" class="form-control">
                                                                <option value="active" <?php echo $row['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                                <option value="inactive" <?php echo $row['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                            </select>
                                                        </div>
                                                        <button type="submit" name="update_status" class="btn btn-success">Update Status</button>
                                                        <button type="button" onclick="toggleStatusForm(<?php echo $row['id']; ?>)" class="btn btn-secondary">Cancel</button>
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
                        <div class="text-center p-20">
                            <p>No residents found.</p>
                            <?php if (!empty($search)): ?>
                                <a href="residents.php" class="btn btn-primary">Clear Search</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Resident Modal -->
    <div class="modal" id="residentModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Resident</h3>
                <button type="button" class="modal-close" onclick="closeModal()">&times;</button>
            </div>
            <form method="POST" action="" id="residentForm" onsubmit="return validateForm()">
                <div class="modal-body">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="resident_id" id="residentId" value="">
                    
                    <div class="form-group">
                        <label>Full Name <span style="color: red;">*</span></label>
                        <input type="text" name="full_name" id="fullName" class="form-control" required 
                               data-tooltip="Enter resident's full name">
                    </div>
                    
                    <div class="form-group">
                        <label>Username <span style="color: red;">*</span></label>
                        <input type="text" name="username" id="username" class="form-control" required 
                               data-tooltip="Enter username for login">
                    </div>
                    
                    <div class="form-group">
                        <label>Email <span style="color: red;">*</span></label>
                        <input type="email" name="email" id="email" class="form-control" required 
                               data-tooltip="Enter email address">
                    </div>
                    
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" name="contact_number" id="contactNumber" class="form-control" 
                               data-tooltip="Enter contact number" placeholder="e.g., 09123456789">
                    </div>
                    
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" id="address" class="form-control" rows="3" 
                                  data-tooltip="Enter complete address"></textarea>
                    </div>
                    
                    <div id="passwordInfo" class="password-info" style="display: none;">
                        <strong>Default Password:</strong> <code>default123</code>
                        <p style="margin: 5px 0 0; font-size: 0.85rem;">User will be prompted to change password on first login.</p>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-success" id="submitBtn">Add Resident</button>
                </div>
            </form>
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

    // Modal functions
    function openAddModal() {
        document.getElementById('modalTitle').textContent = 'Add New Resident';
        document.getElementById('formAction').value = 'add';
        document.getElementById('residentId').value = '';
        document.getElementById('fullName').value = '';
        document.getElementById('username').value = '';
        document.getElementById('email').value = '';
        document.getElementById('contactNumber').value = '';
        document.getElementById('address').value = '';
        document.getElementById('passwordInfo').style.display = 'block';
        document.getElementById('submitBtn').textContent = 'Add Resident';
        document.getElementById('residentModal').classList.add('active');
    }
    
    function editResident(resident) {
        document.getElementById('modalTitle').textContent = 'Edit Resident';
        document.getElementById('formAction').value = 'edit';
        document.getElementById('residentId').value = resident.id;
        document.getElementById('fullName').value = resident.full_name;
        document.getElementById('username').value = resident.username;
        document.getElementById('email').value = resident.email;
        document.getElementById('contactNumber').value = resident.contact_number || '';
        document.getElementById('address').value = resident.address || '';
        document.getElementById('passwordInfo').style.display = 'none';
        document.getElementById('submitBtn').textContent = 'Update Resident';
        document.getElementById('residentModal').classList.add('active');
    }
    
    function closeModal() {
        document.getElementById('residentModal').classList.remove('active');
    }
    
    // View resident details
    function viewResident(id) {
        // Hide any open status forms first
        const allStatusRows = document.querySelectorAll('[id^="status-"]');
        allStatusRows.forEach(row => row.classList.add('hidden'));
        
        // Toggle the details row
        const detailsRow = document.getElementById('details-' + id);
        if (detailsRow) {
            detailsRow.classList.toggle('hidden');
        }
    }
    
    // Status form
    function showStatusForm(id) {
        // Hide any open details rows first
        const allDetailsRows = document.querySelectorAll('[id^="details-"]');
        allDetailsRows.forEach(row => row.classList.add('hidden'));
        
        // Toggle the status form row
        const statusRow = document.getElementById('status-' + id);
        if (statusRow) {
            statusRow.classList.toggle('hidden');
        }
    }
    
    function toggleStatusForm(id) {
        const statusRow = document.getElementById('status-' + id);
        if (statusRow) {
            statusRow.classList.add('hidden');
        }
    }
    
    // Password reset
    function resetPassword(id) {
        if (confirm('Are you sure you want to reset this resident\'s password to default123?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '';
            
            const resetInput = document.createElement('input');
            resetInput.type = 'hidden';
            resetInput.name = 'reset_password';
            resetInput.value = '1';
            
            const idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'resident_id';
            idInput.value = id;
            
            form.appendChild(resetInput);
            form.appendChild(idInput);
            document.body.appendChild(form);
            form.submit();
        }
    }
    
    // Delete confirmation
    function confirmDelete(id, name) {
        if (confirm(`Are you sure you want to delete resident "${name}"? This action cannot be undone.`)) {
            window.location.href = '?delete=' + id;
        }
    }
    
    // Form validation
    function validateForm() {
        const fullName = document.getElementById('fullName').value.trim();
        const username = document.getElementById('username').value.trim();
        const email = document.getElementById('email').value.trim();
        
        if (!fullName || !username || !email) {
            if (window.BarangayLink && window.BarangayLink.showNotification) {
                window.BarangayLink.showNotification('Please fill in all required fields', 'error');
            } else {
                alert('Please fill in all required fields');
            }
            return false;
        }
        
        // Email validation
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) {
            if (window.BarangayLink && window.BarangayLink.showNotification) {
                window.BarangayLink.showNotification('Please enter a valid email address', 'error');
            } else {
                alert('Please enter a valid email address');
            }
            return false;
        }
        
        // Username validation (alphanumeric + underscore)
        const usernameRegex = /^[a-zA-Z0-9_]+$/;
        if (!usernameRegex.test(username)) {
            if (window.BarangayLink && window.BarangayLink.showNotification) {
                window.BarangayLink.showNotification('Username can only contain letters, numbers, and underscores', 'error');
            } else {
                alert('Username can only contain letters, numbers, and underscores');
            }
            return false;
        }
        
        return true;
    }
    
    function validateSearch() {
        const searchInput = document.getElementById('searchInput');
        const searchTerm = searchInput.value.trim();
        
        if (searchTerm.length > 0 && searchTerm.length < 2) {
            if (window.BarangayLink && window.BarangayLink.showNotification) {
                window.BarangayLink.showNotification('Please enter at least 2 characters to search', 'warning');
            } else {
                alert('Please enter at least 2 characters to search');
            }
            return false;
        }
        
        return true;
    }
    
    // Live search
    let searchTimeout;
    document.getElementById('searchInput')?.addEventListener('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            if (this.value.length >= 2 || this.value.length === 0) {
                document.getElementById('searchForm').submit();
            }
        }, 500);
    });
    
    // Highlight search terms
    document.addEventListener('DOMContentLoaded', function() {
        const searchTerm = new URLSearchParams(window.location.search).get('search');
        if (searchTerm && searchTerm.length > 0) {
            highlightSearchTerm(searchTerm);
        }
    });
    
    function highlightSearchTerm(term) {
        const cells = document.querySelectorAll('#residentsTable td:not(:first-child):not(:last-child)');
        const regex = new RegExp(`(${term})`, 'gi');
        
        cells.forEach(cell => {
            if (cell.textContent.match(regex)) {
                cell.innerHTML = cell.textContent.replace(regex, '<mark style="background: #fff3cd; padding: 2px; border-radius: 3px;">$1</mark>');
            }
        });
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('residentModal');
        if (event.target == modal) {
            closeModal();
        }
    }
    </script>
    
    <script src="../../src/js/main.js"></script>
    <script src="../../src/js/mobile-menu.js"></script>
</body>
</html>