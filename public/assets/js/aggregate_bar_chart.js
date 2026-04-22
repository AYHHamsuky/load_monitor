async function loadAggregateChart(type, date) {
    const res = await fetch(
        `/api/daily_aggregate.php?type=${type}&date=${date}`
    );
    const data = await res.json();

    const labels = data.map(d => d.feeder);
    const total  = data.map(d => d.total_load);
    const avg    = data.map(d => d.avg_load);
    const peak   = data.map(d => d.peak_load);

    const ctx = document.getElementById('aggregateBarChart').getContext('2d');

    if (window.aggregateChart) window.aggregateChart.destroy();

    window.aggregateChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'Total Load', data: total },
                { label: 'Avg Load', data: avg },
                { label: 'Peak Load', data: peak }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}
