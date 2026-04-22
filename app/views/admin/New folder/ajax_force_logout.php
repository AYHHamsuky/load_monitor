<?php
// public/ajax/force_logout.php

/**
 * Force Logout AJAX Handler
 * Allows UL7 to terminate user sessions
 * 
 * @version 1.0
 */

require_once __DIR__ . '/../../app/bootstrap.php';

header('Content-Type: application/json');

// Only UL7 can force logout
if (!Guard::hasRole('UL7')) {
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Only Super Admin can force logout users.'
    ]);
    exit;
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'logout_all':
            // Force logout ALL users
            $count = SessionManager::logoutAll();

            echo json_encode([
                'success' => true,
                'message' => "Successfully terminated {$count} active session(s).",
                'sessions_terminated' => $count
            ]);
            break;

        case 'kill_session':
            // Kill specific session
            $sessionId = $_POST['session_id'] ?? '';

            if (empty($sessionId)) {
                throw new Exception('Session ID is required');
            }

            $success = SessionManager::killSession($sessionId);

            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Session terminated successfully.'
                ]);
            } else {
                throw new Exception('Failed to terminate session');
            }
            break;

        case 'logout_user':
            // Force logout specific user (all their sessions)
            $userId = $_POST['user_id'] ?? '';

            if (empty($userId)) {
                throw new Exception('User ID is required');
            }

            $count = SessionManager::logoutUser($userId);

            echo json_encode([
                'success' => true,
                'message' => "Terminated {$count} session(s) for user {$userId}.",
                'sessions_terminated' => $count
            ]);
            break;

        case 'logout_role':
            // Force logout all users of a specific role
            $role = $_POST['role'] ?? '';

            if (empty($role)) {
                throw new Exception('Role is required');
            }

            if (!in_array($role, ['UL1', 'UL2', 'UL3', 'UL4', 'UL5', 'UL6'])) {
                throw new Exception('Invalid role');
            }

            $count = SessionManager::logoutByRole($role);

            echo json_encode([
                'success' => true,
                'message' => "Terminated {$count} session(s) for role {$role}.",
                'sessions_terminated' => $count
            ]);
            break;

        case 'get_active_sessions':
            // Get list of all active sessions
            $sessions = SessionManager::getAllActiveSessions();

            echo json_encode([
                'success' => true,
                'sessions' => $sessions,
                'count' => count($sessions)
            ]);
            break;

        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}