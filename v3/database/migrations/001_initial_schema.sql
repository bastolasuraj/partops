-- PartOps Database Schema (MySQL 8)

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL UNIQUE,
    auth_source ENUM('ldap', 'local') NOT NULL DEFAULT 'local',
    password_hash VARCHAR(255),
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    last_login_at DATETIME(6) NULL,
    deleted_at DATETIME(6) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS parts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    anchor_slug VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    notes TEXT,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6) ON UPDATE CURRENT_TIMESTAMP(6),
    deleted_at DATETIME(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS part_numbers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    part_id INT UNSIGNED NOT NULL,
    value VARCHAR(100) NOT NULL,
    type ENUM('active', 'historical', 'aftermarket') NOT NULL DEFAULT 'active',
    manufacturer VARCHAR(255),
    is_primary BOOLEAN NOT NULL DEFAULT FALSE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    INDEX idx_part_numbers_part_id (part_id),
    INDEX idx_part_numbers_value (value),
    FULLTEXT KEY idx_part_numbers_fulltext (value, manufacturer),
    CONSTRAINT fk_part_numbers_part FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS suppliers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    contact_email VARCHAR(255),
    contact_phone VARCHAR(50),
    reorder_url TEXT,
    is_preferred BOOLEAN NOT NULL DEFAULT FALSE,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    deleted_at DATETIME(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS part_suppliers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    part_id INT UNSIGNED NOT NULL,
    supplier_id INT UNSIGNED NOT NULL,
    sku VARCHAR(100),
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    currency CHAR(3) NOT NULL DEFAULT 'USD',
    core_charge DECIMAL(10,2) NOT NULL DEFAULT 0,
    expected_rebate DECIMAL(10,2) NOT NULL DEFAULT 0,
    recorded_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    UNIQUE KEY part_suppliers_part_id_supplier_id_key (part_id, supplier_id),
    INDEX idx_part_suppliers_part (part_id),
    INDEX idx_part_suppliers_supplier (supplier_id),
    CONSTRAINT fk_part_suppliers_part FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE,
    CONSTRAINT fk_part_suppliers_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aisle VARCHAR(20) NOT NULL,
    shelf VARCHAR(20) NOT NULL,
    bay VARCHAR(20) NOT NULL,
    bin VARCHAR(20),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    deleted_at DATETIME(6),
    UNIQUE KEY locations_aisle_shelf_bay_bin_key (aisle, shelf, bay, bin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS technicians (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    phone VARCHAR(50),
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    deleted_at DATETIME(6)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS work_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    external_ref VARCHAR(100) NOT NULL UNIQUE,
    vehicle_ref VARCHAR(255),
    status ENUM('open', 'closed') NOT NULL DEFAULT 'open',
    opened_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    closed_at DATETIME(6),
    INDEX idx_work_orders_ref (external_ref)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_levels (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    part_id INT UNSIGNED NOT NULL,
    location_id INT UNSIGNED NOT NULL,
    on_hand INT NOT NULL DEFAULT 0,
    reserved INT NOT NULL DEFAULT 0,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    updated_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    UNIQUE KEY inventory_levels_part_id_location_id_key (part_id, location_id),
    INDEX idx_inventory_levels_part (part_id),
    INDEX idx_inventory_levels_location (location_id),
    CONSTRAINT chk_inventory_levels_on_hand CHECK (on_hand >= 0),
    CONSTRAINT chk_inventory_levels_reserved CHECK (reserved >= 0),
    CONSTRAINT fk_inventory_levels_part FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE,
    CONSTRAINT fk_inventory_levels_location FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS inventory_moves (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    part_id INT UNSIGNED NOT NULL,
    location_id INT UNSIGNED NULL,
    qty INT NOT NULL,
    direction ENUM('in', 'out') NOT NULL,
    reason ENUM('receive', 'checkout', 'return', 'core_return', 'adjust') NOT NULL,
    work_order_id INT UNSIGNED NULL,
    technician_id INT UNSIGNED NULL,
    supplier_id INT UNSIGNED NULL,
    price_at_tx DECIMAL(10,2),
    currency CHAR(3) NOT NULL DEFAULT 'USD',
    core_charge_at_tx DECIMAL(10,2),
    core_rebate_expected DECIMAL(10,2),
    core_rebate_received DECIMAL(10,2),
    core_due_state ENUM('none', 'due', 'sent', 'rebated') NOT NULL DEFAULT 'none',
    notes TEXT,
    created_by INT UNSIGNED NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    INDEX idx_inventory_moves_part (part_id),
    INDEX idx_inventory_moves_reason (reason),
    INDEX idx_inventory_moves_core_state (core_due_state),
    INDEX idx_inventory_moves_created (created_at),
    CONSTRAINT fk_inventory_moves_part FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE,
    CONSTRAINT fk_inventory_moves_location FOREIGN KEY (location_id) REFERENCES locations(id),
    CONSTRAINT fk_inventory_moves_work_order FOREIGN KEY (work_order_id) REFERENCES work_orders(id),
    CONSTRAINT fk_inventory_moves_technician FOREIGN KEY (technician_id) REFERENCES technicians(id),
    CONSTRAINT fk_inventory_moves_supplier FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    CONSTRAINT fk_inventory_moves_created_by FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS qr_codes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    part_id INT UNSIGNED NOT NULL,
    payload TEXT NOT NULL,
    issued_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    expires_at DATETIME(6),
    CONSTRAINT fk_qr_codes_part FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT,
    diff JSON,
    correlation_id VARCHAR(100),
    ip VARCHAR(45),
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    INDEX idx_audit_log_entity (entity_type, entity_id),
    INDEX idx_audit_log_created (created_at),
    INDEX idx_audit_log_user (user_id),
    CONSTRAINT fk_audit_log_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_parts_anchor ON parts(anchor_slug);

INSERT INTO users (username, email, auth_source, password_hash, role, is_active)
VALUES ('admin', 'admin@partops.local', 'local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', TRUE)
ON DUPLICATE KEY UPDATE id = id;
