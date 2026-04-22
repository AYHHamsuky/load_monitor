<?php
/**
 * AJAX Handler: Stage 2 — Complete 11kV interruption
 * Path: /public/ajax/interruption_11kv_complete.php
 *
 * Amendment 3: Server re-validates datetime_in not >30 min in future.
 * Amendment 4: Blocked if still AWAITING_APPROVAL.
 */
ob_start(); ini_set('display_errors', '0');
header('Content-Type: application/json');
require '../../app/bootstrap.php';
require '../../app/models/Interruption11kv.php';

if (!Auth::check()) {
    ob_end_clean();
    echo json_encode(['success'=>false,'message'=>'Session expired.']);
    exit;
}
$user = Auth::user();
if ($user['role'] !== 'UL1') {
    ob_end_clean();
    echo json_encode(['success'=>false,'message'=>'Unauthorized.']);
    exit;
}

try {
    $id = (int)($_POST['interruption_id'] ?? 0);
    if (!$id) {
        ob_end_clean();
        echo json_encode(['success'=>false,'message'=>'Invalid interruption ID.']);
        exit;
    }

    // Verify the record belongs to this user's station
    $db = Database::connect();
    $ck = $db->prepare("
        SELECT i.id FROM interruptions_11kv i
        INNER JOIN fdr11kv f ON f.fdr11kv_code = i.fdr11kv_code
        WHERE i.id = ? AND f.fdr33kv_code = ?
    ");
    $ck->execute([$id, $user['assigned_33kv_code']]);
    if (!$ck->fetch()) {
        ob_end_clean();
        echo json_encode(['success'=>false,'message'=>'Record not found or not in your station.']);
        exit;
    }

    $result = Interruption11kv::stage2($id, [
        'datetime_in'             => $_POST['datetime_in']             ?? '',
        'load_loss'               => $_POST['load_loss']               ?? 0,
        'reason_for_interruption' => $_POST['reason_for_interruption'] ?? '',
        'resolution'              => $_POST['resolution']              ?? null,
        'reason_for_delay'        => $_POST['reason_for_delay']        ?? null,
        'other_reasons'           => $_POST['other_reasons']           ?? null,
        'completed_by'            => $user['payroll_id'],
    ]);

    ob_end_clean();
    echo json_encode($result);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success'=>false,'message'=>'System error: '.$e->getMessage()]);
}
