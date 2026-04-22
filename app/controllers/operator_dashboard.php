<h2>Operator Dashboard — Today</h2>

<section>
<h3>33KV Load (Hourly)</h3>
<table>
<tr><th>Hour</th><th>Load / Fault</th></tr>
<?php foreach ($stationLoad as $r): ?>
<tr>
<td><?= $r['periods'] ?></td>
<td><?= $r['load_reading'] ?? $r['fault'] ?></td>
</tr>
<?php endforeach; ?>
</table>
</section>

<section>
<h3>Active Interruptions</h3>
<?php if (!$interrupts): ?>
<p>No interruptions logged today.</p>
<?php else: ?>
<table>
<tr><th>Start</th><th>End</th><th>Type</th><th>Cause</th></tr>
<?php foreach ($interrupts as $i): ?>
<tr>
<td><?= $i['start_time'] ?></td>
<td><?= $i['end_time'] ?></td>
<td><?= $i['interruption_type'] ?></td>
<td><?= $i['cause'] ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>
</section>
