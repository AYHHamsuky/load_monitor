<?php
// public/ajax/system_control.php

/**
 * System Control AJAX Handler
 * Enable/disable logins, maintenance mode, etc.
 * 
 * @version 1.0
 */

require_once __DIR__ . '/../../app/bootstrap.php';

header('Content-Type: application/json');

// Only UL7 can control system
if (!Guard::hasRole('UL7')) {
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Only Super Admin can control system settings.'
    ]);
    exit;
}

$action = $_POST['action'] ?? '';
$db = Database::getInstance();

try {
    switch ($action) {
        case 'toggle_logins':
            // Enable/disable all logins
            $enable = ($_POST['enable'] ?? 'true') === 'true';
            
            $stmt = $db->prepare("
                UPDATE system_config
                SET config_value = ?,
                    updated_by = ?,
                    updated_at = NOW()
                WHERE config_key = 'login_enabled'
            ");

            $user = Auth::user();
            $stmt->execute([
                $enable ? 'true' : 'false',
                $user['payroll_id']
            ]);

            AuditLogger::logSystem(
                $enable ? 'LOGINS_ENABLED' : 'LOGINS_DISABLED',
                ['action' => $enable ? 'enabled' : 'disabled'],
                'CRITICAL'
            );

            echo json_encode([
                'success' => true,
                'message' => 'System logins have been ' . ($enable ? 'enabled' : 'disabled') . '.',
                'status' => $enable ? 'enabled' : 'disabled'
            ]);
            break;

        case 'toggle_maintenance':
            // Enable/disable maintenance mode
            $enable = ($_POST['enable'] ?? 'false') === 'true';
            
            $stmt = $db->prepare("
                UPDATE system_config
                SET config_value = ?,
                    updated_by = ?,
                    updated_at = NOW()
                WHERE config_key = 'maintenance_mode'
            ");

            $user = Auth::user();
            $stmt->execute([
                $enable ? 'true' : 'false',
                $user['payroll_id']
            ]);

            AuditLogger::logSystem(
                $enable ? 'MAINTENANCE_MODE_ENABLED' : 'MAINTENANCE_MODE_DISABLED',
                ['action' => $enable ? 'enabled' : 'disabled'],
                'HIGH'
            );

            echo json_encode([
                'success' => true,
                'message' => 'Maintenance mode has been ' . ($enable ? 'enabled' : 'disabled') . '.',
                'status' => $enable ? 'enabled' : 'disabled'
            ]);
            break;

        case 'get_system_status':
            // Get current system status
            $stmt = $db->query("
                SELECT config_key, config_value
                FROM system_config
                WHERE config_key IN ('login_enabled', 'maintenance_mode')
            ");

            $config = [];
            while ($row = $stmt->fetch()) {
                $config[$row['config_key']] = $row['config_value'];
            }

            echo json_encode([
                'success' => true,
                'config' => [
                    'logins_enabled' => ($config['login_enabled'] ?? 'true') === 'true',
                    'maintenance_mode' => ($config['maintenance_mode'] ?? 'false') === 'true'
                ]
            ]);
            break;

        case 'clean_expired_sessions':
            // Clean up expired sessions
            $count = SessionManager::cleanExpiredSessions();

            AuditLogger::logSystem(
                'SESSIONS_CLEANED',
                ['sessions_removed' => $count],
                'LOW'
            );

            echo json_encode([
                'success' => true,
                'message' => "Cleaned {$count} expired session(s).",
                'sessions_cleaned' => $count
            ]);
            break;

        case 'clean_old_logs':
            // Clean old audit logs (keep last 365 days)
            $auditCount = AuditLogger::cleanOldLogs(365);
            $securityCount = SecurityMonitor::cleanOldEvents(90);

            AuditLogger::logSystem(
                'LOGS_CLEANED',
                [
                    'audit_logs_removed' => $auditCount,
                    'security_events_removed' => $securityCount
                ],
                'MEDIUM'
            );

            echo json_encode([
                'success' => true,
                'message' => "Cleaned {$auditCount} audit log(s) and {$securityCount} security event(s).",
                'audit_logs_cleaned' => $auditCount,
                'security_events_cleaned' => $securityCount
            ]);
            break;

        case 'optimize_database':
            // Optimize all tables
            $stmt = $db->query("
                SELECT table_name
                FROM information_schema.TABLES
                WHERE table_schema = DATABASE()
            ");

            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $optimized = 0;

            foreach ($tables as $table) {
                try {
                    $db->query("OPTIMIZE TABLE `{$table}`");
                    $optimized++;
                } catch (Exception $e) {
                    // Continue with other tables
                    error_log("Failed to optimize {$table}: " . $e->getMessage());
                }
            }

            AuditLogger::logSystem(
                'DATABASE_OPTIMIZED',
                ['tables_optimized' => $optimized],
                'MEDIUM'
            );

            echo json_encode([
                'success' => true,
                'message' => "Optimized {$optimized} database table(s).",
                'tables_optimized' => $optimized
            ]);
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Operation failed: ' . $e->getMessage()
    ]);
}