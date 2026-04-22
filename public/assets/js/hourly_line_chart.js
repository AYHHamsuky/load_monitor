async function loadHourlyChart(type, feederCode, date) {
    const res = await fetch(
        `/api/hourly_load.php?type=${type}&code=${feederCode}&date=${date}`
    );
    const data = await res.json();

    const labels = data.map(d => `${d.entry_hour}:00`);
    const values = data.map(d => d.load_read);

    const ctx = document.getElementById('hourlyLineChart').getContext('2d');

    if (window.hourlyChart) window.hourlyChart.destroy();

    window.hourlyChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [{
                label: 'Load (MW)',
                data: values,
                borderWidth: 2,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}
