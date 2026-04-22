<?php
// app/core/SystemHealth.php

/**
 * System Health Class
 * Monitor system performance and health metrics
 * 
 * @version 1.0
 * @author LMS Development Team
 */

class SystemHealth {
    private static $db;

    public static function init() {
        self::$db = Database::getInstance();
    }

    /**
     * Perform complete system health check
     * This should be run periodically (every 5-15 minutes)
     */
    public static function performHealthCheck(): array {
        $metrics = [
            'cpu_usage' => self::getCPUUsage(),
            'memory_usage' => self::getMemoryUsage(),
            'disk_usage' => self::getDiskUsage(),
            'database_size' => self::getDatabaseSize(),
            'active_sessions' => SessionManager::getActiveSessions(),
            'query_performance' => self::getQueryPerformance(),
            'error_count' => self::getErrorCount(),
            'warning_count' => self::getWarningCount()
        ];

        // Calculate health score
        $healthScore = self::calculateHealthScore($metrics);
        $status = self::determineStatus($healthScore);

        // Store metrics
        self::storeHealthMetrics($metrics, $healthScore, $status);

        return array_merge($metrics, [
            'health_score' => $healthScore,
            'status' => $status,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get CPU usage percentage
     * Note: This is a simplified version. For production, use system-specific commands
     */
    private static function getCPUUsage(): float {
        // For Linux systems
        if (PHP_OS_FAMILY === 'Linux' && file_exists('/proc/stat')) {
            static $prevStats = null;
            
            $stats = @file_get_contents('/proc/stat');
            if ($stats === false) {
                return 0.0;
            }

            preg_match('/^cpu\s+(\d+)\s+(\d+)\s+(\d+)\s+(\d+)/', $stats, $matches);
            
            if (!$matches || $prevStats === null) {
                $prevStats = $matches;
                return 0.0;
            }

            $prevIdle = $prevStats[4];
            $prevTotal = array_sum(array_slice($prevStats, 1));
            
            $idle = $matches[4];
            $total = array_sum(array_slice($matches, 1));

            $diffIdle = $idle - $prevIdle;
            $diffTotal = $total - $prevTotal;

            $prevStats = $matches;

            return $diffTotal > 0 ? (($diffTotal - $diffIdle) / $diffTotal) * 100 : 0.0;
        }

        // Fallback for other systems
        return 0.0;
    }

    /**
     * Get memory usage percentage
     */
    private static function getMemoryUsage(): float {
        $memoryUsed = memory_get_usage(true);
        $memoryLimit = self::parseMemoryLimit(ini_get('memory_limit'));

        if ($memoryLimit > 0) {
            return ($memoryUsed / $memoryLimit) * 100;
        }

        return 0.0;
    }

    /**
     * Parse memory limit string (e.g., "128M" to bytes)
     */
    private static function parseMemoryLimit(string $limit): int {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit)-1]);
        $value = (int)$limit;

        switch($last) {
            case 'g': $value *= 1024;
            case 'm': $value *= 1024;
            case 'k': $value *= 1024;
        }

        return $value;
    }

    /**
     * Get disk usage percentage
     */
    private static function getDiskUsage(): float {
        $totalSpace = disk_total_space('/');
        $freeSpace = disk_free_space('/');

        if ($totalSpace > 0) {
            return (($totalSpace - $freeSpace) / $totalSpace) * 100;
        }

        return 0.0;
    }

    /**
     * Get database size in bytes
     */
    private static function getDatabaseSize(): int {
        try {
            $stmt = self::$db->query("
                SELECT SUM(data_length + index_length) as size
                FROM information_schema.TABLES
                WHERE table_schema = DATABASE()
            ");

            $result = $stmt->fetch();
            return (int)($result['size'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get average query performance (ms)
     */
    private static function getQueryPerformance(): float {
        try {
            // Run a sample query and measure time
            $start = microtime(true);
            
            self::$db->query("
                SELECT COUNT(*) FROM staff_details
            ");
            
            $end = microtime(true);
            
            return ($end - $start) * 1000; // Convert to milliseconds
        } catch (Exception $e) {
            return 0.0;
        }
    }

    /**
     * Get error count from audit logs (today)
     */
    private static function getErrorCount(): int {
        try {
            $stmt = self::$db->query("
                SELECT COUNT(*) as count
                FROM audit_logs
                WHERE DATE(created_at) = CURDATE()
                AND status = 'FAILED'
            ");

            $result = $stmt->fetch();
            return (int)($result['count'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get warning count from security events (today)
     */
    private static function getWarningCount(): int {
        try {
            $stmt = self::$db->query("
                SELECT COUNT(*) as count
                FROM security_events
                WHERE DATE(created_at) = CURDATE()
                AND severity IN ('MEDIUM', 'HIGH')
            ");

            $result = $stmt->fetch();
            return (int)($result['count'] ?? 0);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Calculate overall health score (0-100)
     */
    private static function calculateHealthScore(array $metrics): int {
        $score = 100;

        // CPU penalty (20 points max)
        if ($metrics['cpu_usage'] > 90) {
            $score -= 20;
        } elseif ($metrics['cpu_usage'] > 70) {
            $score -= 10;
        } elseif ($metrics['cpu_usage'] > 50) {
            $score -= 5;
        }

        // Memory penalty (20 points max)
        if ($metrics['memory_usage'] > 90) {
            $score -= 20;
        } elseif ($metrics['memory_usage'] > 70) {
            $score -= 10;
        } elseif ($metrics['memory_usage'] > 50) {
            $score -= 5;
        }

        // Disk penalty (15 points max)
        if ($metrics['disk_usage'] > 90) {
            $score -= 15;
        } elseif ($metrics['disk_usage'] > 80) {
            $score -= 10;
        } elseif ($metrics['disk_usage'] > 70) {
            $score -= 5;
        }

        // Query performance penalty (15 points max)
        if ($metrics['query_performance'] > 1000) { // > 1 second
            $score -= 15;
        } elseif ($metrics['query_performance'] > 500) {
            $score -= 10;
        } elseif ($metrics['query_performance'] > 200) {
            $score -= 5;
        }

        // Error penalty (15 points max)
        if ($metrics['error_count'] > 50) {
            $score -= 15;
        } elseif ($metrics['error_count'] > 20) {
            $score -= 10;
        } elseif ($metrics['error_count'] > 10) {
            $score -= 5;
        }

        // Warning penalty (15 points max)
        if ($metrics['warning_count'] > 100) {
            $score -= 15;
        } elseif ($metrics['warning_count'] > 50) {
            $score -= 10;
        } elseif ($metrics['warning_count'] > 20) {
            $score -= 5;
        }

        return max(0, min(100, $score));
    }

    /**
     * Determine system status based on health score
     */
    private static function determineStatus(int $healthScore): string {
        if ($healthScore >= 85) {
            return 'HEALTHY';
        } elseif ($healthScore >= 70) {
            return 'WARNING';
        } else {
            return 'CRITICAL';
        }
    }

    /**
     * Store health metrics in database
     */
    private static function storeHealthMetrics(array $metrics, int $healthScore, string $status): bool {
        try {
            $stmt = self::$db->prepare("
                INSERT INTO system_health (
                    check_time, cpu_usage, memory_usage, disk_usage,
                    database_size, active_sessions, query_performance,
                    error_count, warning_count, health_score, status, details
                ) VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            return $stmt->execute([
                $metrics['cpu_usage'],
                $metrics['memory_usage'],
                $metrics['disk_usage'],
                $metrics['database_size'],
                $metrics['active_sessions'],
                $metrics['query_performance'],
                $metrics['error_count'],
                $metrics['warning_count'],
                $healthScore,
                $status,
                json_encode($metrics)
            ]);
        } catch (Exception $e) {
            error_log("Failed to store health metrics: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get latest health check
     */
    public static function getLatestHealth(): ?array {
        try {
            $stmt = self::$db->query("
                SELECT * FROM system_health
                ORDER BY check_time DESC
                LIMIT 1
            ");

            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get health history
     */
    public static function getHealthHistory(int $hours = 24): array {
        try {
            $stmt = self::$db->prepare("
                SELECT * FROM system_health
                WHERE check_time >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                ORDER BY check_time DESC
            ");

            $stmt->execute([$hours]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get health trends (for charts)
     */
    public static function getHealthTrends(int $hours = 24): array {
        try {
            $stmt = self::$db->prepare("
                SELECT 
                    DATE_FORMAT(check_time, '%Y-%m-%d %H:%i') as time,
                    health_score,
                    cpu_usage,
                    memory_usage,
                    disk_usage,
                    active_sessions
                FROM system_health
                WHERE check_time >= DATE_SUB(NOW(), INTERVAL ? HOUR)
                ORDER BY check_time ASC
            ");

            $stmt->execute([$hours]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Clean old health records (keep last N days)
     */
    public static function cleanOldRecords(int $keepDays = 30): int {
        try {
            $stmt = self::$db->prepare("
                DELETE FROM system_health
                WHERE check_time < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");

            $stmt->execute([$keepDays]);
            return $stmt->rowCount();
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get critical alerts
     */
    public static function getCriticalAlerts(): array {
        $alerts = [];
        $latest = self::getLatestHealth();

        if (!$latest) {
            return $alerts;
        }

        // CPU alerts
        if ($latest['cpu_usage'] > 90) {
            $alerts[] = [
                'type' => 'CRITICAL',
                'category' => 'CPU',
                'message' => 'CPU usage critically high: ' . number_format($latest['cpu_usage'], 1) . '%',
                'severity' => 'CRITICAL'
            ];
        } elseif ($latest['cpu_usage'] > 70) {
            $alerts[] = [
                'type' => 'WARNING',
                'category' => 'CPU',
                'message' => 'CPU usage elevated: ' . number_format($latest['cpu_usage'], 1) . '%',
                'severity' => 'HIGH'
            ];
        }

        // Memory alerts
        if ($latest['memory_usage'] > 90) {
            $alerts[] = [
                'type' => 'CRITICAL',
                'category' => 'MEMORY',
                'message' => 'Memory usage critically high: ' . number_format($latest['memory_usage'], 1) . '%',
                'severity' => 'CRITICAL'
            ];
        }

        // Disk alerts
        if ($latest['disk_usage'] > 90) {
            $alerts[] = [
                'type' => 'CRITICAL',
                'category' => 'DISK',
                'message' => 'Disk space critically low: ' . number_format($latest['disk_usage'], 1) . '% used',
                'severity' => 'CRITICAL'
            ];
        }

        // Error count alerts
        if ($latest['error_count'] > 50) {
            $alerts[] = [
                'type' => 'WARNING',
                'category' => 'ERRORS',
                'message' => 'High error count today: ' . $latest['error_count'] . ' errors',
                'severity' => 'HIGH'
            ];
        }

        return $alerts;
    }
}

// Initialize on load
SystemHealth::init();