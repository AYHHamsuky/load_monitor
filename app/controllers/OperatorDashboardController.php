<?php
require __DIR__ . '/../config/auth.php';
require __DIR__ . '/../models/LoadReading33kv.php';
require __DIR__ . '/../models/LoadReading11kv.php';
require __DIR__ . '/../models/Interruption.php';

$user = Auth::user();

/* Fetch today's data */
$stationLoad = LoadReading33kv::today($user['assigned_33kv']);
$feederData  = LoadReading11kv::todayBy33kv($user['assigned_33kv']);
$interrupts  = Interruption::today($user['assigned_33kv']);

/* Build hour completeness map (1–24) */
$filledHours = array_column($stationLoad, 'periods');
$missingHours = array_diff(DAY_HOURS, $filledHours);

require __DIR__ . '/../views/layout/header.php';
require __DIR__ . '/../views/layout/sidebar.php';
require __DIR__ . '/../views/reports/operator_dashboard.php';
require __DIR__ . '/../views/layout/footer.php';
