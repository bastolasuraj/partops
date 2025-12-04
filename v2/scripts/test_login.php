<?php
/**
 * Test Login Script
 * Usage: php scripts/test_login.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

echo "PartOps v2 - Test Login\n";
echo "========================\n\n";

$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$database = $_ENV['DB_NAME'] ?? 'partops_v2';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Get admin user
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'admin'");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "✗ Admin user not found in database!\n";
        echo "Run: php scripts/create_admin.php\n";
        exit(1);
    }

    echo "✓ Admin user found:\n";
    echo "  ID: {$user['id']}\n";
    echo "  Username: {$user['username']}\n";
    echo "  Email: {$user['email']}\n";
    echo "  Role: {$user['role']}\n";
    echo "  Auth Source: {$user['auth_source']}\n";
    echo "  Is Active: " . ($user['is_active'] ? 'Yes' : 'No') . "\n";
    echo "  Password Hash: " . substr($user['password_hash'], 0, 20) . "...\n\n";

    // Test password verification
    $testPassword = 'password';
    echo "Testing password: '$testPassword'\n";
    
    if (password_verify($testPassword, $user['password_hash'])) {
        echo "✓ Password verification SUCCESSFUL!\n";
        echo "\nYou should be able to login with:\n";
        echo "  Username: admin\n";
        echo "  Password: password\n";
    } else {
        echo "✗ Password verification FAILED!\n";
        echo "\nThe password hash in the database doesn't match 'password'.\n";
        echo "Run: php scripts/create_admin.php to reset it.\n";
    }

} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    exit(1);
}
