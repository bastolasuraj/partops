<?php
/**
 * Apply Unique Constraint Fix
 * Changes the unique constraint from fowler_part_number to supplier_part_number
 */

require_once __DIR__ . '/../../backend/app/Core/Database.php';

use App\Core\Database;

echo "=== Applying Unique Constraint Fix ===\n\n";

try {
    // Read the SQL file
    $sqlFile = __DIR__ . '/../../backend/database/fix_unique_constraint.sql';
    
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
        echo "[$num] Executing: " . substr($statement, 0, 80) . "...\n";
        
        try {
            Database::query($statement);
            echo "    ✓ Success\n";
        } catch (PDOException $e) {
            // Check if it's a "duplicate key" error for supplier_part_number
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo "    ⚠ Warning: Duplicate supplier_part_number found!\n";
                echo "    You need to clean up duplicate supplier part numbers before applying this fix.\n";
                echo "    Error: " . $e->getMessage() . "\n";
                
                // Show duplicates
                echo "\n    Finding duplicate supplier part numbers...\n";
                $duplicates = Database::query("
                    SELECT supplier_part_number, COUNT(*) as count 
                    FROM parts 
                    WHERE supplier_part_number IS NOT NULL 
                    AND supplier_part_number != ''
                    GROUP BY supplier_part_number 
                    HAVING count > 1
                ")->fetchAll();
                
                if (!empty($duplicates)) {
                    echo "    Duplicates found:\n";
                    foreach ($duplicates as $dup) {
                        echo "      - '{$dup['supplier_part_number']}' appears {$dup['count']} times\n";
                    }
                    echo "\n    Please manually update these records to have unique supplier_part_numbers.\n";
                }
                
                throw $e;
            } else {
                throw $e;
            }
        }
        echo "\n";
    }
    
    echo "=== Migration completed successfully! ===\n\n";
    echo "Summary of changes:\n";
    echo "  ✓ Removed UNIQUE constraint from fowler_part_number\n";
    echo "  ✓ Added UNIQUE constraint to supplier_part_number\n";
    echo "  ✓ Added index on fowler_part_number for faster lookups\n\n";
    echo "Now multiple supplier parts can share the same Fowler PN!\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
