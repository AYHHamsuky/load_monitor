<?php
/**
 * 11kV Interruption Form — five modes
 * Path: /app/views/interruptions_11kv/log_form.php
 *
 * $mode:
 *   'new'           → Stage 1 active, Stage 2 locked
 *   'view'          → all locked; "Complete" button shown if pending
 *   'complete'      → Stage 1 locked, Stage 2 active
 *   'edit'          → Stage 1 editable within 1-hour window
 *   'cancel_confirm'→ confirmation panel for cancellation
 *
 * $interruption     → DB record (null when mode='new')
 * $feeders_11kv     → from controller
 * $pageError        → optional string shown as error banner
 * $editWindowSecondsLeft → integer, set in 'edit' and 'cancel_confirm' modes
 */
require __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/sidebar.php';

$db = Database::connect();
$codesStmt = $db->query("
    SELECT interruption_code, interruption_description, interruption_type,
           interruption_group, body_responsible, approval_requirement
    FROM interruption_codes ORDER BY interruption_type, interruption_description
");
$interruptionCodes = $codesStmt->fetchAll(PDO::FETCH_ASSOC);
$codesByType = [];
foreach ($interruptionCodes as $c) { $codesByType[$c['interruption_type']][] = $c; }
$interruptionTypes = array_unique(array_column($interruptionCodes, 'interruption_type'));

$mode  = $mode  ?? 'new';
$irec  = $interruption ?? null;
$pageError = $pageError ?? null;
$editWindowSecondsLeft = $editWindowSecondsLeft ?? 0;

// Locking logic
$stage1Locked = !in_array($mode, ['new','edit']);
$stage2Locked = ($mode !== 'complete');

// "Complete Interruption" button shown in view mode when pending
$canComplete = $irec
    && $mode === 'view'
    && in_array($irec['form_status'], ['PENDING_COMPLETION','PENDING_COMPLETION_APPROVED']);

// Can we edit/cancel this ticket?
$canEditOrCancel = $irec && $mode === 'view'
    && !in_array($irec['form_status'], ['COMPLETED','CANCELLED'])
    && isset($irec['started_at']);

if ($canEditOrCancel) {
    require_once __DIR__ . '/../../models/Interruption11kv.php';
    $ecGuard = Interruption11kv::canEditOrCancel($irec);
    $canEditOrCancel = $ecGuard['allowed'];
    $ecSecondsLeft   = $ecGuard['seconds_left'] ?? 0;
}

$d1 = $stage1Locked ? 'disabled' : '';
$d2 = $stage2Locked ? 'disabled' : '';
?>

<style>
/* ── Base layout ────────────────────────────────────────────────────────── */
.main-content{margin-left:260px;padding:22px;padding-top:90px;min-height:calc(100vh - 64px);background:#f4f6fa;}
.interruption-card{background:#fff;border-radius:14px;padding:28px;box-shadow:0 10px 30px rgba(0,0,0,.08);max-width:1000px;margin:0 auto;}
.page-title{font-size:24px;font-weight:700;color:#0f172a;margin-bottom:8px;}
.page-subtitle{color:#6b7280;font-size:14px;margin-bottom:24px;}

/* ── Ticket banner ──────────────────────────────────────────────────────── */
.ticket-banner{display:flex;align-items:center;gap:20px;background:linear-gradient(135deg,#0f172a,#1e3a5f);
    color:#fff;border-radius:12px;padding:16px 22px;margin-bottom:16px;}
.ticket-num{font-family:monospace;font-size:24px;font-weight:900;letter-spacing:3px;}
.ticket-meta{font-size:12px;opacity:.75;margin-top:2px;}
.status-badge{margin-left:auto;padding:5px 16px;border-radius:20px;font-size:11px;font-weight:700;
    text-transform:uppercase;white-space:nowrap;}
.badge-pending   {background:#fef3c7;color:#92400e;}
.badge-approval  {background:#dbeafe;color:#1e40af;}
.badge-approved  {background:#d1fae5;color:#065f46;}
.badge-completed {background:#dcfce7;color:#166534;}
.badge-cancelled {background:#fee2e2;color:#991b1b;}

/* ── Edit/Cancel toolbar ────────────────────────────────────────────────── */
.ec-toolbar{display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap;}
.ec-timer{font-size:13px;color:#92400e;background:#fef3c7;padding:6px 14px;border-radius:20px;font-weight:600;}
.btn-edit-ticket{background:#fbbf24;color:#000;border:none;padding:8px 18px;border-radius:7px;
    font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block;}
.btn-cancel-ticket{background:#dc2626;color:#fff;border:none;padding:8px 18px;border-radius:7px;
    font-size:13px;font-weight:700;cursor:pointer;}

/* ── Complete button ────────────────────────────────────────────────────── */
.btn-complete-interruption{display:inline-flex;align-items:center;gap:8px;
    background:linear-gradient(135deg,#1e40af,#1d4ed8);color:#fff;border:none;
    padding:13px 28px;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;
    box-shadow:0 4px 14px rgba(30,64,175,.35);text-decoration:none;margin-bottom:16px;}
.btn-complete-interruption:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(30,64,175,.4);}

/* ── Form structure ─────────────────────────────────────────────────────── */
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px;}
.form-group{margin-bottom:18px;}
.form-group.full-width{grid-column:1/-1;}
.form-group label{display:block;font-weight:600;margin-bottom:7px;font-size:14px;color:#374151;}
.form-group label .required{color:#dc2626;}
.form-group input,.form-group select,.form-group textarea{
    width:100%;padding:10px 14px;border-radius:8px;border:1.5px solid #d1d5db;
    font-size:14px;transition:all .2s;}
.form-group textarea{resize:vertical;min-height:80px;font-family:inherit;}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{
    outline:none;border-color:#dc2626;box-shadow:0 0 0 3px rgba(220,38,38,.1);}
.form-group input:disabled,.form-group select:disabled,.form-group textarea:disabled,
.form-group input[readonly]{background:#f3f4f6;color:#6b7280;cursor:not-allowed;}

/* ── Stage 2 ────────────────────────────────────────────────────────────── */
.stage2-block{border-radius:10px;padding:20px;border:2px dashed #d1d5db;background:#fafafa;margin-top:8px;}
.stage2-block.active{border-color:#3b82f6;background:#fff;}
.stage2-locked-msg{text-align:center;padding:22px;color:#9ca3af;font-size:14px;font-style:italic;}
.field-grayed{opacity:.5;pointer-events:none;}

/* ── Notices ────────────────────────────────────────────────────────────── */
.info-box{background:#fef3c7;border-left:4px solid #f59e0b;padding:14px 18px;border-radius:6px;
    margin-bottom:16px;font-size:13px;color:#92400e;}
.info-box.blue {background:#dbeafe;border-color:#3b82f6;color:#1e40af;}
.info-box.red  {background:#fee2e2;border-color:#dc2626;color:#991b1b;}
.info-box.green{background:#d1fae5;border-color:#10b981;color:#065f46;}
.approval-notice{background:#dbeafe;border-left:4px solid #3b82f6;padding:14px 18px;border-radius:6px;
    margin-bottom:16px;font-size:13px;color:#1e40af;display:none;}
#approvalNoteGroup{display:none;}
#delayReasonGroup{display:none;}

/* ── Datetime validation error strip ────────────────────────────────────── */
.dt-error{
    margin-top:6px;padding:7px 12px;border-radius:6px;
    background:#fee2e2;border:1px solid #fca5a5;
    color:#dc2626;font-size:12px;font-weight:600;
    display:none;
}

/* ── Late Entry Panel (datetime_in exceeded 30-min window) ──────────────── */
#lateEntryPanel{
    background:linear-gradient(135deg,#fff7ed 0%,#ffedd5 100%);
    border-left:4px solid #f97316;
    border-radius:10px;
    padding:16px 18px;
    margin-top:12px;
    display:none;
}
.late-entry-header{
    display:flex;align-items:center;gap:8px;
    font-weight:700;color:#c2410c;margin-bottom:8px;font-size:14px;
}
.late-entry-body{color:#7c2d12;font-size:13px;line-height:1.5;margin-bottom:12px;}
#lateEntryPanel .form-group{margin-bottom:0;}
#lateEntryPanel textarea{
    border-color:#f97316;
    background:#fffbf5;
}
#lateEntryPanel textarea:focus{
    border-color:#ea580c;
    box-shadow:0 0 0 3px rgba(249,115,22,.15);
}

/* ── Section divider ────────────────────────────────────────────────────── */
.section-head{display:flex;align-items:center;gap:10px;margin:24px 0 16px;
    font-size:13px;font-weight:700;color:#374151;}
.section-head::before,.section-head::after{content:'';flex:1;height:1px;background:#e5e7eb;}

/* ── Cancel confirmation box ────────────────────────────────────────────── */
.cancel-confirm-box{background:#fff5f5;border:2px solid #fca5a5;border-radius:12px;padding:24px;margin-top:16px;}
.cancel-confirm-box h3{color:#dc2626;margin-bottom:12px;}

/* ── Buttons ────────────────────────────────────────────────────────────── */
.btn-group{display:flex;gap:12px;margin-top:28px;flex-wrap:wrap;}
.btn-primary{background:linear-gradient(135deg,#dc2626,#991b1b);color:#fff;border:none;
    padding:12px 28px;border-radius:8px;cursor:pointer;font-weight:600;font-size:14px;transition:all .2s;}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(220,38,38,.3);}
.btn-primary:disabled{opacity:.6;cursor:not-allowed;transform:none;}
.btn-secondary{background:#e5e7eb;color:#374151;border:none;padding:12px 28px;
    border-radius:8px;cursor:pointer;font-weight:600;font-size:14px;text-decoration:none;display:inline-block;}
.alert{padding:14px 18px;border-radius:8px;margin-bottom:20px;display:none;}
.alert.success{background:#dcfce7;color:#166534;border:1px solid #bbf7d0;}
.alert.error  {background:#fee2e2;color:#991b1b;border:1px solid #fecaca;}

@media(max-width:768px){
    .main-content{margin-left:0;padding:12px;padding-top:70px;}
    .form-row{grid-template-columns:1fr;}
    .ticket-banner{flex-direction:column;align-items:flex-start;}
    .status-badge{margin-left:0;}
}
</style>

<div class="main-content">
<div class="interruption-card">

<?php /* ── TICKET BANNER ── */ ?>
<?php if ($irec): ?>
<div class="ticket-banner">
    <div>
        <div style="font-size:11px;opacity:.6;font-weight:600;text-transform:uppercase;letter-spacing:1px;">Ticket Number</div>
        <div class="ticket-num"><?= htmlspecialchars($irec['ticket_number']) ?></div>
        <div class="ticket-meta">
            <?= htmlspecialchars($irec['fdr11kv_name']) ?> &nbsp;·&nbsp;
            Logged: <?= date('d M Y H:i', strtotime($irec['started_at'])) ?>
            <?php if ($irec['completed_at']): ?>
                &nbsp;·&nbsp; Completed: <?= date('d M Y H:i', strtotime($irec['completed_at'])) ?>
            <?php endif; ?>
        </div>
    </div>
    <?php
        $statusMap = [
            'PENDING_COMPLETION'          => ['badge-pending',  '⏳ Pending Completion'],
            'AWAITING_APPROVAL'           => ['badge-approval', '🔄 Awaiting Approval'],
            'PENDING_COMPLETION_APPROVED' => ['badge-approved', '✅ Approved — Complete Stage 2'],
            'COMPLETED'                   => ['badge-completed','✔ Completed'],
            'CANCELLED'                   => ['badge-cancelled','✖ Cancelled'],
        ];
        [$cls, $lbl] = $statusMap[$irec['form_status']] ?? ['badge-pending', $irec['form_status']];
        echo "<span class=\"status-badge {$cls}\">{$lbl}</span>";
    ?>
</div>

<?php /* ── Edit / Cancel toolbar — shown in view mode within 1 hour ── */ ?>
<?php if ($canEditOrCancel): ?>
<div class="ec-toolbar">
    <span class="ec-timer" id="ecTimer">⏱ Edit/Cancel window: <strong id="ecCountdown">--:--</strong> remaining</span>
    <a href="index.php?page=interruptions_11kv&action=edit&id=<?= $irec['id'] ?>"
       class="btn-edit-ticket">✏️ Edit Ticket</a>
    <button class="btn-cancel-ticket" onclick="showCancelPanel()">✖ Cancel Ticket</button>
</div>
<?php endif; ?>

<?php /* ── Action buttons (view mode) ── */ ?>
<?php if ($canComplete): ?>
<a href="index.php?page=interruptions_11kv&action=complete&id=<?= $irec['id'] ?>"
   class="btn-complete-interruption">⚡ Complete Interruption &nbsp;— Power has been restored</a>

<?php elseif ($mode === 'view' && $irec['form_status'] === 'AWAITING_APPROVAL'): ?>
<div class="info-box blue">
    🔄 <strong>Awaiting approval.</strong>
    Stage 2 will unlock once UL3/UL4 approves the request.
    You may still edit or cancel this ticket within the 1-hour window above.
</div>

<?php elseif ($mode === 'view' && $irec['form_status'] === 'CANCELLED'): ?>
<div class="info-box red">
    ✖ <strong>This ticket has been cancelled.</strong>
    Please check the audit log for the reason.
</div>
<?php endif; ?>
<?php endif; /* end $irec */ ?>

<?php /* ── Page error banner ── */ ?>
<?php if ($pageError): ?>
<div class="info-box blue">⚠️ <?= htmlspecialchars($pageError) ?></div>
<?php endif; ?>

<?php /* ── Page title ── */ ?>
<h1 class="page-title">
    <?php if ($mode === 'new'):            ?>⚡ Log 11kV Interruption
    <?php elseif ($mode === 'complete'):   ?>⚡ Complete Interruption — Stage 2
    <?php elseif ($mode === 'edit'):       ?>✏️ Edit Interruption Ticket
    <?php elseif ($mode === 'cancel_confirm'): ?>✖ Cancel Interruption Ticket
    <?php else:                            ?>⚡ Interruption Record
    <?php endif; ?>
</h1>

<p class="page-subtitle">
    <?php if ($mode === 'new'): ?>
        Complete Stage 1 and submit. Return from My Requests once the event ends to complete Stage 2.
    <?php elseif ($mode === 'complete'): ?>
        Stage 1 is locked. Fill restoration details below and click <strong>Finalise Record</strong>.
    <?php elseif ($mode === 'edit'): ?>
        Update Stage 1 fields below. Changes are logged. You have
        <strong id="editCountdown">--:--</strong> remaining in the edit window.
    <?php elseif ($mode === 'cancel_confirm'): ?>
        Confirm cancellation. This action is irreversible and will be logged.
    <?php else: ?>
        11kV Interruption record — read only.
    <?php endif; ?>
</p>

<?php if ($mode === 'new'): ?>
<div class="info-box">
    ⚠️ <strong>Important:</strong> Fill Stage 1 now (feeder → weather). Stage 2 is locked until after the event ends.
    <strong>Planned interruptions do not require approval</strong> and proceed directly to Stage 2.
    Time Out must be logged within <strong>30 minutes</strong> of the actual outage time.
</div>
<?php endif; ?>

<?php if ($mode === 'edit'): ?>
<div class="info-box">
    ✏️ <strong>Editing is allowed for up to 1 hour from ticket creation</strong>, and only before any
    approval or concurrence is recorded. A reason for the edit is required and will be logged.
    Time Out must still be within the <strong>30-minute</strong> logging window.
</div>
<?php endif; ?>

<?php if ($mode === 'complete'): ?>
<div class="info-box blue">
    ℹ️ <strong>Note:</strong> Time In (restoration) must be logged within <strong>30 minutes</strong>
    of the actual restoration time. If more than 30 minutes have elapsed, you will be required to
    provide a reason for the late entry.
</div>
<?php endif; ?>

<div id="approvalNotice" class="approval-notice"
     <?= ($irec && $irec['requires_approval']==='YES') ? 'style="display:block"' : '' ?>>
    ℹ️ <strong>Approval Required:</strong> This interruption code requires UL3/UL4 approval before execution.
</div>

<div id="alertBox" class="alert"></div>

<?php /* ══ CANCEL CONFIRM PANEL ══ */ ?>
<?php if ($mode === 'cancel_confirm'): ?>
<div class="cancel-confirm-box">
    <h3>✖ Confirm Cancellation</h3>
    <p>You are about to cancel ticket <strong><?= htmlspecialchars($irec['ticket_number']) ?></strong>
       — <em><?= htmlspecialchars($irec['fdr11kv_name']) ?></em>.</p>
    <p>This is <strong>irreversible</strong> and will be recorded in the audit log.</p>
    <div class="form-group" style="margin-top:16px;">
        <label>Reason for Cancellation <span class="required">*</span></label>
        <textarea id="cancelReason" rows="3"
                  placeholder="State clearly why this ticket is being cancelled..."
                  style="width:100%;padding:10px;border:1.5px solid #fca5a5;border-radius:8px;font-size:14px;"></textarea>
    </div>
    <div style="display:flex;gap:12px;margin-top:14px;">
        <button class="btn-primary" onclick="confirmCancel(<?= $irec['id'] ?>)">✖ Confirm Cancellation</button>
        <a href="index.php?page=interruptions_11kv&action=my-requests" class="btn-secondary">← Back</a>
    </div>
</div>

<?php else: /* ══ MAIN FORM ══ */ ?>

<form id="interruptionForm">
<?php if ($irec): ?>
    <input type="hidden" name="interruption_id" value="<?= $irec['id'] ?>">
<?php endif; ?>

<!-- ════════════ STAGE 1 FIELDS ════════════ -->
<div class="section-head">Stage 1 — Interruption Start
    <?php if ($stage1Locked && $mode !== 'edit'): ?>
        <span style="font-size:11px;font-weight:400;color:#9ca3af;">(locked)</span>
    <?php endif; ?>
</div>

<div class="form-row">
    <div class="form-group">
        <label>11kV Feeder <span class="required">*</span></label>
        <select name="fdr11kv_code" id="feederSelect" <?= $d1 ?> <?= !$d1 ? 'required' : '' ?>>
            <option value="">-- Select Feeder --</option>
            <?php foreach ($feeders_11kv as $fdr): ?>
            <option value="<?= htmlspecialchars($fdr['fdr11kv_code']) ?>"
                    <?= ($irec && $irec['fdr11kv_code']===$fdr['fdr11kv_code']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($fdr['fdr11kv_name']) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Interruption Type <span class="required">*</span></label>
        <select name="interruption_type" id="interruptionType" <?= $d1 ?> <?= !$d1 ? 'required' : '' ?>>
            <option value="">-- Select Type --</option>
            <?php foreach ($interruptionTypes as $type): ?>
            <option value="<?= htmlspecialchars($type) ?>"
                    <?= ($irec && $irec['interruption_type']===$type) ? 'selected' : '' ?>>
                <?= htmlspecialchars($type) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="form-row">
    <div class="form-group">
        <label>Interruption Code <span class="required">*</span></label>
        <select name="interruption_code" id="interruptionCode"
                <?= $d1 ?> <?= (!$d1 && !$irec) ? 'disabled' : '' ?> <?= !$d1 ? 'required' : '' ?>>
            <option value="">-- Select type first --</option>
            <?php if ($irec): ?>
            <option value="<?= htmlspecialchars($irec['interruption_code']) ?>" selected>
                <?= htmlspecialchars($irec['interruption_code']) ?>
            </option>
            <?php endif; ?>
        </select>
    </div>
    <div class="form-group">
        <label>Code Description</label>
        <input type="text" id="interruptionDescription" readonly
               value="<?= htmlspecialchars($irec['interruption_description'] ?? '') ?>">
    </div>
</div>

<div class="form-row">
    <div class="form-group">
        <label>Interruption Group</label>
        <input type="text" id="interruptionGroup" readonly
               value="<?= htmlspecialchars($irec['interruption_group'] ?? '') ?>">
    </div>
    <div class="form-group">
        <label>Body Responsible</label>
        <input type="text" id="bodyResponsible" readonly
               value="<?= htmlspecialchars($irec['body_responsible'] ?? '') ?>">
    </div>
</div>

<div class="form-row">
    <!-- ── datetime_out ────────────────────────────────────────────────── -->
    <div class="form-group">
        <label>Date/Time Out <span class="required">*</span></label>
        <input type="datetime-local" name="datetime_out" id="datetimeOut"
               <?= $d1 ?> <?= !$d1 ? 'required' : '' ?>
               value="<?= $irec ? date('Y-m-d\TH:i', strtotime($irec['datetime_out'])) : '' ?>">
        <!-- Inline error strip for datetime_out -->
        <div class="dt-error" id="dtOutError"></div>
        <?php if (!$stage1Locked): ?>
        <div style="margin-top:6px;font-size:12px;color:#6b7280;">
            ⏱ You may only log a Time Out that occurred within the last <strong>30 minutes</strong>.
        </div>
        <?php endif; ?>
    </div>

    <!-- ── datetime_in ─────────────────────────────────────────────────── -->
    <div class="form-group <?= $stage2Locked ? 'field-grayed' : '' ?>">
        <label>Date/Time In (Restored) <?= !$stage2Locked ? '<span class="required">*</span>' : '' ?></label>
        <input type="datetime-local" name="datetime_in" id="datetimeIn"
               <?= $d2 ?> <?= !$stage2Locked ? 'required' : '' ?>
               value="<?= ($irec && $irec['datetime_in']) ? date('Y-m-d\TH:i', strtotime($irec['datetime_in'])) : '' ?>">
        <!-- Inline error strip for datetime_in -->
        <div class="dt-error" id="dtInError"></div>
        <?php if (!$stage2Locked): ?>
        <div style="margin-top:6px;font-size:12px;color:#6b7280;">
            ⏱ Entries beyond 30 minutes of restoration time require a reason.
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ── Late Entry Panel — shown when datetime_in exceeds 30-min window ── -->
<?php if ($mode === 'complete'): ?>
<div id="lateEntryPanel">
    <div class="late-entry-header">
        ⚠️ Late Entry Detected — Explanation Required
    </div>
    <div class="late-entry-body">
        The restoration time you entered is more than <strong>30 minutes</strong> ago.
        This entry will be <strong>flagged</strong> for reviewer attention.
        You must provide a reason explaining why the restoration was not logged promptly.
    </div>
    <div class="form-group">
        <label style="color:#c2410c;">
            Reason for Late Entry <span class="required">*</span>
            <span style="font-size:12px;font-weight:400;color:#92400e;">
                — this will appear on the analyst &amp; manager review screens
            </span>
        </label>
        <textarea name="late_entry_reason" id="lateEntryReason" rows="3"
                  placeholder="e.g. System was offline at time of restoration, shift handover delay, communication breakdown between field crew and control room..."
                  style="border-color:#f97316;background:#fffbf5;"><?= htmlspecialchars($irec['late_entry_reason'] ?? '') ?></textarea>
    </div>
</div>
<?php endif; ?>

<div class="form-row">
    <div class="form-group <?= $stage2Locked ? 'field-grayed' : '' ?>">
        <label>Load Loss (MW) <?= !$stage2Locked ? '<span class="required">*</span>' : '' ?></label>
        <input type="number" step="0.01" min="0" name="load_loss"
               placeholder="0.00" <?= $d2 ?> <?= !$stage2Locked ? 'required' : '' ?>
               value="<?= $irec ? htmlspecialchars($irec['load_loss'] ?? '') : '' ?>">
    </div>
    <div class="form-group">
        <label>Weather Condition</label>
        <select name="weather_condition" <?= $d1 ?>>
            <option value="">-- Select Weather --</option>
            <?php foreach (['Clear','Rainy','Stormy','Windy','Foggy'] as $w): ?>
            <option value="<?= $w ?>" <?= ($irec && $irec['weather_condition']===$w) ? 'selected' : '' ?>><?= $w ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<!-- Approval note (hidden for PLANNED type) -->
<div class="form-group full-width" id="approvalNoteGroup"
     <?= ($irec && $irec['requires_approval']==='YES') ? 'style="display:block"' : '' ?>>
    <label>
        Approval Note <?= (!$stage1Locked) ? '<span class="required">*</span>' : '' ?>
        <span style="font-size:12px;font-weight:400;color:#6b7280;">
            — brief note for the approving UL3/UL4
        </span>
    </label>
    <textarea name="approval_note" id="approvalNote" rows="2"
              placeholder="Briefly describe why this interruption requires approval..."
              <?= $d1 ?>><?= htmlspecialchars($irec['approval_note'] ?? '') ?></textarea>
</div>

<!-- Edit reason (edit mode only) -->
<?php if ($mode === 'edit'): ?>
<div class="form-group full-width">
    <label>Reason for Edit <span class="required">*</span></label>
    <textarea name="edit_reason" id="editReason" rows="2" required
              placeholder="State why you are editing this ticket (will be logged)..."
              style="border-color:#f59e0b;"></textarea>
</div>
<?php endif; ?>

<!-- ════════════ STAGE 2 FIELDS ════════════ -->
<div class="section-head" style="margin-top:28px;">Stage 2 — Restoration Details
    <?php if ($stage2Locked && $mode === 'new'): ?>
        <span style="font-size:11px;font-weight:400;color:#9ca3af;">(unlocks after Stage 1 submitted)</span>
    <?php elseif ($mode === 'view' && $stage2Locked): ?>
        <span style="font-size:11px;font-weight:400;color:#9ca3af;">(click "Complete Interruption" above)</span>
    <?php endif; ?>
</div>

<div class="stage2-block <?= !$stage2Locked ? 'active' : '' ?>">

<?php if ($mode === 'new' || $mode === 'edit'): ?>
    <div class="stage2-locked-msg">
        🔒 &nbsp; Submit Stage 1 first. After the event ends open this ticket from
        <strong>My Requests</strong> to unlock Stage 2.
    </div>

<?php elseif ($mode === 'view' && $stage2Locked): ?>
    <?php if ($irec['form_status'] === 'AWAITING_APPROVAL'): ?>
    <div class="stage2-locked-msg" style="color:#1e40af;">
        🔄 &nbsp; Awaiting UL3/UL4 approval before Stage 2 can begin.
    </div>
    <?php elseif ($irec['form_status'] === 'CANCELLED'): ?>
    <div class="stage2-locked-msg" style="color:#991b1b;">✖ &nbsp; Cancelled ticket — no further action.</div>
    <?php else: ?>
    <div class="stage2-locked-msg">
        Click <strong>Complete Interruption</strong> above to unlock Stage 2.
    </div>
    <?php endif; ?>

<?php else: ?>

    <div class="form-group full-width <?= $stage2Locked ? 'field-grayed' : '' ?>">
        <label>Reason for Interruption <?= !$stage2Locked ? '<span class="required">*</span>' : '' ?></label>
        <textarea name="reason_for_interruption" rows="3"
                  placeholder="Describe the cause of interruption..."
                  <?= $d2 ?> <?= !$stage2Locked ? 'required' : '' ?>
                  ><?= htmlspecialchars($irec['reason_for_interruption'] ?? '') ?></textarea>
    </div>

    <div class="form-group full-width <?= $stage2Locked ? 'field-grayed' : '' ?>">
        <label>Resolution / Action Taken</label>
        <textarea name="resolution" rows="3"
                  placeholder="Describe how the issue was resolved..."
                  <?= $d2 ?>><?= htmlspecialchars($irec['resolution'] ?? '') ?></textarea>
    </div>

    <div class="form-group full-width <?= $stage2Locked ? 'field-grayed' : '' ?>">
        <label>Reason for Delay (if applicable)</label>
        <select name="reason_for_delay" id="reasonForDelay" <?= $d2 ?>>
            <option value="">-- No Delay --</option>
            <?php
            $delays = ['DSO communicated late','Lack of vehicle or fuel for patrol',
                       'Lack of staff during restoration work','Lack of material',
                       'Delay to get security','Line in marshy Area',
                       'Technical staff negligence','others'];
            foreach ($delays as $d): ?>
            <option value="<?= htmlspecialchars($d) ?>"
                    <?= ($irec && $irec['reason_for_delay']===$d) ? 'selected' : '' ?>>
                <?= $d === 'others' ? 'Others (Specify)' : htmlspecialchars($d) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="form-group full-width <?= $stage2Locked ? 'field-grayed' : '' ?>"
         id="delayReasonGroup"
         <?= ($irec && $irec['reason_for_delay']==='others') ? 'style="display:block"' : 'style="display:none"' ?>>
        <label>Specify Other Reason</label>
        <input type="text" name="other_reasons"
               placeholder="Specify other reason for delay"
               <?= $d2 ?>
               value="<?= htmlspecialchars($irec['other_reasons'] ?? '') ?>">
    </div>

<?php endif; ?>
</div><!-- /stage2-block -->

<div class="btn-group">
    <?php if ($mode === 'new'): ?>
        <button type="button" class="btn-secondary" onclick="history.back()">Cancel</button>
        <button type="submit" class="btn-primary" id="submitBtn">⚡ Submit Interruption (Stage 1)</button>

    <?php elseif ($mode === 'edit'): ?>
        <a href="index.php?page=interruptions_11kv&action=view&id=<?= $irec['id'] ?>" class="btn-secondary">← Back</a>
        <button type="submit" class="btn-primary" id="submitBtn">✏️ Save Changes</button>

    <?php elseif ($mode === 'complete'): ?>
        <a href="index.php?page=interruptions_11kv&action=my-requests" class="btn-secondary">← My Requests</a>
        <button type="submit" class="btn-primary" id="submitBtn">✅ Finalise Interruption Record</button>

    <?php else: ?>
        <a href="index.php?page=interruptions_11kv&action=my-requests" class="btn-secondary">← My Requests</a>
    <?php endif; ?>
</div>

</form>
<?php endif; /* end cancel_confirm else */ ?>

</div><!-- /card -->
</div><!-- /main-content -->

<script>
const interruptionCodes = <?= json_encode($codesByType) ?>;
const mode = '<?= $mode ?>';
<?php if ($irec): ?>
const datetimeOutVal = '<?= date('Y-m-d\TH:i', strtotime($irec['datetime_out'])) ?>';
<?php else: ?>
const datetimeOutVal = '';
<?php endif; ?>

/* ── Current local time ──────────────────────────────────────────────────── */
function localNow() { return new Date(Date.now()); }

/* ═══════════════════════════════════════════════════════════════════════════
   DATETIME OUT VALIDATION  (new mode + edit mode)

   Rule 1 — Hard block (too far past):
     now − entered > 30 min  →  reject: "beyond 30-minute logging window"

   Rule 2 — Hard block (future):
     entered > now + 5 min   →  reject: "cannot be in the future"
     (5-min grace absorbs browser/server clock drift)
   ═══════════════════════════════════════════════════════════════════════════ */
<?php if (in_array($mode, ['new','edit'])): ?>
function validateDatetimeOut(el) {
    const errDiv = document.getElementById('dtOutError');
    const dtOut  = el.value ? new Date(el.value) : null;

    if (!dtOut || isNaN(dtOut.getTime())) {
        el.setCustomValidity('');
        errDiv.style.display = 'none';
        return true;
    }

    const now     = localNow();
    const diffMin = (now - dtOut) / 60000;   // positive = past, negative = future

    // Block future entries
    if (diffMin < -5) {
        const msg = 'Time Out cannot be in the future. Please enter the actual outage time.';
        el.setCustomValidity(msg);
        errDiv.textContent   = '⚠️ ' + msg;
        errDiv.style.display = 'block';
        return false;
    }

    // Block entries older than 30 minutes
    if (diffMin > 30) {
        const mins = Math.floor(diffMin);
        const msg  = 'This Time Out was ' + mins + ' minute' + (mins !== 1 ? 's' : '') +
                     ' ago — beyond the 30-minute logging window. ' +
                     'You can only log outages that occurred within the last 30 minutes.';
        el.setCustomValidity(msg);
        errDiv.textContent   = '⚠️ ' + msg;
        errDiv.style.display = 'block';
        return false;
    }

    // Valid
    el.setCustomValidity('');
    errDiv.style.display = 'none';
    return true;
}

document.getElementById('datetimeOut').addEventListener('change', function() {
    validateDatetimeOut(this);
    if (this.validationMessage) this.reportValidity();
});
<?php endif; ?>

/* ═══════════════════════════════════════════════════════════════════════════
   DATETIME IN VALIDATION  (complete mode only)

   Rule A — Hard block:
     datetime_in ≤ datetime_out  →  reject (must be after outage start)

   Rule B — Hard block:
     datetime_in > now + 5 min   →  reject (cannot be in the future)

   Rule C — Soft block (shows late-entry panel, does NOT block submit):
     now − datetime_in > 30 min  →  show panel, make reason required

   Rule D — Clear:
     now − datetime_in ≤ 30 min  →  hide panel, clear reason
   ═══════════════════════════════════════════════════════════════════════════ */
<?php if ($mode === 'complete'): ?>
function validateDatetimeIn(el) {
    const errDiv     = document.getElementById('dtInError');
    const latePanel  = document.getElementById('lateEntryPanel');
    const lateReason = document.getElementById('lateEntryReason');
    const dtIn       = el.value ? new Date(el.value) : null;

    // Clear everything when field is empty
    if (!dtIn || isNaN(dtIn.getTime())) {
        el.setCustomValidity('');
        errDiv.style.display    = 'none';
        latePanel.style.display = 'none';
        lateReason.required     = false;
        return true;
    }

    const now     = localNow();
    const dtOut   = datetimeOutVal ? new Date(datetimeOutVal) : null;
    const diffMin = (now - dtIn) / 60000;   // positive = past, negative = future

    // ── Rule A: must be after datetime_out ──────────────────────────────
    if (dtOut && dtIn <= dtOut) {
        const msg = 'Restoration time must be after the interruption start time ('
                  + dtOut.toLocaleString() + ').';
        el.setCustomValidity(msg);
        errDiv.textContent      = '⚠️ ' + msg;
        errDiv.style.display    = 'block';
        latePanel.style.display = 'none';
        lateReason.required     = false;
        return false;
    }

    // ── Rule B: block future entries beyond 5-min drift allowance ───────
    if (diffMin < -5) {
        const msg = 'Restoration time cannot be set more than 5 minutes into the future.';
        el.setCustomValidity(msg);
        errDiv.textContent      = '⚠️ ' + msg;
        errDiv.style.display    = 'block';
        latePanel.style.display = 'none';
        lateReason.required     = false;
        return false;
    }

    // Clear hard-block state before soft check
    el.setCustomValidity('');
    errDiv.style.display = 'none';

    // ── Rule C: soft block — late entry > 30 min ────────────────────────
    if (diffMin > 30) {
        const mins = Math.floor(diffMin);
        latePanel.style.display = 'block';
        lateReason.required     = true;

        // Update header to show exact lag
        const header = latePanel.querySelector('.late-entry-header');
        if (header) {
            header.textContent = '⚠️ Late Entry Detected — ' + mins +
                                 ' minute' + (mins !== 1 ? 's' : '') +
                                 ' ago (Explanation Required)';
        }
        return true;   // not a hard block — submission allowed with reason
    }

    // ── Rule D: within window — hide panel ──────────────────────────────
    latePanel.style.display = 'none';
    lateReason.required     = false;
    lateReason.value        = '';
    return true;
}

document.getElementById('datetimeIn').addEventListener('change', function() {
    validateDatetimeIn(this);
    if (this.validationMessage) this.reportValidity();
});

// ── Delay reason toggle ──────────────────────────────────────────────────
document.getElementById('reasonForDelay').addEventListener('change', function() {
    document.getElementById('delayReasonGroup').style.display =
        this.value === 'others' ? 'block' : 'none';
});
<?php endif; ?>

/* ── Edit/Cancel countdown ───────────────────────────────────────────────── */
<?php if ($canEditOrCancel && $ecSecondsLeft > 0): ?>
(function() {
    let secs = <?= (int)$ecSecondsLeft ?>;
    const el = document.getElementById('ecCountdown');
    function tick() {
        if (secs <= 0) {
            document.getElementById('ecTimer').textContent = '⏱ Edit/Cancel window has expired.';
            document.querySelectorAll('.btn-edit-ticket,.btn-cancel-ticket').forEach(function(b) {
                b.disabled = true; b.style.opacity = '0.4'; b.style.pointerEvents = 'none';
            });
            return;
        }
        const m = String(Math.floor(secs / 60)).padStart(2, '0');
        const s = String(secs % 60).padStart(2, '0');
        el.textContent = m + ':' + s;
        secs--;
        setTimeout(tick, 1000);
    }
    tick();
})();
<?php endif; ?>

<?php if ($mode === 'edit' && $editWindowSecondsLeft > 0): ?>
(function() {
    let secs = <?= (int)$editWindowSecondsLeft ?>;
    const el = document.getElementById('editCountdown');
    function tick() {
        if (!el) return;
        const m = String(Math.floor(secs / 60)).padStart(2, '0');
        const s = String(secs % 60).padStart(2, '0');
        el.textContent = m + ':' + s;
        if (secs <= 0) { el.style.color = '#dc2626'; return; }
        secs--;
        setTimeout(tick, 1000);
    }
    tick();
})();
<?php endif; ?>

/* ── Cascading dropdowns (new + edit mode) ───────────────────────────────── */
<?php if (in_array($mode, ['new','edit'])): ?>
document.getElementById('interruptionType').addEventListener('change', function() {
    const cs = document.getElementById('interruptionCode');
    cs.innerHTML = '<option value="">-- Select Code --</option>';
    cs.disabled  = !this.value;
    document.getElementById('interruptionDescription').value = '';
    document.getElementById('interruptionGroup').value       = '';
    document.getElementById('bodyResponsible').value         = '';
    document.getElementById('approvalNotice').style.display  = 'none';
    document.getElementById('approvalNoteGroup').style.display = 'none';

    // PLANNED type — never shows approval notice
    const isPlanned = this.value && this.value.toUpperCase() === 'PLANNED';

    if (this.value && interruptionCodes[this.value]) {
        interruptionCodes[this.value].forEach(function(code) {
            const o = document.createElement('option');
            o.value = code.interruption_code;
            o.textContent = code.interruption_code;
            o.dataset.description = code.interruption_description;
            o.dataset.group       = code.interruption_group;
            o.dataset.body        = code.body_responsible;
            o.dataset.approval    = isPlanned ? 'NO' : code.approval_requirement;
            cs.appendChild(o);
        });
    }
});

document.getElementById('interruptionCode').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (!opt || !opt.value) return;
    document.getElementById('interruptionDescription').value = opt.dataset.description || '';
    document.getElementById('interruptionGroup').value       = opt.dataset.group       || '';
    document.getElementById('bodyResponsible').value         = opt.dataset.body        || '';

    const typeEl    = document.getElementById('interruptionType');
    const isPlanned = typeEl && typeEl.value.toUpperCase() === 'PLANNED';
    const needsApproval = !isPlanned && opt.dataset.approval === 'YES';

    document.getElementById('approvalNotice').style.display    = needsApproval ? 'block' : 'none';
    document.getElementById('approvalNoteGroup').style.display = needsApproval ? 'block' : 'none';
    if (document.getElementById('approvalNote'))
        document.getElementById('approvalNote').required = needsApproval;
});
<?php endif; ?>

/* ═══════════════════════════════════════════════════════════════════════════
   FORM SUBMISSION
   ═══════════════════════════════════════════════════════════════════════════ */
<?php if (in_array($mode, ['new','complete','edit'])): ?>
document.getElementById('interruptionForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const btn  = document.getElementById('submitBtn');
    const box  = document.getElementById('alertBox');

    const urls   = { new: 'ajax/interruption_11kv_log.php',
                     edit: 'ajax/interruption_11kv_edit.php',
                     complete: 'ajax/interruption_11kv_complete.php' };
    const labels = { new:      ['Saving…',     '⚡ Submit Interruption (Stage 1)'],
                     edit:     ['Saving…',     '✏️ Save Changes'],
                     complete: ['Finalising…', '✅ Finalise Interruption Record'] };

    /* ── Pre-submit re-validation (clock advances while user fills form) ── */
    if (mode === 'new' || mode === 'edit') {
        const dtOutEl = document.getElementById('datetimeOut');
        if (dtOutEl && !validateDatetimeOut(dtOutEl)) {
            dtOutEl.reportValidity();
            return;
        }
    }

    if (mode === 'complete') {
        const dtInEl = document.getElementById('datetimeIn');
        if (dtInEl) {
            validateDatetimeIn(dtInEl);
            if (dtInEl.validationMessage) {
                dtInEl.reportValidity();
                return;
            }
        }

        // If late-entry panel is visible, reason must be filled
        const latePanel  = document.getElementById('lateEntryPanel');
        const lateReason = document.getElementById('lateEntryReason');
        if (latePanel && latePanel.style.display !== 'none') {
            if (!lateReason.value.trim()) {
                lateReason.setCustomValidity('Please provide a reason for the late entry.');
                lateReason.reportValidity();
                return;
            }
            lateReason.setCustomValidity('');
        }
    }

    btn.disabled    = true;
    btn.textContent = labels[mode][0];

    fetch(urls[mode], { method: 'POST', body: new FormData(this) })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        box.style.display = 'block';
        if (res.success) {
            box.className = 'alert success';
            box.innerHTML = '✅ ' + res.message
                + (res.ticket_number
                    ? ' &nbsp;— Ticket: <strong style="font-family:monospace;font-size:16px;">'
                      + res.ticket_number + '</strong>' : '');
            setTimeout(function() {
                window.location.href = 'index.php?page=interruptions_11kv&action=my-requests';
            }, 2500);
        } else {
            box.className   = 'alert error';
            box.textContent = '❌ ' + res.message;
            btn.disabled    = false;
            btn.textContent = labels[mode][1];
        }
    })
    .catch(function(err) {
        box.className     = 'alert error';
        box.style.display = 'block';
        box.textContent   = '❌ Network error: ' + err.message;
        btn.disabled      = false;
        btn.textContent   = labels[mode][1];
    });
});
<?php endif; ?>

/* ── Cancel panel / confirm ──────────────────────────────────────────────── */
function showCancelPanel() {
    window.location.href =
        'index.php?page=interruptions_11kv&action=cancel&id=<?= $irec ? $irec['id'] : 0 ?>';
}

function confirmCancel(id) {
    const reason = document.getElementById('cancelReason').value.trim();
    const box    = document.getElementById('alertBox');
    if (!reason) { alert('Please enter a reason for cancellation.'); return; }

    const fd = new FormData();
    fd.append('interruption_id', id);
    fd.append('cancel_reason',   reason);

    fetch('ajax/interruption_11kv_cancel.php', { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        box.style.display = 'block';
        if (res.success) {
            box.className   = 'alert success';
            box.textContent = '✅ ' + res.message;
            setTimeout(function() {
                window.location.href = 'index.php?page=interruptions_11kv&action=my-requests';
            }, 2000);
        } else {
            box.className   = 'alert error';
            box.textContent = '❌ ' + res.message;
        }
    })
    .catch(function(err) {
        box.className     = 'alert error';
        box.style.display = 'block';
        box.textContent   = '❌ Network error: ' + err.message;
    });
}
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
