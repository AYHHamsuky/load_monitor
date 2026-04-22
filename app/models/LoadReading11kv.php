<?php
/**
 * LoadReading11kv Model
 * Path: app/models/LoadReading11kv.php
 *
 * Hours: 0-23  (0 = 00:00–00:59 … 23 = 23:00–23:59)
 * Operational date: see getOperationalDate() in LateEntryLog.php
 */
class LoadReading11kv
{
    /**
     * Save or overwrite one hourly reading.
     * Business-rule gating (batch windows, future-hour block, late-entry)
     * is done in LoadEntryController before this is called.
     * This method only enforces field-level and DB-level rules.
     */
    public static function save(array $data): array
    {
        $db = Database::connect();

        foreach (['entry_date','Fdr11kv_code','entry_hour','load_read','user_id'] as $f) {
            if (!isset($data[$f]) || (string)$data[$f] === '') {
                return ['success' => false, 'message' => "Missing required field: {$f}"];
            }
        }

        $hour = (int)$data['entry_hour'];
        if ($hour < 0 || $hour > 23) {
            return ['success' => false, 'message' => 'Invalid hour — must be 0–23'];
        }

        // entry_date must be the current operational date
        if ($data['entry_date'] !== getOperationalDate()) {
            return ['success' => false, 'message' => 'Past-date entries require a Correction Request'];
        }

        if ((float)$data['load_read'] < 0) {
            return ['success' => false, 'message' => 'Negative load values are not allowed'];
        }

        // max_load guard
        $ms = $db->prepare("SELECT max_load FROM fdr11kv WHERE fdr11kv_code = ? LIMIT 1");
        $ms->execute([$data['Fdr11kv_code']]);
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

        // Hour uniqueness check
        $ck = $db->prepare("
            SELECT user_id FROM fdr11kv_data
            WHERE entry_date = ? AND Fdr11kv_code = ? AND entry_hour = ?
            LIMIT 1
        ");
        $ck->execute([$data['entry_date'], $data['Fdr11kv_code'], $data['entry_hour']]);
        $existing = $ck->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            if ($existing['user_id'] !== $data['user_id']) {
                return ['success' => false, 'message' => 'This hour has already been entered by another staff member'];
            }
            // Same user — update in place
            $db->prepare("
                UPDATE fdr11kv_data
                   SET load_read    = ?,
                       fault_code   = ?,
                       fault_remark = ?,
                       timestamp    = CURRENT_TIMESTAMP
                 WHERE entry_date   = ?
                   AND Fdr11kv_code = ?
                   AND entry_hour   = ?
            ")->execute([
                $data['load_read'],
                $data['fault_code'],
                $data['fault_remark'],
                $data['entry_date'],
                $data['Fdr11kv_code'],
                $data['entry_hour'],
            ]);
            return ['success' => true, 'message' => 'Load entry updated successfully'];
        }

        // Fresh insert
        $db->prepare("
            INSERT INTO fdr11kv_data
                (entry_date, Fdr11kv_code, entry_hour, load_read, fault_code, fault_remark, user_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ")->execute([
            $data['entry_date'],
            $data['Fdr11kv_code'],
            $data['entry_hour'],
            $data['load_read'],
            $data['fault_code'],
            $data['fault_remark'],
            $data['user_id'],
        ]);

        return ['success' => true, 'message' => 'Load entry saved successfully'];
    }

    /**
     * Auto-seal an operational day at 01:00.
     * Writes a NULL-load / BLANK-fault row for every missing hour on every
     * 11kV feeder under the given 33kV parent.
     *
     * @param  string $opDate      The operational date being sealed (Y-m-d)
     * @param  string $fdr33kvCode Parent 33kV feeder code
     * @return int    Number of blank cells written
     */
    public static function sealDay(string $opDate, string $fdr33kvCode): int
    {
        $db = Database::connect();

        $fs = $db->prepare("SELECT fdr11kv_code FROM fdr11kv WHERE fdr33kv_code = ?");
        $fs->execute([$fdr33kvCode]);
        $feeders = $fs->fetchAll(PDO::FETCH_COLUMN);

        $blanked = 0;
        foreach ($feeders as $code) {
            $ex = $db->prepare(
                "SELECT entry_hour FROM fdr11kv_data WHERE entry_date = ? AND Fdr11kv_code = ?"
            );
            $ex->execute([$opDate, $code]);
            $filled  = array_column($ex->fetchAll(PDO::FETCH_ASSOC), 'entry_hour');
            $missing = array_diff(range(0, 23), array_map('intval', $filled));

            foreach ($missing as $h) {
                $db->prepare("
                    INSERT INTO fdr11kv_data
                        (entry_date, Fdr11kv_code, entry_hour,
                         load_read, fault_code, fault_remark, user_id)
                    VALUES (?, ?, ?, NULL, 'BLANK', 'Auto-sealed at day close', 'SYSTEM')
                ")->execute([$opDate, $code, $h]);
                $blanked++;
            }
        }
        return $blanked;
    }

    /**
     * Build the hour-matrix for the dashboard view.
     * Returns an array keyed by fdr11kv_code; each entry has 'hours' indexed 0-23.
     */
    public static function matrixByDate(string $fdr33kvCode, string $opDate): array
    {
        $db   = Database::connect();
        $stmt = $db->prepare("
            SELECT f.fdr11kv_code, f.fdr11kv_name, f.band, f.max_load,
                   d.entry_hour, d.load_read, d.fault_code, d.fault_remark
            FROM fdr11kv f
            LEFT JOIN fdr11kv_data d
                ON  d.Fdr11kv_code = f.fdr11kv_code
                AND d.entry_date   = ?
            WHERE f.fdr33kv_code = ?
            ORDER BY f.fdr11kv_name, d.entry_hour
        ");
        $stmt->execute([$opDate, $fdr33kvCode]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $matrix = [];
        foreach ($rows as $r) {
            $code = $r['fdr11kv_code'];
            if (!isset($matrix[$code])) {
                $matrix[$code] = [
                    'fdr11kv_code' => $code,
                    'fdr11kv_name' => $r['fdr11kv_name'],
                    'band'         => $r['band'],
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

    public static function feedersByIss(string $issCode): array
    {
        $db   = Database::connect();
        $stmt = $db->prepare(
            "SELECT fdr11kv_code, fdr11kv_name, band, max_load
               FROM fdr11kv WHERE iss_code = ? ORDER BY fdr11kv_name"
        );
        $stmt->execute([$issCode]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
