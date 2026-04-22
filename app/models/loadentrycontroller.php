<?php
require '../app/config/auth.php';
require '../app/models/Feeder11kv.php';
require '../app/models/LoadReading.php';

$user = Auth::user();

if (!$user['can_11kv']) {
    die("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $load = $_POST['load'] !== '' ? (float)$_POST['load'] : null;

    if ($load !== null && $load > MAX_LOAD) {
        die("Load exceeds maximum");
    }

    LoadReading::save11kv([
        'feeder' => $_POST['feeder'],
        'hour'   => $_POST['hour'],
        'load'   => $load,
        'fault'  => $_POST['fault'] ?: null,
        'remark' => $_POST['remark'],
        'user'   => $user['payroll_id']
    ]);
}

$feeders = Feeder11kv::by33kv($user['assigned_33kv']);

require '../app/views/load_entry/11kv_form.php';
