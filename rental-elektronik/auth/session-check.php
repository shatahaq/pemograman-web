<?php
/**
 * ElektraRent — Session Check (JSON API)
 */

require_once __DIR__ . '/../includes/helpers.php';

header('Content-Type: application/json; charset=utf-8');

if (isLoggedIn()) {
    echo json_encode([
        'logged_in' => true,
        'user'      => currentUser(),
    ]);
} else {
    echo json_encode([
        'logged_in' => false,
        'user'      => null,
    ]);
}
