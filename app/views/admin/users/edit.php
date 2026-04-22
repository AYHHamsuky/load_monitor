<?php require __DIR__ . '/../../layout/header.php'; ?>
<?php require __DIR__ . '/../../layout/sidebar.php'; ?>

<div class="main-content">
    <div class="form-container">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>✏️ Edit User</h1>
                <p class="subtitle">Update user information and assignments</p>
            </div>
            <a href="?page=users" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Users
            </a>
        </div>

        <!-- Edit User Form -->
        <div class="form-card">
            <form id="editUserForm">
                <input type="hidden" name="payroll_id" value="<?= htmlspecialchars($edit_user['payroll_id']) ?>">
                
                <div class="form-sections">
                    <!-- Basic Information -->
                    <div class="form-section">
                        <h3><i class="fas fa-user"></i> Basic Information</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Payroll ID</label>
                                <input type="text" value="<?= htmlspecialchars($edit_user['payroll_id']) ?>" 
                                       class="form-control" disabled>
                                <small>Payroll ID cannot be changed</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="staff_name">Full Name *</label>
                                <input type="text" id="staff_name" name="staff_name" 
                                       class="form-control" required
                                       value="<?= htmlspecialchars($edit_user['staff_name']) ?>">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="role">User Role *</label>
                                <select id="role" name="role" class="form-control" required>
                                    <option value="">-- Select Role --</option>
                                    <option value="UL1" <?= $edit_user['role'] === 'UL1' ? 'selected' : '' ?>>UL1 - 11kV Data Entry Staff</option>
                                    <option value="UL2" <?= $edit_user['role'] === 'UL2' ? 'selected' : '' ?>>UL2 - 33kV Data Entry Staff</option>
                                    <option value="UL3" <?= $edit_user['role'] === 'UL3' ? 'selected' : '' ?>>UL3 - Analyst</option>
                                    <option value="UL4" <?= $edit_user['role'] === 'UL4' ? 'selected' : '' ?>>UL4 - Manager</option>
                                    <option value="UL5" <?= $edit_user['role'] === 'UL5' ? 'selected' : '' ?>>UL5 - Staff View (Read-Only)</option>
                                    <option value="UL6" <?= $edit_user['role'] === 'UL6' ? 'selected' : '' ?>>UL6 - System Administrator</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="staff_level">Staff Level</label>
                                <input type="text" id="staff_level" name="staff_level" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($edit_user['staff_level'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Assignment (Role-Based) -->
                    <div class="form-section" id="assignmentSection">
                        <h3><i class="fas fa-map-marker-alt"></i> Assignment</h3>
                        
                        <!-- For UL1 -->
                        <div id="ul1Assignment" style="display: <?= $edit_user['role'] === 'UL1' ? 'block' : 'none' ?>;">
                            <div class="form-group">
                                <label for="iss_code">ISS Location *</label>
                                <select id="iss_code" name="iss_code" class="form-control" 
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
                        
                        <!-- For UL2 -->
                        <div id="ul2Assignment" style="display: <?= $edit_user['role'] === 'UL2' ? 'block' : 'none' ?>;">
                            <div class="form-group">
                                <label for="assigned_33kv_code">33kV Feeder *</label>
                                <select id="assigned_33kv_code" name="assigned_33kv_code" class="form-control"
                                        <?= $edit_user['role'] === 'UL2' ? 'required' : '' ?>>
                                    <option value="">-- Select 33kV Feeder --</option>
                                    <?php foreach ($feeders_33kv as $feeder): ?>
                                        <option value="<?= htmlspecialchars($feeder['feeder_code']) ?>"
                                                <?= $edit_user['assigned_33kv_code'] === $feeder['feeder_code'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($feeder['feeder_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small>UL2 users are assigned to a 33kV feeder</small>
                            </div>
                        </div>
                        
                        <!-- For UL3-UL6 -->
                        <div id="noAssignment" style="display: <?= in_array($edit_user['role'], ['UL3', 'UL4', 'UL5', 'UL6']) ? 'block' : 'none' ?>;">
                            <div class="info-box">
                                <i class="fas fa-info-circle"></i>
                                <p>This role does not require ISS or feeder assignment.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="form-section">
                        <h3><i class="fas fa-address-card"></i> Contact Information</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone_no">Phone Number</label>
                                <input type="tel" id="phone_no" name="phone_no" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($edit_user['phone_no'] ?? '') ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" 
                                       class="form-control" 
                                       value="<?= htmlspecialchars($edit_user['email'] ?? '') ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Status -->
                    <div class="form-section">
                        <h3><i class="fas fa-toggle-on"></i> Account Status</h3>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_active" value="Yes" 
                                       <?= $edit_user['is_active'] === 'Yes' ? 'checked' : '' ?>>
                                <span>Account is active</span>
                            </label>
                            <small>Unchecking this will prevent the user from logging in</small>
                        </div>
                    </div>

                    <!-- Password Reset Section -->
                    <div class="form-section">
                        <h3><i class="fas fa-key"></i> Change Password (Optional)</h3>
                        
                        <div class="info-box warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>Leave blank to keep current password. Only fill if you want to change it.</p>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" 
                                       class="form-control"
                                       minlength="6"
                                       placeholder="Minimum 6 characters">
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_new_password">Confirm New Password</label>
                                <input type="password" id="confirm_new_password" name="confirm_new_password" 
                                       class="form-control"
                                       minlength="6"
                                       placeholder="Re-enter new password">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="?page=users" class="btn-cancel">Cancel</a>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-save"></i> Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.form-container {
    padding: 30px;
    max-width: 1000px;
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

.form-card {
    background: white;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.form-sections {
    display: flex;
    flex-direction: column;
    gap: 30px;
}

.form-section {
    padding-bottom: 30px;
    border-bottom: 2px solid #f0f0f0;
}

.form-section:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.form-section h3 {
    color: #2c3e50;
    margin: 0 0 20px 0;
    font-size: 18px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group label {
    font-weight: 600;
    color: #495057;
    margin-bottom: 8px;
    font-size: 14px;
}

.form-control {
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
}

.form-control:disabled {
    background: #e9ecef;
    cursor: not-allowed;
}

.form-group small {
    margin-top: 5px;
    color: #6c757d;
    font-size: 12px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-weight: 500;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.info-box {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px;
    background: #e7f3ff;
    border-left: 4px solid #007bff;
    border-radius: 8px;
    margin-bottom: 20px;
}

.info-box.warning {
    background: #fff3cd;
    border-left-color: #ffc107;
}

.info-box i {
    font-size: 20px;
    color: #007bff;
}

.info-box.warning i {
    color: #ffc107;
}

.info-box p {
    margin: 0;
    color: #495057;
}

.form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 15px;
    margin-top: 30px;
    padding-top: 30px;
    border-top: 2px solid #f0f0f0;
}

.btn-cancel, .btn-submit {
    padding: 12px 30px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-cancel {
    background: #e9ecef;
    color: #495057;
    border: none;
}

.btn-submit {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    border: none;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
}
</style>

<script>
// Role-based assignment visibility
document.getElementById('role').addEventListener('change', function() {
    const role = this.value;
    const ul1Assignment = document.getElementById('ul1Assignment');
    const ul2Assignment = document.getElementById('ul2Assignment');
    const noAssignment = document.getElementById('noAssignment');
    
    // Hide all
    ul1Assignment.style.display = 'none';
    ul2Assignment.style.display = 'none';
    noAssignment.style.display = 'none';
    
    // Clear required attributes
    document.getElementById('iss_code').removeAttribute('required');
    document.getElementById('assigned_33kv_code').removeAttribute('required');
    
    if (role === 'UL1') {
        ul1Assignment.style.display = 'block';
        document.getElementById('iss_code').setAttribute('required', 'required');
    } else if (role === 'UL2') {
        ul2Assignment.style.display = 'block';
        document.getElementById('assigned_33kv_code').setAttribute('required', 'required');
    } else if (role && ['UL3', 'UL4', 'UL5', 'UL6'].includes(role)) {
        noAssignment.style.display = 'block';
    }
});

// Form submission
document.getElementById('editUserForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Validate passwords match if provided
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_new_password').value;
    
    if (newPassword || confirmPassword) {
        if (newPassword !== confirmPassword) {
            alert('New passwords do not match!');
            return;
        }
    }
    
    const formData = new FormData(this);
    
    fetch('/public/ajax/user_update.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            window.location.href = '?page=users';
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('An error occurred. Please try again.');
        console.error(error);
    });
});
</script>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
