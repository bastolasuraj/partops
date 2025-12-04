-- Track free-form work order references and unit numbers on inventory moves
ALTER TABLE inventory_moves
    ADD COLUMN work_order_ref VARCHAR(100) NULL AFTER work_order_id,
    ADD COLUMN unit_number VARCHAR(100) NULL AFTER work_order_ref;
