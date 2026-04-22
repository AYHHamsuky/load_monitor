<?php
/**
 * Staff on Duty — Enhanced v3.0
 * Path: app/views/lead_dispatch/staff_on_duty.php
 *
 * AJAX NOTE: header.php and sidebar.php both run DB queries and emit full HTML.
 * The controller may require them before reaching this view. To guarantee clean
 * JSON responses we must discard ALL prior output the moment we detect an AJAX
 * request. ob_end_clean() loops until every output buffer is gone, then we send
 * the JSON and exit — the layout files are never loaded for AJAX calls.
 */

// ── AJAX gate ─────────────────────────────────────────────────────────────────
if (isset($_GET['ajax'])) {
    // Kill every output buffer opened by bootstrap / controller / layout
    while (ob_get_level() > 0) { ob_end_clean(); }
    // From here no HTML can leak into the JSON response
}

// ── AJAX: activity details ────────────────────────────────────────────────────
if (isset($_GET['ajax']) && $_GET['ajax'] === 'details') {
    header('Content-Type: application/json');
    $pid           = trim($_GET['payroll_id'] ?? '');
    $ajax_date     = trim($_GET['date'] ?? date('Y-m-d'));

    if (!$pid) { echo json_encode(['error' => 'No payroll_id']); exit; }

    // Staff info
    $st = $db->prepare("
        SELECT s.payroll_id, s.staff_name, s.role,
               CASE WHEN s.role='UL1' THEN COALESCE(iss.iss_name,'Not Assigned')
                    WHEN s.role='UL2' THEN COALESCE(ts.station_name,'Not Assigned')
                    ELSE 'N/A' END AS assigned_location
        FROM   staff_details s
        LEFT JOIN iss_locations         iss ON iss.iss_code     = s.iss_code
        LEFT JOIN fdr33kv               f33 ON f33.fdr33kv_code = s.assigned_33kv_code
        LEFT JOIN transmission_stations ts  ON ts.ts_code       = f33.ts_code
        WHERE  s.payroll_id = ?
    ");
    $st->execute([$pid]);
    $staff = $st->fetch(PDO::FETCH_ASSOC);
    if (!$staff) { echo json_encode(['error' => 'Staff not found']); exit; }

    // Session for the date
    $sess = $db->prepare("
        SELECT login_time, logout_time, session_duration, is_active
        FROM   staff_sessions
        WHERE  payroll_id = ? AND DATE(login_time) = ?
        ORDER  BY login_time DESC LIMIT 1
    ");
    $sess->execute([$pid, $ajax_date]);
    $session = $sess->fetch(PDO::FETCH_ASSOC);

    // Format duration: GENERATED col is decimal hours
    $dur_fmt = null;
    if ($session && $session['session_duration'] !== null) {
        $dh = (float)$session['session_duration'];
        $dur_fmt = floor($dh) . 'h ' . round(($dh - floor($dh)) * 60) . 'm';
    } elseif ($session && $session['login_time'] && !$session['logout_time']) {
        $el = time() - strtotime($session['login_time']);
        $dur_fmt = floor($el/3600) . 'h ' . floor(($el%3600)/60) . 'm (live)';
    }

    // Activities
    $acts = $db->prepare("
        SELECT activity_type, activity_description, activity_time
        FROM   staff_activity_logs
        WHERE  payroll_id = ? AND DATE(activity_time) = ?
        ORDER  BY activity_time ASC
    ");
    $acts->execute([$pid, $ajax_date]);
    $activities = [];
    foreach ($acts->fetchAll(PDO::FETCH_ASSOC) as $a) {
        $activities[] = [
            'time'        => date('H:i', strtotime($a['activity_time'])),
            'type'        => $a['activity_type'],
            'description' => $a['activity_description'] ?: ucfirst(strtolower(str_replace('_',' ',$a['activity_type']))),
        ];
    }

    // Total activities & entries
    $act_count = $db->prepare("SELECT COUNT(*) FROM staff_activity_logs WHERE payroll_id=? AND DATE(activity_time)=?");
    $act_count->execute([$pid, $ajax_date]);

    echo json_encode([
        'success'          => true,
        'staff_name'       => $staff['staff_name'],
        'payroll_id'       => $staff['payroll_id'],
        'role_name'        => ($staff['role']==='UL1'?'11kV Data Entry':'33kV Data Entry'),
        'assigned_location'=> $staff['assigned_location'],
        'login_time'       => $session ? date('H:i', strtotime($session['login_time'])) : null,
        'logout_time'      => ($session && $session['logout_time']) ? date('H:i', strtotime($session['logout_time'])) : null,
        'session_duration' => $dur_fmt,
        'total_activities' => (int)$act_count->fetchColumn(),
        'activities'       => $activities,
    ]);
    exit;
}

// ── AJAX: performance profile (all 4 periods) ─────────────────────────────────
if (isset($_GET['ajax']) && $_GET['ajax'] === 'perf') {
    header('Content-Type: application/json');
    $pid      = trim($_GET['payroll_id'] ?? '');
    $ref_date = trim($_GET['date'] ?? date('Y-m-d'));

    if (!$pid) { echo json_encode(['success'=>false,'message'=>'No payroll_id']); exit; }

    // Helper: compute scores for a given date range
    $compute = function(string $start, string $end) use ($db, $pid): array {
        $days = max(1, (new DateTime($start))->diff(new DateTime($end))->days + 1);

        // Corrections initiated in period
        $c = $db->prepare("SELECT COUNT(*) FROM load_corrections WHERE requested_by=? AND DATE(requested_at) BETWEEN ? AND ?");
        $c->execute([$pid, $start, $end]);
        $corrections = (int)$c->fetchColumn();

        // Late-entry explanations in period
        $e = $db->prepare("SELECT COUNT(*) FROM late_entry_log WHERE user_id=? AND log_date BETWEEN ? AND ?");
        $e->execute([$pid, $start, $end]);
        $explanations = (int)$e->fetchColumn();

        // Thresholds scale with number of days
        $corr_max = 1 * $days;   // 1 correction/day = threshold
        $expl_max = 3 * $days;   // 3 explanations/day = threshold

        // Score: 100 = zero violations. Capped at 0.
        $corr_score = max(0, (int)round(100 - ($corrections / $corr_max) * 100));
        $expl_score = max(0, (int)round(100 - ($explanations / max(1, $expl_max)) * 100));
        $aggregate  = (int)round($corr_score * 0.60 + $expl_score * 0.40);

        return compact('corrections','explanations','corr_score','expl_score','aggregate','days');
    };

    $mon_start = date('Y-m-01', strtotime($ref_date));
    $mon_end   = date('Y-m-t',  strtotime($ref_date));
    // Week: Mon–Sun
    $dow       = date('N', strtotime($ref_date));  // 1=Mon
    $wk_start  = date('Y-m-d', strtotime($ref_date . ' -' . ($dow-1) . ' days'));
    $wk_end    = date('Y-m-d', strtotime($wk_start . ' +6 days'));

    $scores = [
        'day'   => $compute($ref_date, $ref_date),
        'week'  => $compute($wk_start, $wk_end),
        'month' => $compute($mon_start, $mon_end),
        'year'  => $compute(date('Y-01-01', strtotime($ref_date)), date('Y-12-31', strtotime($ref_date))),
    ];

    echo json_encode(['success'=>true, 'scores'=>$scores]);
    exit;
}

// ── Normal page render ────────────────────────────────────────────────────────
require __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/sidebar.php';

// ── Operational date ──────────────────────────────────────────────────────────
$now   = new DateTime();
$today = ((int)$now->format('G') === 0)
    ? (clone $now)->modify('-1 day')->format('Y-m-d')
    : $now->format('Y-m-d');

$selected_date = $_GET['date'] ?? $today;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_date)) $selected_date = $today;

$active_tab     = $_GET['tab']           ?? 'active';
$filter_role    = $_GET['role']          ?? 'all';
$search_query   = trim($_GET['search']   ?? '');
$perf_search    = trim($_GET['perf_search']    ?? '');
$perf_location  = trim($_GET['perf_location']  ?? '');  // ISS code or TS code
$perf_rating    = trim($_GET['perf_rating']    ?? '');  // Excellent|Good|Fair|Poor
$perf_page      = max(1, (int)($_GET['perf_page'] ?? 1));
$perf_period    = $_GET['period'] ?? 'day';

// ── Base staff query ──────────────────────────────────────────────────────────
$where  = ["s.role IN ('UL1','UL2')"];
$params = [];
if ($filter_role !== 'all') { $where[] = "s.role = ?"; $params[] = $filter_role; }
if ($search_query !== '') {
    $where[] = "(s.payroll_id LIKE ? OR s.staff_name LIKE ?)";
    $params[] = "%$search_query%"; $params[] = "%$search_query%";
}
$where_sql = implode(' AND ', $where);

$stmt = $db->prepare("
    SELECT s.payroll_id, s.staff_name, s.role, s.iss_code, s.assigned_33kv_code,
           iss.iss_name,
           f33.fdr33kv_name, f33.ts_code,
           ts.station_name,
           CASE WHEN s.role='UL1' THEN COALESCE(iss.iss_name,'Not Assigned')
                WHEN s.role='UL2' THEN COALESCE(ts.station_name,'Not Assigned')
                ELSE 'N/A' END AS assigned_location,
           CASE WHEN s.role='UL1' THEN s.iss_code
                WHEN s.role='UL2' THEN f33.ts_code END AS location_code
    FROM   staff_details s
    LEFT JOIN iss_locations          iss ON iss.iss_code      = s.iss_code
    LEFT JOIN fdr33kv                f33 ON f33.fdr33kv_code  = s.assigned_33kv_code
    LEFT JOIN transmission_stations  ts  ON ts.ts_code        = f33.ts_code
    WHERE  $where_sql
    ORDER  BY s.role, s.staff_name
");
$stmt->execute($params);
$all_staff_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── Enrich with session data ──────────────────────────────────────────────────
$all_staff = [];
foreach ($all_staff_rows as $s) {
    $pid = $s['payroll_id'];

    // Latest session for selected_date
    $sess = $db->prepare("
        SELECT session_id, login_time, logout_time, session_duration, is_active
        FROM   staff_sessions
        WHERE  payroll_id = ? AND DATE(login_time) = ?
        ORDER  BY login_time DESC LIMIT 1
    ");
    $sess->execute([$pid, $selected_date]);
    $session = $sess->fetch(PDO::FETCH_ASSOC);

    $s['is_active']        = ($session && $session['is_active'] === 'Yes') ? 'Yes' : 'No';
    $s['login_time']       = $session['login_time']       ?? null;
    $s['logout_time']      = $session['logout_time']      ?? null;
    $s['session_duration'] = $session['session_duration'] ?? null; // hours decimal
    $s['session_id']       = $session['session_id']       ?? null;

    // Activity count for today
    $act = $db->prepare("
        SELECT COUNT(*) FROM staff_activity_logs
        WHERE payroll_id = ? AND DATE(activity_time) = ?
    ");
    $act->execute([$pid, $selected_date]);
    $s['total_activities'] = (int)$act->fetchColumn();

    // Data entries for today (11kV + 33kV combined)
    $ent = $db->prepare("
        SELECT
            (SELECT COUNT(*) FROM fdr11kv_data  WHERE user_id=? AND entry_date=?) +
            (SELECT COUNT(*) FROM fdr33kv_data  WHERE user_id=? AND entry_date=?) AS cnt
    ");
    $ent->execute([$pid, $selected_date, $pid, $selected_date]);
    $s['entries_today'] = (int)$ent->fetchColumn();

    $all_staff[] = $s;
}

// Partition: active-now
$active_staff   = array_filter($all_staff, fn($s) => $s['is_active'] === 'Yes');
$inactive_staff = array_filter($all_staff, fn($s) => $s['is_active'] !== 'Yes');

// ── ISS / TS / 33kV feeder lists for reassign modal ──────────────────────────
$all_iss = $db->query("SELECT iss_code, iss_name FROM iss_locations ORDER BY iss_name")->fetchAll(PDO::FETCH_ASSOC);
$all_ts  = $db->query("SELECT ts_code, station_name FROM transmission_stations ORDER BY station_name")->fetchAll(PDO::FETCH_ASSOC);
$all_33kv_feeders = $db->query("
    SELECT f.fdr33kv_code, f.fdr33kv_name, f.ts_code, ts.station_name
    FROM fdr33kv f LEFT JOIN transmission_stations ts ON ts.ts_code=f.ts_code
    ORDER BY ts.station_name, f.fdr33kv_name
")->fetchAll(PDO::FETCH_ASSOC);
$feeders_by_ts = [];
foreach ($all_33kv_feeders as $f) {
    $feeders_by_ts[$f['ts_code'] ?? 'UNASSIGNED'][] = $f;
}

// ── Performance data ──────────────────────────────────────────────────────────
// Period bounds
$period_map = [
    'day'   => [$selected_date, $selected_date],
    'week'  => [date('Y-m-d', strtotime('monday this week', strtotime($selected_date))),
                date('Y-m-d', strtotime('sunday this week',  strtotime($selected_date)))],
    'month' => [date('Y-m-01', strtotime($selected_date)), date('Y-m-t', strtotime($selected_date))],
    'year'  => [date('Y-01-01', strtotime($selected_date)), date('Y-12-31', strtotime($selected_date))],
];
[$period_start, $period_end] = $period_map[$perf_period] ?? $period_map['day'];

// ── Build perf_staff_list: apply text search + location + rating filters ────────
// First apply text search and location (cheap, no DB)
$perf_staff_list = $all_staff;
if ($perf_search !== '') {
    $q = strtolower($perf_search);
    $perf_staff_list = array_values(array_filter($perf_staff_list, fn($s)
        => str_contains(strtolower($s['payroll_id']), $q)
        || str_contains(strtolower($s['staff_name']), $q)));
}
if ($perf_location !== '') {
    $perf_staff_list = array_values(array_filter($perf_staff_list, fn($s)
        => $s['iss_code'] === $perf_location
        || $s['ts_code']  === $perf_location));
}

// Pre-compute aggregate score for every remaining staff member (needed for rating filter + cards)
$_days_for_filter = max(1,(new DateTime($period_start))->diff(new DateTime($period_end))->days+1);
foreach ($perf_staff_list as &$_sf) {
    $_c = $db->prepare("SELECT COUNT(*) FROM load_corrections WHERE requested_by=? AND DATE(requested_at) BETWEEN ? AND ?");
    $_c->execute([$_sf['payroll_id'],$period_start,$period_end]);
    $_corr=(int)$_c->fetchColumn();
    $_e = $db->prepare("SELECT COUNT(*) FROM late_entry_log WHERE user_id=? AND log_date BETWEEN ? AND ?");
    $_e->execute([$_sf['payroll_id'],$period_start,$period_end]);
    $_expl=(int)$_e->fetchColumn();
    $_cs = max(0,(int)round(100-($_corr/max(1,$_days_for_filter))*100));
    $_es = max(0,(int)round(100-($_expl/max(1,$_days_for_filter*3))*100));
    $_agg= (int)round($_cs*0.60+$_es*0.40);
    $_sf['_perf_rating'] = $_agg>=90?'Excellent':($_agg>=70?'Good':($_agg>=50?'Fair':'Poor'));
}
unset($_sf);

// Rating filter (applied after scores are computed)
if ($perf_rating !== '') {
    $perf_staff_list = array_values(array_filter($perf_staff_list,
        fn($s) => $s['_perf_rating'] === $perf_rating));
}

// Rating card counts (across text+location filtered list, ignoring rating filter so cards always show totals)
$all_rating_counts_prefilter = ['Excellent'=>0,'Good'=>0,'Fair'=>0,'Poor'=>0];
foreach ($perf_staff_list as $_sf2) {
    $all_rating_counts_prefilter[$_sf2['_perf_rating']]++;
}

$all_ids   = array_column($perf_staff_list, 'payroll_id');
$per_page  = 10;
$total_perf_staff = count($all_ids);
$total_pages      = max(1, (int)ceil($total_perf_staff / $per_page));
$perf_page        = min($perf_page, $total_pages);
$page_ids         = array_slice($all_ids, ($perf_page-1)*$per_page, $per_page);

// Build performance rows for page
$perf_rows = [];
foreach ($page_ids as $pid) {
    // Corrections initiated in period
    $corr = $db->prepare("
        SELECT COUNT(*) FROM load_corrections
        WHERE requested_by=? AND DATE(requested_at) BETWEEN ? AND ?
    ");
    $corr->execute([$pid, $period_start, $period_end]);
    $corrections_count = (int)$corr->fetchColumn();

    // Explanations (late entries) in period
    $expl = $db->prepare("
        SELECT COUNT(*) FROM late_entry_log
        WHERE user_id=? AND log_date BETWEEN ? AND ?
    ");
    $expl->execute([$pid, $period_start, $period_end]);
    $explanations_count = (int)$expl->fetchColumn();

    // Data entries in period
    $days_in_period = max(1, (new DateTime($period_start))->diff(new DateTime($period_end))->days + 1);

    // Scoring logic:
    // Corrections: ideal = 0/day. Score = 100 − (actual/ideal_max)*100
    //   ideal_max = 1 per day (1 correction/day = 0%). Each period scales by days.
    // Explanations: ideal = 0/day. Score = 100 − (actual/ideal_max)*100
    //   ideal_max = 3 per day. Each period scales by days.
    $corr_max     = 1 * $days_in_period;   // 1/day max threshold
    $expl_max     = 3 * $days_in_period;   // 3/day max threshold

    // Deduction: how close to the max bad threshold
    $corr_score = max(0, 100 - round(($corrections_count / $corr_max) * 100));
    $expl_score = max(0, 100 - round(($explanations_count / max(1, $expl_max)) * 100));

    // Weighted aggregate: 60% correction, 40% explanation
    $aggregate_score = round($corr_score * 0.60 + $expl_score * 0.40);

    // Rating label
    $rating = fn($s) => $s >= 90 ? ['Excellent','#059669'] : ($s >= 70 ? ['Good','#1d4ed8'] : ($s >= 50 ? ['Fair','#d97706'] : ['Poor','#dc2626']));

    // Find staff row
    $s = current(array_filter($perf_staff_list, fn($x) => $x['payroll_id'] === $pid));

    $perf_rows[] = [
        'payroll_id'         => $pid,
        'staff_name'         => $s['staff_name']        ?? $pid,
        'role'               => $s['role']               ?? '',
        'assigned_location'  => $s['assigned_location']  ?? '',
        'corrections_count'  => $corrections_count,
        'explanations_count' => $explanations_count,
        'corr_score'         => $corr_score,
        'expl_score'         => $expl_score,
        'aggregate_score'    => $aggregate_score,
        'corr_rating'        => $rating($corr_score),
        'expl_rating'        => $rating($expl_score),
        'agg_rating'         => $rating($aggregate_score),
        'days_in_period'     => $days_in_period,
        'corr_max'           => $corr_max,
        'expl_max'           => $expl_max,
    ];
}

// Summary stats
$total_staff_count    = count($all_staff);
$active_count         = count($active_staff);
$ul1_count            = count(array_filter($all_staff, fn($s) => $s['role']==='UL1'));
$ul2_count            = count(array_filter($all_staff, fn($s) => $s['role']==='UL2'));
$total_activities_all = array_sum(array_column($all_staff, 'total_activities'));
$total_entries_all    = array_sum(array_column($all_staff, 'entries_today'));
?>

<!– ═══════════════════════════════════ STYLES ══════════════════════════════════ –>
<style>
*{box-sizing:border-box;}
body{font-family:'Segoe UI',Tahoma,sans-serif;background:#f0f4f8;color:#1e293b;}

.main-content{margin-left:260px;padding:22px;padding-top:90px;min-height:calc(100vh - 64px);}

/* ── Page Header ── */
.sod-header{display:flex;justify-content:space-between;align-items:flex-start;
    margin-bottom:22px;background:white;padding:20px 26px;border-radius:14px;
    box-shadow:0 2px 10px rgba(0,0,0,.07);}
.sod-header h1{font-size:24px;font-weight:800;color:#0f172a;margin:0 0 4px;}
.sod-header .sub{color:#64748b;font-size:13px;}
.header-controls{display:flex;gap:10px;align-items:center;}

input[type=date].date-pick{padding:9px 13px;border:2px solid #e2e8f0;border-radius:8px;
    font-size:13px;font-family:inherit;cursor:pointer;color:#1e293b;}

/* ── Stat Cards ── */
.stats-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));
    gap:14px;margin-bottom:20px;}
.stat-card{background:white;border-radius:12px;padding:16px 18px;
    display:flex;align-items:center;gap:14px;
    box-shadow:0 2px 8px rgba(0,0,0,.06);transition:transform .2s;}
.stat-card:hover{transform:translateY(-3px);}
.stat-ic{width:50px;height:50px;border-radius:11px;display:flex;align-items:center;
    justify-content:center;font-size:20px;flex-shrink:0;}
.ic-blue  {background:#dbeafe;color:#1e40af;}
.ic-green {background:#dcfce7;color:#166534;}
.ic-purple{background:#f3e8ff;color:#6b21a8;}
.ic-orange{background:#ffedd5;color:#9a3412;}
.ic-teal  {background:#ccfbf1;color:#0f766e;}
.ic-red   {background:#fee2e2;color:#991b1b;}
.stat-val{font-size:22px;font-weight:800;color:#0f172a;margin:0;}
.stat-lbl{font-size:12px;color:#64748b;margin:2px 0 0;}

/* ── Tabs ── */
.tab-bar{display:flex;gap:0;background:white;border-radius:12px;
    padding:6px;box-shadow:0 2px 8px rgba(0,0,0,.06);margin-bottom:20px;width:fit-content;}
.tab-btn{padding:10px 22px;border:none;background:transparent;border-radius:8px;
    font-size:14px;font-weight:600;cursor:pointer;color:#64748b;
    transition:all .2s;display:flex;align-items:center;gap:7px;}
.tab-btn.active{background:linear-gradient(135deg,#0b3a82,#1e40af);color:white;
    box-shadow:0 3px 10px rgba(11,58,130,.25);}
.tab-btn:not(.active):hover{background:#f1f5f9;color:#1e293b;}

.tab-panel{display:none;}
.tab-panel.active{display:block;}

/* ── Filters bar ── */
.filters-bar{background:white;border-radius:12px;padding:16px 20px;
    margin-bottom:18px;box-shadow:0 2px 8px rgba(0,0,0,.06);
    display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;}
.fg{display:flex;flex-direction:column;gap:5px;flex:1;min-width:180px;}
.fg label{font-size:11px;font-weight:700;color:#64748b;text-transform:uppercase;}
.fg select,.fg input{padding:9px 12px;border:2px solid #e2e8f0;border-radius:8px;
    font-size:13px;font-family:inherit;background:white;}
.fg input.with-icon{padding-left:34px;}
.search-wrap{position:relative;flex:2;min-width:240px;}
.search-wrap .si{position:absolute;left:11px;top:50%;transform:translateY(-50%);
    color:#94a3b8;font-size:14px;pointer-events:none;}
.search-wrap input{padding:9px 12px 9px 34px;width:100%;border:2px solid #e2e8f0;
    border-radius:8px;font-size:13px;}
.btn-filter{padding:9px 18px;background:linear-gradient(135deg,#0b3a82,#1e40af);
    color:white;border:none;border-radius:8px;font-size:13px;font-weight:700;
    cursor:pointer;white-space:nowrap;transition:all .2s;}
.btn-filter:hover{transform:translateY(-1px);}
.btn-clear{padding:9px 14px;background:#e5e7eb;color:#374151;border:none;
    border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;
    text-decoration:none;display:inline-block;line-height:1.4;}

/* ── Table card ── */
.tcard{background:white;border-radius:12px;padding:20px;
    box-shadow:0 2px 8px rgba(0,0,0,.06);overflow:hidden;}
.tcard-header{display:flex;justify-content:space-between;align-items:center;
    margin-bottom:14px;}
.tcard-title{font-size:16px;font-weight:700;color:#0f172a;}
.tcard-meta{font-size:12px;color:#64748b;}

table.dt{width:100%;border-collapse:collapse;font-size:13px;}
table.dt thead th{background:#f8fafc;padding:11px 12px;text-align:left;
    font-size:11px;font-weight:700;color:#475569;text-transform:uppercase;
    border-bottom:2px solid #e2e8f0;white-space:nowrap;}
table.dt tbody tr{border-bottom:1px solid #f1f5f9;transition:background .15s;}
table.dt tbody tr:hover{background:#f8fafc;}
table.dt td{padding:13px 12px;vertical-align:middle;}

/* Badges */
.badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;}
.b-ul1{background:#dbeafe;color:#1e40af;}
.b-ul2{background:#fce7f3;color:#9f1239;}
.b-active{background:#dcfce7;color:#166534;}
.b-offline{background:#fee2e2;color:#991b1b;}

/* Duration pill */
.dur-pill{background:#f0f9ff;color:#0369a1;padding:3px 9px;border-radius:10px;
    font-size:11px;font-weight:700;}

/* Action buttons */
.btn-view{padding:5px 12px;font-size:12px;font-weight:600;
    background:linear-gradient(135deg,#3b82f6,#2563eb);color:white;
    border:none;border-radius:6px;cursor:pointer;transition:all .2s;}
.btn-view:hover{transform:translateY(-1px);}
.btn-reassign{padding:5px 12px;font-size:12px;font-weight:600;
    background:linear-gradient(135deg,#f59e0b,#d97706);color:white;
    border:none;border-radius:6px;cursor:pointer;transition:all .2s;margin-left:5px;}
.btn-reassign:hover{transform:translateY(-1px);}
.btn-perf{padding:5px 12px;font-size:12px;font-weight:600;
    background:linear-gradient(135deg,#6366f1,#4f46e5);color:white;
    border:none;border-radius:6px;cursor:pointer;transition:all .2s;margin-left:5px;}
.btn-perf:hover{transform:translateY(-1px);}

/* Active pulse */
.live-dot{width:8px;height:8px;border-radius:50%;background:#22c55e;
    display:inline-block;margin-right:5px;animation:pulse 1.5s infinite;}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.5;transform:scale(1.4)}}

/* ── Score bars ── */
.score-bar-wrap{width:100%;height:8px;background:#e5e7eb;border-radius:4px;margin-top:4px;}
.score-bar{height:8px;border-radius:4px;transition:width .6s;}

/* ── Clickable rating cards ── */
.perf-rating-card{transition:transform .15s ease,box-shadow .15s ease;}
.perf-rating-card:hover{transform:translateY(-3px);box-shadow:0 6px 18px rgba(0,0,0,.13);}
.perf-rating-card.rating-active{transform:translateY(-2px);}

/* ── Period selector (tab 3) ── */
.period-tabs{display:flex;gap:6px;margin-bottom:16px;}
.pt{padding:6px 16px;border:2px solid #e2e8f0;border-radius:8px;
    font-size:12px;font-weight:700;cursor:pointer;color:#64748b;
    background:white;text-decoration:none;transition:all .2s;}
.pt.active,.pt:hover{border-color:#0b3a82;color:#0b3a82;background:#eff6ff;}

/* ── Pagination ── */
.pagination{display:flex;gap:6px;justify-content:center;margin-top:16px;align-items:center;}
.pg-btn{padding:7px 13px;border:2px solid #e2e8f0;background:white;border-radius:8px;
    font-size:13px;font-weight:600;cursor:pointer;color:#64748b;text-decoration:none;
    transition:all .2s;}
.pg-btn.active,.pg-btn:hover{border-color:#0b3a82;color:#0b3a82;background:#eff6ff;}
.pg-btn.disabled{opacity:.4;pointer-events:none;}

/* ── Performance rating chip ── */
.rate-chip{display:inline-block;padding:2px 9px;border-radius:10px;font-size:11px;font-weight:700;}

/* ── Empty state ── */
.empty{text-align:center;padding:50px 20px;color:#94a3b8;}
.empty i{font-size:48px;margin-bottom:12px;opacity:.4;}

/* ══════════════════ MODALS ══════════════════ */
.modal{display:none;position:fixed;z-index:5000;inset:0;
    background:rgba(0,0,0,.55);backdrop-filter:blur(3px);}
.modal.open{display:flex;align-items:center;justify-content:center;}
.mc{background:white;border-radius:16px;width:90%;max-height:92vh;
    overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.3);animation:mslide .25s ease;}
.mc.lg{max-width:760px;}
.mc.xl{max-width:900px;}
@keyframes mslide{from{transform:translateY(-30px);opacity:0}to{transform:translateY(0);opacity:1}}
.mh{padding:20px 24px;border-bottom:2px solid #f1f5f9;
    display:flex;justify-content:space-between;align-items:flex-start;}
.mh h3{font-size:18px;font-weight:700;color:#0f172a;margin:0;}
.mh .msub{font-size:13px;color:#64748b;margin-top:3px;}
.mclose{font-size:26px;color:#94a3b8;cursor:pointer;line-height:1;margin-left:12px;}
.mclose:hover{color:#0f172a;}
.mb{padding:22px 24px;}

/* Activity timeline */
.timeline{border-left:2px solid #e2e8f0;padding-left:18px;margin-top:10px;}
.tl-item{position:relative;margin-bottom:14px;}
.tl-dot{position:absolute;left:-25px;top:3px;width:10px;height:10px;
    background:#3b82f6;border-radius:50%;border:2px solid white;
    box-shadow:0 0 0 2px #3b82f6;}
.tl-time{font-size:11px;color:#64748b;margin-bottom:2px;}
.tl-desc{font-size:13px;color:#1e293b;}
.tl-type{display:inline-block;padding:1px 7px;border-radius:8px;
    font-size:10px;font-weight:700;background:#f1f5f9;color:#475569;margin-left:6px;}

/* Profile sections */
.profile-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));
    gap:12px;margin-bottom:20px;}
.prof-stat{background:#f8fafc;border-radius:10px;padding:14px;text-align:center;}
.prof-stat-val{font-size:22px;font-weight:800;color:#0f172a;}
.prof-stat-lbl{font-size:11px;color:#64748b;margin-top:3px;}

.perf-section{margin-bottom:22px;}
.perf-section h4{font-size:14px;font-weight:700;color:#0f172a;margin:0 0 10px;}
.perf-row{display:flex;align-items:center;gap:12px;margin-bottom:10px;}
.perf-label{font-size:13px;color:#475569;width:120px;flex-shrink:0;}
.perf-bar-wrap{flex:1;height:10px;background:#e5e7eb;border-radius:5px;}
.perf-bar{height:10px;border-radius:5px;}
.perf-val{font-size:13px;font-weight:700;width:45px;text-align:right;}

/* Chart canvas in modal */
.chart-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:16px;}

/* ── Reassign form ── */
.reassign-type-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:18px;}
.rt-opt{border:2px solid #e2e8f0;border-radius:10px;padding:14px 10px;text-align:center;
    cursor:pointer;transition:all .2s;}
.rt-opt:hover,.rt-opt.sel{border-color:#0b3a82;background:#eff6ff;}
.rt-opt-icon{font-size:26px;margin-bottom:6px;}
.rt-opt-title{font-size:13px;font-weight:700;color:#1e293b;}
.rt-opt-desc{font-size:11px;color:#64748b;margin-top:3px;}

.form-group{margin-bottom:16px;}
.form-group label{display:block;font-size:12px;font-weight:700;color:#475569;
    text-transform:uppercase;margin-bottom:6px;}
.form-group select,.form-group textarea,.form-group input[type=text]{
    width:100%;padding:10px 13px;border:2px solid #e2e8f0;border-radius:8px;
    font-size:13px;font-family:inherit;}
.form-group select:focus,.form-group textarea:focus{
    outline:none;border-color:#1e40af;box-shadow:0 0 0 3px rgba(30,64,175,.08);}
.form-group textarea{resize:vertical;min-height:80px;}
.info-box{background:#eff6ff;border-left:4px solid #3b82f6;padding:11px 14px;
    border-radius:8px;margin-bottom:16px;font-size:13px;color:#1e40af;}
.warn-box{background:#fef3c7;border-left:4px solid #f59e0b;padding:11px 14px;
    border-radius:8px;margin-bottom:16px;font-size:13px;color:#92400e;}

.btn-full{width:100%;padding:12px;background:linear-gradient(135deg,#f59e0b,#d97706);
    color:white;border:none;border-radius:8px;font-size:14px;font-weight:700;
    cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:8px;}
.btn-full:hover{transform:translateY(-1px);box-shadow:0 5px 16px rgba(245,158,11,.35);}
.btn-full:disabled{opacity:.6;cursor:not-allowed;transform:none;}

/* Toast */
.toast-wrap{position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:8px;}
.toast{padding:12px 20px;border-radius:10px;font-size:14px;font-weight:600;
    color:white;box-shadow:0 4px 16px rgba(0,0,0,.25);animation:toastIn .3s ease;}
.toast.s{background:linear-gradient(135deg,#059669,#10b981);}
.toast.e{background:linear-gradient(135deg,#dc2626,#ef4444);}
@keyframes toastIn{from{transform:translateX(120%)}to{transform:translateX(0)}}

@media(max-width:768px){
    .main-content{margin-left:0;padding:12px;padding-top:80px;}
    .stats-row{grid-template-columns:1fr 1fr;}
    .tab-bar{flex-wrap:wrap;}
    .chart-row{grid-template-columns:1fr;}
    .reassign-type-grid{grid-template-columns:1fr;}
}
</style>

<div class="main-content">

<!-- ── Page Header ─────────────────────────────────────────────────────────── -->
<div class="sod-header">
    <div>
        <h1>👥 Staff on Duty</h1>
        <p class="sub">Lead Dispatch Monitoring — <?= date('l, F j, Y', strtotime($selected_date)) ?></p>
    </div>
    <div class="header-controls">
        <input type="date" class="date-pick" id="globalDate"
               value="<?= $selected_date ?>" max="<?= $today ?>"
               onchange="window.location.href=updateParam('date',this.value)">
    </div>
</div>

<!-- ── Stat Cards ──────────────────────────────────────────────────────────── -->
<div class="stats-row">
    <div class="stat-card">
        <div class="stat-ic ic-blue"><i class="fas fa-users"></i></div>
        <div><p class="stat-val"><?= $total_staff_count ?></p><p class="stat-lbl">Total Staff</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-ic ic-green"><i class="fas fa-user-check"></i></div>
        <div><p class="stat-val"><?= $active_count ?></p><p class="stat-lbl">Active Now</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-ic ic-purple"><i class="fas fa-bolt"></i></div>
        <div><p class="stat-val"><?= $ul1_count ?></p><p class="stat-lbl">UL1 (11kV)</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-ic ic-orange"><i class="fas fa-plug"></i></div>
        <div><p class="stat-val"><?= $ul2_count ?></p><p class="stat-lbl">UL2 (33kV)</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-ic ic-teal"><i class="fas fa-database"></i></div>
        <div><p class="stat-val"><?= $total_entries_all ?></p><p class="stat-lbl">Entries Today</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-ic ic-red"><i class="fas fa-tasks"></i></div>
        <div><p class="stat-val"><?= $total_activities_all ?></p><p class="stat-lbl">Activities Today</p></div>
    </div>
</div>

<!-- ── Tab Bar ─────────────────────────────────────────────────────────────── -->
<div class="tab-bar">
    <button class="tab-btn <?= $active_tab==='active'?'active':'' ?>"
            onclick="switchTab('active')">
        <i class="fas fa-circle" style="color:#22c55e;font-size:8px;"></i> Active Now
        <span style="background:rgba(255,255,255,.25);padding:1px 7px;border-radius:8px;font-size:11px;">
            <?= $active_count ?>
        </span>
    </button>
    <button class="tab-btn <?= $active_tab==='all'?'active':'' ?>"
            onclick="switchTab('all')">
        <i class="fas fa-users"></i> All Staff
    </button>
    <button class="tab-btn <?= $active_tab==='perf'?'active':'' ?>"
            onclick="switchTab('perf')">
        <i class="fas fa-chart-bar"></i> Performance
    </button>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     TAB 1 — ACTIVE NOW
     ══════════════════════════════════════════════════════════════════════════ -->
<div id="tab-active" class="tab-panel <?= $active_tab==='active'?'active':'' ?>">
    <div class="tcard">
        <div class="tcard-header">
            <span class="tcard-title">
                <span class="live-dot"></span>Currently Logged In
            </span>
            <span class="tcard-meta"><?= count($active_staff) ?> active session<?= count($active_staff)!==1?'s':'' ?></span>
        </div>
        <?php if (empty($active_staff)): ?>
            <div class="empty">
                <i class="fas fa-user-clock"></i>
                <p><strong>No active sessions right now</strong></p>
                <p style="font-size:13px;">No UL1 or UL2 staff are currently logged in.</p>
            </div>
        <?php else: ?>
        <table class="dt">
            <thead>
                <tr>
                    <th>Staff</th><th>Role</th><th>Location</th>
                    <th>Login</th><th>Duration</th><th>Entries</th><th>Activities</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($active_staff as $s): ?>
                <?php
                // session_duration is STORED GENERATED col in DB (decimal hours).
                // Use it when available. For active sessions (logout_time IS NULL)
                // the generated col returns NULL — compute elapsed from login_time.
                if ($s['session_duration'] !== null) {
                    $dur_h = (float)$s['session_duration'];
                    $dur_hh = floor($dur_h);
                    $dur_mm = round(($dur_h - $dur_hh) * 60);
                    $dur = "{$dur_hh}h {$dur_mm}m";
                } elseif ($s['login_time']) {
                    $elapsed = time() - strtotime($s['login_time']);
                    $dur_hh  = floor($elapsed / 3600);
                    $dur_mm  = floor(($elapsed % 3600) / 60);
                    $dur = "{$dur_hh}h {$dur_mm}m ●";  // ● = live
                } else {
                    $dur = '—';
                }
                ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($s['staff_name']) ?></strong><br>
                        <span style="font-size:11px;color:#94a3b8;"><?= $s['payroll_id'] ?></span>
                    </td>
                    <td><span class="badge b-<?= strtolower($s['role']) ?>"><?= $s['role'] ?></span></td>
                    <td style="font-size:12px;"><?= htmlspecialchars($s['assigned_location']) ?></td>
                    <td><?= $s['login_time'] ? date('H:i', strtotime($s['login_time'])) : '—' ?></td>
                    <td>
                        <span class="dur-pill"><?= $dur ?></span>
                    </td>
                    <td><strong><?= $s['entries_today'] ?></strong></td>
                    <td><?= $s['total_activities'] ?></td>
                    <td style="white-space:nowrap;">
                        <button class="btn-view"
                                onclick="openActivityModal('<?= $s['payroll_id'] ?>','<?= addslashes($s['staff_name']) ?>','<?= $s['role'] ?>','<?= addslashes($s['assigned_location']) ?>')">
                            <i class="fas fa-eye"></i> Activity
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     TAB 2 — ALL STAFF
     ══════════════════════════════════════════════════════════════════════════ -->
<div id="tab-all" class="tab-panel <?= $active_tab==='all'?'active':'' ?>">
    <!-- Filters -->
    <form method="GET" class="filters-bar">
        <input type="hidden" name="page"   value="dashboard">
        <input type="hidden" name="action" value="staff">
        <input type="hidden" name="tab"    value="all">
        <input type="hidden" name="date"   value="<?= $selected_date ?>">

        <div class="search-wrap fg">
            <label>Search</label>
            <div style="position:relative;">
                <i class="fas fa-search si" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:#94a3b8;"></i>
                <input type="text" name="search" value="<?= htmlspecialchars($search_query) ?>"
                       placeholder="Payroll ID or name…"
                       style="padding:9px 12px 9px 34px;width:100%;border:2px solid #e2e8f0;border-radius:8px;font-size:13px;">
            </div>
        </div>
        <div class="fg" style="max-width:160px;">
            <label>Role</label>
            <select name="role">
                <option value="all"  <?= $filter_role==='all' ?'selected':'' ?>>All Roles</option>
                <option value="UL1"  <?= $filter_role==='UL1' ?'selected':'' ?>>UL1 (11kV)</option>
                <option value="UL2"  <?= $filter_role==='UL2' ?'selected':'' ?>>UL2 (33kV)</option>
            </select>
        </div>
        <button type="submit" class="btn-filter"><i class="fas fa-search"></i> Search</button>
        <?php if ($search_query || $filter_role!=='all'): ?>
            <a href="?page=dashboard&action=staff&tab=all&date=<?= $selected_date ?>" class="btn-clear">
                <i class="fas fa-times"></i> Clear
            </a>
        <?php endif; ?>
    </form>

    <div class="tcard">
        <div class="tcard-header">
            <span class="tcard-title">All Operational Staff</span>
            <span class="tcard-meta"><?= count($all_staff) ?> staff</span>
        </div>
        <?php if (empty($all_staff)): ?>
            <div class="empty"><i class="fas fa-users"></i><p>No staff found.</p></div>
        <?php else: ?>
        <table class="dt">
            <thead>
                <tr>
                    <th>Staff</th><th>Role</th><th>Location</th>
                    <th>Status</th><th>Login</th><th>Entries</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($all_staff as $s): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($s['staff_name']) ?></strong><br>
                        <span style="font-size:11px;color:#94a3b8;"><?= $s['payroll_id'] ?></span>
                    </td>
                    <td><span class="badge b-<?= strtolower($s['role']) ?>"><?= $s['role'] ?></span></td>
                    <td style="font-size:12px;"><?= htmlspecialchars($s['assigned_location']) ?></td>
                    <td>
                        <span class="badge <?= $s['is_active']==='Yes'?'b-active':'b-offline' ?>">
                            <?= $s['is_active']==='Yes'?'Active':'Offline' ?>
                        </span>
                    </td>
                    <td><?= $s['login_time'] ? date('H:i', strtotime($s['login_time'])) : '—' ?></td>
                    <td><?= $s['entries_today'] ?></td>
                    <td style="white-space:nowrap;">
                        <button class="btn-view"
                                onclick="openActivityModal('<?= $s['payroll_id'] ?>','<?= addslashes($s['staff_name']) ?>','<?= $s['role'] ?>','<?= addslashes($s['assigned_location']) ?>')">
                            <i class="fas fa-eye"></i> Activity
                        </button>
                        <button class="btn-reassign"
                                onclick="openReassignModal(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)">
                            <i class="fas fa-exchange-alt"></i> Reassign
                        </button>
                        <button class="btn-perf"
                                onclick="openPerfModal('<?= $s['payroll_id'] ?>','<?= addslashes($s['staff_name']) ?>')">
                            <i class="fas fa-chart-line"></i> Profile
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     TAB 3 — PERFORMANCE
     ══════════════════════════════════════════════════════════════════════════ -->
<div id="tab-perf" class="tab-panel <?= $active_tab==='perf'?'active':'' ?>">

    <!-- Period selector -->
    <div class="period-tabs">
        <?php foreach (['day'=>'Today','week'=>'This Week','month'=>'This Month','year'=>'This Year'] as $k=>$lbl):
            $pt_url = "?page=dashboard&action=staff&tab=perf&date={$selected_date}&period={$k}&perf_page=1"
                    . ($perf_search   !== '' ? '&perf_search='   . urlencode($perf_search)   : '')
                    . ($perf_location !== '' ? '&perf_location=' . urlencode($perf_location) : '')
                    . ($perf_rating   !== '' ? '&perf_rating='   . urlencode($perf_rating)   : '');
        ?>
            <a href="<?= $pt_url ?>" class="pt <?= $perf_period===$k?'active':'' ?>"><?= $lbl ?></a>
        <?php endforeach; ?>
    </div>

    <?php
    // Build base URL that preserves period, search, location — used for rating card links
    $perf_base_url = "?page=dashboard&action=staff&tab=perf&date={$selected_date}&period={$perf_period}&perf_page=1"
        . ($perf_search   !== '' ? '&perf_search='   . urlencode($perf_search)   : '')
        . ($perf_location !== '' ? '&perf_location=' . urlencode($perf_location) : '');

    // Rating card helper: is this rating currently active?
    $rc_active = fn($r) => $perf_rating === $r;
    $rc_href   = fn($r) => $perf_base_url . ($perf_rating === $r ? '' : '&perf_rating=' . urlencode($r));
    ?>

    <!-- Rating summary cards — clickable to filter, highlighted when active -->
    <div class="stats-row" style="margin-bottom:16px;">
        <?php
        $rating_cfg = [
            'Excellent' => ['#22c55e','#166534','#dcfce7','fas fa-star',       'Excellent (≥90)'],
            'Good'      => ['#3b82f6','#1e40af','#dbeafe','fas fa-thumbs-up',  'Good (≥70)'],
            'Fair'      => ['#f59e0b','#92400e','#fef3c7','fas fa-exclamation-circle','Fair (≥50)'],
            'Poor'      => ['#ef4444','#991b1b','#fee2e2','fas fa-times-circle','Poor (<50)'],
        ];
        foreach ($rating_cfg as $rlbl => [$border, $text, $bg, $icon, $label]):
            $is_active = $rc_active($rlbl);
            $href      = $rc_href($rlbl);
            $count     = $all_rating_counts_prefilter[$rlbl];
        ?>
        <a href="<?= $href ?>" style="text-decoration:none;">
            <div class="stat-card perf-rating-card <?= $is_active ? 'rating-active' : '' ?>"
                 style="border-left:4px solid <?= $border ?>;<?= $is_active ? "background:{$bg};box-shadow:0 0 0 2px {$border};" : '' ?>
                        cursor:pointer;transition:all .2s;">
                <div class="stat-ic" style="background:<?= $bg ?>;color:<?= $text ?>;"><i class="<?= $icon ?>"></i></div>
                <div>
                    <p class="stat-val" style="color:<?= $text ?>;font-size:26px;font-weight:800;"><?= $count ?></p>
                    <p class="stat-lbl" style="color:<?= $text ?>;<?= $is_active ? 'font-weight:700;' : '' ?>"><?= $label ?></p>
                    <?php if ($is_active): ?>
                        <p style="font-size:10px;color:<?= $text ?>;opacity:.75;margin-top:2px;">▼ Filtered</p>
                    <?php endif; ?>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Performance filters bar: search + ISS/TS location + clear -->
    <?php
    $has_perf_filters = $perf_search !== '' || $perf_location !== '' || $perf_rating !== '';
    $clear_url = "?page=dashboard&action=staff&tab=perf&date={$selected_date}&period={$perf_period}";
    ?>
    <form method="GET" class="filters-bar" style="margin-bottom:14px;flex-wrap:wrap;gap:10px;">
        <input type="hidden" name="page"      value="dashboard">
        <input type="hidden" name="action"    value="staff">
        <input type="hidden" name="tab"       value="perf">
        <input type="hidden" name="date"      value="<?= $selected_date ?>">
        <input type="hidden" name="period"    value="<?= $perf_period ?>">
        <input type="hidden" name="perf_page" value="1">
        <?php if ($perf_rating !== ''): ?>
            <input type="hidden" name="perf_rating" value="<?= htmlspecialchars($perf_rating) ?>">
        <?php endif; ?>

        <!-- Text search -->
        <div class="fg" style="flex:2;min-width:200px;">
            <label>Search Staff</label>
            <div style="position:relative;">
                <i class="fas fa-search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:12px;pointer-events:none;"></i>
                <input type="text" name="perf_search"
                       value="<?= htmlspecialchars($perf_search) ?>"
                       placeholder="Payroll ID or name…"
                       style="padding:9px 10px 9px 30px;width:100%;border:2px solid #e2e8f0;border-radius:8px;font-size:13px;">
            </div>
        </div>

        <!-- ISS / TS Location filter -->
        <div class="fg" style="flex:2;min-width:180px;">
            <label>ISS / Transmission Station</label>
            <select name="perf_location" style="width:100%;padding:9px 10px;border:2px solid #e2e8f0;border-radius:8px;font-size:13px;<?= $perf_location !== '' ? 'border-color:#3b82f6;background:#eff6ff;' : '' ?>">
                <option value="">— All Locations —</option>
                <optgroup label="── 11kV Injection Substations (UL1) ──">
                    <?php foreach ($all_iss as $iss): ?>
                        <option value="<?= htmlspecialchars($iss['iss_code']) ?>"
                                <?= $perf_location === $iss['iss_code'] ? 'selected' : '' ?>>
                            ⚡ <?= htmlspecialchars($iss['iss_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="── 33kV Transmission Stations (UL2) ──">
                    <?php foreach ($all_ts as $ts): ?>
                        <option value="<?= htmlspecialchars($ts['ts_code']) ?>"
                                <?= $perf_location === $ts['ts_code'] ? 'selected' : '' ?>>
                            🔌 <?= htmlspecialchars($ts['station_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </optgroup>
            </select>
        </div>

        <!-- Rating quick-filter chips (secondary to the cards above) -->
        <div class="fg" style="flex:1.5;min-width:160px;">
            <label>Rating Filter</label>
            <select name="perf_rating" style="width:100%;padding:9px 10px;border:2px solid #e2e8f0;border-radius:8px;font-size:13px;<?= $perf_rating !== '' ? 'border-color:#3b82f6;background:#eff6ff;' : '' ?>">
                <option value="">— All Ratings —</option>
                <option value="Excellent" <?= $perf_rating==='Excellent'?'selected':'' ?>>⭐ Excellent (≥90)</option>
                <option value="Good"      <?= $perf_rating==='Good'     ?'selected':'' ?>>👍 Good (≥70)</option>
                <option value="Fair"      <?= $perf_rating==='Fair'     ?'selected':'' ?>>⚠️ Fair (≥50)</option>
                <option value="Poor"      <?= $perf_rating==='Poor'     ?'selected':'' ?>>❌ Poor (&lt;50)</option>
            </select>
        </div>

        <div style="display:flex;gap:8px;align-items:flex-end;padding-bottom:1px;">
            <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Filter</button>
            <?php if ($has_perf_filters): ?>
                <a href="<?= $clear_url ?>" class="btn-clear" style="display:inline-flex;align-items:center;gap:6px;white-space:nowrap;">
                    <i class="fas fa-times"></i> Clear
                </a>
            <?php endif; ?>
        </div>
    </form>

    <?php if ($perf_rating !== '' || $perf_location !== ''): ?>
    <div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;">
        <?php if ($perf_rating !== ''): ?>
            <span class="rate-chip" style="background:#dbeafe;color:#1e40af;padding:5px 12px;font-size:12px;">
                Rating: <?= htmlspecialchars($perf_rating) ?>
                <a href="<?= $perf_base_url ?>" style="color:#1e40af;margin-left:6px;font-weight:700;">×</a>
            </span>
        <?php endif; ?>
        <?php if ($perf_location !== ''): ?>
            <?php
            $loc_label = '';
            foreach ($all_iss as $_il) { if ($_il['iss_code']===$perf_location) $loc_label='⚡ '.$_il['iss_name']; }
            foreach ($all_ts  as $_tl) { if ($_tl['ts_code'] ===$perf_location) $loc_label='🔌 '.$_tl['station_name']; }
            ?>
            <span class="rate-chip" style="background:#dcfce7;color:#166534;padding:5px 12px;font-size:12px;">
                Location: <?= htmlspecialchars($loc_label) ?>
                <a href="<?= $perf_base_url . ($perf_rating !== '' ? '&perf_rating='.urlencode($perf_rating) : '') ?>" style="color:#166534;margin-left:6px;font-weight:700;">×</a>
            </span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="tcard">
        <div class="tcard-header">
            <span class="tcard-title">Staff Performance — <?= ucfirst($perf_period) ?></span>
            <span class="tcard-meta">
                Period: <?= date('d M Y', strtotime($period_start)) ?>
                <?= $period_start !== $period_end ? ' → '.date('d M Y', strtotime($period_end)) : '' ?>
                &nbsp;|&nbsp; Showing <?= ($perf_page-1)*10+1 ?>–<?= min($perf_page*10, $total_perf_staff) ?>
                of <?= $total_perf_staff ?>
            </span>
        </div>

        <!-- Scoring legend -->
        <div style="background:#f8fafc;border-radius:8px;padding:12px 16px;margin-bottom:16px;
                    font-size:12px;color:#475569;line-height:1.8;">
            <strong>Scoring:</strong>
            Corrections (60% weight) — ideal ≤ 1/day → score = 100 − (actual ÷ <?= $period_map[$perf_period][0]===$period_map[$perf_period][1]?'1':$total_perf_staff ?> × 100).
            Explanations (40% weight) — ideal ≤ 3/day → similar scale.
            <strong>100 = perfect, 0 = worst.</strong>
            &nbsp;|&nbsp;
            <span class="rate-chip" style="background:#dcfce7;color:#166534;">Excellent ≥90</span>
            <span class="rate-chip" style="background:#dbeafe;color:#1e40af;margin:0 4px;">Good ≥70</span>
            <span class="rate-chip" style="background:#fef3c7;color:#92400e;">Fair ≥50</span>
            <span class="rate-chip" style="background:#fee2e2;color:#991b1b;margin-left:4px;">Poor &lt;50</span>
        </div>

        <?php if (empty($perf_rows)): ?>
            <div class="empty"><i class="fas fa-chart-bar"></i><p>No data.</p></div>
        <?php else: ?>
        <table class="dt">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Staff</th>
                    <th>Role / Location</th>
                    <th>Corrections<br><small style="font-weight:400;color:#94a3b8;">(max 1/day)</small></th>
                    <th>Corr Score<br><small style="font-weight:400;">(60% weight)</small></th>
                    <th>Explanations<br><small style="font-weight:400;color:#94a3b8;">(max 3/day)</small></th>
                    <th>Expl Score<br><small style="font-weight:400;">(40% weight)</small></th>
                    <th>Aggregate</th>
                    <th>Profile</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($perf_rows as $i => $p): ?>
                <?php
                $global_rank = ($perf_page-1)*10 + $i + 1;
                $bar_color = fn($s) => $s>=90?'#22c55e':($s>=70?'#3b82f6':($s>=50?'#f59e0b':'#ef4444'));
                ?>
                <tr>
                    <td style="color:#94a3b8;font-size:12px;"><?= $global_rank ?></td>
                    <td>
                        <strong><?= htmlspecialchars($p['staff_name']) ?></strong><br>
                        <span style="font-size:11px;color:#94a3b8;"><?= $p['payroll_id'] ?></span>
                    </td>
                    <td>
                        <span class="badge b-<?= strtolower($p['role']) ?>"><?= $p['role'] ?></span><br>
                        <span style="font-size:11px;color:#64748b;"><?= htmlspecialchars($p['assigned_location']) ?></span>
                    </td>
                    <td style="text-align:center;">
                        <strong style="font-size:18px;color:<?= $p['corrections_count']>$p['days_in_period']?'#dc2626':'#0f172a' ?>">
                            <?= $p['corrections_count'] ?>
                        </strong>
                        <div style="font-size:10px;color:#94a3b8;">/ <?= $p['corr_max'] ?> threshold</div>
                    </td>
                    <td style="min-width:120px;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <div style="flex:1;">
                                <div style="font-size:13px;font-weight:700;color:<?= $bar_color($p['corr_score']) ?>">
                                    <?= $p['corr_score'] ?>
                                    <span class="rate-chip"
                                          style="background:<?= $p['corr_rating'][1] ?>22;color:<?= $p['corr_rating'][1] ?>">
                                        <?= $p['corr_rating'][0] ?>
                                    </span>
                                </div>
                                <div class="score-bar-wrap">
                                    <div class="score-bar" style="width:<?= $p['corr_score'] ?>%;background:<?= $bar_color($p['corr_score']) ?>"></div>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td style="text-align:center;">
                        <strong style="font-size:18px;color:<?= $p['explanations_count']>$p['expl_max']?'#dc2626':'#0f172a' ?>">
                            <?= $p['explanations_count'] ?>
                        </strong>
                        <div style="font-size:10px;color:#94a3b8;">/ <?= $p['expl_max'] ?> threshold</div>
                    </td>
                    <td style="min-width:120px;">
                        <div style="font-size:13px;font-weight:700;color:<?= $bar_color($p['expl_score']) ?>">
                            <?= $p['expl_score'] ?>
                            <span class="rate-chip"
                                  style="background:<?= $p['expl_rating'][1] ?>22;color:<?= $p['expl_rating'][1] ?>">
                                <?= $p['expl_rating'][0] ?>
                            </span>
                        </div>
                        <div class="score-bar-wrap">
                            <div class="score-bar" style="width:<?= $p['expl_score'] ?>%;background:<?= $bar_color($p['expl_score']) ?>"></div>
                        </div>
                    </td>
                    <td style="min-width:130px;">
                        <div style="font-size:20px;font-weight:800;color:<?= $bar_color($p['aggregate_score']) ?>">
                            <?= $p['aggregate_score'] ?>
                            <span style="font-size:11px;color:#94a3b8;">/100</span>
                        </div>
                        <div class="score-bar-wrap">
                            <div class="score-bar" style="width:<?= $p['aggregate_score'] ?>%;background:<?= $bar_color($p['aggregate_score']) ?>"></div>
                        </div>
                        <div style="margin-top:3px;">
                            <span class="rate-chip"
                                  style="background:<?= $p['agg_rating'][1] ?>22;color:<?= $p['agg_rating'][1] ?>">
                                <?= $p['agg_rating'][0] ?>
                            </span>
                        </div>
                    </td>
                    <td>
                        <button class="btn-perf"
                                onclick="openPerfModal('<?= $p['payroll_id'] ?>','<?= addslashes($p['staff_name']) ?>')">
                            <i class="fas fa-user-chart"></i> View
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1):
            // Build the persistent filter suffix for all pagination links
            $pg_suffix = ($perf_search   !== '' ? '&perf_search='   . urlencode($perf_search)   : '')
                       . ($perf_location !== '' ? '&perf_location=' . urlencode($perf_location) : '')
                       . ($perf_rating   !== '' ? '&perf_rating='   . urlencode($perf_rating)   : '');
            $pg_base   = "?page=dashboard&action=staff&tab=perf&date={$selected_date}&period={$perf_period}&perf_page=";
        ?>
        <div class="pagination">
            <a href="<?= $pg_base . max(1,$perf_page-1) . $pg_suffix ?>"
               class="pg-btn <?= $perf_page<=1?'disabled':'' ?>">‹ Prev</a>
            <?php for ($pg=1; $pg<=$total_pages; $pg++): ?>
                <a href="<?= $pg_base . $pg . $pg_suffix ?>"
                   class="pg-btn <?= $pg===$perf_page?'active':'' ?>"><?= $pg ?></a>
            <?php endfor; ?>
            <a href="<?= $pg_base . min($total_pages,$perf_page+1) . $pg_suffix ?>"
               class="pg-btn <?= $perf_page>=$total_pages?'disabled':'' ?>">Next ›</a>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

</div><!-- /.main-content -->

<!-- ═══════════════════════════════════════════════════════════════════════════
     MODAL — Activity Detail
     ═══════════════════════════════════════════════════════════════════════════ -->
<div id="actModal" class="modal">
    <div class="mc lg">
        <div class="mh">
            <div>
                <h3 id="actModalName">Loading…</h3>
                <div class="msub" id="actModalMeta"></div>
            </div>
            <span class="mclose" onclick="closeModal('actModal')">&times;</span>
        </div>
        <div class="mb" id="actModalBody">
            <div style="text-align:center;padding:40px;">
                <i class="fas fa-spinner fa-spin" style="font-size:28px;color:#3b82f6;"></i>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════
     MODAL — Performance Profile
     ═══════════════════════════════════════════════════════════════════════════ -->
<div id="perfModal" class="modal">
    <div class="mc xl">
        <div class="mh">
            <div>
                <h3 id="perfModalName">Performance Profile</h3>
                <div class="msub" id="perfModalMeta"></div>
            </div>
            <span class="mclose" onclick="closeModal('perfModal')">&times;</span>
        </div>
        <div class="mb" id="perfModalBody">
            <div style="text-align:center;padding:40px;">
                <i class="fas fa-spinner fa-spin" style="font-size:28px;color:#6366f1;"></i>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════════════
     MODAL — Reassign Staff
     ═══════════════════════════════════════════════════════════════════════════ -->
<div id="reassignModal" class="modal">
    <div class="mc lg">
        <div class="mh">
            <div>
                <h3>🔄 Reassign Staff Member</h3>
                <div class="msub" id="reassignSubtitle"></div>
            </div>
            <span class="mclose" onclick="closeModal('reassignModal')">&times;</span>
        </div>
        <div class="mb">
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                Select the type of reassignment. All changes are permanently logged with reason and timestamp.
            </div>

            <!-- Reassignment type -->
            <div style="margin-bottom:16px;">
                <label style="font-size:12px;font-weight:700;color:#475569;text-transform:uppercase;display:block;margin-bottom:8px;">
                    Reassignment Type
                </label>
                <div class="reassign-type-grid" id="reassignTypeGrid">
                    <div class="rt-opt" id="rt-iss-iss" onclick="setReassignType('iss-iss')">
                        <div class="rt-opt-icon">⚡→⚡</div>
                        <div class="rt-opt-title">ISS → ISS</div>
                        <div class="rt-opt-desc">Move UL1 to another ISS</div>
                    </div>
                    <div class="rt-opt" id="rt-ts-ts" onclick="setReassignType('ts-ts')">
                        <div class="rt-opt-icon">🔌→🔌</div>
                        <div class="rt-opt-title">TS → TS</div>
                        <div class="rt-opt-desc">Move UL2 to another TS</div>
                    </div>
                    <div class="rt-opt" id="rt-cross" onclick="setReassignType('cross')">
                        <div class="rt-opt-icon">⚡↔🔌</div>
                        <div class="rt-opt-title">Cross-Voltage</div>
                        <div class="rt-opt-desc">Switch between UL1 and UL2</div>
                    </div>
                </div>
            </div>

            <div id="reassignTypeWarning" class="warn-box" style="display:none;">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Cross-voltage reassignment</strong> will change the staff member's role and clear their current assignment.
            </div>

            <form id="reassignForm">
                <input type="hidden" id="rPayrollId"    name="payroll_id">
                <input type="hidden" id="rCurrentRole"  name="current_role">
                <input type="hidden" id="rNewRole"      name="new_role">
                <input type="hidden" id="rReassignType" name="reassign_type">

                <!-- ISS→ISS fields -->
                <div id="field-iss-iss" class="reassign-fields" style="display:none;">
                    <div class="form-group">
                        <label>New ISS Assignment</label>
                        <select id="newIssCode" name="new_iss_code">
                            <option value="">— Select ISS —</option>
                            <?php foreach ($all_iss as $iss): ?>
                                <option value="<?= $iss['iss_code'] ?>"><?= htmlspecialchars($iss['iss_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- TS→TS fields -->
                <div id="field-ts-ts" class="reassign-fields" style="display:none;">
                    <div class="form-group">
                        <label>New Transmission Station</label>
                        <select id="newTsCode" name="new_ts_code" onchange="loadFeedersForTs()">
                            <option value="">— Select Transmission Station —</option>
                            <?php foreach ($all_ts as $ts): ?>
                                <option value="<?= $ts['ts_code'] ?>"><?= htmlspecialchars($ts['station_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>New 33kV Feeder</label>
                        <select id="new33kvCode" name="new_33kv_code">
                            <option value="">— Select TS first —</option>
                        </select>
                    </div>
                </div>

                <!-- Cross-voltage fields -->
                <div id="field-cross" class="reassign-fields" style="display:none;">
                    <div class="form-group">
                        <label>New Role</label>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                            <div class="rt-opt" id="cross-ul1" onclick="setCrossRole('UL1')">
                                <div class="rt-opt-icon">⚡</div>
                                <div class="rt-opt-title">UL1 — 11kV</div>
                            </div>
                            <div class="rt-opt" id="cross-ul2" onclick="setCrossRole('UL2')">
                                <div class="rt-opt-icon">🔌</div>
                                <div class="rt-opt-title">UL2 — 33kV</div>
                            </div>
                        </div>
                    </div>
                    <div id="cross-iss-group" style="display:none;" class="form-group">
                        <label>New ISS Assignment</label>
                        <select id="crossIssCode" name="cross_iss_code">
                            <option value="">— Select ISS —</option>
                            <?php foreach ($all_iss as $iss): ?>
                                <option value="<?= $iss['iss_code'] ?>"><?= htmlspecialchars($iss['iss_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="cross-ts-group" style="display:none;" class="form-group">
                        <label>New Transmission Station</label>
                        <select id="crossTsCode" name="cross_ts_code" onchange="loadFeedersForCrossTs()">
                            <option value="">— Select TS —</option>
                            <?php foreach ($all_ts as $ts): ?>
                                <option value="<?= $ts['ts_code'] ?>"><?= htmlspecialchars($ts['station_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="cross-feeder-group" style="display:none;" class="form-group">
                        <label>New 33kV Feeder</label>
                        <select id="cross33kvCode" name="cross_33kv_code">
                            <option value="">— Select TS first —</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label>Reason for Reassignment <span style="color:#ef4444;">*</span></label>
                    <textarea id="reassignReason" name="reason" rows="3"
                              placeholder="Provide a clear reason for this reassignment…" required></textarea>
                </div>

                <button type="submit" class="btn-full" id="reassignSubmitBtn" disabled>
                    <i class="fas fa-exchange-alt"></i> Confirm Reassignment
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Toast container -->
<div class="toast-wrap" id="toastWrap"></div>

<!-- Chart.js for performance modal -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// ── Helpers ───────────────────────────────────────────────────────────────────
const SELECTED_DATE = '<?= $selected_date ?>';
const BASE_URL = '<?= (isset($_SERVER['HTTPS'])&&$_SERVER['HTTPS']==='on'?'https':'http').'://'.$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME'] ?>';
const FEEDERSBY_TS = <?= json_encode($feeders_by_ts) ?>;

function updateParam(key, val) {
    const u = new URLSearchParams(window.location.search);
    u.set(key, val);
    return window.location.pathname + '?' + u.toString();
}

function toast(msg, type='s') {
    const w = document.getElementById('toastWrap');
    const t = document.createElement('div');
    t.className = 'toast ' + type;
    t.textContent = msg;
    w.appendChild(t);
    setTimeout(() => t.remove(), 4500);
}

function closeModal(id) {
    document.getElementById(id).classList.remove('open');
    document.body.style.overflow = 'auto';
}
function openModal(id) {
    document.getElementById(id).classList.add('open');
    document.body.style.overflow = 'hidden';
}

// Close on backdrop click
document.querySelectorAll('.modal').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) closeModal(m.id); });
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.modal.open').forEach(m => closeModal(m.id));
});

// ── Tab switching ─────────────────────────────────────────────────────────────
function switchTab(tab) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    event.currentTarget.classList.add('active');
    // Update URL without reload
    const u = new URLSearchParams(window.location.search);
    u.set('tab', tab);
    history.replaceState({}, '', window.location.pathname + '?' + u.toString());
}

// ── Activity Modal ────────────────────────────────────────────────────────────
function openActivityModal(pid, name, role, location) {
    document.getElementById('actModalName').textContent = name;
    document.getElementById('actModalMeta').textContent = pid + ' • ' + role + ' • ' + location;
    document.getElementById('actModalBody').innerHTML =
        '<div style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin" style="font-size:28px;color:#3b82f6;"></i></div>';
    openModal('actModal');

    fetch(BASE_URL + '?page=dashboard&ajax=details&payroll_id=' + encodeURIComponent(pid) + '&date=' + SELECTED_DATE)
        .then(r => r.json())
        .then(data => {
            if (data.error) throw new Error(data.error);

            let dur = data.session_duration || '—';  // pre-formatted by PHP (e.g. "3h 22m")
            let html = `
                <div class="profile-grid">
                    <div class="prof-stat">
                        <div class="prof-stat-val">${data.login_time || '—'}</div>
                        <div class="prof-stat-lbl">Login</div>
                    </div>
                    <div class="prof-stat">
                        <div class="prof-stat-val">${data.logout_time || 'Active'}</div>
                        <div class="prof-stat-lbl">Logout</div>
                    </div>
                    <div class="prof-stat">
                        <div class="prof-stat-val">${dur}</div>
                        <div class="prof-stat-lbl">Duration</div>
                    </div>
                    <div class="prof-stat">
                        <div class="prof-stat-val">${data.total_activities || 0}</div>
                        <div class="prof-stat-lbl">Activities</div>
                    </div>
                </div>
                <h4 style="font-size:14px;font-weight:700;color:#0f172a;margin:0 0 10px;">
                    <i class="fas fa-clock"></i> Activity Timeline
                </h4>`;

            if (data.activities && data.activities.length > 0) {
                html += '<div class="timeline">';
                data.activities.forEach(a => {
                    html += `<div class="tl-item">
                        <div class="tl-dot"></div>
                        <div class="tl-time">${a.time}</div>
                        <div class="tl-desc">${a.description}
                            <span class="tl-type">${a.type}</span>
                        </div>
                    </div>`;
                });
                html += '</div>';
            } else {
                html += '<p style="text-align:center;color:#94a3b8;padding:20px;">No activities recorded for this date.</p>';
            }

            document.getElementById('actModalBody').innerHTML = html;
        })
        .catch(err => {
            document.getElementById('actModalBody').innerHTML =
                '<p style="color:#ef4444;text-align:center;padding:20px;">Error: ' + err.message + '</p>';
        });
}

// ── Performance Profile Modal ─────────────────────────────────────────────────
let perfCharts = [];

function openPerfModal(pid, name) {
    document.getElementById('perfModalName').textContent = name + ' — Performance Profile';
    document.getElementById('perfModalMeta').textContent = pid;
    document.getElementById('perfModalBody').innerHTML =
        '<div style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin" style="font-size:28px;color:#6366f1;"></i></div>';
    openModal('perfModal');

    // Destroy old charts
    perfCharts.forEach(c => c.destroy());
    perfCharts = [];

    fetch(BASE_URL + '?page=dashboard&ajax=perf&payroll_id=' + encodeURIComponent(pid) + '&date=' + SELECTED_DATE)
        .then(r => r.json())
        .then(d => {
            if (!d.success) throw new Error(d.message || 'Failed to load');
            renderPerfModal(d, pid, name);
        })
        .catch(err => {
            document.getElementById('perfModalBody').innerHTML =
                '<p style="color:#ef4444;text-align:center;padding:20px;">Error: ' + err.message + '</p>';
        });
}

function renderPerfModal(d, pid, name) {
    const barColor = s => s>=90?'#22c55e':s>=70?'#3b82f6':s>=50?'#f59e0b':'#ef4444';
    const ratingLabel = s => s>=90?'Excellent':s>=70?'Good':s>=50?'Fair':'Poor';

    const periods = ['day','week','month','year'];
    const periodLabels = {day:'Today',week:'Week',month:'Month',year:'Year'};

    let html = `
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:20px;">
            ${periods.map(p => {
                const sc = d.scores[p];
                return `<div class="prof-stat" style="border:2px solid ${barColor(sc.aggregate)}22;">
                    <div class="prof-stat-val" style="color:${barColor(sc.aggregate)};font-size:28px;">${sc.aggregate}</div>
                    <div class="prof-stat-lbl">${periodLabels[p]}</div>
                    <div style="font-size:10px;color:${barColor(sc.aggregate)};font-weight:700;">${ratingLabel(sc.aggregate)}</div>
                </div>`;
            }).join('')}
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
            ${periods.map(p => {
                const sc = d.scores[p];
                return `<div style="background:#f8fafc;border-radius:10px;padding:14px;">
                    <div style="font-size:13px;font-weight:700;color:#0f172a;margin-bottom:10px;">${periodLabels[p]}</div>
                    <div class="perf-row">
                        <div class="perf-label">Corrections</div>
                        <div class="perf-bar-wrap">
                            <div class="perf-bar" style="width:${sc.corr_score}%;background:${barColor(sc.corr_score)};"></div>
                        </div>
                        <div class="perf-val" style="color:${barColor(sc.corr_score)}">${sc.corr_score}</div>
                    </div>
                    <div class="perf-row">
                        <div class="perf-label">Explanations</div>
                        <div class="perf-bar-wrap">
                            <div class="perf-bar" style="width:${sc.expl_score}%;background:${barColor(sc.expl_score)};"></div>
                        </div>
                        <div class="perf-val" style="color:${barColor(sc.expl_score)}">${sc.expl_score}</div>
                    </div>
                    <div class="perf-row">
                        <div class="perf-label" style="font-weight:700;">Aggregate</div>
                        <div class="perf-bar-wrap">
                            <div class="perf-bar" style="width:${sc.aggregate}%;background:${barColor(sc.aggregate)};"></div>
                        </div>
                        <div class="perf-val" style="color:${barColor(sc.aggregate)};font-weight:800;">${sc.aggregate}</div>
                    </div>
                    <div style="font-size:11px;color:#94a3b8;margin-top:6px;">
                        Corrections: ${sc.corrections} | Explanations: ${sc.explanations}
                    </div>
                </div>`;
            }).join('')}
        </div>

        <div class="chart-row">
            <div style="background:#f8fafc;border-radius:10px;padding:14px;">
                <div style="font-size:13px;font-weight:700;color:#0f172a;margin-bottom:10px;">Aggregate Score Trend</div>
                <canvas id="perfTrendChart" height="180"></canvas>
            </div>
            <div style="background:#f8fafc;border-radius:10px;padding:14px;">
                <div style="font-size:13px;font-weight:700;color:#0f172a;margin-bottom:10px;">Corrections vs Explanations</div>
                <canvas id="perfCompChart" height="180"></canvas>
            </div>
        </div>`;

    document.getElementById('perfModalBody').innerHTML = html;

    // Trend chart
    const tc = document.getElementById('perfTrendChart').getContext('2d');
    const aggScores = periods.map(p => d.scores[p].aggregate);
    perfCharts.push(new Chart(tc, {
        type: 'line',
        data: {
            labels: periods.map(p => periodLabels[p]),
            datasets: [{
                label: 'Aggregate Score',
                data: aggScores,
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99,102,241,.1)',
                tension: 0.4, fill: true,
                pointBackgroundColor: aggScores.map(s => barColor(s)),
                pointRadius: 6
            }]
        },
        options: {
            responsive:true, maintainAspectRatio:false,
            scales: { y: { min:0, max:100, grid:{ color:'rgba(0,0,0,.05)' } } },
            plugins: { legend:{ display:false } }
        }
    }));

    // Comparison chart
    const cc = document.getElementById('perfCompChart').getContext('2d');
    perfCharts.push(new Chart(cc, {
        type: 'bar',
        data: {
            labels: periods.map(p => periodLabels[p]),
            datasets: [
                {
                    label: 'Corrections',
                    data: periods.map(p => d.scores[p].corrections),
                    backgroundColor: 'rgba(239,68,68,.7)'
                },
                {
                    label: 'Explanations',
                    data: periods.map(p => d.scores[p].explanations),
                    backgroundColor: 'rgba(245,158,11,.7)'
                }
            ]
        },
        options: {
            responsive:true, maintainAspectRatio:false,
            scales: { y:{ beginAtZero:true, grid:{ color:'rgba(0,0,0,.05)' } } },
            plugins: { legend:{ position:'top' } }
        }
    }));
}

// ── Reassign Modal ────────────────────────────────────────────────────────────
let currentStaff = null;
let currentReassignType = null;

function openReassignModal(staff) {
    currentStaff = staff;
    currentReassignType = null;

    document.getElementById('reassignSubtitle').textContent =
        staff.staff_name + ' (' + staff.payroll_id + ') — Currently: ' + staff.role + ' at ' + staff.assigned_location;

    document.getElementById('rPayrollId').value   = staff.payroll_id;
    document.getElementById('rCurrentRole').value = staff.role;
    document.getElementById('rNewRole').value     = staff.role;

    // Reset type selection
    document.querySelectorAll('.rt-opt').forEach(o => o.classList.remove('sel'));
    document.querySelectorAll('.reassign-fields').forEach(f => f.style.display='none');
    document.getElementById('reassignTypeWarning').style.display = 'none';
    document.getElementById('reassignSubmitBtn').disabled = true;
    document.getElementById('reassignReason').value = '';
    document.getElementById('reassignForm').reset();

    // Show/hide type options based on current role
    const isUL1 = staff.role === 'UL1';
    document.getElementById('rt-iss-iss').style.display = isUL1 ? 'block' : 'none';
    document.getElementById('rt-ts-ts').style.display   = !isUL1 ? 'block' : 'none';
    document.getElementById('rt-cross').style.display   = 'block';

    openModal('reassignModal');
}

function setReassignType(type) {
    currentReassignType = type;
    document.getElementById('rReassignType').value = type;

    document.querySelectorAll('.rt-opt').forEach(o => o.classList.remove('sel'));
    document.getElementById('rt-' + type).classList.add('sel');

    document.querySelectorAll('.reassign-fields').forEach(f => f.style.display='none');

    if (type === 'iss-iss') {
        document.getElementById('field-iss-iss').style.display = 'block';
        document.getElementById('rNewRole').value = 'UL1';
        document.getElementById('reassignTypeWarning').style.display = 'none';
    } else if (type === 'ts-ts') {
        document.getElementById('field-ts-ts').style.display = 'block';
        document.getElementById('rNewRole').value = 'UL2';
        document.getElementById('reassignTypeWarning').style.display = 'none';
    } else if (type === 'cross') {
        document.getElementById('field-cross').style.display = 'block';
        document.getElementById('reassignTypeWarning').style.display = 'block';
        // Reset cross sub-fields
        document.getElementById('cross-iss-group').style.display = 'none';
        document.getElementById('cross-ts-group').style.display = 'none';
        document.getElementById('cross-feeder-group').style.display = 'none';
        document.querySelectorAll('#cross-ul1, #cross-ul2').forEach(e => e.classList.remove('sel'));
    }

    document.getElementById('reassignSubmitBtn').disabled = false;
}

function setCrossRole(role) {
    document.getElementById('rNewRole').value = role;
    document.querySelectorAll('#cross-ul1, #cross-ul2').forEach(e => e.classList.remove('sel'));
    document.getElementById('cross-' + role.toLowerCase()).classList.add('sel');

    document.getElementById('cross-iss-group').style.display    = role === 'UL1' ? 'block' : 'none';
    document.getElementById('cross-ts-group').style.display     = role === 'UL2' ? 'block' : 'none';
    document.getElementById('cross-feeder-group').style.display = 'none';
}

function loadFeedersForTs() {
    const ts = document.getElementById('newTsCode').value;
    _populateFeeders('new33kvCode', ts);
}
function loadFeedersForCrossTs() {
    const ts = document.getElementById('crossTsCode').value;
    _populateFeeders('cross33kvCode', ts);
    document.getElementById('cross-feeder-group').style.display = ts ? 'block' : 'none';
}
function _populateFeeders(selectId, ts) {
    const sel = document.getElementById(selectId);
    sel.innerHTML = '<option value="">— Select feeder —</option>';
    (FEEDERSBY_TS[ts] || []).forEach(f => {
        const o = document.createElement('option');
        o.value = f.fdr33kv_code;
        o.textContent = f.fdr33kv_name;
        sel.appendChild(o);
    });
}

document.getElementById('reassignForm').addEventListener('submit', function(e) {
    e.preventDefault();
    if (!currentReassignType) { toast('Please select a reassignment type.','e'); return; }

    const fd = new FormData(this);
    fd.set('reassign_type', currentReassignType);

    // Consolidate cross fields
    if (currentReassignType === 'cross') {
        const newRole = document.getElementById('rNewRole').value;
        if (!newRole || newRole === currentStaff.role) {
            // same role is also fine for cross — just different location; but we need at least a location
        }
        if (newRole === 'UL1') {
            fd.set('new_iss_code', document.getElementById('crossIssCode').value);
        } else if (newRole === 'UL2') {
            fd.set('new_33kv_code', document.getElementById('cross33kvCode').value);
        }
    }

    const btn = document.getElementById('reassignSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing…';

    fetch('ajax/staff_reassign.php', { method:'POST', body:fd })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-exchange-alt"></i> Confirm Reassignment';
            if (res.success) {
                toast('✅ ' + res.message, 's');
                closeModal('reassignModal');
                setTimeout(() => location.reload(), 1500);
            } else {
                toast('❌ ' + res.message, 'e');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-exchange-alt"></i> Confirm Reassignment';
            toast('❌ Network error: ' + err.message, 'e');
        });
});
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
