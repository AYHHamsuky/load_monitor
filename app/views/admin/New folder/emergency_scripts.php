<?php
// ===========================================
// FILE 1: emergency_disable_logins.php
// Disable all system logins (maintenance mode)
// ===========================================

require_once __DIR__ . '/../app/bootstrap.php';

echo "⚠️ EMERGENCY: Disabling all system logins...\n\n";

$db = Database::getInstance();

try {
    // Disable logins
    $stmt = $db->prepare("
        UPDATE system_config
        SET config_value = 'false',
            updated_by = 'EMERGENCY_SCRIPT',
            updated_at = NOW()
        WHERE config_key = 'login_enabled'
    ");
    
    $stmt->execute();
    
    // Log the action
    AuditLogger::logSystem(
        'EMERGENCY_LOGINS_DISABLED',
        ['reason' => 'Emergency script executed'],
        'CRITICAL'
    );
    
    echo "✅ All logins have been DISABLED\n";
    echo "To re-enable, run: emergency_enable_logins.php\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}


// ===========================================
// FILE 2: emergency_enable_logins.php
// Re-enable system logins
// ===========================================
?>
<?php
require_once __DIR__ . '/../app/bootstrap.php';

echo "Re-enabling system logins...\n\n";

$db = Database::getInstance();

try {
    // Enable logins
    $stmt = $db->prepare("
        UPDATE system_config
        SET config_value = 'true',
            updated_by = 'EMERGENCY_SCRIPT',
            updated_at = NOW()
        WHERE config_key = 'login_enabled'
    ");
    
    $stmt->execute();
    
    // Log the action
    AuditLogger::logSystem(
        'EMERGENCY_LOGINS_ENABLED',
        ['reason' => 'Emergency script executed'],
        'HIGH'
    );
    
    echo "✅ System logins have been ENABLED\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}


// ===========================================
// FILE 3: emergency_logout_all.php
// Force logout ALL users immediately
// ===========================================
?>
<?php
require_once __DIR__ . '/../app/bootstrap.php';

echo "⚠️ EMERGENCY: Forcing logout of ALL users...\n\n";

try {
    $count = SessionManager::logoutAll();
    
    echo "✅ Terminated $count active session(s)\n";
    echo "All users have been logged out.\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}


// ===========================================
// FILE 4: emergency_clear_blacklist.php
// Clear all blacklisted IPs
// ===========================================
?>
<?php
require_once __DIR__ . '/../app/bootstrap.php';

echo "⚠️ EMERGENCY: Clearing IP blacklist...\n\n";

$db = Database::getInstance();

try {
    $stmt = $db->query("
        UPDATE ip_blacklist
        SET is_active = 0
    ");
    
    $count = $stmt->rowCount();
    
    // Log the action
    AuditLogger::logSystem(
        'EMERGENCY_BLACKLIST_CLEARED',
        ['ips_cleared' => $count],
        'HIGH'
    );
    
    echo "✅ Cleared $count blacklisted IP(s)\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}


// ===========================================
// FILE 5: emergency_unlock_user.php
// Unlock a specific user account
// ===========================================
?>
<?php
require_once __DIR__ . '/../app/bootstrap.php';

// Get user ID from command line
$userId = $argv[1] ?? null;

if (!$userId) {
    echo "Usage: php emergency_unlock_user.php [PAYROLL_ID]\n";
    echo "Example: php emergency_unlock_user.php STAFF001\n\n";
    exit(1);
}

echo "⚠️ EMERGENCY: Unlocking user account...\n";
echo "User ID: $userId\n\n";

$db = Database::getInstance();

try {
    $stmt = $db->prepare("
        UPDATE staff_details
        SET failed_login_attempts = 0,
            account_locked_until = NULL
        WHERE payroll_id = ?
    ");
    
    $stmt->execute([$userId]);
    
    if ($stmt->rowCount() > 0) {
        echo "✅ Account unlocked successfully\n";
        
        // Log the action
        AuditLogger::logUserManagement(
            'EMERGENCY_ACCOUNT_UNLOCKED',
            $userId,
            null,
            null,
            ['unlocked_by' => 'EMERGENCY_SCRIPT']
        );
    } else {
        echo "❌ User not found: $userId\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}


// ===========================================
// FILE 6: emergency_reset_password.php
// Reset user password to default
// ===========================================
?>
<?php
require_once __DIR__ . '/../app/bootstrap.php';

// Get user ID from command line
$userId = $argv[1] ?? null;

if (!$userId) {
    echo "Usage: php emergency_reset_password.php [PAYROLL_ID]\n";
    echo "Example: php emergency_reset_password.php STAFF001\n\n";
    exit(1);
}

// Default password: Temp@123456
$defaultPassword = 'Temp@123456';
$passwordHash = password_hash($defaultPassword, PASSWORD_BCRYPT);

echo "⚠️ EMERGENCY: Resetting password...\n";
echo "User ID: $userId\n";
echo "New Password: $defaultPassword\n\n";

$db = Database::getInstance();

try {
    $stmt = $db->prepare("
        UPDATE staff_details
        SET password_hash = ?,
            failed_login_attempts = 0,
            account_locked_until = NULL,
            last_password_change = NOW()
        WHERE payroll_id = ?
    ");
    
    $stmt->execute([$passwordHash, $userId]);
    
    if ($stmt->rowCount() > 0) {
        echo "✅ Password reset successfully\n";
        echo "⚠️ User should change this password immediately!\n\n";
        
        // Log the action
        AuditLogger::logUserManagement(
            'EMERGENCY_PASSWORD_RESET',
            $userId,
            null,
            null,
            ['reset_by' => 'EMERGENCY_SCRIPT']
        );
    } else {
        echo "❌ User not found: $userId\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}


// ===========================================
// FILE 7: emergency_create_admin.php
// Create emergency UL7 admin account
// ===========================================
?>
<?php
require_once __DIR__ . '/../app/bootstrap.php';

echo "⚠️ EMERGENCY: Creating UL7 admin account...\n\n";

$payrollId = 'EMERGENCY_ADMIN';
$password = 'EmergencyAdmin@' . date('Ymd');
$passwordHash = password_hash($password, PASSWORD_BCRYPT);

echo "Payroll ID: $payrollId\n";
echo "Password: $password\n";
echo "⚠️ CHANGE THIS PASSWORD IMMEDIATELY AFTER LOGIN!\n\n";

$db = Database::getInstance();

try {
    // Check if already exists
    $stmt = $db->prepare("SELECT payroll_id FROM staff_details WHERE payroll_id = ?");
    $stmt->execute([$payrollId]);
    
    if ($stmt->fetch()) {
        echo "⚠️ User already exists. Resetting password...\n";
        
        $stmt = $db->prepare("
            UPDATE staff_details
            SET password_hash = ?,
                is_active = 'Yes',
                failed_login_attempts = 0,
                account_locked_until = NULL
            WHERE payroll_id = ?
        ");
        
        $stmt->execute([$passwordHash, $payrollId]);
    } else {
        echo "Creating new UL7 admin account...\n";
        
        $stmt = $db->prepare("
            INSERT INTO staff_details (
                payroll_id, staff_name, role, password_hash, 
                is_active, created_at, last_password_change
            ) VALUES (?, ?, ?, ?, 'Yes', NOW(), NOW())
        ");
        
        $stmt->execute([
            $payrollId,
            'Emergency Administrator',
            'UL7',
            $passwordHash
        ]);
    }
    
    echo "✅ Emergency admin account ready\n\n";
    
    // Log the action
    AuditLogger::logUserManagement(
        'EMERGENCY_ADMIN_CREATED',
        $payrollId,
        null,
        ['role' => 'UL7'],
        ['created_by' => 'EMERGENCY_SCRIPT']
    );
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}


// ===========================================
// USAGE INSTRUCTIONS
// ===========================================
/*

EMERGENCY SCRIPTS - HOW TO USE:

1. Disable All Logins:
   php scripts/emergency_disable_logins.php

2. Enable All Logins:
   php scripts/emergency_enable_logins.php

3. Force Logout All Users:
   php scripts/emergency_logout_all.php

4. Clear IP Blacklist:
   php scripts/emergency_clear_blacklist.php

5. Unlock User Account:
   php scripts/emergency_unlock_user.php STAFF001

6. Reset User Password:
   php scripts/emergency_reset_password.php STAFF001

7. Create Emergency Admin:
   php scripts/emergency_create_admin.php

⚠️ WARNING: These scripts bypass normal security checks.
   Only use in genuine emergency situations.
   All actions are logged in audit_logs table.

*/