<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/account_tokens.php';
require_once '../includes/auth_layout.php';

$result = (new AccountTokens())->verifyEmail((string)($_GET['token'] ?? ''));
$status = $result['status'];
$resendUrl = 'resend_verification.php' . ($result['email'] ? '?email=' . urlencode($result['email']) : '');

$headings = [
    'ok'      => 'Email verified',
    'already' => 'Already verified',
    'expired' => 'Link expired',
    'invalid' => 'Link not valid',
];

auth_page_start('Verify Email', $headings[$status] ?? 'Verify Email');
?>
            <?php if ($status === 'ok'): ?>
                <div class="status-icon">✅</div>
                <div class="alert alert-success">Thank you! Your email has been verified. You can now log in.</div>
                <a href="index.php" class="btn-primary">Go to Login</a>

            <?php elseif ($status === 'already'): ?>
                <div class="status-icon">✅</div>
                <div class="alert alert-success">This email address is already verified. You can log in.</div>
                <a href="index.php" class="btn-primary">Go to Login</a>

            <?php elseif ($status === 'expired'): ?>
                <div class="alert alert-danger">This verification link has expired. Request a new one below.</div>
                <a href="<?php echo e($resendUrl); ?>" class="btn-primary">Resend verification email</a>

            <?php else: ?>
                <div class="alert alert-danger">This verification link is invalid or was already used.</div>
                <a href="<?php echo e($resendUrl); ?>" class="btn-primary">Resend verification email</a>
            <?php endif; ?>
<?php auth_page_end(); ?>
