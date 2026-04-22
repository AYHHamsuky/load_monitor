<?php require __DIR__ . '/../layout/header.php'; ?>
<?php require __DIR__ . '/../layout/sidebar.php'; ?>

<div class="main-content">
    <div class="analytics-container">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>📊 Analytics & Reports</h1>
                <p class="subtitle">Advanced data analysis and custom reporting</p>
            </div>
            <div class="header-actions">
                <?php if ($user['role'] === 'UL3'): ?>
                    <a href="?page=analytics&action=create" class="btn-primary">
                        <i class="fas fa-plus"></i> Create New Report
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Report Generation -->
        <div class="quick-reports-section">
            <h3><i class="fas fa-bolt"></i> Quick Reports (Generate Instantly)</h3>
            <div class="quick-reports-grid">
                <a href="?page=analytics&action=generate&type=load_summary&date_from=<?= date('Y-m-01') ?>&date_to=<?= date('Y-m-d') ?>" class="quick-report-card">
                    <div class="icon blue">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <div class="content">
                        <h4>Load Summary</h4>
                        <p>Comprehensive load analysis (11kV & 33kV)</p>
                    </div>
                    <i class="fas fa-arrow-right arrow"></i>
                </a>

                <a href="?page=analytics&action=generate&type=interruption_analysis&date_from=<?= date('Y-m-01') ?>&date_to=<?= date('Y-m-d') ?>" class="quick-report-card">
                    <div class="icon red">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="content">
                        <h4>Interruption Analysis</h4>
                        <p>Outage patterns and trends</p>
                    </div>
                    <i class="fas fa-arrow-right arrow"></i>
                </a>

                <a href="?page=analytics&action=generate&type=data_quality&date_from=<?= date('Y-m-01') ?>&date_to=<?= date('Y-m-d') ?>" class="quick-report-card">
                    <div class="icon green">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="content">
                        <h4>Data Quality Report</h4>
                        <p>Data completeness and accuracy</p>
                    </div>
                    <i class="fas fa-arrow-right arrow"></i>
                </a>

                <a href="?page=analytics&action=generate&type=peak_demand&date_from=<?= date('Y-m-01') ?>&date_to=<?= date('Y-m-d') ?>" class="quick-report-card">
                    <div class="icon orange">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="content">
                        <h4>Peak Demand Analysis</h4>
                        <p>Peak load patterns by hour</p>
                    </div>
                    <i class="fas fa-arrow-right arrow"></i>
                </a>

                <a href="?page=analytics&action=generate&type=feeder_performance&date_from=<?= date('Y-m-01') ?>&date_to=<?= date('Y-m-d') ?>" class="quick-report-card">
                    <div class="icon purple">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                    <div class="content">
                        <h4>Feeder Performance</h4>
                        <p>Individual feeder metrics</p>
                    </div>
                    <i class="fas fa-arrow-right arrow"></i>
                </a>

                <a href="?page=analytics&action=generate&type=complaint_trends&date_from=<?= date('Y-m-01') ?>&date_to=<?= date('Y-m-d') ?>" class="quick-report-card">
                    <div class="icon teal">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <div class="content">
                        <h4>Complaint Trends</h4>
                        <p>Customer complaint analysis</p>
                    </div>
                    <i class="fas fa-arrow-right arrow"></i>
                </a>
            </div>
        </div>

        <!-- Saved Reports -->
        <div class="saved-reports-section">
            <h3><i class="fas fa-save"></i> Saved Reports</h3>
            
            <?php if (empty($reports)): ?>
                <div class="empty-state">
                    <i class="fas fa-file-alt fa-4x"></i>
                    <h4>No Saved Reports</h4>
                    <p>Create your first custom report to save it for future reference</p>
                    <?php if ($user['role'] === 'UL3'): ?>
                        <a href="?page=analytics&action=create" class="btn-primary">
                            <i class="fas fa-plus"></i> Create Report
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="reports-table-card">
                    <table class="reports-table">
                        <thead>
                            <tr>
                                <th>Report Name</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th>Created By</th>
                                <th>Date Created</th>
                                <th>Visibility</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reports as $report): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($report['report_name']) ?></strong>
                                </td>
                                <td>
                                    <span class="type-badge">
                                        <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $report['report_type']))) ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?= htmlspecialchars($report['description']) ?></small>
                                </td>
                                <td><?= htmlspecialchars($report['creator_name']) ?></td>
                                <td>
                                    <small><?= date('M j, Y', strtotime($report['created_at'])) ?></small>
                                </td>
                                <td>
                                    <span class="visibility-badge <?= $report['is_public'] ? 'public' : 'private' ?>">
                                        <i class="fas fa-<?= $report['is_public'] ? 'globe' : 'lock' ?>"></i>
                                        <?= $report['is_public'] ? 'Public' : 'Private' ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="?page=analytics&action=view&id=<?= $report['id'] ?>" 
                                       class="btn-action view">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <?php if ($user['role'] === 'UL3' && $report['created_by'] === $user['payroll_id']): ?>
                                        <button onclick="deleteReport(<?= $report['id'] ?>)" 
                                                class="btn-action delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.analytics-container {
    padding: 30px;
    max-width: 1600px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
}

.page-header h1 {
    color: #2c3e50;
    margin: 0 0 5px 0;
}

.subtitle {
    color: #6c757d;
    margin: 0;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 12px 24px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.quick-reports-section, .saved-reports-section {
    margin-bottom: 50px;
}

.quick-reports-section h3, .saved-reports-section h3 {
    color: #2c3e50;
    margin-bottom: 25px;
    font-size: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.quick-reports-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.quick-report-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    display: flex;
    align-items: center;
    gap: 20px;
    text-decoration: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: all 0.3s;
    position: relative;
    overflow: hidden;
}

.quick-report-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.1);
}

.quick-report-card .icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    flex-shrink: 0;
}

.quick-report-card .icon.blue { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
.quick-report-card .icon.red { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; }
.quick-report-card .icon.green { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; }
.quick-report-card .icon.orange { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; }
.quick-report-card .icon.purple { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); color: #495057; }
.quick-report-card .icon.teal { background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); color: #495057; }

.quick-report-card .content {
    flex: 1;
}

.quick-report-card h4 {
    margin: 0 0 5px 0;
    color: #2c3e50;
    font-size: 16px;
}

.quick-report-card p {
    margin: 0;
    color: #6c757d;
    font-size: 13px;
}

.quick-report-card .arrow {
    color: #cbd5e0;
    font-size: 20px;
    transition: all 0.3s;
}

.quick-report-card:hover .arrow {
    color: #667eea;
    transform: translateX(5px);
}

.empty-state {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.empty-state i {
    color: #dee2e6;
    margin-bottom: 20px;
}

.empty-state h4 {
    color: #495057;
    margin-bottom: 10px;
}

.empty-state p {
    color: #6c757d;
    margin-bottom: 25px;
}

.reports-table-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.reports-table {
    width: 100%;
    border-collapse: collapse;
}

.reports-table thead {
    background: #f8f9fa;
}

.reports-table th {
    padding: 15px;
    text-align: left;
    font-size: 12px;
    font-weight: 700;
    color: #6c757d;
    text-transform: uppercase;
}

.reports-table td {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
}

.type-badge {
    padding: 4px 12px;
    background: #e7f3ff;
    color: #0c5460;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.visibility-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.visibility-badge.public {
    background: #d4edda;
    color: #155724;
}

.visibility-badge.private {
    background: #fff3cd;
    color: #856404;
}

.btn-action {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-action.view {
    background: #007bff;
    color: white;
}

.btn-action.delete {
    background: #dc3545;
    color: white;
}

.btn-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

@media (max-width: 768px) {
    .quick-reports-grid {
        grid-template-columns: 1fr;
    }
    
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
}
</style>

<script>
function deleteReport(reportId) {
    if (!confirm('Are you sure you want to delete this report?')) {
        return;
    }
    
    fetch('/public/ajax/analytics_delete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `report_id=${reportId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
