<?php
namespace App\Repositories;

use App\Core\Database;

// Thao tác CSDL với bảng users (tài khoản đăng nhập)
class UserRepository
{
    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Tài khoản theo tên đăng nhập, không có thì null.
     * Kết quả CÓ password_hash — chỉ dùng để kiểm tra mật khẩu, không trả ra ngoài API.
     *
     * @return array{id: int, username: string, password_hash: string, role: string}|null
     */
    public function findByUsername(string $username): ?array
    {
        $user = $this->db->query(
            "SELECT id, username, password_hash, role FROM users WHERE username = ?",
            's',
            [$username]
        )->fetch_assoc();

        if (!$user) {
            return null;
        }
        $user['id'] = (int)$user['id'];
        return $user;
    }
}
