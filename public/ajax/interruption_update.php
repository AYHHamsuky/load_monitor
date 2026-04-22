<?php
/**
 * AJAX Handler: Update 33kV Interruption
 * Path: /public/ajax/interruption_update.php
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

$interruptionId = $_POST['interruption_id'] ?? 0;

// Check if user owns this interruption
$interruption = Interruption::getById($interruptionId);

if (!$interruption) {
    echo json_encode([
        'success' => false,
        'message' => 'Interruption not found'
    ]);
    exit;
}

if ($interruption['user_id'] !== $user['payroll_id']) {
    echo json_encode([
        'success' => false,
        'message' => 'You can only edit your own records'
    ]);
    exit;
}

// Check if same day
$isSameDay = (date('Y-m-d', strtotime($interruption['datetime_out'])) === date('Y-m-d'));

if (!$isSameDay) {
    echo json_encode([
        'success' => false,
        'message' => 'Editing is only allowed on the same day. Please use correction request for past records.'
    ]);
    exit;
}

try {
    $result = Interruption::update($interruptionId, [
        'interruption_code'       => $_POST['interruption_code'] ?? '',
        'interruption_type'       => $_POST['interruption_type'] ?? '',
        'load_loss'               => $_POST['load_loss'] ?? 0,
        'datetime_out'            => $_POST['datetime_out'] ?? '',
        'datetime_in'             => $_POST['datetime_in'] ?? '',
        'reason_for_interruption' => $_POST['reason_for_interruption'] ?? '',
        'resolution'              => $_POST['resolution'] ?? null,
        'weather_condition'       => $_POST['weather_condition'] ?? null,
        'reason_for_delay'        => $_POST['reason_for_delay'] ?? null,
        'other_reasons'           => $_POST['other_reasons'] ?? null
    ]);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'System error: ' . $e->getMessage()
    ]);
}
