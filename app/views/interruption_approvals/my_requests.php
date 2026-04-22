<?php
/**
 * My Correction Requests View
 * Path: /app/views/corrections/my_requests.php
 * For: UL1, UL2
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

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
}

.page-title {
    font-size: 24px;
    font-weight: 700;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 12px;
}

.btn-new-request {
    background: linear-gradient(135deg, #0b3a82 0%, #1e40af 100%);
    color: #fff;
    border: none;
    padding: 11px 24px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    text-decoration: none;
    display: inline-block;
    transition: all 0.2s ease;
}

.btn-new-request:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(11,58,130,0.3);
}

.requests-card {
    background: #ffffff;
    border-radius: 14px;
    padding: 24px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}

.status-badge {
    display: inline-block;
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}

.status-pending {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    color: #92400e;
    border: 1px solid #fcd34d;
}

.status-analyst-approved {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #1e40af;
    border: 1px solid #93c5fd;
}

.status-manager-approved {
    background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
    color: #166534;
    border: 1px solid #86efac;
}

.status-rejected {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #991b1b;
    border: 1px solid #fca5a5;
}

.requests-table-wrapper {
    overflow-x: auto;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.requests-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.requests-table thead th {
    background: linear-gradient(135deg, #0b3a82 0%, #1e40af 100%);
    color: #ffffff;
    padding: 12px 14px;
    text-align: left;
    font-weight: 600;
    border-right: 1px solid rgba(255,255,255,0.1);
    white-space: nowrap;
}

.requests-table tbody tr {
    transition: background-color 0.15s ease;
}

.requests-table tbody tr:nth-child(even) {
    background-color: #fafbfc;
}

.requests-table tbody tr:hover {
    background-color: #f3f4f6;
}

.requests-table td {
    padding: 14px;
    border-bottom: 1px solid #e5e7eb;
    vertical-align: top;
}

.field-label {
    font-size: 11px;
    color: #6b7280;
    font-weight: 600;
    text-transform: uppercase;
    margin-bottom: 4px;
}

.field-value {
    font-weight: 600;
    color: #0f172a;
}

.value-change {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
}

.old-value {
    color: #dc2626;
    text-decoration: line-through;
}

.new-value {
    color: #059669;
    font-weight: 700;
}

.remarks-box {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 10px;
    margin-top: 8px;
    font-size: 12px;
    color: #374151;
}

.remarks-label {
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 4px;
}

.no-data {
    text-align: center;
    padding: 40px 20px;
    color: #6b7280;
    font-style: italic;
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
    margin-bottom: 24px;
}

@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        padding: 12px;
    }
    
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
    }
}
.status-badge {
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending {
    background: #fef3c7;
    color: #92400e;
}

.status-analyst-approved {
    background: #dbeafe;
    color: #1e40af;
}

.status-approved {
    background: #dcfce7;
    color: #166534;
}

.status-rejected {
    background: #fee2e2;
    color: #991b1b;
}


</style>

<div class="main-content">
    <div class="page-header">
        <h1 class="page-title">
            <span>📋</span> My Interruption Requests
        </h1>
    </div>

    <div class="requests-card">
        <?php if (empty($myRequests)): ?>
            <div class="empty-state">
                <div class="empty-icon">📝</div>
                <div class="empty-title">No Interruption Requests Yet</div>
                <div class="empty-text">You have not submitted any interruption approval requests.</div>
            </div>
        <?php else: ?>
            <div class="requests-table-wrapper">
                <table class="requests-table">
                    <thead>
                        <tr>
                            <th>Request ID</th>
                            <th>Interruption Details</th>
                            <th>Date / Time</th>
                            <th>Load Loss</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Submitted On</th>
                            <th>Review Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($myRequests as $req): ?>
                        <tr>
                            <td>
                                <span class="field-value">#<?= $req['id'] ?></span>
                            </td>

                            <td>
                                <div class="field-label">Feeder</div>
                                <div class="field-value"><?= htmlspecialchars($req['feeder_name']) ?></div>

                                <div style="font-size: 11px; color: #6b7280; margin-top: 4px;">
                                    Code: <?= htmlspecialchars($req['interruption_code']) ?>
                                </div>

                                <div style="margin-top: 4px;">
                                    <span class="type-badge <?= $req['interruption_type'] === '11kV' ? 'kv11' : 'kv33' ?>">
                                        <?= htmlspecialchars($req['interruption_type']) ?>
                                    </span>
                                </div>
                            </td>

                            <td>
                                <div class="field-label">Out</div>
                                <div>
                                    <?= !empty($req['datetime_out'])
                                        ? date('d M Y, H:i', strtotime($req['datetime_out']))
                                        : '-' ?>
                                </div>

                                <div class="field-label" style="margin-top: 6px;">In</div>
                                <div>
                                    <?= !empty($req['datetime_in'])
                                        ? date('d M Y, H:i', strtotime($req['datetime_in']))
                                        : '<span style="color:#9ca3af;">Pending</span>' ?>
                                </div>
                            </td>


                            <td>
                                <div class="field-value">
                                    <?= number_format((float)($req['load_loss'] ?? 0), 2) ?> MW
                                </div>
                            </td>

                            <td>
                                <div style="max-width:220px;font-size:12px;">
                                    <?= !empty($req['reason_for_interruption'])
                                        ? htmlspecialchars($req['reason_for_interruption'])
                                        : '<span style="color:#9ca3af;">Not provided</span>' ?>
                                </div>

                            </td>

                            <td>
                                <?php
                                    $statusMap = [
                                        'PENDING'           => 'status-pending',
                                        'ANALYST_APPROVED'  => 'status-analyst-approved',
                                        'APPROVED'          => 'status-approved',
                                        'REJECTED'          => 'status-rejected',
                                    ];
                                    $statusClass = $statusMap[$req['status']] ?? 'status-pending';
                                ?>
                                <span class="status-badge <?= $statusClass ?>">
                                    <?= str_replace('_', ' ', $req['status']) ?>
                                </span>
                            </td>

                            <td>
                                <div style="font-size: 12px;">
                                    <?= date('d M Y', strtotime($req['requested_at'])) ?>
                                </div>
                                <div style="font-size: 11px; color: #6b7280;">
                                    <?= date('H:i', strtotime($req['requested_at'])) ?>
                                </div>
                            </td>

                            <td>
                                <?php if ($req['status'] !== 'PENDING'): ?>

                                    <?php if (!empty($req['analyst_name'])): ?>
                                    <div class="remarks-box">
                                        <div class="remarks-label">
                                            👨‍💼 Analyst: <?= htmlspecialchars($req['analyst_name']) ?>
                                        </div>

                                        <?php if (!empty($req['analyst_remarks'])): ?>
                                            <div style="margin-top: 4px;">
                                                <?= htmlspecialchars($req['analyst_remarks']) ?>
                                            </div>
                                        <?php endif; ?>

                                        <div style="font-size: 10px; color: #9ca3af; margin-top: 4px;">
                                            <?= date('d M Y H:i', strtotime($req['analyst_action_at'])) ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (!empty($req['manager_name'])): ?>
                                    <div class="remarks-box" style="margin-top: 8px;">
                                        <div class="remarks-label">
                                            👔 Manager: <?= htmlspecialchars($req['manager_name']) ?>
                                        </div>

                                        <?php if (!empty($req['manager_remarks'])): ?>
                                            <div style="margin-top: 4px;">
                                                <?= htmlspecialchars($req['manager_remarks']) ?>
                                            </div>
                                        <?php endif; ?>

                                        <div style="font-size: 10px; color: #9ca3af; margin-top: 4px;">
                                            <?= date('d M Y H:i', strtotime($req['manager_action_at'])) ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                <?php else: ?>
                                    <span style="color: #9ca3af; font-size: 12px; font-style: italic;">
                                        Awaiting analyst review
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
