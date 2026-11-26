-- Nâng cấp CSDL ĐANG CÓ DỮ LIỆU: sản phẩm và danh mục có chủ sở hữu.
-- Không xóa dữ liệu. Chỉ chạy 1 lần (sau 004_super_admins.sql):
--     sudo mysql < migrations/005_data_owner.sql
-- (CSDL mới tạo từ database.sql đã có sẵn, không cần chạy.)
--
--   products.created_by  — ai đã thêm sản phẩm (NULL = admin tối cao / dữ liệu cũ → chỉ admin tối cao thấy)
--   categories.owner_id  — quản trị sở hữu danh mục (mỗi quản trị 1 bộ danh mục riêng; NULL = admin tối cao)
-- Sau khi chạy, dữ liệu cũ chỉ admin tối cao thấy — gán chủ cho từng dòng bằng UPDATE.

USE website_db;

SET NAMES utf8mb4;

ALTER TABLE products
    ADD COLUMN created_by INT NULL,
    ADD INDEX idx_products_created_by (created_by),
    ADD CONSTRAINT fk_products_created_by FOREIGN KEY (created_by)
        REFERENCES users (id) ON DELETE SET NULL;

-- Tên danh mục chỉ cần không trùng TRONG CÙNG 1 quản trị
ALTER TABLE categories
    ADD COLUMN owner_id INT NULL,
    DROP INDEX name,
    ADD UNIQUE KEY uq_categories_owner_name (owner_id, name),
    ADD CONSTRAINT fk_categories_owner FOREIGN KEY (owner_id)
        REFERENCES users (id) ON DELETE SET NULL;
