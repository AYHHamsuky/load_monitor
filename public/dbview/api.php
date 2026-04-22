<?php
/**
 * Database Viewer – API Backend
 * Handles all AJAX requests for the DB viewer UI.
 *
 * Endpoints (via ?action=...):
 *   tables          – list all tables with row counts
 *   schema&table=X  – column definitions for table X
 *   data&table=X    – paginated rows (supports ?page, ?per_page, ?search, ?sort, ?dir)
 *   export&table=X  – CSV download of full table
 *   sql             – execute a raw read-only SQL query (POST: {sql: "..."})
 *   stats           – overall database statistics
 */

header('Content-Type: application/json; charset=utf-8');

// ── Connect ────────────────────────────────────────────────────────────────
$dbPath = realpath(__DIR__ . '/../../database/load_monitor.sqlite');
if (!$dbPath || !file_exists($dbPath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Database file not found']);
    exit;
}

try {
    $db = new PDO("sqlite:$dbPath", null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? '';

// ── Helpers ────────────────────────────────────────────────────────────────
function safeTableName(PDO $db, string $name): string {
    // Whitelist: table must actually exist
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")
                 ->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array($name, $tables, true)) {
        http_response_code(400);
        echo json_encode(['error' => "Table '$name' does not exist"]);
        exit;
    }
    return $name;
}

// ── Route ──────────────────────────────────────────────────────────────────
switch ($action) {

    // ── List tables ────────────────────────────────────────────────────────
    case 'tables':
        $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")
                     ->fetchAll(PDO::FETCH_COLUMN);
        $result = [];
        foreach ($tables as $t) {
            $count = (int) $db->query("SELECT COUNT(*) FROM [$t]")->fetchColumn();
            $result[] = ['name' => $t, 'rows' => $count];
        }
        echo json_encode($result);
        break;

    // ── Table schema ───────────────────────────────────────────────────────
    case 'schema':
        $table = safeTableName($db, $_GET['table'] ?? '');
        $cols  = $db->query("PRAGMA table_info([$table])")->fetchAll();
        $fks   = $db->query("PRAGMA foreign_key_list([$table])")->fetchAll();
        $idxs  = $db->query("PRAGMA index_list([$table])")->fetchAll();

        // Get CREATE statement
        $stmt = $db->prepare("SELECT sql FROM sqlite_master WHERE type='table' AND name = ?");
        $stmt->execute([$table]);
        $createSql = $stmt->fetchColumn();

        echo json_encode([
            'table'   => $table,
            'columns' => $cols,
            'foreign_keys' => $fks,
            'indexes' => $idxs,
            'create_sql' => $createSql,
        ]);
        break;

    // ── Table data (paginated) ─────────────────────────────────────────────
    case 'data':
        $table   = safeTableName($db, $_GET['table'] ?? '');
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(200, max(10, (int)($_GET['per_page'] ?? 50)));
        $search  = trim($_GET['search'] ?? '');
        $sort    = $_GET['sort'] ?? '';
        $dir     = strtoupper($_GET['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

        // Get columns
        $cols = $db->query("PRAGMA table_info([$table])")->fetchAll();
        $colNames = array_map(fn($c) => $c['name'], $cols);

        // Build WHERE for search
        $where = '';
        $params = [];
        if ($search !== '') {
            $clauses = [];
            foreach ($colNames as $i => $c) {
                $clauses[] = "CAST([$c] AS TEXT) LIKE :s$i";
                $params[":s$i"] = "%$search%";
            }
            $where = 'WHERE ' . implode(' OR ', $clauses);
        }

        // Validate sort column
        $orderBy = '';
        if ($sort !== '' && in_array($sort, $colNames, true)) {
            $orderBy = "ORDER BY [$sort] $dir";
        }

        // Total count
        $countSql = "SELECT COUNT(*) FROM [$table] $where";
        $stmt = $db->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        // Fetch page
        $offset  = ($page - 1) * $perPage;
        $dataSql = "SELECT * FROM [$table] $where $orderBy LIMIT $perPage OFFSET $offset";
        $stmt = $db->prepare($dataSql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        echo json_encode([
            'table'    => $table,
            'columns'  => $colNames,
            'rows'     => $rows,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => (int) ceil($total / $perPage),
        ]);
        break;

    // ── CSV Export ─────────────────────────────────────────────────────────
    case 'export':
        $table = safeTableName($db, $_GET['table'] ?? '');
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$table}.csv\"");

        $out  = fopen('php://output', 'w');
        $cols = $db->query("PRAGMA table_info([$table])")->fetchAll();
        fputcsv($out, array_map(fn($c) => $c['name'], $cols));

        $rows = $db->query("SELECT * FROM [$table]");
        while ($row = $rows->fetch()) {
            fputcsv($out, array_values($row));
        }
        fclose($out);
        exit;

    // ── Run raw SQL (read-only) ────────────────────────────────────────────
    case 'sql':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'POST required']);
            exit;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $sql   = trim($input['sql'] ?? '');

        if ($sql === '') {
            echo json_encode(['error' => 'Empty SQL']);
            exit;
        }

        // Block writes
        $firstWord = strtoupper(strtok($sql, " \t\n\r"));
        if (!in_array($firstWord, ['SELECT', 'PRAGMA', 'EXPLAIN', 'WITH'], true)) {
            echo json_encode(['error' => 'Only SELECT / PRAGMA / EXPLAIN / WITH queries are allowed']);
            exit;
        }

        try {
            $start = microtime(true);
            $stmt  = $db->query($sql);
            $rows  = $stmt->fetchAll();
            $elapsed = round((microtime(true) - $start) * 1000, 2);

            $columns = [];
            if (count($rows) > 0) {
                $columns = array_keys($rows[0]);
            }
            echo json_encode([
                'columns'  => $columns,
                'rows'     => array_slice($rows, 0, 500),
                'total'    => count($rows),
                'truncated'=> count($rows) > 500,
                'time_ms'  => $elapsed,
            ]);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    // ── Database stats ─────────────────────────────────────────────────────
    case 'stats':
        $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);
        $totalRows = 0;
        foreach ($tables as $t) {
            $totalRows += (int)$db->query("SELECT COUNT(*) FROM [$t]")->fetchColumn();
        }
        $size = filesize($dbPath);
        $views = $db->query("SELECT COUNT(*) FROM sqlite_master WHERE type='view'")->fetchColumn();
        $triggers = $db->query("SELECT COUNT(*) FROM sqlite_master WHERE type='trigger'")->fetchColumn();
        $indexes = $db->query("SELECT COUNT(*) FROM sqlite_master WHERE type='index'")->fetchColumn();

        echo json_encode([
            'db_path'    => basename($dbPath),
            'db_size'    => $size,
            'tables'     => count($tables),
            'views'      => (int)$views,
            'triggers'   => (int)$triggers,
            'indexes'    => (int)$indexes,
            'total_rows' => $totalRows,
            'sqlite_version' => $db->query("SELECT sqlite_version()")->fetchColumn(),
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action. Use: tables, schema, data, export, sql, stats']);
}
