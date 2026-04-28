<?php
/**
 * TEMPORARY DIAGNOSTIC — DELETE AFTER USE
 * Shows data counts by date to locate missing readings.
 * Accessible to UL6, UL7 (admin) and UL8 (Lead Dispatch).
 */
require_once __DIR__ . '/../app/bootstrap.php';

Guard::requireLogin();
$u = Auth::user();
if (!in_array($u['role'], ['UL6', 'UL7', 'UL8'], true)) {
    http_response_code(403);
    die('Access denied — requires UL6, UL7 or UL8.');
}

$db  = Database::connect();
$err = [];

function safe_query(PDO $db, string $sql, array &$err): array {
    try {
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $err[] = htmlspecialchars($e->getMessage());
        return [];
    }
}

// ── 11kV readings per date (last 10 days) ────────────────────────────────
$rows_11kv = safe_query($db, "
    SELECT
        entry_date,
        COUNT(*)                     AS total_rows,
        COUNT(DISTINCT fdr11kv_code) AS feeders,
        COUNT(DISTINCT entry_hour)   AS distinct_hours,
        COALESCE(ROUND(SUM(load_read),2), 0) AS total_load
    FROM fdr11kv_data
    WHERE entry_date >= DATE(NOW() - INTERVAL 10 DAY)
    GROUP BY entry_date
    ORDER BY entry_date DESC
", $err);

// ── 33kV readings per date (last 10 days) ────────────────────────────────
$rows_33kv = safe_query($db, "
    SELECT
        entry_date,
        COUNT(*)                     AS total_rows,
        COUNT(DISTINCT fdr33kv_code) AS feeders,
        COUNT(DISTINCT entry_hour)   AS distinct_hours,
        COALESCE(ROUND(SUM(load_read),2), 0) AS total_load
    FROM fdr33kv_data
    WHERE entry_date >= DATE(NOW() - INTERVAL 10 DAY)
    GROUP BY entry_date
    ORDER BY entry_date DESC
", $err);

// ── Hourly breakdown for two most recent 11kV dates ──────────────────────
$recent = safe_query($db, "
    SELECT DISTINCT entry_date FROM fdr11kv_data
    ORDER BY entry_date DESC LIMIT 2
", $err);

$hourly = [];
foreach ($recent as $row) {
    $d = $row['entry_date'];
    try {
        $st = $db->prepare("
            SELECT
                entry_hour,
                COUNT(DISTINCT fdr11kv_code)          AS feeders,
                COALESCE(ROUND(SUM(load_read),2), 0)  AS total_load
            FROM fdr11kv_data
            WHERE entry_date = ?
            GROUP BY entry_hour
            ORDER BY entry_hour
        ");
        $st->execute([$d]);
        $hourly[$d] = $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        $err[] = htmlspecialchars($e->getMessage());
    }
}

$server_tz  = date_default_timezone_get();
$now_str    = date('Y-m-d H:i:s');
$op_date    = getOperationalDate();
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">
<title>Data Diagnostic</title>
<style>
body{font-family:sans-serif;max-width:1100px;margin:30px auto;background:#f4f6fa;color:#1e293b}
h2{color:#004B23}h3{color:#0369a1;margin-top:28px}
table{width:100%;border-collapse:collapse;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.1);margin-bottom:20px}
th{background:#004B23;color:#fff;padding:9px 12px;text-align:left;font-size:13px}
td{padding:8px 12px;border-bottom:1px solid #e2e8f0;font-size:13px}
tr:last-child td{border:none}
.hi{background:#dcfce7;font-weight:700}
.info{background:#dbeafe;border-left:4px solid #3b82f6;padding:12px 16px;border-radius:6px;margin:16px 0;font-size:13px}
.err-box{background:#fee2e2;border-left:4px solid #dc2626;padding:12px 16px;border-radius:6px;margin:12px 0;font-family:monospace;font-size:12px}
.del{background:#fef9c3;border-left:4px solid #f59e0b;padding:14px;border-radius:8px;margin-top:30px;font-weight:bold;font-size:13px}
</style></head><body>
<h2>Load Monitor — Data Diagnostic</h2>

<div class="info">
    Server timezone: <strong><?= htmlspecialchars($server_tz) ?></strong> &nbsp;|&nbsp;
    Server now: <strong><?= $now_str ?></strong> &nbsp;|&nbsp;
    Operational date: <strong><?= $op_date ?></strong>
</div>

<?php foreach ($err as $e): ?>
    <div class="err-box">SQL error: <?= $e ?></div>
<?php endforeach; ?>

<h3>11kV Readings — last 10 days</h3>
<?php if (empty($rows_11kv)): ?>
    <p>No 11kV data found (or query error above).</p>
<?php else: ?>
<table>
<tr><th>entry_date</th><th>Rows</th><th>Feeders with data</th><th>Hours covered</th><th>Total load (MW)</th></tr>
<?php foreach ($rows_11kv as $r): ?>
    <tr class="<?= $r['total_load'] > 0 ? 'hi' : '' ?>">
        <td><?= htmlspecialchars($r['entry_date']) ?></td>
        <td><?= $r['total_rows'] ?></td>
        <td><?= $r['feeders'] ?></td>
        <td><?= $r['distinct_hours'] ?></td>
        <td><?= $r['total_load'] ?></td>
    </tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<h3>33kV Readings — last 10 days</h3>
<?php if (empty($rows_33kv)): ?>
    <p>No 33kV data found (or query error above).</p>
<?php else: ?>
<table>
<tr><th>entry_date</th><th>Rows</th><th>Feeders with data</th><th>Hours covered</th><th>Total load (MW)</th></tr>
<?php foreach ($rows_33kv as $r): ?>
    <tr class="<?= $r['total_load'] > 0 ? 'hi' : '' ?>">
        <td><?= htmlspecialchars($r['entry_date']) ?></td>
        <td><?= $r['total_rows'] ?></td>
        <td><?= $r['feeders'] ?></td>
        <td><?= $r['distinct_hours'] ?></td>
        <td><?= $r['total_load'] ?></td>
    </tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<h3>11kV — Hourly breakdown for the two most recent dates</h3>
<?php if (empty($hourly)): ?>
    <p>No hourly data to display.</p>
<?php else: ?>
<?php foreach ($hourly as $date => $hrs): ?>
    <strong><?= htmlspecialchars($date) ?></strong>
    <table>
    <tr><th>Hour</th><th>Feeders entered</th><th>Total load (MW)</th></tr>
    <?php foreach ($hrs as $h): ?>
    <tr>
        <td><?= sprintf('%02d:00', (int)$h['entry_hour']) ?></td>
        <td><?= $h['feeders'] ?></td>
        <td><?= $h['total_load'] ?></td>
    </tr>
    <?php endforeach; ?>
    </table>
<?php endforeach; ?>
<?php endif; ?>

<div class="del">
    ⚠️ DELETE this file from the server after reading the output:<br>
    <code>public/diag_data.php</code>
</div>
</body></html>
