<?php
/**
 * 33kV Correction Request Form
 * Path: /app/views/corrections33kv/request_form.php
 */

$user = Auth::user();
$db = Database::connect();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>33kV Correction Request</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<style>
:root {
    --primary-color: #dc3545;
    --primary-dark: #c82333;
    --primary-light: #fff5f5;
    --secondary-color: #64748b;
    --success-color: #10b981;
    --danger-color: #ef4444;
    --text-primary: #1e293b;
    --text-secondary: #64748b;
    --border-color: #e2e8f0;
    --bg-light: #f8fafc;
}

.main-content {
    margin-left: 250px;
    margin-top: 70px;
    padding: 2rem;
    min-height: calc(100vh - 70px);
    background: linear-gradient(135deg, #fff5f5 0%, #ffe5e5 100%);
}

.container {
    max-width: 900px;
    margin: 0 auto;
}

.correction-card {
    background: #ffffff;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 10px 30px rgba(220, 53, 69, 0.1);
    border: 2px solid #ffc9c9;
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

.form-group select:disabled {
    background-color: #f8f9fa;
    cursor: not-allowed;
    opacity: 0.6;
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
    box-shadow: 0 6px 20px rgba(220, 53, 69, 0.3);
}

.btn-secondary {
    background: #e9ecef;
    color: #495057;
}

.btn-secondary:hover {
    background: #dee2e6;
}

.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}

/* Alert Box */
.alert {
    padding: 1rem;
    border-radius: 10px;
    margin-bottom: 1.5rem;
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
}

.alert-info {
    background: #e0f2fe;
    border: 1px solid #0ea5e9;
    color: #075985;
}

.alert-warning {
    background: #fef3c7;
    border: 1px solid #f59e0b;
    color: #92400e;
}

.alert i {
    flex-shrink: 0;
    margin-top: 2px;
}

/* Modal Overlay */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    backdrop-filter: blur(5px);
}

.modal-overlay.active {
    opacity: 1;
    visibility: visible;
}

.status-modal {
    background: white;
    border-radius: 20px;
    padding: 2.5rem;
    max-width: 500px;
    width: 90%;
    text-align: center;
    transform: scale(0.9);
    transition: transform 0.3s ease;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    max-height: 90vh;
    overflow-y: auto;
}

.modal-overlay.active .status-modal {
    transform: scale(1);
}

.status-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 1.5rem;
    font-size: 2.5rem;
}

.status-icon.success {
    background: #d1fae5;
    color: #10b981;
}

.status-icon.error {
    background: #fee2e2;
    color: #ef4444;
}

.status-modal h2 {
    margin: 0 0 1rem;
    color: var(--text-primary);
}

.status-modal p {
    color: var(--text-secondary);
    margin-bottom: 1.5rem;
    line-height: 1.6;
}

.countdown-bar {
    height: 4px;
    background: #e5e7eb;
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 1rem;
}

.countdown-progress {
    height: 100%;
    background: linear-gradient(90deg, var(--primary-color), var(--primary-dark));
    width: 100%;
    transition: width 1s linear;
}

.countdown {
    font-size: 0.875rem;
    color: var(--text-secondary);
    margin-bottom: 1rem;
}

/* Responsive */
@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        margin-top: 60px;
        padding: 1rem;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .page-header {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<div class="main-content">
    <div class="container">
        <div class="correction-card">
            <!-- Page Header -->
            <div class="page-header">
                <div class="icon-wrapper">
                    <i class="fas fa-bolt"></i>
                </div>
                <div>
                    <h1 class="page-title">33kV Correction Request</h1>
                    <p class="page-subtitle">Request correction for historical 33kV load data</p>
                </div>
            </div>

            <!-- Info Alert -->
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>Important:</strong> Corrections can only be made for past entries. Your request will be reviewed by an analyst (UL3) and then approved by a manager (UL4) before being applied.
                </div>
            </div>

            <!-- Correction Form -->
            <form id="correctionForm">
                <!-- Entry Identification Section -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-map-marker-alt"></i>
                        Entry Identification
                    </h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="tsCode">
                                <i class="fas fa-building"></i>
                                Transmission Station <span class="required">*</span>
                            </label>
                            <div class="input-wrapper">
                                <i class="fas fa-building input-icon"></i>
                                <select name="ts_code" id="tsCode" required>
                                    <option value="">-- Select Transmission Station --</option>
                                    <?php foreach ($transmission_stations as $ts): ?>
                                        <option value="<?= htmlspecialchars($ts['ts_code']) ?>">
                                            <?= htmlspecialchars($ts['station_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-hint">
                                <i class="fas fa-info-circle"></i>
                                Select the transmission station first
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="feederCode">
                                <i class="fas fa-bolt"></i>
                                33kV Feeder <span class="required">*</span>
                            </label>
                            <div class="input-wrapper">
                                <i class="fas fa-bolt input-icon"></i>
                                <select name="feeder_code" id="feederCode" required disabled>
                                    <option value="">-- Select Transmission Station First --</option>
                                </select>
                            </div>
                            <div class="form-hint">
                                <i class="fas fa-info-circle"></i>
                                Feeders will load based on selected station
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="entryDate">
                                <i class="fas fa-calendar"></i>
                                Entry Date <span class="required">*</span>
                            </label>
                            <div class="input-wrapper">
                                <i class="fas fa-calendar input-icon"></i>
                                <input type="date" name="entry_date" id="entryDate" required>
                            </div>
                            <div class="form-hint">
                                <i class="fas fa-info-circle"></i>
                                Select the date of the entry to correct
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="entryHour">
                                <i class="fas fa-clock"></i>
                                Entry Hour <span class="required">*</span>
                            </label>
                            <div class="input-wrapper">
                                <i class="fas fa-clock input-icon"></i>
                                <select name="entry_hour" id="entryHour" required>
                                    <option value="">-- Select Hour --</option>
                                    <?php for ($h = 1; $h <= 24; $h++): ?>
                                        <option value="<?= $h ?>"><?= number_format($h, 2) ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="form-hint">
                                <i class="fas fa-info-circle"></i>
                                Select the hour (1.00 - 24.00)
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Correction Details Section -->
                <div class="form-section">
                    <h3 class="section-title">
                        <i class="fas fa-edit"></i>
                        Correction Details
                    </h3>

                    <div class="form-group">
                        <label for="fieldSelect">
                            <i class="fas fa-list"></i>
                            Field to Correct <span class="required">*</span>
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-list input-icon"></i>
                            <select name="field_to_correct" id="fieldSelect" required>
                                <option value="">-- Select Field --</option>
                                <option value="load_read">Load Reading (MW)</option>
                                <option value="fault_code">Fault Code</option>
                                <option value="fault_remark">Fault Remark</option>
                            </select>
                        </div>
                        <div class="form-hint">
                            <i class="fas fa-info-circle"></i>
                            Choose which field needs to be corrected
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="newValue">
                            <i class="fas fa-pen"></i>
                            New Value <span class="required">*</span>
                        </label>
                        <div class="input-wrapper" id="newValueWrapper">
                            <i class="fas fa-edit input-icon"></i>
                            <input type="text" name="new_value" id="newValue" 
                                   placeholder="Select field type above first" required disabled>
                        </div>
                        <div class="form-hint" id="valueHint">
                            <i class="fas fa-info-circle"></i>
                            Select field type above to see specific instructions
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reason">
                            <i class="fas fa-comment-dots"></i>
                            Reason for Correction <span class="required">*</span>
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-comment-dots input-icon"></i>
                            <textarea name="reason" id="reason" required 
                                      placeholder="Provide a detailed reason for this correction request..."></textarea>
                        </div>
                        <div class="form-hint">
                            <i class="fas fa-info-circle"></i>
                            Explain why this correction is necessary
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="btn-group">
                    <button type="button" class="btn btn-secondary" onclick="window.location.href='?page=dashboard33kv'">
                        <i class="fas fa-times"></i>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
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
// Fault codes for 33kV
const faultCodes = {
    '': 'No Fault',
    'TRP': 'Tripping',
    'LBD': 'Line Breakdown',
    'TCH': 'Transformer Change-over',
    'ULO': 'Utility Load Out',
    'PLN': 'Planned Maintenance',
    'UNP': 'Unplanned Outage',
    'OVL': 'Overload',
    'UDV': 'Under Voltage',
    'EQF': 'Equipment Failure',
    'WTH': 'Weather Related',
    'OTH': 'Other (Specify in Remarks)'
};

// Restrict date to past dates only
document.getElementById('entryDate').max = new Date(Date.now() - 86400000).toISOString().split('T')[0];

// Load feeders when transmission station is selected
document.getElementById('tsCode').addEventListener('change', function() {
    const tsCode = this.value;
    const feederSelect = document.getElementById('feederCode');
    
    if (!tsCode) {
        feederSelect.disabled = true;
        feederSelect.innerHTML = '<option value="">-- Select Transmission Station First --</option>';
        return;
    }
    
    // Show loading
    feederSelect.disabled = true;
    feederSelect.innerHTML = '<option value="">Loading feeders...</option>';
    
    // Fetch feeders via AJAX
    fetch('ajax/get_33kv_feeders.php?ts_code=' + encodeURIComponent(tsCode))
        .then(response => response.json())
        .then(data => {
            if (data.success && data.feeders) {
                feederSelect.innerHTML = '<option value="">-- Select 33kV Feeder --</option>';
                
                data.feeders.forEach(feeder => {
                    const option = document.createElement('option');
                    option.value = feeder.fdr33kv_code;
                    option.textContent = feeder.fdr33kv_name;
                    feederSelect.appendChild(option);
                });
                
                feederSelect.disabled = false;
            } else {
                feederSelect.innerHTML = '<option value="">Error loading feeders</option>';
                console.error('Error:', data.message);
            }
        })
        .catch(error => {
            console.error('Error loading feeders:', error);
            feederSelect.innerHTML = '<option value="">Error loading feeders</option>';
        });
});

// Dynamic input validation based on field type
document.getElementById('fieldSelect').addEventListener('change', function() {
    const wrapper = document.getElementById('newValueWrapper');
    const hint = document.getElementById('valueHint');
    const field = this.value;
    
    let inputHtml = '';
    let hintText = '';
    
    switch(field) {
        case 'load_read':
            inputHtml = `
                <i class="fas fa-bolt input-icon"></i>
                <input type="number" name="new_value" id="newValue" 
                       required step="0.01" min="0"
                       placeholder="Enter load reading (e.g., 25.50)">
            `;
            hintText = '<i class="fas fa-info-circle"></i> Enter load reading in megawatts (MW) using decimal format';
            break;
            
        case 'fault_code':
            let faultOptions = '<option value="">-- Select Fault Code --</option>';
            for (let code in faultCodes) {
                const optionText = code ? `${code} - ${faultCodes[code]}` : faultCodes[code];
                faultOptions += `<option value="${code}">${optionText}</option>`;
            }
            
            inputHtml = `
                <i class="fas fa-exclamation-triangle input-icon"></i>
                <select name="new_value" id="newValue" required>
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
                          placeholder="Enter detailed fault remark..."></textarea>
            `;
            hintText = '<i class="fas fa-info-circle"></i> Provide a detailed description of the fault or issue';
            break;
            
        default:
            inputHtml = `
                <i class="fas fa-edit input-icon"></i>
                <input type="text" name="new_value" id="newValue" 
                       placeholder="Select field type above first" required disabled>
            `;
            hintText = '<i class="fas fa-info-circle"></i> Select field type above to see specific instructions';
    }
    
    wrapper.innerHTML = inputHtml;
    hint.innerHTML = hintText;
});

// Form submission
document.getElementById('correctionForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const submitBtn = document.getElementById('submitBtn');
    const formData = new FormData(this);
    
    // Add user payroll ID
    formData.append('requested_by', '<?= htmlspecialchars($user['payroll_id']) ?>');
    formData.append('correction_type', '33kV');
    
    // Disable submit button
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
    
    // Submit via AJAX
    fetch('ajax/correction_request_33kv.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Request';
        
        if (data.success) {
            showStatusModal(
                true,
                'Request Submitted Successfully!',
                data.message || 'Your 33kV correction request has been submitted and is pending approval.'
            );
        } else {
            showStatusModal(
                false,
                'Submission Failed',
                data.message || 'There was an error submitting your request. Please try again.'
            );
        }
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Request';
        
        console.error('Error:', error);
        showStatusModal(
            false,
            'Connection Error',
            'Unable to connect to the server. Please check your network connection and try again.'
        );
    });
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
    window.location.href = '?page=dashboard33kv';
}
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
