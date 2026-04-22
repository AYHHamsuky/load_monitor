<div class="main-content">
    <div class="dashboard-card">
        <h3>33kV Load Analytics</h3>
        <canvas id="kv33Chart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
new Chart(document.getElementById('kv33Chart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($data,'fdr33kv_code')) ?>,
        datasets: [{
            data: <?= json_encode(array_column($data,'total')) ?>,
            backgroundColor: '#1e40af'
        }]
    }
});
</script>
