<?php
/**
 * Log New Interruption Form
 * Path: /app/views/interruptions/log_form.php
 */
require __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/sidebar.php';
?>

<style>
.main-content {
    margin-left: 260px;
    padding: 22px;
    padding-top: 90px;
    min-height: calc(100vh - 64px);
    background: #f4f6fa;
}

.interruption-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 28px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    max-width: 1000px;
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
    min-height: 80px;
    font-family: inherit;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #dc2626;
    box-shadow: 0 0 0 3px rgba(220,38,38,0.1);
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

#delayReasonGroup {
    display: none;
}

@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        padding: 12px;
        padding-top: 70px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="main-content">
    <div class="interruption-card">
        <h1 class="page-title">⚡ Log 33kV Interruption</h1>
        <p class="page-subtitle">Record interruption details for 33kV Feeder</p>
        
        <div class="info-box">
            ⚠️ <strong>Note:</strong> Duration will be auto-calculated. Restoration time must be after interruption time.
        </div>
        
        <div id="alertBox" class="alert"></div>
        
        <form id="interruptionForm">
            <div class="form-row">
                <div class="form-group">
                    <label>33kV Feeder <span class="required">*</span></label>
                    <select name="fdr33kv_code" id="feederSelect" required>
                        <option value="">-- Select Feeder --</option>
                        <?php foreach ($feeders_33kv as $fdr): ?>
                            <option value="<?= htmlspecialchars($fdr['fdr33kv_code']) ?>" 
                                    <?= ($feeder['fdr33kv_code'] === $fdr['fdr33kv_code']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($fdr['fdr33kv_name']) ?> - <?= htmlspecialchars($fdr['station_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Interruption Type <span class="required">*</span></label>
                    <select name="interruption_type" required>
                        <option value="">-- Select Type --</option>
                        <option value="PLANNED">Planned</option>
                        <option value="UNPLANNED">Unplanned</option>
                        <option value="EMERGENCY">Emergency</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Date/Time Out <span class="required">*</span></label>
                    <input type="datetime-local" name="datetime_out" id="datetimeOut" required>
                </div>
                
                <div class="form-group">
                    <label>Date/Time In (Restored) <span class="required">*</span></label>
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
                        <option value="">-- Select Weather --</option>
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
                <textarea name="reason_for_interruption" placeholder="Describe the cause of interruption..." required></textarea>
            </div>
            
            <div class="form-group full-width">
                <label>Resolution/Action Taken</label>
                <textarea name="resolution" placeholder="Describe how the issue was resolved..."></textarea>
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
                <input type="text" name="other_reasons" placeholder="Specify other reason for delay">
            </div>
            
            <div class="btn-group">
                <button type="button" class="btn-secondary" onclick="history.back()">Cancel</button>
                <button type="submit" class="btn-primary" id="submitBtn">Submit Interruption</button>
            </div>
        </form>
    </div>
</div>

<script>
// Show "Other reasons" field when "others" is selected
document.getElementById('reasonForDelay')?.addEventListener('change', function() {
    const otherField = document.getElementById('delayReasonGroup');
    if (this.value === 'others') {
        otherField.style.display = 'block';
    } else {
        otherField.style.display = 'none';
    }
});

// Validate datetime_in is after datetime_out
document.getElementById('datetimeIn')?.addEventListener('change', function() {
    const dateOut = document.getElementById('datetimeOut').value;
    const dateIn = this.value;
    
    if (dateOut && dateIn && new Date(dateIn) <= new Date(dateOut)) {
        alert('⚠️ Restoration time must be after interruption time');
        this.value = '';
    }
});

// Form submission
document.getElementById('interruptionForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const alertBox = document.getElementById('alertBox');
    
    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';
    
    const formData = new FormData(this);
    formData.append('user_id', '<?= $user['payroll_id'] ?>');
    
    fetch('ajax/interruption_log.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alertBox.className = 'alert success';
            alertBox.textContent = '✅ ' + res.message;
            alertBox.style.display = 'block';
            this.reset();
            
            setTimeout(() => {
                window.location.href = 'index.php?page=interruptions';
            }, 2000);
        } else {
            alertBox.className = 'alert error';
            alertBox.textContent = '❌ ' + res.message;
            alertBox.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Interruption';
        }
    })
    .catch(err => {
        alertBox.className = 'alert error';
        alertBox.textContent = '❌ Network error: ' + err.message;
        alertBox.style.display = 'block';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Interruption';
    });
});
</script>
