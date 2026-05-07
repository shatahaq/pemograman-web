<?php
/**
 * ElektraRent — Session Check (JSON API)
 */

require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (isLoggedIn()) {
    echo json_encode([
        'logged_in' => true,
        'user'      => currentUser(),
    ], JSON_HEX_TAG | JSON_HEX_AMP);
} else {
    echo json_encode([
        'logged_in' => false,
        'user'      => null,
    ], JSON_HEX_TAG | JSON_HEX_AMP);
}
