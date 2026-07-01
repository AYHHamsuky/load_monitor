<?php require __DIR__ . '/../layout/header.php'; ?>
<?php require __DIR__ . '/../layout/sidebar.php'; ?>

<style>
.main-content { margin-left:252px; padding:24px; padding-top:90px; background:#f4f6fa; min-height:100vh; }
@media (max-width:768px) { .main-content { margin-left:0; padding:74px 12px 16px; } }

.page-head { background:linear-gradient(135deg,#004B23 0%,#006b30 100%); color:#fff; border-radius:12px;
    padding:22px 26px; margin-bottom:20px; box-shadow:0 4px 14px rgba(0,75,35,.25); }
.page-head h1 { font-size:22px; font-weight:700; margin:0 0 4px; display:flex; gap:10px; align-items:center; }
.page-head .sub { opacity:.85; font-size:13px; }

.card { background:#fff; border-radius:12px; padding:22px 24px; box-shadow:0 2px 10px rgba(0,0,0,.07); margin-bottom:20px; }

.filters { display:flex; gap:12px; align-items:center; margin-bottom:16px; flex-wrap:wrap; }
.filters input, .filters select { padding:8px 12px; border:1.5px solid #cbd5e1; border-radius:7px; font-size:13px; min-width:180px; }
.filters input:focus, .filters select:focus { outline:none; border-color:#004B23; box-shadow:0 0 0 3px rgba(0,75,35,.1); }

.counts { display:flex; gap:16px; margin-bottom:18px; flex-wrap:wrap; }
.count-chip { padding:8px 14px; background:#f1f5f9; border-radius:8px; font-size:13px; color:#334155; font-weight:600; }
.count-chip strong { color:#004B23; }

table { width:100%; border-collapse:collapse; font-size:13px; }
th { background:#004B23; color:#fff; padding:10px 12px; text-align:left; font-size:11px; text-transform:uppercase; letter-spacing:.4px; position:sticky; top:0; z-index:2; }
td { padding:8px 12px; border-bottom:1px solid #eef2f7; }
tr:hover td { background:#f8fafc; }
.badge { display:inline-block; padding:2px 8px; border-radius:10px; font-size:11px; font-weight:700; }
.b-A { background:#dcfce7; color:#166534; }
.b-B { background:#dbeafe; color:#1e40af; }
.b-C { background:#fef3c7; color:#92400e; }
.b-D { background:#fee2e2; color:#991b1b; }
.b-E { background:#f3e8ff; color:#6b21a8; }
.b-none { background:#e2e8f0; color:#475569; }
.dedicated { color:#64748b; font-style:italic; font-size:12px; }
.notice { background:#fef3c7; border-left:4px solid #f59e0b; padding:12px 16px; border-radius:6px; font-size:13px; color:#78350f; }
.notice code { background:#fff; padding:1px 6px; border-radius:3px; font-family:monospace; font-size:12px; }

.scroll { max-height:640px; overflow:auto; border:1px solid #e5e7eb; border-radius:8px; }
</style>

<div class="main-content">

<div class="page-head">
    <h1>🌳 Feeder Mapping Hierarchy</h1>
    <div class="sub">330kV → 132kV → Region → Area Office → 33kV Feeder → 11kV Feeder (with band). Source: dispatch team CSV.</div>
</div>

<div class="card">

<?php if ($rows === null): ?>
    <div class="notice">
        <strong>Hierarchy not loaded yet.</strong>
        The <code>feeder_hierarchy_map</code> table is empty or missing. Run the import once:
        <br><code>php /var/www/html/sql/import_feeder_hierarchy.php</code>
        &nbsp;or&nbsp;
        <code>UL6/UL7 → /import_feeder_hierarchy.php</code>
    </div>

<?php else:
    // Precompute filter options
    $regions      = array_values(array_unique(array_column($rows, 'region')));
    $tx330Options = array_values(array_unique(array_column($rows, 'tx_330kv')));
    $tx132Options = array_values(array_unique(array_column($rows, 'tx_132kv')));
    sort($regions); sort($tx330Options); sort($tx132Options);

    $tot33 = count(array_unique(array_column($rows, 'fdr33kv_name')));
    $tot11 = count(array_unique(array_filter(array_column($rows, 'fdr11kv_name'))));
    $totRegions = count($regions);
    $totAO = count(array_unique(array_column($rows, 'area_office')));
?>

    <div class="counts">
        <div class="count-chip"><strong><?= number_format(count($rows)) ?></strong> mapping rows</div>
        <div class="count-chip"><strong><?= count($tx330Options) ?></strong> × 330kV stations</div>
        <div class="count-chip"><strong><?= count($tx132Options) ?></strong> × 132kV substations</div>
        <div class="count-chip"><strong><?= $totRegions ?></strong> regions</div>
        <div class="count-chip"><strong><?= $totAO ?></strong> area offices</div>
        <div class="count-chip"><strong><?= $tot33 ?></strong> × 33kV feeders</div>
        <div class="count-chip"><strong><?= $tot11 ?></strong> × 11kV feeders</div>
    </div>

    <div class="filters">
        <select id="f_region"><option value="">All Regions</option><?php foreach ($regions as $r): ?><option><?= htmlspecialchars($r) ?></option><?php endforeach; ?></select>
        <select id="f_tx330"><option value="">All 330kV</option><?php foreach ($tx330Options as $r): ?><option><?= htmlspecialchars($r) ?></option><?php endforeach; ?></select>
        <select id="f_tx132"><option value="">All 132kV</option><?php foreach ($tx132Options as $r): ?><option><?= htmlspecialchars($r) ?></option><?php endforeach; ?></select>
        <input type="search" id="f_search" placeholder="Search feeder or area office…" style="flex:1;min-width:220px;">
    </div>

    <div class="scroll">
        <table id="tbl">
            <thead>
                <tr>
                    <th>330kV</th><th>132kV</th><th>Region</th><th>Area Office</th>
                    <th>33kV Feeder</th><th>11kV Feeder</th><th>Band</th><th>Source</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r):
                    $band = $r['band'] ?: '';
                    $bClass = 'b-' . ($band && in_array($band, ['A','B','C','D','E'], true) ? $band : 'none');
                ?>
                <tr>
                    <td><?= htmlspecialchars($r['tx_330kv']) ?></td>
                    <td><?= htmlspecialchars($r['tx_132kv']) ?></td>
                    <td><?= htmlspecialchars($r['region']) ?></td>
                    <td><?= htmlspecialchars($r['area_office']) ?></td>
                    <td><?= htmlspecialchars($r['fdr33kv_name']) ?></td>
                    <td>
                        <?php if ($r['is_dedicated']): ?>
                            <span class="dedicated">(dedicated / no 11kV)</span>
                        <?php else: ?>
                            <?= htmlspecialchars($r['fdr11kv_name']) ?>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge <?= $bClass ?>"><?= htmlspecialchars($band ?: '—') ?></span></td>
                    <td style="font-size:11px;color:#64748b;"><?= htmlspecialchars($r['mapping_source']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <script>
    (function () {
        const rows = document.querySelectorAll('#tbl tbody tr');
        function apply() {
            const r  = document.getElementById('f_region').value.toLowerCase();
            const t3 = document.getElementById('f_tx330').value.toLowerCase();
            const t1 = document.getElementById('f_tx132').value.toLowerCase();
            const q  = document.getElementById('f_search').value.toLowerCase().trim();
            rows.forEach(row => {
                const c = row.children;
                const rowText = row.textContent.toLowerCase();
                const okR = !r  || c[2].textContent.toLowerCase() === r;
                const ok3 = !t3 || c[0].textContent.toLowerCase() === t3;
                const ok1 = !t1 || c[1].textContent.toLowerCase() === t1;
                const okQ = !q  || rowText.includes(q);
                row.style.display = (okR && ok3 && ok1 && okQ) ? '' : 'none';
            });
        }
        ['f_region','f_tx330','f_tx132','f_search'].forEach(id => {
            document.getElementById(id).addEventListener('input',  apply);
            document.getElementById(id).addEventListener('change', apply);
        });
    })();
    </script>

<?php endif; ?>

</div>

</div><!-- /main-content -->

<?php require __DIR__ . '/../layout/footer.php'; ?>
