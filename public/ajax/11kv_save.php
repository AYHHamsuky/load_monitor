<?php
/**
 * public/ajax/11kv_save.php
 *
 * Unified AJAX endpoint for:
 *   action=save_load   — save / update a single hourly load reading
 *   action=log_late    — log a late-entry explanation
 *
 * Always responds with JSON.
 */

header('Content-Type: application/json');

// Bootstrap (adjust path to match your project layout)
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/config/auth.php';
require_once __DIR__ . '/../../app/models/LoadReading11kv.php';
require_once __DIR__ . '/../../app/models/LateEntryLog.php';

// ── Auth check ────────────────────────────────────────────────────────────────
$user = Auth::user();
if (!$user || !$user['can_11kv']) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// ── Input ─────────────────────────────────────────────────────────────────────
$action = $_POST['action'] ?? '';

// ── Batch-time-window helper ──────────────────────────────────────────────────
/**
 * Returns the allowed submission deadline (as a DateTime) for a given hour.
 *
 * Batches and their cut-offs (server local time):
 *   Hours 01-07  → deadline 08:00 same day
 *   Hours 08-14  → deadline 15:00 same day
 *   Hours 15-19  → deadline 20:00 same day
 *   Hours 20-24  → deadline 01:00 NEXT day
 *
 * @param int $hour  1-24
 * @return array  ['batch_label'=>string, 'deadline'=>DateTime, 'is_open'=>bool,
 *                 'hour_from'=>int, 'hour_to'=>int]
 */
function getBatchInfo(int $hour): array
{
    $now   = new DateTime();
    $today = $now->format('Y-m-d');

    if ($hour >= 1 && $hour <= 7) {
        $deadline = new DateTime("{$today} 08:00:00");
        return [
            'batch_label' => '01:00 – 07:00',
            'deadline'    => $deadline,
            'is_open'     => $now <= $deadline,
            'hour_from'   => 1,
            'hour_to'     => 7,
        ];
    }

    if ($hour >= 8 && $hour <= 14) {
        $deadline = new DateTime("{$today} 15:00:00");
        return [
            'batch_label' => '08:00 – 14:00',
            'deadline'    => $deadline,
            'is_open'     => $now <= $deadline,
            'hour_from'   => 8,
            'hour_to'     => 14,
        ];
    }

    if ($hour >= 15 && $hour <= 19) {
        $deadline = new DateTime("{$today} 20:00:00");
        return [
            'batch_label' => '15:00 – 19:00',
            'deadline'    => $deadline,
            'is_open'     => $now <= $deadline,
            'hour_from'   => 15,
            'hour_to'     => 19,
        ];
    }

    // Hours 20-24: deadline is 01:00 the next day
    $tomorrow = (new DateTime())->modify('+1 day')->format('Y-m-d');
    $deadline = new DateTime("{$tomorrow} 01:00:00");
    return [
        'batch_label' => '20:00 – 24:00',
        'deadline'    => $deadline,
        'is_open'     => $now <= $deadline,
        'hour_from'   => 20,
        'hour_to'     => 24,
    ];
}

// ─────────────────────────────────────────────────────────────────────────────
// ACTION: log_late  — officer submits explanation for a closed batch
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'log_late') {

    $result = LateEntryLog::log([
        'user_id'     => $user['payroll_id'],
        'iss_code'    => $user['iss_code'],
        'log_date'    => date('Y-m-d'),
        'hour_from'   => (int)($_POST['hour_from'] ?? 0),
        'hour_to'     => (int)($_POST['hour_to']   ?? 0),
        'explanation' => trim($_POST['explanation'] ?? ''),
    ]);

    echo json_encode($result);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// ACTION: save_load  — save or update one hourly reading
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'save_load') {

    $hour       = (int)($_POST['entry_hour']   ?? 0);
    $feederCode = trim($_POST['Fdr11kv_code']  ?? '');
    $loadRead   = $_POST['load_read'] !== '' ? (float)$_POST['load_read'] : 0.0;
    $faultCode  = trim($_POST['fault_code']    ?? '');
    $faultRemark= trim($_POST['fault_remark']  ?? '');
    $today      = date('Y-m-d');

    // ── Time-window check ─────────────────────────────────────────────────────
    $batch = getBatchInfo($hour);

    if (!$batch['is_open']) {
        // Window closed — check whether officer already logged an explanation
        if (!LateEntryLog::hasPermission($user['payroll_id'], $today, $hour)) {
            echo json_encode([
                'success'       => false,
                'late_entry'    => true,
                'batch_label'   => $batch['batch_label'],
                'deadline'      => $batch['deadline']->format('H:i'),
                'hour_from'     => $batch['hour_from'],
                'hour_to'       => $batch['hour_to'],
                'message'       => "The submission window for the {$batch['batch_label']} batch has closed "
                                  . "(deadline was {$batch['deadline']->format('H:i')}). "
                                  . 'Please log an explanation to proceed.'
            ]);
            exit;
        }
        // Permission exists — fall through and save
    }

    // ── Delegate to model ─────────────────────────────────────────────────────
    $result = LoadReading11kv::save([
        'entry_date'   => $today,
        'Fdr11kv_code' => $feederCode,
        'entry_hour'   => $hour,
        'load_read'    => $loadRead,
        'fault_code'   => $faultCode ?: null,
        'fault_remark' => $faultRemark ?: null,
        'user_id'      => $user['payroll_id'],
    ]);

    echo json_encode($result);
    exit;
}

// Unknown action
echo json_encode(['success' => false, 'message' => 'Unknown action']);
