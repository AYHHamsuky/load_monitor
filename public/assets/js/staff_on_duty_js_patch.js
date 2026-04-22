// ── Helpers ───────────────────────────────────────────────────────────────────
const SELECTED_DATE = '<?= $selected_date ?>';

// All AJAX calls go to the standalone endpoint — never through index.php
const AJAX_URL = (window.__BASE_PATH || '/load_monitor/public') + '/ajax/staff_duty.php';

// Reassign endpoint
const REASSIGN_URL = (window.__BASE_PATH || '/load_monitor/public') + '/ajax/staff_reassign.php';

const FEEDERSBY_TS = <?= json_encode($feeders_by_ts) ?>;

function updateParam(key, val) {
    const u = new URLSearchParams(window.location.search);
    u.set(key, val);
    return window.location.pathname + '?' + u.toString();
}

function toast(msg, type='s') {
    const w = document.getElementById('toastWrap');
    const t = document.createElement('div');
    t.className = 'toast ' + type;
    t.textContent = msg;
    w.appendChild(t);
    setTimeout(() => t.remove(), 4500);
}

function closeModal(id) {
    document.getElementById(id).classList.remove('open');
    document.body.style.overflow = 'auto';
}
function openModal(id) {
    document.getElementById(id).classList.add('open');
    document.body.style.overflow = 'hidden';
}

// Close on backdrop click
document.querySelectorAll('.modal').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) closeModal(m.id); });
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.modal.open').forEach(m => closeModal(m.id));
});

// ── Tab switching ─────────────────────────────────────────────────────────────
function switchTab(tab) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    event.currentTarget.classList.add('active');
    const u = new URLSearchParams(window.location.search);
    u.set('tab', tab);
    history.replaceState({}, '', window.location.pathname + '?' + u.toString());
}

// ── Activity Modal ────────────────────────────────────────────────────────────
function openActivityModal(pid, name, role, location) {
    document.getElementById('actModalName').textContent = name;
    document.getElementById('actModalMeta').textContent = pid + ' • ' + role + ' • ' + location;
    document.getElementById('actModalBody').innerHTML =
        '<div style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin" style="font-size:28px;color:#3b82f6;"></i></div>';
    openModal('actModal');

    const url = AJAX_URL + '?ajax=details&payroll_id=' + encodeURIComponent(pid) + '&date=' + SELECTED_DATE;

    fetch(url, { credentials: 'same-origin' })
        .then(r => r.json())
        .then(data => {
            if (data.error || !data.success) throw new Error(data.error || data.message || 'Unknown error');

            const dur = data.session_duration || '—';
            let html = `
                <div class="profile-grid">
                    <div class="prof-stat">
                        <div class="prof-stat-val">${data.login_time || '—'}</div>
                        <div class="prof-stat-lbl">Login</div>
                    </div>
                    <div class="prof-stat">
                        <div class="prof-stat-val">${data.logout_time || 'Active'}</div>
                        <div class="prof-stat-lbl">Logout</div>
                    </div>
                    <div class="prof-stat">
                        <div class="prof-stat-val">${dur}</div>
                        <div class="prof-stat-lbl">Duration</div>
                    </div>
                    <div class="prof-stat">
                        <div class="prof-stat-val">${data.total_activities || 0}</div>
                        <div class="prof-stat-lbl">Activities</div>
                    </div>
                </div>
                <h4 style="font-size:14px;font-weight:700;color:#0f172a;margin:0 0 10px;">
                    <i class="fas fa-clock"></i> Activity Timeline
                </h4>`;

            if (data.activities && data.activities.length > 0) {
                html += '<div class="timeline">';
                data.activities.forEach(a => {
                    html += `<div class="tl-item">
                        <div class="tl-dot"></div>
                        <div class="tl-time">${a.time}</div>
                        <div class="tl-desc">${a.description}
                            <span class="tl-type">${a.type}</span>
                        </div>
                    </div>`;
                });
                html += '</div>';
            } else {
                html += '<p style="text-align:center;color:#94a3b8;padding:20px;">No activities recorded for this date.</p>';
            }

            document.getElementById('actModalBody').innerHTML = html;
        })
        .catch(err => {
            document.getElementById('actModalBody').innerHTML =
                '<p style="color:#ef4444;text-align:center;padding:20px;">Error: ' + err.message + '</p>';
        });
}

// ── Performance Profile Modal ─────────────────────────────────────────────────
let perfCharts = [];

function openPerfModal(pid, name) {
    document.getElementById('perfModalName').textContent = name + ' — Performance Profile';
    document.getElementById('perfModalMeta').textContent = pid;
    document.getElementById('perfModalBody').innerHTML =
        '<div style="text-align:center;padding:40px;"><i class="fas fa-spinner fa-spin" style="font-size:28px;color:#6366f1;"></i></div>';
    openModal('perfModal');

    perfCharts.forEach(c => c.destroy());
    perfCharts = [];

    const url = AJAX_URL + '?ajax=perf&payroll_id=' + encodeURIComponent(pid) + '&date=' + SELECTED_DATE;

    fetch(url, { credentials: 'same-origin' })
        .then(r => r.json())
        .then(d => {
            if (!d.success) throw new Error(d.message || 'Failed to load');
            renderPerfModal(d, pid, name);
        })
        .catch(err => {
            document.getElementById('perfModalBody').innerHTML =
                '<p style="color:#ef4444;text-align:center;padding:20px;">Error: ' + err.message + '</p>';
        });
}

function renderPerfModal(d, pid, name) {
    const barColor    = s => s>=90?'#22c55e':s>=70?'#3b82f6':s>=50?'#f59e0b':'#ef4444';
    const ratingLabel = s => s>=90?'Excellent':s>=70?'Good':s>=50?'Fair':'Poor';
    const periods     = ['day','week','month','year'];
    const periodLabels= {day:'Today',week:'Week',month:'Month',year:'Year'};

    let html = `
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:20px;">
            ${periods.map(p => {
                const sc = d.scores[p];
                return `<div class="prof-stat" style="border:2px solid ${barColor(sc.aggregate)}22;">
                    <div class="prof-stat-val" style="color:${barColor(sc.aggregate)};font-size:28px;">${sc.aggregate}</div>
                    <div class="prof-stat-lbl">${periodLabels[p]}</div>
                    <div style="font-size:10px;color:${barColor(sc.aggregate)};font-weight:700;">${ratingLabel(sc.aggregate)}</div>
                </div>`;
            }).join('')}
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;">
            ${periods.map(p => {
                const sc = d.scores[p];
                return `<div style="background:#f8fafc;border-radius:10px;padding:14px;">
                    <div style="font-size:13px;font-weight:700;color:#0f172a;margin-bottom:10px;">${periodLabels[p]}</div>
                    <div class="perf-row">
                        <div class="perf-label">Corrections</div>
                        <div class="perf-bar-wrap"><div class="perf-bar" style="width:${sc.corr_score}%;background:${barColor(sc.corr_score)};"></div></div>
                        <div class="perf-val" style="color:${barColor(sc.corr_score)}">${sc.corr_score}</div>
                    </div>
                    <div class="perf-row">
                        <div class="perf-label">Explanations</div>
                        <div class="perf-bar-wrap"><div class="perf-bar" style="width:${sc.expl_score}%;background:${barColor(sc.expl_score)};"></div></div>
                        <div class="perf-val" style="color:${barColor(sc.expl_score)}">${sc.expl_score}</div>
                    </div>
                    <div class="perf-row">
                        <div class="perf-label" style="font-weight:700;">Aggregate</div>
                        <div class="perf-bar-wrap"><div class="perf-bar" style="width:${sc.aggregate}%;background:${barColor(sc.aggregate)};"></div></div>
                        <div class="perf-val" style="color:${barColor(sc.aggregate)};font-weight:800;">${sc.aggregate}</div>
                    </div>
                    <div style="font-size:11px;color:#94a3b8;margin-top:6px;">
                        Corrections: ${sc.corrections} | Explanations: ${sc.explanations}
                    </div>
                </div>`;
            }).join('')}
        </div>

        <div class="chart-row">
            <div style="background:#f8fafc;border-radius:10px;padding:14px;">
                <div style="font-size:13px;font-weight:700;color:#0f172a;margin-bottom:10px;">Aggregate Score Trend</div>
                <canvas id="perfTrendChart" height="180"></canvas>
            </div>
            <div style="background:#f8fafc;border-radius:10px;padding:14px;">
                <div style="font-size:13px;font-weight:700;color:#0f172a;margin-bottom:10px;">Corrections vs Explanations</div>
                <canvas id="perfCompChart" height="180"></canvas>
            </div>
        </div>`;

    document.getElementById('perfModalBody').innerHTML = html;

    // Trend chart
    const aggScores = periods.map(p => d.scores[p].aggregate);
    perfCharts.push(new Chart(
        document.getElementById('perfTrendChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: periods.map(p => periodLabels[p]),
            datasets: [{
                label: 'Aggregate Score',
                data: aggScores,
                borderColor: '#6366f1',
                backgroundColor: 'rgba(99,102,241,.1)',
                tension: 0.4, fill: true,
                pointBackgroundColor: aggScores.map(s => barColor(s)),
                pointRadius: 6
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: { y: { min:0, max:100, grid:{ color:'rgba(0,0,0,.05)' } } },
            plugins: { legend: { display: false } }
        }
    }));

    // Comparison chart
    perfCharts.push(new Chart(
        document.getElementById('perfCompChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: periods.map(p => periodLabels[p]),
            datasets: [
                { label: 'Corrections',  data: periods.map(p => d.scores[p].corrections),  backgroundColor: 'rgba(239,68,68,.7)' },
                { label: 'Explanations', data: periods.map(p => d.scores[p].explanations), backgroundColor: 'rgba(245,158,11,.7)' }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, grid:{ color:'rgba(0,0,0,.05)' } } },
            plugins: { legend: { position: 'top' } }
        }
    }));
}

// ── Reassign Modal ────────────────────────────────────────────────────────────
let currentStaff = null;
let currentReassignType = null;

function openReassignModal(staff) {
    currentStaff = staff;
    currentReassignType = null;

    document.getElementById('reassignSubtitle').textContent =
        staff.staff_name + ' (' + staff.payroll_id + ') — Currently: ' + staff.role + ' at ' + staff.assigned_location;

    document.getElementById('rPayrollId').value   = staff.payroll_id;
    document.getElementById('rCurrentRole').value = staff.role;
    document.getElementById('rNewRole').value     = staff.role;

    document.querySelectorAll('.rt-opt').forEach(o => o.classList.remove('sel'));
    document.querySelectorAll('.reassign-fields').forEach(f => f.style.display = 'none');
    document.getElementById('reassignTypeWarning').style.display = 'none';
    document.getElementById('reassignSubmitBtn').disabled = true;
    document.getElementById('reassignReason').value = '';
    document.getElementById('reassignForm').reset();

    const isUL1 = staff.role === 'UL1';
    document.getElementById('rt-iss-iss').style.display = isUL1 ? 'block' : 'none';
    document.getElementById('rt-ts-ts').style.display   = !isUL1 ? 'block' : 'none';
    document.getElementById('rt-cross').style.display   = 'block';

    openModal('reassignModal');
}

function setReassignType(type) {
    currentReassignType = type;
    document.getElementById('rReassignType').value = type;

    document.querySelectorAll('.rt-opt').forEach(o => o.classList.remove('sel'));
    document.getElementById('rt-' + type).classList.add('sel');
    document.querySelectorAll('.reassign-fields').forEach(f => f.style.display = 'none');

    if (type === 'iss-iss') {
        document.getElementById('field-iss-iss').style.display = 'block';
        document.getElementById('rNewRole').value = 'UL1';
        document.getElementById('reassignTypeWarning').style.display = 'none';
    } else if (type === 'ts-ts') {
        document.getElementById('field-ts-ts').style.display = 'block';
        document.getElementById('rNewRole').value = 'UL2';
        document.getElementById('reassignTypeWarning').style.display = 'none';
    } else if (type === 'cross') {
        document.getElementById('field-cross').style.display = 'block';
        document.getElementById('reassignTypeWarning').style.display = 'block';
        document.getElementById('cross-iss-group').style.display    = 'none';
        document.getElementById('cross-ts-group').style.display     = 'none';
        document.getElementById('cross-feeder-group').style.display = 'none';
        document.querySelectorAll('#cross-ul1, #cross-ul2').forEach(e => e.classList.remove('sel'));
    }
    document.getElementById('reassignSubmitBtn').disabled = false;
}

function setCrossRole(role) {
    document.getElementById('rNewRole').value = role;
    document.querySelectorAll('#cross-ul1, #cross-ul2').forEach(e => e.classList.remove('sel'));
    document.getElementById('cross-' + role.toLowerCase()).classList.add('sel');
    document.getElementById('cross-iss-group').style.display    = role === 'UL1' ? 'block' : 'none';
    document.getElementById('cross-ts-group').style.display     = role === 'UL2' ? 'block' : 'none';
    document.getElementById('cross-feeder-group').style.display = 'none';
}

function loadFeedersForTs() {
    _populateFeeders('new33kvCode', document.getElementById('newTsCode').value);
}
function loadFeedersForCrossTs() {
    const ts = document.getElementById('crossTsCode').value;
    _populateFeeders('cross33kvCode', ts);
    document.getElementById('cross-feeder-group').style.display = ts ? 'block' : 'none';
}
function _populateFeeders(selectId, ts) {
    const sel = document.getElementById(selectId);
    sel.innerHTML = '<option value="">— Select feeder —</option>';
    (FEEDERSBY_TS[ts] || []).forEach(f => {
        const o = document.createElement('option');
        o.value = f.fdr33kv_code;
        o.textContent = f.fdr33kv_name;
        sel.appendChild(o);
    });
}

document.getElementById('reassignForm').addEventListener('submit', function (e) {
    e.preventDefault();
    if (!currentReassignType) { toast('Please select a reassignment type.', 'e'); return; }

    const fd = new FormData(this);
    fd.set('reassign_type', currentReassignType);

    if (currentReassignType === 'cross') {
        const newRole = document.getElementById('rNewRole').value;
        if (newRole === 'UL1') {
            fd.set('new_iss_code', document.getElementById('crossIssCode').value);
        } else if (newRole === 'UL2') {
            fd.set('new_33kv_code', document.getElementById('cross33kvCode').value);
        }
    }

    const btn = document.getElementById('reassignSubmitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing…';

    fetch(REASSIGN_URL, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-exchange-alt"></i> Confirm Reassignment';
            if (res.success) {
                toast('✅ ' + res.message, 's');
                closeModal('reassignModal');
                setTimeout(() => location.reload(), 1500);
            } else {
                toast('❌ ' + res.message, 'e');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-exchange-alt"></i> Confirm Reassignment';
            toast('❌ Network error: ' + err.message, 'e');
        });
});
