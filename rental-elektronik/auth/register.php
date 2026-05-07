<?php
/**
 * ElektraRent — Register Handler (POST)
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/email_verification.php';
require_once __DIR__ . '/../includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(getBasePath() . '/register.php');
}

requireCsrf();

$namaLengkap       = trim($_POST['nama_lengkap'] ?? '');
$email              = strtolower(trim($_POST['email'] ?? ''));
$noTelepon          = trim($_POST['no_telepon'] ?? '');
$alamat             = trim($_POST['alamat'] ?? '');
$password           = $_POST['password'] ?? '';
$konfirmasiPassword = $_POST['konfirmasi_password'] ?? '';

$errors = [];

if (empty($namaLengkap)) {
    $errors[] = 'Nama lengkap wajib diisi.';
}
if (empty($email) || !isValidEmail($email)) {
    $errors[] = 'Email tidak valid.';
}
if (empty($noTelepon) || !isValidPhone($noTelepon)) {
    $errors[] = 'Nomor telepon tidak valid (minimal 10 digit angka).';
}
if (empty($alamat)) {
    $errors[] = 'Alamat wajib diisi.';
}
if (strlen($password) < 8) {
    $errors[] = 'Password minimal 8 karakter.';
}
if ($password !== $konfirmasiPassword) {
    $errors[] = 'Konfirmasi password tidak sama.';
}

if (!empty($errors)) {
    setFlash('register', implode(' ', $errors), 'danger');
    redirect(getBasePath() . '/register.php');
}

// Cek email unik
$db = getDB();
$stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);

if ($stmt->fetch()) {
    setFlash('register', 'Email sudah terdaftar. Silakan gunakan email lain.', 'danger');
    redirect(getBasePath() . '/register.php');
}

// Insert user sebagai pending verification, lalu kirim OTP ke email.
$verificationCode = generateEmailVerificationCode();
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

try {
    $db->beginTransaction();

    $stmt = $db->prepare("
        INSERT INTO users
            (nama_lengkap, email, no_telepon, alamat, password_hash, role, status_akun, email_verified_at, created_at, updated_at)
        VALUES
            (?, ?, ?, ?, ?, 'user', 'pending_verification', NULL, NOW(), NOW())
    ");
    $stmt->execute([$namaLengkap, $email, $noTelepon, $alamat, $passwordHash]);

    $userId = (int) $db->lastInsertId();
    createEmailVerification($db, $userId, $verificationCode);
    auditLog('register_pending_verification', 'users', $userId);

    $db->commit();
} catch (Throwable $exception) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }

    error_log('Registration failed: ' . $exception->getMessage());
    setFlash('register', 'Registrasi belum dapat diproses. Silakan coba beberapa saat lagi.', 'danger');
    redirect(getBasePath() . '/register.php');
}

$newUser = [
    'id' => $userId,
    'nama_lengkap' => $namaLengkap,
    'email' => $email,
];

rememberPendingVerificationUser($newUser);

if (sendVerificationEmail($newUser, $verificationCode)) {
    setFlash('verify_email', 'Registrasi berhasil. Masukkan kode verifikasi yang sudah dikirim ke email Anda.', 'success');
} else {
    setFlash('verify_email', 'Akun dibuat, tetapi email verifikasi belum terkirim. Periksa konfigurasi SMTP lalu gunakan tombol kirim ulang kode.', 'danger');
}

redirect(getBasePath() . '/verify-email.php');
