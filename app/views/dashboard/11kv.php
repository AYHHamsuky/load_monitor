<?php
require_once __DIR__ . '/../../models/Dashboard11kv.php';

$data = Dashboard11kv::today(
    $_SESSION['iss_code'],
    $_SESSION['assigned_33kv_code']
);
?>

<h2>Today's 11kV Load Readings</h2>

<table class="load-table">
    <thead>
        <tr>
            <th>11KV FEEDER</th>
            <th>BAND</th>
            <?php for ($h = 1; $h <= 24; $h++): ?>
                <th><?= $h === 24 ? '0.00' : $h . ':00' ?></th>
            <?php endfor; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data['matrix'] as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['band']) ?></td>
                <?php foreach ($row['hours'] as $val): ?>
                    <td><?= $val ?></td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h3>Feeder Performance Summary</h3>

<table class="summary-table">
    <thead>
        <tr>
            <th>Feeder</th>
            <th>Band</th>
            <th>Supply Hrs</th>
            <th>Total Load</th>
            <th>Avg Load</th>
            <th>Peak Load</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data['summary'] as $s): ?>
            <tr>
                <td><?= $s['name'] ?></td>
                <td><?= $s['band'] ?></td>
                <td><?= $s['supply_hours'] ?></td>
                <td><?= number_format($s['total'], 2) ?></td>
                <td><?= number_format($s['avg'], 2) ?></td>
                <td><?= number_format($s['peak'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<canvas id="lineChart"></canvas>
<canvas id="barChart"></canvas>
