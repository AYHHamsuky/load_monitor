<?php require __DIR__ . '/../layout/header.php'; ?>
<?php require __DIR__ . '/../layout/sidebar.php'; ?>

<div class="main-content">
    <div class="dashboard-container">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>🔧 System Administration Dashboard</h1>
                <p class="subtitle">Complete system control and monitoring</p>
            </div>
            <div class="header-actions">
                <a href="?page=users" class="btn-primary">
                    <i class="fas fa-users"></i> Manage Users
                </a>
                <a href="?page=system-config" class="btn-secondary">
                    <i class="fas fa-cog"></i> System Config
                </a>
            </div>
        </div>

        <!-- Critical Alerts -->
        <?php if ($critical_issues > 0): ?>
        <div class="alert-panel critical">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>Critical Alert:</strong> <?= $critical_issues ?> system issue(s) require immediate attention.
                <?php if ($system_health < 70): ?>
                    <br>• System health is below 70% (<?= number_format($system_health, 1) ?>%)
                <?php endif; ?>
                <?php if ($pending_corrections > 10): ?>
                    <br>• High number of pending corrections (<?= $pending_corrections ?>)
                <?php endif; ?>
            </div>
        </div>
        <?php elseif ($system_health < 85): ?>
        <div class="alert-panel warning">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>Warning:</strong> System health is below optimal level (<?= number_format($system_health, 1) ?>%)
            </div>
        </div>
        <?php endif; ?>

        <!-- Key Metrics -->
        <div class="metrics-grid">
            <div class="metric-card health">
                <div class="metric-header">
                    <h4>System Health</h4>
                    <i class="fas fa-heartbeat"></i>
                </div>
                <div class="metric-value">
                    <h2><?= number_format($system_health, 1) ?>%</h2>
                </div>
                <div class="metric-footer">
                    <span class="badge <?= $system_health >= 85 ? 'success' : ($system_health >= 70 ? 'warning' : 'danger') ?>">
                        <?= $system_health >= 85 ? 'Excellent' : ($system_health >= 70 ? 'Good' : 'Critical') ?>
                    </span>
                </div>
            </div>

            <div class="metric-card users">
                <div class="metric-header">
                    <h4>Active Users</h4>
                    <i class="fas fa-users"></i>
                </div>
                <div class="metric-value">
                    <h2><?= $active_users ?> / <?= $total_users ?></h2>
                </div>
                <div class="metric-footer">
                    <span class="badge info"><?= number_format(($active_users/$total_users)*100, 0) ?>% Active</span>
                </div>
            </div>

            <div class="metric-card data">
                <div class="metric-header">
                    <h4>Data Quality</h4>
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="metric-value">
                    <h2><?= number_format($data_quality, 1) ?>%</h2>
                </div>
                <div class="metric-footer">
                    <span class="badge <?= $data_quality >= 80 ? 'success' : 'warning' ?>">
                        <?= $data_quality >= 80 ? 'Good' : 'Needs Improvement' ?>
                    </span>
                </div>
            </div>

            <div class="metric-card uptime">
                <div class="metric-header">
                    <h4>System Uptime</h4>
                    <i class="fas fa-server"></i>
                </div>
                <div class="metric-value">
                    <h2><?= number_format($system_uptime, 1) ?>%</h2>
                </div>
                <div class="metric-footer">
                    <span class="badge success">Operational</span>
                </div>
            </div>
        </div>

        <!-- Two Column Layout -->
        <div class="two-column-layout">
            <!-- Left Column -->
            <div class="left-column">
                <!-- User Statistics -->
                <div class="stats-card">
                    <div class="card-header">
                        <h3><i class="fas fa-users-cog"></i> User Distribution by Role</h3>
                    </div>
                    <div class="stats-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Role</th>
                                    <th>Total</th>
                                    <th>Active</th>
                                    <th>Inactive</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($user_stats as $stat): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($stat['role']) ?></strong></td>
                                    <td><?= $stat['count'] ?></td>
                                    <td><span class="badge success"><?= $stat['active_count'] ?></span></td>
                                    <td><span class="badge secondary"><?= $stat['count'] - $stat['active_count'] ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td><strong>TOTAL</strong></td>
                                    <td><strong><?= $total_users ?></strong></td>
                                    <td><strong><?= $active_users ?></strong></td>
                                    <td><strong><?= $total_users - $active_users ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- System Resources -->
                <div class="stats-card">
                    <div class="card-header">
                        <h3><i class="fas fa-database"></i> System Resources</h3>
                    </div>
                    <div class="resource-list">
                        <div class="resource-item">
                            <div class="resource-info">
                                <i class="fas fa-bolt"></i>
                                <span>11kV Feeders</span>
                            </div>
                            <strong><?= $total_11kv ?></strong>
                        </div>
                        <div class="resource-item">
                            <div class="resource-info">
                                <i class="fas fa-charging-station"></i>
                                <span>33kV Feeders</span>
                            </div>
                            <strong><?= $total_33kv ?></strong>
                        </div>
                        <div class="resource-item">
                            <div class="resource-info">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>ISS Locations</span>
                            </div>
                            <strong><?= $total_iss ?></strong>
                        </div>
                        <div class="resource-item">
                            <div class="resource-info">
                                <i class="fas fa-building"></i>
                                <span>Transmission Stations</span>
                            </div>
                            <strong><?= $total_ts ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Data Entry Activity -->
                <div class="stats-card">
                    <div class="card-header">
                        <h3><i class="fas fa-keyboard"></i> Data Entry (Today)</h3>
                    </div>
                    <div class="activity-bars">
                        <div class="activity-item">
                            <div class="activity-label">
                                <span>11kV Entries</span>
                                <strong><?= $entries_11kv_today ?></strong>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill blue" style="width: <?= min(($entries_11kv_today/($total_11kv*24))*100, 100) ?>%"></div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-label">
                                <span>33kV Entries</span>
                                <strong><?= $entries_33kv_today ?></strong>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill red" style="width: <?= min(($entries_33kv_today/($total_33kv*24))*100, 100) ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="right-column">
                <!-- Corrections Workflow -->
                <div class="stats-card">
                    <div class="card-header">
                        <h3><i class="fas fa-edit"></i> Corrections Workflow</h3>
                    </div>
                    <div class="workflow-stats">
                        <div class="workflow-item pending">
                            <div class="workflow-count"><?= $pending_corrections ?></div>
                            <div class="workflow-label">Pending Review</div>
                        </div>
                        <div class="workflow-item analyst">
                            <div class="workflow-count"><?= $analyst_approved ?></div>
                            <div class="workflow-label">Analyst Approved</div>
                        </div>
                        <div class="workflow-item approved">
                            <div class="workflow-count"><?= $manager_approved ?></div>
                            <div class="workflow-label">Manager Approved</div>
                        </div>
                        <div class="workflow-item rejected">
                            <div class="workflow-count"><?= $rejected ?></div>
                            <div class="workflow-label">Rejected</div>
                        </div>
                    </div>
                </div>

                <!-- Complaints Summary -->
                <div class="stats-card">
                    <div class="card-header">
                        <h3><i class="fas fa-bullhorn"></i> Complaints Summary</h3>
                    </div>
                    <div class="complaints-grid">
                        <?php foreach ($complaint_stats as $status => $count): ?>
                        <div class="complaint-item">
                            <div class="complaint-count"><?= $count ?></div>
                            <div class="complaint-status"><?= htmlspecialchars($status) ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Recent User Activity -->
                <div class="stats-card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-line"></i> Top Active Users (7 Days)</h3>
                    </div>
                    <div class="activity-list">
                        <?php foreach ($user_activity as $activity): ?>
                        <div class="user-activity-item">
                            <div class="user-info">
                                <div class="user-avatar">
                                    <?= strtoupper(substr($activity['staff_name'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="user-name"><?= htmlspecialchars($activity['staff_name']) ?></div>
                                    <div class="user-role"><?= htmlspecialchars($activity['role']) ?></div>
                                </div>
                            </div>
                            <div class="activity-count">
                                <strong><?= $activity['last_login'] ? date('M j', strtotime($activity['last_login'])) : 'Never' ?></strong>
                                <small><?= $activity['days_since_login'] !== null ? $activity['days_since_login'] . ' days ago' : '' ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="quick-actions">
                    <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                    <div class="action-buttons">
                        <a href="?page=users&action=create" class="action-btn">
                            <i class="fas fa-user-plus"></i>
                            <span>Add New User</span>
                        </a>
                        <a href="?page=audit-logs" class="action-btn">
                            <i class="fas fa-history"></i>
                            <span>View Audit Logs</span>
                        </a>
                        <a href="?page=system-config&action=backup" class="action-btn">
                            <i class="fas fa-database"></i>
                            <span>Backup Database</span>
                        </a>
                        <a href="?page=reports" class="action-btn">
                            <i class="fas fa-file-export"></i>
                            <span>Generate Report</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.dashboard-container {
    padding: 30px;
    max-width: 1600px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #f0f0f0;
}

.page-header h1 {
    color: #2c3e50;
    margin: 0 0 5px 0;
}

.subtitle {
    color: #6c757d;
    margin: 0;
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
    transition: all 0.3s;
}

.btn-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
}

.btn-secondary {
    background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
    color: white;
}

.alert-panel {
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 30px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.alert-panel.critical {
    background: #f8d7da;
    border-left: 4px solid #dc3545;
    color: #721c24;
}

.alert-panel.warning {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    color: #856404;
}

.alert-panel i {
    font-size: 24px;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.metric-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: transform 0.3s;
}

.metric-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.metric-card.health { border-top: 4px solid #28a745; }
.metric-card.users { border-top: 4px solid #007bff; }
.metric-card.data { border-top: 4px solid #17a2b8; }
.metric-card.uptime { border-top: 4px solid #6f42c1; }

.metric-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.metric-header h4 {
    margin: 0;
    color: #6c757d;
    font-size: 14px;
    font-weight: 600;
}

.metric-header i {
    font-size: 24px;
    opacity: 0.3;
}

.metric-value h2 {
    margin: 10px 0;
    font-size: 36px;
    color: #2c3e50;
}

.metric-footer {
    margin-top: 15px;
}

.badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.badge.success { background: #d4edda; color: #155724; }
.badge.warning { background: #fff3cd; color: #856404; }
.badge.danger { background: #f8d7da; color: #721c24; }
.badge.info { background: #d1ecf1; color: #0c5460; }
.badge.secondary { background: #e2e3e5; color: #383d41; }

.two-column-layout {
    display: grid;
    grid-template-columns: 1.2fr 1fr;
    gap: 20px;
}

.stats-card, .quick-actions {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 20px;
}

.card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.card-header h3 {
    margin: 0;
    color: #2c3e50;
    font-size: 16px;
}

.stats-table table {
    width: 100%;
    border-collapse: collapse;
}

.stats-table th {
    text-align: left;
    padding: 10px;
    background: #f8f9fa;
    font-size: 12px;
    color: #6c757d;
    font-weight: 600;
}

.stats-table td {
    padding: 12px 10px;
    border-bottom: 1px solid #f0f0f0;
}

.resource-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.resource-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.resource-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.resource-info i {
    font-size: 20px;
    color: #007bff;
}

.activity-bars {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.activity-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
    font-size: 14px;
}

.progress-bar {
    height: 12px;
    background: #e9ecef;
    border-radius: 6px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    transition: width 0.3s;
}

.progress-fill.blue { background: linear-gradient(90deg, #007bff 0%, #0056b3 100%); }
.progress-fill.red { background: linear-gradient(90deg, #dc3545 0%, #c82333 100%); }

.workflow-stats {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

.workflow-item {
    text-align: center;
    padding: 20px;
    border-radius: 8px;
}

.workflow-item.pending { background: #fff3cd; }
.workflow-item.analyst { background: #d1ecf1; }
.workflow-item.approved { background: #d4edda; }
.workflow-item.rejected { background: #f8d7da; }

.workflow-count {
    font-size: 32px;
    font-weight: 700;
    color: #2c3e50;
}

.workflow-label {
    font-size: 12px;
    color: #6c757d;
    margin-top: 5px;
}

.complaints-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

.complaint-item {
    text-align: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
}

.complaint-count {
    font-size: 24px;
    font-weight: 700;
    color: #2c3e50;
}

.complaint-status {
    font-size: 11px;
    color: #6c757d;
    margin-top: 5px;
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.user-activity-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 8px;
}

.user-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
}

.user-name {
    font-weight: 600;
    color: #2c3e50;
    font-size: 14px;
}

.user-role {
    font-size: 11px;
    color: #6c757d;
}

.quick-actions h3 {
    margin: 0 0 20px 0;
    color: #2c3e50;
    font-size: 16px;
}

.action-buttons {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

.action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    text-decoration: none;
    color: #2c3e50;
    transition: all 0.3s;
}

.action-btn:hover {
    background: #007bff;
    color: white;
    transform: translateY(-3px);
}

.action-btn i {
    font-size: 24px;
}

.action-btn span {
    font-size: 12px;
    font-weight: 600;
    text-align: center;
}

@media (max-width: 1200px) {
    .two-column-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .metrics-grid {
        grid-template-columns: 1fr;
    }
    
    .header-actions {
        flex-direction: column;
    }
    
    .workflow-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require __DIR__ . '/../layout/footer.php'; ?>
