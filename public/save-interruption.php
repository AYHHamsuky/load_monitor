<?php
// File: /public/save-interruption.php

session_start();
require_once __DIR__ . '/../app/Core/Auth.php';
require_once __DIR__ . '/../app/Core/Database.php';

use App\Core\Auth;
use App\Core\Database;

$user = Auth::requireLogin();
$db = Database::get();

$out = strtotime($_POST['datetime_out']);
$in  = strtotime($_POST['datetime_in']);

if ($in <= $out) {
    exit('Invalid interruption time');
}

$duration = ($in - $out) / 3600;

$stmt = $db->prepare(
    "INSERT INTO interruptions
     (fdr33_code, Interruption_Type, Load_Loss,
      datetime_out, datetime_in, Duration,
      Reason_for_Interruption, Resolution,
      Weather_Condition, Reason_for_delay, user_id)
     VALUES (?,?,?,?,?,?,?,?,?,?,?)"
);

$stmt->execute([
    $_POST['fdr33kv_code'],
    $_POST['interruption_type'],
    $_POST['load_loss'],
    $_POST['datetime_out'],
    $_POST['datetime_in'],
    $duration,
    $_POST['reason'],
    $_POST['resolution'],
    $_POST['weather'],
    $_POST['delay_reason'],
    $user['payroll_id']
]);

header('Location: /dashboard.php');
