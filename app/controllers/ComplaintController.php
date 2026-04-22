<?php
// app/controllers/ComplaintController.php

Guard::requireLogin();

$user = Auth::user();
$db = Database::connect();

// Get action
$action = $_GET['action'] ?? 'list';

// Role-based access control
$can_create = in_array($user['role'], ['UL1', 'UL2']); // Only UL1 & UL2 can create
$can_assign = ($user['role'] === 'UL3'); // Only UL3 can assign
$can_approve = ($user['role'] === 'UL4'); // Only UL4 can approve assignments

switch ($action) {
    case 'list':
        // Get filters
        $status_filter = $_GET['status'] ?? '';
        $priority_filter = $_GET['priority'] ?? '';
        
        $where_clauses = [];
        $params = [];
        
        if (!empty($status_filter)) {
            $where_clauses[] = "status = ?";
            $params[] = $status_filter;
        }
        
        if (!empty($priority_filter)) {
            $where_clauses[] = "priority = ?";
            $params[] = $priority_filter;
        }
        
        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
        
        // Get complaints
        $stmt = $db->prepare("
            SELECT 
                c.*,
                s.staff_name as logged_by_name,
                a.staff_name as assigned_to_name
            FROM complaint_log c
            LEFT JOIN staff_details s ON c.logged_by = s.payroll_id
            LEFT JOIN staff_details a ON c.assigned_to = a.payroll_id
            $where_sql
            ORDER BY c.logged_at DESC, c.complaint_ref DESC
            LIMIT 100
        ");
        $stmt->execute($params);
        $complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get statistics
        $stmt = $db->query("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'Assigned' THEN 1 ELSE 0 END) as assigned,
                SUM(CASE WHEN status = 'In Progress' THEN 1 ELSE 0 END) as in_progress,
                SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) as resolved,
                SUM(CASE WHEN status = 'Closed' THEN 1 ELSE 0 END) as closed,
                SUM(CASE WHEN priority = 'Critical' THEN 1 ELSE 0 END) as critical
            FROM complaint_log
        ");
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        require __DIR__ . '/../views/complaints/index.php';
        break;
        
    case 'log':
        // Only UL1 and UL2 can log complaints
        if (!$can_create) {
            die('Access denied. Only UL1 and UL2 can log complaints.');
        }
        
        require __DIR__ . '/../views/complaints/log_form.php';
        break;
        
    case 'view':
        $complaint_id = $_GET['id'] ?? null;
        
        if (!$complaint_id) {
            header('Location: ?page=complaints');
            exit;
        }
        
        // Get complaint details
        $stmt = $db->prepare("
            SELECT 
                c.*,
                s.staff_name as logged_by_name,
                a.staff_name as assigned_to_name
            FROM complaint_log c
            LEFT JOIN staff_details s ON c.logged_by = s.payroll_id
            LEFT JOIN staff_details a ON c.assigned_to = a.payroll_id
            WHERE c.complaint_ref = ?
        ");
        $stmt->execute([$complaint_id]);
        $complaint = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$complaint) {
            header('Location: ?page=complaints');
            exit;
        }
        
        // Get available staff for assignment (only active staff)
        $staff_stmt = $db->query("
            SELECT payroll_id, staff_name, role
            FROM staff_details
            WHERE is_active = 'Yes'
            AND role IN ('UL1', 'UL2')
            ORDER BY staff_name
        ");
        $available_staff = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        require __DIR__ . '/../views/complaints/view_detail.php';
        break;
        
    default:
        header('Location: ?page=complaints');
        break;
}
