<?php
/**
 * AJAX Handler: Single-form 33kV interruption (combined Stage 1 + Stage 2).
 * Path: /public/ajax/interruption_simple_log.php
 *
 * Driven by the simplified UL2 form that matches the dispatch team's
 * Excel sheet layout: feeder, type, load-loss, date/time out, date/time in,
 * reason, resolution, weather — all in one form, one submit.
 *
 * Behaviour:
 *   • Same-day entries  → form_status = COMPLETED immediately
 *   • Past-day entries  → require approval_note, form_status = AWAITING_APPROVAL
 *                         and an approval request is opened for UL3/UL4
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
    echo json_encode(['success' => false, 'message' => 'Unauthorized access — UL2 only.']);
    exit;
}

try {
    // Combine the four date/time pieces into datetime strings if the form
    // posted them separately (the new single form uses split date+time inputs).
    $datetimeOut = trim($_POST['datetime_out'] ?? '');
    if ($datetimeOut === '' && !empty($_POST['date_out']) && !empty($_POST['time_out'])) {
        $datetimeOut = $_POST['date_out'] . ' ' . $_POST['time_out'];
    }
    $datetimeIn = trim($_POST['datetime_in'] ?? '');
    if ($datetimeIn === '' && !empty($_POST['date_in']) && !empty($_POST['time_in'])) {
        $datetimeIn = $_POST['date_in'] . ' ' . $_POST['time_in'];
    }

    $required = ['fdr33kv_code', 'interruption_code', 'load_loss', 'reason_for_interruption'];
    $missing  = [];
    foreach ($required as $f) {
        // NOTE: parens required — `??` has LOWER precedence than `===`,
        // so `$_POST[$f] ?? '' === ''` parses as `$_POST[$f] ?? true`.
        if (($_POST[$f] ?? '') === '') $missing[] = $f;
    }
    if ($datetimeOut === '') $missing[] = 'datetime_out';
    if ($datetimeIn  === '') $missing[] = 'datetime_in';
    if ($missing) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Missing fields: ' . implode(', ', $missing)]);
        exit;
    }

    // Normalise datetime strings to "Y-m-d H:i:s"
    $dtoTs = strtotime($datetimeOut);
    $dtiTs = strtotime($datetimeIn);
    if (!$dtoTs || !$dtiTs) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid date/time value.']);
        exit;
    }
    $datetimeOut = date('Y-m-d H:i:s', $dtoTs);
    $datetimeIn  = date('Y-m-d H:i:s', $dtiTs);

    $result = Interruption::logSingle([
        'fdr33kv_code'            => $_POST['fdr33kv_code'],
        'interruption_code'       => $_POST['interruption_code'],
        'datetime_out'            => $datetimeOut,
        'datetime_in'             => $datetimeIn,
        'load_loss'               => $_POST['load_loss'],
        'reason_for_interruption' => trim($_POST['reason_for_interruption']),
        'resolution'              => trim($_POST['resolution']        ?? '') ?: null,
        'weather_condition'       => trim($_POST['weather_condition'] ?? '') ?: null,
        'reason_for_delay'        => trim($_POST['reason_for_delay']  ?? '') ?: null,
        'other_reasons'           => trim($_POST['other_reasons']     ?? '') ?: null,
        'approval_note'           => trim($_POST['approval_note']     ?? '') ?: null,
        'user_id'                 => $user['payroll_id'],
        'user_name'               => $user['staff_name'] ?? $user['payroll_id'],
    ]);

    ob_end_clean();
    echo json_encode($result);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
}
