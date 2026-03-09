-- Add URL column to suppliers table
USE partsam;

ALTER TABLE suppliers ADD COLUMN url VARCHAR(500) NULL AFTER email;
