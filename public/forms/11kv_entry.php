<?php
session_start();

if (!isset($_SESSION['payroll_id']) || $_SESSION['role'] !== 'UL1') {
    header("Location: /dashboard.php");
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../layout/header.php';
require_once __DIR__ . '/../layout/sidebar.php';

/* preload feeders for staff ISS + assigned 33kV */
$stmt = $pdo->prepare("
    SELECT fdr11kv_code, fdr11kv_name
    FROM fdr11kv
    WHERE iss_code = ?
      AND fdr33kv_code = ?
    ORDER BY fdr11kv_name
");
$stmt->execute([
    $_SESSION['iss_code'],
    $_SESSION['assigned_33kv_code']
]);
$feeders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<main class="main-content">
<h2>11kV Load Reading Entry</h2>

<form id="loadForm" method="POST" action="/actions/save_11kv_entry.php">
    
    <label>Feeder</label>
    <select name="fdr11kv_code" required>
        <option value="">-- Select Feeder --</option>
        <?php foreach ($feeders as $f): ?>
            <option value="<?= $f['fdr11kv_code'] ?>">
                <?= htmlspecialchars($f['fdr11kv_name']) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label>Hour</label>
    <select name="entry_hour" required>
        <?php for ($h = 1; $h <= 24; $h++): ?>
            <option value="<?= $h ?>">
                <?= $h === 24 ? '0.00 hrs' : $h . ':00 hrs' ?>
            </option>
        <?php endfor; ?>
    </select>

    <label>Load Reading (MW)</label>
    <input type="number" step="0.01" name="load_read" id="load_read" required>

    <fieldset id="faultBox">
        <legend>Fault Details (required if Load = 0.00)</legend>

        <label>Fault Code</label>
        <select name="fault_code" id="fault_code">
            <option value="">-- Select --</option>
            <option value="FO">FO</option>
            <option value="BF">BF</option>
            <option value="OS">OS</option>
            <option value="DOff">DOff</option>
            <option value="MVR">MVR</option>
        </select>

        <label>Fault Remark</label>
        <input type="text" name="fault_remark" id="fault_remark">
    </fieldset>

    <button type="submit">Submit Entry</button>
</form>
</main>

<script src="/assets/js/load_form.js"></script>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
