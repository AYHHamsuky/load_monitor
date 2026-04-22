-- ========================================
-- UL7 SUPER ADMIN - DATABASE SETUP SCRIPT
-- ========================================
-- Version: 1.0
-- Date: January 24, 2026
-- Run this script to set up all UL7 tables and modifications

-- 1. UPDATE staff_details table to support UL7 role
-- ================================================
ALTER TABLE staff_details 
MODIFY COLUMN role ENUM('UL1', 'UL2', 'UL3', 'UL4', 'UL5', 'UL6', 'UL7') NOT NULL;

-- Add security fields
ALTER TABLE staff_details 
ADD COLUMN two_factor_enabled TINYINT(1) DEFAULT 0 AFTER password_hash,
ADD COLUMN two_factor_secret VARCHAR(255) DEFAULT NULL AFTER two_factor_enabled,
ADD COLUMN last_password_change DATETIME DEFAULT NULL AFTER two_factor_secret,
ADD COLUMN failed_login_attempts INT DEFAULT 0 AFTER last_password_change,
ADD COLUMN account_locked_until DATETIME DEFAULT NULL AFTER failed_login_attempts,
ADD COLUMN last_ip_address VARCHAR(45) DEFAULT NULL AFTER account_locked_until;

-- Create indexes for security monitoring
CREATE INDEX idx_failed_attempts ON staff_details(failed_login_attempts);
CREATE INDEX idx_account_locked ON staff_details(account_locked_until);


-- 2. CREATE audit_logs table
-- ===========================
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `user_id` VARCHAR(20) NOT NULL,
  `action_type` VARCHAR(50) NOT NULL,
  `action_category` ENUM('AUTH', 'USER_MGMT', 'DATA_ENTRY', 'CORRECTION', 'COMPLAINT', 'SYSTEM', 'SECURITY') NOT NULL,
  `module` VARCHAR(50) DEFAULT NULL,
  `record_id` VARCHAR(50) DEFAULT NULL,
  `old_value` TEXT DEFAULT NULL,
  `new_value` TEXT DEFAULT NULL,
  `details` JSON DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('SUCCESS', 'FAILED', 'WARNING') DEFAULT 'SUCCESS',
  `severity` ENUM('LOW', 'MEDIUM', 'HIGH', 'CRITICAL') DEFAULT 'LOW',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_action_category` (`action_category`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_severity` (`severity`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 
COMMENT='Comprehensive audit trail for all system activities';


-- 3. CREATE security_events table
-- ================================
CREATE TABLE IF NOT EXISTS `security_events` (
  `id` BIGINT NOT NULL AUTO_INCREMENT,
  `event_type` ENUM('FAILED_LOGIN', 'BRUTE_FORCE', 'SUSPICIOUS_IP', 'SQL_INJECTION', 'XSS_ATTEMPT', 'UNAUTHORIZED_ACCESS', 'SESSION_HIJACK', 'FILE_TAMPERING', 'OTHER') NOT NULL,
  `severity` ENUM('LOW', 'MEDIUM', 'HIGH', 'CRITICAL') NOT NULL,
  `user_id` VARCHAR(20) DEFAULT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `request_uri` VARCHAR(500) DEFAULT NULL,
  `request_method` VARCHAR(10) DEFAULT NULL,
  `payload` TEXT DEFAULT NULL,
  `threat_score` INT DEFAULT 0,
  `blocked` TINYINT(1) DEFAULT 0,
  `details` JSON DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_event_type` (`event_type`),
  KEY `idx_severity` (`severity`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_threat_score` (`threat_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
COMMENT='Security threat detection and monitoring';


-- 4. CREATE system_health table
-- ==============================
CREATE TABLE IF NOT EXISTS `system_health` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `check_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `cpu_usage` DECIMAL(5,2) DEFAULT NULL,
  `memory_usage` DECIMAL(5,2) DEFAULT NULL,
  `disk_usage` DECIMAL(5,2) DEFAULT NULL,
  `database_size` BIGINT DEFAULT NULL,
  `active_sessions` INT DEFAULT NULL,
  `query_performance` DECIMAL(10,2) DEFAULT NULL,
  `error_count` INT DEFAULT 0,
  `warning_count` INT DEFAULT 0,
  `health_score` INT DEFAULT 100,
  `status` ENUM('HEALTHY', 'WARNING', 'CRITICAL') DEFAULT 'HEALTHY',
  `details` JSON DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_check_time` (`check_time`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
COMMENT='System performance and health metrics';


-- 5. CREATE ip_whitelist table
-- =============================
CREATE TABLE IF NOT EXISTS `ip_whitelist` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `ip_address` VARCHAR(45) NOT NULL,
  `description` VARCHAR(255) DEFAULT NULL,
  `created_by` VARCHAR(20) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ip` (`ip_address`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
COMMENT='Whitelisted IP addresses for secure access';


-- 6. CREATE ip_blacklist table
-- =============================
CREATE TABLE IF NOT EXISTS `ip_blacklist` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `ip_address` VARCHAR(45) NOT NULL,
  `reason` VARCHAR(255) NOT NULL,
  `blocked_by` VARCHAR(20) NOT NULL,
  `blocked_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `block_count` INT DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_ip` (`ip_address`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
COMMENT='Blacklisted IP addresses - security threats';


-- 7. CREATE active_sessions table
-- ================================
CREATE TABLE IF NOT EXISTS `active_sessions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `session_id` VARCHAR(128) NOT NULL,
  `user_id` VARCHAR(20) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `last_activity` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_session` (`session_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
COMMENT='Active user sessions tracking';


-- 8. CREATE file_integrity table
-- ===============================
CREATE TABLE IF NOT EXISTS `file_integrity` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `file_path` VARCHAR(500) NOT NULL,
  `file_hash` VARCHAR(64) NOT NULL,
  `file_size` BIGINT NOT NULL,
  `last_checked` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` ENUM('INTACT', 'MODIFIED', 'MISSING', 'SUSPICIOUS') DEFAULT 'INTACT',
  `checked_by` VARCHAR(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_file` (`file_path`),
  KEY `idx_status` (`status`),
  KEY `idx_last_checked` (`last_checked`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
COMMENT='File integrity monitoring for critical system files';


-- 9. CREATE system_config table (for login control, etc.)
-- ========================================================
CREATE TABLE IF NOT EXISTS `system_config` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `config_key` VARCHAR(100) NOT NULL,
  `config_value` TEXT NOT NULL,
  `config_type` ENUM('BOOLEAN', 'STRING', 'INTEGER', 'JSON') DEFAULT 'STRING',
  `description` VARCHAR(255) DEFAULT NULL,
  `updated_by` VARCHAR(20) DEFAULT NULL,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
COMMENT='System-wide configuration settings';

-- Insert default system configurations
INSERT INTO system_config (config_key, config_value, config_type, description) VALUES
('login_enabled', 'true', 'BOOLEAN', 'Global login enable/disable'),
('maintenance_mode', 'false', 'BOOLEAN', 'System maintenance mode'),
('max_login_attempts', '5', 'INTEGER', 'Maximum failed login attempts before lockout'),
('session_timeout', '480', 'INTEGER', 'Session timeout in minutes (8 hours)'),
('password_expiry_days', '90', 'INTEGER', 'Password expiration period in days');


-- ========================================
-- CREATE SUPER ADMIN TEST USER
-- ========================================
-- IMPORTANT: Only run this ONCE and change the password immediately after first login
-- Password: SuperAdmin@2026 (MUST BE CHANGED)

INSERT INTO staff_details (
  payroll_id, 
  staff_name, 
  role, 
  staff_level,
  phone_no,
  email,
  password_hash,
  is_active,
  last_password_change
) VALUES (
  'SUPERADMIN001',
  'Super Administrator',
  'UL7',
  'Executive',
  '0800000000',
  'superadmin@kadunaelectric.com',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Password: SuperAdmin@2026
  'Yes',
  NOW()
);

-- ========================================
-- VERIFICATION QUERIES
-- ========================================
-- Run these to verify the setup

-- Check staff_details role column
SELECT COLUMN_TYPE 
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'staff_details' 
AND COLUMN_NAME = 'role';

-- Check if UL7 user exists
SELECT payroll_id, staff_name, role, is_active 
FROM staff_details 
WHERE role = 'UL7';

-- Verify all new tables exist
SELECT TABLE_NAME, TABLE_COMMENT 
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN (
  'audit_logs', 
  'security_events', 
  'system_health', 
  'ip_whitelist', 
  'ip_blacklist', 
  'active_sessions', 
  'file_integrity',
  'system_config'
);

-- Check indexes on staff_details
SHOW INDEXES FROM staff_details 
WHERE Key_name IN ('idx_failed_attempts', 'idx_account_locked');

-- ========================================
-- CLEANUP QUERIES (USE WITH CAUTION!)
-- ========================================
-- Uncomment and run ONLY if you need to reset everything

-- DROP TABLE IF EXISTS audit_logs;
-- DROP TABLE IF EXISTS security_events;
-- DROP TABLE IF EXISTS system_health;
-- DROP TABLE IF EXISTS ip_whitelist;
-- DROP TABLE IF EXISTS ip_blacklist;
-- DROP TABLE IF EXISTS active_sessions;
-- DROP TABLE IF EXISTS file_integrity;
-- DROP TABLE IF EXISTS system_config;

-- DELETE FROM staff_details WHERE role = 'UL7';

-- ========================================
-- END OF SETUP SCRIPT
-- ========================================