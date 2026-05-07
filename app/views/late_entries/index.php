<?php require __DIR__ . '/../layout/header.php'; ?>
<?php require __DIR__ . '/../layout/sidebar.php'; ?>

<style>
.main-content { margin-left: 252px; padding: 80px 22px 22px; background: #f4f6fa; min-height: 100vh; }
@media (max-width: 768px) { .main-content { margin-left: 0; padding: 74px 12px 16px; } }

.page-head {
    background: linear-gradient(135deg, #004B23 0%, #006b30 100%);
    color: #fff;
    border-radius: 12px;
    padding: 22px 26px;
    margin-bottom: 18px;
    box-shadow: 0 4px 14px rgba(0,75,35,0.25);
}
.page-head h1 { font-size: 22px; font-weight: 700; margin: 0 0 4px; display: flex; gap: 10px; align-items: center; }
.page-head .sub { opacity: 0.85; font-size: 13px; }

.stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px,1fr)); gap: 12px; margin-bottom: 18px; }
.stat-card { background: #fff; padding: 14px 16px; border-radius: 8px; box-shadow: 0 1px 6px rgba(0,0,0,.07); }
.stat-card .v { font-size: 22px; font-weight: 800; color: #1e293b; }
.stat-card .l { font-size: 11px; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; }

.filters {
    background: #fff;
    border-radius: 10px;
    padding: 16px 18px;
    margin-bottom: 16px;
    box-shadow: 0 1px 6px rgba(0,0,0,.07);
}
.filters form { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px,1fr)); gap: 12px; align-items: end; }
.filters label { display: block; font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 4px; }
.filters input, .filters select {
    width: 100%; padding: 7px 10px; border: 1px solid #cbd5e1; border-radius: 6px;
    font-size: 13px; background: #fff;
}
.btn { padding: 8px 18px; border-radius: 6px; font-weight: 600; font-size: 13px; cursor: pointer; border: 0; }
.btn-primary { background: #004B23; color: #fff; }
.btn-primary:hover { background: #006b30; }
.btn-outline { background: #fff; color: #004B23; border: 1px solid #004B23; text-decoration: none; display: inline-block; }
.btn-outline:hover { background: #f0f7ee; }

.results-card { background: #fff; border-radius: 10px; box-shadow: 0 1px 6px rgba(0,0,0,.07); overflow: hidden; }
.results-head {
    display: flex; justify-content: space-between; align-items: center;
    padding: 14px 18px; border-bottom: 1px solid #e5e7eb;
}
.results-head h3 { margin: 0; font-size: 15px; color: #1e293b; }

table.late-tbl { width: 100%; border-collapse: collapse; font-size: 13px; }
.late-tbl th { background: #f8fafc; color: #1e293b; text-align: left; padding: 10px 12px; font-weight: 700; border-bottom: 2px solid #e5e7eb; font-size: 11px; text-transform: uppercase; letter-spacing: 0.4px; }
.late-tbl td { padding: 10px 12px; border-bottom: 1px solid #eef2f7; vertical-align: top; }
.late-tbl tr:hover td { background: #fafbfc; }
.late-tbl .hour { font-family: 'Courier New', monospace; font-weight: 700; color: #004B23; white-space: nowrap; }
.late-tbl .voltage { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 700; }
.late-tbl .v-11 { background: #dbeafe; color: #1e40af; }
.late-tbl .v-33 { background: #fef3c7; color: #92400e; }
.late-tbl .explanation { color: #374151; max-width: 400px; }
.late-tbl .small { color: #64748b; font-size: 12px; }
.late-tbl .empty { text-align: center; padding: 50px 20px; color: #94a3b8; font-style: italic; }

.pager { display: flex; justify-content: space-between; align-items: center; padding: 12px 18px; border-top: 1px solid #e5e7eb; font-size: 13px; color: #475569; }
.pager .links a, .pager .links span { padding: 5px 10px; border-radius: 4px; text-decoration: none; color: #004B23; margin: 0 2px; border: 1px solid transparent; }
.pager .links a:hover { background: #f0f7ee; border-color: #cbd5e1; }
.pager .links .current { background: #004B23; color: #fff; }
.pager .links .disabled { color: #cbd5e1; pointer-events: none; }
</style>

<div class="main-content">

<div class="page-head">
    <h1>⏰ Late Entry Explanations</h1>
    <div class="sub">Audit log of every late-submission explanation provided by data-entry staff.</div>
</div>

<div class="stats">
    <div class="stat-card"><div class="v"><?= number_format($stats['total']) ?></div><div class="l">Records (in range)</div></div>
    <div class="stat-card"><div class="v"><?= number_format($stats['c11']) ?></div><div class="l">11 kV</div></div>
    <div class="stat-card"><div class="v"><?= number_format($stats['c33']) ?></div><div class="l">33 kV</div></div>
    <div class="stat-card"><div class="v"><?= number_format($stats['staff_count']) ?></div><div class="l">Distinct staff</div></div>
    <div class="stat-card"><div class="v"><?= number_format($stats['iss_count']) ?></div><div class="l">Distinct ISS</div></div>
</div>

<div class="filters">
    <form method="get" action="">
        <input type="hidden" name="page" value="late_entries">
        <div>
            <label>From</label>
            <input type="date" name="from" value="<?= htmlspecialchars($fromDate) ?>">
        </div>
        <div>
            <label>To</label>
            <input type="date" name="to" value="<?= htmlspecialchars($toDate) ?>">
        </div>
        <div>
            <label>Voltage</label>
            <select name="voltage">
                <option value="all"  <?= $voltage==='all'  ? 'selected' : '' ?>>All</option>
                <option value="11kV" <?= $voltage==='11kV' ? 'selected' : '' ?>>11 kV</option>
                <option value="33kV" <?= $voltage==='33kV' ? 'selected' : '' ?>>33 kV</option>
            </select>
        </div>
        <div>
            <label>ISS</label>
            <select name="iss">
                <option value="all">All</option>
                <?php foreach ($issList as $i): ?>
                    <option value="<?= htmlspecialchars($i['iss_code']) ?>" <?= $issCode===$i['iss_code']?'selected':'' ?>>
                        <?= htmlspecialchars($i['iss_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label>Staff name / ID</label>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="search...">
        </div>
        <div>
            <button type="submit" class="btn btn-primary">Apply</button>
        </div>
    </form>
</div>

<div class="results-card">
    <div class="results-head">
        <h3>Results — <?= number_format($totalRows) ?> record<?= $totalRows===1?'':'s' ?></h3>
        <a class="btn btn-outline btn-sm" href="?page=late_entries&export=csv&from=<?= urlencode($fromDate) ?>&to=<?= urlencode($toDate) ?>&voltage=<?= urlencode($voltage) ?>&iss=<?= urlencode($issCode) ?>&q=<?= urlencode($search) ?>">⬇ Export CSV</a>
    </div>

    <table class="late-tbl">
        <thead>
            <tr>
                <th>Date</th>
                <th>Hour</th>
                <th>Voltage</th>
                <th>Staff</th>
                <th>ISS</th>
                <th>Explanation</th>
                <th>Logged At</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($entries)): ?>
                <tr><td colspan="7" class="empty">No late-entry records match your filters.</td></tr>
            <?php else: ?>
                <?php foreach ($entries as $e): ?>
                    <tr>
                        <td><?= htmlspecialchars($e['log_date']) ?></td>
                        <td class="hour"><?= sprintf('%02d:00', (int)$e['specific_hour']) ?></td>
                        <td><span class="voltage <?= $e['voltage_level']==='11kV'?'v-11':'v-33' ?>"><?= htmlspecialchars($e['voltage_level']) ?></span></td>
                        <td>
                            <div><?= htmlspecialchars($e['staff_name'] ?? '—') ?></div>
                            <div class="small"><?= htmlspecialchars($e['user_id']) ?> &middot; <?= htmlspecialchars($e['role'] ?? '') ?></div>
                        </td>
                        <td>
                            <div><?= htmlspecialchars($e['iss_name'] ?? '—') ?></div>
                            <div class="small"><?= htmlspecialchars($e['iss_code']) ?></div>
                        </td>
                        <td class="explanation"><?= nl2br(htmlspecialchars($e['explanation'])) ?></td>
                        <td class="small"><?= htmlspecialchars($e['logged_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1):
        $base = "?page=late_entries&from={$fromDate}&to={$toDate}&voltage={$voltage}&iss={$issCode}&q=" . urlencode($search);
        $window = 5;
        $start  = max(1, $page - $window);
        $end    = min($totalPages, $page + $window);
    ?>
    <div class="pager">
        <div>Page <?= $page ?> of <?= $totalPages ?></div>
        <div class="links">
            <a href="<?= $base ?>&page=1" class="<?= $page<=1?'disabled':'' ?>">« First</a>
            <a href="<?= $base ?>&page=<?= max(1,$page-1) ?>" class="<?= $page<=1?'disabled':'' ?>">‹ Prev</a>
            <?php for ($p=$start; $p<=$end; $p++): ?>
                <a href="<?= $base ?>&page=<?= $p ?>" class="<?= $p===$page?'current':'' ?>"><?= $p ?></a>
            <?php endfor; ?>
            <a href="<?= $base ?>&page=<?= min($totalPages,$page+1) ?>" class="<?= $page>=$totalPages?'disabled':'' ?>">Next ›</a>
            <a href="<?= $base ?>&page=<?= $totalPages ?>" class="<?= $page>=$totalPages?'disabled':'' ?>">Last »</a>
        </div>
    </div>
    <?php endif; ?>
</div>

</div><!-- /main-content -->

<?php require __DIR__ . '/../layout/footer.php'; ?>
