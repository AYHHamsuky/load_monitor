<?php
/**
 * AJAX Handler: Edit Stage 1 fields of an 11kV ticket (Amendment 6)
 * Path: /public/ajax/interruption_11kv_edit.php
 *
 * Allowed within 1 hour of started_at, before any approval/concurrence.
 * Action is written to ticket_edit_cancel_log.
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

    $result = Interruption11kv::editStage1($id, [
        'interruption_type' => $_POST['interruption_type'] ?? null,
        'interruption_code' => $_POST['interruption_code'] ?? null,
        'datetime_out'      => $_POST['datetime_out']      ?? null,
        'weather_condition' => $_POST['weather_condition'] ?? null,
        'approval_note'     => $_POST['approval_note']     ?? null,
        'edit_reason'       => trim($_POST['edit_reason']  ?? ''),
    ], $user['payroll_id']);

    ob_end_clean();
    echo json_encode($result);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success'=>false,'message'=>'System error: '.$e->getMessage()]);
}
