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

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $full_name = $db->escape($_POST['full_name']);
        $address = $db->escape($_POST['address']);
        $contact_number = $db->escape($_POST['contact_number']);
        
        $sql = "UPDATE users SET full_name='$full_name', address='$address', contact_number='$contact_number' WHERE id={$user['id']}";
        
        if ($db->query($sql)) {
            $_SESSION['full_name'] = $full_name;
            $message = "Profile updated successfully!";
            $user = $auth->getCurrentUser(); // Refresh user data
            echo "<script>
document.addEventListener('DOMContentLoaded', function(){
    if(window.BarangayLink){
        window.BarangayLink.showNotification('Profile updated successfully!', 'success');
    }
});
</script>";
        } else {
            $error = "Failed to update profile.";
            echo "<script>
document.addEventListener('DOMContentLoaded', function(){
    if(window.BarangayLink){
        BarangayLink.showNotification('Failed to update profile', 'error');
    }
});
</script>";
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        
        $check = $db->query("SELECT password FROM users WHERE id={$user['id']}")->fetch_assoc();
        
        if (password_verify($current, $check['password'])) {
            if ($new == $confirm) {
                if (strlen($new) >= 8) {
                    $hashed = password_hash($new, PASSWORD_DEFAULT);
                    $db->query("UPDATE users SET password='$hashed' WHERE id={$user['id']}");
                    $message = "Password changed successfully!";
                    echo "<script>
document.addEventListener('DOMContentLoaded', function(){
    if(window.BarangayLink){
        BarangayLink.showNotification('Password changed successfully!', 'success');
    }
});
</script>";
                } else {
                    $error = "Password must be at least 8 characters long.";
                    echo "<script>
document.addEventListener('DOMContentLoaded', function(){
    if(window.BarangayLink){
        BarangayLink.showNotification('Password must be at least 8 characters long', 'error');
    }
});
</script>";
                }
            } else {
                $error = "New passwords do not match.";
                echo "<script>
document.addEventListener('DOMContentLoaded', function(){
    if(window.BarangayLink){
        BarangayLink.showNotification('New passwords do not match', 'error');
    }
});
</script>";
            }
        } else {
            $error = "Current password is incorrect.";
            echo "<script>
document.addEventListener('DOMContentLoaded', function(){
    if(window.BarangayLink){
        BarangayLink.showNotification('Current password is incorrect', 'error');
    }
});
</script>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile - BarangayLink</title>
    <link rel="stylesheet" href="../../src/css/main.css?v=<?php echo time(); ?>">
    <style>
        .profile-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            text-align: center;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: var(--primary-color);
            border: 4px solid rgba(255,255,255,0.3);
        }
        
        .admin-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            margin-top: 10px;
        }
        
        .password-requirements {
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
            padding: 10px;
            background: var(--light-bg);
            border-radius: 4px;
        }
        
        .password-requirements ul {
            margin: 5px 0 0 20px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 20px;
        }
        
        .permission-list {
            list-style: none;
            padding: 0;
            margin: 10px 0 0 0;
        }
        
        .permission-list li {
            padding: 5px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .permission-list li:last-child {
            border-bottom: none;
        }
        
        .permission-list li:before {
            content: "✓ ";
            color: var(--success-color);
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .profile-header {
                padding: 20px;
            }
            
            .profile-avatar {
                width: 80px;
                height: 80px;
                font-size: 2.5rem;
            }
        }
        .password-field{
    position:relative;
}

.toggle-password{
    position:absolute;
    right:10px;
    top:50%;
    transform:translateY(-50%);
    cursor:pointer;
    font-size:16px;
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
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-avatar">
                    <?php 
                    $initial = strtoupper(substr($user['full_name'], 0, 1));
                    echo $initial;
                    ?>
                </div>
                <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
                <div class="admin-badge">
                    👑 Administrator
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <span>👤 Personal Information</span>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" id="profileForm">
                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                    <small class="text-muted">Username cannot be changed</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>Email Address</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                    <small class="text-muted">Email cannot be changed</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>Full Name</label>
                                    <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required data-tooltip="Enter your full name">
                                </div>
                                
                                <div class="form-group">
                                    <label>Address</label>
                                    <textarea name="address" class="form-control" rows="3" data-tooltip="Your current address"><?php echo htmlspecialchars($user['address']); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label>Contact Number</label>
                                    <input type="text" name="contact_number" class="form-control" value="<?php echo htmlspecialchars($user['contact_number']); ?>" data-tooltip="Your contact number" placeholder="e.g., 09123456789">
                                </div>
                                
                                <button type="submit" name="update_profile" class="btn btn-primary" data-tooltip="Save changes">
                                    💾 Update Profile
                                </button>
                                <button type="reset" class="btn btn-secondary" data-tooltip="Reset form">
                                    🔄 Reset
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            <span>🔒 Change Password</span>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" id="passwordForm">
                                <div class="form-group">
                                    <label>Current Password</label>
                                    <div class="password-field">
    <input type="password" name="current_password" id="current_password" class="form-control" required>
    <span class="toggle-password" onclick="togglePasswordVisibility('current_password', this)">👁️</span>
</div>
                                </div>
                                
                                <div class="form-group">
                                    <label>New Password</label>
                                    <div class="password-field">
    <input type="password" name="new_password" id="new_password" class="form-control" required>
    <span class="toggle-password" onclick="togglePasswordVisibility('new_password', this)">👁️</span>
</div>

<div id="password-strength" class="password-strength"></div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Confirm New Password</label>
                                    <div class="password-field">
    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
    <span class="toggle-password" onclick="togglePasswordVisibility('confirm_password', this)">👁️</span>
</div>
                                </div>
                                
                                <div class="password-requirements">
                                    <strong>Password Requirements:</strong>
                                    <ul>
                                        <li>At least 8 characters long</li>
                                        <li>Include at least one uppercase letter</li>
                                        <li>Include at least one number</li>
                                        <li>Include at least one special character</li>
                                    </ul>
                                </div>
                                
                                <button type="submit" name="change_password" class="btn btn-warning" data-tooltip="Change your password">
                                    🔑 Change Password
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card mt-20">
                        <div class="card-header">
                            <span>📊 Account Information</span>
                        </div>
                        <div class="card-body">
                            <div class="info-grid">
                                <div>
                                    <strong>Member Since:</strong>
                                    <p><?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
                                </div>
                                <div>
                                    <strong>Account Type:</strong>
                                    <p><span class="badge bg-primary">Administrator</span></p>
                                </div>
                                <div>
                                    <strong>Status:</strong>
                                    <p><?php echo getStatusBadge($user['status']); ?></p>
                                </div>
                                <div>
                                    <strong>Last Updated:</strong>
                                    <p><?php echo isset($user['updated_at']) ? date('F d, Y', strtotime($user['updated_at'])) : 'N/A'; ?></p>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div>
                                <strong>Account Permissions:</strong>
                                <ul class="permission-list">
                                    <li>Full system access</li>
                                    <li>Manage announcements</li>
                                    <li>Manage service requests</li>
                                    <li>Manage residents</li>
                                    <li>Generate reports</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../../src/js/main.js"></script>
    <script src="../../src/js/mobile-menu.js"></script>
    <script>
    // Real-time password matching
    document.getElementById('confirm_password')?.addEventListener('keyup', function() {
        const newPass = document.getElementById('new_password').value;
        const confirmPass = this.value;
        
        if (newPass !== confirmPass) {
            this.classList.add('error');
            this.setCustomValidity('Passwords do not match');
        } else {
            this.classList.remove('error');
            this.setCustomValidity('');
        }
    });
    
    document.getElementById('new_password')?.addEventListener('keyup', function() {
        const confirmField = document.getElementById('confirm_password');
        if (confirmField.value) {
            if (this.value !== confirmField.value) {
                confirmField.classList.add('error');
                confirmField.setCustomValidity('Passwords do not match');
            } else {
                confirmField.classList.remove('error');
                confirmField.setCustomValidity('');
            }
        }
    });
    
    // Password strength indicator
    const passwordInput = document.getElementById('new_password');
const strengthIndicator = document.getElementById('password-strength');

passwordInput?.addEventListener('input', function(){

    const password = this.value;
    let strength = 0;

    if(password.length >= 8) strength++;
    if(/[a-z]/.test(password)) strength++;
    if(/[A-Z]/.test(password)) strength++;
    if(/[0-9]/.test(password)) strength++;
    if(/[$@#&!]/.test(password)) strength++;

    let text = '';
    let className = '';

    switch(strength){
        case 0:
        case 1:
            text = 'Very Weak';
            className = 'very-weak';
        break;

        case 2:
            text = 'Weak';
            className = 'weak';
        break;

        case 3:
            text = 'Fair';
            className = 'fair';
        break;

        case 4:
            text = 'Good';
            className = 'good';
        break;

        case 5:
            text = 'Strong';
            className = 'strong';
        break;
    }

    strengthIndicator.className = 'password-strength ' + className;
    strengthIndicator.textContent = password ? 'Password Strength: ' + text : '';

});
    
    // Form validation
    document.getElementById('profileForm')?.addEventListener('submit', function(e) {
        const fullName = this.querySelector('input[name="full_name"]').value.trim();
        
        if (!fullName) {
            e.preventDefault();
            window.BarangayLink.showNotification('Please fill in all required fields', 'error');
        }
    });
    
    document.getElementById('passwordForm')?.addEventListener('submit', function(e) {

    const current = this.querySelector('input[name="current_password"]').value;
    const newPass = this.querySelector('input[name="new_password"]').value;
    const confirmPass = this.querySelector('input[name="confirm_password"]').value;

    if (!current || !newPass || !confirmPass) {
        e.preventDefault();
        window.BarangayLink.showNotification('Please fill in all password fields', 'error');
        return;
    }

    if (newPass !== confirmPass) {
        e.preventDefault();
        window.BarangayLink.showNotification('New passwords do not match', 'error');
        return;
    }

    if (newPass.length < 8) {
        e.preventDefault();
        window.BarangayLink.showNotification('Password must be at least 8 characters long', 'error');
        return;
    }

    // NEW CONFIRMATION
    const confirmChange = confirm("Are you sure you want to change your password?");

    if (!confirmChange) {
        e.preventDefault();
        return;
    }

    });
    
    // Auto-hide alerts
    setTimeout(function() {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
    function togglePasswordVisibility(fieldId, icon) {

    const passwordInput = document.getElementById(fieldId);

    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.textContent = '🔒';
    } else {
        passwordInput.type = 'password';
        icon.textContent = '👁️';
    }

}
    </script>
    
    
</body>
</html>