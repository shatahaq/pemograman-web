<?php
/**
 * ElektraRent — General Helpers
 */

/**
 * Load environment variable.
 */
function env(string $key, mixed $default = null): mixed
{
    static $fileValues = null;
    if ($fileValues === null) {
        $fileValues = [];
        $envPath = __DIR__ . '/../.env';

        if (is_file($envPath) && is_readable($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                    continue;
                }

                [$envKey, $envValue] = explode('=', $line, 2);
                $envKey = trim($envKey);
                $envValue = trim($envValue);
                $envValue = trim($envValue, "\"'");

                if ($envKey !== '') {
                    $fileValues[$envKey] = $envValue;
                }
            }
        }
    }

    return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: ($fileValues[$key] ?? $default);
}

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/validation.php';

/**
 * Redirect ke URL.
 */
function redirect(string $url): void
{
    header("Location: $url");
    exit;
}

/**
 * Generate kode pesanan unik.
 */
function generateOrderCode(): string
{
    $db = getDB();
    $stmt = $db->query("SELECT MAX(id) AS max_id FROM rental_orders");
    $row = $stmt->fetch();
    $nextId = ($row['max_id'] ?? 0) + 1;
    return 'ORD-' . str_pad($nextId, 4, '0', STR_PAD_LEFT);
}

/**
 * Generate kode produk unik.
 */
function generateProductCode(string $kategoriSlug): string
{
    $prefix = match ($kategoriSlug) {
        'kamera'    => 'PRD-CAM-',
        'laptop'    => 'PRD-LTP-',
        'proyektor' => 'PRD-PRY-',
        'drone'     => 'PRD-DRN-',
        'audio'     => 'PRD-AUD-',
        default     => 'PRD-GEN-',
    };

    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) AS cnt FROM products WHERE kode_produk LIKE ?");
    $stmt->execute([$prefix . '%']);
    $row = $stmt->fetch();
    $next = ($row['cnt'] ?? 0) + 1;

    return $prefix . str_pad($next, 3, '0', STR_PAD_LEFT);
}

/**
 * Tulis audit log.
 */
function auditLog(string $aksi, ?string $tabelTarget = null, ?int $targetId = null, ?array $metadata = null): void
{
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO audit_logs (user_id, aksi, tabel_target, target_id, metadata_json, ip_address, user_agent, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    $stmt->execute([
        $_SESSION['user_id'] ?? null,
        $aksi,
        $tabelTarget,
        $targetId,
        $metadata ? json_encode($metadata) : null,
        $_SERVER['REMOTE_ADDR'] ?? null,
        substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
    ]);
}

/**
 * Cek rate limit login.
 */
function isLoginRateLimited(string $email): bool
{
    $db = getDB();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $windowStart = date('Y-m-d H:i:s', strtotime('-' . LOGIN_WINDOW_MINUTES . ' minutes'));

    $stmt = $db->prepare("
        SELECT COUNT(*) AS attempts
        FROM login_attempts
        WHERE (email = ? OR ip_address = ?)
          AND attempted_at > ?
    ");
    $stmt->execute([$email, $ip, $windowStart]);
    $row = $stmt->fetch();

    return ($row['attempts'] ?? 0) >= LOGIN_MAX_ATTEMPTS;
}

/**
 * Catat percobaan login gagal.
 */
function recordLoginAttempt(string $email): void
{
    $db = getDB();
    $stmt = $db->prepare("
        INSERT INTO login_attempts (email, ip_address, attempted_at)
        VALUES (?, ?, NOW())
    ");
    $stmt->execute([$email, $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0']);
}

/**
 * Hapus login attempts setelah login berhasil.
 */
function clearLoginAttempts(string $email): void
{
    $db = getDB();
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $stmt = $db->prepare("DELETE FROM login_attempts WHERE email = ? OR ip_address = ?");
    $stmt->execute([$email, $ip]);
}

/**
 * Mendapatkan status badge CSS class.
 */
function statusBadgeClass(string $status): string
{
    return match ($status) {
        'aktif', 'tersedia'  => 'status-active',
        'lunas'              => 'status-paid',
        'pending'            => 'status-pending',
        'selesai'            => 'status-done',
        'dibatalkan', 'habis', 'nonaktif' => 'status-cancelled',
        'pending_verification' => 'status-pending',
        'gagal'              => 'status-danger',
        'maintenance'        => 'status-maintenance',
        default              => 'status-pending',
    };
}

/**
 * Mendapatkan label status yang readable.
 */
function statusLabel(string $status): string
{
    return match ($status) {
        'aktif'      => 'Aktif',
        'pending'    => 'Pending',
        'selesai'    => 'Selesai',
        'dibatalkan' => 'Dibatalkan',
        'tersedia'   => 'Tersedia',
        'habis'      => 'Stok Habis',
        'maintenance'=> 'Maintenance',
        'nonaktif'   => 'Nonaktif',
        'pending_verification' => 'Menunggu Verifikasi',
        'lunas'      => 'Lunas',
        'gagal'      => 'Gagal',
        default      => ucfirst($status),
    };
}

/**
 * Resolve product image URL — handle lokal file dan external URL.
 */
function productImageSrc(?string $imageUrl, string $basePath = ''): string
{
    if (empty($imageUrl)) {
        return 'https://placehold.co/300x200/1a2332/e8dcc8?text=No+Image';
    }
    if (str_starts_with($imageUrl, 'http')) {
        return $imageUrl;
    }
    $prefix = $basePath ?: (defined('BASE_PATH') ? BASE_PATH : '.');
    return $prefix . '/assets/images/products/' . $imageUrl;
}
