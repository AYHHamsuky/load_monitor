<?php
/**
 * AJAX Handler: Submit Correction Request
 * Path: /public/ajax/correction_request.php
 *
 * FIX: ob_start() + error suppression ensures ONLY valid JSON is ever sent back,
 * even if PHP emits notices/warnings (e.g. about blank_hour_reason column not
 * existing yet). Previously any stray PHP output before/after the JSON body
 * caused JSON.parse() to throw "Response Error" on the client even though the
 * record had already been saved successfully.
 */

ob_start();                          // capture any stray PHP output
ini_set('display_errors', '0');      // never let PHP errors bleed into the body
error_reporting(E_ALL);

header('Content-Type: application/json');

require '../../app/bootstrap.php';
require '../../app/models/Correction.php';

// Ensure user is logged in
if (!Auth::check()) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
    exit;
}

$user = Auth::user();

// Validate role
if (!in_array($user['role'], ['UL1', 'UL2'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $result = Correction::request([
        'feeder_code'        => $_POST['feeder_code']        ?? '',
        'entry_date'         => $_POST['entry_date']         ?? '',
        'entry_hour'         => $_POST['entry_hour']         ?? '',
        'correction_type'    => $_POST['correction_type']    ?? '11kV',
        'field_to_correct'   => $_POST['field_to_correct']   ?? '',
        'new_value'          => $_POST['new_value']          ?? '',
        'reason'             => $_POST['reason']             ?? '',
        'blank_hour_reason'  => $_POST['blank_hour_reason']  ?? '',
        'requested_by'       => $user['payroll_id']
    ]);

    ob_end_clean();                  // discard any stray output before echoing JSON
    echo json_encode($result);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode([
        'success' => false,
        'message' => 'System error: ' . $e->getMessage()
    ]);
}
