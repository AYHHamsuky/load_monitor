<?php
/**
 * AJAX Handler: bulk-paste 33kV interruptions (dispatch's daily log sheet).
 * Path: /public/ajax/interruption_bulk_save.php
 *
 * POST fields:
 *   rows             JSON array of {
 *                       feeder_token, interruption_code, load_loss,
 *                       date_out, time_out, date_in?, time_in?,
 *                       reason?, resolution?, weather?
 *                    }
 *   approval_note    Optional — applied to every row whose Date Out is
 *                    before today (bulk approval reason).
 *
 * Response:
 *   {
 *     success:  bool,
 *     saved:    N,
 *     failed:   N,
 *     results: [{row, feeder, code, ticket|error, status}, ...]
 *   }
 */
ob_start(); ini_set('display_errors', '0');
header('Content-Type: application/json');
require '../../app/bootstrap.php';
require '../../app/models/Interruption.php';
require '../../app/models/InterruptionApproval.php';

if (!Auth::check()) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Session expired. Please login again.']);
    exit;
}
$user = Auth::user();
if ($user['role'] !== 'UL2') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized access — UL2 only.']);
    exit;
}

try {
    $rowsJson = $_POST['rows'] ?? '';
    $rows     = json_decode($rowsJson, true);
    if (!is_array($rows) || empty($rows)) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'No rows to save.']);
        exit;
    }
    $bulkApprovalNote = trim($_POST['approval_note'] ?? '');

    $results = [];
    $saved   = 0;
    $failed  = 0;

    foreach ($rows as $i => $r) {

        // Combine date + time into "Y-m-d H:i:s"
        $dtOut = ''; $dtIn = '';
        if (!empty($r['date_out']) && !empty($r['time_out'])) {
            $ts = strtotime($r['date_out'] . ' ' . $r['time_out']);
            if ($ts) $dtOut = date('Y-m-d H:i:s', $ts);
        }
        if (!empty($r['date_in']) && !empty($r['time_in'])) {
            $ts = strtotime($r['date_in'] . ' ' . $r['time_in']);
            if ($ts) $dtIn = date('Y-m-d H:i:s', $ts);
        }

        $rowRes = [
            'row'    => (int)$i + 1,
            'feeder' => $r['feeder_token'] ?? '',
            'code'   => $r['interruption_code'] ?? '',
        ];

        if (empty($r['fdr33kv_code'])) {
            $results[] = $rowRes + ['success' => false,
                'message' => 'Unknown feeder — matched no 33kV record.'];
            $failed++; continue;
        }
        if (empty($r['interruption_code'])) {
            $results[] = $rowRes + ['success' => false,
                'message' => 'Missing interruption code.'];
            $failed++; continue;
        }
        if ($dtOut === '') {
            $results[] = $rowRes + ['success' => false,
                'message' => 'Missing / invalid Date Out or Time Out.'];
            $failed++; continue;
        }

        $result = Interruption::logSingle([
            'fdr33kv_code'            => $r['fdr33kv_code'],
            'interruption_code'       => $r['interruption_code'],
            'datetime_out'            => $dtOut,
            'datetime_in'             => $dtIn,
            'load_loss'               => $r['load_loss']  ?? 0,
            'reason_for_interruption' => trim($r['reason']      ?? '') ?: null,
            'resolution'              => trim($r['resolution']  ?? '') ?: null,
            'weather_condition'       => trim($r['weather']     ?? '') ?: null,
            'approval_note'           => $bulkApprovalNote ?: null,
            'user_id'                 => $user['payroll_id'],
            'user_name'               => $user['staff_name'] ?? $user['payroll_id'],
        ]);

        if (!empty($result['success'])) {
            $results[] = $rowRes + [
                'success' => true,
                'ticket'  => $result['ticket_number'],
                'status'  => $result['form_status'],
                'message' => $result['message'],
            ];
            $saved++;
        } else {
            $results[] = $rowRes + [
                'success' => false,
                'message' => $result['message'] ?? 'Save failed',
                'needs_approval_note' => !empty($result['needs_approval_note']),
            ];
            $failed++;
        }
    }

    ob_end_clean();
    echo json_encode([
        'success'  => $saved > 0,
        'saved'    => $saved,
        'failed'   => $failed,
        'total'    => count($rows),
        'results'  => $results,
        'message'  => $saved > 0
            ? "{$saved} interruption(s) saved" . ($failed > 0 ? ", {$failed} failed" : '')
            : 'No interruptions were saved. Check the row errors.',
    ]);

} catch (Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'System error: ' . $e->getMessage()]);
}
