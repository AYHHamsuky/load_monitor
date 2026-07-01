<?php
/**
 * Load the dispatch team's feeder mapping hierarchy from
 *   sql/feeder_hierarchy.csv
 * into a new reference table `feeder_hierarchy_map`, and safely refresh the
 * `band` and `region` metadata on existing feeder / area-office rows where
 * the names match (fuzzy).
 *
 * DESIGN NOTE — safety first:
 *   • Existing fdr11kv_code / fdr33kv_code / ao_id / ts_code values are
 *     never changed.  All historical rows in fdr11kv_data / fdr33kv_data
 *     that reference those codes stay intact.
 *   • The CSV becomes the source of truth for band + hierarchy going
 *     forward, and admin pages can display it directly from the new
 *     `feeder_hierarchy_map` table.
 *   • `area_offices.region` and `transmission_stations` gain optional
 *     hierarchy columns (parent_330kv, voltage_level) so reports can group
 *     by 330 kV / 132 kV / Region without breaking anything.
 *
 * Run once via  Terminal:
 *     php sql/import_feeder_hierarchy.php
 * or via the admin browser at
 *     /import_feeder_hierarchy.php
 */
require_once __DIR__ . '/../app/bootstrap.php';

$db     = Database::connect();
$driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
$log    = [];

function say(string $tag, string $msg, array &$log): void {
    $log[] = ['tag' => $tag, 'msg' => $msg];
    if (php_sapi_name() === 'cli') echo "  {$tag}  {$msg}\n";
}

// ── 1. Schema bumps (additive only) ─────────────────────────────────────────
$existingCols = fn(string $tbl) => array_column(
    ($driver === 'sqlite'
        ? $db->query("PRAGMA table_info({$tbl})")->fetchAll(PDO::FETCH_ASSOC)
        : $db->query("SHOW COLUMNS FROM {$tbl}")->fetchAll(PDO::FETCH_ASSOC)),
    $driver === 'sqlite' ? 'name' : 'Field'
);

$addCol = function (string $tbl, string $col, string $def) use ($db, $existingCols, &$log) {
    if (!in_array($col, $existingCols($tbl), true)) {
        $db->exec("ALTER TABLE {$tbl} ADD COLUMN {$col} {$def}");
        say('add', "{$tbl}.{$col} — added ({$def})", $log);
    }
};
$addCol('area_offices',         'region',        'TEXT');
$addCol('transmission_stations','parent_330kv',  'TEXT');
$addCol('transmission_stations','voltage_level', "TEXT DEFAULT '132kV'");

// ── 2. feeder_hierarchy_map table ───────────────────────────────────────────
$db->exec($driver === 'sqlite' ? "
    CREATE TABLE IF NOT EXISTS feeder_hierarchy_map (
        id             INTEGER PRIMARY KEY AUTOINCREMENT,
        tx_330kv       TEXT NOT NULL,
        tx_132kv       TEXT NOT NULL,
        region         TEXT NOT NULL,
        area_office    TEXT NOT NULL,
        fdr33kv_name   TEXT NOT NULL,
        fdr11kv_name   TEXT,
        band           TEXT,
        is_dedicated   INTEGER NOT NULL DEFAULT 0,
        mapping_source TEXT NOT NULL DEFAULT 'CSV',
        imported_at    TEXT NOT NULL DEFAULT (datetime('now'))
    )
" : "
    CREATE TABLE IF NOT EXISTS feeder_hierarchy_map (
        id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        tx_330kv       VARCHAR(64) NOT NULL,
        tx_132kv       VARCHAR(64) NOT NULL,
        region         VARCHAR(32) NOT NULL,
        area_office    VARCHAR(64) NOT NULL,
        fdr33kv_name   VARCHAR(100) NOT NULL,
        fdr11kv_name   VARCHAR(100) NULL,
        band           VARCHAR(4) NULL,
        is_dedicated   TINYINT NOT NULL DEFAULT 0,
        mapping_source VARCHAR(32) NOT NULL DEFAULT 'CSV',
        imported_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX ix_fdr33 (fdr33kv_name),
        INDEX ix_ao    (area_office),
        INDEX ix_region(region)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// ── 3. Load the CSV ─────────────────────────────────────────────────────────
$csvPath = __DIR__ . '/feeder_hierarchy.csv';
if (!is_file($csvPath)) {
    say('err', "CSV not found: {$csvPath}", $log);
    return_or_render($log, 'CSV missing');
}
$fh = fopen($csvPath, 'r');
if (!$fh) {
    say('err', "Could not open {$csvPath}", $log);
    return_or_render($log, 'read failed');
}
$header = fgetcsv($fh);   // discard
$rows   = [];
while (($r = fgetcsv($fh)) !== false) {
    if (count($r) < 8) continue;
    [$tx330, $tx132, $reg, $ao, $f33, $f11, $band, $mapping] = array_map('trim', $r);
    if ($tx330 === '' && $tx132 === '') continue;

    $isDedicated = (stripos($f11, 'dedicated') !== false || stripos($f11, 'no 11') !== false || $f11 === '');
    $rows[] = [
        'tx_330kv'      => $tx330,
        'tx_132kv'      => $tx132,
        'region'        => strtoupper($reg),
        'area_office'   => strtoupper($ao),
        'fdr33kv_name'  => $f33,
        'fdr11kv_name'  => $isDedicated ? null : $f11,
        'band'          => ($band === '' || $band === '*') ? null : strtoupper($band),
        'is_dedicated'  => $isDedicated ? 1 : 0,
        'mapping_source'=> $mapping ?: 'CSV',
    ];
}
fclose($fh);
say('info', 'CSV parsed: ' . count($rows) . ' rows', $log);

// ── 4. Replace map contents ─────────────────────────────────────────────────
$db->beginTransaction();
try {
    $db->exec("DELETE FROM feeder_hierarchy_map");
    $ins = $db->prepare("
        INSERT INTO feeder_hierarchy_map
            (tx_330kv, tx_132kv, region, area_office, fdr33kv_name, fdr11kv_name, band, is_dedicated, mapping_source)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($rows as $r) {
        $ins->execute([$r['tx_330kv'], $r['tx_132kv'], $r['region'], $r['area_office'],
                       $r['fdr33kv_name'], $r['fdr11kv_name'], $r['band'], $r['is_dedicated'], $r['mapping_source']]);
    }
    $db->commit();
    say('ok', 'feeder_hierarchy_map: ' . count($rows) . ' rows loaded', $log);
} catch (Throwable $e) {
    $db->rollBack();
    say('err', 'load failed: ' . $e->getMessage(), $log);
    return_or_render($log, 'FAILED');
}

// ── 5. Region tagging on area_offices (fuzzy name match) ────────────────────
$aoNorm = fn(string $s) => strtoupper(preg_replace('/[^A-Z0-9]/i', '', $s));
$aos    = $db->query("SELECT ao_id, ao_name, region FROM area_offices")->fetchAll(PDO::FETCH_ASSOC);
$regionMap = [];
foreach ($rows as $r) $regionMap[$aoNorm($r['area_office'])] = $r['region'];

$updAO = $db->prepare("UPDATE area_offices SET region = ? WHERE ao_id = ?");
$aoUpdated = 0;
foreach ($aos as $a) {
    if (!empty($a['region'])) continue;                        // already tagged
    $key = $aoNorm($a['ao_name']);
    if (isset($regionMap[$key])) {
        $updAO->execute([$regionMap[$key], $a['ao_id']]);
        $aoUpdated++;
    }
}
say('ok', "area_offices.region — {$aoUpdated} row(s) auto-tagged", $log);

// ── 6. TS 132/330 tagging (fuzzy name match) ────────────────────────────────
$tsNorm = fn(string $s) => strtoupper(preg_replace('/[^A-Z0-9]/i', '', $s));
$tsList = $db->query("SELECT ts_code, station_name, parent_330kv, voltage_level FROM transmission_stations")->fetchAll(PDO::FETCH_ASSOC);
$tsMap = [];
foreach ($rows as $r) $tsMap[$tsNorm(preg_replace('/\s*132\s*k?v?$/i', '', $r['tx_132kv']))] = $r['tx_330kv'];

$updTS = $db->prepare("UPDATE transmission_stations SET parent_330kv = ?, voltage_level = '132kV' WHERE ts_code = ?");
$tsUpdated = 0;
foreach ($tsList as $t) {
    if (!empty($t['parent_330kv'])) continue;
    $key = $tsNorm(preg_replace('/\s*(transmission\s*station|132\s*kV)$/i', '', $t['station_name']));
    if (isset($tsMap[$key])) {
        $updTS->execute([$tsMap[$key], $t['ts_code']]);
        $tsUpdated++;
    }
}
say('ok', "transmission_stations.parent_330kv — {$tsUpdated} row(s) auto-tagged", $log);

// ── 7. fdr11kv band refresh (fuzzy name match) ──────────────────────────────
$feederNorm = function (string $s): string {
    $s = preg_replace('/^\s*(11|33)\s*k?v?\s+/i', '', $s);            // strip 11kV / 33kV prefix
    $s = preg_replace('/\s*\((dedicated|dedicated feeder|no 11kv)\)\s*$/i', '', $s); // dedicated tag
    $s = preg_replace('/[^A-Z0-9]/i', '', strtoupper($s));
    return $s;
};
$bandMap = [];                                                // fuzzy 11kV key → band
$parentMap = [];                                              // fuzzy 11kV key → fdr33kv fuzzy key
foreach ($rows as $r) {
    if (empty($r['fdr11kv_name'])) continue;
    $k = $feederNorm($r['fdr11kv_name']);
    $bandMap[$k]   = $r['band'];
    $parentMap[$k] = $feederNorm($r['fdr33kv_name']);
}

$rows11 = $db->query("SELECT fdr11kv_code, fdr11kv_name, band FROM fdr11kv")->fetchAll(PDO::FETCH_ASSOC);
$upd11  = $db->prepare("UPDATE fdr11kv SET band = ? WHERE fdr11kv_code = ?");
$bandChanged = $bandUnknown = 0;
foreach ($rows11 as $f) {
    $k = $feederNorm($f['fdr11kv_name']);
    if (!isset($bandMap[$k]) || $bandMap[$k] === null) { $bandUnknown++; continue; }
    if ($bandMap[$k] !== $f['band']) {
        $upd11->execute([$bandMap[$k], $f['fdr11kv_code']]);
        $bandChanged++;
    }
}
say('ok', "fdr11kv.band — {$bandChanged} row(s) updated, {$bandUnknown} unmatched", $log);

say('done', 'Feeder hierarchy import complete.', $log);
return_or_render($log, 'OK');

// ─────────────────────────────────────────────────────────────────────────────
function return_or_render(array $log, string $status): void {
    if (php_sapi_name() === 'cli') { echo "\n{$status}\n"; return; }
    header('Content-Type: text/html; charset=utf-8');
    ?>
    <!DOCTYPE html><html><head><meta charset="UTF-8"><title>Feeder hierarchy import</title>
    <style>
      body{font-family:sans-serif;max-width:800px;margin:30px auto;padding:0 20px;background:#f4f6fa;color:#1e293b}
      h2{color:#004B23;margin:0 0 8px}
      .row{padding:10px 14px;border-radius:6px;margin:6px 0;font-size:13px;display:flex;gap:12px;align-items:flex-start}
      .ok{background:#dcfce7;border-left:4px solid #22c55e}
      .add{background:#dbeafe;border-left:4px solid #3b82f6}
      .info{background:#f1f5f9;border-left:4px solid #64748b}
      .err{background:#fee2e2;border-left:4px solid #dc2626}
      .done{background:#dcfce7;border-left:4px solid #16a34a;font-weight:700}
      .tag{font-weight:700;text-transform:uppercase;font-size:11px;min-width:36px}
      .del{background:#fee2e2;border:2px solid #dc2626;padding:14px;border-radius:8px;margin-top:24px;font-weight:bold;color:#991b1b}
      code{background:#fff;padding:2px 6px;border-radius:3px;font-family:monospace;font-size:12px}
    </style></head><body>
    <h2>Feeder Hierarchy Import — <?= htmlspecialchars($status) ?></h2>
    <?php foreach ($log as $l): ?>
      <div class="row <?= htmlspecialchars($l['tag']) ?>">
          <div class="tag"><?= htmlspecialchars($l['tag']) ?></div>
          <div><?= htmlspecialchars($l['msg']) ?></div>
      </div>
    <?php endforeach; ?>
    <div class="del">🗑️ DELETE this file from the server after use:<br>
        <code>public/import_feeder_hierarchy.php</code></div>
    </body></html>
    <?php
}
