-- Migration 002: Create inventory_location_levels table
-- Replaces runtime CREATE TABLE IF NOT EXISTS in InventoryLocationLevel::ensureSchema()
-- Run this ONCE before deploying v2.

CREATE TABLE IF NOT EXISTS inventory_location_levels (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    part_id       INT          NOT NULL,
    location_key  VARCHAR(255) NOT NULL,
    location_aisle VARCHAR(20) NULL,
    location_shelf VARCHAR(20) NULL,
    location_bay  VARCHAR(20)  NULL,
    location_alt  VARCHAR(255) NULL,
    quantity      INT          NOT NULL DEFAULT 0,
    last_updated  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE,
    UNIQUE KEY unique_part_location (part_id, location_key),
    KEY idx_part_id (part_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
