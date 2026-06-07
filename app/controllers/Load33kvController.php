<?php
/**
 * Load33kvController — 33kV AJAX endpoint
 * Path: app/controllers/Load33kvController.php
 * POST to ?page=load_33kv
 *
 * ── 33kV Entry rules ─────────────────────────────────────────────────────────
 *   Batch A  Hours 00–11  free until 15:00   |  explanation until 01:00 next
 *   Batch B  Hours 12–19  free until 20:00   |  explanation until 01:00 next
 *   Batch C  Hours 20–23  free until 01:00 next  (no separate explanation window)
 *   After 01:00 next morning → Correction only
 */

require_once __DIR__ . '/../models/LoadReading33kv.php';
require_once __DIR__ . '/../models/LateEntryLog.php';

// ── Auth: UL2 only ────────────────────────────────────────────────────────────
$user = Auth::user();
if (!$user || $user['role'] !== 'UL2') {
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
$clockH  = (int)$now->format('G');  // 0-23

// ─────────────────────────────────────────────────────────────────────────────
// ACTION: log_late
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'log_late') {

    $result = LateEntryLog::log([
        'voltage_level' => '33kV',
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
    if (!isOpDateWindowOpen($opDate)) {
        echo json_encode([
            'success'         => false,
            'correction_only' => true,
            'message'         => 'The entry window for ' . $opDate . ' has closed. '
                               . 'Please use the Correction Request system.',
        ]);
        exit;
    }

    // ── RULE 2: Future-hour block ─────────────────────────────────────────────
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

    // ── RULE 3: Batch window ─────────────────────────────────────────────────
    $batch = getBatchWindow33kv($hour, $opDate);

    $isCurrentHour = ($clockH >= 1 && $hour === $clockH) || ($clockH === 0);
    $isFreeEntry   = $isCurrentHour && $batch['is_free'];

    if (!$isFreeEntry) {

        if ($batch['correction_only']) {
            echo json_encode([
                'success'         => false,
                'correction_only' => true,
                'message'         => 'The ' . $batch['label'] . ' batch window has closed at 01:00. '
                                   . 'Please use the Correction Request system.',
            ]);
            exit;
        }

        if (!LateEntryLog::hasPermission($user['payroll_id'], $opDate, $hour, '33kV')) {

            if ($hour < $clockH && $batch['is_free']) {
                $reason = sprintf(
                    'Hour %02d:00 is a past hour within the %s batch. '
                    . 'Please provide an explanation before submitting.',
                    $hour, $batch['label']
                );
            } elseif (!$batch['is_free'] && $batch['is_open']) {
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
    $result = LoadReading33kv::save([
        'entry_date'   => $opDate,
        'Fdr33kv_code' => trim($_POST['fdr33kv_code'] ?? ''),
        'entry_hour'   => $hour,
        'load_read'    => $loadRead,
        'fault_code'   => $faultCode ?: null,
        'fault_remark' => trim($_POST['fault_remark'] ?? '') ?: null,
        'user_id'      => $user['payroll_id'],
    ]);

    echo json_encode($result);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// ACTION: save_bulk
// Accepts an array of rows pasted from Excel/spreadsheet and saves each via
// the same validation pipeline used by save_load.  Returns per-row outcomes
// so the UI can show a green/red status next to every line the user pasted.
//
// POST fields:
//   rows                JSON array of {fdr33kv_code, entry_hour, load_read, fault_code?, fault_remark?}
//   bulk_explanation    Optional — applied to any row that needs a late-entry
//                       explanation (saves the user typing it for each row).
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'save_bulk') {

    $rowsJson = $_POST['rows'] ?? '';
    $rows     = json_decode($rowsJson, true);
    if (!is_array($rows) || empty($rows)) {
        echo json_encode(['success' => false, 'message' => 'No rows provided.']);
        exit;
    }

    $bulkExplanation = trim($_POST['bulk_explanation'] ?? '');

    if (!isOpDateWindowOpen($opDate)) {
        echo json_encode([
            'success'         => false,
            'correction_only' => true,
            'message'         => 'The entry window for ' . $opDate . ' has closed. Use the Correction Request system.',
        ]);
        exit;
    }

    $results   = [];
    $okCount   = 0;
    $failCount = 0;

    foreach ($rows as $idx => $row) {

        $code      = trim($row['fdr33kv_code'] ?? '');
        $hour      = isset($row['entry_hour']) ? (int)$row['entry_hour'] : -1;
        $loadRead  = (isset($row['load_read']) && $row['load_read'] !== '')
                         ? (float)$row['load_read'] : 0.0;
        $faultCode = trim($row['fault_code']   ?? '');
        $remark    = trim($row['fault_remark'] ?? '');

        $rowRes = [
            'row'    => (int)$idx,
            'code'   => $code,
            'hour'   => $hour,
            'load'   => $loadRead,
            'fault'  => $faultCode,
        ];

        if ($code === '') {
            $results[] = $rowRes + ['success' => false, 'message' => 'Missing feeder code'];
            $failCount++; continue;
        }
        if ($hour < 0 || $hour > 23) {
            $results[] = $rowRes + ['success' => false, 'message' => 'Hour must be 0–23'];
            $failCount++; continue;
        }

        // Future-hour block
        if ($clockH >= 1 && $hour > $clockH) {
            $results[] = $rowRes + ['success' => false, 'message' => "Hour {$hour}:00 has not yet occurred"];
            $failCount++; continue;
        }

        // Batch window — current hour is free, past hours may need explanation
        $batch         = getBatchWindow33kv($hour, $opDate);
        $isCurrentHour = ($clockH >= 1 && $hour === $clockH) || ($clockH === 0);
        $isFreeEntry   = $isCurrentHour && $batch['is_free'];

        if (!$isFreeEntry) {

            if ($batch['correction_only']) {
                $results[] = $rowRes + ['success' => false,
                    'message' => "Batch {$batch['label']} window has closed — needs Correction Request"];
                $failCount++; continue;
            }

            if (!LateEntryLog::hasPermission($user['payroll_id'], $opDate, $hour, '33kV')) {

                // Use the shared bulk explanation if provided
                if ($bulkExplanation !== '') {
                    $logRes = LateEntryLog::log([
                        'voltage_level' => '33kV',
                        'user_id'       => $user['payroll_id'],
                        'iss_code'      => $user['iss_code'] ?? '',
                        'log_date'      => $opDate,
                        'specific_hour' => $hour,
                        'explanation'   => $bulkExplanation,
                    ]);
                    if (empty($logRes['success'])) {
                        $results[] = $rowRes + ['success' => false,
                            'message' => 'Late explanation failed: ' . ($logRes['message'] ?? 'unknown')];
                        $failCount++; continue;
                    }
                } else {
                    $results[] = $rowRes + ['success' => false,
                        'late_entry' => true,
                        'message' => "Late explanation required for hour {$hour}:00 (batch {$batch['label']})"];
                    $failCount++; continue;
                }
            }
        }

        // Save it
        $saveRes = LoadReading33kv::save([
            'entry_date'   => $opDate,
            'Fdr33kv_code' => $code,
            'entry_hour'   => $hour,
            'load_read'    => $loadRead,
            'fault_code'   => $faultCode ?: null,
            'fault_remark' => $remark    ?: null,
            'user_id'      => $user['payroll_id'],
        ]);

        if (!empty($saveRes['success'])) {
            $results[] = $rowRes + ['success' => true, 'message' => $saveRes['message'] ?? 'Saved'];
            $okCount++;
        } else {
            $results[] = $rowRes + ['success' => false, 'message' => $saveRes['message'] ?? 'Save failed'];
            $failCount++;
        }
    }

    echo json_encode([
        'success'    => $okCount > 0,
        'ok_count'   => $okCount,
        'fail_count' => $failCount,
        'total'      => count($rows),
        'results'    => $results,
    ]);
    exit;
}

// ── Unknown action ────────────────────────────────────────────────────────────
echo json_encode(['success' => false, 'message' => 'Unknown action: ' . htmlspecialchars($action)]);
exit;
