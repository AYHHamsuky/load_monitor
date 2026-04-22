<?php
/**
 * AJAX: Check if a feeder/date/hour slot has an existing entry
 *       AND return the status of that hour for UI purposes.
 * Path: /public/ajax/check_blank_hour.php
 *
 * POST params:
 *   feeder_code      string
 *   entry_date       Y-m-d  (op-date)
 *   entry_hour       int    0-23
 *   correction_type  '11kV' | '33kV'
 *
 * Response JSON:
 *   is_blank         bool   – true if no data exists yet for this cell
 *   hour_state       string – 'free' | 'late' | 'correction_only' | 'future'
 *   batch_label      string – e.g. "08:00–16:59"
 *   free_deadline    string – e.g. "18:00"
 *   entry_deadline   string – e.g. "01:00 next day"
 */

ob_start(); ini_set('display_errors', '0');
header('Content-Type: application/json');
require '../../app/bootstrap.php';
require_once '../../app/models/LateEntryLog.php';

if (!Auth::check()) {
    ob_end_clean();
    echo json_encode(['is_blank' => false, 'hour_state' => 'unknown']);
    exit;
}

$feederCode  = trim($_POST['feeder_code']     ?? '');
$entryDate   = trim($_POST['entry_date']      ?? '');
$entryHour   = (int)($_POST['entry_hour']     ?? -1);
$voltage     = trim($_POST['correction_type'] ?? '11kV');

if (!$feederCode || !$entryDate || $entryHour < 0 || $entryHour > 23) {
    ob_end_clean();
    echo json_encode(['is_blank' => false, 'hour_state' => 'unknown']);
    exit;
}

$table     = $voltage === '33kV' ? 'fdr33kv_data' : 'fdr11kv_data';
$feederCol = $voltage === '33kV' ? 'fdr33kv_code' : 'Fdr11kv_code';

try {
    $db   = Database::connect();
    $stmt = $db->prepare("
        SELECT 1 FROM {$table}
        WHERE entry_date  = ?
          AND {$feederCol} = ?
          AND entry_hour  = ?
        LIMIT 1
    ");
    $stmt->execute([$entryDate, $feederCode, $entryHour]);
    $exists = (bool)$stmt->fetch();

    // Determine hour state for UI
    $opDate  = getOperationalDate();
    $now     = new DateTime();
    $clockH  = (int)$now->format('G');  // 0-23

    // Is this entry for a different (past) operational date?
    if ($entryDate !== $opDate) {
        ob_end_clean();
        echo json_encode([
            'is_blank'       => !$exists,
            'hour_state'     => 'correction_only',
            'batch_label'    => '—',
            'free_deadline'  => '—',
            'entry_deadline' => '—',
        ]);
        exit;
    }

    // Get batch window for this hour
    $batch = ($voltage === '11kV')
        ? getBatchWindow11kv($entryHour, $opDate)
        : getBatchWindow33kv($entryHour, $opDate);

    // Determine state
    if ($batch['correction_only']) {
        $state = 'correction_only';
    } elseif ($clockH >= 1 && $entryHour > $clockH) {
        $state = 'future';
    } elseif ($batch['is_free'] && (($clockH >= 1 && $entryHour === $clockH) || $clockH === 0)) {
        $state = 'free';
    } else {
        $state = 'late';
    }

    ob_end_clean();
    echo json_encode([
        'is_blank'       => !$exists,
        'hour_state'     => $state,
        'batch_label'    => $batch['label'],
        'free_deadline'  => $batch['free_deadline'],
        'entry_deadline' => $batch['entry_deadline'],
    ]);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['is_blank' => false, 'hour_state' => 'unknown']);
}
