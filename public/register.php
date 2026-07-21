<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

$auth = new Auth();
$message = '';
$error = '';

// Secret codes for different roles
$ROLE_CODES = [
    'admin' => 'ADMIN2026',
    'official' => 'OFFICIAL2026',
    'staff' => 'STAFF2026'
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_type = $_POST['user_type'] ?? 'resident';
    $secret_code = $_POST['secret_code'] ?? '';
    
    // Validate secret code for admin/official registration
    if ($user_type != 'resident') {
        if (!isset($ROLE_CODES[$user_type]) || $secret_code != $ROLE_CODES[$user_type]) {
            $error = "Invalid secret code for " . ucfirst($user_type) . " registration!";
        }
    }
    
    if (empty($error)) {
        $data = [
            'username' => $_POST['username'] ?? '',
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
            'full_name' => $_POST['full_name'] ?? '',
            'address' => $_POST['address'] ?? '',
            'contact_number' => $_POST['contact_number'] ?? '',
            'user_type' => $user_type
        ];
        
        $result = $auth->register($data);
        
        if ($result === true) {
            $message = "Registration successful! You can now login.";
            echo "<script>setTimeout(function() { window.location.href = 'index.php'; }, 2000);</script>";
        } else {
            $error = $result;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - BarangayLink</title>
    <link rel="stylesheet" href="../src/css/main.css">
    <style>
        /* Additional styles specific to registration */
        .role-selector {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .role-card {
            background: var(--white);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 12px 5px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .role-card:hover {
            border-color: var(--primary-color);
            box-shadow: var(--shadow);
        }
        
        .role-card.active {
            border-color: var(--primary-color);
            background-color: rgba(52, 152, 219, 0.05);
        }
        
        .role-icon {
            font-size: 1.8rem;
            margin-bottom: 5px;
        }
        
        .role-name {
            font-weight: 500;
            font-size: 0.9rem;
            margin-bottom: 3px;
        }
        
        .role-badge {
            font-size: 0.7rem;
            padding: 2px 6px;
            border-radius: 12px;
            background: #f0f0f0;
            display: inline-block;
        }
        
        .secret-code-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 3px solid var(--primary-color);
            display: none;
        }
        
        .secret-code-section.show {
            display: block;
        }
        
        .secret-code-section label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--primary-color);
        }
        
        .secret-code-section input {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 0.95rem;
        }
        
        .secret-code-section input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.1);
        }
        
        .code-hint {
            font-size: 0.8rem;
            color: #666;
            margin-top: 5px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            z-index: 1;
        }
        
        .input-wrapper input {
            padding-left: 38px;
            width: 100%;
        }
        
        /* Password wrapper specific styles */
        .password-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
        }
        
        .password-wrapper input {
            width: 100%;
            padding-right: 45px !important;
            padding-left: 38px !important;
        }
        
        .password-wrapper .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            z-index: 2;
        }
        
        .password-wrapper .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            transition: color 0.3s ease;
            z-index: 2;
            background: transparent;
            line-height: 1;
            font-size: 1.2rem;
            pointer-events: auto;
            user-select: none;
            -webkit-tap-highlight-color: transparent;
        }
        
        .password-wrapper .toggle-password:hover {
            color: var(--primary-color);
        }
        
        /* Password strength indicator */
        .password-strength {
            margin-top: 10px;
            margin-bottom: 5px;
            padding: 10px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            animation: slideDown 0.2s ease;
            width: 100%;
            display: none;
        }
        
        .password-strength.very-weak {
            background: #ffebee;
            color: #c62828;
            border-left: 3px solid #c62828;
        }
        
        .password-strength.weak {
            background: #fff3e0;
            color: #ef6c00;
            border-left: 3px solid #ef6c00;
        }
        
        .password-strength.fair {
            background: #fff8e1;
            color: #f9a825;
            border-left: 3px solid #f9a825;
        }
        
        .password-strength.good {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 3px solid #2e7d32;
        }
        
        .password-strength.strong {
            background: #e8f5e9;
            color: #1b5e20;
            border-left: 3px solid #1b5e20;
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
        
        .terms {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 15px 0;
        }
        
        .terms input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
        
        .terms label {
            color: #666;
            font-size: 0.9rem;
            cursor: pointer;
        }
        
        .terms a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .terms a:hover {
            text-decoration: underline;
        }
        
        .btn-register {
            width: 100%;
            padding: 12px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .btn-register:hover {
            background: #2980b9;
        }
        
        .btn-register.loading {
            opacity: 0.7;
            cursor: not-allowed;
            position: relative;
        }
        
        .btn-register.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            .role-selector {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }
        
        @media (max-width: 480px) {
            .auth-box {
                padding: 20px;
            }
            
            .password-strength {
                font-size: 0.75rem;
                padding: 8px 10px;
            }
            
            .password-wrapper .toggle-password {
                right: 10px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div style="text-align: center; margin-bottom: 25px;">
                <img src="../assets/1772429077726-removebg-preview.png" alt="BarangayLink Logo" style="max-width: 80px; margin-bottom: 10px;" onerror="this.style.display='none'">
                <h2>Create Account</h2>
                <p style="color: #666; font-size: 0.9rem;">Join BarangayLink community</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="registerForm" onsubmit="return validateForm()">
                <!-- Role Selection -->
                <div class="role-selector">
                    <div class="role-card" data-role="resident" onclick="selectRole('resident')">
                        <div class="role-icon">👤</div>
                        <div class="role-name">Resident</div>
                        <span class="role-badge">Community</span>
                    </div>
                    <div class="role-card" data-role="official" onclick="selectRole('official')">
                        <div class="role-icon">👔</div>
                        <div class="role-name">Official</div>
                        <span class="role-badge">Barangay</span>
                    </div>
                    <div class="role-card" data-role="admin" onclick="selectRole('admin')">
                        <div class="role-icon">⚙️</div>
                        <div class="role-name">Admin</div>
                        <span class="role-badge">System</span>
                    </div>
                </div>
                
                <input type="hidden" name="user_type" id="user_type" value="resident">
                
                <!-- Secret Code Section -->
                <div class="secret-code-section" id="secretCodeSection">
                    <label>Secret Registration Code</label>
                    <input type="password" name="secret_code" id="secret_code" 
                           placeholder="Enter secret code">
                    <div class="code-hint">Contact Barangay Administrator for the code</div>
                </div>
                
                <!-- Registration Form -->
                <div class="form-row">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <div class="input-wrapper">
                            <span class="input-icon">👤</span>
                            <input type="text" name="full_name" id="full_name" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Username *</label>
                        <div class="input-wrapper">
                            <span class="input-icon">@</span>
                            <input type="text" name="username" id="username" class="form-control" required>
                        </div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Email *</label>
                        <div class="input-wrapper">
                            <span class="input-icon">📧</span>
                            <input type="email" name="email" id="email" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Contact Number</label>
                        <div class="input-wrapper">
                            <span class="input-icon">📱</span>
                            <input type="text" name="contact_number" id="contact_number" class="form-control" 
                                   placeholder="09123456789">
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Password *</label>
                    <div class="password-wrapper">
                        <span class="input-icon">🔒</span>
                        <input type="password" name="password" id="password" class="form-control" required>
                        <span class="toggle-password" onclick="togglePasswordVisibility()" role="button" aria-label="Toggle password visibility">👁️</span>
                    </div>
                    <!-- Password strength indicator will be added here by main.js -->
                </div>
                
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" id="address" class="form-control" rows="3" 
                              placeholder="123 Rizal St., Barangay San Jose"></textarea>
                </div>
                
                <div class="terms">
                    <input type="checkbox" name="terms" id="terms" required>
                    <label for="terms">I agree to the <a href="#" onclick="alert('Terms of Service')">Terms</a> and <a href="#" onclick="alert('Privacy Policy')">Privacy Policy</a></label>
                </div>
                
                <button type="submit" class="btn-register" id="registerBtn">
                    Create Account
                </button>
            </form>
            
            <div class="auth-links">
                <p>Already have an account? <a href="index.php" class="btn-link">Sign In</a></p>
                <p><a href="index.php" class="btn-link">← Back to Home</a></p>
            </div>
        </div>
    </div>
    
    <script>
        // Password visibility toggle
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.toggle-password');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.textContent = '🔒';
            } else {
                passwordInput.type = 'password';
                toggleIcon.textContent = '👁️';
            }
        }
        
        // Role selection
        function selectRole(role) {
            document.querySelectorAll('.role-card').forEach(card => {
                card.classList.toggle('active', card.dataset.role === role);
            });
            
            document.getElementById('user_type').value = role;
            
            const secretSection = document.getElementById('secretCodeSection');
            const secretInput = document.getElementById('secret_code');
            
            if (role === 'resident') {
                secretSection.classList.remove('show');
                secretInput.removeAttribute('required');
            } else {
                secretSection.classList.add('show');
                secretInput.setAttribute('required', 'required');
            }
        }
        
        // Form validation
        function validateForm() {
            const fullName = document.getElementById('full_name').value.trim();
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const terms = document.getElementById('terms').checked;
            const userType = document.getElementById('user_type').value;
            
            if (!fullName || !username || !email || !password) {
                if (window.BarangayLink && window.BarangayLink.showNotification) {
                    window.BarangayLink.showNotification('Please fill in all required fields', 'error');
                } else {
                    alert('Please fill in all required fields');
                }
                return false;
            }
            
            if (!terms) {
                if (window.BarangayLink && window.BarangayLink.showNotification) {
                    window.BarangayLink.showNotification('You must agree to the Terms of Service', 'error');
                } else {
                    alert('You must agree to the Terms of Service');
                }
                return false;
            }
            
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                if (window.BarangayLink && window.BarangayLink.showNotification) {
                    window.BarangayLink.showNotification('Please enter a valid email address', 'error');
                } else {
                    alert('Please enter a valid email address');
                }
                return false;
            }
            
            const usernameRegex = /^[a-zA-Z0-9_]+$/;
            if (!usernameRegex.test(username)) {
                if (window.BarangayLink && window.BarangayLink.showNotification) {
                    window.BarangayLink.showNotification('Username can only contain letters, numbers, and underscores', 'error');
                } else {
                    alert('Username can only contain letters, numbers, and underscores');
                }
                return false;
            }
            
            if (password.length < 8) {
                if (window.BarangayLink && window.BarangayLink.showNotification) {
                    window.BarangayLink.showNotification('Password must be at least 8 characters long', 'error');
                } else {
                    alert('Password must be at least 8 characters long');
                }
                return false;
            }
            
            // Check secret code for non-resident roles
            if (userType !== 'resident') {
                const secretCode = document.getElementById('secret_code').value.trim();
                if (!secretCode) {
                    if (window.BarangayLink && window.BarangayLink.showNotification) {
                        window.BarangayLink.showNotification('Please enter the secret registration code', 'error');
                    } else {
                        alert('Please enter the secret registration code');
                    }
                    return false;
                }
            }
            
            // Show loading state
            const registerBtn = document.getElementById('registerBtn');
            registerBtn.classList.add('loading');
            registerBtn.textContent = 'Creating Account...';
            
            return true;
        }
        
        // Set resident as default
        document.addEventListener('DOMContentLoaded', function() {
            selectRole('resident');
            
            // Focus on full name field
            document.getElementById('full_name').focus();
        });
        
        // Remove loading state if page is shown again (browser back button)
        window.addEventListener('pageshow', function() {
            const registerBtn = document.getElementById('registerBtn');
            if (registerBtn) {
                registerBtn.classList.remove('loading');
                registerBtn.textContent = 'Create Account';
            }
        });
    </script>
    
    <script src="../src/js/main.js"></script>
</body>
</html>