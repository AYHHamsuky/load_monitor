<?php
/**
 * Analyst Review View - Interruption Approvals
 * Path: /app/views/interruption_approvals/manager_review.php
 * For: UL3
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

.page-title {
    font-size: 24px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.review-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 24px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    margin-bottom: 20px;
}

.info-banner {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    border-left: 4px solid #3b82f6;
    padding: 14px 18px;
    border-radius: 8px;
    margin-bottom: 24px;
    font-size: 13px;
    color: #1e40af;
}

.approval-item {
    background: #ffffff;
    border: 1.5px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
    transition: all 0.2s ease;
}

.approval-item:hover {
    border-color: #0b3a82;
    box-shadow: 0 4px 12px rgba(11,58,130,0.1);
}

.approval-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 1px solid #e5e7eb;
}

.approval-id {
    font-size: 16px;
    font-weight: 700;
    color: #0b3a82;
}

.type-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    margin-left: 8px;
}

.type-badge.kv11 {
    background: #dcfce7;
    color: #166534;
}

.type-badge.kv33 {
    background: #fee2e2;
    color: #991b1b;
}

.requester-info {
    text-align: right;
}

.requester-name {
    font-weight: 600;
    color: #374151;
    font-size: 14px;
}

.request-date {
    font-size: 12px;
    color: #6b7280;
    margin-top: 2px;
}

.approval-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 16px;
}

.detail-group {
    background: #f9fafb;
    padding: 12px;
    border-radius: 8px;
}

.detail-label {
    font-size: 11px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    margin-bottom: 6px;
    letter-spacing: 0.5px;
}

.detail-value {
    font-weight: 600;
    color: #0f172a;
    font-size: 14px;
}

.reason-section {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 8px;
    padding: 14px;
    margin-bottom: 16px;
}

.reason-label {
    font-size: 12px;
    font-weight: 600;
    color: #92400e;
    margin-bottom: 6px;
}

.reason-text {
    color: #451a03;
    font-size: 13px;
    line-height: 1.5;
}

.action-section {
    background: #f9fafb;
    border-radius: 8px;
    padding: 16px;
}

.remarks-input {
    width: 100%;
    padding: 10px 14px;
    border: 1.5px solid #d1d5db;
    border-radius: 8px;
    font-size: 14px;
    margin-bottom: 12px;
    resize: vertical;
    min-height: 80px;
    font-family: inherit;
}

.remarks-input:focus {
    outline: none;
    border-color: #0b3a82;
    box-shadow: 0 0 0 3px rgba(11,58,130,0.1);
}

.action-buttons {
    display: flex;
    gap: 10px;
}

.btn-approve {
    background: linear-gradient(135deg, #059669 0%, #047857 100%);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s ease;
    flex: 1;
}

.btn-approve:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(5,150,105,0.3);
}

.btn-reject {
    background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.2s ease;
    flex: 1;
}

.btn-reject:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(220,38,38,0.3);
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none !important;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-icon {
    font-size: 64px;
    margin-bottom: 16px;
    opacity: 0.5;
}

.empty-title {
    font-size: 18px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.empty-text {
    color: #6b7280;
}

@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        padding: 12px;
    }
    
    .approval-details {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="main-content">
    <h1 class="page-title">
        <span>🔍</span> Review Interruption Approvals
    </h1>

    <div class="review-card">
        <div class="info-banner">
            <strong>📌 Your Role:</strong> As an Analyst (UL3), you review interruption approval requests submitted by field staff. 
            Approved requests will proceed to Manager (UL4) for final approval.
        </div>

        <?php if (empty($analystApproved)): ?>
            <div class="empty-state">
                <div class="empty-icon">✅</div>
                <div class="empty-title">All Clear!</div>
                <div class="empty-text">No pending interruption approval requests at the moment.</div>
            </div>
        <?php else: ?>
            <?php foreach ($analystApproved as $approval): ?>
            <div class="approval-item" id="approval-<?= $approval['id'] ?>">
                <div class="approval-header">
                    <div>
                        <div class="approval-id">
                            Request #<?= $approval['id'] ?>
                            <span class="type-badge <?= $approval['interruption_type'] === '11kV' ? 'kv11' : 'kv33' ?>">
                                <?= htmlspecialchars($approval['interruption_type']) ?>
                            </span>
                        </div>
                    </div>
                    <div class="requester-info">
                        <div class="requester-name">👤 <?= htmlspecialchars($approval['requester_name']) ?></div>
                        <div class="request-date">
                            📅 <?= date('d M Y, H:i', strtotime($approval['requested_at'])) ?>
                        </div>
                    </div>
                </div>

                <div class="approval-details">
                    <div class="detail-group">
                        <div class="detail-label">Feeder</div>
                        <div class="detail-value"><?= htmlspecialchars($approval['feeder_name']) ?></div>
                    </div>

                    <div class="detail-group">
                        <div class="detail-label">Interruption Code</div>
                        <div class="detail-value"><?= htmlspecialchars($approval['interruption_code']) ?></div>
                    </div>

                    <div class="detail-group">
                        <div class="detail-label">Date/Time Out</div>
                        <div class="detail-value">
                            <?= date('d M Y, H:i', strtotime($approval['datetime_out'])) ?>
                        </div>
                    </div>

                    <div class="detail-group">
                        <div class="detail-label">Date/Time In</div>
                        <div class="detail-value">
                            <?= date('d M Y, H:i', strtotime($approval['datetime_in'])) ?>
                        </div>
                    </div>

                    <div class="detail-group">
                        <div class="detail-label">Load Loss</div>
                        <div class="detail-value"><?= number_format($approval['load_loss'], 2) ?> MW</div>
                    </div>
                </div>

                <div class="reason-section">
                    <div class="reason-label">📝 Reason for Interruption</div>
                    <div class="reason-text"><?= htmlspecialchars($approval['reason_for_interruption']) ?></div>
                </div>

                <div class="action-section">
                    <textarea 
                        class="remarks-input" 
                        id="remarks-<?= $approval['id'] ?>" 
                        placeholder="Enter your remarks (optional)..."></textarea>
                    
                    <div class="action-buttons">
                        <button 
                            class="btn-approve" 
                            onclick="processApproval(<?= $approval['id'] ?>, 'approve')"
                            id="approve-btn-<?= $approval['id'] ?>">
                            ✅ Concur & Forward to Manager
                        </button>
                        <button 
                            class="btn-reject" 
                            onclick="processApproval(<?= $approval['id'] ?>, 'reject')"
                            id="reject-btn-<?= $approval['id'] ?>">
                            ❌ Reject
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function processApproval(id, action) {
    const remarks = document.getElementById('remarks-' + id).value;
    const approveBtn = document.getElementById('approve-btn-' + id);
    const rejectBtn = document.getElementById('reject-btn-' + id);
    
    if (action === 'reject' && !remarks.trim()) {
        alert('⚠️ Please provide remarks when rejecting a request.');
        return;
    }
    
    if (!confirm(`Are you sure you want to ${action} this interruption approval request?`)) {
        return;
    }
    
    approveBtn.disabled = true;
    rejectBtn.disabled = true;
    approveBtn.textContent = 'Processing...';
    rejectBtn.textContent = 'Processing...';
    
    const formData = new FormData();
    formData.append('approval_id', id);
    formData.append('action', action);
    formData.append('remarks', remarks);
    
    fetch('ajax/interruption_manager_action.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert('✅ ' + res.message);
            document.getElementById('approval-' + id).remove();
            
            if (document.querySelectorAll('.approval-item').length === 0) {
                location.reload();
            }
        } else {
            alert('❌ ' + res.message);
            approveBtn.disabled = false;
            rejectBtn.disabled = false;
            approveBtn.textContent = '✅ Concur & Forward to Manager';
            rejectBtn.textContent = '❌ Reject';
        }
    })
    .catch(err => {
        alert('❌ Network error: ' + err.message);
        approveBtn.disabled = false;
        rejectBtn.disabled = false;
        approveBtn.textContent = '✅ Concur & Forward to Manager';
        rejectBtn.textContent = '❌ Reject';
    });
}
</script>

<?php require __DIR__ . '/../layout/footer.php'; ?>

