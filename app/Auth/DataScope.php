<?php
namespace App\Auth;

use App\Repositories\UserRepository;

/**
 * Phạm vi DỮ LIỆU (sản phẩm, danh mục) mà 1 tài khoản được thấy.
 * Trả về null = thấy tất cả; mảng id = chỉ dữ liệu của các chủ đó (mảng rỗng = không thấy gì).
 *
 *   Sản phẩm (products.created_by = người đã thêm):
 *     superadmin → tất cả
 *     admin      → sản phẩm của mình + của mọi tài khoản nằm dưới mình
 *     editor     → chỉ sản phẩm mình thêm
 *     user       → do quản trị / editor tạo: sản phẩm của người đã tạo ra mình (chỉ xem)
 *                  tự đăng ký / do admin tối cao tạo (created_by = NULL, thuộc admin tối cao): TẤT CẢ (chỉ xem)
 *
 *   Danh mục (categories.owner_id = quản trị sở hữu; mỗi quản trị 1 bộ riêng):
 *     superadmin → tất cả
 *     admin      → của mình + của các quản trị nằm dưới mình
 *     editor/user → của quản trị gần nhất phía trên; không có → không có
 *     user thuộc admin tối cao → tất cả
 */
class DataScope
{
    /** @var UserRepository */
    private $users;

    public function __construct(UserRepository $users)
    {
        $this->users = $users;
    }

    /** @return int[]|null */
    public function productOwners(array $user): ?array
    {
        switch ($user['role']) {
            case Auth::ROLE_SUPER:
                return null;
            case Auth::ROLE_ADMIN:
                return array_merge([$user['id']], $this->users->descendantIds($user['id']));
            case Auth::ROLE_EDITOR:
                return [$user['id']];
            default:
                $row = $this->users->find($user['id']);
                if (!$row) {
                    return [];
                }
                // Người xem thuộc admin tối cao → xem được mọi sản phẩm (vẫn không có quyền sửa / xóa)
                return $row['created_by'] === null ? null : [$row['created_by']];
        }
    }

    /** @return int[]|null */
    public function categoryOwners(array $user): ?array
    {
        switch ($user['role']) {
            case Auth::ROLE_SUPER:
                return null;
            case Auth::ROLE_ADMIN:
                return array_merge([$user['id']], $this->users->descendantIds($user['id'], Auth::ROLE_ADMIN));
            default:
                if ($user['role'] === Auth::ROLE_USER && $this->isUnderSuper($user)) {
                    return null;
                }
                $admin = $this->users->nearestAdminAbove($user['id']);
                return $admin !== null ? [$admin] : [];
        }
    }

    // Người xem không do quản trị / editor nào tạo (tự đăng ký, hoặc admin tối cao tạo) → thuộc admin tối cao
    private function isUnderSuper(array $user): bool
    {
        $row = $this->users->find($user['id']);
        return $row !== null && $row['created_by'] === null;
    }

    // Chủ của sản phẩm $user vừa thêm: chính người đó (admin tối cao không ở bảng users → NULL)
    public static function productOwnerFor(array $user): ?int
    {
        return $user['role'] === Auth::ROLE_SUPER ? null : $user['id'];
    }

    /**
     * Ghép điều kiện "cột $column thuộc $owners" vào câu SQL.
     * @param int[]|null $owners
     * @return array{0: string, 1: string, 2: array} [điều kiện ('' = không lọc), kiểu tham số, tham số]
     */
    public static function sqlFilter(string $column, ?array $owners): array
    {
        if ($owners === null) {
            return ['', '', []];
        }
        if (!$owners) {
            return ['1 = 0', '', []];
        }
        $owners = array_values(array_unique($owners));
        return [
            "$column IN (" . implode(',', array_fill(0, count($owners), '?')) . ")",
            str_repeat('i', count($owners)),
            $owners,
        ];
    }
}
