<?php
// File: /app/Modules/Auth/AuthController.php

namespace App\Modules\Auth;

use App\Core\Database;

class AuthController
{
    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $payroll = $_POST['payroll_id'];

            $db = Database::get();
            $stmt = $db->prepare("SELECT * FROM user_privilege WHERE payroll_id = ? AND status='active'");
            $stmt->execute([$payroll]);
            $user = $stmt->fetch();

            if ($user) {
                $_SESSION['user'] = $user;
                header('Location: /index.php');
                exit;
            }

            $error = "Invalid user";
        }

        require __DIR__ . '/LoginView.php';
    }
}
