<?php
/**
 * Creates the api_clients + api_request_log tables.  Idempotent —
 * safe to run repeatedly.  Driver-aware (works on SQLite and MySQL).
 *
 * Usage:
 *   php /var/www/html/sql/install_api_schema.php
 */

require_once __DIR__ . '/../app/bootstrap.php';

$db     = Database::connect();
$driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

if ($driver === 'mysql') {
    $db->exec("
        CREATE TABLE IF NOT EXISTS api_clients (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name          VARCHAR(120)  NOT NULL,
            key_hash      CHAR(64)      NOT NULL UNIQUE,
            key_prefix    VARCHAR(12)   NOT NULL,
            scopes        VARCHAR(255)  NOT NULL DEFAULT 'read',
            is_active     TINYINT(1)    NOT NULL DEFAULT 1,
            last_used_at  DATETIME      NULL,
            created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $db->exec("
        CREATE TABLE IF NOT EXISTS api_request_log (
            id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            client_id     INT UNSIGNED NULL,
            method        VARCHAR(10)  NOT NULL,
            endpoint      VARCHAR(120) NOT NULL,
            query_string  TEXT         NULL,
            ip            VARCHAR(64)  NULL,
            status        SMALLINT     NOT NULL,
            response_ms   INT          NULL,
            created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_api_log_client  (client_id, created_at),
            INDEX idx_api_log_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
} else {
    $db->exec("
        CREATE TABLE IF NOT EXISTS api_clients (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            name          TEXT NOT NULL,
            key_hash      TEXT NOT NULL UNIQUE,
            key_prefix    TEXT NOT NULL,
            scopes        TEXT NOT NULL DEFAULT 'read',
            is_active     INTEGER NOT NULL DEFAULT 1,
            last_used_at  TEXT,
            created_at    TEXT NOT NULL DEFAULT (datetime('now'))
        )
    ");
    $db->exec("
        CREATE TABLE IF NOT EXISTS api_request_log (
            id            INTEGER PRIMARY KEY AUTOINCREMENT,
            client_id     INTEGER,
            method        TEXT NOT NULL,
            endpoint      TEXT NOT NULL,
            query_string  TEXT,
            ip            TEXT,
            status        INTEGER NOT NULL,
            response_ms   INTEGER,
            created_at    TEXT NOT NULL DEFAULT (datetime('now'))
        )
    ");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_api_log_client  ON api_request_log(client_id, created_at)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_api_log_created ON api_request_log(created_at)");
}

echo "✅ API schema installed (driver: {$driver}).\n";
