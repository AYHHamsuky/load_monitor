<?php
// app/controllers/ReportsController.php

Guard::requireLogin();

$user = Auth::user();
$db = Database::connect();

// Get filters
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$feeder_type = $_GET['feeder_type'] ?? 'all'; // 11kv, 33kv, or all
$feeder_code = $_GET['feeder_code'] ?? null;

// Get all ISS locations for filter
$iss_list = $db->query("SELECT iss_code, iss_name FROM iss_locations ORDER BY iss_name")->fetchAll(PDO::FETCH_ASSOC);

// Get all 11kV feeders
$feeders_11kv = $db->query("
    SELECT f.fdr11kv_code, f.fdr11kv_name, i.iss_name
    FROM fdr11kv f
    LEFT JOIN iss_locations i ON f.iss_code = i.iss_code
    ORDER BY f.fdr11kv_name
")->fetchAll(PDO::FETCH_ASSOC);

// Get all 33kV feeders
$feeders_33kv = $db->query("
    SELECT f.fdr33kv_code, f.fdr33kv_name, t.station_name
    FROM fdr33kv f
    LEFT JOIN transmission_stations t ON f.ts_code = t.ts_code
    ORDER BY f.fdr33kv_name
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch data based on type
$report_data = [];

if ($feeder_type === '11kv' || $feeder_type === 'all') {
    $where = "WHERE entry_date BETWEEN ? AND ?";
    $params = [$date_from, $date_to];
    
    if ($feeder_code) {
        $where .= " AND feeder_code = ?";
        $params[] = $feeder_code;
    }
    
    $stmt = $db->prepare("
        SELECT 
            d.entry_date,
            d.entry_hour,
            d.fdr11kv_code,
            f.fdr11kv_name,
            i.iss_name,
            d.load_read,
            d.fault_code
        FROM fdr11kv_data d
        LEFT JOIN fdr11kv f ON d.fdr11kv_code = f.fdr11kv_code
        LEFT JOIN iss_locations i ON f.iss_code = i.iss_code
        $where
        ORDER BY d.entry_date DESC, d.entry_hour DESC, d.fdr11kv_code
    ");
    $stmt->execute($params);
    $report_data['11kv'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if ($feeder_type === '33kv' || $feeder_type === 'all') {
    $where = "WHERE entry_date BETWEEN ? AND ?";
    $params = [$date_from, $date_to];
    
    if ($feeder_code) {
        $where .= " AND feeder_code = ?";
        $params[] = $feeder_code;
    }
    
    $stmt = $db->prepare("
        SELECT 
            d.entry_date,
            d.entry_hour,
            d.fdr33kv_code,
            f.fdr33kv_name,
            t.station_name,
            d.load_read,
            d.fault_code
        FROM fdr33kv_data d
        LEFT JOIN fdr33kv f ON d.fdr33kv_code = f.fdr33kv_code
        LEFT JOIN transmission_stations t ON f.ts_code = t.ts_code
        $where
        ORDER BY d.entry_date DESC, d.entry_hour DESC, d.fdr33kv_code
    ");
    $stmt->execute($params);
    $report_data['33kv'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Calculate summary statistics
$summary = [
    'total_load_11kv' => 0,
    'total_load_33kv' => 0,
    'entries_11kv' => 0,
    'entries_33kv' => 0
];

if (isset($report_data['11kv'])) {
    foreach ($report_data['11kv'] as $row) {
        $summary['total_load_11kv'] += (float)$row['load_read'];
        $summary['entries_11kv']++;
    }
}

if (isset($report_data['33kv'])) {
    foreach ($report_data['33kv'] as $row) {
        $summary['total_load_33kv'] += (float)$row['load_read'];
        $summary['entries_33kv']++;
    }
}

require __DIR__ . '/../views/reports/index.php';
