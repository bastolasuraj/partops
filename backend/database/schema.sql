-- Parts Asset Management Database Schema
-- Consolidated schema (includes all migrations)

CREATE DATABASE IF NOT EXISTS partsam;
USE partsam;

-- Suppliers Table
CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    contact VARCHAR(255),
    phone VARCHAR(50),
    email VARCHAR(255),
    url VARCHAR(500) NULL,
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Technicians Table
CREATE TABLE IF NOT EXISTS technicians (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    role VARCHAR(100),
    emp_id VARCHAR(50) UNIQUE,
    phone VARCHAR(50),
    email VARCHAR(255),
    active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Units Table (Vehicles/Equipment)
CREATE TABLE IF NOT EXISTS units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    make VARCHAR(100),
    model VARCHAR(100),
    year VARCHAR(10),
    vin VARCHAR(50),
    plate VARCHAR(20),
    status ENUM('active', 'inactive', 'maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parts Table
CREATE TABLE IF NOT EXISTS parts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fowler_part_number VARCHAR(100) NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    supplier_id INT,
    supplier_part_number VARCHAR(100),
    unit_of_measure VARCHAR(30) NOT NULL DEFAULT 'each',
    location_aisle VARCHAR(20),
    location_shelf VARCHAR(20),
    location_bay VARCHAR(20),
    location_alt VARCHAR(255),
    low_stock_threshold INT DEFAULT 5,
    has_core TINYINT(1) DEFAULT 0,
    core_cost DECIMAL(10,2) DEFAULT 0.00,
    core_rebate DECIMAL(10,2) DEFAULT 0.00,
    unit_price DECIMAL(10,2) DEFAULT 0.00,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    UNIQUE KEY unique_supplier_part_number (supplier_part_number),
    KEY idx_fowler_part_number (fowler_part_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inventory Levels Table
CREATE TABLE IF NOT EXISTS inventory_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    part_id INT NOT NULL,
    quantity INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_part (part_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inventory Location Levels Table (per-part, per-location stock buckets)
CREATE TABLE IF NOT EXISTS inventory_location_levels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    part_id INT NOT NULL,
    location_key VARCHAR(255) NOT NULL,
    location_aisle VARCHAR(20),
    location_shelf VARCHAR(20),
    location_bay VARCHAR(20),
    location_alt VARCHAR(255),
    quantity INT NOT NULL DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_part_location (part_id, location_key),
    KEY idx_part_id (part_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Work Orders Table
CREATE TABLE IF NOT EXISTS work_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    wo_number VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inventory Transactions Table (for tracking all movements)
CREATE TABLE IF NOT EXISTS inventory_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    part_id INT NOT NULL,
    transaction_type ENUM('incoming', 'outgoing', 'return', 'adjustment') NOT NULL,
    quantity INT NOT NULL,
    reference_type ENUM('work_order', 'unit', 'technician', 'supplier', 'vendor', 'adjustment') NULL,
    reference_id INT NULL,
    unit_price DECIMAL(10,2) DEFAULT 0.00,
    has_core TINYINT(1) DEFAULT 0,
    core_cost DECIMAL(10,2) DEFAULT 0.00,
    core_rebate_expected DECIMAL(10,2) DEFAULT 0.00,
    core_rebate_received DECIMAL(10,2) DEFAULT 0.00,
    location_key VARCHAR(255) NULL,
    location_aisle VARCHAR(20) NULL,
    location_shelf VARCHAR(20) NULL,
    location_bay VARCHAR(20) NULL,
    location_alt VARCHAR(255) NULL,
    notes TEXT,
    created_by VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vendor Returns Table
CREATE TABLE IF NOT EXISTS vendor_returns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT NOT NULL,
    part_id INT NOT NULL,
    quantity INT NOT NULL,
    rma_number VARCHAR(100),
    core_cost DECIMAL(10,2) DEFAULT 0.00,
    core_rebate DECIMAL(10,2) DEFAULT 0.00,
    status ENUM('pending', 'shipped', 'received', 'credited') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE CASCADE,
    FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- App Settings Table
CREATE TABLE IF NOT EXISTS app_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO app_settings (setting_key, setting_value) VALUES
('allow_untracked_returns', 'false'),
('audit_logs_page_size', '25'),
('audit_logs_retention_limit', '20');

-- Users Table (fallback authentication)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NULL,
    display_name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    role ENUM('admin', 'user') DEFAULT 'user',
    auth_source VARCHAR(20) NOT NULL DEFAULT 'local',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit Logs Table
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category ENUM('user', 'action') NOT NULL,
    action VARCHAR(100) NOT NULL,
    description VARCHAR(255) NOT NULL,
    outcome ENUM('success', 'failure') NOT NULL DEFAULT 'success',
    user_id INT NULL,
    username VARCHAR(100) NULL,
    display_name VARCHAR(255) NULL,
    user_role VARCHAR(50) NULL,
    auth_type VARCHAR(30) NULL,
    http_method VARCHAR(10) NULL,
    route_path VARCHAR(255) NULL,
    ip_address VARCHAR(64) NULL,
    user_agent VARCHAR(500) NULL,
    origin VARCHAR(255) NULL,
    resource_type VARCHAR(100) NULL,
    resource_id VARCHAR(100) NULL,
    request_payload LONGTEXT NULL,
    metadata LONGTEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category_created (category, created_at),
    INDEX idx_username_created (username, created_at),
    INDEX idx_route_created (route_path, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default admin user (change in production)
-- Password: PartsAdmin123!
INSERT INTO users (username, password_hash, display_name, email, role, auth_source, is_active) 
VALUES (
    'partsadmin',
    '$2y$10$ycwQt6enQmbELEScBtptWOC/Yw9fo0os8Sakof652xw1sdIohg1zG',
    'Parts Administrator',
    'admin@pam.local',
    'admin',
    'local',
    TRUE
) ON DUPLICATE KEY UPDATE 
    password_hash = VALUES(password_hash),
    display_name = VALUES(display_name),
    role = VALUES(role),
    auth_source = VALUES(auth_source);

-- Fowler Supplier Mapping Table
CREATE TABLE IF NOT EXISTS fowler_supplier_mapping (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fowler_part_number VARCHAR(100) NOT NULL,
    part_id INT NOT NULL,
    supplier_id INT,
    supplier_part_number VARCHAR(100) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0 COMMENT 'Indicates if this is the primary/preferred supplier for this Fowler PN',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    INDEX idx_fowler_pn (fowler_part_number),
    INDEX idx_supplier_pn (supplier_part_number),
    INDEX idx_part_id (part_id),
    UNIQUE KEY unique_supplier_part (supplier_part_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: Populate the mapping table with existing data from parts
INSERT IGNORE INTO fowler_supplier_mapping (fowler_part_number, part_id, supplier_id, supplier_part_number, is_primary)
SELECT 
    fowler_part_number,
    id as part_id,
    supplier_id,
    supplier_part_number,
    1 as is_primary
FROM parts
WHERE supplier_part_number IS NOT NULL AND supplier_part_number != '';

-- Optional sample data (comment out for production)
-- INSERT INTO suppliers (name, contact, phone, email) VALUES
-- ('ACME Supplies', 'John Doe', '555-1010', 'sales@acme.com'),
-- ('Bolt & Co', 'Jane Smith', '555-2020', 'hello@boltco.com'),
-- ('Parts Plus', 'Mike Johnson', '555-3030', 'orders@partsplus.com');
-- 
-- INSERT INTO technicians (name, role, emp_id, phone) VALUES
-- ('Sam Tech', 'Senior Mech', 'T-101', '555-3030'),
-- ('Jamie Fixer', 'Junior Mech', 'T-102', '555-4040'),
-- ('Alex Wrench', 'Lead Tech', 'T-103', '555-5050');
-- 
-- INSERT INTO units (name, make, model, year, vin, plate) VALUES
-- ('UNIT-12', 'Ford', 'F-150', '2020', '1FTEW1EP5LFA12345', 'TRK-01'),
-- ('UNIT-07', 'Caterpillar', '259D3', '2018', 'CAT0259D3JKL00001', 'N/A'),
-- ('UNIT-15', 'Chevrolet', 'Silverado', '2021', '3GCUYDED5MG123456', 'TRK-02');
-- 
-- INSERT INTO parts (fowler_part_number, name, supplier_id, supplier_part_number, location_aisle, location_shelf, location_bay, low_stock_threshold, has_core, core_cost, core_rebate, unit_price) VALUES
-- ('FW-1001', 'Hydraulic Filter', 1, 'AC-FF-77', 'A1', 'S2', 'B3', 3, 0, 0.00, 0.00, 25.99),
-- ('FW-2009', 'Alternator (Reman)', 2, 'BC-SP-11', 'A2', 'S1', 'B1', 10, 1, 50.00, 40.00, 189.99),
-- ('FW-3015', 'Oil Filter', 1, 'AC-OF-22', 'A1', 'S1', 'B1', 5, 0, 0.00, 0.00, 12.50),
-- ('FW-4020', 'Brake Pads Set', 3, 'PP-BP-100', 'B1', 'S3', 'B2', 4, 0, 0.00, 0.00, 45.00);
-- 
-- INSERT INTO inventory_levels (part_id, quantity) VALUES
-- (1, 10),
-- (2, 5),
-- (3, 25),
-- (4, 8);
-- 
-- INSERT INTO work_orders (wo_number) VALUES
-- ('WO-5001'),
-- ('WO-5002'),
-- ('WO-5003');
