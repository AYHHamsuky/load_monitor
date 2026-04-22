<?php
/**
 * AJAX Handler: Delete 33kV Interruption
 * Path: /public/ajax/interruption_delete.php
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

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$interruptionId = $input['interruption_id'] ?? 0;

if (!$interruptionId) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid interruption ID'
    ]);
    exit;
}

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
        'message' => 'You can only delete your own records'
    ]);
    exit;
}

// Check if same day
$isSameDay = (date('Y-m-d', strtotime($interruption['datetime_out'])) === date('Y-m-d'));

if (!$isSameDay) {
    echo json_encode([
        'success' => false,
        'message' => 'Deletion is only allowed on the same day.'
    ]);
    exit;
}

try {
    $result = Interruption::delete($interruptionId, $user['payroll_id']);
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'System error: ' . $e->getMessage()
    ]);
}
