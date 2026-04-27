<?php
require_once __DIR__ . '/../../app/bootstrap.php';

header('Content-Type: application/json');

if (!Guard::hasAnyRole(['UL6', 'UL7'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied. Only administrators can update users.']);
    exit;
}

$db = Database::getInstance();

try {
    $payroll_id         = trim($_POST['payroll_id'] ?? '');
    $staff_name         = trim($_POST['staff_name'] ?? '');
    $role               = trim($_POST['role'] ?? '');
    $staff_level        = trim($_POST['staff_level'] ?? '');
    $iss_code           = trim($_POST['iss_code'] ?? '0');
    $assigned_33kv_code = trim($_POST['assigned_33kv_code'] ?? '0');
    $phone              = trim($_POST['phone'] ?? '');
    $email              = trim($_POST['email'] ?? '');
    $new_password       = $_POST['new_password'] ?? '';
    $is_active          = (isset($_POST['is_active']) && $_POST['is_active'] === 'Yes') ? 'Yes' : 'No';

    if (empty($payroll_id)) throw new Exception('Payroll ID is required');
    if (empty($staff_name)) throw new Exception('Staff name is required');
    if (empty($role))       throw new Exception('Role is required');
    if (!in_array($role, ['UL1','UL2','UL3','UL4','UL5','UL6','UL7','UL8'])) {
        throw new Exception('Invalid role selected');
    }
    if ($role === 'UL1' && (empty($iss_code) || $iss_code === '0')) {
        throw new Exception('ISS assignment is required for UL1 users');
    }
    if ($role === 'UL2' && (empty($assigned_33kv_code) || $assigned_33kv_code === '0')) {
        throw new Exception('33kV feeder assignment is required for UL2 users');
    }

    // User must exist
    $chk = $db->prepare("SELECT payroll_id FROM staff_details WHERE payroll_id = ?");
    $chk->execute([$payroll_id]);
    if (!$chk->fetch()) throw new Exception('User not found');

    // Email uniqueness (allow blank → keep existing)
    if (!empty($email)) {
        $echk = $db->prepare("SELECT payroll_id FROM staff_details WHERE email = ? AND payroll_id != ?");
        $echk->execute([$email, $payroll_id]);
        if ($echk->fetch()) throw new Exception('Email address already in use by another user');
    }

    // Clear location for non-applicable roles
    if ($role !== 'UL1') $iss_code = '0';
    if ($role !== 'UL2') $assigned_33kv_code = '0';

    $fields = [
        'staff_name = ?',
        'role = ?',
        'staff_level = ?',
        'iss_code = ?',
        'assigned_33kv_code = ?',
        'phone = ?',
        'is_active = ?',
        'updated_at = CURRENT_TIMESTAMP',
    ];
    $params = [$staff_name, $role, $staff_level, $iss_code, $assigned_33kv_code, $phone, $is_active];

    // Only update email if supplied (it is NOT NULL UNIQUE)
    if (!empty($email)) {
        $fields[] = 'email = ?';
        $params[]  = $email;
    }

    if (!empty($new_password)) {
        if (strlen($new_password) < 6) throw new Exception('Password must be at least 6 characters');
        $fields[] = 'password_hash = ?';
        $params[]  = password_hash($new_password, PASSWORD_BCRYPT);
    }

    $params[] = $payroll_id;
    $db->prepare("UPDATE staff_details SET " . implode(', ', $fields) . " WHERE payroll_id = ?")
       ->execute($params);

    echo json_encode(['success' => true, 'message' => "User '$staff_name' updated successfully!"]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
