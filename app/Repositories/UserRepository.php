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
    private const SELECT = "SELECT id, username, password_hash, role, created_by FROM users";

    // Danh sách tài khoản kèm quyền (gộp bằng GROUP_CONCAT để không phải truy vấn từng người)
    private const LIST_SELECT = "SELECT u.id, u.username, u.role, u.created_at, u.created_by,
               c.username AS created_by_name,
               (SELECT GROUP_CONCAT(p.permission ORDER BY p.permission)
                  FROM user_permissions p WHERE p.user_id = u.id) AS permissions
          FROM users u
          LEFT JOIN users c ON c.id = u.created_by";

    // Mọi tài khoản NẰM DƯỚI ? trong cây "ai tạo ai" (con, cháu, chắt...) — CTE đệ quy của MySQL 8
    private const DESCENDANTS = "WITH RECURSIVE tree (id) AS (
                SELECT id FROM users WHERE created_by = ?
                UNION ALL
                SELECT u.id FROM users u JOIN tree t ON u.created_by = t.id
            )";

    /** @var Database */
    private $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Tài khoản theo tên đăng nhập, không có thì null.
     * @return array{id: int, username: string, password_hash: string, role: string, created_by: ?int}|null
     */
    public function findByUsername(string $username): ?array
    {
        return $this->one(self::SELECT . " WHERE username = ?", 's', [$username]);
    }

    /** @return array{id: int, username: string, password_hash: string, role: string, created_by: ?int}|null */
    public function find(int $id): ?array
    {
        return $this->one(self::SELECT . " WHERE id = ?", 'i', [$id]);
    }

    /**
     * Mọi tài khoản cho trang quản lý (admin) — KHÔNG có password_hash.
     * @return array<array{id: int, username: string, role: string, created_at: string, created_by: ?int, permissions: string[]}>
     */
    public function all(): array
    {
        return $this->listRows($this->db->query(self::LIST_SELECT . " ORDER BY u.id ASC"));
    }

    /**
     * Mọi tài khoản nằm dưới $ancestorId trong cây (con, cháu...). $role khác null thì chỉ lấy vai trò đó.
     * @return array<array{id: int, username: string, role: string, created_at: string, created_by: ?int, permissions: string[]}>
     */
    public function descendants(int $ancestorId, ?string $role = null): array
    {
        $sql    = self::DESCENDANTS . " " . self::LIST_SELECT . " WHERE u.id IN (SELECT id FROM tree)";
        $types  = 'i';
        $params = [$ancestorId];
        if ($role !== null) {
            $sql     .= " AND u.role = ?";
            $types   .= 's';
            $params[] = $role;
        }
        return $this->listRows($this->db->query($sql . " ORDER BY u.id ASC", $types, $params));
    }

    /**
     * id của mọi tài khoản nằm dưới $ancestorId (con, cháu...). $role khác null thì chỉ lấy vai trò đó.
     * @return int[]
     */
    public function descendantIds(int $ancestorId, ?string $role = null): array
    {
        $sql    = self::DESCENDANTS . " SELECT u.id FROM users u WHERE u.id IN (SELECT id FROM tree)";
        $types  = 'i';
        $params = [$ancestorId];
        if ($role !== null) {
            $sql     .= " AND u.role = ?";
            $types   .= 's';
            $params[] = $role;
        }
        return array_map('intval', array_column($this->db->query($sql, $types, $params)->fetch_all(), 0));
    }

    // Quản trị gần nhất phía trên $id trong cây (không tính chính $id), không có thì null
    public function nearestAdminAbove(int $id): ?int
    {
        $row = $this->db->query(
            "WITH RECURSIVE up (id, created_by, role, depth) AS (
                 SELECT id, created_by, role, 0 FROM users WHERE id = ?
                 UNION ALL
                 SELECT u.id, u.created_by, u.role, up.depth + 1 FROM users u JOIN up ON u.id = up.created_by
             )
             SELECT id FROM up WHERE depth > 0 AND role = 'admin' ORDER BY depth LIMIT 1",
            'i',
            [$id]
        )->fetch_row();
        return $row ? (int)$row[0] : null;
    }

    // $id có nằm dưới $ancestorId trong cây không (chính $ancestorId thì không tính)
    public function isDescendant(int $ancestorId, int $id): bool
    {
        return (bool)$this->db->query(
            self::DESCENDANTS . " SELECT 1 FROM tree WHERE id = ? LIMIT 1",
            'ii',
            [$ancestorId, $id]
        )->fetch_row();
    }

    /**
     * Tạo tài khoản. Tên đăng nhập đã tồn tại thì trả về null.
     * (Dựa vào khóa UNIQUE của cột username, nên 2 request cùng lúc cũng không tạo trùng.)
     * @param int|null $createdBy id người tạo (null = tự đăng ký)
     * @return array|null tài khoản vừa tạo (có password_hash)
     */
    public function create(string $username, string $passwordHash, string $role, ?int $createdBy = null): ?array
    {
        // Tên đăng nhập dùng chung với bảng super_admins (đăng nhập cùng 1 form) → không được trùng
        if ($this->db->query("SELECT 1 FROM super_admins WHERE username = ?", 's', [$username])->fetch_row()) {
            return null;
        }
        try {
            $this->db->query(
                "INSERT INTO users (username, password_hash, role, created_by) VALUES (?, ?, ?, ?)",
                'sssi',
                [$username, $passwordHash, $role, $createdBy]
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

    /**
     * Quyền admin đã cấp cho tài khoản $id (xem Auth::PERMISSIONS).
     * @return string[]
     */
    public function permissions(int $id): array
    {
        $rows = $this->db->query(
            "SELECT permission FROM user_permissions WHERE user_id = ? ORDER BY permission",
            'i',
            [$id]
        )->fetch_all(MYSQLI_ASSOC);
        return array_column($rows, 'permission');
    }

    /**
     * Thay toàn bộ quyền của tài khoản $id bằng $permissions (mảng rỗng = bỏ hết quyền).
     * @param string[] $permissions
     */
    public function setPermissions(int $id, array $permissions): void
    {
        $this->db->transaction(function (Database $db) use ($id, $permissions) {
            $db->query("DELETE FROM user_permissions WHERE user_id = ?", 'i', [$id]);
            $stmt = $db->prepare("INSERT INTO user_permissions (user_id, permission) VALUES (?, ?)");
            foreach (array_unique($permissions) as $permission) {
                $stmt->bind_param('is', $id, $permission);
                $stmt->execute();
            }
        });
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        $this->db->query("UPDATE users SET password_hash = ? WHERE id = ?", 'si', [$passwordHash, $id]);
    }

    /**
     * Xóa tài khoản. Những người nó đã tạo — cùng sản phẩm, danh mục của nó — được chuyển lên
     * cho người tạo ra nó (vd. A tạo B, B tạo C; xóa B → C thuộc A), để không bị "mồ côi" khỏi cây của A.
     */
    public function delete(int $id): void
    {
        $this->db->transaction(function (Database $db) use ($id) {
            $row = $db->query("SELECT created_by FROM users WHERE id = ?", 'i', [$id])->fetch_assoc();
            if (!$row) {
                return;
            }
            $parent = $row['created_by'] === null ? null : (int)$row['created_by'];
            $db->query("UPDATE users SET created_by = ? WHERE created_by = ?", 'ii', [$parent, $id]);
            $db->query("UPDATE products SET created_by = ? WHERE created_by = ?", 'ii', [$parent, $id]);
            // Tên danh mục chỉ duy nhất trong 1 chủ: trùng tên với danh mục của người nhận thì giữ nguyên (về NULL khi xóa)
            $db->query(
                "UPDATE IGNORE categories SET owner_id = ? WHERE owner_id = ?",
                'ii',
                [$parent, $id]
            );
            $db->query("DELETE FROM users WHERE id = ?", 'i', [$id]);
        });
    }

    private function listRows(\mysqli_result $result): array
    {
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        foreach ($rows as &$row) {
            $row['id']          = (int)$row['id'];
            $row['created_by']  = $row['created_by'] === null ? null : (int)$row['created_by'];
            $row['permissions'] = $row['permissions'] ? explode(',', $row['permissions']) : [];
        }
        return $rows;
    }

    private function one(string $sql, string $types, array $params): ?array
    {
        $user = $this->db->query($sql, $types, $params)->fetch_assoc();
        if (!$user) {
            return null;
        }
        $user['id']         = (int)$user['id'];
        $user['created_by'] = $user['created_by'] === null ? null : (int)$user['created_by'];
        return $user;
    }
}
