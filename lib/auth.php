<?php
// Xác thực (Authentication) và phân quyền (Authorization) bằng PHP Session.
//
// Sau khi đăng nhập, thông tin user được lưu trên SERVER trong $_SESSION.
// Trình duyệt chỉ giữ cookie chứa session id (HttpOnly — JavaScript không đọc được),
// và tự gửi cookie này kèm mọi request tới api/*.php.

const AUTH_SESSION_NAME = 'BITP_SESSION';

// Thuộc tính cookie phiên — dùng chung khi tạo (đăng nhập) và xóa (đăng xuất)
function auth_cookie_options(): array
{
    return [
        'path'     => '/',
        'httponly' => true,       // chặn JavaScript đọc cookie (giảm thiệt hại khi bị XSS)
        'samesite' => 'Lax',      // không gửi cookie với request POST/PUT/DELETE từ trang khác
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ];
}

function auth_start_session(bool $readOnly = false): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_name(AUTH_SESSION_NAME);
    // lifetime 0: cookie mất khi đóng trình duyệt
    session_set_cookie_params(['lifetime' => 0] + auth_cookie_options());
    // read_and_close: đọc xong đóng ngay, không khóa file session khi chỉ cần đọc
    session_start($readOnly ? ['read_and_close' => true] : []);
}

// User đang đăng nhập, hoặc null
function auth_user(): ?array
{
    auth_start_session(true);
    return $_SESSION['user'] ?? null;
}

function auth_login(array $user): void
{
    auth_start_session();
    session_regenerate_id(true); // đổi session id sau khi đăng nhập — chống session fixation
    $_SESSION['user'] = $user;
    session_write_close();
}

function auth_logout(): void
{
    auth_start_session();
    $_SESSION = [];
    setcookie(session_name(), '', ['expires' => time() - 3600] + auth_cookie_options());
    session_destroy();
}

// Authentication: bắt buộc đăng nhập, nếu chưa thì trả về 401
function require_login(): array
{
    $user = auth_user();
    if (!$user) {
        json_error('Bạn cần đăng nhập.', 401);
    }
    return $user;
}

// Authorization: bắt buộc có vai trò $role, nếu không thì trả về 403
function require_role(string $role): array
{
    $user = require_login();
    if ($user['role'] !== $role) {
        json_error('Bạn không có quyền thực hiện thao tác này.', 403);
    }
    return $user;
}
