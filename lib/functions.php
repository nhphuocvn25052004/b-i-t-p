<?php
// Các hàm tiện ích dùng chung

// Escape dữ liệu trước khi in ra HTML
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Chuyển hướng và dừng script
function redirect(string $url): void
{
    header("Location: $url");
    exit;
}

// Thông báo sau khi thêm / sửa / xóa (truyền qua ?msg=, số lượng qua ?n=)
function flash_message(): string
{
    $messages = [
        'added'        => 'Đã thêm sản phẩm.',
        'updated'      => 'Đã cập nhật sản phẩm.',
        'deleted'      => 'Đã xóa sản phẩm.',
        'bulk_deleted' => 'Đã xóa %d sản phẩm.',
        'none_chosen'  => 'Bạn chưa chọn sản phẩm nào để xóa.',
    ];
    $msg = $messages[$_GET['msg'] ?? ''] ?? '';
    return sprintf($msg, (int)($_GET['n'] ?? 0));
}
