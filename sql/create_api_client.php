<?php
/**
 * Mint a new API client.
 *
 * Usage (inside the PHP container):
 *   php /var/www/html/sql/create_api_client.php "Dashboard Production"
 *
 * Output:  prints the bearer token ONCE.  You cannot retrieve it later —
 *          only its hash is stored.  Treat it like a password.
 */

require_once __DIR__ . '/../app/bootstrap.php';

$name = $argv[1] ?? '';
if ($name === '') {
    fwrite(STDERR, "Usage: php create_api_client.php \"Client name\"\n");
    exit(1);
}

// 40-char URL-safe token = 240 bits of entropy.
$token  = bin2hex(random_bytes(20));     // 40 hex chars
$hash   = hash('sha256', $token);
$prefix = substr($token, 0, 8);

$db = Database::connect();
$db->prepare("
    INSERT INTO api_clients (name, key_hash, key_prefix, scopes, is_active)
    VALUES (?, ?, ?, 'read', 1)
")->execute([$name, $hash, $prefix]);

$id = $db->lastInsertId();

echo "✅ API client created\n";
echo "   ID:       {$id}\n";
echo "   Name:     {$name}\n";
echo "   Prefix:   {$prefix}\n\n";
echo "🔑 Bearer token (save this — it is shown ONCE):\n\n";
echo "    {$token}\n\n";
echo "Use it like this:\n";
echo "    curl -H 'Authorization: Bearer {$token}' https://loadreading.kadunaelectric.cloud/api/v1/health\n";
