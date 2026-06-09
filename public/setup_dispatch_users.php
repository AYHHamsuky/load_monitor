<?php
/**
 * ONE-TIME SCRIPT — Run once at /setup_dispatch_users.php, then DELETE the file.
 *
 * Seeds the 11 Kaduna Electric Dispatch / PS&D / MIS staff supplied by ICT
 * (Mr. Tunde — 09/06/2026).  Behaviour:
 *
 *   • If the payroll_id exists  → UPDATE name + role + staff_level
 *   • If the payroll_id is new  → INSERT a fresh row with default password
 *
 * Default password for new accounts:  password@123
 * Role mapping:
 *   "Team Member"   → UL2 (33 kV data entry)
 *   "Analyst"       → UL3 (Analyst)
 *   "Head Dispatch" → UL8 (Lead Dispatch Officer)
 *
 * Duplicate payroll_id 100303 is detected and the SECOND occurrence is
 * skipped with a warning so it can be corrected manually.
 */
require_once __DIR__ . '/../app/bootstrap.php';

// Only allow super admins to run this
Guard::requireLogin();
$me = Auth::user();
if (!in_array($me['role'], ['UL6', 'UL7'], true)) {
    http_response_code(403);
    die('Access denied — requires UL6 or UL7 login.');
}

$db = Database::connect();

// ── Staff list (verbatim from ICT) ─────────────────────────────────────────
$staff = [
    ['sn'=>1,  'payroll'=>'705075', 'name'=>'Waziri Elisha Emishe',     'level'=>'Team Member',  'role'=>'UL2'],
    ['sn'=>2,  'payroll'=>'599613', 'name'=>'Joshua Musa',              'level'=>'Team Member',  'role'=>'UL2'],
    ['sn'=>3,  'payroll'=>'697459', 'name'=>'Mohammed M. Saleh',        'level'=>'Team Member',  'role'=>'UL2'],
    ['sn'=>4,  'payroll'=>'664209', 'name'=>'Nura Illiyasu',            'level'=>'HoU PS&D',     'role'=>'UL8'],
    ['sn'=>5,  'payroll'=>'836297', 'name'=>'Isiaka Bala Muas',         'level'=>'TL CD',        'role'=>'UL8'],
    ['sn'=>6,  'payroll'=>'100089', 'name'=>'Aliyu Kolawole',           'level'=>'MIS',          'role'=>'UL3'],
    ['sn'=>7,  'payroll'=>'102645', 'name'=>'Adamu Ibrahim Isah',       'level'=>'Team Member',  'role'=>'UL2'],
    ['sn'=>8,  'payroll'=>'100303', 'name'=>'Hassan Asuva Suleiman',    'level'=>'Team Member',  'role'=>'UL2'],
    ['sn'=>9,  'payroll'=>'106051', 'name'=>'Munzali Umar Sulaiman',    'level'=>'Team Member',  'role'=>'UL2'],
    ['sn'=>10, 'payroll'=>'100231', 'name'=>'Stephen Arti Agai',        'level'=>'Team Member',  'role'=>'UL2'],
    ['sn'=>11, 'payroll'=>'100303', 'name'=>'Ibrahim Abdullahi Ahmed',  'level'=>'Team Member',  'role'=>'UL2'],
];

$passwordHash = password_hash('password@123', PASSWORD_BCRYPT);
$inserted = $updated = $skipped = $errors = [];
$seenPayroll = [];

foreach ($staff as $s) {

    // Skip in-batch duplicates
    if (isset($seenPayroll[$s['payroll']])) {
        $skipped[] = sprintf('Row %d (%s — payroll %s): duplicate of row %d (%s). Needs a corrected payroll ID before insert.',
            $s['sn'], $s['name'], $s['payroll'], $seenPayroll[$s['payroll']]['sn'], $seenPayroll[$s['payroll']]['name']);
        continue;
    }
    $seenPayroll[$s['payroll']] = $s;

    try {
        $chk = $db->prepare('SELECT payroll_id, staff_name, role FROM staff_details WHERE payroll_id = ?');
        $chk->execute([$s['payroll']]);
        $existing = $chk->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $db->prepare("
                UPDATE staff_details
                   SET staff_name  = ?,
                       role        = ?,
                       staff_level = ?,
                       is_active   = 'Yes',
                       updated_at  = CURRENT_TIMESTAMP
                 WHERE payroll_id  = ?
            ")->execute([$s['name'], $s['role'], $s['level'], $s['payroll']]);
            $updated[] = sprintf('%s (%s) — was %s/%s, now %s/%s',
                $s['payroll'], $s['name'], $existing['staff_name'], $existing['role'], $s['name'], $s['role']);
        } else {
            $db->prepare("
                INSERT INTO staff_details
                    (payroll_id, staff_name, role, staff_level, iss_code, assigned_33kv_code,
                     sv_code, phone, email, password_hash, is_active, created_at, updated_at)
                VALUES (?, ?, ?, ?, '0', '0', '100000', '', '', ?, 'Yes', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
            ")->execute([$s['payroll'], $s['name'], $s['role'], $s['level'], $passwordHash]);
            $inserted[] = sprintf('%s — %s (%s)', $s['payroll'], $s['name'], $s['role']);
        }
    } catch (Throwable $e) {
        $errors[] = sprintf('%s (%s): %s', $s['payroll'], $s['name'], $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><title>Dispatch User Setup</title>
<style>
body { font-family:'Segoe UI',sans-serif; max-width:780px; margin:30px auto; padding:0 20px; background:#f4f6fa; color:#1e293b; }
h2 { color:#004B23; margin:0 0 8px; }
.sub { color:#64748b; font-size:13px; margin-bottom:24px; }
.row { padding:10px 14px; border-radius:6px; margin:6px 0; font-size:13px; }
.ok   { background:#dcfce7; border-left:4px solid #22c55e; }
.upd  { background:#dbeafe; border-left:4px solid #3b82f6; }
.skip { background:#fef3c7; border-left:4px solid #f59e0b; }
.err  { background:#fee2e2; border-left:4px solid #dc2626; }
.summary { background:#fff; padding:14px 18px; border-radius:8px; box-shadow:0 1px 6px rgba(0,0,0,.08); margin-bottom:18px; }
.summary .n { font-size:22px; font-weight:800; color:#1e293b; display:inline-block; min-width:36px; }
.summary .l { font-size:12px; color:#64748b; text-transform:uppercase; letter-spacing:.4px; margin-left:4px; }
.delete-warn { background:#fee2e2; border:2px solid #dc2626; padding:14px; border-radius:8px;
    margin-top:30px; font-weight:bold; color:#991b1b; }
code { background:#fff; padding:2px 6px; border-radius:3px; font-family:monospace; font-size:12px; }
</style></head>
<body>

<h2>Dispatch Staff Setup — Kaduna Electric</h2>
<div class="sub">Ran by <?= htmlspecialchars($me['staff_name']) ?> (<?= $me['role'] ?>) at <?= date('Y-m-d H:i:s') ?></div>

<div class="summary">
    <span class="n"><?= count($inserted) ?></span><span class="l">Inserted</span> &nbsp;|&nbsp;
    <span class="n"><?= count($updated)  ?></span><span class="l">Updated</span> &nbsp;|&nbsp;
    <span class="n"><?= count($skipped)  ?></span><span class="l">Skipped</span> &nbsp;|&nbsp;
    <span class="n"><?= count($errors)   ?></span><span class="l">Errors</span>
</div>

<?php foreach ($inserted as $m): ?><div class="row ok">✅ Inserted: <?= htmlspecialchars($m) ?></div><?php endforeach; ?>
<?php foreach ($updated  as $m): ?><div class="row upd">🔄 Updated: <?= htmlspecialchars($m) ?></div><?php endforeach; ?>
<?php foreach ($skipped  as $m): ?><div class="row skip">⚠️ Skipped: <?= htmlspecialchars($m) ?></div><?php endforeach; ?>
<?php foreach ($errors   as $m): ?><div class="row err">❌ Error: <?= htmlspecialchars($m) ?></div><?php endforeach; ?>

<?php if (!empty($inserted)): ?>
<p style="margin-top:18px;font-size:13px;">
    <strong>Default password for new accounts:</strong> <code>password@123</code><br>
    New users should change it after first login.
</p>
<?php endif; ?>

<div class="delete-warn">
    🗑️ DELETE this file from the server now:<br>
    <code>public/setup_dispatch_users.php</code>
</div>

</body></html>
