<?php
/**
 * Admin runner for the feeder hierarchy import.  UL6 / UL7 only.
 * DELETE this file after running.
 */
require_once __DIR__ . '/../app/bootstrap.php';

Guard::requireLogin();
$u = Auth::user();
if (!in_array($u['role'], ['UL6', 'UL7'], true)) {
    http_response_code(403);
    die('Access denied — UL6 / UL7 only.');
}

// Delegates: sql/import_feeder_hierarchy.php contains all logic and renders
// its own HTML output when not run via CLI.
require __DIR__ . '/../sql/import_feeder_hierarchy.php';
