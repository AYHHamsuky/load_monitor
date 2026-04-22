
<?php
/**
 * File: app/bootstrap.php
 */

// ── Production error handling ──────────────────────────────────────────────
ini_set('display_errors', 0);          // Never expose errors to the browser
ini_set('log_errors', 1);             // Log errors to file instead
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
error_reporting(E_ALL);

// Critical: Set session settings BEFORE session_start()
ini_set('session.cookie_httponly', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.cookie_lifetime', '0');

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',  // Empty for localhost compatibility
    'secure'   => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Base path: auto-detect based on port ────────────────────────────────────
// Port 5006 VirtualHost has DocumentRoot = public/, so base path is empty.
// Port 80 uses Alias /load_monitor/public, so base path must include it.
if (!defined('BASE_PATH')) {
    $port = $_SERVER['SERVER_PORT'] ?? '5006';
    define('BASE_PATH', $port === '5006' ? '' : '/load_monitor/public');
}

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Guard.php';
require_once __DIR__ . '/models/LateEntryLog.php';
require_once __DIR__ . '/models/MytoAllocation.php';