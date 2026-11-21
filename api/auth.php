<?php
// API đăng nhập / đăng xuất
//
//   GET  api/auth.php                  user đang đăng nhập → {"user": {...}} hoặc {"user": null}
//   POST api/auth.php?action=login     {username, password} → {"user": {...}}  (sai → 401)
//   POST api/auth.php?action=logout    → {"ok": true}
require_once dirname(__DIR__) . '/lib/bootstrap.php';

switch ($_SERVER['REQUEST_METHOD'] . ' ' . query_param('action')) {
    case 'GET ':
        json_response(['user' => auth_user()]);

    case 'POST login':
        $body     = read_json_body();
        $username = string_field($body, 'username');
        $password = string_field($body, 'password', '', false); // mật khẩu giữ nguyên khoảng trắng

        $user = $username !== '' ? user_verify($conn, $username, $password) : null;
        if (!$user) {
            // Không nói rõ sai tên hay sai mật khẩu, để không lộ tên đăng nhập nào tồn tại
            json_error('Sai tên đăng nhập hoặc mật khẩu.', 401);
        }
        auth_login($user);
        json_response(['user' => $user]);

    case 'POST logout':
        read_json_body(); // bắt buộc JSON để chống CSRF
        auth_logout();
        json_response(['ok' => true]);

    default:
        json_error('Yêu cầu không hợp lệ.', 400);
}
