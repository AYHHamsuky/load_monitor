<?php
// app/controllers/AdminConfigController.php

Guard::requireAdmin(); // Only UL6

$user = Auth::user();
$db = Database::connect();

// Get action
$action = $_GET['action'] ?? 'view';

$config_message = '';

switch ($action) {
    case 'backup':
        // Database backup functionality (placeholder)
        $config_message = 'Database backup feature coming soon...';
        break;
        
    case 'view':
    default:
        // View configuration
        break;
}

require __DIR__ . '/../views/admin/config.php';
