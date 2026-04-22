<?php
/**
 * Report Generation AJAX Handler - FULLY DEBUGGED
 * Path: /public/ajax/generate_report.php
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once __DIR__ . '/../../app/core/Database.php';
require_once __DIR__ . '/../../app/core/Auth.php';

// Set JSON header
header('Content-Type: application/json');

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);

try {
    // Verify user is authenticated
    if (!Auth::check()) {
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized access. Please log in.'
        ]);
        exit;
    }

    $user = Auth::user();

    // Validate request method
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request method'
        ]);
        exit;
    }

    // Get and validate parameters
    $report_type = $_POST['report_type'] ?? '';
    $date_from = $_POST['date_from'] ?? '';
    $date_to = $_POST['date_to'] ?? '';
    $feeder_filter = $_POST['feeder_filter'] ?? 'ALL';
    $iss_filter = $_POST['iss_filter'] ?? '';

    // Validate required fields
    if (empty($report_type) || empty($date_from) || empty($date_to)) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required parameters'
        ]);
        exit;
    }

    // Validate dates
    $date_from_obj = DateTime::createFromFormat('Y-m-d', $date_from);
    $date_to_obj = DateTime::createFromFormat('Y-m-d', $date_to);

    if (!$date_from_obj || !$date_to_obj) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid date format'
        ]);
        exit;
    }

    if ($date_from_obj > $date_to_obj) {
        echo json_encode([
            'success' => false,
            'message' => 'From date cannot be later than To date'
        ]);
        exit;
    }

    // Connect to database
    $db = Database::connect();

    // Generate report based on type
    $data = [];

    switch ($report_type) {
        case 'daily':
        case 'weekly':
        case 'monthly':
            $data = generateLoadReport($db, $date_from, $date_to, $feeder_filter, $iss_filter, $user);
            break;

        case 'feeder':
            $data = generateFeederPerformanceReport($db, $date_from, $date_to, $feeder_filter, $iss_filter, $user);
            break;

        case 'fault':
            $data = generateFaultAnalysisReport($db, $date_from, $date_to, $feeder_filter, $iss_filter, $user);
            break;

        case 'completion':
            $data = generateCompletionReport($db, $date_from, $date_to, $feeder_filter, $iss_filter, $user);
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid report type'
            ]);
            exit;
    }

    // Log report generation
    try {
        $log_stmt = $db->prepare("
            INSERT INTO activity_logs (user_id, action, description, created_at)
            VALUES (?, 'generate_report', ?, NOW())
        ");
        $log_description = "Generated {$report_type} report from {$date_from} to {$date_to}";
        $log_stmt->execute([$user['id'], $log_description]);
    } catch (PDOException $e) {
        // Log error but don't fail the request
        error_log('Failed to log report generation: ' . $e->getMessage());
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $data,
        'message' => 'Report generated successfully',
        'record_count' => count($data)
    ]);

} catch (PDOException $e) {
    error_log('Database Error in Report Generation: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'debug' => 'Check server logs for details'
    ]);
} catch (Exception $e) {
    error_log('Report Generation Error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while generating the report',
        'debug' => 'Check server logs for details'
    ]);
}

/**
 * Generate Load Report (Daily/Weekly/Monthly)
 */
function generateLoadReport($db, $date_from, $date_to, $feeder_filter, $iss_filter, $user) {
    try {
        // Apply user role restrictions FIRST
        if ($user['role'] === 'UL2') {
            // UL2 sees only their assigned 33kV feeders
            $query = "
                SELECT 
                    d.entry_date,
                    d.entry_hour,
                    d.load_read,
                    d.fault_code,
                    d.fault_remark,
                    f.fdr33kv_code as feeder_code,
                    f.fdr33kv_name as feeder_name
                FROM fdr33kv_data d
                INNER JOIN fdr33kv f ON d.fdr33kv_code = f.fdr33kv_code
                WHERE d.entry_date BETWEEN ? AND ?
                AND f.fdr33kv_code = ?
            ";
            $params = [$date_from, $date_to, $user['assigned_33kv_code']];

            if ($feeder_filter !== 'ALL') {
                $query .= " AND d.fdr33kv_code = ?";
                $params[] = $feeder_filter;
            }

            $query .= " ORDER BY d.entry_date DESC, d.entry_hour DESC LIMIT 1000";
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // For UL1, UL3, UL4, UL5, UL6, UL7 - handle 11kV feeders
        $query = "
            SELECT 
                d.entry_date,
                d.entry_hour,
                d.load_read,
                d.fault_code,
                d.fault_remark,
                f.fdr11kv_code as feeder_code,
                f.fdr11kv_name as feeder_name
            FROM fdr11kv_data d
            INNER JOIN fdr11kv f ON d.fdr11kv_code = f.fdr11kv_code
            WHERE d.entry_date BETWEEN ? AND ?
        ";

        $params = [$date_from, $date_to];

        // Apply feeder filter
        if ($feeder_filter !== 'ALL') {
            $query .= " AND d.fdr11kv_code = ?";
            $params[] = $feeder_filter;
        }

        // Apply ISS filter for higher level users
        if (!empty($iss_filter)) {
            $query .= " AND f.iss_code = ?";
            $params[] = $iss_filter;
        }

        // Apply user role restrictions for UL1
        if ($user['role'] === 'UL1') {
            $query .= " AND f.iss_code = ?";
            $params[] = $user['iss_code'];
        }

        $query .= " ORDER BY d.entry_date DESC, d.entry_hour DESC LIMIT 1000";

        $stmt = $db->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log('Error in generateLoadReport: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Generate Feeder Performance Report
 */
function generateFeederPerformanceReport($db, $date_from, $date_to, $feeder_filter, $iss_filter, $user) {
    try {
        // Handle UL2 (33kV) separately
        if ($user['role'] === 'UL2') {
            $query = "
                SELECT 
                    f.fdr33kv_code as feeder_code,
                    f.fdr33kv_name as feeder_name,
                    AVG(d.load_read) as avg_load,
                    MAX(d.load_read) as max_load,
                    MIN(d.load_read) as min_load,
                    COUNT(*) as total_entries,
                    SUM(CASE WHEN d.fault_code IS NOT NULL AND d.fault_code != '' THEN 1 ELSE 0 END) as fault_count
                FROM fdr33kv_data d
                INNER JOIN fdr33kv f ON d.fdr33kv_code = f.fdr33kv_code
                WHERE d.entry_date BETWEEN ? AND ?
                AND f.fdr33kv_code = ?
            ";
            $params = [$date_from, $date_to, $user['assigned_33kv_code']];

            if ($feeder_filter !== 'ALL') {
                $query .= " AND d.fdr33kv_code = ?";
                $params[] = $feeder_filter;
            }

            $query .= " GROUP BY f.fdr33kv_code, f.fdr33kv_name ORDER BY avg_load DESC";

            $stmt = $db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // For other users - 11kV feeders
        $query = "
            SELECT 
                f.fdr11kv_code as feeder_code,
                f.fdr11kv_name as feeder_name,
                AVG(d.load_read) as avg_load,
                MAX(d.load_read) as max_load,
                MIN(d.load_read) as min_load,
                COUNT(*) as total_entries,
                SUM(CASE WHEN d.fault_code IS NOT NULL AND d.fault_code != '' THEN 1 ELSE 0 END) as fault_count
            FROM fdr11kv_data d
            INNER JOIN fdr11kv f ON d.fdr11kv_code = f.fdr11kv_code
            WHERE d.entry_date BETWEEN ? AND ?
        ";

        $params = [$date_from, $date_to];

        if ($feeder_filter !== 'ALL') {
            $query .= " AND d.fdr11kv_code = ?";
            $params[] = $feeder_filter;
        }

        if (!empty($iss_filter)) {
            $query .= " AND f.iss_code = ?";
            $params[] = $iss_filter;
        }

        // UL1 restriction
        if ($user['role'] === 'UL1') {
            $query .= " AND f.iss_code = ?";
            $params[] = $user['iss_code'];
        }

        $query .= " GROUP BY f.fdr11kv_code, f.fdr11kv_name ORDER BY avg_load DESC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log('Error in generateFeederPerformanceReport: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Generate Fault Analysis Report
 */
function generateFaultAnalysisReport($db, $date_from, $date_to, $feeder_filter, $iss_filter, $user) {
    try {
        // Handle UL2 (33kV) separately
        if ($user['role'] === 'UL2') {
            $query = "
                SELECT 
                    d.entry_date,
                    d.entry_hour,
                    d.fault_code,
                    d.fault_remark,
                    d.load_read,
                    f.fdr33kv_code as feeder_code,
                    f.fdr33kv_name as feeder_name,
                    CONCAT(d.entry_hour, ' hours') as duration
                FROM fdr33kv_data d
                INNER JOIN fdr33kv f ON d.fdr33kv_code = f.fdr33kv_code
                WHERE d.entry_date BETWEEN ? AND ?
                AND f.fdr33kv_code = ?
                AND (d.fault_code IS NOT NULL AND d.fault_code != '')
            ";
            $params = [$date_from, $date_to, $user['assigned_33kv_code']];

            if ($feeder_filter !== 'ALL') {
                $query .= " AND d.fdr33kv_code = ?";
                $params[] = $feeder_filter;
            }

            $query .= " ORDER BY d.entry_date DESC, d.entry_hour DESC LIMIT 500";

            $stmt = $db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // For other users - 11kV feeders
        $query = "
            SELECT 
                d.entry_date,
                d.entry_hour,
                d.fault_code,
                d.fault_remark,
                d.load_read,
                f.fdr11kv_code as feeder_code,
                f.fdr11kv_name as feeder_name,
                CONCAT(d.entry_hour, ' hours') as duration
            FROM fdr11kv_data d
            INNER JOIN fdr11kv f ON d.fdr11kv_code = f.fdr11kv_code
            WHERE d.entry_date BETWEEN ? AND ?
            AND (d.fault_code IS NOT NULL AND d.fault_code != '')
        ";

        $params = [$date_from, $date_to];

        if ($feeder_filter !== 'ALL') {
            $query .= " AND d.fdr11kv_code = ?";
            $params[] = $feeder_filter;
        }

        if (!empty($iss_filter)) {
            $query .= " AND f.iss_code = ?";
            $params[] = $iss_filter;
        }

        // UL1 restriction
        if ($user['role'] === 'UL1') {
            $query .= " AND f.iss_code = ?";
            $params[] = $user['iss_code'];
        }

        $query .= " ORDER BY d.entry_date DESC, d.entry_hour DESC LIMIT 500";

        $stmt = $db->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log('Error in generateFaultAnalysisReport: ' . $e->getMessage());
        throw $e;
    }
}

/**
 * Generate Data Completion Report
 */
function generateCompletionReport($db, $date_from, $date_to, $feeder_filter, $iss_filter, $user) {
    try {
        // Handle UL2 (33kV) separately
        if ($user['role'] === 'UL2') {
            $query = "
                SELECT 
                    d.entry_date,
                    f.fdr33kv_code as feeder_code,
                    f.fdr33kv_name as feeder_name,
                    24 as expected_entries,
                    COUNT(DISTINCT d.entry_hour) as actual_entries
                FROM fdr33kv f
                LEFT JOIN fdr33kv_data d ON f.fdr33kv_code = d.fdr33kv_code
                    AND d.entry_date BETWEEN ? AND ?
                WHERE f.fdr33kv_code = ?
            ";
            $params = [$date_from, $date_to, $user['assigned_33kv_code']];

            if ($feeder_filter !== 'ALL') {
                $query .= " AND f.fdr33kv_code = ?";
                $params[] = $feeder_filter;
            }

            $query .= " GROUP BY d.entry_date, f.fdr33kv_code, f.fdr33kv_name 
                        HAVING d.entry_date IS NOT NULL
                        ORDER BY d.entry_date DESC, actual_entries ASC";

            $stmt = $db->prepare($query);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // For other users - 11kV feeders
        $query = "
            SELECT 
                d.entry_date,
                f.fdr11kv_code as feeder_code,
                f.fdr11kv_name as feeder_name,
                24 as expected_entries,
                COUNT(DISTINCT d.entry_hour) as actual_entries
            FROM fdr11kv f
            LEFT JOIN fdr11kv_data d ON f.fdr11kv_code = d.fdr11kv_code
                AND d.entry_date BETWEEN ? AND ?
            WHERE 1=1
        ";

        $params = [$date_from, $date_to];

        if ($feeder_filter !== 'ALL') {
            $query .= " AND f.fdr11kv_code = ?";
            $params[] = $feeder_filter;
        }

        if (!empty($iss_filter)) {
            $query .= " AND f.iss_code = ?";
            $params[] = $iss_filter;
        }

        // UL1 restriction
        if ($user['role'] === 'UL1') {
            $query .= " AND f.iss_code = ?";
            $params[] = $user['iss_code'];
        }

        $query .= " GROUP BY d.entry_date, f.fdr11kv_code, f.fdr11kv_name 
                    HAVING d.entry_date IS NOT NULL
                    ORDER BY d.entry_date DESC, actual_entries ASC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log('Error in generateCompletionReport: ' . $e->getMessage());
        throw $e;
    }
}
?>
