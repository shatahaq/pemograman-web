<?php
/**
 * ElektraRent — CSRF Protection
 */

require_once __DIR__ . '/session.php';

/**
 * Generate atau ambil CSRF token.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render hidden input CSRF token.
 */
function csrfField(): string
{
    $token = htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="_csrf_token" value="' . $token . '">';
}

/**
 * Validasi CSRF token dari POST request.
 */
function validateCsrf(): bool
{
    $token = $_POST['_csrf_token'] ?? '';
    $sessionToken = $_SESSION['csrf_token'] ?? '';

    if (empty($token) || empty($sessionToken)) {
        return false;
    }

    return hash_equals($sessionToken, $token);
}

/**
 * Validasi CSRF dan abort jika gagal.
 */
function requireCsrf(): void
{
    if (!validateCsrf()) {
        http_response_code(403);
        die('CSRF token tidak valid. Silakan muat ulang halaman dan coba lagi.');
    }
}
