<?php
// app/controllers/DashboardController.php - FIXED VERSION

Guard::requireUL1(); // Only UL1 (11kV Data Entry Staff)

$user = Auth::user();
$db = Database::connect();

// Get user's assigned ISS
$iss_code = $user['iss_code'];

// Get ISS details
$stmt = $db->prepare("
    SELECT * FROM iss_locations WHERE iss_code = ?
");
$stmt->execute([$iss_code]);
$iss = $stmt->fetch(PDO::FETCH_ASSOC);

// Get feeders under this ISS — include max_load for client-side validation
$stmt = $db->prepare("
    SELECT fdr11kv_code, fdr11kv_name, max_load
    FROM fdr11kv 
    WHERE iss_code = ?
    ORDER BY fdr11kv_name
");
$stmt->execute([$iss_code]);
$feeders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_feeders = count($feeders);

// Operational date: the day runs 01:00–00:59 next morning.
// Between 00:00 and 00:59 we are still in the PREVIOUS day's batch.
$now = new DateTime();
$today = ((int)$now->format('G') === 0)
    ? (clone $now)->modify('-1 day')->format('Y-m-d')
    : $now->format('Y-m-d');

// Get today's load data for all feeders
$stmt = $db->prepare("
    SELECT * FROM fdr11kv_data
    WHERE entry_date = ?
    AND fdr11kv_code IN (
        SELECT fdr11kv_code FROM fdr11kv WHERE iss_code = ?
    )
    ORDER BY fdr11kv_code, entry_hour
");
$stmt->execute([$today, $iss_code]);
$load_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate statistics
$total_load = 0;
$supply_hours = 0;
$peak_load = 0;
$total_faults = 0; // NEW: Track total faults
$feeders_with_faults = []; // NEW: Track which feeders have faults

foreach ($load_data as $row) {
    $load = floatval($row['load_read']);
    
    if ($load > 0) {
        $total_load += $load;
        $supply_hours++;
        if ($load > $peak_load) {
            $peak_load = $load;
        }
    }
    
    // NEW: Count faults
    if (!empty($row['fault_code'])) {
        $total_faults++;
        $feeders_with_faults[$row['fdr11kv_code']] = true;
    }
}

$avg_load = $supply_hours > 0 ? $total_load / $supply_hours : 0;

// NEW: Count unique feeders with faults
$feeders_with_faults = count($feeders_with_faults);

// Calculate completion percentage
$required_entries = $total_feeders * 24; // 24 hours per feeder
$actual_entries = count($load_data);
$completion_percentage = $required_entries > 0 
    ? ($actual_entries / $required_entries) * 100 
    : 0;

// Build hourly matrix
$matrix = [];

foreach ($feeders as $feeder) {
    $code = $feeder['fdr11kv_code'];
    $matrix[$code] = [
        'fdr11kv_code' => $code,
        'fdr11kv_name' => $feeder['fdr11kv_name'],
        'hours' => array_fill(0, 24, null)  // indices 0-23
    ];
}

foreach ($load_data as $row) {
    $code = $row['fdr11kv_code'];
    $hour = (int)$row['entry_hour'];
    
    if (isset($matrix[$code]) && $hour >= 0 && $hour <= 23) {
        $matrix[$code]['hours'][$hour] = [
            'load' => $row['load_read'],
            'fault' => $row['fault_code'] ?? ''
        ];
    }
}

// Load the view
require __DIR__ . '/../views/dashboard/index.php';