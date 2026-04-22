<?php
/**
 * Interruption Model (33kV) — Two-Stage Workflow
 * Path: /app/models/Interruption.php
 *
 * form_status values:
 *   PENDING_COMPLETION          → Stage 1 saved, awaiting Stage 2
 *   AWAITING_APPROVAL           → Approval required; Stage 2 locked until approved
 *   PENDING_COMPLETION_APPROVED → Approval granted; user may do Stage 2
 *   COMPLETED                   → Fully submitted
 *
 * Ticket format (Issue 3):
 *   "33" + 3-char UPPERCASE slug + YYMMDDHHmm from datetime_out = 15 chars
 *   e.g. 33kV Jere on 27-Feb-2026 18:05 → 33JER2602271805
 *
 * Slug derivation (see _deriveSlug / _getOrAssignSlug):
 *   Strip "33kV " prefix, take initials of remaining words (uppercase).
 *   Pad/trim to exactly 3 chars.  Stored permanently in feeder_ticket_prefix.
 *   Collision with another feeder → random unused 3-char combo.
 */
class Interruption {

    // ─────────────────────────────────────────────────────────────────────────
    // TICKET GENERATION
    // Format: "33" + 3-char UPPERCASE slug + YYMMDDHHmm (current system time) = 15 chars
    // e.g. 33kV Jere logged at 23-Feb-2026 18:32 → 33JER2602231832
    //
    // Timestamp = NOW() at the moment the ticket is created, NOT datetime_out.
    // Slug is fetched-or-assigned permanently via feeder_ticket_prefix table.
    // ─────────────────────────────────────────────────────────────────────────
    private static function generateTicket(
        string $feederCode,
        \PDO   $db
    ): string {
        $slug   = self::_getOrAssignSlug($feederCode, $db);
        $dtPart = date('ymdHi', time() + 3600); // YYMMDDHHmm — system time +1 hour offset

        $base   = '33' . $slug . $dtPart;       // 15 chars
        $ticket = $base;
        $n      = 0;

        while (true) {
            $ck = $db->prepare("SELECT id FROM interruptions WHERE ticket_number = ? LIMIT 1");
            $ck->execute([$ticket]);
            if (!$ck->fetch()) break;
            $n++;
            $ticket = $base . chr(64 + $n);     // 16 chars: ...A / ...B / etc.
            if ($n > 26) { $ticket = $base . $n; break; }
        }
        return $ticket;
    }

    // ── Slug helpers ──────────────────────────────────────────────────────────

    /**
     * Return the permanent 3-char slug for $feederCode (33kV tier).
     * Reads from feeder_ticket_prefix; assigns and stores if absent.
     */
    private static function _getOrAssignSlug(string $feederCode, \PDO $db): string {
        // Check existing assignment
        $st = $db->prepare(
            "SELECT slug FROM feeder_ticket_prefix WHERE feeder_code=? AND voltage_level='33kV' LIMIT 1"
        );
        $st->execute([$feederCode]);
        $row = $st->fetch(\PDO::FETCH_ASSOC);
        if ($row) return $row['slug'];

        // Resolve feeder name
        $fn = $db->prepare("SELECT fdr33kv_name FROM fdr33kv WHERE fdr33kv_code=? LIMIT 1");
        $fn->execute([$feederCode]);
        $nameRow = $fn->fetch(\PDO::FETCH_ASSOC);
        $name    = $nameRow ? $nameRow['fdr33kv_name'] : $feederCode;

        $preferred = self::_deriveSlug($name, '33kV');
        $slug      = self::_resolveUniqueSlug($preferred, $feederCode, '33kV', $db);

        $db->prepare(
            "INSERT OR IGNORE INTO feeder_ticket_prefix (feeder_code, voltage_level, slug) VALUES (?,?,?)"
        )->execute([$feederCode, '33kV', $slug]);

        return $slug;
    }

    /**
     * Derive preferred 3-char UPPERCASE slug from a feeder name.
     *   1. Strip voltage prefix (11kV/33kV, case-insensitive).
     *   2. Take the first letter of each remaining word → initials.
     *   3. If exactly 3 → done.
     *   4. If > 3 → use first 3 initials.
     *   5. If < 3 → pad with subsequent letters from first remaining word.
     *   6. Uppercase, alpha only.
     *
     * Examples:
     *   "33kV Jere"            → strip "33kV " → "Jere" → J → pad → JER
     *   "33kV North East Ring" → strip "33kV " → NER
     *   "11kV Ahmadu Bello Way"→ strip "11kV " → ABW
     */
    private static function _deriveSlug(string $name, string $voltage): string {
        // Strip voltage prefix
        $stripped = preg_replace('/^\s*(11|33)\s*[kK][vV]\s*/u', '', trim($name));
        if (empty($stripped)) $stripped = $name;

        // Split into words
        $words = array_values(array_filter(preg_split('/[\s\-_\/]+/', $stripped)));
        if (empty($words)) {
            $alpha = preg_replace('/[^A-Za-z]/', '', $name);
            return strtoupper(str_pad(substr($alpha, 0, 3), 3, 'X'));
        }

        // Build initials
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

        // Pad from second word
        if (isset($words[1])) {
            $second = preg_replace('/[^A-Za-z]/', '', $words[1]);
            $padded .= strtoupper(substr($second, 1));
            if (strlen($padded) >= 3) return substr($padded, 0, 3);
        }

        return str_pad(substr($padded, 0, 3), 3, 'X');
    }

    /**
     * Check if $preferred is free within $voltage tier.
     * If taken by another feeder → pick random unused combo.
     */
    private static function _resolveUniqueSlug(
        string $preferred,
        string $feederCode,
        string $voltage,
        \PDO   $db
    ): string {
        $st = $db->prepare(
            "SELECT feeder_code FROM feeder_ticket_prefix WHERE voltage_level=? AND slug=? LIMIT 1"
        );
        $st->execute([$voltage, $preferred]);
        $row = $st->fetch(\PDO::FETCH_ASSOC);

        // Free, or already ours
        if (!$row || $row['feeder_code'] === $feederCode) return $preferred;

        // Fetch all used slugs for this voltage tier
        $us = $db->prepare("SELECT slug FROM feeder_ticket_prefix WHERE voltage_level=?");
        $us->execute([$voltage]);
        $usedSet = array_flip(array_column($us->fetchAll(\PDO::FETCH_ASSOC), 'slug'));

        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        // Fast random picks (usually succeeds in 1-3 tries)
        for ($i = 0; $i < 300; $i++) {
            $c = $letters[random_int(0,25)] . $letters[random_int(0,25)] . $letters[random_int(0,25)];
            if (!isset($usedSet[$c])) return $c;
        }
        // Exhaustive scan (26^3 = 17,576 combinations)
        for ($a = 0; $a < 26; $a++)
            for ($b = 0; $b < 26; $b++)
                for ($c = 0; $c < 26; $c++) {
                    $candidate = $letters[$a].$letters[$b].$letters[$c];
                    if (!isset($usedSet[$candidate])) return $candidate;
                }

        throw new \RuntimeException("All 17,576 ticket slug slots exhausted for {$voltage}.");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STAGE 1 — save initial fields, generate ticket
    // ─────────────────────────────────────────────────────────────────────────
    public static function stage1(array $data): array {
        $db = Database::connect();

        // Basic validation
        foreach (['fdr33kv_code','interruption_type','interruption_code','datetime_out','user_id'] as $f) {
            if (empty($data[$f])) return ['success' => false, 'message' => "Missing required field: {$f}"];
        }

        if (strtotime($data['datetime_out']) < strtotime(date('Y-m-d 00:00:00'))) {
            return ['success' => false, 'message' => 'Cannot log interruptions for past dates.'];
        }

        $ticket        = self::generateTicket($data['fdr33kv_code'], $db);
        $needsApproval = ($data['requires_approval'] ?? 'NO') === 'YES';

        // For planned interruptions needing approval → AWAITING_APPROVAL straight away
        // For normal interruptions → PENDING_COMPLETION
        $formStatus     = $needsApproval ? 'AWAITING_APPROVAL'   : 'PENDING_COMPLETION';
        $approvalStatus = $needsApproval ? 'PENDING'              : 'NOT_REQUIRED';

        try {
            $stmt = $db->prepare("
                INSERT INTO interruptions
                    (ticket_number, fdr33kv_code, interruption_type, interruption_code,
                     datetime_out, weather_condition, approval_note,
                     requires_approval, approval_status, form_status,
                     started_by, started_at, user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
            ");
            $stmt->execute([
                $ticket,
                $data['fdr33kv_code'],
                $data['interruption_type'],
                $data['interruption_code'],
                $data['datetime_out'],
                $data['weather_condition']  ?? null,
                $data['approval_note']      ?? null,
                $data['requires_approval']  ?? 'NO',
                $approvalStatus,
                $formStatus,
                $data['user_id'],
                $data['user_id'],
            ]);

            return [
                'success'          => true,
                'message'          => $needsApproval
                    ? 'Interruption saved and sent for approval. You can complete Stage 2 once approval is granted.'
                    : 'Interruption saved. Return to complete the form after the event ends.',
                'interruption_id'  => (int)$db->lastInsertId(),
                'ticket_number'    => $ticket,
                'requires_approval'=> $needsApproval,
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // STAGE 2 — complete the record after the event ends
    // ─────────────────────────────────────────────────────────────────────────
    public static function stage2(int $id, array $data): array {
        $db = Database::connect();

        // Fetch the record — must be in a completable state
        $st = $db->prepare("SELECT * FROM interruptions WHERE id = ?");
        $st->execute([$id]);
        $rec = $st->fetch(PDO::FETCH_ASSOC);

        if (!$rec) {
            return ['success' => false, 'message' => 'Interruption record not found.'];
        }

        $completable = in_array($rec['form_status'], ['PENDING_COMPLETION', 'PENDING_COMPLETION_APPROVED']);
        if (!$completable) {
            if ($rec['form_status'] === 'AWAITING_APPROVAL') {
                return ['success' => false, 'message' => 'This interruption is awaiting approval. Stage 2 will unlock once approved.'];
            }
            return ['success' => false, 'message' => 'This interruption is already completed.'];
        }

        // Validate Stage-2 fields
        if (empty($data['datetime_in'])) {
            return ['success' => false, 'message' => 'Restoration time (Date/Time In) is required.'];
        }
        if (strtotime($data['datetime_in']) <= strtotime($rec['datetime_out'])) {
            return ['success' => false, 'message' => 'Restoration time must be after interruption start time.'];
        }
        if (!isset($data['load_loss']) || (float)$data['load_loss'] < 0) {
            return ['success' => false, 'message' => 'Load loss is required and cannot be negative.'];
        }
        if (empty($data['reason_for_interruption'])) {
            return ['success' => false, 'message' => 'Reason for interruption is required.'];
        }

        try {
            $stmt = $db->prepare("
                UPDATE interruptions SET
                    datetime_in             = ?,
                    load_loss               = ?,
                    reason_for_interruption = ?,
                    resolution              = ?,
                    reason_for_delay        = ?,
                    other_reasons           = ?,
                    form_status             = 'COMPLETED',
                    completed_by            = ?,
                    completed_at            = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $data['datetime_in'],
                $data['load_loss'],
                $data['reason_for_interruption'],
                $data['resolution']        ?? null,
                $data['reason_for_delay']  ?? null,
                $data['other_reasons']     ?? null,
                $data['completed_by'],
                $id,
            ]);

            if ($stmt->rowCount() === 0) {
                return ['success' => false, 'message' => 'No changes were saved. Please try again.'];
            }

            return [
                'success'         => true,
                'message'         => 'Interruption completed successfully.',
                'interruption_id' => $id,
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Called by original interruption_log.php — if all fields present, does
    // stage1 + stage2 in one go (legacy single-submission path, not used in
    // the new two-stage flow but preserved for compatibility).
    // ─────────────────────────────────────────────────────────────────────────
    public static function log(array $data): array {
        $s1 = self::stage1([
            'fdr33kv_code'      => $data['fdr33kv_code'],
            'interruption_type' => $data['interruption_type'],
            'interruption_code' => $data['interruption_code'],
            'datetime_out'      => $data['datetime_out'],
            'weather_condition' => $data['weather_condition'] ?? null,
            'approval_note'     => $data['approval_note']     ?? null,
            'requires_approval' => $data['requires_approval'] ?? 'NO',
            'user_id'           => $data['user_id'],
        ]);
        if (!$s1['success'] || empty($data['datetime_in'])) return $s1;

        return self::stage2($s1['interruption_id'], [
            'datetime_in'             => $data['datetime_in'],
            'load_loss'               => $data['load_loss']               ?? 0,
            'reason_for_interruption' => $data['reason_for_interruption'] ?? '',
            'resolution'              => $data['resolution']              ?? null,
            'reason_for_delay'        => $data['reason_for_delay']        ?? null,
            'other_reasons'           => $data['other_reasons']           ?? null,
            'completed_by'            => $data['user_id'],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Called by approval model to unlock Stage 2 after approval granted
    // ─────────────────────────────────────────────────────────────────────────
    public static function unlockAfterApproval(int $id): bool {
        $db = Database::connect();
        $st = $db->prepare("
            UPDATE interruptions
            SET form_status = 'PENDING_COMPLETION_APPROVED'
            WHERE id = ? AND form_status = 'AWAITING_APPROVAL'
        ");
        $st->execute([$id]);
        return $st->rowCount() > 0;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Queries
    // ─────────────────────────────────────────────────────────────────────────
    public static function getById(int $id): ?array {
        $db   = Database::connect();
        $stmt = $db->prepare("
            SELECT i.*, f.fdr33kv_name,
                   u.staff_name  AS logger_name,
                   c.staff_name  AS completer_name
            FROM interruptions i
            INNER JOIN fdr33kv f      ON f.fdr33kv_code = i.fdr33kv_code
            LEFT JOIN staff_details u ON u.payroll_id   = i.user_id
            LEFT JOIN staff_details c ON c.payroll_id   = i.completed_by
            WHERE i.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public static function getByTicket(string $ticket): ?array {
        $db   = Database::connect();
        $stmt = $db->prepare("
            SELECT i.*, f.fdr33kv_name,
                   u.staff_name AS logger_name,
                   c.staff_name AS completer_name
            FROM interruptions i
            INNER JOIN fdr33kv f      ON f.fdr33kv_code = i.fdr33kv_code
            LEFT JOIN staff_details u ON u.payroll_id   = i.user_id
            LEFT JOIN staff_details c ON c.payroll_id   = i.completed_by
            WHERE i.ticket_number = ?
        ");
        $stmt->execute([$ticket]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    // My Requests — all interruptions belonging to this user
    public static function myRequests(string $userId): array {
        $db   = Database::connect();
        $stmt = $db->prepare("
            SELECT i.*, f.fdr33kv_name,
                   u.staff_name AS logger_name
            FROM interruptions i
            INNER JOIN fdr33kv f      ON f.fdr33kv_code = i.fdr33kv_code
            LEFT JOIN staff_details u ON u.payroll_id   = i.user_id
            WHERE i.user_id = ?
            ORDER BY i.started_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function byFeeder(string $fdr33_code, ?string $df = null, ?string $dt = null): array {
        $db = Database::connect();
        $q  = "SELECT i.*, f.fdr33kv_name, u.staff_name AS logger_name
               FROM interruptions i
               INNER JOIN fdr33kv f ON f.fdr33kv_code = i.fdr33kv_code
               LEFT JOIN staff_details u ON u.payroll_id = i.user_id
               WHERE i.fdr33kv_code = ?";
        $p  = [$fdr33_code];
        if ($df && $dt) { $q .= " AND DATE(i.datetime_out) BETWEEN ? AND ?"; $p[] = $df; $p[] = $dt; }
        $q .= " ORDER BY i.datetime_out DESC";
        $stmt = $db->prepare($q); $stmt->execute($p);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function update(int $id, array $data): array {
        $db   = Database::connect();
        $stmt = $db->prepare("
            UPDATE interruptions SET
                interruption_type=?, interruption_code=?,
                datetime_out=?, datetime_in=?, load_loss=?,
                reason_for_interruption=?, resolution=?,
                weather_condition=?, reason_for_delay=?, other_reasons=?
            WHERE id=?
        ");
        $stmt->execute([
            $data['interruption_type'],       $data['interruption_code'] ?? '',
            $data['datetime_out'],            $data['datetime_in']       ?? null,
            $data['load_loss']       ?? null, $data['reason_for_interruption'] ?? null,
            $data['resolution']      ?? null, $data['weather_condition'] ?? null,
            $data['reason_for_delay']?? null, $data['other_reasons']     ?? null,
            $id,
        ]);
        return $stmt->rowCount() > 0
            ? ['success' => true,  'message' => 'Updated successfully']
            : ['success' => false, 'message' => 'No changes made'];
    }

    public static function delete(int $id, string $userId): array {
        $db = Database::connect();
        $st = $db->prepare("SELECT user_id FROM interruptions WHERE id = ?");
        $st->execute([$id]);
        $r  = $st->fetch(PDO::FETCH_ASSOC);
        if (!$r)                       return ['success' => false, 'message' => 'Not found'];
        if ($r['user_id'] !== $userId) return ['success' => false, 'message' => 'You can only delete your own records'];
        $db->prepare("DELETE FROM interruptions WHERE id = ?")->execute([$id]);
        return ['success' => true, 'message' => 'Deleted successfully'];
    }

    public static function getStats(string $fdr33_code, ?string $df = null, ?string $dt = null): array {
        $db = Database::connect();
        $q  = "SELECT COUNT(*) as total_interruptions,
                      SUM(load_loss) as total_load_loss, AVG(load_loss) as avg_load_loss,
                      SUM(duration)  as total_duration,  AVG(duration)  as avg_duration,
                      MAX(duration)  as max_duration,
                      SUM(CASE WHEN reason_for_delay IS NOT NULL THEN 1 ELSE 0 END) as delayed_restorations
               FROM interruptions WHERE fdr33kv_code = ?";
        $p = [$fdr33_code];
        if ($df && $dt) { $q .= " AND DATE(datetime_out) BETWEEN ? AND ?"; $p[] = $df; $p[] = $dt; }
        $stmt = $db->prepare($q); $stmt->execute($p);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public static function getTypeBreakdown(string $fdr33_code): array {
        $db   = Database::connect();
        $stmt = $db->prepare("SELECT interruption_type, COUNT(*) as count,
                               SUM(duration) as total_duration, AVG(duration) as avg_duration
                               FROM interruptions WHERE fdr33kv_code = ?
                               GROUP BY interruption_type ORDER BY count DESC");
        $stmt->execute([$fdr33_code]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
