-- Database schema for DAECHANG Shipping Control System
CREATE DATABASE IF NOT EXISTS daechang_shipping CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE daechang_shipping;

-- Users table for authentication and role management
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    role ENUM('admin', 'supervisor', 'operator', 'security') NOT NULL DEFAULT 'operator',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    last_login DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Orders table for delivery orders
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    customer VARCHAR(100) NOT NULL DEFAULT 'Samsung',
    description TEXT,
    priority ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
    status ENUM('created', 'in_progress', 'loading', 'loaded', 'in_transit', 'delivered', 'cancelled') NOT NULL DEFAULT 'created',
    estimated_delivery DATE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Products catalog
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(50),
    weight_kg DECIMAL(10,2),
    dimensions VARCHAR(50), -- e.g., "120x80x60 cm"
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Order items - products included in each order
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    weight_total DECIMAL(10,2),
    notes TEXT,
    scanned BOOLEAN DEFAULT FALSE,
    scanned_at DATETIME NULL,
    scanned_by INT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (scanned_by) REFERENCES users(id)
);

-- Deliveries table for tracking shipments
CREATE TABLE deliveries (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    truck_plate VARCHAR(20) NOT NULL,
    driver_name VARCHAR(100) NOT NULL,
    driver_license VARCHAR(50),
    driver_phone VARCHAR(20),
    companion_name VARCHAR(100),
    entry_time DATETIME,
    exit_time DATETIME NULL,
    status ENUM('waiting', 'loading', 'loaded', 'departed', 'delivered') NOT NULL DEFAULT 'waiting',
    security_notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Delivery evidence - signatures and photos
CREATE TABLE delivery_evidence (
    id INT AUTO_INCREMENT PRIMARY KEY,
    delivery_id INT NOT NULL,
    signature_path VARCHAR(255),
    recipient_name VARCHAR(100),
    recipient_id VARCHAR(50),
    delivery_notes TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (delivery_id) REFERENCES deliveries(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Photo evidence
CREATE TABLE delivery_photos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    delivery_evidence_id INT NOT NULL,
    photo_path VARCHAR(255) NOT NULL,
    photo_type ENUM('loading', 'product', 'signature', 'delivery', 'truck') NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (delivery_evidence_id) REFERENCES delivery_evidence(id) ON DELETE CASCADE
);

-- Security logs for tracking all actions
CREATE TABLE security_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    delivery_id INT,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (delivery_id) REFERENCES deliveries(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- System notifications
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'error', 'success') NOT NULL DEFAULT 'info',
    read_status BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, password, full_name, email, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador del Sistema', 'admin@daechang.com', 'admin');

-- Insert sample products
INSERT INTO products (product_code, name, description, category, weight_kg, dimensions) VALUES 
('SAM-TV-55', 'Televisor Samsung 55"', 'Televisor Smart TV 55 pulgadas', 'Electronics', 15.5, '123x71x8 cm'),
('SAM-REF-450', 'Refrigerador Samsung 450L', 'Refrigerador No Frost 450 litros', 'Appliances', 85.0, '185x60x65 cm'),
('SAM-WASH-8KG', 'Lavadora Samsung 8kg', 'Lavadora automática 8 kilogramos', 'Appliances', 65.0, '85x60x55 cm'),
('SAM-AC-12K', 'Aire Acondicionado 12000 BTU', 'Aire acondicionado split 12000 BTU', 'HVAC', 45.0, '80x30x25 cm');

-- Create indexes for better performance
CREATE INDEX idx_orders_status ON orders(status);
CREATE INDEX idx_orders_created_at ON orders(created_at);
CREATE INDEX idx_deliveries_status ON deliveries(status);
CREATE INDEX idx_deliveries_entry_time ON deliveries(entry_time);
CREATE INDEX idx_security_logs_created_at ON security_logs(created_at);
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_products_code ON products(product_code);