<?php
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!in_array($_SESSION['role'], ['UL2','UL3','UL4','UL5','UL6'])) {
    exit('Unauthorized');
}

$payroll = $_SESSION['payroll_id'];

$fdr33  = $_POST['fdr33_code'];
$type   = trim($_POST['interruption_type']);
$loss   = (float)$_POST['load_loss'];
$out    = $_POST['datetime_out'];
$in     = $_POST['datetime_in'];

$reason = $_POST['reason_for_interruption'] ?? null;
$res    = $_POST['resolution'] ?? null;
$weather= $_POST['weather_condition'] ?? null;
$delay  = $_POST['reason_for_delay'] ?? null;
$other  = $_POST['other_reasons'] ?? null;

/* time validation */
if (strtotime($in) <= strtotime($out)) {
    exit('Restoration time must be after outage time.');
}

/* enforce other reason */
if ($delay === 'others' && empty($other)) {
    exit('Specify other reason for delay.');
}

$stmt = $pdo->prepare("
    INSERT INTO interruptions
    (
        fdr33_code, interruption_type, load_loss,
        datetime_out, datetime_in,
        reason_for_interruption, resolution,
        weather_condition, reason_for_delay,
        other_reasons, user_id
    )
    VALUES (?,?,?,?,?,?,?,?,?,?,?)
");

$stmt->execute([
    $fdr33, $type, $loss,
    $out, $in,
    $reason, $res,
    $weather, $delay,
    $other, $payroll
]);

header("Location: /dashboard.php");
