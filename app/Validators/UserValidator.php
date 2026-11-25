<?php
namespace App\Validators;

use App\Auth\Auth;

/**
 * Kiểm tra tên đăng nhập, mật khẩu, vai trò. Mỗi hàm trả về thông báo lỗi, '' = hợp lệ.
 *
 *   $error = $validator->passwordError($password);
 *   if ($error !== '') { ...trả lỗi 422... }
 *
 * Quy tắc giống hệt frontend/src/utils/validation.js (kiểm tra ở cả 2 nơi:
 * React để báo lỗi ngay, PHP vì ai cũng có thể gọi thẳng API).
 */
class UserValidator
{
    public const USERNAME_MIN = 3;
    public const USERNAME_MAX = 50;  // = VARCHAR(50) của users.username
    public const PASSWORD_MIN = 6;
    public const PASSWORD_MAX = 72;  // bcrypt chỉ dùng 72 byte đầu

    public const ROLES = [Auth::ROLE_ADMIN, Auth::ROLE_USER];

    // Chữ không dấu, số, dấu gạch dưới
    private const USERNAME_PATTERN = '/^[A-Za-z0-9_]+$/';

    // Ký tự in được trong bảng ASCII (mã 33–126): chữ KHÔNG DẤU, số, ký hiệu trên bàn phím.
    // Không cho chữ có dấu (á, ư, ô...) và khoảng trắng.
    private const PASSWORD_PATTERN = '/^[\x21-\x7E]+$/';

    public function usernameError(string $username): string
    {
        if (!preg_match(self::USERNAME_PATTERN, $username)) {
            return 'Tên đăng nhập chỉ gồm chữ không dấu, số và dấu gạch dưới (_).';
        }
        $length = strlen($username); // chỉ có ký tự ASCII nên strlen = số ký tự
        if ($length < self::USERNAME_MIN || $length > self::USERNAME_MAX) {
            return 'Tên đăng nhập dài từ ' . self::USERNAME_MIN . ' đến ' . self::USERNAME_MAX . ' ký tự.';
        }
        return '';
    }

    public function passwordError(string $password): string
    {
        if (!preg_match(self::PASSWORD_PATTERN, $password)) {
            return 'Mật khẩu chỉ gồm chữ không dấu, số và ký hiệu (!@#...), không có chữ có dấu hay khoảng trắng.';
        }
        $length = strlen($password);
        if ($length < self::PASSWORD_MIN || $length > self::PASSWORD_MAX) {
            return 'Mật khẩu dài từ ' . self::PASSWORD_MIN . ' đến ' . self::PASSWORD_MAX . ' ký tự.';
        }
        return '';
    }

    public function roleError(string $role): string
    {
        return in_array($role, self::ROLES, true) ? '' : 'Vai trò không hợp lệ.';
    }
}
