<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if ($_SESSION['role'] !== 'UL1') {
    exit('Unauthorized');
}

$payroll = $_SESSION['payroll_id'];
$date = date('Y-m-d');

$fdr   = $_POST['fdr11kv_code'];
$hour  = (int)$_POST['entry_hour'];
$load  = (float)$_POST['load_read'];
$fault = $_POST['fault_code'] ?? null;
$remark = $_POST['fault_remark'] ?? null;

/* RULE: prevent duplicate hour */
$chk = $pdo->prepare("
    SELECT 1 FROM fdr11kv_data
    WHERE entry_date = ?
      AND Fdr11kv_code = ?
      AND entry_hour = ?
");
$chk->execute([$date, $fdr, $hour]);

if ($chk->fetch()) {
    exit("Entry already exists for this hour.");
}

/* RULE: load = 0 requires fault */
if ($load == 0 && empty($fault)) {
    exit("Fault details required when load is zero.");
}

/* insert */
$stmt = $pdo->prepare("
    INSERT INTO fdr11kv_data
    (entry_date, Fdr11kv_code, entry_hour, load_read, fault_code, fault_remark, user_id)
    VALUES (?,?,?,?,?,?,?)
");
$stmt->execute([
    $date, $fdr, $hour, $load,
    $load == 0 ? $fault : null,
    $load == 0 ? $remark : null,
    $payroll
]);

header("Location: /dashboard.php");
