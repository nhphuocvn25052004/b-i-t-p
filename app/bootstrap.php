<?php
// Khởi động cho mọi API — mỗi file trong api/ chỉ cần require_once file này.
// Tạo biến $app (App\Core\App) để lấy các đối tượng dùng chung.
require_once __DIR__ . '/autoload.php';

use App\Core\App;
use App\Core\Database;
use App\Core\JsonResponse;

// Lỗi không lường trước ở bất kỳ API nào: ghi log, trả về JSON 500
// (thay cho try/catch lặp lại trong từng controller)
set_exception_handler(function (Throwable $e) {
    error_log('API lỗi: ' . $e->getMessage() . ' tại ' . $e->getFile() . ':' . $e->getLine());
    JsonResponse::error('Lỗi máy chủ.', 500);
});

try {
    $database = Database::fromConfigFile(dirname(__DIR__) . '/config.php');
} catch (RuntimeException $e) {
    // Thiếu config.php / không kết nối được: báo rõ lý do (chi tiết kỹ thuật đã ghi vào log)
    JsonResponse::error($e->getMessage(), 500);
}

$app = new App($database);
