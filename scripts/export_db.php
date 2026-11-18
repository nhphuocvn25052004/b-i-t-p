<?php
// Xuất dữ liệu hiện tại của bảng products ra file database.sql
// Cách chạy:  php scripts/export_db.php [đường_dẫn_file_khác]
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Chỉ chạy từ dòng lệnh.');
}

require_once __DIR__ . '/../lib/import.php';

$file = $argv[1] ?? __DIR__ . '/../database.sql';
$rows = $conn->query("SELECT * FROM products ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);

$sql = "-- Xuất lúc " . date('Y-m-d H:i:s') . " bằng: php scripts/export_db.php\n"
     . "-- CẢNH BÁO: chạy file này sẽ XÓA bảng products hiện có và tạo lại từ dữ liệu bên dưới.\n\n"
     . "CREATE DATABASE IF NOT EXISTS website_db\n"
     . "    DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;\n\n"
     . "USE website_db;\n\n"
     . "SET NAMES utf8mb4;\n\n"
     . "DROP TABLE IF EXISTS products;\n\n"
     . "CREATE TABLE products (\n"
     . "    id INT AUTO_INCREMENT PRIMARY KEY,\n"
     . "    name VARCHAR(255) NOT NULL,\n"
     . "    price DECIMAL(12,2) NOT NULL,\n"
     . "    description TEXT\n"
     . ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;\n";

if ($rows) {
    $values = [];
    foreach ($rows as $r) {
        $desc = $r['description'] === null ? 'NULL' : "'" . $conn->real_escape_string($r['description']) . "'";
        $values[] = sprintf(
            "(%d, '%s', %s, %s)",
            $r['id'],
            $conn->real_escape_string($r['name']),
            $r['price'],
            $desc
        );
    }
    $sql .= "\nINSERT INTO products (id, name, price, description)\nVALUES\n"
          . implode(",\n", $values) . ";\n";
}

if (@file_put_contents($file, $sql) === false) {
    fwrite(STDERR, "Không ghi được $file (kiểm tra quyền ghi file).\n");
    exit(1);
}

echo "Đã xuất " . count($rows) . " sản phẩm ra $file\n";
