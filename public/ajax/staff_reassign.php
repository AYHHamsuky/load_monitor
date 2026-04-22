<?php
/**
 * AJAX Handler: Staff Reassignment v3.0
 * Path: /public/ajax/staff_reassign.php
 *
 * Handles three reassignment types:
 *   iss-iss  → UL1 moved to a different ISS (stays UL1)
 *   ts-ts    → UL2 moved to a different TS/feeder (stays UL2)
 *   cross    → Role switch: UL1→UL2 or UL2→UL1
 *
 * Logs every change to staff_reassignment_log.
 * Schema columns used:
 *   payroll_id, staff_name, old_role, new_role,
 *   field_changed, old_value, new_value,
 *   reason, reassigned_by, reassigned_at
 */

header('Content-Type: application/json');

require '../../app/bootstrap.php';

// ── Auth ──────────────────────────────────────────────────────────────────────
if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'Session expired. Please log in again.']);
    exit;
}
$user = Auth::user();
if ($user['role'] !== 'UL8') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized: Only Lead Dispatch (UL8) can reassign staff.']);
    exit;
}

// ── Inputs ────────────────────────────────────────────────────────────────────
$payroll_id     = trim($_POST['payroll_id']     ?? '');
$current_role   = trim($_POST['current_role']   ?? '');
$new_role       = trim($_POST['new_role']       ?? '');
$reassign_type  = trim($_POST['reassign_type']  ?? '');
$reason         = trim($_POST['reason']         ?? '');

// Common validators
if (!$payroll_id || !$reassign_type || !$reason) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields: payroll_id, reassign_type, or reason.']);
    exit;
}
if (!in_array($reassign_type, ['iss-iss', 'ts-ts', 'cross'], true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid reassign_type.']);
    exit;
}
if (strlen($reason) < 10) {
    echo json_encode(['success' => false, 'message' => 'Please provide a more detailed reason (at least 10 characters).']);
    exit;
}

try {
    $db = Database::connect();
    $db->beginTransaction();

    // ── Fetch current staff record ─────────────────────────────────────────────
    $stmt = $db->prepare("
        SELECT s.payroll_id, s.staff_name, s.role, s.iss_code, s.assigned_33kv_code,
               iss.iss_name,
               f33.fdr33kv_name, f33.ts_code, ts.station_name
        FROM   staff_details s
        LEFT JOIN iss_locations         iss ON iss.iss_code     = s.iss_code
        LEFT JOIN fdr33kv               f33 ON f33.fdr33kv_code = s.assigned_33kv_code
        LEFT JOIN transmission_stations ts  ON ts.ts_code       = f33.ts_code
        WHERE  s.payroll_id = ?
    ");
    $stmt->execute([$payroll_id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$staff) {
        throw new Exception('Staff member not found.');
    }

    $changes      = [];  // array of [field_changed, old_value, new_value]
    $final_role   = $staff['role'];  // will be updated per type
    $update_sql   = '';
    $update_params = [];
    $success_msg  = '';

    // ═════════════════════════════════════════════════════════════════════════
    // TYPE: iss-iss — UL1 moved to a different ISS
    // ═════════════════════════════════════════════════════════════════════════
    if ($reassign_type === 'iss-iss') {
        if ($staff['role'] !== 'UL1') {
            throw new Exception('ISS→ISS reassignment is only valid for UL1 staff.');
        }
        $new_iss_code = trim($_POST['new_iss_code'] ?? '');
        if (!$new_iss_code) {
            throw new Exception('Please select a new ISS.');
        }

        // Verify ISS exists
        $iss_stmt = $db->prepare("SELECT iss_name FROM iss_locations WHERE iss_code = ?");
        $iss_stmt->execute([$new_iss_code]);
        $new_iss = $iss_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$new_iss) throw new Exception('Selected ISS does not exist.');

        if ($staff['iss_code'] === $new_iss_code) {
            throw new Exception('Staff is already assigned to ' . ($staff['iss_name'] ?? $new_iss_code) . '. No change made.');
        }

        $changes[] = ['iss_code', $staff['iss_code'], $new_iss_code];
        $update_sql    = "UPDATE staff_details SET iss_code = ? WHERE payroll_id = ?";
        $update_params = [$new_iss_code, $payroll_id];
        $final_role    = 'UL1';
        $success_msg   = "Reassigned {$staff['staff_name']} from ISS {$staff['iss_code']} to {$new_iss['iss_name']}.";
    }

    // ═════════════════════════════════════════════════════════════════════════
    // TYPE: ts-ts — UL2 moved to a different TS / 33kV feeder
    // ═════════════════════════════════════════════════════════════════════════
    elseif ($reassign_type === 'ts-ts') {
        if ($staff['role'] !== 'UL2') {
            throw new Exception('TS→TS reassignment is only valid for UL2 staff.');
        }
        $new_33kv_code = trim($_POST['new_33kv_code'] ?? '');
        if (!$new_33kv_code) {
            throw new Exception('Please select a new 33kV feeder.');
        }

        // Verify feeder exists and get TS
        $fdr_stmt = $db->prepare("
            SELECT f.fdr33kv_code, f.fdr33kv_name, f.ts_code, ts.station_name
            FROM fdr33kv f
            LEFT JOIN transmission_stations ts ON ts.ts_code = f.ts_code
            WHERE f.fdr33kv_code = ?
        ");
        $fdr_stmt->execute([$new_33kv_code]);
        $new_fdr = $fdr_stmt->fetch(PDO::FETCH_ASSOC);
        if (!$new_fdr) throw new Exception('Selected 33kV feeder does not exist.');

        if ($staff['assigned_33kv_code'] === $new_33kv_code) {
            throw new Exception('Staff is already assigned to ' . ($staff['fdr33kv_name'] ?? $new_33kv_code) . '. No change made.');
        }

        $changes[] = ['assigned_33kv_code', $staff['assigned_33kv_code'], $new_33kv_code];
        $update_sql    = "UPDATE staff_details SET assigned_33kv_code = ? WHERE payroll_id = ?";
        $update_params = [$new_33kv_code, $payroll_id];
        $final_role    = 'UL2';
        $success_msg   = "Reassigned {$staff['staff_name']} to {$new_fdr['station_name']} / {$new_fdr['fdr33kv_name']}.";
    }

    // ═════════════════════════════════════════════════════════════════════════
    // TYPE: cross — role switch UL1↔UL2
    // ═════════════════════════════════════════════════════════════════════════
    elseif ($reassign_type === 'cross') {
        if (!in_array($new_role, ['UL1', 'UL2'], true)) {
            throw new Exception('Please select a new role (UL1 or UL2) for cross-voltage reassignment.');
        }
        // Note: same role cross is allowed if they want to just change location via cross panel
        $final_role = $new_role;

        if ($new_role === 'UL1') {
            // Moving to UL1 — need new ISS
            // Check for cross_iss_code (from cross panel) or new_iss_code
            $new_iss_code = trim($_POST['cross_iss_code'] ?? $_POST['new_iss_code'] ?? '');
            if (!$new_iss_code) throw new Exception('Please select an ISS for the UL1 assignment.');

            $iss_stmt = $db->prepare("SELECT iss_name FROM iss_locations WHERE iss_code = ?");
            $iss_stmt->execute([$new_iss_code]);
            $new_iss = $iss_stmt->fetch(PDO::FETCH_ASSOC);
            if (!$new_iss) throw new Exception('Selected ISS does not exist.');

            // Log role change if applicable
            if ($staff['role'] !== 'UL1') {
                $changes[] = ['role', $staff['role'], 'UL1'];
            }
            // Log ISS change
            if ($staff['iss_code'] !== $new_iss_code) {
                $changes[] = ['iss_code', $staff['iss_code'], $new_iss_code];
            }
            // Log clearing 33kV
            if ($staff['assigned_33kv_code']) {
                $changes[] = ['assigned_33kv_code', $staff['assigned_33kv_code'], ''];
            }

            // assigned_33kv_code may be NOT NULL — try NULL, fall back to empty string
            $update_sql    = "UPDATE staff_details SET role='UL1', iss_code=?, assigned_33kv_code='' WHERE payroll_id=?";
            $update_params = [$new_iss_code, $payroll_id];
            $success_msg   = "Cross-voltage reassignment: {$staff['staff_name']} → UL1 at {$new_iss['iss_name']}.";

        } else {
            // Moving to UL2 — need new 33kV feeder
            $new_33kv_code = trim($_POST['cross_33kv_code'] ?? $_POST['new_33kv_code'] ?? '');
            if (!$new_33kv_code) throw new Exception('Please select a 33kV feeder for the UL2 assignment.');

            $fdr_stmt = $db->prepare("
                SELECT f.fdr33kv_code, f.fdr33kv_name, f.ts_code, ts.station_name
                FROM fdr33kv f
                LEFT JOIN transmission_stations ts ON ts.ts_code = f.ts_code
                WHERE f.fdr33kv_code = ?
            ");
            $fdr_stmt->execute([$new_33kv_code]);
            $new_fdr = $fdr_stmt->fetch(PDO::FETCH_ASSOC);
            if (!$new_fdr) throw new Exception('Selected 33kV feeder does not exist.');

            // Log role change if applicable
            if ($staff['role'] !== 'UL2') {
                $changes[] = ['role', $staff['role'], 'UL2'];
            }
            // Log feeder change
            if ($staff['assigned_33kv_code'] !== $new_33kv_code) {
                $changes[] = ['assigned_33kv_code', $staff['assigned_33kv_code'], $new_33kv_code];
            }
            // Log clearing ISS
            if ($staff['iss_code']) {
                $changes[] = ['iss_code', $staff['iss_code'], ''];
            }

            $update_sql    = "UPDATE staff_details SET role='UL2', assigned_33kv_code=?, iss_code='' WHERE payroll_id=?";
            $update_params = [$new_33kv_code, $payroll_id];
            $success_msg   = "Cross-voltage reassignment: {$staff['staff_name']} → UL2 at {$new_fdr['station_name']} / {$new_fdr['fdr33kv_name']}.";
        }
    }

    // ── Nothing changed? ──────────────────────────────────────────────────────
    if (empty($changes)) {
        throw new Exception('No changes detected — staff assignment is already the same.');
    }

    // ── Apply the UPDATE ──────────────────────────────────────────────────────
    $db->prepare($update_sql)->execute($update_params);

    // ── Write one log row per changed field ───────────────────────────────────
    $log = $db->prepare("
        INSERT INTO staff_reassignment_log
            (payroll_id, staff_name, old_role, new_role,
             field_changed, old_value, new_value,
             reason, reassigned_by, reassigned_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    foreach ($changes as [$field, $old_val, $new_val]) {
        $log->execute([
            $payroll_id,
            $staff['staff_name'],
            $staff['role'],         // role before this transaction
            $final_role,
            $field,
            $old_val,
            $new_val,
            $reason,
            $user['payroll_id'],
        ]);
    }

    $db->commit();

    echo json_encode([
        'success'       => true,
        'message'       => $success_msg . ' All changes have been logged.',
        'changes_count' => count($changes),
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
