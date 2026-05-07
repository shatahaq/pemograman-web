<?php
/**
 * ElektraRent - Mail Configuration
 *
 * Values are read from environment variables. Do not commit real SMTP credentials.
 */

define('MAIL_HOST', (string) env('MAIL_HOST', ''));
define('MAIL_PORT', (int) env('MAIL_PORT', 587));
define('MAIL_USERNAME', (string) env('MAIL_USERNAME', ''));
define('MAIL_PASSWORD', (string) env('MAIL_PASSWORD', ''));
define('MAIL_ENCRYPTION', strtolower((string) env('MAIL_ENCRYPTION', 'tls')));
define('MAIL_FROM_ADDRESS', (string) env('MAIL_FROM_ADDRESS', 'no-reply@elektrarent.test'));
define('MAIL_FROM_NAME', (string) env('MAIL_FROM_NAME', 'ElektraRent'));
