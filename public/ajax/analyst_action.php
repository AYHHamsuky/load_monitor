<?php
/**
 * AJAX Handler: Analyst Action on Correction
 * Path: /public/ajax/analyst_action.php
 */

header('Content-Type: application/json');

require '../../app/bootstrap.php';
require '../../app/models/Correction.php';

// Ensure user is logged in
if (!Auth::check()) {
    echo json_encode([
        'success' => false,
        'message' => 'Session expired. Please login again.'
    ]);
    exit;
}

$user = Auth::user();

// Validate role (UL3, UL4)
if (!in_array($user['role'], ['UL3', 'UL4'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Validate input
if (empty($_POST['correction_id']) || empty($_POST['action'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required fields'
    ]);
    exit;
}

$correctionId = (int)$_POST['correction_id'];
$action = $_POST['action'];
$remarks = $_POST['remarks'] ?? null;

try {
    if ($action === 'approve') {
        $result = Correction::analystApprove($correctionId, $user['payroll_id'], $remarks);
    } elseif ($action === 'reject') {
        if (empty($remarks)) {
            echo json_encode([
                'success' => false,
                'message' => 'Remarks are required when rejecting'
            ]);
            exit;
        }
        $result = Correction::analystReject($correctionId, $user['payroll_id'], $remarks);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
        exit;
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'System error: ' . $e->getMessage()
    ]);
}
