<?php
// scripts/verify_installation.php
// Run this after installing UL7 to verify everything is working

echo "===========================================\n";
echo "UL7 INSTALLATION VERIFICATION SCRIPT\n";
echo "===========================================\n\n";

$errors = 0;
$warnings = 0;
$success = 0;

// Test 1: Check if bootstrap loads
echo "[1/15] Testing bootstrap file...\n";
try {
    require_once __DIR__ . '/../app/bootstrap.php';
    echo "  ✅ Bootstrap loaded successfully\n\n";
    $success++;
} catch (Exception $e) {
    echo "  ❌ Bootstrap failed: " . $e->getMessage() . "\n\n";
    $errors++;
    die("Cannot proceed without bootstrap.\n");
}

// Test 2: Check database connection
echo "[2/15] Testing database connection...\n";
try {
    $db = Database::getInstance();
    echo "  ✅ Database connected\n\n";
    $success++;
} catch (Exception $e) {
    echo "  ❌ Database connection failed: " . $e->getMessage() . "\n\n";
    $errors++;
}

// Test 3: Check if all core classes exist
echo "[3/15] Checking core classes...\n";
$coreClasses = [
    'Auth',
    'Guard',
    'Database',
    'AuditLogger',
    'SecurityMonitor',
    'SessionManager',
    'SystemHealth'
];

foreach ($coreClasses as $class) {
    if (class_exists($class)) {
        echo "  ✅ $class: Found\n";
        $success++;
    } else {
        echo "  ❌ $class: Missing\n";
        $errors++;
    }
}
echo "\n";

// Test 4: Check database tables
echo "[4/15] Checking database tables...\n";
$requiredTables = [
    'staff_details',
    'audit_logs',
    'security_events',
    'system_health',
    'active_sessions',
    'ip_whitelist',
    'ip_blacklist',
    'file_integrity',
    'system_config'
];

try {
    foreach ($requiredTables as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "  ✅ $table: Exists\n";
            $success++;
        } else {
            echo "  ❌ $table: Missing\n";
            $errors++;
        }
    }
} catch (Exception $e) {
    echo "  ❌ Table check failed: " . $e->getMessage() . "\n";
    $errors++;
}
echo "\n";

// Test 5: Check UL7 role in staff_details
echo "[5/15] Checking UL7 role support...\n";
try {
    $stmt = $db->query("
        SELECT COLUMN_TYPE 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'staff_details' 
        AND COLUMN_NAME = 'role'
    ");
    
    $result = $stmt->fetch();
    if ($result && strpos($result['COLUMN_TYPE'], 'UL7') !== false) {
        echo "  ✅ UL7 role is supported\n\n";
        $success++;
    } else {
        echo "  ❌ UL7 role not found in enum\n\n";
        $errors++;
    }
} catch (Exception $e) {
    echo "  ❌ Role check failed: " . $e->getMessage() . "\n\n";
    $errors++;
}

// Test 6: Check if UL7 user exists
echo "[6/15] Checking for UL7 user...\n";
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM staff_details WHERE role = 'UL7'");
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        echo "  ✅ Found {$result['count']} UL7 user(s)\n\n";
        $success++;
    } else {
        echo "  ⚠️ No UL7 users found (run database setup script)\n\n";
        $warnings++;
    }
} catch (Exception $e) {
    echo "  ❌ UL7 user check failed: " . $e->getMessage() . "\n\n";
    $errors++;
}

// Test 7: Test AuditLogger
echo "[7/15] Testing AuditLogger...\n";
try {
    AuditLogger::log(
        'INSTALLATION_TEST',
        'SYSTEM',
        'installation_verification',
        null,
        null,
        null,
        ['test' => true],
        'LOW'
    );
    
    $stmt = $db->query("
        SELECT COUNT(*) as count FROM audit_logs 
        WHERE action_type = 'INSTALLATION_TEST'
    ");
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        echo "  ✅ AuditLogger working\n\n";
        $success++;
    } else {
        echo "  ❌ AuditLogger not recording\n\n";
        $errors++;
    }
} catch (Exception $e) {
    echo "  ❌ AuditLogger test failed: " . $e->getMessage() . "\n\n";
    $errors++;
}

// Test 8: Test SecurityMonitor
echo "[8/15] Testing SecurityMonitor...\n";
try {
    // Test SQL injection detection
    $isSQLInjection = SecurityMonitor::detectSQLInjection("' OR 1=1 --");
    
    // Test XSS detection
    $isXSS = SecurityMonitor::detectXSS("<script>alert('xss')</script>");
    
    if ($isSQLInjection && $isXSS) {
        echo "  ✅ SecurityMonitor threat detection working\n\n";
        $success++;
    } else {
        echo "  ⚠️ SecurityMonitor may not detect all threats\n\n";
        $warnings++;
    }
} catch (Exception $e) {
    echo "  ❌ SecurityMonitor test failed: " . $e->getMessage() . "\n\n";
    $errors++;
}

// Test 9: Test SessionManager
echo "[9/15] Testing SessionManager...\n";
try {
    $sessionCount = SessionManager::getActiveSessions();
    echo "  ✅ SessionManager working (Active sessions: $sessionCount)\n\n";
    $success++;
} catch (Exception $e) {
    echo "  ❌ SessionManager test failed: " . $e->getMessage() . "\n\n";
    $errors++;
}

// Test 10: Test SystemHealth
echo "[10/15] Testing SystemHealth...\n";
try {
    $health = SystemHealth::performHealthCheck();
    
    if (isset($health['health_score'])) {
        echo "  ✅ SystemHealth working (Score: {$health['health_score']}%)\n\n";
        $success++;
    } else {
        echo "  ❌ SystemHealth not returning proper data\n\n";
        $errors++;
    }
} catch (Exception $e) {
    echo "  ❌ SystemHealth test failed: " . $e->getMessage() . "\n\n";
    $errors++;
}

// Test 11: Check critical files exist
echo "[11/15] Checking critical files...\n";
$criticalFiles = [
    'app/bootstrap.php',
    'app/core/Auth.php',
    'app/core/Guard.php',
    'app/core/AuditLogger.php',
    'app/core/SecurityMonitor.php',
    'app/core/SessionManager.php',
    'app/core/SystemHealth.php',
    'app/controllers/SuperAdminDashboardController.php',
    'public/ajax/force_logout.php',
    'public/ajax/run_integrity_check.php',
    'public/ajax/system_control.php'
];

foreach ($criticalFiles as $file) {
    $fullPath = __DIR__ . '/../' . $file;
    if (file_exists($fullPath)) {
        echo "  ✅ $file\n";
        $success++;
    } else {
        echo "  ❌ $file: Missing\n";
        $errors++;
    }
}
echo "\n";

// Test 12: Check file permissions
echo "[12/15] Checking file permissions...\n";
$logsDir = __DIR__ . '/../logs';
if (is_writable($logsDir)) {
    echo "  ✅ Logs directory is writable\n\n";
    $success++;
} else {
    echo "  ⚠️ Logs directory not writable (chmod 755 recommended)\n\n";
    $warnings++;
}

// Test 13: Check system configuration
echo "[13/15] Checking system configuration...\n";
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM system_config");
    $result = $stmt->fetch();
    
    if ($result['count'] >= 5) {
        echo "  ✅ System configuration loaded ({$result['count']} settings)\n\n";
        $success++;
    } else {
        echo "  ⚠️ Some system configurations may be missing\n\n";
        $warnings++;
    }
} catch (Exception $e) {
    echo "  ❌ System config check failed: " . $e->getMessage() . "\n\n";
    $errors++;
}

// Test 14: Check AJAX endpoints
echo "[14/15] Checking AJAX endpoints...\n";
$ajaxFiles = [
    'force_logout.php',
    'run_integrity_check.php',
    'system_control.php',
    'ip_management.php',
    'superadmin_user_management.php'
];

foreach ($ajaxFiles as $file) {
    $fullPath = __DIR__ . '/../public/ajax/' . $file;
    if (file_exists($fullPath)) {
        echo "  ✅ $file\n";
        $success++;
    } else {
        echo "  ❌ $file: Missing\n";
        $errors++;
    }
}
echo "\n";

// Test 15: Check routing
echo "[15/15] Checking routing setup...\n";
$indexFile = __DIR__ . '/../public/index.php';
if (file_exists($indexFile)) {
    $content = file_get_contents($indexFile);
    if (strpos($content, 'superadmin') !== false && strpos($content, 'UL7') !== false) {
        echo "  ✅ Routing includes UL7 support\n\n";
        $success++;
    } else {
        echo "  ⚠️ Routing may not include UL7 routes\n\n";
        $warnings++;
    }
} else {
    echo "  ❌ index.php not found\n\n";
    $errors++;
}

// Summary
echo "===========================================\n";
echo "VERIFICATION SUMMARY\n";
echo "===========================================\n";
echo "✅ Passed: $success\n";
echo "⚠️ Warnings: $warnings\n";
echo "❌ Errors: $errors\n";
echo "===========================================\n\n";

if ($errors === 0 && $warnings === 0) {
    echo "🎉 EXCELLENT! Installation is complete and verified.\n";
    echo "You can now login with UL7 credentials.\n\n";
    exit(0);
} elseif ($errors === 0) {
    echo "✅ GOOD! Installation is functional with minor warnings.\n";
    echo "Review warnings above and fix if necessary.\n\n";
    exit(0);
} else {
    echo "❌ ISSUES FOUND! Please fix the errors above before using the system.\n\n";
    exit(1);
}