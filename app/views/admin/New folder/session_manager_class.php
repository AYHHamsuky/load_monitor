<?php
// app/core/SessionManager.php

/**
 * Session Manager Class
 * Advanced session management and control
 * 
 * @version 1.0
 * @author LMS Development Team
 */

class SessionManager {
    private static $db;

    public static function init() {
        self::$db = Database::getInstance();
    }

    /**
     * Create session record when user logs in
     */
    public static function create(string $userId): bool {
        try {
            $sessionId = session_id();
            
            // Clean up any existing sessions for this session ID
            self::destroy($sessionId);

            $stmt = self::$db->prepare("
                INSERT INTO active_sessions (
                    session_id, user_id, ip_address, user_agent, 
                    last_activity, created_at, expires_at
                ) VALUES (?, ?, ?, ?, NOW(), NOW(), DATE_ADD(NOW(), INTERVAL 8 HOUR))
            ");

            return $stmt->execute([
                $sessionId,
                $userId,
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            error_log("Session creation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update session activity timestamp
     */
    public static function updateActivity(): bool {
        try {
            $sessionId = session_id();
            
            $stmt = self::$db->prepare("
                UPDATE active_sessions
                SET last_activity = NOW()
                WHERE session_id = ?
            ");

            return $stmt->execute([$sessionId]);
        } catch (Exception $e) {
            error_log("Session update failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate session - check if still active and not expired
     */
    public static function validate(string $sessionId): bool {
        try {
            $stmt = self::$db->prepare("
                SELECT id FROM active_sessions
                WHERE session_id = ?
                AND expires_at > NOW()
                AND last_activity > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
            ");

            $stmt->execute([$sessionId]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Destroy specific session
     */
    public static function destroy(string $sessionId): bool {
        try {
            $stmt = self::$db->prepare("
                DELETE FROM active_sessions WHERE session_id = ?
            ");

            return $stmt->execute([$sessionId]);
        } catch (Exception $e) {
            error_log("Session destroy failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Force logout all users (Emergency use only)
     */
    public static function logoutAll(): int {
        try {
            $stmt = self::$db->query("DELETE FROM active_sessions");
            $count = $stmt->rowCount();

            // Log this critical action
            AuditLogger::logSecurityAction(
                'FORCE_LOGOUT_ALL',
                ['sessions_terminated' => $count]
            );

            return $count;
        } catch (Exception $e) {
            error_log("Logout all failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Force logout specific user (all their sessions)
     */
    public static function logoutUser(string $userId): int {
        try {
            $stmt = self::$db->prepare("
                DELETE FROM active_sessions WHERE user_id = ?
            ");

            $stmt->execute([$userId]);
            $count = $stmt->rowCount();

            // Log this action
            AuditLogger::logSecurityAction(
                'FORCE_LOGOUT_USER',
                ['user_id' => $userId, 'sessions_terminated' => $count]
            );

            return $count;
        } catch (Exception $e) {
            error_log("Logout user failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Force logout users by role
     */
    public static function logoutByRole(string $role): int {
        try {
            $stmt = self::$db->prepare("
                DELETE s FROM active_sessions s
                INNER JOIN staff_details sd ON s.user_id = sd.payroll_id
                WHERE sd.role = ?
            ");

            $stmt->execute([$role]);
            $count = $stmt->rowCount();

            AuditLogger::logSecurityAction(
                'FORCE_LOGOUT_ROLE',
                ['role' => $role, 'sessions_terminated' => $count]
            );

            return $count;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get count of active sessions
     */
    public static function getActiveSessions(): int {
        try {
            $stmt = self::$db->query("
                SELECT COUNT(*) as count
                FROM active_sessions
                WHERE last_activity > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
                AND expires_at > NOW()
            ");

            $result = $stmt->fetch();
            return (int)($result['count'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get all active user sessions with details
     */
    public static function getAllActiveSessions(): array {
        try {
            $stmt = self::$db->query("
                SELECT 
                    s.*,
                    sd.staff_name,
                    sd.role,
                    TIMESTAMPDIFF(SECOND, s.created_at, NOW()) as session_duration
                FROM active_sessions s
                LEFT JOIN staff_details sd ON s.user_id = sd.payroll_id
                WHERE s.last_activity > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
                AND s.expires_at > NOW()
                ORDER BY s.last_activity DESC
            ");

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get user sessions (for specific user)
     */
    public static function getUserSessions(string $userId): array {
        try {
            $stmt = self::$db->prepare("
                SELECT *
                FROM active_sessions
                WHERE user_id = ?
                ORDER BY last_activity DESC
            ");

            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Check for suspicious concurrent sessions
     * (same user logged in from different IPs simultaneously)
     */
    public static function detectConcurrentSessions(string $userId): array {
        try {
            $stmt = self::$db->prepare("
                SELECT 
                    ip_address,
                    COUNT(*) as session_count,
                    GROUP_CONCAT(session_id) as session_ids
                FROM active_sessions
                WHERE user_id = ?
                AND last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                GROUP BY ip_address
                HAVING COUNT(*) > 1
            ");

            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Clean expired sessions
     * Should be run periodically by cron job
     */
    public static function cleanExpiredSessions(): int {
        try {
            $stmt = self::$db->query("
                DELETE FROM active_sessions
                WHERE expires_at < NOW()
                OR last_activity < DATE_SUB(NOW(), INTERVAL 8 HOUR)
            ");

            return $stmt->rowCount();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get session statistics
     */
    public static function getSessionStats(): array {
        try {
            $stmt = self::$db->query("
                SELECT 
                    COUNT(*) as total_sessions,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT ip_address) as unique_ips,
                    AVG(TIMESTAMPDIFF(MINUTE, created_at, last_activity)) as avg_duration_minutes,
                    MAX(last_activity) as most_recent_activity
                FROM active_sessions
                WHERE last_activity > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
            ");

            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Kill specific session by session ID
     */
    public static function killSession(string $sessionId): bool {
        try {
            // Get session info before killing
            $stmt = self::$db->prepare("
                SELECT user_id FROM active_sessions WHERE session_id = ?
            ");
            $stmt->execute([$sessionId]);
            $session = $stmt->fetch();

            if ($session) {
                AuditLogger::logSecurityAction(
                    'SESSION_KILLED',
                    ['session_id' => $sessionId, 'user_id' => $session['user_id']]
                );
            }

            return self::destroy($sessionId);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if session limit exceeded for user
     */
    public static function isSessionLimitExceeded(string $userId, int $maxSessions = 3): bool {
        try {
            $stmt = self::$db->prepare("
                SELECT COUNT(*) as count
                FROM active_sessions
                WHERE user_id = ?
                AND last_activity > DATE_SUB(NOW(), INTERVAL 30 MINUTE)
            ");

            $stmt->execute([$userId]);
            $result = $stmt->fetch();

            return ($result['count'] ?? 0) >= $maxSessions;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Terminate oldest session if limit exceeded
     */
    public static function enforceSessionLimit(string $userId, int $maxSessions = 3): bool {
        try {
            if (!self::isSessionLimitExceeded($userId, $maxSessions)) {
                return true;
            }

            // Get oldest session
            $stmt = self::$db->prepare("
                SELECT session_id FROM active_sessions
                WHERE user_id = ?
                ORDER BY created_at ASC
                LIMIT 1
            ");

            $stmt->execute([$userId]);
            $session = $stmt->fetch();

            if ($session) {
                return self::killSession($session['session_id']);
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }
}

// Initialize on load
SessionManager::init();