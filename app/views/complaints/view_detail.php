<?php require __DIR__ . '/../layout/header.php'; ?>
<?php require __DIR__ . '/../layout/sidebar.php'; ?>

<div class="main-content">
    <div class="complaint-detail-container">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>📢 Complaint Details #<?= $complaint['complaint_ref'] ?></h1>
                <p class="subtitle">View and manage complaint</p>
            </div>
            <a href="?page=complaints" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to List
            </a>
        </div>

        <div class="detail-grid">
            <!-- Complaint Information -->
            <div class="info-card">
                <h3><i class="fas fa-info-circle"></i> Complaint Information</h3>
                <div class="info-rows">
                    <div class="info-row">
                        <span class="label">Complaint ID:</span>
                        <span class="value"><strong>#<?= $complaint['complaint_ref'] ?></strong></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Date Logged:</span>
                        <span class="value"><?= date('F j, Y H:i', strtotime($complaint['logged_at'])) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Type:</span>
                        <span class="value"><?= htmlspecialchars($complaint['complaint_type']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Priority:</span>
                        <span class="value">
                            <span class="priority-badge priority-<?= strtolower($complaint['priority']) ?>">
                                <?= htmlspecialchars($complaint['priority']) ?>
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="label">Status:</span>
                        <span class="value">
                            <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $complaint['status'])) ?>">
                                <?= htmlspecialchars($complaint['status']) ?>
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="label">Source:</span>
                        <span class="value"><?= htmlspecialchars($complaint['complaint_source']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Customer Information -->
            <div class="info-card">
                <h3><i class="fas fa-user"></i> Customer Information</h3>
                <div class="info-rows">
                    <div class="info-row">
                        <span class="label">Name:</span>
                        <span class="value"><?= htmlspecialchars($complaint['customer_name']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Phone:</span>
                        <span class="value"><?= htmlspecialchars($complaint['customer_phone']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Address:</span>
                        <span class="value"><?= htmlspecialchars($complaint['fault_location']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Account Number:</span>
                        <span class="value"><?= htmlspecialchars($complaint['account_number'] ?? 'N/A') ?></span>
                    </div>
                </div>
            </div>

            <!-- Assignment Information -->
            <div class="info-card">
                <h3><i class="fas fa-tasks"></i> Assignment Information</h3>
                <div class="info-rows">
                    <div class="info-row">
                        <span class="label">Logged By:</span>
                        <span class="value"><?= htmlspecialchars($complaint['logged_by_name']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="label">Assigned To:</span>
                        <span class="value"><?= $complaint['assigned_to_name'] ? htmlspecialchars($complaint['assigned_to_name']) : '<em>Not Assigned</em>' ?></span>
                    </div>
                    <?php if ($complaint['resolved_at']): ?>
                    <div class="info-row">
                        <span class="label">Resolved Date:</span>
                        <span class="value"><?= date('F j, Y H:i', strtotime($complaint['resolved_at'])) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Description -->
            <div class="info-card full-width">
                <h3><i class="fas fa-align-left"></i> Description</h3>
                <p class="complaint_details"><?= nl2br(htmlspecialchars($complaint['complaint_details'])) ?></p>
            </div>

            <?php if ($complaint['resolution_details']): ?>
            <!-- Resolution Notes -->
            <div class="info-card full-width">
                <h3><i class="fas fa-check-circle"></i> Resolution Notes</h3>
                <p class="complaint_details"><?= nl2br(htmlspecialchars($complaint['resolution_details'])) ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Assignment Actions (UL3 Only) -->
        <?php if ($can_assign && $complaint['status'] === 'Pending'): ?>
        <div class="action-card">
            <h3><i class="fas fa-user-plus"></i> Assign Complaint</h3>
            <form id="assignForm">
                <input type="hidden" name="complaint_id" value="<?= $complaint['complaint_id'] ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="assigned_to">Assign To Staff *</label>
                        <select name="assigned_to" id="assigned_to" class="form-control" required>
                            <option value="">-- Select Staff Member --</option>
                            <?php foreach ($available_staff as $staff): ?>
                                <option value="<?= htmlspecialchars($staff['payroll_id']) ?>">
                                    <?= htmlspecialchars($staff['staff_name']) ?> (<?= $staff['role'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="assignment_notes">Assignment Notes</label>
                        <textarea name="assignment_notes" id="assignment_notes" 
                                  class="form-control" rows="3" 
                                  placeholder="Add any instructions or notes..."></textarea>
                    </div>
                </div>
                
                <button type="submit" class="btn-primary">
                    <i class="fas fa-check"></i> Assign Complaint
                </button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Approval Actions (UL4 Only) -->
        <?php if ($can_approve && $complaint['status'] === 'Assigned'): ?>
        <div class="action-card">
            <h3><i class="fas fa-check-double"></i> Approve Assignment</h3>
            <p>Complaint assigned to: <strong><?= htmlspecialchars($complaint['assigned_to_name']) ?></strong></p>
            <div class="action-buttons">
                <button onclick="approveAssignment(<?= $complaint['complaint_id'] ?>)" class="btn-success">
                    <i class="fas fa-check"></i> Approve Assignment
                </button>
                <button onclick="rejectAssignment(<?= $complaint['complaint_id'] ?>)" class="btn-danger">
                    <i class="fas fa-times"></i> Reject & Reassign
                </button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.main-content {
    margin-left: 260px;
    margin-top: 70px;
    padding: 0;
    min-height: calc(100vh - 70px);
}

.complaint-detail-container {
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

.detail-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 25px;
}

.info-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.info-card.full-width {
    grid-column: 1 / -1;
}

.info-card h3 {
    margin: 0 0 20px 0;
    color: #2c3e50;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    padding-bottom: 15px;
    border-bottom: 2px solid #f0f0f0;
}

.info-rows {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #f8f9fa;
}

.info-row:last-child {
    border-bottom: none;
}

.info-row .label {
    font-weight: 600;
    color: #6c757d;
    font-size: 14px;
}

.info-row .value {
    color: #2c3e50;
    font-size: 14px;
}

.description {
    color: #495057;
    line-height: 1.6;
    margin: 0;
}

.priority-badge, .status-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
}

.priority-badge.priority-low { background: #d1ecf1; color: #0c5460; }
.priority-badge.priority-medium { background: #fff3cd; color: #856404; }
.priority-badge.priority-high { background: #f8d7da; color: #721c24; }
.priority-badge.priority-critical { background: #dc3545; color: white; }

.status-badge.status-pending { background: #fff3cd; color: #856404; }
.status-badge.status-assigned { background: #d1ecf1; color: #0c5460; }
.status-badge.status-in-progress { background: #d6d8db; color: #1b1e21; }
.status-badge.status-resolved { background: #d4edda; color: #155724; }
.status-badge.status-closed { background: #e2e3e5; color: #383d41; }

.action-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.action-card h3 {
    margin: 0 0 20px 0;
    color: #2c3e50;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #495057;
}

.form-control {
    width: 100%;
    padding: 10px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
}

.btn-primary, .btn-success, .btn-danger {
    padding: 12px 24px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: white;
}

.btn-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
}

.btn-success {
    background: linear-gradient(135deg, #28a745 0%, #218838 100%);
}

.btn-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
}

.action-buttons {
    display: flex;
    gap: 15px;
    margin-top: 20px;
}

@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
    }
    
    .detail-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Assign complaint form
document.getElementById('assignForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('/public/ajax/complaint_assign.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.href = '?page=complaints';
        } else {
            alert('Error: ' + data.message);
        }
    });
});

// Approve assignment
function approveAssignment(complaintId) {
    if (!confirm('Approve this complaint assignment?')) return;
    
    fetch('/public/ajax/complaint_approve.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `complaint_id=${complaintId}&action=approve`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.href = '?page=complaints';
        } else {
            alert('Error: ' + data.message);
        }
    });
}

// Reject assignment
function rejectAssignment(complaintId) {
    if (!confirm('Reject this assignment? The complaint will return to Pending status.')) return;
    
    fetch('/public/ajax/complaint_approve.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `complaint_id=${complaintId}&action=reject`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>
