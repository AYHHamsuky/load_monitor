<?php
/**
 * LoadEntryController — 11kV AJAX endpoint
 * Path: app/controllers/LoadEntryController.php
 * POST to ?page=load_entry
 *
 * ── Hour scheme ──────────────────────────────────────────────────────────────
 *   Hours 0-23.  Hour 0 = 00:00–00:59.
 *
 * ── Operational date ─────────────────────────────────────────────────────────
 *   During 00:xx (hour 0 of the NEXT calendar day) we are still in the
 *   PREVIOUS calendar date's batch.  The entry window closes at 01:00.
 *   From 01:00 onward → next operational date begins.
 *
 * ── 11kV Entry rules ─────────────────────────────────────────────────────────
 *   Batch A  Hours 00–07  free until 09:00   |  explanation until 01:00 next
 *   Batch B  Hours 08–16  free until 18:00   |  explanation until 01:00 next
 *   Batch C  Hours 17–23  free until 01:00 next  (no separate explanation window)
 *   After 01:00 next morning → NO entry, NO explanation → Correction only
 *
 * ── Future hours ─────────────────────────────────────────────────────────────
 *   Any hour > currentClockHour (for the current calendar date) is blocked.
 *   Exception: during 00:xx we are on opDate = yesterday, so ALL hours 0-23
 *   for yesterday are past/current — none are future on that opDate.
 */

require_once __DIR__ . '/../models/LoadReading11kv.php';
require_once __DIR__ . '/../models/LateEntryLog.php';

// ── Auth: UL1 only ────────────────────────────────────────────────────────────
$user = Auth::user();
if (!$user || $user['role'] !== 'UL1') {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

header('Content-Type: application/json');

$action  = trim($_POST['action'] ?? '');
$opDate  = getOperationalDate();
$now     = new DateTime();
$clockH  = (int)$now->format('G');  // 0-23 real clock

// ─────────────────────────────────────────────────────────────────────────────
// ACTION: log_late
// Officer submits explanation before entering a past hour
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'log_late') {

    $result = LateEntryLog::log([
        'voltage_level' => '11kV',
        'user_id'       => $user['payroll_id'],
        'iss_code'      => $user['iss_code'] ?? '',
        'log_date'      => $opDate,
        'specific_hour' => (int)($_POST['specific_hour'] ?? -1),
        'explanation'   => trim($_POST['explanation']    ?? ''),
    ]);

    echo json_encode($result);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// ACTION: save_load
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'save_load') {

    $hour      = (int)($_POST['entry_hour']  ?? -1);
    $loadRead  = (isset($_POST['load_read']) && $_POST['load_read'] !== '')
                     ? (float)$_POST['load_read'] : 0.0;
    $faultCode = trim($_POST['fault_code']   ?? '');
    $isEdit    = (!empty($_POST['is_edit']) && $_POST['is_edit'] === '1');

    if ($hour < 0 || $hour > 23) {
        echo json_encode(['success' => false, 'message' => 'Invalid hour. Must be 0–23.']);
        exit;
    }

    // ── RULE 1: Entry window completely closed? ───────────────────────────────
    // This happens when the caller supplies an opDate that is NOT today's opDate.
    // (Shouldn't happen in normal flow, but defence-in-depth.)
    if (!isOpDateWindowOpen($opDate)) {
        echo json_encode([
            'success'          => false,
            'correction_only'  => true,
            'message'          => 'The entry window for ' . $opDate . ' has closed. '
                                . 'Please use the Correction Request system.',
        ]);
        exit;
    }

    // ── RULE 2: Future-hour block ─────────────────────────────────────────────
    // During 00:xx (clockH===0) we are submitting for yesterday (opDate).
    // All hours 0-23 on opDate are in the past, so no future-hour check needed.
    // At clockH >= 1 the current clock hour IS the slot boundary.
    if ($clockH >= 1 && $hour > $clockH) {
        echo json_encode([
            'success' => false,
            'future'  => true,
            'message' => sprintf(
                'Hour %02d:00 has not yet occurred. Future entries are not permitted.',
                $hour
            ),
        ]);
        exit;
    }

    // ── RULE 3: Batch window — free or needs explanation ─────────────────────
    $batch = getBatchWindow11kv($hour, $opDate);

    // Is the entry already free (within the free window)?
    // "Free" means: hour is current clock hour (or earlier during 00:xx window)
    // AND the free_deadline has not passed.
    $isCurrentHour = ($clockH >= 1 && $hour === $clockH)
                  || ($clockH === 0);  // During 00:xx, all opDate hours are "current batch"

    $isFreeEntry = $isCurrentHour && $batch['is_free'];

    if (!$isFreeEntry) {

        // Correction-only? Hard block.
        if ($batch['correction_only']) {
            echo json_encode([
                'success'         => false,
                'correction_only' => true,
                'message'         => 'The ' . $batch['label'] . ' batch window has closed at 01:00. '
                                   . 'Please use the Correction Request system.',
            ]);
            exit;
        }

        // Needs late-entry explanation — check if one was already logged
        if (!LateEntryLog::hasPermission($user['payroll_id'], $opDate, $hour, '11kV')) {

            if ($hour < $clockH && $batch['is_free']) {
                // Past hour but batch still in free window
                $reason = sprintf(
                    'Hour %02d:00 is a past hour within the %s batch. '
                    . 'Please provide an explanation before submitting.',
                    $hour, $batch['label']
                );
            } elseif (!$batch['is_free'] && $batch['is_open']) {
                // Batch free window closed but entry window still open
                $reason = sprintf(
                    'The free entry window for the %s batch closed at %s. '
                    . 'An explanation is required.',
                    $batch['label'], $batch['free_deadline']
                );
            } else {
                $reason = 'An explanation is required before this entry can be submitted.';
            }

            echo json_encode([
                'success'        => false,
                'late_entry'     => true,
                'batch_label'    => $batch['label'],
                'free_deadline'  => $batch['free_deadline'],
                'entry_deadline' => $batch['entry_deadline'],
                'hour_from'      => $batch['hour_from'],
                'hour_to'        => $batch['hour_to'],
                'message'        => $reason,
            ]);
            exit;
        }
    }

    // ── Delegate to model ─────────────────────────────────────────────────────
    $result = LoadReading11kv::save([
        'entry_date'   => $opDate,
        'Fdr11kv_code' => trim($_POST['Fdr11kv_code'] ?? ''),
        'entry_hour'   => $hour,
        'load_read'    => $loadRead,
        'fault_code'   => $faultCode ?: null,
        'fault_remark' => trim($_POST['fault_remark'] ?? '') ?: null,
        'user_id'      => $user['payroll_id'],
    ]);

    echo json_encode($result);
    exit;
}

// ── Unknown action ────────────────────────────────────────────────────────────
echo json_encode(['success' => false, 'message' => 'Unknown action: ' . htmlspecialchars($action)]);
exit;
