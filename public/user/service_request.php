<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect('../index.php');
}

$db = new Database();
$user = $auth->getCurrentUser();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $request_type = $db->escape($_POST['request_type']);
    $description = $db->escape($_POST['description']);
    
    $sql = "INSERT INTO service_requests (user_id, request_type, description) 
            VALUES ({$user['id']}, '$request_type', '$description')";
    
    if ($db->query($sql)) {
        $message = "Service request submitted successfully!";
    } else {
        $error = "Failed to submit request. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Service - BarangayLink</title>
    <link rel="stylesheet" href="../../src/css/main.css?v=<?php echo time(); ?>">
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
        <div class="container">
            <div class="card" style="max-width: 600px; margin: 0 auto;">
                <div class="card-header">
                    <h2>📝 Request Barangay Service</h2>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST" action="" id="requestForm">
                        <div class="form-group">
                            <label>Request Type</label>
                            <select name="request_type" class="form-control" required data-tooltip="Select the type of service you need">
                                <option value="">Select Request Type</option>
                                <option value="Barangay Clearance">📄 Barangay Clearance</option>
                                <option value="Certificate of Residency">🏠 Certificate of Residency</option>
                                <option value="Business Permit">💼 Business Permit</option>
                                <option value="Complaint">⚠️ File a Complaint</option>
                                <option value="Assistance">🆘 Request Assistance</option>
                                <option value="Other">❓ Other</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="5" 
                                      placeholder="Please provide details about your request..." 
                                      required data-tooltip="Describe your request in detail"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-success" data-tooltip="Submit your request">✅ Submit Request</button>
                            <a href="dashboard.php" class="btn btn-primary" data-tooltip="Cancel and go back">↩️ Cancel</a>
                            <button type="reset" class="btn btn-secondary" data-tooltip="Clear form">🔄 Reset</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    

    
    <script src="../../src/js/main.js"></script>
    <script src="../../src/js/mobile-menu.js"></script>
</body>
</html>