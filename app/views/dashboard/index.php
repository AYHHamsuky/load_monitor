<?php
/**
 * Dashboard View — ENHANCED VERSION
 * Modifications applied:
 *  1. Click any matrix cell → modal pre-filled with feeder + hour (grayed out)
 *  2. Confirmation dialog before insert, reflecting exact values
 *  3. Fault hours shown with light-red background + fault code
 *  4. max_load validation (client + server)
 *  5. Batch time-window enforcement (01-07→08:00, 08-14→15:00, 15-19→20:00, 20-24→01:00 next day)
 *  6. Late-entry explanation gate when window is closed
 */
?>
<?php require __DIR__ . '/../layout/header.php'; ?>
<?php require __DIR__ . '/../layout/sidebar.php'; ?>

<div class="main-content">
    <div class="dashboard-container-11kv">

        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>⚡ 11kV Load Monitoring Dashboard</h1>
                <p class="subtitle">ISS: <?= htmlspecialchars($iss['iss_name']) ?> | <span id="opDayLabel"><?= date('l, F j, Y', strtotime($today)) ?></span> <small style="color:#f39c12;font-size:11px;">(operational day — closes 01:00)</small></p>
            </div>
            <button class="btn-primary" onclick="openLoadEntryModal(null, null)">
                <i class="fas fa-plus"></i> Add Load Entry
            </button>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-icon"><i class="fas fa-bolt"></i></div>
                <div class="stat-details">
                    <h3><?= $total_feeders ?></h3>
                    <p>Total Feeders</p>
                </div>
            </div>
            <div class="stat-card orange">
                <div class="stat-icon"><i class="fas fa-charging-station"></i></div>
                <div class="stat-details">
                    <h3><?= number_format($total_load, 2) ?> MW</h3>
                    <p>Total Load</p>
                </div>
            </div>
            <div class="stat-card purple">
                <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
                <div class="stat-details">
                    <h3><?= number_format($avg_load, 2) ?> MW</h3>
                    <p>Average Load</p>
                </div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon"><i class="fas fa-arrow-up"></i></div>
                <div class="stat-details">
                    <h3><?= number_format($peak_load, 2) ?> MW</h3>
                    <p>Peak Load</p>
                </div>
            </div>
            <div class="stat-card danger">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-details">
                    <h3><?= $supply_hours ?></h3>
                    <p>Supply Hours</p>
                </div>
            </div>
            <div class="stat-card warning">
                <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-details">
                    <h3><?= $total_faults ?? 0 ?></h3>
                    <p>Total Faults Today</p>
                </div>
            </div>
            <div class="stat-card info">
                <div class="stat-icon"><i class="fas fa-tasks"></i></div>
                <div class="stat-details">
                    <h3><?= number_format($completion_percentage, 1) ?>%</h3>
                    <p>Data Completion</p>
                </div>
            </div>
            <div class="stat-card red">
                <div class="stat-icon"><i class="fas fa-plug"></i></div>
                <div class="stat-details">
                    <h3><?= $feeders_with_faults ?? 0 ?></h3>
                    <p>Feeders with Faults</p>
                </div>
            </div>
        </div>

        <!-- Hourly Load Matrix -->
        <div class="matrix-card">
            <div class="matrix-header">
                <h3>📊 24-Hour Load Matrix</h3>
                <span class="matrix-info"><?= count($feeders) ?> feeders × 24 hours &nbsp;|&nbsp; Click any cell to enter / edit</span>
            </div>
            <div class="matrix-scroll">
                <table class="load-matrix-table">
                    <thead>
                        <tr>
                            <th class="sticky-col">Feeder Name</th>
                            <?php for ($h = 0; $h <= 23; $h++): ?>
                                <th class="hour-col" title="<?= str_pad($h,2,'0',STR_PAD_LEFT) ?>:00 – <?= str_pad($h,2,'0',STR_PAD_LEFT) ?>:59">
                                    <span class="hour-top"><?= str_pad($h,2,'0',STR_PAD_LEFT) ?>:00</span>
                                    <span class="hour-bot"><?= str_pad($h,2,'0',STR_PAD_LEFT) ?>:59</span>
                                </th>
                            <?php endfor; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matrix as $code => $feeder): ?>
                        <tr>
                            <td class="sticky-col feeder-name">
                                <?= htmlspecialchars($feeder['fdr11kv_name']) ?>
                            </td>
                            <?php for ($h = 0; $h <= 23; $h++): ?>
                                <?php
                                $hour_data = $feeder['hours'][$h];
                                $has_data  = !is_null($hour_data);
                                $load      = $has_data ? floatval($hour_data['load']) : null;
                                $fault     = $has_data ? trim($hour_data['fault'])    : '';

                                if ($has_data && $load > 0) {
                                    $class   = 'has-load';
                                    $display = number_format($load, 2);
                                } elseif ($has_data && $load === 0.0 && !empty($fault)) {
                                    $class   = 'has-fault';
                                    $display = strtoupper(substr($fault, 0, 8));
                                } else {
                                    $class   = 'no-data';
                                    $display = '–';
                                }

                                // data attributes passed to JS
                                $data_load  = $has_data ? $load  : '';
                                $data_fault = $has_data ? $fault : '';
                                ?>
                                <td class="matrix-cell <?= $class ?>"
                                    data-feeder-code="<?= htmlspecialchars($code) ?>"
                                    data-feeder-name="<?= htmlspecialchars($feeder['fdr11kv_name']) ?>"
                                    data-hour="<?= $h ?>"
                                    data-load="<?= htmlspecialchars($data_load) ?>"
                                    data-fault="<?= htmlspecialchars($data_fault) ?>"
                                    onclick="openLoadEntryModal('<?= htmlspecialchars($code, ENT_QUOTES) ?>', <?= $h ?>, this)"
                                    title="<?= $has_data && !empty($fault) ? 'Fault: ' . htmlspecialchars($fault) : 'Click to enter / edit' ?>">
                                    <?= $display ?>
                                </td>
                            <?php endfor; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Feeder Performance Metrics -->
        <div class="matrix-card feeder-metrics-card">
            <div class="matrix-header">
                <h3>📋 Feeder Performance Metrics</h3>
                <span class="matrix-info">Summary by Feeder</span>
            </div>
            <div class="metrics-scroll">
                <table class="metrics-table">
                    <thead>
                        <tr>
                            <th>Feeder Name</th>
                            <th>Total Load (MW)</th>
                            <th>Average Load (MW)</th>
                            <th>Peak Load (MW)</th>
                            <th>Supply Hours</th>
                            <th>Fault Count</th>
                            <th>Fault Hours</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($matrix as $code => $feeder):
                            $feeder_total_load   = 0;
                            $feeder_peak_load    = 0;
                            $feeder_supply_hours = 0;
                            $feeder_fault_count  = 0;
                            $feeder_fault_hours  = 0;
                            $load_count          = 0;

                            foreach ($feeder['hours'] as $h_data) {
                                if ($h_data) {
                                    $ld = floatval($h_data['load']);
                                    $ft = trim($h_data['fault']);
                                    if ($ld > 0) {
                                        $feeder_total_load += $ld;
                                        $feeder_supply_hours++;
                                        $load_count++;
                                        if ($ld > $feeder_peak_load) $feeder_peak_load = $ld;
                                    }
                                    if (!empty($ft)) {
                                        $feeder_fault_count++;
                                        if ($ld === 0.0) $feeder_fault_hours++;
                                    }
                                }
                            }
                            $feeder_avg_load = $load_count > 0 ? $feeder_total_load / $load_count : 0;
                        ?>
                        <tr>
                            <td class="feeder-name-col">
                                <i class="fas fa-bolt"></i>
                                <?= htmlspecialchars($feeder['fdr11kv_name']) ?>
                            </td>
                            <td class="metric-value"><?= number_format($feeder_total_load, 2) ?></td>
                            <td class="metric-value"><?= number_format($feeder_avg_load, 2) ?></td>
                            <td class="metric-value"><?= number_format($feeder_peak_load, 2) ?></td>
                            <td class="metric-hours"><?= $feeder_supply_hours ?></td>
                            <td class="metric-faults <?= $feeder_fault_count > 0 ? 'has-faults' : '' ?>">
                                <?= $feeder_fault_count ?>
                            </td>
                            <td class="metric-faults <?= $feeder_fault_hours > 0 ? 'has-faults' : '' ?>">
                                <?= $feeder_fault_hours ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts-row">
            <div class="chart-card modern-chart">
                <h3>📈 Load Trend (Today)</h3>
                <canvas id="loadTrendChart"></canvas>
            </div>
            <div class="chart-card modern-chart">
                <h3>📊 Load Distribution by Feeder</h3>
                <canvas id="loadPieChart"></canvas>
            </div>
        </div>

    </div><!-- /.dashboard-container-11kv -->
</div><!-- /.main-content -->


<!-- ═══════════════════════════════════════════════════════════════════════════
     MODAL 1 — Load Entry / Edit
     ═══════════════════════════════════════════════════════════════════════════ -->
<div id="loadEntryModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">⚡ 11kV Load Entry — <?= date('F j, Y', strtotime($today)) ?></h2>
            <span class="close" onclick="closeLoadEntryModal()">&times;</span>
        </div>

        <form id="loadEntryForm" onsubmit="return handleFormSubmit(event)">
            <input type="hidden" name="action"      value="save_load">
            <input type="hidden" name="entry_date"  value="<?= $today /* operational date, set by DashboardController */ ?>">
            <input type="hidden" name="is_edit"     id="is_edit" value="0">

            <!-- Feeder (grayed out when pre-filled from matrix click) -->
            <div class="form-group">
                <label for="fdr11kv_code">11kV Feeder *</label>
                <select name="Fdr11kv_code" id="fdr11kv_code" required class="form-control">
                    <option value="">-- Select Feeder --</option>
                    <?php foreach ($feeders as $f): ?>
                        <option value="<?= htmlspecialchars($f['fdr11kv_code']) ?>"
                                data-max-load="<?= htmlspecialchars($f['max_load'] ?? '') ?>">
                            <?= htmlspecialchars($f['fdr11kv_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small id="maxLoadInfo" class="max-load-hint" style="display:none;"></small>
            </div>

            <!-- Hour (grayed out when pre-filled) -->
            <div class="form-group">
                <label for="entry_hour">Hour *</label>
                <select name="entry_hour" id="entry_hour" required class="form-control">
                    <option value="">-- Select Hour --</option>
                    <?php for ($h = 0; $h <= 23; $h++): ?>
                        <option value="<?= $h ?>"><?= str_pad($h, 2, '0', STR_PAD_LEFT) ?>:00</option>
                    <?php endfor; ?>
                </select>
            </div>

            <!-- Load Reading -->
            <div class="form-group">
                <label for="load_read">Load Reading (MW) *</label>
                <input type="number"
                       step="0.01"
                       name="load_read"
                       id="load_read"
                       class="form-control"
                       min="0"
                       value="0"
                       required
                       oninput="handleLoadChange()">
                <small id="loadHint">Enter 0 if there is a fault, or the actual load if supply is available</small>
                <div id="maxLoadError" class="error-msg" style="display:none;">
                    ⚠️ Value entered exceeded the maximum allowed for the feeder
                </div>
            </div>

            <!-- Fault section (shown only when load = 0) -->
            <div id="faultSection">
                <div class="form-group">
                    <label for="fault_code">Fault Code *</label>
                    <select name="fault_code" id="fault_code" class="form-control" required>
                        <option value="">-- Select Fault Code --</option>
                        <option value="FO">FO – Feeder Off</option>
                        <option value="BF">BF – Breaker Fault</option>
                        <option value="LF">LF – Line Fault</option>
                        <option value="TF">TF – Transformer Fault</option>
                        <option value="LS">LS – Load Shedding</option>
                        <option value="MS">MS – Maintenance / Scheduled</option>
                        <option value="OT">OT – Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="fault_remark">Fault Remark *</label>
                    <textarea name="fault_remark"
                              id="fault_remark"
                              class="form-control"
                              rows="3"
                              placeholder="Describe the fault reason…"
                              required></textarea>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn-secondary" onclick="closeLoadEntryModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" id="submitBtn" class="btn-primary">
                    <i class="fas fa-save"></i> Save Entry
                </button>
            </div>
        </form>
    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════════════════════
     MODAL 2 — Confirmation Dialog
     ═══════════════════════════════════════════════════════════════════════════ -->
<div id="confirmModal" class="modal" style="display:none;">
    <div class="modal-content modal-sm">
        <div class="modal-header confirm-header">
            <h2>🔒 Confirm Entry</h2>
        </div>
        <div class="confirm-body">
            <p id="confirmText"></p>
        </div>
        <div class="form-actions confirm-actions">
            <button class="btn-secondary" onclick="closeConfirmModal()">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button class="btn-primary" id="confirmOkBtn" onclick="doSave()">
                <i class="fas fa-check"></i> Confirm
            </button>
        </div>
    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════════════════════
     MODAL 3 — Late-Entry Explanation
     ═══════════════════════════════════════════════════════════════════════════ -->
<div id="lateEntryModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header late-header">
            <h2>⏰ Late Entry — Explanation Required</h2>
            <span class="close" onclick="closeLateEntryModal()">&times;</span>
        </div>
        <div style="padding:25px;">
            <div class="late-notice" id="lateNoticeText"></div>

            <form id="lateEntryForm" onsubmit="return submitLateExplanation(event)">
                <input type="hidden" name="action"        value="log_late">
                <input type="hidden" name="specific_hour" id="lateSpecificHourHidden">

                <!-- Batch period — auto-filled, read-only -->
                <div class="form-group">
                    <label>Batch Period</label>
                    <input type="text" id="lateBatchPeriodDisplay" class="form-control" readonly
                           style="background:#f0f2f5; cursor:default; font-weight:600; color:#2c3e50;">
                </div>

                <!-- Specific hour within the batch — officer selects -->
                <div class="form-group">
                    <label for="lateSpecificHour">Hour being entered late *</label>
                    <select id="lateSpecificHour" class="form-control" required
                            onchange="document.getElementById('lateSpecificHourHidden').value = this.value">
                        <option value="">-- Select the specific hour --</option>
                    </select>
                    <small>Select the exact hour slot you are submitting a late entry for</small>
                </div>

                <!-- Reason -->
                <div class="form-group">
                    <label for="lateExplanation">Reason for late submission *</label>
                    <textarea id="lateExplanation"
                              name="explanation"
                              class="form-control"
                              rows="4"
                              required
                              placeholder="Please explain why this entry was not submitted within the required time window…"></textarea>
                </div>

                <div class="form-actions">
                    <button type="button" class="btn-secondary" onclick="closeLateEntryModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" id="lateSubmitBtn" class="btn-primary">
                        <i class="fas fa-paper-plane"></i> Submit Explanation & Proceed
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════════════════════
     TOAST NOTIFICATION
     ═══════════════════════════════════════════════════════════════════════════ -->
<div id="toast" class="toast" style="display:none;"></div>


<!-- ═══════════════════════════════════════════════════════════════════════════
     STYLES
     ═══════════════════════════════════════════════════════════════════════════ -->
<style>
* { margin:0; padding:0; box-sizing:border-box; }

body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f0f2f5;
    color: #333;
}

.main-content {
    margin-left: 250px;
    margin-top: 70px;
    padding: 20px;
    min-height: calc(100vh - 70px);
    background: #f0f2f5;
}

.dashboard-container-11kv { max-width:100%; }

/* ── Page Header ── */
.page-header {
    display:flex; justify-content:space-between; align-items:center;
    margin-bottom:30px; background:white; padding:25px;
    border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.1);
}
.page-header h1 { font-size:28px; color:#2c3e50; margin-bottom:5px; }
.subtitle        { color:#7f8c8d; font-size:14px; }

/* ── Buttons ── */
.btn-primary {
    background: linear-gradient(135deg,#667eea 0%,#764ba2 100%);
    color:white; border:none; padding:12px 24px; border-radius:8px;
    cursor:pointer; font-size:14px; font-weight:600;
    display:flex; align-items:center; gap:8px;
    transition:all .3s ease; box-shadow:0 4px 15px rgba(102,126,234,.3);
}
.btn-primary:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(102,126,234,.4); }
.btn-primary:disabled { opacity:.6; cursor:not-allowed; transform:none; }

.btn-secondary {
    background:#e9ecef; color:#495057; border:none;
    padding:12px 24px; border-radius:8px; cursor:pointer;
    font-size:14px; font-weight:600; display:flex; align-items:center; gap:8px;
    transition:all .3s ease;
}
.btn-secondary:hover { background:#dee2e6; }

/* ── Stats ── */
.stats-grid {
    display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr));
    gap:20px; margin-bottom:30px;
}
.stat-card {
    background:white; padding:20px; border-radius:12px;
    display:flex; align-items:center; gap:15px;
    box-shadow:0 2px 8px rgba(0,0,0,.1); transition:all .3s ease;
}
.stat-card:hover { transform:translateY(-5px); box-shadow:0 8px 20px rgba(0,0,0,.15); }
.stat-icon {
    width:60px; height:60px; border-radius:12px;
    display:flex; align-items:center; justify-content:center;
    font-size:24px; color:white;
}
.stat-card.blue   .stat-icon { background:linear-gradient(135deg,#667eea,#764ba2); }
.stat-card.orange .stat-icon { background:linear-gradient(135deg,#f093fb,#f5576c); }
.stat-card.purple .stat-icon { background:linear-gradient(135deg,#4facfe,#00f2fe); }
.stat-card.green  .stat-icon { background:linear-gradient(135deg,#43e97b,#38f9d7); }
.stat-card.danger .stat-icon { background:linear-gradient(135deg,#fa709a,#fee140); }
.stat-card.warning.stat-icon { background:linear-gradient(135deg,#ffecd2,#fcb69f); }
.stat-card.info   .stat-icon { background:linear-gradient(135deg,#a8edea,#fed6e3); }
.stat-card.red    .stat-icon { background:linear-gradient(135deg,#ff6b6b,#ee5a6f); }
.stat-card.warning .stat-icon { background:linear-gradient(135deg,#ffecd2,#fcb69f); }
.stat-details h3  { font-size:28px; color:#2c3e50; margin-bottom:5px; }
.stat-details p   { color:#7f8c8d; font-size:13px; }

/* ── Matrix card ── */
.matrix-card {
    background:white; border-radius:12px;
    box-shadow:0 2px 8px rgba(0,0,0,.1); margin-bottom:30px; overflow:hidden;
}
.matrix-header {
    padding:20px 25px;
    background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
    color:white; display:flex; justify-content:space-between; align-items:center;
}
.matrix-header h3 { font-size:20px; font-weight:600; }
.matrix-info      { font-size:13px; opacity:.9; }

.matrix-scroll { overflow-x:auto; max-height:600px; overflow-y:auto; }

.load-matrix-table {
    width:100%; border-collapse:collapse; font-size:12px;
}
.load-matrix-table th {
    background:#f8f9fa; padding:12px 8px; text-align:center;
    font-weight:600; color:#495057; border:1px solid #dee2e6;
    position:sticky; top:0; z-index:10;
}
/* Two-line hour header: top line = HH:00, bottom = HH:59 */
th.hour-col { padding:6px 4px; min-width:54px; }
th.hour-col .hour-top { display:block; font-size:11px; font-weight:700; line-height:1.2; }
th.hour-col .hour-bot { display:block; font-size:9px; font-weight:400; color:#888; line-height:1.2; }
.load-matrix-table td {
    padding:10px 8px; text-align:center; border:1px solid #dee2e6;
    cursor:pointer; transition:all .2s ease;
    min-width:60px;
}
.load-matrix-table td:not(.sticky-col):hover {
    transform:scale(1.08); box-shadow:0 2px 10px rgba(0,0,0,.2); z-index:5;
}

.sticky-col { position:sticky; left:0; background:white; z-index:11; font-weight:600; }
.load-matrix-table th.sticky-col { z-index:12; }
.feeder-name { text-align:left !important; min-width:200px; max-width:200px; }

/* Cell states */
.has-load  { background:#d4edda; color:#155724; font-weight:600; }
.has-load:hover  { background:#c3e6cb; }

/* MOD 3: fault cells — light red with fault code visible */
.has-fault { background:#fde8ea; color:#c0392b; font-weight:700; font-size:11px; }
.has-fault:hover { background:#f5c6cb; }

.no-data   { background:#f8f9fa; color:#adb5bd; }
.no-data:hover { background:#e9ecef; }

/* ── Metrics table ── */
.feeder-metrics-card { margin-top:30px; }
.metrics-scroll      { overflow-x:auto; max-height:500px; overflow-y:auto; }
.metrics-table       { width:100%; border-collapse:collapse; font-size:14px; }
.metrics-table thead { position:sticky; top:0; z-index:10; }
.metrics-table th {
    background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
    color:white; padding:15px 12px; text-align:left;
    font-weight:600; border:1px solid rgba(255,255,255,.2);
}
.metrics-table td     { padding:12px; border:1px solid #dee2e6; background:white; }
.metrics-table tbody tr:nth-child(even) { background:#f8f9fa; }
.metrics-table tbody tr:hover { background:#e3f2fd; }
.feeder-name-col      { font-weight:600; color:#2c3e50; min-width:200px; }
.feeder-name-col i    { color:#667eea; margin-right:8px; }
.metric-value         { text-align:right; font-weight:600; color:#27ae60; font-family:'Courier New',monospace; }
.metric-hours         { text-align:center; font-weight:600; color:#3498db; }
.metric-faults        { text-align:center; font-weight:600; color:#95a5a6; }
.metric-faults.has-faults { color:#e74c3c; background:#fadbd8 !important; }

/* ── Charts ── */
.charts-row { display:grid; grid-template-columns:repeat(2,1fr); gap:20px; margin-bottom:30px; }
.chart-card {
    background:white; padding:25px; border-radius:12px;
    box-shadow:0 2px 8px rgba(0,0,0,.1);
}
.modern-chart { background:linear-gradient(to bottom,#fff,#f8f9fa); border:1px solid #e9ecef; }
.chart-card h3 { margin-bottom:20px; color:#2c3e50; font-size:18px; font-weight:600; }

/* ── Modals ── */
.modal {
    display:none; position:fixed; z-index:2000; left:0; top:0;
    width:100%; height:100%;
    background:rgba(0,0,0,.6); backdrop-filter:blur(4px);
}
.modal-content {
    background:white; margin:5% auto; padding:0;
    border-radius:12px; width:90%; max-width:600px;
    box-shadow:0 10px 40px rgba(0,0,0,.3);
    animation:modalSlideIn .3s ease;
    max-height: 90vh; /* Limit height */
    overflow-y: auto; /* Make scrollable */
}
.modal-sm { max-width:460px; margin:12% auto; }

@keyframes modalSlideIn {
    from { transform:translateY(-50px); opacity:0; }
    to   { transform:translateY(0);     opacity:1; }
}

.modal-header {
    background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
    color:white; padding:20px 25px; border-radius:12px 12px 0 0;
    display:flex; justify-content:space-between; align-items:center;
}
.modal-header h2 { font-size:20px; font-weight:600; }
.confirm-header  { background:linear-gradient(135deg,#2c3e50,#3498db); }
.late-header     { background:linear-gradient(135deg,#e74c3c,#c0392b); }

.close { color:white; font-size:32px; font-weight:300; cursor:pointer; transition:transform .2s ease; line-height:1; }
.close:hover { transform:rotate(90deg); }

form { padding:25px; }

.form-group        { margin-bottom:20px; }
.form-group label  { display:block; margin-bottom:8px; color:#2c3e50; font-weight:600; font-size:14px; }
.form-control {
    width:100%; padding:12px; border:2px solid #e9ecef;
    border-radius:8px; font-size:14px; transition:all .3s ease;
    font-family:inherit;
}
.form-control:focus { outline:none; border-color:#667eea; box-shadow:0 0 0 3px rgba(102,126,234,.1); }
.form-control:disabled, .form-control[readonly] {
    background:#f0f2f5; color:#666; cursor:not-allowed;
}
.form-group small { display:block; margin-top:5px; color:#7f8c8d; font-size:12px; }

.max-load-hint { color:#e67e22; font-weight:600; }
.error-msg     { color:#e74c3c; font-size:13px; font-weight:600; margin-top:6px; background:#fde8ea; padding:8px 12px; border-radius:6px; border-left:4px solid #e74c3c; }

#faultSection { transition:all .3s ease; }
#faultSection.disabled { opacity:.45; pointer-events:none; }

.form-actions { display:flex; gap:10px; justify-content:flex-end; margin-top:25px; padding-top:20px; border-top:1px solid #e9ecef; }
.confirm-actions { padding:20px 25px; border-top:1px solid #e9ecef; }

/* Confirm body */
.confirm-body { padding:25px 25px 5px; }
.confirm-body p { font-size:15px; line-height:1.6; color:#2c3e50; }

/* Late-entry notice */
.late-notice {
    background:#fff3cd; border:1px solid #ffc107; border-radius:8px;
    padding:14px 16px; margin-bottom:20px; color:#856404; font-size:14px; line-height:1.6;
}

/* Toast */
.toast {
    position:fixed; bottom:30px; right:30px; z-index:9999;
    background:#2c3e50; color:white; padding:14px 24px;
    border-radius:10px; font-size:14px; font-weight:600;
    box-shadow:0 4px 20px rgba(0,0,0,.3);
    animation:toastIn .3s ease;
}
.toast.success { background:linear-gradient(135deg,#27ae60,#2ecc71); }
.toast.error   { background:linear-gradient(135deg,#e74c3c,#c0392b); }
@keyframes toastIn { from{transform:translateX(120%)} to{transform:translateX(0)} }

/* Responsive */
@media(max-width:768px){
    .main-content   { margin-left:0; margin-top:60px; padding:10px; }
    .stats-grid     { grid-template-columns:1fr; }
    .charts-row     { grid-template-columns:1fr; }
    .modal-content  { width:95%; margin:5% auto; max-height:95vh; }
    .page-header    { flex-direction:column; text-align:center; gap:15px; }
}
</style>


<!-- ═══════════════════════════════════════════════════════════════════════════
     SCRIPTS
     ═══════════════════════════════════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// ── Config ────────────────────────────────────────────────────────────────────
const saveUrl = <?= json_encode(
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    . '://' . $_SERVER['HTTP_HOST']
    . $_SERVER['SCRIPT_NAME']
    . '?page=load_entry'
) ?>;

// Feeder max-load map built from PHP
const feederMaxLoad = {};
<?php foreach ($feeders as $f): ?>
<?php if ($f['max_load'] !== null): ?>
feederMaxLoad[<?= json_encode($f['fdr11kv_code']) ?>] = <?= (float)$f['max_load'] ?>;
<?php endif; ?>
<?php endforeach; ?>

// ── Time helpers ─────────────────────────────────────────────────────────────
/** Current clock hour slot 0-23.  Matches PHP date('G'). */
function getCurrentSlot() {
    return new Date().getHours(); // 0-23 directly
}

/** Operational date string (YYYY-MM-DD) as seen by JS.
 *  During 00:xx we are still in yesterday's op-date. */
function getOpDate() {
    const now = new Date();
    if (now.getHours() === 0) {
        const y = new Date(now); y.setDate(y.getDate()-1);
        return y.toISOString().slice(0,10);
    }
    return now.toISOString().slice(0,10);
}

/** 11kV Batch window info for an hour slot (0-23).
 *  Batch A: 00-07 → free until 09:00, entry until 01:00 next
 *  Batch B: 08-16 → free until 18:00, entry until 01:00 next
 *  Batch C: 17-23 → free until 01:00 next                    */
function getBatchInfo(hour) {
    const now     = new Date();
    const opDate  = getOpDate();
    const nextDay = new Date(opDate); nextDay.setDate(nextDay.getDate()+1);
    const nextStr = nextDay.toISOString().slice(0,10);
    function dl(ds,hh,mm){ return new Date(ds+'T'+String(hh).padStart(2,'0')+':'+String(mm).padStart(2,'0')+':00'); }
    if (hour>=0  && hour<=7)  { const free=dl(opDate,9,0);  const entry=dl(nextStr,1,0); return {label:'00:00–07:59',freeDeadline:'09:00',entryDeadline:'01:00 next day',isFree:now<=free,isOpen:now<entry,hourFrom:0, hourTo:7 }; }
    if (hour>=8  && hour<=16) { const free=dl(opDate,18,0); const entry=dl(nextStr,1,0); return {label:'08:00–16:59',freeDeadline:'18:00',entryDeadline:'01:00 next day',isFree:now<=free,isOpen:now<entry,hourFrom:8, hourTo:16}; }
    /* Batch C: 17-23 */      { const entry=dl(nextStr,1,0); return {label:'17:00–23:59',freeDeadline:'01:00 next day',entryDeadline:'01:00 next day',isFree:now<entry,isOpen:now<entry,hourFrom:17,hourTo:23}; }
}

// ── State ─────────────────────────────────────────────────────────────────────
let pendingFormData = null;

// ── Toast ─────────────────────────────────────────────────────────────────────
function showToast(msg, type='') {
    const t = document.getElementById('toast');
    t.textContent   = msg;
    t.className     = 'toast '+type;
    t.style.display = 'block';
    setTimeout(()=>{ t.style.display='none'; }, 5000);
}

// ── Load/fault section toggle ─────────────────────────────────────────────────
function handleLoadChange() {
    const loadVal = parseFloat(document.getElementById('load_read').value)||0;
    const fSec    = document.getElementById('faultSection');
    const fc      = document.getElementById('fault_code');
    const fr      = document.getElementById('fault_remark');
    const err     = document.getElementById('maxLoadError');
    if (loadVal < 0) { document.getElementById('load_read').value=0; return; }
    if (loadVal === 0) {
        fSec.classList.remove('disabled');
        fc.required=true; fc.disabled=false;
        fr.required=true; fr.disabled=false;
    } else {
        fSec.classList.add('disabled');
        fc.required=false; fc.disabled=true; fc.value='';
        fr.required=false; fr.disabled=true; fr.value='';
    }
    const code    = document.getElementById('fdr11kv_code').value;
    const maxLoad = feederMaxLoad[code];
    err.style.display = (maxLoad!==undefined && loadVal>0 && loadVal>maxLoad) ? 'block' : 'none';
}

function handleFeederChange() {
    const code    = document.getElementById('fdr11kv_code').value;
    const maxLoad = feederMaxLoad[code];
    const hint    = document.getElementById('maxLoadInfo');
    if (maxLoad!==undefined) { hint.textContent='Max allowed load: '+maxLoad.toFixed(2)+' MW'; hint.style.display='block'; }
    else { hint.style.display='none'; }
    handleLoadChange();
}

// ── Open entry modal (opens freely; rules checked on submit) ─────────────────
function openLoadEntryModal(feederCode, hour, cell) {
    const form = document.getElementById('loadEntryForm');
    form.reset();
    document.getElementById('load_read').value           = 0;
    document.getElementById('maxLoadError').style.display = 'none';
    document.getElementById('is_edit').value             = '0';

    const feederSel = document.getElementById('fdr11kv_code');
    const hourSel   = document.getElementById('entry_hour');

    // hour can be 0 (midnight slot) — do NOT use truthy check
    if (feederCode && hour !== null && hour !== undefined && !isNaN(parseInt(hour))) {
        feederSel.value    = feederCode;
        feederSel.disabled = true;
        hourSel.value      = hour;
        hourSel.disabled   = true;

        // Detect edit (cell already has data)
        const hasData = cell && (cell.dataset.load !== '' || cell.dataset.fault !== '');
        document.getElementById('is_edit').value = hasData ? '1' : '0';

        if (hasData) {
            if (cell.dataset.load !== '') document.getElementById('load_read').value = cell.dataset.load;
            if (cell.dataset.fault) setTimeout(()=>{ document.getElementById('fault_code').value = cell.dataset.fault; }, 50);
        }

        const feederName = cell ? cell.dataset.feederName : feederCode;
        const hourLabel  = String(hour).padStart(2,'0')+':00';
        const editBadge  = hasData ? ' (Editing)' : '';
        document.getElementById('modalTitle').textContent = '⚡ Load Entry — '+feederName+' — Hour '+hourLabel+editBadge;
    } else {
        feederSel.disabled = false;
        hourSel.disabled   = false;
        document.getElementById('modalTitle').textContent = '⚡ Add 11kV Load Entry — <?= date('F j, Y', strtotime($today)) ?>';
    }

    handleFeederChange();
    handleLoadChange();
    document.getElementById('loadEntryModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeLoadEntryModal() {
    document.getElementById('loadEntryModal').style.display = 'none';
    document.getElementById('fdr11kv_code').disabled = false;
    document.getElementById('entry_hour').disabled   = false;
    document.body.style.overflow = 'auto';
}

// ── Form submit: validate → time-check → confirm ──────────────────────────────
function handleFormSubmit(e) {
    e.preventDefault();

    const loadVal     = parseFloat(document.getElementById('load_read').value)||0;
    const faultCode   = document.getElementById('fault_code').value;
    const faultRemark = document.getElementById('fault_remark').value.trim();
    const feederSel   = document.getElementById('fdr11kv_code');
    const feederCode  = feederSel.value;
    const feederName  = feederSel.options[feederSel.selectedIndex]?.text || feederCode;
    const hour        = parseInt(document.getElementById('entry_hour').value);
    const hourLabel   = String(hour).padStart(2,'0')+':00';
    const isEdit      = document.getElementById('is_edit').value === '1';

    // Required field checks
    if (!feederCode) { showToast('⚠️ Please select a feeder.','error'); return false; }
    if (hour === null || isNaN(hour) || document.getElementById('entry_hour').value === '') { showToast('⚠️ Please select an hour.','error');  return false; }
    if (loadVal===0 && (!faultCode||!faultRemark)) {
        showToast('⚠️ Fault code and remark are required when load is 0.','error'); return false;
    }

    // max_load check
    const maxLoad = feederMaxLoad[feederCode];
    if (maxLoad!==undefined && loadVal>0 && loadVal>maxLoad) {
        showToast('⚠️ Value exceeds maximum allowed for this feeder ('+maxLoad.toFixed(2)+' MW).','error'); return false;
    }

    // ── Future hour: hard client-side block ───────────────────────────────────
    // During 00:xx (slot=0) we're in yesterday's op-date — all 0-23 slots are past/current
    const slot = getCurrentSlot();
    if (slot >= 1 && hour > slot) {
        showToast('🚫 Hour '+hourLabel+' has not yet occurred. Only current or past hours can be entered.','error');
        return false;
    }

    // Past hours and closed windows are handled server-side.
    // Build and cache FormData then show confirm dialog.
    const confirmLoad = loadVal > 0
        ? `Confirm you want to ${isEdit?'<strong>update</strong>':'insert'} <strong>${loadVal.toFixed(2)} MW</strong> into Hour <strong>${hourLabel}</strong> for feeder <strong>${feederName}</strong>.`
        : `Confirm you want to ${isEdit?'<strong>update</strong>':'record'} fault <strong>${faultCode}</strong> for Hour <strong>${hourLabel}</strong> on feeder <strong>${feederName}</strong>.<br>Remark: <em>${faultRemark}</em>`;

    document.getElementById('confirmText').innerHTML = confirmLoad;

    pendingFormData = new FormData(document.getElementById('loadEntryForm'));
    pendingFormData.set('Fdr11kv_code', feederCode);
    pendingFormData.set('entry_hour',   hour);
    pendingFormData.set('is_edit',      isEdit ? '1' : '0');

    document.getElementById('confirmModal').style.display = 'block';
    return false;
}

function closeConfirmModal() {
    document.getElementById('confirmModal').style.display = 'none';
}

// ── AJAX save ─────────────────────────────────────────────────────────────────
function doSave() {
    closeConfirmModal();
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

    fetch(saveUrl, {method:'POST', body:pendingFormData})
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Save Entry';

            if (data.success) {
                showToast('✅ '+data.message,'success');
                closeLoadEntryModal();
                updateMatrixCell(
                    pendingFormData.get('Fdr11kv_code'),
                    parseInt(pendingFormData.get('entry_hour')),
                    parseFloat(pendingFormData.get('load_read')||0),
                    pendingFormData.get('fault_code')||''
                );
                pendingFormData = null;
            } else if (data.future) {
                showToast('🚫 '+data.message,'error');
            } else if (data.late_entry) {
                // Server requires explanation — open late-entry modal.
                // pendingFormData is preserved for automatic re-submit after explanation.
                openLateEntryModal(data);
            } else {
                showToast('❌ '+data.message,'error');
            }
        })
        .catch(()=>{
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Save Entry';
            showToast('❌ Network error. Please try again.','error');
        });
}

// ── Matrix cell update ────────────────────────────────────────────────────────
function updateMatrixCell(feederCode, hour, load, fault) {
    const cell = document.querySelector(`.matrix-cell[data-feeder-code="${feederCode}"][data-hour="${hour}"]`);
    if (!cell) return;
    cell.dataset.load  = load;
    cell.dataset.fault = fault;
    if (load>0) {
        cell.className='matrix-cell has-load'; cell.textContent=load.toFixed(2); cell.title='';
    } else if (fault) {
        cell.className='matrix-cell has-fault'; cell.textContent=fault.substring(0,8).toUpperCase(); cell.title='Fault: '+fault;
    } else {
        cell.className='matrix-cell no-data'; cell.textContent='–'; cell.title='Click to enter / edit';
    }
}

// ── Late-entry explanation modal ──────────────────────────────────────────────
function openLateEntryModal(serverData) {
    // Notice text
    document.getElementById('lateNoticeText').innerHTML =
        '⚠️ Batch: <strong>' + serverData.batch_label + '</strong> &nbsp;|&nbsp; '
        + 'Free entry closed: <strong>' + serverData.deadline + '</strong><br>'
        + 'Select the specific hour you are entering late and provide a reason. '
        + 'Once submitted you may enter the value.';

    // Batch period - read-only display (auto-filled)
    document.getElementById('lateBatchPeriodDisplay').value =
        serverData.batch_label + '  (free deadline: ' + serverData.deadline + ')';

    // Build specific-hour dropdown -- only past hours within the batch range
    const hourSel  = document.getElementById('lateSpecificHour');
    const hiddenHr = document.getElementById('lateSpecificHourHidden');
    hourSel.innerHTML = '<option value="">-- Select the specific hour --</option>';
    hiddenHr.value    = '';

    const currentSlot = getCurrentSlot();

    for (let h = serverData.hour_from; h <= serverData.hour_to; h++) {
        // During midnight window (currentSlot=0) all 24 hours of the op-date are past — show them all
        if (currentSlot >= 1 && h >= currentSlot) continue;

        const opt = document.createElement('option');
        opt.value = h;
        opt.textContent = String(h).padStart(2, '0') + ':00';

        // Pre-select the hour that is pending (same pattern as 33kV)
        if (pendingFormData && parseInt(pendingFormData.get('entry_hour')) === h) {
            opt.selected   = true;
            hiddenHr.value = h;
        }
        hourSel.appendChild(opt);
    }

    document.getElementById('lateExplanation').value = '';
    document.getElementById('lateEntryModal').style.display = 'block';
}

function closeLateEntryModal() {
    document.getElementById('lateEntryModal').style.display = 'none';
}

// After explanation logged → automatically re-submit the original save
function submitLateExplanation(e) {
    e.preventDefault();
    const btn = document.getElementById('lateSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging…';

    fetch(saveUrl, {method:'POST', body: new FormData(document.getElementById('lateEntryForm'))})
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Explanation & Proceed';
            if (data.success) {
                closeLateEntryModal();
                showToast('📋 Explanation logged — re-submitting entry…','success');
                if (pendingFormData) setTimeout(()=>doSave(), 700);
            } else {
                showToast('❌ '+data.message,'error');
            }
        })
        .catch(()=>{
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Explanation & Proceed';
            showToast('❌ Network error. Please try again.','error');
        });
    return false;
}

// ── Keyboard / backdrop close ─────────────────────────────────────────────────
window.onclick = function(e) {
    ['loadEntryModal','confirmModal','lateEntryModal'].forEach(id => {
        if (e.target===document.getElementById(id)) {
            document.getElementById(id).style.display='none';
            document.body.style.overflow='auto';
        }
    });
};
document.addEventListener('keydown', e => {
    if (e.key==='Escape') { closeLoadEntryModal(); closeConfirmModal(); closeLateEntryModal(); }
});
document.addEventListener('DOMContentLoaded', ()=>{
    document.getElementById('fdr11kv_code').addEventListener('change', handleFeederChange);
    handleLoadChange();
});

// ── Charts ────────────────────────────────────────────────────────────────────
const hourlyTotals = Array(24).fill(0);
<?php foreach ($load_data as $row): ?>
hourlyTotals[<?= (int)$row['entry_hour'] ?>] += <?= (float)$row['load_read'] ?>;
<?php endforeach; ?>

const trendCtx = document.getElementById('loadTrendChart').getContext('2d');
const gradient = trendCtx.createLinearGradient(0,0,0,400);
gradient.addColorStop(0,'rgba(102,126,234,.4)');
gradient.addColorStop(1,'rgba(118,75,162,.05)');
new Chart(trendCtx,{
    type:'line',
    data:{
        labels:[<?php for($i=0;$i<=23;$i++) echo "'".str_pad($i,2,'0',STR_PAD_LEFT).":00',"; ?>],
        datasets:[{label:'Total Load (MW)',data:hourlyTotals,borderColor:'#667eea',backgroundColor:gradient,
            tension:.4,fill:true,pointRadius:5,pointHoverRadius:8,
            pointBackgroundColor:'#667eea',pointBorderColor:'#fff',pointBorderWidth:2,
            pointHoverBackgroundColor:'#764ba2',pointHoverBorderColor:'#fff',pointHoverBorderWidth:3}]
    },
    options:{responsive:true,maintainAspectRatio:true,
        plugins:{
            legend:{display:true,position:'top',labels:{font:{size:13,weight:'600'},usePointStyle:true,padding:15}},
            tooltip:{backgroundColor:'rgba(0,0,0,.8)',titleFont:{size:14,weight:'bold'},bodyFont:{size:13},padding:12,cornerRadius:8,
                callbacks:{label:ctx=>'Load: '+ctx.parsed.y.toFixed(2)+' MW'}}
        },
        scales:{
            y:{beginAtZero:true,grid:{color:'rgba(0,0,0,.05)',drawBorder:false},ticks:{font:{size:12},callback:v=>v.toFixed(2)+' MW'}},
            x:{grid:{display:false},ticks:{font:{size:11}}}
        },
        interaction:{mode:'index',intersect:false}
    }
});

const feederTotals={}; let grandTotal=0;
<?php foreach($matrix as $code => $data): ?>
let total_<?= preg_replace('/[^a-zA-Z0-9]/','_',$code) ?>=0;
<?php foreach($data['hours'] as $hour=>$h_data): ?>
<?php if($h_data && $h_data['load']>0): ?>
total_<?= preg_replace('/[^a-zA-Z0-9]/','_',$code) ?>+=<?= (float)$h_data['load'] ?>;
<?php endif; ?><?php endforeach; ?>
if(total_<?= preg_replace('/[^a-zA-Z0-9]/','_',$code) ?>>0){
    feederTotals[<?= json_encode($data['fdr11kv_name']) ?>]=total_<?= preg_replace('/[^a-zA-Z0-9]/','_',$code) ?>;
    grandTotal+=total_<?= preg_replace('/[^a-zA-Z0-9]/','_',$code) ?>;
}
<?php endforeach; ?>

const sortedFeeders=Object.entries(feederTotals).sort((a,b)=>b[1]-a[1]);
const modernColors=['#667eea','#f093fb','#4facfe','#43e97b','#fa709a','#ffecd2','#a8edea','#ff6b6b','#f5576c','#38f9d7','#fee140','#fed6e3','#00f2fe','#fcb69f','#764ba2'];
new Chart(document.getElementById('loadPieChart').getContext('2d'),{
    type:'doughnut',
    data:{labels:sortedFeeders.map(f=>f[0]),datasets:[{data:sortedFeeders.map(f=>f[1]),backgroundColor:modernColors,borderColor:'#fff',borderWidth:3,hoverOffset:15}]},
    options:{responsive:true,maintainAspectRatio:true,
        plugins:{
            legend:{position:'right',labels:{boxWidth:15,padding:12,font:{size:12,weight:'600'},
                generateLabels:chart=>chart.data.labels.map((lbl,i)=>({
                    text:lbl+': '+((chart.data.datasets[0].data[i]/grandTotal)*100).toFixed(1)+'%',
                    fillStyle:chart.data.datasets[0].backgroundColor[i],hidden:false,index:i
                }))}},
            tooltip:{backgroundColor:'rgba(0,0,0,.8)',titleFont:{size:14,weight:'bold'},bodyFont:{size:13},padding:12,cornerRadius:8,
                callbacks:{label:ctx=>[ctx.label,'Load: '+ctx.parsed.toFixed(2)+' MW','Share: '+((ctx.parsed/grandTotal)*100).toFixed(1)+'%']}}
        },
        animation:{animateRotate:true,animateScale:true,duration:1500,easing:'easeInOutQuart'}
    }
});

// ── Operational day auto-reset at exactly 01:00 ──────────────────────────────
(function scheduleOpDayReset() {
    const now = new Date();
    const h   = now.getHours();
    const m   = now.getMinutes();

    const target = new Date(now);
    target.setSeconds(5, 0);
    target.setMinutes(0);
    target.setHours(1);
    if (h > 1 || (h === 1 && m >= 1)) {
        target.setDate(target.getDate() + 1);
    }

    const msUntil = target - now;
    if (msUntil <= 0) return;

    setTimeout(function () {
        const lbl = document.getElementById('opDayLabel');
        if (lbl) {
            const d = new Date();
            lbl.textContent = d.toLocaleDateString('en-GB', {
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
            });
        }
        document.querySelectorAll('.matrix-cell').forEach(function (cell) {
            cell.className   = 'matrix-cell no-data';
            cell.textContent = '\u2013';
            cell.dataset.load  = '';
            cell.dataset.fault = '';
            cell.title = 'Click to enter / edit';
        });
        setTimeout(function () { window.location.reload(); }, 1200);
    }, msUntil);
})();

</script>


<?php require __DIR__ . '/../layout/footer.php'; ?>
