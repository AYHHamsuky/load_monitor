<?php
/**
 * Database Admin – Write API
 * Supports: tables, schema, data, insert, update, delete, run-sql, export, stats
 */

require __DIR__ . '/auth.php';

// Auth check for API — accept session OR JSON password
if (!dbadmin_check_auth()) {
    // Also accept password in JSON body for programmatic access
    $body = json_decode(file_get_contents('php://input'), true);
    if (isset($body['password']) && hash_equals(DBADMIN_PASSWORD, $body['password'])) {
        $_SESSION['dbadmin_auth'] = true;
    } else {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Unauthorized – login required']);
        exit;
    }
}

header('Content-Type: application/json; charset=utf-8');

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
    $db->exec("PRAGMA journal_mode=WAL");
    $db->exec("PRAGMA foreign_keys=ON");
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'DB connection failed: ' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? '';

function safeTable(PDO $db, string $name): string {
    $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")
                 ->fetchAll(PDO::FETCH_COLUMN);
    if (!in_array($name, $tables, true)) {
        http_response_code(400);
        echo json_encode(['error' => "Table not found: $name"]);
        exit;
    }
    return $name;
}

function getColumns(PDO $db, string $table): array {
    return $db->query("PRAGMA table_info([$table])")->fetchAll();
}

function jsonInput(): array {
    return json_decode(file_get_contents('php://input'), true) ?? [];
}

switch ($action) {

    /* ── List tables ──────────────────────────────────────────────────── */
    case 'tables':
        $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name")
                     ->fetchAll(PDO::FETCH_COLUMN);
        $result = [];
        foreach ($tables as $t) {
            $count = (int)$db->query("SELECT COUNT(*) FROM [$t]")->fetchColumn();
            $result[] = ['name' => $t, 'rows' => $count];
        }
        echo json_encode($result);
        break;

    /* ── Schema ───────────────────────────────────────────────────────── */
    case 'schema':
        $table = safeTable($db, $_GET['table'] ?? '');
        $cols  = getColumns($db, $table);
        $fks   = $db->query("PRAGMA foreign_key_list([$table])")->fetchAll();
        $stmt  = $db->prepare("SELECT sql FROM sqlite_master WHERE type='table' AND name=?");
        $stmt->execute([$table]);
        echo json_encode([
            'table'      => $table,
            'columns'    => $cols,
            'foreign_keys' => $fks,
            'create_sql' => $stmt->fetchColumn(),
        ]);
        break;

    /* ── Data (paginated) ─────────────────────────────────────────────── */
    case 'data':
        $table   = safeTable($db, $_GET['table'] ?? '');
        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = min(200, max(10, (int)($_GET['per_page'] ?? 50)));
        $search  = trim($_GET['search'] ?? '');
        $sort    = $_GET['sort'] ?? '';
        $dir     = strtoupper($_GET['dir'] ?? 'ASC') === 'DESC' ? 'DESC' : 'ASC';

        $cols     = getColumns($db, $table);
        $colNames = array_map(fn($c) => $c['name'], $cols);

        $where  = '';
        $params = [];
        if ($search !== '') {
            $clauses = [];
            foreach ($colNames as $i => $c) {
                $clauses[] = "CAST([$c] AS TEXT) LIKE :s$i";
                $params[":s$i"] = "%$search%";
            }
            $where = 'WHERE ' . implode(' OR ', $clauses);
        }

        $orderBy = '';
        if ($sort !== '' && in_array($sort, $colNames, true)) {
            $orderBy = "ORDER BY [$sort] $dir";
        }

        $stmt = $db->prepare("SELECT COUNT(*) FROM [$table] $where");
        $stmt->execute($params);
        $total = (int)$stmt->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $stmt = $db->prepare("SELECT rowid AS __rowid, * FROM [$table] $where $orderBy LIMIT $perPage OFFSET $offset");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // Identify PK columns
        $pkCols = array_values(array_map(fn($c) => $c['name'], array_filter($cols, fn($c) => $c['pk'] > 0)));

        echo json_encode([
            'table'    => $table,
            'columns'  => $colNames,
            'col_info' => $cols,
            'pk_cols'  => $pkCols,
            'rows'     => $rows,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'pages'    => (int)ceil($total / $perPage),
        ]);
        break;

    /* ── INSERT row ───────────────────────────────────────────────────── */
    case 'insert':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'POST required']); exit; }
        $input = jsonInput();
        $table = safeTable($db, $input['table'] ?? '');
        $row   = $input['row'] ?? [];

        if (empty($row)) { echo json_encode(['error' => 'No data provided']); exit; }

        $validCols = array_map(fn($c) => $c['name'], getColumns($db, $table));
        $setCols = [];
        $vals    = [];
        foreach ($row as $col => $val) {
            if (!in_array($col, $validCols, true)) continue;
            $setCols[] = "[$col]";
            $vals[] = $val === '' ? null : $val;
        }

        $placeholders = implode(', ', array_fill(0, count($setCols), '?'));
        $sql = "INSERT INTO [$table] (" . implode(', ', $setCols) . ") VALUES ($placeholders)";

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($vals);
            echo json_encode(['success' => true, 'id' => $db->lastInsertId(), 'message' => 'Row inserted']);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    /* ── UPDATE row ───────────────────────────────────────────────────── */
    case 'update':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'POST required']); exit; }
        $input = jsonInput();
        $table = safeTable($db, $input['table'] ?? '');
        $row   = $input['row'] ?? [];
        $where = $input['where'] ?? [];
        $rowid = $input['rowid'] ?? null;

        if (empty($row)) { echo json_encode(['error' => 'No data provided']); exit; }

        $validCols = array_map(fn($c) => $c['name'], getColumns($db, $table));
        $sets   = [];
        $params = [];
        $i = 0;
        foreach ($row as $col => $val) {
            if (!in_array($col, $validCols, true)) continue;
            $sets[] = "[$col] = :v$i";
            $params[":v$i"] = $val === '' ? null : $val;
            $i++;
        }

        // Build WHERE from rowid or PK
        $wClauses = [];
        if ($rowid !== null) {
            $wClauses[] = "rowid = :wrid";
            $params[":wrid"] = $rowid;
        } else {
            $j = 0;
            foreach ($where as $col => $val) {
                if (!in_array($col, $validCols, true)) continue;
                if ($val === null) {
                    $wClauses[] = "[$col] IS NULL";
                } else {
                    $wClauses[] = "[$col] = :w$j";
                    $params[":w$j"] = $val;
                }
                $j++;
            }
        }

        if (empty($wClauses)) { echo json_encode(['error' => 'No WHERE condition']); exit; }

        $sql = "UPDATE [$table] SET " . implode(', ', $sets) . " WHERE " . implode(' AND ', $wClauses);

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'affected' => $stmt->rowCount(), 'message' => $stmt->rowCount() . ' row(s) updated']);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    /* ── DELETE row ────────────────────────────────────────────────────── */
    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'POST required']); exit; }
        $input = jsonInput();
        $table = safeTable($db, $input['table'] ?? '');
        $where = $input['where'] ?? [];
        $rowid = $input['rowid'] ?? null;

        $validCols = array_map(fn($c) => $c['name'], getColumns($db, $table));
        $params = [];
        $wClauses = [];

        if ($rowid !== null) {
            $wClauses[] = "rowid = :wrid";
            $params[":wrid"] = $rowid;
        } else {
            $j = 0;
            foreach ($where as $col => $val) {
                if (!in_array($col, $validCols, true)) continue;
                if ($val === null) {
                    $wClauses[] = "[$col] IS NULL";
                } else {
                    $wClauses[] = "[$col] = :w$j";
                    $params[":w$j"] = $val;
                }
                $j++;
            }
        }

        if (empty($wClauses)) { echo json_encode(['error' => 'No WHERE condition']); exit; }

        $sql = "DELETE FROM [$table] WHERE " . implode(' AND ', $wClauses);

        try {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            echo json_encode(['success' => true, 'affected' => $stmt->rowCount(), 'message' => $stmt->rowCount() . ' row(s) deleted']);
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    /* ── Run SQL (any statement) ──────────────────────────────────────── */
    case 'sql':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error' => 'POST required']); exit; }
        $input = jsonInput();
        $sql   = trim($input['sql'] ?? '');
        if ($sql === '') { echo json_encode(['error' => 'Empty SQL']); exit; }

        try {
            $start = microtime(true);
            $stmt = $db->prepare($sql);
            $stmt->execute();
            $elapsed = round((microtime(true) - $start) * 1000, 2);

            $firstWord = strtoupper(strtok($sql, " \t\n\r("));
            if (in_array($firstWord, ['SELECT', 'PRAGMA', 'EXPLAIN', 'WITH'], true)) {
                $rows = $stmt->fetchAll();
                $columns = count($rows) > 0 ? array_keys($rows[0]) : [];
                echo json_encode([
                    'type'     => 'select',
                    'columns'  => $columns,
                    'rows'     => array_slice($rows, 0, 1000),
                    'total'    => count($rows),
                    'truncated'=> count($rows) > 1000,
                    'time_ms'  => $elapsed,
                ]);
            } else {
                echo json_encode([
                    'type'     => 'exec',
                    'affected' => $stmt->rowCount(),
                    'time_ms'  => $elapsed,
                    'message'  => $stmt->rowCount() . " row(s) affected ({$elapsed}ms)",
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    /* ── Export CSV ────────────────────────────────────────────────────── */
    case 'export':
        $table = safeTable($db, $_GET['table'] ?? '');
        header('Content-Type: text/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=\"{$table}.csv\"");
        $out  = fopen('php://output', 'w');
        $cols = getColumns($db, $table);
        fputcsv($out, array_map(fn($c) => $c['name'], $cols));
        $rows = $db->query("SELECT * FROM [$table]");
        while ($row = $rows->fetch()) fputcsv($out, array_values($row));
        fclose($out);
        exit;

    /* ── Stats ────────────────────────────────────────────────────────── */
    case 'stats':
        $tables = $db->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")->fetchAll(PDO::FETCH_COLUMN);
        $totalRows = 0;
        foreach ($tables as $t) $totalRows += (int)$db->query("SELECT COUNT(*) FROM [$t]")->fetchColumn();
        echo json_encode([
            'db_path'    => basename($dbPath),
            'db_size'    => filesize($dbPath),
            'tables'     => count($tables),
            'views'      => (int)$db->query("SELECT COUNT(*) FROM sqlite_master WHERE type='view'")->fetchColumn(),
            'triggers'   => (int)$db->query("SELECT COUNT(*) FROM sqlite_master WHERE type='trigger'")->fetchColumn(),
            'indexes'    => (int)$db->query("SELECT COUNT(*) FROM sqlite_master WHERE type='index'")->fetchColumn(),
            'total_rows' => $totalRows,
            'sqlite_ver' => $db->query("SELECT sqlite_version()")->fetchColumn(),
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Unknown action']);
}
