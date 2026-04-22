<?php
// app/core/Guard.php - UPDATED VERSION WITH UL7

/**
 * Guard Class - Enhanced Role-Based Access Control
 * Now includes UL7 (Super Admin) support
 * 
 * @version 2.0
 * @author LMS Development Team
 */

class Guard {
    
    /**
     * Require user to be logged in
     */
    public static function requireLogin(): void {
        if (!Auth::check()) {
            header('Location: /public/login.php');
            exit;
        }
    }

    /**
     * Require UL1 role (11kV Data Entry Staff)
     */
    public static function requireUL1(): void {
        self::requireLogin();
        $user = Auth::user();
        
        if ($user['role'] !== 'UL1') {
            self::showAccessDenied('This page is only accessible to 11kV Data Entry Staff (UL1)');
        }
    }

    /**
     * Require UL2 role (33kV Data Entry Staff)
     */
    public static function requireUL2(): void {
        self::requireLogin();
        $user = Auth::user();
        
        if ($user['role'] !== 'UL2') {
            self::showAccessDenied('This page is only accessible to 33kV Data Entry Staff (UL2)');
        }
    }

    /**
     * Require UL3 role (Analyst - Concurs corrections)
     */
    public static function requireAnalyst(): void {
        self::requireLogin();
        $user = Auth::user();
        
        if ($user['role'] !== 'UL3') {
            self::showAccessDenied('This page is only accessible to Analysts (UL3)');
        }
    }

    /**
     * Require UL4 role (Manager - Approves corrections)
     */
    public static function requireManager(): void {
        self::requireLogin();
        $user = Auth::user();
        
        if ($user['role'] !== 'UL4') {
            self::showAccessDenied('This page is only accessible to Managers (UL4)');
        }
    }

    /**
     * Require UL5 role (Staff View - Read-only)
     */
    public static function requireStaffView(): void {
        self::requireLogin();
        $user = Auth::user();
        
        if ($user['role'] !== 'UL5') {
            self::showAccessDenied('This page is only accessible to Staff View users (UL5)');
        }
    }

    /**
     * Require UL6 role (Admin/Super Operator - Manages UL1 & UL2 only)
     */
    public static function requireAdmin(): void {
        self::requireLogin();
        $user = Auth::user();
        
        if ($user['role'] !== 'UL6') {
            self::showAccessDenied('This page is only accessible to Administrators (UL6)');
        }
    }

    /**
     * Require UL7 role (Super Admin - Full system access)
     * 🆕 NEW FOR UL7
     */
    public static function requireSuperAdmin(): void {
        self::requireLogin();
        $user = Auth::user();
        
        if ($user['role'] !== 'UL7') {
            self::showAccessDenied('This page requires Super Admin privileges (UL7)');
        }

        // Log all UL7 page accesses for security
        AuditLogger::logSecurityAction(
            'SUPERADMIN_PAGE_ACCESS',
            ['page' => $_SERVER['REQUEST_URI'] ?? 'unknown']
        );
    }

    /**
     * Check if user has specific role (non-blocking)
     */
    public static function hasRole(string $role): bool {
        if (!Auth::check()) {
            return false;
        }
        
        $user = Auth::user();
        return $user['role'] === $role;
    }

    /**
     * Check if user has any of the specified roles
     */
    public static function hasAnyRole(array $roles): bool {
        if (!Auth::check()) {
            return false;
        }
        
        $user = Auth::user();
        return in_array($user['role'], $roles);
    }

    /**
     * Check if current user can manage target user
     * UL7 can manage UL1-UL6
     * UL6 can only manage UL1 & UL2
     * 🆕 UPDATED FOR UL7
     */
    public static function canManageUser(string $targetRole): bool {
        self::requireLogin();
        $user = Auth::user();

        // UL7 (Super Admin) can manage everyone except other UL7s
        if ($user['role'] === 'UL7') {
            return in_array($targetRole, ['UL1', 'UL2', 'UL3', 'UL4', 'UL5', 'UL6']);
        }

        // UL6 (Admin) can only manage UL1 and UL2
        if ($user['role'] === 'UL6') {
            return in_array($targetRole, ['UL1', 'UL2']);
        }

        return false;
    }

    /**
     * Check if user can create users
     * Only UL7 can create users
     * 🆕 NEW FOR UL7
     */
    public static function canCreateUsers(): bool {
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();
        return $user['role'] === 'UL7';
    }

    /**
     * Check if user can delete users
     * Only UL7 can delete users
     * 🆕 NEW FOR UL7
     */
    public static function canDeleteUsers(): bool {
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();
        return $user['role'] === 'UL7';
    }

    /**
     * Check if user can access security features
     * Only UL7
     * 🆕 NEW FOR UL7
     */
    public static function canAccessSecurity(): bool {
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();
        return $user['role'] === 'UL7';
    }

    /**
     * Check if user can access audit logs
     * Only UL7 has full access
     * 🆕 NEW FOR UL7
     */
    public static function canAccessAuditLogs(): bool {
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();
        return $user['role'] === 'UL7';
    }

    /**
     * Require approval permissions (UL3 or UL4)
     * UL3 concurs, UL4 approves
     */
    public static function requireApprovalPermission(): void {
        self::requireLogin();
        $user = Auth::user();
        
        if (!in_array($user['role'], ['UL3', 'UL4'])) {
            self::showAccessDenied('Only Analysts (UL3) and Managers (UL4) can review corrections');
        }
    }

    /**
     * Require data entry permissions (UL1 or UL2)
     */
    public static function requireDataEntryPermission(): void {
        self::requireLogin();
        $user = Auth::user();
        
        if (!in_array($user['role'], ['UL1', 'UL2'])) {
            self::showAccessDenied('Only data entry staff (UL1/UL2) can enter load data');
        }
    }

    /**
     * Require management permissions (UL4, UL6, or UL7)
     */
    public static function requireManagementPermission(): void {
        self::requireLogin();
        $user = Auth::user();
        
        if (!in_array($user['role'], ['UL4', 'UL6', 'UL7'])) {
            self::showAccessDenied('Management access required');
        }
    }

    /**
     * Check if user can force logout others
     * Only UL7
     * 🆕 NEW FOR UL7
     */
    public static function canForceLogout(): bool {
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();
        return $user['role'] === 'UL7';
    }

    /**
     * Check if user can access system configuration
     * Only UL7
     * 🆕 NEW FOR UL7
     */
    public static function canConfigureSystem(): bool {
        if (!Auth::check()) {
            return false;
        }

        $user = Auth::user();
        return $user['role'] === 'UL7';
    }

    /**
     * Show access denied page
     */
    public static function showAccessDenied(string $message = 'Access Denied'): void {
        http_response_code(403);
        
        // Log unauthorized access attempt
        $user = Auth::user();
        if ($user) {
            AuditLogger::log(
                'UNAUTHORIZED_ACCESS',
                'SECURITY',
                'access_control',
                null,
                null,
                null,
                [
                    'user_id' => $user['payroll_id'],
                    'role' => $user['role'],
                    'requested_page' => $_SERVER['REQUEST_URI'] ?? 'unknown',
                    'message' => $message
                ],
                'MEDIUM'
            );

            // Also log as security event
            SecurityMonitor::logSecurityEvent(
                'UNAUTHORIZED_ACCESS',
                'MEDIUM',
                $user['payroll_id'],
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                ['requested_page' => $_SERVER['REQUEST_URI'] ?? 'unknown']
            );
        }

        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Access Denied - Load Monitoring System</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    min-height: 100vh;
                    padding: 20px;
                }
                .access-denied-container {
                    background: white;
                    padding: 50px;
                    border-radius: 20px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    text-align: center;
                    max-width: 600px;
                    width: 100%;
                }
                .lock-icon {
                    font-size: 80px;
                    color: #dc3545;
                    margin-bottom: 20px;
                }
                h1 {
                    color: #2c3e50;
                    margin-bottom: 15px;
                    font-size: 32px;
                }
                p {
                    color: #6c757d;
                    margin-bottom: 30px;
                    font-size: 18px;
                    line-height: 1.6;
                }
                .user-info {
                    background: #f8f9fa;
                    padding: 20px;
                    border-radius: 10px;
                    margin-bottom: 30px;
                    text-align: left;
                }
                .user-info strong {
                    color: #495057;
                }
                .btn-back {
                    display: inline-block;
                    padding: 15px 40px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    text-decoration: none;
                    border-radius: 10px;
                    font-weight: 600;
                    transition: all 0.3s;
                }
                .btn-back:hover {
                    transform: translateY(-3px);
                    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
                }
            </style>
        </head>
        <body>
            <div class="access-denied-container">
                <div class="lock-icon">🔒</div>
                <h1>Access Denied</h1>
                <p><?= htmlspecialchars($message) ?></p>
                
                <?php if ($user): ?>
                <div class="user-info">
                    <strong>Your Role:</strong> <?= htmlspecialchars($user['role']) ?><br>
                    <strong>Your Name:</strong> <?= htmlspecialchars($user['staff_name']) ?><br>
                    <strong>Payroll ID:</strong> <?= htmlspecialchars($user['payroll_id']) ?>
                </div>
                <?php endif; ?>
                
                <a href="/public/index.php?page=dashboard" class="btn-back">← Return to Dashboard</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Validate ISS assignment for UL1 users
     */
    public static function validateISSAccess(string $issCode): bool {
        self::requireLogin();
        $user = Auth::user();

        // UL7 and UL6 have access to all ISS
        if (in_array($user['role'], ['UL6', 'UL7'])) {
            return true;
        }

        // UL1 can only access their assigned ISS
        if ($user['role'] === 'UL1') {
            return $user['iss_code'] === $issCode;
        }

        return false;
    }

    /**
     * Validate 33kV feeder access for UL2 users
     */
    public static function validate33kVAccess(string $feederCode): bool {
        self::requireLogin();
        $user = Auth::user();

        // UL7 and UL6 have access to all feeders
        if (in_array($user['role'], ['UL6', 'UL7'])) {
            return true;
        }

        // UL2 can only access their assigned feeder
        if ($user['role'] === 'UL2') {
            return $user['assigned_33kv_code'] === $feederCode;
        }

        return false;
    }
}
