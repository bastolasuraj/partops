-- Add core charge tracking to inventory transactions
-- This allows tracking actual core costs/rebates per transaction since they vary over time

USE partsAM;

-- Add core tracking columns to inventory_transactions
ALTER TABLE inventory_transactions 
ADD COLUMN has_core TINYINT(1) DEFAULT 0 AFTER unit_price,
ADD COLUMN core_cost DECIMAL(10,2) DEFAULT 0.00 AFTER has_core,
ADD COLUMN core_rebate_expected DECIMAL(10,2) DEFAULT 0.00 AFTER core_cost,
ADD COLUMN core_rebate_received DECIMAL(10,2) DEFAULT 0.00 AFTER core_rebate_expected;
