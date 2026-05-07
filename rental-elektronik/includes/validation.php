<?php
/**
 * ElektraRent — Input Validation Utilities
 */

/**
 * Sanitasi string input.
 */
function sanitize(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

/**
 * Validasi email format.
 */
function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validasi nomor telepon Indonesia (minimal 10 digit).
 */
function isValidPhone(string $phone): bool
{
    return (bool) preg_match('/^[0-9]{10,15}$/', $phone);
}

/**
 * Validasi tanggal format Y-m-d.
 */
function isValidDate(string $date): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Escape output HTML.
 */
function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Format harga ke Rupiah.
 */
function formatRupiah(float|int $value): string
{
    return 'Rp ' . number_format($value, 0, ',', '.');
}

/**
 * Format tanggal ke format Indonesia.
 */
function formatTanggal(string $date): string
{
    $bulan = [
        1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
        5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Agu',
        9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des',
    ];

    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return $date;
    }

    $day   = date('d', $timestamp);
    $month = (int) date('n', $timestamp);
    $year  = date('Y', $timestamp);

    return "$day {$bulan[$month]} $year";
}
