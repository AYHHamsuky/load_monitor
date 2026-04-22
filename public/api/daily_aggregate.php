<?php
require_once __DIR__ . '/../../config/database.php';

$type = $_GET['type'];
$date = $_GET['date'];

$table = ($type === '11kv') ? 'fdr11kv_data' : 'fdr33kv_data';
$col   = ($type === '11kv') ? 'Fdr11kv_code' : 'Fdr33kv_code';

$stmt = $pdo->prepare("
    SELECT {$col} AS feeder,
           SUM(load_read) AS total_load,
           AVG(load_read) AS avg_load,
           MAX(load_read) AS peak_load
    FROM {$table}
    WHERE entry_date = ?
    GROUP BY {$col}
");

$stmt->execute([$date]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
