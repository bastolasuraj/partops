-- Sample data for PartOps

-- Suppliers
INSERT INTO suppliers (name, contact_email, contact_phone, reorder_url, is_preferred) VALUES
('OEMCo', 'orders@oemco.com', '555-0100', 'https://portal.oemco.com', TRUE),
('AfterParts', 'sales@afterparts.com', '555-0200', 'https://afterparts.com/order', FALSE),
('QuickShip Auto', 'support@quickship.com', '555-0300', 'https://quickship.com/b2b', TRUE)
ON DUPLICATE KEY UPDATE id = id;

-- Locations
INSERT INTO locations (aisle, shelf, bay, bin) VALUES
('A1', 'S1', 'B1', NULL),
('A1', 'S2', 'B3', NULL),
('A2', 'S1', 'B1', NULL),
('B1', 'S1', 'B1', 'BIN-1'),
('B4', 'S1', 'B1', 'BIN-12')
ON DUPLICATE KEY UPDATE id = id;

-- Technicians
INSERT INTO technicians (name, email, phone) VALUES
('John Smith', 'jsmith@example.com', '555-1001'),
('Maria Garcia', 'mgarcia@example.com', '555-1002'),
('Tech Williams', 'twilliams@example.com', '555-1003')
ON DUPLICATE KEY UPDATE id = id;

-- Parts
INSERT INTO parts (anchor_slug, name, description, notes) VALUES
('brake-kit-001', 'Front Brake Kit', 'Front brake kit with pads and rotors', 'Fits 2015-2020 models. Store in climate-controlled area.'),
('filter-042', 'Oil Filter Premium', 'High-performance oil filter', 'Compatible with most domestic vehicles'),
('alternator-055', 'Heavy Duty Alternator', '200A alternator for trucks', 'Core return required'),
('spark-plug-set-012', 'Platinum Spark Plug Set', 'Set of 8 platinum spark plugs', 'For V8 engines')
ON DUPLICATE KEY UPDATE id = id;

-- Part Numbers
INSERT INTO part_numbers (part_id, value, type, manufacturer, is_primary) VALUES
((SELECT id FROM parts WHERE anchor_slug = 'brake-kit-001'), 'OEM-9981', 'active', 'OEMCo', TRUE),
((SELECT id FROM parts WHERE anchor_slug = 'brake-kit-001'), 'ALT-8821', 'aftermarket', 'AfterParts', FALSE),
((SELECT id FROM parts WHERE anchor_slug = 'brake-kit-001'), 'HIST-7701', 'historical', 'OEMCo', FALSE),
((SELECT id FROM parts WHERE anchor_slug = 'filter-042'), 'FIL-2201', 'active', 'OEMCo', TRUE),
((SELECT id FROM parts WHERE anchor_slug = 'filter-042'), 'AF-9921', 'aftermarket', 'AfterParts', FALSE),
((SELECT id FROM parts WHERE anchor_slug = 'alternator-055'), 'ALT-5500', 'active', 'OEMCo', TRUE),
((SELECT id FROM parts WHERE anchor_slug = 'spark-plug-set-012'), 'SPK-1200', 'active', 'OEMCo', TRUE)
ON DUPLICATE KEY UPDATE id = id;

-- Part-Supplier relationships with pricing
INSERT INTO part_suppliers (part_id, supplier_id, sku, price, core_charge, expected_rebate) VALUES
((SELECT id FROM parts WHERE anchor_slug = 'brake-kit-001'), (SELECT id FROM suppliers WHERE name = 'OEMCo'), 'OEM-9981', 125.00, 35.00, 20.00),
((SELECT id FROM parts WHERE anchor_slug = 'brake-kit-001'), (SELECT id FROM suppliers WHERE name = 'AfterParts'), 'ALT-8821', 89.99, 30.00, 18.00),
((SELECT id FROM parts WHERE anchor_slug = 'filter-042'), (SELECT id FROM suppliers WHERE name = 'OEMCo'), 'FIL-2201', 24.99, 0, 0),
((SELECT id FROM parts WHERE anchor_slug = 'filter-042'), (SELECT id FROM suppliers WHERE name = 'AfterParts'), 'AF-9921', 18.99, 0, 0),
((SELECT id FROM parts WHERE anchor_slug = 'alternator-055'), (SELECT id FROM suppliers WHERE name = 'OEMCo'), 'ALT-5500', 289.00, 75.00, 50.00),
((SELECT id FROM parts WHERE anchor_slug = 'spark-plug-set-012'), (SELECT id FROM suppliers WHERE name = 'QuickShip Auto'), 'SPK-1200', 64.99, 0, 0)
ON DUPLICATE KEY UPDATE id = id;

-- Inventory levels
INSERT INTO inventory_levels (part_id, location_id, on_hand, reserved) VALUES
((SELECT id FROM parts WHERE anchor_slug = 'brake-kit-001'), (SELECT id FROM locations WHERE aisle = 'A1' AND shelf = 'S2'), 14, 2),
((SELECT id FROM parts WHERE anchor_slug = 'filter-042'), (SELECT id FROM locations WHERE aisle = 'B4' AND shelf = 'S1'), 62, 0),
((SELECT id FROM parts WHERE anchor_slug = 'alternator-055'), (SELECT id FROM locations WHERE aisle = 'A2' AND shelf = 'S1'), 8, 1),
((SELECT id FROM parts WHERE anchor_slug = 'spark-plug-set-012'), (SELECT id FROM locations WHERE aisle = 'B1' AND shelf = 'S1'), 24, 0)
ON DUPLICATE KEY UPDATE id = id;
