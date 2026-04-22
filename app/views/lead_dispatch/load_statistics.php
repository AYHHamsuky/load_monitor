<?php
/**
 * Load Statistics by Hierarchy - COMPLETE WITH TABS AND CHARTS
 * Path: /app/views/lead_dispatch/load_statistics.php
 * 
 * Features:
 * - 5 Tabs: Area Offices, Transmission Stations, ISS, 11kV Feeders, 33kV Feeders
 * - Chart.js integration for visual analytics
 * - Filterable data with drill-down capabilities
 */

// Get active tab
$active_tab = $_GET['tab'] ?? 'area_offices';

// Get filter parameters
$selected_ao = $_GET['ao'] ?? 'all';
$selected_ts = $_GET['ts'] ?? 'all';
$selected_iss = $_GET['iss'] ?? 'all';

// ========== TAB 1: AREA OFFICES ==========
if ($active_tab === 'area_offices') {
    $stmt = $db->prepare("
        SELECT 
            ao.ao_id,
            ao.ao_name,
            COUNT(DISTINCT iss.iss_code) as total_iss,
            COUNT(DISTINCT f11.fdr11kv_code) as total_11kv_feeders,
            COUNT(DISTINCT f33.fdr33kv_code) as total_33kv_feeders,
            COALESCE(SUM(d11.load_read), 0) as load_11kv,
            COALESCE(SUM(d33.load_read), 0) as load_33kv,
            COALESCE(SUM(d11.load_read), 0) + COALESCE(SUM(d33.load_read), 0) as total_load,
            COALESCE(AVG(d11.load_read), 0) as avg_load_11kv,
            COALESCE(AVG(d33.load_read), 0) as avg_load_33kv,
            COALESCE(MAX(d11.load_read), 0) as peak_load_11kv,
            COALESCE(MAX(d33.load_read), 0) as peak_load_33kv
        FROM area_offices ao
        LEFT JOIN fdr11kv f11 ON f11.ao_code = ao.ao_id
        LEFT JOIN iss_locations iss ON iss.iss_code = f11.iss_code
        LEFT JOIN fdr11kv_data d11 ON d11.Fdr11kv_code = f11.fdr11kv_code AND d11.entry_date = ?
        LEFT JOIN fdr33kv f33 ON f33.ts_code IN (
            SELECT DISTINCT f.iss_code FROM fdr11kv f WHERE f.ao_code = ao.ao_id
        )
        LEFT JOIN fdr33kv_data d33 ON d33.Fdr33kv_code = f33.fdr33kv_code AND d33.entry_date = ?
        GROUP BY ao.ao_id, ao.ao_name
        HAVING total_load > 0
        ORDER BY total_load DESC
    ");
    $stmt->execute([$selected_date, $selected_date]);
    $data_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_system_load = array_sum(array_column($data_stats, 'total_load'));
}

// ========== TAB 2: TRANSMISSION STATIONS ==========
elseif ($active_tab === 'transmission_stations') {
    // Build WHERE clause
    $where_conditions = [];
    $params = [$selected_date];
    
    if ($selected_ao !== 'all') {
        $where_conditions[] = "ao.ao_id = ?";
        $params[] = $selected_ao;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $stmt = $db->prepare("
        SELECT 
            ts.ts_code,
            ts.station_name,
            ao.ao_name,
            COUNT(DISTINCT f.fdr33kv_code) as total_feeders,
            COALESCE(SUM(d.load_read), 0) as total_load,
            COALESCE(AVG(d.load_read), 0) as avg_load,
            COALESCE(MAX(d.load_read), 0) as peak_load,
            COUNT(DISTINCT CASE WHEN d.fault_code IS NOT NULL THEN d.Fdr33kv_code END) as feeders_with_faults
        FROM transmission_stations ts
        LEFT JOIN fdr33kv f ON f.ts_code = ts.ts_code
        LEFT JOIN fdr33kv_data d ON d.Fdr33kv_code = f.fdr33kv_code AND d.entry_date = ?
        LEFT JOIN fdr11kv f11 ON f11.iss_code = ts.ts_code
        LEFT JOIN area_offices ao ON ao.ao_id = f11.ao_code
        {$where_clause}
        GROUP BY ts.ts_code, ts.station_name, ao.ao_name
        HAVING total_load > 0
        ORDER BY total_load DESC
    ");
    $stmt->execute($params);
    $data_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_system_load = array_sum(array_column($data_stats, 'total_load'));
    
    // Get AO list for filter
    $ao_list = $db->query("SELECT ao_id, ao_name FROM area_offices ORDER BY ao_name")->fetchAll(PDO::FETCH_ASSOC);
}

// ========== TAB 3: INJECTION SUBSTATIONS ==========
elseif ($active_tab === 'iss') {
    // Build WHERE clause
    $where_conditions = [];
    $params = [$selected_date];
    
    if ($selected_ao !== 'all') {
        $where_conditions[] = "ao.ao_id = ?";
        $params[] = $selected_ao;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $stmt = $db->prepare("
        SELECT 
            iss.iss_code,
            iss.iss_name,
            ao.ao_name,
            COUNT(DISTINCT f.fdr11kv_code) as total_feeders,
            COALESCE(SUM(d.load_read), 0) as total_load,
            COALESCE(AVG(d.load_read), 0) as avg_load,
            COALESCE(MAX(d.load_read), 0) as peak_load,
            COUNT(DISTINCT CASE WHEN d.fault_code IS NOT NULL THEN d.Fdr11kv_code END) as feeders_with_faults
        FROM iss_locations iss
        LEFT JOIN fdr11kv f ON f.iss_code = iss.iss_code
        LEFT JOIN fdr11kv_data d ON d.Fdr11kv_code = f.fdr11kv_code AND d.entry_date = ?
        LEFT JOIN area_offices ao ON ao.ao_id = f.ao_code
        {$where_clause}
        GROUP BY iss.iss_code, iss.iss_name, ao.ao_name
        HAVING total_load > 0
        ORDER BY total_load DESC
    ");
    $stmt->execute($params);
    $data_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_system_load = array_sum(array_column($data_stats, 'total_load'));
    
    // Get AO list for filter
    $ao_list = $db->query("SELECT ao_id, ao_name FROM area_offices ORDER BY ao_name")->fetchAll(PDO::FETCH_ASSOC);
}

// ========== TAB 4: 11kV FEEDERS ==========
elseif ($active_tab === '11kv_feeders') {
    // Build WHERE clause
    $where_conditions = [];
    $params = [$selected_date];
    
    if ($selected_ao !== 'all') {
        $where_conditions[] = "ao.ao_id = ?";
        $params[] = $selected_ao;
    }
    
    if ($selected_iss !== 'all') {
        $where_conditions[] = "iss.iss_code = ?";
        $params[] = $selected_iss;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $stmt = $db->prepare("
        SELECT 
            f.fdr11kv_code,
            f.fdr11kv_name,
            f.band,
            iss.iss_name,
            ao.ao_name,
            COALESCE(SUM(d.load_read), 0) as total_load,
            COALESCE(AVG(d.load_read), 0) as avg_load,
            COALESCE(MAX(d.load_read), 0) as peak_load,
            COUNT(CASE WHEN d.load_read > 0 THEN 1 END) as supply_hours,
            COUNT(CASE WHEN d.fault_code IS NOT NULL THEN 1 END) as fault_hours,
            COUNT(d.entry_hour) as data_entries,
            ROUND((COUNT(d.entry_hour) / 24.0) * 100, 1) as completion_rate
        FROM fdr11kv f
        LEFT JOIN iss_locations iss ON iss.iss_code = f.iss_code
        LEFT JOIN area_offices ao ON ao.ao_id = f.ao_code
        LEFT JOIN fdr11kv_data d ON d.Fdr11kv_code = f.fdr11kv_code AND d.entry_date = ?
        {$where_clause}
        GROUP BY f.fdr11kv_code, f.fdr11kv_name, f.band, iss.iss_name, ao.ao_name
        HAVING total_load > 0
        ORDER BY total_load DESC
        LIMIT 100
    ");
    $stmt->execute($params);
    $data_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_system_load = array_sum(array_column($data_stats, 'total_load'));
    
    // Get filter lists
    $ao_list = $db->query("SELECT ao_id, ao_name FROM area_offices ORDER BY ao_name")->fetchAll(PDO::FETCH_ASSOC);
    $iss_list = $db->query("SELECT iss_code, iss_name FROM iss_locations ORDER BY iss_name")->fetchAll(PDO::FETCH_ASSOC);
}

// ========== TAB 5: 33kV FEEDERS ==========
elseif ($active_tab === '33kv_feeders') {
    // Build WHERE clause
    $where_conditions = [];
    $params = [$selected_date];
    
    if ($selected_ts !== 'all') {
        $where_conditions[] = "ts.ts_code = ?";
        $params[] = $selected_ts;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $stmt = $db->prepare("
        SELECT 
            f.fdr33kv_code,
            f.fdr33kv_name,
            ts.station_name,
            COALESCE(SUM(d.load_read), 0) as total_load,
            COALESCE(AVG(d.load_read), 0) as avg_load,
            COALESCE(MAX(d.load_read), 0) as peak_load,
            COUNT(CASE WHEN d.load_read > 0 THEN 1 END) as supply_hours,
            COUNT(CASE WHEN d.fault_code IS NOT NULL THEN 1 END) as fault_hours,
            COUNT(d.entry_hour) as data_entries,
            ROUND((COUNT(d.entry_hour) / 24.0) * 100, 1) as completion_rate
        FROM fdr33kv f
        LEFT JOIN transmission_stations ts ON ts.ts_code = f.ts_code
        LEFT JOIN fdr33kv_data d ON d.Fdr33kv_code = f.fdr33kv_code AND d.entry_date = ?
        {$where_clause}
        GROUP BY f.fdr33kv_code, f.fdr33kv_name, ts.station_name
        HAVING total_load > 0
        ORDER BY total_load DESC
        LIMIT 100
    ");
    $stmt->execute($params);
    $data_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_system_load = array_sum(array_column($data_stats, 'total_load'));
    
    // Get TS list for filter
    $ts_list = $db->query("SELECT ts_code, station_name FROM transmission_stations ORDER BY station_name")->fetchAll(PDO::FETCH_ASSOC);
}

// Calculate overall statistics
$stats = [
    'total_count' => count($data_stats),
    'total_load' => $total_system_load,
    'avg_load' => count($data_stats) > 0 ? $total_system_load / count($data_stats) : 0,
    'peak_load' => count($data_stats) > 0 ? max(array_column($data_stats, in_array($active_tab, ['11kv_feeders', '33kv_feeders']) ? 'peak_load' : 'total_load')) : 0
];

require __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/sidebar.php';
?>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>

<style>
.main-content {
    margin-left: 260px;
    padding: 20px;
    padding-top: 84px;
    background: #f4f6fa;
    min-height: 100vh;
}

.page-header {
    background: linear-gradient(135deg, #f2994a 0%, #f2c94c 100%);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 20px;
    color: white;
    box-shadow: 0 4px 12px rgba(242, 153, 74, 0.3);
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
    overflow-x: auto;
}

.tab {
    flex: 1;
    padding: 16px 20px;
    text-align: center;
    cursor: pointer;
    font-weight: 600;
    font-size: 13px;
    color: #64748b;
    transition: all 0.3s ease;
    text-decoration: none;
    border-bottom: 3px solid transparent;
    white-space: nowrap;
}

.tab:hover {
    background: #f8fafc;
    color: #1e293b;
}

.tab.active {
    color: #f2994a;
    border-bottom-color: #f2994a;
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

/* Content Layout */
.content-layout {
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

/* Chart Container */
.chart-container {
    position: relative;
    height: 300px;
    margin-bottom: 20px;
}

/* Data Table */
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
    position: sticky;
    top: 0;
}

.data-table tbody td {
    padding: 10px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 14px;
}

.data-table tbody tr:hover {
    background: #f8fafc;
}

/* Progress Bar */
.progress-bar {
    height: 8px;
    background: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
    width: 100%;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #f2994a, #f2c94c);
    border-radius: 4px;
    transition: width 0.3s ease;
}

/* Badge */
.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.badge-a { background: #dcfce7; color: #166534; }
.badge-b { background: #dbeafe; color: #1e40af; }
.badge-c { background: #fef3c7; color: #92400e; }

@media (max-width: 1024px) {
    .content-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        padding: 12px;
        padding-top: 76px;
    }
    
    .tabs {
        flex-wrap: nowrap;
        overflow-x: scroll;
    }
}
</style>

<div class="main-content">
    <div class="page-header">
        <div class="page-title">
            <i class="fas fa-chart-pie"></i>
            Load Statistics & Analytics
        </div>
        <div class="filters-row">
            <div class="filter-group">
                <i class="fas fa-calendar"></i>
                <input type="date" 
                       value="<?= htmlspecialchars($selected_date) ?>" 
                       max="<?= date('Y-m-d') ?>"
                       onchange="window.location.href='?page=dashboard&action=statistics&tab=<?= $active_tab ?>&date=' + this.value">
            </div>
            
            <?php if (isset($ao_list) && $active_tab !== 'area_offices'): ?>
                <div class="filter-group">
                    <i class="fas fa-filter"></i>
                    <label style="font-size: 13px; font-weight: 600;">Area Office:</label>
                    <select onchange="window.location.href='?page=dashboard&action=statistics&tab=<?= $active_tab ?>&date=<?= urlencode($selected_date) ?>&ao=' + this.value<?= isset($iss_list) ? " + '&iss=<?= urlencode($selected_iss) ?>'" : '' ?><?= isset($ts_list) ? " + '&ts=<?= urlencode($selected_ts) ?>'" : '' ?>">
                        <option value="all" <?= $selected_ao === 'all' ? 'selected' : '' ?>>All Area Offices</option>
                        <?php foreach ($ao_list as $ao): ?>
                            <option value="<?= htmlspecialchars($ao['ao_id']) ?>" <?= $selected_ao === $ao['ao_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ao['ao_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <?php if (isset($iss_list)): ?>
                <div class="filter-group">
                    <label style="font-size: 13px; font-weight: 600;">ISS:</label>
                    <select onchange="window.location.href='?page=dashboard&action=statistics&tab=<?= $active_tab ?>&date=<?= urlencode($selected_date) ?>&ao=<?= urlencode($selected_ao) ?>&iss=' + this.value">
                        <option value="all" <?= $selected_iss === 'all' ? 'selected' : '' ?>>All ISS</option>
                        <?php foreach ($iss_list as $iss): ?>
                            <option value="<?= htmlspecialchars($iss['iss_code']) ?>" <?= $selected_iss === $iss['iss_code'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($iss['iss_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <?php if (isset($ts_list)): ?>
                <div class="filter-group">
                    <label style="font-size: 13px; font-weight: 600;">TS:</label>
                    <select onchange="window.location.href='?page=dashboard&action=statistics&tab=<?= $active_tab ?>&date=<?= urlencode($selected_date) ?>&ts=' + this.value">
                        <option value="all" <?= $selected_ts === 'all' ? 'selected' : '' ?>>All TS</option>
                        <?php foreach ($ts_list as $ts): ?>
                            <option value="<?= htmlspecialchars($ts['ts_code']) ?>" <?= $selected_ts === $ts['ts_code'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($ts['station_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <span style="margin-left: auto; font-size: 14px;">
                <?= date('l, F j, Y', strtotime($selected_date)) ?>
            </span>
        </div>
    </div>

    <!-- Tabs -->
    <div class="tabs-container">
        <div class="tabs">
            <a href="?page=dashboard&action=statistics&tab=area_offices&date=<?= urlencode($selected_date) ?>" 
               class="tab <?= $active_tab === 'area_offices' ? 'active' : '' ?>">
                <i class="fas fa-building"></i> Area Offices
            </a>
            <a href="?page=dashboard&action=statistics&tab=transmission_stations&date=<?= urlencode($selected_date) ?>" 
               class="tab <?= $active_tab === 'transmission_stations' ? 'active' : '' ?>">
                <i class="fas fa-broadcast-tower"></i> Transmission Stations
            </a>
            <a href="?page=dashboard&action=statistics&tab=iss&date=<?= urlencode($selected_date) ?>" 
               class="tab <?= $active_tab === 'iss' ? 'active' : '' ?>">
                <i class="fas fa-charging-station"></i> Injection Substations
            </a>
            <a href="?page=dashboard&action=statistics&tab=11kv_feeders&date=<?= urlencode($selected_date) ?>" 
               class="tab <?= $active_tab === '11kv_feeders' ? 'active' : '' ?>">
                <i class="fas fa-bolt"></i> 11kV Feeders
            </a>
            <a href="?page=dashboard&action=statistics&tab=33kv_feeders&date=<?= urlencode($selected_date) ?>" 
               class="tab <?= $active_tab === '33kv_feeders' ? 'active' : '' ?>">
                <i class="fas fa-plug"></i> 33kV Feeders
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-bar">
        <div class="stat-box">
            <div class="stat-value"><?= number_format($stats['total_count']) ?></div>
            <div class="stat-label">
                <?php
                    $label_map = [
                        'area_offices' => 'Area Offices',
                        'transmission_stations' => 'TS Locations',
                        'iss' => 'ISS Locations',
                        '11kv_feeders' => '11kV Feeders',
                        '33kv_feeders' => '33kV Feeders'
                    ];
                    echo $label_map[$active_tab] ?? 'Total';
                ?>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?= number_format($stats['total_load'], 2) ?> MW</div>
            <div class="stat-label">Total Load</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?= number_format($stats['avg_load'], 2) ?> MW</div>
            <div class="stat-label">Average Load</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?= number_format($stats['peak_load'], 2) ?> MW</div>
            <div class="stat-label">Peak Load</div>
        </div>
    </div>

    <!-- Content Layout -->
    <div class="content-layout">
        <!-- Main Table -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-table"></i>
                Detailed Data
            </div>
            
            <div style="max-height: 600px; overflow-y: auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <?php if ($active_tab === 'area_offices'): ?>
                                <th>Area Office</th>
                                <th>ISS Count</th>
                                <th>11kV Load</th>
                                <th>33kV Load</th>
                                <th>Total Load</th>
                                <th>% of System</th>
                            <?php elseif ($active_tab === 'transmission_stations'): ?>
                                <th>Station Name</th>
                                <th>Area Office</th>
                                <th>Feeders</th>
                                <th>Total Load</th>
                                <th>Avg Load</th>
                                <th>Peak Load</th>
                            <?php elseif ($active_tab === 'iss'): ?>
                                <th>ISS Name</th>
                                <th>Area Office</th>
                                <th>Feeders</th>
                                <th>Total Load</th>
                                <th>Avg Load</th>
                                <th>Peak Load</th>
                            <?php elseif ($active_tab === '11kv_feeders'): ?>
                                <th>Feeder Name</th>
                                <th>Band</th>
                                <th>ISS</th>
                                <th>Total Load</th>
                                <th>Peak Load</th>
                                <th>Completion</th>
                            <?php elseif ($active_tab === '33kv_feeders'): ?>
                                <th>Feeder Name</th>
                                <th>TS</th>
                                <th>Total Load</th>
                                <th>Peak Load</th>
                                <th>Supply Hrs</th>
                                <th>Completion</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data_stats as $row): ?>
                        <tr>
                            <?php if ($active_tab === 'area_offices'): 
                                $percentage = $total_system_load > 0 ? ($row['total_load'] / $total_system_load) * 100 : 0;
                            ?>
                                <td><strong><?= htmlspecialchars($row['ao_name']) ?></strong></td>
                                <td><?= $row['total_iss'] ?></td>
                                <td><?= number_format($row['load_11kv'], 2) ?> MW</td>
                                <td><?= number_format($row['load_33kv'], 2) ?> MW</td>
                                <td><strong><?= number_format($row['total_load'], 2) ?> MW</strong></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div class="progress-bar" style="width: 80px;">
                                            <div class="progress-fill" style="width: <?= $percentage ?>%;"></div>
                                        </div>
                                        <span><?= number_format($percentage, 1) ?>%</span>
                                    </div>
                                </td>
                            <?php elseif ($active_tab === 'transmission_stations'): ?>
                                <td><strong><?= htmlspecialchars($row['station_name']) ?></strong></td>
                                <td><?= htmlspecialchars($row['ao_name'] ?? 'N/A') ?></td>
                                <td><?= $row['total_feeders'] ?></td>
                                <td><strong><?= number_format($row['total_load'], 2) ?> MW</strong></td>
                                <td><?= number_format($row['avg_load'], 2) ?> MW</td>
                                <td><?= number_format($row['peak_load'], 2) ?> MW</td>
                            <?php elseif ($active_tab === 'iss'): ?>
                                <td><strong><?= htmlspecialchars($row['iss_name']) ?></strong></td>
                                <td><?= htmlspecialchars($row['ao_name'] ?? 'N/A') ?></td>
                                <td><?= $row['total_feeders'] ?></td>
                                <td><strong><?= number_format($row['total_load'], 2) ?> MW</strong></td>
                                <td><?= number_format($row['avg_load'], 2) ?> MW</td>
                                <td><?= number_format($row['peak_load'], 2) ?> MW</td>
                            <?php elseif ($active_tab === '11kv_feeders'): ?>
                                <td><strong><?= htmlspecialchars($row['fdr11kv_name']) ?></strong></td>
                                <td><span class="badge badge-<?= strtolower($row['band']) ?>"><?= htmlspecialchars($row['band']) ?></span></td>
                                <td><?= htmlspecialchars($row['iss_name']) ?></td>
                                <td><strong><?= number_format($row['total_load'], 2) ?> MW</strong></td>
                                <td><?= number_format($row['peak_load'], 2) ?> MW</td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div class="progress-bar" style="width: 60px;">
                                            <div class="progress-fill" style="width: <?= $row['completion_rate'] ?>%;"></div>
                                        </div>
                                        <span><?= $row['completion_rate'] ?>%</span>
                                    </div>
                                </td>
                            <?php elseif ($active_tab === '33kv_feeders'): ?>
                                <td><strong><?= htmlspecialchars($row['fdr33kv_name']) ?></strong></td>
                                <td><?= htmlspecialchars($row['station_name']) ?></td>
                                <td><strong><?= number_format($row['total_load'], 2) ?> MW</strong></td>
                                <td><?= number_format($row['peak_load'], 2) ?> MW</td>
                                <td><?= $row['supply_hours'] ?>/24</td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div class="progress-bar" style="width: 60px;">
                                            <div class="progress-fill" style="width: <?= $row['completion_rate'] ?>%;"></div>
                                        </div>
                                        <span><?= $row['completion_rate'] ?>%</span>
                                    </div>
                                </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Chart Sidebar -->
        <div>
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-bar"></i>
                    Visual Analytics
                </div>
                <div class="chart-container">
                    <canvas id="loadChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Prepare chart data
const chartData = {
    labels: <?= json_encode(array_slice(array_column($data_stats, 
        $active_tab === 'area_offices' ? 'ao_name' : 
        ($active_tab === 'transmission_stations' ? 'station_name' : 
        ($active_tab === 'iss' ? 'iss_name' : 
        ($active_tab === '11kv_feeders' ? 'fdr11kv_name' : 'fdr33kv_name')))), 0, 10)) ?>,
    values: <?= json_encode(array_slice(array_column($data_stats, 
        in_array($active_tab, ['11kv_feeders', '33kv_feeders']) ? 'peak_load' : 'total_load'), 0, 10)) ?>
};

// Create chart
const ctx = document.getElementById('loadChart').getContext('2d');
new Chart(ctx, {
    type: 'bar',
    data: {
        labels: chartData.labels,
        datasets: [{
            label: 'Load (MW)',
            data: chartData.values,
            backgroundColor: 'rgba(242, 153, 74, 0.8)',
            borderColor: 'rgba(242, 153, 74, 1)',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Load (MW)',
                    font: { weight: 'bold' }
                }
            }
        },
        plugins: {
            legend: {
                display: false
            },
            title: {
                display: true,
                text: 'Top 10 by Load',
                font: { size: 14, weight: 'bold' }
            }
        }
    }
});
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
