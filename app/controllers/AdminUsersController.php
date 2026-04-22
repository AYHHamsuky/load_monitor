<?php
// app/controllers/AdminUsersController.php

Guard::requireAdmin(); // Only UL6

$user = Auth::user();
$db = Database::connect();

// Get action
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        // Pagination
        $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
        $per_page = 15;
        $offset = ($page - 1) * $per_page;
        
        // Search filter
        $search = $_GET['search'] ?? '';
        $role_filter = $_GET['role'] ?? '';
        $status_filter = $_GET['status'] ?? '';
        
        // Build query
        $where_clauses = [];
        $params = [];
        
        if (!empty($search)) {
            $where_clauses[] = "(payroll_id LIKE ? OR staff_name LIKE ? OR email LIKE ?)";
            $search_term = "%$search%";
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
        }
        
        if (!empty($role_filter)) {
            $where_clauses[] = "role = ?";
            $params[] = $role_filter;
        }
        
        if (!empty($status_filter)) {
            $where_clauses[] = "is_active = ?";
            $params[] = $status_filter;
        }
        
        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
        
        // Get total count
        $count_stmt = $db->prepare("SELECT COUNT(*) as total FROM staff_details $where_sql");
        $count_stmt->execute($params);
        $total_users = $count_stmt->fetch()['total'];
        $total_pages = ceil($total_users / $per_page);
        
        // Get users
        $stmt = $db->prepare("
            SELECT 
                payroll_id,
                staff_name,
                role,
                iss_code,
                assigned_33kv_code,
                phone,
                email,
                staff_level,
                is_active,
                created_at,
                last_login
            FROM staff_details
            $where_sql
            ORDER BY created_at DESC
            LIMIT $per_page OFFSET $offset
        ");
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get ISS and 33kV names for display
        foreach ($users as &$u) {
            if (!empty($u['iss_code'])) {
                $iss_stmt = $db->prepare("SELECT iss_name FROM iss_locations WHERE iss_code = ?");
                $iss_stmt->execute([$u['iss_code']]);
                $u['iss_name'] = $iss_stmt->fetch()['iss_name'] ?? 'N/A';
            } else {
                $u['iss_name'] = 'N/A';
            }
            
            if (!empty($u['assigned_33kv_code'])) {
                $fdr_stmt = $db->prepare("SELECT fdr33kv_name FROM fdr33kv WHERE fdr33kv_code = ?");
                $fdr_stmt->execute([$u['assigned_33kv_code']]);
                $u['fdr33kv_name'] = $fdr_stmt->fetch()['fdr33kv_name'] ?? 'N/A';
            } else {
                $u['fdr33kv_name'] = 'N/A';
            }
        }
        
        require __DIR__ . '/../views/admin/users/index.php';
        break;
        
    case 'create':
        // Get master data for dropdowns
        $iss_list = $db->query("SELECT iss_code, iss_name FROM iss_locations ORDER BY iss_name")->fetchAll(PDO::FETCH_ASSOC);
        $feeders_33kv = $db->query("SELECT fdr33kv_code, fdr33kv_name FROM fdr33kv ORDER BY fdr33kv_name")->fetchAll(PDO::FETCH_ASSOC);
        
        require __DIR__ . '/../views/admin/users/create.php';
        break;
        
    case 'edit':
        $payroll_id = $_GET['id'] ?? null;
        
        if (!$payroll_id) {
            header('Location: ?page=users');
            exit;
        }
        
        // Get user details
        $stmt = $db->prepare("SELECT * FROM staff_details WHERE payroll_id = ?");
        $stmt->execute([$payroll_id]);
        $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$edit_user) {
            header('Location: ?page=users');
            exit;
        }
        
        // Get master data
        $iss_list = $db->query("SELECT iss_code, iss_name FROM iss_locations ORDER BY iss_name")->fetchAll(PDO::FETCH_ASSOC);
        $feeders_33kv = $db->query("SELECT fdr33kv_code, fdr33kv_name FROM fdr33kv ORDER BY fdr33kv_name")->fetchAll(PDO::FETCH_ASSOC);
        
        require __DIR__ . '/../views/admin/users/edit.php';
        break;
        
    default:
        header('Location: ?page=users');
        break;
}
