# 🔐 UL7 SUPER ADMIN - QUICK REFERENCE GUIDE

## 🚀 QUICK START (5 Minutes)

### **Step 1: Database Setup**
```bash
# Backup first!
mysqldump -u root -p load_monitor > backup.sql

# Run the setup script (Artifact #1)
mysql -u root -p load_monitor < ul7_database_schema.sql
```

### **Step 2: Upload Files**
```bash
# Core Classes (to /app/core/)
- AuditLogger.php
- SecurityMonitor.php  
- SessionManager.php
- SystemHealth.php
- Guard.php (replace)
- Auth.php (replace)

# Controller (to /app/controllers/)
- SuperAdminDashboardController.php

# AJAX (to /public/ajax/)
- force_logout.php
- run_integrity_check.php
- system_control.php
- ip_management.php
- superadmin_user_management.php

# Views (to /app/views/)
- /superadmin/dashboard.php (create folder first)
- /layout/sidebar.php (replace)

# Routing (to /public/)
- index.php (replace)
```

### **Step 3: Update Bootstrap**
Add to `/app/bootstrap.php`:
```php
require_once __DIR__ . '/core/AuditLogger.php';
require_once __DIR__ . '/core/SecurityMonitor.php';
require_once __DIR__ . '/core/SessionManager.php';
require_once __DIR__ . '/core/SystemHealth.php';

AuditLogger::init();
SecurityMonitor::init();
SessionManager::init();
SystemHealth::init();
```

### **Step 4: Login**
```
URL: http://your-domain/public/login.php
Payroll ID: SUPERADMIN001
Password: SuperAdmin@2026
```

✅ **Done!** You should see the Super Admin Dashboard.

---

## 📋 ALL ARTIFACTS CREATED

| # | Artifact Name | Type | Location | Status |
|---|--------------|------|----------|--------|
| 1 | ul7_database_schema | SQL | Run in MySQL | ✅ Ready |
| 2 | AuditLogger.php | PHP Class | /app/core/ | ✅ Ready |
| 3 | SecurityMonitor.php | PHP Class | /app/core/ | ✅ Ready |
| 4 | SessionManager.php | PHP Class | /app/core/ | ✅ Ready |
| 5 | SystemHealth.php | PHP Class | /app/core/ | ✅ Ready |
| 6 | Guard.php | PHP Class | /app/core/ | ✅ Ready (Replace) |
| 7 | Auth.php | PHP Class | /app/core/ | ✅ Ready (Replace) |
| 8 | SuperAdminDashboardController.php | Controller | /app/controllers/ | ✅ Ready |
| 9 | force_logout.php | AJAX | /public/ajax/ | ✅ Ready |
| 10 | run_integrity_check.php | AJAX | /public/ajax/ | ✅ Ready |
| 11 | system_control.php | AJAX | /public/ajax/ | ✅ Ready |
| 12 | ip_management.php | AJAX | /public/ajax/ | ✅ Ready |
| 13 | superadmin_user_management.php | AJAX | /public/ajax/ | ✅ Ready |
| 14 | dashboard.php | View | /app/views/superadmin/ | ✅ Ready |
| 15 | sidebar.php | Layout | /app/views/layout/ | ✅ Ready (Replace) |
| 16 | index.php | Router | /public/ | ✅ Ready (Replace) |
| 17 | Implementation Checklist | Docs | Reference | ✅ Ready |
| 18 | Quick Reference Guide | Docs | Reference | ✅ Ready |

---

## 🗄️ DATABASE TABLES CREATED

| Table Name | Purpose | Rows (Initial) |
|------------|---------|----------------|
| audit_logs | Comprehensive audit trail | 0 |
| security_events | Security threat tracking | 0 |
| system_health | Performance metrics | 0 |
| active_sessions | Session management | 0 |
| ip_whitelist | Trusted IP addresses | 0 |
| ip_blacklist | Blocked IP addresses | 0 |
| file_integrity | File tampering detection | 0 |
| system_config | System configuration | 5 |

**staff_details table updated:**
- Added: `two_factor_enabled`
- Added: `two_factor_secret`
- Added: `last_password_change`
- Added: `failed_login_attempts`
- Added: `account_locked_until`
- Added: `last_ip_address`
- Modified: `role` enum now includes 'UL7'

---

## 🔑 KEY FUNCTIONS AVAILABLE

### **Dashboard Features:**
- ✅ Security metrics (24-hour overview)
- ✅ System health monitoring
- ✅ Active session management
- ✅ User distribution statistics
- ✅ Recent security events
- ✅ Critical alerts
- ✅ Quick actions panel

### **Security Controls:**
- ✅ Force logout all users
- ✅ Kill individual sessions
- ✅ Enable/disable system logins
- ✅ File integrity checking
- ✅ IP blacklist/whitelist management
- ✅ Brute force detection
- ✅ SQL injection detection
- ✅ XSS attack detection

### **System Management:**
- ✅ View all audit logs
- ✅ Monitor active sessions
- ✅ System health tracking
- ✅ Database optimization
- ✅ Session cleanup
- ✅ Log cleanup

### **User Management (AJAX Ready):**
- ✅ Create users (UL1-UL6)
- ✅ Edit user details
- ✅ Deactivate/delete users
- ✅ Reset passwords
- ✅ Unlock accounts
- ✅ View user activity

---

## 💻 USEFUL SQL QUERIES

### **Check UL7 Status:**
```sql
-- Verify UL7 user exists
SELECT payroll_id, staff_name, role, is_active 
FROM staff_details 
WHERE role = 'UL7';
```

### **View Recent Audit Logs:**
```sql
SELECT 
    a.action_type,
    a.user_id,
    s.staff_name,
    a.created_at,
    a.severity
FROM audit_logs a
LEFT JOIN staff_details s ON a.user_id = s.payroll_id
ORDER BY a.created_at DESC
LIMIT 20;
```

### **Check Active Sessions:**
```sql
SELECT 
    s.*,
    sd.staff_name,
    sd.role
FROM active_sessions s
LEFT JOIN staff_details sd ON s.user_id = sd.payroll_id
WHERE s.last_activity > DATE_SUB(NOW(), INTERVAL 30 MINUTE);
```

### **Security Events (Last 24h):**
```sql
SELECT 
    event_type,
    severity,
    ip_address,
    COUNT(*) as count
FROM security_events
WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY event_type, severity, ip_address
ORDER BY count DESC;
```

### **System Health:**
```sql
SELECT * FROM system_health
ORDER BY check_time DESC
LIMIT 1;
```

### **User Distribution:**
```sql
SELECT 
    role,
    COUNT(*) as total,
    SUM(CASE WHEN is_active = 'Yes' THEN 1 ELSE 0 END) as active,
    SUM(CASE WHEN is_active = 'No' THEN 1 ELSE 0 END) as inactive
FROM staff_details
GROUP BY role
ORDER BY role;
```

---

## 🧪 TESTING COMMANDS

### **Test Core Classes:**
```php
<?php
require_once __DIR__ . '/app/bootstrap.php';

// Test class loading
echo "AuditLogger: " . (class_exists('AuditLogger') ? '✅' : '❌') . "\n";
echo "SecurityMonitor: " . (class_exists('SecurityMonitor') ? '✅' : '❌') . "\n";
echo "SessionManager: " . (class_exists('SessionManager') ? '✅' : '❌') . "\n";
echo "SystemHealth: " . (class_exists('SystemHealth') ? '✅' : '❌') . "\n";

// Test methods
echo "\nAudit Log Test:\n";
AuditLogger::log('TEST_ACTION', 'SYSTEM', 'testing', null, null, null, ['test' => true], 'LOW');
echo "✅ Audit log created\n";

// Test security monitoring
echo "\nSecurity Test:\n";
$isBlacklisted = SecurityMonitor::isBlacklisted('192.168.1.1');
echo "IP Check: " . ($isBlacklisted ? 'Blacklisted' : 'Clear') . "\n";

// Test session manager
echo "\nSession Test:\n";
$count = SessionManager::getActiveSessions();
echo "Active Sessions: $count\n";

// Test system health
echo "\nHealth Test:\n";
$health = SystemHealth::getLatestHealth();
echo "Health Score: " . ($health['health_score'] ?? 'N/A') . "\n";
?>
```

### **Test AJAX with cURL:**
```bash
# Test force logout (replace with your session ID)
curl -X POST http://localhost/public/ajax/force_logout.php \
  -d "action=get_active_sessions" \
  -b "PHPSESSID=abc123"

# Test integrity check
curl -X POST http://localhost/public/ajax/run_integrity_check.php \
  -b "PHPSESSID=abc123"

# Test system control
curl -X POST http://localhost/public/ajax/system_control.php \
  -d "action=get_system_status" \
  -b "PHPSESSID=abc123"
```

---

## 🔧 MAINTENANCE SCRIPTS

### **Create Cleanup Script:**
```php
<?php
// cleanup_maintenance.php
require_once __DIR__ . '/app/bootstrap.php';

echo "Starting maintenance cleanup...\n";

// Clean expired sessions
$sessions = SessionManager::cleanExpiredSessions();
echo "✅ Cleaned $sessions expired sessions\n";

// Clean old audit logs (keep last 365 days)
$audits = AuditLogger::cleanOldLogs(365);
echo "✅ Cleaned $audits old audit logs\n";

// Clean old security events (keep last 90 days)
$events = SecurityMonitor::cleanOldEvents(90);
echo "✅ Cleaned $events old security events\n";

// Clean old health records (keep last 30 days)
$health = SystemHealth::cleanOldRecords(30);
echo "✅ Cleaned $health old health records\n";

echo "\n✅ Maintenance complete!\n";
?>
```

### **Create Health Check Script:**
```php
<?php
// health_check.php
require_once __DIR__ . '/app/bootstrap.php';

echo "Running system health check...\n";

$health = SystemHealth::performHealthCheck();

echo "CPU Usage: " . number_format($health['cpu_usage'], 2) . "%\n";
echo "Memory Usage: " . number_format($health['memory_usage'], 2) . "%\n";
echo "Disk Usage: " . number_format($health['disk_usage'], 2) . "%\n";
echo "Health Score: " . $health['health_score'] . "\n";
echo "Status: " . $health['status'] . "\n";

if ($health['status'] === 'CRITICAL') {
    echo "\n⚠️ CRITICAL: System health is critical!\n";
    // Send email alert here
}

echo "\n✅ Health check complete!\n";
?>
```

---

## 📞 EMERGENCY PROCEDURES

### **Emergency: Force Logout All Users**
```sql
-- Direct database approach if dashboard unavailable
DELETE FROM active_sessions;

-- Verify
SELECT COUNT(*) FROM active_sessions;
-- Should return 0
```

### **Emergency: Reset UL7 Password**
```sql
-- Reset to: Admin@123456
UPDATE staff_details 
SET password_hash = '$2y$10$YourNewHashHere',
    failed_login_attempts = 0,
    account_locked_until = NULL
WHERE role = 'UL7';
```

### **Emergency: Disable All Logins**
```sql
UPDATE system_config
SET config_value = 'false'
WHERE config_key = 'login_enabled';
```

### **Emergency: Re-enable Logins**
```sql
UPDATE system_config
SET config_value = 'true'
WHERE config_key = 'login_enabled';
```

### **Emergency: Clear IP Blacklist**
```sql
UPDATE ip_blacklist
SET is_active = 0;
```

---

## 🎯 COMMON TASKS

### **Add New UL7 Admin:**
```sql
INSERT INTO staff_details (
    payroll_id, staff_name, role, password_hash, is_active, created_at
) VALUES (
    'ADMIN_NEW',
    'New Admin Name',
    'UL7',
    '$2y$10$...',  -- Generate with password_hash()
    'Yes',
    NOW()
);
```

### **View Failed Login Attempts:**
```sql
SELECT 
    user_id,
    COUNT(*) as attempts,
    MAX(created_at) as last_attempt
FROM security_events
WHERE event_type = 'FAILED_LOGIN'
AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY user_id
ORDER BY attempts DESC;
```

### **Unlock Locked Account:**
```sql
UPDATE staff_details
SET failed_login_attempts = 0,
    account_locked_until = NULL
WHERE payroll_id = 'USER_ID';
```

### **View User Activity:**
```sql
SELECT * FROM audit_logs
WHERE user_id = 'USER_ID'
ORDER BY created_at DESC
LIMIT 50;
```

---

## 📊 PERFORMANCE MONITORING

### **Check Database Size:**
```sql
SELECT 
    table_name,
    ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
FROM information_schema.TABLES
WHERE table_schema = 'load_monitor'
ORDER BY (data_length + index_length) DESC;
```

### **Check Table Row Counts:**
```sql
SELECT 
    'audit_logs' as table_name, COUNT(*) as rows FROM audit_logs
UNION ALL
SELECT 'security_events', COUNT(*) FROM security_events
UNION ALL
SELECT 'active_sessions', COUNT(*) FROM active_sessions
UNION ALL
SELECT 'system_health', COUNT(*) FROM system_health;
```

---

## ✅ FINAL CHECKLIST

Before considering UL7 implementation complete:

**Core Functionality:**
- [ ] Can login as UL7
- [ ] Dashboard loads without errors
- [ ] Security metrics display
- [ ] System health shows
- [ ] Can view active sessions
- [ ] File integrity check works
- [ ] Can force logout users
- [ ] Can toggle system logins

**Security:**
- [ ] Audit logs recording
- [ ] Security events detecting
- [ ] Session management working
- [ ] IP management functional
- [ ] Access control enforced

**Performance:**
- [ ] Page loads < 2 seconds
- [ ] No JavaScript errors
- [ ] AJAX calls working
- [ ] Database queries optimized

**Documentation:**
- [ ] Team trained
- [ ] Procedures documented
- [ ] Passwords changed
- [ ] Backup created

---

**🎉 CONGRATULATIONS!**

If all items are checked, your UL7 Super Admin system is **FULLY OPERATIONAL**!

You now have:
- ✅ Enterprise-grade security monitoring
- ✅ Comprehensive audit trail
- ✅ Advanced session management
- ✅ System health tracking
- ✅ Complete access control

**Next:** Build out the remaining placeholder controllers for full functionality!

---

*Quick Reference Guide - Version 1.0*