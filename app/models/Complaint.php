<?php
/**
 * Complaint Model
 * Path: /app/models/Complaint.php
 */

class Complaint {
    
    /**
     * Generate unique complaint reference
     */
    private static function generateReference(): string {
        $date = date('Ymd');
        $random = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        return "CMP-{$date}-{$random}";
    }
    
    /**
     * Log a new complaint
     */
    public static function log(array $data): array {
        $db = Database::connect();
        
        // Validate required fields
        $required = ['feeder_code', 'complaint_type', 'complaint_source', 'complaint_details', 'priority', 'logged_by'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Missing field: {$field}"];
            }
        }
        
        try {
            $complaintRef = self::generateReference();
            
            $stmt = $db->prepare("
                INSERT INTO complaint_log
                (complaint_ref, feeder_code, complaint_type, complaint_source, 
                 affected_area, customer_phone, customer_name, complaint_details, 
                 fault_location, priority, logged_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $complaintRef,
                $data['feeder_code'],
                $data['complaint_type'],
                $data['complaint_source'],
                $data['affected_area'] ?? null,
                $data['customer_phone'] ?? null,
                $data['customer_name'] ?? null,
                $data['complaint_details'],
                $data['fault_location'] ?? null,
                $data['priority'],
                $data['logged_by']
            ]);
            
            return [
                'success' => true, 
                'message' => 'Complaint logged successfully',
                'complaint_ref' => $complaintRef
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get complaints by ISS
     */
    public static function byIss(string $iss_code, ?string $status = null): array {
        $db = Database::connect();
        
        $query = "
            SELECT 
                c.*,
                f.fdr11kv_name,
                f.band,
                s1.staff_name as logger_name,
                s2.staff_name as assignee_name,
                s3.staff_name as resolver_name
            FROM complaint_log c
            INNER JOIN fdr11kv f ON f.fdr11kv_code = c.feeder_code
            LEFT JOIN staff_details s1 ON s1.payroll_id = c.logged_by
            LEFT JOIN staff_details s2 ON s2.payroll_id = c.assigned_to
            LEFT JOIN staff_details s3 ON s3.payroll_id = c.resolved_by
            WHERE f.iss_code = ?
        ";
        
        $params = [$iss_code];
        
        if ($status) {
            $query .= " AND c.status = ?";
            $params[] = $status;
        }
        
        $query .= " ORDER BY c.logged_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get complaint by reference
     */
    public static function getByRef(string $complaint_ref): ?array {
        $db = Database::connect();
        
        $stmt = $db->prepare("
            SELECT 
                c.*,
                f.fdr11kv_name,
                f.band,
                s1.staff_name as logger_name,
                s2.staff_name as assignee_name,
                s3.staff_name as resolver_name
            FROM complaint_log c
            INNER JOIN fdr11kv f ON f.fdr11kv_code = c.feeder_code
            LEFT JOIN staff_details s1 ON s1.payroll_id = c.logged_by
            LEFT JOIN staff_details s2 ON s2.payroll_id = c.assigned_to
            LEFT JOIN staff_details s3 ON s3.payroll_id = c.resolved_by
            WHERE c.complaint_ref = ?
        ");
        
        $stmt->execute([$complaint_ref]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    
    /**
     * Update complaint status
     */
    public static function updateStatus(string $complaint_ref, string $status, ?string $remarks = null): array {
        $db = Database::connect();
        
        try {
            $stmt = $db->prepare("
                UPDATE complaint_log
                SET status = ?
                WHERE complaint_ref = ?
            ");
            
            $stmt->execute([$status, $complaint_ref]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Status updated successfully'];
            } else {
                return ['success' => false, 'message' => 'Complaint not found'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Assign complaint to technician
     */
    public static function assign(string $complaint_ref, string $assignee_id): array {
        $db = Database::connect();
        
        try {
            $stmt = $db->prepare("
                UPDATE complaint_log
                SET status = 'ASSIGNED',
                    assigned_to = ?,
                    assigned_at = NOW()
                WHERE complaint_ref = ?
            ");
            
            $stmt->execute([$assignee_id, $complaint_ref]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Complaint assigned successfully'];
            } else {
                return ['success' => false, 'message' => 'Complaint not found'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Resolve complaint
     */
    public static function resolve(string $complaint_ref, string $resolver_id, string $resolution_details, ?float $downtime_hours = null): array {
        $db = Database::connect();
        
        try {
            $stmt = $db->prepare("
                UPDATE complaint_log
                SET status = 'RESOLVED',
                    resolution_details = ?,
                    resolved_by = ?,
                    resolved_at = NOW(),
                    downtime_hours = ?
                WHERE complaint_ref = ?
            ");
            
            $stmt->execute([$resolution_details, $resolver_id, $downtime_hours, $complaint_ref]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Complaint resolved successfully'];
            } else {
                return ['success' => false, 'message' => 'Complaint not found'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Close complaint
     */
    public static function close(string $complaint_ref, string $closer_id, string $closure_remarks): array {
        $db = Database::connect();
        
        try {
            $stmt = $db->prepare("
                UPDATE complaint_log
                SET status = 'CLOSED',
                    closure_remarks = ?,
                    closed_by = ?,
                    closed_at = NOW()
                WHERE complaint_ref = ?
            ");
            
            $stmt->execute([$closure_remarks, $closer_id, $complaint_ref]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Complaint closed successfully'];
            } else {
                return ['success' => false, 'message' => 'Complaint not found'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get statistics
     */
    public static function getStats(string $iss_code): array {
        $db = Database::connect();
        
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_complaints,
                SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'ASSIGNED' THEN 1 ELSE 0 END) as assigned,
                SUM(CASE WHEN status = 'IN_PROGRESS' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'RESOLVED' THEN 1 ELSE 0 END) as resolved,
                SUM(CASE WHEN status = 'CLOSED' THEN 1 ELSE 0 END) as closed,
                SUM(CASE WHEN priority = 'CRITICAL' THEN 1 ELSE 0 END) as critical,
                AVG(downtime_hours) as avg_downtime
            FROM complaint_log c
            INNER JOIN fdr11kv f ON f.fdr11kv_code = c.feeder_code
            WHERE f.iss_code = ?
        ");
        
        $stmt->execute([$iss_code]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }
}
