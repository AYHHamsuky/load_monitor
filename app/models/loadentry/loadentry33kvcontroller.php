<?php
// File: /app/Modules/LoadEntry/LoadEntry33kvController.php

namespace App\Modules\LoadEntry;

use App\Core\Auth;
use App\Core\Database;

class LoadEntry33kvController
{
    public function form(): void
    {
        $user = Auth::user();
        if ($user['33kv_level'] !== 'YES') {
            http_response_code(403);
            exit('Unauthorized');
        }

        $db = Database::get();
        $stmt = $db->prepare("
            SELECT * FROM fdr33kv
            WHERE ts_code = ?
        ");
        $stmt->execute([$user['assigned_33kv_station']]);

        $feeders = $stmt->fetchAll();
        require __DIR__ . '/LoadEntry33kvView.php';
    }

    public function store(): void
    {
        $user = Auth::user();
        $db = Database::get();

        $stmt = $db->prepare("
            INSERT INTO fdr33kv_data
            (fdr33kv_code, day_hour, load_reading, fault_code, fault_remark, user_id, date)
            VALUES (?, ?, ?, ?, ?, ?, CURDATE())
        ");

        $stmt->execute([
            $_POST['fdr33kv_code'],
            $_POST['day_hour'],
            $_POST['load_reading'],
            $_POST['fault'],
            $_POST['fault_remark'],
            $user['payroll_id']
        ]);

        header('Location: /index.php');
    }
}
