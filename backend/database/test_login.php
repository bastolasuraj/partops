<?php
/**
 * Test login functionality
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

use App\Models\User;

echo "Testing authentication...\n\n";

// Test 1: Find user
echo "Test 1: Finding user 'partsadmin'...\n";
$user = User::findByUsername('partsadmin');

if ($user) {
    echo "  ✓ User found\n";
    echo "  - ID: {$user->id}\n";
    echo "  - Username: {$user->username}\n";
    echo "  - Display Name: {$user->display_name}\n";
    echo "  - Role: {$user->role}\n";
    echo "  - Active: " . ($user->is_active ? 'Yes' : 'No') . "\n";
} else {
    echo "  ✗ User not found!\n";
    exit(1);
}

echo "\n";

// Test 2: Verify correct password
echo "Test 2: Verifying correct password...\n";
if ($user->verifyPassword('PartsAdmin123!')) {
    echo "  ✓ Password verified successfully\n";
} else {
    echo "  ✗ Password verification failed!\n";
    exit(1);
}

echo "\n";

// Test 3: Verify incorrect password
echo "Test 3: Verifying incorrect password...\n";
if (!$user->verifyPassword('WrongPassword')) {
    echo "  ✓ Incorrect password correctly rejected\n";
} else {
    echo "  ✗ Incorrect password was accepted!\n";
    exit(1);
}

echo "\n";

// Test 4: Check admin role
echo "Test 4: Checking admin role...\n";
if ($user->isAdmin()) {
    echo "  ✓ User is admin\n";
} else {
    echo "  ✗ User is not admin!\n";
    exit(1);
}

echo "\n";

// Test 5: Session data
echo "Test 5: Getting session data...\n";
$sessionData = $user->toSessionData();
echo "  ✓ Session data generated:\n";
echo "    - Username: {$sessionData['username']}\n";
echo "    - Display Name: {$sessionData['display_name']}\n";
echo "    - Role: {$sessionData['role']}\n";
echo "    - Auth Type: {$sessionData['auth_type']}\n";

echo "\n";
echo "✓ All tests passed!\n";
echo "\nYou can now login with:\n";
echo "  Username: partsadmin\n";
echo "  Password: PartsAdmin123!\n\n";
