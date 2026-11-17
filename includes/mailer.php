<?php
/**
 * GmailMailer — sends email through the Gmail API (Google Cloud).
 *
 * Uses an OAuth2 refresh token to get a short-lived access token, then calls
 *   POST https://gmail.googleapis.com/gmail/v1/users/me/messages/send
 * Everything goes over HTTPS (port 443), so it works on hosts that block SMTP.
 *
 * Required environment variables (see EMAIL_SETUP.md):
 *   GMAIL_CLIENT_ID, GMAIL_CLIENT_SECRET, GMAIL_REFRESH_TOKEN, GMAIL_SENDER
 * Optional:
 *   GMAIL_SENDER_NAME  (default "BarangayLink")
 *   MAIL_LOG_ONLY=1    (local dev: write the email to the PHP error log instead of sending)
 */
require_once __DIR__ . '/config.php';

class GmailMailer
{
    private static $accessToken = null;
    private static $accessTokenExpires = 0;

    public static function isConfigured(): bool
    {
        return GMAIL_CLIENT_ID !== '' && GMAIL_CLIENT_SECRET !== ''
            && GMAIL_REFRESH_TOKEN !== '' && GMAIL_SENDER !== '';
    }

    /**
     * @return array [bool $ok, string $error]
     */
    public static function send(string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): array
    {
        $toEmail = self::stripNewlines($toEmail);
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            return [false, 'Invalid recipient address'];
        }

        if ($textBody === '') {
            $textBody = trim(html_entity_decode(
                strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $htmlBody)),
                ENT_QUOTES,
                'UTF-8'
            ));
        }

        // Local development helper: don't call Google, just log the message.
        if (getenv('MAIL_LOG_ONLY') === '1') {
            error_log("[MAIL_LOG_ONLY] To: $toEmail\nSubject: $subject\n$textBody");
            return [true, ''];
        }

        if (!self::isConfigured()) {
            error_log('Mailer: Gmail API credentials are not configured (GMAIL_* environment variables).');
            return [false, 'Email service is not configured'];
        }

        [$token, $tokenError] = self::getAccessToken();
        if ($token === null) {
            return [false, $tokenError];
        }

        $mime = self::buildMime($toEmail, $toName, $subject, $htmlBody, $textBody);
        $raw  = rtrim(strtr(base64_encode($mime), '+/', '-_'), '=');

        [$status, $response, $curlError] = self::post(
            'https://gmail.googleapis.com/gmail/v1/users/me/messages/send',
            json_encode(['raw' => $raw]),
            ['Authorization: Bearer ' . $token, 'Content-Type: application/json']
        );

        if ($curlError !== '') {
            error_log('Mailer: network error while sending: ' . $curlError);
            return [false, 'Could not reach the Gmail API'];
        }
        if ($status < 200 || $status >= 300) {
            error_log("Mailer: Gmail API returned HTTP $status: " . substr($response, 0, 500));
            return [false, "Gmail API error (HTTP $status)"];
        }

        return [true, ''];
    }

    /**
     * Exchange the refresh token for an access token (cached for the request).
     * @return array [?string $token, string $error]
     */
    private static function getAccessToken(): array
    {
        if (self::$accessToken !== null && time() < self::$accessTokenExpires - 30) {
            return [self::$accessToken, ''];
        }

        [$status, $response, $curlError] = self::post(
            'https://oauth2.googleapis.com/token',
            http_build_query([
                'client_id'     => GMAIL_CLIENT_ID,
                'client_secret' => GMAIL_CLIENT_SECRET,
                'refresh_token' => GMAIL_REFRESH_TOKEN,
                'grant_type'    => 'refresh_token',
            ]),
            ['Content-Type: application/x-www-form-urlencoded']
        );

        if ($curlError !== '') {
            error_log('Mailer: network error while getting access token: ' . $curlError);
            return [null, 'Could not reach Google'];
        }

        $data = json_decode($response, true);
        if ($status !== 200 || empty($data['access_token'])) {
            $reason = is_array($data) ? (($data['error'] ?? '') . ' ' . ($data['error_description'] ?? '')) : substr($response, 0, 200);
            error_log("Mailer: token request failed (HTTP $status): " . trim($reason));
            if (is_array($data) && ($data['error'] ?? '') === 'invalid_grant') {
                error_log('Mailer: invalid_grant = refresh token expired/revoked. Re-generate it (consent screen must be "In production", not "Testing").');
            }
            return [null, 'Could not authorize with Google'];
        }

        self::$accessToken = $data['access_token'];
        self::$accessTokenExpires = time() + (int)($data['expires_in'] ?? 3600);
        return [self::$accessToken, ''];
    }

    private static function buildMime(string $toEmail, string $toName, string $subject, string $html, string $text): string
    {
        $boundary = 'bl_' . bin2hex(random_bytes(12));
        $toName   = self::stripNewlines($toName);

        $headers = [
            'From: ' . self::formatAddress(GMAIL_SENDER_NAME, GMAIL_SENDER),
            'To: ' . self::formatAddress($toName, $toEmail),
            'Subject: ' . self::encodeHeader(self::stripNewlines($subject)),
            'Date: ' . date('r'),
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ];

        $body  = "--$boundary\r\n";
        $body .= "Content-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($text));
        $body .= "--$boundary\r\n";
        $body .= "Content-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
        $body .= chunk_split(base64_encode($html));
        $body .= "--$boundary--";

        return implode("\r\n", $headers) . "\r\n\r\n" . $body;
    }

    private static function formatAddress(string $name, string $email): string
    {
        $email = self::stripNewlines($email);
        if (trim($name) === '') {
            return $email;
        }
        return self::encodeHeader(self::stripNewlines($name), true) . ' <' . $email . '>';
    }

    /** RFC 2047 encode non-ASCII text; quote ASCII display names that contain specials. */
    private static function encodeHeader(string $value, bool $isName = false): string
    {
        if (preg_match('/[^\x20-\x7E]/', $value)) {
            return '=?UTF-8?B?' . base64_encode($value) . '?=';
        }
        if ($isName && preg_match('/[()<>@,;:\\\\".\[\]]/', $value)) {
            return '"' . addcslashes($value, '"\\') . '"';
        }
        return $value;
    }

    /** Prevent header injection. */
    private static function stripNewlines(string $value): string
    {
        return trim(str_replace(["\r", "\n", "\0"], '', $value));
    }

    /** @return array [int $status, string $body, string $curlError] */
    private static function post(string $url, string $body, array $headers): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $response = curl_exec($ch);
        $status   = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        return [$status, $response === false ? '' : (string)$response, $error];
    }
}
