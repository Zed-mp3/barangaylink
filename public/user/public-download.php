<?php
// No authentication required - this is a public page
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>Download BarangayLink Android App</title>
    <link rel="stylesheet" href="../../src/css/main.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            width: 100%;
            overflow-x: hidden;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .download-header {
            background: rgba(44, 62, 80, 0.98);
            color: white;
            padding: 12px;
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 100;
            width: 100%;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            flex-shrink: 0;
        }

        .header-content {
            max-width: 600px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .header-content img {
            height: 32px;
            width: auto;
            filter: brightness(0) invert(1);
        }

        .header-content span {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .download-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            padding: 16px 12px;
            width: 100%;
            min-height: 0;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        .thankyou-card {
            background: white;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            max-width: 500px;
            width: 100%;
            padding: 24px 20px;
            text-align: center;
            animation: slideUp 0.4s ease;
            margin: 0 auto 20px;
        }

        .app-icon {
            width: 75px;
            height: 75px;
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            color: white;
            font-size: 38px;
            box-shadow: 0 8px 16px rgba(44, 62, 80, 0.2);
        }

        .thankyou-card h1 {
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 1.8rem;
            line-height: 1.2;
            font-weight: 700;
        }

        .thankyou-card p {
            color: #666;
            font-size: 1rem;
            line-height: 1.4;
            margin-bottom: 16px;
        }

        .download-badge-large {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
            padding: 5px 16px;
            border-radius: 50px;
            display: inline-block;
            font-weight: 600;
            margin-bottom: 16px;
            font-size: 0.9rem;
            letter-spacing: 0.5px;
        }

        .features {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin: 20px 0;
            flex-wrap: wrap;
        }

        .feature {
            text-align: center;
            flex: 1;
            min-width: 70px;
        }

        .feature span {
            font-size: 2rem;
            display: block;
            margin-bottom: 4px;
        }

        .feature p {
            font-size: 0.85rem;
            margin: 0;
            color: #666;
            font-weight: 500;
        }

        .warning-note {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 12px;
            border-radius: 12px;
            margin: 16px 0;
            font-size: 0.9rem;
            text-align: left;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .warning-note span {
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .warning-note div {
            flex: 1;
        }

        .download-info {
            background: #f8f9fa;
            border-radius: 16px;
            padding: 16px;
            margin: 16px 0;
            text-align: left;
            border-left: 4px solid #3498db;
        }

        .download-info h3 {
            color: #2c3e50;
            margin-bottom: 12px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .download-info ul {
            margin: 0;
            padding-left: 20px;
        }

        .download-info li {
            margin: 8px 0;
            color: #555;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .button-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin: 20px 0 16px;
        }

        .btn-download {
            background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
            color: white;
            padding: 14px 20px;
            border-radius: 60px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 8px 16px rgba(39, 174, 96, 0.2);
            width: 100%;
            border: none;
            cursor: pointer;
        }

        .btn-download:active {
            transform: translateY(2px);
            box-shadow: 0 4px 8px rgba(39, 174, 96, 0.2);
        }

        .btn-secondary {
            background: white;
            color: #2c3e50;
            padding: 14px 20px;
            border-radius: 60px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
            border: 2px solid #3498db;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 1rem;
            width: 100%;
        }

        .btn-secondary:active {
            background: #e6f0ff;
            transform: translateY(2px);
        }

        .register-prompt {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #eee;
            font-size: 0.9rem;
        }

        .register-prompt a {
            color: #3498db;
            font-weight: 600;
            text-decoration: none;
            padding: 5px;
        }

        .version-info {
            font-size: 0.75rem;
            color: #999;
            margin-top: 16px;
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

        /* Small phones (iPhone SE, etc) */
        @media (max-width: 375px) {
            .thankyou-card {
                padding: 20px 16px;
            }

            .app-icon {
                width: 65px;
                height: 65px;
                font-size: 32px;
            }

            .thankyou-card h1 {
                font-size: 1.5rem;
            }

            .features {
                gap: 10px;
            }

            .feature span {
                font-size: 1.8rem;
            }

            .feature p {
                font-size: 0.75rem;
            }

            .btn-download, .btn-secondary {
                padding: 12px 16px;
                font-size: 1rem;
            }
        }

        /* Very small phones */
        @media (max-width: 320px) {
            .thankyou-card {
                padding: 16px 12px;
            }

            .app-icon {
                width: 55px;
                height: 55px;
                font-size: 28px;
            }

            .thankyou-card h1 {
                font-size: 1.3rem;
            }

            .features {
                gap: 5px;
            }

            .feature span {
                font-size: 1.5rem;
            }
        }

        /* Landscape orientation */
        @media (max-height: 600px) and (orientation: landscape) {
            .download-container {
                justify-content: flex-start;
                padding: 10px;
            }

            .thankyou-card {
                padding: 16px;
            }

            .app-icon {
                width: 50px;
                height: 50px;
                font-size: 24px;
                margin-bottom: 10px;
            }

            .features {
                margin: 10px 0;
            }

            .download-info {
                margin: 10px 0;
                padding: 12px;
            }

            .download-info li {
                margin: 4px 0;
            }
        }

        /* Tall phones */
        @media (min-height: 800px) {
            .download-container {
                justify-content: center;
            }
        }

        /* Ensure scrolling works */
        .download-container::-webkit-scrollbar {
            width: 0;
            background: transparent;
        }
    </style>
</head>
<body>
    <!-- Simple Header -->
    <div class="download-header">
        <div class="header-content">
            <img src="../../assets/1772429077726-removebg-preview.png" alt="BarangayLink">
            <span>BarangayLink</span>
        </div>
    </div>

    <!-- Main Content - Now properly scrollable -->
    <div class="download-container">
        <div class="thankyou-card">
            <div class="app-icon">
                📱
            </div>
            
            <div class="download-badge-large">
                FREE DOWNLOAD
            </div>
            
            <h1>BarangayLink for Android</h1>
            <p>Stay connected with your community</p>
            
            <div class="features">
                <div class="feature">
                    <span>📢</span>
                    <p>Announcements</p>
                </div>
                <div class="feature">
                    <span>📝</span>
                    <p>Requests</p>
                </div>
                <div class="feature">
                    <span>🔔</span>
                    <p>Notifications</p>
                </div>
            </div>
            
            <div class="warning-note">
                <span>⚠️</span>
                <div>
                    <strong>Android only:</strong> Enable "Install from unknown sources" in settings.
                </div>
            </div>
            
            <div class="download-info">
                <h3>
                    <span>📱</span> How to Install
                </h3>
                <ul>
                    <li><strong>1.</strong> Tap "Download Now"</li>
                    <li><strong>2.</strong> Open the APK file</li>
                    <li><strong>3.</strong> Tap "Settings" if prompted</li>
                    <li><strong>4.</strong> Allow from this source</li>
                    <li><strong>5.</strong> Tap "Install"</li>
                    <li><strong>6.</strong> Open app & create account</li>
                </ul>
            </div>
            
            <div class="button-group">
                <a href="https://barangaylink.sbs/downloads/barangaylink.apk" class="btn-download" id="downloadNowBtn">
                    ⬇️ Download Now
                </a>
                <a href="register.php" class="btn-secondary">
                    📝 Create Account
                </a>
            </div>
            
            <div class="register-prompt">
                Already have an account? <a href="login.php">Login</a>
            </div>
            
            <div class="version-info">
                v1.0.0 • ~15MB • Secure
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const downloadBtn = document.getElementById('downloadNowBtn');
        
        downloadBtn.addEventListener('click', function(e) {
            console.log('Download started');
            
            if (typeof gtag !== 'undefined') {
                gtag('event', 'download', {
                    'event_category': 'app',
                    'event_label': 'public_download'
                });
            }
        });

        // Prevent zoom on double tap
        let lastTap = 0;
        document.addEventListener('touchend', function(e) {
            const currentTime = new Date().getTime();
            const tapLength = currentTime - lastTap;
            if (tapLength < 500 && tapLength > 0) {
                e.preventDefault();
            }
            lastTap = currentTime;
        });
    });
    </script>
</body>
</html>