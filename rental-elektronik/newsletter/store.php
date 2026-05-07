<?php
/**
 * ElektraRent — Newsletter Store (POST)
 */

require_once __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(getBasePath() . '/index.php');
}

requireCsrf();

$email = strtolower(trim($_POST['email_subscriber'] ?? ''));

if (empty($email) || !isValidEmail($email)) {
    setFlash('newsletter', 'Format email tidak valid.', 'danger');
    redirect(getBasePath() . '/index.php#newsletter');
}

// Limit email length to prevent abuse
if (strlen($email) > 255) {
    setFlash('newsletter', 'Email terlalu panjang.', 'danger');
    redirect(getBasePath() . '/index.php#newsletter');
}

$db = getDB();

// Rate limit: max 10 newsletter signups per IP per hour
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$stmtRate = $db->prepare("
    SELECT COUNT(*) AS cnt FROM audit_logs
    WHERE aksi = 'newsletter_subscribe'
      AND ip_address = ?
      AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
");
$stmtRate->execute([$ip]);
$rateRow = $stmtRate->fetch();
if (($rateRow['cnt'] ?? 0) >= 10) {
    setFlash('newsletter', 'Terlalu banyak permintaan. Coba lagi nanti.', 'danger');
    redirect(getBasePath() . '/index.php#newsletter');
}

// Cek apakah sudah terdaftar
$stmt = $db->prepare("SELECT id FROM newsletter_subscribers WHERE email = ? LIMIT 1");
$stmt->execute([$email]);

if ($stmt->fetch()) {
    setFlash('newsletter', 'Email ini sudah terdaftar sebagai subscriber.', 'danger');
    redirect(getBasePath() . '/index.php#newsletter');
}

$stmt = $db->prepare("INSERT INTO newsletter_subscribers (email, status, created_at) VALUES (?, 'aktif', NOW())");
$stmt->execute([$email]);

auditLog('newsletter_subscribe', 'newsletter_subscribers', (int) $db->lastInsertId(), ['email' => $email]);
setFlash('newsletter', 'Terima kasih! Email berhasil didaftarkan.', 'success');
redirect(getBasePath() . '/index.php#newsletter');
