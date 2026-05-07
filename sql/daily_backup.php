<?php
/**
 * Daily backup script — runs inside the PHP container.
 *
 * Behaviour:
 *  • Exports every user table to JSON
 *  • Gzips the JSON (~80% smaller — 5MB → ~1MB)
 *  • Stores in /var/www/html/database/backups/  (persistent volume)
 *  • Rotates: keeps the last 30 daily backups, deletes older ones
 *  • Logs result to logs/backup.log
 *
 * Schedule via Dokploy → Schedules → run daily, e.g. 02:00:
 *   Service:  loadreading (the PHP app)
 *   Command:  php /var/www/html/sql/daily_backup.php
 *   Cron:     0 2 * * *
 *
 * Manual run (test):
 *   php /var/www/html/sql/daily_backup.php
 */

require_once __DIR__ . '/../app/bootstrap.php';

const KEEP_DAYS = 30;
$backupDir = '/var/www/html/database/backups';
$logFile   = '/var/www/html/logs/backup.log';

function blog(string $msg, string $log): void {
    $line = '[' . date('Y-m-d H:i:s') . "] {$msg}\n";
    @file_put_contents($log, $line, FILE_APPEND);
    echo $line;
}

try {
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0775, true);
    }

    $db = Database::connect();
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

    // List user tables — driver-specific
    if ($driver === 'sqlite') {
        $tables = $db->query("
            SELECT name FROM sqlite_master
            WHERE type='table' AND name NOT LIKE 'sqlite_%'
            ORDER BY name
        ")->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    }

    blog("Backup starting — driver: {$driver}, tables: " . count($tables), $logFile);

    $dump  = [];
    $total = 0;
    foreach ($tables as $t) {
        $rows = $db->query("SELECT * FROM `{$t}`")->fetchAll(PDO::FETCH_ASSOC);
        $dump[$t] = $rows;
        $total += count($rows);
    }

    $stamp = date('Ymd_His');
    $file  = "{$backupDir}/backup_{$stamp}.json.gz";

    $json   = json_encode($dump, JSON_UNESCAPED_UNICODE);
    $gz     = gzencode($json, 9);
    file_put_contents($file, $gz);

    $sizeKb = round(filesize($file) / 1024, 1);
    blog("✅ Wrote {$file} — {$total} rows, {$sizeKb} KB", $logFile);

    // ── Rotate: keep newest KEEP_DAYS files, delete the rest ────────────────
    $existing = glob("{$backupDir}/backup_*.json.gz") ?: [];
    rsort($existing);                                  // newest first by name
    $toDelete = array_slice($existing, KEEP_DAYS);
    foreach ($toDelete as $old) {
        @unlink($old);
        blog("  rotated out: " . basename($old), $logFile);
    }

    blog("Backup complete. Kept " . min(count($existing), KEEP_DAYS) . " backups.", $logFile);
    exit(0);

} catch (Throwable $e) {
    blog("❌ FAILED: " . $e->getMessage() . " at " . $e->getFile() . ':' . $e->getLine(), $logFile);
    exit(1);
}
