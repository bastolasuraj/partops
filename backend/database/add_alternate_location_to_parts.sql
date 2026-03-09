-- Add alternate/freeform location for parts
ALTER TABLE parts
ADD COLUMN location_alt VARCHAR(255) NULL AFTER location_bay;
