<!-- File: /app/Modules/Interruptions/InterruptionView.php -->
<h2>Interruption Log</h2>

<form method="post" action="/save-interruption.php">
    <input name="fdr33_code" placeholder="33kV Feeder Code" required>

    <select name="type">
        <option>O/S TCN</option>
        <option>E/F</option>
        <option>SBT KE</option>
        <option>O/C and E/F</option>
    </select>

    <input type="number" step="0.01" name="load_loss">

    <input type="datetime-local" name="datetime_out" required>
    <input type="datetime-local" name="datetime_in" required>

    <textarea name="reason"></textarea>
    <textarea name="resolution"></textarea>

    <select name="weather">
        <option>FINE</option>
        <option>RAIN</option>
        <option>STORM</option>
    </select>

    <select name="delay_reason">
        <option>DSO communicated late</option>
        <option>Lack of vehicle or fuel</option>
        <option>Lack of staff</option>
        <option>Lack of material</option>
        <option>Security delay</option>
        <option>Technical negligence</option>
        <option>Others</option>
    </select>

    <button type="submit">Save</button>
</form>
