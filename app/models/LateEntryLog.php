<?php
/**
 * LateEntryLog — shared helper for 11kV and 33kV late-entry explanations
 * Path: app/models/LateEntryLog.php
 *
 * ── Hour scheme (0-23) ───────────────────────────────────────────────────
 *  Hour 0  = 00:00–00:59
 *  Hour 1  = 01:00–01:59
 *  …
 *  Hour 23 = 23:00–23:59
 *
 * ── Operational date rules ───────────────────────────────────────────────
 *  • A day's data spans hours 0–23 on that calendar date.
 *  • The ENTRY WINDOW for that day stays open until 01:00 the next morning.
 *  • During 00:xx (hour 0 of the NEXT calendar day) you are still entering
 *    data for the PREVIOUS calendar date — because hour 0 of the current
 *    calendar date is the LAST hour of yesterday's operational batch.
 *
 *    Concretely:
 *      2026-03-15  00:00 → entering data for op-date 2026-03-14, hour 0
 *      2026-03-15  00:59 → still entering for 2026-03-14, hour 0
 *      2026-03-15  01:00 → system flips — now entering for 2026-03-15
 *
 * ── Late-entry explanation rules (11kV) ─────────────────────────────────
 *  Batch A: hours 00–07, free until 09:00, explanation 09:00–01:00 next
 *  Batch B: hours 08–16, free until 18:00, explanation 18:00–01:00 next
 *  Batch C: hours 17–23, free until 01:00 next, no explanation after that
 *  After 01:00 next morning → entry window closed, correction only
 *
 * ── Late-entry rules (33kV) ─────────────────────────────────────────────
 *  Batch A: hours 00–11, free until 15:00, explanation 15:00–01:00 next
 *  Batch B: hours 12–19, free until 20:00, explanation 20:00–01:00 next
 *  Batch C: hours 20–23, free until 01:00 next
 *  After 01:00 next morning → correction only
 *
 * DDL:
 *  CREATE TABLE IF NOT EXISTS late_entry_log (
 *      id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 *      voltage_level  ENUM('11kV','33kV') NOT NULL DEFAULT '11kV',
 *      user_id        VARCHAR(50)  NOT NULL,
 *      iss_code       VARCHAR(20)  NOT NULL,
 *      log_date       DATE         NOT NULL  COMMENT 'Operational date (not calendar date)',
 *      specific_hour  TINYINT UNSIGNED NOT NULL COMMENT '0-23',
 *      explanation    TEXT         NOT NULL,
 *      logged_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
 *      INDEX idx_lookup (voltage_level, user_id, log_date, specific_hour)
 *  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
 */

// ─────────────────────────────────────────────────────────────────────────────
// Core helpers — used by both controllers and models
// ─────────────────────────────────────────────────────────────────────────────

/**
 * Returns the current OPERATIONAL date (Y-m-d).
 *
 * During 00:xx we are still in yesterday's batch (the entry window is still
 * open; it only closes at 01:00).  From 01:00 onward we are in today's batch.
 */
function getOperationalDate(): string
{
    $now = new DateTime();
    // PHP format 'G' = 0-23.  G===0 means 00:00–00:59.
    if ((int)$now->format('G') === 0) {
        // Still inside yesterday's entry window
        return (clone $now)->modify('-1 day')->format('Y-m-d');
    }
    return $now->format('Y-m-d');
}

/**
 * Returns the current clock hour as an entry slot (0-23).
 *  0  = 00:xx
 *  1  = 01:xx
 *  …
 * 23  = 23:xx
 */
function getCurrentHourSlot(): int
{
    return (int)(new DateTime())->format('G');
}

/**
 * Is the entry window for $opDate still open right now?
 * The window closes at exactly 01:00 on the calendar day AFTER $opDate.
 */
function isOpDateWindowOpen(string $opDate): bool
{
    $now      = new DateTime();
    $deadline = new DateTime($opDate . ' 01:00:00');
    $deadline->modify('+1 day');   // 01:00 on opDate+1
    return $now < $deadline;
}


// ─────────────────────────────────────────────────────────────────────────────
// 11kV batch windows
// ─────────────────────────────────────────────────────────────────────────────
/**
 * Returns batch metadata for an 11kV entry hour (0-23).
 *
 *  Batch A  hours 00–07  →  free window closes 09:00 same op-date calendar day
 *  Batch B  hours 08–16  →  free window closes 18:00 same op-date calendar day
 *  Batch C  hours 17–23  →  free window closes 01:00 next calendar day
 *
 * 'is_free'  = true  → no explanation needed right now
 * 'is_open'  = true  → explanation still accepted (entry window not yet closed)
 * 'correction_only' = true → window fully closed; must use correction system
 */
function getBatchWindow11kv(int $hour, string $opDate): array
{
    $now          = new DateTime();
    $calDate      = $opDate;          // deadline is relative to the op-date's calendar day
    $nextCalDate  = (new DateTime($opDate))->modify('+1 day')->format('Y-m-d');

    if ($hour >= 0 && $hour <= 7) {
        $freeDeadline = new DateTime("{$calDate} 09:00:00");
        $entryDeadline = new DateTime("{$nextCalDate} 01:00:00");
        return [
            'label'           => '00:00–07:59',
            'free_deadline'   => '09:00',
            'entry_deadline'  => '01:00 next day',
            'is_free'         => $now <= $freeDeadline,
            'is_open'         => $now < $entryDeadline,
            'correction_only' => $now >= $entryDeadline,
            'hour_from'       => 0,
            'hour_to'         => 7,
        ];
    }

    if ($hour >= 8 && $hour <= 16) {
        $freeDeadline  = new DateTime("{$calDate} 18:00:00");
        $entryDeadline = new DateTime("{$nextCalDate} 01:00:00");
        return [
            'label'           => '08:00–16:59',
            'free_deadline'   => '18:00',
            'entry_deadline'  => '01:00 next day',
            'is_free'         => $now <= $freeDeadline,
            'is_open'         => $now < $entryDeadline,
            'correction_only' => $now >= $entryDeadline,
            'hour_from'       => 8,
            'hour_to'         => 16,
        ];
    }

    // Batch C: hours 17–23
    $entryDeadline = new DateTime("{$nextCalDate} 01:00:00");
    return [
        'label'           => '17:00–23:59',
        'free_deadline'   => '01:00 next day',
        'entry_deadline'  => '01:00 next day',
        'is_free'         => $now < $entryDeadline,
        'is_open'         => $now < $entryDeadline,
        'correction_only' => $now >= $entryDeadline,
        'hour_from'       => 17,
        'hour_to'         => 23,
    ];
}

// ─────────────────────────────────────────────────────────────────────────────
// 33kV batch windows
// ─────────────────────────────────────────────────────────────────────────────
/**
 * Returns batch metadata for a 33kV entry hour (0-23).
 *
 *  Batch A  hours 00–11  →  free window closes 15:00 same op-date calendar day
 *  Batch B  hours 12–19  →  free window closes 20:00 same op-date calendar day
 *  Batch C  hours 20–23  →  free window closes 01:00 next calendar day
 */
function getBatchWindow33kv(int $hour, string $opDate): array
{
    $now         = new DateTime();
    $calDate     = $opDate;
    $nextCalDate = (new DateTime($opDate))->modify('+1 day')->format('Y-m-d');

    if ($hour >= 0 && $hour <= 11) {
        $freeDeadline  = new DateTime("{$calDate} 15:00:00");
        $entryDeadline = new DateTime("{$nextCalDate} 01:00:00");
        return [
            'label'           => '00:00–11:59',
            'free_deadline'   => '15:00',
            'entry_deadline'  => '01:00 next day',
            'is_free'         => $now <= $freeDeadline,
            'is_open'         => $now < $entryDeadline,
            'correction_only' => $now >= $entryDeadline,
            'hour_from'       => 0,
            'hour_to'         => 11,
        ];
    }

    if ($hour >= 12 && $hour <= 19) {
        $freeDeadline  = new DateTime("{$calDate} 20:00:00");
        $entryDeadline = new DateTime("{$nextCalDate} 01:00:00");
        return [
            'label'           => '12:00–19:59',
            'free_deadline'   => '20:00',
            'entry_deadline'  => '01:00 next day',
            'is_free'         => $now <= $freeDeadline,
            'is_open'         => $now < $entryDeadline,
            'correction_only' => $now >= $entryDeadline,
            'hour_from'       => 12,
            'hour_to'         => 19,
        ];
    }

    // Batch C: hours 20–23
    $entryDeadline = new DateTime("{$nextCalDate} 01:00:00");
    return [
        'label'           => '20:00–23:59',
        'free_deadline'   => '01:00 next day',
        'entry_deadline'  => '01:00 next day',
        'is_free'         => $now < $entryDeadline,
        'is_open'         => $now < $entryDeadline,
        'correction_only' => $now >= $entryDeadline,
        'hour_from'       => 20,
        'hour_to'         => 23,
    ];
}


// ─────────────────────────────────────────────────────────────────────────────
// LateEntryLog class
// ─────────────────────────────────────────────────────────────────────────────
class LateEntryLog
{
    /**
     * Log a late-entry explanation for one specific past hour.
     *
     * Required keys:
     *   voltage_level  ('11kV' or '33kV')
     *   user_id
     *   iss_code
     *   log_date       (operational date Y-m-d)
     *   specific_hour  (0-23)
     *   explanation    (non-empty string)
     *
     * Rejects if:
     *  – log_date is not the current operational date
     *  – the entry window for that batch has already closed (01:00 passed)
     *  – the hour is current or future (no explanation needed)
     */
    public static function log(array $data): array
    {
        $db = Database::connect();

        foreach (['voltage_level','user_id','iss_code','log_date','specific_hour','explanation'] as $f) {
            if (!isset($data[$f]) || (string)$data[$f] === '') {
                return ['success' => false, 'message' => "Missing field: {$f}"];
            }
        }

        if (!in_array($data['voltage_level'], ['11kV','33kV'])) {
            return ['success' => false, 'message' => 'Invalid voltage_level.'];
        }

        $hour = (int)$data['specific_hour'];
        if ($hour < 0 || $hour > 23) {
            return ['success' => false, 'message' => 'Invalid hour (must be 0–23).'];
        }

        // Must match the current operational date
        $opDate = getOperationalDate();
        if ($data['log_date'] !== $opDate) {
            return [
                'success' => false,
                'message' => 'The entry window for that date has closed. '
                           . 'Please submit a Correction Request instead.',
            ];
        }

        // Fetch batch window to verify the window is still open
        $batch = ($data['voltage_level'] === '11kV')
            ? getBatchWindow11kv($hour, $opDate)
            : getBatchWindow33kv($hour, $opDate);

        if ($batch['correction_only']) {
            return [
                'success' => false,
                'message' => 'The entry window for the '
                           . $batch['label']
                           . ' batch has closed at 01:00. Please use the Correction Request system.',
            ];
        }

        // The hour must be in the past relative to the current clock
        $currentSlot = getCurrentHourSlot();
        $opDateIsYesterday = ($opDate !== (new DateTime())->format('Y-m-d'));

        // During 00:xx window (opDate is yesterday): hours 1-23 are all past
        // During normal hours: hour must be < currentSlot
        if (!$opDateIsYesterday && $hour >= $currentSlot) {
            return [
                'success' => false,
                'message' => sprintf(
                    'Hour %02d:00 has not yet occurred — no explanation needed.',
                    $hour
                ),
            ];
        }

        $stmt = $db->prepare("
            INSERT INTO late_entry_log
                (voltage_level, user_id, iss_code, log_date, specific_hour, explanation)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['voltage_level'],
            $data['user_id'],
            $data['iss_code'],
            $data['log_date'],
            $hour,
            trim($data['explanation']),
        ]);

        return [
            'success' => true,
            'log_id'  => $db->lastInsertId(),
            'message' => sprintf(
                'Explanation logged. You may now enter the reading for %02d:00.',
                $hour
            ),
        ];
    }

    /**
     * Check whether a late-entry permission exists for user/op-date/hour/voltage.
     */
    public static function hasPermission(
        string $userId,
        string $opDate,
        int    $hour,
        string $voltageLevel = '11kV'
    ): bool {
        $db   = Database::connect();
        $stmt = $db->prepare("
            SELECT id FROM late_entry_log
            WHERE voltage_level = ?
              AND user_id       = ?
              AND log_date      = ?
              AND specific_hour = ?
            LIMIT 1
        ");
        $stmt->execute([$voltageLevel, $userId, $opDate, $hour]);
        return (bool)$stmt->fetch();
    }
}
