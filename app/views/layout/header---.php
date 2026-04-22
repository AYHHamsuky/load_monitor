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
?>

<style>
/* ===== HEADER ===== */
.top-header {
    position: fixed;
    top: 0;
    left: 0;
    height: 64px;
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 18px;
    background: rgba(6, 25, 60, 0.85);
    backdrop-filter: blur(14px);
    -webkit-backdrop-filter: blur(14px);
    border-bottom: 1px solid rgba(255,255,255,0.1);
    z-index: 1000;
}

/* ===== LEFT ===== */
.header-left {
    display: flex;
    align-items: center;
    gap: 14px;
}

.header-left img {
    height: 38px;
}

#sidebarToggle {
    font-size: 22px;
    cursor: pointer;
    color: #e5e7eb;
}

/* ===== CENTER ===== */
.header-center {
    font-size: 16px;
    font-weight: 600;
    color: #f9fafb;
    text-align: center;
    white-space: nowrap;
}

/* ===== RIGHT ===== */
.header-right {
    display: flex;
    align-items: center;
    gap: 18px;
    color: #e5e7eb;
    font-size: 13px;
}

/* ===== PROFILE ===== */
.profile {
    position: relative;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
}

.profile-name {
    font-weight: 600;
    color: #ffffff;
}

.profile-role {
    font-size: 10px;
    font-weight: 600;
    background: rgba(59, 130, 246, 0.3);
    color: #93c5fd;
    padding: 2px 8px;
    border-radius: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.profile-menu {
    display: none;
    position: absolute;
    right: 0;
    top: 44px;
    background: rgba(17, 24, 39, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 10px;
    min-width: 200px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.35);
    overflow: hidden;
    border: 1px solid rgba(255,255,255,0.1);
}

.profile-menu-header {
    padding: 12px 14px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    background: rgba(255,255,255,0.05);
}

.profile-menu-header .name {
    font-size: 13px;
    font-weight: 600;
    color: #ffffff;
    margin-bottom: 2px;
}

.profile-menu-header .id {
    font-size: 11px;
    color: #9ca3af;
}

.profile-menu a {
    display: block;
    padding: 10px 14px;
    color: #e5e7eb;
    text-decoration: none;
    font-size: 13px;
    transition: background 0.15s ease;
}

.profile-menu a:hover {
    background: rgba(255,255,255,0.08);
}

/* ===== CONTENT OFFSET ===== */
.app-container {
    padding-top: 64px; /* HEADER HEIGHT */
}

/* ===== MOBILE ===== */
@media (max-width: 768px) {
    .header-center {
        display: none;
    }
    
    .profile-name {
        max-width: 150px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
}
</style>

<header class="top-header">
    <div class="header-left">
        <span id="sidebarToggle">☰</span>
        <img src="/load_monitor/public/assets/img/ke_logo.png" alt="KE Logo">
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
                <a href="/load_monitor/public/index.php?page=logout">Logout</a>
            </div>
        </div>
    </div>
</header>

<script>
(function () {
    const profileToggle = document.getElementById('profileToggle');
    const profileMenu = document.getElementById('profileMenu');

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
