<?php
/**
 * AJAX Handler: Submit 33kV Correction Request
 * Path: /public/ajax/correction_request_33kv.php
 */

header('Content-Type: application/json');

require '../../app/bootstrap.php';
require '../../app/models/Correction33kv.php';

// Ensure user is logged in
if (!Auth::check()) {
    echo json_encode([
        'success' => false,
        'message' => 'Session expired. Please login again.'
    ]);
    exit;
}

$user = Auth::user();

// Validate role - Only UL2 can submit 33kV corrections
if ($user['role'] !== 'UL2') {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access. Only UL2 users can submit 33kV correction requests.'
    ]);
    exit;
}

try {
    $result = Correction33kv::request([
        'feeder_code'       => $_POST['feeder_code'] ?? '',
        'entry_date'        => $_POST['entry_date'] ?? '',
        'entry_hour'        => $_POST['entry_hour'] ?? '',
        'field_to_correct'  => $_POST['field_to_correct'] ?? '',
        'new_value'         => $_POST['new_value'] ?? '',
        'reason'            => $_POST['reason'] ?? '',
        'requested_by'      => $user['payroll_id']
    ]);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("33kV Correction Request Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'System error: ' . $e->getMessage()
    ]);
}
