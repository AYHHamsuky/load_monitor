<?php
// app/bootstrap.php - UPDATED VERSION WITH UL7 CLASSES

/**
 * Application Bootstrap
 * Initializes all core components and security systems
 * 
 * @version 2.0 - Updated for UL7
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_samesite' => 'Strict',
        'gc_maxlifetime' => 28800, // 8 hours
        'use_strict_mode' => true
    ]);
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Hide errors in production
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Set timezone
date_default_timezone_set('Africa/Lagos');

// Load configuration
require_once __DIR__ . '/config/database.php';

// Load core classes (in order of dependency)
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Guard.php';

// 🆕 Load UL7 security classes
require_once __DIR__ . '/core/AuditLogger.php';
require_once __DIR__ . '/core/SecurityMonitor.php';
require_once __DIR__ . '/core/SessionManager.php';
require_once __DIR__ . '/core/SystemHealth.php';

// Initialize core systems
Database::init();
Auth::init();

// 🆕 Initialize UL7 security systems
AuditLogger::init();
SecurityMonitor::init();
SessionManager::init();
SystemHealth::init();

// 🆕 Security checks on every request
if (isset($_SESSION['user_id'])) {
    // Update session activity
    SessionManager::updateActivity();
    
    // Check if IP changed (potential session hijacking)
    if (isset($_SESSION['ip_address'])) {
        $currentIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if ($_SESSION['ip_address'] !== $currentIP) {
            // Log suspicious activity
            SecurityMonitor::logSecurityEvent(
                'SESSION_HIJACK',
                'HIGH',
                $_SESSION['user_id'],
                $currentIP,
                ['original_ip' => $_SESSION['ip_address']]
            );
            
            // Logout user
            Auth::logout();
            header('Location: /public/login.php?error=security');
            exit;
        }
    }
}

// 🆕 Input validation on all POST/GET requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
    $inputs = array_merge($_POST, $_GET);
    
    // Check for SQL injection attempts
    foreach ($inputs as $key => $value) {
        if (is_string($value) && SecurityMonitor::detectSQLInjection($value)) {
            SecurityMonitor::logSecurityEvent(
                'SQL_INJECTION',
                'CRITICAL',
                $_SESSION['user_id'] ?? null,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                ['field' => $key, 'value' => substr($value, 0, 100)]
            );
            
            http_response_code(403);
            die('Security violation detected. This incident has been logged.');
        }
        
        // Check for XSS attempts
        if (is_string($value) && SecurityMonitor::detectXSS($value)) {
            SecurityMonitor::logSecurityEvent(
                'XSS_ATTEMPT',
                'HIGH',
                $_SESSION['user_id'] ?? null,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                ['field' => $key, 'value' => substr($value, 0, 100)]
            );
            
            http_response_code(403);
            die('Security violation detected. This incident has been logged.');
        }
    }
}

// 🆕 Check if IP is blacklisted
$currentIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (SecurityMonitor::isBlacklisted($currentIP)) {
    http_response_code(403);
    die('Access denied. Your IP address has been blocked.');
}

// Helper function for safe output
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

// Helper function for debug logging
function debug_log($message, $data = []) {
    $logFile = __DIR__ . '/../logs/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    
    if (!empty($data)) {
        $logMessage .= ' | Data: ' . json_encode($data);
    }
    
    error_log($logMessage . PHP_EOL, 3, $logFile);
}