<?php
/**
 * Log New 11kV Interruption Form (DB-driven Interruption Codes)
 * Path: /app/views/interruptions_11kv/log_form.php
 */
require __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/sidebar.php';

$db = Database::connect();

// Get interruption codes
$codesStmt = $db->query("
    SELECT interruption_code, interruption_description, interruption_type, 
           interruption_group, body_responsible, approval_requirement
    FROM interruption_codes
    ORDER BY interruption_type, interruption_description
");
$interruptionCodes = $codesStmt->fetchAll(PDO::FETCH_ASSOC);

// Group by type
$codesByType = [];
$descriptionsByType = [];
foreach ($interruptionCodes as $code) {
    $codesByType[$code['interruption_type']][] = $code;
    $descriptionsByType[$code['interruption_type']][] = $code['interruption_description'];
}

// Get distinct interruption types
$typesStmt = $db->query("
    SELECT DISTINCT interruption_type 
    FROM interruption_codes
    ORDER BY interruption_type
");
$interruptionTypes = $typesStmt->fetchAll(PDO::FETCH_COLUMN);
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
    color: #16a34a;
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
    border-color: #16a34a;
    box-shadow: 0 0 0 3px rgba(22,163,74,0.1);
}

.form-group input:read-only {
    background: #f9fafb;
    cursor: not-allowed;
}

.btn-group {
    display: flex;
    gap: 12px;
    margin-top: 28px;
}

.btn-primary {
    background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
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
    box-shadow: 0 4px 12px rgba(22,163,74,0.3);
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
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

.approval-notice {
    background: #dbeafe;
    border-left: 4px solid #3b82f6;
    padding: 14px 18px;
    border-radius: 6px;
    margin-bottom: 24px;
    font-size: 13px;
    color: #1e40af;
    display: none;
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
        <h1 class="page-title">⚡ Log 11kV Interruption</h1>
        <p class="page-subtitle">Record interruption details for 11kV Feeder</p>

        <div class="info-box">
            ⚠️ <strong>Important:</strong> Interruptions can only be logged for today or future dates.
        </div>

        <div id="approvalNotice" class="approval-notice">
            ℹ️ <strong>Approval Required:</strong> This interruption code requires approval before execution.
        </div>

        <div id="alertBox" class="alert"></div>

        <form id="interruptionForm">

            <div class="form-row">
                <div class="form-group">
                    <label>11kV Feeder <span class="required">*</span></label>
                    <select name="fdr11kv_code" id="feederSelect" required>
                        <option value="">-- Select Feeder --</option>
                        <?php foreach ($feeders_11kv as $fdr): ?>
                            <option value="<?= htmlspecialchars($fdr['fdr11kv_code']) ?>">
                                <?= htmlspecialchars($fdr['fdr11kv_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Interruption Type <span class="required">*</span></label>
                    <select name="interruption_type" id="interruptionType" required>
                        <option value="">-- Select Type --</option>
                        <?php foreach ($interruptionTypes as $type): ?>
                            <option value="<?= htmlspecialchars($type) ?>">
                                <?= htmlspecialchars($type) ?>
                            </option>
                        <?php endforeach; ?>
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
                    <label>Code Description</label>
                    <select id="interruptionDescription" disabled>
                        <option value="">-- Select type first --</option>
                    </select>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Interruption Group</label>
                    <input type="text" id="interruptionGroup" readonly>
                </div>

                <div class="form-group">
                    <label>Body Responsible</label>
                    <input type="text" id="bodyResponsible" readonly>
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
const interruptionCodes = <?= json_encode($codesByType) ?>;
const descriptionsByType = <?= json_encode($descriptionsByType) ?>;

// Populate codes + descriptions by type
document.getElementById('interruptionType').addEventListener('change', function () {
    const type = this.value;
    const codeSelect = document.getElementById('interruptionCode');
    const descSelect = document.getElementById('interruptionDescription');

    codeSelect.innerHTML = '<option value="">-- Select Code --</option>';
    descSelect.innerHTML = '<option value="">-- Select Description --</option>';

    codeSelect.disabled = !type;
    descSelect.disabled = !type;

    document.getElementById('interruptionGroup').value = '';
    document.getElementById('bodyResponsible').value = '';
    document.getElementById('approvalNotice').style.display = 'none';

    if (type && interruptionCodes[type]) {
        interruptionCodes[type].forEach(code => {
            const opt = document.createElement('option');
            opt.value = code.interruption_code;
            opt.textContent = code.interruption_code + ' - ' + code.interruption_description;
            opt.dataset.description = code.interruption_description;
            opt.dataset.group = code.interruption_group;
            opt.dataset.body = code.body_responsible;
            opt.dataset.approval = code.approval_requirement;
            codeSelect.appendChild(opt);
        });
    }

    if (type && descriptionsByType[type]) {
        [...new Set(descriptionsByType[type])].forEach(desc => {
            const opt = document.createElement('option');
            opt.value = desc;
            opt.textContent = desc;
            descSelect.appendChild(opt);
        });
    }
});

// Sync fields on code select
document.getElementById('interruptionCode').addEventListener('change', function () {
    const opt = this.options[this.selectedIndex];
    if (!opt || !opt.value) return;

    document.getElementById('interruptionDescription').value = opt.dataset.description;
    document.getElementById('interruptionGroup').value = opt.dataset.group;
    document.getElementById('bodyResponsible').value = opt.dataset.body;

    if (opt.dataset.approval === 'YES') {
        document.getElementById('approvalNotice').style.display = 'block';
    } else {
        document.getElementById('approvalNotice').style.display = 'none';
    }
});

// Show/hide "other reasons" field
document.getElementById('reasonForDelay').addEventListener('change', function() {
    const otherGroup = document.getElementById('delayReasonGroup');
    if (this.value === 'others') {
        otherGroup.style.display = 'block';
    } else {
        otherGroup.style.display = 'none';
    }
});

// Set minimum datetime to today
const now = new Date();
const today = now.toISOString().slice(0, 16);
document.getElementById('datetimeOut').min = today;
document.getElementById('datetimeIn').min = today;
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
