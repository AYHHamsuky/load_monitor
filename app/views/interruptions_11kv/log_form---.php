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
 * $pageError        → optional string shown as error banner (e.g. approval block)
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
.main-content{margin-left:260px;padding:22px;padding-top:90px;min-height:calc(100vh - 64px);background:#f4f6fa;}
.interruption-card{background:#fff;border-radius:14px;padding:28px;box-shadow:0 10px 30px rgba(0,0,0,.08);max-width:1000px;margin:0 auto;}
.page-title{font-size:24px;font-weight:700;color:#0f172a;margin-bottom:8px;}
.page-subtitle{color:#6b7280;font-size:14px;margin-bottom:24px;}

/* Ticket banner */
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

/* Edit/cancel toolbar */
.ec-toolbar{display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap;}
.ec-timer{font-size:13px;color:#92400e;background:#fef3c7;padding:6px 14px;border-radius:20px;font-weight:600;}
.btn-edit-ticket{background:#fbbf24;color:#000;border:none;padding:8px 18px;border-radius:7px;
    font-size:13px;font-weight:700;cursor:pointer;text-decoration:none;display:inline-block;}
.btn-cancel-ticket{background:#dc2626;color:#fff;border:none;padding:8px 18px;border-radius:7px;
    font-size:13px;font-weight:700;cursor:pointer;}

/* Complete button */
.btn-complete-interruption{display:inline-flex;align-items:center;gap:8px;
    background:linear-gradient(135deg,#1e40af,#1d4ed8);color:#fff;border:none;
    padding:13px 28px;border-radius:10px;font-size:15px;font-weight:700;cursor:pointer;
    box-shadow:0 4px 14px rgba(30,64,175,.35);text-decoration:none;margin-bottom:16px;}
.btn-complete-interruption:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(30,64,175,.4);}

/* Form structure */
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

/* Stage 2 */
.stage2-block{border-radius:10px;padding:20px;border:2px dashed #d1d5db;background:#fafafa;margin-top:8px;}
.stage2-block.active{border-color:#3b82f6;background:#fff;}
.stage2-locked-msg{text-align:center;padding:22px;color:#9ca3af;font-size:14px;font-style:italic;}
.field-grayed{opacity:.5;pointer-events:none;}

/* Notices */
.info-box{background:#fef3c7;border-left:4px solid #f59e0b;padding:14px 18px;border-radius:6px;
    margin-bottom:16px;font-size:13px;color:#92400e;}
.info-box.blue{background:#dbeafe;border-color:#3b82f6;color:#1e40af;}
.info-box.red {background:#fee2e2;border-color:#dc2626;color:#991b1b;}
.info-box.green{background:#d1fae5;border-color:#10b981;color:#065f46;}
.approval-notice{background:#dbeafe;border-left:4px solid #3b82f6;padding:14px 18px;border-radius:6px;
    margin-bottom:16px;font-size:13px;color:#1e40af;display:none;}

/* Amendment 3 — 30-min warning banner (shown at top of Stage 2) */
.warn-30min{background:#fff7ed;border-left:4px solid #f97316;padding:14px 18px;border-radius:6px;
    margin-bottom:16px;font-size:13px;color:#9a3412;display:none;}

#approvalNoteGroup{display:none;}
#delayReasonGroup{display:none;}

.section-head{display:flex;align-items:center;gap:10px;margin:24px 0 16px;
    font-size:13px;font-weight:700;color:#374151;}
.section-head::before,.section-head::after{content:'';flex:1;height:1px;background:#e5e7eb;}

/* Cancel confirmation box */
.cancel-confirm-box{background:#fff5f5;border:2px solid #fca5a5;border-radius:12px;padding:24px;margin-top:16px;}
.cancel-confirm-box h3{color:#dc2626;margin-bottom:12px;}

/* Buttons */
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

<?php /* ── Edit / Cancel toolbar (Amendment 6) — shown in view mode within 1 hour ── */ ?>
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
    Stage 2 of this form will unlock once UL3/UL4 approves the request.
    You may still edit or cancel this ticket within the 1-hour window above.
</div>

<?php elseif ($mode === 'view' && $irec['form_status'] === 'CANCELLED'): ?>
<div class="info-box red">
    ✖ <strong>This ticket has been cancelled.</strong>
    Please check the audit log for the reason.
</div>
<?php endif; ?>
<?php endif; ?>

<?php /* ── Page error banner (Amendment 4 — approval block etc.) ── */ ?>
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
</div>
<?php endif; ?>

<?php if ($mode === 'edit'): ?>
<div class="info-box">
    ✏️ <strong>Editing is allowed for up to 1 hour from ticket creation</strong>, and only before any approval or concurrence is recorded.
    A reason for the edit is required and will be logged.
</div>
<?php endif; ?>

<div id="approvalNotice" class="approval-notice"
     <?= ($irec && $irec['requires_approval']==='YES') ? 'style="display:block"' : '' ?>>
    ℹ️ <strong>Approval Required:</strong> This interruption code requires UL3/UL4 approval before execution.
</div>

<div id="alertBox" class="alert"></div>

<?php /* ── CANCEL CONFIRM PANEL (shown inline in cancel_confirm mode) ── */ ?>
<?php if ($mode === 'cancel_confirm'): ?>
<div class="cancel-confirm-box">
    <h3>✖ Confirm Cancellation</h3>
    <p>You are about to cancel ticket <strong><?= htmlspecialchars($irec['ticket_number']) ?></strong>
       — <em><?= htmlspecialchars($irec['fdr11kv_name']) ?></em>.</p>
    <p>This is <strong>irreversible</strong> and will be recorded in the audit log.</p>
    <div class="form-group" style="margin-top:16px;">
        <label>Reason for Cancellation <span class="required">*</span></label>
        <textarea id="cancelReason" rows="3" placeholder="State clearly why this ticket is being cancelled..."
                  style="width:100%;padding:10px;border:1.5px solid #fca5a5;border-radius:8px;font-size:14px;"></textarea>
    </div>
    <div style="display:flex;gap:12px;margin-top:14px;">
        <button class="btn-primary" onclick="confirmCancel(<?= $irec['id'] ?>)">✖ Confirm Cancellation</button>
        <a href="index.php?page=interruptions_11kv&action=my-requests" class="btn-secondary">← Back</a>
    </div>
</div>
<?php else: ?>

<?php /* ══════════ FORM ══════════ */ ?>
<form id="interruptionForm">
<?php if ($irec): ?>
    <input type="hidden" name="interruption_id" value="<?= $irec['id'] ?>">
<?php endif; ?>

<!-- ════ STAGE 1 ════ -->
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
    <div class="form-group">
        <label>Date/Time Out <span class="required">*</span></label>
        <input type="datetime-local" name="datetime_out" id="datetimeOut"
               <?= $d1 ?> <?= !$d1 ? 'required' : '' ?>
               value="<?= $irec ? date('Y-m-d\TH:i', strtotime($irec['datetime_out'])) : '' ?>">
    </div>

    <!-- Date/Time In — Amendment 3 warning triggered on change -->
    <div class="form-group <?= $stage2Locked ? 'field-grayed' : '' ?>">
        <label>Date/Time In (Restored) <?= !$stage2Locked ? '<span class="required">*</span>' : '' ?></label>
        <input type="datetime-local" name="datetime_in" id="datetimeIn"
               <?= $d2 ?> <?= !$stage2Locked ? 'required' : '' ?>
               value="<?= ($irec && $irec['datetime_in']) ? date('Y-m-d\TH:i', strtotime($irec['datetime_in'])) : '' ?>">
    </div>
</div>

<!-- Amendment 3: 30-min warning banner — shown when datetime_in > now + 30 min -->
<div class="warn-30min" id="warn30min">
    ⚠️ <strong>Time Warning:</strong> The restoration time you entered is more than <strong>30 minutes
    in the future</strong>. Please verify this is correct. Entries more than 30 minutes ahead of the
    current time will be rejected on submission.
</div>

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

<?php /* ── Edit reason (edit mode only) ── */ ?>
<?php if ($mode === 'edit'): ?>
<div class="form-group full-width">
    <label>Reason for Edit <span class="required">*</span></label>
    <textarea name="edit_reason" id="editReason" rows="2" required
              placeholder="State why you are editing this ticket (will be logged)..."
              style="border-color:#f59e0b;"></textarea>
</div>
<?php endif; ?>

<!-- ════ STAGE 2 ════ -->
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
        You will be able to complete Stage 2 once approval is granted.
    </div>
    <?php elseif ($irec['form_status'] === 'CANCELLED'): ?>
    <div class="stage2-locked-msg" style="color:#991b1b;">✖ &nbsp; Cancelled ticket — no further action.</div>
    <?php else: ?>
    <div class="stage2-locked-msg">
        Click <strong>Complete Interruption</strong> above to unlock Stage 2.
    </div>
    <?php endif; ?>

<?php else: ?>

    <!-- Amendment 3: 30-min warning at top of Stage 2 in complete mode -->
    <?php if ($mode === 'complete'): ?>
    <div class="info-box" style="margin-bottom:16px;">
        ⏱ <strong>Time check:</strong> The <em>Date/Time In</em> you enter must not be more than
        <strong>30 minutes in the future</strong>. A warning will appear if you exceed this limit.
    </div>
    <?php endif; ?>

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
         <?= ($irec && $irec['reason_for_delay']==='others') ? 'style="display:block"' : '' ?>>
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

// ── Edit/Cancel countdown (Amendment 6) ──────────────────────────────────────
<?php if ($canEditOrCancel && $ecSecondsLeft > 0): ?>
(function() {
    let secs = <?= (int)$ecSecondsLeft ?>;
    const el = document.getElementById('ecCountdown');
    function tick() {
        if (secs <= 0) {
            document.getElementById('ecTimer').textContent = '⏱ Edit/Cancel window has expired.';
            document.querySelectorAll('.btn-edit-ticket,.btn-cancel-ticket').forEach(function(b){
                b.disabled=true; b.style.opacity='0.4'; b.style.pointerEvents='none';
            });
            return;
        }
        const m = String(Math.floor(secs/60)).padStart(2,'0');
        const s = String(secs%60).padStart(2,'0');
        el.textContent = m+':'+s;
        secs--;
        setTimeout(tick,1000);
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
        const m = String(Math.floor(secs/60)).padStart(2,'0');
        const s = String(secs%60).padStart(2,'0');
        el.textContent = m+':'+s;
        if (secs <= 0) { el.style.color='#dc2626'; return; }
        secs--; setTimeout(tick,1000);
    }
    tick();
})();
<?php endif; ?>

// ── Local time helper (+1 hour server offset) ─────────────────────────────────
// The server clock runs 1 hour behind local time; add 3600 s to compensate.
function localNow() { return new Date(Date.now()); }
function localNowStr() {
    const d = localNow();
    return d.getFullYear() + '-' +
        String(d.getMonth()+1).padStart(2,'0') + '-' +
        String(d.getDate()).padStart(2,'0') + 'T' +
        String(d.getHours()).padStart(2,'0') + ':' +
        String(d.getMinutes()).padStart(2,'0');
}

// ── Datetime min (new mode) ───────────────────────────────────────────────────
<?php if ($mode === 'new'): ?>
document.getElementById('datetimeOut').min = localNowStr();
<?php endif; ?>

// ── Cascading dropdowns (new/edit mode) ──────────────────────────────────────
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

    // Amendment 1: PLANNED type — never shows approval notice
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

    // Amendment 1: PLANNED type never needs approval
    const typeEl = document.getElementById('interruptionType');
    const isPlanned = typeEl && typeEl.value.toUpperCase() === 'PLANNED';
    const needsApproval = !isPlanned && opt.dataset.approval === 'YES';

    document.getElementById('approvalNotice').style.display    = needsApproval ? 'block' : 'none';
    document.getElementById('approvalNoteGroup').style.display = needsApproval ? 'block' : 'none';
    if (document.getElementById('approvalNote'))
        document.getElementById('approvalNote').required = needsApproval;
});
<?php endif; ?>

// ── Issue 6: 30-min hard block on datetime_in ───────────────────────────────
// Rule: datetime_in must be ≤ NOW + 30 minutes AND > datetime_out.
// setCustomValidity blocks native form submission and shows browser tooltip.
<?php if ($mode === 'complete'): ?>
function validate11DatetimeIn(el) {
    const warn      = document.getElementById('warn30min');
    const dtOut     = datetimeOutVal ? new Date(datetimeOutVal) : null;
    const dtIn      = el.value ? new Date(el.value) : null;
    const now       = localNow();
    const nowPlus30 = new Date(now.getTime() + 30 * 60 * 1000);

    if (!dtIn) { el.setCustomValidity(''); warn.style.display = 'none'; return; }

    if (dtOut && dtIn <= dtOut) {
        el.setCustomValidity('Restoration time must be after the interruption start time.');
        warn.style.display = 'none';
        return;
    }
    if (dtIn > nowPlus30) {
        const minsAhead = Math.ceil((dtIn - now) / 60000);
        el.setCustomValidity(
            'Restoration time is ' + minsAhead + ' min ahead of now. ' +
            'Maximum allowed is 30 minutes into the future.'
        );
        warn.style.display = 'block';
        return;
    }
    el.setCustomValidity('');
    warn.style.display = 'none';
}

document.getElementById('datetimeIn').addEventListener('change', function() {
    validate11DatetimeIn(this);
    if (this.validationMessage) this.reportValidity();
});
<?php endif; ?>

// ── Delay reason toggle ───────────────────────────────────────────────────────
<?php if ($mode === 'complete'): ?>
document.getElementById('reasonForDelay').addEventListener('change', function() {
    document.getElementById('delayReasonGroup').style.display = this.value === 'others' ? 'block' : 'none';
});
<?php endif; ?>

// ── Form submission ───────────────────────────────────────────────────────────
<?php if (in_array($mode, ['new','complete','edit'])): ?>
document.getElementById('interruptionForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const btn  = document.getElementById('submitBtn');
    const box  = document.getElementById('alertBox');

    let url;
    if (mode === 'new')      url = 'ajax/interruption_11kv_log.php';
    else if (mode === 'edit') url = 'ajax/interruption_11kv_edit.php';
    else                      url = 'ajax/interruption_11kv_complete.php';

    const labels = {
        new:      ['Saving…',     '⚡ Submit Interruption (Stage 1)'],
        edit:     ['Saving…',     '✏️ Save Changes'],
        complete: ['Finalising…', '✅ Finalise Interruption Record'],
    };

    btn.disabled    = true;
    btn.textContent = labels[mode][0];

    // Re-check 30-min rule at moment of submit
    if (mode === 'complete') {
        const dtInEl = document.getElementById('datetimeIn');
        if (dtInEl) {
            validate11DatetimeIn(dtInEl);
            if (dtInEl.validationMessage) {
                dtInEl.reportValidity();
                btn.disabled    = false;
                btn.textContent = labels[mode][1];
                return;
            }
        }
    }

    fetch(url, {method:'POST', body: new FormData(this)})
    .then(r => r.json())
    .then(res => {
        box.style.display = 'block';
        if (res.success) {
            box.className = 'alert success';
            box.innerHTML = '✅ ' + res.message
                + (res.ticket_number
                    ? ' &nbsp;— Ticket: <strong style="font-family:monospace;font-size:16px;">'
                      + res.ticket_number + '</strong>' : '');
            setTimeout(() => {
                window.location.href = 'index.php?page=interruptions_11kv&action=my-requests';
            }, 2500);
        } else {
            box.className   = 'alert error';
            box.textContent = '❌ ' + res.message;
            btn.disabled    = false;
            btn.textContent = labels[mode][1];
        }
    })
    .catch(err => {
        box.className     = 'alert error';
        box.style.display = 'block';
        box.textContent   = '❌ Network error: ' + err.message;
        btn.disabled      = false;
        btn.textContent   = labels[mode][1];
    });
});
<?php endif; ?>

// ── Cancel panel / confirm (Amendment 6) ─────────────────────────────────────
function showCancelPanel() {
    // Navigate to cancel confirm page
    window.location.href = 'index.php?page=interruptions_11kv&action=cancel&id=<?= $irec ? $irec['id'] : 0 ?>';
}

function confirmCancel(id) {
    const reason = document.getElementById('cancelReason').value.trim();
    const box    = document.getElementById('alertBox');
    if (!reason) {
        alert('Please enter a reason for cancellation.');
        return;
    }
    const fd = new FormData();
    fd.append('interruption_id', id);
    fd.append('cancel_reason', reason);
    fetch('ajax/interruption_11kv_cancel.php', {method:'POST', body:fd})
    .then(r => r.json())
    .then(res => {
        box.style.display = 'block';
        if (res.success) {
            box.className = 'alert success';
            box.textContent = '✅ ' + res.message;
            setTimeout(() => {
                window.location.href = 'index.php?page=interruptions_11kv&action=my-requests';
            }, 2000);
        } else {
            box.className   = 'alert error';
            box.textContent = '❌ ' + res.message;
        }
    })
    .catch(err => {
        box.className     = 'alert error';
        box.style.display = 'block';
        box.textContent   = '❌ Network error: ' + err.message;
    });
}
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
