<?php
/**
 * Stage 2: Complete 11kV Interruption
 * Path: /app/views/interruptions_11kv/complete_form.php
 */
require __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/sidebar.php';
?>
<style>
.main-content { margin-left:260px; padding:22px; padding-top:90px; min-height:calc(100vh - 64px); background:#f4f6fa; }
.interruption-card { background:#fff; border-radius:14px; padding:28px; box-shadow:0 10px 30px rgba(0,0,0,.08); max-width:900px; margin:0 auto; }
.page-title { font-size:24px; font-weight:700; color:#0f172a; margin-bottom:6px; }
.page-subtitle { color:#6b7280; font-size:14px; margin-bottom:24px; }
.stage-badge { display:inline-flex; align-items:center; gap:6px; background:linear-gradient(135deg,#16a34a,#15803d); color:#fff; padding:6px 16px; border-radius:20px; font-size:12px; font-weight:700; margin-bottom:20px; }
.summary-box { background:linear-gradient(135deg,#f0fdf4,#dcfce7); border:1px solid #bbf7d0; border-radius:10px; padding:16px 20px; margin-bottom:20px; }
.summary-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; }
.summary-item .label { font-size:11px; font-weight:700; color:#15803d; text-transform:uppercase; }
.summary-item .value { font-size:15px; font-weight:700; color:#0f172a; margin-top:2px; }
.serial-highlight { font-size:20px; color:#15803d; font-family:monospace; font-weight:800; letter-spacing:1px; }
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
.form-group { margin-bottom:16px; }
.form-group label { display:block; font-weight:600; margin-bottom:6px; font-size:13px; color:#374151; }
.required { color:#dc2626; }
.form-group input, .form-group select, .form-group textarea { width:100%; padding:10px 14px; border-radius:8px; border:1.5px solid #d1d5db; font-size:14px; transition:all .2s; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline:none; border-color:#15803d; box-shadow:0 0 0 3px rgba(30,64,175,.1); }
.form-group textarea { resize:vertical; min-height:80px; font-family:inherit; }
#delayReasonGroup { display:none; }
.btn-group { display:flex; gap:12px; margin-top:24px; }
.btn-primary { background:linear-gradient(135deg,#16a34a,#15803d); color:#fff; border:none; padding:12px 28px; border-radius:8px; cursor:pointer; font-weight:600; font-size:14px; }
.btn-primary:disabled { opacity:.6; cursor:not-allowed; }
.btn-secondary { background:#e5e7eb; color:#374151; border:none; padding:12px 28px; border-radius:8px; cursor:pointer; font-weight:600; font-size:14px; text-decoration:none; display:inline-block; }
.alert { padding:14px 18px; border-radius:8px; margin-bottom:16px; display:none; }
.alert.success { background:#dcfce7; color:#166534; border:1px solid #bbf7d0; }
.alert.error   { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; }
</style>

<div class="main-content">
  <div class="interruption-card">
    <div class="stage-badge">✅ STAGE 2 OF 2 — Complete Interruption</div>
    <h1 class="page-title">Complete 11kV Interruption</h1>
    <p class="page-subtitle">Power has been restored. Fill the restoration details to close this interruption record.</p>

    <!-- Summary of Stage 1 data -->
    <div class="summary-box">
      <div style="margin-bottom:10px;">
        <span class="summary-item"><span class="label">Serial Number</span><span class="serial-highlight"><?= htmlspecialchars($interruption['serial_number']) ?></span></span>
      </div>
      <div class="summary-grid">
        <div class="summary-item">
          <div class="label">11kV Feeder</div>
          <div class="value"><?= htmlspecialchars($interruption['fdr11kv_name']) ?></div>
        </div>
        <div class="summary-item">
          <div class="label">Type / Code</div>
          <div class="value"><?= htmlspecialchars($interruption['interruption_type']) ?> / <?= htmlspecialchars($interruption['interruption_code']) ?></div>
        </div>
        <div class="summary-item">
          <div class="label">Started</div>
          <div class="value"><?= date('d M Y H:i', strtotime($interruption['datetime_out'])) ?></div>
        </div>
        <div class="summary-item">
          <div class="label">Started By</div>
          <div class="value"><?= htmlspecialchars($interruption['starter_name'] ?? $interruption['started_by']) ?></div>
        </div>
        <div class="summary-item">
          <div class="label">Weather</div>
          <div class="value"><?= htmlspecialchars($interruption['weather_condition'] ?: '—') ?></div>
        </div>
        <div class="summary-item">
          <div class="label">Approval Required</div>
          <div class="value"><?= $interruption['requires_approval'] === 'YES' ? '⚠️ Yes' : 'No' ?></div>
        </div>
      </div>
    </div>

    <div id="alertBox" class="alert"></div>

    <form id="completeForm">
      <input type="hidden" name="interruption_id" value="<?= $interruption['id'] ?>">

      <div class="form-row">
        <div class="form-group">
          <label>Date/Time In (Power Restored) <span class="required">*</span></label>
          <input type="datetime-local" name="datetime_in" id="datetimeIn" required
                 min="<?= date('Y-m-d\TH:i', strtotime($interruption['datetime_out'])) ?>">
        </div>
        <div class="form-group">
          <label>Load Loss (MW) <span class="required">*</span></label>
          <input type="number" step="0.01" min="0" name="load_loss" placeholder="0.00" required>
        </div>
      </div>

      <div class="form-group">
        <label>Reason for Interruption <span class="required">*</span></label>
        <textarea name="reason_for_interruption" required placeholder="Describe the cause of the interruption..."></textarea>
      </div>

      <div class="form-group">
        <label>Resolution / Action Taken</label>
        <textarea name="resolution" placeholder="Describe how the issue was resolved..."></textarea>
      </div>

      <div class="form-group">
        <label>Reason for Delay (if applicable)</label>
        <select name="reason_for_delay" id="reasonForDelay">
          <option value="">-- No Delay --</option>
          <option value="DSO communicated late">DSO communicated late</option>
          <option value="Lack of vehicle or fuel for patrol">Lack of vehicle or fuel for patrol</option>
          <option value="Lack of staff during restoration work">Lack of staff during restoration work</option>
          <option value="Lack of material">Lack of material</option>
          <option value="Delay to get security">Delay to get security</option>
          <option value="Line in marshy Area">Line in marshy Area</option>
          <option value="Technical staff negligence">Technical staff negligence</option>
          <option value="others">Others (Specify)</option>
        </select>
      </div>

      <div class="form-group" id="delayReasonGroup">
        <label>Specify Other Reason</label>
        <input type="text" name="other_reasons" placeholder="Specify other reason for delay">
      </div>

      <div class="btn-group">
        <a href="index.php?page=interruptions_11kv" class="btn-secondary">← Back to List</a>
        <button type="submit" class="btn-primary" id="submitBtn">✅ Complete Interruption</button>
      </div>
    </form>
  </div>
</div>

<script>
document.getElementById('reasonForDelay').addEventListener('change', function() {
    document.getElementById('delayReasonGroup').style.display = this.value === 'others' ? 'block' : 'none';
});

document.getElementById('datetimeIn').addEventListener('change', function() {
    const out = new Date('<?= date('Y-m-d\TH:i', strtotime($interruption['datetime_out'])) ?>');
    if (new Date(this.value) <= out) {
        alert('⚠️ Restoration time must be after interruption start time.');
        this.value = '';
    }
});

document.getElementById('completeForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('submitBtn');
    btn.disabled = true; btn.textContent = 'Completing...';
    fetch('ajax/interruption_11kv_complete.php', { method:'POST', body: new FormData(this) })
        .then(r => r.json())
        .then(res => {
            const box = document.getElementById('alertBox');
            box.style.display = 'block';
            if (res.success) {
                box.className = 'alert success';
                box.textContent = '✅ ' + res.message;
                setTimeout(() => window.location.href = 'index.php?page=interruptions', 2000);
            } else {
                box.className = 'alert error'; box.textContent = '❌ ' + res.message;
                btn.disabled = false; btn.textContent = '✅ Complete Interruption';
            }
        })
        .catch(err => {
            const box = document.getElementById('alertBox');
            box.className = 'alert error'; box.style.display = 'block';
            box.textContent = '❌ Network error: ' + err.message;
            btn.disabled = false; btn.textContent = '✅ Complete Interruption';
        });
});
</script>
<?php require __DIR__ . '/../layout/footer.php'; ?>
