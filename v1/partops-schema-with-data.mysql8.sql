-- MySQL 8 schema and seed data converted from partops-schema-with-data.sql (PostgreSQL)
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS idempotency_keys;
DROP TABLE IF EXISTS inventory_moves;
DROP TABLE IF EXISTS inventory_levels;
DROP TABLE IF EXISTS part_suppliers;
DROP TABLE IF EXISTS part_numbers;
DROP TABLE IF EXISTS qr_codes;
DROP TABLE IF EXISTS locations;
DROP TABLE IF EXISTS parts;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS technicians;
DROP TABLE IF EXISTS work_orders;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    auth_source ENUM('ldap', 'local') NOT NULL DEFAULT 'local',
    password_hash VARCHAR(255),
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    last_login_at DATETIME(6) NULL,
    deleted_at DATETIME(6) NULL,
    CHECK (
        (auth_source = 'ldap' AND role = 'user')
        OR (auth_source = 'local' AND role = 'admin')
    ),
    PRIMARY KEY (id),
    UNIQUE KEY users_username_key (username),
    UNIQUE KEY users_email_key (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE suppliers (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    contact_email VARCHAR(255),
    contact_phone VARCHAR(50),
    reorder_url TEXT,
    is_preferred BOOLEAN NOT NULL DEFAULT FALSE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    deleted_at DATETIME(6),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE technicians (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(50),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    deleted_at DATETIME(6),
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE work_orders (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    external_ref VARCHAR(100) NOT NULL,
    vehicle_ref VARCHAR(255),
    status ENUM('open', 'closed') NOT NULL DEFAULT 'open',
    opened_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    closed_at DATETIME(6),
    PRIMARY KEY (id),
    UNIQUE KEY work_orders_external_ref_key (external_ref),
    KEY idx_work_orders_ref (external_ref)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE parts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    anchor_slug VARCHAR(100) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    notes TEXT,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    deleted_at DATETIME(6),
    PRIMARY KEY (id),
    UNIQUE KEY parts_anchor_slug_key (anchor_slug),
    KEY idx_parts_anchor (anchor_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE locations (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    aisle VARCHAR(20) NOT NULL,
    shelf VARCHAR(20) NOT NULL,
    bay VARCHAR(20) NOT NULL,
    bin VARCHAR(20),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    deleted_at DATETIME(6),
    PRIMARY KEY (id),
    UNIQUE KEY locations_aisle_shelf_bay_bin_key (aisle, shelf, bay, bin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE part_numbers (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    part_id INT UNSIGNED NOT NULL,
    value VARCHAR(100) NOT NULL,
    type ENUM('active', 'historical', 'aftermarket') NOT NULL DEFAULT 'active',
    manufacturer VARCHAR(255),
    is_primary BOOLEAN NOT NULL DEFAULT FALSE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    KEY idx_part_numbers_part_id (part_id),
    KEY idx_part_numbers_value (value),
    FULLTEXT KEY idx_part_numbers_fulltext (value, manufacturer),
    CONSTRAINT part_numbers_part_id_fk FOREIGN KEY (part_id)
        REFERENCES parts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE part_suppliers (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    part_id INT UNSIGNED NOT NULL,
    supplier_id INT UNSIGNED NOT NULL,
    sku VARCHAR(100),
    price DECIMAL(10, 2) NOT NULL DEFAULT 0,
    currency CHAR(3) NOT NULL DEFAULT 'USD',
    core_charge DECIMAL(10, 2) NOT NULL DEFAULT 0,
    expected_rebate DECIMAL(10, 2) NOT NULL DEFAULT 0,
    recorded_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    UNIQUE KEY part_suppliers_part_id_supplier_id_key (part_id, supplier_id),
    KEY idx_part_suppliers_part (part_id),
    KEY idx_part_suppliers_supplier (supplier_id),
    CONSTRAINT part_suppliers_part_id_fk FOREIGN KEY (part_id)
        REFERENCES parts (id) ON DELETE CASCADE,
    CONSTRAINT part_suppliers_supplier_id_fk FOREIGN KEY (supplier_id)
        REFERENCES suppliers (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inventory_levels (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    part_id INT UNSIGNED NOT NULL,
    location_id INT UNSIGNED NOT NULL,
    on_hand INT NOT NULL DEFAULT 0,
    reserved INT NOT NULL DEFAULT 0,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    CHECK (on_hand >= 0),
    CHECK (reserved >= 0),
    PRIMARY KEY (id),
    UNIQUE KEY inventory_levels_part_id_location_id_key (part_id, location_id),
    KEY idx_inventory_levels_part (part_id),
    KEY idx_inventory_levels_location (location_id),
    CONSTRAINT inventory_levels_part_id_fk FOREIGN KEY (part_id)
        REFERENCES parts (id) ON DELETE CASCADE,
    CONSTRAINT inventory_levels_location_id_fk FOREIGN KEY (location_id)
        REFERENCES locations (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE inventory_moves (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    part_id INT UNSIGNED NOT NULL,
    location_id INT UNSIGNED,
    qty INT NOT NULL,
    direction ENUM('in', 'out') NOT NULL,
    reason ENUM('receive', 'checkout', 'return', 'core_return', 'adjust') NOT NULL,
    work_order_id INT UNSIGNED,
    technician_id INT UNSIGNED,
    supplier_id INT UNSIGNED,
    price_at_tx DECIMAL(10, 2),
    currency CHAR(3) NOT NULL DEFAULT 'USD',
    core_charge_at_tx DECIMAL(10, 2),
    core_rebate_expected DECIMAL(10, 2),
    core_rebate_received DECIMAL(10, 2),
    core_due_state ENUM('none', 'due', 'sent', 'rebated') NOT NULL DEFAULT 'none',
    notes TEXT,
    created_by INT UNSIGNED,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    KEY idx_inventory_moves_part (part_id),
    KEY idx_inventory_moves_reason (reason),
    KEY idx_inventory_moves_created (created_at),
    KEY idx_inventory_moves_core_state (core_due_state),
    KEY inventory_moves_location_id_fk (location_id),
    KEY inventory_moves_supplier_id_fk (supplier_id),
    KEY inventory_moves_technician_id_fk (technician_id),
    KEY inventory_moves_work_order_id_fk (work_order_id),
    CONSTRAINT inventory_moves_part_id_fk FOREIGN KEY (part_id)
        REFERENCES parts (id) ON DELETE CASCADE,
    CONSTRAINT inventory_moves_location_id_fk FOREIGN KEY (location_id)
        REFERENCES locations (id),
    CONSTRAINT inventory_moves_supplier_id_fk FOREIGN KEY (supplier_id)
        REFERENCES suppliers (id),
    CONSTRAINT inventory_moves_technician_id_fk FOREIGN KEY (technician_id)
        REFERENCES technicians (id),
    CONSTRAINT inventory_moves_work_order_id_fk FOREIGN KEY (work_order_id)
        REFERENCES work_orders (id),
    CONSTRAINT inventory_moves_created_by_fk FOREIGN KEY (created_by)
        REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_log (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id INT UNSIGNED,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT,
    diff JSON,
    correlation_id VARCHAR(100),
    ip VARCHAR(45),
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    PRIMARY KEY (id),
    KEY idx_audit_log_user (user_id),
    KEY idx_audit_log_entity (entity_type, entity_id),
    KEY idx_audit_log_created (created_at),
    CONSTRAINT audit_log_user_id_fk FOREIGN KEY (user_id)
        REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE idempotency_keys (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    key_value VARCHAR(100) NOT NULL,
    user_id INT UNSIGNED,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    expires_at DATETIME(6) NOT NULL DEFAULT
        (CURRENT_TIMESTAMP(6) + INTERVAL 1 HOUR),
    PRIMARY KEY (id),
    UNIQUE KEY idempotency_keys_key_value_key (key_value),
    KEY idx_idempotency_keys_user (user_id),
    KEY idx_idempotency_keys_expires (expires_at),
    CONSTRAINT idempotency_keys_user_id_fk FOREIGN KEY (user_id)
        REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE qr_codes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    part_id INT UNSIGNED NOT NULL,
    payload TEXT NOT NULL,
    issued_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    expires_at DATETIME(6),
    PRIMARY KEY (id),
    CONSTRAINT qr_codes_part_id_fk FOREIGN KEY (part_id)
        REFERENCES parts (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO users (
    id, username, email, auth_source, password_hash, role, is_active, created_at,
    last_login_at, deleted_at
) VALUES (
    1, 'admin', 'admin@partops.local', 'local',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', TRUE,
    '2025-12-01 22:15:02.696618', '2025-12-02 03:04:43.445487', NULL
);

INSERT INTO suppliers (
    id, name, contact_email, contact_phone, reorder_url, is_preferred, is_active,
    created_at, deleted_at
) VALUES
    (1, 'OEMCo', 'orders@oemco.com', '555-0100', 'https://portal.oemco.com', TRUE,
        TRUE, '2025-12-01 22:15:03.469801', NULL),
    (2, 'AfterParts', 'sales@afterparts.com', '555-0200',
        'https://afterparts.com/order', FALSE, TRUE,
        '2025-12-01 22:15:03.469801', NULL),
    (3, 'QuickShip Auto', 'support@quickship.com', '555-0300',
        'https://quickship.com/b2b', TRUE, TRUE,
        '2025-12-01 22:15:03.469801', NULL);

INSERT INTO technicians (
    id, name, email, phone, is_active, created_at, deleted_at
) VALUES
    (1, 'John Smith', 'jsmith@example.com', '555-1001', TRUE,
        '2025-12-01 22:15:03.624000', NULL),
    (2, 'Maria Garcia', 'mgarcia@example.com', '555-1002', TRUE,
        '2025-12-01 22:15:03.624000', NULL),
    (3, 'Tech Williams', 'twilliams@example.com', '555-1003', TRUE,
        '2025-12-01 22:15:03.624000', NULL);

INSERT INTO parts (
    id, anchor_slug, name, description, notes, is_active, created_at, updated_at,
    deleted_at
) VALUES
    (1, 'brake-kit-001', 'Front Brake Kit',
        'Front brake kit with pads and rotors',
        'Fits 2015-2020 models. Store in climate-controlled area.', TRUE,
        '2025-12-01 22:15:03.699112', '2025-12-01 22:15:03.699112', NULL),
    (2, 'filter-042', 'Oil Filter Premium', 'High-performance oil filter',
        'Compatible with most domestic vehicles', TRUE,
        '2025-12-01 22:15:03.699112', '2025-12-01 22:15:03.699112', NULL),
    (3, 'alternator-055', 'Heavy Duty Alternator', '200A alternator for trucks',
        'Core return required', TRUE, '2025-12-01 22:15:03.699112',
        '2025-12-01 22:15:03.699112', NULL),
    (4, 'spark-plug-set-012', 'Platinum Spark Plug Set',
        'Set of 8 platinum spark plugs', 'For V8 engines', TRUE,
        '2025-12-01 22:15:03.699112', '2025-12-01 22:15:03.699112', NULL);

INSERT INTO locations (
    id, aisle, shelf, bay, bin, is_active, created_at, deleted_at
) VALUES
    (1, 'A1', 'S1', 'B1', NULL, TRUE, '2025-12-01 22:15:03.548009', NULL),
    (2, 'A1', 'S2', 'B3', NULL, TRUE, '2025-12-01 22:15:03.548009', NULL),
    (3, 'A2', 'S1', 'B1', NULL, TRUE, '2025-12-01 22:15:03.548009', NULL),
    (4, 'B1', 'S1', 'B1', 'BIN-1', TRUE, '2025-12-01 22:15:03.548009', NULL),
    (5, 'B4', 'S1', 'B1', 'BIN-12', TRUE, '2025-12-01 22:15:03.548009', NULL);

INSERT INTO part_numbers (
    id, part_id, value, type, manufacturer, is_primary, is_active, created_at
) VALUES
    (1, 1, 'OEM-9981', 'active', 'OEMCo', TRUE, TRUE,
        '2025-12-01 22:15:03.774648'),
    (2, 1, 'ALT-8821', 'aftermarket', 'AfterParts', FALSE, TRUE,
        '2025-12-01 22:15:03.774648'),
    (3, 1, 'HIST-7701', 'historical', 'OEMCo', FALSE, TRUE,
        '2025-12-01 22:15:03.774648'),
    (4, 2, 'FIL-2201', 'active', 'OEMCo', TRUE, TRUE,
        '2025-12-01 22:15:03.774648'),
    (5, 2, 'AF-9921', 'aftermarket', 'AfterParts', FALSE, TRUE,
        '2025-12-01 22:15:03.774648'),
    (6, 3, 'ALT-5500', 'active', 'OEMCo', TRUE, TRUE,
        '2025-12-01 22:15:03.774648'),
    (7, 4, 'SPK-1200', 'active', 'OEMCo', TRUE, TRUE,
        '2025-12-01 22:15:03.774648');

INSERT INTO part_suppliers (
    id, part_id, supplier_id, sku, price, currency, core_charge, expected_rebate,
    recorded_at
) VALUES
    (1, 1, 1, 'OEM-9981', 125.00, 'USD', 35.00, 20.00,
        '2025-12-01 22:15:03.864492'),
    (2, 1, 2, 'ALT-8821', 89.99, 'USD', 30.00, 18.00,
        '2025-12-01 22:15:03.864492'),
    (3, 2, 1, 'FIL-2201', 24.99, 'USD', 0.00, 0.00,
        '2025-12-01 22:15:03.864492'),
    (4, 2, 2, 'AF-9921', 18.99, 'USD', 0.00, 0.00,
        '2025-12-01 22:15:03.864492'),
    (5, 3, 1, 'ALT-5500', 289.00, 'USD', 75.00, 50.00,
        '2025-12-01 22:15:03.864492'),
    (6, 4, 3, 'SPK-1200', 64.99, 'USD', 0.00, 0.00,
        '2025-12-01 22:15:03.864492');

INSERT INTO inventory_levels (
    id, part_id, location_id, on_hand, reserved, created_at, updated_at
) VALUES
    (1, 1, 2, 14, 2, '2025-12-01 22:15:03.941621',
        '2025-12-01 22:15:03.941621'),
    (2, 2, 5, 62, 0, '2025-12-01 22:15:03.941621',
        '2025-12-01 22:15:03.941621'),
    (3, 3, 3, 8, 1, '2025-12-01 22:15:03.941621',
        '2025-12-01 22:15:03.941621'),
    (4, 4, 4, 24, 0, '2025-12-01 22:15:03.941621',
        '2025-12-01 22:15:03.941621');

SET FOREIGN_KEY_CHECKS = 1;

