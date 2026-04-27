<?php require __DIR__ . '/../../layout/header.php'; ?>
<?php require __DIR__ . '/../../layout/sidebar.php'; ?>

<div class="main-content">
<div class="uc-wrap">

    <div class="uc-header">
        <div>
            <h1 class="uc-title">Create New User</h1>
            <p class="uc-sub">Add a new user to the system</p>
        </div>
        <a href="?page=users" class="uc-btn uc-btn-back">
            <i class="fas fa-arrow-left"></i> Back to Users
        </a>
    </div>

    <div class="uc-card">
        <form id="createUserForm">
            <!-- Basic Information -->
            <div class="uc-section">
                <h3 class="uc-section-title"><i class="fas fa-user"></i> Basic Information</h3>
                <div class="uc-row">
                    <div class="uc-group">
                        <label>Payroll ID *</label>
                        <input type="text" name="payroll_id" class="uc-input" required placeholder="e.g. 104715">
                        <small>Unique staff identifier</small>
                    </div>
                    <div class="uc-group">
                        <label>Full Name *</label>
                        <input type="text" name="staff_name" class="uc-input" required placeholder="e.g. Amina Ibrahim">
                    </div>
                </div>
                <div class="uc-row">
                    <div class="uc-group">
                        <label>User Role *</label>
                        <select id="roleSelect" name="role" class="uc-input" required>
                            <option value="">-- Select Role --</option>
                            <option value="UL1">UL1 – 11kV Data Entry Staff</option>
                            <option value="UL2">UL2 – 33kV Data Entry Staff</option>
                            <option value="UL3">UL3 – Analyst</option>
                            <option value="UL4">UL4 – Manager</option>
                            <option value="UL5">UL5 – Staff View (Read-Only)</option>
                            <option value="UL6">UL6 – Tech Admin</option>
                            <option value="UL7">UL7 – System Administrator</option>
                            <option value="UL8">UL8 – Lead Dispatch</option>
                        </select>
                    </div>
                    <div class="uc-group">
                        <label>Staff Level</label>
                        <input type="text" name="staff_level" class="uc-input" placeholder="e.g. Level 10">
                    </div>
                </div>
            </div>

            <!-- Assignment (role-based) -->
            <div class="uc-section" id="assignSection" style="display:none;">
                <h3 class="uc-section-title"><i class="fas fa-map-marker-alt"></i> Location Assignment</h3>

                <div id="ul1Row" style="display:none;">
                    <div class="uc-group">
                        <label>ISS Location *</label>
                        <select name="iss_code" id="issCode" class="uc-input">
                            <option value="">-- Select ISS --</option>
                            <?php foreach ($iss_list as $iss): ?>
                                <option value="<?= htmlspecialchars($iss['iss_code']) ?>">
                                    <?= htmlspecialchars($iss['iss_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small>UL1 users are assigned to an ISS location (11kV feeders)</small>
                    </div>
                </div>

                <div id="ul2Row" style="display:none;">
                    <div class="uc-group">
                        <label>33kV Feeder *</label>
                        <select name="assigned_33kv_code" id="fdrCode" class="uc-input">
                            <option value="">-- Select 33kV Feeder --</option>
                            <?php foreach ($feeders_33kv as $f): ?>
                                <option value="<?= htmlspecialchars($f['fdr33kv_code']) ?>">
                                    <?= htmlspecialchars($f['fdr33kv_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small>UL2 users are assigned to a 33kV feeder</small>
                    </div>
                </div>

                <div id="noLocRow" style="display:none;">
                    <div class="uc-info-box">
                        <i class="fas fa-info-circle"></i>
                        <p>This role does not require an ISS or feeder assignment.</p>
                    </div>
                </div>
            </div>

            <!-- Contact -->
            <div class="uc-section">
                <h3 class="uc-section-title"><i class="fas fa-address-card"></i> Contact Information</h3>
                <div class="uc-row">
                    <div class="uc-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" class="uc-input" placeholder="08012345678">
                    </div>
                    <div class="uc-group">
                        <label>Email Address</label>
                        <input type="email" name="email" class="uc-input" placeholder="user@kadunaelectric.com">
                        <small>Leave blank to auto-generate a system email</small>
                    </div>
                </div>
            </div>

            <!-- Security -->
            <div class="uc-section">
                <h3 class="uc-section-title"><i class="fas fa-lock"></i> Security</h3>
                <div class="uc-row">
                    <div class="uc-group">
                        <label>Password *</label>
                        <input type="password" id="pwd" name="password" class="uc-input" required minlength="6" placeholder="Min. 6 characters">
                    </div>
                    <div class="uc-group">
                        <label>Confirm Password *</label>
                        <input type="password" id="pwd2" name="confirm_password" class="uc-input" required minlength="6" placeholder="Re-enter password">
                    </div>
                </div>
                <div class="uc-group">
                    <label class="uc-checkbox-label">
                        <input type="checkbox" name="is_active" value="Yes" checked>
                        <span>Activate account immediately</span>
                    </label>
                </div>
            </div>

            <!-- Actions -->
            <div class="uc-form-actions">
                <a href="?page=users" class="uc-btn uc-btn-cancel">Cancel</a>
                <button type="submit" class="uc-btn uc-btn-submit">
                    <i class="fas fa-user-plus"></i> Create User
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

.uc-wrap { padding:28px 32px; max-width:900px; margin:0 auto; }

.uc-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
.uc-title  { color:var(--ke-dark); margin:0 0 4px; font-size:1.5rem; font-weight:700; }
.uc-sub    { color:#6c757d; margin:0; }

.uc-btn {
    display:inline-flex; align-items:center; gap:7px;
    padding:10px 20px; border-radius:7px; font-weight:600;
    font-size:14px; cursor:pointer; text-decoration:none; border:none; transition:all .25s;
}
.uc-btn-back   { background:#6c757d; color:#fff; }
.uc-btn-cancel { background:#e9ecef; color:#495057; }
.uc-btn-submit { background:var(--ke-dark); color:#fff; }
.uc-btn-submit:hover { background:var(--ke-medium); }

.uc-card { background:#fff; border-radius:10px; padding:30px; box-shadow:0 2px 10px rgba(0,75,35,.08); }

.uc-section {
    padding-bottom:24px; margin-bottom:24px;
    border-bottom:2px solid #f0f0f0;
}
.uc-section:last-of-type { border-bottom:none; }

.uc-section-title {
    color:var(--ke-dark); margin:0 0 18px; font-size:1rem;
    display:flex; align-items:center; gap:8px; font-weight:700;
}
.uc-section-title i { color:var(--ke-light); }

.uc-row { display:grid; grid-template-columns:repeat(auto-fit, minmax(240px,1fr)); gap:18px; }
.uc-group { display:flex; flex-direction:column; margin-bottom:4px; }
.uc-group label {
    font-weight:600; color:var(--ke-dark); margin-bottom:6px;
    font-size:12px; text-transform:uppercase; letter-spacing:.4px;
}
.uc-group small { margin-top:5px; color:#6c757d; font-size:12px; }
.uc-input {
    padding:11px 13px; border:2px solid var(--ke-border); border-radius:7px;
    font-size:14px; background:var(--ke-bg); transition:border-color .25s;
}
.uc-input:focus { outline:none; border-color:var(--ke-light); box-shadow:0 0 0 3px rgba(108,174,39,.12); }

.uc-info-box {
    display:flex; align-items:center; gap:12px; padding:14px 16px;
    background:#f0faf0; border-left:4px solid var(--ke-light); border-radius:7px;
}
.uc-info-box i { color:var(--ke-light); font-size:18px; }
.uc-info-box p { margin:0; color:#444; font-size:14px; }

.uc-checkbox-label { display:flex; align-items:center; gap:10px; cursor:pointer; font-weight:500; color:#333; }
.uc-checkbox-label input { width:17px; height:17px; cursor:pointer; accent-color:var(--ke-dark); }

.uc-form-actions {
    display:flex; justify-content:flex-end; gap:12px;
    margin-top:24px; padding-top:24px; border-top:2px solid #f0f0f0;
}

@media(max-width:768px){
    .uc-wrap { padding:16px; }
    .uc-header { flex-direction:column; align-items:flex-start; gap:10px; }
}
</style>

<script>
const BASE = '<?= BASE_PATH ?>';

document.getElementById('roleSelect').addEventListener('change', function () {
    const role = this.value;
    const assignSection = document.getElementById('assignSection');
    const ul1Row = document.getElementById('ul1Row');
    const ul2Row = document.getElementById('ul2Row');
    const noLocRow = document.getElementById('noLocRow');
    const issCode = document.getElementById('issCode');
    const fdrCode = document.getElementById('fdrCode');

    ul1Row.style.display = 'none';
    ul2Row.style.display = 'none';
    noLocRow.style.display = 'none';
    issCode.removeAttribute('required');
    fdrCode.removeAttribute('required');

    if (!role) { assignSection.style.display = 'none'; return; }

    assignSection.style.display = 'block';
    if (role === 'UL1') {
        ul1Row.style.display = 'block';
        issCode.setAttribute('required', 'required');
    } else if (role === 'UL2') {
        ul2Row.style.display = 'block';
        fdrCode.setAttribute('required', 'required');
    } else {
        noLocRow.style.display = 'block';
    }
});

document.getElementById('createUserForm').addEventListener('submit', function (e) {
    e.preventDefault();

    const pwd  = document.getElementById('pwd').value;
    const pwd2 = document.getElementById('pwd2').value;
    if (pwd !== pwd2) { alert('Passwords do not match!'); return; }

    const btn = this.querySelector('[type=submit]');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating…';

    const formData = new FormData(this);

    fetch(BASE + '/ajax/user_create.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.href = '?page=users';
        } else {
            alert('Error: ' + data.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-user-plus"></i> Create User';
        }
    })
    .catch(() => {
        alert('Network error. Please try again.');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-user-plus"></i> Create User';
    });
});
</script>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
