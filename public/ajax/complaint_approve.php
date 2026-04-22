<?php
// public/ajax/complaint_approve.php

require_once __DIR__ . '/../../app/bootstrap.php';

header('Content-Type: application/json');

// Only UL4 can approve/reject assignments
if (!Guard::hasRole('UL4')) {
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Only Managers (UL4) can approve assignments.'
    ]);
    exit;
}

$user = Auth::user();
$db = Database::getInstance();

try {
    $complaint_id = $_POST['complaint_id'] ?? '';
    $action = $_POST['action'] ?? ''; // 'approve' or 'reject'
    
    // Validation
    if (empty($complaint_id)) {
        throw new Exception('Complaint ID is required');
    }
    
    if (!in_array($action, ['approve', 'reject'])) {
        throw new Exception('Invalid action');
    }
    
    // Check if complaint exists and is assigned
    $stmt = $db->prepare("
        SELECT status, assigned_to 
        FROM complaint_log 
        WHERE complaint_id = ?
    ");
    $stmt->execute([$complaint_id]);
    $complaint = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$complaint) {
        throw new Exception('Complaint not found');
    }
    
    if ($complaint['status'] !== 'Assigned') {
        throw new Exception('Can only approve/reject complaints with Assigned status');
    }
    
    if ($action === 'approve') {
        // Approve - move to In Progress
        $stmt = $db->prepare("
            UPDATE complaint_log 
            SET 
                status = 'In Progress',
                updated_at = NOW()
            WHERE complaint_id = ?
        ");
        $stmt->execute([$complaint_id]);
        
        $message = "Complaint assignment approved. Status changed to In Progress.";
        
    } else {
        // Reject - back to Pending and clear assignment
        $stmt = $db->prepare("
            UPDATE complaint_log 
            SET 
                status = 'Pending',
                assigned_to = NULL,
                updated_at = NOW()
            WHERE complaint_id = ?
        ");
        $stmt->execute([$complaint_id]);
        
        $message = "Complaint assignment rejected. Returned to Pending status for reassignment.";
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
