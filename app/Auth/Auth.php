<?php
namespace App\Auth;

use App\Core\JsonResponse;
use App\Repositories\UserRepository;

/**
 * Xác thực (Authentication) và phân quyền (Authorization) bằng PHP Session.
 *
 * Sau khi đăng nhập, id của user được lưu trên SERVER trong $_SESSION.
 * Trình duyệt chỉ giữ cookie chứa session id (HttpOnly — JavaScript không đọc được),
 * và tự gửi cookie này kèm mọi request tới api/*.php.
 *
 * Mỗi request đọc lại tài khoản từ CSDL, nên:
 *   - admin đổi vai trò / xóa tài khoản → có hiệu lực ngay ở request kế tiếp
 *   - đổi mật khẩu → các phiên đăng nhập khác của tài khoản đó bị đăng xuất
 *     (session lưu "dấu" của password_hash; hash đổi thì dấu không còn khớp)
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

    // Băm mật khẩu để lưu vào CSDL (bcrypt, tự sinh salt)
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Thông tin user được phép trả ra API (bỏ password_hash).
     * @return array{id: int, username: string, role: string}
     */
    public static function publicUser(array $user): array
    {
        return [
            'id'       => (int)$user['id'],
            'username' => $user['username'],
            'role'     => $user['role'],
        ];
    }

    /**
     * Kiểm tra tên đăng nhập + mật khẩu.
     * Đúng thì trả về user (KHÔNG có password_hash), sai thì null.
     *
     * @return array{id: int, username: string, role: string}|null
     */
    public function verifyCredentials(string $username, string $password): ?array
    {
        $user = $this->findVerified($username, $password);
        return $user ? self::publicUser($user) : null;
    }

    /**
     * Kiểm tra mật khẩu, đúng thì đăng nhập luôn.
     * @return array|null user đã đăng nhập, sai thì null
     */
    public function attempt(string $username, string $password): ?array
    {
        $user = $this->findVerified($username, $password);
        if (!$user) {
            return null;
        }
        $this->login($user);
        return self::publicUser($user);
    }

    // User đang đăng nhập (đọc mới từ CSDL), hoặc null
    public function user(): ?array
    {
        $this->startSession(true);
        $id     = (int)($_SESSION['user_id'] ?? 0);
        $marker = (string)($_SESSION['pw'] ?? '');
        if ($id <= 0) {
            return null;
        }

        $user = $this->users->find($id);
        // Tài khoản đã bị xóa, hoặc mật khẩu đã đổi ở phiên khác → coi như chưa đăng nhập
        if (!$user || !hash_equals(self::passwordMarker($user['password_hash']), $marker)) {
            return null;
        }
        return self::publicUser($user);
    }

    /**
     * Lưu user vào session (gọi sau khi đã kiểm tra mật khẩu, đăng ký, hoặc đổi mật khẩu).
     * @param array $user dòng trong bảng users, CÓ password_hash
     */
    public function login(array $user): void
    {
        $this->startSession();
        session_regenerate_id(true); // đổi session id sau khi đăng nhập — chống session fixation
        $_SESSION = [
            'user_id' => (int)$user['id'],
            'pw'      => self::passwordMarker($user['password_hash']),
        ];
        session_write_close();
    }

    public function logout(): void
    {
        $this->startSession();
        $_SESSION = [];
        setcookie(session_name(), '', ['expires' => time() - 3600] + $this->cookieOptions());
        session_destroy();
    }

    /**
     * Đổi mật khẩu của $user. Sai mật khẩu hiện tại thì trả về false, không đổi gì.
     * Đổi xong thì đăng nhập lại phiên hiện tại; các phiên khác bị đăng xuất.
     */
    public function changePassword(array $user, string $currentPassword, string $newPassword): bool
    {
        $row = $this->users->find((int)$user['id']);
        if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
            return false;
        }

        $row['password_hash'] = self::hashPassword($newPassword);
        $this->users->updatePassword($row['id'], $row['password_hash']);
        $this->login($row);
        return true;
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

    // Tài khoản (có password_hash) nếu đúng mật khẩu, sai thì null
    private function findVerified(string $username, string $password): ?array
    {
        if ($username === '') {
            return null;
        }
        $user = $this->users->findByUsername($username);

        // Mật khẩu chỉ lưu dạng băm (password_hash), so sánh bằng password_verify
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return null;
        }
        return $user;
    }

    // "Dấu" của mật khẩu lưu trong session — không phải hash thật, không dùng để đăng nhập được
    private static function passwordMarker(string $passwordHash): string
    {
        return hash('sha256', $passwordHash);
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
