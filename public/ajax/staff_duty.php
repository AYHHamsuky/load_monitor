<?php
/**
 * Staff On Duty — AJAX Endpoint
 * Path: public/ajax/staff_duty.php
 *
 * Handles two AJAX actions:
 *   ?ajax=details  — activity timeline for a staff member
 *   ?ajax=perf     — performance profile (4 periods)
 *
 * Called by the modals in app/views/lead_dispatch/staff_on_duty.php
 */

// ── Bootstrap ─────────────────────────────────────────────────────────────────
$bootstrap = __DIR__ . '/../../app/bootstrap.php';

if (!file_exists($bootstrap)) {
    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Bootstrap not found']);
    exit;
}

require_once $bootstrap;

// ── DB connection ─────────────────────────────────────────────────────────────
try {
    $db = Database::connect();
} catch (Exception $e) {
    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
    exit;
}

// ── Pure JSON output only ─────────────────────────────────────────────────────
while (ob_get_level() > 0) ob_end_clean();
header('Content-Type: application/json');
header('Cache-Control: no-cache');

// ── Auth ──────────────────────────────────────────────────────────────────────
if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
    exit;
}

$ajax = trim($_GET['ajax'] ?? '');

// ══════════════════════════════════════════════════════════════════════════════
//  ACTION: details — activity timeline for one staff member on one date
// ══════════════════════════════════════════════════════════════════════════════
if ($ajax === 'details') {
    $pid        = trim($_GET['payroll_id'] ?? '');
    $query_date = trim($_GET['date'] ?? date('Y-m-d'));

    if (!$pid) {
        echo json_encode(['success' => false, 'error' => 'No payroll_id']);
        exit;
    }

    // ── Staff info ────────────────────────────────────────────────────────────
    $st = $db->prepare("
        SELECT s.payroll_id, s.staff_name, s.role,
               CASE
                   WHEN s.role = 'UL1' THEN COALESCE(iss.iss_name, 'Not Assigned')
                   WHEN s.role = 'UL2' THEN COALESCE(ts.station_name, 'Not Assigned')
                   ELSE 'N/A'
               END AS assigned_location
        FROM   staff_details s
        LEFT JOIN iss_locations         iss ON iss.iss_code     = s.iss_code
        LEFT JOIN fdr33kv               f33 ON f33.fdr33kv_code = s.assigned_33kv_code
        LEFT JOIN transmission_stations ts  ON ts.ts_code       = f33.ts_code
        WHERE  s.payroll_id = ?
    ");
    $st->execute([$pid]);
    $staff = $st->fetch(PDO::FETCH_ASSOC);

    if (!$staff) {
        echo json_encode(['success' => false, 'error' => 'Staff not found']);
        exit;
    }

    // ── Session for the date ──────────────────────────────────────────────────
    $sess = $db->prepare("
        SELECT login_time, logout_time, session_duration, is_active
        FROM   staff_sessions
        WHERE  payroll_id = ? AND DATE(login_time) = ?
        ORDER  BY login_time DESC
        LIMIT  1
    ");
    $sess->execute([$pid, $query_date]);
    $session = $sess->fetch(PDO::FETCH_ASSOC);

    // Format duration
    $dur_fmt = null;
    if ($session && $session['session_duration'] !== null) {
        $dh      = (float)$session['session_duration'];
        $dur_fmt = floor($dh) . 'h ' . round(($dh - floor($dh)) * 60) . 'm';
    } elseif ($session && $session['login_time'] && !$session['logout_time']) {
        $el      = time() - strtotime($session['login_time']);
        $dur_fmt = floor($el / 3600) . 'h ' . floor(($el % 3600) / 60) . 'm (live)';
    }

    // ── Activities ────────────────────────────────────────────────────────────
    $acts = $db->prepare("
        SELECT activity_type, activity_description, activity_time
        FROM   staff_activity_logs
        WHERE  payroll_id = ? AND DATE(activity_time) = ?
        ORDER  BY activity_time ASC
    ");
    $acts->execute([$pid, $query_date]);

    $activities = [];
    foreach ($acts->fetchAll(PDO::FETCH_ASSOC) as $a) {
        $activities[] = [
            'time'        => date('H:i', strtotime($a['activity_time'])),
            'type'        => $a['activity_type'],
            'description' => $a['activity_description']
                ?: ucfirst(strtolower(str_replace('_', ' ', $a['activity_type']))),
        ];
    }

    // ── Total activity count ──────────────────────────────────────────────────
    $act_count = $db->prepare(
        "SELECT COUNT(*) FROM staff_activity_logs WHERE payroll_id = ? AND DATE(activity_time) = ?"
    );
    $act_count->execute([$pid, $query_date]);

    echo json_encode([
        'success'           => true,
        'staff_name'        => $staff['staff_name'],
        'payroll_id'        => $staff['payroll_id'],
        'role_name'         => $staff['role'] === 'UL1' ? '11kV Data Entry' : '33kV Data Entry',
        'assigned_location' => $staff['assigned_location'],
        'login_time'        => $session ? date('H:i', strtotime($session['login_time'])) : null,
        'logout_time'       => ($session && $session['logout_time'])
                                ? date('H:i', strtotime($session['logout_time']))
                                : null,
        'session_duration'  => $dur_fmt,
        'total_activities'  => (int)$act_count->fetchColumn(),
        'activities'        => $activities,
    ]);
    exit;
}

// ══════════════════════════════════════════════════════════════════════════════
//  ACTION: perf — performance scores across 4 periods
// ══════════════════════════════════════════════════════════════════════════════
if ($ajax === 'perf') {
    $pid      = trim($_GET['payroll_id'] ?? '');
    $ref_date = trim($_GET['date'] ?? date('Y-m-d'));

    if (!$pid) {
        echo json_encode(['success' => false, 'message' => 'No payroll_id']);
        exit;
    }

    // Helper closure: compute scores for a date range
    $compute = function (string $start, string $end) use ($db, $pid): array {
        $days = max(1, (new DateTime($start))->diff(new DateTime($end))->days + 1);

        $c = $db->prepare(
            "SELECT COUNT(*) FROM load_corrections WHERE requested_by = ? AND DATE(requested_at) BETWEEN ? AND ?"
        );
        $c->execute([$pid, $start, $end]);
        $corrections = (int)$c->fetchColumn();

        $e = $db->prepare(
            "SELECT COUNT(*) FROM late_entry_log WHERE user_id = ? AND log_date BETWEEN ? AND ?"
        );
        $e->execute([$pid, $start, $end]);
        $explanations = (int)$e->fetchColumn();

        $corr_max   = 1 * $days;
        $expl_max   = 3 * $days;
        $corr_score = max(0, (int)round(100 - ($corrections / $corr_max) * 100));
        $expl_score = max(0, (int)round(100 - ($explanations / max(1, $expl_max)) * 100));
        $aggregate  = (int)round($corr_score * 0.60 + $expl_score * 0.40);

        return compact('corrections', 'explanations', 'corr_score', 'expl_score', 'aggregate', 'days');
    };

    // Week bounds: Mon–Sun
    $dow      = (int)date('N', strtotime($ref_date)); // 1 = Monday
    $wk_start = date('Y-m-d', strtotime($ref_date . ' -' . ($dow - 1) . ' days'));
    $wk_end   = date('Y-m-d', strtotime($wk_start . ' +6 days'));

    $scores = [
        'day'   => $compute($ref_date, $ref_date),
        'week'  => $compute($wk_start, $wk_end),
        'month' => $compute(date('Y-m-01', strtotime($ref_date)), date('Y-m-t', strtotime($ref_date))),
        'year'  => $compute(date('Y-01-01', strtotime($ref_date)), date('Y-12-31', strtotime($ref_date))),
    ];

    echo json_encode(['success' => true, 'scores' => $scores]);
    exit;
}

// ── Unknown action ────────────────────────────────────────────────────────────
echo json_encode(['success' => false, 'message' => 'Unknown ajax action: ' . htmlspecialchars($ajax)]);
exit;
