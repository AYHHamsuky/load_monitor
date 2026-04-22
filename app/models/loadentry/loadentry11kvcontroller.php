<?php
// File: /app/Modules/LoadEntry/LoadEntry11kvController.php

namespace App\Modules\LoadEntry;

use App\Core\Auth;
use App\Core\Database;

class LoadEntry11kvController
{
    public function form(): void
    {
        $user = Auth::user();
        if ($user['11kv_level'] !== 'YES') {
            http_response_code(403);
            exit('Unauthorized');
        }

        $db = Database::get();

        $feeders = $db->prepare("
            SELECT f.*
            FROM fdr11kv f
            JOIN fdr33kv t ON f.33kv_code = t.33kv_code
            WHERE t.ts_code = ?
        ");
        $feeders->execute([$user['assigned_33kv_station']]);

        require __DIR__ . '/LoadEntry11kvView.php';
    }

    public function store(): void
    {
        $db = Database::get();
        $user = Auth::user();

        $stmt = $db->prepare("
            INSERT INTO fdr11kv_data
            (fdr11kv_code, day_hour, load_reading, fault, fault_remark, user_id, date)
            VALUES (?, ?, ?, ?, ?, ?, CURDATE())
        ");

        $stmt->execute([
            $_POST['fdr11kv_code'],
            $_POST['day_hour'],
            $_POST['load_reading'],
            $_POST['fault'] ?: null,
            $_POST['fault_remark'],
            $user['payroll_id']
        ]);

        header('Location: /index.php');
    }
}
