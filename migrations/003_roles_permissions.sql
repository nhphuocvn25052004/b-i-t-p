-- Nâng cấp CSDL ĐANG CÓ DỮ LIỆU: phân quyền 3 cấp admin / editor (user1) / user (user2).
-- Không xóa dữ liệu cũ. Chỉ chạy 1 lần (sau 002_login_attempts_user_created_at.sql):
--     sudo mysql < migrations/003_roles_permissions.sql
-- (CSDL mới tạo từ database.sql đã có sẵn, không cần chạy.)

USE website_db;

SET NAMES utf8mb4;

-- Thêm vai trò editor (user1): chỉ làm những việc admin cấp trong bảng user_permissions.
-- created_by: ai đã tạo tài khoản này (NULL = tự đăng ký / tài khoản cũ).
-- Xóa người tạo → created_by thành NULL, tài khoản do admin quản lý.
ALTER TABLE users
    MODIFY COLUMN role ENUM('admin', 'editor', 'user') NOT NULL DEFAULT 'user',
    ADD COLUMN created_by INT NULL,
    ADD CONSTRAINT fk_users_created_by FOREIGN KEY (created_by)
        REFERENCES users (id) ON DELETE SET NULL;

-- Quyền admin cấp cho từng editor (xem Auth::PERMISSIONS)
CREATE TABLE IF NOT EXISTS user_permissions (
    user_id    INT NOT NULL,
    permission VARCHAR(50) NOT NULL,
    PRIMARY KEY (user_id, permission),
    CONSTRAINT fk_user_permissions_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
