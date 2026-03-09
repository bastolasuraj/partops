<?php
/**
 * Apply users table migration
 * Run this script to create the users table and add the default admin user
 */

// Set timezone
date_default_timezone_set('America/Toronto');

// Autoload classes
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../app/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

use App\Core\Database;

try {
    echo "Creating users table...\n\n";
    
    // Get database connection
    $db = Database::getInstance();
    
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/create_users_table.sql');
    
    // Remove comments
    $sql = preg_replace('/--.*$/m', '', $sql);
    
    // Split by semicolon and execute each statement
    $statements = explode(';', $sql);
    
    $executed = 0;
    foreach ($statements as $statement) {
        // Clean up the statement
        $statement = trim($statement);
        
        // Skip empty statements
        if (empty($statement)) {
            continue;
        }
        
        echo "Executing: " . substr(preg_replace('/\s+/', ' ', $statement), 0, 60) . "...\n";
        try {
            $result = $db->exec($statement);
            echo "  ✓ Success (affected rows: " . ($result !== false ? $result : 0) . ")\n";
            $executed++;
        } catch (Exception $e) {
            echo "  ✗ Failed: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
    
    echo "\nTotal statements executed: $executed\n";
    
    echo "\n✓ Users table created successfully!\n";
    echo "✓ Default admin user created:\n";
    echo "  Username: partsadmin\n";
    echo "  Password: PartsAdmin123!\n";
    echo "\n⚠ IMPORTANT: Change the admin password after first login!\n\n";
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
