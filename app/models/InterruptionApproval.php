<?php
/**
 * Interruption Approval Model
 * Path: /app/models/InterruptionApproval.php
 * 
 * Handles approval workflow for interruptions requiring UL3 and UL4 approval
 */

class InterruptionApproval {
    
    /**
     * Create approval request for new interruption
     */
    public static function createApprovalRequest(array $data): array {
        $db = Database::connect();
        
        $required = ['interruption_id', 'interruption_type', 'requester_id', 'requester_name'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Missing field: {$field}"];
            }
        }
        
        try {
            $stmt = $db->prepare("
                INSERT INTO interruption_approvals
                (interruption_id, interruption_type, requester_id, requester_name, status)
                VALUES (?, ?, ?, ?, 'PENDING')
            ");
            
            $stmt->execute([
                $data['interruption_id'],
                $data['interruption_type'],
                $data['requester_id'],
                $data['requester_name']
            ]);
            
            $approvalId = $db->lastInsertId();
            
            return [
                'success' => true,
                'message' => 'Approval request created. Awaiting UL3 concurrence.',
                'approval_id' => $approvalId
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get pending approvals for UL3 (Analyst)
     */
    public static function getPendingForAnalyst(): array {
        $db = Database::connect();
        
        $stmt = $db->query("
            SELECT 
                ia.*,
                CASE 
                    WHEN ia.interruption_type = '33kV' THEN f33.fdr33kv_name
                    ELSE f11.fdr11kv_name
                END as feeder_name,
                CASE 
                    WHEN ia.interruption_type = '33kV' THEN i33.datetime_out
                    ELSE i11.datetime_out
                END as datetime_out,
                CASE 
                    WHEN ia.interruption_type = '33kV' THEN i33.datetime_in
                    ELSE i11.datetime_in
                END as datetime_in,
                CASE 
                    WHEN ia.interruption_type = '33kV' THEN i33.interruption_code
                    ELSE i11.interruption_code
                END as interruption_code,
                CASE 
                    WHEN ia.interruption_type = '33kV' THEN i33.reason_for_interruption
                    ELSE i11.reason_for_interruption
                END as reason_for_interruption,
                CASE 
                    WHEN ia.interruption_type = '33kV' THEN i33.load_loss
                    ELSE i11.load_loss
                END as load_loss
            FROM interruption_approvals ia
            LEFT JOIN interruptions i33 ON ia.interruption_id = i33.id AND ia.interruption_type = '33kV'
            LEFT JOIN fdr33kv f33 ON i33.fdr33kv_code = f33.fdr33kv_code
            LEFT JOIN interruptions_11kv i11 ON ia.interruption_id = i11.id AND ia.interruption_type = '11kV'
            LEFT JOIN fdr11kv f11 ON i11.fdr11kv_code = f11.fdr11kv_code
            WHERE ia.status = 'PENDING'
            ORDER BY ia.requested_at ASC
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get analyst-approved requests for UL4 (Manager)
     */
    public static function getAnalystApprovedForManager(): array {
        $db = Database::connect();
        
        $stmt = $db->query("
            SELECT 
                ia.*,
                CASE 
                    WHEN ia.interruption_type = '33kV' THEN f33.fdr33kv_name
                    ELSE f11.fdr11kv_name
                END as feeder_name,
                CASE 
                    WHEN ia.interruption_type = '33kV' THEN i33.datetime_out
                    ELSE i11.datetime_out
                END as datetime_out,
                CASE 
                    WHEN ia.interruption_type = '33kV' THEN i33.datetime_in
                    ELSE i11.datetime_in
                END as datetime_in,
                CASE 
                    WHEN ia.interruption_type = '33kV' THEN i33.interruption_code
                    ELSE i11.interruption_code
                END as interruption_code,
                CASE 
                    WHEN ia.interruption_type = '33kV' THEN i33.reason_for_interruption
                    ELSE i11.reason_for_interruption
                END as reason_for_interruption,
                CASE 
                    WHEN ia.interruption_type = '33kV' THEN i33.load_loss
                    ELSE i11.load_loss
                END as load_loss
            FROM interruption_approvals ia
            LEFT JOIN interruptions i33 ON ia.interruption_id = i33.id AND ia.interruption_type = '33kV'
            LEFT JOIN fdr33kv f33 ON i33.fdr33kv_code = f33.fdr33kv_code
            LEFT JOIN interruptions_11kv i11 ON ia.interruption_id = i11.id AND ia.interruption_type = '11kV'
            LEFT JOIN fdr11kv f11 ON i11.fdr11kv_code = f11.fdr11kv_code
            WHERE ia.status = 'ANALYST_APPROVED'
            ORDER BY ia.analyst_action_at ASC
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Analyst action (UL3)
     */
    public static function analystAction(int $approvalId, string $action, string $analystId, string $analystName, ?string $remarks = null): array {
        $db = Database::connect();
        
        if (!in_array($action, ['APPROVED', 'REJECTED'])) {
            return ['success' => false, 'message' => 'Invalid action'];
        }
        
        try {
            $db->beginTransaction();
            
            // Update approval record
            $newStatus = $action === 'APPROVED' ? 'ANALYST_APPROVED' : 'REJECTED';
            
            $stmt = $db->prepare("
                UPDATE interruption_approvals
                SET analyst_id = ?,
                    analyst_name = ?,
                    analyst_remarks = ?,
                    analyst_action = ?,
                    analyst_action_at = NOW(),
                    status = ?
                WHERE id = ? AND status = 'PENDING'
            ");
            
            $stmt->execute([
                $analystId,
                $analystName,
                $remarks,
                $action,
                $newStatus,
                $approvalId
            ]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Approval request not found or already processed');
            }
            
            // Get approval details to update interruption table
            $approval = self::getById($approvalId);
            
            // Update interruption table status
            if ($action === 'REJECTED') {
                $tableName = $approval['interruption_type'] === '33kV' ? 'interruptions' : 'interruptions_11kv';
                
                $updateStmt = $db->prepare("
                    UPDATE {$tableName}
                    SET approval_status = 'REJECTED'
                    WHERE id = ?
                ");
                $updateStmt->execute([$approval['interruption_id']]);
            } else {
                // Update to ANALYST_APPROVED
                $tableName = $approval['interruption_type'] === '33kV' ? 'interruptions' : 'interruptions_11kv';
                
                $updateStmt = $db->prepare("
                    UPDATE {$tableName}
                    SET approval_status = 'ANALYST_APPROVED'
                    WHERE id = ?
                ");
                $updateStmt->execute([$approval['interruption_id']]);
            }
            
            $db->commit();
            
            $message = $action === 'APPROVED' 
                ? 'Concurrence successful. Request sent to Manager for final approval.'
                : 'Request rejected successfully.';
            
            return ['success' => true, 'message' => $message];
            
        } catch (Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Manager final approval (UL4)
     */
    public static function managerAction(int $approvalId, string $action, string $managerId, string $managerName, ?string $remarks = null): array {
        $db = Database::connect();
        
        if (!in_array($action, ['APPROVED', 'REJECTED'])) {
            return ['success' => false, 'message' => 'Invalid action'];
        }
        
        try {
            $db->beginTransaction();
            
            // Update approval record
            $newStatus = $action === 'APPROVED' ? 'APPROVED' : 'REJECTED';
            
            $stmt = $db->prepare("
                UPDATE interruption_approvals
                SET manager_id = ?,
                    manager_name = ?,
                    manager_remarks = ?,
                    manager_action = ?,
                    manager_action_at = NOW(),
                    status = ?
                WHERE id = ? AND status = 'ANALYST_APPROVED'
            ");
            
            $stmt->execute([
                $managerId,
                $managerName,
                $remarks,
                $action,
                $newStatus,
                $approvalId
            ]);
            
            if ($stmt->rowCount() === 0) {
                throw new Exception('Approval request not found or already processed');
            }
            
            // Get approval details
            $approval = self::getById($approvalId);
            
            // Update interruption table status
            $tableName = $approval['interruption_type'] === '33kV' ? 'interruptions' : 'interruptions_11kv';
            
            $updateStmt = $db->prepare("
                UPDATE {$tableName}
                SET approval_status = ?
                WHERE id = ?
            ");
            $updateStmt->execute([$newStatus, $approval['interruption_id']]);
            
            $db->commit();
            
            $message = $action === 'APPROVED' 
                ? 'Interruption approved successfully. Record is now active.'
                : 'Interruption rejected.';
            
            return ['success' => true, 'message' => $message];
            
        } catch (Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get approval by ID
     */
    public static function getById(int $id): ?array {
        $db = Database::connect();
        
        $stmt = $db->prepare("SELECT * FROM interruption_approvals WHERE id = ?");
        $stmt->execute([$id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Get my pending approval requests
     */
    public static function myRequests(string $userId): array {
        $db = Database::connect();
        
        $stmt = $db->prepare("
            SELECT 
                ia.*,
                CASE 
                    WHEN ia.interruption_type = '33kV' THEN f33.fdr33kv_name
                    ELSE f11.fdr11kv_name
                END as feeder_name,
                CASE 
                    WHEN ia.interruption_type = '33kV' THEN i33.datetime_out
                    ELSE i11.datetime_out
                END as datetime_out,
                CASE 
                    WHEN ia.interruption_type = '33kV' THEN i33.interruption_code
                    ELSE i11.interruption_code
                END as interruption_code
            FROM interruption_approvals ia
            LEFT JOIN interruptions i33 ON ia.interruption_id = i33.id AND ia.interruption_type = '33kV'
            LEFT JOIN fdr33kv f33 ON i33.fdr33kv_code = f33.fdr33kv_code
            LEFT JOIN interruptions_11kv i11 ON ia.interruption_id = i11.id AND ia.interruption_type = '11kV'
            LEFT JOIN fdr11kv f11 ON i11.fdr11kv_code = f11.fdr11kv_code
            WHERE ia.requester_id = ?
            ORDER BY ia.requested_at DESC
        ");
        
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get count of pending approvals for notifications
     */
    public static function getPendingCount(string $role): int {
        $db = Database::connect();
        
        if ($role === 'UL3') {
            $stmt = $db->query("SELECT COUNT(*) FROM interruption_approvals WHERE status = 'PENDING'");
        } elseif ($role === 'UL4') {
            $stmt = $db->query("SELECT COUNT(*) FROM interruption_approvals WHERE status = 'ANALYST_APPROVED'");
        } else {
            return 0;
        }
        
        return (int) $stmt->fetchColumn();
    }
}
