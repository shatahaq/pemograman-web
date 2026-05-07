<?php
/**
 * ElektraRent - Secure Session Management
 */

require_once __DIR__ . '/../config/app.php';

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', $isHttps ? '1' : '0');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.gc_maxlifetime', (string) SESSION_LIFETIME);

    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();

    // Session fingerprint validation to detect session hijacking
    $fingerprint = hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|' . PHP_MAJOR_VERSION);
    if (isset($_SESSION['_fingerprint']) && !hash_equals($_SESSION['_fingerprint'], $fingerprint)) {
        // Possible session hijacking — destroy and restart
        $_SESSION = [];
        session_destroy();
        session_start();
    }
    $_SESSION['_fingerprint'] = $fingerprint;
}

// Security headers — applied on every request
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.tailwindcss.com https://unpkg.com; style-src 'self' 'unsafe-inline'; img-src 'self' data: https: blob:; font-src 'self' https:; frame-ancestors 'none';");

function setFlash(string $key, string $message, string $type = 'success'): void
{
    $_SESSION['flash'][$key] = [
        'message' => $message,
        'type' => $type,
    ];
}

function getFlash(string $key): ?array
{
    if (isset($_SESSION['flash'][$key])) {
        $flash = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $flash;
    }

    return null;
}

function hasFlash(string $key): bool
{
    return isset($_SESSION['flash'][$key]);
}
