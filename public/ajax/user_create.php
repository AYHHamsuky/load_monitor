<?php
require_once __DIR__ . '/../../app/bootstrap.php';

header('Content-Type: application/json');

if (!Guard::hasRole('UL6')) {
    echo json_encode(['success' => false, 'message' => 'Access denied. Only administrators can create users.']);
    exit;
}

$db = Database::getInstance();

try {
    $payroll_id        = trim($_POST['payroll_id'] ?? '');
    $staff_name        = trim($_POST['staff_name'] ?? '');
    $role              = trim($_POST['role'] ?? '');
    $staff_level       = trim($_POST['staff_level'] ?? '');
    $iss_code          = trim($_POST['iss_code'] ?? '0');
    $assigned_33kv_code = trim($_POST['assigned_33kv_code'] ?? '0');
    $phone             = trim($_POST['phone'] ?? '');
    $email             = trim($_POST['email'] ?? '');
    $password          = $_POST['password'] ?? '';
    $is_active         = (isset($_POST['is_active']) && $_POST['is_active'] === 'Yes') ? 'Yes' : 'No';

    if (empty($payroll_id)) throw new Exception('Payroll ID is required');
    if (empty($staff_name)) throw new Exception('Staff name is required');
    if (empty($role))       throw new Exception('Role is required');
    if (!in_array($role, ['UL1','UL2','UL3','UL4','UL5','UL6','UL7','UL8'])) {
        throw new Exception('Invalid role selected');
    }
    if (empty($password))            throw new Exception('Password is required');
    if (strlen($password) < 6)       throw new Exception('Password must be at least 6 characters');
    if ($role === 'UL1' && empty($iss_code)) throw new Exception('ISS assignment required for UL1');
    if ($role === 'UL2' && empty($assigned_33kv_code)) throw new Exception('33kV feeder required for UL2');

    // Check duplicate payroll
    $chk = $db->prepare("SELECT payroll_id FROM staff_details WHERE payroll_id = ?");
    $chk->execute([$payroll_id]);
    if ($chk->fetch()) throw new Exception('Payroll ID already exists');

    // Generate placeholder email if blank (email is NOT NULL UNIQUE)
    if (empty($email)) {
        $email = $payroll_id . '@ke.staff';
    } else {
        $chk2 = $db->prepare("SELECT payroll_id FROM staff_details WHERE email = ?");
        $chk2->execute([$email]);
        if ($chk2->fetch()) throw new Exception('Email address already in use');
    }

    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Clear location for non-applicable roles
    if ($role !== 'UL1') $iss_code = '0';
    if ($role !== 'UL2') $assigned_33kv_code = '0';

    $db->prepare("
        INSERT INTO staff_details
            (payroll_id, staff_name, role, staff_level, iss_code, assigned_33kv_code,
             sv_code, phone, email, password_hash, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, '100000', ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    ")->execute([
        $payroll_id, $staff_name, $role, $staff_level, $iss_code, $assigned_33kv_code,
        $phone, $email, $password_hash, $is_active,
    ]);

    echo json_encode(['success' => true, 'message' => "User '$staff_name' created successfully!"]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
