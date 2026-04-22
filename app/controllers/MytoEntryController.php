<?php
/**
 * MytoEntryController — AJAX endpoint for MYTO bulk allocation entry
 * Path: app/controllers/MytoEntryController.php
 *
 * ADD THIS ROUTE IN public/index.php inside the switch($page) block:
 *
 *   case 'myto_entry':
 *       Guard::requireLogin();
 *       require __DIR__ . '/../app/controllers/MytoEntryController.php';
 *       break;
 *
 * POST actions:
 *   save_allocation   — save one hourly bulk MYTO figure + distribute
 *   save_formula      — save an edited sharing formula
 *   get_formula       — return current active formula as JSON
 *   get_daily_summary — return today's myto_daily rows (for matrix refresh)
 */

require_once __DIR__ . '/../models/MytoAllocation.php';

// ── Auth: UL8 only ────────────────────────────────────────────────────────────
$user = Auth::user();
if (!$user || $user['role'] !== 'UL8') {
    header('Content-Type: application/json');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. UL8 role required.']);
    exit;
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST required.']);
    exit;
}

$action = trim($_POST['action'] ?? '');
$opDate = getOperationalDate();   // from LateEntryLog.php helpers (auto-included via bootstrap)

// ─────────────────────────────────────────────────────────────────────────────
// ACTION: get_formula
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'get_formula') {
    $formula = MytoAllocation::getActiveFormula();
    $version = MytoAllocation::getCurrentVersion();
    $sum     = array_sum(array_column($formula, 'percentage'));
    echo json_encode([
        'success'  => true,
        'formula'  => $formula,
        'version'  => $version,
        'sum'      => round($sum, 4),
    ]);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// ACTION: save_formula
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'save_formula') {
    // Expect JSON body in POST['rows'] = '{"TS001":30.5,"TS002":69.5}'
    $rawRows = trim($_POST['rows'] ?? '');
    if (empty($rawRows)) {
        echo json_encode(['success' => false, 'message' => 'No formula rows supplied.']);
        exit;
    }
    $rows = json_decode($rawRows, true);
    if (!is_array($rows)) {
        echo json_encode(['success' => false, 'message' => 'Invalid formula JSON.']);
        exit;
    }

    // Cast to float and strip blanks
    $cleaned = [];
    foreach ($rows as $ts => $pct) {
        $ts = trim($ts);
        if ($ts === '') continue;
        $cleaned[$ts] = (float)$pct;
    }

    $result = MytoAllocation::saveFormula($cleaned, $user['payroll_id']);
    echo json_encode($result);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// ACTION: save_allocation
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'save_allocation') {

    $hour       = (int)($_POST['entry_hour']    ?? -1);
    $allocation = isset($_POST['myto_allocation']) && $_POST['myto_allocation'] !== ''
                  ? (float)$_POST['myto_allocation'] : -1;
    $useCustom  = !empty($_POST['use_custom_formula']) && $_POST['use_custom_formula'] === '1';

    // Validate hour
    if ($hour < 0 || $hour > 23) {
        echo json_encode(['success' => false, 'message' => 'Invalid hour (0–23).']);
        exit;
    }

    // Validate allocation
    if ($allocation < 0) {
        echo json_encode(['success' => false, 'message' => 'MYTO allocation must be ≥ 0.']);
        exit;
    }

    // Future-hour guard: same logic as 11kV/33kV
    $currentSlot = (int)(new DateTime())->format('G');
    if ($currentSlot >= 1 && $hour > $currentSlot) {
        echo json_encode([
            'success' => false,
            'message' => sprintf('Hour %02d:00 has not yet occurred.', $hour),
        ]);
        exit;
    }

    // Custom formula?
    $customFormula = null;
    if ($useCustom) {
        $rawRows = trim($_POST['custom_rows'] ?? '');
        if (empty($rawRows)) {
            echo json_encode(['success' => false, 'message' => 'Custom formula rows missing.']);
            exit;
        }
        $decoded = json_decode($rawRows, true);
        if (!is_array($decoded)) {
            echo json_encode(['success' => false, 'message' => 'Invalid custom formula JSON.']);
            exit;
        }
        $customFormula = array_map('floatval', $decoded);
    }

    $result = MytoAllocation::saveHourlyAllocation(
        $opDate,
        $hour,
        $allocation,
        $user['payroll_id'],
        $customFormula
    );

    // If custom formula was applied and saved, also persist it as the new active formula
    if ($result['success'] && $useCustom && $customFormula !== null) {
        MytoAllocation::saveFormula($customFormula, $user['payroll_id']);
    }

    echo json_encode($result);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// ACTION: get_daily_summary — return today's entries for live matrix refresh
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'get_daily_summary') {
    $daily = MytoAllocation::getDailyAllocations($opDate);
    $ts    = MytoAllocation::getTsAllocations($opDate);
    echo json_encode(['success' => true, 'daily' => $daily, 'ts_allocations' => $ts]);
    exit;
}

// Unknown action
echo json_encode(['success' => false, 'message' => 'Unknown action: ' . htmlspecialchars($action)]);
exit;
