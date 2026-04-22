<?php
/**
 * Enhanced Guard with CORRECTED Role Enforcement
 * File: /app/core/Guard.php
 * 
 * CORRECTION WORKFLOW:
 * - UL3: Concurs to correction requests (first review)
 * - UL4: Approves correction requests (final approval)
 */

class Guard
{
    /**
     * Require user to be logged in
     */
    public static function requireLogin(): void
    {
        if (!Auth::check()) {
            error_log("GUARD: Auth check failed, redirecting to login");
            $base = defined('BASE_PATH') ? BASE_PATH : '/load_monitor/public';
            header("Location: {$base}/index.php?page=login");
            exit;
        }
    }

    /**
     * Require user to have specific role(s)
     * 
     * @param array $roles Array of allowed roles (e.g., ['UL1', 'UL2'])
     */
    public static function requireRole(array $roles): void
    {
        self::requireLogin();
        
        $user = Auth::user();
        $userRole = $user['role'] ?? null;
        
        if (!in_array($userRole, $roles, true)) {
            error_log("GUARD: Role check failed - User: {$userRole}, Required: " . implode(', ', $roles));
            
            // Show friendly error page
            http_response_code(403);
            self::showAccessDenied($userRole, $roles);
            exit;
        }
    }
    
    /**
     * Helper methods for specific role checks
     */
    
    // Data Entry Roles
    public static function requireUL1(): void
    {
        self::requireRole(['UL1']);
    }
    
    public static function requireUL2(): void
    {
        self::requireRole(['UL2']);
    }
    
    // Correction Workflow Roles
    public static function requireAnalyst(): void
    {
        // UL3 ONLY - Concurs to correction requests
        self::requireRole(['UL3']);
    }
    
    public static function requireManager(): void
    {
        // UL4 ONLY - Final approval of correction requests
        self::requireRole(['UL4']);
    }
    
    // Senior Management & Admin
    public static function requireSeniorManagement(): void
    {
        // UL5 - Staff view dashboard
        self::requireRole(['UL5']);
    }
    
    public static function requireAdmin(): void
    {
        // UL6 ONLY - System administration
        self::requireRole(['UL6']);
    }
    
    // Combined access for data entry staff
    public static function requireDataEntry(): void
    {
        self::requireRole(['UL1', 'UL2']);
    }
    
    /**
     * Check if current user has specific role
     * Non-blocking check (returns boolean)
     */
    public static function hasRole(string $role): bool
    {
        if (!Auth::check()) {
            return false;
        }
        
        $user = Auth::user();
        return ($user['role'] ?? null) === $role;
    }
    
    /**
     * Check if current user has any of the specified roles
     */
    public static function hasAnyRole(array $roles): bool
    {
        if (!Auth::check()) {
            return false;
        }
        
        $user = Auth::user();
        $userRole = $user['role'] ?? null;
        
        return in_array($userRole, $roles, true);
    }
    
    /**
     * Display access denied page
     */
    private static function showAccessDenied(?string $userRole, array $requiredRoles): void
    {
        $user = Auth::user();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Access Denied</title>
            <style>
                body {
                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    height: 100vh;
                    margin: 0;
                }
                .container {
                    background: white;
                    padding: 40px 50px;
                    border-radius: 16px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    text-align: center;
                    max-width: 500px;
                }
                .error-icon {
                    font-size: 64px;
                    margin-bottom: 20px;
                }
                h1 {
                    color: #dc2626;
                    margin: 0 0 10px;
                    font-size: 28px;
                }
                .message {
                    color: #6b7280;
                    margin-bottom: 30px;
                    line-height: 1.6;
                }
                .info {
                    background: #f3f4f6;
                    padding: 15px;
                    border-radius: 8px;
                    margin: 20px 0;
                    font-size: 14px;
                }
                .info strong {
                    color: #374151;
                }
                .btn {
                    display: inline-block;
                    background: linear-gradient(135deg, #0b3a82 0%, #1e40af 100%);
                    color: white;
                    padding: 12px 30px;
                    border-radius: 8px;
                    text-decoration: none;
                    font-weight: 600;
                    transition: transform 0.2s ease;
                }
                .btn:hover {
                    transform: translateY(-2px);
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="error-icon">🚫</div>
                <h1>Access Denied</h1>
                <p class="message">
                    You do not have permission to access this page.
                </p>
                
                <div class="info">
                    <strong>Your Role:</strong> <?= htmlspecialchars($userRole ?? 'Unknown') ?><br>
                    <strong>Required Role(s):</strong> <?= htmlspecialchars(implode(', ', $requiredRoles)) ?><br>
                    <strong>User:</strong> <?= htmlspecialchars($user['staff_name'] ?? 'Unknown') ?>
                </div>
                
                <a href="<?= defined('BASE_PATH') ? BASE_PATH : '/load_monitor/public' ?>/index.php?page=dashboard" class="btn">
                    Return to Dashboard
                </a>
            </div>
        </body>
        </html>
        <?php
    }
}
