<?php
/**
 * Late Entry Report Controller
 * Path: /app/controllers/LateEntryReportController.php
 *
 * Lists every row from the `late_entry_log` table with filters:
 *   • Date range (default: last 7 days)
 *   • Voltage level (11kV / 33kV / all)
 *   • ISS / Transmission Station
 *   • Staff (payroll_id or name search)
 *
 * Visible to: UL3 (Analyst), UL4 (Manager), UL6 (Tech Admin),
 *             UL7 (System Admin), UL8 (Lead Dispatch).
 */

Guard::requireRole(['UL3', 'UL4', 'UL6', 'UL7', 'UL8']);

$user = Auth::user();
$db   = Database::connect();

// ── Filters ───────────────────────────────────────────────────────────────
$today    = date('Y-m-d');
$default  = date('Y-m-d', strtotime('-7 days'));

$fromDate = $_GET['from']    ?? $default;
$toDate   = $_GET['to']      ?? $today;
$voltage  = $_GET['voltage'] ?? 'all';        // 'all' | '11kV' | '33kV'
$issCode  = $_GET['iss']     ?? 'all';
$search   = trim($_GET['q']  ?? '');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fromDate)) $fromDate = $default;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $toDate))   $toDate   = $today;
if (!in_array($voltage, ['all', '11kV', '33kV'], true)) $voltage = 'all';

// ── Build query with bound params ─────────────────────────────────────────
$where  = ['l.log_date BETWEEN ? AND ?'];
$params = [$fromDate, $toDate];

if ($voltage !== 'all') {
    $where[] = 'l.voltage_level = ?';
    $params[] = $voltage;
}
if ($issCode !== 'all' && $issCode !== '') {
    $where[] = 'l.iss_code = ?';
    $params[] = $issCode;
}
if ($search !== '') {
    $where[] = '(s.staff_name LIKE ? OR l.user_id LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$whereSql = implode(' AND ', $where);

// Pagination
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;
$offset  = ($page - 1) * $perPage;

// Count total for pagination
$countSql = "
    SELECT COUNT(*)
    FROM late_entry_log l
    LEFT JOIN staff_details s   ON s.payroll_id = l.user_id
    WHERE {$whereSql}
";
$cstmt = $db->prepare($countSql);
$cstmt->execute($params);
$totalRows  = (int)$cstmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalRows / $perPage));

// Fetch the page
$sql = "
    SELECT
        l.id,
        l.voltage_level,
        l.user_id,
        l.iss_code,
        l.log_date,
        l.specific_hour,
        l.explanation,
        l.logged_at,
        s.staff_name,
        s.role,
        iss.iss_name
    FROM late_entry_log l
    LEFT JOIN staff_details  s   ON s.payroll_id = l.user_id
    LEFT JOIN iss_locations  iss ON iss.iss_code = l.iss_code
    WHERE {$whereSql}
    ORDER BY l.log_date DESC, l.specific_hour DESC, l.logged_at DESC
    LIMIT {$perPage} OFFSET {$offset}
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Summary stats for the same filter range
$statSql = "
    SELECT
        COUNT(*)                                              AS total,
        SUM(CASE WHEN l.voltage_level='11kV' THEN 1 ELSE 0 END) AS c11,
        SUM(CASE WHEN l.voltage_level='33kV' THEN 1 ELSE 0 END) AS c33,
        COUNT(DISTINCT l.user_id)                             AS staff_count,
        COUNT(DISTINCT l.iss_code)                            AS iss_count
    FROM late_entry_log l
    WHERE {$whereSql}
";
$sstmt = $db->prepare($statSql);
$sstmt->execute($params);
$stats = $sstmt->fetch(PDO::FETCH_ASSOC) ?: ['total'=>0,'c11'=>0,'c33'=>0,'staff_count'=>0,'iss_count'=>0];

// ISS dropdown
$issList = $db->query("
    SELECT iss_code, iss_name FROM iss_locations ORDER BY iss_name
")->fetchAll(PDO::FETCH_ASSOC);

// CSV export
if (($_GET['export'] ?? '') === 'csv') {
    // Re-run without LIMIT for the export
    $exportSql = preg_replace('/LIMIT.*$/s', '', $sql);
    $estmt = $db->prepare($exportSql);
    $estmt->execute($params);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="late_entries_' . $fromDate . '_to_' . $toDate . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Date', 'Hour', 'Voltage', 'Payroll ID', 'Staff Name', 'Role', 'ISS Code', 'ISS Name', 'Explanation', 'Logged At']);
    while ($r = $estmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($out, [
            $r['log_date'],
            sprintf('%02d:00', (int)$r['specific_hour']),
            $r['voltage_level'],
            $r['user_id'],
            $r['staff_name'] ?? '—',
            $r['role'] ?? '—',
            $r['iss_code'],
            $r['iss_name'] ?? '—',
            $r['explanation'],
            $r['logged_at'],
        ]);
    }
    fclose($out);
    exit;
}

require __DIR__ . '/../views/late_entries/index.php';
