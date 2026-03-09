<?php
/**
 * Create Fowler-Supplier Mapping Table
 * This creates a relationship table to track which supplier parts belong to which Fowler PN
 */

require_once __DIR__ . '/../../backend/app/Core/Database.php';

use App\Core\Database;

echo "Creating Fowler-Supplier Mapping Table...\n";
echo str_repeat("=", 50) . "\n\n";

try {
    // Read the SQL file
    $sql = file_get_contents(__DIR__ . '/../../backend/database/create_fowler_supplier_mapping.sql');
    
    // Split into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        fn($stmt) => !empty($stmt)
    );
    
    $pdo = Database::getInstance();
    
    foreach ($statements as $statement) {
        if (empty($statement)) continue;
        
        echo "Executing: " . substr($statement, 0, 80) . "...\n";
        $pdo->exec($statement);
        echo "✓ Success\n\n";
    }
    
    // Verify the table was created
    $result = Database::query("SHOW TABLES LIKE 'fowler_supplier_mapping'")->fetch();
    
    if ($result) {
        echo "✓ Table 'fowler_supplier_mapping' created successfully!\n\n";
        
        // Show count of mappings
        $count = Database::query("SELECT COUNT(*) as count FROM fowler_supplier_mapping")->fetch();
        echo "✓ Populated with {$count['count']} existing part mappings\n\n";
        
        // Show sample data
        echo "Sample mappings:\n";
        echo str_repeat("-", 50) . "\n";
        $samples = Database::query("
            SELECT 
                fsm.fowler_part_number,
                fsm.supplier_part_number,
                s.name as supplier_name,
                p.name as part_name,
                fsm.is_primary
            FROM fowler_supplier_mapping fsm
            LEFT JOIN suppliers s ON fsm.supplier_id = s.id
            LEFT JOIN parts p ON fsm.part_id = p.id
            LIMIT 5
        ")->fetchAll();
        
        foreach ($samples as $sample) {
            echo "Fowler PN: {$sample['fowler_part_number']}\n";
            echo "  → Supplier: {$sample['supplier_name']}\n";
            echo "  → Supplier PN: {$sample['supplier_part_number']}\n";
            echo "  → Part: {$sample['part_name']}\n";
            echo "  → Primary: " . ($sample['is_primary'] ? 'Yes' : 'No') . "\n\n";
        }
    }
    
    echo str_repeat("=", 50) . "\n";
    echo "Migration completed successfully!\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
