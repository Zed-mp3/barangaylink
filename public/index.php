<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

$auth = new Auth();
$error = '';

// If already logged in, redirect to dashboard
if ($auth->isLoggedIn()) {
    if ($auth->isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('user/dashboard.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? true : false;
    
    if ($auth->login($username, $password, $remember)) {
        if ($auth->isAdmin()) {
            redirect('admin/dashboard.php');
        } else {
            redirect('user/dashboard.php');
        }
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BarangayLink - Login</title>
    <link rel="stylesheet" href="../src/css/main.css">
    <style>
        
        
        .auth-box {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            animation: slideUp 0.5s ease;
        }
        
        .logo-container {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .logo-container img {
            max-width: 120px;
            height: auto;
            margin-bottom: 15px;
            animation: fadeIn 0.5s ease;
        }
        
        .auth-box h2 {
            color: var(--primary-color);
            margin-bottom: 10px;
            font-size: 1.8rem;
            text-align: center;
        }
        
        .auth-box p {
            text-align: center;
            margin-bottom: 30px;
            color: #666;
            font-size: 0.95rem;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .remember-me {
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .remember-me label {
            color: #666;
            cursor: pointer;
            font-size: 0.95rem;
        }
        
        .btn-primary {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-primary:active {
            transform: translateY(0);
        }
        
        .btn-primary.loading {
            opacity: 0.8;
            cursor: not-allowed;
        }
        
        .btn-primary.loading::after {
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
        
        .auth-links {
            text-align: center;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .auth-links p {
            margin: 5px 0;
            color: #666;
        }
        
        .btn-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        
        .btn-link:hover {
            color: #5a67d8;
            text-decoration: underline;
        }
        
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 0.95rem;
            animation: shake 0.5s ease;
        }
        
        .alert-danger {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Mobile responsive */
        @media (max-width: 480px) {
            .auth-box {
                padding: 30px 20px;
            }
            
            .logo-container img {
                max-width: 100px;
            }
            
            .auth-box h2 {
                font-size: 1.5rem;
            }
        }
        
        /* Password visibility toggle */
        .password-wrapper {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #999;
            transition: color 0.3s ease;
        }
        
        .toggle-password:hover {
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="logo-container">
                <img src="../assets/1772429077726-removebg-preview.png" alt="BarangayLink Logo" onerror="this.style.display='none'">
            </div>
            <h2>Welcome to BarangayLink</h2>
            <p>Please login to continue</p>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm" onsubmit="return validateLoginForm()">
                <div class="form-group">
                    <label>Username or Email</label>
                    <input type="text" name="username" id="username" class="form-control" required 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                           data-tooltip="Enter your username or email"
                           placeholder="Enter your username or email">
                </div>
                
                <div class="form-group">
    <label>Password</label>
    <div class="password-wrapper">
        <input type="password" name="password" id="password" class="form-control" required 
               data-tooltip="Enter your password"
               placeholder="Enter your password"
               autocomplete="current-password">
        <span class="toggle-password" onclick="togglePasswordVisibility()" role="button" aria-label="Toggle password visibility">👁️</span>
    </div>
    <!-- Password strength indicator will be inserted here by JavaScript -->
</div>
                
                <div class="remember-me">
                    <input type="checkbox" name="remember" id="remember" <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>>
                    <label for="remember">Remember me for 30 days</label>
                </div>
                
                <button type="submit" class="btn-primary" id="loginBtn">Login</button>
            </form>
            
            <div class="auth-links">
                <p>Don't have an account? <a href="register.php" class="btn-link">Register here</a></p>
            
            </div>
        </div>
    </div>
    
    <script>
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
    
    function validateLoginForm() {
        const username = document.getElementById('username').value.trim();
        const password = document.getElementById('password').value.trim();
        const loginBtn = document.getElementById('loginBtn');
        
        if (!username || !password) {
            if (window.BarangayLink && window.BarangayLink.showNotification) {
                window.BarangayLink.showNotification('Please enter both username and password', 'error');
            } else {
                alert('Please enter both username and password');
            }
            return false;
        }
        
        // Show loading state
        loginBtn.classList.add('loading');
        loginBtn.textContent = 'Logging in...';
        
        return true;
    }
    
    // Remove loading state if form submission fails (page reload will happen anyway, but just in case)
    window.addEventListener('pageshow', function() {
        const loginBtn = document.getElementById('loginBtn');
        if (loginBtn) {
            loginBtn.classList.remove('loading');
            loginBtn.textContent = 'Login';
        }
    });
    
    // Auto-focus username field
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('username').focus();
    });
    </script>
    
    <script type="module" src="../src/js/main.js"></script>
</body>
</html>