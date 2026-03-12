-- Migration 003: Harden users table for LDAP support
-- Replaces runtime ALTER TABLE calls in User::ensureSchema()
-- Run this ONCE before deploying v2.

-- Allow password_hash to be NULL (LDAP users have no local password)
ALTER TABLE users
    MODIFY COLUMN password_hash VARCHAR(255) NULL;

-- Add auth_source column if it does not exist
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS auth_source VARCHAR(20) NOT NULL DEFAULT 'local' AFTER role;

-- Backfill auth_source for existing rows
UPDATE users
SET auth_source = 'local'
WHERE auth_source IS NULL OR TRIM(auth_source) = '';

-- Clear password hashes for any LDAP-sourced users
UPDATE users
SET password_hash = NULL
WHERE auth_source = 'ldap';
