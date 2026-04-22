<?php
// app/core/SecurityMonitor.php

/**
 * Security Monitor Class
 * Real-time security threat detection and prevention
 * 
 * @version 1.0
 * @author LMS Development Team
 */

class SecurityMonitor {
    private static $db;

    public static function init() {
        self::$db = Database::getInstance();
    }

    /**
     * Check for brute force login attempts
     * Blocks IP after 5 failed attempts in 15 minutes
     */
    public static function checkBruteForce(string $ipAddress): bool {
        try {
            $stmt = self::$db->prepare("
                SELECT COUNT(*) as attempts
                FROM security_events
                WHERE event_type = 'FAILED_LOGIN'
                AND ip_address = ?
                AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)
            ");
            $stmt->execute([$ipAddress]);
            $result = $stmt->fetch();

            $isBruteForce = $result['attempts'] >= 5;

            if ($isBruteForce) {
                self::logSecurityEvent(
                    'BRUTE_FORCE',
                    'CRITICAL',
                    null,
                    $ipAddress,
                    ['attempts' => $result['attempts']]
                );
                self::autoBlacklistIP($ipAddress, 'Brute force attack detected');
            }

            return $isBruteForce;
        } catch (Exception $e) {
            error_log("Brute force check failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if IP is blacklisted
     */
    public static function isBlacklisted(string $ipAddress): bool {
        try {
            $stmt = self::$db->prepare("
                SELECT id FROM ip_blacklist
                WHERE ip_address = ?
                AND is_active = 1
                AND (expires_at IS NULL OR expires_at > NOW())
            ");
            $stmt->execute([$ipAddress]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            error_log("Blacklist check failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if IP is whitelisted
     */
    public static function isWhitelisted(string $ipAddress): bool {
        try {
            $stmt = self::$db->prepare("
                SELECT id FROM ip_whitelist
                WHERE ip_address = ?
                AND is_active = 1
                AND (expires_at IS NULL OR expires_at > NOW())
            ");
            $stmt->execute([$ipAddress]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Detect SQL injection attempts
     */
    public static function detectSQLInjection(string $input): bool {
        $patterns = [
            '/(\bunion\b.*\bselect\b)/i',
            '/(\bor\b.*=.*)/i',
            '/(\'|\";\|--|\#|\/\*)/i',
            '/(\bdrop\b|\bdelete\b|\binsert\b|\bupdate\b|\btruncate\b)/i',
            '/(\bexec\b|\bexecute\b|\bdeclare\b)/i',
            '/(\binto\b.*\boutfile\b)/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                self::logSecurityEvent(
                    'SQL_INJECTION',
                    'CRITICAL',
                    null,
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    ['input' => substr($input, 0, 200), 'pattern_matched' => $pattern]
                );
                return true;
            }
        }

        return false;
    }

    /**
     * Detect XSS attempts
     */
    public static function detectXSS(string $input): bool {
        $patterns = [
            '/<script[\s\S]*?>[\s\S]*?<\/script>/i',
            '/on\w+\s*=\s*["\'].*?["\']/i',
            '/<iframe/i',
            '/javascript:/i',
            '/<object/i',
            '/<embed/i',
            '/onerror\s*=/i',
            '/onload\s*=/i'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                self::logSecurityEvent(
                    'XSS_ATTEMPT',
                    'HIGH',
                    null,
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                    ['input' => substr($input, 0, 200)]
                );
                return true;
            }
        }

        return false;
    }

    /**
     * Validate all user inputs for security threats
     */
    public static function validateInput(array $inputs): bool {
        foreach ($inputs as $key => $value) {
            if (is_string($value)) {
                if (self::detectSQLInjection($value)) {
                    return false;
                }
                if (self::detectXSS($value)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Calculate threat score for an IP address
     * Based on security events in last 24 hours
     */
    public static function calculateThreatScore(string $ipAddress): int {
        try {
            $stmt = self::$db->prepare("
                SELECT
                    COUNT(*) as total_events,
                    SUM(CASE WHEN severity = 'CRITICAL' THEN 10 ELSE 0 END) +
                    SUM(CASE WHEN severity = 'HIGH' THEN 5 ELSE 0 END) +
                    SUM(CASE WHEN severity = 'MEDIUM' THEN 2 ELSE 0 END) +
                    SUM(CASE WHEN severity = 'LOW' THEN 1 ELSE 0 END) as score
                FROM security_events
                WHERE ip_address = ?
                AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            $stmt->execute([$ipAddress]);
            $result = $stmt->fetch();

            return (int)($result['score'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Log security event to database
     */
    public static function logSecurityEvent(
        string $eventType,
        string $severity,
        ?string $userId = null,
        ?string $ipAddress = null,
        array $details = []
    ): bool {
        try {
            $stmt = self::$db->prepare("
                INSERT INTO security_events (
                    event_type, severity, user_id, ip_address, user_agent,
                    request_uri, request_method, details, threat_score, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $threatScore = $ipAddress ? self::calculateThreatScore($ipAddress) : 0;

            return $stmt->execute([
                $eventType,
                $severity,
                $userId,
                $ipAddress ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                $_SERVER['REQUEST_URI'] ?? 'unknown',
                $_SERVER['REQUEST_METHOD'] ?? 'unknown',
                json_encode($details),
                $threatScore
            ]);
        } catch (Exception $e) {
            error_log("Failed to log security event: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check file integrity
     */
    public static function checkFileIntegrity(string $filePath): bool {
        try {
            if (!file_exists($filePath)) {
                self::updateFileStatus($filePath, 'MISSING');
                return false;
            }

            $currentHash = hash_file('sha256', $filePath);
            $currentSize = filesize($filePath);

            $stmt = self::$db->prepare("
                SELECT file_hash, file_size, status 
                FROM file_integrity
                WHERE file_path = ?
            ");
            $stmt->execute([$filePath]);
            $stored = $stmt->fetch();

            if (!$stored) {
                // First time - store hash
                self::storeFileHash($filePath, $currentHash, $currentSize);
                return true;
            }

            // Check if file has been modified
            $isIntact = ($currentHash === $stored['file_hash']);

            if (!$isIntact) {
                self::updateFileStatus($filePath, 'MODIFIED', $currentHash, $currentSize);
                self::logSecurityEvent(
                    'FILE_TAMPERING',
                    'CRITICAL',
                    null,
                    null,
                    ['file' => $filePath, 'expected_hash' => $stored['file_hash'], 'actual_hash' => $currentHash]
                );
            } else {
                self::updateFileStatus($filePath, 'INTACT', $currentHash, $currentSize);
            }

            return $isIntact;
        } catch (Exception $e) {
            error_log("File integrity check failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Store file hash for integrity checking
     */
    private static function storeFileHash(string $filePath, string $hash, int $size): bool {
        try {
            $stmt = self::$db->prepare("
                INSERT INTO file_integrity (file_path, file_hash, file_size, last_checked, status)
                VALUES (?, ?, ?, NOW(), 'INTACT')
                ON DUPLICATE KEY UPDATE 
                    file_hash = VALUES(file_hash),
                    file_size = VALUES(file_size),
                    last_checked = NOW(),
                    status = 'INTACT'
            ");
            return $stmt->execute([$filePath, $hash, $size]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Update file status
     */
    private static function updateFileStatus(
        string $filePath, 
        string $status, 
        ?string $hash = null, 
        ?int $size = null
    ): bool {
        try {
            if ($hash && $size) {
                $stmt = self::$db->prepare("
                    UPDATE file_integrity
                    SET status = ?, file_hash = ?, file_size = ?, last_checked = NOW()
                    WHERE file_path = ?
                ");
                return $stmt->execute([$status, $hash, $size, $filePath]);
            } else {
                $stmt = self::$db->prepare("
                    UPDATE file_integrity
                    SET status = ?, last_checked = NOW()
                    WHERE file_path = ?
                ");
                return $stmt->execute([$status, $filePath]);
            }
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Auto-blacklist IP after threshold
     */
    private static function autoBlacklistIP(string $ipAddress, string $reason): bool {
        try {
            // Check if already blacklisted
            if (self::isBlacklisted($ipAddress)) {
                return true;
            }

            $stmt = self::$db->prepare("
                INSERT INTO ip_blacklist (ip_address, reason, blocked_by, blocked_at, is_active)
                VALUES (?, ?, 'SYSTEM', NOW(), 1)
            ");
            
            return $stmt->execute([$ipAddress, $reason]);
        } catch (Exception $e) {
            error_log("Auto-blacklist failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get recent security events
     */
    public static function getRecentEvents(int $limit = 50, array $filters = []): array {
        try {
            $where = [];
            $params = [];

            if (!empty($filters['event_type'])) {
                $where[] = "event_type = ?";
                $params[] = $filters['event_type'];
            }

            if (!empty($filters['severity'])) {
                $where[] = "severity = ?";
                $params[] = $filters['severity'];
            }

            if (!empty($filters['ip_address'])) {
                $where[] = "ip_address = ?";
                $params[] = $filters['ip_address'];
            }

            $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

            $stmt = self::$db->prepare("
                SELECT * FROM security_events
                {$whereClause}
                ORDER BY created_at DESC
                LIMIT ?
            ");

            $params[] = $limit;
            $stmt->execute($params);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get security statistics
     */
    public static function getSecurityStats(string $period = 'today'): array {
        try {
            $dateCondition = match($period) {
                'today' => "DATE(created_at) = CURDATE()",
                'week' => "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
                'month' => "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
                default => "DATE(created_at) = CURDATE()"
            };

            $stmt = self::$db->query("
                SELECT 
                    COUNT(*) as total_events,
                    SUM(CASE WHEN severity = 'CRITICAL' THEN 1 ELSE 0 END) as critical,
                    SUM(CASE WHEN severity = 'HIGH' THEN 1 ELSE 0 END) as high,
                    SUM(CASE WHEN severity = 'MEDIUM' THEN 1 ELSE 0 END) as medium,
                    SUM(CASE WHEN severity = 'LOW' THEN 1 ELSE 0 END) as low,
                    SUM(CASE WHEN event_type = 'FAILED_LOGIN' THEN 1 ELSE 0 END) as failed_logins,
                    SUM(CASE WHEN event_type = 'BRUTE_FORCE' THEN 1 ELSE 0 END) as brute_force,
                    SUM(CASE WHEN event_type = 'SQL_INJECTION' THEN 1 ELSE 0 END) as sql_injection,
                    SUM(CASE WHEN event_type = 'XSS_ATTEMPT' THEN 1 ELSE 0 END) as xss_attempts,
                    COUNT(DISTINCT ip_address) as unique_ips
                FROM security_events
                WHERE {$dateCondition}
            ");

            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Clean old security events (keep last N days)
     */
    public static function cleanOldEvents(int $keepDays = 90): int {
        try {
            $stmt = self::$db->prepare("
                DELETE FROM security_events
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
                AND severity NOT IN ('CRITICAL', 'HIGH')
            ");

            $stmt->execute([$keepDays]);
            return $stmt->rowCount();
        } catch (Exception $e) {
            return 0;
        }
    }
}

// Initialize on load
SecurityMonitor::init();