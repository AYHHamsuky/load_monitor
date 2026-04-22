<?php
// scripts/optimize_database.php
// Run weekly (Sunday at 3 AM): 0 3 * * 0

require_once __DIR__ . '/../app/bootstrap.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting database optimization...\n";

$db = Database::getInstance();
$optimized = 0;
$errors = 0;

try {
    // Get all tables
    $stmt = $db->query("
        SELECT table_name
        FROM information_schema.TABLES
        WHERE table_schema = DATABASE()
    ");
    
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Found " . count($tables) . " tables to optimize\n\n";
    
    foreach ($tables as $table) {
        echo "Optimizing: $table ... ";
        
        try {
            // Optimize table
            $db->query("OPTIMIZE TABLE `$table`");
            echo "✅ Done\n";
            $optimized++;
            
        } catch (Exception $e) {
            echo "❌ Error: " . $e->getMessage() . "\n";
            $errors++;
        }
    }
    
    echo "\n=================================\n";
    echo "Optimization Summary:\n";
    echo "=================================\n";
    echo "Total Tables: " . count($tables) . "\n";
    echo "✅ Optimized: $optimized\n";
    echo "❌ Errors: $errors\n";
    echo "=================================\n\n";
    
    // Analyze tables for query optimization
    echo "Analyzing tables for query optimization...\n";
    foreach ($tables as $table) {
        try {
            $db->query("ANALYZE TABLE `$table`");
            echo "✅ Analyzed: $table\n";
        } catch (Exception $e) {
            echo "❌ Error analyzing $table: " . $e->getMessage() . "\n";
        }
    }
    
    // Get database size
    $stmt = $db->query("
        SELECT 
            ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb
        FROM information_schema.TABLES
        WHERE table_schema = DATABASE()
    ");
    
    $dbSize = $stmt->fetch()['size_mb'];
    echo "\nCurrent Database Size: {$dbSize} MB\n";
    
    // Log the optimization
    AuditLogger::logSystem(
        'DATABASE_OPTIMIZED',
        [
            'tables_optimized' => $optimized,
            'errors' => $errors,
            'database_size_mb' => $dbSize
        ],
        'LOW'
    );
    
    echo "\n✅ Database optimization complete!\n";
    
} catch (Exception $e) {
    echo "❌ Fatal Error: " . $e->getMessage() . "\n";
    error_log("Database optimization failed: " . $e->getMessage());
}

echo "[" . date('Y-m-d H:i:s') . "] Optimization complete\n";