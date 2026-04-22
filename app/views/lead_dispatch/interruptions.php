<?php
/**
 * Interruptions Monitor View - COMPLETE WITH TABS
 * Path: /app/views/lead_dispatch/interruptions.php
 * 
 * Uses: interruptions_11kv and interruptions (33kV) tables
 * Features: Tabs, hourly matrix, statistics, filters
 */

// Get active tab (default to 11kV)
$active_tab = $_GET['tab'] ?? '11kv';

// Get filter parameters
$selected_iss = $_GET['iss'] ?? 'all';
$selected_ts = $_GET['ts'] ?? 'all';
$selected_type = $_GET['type'] ?? 'all';

// ========== 11kV INTERRUPTIONS DATA ==========
if ($active_tab === '11kv') {
    // Get ISS list for dropdown
    $stmt_iss_list = $db->query("SELECT iss_code, iss_name FROM iss_locations ORDER BY iss_name");
    $iss_list = $stmt_iss_list->fetchAll(PDO::FETCH_ASSOC);
    
    // Get interruption types for dropdown
    $stmt_types = $db->query("
        SELECT DISTINCT interruption_type 
        FROM interruptions_11kv 
        WHERE interruption_type IS NOT NULL 
        ORDER BY interruption_type
    ");
    $interruption_types = $stmt_types->fetchAll(PDO::FETCH_COLUMN);
    
    // Build WHERE clause for filters
    $where_conditions = ["DATE(i.datetime_out) = ?"];
    $params = [$selected_date];
    
    if ($selected_iss !== 'all') {
        $where_conditions[] = "f.iss_code = ?";
        $params[] = $selected_iss;
    }
    
    if ($selected_type !== 'all') {
        $where_conditions[] = "i.interruption_type = ?";
        $params[] = $selected_type;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Get all 11kV interruptions for selected date with filters
    $stmt_11kv = $db->prepare("
        SELECT 
            i.*,
            f.fdr11kv_name,
            f.iss_code,
            iss.iss_name,
            ao.ao_name,
            ic.interruption_description,
            ic.body_responsible,
            HOUR(i.datetime_out) as out_hour,
            HOUR(i.datetime_in) as in_hour
        FROM interruptions_11kv i
        INNER JOIN fdr11kv f ON f.fdr11kv_code = i.fdr11kv_code
        INNER JOIN iss_locations iss ON iss.iss_code = f.iss_code
        LEFT JOIN area_offices ao ON ao.ao_id = f.ao_code
        LEFT JOIN interruption_codes ic ON ic.interruption_code = i.interruption_code
        WHERE {$where_clause}
        ORDER BY i.datetime_out DESC
    ");
    $stmt_11kv->execute($params);
    $interruptions_data = $stmt_11kv->fetchAll(PDO::FETCH_ASSOC);
    
    // Build hourly matrix (24 hours)
    $hourly_matrix = array_fill(1, 24, [
        'count' => 0,
        'total_duration' => 0,
        'total_load_loss' => 0,
        'interruptions' => []
    ]);
    
    foreach ($interruptions_data as $int) {
        $out_hour = (int)$int['out_hour'] ?: 1;
        if ($out_hour >= 1 && $out_hour <= 24) {
            $hourly_matrix[$out_hour]['count']++;
            $hourly_matrix[$out_hour]['total_duration'] += $int['duration'];
            $hourly_matrix[$out_hour]['total_load_loss'] += $int['load_loss'];
            $hourly_matrix[$out_hour]['interruptions'][] = $int;
        }
    }
    
    // Calculate statistics
    $stats = [
        'total_interruptions' => count($interruptions_data),
        'total_load_loss' => array_sum(array_column($interruptions_data, 'load_loss')),
        'total_duration' => array_sum(array_column($interruptions_data, 'duration')),
        'avg_duration' => count($interruptions_data) > 0 ? array_sum(array_column($interruptions_data, 'duration')) / count($interruptions_data) : 0,
        'unique_feeders' => count(array_unique(array_column($interruptions_data, 'fdr11kv_code'))),
        'pending_approvals' => count(array_filter($interruptions_data, fn($i) => $i['approval_status'] === 'PENDING')),
        'approved' => count(array_filter($interruptions_data, fn($i) => $i['approval_status'] === 'APPROVED')),
        'rejected' => count(array_filter($interruptions_data, fn($i) => $i['approval_status'] === 'REJECTED'))
    ];
    
    // Group by interruption type
    $by_type = [];
    foreach ($interruptions_data as $int) {
        $type = $int['interruption_type'] ?? 'Unknown';
        if (!isset($by_type[$type])) {
            $by_type[$type] = ['count' => 0, 'duration' => 0, 'load_loss' => 0];
        }
        $by_type[$type]['count']++;
        $by_type[$type]['duration'] += $int['duration'];
        $by_type[$type]['load_loss'] += $int['load_loss'];
    }
    arsort($by_type);
    
    // Group by ISS
    $by_iss = [];
    foreach ($interruptions_data as $int) {
        $iss = $int['iss_name'];
        if (!isset($by_iss[$iss])) {
            $by_iss[$iss] = ['count' => 0, 'duration' => 0, 'load_loss' => 0];
        }
        $by_iss[$iss]['count']++;
        $by_iss[$iss]['duration'] += $int['duration'];
        $by_iss[$iss]['load_loss'] += $int['load_loss'];
    }
    arsort($by_iss);
}

// ========== 33kV INTERRUPTIONS DATA ==========
if ($active_tab === '33kv') {
    // Get TS list for dropdown
    $stmt_ts_list = $db->query("SELECT ts_code, station_name FROM transmission_stations ORDER BY station_name");
    $ts_list = $stmt_ts_list->fetchAll(PDO::FETCH_ASSOC);
    
    // Get interruption types for dropdown
    $stmt_types = $db->query("
        SELECT DISTINCT interruption_type 
        FROM interruptions 
        WHERE interruption_type IS NOT NULL 
        ORDER BY interruption_type
    ");
    $interruption_types = $stmt_types->fetchAll(PDO::FETCH_COLUMN);
    
    // Build WHERE clause for filters
    $where_conditions = ["DATE(i.datetime_out) = ?"];
    $params = [$selected_date];
    
    if ($selected_ts !== 'all') {
        $where_conditions[] = "f.ts_code = ?";
        $params[] = $selected_ts;
    }
    
    if ($selected_type !== 'all') {
        $where_conditions[] = "i.interruption_type = ?";
        $params[] = $selected_type;
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Get all 33kV interruptions for selected date with filters
    $stmt_33kv = $db->prepare("
        SELECT 
            i.*,
            f.fdr33kv_name,
            f.ts_code,
            ts.station_name,
            ic.interruption_description,
            ic.body_responsible,
            HOUR(i.datetime_out) as out_hour,
            HOUR(i.datetime_in) as in_hour
        FROM interruptions i
        INNER JOIN fdr33kv f ON f.fdr33kv_code = i.fdr33kv_code
        INNER JOIN transmission_stations ts ON ts.ts_code = f.ts_code
        LEFT JOIN interruption_codes ic ON ic.interruption_code = i.interruption_code
        WHERE {$where_clause}
        ORDER BY i.datetime_out DESC
    ");
    $stmt_33kv->execute($params);
    $interruptions_data = $stmt_33kv->fetchAll(PDO::FETCH_ASSOC);
    
    // Build hourly matrix (24 hours)
    $hourly_matrix = array_fill(1, 24, [
        'count' => 0,
        'total_duration' => 0,
        'total_load_loss' => 0,
        'interruptions' => []
    ]);
    
    foreach ($interruptions_data as $int) {
        $out_hour = (int)$int['out_hour'] ?: 1;
        if ($out_hour >= 1 && $out_hour <= 24) {
            $hourly_matrix[$out_hour]['count']++;
            $hourly_matrix[$out_hour]['total_duration'] += $int['duration'];
            $hourly_matrix[$out_hour]['total_load_loss'] += $int['load_loss'];
            $hourly_matrix[$out_hour]['interruptions'][] = $int;
        }
    }
    
    // Calculate statistics
    $stats = [
        'total_interruptions' => count($interruptions_data),
        'total_load_loss' => array_sum(array_column($interruptions_data, 'load_loss')),
        'total_duration' => array_sum(array_column($interruptions_data, 'duration')),
        'avg_duration' => count($interruptions_data) > 0 ? array_sum(array_column($interruptions_data, 'duration')) / count($interruptions_data) : 0,
        'unique_feeders' => count(array_unique(array_column($interruptions_data, 'fdr33kv_code'))),
        'pending_approvals' => count(array_filter($interruptions_data, fn($i) => $i['approval_status'] === 'PENDING')),
        'approved' => count(array_filter($interruptions_data, fn($i) => $i['approval_status'] === 'APPROVED')),
        'rejected' => count(array_filter($interruptions_data, fn($i) => $i['approval_status'] === 'REJECTED'))
    ];
    
    // Group by interruption type
    $by_type = [];
    foreach ($interruptions_data as $int) {
        $type = $int['interruption_type'] ?? 'Unknown';
        if (!isset($by_type[$type])) {
            $by_type[$type] = ['count' => 0, 'duration' => 0, 'load_loss' => 0];
        }
        $by_type[$type]['count']++;
        $by_type[$type]['duration'] += $int['duration'];
        $by_type[$type]['load_loss'] += $int['load_loss'];
    }
    arsort($by_type);
    
    // Group by TS
    $by_ts = [];
    foreach ($interruptions_data as $int) {
        $ts = $int['station_name'];
        if (!isset($by_ts[$ts])) {
            $by_ts[$ts] = ['count' => 0, 'duration' => 0, 'load_loss' => 0];
        }
        $by_ts[$ts]['count']++;
        $by_ts[$ts]['duration'] += $int['duration'];
        $by_ts[$ts]['load_loss'] += $int['load_loss'];
    }
    arsort($by_ts);
}

require __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/sidebar.php';
?>

<style>
.main-content {
    margin-left: 260px;
    padding: 20px;
    padding-top: 84px;
    background: #f4f6fa;
    min-height: 100vh;
}

.page-header {
    background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 20px;
    color: white;
    box-shadow: 0 4px 12px rgba(235, 51, 73, 0.3);
}

.page-title {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.filters-row {
    display: flex;
    gap: 16px;
    align-items: center;
    margin-top: 12px;
    flex-wrap: wrap;
}

.filter-group {
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-group label {
    font-size: 13px;
    font-weight: 600;
}

.filter-group select,
.filter-group input[type="date"] {
    padding: 6px 12px;
    border: 2px solid rgba(255,255,255,0.3);
    border-radius: 6px;
    background: rgba(255,255,255,0.2);
    color: white;
    font-weight: 600;
    min-width: 180px;
}

.filter-group select option {
    background: #1e293b;
    color: white;
}

/* Tabs */
.tabs-container {
    background: white;
    border-radius: 12px 12px 0 0;
    padding: 0;
    margin-bottom: 0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.tabs {
    display: flex;
    border-bottom: 2px solid #e2e8f0;
}

.tab {
    flex: 1;
    padding: 16px 24px;
    text-align: center;
    cursor: pointer;
    font-weight: 600;
    color: #64748b;
    transition: all 0.3s ease;
    text-decoration: none;
    border-bottom: 3px solid transparent;
}

.tab:hover {
    background: #f8fafc;
    color: #1e293b;
}

.tab.active {
    color: #eb3349;
    border-bottom-color: #eb3349;
    background: #fff;
}

/* Statistics Cards */
.stats-bar {
    display: flex;
    gap: 16px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.stat-box {
    background: white;
    padding: 16px;
    border-radius: 8px;
    flex: 1;
    min-width: 150px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.stat-value {
    font-size: 24px;
    font-weight: 700;
    color: #1e293b;
}

.stat-label {
    font-size: 12px;
    color: #64748b;
    text-transform: uppercase;
    margin-top: 4px;
}

/* Hourly Matrix */
.matrix-section {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.section-title {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 16px;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 8px;
}

.hourly-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 8px;
}

.hour-cell {
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    padding: 12px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
}

.hour-cell:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.hour-cell.has-interruptions {
    background: #fee2e2;
    border-color: #f87171;
}

.hour-cell.has-interruptions:hover {
    background: #fecaca;
}

.hour-label {
    font-size: 11px;
    color: #64748b;
    font-weight: 600;
    margin-bottom: 4px;
}

.hour-count {
    font-size: 20px;
    font-weight: 700;
    color: #1e293b;
}

.hour-duration {
    font-size: 10px;
    color: #64748b;
    margin-top: 2px;
}

/* Content Grid */
.content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}

.card-header {
    font-size: 16px;
    font-weight: 700;
    margin-bottom: 16px;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 8px;
    padding-bottom: 12px;
    border-bottom: 2px solid #f1f5f9;
}

/* Tables */
.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table thead th {
    background: #f8fafc;
    padding: 10px;
    text-align: left;
    font-size: 12px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    border-bottom: 2px solid #e2e8f0;
}

.data-table tbody td {
    padding: 10px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 14px;
}

.data-table tbody tr:hover {
    background: #f8fafc;
}

/* Badges */
.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
}

.badge-pending { background: #fef3c7; color: #92400e; }
.badge-approved { background: #dcfce7; color: #166534; }
.badge-rejected { background: #fee2e2; color: #991b1b; }
.badge-not-required { background: #f1f5f9; color: #64748b; }

/* Type Stats */
.type-stat {
    padding: 12px;
    background: #f8fafc;
    border-radius: 8px;
    margin-bottom: 8px;
}

.type-stat-header {
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 4px;
}

.type-stat-details {
    font-size: 11px;
    color: #64748b;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #94a3b8;
}

.empty-state i {
    font-size: 64px;
    margin-bottom: 16px;
    opacity: 0.3;
}

@media (max-width: 1024px) {
    .content-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        padding: 12px;
        padding-top: 76px;
    }
    
    .hourly-grid {
        grid-template-columns: repeat(auto-fill, minmax(60px, 1fr));
    }
}
</style>

<div class="main-content">
    <div class="page-header">
        <div class="page-title">
            <i class="fas fa-exclamation-triangle"></i>
            Interruptions Monitor
        </div>
        <div class="filters-row">
            <div class="filter-group">
                <i class="fas fa-calendar"></i>
                <input type="date" 
                       value="<?= htmlspecialchars($selected_date) ?>" 
                       max="<?= date('Y-m-d') ?>"
                       onchange="window.location.href='?page=dashboard&action=interruptions&tab=<?= $active_tab ?>&date=' + this.value + '<?= $active_tab === '11kv' ? '&iss=' . urlencode($selected_iss) : '&ts=' . urlencode($selected_ts) ?>&type=<?= urlencode($selected_type) ?>'">
            </div>
            
            <?php if ($active_tab === '11kv'): ?>
                <div class="filter-group">
                    <i class="fas fa-filter"></i>
                    <label>ISS:</label>
                    <select onchange="window.location.href='?page=dashboard&action=interruptions&tab=11kv&date=<?= urlencode($selected_date) ?>&iss=' + this.value + '&type=<?= urlencode($selected_type) ?>'">
                        <option value="all" <?= $selected_iss === 'all' ? 'selected' : '' ?>>All ISS</option>
                        <?php foreach ($iss_list as $iss): ?>
                            <option value="<?= htmlspecialchars($iss['iss_code']) ?>" <?= $selected_iss === $iss['iss_code'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($iss['iss_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else: ?>
                <div class="filter-group">
                    <i class="fas fa-filter"></i>
                    <label>TS:</label>
                    <select onchange="window.location.href='?page=dashboard&action=interruptions&tab=33kv&date=<?= urlencode($selected_date) ?>&ts=' + this.value + '&type=<?= urlencode($selected_type) ?>'">
                        <option value="all" <?= $selected_ts === 'all' ? 'selected' : '' ?>>All TS</option>
                        <?php foreach ($ts_list as $ts): ?>
                            <option value="<?= htmlspecialchars($ts['ts_code']) ?>" <?= $selected_ts === $ts['ts_code'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ts['station_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <div class="filter-group">
                <label>Type:</label>
                <select onchange="window.location.href='?page=dashboard&action=interruptions&tab=<?= $active_tab ?>&date=<?= urlencode($selected_date) ?>&<?= $active_tab === '11kv' ? 'iss=' . urlencode($selected_iss) : 'ts=' . urlencode($selected_ts) ?>&type=' + this.value">
                    <option value="all" <?= $selected_type === 'all' ? 'selected' : '' ?>>All Types</option>
                    <?php foreach ($interruption_types as $type): ?>
                        <option value="<?= htmlspecialchars($type) ?>" <?= $selected_type === $type ? 'selected' : '' ?>>
                            <?= htmlspecialchars($type) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <span style="margin-left: auto; font-size: 14px;">
                <?= date('l, F j, Y', strtotime($selected_date)) ?>
            </span>
        </div>
    </div>

    <!-- Tabs -->
    <div class="tabs-container">
        <div class="tabs">
            <a href="?page=dashboard&action=interruptions&tab=11kv&date=<?= urlencode($selected_date) ?>" 
               class="tab <?= $active_tab === '11kv' ? 'active' : '' ?>">
                <i class="fas fa-bolt"></i> 11kV Interruptions
            </a>
            <a href="?page=dashboard&action=interruptions&tab=33kv&date=<?= urlencode($selected_date) ?>" 
               class="tab <?= $active_tab === '33kv' ? 'active' : '' ?>">
                <i class="fas fa-broadcast-tower"></i> 33kV Interruptions
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-bar">
        <div class="stat-box">
            <div class="stat-value"><?= number_format($stats['total_interruptions']) ?></div>
            <div class="stat-label">Total Interruptions</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?= number_format($stats['total_load_loss'], 2) ?> MW</div>
            <div class="stat-label">Total Load Loss</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?= number_format($stats['total_duration'], 2) ?> hrs</div>
            <div class="stat-label">Total Duration</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?= number_format($stats['avg_duration'], 2) ?> hrs</div>
            <div class="stat-label">Avg Duration</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?= $stats['unique_feeders'] ?></div>
            <div class="stat-label">Affected Feeders</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?= $stats['pending_approvals'] ?></div>
            <div class="stat-label">Pending Approvals</div>
        </div>
    </div>

    <!-- Hourly Matrix -->
    <div class="matrix-section">
        <div class="section-title">
            <i class="fas fa-clock"></i>
            Interruptions by Hour
        </div>
        <div class="hourly-grid">
            <?php for ($h = 1; $h <= 24; $h++): 
                $hour_data = $hourly_matrix[$h];
                $has_interruptions = $hour_data['count'] > 0;
            ?>
                <div class="hour-cell <?= $has_interruptions ? 'has-interruptions' : '' ?>" 
                     title="Hour <?= sprintf('%02d', $h) ?>: <?= $hour_data['count'] ?> interruption(s)">
                    <div class="hour-label"><?= sprintf('%02d:00', $h) ?></div>
                    <div class="hour-count"><?= $hour_data['count'] ?></div>
                    <?php if ($has_interruptions): ?>
                        <div class="hour-duration"><?= number_format($hour_data['total_duration'], 1) ?>h</div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Content Grid -->
    <div class="content-grid">
        <!-- Interruptions List -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-list"></i>
                All Interruptions (<?= count($interruptions_data) ?>)
            </div>
            
            <?php if (empty($interruptions_data)): ?>
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <p><strong>No Interruptions</strong></p>
                    <p>No interruptions recorded for selected filters</p>
                </div>
            <?php else: ?>
                <div style="max-height: 600px; overflow-y: auto;">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Time Out</th>
                                <th>Feeder</th>
                                <th>Location</th>
                                <th>Type</th>
                                <th>Duration</th>
                                <th>Load Loss</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($interruptions_data as $int): ?>
                            <tr>
                                <td>
                                    <strong><?= date('H:i', strtotime($int['datetime_out'])) ?></strong>
                                    <br><small style="color: #64748b;"><?= date('M d', strtotime($int['datetime_out'])) ?></small>
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($active_tab === '11kv' ? $int['fdr11kv_name'] : $int['fdr33kv_name']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($active_tab === '11kv' ? $int['iss_name'] : $int['station_name']) ?></td>
                                <td><small><?= htmlspecialchars($int['interruption_type']) ?></small></td>
                                <td><strong><?= number_format($int['duration']?? 0, 2) ?></strong> hrs</td>
                                <td><strong><?= number_format($int['load_loss']?? 0, 2) ?></strong> MW</td>
                                <td><span class="badge badge-<?= strtolower(str_replace('_', '-', $int['approval_status'])) ?>"><?= $int['approval_status'] ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Analysis Sidebar -->
        <div>
            <!-- By Type -->
            <div class="card" style="margin-bottom: 20px;">
                <div class="card-header">
                    <i class="fas fa-chart-bar"></i>
                    By Interruption Type
                </div>
                <?php if (empty($by_type)): ?>
                    <p style="color: #94a3b8; text-align: center; padding: 20px;">No data</p>
                <?php else: ?>
                    <?php foreach (array_slice($by_type, 0, 10) as $type => $data): ?>
                        <div class="type-stat">
                            <div class="type-stat-header"><?= htmlspecialchars($type) ?></div>
                            <div class="type-stat-details">
                                <?= $data['count'] ?> interruption(s) | 
                                <?= number_format($data['duration'], 1) ?>h | 
                                <?= number_format($data['load_loss'], 1) ?> MW
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- By Location -->
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-map-marker-alt"></i>
                    By <?= $active_tab === '11kv' ? 'ISS' : 'Transmission Station' ?>
                </div>
                <?php 
                $by_location = $active_tab === '11kv' ? $by_iss : $by_ts;
                if (empty($by_location)): ?>
                    <p style="color: #94a3b8; text-align: center; padding: 20px;">No data</p>
                <?php else: ?>
                    <?php foreach (array_slice($by_location, 0, 10) as $loc => $data): ?>
                        <div class="type-stat">
                            <div class="type-stat-header"><?= htmlspecialchars($loc) ?></div>
                            <div class="type-stat-details">
                                <?= $data['count'] ?> interruption(s) | 
                                <?= number_format($data['duration'], 1) ?>h | 
                                <?= number_format($data['load_loss'], 1) ?> MW
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
