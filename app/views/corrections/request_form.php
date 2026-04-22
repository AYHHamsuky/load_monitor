<?php
/**
 * Correction Request Form - WITH DEBUGGING
 * Path: /app/views/corrections/request_form.php
 */

require __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/sidebar.php';

$user = Auth::user();
$db = Database::connect();

// Get feeders based on user role
if ($user['role'] === 'UL1') {
    $feedersStmt = $db->prepare("
        SELECT fdr11kv_code, fdr11kv_name, max_load
        FROM fdr11kv 
        WHERE iss_code = ?
        ORDER BY fdr11kv_name
    ");
    $feedersStmt->execute([$user['iss_code']]);
    $feeders = $feedersStmt->fetchAll(PDO::FETCH_ASSOC);
    $feeder_type = '11kV';
    $feeder_code_field = 'fdr11kv_code';
    $feeder_name_field = 'fdr11kv_name';
    
} elseif ($user['role'] === 'UL2') {
    // Get all 33kV feeders with station information (same logic as log_form.php)
    $feedersStmt = $db->query("
        SELECT f.fdr33kv_code, f.fdr33kv_name, f.max_load, t.station_name
        FROM fdr33kv f
        LEFT JOIN transmission_stations t ON f.ts_code = t.ts_code
        ORDER BY f.fdr33kv_name
    ");
    $feeders = $feedersStmt->fetchAll(PDO::FETCH_ASSOC);
    $feeder_type = '33kV';
    $feeder_code_field = 'fdr33kv_code';
    $feeder_name_field = 'fdr33kv_name';
    
} else {
    $feeders = [];
    $feeder_type = 'Unknown';
    $feeder_code_field = '';
    $feeder_name_field = '';
}
?>

<style>
:root {
    --primary-color: #4f46e5;
    --primary-dark: #4338ca;
    --primary-light: #eef2ff;
    --secondary-color: #64748b;
    --success-color: #10b981;
    --danger-color: #ef4444;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --border-color: #e2e8f0;
    --bg-light: #f8fafc;
}

.main-content {
    margin-left: 260px;
    margin-top: 70px;
    padding: 2rem;
    min-height: calc(100vh - 70px);
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

.container {
    max-width: 900px;
    margin: 0 auto;
}

.correction-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border: 1px solid var(--border-color);
}

/* Debug Panel */
.debug-panel {
    background: #fff3cd;
    border: 2px solid #ffc107;
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1.5rem;
    font-family: 'Courier New', monospace;
    font-size: 12px;
}

.debug-panel h4 {
    color: #856404;
    margin: 0 0 0.5rem 0;
    font-size: 14px;
    font-weight: bold;
}

.debug-panel pre {
    background: #fff;
    padding: 0.5rem;
    border-radius: 5px;
    overflow-x: auto;
    margin: 0.5rem 0;
}

/* Page Header */
.page-header {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid var(--bg-light);
}

.icon-wrapper {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.page-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
}

.page-subtitle {
    color: var(--text-secondary);
    font-size: 0.95rem;
    margin: 0.25rem 0 0 0;
}

/* Form Sections */
.form-section {
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 2px solid var(--bg-light);
}

.form-section:last-of-type {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.section-title {
    color: var(--text-primary);
    margin: 0 0 1.5rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.section-title i {
    color: var(--primary-color);
}

/* Form Groups */
.form-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group:last-child {
    margin-bottom: 0;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    color: var(--text-primary);
}

.form-group label i {
    margin-right: 0.375rem;
    color: var(--primary-color);
}

.required {
    color: var(--danger-color);
    margin-left: 2px;
}

/* Input Wrapper with Icons */
.input-wrapper {
    position: relative;
}

.input-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-secondary);
    font-size: 0.875rem;
    pointer-events: none;
    z-index: 1;
}

.input-wrapper input,
.input-wrapper select,
.input-wrapper textarea {
    padding-left: 2.75rem;
}

/* Form Controls */
.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid var(--border-color);
    border-radius: 10px;
    font-size: 0.875rem;
    transition: all 0.3s ease;
    background: white;
    color: var(--text-primary);
    box-sizing: border-box;
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
    font-family: inherit;
    line-height: 1.5;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 4px var(--primary-light);
}

.form-group select {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 1.25rem;
    padding-right: 2.5rem;
}

.form-hint {
    display: flex;
    align-items: flex-start;
    gap: 0.375rem;
    margin-top: 0.5rem;
    color: var(--text-secondary);
    font-size: 0.8125rem;
    line-height: 1.4;
}

.form-hint i {
    margin-top: 2px;
    flex-shrink: 0;
}

/* Buttons */
.btn-group {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 2px solid var(--bg-light);
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.875rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
}

.btn-primary:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(79, 70, 229, 0.4);
}

.btn-secondary {
    background: white;
    color: var(--text-secondary);
    border: 2px solid var(--border-color);
}

.btn-secondary:hover {
    background: var(--bg-light);
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Alert Banner */
.alert-banner {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-left: 4px solid #f59e0b;
    padding: 1rem 1.25rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: start;
    gap: 0.75rem;
}

.alert-banner i {
    color: #f59e0b;
    font-size: 1.25rem;
    flex-shrink: 0;
    margin-top: 2px;
}

.alert-content {
    flex: 1;
}

.alert-title {
    font-weight: 600;
    color: #92400e;
    margin-bottom: 0.25rem;
}

.alert-text {
    color: #78350f;
    font-size: 0.875rem;
    line-height: 1.5;
}

/* Modal Styles */
.modal-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    z-index: 9999;
    align-items: center;
    justify-content: center;
}

.modal-overlay.active {
    display: flex;
}

.status-modal {
    background: white;
    border-radius: 16px;
    padding: 2.5rem;
    max-width: 450px;
    width: 90%;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    text-align: center;
    animation: modalSlideIn 0.3s ease-out;
    max-height: 90vh;
    overflow-y: auto;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: scale(0.9) translateY(-20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.status-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 1.5rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
}

.status-icon.success {
    background: linear-gradient(135deg, #dcfce7, #bbf7d0);
    color: #166534;
}

.status-icon.error {
    background: linear-gradient(135deg, #fee2e2, #fecaca);
    color: #991b1b;
}

.status-modal h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 0.75rem;
}

.status-modal p {
    color: var(--text-secondary);
    font-size: 0.9375rem;
    line-height: 1.5;
    margin-bottom: 1.5rem;
}

.countdown {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin-top: 1rem;
}

.countdown-bar {
    width: 100%;
    height: 4px;
    background: #e5e7eb;
    border-radius: 4px;
    overflow: hidden;
    margin: 1rem 0;
}

.countdown-progress {
    height: 100%;
    background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
    width: 100%;
    transition: width 1s linear;
}

/* Responsive */
@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        padding: 1rem;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .btn-group {
        flex-direction: column;
    }
}
</style>

<div class="main-content">
    <div class="container">
        <div class="correction-card">
            <div class="page-header">
                <div class="icon-wrapper">
                    <i class="fas fa-edit"></i>
                </div>
                <div>
                    <h1 class="page-title">Request Data Correction</h1>
                    <p class="page-subtitle">Submit a request to correct historical load data</p>
                </div>
            </div>


            <div class="alert-banner">
                <i class="fas fa-info-circle"></i>
                <div class="alert-content">
                    <div class="alert-title">Correction Request Process</div>
                    <div class="alert-text">
                        Your correction request will be reviewed by an Analyst and then approved by a Manager before being applied to the database.
                    </div>
                </div>
            </div>

            <?php if (empty($feeders)): ?>
            <div class="alert-banner" style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); border-left-color: #dc2626;">
                <i class="fas fa-exclamation-triangle"></i>
                <div class="alert-content">
                    <div class="alert-title" style="color: #991b1b;">No Feeders Found</div>
                    <div class="alert-text" style="color: #7f1d1d;">
                        No <?= $feeder_type ?> feeders found for your account. Please contact your system administrator.
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <form id="correctionForm" method="POST">
                <!-- Feeder Information -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-bolt"></i>
                        Feeder Information
                    </h3>
                    
                    <div class="form-group">
                        <label>
                            <i class="fas fa-bolt"></i>
                            Select Feeder <span class="required">*</span>
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-bolt input-icon"></i>
                            <select name="feeder_code" required style="padding-left: 2.75rem;" <?= empty($feeders) ? 'disabled' : '' ?>>
                                <option value="">-- Select Feeder --</option>
                                <?php foreach ($feeders as $feeder): ?>
                                    <option value="<?= htmlspecialchars($feeder[$feeder_code_field]) ?>">
                                        <?= htmlspecialchars($feeder[$feeder_name_field]) ?>
                                        <?php if ($user['role'] === 'UL2' && isset($feeder['station_name'])): ?>
                                            - <?= htmlspecialchars($feeder['station_name']) ?>
                                        <?php elseif (isset($feeder['ts_code'])): ?>
                                            (TS: <?= htmlspecialchars($feeder['ts_code']) ?>)
                                        <?php endif; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-hint">
                            <i class="fas fa-info-circle"></i>
                            Select the <?= $feeder_type ?> feeder for which you want to request a correction
                            (Found: <?= count($feeders) ?> feeder<?= count($feeders) != 1 ? 's' : '' ?>)
                        </div>
                    </div>
                    
                    <input type="hidden" name="correction_type" value="<?= htmlspecialchars($feeder_type) ?>">
                </div>

                <!-- Date & Time Information -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-calendar"></i>
                        Date & Time
                    </h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>
                                <i class="fas fa-calendar-day"></i>
                                Entry Date <span class="required">*</span>
                            </label>
                            <div class="input-wrapper">
                                <i class="fas fa-calendar-day input-icon"></i>
                                <input type="date" name="entry_date" id="entryDate" required style="padding-left: 2.75rem;">
                            </div>
                            <div class="form-hint">
                                <i class="fas fa-info-circle"></i>
                                Select the date of the entry you want to correct
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <i class="fas fa-clock"></i>
                                Entry Hour <span class="required">*</span>
                            </label>
                            <div class="input-wrapper">
                                <i class="fas fa-clock input-icon"></i>
                                <select name="entry_hour" id="entryHour" required style="padding-left: 2.75rem;">
                                    <option value="">-- Select Hour --</option>
                                    <?php for ($h = 1; $h <= 24; $h++): ?>
                                        <option value="<?= $h ?>"><?= sprintf('%02d:00', $h) ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-hint">
                                <i class="fas fa-info-circle"></i>
                                Select the hour (1-24) you want to correct
                            </div>
                        </div>
                    </div>

                    <!-- Blank Hour Flag — shown dynamically by JS when hour has no entry -->
                    <div id="blankHourPanel" style="display:none; margin-top:1rem;">
                        <div style="background:linear-gradient(135deg,#fff7ed 0%,#ffedd5 100%);
                                    border-left:4px solid #f97316;
                                    border-radius:10px;
                                    padding:1rem 1.25rem;
                                    margin-bottom:1rem;">
                            <div style="display:flex;align-items:center;gap:0.5rem;font-weight:700;
                                        color:#c2410c;margin-bottom:0.5rem;font-size:0.95rem;">
                                <i class="fas fa-exclamation-triangle"></i>
                                ⚠️ Blank Hour Detected
                            </div>
                            <div style="color:#7c2d12;font-size:0.875rem;line-height:1.5;">
                                No entry exists for the selected feeder, date and hour. 
                                This request will be <strong>flagged</strong> to reviewers as a blank-hour submission.
                                You must explain below why this hour was left blank.
                            </div>
                        </div>
                        <div class="form-group">
                            <label>
                                <i class="fas fa-question-circle" style="color:#f97316;"></i>
                                Why was this hour left blank? <span class="required">*</span>
                            </label>
                            <div class="input-wrapper">
                                <i class="fas fa-question-circle input-icon" style="color:#f97316;"></i>
                                <textarea name="blank_hour_reason" id="blankHourReason"
                                          rows="3"
                                          placeholder="e.g. System was down, data was not captured at the time, oversight during shift..."
                                          style="padding-left:2.75rem; border-color:#f97316;"></textarea>
                            </div>
                            <div class="form-hint">
                                <i class="fas fa-info-circle"></i>
                                This explanation will appear prominently on the analyst and manager review screens.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Correction Details -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-wrench"></i>
                        Correction Details
                    </h3>
                    
                    <div class="form-group">
                        <label>
                            <i class="fas fa-tag"></i>
                            Field to Correct <span class="required">*</span>
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-tag input-icon"></i>
                            <select name="field_to_correct" id="fieldSelect" required style="padding-left: 2.75rem;">
                                <option value="">-- Select Field --</option>
                                <option value="load_read">Load Reading</option>
                                <option value="fault_code">Fault Code</option>
                                <option value="fault_remark">Fault Remark</option>
                            </select>
                        </div>
                        <div class="form-hint">
                            <i class="fas fa-info-circle"></i>
                            Choose which field you want to correct
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <i class="fas fa-edit"></i>
                            New Value <span class="required">*</span>
                        </label>
                        <div class="input-wrapper" id="newValueWrapper">
                            <i class="fas fa-edit input-icon"></i>
                            <input type="text" name="new_value" id="newValue" 
                                   placeholder="Select field type above first" required style="padding-left: 2.75rem;">
                        </div>
                        <div class="form-hint" id="valueHint">
                            <i class="fas fa-info-circle"></i>
                            Select field type above to see specific instructions
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>
                            <i class="fas fa-comment"></i>
                            Reason for Correction <span class="required">*</span>
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-comment input-icon"></i>
                            <textarea name="reason" required 
                                      placeholder="Explain why this correction is needed..." style="padding-left: 2.75rem;"></textarea>
                        </div>
                        <div class="form-hint">
                            <i class="fas fa-info-circle"></i>
                            Provide a clear explanation for why this correction is necessary
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='?page=corrections&action=my-requests'">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitBtn" <?= empty($feeders) ? 'disabled' : '' ?>>
                        <i class="fas fa-paper-plane"></i>
                        Submit Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Status Modal -->
<div class="modal-overlay" id="statusModal">
    <div class="status-modal">
        <div class="status-icon" id="statusIcon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h2 id="statusTitle">Success!</h2>
        <p id="statusMessage">Your correction request has been submitted successfully.</p>
        <div class="countdown-bar" id="countdownBarContainer">
            <div class="countdown-progress" id="countdownProgress"></div>
        </div>
        <p class="countdown" id="countdown">Redirecting to dashboard in <strong>5</strong> seconds...</p>
        <button class="btn btn-primary" onclick="goToDashboard()">
            <i class="fas fa-home"></i>
            Go to Dashboard Now
        </button>
    </div>
</div>

<script>
// Available fault codes
const faultCodes = {
    '': 'No Fault',
    'FO': 'Forced Outage',
    'BF': 'Breaker Failure',
    'OS': 'Out of Service',
    'DOff': 'De-energized Off',
    'MVR': 'Manual Voltage Reduction',
    'OL': 'Overload',
    'UV': 'Under Voltage',
    'OV': 'Over Voltage',
    'SC': 'Short Circuit',
    'GF': 'Ground Fault',
    'TF': 'Transformer Fault',
    'LO': 'Line Outage',
    'EQ': 'Equipment Failure',
    'MT': 'Maintenance',
    'WE': 'Weather Related',
    'OT': 'Other'
};

// ── Feeder max_load map (built from PHP) ─────────────────────────────────────
const feederMaxLoad = {};
<?php foreach ($feeders as $f): ?>
<?php
    $code     = $feeder_type === '33kV' ? $f['fdr33kv_code'] : $f['fdr11kv_code'];
    $maxLoad  = isset($f['max_load']) && $f['max_load'] !== null ? (float)$f['max_load'] : null;
?>
<?php if ($maxLoad !== null): ?>
feederMaxLoad[<?= json_encode($code) ?>] = <?= $maxLoad ?>;
<?php endif; ?>
<?php endforeach; ?>

// Restrict date to past dates only
document.getElementById('entryDate').max = new Date(Date.now() - 86400000).toISOString().split('T')[0];

// ── Blank-hour detection ──────────────────────────────────────────────────────
// When feeder, date, or hour changes, check via AJAX if that slot has an entry.
// If not, show the blank-hour panel and make blank_hour_reason required.

let blankHourCheckTimer = null;

function checkBlankHour() {
    clearTimeout(blankHourCheckTimer);
    blankHourCheckTimer = setTimeout(_doBlankHourCheck, 400);
}

function _doBlankHourCheck() {
    const feeder = document.querySelector('[name="feeder_code"]').value;
    const date   = document.getElementById('entryDate').value;
    const hour   = document.getElementById('entryHour').value;
    const panel  = document.getElementById('blankHourPanel');
    const reasonField = document.getElementById('blankHourReason');

    if (!feeder || !date || !hour) {
        panel.style.display = 'none';
        reasonField.required = false;
        return;
    }

    const fd = new FormData();
    fd.append('feeder_code',     feeder);
    fd.append('entry_date',      date);
    fd.append('entry_hour',      hour);
    fd.append('correction_type', document.querySelector('[name="correction_type"]').value);

    fetch('ajax/check_blank_hour.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            if (res.is_blank) {
                panel.style.display = 'block';
                reasonField.required = true;
            } else {
                panel.style.display = 'none';
                reasonField.required = false;
                reasonField.value = '';
            }
        })
        .catch(() => {
            // On network error just hide the panel — server will validate
            panel.style.display = 'none';
            reasonField.required = false;
        });
}

document.querySelector('[name="feeder_code"]').addEventListener('change', checkBlankHour);
document.getElementById('entryDate').addEventListener('change', checkBlankHour);
document.getElementById('entryHour').addEventListener('change', checkBlankHour);

// Dynamic input validation based on field type
document.getElementById('fieldSelect').addEventListener('change', function() {
    const wrapper = document.getElementById('newValueWrapper');
    const hint = document.getElementById('valueHint');
    const field = this.value;
    
    let inputHtml = '';
    let hintText = '';
    
    switch(field) {
        case 'load_read': {
            const selFeeder  = document.querySelector('[name="feeder_code"]').value;
            const maxLoad    = (selFeeder && feederMaxLoad[selFeeder] !== undefined)
                                   ? feederMaxLoad[selFeeder] : null;
            const maxAttr    = maxLoad !== null ? `max="${maxLoad}"` : '';
            const maxHint    = maxLoad !== null
                ? `<span id="maxLoadHint" style="color:#059669;font-weight:700;">
                       Max allowed: ${maxLoad} MW
                   </span>`
                : '';

            inputHtml = `
                <i class="fas fa-bolt input-icon"></i>
                <input type="number" name="new_value" id="newValue"
                       required step="0.01" min="0" ${maxAttr}
                       placeholder="Enter load reading (e.g., 5.50)"
                       style="padding-left: 2.75rem;"
                       oninput="validateLoadInput(this)">
                <div id="loadErrorMsg" style="display:none;color:#dc2626;font-size:12px;
                     font-weight:600;margin-top:4px;padding:6px 10px;background:#fee2e2;
                     border-radius:6px;border:1px solid #fca5a5;">
                </div>
            `;
            hintText = `<i class="fas fa-info-circle"></i> Enter load reading in MW (e.g., 5.50). ${maxHint}`;
            break;
        }
            
        case 'fault_code':
            let faultOptions = '<option value="">-- Select Fault Code --</option>';
            for (let code in faultCodes) {
                const optionText = code ? `${code} - ${faultCodes[code]}` : faultCodes[code];
                faultOptions += `<option value="${code}">${optionText}</option>`;
            }
            
            inputHtml = `
                <i class="fas fa-exclamation-triangle input-icon"></i>
                <select name="new_value" id="newValue" required style="padding-left: 2.75rem;">
                    ${faultOptions}
                </select>
            `;
            hintText = '<i class="fas fa-info-circle"></i> Select the appropriate fault code from the dropdown list';
            break;
            
        case 'fault_remark':
            inputHtml = `
                <i class="fas fa-comment input-icon"></i>
                <textarea name="new_value" id="newValue" 
                          required rows="3"
                          placeholder="Enter detailed fault remark..." style="padding-left: 2.75rem;"></textarea>
            `;
            hintText = '<i class="fas fa-info-circle"></i> Provide a detailed description of the fault or issue';
            break;
            
        default:
            inputHtml = `
                <i class="fas fa-edit input-icon"></i>
                <input type="text" name="new_value" id="newValue" 
                       placeholder="Enter corrected value" required style="padding-left: 2.75rem;">
            `;
            hintText = '<i class="fas fa-info-circle"></i> Select field type above to see specific instructions';
    }
    
    wrapper.innerHTML = inputHtml;
    hint.innerHTML = hintText;
});

// ── Load max validation ───────────────────────────────────────────────────────
function validateLoadInput(input) {
    const errDiv = document.getElementById('loadErrorMsg');
    if (!errDiv) return;

    const val     = parseFloat(input.value);
    const feeder  = document.querySelector('[name="feeder_code"]').value;
    const maxLoad = (feeder && feederMaxLoad[feeder] !== undefined) ? feederMaxLoad[feeder] : null;

    if (isNaN(val) || val < 0) {
        errDiv.style.display = 'block';
        errDiv.textContent   = 'Load reading cannot be negative.';
        return;
    }

    if (maxLoad !== null && val > maxLoad) {
        errDiv.style.display = 'block';
        errDiv.textContent   = `⚠️ Value ${val} MW exceeds the maximum allowed load of ${maxLoad} MW for this feeder. Please enter a valid value.`;
    } else {
        errDiv.style.display = 'none';
        errDiv.textContent   = '';
    }
}

// ── Re-run blank-hour check also triggers load hint refresh ──────────────────
// When feeder changes while load_read field is active, refresh max hint.
document.querySelector('[name="feeder_code"]').addEventListener('change', function() {
    if (document.getElementById('fieldSelect').value === 'load_read') {
        document.getElementById('fieldSelect').dispatchEvent(new Event('change'));
    }
});

// Form submission with enhanced error handling using XMLHttpRequest
document.getElementById('correctionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const formData = new FormData(this);
    
    // Add user payroll ID
    formData.append('requested_by', '<?= htmlspecialchars($user['payroll_id']) ?>');
    // Include blank_hour_reason if the blank-hour panel is visible
    if (document.getElementById('blankHourPanel').style.display !== 'none') {
        formData.set('blank_hour_reason', document.getElementById('blankHourReason').value);
    }
    
    // ── max_load guard — block submission if load exceeds maximum ────────────
    if (document.getElementById('fieldSelect').value === 'load_read') {
        const loadInput = document.getElementById('newValue');
        const loadVal   = parseFloat(loadInput ? loadInput.value : '');
        const feeder    = document.querySelector('[name="feeder_code"]').value;
        const maxLoad   = (feeder && feederMaxLoad[feeder] !== undefined) ? feederMaxLoad[feeder] : null;

        if (!isNaN(loadVal) && maxLoad !== null && loadVal > maxLoad) {
            showStatusModal(
                false,
                'Load Exceeds Maximum',
                `The value ${loadVal} MW exceeds the maximum allowed load of ${maxLoad} MW for this feeder. Please correct the value before submitting.`
            );
            return;   // stop — do NOT disable button or send XHR
        }
    }

    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    
    // Use XMLHttpRequest for better error handling and compatibility
    const xhr = new XMLHttpRequest();
    
    // Use the correct AJAX path
    xhr.open('POST', 'ajax/correction_request.php', true);
    
    // Set timeout to 15 seconds
    xhr.timeout = 15000;
    
    xhr.onload = function() {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Request';
        
        if (xhr.status === 404) {
            showStatusModal(
                false,
                'Endpoint Not Found',
                'The correction request handler could not be found. Please ensure the ajax/correction_request.php file exists or contact your system administrator.'
            );
        } else if (xhr.status >= 200 && xhr.status < 300) {
            try {
                // Strip any leading PHP notices/warnings before the JSON body
                let rawText = xhr.responseText.trim();
                const jsonStart = rawText.indexOf('{');
                if (jsonStart > 0) rawText = rawText.substring(jsonStart);
                const response = JSON.parse(rawText);
                
                if (response.success) {
                    showStatusModal(
                        true,
                        'Request Submitted Successfully!',
                        response.message || 'Your correction request has been submitted and is pending approval.'
                    );
                } else {
                    showStatusModal(
                        false,
                        'Submission Failed',
                        response.message || 'There was an error submitting your request. Please try again.'
                    );
                }
            } catch (err) {
                console.error('JSON Parse Error:', err);
                console.error('Response Text:', xhr.responseText);
                // If parse failed but HTTP 200, record may have saved — show softer message
                showStatusModal(
                    false,
                    'Submission Status Unknown',
                    'The request may have been saved. Please check your requests list before trying again.'
                );
            }
        } else if (xhr.status === 500) {
            showStatusModal(
                false,
                'Server Error',
                'Internal server error occurred. Please contact your system administrator.'
            );
        } else if (xhr.status === 403) {
            showStatusModal(
                false,
                'Access Denied',
                'You do not have permission to submit correction requests. Please contact your system administrator.'
            );
        } else {
            showStatusModal(
                false,
                'Server Error',
                `Server returned status ${xhr.status}. Please try again or contact support.`
            );
        }
    };
    
    xhr.onerror = function() {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Request';
        
        showStatusModal(
            false,
            'Connection Error',
            'Unable to connect to the server. Please check your network connection and try again.'
        );
    };
    
    xhr.ontimeout = function() {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Request';
        
        showStatusModal(
            false,
            'Request Timeout',
            'The request took too long to complete. Please try again.'
        );
    };
    
    // Send the form data
    xhr.send(formData);
});

// Show status modal with countdown
function showStatusModal(success, title, message) {
    const modal = document.getElementById('statusModal');
    const icon = document.getElementById('statusIcon');
    const titleEl = document.getElementById('statusTitle');
    const messageEl = document.getElementById('statusMessage');
    const countdownEl = document.getElementById('countdown');
    const progressBar = document.getElementById('countdownProgress');
    const countdownBarContainer = document.getElementById('countdownBarContainer');

    // Update modal content
    icon.className = 'status-icon ' + (success ? 'success' : 'error');
    icon.innerHTML = success 
        ? '<i class="fas fa-check-circle"></i>' 
        : '<i class="fas fa-exclamation-circle"></i>';
    titleEl.textContent = title;
    messageEl.textContent = message;

    // Show modal
    modal.classList.add('active');

    // Start countdown only on success
    if (success) {
        let seconds = 5;
        countdownEl.style.display = 'block';
        countdownBarContainer.style.display = 'block';
        
        const countdownInterval = setInterval(() => {
            seconds--;
            countdownEl.innerHTML = `Redirecting to dashboard in <strong>${seconds}</strong> seconds...`;
            progressBar.style.width = (seconds / 5 * 100) + '%';
            
            if (seconds <= 0) {
                clearInterval(countdownInterval);
                goToDashboard();
            }
        }, 1000);
    } else {
        countdownEl.style.display = 'none';
        countdownBarContainer.style.display = 'none';
    }
}

// Navigate to dashboard
function goToDashboard() {
    window.location.href = '?page=corrections&action=my-requests';
}
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
