-- Database: sumber_urip
-- Charset: utf8mb4, Collation: utf8mb4_unicode_ci, Engine: InnoDB

CREATE DATABASE IF NOT EXISTS `sumber_urip` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `sumber_urip`;

-- 1. Table: products
CREATE TABLE IF NOT EXISTS products (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(30) NOT NULL UNIQUE,
  name VARCHAR(100) NOT NULL,
  price INT UNSIGNED NOT NULL,
  cost_price INT UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Table: sales
CREATE TABLE IF NOT EXISTS sales (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  invoice_no VARCHAR(20) NOT NULL UNIQUE,
  sold_at DATETIME NOT NULL,
  total_amount INT UNSIGNED NOT NULL,
  total_cost INT UNSIGNED NOT NULL,
  cash_received INT UNSIGNED NULL,
  change_amount INT NULL,
  note VARCHAR(255) NULL,
  status ENUM('completed','void') NOT NULL DEFAULT 'completed',
  voided_at DATETIME NULL,
  void_reason VARCHAR(255) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_sales_sold_at (sold_at),
  INDEX idx_sales_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Table: sale_items
CREATE TABLE IF NOT EXISTS sale_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  sale_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  product_name VARCHAR(100) NOT NULL,
  quantity SMALLINT UNSIGNED NOT NULL,
  unit_price INT UNSIGNED NOT NULL,
  unit_cost INT UNSIGNED NOT NULL,
  subtotal INT UNSIGNED NOT NULL,
  cost_subtotal INT UNSIGNED NOT NULL,
  CONSTRAINT fk_items_sale FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
  CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products(id),
  INDEX idx_items_sale (sale_id),
  INDEX idx_items_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Table: expenses
CREATE TABLE IF NOT EXISTS expenses (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  expense_date DATE NOT NULL,
  category VARCHAR(50) NOT NULL,
  description VARCHAR(255) NULL,
  amount INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_expenses_date (expense_date),
  INDEX idx_expenses_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Seed Products (if not already seeded)
INSERT INTO products (code, name, price, cost_price, sort_order) VALUES
('GALON_BESAR', 'Galon Besar', 6000, 2500, 1),
('GALON_KECIL', 'Galon Kecil', 3000, 1200, 2),
('GALON_BARU', 'Galon Baru (Pabrik)', 55000, 45000, 3)
ON DUPLICATE KEY UPDATE 
  name = VALUES(name),
  sort_order = VALUES(sort_order);
