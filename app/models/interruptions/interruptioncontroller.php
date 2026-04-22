<?php
// File: /app/Modules/Interruptions/InterruptionController.php

namespace App\Modules\Interruptions;

use App\Core\Auth;
use App\Core\Database;

class InterruptionController
{
    public function form(): void
    {
        Auth::check();
        require __DIR__ . '/InterruptionView.php';
    }

    public function store(): void
    {
        $db = Database::get();
        $user = Auth::user();

        $out = $_POST['datetime_out'];
        $in  = $_POST['datetime_in'];

        $duration = (strtotime($in) - strtotime($out)) / 3600;

        $stmt = $db->prepare("
            INSERT INTO interruptions
            (fdr33_code, Interruption_Type, Load_Loss,
             datetime_out, datetime_in, Duration,
             Reason_for_Interruption, Resolution,
             Weather_Condition, Reason_for_delay,
             user_id)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)
        ");

        $stmt->execute([
            $_POST['fdr33_code'],
            $_POST['type'],
            $_POST['load_loss'],
            $out,
            $in,
            $duration,
            $_POST['reason'],
            $_POST['resolution'],
            $_POST['weather'],
            $_POST['delay_reason'],
            $user['payroll_id']
        ]);

        header('Location: /index.php');
    }
}
