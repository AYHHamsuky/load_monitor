<?php require __DIR__ . '/../../layout/header.php'; ?>
<?php require __DIR__ . '/../../layout/sidebar.php'; ?>

<div class="main-content">
<div class="um-wrap">

    <!-- Page Header -->
    <div class="um-header">
        <div>
            <h1 class="um-title">User Management</h1>
            <p class="um-sub">Manage system users and access control</p>
        </div>
        <div class="um-header-actions">
            <button onclick="openImportModal()" class="um-btn um-btn-import">
                <i class="fas fa-file-csv"></i> Import CSV
            </button>
            <a href="?page=users&action=create" class="um-btn um-btn-primary">
                <i class="fas fa-user-plus"></i> Add New User
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="um-card um-filters">
        <form method="GET" action="" class="um-filters-form">
            <input type="hidden" name="page" value="users">
            <input type="hidden" name="action" value="list">
            <div class="um-filter-item">
                <input type="text" name="search" placeholder="Search by ID, name or email…"
                       value="<?= htmlspecialchars($search) ?>" class="um-input">
            </div>
            <div class="um-filter-item">
                <select name="role" class="um-input">
                    <option value="">All Roles</option>
                    <option value="UL1" <?= $role_filter === 'UL1' ? 'selected' : '' ?>>UL1 – 11kV Entry</option>
                    <option value="UL2" <?= $role_filter === 'UL2' ? 'selected' : '' ?>>UL2 – 33kV Entry</option>
                    <option value="UL3" <?= $role_filter === 'UL3' ? 'selected' : '' ?>>UL3 – Analyst</option>
                    <option value="UL4" <?= $role_filter === 'UL4' ? 'selected' : '' ?>>UL4 – Manager</option>
                    <option value="UL5" <?= $role_filter === 'UL5' ? 'selected' : '' ?>>UL5 – Staff View</option>
                    <option value="UL6" <?= $role_filter === 'UL6' ? 'selected' : '' ?>>UL6 – Tech Admin</option>
                    <option value="UL7" <?= $role_filter === 'UL7' ? 'selected' : '' ?>>UL7 – System Admin</option>
                    <option value="UL8" <?= $role_filter === 'UL8' ? 'selected' : '' ?>>UL8 – Lead Dispatch</option>
                </select>
            </div>
            <div class="um-filter-item">
                <select name="status" class="um-input">
                    <option value="">All Status</option>
                    <option value="Yes" <?= $status_filter === 'Yes' ? 'selected' : '' ?>>Active</option>
                    <option value="No"  <?= $status_filter === 'No'  ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <button type="submit" class="um-btn um-btn-filter"><i class="fas fa-filter"></i> Filter</button>
            <a href="?page=users&action=list" class="um-btn um-btn-reset"><i class="fas fa-redo"></i> Reset</a>
        </form>
    </div>

    <!-- Stats bar -->
    <div class="um-statsbar">
        <span><b><?= $total_users ?></b> total users</span>
        <span><b><?= count($users) ?></b> shown</span>
        <span>Page <b><?= $page ?></b> of <b><?= $total_pages ?></b></span>
    </div>

    <!-- Table -->
    <div class="um-card um-table-wrap">
        <div class="um-scroll">
            <table class="um-table">
                <thead>
                    <tr>
                        <th>Payroll ID</th>
                        <th>Staff Name</th>
                        <th>Role</th>
                        <th>ISS / 33kV Assignment</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" class="um-empty">
                            <i class="fas fa-users fa-3x"></i>
                            <p>No users found</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($u['payroll_id']) ?></strong></td>
                            <td>
                                <div class="um-user-cell">
                                    <div class="um-avatar"><?= strtoupper(substr($u['staff_name'], 0, 1)) ?></div>
                                    <div>
                                        <div class="um-name"><?= htmlspecialchars($u['staff_name']) ?></div>
                                        <div class="um-email"><?= htmlspecialchars($u['email'] ?? '') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><span class="um-role-badge um-role-<?= strtolower($u['role']) ?>"><?= htmlspecialchars($u['role']) ?></span></td>
                            <td>
                                <?php if ($u['role'] === 'UL1'): ?>
                                    <small class="um-assign"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($u['iss_name']) ?></small>
                                <?php elseif ($u['role'] === 'UL2'): ?>
                                    <small class="um-assign"><i class="fas fa-bolt"></i> <?= htmlspecialchars($u['fdr33kv_name']) ?></small>
                                <?php else: ?>
                                    <span class="um-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><small class="um-muted"><?= htmlspecialchars($u['phone'] ?? '') ?></small></td>
                            <td>
                                <span class="um-status-badge <?= $u['is_active'] === 'Yes' ? 'active' : 'inactive' ?>">
                                    <?= $u['is_active'] === 'Yes' ? 'Active' : 'Inactive' ?>
                                </span>
                            </td>
                            <td><small class="um-muted"><?= $u['last_login'] ? date('d M Y', strtotime($u['last_login'])) : 'Never' ?></small></td>
                            <td>
                                <div class="um-actions">
                                    <a href="?page=users&action=edit&id=<?= urlencode($u['payroll_id']) ?>"
                                       class="um-act um-act-edit" title="Edit"><i class="fas fa-edit"></i></a>
                                    <button onclick="toggleStatus('<?= htmlspecialchars($u['payroll_id'], ENT_QUOTES) ?>','<?= $u['is_active'] ?>')"
                                            class="um-act <?= $u['is_active'] === 'Yes' ? 'um-act-deactivate' : 'um-act-activate' ?>"
                                            title="<?= $u['is_active'] === 'Yes' ? 'Deactivate' : 'Activate' ?>">
                                        <i class="fas fa-<?= $u['is_active'] === 'Yes' ? 'ban' : 'check' ?>"></i>
                                    </button>
                                    <button onclick="resetPassword('<?= htmlspecialchars($u['payroll_id'], ENT_QUOTES) ?>')"
                                            class="um-act um-act-reset" title="Reset Password">
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
    <div class="um-pagination">
        <?php if ($page > 1): ?>
            <a href="?page=users&action=list&p=<?= $page-1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role_filter) ?>&status=<?= urlencode($status_filter) ?>"
               class="um-page-btn"><i class="fas fa-chevron-left"></i> Prev</a>
        <?php endif; ?>
        <span class="um-page-info">Page <?= $page ?> of <?= $total_pages ?></span>
        <?php if ($page < $total_pages): ?>
            <a href="?page=users&action=list&p=<?= $page+1 ?>&search=<?= urlencode($search) ?>&role=<?= urlencode($role_filter) ?>&status=<?= urlencode($status_filter) ?>"
               class="um-page-btn">Next <i class="fas fa-chevron-right"></i></a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>
</div>

<!-- CSV Import Modal -->
<div id="importModal" class="um-modal-overlay" style="display:none;">
    <div class="um-modal">
        <div class="um-modal-header">
            <h2><i class="fas fa-file-csv"></i> Import Users from CSV</h2>
            <button onclick="closeImportModal()" class="um-modal-close"><i class="fas fa-times"></i></button>
        </div>
        <div class="um-modal-body">

            <div class="um-import-info">
                <i class="fas fa-info-circle"></i>
                <div>
                    <strong>Supported formats (auto-detected):</strong><br>
                    <span style="font-size:12px;">
                    &bull; <strong>KE Staff DB (21 cols):</strong>
                    <code>S/N, Full Name, Payroll ID, &hellip;, Official Email, Active Phone, Marital Status</code><br>
                    &bull; <strong>Simple (8 cols):</strong>
                    <code>Full Name, Payroll ID, Last name, Middle Name, First Name, Department, Unit, Job Role Title</code>
                    </span><br>
                    The header row is automatically skipped. Existing payroll IDs are skipped without error.
                    Real emails and phone numbers are imported when available.
                </div>
            </div>

            <form id="importForm" enctype="multipart/form-data">
                <div class="um-form-row">
                    <label>CSV File *</label>
                    <input type="file" name="csv_file" id="csv_file" accept=".csv" class="um-input" required>
                </div>

                <div class="um-form-row">
                    <label>Assign Role to All Imported Users *</label>
                    <select name="role" id="importRole" class="um-input" required onchange="updateImportLocation()">
                        <option value="">-- Select Role --</option>
                        <option value="UL1">UL1 – 11kV Data Entry</option>
                        <option value="UL2">UL2 – 33kV Data Entry</option>
                        <option value="UL3">UL3 – Analyst</option>
                        <option value="UL4">UL4 – Manager</option>
                        <option value="UL5" selected>UL5 – Staff View (safe default)</option>
                        <option value="UL6">UL6 – Tech Admin</option>
                        <option value="UL7">UL7 – System Admin</option>
                        <option value="UL8">UL8 – Lead Dispatch</option>
                    </select>
                </div>

                <!-- UL1 ISS picker -->
                <div id="importIssRow" class="um-form-row" style="display:none;">
                    <label>ISS Location (required for UL1) *</label>
                    <select name="iss_code" id="importIssCode" class="um-input">
                        <option value="">-- Select ISS --</option>
                        <?php foreach ($iss_list as $iss): ?>
                            <option value="<?= htmlspecialchars($iss['iss_code']) ?>">
                                <?= htmlspecialchars($iss['iss_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- UL2 33kV picker -->
                <div id="importFdrRow" class="um-form-row" style="display:none;">
                    <label>33kV Feeder (required for UL2) *</label>
                    <select name="assigned_33kv_code" id="importFdrCode" class="um-input">
                        <option value="">-- Select 33kV Feeder --</option>
                        <?php foreach ($feeders_33kv as $f): ?>
                            <option value="<?= htmlspecialchars($f['fdr33kv_code']) ?>">
                                <?= htmlspecialchars($f['fdr33kv_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="um-form-row">
                    <label>Default Password *</label>
                    <input type="text" name="default_password" value="password@123" class="um-input" required minlength="6">
                    <small>All imported users will get this password. Advise them to change it on first login.</small>
                </div>

                <div id="importResult" class="um-import-result" style="display:none;"></div>

                <div class="um-modal-footer">
                    <button type="button" onclick="closeImportModal()" class="um-btn um-btn-reset">Cancel</button>
                    <button type="submit" id="importSubmitBtn" class="um-btn um-btn-primary">
                        <i class="fas fa-upload"></i> Import Now
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
:root {
    --ke-dark:   #004B23;
    --ke-medium: #008000;
    --ke-light:  #6CAE27;
    --ke-bg:     #f4f9f0;
    --ke-border: #d4eabf;
}

.um-wrap { padding: 28px 32px; max-width: 1600px; margin: 0 auto; }

/* Header */
.um-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
.um-title  { color:var(--ke-dark); margin:0 0 4px; font-size:1.6rem; font-weight:700; }
.um-sub    { color:#6c757d; margin:0; }
.um-header-actions { display:flex; gap:10px; }

/* Buttons */
.um-btn {
    display:inline-flex; align-items:center; gap:7px;
    padding:10px 20px; border-radius:7px; font-weight:600;
    font-size:14px; cursor:pointer; text-decoration:none; border:none;
    transition:all .25s;
}
.um-btn-primary  { background:var(--ke-dark); color:#fff; }
.um-btn-primary:hover  { background:var(--ke-medium); }
.um-btn-import   { background:var(--ke-light); color:#fff; }
.um-btn-import:hover   { background:#5a9420; }
.um-btn-filter   { background:var(--ke-dark); color:#fff; }
.um-btn-reset    { background:#6c757d; color:#fff; }

/* Card */
.um-card { background:#fff; border-radius:10px; box-shadow:0 2px 10px rgba(0,75,35,.08); }

/* Filters */
.um-filters { padding:18px 20px; margin-bottom:16px; }
.um-filters-form { display:flex; gap:12px; flex-wrap:wrap; align-items:center; }
.um-filter-item  { flex:1; min-width:180px; }
.um-input {
    width:100%; padding:10px 13px; border:2px solid var(--ke-border);
    border-radius:7px; font-size:14px; background:#f8fdf4;
    transition:border-color .25s; box-sizing:border-box;
}
.um-input:focus { outline:none; border-color:var(--ke-light); }

/* Stats bar */
.um-statsbar {
    display:flex; gap:24px; padding:12px 18px;
    background:linear-gradient(90deg, var(--ke-dark), var(--ke-medium));
    border-radius:7px; color:#fff; margin-bottom:16px; font-size:14px;
}
.um-statsbar b { font-weight:700; }

/* Table */
.um-table-wrap { overflow:hidden; margin-bottom:20px; }
.um-scroll { overflow-x:auto; }
.um-table { width:100%; border-collapse:collapse; }
.um-table thead { background:var(--ke-dark); }
.um-table th {
    padding:13px 12px; text-align:left; color:#fff;
    font-size:12px; font-weight:700; text-transform:uppercase;
    letter-spacing:.5px; white-space:nowrap;
}
.um-table td { padding:13px 12px; border-bottom:1px solid #f0f0f0; vertical-align:middle; }
.um-table tbody tr:hover { background:var(--ke-bg); }

/* User cell */
.um-user-cell { display:flex; align-items:center; gap:10px; }
.um-avatar {
    width:38px; height:38px; border-radius:50%;
    background:linear-gradient(135deg, var(--ke-dark), var(--ke-medium));
    color:#fff; display:flex; align-items:center; justify-content:center;
    font-weight:700; font-size:15px; flex-shrink:0;
}
.um-name  { font-weight:600; color:#1a1a1a; font-size:14px; }
.um-email { font-size:12px; color:#6c757d; }
.um-muted { color:#adb5bd; font-size:13px; }
.um-assign { color:var(--ke-dark); font-size:13px; }
.um-assign i { margin-right:4px; color:var(--ke-light); }

/* Role badges */
.um-role-badge {
    padding:3px 10px; border-radius:10px; font-size:11px;
    font-weight:700; text-transform:uppercase; letter-spacing:.4px;
}
.um-role-ul1 { background:#d1ecf1; color:#0c5460; }
.um-role-ul2 { background:#ffeeba; color:#856404; }
.um-role-ul3 { background:#d4edda; color:#155724; }
.um-role-ul4 { background:#cce5ff; color:#004085; }
.um-role-ul5 { background:#e2e3e5; color:#383d41; }
.um-role-ul6 { background:#f8d7da; color:#721c24; }
.um-role-ul7 { background:#d6d8db; color:#1b1e21; }
.um-role-ul8 { background:#e2d9f3; color:#4a235a; }

/* Status badges */
.um-status-badge { padding:3px 10px; border-radius:10px; font-size:11px; font-weight:700; }
.um-status-badge.active   { background:#d4edda; color:#155724; }
.um-status-badge.inactive { background:#f8d7da; color:#721c24; }

/* Action buttons */
.um-actions { display:flex; gap:6px; }
.um-act {
    padding:5px 9px; border:none; border-radius:6px; cursor:pointer;
    font-size:13px; transition:all .2s; text-decoration:none;
    display:inline-flex; align-items:center; justify-content:center;
}
.um-act-edit       { background:#17a2b8; color:#fff; }
.um-act-deactivate { background:#dc3545; color:#fff; }
.um-act-activate   { background:#28a745; color:#fff; }
.um-act-reset      { background:#ffc107; color:#212529; }
.um-act:hover { opacity:.85; transform:translateY(-1px); }

/* Empty state */
.um-empty { text-align:center; padding:60px 20px; color:#6c757d; }
.um-empty i { display:block; color:var(--ke-border); margin-bottom:16px; }

/* Pagination */
.um-pagination { display:flex; justify-content:center; align-items:center; gap:16px; margin-top:24px; }
.um-page-btn {
    padding:9px 18px; background:var(--ke-dark); color:#fff;
    border-radius:7px; text-decoration:none; font-weight:600; transition:background .2s;
}
.um-page-btn:hover { background:var(--ke-medium); }
.um-page-info { color:#6c757d; font-weight:600; }

/* Modal */
.um-modal-overlay {
    position:fixed; inset:0; background:rgba(0,0,0,.5);
    display:flex; align-items:center; justify-content:center; z-index:9999;
}
.um-modal {
    background:#fff; border-radius:12px; width:95%; max-width:600px;
    box-shadow:0 20px 60px rgba(0,0,0,.25); max-height:90vh; overflow-y:auto;
}
.um-modal-header {
    display:flex; justify-content:space-between; align-items:center;
    padding:20px 24px; border-bottom:2px solid var(--ke-border);
    background:linear-gradient(90deg, var(--ke-dark), var(--ke-medium));
    border-radius:12px 12px 0 0; color:#fff;
}
.um-modal-header h2 { margin:0; font-size:1.1rem; display:flex; align-items:center; gap:8px; }
.um-modal-close { background:none; border:none; color:#fff; font-size:20px; cursor:pointer; }
.um-modal-body { padding:24px; }
.um-modal-footer { display:flex; justify-content:flex-end; gap:10px; margin-top:20px; padding-top:16px; border-top:1px solid #eee; }

.um-form-row { margin-bottom:18px; }
.um-form-row label { display:block; font-weight:600; color:var(--ke-dark); margin-bottom:6px; font-size:14px; text-transform:uppercase; font-size:12px; }
.um-form-row small { display:block; margin-top:5px; color:#6c757d; font-size:12px; }

.um-import-info {
    display:flex; gap:12px; padding:14px 16px;
    background:#f0faf0; border-left:4px solid var(--ke-light);
    border-radius:7px; margin-bottom:20px; font-size:13px; color:#444;
}
.um-import-info i { color:var(--ke-light); font-size:18px; flex-shrink:0; margin-top:2px; }
.um-import-info code { background:#e8f5e0; padding:2px 5px; border-radius:3px; font-size:12px; }

.um-import-result {
    padding:14px 16px; border-radius:7px; margin-top:16px;
    font-size:14px; border-left:4px solid;
}
.um-import-result.success { background:#d4edda; border-color:#28a745; color:#155724; }
.um-import-result.error   { background:#f8d7da; border-color:#dc3545; color:#721c24; }

@media(max-width:768px){
    .um-wrap { padding:16px; }
    .um-header { flex-direction:column; align-items:flex-start; gap:12px; }
    .um-statsbar { flex-direction:column; gap:6px; }
}
</style>

<script>
const BASE = '<?= BASE_PATH ?>';

function toggleStatus(payrollId, currentStatus) {
    const action = currentStatus === 'Yes' ? 'deactivate' : 'activate';
    const msg = currentStatus === 'Yes'
        ? 'Deactivate this user?' : 'Activate this user?';
    if (!confirm(msg)) return;

    fetch(BASE + '/ajax/user_toggle.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'payroll_id=' + encodeURIComponent(payrollId) + '&action=' + action
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) { location.reload(); }
        else { alert('Error: ' + data.message); }
    });
}

function resetPassword(payrollId) {
    if (!confirm("Reset this user's password? A new temporary password will be generated.")) return;

    fetch(BASE + '/ajax/user_reset_password.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'payroll_id=' + encodeURIComponent(payrollId)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Password reset!\n\nNew Password: ' + data.new_password + '\n\nShare securely with the user.');
        } else {
            alert('Error: ' + data.message);
        }
    });
}

function openImportModal()  { document.getElementById('importModal').style.display = 'flex'; }
function closeImportModal() {
    document.getElementById('importModal').style.display = 'none';
    document.getElementById('importResult').style.display = 'none';
    document.getElementById('importForm').reset();
    updateImportLocation();
}

function updateImportLocation() {
    const role = document.getElementById('importRole').value;
    document.getElementById('importIssRow').style.display = role === 'UL1' ? 'block' : 'none';
    document.getElementById('importFdrRow').style.display = role === 'UL2' ? 'block' : 'none';
}

document.getElementById('importForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('importSubmitBtn');
    const resultDiv = document.getElementById('importResult');

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Importing…';

    const formData = new FormData(this);

    fetch(BASE + '/ajax/user_import_csv.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        resultDiv.style.display = 'block';
        resultDiv.className = 'um-import-result ' + (data.success ? 'success' : 'error');
        resultDiv.innerHTML = data.message;
        if (data.success) {
            btn.innerHTML = '<i class="fas fa-check"></i> Done';
            setTimeout(() => location.reload(), 2000);
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-upload"></i> Import Now';
        }
    })
    .catch(() => {
        resultDiv.style.display = 'block';
        resultDiv.className = 'um-import-result error';
        resultDiv.textContent = 'Network error. Please try again.';
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-upload"></i> Import Now';
    });
});
</script>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
