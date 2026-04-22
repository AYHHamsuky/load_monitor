<?php
/**
 * UL4 Manager Dashboard Controller
 * Path: /app/controllers/ManagerDashboardController.php
 * 
 * Functions:
 * - Executive summary
 * - Final approval of corrections
 * - Management KPIs
 * - System performance overview
 */

require __DIR__ . '/../config/auth.php';
require __DIR__ . '/../config/constants.php';

// Enforce UL4 role
Guard::requireManager();

$user = Auth::user();
$db = Database::connect();

// ========== EXECUTIVE KPIs ==========

// System Health Score (based on data completeness)
$healthStmt = $db->query("
    SELECT 
        COUNT(DISTINCT f.fdr11kv_code) as total_feeders,
        COUNT(DISTINCT CASE 
            WHEN d.entry_date = CURDATE() 
            THEN CONCAT(d.Fdr11kv_code, '-', d.entry_hour) 
        END) as today_entries
    FROM fdr11kv f
    LEFT JOIN fdr11kv_data d ON d.Fdr11kv_code = f.fdr11kv_code AND d.entry_date = CURDATE()
");
$health = $healthStmt->fetch(PDO::FETCH_ASSOC);
$requiredEntries = $health['total_feeders'] * 24;
$systemHealth = $requiredEntries > 0 ? round(($health['today_entries'] / $requiredEntries) * 100, 1) : 0;

// Awaiting Approval Count
$awaitingStmt = $db->query("
    SELECT COUNT(*) as count 
    FROM load_corrections 
    WHERE status = 'ANALYST_APPROVED'
");
$awaitingApproval = $awaitingStmt->fetchColumn();

// Total Corrections This Month
$correctionsMonthStmt = $db->query("
    SELECT COUNT(*) as count 
    FROM load_corrections 
    WHERE MONTH(requested_at) = MONTH(CURDATE()) 
      AND YEAR(requested_at) = YEAR(CURDATE())
");
$correctionsMonth = $correctionsMonthStmt->fetchColumn();

// Critical Issues (High Priority Complaints)
$criticalStmt = $db->query("
    SELECT COUNT(*) as count 
    FROM complaint_log 
    WHERE priority IN ('HIGH', 'CRITICAL')
      AND status IN ('PENDING', 'ASSIGNED', 'IN_PROGRESS')
");
$criticalIssues = $criticalStmt->fetchColumn();

// Data Quality Score (% of entries without faults)
$qualityStmt = $db->query("
    SELECT 
        COUNT(*) as total_entries,
        SUM(CASE WHEN load_read > 0 THEN 1 ELSE 0 END) as quality_entries
    FROM fdr11kv_data
    WHERE entry_date >= date('now', '-7 days')
");
$quality = $qualityStmt->fetch(PDO::FETCH_ASSOC);
$dataQuality = $quality['total_entries'] > 0 
    ? round(($quality['quality_entries'] / $quality['total_entries']) * 100, 1) 
    : 0;

// System Uptime (% hours with supply)
$uptimeStmt = $db->query("
    SELECT 
        COUNT(*) as total_hours,
        SUM(CASE WHEN load_read > 0 THEN 1 ELSE 0 END) as supply_hours
    FROM fdr11kv_data
    WHERE entry_date >= date('now', '-7 days')
");
$uptime = $uptimeStmt->fetch(PDO::FETCH_ASSOC);
$systemUptime = $uptime['total_hours'] > 0 
    ? round(($uptime['supply_hours'] / $uptime['total_hours']) * 100, 1) 
    : 0;

// ========== PENDING APPROVALS ==========
$pendingApprovalsStmt = $db->prepare("
    SELECT 
        c.id,
        c.feeder_code,
        c.entry_date,
        c.entry_hour,
        c.field_to_correct,
        c.old_value,
        c.new_value,
        c.reason,
        c.requested_by,
        c.analyst_id,
        c.analyst_remarks,
        c.analyst_action_at,
        req.staff_name as requester_name,
        ana.staff_name as analyst_name
    FROM load_corrections c
    LEFT JOIN staff_details req ON req.payroll_id = c.requested_by
    LEFT JOIN staff_details ana ON ana.payroll_id = c.analyst_id
    WHERE c.status = 'ANALYST_APPROVED'
    ORDER BY c.analyst_action_at DESC
    LIMIT 10
");
$pendingApprovalsStmt->execute();
$pendingApprovals = $pendingApprovalsStmt->fetchAll(PDO::FETCH_ASSOC);

// ========== MONTHLY PERFORMANCE ==========
$monthlyStmt = $db->query("
    SELECT 
        DAY(entry_date) as day,
        SUM(load_read) as total_load,
        COUNT(*) as entries
    FROM fdr11kv_data
    WHERE MONTH(entry_date) = MONTH(CURDATE())
      AND YEAR(entry_date) = YEAR(CURDATE())
    GROUP BY DAY(entry_date)
    ORDER BY day
");
$monthlyPerformance = $monthlyStmt->fetchAll(PDO::FETCH_ASSOC);

// ========== SYSTEM ALERTS ==========
$alerts = [];

if ($systemHealth < 70) {
    $alerts[] = [
        'type' => 'danger',
        'icon' => '🚨',
        'message' => "Critical: System health at {$systemHealth}%"
    ];
} elseif ($systemHealth < 85) {
    $alerts[] = [
        'type' => 'warning',
        'icon' => '⚠️',
        'message' => "Warning: System health at {$systemHealth}%"
    ];
}

if ($awaitingApproval > 5) {
    $alerts[] = [
        'type' => 'warning',
        'icon' => '📋',
        'message' => "{$awaitingApproval} correction requests awaiting your approval"
    ];
}

if ($criticalIssues > 0) {
    $alerts[] = [
        'type' => 'danger',
        'icon' => '🔴',
        'message' => "{$criticalIssues} critical complaint(s) require attention"
    ];
}

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

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
    margin-bottom: 24px;
}

.kpi-card {
    background: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
    border-radius: 12px;
    padding: 24px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

.kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
}

.kpi-card.excellent::before {
    background: linear-gradient(90deg, #10b981 0%, #059669 100%);
}

.kpi-card.good::before {
    background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%);
}

.kpi-card.warning::before {
    background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%);
}

.kpi-card.critical::before {
    background: linear-gradient(90deg, #ef4444 0%, #dc2626 100%);
}

.kpi-icon {
    font-size: 36px;
    margin-bottom: 12px;
}

.kpi-value {
    font-size: 36px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 4px;
}

.kpi-label {
    font-size: 13px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.kpi-status {
    font-size: 12px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 12px;
    display: inline-block;
}

.kpi-status.excellent {
    background: #d1fae5;
    color: #065f46;
}

.kpi-status.good {
    background: #dbeafe;
    color: #1e40af;
}

.kpi-status.warning {
    background: #fef3c7;
    color: #92400e;
}

.kpi-status.critical {
    background: #fee2e2;
    color: #991b1b;
}

.alert {
    padding: 16px 20px;
    border-radius: 10px;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 14px;
}

.alert-danger {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    border-left: 4px solid #dc2626;
    color: #991b1b;
}

.alert-warning {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-left: 4px solid #f59e0b;
    color: #92400e;
}

.alert-success {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    border-left: 4px solid #10b981;
    color: #065f46;
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

.btn-approve {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-weight: 600;
    font-size: 12px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
}

.btn-approve:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(16,185,129,0.3);
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
    
    .kpi-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="main-content">

    <!-- Page Header -->
    <div class="page-header">
        <h1 class="page-title">🎯 Manager Dashboard</h1>
        <p class="page-subtitle">Welcome back, <?= htmlspecialchars($user['staff_name']) ?> | Executive Overview & Approvals</p>
    </div>

    <!-- System Alerts -->
    <?php if (!empty($alerts)): ?>
    <div style="margin-bottom: 24px;">
        <?php foreach ($alerts as $alert): ?>
        <div class="alert alert-<?= $alert['type'] ?>">
            <span style="font-size: 24px;"><?= $alert['icon'] ?></span>
            <strong><?= $alert['message'] ?></strong>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="alert alert-success">
        <span style="font-size: 24px;">✅</span>
        <strong>All Systems Operational</strong> - No critical issues detected
    </div>
    <?php endif; ?>

    <!-- Executive KPIs -->
    <div class="kpi-grid">
        <div class="kpi-card <?= $systemHealth >= 90 ? 'excellent' : ($systemHealth >= 75 ? 'good' : 'warning') ?>">
            <div class="kpi-icon">💚</div>
            <div class="kpi-value"><?= $systemHealth ?>%</div>
            <div class="kpi-label">System Health</div>
            <span class="kpi-status <?= $systemHealth >= 90 ? 'excellent' : ($systemHealth >= 75 ? 'good' : 'warning') ?>">
                <?= $systemHealth >= 90 ? 'Excellent' : ($systemHealth >= 75 ? 'Good' : 'Needs Attention') ?>
            </span>
        </div>

        <div class="kpi-card <?= $awaitingApproval > 5 ? 'warning' : 'good' ?>">
            <div class="kpi-icon">✅</div>
            <div class="kpi-value"><?= $awaitingApproval ?></div>
            <div class="kpi-label">Pending Approvals</div>
            <span class="kpi-status <?= $awaitingApproval > 5 ? 'warning' : 'good' ?>">
                <?= $awaitingApproval > 5 ? 'Action Required' : 'Under Control' ?>
            </span>
        </div>

        <div class="kpi-card <?= $dataQuality >= 90 ? 'excellent' : ($dataQuality >= 75 ? 'good' : 'warning') ?>">
            <div class="kpi-icon">📊</div>
            <div class="kpi-value"><?= $dataQuality ?>%</div>
            <div class="kpi-label">Data Quality</div>
            <span class="kpi-status <?= $dataQuality >= 90 ? 'excellent' : ($dataQuality >= 75 ? 'good' : 'warning') ?>">
                Last 7 Days
            </span>
        </div>

        <div class="kpi-card <?= $systemUptime >= 95 ? 'excellent' : ($systemUptime >= 85 ? 'good' : 'warning') ?>">
            <div class="kpi-icon">⚡</div>
            <div class="kpi-value"><?= $systemUptime ?>%</div>
            <div class="kpi-label">System Uptime</div>
            <span class="kpi-status <?= $systemUptime >= 95 ? 'excellent' : ($systemUptime >= 85 ? 'good' : 'warning') ?>">
                Last 7 Days
            </span>
        </div>

        <div class="kpi-card <?= $criticalIssues > 0 ? 'critical' : 'excellent' ?>">
            <div class="kpi-icon">🔴</div>
            <div class="kpi-value"><?= $criticalIssues ?></div>
            <div class="kpi-label">Critical Issues</div>
            <span class="kpi-status <?= $criticalIssues > 0 ? 'critical' : 'excellent' ?>">
                <?= $criticalIssues > 0 ? 'Immediate Attention' : 'None' ?>
            </span>
        </div>

        <div class="kpi-card good">
            <div class="kpi-icon">📋</div>
            <div class="kpi-value"><?= $correctionsMonth ?></div>
            <div class="kpi-label">Corrections (MTD)</div>
            <span class="kpi-status good">
                Month to Date
            </span>
        </div>
    </div>

    <!-- Pending Approvals -->
    <div class="dashboard-card">
        <div class="card-header">
            <div class="card-title">
                <span>✅</span> Corrections Awaiting Your Approval
            </div>
            <a href="index.php?page=corrections&action=manager-review" class="card-action">View All →</a>
        </div>

        <?php if (empty($pendingApprovals)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">✅</div>
            <p>No corrections awaiting approval at this time.</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Feeder</th>
                        <th>Date/Hour</th>
                        <th>Field</th>
                        <th>Old → New</th>
                        <th>Requester</th>
                        <th>Analyst</th>
                        <th>Concurred At</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingApprovals as $app): ?>
                    <tr>
                        <td>#<?= $app['id'] ?></td>
                        <td><?= htmlspecialchars($app['feeder_code']) ?></td>
                        <td><?= date('Y-m-d', strtotime($app['entry_date'])) ?> | H<?= $app['entry_hour'] ?></td>
                        <td><?= htmlspecialchars($app['field_to_correct']) ?></td>
                        <td><?= htmlspecialchars($app['old_value']) ?> → <?= htmlspecialchars($app['new_value']) ?></td>
                        <td><?= htmlspecialchars($app['requester_name']) ?></td>
                        <td><?= htmlspecialchars($app['analyst_name']) ?></td>
                        <td><?= date('M d, H:i', strtotime($app['analyst_action_at'])) ?></td>
                        <td>
                            <a href="index.php?page=corrections&action=manager-review" class="btn-approve">
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

    <!-- Monthly Performance Chart -->
    <div class="dashboard-card">
        <div class="card-header">
            <div class="card-title">
                <span>📈</span> Monthly Load Performance
            </div>
        </div>
        <div class="chart-container">
            <canvas id="monthlyPerformanceChart" height="80"></canvas>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Monthly Performance Chart
const monthlyDays = <?= json_encode(array_column($monthlyPerformance, 'day')) ?>;
const monthlyLoads = <?= json_encode(array_column($monthlyPerformance, 'total_load')) ?>;

new Chart(document.getElementById('monthlyPerformanceChart'), {
    type: 'bar',
    data: {
        labels: monthlyDays.map(d => 'Day ' + d),
        datasets: [{
            label: 'Total Load (MW)',
            data: monthlyLoads,
            backgroundColor: 'rgba(11,58,130,0.8)',
            borderColor: '#0b3a82',
            borderWidth: 1,
            borderRadius: 6
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
