<?php require __DIR__ . '/../layout/header.php'; ?>
<?php require __DIR__ . '/../layout/sidebar.php'; ?>

<div class="main-content">
    <div class="create-report-container">
        <div class="page-header">
            <div>
                <h1>📊 Create Custom Report</h1>
                <p class="subtitle">Design and save your analytics report</p>
            </div>
            <a href="?page=analytics" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>

        <div class="form-card">
            <form id="createReportForm">
                <!-- Report Type Selection -->
                <div class="form-section">
                    <h3><i class="fas fa-chart-pie"></i> Report Type</h3>
                    <div class="report-types-grid">
                        <label class="report-type-card">
                            <input type="radio" name="report_type" value="load_summary" required>
                            <div class="card-content">
                                <i class="fas fa-chart-bar"></i>
                                <h4>Load Summary</h4>
                                <p>Complete load analysis for 11kV and 33kV networks</p>
                            </div>
                        </label>

                        <label class="report-type-card">
                            <input type="radio" name="report_type" value="interruption_analysis">
                            <div class="card-content">
                                <i class="fas fa-exclamation-triangle"></i>
                                <h4>Interruption Analysis</h4>
                                <p>Outage patterns, causes, and trends</p>
                            </div>
                        </label>

                        <label class="report-type-card">
                            <input type="radio" name="report_type" value="data_quality">
                            <div class="card-content">
                                <i class="fas fa-check-circle"></i>
                                <h4>Data Quality</h4>
                                <p>Data completeness and entry accuracy</p>
                            </div>
                        </label>

                        <label class="report-type-card">
                            <input type="radio" name="report_type" value="peak_demand">
                            <div class="card-content">
                                <i class="fas fa-chart-line"></i>
                                <h4>Peak Demand</h4>
                                <p>Peak load patterns by hour and feeder</p>
                            </div>
                        </label>

                        <label class="report-type-card">
                            <input type="radio" name="report_type" value="feeder_performance">
                            <div class="card-content">
                                <i class="fas fa-tachometer-alt"></i>
                                <h4>Feeder Performance</h4>
                                <p>Individual feeder metrics and rankings</p>
                            </div>
                        </label>

                        <label class="report-type-card">
                            <input type="radio" name="report_type" value="complaint_trends">
                            <div class="card-content">
                                <i class="fas fa-bullhorn"></i>
                                <h4>Complaint Trends</h4>
                                <p>Customer complaint analysis and patterns</p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Report Details -->
                <div class="form-section">
                    <h3><i class="fas fa-info-circle"></i> Report Details</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="report_name">Report Name *</label>
                            <input type="text" id="report_name" name="report_name" 
                                   class="form-control" required
                                   placeholder="e.g., Monthly Load Analysis - December 2025">
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" 
                                      class="form-control" rows="3"
                                      placeholder="Brief description of this report..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Date Range -->
                <div class="form-section">
                    <h3><i class="fas fa-calendar"></i> Date Range</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="date_from">From Date *</label>
                            <input type="date" id="date_from" name="date_from" 
                                   class="form-control" required
                                   value="<?= date('Y-m-01') ?>">
                        </div>

                        <div class="form-group">
                            <label for="date_to">To Date *</label>
                            <input type="date" id="date_to" name="date_to" 
                                   class="form-control" required
                                   value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>
                </div>

                <!-- Filters (Optional) -->
                <div class="form-section">
                    <h3><i class="fas fa-filter"></i> Filters (Optional)</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="iss_code">ISS Location</label>
                            <select id="iss_code" name="iss_code" class="form-control">
                                <option value="">All ISS Locations</option>
                                <?php foreach ($iss_list as $iss): ?>
                                    <option value="<?= htmlspecialchars($iss['iss_code']) ?>">
                                        <?= htmlspecialchars($iss['iss_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="ts_code">Transmission Station</label>
                            <select id="ts_code" name="ts_code" class="form-control">
                                <option value="">All Transmission Stations</option>
                                <?php foreach ($ts_list as $ts): ?>
                                    <option value="<?= htmlspecialchars($ts['ts_code']) ?>">
                                        <?= htmlspecialchars($ts['ts_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Visibility -->
                <div class="form-section">
                    <h3><i class="fas fa-eye"></i> Visibility</h3>
                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="is_public" value="1" checked>
                            <span>Make this report public (visible to all users with analytics access)</span>
                        </label>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="?page=analytics" class="btn-cancel">Cancel</a>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Save Report
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.create-report-container {
    padding: 30px;
    max-width: 1200px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.page-header h1 {
    color: #2c3e50;
    margin: 0 0 5px 0;
}

.subtitle {
    color: #6c757d;
    margin: 0;
}

.btn-secondary {
    background: #6c757d;
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.form-card {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.form-section {
    margin-bottom: 40px;
    padding-bottom: 30px;
    border-bottom: 2px solid #f0f0f0;
}

.form-section:last-of-type {
    border-bottom: none;
    padding-bottom: 0;
}

.form-section h3 {
    color: #2c3e50;
    margin: 0 0 20px 0;
    font-size: 18px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.report-types-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
}

.report-type-card {
    position: relative;
    cursor: pointer;
}

.report-type-card input[type="radio"] {
    position: absolute;
    opacity: 0;
}

.report-type-card .card-content {
    border: 2px solid #e0e0e0;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s;
}

.report-type-card input[type="radio"]:checked + .card-content {
    border-color: #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
}

.report-type-card .card-content i {
    font-size: 36px;
    color: #667eea;
    margin-bottom: 10px;
}

.report-type-card .card-content h4 {
    margin: 0 0 8px 0;
    color: #2c3e50;
    font-size: 15px;
}

.report-type-card .card-content p {
    margin: 0;
    color: #6c757d;
    font-size: 12px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
    font-size: 14px;
}

.form-control {
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

textarea.form-control {
    resize: vertical;
    font-family: inherit;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-weight: 500;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    margin-top: 30px;
    padding-top: 30px;
    border-top: 2px solid #f0f0f0;
}

.btn-cancel, .btn-submit {
    padding: 12px 30px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-cancel {
    background: #e9ecef;
    color: #495057;
    border: none;
}

.btn-submit {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

@media (max-width: 768px) {
    .report-types-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
document.getElementById('createReportForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    // Build parameters object
    const parameters = {
        date_from: formData.get('date_from'),
        date_to: formData.get('date_to'),
        iss_code: formData.get('iss_code') || null,
        ts_code: formData.get('ts_code') || null
    };
    
    // Create request data
    const requestData = new FormData();
    requestData.append('report_name', formData.get('report_name'));
    requestData.append('report_type', formData.get('report_type'));
    requestData.append('description', formData.get('description') || '');
    requestData.append('parameters', JSON.stringify(parameters));
    requestData.append('is_public', formData.get('is_public') ? '1' : '0');
    
    fetch('/public/ajax/analytics_save.php', {
        method: 'POST',
        body: requestData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.href = '?page=analytics&action=view&id=' + data.report_id;
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('An error occurred. Please try again.');
        console.error(error);
    });
});
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
