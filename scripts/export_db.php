<?php
// Xuất dữ liệu hiện tại (danh mục + sản phẩm) ra file database.sql
// Cách chạy:  php scripts/export_db.php [đường_dẫn_file_khác]
//
// Bảng users KHÔNG được xuất (tránh đưa mật khẩu băm của người thật vào git):
// file chỉ tạo bảng users nếu chưa có, kèm 2 tài khoản demo.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Chỉ chạy từ dòng lệnh.');
}

require_once __DIR__ . '/../lib/functions.php';
require_once __DIR__ . '/../lib/import.php';

$file = $argv[1] ?? __DIR__ . '/../database.sql';

$categories = $conn->query("SELECT id, name FROM categories ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
$products   = $conn->query(
    "SELECT id, name, price, description, category_id FROM products ORDER BY id ASC"
)->fetch_all(MYSQLI_ASSOC);

$str = fn($v) => $v === null ? 'NULL' : "'" . $conn->real_escape_string($v) . "'";
$int = fn($v) => $v === null ? 'NULL' : (string)(int)$v;

$sql = "-- Xuất lúc " . date('Y-m-d H:i:s') . " bằng: php scripts/export_db.php\n"
     . "-- CẢNH BÁO: chạy file này sẽ XÓA bảng products, categories hiện có và tạo lại từ dữ liệu bên dưới.\n"
     . "-- Bảng users được giữ nguyên nếu đã tồn tại.\n\n"
     . <<<SQL
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
('admin', '\$2y\$12\$kCIh7o2yR0jBNskDBIFajulufMJ0vQsBNfrbAZB1ZF3g5ifU/v5zu', 'admin'),
('user',  '\$2y\$12\$D9MbG3pe3Wji9S/U3Kivt.xk5MIct5nJuOWiPSp6w3UlxZfgHvDrm', 'user');

SQL;

if ($categories) {
    $rows = array_map(fn($c) => sprintf("(%s, %s)", $int($c['id']), $str($c['name'])), $categories);
    $sql .= "\nINSERT INTO categories (id, name)\nVALUES\n" . implode(",\n", $rows) . ";\n";
}

if ($products) {
    $rows = array_map(fn($p) => sprintf(
        "(%s, %s, %s, %s, %s)",
        $int($p['id']),
        $str($p['name']),
        $p['price'],
        $str($p['description']),
        $int($p['category_id'])
    ), $products);
    $sql .= "\nINSERT INTO products (id, name, price, description, category_id)\nVALUES\n"
          . implode(",\n", $rows) . ";\n";
}

if (@file_put_contents($file, $sql) === false) {
    fwrite(STDERR, "Không ghi được $file (kiểm tra quyền ghi file).\n");
    exit(1);
}

echo "Đã xuất " . count($categories) . " danh mục, " . count($products) . " sản phẩm ra $file\n";
