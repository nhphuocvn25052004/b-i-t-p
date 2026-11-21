<?php
// Các hàm tiện ích dùng chung cho API

// Trả về JSON và dừng script
function json_response($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Trả về lỗi dạng {"error": "..."}
function json_error(string $message, int $status = 400): void
{
    json_response(['error' => $message], $status);
}

// Đọc body JSON của request, trả về mảng (rỗng nếu không hợp lệ).
// Bắt buộc Content-Type: application/json — form HTML của trang web khác không gửi được
// kiểu này, nên đây cũng là một lớp chống CSRF.
function read_json_body(): array
{
    if (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== 0) {
        json_error('Content-Type phải là application/json.', 415);
    }
    $raw = file_get_contents('php://input');
    if (trim($raw) === '') {
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        json_error('Dữ liệu JSON không hợp lệ.', 400);
    }
    return $data;
}

// Lấy 1 trường chuỗi trong mảng (body JSON, $_GET...). Không phải chuỗi thì trả về $default.
function string_field(array $data, string $key, string $default = '', bool $trim = true): string
{
    $value = $data[$key] ?? null;
    if (!is_string($value)) {
        return $default;
    }
    return $trim ? trim($value) : $value;
}

// Lấy tham số chuỗi từ query string (?key=...)
function query_param(string $key, string $default = ''): string
{
    return string_field($_GET, $key, $default);
}

// Chạy câu SQL bằng prepared statement và trả về kết quả (null với INSERT/UPDATE/DELETE)
function db_query(mysqli $conn, string $sql, string $types = '', array $params = [])
{
    $stmt = $conn->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt->get_result() ?: null;
}
