<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

use App\Core\Database;

echo "Seeding database...\n\n";

try {
    $db = Database::getInstance();

    // Clear existing data
    echo "Clearing existing data...\n";
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    $db->exec("TRUNCATE TABLE returns");
    $db->exec("TRUNCATE TABLE work_order_returns");
    $db->exec("TRUNCATE TABLE part_checkouts");
    $db->exec("TRUNCATE TABLE part_checkins");
    $db->exec("TRUNCATE TABLE work_orders");
    $db->exec("TRUNCATE TABLE parts");
    $db->exec("TRUNCATE TABLE technicians");
    $db->exec("TRUNCATE TABLE suppliers");
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");

    // Seed Suppliers
    echo "Seeding suppliers...\n";
    $suppliers = [
        ['Acme Parts Co.', '(555) 123-4567', 'sales@acmeparts.com', '123 Industrial Blvd, City, State 12345', 'https://acmeparts.com'],
        ['Global Hydraulics', '(555) 987-6543', 'info@globalhydraulics.com', '456 Manufacturing Ave, City, State 67890', 'https://globalhydraulics.com'],
        ['Premium Auto Supply', '(555) 555-1234', 'orders@premiumauto.com', '789 Parts Lane, City, State 11111', 'https://premiumauto.com'],
    ];

    foreach ($suppliers as $supplier) {
        Database::query(
            "INSERT INTO suppliers (name, phone, email, address, url) VALUES (?, ?, ?, ?, ?)",
            $supplier
        );
    }
    echo "✓ Seeded " . count($suppliers) . " suppliers\n";

    // Seed Technicians
    echo "Seeding technicians...\n";
    $technicians = [
        ['John Smith', '(555) 111-2222', 'john.smith@company.com'],
        ['Sarah Johnson', '(555) 333-4444', 'sarah.j@company.com'],
        ['Mike Davis', '(555) 555-6666', 'mike.davis@company.com'],
    ];

    foreach ($technicians as $tech) {
        Database::query(
            "INSERT INTO technicians (name, phone, email) VALUES (?, ?, ?)",
            $tech
        );
    }
    echo "✓ Seeded " . count($technicians) . " technicians\n";

    // Seed Parts
    echo "Seeding parts...\n";
    $parts = [
        ['FWL-12345', 'Hydraulic Pump Assembly', 1, 'SUP-98765', 'A', '3', 'B', 5, 'https://acmeparts.com/hydraulic-pump', 'High-pressure hydraulic pump assembly'],
        ['FWL-67890', 'Brake Pad Set', 2, 'GH-54321', 'B', '2', 'A', 5, null, 'Premium ceramic brake pad set'],
        ['FWL-11111', 'Oil Filter', 3, 'PAS-11111', 'C', '1', 'C', 10, null, 'Standard oil filter for routine maintenance'],
        ['FWL-22222', 'Air Filter', 1, 'SUP-22222', 'C', '1', 'D', 8, null, 'High-efficiency air filter'],
        ['FWL-33333', 'Transmission Fluid', 2, 'GH-33333', 'D', '4', 'A', 20, null, 'Synthetic transmission fluid'],
    ];

    foreach ($parts as $part) {
        Database::query(
            "INSERT INTO parts (fowler_part_number, name, supplier_id, supplier_part_number, location_aisle, location_shelf, location_bay, low_stock_threshold, url, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            $part
        );
    }
    echo "✓ Seeded " . count($parts) . " parts\n";

    // Seed Work Orders
    echo "Seeding work orders...\n";
    $workOrders = [
        ['WO-2024-001', 1, 'UNIT-456'],
        ['WO-2024-002', 2, 'UNIT-789'],
        ['WO-2024-003', 3, 'UNIT-123'],
    ];

    foreach ($workOrders as $wo) {
        Database::query(
            "INSERT INTO work_orders (wo_number, technician_id, unit_number) VALUES (?, ?, ?)",
            $wo
        );
    }
    echo "✓ Seeded " . count($workOrders) . " work orders\n";

    // Seed Part Check-ins
    echo "Seeding part check-ins...\n";
    $checkins = [
        [1, 1, 'SUP-98765', 125.50, false, null, 10, 'Regular stock replenishment'],
        [2, 2, 'GH-54321', 89.99, false, null, 15, 'Monthly restock'],
        [3, 3, 'PAS-11111', 15.75, false, null, 25, 'Bulk order'],
        [4, 1, 'SUP-22222', 22.50, false, null, 30, 'Seasonal stock'],
        [5, 2, 'GH-33333', 45.00, false, null, 50, 'Transmission fluid bulk'],
    ];

    foreach ($checkins as $checkin) {
        Database::query(
            "INSERT INTO part_checkins (part_id, supplier_id, supplier_part_number, price, has_core_charge, expected_rebate, quantity, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            $checkin
        );
    }
    echo "✓ Seeded " . count($checkins) . " check-ins\n";

    // Seed Part Checkouts
    echo "Seeding part checkouts...\n";
    $checkouts = [
        [1, 1, 'UNIT-456', 1, 'SUP-98765', 3],
        [1, 1, 'UNIT-456', 2, 'GH-54321', 2],
        [2, 2, 'UNIT-789', 2, 'GH-54321', 3],
        [3, 3, 'UNIT-123', 1, 'SUP-98765', 5],
    ];

    foreach ($checkouts as $checkout) {
        Database::query(
            "INSERT INTO part_checkouts (work_order_id, technician_id, unit_number, part_id, supplier_part_number, quantity) VALUES (?, ?, ?, ?, ?, ?)",
            $checkout
        );
    }
    echo "✓ Seeded " . count($checkouts) . " checkouts\n";

    // Seed Work Order Returns
    echo "Seeding work order returns...\n";
    $returns = [
        [1, 1, 1, 2],
        [2, 2, 3, 2],
    ];

    foreach ($returns as $return) {
        Database::query(
            "INSERT INTO work_order_returns (work_order_id, part_id, return_quantity, required_quantity) VALUES (?, ?, ?, ?)",
            $return
        );
    }
    echo "✓ Seeded " . count($returns) . " work order returns\n";

    echo "\n✓ Database seeded successfully!\n";

} catch (Exception $e) {
    die("Seeding failed: " . $e->getMessage() . "\n");
}
