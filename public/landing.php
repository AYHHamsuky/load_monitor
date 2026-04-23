<!DOCTYPE html>
<html>
<head>
    <title>Kaduna Electric Load Reading Management System</title>

    <?php $bp = defined('BASE_PATH') ? BASE_PATH : '/load_monitor/public'; ?>
    <link rel="stylesheet" href="<?= $bp ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?= $bp ?>/assets/css/landing.css">
</head>
<body>

<div class="landing-wrapper">
    <header class="landing-header">
        <img src="<?= $bp ?>/assets/img/ke_logo.png" alt="Kaduna Electric" style="height:44px;vertical-align:middle;margin-right:10px;">
        Kaduna Electric Load Reading Management System
    </header>

    <section class="landing-hero">
        <h1>Real-Time Power Load & Interruption Monitoring</h1>
        <p>
            A centralized operational platform for capturing, validating,
            and analyzing 11KV and 33KV load data, feeder performance,
            and power interruptions — designed for utility operations,
            compliance, and decision intelligence.
        </p>

        <a class="landing-btn" href="index.php?page=login">
            Access System
        </a>
    </section>

    <section class="landing-features">
        <div>
            <h3>⚡ Load Reading</h3>
            <p>
                Hourly capture of 11KV and 33KV feeder loads with built-in
                validation and role-based access control.
            </p>
        </div>

        <div>
            <h3>🚨 Interruption Tracking</h3>
            <p>
                Structured logging of planned and forced outages,
                enabling SAIDI, SAIFI, and reliability analysis.
            </p>
        </div>

        <div>
            <h3>🧠 AI-Ready Analytics</h3>
            <p>
                Clean time-series data designed for forecasting,
                anomaly detection, and operational intelligence.
            </p>
        </div>
    </section>

    <footer class="landing-footer">
        © <?= date('Y') ?> Kaduna Electric – Load Reading Management System
    </footer>
</div>

</body>
</html>
