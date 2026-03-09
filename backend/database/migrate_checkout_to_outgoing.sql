-- Migration: Change 'checkout' to 'outgoing' in inventory_transactions
-- This script updates existing data and modifies the ENUM type

USE partsAM;

-- Step 1: Update existing 'checkout' records to 'outgoing'
UPDATE inventory_transactions 
SET transaction_type = 'outgoing' 
WHERE transaction_type = 'checkout';

-- Step 2: Modify the ENUM to replace 'checkout' with 'outgoing'
ALTER TABLE inventory_transactions 
MODIFY COLUMN transaction_type ENUM('incoming', 'outgoing', 'return', 'adjustment') NOT NULL;

-- Verify the changes
SELECT transaction_type, COUNT(*) as count 
FROM inventory_transactions 
GROUP BY transaction_type;
