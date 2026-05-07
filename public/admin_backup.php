<?php
/**
 * Admin backup viewer / downloader.
 *
 *  • Lists every backup_*.json.gz in the persistent backups dir
 *  • Lets a UL6 / UL7 admin click-to-download any of them
 *  • Lets the admin trigger an on-demand backup via the "Run backup now" button
 *
 * Reachable at:  /admin_backup.php
 */

require_once __DIR__ . '/../app/bootstrap.php';

Guard::requireLogin();
$user = Auth::user();
if (!in_array($user['role'], ['UL6', 'UL7'], true)) {
    http_response_code(403);
    die('Access denied — UL6 or UL7 only.');
}

$backupDir = '/var/www/html/database/backups';
$message   = '';
$messageOk = true;

// ── Action: run backup now ──────────────────────────────────────────────────
if (($_POST['action'] ?? '') === 'run') {
    $cmd = 'php ' . escapeshellarg(__DIR__ . '/../sql/daily_backup.php') . ' 2>&1';
    $out = shell_exec($cmd) ?: '(no output)';
    $message   = "Backup run output:\n" . $out;
    $messageOk = (strpos($out, '✅') !== false);
}

// ── Action: download a specific file ────────────────────────────────────────
if (isset($_GET['file'])) {
    $name = basename($_GET['file']);                    // strip path
    if (preg_match('/^backup_\d{8}_\d{6}\.json\.gz$/', $name)) {
        $path = $backupDir . '/' . $name;
        if (is_file($path)) {
            header('Content-Type: application/gzip');
            header('Content-Length: ' . filesize($path));
            header('Content-Disposition: attachment; filename="' . $name . '"');
            readfile($path);
            exit;
        }
    }
    http_response_code(404);
    die('Backup not found.');
}

// ── List backups ────────────────────────────────────────────────────────────
$files = is_dir($backupDir) ? glob($backupDir . '/backup_*.json.gz') ?: [] : [];
rsort($files);

$totalSize = array_sum(array_map('filesize', $files));
?>
<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8">
<title>Backup Manager</title>
<style>
body{font-family:'Segoe UI',sans-serif;max-width:900px;margin:30px auto;padding:0 20px;background:#f4f6fa;color:#1e293b}
h2{color:#004B23;margin:0 0 8px}
.sub{color:#64748b;font-size:13px;margin-bottom:24px}
.card{background:#fff;border-radius:10px;padding:20px;box-shadow:0 2px 8px rgba(0,0,0,.08);margin-bottom:20px}
table{width:100%;border-collapse:collapse}
th{background:#004B23;color:#fff;padding:10px 12px;text-align:left;font-size:13px}
td{padding:9px 12px;border-bottom:1px solid #e2e8f0;font-size:13px}
tr:last-child td{border:none}
.btn{display:inline-block;padding:8px 18px;border-radius:6px;background:#004B23;color:#fff;text-decoration:none;font-weight:600;font-size:13px;border:none;cursor:pointer}
.btn:hover{background:#006b30}
.btn-sm{padding:5px 12px;font-size:12px}
.ok{background:#dcfce7;border-left:4px solid #22c55e;padding:12px 16px;border-radius:6px;margin-bottom:16px;white-space:pre-wrap;font-family:monospace;font-size:12px}
.err{background:#fee2e2;border-left:4px solid #dc2626;padding:12px 16px;border-radius:6px;margin-bottom:16px;white-space:pre-wrap;font-family:monospace;font-size:12px}
.empty{text-align:center;color:#64748b;padding:40px;font-style:italic}
.summary{color:#475569;font-size:13px;margin-bottom:12px}
a.dl{color:#1e40af;text-decoration:none;font-weight:600}
a.dl:hover{text-decoration:underline}
</style></head>
<body>
<h2>Database Backup Manager</h2>
<div class="sub">Logged in as <?= htmlspecialchars($user['staff_name']) ?> (<?= $user['role'] ?>)</div>

<?php if ($message): ?>
    <div class="<?= $messageOk ? 'ok' : 'err' ?>"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="card">
    <form method="post" style="display:inline">
        <input type="hidden" name="action" value="run">
        <button type="submit" class="btn">▶ Run backup now</button>
    </form>
    <span style="color:#64748b;font-size:13px;margin-left:14px">
        Manually trigger a backup right now. (Daily auto-backups run via Dokploy Schedules.)
    </span>
</div>

<div class="card">
    <div class="summary">
        <strong><?= count($files) ?></strong> backups stored —
        total size <strong><?= number_format($totalSize / 1024, 1) ?> KB</strong>
        (<?= number_format($totalSize / 1024 / 1024, 2) ?> MB)
    </div>
    <?php if (empty($files)): ?>
        <div class="empty">No backups yet. Click "Run backup now" to create the first one.</div>
    <?php else: ?>
    <table>
        <tr><th>File</th><th>Date</th><th>Size</th><th></th></tr>
        <?php foreach ($files as $f):
            $name = basename($f);
            $size = filesize($f);
            // backup_YYYYMMDD_HHMMSS.json.gz
            $ts = preg_match('/backup_(\d{8})_(\d{6})/', $name, $m)
                ? DateTime::createFromFormat('Ymd His', $m[1] . ' ' . $m[2])->format('Y-m-d H:i:s')
                : '—';
        ?>
        <tr>
            <td><?= htmlspecialchars($name) ?></td>
            <td><?= $ts ?></td>
            <td><?= number_format($size / 1024, 1) ?> KB</td>
            <td><a class="dl" href="?file=<?= urlencode($name) ?>">⬇ Download</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</div>

<div style="color:#64748b;font-size:12px;text-align:center;margin-top:24px">
    Backup files are gzipped JSON.  Decompress with: <code>gunzip backup_*.json.gz</code>
</div>
</body></html>
