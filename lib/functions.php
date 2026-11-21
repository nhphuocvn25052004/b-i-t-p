<?php
// Các hàm tiện ích dùng chung cho API

// Trả về JSON và dừng script
function json_response($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Trả về lỗi dạng {"error": "..."}
function json_error(string $message, int $status = 400): void
{
    json_response(['error' => $message], $status);
}

// Đọc body JSON của request, trả về mảng (rỗng nếu không hợp lệ)
function read_json_body(): array
{
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : [];
}
