<?php
/**
 * MytoAllocation Model
 * Path: app/models/MytoAllocation.php
 *
 * Handles all database operations for the MYTO bulk entry and
 * per-TS allocation system used by UL8 (Lead Dispatch Officer).
 *
 * Hour scheme: 0 = 00:00-00:59 … 23 = 23:00-23:59
 */
class MytoAllocation
{
    // ─────────────────────────────────────────────────────────────────────────
    // FORMULA MANAGEMENT
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Fetch the current active formula rows (all TS with their percentages).
     * Returns array keyed by ts_code, each with: ts_code, station_name,
     * percentage, version.
     */
    public static function getActiveFormula(): array
    {
        $db   = Database::connect();
        $stmt = $db->query("
            SELECT f.ts_code, f.percentage, f.version, t.station_name
            FROM   myto_sharing_formula f
            JOIN   transmission_stations t ON t.ts_code = f.ts_code
            WHERE  f.is_active = 1
            ORDER  BY t.station_name
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Returns the current formula version number (0 if none yet).
     */
    public static function getCurrentVersion(): int
    {
        $db  = Database::connect();
        $row = $db->query(
            "SELECT COALESCE(MAX(version), 0) FROM myto_sharing_formula WHERE is_active = 1"
        )->fetchColumn();
        return (int)$row;
    }

    /**
     * Validate that formula rows sum to 100 (±0.01 tolerance).
     *
     * @param  array $rows  [ts_code => percentage, …]
     * @return true|string  true on pass, error message string on fail
     */
    public static function validateFormula(array $rows)
    {
        if (empty($rows)) {
            return 'Formula cannot be empty.';
        }
        $sum = array_sum(array_values($rows));
        if (abs($sum - 100.0) > 0.01) {
            return sprintf(
                'Percentages must sum to exactly 100%%. Current sum: %.4f%%.',
                $sum
            );
        }
        foreach ($rows as $code => $pct) {
            if ($pct < 0 || $pct > 100) {
                return "Percentage for {$code} must be between 0 and 100.";
            }
        }
        return true;
    }

    /**
     * Save (replace) the active formula with a new version.
     *
     * Steps:
     *  1. Archive current active rows to myto_formula_history.
     *  2. Deactivate current active rows.
     *  3. Insert new rows with version+1.
     *
     * @param  array  $rows   [ts_code => percentage, …]
     * @param  string $userId
     * @return array  ['success'=>bool, 'message'=>string, 'version'=>int]
     */
    public static function saveFormula(array $rows, string $userId): array
    {
        $db = Database::connect();

        $validation = self::validateFormula($rows);
        if ($validation !== true) {
            return ['success' => false, 'message' => $validation];
        }

        $db->beginTransaction();
        try {
            $currentVersion = self::getCurrentVersion();
            $newVersion     = $currentVersion + 1;

            // Archive old rows
            if ($currentVersion > 0) {
                $db->prepare("
                    INSERT INTO myto_formula_history
                        (version, ts_code, percentage, changed_by)
                    SELECT version, ts_code, percentage, ?
                    FROM   myto_sharing_formula
                    WHERE  is_active = 1
                ")->execute([$userId]);

                $db->prepare("
                    UPDATE myto_sharing_formula SET is_active = 0 WHERE is_active = 1
                ")->execute();
            }

            // Insert new rows
            $ins = $db->prepare("
                INSERT INTO myto_sharing_formula
                    (ts_code, percentage, version, is_active, updated_by)
                VALUES (?, ?, ?, 1, ?)
            ");
            foreach ($rows as $tsCode => $pct) {
                $ins->execute([$tsCode, $pct, $newVersion, $userId]);
            }

            $db->commit();
            return [
                'success' => true,
                'version' => $newVersion,
                'message' => "Formula updated to version {$newVersion}.",
            ];
        } catch (Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'DB error: ' . $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MYTO BULK ENTRY (myto_daily)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Save or update one hourly MYTO allocation and distribute it to all TS.
     *
     * @param  string $entryDate    Y-m-d
     * @param  int    $hour         0-23
     * @param  float  $allocation   MW
     * @param  string $userId
     * @param  array|null $customFormula  null = use active formula
     *                                    array = [ts_code => percentage, …]
     * @return array  ['success'=>bool, 'message'=>string, ...]
     */
    public static function saveHourlyAllocation(
        string $entryDate,
        int    $hour,
        float  $allocation,
        string $userId,
        ?array $customFormula = null
    ): array {
        if ($hour < 0 || $hour > 23) {
            return ['success' => false, 'message' => 'Invalid hour (0–23).'];
        }
        if ($allocation < 0) {
            return ['success' => false, 'message' => 'Allocation cannot be negative.'];
        }

        $db = Database::connect();

        // Determine formula to use
        if ($customFormula !== null) {
            $validation = self::validateFormula($customFormula);
            if ($validation !== true) {
                return ['success' => false, 'message' => $validation];
            }
            $formulaRows    = $customFormula;       // [ts_code => pct]
            $formulaVersion = self::getCurrentVersion(); // still record current version
        } else {
            $active = self::getActiveFormula();
            if (empty($active)) {
                return [
                    'success' => false,
                    'message' => 'No active sharing formula found. Please configure the formula first.',
                ];
            }
            $formulaRows    = array_column($active, 'percentage', 'ts_code');
            $formulaVersion = self::getCurrentVersion();
        }

        $db->beginTransaction();
        try {
            // Upsert myto_daily
            $db->prepare("
                INSERT INTO myto_daily
                    (entry_date, entry_hour, myto_allocation, formula_version, user_id, timestamp)
                VALUES (?, ?, ?, ?, ?, datetime('now'))
                ON CONFLICT(entry_date, entry_hour) DO UPDATE SET
                    myto_allocation  = excluded.myto_allocation,
                    formula_version  = excluded.formula_version,
                    user_id          = excluded.user_id,
                    timestamp        = datetime('now')
            ")->execute([$entryDate, $hour, $allocation, $formulaVersion, $userId]);

            // Distribute to each TS — upsert myto_ts_allocation
            $ins = $db->prepare("
                INSERT INTO myto_ts_allocation
                    (entry_date, ts_code, entry_hour, myto_hour_allocation, formula_version, user_id, timestamp)
                VALUES (?, ?, ?, ?, ?, ?, datetime('now'))
                ON CONFLICT(entry_date, ts_code, entry_hour) DO UPDATE SET
                    myto_hour_allocation = excluded.myto_hour_allocation,
                    formula_version      = excluded.formula_version,
                    user_id              = excluded.user_id,
                    timestamp            = datetime('now')
            ");
            $distributed = [];
            foreach ($formulaRows as $tsCode => $pct) {
                $share = round($allocation * ((float)$pct / 100.0), 2);
                $ins->execute([$entryDate, $tsCode, $hour, $share, $formulaVersion, $userId]);
                $distributed[$tsCode] = $share;
            }

            $db->commit();
            return [
                'success'     => true,
                'message'     => 'Allocation saved and distributed successfully.',
                'distributed' => $distributed,
            ];
        } catch (Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'DB error: ' . $e->getMessage()];
        }
    }

    /**
     * Get all hourly allocations for a given date.
     * Returns array indexed 0-23; null = not yet entered.
     */
    public static function getDailyAllocations(string $entryDate): array
    {
        $db   = Database::connect();
        $stmt = $db->prepare("
            SELECT entry_hour, myto_allocation, user_id, timestamp
            FROM   myto_daily
            WHERE  entry_date = ?
            ORDER  BY entry_hour
        ");
        $stmt->execute([$entryDate]);
        $rows   = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = array_fill(0, 24, null);
        foreach ($rows as $r) {
            $result[(int)$r['entry_hour']] = $r;
        }
        return $result;
    }

    /**
     * Get per-TS allocations for a given date, indexed by [ts_code][hour].
     */
    public static function getTsAllocations(string $entryDate): array
    {
        $db   = Database::connect();
        $stmt = $db->prepare("
            SELECT a.ts_code, a.entry_hour, a.myto_hour_allocation, t.station_name
            FROM   myto_ts_allocation a
            JOIN   transmission_stations t ON t.ts_code = a.ts_code
            WHERE  a.entry_date = ?
            ORDER  BY t.station_name, a.entry_hour
        ");
        $stmt->execute([$entryDate]);
        $rows   = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = [];
        foreach ($rows as $r) {
            $result[$r['ts_code']][(int)$r['entry_hour']] = (float)$r['myto_hour_allocation'];
        }
        return $result;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // DASHBOARD MATRIX DATA
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Build the full matrix for the UL8 dashboard.
     *
     * Returns array keyed by ts_code. Each entry:
     *   station_name, hours[0..23] => [
     *     'myto'        => float|null   (MYTO allocation for this TS+hour)
     *     'actual_load' => float|null   (sum of all 33kV feeder loads under TS)
     *     'fault_count' => int
     *     'variance'    => float|null   (actual - myto; positive = over-allocated)
     *   ]
     */
    public static function buildDashboardMatrix(string $entryDate): array
    {
        $db = Database::connect();

        // Get all transmission stations
        $ts_rows = $db->query("
            SELECT ts_code, station_name FROM transmission_stations ORDER BY station_name
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Build skeleton
        $matrix = [];
        foreach ($ts_rows as $ts) {
            $matrix[$ts['ts_code']] = [
                'ts_code'      => $ts['ts_code'],
                'station_name' => $ts['station_name'],
                'hours'        => array_fill(0, 24, [
                    'myto'        => null,
                    'actual_load' => null,
                    'fault_count' => 0,
                    'variance'    => null,
                ]),
            ];
        }

        // Fill MYTO allocations
        $myto_stmt = $db->prepare("
            SELECT ts_code, entry_hour, myto_hour_allocation
            FROM   myto_ts_allocation
            WHERE  entry_date = ?
        ");
        $myto_stmt->execute([$entryDate]);
        foreach ($myto_stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $tc = $r['ts_code'];
            $h  = (int)$r['entry_hour'];
            if (isset($matrix[$tc])) {
                $matrix[$tc]['hours'][$h]['myto'] = (float)$r['myto_hour_allocation'];
            }
        }

        // Fill actual 33kV load (sum of all feeders under each TS)
        $load_stmt = $db->prepare("
            SELECT f.ts_code,
                   d.entry_hour,
                   SUM(d.load_read)                                          AS total_load,
                   SUM(CASE WHEN d.fault_code IS NOT NULL
                             AND d.fault_code != '' THEN 1 ELSE 0 END)      AS fault_count
            FROM   fdr33kv_data d
            JOIN   fdr33kv      f ON f.fdr33kv_code = d.fdr33kv_code
            WHERE  d.entry_date = ?
            GROUP  BY f.ts_code, d.entry_hour
        ");
        $load_stmt->execute([$entryDate]);
        foreach ($load_stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $tc   = $r['ts_code'];
            $h    = (int)$r['entry_hour'];
            $load = (float)$r['total_load'];
            if (isset($matrix[$tc])) {
                $matrix[$tc]['hours'][$h]['actual_load'] = $load;
                $matrix[$tc]['hours'][$h]['fault_count'] = (int)$r['fault_count'];
                $myto = $matrix[$tc]['hours'][$h]['myto'];
                if ($myto !== null) {
                    $matrix[$tc]['hours'][$h]['variance'] = round($load - $myto, 2);
                }
            }
        }

        return $matrix;
    }

    /**
     * Hourly grand totals across ALL TS for the column footer.
     * Returns [hour => ['myto'=>float, 'actual'=>float]]  for 0-23.
     */
    public static function getHourlyGrandTotals(string $entryDate): array
    {
        $db   = Database::connect();
        $totals = array_fill(0, 24, ['myto' => 0.0, 'actual' => 0.0]);

        // MYTO totals from myto_daily (the raw bulk figure = sum of all TS)
        $stmt = $db->prepare("
            SELECT entry_hour, myto_allocation FROM myto_daily WHERE entry_date = ?
        ");
        $stmt->execute([$entryDate]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $totals[(int)$r['entry_hour']]['myto'] = (float)$r['myto_allocation'];
        }

        // Actual 33kV totals across all feeders
        $stmt = $db->prepare("
            SELECT entry_hour, SUM(load_read) as total
            FROM   fdr33kv_data
            WHERE  entry_date = ?
            GROUP  BY entry_hour
        ");
        $stmt->execute([$entryDate]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $totals[(int)$r['entry_hour']]['actual'] = (float)$r['total'];
        }

        return $totals;
    }

    /**
     * Summary stats for the dashboard cards.
     */
    public static function getDashboardStats(string $entryDate): array
    {
        $db = Database::connect();

        // MYTO summary
        $myto = $db->prepare("
            SELECT
                COUNT(*)                          AS hours_entered,
                COALESCE(SUM(myto_allocation),0)  AS total_myto,
                COALESCE(MAX(myto_allocation),0)  AS peak_myto,
                COALESCE(AVG(myto_allocation),0)  AS avg_myto
            FROM myto_daily WHERE entry_date = ?
        ");
        $myto->execute([$entryDate]);
        $myto_stats = $myto->fetch(PDO::FETCH_ASSOC);

        // 33kV actual load
        $load = $db->prepare("
            SELECT
                COALESCE(SUM(load_read),0)        AS total_actual,
                COALESCE(MAX(load_read),0)        AS peak_actual,
                COUNT(CASE WHEN fault_code IS NOT NULL
                            AND fault_code != '' THEN 1 END) AS fault_hours
            FROM fdr33kv_data WHERE entry_date = ?
        ");
        $load->execute([$entryDate]);
        $load_stats = $load->fetch(PDO::FETCH_ASSOC);

        // TS count
        $ts_count = (int)$db->query(
            "SELECT COUNT(*) FROM transmission_stations"
        )->fetchColumn();

        return [
            'hours_entered'   => (int)$myto_stats['hours_entered'],
            'total_myto'      => (float)$myto_stats['total_myto'],
            'peak_myto'       => (float)$myto_stats['peak_myto'],
            'avg_myto'        => (float)$myto_stats['avg_myto'],
            'total_actual'    => (float)$load_stats['total_actual'],
            'peak_actual'     => (float)$load_stats['peak_actual'],
            'fault_hours'     => (int)$load_stats['fault_hours'],
            'total_ts'        => $ts_count,
            'completion_pct'  => $ts_count > 0
                ? round(($myto_stats['hours_entered'] / 24) * 100, 1)
                : 0,
        ];
    }
}
