<?php
/**
 * One-shot web-accessible migration runner (admin only).
 *
 * Runs any pending schema/data migrations that can't be done from a
 * regular request path (schema-changing operations).
 *
 * Current migrations:
 *   1. Drop CHECK on fdr11kv_data.fault_code / fdr33kv_data.fault_code
 *      (so codes like "O/C", "E/F", "C33kV Off/Y" can be inserted).
 *   2. Set default max_load = 13.00 MW for all 11kV feeders.
 *   3. Set default max_load = 25.00 MW for all 33kV feeders (drops
 *      the old CHECK max_load <= 15.00 constraint that would block it).
 *
 * DELETE this file from the server after running it once.
 */
require_once __DIR__ . '/../app/bootstrap.php';

Guard::requireLogin();
$me = Auth::user();
if (!in_array($me['role'], ['UL6', 'UL7'], true)) {
    http_response_code(403);
    die('Access denied — requires UL6 or UL7 login.');
}

$db     = Database::connect();
$driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
$log    = [];

function step(string $tag, callable $fn, array &$log): void {
    try {
        $result = $fn();
        $log[] = ['ok', $tag, $result ?: 'done'];
    } catch (Throwable $e) {
        $log[] = ['err', $tag, $e->getMessage()];
    }
}

// ── 1. Fault-code CHECK / ENUM drop ─────────────────────────────────────────
step('fdr11kv_data — drop fault_code constraint', function () use ($db, $driver) {
    if ($driver === 'sqlite') return recreateWithoutCheck($db, 'fdr11kv_data', 'fdr11kv_code');
    if ($driver === 'mysql')  return alterEnumToVarchar($db, 'fdr11kv_data');
    return 'skipped (unsupported driver)';
}, $log);

step('fdr33kv_data — drop fault_code constraint', function () use ($db, $driver) {
    if ($driver === 'sqlite') return recreateWithoutCheck($db, 'fdr33kv_data', 'fdr33kv_code');
    if ($driver === 'mysql')  return alterEnumToVarchar($db, 'fdr33kv_data');
    return 'skipped (unsupported driver)';
}, $log);

// ── 2 & 3. Raise max_load thresholds ────────────────────────────────────────
step('fdr11kv — set max_load = 13.00 MW for every feeder', function () use ($db, $driver) {
    if ($driver === 'sqlite') recreateFdrWithNewMax($db, 'fdr11kv', 'fdr11kv_code', 'fdr11kv_name', 13.00, 20.00, true);
    else $db->exec("ALTER TABLE fdr11kv MODIFY COLUMN max_load DECIMAL(6,2) NOT NULL DEFAULT 13.00");
    $n = $db->exec("UPDATE fdr11kv SET max_load = 13.00");
    return "{$n} rows updated";
}, $log);

step('fdr33kv — set max_load = 25.00 MW for every feeder', function () use ($db, $driver) {
    if ($driver === 'sqlite') recreateFdrWithNewMax($db, 'fdr33kv', 'fdr33kv_code', 'fdr33kv_name', 25.00, 30.00, false);
    else $db->exec("ALTER TABLE fdr33kv MODIFY COLUMN max_load DECIMAL(6,2) NOT NULL DEFAULT 25.00");
    $n = $db->exec("UPDATE fdr33kv SET max_load = 25.00");
    return "{$n} rows updated";
}, $log);

// ─────────────────────────────────────────────────────────────────────────────
function recreateWithoutCheck(PDO $db, string $tbl, string $feederCol): string {
    $cur = $db->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='{$tbl}'")->fetchColumn();
    if (!$cur) return "table {$tbl} not found";
    if (stripos($cur, 'CHECK') === false || stripos($cur, 'fault_code') === false) {
        return "already upgraded (no CHECK on fault_code)";
    }

    $db->beginTransaction();
    try {
        $tmp = $tbl . '_new_mig';
        $db->exec("
            CREATE TABLE {$tmp} (
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
        $db->exec("
            INSERT INTO {$tmp}
                (entry_date, {$feederCol}, entry_hour, load_read,
                 fault_code, fault_remark, user_id, timestamp)
            SELECT entry_date, {$feederCol}, entry_hour, load_read,
                   fault_code, fault_remark, user_id, timestamp
            FROM {$tbl}
        ");
        $db->exec("DROP TABLE {$tbl}");
        $db->exec("ALTER TABLE {$tmp} RENAME TO {$tbl}");
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
    $rows = $db->query("SELECT COUNT(*) FROM {$tbl}")->fetchColumn();
    return "CHECK dropped, {$rows} rows preserved";
}

function alterEnumToVarchar(PDO $db, string $tbl): string {
    $col = $db->query("
        SELECT COLUMN_TYPE FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$tbl}' AND COLUMN_NAME = 'fault_code'
    ")->fetchColumn();
    if (!$col) return "column not found";
    if (stripos($col, 'enum') === false) return "already upgraded ({$col})";
    $db->exec("ALTER TABLE {$tbl} MODIFY COLUMN fault_code VARCHAR(50) DEFAULT NULL");
    return "widened to VARCHAR(50)";
}

function recreateFdrWithNewMax(PDO $db, string $tbl, string $codeCol, string $nameCol,
                                float $newDefault, float $newCeiling, bool $is11kv): void {
    $cur = $db->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='{$tbl}'")->fetchColumn();
    if (!$cur) return;
    if (stripos($cur, 'CHECK') === false || stripos($cur, 'max_load') === false) return; // already fine

    // Column list differs between 11kV (has fdr33kv_code, band, ao_code, iss_code)
    // and 33kV (has ts_code). Keep it schema-preserving.
    $db->beginTransaction();
    try {
        $tmp = $tbl . '_new_mig';
        if ($is11kv) {
            $db->exec("
                CREATE TABLE {$tmp} (
                    {$codeCol}   TEXT PRIMARY KEY,
                    {$nameCol}   TEXT NOT NULL UNIQUE,
                    fdr33kv_code TEXT,
                    band         TEXT NOT NULL,
                    ao_code      TEXT NOT NULL,
                    iss_code     TEXT NOT NULL,
                    created_at   TEXT NOT NULL DEFAULT (datetime('now')),
                    max_load     REAL NOT NULL DEFAULT {$newDefault},
                    CHECK (max_load <= {$newCeiling})
                )
            ");
            $db->exec("
                INSERT INTO {$tmp} ({$codeCol}, {$nameCol}, fdr33kv_code, band, ao_code, iss_code, created_at, max_load)
                SELECT {$codeCol}, {$nameCol}, fdr33kv_code, band, ao_code, iss_code, created_at, max_load
                FROM {$tbl}
            ");
        } else {
            $db->exec("
                CREATE TABLE {$tmp} (
                    {$codeCol}   TEXT PRIMARY KEY,
                    {$nameCol}   TEXT NOT NULL UNIQUE,
                    ts_code      TEXT NOT NULL,
                    created_at   TEXT NOT NULL DEFAULT (datetime('now')),
                    max_load     REAL NOT NULL DEFAULT {$newDefault},
                    CHECK (max_load <= {$newCeiling})
                )
            ");
            $db->exec("
                INSERT INTO {$tmp} ({$codeCol}, {$nameCol}, ts_code, created_at, max_load)
                SELECT {$codeCol}, {$nameCol}, ts_code, created_at, max_load
                FROM {$tbl}
            ");
        }
        $db->exec("DROP TABLE {$tbl}");
        $db->exec("ALTER TABLE {$tmp} RENAME TO {$tbl}");
        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Schema Migrations</title>
<style>
body { font-family:'Segoe UI',sans-serif; max-width:800px; margin:30px auto; padding:0 20px; background:#f4f6fa; color:#1e293b; }
h2 { color:#004B23; margin:0 0 6px; }
.sub { color:#64748b; font-size:13px; margin-bottom:24px; }
.row { padding:12px 16px; border-radius:8px; margin:8px 0; font-size:13px; display:flex; gap:12px; align-items:flex-start; }
.ok  { background:#dcfce7; border-left:4px solid #22c55e; }
.err { background:#fee2e2; border-left:4px solid #dc2626; }
.row .tag { font-weight:700; min-width:22px; }
.row .name { font-weight:700; flex:1; }
.row .msg  { color:#475569; font-family:monospace; font-size:12px; }
.info { background:#dbeafe; border-left:4px solid #3b82f6; padding:12px 16px; border-radius:6px; margin-bottom:16px; font-size:13px; }
.del  { background:#fee2e2; border:2px solid #dc2626; padding:14px; border-radius:8px; margin-top:24px; font-weight:bold; color:#991b1b; }
code { background:#fff; padding:2px 6px; border-radius:3px; font-family:monospace; font-size:12px; }
</style></head>
<body>
<h2>Schema Migrations</h2>
<div class="sub">Ran by <?= htmlspecialchars($me['staff_name']) ?> (<?= $me['role'] ?>) at <?= date('Y-m-d H:i:s') ?> — driver: <code><?= $driver ?></code></div>

<div class="info">
    This page has run the pending schema-changing migrations. Each row shows the
    outcome. Re-running the page is safe — steps that were already applied show
    <em>"already upgraded"</em> and are a no-op.
</div>

<?php foreach ($log as [$tag, $name, $msg]): ?>
    <div class="row <?= $tag === 'ok' ? 'ok' : 'err' ?>">
        <div class="tag"><?= $tag === 'ok' ? '✅' : '❌' ?></div>
        <div class="name"><?= htmlspecialchars($name) ?></div>
        <div class="msg"><?= htmlspecialchars($msg) ?></div>
    </div>
<?php endforeach; ?>

<div class="del">
    🗑️ DELETE this file from the server after running:<br>
    <code>public/run_migrations.php</code>
</div>
</body></html>
