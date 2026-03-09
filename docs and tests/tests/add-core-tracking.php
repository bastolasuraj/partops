<?php
/**
 * Add Core Tracking to Inventory Transactions
 * Adds fields to track actual core costs and rebates per transaction
 */

require_once __DIR__ . '/../../backend/app/Core/Database.php';

use App\Core\Database;

echo "=== Adding Core Tracking to Inventory Transactions ===\n\n";

try {
    $sqlFile = __DIR__ . '/../../backend/database/add_core_tracking_to_transactions.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Remove comments and split into individual statements
    $lines = explode("\n", $sql);
    $cleanedLines = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '--') === 0) {
            continue;
        }
        $cleanedLines[] = $line;
    }
    $cleanedSql = implode("\n", $cleanedLines);
    
    // Split into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $cleanedSql)),
        function($stmt) {
            return !empty($stmt);
        }
    );
    
    echo "Found " . count($statements) . " SQL statements to execute\n\n";
    
    foreach ($statements as $index => $statement) {
        $num = $index + 1;
        $preview = substr(str_replace("\n", " ", $statement), 0, 80);
        echo "[$num] Executing: $preview...\n";
        
        try {
            Database::query($statement);
            echo "    ✓ Success\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), "Duplicate column name") !== false) {
                echo "    ⚠ Warning: Columns already exist (migration already applied?)\n";
            } else {
                throw $e;
            }
        }
        echo "\n";
    }
    
    echo "=== Migration completed successfully! ===\n\n";
    echo "Added columns to inventory_transactions:\n";
    echo "  ✓ has_core - Whether this transaction involves a core item\n";
    echo "  ✓ core_cost - Actual core cost paid for this transaction\n";
    echo "  ✓ core_rebate_expected - Expected rebate amount\n";
    echo "  ✓ core_rebate_received - Actual rebate received\n\n";
    echo "Now you can track variable core costs and rebates per transaction!\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
