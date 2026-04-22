<?php
/**
 * Lead Dispatch Controller
 * Path: /app/controllers/LeadDispatchController.php
 * Role: UL8
 */

// Ensure user has UL8 role
Guard::requireRole(['UL8']);

$user = Auth::user();
$db = Database::connect();

// Get selected date (default to today)
$selected_date = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_date)) {
    $selected_date = date('Y-m-d');
}

// Get action (determines which view to load)
$action = $_GET['action'] ?? '11kv'; // Default to 11kV matrix

// Route to appropriate view
switch ($action) {
    case '11kv':
    case '':
        // 11kV Hourly Load Matrix
        require __DIR__ . '/../views/lead_dispatch/11kv_matrix_view.php';
        break;
        
    case '33kv':
        // 33kV Hourly Load Matrix
        require __DIR__ . '/../views/lead_dispatch/33kv_matrix_view.php';
        break;
        
    case 'staff':
        // Staff on Duty
        require __DIR__ . '/../views/lead_dispatch/staff_on_duty_view.php';
        break;
        
    case 'interruptions':
        // Interruptions Monitor
        require __DIR__ . '/../views/lead_dispatch/interruptions_view.php';
        break;
        
    case 'statistics':
        // Load Statistics by Hierarchy
        require __DIR__ . '/../views/lead_dispatch/load_statistics_view.php';
        break;
        
    default:
        // Default to 11kV matrix
        require __DIR__ . '/../views/lead_dispatch/11kv_matrix_view.php';
        break;
}
