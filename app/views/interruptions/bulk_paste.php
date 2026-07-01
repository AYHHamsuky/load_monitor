<?php
/**
 * Bulk-paste 33kV interruptions — matches the dispatch team's daily log
 * spreadsheet layout (17 columns: S/N, Region, Area Office, Feeder, Type,
 * Load Loss, Date Out, Time Out, Date In, Time In, Period Out, Period In,
 * Duration, Duration2, Reason, Resolution, Weather).
 *
 * $feeders_33kv       — from controller
 * $interruptionCodes  — from controller
 */
require __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/sidebar.php';

$bp = defined('BASE_PATH') ? BASE_PATH : '';
?>

<style>
.main-content { margin-left:252px; padding:24px; padding-top:90px; background:#f4f6fa; min-height:100vh; }
@media (max-width:768px) { .main-content { margin-left:0; padding:74px 12px 16px; } }

.page-head { background:linear-gradient(135deg,#0369a1 0%,#0284c7 100%); color:#fff; border-radius:12px;
    padding:22px 26px; margin-bottom:20px; box-shadow:0 4px 14px rgba(3,105,161,.25); }
.page-head h1 { font-size:22px; font-weight:700; margin:0 0 4px; display:flex; gap:10px; align-items:center; }
.page-head .sub { opacity:.85; font-size:13px; }

.card { background:#fff; border-radius:12px; padding:22px 24px; box-shadow:0 2px 10px rgba(0,0,0,.07); margin-bottom:20px; }

.help-strip { background:#eff6ff; border-left:4px solid #0ea5e9; padding:14px 16px; border-radius:6px;
    margin-bottom:16px; font-size:13px; color:#075985; line-height:1.6; }
.help-strip ol { margin:6px 0 4px 20px; }
.help-strip code { background:#fff; padding:1px 6px; border-radius:3px; font-family:monospace; font-size:12px; }

label { display:block; font-size:12px; font-weight:700; color:#475569; text-transform:uppercase;
    letter-spacing:.4px; margin-bottom:6px; }
textarea, input[type=text] { width:100%; padding:10px 12px; border:1.5px solid #cbd5e1; border-radius:7px;
    font-size:13px; font-family:inherit; }
textarea { font-family:'Courier New',monospace; font-size:12px; white-space:pre; resize:vertical; }
textarea:focus, input:focus { outline:none; border-color:#0369a1; box-shadow:0 0 0 3px rgba(3,105,161,.1); }

.btn { padding:11px 22px; border-radius:8px; font-weight:700; font-size:14px; cursor:pointer; border:0;
    display:inline-flex; gap:8px; align-items:center; }
.btn-primary { background:#0369a1; color:#fff; }
.btn-primary:hover { background:#0284c7; }
.btn-primary:disabled { background:#94a3b8; cursor:not-allowed; }
.btn-secondary { background:#e9ecef; color:#374151; }
.actions { display:flex; gap:12px; justify-content:space-between; align-items:center; padding-top:8px; flex-wrap:wrap; }

.preview-wrap { display:none; margin-top:18px; }
.preview-wrap h4 { margin:0 0 10px; color:#0f172a; font-size:14px; }
.preview-scroll { max-height:400px; overflow:auto; border:1px solid #e5e7eb; border-radius:6px; }
.preview-table { width:100%; border-collapse:collapse; font-size:12px; background:#fff; }
.preview-table th { background:#f1f5f9; color:#1e293b; padding:8px 10px; text-align:left; font-weight:700;
    border-bottom:2px solid #e5e7eb; font-size:11px; text-transform:uppercase; letter-spacing:.4px;
    position:sticky; top:0; z-index:2; }
.preview-table td { padding:8px 10px; border-bottom:1px solid #eef2f7; vertical-align:top; }
.preview-table tr.row-err td { background:#fef2f2; }
.preview-table tr.row-ok  td { background:#f0fdf4; }
.preview-table .status-ok  { color:#166534; font-weight:700; }
.preview-table .status-err { color:#991b1b; font-weight:700; }
.preview-table .small { color:#64748b; font-size:11px; }

.approval-box { display:none; background:#fef3c7; border:1px solid #f59e0b; border-radius:8px;
    padding:12px 14px; margin-top:10px; font-size:13px; color:#78350f; }
.approval-box.show { display:block; }
.approval-box textarea { border-color:#f59e0b; }

.toast { position:fixed; bottom:30px; right:30px; z-index:9999; background:#0f172a; color:#fff;
    padding:14px 22px; border-radius:10px; font-weight:600; box-shadow:0 4px 20px rgba(0,0,0,.3);
    max-width:460px; display:none; }
.toast.success { background:linear-gradient(135deg,#16a34a,#22c55e); }
.toast.error   { background:linear-gradient(135deg,#dc2626,#b91c1c); }
</style>

<div class="main-content">

<div class="page-head">
    <h1>📋 Bulk Paste 33kV Interruptions</h1>
    <div class="sub">Paste the full daily log sheet — the parser skips S/N/Region/Area/computed columns and saves each remaining row as a ticket.</div>
</div>

<div class="card">

    <div class="help-strip">
        <strong>📋 How to paste:</strong>
        <ol>
            <li>Copy your daily interruption log rows from Excel (including or excluding the header row — the parser detects it).</li>
            <li>Column order expected:
                <code>S/N | Region | Area Office | Feeder | Type | Load Loss | Date Out | Time Out | Date In | Time In | Period Out | Period In | Duration | Duration2 | Reason | Resolution | Weather</code>
            </li>
            <li>Rows with a blank <strong>Date In / Time In</strong> are saved as <strong>PENDING_COMPLETION</strong> (the officer can complete them later).</li>
            <li>Rows with <strong>Date Out</strong> before today require an approval reason (fill it once below).</li>
        </ol>
    </div>

    <div style="margin-bottom:14px;">
        <label>Paste your daily log rows here <span style="color:#dc2626;">*</span></label>
        <textarea id="pasteInput" rows="10" placeholder="Paste (Ctrl+V) from Excel — S/N | Region | Area Office | Feeder | Type | Load Loss | Date Out | Time Out | …"
                  oninput="parsePasteInput()" onpaste="handlePasteEvent(event)"></textarea>
    </div>

    <div class="approval-box" id="approvalBox">
        <strong>⚠️ Some rows are backdated — approval reason required.</strong>
        <p style="margin:6px 0;">The rows whose Date Out is before today will be routed to UL3 / UL4 for approval. Provide one reason to use for all of them:</p>
        <textarea id="approval_note" rows="2" placeholder="Reason for logging these interruptions late…"></textarea>
    </div>

    <div class="actions" style="margin-top:14px;">
        <button type="button" class="btn btn-secondary" onclick="window.location.href='?page=interruptions'">
            <i class="fas fa-arrow-left"></i> Back to Interruptions
        </button>
        <div style="display:flex; gap:10px;">
            <button type="button" class="btn btn-secondary" onclick="clearPaste()">
                <i class="fas fa-times"></i> Clear
            </button>
            <button type="button" class="btn btn-primary" id="saveBtn" onclick="submitBulk()" disabled>
                <i class="fas fa-save"></i> Save All Valid Rows
            </button>
        </div>
    </div>

    <div class="preview-wrap" id="previewWrap">
        <h4 id="previewStatus"></h4>
        <div class="preview-scroll">
            <table class="preview-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Feeder</th>
                        <th>Type</th>
                        <th>Load MW</th>
                        <th>Out</th>
                        <th>In</th>
                        <th>Reason / Resolution / Weather</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="previewBody"></tbody>
            </table>
        </div>
    </div>

</div>

</div><!-- /main-content -->

<div id="toast" class="toast"></div>

<script>
const BASE = <?= json_encode($bp) ?>;
const TODAY = <?= json_encode(date('Y-m-d')) ?>;

const FEEDERS = <?= json_encode(array_map(function($f) {
    return [
        'code' => $f['fdr33kv_code'],
        'name' => $f['fdr33kv_name'],
        'ts'   => $f['station_name'] ?? '',
    ];
}, $feeders_33kv)) ?>;

const CODES = <?= json_encode(array_map(function($c) {
    return $c['interruption_code'];
}, $interruptionCodes)) ?>;

// Build feeder index (case-insensitive, with 33KV-prefix stripping)
const FEEDER_INDEX = (function () {
    const idx = {};
    FEEDERS.forEach(f => {
        const nameLc = String(f.name).trim().toLowerCase().replace(/\s+/g, ' ');
        const noPfx  = nameLc.replace(/^33\s*k?v?\s+/i, '').trim();
        idx[String(f.code).trim().toLowerCase()] = f;
        idx[nameLc] = f;
        if (noPfx && noPfx !== nameLc) idx[noPfx] = f;
        idx['33kv ' + noPfx] = f;
    });
    return idx;
})();

// Build code index (case-insensitive lookup returning canonical form)
const CODE_INDEX = (function () {
    const idx = {};
    CODES.forEach(c => { idx[String(c).trim().toLowerCase()] = c; });
    return idx;
})();

function _lookupFeeder(token) {
    if (!token) return null;
    const t = String(token).trim().toLowerCase().replace(/\s+/g, ' ');
    if (FEEDER_INDEX[t]) return FEEDER_INDEX[t];
    const stripped = t.replace(/^33\s*k?v?\s+/i, '').trim();
    return FEEDER_INDEX[stripped] || null;
}

function _lookupCode(token) {
    if (!token) return null;
    return CODE_INDEX[String(token).trim().toLowerCase()] || null;
}

// Convert Excel-style date "1/Jul/2026" (or "01/07/2026") into ISO "2026-07-01"
function _parseDate(s) {
    if (!s) return '';
    s = String(s).trim();
    if (/^\d{4}-\d{2}-\d{2}$/.test(s)) return s;
    // dd/Mon/YYYY or dd/mm/YYYY or d/Mon/YYYY
    const m = s.match(/^(\d{1,2})[\/\-\s](\w+|\d{1,2})[\/\-\s](\d{2,4})$/);
    if (!m) return '';
    const day  = parseInt(m[1], 10);
    let mon    = m[2];
    let year   = parseInt(m[3], 10);
    if (year < 100) year += 2000;
    const monMap = {jan:1,feb:2,mar:3,apr:4,may:5,jun:6,jul:7,aug:8,sep:9,oct:10,nov:11,dec:12};
    let monNum = /^\d+$/.test(mon) ? parseInt(mon,10) : monMap[String(mon).slice(0,3).toLowerCase()];
    if (!monNum || monNum < 1 || monNum > 12) return '';
    return year + '-' + String(monNum).padStart(2,'0') + '-' + String(day).padStart(2,'0');
}

function _parseTime(s) {
    if (!s) return '';
    s = String(s).trim();
    if (/^\d{1,2}:\d{2}(:\d{2})?$/.test(s)) {
        const parts = s.split(':');
        return parts[0].padStart(2,'0') + ':' + parts[1] + (parts[2] ? ':' + parts[2] : ':00');
    }
    return '';
}

let parsedRows = [];

function handlePasteEvent(event) {
    event.preventDefault();
    const text = (event.clipboardData || window.clipboardData).getData('text');
    document.getElementById('pasteInput').value = text;
    parsePasteInput();
}
function clearPaste() {
    document.getElementById('pasteInput').value = '';
    document.getElementById('previewWrap').style.display = 'none';
    document.getElementById('saveBtn').disabled = true;
    document.getElementById('approvalBox').classList.remove('show');
    parsedRows = [];
}

function parsePasteInput() {
    const raw = document.getElementById('pasteInput').value;
    parsedRows = [];
    let anyBackdated = false;
    let skippedHeader = false;

    const lines = raw.split(/\r?\n/).map(l => l.replace(/\s+$/, '')).filter(l => l.trim().length);
    if (!lines.length) { clearPaste(); return; }

    lines.forEach((line, idx) => {
        const cells = line.split('\t').map(c => c.trim());
        if (cells.length < 8) return;   // not enough columns

        // Header detection — first row where col 1 = "S/N" (or numeric header)
        if (idx === 0) {
            const c0 = cells[0].toLowerCase();
            if (c0 === 's/n' || c0.includes('serial') ||
                (cells[3] && /feeder/i.test(cells[3])) ||
                (cells[4] && /interruption\s*type/i.test(cells[4]))) {
                skippedHeader = true; return;
            }
        }

        // Skip blank / total rows
        if (/^total\b/i.test(cells[0]) || cells.every(c => !c)) return;

        // Column mapping (0-indexed):
        //  0: S/N        1: Region       2: Area Office
        //  3: Feeder     4: Type         5: Load Loss
        //  6: Date Out   7: Time Out     8: Date In      9: Time In
        //  10-13: computed (ignored)
        //  14: Reason    15: Resolution  16: Weather
        const feederToken = cells[3] || '';
        const codeToken   = cells[4] || '';
        const loadLoss    = parseFloat(String(cells[5] || '0').replace(',', '.')) || 0;
        const dateOut     = _parseDate(cells[6]);
        const timeOut     = _parseTime(cells[7]);
        const dateIn      = _parseDate(cells[8]);
        const timeIn      = _parseTime(cells[9]);
        const reason      = cells[14] || '';
        const resolution  = cells[15] || '';
        const weather     = cells[16] || '';

        const feeder = _lookupFeeder(feederToken);
        const code   = _lookupCode(codeToken);

        const errs = [];
        if (!feeder)  errs.push('Unknown feeder "' + feederToken + '"');
        if (!code)    errs.push('Unknown code "' + codeToken + '"');
        if (!dateOut) errs.push('Invalid Date Out "' + cells[6] + '"');
        if (!timeOut) errs.push('Invalid Time Out "' + cells[7] + '"');
        if (loadLoss < 0) errs.push('Negative load loss');

        // Check backdate
        const isBackdate = dateOut && dateOut < TODAY;
        if (isBackdate) anyBackdated = true;

        parsedRows.push({
            line: idx + 1,
            feeder_token: feederToken,
            fdr33kv_code: feeder ? feeder.code : '',
            feeder_display: feeder ? (feeder.name + ' — ' + (feeder.ts || '')) : ('(unknown) ' + feederToken),
            interruption_code: code || codeToken,
            interruption_code_ok: !!code,
            load_loss: loadLoss,
            date_out: dateOut, time_out: timeOut,
            date_in:  dateIn,  time_in:  timeIn,
            reason, resolution, weather,
            is_backdate: isBackdate,
            is_pending:  !(dateIn && timeIn),
            errors: errs,
        });
    });

    document.getElementById('approvalBox').classList.toggle('show', anyBackdated);

    renderPreview();
}

function renderPreview() {
    const tbody = document.getElementById('previewBody');
    tbody.innerHTML = '';
    if (!parsedRows.length) {
        document.getElementById('previewWrap').style.display = 'none';
        document.getElementById('saveBtn').disabled = true;
        return;
    }
    let okCount = 0, errCount = 0;
    parsedRows.forEach((r, i) => {
        const tr = document.createElement('tr');
        tr.className = r.errors.length ? 'row-err' : 'row-ok';
        if (r.errors.length) errCount++; else okCount++;

        const statusHtml = r.errors.length
            ? '<span class="status-err">✗ ' + r.errors.map(_esc).join('; ') + '</span>'
            : ('<span class="status-ok">✓ ' + (r.is_pending ? 'Pending Completion' :
                                                r.is_backdate ? 'Awaiting Approval' : 'Ready') + '</span>');

        const meta = [
            r.reason && 'Reason: ' + r.reason,
            r.resolution && 'Res: ' + r.resolution,
            r.weather && 'Weather: ' + r.weather,
        ].filter(Boolean).join(' · ');

        tr.innerHTML =
            '<td>' + (i + 1) + '</td>' +
            '<td>' + _esc(r.feeder_display) + '</td>' +
            '<td>' + _esc(r.interruption_code) + '</td>' +
            '<td style="text-align:right;font-family:monospace;">' + Number(r.load_loss).toFixed(2) + '</td>' +
            '<td class="small">' + _esc(r.date_out || '?') + ' ' + _esc(r.time_out || '') + '</td>' +
            '<td class="small">' + _esc(r.date_in ? (r.date_in + ' ' + r.time_in) : '(ongoing)') + '</td>' +
            '<td class="small">' + _esc(meta || '—') + '</td>' +
            '<td>' + statusHtml + '</td>';
        tbody.appendChild(tr);
    });

    document.getElementById('previewStatus').innerHTML =
        '<span style="color:#16a34a;">✓ ' + okCount + ' ready</span>' +
        (errCount ? ' &nbsp;|&nbsp; <span style="color:#dc2626;">✗ ' + errCount + ' problems</span>' : '');
    document.getElementById('previewWrap').style.display = 'block';
    document.getElementById('saveBtn').disabled = (okCount === 0);
}

function _esc(s) {
    return String(s || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function showToast(msg, type) {
    const t = document.getElementById('toast');
    t.innerHTML = msg;
    t.className = 'toast ' + (type || '');
    t.style.display = 'block';
    setTimeout(() => { t.style.display = 'none'; }, 6000);
}

async function submitBulk() {
    const valid = parsedRows.filter(r => !r.errors.length);
    if (!valid.length) { showToast('⚠️ No valid rows to save.', 'error'); return; }

    // If any row is backdated, require approval note
    const anyBack = valid.some(r => r.is_backdate);
    const note    = (document.getElementById('approval_note').value || '').trim();
    if (anyBack && !note) {
        showToast('⚠️ Approval reason required for backdated rows.', 'error');
        document.getElementById('approval_note').focus();
        return;
    }

    const btn = document.getElementById('saveBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving ' + valid.length + ' rows…';

    const fd = new FormData();
    fd.append('rows',          JSON.stringify(valid));
    fd.append('approval_note', note);

    try {
        const resp = await fetch(BASE + '/ajax/interruption_bulk_save.php', { method: 'POST', body: fd });
        const data = await resp.json();
        if (data.success) {
            showToast('✓ ' + data.message, 'success');
            _renderServerResults(data.results);
            setTimeout(() => { window.location.href = BASE + '/index.php?page=interruptions&action=my-requests'; }, 3000);
        } else {
            showToast('✗ ' + (data.message || 'Save failed'), 'error');
            _renderServerResults(data.results || []);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Save All Valid Rows';
        }
    } catch (err) {
        showToast('✗ Network error: ' + err.message, 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Save All Valid Rows';
    }
}

function _renderServerResults(results) {
    if (!Array.isArray(results)) return;
    const tbody = document.getElementById('previewBody');
    const trs   = tbody.querySelectorAll('tr');
    // Only re-annotate rows that were submitted (valid ones). Map by "line index" in valid subset.
    const valid = parsedRows.filter(r => !r.errors.length);
    let vi = 0;
    parsedRows.forEach((r, idx) => {
        if (r.errors.length) return;
        const res = results[vi++];
        if (!res) return;
        const tr = trs[idx];
        if (!tr) return;
        const cell = tr.children[7];
        if (res.success) {
            cell.innerHTML = '<span class="status-ok">✓ Saved (' + _esc(res.status || '') + ')<br><small>' + _esc(res.ticket || '') + '</small></span>';
            tr.style.background = '#dcfce7';
        } else {
            cell.innerHTML = '<span class="status-err">✗ ' + _esc(res.message || 'Failed') + '</span>';
            tr.style.background = '#fee2e2';
        }
    });
}
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
