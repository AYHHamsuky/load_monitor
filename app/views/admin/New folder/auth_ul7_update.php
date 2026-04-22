<?php
// app/core/Auth.php - UPDATED VERSION WITH UL7 SECURITY

/**
 * Auth Class - Enhanced Authentication System
 * Now includes security monitoring and UL7 support
 * 
 * @version 2.0
 * @author LMS Development Team
 */

class Auth {
    private static $db;

    public static function init() {
        self::$db = Database::getInstance();
    }

    /**
     * Enhanced login with security monitoring
     * 🆕 UPDATED FOR UL7
     */
    public static function attemptWithAudit(string $payroll_id, string $password): bool {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        try {
            // 1. Check if IP is blacklisted
            if (SecurityMonitor::isBlacklisted($ip)) {
                AuditLogger::logAuth('LOGIN_BLOCKED_BLACKLIST', $payroll_id, false, [
                    'ip' => $ip,
                    'reason' => 'IP is blacklisted'
                ]);
                return false;
            }

            // 2. Check for brute force attempts
            if (SecurityMonitor::checkBruteForce($ip)) {
                AuditLogger::logAuth('LOGIN_BLOCKED_BRUTE_FORCE', $payroll_id, false, [
                    'ip' => $ip,
                    'reason' => 'Brute force detected'
                ]);
                return false;
            }

            // 3. Get user from database
            $stmt = self::$db->prepare("
                SELECT * FROM staff_details 
                WHERE payroll_id = ? AND is_active = 'Yes'
            ");
            $stmt->execute([$payroll_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                // User not found or inactive
                self::logFailedAttempt($payroll_id, $ip, 'User not found or inactive');
                return false;
            }

            // 4. Check if account is locked
            if (self::isAccountLocked($user)) {
                AuditLogger::logAuth('LOGIN_BLOCKED_LOCKED', $payroll_id, false, [
                    'ip' => $ip,
                    'locked_until' => $user['account_locked_until']
                ]);
                return false;
            }

            // 5. Verify password
            if (!password_verify($password, $user['password_hash'])) {
                self::incrementFailedAttempts($payroll_id, $ip);
                self::logFailedAttempt($payroll_id, $ip, 'Invalid password');
                return false;
            }

            // 6. Check if logins are globally enabled
            if (!self::areLoginsEnabled()) {
                AuditLogger::logAuth('LOGIN_BLOCKED_MAINTENANCE', $payroll_id, false, [
                    'ip' => $ip,
                    'reason' => 'System maintenance mode'
                ]);
                return false;
            }

            // 7. Successful login - reset failed attempts
            self::resetFailedAttempts($payroll_id);

            // 8. Update last login and IP
            self::updateLastLogin($payroll_id, $ip);

            // 9. Create session
            $_SESSION['user_id'] = $user['payroll_id'];
            $_SESSION['login_time'] = time();
            $_SESSION['ip_address'] = $ip;

            // 10. Create session record in database
            SessionManager::create($user['payroll_id']);

            // 11. Log successful login
            AuditLogger::logAuth('LOGIN_SUCCESS', $payroll_id, true, [
                'ip' => $ip,
                'role' => $user['role']
            ]);

            return true;

        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Original attempt method (for backward compatibility)
     */
    public static function attempt(string $payroll_id, string $password): bool {
        return self::attemptWithAudit($payroll_id, $password);
    }

    /**
     * Check if user is authenticated
     */
    public static function check(): bool {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }

        // Validate session hasn't expired
        if (isset($_SESSION['login_time'])) {
            $sessionTimeout = 8 * 60 * 60; // 8 hours
            if (time() - $_SESSION['login_time'] > $sessionTimeout) {
                self::logout();
                return false;
            }
        }

        // Update session activity
        SessionManager::updateActivity();

        return true;
    }

    /**
     * Get current authenticated user
     */
    public static function user(): ?array {
        if (!self::check()) {
            return null;
        }

        try {
            $stmt = self::$db->prepare("
                SELECT * FROM staff_details 
                WHERE payroll_id = ? AND is_active = 'Yes'
            ");
            $stmt->execute([$_SESSION['user_id']]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Logout user
     */
    public static function logout(): void {
        $user = self::user();
        
        if ($user) {
            // Log logout
            AuditLogger::logAuth('LOGOUT', $user['payroll_id'], true);
            
            // Destroy session record
            SessionManager::destroy(session_id());
        }

        // Clear session
        $_SESSION = [];
        session_destroy();
    }

    /**
     * Check if user is Super Admin (UL7)
     * 🆕 NEW FOR UL7
     */
    public static function isSuperAdmin(): bool {
        $user = self::user();
        return $user && $user['role'] === 'UL7';
    }

    /**
     * Check if user is Admin (UL6)
     */
    public static function isAdmin(): bool {
        $user = self::user();
        return $user && $user['role'] === 'UL6';
    }

    /**
     * Check if user can manage specific role
     * 🆕 UPDATED FOR UL7
     */
    public static function canManageRole(string $targetRole): bool {
        $user = self::user();
        if (!$user) return false;

        // UL7 can manage UL1-UL6
        if ($user['role'] === 'UL7') {
            return in_array($targetRole, ['UL1', 'UL2', 'UL3', 'UL4', 'UL5', 'UL6']);
        }

        // UL6 can only manage UL1 and UL2
        if ($user['role'] === 'UL6') {
            return in_array($targetRole, ['UL1', 'UL2']);
        }

        return false;
    }

    /**
     * Check if account is locked
     */
    private static function isAccountLocked(array $user): bool {
        if (!$user['account_locked_until']) {
            return false;
        }

        $lockTime = strtotime($user['account_locked_until']);
        return $lockTime > time();
    }

    /**
     * Increment failed login attempts
     */
    private static function incrementFailedAttempts(string $payroll_id, string $ip): void {
        try {
            $stmt = self::$db->prepare("
                UPDATE staff_details
                SET failed_login_attempts = failed_login_attempts + 1,
                    last_ip_address = ?
                WHERE payroll_id = ?
            ");
            $stmt->execute([$ip, $payroll_id]);

            // Check if we should lock the account
            $stmt = self::$db->prepare("
                SELECT failed_login_attempts FROM staff_details WHERE payroll_id = ?
            ");
            $stmt->execute([$payroll_id]);
            $result = $stmt->fetch();

            if ($result && $result['failed_login_attempts'] >= 5) {
                self::lockAccount($payroll_id, 30); // Lock for 30 minutes
            }
        } catch (Exception $e) {
            error_log("Failed to increment login attempts: " . $e->getMessage());
        }
    }

    /**
     * Lock account for specified minutes
     */
    private static function lockAccount(string $payroll_id, int $minutes): void {
        try {
            $stmt = self::$db->prepare("
                UPDATE staff_details
                SET account_locked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE)
                WHERE payroll_id = ?
            ");
            $stmt->execute([$minutes, $payroll_id]);

            AuditLogger::logAuth('ACCOUNT_LOCKED', $payroll_id, false, [
                'reason' => 'Too many failed attempts',
                'locked_for_minutes' => $minutes
            ]);
        } catch (Exception $e) {
            error_log("Failed to lock account: " . $e->getMessage());
        }
    }

    /**
     * Reset failed login attempts
     */
    private static function resetFailedAttempts(string $payroll_id): void {
        try {
            $stmt = self::$db->prepare("
                UPDATE staff_details
                SET failed_login_attempts = 0,
                    account_locked_until = NULL
                WHERE payroll_id = ?
            ");
            $stmt->execute([$payroll_id]);
        } catch (Exception $e) {
            error_log("Failed to reset login attempts: " . $e->getMessage());
        }
    }

    /**
     * Update last login timestamp and IP
     */
    private static function updateLastLogin(string $payroll_id, string $ip): void {
        try {
            $stmt = self::$db->prepare("
                UPDATE staff_details
                SET last_login = NOW(),
                    last_ip_address = ?
                WHERE payroll_id = ?
            ");
            $stmt->execute([$ip, $payroll_id]);
        } catch (Exception $e) {
            error_log("Failed to update last login: " . $e->getMessage());
        }
    }

    /**
     * Log failed login attempt
     */
    private static function logFailedAttempt(string $payroll_id, string $ip, string $reason): void {
        AuditLogger::logAuth('LOGIN_FAILED', $payroll_id, false, [
            'ip' => $ip,
            'reason' => $reason
        ]);

        SecurityMonitor::logSecurityEvent(
            'FAILED_LOGIN',
            'MEDIUM',
            $payroll_id,
            $ip,
            ['reason' => $reason]
        );
    }

    /**
     * Check if logins are globally enabled
     * 🆕 NEW FOR UL7
     */
    private static function areLoginsEnabled(): bool {
        try {
            $stmt = self::$db->prepare("
                SELECT config_value FROM system_config
                WHERE config_key = 'login_enabled'
            ");
            $stmt->execute();
            $result = $stmt->fetch();

            return $result ? ($result['config_value'] === 'true') : true;
        } catch (Exception $e) {
            return true; // Default to enabled if check fails
        }
    }

    /**
     * Enable/disable all logins (UL7 only)
     * 🆕 NEW FOR UL7
     */
    public static function setLoginsEnabled(bool $enabled): bool {
        if (!self::isSuperAdmin()) {
            return false;
        }

        try {
            $value = $enabled ? 'true' : 'false';
            
            $stmt = self::$db->prepare("
                UPDATE system_config
                SET config_value = ?,
                    updated_by = ?,
                    updated_at = NOW()
                WHERE config_key = 'login_enabled'
            ");

            $user = self::user();
            $stmt->execute([$value, $user['payroll_id']]);

            AuditLogger::logSystem(
                $enabled ? 'LOGINS_ENABLED' : 'LOGINS_DISABLED',
                ['action' => $enabled ? 'enabled' : 'disabled'],
                'CRITICAL'
            );

            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check password strength
     * 🆕 NEW FOR UL7
     */
    public static function isPasswordStrong(string $password): array {
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters";
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Password must contain at least one uppercase letter";
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Password must contain at least one lowercase letter";
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Password must contain at least one number";
        }
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = "Password must contain at least one special character";
        }

        return [
            'is_strong' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Change user password
     * 🆕 NEW FOR UL7
     */
    public static function changePassword(string $payroll_id, string $newPassword): bool {
        try {
            $hash = password_hash($newPassword, PASSWORD_BCRYPT);

            $stmt = self::$db->prepare("
                UPDATE staff_details
                SET password_hash = ?,
                    last_password_change = NOW()
                WHERE payroll_id = ?
            ");

            $result = $stmt->execute([$hash, $payroll_id]);

            if ($result) {
                AuditLogger::logUserManagement(
                    'PASSWORD_CHANGED',
                    $payroll_id,
                    null,
                    null,
                    ['changed_by' => self::user()['payroll_id'] ?? 'SYSTEM']
                );
            }

            return $result;
        } catch (Exception $e) {
            return false;
        }
    }
}

// Initialize on load
Auth::init();