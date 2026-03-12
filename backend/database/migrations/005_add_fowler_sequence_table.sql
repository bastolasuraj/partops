-- Migration 005: Create Fowler part number sequence table
-- Replaces race-prone MAX()+1 pattern in PartController::getNextFowlerSequence()
-- Run this ONCE before deploying v2.

CREATE TABLE IF NOT EXISTS fowler_part_sequences (
    id         INT          NOT NULL DEFAULT 1,
    seq_value INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed the sequence with the current maximum from existing parts
INSERT INTO fowler_part_sequences (id, seq_value)
SELECT 1, COALESCE(MAX(CAST(SUBSTRING(fowler_part_number, 5) AS UNSIGNED)), 0)
FROM parts
WHERE fowler_part_number REGEXP '^FC-P[0-9]{6}$'
ON DUPLICATE KEY UPDATE seq_value = VALUES(seq_value);
