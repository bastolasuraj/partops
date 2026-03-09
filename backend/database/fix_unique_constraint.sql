-- Fix unique constraint: Make supplier_part_number unique instead of fowler_part_number
-- This allows multiple supplier parts to be grouped under the same Fowler PN

USE partsAM;

-- Step 1: Remove the UNIQUE constraint from fowler_part_number
ALTER TABLE parts DROP INDEX fowler_part_number;

-- Step 2: Add UNIQUE constraint to supplier_part_number
-- First, we need to ensure there are no duplicate supplier_part_numbers
-- If there are duplicates, this will fail and you'll need to clean them up first
ALTER TABLE parts ADD UNIQUE KEY unique_supplier_part_number (supplier_part_number);

-- Step 3: Add an index on fowler_part_number for faster lookups (but not unique)
ALTER TABLE parts ADD INDEX idx_fowler_part_number (fowler_part_number);
