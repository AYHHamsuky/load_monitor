<?php
/**
 * AJAX Handler: Stage 1 — Log new 11kV interruption
 * Path: /public/ajax/interruption_11kv_log.php
 *
 * Amendment 1: PLANNED interruptions bypass approval check entirely.
 */
ob_start(); ini_set('display_errors', '0');
header('Content-Type: application/json');
require '../../app/bootstrap.php';
require '../../app/models/Interruption11kv.php';
require '../../app/models/InterruptionApproval.php';

if (!Auth::check()) {
    ob_end_clean();
    echo json_encode(['success'=>false,'message'=>'Session expired. Please login again.']);
    exit;
}
$user = Auth::user();
if ($user['role'] !== 'UL1') {
    ob_end_clean();
    echo json_encode(['success'=>false,'message'=>'Unauthorized access.']);
    exit;
}

try {
    $required = ['fdr11kv_code','interruption_type','interruption_code','datetime_out'];
    $missing  = [];
    foreach ($required as $f) { if (empty($_POST[$f])) $missing[] = $f; }
    if ($missing) {
        ob_end_clean();
        echo json_encode(['success'=>false,'message'=>'Missing fields: '.implode(', ',$missing)]);
        exit;
    }

    if (strtotime($_POST['datetime_out']) < strtotime(date('Y-m-d 00:00:00'))) {
        ob_end_clean();
        echo json_encode(['success'=>false,'message'=>'Cannot log interruptions for past dates.']);
        exit;
    }

    $db = Database::connect();

    // Amendment 1: PLANNED type always bypasses approval
    $interruptionType = strtoupper(trim($_POST['interruption_type']));
    if ($interruptionType === 'PLANNED') {
        $requiresApproval = 'NO';
    } else {
        $cs = $db->prepare("SELECT approval_requirement FROM interruption_codes WHERE interruption_code = ?");
        $cs->execute([$_POST['interruption_code']]);
        $ci = $cs->fetch(PDO::FETCH_ASSOC);
        $requiresApproval = ($ci && $ci['approval_requirement'] === 'YES') ? 'YES' : 'NO';
    }

    $result = Interruption11kv::stage1([
        'fdr11kv_code'      => $_POST['fdr11kv_code'],
        'interruption_type' => $_POST['interruption_type'],
        'interruption_code' => $_POST['interruption_code'],
        'datetime_out'      => $_POST['datetime_out'],
        'weather_condition' => $_POST['weather_condition'] ?? null,
        'approval_note'     => $_POST['approval_note']     ?? null,
        'requires_approval' => $requiresApproval,
        'user_id'           => $user['payroll_id'],
    ]);

    // Only create approval request for non-PLANNED tickets that need approval
    if ($result['success'] && $requiresApproval === 'YES') {
        $ar = InterruptionApproval::createApprovalRequest([
            'interruption_id'   => $result['interruption_id'],
            'interruption_type' => '11kV',
            'requester_id'      => $user['payroll_id'],
            'requester_name'    => $user['staff_name'] ?? $user['payroll_id'],
        ]);
        if ($ar['success']) {
            $db->prepare("UPDATE interruptions_11kv SET approval_id = ? WHERE id = ?")
               ->execute([$ar['approval_id'], $result['interruption_id']]);
        }
        $result['message'] = 'Interruption logged and sent for UL3/UL4 approval. Stage 2 unlocks once approved.';
    }

    ob_end_clean();
    echo json_encode($result);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success'=>false,'message'=>'System error: '.$e->getMessage()]);
}
