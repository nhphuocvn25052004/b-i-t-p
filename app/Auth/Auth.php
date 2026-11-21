<?php
namespace App\Auth;

use App\Core\JsonResponse;
use App\Repositories\UserRepository;

/**
 * Xác thực (Authentication) và phân quyền (Authorization) bằng PHP Session.
 *
 * Sau khi đăng nhập, thông tin user được lưu trên SERVER trong $_SESSION.
 * Trình duyệt chỉ giữ cookie chứa session id (HttpOnly — JavaScript không đọc được),
 * và tự gửi cookie này kèm mọi request tới api/*.php.
 *
 *   $user = $auth->attempt('admin', 'admin123');   // đúng → user, sai → null
 *   $auth->requireRole('admin');                   // không phải admin → 403
 */
class Auth
{
    public const SESSION_NAME = 'BITP_SESSION';
    public const ROLE_ADMIN   = 'admin';
    public const ROLE_USER    = 'user';

    /** @var UserRepository */
    private $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    /**
     * Kiểm tra tên đăng nhập + mật khẩu.
     * Đúng thì trả về user (KHÔNG có password_hash), sai thì null.
     *
     * @return array{id: int, username: string, role: string}|null
     */
    public function verifyCredentials(string $username, string $password): ?array
    {
        if ($username === '') {
            return null;
        }
        $user = $this->users->findByUsername($username);

        // Mật khẩu chỉ lưu dạng băm (password_hash), so sánh bằng password_verify
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }

        return [
            'id'       => $user['id'],
            'username' => $user['username'],
            'role'     => $user['role'],
        ];
    }

    /**
     * Kiểm tra mật khẩu, đúng thì đăng nhập luôn.
     * @return array|null user đã đăng nhập, sai thì null
     */
    public function attempt(string $username, string $password): ?array
    {
        $user = $this->verifyCredentials($username, $password);
        if ($user) {
            $this->login($user);
        }
        return $user;
    }

    // User đang đăng nhập, hoặc null
    public function user(): ?array
    {
        $this->startSession(true);
        return $_SESSION['user'] ?? null;
    }

    // Lưu user vào session (gọi sau khi đã kiểm tra mật khẩu)
    public function login(array $user): void
    {
        $this->startSession();
        session_regenerate_id(true); // đổi session id sau khi đăng nhập — chống session fixation
        $_SESSION['user'] = $user;
        session_write_close();
    }

    public function logout(): void
    {
        $this->startSession();
        $_SESSION = [];
        setcookie(session_name(), '', ['expires' => time() - 3600] + $this->cookieOptions());
        session_destroy();
    }

    // Authentication: bắt buộc đăng nhập, nếu chưa thì trả về 401
    public function requireLogin(): array
    {
        $user = $this->user();
        if (!$user) {
            JsonResponse::error('Bạn cần đăng nhập.', 401);
        }
        return $user;
    }

    // Authorization: bắt buộc có vai trò $role, nếu không thì trả về 403
    public function requireRole(string $role): array
    {
        $user = $this->requireLogin();
        if ($user['role'] !== $role) {
            JsonResponse::error('Bạn không có quyền thực hiện thao tác này.', 403);
        }
        return $user;
    }

    private function startSession(bool $readOnly = false): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        session_name(self::SESSION_NAME);
        // lifetime 0: cookie mất khi đóng trình duyệt
        session_set_cookie_params(['lifetime' => 0] + $this->cookieOptions());
        // read_and_close: đọc xong đóng ngay, không khóa file session khi chỉ cần đọc
        session_start($readOnly ? ['read_and_close' => true] : []);
    }

    // Thuộc tính cookie phiên — dùng chung khi tạo (đăng nhập) và xóa (đăng xuất)
    private function cookieOptions(): array
    {
        return [
            'path'     => '/',
            'httponly' => true,   // chặn JavaScript đọc cookie (giảm thiệt hại khi bị XSS)
            'samesite' => 'Lax',  // không gửi cookie với request POST/PUT/DELETE từ trang khác
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ];
    }
}
