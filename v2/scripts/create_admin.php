<?php
/**
 * Create Admin User Script
 * Usage: php scripts/create_admin.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

echo "PartOps v2 - Create Admin User\n";
echo "================================\n\n";

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

    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE username = 'admin'");
    $stmt->execute();
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        echo "Admin user already exists (ID: {$existing['id']})\n";
        echo "Updating password to 'password'...\n";
        
        // Update password
        $passwordHash = password_hash('password', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = 'admin'");
        $stmt->execute([$passwordHash]);
        
        echo "✓ Password updated successfully!\n";
    } else {
        echo "Creating admin user...\n";
        
        // Create admin user
        $passwordHash = password_hash('password', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, auth_source, password_hash, role, is_active)
            VALUES ('admin', 'admin@partops.local', 'local', ?, 'admin', 1)
        ");
        $stmt->execute([$passwordHash]);
        
        echo "✓ Admin user created successfully!\n";
    }

    echo "\nLogin credentials:\n";
    echo "  Username: admin\n";
    echo "  Password: password\n";
    echo "\n⚠ Remember to change the password after first login!\n";

} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    exit(1);
}
