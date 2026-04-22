<?php
/**
 * 33kV Correction Controller
 * Path: /app/controllers/Correction33kvController.php
 * 
 * WORKFLOW FOR 33kV CORRECTIONS:
 * 1. UL2 → Request Correction
 * 2. UL3 → Concurs (First Review)
 * 3. UL4 → Approves (Final Approval)
 * 4. Database Updated
 */

require __DIR__ . '/../config/auth.php';
require __DIR__ . '/../models/Correction33kv.php';

$user = Auth::user();
$action = $_GET['action'] ?? 'list';

// ========== UL2: REQUEST CORRECTION ==========
if ($action === 'request') {
    Guard::requireUL2(); // Only UL2 can request 33kV corrections
    
    $db = Database::connect();
    
    // Get all transmission stations for dropdown
    $ts_stmt = $db->query("
        SELECT DISTINCT ts_code, station_name 
        FROM transmission_stations 
        ORDER BY station_name
    ");
    $transmission_stations = $ts_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user info
    $feeder_type = '33kV';
    
    require __DIR__ . '/../views/layout/header.php';
    require __DIR__ . '/../views/layout/sidebar.php';
    require __DIR__ . '/../views/corrections33kv/request_form.php';
    require __DIR__ . '/../views/layout/footer.php';
    exit;
}

// ========== UL2: MY REQUESTS ==========
if ($action === 'my-requests') {
    Guard::requireUL2();
    
    $myRequests = Correction33kv::myRequests($user['payroll_id']);
    
    require __DIR__ . '/../views/layout/header.php';
    require __DIR__ . '/../views/layout/sidebar.php';
    require __DIR__ . '/../views/corrections33kv/my_requests.php';
    require __DIR__ . '/../views/layout/footer.php';
    exit;
}

// ========== UL3: ANALYST CONCURRENCE ==========
if ($action === 'analyst-review') {
    Guard::requireAnalyst(); // UL3 ONLY
    
    $pendingCorrections = Correction33kv::pending('PENDING');
    
    require __DIR__ . '/../views/layout/header.php';
    require __DIR__ . '/../views/layout/sidebar.php';
    require __DIR__ . '/../views/corrections33kv/analyst_review.php';
    require __DIR__ . '/../views/layout/footer.php';
    exit;
}

// ========== UL4: MANAGER APPROVAL ==========
if ($action === 'manager-review') {
    Guard::requireManager(); // UL4 ONLY
    
    $analystApproved = Correction33kv::pending('ANALYST_APPROVED');
    
    require __DIR__ . '/../views/layout/header.php';
    require __DIR__ . '/../views/layout/sidebar.php';
    require __DIR__ . '/../views/corrections33kv/manager_review.php';
    require __DIR__ . '/../views/layout/footer.php';
    exit;
}

// Default: redirect to appropriate page based on role
if ($user['role'] === 'UL2') {
    header('Location: index.php?page=corrections33kv&action=my-requests');
} elseif ($user['role'] === 'UL3') {
    header('Location: index.php?page=corrections33kv&action=analyst-review');
} elseif ($user['role'] === 'UL4') {
    header('Location: index.php?page=corrections33kv&action=manager-review');
} else {
    http_response_code(403);
    echo "Access Denied: Your role does not have access to 33kV corrections.";
}
exit;
