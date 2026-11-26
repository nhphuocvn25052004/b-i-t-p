<?php
namespace App\Repositories;

use App\Core\Database;
use mysqli_sql_exception;

/**
 * Thao tác CSDL với bảng super_admins — admin tối cao, tách riêng khỏi bảng users.
 *
 * Mỗi dòng trả về có thêm role = 'superadmin' để Auth xử lý giống tài khoản thường.
 * promoteUser() / demoteToUser() chuyển tài khoản giữa 2 bảng (giữ nguyên mật khẩu, id mới).
 * Các hàm find* trả về CẢ password_hash — chỉ dùng để kiểm tra mật khẩu, không trả ra API.
 */
class SuperAdminRepository
{
    private const SELECT = "SELECT id, username, password_hash, 'superadmin' AS role FROM super_admins";

    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /** @return array{id: int, username: string, password_hash: string, role: string}|null */
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
     * Danh sách admin tối cao — KHÔNG có password_hash.
     * @return array<array{id: int, username: string, created_at: string}>
     */
    public function all(): array
    {
        $rows = $this->db->query("SELECT id, username, created_at FROM super_admins ORDER BY id ASC")
                         ->fetch_all(MYSQLI_ASSOC);
        foreach ($rows as &$row) {
            $row['id'] = (int)$row['id'];
        }
        return $rows;
    }

    /**
     * Tạo admin tối cao. Tên đã có (ở bảng này hoặc bảng users) thì trả về null.
     * @return array|null tài khoản vừa tạo (có password_hash)
     */
    public function create(string $username, string $passwordHash): ?array
    {
        if ($this->db->query("SELECT 1 FROM users WHERE username = ?", 's', [$username])->fetch_row()) {
            return null;
        }
        try {
            $this->db->query(
                "INSERT INTO super_admins (username, password_hash) VALUES (?, ?)",
                'ss',
                [$username, $passwordHash]
            );
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() === Database::ERR_DUPLICATE) {
                return null;
            }
            throw $e;
        }
        return $this->findByUsername($username);
    }

    public function delete(int $id): void
    {
        $this->db->query("DELETE FROM super_admins WHERE id = ?", 'i', [$id]);
    }

    /**
     * Nâng tài khoản $userId (bảng users) lên admin tối cao: chuyển sang bảng super_admins.
     * Những người nó đã tạo → created_by = NULL (thuộc quyền admin tối cao).
     */
    public function promoteUser(int $userId): void
    {
        $this->db->transaction(function (Database $db) use ($userId) {
            $row = $db->query("SELECT username, password_hash, created_at FROM users WHERE id = ?", 'i', [$userId])
                      ->fetch_assoc();
            if (!$row) {
                return;
            }
            $db->query("UPDATE users SET created_by = NULL WHERE created_by = ?", 'i', [$userId]);
            $db->query("DELETE FROM users WHERE id = ?", 'i', [$userId]); // quyền trong user_permissions xóa theo
            $db->query(
                "INSERT INTO super_admins (username, password_hash, created_at) VALUES (?, ?, ?)",
                'sss',
                [$row['username'], $row['password_hash'], $row['created_at']]
            );
        });
    }

    /**
     * Hạ admin tối cao $id xuống bảng users với vai trò $role (admin / editor / user),
     * created_by = NULL → chỉ admin tối cao quản lý.
     */
    public function demoteToUser(int $id, string $role): void
    {
        $this->db->transaction(function (Database $db) use ($id, $role) {
            $row = $db->query("SELECT username, password_hash, created_at FROM super_admins WHERE id = ?", 'i', [$id])
                      ->fetch_assoc();
            if (!$row) {
                return;
            }
            $db->query("DELETE FROM super_admins WHERE id = ?", 'i', [$id]);
            $db->query(
                "INSERT INTO users (username, password_hash, role, created_at) VALUES (?, ?, ?, ?)",
                'ssss',
                [$row['username'], $row['password_hash'], $role, $row['created_at']]
            );
        });
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $this->db->query("UPDATE super_admins SET password_hash = ? WHERE id = ?", 'si', [$passwordHash, $id]);
    }

    private function one(string $sql, string $types, array $params): ?array
    {
        $row = $this->db->query($sql, $types, $params)->fetch_assoc();
        if (!$row) {
            return null;
        }
        $row['id'] = (int)$row['id'];
        return $row;
    }
}
