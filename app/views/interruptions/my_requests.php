<?php
/**
 * My Interruption Requests — 33kV
 * Path: /app/views/interruptions/my_requests.php
 *
 * Shows all interruptions logged by the current UL2 user.
 * Ticket numbers are clickable to open the record.
 */
require __DIR__ . '/../layout/header.php';
require __DIR__ . '/../layout/sidebar.php';
?>

<style>
.main-content{margin-left:260px;padding:22px;padding-top:90px;min-height:calc(100vh - 64px);background:#f4f6fa;}
.page-card{background:#fff;border-radius:14px;padding:28px;box-shadow:0 10px 30px rgba(0,0,0,.08);max-width:1200px;margin:0 auto;}
.page-title{font-size:24px;font-weight:700;color:#0f172a;margin-bottom:4px;}
.page-subtitle{color:#6b7280;font-size:14px;margin-bottom:24px;}

/* Summary chips */
.summary-row{display:flex;gap:14px;margin-bottom:24px;flex-wrap:wrap;}
.chip{background:#f8faff;border:1px solid #e0e7ff;border-radius:10px;padding:14px 20px;min-width:160px;flex:1;}
.chip .chip-val{font-size:28px;font-weight:800;color:#1e40af;}
.chip .chip-label{font-size:12px;color:#6b7280;margin-top:2px;}

/* Table */
.requests-table{width:100%;border-collapse:collapse;font-size:14px;}
.requests-table thead th{
    background:#f8faff;padding:11px 14px;text-align:left;
    font-size:12px;font-weight:700;color:#374151;text-transform:uppercase;
    border-bottom:2px solid #e5e7eb;white-space:nowrap;
}
.requests-table tbody tr{border-bottom:1px solid #f3f4f6;transition:background .15s;}
.requests-table tbody tr:hover{background:#f8faff;}
.requests-table td{padding:12px 14px;vertical-align:middle;}

/* Ticket link */
.ticket-link{
    font-family:monospace;font-size:14px;font-weight:800;color:#1e40af;
    text-decoration:none;letter-spacing:1px;
    padding:4px 10px;background:#eff6ff;border-radius:6px;
    display:inline-block;border:1px solid #bfdbfe;
    transition:all .2s;
}
.ticket-link:hover{background:#dbeafe;border-color:#93c5fd;transform:translateY(-1px);}

/* Status pills */
.status-pill{padding:4px 12px;border-radius:20px;font-size:11px;font-weight:700;white-space:nowrap;}
.s-pending   {background:#fef3c7;color:#92400e;}
.s-approval  {background:#dbeafe;color:#1e40af;}
.s-approved  {background:#d1fae5;color:#065f46;}
.s-completed {background:#dcfce7;color:#166534;}

/* Action buttons */
.btn-action{
    padding:5px 12px;border-radius:6px;font-size:12px;font-weight:600;
    cursor:pointer;border:none;text-decoration:none;display:inline-block;
    white-space:nowrap;
}
.btn-complete-row{background:linear-gradient(135deg,#1e40af,#1d4ed8);color:#fff;}
.btn-complete-row:hover{opacity:.9;}
.btn-view-row{background:#f3f4f6;color:#374151;}

/* Empty state */
.empty-state{text-align:center;padding:60px 20px;color:#9ca3af;}
.empty-state .empty-icon{font-size:48px;margin-bottom:12px;}
.empty-state h3{font-size:18px;font-weight:700;color:#6b7280;margin-bottom:8px;}

/* Log button */
.btn-log{
    background:linear-gradient(135deg,#dc2626,#991b1b);color:#fff;
    border:none;padding:10px 22px;border-radius:8px;cursor:pointer;
    font-weight:600;font-size:14px;text-decoration:none;display:inline-block;
    margin-bottom:20px;
}

@media(max-width:768px){
    .main-content{margin-left:0;padding:12px;padding-top:70px;}
    .requests-table{display:block;overflow-x:auto;}
}
</style>

<div class="main-content">
<div class="page-card">
    <h1 class="page-title">My Interruption Requests</h1>
    <p class="page-subtitle">All 33kV interruptions you have logged. Click a ticket number to view or complete the record.</p>

    <a href="index.php?page=interruptions&action=log" class="btn-log">⚡ Log New Interruption</a>

    <?php
    // Count by status
    $total      = count($myRequests);
    $pending    = count(array_filter($myRequests, fn($r) => $r['form_status'] === 'PENDING_COMPLETION'));
    $awaiting   = count(array_filter($myRequests, fn($r) => $r['form_status'] === 'AWAITING_APPROVAL'));
    $approved   = count(array_filter($myRequests, fn($r) => $r['form_status'] === 'PENDING_COMPLETION_APPROVED'));
    $completed  = count(array_filter($myRequests, fn($r) => $r['form_status'] === 'COMPLETED'));
    ?>

    <div class="summary-row">
        <div class="chip"><div class="chip-val"><?= $total ?></div><div class="chip-label">Total</div></div>
        <div class="chip"><div class="chip-val" style="color:#92400e"><?= $pending ?></div><div class="chip-label">Pending Completion</div></div>
        <div class="chip"><div class="chip-val" style="color:#1e40af"><?= $awaiting ?></div><div class="chip-label">Awaiting Approval</div></div>
        <div class="chip"><div class="chip-val" style="color:#065f46"><?= $approved ?></div><div class="chip-label">Approved — Stage 2 Ready</div></div>
        <div class="chip"><div class="chip-val" style="color:#166534"><?= $completed ?></div><div class="chip-label">Completed</div></div>
    </div>

    <?php if (empty($myRequests)): ?>
    <div class="empty-state">
        <div class="empty-icon">📋</div>
        <h3>No interruptions logged yet</h3>
        <p>Click "Log New Interruption" above to start a new record.</p>
    </div>
    <?php else: ?>
    <table class="requests-table">
        <thead>
            <tr>
                <th>Ticket</th>
                <th>Feeder</th>
                <th>Type / Code</th>
                <th>Date/Time Out</th>
                <th>Date/Time In</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($myRequests as $req): ?>
        <tr>
            <td>
                <a href="index.php?page=interruptions&action=view&ticket=<?= urlencode($req['ticket_number']) ?>"
                   class="ticket-link" title="Click to open record">
                    <?= htmlspecialchars($req['ticket_number']) ?>
                </a>
            </td>
            <td><?= htmlspecialchars($req['fdr33kv_name']) ?></td>
            <td>
                <span style="font-size:12px;color:#6b7280;"><?= htmlspecialchars($req['interruption_type']) ?></span><br>
                <strong><?= htmlspecialchars($req['interruption_code']) ?></strong>
            </td>
            <td><?= date('d M Y<\b\r>H:i', strtotime($req['datetime_out'])) ?></td>
            <td>
                <?= $req['datetime_in']
                    ? date('d M Y<\b\r>H:i', strtotime($req['datetime_in']))
                    : '<span style="color:#9ca3af;font-size:12px;">— pending —</span>' ?>
            </td>
            <td>
                <?php
                    $statusDisplay = [
                        'PENDING_COMPLETION'          => ['s-pending',  '⏳ Pending Completion'],
                        'AWAITING_APPROVAL'           => ['s-approval', '🔄 Awaiting Approval'],
                        'PENDING_COMPLETION_APPROVED' => ['s-approved', '✅ Approved — Complete'],
                        'COMPLETED'                   => ['s-completed','✔ Completed'],
                    ];
                    [$cls, $label] = $statusDisplay[$req['form_status']] ?? ['s-pending', $req['form_status']];
                    echo "<span class=\"status-pill {$cls}\">{$label}</span>";
                ?>
            </td>
            <td>
                <?php if (in_array($req['form_status'], ['PENDING_COMPLETION','PENDING_COMPLETION_APPROVED'])): ?>
                <a href="index.php?page=interruptions&action=complete&id=<?= $req['id'] ?>"
                   class="btn-action btn-complete-row">⚡ Complete</a>
                <?php else: ?>
                <a href="index.php?page=interruptions&action=view&ticket=<?= urlencode($req['ticket_number']) ?>"
                   class="btn-action btn-view-row">View</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

</div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
