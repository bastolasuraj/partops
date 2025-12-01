-- Sample seed data for development

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, auth_source, password_hash, role) VALUES
('admin', 'admin@partops.local', 'local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Insert sample technicians
INSERT INTO technicians (name, email, phone) VALUES
('John Smith', 'john.smith@example.com', '555-0101'),
('Jane Doe', 'jane.doe@example.com', '555-0102'),
('Mike Johnson', 'mike.johnson@example.com', '555-0103');

-- Insert sample suppliers
INSERT INTO suppliers (name, contact_name, email, phone, is_preferred) VALUES
('AutoZone', 'Sales Dept', 'sales@autozone.com', '800-288-6966', TRUE),
('NAPA Auto Parts', 'Parts Counter', 'parts@napaonline.com', '877-627-2872', TRUE),
('O''Reilly Auto Parts', 'Customer Service', 'service@oreillyauto.com', '800-755-6759', FALSE);

-- Insert sample locations
INSERT INTO locations (aisle, shelf, bay, bin) VALUES
('A', '1', '1', NULL),
('A', '1', '2', NULL),
('A', '2', '1', NULL),
('B', '1', '1', 'A'),
('B', '1', '1', 'B');

-- Insert sample work orders
INSERT INTO work_orders (external_ref, vehicle_ref, status) VALUES
('WO-2025-001', '2018 Honda Civic - ABC123', 'open'),
('WO-2025-002', '2020 Ford F-150 - XYZ789', 'in_progress'),
('WO-2025-003', '2019 Toyota Camry - DEF456', 'completed');
