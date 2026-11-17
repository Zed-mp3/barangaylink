<?php
/**
 * Shared page chrome for forgot/reset/verify/resend pages.
 * Styled to match public/index.php (login). Call auth_page_start() before any output.
 */

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

function auth_page_start(string $title, string $heading, string $subtitle = ''): void
{
    // Tokens live in the URL: don't leak them via Referer and don't cache these pages.
    header('Referrer-Policy: no-referrer');
    header('Cache-Control: no-store');
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="referrer" content="no-referrer">
    <title><?php echo e($title); ?> - BarangayLink</title>
    <link rel="stylesheet" href="../src/css/main.css">
    <style>
        .auth-box { border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,.2); padding: 40px; max-width: 400px; }
        .auth-box h2 { font-size: 1.6rem; margin-bottom: 10px; }
        .auth-logo { text-align: center; margin-bottom: 15px; }
        .auth-logo img { max-width: 100px; height: auto; }
        .auth-sub { text-align: center; margin-bottom: 25px; color: #666; font-size: .95rem; line-height: 1.5; }
        .auth-box .form-group { margin-bottom: 20px; }
        .auth-box .form-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #333; }
        .auth-box .form-control { width: 100%; padding: 12px 15px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 1rem; box-sizing: border-box; }
        .auth-box .form-control:focus { border-color: #667eea; outline: none; box-shadow: 0 0 0 3px rgba(102,126,234,.1); }
        .auth-box .btn-primary { display: block; width: 100%; padding: 14px; background: linear-gradient(135deg,#667eea 0%,#764ba2 100%); color: #fff; border: none; border-radius: 10px; font-size: 1.05rem; font-weight: 600; cursor: pointer; text-align: center; text-decoration: none; box-sizing: border-box; }
        .auth-box .btn-primary:hover { box-shadow: 0 5px 20px rgba(102,126,234,.4); }
        .auth-box .auth-links { text-align: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid #e0e0e0; }
        .auth-box .btn-link { color: #667eea; text-decoration: none; font-weight: 600; }
        .auth-box .btn-link:hover { text-decoration: underline; }
        .auth-box .alert { font-size: .95rem; border-radius: 10px; line-height: 1.5; }
        .auth-box .password-wrapper { position: relative; }
        .auth-box .toggle-password { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; user-select: none; }
        .auth-box .password-wrapper .form-control { padding-right: 45px; }
        .status-icon { font-size: 3rem; text-align: center; margin-bottom: 10px; }
        @media (max-width: 480px) { .auth-box { padding: 30px 20px; } }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-logo">
                <img src="../assets/1772429077726-removebg-preview.png" alt="BarangayLink Logo" onerror="this.style.display='none'">
            </div>
            <h2><?php echo e($heading); ?></h2>
            <?php if ($subtitle !== ''): ?><p class="auth-sub"><?php echo e($subtitle); ?></p><?php endif; ?>
<?php
}

function auth_page_end(string $script = ''): void
{
    ?>
            <div class="auth-links">
                <p><a href="index.php" class="btn-link">&larr; Back to Login</a></p>
            </div>
        </div>
    </div>
    <?php if ($script !== ''): ?><script><?php echo $script; ?></script><?php endif; ?>
</body>
</html>
<?php
}
