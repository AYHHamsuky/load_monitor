<?php
// DO NOT start session here
// DO NOT require database/config files here

$user = Auth::user();

// Determine display name based on role
if ($user['role'] === 'UL8') {
    $locationName = 'Lead Dispatch Control Center';
} elseif (!empty($user['iss_code'])) {
    $db = Database::connect();
    $stmt = $db->prepare("
        SELECT iss_name 
        FROM iss_locations 
        WHERE iss_code = ?
        LIMIT 1
    ");
    $stmt->execute([$user['iss_code']]);
    $issName = $stmt->fetchColumn();
    $locationName = $issName ? $issName . ' Injection Substation' : 'Head Office';
} else {
    $locationName = 'Head Office';
}

// Role display names
$roleNames = [
    'UL1' => '11kV Entry',
    'UL2' => '33kV Entry',
    'UL3' => 'Analyst',
    'UL4' => 'Manager',
    'UL5' => 'Staff View',
    'UL6' => 'Admin',
    'UL8' => 'Lead Dispatch'
];

$roleDisplay = $roleNames[$user['role']] ?? $user['role'];

date_default_timezone_set('Africa/Lagos');

// ── Base path helper ──────────────────────────────────────────────────────────
// Uses the BASE_PATH constant set in bootstrap.php (auto-detects port 80 vs 5006).
$basePath = defined('BASE_PATH') ? BASE_PATH : '/load_monitor/public';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kaduna Electric Load Reading Management System</title>

    <!-- Favicon (optional — place file at public/assets/img/favicon.ico) -->
    <link rel="icon" type="image/x-icon" href="<?= $basePath ?>/assets/img/favicon.ico">

    <!-- Font Awesome (icons used throughout the app) -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
          crossorigin="anonymous" referrerpolicy="no-referrer">

    <!-- Base path for JS modules -->
    <script>window.__BASE_PATH = '<?= $basePath ?>';</script>

    <!-- Global dashboard JS (clock utility) -->
    <script defer src="<?= $basePath ?>/assets/js/dashboard.js"></script>
</head>
<body>

<style>
/* ── Header ─────────────────────────────────────────────── */
.top-header {
    position: fixed;
    top: 0; left: 0;
    height: 62px;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px;
    background: linear-gradient(90deg, #004B23 0%, #005a2a 100%);
    border-bottom: 3px solid #6CAE27;
    box-shadow: 0 2px 12px rgba(0,75,35,0.35);
    z-index: 1000;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 12px;
}

.header-left img { height: 38px; object-fit: contain; }

#sidebarToggle {
    font-size: 22px;
    cursor: pointer;
    color: #d4f0b5;
    display: none;
    background: none;
    border: none;
    padding: 0;
    line-height: 1;
}

.header-center {
    font-size: 14px;
    font-weight: 700;
    color: #ffffff;
    text-align: center;
    white-space: nowrap;
    letter-spacing: 0.2px;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 16px;
    color: #d4f0b5;
    font-size: 13px;
}

/* ── Profile ─────────────────────────────────────────────── */
.profile {
    position: relative;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
}

.profile-name {
    font-weight: 700;
    color: #ffffff;
    font-size: 13px;
}

.profile-role {
    font-size: 10px;
    font-weight: 700;
    background: rgba(108,174,39,0.28);
    color: #d4f0b5;
    padding: 2px 8px;
    border-radius: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.profile-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 46px;
    background: #004B23;
    border-radius: 10px;
    min-width: 210px;
    box-shadow: 0 12px 30px rgba(0,0,0,0.35);
    overflow: hidden;
    border: 1px solid rgba(108,174,39,0.3);
    z-index: 2000;
}

.profile-menu-header {
    padding: 12px 16px;
    background: rgba(0,0,0,0.25);
    border-bottom: 1px solid rgba(108,174,39,0.2);
}

.profile-menu-header .name {
    font-size: 13px;
    font-weight: 700;
    color: #fff;
}

.profile-menu-header .id {
    font-size: 11px;
    color: #a3c47a;
    margin-top: 2px;
}

.profile-menu a {
    display: block;
    padding: 10px 16px;
    color: #d4f0b5;
    text-decoration: none;
    font-size: 13px;
    transition: background 0.15s;
}

.profile-menu a:hover { background: rgba(108,174,39,0.2); color: #fff; }

/* ── Layout offset ───────────────────────────────────────── */
.app-container { padding-top: 62px; }

/* ── Mobile ──────────────────────────────────────────────── */
@media (max-width: 768px) {
    .header-center { display: none; }
    #sidebarToggle { display: block; }
    .profile-name {
        max-width: 130px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
}
</style>

<header class="top-header">
    <div class="header-left">
        <span id="sidebarToggle">☰</span>
        <img src="<?= $basePath ?>/assets/img/ke_logo.png" alt="KE Logo">
    </div>

    <div class="header-center">
        <?= htmlspecialchars($locationName) ?>
    </div>

    <div class="header-right">
        <span><?= date('d M Y, H:i') ?></span>

        <div class="profile" id="profileToggle">
            <span class="profile-name">
                <?= htmlspecialchars($user['staff_name']) ?>
            </span>
            <span class="profile-role"><?= htmlspecialchars($roleDisplay) ?></span>

            <div class="profile-menu" id="profileMenu">
                <div class="profile-menu-header">
                    <div class="name"><?= htmlspecialchars($user['staff_name']) ?></div>
                    <div class="id"><?= htmlspecialchars($user['payroll_id']) ?></div>
                </div>
                <a href="#">Profile</a>
                <a href="#">Settings</a>
                <a href="<?= $basePath ?>/index.php?page=logout">Logout</a>
            </div>
        </div>
    </div>
</header>

<script>
(function () {
    // Sidebar toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('active');
        });
    }
    
    // Profile menu
    const profileToggle = document.getElementById('profileToggle');
    const profileMenu   = document.getElementById('profileMenu');

    if (!profileToggle || !profileMenu) return;

    profileToggle.addEventListener('click', function (e) {
        e.stopPropagation();
        profileMenu.style.display =
            profileMenu.style.display === 'block' ? 'none' : 'block';
    });

    document.addEventListener('click', function () {
        profileMenu.style.display = 'none';
    });
})();
</script>
