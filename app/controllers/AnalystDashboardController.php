<?php
/**
 * UL3 Analyst Dashboard Controller
 * Path: /app/controllers/AnalystDashboardController.php
 * 
 * Functions:
 * - Analytics overview
 * - System health monitoring
 * - Pending correction concurrence
 * - Report creation
 */

require __DIR__ . '/../config/auth.php';
require __DIR__ . '/../config/constants.php';

// Enforce UL3 role
Guard::requireAnalyst();

$user = Auth::user();
$db = Database::connect();

// ========== SYSTEM STATISTICS ==========

// Total Load Data Entries (Today)
$entriesStmt = $db->query("
    SELECT 
        (SELECT COUNT(*) FROM fdr11kv_data WHERE entry_date = CURDATE()) as kv11_entries,
        (SELECT COUNT(*) FROM fdr33kv_data WHERE entry_date = CURDATE()) as kv33_entries
");
$entries = $entriesStmt->fetch(PDO::FETCH_ASSOC);

// Data Completeness (11kV)
$completenessStmt = $db->query("
    SELECT 
        COUNT(DISTINCT f.fdr11kv_code) as total_feeders,
        COUNT(DISTINCT CASE 
            WHEN d.entry_date = CURDATE() 
            THEN CONCAT(d.Fdr11kv_code, '-', d.entry_hour) 
        END) as today_entries
    FROM fdr11kv f
    LEFT JOIN fdr11kv_data d ON d.Fdr11kv_code = f.fdr11kv_code AND d.entry_date = CURDATE()
");
$completeness = $completenessStmt->fetch(PDO::FETCH_ASSOC);
$requiredEntries = $completeness['total_feeders'] * 24;
$completionRate = $requiredEntries > 0 ? round(($completeness['today_entries'] / $requiredEntries) * 100, 1) : 0;

// Pending Corrections Count
$pendingStmt = $db->query("
    SELECT COUNT(*) as count 
    FROM load_corrections 
    WHERE status = 'PENDING'
");
$pendingCorrections = $pendingStmt->fetchColumn();

// Total Interruptions (This Month)
$interruptionsStmt = $db->query("
    SELECT COUNT(*) as count 
    FROM interruptions 
    WHERE MONTH(datetime_out) = MONTH(CURDATE()) 
      AND YEAR(datetime_out) = YEAR(CURDATE())
");
$interruptions = $interruptionsStmt->fetchColumn();

// Total Complaints (Pending)
$complaintsStmt = $db->query("
    SELECT COUNT(*) as count 
    FROM complaint_log 
    WHERE status IN ('PENDING', 'ASSIGNED', 'IN_PROGRESS')
");
$complaints = $complaintsStmt->fetchColumn();

// ========== LOAD TRENDS (Last 7 Days) ==========
$trendsStmt = $db->query("
    SELECT 
        entry_date,
        SUM(load_read) as total_load,
        COUNT(*) as entries
    FROM fdr11kv_data
    WHERE entry_date >= date('now', '-7 days')
    GROUP BY entry_date
    ORDER BY entry_date
");
$loadTrends = $trendsStmt->fetchAll(PDO::FETCH_ASSOC);

// ========== TOP FEEDERS BY LOAD (Today) ==========
$topFeedersStmt = $db->query("
    SELECT 
        f.fdr11kv_name,
        SUM(d.load_read) as total_load,
        AVG(d.load_read) as avg_load,
        MAX(d.load_read) as peak_load
    FROM fdr11kv_data d
    INNER JOIN fdr11kv f ON f.fdr11kv_code = d.Fdr11kv_code
    WHERE d.entry_date = CURDATE() AND d.load_read > 0
    GROUP BY d.Fdr11kv_code, f.fdr11kv_name
    ORDER BY total_load DESC
    LIMIT 10
");
$topFeeders = $topFeedersStmt->fetchAll(PDO::FETCH_ASSOC);

// ========== RECENT CORRECTIONS ==========
$recentCorrectionsStmt = $db->prepare("
    SELECT 
        c.id,
        c.feeder_code,
        c.entry_date,
        c.entry_hour,
        c.field_to_correct,
        c.status,
        c.requested_by,
        c.requested_at,
        s.staff_name
    FROM load_corrections c
    LEFT JOIN staff_details s ON s.payroll_id = c.requested_by
    WHERE c.status = 'PENDING'
    ORDER BY c.requested_at DESC
    LIMIT 5
");
$recentCorrectionsStmt->execute();
$recentCorrections = $recentCorrectionsStmt->fetchAll(PDO::FETCH_ASSOC);

// Load views
require __DIR__ . '/../views/layout/header.php';
require __DIR__ . '/../views/layout/sidebar.php';
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
    margin-bottom: 24px;
}

.page-title {
    font-size: 28px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 8px;
}

.page-subtitle {
    font-size: 14px;
    color: #6b7280;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.stat-card {
    background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: linear-gradient(135deg, #0b3a82 0%, #1e40af 100%);
}

.stat-icon {
    font-size: 32px;
    margin-bottom: 12px;
}

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
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-trend {
    font-size: 12px;
    margin-top: 8px;
}

.stat-trend.up {
    color: #059669;
}

.stat-trend.down {
    color: #dc2626;
}

.dashboard-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 24px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    margin-bottom: 22px;
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 2px solid #f3f4f6;
}

.card-title {
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 10px;
}

.card-action {
    font-size: 13px;
    color: #0b3a82;
    text-decoration: none;
    font-weight: 600;
    transition: color 0.2s ease;
}

.card-action:hover {
    color: #1e40af;
}

.table-responsive {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.data-table thead th {
    background: #f9fafb;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: #374151;
    border-bottom: 2px solid #e5e7eb;
}

.data-table tbody td {
    padding: 12px;
    border-bottom: 1px solid #f3f4f6;
}

.data-table tbody tr:hover {
    background: #f9fafb;
}

.status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.btn-primary {
    background: linear-gradient(135deg, #0b3a82 0%, #1e40af 100%);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    transition: transform 0.2s ease;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(11,58,130,0.3);
}

.alert {
    padding: 16px 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.alert-warning {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-left: 4px solid #f59e0b;
    color: #92400e;
}

.alert-info {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    border-left: 4px solid #3b82f6;
    color: #1e40af;
}

.chart-container {
    background: #f9fafb;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #9ca3af;
}

.empty-state-icon {
    font-size: 48px;
    margin-bottom: 16px;
}

@media (max-width: 900px) {
    .main-content {
        margin-left: 0;
        padding: 12px;
    }
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="main-content">

    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">📊 Analyst Dashboard</h1>
        <p class="page-subtitle">Welcome back, <?= htmlspecialchars($user['staff_name']) ?> | System Analytics & Monitoring</p>
    </div>

    <!-- Data Completeness Alert -->
    <?php if ($completionRate < 80): ?>
    <div class="alert alert-warning">
        <span style="font-size: 24px;">⚠️</span>
        <div>
            <strong>Data Completeness Alert:</strong> Today's data is only <?= $completionRate ?>% complete. 
            <?= $completeness['today_entries'] ?> of <?= $requiredEntries ?> required entries have been logged.
        </div>
    </div>
    <?php endif; ?>

    <!-- Pending Actions Alert -->
    <?php if ($pendingCorrections > 0): ?>
    <div class="alert alert-info">
        <span style="font-size: 24px;">🔔</span>
        <div>
            <strong>Action Required:</strong> You have <?= $pendingCorrections ?> correction request(s) pending your concurrence.
            <a href="index.php?page=corrections&action=analyst-review" style="color: #1e40af; font-weight: 600; margin-left: 8px;">Review Now →</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Key Metrics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-value"><?= $completionRate ?>%</div>
            <div class="stat-label">Data Completeness</div>
            <div class="stat-trend <?= $completionRate >= 80 ? 'up' : 'down' ?>">
                <?= $completeness['today_entries'] ?> / <?= $requiredEntries ?> entries
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">⚡</div>
            <div class="stat-value"><?= number_format($entries['kv11_entries'] + $entries['kv33_entries']) ?></div>
            <div class="stat-label">Today's Entries</div>
            <div class="stat-trend">
                11kV: <?= $entries['kv11_entries'] ?> | 33kV: <?= $entries['kv33_entries'] ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">🔍</div>
            <div class="stat-value"><?= $pendingCorrections ?></div>
            <div class="stat-label">Pending Concurrence</div>
            <div class="stat-trend">
                Correction requests awaiting review
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">⛔</div>
            <div class="stat-value"><?= $interruptions ?></div>
            <div class="stat-label">Interruptions (MTD)</div>
            <div class="stat-trend">
                Month-to-date interruption count
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">📢</div>
            <div class="stat-value"><?= $complaints ?></div>
            <div class="stat-label">Active Complaints</div>
            <div class="stat-trend">
                Pending resolution
            </div>
        </div>
    </div>

    <!-- Load Trends Chart -->
    <div class="dashboard-card">
        <div class="card-header">
            <div class="card-title">
                <span>📈</span> Load Trends (Last 7 Days)
            </div>
        </div>
        <div class="chart-container">
            <canvas id="loadTrendsChart" height="80"></canvas>
        </div>
    </div>

    <!-- Recent Corrections Pending Concurrence -->
    <div class="dashboard-card">
        <div class="card-header">
            <div class="card-title">
                <span>🔍</span> Pending Correction Requests
            </div>
            <a href="index.php?page=corrections&action=analyst-review" class="card-action">View All →</a>
        </div>

        <?php if (empty($recentCorrections)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">✅</div>
            <p>No pending correction requests at this time.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Request ID</th>
                        <th>Feeder</th>
                        <th>Date/Hour</th>
                        <th>Field</th>
                        <th>Requested By</th>
                        <th>Requested At</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentCorrections as $corr): ?>
                    <tr>
                        <td>#<?= $corr['id'] ?></td>
                        <td><?= htmlspecialchars($corr['feeder_code']) ?></td>
                        <td><?= date('Y-m-d', strtotime($corr['entry_date'])) ?> | Hour <?= $corr['entry_hour'] ?></td>
                        <td><?= htmlspecialchars($corr['field_to_correct']) ?></td>
                        <td><?= htmlspecialchars($corr['staff_name']) ?></td>
                        <td><?= date('M d, H:i', strtotime($corr['requested_at'])) ?></td>
                        <td><span class="status-badge status-pending">Pending</span></td>
                        <td>
                            <a href="index.php?page=corrections&action=analyst-review" class="btn-primary" style="padding: 6px 14px; font-size: 12px;">
                                Review
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Top Feeders by Load -->
    <div class="dashboard-card">
        <div class="card-header">
            <div class="card-title">
                <span>🏆</span> Top 10 Feeders by Load (Today)
            </div>
        </div>

        <?php if (empty($topFeeders)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📊</div>
            <p>No load data available for today.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Feeder Name</th>
                        <th>Total Load (MW)</th>
                        <th>Average Load (MW)</th>
                        <th>Peak Load (MW)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $rank = 1; foreach ($topFeeders as $feeder): ?>
                    <tr>
                        <td><?= $rank++ ?></td>
                        <td><?= htmlspecialchars($feeder['fdr11kv_name']) ?></td>
                        <td><?= number_format($feeder['total_load'], 2) ?></td>
                        <td><?= number_format($feeder['avg_load'], 2) ?></td>
                        <td><?= number_format($feeder['peak_load'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Load Trends Chart
const trendDates = <?= json_encode(array_column($loadTrends, 'entry_date')) ?>;
const trendLoads = <?= json_encode(array_column($loadTrends, 'total_load')) ?>;

new Chart(document.getElementById('loadTrendsChart'), {
    type: 'line',
    data: {
        labels: trendDates.map(d => new Date(d).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })),
        datasets: [{
            label: 'Total Load (MW)',
            data: trendLoads,
            fill: true,
            tension: 0.4,
            backgroundColor: 'rgba(11,58,130,0.1)',
            borderColor: '#0b3a82',
            borderWidth: 3,
            pointBackgroundColor: '#0b3a82',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 7
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Total Load: ' + context.parsed.y.toFixed(2) + ' MW';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                title: { display: true, text: 'Load (MW)' },
                grid: { color: 'rgba(0,0,0,0.05)' }
            },
            x: {
                grid: { display: false }
            }
        }
    }
});
</script>

<?php
require __DIR__ . '/../views/layout/footer.php';
