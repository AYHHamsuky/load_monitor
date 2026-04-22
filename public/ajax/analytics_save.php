<?php
// public/ajax/analytics_save.php

require_once __DIR__ . '/../../app/bootstrap.php';

header('Content-Type: application/json');

// Only UL3 can create/save reports
if (!Guard::hasRole('UL3')) {
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Only Analysts (UL3) can create reports.'
    ]);
    exit;
}

$user = Auth::user();
$db = Database::getInstance();

try {
    $report_name = trim($_POST['report_name'] ?? '');
    $report_type = $_POST['report_type'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $parameters = $_POST['parameters'] ?? '{}';
    $is_public = isset($_POST['is_public']) && $_POST['is_public'] === '1' ? 1 : 0;
    
    // Validation
    if (empty($report_name)) {
        throw new Exception('Report name is required');
    }
    
    if (empty($report_type)) {
        throw new Exception('Report type is required');
    }
    
    $valid_types = [
        'load_summary',
        'interruption_analysis',
        'data_quality',
        'peak_demand',
        'feeder_performance',
        'complaint_trends'
    ];
    
    if (!in_array($report_type, $valid_types)) {
        throw new Exception('Invalid report type');
    }
    
    // Validate JSON parameters
    $params_array = json_decode($parameters, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid parameters format');
    }
    
    // Insert report
    $stmt = $db->prepare("
        INSERT INTO analytics_reports (
            report_name,
            report_type,
            description,
            parameters,
            created_by,
            is_public,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $report_name,
        $report_type,
        $description,
        $parameters,
        $user['payroll_id'],
        $is_public
    ]);
    
    $report_id = $db->lastInsertId();
    
    echo json_encode([
        'success' => true,
        'message' => 'Report saved successfully!',
        'report_id' => $report_id
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
