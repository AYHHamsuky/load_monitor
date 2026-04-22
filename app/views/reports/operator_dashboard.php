<h2>Operator Dashboard — Today</h2>

<!-- ALERTS -->
<?php if ($missingHours): ?>
<div style="background:#fee2e2;color:#991b1b;padding:10px;margin-bottom:15px;">
    ⚠ Missing 33KV Load Entry for Hours:
    <?= implode(', ', $missingHours) ?>
</div>
<?php endif; ?>

<!-- 33KV LOAD -->
<section>
<h3>33KV Station Load</h3>
<table>
<tr>
    <th>Hour</th>
    <th>Load / Fault</th>
</tr>
<?php foreach (DAY_HOURS as $h): ?>
<tr>
    <td><?= $h ?></td>
    <td>
        <?php
        $found = false;
        foreach ($stationLoad as $r) {
            if ($r['periods'] == $h) {
                echo $r['load_reading'] ?? $r['fault'];
                $found = true;
                break;
            }
        }
        if (!$found) echo '—';
        ?>
    </td>
</tr>
<?php endforeach; ?>
</table>
</section>

<!-- INTERRUPTIONS -->
<section style="margin-top:30px;">
<h3>Interruptions (Today)</h3>
<?php if (!$interrupts): ?>
<p>No interruptions recorded.</p>
<?php else: ?>
<table>
<tr>
    <th>Start</th>
    <th>End</th>
    <th>Type</th>
    <th>Cause</th>
</tr>
<?php foreach ($interrupts as $i): ?>
<tr>
    <td><?= date('H:i', strtotime($i['datetime_out'])) ?></td>
    <td>
        <?= $i['datetime_in']
            ? date('H:i', strtotime($i['datetime_in']))
            : '—'
        ?>
    </td>
    <td><?= htmlspecialchars($i['Interruption_Type']) ?></td>
    <td><?= htmlspecialchars($i['Reason_for_Interruption']) ?></td>
</tr>
<?php endforeach; ?>

</table>
<?php endif; ?>
</section>
