<?php
/**
 * ElektraRent — Login Handler (POST)
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/email_verification.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(getBasePath() . '/login.php');
}

requireCsrf();

$email    = strtolower(trim($_POST['email'] ?? ''));
$password = $_POST['password'] ?? '';

// Validasi input
if (empty($email) || empty($password)) {
    setFlash('login', 'Email dan password wajib diisi.', 'danger');
    redirect(getBasePath() . '/login.php');
}

if (!isValidEmail($email)) {
    setFlash('login', 'Format email tidak valid.', 'danger');
    redirect(getBasePath() . '/login.php');
}

// Rate limit
if (isLoginRateLimited($email)) {
    setFlash('login', 'Terlalu banyak percobaan login. Silakan tunggu ' . LOGIN_WINDOW_MINUTES . ' menit.', 'danger');
    redirect(getBasePath() . '/login.php');
}

// Cari user
$db   = getDB();
$stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    recordLoginAttempt($email);
    setFlash('login', 'Email atau password salah.', 'danger');
    redirect(getBasePath() . '/login.php');
}

// Akun baru wajib verifikasi email sebelum login penuh.
if ($user['status_akun'] === 'pending_verification') {
    clearLoginAttempts($email);
    rememberPendingVerificationUser($user);
    setFlash('verify_email', 'Akun Anda belum aktif. Masukkan kode verifikasi yang dikirim ke email Anda.', 'danger');
    redirect(getBasePath() . '/verify-email.php');
}

if ($user['status_akun'] === 'aktif' && empty($user['email_verified_at'])) {
    clearLoginAttempts($email);
    rememberPendingVerificationUser($user);
    setFlash('verify_email', 'Email akun Anda belum terverifikasi. Silakan lanjutkan proses verifikasi.', 'danger');
    redirect(getBasePath() . '/verify-email.php');
}

// Cek status akun
if ($user['status_akun'] !== 'aktif') {
    setFlash('login', 'Akun Anda dinonaktifkan. Hubungi admin.', 'danger');
    redirect(getBasePath() . '/login.php');
}

// Login berhasil
clearLoginAttempts($email);
loginUser($user);
auditLog('login', 'users', $user['id']);

// Handle "Ingat Saya" cookie
if (!empty($_POST['remember_me'])) {
    setRememberCookie((int) $user['id']);
}

// Redirect berdasarkan role
if ($user['role'] === 'admin') {
    redirect(getBasePath() . '/admin/dashboard.php');
} else {
    redirect(getBasePath() . '/user/dashboard.php');
}
