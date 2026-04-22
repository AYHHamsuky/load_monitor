<?php
// public/ajax/get_33kv_feeders.php

require_once __DIR__ . '/../../app/bootstrap.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Ensure only UL2 can access
if (!Guard::hasRole('UL2')) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Only UL2 users can access this resource.'
    ]);
    exit;
}

try {
    $ts_code = $_GET['ts_code'] ?? null;
    
    if (!$ts_code) {
        throw new Exception('Transmission Station code is required');
    }
    
    // Validate ts_code format (basic validation)
    if (!preg_match('/^[A-Z0-9_-]+$/i', $ts_code)) {
        throw new Exception('Invalid Transmission Station code format');
    }
    
    $db = Database::getInstance();
    
    // First verify the transmission station exists
    $ts_check = $db->prepare("
        SELECT ts_code, station_name 
        FROM transmission_stations 
        WHERE ts_code = ?
    ");
    $ts_check->execute([$ts_code]);
    $ts = $ts_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$ts) {
        throw new Exception('Transmission Station not found');
    }
    
    // Fetch all 33kV feeders for the selected Transmission Station
    $stmt = $db->prepare("
        SELECT 
            f.fdr33kv_code,
            f.fdr33kv_name,
            f.ts_code,
            t.station_name
        FROM fdr33kv f
        LEFT JOIN transmission_stations t ON f.ts_code = t.ts_code
        WHERE f.ts_code = ?
        ORDER BY f.fdr33kv_name ASC
    ");
    
    $stmt->execute([$ts_code]);
    $feeders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($feeders)) {
        // No feeders found, but not an error
        echo json_encode([
            'success' => true,
            'feeders' => [],
            'message' => 'No feeders found for this transmission station',
            'ts_name' => $ts['station_name']
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'feeders' => $feeders,
            'count' => count($feeders),
            'ts_name' => $ts['station_name']
        ]);
    }
    
} catch (PDOException $e) {
    // Database error
    error_log('Database error in get_33kv_feeders.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred. Please try again or contact support.',
        'error_code' => 'DB_ERROR'
    ]);
} catch (Exception $e) {
    // Application error
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => 'APP_ERROR'
    ]);
}
