<?php
// Xuất dữ liệu hiện tại (danh mục + sản phẩm) ra file database.sql
// Cách chạy:  php scripts/export_db.php [đường_dẫn_file_khác]
// Nội dung file do App\Services\DatabaseExporter tạo.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Chỉ chạy từ dòng lệnh.');
}

require_once __DIR__ . '/../app/autoload.php';

use App\Core\Database;
use App\Services\DatabaseExporter;

$file = $argv[1] ?? __DIR__ . '/../database.sql';

try {
    $database = Database::fromConfigFile(__DIR__ . '/../config.php');
} catch (RuntimeException $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

$exporter = new DatabaseExporter($database);

if (@file_put_contents($file, $exporter->export()) === false) {
    fwrite(STDERR, "Không ghi được $file (kiểm tra quyền ghi file).\n");
    exit(1);
}

echo "Đã xuất {$exporter->categoryCount()} danh mục, {$exporter->productCount()} sản phẩm ra $file\n";
