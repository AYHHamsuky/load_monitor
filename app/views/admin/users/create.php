<?php require __DIR__ . '/../../layout/header.php'; ?>
<?php require __DIR__ . '/../../layout/sidebar.php'; ?>

<div class="main-content">
    <div class="form-container">
        <!-- Page Header -->
        <div class="page-header">
            <div>
                <h1>➕ Create New User</h1>
                <p class="subtitle">Add a new user to the system</p>
            </div>
            <a href="?page=users" class="btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Users
            </a>
        </div>

        <!-- Create User Form -->
        <div class="form-card">
            <form id="createUserForm">
                <div class="form-sections">
                    <!-- Basic Information -->
                    <div class="form-section">
                        <h3><i class="fas fa-user"></i> Basic Information</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="payroll_id">Payroll ID *</label>
                                <input type="text" id="payroll_id" name="payroll_id" 
                                       class="form-control" required
                                       placeholder="e.g., KE12345">
                                <small>Unique identifier for the user</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="staff_name">Full Name *</label>
                                <input type="text" id="staff_name" name="staff_name" 
                                       class="form-control" required
                                       placeholder="e.g., John Doe">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="role">User Role *</label>
                                <select id="role" name="role" class="form-control" required>
                                    <option value="">-- Select Role --</option>
                                    <option value="UL1">UL1 - 11kV Data Entry Staff</option>
                                    <option value="UL2">UL2 - 33kV Data Entry Staff</option>
                                    <option value="UL3">UL3 - Analyst</option>
                                    <option value="UL4">UL4 - Manager</option>
                                    <option value="UL5">UL5 - Staff View (Read-Only)</option>
                                    <option value="UL6">UL6 - Tech Admin</option>
                                    <option value="UL6">UL7 - System Administrator</option>
                                    <option value="UL6">UL8 - Lead - Dispatch</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="staff_level">Staff Level</label>
                                <input type="text" id="staff_level" name="staff_level" 
                                       class="form-control" 
                                       placeholder="e.g., Level 10">
                            </div>
                        </div>
                    </div>

                    <!-- Assignment (Role-Based) -->
                    <div class="form-section" id="assignmentSection" style="display: none;">
                        <h3><i class="fas fa-map-marker-alt"></i> Assignment</h3>
                        
                        <!-- For UL1 -->
                        <div id="ul1Assignment" style="display: none;">
                            <div class="form-group">
                                <label for="iss_code">ISS Location *</label>
                                <select id="iss_code" name="iss_code" class="form-control">
                                    <option value="">-- Select ISS --</option>
                                    <?php foreach ($iss_list as $iss): ?>
                                        <option value="<?= htmlspecialchars($iss['iss_code']) ?>">
                                            <?= htmlspecialchars($iss['iss_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small>UL1 users are assigned to an ISS location</small>
                            </div>
                        </div>
                        
                        <!-- For UL2 -->
                        <div id="ul2Assignment" style="display: none;">
                            <div class="form-group">
                                <label for="assigned_33kv_code">33kV Feeder *</label>
                                <select id="assigned_33kv_code" name="assigned_33kv_code" class="form-control">
                                    <option value="">-- Select 33kV Feeder --</option>
                                    <?php foreach ($feeders_33kv as $feeder): ?>
                                        <option value="<?= htmlspecialchars($feeder['feeder_code']) ?>">
                                            <?= htmlspecialchars($feeder['feeder_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small>UL2 users are assigned to a 33kV feeder</small>
                            </div>
                        </div>
                        
                        <!-- For UL3-UL6 -->
                        <div id="noAssignment" style="display: none;">
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
                                       placeholder="e.g., 08012345678">
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" 
                                       class="form-control" 
                                       placeholder="e.g., user@kadunaelectric.com">
                            </div>
                        </div>
                    </div>

                    <!-- Security -->
                    <div class="form-section">
                        <h3><i class="fas fa-lock"></i> Security</h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">Password *</label>
                                <input type="password" id="password" name="password" 
                                       class="form-control" required
                                       minlength="6"
                                       placeholder="Minimum 6 characters">
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm Password *</label>
                                <input type="password" id="confirm_password" name="confirm_password" 
                                       class="form-control" required
                                       minlength="6"
                                       placeholder="Re-enter password">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="checkbox-label">
                                <input type="checkbox" name="is_active" value="Yes" checked>
                                <span>Activate user immediately</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <a href="?page=users" class="btn-cancel">Cancel</a>
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-user-plus"></i> Create User
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
}

.info-box i {
    font-size: 20px;
    color: #007bff;
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
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    border: none;
}

.btn-submit:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
}
</style>

<script>
// Role-based assignment visibility
document.getElementById('role').addEventListener('change', function() {
    const role = this.value;
    const assignmentSection = document.getElementById('assignmentSection');
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
        assignmentSection.style.display = 'block';
        ul1Assignment.style.display = 'block';
        document.getElementById('iss_code').setAttribute('required', 'required');
    } else if (role === 'UL2') {
        assignmentSection.style.display = 'block';
        ul2Assignment.style.display = 'block';
        document.getElementById('assigned_33kv_code').setAttribute('required', 'required');
    } else if (role && ['UL3', 'UL4', 'UL5', 'UL6'].includes(role)) {
        assignmentSection.style.display = 'block';
        noAssignment.style.display = 'block';
    } else {
        assignmentSection.style.display = 'none';
    }
});

// Form submission
document.getElementById('createUserForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Validate passwords match
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (password !== confirmPassword) {
        alert('Passwords do not match!');
        return;
    }
    
    const formData = new FormData(this);
    
    fetch('/public/ajax/user_create.php', {
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
