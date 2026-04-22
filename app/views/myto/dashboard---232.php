<?php
/**
 * MYTO Dashboard View
 * Path: app/views/myto/dashboard.php
 * Loaded by: MytoDashboardController.php
 */
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

<?php require __DIR__ . '/../layout/footer.php'; ?>
