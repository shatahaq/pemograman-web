<?php
/**
 * ElektraRent — Newsletter Store (POST)
 */

require_once __DIR__ . '/../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(getBasePath() . '/index.php');
}

requireCsrf();

$email = trim($_POST['email_subscriber'] ?? '');

if (empty($email) || !isValidEmail($email)) {
    setFlash('newsletter', 'Format email tidak valid.', 'danger');
    redirect(getBasePath() . '/index.php#newsletter');
}

$db = getDB();

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
