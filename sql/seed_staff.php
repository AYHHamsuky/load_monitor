<?php
/**
 * Staff Seeder
 * Imports all staff from load.sql into the SQLite database
 * and sets every password to password@123.
 *
 * Run once:  php sql/seed_staff.php
 * Or via browser (protected by token check below).
 */

// ── CLI or browser with token ────────────────────────────────────────────────
$isCli = PHP_SAPI === 'cli';

if (!$isCli) {
    // Protect browser access: ?token=seed2026
    $token = $_GET['token'] ?? '';
    if ($token !== 'seed2026') {
        http_response_code(403);
        exit("Forbidden. Use ?token=seed2026 to run.");
    }
    echo "<pre>\n";
}

$dbPath  = __DIR__ . '/../database/load_monitor.sqlite';
$sqlFile = __DIR__ . '/load.sql';

if (!file_exists($sqlFile)) {
    exit("ERROR: sql/load.sql not found.\n");
}
if (!file_exists($dbPath)) {
    exit("ERROR: database/load_monitor.sqlite not found. Run sql/init_sqlite.php first.\n");
}

// ── Connect ──────────────────────────────────────────────────────────────────
$db = new PDO("sqlite:$dbPath", null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec('PRAGMA journal_mode=WAL');
$db->exec('PRAGMA foreign_keys=ON');

// ── Generate hash once — bcrypt is slow by design ────────────────────────────
$defaultPassword = 'password@123';
$hash = password_hash($defaultPassword, PASSWORD_BCRYPT, ['cost' => 10]);
echo "Password hash generated.\n";

// ── Parse staff rows from load.sql ───────────────────────────────────────────
$sql = file_get_contents($sqlFile);

// Find the staff_details INSERT block
if (!preg_match(
    '/INSERT INTO `staff_details`[^;]+;/s',
    $sql,
    $match
)) {
    exit("ERROR: Could not find INSERT INTO staff_details in load.sql\n");
}

$block = $match[0];

// Extract all value tuples:  ('val1', 'val2', NULL, ...)
preg_match_all("/\('([^']*(?:''[^']*)*)','([^']+)','([^']*)','([^']*)','([^']*)','([^']*)','([^']*)','([^']+)',[^,]+,'([^']*|NULL)','([^']+)','([^']+)'\)/", $block, $rows, PREG_SET_ORDER);

// Columns order in load.sql INSERT:
// staff_name, payroll_id, iss_code, phone, staff_level, sv_code,
// assigned_33kv_code, email, password_hash, last_login, is_active, role, created_at, updated_at

// More reliable: extract rows line-by-line
$lines  = explode("\n", $block);
$tuples = [];
foreach ($lines as $line) {
    $line = trim($line);
    // Each data line starts with ( and ends with ), or );
    if (preg_match('/^\((.+)\)[,;]?$/', $line, $m)) {
        $tuples[] = $m[1];
    }
}

echo "Found " . count($tuples) . " staff rows to import.\n\n";

// ── Prepare upsert ───────────────────────────────────────────────────────────
// INSERT OR REPLACE handles both new rows and updates.
// The UNIQUE constraint on email means duplicates (by email) get replaced.
// We key on payroll_id via a prior DELETE to ensure payroll_id is also replaced.
$ins  = $db->prepare("
    INSERT INTO staff_details
        (staff_name, payroll_id, iss_code, phone, staff_level, sv_code,
         assigned_33kv_code, email, password_hash, last_login, is_active, role)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?)
");

$ok = 0; $skip = 0;

$db->beginTransaction();

// Wipe existing staff so we start clean — load.sql is the source of truth
$db->exec("DELETE FROM staff_details");
echo "Existing staff cleared.\n\n";

foreach ($tuples as $tuple) {
    // Parse CSV-like values, handling NULL and escaped quotes
    $values = parseMysqlRow($tuple);

    if (count($values) < 12) {
        echo "  SKIP (parse error): $tuple\n";
        $skip++;
        continue;
    }

    // Positional: staff_name[0], payroll_id[1], iss_code[2], phone[3],
    //             staff_level[4], sv_code[5], assigned_33kv_code[6], email[7],
    //             password_hash[8], last_login[9], is_active[10], role[11]
    $payroll  = $values[1];
    $name     = $values[0];
    $iss      = $values[2];
    $phone    = $values[3];
    $level    = $values[4];
    $sv       = $values[5];
    $code33   = $values[6];
    $email    = $values[7];
    $active   = $values[10];
    $role     = $values[11];

    try {
        $ins->execute([$name, $payroll, $iss, $phone, $level, $sv, $code33,
                       $email, $hash, $active, $role]);
        echo "  OK  payroll=$payroll  name=$name  role=$role\n";
        $ok++;
    } catch (PDOException $e) {
        echo "  SKIP payroll=$payroll  email=$email  reason=" . $e->getMessage() . "\n";
        $skip++;
    }
}
$db->commit();

echo "\n✓ Done. Imported=$ok  Skipped=$skip\n";
echo "All staff can now log in with their payroll_id and password: $defaultPassword\n";

if (!$isCli) echo "</pre>\n";

// ── Helper: parse one MySQL VALUES row ───────────────────────────────────────
function parseMysqlRow(string $row): array
{
    $values = [];
    $i      = 0;
    $len    = strlen($row);

    while ($i < $len) {
        // Skip leading whitespace / comma
        while ($i < $len && in_array($row[$i], [' ', ','])) $i++;
        if ($i >= $len) break;

        if ($row[$i] === "'") {
            // Quoted string
            $i++; // skip opening quote
            $val = '';
            while ($i < $len) {
                if ($row[$i] === "'" && ($i + 1 >= $len || $row[$i + 1] !== "'")) {
                    $i++; // skip closing quote
                    break;
                }
                if ($row[$i] === "'" && $row[$i + 1] === "'") {
                    $val .= "'";
                    $i += 2;
                } else {
                    $val .= $row[$i];
                    $i++;
                }
            }
            $values[] = $val;
        } else {
            // Unquoted (NULL or number)
            $j = $i;
            while ($j < $len && $row[$j] !== ',') $j++;
            $raw      = trim(substr($row, $i, $j - $i));
            $values[] = strtoupper($raw) === 'NULL' ? null : $raw;
            $i        = $j;
        }
    }

    return $values;
}
