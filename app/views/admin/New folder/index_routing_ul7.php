<?php
// public/index.php - UPDATED VERSION WITH UL7 ROUTING

require_once __DIR__ . '/../app/bootstrap.php';

// Get requested page
$page = $_GET['page'] ?? 'dashboard';

// Route handling
switch ($page) {
    case 'dashboard':
        Guard::requireLogin();
        $user = Auth::user();
        
        // Route to appropriate dashboard by role
        switch ($user['role']) {
            case 'UL1':
                // 11kV Data Entry Staff Dashboard
                require __DIR__ . '/../app/controllers/DashboardController.php';
                break;
                
            case 'UL2':
                // 33kV Data Entry Staff Dashboard
                require __DIR__ . '/../app/controllers/Dashboard33kvController.php';
                break;
                
            case 'UL3':
                // Analyst Dashboard
                require __DIR__ . '/../app/controllers/AnalystDashboardController.php';
                break;
                
            case 'UL4':
                // Manager Dashboard
                require __DIR__ . '/../app/controllers/ManagerDashboardController.php';
                break;
                
            case 'UL5':
                // Staff View Dashboard (Read-only)
                require __DIR__ . '/../app/controllers/StaffDashboardController.php';
                break;
                
            case 'UL6':
                // Admin Dashboard (Super Operator)
                require __DIR__ . '/../app/controllers/AdminDashboardController.php';
                break;
                
            case 'UL7':
                // 🆕 Super Admin Dashboard
                require __DIR__ . '/../app/controllers/SuperAdminDashboardController.php';
                break;
                
            default:
                die('Invalid role');
        }
        break;

    case 'superadmin':
        // 🆕 Super Admin Pages (Only UL7)
        Guard::requireSuperAdmin();
        
        $action = $_GET['action'] ?? 'dashboard';
        
        switch ($action) {
            case 'users':
                // User management page
                require __DIR__ . '/../app/controllers/SuperAdminUsersController.php';
                break;
                
            case 'audit-logs':
                // Audit logs viewer
                require __DIR__ . '/../app/controllers/AuditLogController.php';
                break;
                
            case 'security':
                // Security monitor
                require __DIR__ . '/../app/controllers/SecurityMonitorController.php';
                break;
                
            case 'sessions':
                // Active sessions management
                require __DIR__ . '/../app/controllers/SessionManagementController.php';
                break;
                
            case 'file-integrity':
                // File integrity checker
                require __DIR__ . '/../app/controllers/FileIntegrityController.php';
                break;
                
            case 'system-health':
                // System health monitor
                require __DIR__ . '/../app/controllers/SystemHealthController.php';
                break;
                
            case 'ip-management':
                // IP whitelist/blacklist management
                require __DIR__ . '/../app/controllers/IPManagementController.php';
                break;
                
            default:
                // Default to main dashboard
                require __DIR__ . '/../app/controllers/SuperAdminDashboardController.php';
        }
        break;

    case 'users':
        // User Management - Route based on role
        Guard::requireLogin();
        $user = Auth::user();
        
        if ($user['role'] === 'UL7') {
            // UL7: Manage all users (UL1-UL6)
            require __DIR__ . '/../app/controllers/SuperAdminUsersController.php';
        } elseif ($user['role'] === 'UL6') {
            // UL6: Manage only UL1 & UL2
            require __DIR__ . '/../app/controllers/AdminUsersController.php';
        } else {
            Guard::showAccessDenied('Only administrators can manage users');
        }
        break;

    case 'corrections':
        Guard::requireLogin();
        require __DIR__ . '/../app/controllers/CorrectionController.php';
        break;

    case 'complaints':
        Guard::requireLogin();
        require __DIR__ . '/../app/controllers/ComplaintController.php';
        break;

    case 'interruptions':
        Guard::requireUL2();
        require __DIR__ . '/../app/controllers/InterruptionController.php';
        break;

    case 'reports':
        Guard::requireLogin();
        require __DIR__ . '/../app/controllers/ReportsController.php';
        break;

    case 'analytics':
        Guard::requireLogin();
        $user = Auth::user();
        
        // Only UL4, UL5, UL6, UL7 can access analytics
        if (!in_array($user['role'], ['UL4', 'UL5', 'UL6', 'UL7'])) {
            Guard::showAccessDenied('Analytics access not available for your role');
        }
        
        require __DIR__ . '/../app/controllers/AnalyticsController.php';
        break;

    default:
        // 404 Page
        http_response_code(404);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Page Not Found - LMS</title>
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
                .error-container {
                    background: white;
                    padding: 50px;
                    border-radius: 20px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    text-align: center;
                    max-width: 600px;
                }
                .error-code {
                    font-size: 120px;
                    font-weight: 900;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    -webkit-background-clip: text;
                    -webkit-text-fill-color: transparent;
                    margin-bottom: 20px;
                }
                h1 {
                    color: #2c3e50;
                    margin-bottom: 15px;
                }
                p {
                    color: #6c757d;
                    margin-bottom: 30px;
                    font-size: 18px;
                }
                .btn-home {
                    display: inline-block;
                    padding: 15px 40px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                    text-decoration: none;
                    border-radius: 10px;
                    font-weight: 600;
                    transition: all 0.3s;
                }
                .btn-home:hover {
                    transform: translateY(-3px);
                    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
                }
            </style>
        </head>
        <body>
            <div class="error-container">
                <div class="error-code">404</div>
                <h1>Page Not Found</h1>
                <p>The page you're looking for doesn't exist or has been moved.</p>
                <a href="?page=dashboard" class="btn-home">← Return to Dashboard</a>
            </div>
        </body>
        </html>
        <?php
        exit;
}
