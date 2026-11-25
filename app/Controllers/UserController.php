<?php
namespace App\Controllers;

use App\Auth\Auth;
use App\Core\JsonResponse;
use App\Core\Request;
use App\Repositories\UserRepository;
use App\Validators\UserValidator;

/**
 * API quản lý người dùng — CHỈ admin (người khác → 401 / 403)
 *
 *   GET    api/users.php                         [{id, username, role, created_at}]
 *   POST   api/users.php       {username, password, role}   tạo tài khoản → 201 {"user": {...}}
 *   PUT    api/users.php?id=3  {role}                       đổi vai trò → {"ok": true}
 *   DELETE api/users.php?id=3                               xóa tài khoản → {"ok": true}
 *
 * Admin không được đổi vai trò hay xóa chính mình → luôn còn ít nhất 1 admin
 * (người đang thao tác), không ai tự khóa mình khỏi trang quản trị.
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

    public function __construct(Request $request, Auth $auth, UserRepository $users, UserValidator $validator)
    {
        $this->request   = $request;
        $this->auth      = $auth;
        $this->users     = $users;
        $this->validator = $validator;
    }

    public function handle(): void
    {
        // Mọi thao tác đều cần admin — kiểm tra 1 lần ở đây
        $admin = $this->auth->requireRole(Auth::ROLE_ADMIN);
        $id    = $this->request->queryInt('id');

        switch ($this->request->method()) {
            case 'GET':
                $this->index();
                break;
            case 'POST':
                $this->store();
                break;
            case 'PUT':
                $this->updateRole($admin, $id);
                break;
            case 'DELETE':
                $this->destroy($admin, $id);
                break;
            default:
                JsonResponse::error('Phương thức không được hỗ trợ.', 405);
        }
    }

    public function index(): void
    {
        JsonResponse::send($this->users->all());
    }

    public function store(): void
    {
        $body     = $this->request->json();
        $username = Request::stringField($body, 'username');
        $password = Request::stringField($body, 'password', '', false);
        $role     = Request::stringField($body, 'role', Auth::ROLE_USER);

        $error = $this->validator->usernameError($username)
              ?: $this->validator->passwordError($password)
              ?: $this->validator->roleError($role);
        if ($error !== '') {
            JsonResponse::error($error, 422);
        }

        $user = $this->users->create($username, Auth::hashPassword($password), $role);
        if (!$user) {
            JsonResponse::error('Tên đăng nhập đã có người dùng.', 409);
        }
        JsonResponse::send(['user' => Auth::publicUser($user)], 201);
    }

    public function updateRole(array $admin, int $id): void
    {
        $this->findOr404($id);
        $this->refuseSelf($admin, $id, 'Bạn không thể đổi vai trò của chính mình.');

        $role  = Request::stringField($this->request->json(), 'role');
        $error = $this->validator->roleError($role);
        if ($error !== '') {
            JsonResponse::error($error, 422);
        }

        $this->users->updateRole($id, $role);
        JsonResponse::send(['ok' => true]);
    }

    public function destroy(array $admin, int $id): void
    {
        $this->request->json(); // bắt buộc JSON để chống CSRF (giống các API ghi dữ liệu khác)
        $this->findOr404($id);
        $this->refuseSelf($admin, $id, 'Bạn không thể xóa tài khoản của chính mình.');

        $this->users->delete($id);
        JsonResponse::send(['ok' => true]);
    }

    private function findOr404(int $id): array
    {
        $user = $id > 0 ? $this->users->find($id) : null;
        if (!$user) {
            JsonResponse::error('Không tìm thấy tài khoản.', 404);
        }
        return $user;
    }

    private function refuseSelf(array $admin, int $id, string $message): void
    {
        if ($admin['id'] === $id) {
            JsonResponse::error($message, 422);
        }
    }
}
