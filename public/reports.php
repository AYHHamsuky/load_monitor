<?php
// File: /public/reports.php

session_start();
require_once __DIR__ . '/../app/Core/Auth.php';
require_once __DIR__ . '/../app/Core/Database.php';

use App\Core\Auth;
use App\Core\Database;

$user = Auth::requireLogin();
$db = Database::get();

$daily = $db->query(
    "SELECT reading_date, COUNT(*) entries
     FROM fdr11kv_data
     GROUP BY reading_date
     ORDER BY reading_date DESC
     LIMIT 7"
)->fetchAll();

$monthly = $db->query(
    "SELECT DATE_FORMAT(reading_date,'%Y-%m') month,
            SUM(load_reading) total_load
     FROM fdr11kv_data
     GROUP BY month
     ORDER BY month DESC"
)->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>Reports</title></head>
<body>

<h2>Daily Entries</h2>
<table border="1">
<tr><th>Date</th><th>Entries</th></tr>
<?php foreach ($daily as $d): ?>
<tr><td><?= $d['reading_date'] ?></td><td><?= $d['entries'] ?></td></tr>
<?php endforeach; ?>
</table>

<h2>Monthly Load Summary</h2>
<table border="1">
<tr><th>Month</th><th>Total Load</th></tr>
<?php foreach ($monthly as $m): ?>
<tr><td><?= $m['month'] ?></td><td><?= $m['total_load'] ?></td></tr>
<?php endforeach; ?>
</table>

</body>
</html>
