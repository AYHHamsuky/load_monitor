<?php
/**
 * Persist one row per API call so admins can audit access.
 * Best-effort — failures here never break the response.
 */
class ApiLog
{
    public static function record(
        ?int   $clientId,
        string $method,
        string $endpoint,
        string $queryString,
        ?string $ip,
        int    $status,
        int    $responseMs
    ): void {
        try {
            $db = Database::connect();
            $db->prepare("
                INSERT INTO api_request_log
                    (client_id, method, endpoint, query_string, ip, status, response_ms)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ")->execute([
                $clientId,
                $method,
                $endpoint,
                $queryString !== '' ? $queryString : null,
                $ip,
                $status,
                $responseMs,
            ]);
        } catch (Throwable $e) {
            error_log('ApiLog::record failed: ' . $e->getMessage());
        }
    }
}
