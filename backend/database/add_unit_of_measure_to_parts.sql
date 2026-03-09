-- Add unit of measure tracking to parts
ALTER TABLE parts
ADD COLUMN IF NOT EXISTS unit_of_measure VARCHAR(30) NOT NULL DEFAULT 'each' AFTER supplier_part_number;

