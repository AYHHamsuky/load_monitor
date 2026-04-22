<?php
// File: /public/interruptions.php

session_start();
require_once __DIR__ . '/../app/Core/Auth.php';
require_once __DIR__ . '/../app/Core/Database.php';

use App\Core\Auth;
use App\Core\Database;

$user = Auth::requireLogin();
$db = Database::get();

$stmt = $db->prepare(
    "SELECT 33kv_code, 33kv_name FROM fdr33kv WHERE ts_code = ?"
);
$stmt->execute([$user['assigned_33kv_station']]);
$feeders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>Interruptions</title></head>
<body>

<h2>Interruption Log</h2>

<form method="post" action="/save-interruption.php">

<label>33kV Feeder</label><br>
<select name="fdr33kv_code">
<?php foreach ($feeders as $f): ?>
<option value="<?= $f['33kv_code'] ?>"><?= $f['33kv_name'] ?></option>
<?php endforeach; ?>
</select><br><br>

<label>Interruption Type</label><br>
<input name="interruption_type"><br><br>

<label>Load Loss (MW)</label><br>
<input type="number" step="0.01" name="load_loss"><br><br>

<label>Out</label><br>
<input type="datetime-local" name="datetime_out" required><br><br>

<label>In</label><br>
<input type="datetime-local" name="datetime_in" required><br><br>

<label>Reason</label><br>
<textarea name="reason"></textarea><br><br>

<label>Resolution</label><br>
<textarea name="resolution"></textarea><br><br>

<label>Weather</label><br>
<select name="weather">
<option>FINE</option>
<option>RAIN</option>
<option>STORM</option>
</select><br><br>

<label>Reason for Delay</label><br>
<select name="delay_reason">
<option>DSO communicated late</option>
<option>Lack of vehicle or fuel for patrol</option>
<option>Lack of staff during restoration work</option>
<option>Lack of material</option>
<option>Delay to get security</option>
<option>Line in marshy Area</option>
<option>Technical staff negligence</option>
<option>Others</option>
</select><br><br>

<button type="submit">Save</button>
</form>

</body>
</html>
