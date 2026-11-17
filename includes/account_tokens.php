<?php
/**
 * AccountTokens — email verification + password reset.
 *
 * - Raw tokens are random 256-bit values that only ever exist in the emailed link.
 * - The database stores only SHA-256(token), so a leaked DB can't be used to reset accounts.
 * - Tokens are single-use and expire (verify: 24h, reset: 60 min — see config.php).
 * - All expiry math uses MySQL NOW() so PHP/DB timezone differences can't break it.
 */
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/mailer.php';

class AccountTokens
{
    private $conn;

    public function __construct(?Database $db = null)
    {
        $db = $db ?: new Database();
        $this->conn = $db->getConnection();
    }

    /* ------------------------------------------------------------------
     * Lookups
     * ------------------------------------------------------------------ */

    public function findUserByEmail(string $email): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT id, email, full_name, status, email_verified FROM users WHERE email = ? LIMIT 1"
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /** Seconds the user must still wait before another email of this type may be sent. */
    public function cooldownRemaining(int $userId, string $type): int
    {
        $stmt = $this->conn->prepare(
            "SELECT TIMESTAMPDIFF(SECOND, created_at, NOW()) AS age
               FROM email_tokens WHERE user_id = ? AND type = ?
              ORDER BY created_at DESC LIMIT 1"
        );
        $stmt->bind_param('is', $userId, $type);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return 0;
        }
        return max(0, EMAIL_RESEND_COOLDOWN_SEC - (int)$row['age']);
    }

    private function find(string $rawToken, string $type): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $rawToken)) {
            return null;
        }
        $hash = hash('sha256', $rawToken);

        $stmt = $this->conn->prepare(
            "SELECT t.id, t.user_id, t.used_at, (t.expires_at < NOW()) AS expired,
                    u.email, u.full_name, u.status, u.email_verified
               FROM email_tokens t
               JOIN users u ON u.id = t.user_id
              WHERE t.token_hash = ? AND t.type = ?
              LIMIT 1"
        );
        $stmt->bind_param('ss', $hash, $type);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /* ------------------------------------------------------------------
     * Issue + send
     * ------------------------------------------------------------------ */

    /** Create a new token (invalidating older unused ones) and return the RAW token. */
    private function issue(int $userId, string $type): string
    {
        $stmt = $this->conn->prepare("DELETE FROM email_tokens WHERE user_id = ? AND type = ? AND used_at IS NULL");
        $stmt->bind_param('is', $userId, $type);
        $stmt->execute();
        $stmt->close();

        $raw  = bin2hex(random_bytes(32));
        $hash = hash('sha256', $raw);
        $ttl  = $type === 'verify' ? VERIFY_TOKEN_TTL_MIN : RESET_TOKEN_TTL_MIN;

        $stmt = $this->conn->prepare(
            "INSERT INTO email_tokens (user_id, type, token_hash, expires_at)
             VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE))"
        );
        $stmt->bind_param('issi', $userId, $type, $hash, $ttl);
        $stmt->execute();
        $stmt->close();

        return $raw;
    }

    /** @return array [bool $ok, string $error] */
    public function sendVerification(int $userId, string $email, string $fullName): array
    {
        $token = $this->issue($userId, 'verify');
        $link  = SITE_URL . '/public/verify_email.php?token=' . $token;

        [$html, $text] = self::buildEmail(
            $fullName,
            'Verify your email address',
            'Thanks for registering with ' . SITE_NAME . '. Please confirm your email address to activate your account.',
            'Verify my email',
            $link,
            'This link expires in ' . self::humanTtl(VERIFY_TOKEN_TTL_MIN) . '. If you did not create an account, you can ignore this email.'
        );

        return GmailMailer::send($email, $fullName, 'Verify your ' . SITE_NAME . ' account', $html, $text);
    }

    /** @return array [bool $ok, string $error] */
    public function sendPasswordReset(int $userId, string $email, string $fullName): array
    {
        $token = $this->issue($userId, 'reset');
        $link  = SITE_URL . '/public/reset_password.php?token=' . $token;

        [$html, $text] = self::buildEmail(
            $fullName,
            'Reset your password',
            'We received a request to reset the password for your ' . SITE_NAME . ' account.',
            'Choose a new password',
            $link,
            'This link expires in ' . self::humanTtl(RESET_TOKEN_TTL_MIN) . ' and can be used once. If you did not request this, you can safely ignore this email — your password will not change.'
        );

        return GmailMailer::send($email, $fullName, 'Reset your ' . SITE_NAME . ' password', $html, $text);
    }

    /* ------------------------------------------------------------------
     * Verify email
     * ------------------------------------------------------------------ */

    /**
     * @return array ['status' => 'ok'|'already'|'expired'|'invalid', 'email' => ?string]
     */
    public function verifyEmail(string $rawToken): array
    {
        $row = $this->find($rawToken, 'verify');
        if (!$row) {
            return ['status' => 'invalid', 'email' => null];
        }

        // Link scanners / double-clicks: if the account is already verified, say so.
        if ((int)$row['email_verified'] === 1) {
            return ['status' => 'already', 'email' => $row['email']];
        }
        if ($row['used_at'] !== null) {
            return ['status' => 'invalid', 'email' => null];
        }
        if ((int)$row['expired'] === 1) {
            return ['status' => 'expired', 'email' => $row['email']];
        }

        $this->conn->begin_transaction();
        try {
            $stmt = $this->conn->prepare("UPDATE users SET email_verified = 1, email_verified_at = NOW() WHERE id = ?");
            $stmt->bind_param('i', $row['user_id']);
            $stmt->execute();
            $stmt->close();

            $stmt = $this->conn->prepare("UPDATE email_tokens SET used_at = NOW() WHERE id = ?");
            $stmt->bind_param('i', $row['id']);
            $stmt->execute();
            $stmt->close();

            $this->conn->commit();
        } catch (Throwable $e) {
            $this->conn->rollback();
            error_log('verifyEmail failed: ' . $e->getMessage());
            return ['status' => 'invalid', 'email' => null];
        }

        return ['status' => 'ok', 'email' => $row['email']];
    }

    /* ------------------------------------------------------------------
     * Reset password
     * ------------------------------------------------------------------ */

    /** Returns the token row if it is unused, unexpired and the account is active. */
    public function validateReset(string $rawToken): ?array
    {
        $row = $this->find($rawToken, 'reset');
        if (!$row || $row['used_at'] !== null || (int)$row['expired'] === 1 || $row['status'] !== 'active') {
            return null;
        }
        return $row;
    }

    /** @return array [bool $ok, string $error] */
    public function resetPassword(string $rawToken, string $newPassword): array
    {
        $row = $this->validateReset($rawToken);
        if (!$row) {
            return [false, 'This reset link is invalid or has expired.'];
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);

        $this->conn->begin_transaction();
        try {
            // New password, sign out "remember me" devices, and mark the email as verified
            // (clicking the emailed link proves the user controls the address).
            $stmt = $this->conn->prepare(
                "UPDATE users
                    SET password = ?, remember_token = NULL, token_expiry = NULL,
                        email_verified = 1,
                        email_verified_at = COALESCE(email_verified_at, NOW())
                  WHERE id = ?"
            );
            $stmt->bind_param('si', $hash, $row['user_id']);
            $stmt->execute();
            $stmt->close();

            // Burn every outstanding token for this user.
            $stmt = $this->conn->prepare("DELETE FROM email_tokens WHERE user_id = ?");
            $stmt->bind_param('i', $row['user_id']);
            $stmt->execute();
            $stmt->close();

            $this->conn->commit();
        } catch (Throwable $e) {
            $this->conn->rollback();
            error_log('resetPassword failed: ' . $e->getMessage());
            return [false, 'Something went wrong. Please try again.'];
        }

        return [true, ''];
    }

    /* ------------------------------------------------------------------
     * Email template
     * ------------------------------------------------------------------ */

    private static function humanTtl(int $minutes): string
    {
        return $minutes % 60 === 0 && $minutes >= 60
            ? ($minutes / 60) . ' hour' . ($minutes / 60 > 1 ? 's' : '')
            : $minutes . ' minutes';
    }

    /** @return array [string $html, string $text] */
    private static function buildEmail(string $name, string $heading, string $intro, string $buttonText, string $link, string $footerNote): array
    {
        $e = static function ($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); };
        $first = trim(explode(' ', trim($name))[0] ?? '');
        $hello = $first !== '' ? 'Hi ' . $first . ',' : 'Hello,';

        $html = '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;padding:24px 0;"><tr><td align="center">'
            . '<table role="presentation" width="520" cellpadding="0" cellspacing="0" style="max-width:520px;width:100%;background:#ffffff;border-radius:12px;overflow:hidden;">'
            . '<tr><td style="background:#2c3e50;color:#ffffff;padding:20px 28px;font-size:20px;font-weight:bold;">' . $e(SITE_NAME) . '</td></tr>'
            . '<tr><td style="padding:28px;color:#333333;font-size:15px;line-height:1.6;">'
            . '<h2 style="margin:0 0 16px;font-size:20px;color:#2c3e50;">' . $e($heading) . '</h2>'
            . '<p style="margin:0 0 12px;">' . $e($hello) . '</p>'
            . '<p style="margin:0 0 24px;">' . $e($intro) . '</p>'
            . '<p style="margin:0 0 24px;"><a href="' . $e($link) . '" style="background:#667eea;color:#ffffff;text-decoration:none;padding:12px 24px;border-radius:8px;font-weight:bold;display:inline-block;">' . $e($buttonText) . '</a></p>'
            . '<p style="margin:0 0 8px;font-size:13px;color:#666666;">If the button does not work, copy and paste this link into your browser:</p>'
            . '<p style="margin:0 0 24px;font-size:13px;word-break:break-all;"><a href="' . $e($link) . '" style="color:#667eea;">' . $e($link) . '</a></p>'
            . '<p style="margin:0;font-size:13px;color:#666666;">' . $e($footerNote) . '</p>'
            . '</td></tr></table></td></tr></table></body></html>';

        $text = $hello . "\n\n" . $intro . "\n\n" . $buttonText . ":\n" . $link . "\n\n" . $footerNote . "\n\n- " . SITE_NAME;

        return [$html, $text];
    }
}

/* ----------------------------------------------------------------------
 * Small CSRF helpers for the new forms
 * ---------------------------------------------------------------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_valid(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
}
