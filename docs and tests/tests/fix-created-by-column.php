<?php
/**
 * Fix created_by column to store username (VARCHAR) instead of user ID (INT)
 */

$config = require __DIR__ . '/../../backend/config/database.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "Connected to database: {$config['database']}\n\n";
    
    // Check current column type
    echo "Checking current created_by column type...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM inventory_transactions LIKE 'created_by'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Current Type: {$column['Type']}\n";
    echo "Current Null: {$column['Null']}\n\n";
    
    // Change column type to VARCHAR
    echo "Changing created_by column to VARCHAR(100)...\n";
    $sql = "ALTER TABLE inventory_transactions 
            MODIFY COLUMN created_by VARCHAR(100) NULL";
    
    $pdo->exec($sql);
    echo "✓ Successfully updated created_by column\n\n";
    
    // Verify the change
    echo "Verifying the change...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM inventory_transactions LIKE 'created_by'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "New Type: {$column['Type']}\n";
    echo "New Null: {$column['Null']}\n\n";
    
    echo "✓ All done! The created_by column now stores usernames (VARCHAR) instead of user IDs (INT).\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
