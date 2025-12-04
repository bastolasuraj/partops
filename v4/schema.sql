-- ============================================================================
-- PartOps Database Schema
-- Based on: partops.md specification and project requirements
-- Version: 4.0
-- Description: Complete database schema for parts inventory management system
-- ============================================================================

-- Drop tables in reverse dependency order (if exists)
DROP TABLE IF EXISTS `returns`;
DROP TABLE IF EXISTS `work_order_returns`;
DROP TABLE IF EXISTS `part_checkouts`;
DROP TABLE IF EXISTS `part_checkins`;
DROP TABLE IF EXISTS `work_orders`;
DROP TABLE IF EXISTS `part_supplier_numbers`;
DROP TABLE IF EXISTS `parts`;
DROP TABLE IF EXISTS `technicians`;
DROP TABLE IF EXISTS `suppliers`;

-- ============================================================================
-- Table: suppliers
-- Purpose: Stores parts suppliers/manufacturers
-- ============================================================================
CREATE TABLE `suppliers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `address` TEXT DEFAULT NULL,
  `url` VARCHAR(500) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_suppliers_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table: technicians
-- Purpose: People checking parts in/out
-- ============================================================================
CREATE TABLE `technicians` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) NOT NULL,
  `email` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_technicians_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table: parts
-- Purpose: Master list of all parts
-- ============================================================================
CREATE TABLE `parts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fowler_part_number` VARCHAR(100) NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `supplier_id` INT UNSIGNED DEFAULT NULL,
  `supplier_part_number` VARCHAR(100) DEFAULT NULL,
  `location_aisle` VARCHAR(50) DEFAULT NULL,
  `location_shelf` VARCHAR(50) DEFAULT NULL,
  `location_bay` VARCHAR(50) DEFAULT NULL,
  `low_stock_threshold` INT UNSIGNED DEFAULT NULL,
  `url` VARCHAR(500) DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fowler_part_number` (`fowler_part_number`),
  INDEX `idx_parts_name` (`name`),
  INDEX `idx_parts_supplier` (`supplier_id`),
  INDEX `idx_parts_location` (`location_aisle`, `location_shelf`, `location_bay`),
  CONSTRAINT `fk_parts_supplier` 
    FOREIGN KEY (`supplier_id`) 
    REFERENCES `suppliers` (`id`) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table: part_supplier_numbers
-- Purpose: Map company parts to supplier/manufacturer part numbers
-- ============================================================================
CREATE TABLE `part_supplier_numbers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `part_id` INT UNSIGNED NOT NULL,
  `supplier_id` INT UNSIGNED NOT NULL,
  `supplier_part_number` VARCHAR(100) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uid_part_supplier_number` (`part_id`, `supplier_id`, `supplier_part_number`),
  INDEX `idx_psn_part` (`part_id`),
  INDEX `idx_psn_supplier` (`supplier_id`),
  CONSTRAINT `fk_psn_part` 
    FOREIGN KEY (`part_id`) 
    REFERENCES `parts` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE,
  CONSTRAINT `fk_psn_supplier` 
    FOREIGN KEY (`supplier_id`) 
    REFERENCES `suppliers` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table: work_orders
-- Purpose: Represents a work order a tech is working on
-- ============================================================================
CREATE TABLE `work_orders` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `wo_number` VARCHAR(100) NOT NULL,
  `technician_id` INT UNSIGNED DEFAULT NULL,
  `unit_number` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_wo_number` (`wo_number`),
  INDEX `idx_work_orders_technician` (`technician_id`),
  INDEX `idx_work_orders_unit` (`unit_number`),
  CONSTRAINT `fk_work_orders_technician` 
    FOREIGN KEY (`technician_id`) 
    REFERENCES `technicians` (`id`) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table: part_checkins
-- Purpose: Form 4a – New Parts Check-In
-- Records incoming stock (new parts)
-- ============================================================================
CREATE TABLE `part_checkins` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `part_id` INT UNSIGNED NOT NULL,
  `supplier_id` INT UNSIGNED DEFAULT NULL,
  `supplier_part_number` VARCHAR(100) DEFAULT NULL,
  `location_aisle` VARCHAR(50) DEFAULT NULL,
  `location_shelf` VARCHAR(50) DEFAULT NULL,
  `location_bay` VARCHAR(50) DEFAULT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `has_core_charge` BOOLEAN NOT NULL DEFAULT FALSE,
  `expected_rebate` DECIMAL(10,2) DEFAULT NULL,
  `quantity` INT UNSIGNED NOT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_part_checkins_part` (`part_id`),
  INDEX `idx_part_checkins_supplier` (`supplier_id`),
  INDEX `idx_part_checkins_created` (`created_at`),
  CONSTRAINT `fk_part_checkins_part` 
    FOREIGN KEY (`part_id`) 
    REFERENCES `parts` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE,
  CONSTRAINT `fk_part_checkins_supplier` 
    FOREIGN KEY (`supplier_id`) 
    REFERENCES `suppliers` (`id`) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table: part_checkouts
-- Purpose: Form 5 – Parts Checkout to Work Order / Technician
-- Issue parts to a technician / work order
-- ============================================================================
CREATE TABLE `part_checkouts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `work_order_id` INT UNSIGNED NOT NULL,
  `technician_id` INT UNSIGNED NOT NULL,
  `unit_number` VARCHAR(100) NOT NULL,
  `part_id` INT UNSIGNED NOT NULL,
  `supplier_part_number` VARCHAR(100) DEFAULT NULL,
  `quantity` INT UNSIGNED NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_part_checkouts_work_order` (`work_order_id`),
  INDEX `idx_part_checkouts_technician` (`technician_id`),
  INDEX `idx_part_checkouts_part` (`part_id`),
  INDEX `idx_part_checkouts_created` (`created_at`),
  CONSTRAINT `fk_part_checkouts_work_order` 
    FOREIGN KEY (`work_order_id`) 
    REFERENCES `work_orders` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE,
  CONSTRAINT `fk_part_checkouts_technician` 
    FOREIGN KEY (`technician_id`) 
    REFERENCES `technicians` (`id`) 
    ON DELETE RESTRICT 
    ON UPDATE CASCADE,
  CONSTRAINT `fk_part_checkouts_part` 
    FOREIGN KEY (`part_id`) 
    REFERENCES `parts` (`id`) 
    ON DELETE RESTRICT 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table: work_order_returns
-- Purpose: Form 4b – Work Order Return
-- Return unused parts from a work order back into stock
-- ============================================================================
CREATE TABLE `work_order_returns` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `work_order_id` INT UNSIGNED NOT NULL,
  `part_id` INT UNSIGNED NOT NULL,
  `return_quantity` INT UNSIGNED NOT NULL,
  `required_quantity` INT UNSIGNED DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_work_order_returns_work_order` (`work_order_id`),
  INDEX `idx_work_order_returns_part` (`part_id`),
  INDEX `idx_work_order_returns_created` (`created_at`),
  CONSTRAINT `fk_work_order_returns_work_order` 
    FOREIGN KEY (`work_order_id`) 
    REFERENCES `work_orders` (`id`) 
    ON DELETE CASCADE 
    ON UPDATE CASCADE,
  CONSTRAINT `fk_work_order_returns_part` 
    FOREIGN KEY (`part_id`) 
    REFERENCES `parts` (`id`) 
    ON DELETE RESTRICT 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Table: returns
-- Purpose: Form 6a + 6b – Supplier Returns + Core Charge Returns
-- Return parts to supplier (standard or core charge)
-- ============================================================================
CREATE TABLE `returns` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `supplier_id` INT UNSIGNED NOT NULL,
  `supplier_part_number` VARCHAR(100) NOT NULL,
  `part_id` INT UNSIGNED DEFAULT NULL,
  `quantity` INT UNSIGNED NOT NULL,
  `notes` TEXT DEFAULT NULL,
  
  -- Core charge-specific fields (Form 6b)
  `is_core_charge` BOOLEAN NOT NULL DEFAULT FALSE,
  `core_charge_amount` DECIMAL(10,2) DEFAULT NULL,
  `expected_rebate` DECIMAL(10,2) DEFAULT NULL,
  `rebate_received` DECIMAL(10,2) DEFAULT NULL,
  
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_returns_supplier` (`supplier_id`),
  INDEX `idx_returns_part` (`part_id`),
  INDEX `idx_returns_is_core` (`is_core_charge`),
  INDEX `idx_returns_created` (`created_at`),
  CONSTRAINT `fk_returns_supplier` 
    FOREIGN KEY (`supplier_id`) 
    REFERENCES `suppliers` (`id`) 
    ON DELETE RESTRICT 
    ON UPDATE CASCADE,
  CONSTRAINT `fk_returns_part` 
    FOREIGN KEY (`part_id`) 
    REFERENCES `parts` (`id`) 
    ON DELETE SET NULL 
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- Views for reporting and convenience
-- ============================================================================

-- View: Current inventory levels (calculated from checkins, checkouts, returns)
CREATE OR REPLACE VIEW `v_inventory_levels` AS
SELECT 
  p.id AS part_id,
  p.fowler_part_number,
  p.name AS part_name,
  p.location_aisle,
  p.location_shelf,
  p.location_bay,
  COALESCE(
    (SELECT SUM(quantity) FROM part_checkins WHERE part_id = p.id), 0
  ) + COALESCE(
    (SELECT SUM(return_quantity) FROM work_order_returns WHERE part_id = p.id), 0
  ) - COALESCE(
    (SELECT SUM(quantity) FROM part_checkouts WHERE part_id = p.id), 0
  ) - COALESCE(
    (SELECT SUM(quantity) FROM returns WHERE part_id = p.id AND is_core_charge = FALSE), 0
  ) AS on_hand_quantity,
  p.low_stock_threshold,
  CASE 
    WHEN p.low_stock_threshold IS NOT NULL 
      AND (
        COALESCE((SELECT SUM(quantity) FROM part_checkins WHERE part_id = p.id), 0) +
        COALESCE((SELECT SUM(return_quantity) FROM work_order_returns WHERE part_id = p.id), 0) -
        COALESCE((SELECT SUM(quantity) FROM part_checkouts WHERE part_id = p.id), 0) -
        COALESCE((SELECT SUM(quantity) FROM returns WHERE part_id = p.id AND is_core_charge = FALSE), 0)
      ) <= p.low_stock_threshold
    THEN TRUE
    ELSE FALSE
  END AS is_low_stock
FROM parts p;

-- View: Outstanding core charges
CREATE OR REPLACE VIEW `v_outstanding_cores` AS
SELECT 
  r.id,
  r.supplier_id,
  s.name AS supplier_name,
  r.supplier_part_number,
  r.part_id,
  p.fowler_part_number,
  p.name AS part_name,
  r.quantity,
  r.core_charge_amount,
  r.expected_rebate,
  r.rebate_received,
  (r.expected_rebate - COALESCE(r.rebate_received, 0)) AS rebate_outstanding,
  r.created_at
FROM returns r
JOIN suppliers s ON r.supplier_id = s.id
LEFT JOIN parts p ON r.part_id = p.id
WHERE r.is_core_charge = TRUE
  AND (r.rebate_received IS NULL OR r.rebate_received < r.expected_rebate);

-- View: Work order parts summary
CREATE OR REPLACE VIEW `v_work_order_parts` AS
SELECT 
  wo.id AS work_order_id,
  wo.wo_number,
  wo.unit_number,
  t.name AS technician_name,
  p.id AS part_id,
  p.fowler_part_number,
  p.name AS part_name,
  COALESCE(SUM(pc.quantity), 0) AS total_checked_out,
  COALESCE(SUM(wor.return_quantity), 0) AS total_returned,
  COALESCE(SUM(pc.quantity), 0) - COALESCE(SUM(wor.return_quantity), 0) AS net_quantity
FROM work_orders wo
LEFT JOIN technicians t ON wo.technician_id = t.id
LEFT JOIN part_checkouts pc ON wo.id = pc.work_order_id
LEFT JOIN parts p ON pc.part_id = p.id
LEFT JOIN work_order_returns wor ON wo.id = wor.work_order_id AND p.id = wor.part_id
GROUP BY wo.id, wo.wo_number, wo.unit_number, t.name, p.id, p.fowler_part_number, p.name;

-- ============================================================================
-- Sample Comments and Documentation
-- ============================================================================

-- Table relationships:
-- 1. parts.supplier_id → suppliers.id (optional default supplier)
-- 2. work_orders.technician_id → technicians.id (assigned tech)
-- 3. part_checkins.part_id → parts.id (which part was received)
-- 4. part_checkins.supplier_id → suppliers.id (who supplied it)
-- 5. part_checkouts.work_order_id → work_orders.id (which WO)
-- 6. part_checkouts.technician_id → technicians.id (who took it)
-- 7. part_checkouts.part_id → parts.id (which part)
-- 8. work_order_returns.work_order_id → work_orders.id (returning to which WO)
-- 9. work_order_returns.part_id → parts.id (which part returned)
-- 10. returns.supplier_id → suppliers.id (returning to which supplier)
-- 11. returns.part_id → parts.id (optional link to Fowler part)

-- Validation rules to implement in application layer:
-- 1. work_order_returns.return_quantity must be <= total checked out for that WO+part
-- 2. Core charge fields (core_charge_amount, expected_rebate) should be required when is_core_charge = TRUE
-- 3. Inventory levels should not go negative (enforce in application)
-- 4. Fowler part numbers must be unique across the system
-- 5. Work order numbers must be unique

-- Indexes are created for:
-- - Foreign key relationships (automatic query optimization)
-- - Common search fields (name, wo_number, fowler_part_number)
-- - Date fields for reporting (created_at)
-- - Location fields for warehouse operations

-- ============================================================================
-- End of Schema
-- ============================================================================
