-- Nâng cấp CSDL ĐANG CÓ DỮ LIỆU: tách admin tối cao ra bảng riêng super_admins.
-- Không xóa dữ liệu (tài khoản admin được CHUYỂN sang bảng mới, giữ nguyên mật khẩu).
-- Chỉ chạy 1 lần (sau 003_roles_permissions.sql):
--     sudo mysql < migrations/004_super_admins.sql
-- (CSDL mới tạo từ database.sql đã có sẵn, không cần chạy.)
--
-- Sau migration:
--   super_admins  — admin tối cao: thấy mọi tài khoản, đổi mật khẩu / xóa bất kỳ ai
--   users.admin   — quản trị thường: chỉ thấy / quản lý cây tài khoản do mình tạo hoặc nâng lên

USE website_db;

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS super_admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

START TRANSACTION;

-- Các admin gốc (không do ai tạo) trở thành admin tối cao
INSERT IGNORE INTO super_admins (username, password_hash, created_at)
SELECT username, password_hash, created_at FROM users WHERE role = 'admin' AND created_by IS NULL;

-- Tài khoản do họ tạo → created_by = NULL (thuộc quyền admin tối cao)
UPDATE users c JOIN users p ON p.id = c.created_by
   SET c.created_by = NULL
 WHERE p.role = 'admin' AND p.created_by IS NULL;

DELETE FROM users WHERE role = 'admin' AND created_by IS NULL;

COMMIT;
