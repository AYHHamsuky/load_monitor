<?php
session_start();

if (!isset($_SESSION['payroll_id'])) {
    header("Location: /login.php");
    exit;
}

require_once __DIR__ . '/../app/views/layout/header.php';
require_once __DIR__ . '/../app/views/layout/sidebar.php';

echo '<main class="main-content">';

if ($_SESSION['role'] === 'UL1') {
    require __DIR__ . '/../app/views/dashboard/11kv.php';
} elseif ($_SESSION['role'] === 'UL2') {
    require __DIR__ . '/../app/views/dashboard/33kv.php';
} else {
    require __DIR__ . '/../app/views/dashboard/admin.php';
}

echo '</main>';

require_once __DIR__ . '/../app/views/layout/footer.php';
