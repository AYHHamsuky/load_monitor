<?php
/**
 * AJAX — Complete an 11kV Interruption (Stage 2)
 * Path: /ajax/interruption_11kv_complete.php
 *
 * Identical business logic to interruption_complete.php (33kV).
 * Differences: table = interruptions_11kv, feeder ref = fdr11kv_code.
 *
 * POST fields:
 *   interruption_id       int
 *   datetime_in           string  (Y-m-d\TH:i)
 *   load_loss             float
 *   reason_for_interruption string (required)
 *   resolution            string
 *   reason_for_delay      string
 *   other_reasons         string
 *   late_in_auto_note     string  system-generated lag summary
 *   late_in_reason        string  user-typed explanation (required when overrun)
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/Auth.php';

/* ── Guards ──────────────────────────────────────────────────────────────── */
$user = Auth::user();
if (!$user) { http_response_code(403); echo json_encode(['success'=>false,'message'=>'Session expired.']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['success'=>false,'message'=>'Method not allowed.']); exit; }

function clean(?string $v): ?string {
    if ($v === null) return null;
    $v = trim($v);
    return $v === '' ? null : $v;
}

/* ── Collect inputs ──────────────────────────────────────────────────────── */
$interruptionId  = filter_input(INPUT_POST, 'interruption_id', FILTER_VALIDATE_INT);
$datetimeIn      = clean($_POST['datetime_in']              ?? null);
$loadLoss        = filter_input(INPUT_POST, 'load_loss',       FILTER_VALIDATE_FLOAT);
$reasonForInt    = clean($_POST['reason_for_interruption']   ?? null);
$resolution      = clean($_POST['resolution']                ?? null);
$reasonForDelay  = clean($_POST['reason_for_delay']          ?? null);
$otherReasons    = clean($_POST['other_reasons']             ?? null);
$lateInAutoNote  = clean($_POST['late_in_auto_note']         ?? null);
$lateInReason    = clean($_POST['late_in_reason']            ?? null);

/* ── Basic validation ────────────────────────────────────────────────────── */
$errors = [];
if (!$interruptionId)                                   $errors[] = 'Invalid or missing interruption record.';
if (!$datetimeIn)                                       $errors[] = 'Restoration date/time is required.';
if ($loadLoss === false || $loadLoss === null || $loadLoss < 0) $errors[] = 'Load loss must be a non-negative number.';
if (!$reasonForInt)                                     $errors[] = 'Reason for interruption is required.';
if ($reasonForDelay === 'others' && !$otherReasons)     $errors[] = '"Others" delay selected — please specify.';

$dtIn    = null;
$lagMins = 0;

if ($datetimeIn) {
    try {
        $dtIn    = new DateTime($datetimeIn);
        $now     = new DateTime();
        $lagSecs = $now->getTimestamp() - $dtIn->getTimestamp();
        $lagMins = $lagSecs / 60;

        if ($lagSecs < -60) {
            $errors[] = 'Restoration time cannot be set in the future.';
        }

        if ($lagMins > 30 && !$lateInReason) {
            $errors[] = 'Restoration time is more than 30 minutes past — a late entry explanation is required.';
        }
    } catch (Exception $e) {
        $errors[] = 'Restoration date/time format is invalid.';
    }
}

if ($errors) { echo json_encode(['success'=>false,'message'=>implode(' ',$errors)]); exit; }

/* ── Database checks ─────────────────────────────────────────────────────── */
try {
    $db = Database::connect();

    $stmt = $db->prepare("SELECT id, datetime_out, form_status, created_by FROM interruptions_11kv WHERE id = ? LIMIT 1");
    $stmt->execute([$interruptionId]);
    $rec = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$rec) { echo json_encode(['success'=>false,'message'=>'Record not found.']); exit; }

    $allowedRoles = ['UL3','UL4','Admin'];
    if ((int)$rec['created_by'] !== (int)$user['id'] && !in_array($user['role'], $allowedRoles, true)) {
        echo json_encode(['success'=>false,'message'=>'You do not have permission to complete this record.']); exit;
    }

    if (!in_array($rec['form_status'], ['PENDING_COMPLETION','PENDING_COMPLETION_APPROVED'], true)) {
        echo json_encode(['success'=>false,'message'=>'Cannot complete record in status: '.$rec['form_status']]); exit;
    }

    $dtOut = new DateTime($rec['datetime_out']);
    if ($dtIn <= $dtOut) {
        echo json_encode(['success'=>false,'message'=>'Restoration time must be after the outage start ('.$dtOut->format('d M Y H:i').').']); exit;
    }

    // Re-verify overrun server-side
    $nowDB     = new DateTime();
    $lagMinsDB = ($nowDB->getTimestamp() - $dtIn->getTimestamp()) / 60;
    if ($lagMinsDB > 30 && !$lateInReason) {
        echo json_encode(['success'=>false,'message'=>'Server: late entry explanation required (lag: '.round($lagMinsDB).' min).']); exit;
    }

    $diff        = $dtOut->diff($dtIn);
    $durationHrs = round(($diff->days * 24) + $diff->h + ($diff->i / 60), 4);
    $isLateIn    = ($lagMinsDB > 30) ? 1 : 0;

    $fullNote = null;
    if ($isLateIn) {
        $parts = [];
        if ($lateInAutoNote) $parts[] = $lateInAutoNote;
        if ($lateInReason)   $parts[] = 'Operator explanation: ' . $lateInReason;
        $fullNote = implode("\n", $parts);
    }

    /* ── UPDATE ──────────────────────────────────────────────────────────── */
    $upd = $db->prepare("
        UPDATE interruptions_11kv SET
            datetime_in             = :datetime_in,
            load_loss               = :load_loss,
            duration                = :duration,
            reason_for_interruption = :reason_for_interruption,
            resolution              = :resolution,
            reason_for_delay        = :reason_for_delay,
            other_reasons           = :other_reasons,
            late_in_note            = :late_in_note,
            is_late_in              = :is_late_in,
            form_status             = 'COMPLETED',
            completed_at            = NOW(),
            completed_by            = :completed_by
        WHERE id = :id
    ");
    $upd->execute([
        ':datetime_in'             => $dtIn->format('Y-m-d H:i:s'),
        ':load_loss'               => $loadLoss,
        ':duration'                => $durationHrs,
        ':reason_for_interruption' => $reasonForInt,
        ':resolution'              => $resolution,
        ':reason_for_delay'        => $reasonForDelay,
        ':other_reasons'           => ($reasonForDelay === 'others') ? $otherReasons : null,
        ':late_in_note'            => $fullNote,
        ':is_late_in'              => $isLateIn,
        ':completed_by'            => $user['id'],
        ':id'                      => $interruptionId,
    ]);

    if ($upd->rowCount() === 0) { echo json_encode(['success'=>false,'message'=>'No changes saved. Please try again.']); exit; }

    $msg = 'Record finalised. Duration: ' . number_format($durationHrs, 2) . ' hrs.';
    if ($isLateIn) $msg .= ' ⚠️ Flagged: Time In logged ' . round($lagMinsDB) . ' min after restoration.';

    echo json_encode(['success'=>true,'message'=>$msg]);

} catch (PDOException $e) {
    error_log('[interruption_11kv_complete] '.$e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Database error. Please try again.']);
} catch (Exception $e) {
    error_log('[interruption_11kv_complete] '.$e->getMessage());
    echo json_encode(['success'=>false,'message'=>'Unexpected error: '.$e->getMessage()]);
}
