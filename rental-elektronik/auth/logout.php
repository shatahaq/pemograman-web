<?php
/**
 * ElektraRent — Logout Handler
 */

require_once __DIR__ . '/../includes/helpers.php';

auditLog('logout', 'users', $_SESSION['user_id'] ?? null);
logoutUser();
setFlash('login', 'Anda telah keluar dari sistem.', 'success');
redirect(getBasePath() . '/login.php');
