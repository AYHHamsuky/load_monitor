<?php
// app/controllers/StaffDashboardController.php

Guard::requireLogin();

// Ensure only UL5 can access
$user = Auth::user();
if ($user['role'] !== 'UL5') {
    header('Location: ?page=dashboard');
    exit;
}

$db = Database::connect();
$today = date('Y-m-d');

// Get system overview statistics
// 11kV Data Summary
$stmt = $db->query("
    SELECT 
        COUNT(DISTINCT fdr11kv_code) as total_feeders,
        COUNT(*) as total_entries,
        SUM(load_read) as total_load
    FROM fdr11kv_data
    WHERE entry_date = '$today'
");
$data_11kv = $stmt->fetch(PDO::FETCH_ASSOC);

// 33kV Data Summary
$stmt = $db->query("
    SELECT 
        COUNT(DISTINCT fdr33kv_code) as total_feeders,
        COUNT(*) as total_entries,
        SUM(load_read) as total_load
    FROM fdr33kv_data
    WHERE entry_date = '$today'
");
$data_33kv = $stmt->fetch(PDO::FETCH_ASSOC);

// Total Interruptions (MTD)
$first_day = date('Y-m-01');
$stmt = $db->query("
    SELECT 
        COUNT(*) as total_interruptions,
        SUM(TIMESTAMPDIFF(MINUTE, datetime_out, datetime_in)) as total_minutes
    FROM interruptions
    WHERE datetime_out BETWEEN '$first_day' AND '$today'
");
$interruptions = $stmt->fetch(PDO::FETCH_ASSOC);
$total_interruptions = $interruptions['total_interruptions'] ?? 0;
$total_downtime = round(($interruptions['total_minutes'] ?? 0) / 60, 2);

// Active Complaints
$stmt = $db->query("
    SELECT COUNT(*) as active_count
    FROM complaint_log
    WHERE status NOT IN ('Resolved', 'Closed')
");
$complaints = $stmt->fetch(PDO::FETCH_ASSOC);
$active_complaints = $complaints['active_count'] ?? 0;

// Pending Corrections
$stmt = $db->query("
    SELECT COUNT(*) as pending_count
    FROM load_corrections
    WHERE status = 'PENDING'
");
$corrections = $stmt->fetch(PDO::FETCH_ASSOC);
$pending_corrections = $corrections['pending_count'] ?? 0;

// Data Completeness (11kV)
$stmt = $db->query("SELECT COUNT(*) as total FROM fdr11kv");
$total_11kv_feeders = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$expected_entries_11kv = $total_11kv_feeders * 24;
$actual_entries_11kv = $data_11kv['total_entries'] ?? 0;
$completeness_11kv = $expected_entries_11kv > 0 ? ($actual_entries_11kv / $expected_entries_11kv) * 100 : 0;

// Recent Reports (for viewing)
$stmt = $db->query("
    SELECT 
        'Daily Load Report' as report_name,
        '$today' as report_date,
        'System Generated' as created_by,
        'Available' as status
    LIMIT 5
");
$recent_reports = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Load trend (Last 7 days) - 11kV
$stmt = $db->query("
    SELECT 
        entry_date,
        SUM(load_read) as total_load,
        COUNT(*) as entries
    FROM fdr11kv_data
    WHERE entry_date >= date('$today', '-7 days')
    GROUP BY entry_date
    ORDER BY entry_date ASC
");
$load_trend = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include view
require __DIR__ . '/../views/staff/dashboard.php';
