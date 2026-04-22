<?php
/**
 * 33kV Hourly Load Matrix View - CORRECTED SCHEMA
 * Path: /app/views/lead_dispatch/33kv_matrix.php
 * 
 * Uses only: fdr33kv (fdr33kv_code, fdr33kv_name, ts_code)
 *           transmission_stations (ts_code, station_name)
 *           fdr33kv_data (entry_date, Fdr33kv_code, entry_hour, load_read, fault_code)
 */

// Get selected TS filter from URL parameter
$selected_ts = $_GET['ts'] ?? 'all';

// Get all TS locations for dropdown
$stmt_ts_list = $db->query("
    SELECT ts_code, station_name 
    FROM transmission_stations 
    ORDER BY station_name
");
$ts_list = $stmt_ts_list->fetchAll(PDO::FETCH_ASSOC);

// Build query with TS filter
$where_clause = "";
$params = [];

if ($selected_ts !== 'all') {
    $where_clause = " WHERE f.ts_code = ?";
    $params[] = $selected_ts;
}

// Fetch all 33kV feeders (filtered by TS if selected)
// ONLY USE COLUMNS THAT EXIST IN SCHEMA
$sql = "
    SELECT 
        f.fdr33kv_code,
        f.fdr33kv_name,
        f.ts_code,
        ts.station_name
    FROM fdr33kv f
    INNER JOIN transmission_stations ts ON ts.ts_code = f.ts_code
    {$where_clause}
    ORDER BY ts.station_name, f.fdr33kv_name
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$feeders_33kv = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch hourly data for each feeder
$feeder_data = [];
$feeder_summary = [];

foreach ($feeders_33kv as $feeder) {
    $stmt = $db->prepare("
        SELECT 
            entry_hour,
            load_read,
            fault_code,
            fault_remark
        FROM fdr33kv_data
        WHERE Fdr33kv_code = ? AND entry_date = ?
        ORDER BY entry_hour
    ");
    $stmt->execute([$feeder['fdr33kv_code'], $selected_date]);
    $hours = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize by hour
    $feeder_data[$feeder['fdr33kv_code']] = [];
    $total_load = 0;
    $valid_readings = 0;
    $peak_load = 0;
    $supply_hours = 0;
    $fault_hours = 0;
    
    for ($h = 1; $h <= 24; $h++) {
        $hour_entry = null;
        foreach ($hours as $entry) {
            if ($entry['entry_hour'] == $h) {
                $hour_entry = $entry;
                break;
            }
        }
        
        $feeder_data[$feeder['fdr33kv_code']][$h] = $hour_entry;
        
        if ($hour_entry && $hour_entry['load_read'] !== null) {
            $total_load += $hour_entry['load_read'];
            $valid_readings++;
            $peak_load = max($peak_load, $hour_entry['load_read']);
            
            if ($hour_entry['load_read'] > 0) {
                $supply_hours++;
            }
        }
        
        if ($hour_entry && !empty($hour_entry['fault_code'])) {
            $fault_hours++;
        }
    }
    
    $feeder_summary[$feeder['fdr33kv_code']] = [
        'feeder' => $feeder,
        'total_load' => $total_load,
        'avg_load' => $valid_readings > 0 ? $total_load / $valid_readings : 0,
        'peak_load' => $peak_load,
        'supply_hours' => $supply_hours,
        'fault_hours' => $fault_hours,
        'completion_rate' => ($valid_readings / 24) * 100
    ];
}

// Overall statistics (recalculated based on filtered feeders)
$overall_stats = [
    'total_feeders' => count($feeders_33kv),
    'total_load' => array_sum(array_column($feeder_summary, 'total_load')),
    'avg_load' => count($feeder_summary) > 0 ? array_sum(array_column($feeder_summary, 'avg_load')) / count($feeder_summary) : 0,
    'peak_load' => count($feeder_summary) > 0 ? max(array_column($feeder_summary, 'peak_load')) : 0,
    'total_supply_hours' => array_sum(array_column($feeder_summary, 'supply_hours')),
    'total_fault_hours' => array_sum(array_column($feeder_summary, 'fault_hours')),
    'avg_completion' => count($feeder_summary) > 0 ? array_sum(array_column($feeder_summary, 'completion_rate')) / count($feeder_summary) : 0
];

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
    background: linear-gradient(135deg, #7f00ff 0%, #e100ff 100%);
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 20px;
    color: white;
    box-shadow: 0 4px 12px rgba(127, 0, 255, 0.3);
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
    min-width: 200px;
}

.filter-group select option {
    background: #1e293b;
    color: white;
}

.filter-group select::-webkit-calendar-picker-indicator,
.filter-group input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
}

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

.matrix-container {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    overflow-x: auto;
}

.matrix-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.matrix-table th {
    background: #faf5ff;
    padding: 10px 8px;
    text-align: left;
    font-weight: 700;
    color: #334155;
    border-bottom: 2px solid #e9d5ff;
    position: sticky;
    top: 0;
    z-index: 10;
}

.matrix-table td {
    padding: 8px;
    border-bottom: 1px solid #f1f5f9;
}

.matrix-table tbody tr:hover {
    background: #faf5ff;
}

.feeder-cell {
    font-weight: 600;
    color: #1e293b;
    min-width: 200px;
}

.load-cell {
    text-align: center;
    font-weight: 600;
}

.load-cell.has-load {
    background: #fce7f3;
    color: #9f1239;
}

.load-cell.has-fault {
    background: #fee2e2;
    color: #991b1b;
}

.load-cell.no-data {
    background: #f1f5f9;
    color: #94a3b8;
}

.summary-cell {
    font-weight: 700;
    background: #faf5ff;
    color: #7e22ce;
}

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

@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        padding: 12px;
        padding-top: 76px;
    }
    
    .stats-bar {
        flex-direction: column;
    }
    
    .filters-row {
        flex-direction: column;
        align-items: stretch;
    }
    
    .filter-group select,
    .filter-group input[type="date"] {
        width: 100%;
    }
}
</style>

<div class="main-content">
    <div class="page-header">
        <div class="page-title">
            <i class="fas fa-table"></i>
            33kV Hourly Load Matrix
        </div>
        <div class="filters-row">
            <div class="filter-group">
                <i class="fas fa-calendar"></i>
                <input type="date" 
                       value="<?= htmlspecialchars($selected_date) ?>" 
                       max="<?= date('Y-m-d') ?>"
                       onchange="window.location.href='?page=dashboard&action=33kv&date=' + this.value + '&ts=<?= urlencode($selected_ts) ?>'">
            </div>
            
            <div class="filter-group">
                <i class="fas fa-filter"></i>
                <label>Filter by TS:</label>
                <select onchange="window.location.href='?page=dashboard&action=33kv&date=<?= urlencode($selected_date) ?>&ts=' + this.value">
                    <option value="all" <?= $selected_ts === 'all' ? 'selected' : '' ?>>All Transmission Stations</option>
                    <?php foreach ($ts_list as $ts): ?>
                        <option value="<?= htmlspecialchars($ts['ts_code']) ?>" 
                                <?= $selected_ts === $ts['ts_code'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($ts['station_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <span style="margin-left: auto; font-size: 14px;">
                <?= date('l, F j, Y', strtotime($selected_date)) ?>
            </span>
        </div>
    </div>

    <div class="stats-bar">
        <div class="stat-box">
            <div class="stat-value"><?= number_format($overall_stats['total_feeders']) ?></div>
            <div class="stat-label">Total Feeders <?= $selected_ts !== 'all' ? '(Filtered)' : '' ?></div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?= number_format($overall_stats['total_load'], 2) ?></div>
            <div class="stat-label">Total Load (MW)</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?= number_format($overall_stats['avg_load'], 2) ?></div>
            <div class="stat-label">Average Load (MW)</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?= number_format($overall_stats['peak_load'], 2) ?></div>
            <div class="stat-label">Peak Load (MW)</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?= $overall_stats['total_fault_hours'] ?></div>
            <div class="stat-label">Fault Hours</div>
        </div>
        <div class="stat-box">
            <div class="stat-value"><?= number_format($overall_stats['avg_completion'], 1) ?>%</div>
            <div class="stat-label">Data Completion</div>
        </div>
    </div>

    <div class="matrix-container">
        <?php if (empty($feeders_33kv)): ?>
            <div class="empty-state">
                <i class="fas fa-info-circle"></i>
                <p><strong>No Feeders Found</strong></p>
                <p>No feeders found for the selected Transmission Station and date.</p>
            </div>
        <?php else: ?>
            <table class="matrix-table">
                <thead>
                    <tr>
                        <th>Feeder</th>
                        <th>Transmission Station</th>
                        <?php for ($h = 1; $h <= 24; $h++): ?>
                            <th style="text-align: center;"><?= sprintf('%02d', $h) ?></th>
                        <?php endfor; ?>
                        <th style="text-align: center;">Total</th>
                        <th style="text-align: center;">Avg</th>
                        <th style="text-align: center;">Peak</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($feeders_33kv as $feeder): 
                        $summary = $feeder_summary[$feeder['fdr33kv_code']];
                    ?>
                    <tr>
                        <td class="feeder-cell"><?= htmlspecialchars($feeder['fdr33kv_name']) ?></td>
                        <td><?= htmlspecialchars($feeder['station_name']) ?></td>
                        
                        <?php for ($h = 1; $h <= 24; $h++): 
                            $data = $feeder_data[$feeder['fdr33kv_code']][$h];
                            if ($data && !empty($data['fault_code'])) {
                                echo '<td class="load-cell has-fault" title="' . htmlspecialchars($data['fault_code']) . '">';
                                echo htmlspecialchars($data['fault_code']);
                            } elseif ($data && $data['load_read'] !== null) {
                                echo '<td class="load-cell has-load">';
                                echo number_format($data['load_read'], 2);
                            } else {
                                echo '<td class="load-cell no-data">';
                                echo '-';
                            }
                            echo '</td>';
                        endfor; ?>
                        
                        <td class="summary-cell" style="text-align: center;">
                            <?= number_format($summary['total_load'], 2) ?>
                        </td>
                        <td class="summary-cell" style="text-align: center;">
                            <?= number_format($summary['avg_load'], 2) ?>
                        </td>
                        <td class="summary-cell" style="text-align: center;">
                            <?= number_format($summary['peak_load'], 2) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
