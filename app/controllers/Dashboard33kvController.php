<?php
// app/controllers/Dashboard33kvController.php

Guard::requireUL2(); // Only UL2 can access

$user = Auth::user();
$db = Database::connect();

// Operational date: day runs 01:00-00:59 next morning.
// Between 00:00 and 00:59 we are still in the PREVIOUS day's batch.
$now = new DateTime();
$today = ((int)$now->format('G') === 0)
    ? (clone $now)->modify('-1 day')->format('Y-m-d')
    : $now->format('Y-m-d');

// Transmission stations for dropdown
$ts_stmt = $db->query("
    SELECT DISTINCT ts_code, station_name
    FROM transmission_stations
    ORDER BY station_name
");
$transmission_stations = $ts_stmt->fetchAll(PDO::FETCH_ASSOC);

$selected_ts = isset($_GET['ts_code']) && $_GET['ts_code'] !== '' ? $_GET['ts_code'] : 'all';
if (empty($selected_ts)) $selected_ts = 'all';

// Build WHERE for filtered feeders
$where_clauses = [];
$params = [];
if ($selected_ts && $selected_ts !== 'all') {
    $where_clauses[] = "f.ts_code = ?";
    $params[] = $selected_ts;
}
$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Filtered feeders — includes max_load
$feeder_stmt = $db->prepare("
    SELECT
        f.fdr33kv_code,
        f.fdr33kv_name,
        f.ts_code,
        f.max_load,
        t.station_name
    FROM fdr33kv f
    LEFT JOIN transmission_stations t ON f.ts_code = t.ts_code
    $where_sql
    ORDER BY t.station_name, f.fdr33kv_name
");
$feeder_stmt->execute($params);
$feeders = $feeder_stmt->fetchAll(PDO::FETCH_ASSOC);

// All feeders for modal dropdown — also includes max_load
$all_feeders_stmt = $db->query("
    SELECT
        f.fdr33kv_code,
        f.fdr33kv_name,
        f.ts_code,
        f.max_load,
        t.station_name
    FROM fdr33kv f
    LEFT JOIN transmission_stations t ON f.ts_code = t.ts_code
    ORDER BY t.station_name, f.fdr33kv_name
");
$all_feeders = $all_feeders_stmt->fetchAll(PDO::FETCH_ASSOC);

// Today's load data for filtered feeders
$load_data = [];
$matrix = [];
$feeder_summary = [];

if (!empty($feeders)) {
    $feeder_codes = array_column($feeders, 'fdr33kv_code');
    $placeholders = str_repeat('?,', count($feeder_codes) - 1) . '?';

    $data_stmt = $db->prepare("
        SELECT
            fdr33kv_code,
            entry_hour,
            load_read,
            fault_code,
            fault_remark
        FROM fdr33kv_data
        WHERE fdr33kv_code IN ($placeholders)
          AND entry_date = ?
        ORDER BY fdr33kv_code, entry_hour
    ");
    $data_stmt->execute([...$feeder_codes, $today]);
    $load_data = $data_stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($feeders as $feeder) {
        $code = $feeder['fdr33kv_code'];
        $matrix[$code] = [
            'fdr33kv_name' => $feeder['fdr33kv_name'],
            'ts_code'      => $feeder['ts_code'],
            'station_name' => $feeder['station_name'],
            'max_load'     => $feeder['max_load'],
            'hours'        => array_fill(0, 24, null),  // keys 0-23
        ];
        $feeder_summary[$code] = [
            'fdr33kv_name' => $feeder['fdr33kv_name'],
            'station_name' => $feeder['station_name'],
            'total_load'   => 0, 'peak_load'    => 0,
            'supply_hours' => 0, 'fault_count'  => 0,
            'fault_hours'  => 0, 'entries_count'=> 0,
            'avg_load'     => 0,
        ];
    }

    foreach ($load_data as $row) {
        $code  = $row['fdr33kv_code'];
        $hour  = (int)$row['entry_hour'];
        $load  = (float)$row['load_read'];
        $fault = $row['fault_code'];

        if (isset($matrix[$code]) && $hour >= 0 && $hour <= 23) {
            $matrix[$code]['hours'][$hour] = [
                'load'         => $load,
                'fault'        => $fault,
                'fault_remark' => $row['fault_remark'],
            ];
        }

        if (isset($feeder_summary[$code])) {
            $feeder_summary[$code]['entries_count']++;
            if ($load > 0) {
                $feeder_summary[$code]['total_load']  += $load;
                $feeder_summary[$code]['supply_hours']++;
                $feeder_summary[$code]['peak_load'] = max($feeder_summary[$code]['peak_load'], $load);
            }
            if (!empty($fault)) {
                $feeder_summary[$code]['fault_count']++;
                if ($load == 0) $feeder_summary[$code]['fault_hours']++;
            }
        }
    }

    foreach ($feeder_summary as $code => &$summary) {
        $summary['avg_load'] = $summary['supply_hours'] > 0
            ? $summary['total_load'] / $summary['supply_hours'] : 0;
    }
}

// Overall statistics
$total_feeders          = count($feeders);
$total_possible_entries = $total_feeders * 24;
$total_entries          = count($load_data);
$completion_percentage  = $total_possible_entries > 0
    ? ($total_entries / $total_possible_entries) * 100 : 0;

$total_load  = 0; $peak_load = 0; $supply_hours = 0; $fault_hours = 0;
foreach ($load_data as $row) {
    $load        = (float)$row['load_read'];
    $total_load += $load;
    if ($load > 0)              { $supply_hours++; $peak_load = max($peak_load, $load); }
    if (!empty($row['fault_code'])) $fault_hours++;
}
$avg_load = $supply_hours > 0 ? $total_load / $supply_hours : 0;

// Hourly chart data — indices 0-23
$hourly_data = array_fill(0, 24, 0);
foreach ($load_data as $row) {
    $h = (int)$row['entry_hour'];
    if ($h >= 0 && $h <= 23) $hourly_data[$h] += (float)$row['load_read'];
}

// Feeder totals for pie chart
$feeder_totals = [];
foreach ($matrix as $code => $data) {
    $ft = 0;
    foreach ($data['hours'] as $hd) { if ($hd) $ft += (float)$hd['load']; }
    if ($ft > 0) $feeder_totals[$data['fdr33kv_name']] = $ft;
}

// TS totals
$ts_totals = [];
$ts_all_stmt = $db->query("
    SELECT t.ts_code, t.station_name,
           COALESCE(SUM(d.load_read), 0) as total_load
    FROM transmission_stations t
    LEFT JOIN fdr33kv f ON t.ts_code = f.ts_code
    LEFT JOIN fdr33kv_data d ON f.fdr33kv_code = d.fdr33kv_code AND d.entry_date = '$today'
    GROUP BY t.ts_code, t.station_name
    ORDER BY total_load DESC
");
foreach ($ts_all_stmt->fetchAll(PDO::FETCH_ASSOC) as $ts) {
    if ($ts['total_load'] > 0) $ts_totals[$ts['station_name']] = (float)$ts['total_load'];
}

// Fault codes
$fault_codes = [
    'FO' => 'Feeder Off', 'BF' => 'Breaker Fault',
    'OS' => 'Out of Service', 'DOff' => 'Deliberately Off', 'MVR' => 'Maintenance/Repair',
];

require __DIR__ . '/../views/dashboard33kv/index.php';
