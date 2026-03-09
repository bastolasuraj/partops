<?php
/**
 * Test database connection
 */

echo "Testing database connection...\n\n";

// Test 1: Check if pdo_mysql is loaded
echo "1. Checking PDO MySQL extension: ";
if (extension_loaded('pdo_mysql')) {
    echo "✓ LOADED\n";
} else {
    echo "✗ NOT LOADED\n";
    exit(1);
}

// Test 2: Load config
echo "2. Loading database config: ";
$config = require __DIR__ . '/../../backend/config/database.php';
echo "✓ OK\n";
echo "   Host: {$config['host']}\n";
echo "   Database: {$config['database']}\n";
echo "   Username: {$config['username']}\n";

// Test 3: Try to connect
echo "3. Attempting connection: ";
try {
    $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "✓ CONNECTED\n";
    
    // Test 4: Query the database
    echo "4. Testing query: ";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM parts");
    $result = $stmt->fetch();
    echo "✓ OK (Found {$result['count']} parts)\n";
    
    echo "\n✓ All tests passed! Database connection is working.\n";
    
} catch (PDOException $e) {
    echo "✗ FAILED\n";
    echo "   Error: " . $e->getMessage() . "\n";
    echo "\nPossible issues:\n";
    echo "- Database server is not accessible from this machine\n";
    echo "- Firewall blocking connection\n";
    echo "- Wrong credentials\n";
    echo "- Database does not exist\n";
    exit(1);
}
