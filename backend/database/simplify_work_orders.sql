-- Simplify Work Orders Table
-- Remove columns: technician_id, unit_id, unit_number, description, status
-- Keep only: id, wo_number, created_at, updated_at

USE partsAM;

-- Step 1: Drop foreign key constraints first
ALTER TABLE work_orders DROP FOREIGN KEY work_orders_ibfk_1;
ALTER TABLE work_orders DROP FOREIGN KEY work_orders_ibfk_2;

-- Step 2: Drop the columns
ALTER TABLE work_orders DROP COLUMN technician_id;
ALTER TABLE work_orders DROP COLUMN unit_id;
ALTER TABLE work_orders DROP COLUMN unit_number;
ALTER TABLE work_orders DROP COLUMN description;
ALTER TABLE work_orders DROP COLUMN status;
