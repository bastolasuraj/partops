<?php
/**
 * Simplify Work Orders Table
 * Removes: technician_id, unit_id, unit_number, description, status
 * Keeps: id, wo_number, created_at, updated_at
 */

require_once __DIR__ . '/../../backend/app/Core/Database.php';

use App\Core\Database;

echo "=== Simplifying Work Orders Table ===\n\n";
echo "This will remove the following columns:\n";
echo "  - technician_id\n";
echo "  - unit_id\n";
echo "  - unit_number\n";
echo "  - description\n";
echo "  - status\n\n";

try {
    // Read the SQL file
    $sqlFile = __DIR__ . '/../../backend/database/simplify_work_orders.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Remove comments and split into individual statements
    $lines = explode("\n", $sql);
    $cleanedLines = [];
    foreach ($lines as $line) {
        $line = trim($line);
        // Skip empty lines and comment lines
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
            // Check if it's a "Can't DROP" error (constraint doesn't exist)
            if (strpos($e->getMessage(), "Can't DROP") !== false || 
                strpos($e->getMessage(), "check that column/key exists") !== false) {
                echo "    ⚠ Warning: Constraint or column may not exist (already removed?)\n";
                echo "    Continuing...\n";
            } else {
                throw $e;
            }
        }
        echo "\n";
    }
    
    echo "=== Migration completed successfully! ===\n\n";
    echo "Work Orders table now contains only:\n";
    echo "  ✓ id (Primary Key)\n";
    echo "  ✓ wo_number (Unique identifier)\n";
    echo "  ✓ created_at (Timestamp)\n";
    echo "  ✓ updated_at (Timestamp)\n\n";
    echo "The table is now simplified for basic work order tracking.\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
