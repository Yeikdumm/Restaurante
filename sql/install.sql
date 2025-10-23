-- Crafting Restaurante initial schema
CREATE DATABASE IF NOT EXISTS `crafting_restaurante` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `crafting_restaurante`;

-- Users & RBAC
CREATE TABLE roles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL,
  slug VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE permissions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  key_name VARCHAR(100) NOT NULL UNIQUE,
  description VARCHAR(255) NULL
);

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_roles (
  user_id INT NOT NULL,
  role_id INT NOT NULL,
  PRIMARY KEY (user_id, role_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

CREATE TABLE role_permissions (
  role_id INT NOT NULL,
  permission_id INT NOT NULL,
  PRIMARY KEY (role_id, permission_id),
  FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
  FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- Company settings
CREATE TABLE company_settings (
  id TINYINT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  nit VARCHAR(50) NOT NULL,
  iva_rate DECIMAL(5,4) NOT NULL DEFAULT 0.1900,
  logo_path VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO company_settings (id, name, nit, iva_rate) VALUES (1, 'Mi Heladeria', '123456789-0', 0.1900);

-- Inventory
CREATE TABLE inventory_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  unit ENUM('g','unit') NOT NULL DEFAULT 'g',
  stock_qty DECIMAL(12,3) NOT NULL DEFAULT 0,
  avg_cost_per_unit DECIMAL(12,4) NOT NULL DEFAULT 0,
  min_stock DECIMAL(12,3) NOT NULL DEFAULT 0,
  active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE inventory_movements (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  item_id INT NOT NULL,
  qty_change DECIMAL(12,3) NOT NULL,
  cost_total DECIMAL(12,2) NOT NULL DEFAULT 0,
  type ENUM('in','out','adjust') NOT NULL,
  note VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (item_id) REFERENCES inventory_items(id)
);

-- Products & bill of materials (recipes)
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  sku VARCHAR(64) NULL UNIQUE,
  public_price_gross DECIMAL(12,2) NOT NULL DEFAULT 0,
  tax_rate DECIMAL(5,4) NOT NULL DEFAULT 0.1900,
  active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE product_ingredients (
  product_id INT NOT NULL,
  item_id INT NOT NULL,
  qty_required DECIMAL(12,3) NOT NULL,
  PRIMARY KEY (product_id, item_id),
  FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  FOREIGN KEY (item_id) REFERENCES inventory_items(id)
);

-- Orders
CREATE TABLE tables_catalog (
  id INT AUTO_INCREMENT PRIMARY KEY,
  table_number INT NOT NULL UNIQUE,
  active TINYINT(1) DEFAULT 1
);

CREATE TABLE orders (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(20) NOT NULL UNIQUE,
  type ENUM('table','delivery') NOT NULL,
  table_number INT NULL,
  customer_name VARCHAR(120) NULL,
  customer_phone VARCHAR(40) NULL,
  status ENUM('pending','in_kitchen','ready','paid','cancelled') NOT NULL DEFAULT 'pending',
  created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE order_items (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT NOT NULL,
  product_id INT NOT NULL,
  qty DECIMAL(12,3) NOT NULL,
  unit_price_gross DECIMAL(12,2) NOT NULL,
  net_amount DECIMAL(12,2) NOT NULL,
  tax_amount DECIMAL(12,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (product_id) REFERENCES products(id)
);

CREATE TABLE order_status_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT NOT NULL,
  status VARCHAR(20) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

-- Payments & cash drawer
CREATE TABLE payments (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  order_id BIGINT NOT NULL,
  method ENUM('cash','card','transfer') NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

CREATE TABLE cash_movements (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  type ENUM('in','out') NOT NULL,
  amount DECIMAL(12,2) NOT NULL,
  note VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Permissions seed
INSERT INTO permissions (key_name, description) VALUES
('view.dashboard','Ver dashboard'),
('manage.orders','Crear/gestionar pedidos'),
('kitchen.view','Ver pantalla de cocina'),
('cashier.view','Ver módulo de caja'),
('inventory.manage','Gestionar inventario'),
('products.manage','Gestionar productos y recetas'),
('users.manage','Gestionar usuarios y roles'),
('reports.view','Ver reportes'),
('settings.manage','Configurar empresa');

INSERT INTO roles (name, slug) VALUES
('Administrador','admin'),
('Mesero','waiter'),
('Domicilios','delivery'),
('Cajero','cashier'),
('Cocina','kitchen');

-- Role permissions mapping
-- Admin gets all
INSERT INTO role_permissions (role_id, permission_id)
SELECT 1 AS role_id, id FROM permissions;

-- Waiter
INSERT INTO role_permissions (role_id, permission_id)
SELECT 2, id FROM permissions WHERE key_name IN ('view.dashboard','manage.orders');

-- Delivery
INSERT INTO role_permissions (role_id, permission_id)
SELECT 3, id FROM permissions WHERE key_name IN ('view.dashboard','manage.orders');

-- Cashier
INSERT INTO role_permissions (role_id, permission_id)
SELECT 4, id FROM permissions WHERE key_name IN ('view.dashboard','cashier.view','reports.view');

-- Kitchen
INSERT INTO role_permissions (role_id, permission_id)
SELECT 5, id FROM permissions WHERE key_name IN ('kitchen.view');

-- Default admin user (password: Admin123*)
INSERT INTO users (name, email, password_hash) VALUES
('Administrador', 'admin@local', '$2y$10$J4cWDv9xzA9yG4t2SdqgqOTc3t7y3fPzKXbK3m2oxp1sZ6dufRNP2');

INSERT INTO user_roles (user_id, role_id) VALUES (1,1);