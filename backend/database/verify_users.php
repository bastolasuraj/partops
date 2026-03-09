<?php
/**
 * Verify users table and admin user
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
    echo "Verifying users table...\n\n";
    
    $db = Database::getInstance();
    
    // Check if table exists
    $stmt = $db->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() === 0) {
        echo "✗ Users table does not exist!\n";
        echo "Run: php apply_users_table.php\n\n";
        exit(1);
    }
    
    echo "✓ Users table exists\n\n";
    
    // Get all users
    $stmt = $db->query("SELECT id, username, display_name, email, role, is_active, last_login FROM users ORDER BY id");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "✗ No users found in database!\n\n";
        exit(1);
    }
    
    echo "Users in database:\n";
    echo str_repeat("-", 80) . "\n";
    printf("%-5s %-15s %-25s %-10s %-10s %s\n", "ID", "Username", "Display Name", "Role", "Status", "Last Login");
    echo str_repeat("-", 80) . "\n";
    
    foreach ($users as $user) {
        printf(
            "%-5s %-15s %-25s %-10s %-10s %s\n",
            $user['id'],
            $user['username'],
            $user['display_name'],
            $user['role'],
            $user['is_active'] ? 'Active' : 'Inactive',
            $user['last_login'] ?? 'Never'
        );
    }
    
    echo str_repeat("-", 80) . "\n\n";
    
    // Check admin user
    $stmt = $db->query("SELECT * FROM users WHERE username = 'partsadmin'");
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "✓ Admin user 'partsadmin' found\n";
        echo "  Role: " . $admin['role'] . "\n";
        echo "  Status: " . ($admin['is_active'] ? 'Active' : 'Inactive') . "\n";
        
        // Test password verification
        if (password_verify('PartsAdmin123!', $admin['password_hash'])) {
            echo "  Password: ✓ Verified (default password is set)\n";
            echo "\n⚠ Remember to change the default password!\n";
        } else {
            echo "  Password: ✗ Default password not working\n";
        }
    } else {
        echo "✗ Admin user 'partsadmin' not found!\n";
    }
    
    echo "\n";
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
