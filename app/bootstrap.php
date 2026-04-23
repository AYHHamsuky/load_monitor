<?php
/**
 * File: app/bootstrap.php
 */

// ── Production error handling ──────────────────────────────────────────────
ini_set('display_errors', 0);
ini_set('log_errors', 1);
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

// ── Base path ────────────────────────────────────────────────────────────────
// Docker/container: set ENV APP_BASE_PATH=  (empty string) so the app serves
// from the domain root.  Legacy Apache on port 80 uses /load_monitor/public.
if (!defined('BASE_PATH')) {
    $envBase = getenv('APP_BASE_PATH');
    if ($envBase !== false) {
        define('BASE_PATH', $envBase);
    } else {
        $port = $_SERVER['SERVER_PORT'] ?? '5006';
        define('BASE_PATH', $port === '5006' ? '' : '/load_monitor/public');
    }
}

require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Guard.php';
require_once __DIR__ . '/models/LateEntryLog.php';
require_once __DIR__ . '/models/MytoAllocation.php';