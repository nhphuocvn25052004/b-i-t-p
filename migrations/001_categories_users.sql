-- Nâng cấp CSDL ĐANG CÓ DỮ LIỆU: thêm danh mục sản phẩm và tài khoản đăng nhập.
-- Không xóa dữ liệu cũ. Chỉ chạy 1 lần:
--     sudo mysql < migrations/001_categories_users.sql
-- (CSDL mới tạo từ database.sql đã có sẵn các bảng này, không cần chạy.)

USE website_db;

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT IGNORE INTO categories (name) VALUES
('Tàu thủy'),
('Thiết bị hàng hải'),
('Phụ tùng');

ALTER TABLE products
    ADD COLUMN category_id INT NULL AFTER description,
    ADD INDEX idx_products_category (category_id),
    ADD CONSTRAINT fk_products_category FOREIGN KEY (category_id)
        REFERENCES categories (id) ON DELETE SET NULL;

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
