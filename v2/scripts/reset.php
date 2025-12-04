<?php
/**
 * Database Reset Script
 * This file should be moved to: scripts/reset.php
 * 
 * WARNING: This will drop ALL tables in the database!
 * Usage: php scripts/reset.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

echo "PartOps v2 - Database Reset\n";
echo "===========================\n\n";
echo "⚠ WARNING: This will DROP ALL TABLES in the database!\n";
echo "Press Ctrl+C to cancel, or Enter to continue...\n";

// Wait for user confirmation (skip in non-interactive mode)
if (posix_isatty(STDIN)) {
    fgets(STDIN);
}

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

    // Disable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Get all tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($tables)) {
        echo "No tables found in database.\n";
    } else {
        echo "Dropping " . count($tables) . " tables...\n";
        
        foreach ($tables as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
            echo "✓ Dropped: {$table}\n";
        }
    }

    // Get all views
    $stmt = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
    $views = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($views)) {
        echo "\nDropping " . count($views) . " views...\n";
        
        foreach ($views as $view) {
            $pdo->exec("DROP VIEW IF EXISTS `{$view}`");
            echo "✓ Dropped view: {$view}\n";
        }
    }

    // Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "\n===========================\n";
    echo "Database reset complete!\n";
    echo "\nRun 'php scripts/migrate.php' to recreate the schema.\n";

} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    exit(1);
}
