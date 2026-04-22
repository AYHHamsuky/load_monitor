<?php
/**
 * Edit Interruption Form
 * Path: /app/views/interruptions/edit_form.php
 */
require __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/sidebar.php';

// Check if same day
$isSameDay = (date('Y-m-d', strtotime($interruption['datetime_out'])) === date('Y-m-d'));

if (!$isSameDay) {
    die("⚠️ Editing is only allowed on the same day. Use correction request for past-date changes.");
}
?>

<style>
.main-content {
    margin-left: 260px;
    padding: 22px;
    padding-top: 90px;
    min-height: calc(100vh - 64px);
    background: #f4f6fa;
}

.edit-card {
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
    background: linear-gradient(135deg, #0b3a82 0%, #1e40af 100%);
    color: #fff;
    border: none;
    padding: 12px 28px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
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
    text-decoration: none;
    display: inline-block;
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
    background: #eff6ff;
    border-left: 4px solid #3b82f6;
    padding: 14px 18px;
    border-radius: 6px;
    margin-bottom: 24px;
    font-size: 13px;
    color: #1e40af;
}

#delayReasonGroup {
    display: <?= $interruption['reason_for_delay'] === 'others' ? 'block' : 'none' ?>;
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
    <div class="edit-card">
        <h1 class="page-title">✏️ Edit Interruption #<?= $interruption['id'] ?></h1>
        <p class="page-subtitle">Modify interruption details for <?= htmlspecialchars($interruption['fdr33kv_name']) ?></p>
        
        <div class="info-box">
            ℹ️ <strong>Same-Day Edit:</strong> You can edit this record because it was logged today. Past-date changes require correction approval.
        </div>
        
        <div id="alertBox" class="alert"></div>
        
        <form id="editInterruptionForm">
            <input type="hidden" name="interruption_id" value="<?= $interruption['id'] ?>">
            <input type="hidden" name="fdr33kv_code" value="<?= $interruption['fdr33kv_code'] ?>">
            
            <div class="form-row">
                <div class="form-group">
                    <label>33kV Feeder</label>
                    <input type="text" value="<?= htmlspecialchars($interruption['fdr33kv_name']) ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label>Interruption Type <span class="required">*</span></label>
                    <select name="interruption_type" required>
                        <option value="PLANNED" <?= $interruption['interruption_type'] === 'PLANNED' ? 'selected' : '' ?>>Planned</option>
                        <option value="UNPLANNED" <?= $interruption['interruption_type'] === 'UNPLANNED' ? 'selected' : '' ?>>Unplanned</option>
                        <option value="EMERGENCY" <?= $interruption['interruption_type'] === 'EMERGENCY' ? 'selected' : '' ?>>Emergency</option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Date/Time Out <span class="required">*</span></label>
                    <input type="datetime-local" name="datetime_out" id="datetimeOut" 
                           value="<?= date('Y-m-d\TH:i', strtotime($interruption['datetime_out'])) ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Date/Time In (Restored) <span class="required">*</span></label>
                    <input type="datetime-local" name="datetime_in" id="datetimeIn" 
                           value="<?= date('Y-m-d\TH:i', strtotime($interruption['datetime_in'])) ?>" required>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Load Loss (MW) <span class="required">*</span></label>
                    <input type="number" step="0.01" min="0" name="load_loss" 
                           value="<?= $interruption['load_loss'] ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Weather Condition</label>
                    <select name="weather_condition">
                        <option value="">-- Select Weather --</option>
                        <option value="Clear" <?= $interruption['weather_condition'] === 'Clear' ? 'selected' : '' ?>>Clear</option>
                        <option value="Rainy" <?= $interruption['weather_condition'] === 'Rainy' ? 'selected' : '' ?>>Rainy</option>
                        <option value="Stormy" <?= $interruption['weather_condition'] === 'Stormy' ? 'selected' : '' ?>>Stormy</option>
                        <option value="Windy" <?= $interruption['weather_condition'] === 'Windy' ? 'selected' : '' ?>>Windy</option>
                        <option value="Foggy" <?= $interruption['weather_condition'] === 'Foggy' ? 'selected' : '' ?>>Foggy</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group full-width">
                <label>Reason for Interruption <span class="required">*</span></label>
                <textarea name="reason_for_interruption" required><?= htmlspecialchars($interruption['reason_for_interruption']) ?></textarea>
            </div>
            
            <div class="form-group full-width">
                <label>Resolution/Action Taken</label>
                <textarea name="resolution"><?= htmlspecialchars($interruption['resolution'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group full-width">
                <label>Reason for Delay (if applicable)</label>
                <select name="reason_for_delay" id="reasonForDelay">
                    <option value="">-- No Delay --</option>
                    <option value="DSO communicated late" <?= $interruption['reason_for_delay'] === 'DSO communicated late' ? 'selected' : '' ?>>DSO communicated late</option>
                    <option value="Lack of vehicle or fuel for patrol" <?= $interruption['reason_for_delay'] === 'Lack of vehicle or fuel for patrol' ? 'selected' : '' ?>>Lack of vehicle or fuel for patrol</option>
                    <option value="Lack of staff during restoration work" <?= $interruption['reason_for_delay'] === 'Lack of staff during restoration work' ? 'selected' : '' ?>>Lack of staff during restoration work</option>
                    <option value="Lack of material" <?= $interruption['reason_for_delay'] === 'Lack of material' ? 'selected' : '' ?>>Lack of material</option>
                    <option value="Delay to get security" <?= $interruption['reason_for_delay'] === 'Delay to get security' ? 'selected' : '' ?>>Delay to get security</option>
                    <option value="Line in marshy Area" <?= $interruption['reason_for_delay'] === 'Line in marshy Area' ? 'selected' : '' ?>>Line in marshy Area</option>
                    <option value="Technical staff negligence" <?= $interruption['reason_for_delay'] === 'Technical staff negligence' ? 'selected' : '' ?>>Technical staff negligence</option>
                    <option value="others" <?= $interruption['reason_for_delay'] === 'others' ? 'selected' : '' ?>>Others (Specify)</option>
                </select>
            </div>
            
            <div class="form-group full-width" id="delayReasonGroup">
                <label>Specify Other Reason</label>
                <input type="text" name="other_reasons" 
                       value="<?= htmlspecialchars($interruption['other_reasons'] ?? '') ?>" 
                       placeholder="Specify other reason for delay">
            </div>
            
            <div class="btn-group">
                <a href="index.php?page=interruptions&action=view&id=<?= $interruption['id'] ?>" class="btn-secondary">Cancel</a>
                <button type="submit" class="btn-primary" id="submitBtn">Update Interruption</button>
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
        this.value = '<?= date('Y-m-d\TH:i', strtotime($interruption['datetime_in'])) ?>';
    }
});

// Form submission
document.getElementById('editInterruptionForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const alertBox = document.getElementById('alertBox');
    
    submitBtn.disabled = true;
    submitBtn.textContent = 'Updating...';
    
    fetch('ajax/interruption_update.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alertBox.className = 'alert success';
            alertBox.textContent = '✅ ' + res.message;
            alertBox.style.display = 'block';
            
            setTimeout(() => {
                window.location.href = 'index.php?page=interruptions&action=view&id=<?= $interruption['id'] ?>';
            }, 1500);
        } else {
            alertBox.className = 'alert error';
            alertBox.textContent = '❌ ' + res.message;
            alertBox.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.textContent = 'Update Interruption';
        }
    })
    .catch(err => {
        alertBox.className = 'alert error';
        alertBox.textContent = '❌ Network error: ' + err.message;
        alertBox.style.display = 'block';
        submitBtn.disabled = false;
        submitBtn.textContent = 'Update Interruption';
    });
});
</script>
