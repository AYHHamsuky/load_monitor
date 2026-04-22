<!-- File: /app/Modules/LoadEntry/LoadEntry11kvView.php -->
<form method="post" action="/save-11kv.php">
    <select name="fdr11kv_code" required>
        <?php foreach ($feeders as $f): ?>
            <option value="<?= $f['11kv_code'] ?>">
                <?= $f['11kv_fdr_name'] ?> (<?= $f['band'] ?>)
            </option>
        <?php endforeach; ?>
    </select>

    <select name="day_hour">
        <?php for ($i=1;$i<=24;$i++): ?>
            <option><?= number_format($i,2) ?></option>
        <?php endfor; ?>
    </select>

    <input type="number" step="0.01" max="10" name="load_reading">

    <select name="fault">
        <option value="">--</option>
        <option>LS</option>
        <option>OS</option>
        <option>FO</option>
        <option>BF</option>
    </select>

    <textarea name="fault_remark"></textarea>

    <button type="submit">Save</button>
</form>
