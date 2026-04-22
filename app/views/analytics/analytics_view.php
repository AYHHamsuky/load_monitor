<?php require __DIR__ . '/../layout/header.php'; ?>
<?php require __DIR__ . '/../layout/sidebar.php'; ?>

<div class="main-content">
    <div class="report-view-container">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>📊 <?= htmlspecialchars($report['report_name']) ?></h1>
                <p class="subtitle">
                    <?= htmlspecialchars($report['description']) ?>
                    <br>
                    <small>
                        Created by <?= htmlspecialchars($report['creator_name']) ?> 
                        on <?= date('F j, Y', strtotime($report['created_at'])) ?>
                    </small>
                </p>
            </div>
            <div class="header-actions">
                <a href="?page=analytics" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <button onclick="window.print()" class="btn-primary">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>

        <!-- Report Content Based on Type -->
        <div class="report-content">
            <?php if ($report['report_type'] === 'load_summary'): ?>
                <!-- Load Summary Report -->
                <div class="summary-cards">
                    <div class="summary-card">
                        <h3>Total 11kV Load</h3>
                        <p class="value"><?= number_format($report_data['summary']['total_11kv_load'], 2) ?> MW</p>
                    </div>
                    <div class="summary-card">
                        <h3>Total 33kV Load</h3>
                        <p class="value"><?= number_format($report_data['summary']['total_33kv_load'], 2) ?> MW</p>
                    </div>
                    <div class="summary-card">
                        <h3>Average 11kV Load</h3>
                        <p class="value"><?= number_format($report_data['summary']['avg_11kv_load'], 2) ?> MW</p>
                    </div>
                    <div class="summary-card">
                        <h3>Total Faults</h3>
                        <p class="value"><?= $report_data['summary']['total_faults'] ?></p>
                    </div>
                </div>

                <div class="data-table">
                    <h3>11kV Daily Summary</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Feeders</th>
                                <th>Total Load (MW)</th>
                                <th>Avg Load (MW)</th>
                                <th>Peak Load (MW)</th>
                                <th>Faults</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data['load_11kv'] as $row): ?>
                            <tr>
                                <td><?= date('M j, Y', strtotime($row['date'])) ?></td>
                                <td><?= $row['feeders'] ?></td>
                                <td><?= number_format($row['total_load'], 2) ?></td>
                                <td><?= number_format($row['avg_load'], 2) ?></td>
                                <td><?= number_format($row['peak_load'], 2) ?></td>
                                <td><?= $row['fault_count'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($report['report_type'] === 'interruption_analysis'): ?>
                <!-- Interruption Analysis Report -->
                <div class="data-table">
                    <h3>By Interruption Type</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Count</th>
                                <th>Total Duration</th>
                                <th>Avg Duration</th>
                                <th>Load Loss (MW)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_data['by_type'] as $row): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['interruption_type']) ?></td>
                                <td><?= $row['count'] ?></td>
                                <td><?= round($row['total_minutes'] / 60, 2) ?> hrs</td>
                                <td><?= round($row['avg_minutes'] / 60, 2) ?> hrs</td>
                                <td><?= number_format($row['total_load_loss'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($report['report_type'] === 'data_quality'): ?>
                <!-- Data Quality Report -->
                <div class="summary-cards">
                    <div class="summary-card">
                        <h3>11kV Completeness</h3>
                        <p class="value"><?= number_format($report_data['completeness_11kv'], 1) ?>%</p>
                    </div>
                    <div class="summary-card">
                        <h3>33kV Completeness</h3>
                        <p class="value"><?= number_format($report_data['completeness_33kv'], 1) ?>%</p>
                    </div>
                    <div class="summary-card">
                        <h3>11kV Expected</h3>
                        <p class="value"><?= number_format($report_data['expected_11kv']) ?></p>
                    </div>
                    <div class="summary-card">
                        <h3>11kV Actual</h3>
                        <p class="value"><?= number_format($report_data['actual_11kv']) ?></p>
                    </div>
                </div>

            <?php else: ?>
                <!-- Generic Report Display -->
                <div class="info-message">
                    <p>Report data generated successfully. Detailed visualization coming soon.</p>
                    <pre><?= json_encode($report_data, JSON_PRETTY_PRINT) ?></pre>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.report-view-container {
    padding: 30px;
    max-width: 1400px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f0f0f0;
}

.page-header h1 {
    color: #2c3e50;
    margin: 0 0 10px 0;
}

.subtitle {
    color: #6c757d;
    margin: 0;
    font-size: 14px;
}

.header-actions {
    display: flex;
    gap: 10px;
}

.btn-primary, .btn-secondary {
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: none;
    cursor: pointer;
}

.btn-primary {
    background: #667eea;
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.report-content {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.summary-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.summary-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 25px;
    border-radius: 12px;
    text-align: center;
}

.summary-card h3 {
    margin: 0 0 10px 0;
    font-size: 14px;
    opacity: 0.9;
}

.summary-card .value {
    margin: 0;
    font-size: 32px;
    font-weight: 700;
}

.data-table {
    margin-bottom: 30px;
}

.data-table h3 {
    margin: 0 0 15px 0;
    color: #2c3e50;
}

.data-table table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background: #f8f9fa;
    padding: 12px;
    text-align: left;
    font-size: 12px;
    font-weight: 700;
    color: #6c757d;
    text-transform: uppercase;
}

.data-table td {
    padding: 12px;
    border-bottom: 1px solid #f0f0f0;
}

@media print {
    .header-actions, .btn-primary, .btn-secondary {
        display: none;
    }
}
</style>

<?php require __DIR__ . '/../layout/footer.php'; ?>
