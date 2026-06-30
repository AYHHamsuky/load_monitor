<?php
/**
 * One-shot schema upgrade: drop the CHECK/ENUM constraint on
 * fdr11kv_data.fault_code and fdr33kv_data.fault_code so the dashboards
 * can use the full set of codes from interruption_codes (O/C, E/F,
 * O/C and E/F, C33kV Off/Y, etc.).
 *
 * Safe to run multiple times — detects if the upgrade is already applied
 * and exits early.
 *
 * Usage (inside the PHP container or via Dokploy terminal):
 *   php /var/www/html/sql/upgrade_fault_codes.php
 *
 * Works against both SQLite and MySQL.
 */
require_once __DIR__ . '/../app/bootstrap.php';

$db     = Database::connect();
$driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);

echo "Driver: {$driver}\n";

if ($driver === 'sqlite') {
    upgradeSqlite($db);
} elseif ($driver === 'mysql') {
    upgradeMysql($db);
} else {
    fwrite(STDERR, "Unsupported driver: {$driver}\n");
    exit(1);
}

echo "\n✅ Upgrade complete.\n";

// ─────────────────────────────────────────────────────────────────────────────
function upgradeSqlite(PDO $db): void {
    foreach (['fdr11kv_data' => 'fdr11kv_code', 'fdr33kv_data' => 'fdr33kv_code'] as $tbl => $feederCol) {
        // Read the current CREATE TABLE statement
        $cur = $db->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='{$tbl}'")
                  ->fetchColumn();
        if (!$cur) {
            echo "  · {$tbl}: table not found, skipping\n";
            continue;
        }
        if (stripos($cur, 'CHECK') === false || stripos($cur, 'fault_code') === false) {
            echo "  · {$tbl}: no CHECK constraint on fault_code — already upgraded\n";
            continue;
        }

        echo "  · {$tbl}: dropping CHECK on fault_code …\n";

        $db->beginTransaction();
        try {
            // Build the new table without the CHECK constraint
            $newTbl = $tbl . '_new_upgrade';
            $db->exec("
                CREATE TABLE {$newTbl} (
                    entry_date   TEXT NOT NULL,
                    {$feederCol} TEXT NOT NULL,
                    entry_hour   INTEGER NOT NULL,
                    load_read    REAL NOT NULL,
                    fault_code   TEXT,
                    fault_remark TEXT,
                    user_id      TEXT,
                    timestamp    TEXT DEFAULT (datetime('now')),
                    PRIMARY KEY (entry_date, {$feederCol}, entry_hour)
                )
            ");

            // Copy data
            $db->exec("
                INSERT INTO {$newTbl}
                    (entry_date, {$feederCol}, entry_hour, load_read,
                     fault_code, fault_remark, user_id, timestamp)
                SELECT entry_date, {$feederCol}, entry_hour, load_read,
                       fault_code, fault_remark, user_id, timestamp
                FROM {$tbl}
            ");

            // Swap tables
            $db->exec("DROP TABLE {$tbl}");
            $db->exec("ALTER TABLE {$newTbl} RENAME TO {$tbl}");

            $db->commit();
            $rowCount = $db->query("SELECT COUNT(*) FROM {$tbl}")->fetchColumn();
            echo "    ✓ {$tbl}: upgraded, {$rowCount} rows preserved\n";
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
function upgradeMysql(PDO $db): void {
    foreach (['fdr11kv_data', 'fdr33kv_data'] as $tbl) {
        $col = $db->query("
            SELECT COLUMN_TYPE
              FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME   = '{$tbl}'
               AND COLUMN_NAME  = 'fault_code'
        ")->fetchColumn();

        if (!$col) {
            echo "  · {$tbl}: column not found, skipping\n";
            continue;
        }

        if (stripos($col, 'enum') === false) {
            echo "  · {$tbl}.fault_code is {$col} — already upgraded\n";
            continue;
        }

        echo "  · {$tbl}.fault_code was {$col} — widening to VARCHAR(50) …\n";
        $db->exec("ALTER TABLE {$tbl} MODIFY COLUMN fault_code VARCHAR(50) DEFAULT NULL");
        echo "    ✓ {$tbl}: upgraded\n";
    }
}
