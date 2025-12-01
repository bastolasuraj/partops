<?php
/**
 * Setup Verification Script
 * 
 * Check if the environment is properly configured
 */

declare(strict_types=1);

echo "PartOps Setup Verification\n";
echo "==========================\n\n";

$errors = [];
$warnings = [];

// Check PHP version
echo "Checking PHP version... ";
if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
    echo "✓ " . PHP_VERSION . "\n";
} else {
    echo "✗ " . PHP_VERSION . " (requires 8.1+)\n";
    $errors[] = "PHP version must be 8.1 or higher";
}

// Check required extensions
$requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
echo "\nChecking PHP extensions:\n";
foreach ($requiredExtensions as $ext) {
    echo "  - {$ext}: ";
    if (extension_loaded($ext)) {
        echo "✓\n";
    } else {
        echo "✗\n";
        $errors[] = "Missing required extension: {$ext}";
    }
}

// Check .env file
echo "\nChecking configuration:\n";
echo "  - .env file: ";
if (file_exists(__DIR__ . '/../.env')) {
    echo "✓\n";
} else {
    echo "✗\n";
    $warnings[] = ".env file not found. Copy .env.example to .env";
}

// Check vendor directory
echo "  - Composer dependencies: ";
if (is_dir(__DIR__ . '/../vendor')) {
    echo "✓\n";
} else {
    echo "✗\n";
    $errors[] = "Composer dependencies not installed. Run: composer install";
}

// Check storage directories
$storageDirs = ['storage/logs', 'storage/cache', 'storage/tmp'];
echo "\nChecking storage directories:\n";
foreach ($storageDirs as $dir) {
    $path = __DIR__ . '/../' . $dir;
    echo "  - {$dir}: ";
    if (is_dir($path) && is_writable($path)) {
        echo "✓\n";
    } else {
        echo "✗\n";
        $warnings[] = "Directory {$dir} is not writable";
    }
}

// Try database connection if .env exists
if (file_exists(__DIR__ . '/../.env')) {
    echo "\nChecking database connection:\n";
    require_once __DIR__ . '/../vendor/autoload.php';
    
    try {
        $config = \PartOps\Config\Config::load(__DIR__ . '/../.env');
        $db = \PartOps\Config\Database::connect($config);
        echo "  - Database connection: ✓\n";
        
        // Check if tables exist
        $stmt = $db->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($tables) > 0) {
            echo "  - Database tables: ✓ (" . count($tables) . " tables found)\n";
        } else {
            echo "  - Database tables: ✗\n";
            $warnings[] = "No tables found. Run: make migrate";
        }
    } catch (Exception $e) {
        echo "  - Database connection: ✗\n";
        $warnings[] = "Database connection failed: " . $e->getMessage();
    }
}

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
if (empty($errors) && empty($warnings)) {
    echo "✓ All checks passed! Your environment is ready.\n";
    echo "\nNext steps:\n";
    echo "  1. Run: make run\n";
    echo "  2. Visit: http://localhost:8000\n";
    echo "  3. Login with: admin / admin123\n";
    exit(0);
} else {
    if (!empty($errors)) {
        echo "\n✗ ERRORS:\n";
        foreach ($errors as $error) {
            echo "  - {$error}\n";
        }
    }
    
    if (!empty($warnings)) {
        echo "\n⚠ WARNINGS:\n";
        foreach ($warnings as $warning) {
            echo "  - {$warning}\n";
        }
    }
    
    echo "\nPlease fix the issues above before proceeding.\n";
    exit(1);
}
