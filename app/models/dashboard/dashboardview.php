<!-- File: /app/Modules/Dashboard/DashboardView.php -->
<table border="1">
<tr>
    <th>11KV FEEDER</th><th>BAND</th>
    <?php for($h=1;$h<=24;$h++) echo "<th>$h</th>"; ?>
</tr>

<?php
$current = null;
$row = [];
foreach ($data as $r) {
    if ($current !== $r['11kv_fdr_name']) {
        if ($current) {
            echo "<tr><td>$current</td><td>{$row['band']}</td>";
            for ($i=1;$i<=24;$i++) echo "<td>".($row[$i] ?? '')."</td>";
            echo "</tr>";
        }
        $current = $r['11kv_fdr_name'];
        $row = ['band'=>$r['band']];
    }
    $row[(int)$r['day_hour']] = $r['value'];
}
?>
</table>
