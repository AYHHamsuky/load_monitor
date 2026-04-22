<?php
/**
 * Lead Dispatch Dashboard View - FIXED VERSION
 * Path: /app/views/lead_dispatch/dashboard.php
 * 
 * FIXES:
 * - Removed undefined $staff_stats variable warnings
 * - Removed quick access links section completely
 * - Added null coalescing for all staff_stats references
 */

require __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/sidebar.php';

// Initialize staff_stats if not set (prevents undefined variable warnings)
if (!isset($staff_stats) || !is_array($staff_stats)) {
    $staff_stats = [
        'staff_count' => 0,
        'ul1_count' => 0,
        'ul2_count' => 0
    ];
}
?>

<style>
.main-content {
    margin-left: 260px;
    padding: 20px;
    padding-top: 84px;
    background: #f4f6fa;
    min-height: 100vh;
}

/* Page Header */
.dispatch-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    padding: 30px;
    margin-bottom: 24px;
    color: white;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
}

.dispatch-title {
    font-size: 28px;
    font-weight: 700;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.dispatch-subtitle {
    font-size: 14px;
    opacity: 0.9;
}

/* Date Selector */
.date-selector {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 16px;
}

.date-selector input[type="date"] {
    padding: 8px 16px;
    border: 2px solid rgba(255,255,255,0.3);
    border-radius: 8px;
    background: rgba(255,255,255,0.2);
    color: white;
    font-weight: 600;
    font-size: 14px;
}

.date-selector input[type="date"]::-webkit-calendar-picker-indicator {
    filter: invert(1);
}

/* Statistics Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}

.stat-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 14px;
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-change {
    font-size: 12px;
    color: #64748b;
    margin-top: 8px;
}

.stat-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    color: white;
}

.stat-icon.blue { background: linear-gradient(135deg, #667eea, #764ba2); }
.stat-icon.purple { background: linear-gradient(135deg, #7f00ff, #e100ff); }
.stat-icon.green { background: linear-gradient(135deg, #11998e, #38ef7d); }
.stat-icon.red { background: linear-gradient(135deg, #eb3349, #f45c43); }
.stat-icon.orange { background: linear-gradient(135deg, #f2994a, #f2c94c); }
.stat-icon.cyan { background: linear-gradient(135deg, #00c6ff, #0072ff); }

/* Content Grid */
.content-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 24px;
    margin-bottom: 24px;
}

.content-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.card-header {
    font-size: 18px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #f1f5f9;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* Data Table */
.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table thead th {
    background: #f8fafc;
    padding: 12px;
    text-align: left;
    font-size: 12px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    border-bottom: 2px solid #e2e8f0;
}

.data-table tbody td {
    padding: 12px;
    border-bottom: 1px solid #f1f5f9;
    font-size: 14px;
    color: #334155;
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
    margin-top: 4px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea, #764ba2);
    border-radius: 4px;
}

/* List Items */
.list-item {
    padding: 12px;
    background: #f8fafc;
    border-radius: 8px;
    margin-bottom: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.list-item-title {
    font-weight: 600;
    color: #1e293b;
}

.list-item-meta {
    font-size: 12px;
    color: #64748b;
    margin-top: 4px;
}

.list-item-value {
    font-weight: 700;
    color: #667eea;
    font-size: 16px;
}

/* Badge */
.badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
}

.badge-11kv { background: #dbeafe; color: #1e40af; }
.badge-33kv { background: #fce7f3; color: #9f1239; }

/* Responsive */
@media (max-width: 1200px) {
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
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="main-content">
    <!-- Header -->
    <div class="dispatch-header">
        <div class="dispatch-title">
            <i class="fas fa-tachometer-alt"></i>
            Lead Dispatch Control Center
        </div>
        <div class="dispatch-subtitle">
            Real-time System Monitoring & Operations Dashboard
        </div>
        <div class="date-selector">
            <i class="fas fa-calendar"></i>
            <input type="date" 
                   value="<?= htmlspecialchars($selected_date) ?>" 
                   max="<?= date('Y-m-d') ?>"
                   onchange="window.location.href='?page=dashboard&date=' + this.value">
            <span><?= date('l, F j, Y', strtotime($selected_date)) ?></span>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <div class="stat-value"><?= number_format($system_stats['total_feeders']) ?></div>
                    <div class="stat-label">Total Feeders</div>
                    <div class="stat-change">
                        11kV: <?= $system_stats['total_11kv_feeders'] ?> | 33kV: <?= $system_stats['total_33kv_feeders'] ?>
                    </div>
                </div>
                <div class="stat-icon blue">
                    <i class="fas fa-bolt"></i>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <div class="stat-value"><?= number_format($system_stats['total_load'], 2) ?> MW</div>
                    <div class="stat-label">System Load</div>
                    <div class="stat-change">
                        11kV: <?= number_format($system_stats['load_11kv'], 2) ?> | 33kV: <?= number_format($system_stats['load_33kv'], 2) ?>
                    </div>
                </div>
                <div class="stat-icon purple">
                    <i class="fas fa-plug"></i>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <div class="stat-value"><?= number_format($system_stats['peak_load'], 2) ?> MW</div>
                    <div class="stat-label">Peak Load</div>
                    <div class="stat-change">
                        Stations: <?= $system_stats['total_iss'] ?> ISS | <?= $system_stats['total_ts'] ?> TS
                    </div>
                </div>
                <div class="stat-icon orange">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <div class="stat-value"><?= number_format($system_stats['data_completion'], 1) ?>%</div>
                    <div class="stat-label">Data Completion</div>
                    <div class="stat-change">
                        Supply Hours: <?= number_format($system_stats['supply_hours']) ?>
                    </div>
                </div>
                <div class="stat-icon green">
                    <i class="fas fa-tasks"></i>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <div class="stat-value"><?= $staff_stats['staff_count'] ?></div>
                    <div class="stat-label">Staff on Duty</div>
                    <div class="stat-change">
                        UL1: <?= $staff_stats['ul1_count'] ?> | UL2: <?= $staff_stats['ul2_count'] ?>
                    </div>
                </div>
                <div class="stat-icon cyan">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-header">
                <div>
                    <div class="stat-value"><?= count($recent_interruptions) ?></div>
                    <div class="stat-label">Interruptions</div>
                    <div class="stat-change">
                        Fault Hours: <?= number_format($system_stats['fault_hours']) ?>
                    </div>
                </div>
                <div class="stat-icon red">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Content Grid -->
    <div class="content-grid">
        <!-- Top Performing Feeders -->
        <div class="content-card">
            <div class="card-header">
                <i class="fas fa-trophy"></i>
                Top Performing Feeders by Load
            </div>
            
            <?php if (empty($top_feeders)): ?>
                <p style="text-align: center; color: #94a3b8; padding: 40px;">No data available</p>
            <?php else: ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Feeder Name</th>
                            <th>Location</th>
                            <th>Type</th>
                            <th>Total Load (MW)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_feeders as $feeder): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($feeder['feeder_name']) ?></strong></td>
                            <td><?= htmlspecialchars($feeder['location']) ?></td>
                            <td><span class="badge badge-<?= strtolower($feeder['voltage_level']) ?>"><?= $feeder['voltage_level'] ?></span></td>
                            <td><strong><?= number_format($feeder['total_load'], 2) ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Recent Interruptions -->
        <div class="content-card">
            <div class="card-header">
                <i class="fas fa-bell"></i>
                Recent Interruptions
            </div>
            
            <?php if (empty($recent_interruptions)): ?>
                <p style="text-align: center; color: #94a3b8; padding: 40px;">
                    <i class="fas fa-check-circle" style="font-size: 48px; opacity: 0.3; margin-bottom: 12px; display: block;"></i>
                    No interruptions
                </p>
            <?php else: ?>
                <?php foreach (array_slice($recent_interruptions, 0, 10) as $interruption): ?>
                    <div class="list-item">
                        <div>
                            <div class="list-item-title">
                                <?= htmlspecialchars($interruption['feeder_name']) ?>
                                <span class="badge badge-<?= strtolower($interruption['voltage_level']) ?>">
                                    <?= $interruption['voltage_level'] ?>
                                </span>
                            </div>
                            <div class="list-item-meta">
                                <?= htmlspecialchars($interruption['location_name']) ?> • 
                                Hour <?= sprintf('%02d:00', $interruption['entry_hour']) ?>
                            </div>
                        </div>
                        <div class="list-item-value">
                            <?= htmlspecialchars($interruption['fault_code']) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Area Office Load Distribution -->
    <?php if (!empty($regional_breakdown)): ?>
    <div class="content-card">
        <div class="card-header">
            <i class="fas fa-map-marked-alt"></i>
            Area Office Load Distribution
        </div>
        
        <table class="data-table">
            <thead>
                <tr>
                    <th>Area Office</th>
                    <th>Feeders</th>
                    <th>Total Load (MW)</th>
                    <th>% of System</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total_system_load = array_sum(array_column($regional_breakdown, 'total_load'));
                foreach ($regional_breakdown as $ao): 
                    $percentage = $total_system_load > 0 ? ($ao['total_load'] / $total_system_load) * 100 : 0;
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($ao['ao_name']) ?></strong></td>
                    <td><?= $ao['total_feeders'] ?></td>
                    <td><strong><?= number_format($ao['total_load'], 2) ?></strong></td>
                    <td>
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div class="progress-bar" style="width: 120px;">
                                <div class="progress-fill" style="width: <?= $percentage ?>%;"></div>
                            </div>
                            <span style="font-weight: 600; color: #667eea;">
                                <?= number_format($percentage, 1) ?>%
                            </span>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
