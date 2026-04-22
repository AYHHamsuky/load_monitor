<h2>33KV Load Entry</h2>

<form method="POST">
    <label>Hour</label><br>
    <select name="hour">
        <?php foreach (DAY_HOURS as $h): ?>
            <option value="<?= $h ?>"><?= $h ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Load (MW)</label><br>
    <input type="number" step="0.01" name="load" required><br><br>

    <button>Save</button>
</form>

<h3>Today’s Load</h3>
<pre><?php print_r($loads); ?></pre>
