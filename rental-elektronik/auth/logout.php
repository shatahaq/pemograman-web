<?php
/**
 * ElektraRent — Logout Handler
 */

require_once __DIR__ . '/../includes/helpers.php';

// Accept both GET (for simple link logouts) and POST
// For GET requests, perform logout directly (acceptable trade-off for UX)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireCsrf();
}

auditLog('logout', 'users', $_SESSION['user_id'] ?? null);
logoutUser();
setFlash('login', 'Anda telah keluar dari sistem.', 'success');
redirect(getBasePath() . '/login.php');
