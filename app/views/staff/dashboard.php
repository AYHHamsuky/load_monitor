<?php 
/**
 * Enhanced Staff Dashboard
 * Path: app/views/staff/dashboard.php
 * 
 * Features:
 * - 33kV / 11kV voltage level selection
 * - Date range filtering
 * - Line chart for load trends
 * - Statistics cards
 * - Proper layout within header/sidebar boundaries
 */

require __DIR__ . '/../layout/header.php'; 
require __DIR__ . '/../layout/sidebar.php'; 

// Get filters from query string with defaults
$voltageLevel = $_GET['voltage'] ?? '11kV';
$dateFrom = $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days'));
$dateTo = $_GET['date_to'] ?? date('Y-m-d');

$db = Database::connect();

// Get load trend data based on voltage level
if ($voltageLevel === '11kV') {
    $stmt = $db->prepare("
        SELECT 
            entry_date,
            SUM(max_load) as total_load,
            COUNT(*) as num_feeders,
            AVG(max_load) as avg_load
        FROM fdr11kv
        WHERE entry_date BETWEEN ? AND ?
        GROUP BY entry_date
        ORDER BY entry_date ASC
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    $loadTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get today's statistics
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_entries,
            SUM(max_load) as total_load,
            AVG(max_load) as avg_load,
            MAX(max_load) as peak_load,
            MIN(max_load) as min_load
        FROM load_11kv
        WHERE entry_date BETWEEN ? AND ?
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} else { // 33kV
    $stmt = $db->prepare("
        SELECT 
            entry_date,
            SUM(max_load) as total_load,
            COUNT(*) as num_feeders,
            AVG(max_load) as avg_load
        FROM load_33kv
        WHERE entry_date BETWEEN ? AND ?
        GROUP BY entry_date
        ORDER BY entry_date ASC
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    $loadTrend = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get statistics
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_entries,
            SUM(max_load) as total_load,
            AVG(max_load) as avg_load,
            MAX(max_load) as peak_load,
            MIN(max_load) as min_load
        FROM load_33kv
        WHERE entry_date BETWEEN ? AND ?
    ");
    $stmt->execute([$dateFrom, $dateTo]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get interruptions count for the period
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_interruptions,
        SUM(duration) as total_downtime,
        SUM(load_loss) as total_load_loss
    FROM interruptions
    WHERE DATE(datetime_out) BETWEEN ? AND ?
");
$stmt->execute([$dateFrom, $dateTo]);
$interruptions = $stmt->fetch(PDO::FETCH_ASSOC);

// Calculate data completeness
$daysInPeriod = (strtotime($dateTo) - strtotime($dateFrom)) / 86400 + 1;
$expectedEntries = $daysInPeriod; // Simplified - adjust based on your feeder count
$completeness = ($stats['total_entries'] / max($expectedEntries, 1)) * 100;

?>

<style>
.main-content {
    margin-left: 260px;
    padding: 22px;
    padding-top: 90px;
    min-height: calc(100vh - 64px);
    background: #f4f6fa;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid #e5e7eb;
}

.page-title {
    font-size: 28px;
    font-weight: 700;
    color: #0f172a;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.page-subtitle {
    color: #6b7280;
    font-size: 14px;
    margin-top: 4px;
}

.header-badge {
    display: flex;
    gap: 10px;
}

.badge-readonly {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    letter-spacing: 0.5px;
}

/* Filter Section */
.filter-card {
    background: #ffffff;
    border-radius: 12px;
    padding: 20px 24px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    margin-bottom: 24px;
    border: 1px solid #e5e7eb;
}

.filter-grid {
    display: grid;
    grid-template-columns: 200px 1fr 1fr auto;
    gap: 16px;
    align-items: end;
}

.filter-group label {
    display: block;
    font-weight: 600;
    font-size: 13px;
    color: #374151;
    margin-bottom: 8px;
}

.filter-group select,
.filter-group input {
    width: 100%;
    padding: 10px 14px;
    border-radius: 8px;
    border: 1.5px solid #d1d5db;
    font-size: 14px;
    transition: all 0.2s ease;
}

.filter-group select:focus,
.filter-group input:focus {
    outline: none;
    border-color: #dc2626;
    box-shadow: 0 0 0 3px rgba(220,38,38,0.1);
}

.btn-filter {
    background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
    color: white;
    border: none;
    padding: 10px 24px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s ease;
}

.btn-filter:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(220,38,38,0.3);
}

/* Statistics Cards */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 18px;
    margin-bottom: 28px;
}

.stat-card {
    background: #ffffff;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.06);
    border-left: 4px solid;
    transition: all 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 18px rgba(0,0,0,0.1);
}

.stat-card.primary { border-color: #dc2626; }
.stat-card.secondary { border-color: #f59e0b; }
.stat-card.info { border-color: #3b82f6; }
.stat-card.success { border-color: #10b981; }
.stat-card.warning { border-color: #f97316; }
.stat-card.purple { border-color: #8b5cf6; }

.stat-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 12px;
}

.stat-icon {
    font-size: 28px;
    opacity: 0.8;
}

.stat-card.primary .stat-icon { color: #dc2626; }
.stat-card.secondary .stat-icon { color: #f59e0b; }
.stat-card.info .stat-icon { color: #3b82f6; }
.stat-card.success .stat-icon { color: #10b981; }
.stat-card.warning .stat-icon { color: #f97316; }
.stat-card.purple .stat-icon { color: #8b5cf6; }

.stat-value {
    font-size: 32px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 13px;
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 8px;
}

.stat-meta {
    font-size: 12px;
    color: #9ca3af;
}

/* Chart Section */
.chart-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 28px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    margin-bottom: 24px;
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid #e5e7eb;
}

.chart-title {
    font-size: 20px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 10px;
}

.voltage-badge {
    background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
    color: white;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.5px;
}

.chart-container {
    position: relative;
    height: 350px;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #6b7280;
}

.empty-icon {
    font-size: 64px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-title {
    font-size: 20px;
    font-weight: 700;
    color: #374151;
    margin-bottom: 8px;
}

.empty-description {
    font-size: 14px;
    color: #6b7280;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .filter-grid {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        padding: 12px;
        padding-top: 70px;
    }
    
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
    
    .filter-grid {
        grid-template-columns: 1fr;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="main-content">
    <!-- Page Header -->
    <div class="page-header">
        <div>
            <h1 class="page-title">
                <span>📊</span> Load Monitoring Dashboard
            </h1>
            <p class="page-subtitle">
                Real-time monitoring for <?= date('l, F j, Y') ?>
            </p>
        </div>
        <div class="header-badge">
            <span class="badge-readonly">STAFF VIEW</span>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-card">
        <form method="GET" action="">
            <input type="hidden" name="page" value="dashboard">
            <div class="filter-grid">
                <div class="filter-group">
                    <label>Voltage Level</label>
                    <select name="voltage">
                        <option value="11kV" <?= $voltageLevel === '11kV' ? 'selected' : '' ?>>11kV Network</option>
                        <option value="33kV" <?= $voltageLevel === '33kV' ? 'selected' : '' ?>>33kV Network</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Date From</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>" required>
                </div>
                
                <div class="filter-group">
                    <label>Date To</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>" required>
                </div>
                
                <button type="submit" class="btn-filter">
                    🔎 Apply
                </button>
            </div>
        </form>
    </div>

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-header">
                <span class="stat-icon">⚡</span>
            </div>
            <div class="stat-value"><?= number_format($stats['total_load'] ?? 0, 2) ?></div>
            <div class="stat-label">Total Load (MW)</div>
            <div class="stat-meta"><?= $voltageLevel ?> Network</div>
        </div>

        <div class="stat-card secondary">
            <div class="stat-header">
                <span class="stat-icon">📈</span>
            </div>
            <div class="stat-value"><?= number_format($stats['avg_load'] ?? 0, 2) ?></div>
            <div class="stat-label">Average Load (MW)</div>
            <div class="stat-meta">Daily Average</div>
        </div>

        <div class="stat-card info">
            <div class="stat-header">
                <span class="stat-icon">🔝</span>
            </div>
            <div class="stat-value"><?= number_format($stats['peak_load'] ?? 0, 2) ?></div>
            <div class="stat-label">Peak Load (MW)</div>
            <div class="stat-meta">Maximum Recorded</div>
        </div>

        <div class="stat-card warning">
            <div class="stat-header">
                <span class="stat-icon">⚠️</span>
            </div>
            <div class="stat-value"><?= $interruptions['total_interruptions'] ?? 0 ?></div>
            <div class="stat-label">Interruptions</div>
            <div class="stat-meta"><?= number_format($interruptions['total_downtime'] ?? 0, 1) ?> hrs downtime</div>
        </div>

        <div class="stat-card success">
            <div class="stat-header">
                <span class="stat-icon">✅</span>
            </div>
            <div class="stat-value"><?= number_format($completeness, 1) ?>%</div>
            <div class="stat-label">Data Completeness</div>
            <div class="stat-meta"><?= $stats['total_entries'] ?? 0 ?> entries</div>
        </div>

        <div class="stat-card purple">
            <div class="stat-header">
                <span class="stat-icon">💥</span>
            </div>
            <div class="stat-value"><?= number_format($interruptions['total_load_loss'] ?? 0, 2) ?></div>
            <div class="stat-label">Load Loss (MW)</div>
            <div class="stat-meta">Due to Interruptions</div>
        </div>
    </div>

    <!-- Load Trend Chart -->
    <div class="chart-card">
        <div class="chart-header">
            <h2 class="chart-title">
                <span>📉</span> Load Trend Analysis
            </h2>
            <span class="voltage-badge"><?= htmlspecialchars($voltageLevel) ?></span>
        </div>

        <?php if (empty($loadTrend)): ?>
            <div class="empty-state">
                <div class="empty-icon">📊</div>
                <div class="empty-title">No Data Available</div>
                <div class="empty-description">
                    No load data found for the selected period. Please adjust your date range.
                </div>
            </div>
        <?php else: ?>
            <div class="chart-container">
                <canvas id="loadTrendChart"></canvas>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($loadTrend)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Prepare data for chart
const dates = <?= json_encode(array_map(function($row) {
    return date('M j, Y', strtotime($row['entry_date']));
}, $loadTrend)) ?>;

const loads = <?= json_encode(array_map(function($row) {
    return floatval($row['total_load']);
}, $loadTrend)) ?>;

const avgLoads = <?= json_encode(array_map(function($row) {
    return floatval($row['avg_load']);
}, $loadTrend)) ?>;

// Create gradient
const ctx = document.getElementById('loadTrendChart').getContext('2d');
const gradient = ctx.createLinearGradient(0, 0, 0, 350);
gradient.addColorStop(0, 'rgba(220, 38, 38, 0.3)');
gradient.addColorStop(1, 'rgba(220, 38, 38, 0.01)');

const gradientAvg = ctx.createLinearGradient(0, 0, 0, 350);
gradientAvg.addColorStop(0, 'rgba(59, 130, 246, 0.3)');
gradientAvg.addColorStop(1, 'rgba(59, 130, 246, 0.01)');

// Create chart
new Chart(ctx, {
    type: 'line',
    data: {
        labels: dates,
        datasets: [
            {
                label: 'Total Load (MW)',
                data: loads,
                borderColor: '#dc2626',
                backgroundColor: gradient,
                borderWidth: 3,
                tension: 0.4,
                fill: true,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: '#dc2626',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointHoverBackgroundColor: '#dc2626',
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 3
            },
            {
                label: 'Average Load (MW)',
                data: avgLoads,
                borderColor: '#3b82f6',
                backgroundColor: gradientAvg,
                borderWidth: 2,
                borderDash: [5, 5],
                tension: 0.4,
                fill: true,
                pointRadius: 4,
                pointHoverRadius: 6,
                pointBackgroundColor: '#3b82f6',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            mode: 'index',
            intersect: false
        },
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: {
                    usePointStyle: true,
                    padding: 20,
                    font: {
                        size: 13,
                        weight: '600'
                    }
                }
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                padding: 12,
                titleFont: {
                    size: 14,
                    weight: 'bold'
                },
                bodyFont: {
                    size: 13
                },
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + context.parsed.y.toFixed(2) + ' MW';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Load (MW)',
                    font: {
                        size: 14,
                        weight: 'bold'
                    }
                },
                ticks: {
                    callback: function(value) {
                        return value.toFixed(1) + ' MW';
                    },
                    font: {
                        size: 12
                    }
                },
                grid: {
                    color: 'rgba(0, 0, 0, 0.05)',
                    drawBorder: false
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Date',
                    font: {
                        size: 14,
                        weight: 'bold'
                    }
                },
                ticks: {
                    font: {
                        size: 12
                    },
                    maxRotation: 45,
                    minRotation: 45
                },
                grid: {
                    display: false
                }
            }
        }
    }
});
</script>
<?php endif; ?>

<?php require __DIR__ . '/../layout/footer.php'; ?>
