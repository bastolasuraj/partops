<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

echo "Running database migrations...\n\n";

try {
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $port = $_ENV['DB_PORT'] ?? '3306';
    $dbname = $_ENV['DB_NAME'] ?? 'partops_v4';
    $user = $_ENV['DB_USER'] ?? 'root';
    $pass = $_ENV['DB_PASS'] ?? '';

    // Connect without database first to create it if needed
    $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database '{$dbname}' ready\n";

    // Connect to the database
    $pdo->exec("USE `{$dbname}`");

    // Read and execute schema
    $schemaPath = __DIR__ . '/../schema.sql';
    if (!file_exists($schemaPath)) {
        die("Error: schema.sql not found!\n");
    }

    $schema = file_get_contents($schemaPath);
    
    // Split by semicolons and execute each statement
    $statements = array_filter(
        array_map('trim', explode(';', $schema)),
        fn($stmt) => !empty($stmt) && !str_starts_with($stmt, '--')
    );

    foreach ($statements as $statement) {
        if (empty($statement)) continue;
        
        try {
            $pdo->exec($statement);
        } catch (PDOException $e) {
            // Skip if table already exists or other non-critical errors
            if (strpos($e->getMessage(), 'already exists') === false) {
                echo "Warning: " . $e->getMessage() . "\n";
            }
        }
    }

    echo "✓ Schema migrated successfully\n";
    echo "\nMigration complete!\n";

} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
