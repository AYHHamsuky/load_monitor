<?php
/**
 * Enhanced 33kV Interruption Log Form
 * Features: Date restrictions, Interruption codes, Approval workflow
 */
require __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/sidebar.php';

$db = Database::connect();

// Get interruption codes
$codesStmt = $db->query("
    SELECT interruption_code, interruption_description, interruption_type, 
           interruption_group, approval_requirement
    FROM interruption_codes
    ORDER BY interruption_type, interruption_description
");
$interruptionCodes = $codesStmt->fetchAll(PDO::FETCH_ASSOC);

// Group by type
$codesByType = [];
foreach ($interruptionCodes as $code) {
    $codesByType[$code['interruption_type']][] = $code;
}
?>

<style>
.main-content {margin-left: 260px; padding: 22px; padding-top: 90px; min-height: calc(100vh - 64px); background: #f4f6fa;}
.interruption-card {background: #fff; border-radius: 14px; padding: 28px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); max-width: 1000px; margin: 0 auto;}
.page-title {font-size: 24px; font-weight: 700; color: #0f172a; margin-bottom: 8px;}
.page-subtitle {color: #6b7280; font-size: 14px; margin-bottom: 28px;}
.form-row {display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-bottom: 18px;}
.form-group {margin-bottom: 18px;}
.form-group.full-width {grid-column: 1 / -1;}
.form-group label {display: block; font-weight: 600; margin-bottom: 7px; font-size: 14px; color: #374151;}
.form-group label .required {color: #dc2626;}
.form-group input, .form-group select, .form-group textarea {width: 100%; padding: 10px 14px; border-radius: 8px; border: 1.5px solid #d1d5db; font-size: 14px; transition: all 0.2s;}
.form-group textarea {resize: vertical; min-height: 80px; font-family: inherit;}
.form-group input:focus, .form-group select:focus, .form-group textarea:focus {outline: none; border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220,38,38,0.1);}
.btn-group {display: flex; gap: 12px; margin-top: 28px;}
.btn-primary {background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%); color: #fff; border: none; padding: 12px 28px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px;}
.btn-secondary {background: #e5e7eb; color: #374151; border: none; padding: 12px 28px; border-radius: 8px; cursor: pointer; font-weight: 600; font-size: 14px;}
.alert {padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; display: none;}
.alert.success {background: #dcfce7; color: #166534; border: 1px solid #bbf7d0;}
.alert.error {background: #fee2e2; color: #991b1b; border: 1px solid #fecaca;}
.info-box {background: #fef3c7; border-left: 4px solid #f59e0b; padding: 14px 18px; border-radius: 6px; margin-bottom: 24px; font-size: 13px; color: #92400e;}
.approval-notice {background: #dbeafe; border-left: 4px solid #3b82f6; padding: 14px 18px; border-radius: 6px; margin-bottom: 24px; font-size: 13px; color: #1e40af; display: none;}
#delayReasonGroup {display: none;}
@media (max-width: 768px) {.main-content {margin-left: 0; padding: 12px; padding-top: 70px;} .form-row {grid-template-columns: 1fr;}}
</style>

<div class="main-content">
    <div class="interruption-card">
        <h1 class="page-title">⚡ Log 33kV Interruption</h1>
        <p class="page-subtitle">Record interruption details for 33kV Feeder</p>
        
        <div class="info-box">
            ⚠️ <strong>Important:</strong> Interruptions can only be logged for today or future dates. Past dates are not allowed.
        </div>
        
        <div id="approvalNotice" class="approval-notice">
            ℹ️ <strong>Approval Required:</strong> This interruption code requires UL3 approval before execution.
        </div>
        
        <div id="alertBox" class="alert"></div>
        
        <form id="interruptionForm">
            <div class="form-row">
                <div class="form-group">
                    <label>33kV Feeder <span class="required">*</span></label>
                    <select name="fdr33kv_code" required>
                        <option value="">-- Select Feeder --</option>
                        <?php foreach ($feeders_33kv as $fdr): ?>
                            <option value="<?= htmlspecialchars($fdr['fdr33kv_code']) ?>">
                                <?= htmlspecialchars($fdr['fdr33kv_name']) ?> - <?= htmlspecialchars($fdr['station_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Interruption Type <span class="required">*</span></label>
                    <select name="interruption_type" id="interruptionType" required>
                        <option value="">-- Select Type --</option>
                        <option value="PLANNED">Planned</option>
                        <option value="UNPLANNED">Unplanned</option>
                        <option value="EMERGENCY">Emergency</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Interruption Code <span class="required">*</span></label>
                    <select name="interruption_code" id="interruptionCode" required disabled>
                        <option value="">-- Select type first --</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" id="interruptionDescription" readonly placeholder="Select code to see description" style="background: #f9fafb;">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Date/Time Out <span class="required">*</span></label>
                    <input type="datetime-local" name="datetime_out" id="datetimeOut" required>
                </div>
                
                <div class="form-group">
                    <label>Date/Time In <span class="required">*</span></label>
                    <input type="datetime-local" name="datetime_in" id="datetimeIn" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Load Loss (MW) <span class="required">*</span></label>
                    <input type="number" step="0.01" min="0" name="load_loss" placeholder="0.00" required>
                </div>
                
                <div class="form-group">
                    <label>Weather Condition</label>
                    <select name="weather_condition">
                        <option value="">-- Select --</option>
                        <option value="Clear">Clear</option>
                        <option value="Rainy">Rainy</option>
                        <option value="Stormy">Stormy</option>
                        <option value="Windy">Windy</option>
                        <option value="Foggy">Foggy</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group full-width">
                <label>Reason for Interruption <span class="required">*</span></label>
                <textarea name="reason_for_interruption" required></textarea>
            </div>
            
            <div class="form-group full-width">
                <label>Resolution/Action Taken</label>
                <textarea name="resolution"></textarea>
            </div>
            
            <div class="form-group full-width">
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
            
            <div class="form-group full-width" id="delayReasonGroup">
                <label>Specify Other Reason</label>
                <input type="text" name="other_reasons">
            </div>
            
            <input type="hidden" name="requires_approval" id="requiresApproval" value="0">
            
            <div class="btn-group">
                <button type="button" class="btn-secondary" onclick="history.back()">Cancel</button>
                <button type="submit" class="btn-primary" id="submitBtn">Submit Interruption</button>
            </div>
        </form>
    </div>
</div>

<script>
const codes = <?= json_encode($codesByType) ?>;
const today = new Date(); today.setHours(0,0,0,0);
const todayStr = today.toISOString().slice(0,16);
document.getElementById('datetimeOut').min = todayStr;
document.getElementById('datetimeIn').min = todayStr;

document.getElementById('interruptionType').addEventListener('change', function() {
    const select = document.getElementById('interruptionCode');
    select.innerHTML = '<option value="">-- Select Code --</option>';
    select.disabled = !this.value;
    if (this.value && codes[this.value]) {
        codes[this.value].forEach(c => {
            const opt = new Option(`${c.interruption_code} - ${c.interruption_description}`, c.interruption_code);
            opt.dataset.desc = c.interruption_description;
            opt.dataset.approval = c.approval_requirement;
            select.add(opt);
        });
    }
});

document.getElementById('interruptionCode').addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    document.getElementById('interruptionDescription').value = opt.dataset.desc || '';
    const notice = document.getElementById('approvalNotice');
    if (opt.dataset.approval === 'YES') {
        notice.style.display = 'block';
        document.getElementById('requiresApproval').value = '1';
    } else {
        notice.style.display = 'none';
        document.getElementById('requiresApproval').value = '0';
    }
});

document.getElementById('reasonForDelay').addEventListener('change', function() {
    document.getElementById('delayReasonGroup').style.display = this.value === 'others' ? 'block' : 'none';
});

document.getElementById('datetimeOut').addEventListener('change', function() {
    if (new Date(this.value) < today) {
        alert('⚠️ Cannot log interruptions for past dates');
        this.value = '';
    }
});

document.getElementById('datetimeIn').addEventListener('change', function() {
    const out = new Date(document.getElementById('datetimeOut').value);
    const inVal = new Date(this.value);
    if (inVal < today) {
        alert('⚠️ Restoration time cannot be in the past');
        this.value = '';
    } else if (inVal <= out) {
        alert('⚠️ Restoration time must be after interruption time');
        this.value = '';
    }
});

document.getElementById('interruptionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('submitBtn');
    const alert = document.getElementById('alertBox');
    btn.disabled = true; btn.textContent = 'Submitting...';
    
    const fd = new FormData(this);
    fd.append('user_id', '<?= $user['payroll_id'] ?>');
    
    fetch('ajax/interruption_log_enhanced.php', {method: 'POST', body: fd})
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert.className = 'alert success';
            alert.textContent = '✅ ' + res.message;
            alert.style.display = 'block';
            setTimeout(() => window.location.href = 'index.php?page=interruptions', 2000);
        } else {
            alert.className = 'alert error';
            alert.textContent = '❌ ' + res.message;
            alert.style.display = 'block';
            btn.disabled = false; btn.textContent = 'Submit Interruption';
        }
    })
    .catch(err => {
        alert.className = 'alert error';
        alert.textContent = '❌ ' + err.message;
        alert.style.display = 'block';
        btn.disabled = false; btn.textContent = 'Submit Interruption';
    });
});
</script>
