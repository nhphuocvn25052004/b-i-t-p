<?php
namespace App\Controllers;

use App\Auth\Auth;
use App\Core\JsonResponse;
use App\Core\Request;
use App\Repositories\SuperAdminRepository;
use App\Validators\UserValidator;

/**
 * API quản lý bảng super_admins — CHỈ admin tối cao (người khác → 401 / 403)
 *
 *   GET    api/super_admins.php                                   [{id, username, created_at}]
 *   POST   api/super_admins.php       {username, password}        thêm admin tối cao → 201
 *   POST   api/super_admins.php?id=2&action=reset_password {password}   cưỡng chế đặt mật khẩu mới
 *   POST   api/super_admins.php?id=2&action=demote  {role}         hạ xuống bảng users (admin / editor / user)
 *   DELETE api/super_admins.php?id=2                               xóa
 *
 * (Nâng 1 tài khoản trong users lên admin tối cao: PUT api/users.php?id=… {"role": "superadmin"}.)
 * Không tự hạ cấp / tự xóa mình → luôn còn ít nhất 1 admin tối cao (người đang thao tác).
 */
class SuperAdminController
{
    /** @var Request */
    private $request;

    /** @var Auth */
    private $auth;

    /** @var SuperAdminRepository */
    private $superAdmins;

    /** @var UserValidator */
    private $validator;

    public function __construct(Request $request, Auth $auth, SuperAdminRepository $superAdmins, UserValidator $validator)
    {
        $this->request     = $request;
        $this->auth        = $auth;
        $this->superAdmins = $superAdmins;
        $this->validator   = $validator;
    }

    public function handle(): void
    {
        $me = $this->auth->requireRole(Auth::ROLE_SUPER);
        $id = $this->request->queryInt('id');

        switch ($this->request->method() . ' ' . $this->request->query('action')) {
            case 'GET ':
                JsonResponse::send($this->superAdmins->all());
                break;
            case 'POST ':
                $this->store();
                break;
            case 'POST reset_password':
                $this->resetPassword($me, $id);
                break;
            case 'POST demote':
                $this->demote($me, $id);
                break;
            case 'DELETE ':
                $this->destroy($me, $id);
                break;
            default:
                JsonResponse::error('Yêu cầu không hợp lệ.', 400);
        }
    }

    public function store(): void
    {
        $body     = $this->request->json();
        $username = Request::stringField($body, 'username');
        $password = Request::stringField($body, 'password', '', false);

        $error = $this->validator->usernameError($username) ?: $this->validator->passwordError($password);
        if ($error !== '') {
            JsonResponse::error($error, 422);
        }

        $admin = $this->superAdmins->create($username, Auth::hashPassword($password));
        if (!$admin) {
            JsonResponse::error('Tên đăng nhập đã có người dùng.', 409);
        }
        JsonResponse::send(['user' => Auth::publicUser($admin)], 201);
    }

    public function resetPassword(array $me, int $id): void
    {
        $password = Request::stringField($this->request->json(), 'password', '', false);
        $this->refuseSelf($me, $id, 'Hãy đổi mật khẩu của chính mình ở trang Đổi mật khẩu.');
        $this->findOr404($id);

        $error = $this->validator->passwordError($password);
        if ($error !== '') {
            JsonResponse::error($error, 422);
        }
        $this->superAdmins->updatePassword($id, Auth::hashPassword($password));
        JsonResponse::send(['ok' => true]);
    }

    public function demote(array $me, int $id): void
    {
        $role = Request::stringField($this->request->json(), 'role', Auth::ROLE_USER);
        $this->refuseSelf($me, $id, 'Bạn không thể tự hạ cấp chính mình.');
        $this->findOr404($id);

        $error = $this->validator->roleError($role);
        if ($error !== '') {
            JsonResponse::error($error, 422);
        }
        $this->superAdmins->demoteToUser($id, $role);
        JsonResponse::send(['ok' => true]);
    }

    public function destroy(array $me, int $id): void
    {
        $this->request->json(); // bắt buộc JSON để chống CSRF
        $this->refuseSelf($me, $id, 'Bạn không thể xóa tài khoản của chính mình.');
        $this->findOr404($id);

        $this->superAdmins->delete($id);
        JsonResponse::send(['ok' => true]);
    }

    private function findOr404(int $id): array
    {
        $admin = $id > 0 ? $this->superAdmins->find($id) : null;
        if (!$admin) {
            JsonResponse::error('Không tìm thấy tài khoản.', 404);
        }
        return $admin;
    }

    private function refuseSelf(array $me, int $id, string $message): void
    {
        if ($me['id'] === $id) {
            JsonResponse::error($message, 422);
        }
    }
}
