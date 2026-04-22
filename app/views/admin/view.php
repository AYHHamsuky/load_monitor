<?php require __DIR__ . '/../layout/header.php'; ?>
<?php require __DIR__ . '/../layout/sidebar.php'; ?>

<div class="main-content">
    <div class="config-container">
        <div class="page-header">
            <div>
                <h1>⚙️ System Configuration</h1>
                <p class="subtitle">Manage system settings and preferences</p>
            </div>
            <a href="?page=dashboard" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <?php if (!empty($config_message)): ?>
        <div class="alert-info">
            <i class="fas fa-info-circle"></i>
            <?= htmlspecialchars($config_message) ?>
        </div>
        <?php endif; ?>

        <div class="config-sections">
            <!-- Database Backup -->
            <div class="config-card">
                <div class="card-header">
                    <h3><i class="fas fa-database"></i> Database Backup</h3>
                </div>
                <div class="card-body">
                    <p>Create a backup of the database</p>
                    <a href="?page=system-config&action=backup" class="btn-primary">
                        <i class="fas fa-download"></i> Backup Now
                    </a>
                </div>
            </div>

            <!-- System Info -->
            <div class="config-card">
                <div class="card-header">
                    <h3><i class="fas fa-info-circle"></i> System Information</h3>
                </div>
                <div class="card-body">
                    <table class="info-table">
                        <tr>
                            <td><strong>PHP Version:</strong></td>
                            <td><?= phpversion() ?></td>
                        </tr>
                        <tr>
                            <td><strong>Server Software:</strong></td>
                            <td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></td>
                        </tr>
                        <tr>
                            <td><strong>System Date:</strong></td>
                            <td><?= date('F j, Y H:i:s') ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.config-container {
    padding: 30px;
    max-width: 1200px;
    margin: 0 auto;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.page-header h1 {
    color: #2c3e50;
    margin: 0 0 5px 0;
}

.subtitle {
    color: #6c757d;
    margin: 0;
}

.btn-secondary {
    background: #6c757d;
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.alert-info {
    padding: 15px 20px;
    background: #d1ecf1;
    border-left: 4px solid #17a2b8;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.config-sections {
    display: grid;
    gap: 20px;
}

.config-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    overflow: hidden;
}

.card-header {
    background: #f8f9fa;
    padding: 20px;
    border-bottom: 2px solid #e9ecef;
}

.card-header h3 {
    margin: 0;
    color: #2c3e50;
    display: flex;
    align-items: center;
    gap: 10px;
}

.card-body {
    padding: 20px;
}

.btn-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    padding: 10px 20px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
}

.info-table {
    width: 100%;
    border-collapse: collapse;
}

.info-table td {
    padding: 10px;
    border-bottom: 1px solid #f0f0f0;
}

.info-table td:first-child {
    width: 200px;
}
</style>

<?php require __DIR__ . '/../layout/footer.php'; ?>
