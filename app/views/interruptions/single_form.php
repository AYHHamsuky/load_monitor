<?php
/**
 * Simplified single-form 33kV interruption — matches dispatch team's
 * Excel sheet layout: feeder, type, load-loss, out, in, reason, resolution,
 * weather.  Submits to /ajax/interruption_simple_log.php in one shot.
 *
 * $feeders_33kv      → from controller
 * $interruptionCodes → from controller
 */
require __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/sidebar.php';

$bp = defined('BASE_PATH') ? BASE_PATH : '';
?>

<style>
.main-content { margin-left:252px; padding:24px; padding-top:90px; background:#f4f6fa; min-height:100vh; }
@media (max-width:768px) { .main-content { margin-left:0; padding:74px 12px 16px; } }

.page-head { background:linear-gradient(135deg,#004B23 0%,#006b30 100%); color:#fff; border-radius:12px;
    padding:22px 26px; margin-bottom:20px; box-shadow:0 4px 14px rgba(0,75,35,.25); }
.page-head h1 { font-size:22px; font-weight:700; margin:0 0 4px; display:flex; gap:10px; align-items:center; }
.page-head .sub { opacity:.85; font-size:13px; }

.card { background:#fff; border-radius:12px; padding:24px 26px; box-shadow:0 2px 10px rgba(0,0,0,.07); margin-bottom:20px; }

.form-grid { display:grid; grid-template-columns:repeat(12,1fr); gap:16px; }
.fg { display:flex; flex-direction:column; }
.fg label { font-size:12px; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:.4px; margin-bottom:5px; }
.fg label .req { color:#dc2626; }
.fg input, .fg select, .fg textarea { padding:9px 12px; border:1.5px solid #cbd5e1; border-radius:7px;
    font-size:14px; font-family:inherit; transition:all .15s; background:#fff; }
.fg input:focus, .fg select:focus, .fg textarea:focus { outline:none; border-color:#004B23;
    box-shadow:0 0 0 3px rgba(0,75,35,.1); }
.fg textarea { resize:vertical; min-height:64px; }
.fg .hint { font-size:11px; color:#64748b; margin-top:3px; font-weight:400; text-transform:none; letter-spacing:0; }
.fg.readonly input { background:#f1f5f9; color:#475569; cursor:not-allowed; }
.fg .computed { padding:9px 12px; background:#f8fafc; border-radius:7px; font-family:monospace; font-weight:600;
    color:#0f172a; border:1.5px solid #e2e8f0; min-height:38px; display:flex; align-items:center; }

.col-12 { grid-column:span 12; }  .col-6  { grid-column:span 6; }   .col-4 { grid-column:span 4; }
.col-3  { grid-column:span 3; }   .col-2  { grid-column:span 2; }
@media (max-width:900px) { .col-2,.col-3,.col-4,.col-6 { grid-column:span 12; } }

.approval-box { display:none; background:#fef3c7; border:1px solid #f59e0b; border-radius:8px; padding:14px 16px; margin-top:14px; }
.approval-box.show { display:block; }
.approval-box h4 { color:#92400e; font-size:14px; margin:0 0 8px; }
.approval-box p  { color:#78350f; font-size:13px; margin:0 0 8px; }

.actions { display:flex; gap:12px; justify-content:flex-end; padding-top:8px; }
.btn { padding:11px 22px; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer; border:0;
    display:inline-flex; gap:8px; align-items:center; }
.btn-primary { background:#004B23; color:#fff; }
.btn-primary:hover { background:#006b30; }
.btn-primary:disabled { background:#94a3b8; cursor:not-allowed; }
.btn-secondary { background:#e9ecef; color:#374151; }

.help-strip { display:flex; gap:14px; align-items:center; background:#eff6ff; border-left:4px solid #0ea5e9;
    padding:12px 16px; border-radius:6px; margin-bottom:14px; font-size:13px; color:#075985; }

.toast { position:fixed; bottom:30px; right:30px; z-index:9999; background:#0f172a; color:#fff;
    padding:14px 22px; border-radius:10px; font-weight:600; box-shadow:0 4px 20px rgba(0,0,0,.3);
    max-width:460px; display:none; }
.toast.success { background:linear-gradient(135deg,#16a34a,#22c55e); }
.toast.error   { background:linear-gradient(135deg,#dc2626,#b91c1c); }
.toast.info    { background:linear-gradient(135deg,#0ea5e9,#0369a1); }
</style>

<div class="main-content">

<div class="page-head">
    <h1>⚡ Log 33kV Interruption — Quick Entry</h1>
    <div class="sub">One form for the full event. Same-day entries save immediately; past dates need an approval reason.</div>
</div>

<div class="card">

    <div class="help-strip">
        <strong>📋 Tip:</strong>
        Fill in every column matching your Excel row, then click <strong>Save Interruption</strong>.
        Period Out / Period In / Duration are computed automatically.
    </div>

    <form id="simpleIntForm" onsubmit="return submitSimpleInterruption(event)">

        <div class="form-grid">

            <!-- Affected Feeder -->
            <div class="fg col-6">
                <label>Affected Feeder <span class="req">*</span></label>
                <select name="fdr33kv_code" id="fdr33kv_code" required>
                    <option value="">— Select 33kV Feeder —</option>
                    <?php foreach ($feeders_33kv as $f): ?>
                        <option value="<?= htmlspecialchars($f['fdr33kv_code']) ?>">
                            33kV <?= htmlspecialchars($f['fdr33kv_name']) ?>
                            <?= $f['station_name'] ? '— ' . htmlspecialchars($f['station_name']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Interruption Type (Unplanned / Planned / Emergency) -->
            <div class="fg col-3">
                <label>Interruption Type <span class="req">*</span></label>
                <select name="interruption_type" id="interruption_type" required>
                    <option value="">— Select Type —</option>
                    <?php foreach ($interruptionTypes as $t): ?>
                        <option value="<?= htmlspecialchars($t) ?>"><?= htmlspecialchars($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Interruption Code (cascades from type) -->
            <div class="fg col-3">
                <label>Interruption Code <span class="req">*</span></label>
                <select name="interruption_code" id="interruption_code" required disabled>
                    <option value="">— Pick a type first —</option>
                </select>
                <div class="hint">e.g. O/C, E/F, O/C and E/F</div>
            </div>

            <!-- Auto-populated read-only details -->
            <div class="fg col-4">
                <label>Code Description</label>
                <div class="computed" id="code_description">—</div>
            </div>
            <div class="fg col-4">
                <label>Interruption Group</label>
                <div class="computed" id="code_group">—</div>
            </div>
            <div class="fg col-4">
                <label>Body Responsible</label>
                <div class="computed" id="code_body">—</div>
            </div>

            <!-- Load Loss -->
            <div class="fg col-3">
                <label>Load Loss (MW) <span class="req">*</span></label>
                <input type="number" name="load_loss" id="load_loss" min="0" step="0.01" value="0.00" required>
            </div>

            <!-- Date Out / Time Out -->
            <div class="fg col-3">
                <label>Date Out <span class="req">*</span></label>
                <input type="date" name="date_out" id="date_out" required value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>" onchange="recomputeDerived()">
            </div>
            <div class="fg col-3">
                <label>Time Out <span class="req">*</span></label>
                <input type="time" name="time_out" id="time_out" required value="<?= date('H:i') ?>" onchange="recomputeDerived()">
            </div>

            <!-- Date In / Time In -->
            <div class="fg col-3">
                <label>Date In <span class="req">*</span></label>
                <input type="date" name="date_in" id="date_in" required value="<?= date('Y-m-d') ?>" onchange="recomputeDerived()">
            </div>
            <div class="fg col-3">
                <label>Time In <span class="req">*</span></label>
                <input type="time" name="time_in" id="time_in" required value="<?= date('H:i') ?>" onchange="recomputeDerived()">
            </div>

            <!-- Period Out / Period In / Duration (computed) -->
            <div class="fg col-4">
                <label>Period Out</label>
                <div class="computed" id="period_out">—</div>
            </div>
            <div class="fg col-4">
                <label>Period In</label>
                <div class="computed" id="period_in">—</div>
            </div>
            <div class="fg col-4">
                <label>Duration</label>
                <div class="computed" id="duration_disp">— (0.00 hrs)</div>
            </div>

            <!-- Reason for Interruption -->
            <div class="fg col-12">
                <label>Reason for Interruption <span class="req">*</span></label>
                <textarea name="reason_for_interruption" id="reason_for_interruption" rows="2" required placeholder="e.g. Transient fault, line clearance, animal contact…"></textarea>
            </div>

            <!-- Resolution -->
            <div class="fg col-6">
                <label>Resolution</label>
                <input type="text" name="resolution" id="resolution" placeholder="e.g. Restored ok">
            </div>

            <!-- Weather Condition -->
            <div class="fg col-6">
                <label>Weather Condition</label>
                <select name="weather_condition" id="weather_condition">
                    <option value="">— Select Weather —</option>
                    <option value="Fine Weather">Fine Weather</option>
                    <option value="Cloudy">Cloudy</option>
                    <option value="Windy">Windy</option>
                    <option value="Rainy">Rainy</option>
                    <option value="Windy and Rainy Weather">Windy and Rainy Weather</option>
                    <option value="Stormy">Stormy</option>
                    <option value="Foggy">Foggy</option>
                </select>
            </div>

            <!-- Reason for Delay (only if restoration took long) -->
            <div class="fg col-6">
                <label>Reason for Delay <small style="font-weight:400;color:#64748b;">(if any)</small></label>
                <select name="reason_for_delay" id="reason_for_delay">
                    <option value="">— No Delay —</option>
                    <option value="DSO communicated late">DSO communicated late</option>
                    <option value="Lack of vehicle or fuel for patrol">Lack of vehicle or fuel for patrol</option>
                    <option value="Lack of staff during restoration work">Lack of staff during restoration work</option>
                    <option value="Lack of material">Lack of material</option>
                    <option value="Delay to get security">Delay to get security</option>
                    <option value="Line in marshy Area">Line in marshy Area</option>
                    <option value="Technical staff negligence">Technical staff negligence</option>
                    <option value="others">Other reason (specify below)</option>
                </select>
            </div>

            <!-- Other reason free-text (only if delay=others) -->
            <div class="fg col-12" id="otherReasonWrap" style="display:none;">
                <label>Other Reason for Delay <span class="req">*</span></label>
                <input type="text" name="other_reasons" id="other_reasons" placeholder="Describe the delay reason…">
            </div>

            <!-- Approval Note (shown only for past-date entries) -->
            <div class="col-12">
                <div class="approval-box" id="approvalBox">
                    <h4>⚠️ Past-Date Entry — Approval Reason Required</h4>
                    <p>This interruption's Date Out is before today. Provide a reason — the record will be saved as <strong>AWAITING_APPROVAL</strong> and routed to UL3 / UL4 for review.</p>
                    <textarea name="approval_note" id="approval_note" rows="3" style="width:100%;padding:9px 12px;border:1.5px solid #f59e0b;border-radius:7px;font-size:14px;font-family:inherit;" placeholder="Reason for logging this interruption late…"></textarea>
                </div>
            </div>

        </div>

        <div class="actions" style="margin-top:18px;">
            <button type="button" class="btn btn-secondary" onclick="window.location.href='?page=interruptions'">
                <i class="fas fa-times"></i> Cancel
            </button>
            <button type="submit" class="btn btn-primary" id="saveBtn">
                <i class="fas fa-save"></i> Save Interruption
            </button>
        </div>

    </form>
</div>

</div><!-- /main-content -->

<div id="toast" class="toast"></div>

<script>
const BASE = <?= json_encode($bp) ?>;
const TODAY = <?= json_encode(date('Y-m-d')) ?>;
const CODES_BY_TYPE = <?= json_encode($codesByType) ?>;

// ── Cascade: Type → Code → auto-populated Description/Group/Body ────────────
document.addEventListener('DOMContentLoaded', function () {
    const typeSel = document.getElementById('interruption_type');
    const codeSel = document.getElementById('interruption_code');
    const desc    = document.getElementById('code_description');
    const grp     = document.getElementById('code_group');
    const body    = document.getElementById('code_body');

    typeSel.addEventListener('change', function () {
        // Repopulate Interruption Code dropdown from CODES_BY_TYPE[type]
        codeSel.innerHTML = '<option value="">— Select Code —</option>';
        desc.textContent = grp.textContent = body.textContent = '—';
        const list = CODES_BY_TYPE[this.value] || [];
        if (!list.length) { codeSel.disabled = true; return; }
        list.forEach(function (c) {
            const opt = document.createElement('option');
            opt.value = c.interruption_code;
            opt.textContent = c.interruption_code + ' — ' + c.interruption_description;
            opt.dataset.description = c.interruption_description;
            opt.dataset.group       = c.interruption_group;
            opt.dataset.body        = c.body_responsible;
            codeSel.appendChild(opt);
        });
        codeSel.disabled = false;
    });

    codeSel.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        desc.textContent = opt && opt.dataset.description ? opt.dataset.description : '—';
        grp.textContent  = opt && opt.dataset.group       ? opt.dataset.group       : '—';
        body.textContent = opt && opt.dataset.body        ? opt.dataset.body        : '—';
    });

    // Reason for Delay = "others" → show free-text field
    const delaySel = document.getElementById('reason_for_delay');
    const otherWrap= document.getElementById('otherReasonWrap');
    const otherInp = document.getElementById('other_reasons');
    delaySel.addEventListener('change', function () {
        if (this.value === 'others') { otherWrap.style.display = ''; otherInp.required = true; }
        else                          { otherWrap.style.display = 'none'; otherInp.required = false; otherInp.value = ''; }
    });
});

function showToast(msg, type) {
    const t = document.getElementById('toast');
    t.innerHTML = msg;
    t.className = 'toast ' + (type || '');
    t.style.display = 'block';
    setTimeout(() => { t.style.display = 'none'; }, 6000);
}

function pad(n) { return String(n).padStart(2, '0'); }

function _formatPeriod(date, time) {
    if (!date || !time) return '—';
    const d = new Date(date + 'T' + time + ':00');
    if (isNaN(d)) return '—';
    return pad(d.getDate()) + '/' +
           ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'][d.getMonth()] + '/' +
           d.getFullYear() + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes());
}

function recomputeDerived() {
    const dOut = document.getElementById('date_out').value;
    const tOut = document.getElementById('time_out').value;
    const dIn  = document.getElementById('date_in').value;
    const tIn  = document.getElementById('time_in').value;

    document.getElementById('period_out').textContent = _formatPeriod(dOut, tOut);
    document.getElementById('period_in').textContent  = _formatPeriod(dIn,  tIn);

    if (dOut && tOut && dIn && tIn) {
        const out = new Date(dOut + 'T' + tOut + ':00');
        const inn = new Date(dIn  + 'T' + tIn  + ':00');
        if (!isNaN(out) && !isNaN(inn) && inn > out) {
            const secs = (inn - out) / 1000;
            const h = Math.floor(secs / 3600);
            const m = Math.floor((secs % 3600) / 60);
            const decimal = (secs / 3600).toFixed(2);
            document.getElementById('duration_disp').textContent =
                pad(h) + ':' + pad(m) + ' (' + decimal + ' hrs)';
        } else {
            document.getElementById('duration_disp').textContent = '— (invalid range)';
        }
    }

    // Show approval box when date_out is before today
    const box = document.getElementById('approvalBox');
    if (dOut && dOut < TODAY) box.classList.add('show');
    else                       box.classList.remove('show');
}

async function submitSimpleInterruption(e) {
    e.preventDefault();
    recomputeDerived();

    const form = document.getElementById('simpleIntForm');
    const fd   = new FormData(form);
    const btn  = document.getElementById('saveBtn');

    // Client-side guard: in < out
    const out = new Date(fd.get('date_out') + 'T' + fd.get('time_out') + ':00');
    const inn = new Date(fd.get('date_in')  + 'T' + fd.get('time_in')  + ':00');
    if (inn <= out) {
        showToast('⚠️ Date/Time In must be after Date/Time Out.', 'error');
        return false;
    }

    // Same date check vs today: date_out must be ≤ today (the date input
    // already enforces this with max=today). Past dates require approval note.
    if (fd.get('date_out') < TODAY) {
        const note = (fd.get('approval_note') || '').trim();
        if (!note) {
            showToast('⚠️ Past-date entry — fill in the approval reason below.', 'error');
            document.getElementById('approvalBox').classList.add('show');
            document.getElementById('approval_note').focus();
            return false;
        }
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

    try {
        const resp = await fetch(BASE + '/ajax/interruption_simple_log.php', {
            method: 'POST', body: fd,
        });
        const data = await resp.json();

        if (data.success) {
            showToast('✓ ' + (data.message || 'Saved'), 'success');
            setTimeout(() => {
                window.location.href = BASE + '/index.php?page=interruptions&action=view&ticket=' + encodeURIComponent(data.ticket_number);
            }, 1500);
        } else {
            showToast('✗ ' + (data.message || 'Save failed'), 'error');
            if (data.needs_approval_note) {
                document.getElementById('approvalBox').classList.add('show');
                document.getElementById('approval_note').focus();
            }
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Save Interruption';
        }
    } catch (err) {
        showToast('✗ Network error: ' + err.message, 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Save Interruption';
    }
    return false;
}

document.addEventListener('DOMContentLoaded', recomputeDerived);
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
