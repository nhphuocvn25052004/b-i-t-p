<?php
namespace App\Repositories;

use App\Core\Database;
use mysqli_sql_exception;

/**
 * Thao tác CSDL với bảng users (tài khoản đăng nhập).
 *
 * Các hàm find* trả về CẢ password_hash — chỉ dùng để kiểm tra mật khẩu,
 * không trả ra ngoài API (controller dùng Auth::publicUser() hoặc all()).
 */
class UserRepository
{
    private const SELECT = "SELECT id, username, password_hash, role FROM users";

    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Tài khoản theo tên đăng nhập, không có thì null.
     * @return array{id: int, username: string, password_hash: string, role: string}|null
     */
    public function findByUsername(string $username): ?array
    {
        return $this->one(self::SELECT . " WHERE username = ?", 's', [$username]);
    }

    /** @return array{id: int, username: string, password_hash: string, role: string}|null */
    public function find(int $id): ?array
    {
        return $this->one(self::SELECT . " WHERE id = ?", 'i', [$id]);
    }

    /**
     * Mọi tài khoản cho trang quản lý — KHÔNG có password_hash.
     * @return array<array{id: int, username: string, role: string, created_at: string}>
     */
    public function all(): array
    {
        $rows = $this->db->query("SELECT id, username, role, created_at FROM users ORDER BY id ASC")
                         ->fetch_all(MYSQLI_ASSOC);
        foreach ($rows as &$row) {
            $row['id'] = (int)$row['id'];
        }
        return $rows;
    }

    /**
     * Tạo tài khoản. Tên đăng nhập đã tồn tại thì trả về null.
     * (Dựa vào khóa UNIQUE của cột username, nên 2 request cùng lúc cũng không tạo trùng.)
     * @return array|null tài khoản vừa tạo (có password_hash)
     */
    public function create(string $username, string $passwordHash, string $role): ?array
    {
        try {
            $this->db->query(
                "INSERT INTO users (username, password_hash, role) VALUES (?, ?, ?)",
                'sss',
                [$username, $passwordHash, $role]
            );
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === Database::ERR_DUPLICATE) {
                return null;
            }
            throw $e;
        }
        return $this->findByUsername($username);
    }

    public function updateRole(int $id, string $role): void
    {
        $this->db->query("UPDATE users SET role = ? WHERE id = ?", 'si', [$role, $id]);
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $this->db->query("UPDATE users SET password_hash = ? WHERE id = ?", 'si', [$passwordHash, $id]);
    }

    public function delete(int $id): void
    {
        $this->db->query("DELETE FROM users WHERE id = ?", 'i', [$id]);
    }

    private function one(string $sql, string $types, array $params): ?array
    {
        $user = $this->db->query($sql, $types, $params)->fetch_assoc();
        if (!$user) {
            return null;
        }
        $user['id'] = (int)$user['id'];
        return $user;
    }
}
