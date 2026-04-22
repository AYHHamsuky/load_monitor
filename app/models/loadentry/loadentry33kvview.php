<!-- File: /app/Modules/LoadEntry/LoadEntry33kvView.php -->
<h2>33kV Load Entry</h2>

<form method="post" action="/save-33kv.php">
    <select name="fdr33kv_code" required>
        <?php foreach ($feeders as $f): ?>
            <option value="<?= $f['33kv_code'] ?>">
                <?= $f['33kv_name'] ?>
            </option>
        <?php endforeach; ?>
    </select>

    <select name="day_hour">
        <?php for ($i=1;$i<=24;$i++): ?>
            <option><?= number_format($i,2) ?></option>
        <?php endfor; ?>
    </select>

    <input type="number" step="0.01" max="10" name="load_reading" required>

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
