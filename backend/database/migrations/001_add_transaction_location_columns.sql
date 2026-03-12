-- Migration 001: Add location columns to inventory_transactions
-- Replaces runtime schema mutation in InventoryTransaction::ensureSchema()
-- Run this ONCE before deploying v2.

ALTER TABLE inventory_transactions
    ADD COLUMN IF NOT EXISTS location_key  VARCHAR(255) NULL AFTER core_rebate_received,
    ADD COLUMN IF NOT EXISTS location_aisle VARCHAR(20)  NULL AFTER location_key,
    ADD COLUMN IF NOT EXISTS location_shelf VARCHAR(20)  NULL AFTER location_aisle,
    ADD COLUMN IF NOT EXISTS location_bay  VARCHAR(20)  NULL AFTER location_shelf,
    ADD COLUMN IF NOT EXISTS location_alt  VARCHAR(255) NULL AFTER location_bay;
