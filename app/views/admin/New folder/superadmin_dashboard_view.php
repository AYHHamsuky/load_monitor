<?php
// app/views/superadmin/dashboard.php
require __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/sidebar.php';
?>

<div class="main-content">
    <div class="superadmin-container">
        
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>🔐 Super Admin Dashboard</h1>
                <p class="subtitle">System Security & Control Center</p>
            </div>
            <div class="threat-indicator" style="background: <?= $threat_color ?>;">
                <i class="fas fa-shield-alt"></i>
                <span>Threat Level: <?= $threat_level ?></span>
            </div>
        </div>

        <!-- System Status Badges -->
        <div class="status-badges">
            <div class="status-badge <?= $logins_enabled ? 'success' : 'danger' ?>">
                <i class="fas fa-<?= $logins_enabled ? 'unlock' : 'lock' ?>"></i>
                Logins: <?= $logins_enabled ? 'Enabled' : 'Disabled' ?>
            </div>
            <div class="status-badge <?= $maintenance_mode ? 'warning' : 'success' ?>">
                <i class="fas fa-tools"></i>
                Maintenance: <?= $maintenance_mode ? 'Active' : 'Normal' ?>
            </div>
        </div>

        <!-- Security Overview -->
        <div class="section-title">
            <h2><i class="fas fa-shield-virus"></i> Security Overview (Last 24 Hours)</h2>
        </div>

        <div class="metrics-grid">
            <div class="metric-card critical">
                <div class="metric-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="metric-details">
                    <h3><?= $securityStats['critical'] ?? 0 ?></h3>
                    <p>Critical Events</p>
                </div>
            </div>

            <div class="metric-card warning">
                <div class="metric-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <div class="metric-details">
                    <h3><?= $failed_logins ?></h3>
                    <p>Failed Logins</p>
                </div>
            </div>

            <div class="metric-card danger">
                <div class="metric-icon">
                    <i class="fas fa-ban"></i>
                </div>
                <div class="metric-details">
                    <h3><?= $blacklisted_ips ?></h3>
                    <p>Blacklisted IPs</p>
                </div>
            </div>

            <div class="metric-card info">
                <div class="metric-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="metric-details">
                    <h3><?= $active_sessions ?></h3>
                    <p>Active Sessions</p>
                </div>
            </div>

            <div class="metric-card <?= $integrity_issues > 0 ? 'danger' : 'success' ?>">
                <div class="metric-icon">
                    <i class="fas fa-file-code"></i>
                </div>
                <div class="metric-details">
                    <h3><?= $integrity_issues ?></h3>
                    <p>File Integrity Issues</p>
                </div>
            </div>

            <div class="metric-card <?= $system_health ? ($system_health['status'] === 'HEALTHY' ? 'success' : 'warning') : 'secondary' ?>">
                <div class="metric-icon">
                    <i class="fas fa-heartbeat"></i>
                </div>
                <div class="metric-details">
                    <h3><?= $system_health ? $system_health['health_score'] . '%' : 'N/A' ?></h3>
                    <p>System Health</p>
                </div>
            </div>
        </div>

        <!-- System Health Details -->
        <?php if ($system_health): ?>
        <div class="section-title">
            <h2><i class="fas fa-server"></i> System Health</h2>
        </div>

        <div class="health-grid">
            <div class="health-card">
                <div class="health-label">CPU Usage</div>
                <div class="health-bar">
                    <div class="health-fill cpu" style="width: <?= $system_health['cpu_usage'] ?>%">
                        <?= number_format($system_health['cpu_usage'], 1) ?>%
                    </div>
                </div>
            </div>

            <div class="health-card">
                <div class="health-label">Memory Usage</div>
                <div class="health-bar">
                    <div class="health-fill memory" style="width: <?= $system_health['memory_usage'] ?>%">
                        <?= number_format($system_health['memory_usage'], 1) ?>%
                    </div>
                </div>
            </div>

            <div class="health-card">
                <div class="health-label">Disk Usage</div>
                <div class="health-bar">
                    <div class="health-fill disk" style="width: <?= $system_health['disk_usage'] ?>%">
                        <?= number_format($system_health['disk_usage'], 1) ?>%
                    </div>
                </div>
            </div>

            <div class="health-card">
                <div class="health-label">Database Size</div>
                <div class="health-value">
                    <?= number_format($system_health['database_size'] / (1024*1024), 2) ?> MB
                </div>
            </div>

            <div class="health-card">
                <div class="health-label">Errors Today</div>
                <div class="health-value <?= $system_health['error_count'] > 10 ? 'text-danger' : '' ?>">
                    <?= $system_health['error_count'] ?>
                </div>
            </div>

            <div class="health-card">
                <div class="health-label">Warnings Today</div>
                <div class="health-value <?= $system_health['warning_count'] > 20 ? 'text-warning' : '' ?>">
                    <?= $system_health['warning_count'] ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Critical Alerts -->
        <?php if (!empty($critical_alerts)): ?>
        <div class="section-title">
            <h2><i class="fas fa-bell"></i> Critical Alerts</h2>
        </div>
        <div class="alerts-container">
            <?php foreach ($critical_alerts as $alert): ?>
            <div class="alert alert-<?= strtolower($alert['severity']) ?>">
                <i class="fas fa-exclamation-triangle"></i>
                <strong><?= $alert['category'] ?>:</strong> <?= $alert['message'] ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="section-title">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
        </div>

        <div class="actions-grid">
            <button onclick="forceLogoutAll()" class="action-btn danger">
                <i class="fas fa-sign-out-alt"></i>
                <span>Force Logout All</span>
            </button>

            <button onclick="toggleLogins()" class="action-btn warning">
                <i class="fas fa-<?= $logins_enabled ? 'lock' : 'unlock' ?>"></i>
                <span><?= $logins_enabled ? 'Disable' : 'Enable' ?> Logins</span>
            </button>

            <button onclick="runIntegrityCheck()" class="action-btn info">
                <i class="fas fa-shield-alt"></i>
                <span>Run Integrity Check</span>
            </button>

            <button onclick="generateSecurityReport()" class="action-btn primary">
                <i class="fas fa-file-pdf"></i>
                <span>Security Report</span>
            </button>

            <a href="?page=superadmin&action=users" class="action-btn success">
                <i class="fas fa-users-cog"></i>
                <span>Manage All Users</span>
            </a>

            <a href="?page=superadmin&action=audit-logs" class="action-btn secondary">
                <i class="fas fa-history"></i>
                <span>View Audit Logs</span>
            </a>
        </div>

        <!-- Active Sessions -->
        <div class="section-title">
            <h2><i class="fas fa-user-clock"></i> Active Sessions (<?= count($active_user_sessions) ?>)</h2>
            <button onclick="refreshSessions()" class="btn-refresh">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>

        <div class="table-card">
            <table class="sessions-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>IP Address</th>
                        <th>Last Activity</th>
                        <th>Duration</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="sessions-tbody">
                    <?php foreach ($active_user_sessions as $session): ?>
                    <tr>
                        <td><?= htmlspecialchars($session['staff_name']) ?></td>
                        <td><span class="role-badge"><?= $session['role'] ?></span></td>
                        <td><?= htmlspecialchars($session['ip_address']) ?></td>
                        <td><?= date('H:i:s', strtotime($session['last_activity'])) ?></td>
                        <td><?= gmdate('H:i:s', $session['session_duration']) ?></td>
                        <td>
                            <button onclick="killSession('<?= $session['session_id'] ?>')" class="btn-kill">
                                <i class="fas fa-times"></i> Kill
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Recent Security Events -->
        <div class="section-title">
            <h2><i class="fas fa-exclamation-circle"></i> Recent Security Events</h2>
        </div>

        <div class="events-list">
            <?php foreach (array_slice($recent_security, 0, 10) as $event): ?>
            <div class="event-item severity-<?= strtolower($event['severity']) ?>">
                <div class="event-icon">
                    <?php
                    $icons = [
                        'FAILED_LOGIN' => 'fa-lock',
                        'BRUTE_FORCE' => 'fa-user-times',
                        'SQL_INJECTION' => 'fa-database',
                        'XSS_ATTEMPT' => 'fa-code',
                        'SUSPICIOUS_IP' => 'fa-eye'
                    ];
                    $icon = $icons[$event['event_type']] ?? 'fa-exclamation-triangle';
                    ?>
                    <i class="fas <?= $icon ?>"></i>
                </div>
                <div class="event-details">
                    <div class="event-type"><?= str_replace('_', ' ', $event['event_type']) ?></div>
                    <div class="event-info">
                        IP: <?= htmlspecialchars($event['ip_address']) ?> | 
                        <?= date('M j, Y H:i:s', strtotime($event['created_at'])) ?>
                    </div>
                </div>
                <div class="event-severity">
                    <span class="severity-badge <?= strtolower($event['severity']) ?>">
                        <?= $event['severity'] ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- User Distribution -->
        <div class="section-title">
            <h2><i class="fas fa-chart-pie"></i> User Distribution</h2>
        </div>

        <div class="distribution-grid">
            <?php foreach ($user_distribution as $dist): ?>
            <div class="dist-card">
                <div class="dist-header">
                    <h3><?= $dist['role'] ?></h3>
                </div>
                <div class="dist-body">
                    <div class="dist-stat">
                        <span class="dist-label">Total:</span>
                        <span class="dist-value"><?= $dist['total'] ?></span>
                    </div>
                    <div class="dist-stat">
                        <span class="dist-label">Active:</span>
                        <span class="dist-value active"><?= $dist['active'] ?></span>
                    </div>
                    <div class="dist-stat">
                        <span class="dist-label">Inactive:</span>
                        <span class="dist-value inactive"><?= $dist['total'] - $dist['active'] ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    </div>
</div>

<style>
.main-content {
    margin-left: 260px;
    margin-top: 70px;
    padding: 0;
    min-height: calc(100vh - 70px);
}

.superadmin-container {
    padding: 30px;
    background: #f5f7fa;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 25px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.page-header h1 {
    color: #2c3e50;
    margin: 0 0 5px 0;
}

.subtitle {
    color: #6c757d;
    margin: 0;
}

.threat-indicator {
    padding: 12px 24px;
    border-radius: 8px;
    color: white;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}

.status-badges {
    display: flex;
    gap: 15px;
    margin-bottom: 30px;
}

.status-badge {
    padding: 12px 20px;
    border-radius: 8px;
    color: white;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.status-badge.success { background: #28a745; }
.status-badge.danger { background: #dc3545; }
.status-badge.warning { background: #ffc107; color: #000; }

.section-title {
    margin: 30px 0 15px 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.section-title h2 {
    color: #2c3e50;
    font-size: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0;
}

.btn-refresh {
    padding: 8px 16px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
}

.metrics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.metric-card {
    background: white;
    padding: 25px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: transform 0.3s;
}

.metric-card:hover {
    transform: translateY(-5px);
}

.metric-card.critical { border-left: 4px solid #dc3545; }
.metric-card.warning { border-left: 4px solid #ffc107; }
.metric-card.danger { border-left: 4px solid #fd7e14; }
.metric-card.info { border-left: 4px solid #17a2b8; }
.metric-card.success { border-left: 4px solid #28a745; }
.metric-card.secondary { border-left: 4px solid #6c757d; }

.metric-icon {
    font-size: 40px;
}

.metric-card.critical .metric-icon { color: #dc3545; }
.metric-card.warning .metric-icon { color: #ffc107; }
.metric-card.danger .metric-icon { color: #fd7e14; }
.metric-card.info .metric-icon { color: #17a2b8; }
.metric-card.success .metric-icon { color: #28a745; }
.metric-card.secondary .metric-icon { color: #6c757d; }

.metric-details h3 {
    margin: 0;
    font-size: 32px;
    color: #2c3e50;
}

.metric-details p {
    margin: 5px 0 0 0;
    color: #6c757d;
    font-size: 14px;
}

.health-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.health-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.health-label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 10px;
}

.health-bar {
    height: 30px;
    background: #e9ecef;
    border-radius: 15px;
    overflow: hidden;
}

.health-fill {
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 12px;
    transition: width 0.5s;
}

.health-fill.cpu { background: linear-gradient(90deg, #667eea 0%, #764ba2 100%); }
.health-fill.memory { background: linear-gradient(90deg, #f093fb 0%, #f5576c 100%); }
.health-fill.disk { background: linear-gradient(90deg, #4facfe 0%, #00f2fe 100%); }

.health-value {
    font-size: 24px;
    font-weight: 700;
    color: #2c3e50;
    text-align: center;
    padding: 10px;
}

.text-danger { color: #dc3545; }
.text-warning { color: #ffc107; }

.alerts-container {
    margin-bottom: 30px;
}

.alert {
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.alert-critical {
    background: #f8d7da;
    color: #721c24;
    border-left: 4px solid #dc3545;
}

.alert-high {
    background: #fff3cd;
    color: #856404;
    border-left: 4px solid #ffc107;
}

.actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 30px;
}

.action-btn {
    padding: 20px;
    border-radius: 12px;
    border: none;
    cursor: pointer;
    font-weight: 600;
    color: white;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
    transition: all 0.3s;
    text-decoration: none;
}

.action-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}

.action-btn.danger { background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); }
.action-btn.warning { background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); }
.action-btn.info { background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); }
.action-btn.primary { background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); }
.action-btn.success { background: linear-gradient(135deg, #28a745 0%, #218838 100%); }
.action-btn.secondary { background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%); }

.action-btn i {
    font-size: 24px;
}

.table-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    margin-bottom: 30px;
}

.sessions-table {
    width: 100%;
    border-collapse: collapse;
}

.sessions-table thead {
    background: #f8f9fa;
}

.sessions-table th {
    padding: 15px;
    text-align: left;
    font-size: 12px;
    font-weight: 700;
    color: #6c757d;
    text-transform: uppercase;
}

.sessions-table td {
    padding: 15px;
    border-bottom: 1px solid #f0f0f0;
}

.role-badge {
    padding: 4px 12px;
    background: #007bff;
    color: white;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 700;
}

.btn-kill {
    padding: 6px 12px;
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
}

.events-list {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 30px;
}

.event-item {
    background: white;
    padding: 15px 20px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.event-item.severity-critical { border-left: 4px solid #dc3545; }
.event-item.severity-high { border-left: 4px solid #fd7e14; }
.event-item.severity-medium { border-left: 4px solid #ffc107; }
.event-item.severity-low { border-left: 4px solid #17a2b8; }

.event-icon {
    font-size: 24px;
}

.event-item.severity-critical .event-icon { color: #dc3545; }
.event-item.severity-high .event-icon { color: #fd7e14; }
.event-item.severity-medium .event-icon { color: #ffc107; }
.event-item.severity-low .event-icon { color: #17a2b8; }

.event-details {
    flex: 1;
}

.event-type {
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 3px;
}

.event-info {
    font-size: 12px;
    color: #6c757d;
}

.severity-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 700;
    color: white;
}

.severity-badge.critical { background: #dc3545; }
.severity-badge.high { background: #fd7e14; }
.severity-badge.medium { background: #ffc107; color: #000; }
.severity-badge.low { background: #17a2b8; }

.distribution-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.dist-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.dist-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px;
    text-align: center;
}

.dist-header h3 {
    margin: 0;
    font-size: 18px;
}

.dist-body {
    padding: 20px;
}

.dist-stat {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #f0f0f0;
}

.dist-stat:last-child {
    border-bottom: none;
}

.dist-label {
    font-weight: 600;
    color: #6c757d;
}

.dist-value {
    font-weight: 700;
    color: #2c3e50;
}

.dist-value.active {
    color: #28a745;
}

.dist-value.inactive {
    color: #dc3545;
}
</style>

<script>
function forceLogoutAll() {
    if (!confirm('⚠️ WARNING: This will force logout ALL users!\n\nThis includes:\n- All data entry staff\n- All analysts\n- All managers\n- All administrators\n\nAre you absolutely sure?')) {
        return;
    }

    fetch('/public/ajax/force_logout.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=logout_all'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            location.reload();
        } else {
            alert('❌ Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('❌ Network error: ' + error);
    });
}

function killSession(sessionId) {
    if (!confirm('Kill this session?')) return;

    fetch('/public/ajax/force_logout.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=kill_session&session_id=${sessionId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            location.reload();
        } else {
            alert('❌ Error: ' + data.message);
        }
    });
}

function toggleLogins() {
    const currentlyEnabled = <?= $logins_enabled ? 'true' : 'false' ?>;
    const action = currentlyEnabled ? 'disable' : 'enable';
    
    if (!confirm(`Are you sure you want to ${action} all system logins?`)) {
        return;
    }

    fetch('/public/ajax/system_control.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=toggle_logins&enable=${!currentlyEnabled}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            location.reload();
        } else {
            alert('❌ Error: ' + data.message);
        }
    });
}

function runIntegrityCheck() {
    if (!confirm('Run file integrity check? This may take a few minutes.')) return;

    const btn = event.target.closest('button');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Checking...</span>';

    fetch('/public/ajax/run_integrity_check.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-shield-alt"></i><span>Run Integrity Check</span>';
        
        if (data.success) {
            let message = data.message;
            if (data.details && data.details.modified_files.length > 0) {
                message += '\n\n⚠️ MODIFIED FILES:\n' + data.details.modified_files.join('\n');
            }
            if (data.details && data.details.missing_files.length > 0) {
                message += '\n\n❌ MISSING FILES:\n' + data.details.missing_files.join('\n');
            }
            alert(message);
        } else {
            alert('❌ Error: ' + data.message);
        }
    })
    .catch(error => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-shield-alt"></i><span>Run Integrity Check</span>';
        alert('❌ Network error: ' + error);
    });