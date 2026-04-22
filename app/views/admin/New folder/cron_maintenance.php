<?php
// ===========================================
// FILE 1: cleanup_sessions.php
// Run every hour: 0 * * * *
// ===========================================

require_once __DIR__ . '/../app/bootstrap.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting session cleanup...\n";

try {
    $count = SessionManager::cleanExpiredSessions();
    
    echo "✅ Cleaned $count expired session(s)\n";
    
    // Log the cleanup
    AuditLogger::logSystem(
        'SESSION_CLEANUP',
        ['sessions_removed' => $count],
        'LOW'
    );
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    error_log("Session cleanup failed: " . $e->getMessage());
}

echo "[" . date('Y-m-d H:i:s') . "] Session cleanup complete\n\n";


// ===========================================
// FILE 2: health_check.php
// Run every 15 minutes: */15 * * * *
// ===========================================
?>
<?php
require_once __DIR__ . '/../app/bootstrap.php';

echo "[" . date('Y-m-d H:i:s') . "] Running system health check...\n";

try {
    $health = SystemHealth::performHealthCheck();
    
    echo "CPU Usage: " . number_format($health['cpu_usage'], 2) . "%\n";
    echo "Memory Usage: " . number_format($health['memory_usage'], 2) . "%\n";
    echo "Disk Usage: " . number_format($health['disk_usage'], 2) . "%\n";
    echo "Health Score: " . $health['health_score'] . "%\n";
    echo "Status: " . $health['status'] . "\n";
    
    // Alert if critical
    if ($health['status'] === 'CRITICAL') {
        echo "\n⚠️ CRITICAL: System health is critical!\n";
        
        // Log critical alert
        AuditLogger::logSystem(
            'SYSTEM_CRITICAL',
            [
                'health_score' => $health['health_score'],
                'cpu_usage' => $health['cpu_usage'],
                'memory_usage' => $health['memory_usage'],
                'disk_usage' => $health['disk_usage']
            ],
            'CRITICAL'
        );
        
        // TODO: Send email/SMS alert to administrators
    }
    
    echo "✅ Health check stored\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    error_log("Health check failed: " . $e->getMessage());
}

echo "[" . date('Y-m-d H:i:s') . "] Health check complete\n\n";


// ===========================================
// FILE 3: cleanup_logs.php
// Run daily at 2 AM: 0 2 * * *
// ===========================================
?>
<?php
require_once __DIR__ . '/../app/bootstrap.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting log cleanup...\n";

try {
    // Clean old audit logs (keep last 365 days)
    $auditCount = AuditLogger::cleanOldLogs(365);
    echo "✅ Cleaned $auditCount old audit log(s)\n";
    
    // Clean old security events (keep last 90 days)
    $securityCount = SecurityMonitor::cleanOldEvents(90);
    echo "✅ Cleaned $securityCount old security event(s)\n";
    
    // Clean old health records (keep last 30 days)
    $healthCount = SystemHealth::cleanOldRecords(30);
    echo "✅ Cleaned $healthCount old health record(s)\n";
    
    // Log the cleanup
    AuditLogger::logSystem(
        'LOG_CLEANUP',
        [
            'audit_logs_removed' => $auditCount,
            'security_events_removed' => $securityCount,
            'health_records_removed' => $healthCount
        ],
        'LOW'
    );
    
    echo "✅ Log cleanup complete\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    error_log("Log cleanup failed: " . $e->getMessage());
}

echo "[" . date('Y-m-d H:i:s') . "] Cleanup complete\n\n";


// ===========================================
// FILE 4: integrity_check.php
// Run daily at 3 AM: 0 3 * * *
// ===========================================
?>
<?php
require_once __DIR__ . '/../app/bootstrap.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting file integrity check...\n";

$criticalFiles = [
    __DIR__ . '/../app/core/Auth.php',
    __DIR__ . '/../app/core/Guard.php',
    __DIR__ . '/../app/core/Database.php',
    __DIR__ . '/../app/core/AuditLogger.php',
    __DIR__ . '/../app/core/SecurityMonitor.php',
    __DIR__ . '/../app/core/SessionManager.php',
    __DIR__ . '/../public/index.php',
    __DIR__ . '/../app/bootstrap.php',
    __DIR__ . '/../public/login.php'
];

$issues = [];
$intact = 0;
$modified = 0;
$missing = 0;

foreach ($criticalFiles as $file) {
    if (!file_exists($file)) {
        $missing++;
        $issues[] = basename($file) . ' - MISSING';
        continue;
    }
    
    $isIntact = SecurityMonitor::checkFileIntegrity($file);
    
    if ($isIntact) {
        $intact++;
    } else {
        $modified++;
        $issues[] = basename($file) . ' - MODIFIED';
    }
}

echo "Files Checked: " . count($criticalFiles) . "\n";
echo "✅ Intact: $intact\n";
echo "⚠️ Modified: $modified\n";
echo "❌ Missing: $missing\n";

if (!empty($issues)) {
    echo "\n⚠️ ISSUES FOUND:\n";
    foreach ($issues as $issue) {
        echo "  - $issue\n";
    }
    
    // Log critical alert
    AuditLogger::logSystem(
        'FILE_INTEGRITY_ISSUES',
        [
            'total_checked' => count($criticalFiles),
            'intact' => $intact,
            'modified' => $modified,
            'missing' => $missing,
            'issues' => $issues
        ],
        'CRITICAL'
    );
    
    // TODO: Send email/SMS alert to administrators
    
} else {
    echo "\n✅ All files intact!\n";
}

echo "[" . date('Y-m-d H:i:s') . "] Integrity check complete\n\n";


// ===========================================
// CRONTAB SETUP INSTRUCTIONS
// ===========================================
/*

To set up these cron jobs, add the following to your crontab:
(Run: crontab -e)

# Load Monitoring System - Maintenance Jobs

# Clean expired sessions (every hour)
0 * * * * /usr/bin/php /path/to/load_monitor/scripts/cleanup_sessions.php >> /path/to/load_monitor/logs/cron.log 2>&1

# System health check (every 15 minutes)
*/15 * * * * /usr/bin/php /path/to/load_monitor/scripts/health_check.php >> /path/to/load_monitor/logs/cron.log 2>&1

# Clean old logs (daily at 2 AM)
0 2 * * * /usr/bin/php /path/to/load_monitor/scripts/cleanup_logs.php >> /path/to/load_monitor/logs/cron.log 2>&1

# File integrity check (daily at 3 AM)
0 3 * * * /usr/bin/php /path/to/load_monitor/scripts/integrity_check.php >> /path/to/load_monitor/logs/cron.log 2>&1

# Database optimization (weekly on Sunday at 3 AM)
0 3 * * 0 /usr/bin/php /path/to/load_monitor/scripts/optimize_database.php >> /path/to/load_monitor/logs/cron.log 2>&1

*/