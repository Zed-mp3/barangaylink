<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

$auth = new Auth();
$auth->requireAdmin();

if ($_SESSION['user_type'] !== 'admin') {
    header("Location: dashboard.php");
    exit();
}

$db = new Database();
$user = $auth->getCurrentUser();

// Current codes for Admin and Official only
$codes = [
    'admin' => 'ADMIN2026',
    'official' => 'OFFICIAL2026'
];

// Handle code refresh
if (isset($_POST['refresh_code'])) {
    $role = $_POST['role'];
    $new_code = strtoupper($role . rand(1000, 9999));
    $codes[$role] = $new_code;
    $message = "New code generated for " . ucfirst($role);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Codes - BarangayLink Admin</title>
    <link rel="stylesheet" href="../../src/css/main.css?v=<?php echo time(); ?>">
    <style>
        .code-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .code-card {
            background: var(--white);
            border-radius: 8px;
            padding: 25px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
        }
        
        .code-card:hover {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .code-card h3 {
            color: var(--primary-color);
            margin: 0 0 5px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1.2rem;
        }
        
        .code-card p {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .code-display {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 1.3rem;
            font-weight: bold;
            letter-spacing: 2px;
            margin: 15px 0;
            border: 2px dashed var(--primary-color);
            text-align: center;
            color: var(--primary-color);
        }
        
        .code-actions {
            display: flex;
            gap: 12px;
            margin-top: 5px;
        }

        .refresh-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 15px;
            border-radius: 8px;
            cursor: pointer;
            flex: 1;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }

        .refresh-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .refresh-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            background: linear-gradient(135deg, #5a67d8 0%, #6b46a0 100%);
        }

        .refresh-btn:hover::before {
            left: 100%;
        }

        .refresh-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(102, 126, 234, 0.3);
        }

        .refresh-btn .btn-icon {
            font-size: 1.1rem;
            animation: spin 2s linear infinite;
            animation-play-state: paused;
        }

        .refresh-btn:hover .btn-icon {
            animation-play-state: running;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .copy-btn {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            border: none;
            padding: 12px 15px;
            border-radius: 8px;
            cursor: pointer;
            flex: 2;
            font-size: 0.95rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 15px rgba(72, 187, 120, 0.3);
            position: relative;
            overflow: hidden;
        }

        .copy-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255,255,255,0.3);
            transform: translate(-50%, -50%);
            transition: width 0.5s, height 0.5s;
        }

        .copy-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(72, 187, 120, 0.4);
            background: linear-gradient(135deg, #3ca56b 0%, #2f8d56 100%);
        }

        .copy-btn:hover::before {
            width: 200px;
            height: 200px;
        }

        .copy-btn:active {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(72, 187, 120, 0.3);
        }

        /* Custom Modal Styles */
        .custom-confirm-modal {
            display: none;
            position: fixed;
            z-index: 10000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }

        .custom-confirm-modal.show {
            display: flex;
        }

        .custom-confirm-content {
            background: white;
            border-radius: 12px;
            padding: 25px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(-30px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .custom-confirm-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
        }

        .custom-confirm-icon {
            font-size: 2.5rem;
        }

        .custom-confirm-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #e67e22;
            margin: 0;
        }

        .custom-confirm-message {
            color: #34495e;
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 25px;
            padding-left: 52px;
        }

        .custom-confirm-warning {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .custom-confirm-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .custom-confirm-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .custom-confirm-btn.confirm {
            background: linear-gradient(135deg, #e67e22, #d35400);
            color: white;
            box-shadow: 0 4px 15px rgba(230, 126, 34, 0.3);
        }

        .custom-confirm-btn.confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(230, 126, 34, 0.4);
            background: linear-gradient(135deg, #d35400, #c0392b);
        }

        .custom-confirm-btn.cancel {
            background: #ecf0f1;
            color: #7f8c8d;
        }

        .custom-confirm-btn.cancel:hover {
            background: #e0e6e8;
            color: #5a6a7a;
            transform: translateY(-2px);
        }

        /* Pulse animation for new code */
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(72, 187, 120, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(72, 187, 120, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(72, 187, 120, 0);
            }
        }

        .code-display.new-code {
            animation: pulse 1.5s ease-out;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .code-actions {
                flex-direction: column;
            }
            
            .refresh-btn, .copy-btn {
                width: 100%;
                padding: 14px;
            }

            .custom-confirm-content {
                width: 95%;
                padding: 20px;
            }
        }
        
        .warning {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .warning-icon {
            font-size: 1.5rem;
        }
        
        .instruction-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
            border: 1px solid var(--border-color);
        }
        
        .instruction-card h3 {
            color: var(--primary-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .instruction-list {
            list-style: none;
            padding: 0;
        }
        
        .instruction-list li {
            padding: 10px 0;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .instruction-list li:last-child {
            border-bottom: none;
        }
        
        .instruction-list .step {
            background: var(--primary-color);
            color: white;
            width: 25px;
            height: 25px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: bold;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Custom Confirmation Modal -->
    <div id="confirmModal" class="custom-confirm-modal">
        <div class="custom-confirm-content">
            <div class="custom-confirm-header">
                <span class="custom-confirm-icon">⚠️</span>
                <h3 class="custom-confirm-title">Generate New Code?</h3>
            </div>
            <div class="custom-confirm-message" id="confirmMessage">
                Are you sure you want to generate a new code?
            </div>
            <div class="custom-confirm-warning">
                <span>⚠️</span>
                <span id="warningText">The old code will no longer work!</span>
            </div>
            <div class="custom-confirm-actions">
                <button class="custom-confirm-btn cancel" id="cancelConfirm">Cancel</button>
                <button class="custom-confirm-btn confirm" id="confirmAction">Yes, Generate New Code</button>
            </div>
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
            <h1>Registration Codes</h1>
            
            <?php if (isset($message)): ?>
                <div class="alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <div class="warning">
                <span class="warning-icon">⚠️</span>
                <div>
                    <strong>Important:</strong> These codes are for authorized personnel only. 
                    Share them securely with verified officials and administrators. 
                    Do not post these codes publicly.
                </div>
            </div>
            
            <div class="code-container">
                <?php foreach ($codes as $role => $code): ?>
                <div class="code-card">
                    <h3>
                        <?php 
                        $icons = ['admin' => '⚙️', 'official' => '👔'];
                        echo $icons[$role];
                        ?>
                        <?php echo ucfirst($role); ?> Code
                    </h3>
                    <p>For <?php echo ucfirst($role); ?> account registration</p>
                    <div class="code-display" id="<?php echo $role; ?>Code"><?php echo $code; ?></div>
                    <div class="code-actions">
                        <button class="copy-btn" onclick="copyCode('<?php echo $role; ?>Code')">
                            <span class="btn-icon">📋</span>
                            <span class="btn-text">Copy Code</span>
                        </button>
                        <button type="button" class="refresh-btn" onclick="showRefreshConfirmation('<?php echo $role; ?>')">
                            <span class="btn-icon">🔄</span>
                            <span class="btn-text">Generate New</span>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="instruction-card">
                <h3>
                    <span>📋</span>
                    How to Use Registration Codes
                </h3>
                <ul class="instruction-list">
                    <li>
                        <span class="step">1</span>
                        <strong>Verify Identity:</strong> Ensure the person requesting is authorized for the role
                    </li>
                    <li>
                        <span class="step">2</span>
                        <strong>Share Code:</strong> Provide the appropriate code securely (in-person, email, or SMS)
                    </li>
                    <li>
                        <span class="step">3</span>
                        <strong>Instruct User:</strong> Tell them to select the correct role and enter the code during registration
                    </li>
                    <li>
                        <span class="step">4</span>
                        <strong>Refresh Codes:</strong> Generate new codes if old ones are compromised
                    </li>
                </ul>
            </div>
        </div>
    </div>
    
    <script>
    // Variables to store pending refresh data
    let pendingRole = null;

    // Show custom confirmation modal
    function showRefreshConfirmation(role) {
        const modal = document.getElementById('confirmModal');
        const message = document.getElementById('confirmMessage');
        const warning = document.getElementById('warningText');
        
        // Customize message based on role
        message.textContent = `Are you sure you want to generate a new ${role} registration code?`;
        warning.textContent = 'The old code will no longer work!';
        
        // Store the role for later use
        pendingRole = role;
        
        // Show modal
        modal.classList.add('show');
        
        // Prevent body scrolling
        document.body.style.overflow = 'hidden';
    }

    // Hide custom confirmation modal
    function hideConfirmationModal() {
        const modal = document.getElementById('confirmModal');
        modal.classList.remove('show');
        document.body.style.overflow = '';
        pendingRole = null;
    }

    // Execute refresh after confirmation
    function executeRefresh() {
        if (!pendingRole) return;
        
        // Create and submit form
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const roleInput = document.createElement('input');
        roleInput.type = 'hidden';
        roleInput.name = 'role';
        roleInput.value = pendingRole;
        
        const refreshInput = document.createElement('input');
        refreshInput.type = 'hidden';
        refreshInput.name = 'refresh_code';
        refreshInput.value = '1';
        
        form.appendChild(roleInput);
        form.appendChild(refreshInput);
        document.body.appendChild(form);
        
        // Add pulse animation to the corresponding code display
        const codeDisplay = document.getElementById(pendingRole + 'Code');
        if (codeDisplay) {
            codeDisplay.classList.add('new-code');
        }
        
        // Use BarangayLink notification system if available
        if (window.BarangayLink && window.BarangayLink.showNotification) {
            window.BarangayLink.showNotification('Generating new code...', 'info');
        }
        
        // Submit form
        form.submit();
    }

    // Copy code function using BarangayLink notification if available
    function copyCode(elementId) {
        const code = document.getElementById(elementId).textContent;
        navigator.clipboard.writeText(code).then(() => {
            if (window.BarangayLink && window.BarangayLink.showNotification) {
                window.BarangayLink.showNotification('✅ Code copied to clipboard!', 'success');
            } else {
                alert('✅ Code copied to clipboard!');
            }
        }).catch(() => {
            // Fallback for older browsers
            const textarea = document.createElement('textarea');
            textarea.value = code;
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            
            if (window.BarangayLink && window.BarangayLink.showNotification) {
                window.BarangayLink.showNotification('✅ Code copied to clipboard!', 'success');
            } else {
                alert('✅ Code copied to clipboard!');
            }
        });
    }

    // Initialize modal event listeners
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('confirmModal');
        const confirmBtn = document.getElementById('confirmAction');
        const cancelBtn = document.getElementById('cancelConfirm');
        
        // Confirm button
        confirmBtn.addEventListener('click', function() {
            executeRefresh();
            hideConfirmationModal();
        });
        
        // Cancel button
        cancelBtn.addEventListener('click', function() {
            hideConfirmationModal();
            
            // Show cancellation notification
            if (window.BarangayLink && window.BarangayLink.showNotification) {
                window.BarangayLink.showNotification('Code generation cancelled', 'info');
            }
        });
        
        // Close modal when clicking outside
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                hideConfirmationModal();
            }
        });
        
        // Close on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('show')) {
                hideConfirmationModal();
            }
        });

        // Add pulse animation to code display if there's a success message
        <?php if (isset($message)): ?>
        setTimeout(function() {
            const codeDisplays = document.querySelectorAll('.code-display');
            codeDisplays.forEach(display => {
                display.classList.add('new-code');
                setTimeout(() => {
                    display.classList.remove('new-code');
                }, 1500);
            });
            
            // Show success notification using BarangayLink system
            if (window.BarangayLink && window.BarangayLink.showNotification) {
                window.BarangayLink.showNotification('<?php echo $message; ?>', 'success');
            }
        }, 100);
        <?php endif; ?>
    });
    </script>
    
    <script src="../../src/js/main.js"></script>
    <script src="../../src/js/mobile-menu.js"></script>
</body>
</html>