<?php
/**
 * Correction Controller - WITH MULTIPLE 33kV LOADING STRATEGIES
 * Path: /app/controllers/CorrectionController.php
 * 
 * WORKFLOW:
 * 1. UL1/UL2 → Request Correction
 * 2. UL3 → Concurs (First Review)
 * 3. UL4 → Approves (Final Approval)
 * 4. Database Updated
 */

require __DIR__ . '/../config/auth.php';
require __DIR__ . '/../models/Correction.php';
require __DIR__ . '/../models/LoadReading11kv.php';

$user = Auth::user();
$action = $_GET['action'] ?? 'list';

// ========== UL1/UL2: REQUEST CORRECTION ==========
if ($action === 'request') {
    Guard::requireRole(['UL1', 'UL2']);
    
    // Get user's feeders for dropdown
    $db = Database::connect();
    if ($user['role'] === 'UL1') {
        // 11kV FEEDERS - Works fine
        $feedersStmt = $db->prepare("
            SELECT fdr11kv_code, fdr11kv_name 
            FROM fdr11kv 
            WHERE iss_code = ?
            ORDER BY fdr11kv_name
        ");
        $feedersStmt->execute([$user['iss_code']]);
        $feeders = $feedersStmt->fetchAll(PDO::FETCH_ASSOC);
        $correctionType = '11kV';
    } else { // UL2
        // 33kV FEEDERS - MULTIPLE STRATEGIES
        
        // STRATEGY 1: Try assigned_33kv_code (most likely)
        $feedersStmt = $db->prepare("
            SELECT fdr33kv_code, fdr33kv_name, ts_code
            FROM fdr33kv 
            WHERE fdr33kv_code = ?
            ORDER BY fdr33kv_name
        ");
        
        // Try different possible column names
        $possibleColumns = ['assigned_33kv_code', 'assigned_33kv', 'fdr33kv_code', 'assigned_fdr33kv_code'];
        $feeders = [];
        
        foreach ($possibleColumns as $col) {
            if (isset($user[$col]) && !empty($user[$col])) {
                $feedersStmt->execute([$user[$col]]);
                $feeders = $feedersStmt->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($feeders)) {
                    break; // Found feeders, stop trying
                }
            }
        }
        
        // STRATEGY 2: If still no feeders, load ALL 33kV feeders the user has access to
        if (empty($feeders)) {
            // Check if there's a ts_code assignment
            if (isset($user['ts_code']) && !empty($user['ts_code'])) {
                $feedersStmt = $db->prepare("
                    SELECT fdr33kv_code, fdr33kv_name, ts_code
                    FROM fdr33kv 
                    WHERE ts_code = ?
                    ORDER BY fdr33kv_name
                ");
                $feedersStmt->execute([$user['ts_code']]);
                $feeders = $feedersStmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
        
        // STRATEGY 3: If still empty, load all 33kV feeders (fallback)
        if (empty($feeders)) {
            $feedersStmt = $db->prepare("
                SELECT fdr33kv_code, fdr33kv_name, ts_code
                FROM fdr33kv 
                ORDER BY fdr33kv_name
            ");
            $feedersStmt->execute();
            $feeders = $feedersStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        $correctionType = '33kV';
    }
    
    require __DIR__ . '/../views/layout/header.php';
    require __DIR__ . '/../views/layout/sidebar.php';
    require __DIR__ . '/../views/corrections/request_form.php';
    require __DIR__ . '/../views/layout/footer.php';
    exit;
}

// ========== UL1/UL2: MY REQUESTS ==========
if ($action === 'my-requests') {
    Guard::requireRole(['UL1', 'UL2']);
    
    $myRequests = Correction::myRequests($user['payroll_id']);
    
    require __DIR__ . '/../views/layout/header.php';
    require __DIR__ . '/../views/layout/sidebar.php';
    require __DIR__ . '/../views/corrections/my_requests.php';
    require __DIR__ . '/../views/layout/footer.php';
    exit;
}

// ========== UL3: ANALYST CONCURRENCE ==========
if ($action === 'analyst-review') {
    Guard::requireAnalyst(); // UL3 ONLY
    
    $pendingCorrections = Correction::pending('PENDING');
    
    require __DIR__ . '/../views/layout/header.php';
    require __DIR__ . '/../views/layout/sidebar.php';
    require __DIR__ . '/../views/corrections/analyst_review.php';
    require __DIR__ . '/../views/layout/footer.php';
    exit;
}

// ========== UL4: MANAGER APPROVAL ==========
if ($action === 'manager-review') {
    Guard::requireManager(); // UL4 ONLY
    
    $analystApproved = Correction::pending('ANALYST_APPROVED');
    
    require __DIR__ . '/../views/layout/header.php';
    require __DIR__ . '/../views/layout/sidebar.php';
    require __DIR__ . '/../views/corrections/manager_review.php';
    require __DIR__ . '/../views/layout/footer.php';
    exit;
}

// Default: redirect to appropriate page based on role
if (in_array($user['role'], ['UL1', 'UL2'])) {
    header('Location: index.php?page=corrections&action=my-requests');
} elseif ($user['role'] === 'UL3') {
    header('Location: index.php?page=corrections&action=analyst-review');
} elseif ($user['role'] === 'UL4') {
    header('Location: index.php?page=corrections&action=manager-review');
} else {
    http_response_code(403);
    echo "Access Denied: Your role does not have access to corrections.";
}
exit;
