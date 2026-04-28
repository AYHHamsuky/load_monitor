<?php
/**
 * TEMPORARY DIAGNOSTIC — DELETE AFTER USE
 * Shows data counts by date to locate missing readings.
 */
require_once __DIR__ . '/../app/bootstrap.php';
Guard::requireAdmin();   // UL6 or UL7 only

$db = Database::connect();

// Last 7 days of 11kV data
$rows_11kv = $db->query("
    SELECT entry_date,
           COUNT(*)                                   AS total_rows,
           COUNT(DISTINCT fdr11kv_code)               AS feeders,
           COUNT(DISTINCT entry_hour)                 AS distinct_hours,
           SUM(CASE WHEN load_read > 0 THEN 1 END)   AS load_rows,
           ROUND(SUM(load_read), 2)                   AS total_load
    FROM fdr11kv_data
    WHERE entry_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY entry_date
    ORDER BY entry_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Last 7 days of 33kV data
$rows_33kv = $db->query("
    SELECT entry_date,
           COUNT(*)                                   AS total_rows,
           COUNT(DISTINCT fdr33kv_code)               AS feeders,
           COUNT(DISTINCT entry_hour)                 AS distinct_hours,
           SUM(CASE WHEN load_read > 0 THEN 1 END)   AS load_rows,
           ROUND(SUM(load_read), 2)                   AS total_load
    FROM fdr33kv_data
    WHERE entry_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY entry_date
    ORDER BY entry_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Hourly breakdown for the two most recent dates (11kV)
$recent_dates = $db->query("
    SELECT DISTINCT entry_date FROM fdr11kv_data
    ORDER BY entry_date DESC LIMIT 2
")->fetchAll(PDO::FETCH_COLUMN);

$hourly = [];
foreach ($recent_dates as $d) {
    $hourly[$d] = $db->prepare("
        SELECT entry_hour,
               COUNT(DISTINCT fdr11kv_code) AS feeders,
               SUM(CASE WHEN load_read > 0 THEN 1 END) AS load_cells,
               ROUND(SUM(load_read),2) AS total_load
        FROM fdr11kv_data
        WHERE entry_date = ?
        GROUP BY entry_hour
        ORDER BY entry_hour
    ");
    $hourly[$d]->execute([$d]);
    $hourly[$d] = $hourly[$d]->fetchAll(PDO::FETCH_ASSOC);
}

$server_tz = date_default_timezone_get();
$now_str   = date('Y-m-d H:i:s');
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
.warn{background:#fef9c3;border-left:4px solid #f59e0b;padding:12px 16px;border-radius:6px;margin:16px 0}
.info{background:#dbeafe;border-left:4px solid #3b82f6;padding:12px 16px;border-radius:6px;margin:16px 0}
.del{background:#fee2e2;border-left:4px solid #dc2626;padding:14px;border-radius:8px;margin-top:30px;font-weight:bold}
</style></head><body>
<h2>Load Monitor — Data Diagnostic</h2>
<div class="info">
    Server timezone: <strong><?= $server_tz ?></strong> &nbsp;|&nbsp;
    Server now: <strong><?= $now_str ?></strong>
</div>

<h3>11kV Readings — last 7 days</h3>
<?php if (empty($rows_11kv)): ?>
    <p>No 11kV data found in the last 7 days.</p>
<?php else: ?>
<table>
<tr><th>entry_date</th><th>Rows</th><th>Feeders</th><th>Distinct hours</th><th>Load cells</th><th>Total load (MW)</th></tr>
<?php foreach ($rows_11kv as $r): ?>
    <tr class="<?= $r['total_load'] > 0 ? 'hi' : '' ?>">
        <td><?= $r['entry_date'] ?></td>
        <td><?= $r['total_rows'] ?></td>
        <td><?= $r['feeders'] ?></td>
        <td><?= $r['distinct_hours'] ?></td>
        <td><?= $r['load_rows'] ?></td>
        <td><?= $r['total_load'] ?></td>
    </tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<h3>33kV Readings — last 7 days</h3>
<?php if (empty($rows_33kv)): ?>
    <p>No 33kV data found in the last 7 days.</p>
<?php else: ?>
<table>
<tr><th>entry_date</th><th>Rows</th><th>Feeders</th><th>Distinct hours</th><th>Load cells</th><th>Total load (MW)</th></tr>
<?php foreach ($rows_33kv as $r): ?>
    <tr class="<?= $r['total_load'] > 0 ? 'hi' : '' ?>">
        <td><?= $r['entry_date'] ?></td>
        <td><?= $r['total_rows'] ?></td>
        <td><?= $r['feeders'] ?></td>
        <td><?= $r['distinct_hours'] ?></td>
        <td><?= $r['load_rows'] ?></td>
        <td><?= $r['total_load'] ?></td>
    </tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

<h3>11kV — Hourly breakdown for most recent dates</h3>
<?php foreach ($hourly as $date => $hrs): ?>
    <strong><?= $date ?></strong>
    <table>
    <tr><th>Hour</th><th>Feeders entered</th><th>Load cells</th><th>Total load (MW)</th></tr>
    <?php foreach ($hrs as $h): ?>
    <tr>
        <td><?= sprintf('%02d:00', $h['entry_hour']) ?></td>
        <td><?= $h['feeders'] ?></td>
        <td><?= $h['load_cells'] ?></td>
        <td><?= $h['total_load'] ?></td>
    </tr>
    <?php endforeach; ?>
    </table>
<?php endforeach; ?>

<div class="del">
    🗑️ DELETE this file immediately after reading the output:<br>
    <code>public/diag_data.php</code>
</div>
</body></html>
