<?php
// app/controllers/AnalyticsController.php

Guard::requireLogin();

$user = Auth::user();
$db = Database::connect();

// Only UL3, UL4, and UL5 can access analytics
if (!in_array($user['role'], ['UL3', 'UL4', 'UL5'])) {
    die('Access denied. Analytics module requires UL3, UL4, or UL5 role.');
}

// Get action
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        // Get all saved analytics/reports
        $stmt = $db->prepare("
            SELECT 
                id,
                report_name,
                report_type,
                description,
                created_by,
                created_at,
                is_public
            FROM analytics_reports
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get creator names
        foreach ($reports as &$report) {
            $stmt = $db->prepare("SELECT staff_name FROM staff_details WHERE payroll_id = ?");
            $stmt->execute([$report['created_by']]);
            $creator = $stmt->fetch();
            $report['creator_name'] = $creator ? $creator['staff_name'] : 'Unknown';
        }
        
        require __DIR__ . '/../views/analytics/index.php';
        break;
        
    case 'create':
        // Only UL3 can create analytics
        if ($user['role'] !== 'UL3') {
            die('Access denied. Only Analysts (UL3) can create analytics reports.');
        }
        
        // Get master data for dropdowns
        $iss_list = $db->query("SELECT iss_code, iss_name FROM iss_locations ORDER BY iss_name")->fetchAll(PDO::FETCH_ASSOC);
        $ts_list = $db->query("SELECT ts_code, station_name FROM transmission_stations ORDER BY station_name")->fetchAll(PDO::FETCH_ASSOC);
        
        require __DIR__ . '/../views/analytics/create.php';
        break;
        
    case 'view':
        $report_id = $_GET['id'] ?? null;
        
        if (!$report_id) {
            header('Location: ?page=analytics');
            exit;
        }
        
        // Get report details
        $stmt = $db->prepare("SELECT * FROM analytics_reports WHERE id = ?");
        $stmt->execute([$report_id]);
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$report) {
            header('Location: ?page=analytics');
            exit;
        }
        
        // Get creator name
        $stmt = $db->prepare("SELECT staff_name FROM staff_details WHERE payroll_id = ?");
        $stmt->execute([$report['created_by']]);
        $creator = $stmt->fetch();
        $report['creator_name'] = $creator ? $creator['staff_name'] : 'Unknown';
        
        // Decode parameters
        $parameters = json_decode($report['parameters'], true);
        
        // Generate report data based on type
        $report_data = generateReportData($db, $report['report_type'], $parameters);
        
        require __DIR__ . '/../views/analytics/view.php';
        break;
        
    case 'generate':
        // Live report generation (without saving)
        $report_type = $_GET['type'] ?? '';
        
        if (empty($report_type)) {
            die('Report type is required');
        }
        
        // Get parameters from query string
        $parameters = [
            'date_from' => $_GET['date_from'] ?? date('Y-m-01'),
            'date_to' => $_GET['date_to'] ?? date('Y-m-d'),
            'iss_code' => $_GET['iss_code'] ?? null,
            'ts_code' => $_GET['ts_code'] ?? null,
            'feeder_type' => $_GET['feeder_type'] ?? 'both'
        ];
        
        // Generate report data
        $report_data = generateReportData($db, $report_type, $parameters);
        $report = [
            'report_name' => 'Live Report - ' . ucfirst(str_replace('_', ' ', $report_type)),
            'report_type' => $report_type,
            'description' => 'Generated on-demand',
            'created_by' => $user['payroll_id'],
            'creator_name' => $user['staff_name'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        require __DIR__ . '/../views/analytics/view.php';
        break;
        
    default:
        header('Location: ?page=analytics');
        break;
}

/**
 * Generate report data based on type
 */
function generateReportData($db, $report_type, $parameters) {
    $date_from = $parameters['date_from'] ?? date('Y-m-01');
    $date_to = $parameters['date_to'] ?? date('Y-m-d');
    $iss_code = $parameters['iss_code'] ?? null;
    $ts_code = $parameters['ts_code'] ?? null;
    
    switch ($report_type) {
        case 'load_summary':
            return generateLoadSummary($db, $date_from, $date_to, $iss_code);
            
        case 'interruption_analysis':
            return generateInterruptionAnalysis($db, $date_from, $date_to, $ts_code);
            
        case 'data_quality':
            return generateDataQualityReport($db, $date_from, $date_to);
            
        case 'peak_demand':
            return generatePeakDemandReport($db, $date_from, $date_to, $iss_code);
            
        case 'feeder_performance':
            return generateFeederPerformance($db, $date_from, $date_to);
            
        case 'complaint_trends':
            return generateComplaintTrends($db, $date_from, $date_to);
            
        default:
            return [];
    }
}

function generateLoadSummary($db, $date_from, $date_to, $iss_code = null) {
    $where = "WHERE entry_date BETWEEN ? AND ?";
    $params = [$date_from, $date_to];
    
    if ($iss_code) {
        $where .= " AND f.iss_code = ?";
        $params[] = $iss_code;
    }
    
    // 11kV Summary
    $stmt = $db->prepare("
        SELECT 
            DATE(d.entry_date) as date,
            COUNT(DISTINCT d.fdr11kv_code) as feeders,
            COUNT(*) as total_entries,
            SUM(d.load_read) as total_load,
            AVG(d.load_read) as avg_load,
            MAX(d.load_read) as peak_load,
            SUM(CASE WHEN d.fault_code IS NOT NULL THEN 1 ELSE 0 END) as fault_count
        FROM fdr11kv_data d
        LEFT JOIN fdr11kv f ON d.fdr11kv_code = f.fdr11kv_code
        $where
        GROUP BY DATE(d.entry_date)
        ORDER BY date
    ");
    $stmt->execute($params);
    $load_11kv = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 33kV Summary
    $where_33kv = "WHERE entry_date BETWEEN ? AND ?";
    $params_33kv = [$date_from, $date_to];
    
    if ($ts_code ?? null) {
        $where_33kv .= " AND f.ts_code = ?";
        $params_33kv[] = $ts_code;
    }
    
    $stmt = $db->prepare("
        SELECT 
            DATE(d.entry_date) as date,
            COUNT(DISTINCT d.fdr33kv_code) as feeders,
            COUNT(*) as total_entries,
            SUM(d.load_read) as total_load,
            AVG(d.load_read) as avg_load,
            MAX(d.load_read) as peak_load,
            SUM(CASE WHEN d.fault_code IS NOT NULL THEN 1 ELSE 0 END) as fault_count
        FROM fdr33kv_data d
        LEFT JOIN fdr33kv f ON d.fdr33kv_code = f.fdr33kv_code
        $where_33kv
        GROUP BY DATE(d.entry_date)
        ORDER BY date
    ");
    $stmt->execute($params_33kv);
    $load_33kv = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'load_11kv' => $load_11kv,
        'load_33kv' => $load_33kv,
        'summary' => [
            'total_11kv_load' => array_sum(array_column($load_11kv, 'total_load')),
            'total_33kv_load' => array_sum(array_column($load_33kv, 'total_load')),
            'avg_11kv_load' => count($load_11kv) > 0 ? array_sum(array_column($load_11kv, 'avg_load')) / count($load_11kv) : 0,
            'avg_33kv_load' => count($load_33kv) > 0 ? array_sum(array_column($load_33kv, 'avg_load')) / count($load_33kv) : 0,
            'total_faults' => array_sum(array_column($load_11kv, 'fault_count')) + array_sum(array_column($load_33kv, 'fault_count'))
        ]
    ];
}

function generateInterruptionAnalysis($db, $date_from, $date_to, $ts_code = null) {
    $where = "WHERE datetime_out BETWEEN ? AND ?";
    $params = [$date_from, $date_to];
    
    if ($ts_code) {
        $where .= " AND feeder_code IN (SELECT feeder_code FROM fdr33kv WHERE ts_code = ?)";
        $params[] = $ts_code;
    }
    
    $stmt = $db->prepare("
        SELECT 
            interruption_type,
            COUNT(*) as count,
            SUM(TIMESTAMPDIFF(MINUTE, datetime_out, datetime_in)) as total_minutes,
            AVG(TIMESTAMPDIFF(MINUTE, datetime_out, datetime_in)) as avg_minutes,
            SUM(load_loss) as total_load_loss
        FROM interruptions
        $where
        GROUP BY interruption_type
    ");
    $stmt->execute($params);
    $by_type = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // By reason
    $stmt = $db->prepare("
        SELECT 
            reason_for_interruption,
            COUNT(*) as count,
            SUM(TIMESTAMPDIFF(MINUTE, datetime_out, datetime_in)) as total_minutes
        FROM interruptions
        $where
        GROUP BY reason_for_interruption
        ORDER BY count DESC
        LIMIT 10
    ");
    $stmt->execute($params);
    $by_reason = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Monthly trend
    $stmt = $db->prepare("
        SELECT 
            DATE_FORMAT(datetime_out, '%Y-%m') as month,
            COUNT(*) as count,
            SUM(TIMESTAMPDIFF(MINUTE, datetime_out, datetime_in)) as total_minutes
        FROM interruptions
        $where
        GROUP BY DATE_FORMAT(datetime_out, '%Y-%m')
        ORDER BY month
    ");
    $stmt->execute($params);
    $trend = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'by_type' => $by_type,
        'by_reason' => $by_reason,
        'trend' => $trend
    ];
}

function generateDataQualityReport($db, $date_from, $date_to) {
    // 11kV Data Quality
    $total_11kv_feeders = $db->query("SELECT COUNT(*) as count FROM fdr11kv")->fetch()['count'];
    $days = (strtotime($date_to) - strtotime($date_from)) / 86400 + 1;
    $expected_entries = $total_11kv_feeders * 24 * $days;
    
    $stmt = $db->prepare("
        SELECT COUNT(*) as actual_entries
        FROM fdr11kv_data
        WHERE entry_date BETWEEN ? AND ?
    ");
    $stmt->execute([$date_from, $date_to]);
    $actual_11kv = $stmt->fetch()['actual_entries'];
    
    // 33kV Data Quality
    $total_33kv_feeders = $db->query("SELECT COUNT(*) as count FROM fdr33kv")->fetch()['count'];
    $expected_entries_33kv = $total_33kv_feeders * 24 * $days;
    
    $stmt = $db->prepare("
        SELECT COUNT(*) as actual_entries
        FROM fdr33kv_data
        WHERE entry_date BETWEEN ? AND ?
    ");
    $stmt->execute([$date_from, $date_to]);
    $actual_33kv = $stmt->fetch()['actual_entries'];
    
    // Daily completeness
    $stmt = $db->prepare("
        SELECT 
            entry_date,
            COUNT(*) as entries,
            ? as expected
        FROM fdr11kv_data
        WHERE entry_date BETWEEN ? AND ?
        GROUP BY entry_date
        ORDER BY entry_date
    ");
    $stmt->execute([$total_11kv_feeders * 24, $date_from, $date_to]);
    $daily_quality = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'completeness_11kv' => ($actual_11kv / max($expected_entries, 1)) * 100,
        'completeness_33kv' => ($actual_33kv / max($expected_entries_33kv, 1)) * 100,
        'expected_11kv' => $expected_entries,
        'actual_11kv' => $actual_11kv,
        'expected_33kv' => $expected_entries_33kv,
        'actual_33kv' => $actual_33kv,
        'daily_quality' => $daily_quality
    ];
}

function generatePeakDemandReport($db, $date_from, $date_to, $iss_code = null) {
    $where = "WHERE d.entry_date BETWEEN ? AND ?";
    $params = [$date_from, $date_to];
    
    if ($iss_code) {
        $where .= " AND f.iss_code = ?";
        $params[] = $iss_code;
    }
    
    // Peak by hour
    $stmt = $db->prepare("
        SELECT 
            d.entry_hour,
            AVG(d.load_read) as avg_load,
            MAX(d.load_read) as peak_load,
            COUNT(*) as readings
        FROM fdr11kv_data d
        LEFT JOIN fdr11kv f ON d.fdr11kv_code = f.fdr11kv_code
        $where AND d.load_read > 0
        GROUP BY d.entry_hour
        ORDER BY d.entry_hour
    ");
    $stmt->execute($params);
    $hourly_demand = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Top feeders by peak load
    $stmt = $db->prepare("
        SELECT 
            d.fdr11kv_code,
            f.fdr11kv_code,
            MAX(d.load_read) as peak_load,
            AVG(d.load_read) as avg_load
        FROM fdr11kv_data d
        LEFT JOIN fdr11kv f ON d.fdr11kv_code = f.fdr11kv_code
        $where AND d.load_read > 0
        GROUP BY d.fdr11kv_code
        ORDER BY peak_load DESC
        LIMIT 20
    ");
    $stmt->execute($params);
    $top_feeders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'hourly_demand' => $hourly_demand,
        'top_feeders' => $top_feeders
    ];
}

function generateFeederPerformance($db, $date_from, $date_to) {
    // 11kV Feeder Performance
    $stmt = $db->prepare("
        SELECT 
            d.fdr11kv_code,
            f.fdr11kv_name,
            f.iss_code,
            i.iss_name,
            COUNT(*) as total_entries,
            SUM(d.load_read) as total_load,
            AVG(d.load_read) as avg_load,
            MAX(d.load_read) as peak_load,
            SUM(CASE WHEN d.fault_code IS NOT NULL THEN 1 ELSE 0 END) as fault_count,
            (SUM(CASE WHEN d.fault_code IS NOT NULL THEN 1 ELSE 0 END) / COUNT(*)) * 100 as fault_percentage
        FROM fdr11kv_data d
        LEFT JOIN fdr11kv f ON d.fdr11kv_code = f.fdr11kv_code
        LEFT JOIN iss_locations i ON f.iss_code = i.iss_code
        WHERE d.entry_date BETWEEN ? AND ?
        GROUP BY d.fdr11kv_code
        ORDER BY total_load DESC
    ");
    $stmt->execute([$date_from, $date_to]);
    $feeder_performance = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'feeders' => $feeder_performance
    ];
}

function generateComplaintTrends($db, $date_from, $date_to) {
    // By status
    $stmt = $db->prepare("
        SELECT 
            status,
            COUNT(*) as count
        FROM complaint_log
        WHERE logged_at BETWEEN ? AND ?
        GROUP BY status
    ");
    $stmt->execute([$date_from, $date_to]);
    $by_status = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // By type
    $stmt = $db->prepare("
        SELECT 
            complaint_type,
            COUNT(*) as count
        FROM complaint_log
        WHERE logged_at BETWEEN ? AND ?
        GROUP BY complaint_type
        ORDER BY count DESC
    ");
    $stmt->execute([$date_from, $date_to]);
    $by_type = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // By priority
    $stmt = $db->prepare("
        SELECT 
            priority,
            COUNT(*) as count,
            AVG(TIMESTAMPDIFF(HOUR, logged_at, resolved_at)) as avg_resolution_hours
        FROM complaint_log
        WHERE logged_at BETWEEN ? AND ?
        GROUP BY priority
    ");
    $stmt->execute([$date_from, $date_to]);
    $by_priority = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Daily trend
    $stmt = $db->prepare("
        SELECT 
            DATE(logged_at) as date,
            COUNT(*) as count
        FROM complaint_log
        WHERE logged_at BETWEEN ? AND ?
        GROUP BY DATE(logged_at)
        ORDER BY date
    ");
    $stmt->execute([$date_from, $date_to]);
    $daily_trend = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return [
        'by_status' => $by_status,
        'by_type' => $by_type,
        'by_priority' => $by_priority,
        'daily_trend' => $daily_trend
    ];
}
