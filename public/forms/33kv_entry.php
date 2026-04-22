<?php
session_start();

if ($_SESSION['role'] !== 'UL2') {
    header("Location: /dashboard.php");
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../layout/sidebar.php';

/* preload 33kV feeders */
$stmt = $pdo->prepare("
    SELECT fdr33kv_code, fdr33kv_name
    FROM fdr33kv
    ORDER BY fdr33kv_name
");
$stmt->execute();
$feeders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="main-content">
<h2>33kV Load Reading Entry</h2>

<form method="POST" action="/actions/save_33kv_entry.php" id="loadForm">

<select name="fdr33kv_code" required>
    <option value="">-- Select 33kV Feeder --</option>
    <?php foreach ($feeders as $f): ?>
        <option value="<?= $f['fdr33kv_code'] ?>">
            <?= htmlspecialchars($f['fdr33kv_name']) ?>
        </option>
    <?php endforeach; ?>
</select>

<select name="entry_hour" required>
<?php for ($h=1;$h<=24;$h++): ?>
    <option value="<?= $h ?>"><?= $h===24?'0.00':$h.':00' ?></option>
<?php endfor; ?>
</select>

<input type="number" step="0.01" name="load_read" id="load_read" required>

<fieldset id="faultBox">
    <select name="fault_code" id="fault_code">
        <option value="">Fault</option>
        <option value="FO">FO</option>
        <option value="BF">BF</option>
        <option value="OS">OS</option>
        <option value="DOff">DOff</option>
        <option value="MVR">MVR</option>
    </select>
    <input name="fault_remark" id="fault_remark">
</fieldset>

<button type="submit">Submit</button>
</form>
</main>

<script src="/assets/js/load_form.js"></script>
<?php require_once __DIR__ . '/../layout/footer.php'; ?>
