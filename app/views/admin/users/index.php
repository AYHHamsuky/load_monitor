<?php require __DIR__ . '/../../layout/header.php'; ?>
<?php require __DIR__ . '/../../layout/sidebar.php'; ?>

<div class="main-content">
    <div class="users-container">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>👥 User Management</h1>
                <p class="subtitle">Manage system users and access control</p>
            </div>
            <a href="?page=users&action=create" class="btn-primary">
                <i class="fas fa-user-plus"></i> Add New User
            </a>
        </div>

        <!-- Filters -->
        <div class="filters-card">
            <form method="GET" action="" class="filters-form">
                <input type="hidden" name="page" value="users">
                <input type="hidden" name="action" value="list">
                
                <div class="filter-group">
                    <input type="text" name="search" placeholder="Search by ID, name, or email..." 
                           value="<?= htmlspecialchars($search) ?>" class="search-input">
                </div>
                
                <div class="filter-group">
                    <select name="role" class="filter-select">
                        <option value="">All Roles</option>
                        <option value="UL1" <?= $role_filter === 'UL1' ? 'selected' : '' ?>>UL1 - 11kV Entry</option>
                        <option value="UL2" <?= $role_filter === 'UL2' ? 'selected' : '' ?>>UL2 - 33kV Entry</option>
                        <option value="UL3" <?= $role_filter === 'UL3' ? 'selected' : '' ?>>UL3 - Analyst</option>
                        <option value="UL4" <?= $role_filter === 'UL4' ? 'selected' : '' ?>>UL4 - Manager</option>
                        <option value="UL5" <?= $role_filter === 'UL5' ? 'selected' : '' ?>>UL5 - Staff View</option>
                        <option value="UL6" <?= $role_filter === 'UL6' ? 'selected' : '' ?>>UL6 - Admin</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <select name="status" class="filter-select">
                        <option value="">All Status</option>
                        <option value="Yes" <?= $status_filter === 'Yes' ? 'selected' : '' ?>>Active</option>
                        <option value="No" <?= $status_filter === 'No' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                
                <button type="submit" class="btn-filter">
                    <i class="fas fa-filter"></i> Filter
                </button>
                
                <a href="?page=users&action=list" class="btn-reset">
                    <i class="fas fa-redo"></i> Reset
                </a>
            </form>
        </div>

        <!-- User Statistics -->
        <div class="stats-bar">
            <div class="stat-item">
                <span class="stat-label">Total Users:</span>
                <span class="stat-value"><?= $total_users ?></span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Showing:</span>
                <span class="stat-value"><?= count($users) ?> users</span>
            </div>
            <div class="stat-item">
                <span class="stat-label">Page:</span>
                <span class="stat-value"><?= $page ?> of <?= $total_pages ?></span>
            </div>
        </div>

        <!-- Users Table -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Payroll ID</th>
                            <th>Staff Name</th>
                            <th>Role</th>
                            <th>ISS Assignment</th>
                            <th>33kV Assignment</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="9" class="text-center">
                                <div class="empty-state">
                                    <i class="fas fa-users fa-3x"></i>
                                    <p>No users found</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($users as $u): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($u['payroll_id']) ?></strong></td>
                                <td>
                                    <div class="user-cell">
                                        <div class="user-avatar">
                                            <?= strtoupper(substr($u['staff_name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="user-name"><?= htmlspecialchars($u['staff_name']) ?></div>
                                            <div class="user-email"><?= htmlspecialchars($u['email'] ?? 'N/A') ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="role-badge role-<?= strtolower($u['role']) ?>">
                                        <?= htmlspecialchars($u['role']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($u['role'] === 'UL1'): ?>
                                        <span class="assignment-text">
                                            <?= htmlspecialchars($u['iss_name']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($u['role'] === 'UL2'): ?>
                                        <span class="assignment-text">
                                            <?= htmlspecialchars($u['fdr33kv_name']) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="contact-cell">
                                        <?php if (!empty($u['phone_no'])): ?>
                                            <div><i class="fas fa-phone"></i> <?= htmlspecialchars($u['phone_no']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge <?= $u['is_active'] === 'Yes' ? 'active' : 'inactive' ?>">
                                        <?= $u['is_active'] === 'Yes' ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        <?= $u['last_login'] ? date('M j, Y', strtotime($u['last_login'])) : 'Never' ?>
                                    </small>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="?page=users&action=edit&id=<?= urlencode($u['payroll_id']) ?>" 
                                           class="btn-action edit" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="toggleUserStatus('<?= htmlspecialchars($u['payroll_id']) ?>', '<?= $u['is_active'] ?>')" 
                                                class="btn-action <?= $u['is_active'] === 'Yes' ? 'deactivate' : 'activate' ?>" 
                                                title="<?= $u['is_active'] === 'Yes' ? 'Deactivate' : 'Activate' ?>">
                                            <i class="fas fa-<?= $u['is_active'] === 'Yes' ? 'ban' : 'check' ?>"></i>
                                        </button>
                                        <button onclick="resetPassword('<?= htmlspecialchars($u['payroll_id']) ?>')" 
                                                class="btn-action reset" title="Reset Password">
                                            <i class="fas fa-key"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=users&action=list&p=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role_filter) ?>&status=<?= urlencode($status_filter) ?>" 
                   class="page-btn">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            <?php endif; ?>
            
            <span class="page-info">Page <?= $page ?> of <?= $total_pages ?></span>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=users&action=list&p=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role_filter) ?>&status=<?= urlencode($status_filter) ?>" 
                   class="page-btn">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.users-container {
    padding: 30px;
    max-width: 1600px;
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
    transition: all 0.3s;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
}

.filters-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.filters-form {
    display: flex;
    gap: 15px;
    align-items: center;
    flex-wrap: wrap;
}

.filter-group {
    flex: 1;
    min-width: 200px;
}

.search-input, .filter-select {
    width: 100%;
    padding: 10px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s;
}

.search-input:focus, .filter-select:focus {
    outline: none;
    border-color: #007bff;
}

.btn-filter, .btn-reset {
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.btn-filter {
    background: #007bff;
    color: white;
    border: none;
}

.btn-reset {
    background: #6c757d;
    color: white;
    border: none;
}

.stats-bar {
    display: flex;
    gap: 30px;
    padding: 15px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 8px;
    color: white;
    margin-bottom: 20px;
}

.stat-item {
    display: flex;
    gap: 8px;
}

.stat-label {
    opacity: 0.9;
}

.stat-value {
    font-weight: 700;
}

.table-card {
    background: white;
    border-radius: 12px;
    padding: 0;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    overflow: hidden;
}

.table-responsive {
    overflow-x: auto;
}

.users-table {
    width: 100%;
    border-collapse: collapse;
}

.users-table thead {
    background: #f8f9fa;
}

.users-table th {
    padding: 15px 12px;
    text-align: left;
    font-size: 12px;
    font-weight: 700;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
}

.users-table td {
    padding: 15px 12px;
    border-bottom: 1px solid #f0f0f0;
    vertical-align: middle;
}

.user-cell {
    display: flex;
    align-items: center;
    gap: 12px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 16px;
}

.user-name {
    font-weight: 600;
    color: #2c3e50;
    font-size: 14px;
}

.user-email {
    font-size: 12px;
    color: #6c757d;
}

.role-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.role-badge.role-ul1 { background: #d1ecf1; color: #0c5460; }
.role-badge.role-ul2 { background: #f8d7da; color: #721c24; }
.role-badge.role-ul3 { background: #d4edda; color: #155724; }
.role-badge.role-ul4 { background: #fff3cd; color: #856404; }
.role-badge.role-ul5 { background: #e2e3e5; color: #383d41; }
.role-badge.role-ul6 { background: #d6d8db; color: #1b1e21; }

.assignment-text {
    font-size: 13px;
    color: #495057;
}

.text-muted {
    color: #adb5bd;
    font-size: 13px;
}

.contact-cell {
    font-size: 12px;
    color: #6c757d;
}

.contact-cell i {
    margin-right: 5px;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 700;
}

.status-badge.active {
    background: #d4edda;
    color: #155724;
}

.status-badge.inactive {
    background: #f8d7da;
    color: #721c24;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-action {
    padding: 6px 10px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 14px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-action.edit {
    background: #17a2b8;
    color: white;
}

.btn-action.deactivate {
    background: #dc3545;
    color: white;
}

.btn-action.activate {
    background: #28a745;
    color: white;
}

.btn-action.reset {
    background: #ffc107;
    color: #212529;
}

.btn-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.empty-state {
    padding: 60px 20px;
    text-align: center;
    color: #6c757d;
}

.empty-state i {
    color: #dee2e6;
    margin-bottom: 20px;
}

.pagination {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 20px;
    margin-top: 30px;
}

.page-btn {
    padding: 10px 20px;
    background: #007bff;
    color: white;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
}

.page-btn:hover {
    background: #0056b3;
}

.page-info {
    color: #6c757d;
    font-weight: 600;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .stats-bar {
        flex-direction: column;
        gap: 10px;
    }
}
</style>

<script>
function toggleUserStatus(payrollId, currentStatus) {
    const action = currentStatus === 'Yes' ? 'deactivate' : 'activate';
    const confirmMsg = currentStatus === 'Yes' 
        ? 'Are you sure you want to deactivate this user?' 
        : 'Are you sure you want to activate this user?';
    
    if (!confirm(confirmMsg)) return;
    
    fetch('/public/ajax/user_toggle_status.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `payroll_id=${encodeURIComponent(payrollId)}&action=${action}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function resetPassword(payrollId) {
    if (!confirm('Are you sure you want to reset this user\'s password? A new temporary password will be generated.')) {
        return;
    }
    
    fetch('/public/ajax/user_reset_password.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `payroll_id=${encodeURIComponent(payrollId)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Password reset successful!\n\nNew Password: ' + data.new_password + '\n\nPlease provide this to the user securely.');
        } else {
            alert('Error: ' + data.message);
        }
    });
}
</script>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
