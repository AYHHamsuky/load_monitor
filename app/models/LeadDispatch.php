<?php
/**
 * Lead Dispatch Model
 * Path: /app/models/LeadDispatch.php
 * 
 * Handles all data operations for UL8 Lead Dispatch role
 */

class LeadDispatch {
    
    /**
     * Get system-wide statistics for a specific date
     */
    public static function getSystemStats(string $date): array {
        $db = Database::connect();
        
        // 11kV Statistics
        $stmt = $db->prepare("
            SELECT 
                COUNT(DISTINCT f.fdr11kv_code) as total_feeders,
                COUNT(DISTINCT f.iss_code) as total_iss,
                COALESCE(SUM(d.load_read), 0) as total_load,
                COALESCE(AVG(d.load_read), 0) as avg_load,
                COALESCE(MAX(d.load_read), 0) as peak_load,
                COUNT(CASE WHEN d.load_read > 0 THEN 1 END) as supply_hours,
                COUNT(CASE WHEN d.fault_code IS NOT NULL AND d.fault_code != '' THEN 1 END) as fault_hours,
                COUNT(d.id) as total_entries
            FROM fdr11kv f
            LEFT JOIN fdr11kv_data d ON d.Fdr11kv_code = f.fdr11kv_code AND d.entry_date = ?
        ");
        $stmt->execute([$date]);
        $stats_11kv = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 33kV Statistics
        $stmt = $db->prepare("
            SELECT 
                COUNT(DISTINCT f.fdr33kv_code) as total_feeders,
                COUNT(DISTINCT f.ts_code) as total_ts,
                COALESCE(SUM(d.load_read), 0) as total_load,
                COALESCE(AVG(d.load_read), 0) as avg_load,
                COALESCE(MAX(d.load_read), 0) as peak_load,
                COUNT(CASE WHEN d.load_read > 0 THEN 1 END) as supply_hours,
                COUNT(CASE WHEN d.fault_code IS NOT NULL AND d.fault_code != '' THEN 1 END) as fault_hours,
                COUNT(d.id) as total_entries
            FROM fdr33kv f
            LEFT JOIN fdr33kv_data d ON d.Fdr33kv_code = f.fdr33kv_code AND d.entry_date = ?
        ");
        $stmt->execute([$date]);
        $stats_33kv = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            '11kv' => $stats_11kv,
            '33kv' => $stats_33kv,
            'combined' => [
                'total_feeders' => $stats_11kv['total_feeders'] + $stats_33kv['total_feeders'],
                'total_locations' => $stats_11kv['total_iss'] + $stats_33kv['total_ts'],
                'total_load' => $stats_11kv['total_load'] + $stats_33kv['total_load'],
                'avg_load' => ($stats_11kv['avg_load'] + $stats_33kv['avg_load']) / 2,
                'peak_load' => max($stats_11kv['peak_load'], $stats_33kv['peak_load']),
                'supply_hours' => $stats_11kv['supply_hours'] + $stats_33kv['supply_hours'],
                'fault_hours' => $stats_11kv['fault_hours'] + $stats_33kv['fault_hours'],
                'completion_rate' => (($stats_11kv['total_entries'] + $stats_33kv['total_entries']) / 
                                     (($stats_11kv['total_feeders'] + $stats_33kv['total_feeders']) * 24)) * 100
            ]
        ];
    }
    
    /**
     * Get staff on duty for a specific date
     */
    public static function getStaffOnDuty(string $date): array {
        $db = Database::connect();
        
        $stmt = $db->prepare("
            SELECT DISTINCT
                s.payroll_id,
                s.staff_name,
                s.role,
                s.iss_code,
                iss.iss_name,
                f33.fdr33kv_name,
                ts.station_name,
                al.login_time,
                al.ip_address,
                CASE 
                    WHEN s.role = 'UL1' THEN '11kV Data Entry'
                    WHEN s.role = 'UL2' THEN '33kV Data Entry'
                    WHEN s.role = 'UL3' THEN 'Analyst'
                    WHEN s.role = 'UL4' THEN 'Manager'
                    WHEN s.role = 'UL5' THEN 'Senior Manager'
                    WHEN s.role = 'UL6' THEN 'Administrator'
                    WHEN s.role = 'UL8' THEN 'Lead Dispatch'
                    ELSE s.role
                END as role_name,
                CASE 
                    WHEN s.role = 'UL1' THEN iss.iss_name
                    WHEN s.role = 'UL2' THEN ts.station_name
                    ELSE 'System Wide'
                END as assigned_location
            FROM staff_details s
            LEFT JOIN injection_substations iss ON iss.iss_code = s.iss_code
            LEFT JOIN fdr33kv f33 ON f33.fdr33kv_code = s.assigned_33kv_code
            LEFT JOIN transmission_stations ts ON ts.ts_code = f33.ts_code
            INNER JOIN activity_logs al ON al.payroll_id = s.payroll_id 
                AND DATE(al.login_time) = ?
                AND al.action = 'LOGIN'
            ORDER BY al.login_time DESC
        ");
        $stmt->execute([$date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get interruptions for a specific date
     */
    public static function getInterruptions(string $date): array {
        $db = Database::connect();
        
        $stmt = $db->prepare("
            SELECT 
                d.entry_hour,
                d.fault_code,
                d.fault_remark,
                f.fdr11kv_code as feeder_code,
                f.fdr11kv_name as feeder_name,
                iss.iss_name as location_name,
                '11kV' as voltage_level
            FROM fdr11kv_data d
            INNER JOIN fdr11kv f ON f.fdr11kv_code = d.Fdr11kv_code
            INNER JOIN injection_substations iss ON iss.iss_code = f.iss_code
            WHERE d.entry_date = ? 
              AND d.fault_code IS NOT NULL 
              AND d.fault_code != ''
            
            UNION ALL
            
            SELECT 
                d.entry_hour,
                d.fault_code,
                d.fault_remark,
                f.fdr33kv_code as feeder_code,
                f.fdr33kv_name as feeder_name,
                ts.station_name as location_name,
                '33kV' as voltage_level
            FROM fdr33kv_data d
            INNER JOIN fdr33kv f ON f.fdr33kv_code = d.Fdr33kv_code
            INNER JOIN transmission_stations ts ON ts.ts_code = f.ts_code
            WHERE d.entry_date = ? 
              AND d.fault_code IS NOT NULL 
              AND d.fault_code != ''
            
            ORDER BY entry_hour DESC
        ");
        $stmt->execute([$date, $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get hourly load trend for a specific date
     */
    public static function getHourlyTrend(string $date): array {
        $db = Database::connect();
        
        $trend = [];
        for ($hour = 1; $hour <= 24; $hour++) {
            $stmt = $db->prepare("
                SELECT 
                    ? as hour,
                    COALESCE(SUM(d11.load_read), 0) as load_11kv,
                    COALESCE(SUM(d33.load_read), 0) as load_33kv,
                    COALESCE(SUM(d11.load_read), 0) + COALESCE(SUM(d33.load_read), 0) as total_load
                FROM (SELECT 1) dummy
                LEFT JOIN fdr11kv_data d11 ON d11.entry_hour = ? AND d11.entry_date = ?
                LEFT JOIN fdr33kv_data d33 ON d33.entry_hour = ? AND d33.entry_date = ?
            ");
            $stmt->execute([$hour, $hour, $date, $hour, $date]);
            $trend[] = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        return $trend;
    }
    
    /**
     * Get regional statistics
     */
    public static function getRegionalStats(string $date): array {
        $db = Database::connect();
        
        $stmt = $db->prepare("
            SELECT 
                r.region_name,
                COUNT(DISTINCT iss.iss_code) as total_iss,
                COUNT(DISTINCT ts.ts_code) as total_ts,
                COUNT(DISTINCT f11.fdr11kv_code) as total_11kv_feeders,
                COUNT(DISTINCT f33.fdr33kv_code) as total_33kv_feeders,
                COALESCE(SUM(d11.load_read), 0) as load_11kv,
                COALESCE(SUM(d33.load_read), 0) as load_33kv,
                COALESCE(SUM(d11.load_read), 0) + COALESCE(SUM(d33.load_read), 0) as total_load
            FROM regions r
            LEFT JOIN injection_substations iss ON iss.region_code = r.region_code
            LEFT JOIN fdr11kv f11 ON f11.iss_code = iss.iss_code
            LEFT JOIN fdr11kv_data d11 ON d11.Fdr11kv_code = f11.fdr11kv_code AND d11.entry_date = ?
            LEFT JOIN transmission_stations ts ON ts.region_code = r.region_code
            LEFT JOIN fdr33kv f33 ON f33.ts_code = ts.ts_code
            LEFT JOIN fdr33kv_data d33 ON d33.Fdr33kv_code = f33.fdr33kv_code AND d33.entry_date = ?
            GROUP BY r.region_code, r.region_name
            HAVING total_load > 0
            ORDER BY total_load DESC
        ");
        $stmt->execute([$date, $date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get top performing feeders by load
     */
    public static function getTopFeeders(string $date, int $limit = 10): array {
        $db = Database::connect();
        
        $stmt = $db->prepare("
            SELECT 
                f.fdr11kv_name as feeder_name,
                f.fdr11kv_code as feeder_code,
                iss.iss_name as location,
                SUM(d.load_read) as total_load,
                AVG(d.load_read) as avg_load,
                MAX(d.load_read) as peak_load,
                '11kV' as voltage_level
            FROM fdr11kv_data d
            INNER JOIN fdr11kv f ON f.fdr11kv_code = d.Fdr11kv_code
            INNER JOIN injection_substations iss ON iss.iss_code = f.iss_code
            WHERE d.entry_date = ?
            GROUP BY f.fdr11kv_code, f.fdr11kv_name, iss.iss_name
            
            UNION ALL
            
            SELECT 
                f.fdr33kv_name as feeder_name,
                f.fdr33kv_code as feeder_code,
                ts.station_name as location,
                SUM(d.load_read) as total_load,
                AVG(d.load_read) as avg_load,
                MAX(d.load_read) as peak_load,
                '33kV' as voltage_level
            FROM fdr33kv_data d
            INNER JOIN fdr33kv f ON f.fdr33kv_code = d.Fdr33kv_code
            INNER JOIN transmission_stations ts ON ts.ts_code = f.ts_code
            WHERE d.entry_date = ?
            GROUP BY f.fdr33kv_code, f.fdr33kv_name, ts.station_name
            
            ORDER BY total_load DESC
            LIMIT ?
        ");
        $stmt->execute([$date, $date, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get fault type statistics
     */
    public static function getFaultStats(string $date): array {
        $db = Database::connect();
        
        $fault_codes = [
            'FO' => 'Forced Outage',
            'BF' => 'Breaker Failure',
            'OS' => 'Out of Service',
            'DOff' => 'De-energized Off',
            'MVR' => 'Manual Voltage Reduction',
            'OL' => 'Overload',
            'UV' => 'Under Voltage',
            'OV' => 'Over Voltage',
            'SC' => 'Short Circuit',
            'GF' => 'Ground Fault',
            'TF' => 'Transformer Fault',
            'LO' => 'Line Outage',
            'EQ' => 'Equipment Failure',
            'MT' => 'Maintenance',
            'WE' => 'Weather Related',
            'OT' => 'Other'
        ];
        
        $stmt = $db->prepare("
            SELECT 
                fault_code,
                COUNT(*) as count,
                '11kV' as voltage_level
            FROM fdr11kv_data
            WHERE entry_date = ? AND fault_code IS NOT NULL AND fault_code != ''
            GROUP BY fault_code
            
            UNION ALL
            
            SELECT 
                fault_code,
                COUNT(*) as count,
                '33kV' as voltage_level
            FROM fdr33kv_data
            WHERE entry_date = ? AND fault_code IS NOT NULL AND fault_code != ''
            GROUP BY fault_code
        ");
        $stmt->execute([$date, $date]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organize by fault code
        $stats = [];
        foreach ($results as $row) {
            if (!isset($stats[$row['fault_code']])) {
                $stats[$row['fault_code']] = [
                    'code' => $row['fault_code'],
                    'description' => $fault_codes[$row['fault_code']] ?? 'Unknown',
                    'total' => 0,
                    '11kv' => 0,
                    '33kv' => 0
                ];
            }
            $stats[$row['fault_code']]['total'] += $row['count'];
            if ($row['voltage_level'] === '11kV') {
                $stats[$row['fault_code']]['11kv'] += $row['count'];
            } else {
                $stats[$row['fault_code']]['33kv'] += $row['count'];
            }
        }
        
        // Sort by total count descending
        uasort($stats, function($a, $b) {
            return $b['total'] - $a['total'];
        });
        
        return array_values($stats);
    }
    
    /**
     * Get activity summary for staff
     */
    public static function getStaffActivity(string $date): array {
        $db = Database::connect();
        
        $stmt = $db->prepare("
            SELECT 
                s.payroll_id,
                s.staff_name,
                s.role,
                COUNT(CASE WHEN al.action = 'DATA_ENTRY' THEN 1 END) as data_entries,
                COUNT(CASE WHEN al.action = 'CORRECTION_REQUEST' THEN 1 END) as corrections,
                COUNT(*) as total_activities,
                MAX(al.action_time) as last_activity
            FROM staff_details s
            INNER JOIN activity_logs al ON al.payroll_id = s.payroll_id
            WHERE DATE(al.action_time) = ?
            GROUP BY s.payroll_id, s.staff_name, s.role
            ORDER BY total_activities DESC
        ");
        $stmt->execute([$date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
