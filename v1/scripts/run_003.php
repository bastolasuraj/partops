<?php
require_once __DIR__ . '/../vendor/autoload.php';
use PartOps\Config\Config;
use PartOps\Config\Database;

$config = Config::load(__DIR__ . '/../.env');
$db = Database::connect($config);

$sql = "ALTER TABLE inventory_moves ADD COLUMN supplier_sku VARCHAR(100) NULL AFTER supplier_id";

try {
    $db->exec($sql);
    echo "Migration 003 applied successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

