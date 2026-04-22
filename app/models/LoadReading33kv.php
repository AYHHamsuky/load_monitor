<?php
/**
 * LoadReading33kv Model
 * Path: app/models/LoadReading33kv.php
 *
 * Hours: 0-23  (0 = 00:00–00:59 … 23 = 23:00–23:59)
 */
class LoadReading33kv
{
    public static function save(array $data): array
    {
        $db = Database::connect();

        foreach (['entry_date','Fdr33kv_code','entry_hour','load_read','user_id'] as $f) {
            if (!isset($data[$f]) || (string)$data[$f] === '') {
                return ['success' => false, 'message' => "Missing required field: {$f}"];
            }
        }

        $hour = (int)$data['entry_hour'];
        if ($hour < 0 || $hour > 23) {
            return ['success' => false, 'message' => 'Invalid hour — must be 0–23'];
        }

        if ($data['entry_date'] !== getOperationalDate()) {
            return ['success' => false, 'message' => 'Past-date entries require a Correction Request'];
        }

        if ((float)$data['load_read'] < 0) {
            return ['success' => false, 'message' => 'Negative load values are not allowed'];
        }

        // max_load guard
        $ms = $db->prepare("SELECT max_load FROM fdr33kv WHERE fdr33kv_code = ? LIMIT 1");
        $ms->execute([$data['Fdr33kv_code']]);
        $fr = $ms->fetch(PDO::FETCH_ASSOC);
        if ($fr && $fr['max_load'] !== null && (float)$data['load_read'] > (float)$fr['max_load']) {
            return [
                'success'  => false,
                'message'  => 'Value exceeded the maximum allowed load for this feeder',
                'max_load' => (float)$fr['max_load'],
            ];
        }

        // Load vs fault rule
        if ((float)$data['load_read'] > 0) {
            $data['fault_code']   = null;
            $data['fault_remark'] = null;
        } else {
            if (empty($data['fault_code'])) {
                return ['success' => false, 'message' => 'Fault code is required when load is zero'];
            }
        }

        // Uniqueness check
        $ck = $db->prepare("
            SELECT user_id FROM fdr33kv_data
            WHERE entry_date = ? AND fdr33kv_code = ? AND entry_hour = ?
            LIMIT 1
        ");
        $ck->execute([$data['entry_date'], $data['Fdr33kv_code'], $data['entry_hour']]);
        $existing = $ck->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            if ($existing['user_id'] !== $data['user_id']) {
                return ['success' => false, 'message' => 'This hour has already been entered by another staff member'];
            }
            // Same user: delete + re-insert (original pattern)
            $db->prepare("
                DELETE FROM fdr33kv_data
                WHERE entry_date = ? AND fdr33kv_code = ? AND entry_hour = ?
            ")->execute([$data['entry_date'], $data['Fdr33kv_code'], $data['entry_hour']]);
        }

        $db->prepare("
            INSERT INTO fdr33kv_data
                (entry_date, fdr33kv_code, entry_hour, load_read, fault_code, fault_remark, user_id, timestamp)
            VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ")->execute([
            $data['entry_date'],
            $data['Fdr33kv_code'],
            $data['entry_hour'],
            $data['load_read'],
            $data['fault_code']   ?? null,
            $data['fault_remark'] ?? null,
            $data['user_id'],
        ]);

        return [
            'success' => true,
            'message' => $existing ? 'Load entry updated successfully' : 'Load entry saved successfully',
        ];
    }

    /**
     * Auto-seal all 33kV feeders at 01:00 — write BLANK for every missing hour.
     */
    public static function sealDay(string $opDate): int
    {
        $db      = Database::connect();
        $fs      = $db->query("SELECT fdr33kv_code FROM fdr33kv");
        $feeders = $fs->fetchAll(PDO::FETCH_COLUMN);
        $blanked = 0;

        foreach ($feeders as $code) {
            $ex = $db->prepare(
                "SELECT entry_hour FROM fdr33kv_data WHERE entry_date = ? AND fdr33kv_code = ?"
            );
            $ex->execute([$opDate, $code]);
            $filled  = array_column($ex->fetchAll(PDO::FETCH_ASSOC), 'entry_hour');
            $missing = array_diff(range(0, 23), array_map('intval', $filled));

            foreach ($missing as $h) {
                $db->prepare("
                    INSERT INTO fdr33kv_data
                        (entry_date, fdr33kv_code, entry_hour,
                         load_read, fault_code, fault_remark, user_id, timestamp)
                    VALUES (?, ?, ?, NULL, 'BLANK', 'Auto-sealed at day close', 'SYSTEM', CURRENT_TIMESTAMP)
                ")->execute([$opDate, $code, $h]);
                $blanked++;
            }
        }
        return $blanked;
    }

    /**
     * Build hour-matrix for the 33kV dashboard view.
     * Returns array keyed by fdr33kv_code; 'hours' indexed 0-23.
     */
    public static function matrixByDate(string $opDate): array
    {
        $db   = Database::connect();
        $stmt = $db->prepare("
            SELECT f.fdr33kv_code, f.fdr33kv_name, f.ts_code, f.max_load,
                   t.station_name,
                   d.entry_hour, d.load_read, d.fault_code, d.fault_remark
            FROM fdr33kv f
            LEFT JOIN transmission_stations t ON t.ts_code = f.ts_code
            LEFT JOIN fdr33kv_data d
                ON  d.fdr33kv_code = f.fdr33kv_code
                AND d.entry_date   = ?
            ORDER BY f.fdr33kv_name, d.entry_hour
        ");
        $stmt->execute([$opDate]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $matrix = [];
        foreach ($rows as $r) {
            $code = $r['fdr33kv_code'];
            if (!isset($matrix[$code])) {
                $matrix[$code] = [
                    'fdr33kv_code' => $code,
                    'fdr33kv_name' => $r['fdr33kv_name'],
                    'ts_code'      => $r['ts_code'],
                    'station_name' => $r['station_name'],
                    'max_load'     => $r['max_load'],
                    'hours'        => array_fill(0, 24, null), // indices 0-23
                ];
            }
            if ($r['entry_hour'] !== null) {
                $matrix[$code]['hours'][(int)$r['entry_hour']] = [
                    'load'   => $r['load_read'],
                    'fault'  => $r['fault_code'],
                    'remark' => $r['fault_remark'],
                ];
            }
        }
        return $matrix;
    }

    public static function getFeeder(string $code): ?array
    {
        $db   = Database::connect();
        $stmt = $db->prepare(
            "SELECT fdr33kv_code, fdr33kv_name, ts_code, max_load
               FROM fdr33kv WHERE fdr33kv_code = ? LIMIT 1"
        );
        $stmt->execute([$code]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
