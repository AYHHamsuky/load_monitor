<?php
/**
 * MytoDashboardController
 * Path: app/controllers/MytoDashboardController.php
 *
 * Loads the MYTO dashboard view for UL8 (Lead Dispatch Officer).
 * Wire in public/index.php:
 *
 *   case 'myto_dashboard':
 *       Guard::requireLogin();
 *       require __DIR__ . '/../app/controllers/MytoDashboardController.php';
 *       break;
 *
 * Also add to the UL8 sidebar entry (sidebar.php) already included in
 * the uploaded file — add one <li> inside the UL8 block:
 *
 *   <li class="<?= $current_page === 'myto_dashboard' ? 'active' : '' ?>">
 *       <a href="?page=myto_dashboard">
 *           <i class="fas fa-bolt"></i>
 *           <span>MYTO Allocation</span>
 *       </a>
 *   </li>
 */

require_once __DIR__ . '/../models/MytoAllocation.php';

// Auth
$user = Auth::user();
if (!$user || $user['role'] !== 'UL8') {
    http_response_code(403);
    die('Access denied.');
}

$db = Database::connect();

// Operational date
$now   = new DateTime();
$today = ((int)$now->format('G') === 0)
    ? (clone $now)->modify('-1 day')->format('Y-m-d')
    : $now->format('Y-m-d');

// Date selection (read-only history view)
$selected_date = $_GET['date'] ?? $today;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_date)) {
    $selected_date = $today;
}
$is_today = ($selected_date === $today);

// ── Data for view ─────────────────────────────────────────────────────────────

// Dashboard stat cards
$stats = MytoAllocation::getDashboardStats($selected_date);

// Full matrix (TS rows × 24 hour cols)
$matrix = MytoAllocation::buildDashboardMatrix($selected_date);

// Grand totals per hour (footer row)
$hourly_totals = MytoAllocation::getHourlyGrandTotals($selected_date);

// Active sharing formula
$active_formula  = MytoAllocation::getActiveFormula();
$formula_version = MytoAllocation::getCurrentVersion();

// Daily allocations (for modal pre-fill when editing)
$daily_allocations = MytoAllocation::getDailyAllocations($selected_date);

// All transmission stations (for formula editor)
$all_ts = $db->query("
    SELECT ts_code, station_name FROM transmission_stations ORDER BY station_name
")->fetchAll(PDO::FETCH_ASSOC);

// Formula percentage map [ts_code => pct] for JS
$formula_map = [];
foreach ($active_formula as $row) {
    $formula_map[$row['ts_code']] = (float)$row['percentage'];
}

// AJAX endpoint URL — mirrors the pattern used by 11kV/33kV controllers
$mytoSaveUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
    . '://' . $_SERVER['HTTP_HOST']
    . $_SERVER['SCRIPT_NAME']
    . '?page=myto_entry';

// Load the view
require __DIR__ . '../../views/myto/dashboard.php';
