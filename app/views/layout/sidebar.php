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
        <?php $bp = defined('BASE_PATH') ? BASE_PATH : ''; ?>
        <img src="<?= $bp ?>/assets/img/ke_logo.png" alt="Kaduna Electric" class="sidebar-logo-img">
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
/* ── Sidebar – Kaduna Electric  #004B23 / #008000 / #6CAE27 ── */
.sidebar {
    width: 252px;
    height: 100vh;
    background: linear-gradient(180deg, #004B23 0%, #003519 100%);
    position: fixed;
    left: 0;
    top: 0;
    overflow-y: auto;
    transition: transform 0.28s ease;
    z-index: 1001;
    box-shadow: 3px 0 16px rgba(0,0,0,0.22);
    border-right: 1px solid rgba(108,174,39,0.2);
}

/* ── Header ───────────────────────────────────────────────── */
.sidebar-header {
    padding: 14px 16px;
    background: rgba(0,0,0,0.25);
    border-bottom: 2px solid #6CAE27;
    display: flex;
    align-items: center;
    justify-content: space-between;
    min-height: 62px;
}

.sidebar-logo-img {
    height: 36px;
    object-fit: contain;
    filter: brightness(0) invert(1);
}

.sidebar-toggle {
    background: none;
    border: none;
    color: #d4f0b5;
    font-size: 18px;
    cursor: pointer;
    padding: 4px;
    display: none;
    line-height: 1;
}

/* ── Backdrop (mobile) ────────────────────────────────────── */
.sidebar-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.5);
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.25s ease;
    z-index: 1000;
    display: none;
}
.sidebar-backdrop.active {
    opacity: 1;
    pointer-events: auto;
}

/* ── Nav ──────────────────────────────────────────────────── */
.sidebar-nav { padding: 10px 0; }

.sidebar-nav ul { list-style: none; padding: 0; margin: 0; }

.sidebar-nav li { margin: 1px 0; }

.sidebar-nav a {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 11px 18px;
    color: rgba(255,255,255,0.78);
    text-decoration: none;
    font-size: 13px;
    font-weight: 500;
    border-left: 3px solid transparent;
    transition: background 0.18s, color 0.18s, border-color 0.18s, padding-left 0.18s;
    gap: 10px;
}

.sidebar-nav a:hover {
    background: rgba(108,174,39,0.15);
    color: #fff;
    border-left-color: #6CAE27;
    padding-left: 22px;
}

.sidebar-nav li.active a {
    background: rgba(108,174,39,0.22);
    color: #fff;
    border-left-color: #6CAE27;
    font-weight: 700;
}

.sidebar-nav i {
    width: 20px;
    text-align: center;
    font-size: 14px;
    flex-shrink: 0;
    color: #a3c47a;
}

.sidebar-nav li.active a i,
.sidebar-nav a:hover i { color: #6CAE27; }

.sidebar-nav span { flex: 1; }

/* ── Badges ───────────────────────────────────────────────── */
.sidebar-nav .badge,
.sidebar-nav .badge-notify {
    background: #dc2626;
    color: #fff;
    padding: 2px 7px;
    border-radius: 10px;
    font-size: 10px;
    font-weight: 800;
    flex-shrink: 0;
    min-width: 18px;
    text-align: center;
}

/* ── Scrollbar ────────────────────────────────────────────── */
.sidebar::-webkit-scrollbar { width: 4px; }
.sidebar::-webkit-scrollbar-track { background: transparent; }
.sidebar::-webkit-scrollbar-thumb { background: rgba(108,174,39,0.35); border-radius: 4px; }
.sidebar::-webkit-scrollbar-thumb:hover { background: rgba(108,174,39,0.55); }

/* ── Mobile ───────────────────────────────────────────────── */
@media (max-width: 768px) {
    .sidebar {
        width: 280px;
        transform: translateX(-100%);
    }
    .sidebar.active  { transform: translateX(0); }
    .sidebar-toggle  { display: block; }
    .sidebar-backdrop { display: block; }
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
