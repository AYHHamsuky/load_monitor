<?php require __DIR__ . '/../layout/header.php'; ?>
<?php require __DIR__ . '/../layout/sidebar.php'; ?>
<style>
.main-content {
    margin-left: 260px;
    padding: 22px;
    padding-top: 90px;
    min-height: calc(100vh - 64px);
    background: #f4f6fa;
}
@media(max-width:768px){
    .main-content { margin-left:0; padding-top:70px; }
}
</style>


<?php
// ── Status helpers ────────────────────────────────────────────────────────────
function statusBadge33(string $s): string {
    $map = [
        'PENDING_COMPLETION'          => ['Pending Completion','#f39c12','#fff8e1'],
        'AWAITING_APPROVAL'           => ['Awaiting Approval', '#8e44ad','#f5eef8'],
        'PENDING_COMPLETION_APPROVED' => ['Approved – Complete','#27ae60','#eafaf1'],
        'COMPLETED'                   => ['Completed',          '#2980b9','#ebf5fb'],
        'CANCELLED'                   => ['Cancelled',          '#e74c3c','#fdedec'],
    ];
    [$label,$color,$bg] = $map[$s] ?? [$s,'#7f8c8d','#f2f3f4'];
    return "<span style=\"background:{$bg};color:{$color};padding:3px 10px;border-radius:20px;
            font-size:11px;font-weight:700;white-space:nowrap;border:1px solid {$color}40;\">{$label}</span>";
}
function typeBadge33(string $t): string {
    $c = $t === 'FORCED' ? '#e74c3c' : ($t === 'PLANNED' ? '#2980b9' : '#7f8c8d');
    return "<span style=\"background:{$c}15;color:{$c};padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600;\">{$t}</span>";
}
$stats          = $stats ?? [];
$interruptions  = $interruptions ?? [];
$typeBreakdown  = $typeBreakdown ?? [];
$feeders_33kv   = $feeders_33kv ?? [];
$filterDateFrom = $filterDateFrom ?? date('Y-m-d');
$filterDateTo   = $filterDateTo   ?? date('Y-m-d');
$selectedFeederCode = $selectedFeederCode ?? 'ALL';
$feeder         = $feeder ?? ['fdr33kv_name'=>'All 33kV Feeders','station_name'=>'System-wide'];
?>

<div class="main-content">
<div style="max-width:100%;padding:0;">

    <!-- Page Header -->
    <div style="background:white;padding:22px 28px;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.08);
                margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;">
        <div>
            <h1 style="font-size:24px;color:#2c3e50;margin-bottom:4px;">⚡ 33kV Interruption Records</h1>
            <p style="color:#7f8c8d;font-size:13px;">
                <?= htmlspecialchars($feeder['fdr33kv_name']) ?>
                <?php if (!empty($feeder['station_name']) && $feeder['station_name'] !== 'System-wide'): ?>
                    &nbsp;·&nbsp; <?= htmlspecialchars($feeder['station_name']) ?>
                <?php endif; ?>
                &nbsp;·&nbsp; <?= date('d M Y', strtotime($filterDateFrom)) ?>
                <?php if ($filterDateFrom !== $filterDateTo): ?>
                    &nbsp;→&nbsp; <?= date('d M Y', strtotime($filterDateTo)) ?>
                <?php endif; ?>
            </p>
        </div>
        <a href="index.php?page=interruptions&action=log"
           style="background:linear-gradient(135deg,#667eea,#764ba2);color:white;text-decoration:none;
                  padding:11px 22px;border-radius:8px;font-size:13px;font-weight:600;display:inline-flex;align-items:center;gap:7px;">
            <i class="fas fa-plus"></i> Log Interruption
        </a>
    </div>

    <!-- Filters -->
    <div style="background:white;padding:18px 22px;border-radius:10px;box-shadow:0 2px 6px rgba(0,0,0,.06);margin-bottom:22px;">
        <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
            <input type="hidden" name="page"   value="interruptions">
            <input type="hidden" name="action" value="list">
            <div style="flex:1;min-width:200px;">
                <label style="display:block;font-size:12px;font-weight:600;color:#555;margin-bottom:5px;">Feeder</label>
                <select name="feeder" class="form-ctrl">
                    <option value="ALL" <?= $selectedFeederCode==='ALL'?'selected':'' ?>>All 33kV Feeders</option>
                    <?php foreach ($feeders_33kv as $f): ?>
                        <option value="<?= htmlspecialchars($f['fdr33kv_code']) ?>"
                                <?= $selectedFeederCode===$f['fdr33kv_code']?'selected':'' ?>>
                            <?= htmlspecialchars($f['fdr33kv_name']) ?>
                            <?php if (!empty($f['station_name'])): ?>(<?= htmlspecialchars($f['station_name']) ?>)<?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#555;margin-bottom:5px;">Date From</label>
                <input type="date" name="date_from" value="<?= htmlspecialchars($filterDateFrom) ?>" class="form-ctrl">
            </div>
            <div>
                <label style="display:block;font-size:12px;font-weight:600;color:#555;margin-bottom:5px;">Date To</label>
                <input type="date" name="date_to"   value="<?= htmlspecialchars($filterDateTo) ?>"   class="form-ctrl">
            </div>
            <button type="submit" style="background:linear-gradient(135deg,#667eea,#764ba2);color:white;border:none;
                    padding:10px 20px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
                <i class="fas fa-filter"></i> Filter
            </button>
            <a href="index.php?page=interruptions&action=list"
               style="background:#e9ecef;color:#495057;text-decoration:none;padding:10px 18px;
                      border-radius:8px;font-size:13px;font-weight:600;">
                <i class="fas fa-times"></i> Clear
            </a>
        </form>
    </div>

    <!-- Stats Row -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:22px;">
        <?php
        $sc  = [
            ['Total',         $stats['total_interruptions'] ?? 0, 'fas fa-bolt',      '#667eea'],
            ['Load Loss (MW)',number_format((float)($stats['total_load_loss'] ?? 0),2),'fas fa-tachometer-alt','#e74c3c'],
            ['Avg Duration',  round((float)($stats['avg_duration'] ?? 0)).' min','fas fa-clock','#f39c12'],
            ['Max Duration',  round((float)($stats['max_duration'] ?? 0)).' min','fas fa-stopwatch','#8e44ad'],
            ['Delayed Restores', $stats['delayed_restorations'] ?? 0,'fas fa-exclamation-triangle','#c0392b'],
        ];
        foreach ($sc as [$lbl,$val,$icon,$clr]):
        ?>
        <div style="background:white;padding:18px;border-radius:10px;box-shadow:0 2px 6px rgba(0,0,0,.06);
                    display:flex;align-items:center;gap:14px;border-left:4px solid <?= $clr ?>;">
            <div style="width:42px;height:42px;border-radius:10px;background:<?= $clr ?>18;
                        display:flex;align-items:center;justify-content:center;color:<?= $clr ?>;font-size:18px;">
                <i class="<?= $icon ?>"></i>
            </div>
            <div>
                <div style="font-size:20px;font-weight:700;color:#2c3e50;"><?= $val ?></div>
                <div style="font-size:11px;color:#7f8c8d;"><?= $lbl ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Records Table -->
    <div style="background:white;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.08);overflow:hidden;">
        <div style="background:linear-gradient(135deg,#667eea,#764ba2);padding:16px 22px;
                    display:flex;justify-content:space-between;align-items:center;">
            <h3 style="color:white;font-size:16px;font-weight:600;margin:0;">
                📋 Interruption Log &nbsp;<span style="font-size:13px;opacity:.85;">(<?= count($interruptions) ?> records)</span>
            </h3>
            <?php if (!empty($typeBreakdown)): ?>
            <div style="display:flex;gap:10px;">
                <?php foreach ($typeBreakdown as $tb): ?>
                <span style="background:white15;color:white;border:1px solid rgba(255,255,255,.4);
                             padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;">
                    <?= htmlspecialchars($tb['interruption_type']) ?>: <?= $tb['count'] ?>
                </span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if (empty($interruptions)): ?>
        <div style="text-align:center;padding:60px 20px;color:#7f8c8d;">
            <i class="fas fa-inbox fa-3x" style="color:#dee2e6;margin-bottom:16px;display:block;"></i>
            <p style="font-size:16px;">No interruption records found for the selected filters.</p>
            <a href="index.php?page=interruptions&action=log"
               style="display:inline-block;margin-top:14px;background:linear-gradient(135deg,#667eea,#764ba2);
                      color:white;text-decoration:none;padding:10px 22px;border-radius:8px;font-size:13px;font-weight:600;">
                <i class="fas fa-plus"></i> Log First Interruption
            </a>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:13px;">
            <thead>
                <tr style="background:#f8f9fa;">
                    <th class="th">Ticket</th>
                    <th class="th">Feeder</th>
                    <th class="th">Station</th>
                    <th class="th">Type</th>
                    <th class="th">Code</th>
                    <th class="th">Date Out</th>
                    <th class="th">Date In</th>
                    <th class="th">Duration</th>
                    <th class="th">Load Loss</th>
                    <th class="th">Status</th>
                    <th class="th">Logged By</th>
                    <th class="th">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($interruptions as $i): ?>
            <tr class="tr-row">
                <td class="td" style="font-family:monospace;font-weight:700;color:#2c3e50;font-size:12px;white-space:nowrap;">
                    <?= htmlspecialchars($i['ticket_number']) ?>
                </td>
                <td class="td" style="font-weight:600;color:#2c3e50;"><?= htmlspecialchars($i['fdr33kv_name'] ?? '—') ?></td>
                <td class="td" style="color:#7f8c8d;font-size:12px;"><?= htmlspecialchars($i['station_name'] ?? '—') ?></td>
                <td class="td"><?= typeBadge33($i['interruption_type'] ?? '') ?></td>
                <td class="td" style="font-family:monospace;font-size:12px;"><?= htmlspecialchars($i['interruption_code'] ?? '—') ?></td>
                <td class="td" style="white-space:nowrap;color:#555;">
                    <?= $i['datetime_out'] ? date('d/m/y H:i', strtotime($i['datetime_out'])) : '—' ?>
                </td>
                <td class="td" style="white-space:nowrap;color:#555;">
                    <?= $i['datetime_in'] ? date('d/m/y H:i', strtotime($i['datetime_in'])) : '<span style="color:#e74c3c;">Pending</span>' ?>
                </td>
                <td class="td" style="text-align:right;font-weight:600;">
                    <?= $i['duration'] !== null ? round((float)$i['duration']).' min' : '—' ?>
                </td>
                <td class="td" style="text-align:right;font-weight:600;color:#e74c3c;">
                    <?= $i['load_loss'] !== null ? number_format((float)$i['load_loss'],2).' MW' : '—' ?>
                </td>
                <td class="td"><?= statusBadge33($i['form_status'] ?? '') ?></td>
                <td class="td" style="font-size:12px;color:#7f8c8d;"><?= htmlspecialchars($i['logger_name'] ?? '—') ?></td>
                <td class="td" style="white-space:nowrap;">
                    <a href="index.php?page=interruptions&action=view&ticket=<?= urlencode($i['ticket_number']) ?>"
                       title="View" style="color:#667eea;text-decoration:none;margin-right:8px;font-size:14px;">
                        <i class="fas fa-eye"></i>
                    </a>
                    <?php if (in_array($i['form_status'],['PENDING_COMPLETION','PENDING_COMPLETION_APPROVED'])): ?>
                    <a href="index.php?page=interruptions&action=complete&id=<?= (int)$i['id'] ?>"
                       title="Complete Stage 2" style="color:#27ae60;text-decoration:none;font-size:14px;">
                        <i class="fas fa-check-circle"></i>
                    </a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>

</div><!-- /max-width -->
</div><!-- /main-content -->

<style>
.form-ctrl{width:100%;padding:9px 12px;border:1.5px solid #e0e0e0;border-radius:7px;font-size:13px;font-family:inherit;transition:border-color .2s;}
.form-ctrl:focus{outline:none;border-color:#667eea;}
.th{padding:11px 14px;text-align:left;font-weight:700;font-size:12px;color:#495057;
    border-bottom:2px solid #dee2e6;white-space:nowrap;}
.td{padding:11px 14px;border-bottom:1px solid #f0f2f5;vertical-align:middle;}
.tr-row:hover td{background:#f8f9ff;}
.tr-row:last-child td{border-bottom:none;}
</style>

<?php require __DIR__ . '/../layout/footer.php'; ?>
