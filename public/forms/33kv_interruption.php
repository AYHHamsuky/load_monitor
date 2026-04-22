<?php
session_start();

if (!isset($_SESSION['payroll_id']) || !in_array($_SESSION['role'], ['UL2','UL3','UL4','UL5','UL6'])) {
    header("Location: /dashboard.php");
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../layout/sidebar.php';

/* preload 33kV feeders */
$stmt = $pdo->query("
    SELECT fdr33kv_code, fdr33kv_name
    FROM fdr33kv
    ORDER BY fdr33kv_name
");
$feeders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="main-content">
<h2>33kV Interruption Entry</h2>

<form method="POST" action="/actions/save_33kv_interruption.php" id="interruptForm">

<label>33kV Feeder</label>
<select name="fdr33_code" required>
    <option value="">-- Select Feeder --</option>
    <?php foreach ($feeders as $f): ?>
        <option value="<?= $f['fdr33kv_code'] ?>">
            <?= htmlspecialchars($f['fdr33kv_name']) ?>
        </option>
    <?php endforeach; ?>
</select>

<label>Interruption Type</label>
<input type="text" name="interruption_type" required placeholder="Planned / Forced / Emergency">

<label>Load Loss (MW)</label>
<input type="number" step="0.01" name="load_loss" required>

<label>Outage Start</label>
<input type="datetime-local" name="datetime_out" required>

<label>Restoration Time</label>
<input type="datetime-local" name="datetime_in" required>

<label>Reason for Interruption</label>
<textarea name="reason_for_interruption"></textarea>

<label>Resolution</label>
<input type="text" name="resolution">

<label>Weather Condition</label>
<input type="text" name="weather_condition">

<label>Reason for Delay</label>
<select name="reason_for_delay" id="delay_reason">
    <option value="">-- None --</option>
    <option value="DSO communicated late">DSO communicated late</option>
    <option value="Lack of vehicle or fuel for patrol">Lack of vehicle or fuel for patrol</option>
    <option value="Lack of staff during restoration work">Lack of staff during restoration work</option>
    <option value="Lack of material">Lack of material</option>
    <option value="Delay to get security">Delay to get security</option>
    <option value="Line in marshy Area">Line in marshy Area</option>
    <option value="Technical staff negligence">Technical staff negligence</option>
    <option value="others">Others</option>
</select>

<label id="otherLabel" style="display:none;">Other Reason</label>
<input type="text" name="other_reasons" id="otherReason" style="display:none;">

<button type="submit">Submit Interruption</button>
</form>
</main>

<script src="/assets/js/interruption_form.js"></script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
