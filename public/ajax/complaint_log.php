<?php
/**
 * AJAX Handler: Log New Complaint
 * Path: /public/ajax/complaint_log.php
 */

header('Content-Type: application/json');

require '../../app/bootstrap.php';
require '../../app/models/Complaint.php';

// Ensure user is logged in
if (!Auth::check()) {
    echo json_encode([
        'success' => false,
        'message' => 'Session expired. Please login again.'
    ]);
    exit;
}

$user = Auth::user();

// Validate role (UL1, UL2)
if (!in_array($user['role'], ['UL1', 'UL2'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

try {
    $result = Complaint::log([
        'feeder_code'       => $_POST['feeder_code'] ?? '',
        'complaint_type'    => $_POST['complaint_type'] ?? '',
        'complaint_source'  => $_POST['complaint_source'] ?? '',
        'affected_area'     => $_POST['affected_area'] ?? null,
        'customer_phone'    => $_POST['customer_phone'] ?? null,
        'customer_name'     => $_POST['customer_name'] ?? null,
        'complaint_details' => $_POST['complaint_details'] ?? '',
        'fault_location'    => $_POST['fault_location'] ?? null,
        'priority'          => $_POST['priority'] ?? 'MEDIUM',
        'logged_by'         => $user['payroll_id']
    ]);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'System error: ' . $e->getMessage()
    ]);
}
