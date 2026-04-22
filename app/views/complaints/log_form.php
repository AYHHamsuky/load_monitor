<?php
/**
 * Log New Complaint Form
 * Path: /app/views/complaints/log_form.php
 */
?>

<style>
.main-content {
    margin-left: 260px;
    padding: 22px;
    padding-top: 90px;
    min-height: calc(100vh - 64px);
    background: #f4f6fa;
}

.complaint-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 28px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    max-width: 900px;
    margin: 0 auto;
}

.page-title {
    font-size: 24px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 8px;
}

.page-subtitle {
    color: #6b7280;
    font-size: 14px;
    margin-bottom: 28px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 18px;
    margin-bottom: 18px;
}

.form-group {
    margin-bottom: 18px;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 7px;
    font-size: 14px;
    color: #374151;
}

.form-group label .required {
    color: #dc2626;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px 14px;
    border-radius: 8px;
    border: 1.5px solid #d1d5db;
    font-size: 14px;
    transition: all 0.2s ease;
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
    font-family: inherit;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #0b3a82;
    box-shadow: 0 0 0 3px rgba(11,58,130,0.1);
}

.btn-group {
    display: flex;
    gap: 12px;
    margin-top: 28px;
}

.btn-primary {
    background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
    color: #fff;
    border: none;
    padding: 12px 28px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s ease;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(220,38,38,0.3);
}

.btn-secondary {
    background: #e5e7eb;
    color: #374151;
    border: none;
    padding: 12px 28px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s ease;
}

.btn-secondary:hover {
    background: #d1d5db;
}

.alert {
    padding: 14px 18px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: none;
}

.alert.success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #bbf7d0;
}

.alert.error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.info-box {
    background: #fef3c7;
    border-left: 4px solid #f59e0b;
    padding: 14px 18px;
    border-radius: 6px;
    margin-bottom: 24px;
    font-size: 13px;
    color: #92400e;
}

@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        padding: 12px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="main-content">
    <div class="complaint-card">
        <h1 class="page-title">📢 Log New Complaint</h1>
        <p class="page-subtitle">Record feeder complaints, faults, and customer issues</p>
        
        <div class="info-box">
            📌 <strong>Note:</strong> All complaints are tracked and assigned for resolution. Critical complaints will be prioritized.
        </div>
        
        <div id="alertBox" class="alert"></div>
        
        <form id="complaintForm">
            <div class="form-row">
                <div class="form-group">
                    <label>Feeder <span class="required">*</span></label>
                    <select name="feeder_code" required>
                        <option value="">-- Select Feeder --</option>
                        <?php foreach ($feeders as $f): ?>
                            <option value="<?= htmlspecialchars($f['fdr11kv_code']) ?>">
                                <?= htmlspecialchars($f['fdr11kv_name']) ?> (Band <?= htmlspecialchars($f['band']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Complaint Type <span class="required">*</span></label>
                    <select name="complaint_type" required>
                        <option value="">-- Select Type --</option>
                        <option value="NO_SUPPLY">No Supply</option>
                        <option value="LOW_VOLTAGE">Low Voltage</option>
                        <option value="INTERMITTENT">Intermittent Supply</option>
                        <option value="TRANSFORMER_FAULT">Transformer Fault</option>
                        <option value="LINE_FAULT">Line Fault</option>
                        <option value="OTHERS">Others</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Complaint Source <span class="required">*</span></label>
                    <select name="complaint_source" required>
                        <option value="">-- Select Source --</option>
                        <option value="CUSTOMER_CALL">Customer Call</option>
                        <option value="FIELD_PATROL">Field Patrol</option>
                        <option value="INTERNAL_MONITORING">Internal Monitoring</option>
                        <option value="DSO_REPORT">DSO Report</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Priority <span class="required">*</span></label>
                    <select name="priority" required>
                        <option value="">-- Select Priority --</option>
                        <option value="LOW">Low</option>
                        <option value="MEDIUM" selected>Medium</option>
                        <option value="HIGH">High</option>
                        <option value="CRITICAL">Critical</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Customer Name</label>
                    <input type="text" name="customer_name" placeholder="Optional">
                </div>
                
                <div class="form-group">
                    <label>Customer Phone</label>
                    <input type="tel" name="customer_phone" placeholder="e.g., 08012345678">
                </div>
            </div>
            
            <div class="form-group full-width">
                <label>Affected Area</label>
                <input type="text" name="affected_area" placeholder="e.g., Kaduna North, Sabon Gari">
            </div>
            
            <div class="form-group full-width">
                <label>Fault Location</label>
                <input type="text" name="fault_location" placeholder="Specific location or landmark">
            </div>
            
            <div class="form-group full-width">
                <label>Complaint Details <span class="required">*</span></label>
                <textarea name="complaint_details" placeholder="Describe the issue in detail..." required></textarea>
            </div>
            
            <div class="btn-group">
                <button type="button" class="btn-secondary" onclick="history.back()">Cancel</button>
                <button type="submit" class="btn-primary" id="submitBtn">Submit Complaint</button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('complaintForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const alertBox = document.getElementById('alertBox');
    
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';
    
    const formData = new FormData(this);
    formData.append('logged_by', '<?= $user['payroll_id'] ?>');
    
    fetch('ajax/complaint_log.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alertBox.className = 'alert success';
            alertBox.textContent = '✅ ' + res.message + ' (Ref: ' + res.complaint_ref + ')';
            alertBox.style.display = 'block';
            this.reset();
            
            setTimeout(() => {
                window.location.href = 'index.php?page=complaints';
            }, 2000);
        } else {
            alertBox.className = 'alert error';
            alertBox.textContent = '❌ ' + res.message;
            alertBox.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Complaint';
        }
    })
    .catch(err => {
        alertBox.className = 'alert error';
        alertBox.textContent = '❌ Network error: ' + err.message;
        alertBox.style.display = 'block';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Complaint';
    });
});
</script>
