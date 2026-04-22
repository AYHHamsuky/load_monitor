<?php
/**
 * Manager Final Review View
 * Path: /app/views/corrections/manager_review.php
 * For: UL5, UL6
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
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-left: 4px solid #f59e0b;
    padding: 14px 18px;
    border-radius: 8px;
    margin-bottom: 24px;
    font-size: 13px;
    color: #92400e;
}

.correction-item {
    background: #ffffff;
    border: 2px solid #0b3a82;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 16px;
    transition: all 0.2s ease;
}

.correction-item:hover {
    box-shadow: 0 6px 16px rgba(11,58,130,0.15);
}

.correction-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
    padding-bottom: 16px;
    border-bottom: 2px solid #e5e7eb;
}

.correction-id {
    font-size: 18px;
    font-weight: 700;
    color: #0b3a82;
}

.approval-badge {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #1e40af;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-transform: uppercase;
    border: 1px solid #93c5fd;
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

.correction-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 16px;
}

.detail-group {
    background: #f9fafb;
    padding: 12px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
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

.value-comparison {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 4px;
}

.old-value {
    color: #dc2626;
    text-decoration: line-through;
    font-size: 13px;
}

.arrow {
    color: #6b7280;
}

.new-value {
    color: #059669;
    font-weight: 700;
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

.analyst-review-box {
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    border: 1px solid #93c5fd;
    border-radius: 8px;
    padding: 14px;
    margin-bottom: 16px;
}

.analyst-header {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: #1e40af;
    margin-bottom: 8px;
}

.analyst-remarks {
    color: #1e3a8a;
    font-size: 13px;
    line-height: 1.5;
    margin-top: 6px;
}

.analyst-date {
    font-size: 11px;
    color: #60a5fa;
    margin-top: 6px;
}

.action-section {
    background: #f9fafb;
    border-radius: 8px;
    padding: 16px;
    border: 1px solid #e5e7eb;
}

.action-title {
    font-weight: 600;
    color: #374151;
    margin-bottom: 12px;
    font-size: 14px;
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
    padding: 11px 24px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 700;
    font-size: 14px;
    transition: all 0.2s ease;
    flex: 1;
}

.btn-approve:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(5,150,105,0.4);
}

.btn-reject {
    background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
    color: white;
    border: none;
    padding: 11px 24px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 700;
    font-size: 14px;
    transition: all 0.2s ease;
    flex: 1;
}

.btn-reject:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(220,38,38,0.4);
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none !important;
}

.warning-box {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border: 1px solid #fcd34d;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 12px;
    font-size: 13px;
    color: #92400e;
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
    
    .correction-details {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="main-content">
    <h1 class="page-title">
        <span>✅</span> Final Approval - Manager Review
    </h1>

    <div class="review-card">
        <div class="info-banner">
            <strong>⚠️ Final Authority:</strong> As a Manager, your approval will <strong>immediately apply</strong> the correction to the database. 
            Please review carefully before approving.
        </div>

        <?php if (empty($analystApproved)): ?>
            <div class="empty-state">
                <div class="empty-icon">✅</div>
                <div class="empty-title">All Clear!</div>
                <div class="empty-text">No corrections awaiting final approval.</div>
            </div>
        <?php else: ?>
            <?php foreach ($analystApproved as $correction): ?>
            <div class="correction-item" id="correction-<?= $correction['id'] ?>">
                <div class="correction-header">
                    <div>
                        <div class="correction-id">Request #<?= $correction['id'] ?></div>
                    </div>
                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 8px;">
                        <span class="approval-badge">Analyst Approved</span>
                        <div class="requester-info">
                            <div class="requester-name">👤 <?= htmlspecialchars($correction['requester_name']) ?></div>
                            <div class="request-date">
                                📅 <?= date('d M Y, H:i', strtotime($correction['requested_at'])) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="correction-details">
                    <div class="detail-group">
                        <div class="detail-label">Feeder</div>
                        <div class="detail-value"><?= htmlspecialchars($correction['feeder_name']) ?></div>
                        <div style="font-size: 11px; color: #6b7280; margin-top: 4px;">
                            <?= $correction['correction_type'] ?>
                        </div>
                    </div>

                    <div class="detail-group">
                        <div class="detail-label">Date & Hour</div>
                        <div class="detail-value">
                            <?= date('d M Y', strtotime($correction['entry_date'])) ?>
                        </div>
                        <div style="font-size: 13px; color: #6b7280; margin-top: 4px;">
                            Hour: <?= sprintf('%02d:00', $correction['entry_hour']) ?>
                        </div>
                    </div>

                    <div class="detail-group">
                        <div class="detail-label">Field to Correct</div>
                        <div class="detail-value">
                            <?= ucwords(str_replace('_', ' ', $correction['field_to_correct'])) ?>
                        </div>
                    </div>
                </div>

                <div class="detail-group" style="margin-bottom: 16px;">
                    <div class="detail-label">Value Change</div>
                    <div class="value-comparison">
                        <span class="old-value"><?= htmlspecialchars($correction['old_value'] ?: 'None') ?></span>
                        <span class="arrow">→</span>
                        <span class="new-value"><?= htmlspecialchars($correction['new_value']) ?></span>
                    </div>
                </div>

                <?php if (!empty($correction['blank_hour_reason'])): ?>
                <div style="background:linear-gradient(135deg,#fff7ed 0%,#ffedd5 100%);
                            border:2px solid #f97316;
                            border-radius:10px;
                            padding:14px 18px;
                            margin-bottom:16px;">
                    <div style="display:flex;align-items:center;gap:8px;font-weight:700;
                                color:#c2410c;margin-bottom:8px;font-size:13px;letter-spacing:0.3px;">
                        ⚠️ BLANK HOUR SUBMISSION — No prior entry existed
                    </div>
                    <div style="font-size:12px;font-weight:600;color:#9a3412;margin-bottom:4px;">
                        Requester's explanation for the blank hour:
                    </div>
                    <div style="color:#7c2d12;font-size:13px;line-height:1.5;">
                        <?= htmlspecialchars($correction['blank_hour_reason']) ?>
                    </div>
                    <div style="font-size:11px;color:#ea580c;margin-top:8px;font-weight:600;">
                        ⚠️ Manager: approving this will attempt to update a row that may not exist.
                        Verify that the entry has been created before approving, or reject and follow up.
                    </div>
                </div>
                <?php endif; ?>
                <div class="reason-section">
                    <div class="reason-label">📝 Requester's Reason</div>
                    <div class="reason-text"><?= htmlspecialchars($correction['reason']) ?></div>
                </div>

                <!-- Analyst Review -->
                <?php if (!empty($correction['analyst_id'])): ?>
                <div class="analyst-review-box">
                    <div class="analyst-header">
                        <span>👨‍💼</span>
                        <span>Analyst Review</span>
                    </div>
                    <div style="font-weight: 600; color: #1e40af;">
                        Reviewed by: <?= htmlspecialchars($correction['analyst_id']) ?>
                    </div>
                    <?php if ($correction['analyst_remarks']): ?>
                        <div class="analyst-remarks">
                            <strong>Remarks:</strong> <?= htmlspecialchars($correction['analyst_remarks']) ?>
                        </div>
                    <?php else: ?>
                        <div class="analyst-remarks" style="font-style: italic; color: #60a5fa;">
                            No remarks provided
                        </div>
                    <?php endif; ?>
                    <div class="analyst-date">
                        ✓ Approved on <?= date('d M Y, H:i', strtotime($correction['analyst_action_at'])) ?>
                    </div>
                </div>
                <?php endif; ?>

                <div class="action-section">
                    <div class="action-title">👔 Manager Decision</div>
                    
                    <div class="warning-box">
                        ⚠️ <strong>Important:</strong> Approving this request will immediately update the database.
                    </div>
                    
                    <textarea 
                        class="remarks-input" 
                        id="remarks-<?= $correction['id'] ?>" 
                        placeholder="Enter your final remarks (optional)..."></textarea>
                    
                    <div class="action-buttons">
                        <button 
                            class="btn-approve" 
                            onclick="processFinalApproval(<?= $correction['id'] ?>, 'approve')"
                            id="approve-btn-<?= $correction['id'] ?>">
                            ✅ Final Approve & Apply
                        </button>
                        <button 
                            class="btn-reject" 
                            onclick="processFinalApproval(<?= $correction['id'] ?>, 'reject')"
                            id="reject-btn-<?= $correction['id'] ?>">
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
function processFinalApproval(id, action) {
    const remarks = document.getElementById('remarks-' + id).value;
    const approveBtn = document.getElementById('approve-btn-' + id);
    const rejectBtn = document.getElementById('reject-btn-' + id);
    
    if (action === 'reject' && !remarks.trim()) {
        alert('⚠️ Please provide remarks when rejecting a request.');
        return;
    }
    
    const confirmMsg = action === 'approve'
        ? '🔴 FINAL APPROVAL: This will IMMEDIATELY apply the correction to the database. Are you sure?'
        : 'Are you sure you want to reject this correction request?';
    
    if (!confirm(confirmMsg)) {
        return;
    }
    
    // Disable buttons
    approveBtn.disabled = true;
    rejectBtn.disabled = true;
    approveBtn.textContent = 'Processing...';
    rejectBtn.textContent = 'Processing...';
    
    const formData = new FormData();
    formData.append('correction_id', id);
    formData.append('action', action);
    formData.append('remarks', remarks);
    
    fetch('ajax/manager_action.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert('✅ ' + res.message);
            // Remove the correction item from view
            document.getElementById('correction-' + id).remove();
            
            // Check if all items are processed
            if (document.querySelectorAll('.correction-item').length === 0) {
                location.reload();
            }
        } else {
            alert('❌ ' + res.message);
            approveBtn.disabled = false;
            rejectBtn.disabled = false;
            approveBtn.textContent = '✅ Final Approve & Apply';
            rejectBtn.textContent = '❌ Reject';
        }
    })
    .catch(err => {
        alert('❌ Network error: ' + err.message);
        approveBtn.disabled = false;
        rejectBtn.disabled = false;
        approveBtn.textContent = '✅ Final Approve & Apply';
        rejectBtn.textContent = '❌ Reject';
    });
}
</script>
