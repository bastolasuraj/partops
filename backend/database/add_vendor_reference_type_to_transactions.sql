-- Allow vendor reference type in inventory transactions
ALTER TABLE inventory_transactions
  MODIFY COLUMN reference_type ENUM('work_order', 'unit', 'technician', 'supplier', 'vendor', 'adjustment') NULL;
