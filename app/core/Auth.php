<?php
// app/core/Auth.php - FIXED VERSION

class Auth {
    
    public static function attempt($payroll_id, $password) {
        $db = Database::connect();
        
        $stmt = $db->prepare("
            SELECT 
                payroll_id,
                staff_name,
                role,
                iss_code,
                assigned_33kv_code,
                password_hash,
                is_active,
                staff_level,
                phone,
                email
            FROM staff_details 
            WHERE payroll_id = ? AND is_active = 'Yes'
        ");
        
        $stmt->execute([$payroll_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return false;
        }
        
        if (!password_verify($password, $user['password_hash'])) {
            return false;
        }
        
        // Store user in session
        $_SESSION['user'] = $user;
        $_SESSION['user_id'] = $user['payroll_id'];
        $_SESSION['role'] = $user['role'];
        
        return true;
    }
    
    public static function user() {
        return $_SESSION['user'] ?? null;
    }
    
    public static function check() {
        return isset($_SESSION['user']);
    }
    
    public static function logout() {
        session_destroy();
    }
    
    /**
     * Get user's assigned feeders based on role
     * UL1: All 11kV feeders for their ISS
     * UL2: ALL 33kV feeders (selected via TS dropdown)
     * UL3-UL6: NULL (no feeder assignment)
     */
    public static function getAssignedFeeders() {
        $user = self::user();
        if (!$user) return [];
        
        // FIXED: Changed from getInstance() to connect()
        $db = Database::connect();
        
        switch ($user['role']) {
            case 'UL1':
                // Get all 11kV feeders for this ISS
                $stmt = $db->prepare("
                    SELECT fdr11kv_code, fdr11kv_name 
                    FROM fdr11kv 
                    WHERE iss_code = ?
                    ORDER BY fdr11kv_name
                ");
                $stmt->execute([$user['iss_code']]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            case 'UL2':
                // Get ALL 33kV feeders (no restriction)
                $stmt = $db->prepare("
                    SELECT fdr11kv_code AS feeder_code, fdr11kv_name AS feeder_name, ts_code
                    FROM fdr33kv 
                    ORDER BY fdr11kv_name
                ");
                $stmt->execute();
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
                
            default:
                // UL3-UL6: No feeder assignment
                return [];
        }
    }
    
    /**
     * Check if user has feeder-level access
     */
    public static function hasFeederAccess() {
        $user = self::user();
        return in_array($user['role'], ['UL1', 'UL2']);
    }
    
    /**
     * Check if user has system-wide access
     */
    public static function hasSystemWideAccess() {
        $user = self::user();
        return in_array($user['role'], ['UL3', 'UL4', 'UL5', 'UL6', 'UL7', 'UL8']);
    }
}
