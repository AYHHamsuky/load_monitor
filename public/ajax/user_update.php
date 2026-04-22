<?php
// public/ajax/user_update.php

require_once __DIR__ . '/../../app/bootstrap.php';

header('Content-Type: application/json');

// Only admins can update users
if (!Guard::hasRole('UL6')) {
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Only administrators can update users.'
    ]);
    exit;
}

$db = Database::getInstance();

try {
    // Get form data
    $payroll_id = $_POST['payroll_id'] ?? '';
    $staff_name = trim($_POST['staff_name'] ?? '');
    $role = $_POST['role'] ?? '';
    $staff_level = trim($_POST['staff_level'] ?? '');
    $iss_code = $_POST['iss_code'] ?? null;
    $assigned_33kv_code = $_POST['assigned_33kv_code'] ?? null;
    $phone_no = trim($_POST['phone_no'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $is_active = isset($_POST['is_active']) && $_POST['is_active'] === 'Yes' ? 'Yes' : 'No';
    
    // Validation
    if (empty($payroll_id)) {
        throw new Exception('Payroll ID is required');
    }
    
    if (empty($staff_name)) {
        throw new Exception('Staff name is required');
    }
    
    if (empty($role)) {
        throw new Exception('Role is required');
    }
    
    if (!in_array($role, ['UL1', 'UL2', 'UL3', 'UL4', 'UL5', 'UL6'])) {
        throw new Exception('Invalid role selected');
    }
    
    // Role-specific validation
    if ($role === 'UL1' && empty($iss_code)) {
        throw new Exception('ISS assignment is required for UL1 users');
    }
    
    if ($role === 'UL2' && empty($assigned_33kv_code)) {
        throw new Exception('33kV feeder assignment is required for UL2 users');
    }
    
    // Check if user exists
    $stmt = $db->prepare("SELECT payroll_id FROM staff_details WHERE payroll_id = ?");
    $stmt->execute([$payroll_id]);
    if (!$stmt->fetch()) {
        throw new Exception('User not found');
    }
    
    // Check if email exists for another user (if provided)
    if (!empty($email)) {
        $stmt = $db->prepare("SELECT payroll_id FROM staff_details WHERE email = ? AND payroll_id != ?");
        $stmt->execute([$email, $payroll_id]);
        if ($stmt->fetch()) {
            throw new Exception('Email address already in use by another user');
        }
    }
    
    // Set NULL for non-applicable assignments
    if (!in_array($role, ['UL1'])) {
        $iss_code = null;
    }
    if (!in_array($role, ['UL2'])) {
        $assigned_33kv_code = null;
    }
    
    // Build update query
    $update_fields = [
        'staff_name = ?',
        'role = ?',
        'staff_level = ?',
        'iss_code = ?',
        'assigned_33kv_code = ?',
        'phone_no = ?',
        'email = ?',
        'is_active = ?',
        'updated_at = NOW()'
    ];
    
    $params = [
        $staff_name,
        $role,
        $staff_level,
        $iss_code,
        $assigned_33kv_code,
        $phone_no,
        $email,
        $is_active
    ];
    
    // If password is being changed
    if (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            throw new Exception('Password must be at least 6 characters');
        }
        $update_fields[] = 'password_hash = ?';
        $params[] = password_hash($new_password, PASSWORD_BCRYPT);
    }
    
    $params[] = $payroll_id;
    
    // Update user
    $sql = "UPDATE staff_details SET " . implode(', ', $update_fields) . " WHERE payroll_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    echo json_encode([
        'success' => true,
        'message' => "User '$staff_name' updated successfully!"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
