
<?php
if (!Auth::check()) {
    $base = defined('BASE_PATH') ? BASE_PATH : '/load_monitor/public';
    header("Location: {$base}/index.php?page=login");
    exit;
}
