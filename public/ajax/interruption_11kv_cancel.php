<?php
/**
 * AJAX Handler: Cancel an 11kV ticket (Amendment 6)
 * Path: /public/ajax/interruption_11kv_cancel.php
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
    $id     = (int)($_POST['interruption_id'] ?? 0);
    $reason = trim($_POST['cancel_reason'] ?? '');

    if (!$id) {
        ob_end_clean();
        echo json_encode(['success'=>false,'message'=>'Invalid interruption ID.']);
        exit;
    }
    if (empty($reason)) {
        ob_end_clean();
        echo json_encode(['success'=>false,'message'=>'Please provide a reason for cancellation.']);
        exit;
    }

    $result = Interruption11kv::cancel($id, $user['payroll_id'], $reason);

    ob_end_clean();
    echo json_encode($result);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success'=>false,'message'=>'System error: '.$e->getMessage()]);
}
