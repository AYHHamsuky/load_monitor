<?php
/**
 * ONE-TIME SCRIPT — Run once then delete this file.
 * Inserts ICT department staff as UL7 System Administrators.
 */
require_once __DIR__ . '/../app/bootstrap.php';

$db = Database::getInstance();

$users = [
    [
        'payroll_id' => '104715',
        'staff_name' => 'Yasir Mahmoud AbdusSalam',
        'staff_level'=> 'AGM',
        'phone'      => '08030499098',
        'email'      => 'yasir.abdussalam@kadunaelectric.com',
    ],
    [
        'payroll_id' => '100435',
        'staff_name' => 'Babatunde Emma Odesoji',
        'staff_level'=> 'AM',
        'phone'      => '08184428175',
        'email'      => 'tunde.odesoji@kadunaelectric.com',
    ],
    [
        'payroll_id' => '105593',
        'staff_name' => 'Abubakar Yahya Hamza',
        'staff_level'=> 'AM',
        'phone'      => '08133805942',
        'email'      => 'abubakar.yahya.hamza@kadunaelectric.com',
    ],
];

$password_hash = password_hash('password@123', PASSWORD_BCRYPT);
$inserted = [];
$skipped  = [];
$errors   = [];

foreach ($users as $u) {
    $chk = $db->prepare("SELECT payroll_id FROM staff_details WHERE payroll_id = ?");
    $chk->execute([$u['payroll_id']]);
    if ($chk->fetch()) {
        // Already exists — just ensure role is UL7
        $db->prepare("UPDATE staff_details SET role='UL7', updated_at=CURRENT_TIMESTAMP WHERE payroll_id=?")
           ->execute([$u['payroll_id']]);
        $skipped[] = $u['payroll_id'] . ' (' . $u['staff_name'] . ') — already existed, role updated to UL7';
        continue;
    }

    try {
        $db->prepare("
            INSERT INTO staff_details
                (payroll_id, staff_name, role, staff_level, iss_code, assigned_33kv_code,
                 sv_code, phone, email, password_hash, is_active, created_at, updated_at)
            VALUES (?, ?, 'UL7', ?, '0', '0', '100000', ?, ?, ?, 'Yes', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ")->execute([
            $u['payroll_id'], $u['staff_name'], $u['staff_level'],
            $u['phone'], $u['email'], $password_hash,
        ]);
        $inserted[] = $u['payroll_id'] . ' — ' . $u['staff_name'];
    } catch (Exception $e) {
        $errors[] = $u['payroll_id'] . ': ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>ICT User Setup</title>
<style>
body{font-family:sans-serif;max-width:700px;margin:40px auto;padding:20px;background:#f4f6fa;}
h2{color:#004B23;}
.ok{background:#dcfce7;border-left:4px solid #22c55e;padding:10px 14px;border-radius:6px;margin:8px 0;}
.skip{background:#fef3c7;border-left:4px solid #f59e0b;padding:10px 14px;border-radius:6px;margin:8px 0;}
.err{background:#fee2e2;border-left:4px solid #dc2626;padding:10px 14px;border-radius:6px;margin:8px 0;}
.warn{background:#fef9c3;border:2px solid #f59e0b;padding:14px;border-radius:8px;margin-top:24px;font-weight:bold;}
</style></head>
<body>
<h2>ICT User Setup — UL7 System Administrator</h2>

<?php foreach ($inserted as $msg): ?>
    <div class="ok">✅ Inserted: <?= htmlspecialchars($msg) ?></div>
<?php endforeach; ?>
<?php foreach ($skipped as $msg): ?>
    <div class="skip">⚠️ Skipped: <?= htmlspecialchars($msg) ?></div>
<?php endforeach; ?>
<?php foreach ($errors as $msg): ?>
    <div class="err">❌ Error: <?= htmlspecialchars($msg) ?></div>
<?php endforeach; ?>

<?php if (empty($errors)): ?>
<p><strong>Default password:</strong> <code>password@123</code></p>
<div class="warn">🗑️ DELETE this file from the server immediately after use:<br>
<code>public/setup_ict_users.php</code></div>
<?php endif; ?>
</body>
</html>
