<?php
/**
 * ISS 11kV Interruption Requests
 * Path: /app/views/interruptions_11kv/my_requests.php
 *
 * Shows all interruptions for every 11kV feeder under the current user's
 * injection substation (ISS).  Any UL1 user at that ISS can execute Stage 2.
 * Edit / Cancel remain restricted to the original logger within the 1-hour window.
 */
require __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/sidebar.php';
require_once __DIR__ . '/../../models/Interruption11kv.php';

// Controller passes $issInterruptions, $issName, $isUL1
$issInterruptions = $issInterruptions ?? [];
$issName          = $issName          ?? 'Your Station';
$isUL1            = $isUL1            ?? false;
$currentUserId    = $user['payroll_id'] ?? null;

// Pre-compute edit/cancel eligibility for each row (creator only)
foreach ($issInterruptions as &$req) {
    $isOwner = ($req['user_id'] == $currentUserId);
    if ($isOwner) {
        $guard = Interruption11kv::canEditOrCancel($req);
        $req['_ec_allowed']      = $guard['allowed'];
        $req['_ec_seconds_left'] = $guard['allowed'] ? $guard['seconds_left'] : 0;
    } else {
        $req['_ec_allowed']      = false;
        $req['_ec_seconds_left'] = 0;
    }
    $req['_is_owner'] = $isOwner;
}
unset($req);
?>

<style>
.main-content{margin-left:260px;padding:22px;padding-top:90px;min-height:calc(100vh - 64px);background:#f4f6fa;}
.page-card{background:#fff;border-radius:14px;padding:28px;box-shadow:0 10px 30px rgba(0,0,0,.08);max-width:1400px;margin:0 auto;}
.page-title{font-size:24px;font-weight:700;color:#0f172a;margin-bottom:4px;}
.page-subtitle{color:#6b7280;font-size:14px;margin-bottom:24px;}

/* ISS badge */
.iss-badge{display:inline-flex;align-items:center;gap:6px;background:#eff6ff;border:1px solid #bfdbfe;
    border-radius:8px;padding:4px 12px;font-size:12px;font-weight:700;color:#1e40af;margin-bottom:20px;}

/* Summary chips */
.summary-row{display:flex;gap:14px;margin-bottom:24px;flex-wrap:wrap;}
.chip{background:#f8faff;border:1px solid #e0e7ff;border-radius:10px;padding:14px 20px;min-width:140px;flex:1;}
.chip .chip-val{font-size:28px;font-weight:800;color:#1e40af;}
.chip .chip-label{font-size:12px;color:#6b7280;margin-top:2px;}

/* Table */
.requests-table{width:100%;border-collapse:collapse;font-size:13px;}
.requests-table thead th{background:#f8faff;padding:10px 12px;text-align:left;
    font-size:11px;font-weight:700;color:#374151;text-transform:uppercase;
    border-bottom:2px solid #e5e7eb;white-space:nowrap;}
.requests-table tbody tr{border-bottom:1px solid #f3f4f6;transition:background .15s;}
.requests-table tbody tr:hover{background:#f8faff;}
.requests-table td{padding:10px 12px;vertical-align:middle;}

/* Ticket link */
.ticket-link{font-family:monospace;font-size:13px;font-weight:800;color:#1e40af;
    text-decoration:none;letter-spacing:1px;padding:4px 10px;background:#eff6ff;
    border-radius:6px;display:inline-block;border:1px solid #bfdbfe;transition:all .2s;}
.ticket-link:hover{background:#dbeafe;transform:translateY(-1px);}

/* Owner badge */
.owner-dot{display:inline-block;width:6px;height:6px;border-radius:50%;
    background:#22c55e;margin-right:4px;vertical-align:middle;}

/* Status pills */
.status-pill{padding:4px 11px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap;}
.s-pending   {background:#fef3c7;color:#92400e;}
.s-approval  {background:#dbeafe;color:#1e40af;}
.s-approved  {background:#d1fae5;color:#065f46;}
.s-completed {background:#dcfce7;color:#166534;}
.s-cancelled {background:#fee2e2;color:#991b1b;}

/* Action buttons */
.btn-action{padding:5px 11px;border-radius:6px;font-size:12px;font-weight:600;
    cursor:pointer;border:none;text-decoration:none;display:inline-block;
    white-space:nowrap;margin:2px 1px;}
.btn-complete-row{background:linear-gradient(135deg,#1e40af,#1d4ed8);color:#fff;}
.btn-view-row    {background:#f3f4f6;color:#374151;}
.btn-edit-row    {background:#fbbf24;color:#000;}
.btn-cancel-row  {background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;}

/* Timer chip */
.ec-chip{display:inline-flex;align-items:center;gap:5px;
    background:#fef3c7;border:1px solid #fde68a;border-radius:14px;
    padding:3px 9px;font-size:11px;font-weight:700;color:#92400e;white-space:nowrap;}

/* Log button */
.btn-log{background:linear-gradient(135deg,#dc2626,#991b1b);color:#fff;
    border:none;padding:10px 22px;border-radius:8px;cursor:pointer;
    font-weight:600;font-size:14px;text-decoration:none;display:inline-block;margin-bottom:20px;}

.empty-state{text-align:center;padding:60px 20px;color:#9ca3af;}
.empty-state .empty-icon{font-size:48px;margin-bottom:12px;}
.empty-state h3{font-size:18px;font-weight:700;color:#6b7280;margin-bottom:8px;}

/* Cancel modal */
.modal-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;
    background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center;}
.modal-overlay.open{display:flex;}
.modal-box{background:#fff;border-radius:14px;padding:28px;max-width:480px;width:90%;
    box-shadow:0 20px 60px rgba(0,0,0,.3);}
.modal-box h3{color:#dc2626;margin-bottom:12px;font-size:18px;}
.modal-box textarea{width:100%;padding:10px;border:1.5px solid #fca5a5;border-radius:8px;
    font-size:14px;min-height:80px;margin:12px 0;}
.modal-btn-row{display:flex;gap:10px;margin-top:8px;}

@media(max-width:768px){
    .main-content{margin-left:0;padding:12px;padding-top:70px;}
    .requests-table{display:block;overflow-x:auto;}
}
</style>

<div class="main-content">
<div class="page-card">

    <h1 class="page-title">⚡ 11kV Interruption Log</h1>
    <p class="page-subtitle">
        All active interruptions at your injection substation.
        Any UL1 staff here can execute Stage 2. Edit / Cancel is restricted
        to the <strong>original logger</strong> within 1 hour of creation.
    </p>

    <div class="iss-badge">
        <i class="fas fa-building"></i>
        <?= htmlspecialchars($issName) ?> — ISS Interruptions
    </div>

    <?php if ($isUL1): ?>
    <a href="index.php?page=interruptions_11kv&action=log" class="btn-log">⚡ Log New Interruption</a>
    <?php endif; ?>

    <?php
    $total     = count($issInterruptions);
    $pending   = count(array_filter($issInterruptions, fn($r) => $r['form_status'] === 'PENDING_COMPLETION'));
    $awaiting  = count(array_filter($issInterruptions, fn($r) => $r['form_status'] === 'AWAITING_APPROVAL'));
    $approved  = count(array_filter($issInterruptions, fn($r) => $r['form_status'] === 'PENDING_COMPLETION_APPROVED'));
    $completed = count(array_filter($issInterruptions, fn($r) => $r['form_status'] === 'COMPLETED'));
    $cancelled = count(array_filter($issInterruptions, fn($r) => $r['form_status'] === 'CANCELLED'));
    ?>

    <div class="summary-row">
        <div class="chip"><div class="chip-val"><?= $total ?></div><div class="chip-label">Total</div></div>
        <div class="chip"><div class="chip-val" style="color:#92400e"><?= $pending ?></div><div class="chip-label">Pending Completion</div></div>
        <div class="chip"><div class="chip-val" style="color:#1e40af"><?= $awaiting ?></div><div class="chip-label">Awaiting Approval</div></div>
        <div class="chip"><div class="chip-val" style="color:#065f46"><?= $approved ?></div><div class="chip-label">Approved — Stage 2 Ready</div></div>
        <div class="chip"><div class="chip-val" style="color:#166534"><?= $completed ?></div><div class="chip-label">Completed</div></div>
        <div class="chip"><div class="chip-val" style="color:#991b1b"><?= $cancelled ?></div><div class="chip-label">Cancelled</div></div>
    </div>

    <?php if (empty($issInterruptions)): ?>
    <div class="empty-state">
        <div class="empty-icon">📋</div>
        <h3>No interruptions logged yet</h3>
        <?php if ($isUL1): ?>
        <p>Click "Log New Interruption" above to start.</p>
        <?php else: ?>
        <p>No interruptions have been logged at this station.</p>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
    <table class="requests-table">
        <thead>
            <tr>
                <th>Ticket</th>
                <th>Feeder</th>
                <th>Type / Code</th>
                <th>Date/Time Out</th>
                <th>Date/Time In</th>
                <th>Status</th>
                <th>Logged By</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($issInterruptions as $idx => $req): ?>
        <tr>
            <td>
                <a href="index.php?page=interruptions_11kv&action=view&ticket=<?= urlencode($req['ticket_number']) ?>"
                   class="ticket-link" title="Click to open record">
                    <?= htmlspecialchars($req['ticket_number']) ?>
                </a>
            </td>
            <td>
                <?= htmlspecialchars($req['fdr11kv_name']) ?>
                <?php if (($req['interruption_type'] ?? '') === 'PLANNED'): ?>
                    <br><span style="font-size:10px;background:#d1fae5;color:#065f46;padding:1px 6px;border-radius:10px;font-weight:700;">PLANNED</span>
                <?php endif; ?>
            </td>
            <td>
                <span style="font-size:11px;color:#6b7280;"><?= htmlspecialchars($req['interruption_type'] ?? '') ?></span><br>
                <strong><?= htmlspecialchars($req['interruption_code'] ?? '') ?></strong>
            </td>
            <td><?= $req['datetime_out'] ? date('d M Y<\b\r>H:i', strtotime($req['datetime_out'])) : '—' ?></td>
            <td>
                <?= $req['datetime_in']
                    ? date('d M Y<\b\r>H:i', strtotime($req['datetime_in']))
                    : '<span style="color:#9ca3af;font-size:12px;">— pending —</span>' ?>
            </td>
            <td>
                <?php
                $statusDisplay = [
                    'PENDING_COMPLETION'          => ['s-pending',  '⏳ Pending'],
                    'AWAITING_APPROVAL'           => ['s-approval', '🔄 Awaiting Approval'],
                    'PENDING_COMPLETION_APPROVED' => ['s-approved', '✅ Approved'],
                    'COMPLETED'                   => ['s-completed','✔ Completed'],
                    'CANCELLED'                   => ['s-cancelled','✖ Cancelled'],
                ];
                [$cls,$lbl] = $statusDisplay[$req['form_status']] ?? ['s-pending', $req['form_status']];
                echo "<span class=\"status-pill {$cls}\">{$lbl}</span>";
                ?>
            </td>
            <td style="white-space:nowrap;">
                <?php if ($req['_is_owner']): ?>
                    <span class="owner-dot" title="You logged this"></span>
                <?php endif; ?>
                <?= htmlspecialchars($req['logger_name'] ?? '—') ?>
            </td>
            <td style="white-space:nowrap;">
                <!-- View: always visible -->
                <a href="index.php?page=interruptions_11kv&action=view&ticket=<?= urlencode($req['ticket_number']) ?>"
                   class="btn-action btn-view-row">View</a>

                <?php
                // Complete (Stage 2): any UL1 at this ISS can execute
                $canComplete = $isUL1 && in_array($req['form_status'], ['PENDING_COMPLETION','PENDING_COMPLETION_APPROVED']);
                if ($canComplete):
                ?>
                <a href="index.php?page=interruptions_11kv&action=complete&id=<?= (int)$req['id'] ?>"
                   class="btn-action btn-complete-row">⚡ Complete</a>
                <?php endif; ?>

                <?php if ($req['_ec_allowed']): ?>
                    <br>
                    <span class="ec-chip" id="chip_<?= $idx ?>"
                          data-seconds="<?= (int)$req['_ec_seconds_left'] ?>">
                        ⏱ <span id="timer_<?= $idx ?>">--:--</span>
                    </span>
                    <!-- Edit/Cancel: creator only, within 1-hour window -->
                    <a href="index.php?page=interruptions_11kv&action=edit&id=<?= (int)$req['id'] ?>"
                       class="btn-action btn-edit-row" style="margin-top:4px;">✏️ Edit</a>
                    <button class="btn-action btn-cancel-row"
                            onclick="openCancelModal(<?= (int)$req['id'] ?>, '<?= htmlspecialchars($req['ticket_number'], ENT_QUOTES) ?>')"
                            style="margin-top:4px;">✖ Cancel</button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>

</div><!-- /.page-card -->
</div><!-- /.main-content -->

<!-- Cancel modal (creator only — button only shown to eligible users) -->
<div class="modal-overlay" id="cancelModal">
    <div class="modal-box">
        <h3>✖ Cancel Ticket</h3>
        <p id="cancelModalDesc"></p>
        <p><strong>This action is irreversible and will be logged.</strong></p>
        <label style="font-weight:600;font-size:14px;">Reason for Cancellation *</label>
        <textarea id="cancelReason" placeholder="State clearly why this ticket is being cancelled..."></textarea>
        <div class="modal-btn-row">
            <button class="btn-action btn-cancel-row" style="padding:10px 20px;font-size:14px;"
                    onclick="submitCancel()">✖ Confirm Cancel</button>
            <button class="btn-action btn-view-row" style="padding:10px 20px;font-size:14px;"
                    onclick="closeCancelModal()">← Back</button>
        </div>
        <div id="cancelAlert" style="margin-top:12px;padding:10px;border-radius:6px;display:none;"></div>
    </div>
</div>

<script>
// ── 1-hour countdown timers ──────────────────────────────────────────────────
document.querySelectorAll('.ec-chip[data-seconds]').forEach(function(chip) {
    const id      = chip.id.replace('chip_','');
    const timerEl = document.getElementById('timer_' + id);
    let secs      = parseInt(chip.dataset.seconds, 10);

    function tick() {
        if (secs <= 0) {
            chip.textContent     = '⏱ Expired';
            chip.style.background = '#f3f4f6';
            chip.style.color      = '#9ca3af';
            const row = chip.closest('tr');
            row.querySelectorAll('.btn-edit-row,.btn-cancel-row').forEach(function(b){
                b.style.display = 'none';
            });
            return;
        }
        const m = String(Math.floor(secs/60)).padStart(2,'0');
        const s = String(secs%60).padStart(2,'0');
        timerEl.textContent = m+':'+s;
        secs--;
        setTimeout(tick, 1000);
    }
    tick();
});

// ── Cancel modal ─────────────────────────────────────────────────────────────
let cancelTargetId = null;

function openCancelModal(id, ticket) {
    cancelTargetId = id;
    document.getElementById('cancelModalDesc').textContent =
        'You are about to cancel ticket ' + ticket + '.';
    document.getElementById('cancelReason').value = '';
    document.getElementById('cancelAlert').style.display = 'none';
    document.getElementById('cancelModal').classList.add('open');
}

function closeCancelModal() {
    document.getElementById('cancelModal').classList.remove('open');
    cancelTargetId = null;
}

function submitCancel() {
    const reason  = document.getElementById('cancelReason').value.trim();
    const alertEl = document.getElementById('cancelAlert');
    if (!reason) {
        alertEl.style.display    = 'block';
        alertEl.style.background = '#fee2e2';
        alertEl.style.color      = '#991b1b';
        alertEl.textContent      = 'Please enter a reason for cancellation.';
        return;
    }
    const fd = new FormData();
    fd.append('interruption_id', cancelTargetId);
    fd.append('cancel_reason',   reason);

    fetch('ajax/interruption_11kv_cancel.php', {method:'POST', body:fd})
    .then(r => r.json())
    .then(res => {
        alertEl.style.display = 'block';
        if (res.success) {
            alertEl.style.background = '#dcfce7';
            alertEl.style.color      = '#166534';
            alertEl.textContent      = '✅ ' + res.message;
            setTimeout(() => window.location.reload(), 1800);
        } else {
            alertEl.style.background = '#fee2e2';
            alertEl.style.color      = '#991b1b';
            alertEl.textContent      = '❌ ' + res.message;
        }
    })
    .catch(err => {
        alertEl.style.display    = 'block';
        alertEl.style.background = '#fee2e2';
        alertEl.style.color      = '#991b1b';
        alertEl.textContent      = '❌ Network error: ' + err.message;
    });
}

document.getElementById('cancelModal').addEventListener('click', function(e){
    if (e.target === this) closeCancelModal();
});
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
