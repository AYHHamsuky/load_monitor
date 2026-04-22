<?php
/**
 * Interruption11kvController
 * Path: /app/controllers/Interruption11kvController.php
 *
 * Amendments applied:
 *  4. AWAITING_APPROVAL complete attempt → informative page, not die()
 *  6. 'edit' case enforces 1-hour window; new 'cancel' action
 */
Guard::requireLogin(); // Login required for all actions; write actions enforce UL1 below

$user   = Auth::user();
$db     = Database::connect();
$action = $_GET['action'] ?? 'list';

// Feeders list only needed for UL1 write actions
$feeders_11kv = [];
if ($user['role'] === 'UL1' && !empty($user['assigned_33kv_code'])) {
    $fdrStmt = $db->prepare("
        SELECT f.fdr11kv_code, f.fdr11kv_name, t.fdr33kv_name AS parent_feeder
        FROM fdr11kv f
        LEFT JOIN fdr33kv t ON t.fdr33kv_code = f.fdr33kv_code
        WHERE f.fdr33kv_code = ?
        ORDER BY f.fdr11kv_name
    ");
    $fdrStmt->execute([$user['assigned_33kv_code']]);
    $feeders_11kv = $fdrStmt->fetchAll(PDO::FETCH_ASSOC);
}

switch ($action) {

    // ── My Requests — open to ALL logged-in users at same ISS ──────────────────
    // Scope: all interruptions on 11kV feeders belonging to this ISS,
    //        so every role at the injection substation can see the live ticket list.
    case 'my-requests':
        require_once __DIR__ . '/../models/Interruption11kv.php';

        // Resolve iss_code: UL1 users have it directly; others may have it on their profile
        $issCode = $user['iss_code'] ?? null;

        if ($issCode) {
            // All interruptions for feeders under this ISS (any logger)
            $mrStmt = $db->prepare("
                SELECT i.*, f.fdr11kv_name, t.fdr33kv_name AS parent_feeder,
                       u.staff_name AS logger_name
                FROM interruptions_11kv i
                INNER JOIN fdr11kv      f ON f.fdr11kv_code = i.fdr11kv_code
                INNER JOIN iss_locations il ON il.iss_code  = f.iss_code
                LEFT  JOIN fdr33kv      t  ON t.fdr33kv_code = f.fdr33kv_code
                LEFT  JOIN staff_details u  ON u.payroll_id  = i.user_id
                WHERE f.iss_code = ?
                  AND i.form_status != 'CANCELLED'
                ORDER BY i.started_at DESC
            ");
            $mrStmt->execute([$issCode]);
            $issInterruptions = $mrStmt->fetchAll(PDO::FETCH_ASSOC);

            // ISS display name
            $issRow = $db->prepare("SELECT iss_name FROM iss_locations WHERE iss_code = ?");
            $issRow->execute([$issCode]);
            $issName = $issRow->fetchColumn() ?: $issCode;
        } else {
            // Fallback for roles without iss_code: show own records only
            $issInterruptions = Interruption11kv::myRequests($user['payroll_id']);
            $issName          = 'Your Station';
        }

        $isUL1 = ($user['role'] === 'UL1');
        require __DIR__ . '/../views/interruptions_11kv/my_requests.php';
        break;

    // ── Log new (Stage 1) ─────────────────────────────────────────────────────
    case 'log':
        if ($user['role'] !== 'UL1') { header('Location: ?page=interruptions_11kv&action=my-requests'); exit; }
        if (empty($feeders_11kv)) die('No 11kV feeders are assigned to your station.');
        $mode = 'new'; $interruption = null;
        require __DIR__ . '/../views/interruptions_11kv/log_form.php';
        break;

    // ── View / read-only ──────────────────────────────────────────────────────
    case 'view':
        require_once __DIR__ . '/../models/Interruption11kv.php';
        if (!empty($_GET['ticket'])) {
            $interruption = Interruption11kv::getByTicket($_GET['ticket']);
        } elseif (!empty($_GET['id'])) {
            $interruption = Interruption11kv::getById((int)$_GET['id']);
        } else {
            $interruption = null;
        }
        if (!$interruption) die('Interruption not found.');
        $codeRow = $db->prepare("
            SELECT interruption_description, interruption_group, body_responsible
            FROM interruption_codes WHERE interruption_code = ?
        ");
        $codeRow->execute([$interruption['interruption_code']]);
        $interruption = array_merge($interruption, $codeRow->fetch(PDO::FETCH_ASSOC) ?: []);
        $mode = 'view';
        require __DIR__ . '/../views/interruptions_11kv/log_form.php';
        break;

    // ── Complete (Stage 2) ────────────────────────────────────────────────────
    case 'complete':
        if ($user['role'] !== 'UL1') { header('Location: ?page=interruptions_11kv&action=my-requests'); exit; }
        require_once __DIR__ . '/../models/Interruption11kv.php';
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) die('Invalid ID.');
        $interruption = Interruption11kv::getById($id);
        if (!$interruption) die('Interruption not found.');
        // ISS-scope check: ticket feeder must belong to this ISS (any UL1 there can complete)
        $issCheck = $db->prepare("
            SELECT COUNT(*) FROM fdr11kv f
            INNER JOIN iss_locations il ON il.iss_code = f.iss_code
            WHERE f.fdr11kv_code = ? AND f.iss_code = ?
        ");
        $issCheck->execute([$interruption['fdr11kv_code'], $user['iss_code'] ?? '']);
        if (!$issCheck->fetchColumn()) {
            die('This ticket does not belong to your injection substation.');
        }

        // Amendment 4: proper message for awaiting-approval state
        if ($interruption['form_status'] === 'AWAITING_APPROVAL') {
            $mode = 'view';
            $codeRow = $db->prepare("
                SELECT interruption_description, interruption_group, body_responsible
                FROM interruption_codes WHERE interruption_code = ?
            ");
            $codeRow->execute([$interruption['interruption_code']]);
            $interruption = array_merge($interruption, $codeRow->fetch(PDO::FETCH_ASSOC) ?: []);
            $pageError = 'This ticket is still awaiting approval from UL3/UL4. '
                       . 'Stage 2 will unlock automatically once approval is granted.';
            require __DIR__ . '/../views/interruptions_11kv/log_form.php';
            break;
        }
        if ($interruption['form_status'] === 'CANCELLED') {
            die('This ticket has been cancelled and cannot be completed.');
        }
        if (!in_array($interruption['form_status'], ['PENDING_COMPLETION','PENDING_COMPLETION_APPROVED'])) {
            die('This record has already been completed.');
        }
        $codeRow = $db->prepare("
            SELECT interruption_description, interruption_group, body_responsible
            FROM interruption_codes WHERE interruption_code = ?
        ");
        $codeRow->execute([$interruption['interruption_code']]);
        $interruption = array_merge($interruption, $codeRow->fetch(PDO::FETCH_ASSOC) ?: []);
        $mode = 'complete';
        require __DIR__ . '/../views/interruptions_11kv/log_form.php';
        break;

    // ── Edit Stage 1 (Amendment 6) ────────────────────────────────────────────
    case 'edit':
        if ($user['role'] !== 'UL1') { header('Location: ?page=interruptions_11kv&action=my-requests'); exit; }
        require_once __DIR__ . '/../models/Interruption11kv.php';
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) die('Invalid ID.');
        $interruption = Interruption11kv::getById($id);
        if (!$interruption) die('Record not found.');
        if ($interruption['user_id'] !== $user['payroll_id'])
            die('You can only edit your own records.');
        // 1-hour guard
        $guard = Interruption11kv::canEditOrCancel($interruption);
        if (!$guard['allowed']) {
            die(htmlspecialchars($guard['reason']));
        }
        $codeRow = $db->prepare("
            SELECT interruption_description, interruption_group, body_responsible
            FROM interruption_codes WHERE interruption_code = ?
        ");
        $codeRow->execute([$interruption['interruption_code']]);
        $interruption = array_merge($interruption, $codeRow->fetch(PDO::FETCH_ASSOC) ?: []);
        $mode = 'edit';
        $editWindowSecondsLeft = $guard['seconds_left'];
        require __DIR__ . '/../views/interruptions_11kv/log_form.php';
        break;

    // ── Cancel (Amendment 6) ─────────────────────────────────────────────────
    case 'cancel':
        if ($user['role'] !== 'UL1') { header('Location: ?page=interruptions_11kv&action=my-requests'); exit; }
        // Handled via AJAX (interruption_11kv_cancel.php)
        // This page route is for confirm-page approach (optional)
        require_once __DIR__ . '/../models/Interruption11kv.php';
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) die('Invalid ID.');
        $interruption = Interruption11kv::getById($id);
        if (!$interruption) die('Record not found.');
        if ($interruption['user_id'] !== $user['payroll_id'])
            die('You can only cancel your own records.');
        $guard = Interruption11kv::canEditOrCancel($interruption);
        if (!$guard['allowed']) die(htmlspecialchars($guard['reason']));
        $mode = 'cancel_confirm';
        $editWindowSecondsLeft = $guard['seconds_left'];
        require __DIR__ . '/../views/interruptions_11kv/log_form.php';
        break;

    // ── List ──────────────────────────────────────────────────────────────────
    case 'list':
    default:
        require_once __DIR__ . '/../models/Interruption11kv.php';
        $selectedFeederCode = $_GET['feeder']    ?? 'ALL';
        $filterDateFrom     = $_GET['date_from'] ?? date('Y-m-d');
        $filterDateTo       = $_GET['date_to']   ?? date('Y-m-d');

        if ($selectedFeederCode === 'ALL') {
            $stmt = $db->prepare("
                SELECT i.*, f.fdr11kv_name, t.fdr33kv_name AS parent_feeder,
                       u.staff_name AS logger_name
                FROM interruptions_11kv i
                LEFT JOIN fdr11kv f      ON i.fdr11kv_code = f.fdr11kv_code
                LEFT JOIN fdr33kv t      ON f.fdr33kv_code = t.fdr33kv_code
                LEFT JOIN staff_details u ON i.user_id     = u.payroll_id
                WHERE f.fdr33kv_code = ? AND DATE(i.datetime_out) BETWEEN ? AND ?
                  AND i.form_status != 'CANCELLED'
                ORDER BY i.datetime_out DESC
            ");
            $stmt->execute([$user['assigned_33kv_code'], $filterDateFrom, $filterDateTo]);
            $feeder = ['fdr11kv_code'=>'ALL','fdr11kv_name'=>'All 11kV Feeders','parent_feeder'=>''];
            $statsQ = $db->prepare("
                SELECT COUNT(*) as total_interruptions,
                       SUM(i.load_loss) as total_load_loss, AVG(i.load_loss) as avg_load_loss,
                       SUM(i.duration)  as total_duration,  AVG(i.duration)  as avg_duration,
                       MAX(i.duration)  as max_duration,
                       SUM(CASE WHEN i.reason_for_delay IS NOT NULL THEN 1 ELSE 0 END) as delayed_restorations
                FROM interruptions_11kv i
                LEFT JOIN fdr11kv f ON i.fdr11kv_code = f.fdr11kv_code
                WHERE f.fdr33kv_code = ? AND DATE(i.datetime_out) BETWEEN ? AND ?
                  AND i.form_status != 'CANCELLED'
            ");
            $statsQ->execute([$user['assigned_33kv_code'], $filterDateFrom, $filterDateTo]);
            $tbQ = $db->prepare("
                SELECT i.interruption_type, COUNT(*) as count
                FROM interruptions_11kv i
                LEFT JOIN fdr11kv f ON i.fdr11kv_code = f.fdr11kv_code
                WHERE f.fdr33kv_code = ? AND DATE(i.datetime_out) BETWEEN ? AND ?
                  AND i.form_status != 'CANCELLED'
                GROUP BY i.interruption_type
            ");
            $tbQ->execute([$user['assigned_33kv_code'], $filterDateFrom, $filterDateTo]);
        } else {
            $stmt = $db->prepare("
                SELECT i.*, f.fdr11kv_name, t.fdr33kv_name AS parent_feeder,
                       u.staff_name AS logger_name
                FROM interruptions_11kv i
                LEFT JOIN fdr11kv f      ON i.fdr11kv_code = f.fdr11kv_code
                LEFT JOIN fdr33kv t      ON f.fdr33kv_code = t.fdr33kv_code
                LEFT JOIN staff_details u ON i.user_id     = u.payroll_id
                WHERE i.fdr11kv_code = ? AND DATE(i.datetime_out) BETWEEN ? AND ?
                  AND i.form_status != 'CANCELLED'
                ORDER BY i.datetime_out DESC
            ");
            $stmt->execute([$selectedFeederCode, $filterDateFrom, $filterDateTo]);
            $fdrQ = $db->prepare("
                SELECT f.fdr11kv_code, f.fdr11kv_name, t.fdr33kv_name AS parent_feeder
                FROM fdr11kv f LEFT JOIN fdr33kv t ON f.fdr33kv_code = t.fdr33kv_code
                WHERE f.fdr11kv_code = ?
            ");
            $fdrQ->execute([$selectedFeederCode]);
            $feeder = $fdrQ->fetch(PDO::FETCH_ASSOC) ?: [];
            $statsQ = $db->prepare("
                SELECT COUNT(*) as total_interruptions,
                       SUM(load_loss) as total_load_loss, AVG(load_loss) as avg_load_loss,
                       SUM(duration)  as total_duration,  AVG(duration)  as avg_duration,
                       MAX(duration)  as max_duration,
                       SUM(CASE WHEN reason_for_delay IS NOT NULL THEN 1 ELSE 0 END) as delayed_restorations
                FROM interruptions_11kv
                WHERE fdr11kv_code = ? AND DATE(datetime_out) BETWEEN ? AND ?
                  AND form_status != 'CANCELLED'
            ");
            $statsQ->execute([$selectedFeederCode, $filterDateFrom, $filterDateTo]);
            $tbQ = $db->prepare("
                SELECT interruption_type, COUNT(*) as count
                FROM interruptions_11kv
                WHERE fdr11kv_code = ? AND DATE(datetime_out) BETWEEN ? AND ?
                  AND form_status != 'CANCELLED'
                GROUP BY interruption_type
            ");
            $tbQ->execute([$selectedFeederCode, $filterDateFrom, $filterDateTo]);
        }
        $interruptions  = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stats          = $statsQ->fetch(PDO::FETCH_ASSOC);
        $typeBreakdown  = $tbQ->fetchAll(PDO::FETCH_ASSOC);
        require __DIR__ . '/../views/interruptions_11kv/index.php';
        break;
}
