<?php
/**
 * AJAX Handler: Log New 33kV Interruption
 * Path: /public/ajax/interruption_log.php
 */

header('Content-Type: application/json');

require '../../app/bootstrap.php';
require '../../app/models/Interruption.php';

// Ensure user is logged in
if (!Auth::check()) {
    echo json_encode([
        'success' => false,
        'message' => 'Session expired. Please login again.'
    ]);
    exit;
}

$user = Auth::user();

// Validate role (UL2 only)
if ($user['role'] !== 'UL2') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

try {
    // Basic validation for required fields
    if (empty($_POST['interruption_code'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Interruption code is required'
        ]);
        exit;
    }

    if (empty($_POST['datetime_out'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Interruption date/time is required'
        ]);
        exit;
    }

    // Validate that datetime_out is today or future (not past)
    $datetimeOut = strtotime($_POST['datetime_out']);
    $today = strtotime(date('Y-m-d 00:00:00')); // Start of today
    
    if ($datetimeOut < $today) {
        echo json_encode([
            'success' => false,
            'message' => 'Cannot log interruptions for past dates. Only today or future dates are allowed.'
        ]);
        exit;
    }

    $result = Interruption::log([
        'interruption_code'        => $_POST['interruption_code'],
        'fdr33kv_code'             => $_POST['fdr33kv_code'] ?? '',
        'interruption_type'        => $_POST['interruption_type'] ?? '',
        'load_loss'                => $_POST['load_loss'] ?? 0,
        'datetime_out'             => $_POST['datetime_out'] ?? '',
        'datetime_in'              => $_POST['datetime_in'] ?? '',
        'reason_for_interruption'  => $_POST['reason_for_interruption'] ?? '',
        'resolution'               => $_POST['resolution'] ?? null,
        'weather_condition'        => $_POST['weather_condition'] ?? null,
        'reason_for_delay'         => $_POST['reason_for_delay'] ?? null,
        'other_reasons'            => $_POST['other_reasons'] ?? null,
        'user_id'                  => $user['payroll_id']
    ]);

    echo json_encode($result);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'System error: ' . $e->getMessage()
    ]);
}
