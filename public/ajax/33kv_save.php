<?php
// public/ajax/33kv_save.php
//
// 33kV load entry AJAX handler — NO time/batch window restrictions.
// Any past or current hour may be saved freely.
// Future hours (after current clock hour) are still blocked.
//
// Supports:
//   action=save_load    — save or overwrite one hourly reading
//   action=save_batch   — paste/bulk save multiple readings at once

require_once __DIR__ . '/../../app/bootstrap.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
ob_start();

// ── Auth ────────────────────────────────────────────────────────────────────
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

// ── Helpers ─────────────────────────────────────────────────────────────────

if (!function_exists('getOperationalDate')) {
    function getOperationalDate(): string {
        $now = new DateTime();
        return ((int)$now->format('G') === 0)
            ? (clone $now)->modify('-1 day')->format('Y-m-d')
            : $now->format('Y-m-d');
    }
}

/**
 * Save (or overwrite) a single hourly reading.
 * Returns ['success'=>bool, 'message'=>string]
 */
function saveOneReading(
    PDO    $db,
    string $payrollId,
    string $feederCode,
    int    $hour,
    float  $loadRead,
    string $faultCode,
    string $faultRemark,
    string $opDate
): array {

    // ── Validate feeder ─────────────────────────────────────────────────────
    $stmt = $db->prepare("SELECT fdr33kv_code, max_load FROM fdr33kv WHERE fdr33kv_code = ? LIMIT 1");
    $stmt->execute([$feederCode]);
    $feeder = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$feeder) {
        return ['success' => false, 'message' => "Invalid feeder code: {$feederCode}"];
    }

    // ── max_load check ──────────────────────────────────────────────────────
    if ($feeder['max_load'] !== null && $loadRead > 0 && $loadRead > (float)$feeder['max_load']) {
        return [
            'success'  => false,
            'message'  => "Value {$loadRead} exceeds max load {$feeder['max_load']} MW for feeder {$feederCode}",
            'max_load' => (float)$feeder['max_load'],
        ];
    }

    // ── load vs fault rule ──────────────────────────────────────────────────
    if ($loadRead > 0) {
        $faultCode   = '';
        $faultRemark = '';
    } else {
        if (empty($faultCode)) {
            return ['success' => false, 'message' => "Fault code required when load is 0 (hour {$hour})"];
        }
    }

    // ── Validate fault code against the master interruption_codes list ──────
    if (!empty($faultCode) && !FaultCodes::isValid($faultCode)) {
        return ['success' => false, 'message' => "Invalid fault code: {$faultCode}"];
    }

    // ── Overwrite check ─────────────────────────────────────────────────────
    $check = $db->prepare("
        SELECT user_id FROM fdr33kv_data
        WHERE fdr33kv_code = ? AND entry_date = ? AND entry_hour = ?
        LIMIT 1
    ");
    $check->execute([$feederCode, $opDate, $hour]);
    $existing = $check->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        // Any UL2 user may overwrite (restriction removed)
        $db->prepare("
            DELETE FROM fdr33kv_data
            WHERE fdr33kv_code = ? AND entry_date = ? AND entry_hour = ?
        ")->execute([$feederCode, $opDate, $hour]);
    }

    // ── Insert ──────────────────────────────────────────────────────────────
    $db->prepare("
        INSERT INTO fdr33kv_data
            (entry_date, fdr33kv_code, entry_hour, load_read, fault_code, fault_remark, user_id, timestamp)
        VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    ")->execute([
        $opDate,
        $feederCode,
        $hour,
        $loadRead,
        $faultCode   ?: null,
        $faultRemark ?: null,
        $payrollId,
    ]);

    return [
        'success' => true,
        'updated' => (bool)$existing,
        'message' => $existing ? 'Updated' : 'Saved',
    ];
}

// ────────────────────────────────────────────────────────────────────────────
// ACTION: save_load — single cell entry (modal submit)
// ────────────────────────────────────────────────────────────────────────────
if ($action === 'save_load') {

    $hour        = (int)($_POST['entry_hour'] ?? 0);
    $loadRead    = (isset($_POST['load_read']) && $_POST['load_read'] !== '')
                       ? (float)$_POST['load_read'] : 0.0;
    $faultCode   = trim($_POST['fault_code']   ?? '');
    $faultRemark = trim($_POST['fault_remark'] ?? '');
    $feederCode  = trim($_POST['fdr33kv_code'] ?? '');
    $today       = getOperationalDate();
    $now         = new DateTime();

    if (!$feederCode || $hour < 0 || $hour > 23) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Missing required fields: feeder or hour']);
        exit;
    }

    // ── Future hour block (only restriction kept) ───────────────────────────
    $clockHour = (int)$now->format('G');
    if ($clockHour >= 1 && $hour > $clockHour) {
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'future'  => true,
            'message' => sprintf(
                'Hour %02d:00 has not yet occurred. Future hours cannot be entered.',
                $hour
            ),
        ]);
        exit;
    }

    try {
        $db     = Database::connect();
        $result = saveOneReading($db, $user['payroll_id'], $feederCode, $hour, $loadRead, $faultCode, $faultRemark, $today);
        ob_end_clean();
        echo json_encode($result);
    } catch (PDOException $e) {
        error_log('33kv_save.php DB error: ' . $e->getMessage());
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
    }
    exit;
}

// ────────────────────────────────────────────────────────────────────────────
// ACTION: save_batch — multi-feeder paste from Excel
//
// POST body:
//   entries        = JSON array of { fdr33kv_code, hour, load_read, fault_code, fault_remark }
//   fdr33kv_code   = OPTIONAL batch-level fallback feeder code (used when an
//                    entry omits its own fdr33kv_code — backward compatible
//                    with the older single-feeder format)
//
// Response: { success, saved, skipped, errors[], message }
// ────────────────────────────────────────────────────────────────────────────
if ($action === 'save_batch') {

    $defaultFeederCode = trim($_POST['fdr33kv_code'] ?? '');
    $entriesJson       = $_POST['entries'] ?? '[]';
    $today             = getOperationalDate();
    $now               = new DateTime();
    $clockHour         = (int)$now->format('G');

    $entries = json_decode($entriesJson, true);
    if (!is_array($entries) || empty($entries)) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'No entries provided.']);
        exit;
    }

    try {
        $db      = Database::connect();
        $saved   = 0;
        $skipped = 0;
        $errors  = [];
        $feederCounts = [];   // per-feeder save count for the response message

        foreach ($entries as $entry) {
            $feederCode  = trim($entry['fdr33kv_code'] ?? '') ?: $defaultFeederCode;
            $hour        = isset($entry['hour'])      ? (int)$entry['hour']      : -1;
            $loadRead    = isset($entry['load_read']) ? (float)$entry['load_read'] : 0.0;
            $faultCode   = trim($entry['fault_code']   ?? '');
            $faultRemark = trim($entry['fault_remark'] ?? '');

            if ($feederCode === '') {
                $errors[] = "Hour {$hour}: missing feeder code — skipped.";
                $skipped++; continue;
            }
            if ($hour < 0 || $hour > 23) {
                $errors[] = "Feeder {$feederCode} hour {$hour}: invalid hour — skipped.";
                $skipped++; continue;
            }
            if ($clockHour >= 1 && $hour > $clockHour) {
                $errors[] = sprintf('Feeder %s %02d:00 has not yet occurred — skipped.', $feederCode, $hour);
                $skipped++; continue;
            }

            $result = saveOneReading($db, $user['payroll_id'], $feederCode, $hour, $loadRead, $faultCode, $faultRemark, $today);

            if ($result['success']) {
                $saved++;
                $feederCounts[$feederCode] = ($feederCounts[$feederCode] ?? 0) + 1;
            } else {
                $errors[] = "Feeder {$feederCode} hour {$hour}: " . $result['message'];
                $skipped++;
            }
        }

        $feederCount = count($feederCounts);
        $message = $saved > 0
            ? sprintf('%d reading(s) saved across %d feeder(s)%s',
                $saved, $feederCount,
                $skipped > 0 ? sprintf(', %d skipped.', $skipped) : '.')
            : 'No entries were saved. Check the errors.';

        ob_end_clean();
        echo json_encode([
            'success'       => $saved > 0,
            'saved'         => $saved,
            'skipped'       => $skipped,
            'feeders_saved' => $feederCount,
            'errors'        => $errors,
            'message'       => $message,
        ]);

    } catch (PDOException $e) {
        error_log('33kv_save.php batch DB error: ' . $e->getMessage());
        ob_end_clean();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error during batch save.']);
    }
    exit;
}

// ── Unknown action ──────────────────────────────────────────────────────────
ob_end_clean();
echo json_encode(['success' => false, 'message' => 'Unknown action']);
exit;
