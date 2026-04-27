<?php require __DIR__ . '/../../layout/header.php'; ?>
<?php require __DIR__ . '/../../layout/sidebar.php'; ?>

<div class="main-content">
<div class="ue-wrap">

    <div class="ue-header">
        <div>
            <h1 class="ue-title">Edit User</h1>
            <p class="ue-sub">Update user information and access rights</p>
        </div>
        <a href="?page=users" class="ue-btn ue-btn-back">
            <i class="fas fa-arrow-left"></i> Back to Users
        </a>
    </div>

    <div class="ue-card">
        <form id="editUserForm">
            <input type="hidden" name="payroll_id" value="<?= htmlspecialchars($edit_user['payroll_id']) ?>">

            <!-- Basic Information -->
            <div class="ue-section">
                <h3 class="ue-section-title"><i class="fas fa-user"></i> Basic Information</h3>
                <div class="ue-row">
                    <div class="ue-group">
                        <label>Payroll ID</label>
                        <input type="text" value="<?= htmlspecialchars($edit_user['payroll_id']) ?>"
                               class="ue-input ue-disabled" disabled>
                        <small>Payroll ID cannot be changed</small>
                    </div>
                    <div class="ue-group">
                        <label>Full Name *</label>
                        <input type="text" name="staff_name" class="ue-input" required
                               value="<?= htmlspecialchars($edit_user['staff_name']) ?>">
                    </div>
                </div>
                <div class="ue-row">
                    <div class="ue-group">
                        <label>User Role *</label>
                        <select id="roleSelect" name="role" class="ue-input" required>
                            <option value="">-- Select Role --</option>
                            <?php
                            $roles = [
                                'UL1' => 'UL1 – 11kV Data Entry Staff',
                                'UL2' => 'UL2 – 33kV Data Entry Staff',
                                'UL3' => 'UL3 – Analyst',
                                'UL4' => 'UL4 – Manager',
                                'UL5' => 'UL5 – Staff View (Read-Only)',
                                'UL6' => 'UL6 – Tech Admin',
                                'UL7' => 'UL7 – System Administrator',
                                'UL8' => 'UL8 – Lead Dispatch',
                            ];
                            foreach ($roles as $val => $label):
                            ?>
                            <option value="<?= $val ?>" <?= $edit_user['role'] === $val ? 'selected' : '' ?>>
                                <?= $label ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="ue-group">
                        <label>Staff Level</label>
                        <input type="text" name="staff_level" class="ue-input"
                               value="<?= htmlspecialchars($edit_user['staff_level'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Assignment -->
            <div class="ue-section" id="assignSection">
                <h3 class="ue-section-title"><i class="fas fa-map-marker-alt"></i> Location Assignment</h3>

                <div id="ul1Row" style="display:<?= $edit_user['role'] === 'UL1' ? 'block' : 'none' ?>;">
                    <div class="ue-group">
                        <label>ISS Location *</label>
                        <select name="iss_code" id="issCode" class="ue-input"
                                <?= $edit_user['role'] === 'UL1' ? 'required' : '' ?>>
                            <option value="">-- Select ISS --</option>
                            <?php foreach ($iss_list as $iss): ?>
                                <option value="<?= htmlspecialchars($iss['iss_code']) ?>"
                                        <?= $edit_user['iss_code'] === $iss['iss_code'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($iss['iss_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small>UL1 users are assigned to an ISS location</small>
                    </div>
                </div>

                <div id="ul2Row" style="display:<?= $edit_user['role'] === 'UL2' ? 'block' : 'none' ?>;">
                    <div class="ue-group">
                        <label>33kV Feeder *</label>
                        <select name="assigned_33kv_code" id="fdrCode" class="ue-input"
                                <?= $edit_user['role'] === 'UL2' ? 'required' : '' ?>>
                            <option value="">-- Select 33kV Feeder --</option>
                            <?php foreach ($feeders_33kv as $f): ?>
                                <option value="<?= htmlspecialchars($f['fdr33kv_code']) ?>"
                                        <?= $edit_user['assigned_33kv_code'] === $f['fdr33kv_code'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($f['fdr33kv_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small>UL2 users are assigned to a 33kV feeder</small>
                    </div>
                </div>

                <div id="noLocRow" style="display:<?= in_array($edit_user['role'], ['UL3','UL4','UL5','UL6','UL7','UL8']) ? 'block' : 'none' ?>;">
                    <div class="ue-info-box">
                        <i class="fas fa-info-circle"></i>
                        <p>This role does not require an ISS or feeder assignment.</p>
                    </div>
                </div>

                <?php if (!in_array($edit_user['role'], ['UL1','UL2','UL3','UL4','UL5','UL6','UL7','UL8'])): ?>
                <div id="noLocRow" style="display:block;">
                    <div class="ue-info-box">
                        <i class="fas fa-info-circle"></i>
                        <p>Select a role above to configure assignment.</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Contact -->
            <div class="ue-section">
                <h3 class="ue-section-title"><i class="fas fa-address-card"></i> Contact Information</h3>
                <div class="ue-row">
                    <div class="ue-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" class="ue-input"
                               value="<?= htmlspecialchars($edit_user['phone'] ?? '') ?>">
                    </div>
                    <div class="ue-group">
                        <label>Email Address</label>
                        <input type="email" name="email" class="ue-input"
                               value="<?= htmlspecialchars($edit_user['email'] ?? '') ?>">
                        <small>Leave blank to keep existing email</small>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="ue-section">
                <h3 class="ue-section-title"><i class="fas fa-toggle-on"></i> Account Status</h3>
                <div class="ue-group">
                    <label class="ue-checkbox-label">
                        <input type="checkbox" name="is_active" value="Yes"
                               <?= $edit_user['is_active'] === 'Yes' ? 'checked' : '' ?>>
                        <span>Account is active (user can log in)</span>
                    </label>
                </div>
            </div>

            <!-- Password change -->
            <div class="ue-section">
                <h3 class="ue-section-title"><i class="fas fa-key"></i> Change Password <small style="font-weight:400;font-size:.8em;">(optional)</small></h3>
                <div class="ue-info-box ue-info-warn">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Leave blank to keep the current password.</p>
                </div>
                <div class="ue-row" style="margin-top:14px;">
                    <div class="ue-group">
                        <label>New Password</label>
                        <input type="password" id="pwd" name="new_password" class="ue-input"
                               minlength="6" placeholder="Min. 6 characters">
                    </div>
                    <div class="ue-group">
                        <label>Confirm New Password</label>
                        <input type="password" id="pwd2" name="confirm_new_password" class="ue-input"
                               minlength="6" placeholder="Re-enter new password">
                    </div>
                </div>
            </div>

            <div class="ue-form-actions">
                <a href="?page=users" class="ue-btn ue-btn-cancel">Cancel</a>
                <button type="submit" class="ue-btn ue-btn-submit">
                    <i class="fas fa-save"></i> Update User
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
    --ke-border: #d4eabf;
    --ke-bg:     #f8fdf4;
}
.ue-wrap { padding:28px 32px; max-width:900px; margin:0 auto; }
.ue-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
.ue-title  { color:var(--ke-dark); margin:0 0 4px; font-size:1.5rem; font-weight:700; }
.ue-sub    { color:#6c757d; margin:0; }
.ue-btn {
    display:inline-flex; align-items:center; gap:7px;
    padding:10px 20px; border-radius:7px; font-weight:600;
    font-size:14px; cursor:pointer; text-decoration:none; border:none; transition:all .25s;
}
.ue-btn-back   { background:#6c757d; color:#fff; }
.ue-btn-cancel { background:#e9ecef; color:#495057; }
.ue-btn-submit { background:var(--ke-dark); color:#fff; }
.ue-btn-submit:hover { background:var(--ke-medium); }
.ue-card { background:#fff; border-radius:10px; padding:30px; box-shadow:0 2px 10px rgba(0,75,35,.08); }
.ue-section { padding-bottom:24px; margin-bottom:24px; border-bottom:2px solid #f0f0f0; }
.ue-section:last-of-type { border-bottom:none; }
.ue-section-title {
    color:var(--ke-dark); margin:0 0 18px; font-size:1rem;
    display:flex; align-items:center; gap:8px; font-weight:700;
}
.ue-section-title i { color:var(--ke-light); }
.ue-row { display:grid; grid-template-columns:repeat(auto-fit, minmax(240px,1fr)); gap:18px; }
.ue-group { display:flex; flex-direction:column; margin-bottom:4px; }
.ue-group label {
    font-weight:600; color:var(--ke-dark); margin-bottom:6px;
    font-size:12px; text-transform:uppercase; letter-spacing:.4px;
}
.ue-group small { margin-top:5px; color:#6c757d; font-size:12px; }
.ue-input {
    padding:11px 13px; border:2px solid var(--ke-border); border-radius:7px;
    font-size:14px; background:var(--ke-bg); transition:border-color .25s;
}
.ue-input:focus { outline:none; border-color:var(--ke-light); box-shadow:0 0 0 3px rgba(108,174,39,.12); }
.ue-disabled { background:#e9ecef; cursor:not-allowed; opacity:.8; }
.ue-info-box {
    display:flex; align-items:center; gap:12px; padding:13px 16px;
    background:#f0faf0; border-left:4px solid var(--ke-light); border-radius:7px;
}
.ue-info-box i { color:var(--ke-light); font-size:17px; }
.ue-info-box p { margin:0; color:#444; font-size:14px; }
.ue-info-warn { background:#fff8e1; border-left-color:#ffc107; }
.ue-info-warn i { color:#ffc107; }
.ue-checkbox-label { display:flex; align-items:center; gap:10px; cursor:pointer; font-weight:500; color:#333; font-size:14px; }
.ue-checkbox-label input { width:17px; height:17px; cursor:pointer; accent-color:var(--ke-dark); }
.ue-form-actions {
    display:flex; justify-content:flex-end; gap:12px;
    margin-top:24px; padding-top:24px; border-top:2px solid #f0f0f0;
}
@media(max-width:768px){
    .ue-wrap { padding:16px; }
    .ue-header { flex-direction:column; align-items:flex-start; gap:10px; }
}
</style>

<script>
const BASE = '<?= BASE_PATH ?>';

document.getElementById('roleSelect').addEventListener('change', function () {
    const role = this.value;
    const ul1Row  = document.getElementById('ul1Row');
    const ul2Row  = document.getElementById('ul2Row');
    const noLocRow = document.getElementById('noLocRow');
    const issCode  = document.getElementById('issCode');
    const fdrCode  = document.getElementById('fdrCode');

    ul1Row.style.display = 'none';
    ul2Row.style.display = 'none';
    noLocRow.style.display = 'none';
    issCode.removeAttribute('required');
    fdrCode.removeAttribute('required');

    if (role === 'UL1') {
        ul1Row.style.display = 'block';
        issCode.setAttribute('required', 'required');
    } else if (role === 'UL2') {
        ul2Row.style.display = 'block';
        fdrCode.setAttribute('required', 'required');
    } else if (role) {
        noLocRow.style.display = 'block';
    }
});

document.getElementById('editUserForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const pwd  = document.getElementById('pwd').value;
    const pwd2 = document.getElementById('pwd2').value;
    if ((pwd || pwd2) && pwd !== pwd2) {
        alert('New passwords do not match!');
        return;
    }

    const btn = this.querySelector('[type=submit]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

    fetch(BASE + '/ajax/user_update.php', {
        method: 'POST',
        body: new FormData(this)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.href = '?page=users';
        } else {
            alert('Error: ' + data.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Update User';
        }
    })
    .catch(() => {
        alert('Network error. Please try again.');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Update User';
    });
});
</script>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
