<?php
/**
 * Interruption11kv Model — Two-Stage Workflow (Amended v2)
 * Path: /app/models/Interruption11kv.php
 *
 * Amendments:
 *  1. PLANNED type → never requires approval (forced NO regardless of code setting)
 *  2. FK columns use current structure: fdr11kv_code, fdr33kv_code
 *  3. Stage 2 server-side: datetime_in must not be >30 min in the future
 *  4. Stage 2 blocked until approval_status = APPROVED for approval tickets
 *  5. Ticket format: "11" + 3-char feeder-name alpha slug + YYMMDDHHmm (15 chars)
 *     e.g. asikolaye @ 15:42 22-Feb-2026 → 11ask2602221542
 *  6. Edit/cancel allowed within 1 hour of started_at only, before any
 *     concurrence/approval; every action written to ticket_edit_cancel_log
 */
class Interruption11kv {

    // ──────────────────────────────────────────────────────────────────────────
    // TICKET GENERATION
    // Format: "11" + 3-char UPPERCASE slug + YYMMDDHHmm (current system time) = 15 chars
    // e.g. 11kV Ahmadu Bello Way logged at 23-Feb-2026 18:32 → 11ABW2602231832
    //      11kV Asikolaye     logged at 23-Feb-2026 18:35 → 11ASI2602231835
    //
    // Timestamp = NOW() at the moment the ticket is created, NOT datetime_out.
    // Slug is derived from feeder name initials (after stripping "11kV " prefix),
    // stored permanently in feeder_ticket_prefix, and never changes for that feeder.
    // If the natural initials clash with another feeder a random unused combo is used.
    // ──────────────────────────────────────────────────────────────────────────
    private static function generateTicket(
        string $feederCode,
        \PDO   $db
    ): string {
        $slug   = self::_getOrAssignSlug($feederCode, $db);
        $dtPart = date('ymdHi');                // YYMMDDHHmm — current system time

        $base   = '11' . $slug . $dtPart;       // 15 chars
        $ticket = $base;
        $n      = 0;

        while (true) {
            $ck = $db->prepare("SELECT id FROM interruptions_11kv WHERE ticket_number = ? LIMIT 1");
            $ck->execute([$ticket]);
            if (!$ck->fetch()) break;
            $n++;
            $ticket = $base . chr(64 + $n);     // 16 chars: ...A / ...B
            if ($n > 26) { $ticket = $base . $n; break; }
        }
        return $ticket;
    }

    // ── Slug helpers ──────────────────────────────────────────────────────────

    private static function _getOrAssignSlug(string $feederCode, \PDO $db): string {
        $st = $db->prepare(
            "SELECT slug FROM feeder_ticket_prefix WHERE feeder_code=? AND voltage_level='11kV' LIMIT 1"
        );
        $st->execute([$feederCode]);
        $row = $st->fetch(\PDO::FETCH_ASSOC);
        if ($row) return $row['slug'];

        // Resolve feeder name
        $fn = $db->prepare("SELECT fdr11kv_name FROM fdr11kv WHERE fdr11kv_code=? LIMIT 1");
        $fn->execute([$feederCode]);
        $nr   = $fn->fetch(\PDO::FETCH_ASSOC);
        $name = $nr ? $nr['fdr11kv_name'] : $feederCode;

        $preferred = self::_deriveSlug($name);
        $slug      = self::_resolveUniqueSlug($preferred, $feederCode, $db);

        $db->prepare(
            "INSERT OR IGNORE INTO feeder_ticket_prefix (feeder_code, voltage_level, slug) VALUES (?,'11kV',?)"
        )->execute([$feederCode, $slug]);

        return $slug;
    }

    /**
     * Derive preferred 3-char UPPERCASE slug.
     * Strip "11kV " prefix → take initials of remaining words → pad/trim to 3 chars.
     *
     * Examples:
     *   "11kV Ahmadu Bello Way" → strip → "Ahmadu Bello Way" → initials ABW → ABW
     *   "11kV Asikolaye"        → strip → "Asikolaye"        → initial A    → ASI (padded)
     *   "11kV Gwari Market"     → strip → "Gwari Market"     → initials GM  → GMX (padded)
     */
    private static function _deriveSlug(string $name): string {
        $stripped = preg_replace('/^\s*(11|33)\s*[kK][vV]\s*/u', '', trim($name));
        if (empty($stripped)) $stripped = $name;

        $words = array_values(array_filter(preg_split('/[\s\-_\/]+/', $stripped)));
        if (empty($words)) {
            $alpha = preg_replace('/[^A-Za-z]/', '', $name);
            return strtoupper(str_pad(substr($alpha, 0, 3), 3, 'X'));
        }

        $initials = '';
        foreach ($words as $w) {
            $a = preg_replace('/[^A-Za-z]/', '', $w);
            if ($a !== '') $initials .= strtoupper($a[0]);
        }

        if (strlen($initials) >= 3) return substr($initials, 0, 3);

        // Pad from first word
        $first  = preg_replace('/[^A-Za-z]/', '', $words[0]);
        $padded = strtoupper($initials . substr($first, 1));
        if (strlen($padded) >= 3) return substr($padded, 0, 3);

        if (isset($words[1])) {
            $second = preg_replace('/[^A-Za-z]/', '', $words[1]);
            $padded .= strtoupper(substr($second, 1));
            if (strlen($padded) >= 3) return substr($padded, 0, 3);
        }

        return str_pad(substr($padded, 0, 3), 3, 'X');
    }

    private static function _resolveUniqueSlug(string $preferred, string $feederCode, \PDO $db): string {
        $st = $db->prepare(
            "SELECT feeder_code FROM feeder_ticket_prefix WHERE voltage_level='11kV' AND slug=? LIMIT 1"
        );
        $st->execute([$preferred]);
        $row = $st->fetch(\PDO::FETCH_ASSOC);

        if (!$row || $row['feeder_code'] === $feederCode) return $preferred;

        $us = $db->prepare("SELECT slug FROM feeder_ticket_prefix WHERE voltage_level='11kV'");
        $us->execute();
        $usedSet = array_flip(array_column($us->fetchAll(\PDO::FETCH_ASSOC), 'slug'));

        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        for ($i = 0; $i < 300; $i++) {
            $c = $letters[random_int(0,25)] . $letters[random_int(0,25)] . $letters[random_int(0,25)];
            if (!isset($usedSet[$c])) return $c;
        }
        for ($a = 0; $a < 26; $a++)
            for ($b = 0; $b < 26; $b++)
                for ($c = 0; $c < 26; $c++) {
                    $candidate = $letters[$a].$letters[$b].$letters[$c];
                    if (!isset($usedSet[$candidate])) return $candidate;
                }

        throw new \RuntimeException('All 17,576 ticket slug slots exhausted for 11kV.');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // STAGE 1 — save initial fields, generate ticket
    // ──────────────────────────────────────────────────────────────────────────
    public static function stage1(array $data): array {
        $db = Database::connect();

        foreach (['fdr11kv_code','interruption_type','interruption_code','datetime_out','user_id'] as $f) {
            if (empty($data[$f])) return ['success' => false, 'message' => "Missing required field: {$f}"];
        }
        if (strtotime($data['datetime_out']) < strtotime(date('Y-m-d 00:00:00'))) {
            return ['success' => false, 'message' => 'Cannot log interruptions for past dates.'];
        }

        // Amendment 1: PLANNED never requires approval
        if (strtoupper(trim($data['interruption_type'])) === 'PLANNED') {
            $data['requires_approval'] = 'NO';
        }

        $needsApproval  = ($data['requires_approval'] ?? 'NO') === 'YES';
        $formStatus     = $needsApproval ? 'AWAITING_APPROVAL' : 'PENDING_COMPLETION';
        $approvalStatus = $needsApproval ? 'PENDING'           : 'NOT_REQUIRED';

        $ticket = self::generateTicket($data['fdr11kv_code'], $db);

        try {
            $db->prepare("
                INSERT INTO interruptions_11kv
                    (ticket_number, fdr11kv_code, interruption_type, interruption_code,
                     datetime_out, weather_condition, approval_note,
                     requires_approval, approval_status, form_status,
                     started_by, started_at, user_id)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW(),?)
            ")->execute([
                $ticket,
                $data['fdr11kv_code'],
                $data['interruption_type'],
                $data['interruption_code'],
                $data['datetime_out'],
                $data['weather_condition'] ?? null,
                $data['approval_note']     ?? null,
                $data['requires_approval'] ?? 'NO',
                $approvalStatus,
                $formStatus,
                $data['user_id'],
                $data['user_id'],
            ]);
            return [
                'success'           => true,
                'message'           => $needsApproval
                    ? 'Interruption logged and sent for UL3/UL4 approval. Stage 2 will unlock once approved.'
                    : 'Interruption logged. Return after the event ends to complete Stage 2.',
                'interruption_id'   => (int)$db->lastInsertId(),
                'ticket_number'     => $ticket,
                'requires_approval' => $needsApproval,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // STAGE 2 — fill restoration details
    // Amendment 3: datetime_in must not be >30 min ahead of NOW
    // Amendment 4: approval-required ticket blocked until approval_status=APPROVED
    // ──────────────────────────────────────────────────────────────────────────
    public static function stage2(int $id, array $data): array {
        $db = Database::connect();
        $st = $db->prepare("SELECT * FROM interruptions_11kv WHERE id = ?");
        $st->execute([$id]);
        $rec = $st->fetch(\PDO::FETCH_ASSOC);
        if (!$rec) return ['success' => false, 'message' => 'Record not found.'];

        // Amendment 4
        if ($rec['form_status'] === 'AWAITING_APPROVAL') {
            return ['success' => false,
                    'message' => 'This ticket is still awaiting UL3/UL4 approval. Stage 2 will unlock once approved.'];
        }
        if ($rec['form_status'] === 'CANCELLED') {
            return ['success' => false, 'message' => 'This ticket has been cancelled.'];
        }
        if (!in_array($rec['form_status'], ['PENDING_COMPLETION','PENDING_COMPLETION_APPROVED'])) {
            return ['success' => false, 'message' => 'This record has already been completed.'];
        }

        if (empty($data['datetime_in']))
            return ['success' => false, 'message' => 'Restoration time (Date/Time In) is required.'];

        $dtIn  = strtotime($data['datetime_in']);
        $dtOut = strtotime($rec['datetime_out']);

        if ($dtIn <= $dtOut)
            return ['success' => false,
                    'message' => 'Restoration time must be after the interruption start time.'];

        // Amendment 3: no more than 30 minutes in the future
        if ($dtIn > time() + 1800) {
            $minsAhead = (int)ceil(($dtIn - time()) / 60);
            return [
                'success'       => false,
                'late_entry'    => true,
                'minutes_ahead' => $minsAhead,
                'message'       => "The restoration time entered is {$minsAhead} minute(s) in the future. "
                                 . 'Please verify: the system only accepts times up to 30 minutes ahead of now.',
            ];
        }

        if (!isset($data['load_loss']) || (float)$data['load_loss'] < 0)
            return ['success' => false, 'message' => 'Load loss is required and cannot be negative.'];
        if (empty($data['reason_for_interruption']))
            return ['success' => false, 'message' => 'Reason for interruption is required.'];

        try {
            $db->prepare("
                UPDATE interruptions_11kv SET
                    datetime_in=?, load_loss=?, reason_for_interruption=?,
                    resolution=?, reason_for_delay=?, other_reasons=?,
                    form_status='COMPLETED', completed_by=?, completed_at=NOW()
                WHERE id=?
            ")->execute([
                $data['datetime_in'],
                $data['load_loss'],
                $data['reason_for_interruption'],
                $data['resolution']       ?? null,
                $data['reason_for_delay'] ?? null,
                $data['other_reasons']    ?? null,
                $data['completed_by'],
                $id,
            ]);
            return ['success' => true,
                    'message' => '11kV Interruption record completed successfully.',
                    'interruption_id' => $id];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // EDIT / CANCEL GUARD  (Amendment 6)
    // Returns ['allowed'=>bool, 'reason'=>string, 'seconds_left'=>int]
    // ──────────────────────────────────────────────────────────────────────────
    public static function canEditOrCancel(array $rec): array {
        // Gate 1 — already completed or cancelled
        if (in_array($rec['form_status'], ['COMPLETED','CANCELLED'])) {
            return ['allowed' => false,
                    'reason'  => 'Cannot edit/cancel a ' . strtolower($rec['form_status']) . ' ticket.'];
        }
        // Gate 2 — concurrence or approval already recorded
        if (in_array($rec['approval_status'] ?? '', ['APPROVED','CONCURRED'])) {
            return ['allowed' => false,
                    'reason'  => 'Approval or concurrence has already been recorded on this ticket.'];
        }
        // Gate 3 — 1-hour window
        $cutoff = strtotime($rec['started_at']) + 3600;
        $now    = time();
        if ($now > $cutoff) {
            return ['allowed' => false,
                    'reason'  => 'The 1-hour edit/cancel window has expired for this ticket.'];
        }
        return ['allowed' => true, 'seconds_left' => $cutoff - $now, 'cutoff_ts' => $cutoff];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // EDIT STAGE 1 FIELDS  (Amendment 6)
    // ──────────────────────────────────────────────────────────────────────────
    public static function editStage1(int $id, array $data, string $userId): array {
        $db = Database::connect();
        $st = $db->prepare("SELECT * FROM interruptions_11kv WHERE id = ?");
        $st->execute([$id]);
        $rec = $st->fetch(\PDO::FETCH_ASSOC);
        if (!$rec)                       return ['success'=>false,'message'=>'Record not found.'];
        if ($rec['user_id'] !== $userId) return ['success'=>false,'message'=>'You can only edit your own records.'];

        $guard = self::canEditOrCancel($rec);
        if (!$guard['allowed'])          return ['success'=>false,'message'=>$guard['reason']];

        // Amendment 1: PLANNED → no approval even when editing
        $newType = strtoupper(trim($data['interruption_type'] ?? $rec['interruption_type']));
        if ($newType === 'PLANNED') $data['requires_approval'] = 'NO';

        $oldValues = array_intersect_key($rec, array_flip([
            'interruption_type','interruption_code','datetime_out',
            'weather_condition','approval_note','requires_approval',
        ]));

        try {
            $db->prepare("
                UPDATE interruptions_11kv SET
                    interruption_type=?, interruption_code=?, datetime_out=?,
                    weather_condition=?, approval_note=?
                WHERE id=?
            ")->execute([
                $data['interruption_type'] ?? $rec['interruption_type'],
                $data['interruption_code'] ?? $rec['interruption_code'],
                $data['datetime_out']      ?? $rec['datetime_out'],
                $data['weather_condition'] ?? $rec['weather_condition'],
                $data['approval_note']     ?? $rec['approval_note'],
                $id,
            ]);
            self::_logAction($rec['ticket_number'], '11kV', 'EDIT',
                             $userId, $data['edit_reason'] ?? 'Stage 1 correction', $oldValues, $db);
            return ['success'=>true,'message'=>'Ticket updated successfully.'];
        } catch (\Exception $e) {
            return ['success'=>false,'message'=>'Database error: '.$e->getMessage()];
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // CANCEL TICKET  (Amendment 6)
    // ──────────────────────────────────────────────────────────────────────────
    public static function cancel(int $id, string $userId, string $reason): array {
        $db = Database::connect();
        $st = $db->prepare("SELECT * FROM interruptions_11kv WHERE id = ?");
        $st->execute([$id]);
        $rec = $st->fetch(\PDO::FETCH_ASSOC);
        if (!$rec)                       return ['success'=>false,'message'=>'Record not found.'];
        if ($rec['user_id'] !== $userId) return ['success'=>false,'message'=>'You can only cancel your own records.'];
        if (empty(trim($reason)))        return ['success'=>false,'message'=>'A cancellation reason is required.'];

        $guard = self::canEditOrCancel($rec);
        if (!$guard['allowed'])          return ['success'=>false,'message'=>$guard['reason']];

        try {
            $db->prepare("
                UPDATE interruptions_11kv
                SET form_status='CANCELLED', approval_status='CANCELLED'
                WHERE id=?
            ")->execute([$id]);
            self::_logAction($rec['ticket_number'], '11kV', 'CANCEL', $userId, $reason, null, $db);
            return ['success'=>true,'message'=>"Ticket {$rec['ticket_number']} cancelled successfully."];
        } catch (\Exception $e) {
            return ['success'=>false,'message'=>'Database error: '.$e->getMessage()];
        }
    }

    // Audit log writer
    private static function _logAction(
        string $ticket, string $voltage, string $action,
        string $by, string $reason, ?array $old, \PDO $db
    ): void {
        $db->prepare("
            INSERT INTO ticket_edit_cancel_log
                (ticket_number, voltage_level, action_type, action_by, reason, old_values)
            VALUES (?,?,?,?,?,?)
        ")->execute([$ticket, $voltage, $action, $by, $reason, $old ? json_encode($old) : null]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // UNLOCK AFTER APPROVAL
    // ──────────────────────────────────────────────────────────────────────────
    public static function unlockAfterApproval(int $id): bool {
        $db = Database::connect();
        $st = $db->prepare("
            UPDATE interruptions_11kv
            SET form_status='PENDING_COMPLETION_APPROVED', approval_status='APPROVED'
            WHERE id=? AND form_status='AWAITING_APPROVAL'
        ");
        $st->execute([$id]);
        return $st->rowCount() > 0;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // GETTERS
    // ──────────────────────────────────────────────────────────────────────────
    public static function getById(int $id): ?array {
        $db = Database::connect();
        $st = $db->prepare("
            SELECT i.*, f.fdr11kv_name, t.fdr33kv_name AS parent_feeder,
                   u.staff_name AS logger_name, c.staff_name AS completer_name
            FROM interruptions_11kv i
            INNER JOIN fdr11kv f      ON f.fdr11kv_code = i.fdr11kv_code
            LEFT  JOIN fdr33kv t      ON t.fdr33kv_code = f.fdr33kv_code
            LEFT  JOIN staff_details u ON u.payroll_id  = i.user_id
            LEFT  JOIN staff_details c ON c.payroll_id  = i.completed_by
            WHERE i.id = ?
        ");
        $st->execute([$id]);
        return $st->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public static function getByTicket(string $ticket): ?array {
        $db = Database::connect();
        $st = $db->prepare("
            SELECT i.*, f.fdr11kv_name, t.fdr33kv_name AS parent_feeder,
                   u.staff_name AS logger_name, c.staff_name AS completer_name
            FROM interruptions_11kv i
            INNER JOIN fdr11kv f      ON f.fdr11kv_code = i.fdr11kv_code
            LEFT  JOIN fdr33kv t      ON t.fdr33kv_code = f.fdr33kv_code
            LEFT  JOIN staff_details u ON u.payroll_id  = i.user_id
            LEFT  JOIN staff_details c ON c.payroll_id  = i.completed_by
            WHERE i.ticket_number = ?
        ");
        $st->execute([$ticket]);
        return $st->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public static function myRequests(string $userId): array {
        $db = Database::connect();
        $st = $db->prepare("
            SELECT i.*, f.fdr11kv_name, t.fdr33kv_name AS parent_feeder,
                   u.staff_name AS logger_name
            FROM interruptions_11kv i
            INNER JOIN fdr11kv f      ON f.fdr11kv_code = i.fdr11kv_code
            LEFT  JOIN fdr33kv t      ON t.fdr33kv_code = f.fdr33kv_code
            LEFT  JOIN staff_details u ON u.payroll_id  = i.user_id
            WHERE i.user_id = ?
            ORDER BY i.started_at DESC
        ");
        $st->execute([$userId]);
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function byFeeder(string $code, ?string $df=null, ?string $dt=null): array {
        $db = Database::connect();
        $q  = "SELECT i.*, f.fdr11kv_name, u.staff_name AS logger_name
               FROM interruptions_11kv i
               INNER JOIN fdr11kv f      ON f.fdr11kv_code = i.fdr11kv_code
               LEFT  JOIN staff_details u ON u.payroll_id  = i.user_id
               WHERE i.fdr11kv_code = ? AND i.form_status != 'CANCELLED'";
        $p = [$code];
        if ($df && $dt) { $q .= " AND DATE(i.datetime_out) BETWEEN ? AND ?"; $p[]=$df; $p[]=$dt; }
        $q .= " ORDER BY i.datetime_out DESC";
        $st = $db->prepare($q); $st->execute($p);
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }

    public static function getStats(string $code, ?string $df=null, ?string $dt=null): array {
        $db = Database::connect();
        $q  = "SELECT COUNT(*) as total_interruptions, SUM(load_loss) as total_load_loss,
                      AVG(load_loss) as avg_load_loss, SUM(duration) as total_duration,
                      AVG(duration) as avg_duration, MAX(duration) as max_duration,
                      SUM(CASE WHEN reason_for_delay IS NOT NULL THEN 1 ELSE 0 END) as delayed_restorations
               FROM interruptions_11kv
               WHERE fdr11kv_code = ? AND form_status NOT IN ('CANCELLED')";
        $p = [$code];
        if ($df && $dt) { $q .= " AND DATE(datetime_out) BETWEEN ? AND ?"; $p[]=$df; $p[]=$dt; }
        $st = $db->prepare($q); $st->execute($p);
        return $st->fetch(\PDO::FETCH_ASSOC) ?: [];
    }

    public static function getTypeBreakdown(string $code): array {
        $db = Database::connect();
        $st = $db->prepare("
            SELECT interruption_type, COUNT(*) as count,
                   SUM(duration) as total_duration, AVG(duration) as avg_duration
            FROM interruptions_11kv
            WHERE fdr11kv_code = ? AND form_status != 'CANCELLED'
            GROUP BY interruption_type ORDER BY count DESC
        ");
        $st->execute([$code]);
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }
}
