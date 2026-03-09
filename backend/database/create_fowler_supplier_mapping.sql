-- Create a mapping table for Fowler PN to Supplier PN relationships
-- This table tracks which supplier parts are grouped under which Fowler PN

CREATE TABLE IF NOT EXISTS fowler_supplier_mapping (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fowler_part_number VARCHAR(100) NOT NULL,
    part_id INT NOT NULL,
    supplier_id INT,
    supplier_part_number VARCHAR(100) NOT NULL,
    is_primary TINYINT(1) DEFAULT 0 COMMENT 'Indicates if this is the primary/preferred supplier for this Fowler PN',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign keys
    FOREIGN KEY (part_id) REFERENCES parts(id) ON DELETE CASCADE,
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    
    -- Indexes for performance
    INDEX idx_fowler_pn (fowler_part_number),
    INDEX idx_supplier_pn (supplier_part_number),
    INDEX idx_part_id (part_id),
    
    -- Ensure supplier part number is unique (one supplier part can only map to one Fowler PN)
    UNIQUE KEY unique_supplier_part (supplier_part_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Populate the mapping table with existing data from parts table
INSERT INTO fowler_supplier_mapping (fowler_part_number, part_id, supplier_id, supplier_part_number, is_primary)
SELECT 
    fowler_part_number,
    id as part_id,
    supplier_id,
    supplier_part_number,
    1 as is_primary
FROM parts
WHERE supplier_part_number IS NOT NULL AND supplier_part_number != '';
