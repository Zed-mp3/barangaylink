<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/account_tokens.php';
require_once '../includes/auth_layout.php';

$token  = (string)($_POST['token'] ?? $_GET['token'] ?? '');
$tokens = new AccountTokens();
$row    = $tokens->validateReset($token);   // viewing the page does NOT consume the token
$error  = '';

if ($row && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (!csrf_valid()) {
        $error = 'Your session expired. Please try again.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        [$ok, $err] = $tokens->resetPassword($token, $password);
        if ($ok) {
            // Start clean: drop any session this browser had.
            $_SESSION = [];
            redirect('index.php?reset=1');
        }
        $error = $err;
    }
}

auth_page_start('Reset Password', $row ? 'Choose a new password' : 'Link not valid', $row ? 'Use at least 8 characters.' : '');
?>
            <?php if (!$row): ?>
                <div class="alert alert-danger">
                    This password reset link is invalid, has expired, or was already used.
                </div>
                <a href="forgot_password.php" class="btn-primary">Request a new link</a>
            <?php else: ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo e($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="token" value="<?php echo e($token); ?>">

                    <div class="form-group">
                        <label for="password">New password</label>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="password" class="form-control" required minlength="8" autocomplete="new-password">
                            <span class="toggle-password" onclick="togglePw(this)" role="button" aria-label="Toggle password visibility">👁️</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm new password</label>
                        <div class="password-wrapper">
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="8" autocomplete="new-password">
                            <span class="toggle-password" onclick="togglePw(this)" role="button" aria-label="Toggle password visibility">👁️</span>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary">Reset password</button>
                </form>
            <?php endif; ?>
<?php
auth_page_end("
function togglePw(el) {
    var input = el.previousElementSibling;
    if (!input) return;
    input.type = input.type === 'password' ? 'text' : 'password';
    el.textContent = input.type === 'password' ? '\u{1F441}\u{FE0F}' : '\u{1F512}';
}
");
