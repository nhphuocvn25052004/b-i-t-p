<?php
// Kết nối CSDL dùng chung — nạp bằng require_once ở các file cần dùng
// Từ PHP 8.1, mysqli báo lỗi bằng exception nên phải dùng try/catch
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $db = require dirname(__DIR__) . '/config.php';
    $conn = new mysqli($db['host'], $db['user'], $db['pass'], $db['name']);
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    error_log("Lỗi kết nối MySQL: " . $e->getMessage());
    http_response_code(500);
    exit("Lỗi kết nối MySQL");
}
