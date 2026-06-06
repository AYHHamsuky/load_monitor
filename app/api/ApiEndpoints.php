<?php
/**
 * Live read-only endpoint handlers.
 *
 * Each public static method takes ($db, $params) and emits a JSON
 * response via ApiResponse (which terminates the request).
 *
 * URL layout (after .htaccess rewrite):
 *   /api/v1/health
 *   /api/v1/me
 *   /api/v1/iss                        ?q=
 *   /api/v1/transmission-stations
 *   /api/v1/area-offices
 *   /api/v1/feeders/11kv               ?iss=&band=
 *   /api/v1/feeders/33kv               ?ts=
 *   /api/v1/readings/11kv              ?date=|from=&to=  &feeder=  &iss=  &limit=  &offset=
 *   /api/v1/readings/33kv              ?date=|from=&to=  &feeder=  &ts=   &limit=  &offset=
 *   /api/v1/interruptions/11kv         ?from=&to=&iss=
 *   /api/v1/interruptions/33kv         ?from=&to=&ts=
 *   /api/v1/late-entries               ?from=&to=&voltage=&iss=
 *   /api/v1/energy/daily               ?date=|from=&to=
 *   /api/v1/energy/by-band             ?date=
 *   /api/v1/energy/by-area             ?date=
 *   /api/v1/energy/hourly              ?date=
 */
class ApiEndpoints
{
    // ── helpers ──────────────────────────────────────────────────────────────
    private static function date(array $p, string $key, ?string $default = null): ?string
    {
        $v = $p[$key] ?? $default;
        if ($v === null || $v === '') return null;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
            ApiResponse::error('INVALID_DATE', "{$key} must be YYYY-MM-DD", 400);
            exit;
        }
        return $v;
    }

    private static function int(array $p, string $key, int $default, int $min, int $max): int
    {
        $v = isset($p[$key]) ? (int)$p[$key] : $default;
        return max($min, min($max, $v));
    }

    private static function range(array $p): array
    {
        $date = self::date($p, 'date');
        $from = self::date($p, 'from');
        $to   = self::date($p, 'to');
        if ($date) {
            return [$date, $date];
        }
        if (!$from || !$to) {
            // Default to today only
            $today = date('Y-m-d');
            return [$from ?: $today, $to ?: $today];
        }
        if ($from > $to) {
            ApiResponse::error('INVALID_RANGE', 'from must be <= to', 400);
            exit;
        }
        return [$from, $to];
    }

    // ── meta endpoints ──────────────────────────────────────────────────────
    public static function health(PDO $db, array $params): void
    {
        ApiResponse::ok([
            'ok'   => true,
            'time' => date('c'),
        ]);
    }

    public static function me(PDO $db, array $params): void
    {
        ApiResponse::ok($GLOBALS['API_CLIENT']);
    }

    // ── reference data ──────────────────────────────────────────────────────
    public static function iss(PDO $db, array $params): void
    {
        $q = trim($params['q'] ?? '');
        if ($q !== '') {
            $st = $db->prepare("SELECT iss_code, iss_name FROM iss_locations WHERE iss_name LIKE ? OR iss_code LIKE ? ORDER BY iss_name");
            $st->execute(["%$q%", "%$q%"]);
        } else {
            $st = $db->query("SELECT iss_code, iss_name FROM iss_locations ORDER BY iss_name");
        }
        ApiResponse::ok($st->fetchAll(PDO::FETCH_ASSOC));
    }

    public static function transmissionStations(PDO $db, array $params): void
    {
        $rows = $db->query("SELECT ts_code, station_name FROM transmission_stations ORDER BY station_name")
                   ->fetchAll(PDO::FETCH_ASSOC);
        ApiResponse::ok($rows);
    }

    public static function areaOffices(PDO $db, array $params): void
    {
        $rows = $db->query("SELECT ao_id, ao_name FROM area_offices ORDER BY ao_name")
                   ->fetchAll(PDO::FETCH_ASSOC);
        ApiResponse::ok($rows);
    }

    public static function feeders11kv(PDO $db, array $params): void
    {
        $where  = [];
        $args   = [];
        if (!empty($params['iss']))  { $where[] = 'f.iss_code = ?'; $args[] = $params['iss']; }
        if (!empty($params['band'])) { $where[] = 'f.band = ?';     $args[] = $params['band']; }

        $sql = "
            SELECT f.fdr11kv_code, f.fdr11kv_name, f.band, f.max_load,
                   f.iss_code, iss.iss_name, f.fdr33kv_code, f.ao_code
            FROM fdr11kv f
            LEFT JOIN iss_locations iss ON iss.iss_code = f.iss_code
        ";
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY iss.iss_name, f.fdr11kv_name';

        $st = $db->prepare($sql);
        $st->execute($args);
        ApiResponse::ok($st->fetchAll(PDO::FETCH_ASSOC));
    }

    public static function feeders33kv(PDO $db, array $params): void
    {
        $where = []; $args = [];
        if (!empty($params['ts'])) { $where[] = 'f.ts_code = ?'; $args[] = $params['ts']; }

        $sql = "
            SELECT f.fdr33kv_code, f.fdr33kv_name, f.ts_code, ts.station_name, f.max_load
            FROM fdr33kv f
            LEFT JOIN transmission_stations ts ON ts.ts_code = f.ts_code
        ";
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY ts.station_name, f.fdr33kv_name';

        $st = $db->prepare($sql);
        $st->execute($args);
        ApiResponse::ok($st->fetchAll(PDO::FETCH_ASSOC));
    }

    // ── live readings ───────────────────────────────────────────────────────
    public static function readings11kv(PDO $db, array $params): void
    {
        [$from, $to] = self::range($params);
        $limit  = self::int($params, 'limit',  1000, 1, 5000);
        $offset = self::int($params, 'offset',    0, 0, 1_000_000);

        $where = ['d.entry_date BETWEEN ? AND ?'];
        $args  = [$from, $to];
        if (!empty($params['feeder'])) { $where[] = 'd.fdr11kv_code = ?'; $args[] = $params['feeder']; }
        if (!empty($params['iss']))    { $where[] = 'f.iss_code = ?';     $args[] = $params['iss']; }

        $whereSql = implode(' AND ', $where);

        $total = $db->prepare("SELECT COUNT(*) FROM fdr11kv_data d LEFT JOIN fdr11kv f ON f.fdr11kv_code = d.fdr11kv_code WHERE {$whereSql}");
        $total->execute($args);
        $totalRows = (int)$total->fetchColumn();

        $st = $db->prepare("
            SELECT
                d.entry_date, d.entry_hour, d.fdr11kv_code, f.fdr11kv_name, f.band,
                f.iss_code, iss.iss_name,
                d.load_read, d.fault_code, d.fault_remark, d.user_id, d.timestamp
            FROM fdr11kv_data d
            LEFT JOIN fdr11kv f         ON f.fdr11kv_code = d.fdr11kv_code
            LEFT JOIN iss_locations iss ON iss.iss_code   = f.iss_code
            WHERE {$whereSql}
            ORDER BY d.entry_date DESC, d.entry_hour DESC, f.fdr11kv_name
            LIMIT {$limit} OFFSET {$offset}
        ");
        $st->execute($args);

        ApiResponse::ok($st->fetchAll(PDO::FETCH_ASSOC), [
            'from'  => $from, 'to' => $to,
            'limit' => $limit, 'offset' => $offset, 'total' => $totalRows,
        ]);
    }

    public static function readings33kv(PDO $db, array $params): void
    {
        [$from, $to] = self::range($params);
        $limit  = self::int($params, 'limit',  1000, 1, 5000);
        $offset = self::int($params, 'offset',    0, 0, 1_000_000);

        $where = ['d.entry_date BETWEEN ? AND ?'];
        $args  = [$from, $to];
        if (!empty($params['feeder'])) { $where[] = 'd.fdr33kv_code = ?'; $args[] = $params['feeder']; }
        if (!empty($params['ts']))     { $where[] = 'f.ts_code = ?';      $args[] = $params['ts']; }

        $whereSql = implode(' AND ', $where);

        $total = $db->prepare("SELECT COUNT(*) FROM fdr33kv_data d LEFT JOIN fdr33kv f ON f.fdr33kv_code = d.fdr33kv_code WHERE {$whereSql}");
        $total->execute($args);
        $totalRows = (int)$total->fetchColumn();

        $st = $db->prepare("
            SELECT
                d.entry_date, d.entry_hour, d.fdr33kv_code, f.fdr33kv_name,
                f.ts_code, ts.station_name,
                d.load_read, d.fault_code, d.fault_remark, d.user_id, d.timestamp
            FROM fdr33kv_data d
            LEFT JOIN fdr33kv f                ON f.fdr33kv_code = d.fdr33kv_code
            LEFT JOIN transmission_stations ts ON ts.ts_code     = f.ts_code
            WHERE {$whereSql}
            ORDER BY d.entry_date DESC, d.entry_hour DESC, f.fdr33kv_name
            LIMIT {$limit} OFFSET {$offset}
        ");
        $st->execute($args);

        ApiResponse::ok($st->fetchAll(PDO::FETCH_ASSOC), [
            'from'  => $from, 'to' => $to,
            'limit' => $limit, 'offset' => $offset, 'total' => $totalRows,
        ]);
    }

    // ── interruptions ───────────────────────────────────────────────────────
    public static function interruptions11kv(PDO $db, array $params): void
    {
        [$from, $to] = self::range($params);
        $where = []; $args = [];
        if ($from) { $where[] = "DATE(i.datetime_out) BETWEEN ? AND ?"; $args[] = $from; $args[] = $to; }
        if (!empty($params['iss'])) { $where[] = 'f.iss_code = ?'; $args[] = $params['iss']; }
        $sql = "
            SELECT i.*, f.fdr11kv_name, f.iss_code
            FROM interruptions_11kv i
            LEFT JOIN fdr11kv f ON f.fdr11kv_code = i.fdr11kv_code
        ";
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY i.datetime_out DESC';
        $st = $db->prepare($sql);
        $st->execute($args);
        ApiResponse::ok($st->fetchAll(PDO::FETCH_ASSOC), ['from' => $from, 'to' => $to]);
    }

    public static function interruptions33kv(PDO $db, array $params): void
    {
        [$from, $to] = self::range($params);
        $where = []; $args = [];
        if ($from) { $where[] = "DATE(i.datetime_out) BETWEEN ? AND ?"; $args[] = $from; $args[] = $to; }
        if (!empty($params['ts'])) { $where[] = 'f.ts_code = ?'; $args[] = $params['ts']; }
        $sql = "
            SELECT i.*, f.fdr33kv_name, f.ts_code
            FROM interruptions i
            LEFT JOIN fdr33kv f ON f.fdr33kv_code = i.fdr33kv_code
        ";
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY i.datetime_out DESC';
        $st = $db->prepare($sql);
        $st->execute($args);
        ApiResponse::ok($st->fetchAll(PDO::FETCH_ASSOC), ['from' => $from, 'to' => $to]);
    }

    public static function lateEntries(PDO $db, array $params): void
    {
        [$from, $to] = self::range($params);
        $where = ['l.log_date BETWEEN ? AND ?'];
        $args  = [$from, $to];
        if (!empty($params['voltage']) && in_array($params['voltage'], ['11kV','33kV'], true)) {
            $where[] = 'l.voltage_level = ?'; $args[] = $params['voltage'];
        }
        if (!empty($params['iss'])) { $where[] = 'l.iss_code = ?'; $args[] = $params['iss']; }

        $limit  = self::int($params, 'limit', 1000, 1, 5000);
        $offset = self::int($params, 'offset',   0, 0, 1_000_000);
        $whereSql = implode(' AND ', $where);

        $total = $db->prepare("SELECT COUNT(*) FROM late_entry_log l WHERE {$whereSql}");
        $total->execute($args);

        $st = $db->prepare("
            SELECT l.id, l.voltage_level, l.log_date, l.specific_hour,
                   l.user_id, s.staff_name, l.iss_code, iss.iss_name,
                   l.explanation, l.logged_at
            FROM late_entry_log l
            LEFT JOIN staff_details s   ON s.payroll_id = l.user_id
            LEFT JOIN iss_locations iss ON iss.iss_code = l.iss_code
            WHERE {$whereSql}
            ORDER BY l.log_date DESC, l.specific_hour DESC, l.logged_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ");
        $st->execute($args);

        ApiResponse::ok($st->fetchAll(PDO::FETCH_ASSOC), [
            'from'  => $from, 'to' => $to,
            'limit' => $limit, 'offset' => $offset, 'total' => (int)$total->fetchColumn(),
        ]);
    }

    // ── energy aggregates ───────────────────────────────────────────────────
    public static function energyDaily(PDO $db, array $params): void
    {
        [$from, $to] = self::range($params);
        // Sum MWh = sum(load_read MW × 1h)
        $st = $db->prepare("
            SELECT entry_date,
                   ROUND(SUM(CASE WHEN load_read > 0 THEN load_read ELSE 0 END), 2) AS mwh,
                   COUNT(DISTINCT entry_hour)                                       AS hours_with_data,
                   COUNT(DISTINCT fdr11kv_code)                                     AS feeders,
                   COUNT(CASE WHEN fault_code IS NOT NULL AND fault_code != '' THEN 1 END) AS fault_cells
            FROM fdr11kv_data
            WHERE entry_date BETWEEN ? AND ?
            GROUP BY entry_date
            ORDER BY entry_date
        ");
        $st->execute([$from, $to]);
        $rows11 = $st->fetchAll(PDO::FETCH_ASSOC);

        $st = $db->prepare("
            SELECT entry_date,
                   ROUND(SUM(CASE WHEN load_read > 0 THEN load_read ELSE 0 END), 2) AS mwh,
                   COUNT(DISTINCT entry_hour)                                       AS hours_with_data,
                   COUNT(DISTINCT fdr33kv_code)                                     AS feeders,
                   COUNT(CASE WHEN fault_code IS NOT NULL AND fault_code != '' THEN 1 END) AS fault_cells
            FROM fdr33kv_data
            WHERE entry_date BETWEEN ? AND ?
            GROUP BY entry_date
            ORDER BY entry_date
        ");
        $st->execute([$from, $to]);
        $rows33 = $st->fetchAll(PDO::FETCH_ASSOC);

        // Merge by date
        $by = [];
        foreach ($rows11 as $r) {
            $by[$r['entry_date']]['date']    = $r['entry_date'];
            $by[$r['entry_date']]['mwh_11kv'] = (float)$r['mwh'];
            $by[$r['entry_date']]['hours_11kv']   = (int)$r['hours_with_data'];
            $by[$r['entry_date']]['faults_11kv']  = (int)$r['fault_cells'];
        }
        foreach ($rows33 as $r) {
            $by[$r['entry_date']]['date']    = $r['entry_date'];
            $by[$r['entry_date']]['mwh_33kv'] = (float)$r['mwh'];
            $by[$r['entry_date']]['hours_33kv']   = (int)$r['hours_with_data'];
            $by[$r['entry_date']]['faults_33kv']  = (int)$r['fault_cells'];
        }
        $out = [];
        foreach ($by as $row) {
            $row['mwh_11kv'] ??= 0; $row['mwh_33kv'] ??= 0;
            $row['mwh_total'] = round($row['mwh_11kv'] + $row['mwh_33kv'], 2);
            $out[] = $row;
        }
        ApiResponse::ok($out, ['from' => $from, 'to' => $to]);
    }

    public static function energyByBand(PDO $db, array $params): void
    {
        $date = self::date($params, 'date', date('Y-m-d'));
        $st = $db->prepare("
            SELECT f.band,
                   COUNT(DISTINCT f.fdr11kv_code) AS feeders,
                   ROUND(SUM(CASE WHEN d.load_read > 0 THEN d.load_read ELSE 0 END), 2) AS mwh,
                   SUM(CASE WHEN d.load_read > 0 THEN 1 ELSE 0 END) AS supply_cells,
                   COUNT(d.entry_hour) AS reading_cells
            FROM fdr11kv f
            LEFT JOIN fdr11kv_data d
                   ON d.fdr11kv_code = f.fdr11kv_code
                  AND d.entry_date = ?
            GROUP BY f.band
            ORDER BY f.band
        ");
        $st->execute([$date]);
        ApiResponse::ok($st->fetchAll(PDO::FETCH_ASSOC), ['date' => $date]);
    }

    public static function energyByArea(PDO $db, array $params): void
    {
        $date = self::date($params, 'date', date('Y-m-d'));
        $st = $db->prepare("
            SELECT ao.ao_id, ao.ao_name,
                   COUNT(DISTINCT f.fdr11kv_code) AS feeders,
                   ROUND(SUM(CASE WHEN d.load_read > 0 THEN d.load_read ELSE 0 END), 2) AS mwh
            FROM area_offices ao
            LEFT JOIN fdr11kv f      ON f.ao_code = ao.ao_id
            LEFT JOIN fdr11kv_data d ON d.fdr11kv_code = f.fdr11kv_code AND d.entry_date = ?
            GROUP BY ao.ao_id, ao.ao_name
            ORDER BY ao.ao_name
        ");
        $st->execute([$date]);
        ApiResponse::ok($st->fetchAll(PDO::FETCH_ASSOC), ['date' => $date]);
    }

    public static function energyHourly(PDO $db, array $params): void
    {
        $date = self::date($params, 'date', date('Y-m-d'));
        $st = $db->prepare("
            SELECT entry_hour,
                   ROUND(SUM(CASE WHEN load_read > 0 THEN load_read ELSE 0 END), 2) AS mw_11kv,
                   COUNT(DISTINCT fdr11kv_code) AS feeders_with_data
            FROM fdr11kv_data
            WHERE entry_date = ?
            GROUP BY entry_hour
            ORDER BY entry_hour
        ");
        $st->execute([$date]);
        $hours = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $hours[(int)$r['entry_hour']] = $r;
        }
        $out = [];
        for ($h = 0; $h <= 23; $h++) {
            $r = $hours[$h] ?? ['entry_hour' => $h, 'mw_11kv' => 0, 'feeders_with_data' => 0];
            $r['entry_hour'] = (int)$r['entry_hour'];
            $out[] = $r;
        }
        ApiResponse::ok($out, ['date' => $date]);
    }
}
