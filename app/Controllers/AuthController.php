<?php
namespace App\Controllers;

use App\Auth\Auth;
use App\Core\JsonResponse;
use App\Core\Request;

/**
 * API đăng nhập / đăng xuất
 *
 *   GET  api/auth.php                  user đang đăng nhập → {"user": {...}} hoặc {"user": null}
 *   POST api/auth.php?action=login     {username, password} → {"user": {...}}  (sai → 401)
 *   POST api/auth.php?action=logout    → {"ok": true}
 */
class AuthController
{
    /** @var Request */
    private $request;

    /** @var Auth */
    private $auth;

    public function __construct(Request $request, Auth $auth)
    {
        $this->request = $request;
        $this->auth    = $auth;
    }

    // Chọn hành động theo phương thức HTTP + tham số ?action=
    public function handle(): void
    {
        switch ($this->request->method() . ' ' . $this->request->query('action')) {
            case 'GET ':
                $this->me();
                break;
            case 'POST login':
                $this->login();
                break;
            case 'POST logout':
                $this->logout();
                break;
            default:
                JsonResponse::error('Yêu cầu không hợp lệ.', 400);
        }
    }

    public function me(): void
    {
        JsonResponse::send(['user' => $this->auth->user()]);
    }

    public function login(): void
    {
        $body     = $this->request->json();
        $username = Request::stringField($body, 'username');
        $password = Request::stringField($body, 'password', '', false); // mật khẩu giữ nguyên khoảng trắng

        $user = $this->auth->attempt($username, $password);
        if (!$user) {
            // Không nói rõ sai tên hay sai mật khẩu, để không lộ tên đăng nhập nào tồn tại
            JsonResponse::error('Sai tên đăng nhập hoặc mật khẩu.', 401);
        }
        JsonResponse::send(['user' => $user]);
    }

    public function logout(): void
    {
        $this->request->json(); // bắt buộc JSON để chống CSRF
        $this->auth->logout();
        JsonResponse::send(['ok' => true]);
    }
}
