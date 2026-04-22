<h2>11KV Load Entry</h2>

<form method="POST">
    <label>Feeder</label><br>
    <select name="feeder" required>
        <?php foreach ($feeders as $f): ?>
            <option value="<?= $f['11kv_code'] ?>">
                <?= htmlspecialchars($f['11kv_fdr_name']) ?> (Band <?= $f['band'] ?>)
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Hour</label><br>
    <select name="hour">
        <?php foreach (DAY_HOURS as $h): ?>
            <option value="<?= $h ?>"><?= $h ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Load (MW)</label><br>
    <input type="number" name="load" step="0.01" max="10"><br><br>

    <label>Fault</label><br>
    <select name="fault">
        <option value="">None</option>
        <?php foreach (FAULT_TYPES as $f): ?>
            <option value="<?= $f ?>"><?= $f ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Remark</label><br>
    <textarea name="remark"></textarea><br><br>

    <button type="submit">Save Entry</button>
</form>
