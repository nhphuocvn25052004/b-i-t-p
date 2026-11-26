<?php
namespace App\Controllers;

use App\Auth\Auth;
use App\Core\JsonResponse;
use App\Core\Request;
use App\Repositories\SuperAdminRepository;
use App\Repositories\UserRepository;
use App\Validators\UserValidator;

/**
 * API quản lý người dùng — admin tối cao, quản trị, hoặc editor được cấp quyền users.create.
 *
 *   GET    api/users.php                                    [{id, username, role, created_at, created_by, created_by_name, permissions}]
 *   POST   api/users.php       {username, password, role, permissions}   tạo tài khoản → 201 {"user": {...}}
 *   PUT    api/users.php?id=3  {role, permissions}                       đổi vai trò / quyền → {"ok": true}
 *                                       role = "superadmin" (chỉ admin tối cao): chuyển sang bảng super_admins
 *   POST   api/users.php?id=3&action=reset_password  {password}          cưỡng chế đặt mật khẩu mới → {"ok": true}
 *   DELETE api/users.php?id=3                                            xóa tài khoản → {"ok": true}
 *
 * Phạm vi — dựa trên cây "ai tạo ai" (cột users.created_by):
 *   superadmin: thấy và làm mọi thứ với mọi tài khoản trong bảng users.
 *   admin:      chỉ thấy / quản lý tài khoản NẰM DƯỚI mình (con, cháu...). Không thấy các quản trị
 *               khác, trừ quản trị do chính mình tạo hoặc nâng lên (họ nằm dưới mình).
 *   editor:     chỉ thấy / xóa / đặt lại mật khẩu tài khoản xem (user) nằm dưới mình; tạo mới luôn ra user;
 *               không đổi được vai trò / quyền.
 *   user:       không vào được (403).
 * Tài khoản ngoài phạm vi trả về 404 — như thể không tồn tại, để không lộ ai đang có trong hệ thống.
 *
 * Không ai tự đổi vai trò / tự xóa mình qua API này (đổi mật khẩu của mình: api/auth.php).
 */
class UserController
{
    /** @var Request */
    private $request;

    /** @var Auth */
    private $auth;

    /** @var UserRepository */
    private $users;

    /** @var UserValidator */
    private $validator;

    /** @var SuperAdminRepository */
    private $superAdmins;

    public function __construct(
        Request $request,
        Auth $auth,
        UserRepository $users,
        UserValidator $validator,
        SuperAdminRepository $superAdmins
    ) {
        $this->request     = $request;
        $this->auth        = $auth;
        $this->users       = $users;
        $this->validator   = $validator;
        $this->superAdmins = $superAdmins;
    }

    public function handle(): void
    {
        // Mọi thao tác đều cần quyền quản lý tài khoản (superadmin / admin luôn có) — kiểm tra 1 lần ở đây
        $me = $this->auth->requirePermission(Auth::PERM_USERS_CREATE);
        $id = $this->request->queryInt('id');

        switch ($this->request->method() . ' ' . $this->request->query('action')) {
            case 'GET ':
                $this->index($me);
                break;
            case 'POST ':
                $this->store($me);
                break;
            case 'POST reset_password':
                $this->resetPassword($me, $id);
                break;
            case 'PUT ':
                $this->update($me, $id);
                break;
            case 'DELETE ':
                $this->destroy($me, $id);
                break;
            default:
                JsonResponse::error('Yêu cầu không hợp lệ.', 400);
        }
    }

    public function index(array $me): void
    {
        if (Auth::isSuper($me)) {
            $list = $this->users->all();
        } elseif ($me['role'] === Auth::ROLE_ADMIN) {
            $list = $this->users->descendants($me['id']);
        } else {
            $list = $this->users->descendants($me['id'], Auth::ROLE_USER);
        }
        JsonResponse::send($list);
    }

    public function store(array $me): void
    {
        $body     = $this->request->json();
        $username = Request::stringField($body, 'username');
        $password = Request::stringField($body, 'password', '', false);
        // Chỉ quản trị được chọn vai trò; editor luôn tạo tài khoản xem (user2)
        $role        = self::isManager($me) ? Request::stringField($body, 'role', Auth::ROLE_USER) : Auth::ROLE_USER;
        $permissions = $body['permissions'] ?? [];

        $error = $this->validator->usernameError($username)
              ?: $this->validator->passwordError($password)
              ?: $this->validator->roleError($role)
              ?: $this->validator->permissionsError($permissions);
        if ($error !== '') {
            JsonResponse::error($error, 422);
        }

        // Người tạo = gốc của cây; admin tối cao không nằm trong bảng users nên để NULL
        $createdBy = Auth::isSuper($me) ? null : $me['id'];
        $user = $this->users->create($username, Auth::hashPassword($password), $role, $createdBy);
        if (!$user) {
            JsonResponse::error('Tên đăng nhập đã có người dùng.', 409);
        }
        $user['permissions'] = $this->savePermissions($user['id'], $role, $permissions);
        JsonResponse::send(['user' => Auth::publicUser($user)], 201);
    }

    // Đổi vai trò và quyền (vd. nâng 1 tài khoản lên quản trị) — chỉ superadmin / admin
    public function update(array $me, int $id): void
    {
        if (!self::isManager($me)) {
            JsonResponse::error('Chỉ quản trị viên mới đổi được vai trò và quyền.', 403);
        }
        $this->refuseSelf($me, $id, 'Bạn không thể đổi vai trò của chính mình.');
        $this->findManagedOr404($me, $id);

        $body        = $this->request->json();
        $role        = Request::stringField($body, 'role');
        $permissions = $body['permissions'] ?? [];

        // Nâng lên admin tối cao — chỉ admin tối cao làm được
        if ($role === Auth::ROLE_SUPER) {
            if (!Auth::isSuper($me)) {
                JsonResponse::error('Chỉ admin tối cao mới nâng được người khác lên admin tối cao.', 403);
            }
            $this->superAdmins->promoteUser($id);
            JsonResponse::send(['ok' => true]);
        }

        $error = $this->validator->roleError($role) ?: $this->validator->permissionsError($permissions);
        if ($error !== '') {
            JsonResponse::error($error, 422);
        }

        $this->users->updateRole($id, $role);
        $this->savePermissions($id, $role, $permissions);
        JsonResponse::send(['ok' => true]);
    }

    // Cưỡng chế đặt mật khẩu mới — không cần mật khẩu cũ. Các phiên đăng nhập của người đó bị đăng xuất.
    public function resetPassword(array $me, int $id): void
    {
        $password = Request::stringField($this->request->json(), 'password', '', false);
        $this->refuseSelf($me, $id, 'Hãy đổi mật khẩu của chính mình ở trang Đổi mật khẩu.');
        $this->findManagedOr404($me, $id);

        $error = $this->validator->passwordError($password);
        if ($error !== '') {
            JsonResponse::error($error, 422);
        }

        $this->users->updatePassword($id, Auth::hashPassword($password));
        JsonResponse::send(['ok' => true]);
    }

    public function destroy(array $me, int $id): void
    {
        $this->request->json(); // bắt buộc JSON để chống CSRF (giống các API ghi dữ liệu khác)
        $this->refuseSelf($me, $id, 'Bạn không thể xóa tài khoản của chính mình.');
        $this->findManagedOr404($me, $id);

        $this->users->delete($id);
        JsonResponse::send(['ok' => true]);
    }

    // Admin tối cao hoặc quản trị thường — được chọn vai trò, cấp quyền
    private static function isManager(array $user): bool
    {
        return Auth::isSuper($user) || $user['role'] === Auth::ROLE_ADMIN;
    }

    /**
     * Tài khoản $id nếu $me được quản lý nó, nếu không thì 404.
     *   superadmin → mọi tài khoản
     *   admin      → tài khoản nằm dưới mình trong cây
     *   editor     → tài khoản xem (user) nằm dưới mình
     */
    private function findManagedOr404(array $me, int $id): array
    {
        $user = $id > 0 ? $this->users->find($id) : null;
        $ok   = $user && (
            Auth::isSuper($me)
            || ($this->users->isDescendant($me['id'], $id)
                && ($me['role'] === Auth::ROLE_ADMIN || $user['role'] === Auth::ROLE_USER))
        );
        if (!$ok) {
            JsonResponse::error('Không tìm thấy tài khoản.', 404);
        }
        return $user;
    }

    /**
     * Lưu quyền — chỉ editor mới có quyền riêng; vai trò khác thì xóa hết.
     * @return string[] quyền đã lưu
     */
    private function savePermissions(int $id, string $role, array $permissions): array
    {
        $permissions = $role === Auth::ROLE_EDITOR ? array_values(array_unique($permissions)) : [];
        $this->users->setPermissions($id, $permissions);
        return $permissions;
    }

    // Admin tối cao ở bảng khác nên id của họ không bao giờ là "chính mình" trong bảng users
    private function refuseSelf(array $me, int $id, string $message): void
    {
        if (!Auth::isSuper($me) && $me['id'] === $id) {
            JsonResponse::error($message, 422);
        }
    }
}
