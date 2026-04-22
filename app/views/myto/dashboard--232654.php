<?php
/**
 * MYTO Dashboard View
 * Path: app/views/myto/dashboard.php
 * Loaded by: MytoDashboardController.php
 */

// ── AJAX: trend data handler ───────────────────────────────────────────────────
// Must run before header.php/sidebar.php emit any HTML.
if (isset($_GET['ajax']) && $_GET['ajax'] === 'trend') {
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Type: application/json');

    $from     = trim($_GET['from'] ?? '');
    $to       = trim($_GET['to']   ?? '');
    $ts_codes = $_GET['ts'] ?? ['__ALL__'];
    if (!is_array($ts_codes)) $ts_codes = [$ts_codes];

    // Validate dates
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)   ||
        $from > $to) {
        echo json_encode(['success'=>false,'message'=>'Invalid date range']);
        exit;
    }

    $is_all = in_array('__ALL__', $ts_codes, true);

    // Build date spine
    $spine = [];
    $cur   = new DateTime($from);
    $end   = new DateTime($to);
    while ($cur <= $end) {
        $spine[] = $cur->format('Y-m-d');
        $cur->modify('+1 day');
    }
    $total_days = count($spine);

    // Fetch all TS if needed
    if ($is_all) {
        $ts_rows = $db->query("SELECT ts_code FROM transmission_stations ORDER BY ts_code")->fetchAll(PDO::FETCH_COLUMN);
    } else {
        // Sanitise — only alphanumeric/underscore
        $ts_rows = array_filter($ts_codes, fn($c) => preg_match('/^[A-Za-z0-9_\-]+$/', $c));
        $ts_rows = array_values($ts_rows);
    }

    // Aggregate daily totals per TS
    // fdr33kv_data: ts_code, entry_date, load_mw (or sum of feeders)
    // myto_allocations: ts_code, entry_date, entry_hour, allocated_mw
    // We sum allocated_mw per day, and sum actual 33kV load per day

    $days_index   = array_flip($spine);  // date => index
    $ts_day_data  = [];  // [ts_code][date] = {actual, myto, faults}

    foreach ($ts_rows as $tc) {
        foreach ($spine as $d) {
            $ts_day_data[$tc][$d] = ['actual'=>0.0,'myto'=>0.0,'faults'=>0,'has_actual'=>false,'has_myto'=>false];
        }
    }

    // ── Actual 33kV load (from fdr33kv_data joined to fdr33kv for ts_code) ────
    // Aggregate by (ts_code, entry_date) summing all feeders
    if (!empty($ts_rows)) {
        $placeholders = implode(',', array_fill(0, count($ts_rows), '?'));
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
            $tc = $row['ts_code']; $d = $row['entry_date'];
            if (isset($ts_day_data[$tc][$d])) {
                $ts_day_data[$tc][$d]['actual']     += (float)$row['day_actual'];
                $ts_day_data[$tc][$d]['has_actual']  = true;
            }
        }

        // ── MYTO allocations ────────────────────────────────────────────────
        $q2 = $db->prepare("
            SELECT ts_code, entry_date, SUM(myto_hour_allocation) AS day_myto
            FROM   myto_ts_allocations
            WHERE  ts_code IN ($placeholders)
              AND  entry_date BETWEEN ? AND ?
            GROUP  BY ts_code, entry_date
        ");
        $q2->execute(array_merge($ts_rows, [$from, $to]));
        foreach ($q2->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $tc = $row['ts_code']; $d = $row['entry_date'];
            if (isset($ts_day_data[$tc][$d])) {
                $ts_day_data[$tc][$d]['myto']     += (float)$row['day_myto'];
                $ts_day_data[$tc][$d]['has_myto']  = true;
            }
        }

        // ── Faults (from fdr33kv_faults or fault flag in data) ──────────────
        // Using fault_count column in fdr33kv_data if available
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
                $tc = $row['ts_code']; $d = $row['entry_date'];
                if (isset($ts_day_data[$tc][$d])) {
                    $ts_day_data[$tc][$d]['faults'] += (int)$row['fault_hrs'];
                }
            }
        } catch (\PDOException $e) { /* fault_flag column may not exist */ }
    }

    // ── Build companywide daily rollup ────────────────────────────────────────
    $days_out  = [];
    $sum_actual = 0.0; $sum_myto = 0.0; $sum_var = 0.0;
    $days_with_data = 0;

    foreach ($spine as $d) {
        $day_actual = 0.0; $day_myto = 0.0; $day_faults = 0;
        $has_any = false;
        foreach ($ts_rows as $tc) {
            $cell = $ts_day_data[$tc][$d] ?? null;
            if ($cell) {
                $day_actual  += $cell['actual'];
                $day_myto    += $cell['myto'];
                $day_faults  += $cell['faults'];
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
            $sum_actual += $day_actual;
            $sum_myto   += $day_myto;
            $days_with_data++;
        }
    }
    $sum_var  = round($sum_actual - $sum_myto, 2);
    $avg_util = $sum_myto > 0 ? round(($sum_actual / $sum_myto) * 100, 1) : null;

    // ── Per-TS data for multi-station mode ────────────────────────────────────
    $ts_data_out = [];
    if (!$is_all) {
        foreach ($ts_rows as $tc) {
            foreach ($spine as $d) {
                $cell = $ts_day_data[$tc][$d] ?? null;
                $has  = $cell && ($cell['has_actual'] || $cell['has_myto']);
                $ts_data_out[$tc][$d] = $has ? [
                    'actual'   => round($cell['actual'], 2),
                    'myto'     => round($cell['myto'],   2),
                    'variance' => $cell['myto'] > 0 ? round($cell['actual'] - $cell['myto'], 2) : null,
                ] : null;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'days'    => $days_out,
        'ts_data' => $ts_data_out,
        'summary' => [
            'total_actual'    => round($sum_actual, 2),
            'total_myto'      => round($sum_myto,   2),
            'total_variance'  => $sum_var,
            'avg_util'        => $avg_util,
            'days'            => $total_days,
            'days_with_data'  => $days_with_data,
        ],
    ]);
    exit;
}
?>
<?php require __DIR__ . '/../layout/header.php'; ?>
<?php require __DIR__ . '/../layout/sidebar.php'; ?>

<div class="main-content">
<div class="myto-container">

<!-- ── PAGE HEADER ────────────────────────────────────────────────────────── -->
<div class="page-header">
    <div>
        <h1>⚡ MYTO Load Allocation</h1>
        <p class="subtitle">
            Lead Dispatch Control&nbsp;|&nbsp;
            <span id="opDayLabel"><?= date('l, F j, Y', strtotime($selected_date)) ?></span>
            <?php if ($is_today): ?>
                <small style="color:#f39c12;font-size:11px;">(operational day — closes 01:00)</small>
            <?php endif; ?>
        </p>
    </div>
    <div style="display:flex;gap:12px;align-items:center;">
        <!-- Date picker for history -->
        <input type="date" id="datePicker"
               value="<?= htmlspecialchars($selected_date) ?>"
               max="<?= $today ?>"
               style="padding:10px 14px;border:2px solid #e9ecef;border-radius:8px;
                      font-size:14px;font-family:inherit;cursor:pointer;"
               onchange="window.location.href='?page=myto_dashboard&date='+this.value">
        <?php if ($is_today): ?>
        <button class="btn-primary" onclick="openEntryModal(null)">
            <i class="fas fa-plus"></i> Add Allocation
        </button>
        <button class="btn-formula" onclick="openFormulaModal()">
            <i class="fas fa-sliders-h"></i> Formula
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- ── STAT CARDS ─────────────────────────────────────────────────────────── -->
<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-icon"><i class="fas fa-bolt"></i></div>
        <div class="stat-details">
            <h3><?= $stats['total_ts'] ?></h3>
            <p>Transmission Stations</p>
        </div>
    </div>
    <div class="stat-card orange">
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
        <div class="stat-details">
            <h3><?= $stats['hours_entered'] ?> / 24</h3>
            <p>Hours Entered</p>
        </div>
    </div>
    <div class="stat-card purple">
        <div class="stat-icon"><i class="fas fa-broadcast-tower"></i></div>
        <div class="stat-details">
            <h3><?= number_format($stats['total_myto'], 2) ?> MW</h3>
            <p>Total MYTO Allocated</p>
        </div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon"><i class="fas fa-arrow-up"></i></div>
        <div class="stat-details">
            <h3><?= number_format($stats['peak_myto'], 2) ?> MW</h3>
            <p>Peak MYTO Hour</p>
        </div>
    </div>
    <div class="stat-card teal">
        <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
        <div class="stat-details">
            <h3><?= number_format($stats['avg_myto'], 2) ?> MW</h3>
            <p>Average MYTO/Hour</p>
        </div>
    </div>
    <div class="stat-card indigo">
        <div class="stat-icon"><i class="fas fa-plug"></i></div>
        <div class="stat-details">
            <h3><?= number_format($stats['total_actual'], 2) ?> MW</h3>
            <p>Total Actual 33kV Load</p>
        </div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-details">
            <h3><?= $stats['fault_hours'] ?></h3>
            <p>Fault Hours (33kV)</p>
        </div>
    </div>
    <div class="stat-card info">
        <div class="stat-icon"><i class="fas fa-tasks"></i></div>
        <div class="stat-details">
            <h3><?= $stats['completion_pct'] ?>%</h3>
            <p>Day Completion</p>
        </div>
    </div>
</div>

<!-- ── FORMULA STATUS BANNER ──────────────────────────────────────────────── -->
<div class="formula-banner">
    <?php if (empty($active_formula)): ?>
        <div class="formula-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>No sharing formula configured.</strong>
            Click <strong>Formula</strong> above to set percentages for each transmission station
            before entering allocations.
        </div>
    <?php else: ?>
        <div class="formula-info">
            <i class="fas fa-info-circle"></i>
            <strong>Active Formula v<?= $formula_version ?>:</strong>
            <?php foreach ($active_formula as $i => $row): ?>
                <span class="formula-pill">
                    <?= htmlspecialchars($row['station_name']) ?>:
                    <strong><?= number_format($row['percentage'], 2) ?>%</strong>
                </span>
                <?= ($i < count($active_formula)-1) ? '<span style="color:#9ca3af">|</span>' : '' ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- ── 24-HOUR MATRIX ─────────────────────────────────────────────────────── -->
<div class="matrix-card">
    <div class="matrix-header">
        <h3>📊 Transmission Station × 24-Hour MYTO Matrix</h3>
        <span class="matrix-info">
            Rows = Transmission Stations &nbsp;|&nbsp;
            Each cell: <span class="legend-actual">Actual 33kV load</span> ÷
                       <span class="legend-myto">MYTO alloc</span>
            <?php if ($is_today): ?>
                &nbsp;|&nbsp; Click any cell to enter/edit
            <?php endif; ?>
        </span>
    </div>
    <div class="matrix-scroll">
        <table class="myto-matrix-table">
            <thead>
                <tr>
                    <th class="sticky-col">Station</th>
                    <?php for ($h = 0; $h <= 23; $h++): ?>
                        <th class="hour-col" title="<?= str_pad($h,2,'0',STR_PAD_LEFT) ?>:00–<?= str_pad($h,2,'0',STR_PAD_LEFT) ?>:59">
                            <span class="hour-top"><?= str_pad($h,2,'0',STR_PAD_LEFT) ?>:00</span>
                            <span class="hour-bot"><?= str_pad($h,2,'0',STR_PAD_LEFT) ?>:59</span>
                        </th>
                    <?php endfor; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($matrix as $ts_code => $ts_data): ?>
                <tr>
                    <td class="sticky-col station-name">
                        <?= htmlspecialchars($ts_data['station_name']) ?>
                    </td>
                    <?php for ($h = 0; $h <= 23; $h++): ?>
                        <?php
                        $cell        = $ts_data['hours'][$h];
                        $myto        = $cell['myto'];
                        $actual      = $cell['actual_load'];
                        $faults      = $cell['fault_count'];
                        $variance    = $cell['variance'];
                        $has_myto    = $myto !== null;
                        $has_actual  = $actual !== null;

                        // Cell class
                        if ($has_myto && $has_actual) {
                            $cell_class = $variance > 0.5
                                ? 'cell-over'
                                : ($variance < -0.5 ? 'cell-under' : 'cell-ok');
                        } elseif ($has_myto) {
                            $cell_class = 'cell-myto-only';
                        } elseif ($has_actual) {
                            $cell_class = 'cell-actual-only';
                        } else {
                            $cell_class = 'cell-empty';
                        }
                        if ($faults > 0) $cell_class .= ' has-faults';

                        // Clickable only for today
                        $onclick = $is_today
                            ? "openEntryModal({$h})"
                            : '';
                        ?>
                        <td class="matrix-cell <?= $cell_class ?>"
                            data-ts-code="<?= htmlspecialchars($ts_code) ?>"
                            data-hour="<?= $h ?>"
                            data-myto="<?= $has_myto ? $myto : '' ?>"
                            <?= $onclick ? "onclick=\"{$onclick}\"" : '' ?>
                            title="<?= htmlspecialchars($ts_data['station_name']) ?> — <?= str_pad($h,2,'0',STR_PAD_LEFT) ?>:00<?= $has_myto ? ' | MYTO: '.number_format($myto,2).' MW' : '' ?><?= $has_actual ? ' | Actual: '.number_format($actual,2).' MW' : '' ?><?= $faults > 0 ? ' | ⚠ '.$faults.' fault(s)' : '' ?>">
                            <?php if ($has_myto || $has_actual): ?>
                                <span class="cell-actual"><?= $has_actual ? number_format($actual, 2) : '—' ?></span>
                                <span class="cell-sep">÷</span>
                                <span class="cell-myto"><?= $has_myto  ? number_format($myto,  2) : '—' ?></span>
                                <?php if ($faults > 0): ?>
                                    <span class="cell-fault-dot" title="<?= $faults ?> fault(s)">⚠</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="cell-dash">–</span>
                            <?php endif; ?>
                        </td>
                    <?php endfor; ?>
                </tr>
                <?php endforeach; ?>

                <!-- ── Grand Total Row ── -->
                <tr class="grand-total-row">
                    <td class="sticky-col grand-total-label">
                        🌐 COMPANY TOTAL
                    </td>
                    <?php for ($h = 0; $h <= 23; $h++): ?>
                        <?php
                        $gt_myto   = $hourly_totals[$h]['myto'];
                        $gt_actual = $hourly_totals[$h]['actual'];
                        $gt_var    = $gt_myto > 0 ? round($gt_actual - $gt_myto, 2) : null;
                        ?>
                        <td class="matrix-cell grand-total-cell">
                            <?php if ($gt_myto > 0 || $gt_actual > 0): ?>
                                <span class="cell-actual"><?= $gt_actual > 0 ? number_format($gt_actual, 2) : '—' ?></span>
                                <span class="cell-sep">÷</span>
                                <span class="cell-myto"><?= $gt_myto > 0   ? number_format($gt_myto,  2) : '—' ?></span>
                                <?php if ($gt_var !== null): ?>
                                    <span class="gt-variance <?= $gt_var > 0.5 ? 'var-over' : ($gt_var < -0.5 ? 'var-under' : 'var-ok') ?>">
                                        <?= ($gt_var > 0 ? '+' : '') . number_format($gt_var, 2) ?>
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="cell-dash">–</span>
                            <?php endif; ?>
                        </td>
                    <?php endfor; ?>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ── FEEDER PERFORMANCE TABLE ───────────────────────────────────────────── -->
<div class="matrix-card" style="margin-top:24px;">
    <div class="matrix-header">
        <h3>📋 Station Summary</h3>
        <span class="matrix-info">Daily totals by Transmission Station</span>
    </div>
    <div class="metrics-scroll">
        <table class="metrics-table">
            <thead>
                <tr>
                    <th>Station</th>
                    <th>Total Actual Load (MW)</th>
                    <th>Total MYTO Allocated (MW)</th>
                    <th>Variance MW<br><small style="font-weight:400;opacity:.8;">(Actual − MYTO)</small></th>
                    <th>% Utilisation<br><small style="font-weight:400;opacity:.8;">(Actual ÷ MYTO)</small></th>
                    <th>% Variance<br><small style="font-weight:400;opacity:.8;">((Actual−MYTO)÷MYTO)</small></th>
                    <th>Fault Hours</th>
                    <th>Hours w/ MYTO</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($matrix as $ts_code => $ts_data):
                    $ts_total_myto   = 0.0;
                    $ts_total_actual = 0.0;
                    $ts_fault_hours  = 0;
                    $ts_myto_hours   = 0;
                    foreach ($ts_data['hours'] as $cell) {
                        if ($cell['myto'] !== null)   { $ts_total_myto   += $cell['myto'];   $ts_myto_hours++; }
                        if ($cell['actual_load'] !== null) $ts_total_actual += $cell['actual_load'];
                        if ($cell['fault_count'] > 0) $ts_fault_hours++;
                    }
                    // variance computed below in row
                ?>
                <?php
                    // Variance = Actual − MYTO (positive = used more than allocated)
                    $ts_variance     = $ts_total_myto > 0 ? round($ts_total_actual - $ts_total_myto, 2) : null;
                    $ts_pct_util     = $ts_total_myto > 0 ? round(($ts_total_actual / $ts_total_myto) * 100, 1) : null;
                    $ts_pct_variance = $ts_total_myto > 0 ? round((($ts_total_actual - $ts_total_myto) / $ts_total_myto) * 100, 1) : null;
                    // Variance colour: positive (actual > myto) = green, negative = red/orange
                    $ts_var_class = $ts_variance !== null
                        ? ($ts_variance > 0.5 ? 'var-pos' : ($ts_variance < -0.5 ? 'var-neg' : 'var-ok'))
                        : '';
                ?>
                <tr>
                    <td class="station-name-col">
                        <i class="fas fa-broadcast-tower" style="color:#667eea;margin-right:8px;"></i>
                        <?= htmlspecialchars($ts_data['station_name']) ?>
                    </td>
                    <td class="metric-value" style="color:#059669;"><?= number_format($ts_total_actual, 2) ?></td>
                    <td class="metric-value"><?= number_format($ts_total_myto, 2) ?></td>
                    <td class="metric-variance <?= $ts_var_class ?>">
                        <?= $ts_variance !== null ? (($ts_variance > 0 ? '+' : '') . number_format($ts_variance, 2)) : '—' ?>
                    </td>
                    <td class="metric-center <?= $ts_pct_util !== null ? ($ts_pct_util > 100 ? 'pct-over' : 'pct-ok') : '' ?>">
                        <?= $ts_pct_util !== null ? $ts_pct_util.'%' : '—' ?>
                    </td>
                    <td class="metric-center <?= $ts_pct_variance !== null ? ($ts_pct_variance > 0 ? 'pct-pos' : ($ts_pct_variance < 0 ? 'pct-neg' : '')) : '' ?>">
                        <?= $ts_pct_variance !== null ? (($ts_pct_variance > 0 ? '+' : '') . $ts_pct_variance.'%') : '—' ?>
                    </td>
                    <td class="metric-center <?= $ts_fault_hours > 0 ? 'has-faults' : '' ?>">
                        <?= $ts_fault_hours ?>
                    </td>
                    <td class="metric-center"><?= $ts_myto_hours ?>/24</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     COMPANYWIDE TOTALS ROW
     ══════════════════════════════════════════════════════════════════════════ -->
<?php
// Compute companywide totals from matrix
$cw_actual    = 0.0;
$cw_myto      = 0.0;
$cw_faults    = 0;
$cw_myto_hrs  = 0;

foreach ($matrix as $ts_code => $ts_data) {
    foreach ($ts_data['hours'] as $cell) {
        if ($cell['myto']        !== null) { $cw_myto     += $cell['myto'];        $cw_myto_hrs++; }
        if ($cell['actual_load'] !== null) { $cw_actual   += $cell['actual_load']; }
        if ($cell['fault_count']  >  0)    { $cw_faults++; }
    }
}
$cw_variance     = $cw_myto > 0 ? round($cw_actual - $cw_myto, 2) : null;
$cw_pct_util     = $cw_myto > 0 ? round(($cw_actual / $cw_myto) * 100, 1) : null;
$cw_pct_variance = $cw_myto > 0 ? round((($cw_actual - $cw_myto) / $cw_myto) * 100, 1) : null;
$cw_var_cls      = $cw_variance !== null ? ($cw_variance > 0 ? 'var-pos' : ($cw_variance < 0 ? 'var-neg' : 'var-ok')) : '';
?>
<div class="matrix-card" style="margin-top:16px;">
    <div class="matrix-header" style="background:linear-gradient(135deg,#0f172a,#1e293b);">
        <h3>🌐 Companywide Totals — <?= date('d M Y', strtotime($selected_date)) ?></h3>
        <span class="matrix-info" style="opacity:.8;">Aggregate across all Transmission Stations</span>
    </div>
    <div style="overflow-x:auto;">
        <table class="metrics-table">
            <thead>
                <tr>
                    <th>Total Actual Load (MW)</th>
                    <th>Total MYTO Allocated (MW)</th>
                    <th>Variance MW<br><small style="font-weight:400;opacity:.8;">(Actual − MYTO)</small></th>
                    <th>% Utilisation<br><small style="font-weight:400;opacity:.8;">(Actual ÷ MYTO)</small></th>
                    <th>% Variance<br><small style="font-weight:400;opacity:.8;">((Actual−MYTO)÷MYTO)</small></th>
                    <th>Fault Hours</th>
                    <th>Hours w/ MYTO</th>
                </tr>
            </thead>
            <tbody>
                <tr class="cw-total-row">
                    <td class="metric-value cw-actual"><?= number_format($cw_actual, 2) ?> MW</td>
                    <td class="metric-value cw-myto"><?= number_format($cw_myto,  2) ?> MW</td>
                    <td class="metric-variance <?= $cw_var_cls ?>">
                        <?= $cw_variance !== null
                            ? (($cw_variance > 0 ? '+' : '') . number_format($cw_variance, 2) . ' MW')
                            : '—' ?>
                    </td>
                    <td class="metric-center <?= $cw_pct_util !== null ? ($cw_pct_util > 100 ? 'pct-over' : 'pct-ok') : '' ?>">
                        <?= $cw_pct_util !== null ? $cw_pct_util . '%' : '—' ?>
                    </td>
                    <td class="metric-center <?= $cw_pct_variance !== null ? ($cw_pct_variance > 0 ? 'pct-pos' : ($cw_pct_variance < 0 ? 'pct-neg' : '')) : '' ?>">
                        <?= $cw_pct_variance !== null
                            ? (($cw_pct_variance > 0 ? '+' : '') . $cw_pct_variance . '%')
                            : '—' ?>
                    </td>
                    <td class="metric-center <?= $cw_faults > 0 ? 'has-faults' : '' ?>"><?= $cw_faults ?></td>
                    <td class="metric-center"><?= $cw_myto_hrs ?> / <?= count($matrix) * 24 ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     TREND CHART — Multi-period, multi-TS selection
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="matrix-card" id="trendSection" style="margin-top:24px;">
    <div class="matrix-header" style="background:linear-gradient(135deg,#1e40af,#3b82f6);">
        <div>
            <h3>📈 Load Trend Analysis</h3>
            <span class="matrix-info">Daily Actual vs MYTO over time — companywide or by station</span>
        </div>
    </div>

    <!-- ── Controls ── -->
    <div style="padding:18px 20px;background:#f8fafc;border-bottom:1px solid #e5e7eb;">
        <div style="display:flex;flex-wrap:wrap;gap:14px;align-items:flex-end;">

            <!-- Period preset chips -->
            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px;">Period</label>
                <div style="display:flex;gap:6px;flex-wrap:wrap;" id="periodChips">
                    <button class="period-chip active" data-period="7d"  onclick="selectPeriod(this)">Last 7 Days</button>
                    <button class="period-chip"        data-period="1m"  onclick="selectPeriod(this)">Month</button>
                    <button class="period-chip"        data-period="3m"  onclick="selectPeriod(this)">Quarter</button>
                    <button class="period-chip"        data-period="1y"  onclick="selectPeriod(this)">Year</button>
                    <button class="period-chip"        data-period="custom" onclick="selectPeriod(this)">Custom</button>
                </div>
            </div>

            <!-- Custom date range (hidden unless 'Custom' chosen) -->
            <div id="customRangeWrap" style="display:none;display:flex;gap:8px;align-items:flex-end;">
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#64748b;margin-bottom:4px;">From</label>
                    <input type="date" id="trendFrom" style="padding:8px 10px;border:2px solid #e2e8f0;border-radius:7px;font-size:13px;">
                </div>
                <div>
                    <label style="display:block;font-size:11px;font-weight:600;color:#64748b;margin-bottom:4px;">To</label>
                    <input type="date" id="trendTo" style="padding:8px 10px;border:2px solid #e2e8f0;border-radius:7px;font-size:13px;">
                </div>
            </div>

            <!-- Station multi-select -->
            <div style="flex:1;min-width:220px;">
                <label style="display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px;">
                    Transmission Stations <span style="font-weight:400;color:#94a3b8;">(multi-select)</span>
                </label>
                <div style="position:relative;">
                    <select id="trendTsSelect" multiple
                            style="width:100%;border:2px solid #e2e8f0;border-radius:8px;padding:6px 8px;
                                   font-size:13px;min-height:80px;max-height:120px;
                                   background:white;color:#1e293b;">
                        <option value="__ALL__" selected>🌐 Companywide (All Stations)</option>
                        <?php foreach ($all_ts as $ts): ?>
                            <option value="<?= htmlspecialchars($ts['ts_code']) ?>">
                                🔌 <?= htmlspecialchars($ts['station_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p style="font-size:11px;color:#94a3b8;margin-top:4px;">Hold Ctrl/Cmd to select multiple</p>
                </div>
            </div>

            <!-- Metric toggles -->
            <div>
                <label style="display:block;font-size:12px;font-weight:700;color:#475569;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px;">Show</label>
                <div style="display:flex;flex-direction:column;gap:5px;">
                    <label class="metric-toggle"><input type="checkbox" id="showActual" checked> <span>Actual Load</span></label>
                    <label class="metric-toggle"><input type="checkbox" id="showMyto"   checked> <span>MYTO Alloc</span></label>
                    <label class="metric-toggle"><input type="checkbox" id="showVar"          > <span>Variance MW</span></label>
                </div>
            </div>

            <button onclick="loadTrend()" class="btn-trend">
                <i class="fas fa-chart-line"></i> Load Chart
            </button>
        </div>
    </div>

    <!-- ── Chart area ── -->
    <div style="padding:20px;position:relative;min-height:320px;">
        <div id="trendLoading" style="display:none;position:absolute;inset:0;
             background:rgba(255,255,255,.85);display:none;align-items:center;
             justify-content:center;flex-direction:column;gap:12px;z-index:10;border-radius:0 0 14px 14px;">
            <div class="spinner"></div>
            <p style="color:#475569;font-size:14px;">Loading trend data…</p>
        </div>
        <canvas id="trendChart" style="width:100%;max-height:400px;"></canvas>
        <div id="trendEmpty" style="display:none;text-align:center;padding:60px 20px;color:#94a3b8;">
            <i class="fas fa-chart-area" style="font-size:40px;margin-bottom:12px;display:block;"></i>
            Select a period and click <strong>Load Chart</strong>
        </div>
        <!-- Summary stats bar -->
        <div id="trendStats" style="display:none;margin-top:16px;display:none;">
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:10px;">
                <div class="trend-stat-box" id="tsSumActual"></div>
                <div class="trend-stat-box" id="tsSumMyto"></div>
                <div class="trend-stat-box" id="tsSumVar"></div>
                <div class="trend-stat-box" id="tsSumUtil"></div>
                <div class="trend-stat-box" id="tsSumDays"></div>
            </div>
        </div>
    </div>
</div>

</div><!-- /.myto-container -->
</div><!-- /.main-content -->

<!-- ═══════════════════════════════════════════════════════════════════════════
     MODAL 1 — MYTO Hourly Allocation Entry
     ═══════════════════════════════════════════════════════════════════════════ -->
<div id="entryModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="entryModalTitle">⚡ MYTO Allocation Entry — <?= date('F j, Y', strtotime($today)) ?></h2>
            <span class="close" onclick="closeEntryModal()">&times;</span>
        </div>
        <form id="entryForm" onsubmit="return handleEntrySubmit(event)">
            <input type="hidden" name="action"      value="save_allocation">
            <input type="hidden" name="is_edit"     id="is_edit" value="0">
            <input type="hidden" name="use_custom_formula" id="use_custom_formula" value="0">
            <input type="hidden" name="custom_rows" id="custom_rows" value="">

            <!-- Hour -->
            <div class="form-group">
                <label for="entry_hour">Operational Hour <span class="req">*</span></label>
                <select name="entry_hour" id="entry_hour" required class="form-control"
                        onchange="loadExistingAllocation()">
                    <option value="">— Select Hour —</option>
                    <?php for ($h = 0; $h <= 23; $h++): ?>
                        <option value="<?= $h ?>">
                            <?= str_pad($h,2,'0',STR_PAD_LEFT) ?>:00 – <?= str_pad($h,2,'0',STR_PAD_LEFT) ?>:59
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <!-- Bulk MYTO value -->
            <div class="form-group">
                <label for="myto_allocation">Bulk MYTO Allocation (MW) <span class="req">*</span></label>
                <input type="number" step="0.01" min="0" id="myto_allocation"
                       name="myto_allocation" class="form-control"
                       placeholder="e.g. 850.00" required
                       oninput="updateFormulaPreview()">
                <small>Enter the total system MYTO allocation for the selected hour.</small>
            </div>

            <!-- Formula confirmation -->
            <div class="formula-confirm-box" id="formulaConfirmBox">
                <div class="formula-confirm-header">
                    <strong>📐 Active Sharing Formula (v<?= $formula_version ?>)</strong>
                    <div class="formula-confirm-q">Apply this formula to distribute the allocation?</div>
                </div>
                <div class="formula-preview-grid" id="formulaPreviewGrid">
                    <?php foreach ($active_formula as $row): ?>
                    <div class="fp-row">
                        <span class="fp-station"><?= htmlspecialchars($row['station_name']) ?></span>
                        <span class="fp-pct"><?= number_format($row['percentage'], 2) ?>%</span>
                        <span class="fp-value" id="fpv_<?= htmlspecialchars($row['ts_code']) ?>">—</span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="formula-yn-btns">
                    <button type="button" class="btn-yes" id="btnYes" onclick="confirmFormula(true)">
                        <i class="fas fa-check"></i> Yes, use this formula
                    </button>
                    <button type="button" class="btn-no" onclick="confirmFormula(false)">
                        <i class="fas fa-pen"></i> No, edit formula first
                    </button>
                </div>
                <div id="formulaConfirmed" style="display:none;" class="formula-confirmed-msg">
                    <i class="fas fa-check-circle"></i> Formula confirmed — ready to submit.
                </div>
            </div>

            <!-- No-formula warning -->
            <?php if (empty($active_formula)): ?>
            <div class="no-formula-warning">
                ⚠️ No sharing formula is configured yet. Click <strong>Formula</strong> in the header
                to set percentages before saving an allocation.
            </div>
            <?php endif; ?>

            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeEntryModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" id="submitEntryBtn" class="btn-primary"
                        <?= empty($active_formula) ? 'disabled' : '' ?>>
                    <i class="fas fa-save"></i> Save Allocation
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════════════════════
     MODAL 2 — Edit Sharing Formula
     ═══════════════════════════════════════════════════════════════════════════ -->
<div id="formulaModal" class="modal" style="display:none;">
    <div class="modal-content modal-lg">
        <div class="modal-header formula-header">
            <h2>📐 Edit MYTO Sharing Formula</h2>
            <span class="close" onclick="closeFormulaModal()">&times;</span>
        </div>
        <div style="padding:25px;">
            <p style="color:#6b7280;margin-bottom:20px;font-size:14px;line-height:1.6;">
                Set the percentage each Transmission Station receives from the bulk MYTO allocation.
                <strong>Percentages must sum to exactly 100%.</strong>
                Changes are versioned — previous hours already saved will not be affected.
            </p>

            <div id="formulaSumAlert" class="formula-sum-alert" style="display:none;"></div>

            <div class="formula-editor-grid">
                <div class="feg-header">
                    <span>Transmission Station</span>
                    <span>Percentage (%)</span>
                    <span>MW Preview <small>(for 1000 MW bulk)</small></span>
                </div>
                <?php foreach ($all_ts as $ts): ?>
                <div class="feg-row">
                    <label class="feg-label">
                        <i class="fas fa-broadcast-tower" style="color:#667eea;margin-right:6px;"></i>
                        <?= htmlspecialchars($ts['station_name']) ?>
                    </label>
                    <input type="number" step="0.0001" min="0" max="100"
                           class="form-control feg-input"
                           data-ts="<?= htmlspecialchars($ts['ts_code']) ?>"
                           value="<?= isset($formula_map[$ts['ts_code']]) ? number_format($formula_map[$ts['ts_code']], 4) : '0.0000' ?>"
                           oninput="updateFormulaSum()">
                    <span class="feg-preview" id="feg_prev_<?= htmlspecialchars($ts['ts_code']) ?>">
                        <?= isset($formula_map[$ts['ts_code']])
                            ? number_format(1000 * $formula_map[$ts['ts_code']] / 100, 2) . ' MW'
                            : '0.00 MW' ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="formula-sum-row">
                <span>Total:</span>
                <span id="formulaSumDisplay" class="formula-sum-val">
                    <?= number_format(array_sum(array_column($active_formula, 'percentage')), 4) ?>%
                </span>
                <span style="color:#6b7280;font-size:12px;">(must equal 100.0000%)</span>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeFormulaModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" class="btn-primary" onclick="saveFormula()">
                    <i class="fas fa-save"></i> Save Formula
                </button>
            </div>
        </div>
    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════════════════════
     MODAL 3 — Confirm Submission
     ═══════════════════════════════════════════════════════════════════════════ -->
<div id="confirmModal" class="modal" style="display:none;">
    <div class="modal-content modal-sm">
        <div class="modal-header confirm-header">
            <h2>🔒 Confirm Allocation</h2>
        </div>
        <div class="confirm-body">
            <p id="confirmText"></p>
        </div>
        <div class="form-actions confirm-actions">
            <button class="btn-secondary" onclick="closeConfirmModal()">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button class="btn-primary" onclick="doSave()">
                <i class="fas fa-check"></i> Confirm
            </button>
        </div>
    </div>
</div>

<!-- ── TOAST ─────────────────────────────────────────────────────────────── -->
<div id="toast" class="toast" style="display:none;"></div>


<!-- ═══════════════════════════════════════════════════════════════════════════
     STYLES
     ═══════════════════════════════════════════════════════════════════════════ -->
<style>
*{box-sizing:border-box;}
body{font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;background:#f0f2f5;color:#333;}

.main-content{margin-left:260px;padding:22px;padding-top:90px;min-height:calc(100vh - 64px);background:#f0f2f5;}
.myto-container{max-width:100%;}

/* ── Page Header ── */
.page-header{display:flex;justify-content:space-between;align-items:center;
    margin-bottom:28px;background:white;padding:22px 28px;
    border-radius:14px;box-shadow:0 2px 10px rgba(0,0,0,.08);}
.page-header h1{font-size:26px;color:#0f172a;margin-bottom:4px;}
.subtitle{color:#6b7280;font-size:13px;}

/* ── Buttons ── */
.btn-primary{background:linear-gradient(135deg,#0b3a82 0%,#1e40af 100%);color:white;
    border:none;padding:11px 22px;border-radius:8px;cursor:pointer;font-size:14px;
    font-weight:600;display:inline-flex;align-items:center;gap:8px;
    transition:all .2s ease;box-shadow:0 3px 10px rgba(11,58,130,.25);}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 5px 15px rgba(11,58,130,.35);}
.btn-primary:disabled{opacity:.6;cursor:not-allowed;transform:none;}

.btn-formula{background:linear-gradient(135deg,#6366f1 0%,#8b5cf6 100%);color:white;
    border:none;padding:11px 22px;border-radius:8px;cursor:pointer;font-size:14px;
    font-weight:600;display:inline-flex;align-items:center;gap:8px;transition:all .2s ease;}
.btn-formula:hover{transform:translateY(-1px);box-shadow:0 5px 15px rgba(99,102,241,.35);}

.btn-secondary{background:#e9ecef;color:#495057;border:none;padding:11px 22px;
    border-radius:8px;cursor:pointer;font-size:14px;font-weight:600;
    display:inline-flex;align-items:center;gap:8px;transition:all .2s ease;}
.btn-secondary:hover{background:#dee2e6;}

.btn-yes{background:linear-gradient(135deg,#059669,#10b981);color:white;border:none;
    padding:10px 20px;border-radius:7px;cursor:pointer;font-size:13px;font-weight:700;
    display:inline-flex;align-items:center;gap:7px;transition:all .2s;}
.btn-yes:hover{transform:translateY(-1px);}
.btn-yes.active{box-shadow:0 0 0 3px rgba(5,150,105,.3);}

.btn-no{background:linear-gradient(135deg,#dc2626,#ef4444);color:white;border:none;
    padding:10px 20px;border-radius:7px;cursor:pointer;font-size:13px;font-weight:700;
    display:inline-flex;align-items:center;gap:7px;transition:all .2s;}
.btn-no:hover{transform:translateY(-1px);}

/* ── Stats ── */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));
    gap:16px;margin-bottom:24px;}
.stat-card{background:white;padding:18px;border-radius:12px;
    display:flex;align-items:center;gap:14px;
    box-shadow:0 2px 8px rgba(0,0,0,.07);transition:all .25s ease;}
.stat-card:hover{transform:translateY(-3px);box-shadow:0 6px 16px rgba(0,0,0,.12);}
.stat-icon{width:54px;height:54px;border-radius:11px;display:flex;align-items:center;
    justify-content:center;font-size:22px;color:white;flex-shrink:0;}
.stat-card.blue   .stat-icon{background:linear-gradient(135deg,#0b3a82,#1e40af);}
.stat-card.orange .stat-icon{background:linear-gradient(135deg,#ea580c,#f97316);}
.stat-card.purple .stat-icon{background:linear-gradient(135deg,#7c3aed,#a855f7);}
.stat-card.green  .stat-icon{background:linear-gradient(135deg,#059669,#10b981);}
.stat-card.teal   .stat-icon{background:linear-gradient(135deg,#0891b2,#06b6d4);}
.stat-card.indigo .stat-icon{background:linear-gradient(135deg,#4f46e5,#6366f1);}
.stat-card.red    .stat-icon{background:linear-gradient(135deg,#dc2626,#ef4444);}
.stat-card.info   .stat-icon{background:linear-gradient(135deg,#0284c7,#38bdf8);}
.stat-details h3{font-size:24px;color:#0f172a;margin-bottom:3px;font-weight:700;}
.stat-details p{color:#6b7280;font-size:12px;font-weight:500;}

/* ── Formula banner ── */
.formula-banner{background:white;border-radius:12px;padding:14px 22px;
    margin-bottom:22px;box-shadow:0 2px 8px rgba(0,0,0,.06);
    display:flex;align-items:center;}
.formula-warning{color:#92400e;background:#fef3c7;border:1px solid #fcd34d;
    border-radius:8px;padding:12px 16px;font-size:13px;width:100%;}
.formula-info{display:flex;flex-wrap:wrap;align-items:center;gap:10px;font-size:13px;color:#374151;}
.formula-pill{background:#ede9fe;color:#5b21b6;padding:4px 10px;
    border-radius:12px;font-size:12px;}

/* ── Matrix card ── */
.matrix-card{background:white;border-radius:14px;
    box-shadow:0 2px 10px rgba(0,0,0,.08);overflow:hidden;margin-bottom:28px;}
.matrix-header{padding:18px 24px;
    background:linear-gradient(135deg,#0b3a82 0%,#1e40af 100%);
    color:white;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px;}
.matrix-header h3{font-size:18px;font-weight:700;}
.matrix-info{font-size:12px;opacity:.9;}
.legend-myto{background:rgba(255,255,255,.2);padding:2px 8px;border-radius:10px;
    color:#bfdbfe;font-size:11px;font-weight:700;}
.legend-actual{background:rgba(255,255,255,.2);padding:2px 8px;border-radius:10px;
    color:#bbf7d0;font-size:11px;font-weight:700;}
/* cell-actual is now on top — make it visually distinct */
.cell-actual{display:block;color:#065f46;font-weight:800;font-size:10px;line-height:1.4;}

.matrix-scroll{overflow-x:auto;max-height:640px;overflow-y:auto;}

.myto-matrix-table{width:100%;border-collapse:collapse;font-size:11px;}
.myto-matrix-table th{background:#1e3a8a;color:white;padding:8px 5px;text-align:center;
    font-weight:600;border:1px solid rgba(255,255,255,.1);position:sticky;top:0;z-index:10;}
.myto-matrix-table th.sticky-col{z-index:12;}
th.hour-col{min-width:58px;padding:5px 3px;}
th.hour-col .hour-top{display:block;font-size:10px;font-weight:700;line-height:1.2;}
th.hour-col .hour-bot{display:block;font-size:8px;color:#93c5fd;line-height:1.2;}

.sticky-col{position:sticky;left:0;background:white;z-index:11;font-weight:600;}
.myto-matrix-table th.sticky-col{background:#1e3a8a;}
.station-name{text-align:left!important;min-width:180px;max-width:180px;
    font-size:12px;padding:8px 10px;}

.matrix-cell{padding:7px 4px;text-align:center;border:1px solid #e5e7eb;
    cursor:pointer;transition:all .15s ease;min-width:64px;vertical-align:middle;}
.matrix-cell:not(.sticky-col):hover{transform:scale(1.06);
    box-shadow:0 2px 8px rgba(0,0,0,.18);z-index:5;position:relative;}

/* Cell states */
.cell-ok          {background:#dcfce7;}
.cell-over        {background:#fee2e2;}
.cell-under       {background:#fef3c7;}
.cell-myto-only   {background:#dbeafe;}
.cell-actual-only {background:#f3e8ff;}
.cell-empty       {background:#f9fafb;}
.cell-empty:hover {background:#f1f5f9;}
.has-faults       {outline:2px solid #f97316;}

.cell-myto   {display:block;color:#1d4ed8;font-weight:700;font-size:10px;line-height:1.3;}
.cell-sep    {color:#9ca3af;font-size:9px;}
.cell-actual {display:block;color:#065f46;font-weight:800;font-size:10px;line-height:1.4;}
.cell-dash   {color:#d1d5db;font-size:16px;}
.cell-fault-dot{color:#ea580c;font-size:9px;display:block;}

/* Grand total row */
.grand-total-row td{background:linear-gradient(135deg,#0f172a,#1e293b)!important;
    color:white!important;border-color:#334155!important;font-weight:700;}
.grand-total-label{font-size:11px;font-weight:800;letter-spacing:.3px;
    padding:8px 10px!important;text-align:left!important;}
.grand-total-cell .cell-myto{color:#93c5fd!important;}
.grand-total-cell .cell-actual{color:#86efac!important;}
.grand-total-cell .cell-sep{color:#64748b!important;}
.gt-variance{display:block;font-size:9px;font-weight:700;margin-top:2px;}
.var-over  {color:#fca5a5;}
.var-under {color:#fde68a;}
.var-ok    {color:#86efac;}

/* ── Metrics table ── */
.metrics-scroll{overflow-x:auto;max-height:500px;overflow-y:auto;}
.metrics-table{width:100%;border-collapse:collapse;font-size:13px;}
.metrics-table thead{position:sticky;top:0;z-index:10;}
.metrics-table th{background:linear-gradient(135deg,#0b3a82,#1e40af);color:white;
    padding:14px 12px;text-align:left;font-weight:600;}
.metrics-table td{padding:11px 12px;border:1px solid #e5e7eb;background:white;}
.metrics-table tbody tr:nth-child(even) td{background:#f8fafc;}
.metrics-table tbody tr:hover td{background:#eff6ff;}
.station-name-col{font-weight:600;color:#0f172a;min-width:200px;}
.metric-value{text-align:right;font-weight:700;color:#1d4ed8;font-family:'Courier New',monospace;}
.metric-center{text-align:center;font-weight:600;}
.metric-variance{text-align:right;font-weight:700;font-family:'Courier New',monospace;}
.metric-variance.var-over{color:#dc2626;background:#fee2e2!important;}
.metric-variance.var-under{color:#b45309;background:#fef3c7!important;}
.metric-variance.var-ok{color:#059669;}
.has-faults{color:#dc2626;background:#fee2e2!important;}
/* Variance colour: positive (actual > myto) = light green; negative = light red */
.var-pos{color:#065f46;background:#d1fae5!important;font-weight:700;}
.var-neg{color:#991b1b;background:#fee2e2!important;font-weight:700;}
.var-ok{color:#059669;font-weight:700;}
/* % columns */
.pct-over{color:#dc2626;font-weight:700;}
.pct-ok{color:#059669;font-weight:700;}
.pct-pos{color:#065f46;font-weight:700;}
.pct-neg{color:#991b1b;font-weight:700;}

/* ── Modals ── */
.modal{display:none;position:fixed;z-index:3000;left:0;top:0;width:100%;height:100%;
    background:rgba(0,0,0,.6);backdrop-filter:blur(4px);}
.modal-content{background:white;margin:5% auto;padding:0;border-radius:14px;
    width:90%;max-width:620px;max-height:88vh;overflow-y:auto;
    box-shadow:0 12px 40px rgba(0,0,0,.3);animation:slideIn .3s ease;}
.modal-sm{max-width:460px;margin:12% auto;}
.modal-lg{max-width:760px;max-height:92vh;overflow-y:auto;}
@keyframes slideIn{from{transform:translateY(-40px);opacity:0}to{transform:translateY(0);opacity:1}}

.modal-header{background:linear-gradient(135deg,#0b3a82,#1e40af);color:white;
    padding:18px 24px;border-radius:14px 14px 0 0;
    display:flex;justify-content:space-between;align-items:center;}
.modal-header h2{font-size:18px;font-weight:700;}
.formula-header{background:linear-gradient(135deg,#6366f1,#8b5cf6);}
.confirm-header{background:linear-gradient(135deg,#1e293b,#334155);}
.close{color:white;font-size:28px;cursor:pointer;transition:transform .2s;line-height:1;}
.close:hover{transform:rotate(90deg);}

form{padding:24px;}
.form-group{margin-bottom:18px;}
.form-group label{display:block;margin-bottom:7px;color:#0f172a;font-weight:600;font-size:14px;}
.req{color:#dc2626;}
.form-control{width:100%;padding:11px 14px;border:2px solid #e9ecef;border-radius:8px;
    font-size:14px;transition:border-color .2s;font-family:inherit;}
.form-control:focus{outline:none;border-color:#1e40af;box-shadow:0 0 0 3px rgba(30,64,175,.1);}
.form-group small{display:block;margin-top:5px;color:#6b7280;font-size:12px;}

/* Formula confirm box in entry modal */
.formula-confirm-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;
    padding:16px;margin-bottom:18px;}
.formula-confirm-header strong{font-size:14px;color:#0f172a;}
.formula-confirm-q{color:#6b7280;font-size:13px;margin:6px 0 12px;}
.formula-preview-grid{display:flex;flex-direction:column;gap:6px;margin-bottom:14px;}
.fp-row{display:grid;grid-template-columns:1fr 70px 90px;align-items:center;
    gap:10px;padding:6px 10px;background:white;border-radius:6px;
    border:1px solid #e5e7eb;font-size:13px;}
.fp-station{font-weight:600;color:#1e40af;}
.fp-pct{text-align:right;color:#6b7280;font-weight:600;}
.fp-value{text-align:right;font-weight:700;color:#059669;font-family:'Courier New',monospace;}
.formula-yn-btns{display:flex;gap:10px;flex-wrap:wrap;}
.formula-confirmed-msg{margin-top:10px;color:#059669;font-weight:700;font-size:13px;
    display:flex;align-items:center;gap:6px;}
.no-formula-warning{background:#fef3c7;border:1px solid #fcd34d;border-radius:8px;
    padding:12px 16px;color:#92400e;font-size:13px;margin-bottom:14px;}

/* Formula editor modal */
.formula-editor-grid{display:flex;flex-direction:column;gap:4px;margin-bottom:16px;}
.feg-header{display:grid;grid-template-columns:1fr 140px 160px;
    padding:8px 10px;background:#1e40af;color:white;border-radius:6px;
    font-size:12px;font-weight:700;}
.feg-row{display:grid;grid-template-columns:1fr 140px 160px;
    align-items:center;gap:10px;padding:7px 10px;
    background:#f8fafc;border-radius:6px;border:1px solid #e5e7eb;}
.feg-label{font-size:13px;font-weight:600;color:#1e40af;}
.feg-input{padding:8px 10px;font-size:13px;text-align:right;}
.feg-preview{text-align:right;font-size:12px;font-weight:700;color:#059669;
    font-family:'Courier New',monospace;}
.formula-sum-row{display:flex;align-items:center;gap:12px;padding:12px 16px;
    background:#f1f5f9;border-radius:8px;font-size:14px;font-weight:600;margin-bottom:16px;}
.formula-sum-val{font-size:18px;font-weight:800;color:#0f172a;}
.formula-sum-alert{padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:14px;}
.formula-sum-alert.error{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;}
.formula-sum-alert.ok{background:#dcfce7;color:#166534;border:1px solid #86efac;}

.confirm-body{padding:22px 24px 8px;}
.confirm-body p{font-size:14px;line-height:1.7;color:#1e293b;}
.form-actions{display:flex;gap:10px;justify-content:flex-end;
    margin-top:20px;padding-top:18px;border-top:1px solid #e9ecef;}
.confirm-actions{padding:16px 24px;border-top:1px solid #e9ecef;}

/* Toast */
.toast{position:fixed;bottom:28px;right:28px;z-index:9999;color:white;
    padding:13px 22px;border-radius:10px;font-size:14px;font-weight:600;
    box-shadow:0 4px 18px rgba(0,0,0,.3);animation:toastIn .3s ease;}
.toast.success{background:linear-gradient(135deg,#059669,#10b981);}
.toast.error  {background:linear-gradient(135deg,#dc2626,#ef4444);}
.toast.info   {background:linear-gradient(135deg,#1d4ed8,#3b82f6);}
@keyframes toastIn{from{transform:translateX(120%)}to{transform:translateX(0)}}

@media(max-width:768px){
    .main-content{margin-left:0;padding:12px;padding-top:80px;}
    .stats-grid{grid-template-columns:1fr;}
    .page-header{flex-direction:column;gap:14px;}
    .modal-content{width:96%;margin:8% auto;}
}

/* ── Companywide total row ── */
.cw-total-row td{font-size:15px;font-weight:700;
    background:linear-gradient(135deg,#0f172a 0%,#1e293b 100%)!important;
    color:white!important;border-color:#334155!important;padding:14px 12px!important;}
.cw-total-row .cw-actual{color:#86efac!important;}
.cw-total-row .cw-myto{color:#93c5fd!important;}
.cw-total-row .var-pos{color:#86efac!important;background:transparent!important;}
.cw-total-row .var-neg{color:#fca5a5!important;background:transparent!important;}
.cw-total-row .var-ok{color:#86efac!important;}
.cw-total-row .pct-pos{color:#86efac!important;}
.cw-total-row .pct-neg{color:#fca5a5!important;}
.cw-total-row .pct-ok{color:#86efac!important;}
.cw-total-row .pct-over{color:#fca5a5!important;}
.cw-total-row .has-faults{color:#fcd34d!important;background:transparent!important;}

/* ── Trend chart controls ── */
.period-chip{padding:7px 14px;border:2px solid #e2e8f0;border-radius:20px;
    background:white;color:#475569;font-size:13px;font-weight:600;
    cursor:pointer;transition:all .18s;}
.period-chip:hover{border-color:#3b82f6;color:#1e40af;background:#eff6ff;}
.period-chip.active{background:linear-gradient(135deg,#1e40af,#3b82f6);
    color:white;border-color:#1e40af;box-shadow:0 3px 10px rgba(30,64,175,.3);}

.btn-trend{background:linear-gradient(135deg,#0f172a,#1e3a8a);color:white;
    border:none;padding:10px 20px;border-radius:8px;cursor:pointer;
    font-size:13px;font-weight:700;display:inline-flex;align-items:center;
    gap:8px;transition:all .2s;white-space:nowrap;align-self:flex-end;}
.btn-trend:hover{transform:translateY(-1px);box-shadow:0 5px 14px rgba(15,23,42,.35);}

.metric-toggle{display:flex;align-items:center;gap:8px;cursor:pointer;
    font-size:13px;font-weight:600;color:#374151;}
.metric-toggle input{width:15px;height:15px;cursor:pointer;accent-color:#1e40af;}

.trend-stat-box{background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;
    padding:12px 14px;text-align:center;}
.trend-stat-box .ts-label{font-size:11px;color:#6b7280;font-weight:600;
    text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px;}
.trend-stat-box .ts-val{font-size:18px;font-weight:800;color:#0f172a;}
.trend-stat-box .ts-sub{font-size:11px;color:#94a3b8;margin-top:2px;}

/* Spinner */
.spinner{width:36px;height:36px;border:4px solid #e5e7eb;
    border-top-color:#3b82f6;border-radius:50%;animation:spin .8s linear infinite;}
@keyframes spin{to{transform:rotate(360deg)}}
</style>


<!-- ═══════════════════════════════════════════════════════════════════════════
     SCRIPTS
     ═══════════════════════════════════════════════════════════════════════════ -->
<script>
// ── Config ────────────────────────────────────────────────────────────────────
const SAVE_URL = <?= json_encode($mytoSaveUrl) ?>;

// Existing allocations (for pre-fill when clicking a filled cell)
const dailyAllocations = <?= json_encode(array_filter($daily_allocations, fn($v) => $v !== null)) ?>;

// Formula data from PHP
const activeFormula = <?= json_encode($formula_map) ?>;    // {ts_code: pct, ...}
const formulaNames  = <?= json_encode(array_column($active_formula, 'station_name', 'ts_code')) ?>;

// State
let formulaConfirmed = false;
let pendingData      = null;

// ── Toast ─────────────────────────────────────────────────────────────────────
function showToast(msg, type='') {
    const t = document.getElementById('toast');
    t.textContent   = msg;
    t.className     = 'toast ' + type;
    t.style.display = 'block';
    clearTimeout(t._t);
    t._t = setTimeout(() => { t.style.display='none'; }, 5000);
}

// ── Entry Modal ───────────────────────────────────────────────────────────────
function openEntryModal(hour) {
    formulaConfirmed = false;
    document.getElementById('formulaConfirmed').style.display = 'none';
    document.getElementById('btnYes').classList.remove('active');
    document.getElementById('use_custom_formula').value = '0';
    document.getElementById('custom_rows').value = '';
    document.getElementById('is_edit').value = '0';
    document.getElementById('entryForm').reset();

    const hourSel = document.getElementById('entry_hour');
    if (hour !== null && hour !== undefined) {
        hourSel.value    = hour;
        hourSel.disabled = true;
        // Pre-fill if an entry already exists for this hour
        if (dailyAllocations[hour]) {
            document.getElementById('myto_allocation').value =
                parseFloat(dailyAllocations[hour].myto_allocation).toFixed(2);
            document.getElementById('is_edit').value = '1';
            document.getElementById('entryModalTitle').textContent =
                '⚡ Edit MYTO Allocation — Hour ' + String(hour).padStart(2,'0') + ':00';
        } else {
            document.getElementById('entryModalTitle').textContent =
                '⚡ MYTO Allocation — Hour ' + String(hour).padStart(2,'0') + ':00';
        }
    } else {
        hourSel.disabled = false;
        document.getElementById('entryModalTitle').textContent =
            '⚡ MYTO Allocation Entry — <?= date('F j, Y', strtotime($today)) ?>';
    }

    updateFormulaPreview();
    document.getElementById('entryModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeEntryModal() {
    document.getElementById('entryModal').style.display = 'none';
    document.getElementById('entry_hour').disabled = false;
    document.body.style.overflow = 'auto';
}

function loadExistingAllocation() {
    const h = parseInt(document.getElementById('entry_hour').value);
    if (!isNaN(h) && dailyAllocations[h]) {
        document.getElementById('myto_allocation').value =
            parseFloat(dailyAllocations[h].myto_allocation).toFixed(2);
        document.getElementById('is_edit').value = '1';
    } else {
        document.getElementById('is_edit').value = '0';
    }
    updateFormulaPreview();
}

// Live formula distribution preview inside entry modal
function updateFormulaPreview() {
    const bulk = parseFloat(document.getElementById('myto_allocation').value) || 0;
    for (const [ts, pct] of Object.entries(activeFormula)) {
        const el = document.getElementById('fpv_' + ts);
        if (el) {
            const share = (bulk * pct / 100).toFixed(2);
            el.textContent = share > 0 ? share + ' MW' : '— MW';
        }
    }
}

// Yes/No formula confirmation
function confirmFormula(yes) {
    if (yes) {
        formulaConfirmed = true;
        document.getElementById('use_custom_formula').value = '0';
        document.getElementById('formulaConfirmed').style.display = 'flex';
        document.getElementById('btnYes').classList.add('active');
    } else {
        // No — close entry modal and open formula editor
        closeEntryModal();
        openFormulaModal(true); // true = re-open entry modal after save
    }
}

// ── Form submission ────────────────────────────────────────────────────────────
function handleEntrySubmit(e) {
    e.preventDefault();

    const hour       = parseInt(document.getElementById('entry_hour').value);
    const allocation = parseFloat(document.getElementById('myto_allocation').value);
    const isEdit     = document.getElementById('is_edit').value === '1';

    if (isNaN(hour) || document.getElementById('entry_hour').value === '') {
        showToast('⚠️ Please select an hour.', 'error'); return false;
    }
    if (isNaN(allocation) || allocation < 0) {
        showToast('⚠️ Enter a valid allocation (≥ 0 MW).', 'error'); return false;
    }
    if (!formulaConfirmed) {
        showToast('⚠️ Please confirm the sharing formula (Yes/No) before saving.', 'error');
        return false;
    }

    const hourLabel = String(hour).padStart(2,'0') + ':00';
    const action    = isEdit ? 'update' : 'save';

    document.getElementById('confirmText').innerHTML =
        `Confirm you want to <strong>${action}</strong> MYTO allocation of `
        + `<strong>${allocation.toFixed(2)} MW</strong> for hour <strong>${hourLabel}</strong>.<br>`
        + `This will be distributed across all transmission stations using the active formula.`;

    pendingData = new FormData(document.getElementById('entryForm'));
    pendingData.set('entry_hour', hour);
    pendingData.set('myto_allocation', allocation);

    document.getElementById('confirmModal').style.display = 'block';
    return false;
}

function closeConfirmModal() {
    document.getElementById('confirmModal').style.display = 'none';
}

function doSave() {
    closeConfirmModal();
    const btn = document.getElementById('submitEntryBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

    fetch(SAVE_URL, { method: 'POST', body: pendingData })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Save Allocation';
            if (data.success) {
                showToast('✅ ' + data.message, 'success');
                closeEntryModal();
                pendingData = null;
                // Refresh page to update matrix
                setTimeout(() => window.location.reload(), 1200);
            } else {
                showToast('❌ ' + data.message, 'error');
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Save Allocation';
            showToast('❌ Network error. Please try again.', 'error');
        });
}

// ── Formula Modal ─────────────────────────────────────────────────────────────
let returnToEntry = false;

function openFormulaModal(reopen) {
    returnToEntry = !!reopen;
    updateFormulaSum();
    document.getElementById('formulaModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeFormulaModal() {
    document.getElementById('formulaModal').style.display = 'none';
    document.body.style.overflow = 'auto';
    if (returnToEntry) {
        // Re-open entry modal; mark custom formula is now being used
        returnToEntry = false;
        openEntryModal(null);
        document.getElementById('use_custom_formula').value = '1';
    }
}

function updateFormulaSum() {
    const inputs = document.querySelectorAll('.feg-input');
    let sum = 0;
    inputs.forEach(inp => {
        const pct = parseFloat(inp.value) || 0;
        sum += pct;
        const ts   = inp.dataset.ts;
        const prev = document.getElementById('feg_prev_' + ts);
        if (prev) prev.textContent = (1000 * pct / 100).toFixed(2) + ' MW';
    });
    const disp  = document.getElementById('formulaSumDisplay');
    const alert = document.getElementById('formulaSumAlert');
    disp.textContent = sum.toFixed(4) + '%';
    const diff = Math.abs(sum - 100);
    if (diff <= 0.01) {
        disp.style.color = '#059669';
        alert.style.display = 'none';
    } else {
        disp.style.color = '#dc2626';
        alert.className  = 'formula-sum-alert error';
        alert.textContent = `⚠️ Sum is ${sum.toFixed(4)}% — must equal exactly 100.0000%.`;
        alert.style.display = 'block';
    }
}

function saveFormula() {
    const inputs = document.querySelectorAll('.feg-input');
    let sum = 0;
    const rows = {};
    inputs.forEach(inp => {
        const pct = parseFloat(inp.value) || 0;
        sum += pct;
        rows[inp.dataset.ts] = pct;
    });
    if (Math.abs(sum - 100) > 0.01) {
        showToast('⚠️ Percentages must sum to 100%. Current: ' + sum.toFixed(4) + '%', 'error');
        return;
    }

    const fd = new FormData();
    fd.set('action', 'save_formula');
    fd.set('rows', JSON.stringify(rows));

    fetch(SAVE_URL, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showToast('✅ Formula saved — v' + data.version, 'success');
                // Store custom rows for this session
                document.getElementById('custom_rows').value = JSON.stringify(rows);
                document.getElementById('use_custom_formula').value = '0'; // server already saved
                formulaConfirmed = false; // reset confirmation
                closeFormulaModal();
                // Reload to refresh formula banner
                setTimeout(() => window.location.reload(), 1400);
            } else {
                showToast('❌ ' + data.message, 'error');
            }
        })
        .catch(() => showToast('❌ Network error.', 'error'));
}

// ── Backdrop / Escape close ───────────────────────────────────────────────────
window.onclick = function(e) {
    ['entryModal','formulaModal','confirmModal'].forEach(id => {
        if (e.target === document.getElementById(id)) {
            document.getElementById(id).style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });
};
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeEntryModal();
        closeFormulaModal();
        closeConfirmModal();
    }
});
</script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>

<script>
// ══════════════════════════════════════════════════════════════════════════════
//  TREND CHART — Load vs MYTO over date ranges with multi-TS selection
// ══════════════════════════════════════════════════════════════════════════════

// ── State ─────────────────────────────────────────────────────────────────────
let trendChartInst  = null;
let activePeriod    = '7d';

// All TS from PHP (for palette assignment)
const ALL_TS_CODES = <?= json_encode(array_column($all_ts, 'ts_code')) ?>;
const ALL_TS_NAMES = <?= json_encode(array_column($all_ts, 'station_name', 'ts_code')) ?>;

// Colour palette for per-TS lines
const TS_PALETTE = [
    '#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6',
    '#06b6d4','#f97316','#84cc16','#ec4899','#6366f1',
];
function tsColor(idx) { return TS_PALETTE[idx % TS_PALETTE.length]; }

// ── Period chip logic ─────────────────────────────────────────────────────────
function selectPeriod(btn) {
    document.querySelectorAll('.period-chip').forEach(c => c.classList.remove('active'));
    btn.classList.add('active');
    activePeriod = btn.dataset.period;
    const custom = document.getElementById('customRangeWrap');
    custom.style.display = activePeriod === 'custom' ? 'flex' : 'none';
}

// ── Date range from period ─────────────────────────────────────────────────────
function getDateRange() {
    const today = new Date();
    const fmt   = d => d.toISOString().split('T')[0];
    const sub   = (d, n) => { const r = new Date(d); r.setDate(r.getDate() - n); return r; };

    if (activePeriod === '7d')  return { from: fmt(sub(today, 6)),   to: fmt(today) };
    if (activePeriod === '1m')  return { from: fmt(sub(today, 29)),  to: fmt(today) };
    if (activePeriod === '3m')  return { from: fmt(sub(today, 89)),  to: fmt(today) };
    if (activePeriod === '1y')  return { from: fmt(sub(today, 364)), to: fmt(today) };
    if (activePeriod === 'custom') {
        return {
            from: document.getElementById('trendFrom').value,
            to:   document.getElementById('trendTo').value,
        };
    }
    return { from: fmt(sub(today, 6)), to: fmt(today) };
}

// ── Get selected TS codes ──────────────────────────────────────────────────────
function getSelectedTs() {
    const sel  = document.getElementById('trendTsSelect');
    const vals = Array.from(sel.selectedOptions).map(o => o.value);
    if (vals.includes('__ALL__') || vals.length === 0) return ['__ALL__'];
    return vals;
}

// ── Build AJAX URL ─────────────────────────────────────────────────────────────
// We reuse the same page URL with ajax=trend params
const PAGE_URL = window.location.pathname + window.location.search.split('&ajax')[0];
function buildTrendUrl(from, to, tsCodes) {
    let url = PAGE_URL.split('?')[0] + '?page=myto_dashboard&ajax=trend';
    url += '&from=' + encodeURIComponent(from);
    url += '&to='   + encodeURIComponent(to);
    tsCodes.forEach(c => { url += '&ts[]=' + encodeURIComponent(c); });
    return url;
}

// ── Load and render chart ──────────────────────────────────────────────────────
async function loadTrend() {
    const range  = getDateRange();
    const tsCodes = getSelectedTs();

    if (!range.from || !range.to) {
        alert('Please select a valid date range.');
        return;
    }

    // Show loading spinner
    const loading = document.getElementById('trendLoading');
    const empty   = document.getElementById('trendEmpty');
    const stats   = document.getElementById('trendStats');
    loading.style.display = 'flex';
    empty.style.display   = 'none';
    stats.style.display   = 'none';

    try {
        const res  = await fetch(buildTrendUrl(range.from, range.to, tsCodes));
        const data = await res.json();

        if (!data.success || !data.days || data.days.length === 0) {
            loading.style.display = 'none';
            empty.style.display   = 'block';
            return;
        }

        renderTrendChart(data, tsCodes);
        renderTrendStats(data);
        loading.style.display = 'none';
        stats.style.display   = 'block';

    } catch (e) {
        loading.style.display = 'none';
        empty.style.display   = 'block';
        console.error('Trend fetch error:', e);
    }
}

// ── Render Chart.js chart ──────────────────────────────────────────────────────
function renderTrendChart(data, tsCodes) {
    const showActual = document.getElementById('showActual').checked;
    const showMyto   = document.getElementById('showMyto').checked;
    const showVar    = document.getElementById('showVar').checked;

    const labels   = data.days.map(d => d.date);
    const datasets = [];

    const isAll = tsCodes.includes('__ALL__') || tsCodes.length === 0;

    if (isAll) {
        // Companywide — single set of lines
        if (showActual) {
            datasets.push({
                label: 'Actual Load (MW)',
                data: data.days.map(d => d.actual),
                borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.12)',
                borderWidth: 2.5, pointRadius: 3, fill: true, tension: .35,
            });
        }
        if (showMyto) {
            datasets.push({
                label: 'MYTO Allocated (MW)',
                data: data.days.map(d => d.myto),
                borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,.08)',
                borderWidth: 2.5, pointRadius: 3, fill: true, tension: .35, borderDash: [6,3],
            });
        }
        if (showVar) {
            datasets.push({
                label: 'Variance MW',
                data: data.days.map(d => d.variance),
                borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,.08)',
                borderWidth: 2, pointRadius: 2, fill: false, tension: .35, borderDash: [4,4],
                yAxisID: 'yVar',
            });
        }
    } else {
        // Per-station lines
        tsCodes.forEach((tc, idx) => {
            const color   = tsColor(idx);
            const tsName  = ALL_TS_NAMES[tc] || tc;
            const tsData  = data.ts_data && data.ts_data[tc];
            if (!tsData) return;

            if (showActual) {
                datasets.push({
                    label: tsName + ' — Actual',
                    data: data.days.map(d => tsData[d.date]?.actual ?? null),
                    borderColor: color, backgroundColor: color + '18',
                    borderWidth: 2, pointRadius: 2.5, fill: false, tension: .35,
                });
            }
            if (showMyto) {
                datasets.push({
                    label: tsName + ' — MYTO',
                    data: data.days.map(d => tsData[d.date]?.myto ?? null),
                    borderColor: color, backgroundColor: color + '10',
                    borderWidth: 1.5, pointRadius: 2, fill: false, tension: .35,
                    borderDash: [5,3],
                });
            }
            if (showVar) {
                datasets.push({
                    label: tsName + ' — Variance',
                    data: data.days.map(d => tsData[d.date]?.variance ?? null),
                    borderColor: color, borderWidth: 1.5, pointRadius: 2,
                    fill: false, tension: .35, borderDash: [2,4], yAxisID: 'yVar',
                });
            }
        });
    }

    // Destroy old chart
    if (trendChartInst) { trendChartInst.destroy(); trendChartInst = null; }

    const ctx = document.getElementById('trendChart').getContext('2d');
    const scales = {
        x: {
            type: 'time',
            time: { unit: labels.length > 60 ? 'month' : (labels.length > 14 ? 'week' : 'day'),
                    tooltipFormat: 'dd MMM yyyy', displayFormats: { day:'dd MMM', week:'dd MMM', month:'MMM yyyy' } },
            grid: { color: '#f1f5f9' },
            ticks: { color: '#64748b', font: { size: 11 } },
        },
        y: {
            position: 'left',
            title: { display: true, text: 'Load (MW)', color: '#475569', font: { size: 12 } },
            grid: { color: '#f1f5f9' },
            ticks: { color: '#64748b', font: { size: 11 } },
        },
    };
    if (showVar) {
        scales.yVar = {
            position: 'right',
            title: { display: true, text: 'Variance (MW)', color: '#f59e0b', font: { size: 12 } },
            grid: { drawOnChartArea: false },
            ticks: { color: '#f59e0b', font: { size: 11 } },
        };
    }

    trendChartInst = new Chart(ctx, {
        type: 'line',
        data: { labels, datasets },
        options: {
            responsive: true,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { position: 'top', labels: { usePointStyle: true, pointStyleWidth: 10, font: { size: 12 } } },
                tooltip: {
                    callbacks: {
                        label: ctx => ctx.dataset.label + ': ' + (ctx.parsed.y !== null ? ctx.parsed.y.toFixed(2) + ' MW' : '—'),
                    },
                },
            },
            scales,
        },
    });
}

// ── Render summary stats bar ───────────────────────────────────────────────────
function renderTrendStats(data) {
    const fmt = n => n !== null ? parseFloat(n).toFixed(2) : '—';
    const pct = n => n !== null ? parseFloat(n).toFixed(1) + '%' : '—';
    const varSign = n => n > 0 ? '+' : '';

    const setBox = (id, label, val, sub, color) => {
        document.getElementById(id).innerHTML =
            `<div class="ts-label">${label}</div>`+
            `<div class="ts-val" style="color:${color}">${val}</div>`+
            `<div class="ts-sub">${sub}</div>`;
    };

    setBox('tsSumActual', 'Total Actual',   fmt(data.summary.total_actual) + ' MW',  data.summary.days + ' days', '#059669');
    setBox('tsSumMyto',   'Total MYTO',     fmt(data.summary.total_myto)   + ' MW',  'allocated',                 '#1e40af');
    setBox('tsSumVar',    'Total Variance',
           varSign(data.summary.total_variance) + fmt(data.summary.total_variance) + ' MW',
           'Actual − MYTO',
           data.summary.total_variance >= 0 ? '#059669' : '#dc2626');
    setBox('tsSumUtil',   'Avg Utilisation', pct(data.summary.avg_util), '(Actual ÷ MYTO)', '#1e40af');
    setBox('tsSumDays',   'Days w/ Data',   data.summary.days_with_data + ' / ' + data.summary.days, 'periods with entries', '#475569');
}

// ── Auto-load on page ready ────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    // Default custom range to last 7 days
    const today = new Date();
    const sub7  = new Date(today); sub7.setDate(sub7.getDate() - 6);
    const fmt   = d => d.toISOString().split('T')[0];
    document.getElementById('trendFrom').value = fmt(sub7);
    document.getElementById('trendTo').value   = fmt(today);

    // Load chart automatically on page load
    loadTrend();
});

// Listen to metric checkboxes for instant re-render without re-fetching
['showActual','showMyto','showVar'].forEach(id => {
    document.getElementById(id).addEventListener('change', function() {
        if (trendChartInst) {
            // Get last fetched data from chart datasets — simplest is re-fetch
            loadTrend();
        }
    });
});
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
