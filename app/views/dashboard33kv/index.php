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
            <div style="display:flex;gap:10px;align-items:center;">
                <button class="btn-paste" onclick="openPasteModal()">
                    <i class="fas fa-paste"></i> Paste from Excel
                </button>
                <button class="btn-primary" onclick="openLoadEntryModal(null, null, null)">
                    <i class="fas fa-plus"></i> Add Load Entry
                </button>
            </div>
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
                <span class="matrix-info"><?= count($feeders) ?> feeders × 24 hours &nbsp;|&nbsp; Click any cell to enter / edit &nbsp;|&nbsp; Right-click feeder row to paste</span>
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
                        <tr data-feeder-code="<?= htmlspecialchars($code) ?>"
                            data-feeder-name="<?= htmlspecialchars($feeder['fdr33kv_name']) ?>"
                            oncontextmenu="openPasteModalForFeeder(event, '<?= htmlspecialchars($code, ENT_QUOTES) ?>', '<?= htmlspecialchars($feeder['fdr33kv_name'], ENT_QUOTES) ?>')">
                            <td class="sticky-col feeder-name"><?= htmlspecialchars($feeder['fdr33kv_name']) ?></td>
                            <td class="sticky-col2 location-name"><?= htmlspecialchars($feeder['station_name']) ?></td>
                            <?php for ($h = 0; $h <= 23; $h++):
                                $hour_data = $feeder['hours'][$h];
                                $has_data  = !is_null($hour_data);
                                $load      = $has_data ? floatval($hour_data['load'])    : null;
                                $fault     = $has_data ? trim($hour_data['fault'] ?? '') : '';

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
                <h3>🥧 Load Distribution by Feeder</h3>
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
     MODAL 1 — Load Entry / Edit (single cell)
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

            <div class="form-group">
                <label for="fdr33kv_code"><i class="fas fa-bolt"></i> 33kV Feeder *</label>
                <select name="fdr33kv_code" id="fdr33kv_code" required class="form-control" disabled>
                    <option value="">-- Select Transmission Station First --</option>
                </select>
                <small id="maxLoadInfo" class="max-load-hint" style="display:none;"></small>
            </div>

            <div class="form-group">
                <label for="entry_hour"><i class="fas fa-clock"></i> Hour *</label>
                <select name="entry_hour" id="entry_hour" required class="form-control">
                    <option value="">-- Select Hour --</option>
                    <?php for ($h = 0; $h <= 23; $h++): ?>
                        <option value="<?= $h ?>"><?= str_pad($h, 2, '0', STR_PAD_LEFT) ?>:00</option>
                    <?php endfor; ?>
                </select>
            </div>

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
            <h2>🔔 Confirm Entry</h2>
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
     MODAL 3 — Paste from Excel
     ═══════════════════════════════════════════════════════════════════════════ -->
<div id="pasteModal" class="modal" style="display:none;">
    <div class="modal-content modal-paste">
        <div class="modal-header paste-header">
            <h2><i class="fas fa-paste"></i> Paste Load Data from Excel</h2>
            <span class="close" onclick="closePasteModal()">&times;</span>
        </div>
        <div style="padding:25px;">

            <!-- Instructions -->
            <div class="paste-instructions">
                <strong>📋 How to use:</strong>
                <ol>
                    <li>Select the <strong>Transmission Station</strong> — the modal shows all feeders under it.</li>
                    <li>In your Excel sheet, select rows where <strong>column 1</strong> is the feeder name/code and <strong>columns 2-25</strong> are the 24 hourly values (00:00 → 23:00, or set a Starting Hour below).</li>
                    <li>Click in the paste area and press <strong>Ctrl+V</strong>. Header rows (with hour labels) are auto-detected and skipped.</li>
                    <li>Review the preview grid — each cell is colour-coded — then click <strong>Save All</strong>.</li>
                </ol>
            </div>

            <!-- TS selector -->
            <div class="form-group" style="margin-top:15px;">
                <label><i class="fas fa-building"></i> Transmission Station *</label>
                <select id="paste_ts_code" class="form-control" onchange="loadFeedersForPasteTs(this.value)">
                    <option value="">-- Select Transmission Station --</option>
                    <?php foreach ($transmission_stations as $ts): ?>
                        <option value="<?= htmlspecialchars($ts['ts_code']) ?>">
                            <?= htmlspecialchars($ts['station_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Reference list of feeders under selected TS -->
            <div id="pasteFeederListWrap" class="form-group" style="display:none;">
                <label><i class="fas fa-list"></i> Feeders under this station <small id="pasteFeederCount" style="color:#7f8c8d;font-weight:400;margin-left:8px;"></small></label>
                <div id="pasteFeederList" class="feeder-chips"></div>
                <small>Match these names/codes in your spreadsheet's first column.</small>
            </div>

            <!-- Start hour + Default fault -->
            <div style="display:flex;gap:12px;flex-wrap:wrap;">
                <div class="form-group" style="flex:1;min-width:200px;">
                    <label><i class="fas fa-clock"></i> Starting Hour *
                        <small style="font-weight:400;color:#7f8c8d;margin-left:8px;">First value in each row</small>
                    </label>
                    <select id="paste_start_hour" class="form-control" onchange="reparsePreview()">
                        <?php for ($h = 0; $h <= 23; $h++): ?>
                            <option value="<?= $h ?>"><?= str_pad($h, 2, '0', STR_PAD_LEFT) ?>:00</option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group" style="flex:1;min-width:240px;">
                    <label><i class="fas fa-exclamation-triangle"></i> Default Fault Code for Zero-Load Hours</label>
                    <select id="paste_default_fault" class="form-control" onchange="reparsePreview()">
                        <option value="">-- Skip zero-load hours --</option>
                        <?php foreach ($fault_codes as $fc => $desc): ?>
                            <option value="<?= $fc ?>"><?= $fc ?> – <?= htmlspecialchars($desc) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Paste area -->
            <div class="form-group">
                <label><i class="fas fa-clipboard"></i> Paste Excel Data Here *</label>
                <textarea id="pasteInput"
                          class="form-control paste-textarea"
                          placeholder="Click here, then Ctrl+V — paste one or more rows from Excel, each row = one feeder…"
                          oninput="parsePasteInput()"
                          onpaste="handlePasteEvent(event)"
                          rows="6"></textarea>
                <small>One row per feeder. Column 1 = feeder name or code, columns 2-25 = 24 hourly values.</small>
            </div>

            <!-- Preview table -->
            <div id="pastePreviewWrap" style="display:none;">
                <div class="paste-preview-header">
                    <span id="pastePreviewTitle">Preview</span>
                    <button type="button" class="btn-clear-preview" onclick="clearPasteInput()">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
                <div class="paste-preview-scroll" style="max-height:340px;">
                    <table id="pastePreviewTable" class="paste-preview-table">
                        <thead><tr id="pastePreviewHeadRow"></tr></thead>
                        <tbody id="pastePreviewBody"></tbody>
                    </table>
                </div>
                <div id="pasteWarnings" class="paste-warnings" style="display:none;"></div>
            </div>

            <div class="form-actions" style="margin-top:20px;">
                <button type="button" class="btn-secondary" onclick="closePasteModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="button" id="pasteSaveBtn" class="btn-primary" onclick="submitPasteBatch()" disabled>
                    <i class="fas fa-save"></i> Save All
                </button>
            </div>
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
.btn-paste { background:linear-gradient(135deg,#11998e,#38ef7d); color:white; border:none;
    padding:12px 24px; border-radius:8px; cursor:pointer; font-size:14px; font-weight:600;
    display:inline-flex; align-items:center; gap:8px; transition:all .3s; }
.btn-paste:hover { transform:translateY(-2px); box-shadow:0 5px 15px rgba(17,153,142,.4); }
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
.load-matrix-table tr:hover .sticky-col,
.load-matrix-table tr:hover .sticky-col2 { background:#e8f4fd; }
.load-matrix-table tbody tr:hover { background:#e8f4fd; }

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
    box-shadow:0 10px 40px rgba(0,0,0,.3); animation:slideIn .3s ease; }
.modal-paste { max-width:820px; max-height:88vh; overflow-y:auto; }
.modal-sm { max-width:460px; }
@keyframes slideIn { from{transform:translateY(-40px);opacity:0} to{transform:translateY(0);opacity:1} }
.modal-header { background:linear-gradient(135deg,#667eea,#764ba2); color:white; padding:20px 25px;
    border-radius:12px 12px 0 0; display:flex; justify-content:space-between; align-items:center; }
.modal-header h2 { font-size:18px; font-weight:600; }
.confirm-header { background:linear-gradient(135deg,#2c3e50,#34495e); }
.paste-header  { background:linear-gradient(135deg,#11998e,#38ef7d); }
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

/* Toast */
.toast { position:fixed; bottom:30px; right:30px; z-index:9999; background:#2c3e50; color:white;
    padding:14px 22px; border-radius:10px; font-size:14px; font-weight:600;
    box-shadow:0 4px 20px rgba(0,0,0,.3); animation:toastIn .3s ease; max-width:420px; }
.toast.success { background:linear-gradient(135deg,#27ae60,#2ecc71); }
.toast.error   { background:linear-gradient(135deg,#e74c3c,#c0392b); }
.toast.info    { background:linear-gradient(135deg,#667eea,#764ba2); }
@keyframes toastIn { from{transform:translateX(120%)} to{transform:translateX(0)} }

/* Empty state */
.empty-state { text-align:center; padding:60px 20px; color:#7f8c8d; }
.empty-state i { color:#dee2e6; margin-bottom:20px; }
.empty-state h2 { font-size:24px; margin-bottom:10px; color:#2c3e50; }

/* Paste modal specifics */
.paste-instructions { background:#e8f4fd; border:1px solid #bee3f8; border-radius:8px;
    padding:15px 18px; font-size:13px; color:#2c5282; line-height:1.7; }
.paste-instructions ol { margin:8px 0 0 18px; }
.paste-textarea { font-family:monospace; font-size:13px; resize:vertical; min-height:60px;
    border:2px dashed #667eea; background:#fafbff; }
.paste-textarea:focus { border-style:solid; }
.paste-preview-header { display:flex; justify-content:space-between; align-items:center;
    margin-bottom:8px; }
.paste-preview-header span { font-weight:700; color:#2c3e50; font-size:14px; }
.btn-clear-preview { background:none; border:none; color:#e74c3c; cursor:pointer; font-size:13px;
    font-weight:600; display:flex; align-items:center; gap:5px; padding:4px 8px;
    border-radius:6px; transition:background .2s; }
.btn-clear-preview:hover { background:#fde8ea; }
.paste-preview-scroll { overflow-x:auto; }
.paste-preview-table { width:100%; border-collapse:collapse; font-size:12px; }
.paste-preview-table th { background:linear-gradient(135deg,#667eea,#764ba2); color:white;
    padding:7px 10px; text-align:center; font-weight:600; border:1px solid rgba(255,255,255,.2);
    white-space:nowrap; min-width:50px; }
.paste-preview-table td { padding:7px 10px; border:1px solid #e9ecef; text-align:center;
    font-weight:600; font-family:monospace; }
.paste-cell-ok    { background:#d4edda; color:#155724; }
.paste-cell-fault { background:#fde8ea; color:#c0392b; }
.paste-cell-skip  { background:#f8f9fa; color:#bdc3c7; }
.paste-cell-future { background:#fff3cd; color:#856404; }
.paste-cell-err   { background:#fde8ea; color:#721c24; border:2px solid #f5c6cb !important; }
.paste-warnings { background:#fff3cd; border:1px solid #ffc107; border-radius:8px;
    padding:12px 15px; margin-top:10px; font-size:13px; color:#856404; line-height:1.7; }
.feeder-chips { display:flex; flex-wrap:wrap; gap:6px; max-height:90px; overflow-y:auto;
    padding:8px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; }
.feeder-chips .chip { background:#fff; border:1px solid #cbd5e1; color:#334155;
    padding:3px 10px; border-radius:12px; font-size:11px; font-weight:600; }
.paste-feeder-cell { text-align:left !important; padding:6px 10px !important;
    background:#f8fafc; font-weight:700; min-width:160px; max-width:200px;
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.paste-row-err .paste-feeder-cell { background:#fde8ea; color:#721c24; }
.paste-row-ok  .paste-feeder-cell { background:#e8f5e9; color:#1b5e20; }

/* Responsive */
@media(max-width:768px){
    .main-content { margin-left:0; padding-top:70px; padding:10px; }
    .stats-grid { grid-template-columns:1fr; }
    .charts-row { grid-template-columns:1fr; }
    .modal-content { width:95%; margin:10% auto; }
    .page-header { flex-direction:column; text-align:center; gap:15px; }
}
</style>


<!-- ═══════════════════════════════════════════════════════════════════════════
     SCRIPTS
     ═══════════════════════════════════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// ── Config ──────────────────────────────────────────────────────────────────
const saveUrl = (function() {
    const parts = window.location.pathname.split('/');
    parts.pop();
    return window.location.protocol + '//' + window.location.host + parts.join('/') + '/ajax/33kv_save.php';
})();

const allFeeders = <?= json_encode(array_values($all_feeders)) ?>;

const feederMaxLoad = {};
<?php foreach ($all_feeders as $f): ?>
<?php if (isset($f['max_load']) && $f['max_load'] !== null): ?>
feederMaxLoad[<?= json_encode($f['fdr33kv_code']) ?>] = <?= (float)$f['max_load'] ?>;
<?php endif; ?>
<?php endforeach; ?>

// ── Time helpers ────────────────────────────────────────────────────────────
function getCurrentSlot() { return new Date().getHours(); }

function getOpDate() {
    const now = new Date();
    if (now.getHours() === 0) {
        const y = new Date(now); y.setDate(y.getDate()-1);
        return y.toISOString().slice(0,10);
    }
    return now.toISOString().slice(0,10);
}

// ── State ───────────────────────────────────────────────────────────────────
let pendingFormData = null;

// ── Toast ───────────────────────────────────────────────────────────────────
function showToast(msg, type='') {
    const t = document.getElementById('toast');
    t.innerHTML     = msg;
    t.className     = 'toast ' + type;
    t.style.display = 'block';
    setTimeout(() => { t.style.display = 'none'; }, 6000);
}

// ── TS → Feeder cascade (single-entry modal) ────────────────────────────────
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

// ── Load/fault section toggle (single-entry modal) ──────────────────────────
function handleLoadChange() {
    const loadVal = parseFloat(document.getElementById('load_read').value) || 0;
    const fSec    = document.getElementById('faultSection');
    const fc      = document.getElementById('fault_code');
    const fr      = document.getElementById('fault_remark');
    const err     = document.getElementById('maxLoadError');
    if (loadVal < 0) { document.getElementById('load_read').value = 0; return; }
    if (loadVal === 0) {
        fSec.classList.remove('disabled');
        fc.required = true;  fc.disabled = false;
        fr.required = true;  fr.disabled = false;
    } else {
        fSec.classList.add('disabled');
        fc.required = false; fc.disabled = true; fc.value = '';
        fr.required = false; fr.disabled = true; fr.value = '';
    }
    const code    = document.getElementById('fdr33kv_code').value;
    const maxLoad = feederMaxLoad[code];
    err.style.display = (maxLoad !== undefined && loadVal > 0 && loadVal > maxLoad) ? 'block' : 'none';
}

function handleFeederChange() {
    const code    = document.getElementById('fdr33kv_code').value;
    const maxLoad = feederMaxLoad[code];
    const hint    = document.getElementById('maxLoadInfo');
    if (maxLoad !== undefined) {
        hint.textContent = 'Max allowed load: ' + maxLoad.toFixed(2) + ' MW';
        hint.style.display = 'block';
    } else {
        hint.style.display = 'none';
    }
    handleLoadChange();
}

// ── Open single-cell entry modal ────────────────────────────────────────────
function openLoadEntryModal(feederCode, hour, cell) {
    const form = document.getElementById('loadEntryForm');
    form.reset();
    document.getElementById('load_read').value             = 0;
    document.getElementById('maxLoadError').style.display  = 'none';
    document.getElementById('maxLoadInfo').style.display   = 'none';
    document.getElementById('is_edit').value               = '0';

    const tsSel     = document.getElementById('modal_ts_code');
    const feederSel = document.getElementById('fdr33kv_code');
    const hourSel   = document.getElementById('entry_hour');

    if (feederCode !== null && feederCode !== undefined && hour !== null && hour !== undefined && !isNaN(parseInt(hour))) {
        const feederObj = allFeeders.find(f => f.fdr33kv_code === feederCode);
        if (feederObj) {
            tsSel.value    = feederObj.ts_code;
            tsSel.disabled = true;
            feederSel.innerHTML = '<option value="' + feederCode + '">' + feederObj.fdr33kv_name + '</option>';
            feederSel.value    = feederCode;
            feederSel.disabled = true;
        }
        hourSel.value    = hour;
        hourSel.disabled = true;

        const hasData = cell && (cell.dataset.load !== '' || cell.dataset.fault !== '');
        document.getElementById('is_edit').value = hasData ? '1' : '0';

        if (hasData) {
            if (cell.dataset.load !== '') document.getElementById('load_read').value = cell.dataset.load;
            if (cell.dataset.fault) setTimeout(() => { document.getElementById('fault_code').value = cell.dataset.fault; }, 50);
        }

        const feederName = cell ? cell.dataset.feederName : feederCode;
        const hourLabel  = String(hour).padStart(2, '0') + ':00';
        const editBadge  = hasData ? ' (Editing)' : '';
        document.getElementById('modalTitle').textContent = '⚡ Load Entry — ' + feederName + ' — Hour ' + hourLabel + editBadge;
    } else {
        tsSel.disabled     = false;
        feederSel.disabled = true;
        feederSel.innerHTML = '<option value="">-- Select Transmission Station First --</option>';
        hourSel.disabled   = false;
        document.getElementById('modalTitle').textContent = '⚡ Add 33kV Load Entry — <?= date('F j, Y', strtotime($today)) ?>';

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
    document.getElementById('modal_ts_code').disabled  = false;
    document.getElementById('fdr33kv_code').disabled   = false;
    document.getElementById('entry_hour').disabled     = false;
    document.body.style.overflow = 'auto';
}

document.getElementById('modal_ts_code').addEventListener('change', function() {
    if (!this.disabled) loadFeedersForTs(this.value, null);
});

// ── Single-entry form submit ────────────────────────────────────────────────
function handleFormSubmit(e) {
    e.preventDefault();

    const loadVal     = parseFloat(document.getElementById('load_read').value) || 0;
    const faultCode   = document.getElementById('fault_code').value;
    const faultRemark = document.getElementById('fault_remark').value.trim();
    const feederSel   = document.getElementById('fdr33kv_code');
    const feederCode  = feederSel.value;
    const feederName  = feederSel.options[feederSel.selectedIndex]?.text || feederCode;
    const hour        = parseInt(document.getElementById('entry_hour').value);
    const hourLabel   = String(hour).padStart(2, '0') + ':00';
    const isEdit      = document.getElementById('is_edit').value === '1';

    if (!feederCode) { showToast('⚠️ Please select a feeder.', 'error'); return false; }
    if (isNaN(hour) || document.getElementById('entry_hour').value === '') {
        showToast('⚠️ Please select an hour.', 'error'); return false;
    }
    if (loadVal === 0 && (!faultCode || !faultRemark)) {
        showToast('⚠️ Fault code and remark are required when load is 0.', 'error'); return false;
    }

    const maxLoad = feederMaxLoad[feederCode];
    if (maxLoad !== undefined && loadVal > 0 && loadVal > maxLoad) {
        showToast('⚠️ Value exceeds maximum allowed for this feeder (' + maxLoad.toFixed(2) + ' MW).', 'error'); return false;
    }

    // Future hour block
    const slot = getCurrentSlot();
    if (slot >= 1 && hour > slot) {
        showToast('🚫 Hour ' + hourLabel + ' has not yet occurred.', 'error'); return false;
    }

    const confirmLoad = loadVal > 0
        ? `Confirm ${isEdit ? '<strong>update</strong>' : 'insert'} <strong>${loadVal.toFixed(2)} MW</strong> into Hour <strong>${hourLabel}</strong> for feeder <strong>${feederName}</strong>.`
        : `Confirm ${isEdit ? '<strong>update</strong>' : 'record'} fault <strong>${faultCode}</strong> for Hour <strong>${hourLabel}</strong> on feeder <strong>${feederName}</strong>.<br>Remark: <em>${faultRemark}</em>`;

    document.getElementById('confirmText').innerHTML = confirmLoad;

    pendingFormData = new FormData(document.getElementById('loadEntryForm'));
    pendingFormData.set('fdr33kv_code', feederCode);
    pendingFormData.set('entry_hour',   hour);
    pendingFormData.set('is_edit',      isEdit ? '1' : '0');

    document.getElementById('confirmModal').style.display = 'block';
    return false;
}

function closeConfirmModal() { document.getElementById('confirmModal').style.display = 'none'; }

// ── AJAX single save ────────────────────────────────────────────────────────
function doSave() {
    closeConfirmModal();
    const btn = document.getElementById('submitBtn');
    btn.disabled  = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

    fetch(saveUrl, { method: 'POST', body: pendingFormData })
        .then(r => r.json())
        .then(data => {
            btn.disabled  = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Save Entry';

            if (data.success) {
                showToast('✓ ' + data.message, 'success');
                closeLoadEntryModal();
                updateMatrixCell(
                    pendingFormData.get('fdr33kv_code'),
                    parseInt(pendingFormData.get('entry_hour')),
                    parseFloat(pendingFormData.get('load_read') || 0),
                    pendingFormData.get('fault_code') || ''
                );
                pendingFormData = null;
            } else if (data.future) {
                showToast('🚫 ' + data.message, 'error');
            } else {
                showToast('✗ ' + data.message, 'error');
            }
        })
        .catch(() => {
            btn.disabled  = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Save Entry';
            showToast('✗ Network error. Please try again.', 'error');
        });
}

// ── Matrix cell DOM update ──────────────────────────────────────────────────
function updateMatrixCell(feederCode, hour, load, fault) {
    const cell = document.querySelector(`.matrix-cell[data-feeder-code="${feederCode}"][data-hour="${hour}"]`);
    if (!cell) return;
    cell.dataset.load  = load;
    cell.dataset.fault = fault;
    if (load > 0) {
        cell.className = 'matrix-cell has-load';
        cell.textContent = load.toFixed(2);
        cell.title = '';
    } else if (fault) {
        cell.className = 'matrix-cell has-fault';
        cell.textContent = fault.substring(0, 8).toUpperCase();
        cell.title = 'Fault: ' + fault;
    } else {
        cell.className = 'matrix-cell no-data';
        cell.textContent = '–';
        cell.title = 'Click to enter / edit';
    }
}


// ────────────────────────────────────────────────────────────────────────────
// PASTE / BATCH ENTRY ENGINE — MULTI-FEEDER
// ────────────────────────────────────────────────────────────────────────────
//
// Workflow: user picks TS, the modal shows all feeders under that TS. The
// user pastes ALL their rows from Excel (one row per feeder, column 1 =
// feeder identifier, columns 2-25 = 24 hourly values). The parser matches
// each row's column 1 against the TS's feeders (by code or by name,
// case-insensitive) and renders a grid preview. Save All sends every valid
// (feeder, hour, load) cell to /ajax/33kv_save.php in a single request.
// ────────────────────────────────────────────────────────────────────────────

let pasteEntries = [];      // flat array of {fdr33kv_code, hour, load_read, fault_code, fault_remark, skip}
let pasteRowSummary = [];   // [{feederToken, fdr33kv_code, feeder_name, status, validCount, errors[]}]
let pasteTsFeederIndex = {};

function openPasteModal() {
    resetPasteModal();
    document.getElementById('pasteModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('pasteInput').focus(), 200);
}

function openPasteModalForFeeder(event, feederCode, feederName) {
    event.preventDefault();
    resetPasteModal();
    const feederObj = allFeeders.find(f => f.fdr33kv_code === feederCode);
    if (feederObj) {
        document.getElementById('paste_ts_code').value = feederObj.ts_code;
        loadFeedersForPasteTs(feederObj.ts_code);
    }
    document.getElementById('pasteModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
    setTimeout(() => document.getElementById('pasteInput').focus(), 200);
}

function closePasteModal() {
    document.getElementById('pasteModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function resetPasteModal() {
    document.getElementById('paste_ts_code').value       = '';
    document.getElementById('paste_start_hour').value    = '0';
    document.getElementById('paste_default_fault').value = '';
    document.getElementById('pasteFeederListWrap').style.display = 'none';
    pasteTsFeederIndex = {};
    clearPasteInput();
}

// When TS changes, build the chip list AND the feeder lookup index
function loadFeedersForPasteTs(tsCode) {
    const wrap = document.getElementById('pasteFeederListWrap');
    const list = document.getElementById('pasteFeederList');
    const cnt  = document.getElementById('pasteFeederCount');

    pasteTsFeederIndex = {};
    list.innerHTML = '';

    if (!tsCode) {
        wrap.style.display = 'none';
        clearPasteInput();
        return;
    }
    const feeders = allFeeders.filter(f => f.ts_code === tsCode);
    feeders.forEach(f => {
        // Index by code and by name (lower-cased + collapsed whitespace) for matching
        const codeKey = String(f.fdr33kv_code).trim().toLowerCase();
        const nameKey = String(f.fdr33kv_name).trim().toLowerCase().replace(/\s+/g, ' ');
        pasteTsFeederIndex[codeKey] = f;
        pasteTsFeederIndex[nameKey] = f;

        const chip = document.createElement('span');
        chip.className   = 'chip';
        chip.textContent = f.fdr33kv_name;
        chip.title       = 'Code: ' + f.fdr33kv_code + ' • Max: ' + (f.max_load || '—') + ' MW';
        list.appendChild(chip);
    });
    cnt.textContent = '(' + feeders.length + ')';
    wrap.style.display = feeders.length ? 'block' : 'none';

    reparsePreview();
}

function _lookupFeederInTs(token) {
    const t = String(token || '').trim().toLowerCase().replace(/\s+/g, ' ');
    return pasteTsFeederIndex[t] || null;
}

function handlePasteEvent(event) {
    event.preventDefault();
    const text = (event.clipboardData || window.clipboardData).getData('text');
    document.getElementById('pasteInput').value = text;
    parsePasteInput();
}

function clearPasteInput() {
    document.getElementById('pasteInput').value = '';
    document.getElementById('pastePreviewWrap').style.display = 'none';
    document.getElementById('pasteSaveBtn').disabled = true;
    pasteEntries = [];
    pasteRowSummary = [];
}

function reparsePreview() {
    if (document.getElementById('pasteInput').value.trim()) parsePasteInput();
}

/**
 * Multi-row Excel paste parser.
 * Each input row: col 1 = feeder identifier, cols 2-25 = 24 hour values.
 * Header rows (e.g. "Feeder | 00:00 | 01:00 | ...") are auto-detected and skipped.
 */
function parsePasteInput() {
    const raw       = document.getElementById('pasteInput').value;
    const tsCode    = document.getElementById('paste_ts_code').value;
    const startHour = parseInt(document.getElementById('paste_start_hour').value) || 0;
    const defFault  = document.getElementById('paste_default_fault').value;
    const clockSlot = getCurrentSlot();

    pasteEntries     = [];
    pasteRowSummary  = [];
    const warnings   = [];

    if (!tsCode) {
        document.getElementById('pastePreviewWrap').style.display = 'none';
        document.getElementById('pasteSaveBtn').disabled = true;
        if (raw.trim()) showToast('⚠️ Select a Transmission Station first.', 'error');
        return;
    }

    const lines = raw.split(/\r?\n/).map(r => r.replace(/\s+$/,'')).filter(r => r.trim().length > 0);
    if (lines.length === 0) {
        clearPasteInput();
        return;
    }

    lines.forEach((line, lineIdx) => {
        const cells = line.split('\t').map(c => c.trim());
        if (cells.length < 2) return;   // not a usable row

        // Header-row autodetect: skip rows where col 1 doesn't look like a feeder
        // AND col 2 looks like an hour label (e.g. "00:00", "0", "Hour 0")
        if (lineIdx === 0 && !_lookupFeederInTs(cells[0])) {
            const c2 = (cells[1] || '').toLowerCase();
            const looksLikeHour = /^h?\d{1,2}([:.]?\d{0,2})?(:00|h|hour)?$/i.test(cells[1]) || c2.includes('hour') || c2.includes(':00');
            if (looksLikeHour) return;  // skip header
        }

        const feederToken = cells[0];
        const feeder      = _lookupFeederInTs(feederToken);
        const summary = {
            line:          lineIdx + 1,
            feederToken,
            fdr33kv_code:  feeder ? feeder.fdr33kv_code : '',
            feeder_name:   feeder ? feeder.fdr33kv_name : '(not in this TS)',
            max_load:      feeder ? (feeder.max_load || null) : null,
            cells:         [],
            validCount:    0,
            errors:        [],
        };

        if (!feeder) {
            summary.errors.push('Feeder "' + feederToken + '" is not under the selected TS.');
            warnings.push('Row ' + (lineIdx + 1) + ': feeder "' + feederToken + '" not in this TS — entire row skipped.');
        }

        const valueCells = cells.slice(1).map(c => c.replace(',', '.'));   // comma-decimal -> dot
        for (let i = 0; i < valueCells.length; i++) {
            const hour   = startHour + i;
            const rawVal = valueCells[i];

            if (hour > 23) break;
            if (rawVal === '') {
                summary.cells.push({ hour, display: '', cls: 'paste-cell-skip', skip: true });
                continue;
            }

            // Future hour
            if (clockSlot >= 1 && hour > clockSlot) {
                summary.cells.push({ hour, display: rawVal + ' ⏳', cls: 'paste-cell-future', skip: true });
                continue;
            }

            const num = parseFloat(rawVal);
            const isNumeric = !isNaN(num) && rawVal !== '';

            if (!isNumeric) {
                summary.cells.push({ hour, display: '"' + rawVal + '"', cls: 'paste-cell-err', skip: true });
                summary.errors.push(String(hour).padStart(2,'0') + ':00 → non-numeric "' + rawVal + '"');
                continue;
            }
            if (num < 0) {
                summary.cells.push({ hour, display: num + ' ✗', cls: 'paste-cell-err', skip: true });
                summary.errors.push(String(hour).padStart(2,'0') + ':00 → negative');
                continue;
            }

            // Zero load — need fault code
            if (num === 0) {
                if (!defFault) {
                    summary.cells.push({ hour, display: '0', cls: 'paste-cell-skip', skip: true });
                    continue;
                }
                summary.cells.push({ hour, display: defFault, cls: 'paste-cell-fault', skip: false });
                if (feeder) {
                    pasteEntries.push({
                        fdr33kv_code: feeder.fdr33kv_code, hour,
                        load_read: 0, fault_code: defFault, fault_remark: 'Batch paste',
                    });
                    summary.validCount++;
                }
                continue;
            }

            // max_load check
            if (summary.max_load !== null && num > summary.max_load) {
                summary.cells.push({ hour, display: num.toFixed(2) + ' ⚠️', cls: 'paste-cell-err', skip: true });
                summary.errors.push(String(hour).padStart(2,'0') + ':00 → ' + num.toFixed(2) + ' MW > max ' + summary.max_load + ' MW');
                continue;
            }

            // Good value
            summary.cells.push({ hour, display: num.toFixed(2), cls: 'paste-cell-ok', skip: false });
            if (feeder) {
                pasteEntries.push({
                    fdr33kv_code: feeder.fdr33kv_code, hour,
                    load_read: num, fault_code: '', fault_remark: '',
                });
                summary.validCount++;
            }
        }

        pasteRowSummary.push(summary);
    });

    _renderPastePreviewGrid(pasteRowSummary, startHour);

    if (warnings.length) {
        const wb = document.getElementById('pasteWarnings');
        wb.innerHTML = '<strong>⚠️ Notes:</strong><br>' + warnings.join('<br>');
        wb.style.display = 'block';
    } else {
        document.getElementById('pasteWarnings').style.display = 'none';
    }

    const totalValid = pasteEntries.length;
    const totalCells = pasteRowSummary.reduce((s, r) => s + r.cells.length, 0);
    document.getElementById('pastePreviewTitle').textContent =
        'Preview — ' + pasteRowSummary.length + ' row(s) parsed, ' +
        totalValid + ' / ' + totalCells + ' cells will be saved';
    document.getElementById('pastePreviewWrap').style.display = 'block';
    document.getElementById('pasteSaveBtn').disabled = (totalValid === 0);
}

function _renderPastePreviewGrid(rows, startHour) {
    const head = document.getElementById('pastePreviewHeadRow');
    const body = document.getElementById('pastePreviewBody');
    head.innerHTML = '';
    body.innerHTML = '';

    // Determine max columns across rows
    const maxCells = rows.reduce((m, r) => Math.max(m, r.cells.length), 0);

    // Header
    const thFeeder = document.createElement('th');
    thFeeder.textContent = 'Feeder';
    thFeeder.className   = 'paste-feeder-cell';
    head.appendChild(thFeeder);
    for (let i = 0; i < maxCells; i++) {
        const h = startHour + i;
        const th = document.createElement('th');
        th.textContent = String(h).padStart(2,'0') + ':00';
        head.appendChild(th);
    }
    const thStat = document.createElement('th');
    thStat.textContent = 'Status';
    head.appendChild(thStat);

    // Body
    rows.forEach(r => {
        const tr = document.createElement('tr');
        const isErr = !r.fdr33kv_code;
        tr.className = isErr ? 'paste-row-err' : (r.validCount > 0 ? 'paste-row-ok' : '');

        const tdF = document.createElement('td');
        tdF.className   = 'paste-feeder-cell';
        tdF.textContent = r.feederToken + (r.feeder_name && r.feeder_name !== r.feederToken ? ' (' + r.feeder_name + ')' : '');
        tdF.title       = r.fdr33kv_code ? ('Code: ' + r.fdr33kv_code) : r.errors.join(' | ');
        tr.appendChild(tdF);

        for (let i = 0; i < maxCells; i++) {
            const c = r.cells[i];
            const td = document.createElement('td');
            if (!c) { td.className = 'paste-cell-skip'; td.textContent = ''; }
            else    { td.className = c.cls;             td.textContent = c.display; }
            tr.appendChild(td);
        }

        const tdS = document.createElement('td');
        tdS.style.fontSize  = '11px';
        tdS.style.textAlign = 'left';
        if (!r.fdr33kv_code) {
            tdS.innerHTML = '<span style="color:#c0392b;font-weight:700;">✗ Unknown feeder</span>';
        } else if (r.validCount === 0) {
            tdS.innerHTML = '<span style="color:#856404;font-weight:700;">— No valid cells</span>';
        } else {
            tdS.innerHTML = '<span style="color:#1b5e20;font-weight:700;">✓ ' + r.validCount + ' to save</span>';
        }
        tr.appendChild(tdS);
        body.appendChild(tr);
    });
}

// ── Submit batch ────────────────────────────────────────────────────────────
function submitPasteBatch() {
    if (pasteEntries.length === 0) { showToast('⚠️ Nothing to save.', 'error'); return; }

    const btn = document.getElementById('pasteSaveBtn');
    btn.disabled  = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving ' + pasteEntries.length + ' entries…';

    const formData = new FormData();
    formData.set('action',  'save_batch');
    formData.set('entries', JSON.stringify(pasteEntries));

    fetch(saveUrl, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            btn.disabled  = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Save All';

            if (data.success) {
                showToast('✓ ' + (data.message || (data.saved + ' saved')), 'success');
                // Refresh saved cells in the matrix DOM
                pasteEntries.forEach(e => {
                    updateMatrixCell(e.fdr33kv_code, e.hour, e.load_read || 0, e.fault_code || '');
                });
                closePasteModal();
                if (data.skipped > 0 && data.errors && data.errors.length > 0) {
                    setTimeout(() => {
                        showToast('⚠️ ' + data.skipped + ' skipped:<br><small>' + data.errors.slice(0,3).join('<br>') + '</small>', 'error');
                    }, 1500);
                }
            } else {
                showToast('✗ ' + (data.message || 'Save failed'), 'error');
            }
        })
        .catch(() => {
            btn.disabled  = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Save All';
            showToast('✗ Network error. Please try again.', 'error');
        });
}

// ── Right-click → paste modal: suppress default context menu on matrix rows ─
document.addEventListener('contextmenu', function(e) {
    const row = e.target.closest('tr[data-feeder-code]');
    if (row) e.preventDefault();
});

// ── Keyboard / backdrop close ───────────────────────────────────────────────
window.onclick = function(e) {
    ['loadEntryModal', 'confirmModal', 'pasteModal'].forEach(id => {
        if (e.target === document.getElementById(id)) {
            document.getElementById(id).style.display = 'none';
            document.body.style.overflow = 'auto';
        }
    });
};
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeLoadEntryModal();
        closeConfirmModal();
        closePasteModal();
    }
});
document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('fdr33kv_code').addEventListener('change', handleFeederChange);
    handleLoadChange();
});

// ── Charts ──────────────────────────────────────────────────────────────────
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
                tooltip:{callbacks:{label:ctx=>[ctx.label,'Load: '+ctx.parsed.toFixed(2)+' MW','Share: '+((ctx.parsed/gt)*100).toFixed(1)+'%']}}}}
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
                tooltip:{callbacks:{label:ctx=>ctx.label+': '+ctx.parsed.toFixed(2)+' MW ('+((ctx.parsed/tt)*100).toFixed(1)+'%)'}}}}}
    );
}

// ── Operational day auto-reset at exactly 01:00 ─────────────────────────────
(function scheduleOpDayReset() {
    const now = new Date();
    const h   = now.getHours();
    const m   = now.getMinutes();
    const target = new Date(now);
    target.setSeconds(5, 0);
    target.setMinutes(0);
    target.setHours(1);
    if (h > 1 || (h === 1 && m >= 1)) { target.setDate(target.getDate() + 1); }
    const msUntil = target - now;
    if (msUntil <= 0) return;
    setTimeout(function () {
        const lbl = document.getElementById('opDayLabel');
        if (lbl) {
            lbl.textContent = new Date().toLocaleDateString('en-GB', {
                weekday:'long', year:'numeric', month:'long', day:'numeric'
            });
        }
        document.querySelectorAll('.matrix-cell').forEach(function (cell) {
            cell.className   = 'matrix-cell no-data';
            cell.textContent = '–';
            cell.dataset.load  = '';
            cell.dataset.fault = '';
            cell.title = 'Click to enter / edit';
        });
        setTimeout(function () { window.location.reload(); }, 1200);
    }, msUntil);
})();

</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
