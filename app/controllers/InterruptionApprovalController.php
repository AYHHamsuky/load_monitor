<?php
/**
 * Interruption Approval Controller
 * Path: /app/controllers/InterruptionApprovalController.php
 * 
 * WORKFLOW:
 * 1. UL1/UL2 → Submit Interruption (if requires approval)
 * 2. UL3 → Concurs (First Review)
 * 3. UL4 → Approves (Final Approval)
 * 4. Interruption Status Updated
 */

require __DIR__ . '/../config/auth.php';
require __DIR__ . '/../models/InterruptionApproval.php';

$user = Auth::user();
$action = $_GET['action'] ?? 'list';

// ========== UL1/UL2: MY APPROVAL REQUESTS ==========
if ($action === 'my-requests') {
    Guard::requireRole(['UL1', 'UL2']);
    
    $myRequests = InterruptionApproval::myRequests($user['payroll_id']);
    
    require __DIR__ . '/../views/layout/header.php';
    require __DIR__ . '/../views/layout/sidebar.php';
    require __DIR__ . '/../views/interruption_approvals/my_requests.php';
    require __DIR__ . '/../views/layout/footer.php';
    exit;
}

// ========== UL3: ANALYST CONCURRENCE ==========
if ($action === 'analyst-review') {
    // Check if user is UL3
    if ($user['role'] !== 'UL3') {
        http_response_code(403);
        die('Access Denied: Only UL3 can review interruption approvals.');
    }
    
    $pendingApprovals = InterruptionApproval::getPendingForAnalyst();
    
    require __DIR__ . '/../views/layout/header.php';
    require __DIR__ . '/../views/layout/sidebar.php';
    require __DIR__ . '/../views/interruption_approvals/analyst_review.php';
    require __DIR__ . '/../views/layout/footer.php';
    exit;
}

// ========== UL4: MANAGER FINAL APPROVAL ==========
if ($action === 'manager-review') {
    // Check if user is UL4
    if ($user['role'] !== 'UL4') {
        http_response_code(403);
        die('Access Denied: Only UL4 can give final approval.');
    }
    
    $analystApproved = InterruptionApproval::getAnalystApprovedForManager();
    
    require __DIR__ . '/../views/layout/header.php';
    require __DIR__ . '/../views/layout/sidebar.php';
    require __DIR__ . '/../views/interruption_approvals/manager_review.php';
    require __DIR__ . '/../views/layout/footer.php';
    exit;
}

// Default: redirect to appropriate page based on role
if (in_array($user['role'], ['UL1', 'UL2'])) {
    header('Location: index.php?page=interruption_approvals&action=my-requests');
} elseif ($user['role'] === 'UL3') {
    header('Location: index.php?page=interruption_approvals&action=analyst-review');
} elseif ($user['role'] === 'UL4') {
    header('Location: index.php?page=interruption_approvals&action=manager-review');
} else {
    http_response_code(403);
    echo "Access Denied: Your role does not have access to interruption approvals.";
}
exit;
