-- =======================================================
-- FINAL DATABASE SCHEMA & DEMO DATA FOR POS RBAC
-- System: Multi-Role Support, Plain Text Password, Dark Mode Ready
-- =======================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP DATABASE IF EXISTS pos_rbac;
CREATE DATABASE pos_rbac;
USE pos_rbac;

-- 1. RBAC STRUCTURE
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT
);

CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    label VARCHAR(100) NOT NULL
);

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT,
    permission_id INT,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS user_roles (
    user_id INT,
    role_id INT,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

-- 2. MASTER DATA TABLES
CREATE TABLE IF NOT EXISTS suppliers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    contact_info TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    supplier_id INT,
    name VARCHAR(100) NOT NULL,
    cost_price DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    price DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
    stock INT NOT NULL DEFAULT 0,
    unit VARCHAR(20) DEFAULT 'pcs',
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id) ON DELETE SET NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. TRANSACTIONS TABLES
CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    customer_id INT,
    invoice_no VARCHAR(50) NOT NULL UNIQUE,
    total_amount DECIMAL(15, 2) NOT NULL,
    payment_type ENUM('cash', 'card', 'transfer', 'credit') DEFAULT 'cash',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS sales_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT,
    product_id INT,
    qty INT NOT NULL,
    unit_price DECIMAL(15, 2) NOT NULL,
    subtotal DECIMAL(15, 2) NOT NULL,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- 4. Debt Payments Table
CREATE TABLE IF NOT EXISTS debt_payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sale_id INT,
    amount_paid DECIMAL(15, 2) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE
);

-- 5. INITIAL SEEDING DATA
INSERT INTO roles (id, name, description) VALUES 
(1, 'admin', 'Full access to all system modules'),
(2, 'manager', 'Manage products, suppliers, and view reports'),
(3, 'cashier', 'Access to POS system and customer management');

INSERT INTO permissions (name, label) VALUES
('manage_rbac', 'Manage Roles & Permissions'),
('manage_users', 'Manage User Accounts'),
('manage_products', 'Manage Products'),
('manage_suppliers', 'Manage Suppliers'),
('manage_customers', 'Manage Customers'),
('view_reports', 'View Sales Reports'),
('access_pos', 'Access Cashier POS');

-- Assign Permissions to Roles
-- Admin: All
INSERT INTO role_permissions (role_id, permission_id) SELECT 1, id FROM permissions;
-- Manager: Inventory & Reports
INSERT INTO role_permissions (role_id, permission_id) SELECT 2, id FROM permissions WHERE name IN ('manage_products', 'manage_suppliers', 'manage_customers', 'view_reports');
-- Cashier: POS & Customers
INSERT INTO role_permissions (role_id, permission_id) SELECT 3, id FROM permissions WHERE name IN ('manage_customers', 'access_pos');

-- 5. DEMO DATA SEEDING
-- Users
INSERT INTO users (id, username, password, fullname) VALUES 
(1, 'admin', 'admin123', 'Super Administrator'),
(2, 'manager_user', 'manager123', 'Project Manager'),
(3, 'cashier_user', 'cashier123', 'Frontline Cashier'),
(4, 'multi_user', 'multi123', 'Testing Multi Role');

-- User Roles Mapping
INSERT INTO user_roles (user_id, role_id) VALUES 
(1, 1), (2, 2), (3, 3), (4, 2), (4, 3);

-- Suppliers
INSERT INTO suppliers (id, name, phone, address) VALUES 
(1, 'Global Tech Solutions', '081299001122', 'Jakarta Industrial Estate'),
(2, 'Fresh Produce Co.', '081388776655', 'Lembang Farmer Center'),
(3, 'Modern Stationery', '081544332211', 'Surabaya Business Park');

-- Products
INSERT INTO products (id, supplier_id, name, cost_price, price, stock, unit) VALUES 
(1, 1, 'Laptop Pro 14', 12000000, 15000000, 10, 'pcs'),
(2, 1, 'Wireless Mouse', 150000, 250000, 50, 'pcs'),
(3, 2, 'Organic Apple (1kg)', 30000, 45000, 100, 'kg'),
(4, 3, 'Executive Notebook', 20000, 35000, 200, 'pcs');

-- Customers
INSERT INTO customers (id, name, phone, contact_info) VALUES 
(1, 'Budi Santoso', '081122334455', 'Regular Member'),
(2, 'Siti Aminah', '081255667788', 'Gold Member');

SET FOREIGN_KEY_CHECKS = 1;
