-- PartOps v2 Database Schema
-- This script drops all existing tables and creates a fresh schema
-- Run with: mysql -u username -p database_name < database_schema.sql

-- ============================================
-- DROP ALL EXISTING TABLES (in correct order)
-- ============================================
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS qr_codes;
DROP TABLE IF EXISTS core_liabilities;
DROP TABLE IF EXISTS inventory_moves;
DROP TABLE IF EXISTS inventory_levels;
DROP TABLE IF EXISTS work_order_technicians;
DROP TABLE IF EXISTS work_orders;
DROP TABLE IF EXISTS technicians;
DROP TABLE IF EXISTS part_suppliers;
DROP TABLE IF EXISTS part_numbers;
DROP TABLE IF EXISTS parts;
DROP TABLE IF EXISTS suppliers;
DROP TABLE IF EXISTS locations;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS sessions;
DROP TABLE IF EXISTS idempotency_keys;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- USERS & AUTHENTICATION
-- ============================================

-- Users table (LDAP users = 'user' role, local accounts = 'admin')
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NOT NULL,
    auth_source ENUM('ldap', 'local') NOT NULL DEFAULT 'local',
    password_hash VARCHAR(255) NULL COMMENT 'NULL for LDAP users',
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login_at TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_auth_source (auth_source)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions table for session management
CREATE TABLE sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    payload TEXT NOT NULL,
    last_activity INT UNSIGNED NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Idempotency keys for preventing duplicate requests
CREATE TABLE idempotency_keys (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key_value VARCHAR(100) NOT NULL UNIQUE,
    user_id INT UNSIGNED NULL,
    endpoint VARCHAR(255) NOT NULL,
    response_code INT NULL,
    response_body TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_key_value (key_value),
    INDEX idx_expires_at (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- LOCATIONS (Aisle -> Shelf -> Bay -> Bin)
-- ============================================

CREATE TABLE locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    aisle VARCHAR(50) NOT NULL,
    shelf VARCHAR(50) NOT NULL,
    bay VARCHAR(50) NOT NULL,
    bin VARCHAR(50) NULL COMMENT 'Optional sub-location within bay',
    description VARCHAR(255) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_location (aisle, shelf, bay, bin),
    INDEX idx_aisle (aisle),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SUPPLIERS
-- ============================================

CREATE TABLE suppliers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) NULL UNIQUE COMMENT 'Short code for quick reference',
    contact_name VARCHAR(255) NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    address TEXT NULL,
    website VARCHAR(255) NULL,
    reorder_url TEXT NULL COMMENT 'Direct URL for reordering',
    payment_terms VARCHAR(100) NULL,
    notes TEXT NULL,
    is_preferred BOOLEAN NOT NULL DEFAULT FALSE,
    is_oem BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'TRUE if OEM supplier',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_preferred (is_preferred),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- PARTS
-- ============================================

-- Main parts table with anchor slug for grouping related part numbers
CREATE TABLE parts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    anchor_slug VARCHAR(100) NOT NULL UNIQUE COMMENT 'Unique identifier to group OEM/aftermarket/historical numbers',
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category VARCHAR(100) NULL,
    notes TEXT NULL COMMENT 'Stock/handling notes',
    min_stock_level INT UNSIGNED DEFAULT 0 COMMENT 'Alert when below this level',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_anchor_slug (anchor_slug),
    INDEX idx_category (category),
    INDEX idx_active (is_active),
    FULLTEXT idx_search (name, description, anchor_slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Part numbers (multiple numbers per part: active, historical, aftermarket)
CREATE TABLE part_numbers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    part_id INT UNSIGNED NOT NULL,
    number VARCHAR(100) NOT NULL,
    type ENUM('active', 'historical', 'aftermarket') NOT NULL DEFAULT 'active',
    manufacturer VARCHAR(255) NULL,
    is_primary BOOLEAN NOT NULL DEFAULT FALSE COMMENT 'Primary display number',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE,
    INDEX idx_part_id (part_id),
    INDEX idx_number (number),
    INDEX idx_type (type),
    INDEX idx_manufacturer (manufacturer),
    FULLTEXT idx_search (number, manufacturer)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Part-Supplier relationship with pricing
CREATE TABLE part_suppliers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    part_id INT UNSIGNED NOT NULL,
    supplier_id INT UNSIGNED NOT NULL,
    supplier_sku VARCHAR(100) NULL COMMENT 'Supplier-specific part number',
    price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    currency CHAR(3) NOT NULL DEFAULT 'USD',
    core_charge DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Core deposit required',
    expected_rebate DECIMAL(10, 2) NOT NULL DEFAULT 0.00 COMMENT 'Expected rebate on core return',
    lead_time_days INT UNSIGNED NULL,
    min_order_qty INT UNSIGNED DEFAULT 1,
    is_preferred BOOLEAN NOT NULL DEFAULT FALSE,
    last_price_update TIMESTAMP NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
    UNIQUE KEY unique_part_supplier (part_id, supplier_id),
    INDEX idx_supplier_sku (supplier_sku)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INVENTORY
-- ============================================

-- Current inventory levels per part per location
CREATE TABLE inventory_levels (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    part_id INT UNSIGNED NOT NULL,
    location_id INT UNSIGNED NOT NULL,
    on_hand INT NOT NULL DEFAULT 0 COMMENT 'Current quantity in stock',
    reserved INT NOT NULL DEFAULT 0 COMMENT 'Quantity reserved for work orders',
    available INT GENERATED ALWAYS AS (on_hand - reserved) STORED,
    last_count_date DATE NULL COMMENT 'Last physical inventory count',
    last_count_qty INT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_part_location (part_id, location_id),
    INDEX idx_on_hand (on_hand),
    INDEX idx_available (available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- TECHNICIANS & WORK ORDERS
-- ============================================

CREATE TABLE technicians (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(50) NULL UNIQUE COMMENT 'Employee/badge number',
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    department VARCHAR(100) NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_employee_id (employee_id),
    INDEX idx_name (name),
    INDEX idx_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE work_orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wo_number VARCHAR(100) NOT NULL UNIQUE COMMENT 'Work order reference number',
    unit_number VARCHAR(100) NULL COMMENT 'Vehicle/equipment unit number',
    description TEXT NULL,
    status ENUM('open', 'in_progress', 'on_hold', 'completed', 'cancelled') NOT NULL DEFAULT 'open',
    priority ENUM('low', 'normal', 'high', 'urgent') NOT NULL DEFAULT 'normal',
    technician_id INT UNSIGNED NULL COMMENT 'Primary assigned technician',
    opened_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    due_date DATE NULL,
    completed_at TIMESTAMP NULL,
    closed_at TIMESTAMP NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (technician_id) REFERENCES technicians(id) ON DELETE SET NULL,
    INDEX idx_wo_number (wo_number),
    INDEX idx_unit_number (unit_number),
    INDEX idx_status (status),
    INDEX idx_technician (technician_id),
    INDEX idx_opened_at (opened_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Additional technicians can be assigned to work orders
CREATE TABLE work_order_technicians (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    work_order_id INT UNSIGNED NOT NULL,
    technician_id INT UNSIGNED NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (technician_id) REFERENCES technicians(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wo_tech (work_order_id, technician_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- INVENTORY MOVEMENTS (Audit Trail)
-- ============================================

CREATE TABLE inventory_moves (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    part_id INT UNSIGNED NOT NULL,
    part_number_id INT UNSIGNED NULL COMMENT 'Specific part number used',
    location_id INT UNSIGNED NOT NULL,
    qty INT NOT NULL COMMENT 'Positive for in, negative for out',
    direction ENUM('in', 'out') NOT NULL,
    reason ENUM(
        'receive_new',           -- New parts from supplier
        'receive_wo_return',     -- Unused parts returned from work order
        'checkout',              -- Parts checked out to work order
        'return_standard',       -- Standard return (unused parts back to stock)
        'return_core',           -- Core return (used parts for rebate)
        'adjust_add',            -- Manual adjustment - add
        'adjust_remove',         -- Manual adjustment - remove
        'transfer_in',           -- Transfer from another location
        'transfer_out',          -- Transfer to another location
        'count_adjust'           -- Physical count adjustment
    ) NOT NULL,
    
    -- Work order context
    work_order_id INT UNSIGNED NULL,
    technician_id INT UNSIGNED NULL,
    
    -- Supplier context (for receiving)
    supplier_id INT UNSIGNED NULL,
    supplier_sku VARCHAR(100) NULL,
    po_number VARCHAR(100) NULL COMMENT 'Purchase order reference',
    
    -- Pricing snapshot at time of transaction
    price_at_tx DECIMAL(10, 2) NULL,
    currency CHAR(3) NULL DEFAULT 'USD',
    core_charge_at_tx DECIMAL(10, 2) NULL,
    
    -- Core return tracking
    core_rebate_expected DECIMAL(10, 2) NULL,
    core_rebate_received DECIMAL(10, 2) NULL,
    core_due_state ENUM('none', 'due', 'sent', 'rebated') NOT NULL DEFAULT 'none',
    
    -- Idempotency and audit
    idempotency_key VARCHAR(100) NULL UNIQUE,
    user_id INT UNSIGNED NULL COMMENT 'User who performed the action',
    notes TEXT NULL,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE,
    FOREIGN KEY (part_number_id) REFERENCES part_numbers(id) ON DELETE SET NULL,
    FOREIGN KEY (location_id) REFERENCES locations(id) ON DELETE CASCADE,
    FOREIGN KEY (work_order_id) REFERENCES work_orders(id) ON DELETE SET NULL,
    FOREIGN KEY (technician_id) REFERENCES technicians(id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    
    INDEX idx_part_id (part_id),
    INDEX idx_location_id (location_id),
    INDEX idx_work_order_id (work_order_id),
    INDEX idx_reason (reason),
    INDEX idx_direction (direction),
    INDEX idx_created_at (created_at),
    INDEX idx_core_due_state (core_due_state)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- CORE LIABILITIES
-- ============================================

CREATE TABLE core_liabilities (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    receive_move_id INT UNSIGNED NOT NULL COMMENT 'Original receiving transaction',
    part_id INT UNSIGNED NOT NULL,
    part_number_id INT UNSIGNED NULL,
    supplier_id INT UNSIGNED NOT NULL,
    qty_due INT UNSIGNED NOT NULL COMMENT 'Number of cores to return',
    qty_returned INT UNSIGNED NOT NULL DEFAULT 0,
    core_charge DECIMAL(10, 2) NOT NULL,
    expected_rebate DECIMAL(10, 2) NOT NULL,
    actual_rebate DECIMAL(10, 2) NULL,
    due_date DATE NULL COMMENT 'Deadline for core return',
    status ENUM('pending', 'partial', 'sent', 'rebated', 'expired', 'waived') NOT NULL DEFAULT 'pending',
    sent_at TIMESTAMP NULL,
    rebate_received_at TIMESTAMP NULL,
    rma_number VARCHAR(100) NULL COMMENT 'Return merchandise authorization',
    tracking_number VARCHAR(100) NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (receive_move_id) REFERENCES inventory_moves(id) ON DELETE CASCADE,
    FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE,
    FOREIGN KEY (part_number_id) REFERENCES part_numbers(id) ON DELETE SET NULL,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_due_date (due_date),
    INDEX idx_supplier (supplier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- QR CODES
-- ============================================

CREATE TABLE qr_codes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    part_id INT UNSIGNED NOT NULL,
    payload VARCHAR(500) NOT NULL COMMENT 'Signed token/URL',
    signature VARCHAR(100) NOT NULL COMMENT 'HMAC signature for validation',
    issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NULL,
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE,
    INDEX idx_part_id (part_id),
    INDEX idx_signature (signature)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- AUDIT LOG
-- ============================================

CREATE TABLE audit_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL COMMENT 'Action performed',
    entity_type VARCHAR(100) NOT NULL COMMENT 'Table/entity affected',
    entity_id INT UNSIGNED NULL,
    old_values JSON NULL COMMENT 'Previous values',
    new_values JSON NULL COMMENT 'New values',
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    correlation_id VARCHAR(100) NULL COMMENT 'Request correlation ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    INDEX idx_correlation_id (correlation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- DEFAULT ADMIN USER
-- ============================================

INSERT INTO users (username, email, auth_source, password_hash, role, is_active)
VALUES ('admin', 'admin@partops.local', 'local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', TRUE);
-- Default password: password

-- ============================================
-- VIEWS FOR COMMON QUERIES
-- ============================================

-- View: Parts with current stock levels
CREATE OR REPLACE VIEW v_parts_stock AS
SELECT 
    p.id,
    p.anchor_slug,
    p.name,
    p.description,
    p.category,
    p.min_stock_level,
    p.is_active,
    pn.number AS primary_part_number,
    pn.manufacturer,
    COALESCE(SUM(il.on_hand), 0) AS total_on_hand,
    COALESCE(SUM(il.reserved), 0) AS total_reserved,
    COALESCE(SUM(il.available), 0) AS total_available
FROM parts p
LEFT JOIN part_numbers pn ON pn.part_id = p.id AND pn.is_primary = TRUE
LEFT JOIN inventory_levels il ON il.part_id = p.id
GROUP BY p.id, pn.id;

-- View: Open core liabilities
CREATE OR REPLACE VIEW v_open_core_liabilities AS
SELECT 
    cl.*,
    p.anchor_slug,
    p.name AS part_name,
    pn.number AS part_number,
    s.name AS supplier_name,
    DATEDIFF(cl.due_date, CURDATE()) AS days_until_due
FROM core_liabilities cl
JOIN parts p ON p.id = cl.part_id
LEFT JOIN part_numbers pn ON pn.id = cl.part_number_id
JOIN suppliers s ON s.id = cl.supplier_id
WHERE cl.status IN ('pending', 'partial')
ORDER BY cl.due_date ASC;

-- View: Work order parts usage
CREATE OR REPLACE VIEW v_work_order_parts AS
SELECT 
    wo.id AS work_order_id,
    wo.wo_number,
    wo.unit_number,
    wo.status AS wo_status,
    t.name AS technician_name,
    p.anchor_slug,
    p.name AS part_name,
    pn.number AS part_number,
    im.qty,
    im.reason,
    im.created_at AS transaction_date
FROM work_orders wo
LEFT JOIN technicians t ON t.id = wo.technician_id
JOIN inventory_moves im ON im.work_order_id = wo.id
JOIN parts p ON p.id = im.part_id
LEFT JOIN part_numbers pn ON pn.id = im.part_number_id
ORDER BY wo.wo_number, im.created_at;
