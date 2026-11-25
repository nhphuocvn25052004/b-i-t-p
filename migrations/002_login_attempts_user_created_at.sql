-- Nâng cấp CSDL ĐANG CÓ DỮ LIỆU: chống dò mật khẩu + ngày tạo tài khoản.
-- Không xóa dữ liệu cũ. Chỉ chạy 1 lần (sau 001_categories_users.sql):
--     sudo mysql < migrations/002_login_attempts_user_created_at.sql
-- (CSDL mới tạo từ database.sql đã có sẵn, không cần chạy.)

USE website_db;

SET NAMES utf8mb4;

-- Ngày tạo tài khoản (hiện trong trang Quản lý người dùng).
-- Tài khoản cũ nhận thời điểm chạy migration.
ALTER TABLE users
    ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP;

-- Số lần nhập sai mật khẩu liên tiếp theo (tên đăng nhập, IP) — xem app/Auth/LoginThrottle.php
CREATE TABLE IF NOT EXISTS login_attempts (
    username       VARCHAR(50) NOT NULL,
    ip             VARCHAR(45) NOT NULL,           -- đủ cho IPv6
    failures       INT UNSIGNED NOT NULL DEFAULT 0,
    locked_until   DATETIME NULL,                  -- NULL = chưa bị khóa
    last_failed_at DATETIME NOT NULL,
    PRIMARY KEY (username, ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
