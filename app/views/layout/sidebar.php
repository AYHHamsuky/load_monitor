<?php
$user = Auth::user();
$role = $user['role'];
$current_page = $_GET['page'] ?? 'dashboard';
$current_action = $_GET['action'] ?? '';
?>

<?php
// At top of sidebar.php, add:
require_once __DIR__ . '/../../models/InterruptionApproval.php';
$pendingApprovalCount = InterruptionApproval::getPendingCount($user['role']);
?>

<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h3>⚡ Energy</h3>
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <ul>
            <?php if ($role === 'UL1'): ?>
                <!-- UL1 Menu: 11kV Data Entry -->
                <li class="<?= $current_page === 'dashboard' ? 'active' : '' ?>">
                    <a href="?page=dashboard">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="#" onclick="openLoadEntryModal(); return false;">
                        <i class="fas fa-bolt"></i>
                        <span>11kV-Load Entry</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'interruptions_11kv' ? 'active' : '' ?>">
                    <a href="?page=interruptions_11kv">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Interruptions Request</span>
                    </a>
               <li class="<?= $current_page === 'my_requests' ? 'active' : '' ?>">
                    <a href="?page=interruptions_11kv&action=my-requests">
                        <i class="fas fa-clock"></i>
                        <span>Interruption Portsla</span>
                        <?php if ($pendingApprovalCount > 0 && in_array($user['role'], ['UL1', 'UL2'])): ?>
                            <span class="badge"><?= $pendingApprovalCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                
                <li class="<?= $current_page === 'interruption_approvals' ? 'active' : '' ?>">
                    <a href="?page=interruption_approvals&action=my-requests">
                        <i class="fas fa-clock"></i>
                        <span>Interruption Approvals</span>
                        <?php if ($pendingApprovalCount > 0 && in_array($user['role'], ['UL1', 'UL2'])): ?>
                            <span class="badge"><?= $pendingApprovalCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>

                <li class="<?= $current_page === 'corrections' ? 'active' : '' ?>">
                    <a href="?page=corrections&action=request">
                        <i class="fas fa-edit"></i>
                        <span>Request Correction</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'corrections' && isset($_GET['action']) && $_GET['action'] === 'my-requests' ? 'active' : '' ?>">
                    <a href="?page=corrections&action=my-requests">
                        <i class="fas fa-list"></i>
                        <span>Corrections Status</span>
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
                <!-- UL2 Menu: 33kV Data Entry -->
                <li class="<?= $current_page === 'dashboard' ? 'active' : '' ?>">
                    <a href="?page=dashboard">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'interruptions' ? 'active' : '' ?>">
                    <a href="?page=interruptions">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Interruptions</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'interruptions' ? 'active' : '' ?>">
                    <a href="?page=interruptions&action=my-requests">
                        <i class="fas fa-clock"></i>
                        <span>Interruption Portal</span>
                            <?php if ($pendingApprovalCount > 0): ?>
                                <span class="badge"><?= $pendingApprovalCount ?></span>
                            <?php endif; ?>
                    </a>
                </li>
                <li class="<?= $current_page === 'interruption_approvals' ? 'active' : '' ?>">
                    <a href="?page=interruption_approvals&action=my-requests">
                        <i class="fas fa-clock"></i>
                        <span>Interruption Approvals</span>
                            <?php if ($pendingApprovalCount > 0): ?>
                                <span class="badge"><?= $pendingApprovalCount ?></span>
                            <?php endif; ?>
                    </a>
                </li>

                <li class="<?= $current_page === 'corrections' ? 'active' : '' ?>">
                    <a href="?page=corrections&action=request">
                        <i class="fas fa-edit"></i>
                        <span>Correction Request</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'corrections' && isset($_GET['action']) && $_GET['action'] === 'my-requests' ? 'active' : '' ?>">
                    <a href="?page=corrections&action=my-requests">
                        <i class="fas fa-list"></i>
                        <span>Correction Status</span>
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
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'corrections' && isset($_GET['action']) && $_GET['action'] === 'concur' ? 'active' : '' ?>">
                    <a href="?page=corrections&action=concur">
                        <i class="fas fa-search"></i>
                        <span>Corrections</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'interruption_approvals' ? 'active' : '' ?>">
                    <a href="?page=interruption_approvals&action=analyst-review">
                        <i class="fas fa-clipboard-check"></i>
                            <span>Interruptions</span>
                                <?php if ($pendingApprovalCount > 0): ?>
                                <span class="badge-notify"><?= $pendingApprovalCount ?></span>
                            <?php endif; ?>
                    </a>
                </li>


                <li class="<?= $current_page === 'reports' && isset($_GET['action']) && $_GET['action'] === 'create' ? 'active' : '' ?>">
                    <a href="?page=reports&action=create">
                        <i class="fas fa-file-medical"></i>
                        <span>Reports Creation</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'reports' ? 'active' : '' ?>">
                    <a href="?page=reports">
                        <i class="fas fa-file-alt"></i>
                        <span>Report View</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'analytics' ? 'active' : '' ?>">
                    <a href="?page=analytics">
                        <i class="fas fa-chart-bar"></i>
                        <span>Analytics</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'complaints' ? 'active' : '' ?>">
                    <a href="?page=complaints">
                        <i class="fas fa-bullhorn"></i>
                        <span>Complaint View</span>
                    </a>
                </li>
                
            <?php elseif ($role === 'UL4'): ?>
                <!-- UL4 Menu: Manager -->
                <li class="<?= $current_page === 'dashboard' ? 'active' : '' ?>">
                    <a href="?page=dashboard">
                        <i class="fas fa-chart-line"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'corrections' && isset($_GET['action']) && $_GET['action'] === 'approve' ? 'active' : '' ?>">
                    <a href="?page=corrections&action=approve">
                        <i class="fas fa-check-circle"></i>
                        <span>Corrections</span>
                    </a>
                </li>

                <li class="<?= $current_page === 'interruption_approvals' ? 'active' : '' ?>">
                    <a href="?page=interruption_approvals&action=manager-review">
                        <i class="fas fa-check-double"></i>
                            <span>Interruptions</span>
                                <?php if ($pendingApprovalCount > 0): ?>
                                    <span class="badge-notify"><?= $pendingApprovalCount ?></span>
                                <?php endif; ?>
                    </a>
                </li>

                <li class="<?= $current_page === 'reports' ? 'active' : '' ?>">
                    <a href="?page=reports">
                        <i class="fas fa-file-alt"></i>
                        <span>Reports View</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'complaints' ? 'active' : '' ?>">
                    <a href="?page=complaints">
                        <i class="fas fa-bullhorn"></i>
                        <span>Complaint View</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'analytics' ? 'active' : '' ?>">
                    <a href="?page=analytics">
                        <i class="fas fa-chart-bar"></i>
                        <span>Analytics</span>
                    </a>
                </li>
                
            <?php elseif ($role === 'UL5'): ?>
                <!-- UL5 Menu: Staff View (Read-only) -->
                <li class="<?= $current_page === 'dashboard' ? 'active' : '' ?>">
                    <a href="?page=dashboard">
                        <i class="fas fa-eye"></i>
                        <span>Dashboard (Read-only)</span>
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
                <!-- UL6 Menu: Admin -->
                <li class="<?= $current_page === 'dashboard' ? 'active' : '' ?>">
                    <a href="?page=dashboard">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'reports' ? 'active' : '' ?>">
                    <a href="?page=reports">
                        <i class="fas fa-file-alt"></i>
                        <span>System Reports</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'users' ? 'active' : '' ?>">
                    <a href="?page=users">
                        <i class="fas fa-users"></i>
                        <span>User Management</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'system-config' ? 'active' : '' ?>">
                    <a href="?page=system-config">
                        <i class="fas fa-cog"></i>
                        <span>System Config</span>
                    </a>
                </li>
                
            <?php elseif ($role === 'UL8'): ?>
                <!-- UL8 Menu: Lead Dispatch Officer - CORRECTED NAVIGATION -->
                <li class="<?= $current_page === 'dashboard' && $current_action === '' ? 'active' : '' ?>">
                    <a href="?page=dashboard">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'myto_dashboard' ? 'active' : '' ?>">
                    <a href="?page=myto_dashboard">
                        <i class="fas fa-bolt"></i>
                        <span>MYTO Allocation</span>
                    </a>
                </li>
 
                <li class="<?= $current_page === 'dashboard' && $current_action === '11kv' ? 'active' : '' ?>">
                    <a href="?page=dashboard&action=11kv">
                        <i class="fas fa-th"></i>
                        <span>11kV Matrix</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'dashboard' && $current_action === '33kv' ? 'active' : '' ?>">
                    <a href="?page=dashboard&action=33kv">
                        <i class="fas fa-table"></i>
                        <span>33kV Matrix</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'dashboard' && $current_action === 'staff' ? 'active' : '' ?>">
                    <a href="?page=dashboard&action=staff">
                        <i class="fas fa-users"></i>
                        <span>Staff on Duty</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'dashboard' && $current_action === 'interruptions' ? 'active' : '' ?>">
                    <a href="?page=dashboard&action=interruptions">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>Interruptions</span>
                    </a>
                </li>
                <li class="<?= $current_page === 'dashboard' && $current_action === 'statistics' ? 'active' : '' ?>">
                    <a href="?page=dashboard&action=statistics">
                        <i class="fas fa-chart-pie"></i>
                        <span>Load Statistics</span>
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
</aside>
<div id="sidebarBackdrop" class="sidebar-backdrop" onclick="closeSidebar()"></div>

<style>
.sidebar {
    width: 260px;
    height: 100vh;
    background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    position: fixed;
    left: 0;
    top: 0;
    overflow-y: auto;
    transition: transform 0.3s ease;
    z-index: 1000;
    box-shadow: 2px 0 10px rgba(0,0,0,0.1);
}

.sidebar-header {
    padding: 20px;
    background: rgba(255,255,255,0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.sidebar-header h3 {
    color: white;
    margin: 0;
    font-size: 24px;
    font-weight: 700;
}

.sidebar-toggle {
    background: none;
    border: none;
    color: white;
    font-size: 20px;
    cursor: pointer;
    display: none;
}

.sidebar-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.25s ease;
    z-index: 900;
    display: none;
}

.sidebar-backdrop.active {
    opacity: 1;
    pointer-events: auto;
}

.sidebar-nav {
    padding: 20px 0;
}

.sidebar-nav ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.sidebar-nav li {
    margin: 5px 0;
}

.sidebar-nav a {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    transition: all 0.3s ease;
}

.sidebar-nav a:hover {
    background: rgba(255,255,255,0.1);
    color: white;
    padding-left: 25px;
}

.sidebar-nav li.active a {
    background: rgba(255,255,255,0.15);
    color: white;
    border-left: 4px solid #4CAF50;
}

.sidebar-nav i {
    margin-right: 12px;
    width: 20px;
    text-align: center;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .sidebar {
        width: 100%;
        max-width: 320px;
        transform: translateX(-100%);
        top: 0;
        left: 0;
        height: 100vh;
        box-shadow: 0 0 20px rgba(0, 0, 0, 0.25);
    }
    
    .sidebar.active {
        transform: translateX(0);
    }
    
    .sidebar-toggle {
        display: block;
    }

    .sidebar-backdrop {
        display: block;
    }
}

/* Scrollbar */
.sidebar::-webkit-scrollbar {
    width: 6px;
}

.sidebar::-webkit-scrollbar-track {
    background: rgba(255,255,255,0.05);
}

.sidebar::-webkit-scrollbar-thumb {
    background: rgba(255,255,255,0.2);
    border-radius: 3px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(255,255,255,0.3);
}
.sidebar-nav .badge, .sidebar-nav .badge-notify {
    background: #dc2626;
    color: white;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 700;
    margin-left: auto;
}

.sidebar-nav a {
    display: flex;
    align-items: center;
    justify-content: space-between;
}


</style>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    sidebar.classList.toggle('active');
    backdrop.classList.toggle('active');
}

function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebarBackdrop');
    sidebar.classList.remove('active');
    backdrop.classList.remove('active');
}
</script>
