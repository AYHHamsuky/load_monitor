<?php
/**
 * 33kV Interruption Form — single file, three modes
 * Path: /app/views/interruptions/log_form.php
 *
 * $mode (set by controller):
 *   'new'      → fresh form, Stage-1 active, Stage-2 grayed
 *   'view'     → all locked, "Complete Interruption" button at top if PENDING_COMPLETION
 *   'complete' → Stage-1 locked, Stage-2 active, final submit button
 *
 * $interruption  → the record array (null when mode='new')
 * $feeders_33kv  → passed by controller
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

// Determine what's locked/unlocked
$stage1Locked = ($mode !== 'new');          // Stage-1 locked in view and complete modes
$stage2Locked = ($mode !== 'complete');     // Stage-2 only active in complete mode

// Is this a pending record the user can complete?
$canComplete = $irec
    && $mode === 'view'
    && in_array($irec['form_status'], ['PENDING_COMPLETION', 'PENDING_COMPLETION_APPROVED']);

// Helper — html attribute string for disabling
$d1 = $stage1Locked ? 'disabled' : '';  // Stage-1 fields
$d2 = $stage2Locked ? 'disabled' : '';  // Stage-2 fields
?>

<style>
/* ── Base layout ────────────────────────────────────────────────────────── */
.main-content{margin-left:260px;padding:22px;padding-top:90px;min-height:calc(100vh - 64px);background:#f4f6fa;}
.interruption-card{background:#fff;border-radius:14px;padding:28px;box-shadow:0 10px 30px rgba(0,0,0,.08);max-width:1000px;margin:0 auto;}
.page-title{font-size:24px;font-weight:700;color:#0f172a;margin-bottom:8px;}
.page-subtitle{color:#6b7280;font-size:14px;margin-bottom:24px;}

/* ── Ticket banner ──────────────────────────────────────────────────────── */
.ticket-banner{
    display:flex;align-items:center;gap:20px;
    background:linear-gradient(135deg,#0f172a,#1e3a5f);
    color:#fff;border-radius:12px;padding:16px 22px;margin-bottom:22px;
}
.ticket-num{font-family:monospace;font-size:24px;font-weight:900;letter-spacing:3px;}
.ticket-meta{font-size:12px;opacity:.75;margin-top:2px;}
.status-badge{
    margin-left:auto;padding:5px 16px;border-radius:20px;
    font-size:11px;font-weight:700;text-transform:uppercase;white-space:nowrap;
}
.badge-pending   {background:#fef3c7;color:#92400e;}
.badge-approval  {background:#dbeafe;color:#1e40af;}
.badge-approved  {background:#d1fae5;color:#065f46;}
.badge-completed {background:#dcfce7;color:#166534;}

/* ── Complete button ────────────────────────────────────────────────────── */
.btn-complete-interruption{
    display:inline-flex;align-items:center;gap:8px;
    background:linear-gradient(135deg,#1e40af,#1d4ed8);
    color:#fff;border:none;padding:13px 28px;border-radius:10px;
    font-size:15px;font-weight:700;cursor:pointer;
    box-shadow:0 4px 14px rgba(30,64,175,.35);
    text-decoration:none;margin-bottom:22px;
}
.btn-complete-interruption:hover{transform:translateY(-1px);box-shadow:0 6px 18px rgba(30,64,175,.4);}

/* ── Form structure ─────────────────────────────────────────────────────── */
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:18px;}
.form-group{margin-bottom:18px;}
.form-group.full-width{grid-column:1/-1;}
.form-group label{display:block;font-weight:600;margin-bottom:7px;font-size:14px;color:#374151;}
.form-group label .required{color:#dc2626;}
.form-group input,.form-group select,.form-group textarea{
    width:100%;padding:10px 14px;border-radius:8px;border:1.5px solid #d1d5db;
    font-size:14px;transition:all .2s;
}
.form-group textarea{resize:vertical;min-height:80px;font-family:inherit;}
.form-group input:focus,.form-group select:focus,.form-group textarea:focus{
    outline:none;border-color:#dc2626;box-shadow:0 0 0 3px rgba(220,38,38,.1);
}
/* Locked/grayed appearance */
.form-group input:disabled,.form-group select:disabled,.form-group textarea:disabled,
.form-group input[readonly]{background:#f3f4f6;color:#6b7280;cursor:not-allowed;}

/* Stage-2 wrapper when grayed */
.stage2-block{border-radius:10px;padding:20px;border:2px dashed #d1d5db;background:#fafafa;margin-top:8px;}
.stage2-block.active{border-color:#3b82f6;background:#fff;}
.stage2-locked-msg{
    text-align:center;padding:22px;color:#9ca3af;font-size:14px;
    font-style:italic;pointer-events:none;
}
/* Gray overlay for individual grayed fields */
.field-grayed{opacity:.5;pointer-events:none;}

/* ── Notices ────────────────────────────────────────────────────────────── */
.info-box{background:#fef3c7;border-left:4px solid #f59e0b;padding:14px 18px;border-radius:6px;margin-bottom:20px;font-size:13px;color:#92400e;}
.approval-notice{background:#dbeafe;border-left:4px solid #3b82f6;padding:14px 18px;border-radius:6px;margin-bottom:20px;font-size:13px;color:#1e40af;display:none;}
#approvalNoteGroup{display:none;}

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

/* ── Datetime validation error strip ────────────────────────────────────── */
.dt-error{
    margin-top:6px;padding:7px 12px;border-radius:6px;
    background:#fee2e2;border:1px solid #fca5a5;
    color:#dc2626;font-size:12px;font-weight:600;
    display:none;
}

/* ── Section divider ────────────────────────────────────────────────────── */
.section-head{
    display:flex;align-items:center;gap:10px;
    margin:24px 0 16px;font-size:13px;font-weight:700;color:#374151;
}
.section-head::before,.section-head::after{content:'';flex:1;height:1px;background:#e5e7eb;}

/* ── Buttons ────────────────────────────────────────────────────────────── */
.btn-group{display:flex;gap:12px;margin-top:28px;flex-wrap:wrap;}
.btn-primary{
    background:linear-gradient(135deg,#dc2626,#991b1b);color:#fff;border:none;
    padding:12px 28px;border-radius:8px;cursor:pointer;font-weight:600;font-size:14px;transition:all .2s;
}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(220,38,38,.3);}
.btn-primary:disabled{opacity:.6;cursor:not-allowed;transform:none;}
.btn-secondary{
    background:#e5e7eb;color:#374151;border:none;padding:12px 28px;
    border-radius:8px;cursor:pointer;font-weight:600;font-size:14px;text-decoration:none;display:inline-block;
}
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

<?php /* ── TICKET BANNER (existing records only) ─────────────────────── */ ?>
<?php if ($irec): ?>
<div class="ticket-banner">
    <div>
        <div style="font-size:11px;opacity:.6;font-weight:600;text-transform:uppercase;letter-spacing:1px;">Ticket Number</div>
        <div class="ticket-num"><?= htmlspecialchars($irec['ticket_number']) ?></div>
        <div class="ticket-meta">
            <?= htmlspecialchars($irec['fdr33kv_name']) ?> &nbsp;·&nbsp;
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
        ];
        [$cls, $label] = $statusMap[$irec['form_status']] ?? ['badge-pending', $irec['form_status']];
        echo "<span class=\"status-badge {$cls}\">{$label}</span>";
    ?>
</div>

<?php /* ── "COMPLETE INTERRUPTION" BUTTON — shown in view mode when pending */ ?>
<?php if ($canComplete): ?>
<a href="index.php?page=interruptions&action=complete&id=<?= $irec['id'] ?>"
   class="btn-complete-interruption">
    ⚡ Complete Interruption &nbsp; — &nbsp; Power has been restored
</a>
<?php elseif ($mode === 'view' && $irec['form_status'] === 'AWAITING_APPROVAL'): ?>
<div class="info-box" style="background:#dbeafe;border-color:#3b82f6;color:#1e40af;">
    🔄 <strong>Awaiting approval.</strong> Stage 2 of this form will unlock once UL3/UL4 approves the request.
</div>
<?php endif; ?>
<?php endif; ?>

<h1 class="page-title">
    <?php if ($mode === 'new'):     ?>⚡ Log 33kV Interruption
    <?php elseif ($mode === 'complete'): ?>⚡ Complete Interruption
    <?php else:                     ?>⚡ Interruption Record
    <?php endif; ?>
</h1>
<p class="page-subtitle">
    <?php if ($mode === 'new'): ?>
        Fill feeder through weather condition and submit. Return from My Requests after the event to complete the record.
    <?php elseif ($mode === 'complete'): ?>
        Stage 1 is locked. Fill the restoration details below and click <strong>Finalise Record</strong>.
    <?php else: ?>
        33kV Interruption record — read only.
    <?php endif; ?>
</p>

<?php if ($mode === 'new'): ?>
<div class="info-box">
    ⚠️ <strong>Important:</strong>
    Only fill Stage 1 now (feeder to weather). Stage 2 (restoration details) is locked until after the event ends.
    Time Out must be logged within <strong>30 minutes</strong> of the actual outage time.
</div>
<?php endif; ?>

<?php if ($mode === 'complete'): ?>
<div class="info-box" style="background:#eff6ff;border-color:#3b82f6;color:#1e3a8a;">
    ℹ️ <strong>Note:</strong>
    Time In (restoration) must be logged within <strong>30 minutes</strong> of the actual restoration time.
    If more than 30 minutes have elapsed, you will be required to provide a reason for the late entry.
</div>
<?php endif; ?>

<div id="approvalNotice" class="approval-notice"
     <?= ($irec && $irec['requires_approval']==='YES') ? 'style="display:block"' : '' ?>>
    ℹ️ <strong>Approval Required:</strong> This interruption code requires approval before execution.
</div>

<div id="alertBox" class="alert"></div>

<form id="interruptionForm">
<?php if ($irec): ?>
    <input type="hidden" name="interruption_id" value="<?= $irec['id'] ?>">
<?php endif; ?>

<!-- ════════════ STAGE 1 FIELDS ════════════ -->
<div class="section-head">Stage 1 — Interruption Start
    <?php if ($stage1Locked): ?>
        <span style="font-size:11px;font-weight:400;color:#9ca3af;">(locked)</span>
    <?php endif; ?>
</div>

<div class="form-row">
    <div class="form-group">
        <label>33kV Feeder <span class="required">*</span></label>
        <select name="fdr33kv_code" id="feederSelect" <?= $d1 ?> <?= !$d1 ? 'required' : '' ?>>
            <option value="">-- Select Feeder --</option>
            <?php foreach ($feeders_33kv as $fdr): ?>
            <option value="<?= htmlspecialchars($fdr['fdr33kv_code']) ?>"
                <?= ($irec && $irec['fdr33kv_code']===$fdr['fdr33kv_code']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($fdr['fdr33kv_name']) ?>
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
    <!-- ── datetime_out ─────────────────────────────────────────────── -->
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

    <!-- ── datetime_in ──────────────────────────────────────────────── -->
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

<!-- Approval note — only shown/required for approval-required codes -->
<div class="form-group full-width" id="approvalNoteGroup"
     <?= ($irec && $irec['requires_approval']==='YES') ? 'style="display:block"' : '' ?>>
    <label>
        Approval Note
        <?= (!$stage1Locked) ? '<span class="required">*</span>' : '' ?>
        <span style="font-size:12px;font-weight:400;color:#6b7280;">
            — brief note for the approving authority (UL3/UL4)
        </span>
    </label>
    <textarea name="approval_note" id="approvalNote" rows="2"
              placeholder="Briefly describe why this interruption requires approval..."
              <?= $d1 ?>><?= htmlspecialchars($irec['approval_note'] ?? '') ?></textarea>
</div>

<!-- ════════════ STAGE 2 FIELDS ════════════ -->
<div class="section-head" style="margin-top:28px;">Stage 2 — Restoration Details
    <?php if ($stage2Locked && $mode !== 'view'): ?>
    <span style="font-size:11px;font-weight:400;color:#9ca3af;">(unlocks after Stage 1 is submitted)</span>
    <?php elseif ($mode === 'view' && $stage2Locked): ?>
    <span style="font-size:11px;font-weight:400;color:#9ca3af;">(click "Complete Interruption" above to unlock)</span>
    <?php endif; ?>
</div>

<div class="stage2-block <?= !$stage2Locked ? 'active' : '' ?>">

<?php if ($mode === 'new'): ?>
    <!-- Placeholder shown on a fresh form -->
    <div class="stage2-locked-msg">
        🔒 &nbsp; Submit Stage 1 first. After the event ends, open this ticket from
        <strong>My Requests</strong> to unlock and complete Stage 2.
    </div>
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
            $delays = [
                'DSO communicated late',
                'Lack of vehicle or fuel for patrol',
                'Lack of staff during restoration work',
                'Lack of material',
                'Delay to get security',
                'Line in marshy Area',
                'Technical staff negligence',
                'others',
            ];
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
        <button type="submit" class="btn-primary" id="submitBtn">
            ⚡ Submit Interruption (Stage 1)
        </button>

    <?php elseif ($mode === 'complete'): ?>
        <a href="index.php?page=interruptions&action=my-requests" class="btn-secondary">← My Requests</a>
        <button type="submit" class="btn-primary" id="submitBtn">
            ✅ Finalise Interruption Record
        </button>

    <?php else: ?>
        <a href="index.php?page=interruptions&action=my-requests" class="btn-secondary">← My Requests</a>
    <?php endif; ?>
</div>

</form>
</div><!-- /card -->
</div><!-- /main-content -->

<script>
const interruptionCodes = <?= json_encode($codesByType) ?>;
const mode = '<?= $mode ?>';

/* ── Current local time ─────────────────────────────────────────────────── */
function localNow() { return new Date(Date.now()); }

/* ═══════════════════════════════════════════════════════════════════════════
   DATETIME OUT VALIDATION (new mode only)
   Rule: now − entered ≤ 30 minutes
   The entered time must be no more than 30 minutes in the past.
   Future entries are also blocked (can't pre-log an outage that hasn't happened).
   ═══════════════════════════════════════════════════════════════════════════ */
<?php if ($mode === 'new'): ?>
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

    // Block future entries (more than 5 min ahead to allow clock drift)
    if (diffMin < -5) {
        const msg = `Time Out cannot be in the future. Please enter the actual outage time.`;
        el.setCustomValidity(msg);
        errDiv.textContent  = '⚠️ ' + msg;
        errDiv.style.display = 'block';
        return false;
    }

    // Block entries older than 30 minutes
    if (diffMin > 30) {
        const mins = Math.floor(diffMin);
        const msg  = `This Time Out was ${mins} minute${mins !== 1 ? 's' : ''} ago — beyond the 30-minute logging window. `
                   + `You can only log outages that occurred within the last 30 minutes.`;
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
   DATETIME IN VALIDATION (complete mode only)

   Rule A (hard — blocks submission):
     datetime_in must be AFTER datetime_out.

   Rule B (soft — shows late-entry panel, does NOT block):
     now − entered > 30 minutes → user must supply a reason.
     If now − entered ≤ 30 min  → late-entry panel hidden, reason not required.
   ═══════════════════════════════════════════════════════════════════════════ */
<?php if ($mode === 'complete'): ?>
const _dtOutVal = '<?= $irec ? date('Y-m-d\TH:i', strtotime($irec['datetime_out'])) : '' ?>';

function validateDatetimeIn(el) {
    const errDiv      = document.getElementById('dtInError');
    const latePanel   = document.getElementById('lateEntryPanel');
    const lateReason  = document.getElementById('lateEntryReason');
    const dtIn        = el.value ? new Date(el.value) : null;

    // Clear everything if field is empty
    if (!dtIn || isNaN(dtIn.getTime())) {
        el.setCustomValidity('');
        errDiv.style.display  = 'none';
        latePanel.style.display = 'none';
        lateReason.required   = false;
        return true;
    }

    const now     = localNow();
    const dtOut   = _dtOutVal ? new Date(_dtOutVal) : null;
    const diffMin = (now - dtIn) / 60000;   // positive = past, negative = future

    // ── Rule A: must be after datetime_out ──────────────────────────────
    if (dtOut && dtIn <= dtOut) {
        const msg = 'Restoration time must be after the interruption start time.';
        el.setCustomValidity(msg);
        errDiv.textContent   = '⚠️ ' + msg;
        errDiv.style.display = 'block';
        latePanel.style.display = 'none';
        lateReason.required  = false;
        return false;
    }

    // Block future entries beyond 5-minute clock drift allowance
    if (diffMin < -5) {
        const msg = 'Restoration time cannot be set more than 5 minutes into the future.';
        el.setCustomValidity(msg);
        errDiv.textContent   = '⚠️ ' + msg;
        errDiv.style.display = 'block';
        latePanel.style.display = 'none';
        lateReason.required  = false;
        return false;
    }

    // ── Rule B: soft check — late entry > 30 min ───────────────────────
    if (diffMin > 30) {
        const mins = Math.floor(diffMin);
        // Clear hard validation (allowed to submit with reason)
        el.setCustomValidity('');
        errDiv.style.display = 'none';

        // Show late-entry panel and make reason mandatory
        latePanel.style.display = 'block';
        lateReason.required     = true;

        // Update the panel header to show exact lag
        const header = latePanel.querySelector('.late-entry-header');
        if (header) {
            header.textContent = `⚠️ Late Entry Detected — ${mins} minute${mins !== 1 ? 's' : ''} ago (Explanation Required)`;
        }
        return true;   // not a hard block
    }

    // ── All good — within 30-minute window ─────────────────────────────
    el.setCustomValidity('');
    errDiv.style.display    = 'none';
    latePanel.style.display = 'none';
    lateReason.required     = false;
    lateReason.value        = '';
    return true;
}

document.getElementById('datetimeIn').addEventListener('change', function() {
    validateDatetimeIn(this);
    // Only call reportValidity for the hard-block cases
    if (this.validationMessage) this.reportValidity();
});

// ── Delay reason toggle ──────────────────────────────────────────────────
document.getElementById('reasonForDelay').addEventListener('change', function() {
    document.getElementById('delayReasonGroup').style.display =
        this.value === 'others' ? 'block' : 'none';
});
<?php endif; ?>

/* ── Cascading dropdowns (new mode only) ─────────────────────────────────── */
<?php if ($mode === 'new'): ?>
document.getElementById('interruptionType').addEventListener('change', function() {
    const cs = document.getElementById('interruptionCode');
    cs.innerHTML = '<option value="">-- Select Code --</option>';
    cs.disabled  = !this.value;
    document.getElementById('interruptionDescription').value = '';
    document.getElementById('interruptionGroup').value       = '';
    document.getElementById('bodyResponsible').value         = '';
    document.getElementById('approvalNotice').style.display  = 'none';
    document.getElementById('approvalNoteGroup').style.display = 'none';

    if (this.value && interruptionCodes[this.value]) {
        interruptionCodes[this.value].forEach(function(code) {
            const o = document.createElement('option');
            o.value = code.interruption_code;
            o.textContent = code.interruption_code;
            o.dataset.description = code.interruption_description;
            o.dataset.group       = code.interruption_group;
            o.dataset.body        = code.body_responsible;
            o.dataset.approval    = code.approval_requirement;
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
    const needsApproval = opt.dataset.approval === 'YES';
    document.getElementById('approvalNotice').style.display    = needsApproval ? 'block' : 'none';
    document.getElementById('approvalNoteGroup').style.display = needsApproval ? 'block' : 'none';
    document.getElementById('approvalNote').required           = needsApproval;
});
<?php endif; ?>

/* ═══════════════════════════════════════════════════════════════════════════
   FORM SUBMISSION
   ═══════════════════════════════════════════════════════════════════════════ */
<?php if ($mode === 'new' || $mode === 'complete'): ?>
document.getElementById('interruptionForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const btn   = document.getElementById('submitBtn');
    const box   = document.getElementById('alertBox');
    const isNew = (mode === 'new');
    const url   = isNew ? 'ajax/interruption_log.php' : 'ajax/interruption_complete.php';

    /* ── Pre-submit re-validation (clock may have moved since field change) ── */
    if (isNew) {
        // Re-check datetime_out
        const dtOutEl = document.getElementById('datetimeOut');
        if (dtOutEl && !validateDatetimeOut(dtOutEl)) {
            dtOutEl.reportValidity();
            return;
        }
    } else {
        // Re-check datetime_in
        const dtInEl = document.getElementById('datetimeIn');
        if (dtInEl) {
            validateDatetimeIn(dtInEl);
            if (dtInEl.validationMessage) {
                dtInEl.reportValidity();
                return;
            }
        }

        // If late-entry panel is visible, require a reason before submitting
        const latePanel  = document.getElementById('lateEntryPanel');
        const lateReason = document.getElementById('lateEntryReason');
        if (latePanel && latePanel.style.display !== 'none') {
            if (!lateReason.value.trim()) {
                lateReason.setCustomValidity('Please provide a reason for the late entry.');
                lateReason.reportValidity();
                return;
            } else {
                lateReason.setCustomValidity('');
            }
        }
    }

    btn.disabled    = true;
    btn.textContent = isNew ? 'Saving…' : 'Finalising…';

    fetch(url, { method: 'POST', body: new FormData(this) })
    .then(function(r){ return r.json(); })
    .then(function(res) {
        box.style.display = 'block';
        if (res.success) {
            box.className = 'alert success';
            box.innerHTML = '✅ ' + res.message
                + (res.ticket_number
                    ? '&nbsp; — Ticket: <strong style="font-family:monospace;font-size:16px;">'
                      + res.ticket_number + '</strong>' : '');
            setTimeout(function() {
                window.location.href = 'index.php?page=interruptions&action=my-requests';
            }, 2500);
        } else {
            box.className   = 'alert error';
            box.textContent = '❌ ' + res.message;
            btn.disabled    = false;
            btn.textContent = isNew ? '⚡ Submit Interruption (Stage 1)' : '✅ Finalise Interruption Record';
        }
    })
    .catch(function(err) {
        box.className     = 'alert error';
        box.style.display = 'block';
        box.textContent   = '❌ Network error: ' + err.message;
        btn.disabled      = false;
        btn.textContent   = isNew ? '⚡ Submit Interruption (Stage 1)' : '✅ Finalise Interruption Record';
    });
});
<?php endif; ?>
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
