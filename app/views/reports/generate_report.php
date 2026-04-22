<?php
/**
 * Report Generation AJAX Handler
 * Path: /public/ajax/generate_report.php
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include necessary files
require_once __DIR__ . '/../../app/config/Database.php';
require_once __DIR__ . '/../../app/models/Auth.php';

// Set JSON header
header('Content-Type: application/json');

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
            $data = generateFeederPerformanceReport($db, $date_from, $date_to, $feeder_filter, $iss_filter);
            break;

        case 'fault':
            $data = generateFaultAnalysisReport($db, $date_from, $date_to, $feeder_filter, $iss_filter);
            break;

        case 'completion':
            $data = generateCompletionReport($db, $date_from, $date_to, $feeder_filter, $iss_filter);
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

} catch (Exception $e) {
    error_log('Report Generation Error: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while generating the report'
    ]);
}

/**
 * Generate Load Report (Daily/Weekly/Monthly)
 */
function generateLoadReport($db, $date_from, $date_to, $feeder_filter, $iss_filter, $user) {
    $query = "
        SELECT 
            d.entry_date,
            d.entry_hour,
            d.load_reading,
            d.fault_code,
            d.fault_remark,
            f.fdr11kv_code as feeder_code,
            f.fdr11kv_name as feeder_name
        FROM fdr11kv_data d
        INNER JOIN fdr11kv f ON d.feeder_code = f.fdr11kv_code
        WHERE d.entry_date BETWEEN ? AND ?
    ";

    $params = [$date_from, $date_to];

    // Apply feeder filter
    if ($feeder_filter !== 'ALL') {
        $query .= " AND d.feeder_code = ?";
        $params[] = $feeder_filter;
    }

    // Apply ISS filter
    if (!empty($iss_filter)) {
        $query .= " AND f.iss_code = ?";
        $params[] = $iss_filter;
    }

    // Apply user role restrictions
    if ($user['role'] === 'UL1') {
        $query .= " AND f.iss_code = ?";
        $params[] = $user['iss_code'];
    } elseif ($user['role'] === 'UL2') {
        // UL2 sees only their assigned 33kV feeders - add 33kV query
        $query33kv = "
            SELECT 
                d.entry_date,
                d.entry_hour,
                d.load_reading,
                d.fault_code,
                d.fault_remark,
                f.fdr33kv_code as feeder_code,
                f.fdr33kv_name as feeder_name
            FROM fdr33kv_data d
            INNER JOIN fdr33kv f ON d.feeder_code = f.fdr33kv_code
            WHERE d.entry_date BETWEEN ? AND ?
            AND f.fdr33kv_code = ?
        ";
        $params33kv = [$date_from, $date_to, $user['assigned_33kv_code']];

        if ($feeder_filter !== 'ALL') {
            $query33kv .= " AND d.feeder_code = ?";
            $params33kv[] = $feeder_filter;
        }

        $query33kv .= " ORDER BY d.entry_date DESC, d.entry_hour DESC";
        $stmt = $db->prepare($query33kv);
        $stmt->execute($params33kv);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $query .= " ORDER BY d.entry_date DESC, d.entry_hour DESC LIMIT 1000";

    $stmt = $db->prepare($query);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Generate Feeder Performance Report
 */
function generateFeederPerformanceReport($db, $date_from, $date_to, $feeder_filter, $iss_filter) {
    $query = "
        SELECT 
            f.fdr11kv_code as feeder_code,
            f.fdr11kv_name as feeder_name,
            AVG(d.load_reading) as avg_load,
            MAX(d.load_reading) as max_load,
            MIN(d.load_reading) as min_load,
            COUNT(*) as total_entries,
            SUM(CASE WHEN d.fault_code IS NOT NULL AND d.fault_code != '' THEN 1 ELSE 0 END) as fault_count
        FROM fdr11kv_data d
        INNER JOIN fdr11kv f ON d.feeder_code = f.fdr11kv_code
        WHERE d.entry_date BETWEEN ? AND ?
    ";

    $params = [$date_from, $date_to];

    if ($feeder_filter !== 'ALL') {
        $query .= " AND d.feeder_code = ?";
        $params[] = $feeder_filter;
    }

    if (!empty($iss_filter)) {
        $query .= " AND f.iss_code = ?";
        $params[] = $iss_filter;
    }

    $query .= " GROUP BY f.fdr11kv_code, f.fdr11kv_name ORDER BY avg_load DESC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Generate Fault Analysis Report
 */
function generateFaultAnalysisReport($db, $date_from, $date_to, $feeder_filter, $iss_filter) {
    $query = "
        SELECT 
            d.entry_date,
            d.entry_hour,
            d.fault_code,
            d.fault_remark,
            d.load_reading,
            f.fdr11kv_code as feeder_code,
            f.fdr11kv_name as feeder_name,
            CONCAT(d.entry_hour, ' hours') as duration
        FROM fdr11kv_data d
        INNER JOIN fdr11kv f ON d.feeder_code = f.fdr11kv_code
        WHERE d.entry_date BETWEEN ? AND ?
        AND (d.fault_code IS NOT NULL AND d.fault_code != '')
    ";

    $params = [$date_from, $date_to];

    if ($feeder_filter !== 'ALL') {
        $query .= " AND d.feeder_code = ?";
        $params[] = $feeder_filter;
    }

    if (!empty($iss_filter)) {
        $query .= " AND f.iss_code = ?";
        $params[] = $iss_filter;
    }

    $query .= " ORDER BY d.entry_date DESC, d.entry_hour DESC LIMIT 500";

    $stmt = $db->prepare($query);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Generate Data Completion Report
 */
function generateCompletionReport($db, $date_from, $date_to, $feeder_filter, $iss_filter) {
    $query = "
        SELECT 
            d.entry_date,
            f.fdr11kv_code as feeder_code,
            f.fdr11kv_name as feeder_name,
            24 as expected_entries,
            COUNT(DISTINCT d.entry_hour) as actual_entries
        FROM fdr11kv f
        LEFT JOIN fdr11kv_data d ON f.fdr11kv_code = d.feeder_code 
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

    $query .= " GROUP BY d.entry_date, f.fdr11kv_code, f.fdr11kv_name 
                HAVING d.entry_date IS NOT NULL
                ORDER BY d.entry_date DESC, actual_entries ASC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
