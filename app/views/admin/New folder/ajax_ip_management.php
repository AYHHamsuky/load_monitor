<?php
// public/ajax/ip_management.php

/**
 * IP Management AJAX Handler
 * Manage IP whitelist and blacklist
 * 
 * @version 1.0
 */

require_once __DIR__ . '/../../app/bootstrap.php';

header('Content-Type: application/json');

// Only UL7 can manage IPs
if (!Guard::hasRole('UL7')) {
    echo json_encode([
        'success' => false,
        'message' => 'Access denied. Only Super Admin can manage IP lists.'
    ]);
    exit;
}

$action = $_POST['action'] ?? '';
$db = Database::getInstance();
$user = Auth::user();

try {
    switch ($action) {
        case 'add_to_blacklist':
            $ipAddress = trim($_POST['ip_address'] ?? '');
            $reason = trim($_POST['reason'] ?? '');
            $duration = (int)($_POST['duration'] ?? 0); // Minutes (0 = permanent)

            if (empty($ipAddress)) {
                throw new Exception('IP address is required');
            }

            if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
                throw new Exception('Invalid IP address format');
            }

            if (empty($reason)) {
                throw new Exception('Reason is required');
            }

            // Check if already blacklisted
            $stmt = $db->prepare("
                SELECT id FROM ip_blacklist 
                WHERE ip_address = ? AND is_active = 1
            ");
            $stmt->execute([$ipAddress]);

            if ($stmt->fetch()) {
                throw new Exception('IP address is already blacklisted');
            }

            // Add to blacklist
            $stmt = $db->prepare("
                INSERT INTO ip_blacklist (
                    ip_address, reason, blocked_by, blocked_at, 
                    expires_at, is_active
                ) VALUES (?, ?, ?, NOW(), ?, 1)
            ");

            $expiresAt = $duration > 0 
                ? date('Y-m-d H:i:s', strtotime("+{$duration} minutes"))
                : null;

            $stmt->execute([
                $ipAddress,
                $reason,
                $user['payroll_id'],
                $expiresAt
            ]);

            AuditLogger::logSecurityAction(
                'IP_BLACKLISTED',
                [
                    'ip_address' => $ipAddress,
                    'reason' => $reason,
                    'duration' => $duration > 0 ? "{$duration} minutes" : 'permanent'
                ]
            );

            echo json_encode([
                'success' => true,
                'message' => "IP address {$ipAddress} has been blacklisted."
            ]);
            break;

        case 'remove_from_blacklist':
            $ipAddress = trim($_POST['ip_address'] ?? '');

            if (empty($ipAddress)) {
                throw new Exception('IP address is required');
            }

            $stmt = $db->prepare("
                UPDATE ip_blacklist
                SET is_active = 0
                WHERE ip_address = ?
            ");

            $stmt->execute([$ipAddress]);

            AuditLogger::logSecurityAction(
                'IP_BLACKLIST_REMOVED',
                ['ip_address' => $ipAddress]
            );

            echo json_encode([
                'success' => true,
                'message' => "IP address {$ipAddress} has been removed from blacklist."
            ]);
            break;

        case 'add_to_whitelist':
            $ipAddress = trim($_POST['ip_address'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $duration = (int)($_POST['duration'] ?? 0); // Days (0 = permanent)

            if (empty($ipAddress)) {
                throw new Exception('IP address is required');
            }

            if (!filter_var($ipAddress, FILTER_VALIDATE_IP)) {
                throw new Exception('Invalid IP address format');
            }

            // Check if already whitelisted
            $stmt = $db->prepare("
                SELECT id FROM ip_whitelist 
                WHERE ip_address = ? AND is_active = 1
            ");
            $stmt->execute([$ipAddress]);

            if ($stmt->fetch()) {
                throw new Exception('IP address is already whitelisted');
            }

            // Add to whitelist
            $stmt = $db->prepare("
                INSERT INTO ip_whitelist (
                    ip_address, description, created_by, 
                    created_at, expires_at, is_active
                ) VALUES (?, ?, ?, NOW(), ?, 1)
            ");

            $expiresAt = $duration > 0 
                ? date('Y-m-d H:i:s', strtotime("+{$duration} days"))
                : null;

            $stmt->execute([
                $ipAddress,
                $description,
                $user['payroll_id'],
                $expiresAt
            ]);

            AuditLogger::logSecurityAction(
                'IP_WHITELISTED',
                [
                    'ip_address' => $ipAddress,
                    'description' => $description,
                    'duration' => $duration > 0 ? "{$duration} days" : 'permanent'
                ]
            );

            echo json_encode([
                'success' => true,
                'message' => "IP address {$ipAddress} has been whitelisted."
            ]);
            break;

        case 'remove_from_whitelist':
            $ipAddress = trim($_POST['ip_address'] ?? '');

            if (empty($ipAddress)) {
                throw new Exception('IP address is required');
            }

            $stmt = $db->prepare("
                UPDATE ip_whitelist
                SET is_active = 0
                WHERE ip_address = ?
            ");

            $stmt->execute([$ipAddress]);

            AuditLogger::logSecurityAction(
                'IP_WHITELIST_REMOVED',
                ['ip_address' => $ipAddress]
            );

            echo json_encode([
                'success' => true,
                'message' => "IP address {$ipAddress} has been removed from whitelist."
            ]);
            break;

        case 'get_blacklist':
            $stmt = $db->query("
                SELECT 
                    b.*,
                    s.staff_name as blocked_by_name
                FROM ip_blacklist b
                LEFT JOIN staff_details s ON b.blocked_by = s.payroll_id
                WHERE b.is_active = 1
                ORDER BY b.blocked_at DESC
            ");

            $blacklist = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'blacklist' => $blacklist,
                'count' => count($blacklist)
            ]);
            break;

        case 'get_whitelist':
            $stmt = $db->query("
                SELECT 
                    w.*,
                    s.staff_name as created_by_name
                FROM ip_whitelist w
                LEFT JOIN staff_details s ON w.created_by = s.payroll_id
                WHERE w.is_active = 1
                ORDER BY w.created_at DESC
            ");

            $whitelist = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode([
                'success' => true,
                'whitelist' => $whitelist,
                'count' => count($whitelist)
            ]);
            break;

        case 'check_ip_status':
            $ipAddress = trim($_POST['ip_address'] ?? '');

            if (empty($ipAddress)) {
                throw new Exception('IP address is required');
            }

            $isBlacklisted = SecurityMonitor::isBlacklisted($ipAddress);
            $isWhitelisted = SecurityMonitor::isWhitelisted($ipAddress);
            $threatScore = SecurityMonitor::calculateThreatScore($ipAddress);

            echo json_encode([
                'success' => true,
                'ip_address' => $ipAddress,
                'is_blacklisted' => $isBlacklisted,
                'is_whitelisted' => $isWhitelisted,
                'threat_score' => $threatScore,
                'status' => $isBlacklisted ? 'blocked' : ($isWhitelisted ? 'trusted' : 'normal')
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