<?php
/**
 * ElektraRent - Verify email OTP handler
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/email_verification.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(getBasePath() . '/verify-email.php');
}

requireCsrf();

$db = getDB();
$pendingUserId = (int) ($_SESSION['pending_verification_user_id'] ?? 0);
$pendingUser = $pendingUserId > 0 ? getPendingVerificationUser($db, $pendingUserId) : null;

if (!$pendingUser) {
    clearPendingVerificationUser();
    setFlash('login', 'Sesi verifikasi tidak tersedia. Silakan login untuk melanjutkan.', 'danger');
    redirect(getBasePath() . '/login.php');
}

$code = trim($_POST['verification_code'] ?? '');

if (!preg_match('/^[0-9]{6}$/', $code)) {
    setFlash('verify_email', 'Kode verifikasi harus berisi 6 digit angka.', 'danger');
    redirect(getBasePath() . '/verify-email.php');
}

$verification = latestOpenEmailVerification($db, (int) $pendingUser['id']);

if (!$verification) {
    setFlash('verify_email', 'Kode verifikasi tidak tersedia. Silakan kirim ulang kode.', 'danger');
    redirect(getBasePath() . '/verify-email.php');
}

if (isVerificationAttemptRateLimited($db, (int) $pendingUser['id'])) {
    setFlash('verify_email', 'Percobaan verifikasi terlalu sering. Silakan tunggu beberapa menit sebelum mencoba lagi.', 'danger');
    redirect(getBasePath() . '/verify-email.php');
}

if ((int) $verification['attempts'] >= EMAIL_VERIFICATION_MAX_ATTEMPTS) {
    setFlash('verify_email', 'Batas percobaan kode tercapai. Silakan kirim ulang kode baru.', 'danger');
    redirect(getBasePath() . '/verify-email.php');
}

$expiresAt = strtotime((string) $verification['expires_at']);
if ($expiresAt === false || $expiresAt < time()) {
    setFlash('verify_email', 'Kode verifikasi sudah kedaluwarsa. Silakan kirim ulang kode.', 'danger');
    redirect(getBasePath() . '/verify-email.php');
}

if (!password_verify($code, (string) $verification['code_hash'])) {
    $stmt = $db->prepare("
        UPDATE email_verifications
        SET attempts = attempts + 1,
            ip_address = ?,
            user_agent = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([
        verificationClientIp(),
        verificationUserAgent(),
        (int) $verification['id'],
    ]);

    $remainingAttempts = max(0, EMAIL_VERIFICATION_MAX_ATTEMPTS - ((int) $verification['attempts'] + 1));
    auditLog('email_verification_failed', 'users', (int) $pendingUser['id'], ['remaining_attempts' => $remainingAttempts]);
    setFlash('verify_email', 'Kode verifikasi tidak sesuai. Sisa percobaan: ' . $remainingAttempts . '.', 'danger');
    redirect(getBasePath() . '/verify-email.php');
}

try {
    $db->beginTransaction();

    $stmt = $db->prepare("
        UPDATE email_verifications
        SET verified_at = NOW(),
            updated_at = NOW()
        WHERE id = ?
          AND user_id = ?
          AND verified_at IS NULL
    ");
    $stmt->execute([(int) $verification['id'], (int) $pendingUser['id']]);

    $stmt = $db->prepare("
        UPDATE users
        SET email_verified_at = NOW(),
            status_akun = 'aktif',
            updated_at = NOW()
        WHERE id = ?
          AND status_akun = 'pending_verification'
    ");
    $stmt->execute([(int) $pendingUser['id']]);

    auditLog('email_verified', 'users', (int) $pendingUser['id']);

    $db->commit();
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    error_log('Email verification failed: ' . $exception->getMessage());
    setFlash('verify_email', 'Verifikasi belum dapat diproses. Silakan coba beberapa saat lagi.', 'danger');
    redirect(getBasePath() . '/verify-email.php');
}

$stmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([(int) $pendingUser['id']]);
$verifiedUser = $stmt->fetch();

clearPendingVerificationUser();

if ($verifiedUser) {
    loginUser($verifiedUser);
    setFlash('dashboard', 'Email berhasil diverifikasi. Selamat datang di ElektraRent.', 'success');
    redirect(getBasePath() . '/user/dashboard.php');
}

setFlash('login', 'Email berhasil diverifikasi. Silakan login untuk melanjutkan.', 'success');
redirect(getBasePath() . '/login.php');
