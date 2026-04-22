<?php require __DIR__ . '/../layout/header.php'; ?>
<?php require __DIR__ . '/../layout/sidebar.php'; ?>

<div class="main-content">
    <div class="dashboard-container-33kv">

        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>⚡ 33kV Load Monitoring Dashboard</h1>
                <p class="subtitle">Real-time monitoring for <span id="opDayLabel"><?= date('l, F j, Y', strtotime($today)) ?></span> <small style="color:#f39c12;font-size:11px;">(operational day — closes 01:00)</small></p>
            </div>
            <button class="btn-primary" onclick="openLoadEntryModal(null, null, null)">
                <i class="fas fa-plus"></i> Add Load Entry
            </button>
        </div>

        <!-- Filters -->
        <div class="filters-panel">
            <form method="GET" class="filters-form" id="filterForm">
                <?php foreach ($_GET as $key => $value): if ($key !== 'ts_code'): ?>
                    <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
                <?php endif; endforeach; ?>
                <div class="filter-group">
                    <label for="ts_code"><i class="fas fa-building"></i> Transmission Station</label>
                    <select name="ts_code" id="ts_code" class="form-control">
                        <option value="all" <?= ($selected_ts === 'all' || empty($selected_ts)) ? 'selected' : '' ?>>All Transmission Stations</option>
                        <?php foreach ($transmission_stations as $ts): ?>
                            <option value="<?= htmlspecialchars($ts['ts_code']) ?>"
                                    <?= $selected_ts === $ts['ts_code'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ts['station_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Apply Filters</button>
                <?php if ($selected_ts !== 'all' && !empty($selected_ts)):
                    $clear_params = $_GET; $clear_params['ts_code'] = 'all'; ?>
                    <a href="?<?= http_build_query($clear_params) ?>" class="btn-clear">
                        <i class="fas fa-times"></i> Clear Filters
                    </a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card red">
                <div class="stat-icon"><i class="fas fa-bolt"></i></div>
                <div class="stat-details"><h3><?= $total_feeders ?></h3><p>Feeders Monitored</p></div>
            </div>
            <div class="stat-card orange">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-details"><h3><?= $supply_hours ?></h3><p>Supply Hours</p></div>
            </div>
            <div class="stat-card purple">
                <div class="stat-icon"><i class="fas fa-charging-station"></i></div>
                <div class="stat-details"><h3><?= number_format($total_load, 2) ?> MW</h3><p>Total Load</p></div>
            </div>
            <div class="stat-card blue">
                <div class="stat-icon"><i class="fas fa-tachometer-alt"></i></div>
                <div class="stat-details"><h3><?= number_format($avg_load, 2) ?> MW</h3><p>Average Load</p></div>
            </div>
            <div class="stat-card green">
                <div class="stat-icon"><i class="fas fa-arrow-up"></i></div>
                <div class="stat-details"><h3><?= number_format($peak_load, 2) ?> MW</h3><p>Peak Load</p></div>
            </div>
            <div class="stat-card warning">
                <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-details"><h3><?= $fault_hours ?></h3><p>Fault Hours</p></div>
            </div>
            <div class="stat-card info">
                <div class="stat-icon"><i class="fas fa-tasks"></i></div>
                <div class="stat-details"><h3><?= number_format($completion_percentage, 1) ?>%</h3><p>Data Completion</p></div>
            </div>
        </div>

        <?php if (!empty($feeders)): ?>

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
                            <th class="sticky-col2">Station</th>
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
                            <td class="sticky-col feeder-name"><?= htmlspecialchars($feeder['fdr33kv_name']) ?></td>
                            <td class="sticky-col2 location-name"><?= htmlspecialchars($feeder['station_name']) ?></td>
                            <?php for ($h = 0; $h <= 23; $h++):
                                $hour_data = $feeder['hours'][$h];
                                $has_data  = !is_null($hour_data);
                                $load      = $has_data ? floatval($hour_data['load'])              : null;
                                $fault     = $has_data ? trim($hour_data['fault'] ?? '')           : '';

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

                                $data_load  = $has_data ? $load  : '';
                                $data_fault = $has_data ? $fault : '';
                            ?>
                                <td class="matrix-cell <?= $class ?>"
                                    data-feeder-code="<?= htmlspecialchars($code) ?>"
                                    data-feeder-name="<?= htmlspecialchars($feeder['fdr33kv_name']) ?>"
                                    data-ts-code="<?= htmlspecialchars($feeder['ts_code'] ?? '') ?>"
                                    data-hour="<?= $h ?>"
                                    data-load="<?= htmlspecialchars((string)$data_load) ?>"
                                    data-fault="<?= htmlspecialchars($data_fault) ?>"
                                    onclick="openLoadEntryModal('<?= htmlspecialchars($code, ENT_QUOTES) ?>', <?= $h ?>, this)"
                                    title="<?= $has_data && !empty($fault) ? 'Fault: '.htmlspecialchars($fault) : 'Click to enter / edit' ?>">
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
                            <th>Feeder Name</th><th>Station</th>
                            <th>Total Load (MW)</th><th>Avg Load (MW)</th>
                            <th>Peak Load (MW)</th><th>Supply Hours</th>
                            <th>Fault Count</th><th>Fault Hours</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($feeder_summary as $code => $summary): ?>
                        <tr>
                            <td class="feeder-name-col"><i class="fas fa-bolt"></i> <?= htmlspecialchars($summary['fdr33kv_name']) ?></td>
                            <td><?= htmlspecialchars($summary['station_name']) ?></td>
                            <td class="metric-value"><?= number_format($summary['total_load'], 2) ?></td>
                            <td class="metric-value"><?= number_format($summary['avg_load'], 2) ?></td>
                            <td class="metric-value"><?= number_format($summary['peak_load'], 2) ?></td>
                            <td class="metric-hours"><?= $summary['supply_hours'] ?></td>
                            <td class="metric-faults <?= $summary['fault_count'] > 0 ? 'has-faults' : '' ?>"><?= $summary['fault_count'] ?></td>
                            <td class="metric-faults <?= $summary['fault_hours']  > 0 ? 'has-faults' : '' ?>"><?= $summary['fault_hours'] ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Charts -->
        <div class="charts-row">
            <div class="chart-card modern-chart">
                <h3>📈 Hourly Load Trend</h3>
                <canvas id="hourlyTrendChart"></canvas>
            </div>
            <div class="chart-card modern-chart">
                <h3>📊 Load Distribution by Feeder</h3>
                <canvas id="feederPieChart"></canvas>
            </div>
            <div class="chart-card modern-chart">
                <h3>🏢 Load by Transmission Station</h3>
                <canvas id="tsPieChart"></canvas>
            </div>
        </div>

        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-filter fa-5x"></i>
            <h2>No Feeders Found</h2>
            <p>Select a transmission station above or clear filters to view all feeders</p>
        </div>
        <?php endif; ?>

    </div><!-- /.dashboard-container-33kv -->
</div><!-- /.main-content -->


<!-- ═══════════════════════════════════════════════════════════════════════════
     MODAL 1 — Load Entry / Edit
     ═══════════════════════════════════════════════════════════════════════════ -->
<div id="loadEntryModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle">⚡ 33kV Load Entry — <?= date('F j, Y', strtotime($today)) ?></h2>
            <span class="close" onclick="closeLoadEntryModal()">&times;</span>
        </div>

        <form id="loadEntryForm" onsubmit="return handleFormSubmit(event)">
            <input type="hidden" name="action"     value="save_load">
            <input type="hidden" name="entry_date" value="<?= $today ?>">
            <input type="hidden" name="is_edit"    id="is_edit" value="0">

            <!-- Transmission Station (grayed when pre-filled from cell click) -->
            <div class="form-group">
                <label for="modal_ts_code"><i class="fas fa-building"></i> Transmission Station *</label>
                <select id="modal_ts_code" class="form-control" required>
                    <option value="">-- Select Transmission Station --</option>
                    <?php foreach ($transmission_stations as $ts): ?>
                        <option value="<?= htmlspecialchars($ts['ts_code']) ?>"
                                <?= ($selected_ts !== 'all' && $selected_ts === $ts['ts_code']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ts['station_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 33kV Feeder (grayed when pre-filled from cell click) -->
            <div class="form-group">
                <label for="fdr33kv_code"><i class="fas fa-bolt"></i> 33kV Feeder *</label>
                <select name="fdr33kv_code" id="fdr33kv_code" required class="form-control" disabled>
                    <option value="">-- Select Transmission Station First --</option>
                </select>
                <small id="maxLoadInfo" class="max-load-hint" style="display:none;"></small>
            </div>

            <!-- Hour (grayed when pre-filled from cell click) -->
            <div class="form-group">
                <label for="entry_hour"><i class="fas fa-clock"></i> Hour *</label>
                <select name="entry_hour" id="entry_hour" required class="form-control">
                    <option value="">-- Select Hour --</option>
                    <?php for ($h = 0; $h <= 23; $h++): ?>
                        <option value="<?= $h ?>"><?= str_pad($h, 2, '0', STR_PAD_LEFT) ?>:00</option>
                    <?php endfor; ?>
                </select>
            </div>

            <!-- Load Reading -->
            <div class="form-group">
                <label for="load_read"><i class="fas fa-tachometer-alt"></i> Load Reading (MW) *</label>
                <input type="number" step="0.01" id="load_read" name="load_read"
                       class="form-control" min="0" value="0" required
                       oninput="handleLoadChange()">
                <small id="loadHint">Enter 0 if there is a fault, or the actual load if supply is available</small>
                <div id="maxLoadError" class="error-msg" style="display:none;">
                    ⚠️ Value entered exceeded the maximum allowed for the feeder
                </div>
            </div>

            <!-- Fault section (shown only when load = 0) -->
            <div id="faultSection">
                <div class="form-group">
                    <label for="fault_code"><i class="fas fa-exclamation-triangle"></i> Fault Code *</label>
                    <select name="fault_code" id="fault_code" class="form-control" required>
                        <option value="">-- Select Fault Code --</option>
                        <?php foreach ($fault_codes as $fc => $desc): ?>
                            <option value="<?= $fc ?>"><?= $fc ?> – <?= htmlspecialchars($desc) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="fault_remark"><i class="fas fa-comment"></i> Fault Remark *</label>
                    <textarea name="fault_remark" id="fault_remark" class="form-control" rows="3"
                              placeholder="Describe the fault reason…" required></textarea>
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

                <div class="form-group">
                    <label>Batch Period</label>
                    <input type="text" id="lateBatchPeriodDisplay" class="form-control" readonly
                           style="background:#f0f2f5; cursor:default; font-weight:600; color:#2c3e50;">
                </div>

                <div class="form-group">
                    <label for="lateSpecificHour">Hour being entered late *</label>
                    <select id="lateSpecificHour" class="form-control" required
                            onchange="document.getElementById('lateSpecificHourHidden').value = this.value">
                        <option value="">-- Select the specific hour --</option>
                    </select>
                    <small>Select the exact hour slot you are submitting a late entry for</small>
                </div>

                <div class="form-group">
                    <label for="lateExplanation">Reason for late submission *</label>
                    <textarea id="lateExplanation" name="explanation" class="form-control" rows="4" required
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


<!-- Toast notification -->
<div id="toast" class="toast" style="display:none;"></div>

<!-- ═══════════════════════════════════════════════════════════════════════════
     STYLES
     ═══════════════════════════════════════════════════════════════════════════ -->
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; background:#f0f2f5; color:#333; }

.main-content { margin-left:260px; padding:22px; padding-top:90px; min-height:calc(100vh - 64px); background:#f0f2f5; }
.dashboard-container-33kv { max-width:100%; }

/* Page Header */
.page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;
    background:white; padding:25px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.1); }
.page-header h1 { font-size:28px; color:#2c3e50; margin-bottom:5px; }
.subtitle { color:#7f8c8d; font-size:14px; }

/* Buttons */
.btn-primary { background:linear-gradient(135deg,#667eea,#764ba2); color:white; border:none;
    padding:12px 24px; border-radius:8px; cursor:pointer; font-size:14px; font-weight:600;
    display:inline-flex; align-items:center; gap:8px; transition:all .3s; }
.btn-primary:hover { transform:translateY(-2px); box-shadow:0 5px 15px rgba(102,126,234,.4); }
.btn-primary:disabled { opacity:.6; cursor:not-allowed; transform:none; }
.btn-secondary { background:#e9ecef; color:#495057; border:none; padding:12px 24px; border-radius:8px;
    cursor:pointer; font-size:14px; font-weight:600; display:inline-flex; align-items:center; gap:8px; transition:all .3s; }
.btn-secondary:hover { background:#dee2e6; }
.btn-filter { background:linear-gradient(135deg,#667eea,#764ba2); color:white; border:none; padding:10px 20px;
    border-radius:8px; cursor:pointer; font-size:14px; font-weight:600; display:flex; align-items:center; gap:8px; }
.btn-clear { background:#e74c3c; color:white; border:none; padding:10px 20px; border-radius:8px; cursor:pointer;
    font-size:14px; font-weight:600; display:flex; align-items:center; gap:8px; text-decoration:none; }
.btn-clear:hover { background:#c0392b; }

/* Filters */
.filters-panel { background:white; padding:20px; border-radius:12px; margin-bottom:30px; box-shadow:0 2px 8px rgba(0,0,0,.1); }
.filters-form { display:flex; gap:15px; align-items:flex-end; flex-wrap:wrap; }
.filter-group { flex:1; min-width:250px; }
.filter-group label { display:block; margin-bottom:8px; font-weight:600; color:#2c3e50; font-size:14px; }
.filter-group label i { margin-right:5px; color:#667eea; }

/* Stats */
.stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:20px; margin-bottom:30px; }
.stat-card { background:white; padding:20px; border-radius:12px; display:flex; align-items:center; gap:15px;
    box-shadow:0 2px 8px rgba(0,0,0,.08); transition:all .3s; }
.stat-card:hover { transform:translateY(-3px); box-shadow:0 6px 20px rgba(0,0,0,.15); }
.stat-icon { width:55px; height:55px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:22px; color:white; }
.stat-card.red    .stat-icon { background:linear-gradient(135deg,#ff6b6b,#ee5a6f); }
.stat-card.orange .stat-icon { background:linear-gradient(135deg,#f093fb,#f5576c); }
.stat-card.purple .stat-icon { background:linear-gradient(135deg,#667eea,#764ba2); }
.stat-card.blue   .stat-icon { background:linear-gradient(135deg,#4facfe,#00f2fe); }
.stat-card.green  .stat-icon { background:linear-gradient(135deg,#43e97b,#38f9d7); }
.stat-card.warning .stat-icon { background:linear-gradient(135deg,#f6d365,#fda085); }
.stat-card.info   .stat-icon { background:linear-gradient(135deg,#a8edea,#fed6e3); }
.stat-details h3 { font-size:26px; color:#2c3e50; margin-bottom:3px; font-weight:700; }
.stat-details p { color:#7f8c8d; font-size:13px; }

/* Matrix */
.matrix-card { background:white; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.08); margin-bottom:30px; overflow:hidden; }
.matrix-header { padding:20px 25px; background:linear-gradient(135deg,#667eea,#764ba2); color:white;
    display:flex; justify-content:space-between; align-items:center; }
.matrix-header h3 { font-size:18px; font-weight:600; }
.matrix-info { font-size:13px; opacity:.9; }
.matrix-scroll { overflow-x:auto; max-height:450px; overflow-y:auto; }
.load-matrix-table { width:100%; border-collapse:collapse; font-size:12px; }
.load-matrix-table thead { position:sticky; top:0; z-index:10; }
.load-matrix-table th { background:linear-gradient(135deg,#667eea,#764ba2); color:white;
    padding:10px 8px; text-align:center; font-weight:600; border:1px solid rgba(255,255,255,.2); white-space:nowrap; }
/* Two-line hour header: top=HH:00, bottom=HH:59 */
th.hour-col { padding:5px 3px; min-width:52px; }
th.hour-col .hour-top { display:block; font-size:11px; font-weight:700; line-height:1.3; }
th.hour-col .hour-bot { display:block; font-size:9px;  font-weight:400; opacity:.75;    line-height:1.2; }
.load-matrix-table td { padding:8px 6px; text-align:center; border:1px solid #e9ecef; }
.sticky-col { position:sticky; left:0; z-index:5; background:white; text-align:left !important;
    font-weight:600; min-width:160px; padding-left:12px !important; }
.sticky-col2 { position:sticky; left:160px; z-index:5; background:#f8f9fa; text-align:left !important;
    min-width:130px; padding-left:8px !important; }
.load-matrix-table th.sticky-col,
.load-matrix-table th.sticky-col2 { z-index:12; }
.matrix-cell { cursor:pointer; min-width:52px; transition:all .15s; }
.matrix-cell:hover { transform:scale(1.1); box-shadow:0 2px 8px rgba(0,0,0,.2); z-index:3; position:relative; }
.has-load  { background:#d4edda; color:#155724; font-weight:600; }
.has-load:hover { background:#c3e6cb; }
.has-fault { background:#fde8ea; color:#c0392b; font-weight:700; font-size:11px; }
.has-fault:hover { background:#f5b7b1; }
.no-data   { background:#f8f9fa; color:#bdc3c7; }
.no-data:hover { background:#e9ecef; color:#7f8c8d; }
.feeder-name { font-weight:600; color:#2c3e50; }
.location-name { color:#7f8c8d; font-size:12px; }

/* Metrics table */
.feeder-metrics-card .metrics-scroll { overflow-x:auto; max-height:350px; overflow-y:auto; }
.metrics-table { width:100%; border-collapse:collapse; font-size:13px; }
.metrics-table thead { position:sticky; top:0; z-index:10; }
.metrics-table th { background:linear-gradient(135deg,#667eea,#764ba2); color:white;
    padding:12px; text-align:left; font-weight:600; border:1px solid rgba(255,255,255,.2); }
.metrics-table td { padding:10px 12px; border:1px solid #e9ecef; background:white; }
.metrics-table tbody tr:nth-child(even) td { background:#f8f9fa; }
.metrics-table tbody tr:hover td { background:#e8f4fd; }
.feeder-name-col { font-weight:600; color:#2c3e50; }
.feeder-name-col i { color:#667eea; margin-right:6px; }
.metric-value { text-align:right; font-weight:600; color:#27ae60; font-family:monospace; }
.metric-hours { text-align:center; font-weight:600; color:#3498db; }
.metric-faults { text-align:center; font-weight:600; color:#95a5a6; }
.metric-faults.has-faults { color:#e74c3c; background:#fadbd8 !important; }

/* Charts */
.charts-row { display:grid; grid-template-columns:repeat(3,1fr); gap:20px; margin-bottom:30px; }
.chart-card { background:white; padding:20px; border-radius:12px; box-shadow:0 2px 8px rgba(0,0,0,.08); }
.modern-chart h3 { margin-bottom:15px; color:#2c3e50; font-size:15px; font-weight:600; }

/* Form controls */
.form-control { width:100%; padding:10px 14px; border:2px solid #e9ecef; border-radius:8px; font-size:14px;
    transition:all .3s; font-family:inherit; }
.form-control:focus { outline:none; border-color:#667eea; box-shadow:0 0 0 3px rgba(102,126,234,.1); }
.form-control:disabled, .form-control[readonly] { background:#f0f2f5; color:#666; cursor:not-allowed; }

/* Modal */
.modal { display:none; position:fixed; z-index:2000; left:0; top:0; width:100%; height:100%;
    background:rgba(0,0,0,.6); backdrop-filter:blur(4px); }
.modal-content { background:white; margin:5% auto; border-radius:12px; width:90%; max-width:580px;
    box-shadow:0 10px 40px rgba(0,0,0,.3); animation:slideIn .3s ease; max-height:90vh; overflow-y:auto; }
.modal-sm { max-width:460px; }
@keyframes slideIn { from{transform:translateY(-40px);opacity:0} to{transform:translateY(0);opacity:1} }
.modal-header { background:linear-gradient(135deg,#667eea,#764ba2); color:white; padding:20px 25px;
    border-radius:12px 12px 0 0; display:flex; justify-content:space-between; align-items:center; }
.modal-header h2 { font-size:18px; font-weight:600; }
.confirm-header { background:linear-gradient(135deg,#2c3e50,#34495e); }
.late-header { background:linear-gradient(135deg,#e74c3c,#c0392b) !important; }
.close { color:white; font-size:28px; font-weight:300; cursor:pointer; line-height:1; transition:transform .2s; }
.close:hover { transform:rotate(90deg); }
form { padding:25px; }
.form-group { margin-bottom:18px; }
.form-group label { display:block; margin-bottom:7px; color:#2c3e50; font-weight:600; font-size:14px; }
.form-group label i { margin-right:5px; color:#667eea; }
.form-group small { display:block; margin-top:5px; color:#7f8c8d; font-size:12px; }
.max-load-hint { color:#e67e22 !important; font-weight:600 !important; }
.error-msg { color:#e74c3c; font-size:13px; font-weight:600; margin-top:6px;
    background:#fde8ea; padding:8px 12px; border-radius:6px; border-left:4px solid #e74c3c; }
.form-actions { display:flex; gap:10px; justify-content:flex-end; margin-top:20px;
    padding-top:18px; border-top:1px solid #e9ecef; }
.confirm-body { padding:20px 25px; }
.confirm-body p { font-size:14px; line-height:1.7; color:#2c3e50; }
.confirm-actions { padding:0 25px 20px; border-top:none; margin-top:0; }

/* Fault section toggle */
#faultSection { transition:opacity .3s; }
#faultSection.disabled { opacity:.35; pointer-events:none; }
#faultSection.disabled .form-control { background:#f0f2f5; }

/* Late-entry notice */
.late-notice { background:#fff3cd; border:1px solid #ffc107; border-radius:8px;
    padding:14px 16px; margin-bottom:20px; color:#856404; font-size:14px; line-height:1.6; }

/* Toast */
.toast { position:fixed; bottom:30px; right:30px; z-index:9999; background:#2c3e50; color:white;
    padding:14px 22px; border-radius:10px; font-size:14px; font-weight:600;
    box-shadow:0 4px 20px rgba(0,0,0,.3); animation:toastIn .3s ease; max-width:400px; }
.toast.success { background:linear-gradient(135deg,#27ae60,#2ecc71); }
.toast.error   { background:linear-gradient(135deg,#e74c3c,#c0392b); }
@keyframes toastIn { from{transform:translateX(120%)} to{transform:translateX(0)} }

/* Empty state */
.empty-state { text-align:center; padding:60px 20px; color:#7f8c8d; }
.empty-state i { color:#dee2e6; margin-bottom:20px; }
.empty-state h2 { font-size:24px; margin-bottom:10px; color:#2c3e50; }

/* Responsive */
@media(max-width:768px){
    .main-content { margin-left:0; padding-top:70px; padding:10px; }
    .stats-grid { grid-template-columns:1fr; }
    .charts-row { grid-template-columns:1fr; }
    .modal-content { width:95%; margin:5% auto; max-height:95vh; }
    .page-header { flex-direction:column; text-align:center; gap:15px; }
}
</style>

<!-- ═══════════════════════════════════════════════════════════════════════════
     SCRIPTS
     ═══════════════════════════════════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// ── Config ────────────────────────────────────────────────────────────────────
// Build save URL relative to current page — works on any server/path (Laragon, Apache, etc.)
const saveUrl = (function() {
    // Points to the same ajax file the 33kV dashboard already uses
    const parts = window.location.pathname.split('/');
    // Remove last segment (e.g. index.php) and append ajax path
    parts.pop();
    return window.location.protocol + '//' + window.location.host + parts.join('/') + '/ajax/33kv_save.php';
})();

// All feeders array (for TS→feeder cascade in manual modal)
const allFeeders = <?= json_encode(array_values($all_feeders)) ?>;

// Feeder max-load map built from PHP
const feederMaxLoad = {};
<?php foreach ($all_feeders as $f): ?>
<?php if (isset($f['max_load']) && $f['max_load'] !== null): ?>
feederMaxLoad[<?= json_encode($f['fdr33kv_code']) ?>] = <?= (float)$f['max_load'] ?>;
<?php endif; ?>
<?php endforeach; ?>

// ── 33kV time helpers ─────────────────────────────────────────────────────────
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

/** 33kV Batch window info for an hour slot (0-23).
 *  Batch A: 00-11 → free until 15:00, entry until 01:00 next
 *  Batch B: 12-19 → free until 20:00, entry until 01:00 next
 *  Batch C: 20-23 → free until 01:00 next                    */
function getBatchInfo(hour) {
    const now     = new Date();
    const opDate  = getOpDate();
    const nextDay = new Date(opDate); nextDay.setDate(nextDay.getDate()+1);
    const nextStr = nextDay.toISOString().slice(0,10);
    function dl(ds,hh,mm){ return new Date(ds+'T'+String(hh).padStart(2,'0')+':'+String(mm).padStart(2,'0')+':00'); }
    if (hour>=0  && hour<=11) { const free=dl(opDate,15,0); const entry=dl(nextStr,1,0); return {label:'00:00–11:59',freeDeadline:'15:00',entryDeadline:'01:00 next day',isFree:now<=free,isOpen:now<entry,hourFrom:0, hourTo:11}; }
    if (hour>=12 && hour<=19) { const free=dl(opDate,20,0); const entry=dl(nextStr,1,0); return {label:'12:00–19:59',freeDeadline:'20:00',entryDeadline:'01:00 next day',isFree:now<=free,isOpen:now<entry,hourFrom:12,hourTo:19}; }
    /* Batch C: 20-23 */       { const entry=dl(nextStr,1,0); return {label:'20:00–23:59',freeDeadline:'01:00 next day',entryDeadline:'01:00 next day',isFree:now<entry,isOpen:now<entry,hourFrom:20,hourTo:23}; }
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

// ── TS → Feeder cascade (manual entry only) ───────────────────────────────────
function loadFeedersForTs(tsCode, selectedCode) {
    const sel = document.getElementById('fdr33kv_code');
    if (!tsCode) {
        sel.innerHTML = '<option value="">-- Select Transmission Station First --</option>';
        sel.disabled = true;
        return;
    }
    const filtered = allFeeders.filter(f => f.ts_code === tsCode);
    sel.innerHTML = '<option value="">-- Select Feeder --</option>';
    filtered.forEach(f => {
        const o = document.createElement('option');
        o.value = f.fdr33kv_code;
        o.textContent = f.fdr33kv_name;
        if (f.fdr33kv_code === selectedCode) o.selected = true;
        sel.appendChild(o);
    });
    sel.disabled = filtered.length === 0;
    handleFeederChange();
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
        fc.required=true;  fc.disabled=false;
        fr.required=true;  fr.disabled=false;
    } else {
        fSec.classList.add('disabled');
        fc.required=false; fc.disabled=true; fc.value='';
        fr.required=false; fr.disabled=true; fr.value='';
    }
    const code    = document.getElementById('fdr33kv_code').value;
    const maxLoad = feederMaxLoad[code];
    err.style.display = (maxLoad!==undefined && loadVal>0 && loadVal>maxLoad) ? 'block' : 'none';
}

function handleFeederChange() {
    const code    = document.getElementById('fdr33kv_code').value;
    const maxLoad = feederMaxLoad[code];
    const hint    = document.getElementById('maxLoadInfo');
    if (maxLoad!==undefined) { hint.textContent='Max allowed load: '+maxLoad.toFixed(2)+' MW'; hint.style.display='block'; }
    else { hint.style.display='none'; }
    handleLoadChange();
}

// ── Open entry modal ──────────────────────────────────────────────────────────
// Called from cell click: openLoadEntryModal(feederCode, hour, cellElement)
// Called from button:     openLoadEntryModal(null, null, null)
function openLoadEntryModal(feederCode, hour, cell) {
    const form = document.getElementById('loadEntryForm');
    form.reset();
    document.getElementById('load_read').value            = 0;
    document.getElementById('maxLoadError').style.display = 'none';
    document.getElementById('maxLoadInfo').style.display  = 'none';
    document.getElementById('is_edit').value              = '0';

    const tsSel     = document.getElementById('modal_ts_code');
    const feederSel = document.getElementById('fdr33kv_code');
    const hourSel   = document.getElementById('entry_hour');

    // hour can be 0 (midnight slot) — do NOT use truthy check on hour
    if (feederCode !== null && feederCode !== undefined && hour !== null && hour !== undefined && !isNaN(parseInt(hour))) {
        // ── Cell click: pre-fill and lock TS, feeder, hour ────────────────────
        // Find the TS for this feeder
        const feederObj = allFeeders.find(f => f.fdr33kv_code === feederCode);
        if (feederObj) {
            // Set TS dropdown value and disable
            tsSel.value    = feederObj.ts_code;
            tsSel.disabled = true;
            // Populate feeder dropdown with just this feeder
            feederSel.innerHTML = '<option value="'+feederCode+'">'+feederObj.fdr33kv_name+'</option>';
            feederSel.value    = feederCode;
            feederSel.disabled = true;
        }

        hourSel.value    = hour;
        hourSel.disabled = true;

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
        // ── Manual button: free selection ─────────────────────────────────────
        tsSel.disabled    = false;
        feederSel.disabled= true;
        feederSel.innerHTML = '<option value="">-- Select Transmission Station First --</option>';
        hourSel.disabled  = false;
        document.getElementById('modalTitle').textContent = '⚡ Add 33kV Load Entry — <?= date('F j, Y', strtotime($today)) ?>';

        // Pre-select current filter TS if active
        const tsFilter = new URLSearchParams(window.location.search).get('ts_code');
        if (tsFilter && tsFilter !== 'all') {
            tsSel.value = tsFilter;
            loadFeedersForTs(tsFilter, null);
        }
    }

    handleFeederChange();
    handleLoadChange();
    document.getElementById('loadEntryModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeLoadEntryModal() {
    document.getElementById('loadEntryModal').style.display = 'none';
    document.getElementById('modal_ts_code').disabled   = false;
    document.getElementById('fdr33kv_code').disabled     = false;
    document.getElementById('entry_hour').disabled       = false;
    document.body.style.overflow = 'auto';
}

// ── TS change event (manual entry only) ──────────────────────────────────────
document.getElementById('modal_ts_code').addEventListener('change', function() {
    // Only run cascade when TS is not locked (i.e. manual entry mode)
    if (!this.disabled) loadFeedersForTs(this.value, null);
});

// ── Form submit: validate → future check → confirm ───────────────────────────
function handleFormSubmit(e) {
    e.preventDefault();

    const loadVal     = parseFloat(document.getElementById('load_read').value)||0;
    const faultCode   = document.getElementById('fault_code').value;
    const faultRemark = document.getElementById('fault_remark').value.trim();
    const feederSel   = document.getElementById('fdr33kv_code');
    const feederCode  = feederSel.value;
    const feederName  = feederSel.options[feederSel.selectedIndex]?.text || feederCode;
    const hour        = parseInt(document.getElementById('entry_hour').value);
    const hourLabel   = String(hour).padStart(2,'0')+':00';
    const isEdit      = document.getElementById('is_edit').value === '1';

    // Required field checks
    if (!feederCode) { showToast('⚠️ Please select a feeder.','error');  return false; }
    if (hour === null || isNaN(hour) || document.getElementById('entry_hour').value === '') { showToast('⚠️ Please select an hour.','error');   return false; }
    if (loadVal===0 && (!faultCode||!faultRemark)) {
        showToast('⚠️ Fault code and remark are required when load is 0.','error'); return false;
    }

    // max_load check
    const maxLoad = feederMaxLoad[feederCode];
    if (maxLoad!==undefined && loadVal>0 && loadVal>maxLoad) {
        showToast('⚠️ Value exceeds maximum allowed for this feeder ('+maxLoad.toFixed(2)+' MW).','error'); return false;
    }

    // ── Future hour: hard client-side block ───────────────────────────────────
    // During 00:xx (slot=0) we're in yesterday's op-date — all slots are past/current
    const slot = getCurrentSlot();
    if (slot >= 1 && hour > slot) {
        showToast('🚫 Hour '+hourLabel+' has not yet occurred. Only current or past hours can be entered.','error');
        return false;
    }

    // Past hours and closed windows handled server-side after confirm.
    const confirmLoad = loadVal > 0
        ? `Confirm you want to ${isEdit?'<strong>update</strong>':'insert'} <strong>${loadVal.toFixed(2)} MW</strong> into Hour <strong>${hourLabel}</strong> for feeder <strong>${feederName}</strong>.`
        : `Confirm you want to ${isEdit?'<strong>update</strong>':'record'} fault <strong>${faultCode}</strong> for Hour <strong>${hourLabel}</strong> on feeder <strong>${feederName}</strong>.<br>Remark: <em>${faultRemark}</em>`;

    document.getElementById('confirmText').innerHTML = confirmLoad;

    pendingFormData = new FormData(document.getElementById('loadEntryForm'));
    pendingFormData.set('fdr33kv_code', feederCode);
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
            btn.disabled  = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Save Entry';

            if (data.success) {
                showToast('✅ '+data.message,'success');
                closeLoadEntryModal();
                updateMatrixCell(
                    pendingFormData.get('fdr33kv_code'),
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
            btn.disabled  = false;
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
    document.getElementById('lateNoticeText').innerHTML =
        '⚠️ Batch: <strong>' + serverData.batch_label + '</strong> &nbsp;|&nbsp; '
        + 'Free entry closed: <strong>' + serverData.deadline + '</strong><br>'
        + 'Select the specific hour you are entering late and provide a reason. '
        + 'Once submitted the entry will be processed automatically.';

    document.getElementById('lateBatchPeriodDisplay').value =
        serverData.batch_label + '  (free deadline: ' + serverData.deadline + ')';

    const hourSel  = document.getElementById('lateSpecificHour');
    const hiddenHr = document.getElementById('lateSpecificHourHidden');
    hourSel.innerHTML = '<option value="">-- Select the specific hour --</option>';
    hiddenHr.value    = '';

    const currentSlot = getCurrentSlot();

    for (let h = serverData.hour_from; h <= serverData.hour_to; h++) {
        // During midnight window (currentSlot=0) every hour in the batch is past — include all
        if (currentSlot >= 1 && h >= currentSlot) continue;

        const opt = document.createElement('option');
        opt.value       = h;
        opt.textContent = String(h).padStart(2, '0') + ':00';

        // Pre-select the hour that was being entered
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
    btn.disabled  = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging…';

    fetch(saveUrl, {method:'POST', body: new FormData(document.getElementById('lateEntryForm'))})
        .then(r => r.json())
        .then(data => {
            btn.disabled  = false;
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
            btn.disabled  = false;
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
    document.getElementById('fdr33kv_code').addEventListener('change', handleFeederChange);
    handleLoadChange();
});

// ── Charts ────────────────────────────────────────────────────────────────────
<?php if (!empty($feeders)): ?>
const hourlyCtx = document.getElementById('hourlyTrendChart');
if (hourlyCtx) {
    const gradient = hourlyCtx.getContext('2d').createLinearGradient(0,0,0,400);
    gradient.addColorStop(0,'rgba(102,126,234,.4)');
    gradient.addColorStop(1,'rgba(118,75,162,.05)');
    new Chart(hourlyCtx.getContext('2d'), {
        type:'line',
        data:{
            labels:[<?php for($i=0;$i<=23;$i++) echo "'".str_pad($i,2,'0',STR_PAD_LEFT).":00',"; ?>],
            datasets:[{label:'Total Load (MW)',
                data:[<?php foreach($hourly_data as $ld) echo "$ld,"; ?>],
                borderColor:'#667eea',backgroundColor:gradient,tension:.4,fill:true,
                pointRadius:4,pointHoverRadius:7,pointBackgroundColor:'#667eea',
                pointBorderColor:'#fff',pointBorderWidth:2}]
        },
        options:{responsive:true,maintainAspectRatio:true,
            plugins:{legend:{display:true,position:'top'},
                tooltip:{backgroundColor:'rgba(0,0,0,.8)',padding:12,cornerRadius:8,
                    callbacks:{label:ctx=>'Load: '+ctx.parsed.y.toFixed(2)+' MW'}}},
            scales:{y:{beginAtZero:true,title:{display:true,text:'Load (MW)',font:{weight:'bold'}},
                grid:{color:'rgba(0,0,0,.05)'}},
                x:{title:{display:true,text:'Hour',font:{weight:'bold'}},grid:{display:false}}}}
    });
}

const pieCtx = document.getElementById('feederPieChart');
if (pieCtx) {
    const fd = <?= json_encode($feeder_totals) ?>;
    const gt = Object.values(fd).reduce((a,b)=>a+b,0);
    const sf = Object.entries(fd).sort((a,b)=>b[1]-a[1]).slice(0,10);
    const colors = ['#667eea','#764ba2','#f093fb','#4facfe','#43e97b','#fa709a','#fee140','#30cfd0','#a8edea','#fed6e3'];
    new Chart(pieCtx.getContext('2d'),{type:'doughnut',
        data:{labels:sf.map(f=>f[0]),datasets:[{data:sf.map(f=>f[1]),backgroundColor:colors,borderWidth:3,borderColor:'white',hoverOffset:15}]},
        options:{responsive:true,maintainAspectRatio:true,
            plugins:{legend:{position:'right',labels:{boxWidth:15,padding:10,font:{size:11},
                generateLabels:chart=>chart.data.labels.map((l,i)=>({
                    text:l+': '+((chart.data.datasets[0].data[i]/gt)*100).toFixed(1)+'%',
                    fillStyle:chart.data.datasets[0].backgroundColor[i],hidden:false,index:i}))}},
                title:{display:true,text:'Top 10 Feeders (% of Total)',font:{size:13,weight:'bold'}},
                tooltip:{callbacks:{label:ctx=>[ctx.label,'Load: '+ctx.parsed.toFixed(2)+' MW','Share: '+((ctx.parsed/gt)*100).toFixed(1)+'%']}}}
        }
    });
}
<?php endif; ?>

const tsPieCtx = document.getElementById('tsPieChart');
if (tsPieCtx) {
    const td = <?= json_encode($ts_totals) ?>;
    const tt = Object.values(td).reduce((a,b)=>a+b,0);
    const tsColors=['#667eea','#764ba2','#f093fb','#4facfe','#43e97b','#fa709a','#fee140','#30cfd0','#a8edea','#fed6e3','#ff6b6b'];
    new Chart(tsPieCtx.getContext('2d'),{type:'doughnut',
        data:{labels:Object.keys(td),datasets:[{data:Object.values(td),backgroundColor:tsColors,borderWidth:3,borderColor:'white',hoverOffset:15}]},
        options:{responsive:true,maintainAspectRatio:true,
            plugins:{legend:{position:'bottom',labels:{boxWidth:15,padding:12,font:{size:11}}},
                title:{display:true,text:'All Transmission Stations — Load Distribution %',font:{size:13,weight:'bold'}},
                tooltip:{callbacks:{label:ctx=>ctx.label+': '+ctx.parsed.toFixed(2)+' MW ('+((ctx.parsed/tt)*100).toFixed(1)+'%)'}}}
        }
    });
}

// ── Operational day auto-reset at exactly 01:00 ──────────────────────────────
// At 01:00 the PHP backend flips to the new operational date.
// This JS schedules a soft blank + page reload to match.
(function scheduleOpDayReset() {
    const now = new Date();
    const h   = now.getHours();
    const m   = now.getMinutes();

    // Target: today at 01:00:05 if we haven't passed it, else tomorrow 01:00:05
    const target = new Date(now);
    target.setSeconds(5, 0);
    target.setMinutes(0);
    target.setHours(1);
    if (h > 1 || (h === 1 && m >= 1)) {
        // Already past 01:00 today — schedule for tomorrow
        target.setDate(target.getDate() + 1);
    }

    const msUntil = target - now;
    if (msUntil <= 0) return;  // safety

    setTimeout(function () {
        // Update header date label immediately
        const lbl = document.getElementById('opDayLabel');
        if (lbl) {
            const d = new Date();
            lbl.textContent = d.toLocaleDateString('en-GB', {
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
            });
        }
        // Blank every matrix cell visually
        document.querySelectorAll('.matrix-cell').forEach(function (cell) {
            cell.className   = 'matrix-cell no-data';
            cell.textContent = '\u2013';          // –
            cell.dataset.load  = '';
            cell.dataset.fault = '';
            cell.title = 'Click to enter / edit';
        });
        // Reload after brief pause so user sees the blank flash
        setTimeout(function () { window.location.reload(); }, 1200);
    }, msUntil);
})();

</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
