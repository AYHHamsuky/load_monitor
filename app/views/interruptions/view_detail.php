<?php
/**
 * View Interruption Detail
 * Path: /app/views/interruptions/view_detail.php
 */
require __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/sidebar.php';

$canEdit = ($interruption['user_id'] === $user['payroll_id'] && 
            date('Y-m-d', strtotime($interruption['datetime_out'])) === date('Y-m-d'));
?>

<style>
.main-content {
    margin-left: 260px;
    padding: 22px;
    padding-top: 90px;
    min-height: calc(100vh - 64px);
    background: #f4f6fa;
}

.detail-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 28px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    max-width: 1000px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid #e5e7eb;
}

.page-title {
    font-size: 24px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 12px;
}

.action-buttons {
    display: flex;
    gap: 10px;
}

.btn-edit {
    background: linear-gradient(135deg, #0b3a82 0%, #1e40af 100%);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    display: inline-block;
}

.btn-delete {
    background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
}

.btn-back {
    background: #e5e7eb;
    color: #374151;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    display: inline-block;
}

.status-banner {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    border-left: 4px solid #dc2626;
    padding: 16px 20px;
    border-radius: 10px;
    margin-bottom: 24px;
}

.interruption-id {
    font-size: 20px;
    font-weight: 700;
    color: #dc2626;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 24px;
}

.detail-item {
    background: #f9fafb;
    padding: 16px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.detail-label {
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    text-transform: uppercase;
    margin-bottom: 6px;
}

.detail-value {
    font-size: 16px;
    font-weight: 600;
    color: #0f172a;
}

.detail-value.large {
    font-size: 24px;
    color: #dc2626;
}

.type-badge {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
}

.type-planned { background: #dbeafe; color: #1e40af; }
.type-unplanned { background: #fee2e2; color: #991b1b; }
.type-emergency { background: #fef3c7; color: #92400e; }

.section-title {
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 2px solid #e5e7eb;
}

.text-content {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 16px;
    line-height: 1.6;
    color: #374151;
}

.timeline {
    display: flex;
    align-items: center;
    gap: 20px;
    padding: 20px;
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    border-radius: 10px;
    margin-bottom: 24px;
}

.timeline-item {
    flex: 1;
    text-align: center;
}

.timeline-label {
    font-size: 12px;
    font-weight: 600;
    color: #1e40af;
    margin-bottom: 6px;
}

.timeline-value {
    font-size: 18px;
    font-weight: 700;
    color: #0f172a;
}

.timeline-arrow {
    font-size: 24px;
    color: #60a5fa;
}

.meta-info {
    background: #f3f4f6;
    padding: 16px;
    border-radius: 8px;
    font-size: 13px;
    color: #6b7280;
}

@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        padding: 12px;
        padding-top: 70px;
    }
    
    .detail-grid {
        grid-template-columns: 1fr;
    }
    
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
}
</style>

<div class="main-content">
    <div class="detail-card">
        <div class="page-header">
            <div class="page-title">
                <span>⚡</span> Interruption Details
            </div>
            <div class="action-buttons">
                <a href="index.php?page=interruptions" class="btn-back">← Back to List</a>
                <?php if ($canEdit): ?>
                    <a href="index.php?page=interruptions&action=edit&id=<?= $interruption['id'] ?>" class="btn-edit">
                        ✏️ Edit
                    </a>
                    <button class="btn-delete" onclick="deleteInterruption(<?= $interruption['id'] ?>)">
                        🗑️ Delete
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Status Banner -->
        <div class="status-banner">
            <div class="interruption-id">Interruption #<?= $interruption['id'] ?></div>
            <div style="margin-top: 8px;">
                <span class="type-badge type-<?= strtolower($interruption['interruption_type']) ?>">
                    <?= htmlspecialchars($interruption['interruption_type']) ?>
                </span>
            </div>
        </div>

        <!-- Timeline -->
        <div class="timeline">
            <div class="timeline-item">
                <div class="timeline-label">Interruption Started</div>
                <div class="timeline-value">
                    <?= date('d M Y', strtotime($interruption['datetime_out'])) ?>
                </div>
                <div style="font-size: 14px; color: #60a5fa; margin-top: 4px;">
                    <?= date('H:i', strtotime($interruption['datetime_out'])) ?>
                </div>
            </div>
            
            <div class="timeline-arrow">→</div>
            
            <div class="timeline-item">
                <div class="timeline-label">Duration</div>
                <div class="timeline-value" style="color: #dc2626;">
                    <?= number_format($interruption['duration'], 2) ?> hrs
                </div>
            </div>
            
            <div class="timeline-arrow">→</div>
            
            <div class="timeline-item">
                <div class="timeline-label">Power Restored</div>
                <div class="timeline-value">
                    <?= date('d M Y', strtotime($interruption['datetime_in'])) ?>
                </div>
                <div style="font-size: 14px; color: #60a5fa; margin-top: 4px;">
                    <?= date('H:i', strtotime($interruption['datetime_in'])) ?>
                </div>
            </div>
        </div>

        <!-- Key Metrics -->
        <div class="detail-grid">
            <div class="detail-item">
                <div class="detail-label">33kV Feeder</div>
                <div class="detail-value"><?= htmlspecialchars($interruption['fdr33kv_name']) ?></div>
                <div style="font-size: 12px; color: #6b7280; margin-top: 4px;">
                    Code: <?= htmlspecialchars($interruption['fdr33kv_code']) ?>
                </div>
            </div>
            
            <div class="detail-item">
                <div class="detail-label">Load Loss</div>
                <div class="detail-value large"><?= number_format($interruption['load_loss'], 2) ?> MW</div>
            </div>
            
            <div class="detail-item">
                <div class="detail-label">Weather Condition</div>
                <div class="detail-value">
                    <?= htmlspecialchars($interruption['weather_condition'] ?: 'Not Specified') ?>
                </div>
            </div>
            
            <div class="detail-item">
                <div class="detail-label">Resolution</div>
                <div class="detail-value">
                    <?= htmlspecialchars($interruption['resolution'] ?: 'Not Specified') ?>
                </div>
            </div>
        </div>

        <!-- Reason Section -->
        <div style="margin-bottom: 24px;">
            <div class="section-title">Reason for Interruption</div>
            <div class="text-content">
                <?= nl2br(htmlspecialchars($interruption['reason_for_interruption'])) ?>
            </div>
        </div>

        <!-- Delay Information -->
        <?php if ($interruption['reason_for_delay']): ?>
        <div style="margin-bottom: 24px;">
            <div class="section-title">Delay Information</div>
            <div class="text-content">
                <strong>Reason for Delay:</strong> 
                <?= htmlspecialchars($interruption['reason_for_delay']) ?>
                
                <?php if ($interruption['other_reasons']): ?>
                    <div style="margin-top: 8px;">
                        <strong>Additional Details:</strong> 
                        <?= htmlspecialchars($interruption['other_reasons']) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Meta Information -->
        <div class="meta-info">
            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                <div>
                    <strong>Logged By:</strong> <?= htmlspecialchars($interruption['logger_name']) ?>
                </div>
                <div>
                    <strong>Logged At:</strong> <?= date('d M Y H:i', strtotime($interruption['timestamp'])) ?>
                </div>
            </div>
            
            <?php if (!$canEdit): ?>
                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #d1d5db; color: #991b1b;">
                    ⚠️ Editing is only available to the original logger on the same day.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function deleteInterruption(id) {
    if (!confirm('🗑️ Are you sure you want to delete this interruption record? This action cannot be undone.')) {
        return;
    }
    
    fetch('ajax/interruption_delete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ interruption_id: id })
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert('✅ ' + res.message);
            window.location.href = 'index.php?page=interruptions';
        } else {
            alert('❌ ' + res.message);
        }
    })
    .catch(err => {
        alert('❌ Error: ' + err.message);
    });
}
</script>
