-- Add per-location inventory buckets for each part
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
);

-- Add optional location fields to transaction history for auditability
ALTER TABLE inventory_transactions
    ADD COLUMN IF NOT EXISTS location_key VARCHAR(255) NULL AFTER core_rebate_received,
    ADD COLUMN IF NOT EXISTS location_aisle VARCHAR(20) NULL AFTER location_key,
    ADD COLUMN IF NOT EXISTS location_shelf VARCHAR(20) NULL AFTER location_aisle,
    ADD COLUMN IF NOT EXISTS location_bay VARCHAR(20) NULL AFTER location_shelf,
    ADD COLUMN IF NOT EXISTS location_alt VARCHAR(255) NULL AFTER location_bay;
