<?php
/**
 * Correction Model - WITH BLANK HOUR SUPPORT
 * Path: /app/models/Correction.php
 *
 * ADDITION (existing logic unchanged):
 *   - Blank/unpopulated hours are now accepted.
 *   - old_value = NULL for blank hours (no longer blocked).
 *   - blank_hour_reason stored separately and flagged to reviewers.
 *   - managerApprove() behaviour unchanged — it still returns error if row
 *     not found; the manager sees the flag and knows what they are approving.
 *     (Row creation is a separate operational decision left to existing flow.)
 *
 * DDL required (run once):
 *   ALTER TABLE load_corrections
 *       ADD COLUMN blank_hour_reason TEXT NULL AFTER reason;
 */

class Correction {

    /**
     * Request a correction (UL1/UL2)
     * Now accepts blank/unpopulated hours — old_value stored as NULL,
     * blank_hour_reason stored separately and flagged to reviewers.
     */
    public static function request(array $data): array {
        $db = Database::connect();

        // Validate required fields (same as before)
        $required = ['feeder_code', 'entry_date', 'entry_hour', 'field_to_correct', 'new_value', 'reason', 'requested_by'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Missing field: {$field}"];
            }
        }

        // FIXED: Proper table and column names for 33kV (unchanged)
        $table     = $data['correction_type'] === '33kV' ? 'fdr33kv_data' : 'fdr11kv_data';
        $feederCol = $data['correction_type'] === '33kV' ? 'Fdr33kv_code' : 'Fdr11kv_code';

        // Get old value from database — NULL is now acceptable for blank hours
        $stmt = $db->prepare("
            SELECT {$data['field_to_correct']}
            FROM {$table}
            WHERE entry_date = ? AND {$feederCol} = ? AND entry_hour = ?
            LIMIT 1
        ");
        $stmt->execute([$data['entry_date'], $data['feeder_code'], $data['entry_hour']]);
        $old = $stmt->fetch(PDO::FETCH_ASSOC);

        // Determine if this is a blank hour
        $isBlankHour = ($old === false);

        // If blank hour, blank_hour_reason is required
        if ($isBlankHour && empty($data['blank_hour_reason'])) {
            return ['success' => false, 'message' => 'Please provide a reason explaining why this hour was left blank.'];
        }

        $oldValue        = $isBlankHour ? null : $old[$data['field_to_correct']];
        $blankHourReason = $isBlankHour ? trim($data['blank_hour_reason']) : null;

        // ── max_load server-side guard (load_read corrections only) ───────────
        if ($data['field_to_correct'] === 'load_read' && is_numeric($data['new_value'])) {
            $newLoad  = (float)$data['new_value'];
            $maxTable = $correctionType === '33kV' ? 'fdr33kv' : 'fdr11kv';
            $maxCol   = $correctionType === '33kV' ? 'fdr33kv_code' : 'fdr11kv_code';

            $maxStmt  = $db->prepare("SELECT max_load FROM {$maxTable} WHERE {$maxCol} = ? LIMIT 1");
            $maxStmt->execute([$data['feeder_code']]);
            $maxRow   = $maxStmt->fetch(PDO::FETCH_ASSOC);

            if ($maxRow && $maxRow['max_load'] !== null && $newLoad > (float)$maxRow['max_load']) {
                return [
                    'success'  => false,
                    'message'  => 'The new load value of ' . $newLoad . ' MW exceeds the maximum allowed load of '
                                  . $maxRow['max_load'] . ' MW for this feeder.',
                    'max_load' => (float)$maxRow['max_load'],
                ];
            }
        }

        try {
            $stmt = $db->prepare("
                INSERT INTO load_corrections
                (feeder_code, entry_date, entry_hour, correction_type, field_to_correct,
                 old_value, new_value, reason, blank_hour_reason, requested_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $data['feeder_code'],
                $data['entry_date'],
                $data['entry_hour'],
                $data['correction_type'] ?? '11kV',
                $data['field_to_correct'],
                $oldValue,
                $data['new_value'],
                $data['reason'],
                $blankHourReason,
                $data['requested_by']
            ]);

            $msg = $isBlankHour
                ? 'Correction request submitted for a blank hour. Reviewers have been notified.'
                : 'Correction request submitted successfully.';

            return ['success' => true, 'message' => $msg];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Get pending corrections (for Analyst/Manager) — unchanged
     */
    public static function pending(string $status = 'PENDING'): array {
        $db = Database::connect();

        $stmt = $db->prepare("
            SELECT
                c.*,
                s.staff_name as requester_name,
                CASE
                    WHEN c.correction_type = '11kV' THEN f11.fdr11kv_name
                    WHEN c.correction_type = '33kV' THEN f33.fdr33kv_name
                END as feeder_name
            FROM load_corrections c
            INNER JOIN staff_details s  ON s.payroll_id   = c.requested_by
            LEFT JOIN fdr11kv f11       ON f11.fdr11kv_code = c.feeder_code AND c.correction_type = '11kV'
            LEFT JOIN fdr33kv f33       ON f33.fdr33kv_code = c.feeder_code AND c.correction_type = '33kV'
            WHERE c.status = ?
            ORDER BY c.requested_at DESC
        ");

        $stmt->execute([$status]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get user's correction requests — unchanged
     */
    public static function myRequests(string $payroll_id): array {
        $db = Database::connect();

        $stmt = $db->prepare("
            SELECT
                c.*,
                CASE
                    WHEN c.correction_type = '11kV' THEN f11.fdr11kv_name
                    WHEN c.correction_type = '33kV' THEN f33.fdr33kv_name
                END as feeder_name,
                a.staff_name as analyst_name,
                m.staff_name as manager_name
            FROM load_corrections c
            LEFT JOIN fdr11kv f11    ON f11.fdr11kv_code = c.feeder_code AND c.correction_type = '11kV'
            LEFT JOIN fdr33kv f33    ON f33.fdr33kv_code = c.feeder_code AND c.correction_type = '33kV'
            LEFT JOIN staff_details a ON a.payroll_id = c.analyst_id
            LEFT JOIN staff_details m ON m.payroll_id = c.manager_id
            WHERE c.requested_by = ?
            ORDER BY c.requested_at DESC
        ");

        $stmt->execute([$payroll_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Analyst approval — unchanged
     */
    public static function analystApprove(int $id, string $analyst_id, ?string $remarks = null): array {
        $db = Database::connect();

        try {
            $stmt = $db->prepare("
                UPDATE load_corrections
                SET status = 'ANALYST_APPROVED',
                    analyst_id = ?,
                    analyst_remarks = ?,
                    analyst_action_at = NOW()
                WHERE id = ? AND status = 'PENDING'
            ");

            $stmt->execute([$analyst_id, $remarks, $id]);

            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Correction approved by analyst'];
            } else {
                return ['success' => false, 'message' => 'Correction not found or already processed'];
            }

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Analyst rejection — unchanged
     */
    public static function analystReject(int $id, string $analyst_id, string $remarks): array {
        $db = Database::connect();

        try {
            $stmt = $db->prepare("
                UPDATE load_corrections
                SET status = 'REJECTED',
                    analyst_id = ?,
                    analyst_remarks = ?,
                    analyst_action_at = NOW()
                WHERE id = ? AND status = 'PENDING'
            ");

            $stmt->execute([$analyst_id, $remarks, $id]);

            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Correction rejected'];
            } else {
                return ['success' => false, 'message' => 'Correction not found or already processed'];
            }

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Manager final approval — unchanged
     */
    public static function managerApprove(int $id, string $manager_id, ?string $remarks = null): array {
        $db = Database::connect();

        try {
            $db->beginTransaction();

            // Get correction details
            $stmt = $db->prepare("SELECT * FROM load_corrections WHERE id = ? AND status = 'ANALYST_APPROVED'");
            $stmt->execute([$id]);
            $correction = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$correction) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Correction not found or not analyst-approved'];
            }

            // FIXED: Proper table and column names for 33kV
            $table     = $correction['correction_type'] === '33kV' ? 'fdr33kv_data' : 'fdr11kv_data';
            $feederCol = $correction['correction_type'] === '33kV' ? 'Fdr33kv_code' : 'Fdr11kv_code';

            // Apply the correction to actual data table
            $updateStmt = $db->prepare("
                UPDATE {$table}
                SET {$correction['field_to_correct']} = ?
                WHERE entry_date = ? AND {$feederCol} = ? AND entry_hour = ?
            ");

            $updateStmt->execute([
                $correction['new_value'],
                $correction['entry_date'],
                $correction['feeder_code'],
                $correction['entry_hour']
            ]);

            // Check if the update actually affected any rows
            if ($updateStmt->rowCount() === 0) {
                $db->rollBack();
                return ['success' => false, 'message' => 'Data entry not found in ' . $table . ' table'];
            }

            // Update correction status
            $statusStmt = $db->prepare("
                UPDATE load_corrections
                SET status = 'MANAGER_APPROVED',
                    manager_id = ?,
                    manager_remarks = ?,
                    manager_action_at = NOW()
                WHERE id = ?
            ");

            $statusStmt->execute([$manager_id, $remarks, $id]);

            $db->commit();
            return ['success' => true, 'message' => 'Correction approved and applied successfully'];

        } catch (Exception $e) {
            $db->rollBack();
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Manager rejection — unchanged
     */
    public static function managerReject(int $id, string $manager_id, string $remarks): array {
        $db = Database::connect();

        try {
            $stmt = $db->prepare("
                UPDATE load_corrections
                SET status = 'REJECTED',
                    manager_id = ?,
                    manager_remarks = ?,
                    manager_action_at = NOW()
                WHERE id = ? AND status = 'ANALYST_APPROVED'
            ");

            $stmt->execute([$manager_id, $remarks, $id]);

            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Correction rejected by manager'];
            } else {
                return ['success' => false, 'message' => 'Correction not found or already processed'];
            }

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}
