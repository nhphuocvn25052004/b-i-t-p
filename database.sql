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
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    description TEXT,
    category_id INT NULL,
    INDEX idx_products_category (category_id),
    CONSTRAINT fk_products_category FOREIGN KEY (category_id)
        REFERENCES categories (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tài khoản demo: admin / admin123 (toàn quyền), user / user123 (chỉ xem). ĐỔI MẬT KHẨU khi dùng thật.
INSERT IGNORE INTO users (username, password_hash, role) VALUES
('admin', '$2y$12$kCIh7o2yR0jBNskDBIFajulufMJ0vQsBNfrbAZB1ZF3g5ifU/v5zu', 'admin'),
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
