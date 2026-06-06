<?php
/**
 * JSON envelope helpers.  Every API response goes through one of these.
 *
 * Success:
 *   { "success": true, "data": ..., "meta": { ... } }
 *
 * Error:
 *   { "success": false, "error": { "code": "...", "message": "..." } }
 */
class ApiResponse
{
    public static function ok(mixed $data, array $meta = [], int $http = 200): void
    {
        self::send($http, [
            'success' => true,
            'data'    => $data,
            'meta'    => array_merge([
                'generated_at' => date('c'),
            ], $meta),
        ]);
    }

    public static function error(string $code, string $message, int $http = 400, array $extra = []): void
    {
        self::send($http, [
            'success' => false,
            'error'   => array_merge([
                'code'    => $code,
                'message' => $message,
            ], $extra),
        ]);
    }

    private static function send(int $http, array $body): void
    {
        if (!headers_sent()) {
            http_response_code($http);
            header('Content-Type: application/json; charset=utf-8');
            // Generous CORS for now — restrict to specific origins in prod if needed.
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, OPTIONS');
            header('Access-Control-Allow-Headers: Authorization, Content-Type');
            header('X-Content-Type-Options: nosniff');
        }
        echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
