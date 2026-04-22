<?php
// public/ajax/run_integrity_check.php

/**
 * File Integrity Check AJAX Handler
 * Runs integrity checks on critical system files
 * 
 * @version 1.0
 */

require_once __DIR__ . '/../../app/bootstrap.php';

header('Content-Type: application/json');

// Only UL7 can run integrity checks
if (!Guard::hasRole('UL7')) {
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Only Super Admin can run integrity checks.'
    ]);
    exit;
}

try {
    // Define critical files to check
    $files_to_check = [
        // Core files
        __DIR__ . '/../../app/core/Auth.php',
        __DIR__ . '/../../app/core/Guard.php',
        __DIR__ . '/../../app/core/Database.php',
        __DIR__ . '/../../app/core/AuditLogger.php',
        __DIR__ . '/../../app/core/SecurityMonitor.php',
        __DIR__ . '/../../app/core/SessionManager.php',
        
        // Bootstrap
        __DIR__ . '/../../public/index.php',
        __DIR__ . '/../../app/bootstrap.php',
        
        // Critical AJAX handlers
        __DIR__ . '/../../public/ajax/11kv_save.php',
        __DIR__ . '/../../public/ajax/33kv_save.php',
        __DIR__ . '/../../public/ajax/correction_request.php',
        __DIR__ . '/../../public/ajax/analyst_action.php',
        __DIR__ . '/../../public/ajax/manager_action.php',
        
        // Login
        __DIR__ . '/../../public/login.php'
    ];

    $issues = [];
    $intact = [];
    $missing = [];
    $checked = 0;

    foreach ($files_to_check as $file) {
        $checked++;
        $fileName = basename($file);

        if (!file_exists($file)) {
            $missing[] = $fileName;
            continue;
        }

        $isIntact = SecurityMonitor::checkFileIntegrity($file);

        if ($isIntact) {
            $intact[] = $fileName;
        } else {
            $issues[] = $fileName;
        }
    }

    // Prepare response
    $totalIssues = count($issues) + count($missing);
    
    if ($totalIssues > 0) {
        $message = "Integrity check completed. Found {$totalIssues} issue(s).";
        
        if (count($issues) > 0) {
            $message .= "\nModified files: " . implode(', ', $issues);
        }
        
        if (count($missing) > 0) {
            $message .= "\nMissing files: " . implode(', ', $missing);
        }
    } else {
        $message = "All {$checked} critical files are intact and verified.";
    }

    echo json_encode([
        'success' => true,
        'message' => $message,
        'summary' => [
            'total_checked' => $checked,
            'intact' => count($intact),
            'modified' => count($issues),
            'missing' => count($missing)
        ],
        'details' => [
            'intact_files' => $intact,
            'modified_files' => $issues,
            'missing_files' => $missing
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Integrity check failed: ' . $e->getMessage()
    ]);
}