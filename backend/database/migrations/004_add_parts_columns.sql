-- Migration 004: Add missing columns to parts table
-- Replaces runtime ALTER TABLE calls in PartController::ensurePartsSchema()
-- Run this ONCE before deploying v2.

ALTER TABLE parts
    ADD COLUMN IF NOT EXISTS unit_of_measure VARCHAR(30)  NOT NULL DEFAULT 'each' AFTER supplier_part_number,
    ADD COLUMN IF NOT EXISTS location_alt    VARCHAR(255) NULL                    AFTER location_bay,
    ADD COLUMN IF NOT EXISTS deleted_at     TIMESTAMP    NULL DEFAULT NULL       AFTER unit_price;
