<?php
/**
 * Bearer-token authentication.
 *
 *   Authorization: Bearer <token>
 *
 * The raw token is sha256-hashed and looked up in api_clients.key_hash.
 * Returns the client row on success, or null on failure.
 */
class ApiAuth
{
    public static function clientFromRequest(): ?array
    {
        $auth = $_SERVER['HTTP_AUTHORIZATION']
             ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
             ?? '';
        if ($auth === '' && function_exists('getallheaders')) {
            $headers = array_change_key_case(getallheaders() ?: [], CASE_LOWER);
            $auth = $headers['authorization'] ?? '';
        }
        if (!preg_match('/^Bearer\s+([A-Za-z0-9._\-]{20,128})$/', $auth, $m)) {
            return null;
        }

        $token = $m[1];
        $hash  = hash('sha256', $token);

        $db   = Database::connect();
        $stmt = $db->prepare("
            SELECT id, name, key_prefix, scopes, is_active
            FROM api_clients
            WHERE key_hash = ?
            LIMIT 1
        ");
        $stmt->execute([$hash]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$client || (int)$client['is_active'] !== 1) {
            return null;
        }

        // Update last_used_at — best-effort, ignore errors.
        try {
            $db->prepare("UPDATE api_clients SET last_used_at = CURRENT_TIMESTAMP WHERE id = ?")
               ->execute([$client['id']]);
        } catch (Throwable $e) {
            error_log('ApiAuth touch last_used_at failed: ' . $e->getMessage());
        }

        return $client;
    }
}
