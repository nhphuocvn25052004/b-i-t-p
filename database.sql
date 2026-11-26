-- Xuất lúc 2026-11-22 00:00:00 bằng: php scripts/export_db.php
-- CẢNH BÁO: chạy file này sẽ XÓA bảng products, categories hiện có và tạo lại từ dữ liệu bên dưới.
-- Bảng users được giữ nguyên nếu đã tồn tại.

CREATE DATABASE IF NOT EXISTS website_db
    DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;

USE website_db;

SET NAMES utf8mb4;

DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    owner_id INT NULL,
    UNIQUE KEY uq_categories_owner_name (owner_id, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    description TEXT,
    category_id INT NULL,
    created_by INT NULL,
    INDEX idx_products_category (category_id),
    INDEX idx_products_created_by (created_by),
    CONSTRAINT fk_products_category FOREIGN KEY (category_id)
        REFERENCES categories (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'editor', 'user') NOT NULL DEFAULT 'user',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_by INT NULL,
    CONSTRAINT fk_users_created_by FOREIGN KEY (created_by)
        REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Admin tối cao — bảng riêng, tách khỏi users (xem migrations/004_super_admins.sql)
CREATE TABLE IF NOT EXISTS super_admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS user_permissions (
    user_id INT NOT NULL,
    permission VARCHAR(50) NOT NULL,
    PRIMARY KEY (user_id, permission),
    CONSTRAINT fk_user_permissions_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
    username VARCHAR(50) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    failures INT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    last_failed_at DATETIME NOT NULL,
    PRIMARY KEY (username, ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Chủ sở hữu sản phẩm / danh mục (bảng users tạo sau products, categories nên thêm khóa ngoại ở đây)
ALTER TABLE products ADD CONSTRAINT fk_products_created_by FOREIGN KEY (created_by)
    REFERENCES users (id) ON DELETE SET NULL;
ALTER TABLE categories ADD CONSTRAINT fk_categories_owner FOREIGN KEY (owner_id)
    REFERENCES users (id) ON DELETE SET NULL;

-- Tài khoản demo — ĐỔI MẬT KHẨU khi dùng thật:
--   admin / admin123  admin tối cao (bảng super_admins)
--   user  / user123   chỉ xem
INSERT IGNORE INTO super_admins (username, password_hash) VALUES
('admin', '$2y$12$kCIh7o2yR0jBNskDBIFajulufMJ0vQsBNfrbAZB1ZF3g5ifU/v5zu');

INSERT IGNORE INTO users (username, password_hash, role) VALUES
('user',  '$2y$12$D9MbG3pe3Wji9S/U3Kivt.xk5MIct5nJuOWiPSp6w3UlxZfgHvDrm', 'user');

INSERT INTO categories (id, name)
VALUES
(1, 'Tàu thủy'),
(2, 'Thiết bị hàng hải'),
(3, 'Phụ tùng');

INSERT INTO products (id, name, price, description, category_id)
VALUES
(1, 'Tàu vận tải biển', 1500000000.00, 'Tàu vận tải hàng hóa', 1),
(2, '22easd', 200.00, 'ádas', NULL),
(3, 'sadas', 123456.00, '23456', 3),
(4, 'á', 1231231232.00, '12312321312', 2);
