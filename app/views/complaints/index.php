<?php require __DIR__ . '/../layout/header.php'; ?>
<?php require __DIR__ . '/../layout/sidebar.php'; ?>

<div class="main-content">
    <div class="complaints-container">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>📢 Complaint Management</h1>
                <p class="subtitle">
                    <?php if ($can_create): ?>
                        Track and manage customer complaints
                    <?php elseif ($can_assign): ?>
                        Review and assign complaints to field staff
                    <?php elseif ($can_approve): ?>
                        Approve complaint assignments
                    <?php else: ?>
                        View complaint logs
                    <?php endif; ?>
                </p>
            </div>
            <?php if ($can_create): ?>
            <a href="?page=complaints&action=log" class="btn-primary">
                <i class="fas fa-plus"></i> Log New Complaint
            </a>
            <?php endif; ?>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card blue">
                <div class="stat-icon"><i class="fas fa-list"></i></div>
                <div class="stat-details">
                    <h3><?= $stats['total'] ?></h3>
                    <p>Total Complaints</p>
                </div>
            </div>
            
            <div class="stat-card orange">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-details">
                    <h3><?= $stats['pending'] ?></h3>
                    <p>Pending</p>
                </div>
            </div>
            
            <div class="stat-card purple">
                <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                <div class="stat-details">
                    <h3><?= $stats['assigned'] ?></h3>
                    <p>Assigned</p>
                </div>
            </div>
            
            <div class="stat-card info">
                <div class="stat-icon"><i class="fas fa-tasks"></i></div>
                <div class="stat-details">
                    <h3><?= $stats['in_progress'] ?></h3>
                    <p>In Progress</p>
                </div>
            </div>
            
            <div class="stat-card green">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-details">
                    <h3><?= $stats['resolved'] ?></h3>
                    <p>Resolved</p>
                </div>
            </div>
            
            <div class="stat-card danger">
                <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="stat-details">
                    <h3><?= $stats['critical'] ?></h3>
                    <p>Critical Priority</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters-card">
            <form method="GET" action="" class="filters-form">
                <input type="hidden" name="page" value="complaints">
                
                <div class="filter-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="">All Status</option>
                        <option value="Pending" <?= $status_filter === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Assigned" <?= $status_filter === 'Assigned' ? 'selected' : '' ?>>Assigned</option>
                        <option value="In Progress" <?= $status_filter === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                        <option value="Resolved" <?= $status_filter === 'Resolved' ? 'selected' : '' ?>>Resolved</option>
                        <option value="Closed" <?= $status_filter === 'Closed' ? 'selected' : '' ?>>Closed</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="priority">Priority</label>
                    <select name="priority" id="priority" class="form-control">
                        <option value="">All Priorities</option>
                        <option value="Low" <?= $priority_filter === 'Low' ? 'selected' : '' ?>>Low</option>
                        <option value="Medium" <?= $priority_filter === 'Medium' ? 'selected' : '' ?>>Medium</option>
                        <option value="High" <?= $priority_filter === 'High' ? 'selected' : '' ?>>High</option>
                        <option value="Critical" <?= $priority_filter === 'Critical' ? 'selected' : '' ?>>Critical</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-filter">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </form>
        </div>

        <!-- Complaints Table -->
        <div class="table-card">
            <table class="complaints-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Priority</th>
                        <th>Customer Info</th>
                        <th>Status</th>
                        <th>Logged By</th>
                        <th>Assigned To</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($complaints)): ?>
                    <tr>
                        <td colspan="9" class="text-center">
                            <div class="empty-state">
                                <i class="fas fa-inbox fa-3x"></i>
                                <p>No complaints found</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($complaints as $complaint): ?>
                        <tr>
                            <td><strong>#<?= $complaint['complaint_ref'] ?></strong></td>
                            <td><?= date('M j, Y', strtotime($complaint['logged_at'])) ?></td>
                            <td><?= htmlspecialchars($complaint['complaint_type']) ?></td>
                            <td>
                                <span class="priority-badge priority-<?= strtolower($complaint['priority']) ?>">
                                    <?= htmlspecialchars($complaint['priority']) ?>
                                </span>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($complaint['customer_name']) ?></div>
                                <small><?= htmlspecialchars($complaint['customer_phone']) ?></small>
                            </td>
                            <td>
                                <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $complaint['status'])) ?>">
                                    <?= htmlspecialchars($complaint['status']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($complaint['logged_by_name']) ?></td>
                            <td><?= $complaint['assigned_to_name'] ? htmlspecialchars($complaint['assigned_to_name']) : '-' ?></td>
                            <td>
                                <a href="?page=complaints&action=view&id=<?= $complaint['complaint_ref'] ?>" 
                                   class="btn-action view">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.main-content {
    margin-left: 260px;
    margin-top: 70px;
    padding: 0;
    min-height: calc(100vh - 70px);
}

.complaints-container {
    padding: 30px;
    max-width: 100%;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.page-header h1 {
    color: #2c3e50;
    margin: 0 0 5px 0;
}

.subtitle {
    color: #6c757d;
    margin: 0;
}

.btn-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    padding: 12px 24px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    gap: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.stat-card.blue { border-left: 4px solid #007bff; }
.stat-card.orange { border-left: 4px solid #fd7e14; }
.stat-card.purple { border-left: 4px solid #6f42c1; }
.stat-card.info { border-left: 4px solid #17a2b8; }
.stat-card.green { border-left: 4px solid #28a745; }
.stat-card.danger { border-left: 4px solid #dc3545; }

.stat-icon {
    font-size: 36px;
    opacity: 0.8;
}

.stat-card.blue .stat-icon { color: #007bff; }
.stat-card.orange .stat-icon { color: #fd7e14; }
.stat-card.purple .stat-icon { color: #6f42c1; }
.stat-card.info .stat-icon { color: #17a2b8; }
.stat-card.green .stat-icon { color: #28a745; }
.stat-card.danger .stat-icon { color: #dc3545; }

.stat-details h3 {
    margin: 0;
    font-size: 24px;
    color: #2c3e50;
}

.stat-details p {
    margin: 5px 0 0 0;
    color: #6c757d;
    font-size: 13px;
}

.filters-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.filters-form {
    display: grid;
    grid-template-columns: 1fr 1fr auto;
    gap: 15px;
    align-items: end;
}

.filter-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #495057;
}

.form-control {
    width: 100%;
    padding: 10px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
}

.btn-filter {
    padding: 10px 20px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.table-card {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.complaints-table {
    width: 100%;
    border-collapse: collapse;
}

.complaints-table thead {
    background: #f8f9fa;
}

.complaints-table th {
    padding: 15px 12px;
    text-align: left;
    font-size: 12px;
    font-weight: 700;
    color: #6c757d;
    text-transform: uppercase;
}

.complaints-table td {
    padding: 15px 12px;
    border-bottom: 1px solid #f0f0f0;
    font-size: 13px;
}

.priority-badge, .status-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
}

.priority-badge.priority-low { background: #d1ecf1; color: #0c5460; }
.priority-badge.priority-medium { background: #fff3cd; color: #856404; }
.priority-badge.priority-high { background: #f8d7da; color: #721c24; }
.priority-badge.priority-critical { background: #dc3545; color: white; }

.status-badge.status-pending { background: #fff3cd; color: #856404; }
.status-badge.status-assigned { background: #d1ecf1; color: #0c5460; }
.status-badge.status-in-progress { background: #d6d8db; color: #1b1e21; }
.status-badge.status-resolved { background: #d4edda; color: #155724; }
.status-badge.status-closed { background: #e2e3e5; color: #383d41; }

.btn-action {
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all 0.3s;
}

.btn-action.view {
    background: #007bff;
    color: white;
}

.empty-state {
    padding: 40px;
    text-align: center;
    color: #6c757d;
}

.empty-state i {
    color: #dee2e6;
    margin-bottom: 15px;
}

@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
    }
    
    .filters-form {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require __DIR__ . '/../layout/footer.php'; ?>
