<?php
/**
 * AJAX Handler: Stage 1 — Log new 33kV interruption
 * Path: /public/ajax/interruption_log.php
 */
ob_start(); ini_set('display_errors', '0');
header('Content-Type: application/json');
require '../../app/bootstrap.php';
require '../../app/models/Interruption.php';
require '../../app/models/InterruptionApproval.php';

if (!Auth::check()) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
    exit;
}
$user = Auth::user();
if ($user['role'] !== 'UL2') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    // Stage-1 required fields only
    $required = ['fdr33kv_code', 'interruption_type', 'interruption_code', 'datetime_out'];
    $missing  = [];
    foreach ($required as $f) { if (empty($_POST[$f])) $missing[] = $f; }
    if ($missing) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Missing fields: ' . implode(', ', $missing)]);
        exit;
    }

    $datetimeOut = strtotime($_POST['datetime_out']);
    if ($datetimeOut < strtotime(date('Y-m-d 00:00:00'))) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Cannot log interruptions for past dates.']);
        exit;
    }

    $db = Database::connect();
    $cs = $db->prepare("SELECT approval_requirement FROM interruption_codes WHERE interruption_code = ?");
    $cs->execute([$_POST['interruption_code']]);
    $ci = $cs->fetch(PDO::FETCH_ASSOC);
    $requiresApproval = ($ci && $ci['approval_requirement'] === 'YES') ? 'YES' : 'NO';

    $result = Interruption::stage1([
        'fdr33kv_code'      => $_POST['fdr33kv_code'],
        'interruption_type' => $_POST['interruption_type'],
        'interruption_code' => $_POST['interruption_code'],
        'datetime_out'      => $_POST['datetime_out'],
        'weather_condition' => $_POST['weather_condition'] ?? null,
        'approval_note'     => $_POST['approval_note']     ?? null,
        'requires_approval' => $requiresApproval,
        'user_id'           => $user['payroll_id'],
    ]);

    // For approval-required interruptions, create the approval request now
    if ($result['success'] && $requiresApproval === 'YES') {
        $ar = InterruptionApproval::createApprovalRequest([
            'interruption_id'   => $result['interruption_id'],
            'interruption_type' => '33kV',
            'requester_id'      => $user['payroll_id'],
            'requester_name'    => $user['staff_name'] ?? $user['payroll_id'],
        ]);
        if ($ar['success']) {
            $db->prepare("UPDATE interruptions SET approval_id = ? WHERE id = ?")
               ->execute([$ar['approval_id'], $result['interruption_id']]);
        }
        $result['message'] = 'Interruption saved and sent for UL3/UL4 approval. Stage 2 will unlock once approved.';
    }

    ob_end_clean();
    echo json_encode($result);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
}
