<?php
/**
 * Database Migration Script
 * 
 * Usage: php scripts/migrate.php
 */

declare(strict_types=1);

// Load environment
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

echo "PartOps v2 - Database Migration\n";
echo "================================\n\n";

// Database connection
$host = $_ENV['DB_HOST'] ?? 'localhost';
$port = $_ENV['DB_PORT'] ?? '3306';
$database = $_ENV['DB_NAME'] ?? 'partops_v2';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';

/**
 * Parse SQL file into individual statements
 * Handles multi-line statements properly
 */
function parseSqlStatements(string $sql): array {
    // Remove comments
    $sql = preg_replace('/--.*$/m', '', $sql);
    $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
    
    $statements = [];
    $current = '';
    $inString = false;
    $stringChar = '';
    $length = strlen($sql);
    
    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $prev = $i > 0 ? $sql[$i - 1] : '';
        
        // Track string state
        if (($char === "'" || $char === '"') && $prev !== '\\') {
            if (!$inString) {
                $inString = true;
                $stringChar = $char;
            } elseif ($char === $stringChar) {
                $inString = false;
            }
        }
        
        // Check for statement end
        if ($char === ';' && !$inString) {
            $stmt = trim($current);
            if (!empty($stmt)) {
                $statements[] = $stmt;
            }
            $current = '';
        } else {
            $current .= $char;
        }
    }
    
    // Add any remaining statement
    $stmt = trim($current);
    if (!empty($stmt)) {
        $statements[] = $stmt;
    }
    
    return $statements;
}

try {
    // Connect without database first to create it if needed
    $pdo = new PDO(
        "mysql:host={$host};port={$port};charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` 
                CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "✓ Database '{$database}' ready\n";
    
    // Connect to the database
    $pdo->exec("USE `{$database}`");
    
    // Read and execute the schema file - check multiple locations
    $possiblePaths = [
        dirname(__DIR__) . '/database/migrations/001_initial_schema.sql',
        dirname(__DIR__) . '/database_schema.sql',
    ];
    
    $schemaFile = null;
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $schemaFile = $path;
            break;
        }
    }
    
    if ($schemaFile === null) {
        throw new Exception("Schema file not found. Checked:\n  - " . implode("\n  - ", $possiblePaths));
    }
    
    echo "✓ Using schema: " . basename($schemaFile) . "\n\n";
    
    $schema = file_get_contents($schemaFile);
    $statements = parseSqlStatements($schema);
    
    echo "Found " . count($statements) . " SQL statements to execute.\n\n";
    
    $count = 0;
    $errors = 0;
    
    foreach ($statements as $statement) {
        if (empty(trim($statement))) continue;
        
        try {
            $pdo->exec($statement);
            $count++;
            
            // Extract operation type for logging
            if (preg_match('/^CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?/i', $statement, $matches)) {
                echo "✓ Created table: {$matches[1]}\n";
            } elseif (preg_match('/^DROP\s+TABLE\s+(?:IF\s+EXISTS\s+)?`?(\w+)`?/i', $statement, $matches)) {
                echo "✓ Dropped table: {$matches[1]}\n";
            } elseif (preg_match('/^CREATE\s+(?:OR\s+REPLACE\s+)?VIEW\s+`?(\w+)`?/i', $statement, $matches)) {
                echo "✓ Created view: {$matches[1]}\n";
            } elseif (preg_match('/^INSERT\s+(?:IGNORE\s+)?INTO\s+`?(\w+)`?/i', $statement, $matches)) {
                echo "✓ Inserted data into: {$matches[1]}\n";
            } elseif (preg_match('/^SET\s+/i', $statement)) {
                // Silent for SET statements
            } else {
                echo "✓ Executed statement\n";
            }
        } catch (PDOException $e) {
            $errors++;
            // Show warning but continue
            $shortMsg = substr($e->getMessage(), 0, 100);
            echo "⚠ Warning: {$shortMsg}...\n";
        }
    }
    
    echo "\n================================\n";
    echo "Migration complete!\n";
    echo "  Executed: {$count} statements\n";
    if ($errors > 0) {
        echo "  Warnings: {$errors}\n";
    }
    echo "\nDefault admin credentials:\n";
    echo "  Username: admin\n";
    echo "  Password: password\n";
    echo "\n⚠ Remember to change the default password!\n";
    
} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
