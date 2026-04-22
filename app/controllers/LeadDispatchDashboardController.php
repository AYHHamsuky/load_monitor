<?php
/**
 * UL8 Lead Dispatch Dashboard Controller - CORRECTED FOR ACTUAL SCHEMA
 * Path: /app/controllers/LeadDispatchDashboardController.php
 * 
 * Main dashboard controller for UL8 Lead Dispatch role
 * Routes to all 6 views based on action parameter
 * 
 * IMPORTANT: Only uses columns that actually exist in database
 * - fdr33kv: fdr33kv_code, fdr33kv_name, ts_code
 * - transmission_stations: ts_code, station_name
 * - NO region_code, NO area_office_code in these tables
 */

// Ensure user is logged in and has UL8 role
Guard::requireLogin();
$user = Auth::user();

if ($user['role'] !== 'UL8') {
    http_response_code(403);
    die('Access Denied: This page is only accessible to Lead Dispatch Officers (UL8).');
}

$db = Database::connect();

// Get selected date (default to today)
$selected_date = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_date)) {
    $selected_date = date('Y-m-d');
}

// Get action parameter for navigation
$action = $_GET['action'] ?? '';

// Route to appropriate view based on action
switch ($action) {
    case '11kv':
        // 11kV Hourly Load Matrix
        require __DIR__ . '/../views/lead_dispatch/11kv_matrix.php';
        break;
        
    case '33kv':
        // 33kV Hourly Load Matrix
        require __DIR__ . '/../views/lead_dispatch/33kv_matrix.php';
        break;
        
    case 'staff':
        // Staff on Duty Tracker
        require __DIR__ . '/../views/lead_dispatch/staff_on_duty.php';
        break;
        
    case 'interruptions':
        // Interruptions Monitor (with tabs for 11kV and 33kV)
        // Uses: interruptions_11kv and interruptions (33kV) tables
        // Features: Tabs, hourly matrix, filters by ISS/TS/Type
        require __DIR__ . '/../views/lead_dispatch/interruptions.php';
        break;
        
    case 'statistics':
        // Load Statistics by Hierarchy
        require __DIR__ . '/../views/lead_dispatch/load_statistics.php';
        break;
        
    default:
        // Default: Main Dashboard with Overview
        // ========== SYSTEM-WIDE STATISTICS ==========

        // Get 11kV Statistics
        $stmt_11kv = $db->prepare("
            SELECT 
                COUNT(DISTINCT f.fdr11kv_code) as total_feeders,
                COUNT(DISTINCT f.iss_code) as total_iss,
                COUNT(DISTINCT d.entry_hour) as total_entries,
                COALESCE(SUM(d.load_read), 0) as total_load,
                COALESCE(AVG(d.load_read), 0) as avg_load,
                COALESCE(MAX(d.load_read), 0) as peak_load,
                COUNT(CASE WHEN d.load_read > 0 THEN 1 END) as supply_hours,
                COUNT(CASE WHEN d.fault_code IS NOT NULL AND d.fault_code != '' THEN 1 END) as fault_hours
            FROM fdr11kv f
            LEFT JOIN fdr11kv_data d ON d.Fdr11kv_code = f.fdr11kv_code AND d.entry_date = ?
        ");
        $stmt_11kv->execute([$selected_date]);
        $stats_11kv = $stmt_11kv->fetch(PDO::FETCH_ASSOC);

        // Get 33kV Statistics - CORRECTED: Only use columns that exist
        $stmt_33kv = $db->prepare("
            SELECT 
                COUNT(DISTINCT f.fdr33kv_code) as total_feeders,
                COUNT(DISTINCT f.ts_code) as total_ts,
                COUNT(DISTINCT d.entry_hour) as total_entries,
                COALESCE(SUM(d.load_read), 0) as total_load,
                COALESCE(AVG(d.load_read), 0) as avg_load,
                COALESCE(MAX(d.load_read), 0) as peak_load,
                COUNT(CASE WHEN d.load_read > 0 THEN 1 END) as supply_hours,
                COUNT(CASE WHEN d.fault_code IS NOT NULL AND d.fault_code != '' THEN 1 END) as fault_hours
            FROM fdr33kv f
            LEFT JOIN fdr33kv_data d ON d.Fdr33kv_code = f.fdr33kv_code AND d.entry_date = ?
        ");
        $stmt_33kv->execute([$selected_date]);
        $stats_33kv = $stmt_33kv->fetch(PDO::FETCH_ASSOC);

        // Combined System Statistics
        $system_stats = [
            'total_feeders' => $stats_11kv['total_feeders'] + $stats_33kv['total_feeders'],
            'total_11kv_feeders' => $stats_11kv['total_feeders'],
            'total_33kv_feeders' => $stats_33kv['total_feeders'],
            'total_iss' => $stats_11kv['total_iss'],
            'total_ts' => $stats_33kv['total_ts'],
            'total_load' => $stats_11kv['total_load'] + $stats_33kv['total_load'],
            'load_11kv' => $stats_11kv['total_load'],
            'load_33kv' => $stats_33kv['total_load'],
            'avg_load' => ($stats_11kv['avg_load'] + $stats_33kv['avg_load']) / 2,
            'peak_load' => max($stats_11kv['peak_load'], $stats_33kv['peak_load']),
            'supply_hours' => $stats_11kv['supply_hours'] + $stats_33kv['supply_hours'],
            'fault_hours' => $stats_11kv['fault_hours'] + $stats_33kv['fault_hours'],
            'data_completion' => (($stats_11kv['total_entries'] + $stats_33kv['total_entries']) / 
                                 (($stats_11kv['total_feeders'] + $stats_33kv['total_feeders']) * 24)) * 100
        ];

        // ========== STAFF ON DUTY TODAY ==========
        if (isset($_GET['ajax']) && $_GET['ajax'] === 'details' && isset($_GET['payroll_id'])) {
    header('Content-Type: application/json');
    
    $payroll_id = $_GET['payroll_id'];
    $selected_date = $_GET['date'] ?? date('Y-m-d');
    
    // Check if staff_sessions table exists
    $use_sessions_table = false;
    try {
        $db->query("SELECT 1 FROM staff_sessions LIMIT 1");
        $use_sessions_table = true;
    } catch (Exception $e) {
        $use_sessions_table = false;
    }
    
    // Get staff basic info
    $stmt = $db->prepare("
        SELECT 
            s.payroll_id,
            s.staff_name,
            s.role,
            s.iss_code,
            iss.iss_name,
            CASE 
                WHEN s.role = 'UL1' THEN '11kV Data Entry'
                WHEN s.role = 'UL2' THEN '33kV Data Entry'
                WHEN s.role = 'UL3' THEN 'Analyst'
                WHEN s.role = 'UL4' THEN 'Manager'
                WHEN s.role = 'UL5' THEN 'Senior Manager'
                WHEN s.role = 'UL6' THEN 'Administrator'
                WHEN s.role = 'UL7' THEN 'System Admin'
                WHEN s.role = 'UL8' THEN 'Lead Dispatch'
                ELSE s.role
            END as role_name,
            CASE 
                WHEN s.role = 'UL1' THEN iss.iss_name
                WHEN s.role = 'UL2' THEN ts.station_name
                ELSE 'System Wide'
            END as assigned_location
        FROM staff_details s
        LEFT JOIN iss_locations iss ON iss.iss_code = s.iss_code
        LEFT JOIN fdr33kv f33 ON f33.fdr33kv_code = s.assigned_33kv_code
        LEFT JOIN transmission_stations ts ON ts.ts_code = f33.ts_code
        WHERE s.payroll_id = ?
    ");
    $stmt->execute([$payroll_id]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$staff) {
        echo json_encode(['error' => 'Staff not found']);
        exit;
    }
    
    $response = [
        'staff_name' => $staff['staff_name'],
        'payroll_id' => $staff['payroll_id'],
        'role_name' => $staff['role_name'],
        'assigned_location' => $staff['assigned_location'],
        'login_time' => '-',
        'logout_time' => null,
        'session_duration' => null,
        'total_activities' => 0,
        'activities' => []
    ];
    
    if ($use_sessions_table) {
        // Get session info from staff_sessions
        $stmt_session = $db->prepare("
            SELECT 
                login_time,
                logout_time,
                session_duration
            FROM staff_sessions
            WHERE payroll_id = ? AND DATE(login_time) = ?
            ORDER BY login_time DESC
            LIMIT 1
        ");
        $stmt_session->execute([$payroll_id, $selected_date]);
        $session = $stmt_session->fetch(PDO::FETCH_ASSOC);
        
        if ($session) {
            $response['login_time'] = date('h:i A', strtotime($session['login_time']));
            $response['logout_time'] = $session['logout_time'] ? date('h:i A', strtotime($session['logout_time'])) : null;
            $response['session_duration'] = $session['session_duration'] ? number_format($session['session_duration'], 2) . ' hours' : null;
        }
        
        // Get activities from staff_activity_logs
        $stmt_activities = $db->prepare("
            SELECT 
                activity_time,
                activity_type,
                activity_description,
                related_table,
                related_id
            FROM staff_activity_logs
            WHERE payroll_id = ? AND DATE(activity_time) = ?
            ORDER BY activity_time ASC
        ");
        $stmt_activities->execute([$payroll_id, $selected_date]);
        $activities = $stmt_activities->fetchAll(PDO::FETCH_ASSOC);
        
        $response['total_activities'] = count($activities);
        
        foreach ($activities as $activity) {
            $description = $activity['activity_description'];
            
            // Generate human-readable description if not set
            if (empty($description)) {
                $type_map = [
                    'LOGIN' => 'Logged into system',
                    'LOGOUT' => 'Logged out of system',
                    'DATA_ENTRY' => 'Entered load data',
                    'DATA_EDIT' => 'Edited load data',
                    'CORRECTION_REQUEST' => 'Requested correction',
                    'INTERRUPTION_LOG' => 'Logged interruption',
                ];
                $description = $type_map[$activity['activity_type']] ?? $activity['activity_type'];
                
                if ($activity['related_table'] && $activity['related_id']) {
                    $description .= " ({$activity['related_table']}: {$activity['related_id']})";
                }
            }
            
            $response['activities'][] = [
                'time' => date('h:i A', strtotime($activity['activity_time'])),
                'type' => $activity['activity_type'],
                'description' => $description
            ];
        }
        
    } else {
        // Fallback: Use activity_logs
        $stmt_login = $db->prepare("
            SELECT login_time
            FROM activity_logs
            WHERE payroll_id = ? AND DATE(login_time) = ? AND action = 'LOGIN'
            ORDER BY login_time DESC
            LIMIT 1
        ");
        $stmt_login->execute([$payroll_id, $selected_date]);
        $login = $stmt_login->fetch(PDO::FETCH_ASSOC);
        
        if ($login) {
            $response['login_time'] = date('h:i A', strtotime($login['login_time']));
        }
        
        // Get all activities
        $stmt_activities = $db->prepare("
            SELECT 
                action_time,
                action,
                details
            FROM activity_logs
            WHERE payroll_id = ? AND DATE(action_time) = ?
            ORDER BY action_time ASC
        ");
        $stmt_activities->execute([$payroll_id, $selected_date]);
        $activities = $stmt_activities->fetchAll(PDO::FETCH_ASSOC);
        
        $response['total_activities'] = count($activities);
        
        foreach ($activities as $activity) {
            $response['activities'][] = [
                'time' => date('h:i A', strtotime($activity['action_time'])),
                'type' => $activity['action'],
                'description' => $activity['details'] ?? $activity['action']
            ];
        }
    }
    
    echo json_encode($response);
    exit;
}


        // ========== RECENT INTERRUPTIONS ==========
        // CORRECTED: Only join to tables/columns that exist
        $stmt_interruptions = $db->prepare("
            SELECT 
                d.entry_hour,
                d.fault_code,
                f.fdr11kv_name as feeder_name,
                iss.iss_name as location_name,
                '11kV' as voltage_level
            FROM fdr11kv_data d
            INNER JOIN fdr11kv f ON f.fdr11kv_code = d.Fdr11kv_code
            INNER JOIN iss_locations iss ON iss.iss_code = f.iss_code
            WHERE d.entry_date = ? 
              AND d.fault_code IS NOT NULL 
              AND d.fault_code != ''
            
            UNION ALL
            
            SELECT 
                d.entry_hour,
                d.fault_code,
                f.fdr33kv_name as feeder_name,
                ts.station_name as location_name,
                '33kV' as voltage_level
            FROM fdr33kv_data d
            INNER JOIN fdr33kv f ON f.fdr33kv_code = d.Fdr33kv_code
            INNER JOIN transmission_stations ts ON ts.ts_code = f.ts_code
            WHERE d.entry_date = ? 
              AND d.fault_code IS NOT NULL 
              AND d.fault_code != ''
            
            ORDER BY entry_hour DESC
            LIMIT 10
        ");
        $stmt_interruptions->execute([$selected_date, $selected_date]);
        $recent_interruptions = $stmt_interruptions->fetchAll(PDO::FETCH_ASSOC);

        // ========== TOP PERFORMING FEEDERS (By Load) ==========
        // CORRECTED: Only use columns that exist
        $stmt_top_feeders = $db->prepare("
            SELECT 
                f.fdr11kv_name as feeder_name,
                iss.iss_name as location,
                SUM(d.load_read) as total_load,
                '11kV' as voltage_level
            FROM fdr11kv_data d
            INNER JOIN fdr11kv f ON f.fdr11kv_code = d.Fdr11kv_code
            INNER JOIN iss_locations iss ON iss.iss_code = f.iss_code
            WHERE d.entry_date = ?
            GROUP BY f.fdr11kv_code, f.fdr11kv_name, iss.iss_name
            
            UNION ALL
            
            SELECT 
                f.fdr33kv_name as feeder_name,
                ts.station_name as location,
                SUM(d.load_read) as total_load,
                '33kV' as voltage_level
            FROM fdr33kv_data d
            INNER JOIN fdr33kv f ON f.fdr33kv_code = d.Fdr33kv_code
            INNER JOIN transmission_stations ts ON ts.ts_code = f.ts_code
            WHERE d.entry_date = ?
            GROUP BY f.fdr33kv_code, f.fdr33kv_name, ts.station_name
            
            ORDER BY total_load DESC
            LIMIT 5
        ");
        $stmt_top_feeders->execute([$selected_date, $selected_date]);
        $top_feeders = $stmt_top_feeders->fetchAll(PDO::FETCH_ASSOC);

        // ========== AREA OFFICE BREAKDOWN ==========
        // Uses area_offices table (not regions)
        $stmt_area_offices = $db->prepare("
            SELECT 
                ao.ao_name,
                COUNT(DISTINCT f11.fdr11kv_code) as total_feeders,
                COALESCE(SUM(d11.load_read), 0) as total_load
            FROM area_offices ao
            LEFT JOIN fdr11kv f11 ON f11.ao_code = ao.ao_id
            LEFT JOIN fdr11kv_data d11 ON d11.Fdr11kv_code = f11.fdr11kv_code AND d11.entry_date = ?
            GROUP BY ao.ao_id, ao.ao_name
            HAVING total_load > 0
            ORDER BY total_load DESC
        ");
        $stmt_area_offices->execute([$selected_date]);
        $regional_breakdown = $stmt_area_offices->fetchAll(PDO::FETCH_ASSOC);

        // Load the main dashboard view
        require __DIR__ . '/../views/lead_dispatch/dashboard.php';
        break;
}
