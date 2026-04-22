<?php
/**
 * Reports Index - FULLY FUNCTIONAL WITH PROPER PREVIEW
 * Path: /app/views/reports/index.php
 */

require __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/sidebar.php';

$user = Auth::user();
$db = Database::connect();

// Get feeders based on user role with proper field handling
if ($user['role'] === 'UL1') {
    $stmt = $db->prepare("
        SELECT fdr11kv_code, fdr11kv_name 
        FROM fdr11kv 
        WHERE iss_code = ?
        ORDER BY fdr11kv_name
    ");
    $stmt->execute([$user['iss_code']]);
    $available_feeders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $feeder_code_field = 'fdr11kv_code';
    $feeder_name_field = 'fdr11kv_name';
    $feeder_type = '11kV';
    
} elseif ($user['role'] === 'UL2') {
    $stmt = $db->prepare("
        SELECT fdr33kv_code, fdr33kv_name 
        FROM fdr33kv 
        WHERE fdr33kv_code = ?
    ");
    $stmt->execute([$user['assigned_33kv_code']]);
    $available_feeders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $feeder_code_field = 'fdr33kv_code';
    $feeder_name_field = 'fdr33kv_name';
    $feeder_type = '33kV';
    
} else {
    // UL3, UL4, UL5, UL6, UL7 - Can see all feeders
    $stmt11kv = $db->query("SELECT fdr11kv_code, fdr11kv_name FROM fdr11kv ORDER BY fdr11kv_name");
    $feeders11kv = $stmt11kv->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt33kv = $db->query("SELECT fdr33kv_code, fdr33kv_name FROM fdr33kv ORDER BY fdr33kv_name");
    $feeders33kv = $stmt33kv->fetchAll(PDO::FETCH_ASSOC);
    
    $available_feeders = array_merge($feeders11kv, $feeders33kv);
    $feeder_code_field = null; // Mixed types
    $feeder_name_field = null;
    $feeder_type = 'All';
}

// Get ISS locations for higher level users
$iss_locations = [];
if (in_array($user['role'], ['UL3', 'UL4', 'UL5', 'UL6', 'UL7'])) {
    $stmt = $db->query("SELECT iss_code, iss_name FROM iss_locations ORDER BY iss_name");
    $iss_locations = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<style>
:root {
    --primary-color: #4f46e5;
    --primary-dark: #4338ca;
    --primary-light: #eef2ff;
    --secondary-color: #64748b;
    --success-color: #10b981;
    --warning-color: #f59e0b;
    --danger-color: #ef4444;
    --info-color: #3b82f6;
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

.reports-container {
    max-width: 1400px;
    margin: 0 auto;
}

/* Page Header */
.page-header {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 2rem;
    background: white;
    padding: 2rem;
    border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    border-left: 4px solid var(--primary-color);
}

.header-icon {
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

.page-header h1 {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0;
}

.page-header .subtitle {
    color: var(--text-secondary);
    font-size: 0.95rem;
    margin: 0.25rem 0 0 0;
}

/* Filter Card */
.filter-card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border: 1px solid var(--border-color);
}

.filter-card h3 {
    color: var(--text-primary);
    margin: 0 0 1.5rem 0;
    font-size: 1.125rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.filter-card h3 i {
    color: var(--primary-color);
}

/* Form Styles */
.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 1.5rem;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
    color: var(--text-primary);
}

.form-group small {
    color: var(--text-secondary);
    font-size: 0.8125rem;
    margin-top: 0.375rem;
}

.form-control {
    padding: 0.75rem 1rem;
    border: 2px solid var(--border-color);
    border-radius: 10px;
    font-size: 0.875rem;
    transition: all 0.3s ease;
    background: white;
    color: var(--text-primary);
}

.form-control:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 4px var(--primary-light);
}

select.form-control {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 1.25rem;
    padding-right: 2.5rem;
}

/* Form Actions */
.form-actions {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 2px solid var(--bg-light);
}

.btn-primary, .btn-secondary {
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
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.btn-primary:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.15);
}

.btn-primary:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.btn-secondary {
    background: var(--secondary-color);
    color: white;
}

.btn-secondary:hover {
    background: #475569;
}

/* Quick Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: white;
    padding: 1.5rem;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 1rem;
    border-left: 4px solid transparent;
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.15);
}

.stat-card.blue {
    border-left-color: var(--info-color);
}

.stat-card.green {
    border-left-color: var(--success-color);
}

.stat-card.orange {
    border-left-color: var(--warning-color);
}

.stat-card.red {
    border-left-color: var(--danger-color);
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.stat-card.blue .stat-icon {
    background: rgba(59, 130, 246, 0.1);
    color: var(--info-color);
}

.stat-card.green .stat-icon {
    background: rgba(16, 185, 129, 0.1);
    color: var(--success-color);
}

.stat-card.orange .stat-icon {
    background: rgba(245, 158, 11, 0.1);
    color: var(--warning-color);
}

.stat-card.red .stat-icon {
    background: rgba(239, 68, 68, 0.1);
    color: var(--danger-color);
}

.stat-content h4 {
    margin: 0;
    font-size: 0.875rem;
    color: var(--text-secondary);
    font-weight: 500;
}

.stat-content .value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary);
    margin: 0.25rem 0 0 0;
}

/* Report Preview Section */
.report-preview-section {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border: 1px solid var(--border-color);
    display: none;
}

.report-preview-section.active {
    display: block;
    animation: fadeInUp 0.4s ease;
}

.preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid var(--bg-light);
}

.preview-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.preview-actions {
    display: flex;
    gap: 0.75rem;
}

.btn-export {
    padding: 0.625rem 1.25rem;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-weight: 600;
    font-size: 0.8125rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    transition: all 0.3s ease;
}

.btn-csv {
    background: var(--success-color);
    color: white;
}

.btn-csv:hover {
    background: #059669;
    transform: translateY(-2px);
}

.btn-excel {
    background: #10b981;
    color: white;
}

.btn-excel:hover {
    background: #047857;
    transform: translateY(-2px);
}

.btn-pdf {
    background: var(--danger-color);
    color: white;
}

.btn-pdf:hover {
    background: #dc2626;
    transform: translateY(-2px);
}

.btn-close {
    background: var(--secondary-color);
    color: white;
}

.btn-close:hover {
    background: #475569;
    transform: translateY(-2px);
}

/* Summary Cards in Preview */
.summary-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: var(--bg-light);
    border-radius: 12px;
}

.summary-item {
    display: flex;
    flex-direction: column;
}

.summary-item label {
    font-size: 0.8125rem;
    color: var(--text-secondary);
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.summary-item .value {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--text-primary);
}

/* Report Table */
.report-table-container {
    overflow-x: auto;
    margin-top: 1rem;
    border-radius: 12px;
    border: 1px solid var(--border-color);
}

.report-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.report-table thead {
    background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
    color: white;
}

.report-table thead th {
    padding: 1rem;
    text-align: left;
    font-weight: 600;
    white-space: nowrap;
}

.report-table tbody td {
    padding: 0.875rem 1rem;
    border-bottom: 1px solid var(--border-color);
    color: var(--text-primary);
}

.report-table tbody tr:hover {
    background: var(--primary-light);
}

.report-table tbody tr:last-child td {
    border-bottom: none;
}

/* Loading Spinner */
.loading-spinner {
    display: none;
    text-align: center;
    padding: 2rem;
}

.loading-spinner.active {
    display: block;
}

.spinner {
    border: 4px solid var(--border-color);
    border-top: 4px solid var(--primary-color);
    border-radius: 50%;
    width: 50px;
    height: 50px;
    animation: spin 1s linear infinite;
    margin: 0 auto 1rem;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Alert Styles */
.alert {
    padding: 1rem 1.5rem;
    border-radius: 10px;
    font-size: 0.875rem;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 0.75rem;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #10b981;
}

.alert-danger {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #ef4444;
}

.alert-warning {
    background: #fef3c7;
    color: #92400e;
    border: 1px solid #f59e0b;
}

.alert-info {
    background: #dbeafe;
    color: #1e40af;
    border: 1px solid #3b82f6;
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
    
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .preview-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .preview-actions {
        width: 100%;
        flex-direction: column;
    }
    
    .btn-export {
        width: 100%;
        justify-content: center;
    }
}
</style>

<div class="main-content">
    <div class="reports-container">
        
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-icon">
                <i class="fas fa-chart-bar"></i>
            </div>
            <div>
                <h1>Reports & Analytics</h1>
                <p class="subtitle">Generate comprehensive reports for data analysis and insights</p>
            </div>
        </div>

        <!-- Quick Report Stats -->
        <div class="stats-grid">
            <div class="stat-card blue" onclick="quickReport('daily')">
                <div class="stat-icon">
                    <i class="fas fa-calendar-day"></i>
                </div>
                <div class="stat-content">
                    <h4>Daily Report</h4>
                    <p class="value">Today</p>
                </div>
            </div>

            <div class="stat-card green" onclick="quickReport('weekly')">
                <div class="stat-icon">
                    <i class="fas fa-calendar-week"></i>
                </div>
                <div class="stat-content">
                    <h4>Weekly Report</h4>
                    <p class="value">Last 7 Days</p>
                </div>
            </div>

            <div class="stat-card orange" onclick="quickReport('monthly')">
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="stat-content">
                    <h4>Monthly Report</h4>
                    <p class="value">This Month</p>
                </div>
            </div>

            <div class="stat-card red" onclick="quickReport('fault')">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-content">
                    <h4>Fault Analysis</h4>
                    <p class="value">Last 30 Days</p>
                </div>
            </div>
        </div>

        <!-- Report Filter Form -->
        <div class="filter-card" id="filterCard">
            <h3>
                <i class="fas fa-filter"></i>
                Report Configuration
            </h3>

            <form id="reportForm" onsubmit="return handleGenerateReport(event);">
                <div class="form-row">
                    <div class="form-group">
                        <label for="report_type">
                            <i class="fas fa-file-alt"></i> Report Type
                        </label>
                        <select name="report_type" id="report_type" class="form-control" required>
                            <option value="">Select Report Type...</option>
                            <option value="daily">Daily Load Report</option>
                            <option value="weekly">Weekly Load Report</option>
                            <option value="monthly">Monthly Load Report</option>
                            <option value="feeder">Feeder Performance</option>
                            <option value="fault">Fault Analysis</option>
                            <option value="completion">Data Completion</option>
                        </select>
                        <small>Choose the type of report to generate</small>
                    </div>

                    <div class="form-group">
                        <label for="date_from">
                            <i class="fas fa-calendar-alt"></i> From Date
                        </label>
                        <input type="date" name="date_from" id="date_from" class="form-control" required>
                        <small>Start date of the report period</small>
                    </div>

                    <div class="form-group">
                        <label for="date_to">
                            <i class="fas fa-calendar-check"></i> To Date
                        </label>
                        <input type="date" name="date_to" id="date_to" class="form-control" required>
                        <small>End date of the report period</small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="feeder_filter">
                            <i class="fas fa-bolt"></i> Feeder Selection
                        </label>
                        <select name="feeder_filter" id="feeder_filter" class="form-control">
                            <option value="ALL">All Feeders</option>
                            <?php foreach ($available_feeders as $feeder): ?>
                                <option value="<?= htmlspecialchars($feeder[$feeder_code_field ?? 'fdr11kv_code'] ?? $feeder['fdr11kv_code'] ?? $feeder['fdr33kv_code']) ?>">
                                    <?= htmlspecialchars($feeder[$feeder_name_field ?? 'fdr11kv_name'] ?? $feeder['fdr11kv_name'] ?? $feeder['fdr33kv_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small>Filter by specific feeder or view all</small>
                    </div>

                    <?php if (!empty($iss_locations)): ?>
                    <div class="form-group">
                        <label for="iss_filter">
                            <i class="fas fa-map-marker-alt"></i> ISS Location
                        </label>
                        <select name="iss_filter" id="iss_filter" class="form-control">
                            <option value="">All Locations</option>
                            <?php foreach ($iss_locations as $iss): ?>
                                <option value="<?= htmlspecialchars($iss['iss_code']) ?>">
                                    <?= htmlspecialchars($iss['iss_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small>Filter by ISS location (optional)</small>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-chart-line"></i>
                        Generate Report
                    </button>
                    <button type="button" class="btn-secondary" onclick="resetForm()">
                        <i class="fas fa-redo"></i>
                        Reset
                    </button>
                </div>
            </form>
        </div>

        <!-- Loading Spinner -->
        <div class="loading-spinner" id="loadingSpinner">
            <div class="spinner"></div>
            <p style="color: var(--text-secondary); font-weight: 500;">Generating report... Please wait</p>
        </div>

        <!-- Report Preview Section -->
        <div class="report-preview-section" id="reportPreview">
            <div class="preview-header">
                <h3>
                    <i class="fas fa-eye"></i>
                    Report Preview
                </h3>
                <div class="preview-actions">
                    <button class="btn-export btn-csv" onclick="exportReport('csv')">
                        <i class="fas fa-file-csv"></i>
                        Export CSV
                    </button>
                    <button class="btn-export btn-excel" onclick="exportReport('excel')">
                        <i class="fas fa-file-excel"></i>
                        Export Excel
                    </button>
                    <button class="btn-export btn-pdf" onclick="exportReport('pdf')">
                        <i class="fas fa-file-pdf"></i>
                        Export PDF
                    </button>
                    <button class="btn-export btn-close" onclick="closePreview()">
                        <i class="fas fa-times"></i>
                        Close
                    </button>
                </div>
            </div>

            <!-- Summary Statistics -->
            <div class="summary-stats" id="summaryStats">
                <!-- Will be populated dynamically -->
            </div>

            <!-- Report Table -->
            <div class="report-table-container" id="reportTableContainer">
                <!-- Will be populated dynamically -->
            </div>
        </div>

    </div>
</div>

<script>
// Global variable to store current report data
let currentReportData = null;
let currentReportType = null;

// Handle Generate Report Form Submission
function handleGenerateReport(event) {
    event.preventDefault();
    event.stopPropagation();
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Validate dates
    const dateFrom = new Date(formData.get('date_from'));
    const dateTo = new Date(formData.get('date_to'));
    
    if (dateFrom > dateTo) {
        showAlert('danger', 'From date cannot be later than To date');
        return false;
    }
    
    // Show loading
    document.getElementById('loadingSpinner').classList.add('active');
    document.getElementById('reportPreview').classList.remove('active');
    
    // Debug: Log form data
    console.log('Generating report with parameters:');
    for (let [key, value] of formData.entries()) {
        console.log(`  ${key}: ${value}`);
    }
    
    // Send AJAX request
    fetch('ajax/generate_report.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        // Check if response is JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            throw new Error('Server did not return JSON. Check server logs.');
        }
        
        return response.json();
    })
    .then(result => {
        // Hide loading
        document.getElementById('loadingSpinner').classList.remove('active');
        
        console.log('Report result:', result);
        
        if (result.success) {
            // Store data globally
            currentReportData = result.data;
            currentReportType = formData.get('report_type');
            
            // Check if data is empty
            if (!result.data || result.data.length === 0) {
                showAlert('warning', 'No data found for the selected criteria');
                return;
            }
            
            // Display report preview
            displayReportPreview(result.data, currentReportType, formData);
            
            showAlert('success', `Report generated successfully! ${result.record_count} records found.`);
        } else {
            showAlert('danger', result.message || 'Failed to generate report');
            if (result.debug) {
                console.error('Debug info:', result.debug);
            }
        }
    })
    .catch(error => {
        document.getElementById('loadingSpinner').classList.remove('active');
        console.error('Report generation error:', error);
        showAlert('danger', 'Error: ' + error.message + '. Check browser console for details.');
    });
    
    return false;
}

// Display Report Preview
function displayReportPreview(data, reportType, formData) {
    if (!data || data.length === 0) {
        showAlert('warning', 'No data found for the selected criteria');
        return;
    }
    
    // Show preview section
    const previewSection = document.getElementById('reportPreview');
    previewSection.classList.add('active');
    
    // Generate summary statistics
    const summaryHTML = generateSummaryHTML(data, reportType);
    document.getElementById('summaryStats').innerHTML = summaryHTML;
    
    // Generate table
    const tableHTML = generateReportTable(data, reportType);
    document.getElementById('reportTableContainer').innerHTML = tableHTML;
    
    // Scroll to preview
    previewSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// Generate Summary Statistics HTML
function generateSummaryHTML(data, reportType) {
    let html = '';
    
    // Total Records
    html += `
        <div class="summary-item">
            <label>Total Records</label>
            <div class="value">${data.length.toLocaleString()}</div>
        </div>
    `;
    
    // Calculate statistics based on report type
    if (reportType === 'daily' || reportType === 'weekly' || reportType === 'monthly') {
        // Calculate total load, average, and faults
        let totalLoad = 0;
        let faultCount = 0;
        let maxLoad = 0;
        
        data.forEach(row => {
            const load = parseFloat(row.load_read || row.load_reading || 0);
            totalLoad += load;
            if (load > maxLoad) maxLoad = load;
            if (row.fault_code && row.fault_code !== '') faultCount++;
        });
        
        const avgLoad = data.length > 0 ? totalLoad / data.length : 0;
        
        html += `
            <div class="summary-item">
                <label>Total Load</label>
                <div class="value">${totalLoad.toFixed(2)} MW</div>
            </div>
            <div class="summary-item">
                <label>Average Load</label>
                <div class="value">${avgLoad.toFixed(2)} MW</div>
            </div>
            <div class="summary-item">
                <label>Peak Load</label>
                <div class="value">${maxLoad.toFixed(2)} MW</div>
            </div>
            <div class="summary-item">
                <label>Total Faults</label>
                <div class="value" style="color: var(--danger-color);">${faultCount}</div>
            </div>
        `;
        
    } else if (reportType === 'feeder') {
        // Feeder performance stats
        let totalAvgLoad = 0;
        let totalFaults = 0;
        
        data.forEach(row => {
            totalAvgLoad += parseFloat(row.avg_load || 0);
            totalFaults += parseInt(row.fault_count || 0);
        });
        
        html += `
            <div class="summary-item">
                <label>Total Feeders</label>
                <div class="value">${data.length}</div>
            </div>
            <div class="summary-item">
                <label>System Avg Load</label>
                <div class="value">${(totalAvgLoad / data.length).toFixed(2)} MW</div>
            </div>
            <div class="summary-item">
                <label>Total Faults</label>
                <div class="value" style="color: var(--danger-color);">${totalFaults}</div>
            </div>
        `;
        
    } else if (reportType === 'fault') {
        // Fault analysis stats
        const uniqueFaultCodes = [...new Set(data.map(row => row.fault_code))].filter(Boolean).length;
        const uniqueFeeders = [...new Set(data.map(row => row.feeder_code))].length;
        
        html += `
            <div class="summary-item">
                <label>Total Faults</label>
                <div class="value" style="color: var(--danger-color);">${data.length}</div>
            </div>
            <div class="summary-item">
                <label>Unique Fault Types</label>
                <div class="value">${uniqueFaultCodes}</div>
            </div>
            <div class="summary-item">
                <label>Affected Feeders</label>
                <div class="value">${uniqueFeeders}</div>
            </div>
        `;
        
    } else if (reportType === 'completion') {
        // Data completion stats
        let totalExpected = 0;
        let totalActual = 0;
        
        data.forEach(row => {
            totalExpected += parseInt(row.expected_entries || 0);
            totalActual += parseInt(row.actual_entries || 0);
        });
        
        const completionRate = totalExpected > 0 ? (totalActual / totalExpected * 100) : 0;
        
        html += `
            <div class="summary-item">
                <label>Expected Entries</label>
                <div class="value">${totalExpected.toLocaleString()}</div>
            </div>
            <div class="summary-item">
                <label>Actual Entries</label>
                <div class="value">${totalActual.toLocaleString()}</div>
            </div>
            <div class="summary-item">
                <label>Completion Rate</label>
                <div class="value" style="color: ${completionRate >= 80 ? 'var(--success-color)' : 'var(--danger-color)'};">
                    ${completionRate.toFixed(1)}%
                </div>
            </div>
        `;
    }
    
    return html;
}

// Generate Report Table HTML
function generateReportTable(data, reportType) {
    let html = '<table class="report-table"><thead><tr>';
    
    // Define headers based on report type
    const headers = getTableHeaders(reportType);
    headers.forEach(header => {
        html += `<th>${header}</th>`;
    });
    html += '</tr></thead><tbody>';
    
    // Add data rows
    data.forEach(row => {
        html += '<tr>';
        html += getTableRow(row, reportType);
        html += '</tr>';
    });
    
    html += '</tbody></table>';
    return html;
}

// Get Table Headers
function getTableHeaders(reportType) {
    switch(reportType) {
        case 'daily':
        case 'weekly':
        case 'monthly':
            return ['Date', 'Feeder', 'Hour', 'Load (MW)', 'Fault Code', 'Status'];
        case 'feeder':
            return ['Feeder', 'Avg Load (MW)', 'Max Load (MW)', 'Min Load (MW)', 'Total Entries', 'Faults'];
        case 'fault':
            return ['Date', 'Feeder', 'Hour', 'Fault Code', 'Remark', 'Duration'];
        case 'completion':
            return ['Date', 'Feeder', 'Expected', 'Actual', 'Completion %'];
        default:
            return ['Data'];
    }
}

// Get Table Row HTML
function getTableRow(row, reportType) {
    let html = '';
    
    switch(reportType) {
        case 'daily':
        case 'weekly':
        case 'monthly':
            const load = parseFloat(row.load_read || row.load_reading || 0);
            const status = load > 0 ? 
                '<span style="color: var(--success-color); font-weight: 600;">✓ Supply</span>' : 
                '<span style="color: var(--danger-color); font-weight: 600;">✗ Fault</span>';
            
            html = `
                <td>${formatDate(row.entry_date)}</td>
                <td>${escapeHtml(row.feeder_name || row.feeder_code)}</td>
                <td>${String(row.entry_hour).padStart(2, '0')}:00</td>
                <td>${load.toFixed(2)}</td>
                <td>${escapeHtml(row.fault_code) || '-'}</td>
                <td>${status}</td>
            `;
            break;
            
        case 'feeder':
            html = `
                <td>${escapeHtml(row.feeder_name)}</td>
                <td>${parseFloat(row.avg_load || 0).toFixed(2)}</td>
                <td>${parseFloat(row.max_load || 0).toFixed(2)}</td>
                <td>${parseFloat(row.min_load || 0).toFixed(2)}</td>
                <td>${row.total_entries || 0}</td>
                <td style="color: ${row.fault_count > 0 ? 'var(--danger-color)' : 'var(--success-color)'}; font-weight: 600;">
                    ${row.fault_count || 0}
                </td>
            `;
            break;
            
        case 'fault':
            html = `
                <td>${formatDate(row.entry_date)}</td>
                <td>${escapeHtml(row.feeder_name)}</td>
                <td>${String(row.entry_hour).padStart(2, '0')}:00</td>
                <td>${escapeHtml(row.fault_code) || '-'}</td>
                <td>${escapeHtml(row.fault_remark) || '-'}</td>
                <td>${row.duration || '-'}</td>
            `;
            break;
            
        case 'completion':
            const completion = row.expected_entries > 0 ? 
                ((row.actual_entries / row.expected_entries) * 100).toFixed(1) : 0;
            html = `
                <td>${formatDate(row.entry_date)}</td>
                <td>${escapeHtml(row.feeder_name)}</td>
                <td>${row.expected_entries}</td>
                <td>${row.actual_entries}</td>
                <td>
                    <span style="color: ${completion >= 80 ? 'var(--success-color)' : 'var(--danger-color)'}; font-weight: 600;">
                        ${completion}%
                    </span>
                </td>
            `;
            break;
    }
    
    return html;
}

// Export Report
function exportReport(format) {
    if (!currentReportData || currentReportData.length === 0) {
        showAlert('danger', 'No data available to export');
        return;
    }
    
    const formData = new FormData(document.getElementById('reportForm'));
    formData.append('export_format', format);
    formData.append('report_data', JSON.stringify(currentReportData));
    
    // Create a temporary form for file download
    const downloadForm = document.createElement('form');
    downloadForm.method = 'POST';
    downloadForm.action = 'ajax/export_report.php';
    downloadForm.style.display = 'none';
    
    // Append all form data
    for (let [key, value] of formData.entries()) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = key;
        input.value = value;
        downloadForm.appendChild(input);
    }
    
    document.body.appendChild(downloadForm);
    downloadForm.submit();
    document.body.removeChild(downloadForm);
    
    showAlert('success', `Report is being exported as ${format.toUpperCase()}...`);
    
    // DO NOT redirect or close preview - keep it visible for user
}

// Close Preview
function closePreview() {
    const preview = document.getElementById('reportPreview');
    preview.classList.remove('active');
    currentReportData = null;
    currentReportType = null;
}

// Reset Form
function resetForm() {
    document.getElementById('reportForm').reset();
    setDefaultDates();
    closePreview();
}

// Quick Report
function quickReport(type) {
    const form = document.getElementById('reportForm');
    form.elements['report_type'].value = type;
    
    const today = new Date();
    let fromDate, toDate = today.toISOString().split('T')[0];
    
    switch(type) {
        case 'daily':
            fromDate = toDate;
            break;
        case 'weekly':
            const weekAgo = new Date(today);
            weekAgo.setDate(weekAgo.getDate() - 7);
            fromDate = weekAgo.toISOString().split('T')[0];
            break;
        case 'monthly':
            const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
            fromDate = monthStart.toISOString().split('T')[0];
            break;
        case 'fault':
            const faultPeriod = new Date(today);
            faultPeriod.setDate(faultPeriod.getDate() - 30);
            fromDate = faultPeriod.toISOString().split('T')[0];
            break;
    }
    
    form.elements['date_from'].value = fromDate;
    form.elements['date_to'].value = toDate;
    form.elements['feeder_filter'].value = 'ALL';
    
    // Trigger form submission
    form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
}

// Set Default Dates
function setDefaultDates() {
    const today = new Date().toISOString().split('T')[0];
    const lastWeek = new Date(Date.now() - 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
    
    document.getElementById('date_from').value = lastWeek;
    document.getElementById('date_to').value = today;
}

// Utility Functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text ? String(text).replace(/[&<>"']/g, m => map[m]) : '';
}

function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
        ${message}
    `;
    alertDiv.style.cssText = 'position: fixed; top: 90px; right: 20px; z-index: 9999; min-width: 300px; animation: slideIn 0.3s ease;';
    
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        alertDiv.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => document.body.removeChild(alertDiv), 300);
    }, 3000);
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    setDefaultDates();
    console.log('Reports page initialized');
});

// Add slide animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }
`;
document.head.appendChild(style);
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
