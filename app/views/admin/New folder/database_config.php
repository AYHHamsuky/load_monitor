<?php
// app/config/database.php

/**
 * Database Configuration
 * Update these values for your environment
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'load_monitor');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Database options
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
]);

// Application settings
define('APP_NAME', 'Load Monitoring System');
define('APP_VERSION', '2.0.0');
define('APP_ENV', 'development'); // 'development' or 'production'

// Security settings
define('SESSION_LIFETIME', 28800); // 8 hours in seconds
define('MAX_LOGIN_ATTEMPTS', 5);
define('ACCOUNT_LOCK_DURATION', 30); // minutes

// File paths
define('BASE_PATH', dirname(__DIR__, 2));
define('APP_PATH', BASE_PATH . '/app');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('LOGS_PATH', BASE_PATH . '/logs');

// Create logs directory if it doesn't exist
if (!file_exists(LOGS_PATH)) {
    mkdir(LOGS_PATH, 0755, true);
}