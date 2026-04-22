<?php
// public/ajax/complaint_assign.php

require_once __DIR__ . '/../../app/bootstrap.php';

header('Content-Type: application/json');

// Only UL3 can assign complaints
if (!Guard::hasRole('UL3')) {
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Only Analysts (UL3) can assign complaints.'
    ]);
    exit;
}

$user = Auth::user();
$db = Database::getInstance();

try {
    $complaint_id = $_POST['complaint_id'] ?? '';
    $assigned_to = $_POST['assigned_to'] ?? '';
    $assignment_notes = trim($_POST['assignment_notes'] ?? '');
    
    // Validation
    if (empty($complaint_id)) {
        throw new Exception('Complaint ID is required');
    }
    
    if (empty($assigned_to)) {
        throw new Exception('Please select a staff member to assign');
    }
    
    // Check if complaint exists and is pending
    $stmt = $db->prepare("SELECT status FROM complaint_log WHERE complaint_id = ?");
    $stmt->execute([$complaint_id]);
    $complaint = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$complaint) {
        throw new Exception('Complaint not found');
    }
    
    if ($complaint['status'] !== 'Pending') {
        throw new Exception('Can only assign complaints with Pending status');
    }
    
    // Check if staff exists
    $stmt = $db->prepare("SELECT staff_name FROM staff_details WHERE payroll_id = ? AND is_active = 'Yes'");
    $stmt->execute([$assigned_to]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$staff) {
        throw new Exception('Selected staff member not found or inactive');
    }
    
    // Update complaint
    $stmt = $db->prepare("
        UPDATE complaint_log 
        SET 
            assigned_to = ?,
            status = 'Assigned',
            updated_at = NOW()
        WHERE complaint_id = ?
    ");
    $stmt->execute([$assigned_to, $complaint_id]);
    
    echo json_encode([
        'success' => true,
        'message' => "Complaint #{$complaint_id} assigned to {$staff['staff_name']} successfully"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
