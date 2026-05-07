<?php
/**
 * ElektraRent - Authentication Helpers
 */

require_once __DIR__ . '/session.php';

define('REMEMBER_COOKIE_NAME', 'elektrarent_remember');
define('REMEMBER_COOKIE_DAYS', 30);

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin(): bool
{
    return isLoggedIn() && ($_SESSION['user_role'] ?? '') === 'admin';
}

function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }

    return [
        'id' => $_SESSION['user_id'],
        'nama_lengkap' => $_SESSION['user_nama'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'user',
    ];
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        checkRememberCookie();
    }

    if (!isLoggedIn()) {
        $base = getBasePath();
        setFlash('auth', 'Silakan login terlebih dahulu.', 'danger');
        header("Location: {$base}/login.php");
        exit;
    }
}

function requireAdmin(): void
{
    requireLogin();

    if (!isAdmin()) {
        $base = getBasePath();
        setFlash('auth', 'Akses ditolak. Hanya admin yang diizinkan.', 'danger');
        header("Location: {$base}/login.php");
        exit;
    }
}

function loginUser(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_nama'] = $user['nama_lengkap'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['logged_in_at'] = date('Y-m-d H:i:s');
}

function logoutUser(): void
{
    clearRememberCookie();
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            [
                'expires' => time() - 42000,
                'path' => $params['path'] ?? '/',
                'domain' => $params['domain'] ?? '',
                'secure' => (bool) ($params['secure'] ?? false),
                'httponly' => (bool) ($params['httponly'] ?? true),
                'samesite' => $params['samesite'] ?? 'Lax',
            ]
        );
    }

    session_destroy();
}

function setRememberCookie(int $userId): void
{
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $token = bin2hex(random_bytes(32));
    $tokenHash = hash('sha256', $token);
    $expiresAt = date('Y-m-d H:i:s', time() + (REMEMBER_COOKIE_DAYS * 86400));

    $db = getDB();
    $db->prepare("DELETE FROM remember_tokens WHERE user_id = ? AND expires_at < NOW()")->execute([$userId]);

    $stmt = $db->prepare("
        INSERT INTO remember_tokens (user_id, token_hash, expires_at, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([$userId, $tokenHash, $expiresAt]);

    setcookie(
        REMEMBER_COOKIE_NAME,
        $userId . ':' . $token,
        [
            'expires' => time() + (REMEMBER_COOKIE_DAYS * 86400),
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );
}

function clearRememberCookie(): void
{
    $isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    if (isset($_COOKIE[REMEMBER_COOKIE_NAME])) {
        $cookieValue = $_COOKIE[REMEMBER_COOKIE_NAME];
        $parts = explode(':', $cookieValue, 2);

        if (count($parts) === 2) {
            $userId = (int) $parts[0];
            $tokenHash = hash('sha256', $parts[1]);

            try {
                $db = getDB();
                $db->prepare("DELETE FROM remember_tokens WHERE user_id = ? AND token_hash = ?")
                    ->execute([$userId, $tokenHash]);
            } catch (Throwable $exception) {
                error_log('Failed to clear remember token: ' . $exception->getMessage());
            }
        }
    }

    setcookie(
        REMEMBER_COOKIE_NAME,
        '',
        [
            'expires' => time() - 86400,
            'path' => '/',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]
    );
}

function checkRememberCookie(): bool
{
    if (isLoggedIn()) {
        return true;
    }

    if (!isset($_COOKIE[REMEMBER_COOKIE_NAME])) {
        return false;
    }

    $parts = explode(':', $_COOKIE[REMEMBER_COOKIE_NAME], 2);
    if (count($parts) !== 2) {
        clearRememberCookie();
        return false;
    }

    $userId = (int) $parts[0];
    $tokenHash = hash('sha256', $parts[1]);

    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT rt.id AS token_id, u.*
            FROM remember_tokens rt
            JOIN users u ON u.id = rt.user_id
            WHERE rt.user_id = ?
              AND rt.token_hash = ?
              AND rt.expires_at > NOW()
              AND u.status_akun = 'aktif'
              AND u.email_verified_at IS NOT NULL
            LIMIT 1
        ");
        $stmt->execute([$userId, $tokenHash]);
        $row = $stmt->fetch();

        if (!$row) {
            clearRememberCookie();
            return false;
        }

        loginUser($row);
        $db->prepare("DELETE FROM remember_tokens WHERE id = ?")->execute([$row['token_id']]);
        setRememberCookie($userId);
        auditLog('auto_login_cookie', 'users', $userId);

        return true;
    } catch (Throwable $exception) {
        error_log('Remember cookie restore failed: ' . $exception->getMessage());
        clearRememberCookie();
        return false;
    }
}

function getBasePath(): string
{
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $markers = ['/user/', '/admin/', '/auth/', '/newsletter/', '/api/'];

    foreach ($markers as $marker) {
        $pos = strpos($scriptName, $marker);
        if ($pos !== false) {
            return rtrim(substr($scriptName, 0, $pos), '/');
        }
    }

    return rtrim(dirname($scriptName), '/\\');
}
