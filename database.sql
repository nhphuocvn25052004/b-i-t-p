-- Xuất lúc 2026-11-17 04:11:42 bằng: php scripts/export_db.php
-- CẢNH BÁO: chạy file này sẽ XÓA bảng products hiện có và tạo lại từ dữ liệu bên dưới.

CREATE DATABASE IF NOT EXISTS website_db
    DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;

USE website_db;

SET NAMES utf8mb4;

DROP TABLE IF EXISTS products;

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO products (id, name, price, description)
VALUES
(1, 'Tàu vận tải biển', 1500000000.00, 'Tàu vận tải hàng hóa'),
(2, '22easd', 200.00, 'ádas'),
(3, 'sadas', 123456.00, '23456'),
(4, 'á', 1231231232.00, '12312321312');
