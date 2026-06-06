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
require_once __DIR__ . '/../../app/api/ApiOpenApi.php';

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
        'docs'    => '/api/v1/docs (interactive Swagger UI)',
        'openapi' => '/api/v1/openapi.json',
    ]);
    exit;
}

// ── Public docs endpoints (no auth) ────────────────────────────────────────
if ($path === 'openapi.json' || $path === 'openapi') {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $serverUrl = "{$scheme}://{$host}/api/v1";
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    echo json_encode(ApiOpenApi::spec($serverUrl), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($path === 'docs') {
    header('Content-Type: text/html; charset=utf-8');
    echo <<<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Load Monitor API — Documentation</title>
    <link rel="icon" type="image/x-icon" href="/assets/img/favicon.ico">
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui.css">
    <style>
        body { margin: 0; background: #fafafa; }
        .topbar { background: #004B23 !important; padding: 10px 20px !important; }
        .topbar-wrapper .link { color: #fff !important; font-weight: 700; }
        .topbar-wrapper .link:after { content: ' — Load Monitor API'; font-weight: 400; opacity: 0.8; }
        .swagger-ui .info .title { color: #004B23; }
        .swagger-ui .btn.authorize { background: #004B23; border-color: #004B23; color: #fff; }
        .swagger-ui .btn.authorize svg { fill: #fff; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = () => {
            window.ui = SwaggerUIBundle({
                url: '/api/v1/openapi.json',
                dom_id: '#swagger-ui',
                deepLinking: true,
                docExpansion: 'list',
                defaultModelsExpandDepth: 1,
                tryItOutEnabled: true,
                persistAuthorization: true,
                presets: [SwaggerUIBundle.presets.apis, SwaggerUIStandalonePreset],
                plugins: [SwaggerUIBundle.plugins.DownloadUrl],
                layout: 'StandaloneLayout',
            });
        };
    </script>
</body>
</html>
HTML;
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
