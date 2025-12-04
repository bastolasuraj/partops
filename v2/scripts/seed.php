<?php
/**
 * Database Seed Script
 * This file should be moved to: scripts/seed.php
 * 
 * Usage: php scripts/seed.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

echo "PartOps v2 - Database Seeder\n";
echo "============================\n\n";

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

    // Seed Locations
    echo "Seeding locations...\n";
    $locations = [
        ['A1', 'S1', 'B1', null, 'Main aisle, top shelf'],
        ['A1', 'S1', 'B2', null, 'Main aisle, top shelf'],
        ['A1', 'S2', 'B1', null, 'Main aisle, middle shelf'],
        ['A1', 'S2', 'B2', null, 'Main aisle, middle shelf'],
        ['A2', 'S1', 'B1', null, 'Secondary aisle'],
        ['A2', 'S1', 'B2', null, 'Secondary aisle'],
        ['B1', 'S1', 'B1', 'Bin-1', 'Back storage'],
        ['B1', 'S1', 'B1', 'Bin-2', 'Back storage'],
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO locations (aisle, shelf, bay, bin, description) VALUES (?, ?, ?, ?, ?)");
    foreach ($locations as $loc) {
        $stmt->execute($loc);
    }
    echo "✓ Locations seeded\n";

    // Seed Suppliers
    echo "Seeding suppliers...\n";
    $suppliers = [
        ['OEMCo', 'OEM', 'John Smith', 'orders@oemco.com', '555-0100', 'https://portal.oemco.com', 1, 1],
        ['AfterParts Inc', 'AFT', 'Jane Doe', 'sales@afterparts.com', '555-0200', 'https://afterparts.com/order', 0, 0],
        ['QuickShip Auto', 'QSA', 'Mike Johnson', 'support@quickship.com', '555-0300', 'https://quickship.com/b2b', 1, 0],
        ['Budget Parts', 'BUD', 'Sarah Wilson', 'info@budgetparts.com', '555-0400', null, 0, 0],
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO suppliers (name, code, contact_name, email, phone, reorder_url, is_preferred, is_oem) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($suppliers as $sup) {
        $stmt->execute($sup);
    }
    echo "✓ Suppliers seeded\n";

    // Seed Technicians
    echo "Seeding technicians...\n";
    $technicians = [
        ['EMP001', 'John Smith', 'jsmith@example.com', '555-1001', 'Service'],
        ['EMP002', 'Jane Doe', 'jdoe@example.com', '555-1002', 'Service'],
        ['EMP003', 'Mike Johnson', 'mjohnson@example.com', '555-1003', 'Heavy Equipment'],
        ['EMP004', 'Sarah Wilson', 'swilson@example.com', '555-1004', 'Service'],
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO technicians (employee_id, name, email, phone, department) VALUES (?, ?, ?, ?, ?)");
    foreach ($technicians as $tech) {
        $stmt->execute($tech);
    }
    echo "✓ Technicians seeded\n";

    // Seed Parts
    echo "Seeding parts...\n";
    $parts = [
        ['brake-kit-001', 'Front Brake Kit', 'Complete front brake kit with pads and rotors', 'Brakes', 5],
        ['filter-oil-042', 'Oil Filter', 'Standard oil filter for most vehicles', 'Filters', 20],
        ['filter-air-088', 'Air Filter', 'Engine air filter', 'Filters', 15],
        ['spark-plug-12', 'Spark Plug Set', 'Set of 4 spark plugs', 'Ignition', 10],
        ['alternator-55', 'Alternator', 'Remanufactured alternator', 'Electrical', 3],
        ['starter-motor-12', 'Starter Motor', 'Remanufactured starter motor', 'Electrical', 3],
        ['belt-serpentine-22', 'Serpentine Belt', 'Drive belt', 'Belts', 8],
        ['coolant-flush-kit', 'Coolant Flush Kit', 'Complete coolant flush kit', 'Cooling', 5],
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO parts (anchor_slug, name, description, category, min_stock_level) VALUES (?, ?, ?, ?, ?)");
    foreach ($parts as $part) {
        $stmt->execute($part);
    }
    echo "✓ Parts seeded\n";

    // Seed Part Numbers
    echo "Seeding part numbers...\n";
    $partNumbers = [
        [1, 'OEM-9981', 'active', 'OEMCo', 1],
        [1, 'ALT-8821', 'aftermarket', 'AfterParts', 0],
        [1, 'HIST-7701', 'historical', 'OEMCo', 0],
        [2, 'FIL-2201', 'active', 'OEMCo', 1],
        [2, 'AF-9921', 'aftermarket', 'AfterParts', 0],
        [3, 'AIR-3301', 'active', 'OEMCo', 1],
        [4, 'SPK-4401', 'active', 'OEMCo', 1],
        [5, 'ALT-5501', 'active', 'OEMCo', 1],
        [6, 'STR-6601', 'active', 'OEMCo', 1],
        [7, 'BLT-7701', 'active', 'OEMCo', 1],
        [8, 'CLT-8801', 'active', 'OEMCo', 1],
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO part_numbers (part_id, number, type, manufacturer, is_primary) VALUES (?, ?, ?, ?, ?)");
    foreach ($partNumbers as $pn) {
        $stmt->execute($pn);
    }
    echo "✓ Part numbers seeded\n";

    // Seed Part Suppliers (pricing)
    echo "Seeding part-supplier relationships...\n";
    $partSuppliers = [
        [1, 1, 'OEM-9981', 125.00, 35.00, 20.00, 1], // Brake kit from OEMCo
        [1, 2, 'ALT-8821', 89.99, 30.00, 18.00, 0],  // Brake kit from AfterParts
        [2, 1, 'FIL-2201', 12.99, 0, 0, 1],          // Oil filter from OEMCo
        [2, 2, 'AF-9921', 8.99, 0, 0, 0],            // Oil filter from AfterParts
        [3, 1, 'AIR-3301', 24.99, 0, 0, 1],          // Air filter
        [4, 1, 'SPK-4401', 32.99, 0, 0, 1],          // Spark plugs
        [5, 1, 'ALT-5501', 189.99, 45.00, 30.00, 1], // Alternator with core
        [6, 1, 'STR-6601', 159.99, 40.00, 25.00, 1], // Starter with core
        [7, 1, 'BLT-7701', 29.99, 0, 0, 1],          // Belt
        [8, 1, 'CLT-8801', 45.99, 0, 0, 1],          // Coolant kit
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO part_suppliers (part_id, supplier_id, supplier_sku, price, core_charge, expected_rebate, is_preferred) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($partSuppliers as $ps) {
        $stmt->execute($ps);
    }
    echo "✓ Part-supplier relationships seeded\n";

    // Seed Inventory Levels
    echo "Seeding inventory levels...\n";
    $inventoryLevels = [
        [1, 1, 14, 2],  // Brake kit at A1/S1/B1
        [2, 2, 62, 0],  // Oil filter at A1/S1/B2
        [3, 3, 45, 5],  // Air filter at A1/S2/B1
        [4, 4, 28, 0],  // Spark plugs at A1/S2/B2
        [5, 5, 6, 1],   // Alternator at A2/S1/B1
        [6, 6, 4, 0],   // Starter at A2/S1/B2
        [7, 7, 18, 0],  // Belt at B1/S1/B1/Bin-1
        [8, 8, 12, 0],  // Coolant kit at B1/S1/B1/Bin-2
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO inventory_levels (part_id, location_id, on_hand, reserved) VALUES (?, ?, ?, ?)");
    foreach ($inventoryLevels as $il) {
        $stmt->execute($il);
    }
    echo "✓ Inventory levels seeded\n";

    // Seed Work Orders
    echo "Seeding work orders...\n";
    $workOrders = [
        ['WO-2024-001', 'Unit-101', 'Front brake replacement', 'open', 'normal', 1],
        ['WO-2024-002', 'Unit-205', 'Oil change and filter', 'in_progress', 'normal', 2],
        ['WO-2024-003', 'Unit-118', 'Alternator replacement', 'completed', 'high', 1],
        ['WO-2024-004', 'Unit-302', 'Full service', 'open', 'low', 3],
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO work_orders (wo_number, unit_number, description, status, priority, technician_id) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($workOrders as $wo) {
        $stmt->execute($wo);
    }
    echo "✓ Work orders seeded\n";

    // Seed some inventory moves (audit trail)
    echo "Seeding inventory movements...\n";
    $moves = [
        [1, 1, 1, 20, 'in', 'receive_new', null, null, 1, 125.00, 35.00, 20.00, 'none', 'Initial stock'],
        [2, 2, 2, 100, 'in', 'receive_new', null, null, 1, 12.99, 0, 0, 'none', 'Initial stock'],
        [1, 1, 1, -2, 'out', 'checkout', 1, 1, null, 125.00, 35.00, 20.00, 'none', 'Front brake job'],
        [2, 2, 2, -4, 'out', 'checkout', 2, 2, null, 12.99, 0, 0, 'none', 'Oil change'],
    ];
    
    $stmt = $pdo->prepare("INSERT INTO inventory_moves (part_id, part_number_id, location_id, qty, direction, reason, work_order_id, technician_id, supplier_id, price_at_tx, core_charge_at_tx, core_rebate_expected, core_due_state, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($moves as $move) {
        $stmt->execute($move);
    }
    echo "✓ Inventory movements seeded\n";

    // Seed Core Liabilities
    echo "Seeding core liabilities...\n";
    $coreLiabilities = [
        [3, 1, 1, 1, 2, 0, 35.00, 20.00, date('Y-m-d', strtotime('+30 days')), 'pending'],
        [3, 5, 5, 1, 1, 0, 45.00, 30.00, date('Y-m-d', strtotime('+15 days')), 'pending'],
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO core_liabilities (receive_move_id, part_id, part_number_id, supplier_id, qty_due, qty_returned, core_charge, expected_rebate, due_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($coreLiabilities as $cl) {
        $stmt->execute($cl);
    }
    echo "✓ Core liabilities seeded\n";

    echo "\n============================\n";
    echo "Seeding complete!\n";
    
} catch (PDOException $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
    exit(1);
}
