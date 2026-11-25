<?php
namespace App\Controllers;

use App\Auth\Auth;
use App\Auth\LoginThrottle;
use App\Core\JsonResponse;
use App\Core\Request;
use App\Repositories\UserRepository;
use App\Validators\UserValidator;

/**
 * API tài khoản của chính mình
 *
 *   GET  api/auth.php                          user đang đăng nhập → {"user": {...}} hoặc {"user": null}
 *   POST api/auth.php?action=login             {username, password} → {"user": {...}}
 *                                              sai → 401, sai quá 3 lần → 429 {retry_after}
 *   POST api/auth.php?action=logout            → {"ok": true}
 *   POST api/auth.php?action=register          {username, password} → 201 {"user": {...}} (vai trò user, đăng nhập luôn)
 *   POST api/auth.php?action=change_password   {current_password, new_password} → {"ok": true} (đăng nhập)
 *                                              sai mật khẩu hiện tại → 422, sai quá 3 lần → 429
 */
class AuthController
{
    /** @var Request */
    private $request;

    /** @var Auth */
    private $auth;

    /** @var UserRepository */
    private $users;

    /** @var UserValidator */
    private $validator;

    /** @var LoginThrottle */
    private $throttle;

    public function __construct(
        Request $request,
        Auth $auth,
        UserRepository $users,
        UserValidator $validator,
        LoginThrottle $throttle
    ) {
        $this->request   = $request;
        $this->auth      = $auth;
        $this->users     = $users;
        $this->validator = $validator;
        $this->throttle  = $throttle;
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
            case 'POST register':
                $this->register();
                break;
            case 'POST change_password':
                $this->changePassword();
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
        $ip       = $this->request->ip();

        if ($username === '' || $password === '') {
            JsonResponse::error('Vui lòng nhập tên đăng nhập và mật khẩu.', 422);
        }

        // Kiểm tra khóa TRƯỚC khi so mật khẩu: đang bị khóa thì đúng mật khẩu cũng phải chờ
        $this->throttle->ensureAllowed($username, $ip);

        $user = $this->auth->attempt($username, $password);
        if (!$user) {
            // Không nói rõ sai tên hay sai mật khẩu, để không lộ tên đăng nhập nào tồn tại
            $this->throttle->fail($username, $ip, 'Sai tên đăng nhập hoặc mật khẩu.', 401);
        }

        $this->throttle->clear($username, $ip);
        JsonResponse::send(['user' => $user]);
    }

    public function logout(): void
    {
        $this->request->json(); // bắt buộc JSON để chống CSRF
        $this->auth->logout();
        JsonResponse::send(['ok' => true]);
    }

    // Ai cũng tự đăng ký được; tài khoản mới luôn là 'user' (chỉ admin mới tạo được admin)
    public function register(): void
    {
        $body     = $this->request->json();
        $username = Request::stringField($body, 'username');
        $password = Request::stringField($body, 'password', '', false);

        $error = $this->validator->usernameError($username) ?: $this->validator->passwordError($password);
        if ($error !== '') {
            JsonResponse::error($error, 422);
        }

        $user = $this->users->create($username, Auth::hashPassword($password), Auth::ROLE_USER);
        if (!$user) {
            JsonResponse::error('Tên đăng nhập đã có người dùng.', 409);
        }

        $this->auth->login($user);
        JsonResponse::send(['user' => Auth::publicUser($user)], 201);
    }

    public function changePassword(): void
    {
        $user    = $this->auth->requireLogin();
        $body    = $this->request->json();
        $current = Request::stringField($body, 'current_password', '', false);
        $new     = Request::stringField($body, 'new_password', '', false);
        $ip      = $this->request->ip();

        $error = $this->validator->passwordError($new);
        if ($error !== '') {
            JsonResponse::error($error, 422);
        }
        if ($new === $current) {
            JsonResponse::error('Mật khẩu mới phải khác mật khẩu hiện tại.', 422);
        }

        // Dùng chung bộ đếm với đăng nhập: không dò được mật khẩu qua form đổi mật khẩu.
        // Sai mật khẩu hiện tại trả 422 (KHÔNG phải 401 — 401 làm React tự đăng xuất).
        $this->throttle->ensureAllowed($user['username'], $ip);
        if (!$this->auth->changePassword($user, $current, $new)) {
            $this->throttle->fail($user['username'], $ip, 'Mật khẩu hiện tại không đúng.', 422);
        }

        $this->throttle->clear($user['username'], $ip);
        JsonResponse::send(['ok' => true]);
    }
}
