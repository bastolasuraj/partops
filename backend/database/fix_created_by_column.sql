-- Fix created_by column to store username instead of user ID
USE partsam;

-- Change created_by column from INT to VARCHAR
ALTER TABLE inventory_transactions 
MODIFY COLUMN created_by VARCHAR(100) NULL;

-- Show the updated structure
DESCRIBE inventory_transactions;
