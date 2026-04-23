<?php
// public/index.php

// Temporary: enable errors for debug token
if (($_GET['debug'] ?? '') === 'diag2026') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}

require_once __DIR__ . '/../app/bootstrap.php';

// Get the requested page
$page = $_GET['page'] ?? 'login';

// Route handling
switch ($page) {

    case 'login':
        if (Auth::check()) {
            header('Location: ?page=dashboard');
            exit;
        }
        require __DIR__ . '/login.php';
        break;

    case 'logout':
        Auth::logout();
        header('Location: ?page=login');
        exit;

    case 'landing':
        Guard::requireLogin();
        require __DIR__ . '/landing.php';
        break;

    case 'dashboard':
        Guard::requireLogin();
        $user = Auth::user();

        switch ($user['role']) {
            case 'UL1':
                require __DIR__ . '/../app/controllers/DashboardController.php';
                break;
            case 'UL2':
                require __DIR__ . '/../app/controllers/Dashboard33kvController.php';
                break;
            case 'UL3':
                require __DIR__ . '/../app/controllers/AnalystDashboardController.php';
                break;
            case 'UL4':
                require __DIR__ . '/../app/controllers/ManagerDashboardController.php';
                break;
            case 'UL5':
                require __DIR__ . '/../app/controllers/StaffDashboardController.php';
                break;
            case 'UL6':
                require __DIR__ . '/../app/controllers/AdminDashboardController.php';
                break;
            case 'UL8':
                // ADDED: Lead Dispatch Dashboard
                require __DIR__ . '/../app/controllers/LeadDispatchDashboardController.php';
                break;
            default:
                die('Invalid role assigned to your account. Please contact administrator.');
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

    case 'interruptions_11kv':
        Guard::requireUL1();
        require __DIR__ . '/../app/controllers/Interruption11kvController.php';
        break;

    case 'interruptions':
        Guard::requireUL2();
        require __DIR__ . '/../app/controllers/InterruptionController.php';
        break;

    // ✅ ADDED ROUTE (as requested)
    case 'interruption_approvals':
        Guard::requireLogin();
        require __DIR__ . '/../app/controllers/InterruptionApprovalController.php';
        break;

    case 'reports':
        Guard::requireLogin();
        require __DIR__ . '/../app/controllers/ReportsController.php';
        break;

    case 'analytics':
        Guard::requireLogin();
        $user = Auth::user();

        switch ($user['role']) {
            case 'UL3':
            case 'UL4':
            case 'UL5':
                require __DIR__ . '/../app/controllers/AnalyticsController.php';
                break;
            default:
                die('Access denied. Analytics access requires UL3, UL4, or UL5 role.');
        }
        break;

    case 'users':
        Guard::requireAdmin();
        require __DIR__ . '/../app/controllers/AdminUsersController.php';
        break;

    case 'system-config':
        Guard::requireAdmin();
        require __DIR__ . '/../app/controllers/AdminConfigController.php';
        break;

    case 'audit-logs':
        Guard::requireAdmin();
        require __DIR__ . '/../app/controllers/AdminAuditController.php';
        break;

    // ── AJAX endpoint for 11kV load entry (POST only) ─────────────────────────
    case 'load_entry':
        Guard::requireLogin();
        require __DIR__ . '/../app/controllers/LoadEntryController.php';
        break;

    // ── ADDED: 33kV Load Entry Route ─────────────────────────
    case 'load_33kv':
        Guard::requireLogin();
        require __DIR__ . '/../app/controllers/Load33kvController.php';
        break;

    // ── MYTO Allocation Dashboard (UL8 Lead Dispatch) ──────────────────
    case 'myto_dashboard':
        Guard::requireLogin();
        require __DIR__ . '/../app/controllers/MytoDashboardController.php';
        break;
 
      // ── MYTO Entry AJAX endpoint ────────────────────────────────────────
    case 'myto_entry':
          Guard::requireLogin();
          require __DIR__ . '/../app/controllers/MytoEntryController.php';
         break;

    default:
        http_response_code(404);
        echo "Page not found";
        break;
}