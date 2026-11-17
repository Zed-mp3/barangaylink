<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/account_tokens.php';
require_once '../includes/auth_layout.php';

// Already logged in? Nothing to reset.
if (isset($_SESSION['user_id'])) {
    redirect('index.php');
}

$sent  = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $error = 'Your session expired. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $tokens = new AccountTokens();
            $user   = $tokens->findUserByEmail($email);

            // Only email real, active accounts — but never reveal whether one exists.
            if ($user && $user['status'] === 'active' && $tokens->cooldownRemaining((int)$user['id'], 'reset') === 0) {
                [$ok, $err] = $tokens->sendPasswordReset((int)$user['id'], $user['email'], $user['full_name']);
                if (!$ok) {
                    error_log("Password reset email failed for user #{$user['id']}: $err");
                }
            }
            $sent = true;
        }
    }
}

auth_page_start('Forgot Password', 'Forgot your password?', $sent ? '' : "Enter your account email and we'll send you a link to reset it.");
?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo e($error); ?></div>
            <?php endif; ?>

            <?php if ($sent): ?>
                <div class="alert alert-success">
                    If an account exists for that email, we've sent a password reset link. It expires in
                    <?php echo (int)(RESET_TOKEN_TTL_MIN); ?> minutes. Please check your inbox (and spam folder).
                </div>
            <?php else: ?>
                <form method="POST" action="">
                    <?php echo csrf_field(); ?>
                    <div class="form-group">
                        <label for="email">Email address</label>
                        <input type="email" name="email" id="email" class="form-control" required autofocus
                               placeholder="you@example.com" value="<?php echo e($_POST['email'] ?? ''); ?>">
                    </div>
                    <button type="submit" class="btn-primary">Send reset link</button>
                </form>
            <?php endif; ?>
<?php auth_page_end(); ?>
