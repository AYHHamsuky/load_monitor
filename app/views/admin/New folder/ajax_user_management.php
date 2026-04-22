<?php
// public/ajax/superadmin_user_management.php

/**
 * Super Admin User Management AJAX Handler
 * Create, Read, Update, Delete users (UL1-UL6)
 * 
 * @version 1.0
 */

require_once __DIR__ . '/../../app/bootstrap.php';

header('Content-Type: application/json');

// Only UL7 can manage all users
if (!Guard::hasRole('UL7')) {
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Only Super Admin can manage users.'
    ]);
    exit;
}

$action = $_POST['action'] ?? '';
$db = Database::getInstance();
$currentUser = Auth::user();

try {
    switch ($action) {
        case 'create_user':
            // Validate inputs
            $payroll_id = trim($_POST['payroll_id'] ?? '');
            $staff_name = trim($_POST['staff_name'] ?? '');
            $role = $_POST['role'] ?? '';
            $staff_level = trim($_POST['staff_level'] ?? '');
            $iss_code = $_POST['iss_code'] ?? null;
            $assigned_33kv_code = $_POST['assigned_33kv_code'] ?? null;
            $phone_no = trim($_POST['phone_no'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
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

            // UL7 can create UL1-UL6, but not other UL7s (security measure)
            if (!in_array($role, ['UL1', 'UL2', 'UL3', 'UL4', 'UL5', 'UL6'])) {
                throw new Exception('Invalid role. Only UL1-UL6 can be created.');
            }

            if (empty($password)) {
                throw new Exception('Password is required');
            }

            // Check password strength
            $passwordCheck = Auth::isPasswordStrong($password);
            if (!$passwordCheck['is_strong']) {
                throw new Exception('Password is not strong enough: ' . implode(', ', $passwordCheck['errors']));
            }

            // Role-specific validation
            if ($role === 'UL1' && empty($iss_code)) {
                throw new Exception('ISS assignment is required for UL1 users');
            }

            if ($role === 'UL2' && empty($assigned_33kv_code)) {
                throw new Exception('33kV feeder assignment is required for UL2 users');
            }

            // Check if payroll ID already exists
            $stmt = $db->prepare("SELECT payroll_id FROM staff_details WHERE payroll_id = ?");
            $stmt->execute([$payroll_id]);
            
            if ($stmt->fetch()) {
                throw new Exception('Payroll ID already exists');
            }

            // Check if email exists (if provided)
            if (!empty($email)) {
                $stmt = $db->prepare("SELECT payroll_id FROM staff_details WHERE email = ?");
                $stmt->execute([$email]);
                
                if ($stmt->fetch()) {
                    throw new Exception('Email address already in use');
                }
            }

            // Hash password
            $password_hash = password_hash($password, PASSWORD_BCRYPT);

            // Set NULL for non-applicable assignments
            if (!in_array($role, ['UL1'])) {
                $iss_code = null;
            }
            
            if (!in_array($role, ['UL2'])) {
                $assigned_33kv_code = null;
            }

            // Insert user
            $stmt = $db->prepare("
                INSERT INTO staff_details (
                    payroll_id, staff_name, role, staff_level,
                    iss_code, assigned_33kv_code, phone_no, email,
                    password_hash, is_active, created_at, last_password_change
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");

            $stmt->execute([
                $payroll_id,
                $staff_name,
                $role,
                $staff_level,
                $iss_code,
                $assigned_33kv_code,
                $phone_no,
                $email,
                $password_hash,
                $is_active
            ]);

            // Log the action
            AuditLogger::logUserManagement(
                'USER_CREATED',
                $payroll_id,
                null,
                [
                    'payroll_id' => $payroll_id,
                    'staff_name' => $staff_name,
                    'role' => $role,
                    'is_active' => $is_active
                ],
                ['created_by' => $currentUser['payroll_id']]
            );

            echo json_encode([
                'success' => true,
                'message' => "User '{$staff_name}' created successfully!"
            ]);
            break;

        case 'update_user':
            $payroll_id = trim($_POST['payroll_id'] ?? '');
            
            if (empty($payroll_id)) {
                throw new Exception('Payroll ID is required');
            }

            // Get current user data
            $stmt = $db->prepare("SELECT * FROM staff_details WHERE payroll_id = ?");
            $stmt->execute([$payroll_id]);
            $oldData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$oldData) {
                throw new Exception('User not found');
            }

            // Cannot edit other UL7s
            if ($oldData['role'] === 'UL7') {
                throw new Exception('Cannot edit Super Admin accounts');
            }

            // Build update query dynamically
            $updates = [];
            $params = [];
            $newData = [];

            if (isset($_POST['staff_name']) && !empty($_POST['staff_name'])) {
                $updates[] = "staff_name = ?";
                $params[] = trim($_POST['staff_name']);
                $newData['staff_name'] = trim($_POST['staff_name']);
            }

            if (isset($_POST['staff_level'])) {
                $updates[] = "staff_level = ?";
                $params[] = trim($_POST['staff_level']);
                $newData['staff_level'] = trim($_POST['staff_level']);
            }

            if (isset($_POST['phone_no'])) {
                $updates[] = "phone_no = ?";
                $params[] = trim($_POST['phone_no']);
                $newData['phone_no'] = trim($_POST['phone_no']);
            }

            if (isset($_POST['email'])) {
                $updates[] = "email = ?";
                $params[] = trim($_POST['email']);
                $newData['email'] = trim($_POST['email']);
            }

            if (isset($_POST['iss_code'])) {
                $updates[] = "iss_code = ?";
                $params[] = $_POST['iss_code'] ?: null;
                $newData['iss_code'] = $_POST['iss_code'] ?: null;
            }

            if (isset($_POST['assigned_33kv_code'])) {
                $updates[] = "assigned_33kv_code = ?";
                $params[] = $_POST['assigned_33kv_code'] ?: null;
                $newData['assigned_33kv_code'] = $_POST['assigned_33kv_code'] ?: null;
            }

            if (isset($_POST['is_active'])) {
                $updates[] = "is_active = ?";
                $params[] = $_POST['is_active'] === 'Yes' ? 'Yes' : 'No';
                $newData['is_active'] = $_POST['is_active'] === 'Yes' ? 'Yes' : 'No';
            }

            if (empty($updates)) {
                throw new Exception('No fields to update');
            }

            $updates[] = "updated_at = NOW()";
            $params[] = $payroll_id;

            $sql = "UPDATE staff_details SET " . implode(', ', $updates) . " WHERE payroll_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            // Log the update
            AuditLogger::logUserManagement(
                'USER_UPDATED',
                $payroll_id,
                $oldData,
                $newData,
                ['updated_by' => $currentUser['payroll_id']]
            );

            echo json_encode([
                'success' => true,
                'message' => 'User updated successfully!'
            ]);
            break;

        case 'delete_user':
            $payroll_id = trim($_POST['payroll_id'] ?? '');

            if (empty($payroll_id)) {
                throw new Exception('Payroll ID is required');
            }

            // Cannot delete yourself
            if ($payroll_id === $currentUser['payroll_id']) {
                throw new Exception('You cannot delete your own account');
            }

            // Get user info
            $stmt = $db->prepare("SELECT role, staff_name FROM staff_details WHERE payroll_id = ?");
            $stmt->execute([$payroll_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new Exception('User not found');
            }

            // Cannot delete other UL7s
            if ($user['role'] === 'UL7') {
                throw new Exception('Cannot delete Super Admin accounts');
            }

            // Soft delete - just deactivate
            $stmt = $db->prepare("
                UPDATE staff_details
                SET is_active = 'No', updated_at = NOW()
                WHERE payroll_id = ?
            ");

            $stmt->execute([$payroll_id]);

            // Force logout user sessions
            SessionManager::logoutUser($payroll_id);

            // Log the action
            AuditLogger::logUserManagement(
                'USER_DELETED',
                $payroll_id,
                ['is_active' => 'Yes'],
                ['is_active' => 'No'],
                [
                    'deleted_user' => $user['staff_name'],
                    'deleted_by' => $currentUser['payroll_id']
                ]
            );

            echo json_encode([
                'success' => true,
                'message' => "User '{$user['staff_name']}' has been deactivated."
            ]);
            break;

        case 'reset_password':
            $payroll_id = trim($_POST['payroll_id'] ?? '');
            $new_password = $_POST['new_password'] ?? '';

            if (empty($payroll_id)) {
                throw new Exception('Payroll ID is required');
            }

            if (empty($new_password)) {
                throw new Exception('New password is required');
            }

            // Check password strength
            $passwordCheck = Auth::isPasswordStrong($new_password);
            if (!$passwordCheck['is_strong']) {
                throw new Exception('Password is not strong enough: ' . implode(', ', $passwordCheck['errors']));
            }

            // Get user info
            $stmt = $db->prepare("SELECT staff_name, role FROM staff_details WHERE payroll_id = ?");
            $stmt->execute([$payroll_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new Exception('User not found');
            }

            // Cannot reset other UL7 passwords
            if ($user['role'] === 'UL7' && $payroll_id !== $currentUser['payroll_id']) {
                throw new Exception('Cannot reset password for other Super Admin accounts');
            }

            // Update password
            $password_hash = password_hash($new_password, PASSWORD_BCRYPT);

            $stmt = $db->prepare("
                UPDATE staff_details
                SET password_hash = ?,
                    last_password_change = NOW(),
                    failed_login_attempts = 0,
                    account_locked_until = NULL
                WHERE payroll_id = ?
            ");

            $stmt->execute([$password_hash, $payroll_id]);

            // Log the action
            AuditLogger::logUserManagement(
                'PASSWORD_RESET',
                $payroll_id,
                null,
                null,
                ['reset_by' => $currentUser['payroll_id']]
            );

            echo json_encode([
                'success' => true,
                'message' => "Password reset successfully for '{$user['staff_name']}'."
            ]);
            break;

        case 'unlock_account':
            $payroll_id = trim($_POST['payroll_id'] ?? '');

            if (empty($payroll_id)) {
                throw new Exception('Payroll ID is required');
            }

            $stmt = $db->prepare("
                UPDATE staff_details
                SET failed_login_attempts = 0,
                    account_locked_until = NULL
                WHERE payroll_id = ?
            ");

            $stmt->execute([$payroll_id]);

            AuditLogger::logUserManagement(
                'ACCOUNT_UNLOCKED',
                $payroll_id,
                null,
                null,
                ['unlocked_by' => $currentUser['payroll_id']]
            );

            echo json_encode([
                'success' => true,
                'message' => 'Account unlocked successfully.'
            ]);
            break;

        case 'get_all_users':
            $stmt = $db->query("
                SELECT 
                    s.*,
                    i.iss_name,
                    f.feeder_name as assigned_feeder_name,
                    (SELECT COUNT(*) FROM active_sessions WHERE user_id = s.payroll_id) as active_sessions
                FROM staff_details s
                LEFT JOIN iss_locations i ON s.iss_code = i.iss_code
                LEFT JOIN fdr33kv f ON s.assigned_33kv_code = f.feeder_code
                ORDER BY s.role, s.staff_name
            ");

            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'users' => $users,
                'count' => count($users)
            ]);
            break;

        case 'get_user_details':
            $payroll_id = trim($_POST['payroll_id'] ?? '');

            if (empty($payroll_id)) {
                throw new Exception('Payroll ID is required');
            }

            $stmt = $db->prepare("
                SELECT 
                    s.*,
                    i.iss_name,
                    f.feeder_name as assigned_feeder_name
                FROM staff_details s
                LEFT JOIN iss_locations i ON s.iss_code = i.iss_code
                LEFT JOIN fdr33kv f ON s.assigned_33kv_code = f.feeder_code
                WHERE s.payroll_id = ?
            ");

            $stmt->execute([$payroll_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                throw new Exception('User not found');
            }

            echo json_encode([
                'success' => true,
                'user' => $user
            ]);
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}