<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/account_tokens.php';
require_once '../includes/auth_layout.php';

if (isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$sent  = false;
$error = '';
$prefill = trim($_POST['email'] ?? $_GET['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $error = 'Your session expired. Please try again.';
    } elseif (!filter_var($prefill, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $tokens = new AccountTokens();
        $user   = $tokens->findUserByEmail($prefill);

        // Only unverified, active accounts get an email; the response is always the same.
        if ($user && $user['status'] === 'active' && !(int)$user['email_verified']
            && $tokens->cooldownRemaining((int)$user['id'], 'verify') === 0) {
            [$ok, $err] = $tokens->sendVerification((int)$user['id'], $user['email'], $user['full_name']);
            if (!$ok) {
                error_log("Verification email failed for user #{$user['id']}: $err");
            }
        }
        $sent = true;
    }
}

auth_page_start('Resend Verification', 'Verify your email', $sent ? '' : "Enter the email you registered with and we'll send you a new verification link.");
?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo e($error); ?></div>
            <?php endif; ?>

            <?php if ($sent): ?>
                <div class="alert alert-success">
                    If that email belongs to an account that still needs verification, we've sent a new link.
                    It expires in <?php echo (int)(VERIFY_TOKEN_TTL_MIN / 60); ?> hours. Please check your inbox (and spam folder).
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <?php echo csrf_field(); ?>
                    <div class="form-group">
                        <label for="email">Email address</label>
                        <input type="email" name="email" id="email" class="form-control" required
                               placeholder="you@example.com" value="<?php echo e($prefill); ?>">
                    </div>
                    <button type="submit" class="btn-primary">Send verification link</button>
                </form>
            <?php endif; ?>
<?php auth_page_end(); ?>
