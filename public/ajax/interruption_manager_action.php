<?php
/**
 * AJAX Handler: Manager Final Action on Interruption Approval
 * Path: /public/ajax/interruption_manager_action.php
 */

header('Content-Type: application/json');

require '../../app/bootstrap.php';
require '../../app/models/InterruptionApproval.php';

// Ensure user is logged in
if (!Auth::check()) {
    echo json_encode([
        'success' => false,
        'message' => 'Session expired. Please login again.'
    ]);
    exit;
}

$user = Auth::user();

// Validate role (UL4 only)
if ($user['role'] !== 'UL4') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized: Only UL4 can give final approval'
    ]);
    exit;
}

try {
    $approvalId = $_POST['approval_id'] ?? 0;
    $action = $_POST['action'] ?? '';
    $remarks = $_POST['remarks'] ?? '';
    
    if (!$approvalId) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid approval ID'
        ]);
        exit;
    }
    
    if (!in_array($action, ['approve', 'reject'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
        exit;
    }
    
    $actionType = strtoupper($action) . 'D';
    
    $result = InterruptionApproval::managerAction(
        $approvalId,
        $actionType,
        $user['payroll_id'],
        $user['staff_name'] ?? $user['payroll_id'],
        $remarks
    );
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'System error: ' . $e->getMessage()
    ]);
}
