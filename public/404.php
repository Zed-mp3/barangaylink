<?php
require_once '../includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <link rel="stylesheet" href="../src/css/main.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box text-center">
            <h1 style="font-size: 72px; color: var(--danger-color);">404</h1>
            <h2>Page Not Found</h2>
            <p>The page you are looking for doesn't exist or has been moved.</p>
            <div class="mt-20">
                <a href="index.php" class="btn btn-primary">Go to Homepage</a>
                <a href="javascript:history.back()" class="btn btn-secondary">Go Back</a>
            </div>
        </div>
    </div>
</body>
</html>