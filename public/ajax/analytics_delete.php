<?php
// public/ajax/analytics_delete.php

require_once __DIR__ . '/../../app/bootstrap.php';

header('Content-Type: application/json');

// Only UL3 can delete reports
if (!Guard::hasRole('UL3')) {
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Only Analysts (UL3) can delete reports.'
    ]);
    exit;
}

$user = Auth::user();
$db = Database::getInstance();

try {
    $report_id = $_POST['report_id'] ?? '';
    
    if (empty($report_id)) {
        throw new Exception('Report ID is required');
    }
    
    // Check if report exists and belongs to this user
    $stmt = $db->prepare("SELECT created_by, report_name FROM analytics_reports WHERE id = ?");
    $stmt->execute([$report_id]);
    $report = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$report) {
        throw new Exception('Report not found');
    }
    
    // Only the creator can delete their own report
    if ($report['created_by'] !== $user['payroll_id']) {
        throw new Exception('You can only delete reports you created');
    }
    
    // Delete report
    $stmt = $db->prepare("DELETE FROM analytics_reports WHERE id = ?");
    $stmt->execute([$report_id]);
    
    echo json_encode([
        'success' => true,
        'message' => "Report '{$report['report_name']}' deleted successfully"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
