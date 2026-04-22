<?php
// app/controllers/SuperAdminDashboardController.php

/**
 * Super Admin Dashboard Controller (UL7)
 * Main security and system management dashboard
 * 
 * @version 1.0
 * @author LMS Development Team
 */

// Strict access control - Only UL7
Guard::requireSuperAdmin();

$user = Auth::user();
$db = Database::getInstance();
$today = date('Y-m-d');

// =====================================================
// SECURITY METRICS (Last 24 Hours)
// =====================================================
$securityStats = SecurityMonitor::getSecurityStats('today');

// Failed Login Attempts (Last 24h)
$stmt = $db->query("
    SELECT COUNT(*) as count
    FROM security_events
    WHERE event_type = 'FAILED_LOGIN'
    AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
");
$failed_logins = $stmt->fetch()['count'];

// Blacklisted IPs
$stmt = $db->query("
    SELECT COUNT(*) as count
    FROM ip_blacklist
    WHERE is_active = 1
");
$blacklisted_ips = $stmt->fetch()['count'];

// Active Sessions
$active_sessions = SessionManager::getActiveSessions();

// =====================================================
// SYSTEM HEALTH
// =====================================================
$system_health = SystemHealth::getLatestHealth();

// File Integrity Issues
$stmt = $db->query("
    SELECT COUNT(*) as count
    FROM file_integrity
    WHERE status != 'INTACT'
");
$integrity_issues = $stmt->fetch()['count'];

// =====================================================
// USER DISTRIBUTION
// =====================================================
$stmt = $db->query("
    SELECT
        role,
        COUNT(*) as total,
        SUM(CASE WHEN is_active = 'Yes' THEN 1 ELSE 0 END) as active
    FROM staff_details
    GROUP BY role
    ORDER BY role
");
$user_distribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =====================================================
// RECENT AUDIT LOGS (Last 50)
// =====================================================
$recent_audits = AuditLogger::getRecentLogs(50);

// =====================================================
// RECENT SECURITY EVENTS (Last 20)
// =====================================================
$recent_security = SecurityMonitor::getRecentEvents(20);

// =====================================================
// ACTIVE USER SESSIONS
// =====================================================
$active_user_sessions = SessionManager::getAllActiveSessions();

// =====================================================
// CRITICAL ALERTS
// =====================================================
$critical_alerts = SystemHealth::getCriticalAlerts();

// =====================================================
// THREAT LEVEL CALCULATION
// =====================================================
$threat_score = ($securityStats['critical'] * 10) +
                ($securityStats['high'] * 5) +
                ($securityStats['medium'] * 2);

if ($threat_score > 50) {
    $threat_level = 'CRITICAL';
    $threat_color = '#dc3545';
} elseif ($threat_score > 20) {
    $threat_level = 'HIGH';
    $threat_color = '#fd7e14';
} elseif ($threat_score > 10) {
    $threat_level = 'MEDIUM';
    $threat_color = '#ffc107';
} else {
    $threat_level = 'LOW';
    $threat_color = '#28a745';
}

// =====================================================
// PENDING USER MANAGEMENT TASKS
// =====================================================
$stmt = $db->query("
    SELECT COUNT(*) as count
    FROM staff_details
    WHERE account_locked_until > NOW()
");
$locked_accounts = $stmt->fetch()['count'];

// =====================================================
// DATABASE STATISTICS
// =====================================================
$stmt = $db->query("
    SELECT 
        table_name,
        table_rows,
        ROUND(((data_length + index_length) / 1024 / 1024), 2) as size_mb
    FROM information_schema.TABLES
    WHERE table_schema = DATABASE()
    ORDER BY (data_length + index_length) DESC
    LIMIT 10
");
$db_tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =====================================================
// AUDIT LOG STATISTICS
// =====================================================
$audit_stats = AuditLogger::getStatistics('today');

// =====================================================
// RECENT CORRECTIONS (Pending Approval)
// =====================================================
$stmt = $db->query("
    SELECT 
        c.*,
        s.staff_name as requester_name,
        f.feeder_name
    FROM load_corrections c
    LEFT JOIN staff_details s ON c.requested_by = s.payroll_id
    LEFT JOIN fdr11kv f ON c.feeder_code = f.feeder_code
    WHERE c.status IN ('PENDING', 'ANALYST_APPROVED')
    ORDER BY c.requested_at DESC
    LIMIT 10
");
$pending_corrections = $stmt->fetchAll(PDO::FETCH_ASSOC);

// =====================================================
// SYSTEM CONFIGURATION STATUS
// =====================================================
$stmt = $db->query("
    SELECT config_key, config_value
    FROM system_config
    WHERE config_key IN ('login_enabled', 'maintenance_mode')
");
$system_config = [];
while ($row = $stmt->fetch()) {
    $system_config[$row['config_key']] = $row['config_value'];
}

$logins_enabled = ($system_config['login_enabled'] ?? 'true') === 'true';
$maintenance_mode = ($system_config['maintenance_mode'] ?? 'false') === 'true';

// Load the view
require __DIR__ . '/../views/superadmin/dashboard.php';