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
            $user = $auth->getCurrentUser();
        } else {
            $error = "Failed to update profile.";
        }
    }
    
    if (isset($_POST['change_password'])) {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        
        $check = $db->query("SELECT password FROM users WHERE id={$user['id']}")->fetch_assoc();
        
        if (password_verify($current, $check['password'])) {
            if ($new == $confirm) {
                $hashed = password_hash($new, PASSWORD_DEFAULT);
                $db->query("UPDATE users SET password='$hashed' WHERE id={$user['id']}");
                $message = "Password changed successfully!";
            } else {
                $error = "New passwords do not match.";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - BarangayLink</title>
    <link rel="stylesheet" href="../../src/css/main.css?v=<?php echo time(); ?>">
</head>
<style>

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

.password-strength{
    font-size:12px;
    margin-top:5px;
}

.password-strength.very-weak{color:red;}
.password-strength.weak{color:#ff6600;}
.password-strength.fair{color:#ffaa00;}
.password-strength.good{color:#2ecc71;}
.password-strength.strong{color:#27ae60;}

</style>
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
        <div class="container">
            <h1>My Profile</h1>
            
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
                            Personal Information
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" id="profileForm">
                                <div class="form-group">
                                    <label>Username</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                    <small>Username cannot be changed</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                    <small>Email cannot be changed</small>
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
                                    <input type="text" name="contact_number" class="form-control" value="<?php echo htmlspecialchars($user['contact_number']); ?>" data-tooltip="Your contact number">
                                </div>
                                
                                <button type="submit" name="update_profile" class="btn btn-primary" data-tooltip="Save changes">Update Profile</button>
                                <button type="reset" class="btn btn-secondary" data-tooltip="Reset form">Reset</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col">
                    <div class="card">
                        <div class="card-header">
                            Change Password
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
                                
                                <button type="submit" name="change_password" class="btn btn-warning" data-tooltip="Change your password">Change Password</button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card mt-20">
                        <div class="card-header">
                            Account Information
                        </div>
                        <div class="card-body">
                            <p><strong>Member Since:</strong> <?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
                            <p><strong>Account Type:</strong> Resident</p>
                            <p><strong>Status:</strong> <?php echo getStatusBadge($user['status']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        <p>&copy; 2026 BarangayLink. All rights reserved.</p>
    </footer>
    
    <script src="../../src/js/main.js"></script>
<script src="../../src/js/mobile-menu.js"></script>

<script>

// Toggle password visibility
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


// Real-time password match
document.getElementById('confirm_password')?.addEventListener('keyup', function(){

    const newPass = document.getElementById('new_password').value;

    if(newPass !== this.value){
        this.classList.add('error');
        this.setCustomValidity('Passwords do not match');
    }else{
        this.classList.remove('error');
        this.setCustomValidity('');
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


// Profile form validation
document.getElementById('profileForm')?.addEventListener('submit', function(e){

    const fullName = this.querySelector('input[name="full_name"]').value.trim();

    if(!fullName){
        e.preventDefault();
        if(window.BarangayLink){
            BarangayLink.showNotification('Please fill in all required fields','error');
        }
    }

});


// Password change validation
document.getElementById('passwordForm')?.addEventListener('submit', function(e){

    const current = this.querySelector('input[name="current_password"]').value;
    const newPass = this.querySelector('input[name="new_password"]').value;
    const confirmPass = this.querySelector('input[name="confirm_password"]').value;

    if(!current || !newPass || !confirmPass){
        e.preventDefault();
        BarangayLink.showNotification('Please fill in all password fields','error');
        return;
    }

    if(newPass !== confirmPass){
        e.preventDefault();
        BarangayLink.showNotification('New passwords do not match','error');
        return;
    }

    if(newPass.length < 8){
        e.preventDefault();
        BarangayLink.showNotification('Password must be at least 8 characters long','error');
        return;
    }

    const confirmChange = confirm("Are you sure you want to change your password?");

    if(!confirmChange){
        e.preventDefault();
        return;
    }

});


// Auto hide alerts
setTimeout(function(){

    document.querySelectorAll('.alert').forEach(alert=>{
        alert.style.transition = 'opacity 0.5s';
        alert.style.opacity = '0';
        setTimeout(()=>alert.remove(),500);
    });

},5000);

</script>

</body>
</html>