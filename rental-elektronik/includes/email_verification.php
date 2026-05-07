<?php
/**
 * ElektraRent - Email verification helpers
 */

const EMAIL_VERIFICATION_EXPIRY_MINUTES = 10;
const EMAIL_VERIFICATION_RESEND_COOLDOWN_SECONDS = 60;
const EMAIL_VERIFICATION_MAX_ATTEMPTS = 5;
const EMAIL_VERIFICATION_MAX_ATTEMPTS_PER_WINDOW = 10;
const EMAIL_VERIFICATION_ATTEMPT_WINDOW_MINUTES = 15;
const EMAIL_VERIFICATION_MAX_RESENDS_PER_WINDOW = 5;
const EMAIL_VERIFICATION_RESEND_WINDOW_MINUTES = 15;

function generateEmailVerificationCode(): string
{
    return (string) random_int(100000, 999999);
}

function verificationClientIp(): string
{
    return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
}

function verificationUserAgent(): string
{
    return substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500);
}

function rememberPendingVerificationUser(array $user): void
{
    $_SESSION['pending_verification_user_id'] = (int) $user['id'];
    $_SESSION['pending_verification_email'] = (string) ($user['email'] ?? '');
    $_SESSION['pending_verification_name'] = (string) ($user['nama_lengkap'] ?? '');
}

function clearPendingVerificationUser(): void
{
    unset(
        $_SESSION['pending_verification_user_id'],
        $_SESSION['pending_verification_email'],
        $_SESSION['pending_verification_name']
    );
}

function getPendingVerificationUser(PDO $db, int $userId): ?array
{
    $stmt = $db->prepare("
        SELECT id, nama_lengkap, email, status_akun, email_verified_at
        FROM users
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user || $user['status_akun'] !== 'pending_verification' || !empty($user['email_verified_at'])) {
        return null;
    }

    return $user;
}

function latestOpenEmailVerification(PDO $db, int $userId): ?array
{
    $stmt = $db->prepare("
        SELECT *
        FROM email_verifications
        WHERE user_id = ?
          AND verified_at IS NULL
        ORDER BY created_at DESC, id DESC
        LIMIT 1
    ");
    $stmt->execute([$userId]);
    $verification = $stmt->fetch();

    return $verification ?: null;
}

function createEmailVerification(PDO $db, int $userId, string $plainCode): void
{
    $db->prepare("
        UPDATE email_verifications
        SET expires_at = NOW()
        WHERE user_id = ?
          AND verified_at IS NULL
          AND expires_at > NOW()
    ")->execute([$userId]);

    $stmt = $db->prepare("
        INSERT INTO email_verifications
            (user_id, code_hash, expires_at, attempts, last_sent_at, ip_address, user_agent, created_at, updated_at)
        VALUES
            (?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), 0, NOW(), ?, ?, NOW(), NOW())
    ");
    $stmt->execute([
        $userId,
        password_hash($plainCode, PASSWORD_DEFAULT),
        EMAIL_VERIFICATION_EXPIRY_MINUTES,
        verificationClientIp(),
        verificationUserAgent(),
    ]);
}

function secondsUntilVerificationResend(?array $verification): int
{
    if (!$verification || empty($verification['last_sent_at'])) {
        return 0;
    }

    $lastSent = strtotime($verification['last_sent_at']);
    if ($lastSent === false) {
        return 0;
    }

    $elapsed = time() - $lastSent;
    return max(0, EMAIL_VERIFICATION_RESEND_COOLDOWN_SECONDS - $elapsed);
}

function isVerificationResendRateLimited(PDO $db, int $userId): bool
{
    $stmt = $db->prepare("
        SELECT COUNT(*) AS total
        FROM email_verifications
        WHERE user_id = ?
          AND ip_address = ?
          AND last_sent_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)
    ");
    $stmt->execute([
        $userId,
        verificationClientIp(),
        EMAIL_VERIFICATION_RESEND_WINDOW_MINUTES,
    ]);
    $row = $stmt->fetch();

    return (int) ($row['total'] ?? 0) >= EMAIL_VERIFICATION_MAX_RESENDS_PER_WINDOW;
}

function isVerificationAttemptRateLimited(PDO $db, int $userId): bool
{
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(attempts), 0) AS total_attempts
        FROM email_verifications
        WHERE user_id = ?
          AND ip_address = ?
          AND updated_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)
    ");
    $stmt->execute([
        $userId,
        verificationClientIp(),
        EMAIL_VERIFICATION_ATTEMPT_WINDOW_MINUTES,
    ]);
    $row = $stmt->fetch();

    return (int) ($row['total_attempts'] ?? 0) >= EMAIL_VERIFICATION_MAX_ATTEMPTS_PER_WINDOW;
}
