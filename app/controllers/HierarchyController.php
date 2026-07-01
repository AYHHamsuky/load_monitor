<?php
/**
 * HierarchyController — read-only viewer of the feeder mapping loaded from
 * sql/feeder_hierarchy.csv into the feeder_hierarchy_map table.
 *
 * Route: ?page=hierarchy
 */
Guard::requireLogin();

$db = Database::connect();

// Guard: table may not exist yet if the import script hasn't been run
try {
    $rows = $db->query("
        SELECT tx_330kv, tx_132kv, region, area_office,
               fdr33kv_name, fdr11kv_name, band, is_dedicated, mapping_source
          FROM feeder_hierarchy_map
      ORDER BY tx_330kv, tx_132kv, region, area_office, fdr33kv_name, fdr11kv_name
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    $rows = null;
}

require __DIR__ . '/../views/hierarchy/index.php';
