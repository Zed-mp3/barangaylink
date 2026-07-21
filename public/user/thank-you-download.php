<?php
require_once '../../includes/config.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

$auth = new Auth();
$auth->requireUser();

$user = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You for Downloading - BarangayLink</title>
    <link rel="stylesheet" href="../../src/css/main.css">
    <style>
        .download-thankyou-container {
            min-height: calc(100vh - 200px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .thankyou-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            max-width: 600px;
            width: 100%;
            padding: 40px;
            text-align: center;
            animation: slideUp 0.5s ease;
        }
        
        .success-icon {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            color: white;
            font-size: 50px;
            box-shadow: 0 10px 20px rgba(39, 174, 96, 0.3);
            animation: scaleIn 0.5s ease 0.2s both;
        }
        
        .thankyou-card h1 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-size: 2.2rem;
            animation: fadeIn 0.5s ease 0.3s both;
        }
        
        .thankyou-card p {
            color: #666;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 25px;
            animation: fadeIn 0.5s ease 0.4s both;
        }
        
        .download-info {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin: 30px 0;
            text-align: left;
            border-left: 4px solid #27ae60;
            animation: fadeIn 0.5s ease 0.5s both;
        }
        
        .download-info h3 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .download-info ul {
            margin: 10px 0;
            padding-left: 25px;
        }
        
        .download-info li {
            margin: 8px 0;
            color: #555;
        }
        
        .countdown-timer {
            font-size: 1.2rem;
            color: #27ae60;
            font-weight: bold;
            margin: 20px 0;
            animation: pulse 2s infinite;
        }
        
        .button-group {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            animation: fadeIn 0.5s ease 0.6s both;
        }
        
        .btn-download {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
            padding: 14px 30px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-download:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(39, 174, 96, 0.3);
        }
        
        .btn-secondary {
            background: #f8f9fa;
            color: #2c3e50;
            padding: 14px 30px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            border: 1px solid #ddd;
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        
        .btn-secondary:hover {
            background: #e9ecef;
            transform: translateY(-3px);
        }
        
        .android-icon-large {
            width: 50px;
            height: 50px;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }
        
        .redirect-message {
            color: #999;
            font-size: 0.9rem;
            margin-top: 20px;
            animation: fadeIn 0.5s ease 0.7s both;
        }
        
        .progress-bar {
            width: 100%;
            height: 6px;
            background: #f0f0f0;
            border-radius: 10px;
            margin: 25px 0 10px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #27ae60, #2ecc71);
            width: 100%;
            animation: shrink 10s linear forwards;
            transform-origin: left;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.8);
            }
            to {
                opacity: 1;
                transform: scale(1);
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
        
        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }
        
        @keyframes pulse {
            0% {
                opacity: 1;
            }
            50% {
                opacity: 0.7;
            }
            100% {
                opacity: 1;
            }
        }
        
        @keyframes shrink {
            from {
                transform: scaleX(1);
            }
            to {
                transform: scaleX(0);
            }
        }
        
        @media (max-width: 768px) {
            .thankyou-card {
                padding: 30px 20px;
            }
            
            .thankyou-card h1 {
                font-size: 1.8rem;
            }
            
            .button-group {
                flex-direction: column;
            }
            
            .btn-download, .btn-secondary {
                width: 100%;
                justify-content: center;
            }
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
                <a href="dashboard.php">
                    <span class="sidenav-icon">📊</span>
                    <span class="sidenav-text">Dashboard</span>
                </a>
            </li>
            <li class="sidenav-item">
                <a href="announcements.php">
                    <span class="sidenav-icon">📢</span>
                    <span class="sidenav-text">Announcements</span>
                </a>
            </li>
            <li class="sidenav-item">
                <a href="service_request.php">
                    <span class="sidenav-icon">📝</span>
                    <span class="sidenav-text">Request Service</span>
                </a>
            </li>
            <li class="sidenav-item">
                <a href="request_status.php">
                    <span class="sidenav-icon">📋</span>
                    <span class="sidenav-text">My Requests</span>
                </a>
            </li>
            <li class="sidenav-item">
                <a href="profile.php">
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
            Welcome, <?php echo $user['full_name']; ?>
        </div>
        <button class="mobile-menu-btn" id="mobile-menu-btn">☰</button>
    </nav>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <div class="download-thankyou-container">
                <div class="thankyou-card">
                    <div class="success-icon">
                        ✓
                    </div>
                    
                    <svg class="android-icon-large" viewBox="0 0 24 24" width="50" height="50">
                        <path fill="#27ae60" d="M17.6,9.2l2.5-4.3c0.2-0.4,0.1-0.9-0.3-1.1c-0.4-0.2-0.9-0.1-1.1,0.3l-2.5,4.3c-1.2-0.6-2.6-1-4.2-1s-3,0.4-4.2,1L5.3,4.1C5.1,3.7,4.6,3.6,4.2,3.8C3.8,4,3.7,4.5,3.9,4.9l2.5,4.3C4.5,10.5,3,12.7,3,15.2h18C21,12.7,19.5,10.5,17.6,9.2z M8.5,13.2c-0.7,0-1.2-0.6-1.2-1.2s0.6-1.2,1.2-1.2s1.2,0.6,1.2,1.2S9.2,13.2,8.5,13.2z M15.5,13.2c-0.7,0-1.2-0.6-1.2-1.2s0.6-1.2,1.2-1.2s1.2,0.6,1.2,1.2S16.2,13.2,15.5,13.2z M6,16.2c0,0.6,0.5,1,1,1h1v3c0,0.8,0.7,1.5,1.5,1.5s1.5-0.7,1.5-1.5v-3h2v3c0,0.8,0.7,1.5,1.5,1.5s1.5-0.7,1.5-1.5v-3h1c0.6,0,1-0.5,1-1v-7H6V16.2z"/>
                    </svg>
                    
                    <h1>Thank You!</h1>
                    <p>Your download of the BarangayLink Android app will begin shortly.</p>
                    
                    <div class="download-info">
                        <h3>
                            <span>📱</span> Installation Instructions
                        </h3>
                        <ul>
                            <li>Once downloaded, open the APK file</li>
                            <li>If prompted, allow installation from unknown sources</li>
                            <li>Follow the on-screen instructions to complete installation</li>
                            <li>Open the app and log in with your credentials</li>
                        </ul>
                        <p style="margin-top: 10px; font-size: 0.9rem; color: #888;">
                            <strong>Note:</strong> You may need to enable "Install unknown apps" in your phone settings.
                        </p>
                    </div>
                    
                    <div class="countdown-timer" id="countdown">Redirecting in 10 seconds...</div>
                    
                    <div class="progress-bar">
                        <div class="progress-fill" id="progressFill"></div>
                    </div>
                    
                    <div class="button-group">
                        <a href="https://barangaylink.sbs/downloads/barangaylink.apk" class="btn-download" id="downloadNowBtn">
                            ⬇️ Download Now
                        </a>
                        <a href="dashboard.php" class="btn-secondary">
                            ← Back to Dashboard
                        </a>
                    </div>
                    
                    <div class="redirect-message">
                        If your download doesn't start automatically, click the Download Now button above.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../../src/js/mobile-menu.js"></script>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const downloadUrl = 'https://barangaylink.sbs/downloads/barangaylink.apk';
        const countdownElement = document.getElementById('countdown');
        const downloadBtn = document.getElementById('downloadNowBtn');
        let secondsLeft = 10;
        
        // Start download automatically after 2 seconds
        setTimeout(function() {
            // Create an invisible iframe to trigger download without leaving page
            const iframe = document.createElement('iframe');
            iframe.style.display = 'none';
            iframe.src = downloadUrl;
            document.body.appendChild(iframe);
            
            // Show notification
            showNotification('Download started!', 'success');
        }, 2000);
        
        // Update countdown
        const countdownInterval = setInterval(function() {
            secondsLeft--;
            
            if (secondsLeft > 0) {
                countdownElement.textContent = `Redirecting in ${secondsLeft} second${secondsLeft !== 1 ? 's' : ''}...`;
            } else {
                clearInterval(countdownInterval);
                countdownElement.textContent = 'Redirecting now...';
                
                // Redirect to dashboard after download has started
                setTimeout(function() {
                    window.location.href = 'dashboard.php';
                }, 1000);
            }
        }, 1000);
        
        // Manual download button
        downloadBtn.addEventListener('click', function(e) {
            // Don't prevent default, let the link work normally
            // But clear the countdown since user is manually downloading
            clearInterval(countdownInterval);
        });
        
        // Simple notification function
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <span>${message}</span>
                <button class="notification-close">×</button>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);
            
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }, 3000);
            
            notification.querySelector('.notification-close').addEventListener('click', function() {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            });
        }
    });
    </script>
</body>
</html>