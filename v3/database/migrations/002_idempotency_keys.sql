-- Idempotency Keys and auth/role alignment (MySQL 8)

CREATE TABLE IF NOT EXISTS idempotency_keys (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key_value VARCHAR(100) NOT NULL UNIQUE,
    user_id INT UNSIGNED NULL,
    created_at DATETIME(6) NOT NULL DEFAULT CURRENT_TIMESTAMP(6),
    expires_at DATETIME(6) NOT NULL DEFAULT (CURRENT_TIMESTAMP(6) + INTERVAL 1 HOUR),
    INDEX idx_idempotency_keys_expires (expires_at),
    INDEX idx_idempotency_keys_user (user_id),
    CONSTRAINT fk_idempotency_keys_user FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- LDAP users can only be 'user'; local accounts can only be 'admin'
ALTER TABLE users ADD CONSTRAINT chk_auth_role_alignment 
CHECK (
    (auth_source = 'ldap' AND role = 'user') OR 
    (auth_source = 'local' AND role = 'admin')
);
