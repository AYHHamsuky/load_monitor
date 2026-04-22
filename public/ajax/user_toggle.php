<?php
// public/ajax/user_toggle_status.php

require_once __DIR__ . '/../../app/bootstrap.php';

header('Content-Type: application/json');

// Only admins can toggle user status
if (!Guard::hasRole('UL6')) {
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Only administrators can modify user status.'
    ]);
    exit;
}

$db = Database::getInstance();

try {
    $payroll_id = $_POST['payroll_id'] ?? '';
    $action = $_POST['action'] ?? '';
    
    if (empty($payroll_id)) {
        throw new Exception('Payroll ID is required');
    }
    
    if (!in_array($action, ['activate', 'deactivate'])) {
        throw new Exception('Invalid action');
    }
    
    // Check if user exists
    $stmt = $db->prepare("SELECT payroll_id, staff_name, is_active FROM staff_details WHERE payroll_id = ?");
    $stmt->execute([$payroll_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Prevent admin from deactivating themselves
    $current_user = Auth::user();
    if ($action === 'deactivate' && $payroll_id === $current_user['payroll_id']) {
        throw new Exception('You cannot deactivate your own account');
    }
    
    // Update status
    $new_status = $action === 'activate' ? 'Yes' : 'No';
    
    $stmt = $db->prepare("UPDATE staff_details SET is_active = ?, updated_at = NOW() WHERE payroll_id = ?");
    $stmt->execute([$new_status, $payroll_id]);
    
    $message = $action === 'activate' 
        ? "User '{$user['staff_name']}' activated successfully" 
        : "User '{$user['staff_name']}' deactivated successfully";
    
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
