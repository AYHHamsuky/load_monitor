<?php

class Dashboard11kv
{
    /**
     * Get today's dashboard data (matrix + summary)
     */
    public static function today(string $iss, string $fdr33): array
    {
        $db = Database::connect();

        /* ================= FEEDERS ================= */
        $feeders = $db->prepare("
            SELECT fdr11kv_code, fdr11kv_name, band
            FROM fdr11kv
            WHERE iss_code = ? AND fdr33kv_code = ?
            ORDER BY fdr11kv_name
        ");
        $feeders->execute([$iss, $fdr33]);

        $matrix = [];

        foreach ($feeders as $f) {
            $matrix[$f['fdr11kv_code']] = [
                'code'  => $f['fdr11kv_code'],
                'name'  => $f['fdr11kv_name'],
                'band'  => $f['band'],
                'hours' => array_fill(1, 24, '—')
            ];
        }

        /* ================= LOAD DATA ================= */
        $stmt = $db->prepare("
            SELECT Fdr11kv_code, entry_hour, load_read, fault_code
            FROM fdr11kv_data
            WHERE entry_date = CURDATE()
        ");
        $stmt->execute();

        foreach ($stmt as $r) {
            if (!isset($matrix[$r['Fdr11kv_code']])) {
                continue;
            }

            $matrix[$r['Fdr11kv_code']]['hours'][$r['entry_hour']] =
                ($r['load_read'] > 0)
                    ? number_format($r['load_read'], 2)
                    : ($r['fault_code'] ?: 'LS');
        }

        /* ================= SUMMARY ================= */
        $summary = $db->prepare("
            SELECT f.fdr11kv_name, f.band,
                   COUNT(CASE WHEN d.load_read > 0 THEN 1 END) supply_hours,
                   SUM(d.load_read) total_load,
                   AVG(NULLIF(d.load_read,0)) avg_load,
                   MAX(d.load_read) peak_load
            FROM fdr11kv f
            LEFT JOIN fdr11kv_data d
              ON d.Fdr11kv_code = f.fdr11kv_code
             AND d.entry_date = CURDATE()
            WHERE f.iss_code = ? AND f.fdr33kv_code = ?
            GROUP BY f.fdr11kv_code
        ");
        $summary->execute([$iss, $fdr33]);

        return [
            'matrix'  => array_values($matrix),
            'summary' => $summary->fetchAll()
        ];
    }

    /**
     * Protected save (NO overwrite by other staff)
     */
    public static function saveProtected(array $data): array
    {
        $db = Database::connect();

        $check = $db->prepare("
            SELECT user_id
            FROM fdr11kv_data
            WHERE entry_date = CURDATE()
              AND Fdr11kv_code = ?
              AND entry_hour = ?
            LIMIT 1
        ");
        $check->execute([$data['feeder'], $data['hour']]);
        $existing = $check->fetch();

        if ($existing && $existing['user_id'] !== $data['payroll_id']) {
            return [
                'success' => false,
                'message' => 'This hour has already been entered by another staff'
            ];
        }

        $stmt = $db->prepare("
            INSERT INTO fdr11kv_data
            (entry_date, fdr11kv_code, entry_hour, load_read, fault_code, user_id)
            VALUES (date('now'), ?, ?, ?, ?, ?)
            ON CONFLICT(entry_date, fdr11kv_code, entry_hour) DO UPDATE SET
                load_read  = excluded.load_read,
                fault_code = excluded.fault_code,
                user_id    = excluded.user_id
        ");

        $stmt->execute([
            $data['feeder'],
            $data['hour'],
            $data['load'],
            $data['fault'],
            $data['payroll_id']
        ]);

        return ['success' => true];
    }
}
