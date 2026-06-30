<?php
/**
 * File: app/bootstrap.php
 */

// Defence-in-depth: start output buffering so that any stray whitespace or
// BOM before a <?php tag in an included file can't prematurely commit the
// response and break header() redirects (e.g. the "white screen on first
// login" symptom).  The buffer is implicitly flushed at script end.
if (!ob_get_level()) {
    ob_start();
}

// ── Timezone (must be first — affects every date() / DateTime call) ──────────
date_default_timezone_set('Africa/Lagos');

// ── Production error handling ──────────────────────────────────────────────
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
error_reporting(E_ALL);

// Surface uncaught exceptions / fatals so users don't see a blank white page.
set_exception_handler(function (Throwable $e) {
    error_log('UNCAUGHT: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    echo '<!DOCTYPE html><html><body style="font-family:sans-serif;max-width:700px;margin:60px auto;padding:24px;background:#fee2e2;border-radius:8px;border-left:4px solid #dc2626">'
       . '<h2 style="color:#991b1b;margin:0 0 12px">Application error</h2>'
       . '<p style="color:#374151">The page could not be loaded. The error has been logged.</p>'
       . '<p style="color:#6b7280;font-size:13px">Detail: ' . htmlspecialchars($e->getMessage()) . '</p>'
       . '<p><a href="' . (defined('BASE_PATH') ? BASE_PATH : '') . '/index.php?page=login" style="color:#1e40af">Return to login</a></p>'
       . '</body></html>';
});

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
require_once __DIR__ . '/models/FaultCodes.php';