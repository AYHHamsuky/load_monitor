<?php
// File: /app/Modules/Dashboard/DashboardController.php

namespace App\Modules\Dashboard;

use App\Core\Auth;
use App\Core\Database;

class DashboardController
{
    public function index(): void
    {
        $user = Auth::user();
        $db = Database::get();

        $data = $db->prepare("
            SELECT f.11kv_fdr_name, f.band, d.day_hour,
                   COALESCE(d.load_reading, d.fault) AS value
            FROM fdr11kv f
            LEFT JOIN fdr11kv_data d
              ON d.fdr11kv_code = f.11kv_code
             AND d.date = CURDATE()
            WHERE f.33kv_code = ?
            ORDER BY f.11kv_fdr_name, d.day_hour
        ");
        $data->execute([$user['assigned_33kv_station']]);

        require __DIR__ . '/DashboardView.php';
    }
}
