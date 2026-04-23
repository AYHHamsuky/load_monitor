<?php
/**
 * Diagnostic page — visit /diag.php?token=diag2026
 * Shows PHP errors, env vars, and DB status. DELETE after debugging.
 */
if (($_GET['token'] ?? '') !== 'diag2026') {
    http_response_code(403);
    exit('Forbidden');
}

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');

echo "=== PHP " . PHP_VERSION . " ===\n\n";

// Env vars (password redacted)
echo "DB_DRIVER  = " . (getenv('DB_DRIVER')  ?: '(not set — defaults to sqlite)') . "\n";
echo "DB_HOST    = " . (getenv('DB_HOST')    ?: '(not set)') . "\n";
echo "DB_PORT    = " . (getenv('DB_PORT')    ?: '(not set)') . "\n";
echo "DB_NAME    = " . (getenv('DB_NAME')    ?: '(not set)') . "\n";
echo "DB_USER    = " . (getenv('DB_USER')    ?: '(not set)') . "\n";
echo "DB_PASS    = " . (getenv('DB_PASS') !== false ? '(set, hidden)' : '(not set)') . "\n";
echo "APP_BASE_PATH = '" . getenv('APP_BASE_PATH') . "'\n\n";

// Extensions
echo "pdo_mysql  loaded: " . (extension_loaded('pdo_mysql')  ? 'YES' : 'NO') . "\n";
echo "pdo_sqlite loaded: " . (extension_loaded('pdo_sqlite') ? 'YES' : 'NO') . "\n\n";

// Bootstrap (will show any fatal errors)
echo "=== Loading bootstrap ===\n";
try {
    require_once __DIR__ . '/../app/bootstrap.php';
    echo "bootstrap.php: OK\n";
} catch (Throwable $e) {
    echo "bootstrap.php FAILED: " . $e->getMessage() . "\n";
    echo "  at " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit;
}

// DB connection test
echo "\n=== DB connection ===\n";
try {
    $db = Database::connect();
    echo "Connected OK\n";

    // Check for staff_details table
    $driver = getenv('DB_DRIVER') ?: 'sqlite';
    if ($driver === 'mysql') {
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    }
    echo "Tables (" . count($tables) . "): " . implode(', ', $tables) . "\n";

    if (in_array('staff_details', $tables)) {
        $count = $db->query("SELECT COUNT(*) FROM staff_details")->fetchColumn();
        echo "staff_details rows: $count\n";
    } else {
        echo "staff_details: TABLE MISSING — schema not imported\n";
    }
} catch (Throwable $e) {
    echo "DB connection FAILED: " . $e->getMessage() . "\n";
}
