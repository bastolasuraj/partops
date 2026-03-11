-- Create users table for fallback authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NULL,
    display_name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    role ENUM('admin', 'user') DEFAULT 'user',
    auth_source VARCHAR(20) NOT NULL DEFAULT 'local',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    INDEX idx_username (username),
    INDEX idx_role (role),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default local admin user
-- Password: PartsAdmin123!
INSERT INTO users (username, password_hash, display_name, email, role, auth_source, is_active) 
VALUES (
    'partsadmin',
    '$2y$10$ycwQt6enQmbELEScBtptWOC/Yw9fo0os8Sakof652xw1sdIohg1zG',
    'Parts Administrator',
    'admin@pam.local',
    'admin',
    'local',
    TRUE
) ON DUPLICATE KEY UPDATE 
    password_hash = VALUES(password_hash),
    display_name = VALUES(display_name),
    role = VALUES(role),
    auth_source = VALUES(auth_source);

-- Note: The password hash above is for 'PartsAdmin123!'
-- In production, this should be changed immediately after first login
