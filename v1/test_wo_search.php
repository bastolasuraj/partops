<?php
// Test script to debug work order search
require __DIR__ . '/app/bootstrap.php';

use PartOps\Config\Database;

$db = Database::getConnection();

echo "Testing work order search...\n\n";

// Test 1: Check if any work orders exist
$sql = "SELECT COUNT(*) as count FROM inventory_moves WHERE work_order_ref IS NOT NULL AND work_order_ref != ''";
$stmt = $db->query($sql);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "Total work orders in database: " . $result['count'] . "\n\n";

// Test 2: Show some work orders
$sql = "SELECT DISTINCT work_order_ref FROM inventory_moves WHERE work_order_ref IS NOT NULL AND work_order_ref != '' LIMIT 10";
$stmt = $db->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Sample work orders:\n";
foreach ($results as $row) {
    echo "  - " . $row['work_order_ref'] . "\n";
}
echo "\n";

// Test 3: Try the search query with 'WO'
$query = 'WO';
$sql = "SELECT DISTINCT work_order_ref, unit_number 
        FROM inventory_moves 
        WHERE work_order_ref IS NOT NULL 
        AND work_order_ref != '' 
        AND work_order_ref LIKE ? 
        ORDER BY work_order_ref DESC 
        LIMIT 20";
$stmt = $db->prepare($sql);
$stmt->execute(['%' . $query . '%']);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Search results for 'WO': " . count($results) . " found\n";
foreach ($results as $row) {
    echo "  - " . $row['work_order_ref'] . " (Unit: " . ($row['unit_number'] ?? 'N/A') . ")\n";
}
