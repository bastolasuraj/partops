<?php
/**
 * Database Migration Script
 * 
 * Run all SQL migration files in order
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PartOps\Config\Config;
use PartOps\Config\Database;

// Load configuration
$config = Config::load(__DIR__ . '/../.env');

// Connect to database
try {
    $db = Database::connect($config);
    echo "Connected to database successfully.\n";
} catch (Exception $e) {
    echo "Error: Could not connect to database.\n";
    echo $e->getMessage() . "\n";
    exit(1);
}

// Get migration files
$migrationsDir = __DIR__ . '/../database/migrations';
$files = glob($migrationsDir . '/*.sql');
sort($files);

if (empty($files)) {
    echo "No migration files found.\n";
    exit(0);
}

// Run each migration
foreach ($files as $file) {
    $filename = basename($file);
    echo "Running migration: {$filename}...\n";
    
    $sql = file_get_contents($file);
    
    try {
        $db->exec($sql);
        echo "  ✓ {$filename} completed successfully.\n";
    } catch (PDOException $e) {
        echo "  ✗ Error in {$filename}: " . $e->getMessage() . "\n";
        exit(1);
    }
}

echo "\nAll migrations completed successfully!\n";
