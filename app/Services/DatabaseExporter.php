<?php
namespace App\Services;

use App\Core\Database;

/**
 * Xuất dữ liệu hiện tại (danh mục + sản phẩm) thành nội dung file database.sql.
 *
 * Bảng users KHÔNG được xuất (tránh đưa mật khẩu băm của người thật vào git):
 * file chỉ tạo bảng users nếu chưa có, kèm 2 tài khoản demo.
 *
 *   $exporter = new DatabaseExporter($db);
 *   file_put_contents('database.sql', $exporter->export());
 *   echo $exporter->categoryCount(), ' danh mục';
 */
class DatabaseExporter
{
    /** @var Database */
    private $db;

    /** @var int */
    private $categoryCount = 0;

    /** @var int */
    private $productCount = 0;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    // Nội dung file .sql: tạo CSDL, bảng, tài khoản demo và toàn bộ danh mục + sản phẩm
    public function export(): string
    {
        $categories = $this->db->query("SELECT id, name FROM categories ORDER BY id ASC")
                               ->fetch_all(MYSQLI_ASSOC);
        $products   = $this->db->query("SELECT id, name, price, description, category_id FROM products ORDER BY id ASC")
                               ->fetch_all(MYSQLI_ASSOC);

        $this->categoryCount = count($categories);
        $this->productCount  = count($products);

        return $this->header() . $this->schema()
             . $this->categoriesInsert($categories)
             . $this->productsInsert($products);
    }

    // Số danh mục / sản phẩm của lần export() gần nhất
    public function categoryCount(): int
    {
        return $this->categoryCount;
    }

    public function productCount(): int
    {
        return $this->productCount;
    }

    private function header(): string
    {
        return "-- Xuất lúc " . date('Y-m-d H:i:s') . " bằng: php scripts/export_db.php\n"
             . "-- CẢNH BÁO: chạy file này sẽ XÓA bảng products, categories hiện có và tạo lại từ dữ liệu bên dưới.\n"
             . "-- Bảng users được giữ nguyên nếu đã tồn tại.\n\n";
    }

    private function schema(): string
    {
        return <<<SQL
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
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS login_attempts (
    username VARCHAR(50) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    failures INT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME NULL,
    last_failed_at DATETIME NOT NULL,
    PRIMARY KEY (username, ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Tài khoản demo: admin / admin123 (toàn quyền), user / user123 (chỉ xem). ĐỔI MẬT KHẨU khi dùng thật.
INSERT IGNORE INTO users (username, password_hash, role) VALUES
('admin', '\$2y\$12\$kCIh7o2yR0jBNskDBIFajulufMJ0vQsBNfrbAZB1ZF3g5ifU/v5zu', 'admin'),
('user',  '\$2y\$12\$D9MbG3pe3Wji9S/U3Kivt.xk5MIct5nJuOWiPSp6w3UlxZfgHvDrm', 'user');

SQL;
    }

    private function categoriesInsert(array $categories): string
    {
        if (!$categories) {
            return '';
        }
        $rows = array_map(function (array $c) {
            return sprintf("(%s, %s)", $this->int($c['id']), $this->str($c['name']));
        }, $categories);
        return "\nINSERT INTO categories (id, name)\nVALUES\n" . implode(",\n", $rows) . ";\n";
    }

    private function productsInsert(array $products): string
    {
        if (!$products) {
            return '';
        }
        $rows = array_map(function (array $p) {
            return sprintf(
                "(%s, %s, %s, %s, %s)",
                $this->int($p['id']),
                $this->str($p['name']),
                $p['price'],
                $this->str($p['description']),
                $this->int($p['category_id'])
            );
        }, $products);
        return "\nINSERT INTO products (id, name, price, description, category_id)\nVALUES\n"
             . implode(",\n", $rows) . ";\n";
    }

    // Giá trị chuỗi trong SQL: NULL hoặc '...' đã escape
    private function str(?string $value): string
    {
        return $value === null ? 'NULL' : "'" . $this->db->escape($value) . "'";
    }

    // Giá trị số nguyên trong SQL: NULL hoặc số
    private function int($value): string
    {
        return $value === null ? 'NULL' : (string)(int)$value;
    }
}
