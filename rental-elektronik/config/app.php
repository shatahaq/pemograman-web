<?php
/**
 * ElektraRent — Application Configuration
 */

define('APP_NAME', (string) env('APP_NAME', 'ElektraRent'));
define('APP_URL', (string) env('APP_URL', 'http://localhost/PW-2/projek/rental-elektronik'));
define('APP_ENV', (string) env('APP_ENV', 'production'));

// Batas rate limit login (max attempts dalam window)
define('LOGIN_MAX_ATTEMPTS', (int) env('LOGIN_MAX_ATTEMPTS', 5));
define('LOGIN_WINDOW_MINUTES', (int) env('LOGIN_WINDOW_MINUTES', 15));

// Session lifetime
define('SESSION_LIFETIME', (int) env('SESSION_LIFETIME', 7200)); // 2 jam

// CSRF token name
define('CSRF_TOKEN_NAME', (string) env('CSRF_TOKEN_NAME', '_csrf_token'));

// Timezone
date_default_timezone_set((string) env('APP_TIMEZONE', 'Asia/Jakarta'));
