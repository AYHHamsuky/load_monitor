<?php
// public/ajax/user_reset_password.php

require_once __DIR__ . '/../../app/bootstrap.php';

header('Content-Type: application/json');

// Only admins can reset passwords
if (!Guard::hasRole('UL6')) {
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Only administrators can reset passwords.'
    ]);
    exit;
}

$db = Database::getInstance();

try {
    $payroll_id = $_POST['payroll_id'] ?? '';
    
    if (empty($payroll_id)) {
        throw new Exception('Payroll ID is required');
    }
    
    // Check if user exists
    $stmt = $db->prepare("SELECT payroll_id, staff_name FROM staff_details WHERE payroll_id = ?");
    $stmt->execute([$payroll_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Generate random password
    $new_password = generateRandomPassword();
    $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
    
    // Update password
    $stmt = $db->prepare("UPDATE staff_details SET password_hash = ?, updated_at = NOW() WHERE payroll_id = ?");
    $stmt->execute([$password_hash, $payroll_id]);
    
    echo json_encode([
        'success' => true,
        'message' => "Password reset successfully for '{$user['staff_name']}'",
        'new_password' => $new_password
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Generate a random password
 */
function generateRandomPassword($length = 10) {
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $numbers = '0123456789';
    $special = '!@#$%';
    
    $all = $uppercase . $lowercase . $numbers . $special;
    
    $password = '';
    $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
    $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
    $password .= $numbers[random_int(0, strlen($numbers) - 1)];
    $password .= $special[random_int(0, strlen($special) - 1)];
    
    for ($i = 4; $i < $length; $i++) {
        $password .= $all[random_int(0, strlen($all) - 1)];
    }
    
    return str_shuffle($password);
}
