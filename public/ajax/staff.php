<?php
/**
 * AJAX Handler for Staff Activity Details
 * This code should be added to the LeadDispatchDashboardController.php
 * in the 'staff' case before the main view is loaded
 */

// Add this code at the beginning of the 'staff' case in the controller:

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

// Continue with normal view rendering...
