<?php
// app/views/layout/sidebar.php - UPDATED VERSION

$user = Auth::user();
$role = $user['role'] ?? '';
$current_page = $_GET['page'] ?? 'dashboard';
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <h3>⚡ LMS</h3>
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
    </div>

    <nav class="sidebar-nav">
        <ul>
            <?php if ($role === 'UL1'): ?>
                <!-- UL1 Menu: 11kV Data Entry Staff -->
                <li class="<?= $current_page === 'dashboard' ? 'active' : '' ?>">
                    <a href="?page=dashboard">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="#" onclick="openLoadEntryModal(); return false;">
                        <i class="fas fa-bolt"></i>
                        <span>11kV Load Entry</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'corrections' && ($_GET['action'] ?? '') === 'request' ? 'active' : '' ?>">
                    <a href="?page=corrections&action=request">
                        <i class="fas fa-edit"></i>
                        <span>Request Correction</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'corrections' && ($_GET['action'] ?? '') === 'my-requests' ? 'active' : '' ?>">
                    <a href="?page=corrections&action=my-requests">
                        <i class="fas fa-list-alt"></i>
                        <span>My Requests</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'reports' ? 'active' : '' ?>">
                    <a href="?page=reports">
                        <i class="fas fa-file-alt"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'complaints' ? 'active' : '' ?>">
                    <a href="?page=complaints">
                        <i class="fas fa-bullhorn"></i>
                        <span>Complaint Log</span>
                    </a>
                </li>

            <?php elseif ($role === 'UL2'): ?>
                <!-- UL2 Menu: 33kV Data Entry Staff -->
                <li class="<?= $current_page === 'dashboard' ? 'active' : '' ?>">
                    <a href="?page=dashboard">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>33kV Dashboard</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'interruptions' ? 'active' : '' ?>">
                    <a href="?page=interruptions">
                        <i class="fas fa-power-off"></i>
                        <span>Interruptions</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'corrections' && ($_GET['action'] ?? '') === 'request' ? 'active' : '' ?>">
                    <a href="?page=corrections&action=request">
                        <i class="fas fa-edit"></i>
                        <span>Request Correction</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'corrections' && ($_GET['action'] ?? '') === 'my-requests' ? 'active' : '' ?>">
                    <a href="?page=corrections&action=my-requests">
                        <i class="fas fa-list-alt"></i>
                        <span>My Requests</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'reports' ? 'active' : '' ?>">
                    <a href="?page=reports">
                        <i class="fas fa-file-alt"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'complaints' ? 'active' : '' ?>">
                    <a href="?page=complaints">
                        <i class="fas fa-bullhorn"></i>
                        <span>Complaint Log</span>
                    </a>
                </li>

            <?php elseif ($role === 'UL3'): ?>
                <!-- UL3 Menu: Analyst -->
                <li class="<?= $current_page === 'dashboard' ? 'active' : '' ?>">
                    <a href="?page=dashboard">
                        <i class="fas fa-chart-line"></i>
                        <span>Analyst Dashboard</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'corrections' && ($_GET['action'] ?? '') === 'concur' ? 'active' : '' ?>">
                    <a href="?page=corrections&action=concur">
                        <i class="fas fa-check-circle"></i>
                        <span>Concur Corrections</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'reports' ? 'active' : '' ?>">
                    <a href="?page=reports">
                        <i class="fas fa-file-alt"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'complaints' ? 'active' : '' ?>">
                    <a href="?page=complaints">
                        <i class="fas fa-bullhorn"></i>
                        <span>Complaint Log</span>
                    </a>
                </li>

            <?php elseif ($role === 'UL4'): ?>
                <!-- UL4 Menu: Manager -->
                <li class="<?= $current_page === 'dashboard' ? 'active' : '' ?>">
                    <a href="?page=dashboard">
                        <i class="fas fa-chart-pie"></i>
                        <span>Manager Dashboard</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'corrections' && ($_GET['action'] ?? '') === 'approve' ? 'active' : '' ?>">
                    <a href="?page=corrections&action=approve">
                        <i class="fas fa-check-double"></i>
                        <span>Approve Corrections</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'reports' ? 'active' : '' ?>">
                    <a href="?page=reports">
                        <i class="fas fa-file-alt"></i>
                        <span>Reports</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'complaints' ? 'active' : '' ?>">
                    <a href="?page=complaints">
                        <i class="fas fa-bullhorn"></i>
                        <span>Complaint Log</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'analytics' ? 'active' : '' ?>">
                    <a href="?page=analytics">
                        <i class="fas fa-chart-bar"></i>
                        <span>Analytics</span>
                    </a>
                </li>

            <?php elseif ($role === 'UL5'): ?>
                <!-- UL5 Menu: Staff View -->
                <li class="<?= $current_page === 'dashboard' ? 'active' : '' ?>">
                    <a href="?page=dashboard">
                        <i class="fas fa-eye"></i>
                        <span>Dashboard (View)</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'reports' ? 'active' : '' ?>">
                    <a href="?page=reports">
                        <i class="fas fa-file-alt"></i>
                        <span>Reports (View Only)</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'analytics' ? 'active' : '' ?>">
                    <a href="?page=analytics">
                        <i class="fas fa-chart-bar"></i>
                        <span>Analytics (View Only)</span>
                    </a>
                </li>

            <?php elseif ($role === 'UL6'): ?>
                <!-- UL6 Menu: Admin/Super Operator (Restricted) -->
                <li class="<?= $current_page === 'dashboard' ? 'active' : '' ?>">
                    <a href="?page=dashboard">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Admin Dashboard</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'users' ? 'active' : '' ?>">
                    <a href="?page=users">
                        <i class="fas fa-users"></i>
                        <span>Manage UL1 & UL2</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'corrections' && ($_GET['action'] ?? '') === 'approve' ? 'active' : '' ?>">
                    <a href="?page=corrections&action=approve">
                        <i class="fas fa-check-circle"></i>
                        <span>Approve Corrections</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'reports' ? 'active' : '' ?>">
                    <a href="?page=reports">
                        <i class="fas fa-file-alt"></i>
                        <span>System Reports</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'complaints' ? 'active' : '' ?>">
                    <a href="?page=complaints">
                        <i class="fas fa-bullhorn"></i>
                        <span>View Complaints</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'analytics' ? 'active' : '' ?>">
                    <a href="?page=analytics">
                        <i class="fas fa-chart-bar"></i>
                        <span>Analytics</span>
                    </a>
                </li>

            <?php elseif ($role === 'UL7'): ?>
                <!-- 🆕 UL7 Menu: Super Admin -->
                <li class="<?= $current_page === 'dashboard' ? 'active' : '' ?>">
                    <a href="?page=dashboard">
                        <i class="fas fa-shield-alt"></i>
                        <span>Security Dashboard</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'superadmin' && ($_GET['action'] ?? '') === 'users' ? 'active' : '' ?>">
                    <a href="?page=superadmin&action=users">
                        <i class="fas fa-users-cog"></i>
                        <span>Manage All Users</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'superadmin' && ($_GET['action'] ?? '') === 'audit-logs' ? 'active' : '' ?>">
                    <a href="?page=superadmin&action=audit-logs">
                        <i class="fas fa-history"></i>
                        <span>Audit Logs</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'superadmin' && ($_GET['action'] ?? '') === 'security' ? 'active' : '' ?>">
                    <a href="?page=superadmin&action=security">
                        <i class="fas fa-shield-virus"></i>
                        <span>Security Monitor</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'superadmin' && ($_GET['action'] ?? '') === 'sessions' ? 'active' : '' ?>">
                    <a href="?page=superadmin&action=sessions">
                        <i class="fas fa-user-clock"></i>
                        <span>Active Sessions</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'superadmin' && ($_GET['action'] ?? '') === 'file-integrity' ? 'active' : '' ?>">
                    <a href="?page=superadmin&action=file-integrity">
                        <i class="fas fa-file-shield"></i>
                        <span>File Integrity</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'superadmin' && ($_GET['action'] ?? '') === 'system-health' ? 'active' : '' ?>">
                    <a href="?page=superadmin&action=system-health">
                        <i class="fas fa-heartbeat"></i>
                        <span>System Health</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'superadmin' && ($_GET['action'] ?? '') === 'ip-management' ? 'active' : '' ?>">
                    <a href="?page=superadmin&action=ip-management">
                        <i class="fas fa-network-wired"></i>
                        <span>IP Management</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'reports' ? 'active' : '' ?>">
                    <a href="?page=reports">
                        <i class="fas fa-file-alt"></i>
                        <span>Reports</span>
                    </a>
                </li>

            <?php endif; ?>
        </ul>
    </nav>

    <div class="sidebar-footer">
        <a href="/public/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</aside>

<style>
.sidebar {
    position: fixed;
    left: 0;
    top: 70px;
    width: 260px;
    height: calc(100vh - 70px);
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    overflow-y: auto;
    z-index: 1000;
    transition: all 0.3s;
}

.sidebar-header {
    padding: 20px;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar-header h3 {
    color: white;
    margin: 0;
    font-size: 24px;
}

.sidebar-toggle {
    display: none;
    background: none;
    border: none;
    color: white;
    font-size: 20px;
    cursor: pointer;
}

.sidebar-nav ul {
    list-style: none;
    padding: 20px 0;
    margin: 0;
}

.sidebar-nav li {
    margin: 0;
}

.sidebar-nav a {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px 25px;
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    transition: all 0.3s;
}

.sidebar-nav a:hover {
    background: rgba(255,255,255,0.1);
    color: white;
}

.sidebar-nav li.active a {
    background: rgba(255,255,255,0.2);
    color: white;
    border-left: 4px solid white;
}

.sidebar-nav a i {
    font-size: 18px;
    width: 20px;
    text-align: center;
}

.sidebar-footer {
    position: absolute;
    bottom: 0;
    width: 100%;
    padding: 20px;
    border-top: 1px solid rgba(255,255,255,0.1);
}

.logout-btn {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    background: rgba(255,255,255,0.1);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    transition: all 0.3s;
}

.logout-btn:hover {
    background: rgba(255,255,255,0.2);
}

@media (max-width: 768px) {
    .sidebar {
        left: -260px;
    }
    
    .sidebar.active {
        left: 0;
    }
    
    .sidebar-toggle {
        display: block;
    }
}
</style>

<script>
function toggleSidebar() {
    document.querySelector('.sidebar').classList.toggle('active');
}
</script>