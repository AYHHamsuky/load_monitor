
<?php
/**
 * File: public/login.php
 */

require_once __DIR__ . '/../app/bootstrap.php';

// Already logged in? Redirect
if (Auth::check()) {
    header("Location: index.php?page=dashboard");
    exit;
}

$error = '';
$debug = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $payroll  = trim($_POST['payroll_id'] ?? '');
    $password = $_POST['password'] ?? '';

    $debug[] = "POST received";
    $debug[] = "Payroll: $payroll";
    $debug[] = "Session: " . session_id();
    
    error_log("LOGIN ATTEMPT: $payroll | Session: " . session_id());

    if (empty($payroll) || empty($password)) {
        $error = 'All fields are required';
        $debug[] = "ERROR: Empty fields";
        
    } else {
        
        $loginResult = Auth::attempt($payroll, $password);
        $debug[] = "Login result: " . ($loginResult ? 'SUCCESS' : 'FAILED');
        
        if ($loginResult) {
            
            $debug[] = "Session after: " . session_id();
            $debug[] = "Auth::check(): " . (Auth::check() ? 'TRUE' : 'FALSE');
            $debug[] = "Session data: " . json_encode($_SESSION);
            
            error_log("LOGIN SUCCESS: $payroll");
            foreach ($debug as $d) {
                error_log("  - $d");
            }
            
            // Force output buffer clean
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Redirect
            header("Location: index.php?page=dashboard", true, 302);
            exit();
            
        } else {
            $error = 'Invalid Payroll ID or Password';
            $debug[] = "ERROR: Invalid credentials";
        }
    }
    
    if ($error) {
        error_log("LOGIN ERROR: $error");
        foreach ($debug as $d) {
            error_log("  - $d");
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Kaduna Electric Load Reading Management System – Login</title>
<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}
body {
    min-height: 100vh;
    background: linear-gradient(135deg, #0b3a82 0%, #081a3a 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.login-container {
    width: 100%;
    max-width: 380px;
}
.login-box {
    background: rgba(255, 255, 255, 0.95);
    padding: 32px;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
}
.login-logo {
    display: block;
    margin: 0 auto 12px;
    height: 56px;
    object-fit: contain;
}
.login-box h2 {
    color: #1a5c1a;
    font-size: 15px;
    font-weight: 700;
    margin-bottom: 22px;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 0.4px;
    line-height: 1.4;
}
.form-group {
    margin-bottom: 16px;
}
.form-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 6px;
}
.form-group input {
    width: 100%;
    padding: 12px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s;
}
.form-group input:focus {
    outline: none;
    border-color: #0b3a82;
}
.login-btn {
    width: 100%;
    padding: 12px;
    background: #0b3a82;
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s;
}
.login-btn:hover {
    background: #082f66;
}
.error {
    background: #fee2e2;
    color: #991b1b;
    padding: 12px;
    border-radius: 8px;
    font-size: 13px;
    margin-bottom: 16px;
    text-align: center;
}
.debug-info {
    margin-top: 16px;
    padding: 12px;
    background: #fef3c7;
    border-radius: 8px;
    font-size: 11px;
    font-family: 'Courier New', monospace;
    max-height: 200px;
    overflow-y: auto;
}
.debug-info div {
    margin-bottom: 4px;
    color: #92400e;
}
</style>
</head>
<body>

<div class="login-container">
    <div class="login-box">
        <img src="assets/img/ke_logo.png" alt="Kaduna Electric" class="login-logo">
        <h2>Load Reading Management System</h2>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label for="payroll">Payroll ID</label>
                <input 
                    type="text" 
                    id="payroll"
                    name="payroll_id" 
                    required 
                    autofocus
                    autocomplete="off"
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password"
                    name="password" 
                    required
                    autocomplete="off"
                >
            </div>

            <button type="submit" class="login-btn">Login</button>
        </form>

        <?php if (!empty($debug) && isset($_GET['debug'])): ?>
            <div class="debug-info">
                <?php foreach ($debug as $d): ?>
                    <div><?= htmlspecialchars($d) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if (!isset($_GET['debug'])): ?>
        <div style="text-align: center; margin-top: 12px;">
            <a href="?debug=1" style="color: rgba(255,255,255,0.6); font-size: 12px; text-decoration: none;">
                Enable Debug Mode
            </a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>