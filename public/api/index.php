<?php
/**
 * Public read-only API entry point.
 *
 * Receives every /api/v1/* request, authenticates, dispatches,
 * logs, and returns a JSON envelope.
 */

require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/api/ApiResponse.php';
require_once __DIR__ . '/../../app/api/ApiAuth.php';
require_once __DIR__ . '/../../app/api/ApiLog.php';
require_once __DIR__ . '/../../app/api/ApiRouter.php';
require_once __DIR__ . '/../../app/api/ApiEndpoints.php';

// Override bootstrap's HTML exception handler with a JSON one for API requests.
set_exception_handler(function (Throwable $e): void {
    error_log('API uncaught: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
    ApiResponse::error('INTERNAL_ERROR', 'An unexpected error occurred.', 500);
});

$started = microtime(true);

// Method gate — only GET (and OPTIONS for CORS preflight)
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'OPTIONS') {
    ApiResponse::ok(null, [], 204);
    exit;
}
if ($method !== 'GET') {
    ApiResponse::error('METHOD_NOT_ALLOWED', 'This API is read-only — use GET.', 405);
    exit;
}

// Resolve the path.  We accept both rewritten (/api/v1/<path>) and
// raw (/api/index.php?_path=<path>) forms so the API works regardless of
// whether mod_rewrite is active.
$rawPath = $_GET['_path'] ?? '';
if ($rawPath === '') {
    $uri = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    if (preg_match('#/api/v1/(.*)$#', $uri, $m)) {
        $rawPath = $m[1];
    }
}
$path = trim($rawPath, '/');

if ($path === '' || $path === 'v1') {
    ApiResponse::ok([
        'name'    => 'Load Monitor API',
        'version' => 'v1',
        'paths'   => ApiRouter::knownPaths(),
        'docs'    => '/docs/API.md (see source repo)',
    ]);
    exit;
}

$route = ApiRouter::resolve($path);
if (!$route) {
    ApiResponse::error('NOT_FOUND', "Unknown endpoint: {$path}", 404,
        ['available' => ApiRouter::knownPaths()]);
    exit;
}

// Public endpoints (no auth)
$publicEndpoints = ['health'];

$clientId = null;
if (!in_array($route['endpoint'], $publicEndpoints, true)) {
    $client = ApiAuth::clientFromRequest();
    if (!$client) {
        ApiResponse::error('UNAUTHORIZED',
            'Missing or invalid Bearer token. Provide Authorization: Bearer <token>.',
            401);
        exit;
    }
    $clientId = (int)$client['id'];
    $GLOBALS['API_CLIENT'] = [
        'id'         => $clientId,
        'name'       => $client['name'],
        'key_prefix' => $client['key_prefix'],
        'scopes'     => $client['scopes'],
    ];
}

// Dispatch.  Handlers terminate the request themselves via ApiResponse.
register_shutdown_function(function () use ($started, $clientId, $method, $route) {
    $ms     = (int)((microtime(true) - $started) * 1000);
    $status = http_response_code() ?: 200;
    ApiLog::record(
        $clientId,
        $method,
        $route['endpoint'],
        $_SERVER['QUERY_STRING'] ?? '',
        $_SERVER['REMOTE_ADDR'] ?? null,
        $status,
        $ms
    );
});

$db = Database::connect();
call_user_func($route['handler'], $db, $_GET);
