<?php
require_once __DIR__ . '/../../config/database.php';

$type = $_GET['type']; // 11kv or 33kv
$code = $_GET['code'];
$date = $_GET['date'];

$table = ($type === '11kv') ? 'fdr11kv_data' : 'fdr33kv_data';
$col   = ($type === '11kv') ? 'Fdr11kv_code' : 'Fdr33kv_code';

$stmt = $pdo->prepare("
    SELECT entry_hour, load_read
    FROM {$table}
    WHERE {$col} = ?
      AND entry_date = ?
    ORDER BY entry_hour
");

$stmt->execute([$code, $date]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
