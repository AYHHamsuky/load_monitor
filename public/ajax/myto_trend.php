<?php
/**
 * MYTO Trend AJAX Endpoint
 * Path: public/ajax/myto_trend.php
 *
 * Standalone JSON endpoint — no HTML output, no header/sidebar.
 * Called by the trend chart in app/views/myto/dashboard.php
 *
 * Project structure:
 *   load_monitor/
 *     app/
 *       bootstrap.php
 *       core/Database.php   ← Database::connect() singleton
 *     public/
 *       ajax/
 *         myto_trend.php    ← THIS FILE
 */

// ── Bootstrap ─────────────────────────────────────────────────────────────────
// public/ajax/ is 2 levels below project root; app/bootstrap.php is at root/app/
$bootstrap = __DIR__ . '/../../app/bootstrap.php';

if (!file_exists($bootstrap)) {
    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Server configuration error: bootstrap not found at ' . $bootstrap,
    ]);
    exit;
}

require_once $bootstrap;

// ── Get DB connection from singleton ──────────────────────────────────────────
try {
    $db = Database::connect();
} catch (Exception $e) {
    while (ob_get_level() > 0) ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage(),
    ]);
    exit;
}

// ── Output: pure JSON only ────────────────────────────────────────────────────
while (ob_get_level() > 0) ob_end_clean();
header('Content-Type: application/json');
header('Cache-Control: no-cache');

// ── Auth check (uses Auth class loaded by bootstrap) ─────────────────────────
if (!Auth::check()) {
    echo json_encode(['success' => false, 'message' => 'Unauthenticated']);
    exit;
}

// ── Get & validate parameters ─────────────────────────────────────────────────
$from     = trim($_GET['from'] ?? '');
$to       = trim($_GET['to']   ?? '');
$ts_codes = $_GET['ts'] ?? ['__ALL__'];
if (!is_array($ts_codes)) $ts_codes = [$ts_codes];

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ||
    !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)   ||
    $from > $to) {
    echo json_encode(['success' => false, 'message' => 'Invalid date range']);
    exit;
}

$is_all = in_array('__ALL__', $ts_codes, true);

// ── Build date spine ──────────────────────────────────────────────────────────
$spine = [];
$cur   = new DateTime($from);
$end   = new DateTime($to);
while ($cur <= $end) {
    $spine[] = $cur->format('Y-m-d');
    $cur->modify('+1 day');
}
$total_days = count($spine);

// ── Fetch TS list ─────────────────────────────────────────────────────────────
if ($is_all) {
    $ts_rows = $db->query("SELECT ts_code FROM transmission_stations ORDER BY ts_code")
                  ->fetchAll(PDO::FETCH_COLUMN);
} else {
    $ts_rows = array_values(
        array_filter($ts_codes, fn($c) => preg_match('/^[A-Za-z0-9_\-]+$/', $c))
    );
}

// ── Initialise per-TS/day data structure ──────────────────────────────────────
$ts_day_data = [];
foreach ($ts_rows as $tc) {
    foreach ($spine as $d) {
        $ts_day_data[$tc][$d] = [
            'actual'     => 0.0,
            'myto'       => 0.0,
            'faults'     => 0,
            'has_actual' => false,
            'has_myto'   => false,
        ];
    }
}

// ── Queries ───────────────────────────────────────────────────────────────────
if (!empty($ts_rows)) {
    $placeholders = implode(',', array_fill(0, count($ts_rows), '?'));

    // Actual 33kV load
    $q = $db->prepare("
        SELECT f.ts_code, d.entry_date, SUM(d.load_read) AS day_actual
        FROM   fdr33kv_data d
        JOIN   fdr33kv f ON f.fdr33kv_code = d.fdr33kv_code
        WHERE  f.ts_code IN ($placeholders)
          AND  d.entry_date BETWEEN ? AND ?
        GROUP  BY f.ts_code, d.entry_date
    ");
    $q->execute(array_merge($ts_rows, [$from, $to]));
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $tc = $row['ts_code'];
        $d  = $row['entry_date'];
        if (isset($ts_day_data[$tc][$d])) {
            $ts_day_data[$tc][$d]['actual']     += (float)$row['day_actual'];
            $ts_day_data[$tc][$d]['has_actual']  = true;
        }
    }

    // MYTO allocations
    $q2 = $db->prepare("
        SELECT ts_code, entry_date, SUM(myto_hour_allocation) AS day_myto
        FROM   myto_ts_allocation
        WHERE  ts_code IN ($placeholders)
          AND  entry_date BETWEEN ? AND ?
        GROUP  BY ts_code, entry_date
    ");
    $q2->execute(array_merge($ts_rows, [$from, $to]));
    foreach ($q2->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $tc = $row['ts_code'];
        $d  = $row['entry_date'];
        if (isset($ts_day_data[$tc][$d])) {
            $ts_day_data[$tc][$d]['myto']     += (float)$row['day_myto'];
            $ts_day_data[$tc][$d]['has_myto']  = true;
        }
    }

    // Faults (silently skipped if fault_flag column doesn't exist)
    try {
        $q3 = $db->prepare("
            SELECT f.ts_code, d.entry_date, COUNT(*) AS fault_hrs
            FROM   fdr33kv_data d
            JOIN   fdr33kv f ON f.fdr33kv_code = d.fdr33kv_code
            WHERE  f.ts_code IN ($placeholders)
              AND  d.entry_date BETWEEN ? AND ?
              AND  d.fault_flag = 1
            GROUP  BY f.ts_code, d.entry_date
        ");
        $q3->execute(array_merge($ts_rows, [$from, $to]));
        foreach ($q3->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $tc = $row['ts_code'];
            $d  = $row['entry_date'];
            if (isset($ts_day_data[$tc][$d])) {
                $ts_day_data[$tc][$d]['faults'] += (int)$row['fault_hrs'];
            }
        }
    } catch (\PDOException $e) {
        // fault_flag column may not exist — ignore
    }
}

// ── Companywide daily rollup ──────────────────────────────────────────────────
$days_out       = [];
$sum_actual     = 0.0;
$sum_myto       = 0.0;
$days_with_data = 0;

foreach ($spine as $d) {
    $day_actual = 0.0;
    $day_myto   = 0.0;
    $day_faults = 0;
    $has_any    = false;

    foreach ($ts_rows as $tc) {
        $cell = $ts_day_data[$tc][$d] ?? null;
        if ($cell) {
            $day_actual += $cell['actual'];
            $day_myto   += $cell['myto'];
            $day_faults += $cell['faults'];
            if ($cell['has_actual'] || $cell['has_myto']) $has_any = true;
        }
    }

    $variance = $day_myto > 0 ? round($day_actual - $day_myto, 2) : null;
    $util     = $day_myto > 0 ? round(($day_actual / $day_myto) * 100, 1) : null;

    $days_out[] = [
        'date'     => $d,
        'actual'   => $has_any ? round($day_actual, 2) : null,
        'myto'     => $has_any ? round($day_myto,   2) : null,
        'variance' => $variance,
        'util'     => $util,
        'faults'   => $day_faults,
    ];

    if ($has_any) {
        $sum_actual     += $day_actual;
        $sum_myto       += $day_myto;
        $days_with_data++;
    }
}

$sum_var  = round($sum_actual - $sum_myto, 2);
$avg_util = $sum_myto > 0 ? round(($sum_actual / $sum_myto) * 100, 1) : null;

// ── Per-TS data for multi-station mode ────────────────────────────────────────
$ts_data_out = [];
if (!$is_all) {
    foreach ($ts_rows as $tc) {
        foreach ($spine as $d) {
            $cell = $ts_day_data[$tc][$d] ?? null;
            $has  = $cell && ($cell['has_actual'] || $cell['has_myto']);
            $ts_data_out[$tc][$d] = $has ? [
                'actual'   => round($cell['actual'], 2),
                'myto'     => round($cell['myto'],   2),
                'variance' => $cell['myto'] > 0
                    ? round($cell['actual'] - $cell['myto'], 2)
                    : null,
            ] : null;
        }
    }
}

// ── Emit response ─────────────────────────────────────────────────────────────
echo json_encode([
    'success' => true,
    'days'    => $days_out,
    'ts_data' => $ts_data_out,
    'summary' => [
        'total_actual'   => round($sum_actual, 2),
        'total_myto'     => round($sum_myto,   2),
        'total_variance' => $sum_var,
        'avg_util'       => $avg_util,
        'days'           => $total_days,
        'days_with_data' => $days_with_data,
    ],
]);
exit;
