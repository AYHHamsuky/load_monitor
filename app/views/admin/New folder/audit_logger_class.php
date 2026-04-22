<?php
// app/core/AuditLogger.php

/**
 * Audit Logger Class
 * Comprehensive audit trail system for all user actions
 * 
 * @version 1.0
 * @author LMS Development Team
 */

class AuditLogger {
    private static $db;

    public static function init() {
        self::$db = Database::getInstance();
    }

    /**
     * Log an action to audit trail
     * 
     * @param string $actionType - Type of action (e.g., 'USER_CREATED', 'LOGIN_SUCCESS')
     * @param string $actionCategory - Category: AUTH, USER_MGMT, DATA_ENTRY, CORRECTION, COMPLAINT, SYSTEM, SECURITY
     * @param string|null $module - Module name (e.g., 'user_management', 'authentication')
     * @param string|null $recordId - ID of affected record
     * @param array|null $oldValue - Previous value (for updates)
     * @param array|null $newValue - New value (for updates)
     * @param array $details - Additional details as key-value pairs
     * @param string $severity - LOW, MEDIUM, HIGH, CRITICAL
     * @return bool Success status
     */
    public static function log(
        string $actionType,
        string $actionCategory,
        ?string $module = null,
        ?string $recordId = null,
        ?array $oldValue = null,
        ?array $newValue = null,
        array $details = [],
        string $severity = 'LOW'
    ): bool {
        try {
            $user = Auth::user();
            $userId = $user ? $user['payroll_id'] : 'SYSTEM';

            $stmt = self::$db->prepare("
                INSERT INTO audit_logs (
                    user_id, action_type, action_category, module, record_id,
                    old_value, new_value, details, ip_address, user_agent,
                    severity, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            return $stmt->execute([
                $userId,
                $actionType,
                $actionCategory,
                $module,
                $recordId,
                $oldValue ? json_encode($oldValue) : null,
                $newValue ? json_encode($newValue) : null,
                json_encode($details),
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                $severity
            ]);
        } catch (Exception $e) {
            error_log("Audit logging failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log authentication events (login, logout, failed attempts)
     */
    public static function logAuth(string $action, string $userId, bool $success, array $details = []): bool {
        return self::log(
            $action,
            'AUTH',
            'authentication',
            $userId,
            null,
            null,
            array_merge($details, ['success' => $success]),
            $success ? 'LOW' : 'MEDIUM'
        );
    }

    /**
     * Log user management actions (create, edit, delete, activate)
     */
    public static function logUserManagement(
        string $action,
        string $targetUserId,
        ?array $oldData = null,
        ?array $newData = null,
        array $details = []
    ): bool {
        return self::log(
            $action,
            'USER_MGMT',
            'user_management',
            $targetUserId,
            $oldData,
            $newData,
            $details,
            in_array($action, ['USER_DELETED', 'USER_ROLE_CHANGED']) ? 'HIGH' : 'MEDIUM'
        );
    }

    /**
     * Log data entry actions (11kV, 33kV readings)
     */
    public static function logDataEntry(
        string $action,
        string $feederCode,
        string $entryDate,
        int $entryHour,
        array $data = []
    ): bool {
        return self::log(
            $action,
            'DATA_ENTRY',
            'load_monitoring',
            "{$feederCode}_{$entryDate}_H{$entryHour}",
            null,
            $data,
            [],
            'LOW'
        );
    }

    /**
     * Log correction workflow actions
     */
    public static function logCorrection(
        string $action,
        int $correctionId,
        string $status,
        array $details = []
    ): bool {
        return self::log(
            $action,
            'CORRECTION',
            'correction_workflow',
            (string)$correctionId,
            null,
            ['status' => $status],
            $details,
            'MEDIUM'
        );
    }

    /**
     * Log security events (separate from security_events table - this is for audit trail)
     */
    public static function logSecurityAction(
        string $action,
        array $details = []
    ): bool {
        return self::log(
            $action,
            'SECURITY',
            'security_management',
            null,
            null,
            null,
            $details,
            'HIGH'
        );
    }

    /**
     * Log system actions (configuration changes, maintenance)
     */
    public static function logSystem(
        string $action,
        array $details = [],
        string $severity = 'MEDIUM'
    ): bool {
        return self::log(
            $action,
            'SYSTEM',
            'system_management',
            null,
            null,
            null,
            $details,
            $severity
        );
    }

    /**
     * Get recent audit logs
     * 
     * @param int $limit - Number of records to fetch
     * @param array $filters - Optional filters (user_id, action_category, severity, date_from, date_to)
     * @return array Audit logs
     */
    public static function getRecentLogs(int $limit = 50, array $filters = []): array {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['user_id'])) {
                $where[] = "a.user_id = ?";
                $params[] = $filters['user_id'];
            }

            if (!empty($filters['action_category'])) {
                $where[] = "a.action_category = ?";
                $params[] = $filters['action_category'];
            }

            if (!empty($filters['severity'])) {
                $where[] = "a.severity = ?";
                $params[] = $filters['severity'];
            }

            if (!empty($filters['date_from'])) {
                $where[] = "a.created_at >= ?";
                $params[] = $filters['date_from'] . ' 00:00:00';
            }

            if (!empty($filters['date_to'])) {
                $where[] = "a.created_at <= ?";
                $params[] = $filters['date_to'] . ' 23:59:59';
            }

            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $stmt = self::$db->prepare("
                SELECT 
                    a.*,
                    s.staff_name,
                    s.role
                FROM audit_logs a
                LEFT JOIN staff_details s ON a.user_id = s.payroll_id
                {$whereClause}
                ORDER BY a.created_at DESC
                LIMIT ?
            ");

            $params[] = $limit;
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Failed to fetch audit logs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get audit statistics
     */
    public static function getStatistics(string $period = 'today'): array {
        try {
            $dateCondition = match($period) {
                'today' => "DATE(created_at) = CURDATE()",
                'week' => "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
                'month' => "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
                default => "DATE(created_at) = CURDATE()"
            };

            $stmt = self::$db->query("
                SELECT 
                    COUNT(*) as total_actions,
                    COUNT(DISTINCT user_id) as active_users,
                    SUM(CASE WHEN severity = 'CRITICAL' THEN 1 ELSE 0 END) as critical_events,
                    SUM(CASE WHEN severity = 'HIGH' THEN 1 ELSE 0 END) as high_events,
                    SUM(CASE WHEN severity = 'MEDIUM' THEN 1 ELSE 0 END) as medium_events,
                    SUM(CASE WHEN severity = 'LOW' THEN 1 ELSE 0 END) as low_events,
                    SUM(CASE WHEN action_category = 'AUTH' THEN 1 ELSE 0 END) as auth_events,
                    SUM(CASE WHEN action_category = 'USER_MGMT' THEN 1 ELSE 0 END) as user_mgmt_events,
                    SUM(CASE WHEN action_category = 'DATA_ENTRY' THEN 1 ELSE 0 END) as data_entry_events,
                    SUM(CASE WHEN action_category = 'SECURITY' THEN 1 ELSE 0 END) as security_events
                FROM audit_logs
                WHERE {$dateCondition}
            ");

            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("Failed to get audit statistics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user activity timeline
     */
    public static function getUserActivity(string $userId, int $limit = 100): array {
        try {
            $stmt = self::$db->prepare("
                SELECT *
                FROM audit_logs
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT ?
            ");

            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Failed to fetch user activity: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Clean old audit logs (keep last N days)
     * Should be run periodically by cron job
     */
    public static function cleanOldLogs(int $keepDays = 365): int {
        try {
            $stmt = self::$db->prepare("
                DELETE FROM audit_logs
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
                AND severity NOT IN ('CRITICAL', 'HIGH')
            ");

            $stmt->execute([$keepDays]);
            return $stmt->rowCount();
        } catch (Exception $e) {
            error_log("Failed to clean old logs: " . $e->getMessage());
            return 0;
        }
    }
}

// Initialize on load
AuditLogger::init();