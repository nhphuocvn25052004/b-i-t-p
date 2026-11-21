<?php
// Kết nối CSDL dùng chung, tạo biến $conn (cần nạp functions.php trước)
// Từ PHP 8.1, mysqli báo lỗi bằng exception nên phải dùng try/catch
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Dòng lệnh: in ra stderr; web: trả JSON 500
function db_fail(string $message): void
{
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
    json_error($message, 500);
}

$configFile = dirname(__DIR__) . '/config.php';
if (!is_file($configFile)) {
    db_fail('Thiếu file config.php — hãy copy config.example.php thành config.php và điền thông tin CSDL.');
}

try {
    $db   = require $configFile;
    $conn = new mysqli($db['host'], $db['user'], $db['pass'], $db['name'], (int)($db['port'] ?? 3306));
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('Lỗi kết nối MySQL: ' . $e->getMessage());
    db_fail('Lỗi kết nối MySQL — kiểm tra MySQL đã chạy chưa và thông tin trong config.php.');
}
