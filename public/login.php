
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
*, *::before, *::after {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
}
body {
    min-height: 100vh;
    background: linear-gradient(135deg, #004B23 0%, #006b30 55%, #008000 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}
.login-container {
    width: 100%;
    max-width: 400px;
}
.login-box {
    background: #ffffff;
    padding: 36px 32px;
    border-radius: 16px;
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.32);
    border-top: 5px solid #6CAE27;
}
.login-logo {
    display: block;
    margin: 0 auto 14px;
    height: 60px;
    object-fit: contain;
}
.login-box h2 {
    color: #004B23;
    font-size: 13px;
    font-weight: 800;
    margin-bottom: 24px;
    text-align: center;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    line-height: 1.45;
}
.form-group {
    margin-bottom: 16px;
}
.form-group label {
    display: block;
    font-size: 11px;
    font-weight: 800;
    color: #004B23;
    margin-bottom: 6px;
    text-transform: uppercase;
    letter-spacing: 0.4px;
}
.form-group input {
    width: 100%;
    padding: 12px 14px;
    border: 1.5px solid #d4eabf;
    border-radius: 8px;
    font-size: 14px;
    color: #1a2e1a;
    background: #f8fdf4;
    transition: border-color 0.2s, box-shadow 0.2s;
    box-sizing: border-box;
}
.form-group input:focus {
    outline: none;
    border-color: #6CAE27;
    box-shadow: 0 0 0 3px rgba(108,174,39,0.18);
    background: #fff;
}
.login-btn {
    width: 100%;
    padding: 13px;
    background: #004B23;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 800;
    cursor: pointer;
    letter-spacing: 0.4px;
    transition: background 0.2s, transform 0.1s;
    margin-top: 6px;
}
.login-btn:hover  { background: #008000; }
.login-btn:active { transform: scale(0.98); }
.error {
    background: #fde8e8;
    color: #9b1c1c;
    border: 1px solid #fca5a5;
    padding: 11px 14px;
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