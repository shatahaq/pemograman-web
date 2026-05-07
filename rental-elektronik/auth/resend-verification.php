<?php
/**
 * ElektraRent - Resend email verification OTP handler
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/email_verification.php';
require_once __DIR__ . '/../includes/mailer.php';

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

$latestVerification = latestOpenEmailVerification($db, (int) $pendingUser['id']);
$waitSeconds = secondsUntilVerificationResend($latestVerification);

if ($waitSeconds > 0) {
    setFlash('verify_email', 'Tunggu ' . $waitSeconds . ' detik sebelum meminta kode baru.', 'danger');
    redirect(getBasePath() . '/verify-email.php');
}

if (isVerificationResendRateLimited($db, (int) $pendingUser['id'])) {
    setFlash('verify_email', 'Permintaan kode terlalu sering. Silakan coba lagi beberapa menit lagi.', 'danger');
    redirect(getBasePath() . '/verify-email.php');
}

$verificationCode = generateEmailVerificationCode();

try {
    $db->beginTransaction();

    createEmailVerification($db, (int) $pendingUser['id'], $verificationCode);
    auditLog('email_verification_resent', 'users', (int) $pendingUser['id']);

    $db->commit();
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    error_log('Resend verification code failed: ' . $exception->getMessage());
    setFlash('verify_email', 'Kode baru belum dapat dibuat. Silakan coba beberapa saat lagi.', 'danger');
    redirect(getBasePath() . '/verify-email.php');
}

if (sendVerificationEmail($pendingUser, $verificationCode)) {
    setFlash('verify_email', 'Kode verifikasi baru sudah dikirim ke email Anda.', 'success');
} else {
    setFlash('verify_email', 'Kode baru dibuat, tetapi email belum terkirim. Periksa konfigurasi SMTP aplikasi.', 'danger');
}

redirect(getBasePath() . '/verify-email.php');
