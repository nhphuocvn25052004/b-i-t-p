<?php
// Model: bảng users (tài khoản đăng nhập)

// Kiểm tra tên đăng nhập + mật khẩu. Đúng thì trả về user (không có password_hash), sai trả về null.
function user_verify(mysqli $conn, string $username, string $password): ?array
{
    $user = db_query(
        $conn,
        "SELECT id, username, password_hash, role FROM users WHERE username = ?",
        's',
        [$username]
    )->fetch_assoc();

    // Mật khẩu chỉ lưu dạng băm (password_hash), so sánh bằng password_verify
    if (!$user || !password_verify($password, $user['password_hash'])) {
        return null;
    }

    return [
        'id'       => (int)$user['id'],
        'username' => $user['username'],
        'role'     => $user['role'],
    ];
}
