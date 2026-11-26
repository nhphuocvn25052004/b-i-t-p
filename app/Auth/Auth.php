<?php
namespace App\Auth;

use App\Core\JsonResponse;
use App\Repositories\SuperAdminRepository;
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
 * Phân quyền:
 *   superadmin — admin tối cao, nằm ở BẢNG RIÊNG super_admins: thấy và quản lý mọi tài khoản
 *   admin      — quản trị thường: mọi quyền với sản phẩm, nhưng chỉ quản lý được cây tài khoản
 *                do mình tạo / nâng lên (không thấy các quản trị khác)
 *   editor     — (user1) chỉ làm những việc được cấp, lưu trong bảng user_permissions
 *   user       — (user2) chỉ xem
 *
 * Session ghi thêm 'super' => true/false để biết id thuộc bảng nào (2 bảng có thể trùng id).
 *
 *   $user = $auth->attempt('admin', 'admin123');           // đúng → user, sai → null
 *   $auth->requireRole(Auth::ROLE_ADMIN);                  // không phải admin → 403
 *   $auth->requirePermission(Auth::PERM_PRODUCTS_CREATE);  // không có quyền → 403
 */
class Auth
{
    public const SESSION_NAME = 'BITP_SESSION';
    public const ROLE_SUPER   = 'superadmin';  // bảng super_admins
    public const ROLE_ADMIN   = 'admin';
    public const ROLE_EDITOR  = 'editor';  // user1
    public const ROLE_USER    = 'user';    // user2 — chỉ xem

    // Các quyền admin có thể cấp cho editor
    public const PERM_PRODUCTS_CREATE = 'products.create';
    public const PERM_PRODUCTS_UPDATE = 'products.update';
    public const PERM_PRODUCTS_DELETE = 'products.delete';
    public const PERM_USERS_CREATE    = 'users.create';    // tạo / xóa tài khoản xem (user2) của chính mình

    public const PERMISSIONS = [
        self::PERM_PRODUCTS_CREATE,
        self::PERM_PRODUCTS_UPDATE,
        self::PERM_PRODUCTS_DELETE,
        self::PERM_USERS_CREATE,
    ];

    /** @var UserRepository */
    private $users;

    /** @var SuperAdminRepository */
    private $superAdmins;

    public function __construct(UserRepository $users, SuperAdminRepository $superAdmins)
    {
        $this->users       = $users;
        $this->superAdmins = $superAdmins;
    }

    // Admin tối cao (bảng super_admins)?
    public static function isSuper(array $user): bool
    {
        return $user['role'] === self::ROLE_SUPER;
    }

    // Băm mật khẩu để lưu vào CSDL (bcrypt, tự sinh salt)
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Thông tin user được phép trả ra API (bỏ password_hash).
     * permissions: quyền admin đã cấp (chỉ editor có; admin có mọi quyền nên để trống).
     * @return array{id: int, username: string, role: string, permissions: string[]}
     */
    public static function publicUser(array $user): array
    {
        return [
            'id'          => (int)$user['id'],
            'username'    => $user['username'],
            'role'        => $user['role'],
            'permissions' => $user['permissions'] ?? [],
        ];
    }

    /**
     * $user có được làm $permission không.
     *   superadmin, admin → luôn được; editor → nếu đã được cấp; user → không bao giờ.
     *   (Quản lý TÀI KHOẢN NÀO thì còn giới hạn theo cây — xem UserController.)
     */
    public static function can(array $user, string $permission): bool
    {
        if ($user['role'] === self::ROLE_SUPER || $user['role'] === self::ROLE_ADMIN) {
            return true;
        }
        return $user['role'] === self::ROLE_EDITOR
            && in_array($permission, $user['permissions'] ?? [], true);
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
        return $user ? self::publicUser($this->withPermissions($user)) : null;
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
        return self::publicUser($this->withPermissions($user));
    }

    // User đang đăng nhập (đọc mới từ CSDL), hoặc null
    public function user(): ?array
    {
        $this->startSession(true);
        $id     = (int)($_SESSION['user_id'] ?? 0);
        $marker = (string)($_SESSION['pw'] ?? '');
        $super  = !empty($_SESSION['super']);
        if ($id <= 0) {
            return null;
        }

        $user = $super ? $this->superAdmins->find($id) : $this->users->find($id);
        // Tài khoản đã bị xóa, hoặc mật khẩu đã đổi ở phiên khác → coi như chưa đăng nhập
        if (!$user || !hash_equals(self::passwordMarker($user['password_hash']), $marker)) {
            return null;
        }
        // Quyền cũng đọc lại mỗi request → admin bỏ quyền là có hiệu lực ngay
        return self::publicUser($this->withPermissions($user));
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
            'super'   => self::isSuper($user),
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
        $repo = self::isSuper($user) ? $this->superAdmins : $this->users;
        $row  = $repo->find((int)$user['id']);
        if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
            return false;
        }

        $row['password_hash'] = self::hashPassword($newPassword);
        $repo->updatePassword($row['id'], $row['password_hash']);
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

    // Authorization: bắt buộc có quyền $permission (xem can()), nếu không thì trả về 403
    public function requirePermission(string $permission): array
    {
        $user = $this->requireLogin();
        if (!self::can($user, $permission)) {
            JsonResponse::error('Bạn không có quyền thực hiện thao tác này.', 403);
        }
        return $user;
    }

    // Gắn danh sách quyền vào tài khoản (chỉ editor mới cần đọc bảng user_permissions)
    private function withPermissions(array $user): array
    {
        $user['permissions'] = $user['role'] === self::ROLE_EDITOR
            ? $this->users->permissions((int)$user['id'])
            : [];
        return $user;
    }

    // Tài khoản (có password_hash) nếu đúng mật khẩu, sai thì null
    private function findVerified(string $username, string $password): ?array
    {
        if ($username === '') {
            return null;
        }
        // Tìm ở bảng admin tối cao trước, rồi tới bảng users (tên không trùng giữa 2 bảng)
        $user = $this->superAdmins->findByUsername($username) ?? $this->users->findByUsername($username);

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
