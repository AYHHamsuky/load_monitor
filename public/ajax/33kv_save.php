<?php
// public/ajax/33kv_save.php
//
// Drop-in replacement — all 33kV load entry AJAX handled here.
// No router needed; called directly as public/ajax/33kv_save.php
//
// 33kV batch windows (hours 0-23):
//   Hours 00-11  → free until 15:00, entry until 01:00 next day
//   Hours 12-19  → free until 20:00, entry until 01:00 next day
//   Hours 20-23  → free until 01:00 next day

require_once __DIR__ . '/../../app/bootstrap.php';
require_once __DIR__ . '/../../app/models/LateEntryLog.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
ob_start();

// ── Auth ───────────────────────────────────────────────────────────────────────
if (!Guard::hasRole('UL2')) {
    ob_end_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied. UL2 role required.']);
    exit;
}

$user = Auth::user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$action = trim($_POST['action'] ?? '');

// ─────────────────────────────────────────────────────────────────────────────
// ACTION: log_late  — officer submits explanation before entry is allowed
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'log_late') {
    $result = LateEntryLog::log([
        'voltage_level' => '33kV',
        'user_id'       => $user['payroll_id'],
        'iss_code'      => $user['iss_code'] ?? ($user['assigned_33kv_code'] ?? ''),
        'log_date'      => getOperationalDate(),
        'specific_hour' => (int)($_POST['specific_hour'] ?? 0),
        'explanation'   => trim($_POST['explanation'] ?? ''),
    ]);
    ob_end_clean();
    echo json_encode($result);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// ACTION: save_load  — save or update one hourly reading
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'save_load') {

    $hour      = (int)($_POST['entry_hour'] ?? 0);
    $loadRead  = (isset($_POST['load_read']) && $_POST['load_read'] !== '')
                     ? (float)$_POST['load_read'] : 0.0;
    $faultCode = trim($_POST['fault_code']   ?? '');
    $faultRemark = trim($_POST['fault_remark'] ?? '');
    $feederCode  = trim($_POST['fdr33kv_code'] ?? '');
    $today       = getOperationalDate();
    $now         = new DateTime();

    // ── Required fields ───────────────────────────────────────────────────────
    // Note: $hour can be 0 (midnight slot) — do NOT use !$hour
    if (!$feederCode || $hour < 0 || $hour > 23) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Missing required fields: feeder or hour']);
        exit;
    }

    // ── Rule 1: Future hours — hard block, no bypass ──────────────────────────
    // Hours are 0-23. During 00:xx (clockHour=0) we are in the previous
    // op-date's batch — all hours 0-23 for that date are past or current.
    $clockHour = (int)$now->format('G'); // 0-23

    // Only block future hours when clock is at 01:00 or later (not midnight window)
    if ($clockHour >= 1 && $hour > $clockHour) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'future'  => true,
            'message' => sprintf(
                'Hour %02d:00 has not yet occurred. Entries for future hours are not permitted.',
                $hour
            ),
        ]);
        exit;
    }

    // ── Rule 2: Batch window check ────────────────────────────────────────────
    $batch = getBatchWindow33kv($hour, $today);

    // Free entry: current hour slot AND free deadline not yet passed
    // During 00:xx (clockHour=0) all hours for op-date are "past batch" entries
    $isCurrentAndOpen = ($clockHour >= 1 && $hour === $clockHour && $batch['is_free'])
                     || ($clockHour === 0 && $batch['is_free']);

    if (!$isCurrentAndOpen) {
        if (!LateEntryLog::hasPermission($user['payroll_id'], $today, $hour, '33kV')) {
            if ($clockHour >= 1 && $hour < $clockHour && $batch['is_free']) {
                $reason = sprintf(
                    'Hour %02d:00 is a past hour. An explanation is required to submit this entry.',
                    $hour
                );
            } elseif (!$batch['is_free'] && $batch['is_open']) {
                $reason = sprintf(
                    'The free entry window for the %s batch closed at %s. An explanation is required.',
                    $batch['label'], $batch['free_deadline']
                );
            } elseif ($batch['correction_only']) {
                ob_end_clean();
                echo json_encode([
                    'success'         => false,
                    'correction_only' => true,
                    'message'         => 'The entry window for the ' . $batch['label']
                                       . ' batch has closed at 01:00. Please use the Correction Request system.',
                ]);
                exit;
            } else {
                $reason = 'An explanation is required to proceed with this entry.';
            }
            ob_end_clean();
            echo json_encode([
                'success'        => false,
                'late_entry'     => true,
                'batch_label'    => $batch['label'],
                'deadline'       => $batch['free_deadline'],
                'hour_from'      => $batch['hour_from'],
                'hour_to'        => $batch['hour_to'],
                'message'        => $reason,
            ]);
            exit;
        }
    }

    // ── Validate feeder exists ────────────────────────────────────────────────
    try {
        $db = Database::connect();

        $stmt = $db->prepare("SELECT fdr33kv_code, max_load FROM fdr33kv WHERE fdr33kv_code = ? LIMIT 1");
        $stmt->execute([$feederCode]);
        $feeder = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$feeder) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid feeder code']);
            exit;
        }

        // ── max_load check ────────────────────────────────────────────────────
        if ($feeder['max_load'] !== null && $loadRead > 0 && $loadRead > (float)$feeder['max_load']) {
            ob_end_clean();
            echo json_encode([
                'success'  => false,
                'message'  => 'Value entered exceeded the maximum allowed for the feeder',
                'max_load' => (float)$feeder['max_load'],
            ]);
            exit;
        }

        // ── load vs fault rule ────────────────────────────────────────────────
        if ($loadRead > 0) {
            $faultCode  = '';
            $faultRemark = '';
        } else {
            if (empty($faultCode)) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Fault code is required when load is zero']);
                exit;
            }
        }

        // ── Validate fault code ───────────────────────────────────────────────
        $allowedFaults = ['FO', 'BF', 'OS', 'DOff', 'MVR'];
        if (!empty($faultCode) && !in_array($faultCode, $allowedFaults)) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid fault code']);
            exit;
        }

        // ── Uniqueness check ──────────────────────────────────────────────────
        $check = $db->prepare("
            SELECT user_id FROM fdr33kv_data
            WHERE fdr33kv_code = ? AND entry_date = ? AND entry_hour = ?
            LIMIT 1
        ");
        $check->execute([$feederCode, $today, $hour]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            if ($existing['user_id'] !== $user['payroll_id']) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'This hour has already been entered by another staff member']);
                exit;
            }
            // Same user — delete then re-insert
            $db->prepare("
                DELETE FROM fdr33kv_data WHERE fdr33kv_code = ? AND entry_date = ? AND entry_hour = ?
            ")->execute([$feederCode, $today, $hour]);
        }

        // ── Insert ────────────────────────────────────────────────────────────
        $db->prepare("
            INSERT INTO fdr33kv_data
                (entry_date, fdr33kv_code, entry_hour, load_read, fault_code, fault_remark, user_id, timestamp)
            VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ")->execute([
            $today,
            $feederCode,
            $hour,
            $loadRead,
            $faultCode   ?: null,
            $faultRemark ?: null,
            $user['payroll_id'],
        ]);

        ob_end_clean();
        echo json_encode([
            'success' => true,
            'message' => $existing ? 'Load entry updated successfully' : 'Load entry saved successfully',
        ]);

    } catch (PDOException $e) {
        error_log('33kv_save.php DB error: ' . $e->getMessage());
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
    }
    exit;
}

// ── Unknown action ─────────────────────────────────────────────────────────────
ob_end_clean();
echo json_encode(['success' => false, 'message' => 'Unknown action']);
exit;


// (getBatchWindow33kv and getOperationalDate are provided by LateEntryLog.php)
