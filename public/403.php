<?php
require_once '../includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Access Denied</title>
    <link rel="stylesheet" href="../src/css/main.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-box text-center">
            <h1 style="font-size: 72px; color: var(--warning-color);">403</h1>
            <h2>Access Denied</h2>
            <p>You don't have permission to access this page.</p>
            <div class="mt-20">
                <a href="index.php" class="btn btn-primary">Go to Homepage</a>
                <a href="../logout.php" class="btn btn-danger">Logout</a>
            </div>
        </div>
    </div>
</body>
</html>