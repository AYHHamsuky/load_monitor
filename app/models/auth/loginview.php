<!-- File: /app/Modules/Auth/LoginView.php -->
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
</head>
<body>
<form method="post">
    <input name="payroll_id" placeholder="Payroll ID" required>
    <button type="submit">Login</button>
</form>
<?php if (!empty($error)) echo "<p>$error</p>"; ?>
</body>
</html>
