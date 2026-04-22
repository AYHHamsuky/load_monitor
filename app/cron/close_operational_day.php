<?php
/**
 * Operational Day Auto-Close Script
 * Path: app/cron/close_operational_day.php
 *
 * Run this as a cron job at exactly 01:00 every night:
 *   1  0  *  *  *  php /path/to/app/cron/close_operational_day.php
 *
 * What it does:
 *   1. Determines the operational date that just closed
 *      (the previous calendar date — this script runs at 01:00 so the
 *       previous date's batch ended at 00:59 just now).
 *   2. Writes a NULL-load / BLANK row for every hour 0-23 on every feeder
 *      that has no entry yet for that date.
 *   3. Records the seal in operational_day_batches.
 *
 * This script is idempotent: running it twice for the same date is safe
 * because it checks existing rows before inserting blanks and uses an
 * INSERT IGNORE on the batch record.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../models/LoadReading11kv.php';
require_once __DIR__ . '/../models/LoadReading33kv.php';

// At 01:00 the operational date that just closed = yesterday's calendar date
$closedDate = (new DateTime())->modify('-1 day')->format('Y-m-d');

echo "[close_operational_day] Sealing op-date: {$closedDate}\n";

$db = Database::connect();

// ── Seal 33kV ────────────────────────────────────────────────────────────────
$blanked33 = 0;

// Check if already sealed
$chk = $db->prepare(
    "SELECT id FROM operational_day_batches WHERE op_date=? AND voltage_level='33kV'"
);
$chk->execute([$closedDate]);

if (!$chk->fetch()) {
    $blanked33 = LoadReading33kv::sealDay($closedDate);

    $db->prepare("
        INSERT OR IGNORE INTO operational_day_batches (op_date, voltage_level, blank_cells)
        VALUES (?, '33kV', ?)
    ")->execute([$closedDate, $blanked33]);

    echo "[close_operational_day] 33kV: {$blanked33} blank cells written.\n";
} else {
    echo "[close_operational_day] 33kV: already sealed — skipped.\n";
}

// ── Seal 11kV (per 33kV parent feeder) ──────────────────────────────────────
$blanked11 = 0;

$chk11 = $db->prepare(
    "SELECT id FROM operational_day_batches WHERE op_date=? AND voltage_level='11kV'"
);
$chk11->execute([$closedDate]);

if (!$chk11->fetch()) {
    // Get all 33kV feeder codes to iterate over
    $fs = $db->query("SELECT fdr33kv_code FROM fdr33kv");
    $parents = $fs->fetchAll(PDO::FETCH_COLUMN);

    foreach ($parents as $parentCode) {
        $blanked11 += LoadReading11kv::sealDay($closedDate, $parentCode);
    }

    $db->prepare("
        INSERT OR IGNORE INTO operational_day_batches (op_date, voltage_level, blank_cells)
        VALUES (?, '11kV', ?)
    ")->execute([$closedDate, $blanked11]);

    echo "[close_operational_day] 11kV: {$blanked11} blank cells written.\n";
} else {
    echo "[close_operational_day] 11kV: already sealed — skipped.\n";
}

echo "[close_operational_day] Done. " . date('Y-m-d H:i:s') . "\n";
