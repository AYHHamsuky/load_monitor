<?php
// app/controllers/AdminDashboardController.php

Guard::requireAdmin(); // Only UL6

$user = Auth::user();
$db = Database::connect();
$today = date('Y-m-d');

// User Statistics
$stmt = $db->query("
    SELECT 
        role,
        COUNT(*) as count,
        SUM(CASE WHEN is_active = 'Yes' THEN 1 ELSE 0 END) as active_count
    FROM staff_details
    GROUP BY role
    ORDER BY role
");
$user_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_users = 0;
$active_users = 0;
foreach ($user_stats as $stat) {
    $total_users += $stat['count'];
    $active_users += $stat['active_count'];
}

// Recent User Activity (Last 7 days)
// Note: fdr11kv_data and fdr33kv_data don't have created_by field
// We'll show all active UL1 and UL2 users instead
$stmt = $db->query("
    SELECT 
        s.staff_name,
        s.role,
        s.last_login,
        DATEDIFF(NOW(), s.last_login) as days_since_login
    FROM staff_details s
    WHERE s.role IN ('UL1', 'UL2')
    AND s.is_active = 'Yes'
    ORDER BY s.last_login DESC
    LIMIT 10
");
$user_activity = $stmt->fetchAll(PDO::FETCH_ASSOC);

// System Health Metrics
$stmt = $db->query("SELECT COUNT(*) as count FROM fdr11kv");
$total_11kv = $stmt->fetch()['count'];

$stmt = $db->query("SELECT COUNT(*) as count FROM fdr33kv");
$total_33kv = $stmt->fetch()['count'];

$stmt = $db->query("SELECT COUNT(*) as count FROM iss_locations");
$total_iss = $stmt->fetch()['count'];

$stmt = $db->query("SELECT COUNT(*) as count FROM transmission_stations");
$total_ts = $stmt->fetch()['count'];

// Data Entry Statistics (Today)
$stmt = $db->query("
    SELECT COUNT(*) as entries_today
    FROM fdr11kv_data
    WHERE entry_date = '$today'
");
$entries_11kv_today = $stmt->fetch()['entries_today'];

$stmt = $db->query("
    SELECT COUNT(*) as entries_today
    FROM fdr33kv_data
    WHERE entry_date = '$today'
");
$entries_33kv_today = $stmt->fetch()['entries_today'];

// Correction Requests Summary
$stmt = $db->query("
    SELECT 
        status,
        COUNT(*) as count
    FROM load_corrections
    GROUP BY status
");
$correction_stats = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $correction_stats[$row['status']] = $row['count'];
}

$pending_corrections = $correction_stats['PENDING'] ?? 0;
$analyst_approved = $correction_stats['ANALYST_APPROVED'] ?? 0;
$manager_approved = $correction_stats['MANAGER_APPROVED'] ?? 0;
$rejected = $correction_stats['REJECTED'] ?? 0;

// Complaints Summary
$stmt = $db->query("
    SELECT 
        status,
        COUNT(*) as count
    FROM complaint_log
    GROUP BY status
");
$complaint_stats = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $complaint_stats[$row['status']] = $row['count'];
}

// System Uptime Calculation (simplified - based on data entry activity)
$expected_entries = $total_11kv * 24;
$actual_entries = $entries_11kv_today;
$system_uptime = $expected_entries > 0 ? min(($actual_entries / $expected_entries) * 100, 100) : 0;

// Data Quality Score
$data_quality = ($system_uptime + min($active_users / max($total_users, 1) * 100, 100)) / 2;

// System Health Score
$system_health = ($data_quality + $system_uptime) / 2;

// Critical Issues
$critical_issues = 0;
if ($system_health < 70) $critical_issues++;
if ($pending_corrections > 10) $critical_issues++;
if (isset($complaint_stats['Critical']) && $complaint_stats['Critical'] > 0) $critical_issues++;

// Include view
require __DIR__ . '/../views/admin/dashboard.php';
