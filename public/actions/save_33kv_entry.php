<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if ($_SESSION['role'] !== 'UL2') exit('Unauthorized');

$date = date('Y-m-d');
$payroll = $_SESSION['payroll_id'];

$fdr   = $_POST['fdr33kv_code'];
$hour  = (int)$_POST['entry_hour'];
$load  = (float)$_POST['load_read'];
$fault = $_POST['fault_code'] ?? null;
$remark = $_POST['fault_remark'] ?? null;

/* protect hour */
$chk = $pdo->prepare("
    SELECT 1 FROM fdr33kv_data
    WHERE entry_date=? AND Fdr33kv_code=? AND entry_hour=?
");
$chk->execute([$date,$fdr,$hour]);
if ($chk->fetch()) exit("Entry exists");

/* enforce fault rule */
if ($load==0 && empty($fault)) exit("Fault required");

$stmt = $pdo->prepare("
    INSERT INTO fdr33kv_data
    (entry_date,Fdr33kv_code,entry_hour,load_read,fault_code,fault_remark,user_id)
    VALUES (?,?,?,?,?,?,?)
");
$stmt->execute([
    $date,$fdr,$hour,$load,
    $load==0?$fault:null,
    $load==0?$remark:null,
    $payroll
]);

header("Location:/dashboard.php");
