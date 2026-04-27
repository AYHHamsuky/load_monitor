<?php
require_once __DIR__ . '/../../app/bootstrap.php';

header('Content-Type: application/json');

if (!Guard::hasRole('UL6')) {
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

$db = Database::getInstance();

try {
    $role               = trim($_POST['role'] ?? '');
    $iss_code           = trim($_POST['iss_code'] ?? '0');
    $assigned_33kv_code = trim($_POST['assigned_33kv_code'] ?? '0');
    $default_password   = $_POST['default_password'] ?? 'password@123';

    if (!in_array($role, ['UL1','UL2','UL3','UL4','UL5','UL6','UL7','UL8'])) {
        throw new Exception('Invalid role selected');
    }
    if ($role === 'UL1' && (empty($iss_code) || $iss_code === '0')) {
        throw new Exception('ISS location is required when importing as UL1');
    }
    if ($role === 'UL2' && (empty($assigned_33kv_code) || $assigned_33kv_code === '0')) {
        throw new Exception('33kV feeder is required when importing as UL2');
    }
    if (strlen($default_password) < 6) {
        throw new Exception('Default password must be at least 6 characters');
    }

    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('CSV file upload failed or no file selected');
    }

    $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
    if (!$handle) throw new Exception('Cannot read uploaded file');

    // Read header row to detect format
    $header = fgetcsv($handle);
    // Handle UTF-8 BOM
    if ($header && isset($header[0])) {
        $header[0] = ltrim($header[0], "\xEF\xBB\xBF");
    }

    /*
     * Two supported formats:
     *
     * OLD (8 cols):  Full Name | Payroll ID | Last name | Middle Name | First Name | Department | Unit | Job Role Title
     * NEW (21 cols): S/N | Full Name | Payroll ID | Title | Last name | Middle Name | First Name | Department | Unit | Job Role Title |
     *                Job Grade | Job Level | Start Date | Region | Location | Division | Line Manager Name | LM Phone |
     *                Official Email | Active Phone | Marital Status
     */
    $col_count    = is_array($header) ? count($header) : 0;
    $is_new_format = $col_count >= 18;

    $idx_name   = $is_new_format ? 1  : 0;
    $idx_payroll = $is_new_format ? 2  : 1;
    $idx_email  = $is_new_format ? 18 : -1;
    $idx_phone  = $is_new_format ? 19 : -1;

    $password_hash = password_hash($default_password, PASSWORD_BCRYPT);

    if ($role !== 'UL1') $iss_code = '0';
    if ($role !== 'UL2') $assigned_33kv_code = '0';

    $imported = 0;
    $skipped  = 0;
    $errors   = [];
    $row_num  = 1;

    $insert = $db->prepare("
        INSERT INTO staff_details
            (payroll_id, staff_name, role, staff_level, iss_code, assigned_33kv_code,
             sv_code, phone, email, password_hash, is_active, created_at, updated_at)
        VALUES (?, ?, ?, '', ?, ?, '100000', ?, ?, ?, 'Yes', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    ");

    while (($row = fgetcsv($handle)) !== false) {
        $row_num++;
        if (count($row) <= $idx_payroll) continue;

        $full_name  = trim($row[$idx_name]   ?? '');
        $payroll_id = trim($row[$idx_payroll] ?? '');

        if (empty($payroll_id) || empty($full_name) || !is_numeric($payroll_id)) continue;

        // Skip existing payroll IDs
        $chk = $db->prepare("SELECT payroll_id FROM staff_details WHERE payroll_id = ?");
        $chk->execute([$payroll_id]);
        if ($chk->fetch()) {
            $skipped++;
            continue;
        }

        // Use real email if available, else generate placeholder
        $raw_email = ($idx_email >= 0 && isset($row[$idx_email])) ? trim($row[$idx_email]) : '';
        $raw_email = strtolower($raw_email); // normalise to lowercase

        if (!empty($raw_email) && filter_var($raw_email, FILTER_VALIDATE_EMAIL)) {
            $email = $raw_email;
            // Ensure not already taken
            $echk = $db->prepare("SELECT payroll_id FROM staff_details WHERE email = ?");
            $echk->execute([$email]);
            if ($echk->fetch()) {
                $email = $payroll_id . '@ke.staff';
            }
        } else {
            $email = $payroll_id . '@ke.staff';
        }

        $phone = ($idx_phone >= 0 && isset($row[$idx_phone])) ? trim($row[$idx_phone]) : '';

        try {
            $insert->execute([
                $payroll_id, $full_name, $role,
                $iss_code, $assigned_33kv_code,
                $phone, $email, $password_hash,
            ]);
            $imported++;
        } catch (Exception $ex) {
            $errors[] = "Row $row_num ($payroll_id): " . $ex->getMessage();
        }
    }

    fclose($handle);

    $fmt = $is_new_format ? 'new (21-column) format' : 'old (8-column) format';
    $msg = "Import complete using <em>$fmt</em>: <strong>$imported</strong> users added, <strong>$skipped</strong> skipped (already exist).";
    if ($errors) {
        $msg .= ' Errors: ' . implode('; ', array_slice($errors, 0, 5));
    }

    echo json_encode([
        'success'  => true,
        'message'  => $msg,
        'imported' => $imported,
        'skipped'  => $skipped,
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
