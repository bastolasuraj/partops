<?php
/**
 * Database Seed Script
 * 
 * Populate database with sample data
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

// Get seed files
$seedsDir = __DIR__ . '/../database/seeds';
$files = glob($seedsDir . '/*.sql');
sort($files);

if (empty($files)) {
    echo "No seed files found.\n";
    exit(0);
}

// Run each seed file
foreach ($files as $file) {
    $filename = basename($file);
    echo "Running seed: {$filename}...\n";
    
    $sql = file_get_contents($file);
    
    try {
        $db->exec($sql);
        echo "  ✓ {$filename} completed successfully.\n";
    } catch (PDOException $e) {
        echo "  ✗ Error in {$filename}: " . $e->getMessage() . "\n";
        // Continue with other seeds even if one fails
    }
}

echo "\nSeeding completed!\n";
