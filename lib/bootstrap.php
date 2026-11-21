<?php
// Nạp mọi thứ cần thiết — mỗi file trong api/ chỉ cần require_once file này
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/import.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/models/product.php';
require_once __DIR__ . '/models/category.php';
require_once __DIR__ . '/models/user.php';

// Lỗi không lường trước ở bất kỳ API nào: ghi log, trả về JSON 500
// (thay cho try/catch lặp lại trong từng file api/)
set_exception_handler(function (Throwable $e) {
    error_log('API lỗi: ' . $e->getMessage() . ' tại ' . $e->getFile() . ':' . $e->getLine());
    json_error('Lỗi máy chủ.', 500);
});
