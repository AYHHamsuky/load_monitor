<?php
/**
 * InterruptionController — 33kV
 * Path: /app/controllers/InterruptionController.php
 */
Guard::requireUL2();

$user   = Auth::user();
$db     = Database::connect();
$action = $_GET['action'] ?? 'list';

// Load all 33kV feeders once
$fdrStmt = $db->query("SELECT f.fdr33kv_code, f.fdr33kv_name, t.station_name
                        FROM fdr33kv f
                        LEFT JOIN transmission_stations t ON t.ts_code = f.ts_code
                        ORDER BY f.fdr33kv_name");
$feeders_33kv = $fdrStmt->fetchAll(PDO::FETCH_ASSOC);

switch ($action) {

    /* ── MY REQUESTS ───────────────────────────────────────────────────── */
    case 'my-requests':
        require_once __DIR__ . '/../models/Interruption.php';
        $myRequests = Interruption::myRequests($user['payroll_id']);
        require __DIR__ . '/../views/interruptions/my_requests.php';
        break;

    /* ── LOG (Stage 1 form — new) ──────────────────────────────────────── */
    case 'log':
        if (empty($feeders_33kv)) die('No 33kV feeders configured. Contact administrator.');
        $mode         = 'new';
        $interruption = null;
        require __DIR__ . '/../views/interruptions/log_form.php';
        break;

    /* ── VIEW (ticket view — all locked, Complete button if pending) ───── */
    case 'view':
        require_once __DIR__ . '/../models/Interruption.php';
        if (!empty($_GET['ticket'])) {
            $interruption = Interruption::getByTicket($_GET['ticket']);
        } elseif (!empty($_GET['id'])) {
            $interruption = Interruption::getById((int)$_GET['id']);
        } else {
            $interruption = null;
        }
        if (!$interruption) die('Interruption record not found.');
        // Enrich with code info
        $codeRow = $db->prepare("SELECT interruption_description, interruption_group, body_responsible
                                  FROM interruption_codes WHERE interruption_code = ?");
        $codeRow->execute([$interruption['interruption_code']]);
        $interruption = array_merge($interruption, $codeRow->fetch(PDO::FETCH_ASSOC) ?: []);
        $mode = 'view';
        require __DIR__ . '/../views/interruptions/log_form.php';
        break;

    /* ── COMPLETE (Stage 2 form — stage2 active, stage1 locked) ───────── */
    case 'complete':
        require_once __DIR__ . '/../models/Interruption.php';
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) die('Invalid interruption ID.');
        $interruption = Interruption::getById($id);
        if (!$interruption) die('Interruption not found.');
        if ($interruption['user_id'] !== $user['payroll_id']) die('You can only complete your own records.');
        if (!in_array($interruption['form_status'], ['PENDING_COMPLETION','PENDING_COMPLETION_APPROVED'])) {
            if ($interruption['form_status'] === 'AWAITING_APPROVAL') {
                die('This interruption is awaiting approval. Stage 2 will unlock once approved.');
            }
            die('This interruption is already completed.');
        }
        $codeRow = $db->prepare("SELECT interruption_description, interruption_group, body_responsible
                                  FROM interruption_codes WHERE interruption_code = ?");
        $codeRow->execute([$interruption['interruption_code']]);
        $interruption = array_merge($interruption, $codeRow->fetch(PDO::FETCH_ASSOC) ?: []);
        $mode = 'complete';
        require __DIR__ . '/../views/interruptions/log_form.php';
        break;

    /* ── LIST (main index) ─────────────────────────────────────────────── */
    case 'list':
    default:
        require_once __DIR__ . '/../models/Interruption.php';
        $selectedFeederCode = $_GET['feeder']    ?? 'ALL';
        $filterDateFrom     = $_GET['date_from'] ?? date('Y-m-d');
        $filterDateTo       = $_GET['date_to']   ?? date('Y-m-d');

        if ($selectedFeederCode === 'ALL') {
            $stmt = $db->prepare("
                SELECT i.*, f.fdr33kv_name, t.station_name, u.staff_name AS logger_name
                FROM interruptions i
                LEFT JOIN fdr33kv f ON i.fdr33kv_code=f.fdr33kv_code
                LEFT JOIN transmission_stations t ON f.ts_code=t.ts_code
                LEFT JOIN staff_details u ON i.user_id=u.payroll_id
                WHERE DATE(i.datetime_out) BETWEEN ? AND ?
                ORDER BY i.datetime_out DESC
            ");
            $stmt->execute([$filterDateFrom, $filterDateTo]);
            $interruptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $feeder = ['fdr33kv_code'=>'ALL','fdr33kv_name'=>'All 33kV Feeders','station_name'=>'System-wide'];
            $statsQ = $db->prepare("SELECT COUNT(*) as total_interruptions,
                SUM(load_loss) as total_load_loss, AVG(load_loss) as avg_load_loss,
                SUM(duration) as total_duration, AVG(duration) as avg_duration, MAX(duration) as max_duration,
                SUM(CASE WHEN reason_for_delay IS NOT NULL AND reason_for_delay!='' THEN 1 ELSE 0 END) as delayed_restorations
                FROM interruptions WHERE DATE(datetime_out) BETWEEN ? AND ?");
            $statsQ->execute([$filterDateFrom, $filterDateTo]);
        } else {
            $stmt = $db->prepare("
                SELECT i.*, f.fdr33kv_name, t.station_name, u.staff_name AS logger_name
                FROM interruptions i
                LEFT JOIN fdr33kv f ON i.fdr33kv_code=f.fdr33kv_code
                LEFT JOIN transmission_stations t ON f.ts_code=t.ts_code
                LEFT JOIN staff_details u ON i.user_id=u.payroll_id
                WHERE i.fdr33kv_code=? AND DATE(i.datetime_out) BETWEEN ? AND ?
                ORDER BY i.datetime_out DESC
            ");
            $stmt->execute([$selectedFeederCode, $filterDateFrom, $filterDateTo]);
            $interruptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $fdrQ = $db->prepare("SELECT f.fdr33kv_code, f.fdr33kv_name, t.station_name
                FROM fdr33kv f LEFT JOIN transmission_stations t ON f.ts_code=t.ts_code
                WHERE f.fdr33kv_code=?");
            $fdrQ->execute([$selectedFeederCode]);
            $feeder = $fdrQ->fetch(PDO::FETCH_ASSOC) ?: ['fdr33kv_code'=>'','fdr33kv_name'=>'Not Found','station_name'=>''];
            $statsQ = $db->prepare("SELECT COUNT(*) as total_interruptions,
                SUM(load_loss) as total_load_loss, AVG(load_loss) as avg_load_loss,
                SUM(duration) as total_duration, AVG(duration) as avg_duration, MAX(duration) as max_duration,
                SUM(CASE WHEN reason_for_delay IS NOT NULL AND reason_for_delay!='' THEN 1 ELSE 0 END) as delayed_restorations
                FROM interruptions WHERE fdr33kv_code=? AND DATE(datetime_out) BETWEEN ? AND ?");
            $statsQ->execute([$selectedFeederCode, $filterDateFrom, $filterDateTo]);
        }
        $stats = $statsQ->fetch(PDO::FETCH_ASSOC);

        $tbQ = $selectedFeederCode === 'ALL'
            ? $db->prepare("SELECT interruption_type, COUNT(*) as count FROM interruptions
                             WHERE DATE(datetime_out) BETWEEN ? AND ? GROUP BY interruption_type")
            : $db->prepare("SELECT interruption_type, COUNT(*) as count FROM interruptions
                             WHERE fdr33kv_code=? AND DATE(datetime_out) BETWEEN ? AND ? GROUP BY interruption_type");
        $selectedFeederCode === 'ALL'
            ? $tbQ->execute([$filterDateFrom, $filterDateTo])
            : $tbQ->execute([$selectedFeederCode, $filterDateFrom, $filterDateTo]);
        $typeBreakdown = $tbQ->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/interruptions/index.php';
        break;

    /* ── EDIT ──────────────────────────────────────────────────────────── */
    case 'edit':
        require_once __DIR__ . '/../models/Interruption.php';
        $id = (int)($_GET['id'] ?? 0);
        $interruption = Interruption::getById($id);
        if (!$interruption) die('Interruption not found.');
        if ($interruption['user_id'] !== $user['payroll_id']) die('You can only edit your own records.');
        if (date('Y-m-d', strtotime($interruption['datetime_out'])) !== date('Y-m-d')) {
            die('Editing only allowed on the same day. Use correction request for past records.');
        }
        require __DIR__ . '/../views/interruptions/edit_form.php';
        break;
}
