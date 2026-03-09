<?php
/**
 * Fix transaction_type ENUM to use 'outgoing' instead of 'checkout'
 */

require __DIR__ . '/../../backend/config/database.php';

$config = require __DIR__ . '/../../backend/config/database.php';

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}",
        $config['username'],
        $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "Connected to database: {$config['database']}\n\n";
    
    // Check current ENUM values
    echo "Checking current transaction_type ENUM values...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM inventory_transactions LIKE 'transaction_type'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Current Type: {$column['Type']}\n\n";
    
    // First, add 'outgoing' to the ENUM temporarily
    echo "Step 1: Adding 'outgoing' to ENUM values...\n";
    $sql = "ALTER TABLE inventory_transactions 
            MODIFY COLUMN transaction_type ENUM('incoming', 'checkout', 'outgoing', 'return', 'adjustment') NOT NULL";
    $pdo->exec($sql);
    echo "✓ Added 'outgoing' to ENUM\n\n";
    
    // Update all 'checkout' values to 'outgoing'
    echo "Step 2: Updating existing 'checkout' records to 'outgoing'...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM inventory_transactions WHERE transaction_type = 'checkout'");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Found {$count} records with 'checkout' type\n";
    
    if ($count > 0) {
        $pdo->exec("UPDATE inventory_transactions SET transaction_type = 'outgoing' WHERE transaction_type = 'checkout'");
        echo "✓ Updated {$count} records from 'checkout' to 'outgoing'\n\n";
    }
    
    // Now remove 'checkout' from the ENUM
    echo "Step 3: Removing 'checkout' from ENUM values...\n";
    $sql = "ALTER TABLE inventory_transactions 
            MODIFY COLUMN transaction_type ENUM('incoming', 'outgoing', 'return', 'adjustment') NOT NULL";
    $pdo->exec($sql);
    echo "✓ Successfully updated transaction_type column\n\n";
    
    // Verify the change
    echo "Verifying the change...\n";
    $stmt = $pdo->query("SHOW COLUMNS FROM inventory_transactions LIKE 'transaction_type'");
    $column = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "New Type: {$column['Type']}\n\n";
    
    echo "✓ All done! The transaction_type column now uses 'outgoing' instead of 'checkout'.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
