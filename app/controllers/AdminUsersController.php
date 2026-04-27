<?php
Guard::requireAdmin(); // UL6 only

$user = Auth::user();
$db   = Database::connect();

$action = $_GET['action'] ?? 'list';

switch ($action) {

    // ─── LIST ────────────────────────────────────────────────────────────────
    case 'list':
        $page        = max(1, (int)($_GET['p'] ?? 1));
        $per_page    = 15;
        $offset      = ($page - 1) * $per_page;
        $search      = $_GET['search'] ?? '';
        $role_filter = $_GET['role']   ?? '';
        $status_filter = $_GET['status'] ?? '';

        $where = [];
        $params = [];

        if ($search !== '') {
            $where[]  = "(payroll_id LIKE ? OR staff_name LIKE ? OR email LIKE ?)";
            $term     = "%$search%";
            $params[] = $term; $params[] = $term; $params[] = $term;
        }
        if ($role_filter !== '') {
            $where[]  = "role = ?";
            $params[] = $role_filter;
        }
        if ($status_filter !== '') {
            $where[]  = "is_active = ?";
            $params[] = $status_filter;
        }

        $where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $cnt = $db->prepare("SELECT COUNT(*) AS total FROM staff_details $where_sql");
        $cnt->execute($params);
        $total_users = (int)$cnt->fetch()['total'];
        $total_pages = max(1, (int)ceil($total_users / $per_page));
        $page        = min($page, $total_pages);

        $stmt = $db->prepare("
            SELECT payroll_id, staff_name, role, iss_code, assigned_33kv_code,
                   phone, email, staff_level, is_active, created_at, last_login
            FROM   staff_details
            $where_sql
            ORDER BY created_at DESC
            LIMIT $per_page OFFSET $offset
        ");
        $stmt->execute($params);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($users as &$u) {
            if (!empty($u['iss_code']) && $u['iss_code'] !== '0') {
                $s = $db->prepare("SELECT iss_name FROM iss_locations WHERE iss_code = ?");
                $s->execute([$u['iss_code']]);
                $u['iss_name'] = $s->fetchColumn() ?: 'N/A';
            } else {
                $u['iss_name'] = 'N/A';
            }

            if (!empty($u['assigned_33kv_code']) && $u['assigned_33kv_code'] !== '0') {
                $s = $db->prepare("SELECT fdr33kv_name FROM fdr33kv WHERE fdr33kv_code = ?");
                $s->execute([$u['assigned_33kv_code']]);
                $u['fdr33kv_name'] = $s->fetchColumn() ?: 'N/A';
            } else {
                $u['fdr33kv_name'] = 'N/A';
            }
        }
        unset($u);

        // Provide dropdown data needed by the import modal
        $iss_list    = $db->query("SELECT iss_code, iss_name FROM iss_locations ORDER BY iss_name")->fetchAll(PDO::FETCH_ASSOC);
        $feeders_33kv = $db->query("SELECT fdr33kv_code, fdr33kv_name FROM fdr33kv ORDER BY fdr33kv_name")->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/admin/users/index.php';
        break;

    // ─── CREATE ──────────────────────────────────────────────────────────────
    case 'create':
        $iss_list    = $db->query("SELECT iss_code, iss_name FROM iss_locations ORDER BY iss_name")->fetchAll(PDO::FETCH_ASSOC);
        $feeders_33kv = $db->query("SELECT fdr33kv_code, fdr33kv_name FROM fdr33kv ORDER BY fdr33kv_name")->fetchAll(PDO::FETCH_ASSOC);
        require __DIR__ . '/../views/admin/users/create.php';
        break;

    // ─── EDIT ────────────────────────────────────────────────────────────────
    case 'edit':
        $payroll_id = $_GET['id'] ?? null;
        if (!$payroll_id) { header('Location: ?page=users'); exit; }

        $stmt = $db->prepare("SELECT * FROM staff_details WHERE payroll_id = ?");
        $stmt->execute([$payroll_id]);
        $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$edit_user) { header('Location: ?page=users'); exit; }

        $iss_list    = $db->query("SELECT iss_code, iss_name FROM iss_locations ORDER BY iss_name")->fetchAll(PDO::FETCH_ASSOC);
        $feeders_33kv = $db->query("SELECT fdr33kv_code, fdr33kv_name FROM fdr33kv ORDER BY fdr33kv_name")->fetchAll(PDO::FETCH_ASSOC);
        require __DIR__ . '/../views/admin/users/edit.php';
        break;

    default:
        header('Location: ?page=users');
        break;
}
